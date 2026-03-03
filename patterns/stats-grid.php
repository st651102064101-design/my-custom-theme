<?php
/**
 * Title: Stats Grid
 * Slug: my-custom-theme/stats-grid
 * Categories: my-special-design
 * Description: แสดงตัวเลขสถิติ 4 ช่อง
 */

// Pull stats from database
$years_auto    = get_option('site_years_auto',    '0');
$products_auto = get_option('site_products_auto', '0');
$founded_year  = (int) get_option('site_founded_year', 1988);

// Years of experience: auto-calculate from founding year, or use manual value
if ($years_auto === '1' && $founded_year > 0) {
    $years_exp = max(0, (int)date('Y') - $founded_year);
} else {
    $years_exp = (int) get_option('site_years_experience', 20);
}

// Total products: count published products from DB, or use manual value
if ($products_auto === '1') {
    $pc = wp_count_posts('product');
    $total_prod = isset($pc->publish) ? (int)$pc->publish : 0;
} else {
    $total_prod = (int) get_option('site_total_products', 500);
}

$countries   = get_option('site_countries_served', 50);
$happy_cust  = get_option('site_happy_customers', 1000);
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"60px","bottom":"60px"}}},"backgroundColor":"tertiary","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-tertiary-background-color has-background" style="padding-top:60px;padding-bottom:60px">
    <!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"30px"}}}} -->
    <div class="wp-block-columns">
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"48px","fontWeight":"700"},"spacing":{"margin":{"bottom":"5px"}}},"textColor":"primary"} -->
            <p class="has-text-align-center has-primary-color has-text-color" style="margin-bottom:5px;font-size:48px;font-weight:700"><?php echo esc_html($years_exp); ?>+</p>
            <!-- /wp:paragraph -->

            <!-- wp:paragraph {"align":"center","style":{"color":{"text":"#64748b"}}} -->
            <p class="has-text-align-center has-text-color" style="color:#64748b">Years Experience</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"48px","fontWeight":"700"},"spacing":{"margin":{"bottom":"5px"}}},"textColor":"primary"} -->
            <p class="has-text-align-center has-primary-color has-text-color" style="margin-bottom:5px;font-size:48px;font-weight:700"><?php echo esc_html($total_prod); ?>+</p>
            <!-- /wp:paragraph -->

            <!-- wp:paragraph {"align":"center","style":{"color":{"text":"#64748b"}}} -->
            <p class="has-text-align-center has-text-color" style="color:#64748b">Products</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"48px","fontWeight":"700"},"spacing":{"margin":{"bottom":"5px"}}},"textColor":"primary"} -->
            <p class="has-text-align-center has-primary-color has-text-color" style="margin-bottom:5px;font-size:48px;font-weight:700"><?php echo esc_html($countries); ?>+</p>
            <!-- /wp:paragraph -->

            <!-- wp:paragraph {"align":"center","style":{"color":{"text":"#64748b"}}} -->
            <p class="has-text-align-center has-text-color" style="color:#64748b">Countries Served</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"48px","fontWeight":"700"},"spacing":{"margin":{"bottom":"5px"}}},"textColor":"primary"} -->
            <p class="has-text-align-center has-primary-color has-text-color" style="margin-bottom:5px;font-size:48px;font-weight:700"><?php echo esc_html($happy_cust); ?>+</p>
            <!-- /wp:paragraph -->

            <!-- wp:paragraph {"align":"center","style":{"color":{"text":"#64748b"}}} -->
            <p class="has-text-align-center has-text-color" style="color:#64748b">Happy Customers</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->
