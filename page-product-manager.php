<?php
/**
 * Template Name: จัดการสินค้า (Product Manager)
 * 
 * หน้าจัดการสินค้า — แก้ไขได้ผ่าน Block Editor
 * SPA-like product CRUD interface on the front-end
 * เฉพาะ admin เท่านั้นที่เข้าถึงได้
 */

// ── Guard: admin only ──
if (!is_user_logged_in() || !current_user_can('manage_options')) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

get_header();
?>

<!-- ===================== PAGE BANNER ===================== -->
<?php if (have_posts()) : while (have_posts()) : the_post();
    get_template_part('parts/page-banner');
?>

<!-- ===================== BLOCK EDITOR CONTENT ===================== -->
<main id="main-content" class="site-main">
    <?php
    // Render any block editor content the user has added
    $content = get_the_content();
    if (trim($content)) {
        echo '<div class="container py-4">';
        the_content();
        echo '</div>';
    }
    ?>
</main>

<?php endwhile; endif; ?>

<!-- ===================== PRODUCT MANAGER APP ===================== -->
<section class="product-manager-section">
    <?php pm_render_admin_page(); ?>
</section>

<?php get_footer(); ?>
