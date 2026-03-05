<?php
/**
 * Title: 📄 Page: About Us (Full Page)
 * Slug: my-custom-theme/page-about
 * Categories: my-special-design
 * Description: หน้า About Us แบบเต็มหน้า - ดึงข้อมูลทั้งหมดจาก Theme Settings
 * Keywords: about, เกี่ยวกับเรา, page
 * Block Types: core/post-content
 * Post Types: page
 */

// ── Load all About Us options from DB ──
$ab_s1_heading   = get_option('about_s1_heading',   'Leading Electronic Components Manufacturer');
$ab_s1_text1     = get_option('about_s1_text1',     'With over 20 years of experience in the electronic components industry, we have established ourselves as a trusted manufacturer and supplier of high-quality inductors, transformers, and antennas.');
$ab_s1_text2     = get_option('about_s1_text2',     'Our commitment to quality, innovation, and customer satisfaction has made us a preferred partner for leading companies in automotive, telecommunications, industrial automation, and consumer electronics sectors.');
$ab_s1_text3     = get_option('about_s1_text3',     'We operate state-of-the-art manufacturing facilities equipped with advanced automated production lines and rigorous quality control systems to ensure every product meets the highest international standards.');
$ab_s1_image     = get_option('about_s1_image',     'https://schottmagnetics.com/wp-content/uploads/2021/07/Flyback-Transformer4-1024x768-1.jpg');
$ab_mission      = get_option('about_mission_text', 'To provide innovative, reliable, and cost-effective electronic component solutions that enable our customers to succeed in their markets.');
$ab_vision       = get_option('about_vision_text',  'To be the global leader in electronic components manufacturing, recognized for quality, innovation, and exceptional customer service.');
$ab_values_raw   = get_option('about_values',       "Quality Excellence\nCustomer Focus\nContinuous Innovation\nIntegrity & Trust\nEnvironmental Responsibility");
$ab_s2_image     = get_option('about_s2_image',     'https://schottmagnetics.com/wp-content/uploads/2021/07/Toroid-Inductor-Choke3-1024x768-1.jpg');
$ab_cta_heading  = get_option('about_cta_heading',  'Partner With Us');
$ab_cta_text     = get_option('about_cta_text',     "Let's discuss how we can support your electronic component needs");
$ab_cta_btn_text = get_option('about_cta_btn_text', 'Contact Us');
$ab_cta_btn_url  = get_option('about_cta_btn_url',  '/contact');
$years_auto    = get_option('site_years_auto',    '0');
$products_auto = get_option('site_products_auto', '0');
$founded_year  = (int) get_option('site_founded_year', 1988);
if ($years_auto === '1' && $founded_year > 0) {
    $years_exp = max(0, (int)date('Y') - $founded_year);
} else {
    $years_exp = (int) get_option('site_years_experience', 20);
}
if ($products_auto === '1') {
    $pc = wp_count_posts('product');
    $total_prod = isset($pc->publish) ? (int)$pc->publish : 0;
} else {
    $total_prod = (int) get_option('site_total_products', 500);
}
$countries       = get_option('site_countries_served', 50);
$happy_cust      = get_option('site_happy_customers', 1000);
$ab_values       = array_filter(array_map('trim', explode("\n", $ab_values_raw)));
$ab_cert_items   = function_exists('my_theme_get_about_certifications') ? my_theme_get_about_certifications() : array();
?>
<!-- wp:cover {"overlayColor":"primary","align":"full","style":{"spacing":{"padding":{"top":"60px","bottom":"60px"}}}} -->
<div class="wp-block-cover alignfull" style="padding-top:60px;padding-bottom:60px">
    <span aria-hidden="true" class="wp-block-cover__background has-primary-background-color has-background-dim-100 has-background-dim"></span>
    <div class="wp-block-cover__inner-container">
        <!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"42px"},"spacing":{"margin":{"bottom":"10px"}}},"textColor":"white"} -->
        <h1 class="wp-block-heading has-text-align-center has-white-color has-text-color" style="margin-bottom:10px;font-size:42px">About Us</h1>
        <!-- /wp:heading -->

        <!-- wp:paragraph {"align":"center","style":{"elements":{"link":{"color":{"text":"var:preset|color|white"}}}},"textColor":"white"} -->
        <p class="has-text-align-center has-white-color has-text-color has-link-color"><a href="/">Home</a> / About Us</p>
        <!-- /wp:paragraph -->
    </div>
