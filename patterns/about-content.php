<?php
/**
 * Title: About Content
 * Slug: my-custom-theme/about-content
 * Categories: my-special-design
 * Description: เนื้อหา About 2 คอลัมน์พร้อมรูปภาพ
 */
$_about_heading = get_option('about_s1_heading', '');
$_about_text1   = get_option('about_s1_text1',   '');
$_about_text2   = get_option('about_s1_text2',   '');
$_about_text3   = get_option('about_s1_text3',   '');
$_about_image   = get_option('about_s1_image',   '');
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"60px","bottom":"60px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:60px;padding-bottom:60px">
    <!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"50px"}}},"verticalAlignment":"center"} -->
    <div class="wp-block-columns are-vertically-aligned-center">
        <!-- wp:column {"verticalAlignment":"center"} -->
        <div class="wp-block-column is-vertically-aligned-center">
            <!-- wp:shortcode -->[kv_about_intro]<!-- /wp:shortcode -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column {"verticalAlignment":"center"} -->
        <div class="wp-block-column is-vertically-aligned-center">
            <?php if ($_about_image) : ?>
            <!-- wp:image {"sizeSlug":"large","style":{"border":{"radius":"12px"}}} -->
            <figure class="wp-block-image size-large" style="border-radius:12px"><img src="<?php echo esc_url($_about_image); ?>" alt="<?php echo esc_attr($_about_heading ?: 'About Us'); ?>"/></figure>
            <!-- /wp:image -->
            <?php endif; ?>
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->
