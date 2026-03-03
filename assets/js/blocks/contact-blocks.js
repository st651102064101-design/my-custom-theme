(function(){
'use strict';

/* -- WordPress dependencies -- */
var registerBlockType = wp.blocks.registerBlockType;
var InspectorControls = wp.blockEditor.InspectorControls;
var RichText          = wp.blockEditor.RichText;
var useBlockProps      = wp.blockEditor.useBlockProps;
var BlockControls     = wp.blockEditor.BlockControls;

var PanelBody      = wp.components.PanelBody;
var TextControl    = wp.components.TextControl;
var TextareaControl= wp.components.TextareaControl;
var SelectControl  = wp.components.SelectControl;
var ToggleControl  = wp.components.ToggleControl;
var RangeControl   = wp.components.RangeControl;
var Button         = wp.components.Button;
var Spinner        = wp.components.Spinner;
var ColorPalette   = wp.components.ColorPalette;
var ColorPicker    = wp.components.ColorPicker;

var el       = wp.element.createElement;
var Fragment = wp.element.Fragment;
var useState = wp.element.useState;
var useEffect= wp.element.useEffect;
var apiFetch = wp.apiFetch;

/* -- Shared colour palette (18 colours) -- */
var kvPalette = [
    { name:'Primary Blue',   color:'#0056d6' },
    { name:'Accent Teal',    color:'#4ecdc4' },
    { name:'Green',          color:'#22c55e' },
    { name:'Dark Green',     color:'#166534' },
    { name:'Light Green BG', color:'#f0fdf4' },
    { name:'Blue BG',        color:'#eff6ff' },
    { name:'Red',            color:'#dc2626' },
    { name:'Orange',         color:'#f97316' },
    { name:'Purple',         color:'#7c3aed' },
    { name:'White',          color:'#ffffff' },
    { name:'Light Gray',     color:'#f8fafc' },
    { name:'Gray',           color:'#64748b' },
    { name:'Dark',           color:'#1e293b' },
    { name:'Black',          color:'#000000' },
    { name:'LINE Green',     color:'#06c755' },
    { name:'WhatsApp Green', color:'#25d366' },
    { name:'WeChat Green',   color:'#07c160' },
    { name:'Icon BG Blue',   color:'#e8f0fe' }
];

/* ============================================================ */
/* 1. KV CONTACT FORM                                           */
/* ============================================================ */
registerBlockType('kv/contact-form', {
    title: 'KV Contact Form',
    icon: 'email',
    category: 'widgets',
    supports: { html: false },
    attributes: {
        heading:           { type:'string', default:'Send Us a Message' },
        subtitle:          { type:'string', default:"Fill out the form below and we'll get back to you within 24 hours." },
        headingSize:       { type:'number', default:24 },
        subtitleSize:      { type:'number', default:15 },
        headingColor:      { type:'string', default:'#1e293b' },
        subtitleColor:     { type:'string', default:'#64748b' },
        bgColor:           { type:'string', default:'#ffffff' },
        formBorderRadius:  { type:'number', default:12 },
        inputBg:           { type:'string', default:'#f8fafc' },
        inputBorderRadius: { type:'number', default:8 },
        labelSize:         { type:'number', default:14 },
        nameLabel:         { type:'string', default:'Name' },
        namePlaceholder:   { type:'string', default:'Your name' },
        nameRequired:      { type:'boolean', default:true },
        companyLabel:      { type:'string', default:'Company' },
        companyPlaceholder:{ type:'string', default:'Your company' },
        emailLabel:        { type:'string', default:'Email' },
        emailPlaceholder:  { type:'string', default:'your@email.com' },
        emailRequired:     { type:'boolean', default:true },
        phoneLabel:        { type:'string', default:'Phone' },
        phonePlaceholder:  { type:'string', default:'+66 xxx xxx xxxx' },
        subjectLabel:      { type:'string', default:'Subject' },
        subjectRequired:   { type:'boolean', default:true },
        subjectOptions:    { type:'string', default:"General Inquiry\nRequest a Quote\nTechnical Support\nPartnership Opportunity\nOther" },
        messageLabel:      { type:'string', default:'Message' },
        messagePlaceholder:{ type:'string', default:'How can we help you?' },
        messageRequired:   { type:'boolean', default:true },
        consentText:       { type:'string', default:'I consent to KV Electronics collecting and storing my data for the purpose of responding to my inquiry in accordance with the Privacy Policy (PDPA).' },
        buttonText:        { type:'string', default:'Send Message' },
        buttonColor:       { type:'string', default:'' },
        buttonTextColor:   { type:'string', default:'#ffffff' },
        buttonRadius:      { type:'number', default:8 },
        buttonSize:        { type:'number', default:16 }
    },
    edit: function(props) {
        var a = props.attributes, set = props.setAttributes;
        var blockProps = useBlockProps({ style:{ background:a.bgColor, borderRadius:a.formBorderRadius+'px', padding:'30px', boxShadow:'0 4px 6px -1px rgba(0,0,0,0.1)' } });
        var inputS = { width:'100%', padding:'12px 16px', border:'1px solid #e2e8f0', borderRadius:a.inputBorderRadius+'px', fontSize:'16px', boxSizing:'border-box', fontFamily:'inherit', background:a.inputBg };
        var labelS = { display:'block', fontWeight:'600', marginBottom:'8px', color:'#1e293b', fontSize:a.labelSize+'px' };
        return el(Fragment, {},
            el(InspectorControls, {},
                el(PanelBody, { title:'\u270F\uFE0F Form Text', initialOpen:true },
                    el(TextControl, { label:'Name Label', value:a.nameLabel, onChange:function(v){set({nameLabel:v});} }),
                    el(TextControl, { label:'Name Placeholder', value:a.namePlaceholder, onChange:function(v){set({namePlaceholder:v});} }),
                    el(ToggleControl, { label:'Name Required', checked:a.nameRequired, onChange:function(v){set({nameRequired:v});} }),
                    el(TextControl, { label:'Company Label', value:a.companyLabel, onChange:function(v){set({companyLabel:v});} }),
                    el(TextControl, { label:'Company Placeholder', value:a.companyPlaceholder, onChange:function(v){set({companyPlaceholder:v});} }),
                    el(TextControl, { label:'Email Label', value:a.emailLabel, onChange:function(v){set({emailLabel:v});} }),
                    el(TextControl, { label:'Email Placeholder', value:a.emailPlaceholder, onChange:function(v){set({emailPlaceholder:v});} }),
                    el(ToggleControl, { label:'Email Required', checked:a.emailRequired, onChange:function(v){set({emailRequired:v});} }),
                    el(TextControl, { label:'Phone Label', value:a.phoneLabel, onChange:function(v){set({phoneLabel:v});} }),
                    el(TextControl, { label:'Phone Placeholder', value:a.phonePlaceholder, onChange:function(v){set({phonePlaceholder:v});} }),
                    el(TextControl, { label:'Subject Label', value:a.subjectLabel, onChange:function(v){set({subjectLabel:v});} }),
                    el(ToggleControl, { label:'Subject Required', checked:a.subjectRequired, onChange:function(v){set({subjectRequired:v});} }),
                    el(TextareaControl, { label:'Subject Options (one per line)', value:a.subjectOptions, onChange:function(v){set({subjectOptions:v});} }),
                    el(TextControl, { label:'Message Label', value:a.messageLabel, onChange:function(v){set({messageLabel:v});} }),
                    el(TextControl, { label:'Message Placeholder', value:a.messagePlaceholder, onChange:function(v){set({messagePlaceholder:v});} }),
                    el(ToggleControl, { label:'Message Required', checked:a.messageRequired, onChange:function(v){set({messageRequired:v});} }),
                    el(TextareaControl, { label:'Consent Text', value:a.consentText, onChange:function(v){set({consentText:v});} })
                ),
                el(PanelBody, { title:'\uD83C\uDFA8 Button Style', initialOpen:false },
                    el('p',{style:{fontSize:'12px',fontWeight:'600',marginBottom:'4px'}},'Button Background'),
                    el('p',{style:{fontSize:'11px',color:'#64748b',margin:'-2px 0 6px'}},'(ว่าง = ใช้สี Accent จาก Theme Settings)'),
                    el(ColorPalette, { colors:kvPalette, value:a.buttonColor, onChange:function(v){set({buttonColor:v||''});} }),
                    el('p',{style:{fontSize:'12px',fontWeight:'600',marginBottom:'4px'}},'Button Text Color'),
                    el(ColorPalette, { colors:kvPalette, value:a.buttonTextColor, onChange:function(v){set({buttonTextColor:v||'#ffffff'});} }),
                    el(RangeControl, { label:'Button Font Size', value:a.buttonSize, onChange:function(v){set({buttonSize:v});}, min:12, max:24 }),
                    el(RangeControl, { label:'Button Radius', value:a.buttonRadius, onChange:function(v){set({buttonRadius:v});}, min:0, max:30 })
                ),
                el(PanelBody, { title:'\uD83D\uDCDD Form Style', initialOpen:false },
                    el(RangeControl, { label:'Heading Size', value:a.headingSize, onChange:function(v){set({headingSize:v});}, min:16, max:48 }),
                    el(RangeControl, { label:'Subtitle Size', value:a.subtitleSize, onChange:function(v){set({subtitleSize:v});}, min:10, max:24 }),
                    el(RangeControl, { label:'Label Size', value:a.labelSize, onChange:function(v){set({labelSize:v});}, min:10, max:20 }),
                    el(RangeControl, { label:'Form Border Radius', value:a.formBorderRadius, onChange:function(v){set({formBorderRadius:v});}, min:0, max:30 }),
                    el(RangeControl, { label:'Input Border Radius', value:a.inputBorderRadius, onChange:function(v){set({inputBorderRadius:v});}, min:0, max:20 }),
                    el('p',{style:{fontSize:'12px',fontWeight:'600',marginBottom:'4px'}},'Heading Color'),
                    el(ColorPalette, { colors:kvPalette, value:a.headingColor, onChange:function(v){set({headingColor:v||'#1e293b'});} }),
                    el('p',{style:{fontSize:'12px',fontWeight:'600',marginBottom:'4px'}},'Subtitle Color'),
                    el(ColorPalette, { colors:kvPalette, value:a.subtitleColor, onChange:function(v){set({subtitleColor:v||'#64748b'});} }),
                    el('p',{style:{fontSize:'12px',fontWeight:'600',marginBottom:'4px'}},'Form Background'),
                    el(ColorPalette, { colors:kvPalette, value:a.bgColor, onChange:function(v){set({bgColor:v||'#ffffff'});} }),
                    el('p',{style:{fontSize:'12px',fontWeight:'600',marginBottom:'4px'}},'Input Background'),
                    el(ColorPalette, { colors:kvPalette, value:a.inputBg, onChange:function(v){set({inputBg:v||'#f8fafc'});} })
                )
            ),
            el('div', blockProps,
                el(RichText, { tagName:'h3', value:a.heading, onChange:function(v){set({heading:v});}, placeholder:'Form heading...', style:{fontSize:a.headingSize+'px',margin:'0 0 10px',color:a.headingColor} }),
                el(RichText, { tagName:'p', value:a.subtitle, onChange:function(v){set({subtitle:v});}, placeholder:'Form subtitle...', style:{color:a.subtitleColor,margin:'0 0 25px',fontSize:a.subtitleSize+'px'} }),
                el('div',{style:{display:'grid',gridTemplateColumns:'1fr 1fr',gap:'20px',marginBottom:'20px'}},
                    el('div',null,
                        el(RichText,{tagName:'label',value:a.nameLabel,onChange:function(v){set({nameLabel:v});},style:labelS}),
                        el('input',{type:'text',readOnly:true,placeholder:a.namePlaceholder,style:inputS})
                    ),
                    el('div',null,
                        el(RichText,{tagName:'label',value:a.companyLabel,onChange:function(v){set({companyLabel:v});},style:labelS}),
                        el('input',{type:'text',readOnly:true,placeholder:a.companyPlaceholder,style:inputS})
                    )
                ),
                el('div',{style:{display:'grid',gridTemplateColumns:'1fr 1fr',gap:'20px',marginBottom:'20px'}},
                    el('div',null,
                        el(RichText,{tagName:'label',value:a.emailLabel,onChange:function(v){set({emailLabel:v});},style:labelS}),
                        el('input',{type:'text',readOnly:true,placeholder:a.emailPlaceholder,style:inputS})
                    ),
                    el('div',null,
                        el(RichText,{tagName:'label',value:a.phoneLabel,onChange:function(v){set({phoneLabel:v});},style:labelS}),
                        el('input',{type:'text',readOnly:true,placeholder:a.phonePlaceholder,style:inputS})
                    )
                ),
                el('div',{style:{marginBottom:'20px'}},
                    el(RichText,{tagName:'label',value:a.subjectLabel,onChange:function(v){set({subjectLabel:v});},style:labelS}),
                    el('select',{disabled:true,style:Object.assign({},inputS,{background:'white'})},
                        el('option',null,'Select a subject')
                    )
                ),
                el('div',{style:{marginBottom:'20px'}},
                    el(RichText,{tagName:'label',value:a.messageLabel,onChange:function(v){set({messageLabel:v});},style:labelS}),
                    el('textarea',{readOnly:true,rows:5,placeholder:a.messagePlaceholder,style:Object.assign({},inputS,{resize:'vertical'})})
                ),
                el('div',{style:{marginBottom:'20px',display:'flex',gap:'10px',alignItems:'flex-start'}},
                    el('input',{type:'checkbox',disabled:true,style:{marginTop:'4px'}}),
                    el(RichText,{tagName:'span',value:a.consentText,onChange:function(v){set({consentText:v});},style:{fontSize:'14px',color:'#64748b'}})
                ),
                el('div',{style:{background:a.buttonColor||'var(--wp--preset--color--primary,#0042aa)',borderRadius:a.buttonRadius+'px',padding:'14px 32px',textAlign:'center',cursor:'text'}},
                    el(RichText,{tagName:'span',value:a.buttonText,onChange:function(v){set({buttonText:v});},allowedFormats:[],style:{color:a.buttonTextColor,fontSize:a.buttonSize+'px',fontWeight:'600'}})
                )
            )
        );
    },
    save: function(){ return null; }
});

/* ============================================================ */
/* 2. KV GOOGLE MAP  (Native React – Full WYSIWYG)              */
/* ============================================================ */
registerBlockType('kv/google-map', {
    title: 'KV Google Map',
    icon: 'location-alt',
    category: 'widgets',
    supports: { html: false, reusable: true },
    attributes: {
        mapUrl:         { type:'string',  default:'' },
        height:         { type:'number',  default:400 },
        directionsText: { type:'string',  default:'Get Directions' },
        viewMapText:    { type:'string',  default:'View on Google Maps' },
        callText:       { type:'string',  default:'Call Us' },
        addressLabel:   { type:'string',  default:'Address' },
        phoneLabel:     { type:'string',  default:'Phone' },
        hoursLabel:     { type:'string',  default:'Business Hours' },
        showInfoCard:   { type:'boolean', default:true },
        showAddress:    { type:'boolean', default:true },
        showPhone:      { type:'boolean', default:true },
        showHours:      { type:'boolean', default:true },
        showCTA:        { type:'boolean', default:true },
        showCopy:       { type:'boolean', default:true },
        copyText:       { type:'string',  default:'Copy Address' },
        cardBg:         { type:'string',  default:'#f8fafc' },
        cardBorderRadius:{ type:'number', default:12 },
        mapBorderRadius: { type:'number', default:12 },
        buttonRadius:    { type:'number', default:8 },
        buttonFontSize:  { type:'number', default:14 },
        directionsBg:    { type:'string', default:'' },
        viewMapBg:       { type:'string', default:'#ffffff' },
        callBg:          { type:'string', default:'#ffffff' },
        callColor:       { type:'string', default:'#16a34a' },
        iconSize:        { type:'number', default:20 },
        wrapperMT:       { type:'number', default:40 },
        wrapperMB:       { type:'number', default:48 }
    },
    edit: function(props) {
        var a = props.attributes, set = props.setAttributes;
        var blockProps = useBlockProps();

        /* ── remote site options ── */
        var _s = useState({}); var opts = _s[0]; var setOpts = _s[1];
        var _sav = useState(false); var saving = _sav[0]; var setSaving = _sav[1];
        var _n = useState(''); var notice = _n[0]; var setNotice = _n[1];
        var _cp = useState(false); var copied = _cp[0]; var setCopied = _cp[1];

        useEffect(function(){ apiFetch({path:'/kv/v1/site-options'}).then(function(d){setOpts(d||{});}); },[]);
        function upd(k,v){setOpts(function(prev){var o=Object.assign({},prev);o[k]=v;return o;});}
        function saveField(k){
            setSaving(true); var data={}; data[k]=opts[k]||'';
            apiFetch({path:'/kv/v1/site-options',method:'POST',data:data})
                .then(function(){setNotice('saved');setTimeout(function(){setNotice('');},2000);})
                .catch(function(){setNotice('error');})
                .finally(function(){setSaving(false);});
        }
        var primary = opts.theme_primary_color||'#0056d6';
        var accent  = opts.theme_accent_color||primary;

        /* ── SVG helpers ── */
        function SvgDirections(){return el('svg',{width:16,height:16,viewBox:'0 0 24 24',fill:'currentColor'},el('path',{d:'M21.71 11.29l-9-9a1 1 0 0 0-1.42 0l-9 9a1 1 0 0 0 0 1.42l9 9a1 1 0 0 0 1.42 0l9-9a1 1 0 0 0 0-1.42zM14 14.5V12h-4v3H8v-4a1 1 0 0 1 1-1h5V7.5l3.5 3.5-3.5 3.5z'}));}
        function SvgPin(){return el('svg',{width:16,height:16,viewBox:'0 0 24 24',fill:'currentColor'},el('path',{d:'M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z'}));}
        function SvgPhone(){return el('svg',{width:16,height:16,viewBox:'0 0 24 24',fill:'currentColor'},el('path',{d:'M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1-9.4 0-17-7.6-17-17 0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1l-2.3 2.2z'}));}
        function SvgCopy(){return el('svg',{width:12,height:12,viewBox:'0 0 24 24',fill:'currentColor'},el('path',{d:'M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12V1zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2zm0 16H8V7h11v14z'}));}

        /* ── Inline editable styles ── */
        var editInput = {border:'none',borderBottom:'1px dashed #cbd5e1',background:'transparent',color:'#64748b',fontSize:'14px',lineHeight:'1.7',width:'100%',padding:'2px 0',outline:'none'};
        var editTextarea = Object.assign({},editInput,{resize:'vertical',fontFamily:'inherit'});
        var sectionTitle = {margin:'0 0 4px',fontWeight:'600',color:'#1e293b',fontSize:'14px'};
        var addrLines = (opts.site_address_full||'').split('\n').filter(Boolean);
        var phoneDisplay = opts.site_phone||'';

        /* ── CTA button style builder ── */
        function ctaStyle(bg,color,border){
            return {display:'flex',alignItems:'center',justifyContent:'center',gap:'8px',
                background:bg,color:color,padding:'12px 20px',borderRadius:a.buttonRadius+'px',
                fontWeight:'600',fontSize:a.buttonFontSize+'px',textDecoration:'none',
                border:border||'none',cursor:'default',transition:'opacity .2s'};
        }

        /* ── INSPECTOR CONTROLS ── */
        var inspector = el(InspectorControls, {},
            el(PanelBody,{title:'\uD83D\uDDFA Map Settings',initialOpen:true},
                saving?el(Spinner):(notice?el('div',{style:{padding:'4px 10px',borderRadius:'4px',fontSize:'12px',background:notice==='saved'?'#dcfce7':'#fee2e2',color:notice==='saved'?'#166534':'#991b1b',marginBottom:'8px'}},notice==='saved'?'\u2713 Saved':'\u2717 Error'):null),
                el(TextControl,{label:'Google Maps Embed URL',value:a.mapUrl,onChange:function(v){set({mapUrl:v});},help:'Leave blank to use site-wide default from Settings.'}),
                el(RangeControl,{label:'Map Height (px)',value:a.height,onChange:function(v){set({height:v});},min:200,max:800,step:10}),
                el(RangeControl,{label:'Map Border Radius',value:a.mapBorderRadius,onChange:function(v){set({mapBorderRadius:v});},min:0,max:30}),
                el(RangeControl,{label:'Wrapper Margin Top',value:a.wrapperMT,onChange:function(v){set({wrapperMT:v});},min:0,max:100}),
                el(RangeControl,{label:'Wrapper Margin Bottom',value:a.wrapperMB,onChange:function(v){set({wrapperMB:v});},min:0,max:100})
            ),
            el(PanelBody,{title:'\uD83C\uDFA8 Card & Button Style',initialOpen:false},
                el('p',{style:{fontSize:'12px',fontWeight:'600',marginBottom:'4px'}},'Card Background'),
                el(ColorPalette,{colors:kvPalette,value:a.cardBg,onChange:function(v){set({cardBg:v||'#f8fafc'});}}),
                el(RangeControl,{label:'Card Border Radius',value:a.cardBorderRadius,onChange:function(v){set({cardBorderRadius:v});},min:0,max:30}),
                el(RangeControl,{label:'Button Border Radius',value:a.buttonRadius,onChange:function(v){set({buttonRadius:v});},min:0,max:20}),
                el(RangeControl,{label:'Button Font Size',value:a.buttonFontSize,onChange:function(v){set({buttonFontSize:v});},min:10,max:20}),
                el('p',{style:{fontSize:'12px',fontWeight:'600',marginBottom:'4px',marginTop:'12px'}},'Directions Button BG'),
                el(ColorPalette,{colors:kvPalette,value:a.directionsBg||primary,onChange:function(v){set({directionsBg:v||''});}}),
                el('p',{style:{fontSize:'12px',fontWeight:'600',marginBottom:'4px',marginTop:'12px'}},'Call Button Color'),
                el(ColorPalette,{colors:kvPalette,value:a.callColor,onChange:function(v){set({callColor:v||'#16a34a'});}})
            ),
            el(PanelBody,{title:'\uD83D\uDC41 Visibility',initialOpen:false},
                el(ToggleControl,{label:'Show Info Card',checked:a.showInfoCard,onChange:function(v){set({showInfoCard:v});}}),
                a.showInfoCard?el(Fragment,{},
                    el(ToggleControl,{label:'Address',checked:a.showAddress,onChange:function(v){set({showAddress:v});}}),
                    el(ToggleControl,{label:'Phone',checked:a.showPhone,onChange:function(v){set({showPhone:v});}}),
                    el(ToggleControl,{label:'Business Hours',checked:a.showHours,onChange:function(v){set({showHours:v});}}),
                    el(ToggleControl,{label:'Copy Address Button',checked:a.showCopy,onChange:function(v){set({showCopy:v});}}),
                    el(ToggleControl,{label:'CTA Buttons',checked:a.showCTA,onChange:function(v){set({showCTA:v});}})
                ):null
            ),
            el(PanelBody,{title:'\u270F\uFE0F Button Labels',initialOpen:false},
                el(TextControl,{label:'Directions',value:a.directionsText,onChange:function(v){set({directionsText:v});}}),
                el(TextControl,{label:'View Map',value:a.viewMapText,onChange:function(v){set({viewMapText:v});}}),
                el(TextControl,{label:'Call Us',value:a.callText,onChange:function(v){set({callText:v});}}),
                el(TextControl,{label:'Copy Button',value:a.copyText,onChange:function(v){set({copyText:v});}})
            ),
            el(PanelBody,{title:'\uD83D\uDCCD Site Contact Info',initialOpen:false},
                el('p',{style:{color:'#64748b',fontSize:'12px',margin:'0 0 12px'}},'Shared across header, footer, contact page.'),
                el(TextareaControl,{label:'Full Address',value:opts.site_address_full||'',rows:3,onChange:function(v){upd('site_address_full',v);},onBlur:function(){saveField('site_address_full');}}),
                el(TextControl,{label:'Short Address (1 line)',value:opts.site_address||'',onChange:function(v){upd('site_address',v);},onBlur:function(){saveField('site_address');}}),
                el(TextControl,{label:'Phone',value:opts.site_phone||'',onChange:function(v){upd('site_phone',v);},onBlur:function(){saveField('site_phone');}}),
                el(TextControl,{label:'Weekday Hours',value:opts.site_hours_weekday||'',onChange:function(v){upd('site_hours_weekday',v);},onBlur:function(){saveField('site_hours_weekday');}}),
                el(TextControl,{label:'Weekend Hours',value:opts.site_hours_weekend||'',onChange:function(v){upd('site_hours_weekend',v);},onBlur:function(){saveField('site_hours_weekend');}}),
                el(TextControl,{label:'Map Embed URL (site default)',value:opts.site_map_embed||'',onChange:function(v){upd('site_map_embed',v);},onBlur:function(){saveField('site_map_embed');}})
            )
        );

        /* ── MAP SECTION ── */
        var mapSrc = a.mapUrl||opts.site_map_embed||'';
        var mapEl = mapSrc
            ? el('iframe',{src:mapSrc,width:'100%',height:a.height,style:{border:'0',display:'block'},loading:'lazy',allowFullScreen:true,referrerPolicy:'no-referrer-when-downgrade'})
            : el('div',{style:{height:a.height+'px',background:'linear-gradient(135deg,#e0f2fe 0%,#f0fdf4 100%)',border:'2px dashed #93c5fd',display:'flex',flexDirection:'column',alignItems:'center',justifyContent:'center',gap:'8px'}},
                el('span',{style:{fontSize:'56px'}},'\uD83D\uDDFA\uFE0F'),
                el('div',{style:{color:'#1e40af',fontSize:'16px',fontWeight:'700'}},'Google Maps'),
                el('div',{style:{color:'#3b82f6',fontSize:'13px',maxWidth:'240px',textAlign:'center'}},'Paste your Google Maps Embed URL in the sidebar \u2192 Map Settings')
            );

        /* ── ADDRESS ROW ── */
        var addressRow = a.showAddress ? el('div',{style:{display:'flex',gap:'10px',alignItems:'flex-start',marginBottom:'14px'}},
            el('span',{style:{fontSize:a.iconSize+'px',lineHeight:'1',flexShrink:'0'}},'\uD83D\uDCCD'),
            el('div',{style:{flex:'1'}},
                el(RichText,{tagName:'div',value:a.addressLabel,onChange:function(v){set({addressLabel:v});},allowedFormats:['core/bold'],style:sectionTitle}),
                el('div',{style:{color:'#64748b',fontSize:'14px',lineHeight:'1.6'}},
                    addrLines.length>0
                        ? addrLines.map(function(line,i){return el(Fragment,{key:i},i>0?el('br'):null,line);})
                        : el('textarea',{rows:3,value:opts.site_address_full||'',onChange:function(e){upd('site_address_full',e.target.value);},onBlur:function(){saveField('site_address_full');},style:editTextarea,placeholder:'Enter full address...'})
                ),
                a.showCopy ? el('button',{
                    onClick:function(){
                        var txt=opts.site_address||opts.site_address_full||'';
                        if(navigator.clipboard){navigator.clipboard.writeText(txt).then(function(){setCopied(true);setTimeout(function(){setCopied(false);},2000);});}
                    },
                    style:{marginTop:'6px',background:'none',border:'1px solid #cbd5e1',borderRadius:'6px',padding:'3px 10px',fontSize:'12px',color:'#475569',cursor:'pointer',display:'inline-flex',alignItems:'center',gap:'4px'}
                },
                    el(SvgCopy),
                    copied?'\u2713 Copied!':a.copyText
                ):null
            )
        ) : null;

        /* ── PHONE ROW ── */
        var phoneRow = (a.showPhone && phoneDisplay) ? el('div',{style:{display:'flex',gap:'10px',alignItems:'center',marginBottom:'14px'}},
            el('span',{style:{fontSize:a.iconSize+'px',lineHeight:'1',flexShrink:'0'}},'\uD83D\uDCDE'),
            el('div',null,
                el(RichText,{tagName:'div',value:a.phoneLabel,onChange:function(v){set({phoneLabel:v});},allowedFormats:['core/bold'],style:sectionTitle}),
                el('a',{href:'#',onClick:function(e){e.preventDefault();},style:{color:primary,fontSize:'15px',fontWeight:'600',textDecoration:'none'}},phoneDisplay)
            )
        ) : null;

        /* ── HOURS ROW ── */
        var hoursRow = a.showHours ? el('div',{style:{display:'flex',gap:'10px',alignItems:'flex-start'}},
            el('span',{style:{fontSize:a.iconSize+'px',lineHeight:'1',flexShrink:'0'}},'\uD83D\uDD50'),
            el('div',null,
                el(RichText,{tagName:'div',value:a.hoursLabel,onChange:function(v){set({hoursLabel:v});},allowedFormats:['core/bold'],style:sectionTitle}),
                el('div',{style:{color:'#64748b',fontSize:'14px',lineHeight:'1.6'}},
                    opts.site_hours_weekday||'Monday \u2013 Friday: 8:00 AM \u2013 5:00 PM',
                    el('br'),
                    opts.site_hours_weekend||'Saturday \u2013 Sunday: Closed'
                )
            )
        ) : null;

        /* ── CTA BUTTONS ── */
        var ctaButtons = a.showCTA ? el('div',{style:{display:'flex',flexDirection:'column',gap:'10px',minWidth:'180px'}},
            el('div',{style:ctaStyle(a.directionsBg||primary,'#fff')},
                el(SvgDirections),
                el(RichText,{tagName:'span',value:a.directionsText,onChange:function(v){set({directionsText:v});},allowedFormats:[],style:{color:'#fff'}})
            ),
            el('div',{style:ctaStyle(a.viewMapBg||'#fff',primary,'2px solid '+primary)},
                el(SvgPin),
                el(RichText,{tagName:'span',value:a.viewMapText,onChange:function(v){set({viewMapText:v});},allowedFormats:[],style:{color:primary}})
            ),
            phoneDisplay ? el('div',{style:ctaStyle(a.callBg||'#fff',a.callColor,'2px solid '+a.callColor)},
                el(SvgPhone),
                el(RichText,{tagName:'span',value:a.callText,onChange:function(v){set({callText:v});},allowedFormats:[],style:{color:a.callColor}})
            ) : null
        ) : null;

        /* ── INFO CARD ── */
        var infoCard = a.showInfoCard ? el('div',{style:{marginTop:'16px',background:a.cardBg,border:'1px solid #e2e8f0',borderRadius:a.cardBorderRadius+'px',padding:'20px 24px',display:'flex',flexWrap:'wrap',gap:'20px',alignItems:'flex-start',justifyContent:'space-between'}},
            el('div',{style:{flex:'1',minWidth:'220px'}},addressRow,phoneRow,hoursRow),
            ctaButtons
        ) : null;

        /* ── FINAL RENDER ── */
        return el(Fragment, {},
            inspector,
            el('div', blockProps,
                el('div',{className:'kv-google-map-wrapper',style:{marginTop:a.wrapperMT+'px',marginBottom:a.wrapperMB+'px'}},
                    el('div',{style:{borderRadius:a.mapBorderRadius+'px',overflow:'hidden',boxShadow:'0 4px 24px rgba(0,0,0,0.12)'}},mapEl),
                    infoCard
                )
            )
        );
    },
    save: function(){ return null; }
});

/* ============================================================ */
/* 3. KV CHAT BUTTONS                                           */
/* ============================================================ */
registerBlockType('kv/chat-buttons', {
    title: 'KV Chat Buttons',
    icon: 'format-chat',
    category: 'widgets',
    supports: { html: false, reusable: true },
    attributes: {
        layout: { type:'string', default:'horizontal' }
    },
    edit: function(props) {
        var a = props.attributes, set = props.setAttributes;
        var blockProps = useBlockProps();
        var _s = useState({}); var opts = _s[0]; var setOpts = _s[1];
        var _sav = useState(false); var saving = _sav[0]; var setSaving = _sav[1];
        var _n = useState(''); var notice = _n[0]; var setNotice = _n[1];
        useEffect(function(){ apiFetch({path:'/kv/v1/site-options'}).then(function(d){setOpts(d);}); },[]);
        function saveOpts(updates){
            var merged=Object.assign({},opts,updates); setOpts(merged); setSaving(true);
            apiFetch({path:'/kv/v1/site-options',method:'POST',data:updates})
                .then(function(){setNotice('\u2713 Saved');setTimeout(function(){setNotice('');},2000);})
                .catch(function(){setNotice('\u2717 Error');})
                .finally(function(){setSaving(false);});
        }
        var lineOn=opts.chat_line_enabled==='1'; var wcOn=opts.chat_wechat_enabled==='1'; var waOn=opts.chat_whatsapp_enabled==='1';
        var btnS=function(bg){return{display:'inline-flex',alignItems:'center',gap:'10px',padding:'12px 24px',background:bg,color:'#fff',textDecoration:'none',borderRadius:'8px',fontWeight:'600',fontSize:'14px'};};
        return el(Fragment,{},
            el(InspectorControls,{},
                el(PanelBody,{title:'\u2699\uFE0F Chat Settings',initialOpen:true},
                    saving?el(Spinner):(notice?el('div',{style:{padding:'4px 8px',borderRadius:'4px',fontSize:'12px',background:notice.indexOf('\u2713')>=0?'#dcfce7':'#fee2e2',marginBottom:'8px'}},notice):null),
                    el(SelectControl,{label:'Layout',value:a.layout,options:[{label:'Horizontal',value:'horizontal'},{label:'Vertical',value:'vertical'}],onChange:function(v){set({layout:v});}}),
                    el('hr'),
                    el(ToggleControl,{label:'LINE',checked:lineOn,onChange:function(v){saveOpts({chat_line_enabled:v?'1':'0'});}}),
                    lineOn?el(TextControl,{label:'LINE ID',value:opts.chat_line_id||'',onChange:function(v){setOpts(Object.assign({},opts,{chat_line_id:v}));},onBlur:function(){saveOpts({chat_line_id:opts.chat_line_id||''});}}):null,
                    el(ToggleControl,{label:'WhatsApp',checked:waOn,onChange:function(v){saveOpts({chat_whatsapp_enabled:v?'1':'0'});}}),
                    waOn?el(TextControl,{label:'WhatsApp Number',value:opts.chat_whatsapp_number||'',onChange:function(v){setOpts(Object.assign({},opts,{chat_whatsapp_number:v}));},onBlur:function(){saveOpts({chat_whatsapp_number:opts.chat_whatsapp_number||''});}}):null,
                    el(ToggleControl,{label:'WeChat',checked:wcOn,onChange:function(v){saveOpts({chat_wechat_enabled:v?'1':'0'});}}),
                    wcOn?el(TextControl,{label:'WeChat ID',value:opts.chat_wechat_id||'',onChange:function(v){setOpts(Object.assign({},opts,{chat_wechat_id:v}));},onBlur:function(){saveOpts({chat_wechat_id:opts.chat_wechat_id||''});}}):null
                )
            ),
            el('div',Object.assign({},blockProps,{style:{display:'flex',flexDirection:a.layout==='vertical'?'column':'row',flexWrap:'wrap',gap:'12px'}}),
                lineOn?el('div',{style:btnS('#06c755')},'LINE Chat'):null,
                waOn?el('div',{style:btnS('#25d366')},'WhatsApp'):null,
                wcOn?el('div',{style:btnS('#07c160')},'WeChat'):null,
                (!lineOn&&!waOn&&!wcOn)?el('div',{style:{padding:'20px',background:'#fef3c7',borderRadius:'8px',color:'#92400e',fontSize:'14px'}},'Enable at least one chat platform in the sidebar \u2192'):null
            )
        );
    },
    save: function(){ return null; }
});

/* ============================================================ */
/* 4. KV QUALITY STANDARDS (dynamic badges)                     */
/* ============================================================ */
registerBlockType('kv/quality-standards', {
    title: 'KV Quality Standards',
    icon: 'awards',
    category: 'widgets',
    supports: { html: false },
    attributes: {
        sectionLabel:   { type:'string',  default:'QUALITY STANDARDS' },
        sectionColor:   { type:'string',  default:'#64748b' },
        sectionFontSize:{ type:'number',  default:12 },
        bgColor:        { type:'string',  default:'#f8fafc' },
        badgeSize:       { type:'number',  default:90 },
        badgeGap:        { type:'number',  default:20 },
        badges:          { type:'string',  default:'[{"label":"ISO","number":"9001","sub":"2015 CERTIFIED","color":"#22c55e","bg":"#f0fdf4","visible":true},{"label":"ISO","number":"14001","sub":"2015 CERTIFIED","color":"#22c55e","bg":"#f0fdf4","visible":true},{"label":"BOI","number":"","sub":"PROMOTED FACTORY","color":"#0056d6","bg":"#eff6ff","visible":true}]' }
    },
    edit: function(props) {
        var a = props.attributes, set = props.setAttributes;
        var blockProps = useBlockProps();
        var _sel = useState(-1); var selIdx = _sel[0]; var setSelIdx = _sel[1];
        var badges = [];
        try { badges = JSON.parse(a.badges); } catch(e) { badges = []; }
        function saveBadges(arr){ set({badges:JSON.stringify(arr)}); }
        function updateBadge(idx,key,val){ var arr=badges.slice(); arr[idx]=Object.assign({},arr[idx]); arr[idx][key]=val; saveBadges(arr); }
        function addBadge(){ var arr=badges.slice(); arr.push({label:'NEW',number:'',sub:'DESCRIPTION',color:'#64748b',bg:'#f8fafc',visible:true}); saveBadges(arr); setSelIdx(arr.length-1); }
        function removeBadge(idx){ var arr=badges.slice(); arr.splice(idx,1); saveBadges(arr); if(selIdx===idx)setSelIdx(-1); }
        function moveBadge(idx,dir){ var arr=badges.slice(); var ni=idx+dir; if(ni<0||ni>=arr.length)return; var t=arr[idx]; arr[idx]=arr[ni]; arr[ni]=t; saveBadges(arr); if(selIdx===idx)setSelIdx(ni); }
        var size = a.badgeSize;
        return el(Fragment,{},
            el(InspectorControls,{},
                el(PanelBody,{title:'\u2699\uFE0F Section Style',initialOpen:true},
                    el(RangeControl,{label:'Badge Size',value:a.badgeSize,onChange:function(v){set({badgeSize:v});},min:50,max:150}),
                    el(RangeControl,{label:'Badge Gap',value:a.badgeGap,onChange:function(v){set({badgeGap:v});},min:5,max:50}),
                    el(RangeControl,{label:'Label Font Size',value:a.sectionFontSize,onChange:function(v){set({sectionFontSize:v});},min:8,max:24}),
                    el('p',{style:{fontSize:'12px',fontWeight:'600',marginBottom:'4px'}},'Section BG'),
                    el(ColorPalette,{colors:kvPalette,value:a.bgColor,onChange:function(v){set({bgColor:v||'#f8fafc'});}}),
                    el('p',{style:{fontSize:'12px',fontWeight:'600',marginBottom:'4px'}},'Label Color'),
                    el(ColorPalette,{colors:kvPalette,value:a.sectionColor,onChange:function(v){set({sectionColor:v||'#64748b'});}})
                ),
                selIdx>=0&&selIdx<badges.length ? el(PanelBody,{title:'\uD83C\uDFA8 Badge #'+(selIdx+1)+' Colors',initialOpen:true},
                    el(TextControl,{label:'Label',value:badges[selIdx].label,onChange:function(v){updateBadge(selIdx,'label',v);}}),
                    el(TextControl,{label:'Number',value:badges[selIdx].number,onChange:function(v){updateBadge(selIdx,'number',v);}}),
                    el(TextControl,{label:'Sub Text',value:badges[selIdx].sub,onChange:function(v){updateBadge(selIdx,'sub',v);}}),
                    el(ToggleControl,{label:'Visible',checked:badges[selIdx].visible!==false,onChange:function(v){updateBadge(selIdx,'visible',v);}}),
                    el('p',{style:{fontSize:'12px',fontWeight:'600',marginBottom:'4px'}},'Badge Color'),
                    el(ColorPalette,{colors:kvPalette,value:badges[selIdx].color,onChange:function(v){updateBadge(selIdx,'color',v||'#22c55e');}}),
                    el('p',{style:{fontSize:'12px',fontWeight:'600',marginBottom:'4px'}},'Badge Background'),
                    el(ColorPalette,{colors:kvPalette,value:badges[selIdx].bg,onChange:function(v){updateBadge(selIdx,'bg',v||'#f0fdf4');}})
                ) : null
            ),
            el('div',Object.assign({},blockProps,{style:{background:a.bgColor,borderRadius:'12px',padding:'25px'}}),
                el(RichText,{tagName:'p',value:a.sectionLabel,onChange:function(v){set({sectionLabel:v});},style:{color:a.sectionColor,fontSize:a.sectionFontSize+'px',fontWeight:'600',letterSpacing:'1px',margin:'0 0 20px'}}),
                el('div',{style:{display:'flex',gap:a.badgeGap+'px',alignItems:'center',flexWrap:'wrap'}},
                    badges.map(function(b,i){
                        if(b.visible===false) return null;
                        var isSel = selIdx===i;
                        return el('div',{key:i,style:{position:'relative',cursor:'pointer'},onClick:function(){setSelIdx(i);}},
                            el('div',{style:{width:size+'px',height:size+'px',minWidth:size+'px',borderRadius:'50%',display:'flex',flexDirection:'column',alignItems:'center',justifyContent:'center',textAlign:'center',border:'2px solid '+b.color,background:b.bg,boxShadow:isSel?'0 0 0 3px rgba(0,86,214,0.4)':'none',transition:'box-shadow 0.2s'}},
                                el('span',{style:{color:b.color,fontSize:Math.max(8,size*0.12)+'px',fontWeight:'700',lineHeight:'1.2'}},b.label),
                                b.number?el('span',{style:{color:b.color,fontSize:Math.max(10,size*0.16)+'px',fontWeight:'700',lineHeight:'1.2'}},b.number):null,
                                el('span',{style:{color:b.color,fontSize:Math.max(6,size*0.08)+'px',lineHeight:'1.3'}},b.sub)
                            ),
                            el('div',{style:{position:'absolute',top:'-8px',right:'-8px',display:'flex',gap:'2px'}},
                                el(Button,{isSmall:true,variant:'secondary',onClick:function(e){e.stopPropagation();moveBadge(i,-1);},style:{minWidth:'20px',padding:'0 4px',fontSize:'10px'}},'\u25C0'),
                                el(Button,{isSmall:true,variant:'secondary',onClick:function(e){e.stopPropagation();moveBadge(i,1);},style:{minWidth:'20px',padding:'0 4px',fontSize:'10px'}},'\u25B6'),
                                el(Button,{isSmall:true,isDestructive:true,onClick:function(e){e.stopPropagation();removeBadge(i);},style:{minWidth:'20px',padding:'0 4px',fontSize:'10px'}},'\u2716')
                            )
                        );
                    }),
                    el(Button,{variant:'secondary',onClick:addBadge,style:{width:size*0.7+'px',height:size*0.7+'px',borderRadius:'50%',fontSize:'24px',display:'flex',alignItems:'center',justifyContent:'center'}},'+')
                )
            )
        );
    },
    save: function(){ return null; }
});

/* ============================================================ */
/* 5. KV PRODUCT CATEGORIES                                     */
/* ============================================================ */
registerBlockType('kv/product-categories', {
    title: 'KV Product Categories',
    icon: 'category',
    category: 'widgets',
    supports: { html: false },
    attributes: {
        columns:      { type:'number',  default:3 },
        showImage:    { type:'boolean', default:true },
        showDesc:     { type:'boolean', default:true },
        showChildren: { type:'boolean', default:true },
        maxChildren:  { type:'number',  default:5 },
        visibleCards: { type:'number',  default:3 },
        buttonText:   { type:'string',  default:'View Products \u2192' }
    },
    edit: function(props) {
        var a = props.attributes, set = props.setAttributes;
        var blockProps = useBlockProps();
        return el(Fragment,{},
            el(InspectorControls,{},
                el(PanelBody,{title:'\u2699\uFE0F Display Settings',initialOpen:true},
                    el(RangeControl,{label:'Columns',value:a.columns,onChange:function(v){set({columns:v});},min:1,max:4}),
                    el(RangeControl,{label:'Visible Cards',value:a.visibleCards,onChange:function(v){set({visibleCards:v});},min:1,max:12}),
                    el(RangeControl,{label:'Max Sub-categories',value:a.maxChildren,onChange:function(v){set({maxChildren:v});},min:1,max:20}),
                    el(ToggleControl,{label:'Show Images',checked:a.showImage,onChange:function(v){set({showImage:v});}}),
                    el(ToggleControl,{label:'Show Descriptions',checked:a.showDesc,onChange:function(v){set({showDesc:v});}}),
                    el(ToggleControl,{label:'Show Sub-categories',checked:a.showChildren,onChange:function(v){set({showChildren:v});}}),
                    el(TextControl,{label:'Button Text',value:a.buttonText,onChange:function(v){set({buttonText:v});}})
                )
            ),
            el('div',blockProps,
                el('div',{style:{padding:'40px',background:'#f8fafc',borderRadius:'12px',textAlign:'center'}},
                    el('div',{style:{fontSize:'48px',marginBottom:'12px'}},'\uD83D\uDCE6'),
                    el('p',{style:{color:'#1e293b',fontSize:'18px',fontWeight:'600',margin:'0 0 8px'}},'Product Categories'),
                    el('p',{style:{color:'#64748b',fontSize:'14px',margin:'0'}},a.columns+' columns \u2022 '+a.visibleCards+' visible')
                )
            )
        );
    },
    save: function(){ return null; }
});

/* ============================================================ */
/* 6. KV APPLICATIONS                                           */
/* ============================================================ */
registerBlockType('kv/applications', {
    title: 'KV Applications',
    icon: 'screenoptions',
    category: 'widgets',
    supports: { html: false, align: ['full','wide'] },
    attributes: {
        title:   { type:'string', default:'Applications' },
        columns: { type:'number', default:4 },
        bgColor: { type:'string', default:'#f8fafc' },
        align:   { type:'string', default:'full' },
        items:   { type:'string', default:'[{"icon":"\uD83D\uDCE1","title":"Telecommunications","desc":"5G infrastructure, network equipment"},{"icon":"\uD83C\uDFED","title":"Industrial","desc":"Automation, motor drives, power supplies"},{"icon":"\uD83D\uDCF1","title":"Consumer Electronics","desc":"Smartphones, IoT devices, wearables"}]' }
    },
    edit: function(props) {
        var a = props.attributes, set = props.setAttributes;
        var blockProps = useBlockProps();
        var items = [];
        try { items = JSON.parse(a.items); } catch(e) { items = []; }
        function saveItems(arr){ set({items:JSON.stringify(arr)}); }
        function updateItem(idx,key,val){ var arr=items.slice(); arr[idx]=Object.assign({},arr[idx]); arr[idx][key]=val; saveItems(arr); }
        function addItem(){ var arr=items.slice(); arr.push({icon:'\u2B50',title:'New App',desc:'Description'}); saveItems(arr); }
        function removeItem(idx){ var arr=items.slice(); arr.splice(idx,1); saveItems(arr); }
        return el(Fragment,{},
            el(InspectorControls,{},
                el(PanelBody,{title:'\u2699\uFE0F Settings',initialOpen:true},
                    el(RangeControl,{label:'Columns',value:a.columns,onChange:function(v){set({columns:v});},min:2,max:6}),
                    el('p',{style:{fontSize:'12px',fontWeight:'600',marginBottom:'4px'}},'Background Color'),
                    el(ColorPalette,{colors:kvPalette,value:a.bgColor,onChange:function(v){set({bgColor:v||'#f8fafc'});}})
                )
            ),
            el('div',Object.assign({},blockProps,{style:{background:a.bgColor,padding:'40px 20px',borderRadius:'12px'}}),
                el(RichText,{tagName:'h2',value:a.title,onChange:function(v){set({title:v});},style:{textAlign:'center',margin:'0 0 30px',color:'#1e293b',fontSize:'28px'}}),
                el('div',{style:{display:'grid',gridTemplateColumns:'repeat('+a.columns+',1fr)',gap:'20px'}},
                    items.map(function(item,i){
                        return el('div',{key:i,style:{background:'#fff',borderRadius:'12px',padding:'24px',textAlign:'center',border:'1px solid #e2e8f0',position:'relative'}},
                            el(Button,{isSmall:true,isDestructive:true,onClick:function(){removeItem(i);},style:{position:'absolute',top:'4px',right:'4px',minWidth:'20px',padding:'0 6px'}},'\u2716'),
                            el(RichText,{tagName:'div',value:item.icon,onChange:function(v){updateItem(i,'icon',v);},style:{fontSize:'36px',marginBottom:'12px'}}),
                            el(RichText,{tagName:'h4',value:item.title,onChange:function(v){updateItem(i,'title',v);},style:{margin:'0 0 8px',fontSize:'16px',color:'#1e293b'}}),
                            el(RichText,{tagName:'p',value:item.desc,onChange:function(v){updateItem(i,'desc',v);},style:{margin:'0',fontSize:'14px',color:'#64748b'}})
                        );
                    })
                ),
                el('div',{style:{textAlign:'center',marginTop:'16px'}},
                    el(Button,{variant:'secondary',onClick:addItem},'+ Add Application')
                )
            )
        );
    },
    save: function(){ return null; }
});

/* ============================================================ */
/* 7. KV HOME HERO                                              */
/* ============================================================ */
registerBlockType('kv/home-hero', {
    title: 'KV Home Hero',
    icon: 'cover-image',
    category: 'widgets',
    supports: { html: false },
    attributes: {
        title:         { type:'string', default:'KV Electronics | Home' },
        subtitle:      { type:'string', default:'' },
        primaryText:   { type:'string', default:'View Products' },
        primaryUrl:    { type:'string', default:'/products/' },
        secondaryText: { type:'string', default:'Contact Us' },
        secondaryUrl:  { type:'string', default:'/contact/' }
    },
    edit: function(props) {
        var a = props.attributes, set = props.setAttributes;
        var blockProps = useBlockProps();
        return el(Fragment,{},
            el(InspectorControls,{},
                el(PanelBody,{title:'\uD83D\uDD17 Button Links',initialOpen:true},
                    el(TextControl,{label:'Primary Button URL',value:a.primaryUrl,onChange:function(v){set({primaryUrl:v});}}),
                    el(TextControl,{label:'Secondary Button URL',value:a.secondaryUrl,onChange:function(v){set({secondaryUrl:v});}})
                )
            ),
            el('div',Object.assign({},blockProps,{style:{background:'linear-gradient(135deg,#1e293b 0%,#0f172a 100%)',padding:'60px 40px',borderRadius:'12px',textAlign:'center'}}),
                el(RichText,{tagName:'h1',value:a.title,onChange:function(v){set({title:v});},style:{color:'#fff',fontSize:'36px',margin:'0 0 16px'}}),
                el(RichText,{tagName:'p',value:a.subtitle,onChange:function(v){set({subtitle:v});},style:{color:'#94a3b8',fontSize:'18px',margin:'0 0 30px',maxWidth:'700px',marginLeft:'auto',marginRight:'auto'}}),
                el('div',{style:{display:'flex',gap:'16px',justifyContent:'center',flexWrap:'wrap'}},
                    el(RichText,{tagName:'span',value:a.primaryText,onChange:function(v){set({primaryText:v});},style:{background:'#4ecdc4',color:'#fff',padding:'14px 28px',borderRadius:'8px',fontWeight:'600',fontSize:'16px'}}),
                    el(RichText,{tagName:'span',value:a.secondaryText,onChange:function(v){set({secondaryText:v});},style:{background:'transparent',color:'#fff',padding:'14px 28px',borderRadius:'8px',fontWeight:'600',fontSize:'16px',border:'2px solid rgba(255,255,255,0.3)'}})
                )
            )
        );
    },
    save: function(){ return null; }
});

/* ============================================================ */
/* 8. KV WHY CHOOSE US                                          */
/* ============================================================ */
registerBlockType('kv/why-choose', {
    title: 'KV Why Choose Us',
    icon: 'star-filled',
    category: 'widgets',
    supports: { html: false },
    attributes: {
        title:      { type:'string', default:'Why Choose Us' },
        item1Icon:  { type:'string', default:'\uD83C\uDFED' },
        item1Title: { type:'string', default:'Quality Manufacturing' },
        item1Desc:  { type:'string', default:'State-of-the-art facilities with strict quality control' },
        item2Icon:  { type:'string', default:'\uD83D\uDD2C' },
        item2Title: { type:'string', default:'R&D Excellence' },
        item2Desc:  { type:'string', default:'Continuous innovation and product development' },
        item3Icon:  { type:'string', default:'\uD83C\uDF0D' },
        item3Title: { type:'string', default:'Global Reach' },
        item3Desc:  { type:'string', default:'Serving customers worldwide with reliable delivery' },
        item4Icon:  { type:'string', default:'\uD83E\uDD1D' },
        item4Title: { type:'string', default:'Customer Support' },
        item4Desc:  { type:'string', default:'Dedicated technical support and consultation' }
    },
    edit: function(props) {
        var a = props.attributes, set = props.setAttributes;
        var blockProps = useBlockProps();
        var items = [
            {icon:a.item1Icon,title:a.item1Title,desc:a.item1Desc,pre:'item1'},
            {icon:a.item2Icon,title:a.item2Title,desc:a.item2Desc,pre:'item2'},
            {icon:a.item3Icon,title:a.item3Title,desc:a.item3Desc,pre:'item3'},
            {icon:a.item4Icon,title:a.item4Title,desc:a.item4Desc,pre:'item4'}
        ];
        return el(Fragment,{},
            el(InspectorControls,{},
                el(PanelBody,{title:'\u2699\uFE0F Items',initialOpen:true},
                    items.map(function(it,i){
                        var n = i+1;
                        return el(Fragment,{key:i},
                            el('p',{style:{fontWeight:'700',fontSize:'13px',marginTop:i>0?'12px':'0'}},'Item '+n),
                            el(TextControl,{label:'Icon',value:it.icon,onChange:function(v){var o={};o['item'+n+'Icon']=v;set(o);}}),
                            el(TextControl,{label:'Title',value:it.title,onChange:function(v){var o={};o['item'+n+'Title']=v;set(o);}}),
                            el(TextareaControl,{label:'Description',value:it.desc,onChange:function(v){var o={};o['item'+n+'Desc']=v;set(o);}})
                        );
                    })
                )
            ),
            el('div',Object.assign({},blockProps,{style:{padding:'40px 20px'}}),
                el(RichText,{tagName:'h2',value:a.title,onChange:function(v){set({title:v});},style:{textAlign:'center',margin:'0 0 30px',color:'#1e293b',fontSize:'28px'}}),
                el('div',{style:{display:'grid',gridTemplateColumns:'repeat(4,1fr)',gap:'24px'}},
                    items.map(function(it,i){
                        var n=i+1;
                        return el('div',{key:i,style:{textAlign:'center',padding:'24px',background:'#fff',borderRadius:'12px',border:'1px solid #e2e8f0'}},
                            el(RichText,{tagName:'div',value:it.icon,onChange:function(v){var o={};o['item'+n+'Icon']=v;set(o);},style:{fontSize:'36px',marginBottom:'12px'}}),
                            el(RichText,{tagName:'h4',value:it.title,onChange:function(v){var o={};o['item'+n+'Title']=v;set(o);},style:{margin:'0 0 8px',color:'#1e293b'}}),
                            el(RichText,{tagName:'p',value:it.desc,onChange:function(v){var o={};o['item'+n+'Desc']=v;set(o);},style:{margin:'0',color:'#64748b',fontSize:'14px'}})
                        );
                    })
                )
            )
        );
    },
    save: function(){ return null; }
});

/* ============================================================ */
/* 9. KV READY TO GET STARTED                                   */
/* ============================================================ */
registerBlockType('kv/ready-started', {
    title: 'KV Ready to Get Started',
    icon: 'megaphone',
    category: 'widgets',
    supports: { html: false },
    attributes: {
        title:      { type:'string', default:'Ready to Get Started?' },
        subtitle:   { type:'string', default:'Contact us today for custom solutions and quotations' },
        buttonText: { type:'string', default:'Get in Touch' },
        buttonUrl:  { type:'string', default:'/contact/' }
    },
    edit: function(props) {
        var a = props.attributes, set = props.setAttributes;
        var blockProps = useBlockProps();
        return el(Fragment,{},
            el(InspectorControls,{},
                el(PanelBody,{title:'\uD83D\uDD17 Settings',initialOpen:true},
                    el(TextControl,{label:'Button URL',value:a.buttonUrl,onChange:function(v){set({buttonUrl:v});}})
                )
            ),
            el('div',Object.assign({},blockProps,{style:{background:'linear-gradient(135deg,#0056d6 0%,#4ecdc4 100%)',padding:'60px 40px',borderRadius:'12px',textAlign:'center'}}),
                el(RichText,{tagName:'h2',value:a.title,onChange:function(v){set({title:v});},style:{color:'#fff',fontSize:'28px',margin:'0 0 12px'}}),
                el(RichText,{tagName:'p',value:a.subtitle,onChange:function(v){set({subtitle:v});},style:{color:'rgba(255,255,255,0.9)',fontSize:'16px',margin:'0 0 24px'}}),
                el(RichText,{tagName:'span',value:a.buttonText,onChange:function(v){set({buttonText:v});},style:{display:'inline-block',background:'#fff',color:'#0056d6',padding:'14px 32px',borderRadius:'8px',fontWeight:'600',fontSize:'16px'}})
            )
        );
    },
    save: function(){ return null; }
});

/* ============================================================ */
/* 10. KV CONTACT INFO                                          */
/* ============================================================ */
registerBlockType('kv/contact-info', {
    title: 'KV Contact Info',
    icon: 'id',
    category: 'widgets',
    supports: { html: false },
    attributes: {
        showAddress:  { type:'boolean', default:true },
        showPhone:    { type:'boolean', default:true },
        showEmail:    { type:'boolean', default:true },
        showHours:    { type:'boolean', default:true },
        showChat:     { type:'boolean', default:true },
        addressText:  { type:'string', default:'' },
        phoneText:    { type:'string', default:'' },
        emailText:    { type:'string', default:'' },
        hoursWeekday: { type:'string', default:'' },
        hoursWeekend: { type:'string', default:'' },
        addressIcon:  { type:'string', default:'\uD83D\uDCCD' },
        addressTitle: { type:'string', default:'Address' },
        phoneIcon:    { type:'string', default:'\uD83D\uDCDE' },
        phoneTitle:   { type:'string', default:'Phone' },
        emailIcon:    { type:'string', default:'\u2709\uFE0F' },
        emailTitle:   { type:'string', default:'Email' },
        hoursIcon:    { type:'string', default:'\uD83D\uDD50' },
        hoursTitle:   { type:'string', default:'Business Hours' },
        chatIcon:     { type:'string', default:'\uD83D\uDCAC' },
        chatTitle:    { type:'string', default:'Chat with Us' },
        chatLineLabel:     { type:'string', default:'LINE' },
        chatWhatsappLabel: { type:'string', default:'WhatsApp' },
        chatWechatLabel:   { type:'string', default:'WeChat' },
        iconBg:       { type:'string', default:'#e8f0fe' },
        titleColor:   { type:'string', default:'#1e293b' },
        textColor:    { type:'string', default:'#64748b' }
    },
    edit: function(props) {
        var a = props.attributes, set = props.setAttributes;
        var blockProps = useBlockProps();
        var _s = useState({}); var opts = _s[0]; var setOpts = _s[1];
        useEffect(function(){ apiFetch({path:'/kv/v1/site-options'}).then(function(d){setOpts(d||{});}); },[]);

        var addressValue = a.addressText || opts.site_address_full || '';
        var phoneValue = a.phoneText || opts.site_phone || '';
        var emailValue = a.emailText || opts.site_email || '';
        var weekdayValue = a.hoursWeekday || opts.site_hours_weekday || '';
        var weekendValue = a.hoursWeekend || opts.site_hours_weekend || '';

        var primary = opts.theme_primary_color||'#0056d6';
        var cardS = function(show){ return {display:show?'flex':'none',gap:'16px',padding:'24px',background:'#fff',borderRadius:'12px',boxShadow:'0 1px 3px rgba(0,0,0,0.06)',border:'1px solid #f1f5f9',marginBottom:'16px'}; };
        var iconS = {width:'48px',height:'48px',borderRadius:'12px',display:'flex',alignItems:'center',justifyContent:'center',fontSize:'22px',flexShrink:'0',background:a.iconBg};
        var editInput = {border:'none',borderBottom:'1px dashed #cbd5e1',background:'transparent',fontSize:'14px',lineHeight:'1.7',width:'100%',padding:'2px 0',outline:'none',color:a.textColor};
        return el(Fragment,{},
            el(InspectorControls,{},
                el(PanelBody,{title:'\uD83D\uDC41 Visibility',initialOpen:true},
                    el(ToggleControl,{label:'Address',checked:a.showAddress,onChange:function(v){set({showAddress:v});}}),
                    el(ToggleControl,{label:'Phone',checked:a.showPhone,onChange:function(v){set({showPhone:v});}}),
                    el(ToggleControl,{label:'Email',checked:a.showEmail,onChange:function(v){set({showEmail:v});}}),
                    el(ToggleControl,{label:'Hours',checked:a.showHours,onChange:function(v){set({showHours:v});}}),
                    el(ToggleControl,{label:'Chat',checked:a.showChat,onChange:function(v){set({showChat:v});}})
                ),
                el(PanelBody,{title:'\uD83C\uDFA8 Colors',initialOpen:false},
                    el('p',{style:{fontSize:'12px',fontWeight:'600',marginBottom:'4px'}},'Icon Circle Background'),
                    el(ColorPalette,{colors:kvPalette,value:a.iconBg,onChange:function(v){set({iconBg:v||'#e8f0fe'});}}),
                    el('p',{style:{fontSize:'12px',fontWeight:'600',marginBottom:'4px'}},'Title Color'),
                    el(ColorPalette,{colors:kvPalette,value:a.titleColor,onChange:function(v){set({titleColor:v||'#1e293b'});}}),
                    el('p',{style:{fontSize:'12px',fontWeight:'600',marginBottom:'4px'}},'Text Color'),
                    el(ColorPalette,{colors:kvPalette,value:a.textColor,onChange:function(v){set({textColor:v||'#64748b'});}})
                ),
                el(PanelBody,{title:'\uD83D\uDCCD Block Content (per page)',initialOpen:false},
                    el('p',{style:{color:'#64748b',fontSize:'12px',margin:'0 0 12px'}},'Data below is saved in this page block only.'),
                    el(TextareaControl,{label:'Full Address',value:addressValue,rows:3,onChange:function(v){set({addressText:v});}}),
                    el(TextControl,{label:'Phone',value:phoneValue,onChange:function(v){set({phoneText:v});}}),
                    el(TextControl,{label:'Email',value:emailValue,onChange:function(v){set({emailText:v});}}),
                    el(TextControl,{label:'Weekday Hours',value:weekdayValue,onChange:function(v){set({hoursWeekday:v});}}),
                    el(TextControl,{label:'Weekend Hours',value:weekendValue,onChange:function(v){set({hoursWeekend:v});}})
                )
            ),
            el('div',Object.assign({},blockProps,{style:{display:'grid',gridTemplateColumns:'1fr 1fr',gap:'16px'}}),
                /* Address */
                el('div',{style:cardS(a.showAddress)},
                    el('div',{style:iconS},
                        el(RichText,{tagName:'span',value:a.addressIcon,onChange:function(v){set({addressIcon:v});},allowedFormats:[]})
                    ),
                    el('div',{style:{flex:'1'}},
                        el(RichText,{tagName:'div',value:a.addressTitle,onChange:function(v){set({addressTitle:v});},style:{fontWeight:'600',color:a.titleColor,marginBottom:'4px'}}),
                        el('textarea',{value:addressValue,onChange:function(e){set({addressText:e.target.value});},rows:3,style:Object.assign({},editInput,{resize:'vertical',fontFamily:'inherit'}),placeholder:'Enter address...'})
                    )
                ),
                /* Phone */
                el('div',{style:cardS(a.showPhone)},
                    el('div',{style:iconS},
                        el(RichText,{tagName:'span',value:a.phoneIcon,onChange:function(v){set({phoneIcon:v});},allowedFormats:[]})
                    ),
                    el('div',{style:{flex:'1'}},
                        el(RichText,{tagName:'div',value:a.phoneTitle,onChange:function(v){set({phoneTitle:v});},style:{fontWeight:'600',color:a.titleColor,marginBottom:'4px'}}),
                        el('input',{type:'text',value:phoneValue,onChange:function(e){set({phoneText:e.target.value});},style:Object.assign({},editInput,{color:primary,fontWeight:'600',fontSize:'15px'}),placeholder:'Phone...'})
                    )
                ),
                /* Email */
                el('div',{style:cardS(a.showEmail)},
                    el('div',{style:iconS},
                        el(RichText,{tagName:'span',value:a.emailIcon,onChange:function(v){set({emailIcon:v});},allowedFormats:[]})
                    ),
                    el('div',{style:{flex:'1'}},
                        el(RichText,{tagName:'div',value:a.emailTitle,onChange:function(v){set({emailTitle:v});},style:{fontWeight:'600',color:a.titleColor,marginBottom:'4px'}}),
                        el('input',{type:'text',value:emailValue,onChange:function(e){set({emailText:e.target.value});},style:Object.assign({},editInput,{color:primary}),placeholder:'Email...'})
                    )
                ),
                /* Hours */
                el('div',{style:cardS(a.showHours)},
                    el('div',{style:iconS},
                        el(RichText,{tagName:'span',value:a.hoursIcon,onChange:function(v){set({hoursIcon:v});},allowedFormats:[]})
                    ),
                    el('div',{style:{flex:'1'}},
                        el(RichText,{tagName:'div',value:a.hoursTitle,onChange:function(v){set({hoursTitle:v});},style:{fontWeight:'600',color:a.titleColor,marginBottom:'4px'}}),
                        el('input',{type:'text',value:weekdayValue,onChange:function(e){set({hoursWeekday:e.target.value});},style:editInput,placeholder:'Weekday hours...'}),
                        el('input',{type:'text',value:weekendValue,onChange:function(e){set({hoursWeekend:e.target.value});},style:Object.assign({},editInput,{marginTop:'4px'}),placeholder:'Weekend hours...'})
                    )
                ),
                /* Chat */
                el('div',{style:Object.assign({},cardS(a.showChat),{gridColumn:'1 / -1'})},
                    el('div',{style:iconS},
                        el(RichText,{tagName:'span',value:a.chatIcon,onChange:function(v){set({chatIcon:v});},allowedFormats:[]})
                    ),
                    el('div',{style:{flex:'1'}},
                        el(RichText,{tagName:'div',value:a.chatTitle,onChange:function(v){set({chatTitle:v});},style:{fontWeight:'600',color:a.titleColor,marginBottom:'4px'}}),
                        el('div',{style:{display:'flex',gap:'8px',flexWrap:'wrap',marginTop:'8px'}},
                            el('input',{type:'text',value:a.chatLineLabel||'LINE',onChange:function(e){set({chatLineLabel:e.target.value});},style:{background:'#06c755',color:'#fff',padding:'6px 16px',borderRadius:'6px',fontSize:'13px',fontWeight:'600',display:'inline-block',border:'none',outline:'none',minWidth:'90px'}}),
                            el('input',{type:'text',value:a.chatWhatsappLabel||'WhatsApp',onChange:function(e){set({chatWhatsappLabel:e.target.value});},style:{background:'#25d366',color:'#fff',padding:'6px 16px',borderRadius:'6px',fontSize:'13px',fontWeight:'600',display:'inline-block',border:'none',outline:'none',minWidth:'110px'}}),
                            el('input',{type:'text',value:a.chatWechatLabel||'WeChat',onChange:function(e){set({chatWechatLabel:e.target.value});},style:{background:'#07c160',color:'#fff',padding:'6px 16px',borderRadius:'6px',fontSize:'13px',fontWeight:'600',display:'inline-block',border:'none',outline:'none',minWidth:'95px'}})
                        )
                    )
                )
            )
        );
    },
    save: function(){ return null; }
});

})();
