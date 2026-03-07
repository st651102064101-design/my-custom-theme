<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
    <style>
        :root {
            --theme-primary: <?php echo esc_html(get_option('theme_primary_color', '#0056d6')); ?>;
            --theme-primary-dark: <?php echo esc_html(get_option('theme_primary_dark_color', '#0049b4')); ?>;
            --theme-accent: <?php echo esc_html(get_option('theme_accent_color', '#4ecdc4')); ?>;
        }
        body { font-family: 'Sarabun', sans-serif; }
        .product-card { border-radius: 12px; overflow: hidden; transition: transform .2s, box-shadow .2s; }
        .product-card:hover { transform: translateY(-4px); box-shadow: 0 12px 40px rgba(0,0,0,.15)!important; }
        .product-card img { width: 100%; height: 220px; object-fit: cover; }
        .footer-dark { background-color: #1e293b; }
        .footer-dark a { color: #94a3b8; text-decoration: none; }
        .footer-dark a:hover { color: var(--theme-primary); }
        .footer-divider { border-color: #334155; }
        /* ── Sticky header for block-template pages ──
        /* WP admin bar fix */
        .navbar.sticky-top { top: 0; }
        body.logged-in .navbar.sticky-top { top: 32px; }
        @media screen and (max-width: 782px) {
            body.logged-in .navbar.sticky-top { top: 46px; }
        }
        /* ── Hover dropdown (desktop) ── */
        @media (min-width: 992px) {
            .nav-item.dropdown:hover > .dropdown-menu {
                display: block;
                margin-top: 0;
            }
            /* Prevent click-toggle conflict on desktop */
            .nav-item.dropdown > .dropdown-toggle {
                pointer-events: auto;
            }
            /* Nested submenu: slide out to the right */
            .dropdown-menu .dropdown-submenu {
                position: relative;
            }
            .dropdown-menu .dropdown-submenu > .dropdown-menu {
                display: none;
                position: absolute;
                top: -4px;
                left: 100%;
                min-width: 220px;
                border-radius: 8px;
                box-shadow: 0 8px 30px rgba(0,0,0,.12);
                border: 1px solid rgba(0,0,0,.07);
            }
            .dropdown-menu .dropdown-submenu:hover > .dropdown-menu {
                display: block;
            }
        }
        /* Submenu arrow indicator */
        .dropdown-submenu > a::after {
            content: '';
            display: inline-block;
            width: 0; height: 0;
            border-top: 4px solid transparent;
            border-bottom: 4px solid transparent;
            border-left: 5px solid #6c757d;
            float: right;
            margin-top: 5px;
        }
        /* Ensure top-level dropdown chevron is always visible (incl. mobile) */
        .nav-item.dropdown > .nav-link.dropdown-toggle::after {
            display: inline-block !important;
            content: "" !important;
            border-top: .3em solid !important;
            border-right: .3em solid transparent !important;
            border-bottom: 0 !important;
            border-left: .3em solid transparent !important;
            margin-left: .3em;
            vertical-align: .2em;
        }
        /* Submenu item styling */
        .dropdown-menu .dropdown-submenu > .dropdown-menu .dropdown-item {
            font-size: 14px;
            padding: 7px 16px;
            color: #1e293b;
        }
        .dropdown-menu .dropdown-submenu > .dropdown-menu .dropdown-item:hover,
        .dropdown-menu .dropdown-submenu > .dropdown-menu .dropdown-item:focus {
            background-color: #f0f5ff !important;
            color: var(--theme-primary) !important;
        }
        .dropdown-menu .dropdown-submenu > .dropdown-menu .dropdown-item.active {
            background-color: #f0f5ff !important;
            color: var(--theme-primary) !important;
            font-weight: 600;
        }
        /* Parent category active + hover */
        .dropdown-menu > li > .dropdown-item.active,
        .dropdown-menu > li.dropdown-submenu > .dropdown-item.active,
        .dropdown-menu > li > .dropdown-item:hover,
        .dropdown-menu > li > .dropdown-item:focus {
            background-color: #f0f5ff !important;
            color: var(--theme-primary) !important;
        }
        /* Keep parent highlighted while its submenu is open */
        .dropdown-menu > li.dropdown-submenu:hover > .dropdown-item {
            background-color: #f0f5ff !important;
            color: var(--theme-primary) !important;
        }
        .dropdown-menu > li.dropdown-submenu:hover > a::after {
            border-left-color: var(--theme-primary);
        }
        .dropdown-menu > li > .dropdown-item.dropdown-view-all-link:hover,
        .dropdown-menu > li > .dropdown-item.dropdown-view-all-link:focus {
            background-color: #f0f5ff !important;
            color: var(--theme-primary) !important;
        }
        .dropdown-menu > li > .dropdown-item.dropdown-view-all-link:active,
        .dropdown-menu > li > .dropdown-item.dropdown-view-all-link.active {
            background-color: #fff !important;
            color: var(--theme-primary) !important;
        }
        /* Extra nav items beyond 4 — hidden until toggled */
        .nav-cat-extra { display: none !important; }
        .nav-cat-extra.nav-cat-visible { display: flex !important; }
        #nav-cat-showmore:hover { background-color: var(--theme-primary-light, #e8f0fe); color: var(--theme-primary); }
        /* "View All" links at bottom of dropdown — NOT using .dropdown-item to avoid blue hover */
        .dropdown-view-all {
            display: block;
            font-size: 13px;
            padding: 8px 16px;
            color: var(--theme-primary);
            background-color: transparent;
            font-weight: 500;
            text-decoration: none;
            white-space: nowrap;
        }
        .dropdown-view-all:hover,
        .dropdown-view-all:focus {
            background-color: #f0f5ff;
            color: var(--theme-primary);
        }
        .dropdown-menu > li:last-child > .dropdown-view-all {
            border-bottom-left-radius: 8px;
            border-bottom-right-radius: 8px;
        }
    </style>
</head>
<body <?php body_class('bg-white'); ?>>
<?php wp_body_open(); ?>

<?php
// Determine active state for nav
$is_product_page   = false;
$active_cat_slug   = '';
$active_term_id    = 0;   // child-level term currently being viewed
$active_product_id = 0;   // product currently being viewed

if (is_tax('product_category')) {
    $is_product_page = true;
    $queried_term    = get_queried_object();
    $active_term_id  = (int) $queried_term->term_id;
    $root = $queried_term;
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
        $selected_term = null;
        $selected_depth = -1;
        foreach ($terms as $term_item) {
            $depth = 0;
            $walker = $term_item;
            while ($walker->parent) {
                $parent_term = get_term($walker->parent, 'product_category');
                if ($parent_term && !is_wp_error($parent_term)) {
                    $depth++;
                    $walker = $parent_term;
                } else {
                    break;
                }
            }
            if ($depth > $selected_depth) {
                $selected_depth = $depth;
                $selected_term = $term_item;
            }
        }
        if (!$selected_term) {
            $selected_term = $terms[0];
        }
        $active_term_id = (int) $selected_term->term_id;
        $root = $selected_term;
        while ($root->parent) {
            $p = get_term($root->parent, 'product_category');
            if ($p && !is_wp_error($p)) { $root = $p; } else { break; }
        }
        $active_cat_slug = $root->slug;
    }
} elseif (is_page()) {
    $current_slug = get_post_field('post_name', get_queried_object_id());
    if ($current_slug === 'products') {
        $is_product_page = true;
    }
}

// Get product categories for dropdown (parent terms only) - sort by option setting
$sort_order = my_theme_get_product_category_order();
$_test_term  = get_term_by('slug', 'test', 'product_category');
$_exclude_ids = $_test_term ? [$_test_term->term_id] : [];
$nav_categories = get_terms([
    'taxonomy'   => 'product_category',
    'parent'     => 0,
    'hide_empty' => false,
    'orderby'    => 'id',
    'order'      => 'DESC',
]);

// Pre-fetch products for each parent category (for submenu)
$nav_cat_products = [];
if ($nav_categories && !is_wp_error($nav_categories)) {
    foreach ($nav_categories as $cat) {
        // Get child terms for this category
        $child_terms = get_terms(['taxonomy' => 'product_category', 'parent' => $cat->term_id, 'hide_empty' => false]);
        if ($child_terms && !is_wp_error($child_terms) && count($child_terms) > 0) {
            // Has child categories — use them as submenu
            $nav_cat_products[$cat->term_id] = ['type' => 'terms', 'items' => $child_terms];
        } else {
            // No child categories — show products directly
            $prods = get_posts([
                'post_type'      => 'product',
                'posts_per_page' => 10,
                'post_status'    => 'publish',
                'tax_query'      => [[
                    'taxonomy' => 'product_category',
                    'field'    => 'term_id',
                    'terms'    => $cat->term_id,
                ]],
            ]);
            $nav_cat_products[$cat->term_id] = ['type' => 'products', 'items' => $prods];
        }
    }
}

$current_slug = is_page() ? get_post_field('post_name', get_queried_object_id()) : '';
$current_request_path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
$current_segments = array_values(array_filter(explode('/', $current_request_path), 'strlen'));
$products_segment_index = array_search('products', $current_segments, true);
$current_products_parent_segment = ($products_segment_index !== false && isset($current_segments[$products_segment_index + 1]))
    ? $current_segments[$products_segment_index + 1]
    : '';
$current_products_child_segment = ($products_segment_index !== false && isset($current_segments[$products_segment_index + 2]))
    ? $current_segments[$products_segment_index + 2]
    : '';
$current_products_last_segment = ($products_segment_index !== false && !empty($current_segments))
    ? end($current_segments)
    : '';
?>

<!-- ===================== NAVBAR ===================== -->
<?php
/* ── Read all Navbar style settings ── */
$_nav_bg         = get_option('nav_bg_color', '#ffffff');
$_nav_txt        = get_option('nav_text_color', '');
$_nav_hover      = get_option('nav_hover_color', '');
$_nav_active     = get_option('nav_active_color', '');
$_nav_fs         = (int) get_option('nav_font_size', 16);
$_nav_fw         = get_option('nav_font_weight', '500');
$_nav_align      = get_option('nav_align', 'center');
$_nav_sticky     = get_option('nav_sticky', '1');
$_nav_shadow     = get_option('nav_shadow', '1');
$_nav_py         = (int) get_option('nav_padding_y', 8);
$_nav_logo_h     = (int) get_option('nav_logo_height', 50);
$_nav_cta_bg     = get_option('nav_cta_bg', '');
$_nav_cta_txt    = get_option('nav_cta_text_color', '#ffffff');
$_nav_cta_rad    = (int) get_option('nav_cta_radius', 6);
$_nav_cta_fs     = (int) get_option('nav_cta_font_size', 14);

/* Alignment class: left = ms-0 me-auto, center = mx-auto, right = ms-auto me-0 */
$_nav_align_class = 'mx-auto';
if ($_nav_align === 'left')  $_nav_align_class = 'ms-0 me-auto';
if ($_nav_align === 'right') $_nav_align_class = 'ms-auto me-0';

/* Build dynamic nav classes */
$_nav_classes = 'navbar navbar-expand-lg';
$_nav_classes .= ($_nav_bg && strtolower($_nav_bg) !== '#ffffff' && strtolower($_nav_bg) !== '#fff') ? ' navbar-dark' : ' navbar-light';
$_nav_classes .= ($_nav_shadow === '1') ? ' shadow-sm' : '';
$_nav_classes .= ($_nav_sticky === '1') ? ' sticky-top' : '';

/* Hover / active colors fallback */
$_hover_c  = $_nav_hover  ?: 'var(--theme-primary)';
$_active_c = $_nav_active ?: 'var(--theme-primary)';
$_cta_bg_c = $_nav_cta_bg ?: 'var(--theme-primary)';
?>
<style>
/* ── Dynamic Navbar Styles ── */
#navbarMain .nav-link {
    <?php if ($_nav_txt) : ?>color: <?php echo esc_html($_nav_txt); ?> !important;<?php endif; ?>
    font-size: <?php echo $_nav_fs; ?>px;
    font-weight: <?php echo esc_html($_nav_fw); ?>;
    transition: color .2s;
}
#navbarMain .nav-link:hover,
#navbarMain .nav-link:focus {
    color: <?php echo esc_html($_hover_c); ?> !important;
}
#navbarMain .nav-link.active {
    color: <?php echo esc_html($_active_c); ?> !important;
    font-weight: <?php echo max((int)$_nav_fw, 600); ?>;
}
.navbar-kv-cta {
    background: <?php echo esc_html($_cta_bg_c); ?> !important;
    color: <?php echo esc_html($_nav_cta_txt); ?> !important;
    border-radius: <?php echo $_nav_cta_rad; ?>px !important;
    font-size: <?php echo $_nav_cta_fs; ?>px !important;
    font-weight: 600;
    padding: 8px 18px;
    border: none;
    text-decoration: none;
    white-space: nowrap;
    transition: opacity .2s;
}
.navbar-kv-cta:hover { opacity: .85; color: <?php echo esc_html($_nav_cta_txt); ?> !important; }

