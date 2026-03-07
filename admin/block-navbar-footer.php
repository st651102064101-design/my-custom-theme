<?php
/**
 * [kv_navbar] & [kv_footer] shortcodes
 *
 * Render the exact same Bootstrap 5 navbar/footer used in header.php / footer.php
 * so that FSE template parts (parts/header.html, parts/footer.html) produce
 * identical output via <!-- wp:html -->[kv_navbar]<!-- /wp:html -->
 *
 * @since 2026-03-02
 */

if (!defined('ABSPATH')) exit;

if (!function_exists('kv_internal_url')) {
    function kv_internal_url($url_or_path = '/') {
        if ($url_or_path === null || $url_or_path === '') {
            return site_url('/');
        }

        $value = (string) $url_or_path;

        if (preg_match('~^(mailto:|tel:|javascript:|#)~i', $value)) {
            return $value;
        }

        if (!preg_match('#^https?://#i', $value)) {
            return site_url('/' . ltrim($value, '/'));
        }

        $site_parts = parse_url(site_url('/'));
        $url_parts  = parse_url($value);
        if (!$site_parts || !$url_parts) {
            return $value;
        }

        $site_host = strtolower((string) ($site_parts['host'] ?? ''));
        $url_host  = strtolower((string) ($url_parts['host'] ?? ''));

        if ($site_host === '' || $url_host === '' || $site_host !== $url_host) {
            return $value;
        }

        $scheme = $site_parts['scheme'] ?? 'https';
        $host   = $site_parts['host'];
        $port   = isset($site_parts['port']) ? ':' . $site_parts['port'] : '';

        $site_base_path = rtrim((string) ($site_parts['path'] ?? ''), '/');
        $target_path    = (string) ($url_parts['path'] ?? '/');

        if ($site_base_path !== '' && $site_base_path !== '/' && strpos($target_path, $site_base_path . '/') !== 0 && $target_path !== $site_base_path) {
            $target_path = $site_base_path . '/' . ltrim($target_path, '/');
        }

        $query    = isset($url_parts['query']) ? '?' . $url_parts['query'] : '';
        $fragment = isset($url_parts['fragment']) ? '#' . $url_parts['fragment'] : '';

        return $scheme . '://' . $host . $port . $target_path . $query . $fragment;
    }
}

/* ================================================================
   [kv_navbar] — Full Bootstrap 5 responsive navbar
   ================================================================ */
