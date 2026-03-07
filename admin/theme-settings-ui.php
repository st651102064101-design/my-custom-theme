<?php
/**
 * Theme Settings UI — Modern redesign
 * Included from my_theme_settings_page() in functions.php
 * All PHP variables from the data-loading block are available here.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// ── Load all settings directly from wp_options (self-contained, no alias deps) ─
$site_company_name    = get_option('site_company_name', get_theme_mod('site_company_name', 'Electronic Components Co., Ltd.'));
$site_copyright       = get_option('site_copyright',    get_theme_mod('site_copyright', 'All rights reserved.'));
$site_logo_url        = kv_rebase_url( get_option('site_logo_url', '') );
$site_logo_light_url  = kv_rebase_url( get_option('site_logo_light_url', '') );
$site_phone           = get_option('site_phone',         get_theme_mod('site_phone',    ''));
$site_fax             = get_option('site_fax',           get_theme_mod('site_fax',      ''));
$site_email           = get_option('site_email',         get_theme_mod('site_email',    'info@company.com'));
$site_email_sales     = get_option('site_email_sales',   'sales@company.com');
$site_address         = get_option('site_address',       get_theme_mod('site_address',  '123 Industrial Zone, Bangkok, Thailand'));
$site_address_full    = get_option('site_address_full',  "123 Industrial Zone\nBangna-Trad Road\nBangkok 10260, Thailand");
$site_hours_weekday   = get_option('site_hours_weekday', 'Monday – Friday: 8:00 AM – 5:00 PM');
$site_hours_weekend   = get_option('site_hours_weekend', 'Saturday – Sunday: Closed');
$banner_bg_color      = get_option('banner_bg_color', '#1a56db');
$banner_bg_image      = get_option('banner_bg_image', '');
$banner_bg_video      = get_option('banner_bg_video', '');
$banner_overlay       = (int) get_option('banner_overlay', 50);
$banner_fadein_delay_raw = get_option('banner_fadein_delay', 0);
$banner_fadein_delay  = ($banner_fadein_delay_raw === '' || $banner_fadein_delay_raw === null) ? 0 : (int) $banner_fadein_delay_raw;
$banner_video_start   = (float) get_option('banner_video_start', 0);
$banner_video_end     = (float) get_option('banner_video_end', 0);
$site_years_auto      = get_option('site_years_auto',    '0');
$site_founded_year    = (int) get_option('site_founded_year', 1988);
$site_products_auto   = get_option('site_products_auto', '0');
$site_countries_served= get_option('site_countries_served', 50);
$site_happy_customers = get_option('site_happy_customers', 1000);
$theme_primary        = get_option('theme_primary_color', '#0056d6');
$banner_bg_color      = $theme_primary;
$theme_accent         = get_option('theme_accent_color',  '#4ecdc4');
$theme_bg             = get_option('theme_bg_color',      '#ffffff');
$about_s1_heading     = get_option('about_s1_heading',   'Leading Electronic Components Manufacturer');
$about_s1_title1      = get_option('about_s1_title1',    '');
$about_s1_text1       = get_option('about_s1_text1',     '');
$about_s1_title2      = get_option('about_s1_title2',    '');
$about_s1_text2       = get_option('about_s1_text2',     '');
$about_s1_text3       = get_option('about_s1_text3',     '');
$about_s1_image       = get_option('about_s1_image',     '');
$about_s2_title1      = get_option('about_s2_title1',    '');
$about_s2_text1       = get_option('about_s2_text1',     '');
$about_s2_title2      = get_option('about_s2_title2',    '');
$about_s2_text2       = get_option('about_s2_text2',     '');
$about_s2_text3       = get_option('about_s2_text3',     '');
$about_s2_image       = get_option('about_s2_image',     '');
$about_mission_text   = get_option('about_mission_text', '');
$about_vision_text    = get_option('about_vision_text',  '');
$about_values         = get_option('about_values',       '');
$about_cta_heading    = get_option('about_cta_heading',  'Partner With Us');
$about_cta_text       = get_option('about_cta_text',     '');
$about_cta_btn_text   = get_option('about_cta_btn_text', 'Contact Us');
$about_cta_btn_url    = get_option('about_cta_btn_url',  '/contact');
$chat_widget_on       = get_option('chat_widget_enabled','1');
$chat_line_on         = get_option('chat_line_enabled',  '1');
$chat_line_id         = get_option('chat_line_id',       'kriangkrai2042');
$chat_wechat_on       = get_option('chat_wechat_enabled','1');
$chat_wechat_id       = get_option('chat_wechat_id',     'KVElectronics');
$chat_wechat_qr       = get_option('chat_wechat_qr_url', '');
$chat_whatsapp_on     = get_option('chat_whatsapp_enabled','1');
$chat_whatsapp_num    = get_option('chat_whatsapp_number','6621088521');
$social_facebook_url  = get_option('social_facebook_url', 'https://www.facebook.com/KVElectronicsTH/');
$social_instagram_url = get_option('social_instagram_url', 'https://www.instagram.com/kvelectronicsth/');
$social_linkedin_url  = get_option('social_linkedin_url', 'https://www.linkedin.com/company/kv-electronics-co-ltd');
$rag_chat_enabled     = get_option('rag_chat_enabled',   '1');
$nav_bg_color         = get_option('nav_bg_color',       '#ffffff');
$nav_text_color       = get_option('nav_text_color',     '');
$nav_hover_color      = get_option('nav_hover_color',    '');
$nav_active_color     = get_option('nav_active_color',   '');
$nav_font_size        = get_option('nav_font_size',      15);
$nav_font_weight      = get_option('nav_font_weight',    '500');
$nav_align            = get_option('nav_align',          'left');
$nav_padding_y        = get_option('nav_padding_y',      16);
$nav_sticky           = get_option('nav_sticky',         '1');
$nav_shadow           = get_option('nav_shadow',         '1');
$nav_logo_alt         = get_option('nav_logo_alt',       'Company Logo');
$nav_logo_height      = get_option('nav_logo_height',    50);
$nav_home_label       = get_option('nav_home_label',     'Home');
$nav_home_vis         = get_option('nav_home_visible',   '1');
$nav_about_label      = get_option('nav_about_label',    'About Us');
$nav_about_url_val    = get_option('nav_about_url',      '/about/');
$nav_about_vis        = get_option('nav_about_visible',  '1');
$nav_products_label   = get_option('nav_products_label', 'Products');
$nav_products_vis     = get_option('nav_products_visible','1');
$nav_contact_label    = get_option('nav_contact_label',  'Contacts');
$nav_contact_url_val  = get_option('nav_contact_url',    '/contact/');
$nav_contact_vis      = get_option('nav_contact_visible','1');
$nav_custom_items     = get_option('nav_custom_items',   '');
$nav_menu_items_json_raw = get_option('nav_menu_items_json', '');
$nav_cta_text_val     = get_option('nav_cta_text',       '');
$nav_cta_url_val      = get_option('nav_cta_url',        '/contact/');
$nav_cta_vis          = get_option('nav_cta_visible',    '1');
$nav_cta_bg           = get_option('nav_cta_bg',         $theme_primary);
$nav_cta_text_clr     = get_option('nav_cta_text_color', '#ffffff');
$nav_cta_font_size    = get_option('nav_cta_font_size',  14);
$nav_cta_radius       = get_option('nav_cta_radius',     6);
$footer_about_text    = get_option('footer_about_text',  '');
$footer_quick_links   = get_option('footer_quick_links', '');
$gallery_interval     = max(1000, (int) get_option('gallery_interval', 5000));

// ── Block Editor Manager data ─────────────────────────
$be_disabled_blocks   = json_decode(get_option('be_disabled_blocks', '[]'), true) ?: [];
$be_disabled_patterns = json_decode(get_option('be_disabled_patterns', '[]'), true) ?: [];
$be_custom_colors     = json_decode(get_option('be_custom_colors', ''), true);
$be_custom_font_sizes = json_decode(get_option('be_custom_font_sizes', ''), true);
$be_content_width     = get_option('be_content_width', '');
$be_wide_width        = get_option('be_wide_width', '');
$be_editor_css        = get_option('be_editor_css', '');
$be_spacing_units     = json_decode(get_option('be_spacing_units', ''), true);
$be_toggle_map = [
    'disable_code_editor'     => get_option('be_disable_code_editor', '0'),
    'disable_block_directory' => get_option('be_disable_block_directory', '0'),
    'disable_openverse'       => get_option('be_disable_openverse', '0'),
    'disable_remote_patterns' => get_option('be_disable_remote_patterns', '0'),
    'disable_font_library'    => get_option('be_disable_font_library', '0'),
    'default_fullscreen'      => get_option('be_default_fullscreen', '1'),
    'lock_blocks'             => get_option('be_lock_blocks', '0'),
    'disable_drop_cap'        => get_option('be_disable_drop_cap', '0'),
    'freedom_mode'            => get_option('be_freedom_mode', '0'),
];
// Read theme.json defaults for fallback
$_tj = json_decode(file_get_contents(get_template_directory() . '/theme.json'), true);
$be_def_colors  = $_tj['settings']['color']['palette'] ?? [];
$be_def_sizes   = $_tj['settings']['typography']['fontSizes'] ?? [];
$be_def_cw      = $_tj['settings']['layout']['contentSize'] ?? '1140px';
$be_def_ww      = $_tj['settings']['layout']['wideSize'] ?? '1320px';
$be_def_units   = $_tj['settings']['spacing']['units'] ?? ['px','em','rem','%','vh','vw'];
$be_eff_colors  = !empty($be_custom_colors) ? $be_custom_colors : $be_def_colors;
$be_eff_sizes   = !empty($be_custom_font_sizes) ? $be_custom_font_sizes : $be_def_sizes;
$be_eff_cw      = $be_content_width ?: $be_def_cw;
$be_eff_ww      = $be_wide_width ?: $be_def_ww;
$be_eff_units   = !empty($be_spacing_units) ? $be_spacing_units : $be_def_units;
$be_all_blocks   = function_exists('kv_be_get_all_blocks') ? kv_be_get_all_blocks() : [];
$be_all_patterns = function_exists('kv_be_get_all_patterns') ? kv_be_get_all_patterns() : [];
?>
<style>
/* ─── Reset & Root ─────────────────────────────── */
.ts-wrap *{box-sizing:border-box;}
.ts-wrap{--ts-bg:#f1f5f9;--ts-surface:#fff;--ts-border:#e2e8f0;--ts-text:#1e293b;--ts-muted:#64748b;--ts-primary:<?php echo esc_attr($theme_primary ?: '#1a56db'); ?>;--ts-radius:10px;--ts-shadow:0 1px 3px rgba(0,0,0,.08);font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;font-size:14px;color:var(--ts-text);background:var(--ts-bg);min-height:100vh;display:flex;flex-direction:column;}
/* ─── Top Bar ──────────────────────────────────── */
.ts-topbar{position:sticky;top:32px;z-index:100;background:var(--ts-surface);border-bottom:1px solid var(--ts-border);padding:0 24px;height:56px;display:flex;align-items:center;justify-content:space-between;gap:16px;}
.ts-topbar h1{margin:0;font-size:16px;font-weight:600;color:var(--ts-text);display:flex;align-items:center;gap:8px;}
.ts-topbar h1 span.dot{width:8px;height:8px;border-radius:50%;background:var(--ts-primary);display:inline-block;}
.ts-save-btn{background:var(--ts-primary);color:#fff;border:none;border-radius:7px;padding:8px 22px;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:6px;transition:opacity .15s;}
.ts-save-btn:hover{opacity:.87;}
.ts-save-btn svg{width:15px;height:15px;flex-shrink:0;}
/* ─── Body ─────────────────────────────────────── */
.ts-body{display:flex;flex:1;gap:0;min-height:0;}
/* ─── Sidebar ──────────────────────────────────── */
.ts-sidebar{width:212px;flex-shrink:0;background:var(--ts-surface);border-right:1px solid var(--ts-border);padding:16px 8px;display:flex;flex-direction:column;gap:2px;}
.ts-nav-item{display:flex;align-items:center;gap:10px;padding:9px 12px;border-radius:7px;cursor:pointer;font-size:13.5px;font-weight:500;color:var(--ts-muted);border:none;background:transparent;width:100%;text-align:left;transition:background .12s,color .12s;}
.ts-nav-item:hover{background:#f1f5f9;color:var(--ts-text);}
.ts-nav-item.active{background:#eff6ff;color:var(--ts-primary);font-weight:600;}
.ts-nav-item .ico{font-size:16px;width:20px;text-align:center;}
.ts-nav-sep{height:1px;background:var(--ts-border);margin:8px 4px;}
/* ─── Content ──────────────────────────────────── */
.ts-content{flex:1;overflow-y:auto;padding:28px 32px;max-width:960px;}
.ts-panel{display:none;}
.ts-panel.active{display:block;}
/* ─── Section Card ─────────────────────────────── */
.ts-card{background:var(--ts-surface);border:1px solid var(--ts-border);border-radius:var(--ts-radius);padding:24px;margin-bottom:20px;}
.ts-card-title{font-size:14px;font-weight:700;color:var(--ts-text);margin:0 0 4px;}
.ts-card-desc{font-size:12.5px;color:var(--ts-muted);margin:0 0 20px;}
/* ─── Field Groups ─────────────────────────────── */
.ts-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px 24px;}
.ts-form-grid.cols-3{grid-template-columns:1fr 1fr 1fr;}
.ts-form-grid.cols-1{grid-template-columns:1fr;}
.ts-field{display:flex;flex-direction:column;gap:5px;}
.ts-field label{font-size:12.5px;font-weight:600;color:var(--ts-text);}
.ts-field .hint{font-size:11.5px;color:var(--ts-muted);margin-top:2px;}
.ts-field input[type=text],.ts-field input[type=url],.ts-field input[type=email],.ts-field input[type=number],.ts-field input[type=tel],.ts-field select,.ts-field textarea{border:1px solid var(--ts-border);border-radius:6px;padding:7px 10px;font-size:13px;color:var(--ts-text);width:100%;outline:none;transition:border-color .12s;background:#fff;}
.ts-field input:focus,.ts-field select:focus,.ts-field textarea:focus{border-color:var(--ts-primary);box-shadow:0 0 0 3px color-mix(in srgb,var(--ts-primary) 15%,transparent);}
.ts-field input[type=color]{width:42px;height:34px;border:1px solid var(--ts-border);border-radius:6px;padding:2px;cursor:pointer;background:#fff;}
.ts-field textarea{resize:vertical;min-height:80px;}
/* ─── Color Row ────────────────────────────────── */
.ts-color-row{display:flex;align-items:center;gap:10px;}
.ts-color-row .swatch-group{display:flex;gap:5px;flex-wrap:wrap;margin-top:6px;}
.ts-color-row .swatch{width:24px;height:24px;border-radius:50%;border:2px solid #fff;box-shadow:0 0 0 1px rgba(0,0,0,.15);cursor:pointer;transition:transform .12s;}
.ts-color-row .swatch:hover{transform:scale(1.2);}
/* ─── Toggle Switch ────────────────────────────── */
.ts-toggle-row{display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--ts-border);}
.ts-toggle-row:last-child{border-bottom:none;}
.ts-toggle-row .t-lbl{font-size:13px;font-weight:500;color:var(--ts-text);}
.ts-toggle-row .t-sub{font-size:12px;color:var(--ts-muted);}
.ts-switch{position:relative;width:38px;height:22px;flex-shrink:0;}
.ts-switch input{opacity:0;width:0;height:0;}
.ts-switch .slider{position:absolute;inset:0;background:#cbd5e1;border-radius:999px;cursor:pointer;transition:.2s;}
.ts-switch .slider:before{content:'';position:absolute;width:16px;height:16px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.2s;}
.ts-switch input:checked+.slider{background:var(--ts-primary);}
.ts-switch input:checked+.slider:before{transform:translateX(16px);}
/* ─── Inline Preview Bar ───────────────────────── */
.ts-color-preview{display:flex;gap:8px;align-items:center;padding:10px 14px;background:#f8fafc;border:1px solid var(--ts-border);border-radius:8px;margin-bottom:20px;}
.ts-color-preview .cp-dot{width:30px;height:30px;border-radius:50%;border:2px solid #fff;box-shadow:var(--ts-shadow);}
.ts-color-preview .cp-label{font-size:11.5px;color:var(--ts-muted);}
/* ─── Nav Menu Builder ─────────────────────────── */
#nav-menu-builder{list-style:none;margin:0;padding:0;}
.nmb-row{display:flex;align-items:center;gap:8px;padding:7px 10px;border:1px solid var(--ts-border);border-radius:7px;margin-bottom:6px;background:#fafafa;transition:box-shadow .15s,background .15s;}
.nmb-row.drag-over{box-shadow:0 0 0 2px var(--ts-primary);background:#eff6ff;}
.nmb-row.dragging{opacity:.35;}
.nmb-drag{cursor:grab;color:var(--ts-muted);font-size:16px;flex-shrink:0;user-select:none;line-height:1;}
.nmb-drag:active{cursor:grabbing;}
.nmb-type-badge{font-size:10px;background:#e2e8f0;border-radius:4px;padding:2px 6px;color:#475569;flex-shrink:0;white-space:nowrap;font-weight:600;}
.nmb-row input[type=text]{flex:1;min-width:0;border:1px solid var(--ts-border);border-radius:5px;padding:4px 7px;font-size:12.5px;}
.nmb-row input[type=text]:focus{outline:none;border-color:var(--ts-primary);box-shadow:0 0 0 2px #dbeafe;}
.nmb-auto-url{font-size:11px;color:var(--ts-muted);white-space:nowrap;flex-shrink:0;background:#f1f5f9;padding:3px 8px;border-radius:4px;border:1px solid #e2e8f0;}
.nmb-newtab{display:flex;align-items:center;gap:4px;font-size:11px;color:var(--ts-muted);flex-shrink:0;white-space:nowrap;cursor:pointer;}
.nmb-newtab input{margin:0;cursor:pointer;}
.nmb-del{border:none;background:none;cursor:pointer;color:#94a3b8;padding:3px 6px;border-radius:4px;font-size:14px;flex-shrink:0;line-height:1;transition:background .1s,color .1s;}
.nmb-del:hover{background:#fee2e2;color:#dc2626;}
.nmb-add-btn{display:inline-flex;align-items:center;gap:6px;margin-top:10px;padding:7px 16px;border:1.5px dashed var(--ts-border);border-radius:7px;background:none;cursor:pointer;font-size:12.5px;color:var(--ts-muted);transition:border-color .15s,color .15s;}
.nmb-add-btn:hover{border-color:var(--ts-primary);color:var(--ts-primary);}
/* ─── Toast ────────────────────────────────────── */
#ts-toast{position:fixed;bottom:28px;right:28px;z-index:99999;display:flex;flex-direction:column;gap:8px;pointer-events:none;}
.ts-toast-msg{background:#1e293b;color:#fff;padding:10px 18px;border-radius:8px;font-size:13px;opacity:0;transform:translateY(10px);transition:opacity .25s,transform .25s;display:flex;align-items:center;gap:8px;pointer-events:auto;}
.ts-toast-msg.show{opacity:1;transform:translateY(0);}
.ts-toast-msg.success{background:#16a34a;}
.ts-toast-msg.error{background:#dc2626;}
/* ─── Navbar Live Preview ──────────────────────── */
.ts-nav-preview{border-radius:8px;overflow:hidden;margin-bottom:20px;box-shadow:var(--ts-shadow);}
.ts-nav-preview iframe{width:100%;height:500px;border:none;display:block;}
/* ─── Section divider ──────────────────────────── */
.ts-divider{height:1px;background:var(--ts-border);margin:20px 0;}
/* ─── Responsive ───────────────────────────────── */
@media(max-width:900px){.ts-body{flex-direction:column;}.ts-sidebar{width:100%;flex-direction:row;overflow-x:auto;border-right:none;border-bottom:1px solid var(--ts-border);}.ts-content{padding:20px 16px;}}
@media(max-width:900px){.ts-nav-preview iframe{height:320px;}}
@media(max-width:900px){.ts-cert-grid{grid-template-columns:1fr;}}
/* ─── Image picker row ─────────────────────────── */
.ts-img-row{display:flex;gap:10px;align-items:flex-start;}
.ts-img-row input{flex:1;}
.ts-img-preview{width:80px;height:60px;object-fit:contain;border:1px solid var(--ts-border);border-radius:6px;background:#f8fafc;padding:2px;}
/* ─── Section header inside card ──────────────────*/
.ts-sec-head{font-size:12px;font-weight:700;color:var(--ts-muted);text-transform:uppercase;letter-spacing:.6px;margin:20px 0 12px;padding-bottom:6px;border-bottom:1px solid var(--ts-border);}
.ts-sec-head:first-child{margin-top:0;}
/* ─── Info box ─────────────────────────────────── */
.ts-info{background:#eff6ff;border:1px solid #bfdbfe;border-radius:7px;padding:10px 14px;font-size:12.5px;color:#1d4ed8;margin-top:14px;line-height:1.6;}
.ts-range-wrap{display:flex;align-items:center;gap:10px;}
.ts-range-wrap input[type=range]{flex:1;}
.ts-pill{display:inline-flex;align-items:center;justify-content:center;min-width:62px;padding:4px 8px;border:1px solid var(--ts-border);border-radius:999px;background:#f8fafc;font-size:12px;color:var(--ts-text);font-variant-numeric:tabular-nums;}
/* ─── Block Editor Manager ─────────────────── */
.be-summary-bar{display:flex;align-items:center;gap:16px;flex-wrap:wrap;}
.be-badge{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:999px;font-size:12px;font-weight:600;}
.be-badge.green{background:#dcfce7;color:#166534;}
.be-badge.red{background:#fee2e2;color:#991b1b;}
.be-badge.blue{background:#dbeafe;color:#1e40af;}
.be-toolbar{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:16px;}
.be-toolbar input[type=text]{flex:1;min-width:180px;border:1px solid var(--ts-border);border-radius:6px;padding:7px 10px;font-size:13px;outline:none;}
.be-toolbar input[type=text]:focus{border-color:var(--ts-primary);box-shadow:0 0 0 3px color-mix(in srgb,var(--ts-primary) 15%,transparent);}
.be-cat-pills{display:flex;gap:4px;flex-wrap:wrap;}
.be-cat-pill{padding:4px 10px;border-radius:999px;border:1px solid var(--ts-border);background:#f8fafc;font-size:11.5px;cursor:pointer;transition:.15s;white-space:nowrap;}
.be-cat-pill:hover{background:#e2e8f0;}
.be-cat-pill.active{background:var(--ts-primary);color:#fff;border-color:var(--ts-primary);}
.be-bulk-btn{padding:4px 10px;border:1px solid var(--ts-border);border-radius:6px;background:#f8fafc;font-size:11.5px;cursor:pointer;transition:.12s;}
.be-bulk-btn:hover{background:#e2e8f0;}
.be-block-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:8px;max-height:480px;overflow-y:auto;padding:4px 0;}
.be-block-card{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:7px 10px;border:1px solid var(--ts-border);border-radius:6px;background:#fafafa;transition:border-color .12s;}
.be-block-card:hover{border-color:var(--ts-primary);}
.be-block-card.disabled{opacity:.5;background:#fef2f2;}
.be-block-card .be-bname{font-size:12px;font-weight:500;color:var(--ts-text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:150px;}
.be-block-card .be-bcat{font-size:10px;color:var(--ts-muted);}
.be-block-count{font-size:12px;color:var(--ts-muted);margin-bottom:8px;}
.be-pattern-row{display:flex;align-items:center;justify-content:space-between;padding:8px 10px;border-bottom:1px solid var(--ts-border);transition:.12s;}
.be-pattern-row:last-child{border-bottom:none;}
.be-pattern-row:hover{background:#f8fafc;}
.be-pattern-row .be-ptitle{font-size:12.5px;font-weight:500;}
.be-pattern-row .be-pdesc{font-size:11px;color:var(--ts-muted);}
.be-pattern-row .be-pcat{font-size:10px;color:var(--ts-primary);margin-top:2px;}
.be-color-item{display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid var(--ts-border);}
.be-color-item:last-child{border-bottom:none;}
.be-color-item input[type=color]{width:36px;height:30px;border:1px solid var(--ts-border);border-radius:6px;padding:2px;cursor:pointer;}
.be-color-item input[type=text]{border:1px solid var(--ts-border);border-radius:5px;padding:5px 8px;font-size:12px;}
.be-color-item .be-c-name{width:120px;}
.be-color-item .be-c-slug{width:100px;font-family:monospace;font-size:11px;color:var(--ts-muted);background:#f8fafc;}
.be-color-item .be-c-hex{width:80px;font-family:monospace;font-size:11px;}
.be-remove-btn{width:26px;height:26px;border:none;background:#fee2e2;color:#dc2626;border-radius:50%;cursor:pointer;font-size:13px;display:flex;align-items:center;justify-content:center;transition:.12s;flex-shrink:0;}
.be-remove-btn:hover{background:#dc2626;color:#fff;}
.be-size-item{display:flex;align-items:center;gap:8px;padding:6px 0;border-bottom:1px solid var(--ts-border);}
.be-size-item:last-child{border-bottom:none;}
.be-size-item input{border:1px solid var(--ts-border);border-radius:5px;padding:5px 8px;font-size:12px;}
.be-size-item .be-s-name{width:100px;}
.be-size-item .be-s-slug{width:80px;font-family:monospace;font-size:11px;color:var(--ts-muted);background:#f8fafc;}
.be-size-item .be-s-size{width:80px;font-family:monospace;}
.be-add-btn{display:inline-flex;align-items:center;gap:5px;padding:6px 14px;border:1px dashed var(--ts-border);border-radius:6px;background:transparent;font-size:12px;color:var(--ts-primary);cursor:pointer;margin-top:8px;transition:.12s;}
.be-add-btn:hover{background:#eff6ff;border-color:var(--ts-primary);}
.be-reset-btn{display:inline-flex;align-items:center;gap:5px;padding:6px 14px;border:1px solid var(--ts-border);border-radius:6px;background:#f8fafc;font-size:12px;color:var(--ts-muted);cursor:pointer;margin-top:8px;margin-left:6px;transition:.12s;}
.be-reset-btn:hover{background:#fee2e2;color:#dc2626;border-color:#fca5a5;}
.be-unit-checks{display:flex;gap:10px;flex-wrap:wrap;}
.be-unit-check{display:flex;align-items:center;gap:5px;padding:6px 12px;border:1px solid var(--ts-border);border-radius:6px;font-size:12px;cursor:pointer;transition:.12s;}
.be-unit-check:hover{border-color:var(--ts-primary);}
.be-unit-check.active{background:var(--ts-primary);color:#fff;border-color:var(--ts-primary);}
.be-editor-css{width:100%;min-height:150px;font-family:'SF Mono',Monaco,Consolas,monospace;font-size:12px;border:1px solid var(--ts-border);border-radius:6px;padding:10px;resize:vertical;line-height:1.6;background:#1e293b;color:#e2e8f0;tab-size:2;}
.be-editor-css:focus{border-color:var(--ts-primary);box-shadow:0 0 0 3px color-mix(in srgb,var(--ts-primary) 15%,transparent);outline:none;}
.be-save-float{position:sticky;bottom:0;background:var(--ts-surface);border-top:1px solid var(--ts-border);padding:12px 0;margin:0 -32px;padding:12px 32px;display:flex;align-items:center;justify-content:space-between;z-index:10;}
</style>

<div id="ts-toast"></div>

<div class="ts-wrap wrap">
  <!-- ── Top Bar ─────────────────────────────── -->
  <div class="ts-topbar">
    <h1><span class="dot"></span> ⚙️ ตั้งค่าเว็บไซต์</h1>
    <div style="display:flex;gap:10px;align-items:center;">
      <span id="ts-save-status" style="font-size:12px;color:var(--ts-muted);"></span>
      <button type="button" class="ts-save-btn" id="ts-submit-btn" onclick="document.getElementById('ts-main-form').requestSubmit();">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
        บันทึกการตั้งค่า
      </button>
    </div>
  </div>

  <!-- ── Body ──────────────────────────────────── -->
  <div class="ts-body">
    <!-- ── Sidebar ──────────────────────────────── -->
    <nav class="ts-sidebar">
      <button class="ts-nav-item active" data-tab="general"><span class="ico">🏠</span> ทั่วไป</button>
      <button class="ts-nav-item" data-tab="appearance"><span class="ico">🎨</span> ธีมสี & รูปลักษณ์</button>
      <button class="ts-nav-item" data-tab="contact"><span class="ico">📞</span> ข้อมูลติดต่อ</button>
      <button class="ts-nav-item" data-tab="navbar"><span class="ico">🔗</span> Navbar</button>
      <button class="ts-nav-item" data-tab="about"><span class="ico">📘</span> About</button>

      <div class="ts-nav-sep"></div>
      <button class="ts-nav-item" data-tab="chat"><span class="ico">🤖</span> AI Search</button>
      <button class="ts-nav-item" data-tab="footer"><span class="ico">📄</span> Footer</button>

      <button class="ts-nav-item" data-tab="gallery"><span class="ico">🖼️</span> แกลเลอรี</button>
      <div class="ts-nav-sep"></div>
      <button class="ts-nav-item" data-tab="blockeditor"><span class="ico">🧩</span> Block Editor</button>
    </nav>

    <!-- ── Content ────────────────────────────── -->
    <div class="ts-content">
      <form id="ts-main-form" method="post" action="<?php echo esc_url(admin_url('admin.php?page=my-theme-settings')); ?>">
        <?php wp_nonce_field('kv_theme_settings_save', 'kv_theme_nonce'); ?>

        <!-- ═══════════ TAB: GENERAL ═══════════ -->
        <div class="ts-panel active" id="tab-general">

          <!-- Company -->
          <div class="ts-card">
            <div class="ts-card-title">🏢 ข้อมูลบริษัท</div>
            <div class="ts-card-desc">ชื่อบริษัทและลิขสิทธิ์ที่แสดงในเว็บไซต์</div>
            <div class="ts-form-grid">
              <div class="ts-field">
                <label for="site_company_name">ชื่อบริษัท</label>
                <input type="text" id="site_company_name" name="site_company_name" value="<?php echo esc_attr(get_option('site_company_name', get_theme_mod('site_company_name', 'Electronic Components Co., Ltd.'))); ?>" placeholder="Electronic Components Co., Ltd.">
              </div>
              <div class="ts-field">
                <label for="site_copyright">ข้อความลิขสิทธิ์</label>
                <input type="text" id="site_copyright" name="site_copyright" value="<?php echo esc_attr(get_option('site_copyright', get_theme_mod('site_copyright', 'All rights reserved.'))); ?>" placeholder="All rights reserved.">
              </div>
            </div>
          </div>

          <!-- Stats -->
          <div class="ts-card">
            <div class="ts-card-title">📊 สถิติบริษัท</div>
            <div class="ts-card-desc">ตัวเลขที่แสดงในหน้าเว็บ เช่น ประสบการณ์, สินค้า, ลูกค้า</div>

            <div class="ts-sec-head">ประสบการณ์ (ปี)</div>
            <div class="ts-form-grid">
              <div class="ts-field">
                <label>โหมดการคำนวณ</label>
                <select name="site_years_auto">
                  <option value="1" <?php selected($site_years_auto, '1'); ?>>คำนวณอัตโนมัติจากปีที่ก่อตั้ง</option>
                  <option value="0" <?php selected($site_years_auto, '0'); ?>>ระบุค่าเอง</option>
                </select>
              </div>
              <div class="ts-field">
                <label for="site_founded_year">ปีที่ก่อตั้ง (อัตโนมัติ)</label>
                <input type="number" id="site_founded_year" name="site_founded_year" value="<?php echo esc_attr($site_founded_year); ?>" min="1900" max="2099" placeholder="2003">
              </div>
            </div>

            <div class="ts-sec-head">สินค้า & ลูกค้า</div>
            <div class="ts-form-grid">
              <div class="ts-field">
                <label>จำนวนสินค้า</label>
                <select name="site_products_auto">
                  <option value="1" <?php selected($site_products_auto, '1'); ?>>นับจาก WooCommerce/CPT อัตโนมัติ</option>
                  <option value="0" <?php selected($site_products_auto, '0'); ?>>ระบุค่าเอง</option>
                </select>
              </div>
              <div class="ts-field">
                <label for="site_countries_served">ประเทศที่ให้บริการ</label>
                <input type="number" id="site_countries_served" name="site_countries_served" value="<?php echo esc_attr($site_countries_served); ?>" min="0" placeholder="30">
              </div>
            </div>
            <div class="ts-form-grid" style="margin-top:16px;">
              <div class="ts-field">
                <label for="site_happy_customers">จำนวนลูกค้าที่พอใจ</label>
                <input type="number" id="site_happy_customers" name="site_happy_customers" value="<?php echo esc_attr($site_happy_customers); ?>" min="0" placeholder="1000">
              </div>
            </div>
          </div>
        </div>
        <!-- ═══ END TAB GENERAL ═══ -->

        <!-- ═══════════ TAB: APPEARANCE ═══════════ -->
        <div class="ts-panel" id="tab-appearance">

          <!-- Color Scheme -->
          <div class="ts-card">
            <div class="ts-card-title">🎨 ชุดสี 60:30:10</div>
            <div class="ts-card-desc">สีพื้นหลัง (60%) · สีหลัก (30%) · สีเน้น (10%)</div>

            <!-- Live preview bar -->
            <div class="ts-color-preview" id="ts-color-preview-bar">
              <div class="cp-dot" id="prev-bg" style="background:<?php echo esc_attr($theme_bg ?: '#ffffff'); ?>;"></div>
              <div>
                <div style="font-size:12px;font-weight:600;">พื้นหลัง</div>
                <div class="cp-label" id="prev-bg-val"><?php echo esc_html($theme_bg ?: '#ffffff'); ?></div>
              </div>
              <div style="width:1px;height:30px;background:var(--ts-border);margin:0 4px;"></div>
              <div class="cp-dot" id="prev-primary" style="background:<?php echo esc_attr($theme_primary ?: '#1a56db'); ?>;"></div>
              <div>
                <div style="font-size:12px;font-weight:600;">สีหลัก</div>
                <div class="cp-label" id="prev-primary-val"><?php echo esc_html($theme_primary ?: '#1a56db'); ?></div>
              </div>
              <div style="width:1px;height:30px;background:var(--ts-border);margin:0 4px;"></div>
              <div class="cp-dot" id="prev-accent" style="background:<?php echo esc_attr($theme_accent ?: '#ff6b35'); ?>;"></div>
              <div>
                <div style="font-size:12px;font-weight:600;">สีเน้น</div>
                <div class="cp-label" id="prev-accent-val"><?php echo esc_html($theme_accent ?: '#ff6b35'); ?></div>
              </div>
            </div>

            <div class="ts-form-grid cols-3">
              <div class="ts-field">
                <label for="theme_bg_color">🏠 สีพื้นหลัง (60%)</label>
                <div class="ts-color-row">
                  <input type="color" id="theme_bg_color" name="theme_bg_color" value="<?php echo esc_attr($theme_bg ?: '#ffffff'); ?>">
                  <input type="text" id="theme_bg_hex" value="<?php echo esc_attr($theme_bg ?: '#ffffff'); ?>" style="flex:1;font-family:monospace;font-size:12px;" maxlength="7" placeholder="#ffffff">
                </div>
                <div class="swatch-group">
                  <?php foreach(['#ffffff','#f8fafc','#f1f5f9','#0f172a','#1e293b','#111827'] as $c): ?>
                  <span class="swatch" data-target="theme_bg_color" style="background:<?php echo $c ?>" title="<?php echo $c ?>"></span>
                  <?php endforeach; ?>
                </div>
              </div>
              <div class="ts-field">
                <label for="theme_primary_color">🔵 สีหลัก (30%)</label>
                <div class="ts-color-row">
                  <input type="color" id="theme_primary_color" name="theme_primary_color" value="<?php echo esc_attr($theme_primary ?: '#1a56db'); ?>">
                  <input type="text" id="theme_primary_hex" value="<?php echo esc_attr($theme_primary ?: '#1a56db'); ?>" style="flex:1;font-family:monospace;font-size:12px;" maxlength="7" placeholder="#1a56db">
                </div>
                <div class="swatch-group">
                  <?php foreach(['#1a56db','#2563eb','#7c3aed','#0f766e','#0369a1','#1e40af'] as $c): ?>
                  <span class="swatch" data-target="theme_primary_color" style="background:<?php echo $c ?>" title="<?php echo $c ?>"></span>
                  <?php endforeach; ?>
                </div>
              </div>
              <div class="ts-field">
                <label for="theme_accent_color">🟠 สีเน้น (10%)</label>
                <div class="ts-color-row">
                  <input type="color" id="theme_accent_color" name="theme_accent_color" value="<?php echo esc_attr($theme_accent ?: '#ff6b35'); ?>">
                  <input type="text" id="theme_accent_hex" value="<?php echo esc_attr($theme_accent ?: '#ff6b35'); ?>" style="flex:1;font-family:monospace;font-size:12px;" maxlength="7" placeholder="#ff6b35">
                </div>
                <div class="swatch-group">
                  <?php foreach(['#ff6b35','#f97316','#ef4444','#eab308','#22c55e','#ec4899'] as $c): ?>
                  <span class="swatch" data-target="theme_accent_color" style="background:<?php echo $c ?>" title="<?php echo $c ?>"></span>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Logo -->
          <div class="ts-card">
            <div class="ts-card-title">🖼️ โลโก้เว็บไซต์</div>
            <div class="ts-card-desc">URL รูปโลโก้หลักของเว็บไซต์</div>
            <div class="ts-field">
              <label>Logo URL</label>
              <div class="ts-img-row">
                <input type="url" name="site_logo_url" id="site_logo_url" value="<?php echo esc_attr($site_logo_url); ?>" placeholder="https://...">
                <button type="button" class="button" id="logo_pick_btn" style="flex-shrink:0;">📷 เลือกรูป</button>
                <img id="logo_preview" src="<?php echo esc_url($site_logo_url ?: admin_url('images/wordpress-logo.svg')); ?>" class="ts-img-preview" style="<?php echo $site_logo_url ? '' : 'display:none;'; ?>">
              </div>
              <span class="hint">รองรับไฟล์ JPG, PNG, SVG</span>
            </div>
            <div class="ts-field" style="margin-top:12px;">
              <label>Logo Light URL</label>
              <div class="ts-img-row">
                <input type="url" name="site_logo_light_url" id="site_logo_light_url" value="<?php echo esc_attr($site_logo_light_url); ?>" placeholder="https://...">
                <button type="button" class="button" id="logo_light_pick_btn" style="flex-shrink:0;">📷 เลือกรูป</button>
                <img id="logo_light_preview" src="<?php echo esc_url($site_logo_light_url ?: admin_url('images/wordpress-logo.svg')); ?>" class="ts-img-preview" style="<?php echo $site_logo_light_url ? '' : 'display:none;'; ?>">
              </div>
              <span class="hint">รองรับไฟล์ JPG, PNG, SVG</span>
            </div>
          </div>

          <!-- Banner -->
          <div class="ts-card">
            <div class="ts-card-title">🖼️ แบนเนอร์</div>
            <div class="ts-card-desc">ภาพพื้นหลังและส่วนซ้อนทับสำหรับหน้า Banner Hero</div>
            <div class="ts-form-grid cols-3">
              <div class="ts-field">
                <label for="banner_bg_color">สีพื้นหลัง Fallback</label>
                <div class="ts-color-row">
                  <input type="color" id="banner_bg_color" value="<?php echo esc_attr($theme_primary ?: '#1a56db'); ?>" disabled>
                  <input type="text" id="banner_bg_hex" value="<?php echo esc_attr($theme_primary ?: '#1a56db'); ?>" style="flex:1;font-family:monospace;font-size:12px;" maxlength="7" readonly>
                </div>
                <input type="hidden" id="banner_bg_color_hidden" name="banner_bg_color" value="<?php echo esc_attr($theme_primary ?: '#1a56db'); ?>">
                <span class="hint">ใช้ค่าเดียวกับ 🔵 สีหลัก (30%) อัตโนมัติ</span>
              </div>
              <div class="ts-field">
                <label>% ความเข้มโอเวอร์เลย์</label>
                <input type="number" name="banner_overlay" value="<?php echo esc_attr($banner_overlay ?: 50); ?>" min="0" max="100" step="5" placeholder="50">
                <span class="hint">0 = ใส, 100 = ทึบ</span>
              </div>
              <div class="ts-field">
                <label>เวลาหน่วงก่อนแสดง (วินาที)</label>
                <input type="number" name="banner_fadein_delay" value="<?php echo esc_attr($banner_fadein_delay); ?>" min="0" max="30" step="1" placeholder="5">
                <span class="hint">กำหนดเวลารอก่อนหัวข้อ/ปุ่มจะแสดง</span>
              </div>
            </div>
            <div class="ts-field" style="margin-top:16px;">
              <label>รูปพื้นหลัง Banner</label>
              <div class="ts-img-row">
                <input type="url" name="banner_bg_image" id="banner_bg_image" value="<?php echo esc_attr($banner_bg_image); ?>" placeholder="https://...">
                <button type="button" class="button" id="banner_pick_btn" style="flex-shrink:0;">📷 เลือกรูป</button>
                <img id="banner_preview" src="<?php echo esc_url($banner_bg_image); ?>" class="ts-img-preview" style="<?php echo $banner_bg_image ? '' : 'display:none;'; ?>">
              </div>
            </div>
            <div class="ts-field" style="margin-top:16px;">
              <label>วิดีโอพื้นหลัง Banner</label>
              <div class="ts-img-row">
                <input type="url" name="banner_bg_video" id="banner_bg_video" value="<?php echo esc_attr($banner_bg_video); ?>" placeholder="https://.../banner.mp4">
                <button type="button" class="button" id="banner_video_pick_btn" style="flex-shrink:0;">🎬 เลือกวิดีโอ</button>
                <video id="banner_video_preview" class="ts-img-preview" style="<?php echo $banner_bg_video ? '' : 'display:none;'; ?>" muted loop playsinline>
                  <source src="<?php echo esc_url($banner_bg_video); ?>" type="video/mp4">
                </video>
              </div>
              <span class="hint">รองรับ MP4, M4V, WEBM (แนะนำ MP4)</span>
            </div>
            <div class="ts-field" style="margin-top:16px;">
              <label>ช่วงเวลาวิดีโอ Banner (Start / End)</label>
              <video id="banner_video_trim_preview" controls muted playsinline style="width:100%;max-width:420px;border:1px solid var(--ts-border);border-radius:8px;background:#0f172a;<?php echo $banner_bg_video ? '' : 'display:none;'; ?>">
                <source src="<?php echo esc_url($banner_bg_video); ?>" type="video/mp4">
              </video>
              <div class="hint" id="banner_video_duration_hint" style="margin-top:6px;">เลือกวินาทีเริ่ม/จบด้วยแถบเลื่อน (วิดีโอเต็มความยาว)</div>

              <div style="margin-top:12px;display:flex;flex-direction:column;gap:10px;">
                <div class="ts-range-wrap">
                  <label for="banner_video_start_ui" style="min-width:52px;margin:0;font-size:12.5px;font-weight:600;color:var(--ts-text);">Start</label>
                  <input type="range" id="banner_video_start_ui" min="0" max="0" step="0.1" value="<?php echo esc_attr($banner_video_start); ?>">
                  <span class="ts-pill" id="banner_video_start_val">0:00</span>
                  <button type="button" class="button" id="banner_video_set_start_now">ใช้เวลาปัจจุบัน</button>
                </div>
                <div class="ts-range-wrap">
                  <label for="banner_video_end_ui" style="min-width:52px;margin:0;font-size:12.5px;font-weight:600;color:var(--ts-text);">End</label>
                  <input type="range" id="banner_video_end_ui" min="0" max="0" step="0.1" value="<?php echo esc_attr($banner_video_end); ?>">
                  <span class="ts-pill" id="banner_video_end_val">0:00</span>
                  <button type="button" class="button" id="banner_video_set_end_now">ใช้เวลาปัจจุบัน</button>
                </div>
              </div>
              <input type="hidden" name="banner_video_start" id="banner_video_start" value="<?php echo esc_attr($banner_video_start); ?>">
              <input type="hidden" name="banner_video_end" id="banner_video_end" value="<?php echo esc_attr($banner_video_end); ?>">
              <span class="hint">กำหนดช่วงวิดีโอที่ต้องการเล่นใน Hero ได้ด้วย Slider และปุ่มเลือกจากเวลาปัจจุบัน</span>
            </div>
          </div>
        </div>
        <!-- ═══ END TAB APPEARANCE ═══ -->

        <!-- ═══════════ TAB: CONTACT ═══════════ -->
        <div class="ts-panel" id="tab-contact">
          <div class="ts-card">
            <div class="ts-card-title">📞 ข้อมูลติดต่อ</div>
            <div class="ts-card-desc">ข้อมูลที่แสดงในหน้า Contact, Header, Footer</div>

            <div class="ts-sec-head">เบอร์โทร & อีเมล</div>
            <div class="ts-form-grid">
              <div class="ts-field">
                <label for="site_phone">เบอร์โทรศัพท์</label>
                <input type="tel" id="site_phone" name="site_phone" value="<?php echo esc_attr(get_option('site_phone', get_theme_mod('site_phone', ''))); ?>" placeholder="+66 2 108 8521">
              </div>
              <div class="ts-field">
                <label for="site_fax">แฟกซ์</label>
                <input type="tel" id="site_fax" name="site_fax" value="<?php echo esc_attr(get_option('site_fax', get_theme_mod('site_fax', ''))); ?>" placeholder="+66 2 xxx xxxx">
              </div>
              <div class="ts-field">
                <label for="site_email">อีเมลทั่วไป</label>
                <input type="email" id="site_email" name="site_email" value="<?php echo esc_attr(get_option('site_email', get_theme_mod('site_email', 'info@company.com'))); ?>" placeholder="info@company.com">
                <small class="ts-help">อีเมลนี้ใช้เป็นปลายทางรับข้อความจากฟอร์ม Contact และแสดงในส่วนติดต่อของเว็บไซต์ (Header/Footer/Contact)</small>
              </div>
            </div>

            <div class="ts-sec-head">ส่งอีเมลแบบไม่ใช้ SMTP (Brevo ฟรี)</div>
            <div class="ts-form-grid">
              <div class="ts-field">
                <label for="kv_brevo_api_key">Brevo API Key</label>
                <input type="text" id="kv_brevo_api_key" name="kv_brevo_api_key" value="<?php echo esc_attr(get_option('kv_brevo_api_key', '')); ?>" placeholder="xkeysib-...">
                <small class="ts-help">สมัครฟรีที่ Brevo แล้วสร้าง API Key (Free plan ส่งได้ประมาณ 300 อีเมล/วัน)</small>
              </div>
              <div class="ts-field">
                <label for="kv_brevo_from">From Email (ผู้ส่ง)</label>
                <input type="email" id="kv_brevo_from" name="kv_brevo_from" value="<?php echo esc_attr(get_option('kv_brevo_from', get_option('admin_email', ''))); ?>" placeholder="no-reply@yourdomain.com">
                <small class="ts-help">ควรเป็นอีเมลโดเมนของเว็บไซต์ และต้องยืนยัน Sender ใน Brevo ก่อนใช้งาน</small>
              </div>
              <div class="ts-field">
                <label for="kv_brevo_from_name">From Name (ชื่อผู้ส่ง)</label>
                <input type="text" id="kv_brevo_from_name" name="kv_brevo_from_name" value="<?php echo esc_attr(get_option('kv_brevo_from_name', get_bloginfo('name'))); ?>" placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>">
              </div>
              <div class="ts-field">
                <label for="kv_brevo_test_to">Send to Email (ทดสอบส่งถึง)</label>
                <input type="email" id="kv_brevo_test_to" name="kv_brevo_test_to" value="<?php echo esc_attr(get_option('kv_brevo_test_to', get_option('admin_email', ''))); ?>" placeholder="you@yourdomain.com">
                <small class="ts-help">กดปุ่มเพื่อบันทึกค่าปัจจุบัน แล้วลองส่งอีเมลทดสอบทันที</small>
              </div>
              <div class="ts-field" style="justify-content:flex-end;">
                <label>&nbsp;</label>
                <button type="submit" class="button button-primary" name="kv_brevo_send_test" value="1" style="height:36px;">Send to Email</button>
              </div>
            </div>
            <div class="ts-info">
              เมื่อกรอก Brevo API Key และ From Email ถูกต้อง ระบบจะส่งเมลผ่าน HTTPS API อัตโนมัติ (ไม่ใช้พอร์ต SMTP)
            </div>

            <div class="ts-sec-head">ที่อยู่</div>
            <div class="ts-form-grid">
              <div class="ts-field">
                <label for="site_address">ที่อยู่สั้น (Header/Footer)</label>
                <input type="text" id="site_address" name="site_address" value="<?php echo esc_attr(get_option('site_address', get_theme_mod('site_address', '123 Industrial Zone, Bangkok, Thailand'))); ?>" placeholder="123 Industrial Zone, Bangkok">
              </div>
              <div class="ts-field">
                <label for="site_address_full">ที่อยู่เต็ม (หน้า Contact)</label>
                <textarea id="site_address_full" name="site_address_full"><?php echo esc_textarea(get_option('site_address_full', "123 Industrial Zone\nBangna-Trad Road\nBangkok 10260, Thailand")); ?></textarea>
              </div>
            </div>

            <div class="ts-sec-head">เวลาทำการ</div>
            <div class="ts-form-grid">
              <div class="ts-field">
                <label for="site_hours_weekday">วันจันทร์ – ศุกร์</label>
                <input type="text" id="site_hours_weekday" name="site_hours_weekday" value="<?php echo esc_attr(get_option('site_hours_weekday', 'Monday – Friday: 8:00 AM – 5:00 PM')); ?>" placeholder="8:00 AM – 5:00 PM">
              </div>
              <div class="ts-field">
                <label for="site_hours_weekend">วันเสาร์ – อาทิตย์</label>
                <input type="text" id="site_hours_weekend" name="site_hours_weekend" value="<?php echo esc_attr(get_option('site_hours_weekend', 'Saturday – Sunday: Closed')); ?>" placeholder="Closed">
              </div>
            </div>

            <div class="ts-sec-head">แผนที่ Google Maps</div>
            <div class="ts-form-grid">
              <div class="ts-field">
                <label for="site_map_embed">Google Maps Embed URL</label>
                <input type="url" id="site_map_embed" name="site_map_embed" value="<?php echo esc_attr($map_embed); ?>" placeholder="https://www.google.com/maps/embed?..." inputmode="url" spellcheck="false" autocomplete="off">
                <small class="ts-help">วางลิงก์ Embed URL จาก Google Maps (ไม่ต้องวางโค้ด iframe ทั้งชุด)</small>
              </div>
            </div>
          </div>
        </div>
        <!-- ═══ END TAB CONTACT ═══ -->

        <!-- ═══════════ TAB: NAVBAR ═══════════ -->
        <div class="ts-panel" id="tab-navbar">

          <!-- Live Preview -->
          <div class="ts-card" style="padding:14px 20px;">
            <div class="ts-card-title" style="margin-bottom:10px;">👁️ Live Preview</div>
            <div class="ts-nav-preview">
              <iframe id="nav_preview_iframe" src="<?php echo esc_url(home_url('/')); ?>" title="Navbar Preview"></iframe>
            </div>
            <div style="text-align:right;margin-top:6px;">
              <button type="button" class="button" onclick="document.getElementById('nav_preview_iframe').src=document.getElementById('nav_preview_iframe').src;">🔄 รีเฟรช</button>
            </div>
          </div>

          <!-- Style -->
          <div class="ts-card">
            <div class="ts-card-title">🎨 สไตล์ Navbar</div>
            <div class="ts-card-desc">สี, ขนาดตัวอักษร, การจัดตำแหน่ง</div>

            <div class="ts-form-grid cols-3">
              <div class="ts-field">
                <label>สีพื้นหลัง Navbar</label>
                <input type="color" name="nav_bg_color" value="<?php echo esc_attr($nav_bg_color ?: '#ffffff'); ?>">
              </div>
              <div class="ts-field">
                <label>สีตัวอักษร</label>
                <input type="color" name="nav_text_color" value="<?php echo esc_attr($nav_text_color ?: '#1e293b'); ?>">
              </div>
              <div class="ts-field">
                <label>สี Hover</label>
                <input type="color" name="nav_hover_color" value="<?php echo esc_attr($nav_hover_color ?: '#1a56db'); ?>">
              </div>
              <div class="ts-field">
                <label>สี Active</label>
                <input type="color" name="nav_active_color" value="<?php echo esc_attr($nav_active_color ?: '#1a56db'); ?>">
              </div>
              <div class="ts-field">
                <label>ขนาดตัวอักษร (px)</label>
                <input type="number" name="nav_font_size" value="<?php echo (int)$nav_font_size ?: 15; ?>" min="11" max="24">
              </div>
              <div class="ts-field">
                <label>น้ำหนักตัวอักษร</label>
                <select name="nav_font_weight">
                  <?php foreach(['400'=>'Normal','500'=>'Medium','600'=>'Semi-bold','700'=>'Bold'] as $v=>$l): ?>
                  <option value="<?php echo $v ?>" <?php selected((string)$nav_font_weight, $v); ?>><?php echo $l ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="ts-field">
                <label>การจัดตำแหน่งเมนู</label>
                <select name="nav_align">
                  <option value="left" <?php selected($nav_align,'left'); ?>>Left</option>
                  <option value="center" <?php selected($nav_align,'center'); ?>>Center</option>
                  <option value="right" <?php selected($nav_align,'right'); ?>>Right</option>
                </select>
              </div>
              <div class="ts-field">
                <label>Padding แนวตั้ง (px)</label>
                <input type="number" name="nav_padding_y" value="<?php echo (int)$nav_padding_y ?: 16; ?>" min="0" max="40">
              </div>
            </div>

            <div class="ts-divider"></div>

            <div style="display:flex;gap:40px;">
              <div class="ts-toggle-row" style="flex:1;border-bottom:none;">
                <div><div class="t-lbl">Sticky Navbar</div><div class="t-sub">ติดด้านบนเมื่อเลื่อน</div></div>
                <label class="ts-switch">
                  <input type="hidden" name="nav_sticky" value="0">
                  <input type="checkbox" name="nav_sticky" value="1" <?php checked($nav_sticky, '1'); ?>>
                  <span class="slider"></span>
                </label>
              </div>
              <div class="ts-toggle-row" style="flex:1;border-bottom:none;">
                <div><div class="t-lbl">Box Shadow</div><div class="t-sub">เงาใต้ Navbar</div></div>
                <label class="ts-switch">
                  <input type="hidden" name="nav_shadow" value="0">
                  <input type="checkbox" name="nav_shadow" value="1" <?php checked($nav_shadow, '1'); ?>>
                  <span class="slider"></span>
                </label>
              </div>
            </div>

            <div class="ts-sec-head">โลโก้</div>
            <div class="ts-form-grid">
              <div class="ts-field">
                <label for="nav_logo_alt">Alt Text</label>
                <input type="text" id="nav_logo_alt" name="nav_logo_alt" value="<?php echo esc_attr($nav_logo_alt); ?>" placeholder="Company Logo">
              </div>
              <div class="ts-field">
                <label for="nav_logo_height">ความสูงโลโก้ (px)</label>
                <input type="number" id="nav_logo_height" name="nav_logo_height" value="<?php echo (int)$nav_logo_height ?: 48; ?>" min="20" max="120" step="2">
              </div>
            </div>
          </div>

          <!-- Menu Items -->
          <div class="ts-card">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:6px;">
              <div class="ts-card-title" style="margin-bottom:0;">📋 รายการเมนู</div>
              <span id="nmb-save-status" style="font-size:11.5px;color:var(--ts-muted);"></span>
            </div>
            <div class="ts-card-desc">เลือกเมนูที่ต้องการแสดง และกำหนดข้อความ/URL — เปลี่ยนแปลงจะบันทึกอัตโนมัติ</div>

            <?php
            /* ── Load / build nav items ──────────────────────────────────── */
            $_auto_types = ['home','products'];
            $_default_items = [
              ['id'=>'home',     'label'=>$nav_home_label,     'url'=>'',               'type'=>'home',     'visible'=>($nav_home_vis==='1'),     'new_tab'=>false],
              ['id'=>'about',    'label'=>$nav_about_label,    'url'=>$nav_about_url_val,  'type'=>'custom',   'visible'=>($nav_about_vis==='1'),    'new_tab'=>false],
              ['id'=>'products', 'label'=>$nav_products_label, 'url'=>'',               'type'=>'products', 'visible'=>($nav_products_vis==='1'), 'new_tab'=>false],
              ['id'=>'contact',  'label'=>$nav_contact_label,  'url'=>$nav_contact_url_val,'type'=>'custom',   'visible'=>($nav_contact_vis==='1'),  'new_tab'=>false],
            ];
            $_nav_items = [];
            if ($nav_menu_items_json_raw) {
              $_decoded = json_decode($nav_menu_items_json_raw, true);
              if (is_array($_decoded) && count($_decoded) > 0) $_nav_items = $_decoded;
            }
            if (empty($_nav_items)) $_nav_items = $_default_items;
            $_items_json_val = esc_attr(json_encode($_nav_items, JSON_UNESCAPED_UNICODE));
            ?>
            <input type="hidden" id="nav_menu_items_json" name="nav_menu_items_json" value="<?php echo $_items_json_val; ?>">

            <ul id="nav-menu-builder">
            <?php foreach ($_nav_items as $_idx => $_item):
              $_type     = $_item['type'] ?? 'custom';
              $_is_auto  = in_array($_type, $_auto_types);
              $_visible  = !empty($_item['visible']);
              $_new_tab  = !empty($_item['new_tab']);
              $_lbl      = esc_attr($_item['label'] ?? '');
              $_url      = esc_attr($_item['url'] ?? '');
              $_badge    = ($_type==='home') ? 'หน้าแรก' : (($_type==='products') ? 'สินค้า' : '');
            ?>
            <li class="nmb-row" draggable="true" data-idx="<?php echo $_idx; ?>" data-id="<?php echo esc_attr($_item['id'] ?? ''); ?>" data-type="<?php echo esc_attr($_type); ?>">
              <span class="nmb-drag" title="ลาก-วางเพื่อเรียงลำดับ">⠿</span>
              <input type="checkbox" class="nmb-vis" style="width:15px;height:15px;flex-shrink:0;" <?php echo $_visible ? 'checked' : ''; ?> title="แสดง/ซ่อนเมนูนี้">
              <?php if ($_badge): ?><span class="nmb-type-badge"><?php echo $_badge; ?></span><?php endif; ?>
              <input type="text" class="nmb-lbl" value="<?php echo $_lbl; ?>" placeholder="ชื่อเมนู" style="max-width:150px;flex:0 0 150px;">
              <?php if ($_is_auto): ?>
                <span class="nmb-auto-url">🔗 Auto URL</span>
              <?php else: ?>
                <input type="text" class="nmb-url" value="<?php echo $_url; ?>" placeholder="URL (เช่น /about/)">
              <?php endif; ?>
              <label class="nmb-newtab" title="เปิดในแท็บใหม่">
                <input type="checkbox" class="nmb-tab" <?php echo $_new_tab ? 'checked' : ''; ?>>
                New Tab
              </label>
              <button type="button" class="nmb-del" title="ลบรายการนี้">🗑</button>
            </li>
            <?php endforeach; ?>
            </ul>
            <div style="display:flex;align-items:center;gap:10px;margin-top:10px;flex-wrap:wrap;">
              <button type="button" class="nmb-add-btn" id="nmb-add">＋ เพิ่มรายการเมนู</button>
              <button type="button" id="nmb-save-btn" style="padding:7px 18px;background:var(--ts-primary);color:#fff;border:none;border-radius:7px;font-size:12.5px;font-weight:600;cursor:pointer;">💾 บันทึกเมนู</button>
              <button type="button" id="nmb-flush-btn" style="padding:7px 18px;background:#64748b;color:#fff;border:none;border-radius:7px;font-size:12.5px;font-weight:600;cursor:pointer;">🔄 ล้างแคช</button>
            </div>

            <div class="ts-sec-head">เมนูเพิ่มเติม / Dropdown</div>
            <div class="ts-field">
              <label for="nav_custom_items">Custom Menu Items</label>
              <textarea id="nav_custom_items" name="nav_custom_items" rows="6" style="font-family:monospace;font-size:12px;" placeholder="Blog|/blog/&#10;Resources|#&#10;  Documentation|/docs/&#10;  Downloads|/downloads/"><?php echo esc_textarea($nav_custom_items); ?></textarea>
              <span class="hint">รูปแบบ: ชื่อเมนู|URL · เว้น 2 space นำหน้า = Submenu</span>
            </div>

            <div class="ts-sec-head">ปุ่ม CTA (ขวาสุด)</div>
            <div style="margin-bottom:12px;">
              <div class="ts-toggle-row" style="border-bottom:none;padding:4px 0;">
                <div class="t-lbl">แสดงปุ่ม CTA</div>
                <label class="ts-switch">
                  <input type="hidden" name="nav_cta_visible" value="0">
                  <input type="checkbox" name="nav_cta_visible" value="1" <?php checked($nav_cta_vis, '1'); ?>>
                  <span class="slider"></span>
                </label>
              </div>
            </div>
            <div class="ts-form-grid cols-3">
              <div class="ts-field">
                <label>ข้อความปุ่ม</label>
                <input type="text" name="nav_cta_text" value="<?php echo esc_attr($nav_cta_text_val); ?>" placeholder="ว่าง = เบอร์โทร">
              </div>
              <div class="ts-field">
                <label>URL ปุ่ม</label>
                <input type="text" name="nav_cta_url" value="<?php echo esc_attr($nav_cta_url_val); ?>" placeholder="/contact/">
              </div>
              <div class="ts-field">
                <label>สีปุ่ม</label>
                <input type="color" name="nav_cta_bg" value="<?php echo esc_attr($nav_cta_bg ?: $theme_primary); ?>">
              </div>
              <div class="ts-field">
                <label>สีตัวอักษร</label>
                <input type="color" name="nav_cta_text_color" value="<?php echo esc_attr($nav_cta_text_clr ?: '#ffffff'); ?>">
              </div>
              <div class="ts-field">
                <label>ขนาดฟอนต์ (px)</label>
                <input type="number" name="nav_cta_font_size" value="<?php echo (int)$nav_cta_font_size ?: 14; ?>" min="10" max="22">
              </div>
              <div class="ts-field">
                <label>มุมโค้ง (px)</label>
                <input type="number" name="nav_cta_radius" value="<?php echo (int)$nav_cta_radius ?: 6; ?>" min="0" max="30">
              </div>
            </div>
          </div>
        </div>
        <!-- ═══ END TAB NAVBAR ═══ -->

        <!-- ═══════════ TAB: ABOUT ═══════════ -->
        <div class="ts-panel" id="tab-about">

          <!-- Redirect notice -->
          <div class="ts-card" style="border:2px dashed #93c5fd;background:#eff6ff;">
            <div class="ts-card-title" style="color:#1e40af;">📝 แก้ไขหน้า About Us ผ่าน Page Builder</div>
            <div class="ts-card-desc" style="font-size:13.5px;line-height:1.8;color:#1e3a5f;">
              หน้า <strong>About Us</strong> ถูกออกแบบให้แก้ไขผ่าน <strong>Block Editor (Gutenberg)</strong> โดยตรง — ไม่ต้องตั้งค่าที่นี่<br>
              คลิกปุ่มด้านล่างเพื่อเปิดหน้าแก้ไขได้เลย
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px;">
              <?php $about_page = get_page_by_path('about'); if ($about_page): ?>
              <a href="<?php echo esc_url(admin_url('post.php?post=' . $about_page->ID . '&action=edit')); ?>" class="ts-save-btn" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                ✏️ เปิด Block Editor (About Us)
              </a>
              <a href="<?php echo esc_url(get_permalink($about_page->ID)); ?>" target="_blank" class="be-bulk-btn" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                👁️ ดูหน้าจริง
              </a>
              <?php else: ?>
              <a href="<?php echo esc_url(admin_url('post-new.php?post_type=page')); ?>" class="ts-save-btn" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                ➕ สร้างหน้า About Us ใหม่
              </a>
              <?php endif; ?>
              <a href="<?php echo esc_url(admin_url('site-editor.php?path=%2Fpages')); ?>" class="be-bulk-btn" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                🏗️ Site Editor
              </a>
            </div>
          </div>

        </div>
        <!-- ═══ END TAB ABOUT ═══ -->

        <!-- ═══════════ TAB: CHAT (AI Search) ═══════════ -->
        <div class="ts-panel" id="tab-chat">

          <!-- RAG AI -->
          <div class="ts-card">
            <div class="ts-card-title">🤖 AI Search Chat (RAG)</div>
            <div class="ts-card-desc">ปุ่มค้นหาอัจฉริยะมุมขวาล่าง — ค้นหาสินค้า, สเปค, ข้อมูลบริษัท ฯลฯ</div>
            <div class="ts-toggle-row">
              <div><div class="t-lbl">เปิดใช้งาน RAG AI Chat</div><div class="t-sub">แสดงปุ่ม ❓ ที่มุมขวาล่างทุกหน้า</div></div>
              <label class="ts-switch">
                <input type="hidden" name="rag_chat_enabled" value="0">
                <input type="checkbox" name="rag_chat_enabled" value="1" <?php checked($rag_chat_enabled, '1'); ?>>
                <span class="slider"></span>
              </label>
            </div>
            <div class="ts-info">
              💡 รองรับค้นหาภาษาไทยและอังกฤษ พร้อมเปลี่ยนภาษาได้<br>
              📊 ข้อมูลจะถูกสร้างดัชนีอัตโนมัติจากสินค้า, หมวดหมู่, ข้อมูลบริษัท
            </div>
          </div>

          <!-- Deprecated Notice -->
          <div class="ts-card" style="background:#fef3c7;border-color:#f59e0b;">
            <div class="ts-card-title" style="color:#92400e;">📌 หมายเหตุ</div>
            <div class="ts-card-desc" style="color:#78350f;">
              <strong>ปุ่มแชท LINE / WeChat / WhatsApp</strong> และ <strong>Social Media Links</strong> ได้ย้ายไปแก้ไขโดยตรงใน Block Editor แล้ว<br>
              👉 ไปที่ <strong>Appearance → Editor → หน้า Contacts</strong> แล้วแก้ไขบล็อก Social Icons ได้เลย
            </div>
          </div>
        </div>
        <!-- ═══ END TAB CHAT ═══ -->

        <!-- ═══════════ TAB: FOOTER ═══════════ -->
        <div class="ts-panel" id="tab-footer">

          <div class="ts-card" style="border-left:4px solid #0ea5e9;background:#f0f9ff;padding-bottom:12px;">
            <div class="ts-card-desc" style="font-size:12.5px;color:#0369a1;">
              💡 Footer ใช้ Bootstrap HTML + shortcodes — แก้ไขได้ที่นี่โดยตรง บันทึกแล้วเว็บ Site Editor จะไม่แสดงผลได้ preview เพราะ shortcode ต้อง PHP render
            </div>
          </div>

          <div class="ts-card">
            <div class="ts-card-title">📄 Footer Content</div>
            <div class="ts-card-desc">เนื้อหาที่แสดงใน Footer ของเว็บไซต์</div>

            <div class="ts-sec-head">คอลัมน์ About</div>
            <div class="ts-field">
              <label for="footer_about_text">ข้อความแนะนำบริษัท <span class="hint">(shortcode: [footer_about])</span></label>
              <textarea id="footer_about_text" name="footer_about_text" rows="4"><?php echo esc_textarea($footer_about_text); ?></textarea>
            </div>

            <div class="ts-sec-head">Quick Links</div>
            <div class="ts-field">
              <label for="footer_quick_links">รายการลิงก์ด่วน</label>
              <textarea id="footer_quick_links" name="footer_quick_links" rows="6" style="font-family:monospace;font-size:12px;"><?php echo esc_textarea($footer_quick_links); ?></textarea>
              <span class="hint">รูปแบบ: ชื่อลิงก์|URL · หนึ่งแถวต่อหนึ่งลิงก์ · ตัวอย่าง: About Us|/about/</span>
            </div>
          </div>

          <div class="ts-save-bar">
            <button type="submit" class="ts-save-btn">💾 บันทึก Footer</button>
          </div>
        </div>
        <!-- ═══ END TAB FOOTER ═══ -->

        <!-- ═══════════ TAB: GALLERY ═══════════ -->
        <div class="ts-panel" id="tab-gallery">
          <div class="ts-card">
            <div class="ts-card-title">🖼️ แกลเลอรีรูปภาพสินค้า</div>
            <div class="ts-card-desc">ตั้งค่าการแสดงรูปภาพในหน้าสินค้า</div>

            <div class="ts-field" style="max-width:300px;">
              <label for="gallery_interval_sec">เวลาเปลี่ยนภาพ</label>
              <div style="display:flex;align-items:center;gap:8px;">
                <input type="number" id="gallery_interval_sec" min="1" max="30" step="0.5"
                  value="<?php echo esc_attr($gallery_interval / 1000); ?>"
                  style="width:80px;"
                  oninput="document.getElementById('gallery_interval').value=Math.round(this.value*1000)">
                <input type="hidden" id="gallery_interval" name="gallery_interval" value="<?php echo esc_attr($gallery_interval); ?>">
                <span style="color:var(--ts-muted);font-size:13px;">วินาที</span>
              </div>
              <span class="hint">ช่วง 1–30 วินาที · ค่าเริ่มต้น 5 วินาที</span>
            </div>

            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:14px;">
              <?php foreach([2=>'2 วิ',3=>'3 วิ',5=>'5 วิ (default)',7=>'7 วิ',10=>'10 วิ',15=>'15 วิ'] as $sec=>$lbl): ?>
              <button type="button" onclick="document.getElementById('gallery_interval_sec').value=<?php echo $sec?>;document.getElementById('gallery_interval').value=<?php echo $sec*1000?>;" style="background:#f1f5f9;border:1px solid var(--ts-border);border-radius:6px;padding:5px 14px;cursor:pointer;font-size:12px;"><?php echo esc_html($lbl); ?></button>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <!-- ═══ END TAB GALLERY ═══ -->

      </form>

      <!-- ═══════════ TAB: BLOCK EDITOR ═══════════ -->
      <div class="ts-panel" id="tab-blockeditor">

        <!-- Save Bar -->
        <div class="be-save-float" style="position:relative;margin:0 0 16px;border-top:none;border-bottom:1px solid var(--ts-border);padding:0 0 12px;">
          <div class="be-summary-bar">
            <span class="be-badge green" id="be-badge-blocks">🧩 —</span>
            <span class="be-badge red" id="be-badge-disabled">🚫 —</span>
            <span class="be-badge blue" id="be-badge-patterns">🎨 —</span>
          </div>
          <div style="display:flex;gap:8px;align-items:center;">
            <span id="be-save-status" style="font-size:12px;color:var(--ts-muted);"></span>
            <button type="button" class="be-bulk-btn" id="be-unlock-all-btn" style="padding:6px 12px;">🔓 เปิดประตูทุกบาน</button>
            <button type="button" class="ts-save-btn" id="be-save-btn" style="font-size:12px;padding:6px 16px;">💾 บันทึก Block Editor</button>
          </div>
        </div>

        <!-- ── General Settings ── -->
        <div class="ts-card">
          <div class="ts-card-title">⚙️ ตั้งค่า Editor ทั่วไป</div>
          <div class="ts-card-desc">เปิด/ปิดฟีเจอร์ต่างๆ ของ Block Editor</div>
          <?php
          $be_toggle_defs = [
            ['freedom_mode',            'โหมดอิสระ 100%',          'ปลดล็อกทุกบล็อก/แพทเทิร์น และปิดข้อจำกัดทั้งหมด', false],
            ['default_fullscreen',      'Fullscreen Mode เริ่มต้น', 'เปิด Editor แบบเต็มจอทุกครั้ง', true],
            ['disable_code_editor',     'ปิด Code Editor',         'ไม่อนุญาตให้สลับไปโหมดแก้ไข HTML', false],
            ['disable_block_directory', 'ปิด Block Directory',     'ไม่แสดงปุ่มติดตั้ง Block เพิ่มจาก WordPress.org', false],
            ['disable_openverse',       'ปิด Openverse',           'ซ่อนแท็บค้นหาภาพ Openverse ในไลบรารีสื่อ', false],
            ['disable_remote_patterns', 'ปิด Remote Patterns',     'ไม่โหลด Patterns จาก WordPress.org', false],
            ['disable_font_library',    'ปิด Font Library',        'ไม่แสดง Font Library ใน Editor', false],
            ['disable_drop_cap',        'ปิด Drop Cap',            'ไม่อนุญาตใช้ Drop Cap ในย่อหน้า', false],
            ['lock_blocks',             'ล็อค Block ทั้งหมด',       'ไม่อนุญาตล็อค/ปลดล็อค Blocks', false],
          ];
          foreach ($be_toggle_defs as [$key, $label, $desc, $inverse]):
            $val = $be_toggle_map[$key] ?? '0';
            $checked = $inverse ? ($val === '1' ? 'checked' : '') : ($val === '1' ? 'checked' : '');
          ?>
          <div class="ts-toggle-row">
            <div><div class="t-lbl"><?php echo esc_html($label); ?></div><div class="t-sub"><?php echo esc_html($desc); ?></div></div>
            <label class="ts-switch">
              <input type="checkbox" data-be-toggle="<?php echo esc_attr($key); ?>" <?php echo $checked; ?>>
              <span class="slider"></span>
            </label>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- ── Block Visibility ── -->
        <div class="ts-card">
          <div class="ts-card-title">🧩 Block Visibility</div>
          <div class="ts-card-desc">เลือก Block ที่ต้องการให้ใช้งานได้ใน Editor — ปิด Block ที่ไม่ต้องการเพื่อลดความซับซ้อน</div>
          <div class="be-toolbar">
            <input type="text" id="be-block-search" placeholder="🔍 ค้นหา Block...">
            <button type="button" class="be-bulk-btn" onclick="beToggleAllBlocks(true)">✅ เปิดทั้งหมด</button>
            <button type="button" class="be-bulk-btn" onclick="beToggleAllBlocks(false)">❌ ปิดทั้งหมด</button>
          </div>
          <div class="be-cat-pills" id="be-cat-pills"></div>
          <div class="be-block-count" id="be-block-count"></div>
          <div class="be-block-grid" id="be-block-grid"></div>
        </div>

        <!-- ── Pattern Visibility ── -->
        <div class="ts-card">
          <div class="ts-card-title">🎨 Pattern Visibility</div>
          <div class="ts-card-desc">เลือก Pattern ที่ต้องการให้แสดงในตัวเลือก Block Editor</div>
          <div class="be-toolbar">
            <input type="text" id="be-pattern-search" placeholder="🔍 ค้นหา Pattern...">
            <button type="button" class="be-bulk-btn" onclick="beToggleAllPatterns(true)">✅ เปิดทั้งหมด</button>
            <button type="button" class="be-bulk-btn" onclick="beToggleAllPatterns(false)">❌ ปิดทั้งหมด</button>
          </div>
          <div id="be-pattern-list" style="max-height:400px;overflow-y:auto;"></div>
        </div>

        <!-- ── Color Palette ── -->
        <div class="ts-card">
          <div class="ts-card-title">🎨 Color Palette</div>
          <div class="ts-card-desc">จัดการ Color Palette ที่แสดงใน Block Editor — สีเหล่านี้ผู้ใช้เลือกได้เมื่อตั้งค่า Block</div>
          <div id="be-colors-list"></div>
          <div style="margin-top:10px;">
            <button type="button" class="be-add-btn" onclick="beAddColor()">＋ เพิ่มสี</button>
            <button type="button" class="be-reset-btn" onclick="beResetColors()">🔄 คืนค่า theme.json</button>
          </div>
        </div>

        <!-- ── Typography ── -->
        <div class="ts-card">
          <div class="ts-card-title">📝 Font Sizes</div>
          <div class="ts-card-desc">จัดการขนาดตัวอักษรที่แสดงเป็นตัวเลือกใน Block Editor</div>
          <div id="be-sizes-list"></div>
          <div style="margin-top:10px;">
            <button type="button" class="be-add-btn" onclick="beAddSize()">＋ เพิ่มขนาดฟอนต์</button>
            <button type="button" class="be-reset-btn" onclick="beResetSizes()">🔄 คืนค่า theme.json</button>
          </div>
        </div>

        <!-- ── Layout ── -->
        <div class="ts-card">
          <div class="ts-card-title">📐 Layout</div>
          <div class="ts-card-desc">กำหนดความกว้าง Content และ Wide alignment</div>
          <div class="ts-form-grid">
            <div class="ts-field">
              <label>Content Width</label>
              <input type="text" id="be-content-width" value="<?php echo esc_attr($be_eff_cw); ?>" placeholder="1140px">
              <span class="hint">theme.json default: <?php echo esc_html($be_def_cw); ?></span>
            </div>
            <div class="ts-field">
              <label>Wide Width</label>
              <input type="text" id="be-wide-width" value="<?php echo esc_attr($be_eff_ww); ?>" placeholder="1320px">
              <span class="hint">theme.json default: <?php echo esc_html($be_def_ww); ?></span>
            </div>
          </div>
        </div>

        <!-- ── Spacing Units ── -->
        <div class="ts-card">
          <div class="ts-card-title">📏 Spacing Units</div>
          <div class="ts-card-desc">เลือกหน่วยวัดที่อนุญาตใน Block Editor</div>
          <div class="be-unit-checks" id="be-unit-checks">
            <?php
            $all_units = ['px','em','rem','%','vh','vw','svh','svw','dvh','dvw'];
            foreach ($all_units as $u):
              $active = in_array($u, $be_eff_units) ? ' active' : '';
            ?>
            <div class="be-unit-check<?php echo $active; ?>" data-unit="<?php echo esc_attr($u); ?>" onclick="beToggleUnit(this)">
              <strong><?php echo esc_html($u); ?></strong>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- ── Custom Editor CSS ── -->
        <div class="ts-card">
          <div class="ts-card-title">💻 Custom Editor CSS</div>
          <div class="ts-card-desc">CSS ที่จะถูก inject เข้าไปใน Block Editor — มีผลเฉพาะภายใน Editor เท่านั้น</div>
          <textarea class="be-editor-css" id="be-editor-css" placeholder="/* เช่น */\n.editor-styles-wrapper { font-size: 16px; }"><?php echo esc_textarea($be_editor_css); ?></textarea>
        </div>

        <!-- Sticky save bar (bottom) -->
        <div class="be-save-float">
          <div style="font-size:12px;color:var(--ts-muted);">
            💡 การเปลี่ยนแปลงจะมีผลหลังบันทึก — เปิดหน้า Editor ใหม่เพื่อดูผลลัพธ์
          </div>
          <button type="button" class="ts-save-btn" onclick="beSaveAll()" style="font-size:12px;padding:6px 16px;">💾 บันทึก Block Editor</button>
        </div>

        <!-- ── Block Editor Guide ── -->
        <div style="border-top:2px solid #e2e8f0;margin-top:24px;padding-top:8px;">
          <?php kv_block_editor_guide_content(); ?>
        </div>

      </div>
      <!-- ═══ END TAB BLOCK EDITOR ═══ -->

      <!-- Block Editor data (JSON for JS) -->
      <script>
      window._beAllBlocks      = <?php echo wp_json_encode($be_all_blocks); ?>;
      window._beAllPatterns    = <?php echo wp_json_encode($be_all_patterns); ?>;
      window._beDisabledBlocks = <?php echo wp_json_encode($be_disabled_blocks); ?>;
      window._beDisabledPats   = <?php echo wp_json_encode($be_disabled_patterns); ?>;
      window._beDefColors      = <?php echo wp_json_encode($be_def_colors); ?>;
      window._beDefSizes       = <?php echo wp_json_encode($be_def_sizes); ?>;
      window._beEffColors      = <?php echo wp_json_encode($be_eff_colors); ?>;
      window._beEffSizes       = <?php echo wp_json_encode($be_eff_sizes); ?>;
      </script>

    </div><!-- /.ts-content -->
  </div><!-- /.ts-body -->
</div><!-- /.ts-wrap -->

<script>
(function(){
'use strict';

/* ── Tab Switching ─────────────────────────── */
const navItems = document.querySelectorAll('.ts-nav-item[data-tab]');
const panels   = document.querySelectorAll('.ts-panel');

function activateTab(target, updateUrl) {
  const btn = document.querySelector('.ts-nav-item[data-tab="' + target + '"]');
  if (!btn) return false;

  navItems.forEach(b => b.classList.toggle('active', b === btn));
  panels.forEach(p => p.classList.toggle('active', p.id === 'tab-' + target));

  if (updateUrl) {
    const url = new URL(window.location.href);
    url.searchParams.set('tab', target);
    window.history.replaceState({}, '', url.toString());
  }
  return true;
}

navItems.forEach(btn => {
  btn.addEventListener('click', () => {
    activateTab(btn.dataset.tab, true);
  });
});

const initialTab = new URLSearchParams(window.location.search).get('tab');
if (initialTab) {
  activateTab(initialTab, false);
}

/* ── Color Picker ↔ Hex ↔ Swatches ───────── */
function syncColorPair(colorId, hexId, prevDotId, prevValId) {
  const cp  = document.getElementById(colorId);
  const hex = document.getElementById(hexId);
  if (!cp || !hex) return;

  function update(val) {
    const dot = document.getElementById(prevDotId);
    const lbl = document.getElementById(prevValId);
    if (dot) dot.style.background = val;
    if (lbl) lbl.textContent = val;
    // Update CSS var for live feel
    if (colorId === 'theme_primary_color') {
      document.documentElement.style.setProperty('--ts-primary', val);
      const bcp = document.getElementById('banner_bg_color');
      const bhx = document.getElementById('banner_bg_hex');
      const bhd = document.getElementById('banner_bg_color_hidden');
      if (bcp) bcp.value = val;
      if (bhx) bhx.value = val;
      if (bhd) bhd.value = val;
    }
  }

  cp.addEventListener('input', () => { hex.value = cp.value; update(cp.value); });
  hex.addEventListener('input', () => {
    if (/^#[0-9a-fA-F]{6}$/.test(hex.value)) { cp.value = hex.value; update(hex.value); }
  });
}

syncColorPair('theme_bg_color','theme_bg_hex','prev-bg','prev-bg-val');
syncColorPair('theme_primary_color','theme_primary_hex','prev-primary','prev-primary-val');
syncColorPair('theme_accent_color','theme_accent_hex','prev-accent','prev-accent-val');

/* ── Swatches ──────────────────────────────── */
document.querySelectorAll('.swatch').forEach(s => {
  s.addEventListener('click', () => {
    const tgt = s.dataset.target;
    const col = s.title;
    const cp  = document.getElementById(tgt);
    const hexMap = { theme_bg_color:'theme_bg_hex', theme_primary_color:'theme_primary_hex', theme_accent_color:'theme_accent_hex' };
    const hexInput = document.getElementById(hexMap[tgt]);
    if (cp) { cp.value = col; cp.dispatchEvent(new Event('input')); }
    if (hexInput) { hexInput.value = col; }
  });
});

/* ── WP Media Pickers ──────────────────────── */
function wpPick(inputId, previewId) {
  if (typeof wp === 'undefined' || !wp.media) return alert('WP Media Library not available.');
  const frame = wp.media({ title: 'เลือกรูป', multiple: false });
  frame.on('select', function() {
    const att = frame.state().get('selection').first().toJSON();
    const inp = document.getElementById(inputId);
    const prv = document.getElementById(previewId);
    if (inp) inp.value = att.url;
    if (prv) { prv.src = att.url; prv.style.display = ''; }
  });
  frame.open();
}

function wpPickVideo(inputId, previewId) {
  if (typeof wp === 'undefined' || !wp.media) return alert('WP Media Library not available.');
  const frame = wp.media({
    title: 'เลือกวิดีโอ',
    multiple: false,
    library: { type: 'video' }
  });
  frame.on('select', function() {
    const att = frame.state().get('selection').first().toJSON();
    const inp = document.getElementById(inputId);
    const prv = document.getElementById(previewId);
    if (inp) {
      inp.value = att.url;
      inp.dispatchEvent(new Event('change'));
    }
    if (prv) {
      prv.src = att.url;
      const source = prv.querySelector('source');
      if (source) source.src = att.url;
      prv.style.display = '';
      if (typeof prv.load === 'function') prv.load();
    }
  });
  frame.open();
}

const logoPick = document.getElementById('logo_pick_btn');
if (logoPick) logoPick.addEventListener('click', () => wpPick('site_logo_url','logo_preview'));

const logoLightPick = document.getElementById('logo_light_pick_btn');
if (logoLightPick) logoLightPick.addEventListener('click', () => wpPick('site_logo_light_url','logo_light_preview'));

const bannerPick = document.getElementById('banner_pick_btn');
if (bannerPick) bannerPick.addEventListener('click', () => wpPick('banner_bg_image','banner_preview'));

const bannerVideoPick = document.getElementById('banner_video_pick_btn');
if (bannerVideoPick) bannerVideoPick.addEventListener('click', () => wpPickVideo('banner_bg_video','banner_video_preview'));

function fmtTime(seconds) {
  const s = Math.max(0, Number(seconds || 0));
  const mins = Math.floor(s / 60);
  const secs = (s % 60).toFixed(1);
  const secText = Number(secs) < 10 ? '0' + secs : String(secs);
  return mins + ':' + secText;
}

function setupBannerVideoTrimUI() {
  const videoInput = document.getElementById('banner_bg_video');
  const trimVideo = document.getElementById('banner_video_trim_preview');
  const trimSource = trimVideo ? trimVideo.querySelector('source') : null;
  const startUI = document.getElementById('banner_video_start_ui');
  const endUI = document.getElementById('banner_video_end_ui');
  const startHidden = document.getElementById('banner_video_start');
  const endHidden = document.getElementById('banner_video_end');
  const startVal = document.getElementById('banner_video_start_val');
  const endVal = document.getElementById('banner_video_end_val');
  const durationHint = document.getElementById('banner_video_duration_hint');
  const setStartNow = document.getElementById('banner_video_set_start_now');
  const setEndNow = document.getElementById('banner_video_set_end_now');
  if (!videoInput || !trimVideo || !startUI || !endUI || !startHidden || !endHidden || !startVal || !endVal) return;

  let duration = 0;

  function syncText() {
    startVal.textContent = fmtTime(startUI.value);
    endVal.textContent = fmtTime(endUI.value);
  }

  function syncHiddenAndBounds(changed) {
    let start = parseFloat(startUI.value || '0');
    let end = parseFloat(endUI.value || '0');
    if (!isFinite(start)) start = 0;
    if (!isFinite(end)) end = 0;

    if (duration > 0) {
      start = Math.min(Math.max(start, 0), Math.max(0, duration - 0.1));
      end = Math.min(Math.max(end, 0), duration);
      if (end <= 0) end = duration;
      if (changed === 'start' && start >= end) end = Math.min(duration, start + 0.1);
      if (changed === 'end' && end <= start) start = Math.max(0, end - 0.1);
      if (start >= end) {
        start = 0;
        end = duration;
      }
    }

    startUI.value = start.toFixed(1);
    endUI.value = end.toFixed(1);
    startHidden.value = start.toFixed(1);
    endHidden.value = end.toFixed(1);
    syncText();
  }

  function applyDuration() {
    duration = Number(trimVideo.duration || 0);
    if (!isFinite(duration) || duration <= 0) duration = 0;

    const max = duration > 0 ? duration.toFixed(1) : '0';
    startUI.max = max;
    endUI.max = max;

    if (duration > 0) {
      if (durationHint) durationHint.textContent = 'ความยาววิดีโอ: ' + fmtTime(duration) + ' — เลือกช่วง Start/End ที่ต้องการแสดง';
      const initialStart = parseFloat(startHidden.value || '0');
      const initialEnd = parseFloat(endHidden.value || '0');
      startUI.value = isFinite(initialStart) ? initialStart : 0;
      endUI.value = (isFinite(initialEnd) && initialEnd > 0) ? initialEnd : duration;
      syncHiddenAndBounds();
    } else {
      if (durationHint) durationHint.textContent = 'เลือกวิดีโอเพื่อกำหนดช่วงเวลา Start/End';
      startUI.value = '0';
      endUI.value = '0';
      startHidden.value = '0';
      endHidden.value = '0';
      syncText();
    }
  }

  function loadVideo(url) {
    if (!url) {
      trimVideo.style.display = 'none';
      applyDuration();
      return;
    }
    trimVideo.style.display = '';
    if (trimSource) trimSource.src = url;
    trimVideo.src = url;
    if (typeof trimVideo.load === 'function') trimVideo.load();
  }

  startUI.addEventListener('input', function() { syncHiddenAndBounds('start'); });
  endUI.addEventListener('input', function() { syncHiddenAndBounds('end'); });

  if (setStartNow) {
    setStartNow.addEventListener('click', function() {
      if (duration <= 0) return;
      startUI.value = Number(trimVideo.currentTime || 0).toFixed(1);
      syncHiddenAndBounds('start');
    });
  }

  if (setEndNow) {
    setEndNow.addEventListener('click', function() {
      if (duration <= 0) return;
      endUI.value = Number(trimVideo.currentTime || 0).toFixed(1);
      syncHiddenAndBounds('end');
    });
  }

  trimVideo.addEventListener('loadedmetadata', applyDuration);
  videoInput.addEventListener('change', function() {
    loadVideo(videoInput.value.trim());
  });

  loadVideo(videoInput.value.trim());
}

setupBannerVideoTrimUI();

const wechatQrBtn = document.getElementById('wechat_qr_btn');
if (wechatQrBtn) wechatQrBtn.addEventListener('click', () => wpPick('chat_wechat_qr_url','wechat_qr_preview'));

/* ── Global helper for About image pickers ── */
window.aboutPickImage = function(fieldId, previewId) { wpPick(fieldId, previewId); };

/* ── Toast Notifications ───────────────────── */
const toastContainer = document.getElementById('ts-toast');
function showToast(msg, type = '') {
  const el = document.createElement('div');
  el.className = 'ts-toast-msg' + (type ? ' ' + type : '');
  el.textContent = msg;
  toastContainer.appendChild(el);
  requestAnimationFrame(() => { requestAnimationFrame(() => el.classList.add('show')); });
  setTimeout(() => {
    el.classList.remove('show');
    setTimeout(() => el.remove(), 300);
  }, 3000);
}

/* ── AJAX Auto-Save (blur on inputs) ──────── */
const nonce   = '<?php echo wp_create_nonce('wp_rest'); ?>';
const apiUrl  = '<?php echo esc_url(rest_url('kv/v1/site-options')); ?>';
const saveStatus = document.getElementById('ts-save-status');
let saveTimer = null;

function collectFormData() {
  const form = document.getElementById('ts-main-form');
  const data = {};
  const fd = new FormData(form);
  fd.forEach((val, key) => {
    // Skip WP nonce fields — not needed for REST
    if (key === 'kv_theme_nonce' || key === '_wp_http_referer') return;
    data[key] = val;
  });
  return data;
}

function ajaxSave() {
  if (saveTimer) clearTimeout(saveTimer);
  saveTimer = setTimeout(() => {
    saveStatus.textContent = 'กำลังบันทึก…';
    fetch(apiUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
      body: JSON.stringify(collectFormData())
    })
    .then(r => r.ok ? r.json() : Promise.reject(r))
    .then(() => {
      saveStatus.textContent = '✓ บันทึกแล้ว';
      showToast('✓ บันทึกการตั้งค่าแล้ว', 'success');
      setTimeout(() => saveStatus.textContent = '', 3000);
      // Clear nav builder pending indicator
      const nmbSt = document.getElementById('nmb-save-status');
      if (nmbSt && nmbSt.textContent.includes('เปลี่ยนแปลง')) {
        nmbSt.textContent = '✓ บันทึกแล้ว';
        nmbSt.style.color = '#16a34a';
        setTimeout(() => { nmbSt.textContent = ''; nmbSt.style.color = ''; }, 3000);
      }
    })
    .catch(() => {
      saveStatus.textContent = '⚠ ไม่สามารถบันทึกได้';
      showToast('⚠ เกิดข้อผิดพลาด — โปรดลองอีกครั้ง', 'error');
      const nmbSt = document.getElementById('nmb-save-status');
      if (nmbSt) { nmbSt.textContent = '⚠ บันทึกไม่สำเร็จ ลองกดปุ่ม "บันทึกเมนู"'; nmbSt.style.color = '#dc2626'; }
    });
  }, 800);
}

/* ══════════════════════════════════════════════════════
   NAV MENU BUILDER — drag / add / delete / new-tab / sync
   ══════════════════════════════════════════════════════ */
(function () {
  'use strict';

  const AUTO_TYPES = ['home', 'products'];
  const builder    = document.getElementById('nav-menu-builder');
  const jsonInput  = document.getElementById('nav_menu_items_json');
  const addBtn     = document.getElementById('nmb-add');
  if (!builder || !jsonInput || !addBtn) return;

  /* ── read current item data from a <li.nmb-row> ── */
  function rowToObj(li) {
    const type    = li.dataset.type || 'custom';
    const isAuto  = AUTO_TYPES.includes(type);
    const urlEl   = li.querySelector('.nmb-url');
    return {
      id:      li.dataset.id  || ('custom_' + Date.now()),
      label:   (li.querySelector('.nmb-lbl')?.value || '').trim(),
      url:     isAuto ? '' : (urlEl ? urlEl.value.trim() : ''),
      type:    type,
      visible: li.querySelector('.nmb-vis')?.checked ? true : false,
      new_tab: li.querySelector('.nmb-tab')?.checked ? true : false,
    };
  }

  /* ── serialize all rows → hidden JSON input ── */
  function syncJson() {
    const items = [];
    builder.querySelectorAll('li.nmb-row').forEach(li => items.push(rowToObj(li)));
    jsonInput.value = JSON.stringify(items);
    const st = document.getElementById('nmb-save-status');
    if (st) { st.textContent = '⏳ มีการเปลี่ยนแปลง…'; st.style.color = '#f59e0b'; }
    jsonInput.dispatchEvent(new Event('change', { bubbles: true }));
  }

  /* ── build a new <li> from an item object ── */
  function buildRow(item) {
    const isAuto = AUTO_TYPES.includes(item.type || 'custom');
    const badge  = item.type === 'home' ? 'หน้าแรก' : (item.type === 'products' ? 'สินค้า' : '');

    const li = document.createElement('li');
    li.className   = 'nmb-row';
    li.draggable   = true;
    li.dataset.idx  = '0';
    li.dataset.id   = item.id || ('custom_' + Date.now() + '_' + Math.random().toString(36).slice(2,6));
    li.dataset.type = item.type || 'custom';

    li.innerHTML =
      '<span class="nmb-drag" title="ลาก-วาง เพื่อเรียงลำดับ">⠿</span>' +
      '<input type="checkbox" class="nmb-vis" style="width:15px;height:15px;flex-shrink:0;"' + (item.visible ? ' checked' : '') + ' title="แสดง/ซ่อนเมนูนี้">' +
      (badge ? '<span class="nmb-type-badge">' + badge + '</span>' : '') +
      '<input type="text" class="nmb-lbl" value="' + escHtml(item.label || '') + '" placeholder="ชื่อเมนู" style="max-width:150px;flex:0 0 150px;">' +
      (isAuto
        ? '<span class="nmb-auto-url">🔗 Auto URL</span>'
        : '<input type="text" class="nmb-url" value="' + escHtml(item.url || '') + '" placeholder="URL (เช่น /about/)">') +
      '<label class="nmb-newtab" title="เปิดลิงก์ในแท็บใหม่"><input type="checkbox" class="nmb-tab"' + (item.new_tab ? ' checked' : '') + '> New Tab</label>' +
      '<button type="button" class="nmb-del" title="ลบรายการนี้">🗑</button>';

    attachRowEvents(li);
    return li;
  }

  function escHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  /* ── attach events to a row ── */
  function attachRowEvents(li) {
    /* delete */
    li.querySelector('.nmb-del').addEventListener('click', function () {
      li.remove();
      syncJson();
    });
    /* any input change */
    li.querySelectorAll('input').forEach(inp => {
      inp.addEventListener('change', syncJson);
      if (inp.type === 'text') inp.addEventListener('input', syncJson);
    });
    /* drag */
    li.addEventListener('dragstart', onDragStart);
    li.addEventListener('dragend',   onDragEnd);
    li.addEventListener('dragover',  onDragOver);
    li.addEventListener('dragleave', onDragLeave);
    li.addEventListener('drop',      onDrop);
  }

  /* attach events for PHP-rendered rows on page load */
  builder.querySelectorAll('li.nmb-row').forEach(li => {
    /* store type from the rendered data via PHP data-idx; rebuild dataset.type from JSON */
    const items = JSON.parse(jsonInput.value || '[]');
    const idx   = parseInt(li.dataset.idx, 10);
    if (!isNaN(idx) && items[idx]) {
      li.dataset.id   = items[idx].id   || ('item_' + idx);
      li.dataset.type = items[idx].type || 'custom';
    }
    attachRowEvents(li);
  });

  /* ── add button ── */
  addBtn.addEventListener('click', function () {
    const item = { id: 'custom_' + Date.now(), label: '', url: '', type: 'custom', visible: true, new_tab: false };
    builder.appendChild(buildRow(item));
    syncJson();
    const newRow = builder.lastElementChild;
    newRow.querySelector('.nmb-lbl')?.focus();
  });

  /* ── manual save button ── */
  const nmbSaveBtn  = document.getElementById('nmb-save-btn');
  const nmbStatus   = document.getElementById('nmb-save-status');
  function nmbSaveNow() {
    if (!nmbSaveBtn) return;
    syncJson();
    const orig = nmbSaveBtn.textContent;
    nmbSaveBtn.disabled = true;
    nmbSaveBtn.textContent = '⏳ กำลังบันทึก…';
    if (nmbStatus) nmbStatus.textContent = '';
    const nonce  = document.querySelector('meta[name="wp-nonce"]')?.content
                || (typeof wpRestNonce !== 'undefined' ? wpRestNonce : '<?php echo esc_js(wp_create_nonce('wp_rest')); ?>');
    const apiUrl = '<?php echo esc_url(rest_url('kv/v1/site-options')); ?>';
    fetch(apiUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
      body: JSON.stringify({ nav_menu_items_json: jsonInput.value })
    })
    .then(r => r.ok ? r.json() : Promise.reject(r))
    .then(() => {
      nmbSaveBtn.textContent = '✓ บันทึกแล้ว!';
      nmbSaveBtn.style.background = '#16a34a';
      if (nmbStatus) {
        nmbStatus.innerHTML = '✓ บันทึกเมนูเรียบร้อย &nbsp; <a href="' + window.location.origin + '/" target="_blank" style="font-size:11px;color:var(--ts-primary);text-decoration:underline;">🔍 ตรวจสอบ Frontend</a>';
        nmbStatus.style.color = '#16a34a';
      }
      // Warm the frontend cache with fresh content
      fetch(window.location.origin + '/', { cache: 'reload', credentials: 'omit' }).catch(function(){});
      setTimeout(() => {
        nmbSaveBtn.disabled = false;
        nmbSaveBtn.textContent = orig;
        nmbSaveBtn.style.background = '';
      }, 3000);
    })
    .catch(() => {
      nmbSaveBtn.textContent = '⚠ บันทึกไม่สำเร็จ';
      nmbSaveBtn.style.background = '#dc2626';
      if (nmbStatus) { nmbStatus.textContent = '⚠ เกิดข้อผิดพลาด'; nmbStatus.style.color = '#dc2626'; }
      setTimeout(() => {
        nmbSaveBtn.disabled = false;
        nmbSaveBtn.textContent = orig;
        nmbSaveBtn.style.background = '';
        if (nmbStatus) { nmbStatus.textContent = ''; nmbStatus.style.color = ''; }
      }, 3000);
    });
  }
  if (nmbSaveBtn) nmbSaveBtn.addEventListener('click', nmbSaveNow);

  /* ── clear cache button ── */
  const nmbFlushBtn = document.getElementById('nmb-flush-btn');
  if (nmbFlushBtn) {
    nmbFlushBtn.addEventListener('click', function() {
      const orig = nmbFlushBtn.textContent;
      nmbFlushBtn.disabled = true;
      nmbFlushBtn.textContent = '⏳ กำลังล้างแคช…';
      const nonce  = document.querySelector('meta[name="wp-nonce"]')?.content
                  || (typeof wpRestNonce !== 'undefined' ? wpRestNonce : '<?php echo esc_js(wp_create_nonce('wp_rest')); ?>');
      fetch('<?php echo esc_url(rest_url('kv/v1/flush-cache')); ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
      })
      .then(r => r.ok ? r.json() : Promise.reject(r))
      .then(() => {
        nmbFlushBtn.textContent = '✓ ล้างแคชเรียบร้อย';
        nmbFlushBtn.style.background = '#16a34a';
        if (nmbStatus) { nmbStatus.textContent = '✓ ล้างแคชเรียบร้อย'; nmbStatus.style.color = '#16a34a'; }
        setTimeout(() => {
          nmbFlushBtn.disabled = false;
          nmbFlushBtn.textContent = orig;
          nmbFlushBtn.style.background = '';
          if (nmbStatus) { nmbStatus.textContent = ''; nmbStatus.style.color = ''; }
        }, 3000);
      })
      .catch(() => {
        nmbFlushBtn.textContent = '⚠ ล้างแคชไม่สำเร็จ';
        nmbFlushBtn.style.background = '#dc2626';
        setTimeout(() => {
          nmbFlushBtn.disabled = false;
          nmbFlushBtn.textContent = orig;
          nmbFlushBtn.style.background = '';
        }, 3000);
      });
    });
  }


  let dragSrc = null;

  function onDragStart(e) {
    dragSrc = this;
    this.classList.add('dragging');
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', '');
  }
  function onDragEnd() {
    this.classList.remove('dragging');
    builder.querySelectorAll('.nmb-row').forEach(r => r.classList.remove('drag-over'));
    dragSrc = null;
  }
  function onDragOver(e) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    if (this !== dragSrc) this.classList.add('drag-over');
  }
  function onDragLeave() {
    this.classList.remove('drag-over');
  }
  function onDrop(e) {
    e.preventDefault();
    this.classList.remove('drag-over');
    if (!dragSrc || dragSrc === this) return;
    /* insert dragSrc before this, or after if dragging down */
    const rows   = [...builder.querySelectorAll('.nmb-row')];
    const srcIdx = rows.indexOf(dragSrc);
    const tgtIdx = rows.indexOf(this);
    if (srcIdx < tgtIdx) {
      this.after(dragSrc);
    } else {
      this.before(dragSrc);
    }
    syncJson();
  }
})();

// Debounced auto-save on any field change
document.getElementById('ts-main-form').addEventListener('change', ajaxSave);

// Intercept form submit — use AJAX instead of full page reload
document.getElementById('ts-main-form').addEventListener('submit', function(e) {
  // Allow normal POST submit for reliability
  const submitBtn = document.getElementById('ts-submit-btn');
  submitBtn.textContent = '⏳ กำลังบันทึก…';
  submitBtn.disabled = true;
});

/* ═══════════════════════════════════════════════════════════
   BLOCK EDITOR MANAGER — JavaScript
   ═══════════════════════════════════════════════════════════ */
const beApiUrl = '<?php echo esc_url(rest_url('kv/v1/block-editor-settings')); ?>';
let beDisabledBlocks = [...(window._beDisabledBlocks || [])];
let beDisabledPats   = [...(window._beDisabledPats || [])];
let beColors         = JSON.parse(JSON.stringify(window._beEffColors || []));
let beSizes          = JSON.parse(JSON.stringify(window._beEffSizes || []));
let beCurrentCat     = 'all';

/* ── Save function ─────────────────────────── */
let beSaveTimer = null;
function beSaveAll() {
  if (beSaveTimer) clearTimeout(beSaveTimer);
  const status = document.getElementById('be-save-status');
  if (status) status.textContent = 'กำลังบันทึก…';

  // Collect toggles
  const toggles = {};
  document.querySelectorAll('[data-be-toggle]').forEach(el => {
    toggles[el.dataset.beToggle] = el.checked ? '1' : '0';
  });

  // Collect spacing units
  const units = [];
  document.querySelectorAll('.be-unit-check.active').forEach(el => units.push(el.dataset.unit));

  const payload = {
    disabled_blocks: beDisabledBlocks,
    disabled_patterns: beDisabledPats,
    custom_colors: beColors,
    custom_font_sizes: beSizes,
    content_width: document.getElementById('be-content-width')?.value || '',
    wide_width: document.getElementById('be-wide-width')?.value || '',
    editor_css: document.getElementById('be-editor-css')?.value || '',
    spacing_units: units,
    ...toggles
  };

  fetch(beApiUrl, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
    body: JSON.stringify(payload)
  })
  .then(r => r.ok ? r.json() : Promise.reject(r))
  .then(() => {
    if (status) status.textContent = '✓ บันทึกแล้ว';
    showToast('✓ บันทึก Block Editor แล้ว', 'success');
    setTimeout(() => { if (status) status.textContent = ''; }, 3000);
  })
  .catch(() => {
    if (status) status.textContent = '⚠ ไม่สามารถบันทึกได้';
    showToast('⚠ เกิดข้อผิดพลาด', 'error');
  });
}

function beAutoSave() {
  if (beSaveTimer) clearTimeout(beSaveTimer);
  beSaveTimer = setTimeout(beSaveAll, 1200);
}

/* ── Block Editor save btn ─────────────────── */
const beSaveBtn = document.getElementById('be-save-btn');
if (beSaveBtn) beSaveBtn.addEventListener('click', beSaveAll);

const beUnlockAllBtn = document.getElementById('be-unlock-all-btn');
if (beUnlockAllBtn) {
  beUnlockAllBtn.addEventListener('click', () => {
    const toggles = document.querySelectorAll('[data-be-toggle]');
    toggles.forEach(el => {
      if (el.dataset.beToggle === 'freedom_mode') {
        el.checked = true;
      } else if (el.dataset.beToggle === 'default_fullscreen') {
        el.checked = true;
      } else {
        el.checked = false;
      }
    });

    beDisabledBlocks = [];
    beDisabledPats = [];
    beCurrentCat = 'all';

    document.querySelectorAll('.be-unit-check').forEach(el => el.classList.add('active'));

    beRenderCatPills();
    beRenderBlocks();
    beRenderPatterns();
    beUpdateBadges();
    beSaveAll();
  });
}

/* ── Render Blocks ─────────────────────────── */
const beAllBlocks = window._beAllBlocks || [];
const beBlockGrid = document.getElementById('be-block-grid');
const beCatPills  = document.getElementById('be-cat-pills');

function beGetCategories() {
  const cats = new Set();
  beAllBlocks.forEach(b => cats.add(b.category));
  return ['all', ...Array.from(cats).sort()];
}

const beCatLabels = {
  all:'ทั้งหมด', text:'Text', media:'Media', design:'Design',
  widgets:'Widgets', theme:'Theme', embed:'Embeds',
  uncategorized:'อื่นๆ', reusable:'Reusable'
};

function beRenderCatPills() {
  if (!beCatPills) return;
  const cats = beGetCategories();
  beCatPills.innerHTML = '';
  cats.forEach(cat => {
    const pill = document.createElement('span');
    pill.className = 'be-cat-pill' + (cat === beCurrentCat ? ' active' : '');
    pill.textContent = beCatLabels[cat] || cat;
    pill.onclick = () => { beCurrentCat = cat; beRenderCatPills(); beRenderBlocks(); };
    beCatPills.appendChild(pill);
  });
}

function beRenderBlocks() {
  if (!beBlockGrid) return;
  const search = (document.getElementById('be-block-search')?.value || '').toLowerCase();
  beBlockGrid.innerHTML = '';
  let shown = 0, total = 0, disabled = 0;

  beAllBlocks.forEach(block => {
    total++;
    const isDisabled = beDisabledBlocks.includes(block.name);
    if (isDisabled) disabled++;

    // Filter by category
    if (beCurrentCat !== 'all' && block.category !== beCurrentCat) return;
    // Filter by search
    if (search && !block.title.toLowerCase().includes(search) && !block.name.toLowerCase().includes(search)) return;

    shown++;
    const card = document.createElement('div');
    card.className = 'be-block-card' + (isDisabled ? ' disabled' : '');
    card.title = block.name + (block.desc ? ' — ' + block.desc : '');
    card.innerHTML =
      '<div><div class="be-bname">' + escHtml(block.title) + '</div>' +
      '<div class="be-bcat">' + escHtml(block.name) + '</div></div>' +
      '<label class="ts-switch" style="flex-shrink:0;"><input type="checkbox"' +
      (!isDisabled ? ' checked' : '') + '><span class="slider"></span></label>';

    const chk = card.querySelector('input');
    chk.addEventListener('change', () => {
      if (chk.checked) {
        beDisabledBlocks = beDisabledBlocks.filter(n => n !== block.name);
        card.classList.remove('disabled');
      } else {
        if (!beDisabledBlocks.includes(block.name)) beDisabledBlocks.push(block.name);
        card.classList.add('disabled');
      }
      beUpdateBadges();
      beAutoSave();
    });
    beBlockGrid.appendChild(card);
  });

  const countEl = document.getElementById('be-block-count');
  if (countEl) countEl.textContent = 'แสดง ' + shown + ' / ' + total + ' blocks (' + disabled + ' ปิดอยู่)';
  beUpdateBadges();
}

function beUpdateBadges() {
  const total = beAllBlocks.length;
  const dis = beDisabledBlocks.length;
  const pTotal = (window._beAllPatterns || []).length;
  const pDis = beDisabledPats.length;
  const b1 = document.getElementById('be-badge-blocks');
  const b2 = document.getElementById('be-badge-disabled');
  const b3 = document.getElementById('be-badge-patterns');
  if (b1) b1.textContent = '🧩 Blocks: ' + (total - dis) + '/' + total;
  if (b2) b2.textContent = '🚫 ปิด: ' + dis + ' blocks, ' + pDis + ' patterns';
  if (b3) b3.textContent = '🎨 Patterns: ' + (pTotal - pDis) + '/' + pTotal;
}

window.beToggleAllBlocks = function(enable) {
  if (enable) {
    beDisabledBlocks = [];
  } else {
    beDisabledBlocks = beAllBlocks.map(b => b.name);
  }
  beRenderBlocks();
  beAutoSave();
};

function escHtml(str) {
  const d = document.createElement('span');
  d.textContent = str;
  return d.innerHTML;
}

// Search
const beSearchInput = document.getElementById('be-block-search');
if (beSearchInput) beSearchInput.addEventListener('input', beRenderBlocks);

/* ── Render Patterns ───────────────────────── */
const beAllPatterns = window._beAllPatterns || [];
const bePatternList = document.getElementById('be-pattern-list');

function beRenderPatterns() {
  if (!bePatternList) return;
  const search = (document.getElementById('be-pattern-search')?.value || '').toLowerCase();
  bePatternList.innerHTML = '';

  beAllPatterns.forEach(pat => {
    if (search && !pat.title.toLowerCase().includes(search) && !pat.name.toLowerCase().includes(search)) return;
    const isDisabled = beDisabledPats.includes(pat.name);

    const row = document.createElement('div');
    row.className = 'be-pattern-row';
    row.innerHTML =
      '<div style="flex:1;min-width:0;"><div class="be-ptitle">' + escHtml(pat.title) + '</div>' +
      (pat.description ? '<div class="be-pdesc">' + escHtml(pat.description) + '</div>' : '') +
      '<div class="be-pcat">' + escHtml((pat.categories || []).join(', ')) + '</div></div>' +
      '<label class="ts-switch" style="flex-shrink:0;"><input type="checkbox"' +
      (!isDisabled ? ' checked' : '') + '><span class="slider"></span></label>';

    const chk = row.querySelector('input');
    chk.addEventListener('change', () => {
      if (chk.checked) {
        beDisabledPats = beDisabledPats.filter(n => n !== pat.name);
      } else {
        if (!beDisabledPats.includes(pat.name)) beDisabledPats.push(pat.name);
      }
      beUpdateBadges();
      beAutoSave();
    });
    bePatternList.appendChild(row);
  });
}

window.beToggleAllPatterns = function(enable) {
  if (enable) {
    beDisabledPats = [];
  } else {
    beDisabledPats = beAllPatterns.map(p => p.name);
  }
  beRenderPatterns();
  beUpdateBadges();
  beAutoSave();
};

const bePatSearch = document.getElementById('be-pattern-search');
if (bePatSearch) bePatSearch.addEventListener('input', beRenderPatterns);

/* ── Render Colors ─────────────────────────── */
function beRenderColors() {
  const list = document.getElementById('be-colors-list');
  if (!list) return;
  list.innerHTML = '';
  beColors.forEach((c, idx) => {
    const row = document.createElement('div');
    row.className = 'be-color-item';
    row.innerHTML =
      '<input type="color" value="' + (c.color || '#000000') + '">' +
      '<input type="text" class="be-c-name" value="' + escHtml(c.name || '') + '" placeholder="ชื่อสี">' +
      '<input type="text" class="be-c-slug" value="' + escHtml(c.slug || '') + '" placeholder="slug">' +
      '<input type="text" class="be-c-hex" value="' + escHtml(c.color || '') + '" placeholder="#hex">' +
      '<button type="button" class="be-remove-btn" title="ลบ">✕</button>';

    const [colorInp, nameInp, slugInp, hexInp, removeBtn] = row.querySelectorAll('input, button');

    colorInp.addEventListener('input', () => {
      beColors[idx].color = colorInp.value;
      hexInp.value = colorInp.value;
      beAutoSave();
    });
    nameInp.addEventListener('input', () => {
      beColors[idx].name = nameInp.value;
      if (!beColors[idx]._slugEdited) slugInp.value = beColors[idx].slug = nameInp.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
      beAutoSave();
    });
    slugInp.addEventListener('input', () => {
      beColors[idx].slug = slugInp.value;
      beColors[idx]._slugEdited = true;
      beAutoSave();
    });
    hexInp.addEventListener('input', () => {
      if (/^#[0-9a-fA-F]{6}$/.test(hexInp.value)) {
        beColors[idx].color = hexInp.value;
        colorInp.value = hexInp.value;
        beAutoSave();
      }
    });
    removeBtn.addEventListener('click', () => {
      beColors.splice(idx, 1);
      beRenderColors();
      beAutoSave();
    });

    list.appendChild(row);
  });
}

window.beAddColor = function() {
  beColors.push({ slug: 'color-' + (beColors.length + 1), color: '#cccccc', name: 'New Color' });
  beRenderColors();
  beAutoSave();
};
window.beResetColors = function() {
  beColors = JSON.parse(JSON.stringify(window._beDefColors || []));
  beRenderColors();
  beAutoSave();
};

/* ── Render Font Sizes ─────────────────────── */
function beRenderSizes() {
  const list = document.getElementById('be-sizes-list');
  if (!list) return;
  list.innerHTML = '';
  beSizes.forEach((s, idx) => {
    const row = document.createElement('div');
    row.className = 'be-size-item';
    row.innerHTML =
      '<input type="text" class="be-s-name" value="' + escHtml(s.name || '') + '" placeholder="ชื่อ">' +
      '<input type="text" class="be-s-slug" value="' + escHtml(s.slug || '') + '" placeholder="slug">' +
      '<input type="text" class="be-s-size" value="' + escHtml(s.size || '') + '" placeholder="เช่น 16px">' +
      '<button type="button" class="be-remove-btn" title="ลบ">✕</button>';

    const [nameInp, slugInp, sizeInp, removeBtn] = row.querySelectorAll('input, button');
    nameInp.addEventListener('input', () => { beSizes[idx].name = nameInp.value; beAutoSave(); });
    slugInp.addEventListener('input', () => { beSizes[idx].slug = slugInp.value; beAutoSave(); });
    sizeInp.addEventListener('input', () => { beSizes[idx].size = sizeInp.value; beAutoSave(); });
    removeBtn.addEventListener('click', () => { beSizes.splice(idx, 1); beRenderSizes(); beAutoSave(); });

    list.appendChild(row);
  });
}

window.beAddSize = function() {
  beSizes.push({ slug: 'size-' + (beSizes.length + 1), size: '16px', name: 'Custom' });
  beRenderSizes();
  beAutoSave();
};
window.beResetSizes = function() {
  beSizes = JSON.parse(JSON.stringify(window._beDefSizes || []));
  beRenderSizes();
  beAutoSave();
};

/* ── Spacing Units ─────────────────────────── */
window.beToggleUnit = function(el) {
  el.classList.toggle('active');
  beAutoSave();
};

/* ── Toggle auto-save ──────────────────────── */
document.querySelectorAll('[data-be-toggle]').forEach(el => {
  el.addEventListener('change', beAutoSave);
});
['be-content-width','be-wide-width','be-editor-css'].forEach(id => {
  const el = document.getElementById(id);
  if (el) el.addEventListener('input', beAutoSave);
});

/* ── Init renders ──────────────────────────── */
beRenderCatPills();
beRenderBlocks();
beRenderPatterns();
beRenderColors();
beRenderSizes();
beUpdateBadges();

})();
</script>