.navbar-brand-kv {
    display: inline-flex;
    flex-direction: column;
    align-items: flex-start;
    text-decoration: none;
}
.navbar-brand-kv img {
    display: block;
}
.navbar-brand-tagline {
    font-size: 13px;
    line-height: 1.3;
    font-weight: 600;
    color: <?php echo esc_html($_nav_txt ?: 'var(--theme-primary)'); ?>;
    opacity: 1;
    margin-top: 3px;
    white-space: nowrap;
}
</style>
<nav class="<?php echo esc_attr($_nav_classes); ?>" style="background-color:<?php echo esc_attr($_nav_bg); ?>;padding-top:<?php echo $_nav_py; ?>px;padding-bottom:<?php echo $_nav_py; ?>px;">
    <div class="container">
        <a class="navbar-brand navbar-brand-kv" href="<?php echo esc_url(home_url('/')); ?>">
            <img src="<?php echo esc_url(kv_rebase_url(get_option('site_logo_url', home_url('/wp-content/uploads/2026/02/New-Logo-Schott-1-1-300x120-1.jpg')))); ?>"
                 alt="<?php echo esc_attr(get_option('nav_logo_alt', get_bloginfo('name'))); ?>" height="<?php echo $_nav_logo_h; ?>" style="max-height:<?php echo $_nav_logo_h; ?>px;width:auto;">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarMain" aria-controls="navbarMain"
                aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav <?php echo esc_attr($_nav_align_class); ?> mb-2 mb-lg-0">
                <?php
                /* ── Load nav items from JSON or fallback to old options ── */
                $_nav_items_json_raw = get_option('nav_menu_items_json', '');
                $_nav_items = [];
                if ($_nav_items_json_raw) {
                    $_decoded = json_decode($_nav_items_json_raw, true);
                    if (is_array($_decoded) && !empty($_decoded)) $_nav_items = $_decoded;
                }
                if (empty($_nav_items)) {
                    $_nav_items = [
                        ['id'=>'home',     'label'=>get_option('nav_home_label','Home'),           'url'=>'',                                              'type'=>'home',     'visible'=>(get_option('nav_home_visible','1')==='1'),     'new_tab'=>false],
                        ['id'=>'about',    'label'=>get_option('nav_about_label','About Us'),       'url'=>get_option('nav_about_url','/about/'),            'type'=>'custom',   'visible'=>(get_option('nav_about_visible','1')==='1'),    'new_tab'=>false],
                        ['id'=>'products', 'label'=>get_option('nav_products_label','Products'),    'url'=>'',                                              'type'=>'products', 'visible'=>(get_option('nav_products_visible','1')==='1'),'new_tab'=>false],
                        ['id'=>'contact',  'label'=>get_option('nav_contact_label','Contacts'),     'url'=>get_option('nav_contact_url','/contact/'),        'type'=>'custom',   'visible'=>(get_option('nav_contact_visible','1')==='1'),  'new_tab'=>false],
                    ];
                }
                foreach ($_nav_items as $_ni):
                    if (empty($_ni['visible'])) continue;
                    $_ni_label   = esc_html( $_ni['label'] ?? '' );
                    $_ni_type    = $_ni['type'] ?? 'custom';
                    $_ni_new_tab = !empty($_ni['new_tab']);
                    $_ni_target  = $_ni_new_tab ? ' target="_blank" rel="noopener noreferrer"' : '';

                    if ($_ni_type === 'home') : ?>
                <li class="nav-item">
                    <a class="nav-link<?php echo (is_front_page()) ? ' active' : ''; ?>" href="<?php echo esc_url(home_url('/')); ?>"<?php echo $_ni_target; ?>><?php echo $_ni_label; ?></a>
                </li>
                    <?php elseif ($_ni_type === 'products') :
                        $has_nav_cats = $nav_categories && !is_wp_error($nav_categories) && count($nav_categories) > 0;
                        if ($has_nav_cats) : ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle<?php echo $is_product_page ? ' active' : ''; ?>"
                       href="<?php echo esc_url(home_url('/products/')); ?>"
                       role="button" aria-expanded="false"<?php echo $_ni_target; ?>><?php echo $_ni_label; ?></a>
                    <ul class="dropdown-menu" style="min-width:200px;border-radius:8px;box-shadow:0 8px 30px rgba(0,0,0,.12);border:1px solid rgba(0,0,0,.07);padding:6px 0;overflow:visible;">
                        <?php if ($nav_categories && !is_wp_error($nav_categories)) : ?>
                            <?php
                            $nav_total = count($nav_categories);
                            $nav_limit = 4;
                            $nav_idx   = 0;
                            ?>
                            <?php foreach ($nav_categories as $cat) :
                                $nav_idx++;
                                $is_extra     = ($nav_idx > $nav_limit);
                                $extra_class  = $is_extra ? ' nav-cat-extra' : '';
                                $sub          = $nav_cat_products[$cat->term_id] ?? null;
                                $has_sub      = $sub && count($sub['items']) > 0;
                                $cat_link_raw  = get_term_link($cat);
                                $cat_link      = (!is_wp_error($cat_link_raw) && $cat_link_raw)
                                    ? $cat_link_raw
                                    : home_url('/products/');
                                $cat_path      = (!is_wp_error($cat_link) && $cat_link)
                                    ? trim((string) parse_url($cat_link, PHP_URL_PATH), '/')
                                    : '';
                                $cat_slug_prefix = 'products/' . $cat->slug;
                                $has_active_child = false;
                                if ($has_sub && $sub['type'] === 'terms') {
                                    foreach ($sub['items'] as $child_probe) {
                                        $child_probe_link_raw = get_term_link($child_probe);
                                        $child_probe_link = (!is_wp_error($child_probe_link_raw) && $child_probe_link_raw)
                                            ? $child_probe_link_raw
                                            : '';
                                        $child_probe_path = (!is_wp_error($child_probe_link) && $child_probe_link)
                                            ? trim((string) parse_url($child_probe_link, PHP_URL_PATH), '/')
                                            : '';
                                        $child_probe_nested = 'products/' . $cat->slug . '/' . $child_probe->slug;
                                        $child_probe_flat   = 'products/' . $child_probe->slug;
                                        if (
                                            ($active_term_id === (int) $child_probe->term_id)
                                            || ($child_probe_path !== '' && $current_request_path !== '' && ($current_request_path === $child_probe_path || strpos($current_request_path, $child_probe_path . '/') === 0))
                                            || ($current_request_path === $child_probe_nested || strpos($current_request_path, $child_probe_nested . '/') === 0)
                                            || ($current_request_path === $child_probe_flat   || strpos($current_request_path, $child_probe_flat . '/') === 0)
                                            || ($current_products_parent_segment === $cat->slug && ($current_products_child_segment === $child_probe->slug || $current_products_last_segment === $child_probe->slug))
                                        ) {
                                            $has_active_child = true;
                                            break;
                                        }
                                    }
                                }
                                $is_active_cat = ($active_cat_slug === $cat->slug)
                                    || ($cat_path !== '' && $current_request_path !== '' && ($current_request_path === $cat_path || strpos($current_request_path, $cat_path . '/') === 0))
                                    || ($current_request_path === $cat_slug_prefix || strpos($current_request_path, $cat_slug_prefix . '/') === 0)
                                    || ($current_products_parent_segment !== '' && $current_products_parent_segment === $cat->slug)
                                    || $has_active_child;

                                if (!$is_active_cat && $has_sub && $sub['type'] === 'terms' && $current_products_parent_segment === $cat->slug) {
                                    foreach ($sub['items'] as $child_probe) {
                                        if ($current_products_child_segment === $child_probe->slug || $current_products_last_segment === $child_probe->slug) {
                                            $is_active_cat = true;
                                            break;
                                        }
                                    }
                                }
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
                                                $child_link_raw = get_term_link($child);
                                                $child_link   = (!is_wp_error($child_link_raw) && $child_link_raw)
                                                    ? $child_link_raw
                                                    : home_url('/products/');
                                                $child_path   = (!is_wp_error($child_link) && $child_link)
                                                    ? trim((string) parse_url($child_link, PHP_URL_PATH), '/')
                                                    : '';
                                                $child_slug_nested = 'products/' . $cat->slug . '/' . $child->slug;
                                                $child_slug_flat   = 'products/' . $child->slug;
                                                $child_active = ($active_term_id === (int) $child->term_id)
                                                    || ($child_path !== '' && $current_request_path !== '' && ($current_request_path === $child_path || strpos($current_request_path, $child_path . '/') === 0))
                                                    || ($current_request_path === $child_slug_nested || strpos($current_request_path, $child_slug_nested . '/') === 0)
                                                    || ($current_request_path === $child_slug_flat   || strpos($current_request_path, $child_slug_flat . '/') === 0)
                                                    || ($current_products_parent_segment === $cat->slug && ($current_products_child_segment === $child->slug || $current_products_last_segment === $child->slug));
                                            ?>
                                            <li>
                                                <a class="dropdown-item<?php echo $child_active ? ' active' : ''; ?>" href="<?php echo esc_url($child_link); ?>">
                                                    <?php echo esc_html($child->name); ?>
                                                </a>
                                            </li>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <?php foreach ($sub['items'] as $prod) :
                                                $prod_link   = get_permalink($prod->ID);
                                                $prod_path   = $prod_link ? trim((string) parse_url($prod_link, PHP_URL_PATH), '/') : '';
                                                $prod_active = ($active_product_id === (int) $prod->ID)
                                                    || ($prod_path !== '' && $current_request_path !== '' && ($current_request_path === $prod_path));
                                            ?>
                                            <li>
                                                <a class="dropdown-item<?php echo $prod_active ? ' active' : ''; ?>" href="<?php echo esc_url($prod_link); ?>">
                                                    <?php echo esc_html($prod->post_title); ?>
                                                </a>
                                            </li>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li style="background:#fff;border-bottom-left-radius:8px;border-bottom-right-radius:8px;">
                                            <a class="dropdown-view-all" href="<?php echo esc_url($cat_link); ?>" style="display:block;padding:8px 16px;font-size:13px;color:var(--theme-primary);background:#fff !important;text-decoration:none;font-weight:500;border-bottom-left-radius:8px;border-bottom-right-radius:8px;">
                                                View all <?php echo esc_html($cat->name); ?> →
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                                <?php else : ?>
                                <li class="<?php echo trim($extra_class); ?>">
                                    <a class="dropdown-item<?php echo $is_active_cat ? ' active' : ''; ?>"
                                       href="<?php echo esc_url($cat_link); ?>"
                                       style="padding:9px 16px;font-size:15px;">
                                        <?php echo esc_html($cat->name); ?>
                                    </a>
                                </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <?php if ($nav_total > $nav_limit) : ?>
                            <li>
                                <button id="nav-cat-showmore" onclick="(function(btn){
                                    document.querySelectorAll('.nav-cat-extra').forEach(function(el){ el.classList.toggle('nav-cat-visible'); });
                                    var showing = btn.dataset.showing==='1';
                                    btn.innerHTML = showing
                                        ? 'Show <?php echo ($nav_total - $nav_limit); ?> more&hellip; &#9660;'
                                        : 'Show less &#9650;';
                                    btn.dataset.showing = showing ? '0' : '1';
                                })(this)" data-showing="0"
                                    style="width:100%;background:none;border:none;text-align:left;padding:7px 16px;font-size:13px;color:#64748b;cursor:pointer;">
                                    Show <?php echo ($nav_total - $nav_limit); ?> more&hellip; &#9660;
                                </button>
                            </li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider" style="margin:4px 0;"></li>
                            <li style="background:#fff;border-bottom-left-radius:8px;border-bottom-right-radius:8px;">
                                <a class="dropdown-view-all" href="<?php echo esc_url(home_url('/products/')); ?>" style="display:block;padding:8px 16px;font-size:13px;color:var(--theme-primary);background:#fff !important;text-decoration:none;font-weight:500;border-bottom-left-radius:8px;border-bottom-right-radius:8px;">
                                    View All Products →
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </li>
                        <?php else : ?>
                <li class="nav-item">
                    <a class="nav-link<?php echo $is_product_page ? ' active' : ''; ?>"
                       href="<?php echo esc_url(home_url('/products/')); ?>"<?php echo $_ni_target; ?>><?php echo $_ni_label; ?></a>
                </li>
                        <?php endif; ?>
                    <?php else :
                        /* custom / about / contact / any other type */
                        $_ni_url  = $_ni['url'] ?? '';
                        $_ni_href = (strpos($_ni_url,'http') === 0) ? $_ni_url : (trim($_ni_url) !== '' ? home_url($_ni_url) : '#');
                        $_ni_slug = sanitize_title( $_ni['label'] ?? '' );
                        $_ni_active = ($current_slug === $_ni_slug) ? ' active' : '';
                    ?>
                <li class="nav-item">
                    <a class="nav-link<?php echo $_ni_active; ?>" href="<?php echo esc_url($_ni_href); ?>"<?php echo $_ni_target; ?>><?php echo $_ni_label; ?></a>
                </li>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php
                // ── Custom Nav Items (with Dropdown support) ──
                $custom_raw = explode("\n", get_option('nav_custom_items', ''));
                $custom_tree = [];
                $last_parent_idx = -1;
                foreach ($custom_raw as $raw_line) {
                    if (trim($raw_line) === '') continue;
                    $is_sub = (strlen($raw_line) > 2 && substr($raw_line, 0, 2) === '  ');
                    $parts  = explode('|', trim($raw_line), 2);
                    $ci_label = trim($parts[0] ?? '');
                    $ci_url   = trim($parts[1] ?? '#');
                    if (empty($ci_label)) continue;
                    if ($is_sub && $last_parent_idx >= 0) {
                        $custom_tree[$last_parent_idx]['children'][] = ['label' => $ci_label, 'url' => $ci_url];
                    } else {
                        $custom_tree[] = ['label' => $ci_label, 'url' => $ci_url, 'children' => []];
                        $last_parent_idx = count($custom_tree) - 1;
                    }
                }
                foreach ($custom_tree as $ci) {
                    $ci_href = (strpos($ci['url'], 'http') === 0) ? $ci['url'] : home_url($ci['url']);
                    $ci_slug = sanitize_title($ci['label']);
                    $ci_active = ($current_slug === $ci_slug) ? ' active' : '';
                    if (!empty($ci['children'])) {
                        echo '<li class="nav-item dropdown">';
                        echo '<a class="nav-link dropdown-toggle' . $ci_active . '" href="' . esc_url($ci_href) . '" role="button" aria-expanded="false">' . esc_html($ci['label']) . '</a>';
                        echo '<ul class="dropdown-menu" style="min-width:180px;border-radius:8px;box-shadow:0 8px 30px rgba(0,0,0,.12);border:1px solid rgba(0,0,0,.07);padding:6px 0;">';
                        foreach ($ci['children'] as $child) {
                            $child_href = (strpos($child['url'], 'http') === 0) ? $child['url'] : home_url($child['url']);
                            echo '<li><a class="dropdown-item" href="' . esc_url($child_href) . '" style="padding:8px 16px;font-size:14px;">' . esc_html($child['label']) . '</a></li>';
                        }
                        echo '</ul></li>';
                    } else {
                        echo '<li class="nav-item">';
                        echo '<a class="nav-link' . $ci_active . '" href="' . esc_url($ci_href) . '">' . esc_html($ci['label']) . '</a>';
                        echo '</li>';
                    }
                }
                ?>
            </ul>
            <?php
            $nav_cta_text = get_option('nav_cta_text','');
            $nav_cta_url  = get_option('nav_cta_url','/contact/');
            $default_phone_raw = '+66 2 108 8521';
            $site_phone_raw = get_option('site_phone', get_theme_mod('site_phone', $default_phone_raw));
            if (function_exists('kv_clean_text_option_value')) {
                $site_phone_raw = kv_clean_text_option_value($site_phone_raw, $default_phone_raw);
            }
            $site_phone_display = trim((string) $site_phone_raw);
            if ($site_phone_display === '') {
                $site_phone_display = $default_phone_raw;
            }

            $nav_cta_href = (strpos($nav_cta_url,'http') === 0) ? $nav_cta_url : home_url($nav_cta_url);
            if (trim((string) $nav_cta_text) === '' && trim((string) $site_phone_raw) !== '') {
                $phone_tel = preg_replace('/[^0-9+]/', '', (string) $site_phone_raw);
                if ($phone_tel) {
                    $nav_cta_href = 'tel:' . $phone_tel;
                }
            }
            ?>
            <?php if (get_option('nav_cta_visible','1') === '1') : ?>
            <a href="<?php echo esc_url($nav_cta_href); ?>"
                    class="navbar-kv-cta d-inline-flex align-items-center gap-2 mt-2 mt-lg-0 ms-lg-3">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                     class="bi bi-telephone" viewBox="0 0 16 16">
                    <path d="M3.654 1.328a.678.678 0 0 0-1.015-.063L1.605 2.3c-.483.484-.661 1.169-.45 1.77a17.6 17.6 0 0 0 4.168 6.608 17.6 17.6 0 0 0 6.608 4.168c.601.211 1.286.033 1.77-.45l1.034-1.034a.678.678 0 0 0-.063-1.015l-2.307-1.794a.68.68 0 0 0-.58-.122l-2.19.547a1.745 1.745 0 0 1-1.657-.459L5.482 8.062a1.745 1.745 0 0 1-.46-1.657l.548-2.19a.68.68 0 0 0-.122-.58z"/>
                </svg>
                <?php echo esc_html($nav_cta_text ? $nav_cta_text : $site_phone_display); ?>
            </a>
            <?php endif; ?>
        </div>
    </div>
