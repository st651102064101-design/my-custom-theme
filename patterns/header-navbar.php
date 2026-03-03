<?php
/**
 * Title: Header Navbar (Bootstrap 5)
 * Slug: my-custom-theme/header-navbar
 * Categories: my-special-design
 * Description: Header พร้อม Logo, Navigation Menu และเบอร์โทรศัพท์ (Bootstrap 5.3)
 */

// ── Load options from DB ──
$logo         = kv_rebase_url( get_option('site_logo_url', home_url('/wp-content/uploads/2026/02/New-Logo-Schott-1-1-300x120-1.jpg')) );
$nav_logo_alt = get_option('nav_logo_alt',  'Company Logo');
$phone        = kv_format_phone_th(get_option('site_phone', ''));
?>
<!-- wp:html -->
<style>
.navbar-brand-kv {
    display:inline-flex;
    flex-direction:column;
    align-items:flex-start;
    text-decoration:none;
}
.navbar-brand-kv img { display:block; }
.navbar-brand-tagline {
    font-size:13px;
    line-height:1.3;
    font-weight:600;
    color:#2563eb;
    margin-top:3px;
    white-space:nowrap;
}
</style>
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand navbar-brand-kv" href="/">
            <img src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_attr($nav_logo_alt); ?>" height="50">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMain">
            <?php
            wp_nav_menu([
                'theme_location' => 'primary',
                'container'      => false,
                'menu_class'     => 'navbar-nav mx-auto mb-2 mb-lg-0',
                'walker'         => new My_Bootstrap5_Walker(),
                'fallback_cb'    => function() {
                    // Fallback: แสดงเมนูพื้นฐานถ้ายังไม่ได้ตั้งค่า Menu
                    echo '<ul class="navbar-nav mx-auto mb-2 mb-lg-0">';
                    echo '<li class="nav-item"><a class="nav-link active" href="/">Home</a></li>';
                    echo '<li class="nav-item"><a class="nav-link" href="/about">About Us</a></li>';
                    $cats = get_terms(['taxonomy'=>'product_category','parent'=>0,'hide_empty'=>false]);
                    if (!empty($cats) && !is_wp_error($cats)) {
                        echo '<li class="nav-item dropdown">';
                        echo '<a class="nav-link dropdown-toggle" href="/products" data-bs-toggle="dropdown" aria-expanded="false">Products</a>';
                        echo '<ul class="dropdown-menu">';
                        foreach ($cats as $cat) {
                            echo '<li><a class="dropdown-item" href="' . esc_url(get_term_link($cat)) . '">' . esc_html($cat->name) . '</a></li>';
                        }
                        echo '</ul></li>';
                    } else {
                        echo '<li class="nav-item"><a class="nav-link" href="/products">Products</a></li>';
                    }
                    echo '<li class="nav-item"><a class="nav-link" href="/contact">Contacts</a></li>';
                    echo '</ul>';
                    echo '<div style="background:#fff3cd;padding:8px 12px;font-size:12px;color:#856404;">⚠️ ยังไม่ได้ตั้งค่าเมนู — <a href="' . esc_url(admin_url('nav-menus.php')) . '">ไปตั้งค่า Appearance › Menus</a></div>';
                },
            ]);
            ?>
            <span class="navbar-text d-none d-lg-block">
                📞 <?php echo esc_html($phone); ?>
            </span>
        </div>
    </div>
</nav>
<!-- /wp:html -->
