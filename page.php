<?php
/**
 * Template: Page
 * 
 * แสดงหน้า Page ของ WordPress — ใช้ the_content() เพื่อแสดง block content
 */

get_header(); ?>

<!-- ===================== PAGE BANNER ===================== -->
<?php if (have_posts()) : while (have_posts()) : the_post();
    get_template_part('parts/page-banner');
?>

<!-- ===================== PAGE CONTENT ===================== -->
    <main id="main-content" class="site-main">
        <?php the_content(); ?>
    </main>
<?php endwhile; endif; ?>

<?php get_footer(); ?>