</div>
<!-- /wp:cover -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"60px","bottom":"60px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:60px;padding-bottom:60px">
    <!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"50px"}}},"verticalAlignment":"center"} -->
    <div class="wp-block-columns are-vertically-aligned-center">
        <!-- wp:column {"verticalAlignment":"center"} -->
        <div class="wp-block-column is-vertically-aligned-center">
            <!-- wp:heading {"style":{"typography":{"fontSize":"32px"},"spacing":{"margin":{"bottom":"20px"}}}} -->
            <h2 class="wp-block-heading" style="margin-bottom:20px;font-size:32px"><?php echo esc_html($ab_s1_heading); ?></h2>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"style":{"color":{"text":"#64748b"},"spacing":{"margin":{"bottom":"15px"}}}} -->
            <p class="has-text-color" style="color:#64748b;margin-bottom:15px"><?php echo esc_html($ab_s1_text1); ?></p>
            <!-- /wp:paragraph -->

            <!-- wp:paragraph {"style":{"color":{"text":"#64748b"},"spacing":{"margin":{"bottom":"15px"}}}} -->
            <p class="has-text-color" style="color:#64748b;margin-bottom:15px"><?php echo esc_html($ab_s1_text2); ?></p>
            <!-- /wp:paragraph -->

            <!-- wp:paragraph {"style":{"color":{"text":"#64748b"}}} -->
            <p class="has-text-color" style="color:#64748b"><?php echo esc_html($ab_s1_text3); ?></p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column {"verticalAlignment":"center"} -->
        <div class="wp-block-column is-vertically-aligned-center">
            <!-- wp:image {"sizeSlug":"large","style":{"border":{"radius":"12px"}}} -->
            <figure class="wp-block-image size-large" style="border-radius:12px"><img src="<?php echo esc_url($ab_s1_image); ?>" alt="Our Factory"/></figure>
            <!-- /wp:image -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"60px","bottom":"60px"}}},"backgroundColor":"tertiary","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-tertiary-background-color has-background" style="padding-top:60px;padding-bottom:60px">
    <!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"30px"}}}} -->
    <div class="wp-block-columns">
        <?php
        // Stats loaded from DB at top of file
        ?>
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

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"60px","bottom":"60px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:60px;padding-bottom:60px">
    <!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"50px"}}},"verticalAlignment":"center"} -->
    <div class="wp-block-columns are-vertically-aligned-center">
        <!-- wp:column {"verticalAlignment":"center"} -->
        <div class="wp-block-column is-vertically-aligned-center">
            <!-- wp:image {"sizeSlug":"large","style":{"border":{"radius":"12px"}}} -->
            <figure class="wp-block-image size-large" style="border-radius:12px"><img src="<?php echo esc_url($ab_s2_image); ?>" alt="R&amp;D Laboratory"/></figure>
            <!-- /wp:image -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column {"verticalAlignment":"center"} -->
        <div class="wp-block-column is-vertically-aligned-center">
            <!-- wp:heading {"style":{"typography":{"fontSize":"32px"},"spacing":{"margin":{"bottom":"20px"}}}} -->
            <h2 class="wp-block-heading" style="margin-bottom:20px;font-size:32px">Our Mission</h2>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"style":{"color":{"text":"#64748b"},"spacing":{"margin":{"bottom":"15px"}}}} -->
            <p class="has-text-color" style="color:#64748b;margin-bottom:15px"><?php echo esc_html($ab_mission); ?></p>
            <!-- /wp:paragraph -->

            <!-- wp:heading {"style":{"typography":{"fontSize":"32px"},"spacing":{"margin":{"bottom":"20px","top":"30px"}}}} -->
            <h2 class="wp-block-heading" style="margin-top:30px;margin-bottom:20px;font-size:32px">Our Vision</h2>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"style":{"color":{"text":"#64748b"},"spacing":{"margin":{"bottom":"15px"}}}} -->
            <p class="has-text-color" style="color:#64748b;margin-bottom:15px"><?php echo esc_html($ab_vision); ?></p>
            <!-- /wp:paragraph -->

            <!-- wp:heading {"style":{"typography":{"fontSize":"32px"},"spacing":{"margin":{"bottom":"20px","top":"30px"}}}} -->
            <h2 class="wp-block-heading" style="margin-top:30px;margin-bottom:20px;font-size:32px">Core Values</h2>
            <!-- /wp:heading -->

            <!-- wp:list {"style":{"color":{"text":"#64748b"},"spacing":{"blockGap":"8px"}},"className":"is-style-default"} -->
            <ul class="is-style-default has-text-color" style="color:#64748b">
                <?php foreach ($ab_values as $val) : ?>
                <!-- wp:list-item -->
                <li><?php echo esc_html($val); ?></li>
                <!-- /wp:list-item -->
                <?php endforeach; ?>
            </ul>
            <!-- /wp:list -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"80px","bottom":"80px"}}},"backgroundColor":"tertiary","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-tertiary-background-color has-background" style="padding-top:80px;padding-bottom:80px">
    <!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":"36px","fontWeight":"700"},"spacing":{"margin":{"bottom":"50px"}}}} -->
    <h2 class="wp-block-heading has-text-align-center" style="margin-bottom:50px;font-size:36px;font-weight:700">Certifications &amp; Quality</h2>
    <!-- /wp:heading -->

    <!-- wp:columns {"className":"kv-about-cert-grid","style":{"spacing":{"blockGap":{"left":"30px"}}}} -->
    <div class="wp-block-columns kv-about-cert-grid">
        <?php foreach ($ab_cert_items as $cert) : ?>
        <!-- wp:column {"className":"kv-about-cert-item","style":{"spacing":{"padding":{"top":"30px","bottom":"30px","left":"30px","right":"30px"}}}} -->
        <div class="wp-block-column kv-about-cert-item" style="padding-top:30px;padding-right:30px;padding-bottom:30px;padding-left:30px">
            <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"48px"},"spacing":{"margin":{"bottom":"15px"}}}} -->
            <p class="has-text-align-center" style="margin-bottom:15px;font-size:48px"><?php echo esc_html($cert['icon'] ?? '✅'); ?></p>
            <!-- /wp:paragraph -->

            <!-- wp:heading {"textAlign":"center","level":4,"style":{"typography":{"fontSize":"20px"},"spacing":{"margin":{"bottom":"10px"}}}} -->
            <h4 class="wp-block-heading has-text-align-center" style="margin-bottom:10px;font-size:20px"><?php echo esc_html($cert['title'] ?? ''); ?></h4>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"align":"center","style":{"color":{"text":"#64748b"}}} -->
            <p class="has-text-align-center has-text-color" style="color:#64748b"><?php echo esc_html($cert['description'] ?? ''); ?></p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->
        <?php endforeach; ?>
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->

