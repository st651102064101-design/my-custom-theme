/**
 * KV Global Blocks — Navbar & Footer (site-wide settings)
 * Editing these blocks on ANY page updates ALL pages.
 */
(function(wp) {
    const { registerBlockType } = wp.blocks;
    const { useBlockProps, InspectorControls } = wp.blockEditor;
    const { PanelBody, TextControl, TextareaControl, Button, Spinner, Notice } = wp.components;
    const { createElement: el, Fragment, useState, useEffect } = wp.element;
    const apiFetch = wp.apiFetch;

    /* ─── shared: save to REST helper ─── */
    function useSiteOptions() {
        const [opts, setOpts] = useState({});
        const [saving, setSaving] = useState(false);
        const [notice, setNotice] = useState('');
        useEffect(function(){
            apiFetch({ path: '/kv/v1/site-options' }).then(function(d){ setOpts(d || {}); });
        }, []);
        function upd(key, val) {
            setOpts(function(prev){ var o = Object.assign({}, prev); o[key] = val; return o; });
        }
        function save(updates) {
            setSaving(true);
            apiFetch({ path: '/kv/v1/site-options', method: 'POST', data: updates })
                .then(function(){ setNotice('saved'); setTimeout(function(){ setNotice(''); }, 2500); })
                .catch(function(){ setNotice('error'); })
                .finally(function(){ setSaving(false); });
        }
        return { opts: opts, upd: upd, save: save, saving: saving, notice: notice };
    }

    /* ─── Status bar ─── */
    function StatusBar(saving, notice) {
        if (saving) return el('div', { style:{ display:'flex', alignItems:'center', gap:'6px', fontSize:'12px', color:'#64748b', marginBottom:'10px' } }, el(Spinner), 'Saving...');
        if (notice === 'saved') return el('div', { style:{ background:'#dcfce7', color:'#166534', padding:'4px 10px', borderRadius:'6px', fontSize:'12px', marginBottom:'10px' } }, '\u2713 Saved to all pages');
        if (notice === 'error') return el('div', { style:{ background:'#fee2e2', color:'#991b1b', padding:'4px 10px', borderRadius:'6px', fontSize:'12px', marginBottom:'10px' } }, '\u2717 Save failed');
        return null;
    }

    /* ─── SmartField: text field that saves on blur ─── */
    function SmartField(opts, upd, save, key, label, extra) {
        extra = extra || {};
        var Comp = extra.ta ? TextareaControl : TextControl;
        return el(Comp, Object.assign({
            label: label,
            value: opts[key] || '',
            onChange: function(v){ upd(key, v); },
            onBlur: function(){ var u = {}; u[key] = opts[key] || ''; save(u); }
        }, extra.rows ? { rows: extra.rows } : {}, extra.type ? { type: extra.type } : {}));
    }

    /* ==========================================================
       kv/site-navbar  —  Navbar Settings Block
    ========================================================== */
    registerBlockType('kv/site-navbar', {
        title: 'KV Site Navbar Settings',
        description: 'Edit navbar logo/links/button — changes apply to ALL pages',
        icon: 'menu',
        category: 'widgets',
        keywords: ['navbar', 'menu', 'header', 'navigation'],
        supports: { html: false, reusable: true, multiple: false },
        attributes: {},
        edit: function() {
            const { opts, upd, save, saving, notice } = useSiteOptions();
            const blockProps = useBlockProps({ style:{ padding: 0 } });
            var nav = {
                logo:           opts.site_logo_url  || '',
                homeLabel:      opts.nav_home_label  || 'Home',
                aboutLabel:     opts.nav_about_label || 'About Us',
                aboutUrl:       opts.nav_about_url   || '/about/',
                productsLabel:  opts.nav_products_label || 'Products',
                contactLabel:   opts.nav_contact_label  || 'Contacts',
                contactUrl:     opts.nav_contact_url    || '/contact/',
                ctaText:        opts.nav_cta_text || '',
                ctaUrl:         opts.nav_cta_url  || '/contact/',
            };

            /* preview navbar */
            var navPreview = el('nav', { style:{ background:'#fff', borderBottom:'1px solid #e2e8f0', padding:'0 24px', display:'flex', alignItems:'center', justifyContent:'space-between', height:'64px', borderRadius:'8px 8px 0 0' } },
                /* logo */
                el('div', { style:{ display:'flex', alignItems:'center', gap:'10px' } },
                    nav.logo
                        ? el('img', { src: nav.logo, style:{ height:'40px', width:'auto', maxWidth:'140px', objectFit:'contain' } })
                        : el('span', { style:{ fontWeight:'700', color:'#0056d6', fontSize:'16px' } }, '\uD83D\uDEE1 LOGO')
                ),
                /* links */
                el('div', { style:{ display:'flex', alignItems:'center', gap:'24px' } },
                    el('span', { style:{ color:'#475569', fontSize:'14px', fontWeight:'500' } }, nav.homeLabel),
                    el('span', { style:{ color:'#475569', fontSize:'14px', fontWeight:'500' } }, nav.aboutLabel),
                    el('span', { style:{ color:'#475569', fontSize:'14px', fontWeight:'500' } }, nav.productsLabel + ' \u25BC'),
                    el('span', { style:{ color:'#475569', fontSize:'14px', fontWeight:'500' } }, nav.contactLabel)
                ),
                /* CTA button */
                el('div', { style:{ background:'#0056d6', color:'#fff', padding:'8px 18px', borderRadius:'6px', fontSize:'13px', fontWeight:'600' } },
                    nav.ctaText || '\uD83D\uDCDE ' + (opts.site_phone || '+66 2 108 8521')
                )
            );

            /* tip box */
            var tip = el('div', { style:{ background:'#eff6ff', borderRadius:'0 0 8px 8px', padding:'10px 16px', fontSize:'12px', color:'#1d4ed8', fontWeight:'500', display:'flex', alignItems:'center', gap:'6px' } },
                '\u2139\uFE0F Changes save to ALL pages automatically'
            );

            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, { title:'\uD83D\uDEE0 Status', initialOpen: true },
                        StatusBar(saving, notice),
                        el('p', { style:{ fontSize:'12px', color:'#64748b', margin:'0' } }, 'Edit fields below \u2192 click outside (Tab) to save. Changes affect every page.')
                    ),
                    el(PanelBody, { title:'\uD83D\uDDBC Logo', initialOpen: true },
                        SmartField(opts, upd, save, 'site_logo_url', 'Logo Image URL', { type:'url' }),
                        el('p', { style:{ fontSize:'11px', color:'#94a3b8', margin:'-4px 0 0' } }, 'Paste full URL or upload via Media Library')
                    ),
                    el(PanelBody, { title:'\uD83D\uDD17 Navigation Links', initialOpen: true },
                        SmartField(opts, upd, save, 'nav_home_label', 'Home Label'),
                        SmartField(opts, upd, save, 'nav_about_label', 'About Label'),
                        SmartField(opts, upd, save, 'nav_about_url', 'About URL'),
                        SmartField(opts, upd, save, 'nav_products_label', 'Products Label'),
                        SmartField(opts, upd, save, 'nav_contact_label', 'Contact Label'),
                        SmartField(opts, upd, save, 'nav_contact_url', 'Contact URL')
                    ),
                    el(PanelBody, { title:'\uD83D\uDCDE CTA Button (top-right)', initialOpen: false },
                        SmartField(opts, upd, save, 'nav_cta_text', 'Button Text', {}),
                        el('p', { style:{ fontSize:'11px', color:'#94a3b8', margin:'-4px 0 8px' } }, 'Leave blank to auto-show phone number'),
                        SmartField(opts, upd, save, 'nav_cta_url', 'Button URL', {})
                    )
                ),
                el('div', blockProps, navPreview, tip)
            );
        },
        save: function() { return null; }
    });

    /* ==========================================================
       kv/site-footer  —  Footer Settings Block
    ========================================================== */
    registerBlockType('kv/site-footer', {
        title: 'KV Site Footer Settings',
        description: 'Edit footer text/links — changes apply to ALL pages',
        icon: 'admin-appearance',
        category: 'widgets',
        keywords: ['footer', 'contact', 'links', 'copyright'],
        supports: { html: false, reusable: true, multiple: false },
        attributes: {},
        edit: function() {
            const { opts, upd, save, saving, notice } = useSiteOptions();
            const blockProps = useBlockProps({ style:{ padding: 0 } });

            /* mini footer preview */
            var previewStyle = { background:'#1e293b', borderRadius:'8px 8px 0 0', padding:'20px 24px', color:'#cbd5e1' };
            var colHd = { color:'#f8fafc', fontWeight:'700', fontSize:'13px', marginBottom:'8px', marginTop:'0' };
            var colBody = { fontSize:'12px', lineHeight:'1.8', margin:'0', color:'#94a3b8' };
            var footerPreview = el('div', { style: previewStyle },
                el('div', { style:{ display:'grid', gridTemplateColumns:'repeat(4, 1fr)', gap:'16px' } },
                    el('div', {},
                        el('p', { style: colHd }, opts.footer_col1_title || 'About Us'),
                        el('p', { style: colBody }, (opts.footer_about_text || '').slice(0, 80) + '\u2026')
                    ),
                    el('div', {},
                        el('p', { style: colHd }, opts.footer_col2_title || 'Products'),
                        el('p', { style: colBody }, '\u2022 Auto from DB\n\u2022 (categories)')
                    ),
                    el('div', {},
                        el('p', { style: colHd }, opts.footer_col3_title || 'Quick Links'),
                        el('p', { style: colBody }, (opts.footer_quick_links || 'About Us|/about\nContact|/contact').split('\n').slice(0,3).map(function(l){ return '\u2022 ' + l.split('|')[0]; }).join('\n'))
                    ),
                    el('div', {},
                        el('p', { style: colHd }, opts.footer_col4_title || 'Contact Info'),
                        el('p', { style: colBody }, '\uD83D\uDCCD ' + (opts.site_address || '').slice(0, 30) + '\n\uD83D\uDCDE ' + (opts.site_phone || '') + '\n\u2709\uFE0F ' + (opts.site_email || ''))
                    )
                )
            );
            var footerCopyright = el('div', { style:{ background:'#0f172a', borderRadius:'0 0 8px 8px', padding:'10px 24px', textAlign:'center', fontSize:'12px', color:'#64748b' } },
                '\u00A9 ' + new Date().getFullYear() + ' ' + (opts.site_company_name || 'KV Electronics') + ' \u2014 ' + (opts.site_copyright || 'All rights reserved.')
            );
            var tip2 = el('div', { style:{ background:'#eff6ff', borderRadius:'0', padding:'8px 16px', fontSize:'12px', color:'#1d4ed8', fontWeight:'500', display:'flex', alignItems:'center', gap:'6px' } },
                '\u2139\uFE0F Changes save to ALL pages automatically'
            );

            return el(Fragment, {},
                el(InspectorControls, {},
                    el(PanelBody, { title:'\uD83D\uDEE0 Status', initialOpen: true },
                        StatusBar(saving, notice),
                        el('p', { style:{ fontSize:'12px', color:'#64748b', margin:'0' } }, 'Tab out of each field to save.')
                    ),
                    el(PanelBody, { title:'\uD83C\uDFE2 Company Info', initialOpen: true },
                        SmartField(opts, upd, save, 'site_company_name', 'Company Name'),
                        SmartField(opts, upd, save, 'site_copyright', 'Copyright Text'),
                        SmartField(opts, upd, save, 'site_phone', 'Phone'),
                        SmartField(opts, upd, save, 'site_email', 'Email'),
                        SmartField(opts, upd, save, 'site_address', 'Short Address')
                    ),
                    el(PanelBody, { title:'\uD83D\uDCDD Column Titles', initialOpen: false },
                        SmartField(opts, upd, save, 'footer_col1_title', 'Column 1 Title (About)'),
                        SmartField(opts, upd, save, 'footer_col2_title', 'Column 2 Title (Products)'),
                        SmartField(opts, upd, save, 'footer_col3_title', 'Column 3 Title (Quick Links)'),
                        SmartField(opts, upd, save, 'footer_col4_title', 'Column 4 Title (Contact)')
                    ),
                    el(PanelBody, { title:'\uD83D\uDCCB About Text (Col 1)', initialOpen: false },
                        SmartField(opts, upd, save, 'footer_about_text', 'About Text', { ta: true, rows: 4 })
                    ),
                    el(PanelBody, { title:'\uD83D\uDD17 Quick Links (Col 3)', initialOpen: false },
                        el('p', { style:{ fontSize:'11px', color:'#64748b', margin:'0 0 6px' } }, 'One per line: Label|/url'),
                        SmartField(opts, upd, save, 'footer_quick_links', 'Quick Links', { ta: true, rows: 5 }),
                        el('p', { style:{ fontSize:'11px', color:'#94a3b8', margin:'-4px 0 0' } }, 'e.g.\nAbout Us|/about\nContact|/contact')
                    )
                ),
                el('div', blockProps, footerPreview, footerCopyright, tip2)
            );
        },
        save: function() { return null; }
    });

})(window.wp);