</nav><script>
/* ── Sticky navbar: hide on scroll down, show on scroll up ── */
(function () {
    var navbar = document.querySelector('.navbar.sticky-top');
    if (!navbar) return;
    var lastY = window.pageYOffset || 0;
    var hideAfter = 60;
    var ticking = false;
    var isHidden = false;

    function setNavbarHidden(hidden) {
        if (isHidden === hidden) return;
        isHidden = hidden;
        navbar.classList.toggle('nav-hidden', hidden);
    }

    function onScroll() {
        var y = window.pageYOffset || document.documentElement.scrollTop || 0;
        if (y < hideAfter) {
            setNavbarHidden(false);
        } else if (y > lastY) {
            setNavbarHidden(true);
        } else if (y < lastY) {
            setNavbarHidden(false);
        }
        lastY = y;
        ticking = false;
    }

    window.addEventListener('scroll', function () {
        if (!ticking) {
            ticking = true;
            window.requestAnimationFrame(onScroll);
        }
    }, { passive: true });
})();

(function () {
    document.addEventListener('DOMContentLoaded', function () {
        /* ── Submenu hover on desktop ── */
        document.querySelectorAll('.dropdown-submenu').forEach(function (item) {
            var sub = Array.prototype.find.call(item.children, function (el) {
                return el.classList && el.classList.contains('dropdown-menu');
            });
            if (!sub) return;
            item.addEventListener('mouseenter', function () { sub.style.display = 'block'; });
            item.addEventListener('mouseleave', function () { sub.style.display = ''; });
        });

        /* ── Helper: close all open dropdowns ── */
        function closeAllDropdowns() {
            document.querySelectorAll('.nav-item.dropdown').forEach(function (li) {
                li.querySelector('.dropdown-menu').classList.remove('show');
                li.querySelector('.dropdown-menu').style.display = '';
                li.querySelector('.dropdown-toggle').classList.remove('show');
                li.querySelector('.dropdown-toggle').setAttribute('aria-expanded', 'false');
            });
        }

        /* ── Top-level dropdown: desktop = navigate, mobile = toggle ── */
        document.querySelectorAll('.nav-item.dropdown > .nav-link.dropdown-toggle').forEach(function (a) {
            a.addEventListener('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (window.innerWidth >= 992) {
                    window.location.href = this.getAttribute('href');
                } else {
                    var menu = this.nextElementSibling;
                    var isOpen = menu.classList.contains('show');
                    closeAllDropdowns();
                    if (!isOpen) {
                        menu.classList.add('show');
                        menu.style.display = 'block';
                        this.classList.add('show');
                        this.setAttribute('aria-expanded', 'true');
                    }
                }
            });
        });

        /* ── Submenu click toggle on mobile ── */
        document.querySelectorAll('.dropdown-submenu > a').forEach(function (a) {
            a.addEventListener('click', function (e) {
                if (window.innerWidth < 992) {
                    e.preventDefault();
                    e.stopPropagation();
                    var submenuParent = this.closest('.dropdown-submenu');
                    var sub = submenuParent ? Array.prototype.find.call(submenuParent.children, function (el) {
                        return el.classList && el.classList.contains('dropdown-menu');
                    }) : null;
                    if (sub) {
                        var isOpen = sub.classList.contains('show');
                        sub.style.display = isOpen ? '' : 'block';
                        sub.classList.toggle('show', !isOpen);
                    }
                }
            });
        });

        /* ── Close dropdown on outside click ── */
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.nav-item.dropdown')) {
                closeAllDropdowns();
            }
        });

    });
})();
</script>