add_shortcode('kv_navbar', function () {
    ob_start();

    // ── Active-state detection (same logic as header.php) ──
    $is_product_page   = false;
    $active_cat_slug   = '';
    $active_term_id    = 0;
    $active_product_id = 0;

    if (is_tax('product_category')) {
        $is_product_page = true;
        $queried = get_queried_object();
        $active_term_id = (int) $queried->term_id;
        $root = $queried;
        while ($root->parent) {
            $p = get_term($root->parent, 'product_category');
            if ($p && !is_wp_error($p)) { $root = $p; } else { break; }
        }
        $active_cat_slug = $root->slug;
    } elseif (is_singular('product')) {
        $is_product_page   = true;
        $active_product_id = (int) get_queried_object_id();
        $terms = wp_get_object_terms($active_product_id, 'product_category');
        if ($terms && !is_wp_error($terms)) {
            $selected = null; $selected_depth = -1;
            foreach ($terms as $t) {
                $d = 0; $w = $t;
                while ($w->parent) { $pp = get_term($w->parent, 'product_category'); if ($pp && !is_wp_error($pp)) { $d++; $w = $pp; } else break; }
                if ($d > $selected_depth) { $selected_depth = $d; $selected = $t; }
            }
            if (!$selected) $selected = $terms[0];
            $active_term_id = (int) $selected->term_id;
            $root = $selected;
            while ($root->parent) { $p = get_term($root->parent, 'product_category'); if ($p && !is_wp_error($p)) { $root = $p; } else break; }
            $active_cat_slug = $root->slug;
        }
    } elseif (is_page()) {
        $slug = get_post_field('post_name', get_queried_object_id());
        if ($slug === 'products') $is_product_page = true;
    }

    $current_slug = is_page() ? get_post_field('post_name', get_queried_object_id()) : '';
    $current_request_path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    $segments = array_values(array_filter(explode('/', $current_request_path), 'strlen'));
    $prod_idx = array_search('products', $segments, true);
    $parent_seg = ($prod_idx !== false && isset($segments[$prod_idx + 1])) ? $segments[$prod_idx + 1] : '';
    $child_seg  = ($prod_idx !== false && isset($segments[$prod_idx + 2])) ? $segments[$prod_idx + 2] : '';
    $last_seg   = ($prod_idx !== false && !empty($segments)) ? end($segments) : '';

    // ── Product categories + children ──
    $sort_order = my_theme_get_product_category_order();
    $nav_categories = get_terms(['taxonomy' => 'product_category', 'parent' => 0, 'hide_empty' => false, 'orderby' => 'id', 'order' => 'DESC']);
    $nav_cat_products = [];
    if ($nav_categories && !is_wp_error($nav_categories)) {
        foreach ($nav_categories as $cat) {
            $children = get_terms(['taxonomy' => 'product_category', 'parent' => $cat->term_id, 'hide_empty' => false]);
            if ($children && !is_wp_error($children) && count($children) > 0) {
                $nav_cat_products[$cat->term_id] = ['type' => 'terms', 'items' => $children];
            } else {
                $prods = get_posts(['post_type' => 'product', 'posts_per_page' => 10, 'post_status' => 'publish',
                    'tax_query' => [['taxonomy' => 'product_category', 'field' => 'term_id', 'terms' => $cat->term_id]]]);
                $nav_cat_products[$cat->term_id] = ['type' => 'products', 'items' => $prods];
            }
        }
    }

    // ── Navbar styling options ──
    $_nav_bg      = get_option('nav_bg_color', '#ffffff');
    $_nav_txt     = get_option('nav_text_color', '');
    $_nav_hover   = get_option('nav_hover_color', '')  ?: 'var(--theme-primary)';
    $_nav_active  = get_option('nav_active_color', '') ?: 'var(--theme-primary)';
    $_nav_fs      = (int) get_option('nav_font_size', 16);
    $_nav_fw      = get_option('nav_font_weight', '500');
    $_nav_align   = get_option('nav_align', 'center');
    $_nav_sticky  = get_option('nav_sticky', '1');
    $_nav_shadow  = get_option('nav_shadow', '1');
    $_nav_py      = (int) get_option('nav_padding_y', 8);
    $_nav_logo_h  = (int) get_option('nav_logo_height', 50);
    $_nav_cta_bg  = get_option('nav_cta_bg', '')      ?: 'var(--theme-primary)';
    $_nav_cta_txt = get_option('nav_cta_text_color', '#ffffff');
    $_nav_cta_rad = (int) get_option('nav_cta_radius', 6);
    $_nav_cta_fs  = (int) get_option('nav_cta_font_size', 14);

    $_nav_align_class = 'mx-auto';
    if ($_nav_align === 'left')  $_nav_align_class = 'ms-0 me-auto';
    if ($_nav_align === 'right') $_nav_align_class = 'ms-auto me-0';

    $_nav_classes = 'navbar navbar-expand-lg';
    $_nav_classes .= ($_nav_bg && !in_array(strtolower($_nav_bg), ['#ffffff','#fff'])) ? ' navbar-dark' : ' navbar-light';
    $_nav_classes .= ($_nav_shadow === '1') ? ' shadow-sm' : '';
    $_nav_classes .= ($_nav_sticky === '1') ? ' sticky-top' : '';

    $logo_url = esc_url(kv_internal_url(kv_rebase_url(get_option('site_logo_url', site_url('/wp-content/uploads/2026/02/New-Logo-Schott-1-1-300x120-1.jpg')))));
    $logo_alt = esc_attr(get_option('nav_logo_alt', get_bloginfo('name')));

    // Phone CTA
    $default_phone_raw = '+66 2 108 8521';
    $site_phone_raw = get_option('site_phone', get_theme_mod('site_phone', $default_phone_raw));
    if (function_exists('kv_clean_text_option_value')) {
        $site_phone_raw = kv_clean_text_option_value($site_phone_raw, $default_phone_raw);
    }
    $phone_display = trim((string) $site_phone_raw);
    if ($phone_display === '') $phone_display = $default_phone_raw;

    $nav_cta_text = get_option('nav_cta_text', '');
    $nav_cta_url  = get_option('nav_cta_url', '/contact/');
    $nav_cta_href = kv_internal_url($nav_cta_url);
    if (trim((string) $nav_cta_text) === '' && trim((string) $site_phone_raw) !== '') {
        $phone_tel = preg_replace('/[^0-9+]/', '', (string) $site_phone_raw);
        if ($phone_tel) $nav_cta_href = 'tel:' . $phone_tel;
    }
    $cta_label = $nav_cta_text ? $nav_cta_text : $phone_display;

    $has_nav_cats = $nav_categories && !is_wp_error($nav_categories) && count($nav_categories) > 0;
    ?>
<style>
:root{--theme-primary:<?php echo esc_html(get_option('theme_primary_color','#0056d6')); ?>;--theme-primary-dark:<?php echo esc_html(get_option('theme_primary_dark_color','#0049b4')); ?>;--theme-accent:<?php echo esc_html(get_option('theme_accent_color','#4ecdc4')); ?>;}
body{font-family:'Sarabun',sans-serif;}
.footer-dark{background-color:#1e293b;}.footer-dark a{color:#94a3b8;text-decoration:none;}.footer-dark a:hover{color:var(--theme-primary);}.footer-divider{border-color:#334155;}
.navbar.sticky-top{top:0}body.logged-in .navbar.sticky-top{top:32px}
@media screen and (max-width:782px){body.logged-in .navbar.sticky-top{top:46px}}
@media(min-width:992px){
.nav-item.dropdown:hover>.dropdown-menu{display:block;margin-top:0}
.nav-item.dropdown>.dropdown-toggle{pointer-events:auto}
.dropdown-menu .dropdown-submenu{position:relative}
.dropdown-menu .dropdown-submenu>.dropdown-menu{display:none;position:absolute;top:-4px;left:100%;min-width:220px;border-radius:8px;box-shadow:0 8px 30px rgba(0,0,0,.12);border:1px solid rgba(0,0,0,.07)}
.dropdown-menu .dropdown-submenu:hover>.dropdown-menu{display:block}
}
.dropdown-submenu>a::after{content:'';display:inline-block;width:0;height:0;border-top:4px solid transparent;border-bottom:4px solid transparent;border-left:5px solid #6c757d;float:right;margin-top:5px}
.nav-item.dropdown>.nav-link.dropdown-toggle::after{display:inline-block!important;content:""!important;border-top:.3em solid!important;border-right:.3em solid transparent!important;border-bottom:0!important;border-left:.3em solid transparent!important;margin-left:.3em;vertical-align:.2em}
.dropdown-menu .dropdown-submenu>.dropdown-menu .dropdown-item{font-size:14px;padding:7px 16px;color:#1e293b}
.dropdown-menu .dropdown-submenu>.dropdown-menu .dropdown-item:hover,.dropdown-menu .dropdown-submenu>.dropdown-menu .dropdown-item:focus{background-color:#f0f5ff!important;color:var(--theme-primary)!important}
.dropdown-menu .dropdown-submenu>.dropdown-menu .dropdown-item.active{background-color:#f0f5ff!important;color:var(--theme-primary)!important;font-weight:600}
.dropdown-menu>li>.dropdown-item.active,.dropdown-menu>li.dropdown-submenu>.dropdown-item.active,.dropdown-menu>li>.dropdown-item:hover,.dropdown-menu>li>.dropdown-item:focus{background-color:#f0f5ff!important;color:var(--theme-primary)!important}
.dropdown-menu>li.dropdown-submenu:hover>.dropdown-item{background-color:#f0f5ff!important;color:var(--theme-primary)!important}
.dropdown-menu>li.dropdown-submenu:hover>a::after{border-left-color:var(--theme-primary)}
.nav-cat-extra{display:none!important}.nav-cat-extra.nav-cat-visible{display:flex!important}
.dropdown-view-all{display:block;font-size:13px;padding:8px 16px;color:var(--theme-primary);background-color:transparent;font-weight:500;text-decoration:none;white-space:nowrap}
.dropdown-view-all:hover,.dropdown-view-all:focus{background-color:#f0f5ff;color:var(--theme-primary)}
.dropdown-menu>li:last-child>.dropdown-view-all{border-bottom-left-radius:8px;border-bottom-right-radius:8px}
/* Dynamic navbar */
#navbarMain .nav-link{<?php if($_nav_txt):?>color:<?php echo esc_html($_nav_txt);?>!important;<?php endif;?>font-size:<?php echo $_nav_fs;?>px;font-weight:<?php echo esc_html($_nav_fw);?>;transition:color .2s}
#navbarMain .nav-link:hover,#navbarMain .nav-link:focus{color:<?php echo esc_html($_nav_hover);?>!important}
#navbarMain .nav-link.active{color:<?php echo esc_html($_nav_active);?>!important;font-weight:<?php echo max((int)$_nav_fw,600);?>}
.navbar-kv-cta{background:<?php echo esc_html($_nav_cta_bg);?>!important;color:<?php echo esc_html($_nav_cta_txt);?>!important;border-radius:<?php echo $_nav_cta_rad;?>px!important;font-size:<?php echo $_nav_cta_fs;?>px!important;font-weight:600;padding:8px 18px;border:none;text-decoration:none;white-space:nowrap;transition:opacity .2s}
.navbar-kv-cta:hover{opacity:.85;color:<?php echo esc_html($_nav_cta_txt);?>!important}
.navbar-brand-kv{display:inline-flex;flex-direction:column;align-items:flex-start;text-decoration:none}
.navbar-brand-kv img{display:block}
</style>
<nav class="<?php echo esc_attr($_nav_classes); ?>" style="background-color:<?php echo esc_attr($_nav_bg); ?>;padding-top:<?php echo $_nav_py; ?>px;padding-bottom:<?php echo $_nav_py; ?>px;">
    <div class="container">
        <a class="navbar-brand navbar-brand-kv" href="<?php echo esc_url(kv_internal_url('/')); ?>">
            <img src="<?php echo $logo_url; ?>" alt="<?php echo $logo_alt; ?>" height="<?php echo $_nav_logo_h; ?>" style="max-height:<?php echo $_nav_logo_h; ?>px;width:auto;">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarMain" aria-controls="navbarMain"
                aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav <?php echo esc_attr($_nav_align_class); ?> mb-2 mb-lg-0">
                <?php
                /* ══════════════════════════════════════════════════
                   JSON-driven nav items (synced with admin builder)
                   ══════════════════════════════════════════════════ */
                $_nav_json_raw = get_option('nav_menu_items_json', '');
                $_nav_items = [];
                if ($_nav_json_raw) {
                    $_dec = json_decode($_nav_json_raw, true);
                    if (is_array($_dec) && !empty($_dec)) $_nav_items = $_dec;
                }
                if (empty($_nav_items)) {
                    $_nav_items = [
                        ['id'=>'home',     'label'=>get_option('nav_home_label','Home'),        'url'=>'',                                           'type'=>'home',     'visible'=>(get_option('nav_home_visible','1')==='1'),    'new_tab'=>false],
                        ['id'=>'about',    'label'=>get_option('nav_about_label','About Us'),   'url'=>get_option('nav_about_url','/about/'),         'type'=>'custom',   'visible'=>(get_option('nav_about_visible','1')==='1'),   'new_tab'=>false],
                        ['id'=>'products', 'label'=>get_option('nav_products_label','Products'),'url'=>'',                                           'type'=>'products', 'visible'=>(get_option('nav_products_visible','1')==='1'),'new_tab'=>false],
                        ['id'=>'contact',  'label'=>get_option('nav_contact_label','Contacts'), 'url'=>get_option('nav_contact_url','/contact/'),     'type'=>'custom',   'visible'=>(get_option('nav_contact_visible','1')==='1'), 'new_tab'=>false],
                    ];
                }
                foreach ($_nav_items as $_ni):
                    if (empty($_ni['visible'])) continue;
                    $_ni_label   = esc_html($_ni['label'] ?? '');
                    $_ni_type    = $_ni['type'] ?? 'custom';
                    $_ni_new_tab = !empty($_ni['new_tab']);
                    $_ni_target  = $_ni_new_tab ? ' target="_blank" rel="noopener noreferrer"' : '';

                    if ($_ni_type === 'home') : ?>
                <li class="nav-item">
                    <a class="nav-link<?php echo is_front_page() ? ' active' : ''; ?>" href="<?php echo esc_url(kv_internal_url('/')); ?>"<?php echo $_ni_target; ?>><?php echo $_ni_label; ?></a>
                </li>
                    <?php elseif ($_ni_type === 'products') : ?>
                <?php if ($has_nav_cats) : ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle<?php echo $is_product_page ? ' active' : ''; ?>"
                       href="<?php echo esc_url(kv_internal_url('/products/')); ?>"
                       role="button" aria-expanded="false"<?php echo $_ni_target; ?>><?php echo $_ni_label; ?></a>
                    <ul class="dropdown-menu" style="min-width:200px;border-radius:8px;box-shadow:0 8px 30px rgba(0,0,0,.12);border:1px solid rgba(0,0,0,.07);padding:6px 0;overflow:visible;">
                        <?php
                        $nav_total = count($nav_categories);
                        $nav_limit = 4;
                        $nav_idx = 0;
                        foreach ($nav_categories as $cat) :
                            $nav_idx++;
                            $is_extra    = ($nav_idx > $nav_limit);
                            $extra_class = $is_extra ? ' nav-cat-extra' : '';
                            $sub         = $nav_cat_products[$cat->term_id] ?? null;
                            $has_sub     = $sub && count($sub['items']) > 0;
                            $cat_link    = kv_internal_url(get_term_link($cat));
                            $cat_path    = (!is_wp_error($cat_link) && $cat_link) ? trim((string)parse_url($cat_link, PHP_URL_PATH), '/') : '';
                            $cat_slug_prefix = 'products/' . $cat->slug;

                            $has_active_child = false;
                            if ($has_sub && $sub['type'] === 'terms') {
                                foreach ($sub['items'] as $cp) {
                                    $cp_link = kv_internal_url(get_term_link($cp));
                                    $cp_path = (!is_wp_error($cp_link) && $cp_link) ? trim((string)parse_url($cp_link, PHP_URL_PATH), '/') : '';
                                    $cp_n = 'products/' . $cat->slug . '/' . $cp->slug;
                                    $cp_f = 'products/' . $cp->slug;
                                    if (
                                        ($active_term_id === (int)$cp->term_id)
                                        || ($cp_path && $current_request_path && ($current_request_path === $cp_path || strpos($current_request_path, $cp_path.'/') === 0))
                                        || ($current_request_path === $cp_n || strpos($current_request_path, $cp_n.'/') === 0)
                                        || ($current_request_path === $cp_f || strpos($current_request_path, $cp_f.'/') === 0)
                                        || ($parent_seg === $cat->slug && ($child_seg === $cp->slug || $last_seg === $cp->slug))
                                    ) { $has_active_child = true; break; }
                                }
                            }
                            $is_active_cat = ($active_cat_slug === $cat->slug)
                                || ($cat_path && $current_request_path && ($current_request_path === $cat_path || strpos($current_request_path, $cat_path.'/') === 0))
                                || ($current_request_path === $cat_slug_prefix || strpos($current_request_path, $cat_slug_prefix.'/') === 0)
                                || ($parent_seg && $parent_seg === $cat->slug)
                                || $has_active_child;
                        ?>
                            <?php if ($has_sub) : ?>
                            <li class="dropdown-submenu<?php echo $extra_class; ?>">
                                <a class="dropdown-item d-flex justify-content-between align-items-center<?php echo $is_active_cat ? ' active' : ''; ?>"
                                   href="<?php echo esc_url($cat_link); ?>"
                                   style="padding:9px 16px;font-size:15px;">
                                    <?php echo esc_html($cat->name); ?>
                                </a>
                                <ul class="dropdown-menu" style="padding:6px 0;">
                                    <?php if ($sub['type'] === 'terms') : ?>
                                        <?php foreach ($sub['items'] as $child) :
                                            $ch_link = kv_internal_url(get_term_link($child));
                                            $ch_path = (!is_wp_error($ch_link) && $ch_link) ? trim((string)parse_url($ch_link, PHP_URL_PATH), '/') : '';
                                            $ch_n = 'products/' . $cat->slug . '/' . $child->slug;
                                            $ch_f = 'products/' . $child->slug;
                                            $ch_active = ($active_term_id === (int)$child->term_id)
                                                || ($ch_path && $current_request_path && ($current_request_path === $ch_path || strpos($current_request_path, $ch_path.'/') === 0))
                                                || ($current_request_path === $ch_n || strpos($current_request_path, $ch_n.'/') === 0)
                                                || ($current_request_path === $ch_f || strpos($current_request_path, $ch_f.'/') === 0)
                                                || ($parent_seg === $cat->slug && ($child_seg === $child->slug || $last_seg === $child->slug));
                                        ?>
                                        <li><a class="dropdown-item<?php echo $ch_active ? ' active' : ''; ?>" href="<?php echo esc_url($ch_link); ?>"><?php echo esc_html($child->name); ?></a></li>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <?php foreach ($sub['items'] as $prod) :
                                            $pr_link = kv_internal_url(get_permalink($prod->ID));
                                            $pr_path = $pr_link ? trim((string)parse_url($pr_link, PHP_URL_PATH), '/') : '';
                                            $pr_active = ($active_product_id === (int)$prod->ID) || ($pr_path && $current_request_path && $current_request_path === $pr_path);
                                        ?>
                                        <li><a class="dropdown-item<?php echo $pr_active ? ' active' : ''; ?>" href="<?php echo esc_url($pr_link); ?>"><?php echo esc_html($prod->post_title); ?></a></li>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li style="background:#fff;border-bottom-left-radius:8px;border-bottom-right-radius:8px;">
                                        <a class="dropdown-view-all" href="<?php echo esc_url(kv_internal_url(get_term_link($cat))); ?>" style="display:block;padding:8px 16px;font-size:13px;color:var(--theme-primary);background:#fff!important;text-decoration:none;font-weight:500;border-bottom-left-radius:8px;border-bottom-right-radius:8px;">
                                            View all <?php echo esc_html($cat->name); ?> →
                                        </a>
                                    </li>
                                </ul>
                            </li>
                            <?php else : ?>
                            <li class="<?php echo trim($extra_class); ?>">
                                <a class="dropdown-item<?php echo $is_active_cat ? ' active' : ''; ?>"
                                   href="<?php echo esc_url(kv_internal_url(get_term_link($cat))); ?>"
                                   style="padding:9px 16px;font-size:15px;">
                                    <?php echo esc_html($cat->name); ?>
                                </a>
                            </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <?php if ($nav_total > $nav_limit) : ?>
                        <li>
                            <button id="nav-cat-showmore" onclick="(function(btn){document.querySelectorAll('.nav-cat-extra').forEach(function(el){el.classList.toggle('nav-cat-visible');});var s=btn.dataset.showing==='1';btn.innerHTML=s?'Show <?php echo ($nav_total-$nav_limit);?> more&hellip; &#9660;':'Show less &#9650;';btn.dataset.showing=s?'0':'1';})(this)" data-showing="0"
                                style="width:100%;background:none;border:none;text-align:left;padding:7px 16px;font-size:13px;color:#64748b;cursor:pointer;">
                                Show <?php echo ($nav_total-$nav_limit); ?> more&hellip; &#9660;
                            </button>
                        </li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider" style="margin:4px 0;"></li>
                        <li style="background:#fff;border-bottom-left-radius:8px;border-bottom-right-radius:8px;">
                            <a class="dropdown-view-all" href="<?php echo esc_url(kv_internal_url('/products/')); ?>" style="display:block;padding:8px 16px;font-size:13px;color:var(--theme-primary);background:#fff!important;text-decoration:none;font-weight:500;border-bottom-left-radius:8px;border-bottom-right-radius:8px;">
                                View All Products →
                            </a>
                        </li>
                    </ul>
                </li>
                <?php else : ?>
                <li class="nav-item">
                    <a class="nav-link<?php echo $is_product_page ? ' active' : ''; ?>"
                       href="<?php echo esc_url(kv_internal_url('/products/')); ?>"<?php echo $_ni_target; ?>><?php echo $_ni_label; ?></a>
                </li>
                <?php endif; ?>
                    <?php else :
                        /* custom / about / contact / any other type */
                        $_ni_url  = $_ni['url'] ?? '';
                        $_ni_href = (strpos($_ni_url, 'http') === 0) ? $_ni_url : (trim($_ni_url) !== '' ? kv_internal_url($_ni_url) : '#');
                        $_ni_slug = sanitize_title($_ni['label'] ?? '');
                        $_ni_active = ($current_slug === $_ni_slug) ? ' active' : '';
                    ?>
                <li class="nav-item">
                    <a class="nav-link<?php echo $_ni_active; ?>" href="<?php echo esc_url($_ni_href); ?>"<?php echo $_ni_target; ?>><?php echo $_ni_label; ?></a>
                </li>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php
                // Custom nav items
                $custom_raw = explode("\n", get_option('nav_custom_items', ''));
                $custom_tree = []; $last_pi = -1;
                foreach ($custom_raw as $rl) {
                    if (trim($rl) === '') continue;
                    $is_sub = (strlen($rl) > 2 && substr($rl,0,2) === '  ');
                    $pts = explode('|', trim($rl), 2);
                    $cl = trim($pts[0] ?? ''); $cu = trim($pts[1] ?? '#');
                    if (!$cl) continue;
                    if ($is_sub && $last_pi >= 0) { $custom_tree[$last_pi]['ch'][] = ['l'=>$cl,'u'=>$cu]; }
                    else { $custom_tree[] = ['l'=>$cl,'u'=>$cu,'ch'=>[]]; $last_pi = count($custom_tree)-1; }
                }
                foreach ($custom_tree as $ci) {
                    $ci_href = kv_internal_url($ci['u']);
                    $ci_slug = sanitize_title($ci['l']);
                    $ci_a = ($current_slug === $ci_slug) ? ' active' : '';
                    if (!empty($ci['ch'])) {
                        echo '<li class="nav-item dropdown"><a class="nav-link dropdown-toggle'.$ci_a.'" href="'.esc_url($ci_href).'" role="button" aria-expanded="false">'.esc_html($ci['l']).'</a>';
                        echo '<ul class="dropdown-menu" style="min-width:180px;border-radius:8px;box-shadow:0 8px 30px rgba(0,0,0,.12);border:1px solid rgba(0,0,0,.07);padding:6px 0;">';
                        foreach ($ci['ch'] as $ch) {
                            $ch_href = kv_internal_url($ch['u']);
                            echo '<li><a class="dropdown-item" href="'.esc_url($ch_href).'" style="padding:8px 16px;font-size:14px;">'.esc_html($ch['l']).'</a></li>';
                        }
                        echo '</ul></li>';
                    } else {
                        echo '<li class="nav-item"><a class="nav-link'.$ci_a.'" href="'.esc_url($ci_href).'">'.esc_html($ci['l']).'</a></li>';
                    }
                }
                ?>
            </ul>
            <?php if (get_option('nav_cta_visible','1') === '1') : ?>
            <a href="<?php echo esc_url($nav_cta_href); ?>"
               class="navbar-kv-cta d-inline-flex align-items-center gap-2 mt-2 mt-lg-0 ms-lg-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-telephone" viewBox="0 0 16 16">
                    <path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.6 17.6 0 0 0 4.168 6.608 17.6 17.6 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.68.68 0 0 0-.58-.122l-2.19.547a1.745 1.745 0 0 1-1.657-.459L5.482 8.062a1.745 1.745 0 0 1-.46-1.657l.548-2.19a.68.68 0 0 0-.122-.58z"/>
                </svg>
                <?php echo esc_html($cta_label); ?>
            </a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<script>
(function(){
    var navbar=document.querySelector('.navbar.sticky-top');
    if(!navbar)return;
    var lastY=window.pageYOffset||0,hideAfter=60,ticking=false,isHidden=false;
    function setH(h){if(isHidden===h)return;isHidden=h;navbar.classList.toggle('nav-hidden',h);}
    function onS(){var y=window.pageYOffset||document.documentElement.scrollTop||0;if(y<hideAfter)setH(false);else if(y>lastY)setH(true);else if(y<lastY)setH(false);lastY=y;ticking=false;}
    window.addEventListener('scroll',function(){if(!ticking){ticking=true;window.requestAnimationFrame(onS);}},{passive:true});
})();
(function(){
    document.addEventListener('DOMContentLoaded',function(){
        document.querySelectorAll('.dropdown-submenu').forEach(function(item){
            var sub=Array.prototype.find.call(item.children,function(el){
                if (!el.classList) { return false; }
                return el.classList.contains('dropdown-menu');
            });
            if(!sub)return;
            item.addEventListener('mouseenter',function(){sub.style.display='block';});
            item.addEventListener('mouseleave',function(){sub.style.display='';});
        });
        function closeAll(){document.querySelectorAll('.nav-item.dropdown').forEach(function(li){var m=li.querySelector('.dropdown-menu'),t=li.querySelector('.dropdown-toggle');if(m){m.classList.remove('show');m.style.display='';}if(t){t.classList.remove('show');t.setAttribute('aria-expanded','false');}});}
        document.querySelectorAll('.nav-item.dropdown>.nav-link.dropdown-toggle').forEach(function(a){
            a.addEventListener('click',function(e){e.preventDefault();e.stopPropagation();
                if(window.innerWidth>=992){window.location.href=this.getAttribute('href');}
                else{var menu=this.nextElementSibling;var isO=menu.classList.contains('show');closeAll();if(!isO){menu.classList.add('show');menu.style.display='block';this.classList.add('show');this.setAttribute('aria-expanded','true');}}
            });
        });
        document.querySelectorAll('.dropdown-submenu>a').forEach(function(a){
            a.addEventListener('click',function(e){if(window.innerWidth<992){e.preventDefault();e.stopPropagation();var p=this.closest('.dropdown-submenu');var s=p?Array.prototype.find.call(p.children,function(el){if(!el.classList){return false;}return el.classList.contains('dropdown-menu');}):null;if(s){var o=s.classList.contains('show');s.style.display=o?'':'block';s.classList.toggle('show',!o);}}});
        });
        document.addEventListener('click',function(e){if(!e.target.closest('.nav-item.dropdown'))closeAll();});
    });
})();
</script>
    <?php
    return ob_get_clean();
});


/* ================================================================
   [kv_footer] — Full Bootstrap 5 footer (same as footer.php)
   ================================================================ */
add_shortcode('kv_footer', function () {
    ob_start();

    $footer_phone_raw   = function_exists('kv_get_site_phone_raw_display')
        ? kv_get_site_phone_raw_display('+66 2 108 8521')
        : trim((string) get_option('site_phone', get_theme_mod('site_phone', '+66 2 108 8521')));
    $footer_phone_label = $footer_phone_raw !== '' ? $footer_phone_raw : '+66 2 108 8521';
    $footer_phone_href  = preg_replace('/[^0-9+]/', '', (string) $footer_phone_raw);
    $footer_email       = get_option('site_email', get_theme_mod('site_email', 'info@company.com'));
    $footer_address     = get_option('site_address', get_theme_mod('site_address', '123 Industrial Zone, Bangkok, Thailand'));
    $sort_order         = my_theme_get_product_category_order();
    $footer_cats        = get_terms(['taxonomy' => 'product_category', 'parent' => 0, 'hide_empty' => false, 'orderby' => 'id', 'order' => $sort_order]);
    $footer_about       = get_option('footer_about_text', 'Founded in 1988, KV Electronics Co., Ltd. has grown from a shared passion between two industry experts into a trusted manufacturer of high-quality magnetic components and electronic solutions.');
    $footer_links_raw   = get_option('footer_quick_links', "About Us|/about\nContact|/contact");
    $company_name       = get_option('site_company_name', 'Electronic Components Co., Ltd.');
    $copyright_text     = get_option('site_copyright', 'All rights reserved.');

    $line_id     = get_option('chat_line_id', 'kriangkrai2042');
    $fb_url      = get_option('social_facebook_url', 'https://www.facebook.com/KVElectronicsTH/');
    $ig_url      = get_option('social_instagram_url', 'https://www.instagram.com/kvelectronicsth/');
    $linkedin_url = get_option('social_linkedin_url', 'https://www.linkedin.com/company/kv-electronics-co-ltd');
    ?>
<footer class="footer-dark pt-5 pb-4">
    <div class="container">
        <div class="row g-4 mb-4">
            <div class="col-sm-6 col-lg-3">
                <h5 class="fw-semibold mb-3"><?php echo esc_html(get_option('footer_col1_title','About Us')); ?></h5>
                <p class="small" style="color: #cbd5e1;">
                    <?php echo nl2br(esc_html($footer_about)); ?>
                </p>
            </div>
            <div class="col-sm-6 col-lg-3">
                <h5 class="fw-semibold mb-3"><?php echo esc_html(get_option('footer_col2_title','Products')); ?></h5>
                <ul class="list-unstyled small" style="color: #cbd5e1;">
                    <?php if ($footer_cats && !is_wp_error($footer_cats)) :
                        foreach ($footer_cats as $fcat) : ?>
                    <li class="mb-1"><a href="<?php echo esc_url(kv_internal_url(get_term_link($fcat))); ?>" style="color: #cbd5e1; text-decoration: none;"><?php echo esc_html($fcat->name); ?></a></li>
                    <?php endforeach; endif; ?>
                </ul>
            </div>
            <div class="col-sm-6 col-lg-3">
                <h5 class="fw-semibold mb-3"><?php echo esc_html(get_option('footer_col3_title','Quick Links')); ?></h5>
                <ul class="list-unstyled small" style="color: #cbd5e1;">
                    <?php
                    $fll = array_filter(array_map('trim', explode("\n", $footer_links_raw)));
                    foreach ($fll as $fl) :
                        $pts = explode('|', $fl, 2);
                        if (count($pts) === 2) :
                            $ll = trim($pts[0]); $lu = trim($pts[1]);
                            $fu = kv_internal_url($lu);
                    ?>
                    <li class="mb-1"><a href="<?php echo esc_url($fu); ?>" style="color: #cbd5e1; text-decoration: none;"><?php echo esc_html($ll); ?></a></li>
                    <?php endif; endforeach; ?>
                </ul>
            </div>
            <div class="col-sm-6 col-lg-3">
                <h5 class="fw-semibold mb-3"><?php echo esc_html(get_option('footer_col4_title','Contact Info')); ?></h5>
                <ul class="list-unstyled small" style="color: #cbd5e1;">
                    <li class="mb-2">📍 <?php echo esc_html($footer_address); ?></li>
                </ul>
                <div style="display:flex;gap:16px;align-items:flex-start;margin-top:18px;">
                    <div>
                        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px;">
                            <?php if (!empty($footer_phone_href)) : ?>
                                <a href="tel:<?php echo esc_attr($footer_phone_href); ?>" style="display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border-radius:999px;background:#0f172a;border:1px solid #334155;color:#fff;text-decoration:none;font-size:13px;">📞 <?php echo esc_html($footer_phone_label); ?></a>
                            <?php endif; ?>
                            <?php if (!empty($footer_email)) : ?>
                                <a href="mailto:<?php echo esc_attr($footer_email); ?>" style="display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border-radius:999px;background:#0f172a;border:1px solid #334155;color:#fff;text-decoration:none;font-size:13px;">✉️ <?php echo esc_html($footer_email); ?></a>
                            <?php endif; ?>
                        </div>
                        <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                            <?php if ($line_id) : ?>
                            <a href="https://line.me/ti/p/~<?php echo esc_attr($line_id); ?>" target="_blank" rel="noopener noreferrer" style="width:48px;height:48px;border-radius:50%;background:#06C755;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(6,199,85,0.35);transition:transform .2s;text-decoration:none;" aria-label="Chat on LINE" onmouseenter="this.style.transform='scale(1.12)'" onmouseleave="this.style.transform='scale(1)'">
                                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="#fff"><path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386a.63.63 0 0 1-.63-.629V8.108a.63.63 0 0 1 .63-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016a.63.63 0 0 1-.63.629.626.626 0 0 1-.51-.262l-2.397-3.265v2.898a.63.63 0 0 1-.63.629.627.627 0 0 1-.629-.629V8.108a.63.63 0 0 1 .63-.63c.2 0 .38.095.51.262l2.397 3.265V8.108a.63.63 0 0 1 .63-.63.63.63 0 0 1 .629.63v4.771zm-5.741 0a.63.63 0 0 1-1.26 0V8.108a.63.63 0 0 1 1.26 0v4.771zm-2.527.629H4.856a.63.63 0 0 1-.63-.629V8.108a.63.63 0 0 1 1.26 0v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629zM24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"></path></svg>
                            </a>
                            <?php endif; ?>
                            <?php if ($fb_url) : ?>
                            <a href="<?php echo esc_url($fb_url); ?>" target="_blank" rel="noopener noreferrer" style="width:48px;height:48px;border-radius:50%;background:#1877F2;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(24,119,242,0.35);transition:transform .2s;text-decoration:none;" aria-label="Open Facebook" onmouseenter="this.style.transform='scale(1.12)'" onmouseleave="this.style.transform='scale(1)'">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="#fff"><path d="M13.5 22v-8h2.7l.4-3h-3.1V9.1c0-.9.3-1.6 1.6-1.6h1.7V4.7c-.3 0-1.3-.1-2.4-.1-2.4 0-4 1.4-4 4.2V11H8v3h2.4v8h3.1z"></path></svg>
                            </a>
                            <?php endif; ?>
                            <?php if ($ig_url) : ?>
                            <a href="<?php echo esc_url($ig_url); ?>" target="_blank" rel="noopener noreferrer" style="width:48px;height:48px;border-radius:50%;background:radial-gradient(circle at 30% 107%, rgb(253, 244, 151) 0%, rgb(253, 244, 151) 5%, rgb(253, 89, 73) 45%, rgb(214, 36, 159) 60%, rgb(40, 90, 235) 90%);display:flex;align-items:center;justify-content:center;box-shadow:rgba(214, 36, 159, 0.35) 0px 4px 14px;transition:transform 0.2s;text-decoration:none;" aria-label="Open Instagram" onmouseenter="this.style.transform='scale(1.12)'" onmouseleave="this.style.transform='scale(1)'">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="5" ry="5"></rect><circle cx="12" cy="12" r="4"></circle><circle cx="17.5" cy="6.5" r="1"></circle></svg>
                            </a>
                            <?php endif; ?>
                            <?php if ($linkedin_url) : ?>
                            <a href="<?php echo esc_url($linkedin_url); ?>" target="_blank" rel="noopener noreferrer" style="width:48px;height:48px;border-radius:50%;background:rgb(10, 102, 194);display:flex;align-items:center;justify-content:center;box-shadow:rgba(10, 102, 194, 0.35) 0px 4px 14px;transition:transform 0.2s;text-decoration:none;" aria-label="Open LinkedIn" onmouseenter="this.style.transform='scale(1.12)'" onmouseleave="this.style.transform='scale(1)'">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="#fff"><path d="M6.94 8.5a1.56 1.56 0 1 1 0-3.12 1.56 1.56 0 0 1 0 3.12zM5.5 9.75h2.9V19h-2.9V9.75zM10.2 9.75h2.78v1.26h.04c.39-.73 1.34-1.5 2.75-1.5 2.94 0 3.48 1.93 3.48 4.44V19h-2.9v-4.47c0-1.07-.02-2.45-1.49-2.45-1.5 0-1.73 1.17-1.73 2.37V19H10.2V9.75z"></path></svg>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <hr class="footer-divider">
        <p class="text-center small mb-0 pt-3" style="color: #cbd5e1;">
            &copy; <?php echo wp_date('Y'); ?> <?php echo esc_html($company_name); ?> <?php echo esc_html($copyright_text); ?>
        </p>
    </div>
</footer>
    <?php
    return ob_get_clean();
});
