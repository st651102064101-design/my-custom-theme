<?php
/**
 * Title: Hero Section
 * Slug: my-custom-theme/hero-section
 * Categories: my-special-design
 * Description: หน้าหลักแบบ Hero พร้อมปุ่ม CTA
 */
?>
<!-- wp:cover {"overlayColor":"primary","minHeight":500,"isDark":true,"align":"full","style":{"spacing":{"padding":{"top":"100px","bottom":"100px"}}}} -->
<div class="wp-block-cover alignfull is-dark" style="padding-top:100px;padding-bottom:100px;min-height:500px">
    <span aria-hidden="true" class="wp-block-cover__background has-primary-background-color has-background-dim-100 has-background-dim"></span>
    <div class="wp-block-cover__inner-container">
        <!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"48px","fontWeight":"700"}},"textColor":"white"} -->
        <h1 class="wp-block-heading has-text-align-center has-white-color has-text-color" style="font-size:48px;font-weight:700;margin-bottom:40px">KV Electronics | Home</h1>
        <!-- /wp:heading -->

        <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"20px"},"spacing":{"margin":{"top":"20px","bottom":"30px"}}},"textColor":"white"} -->
        <!-- /wp:paragraph -->

        <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"blockGap":"15px"}}} -->
        <div class="wp-block-buttons">
            <!-- wp:button {"backgroundColor":"white","textColor":"primary","style":{"border":{"radius":"6px"}}} -->
            <div class="wp-block-button"><a class="wp-block-button__link has-primary-color has-white-background-color has-text-color has-background wp-element-button" href="/products/" style="border-radius:6px">View Products</a></div>
            <!-- /wp:button -->

            <!-- wp:button {"textColor":"white","className":"is-style-outline","style":{"border":{"radius":"6px","width":"2px","color":"#ffffff"}}} -->
            <div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-white-color has-text-color wp-element-button" href="/contact/" style="border-radius:6px;border-width:2px;border-color:#ffffff;color:#ffffff">Contact Us</a></div>
            <!-- /wp:button -->
        </div>
        <!-- /wp:buttons -->
    </div>
</div>
<!-- /wp:cover -->
