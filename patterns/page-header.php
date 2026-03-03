<?php
/**
 * Title: Page Header
 * Slug: my-custom-theme/page-header
 * Categories: my-special-design
 * Description: หัวหน้าสำหรับหน้าย่อย พร้อม Breadcrumb
 */
?>
<!-- wp:cover {"overlayColor":"primary","align":"full","style":{"spacing":{"padding":{"top":"60px","bottom":"60px"}}}} -->
<div class="wp-block-cover alignfull" style="padding-top:60px;padding-bottom:60px">
    <span aria-hidden="true" class="wp-block-cover__background has-primary-background-color has-background-dim-100 has-background-dim"></span>
    <div class="wp-block-cover__inner-container">
        <!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"42px"},"spacing":{"margin":{"bottom":"10px"}}},"textColor":"white"} -->
        <h1 class="wp-block-heading has-text-align-center has-white-color has-text-color" style="margin-bottom:10px;font-size:42px">Page Title</h1>
        <!-- /wp:heading -->

        <!-- wp:paragraph {"align":"center","style":{"elements":{"link":{"color":{"text":"var:preset|color|white"}}}},"textColor":"white"} -->
        <p class="has-text-align-center has-white-color has-text-color has-link-color"><a href="#">Home</a> / Current Page</p>
        <!-- /wp:paragraph -->
    </div>
</div>
<!-- /wp:cover -->
