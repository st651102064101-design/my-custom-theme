/**
 * KV About Page Blocks — About Intro (S1) & About S2 (S2)
 * Fully inline-editable in the Gutenberg canvas.
 * Content stored in wp_options via REST /kv/v1/about-options.
 */
(function(wp) {
    const { registerBlockType }                          = wp.blocks;
    const { useBlockProps, RichText,
            MediaUploadCheck, MediaUpload }              = wp.blockEditor;
    const { Button, Notice, Spinner }                   = wp.components;
    const { createElement: el, Fragment,
            useState, useEffect }                       = wp.element;
    const apiFetch                                       = wp.apiFetch;

    // ── styles ────────────────────────────────────────────────────────────────
    const S = {
        row:    { display:'flex', gap:'36px', alignItems:'flex-start' },
        imgCol: { flex:'0 0 320px', maxWidth:'320px' },
        txtCol: { flex:1, minWidth:0 },
        imgBox: {
            position:'relative', width:'100%', paddingBottom:'75%',
            background:'#f1f5f9', borderRadius:'10px', overflow:'hidden',
            border:'2px dashed #cbd5e1', cursor:'pointer'
        },
        imgEl:  {
            position:'absolute', inset:0, width:'100%', height:'100%',
            objectFit:'cover', display:'block'
        },
        overlay:{
            position:'absolute', inset:0,
            background:'rgba(0,0,0,.45)',
            display:'flex', alignItems:'center', justifyContent:'center',
            transition:'opacity .2s'
        },
        emptyImg: {
            position:'absolute', inset:0,
            display:'flex', flexDirection:'column',
            alignItems:'center', justifyContent:'center',
            color:'#94a3b8', fontSize:'13px', gap:'8px'
        },
        badge:  {
            display:'inline-block', background:'#0073aa', color:'#fff',
            borderRadius:'4px', padding:'3px 10px',
            fontSize:'11px', fontWeight:700, marginBottom:'12px'
        },
        saveBar:{
            marginTop:'16px', padding:'10px 14px',
            background:'#1e293b', borderRadius:'8px',
            display:'flex', alignItems:'center', gap:'12px'
        },
        dirty:  { color:'#e2e8f0', fontSize:'12px', flex:1 },
        rtH4:   {
            fontSize:'18px', fontWeight:700, color:'#1e293b',
            margin:'0 0 6px', padding:'2px 6px', borderRadius:'3px',
            minHeight:'28px'
        },
        rtP:    {
            fontSize:'15px', color:'#475569', lineHeight:1.7,
            margin:'0 0 14px', padding:'2px 6px', borderRadius:'3px',
            minHeight:'22px'
        }
    };

    // ── REST state hook ───────────────────────────────────────────────────────
    const DEFAULTS = {
        about_s1_title1:'', about_s1_text1:'', about_s1_title2:'',
        about_s1_text2:'', about_s1_text3:'', about_s1_image:'',
        about_s2_title1:'', about_s2_text1:'', about_s2_title2:'',
        about_s2_text2:'', about_s2_text3:'', about_s2_image:''
    };

    function useAbout() {
        const [saved,   setSaved]   = useState(DEFAULTS);
        const [fields,  setFields]  = useState(DEFAULTS);
        const [loading, setLoading] = useState(true);
        const [saving,  setSaving]  = useState(false);
        const [notice,  setNotice]  = useState(null);

        useEffect(function() {
            apiFetch({ path:'/kv/v1/about-options' })
                .then(function(d){ setFields(d); setSaved(d); setLoading(false); })
                .catch(function() { setLoading(false); });
        }, []);

        function set(k,v){ setFields(function(p){ return Object.assign({},p,{[k]:v}); }); }

        const dirty = JSON.stringify(fields) !== JSON.stringify(saved);

        function save(){
            setSaving(true); setNotice(null);
            apiFetch({ path:'/kv/v1/about-options', method:'POST', data:fields })
                .then(function(){
                    setSaving(false);
                    setSaved(Object.assign({},fields));
                    setNotice({ status:'success', msg:'\u0e1a\u0e31\u0e19\u0e17\u0e36\u0e01\u0e41\u0e25\u0e49\u0e27 \u2713' });
                    setTimeout(function(){ setNotice(null); }, 2500);
                })
                .catch(function(e){
                    setSaving(false);
                    setNotice({ status:'error', msg:'Error: '+(e.message||'?') });
                });
        }
        return { fields, set, loading, saving, save, dirty, notice };
    }

    // ── image picker ──────────────────────────────────────────────────────────
    function ImgPicker({ src, onSelect }) {
        const [hover, setHover] = useState(false);
        return el(MediaUploadCheck, {},
            el(MediaUpload, {
                onSelect: function(m){ onSelect(m.url); },
                allowedTypes:['image'],
                render: function(ref){
                    return el('div', {
                            style: S.imgBox,
                            onMouseEnter:function(){ setHover(true);  },
                            onMouseLeave:function(){ setHover(false); },
                            onClick: ref.open
                        },
                        src
                            ? el('img',{ src:src, alt:'', style:S.imgEl })
                            : el('div', { style:S.emptyImg },
                                el('span',{ style:{ fontSize:'32px' } }, '\ud83d\uddbc\ufe0f'),
                                '\u0e04\u0e25\u0e34\u0e01\u0e40\u0e1e\u0e37\u0e48\u0e2d\u0e40\u0e25\u0e37\u0e2d\u0e01\u0e23\u0e39\u0e1b\u0e20\u0e32\u0e1e'
                              ),
                        el('div', {
                                style: Object.assign({}, S.overlay, { opacity: hover ? 1 : 0 })
                            },
                            el(Button,{ variant:'primary', style:{ pointerEvents:'none' } },
                                src ? '\ud83d\uddbc\ufe0f \u0e40\u0e1b\u0e25\u0e35\u0e48\u0e22\u0e19\u0e23\u0e39\u0e1b' : '\ud83d\udcc1 \u0e40\u0e25\u0e37\u0e2d\u0e01\u0e23\u0e39\u0e1b'
                            )
                        )
                    );
                }
            })
        );
    }

    // ── inline text fields ────────────────────────────────────────────────────
    function TextFields({ fields, set, pfx }) {
        const fmts = ['core/bold','core/italic'];
        return el('div', { style:S.txtCol },
            el(RichText, {
                tagName:'h4', value:fields[pfx+'title1'],
                onChange:function(v){ set(pfx+'title1',v); },
                placeholder:'\u0e2b\u0e31\u0e27\u0e02\u0e49\u0e2d\u0e22\u0e48\u0e2d\u0e22 1 (\u0e44\u0e21\u0e48\u0e1a\u0e31\u0e07\u0e04\u0e31\u0e1a)\u2026',
                style:S.rtH4, allowedFormats:fmts
            }),
            el(RichText, {
                tagName:'p', value:fields[pfx+'text1'],
                onChange:function(v){ set(pfx+'text1',v); },
                placeholder:'\u0e22\u0e48\u0e2d\u0e2b\u0e19\u0e49\u0e32\u0e17\u0e35\u0e48 1\u2026',
                style:S.rtP, allowedFormats:[...fmts,'core/link']
            }),
            el(RichText, {
                tagName:'h4', value:fields[pfx+'title2'],
                onChange:function(v){ set(pfx+'title2',v); },
                placeholder:'\u0e2b\u0e31\u0e27\u0e02\u0e49\u0e2d\u0e22\u0e48\u0e2d\u0e22 2 (\u0e44\u0e21\u0e48\u0e1a\u0e31\u0e07\u0e04\u0e31\u0e1a)\u2026',
                style:S.rtH4, allowedFormats:fmts
            }),
            el(RichText, {
                tagName:'p', value:fields[pfx+'text2'],
                onChange:function(v){ set(pfx+'text2',v); },
                placeholder:'\u0e22\u0e48\u0e2d\u0e2b\u0e19\u0e49\u0e32\u0e17\u0e35\u0e48 2\u2026',
                style:S.rtP, allowedFormats:[...fmts,'core/link']
            }),
            el(RichText, {
                tagName:'p', value:fields[pfx+'text3'],
                onChange:function(v){ set(pfx+'text3',v); },
                placeholder:'\u0e22\u0e48\u0e2d\u0e2b\u0e19\u0e49\u0e32\u0e17\u0e35\u0e48 3 (\u0e44\u0e21\u0e48\u0e1a\u0e31\u0e07\u0e04\u0e31\u0e1a)\u2026',
                style: Object.assign({}, S.rtP, { marginBottom:0 }),
                allowedFormats:[...fmts,'core/link']
            })
        );
    }

    // ── save bar ──────────────────────────────────────────────────────────────
    function SaveBar({ dirty, saving, save, notice }) {
        if (!dirty && !notice && !saving) return null;
        return el('div', { style:S.saveBar },
            notice
                ? el(Notice, { status:notice.status, isDismissible:false, style:{ margin:0, flex:1 } }, notice.msg)
                : el('span', { style:S.dirty }, '\u25cf \u0e21\u0e35\u0e01\u0e32\u0e23\u0e40\u0e1b\u0e25\u0e35\u0e48\u0e22\u0e19\u0e41\u0e1b\u0e25\u0e07\u0e17\u0e35\u0e48\u0e22\u0e31\u0e07\u0e44\u0e21\u0e48\u0e1a\u0e31\u0e19\u0e17\u0e36\u0e01'),
            el(Button,{
                variant:'primary', onClick:save,
                disabled: saving || !dirty, style:{ flexShrink:0 }
            },
                saving
                    ? el(Fragment,{}, el(Spinner,{ style:{ width:'14px',height:'14px' } }), ' \u0e01\u0e33\u0e25\u0e31\u0e07\u0e1a\u0e31\u0e19\u0e17\u0e36\u0e01\u2026')
                    : '\ud83d\udcbe \u0e1a\u0e31\u0e19\u0e17\u0e36\u0e01'
            )
        );
    }

    // ── makeEdit factory ──────────────────────────────────────────────────────
    function makeEdit(sec) {
        return function() {
            const H = useAbout();
            const bProps = useBlockProps({ style:{ padding:0 } });
            const pfx    = 'about_' + sec + '_';
            const imgKey = 'about_' + sec + '_image';

            if (H.loading) {
                return el('div', bProps,
                    el('div',{ style:{ padding:'40px', textAlign:'center', color:'#94a3b8' } },
                        el(Spinner), ' \u0e01\u0e33\u0e25\u0e31\u0e07\u0e42\u0e2b\u0e25\u0e14\u2026'));
            }

            const label = sec === 's1'
                ? 'KV About \u2014 Section 1 \u203a \u0e40\u0e19\u0e37\u0e49\u0e2d\u0e2b\u0e32\u0e0b\u0e49\u0e32\u0e22 | \u0e23\u0e39\u0e1b\u0e02\u0e27\u0e32'
                : 'KV About \u2014 Section 2 \u203a \u0e23\u0e39\u0e1b\u0e0b\u0e49\u0e32\u0e22 | \u0e40\u0e19\u0e37\u0e49\u0e2d\u0e2b\u0e32\u0e02\u0e27\u0e32';

            const txt = el(TextFields, { fields:H.fields, set:H.set, pfx });
            const img = el('div', { style:S.imgCol },
                el(ImgPicker, {
                    src: H.fields[imgKey],
                    onSelect: function(url){ H.set(imgKey, url); }
                })
            );

            const cols = sec === 's1'
                ? el('div', { style:S.row }, txt, img)
                : el('div', { style:S.row }, img, txt);

            return el('div', bProps,
                el('span', { style:S.badge }, label),
                cols,
                el(SaveBar, { dirty:H.dirty, saving:H.saving, save:H.save, notice:H.notice })
            );
        };
    }

    // ── register blocks ───────────────────────────────────────────────────────
    registerBlockType('kv/about-intro', {
        title: 'KV About \u2014 Section 1',
        icon:  'format-image',
        category: 'widgets',
        description: 'About Section 1 \u2014 \u0e40\u0e19\u0e37\u0e49\u0e2d\u0e2b\u0e32\u0e0b\u0e49\u0e32\u0e22 \u0e23\u0e39\u0e1b\u0e02\u0e27\u0e32',
        supports: { html:false, reusable:false },
        attributes: {},
        edit: makeEdit('s1'),
        save: function() { return null; }
    });

    registerBlockType('kv/about-s2', {
        title: 'KV About \u2014 Section 2',
        icon:  'format-image',
        category: 'widgets',
        description: 'About Section 2 \u2014 \u0e23\u0e39\u0e1b\u0e0b\u0e49\u0e32\u0e22 \u0e40\u0e19\u0e37\u0e49\u0e2d\u0e2b\u0e32\u0e02\u0e27\u0e32',
        supports: { html:false, reusable:false },
        attributes: {},
        edit: makeEdit('s2'),
        save: function() { return null; }
    });

})(window.wp);