<!-- wp:cover {"overlayColor":"primary","align":"full","style":{"spacing":{"padding":{"top":"60px","bottom":"60px"}}}} -->
<div class="wp-block-cover alignfull" style="padding-top:60px;padding-bottom:60px">
    <span aria-hidden="true" class="wp-block-cover__background has-primary-background-color has-background-dim-100 has-background-dim"></span>
    <div class="wp-block-cover__inner-container">
        <!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":"36px"},"spacing":{"margin":{"bottom":"15px"}}},"textColor":"white"} -->
        <h2 class="wp-block-heading has-text-align-center has-white-color has-text-color" style="margin-bottom:15px;font-size:36px"><?php echo esc_html($ab_cta_heading); ?></h2>
        <!-- /wp:heading -->

        <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"18px"},"spacing":{"margin":{"bottom":"25px"}}},"textColor":"white"} -->
        <p class="has-text-align-center has-white-color has-text-color" style="margin-bottom:25px;font-size:18px"><?php echo esc_html($ab_cta_text); ?></p>
        <!-- /wp:paragraph -->

        <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
        <div class="wp-block-buttons">
            <!-- wp:button {"backgroundColor":"white","textColor":"primary","style":{"border":{"radius":"6px"}}} -->
            <div class="wp-block-button"><a class="wp-block-button__link has-primary-color has-white-background-color has-text-color has-background wp-element-button" href="<?php echo esc_url($ab_cta_btn_url); ?>" style="border-radius:6px"><?php echo esc_html($ab_cta_btn_text); ?></a></div>
            <!-- /wp:button -->
        </div>
        <!-- /wp:buttons -->
    </div>
</div>
<!-- /wp:cover -->
