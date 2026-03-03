<?php
/**
 * Title: Footer Section
 * Slug: my-custom-theme/footer-section
 * Categories: my-special-design
 * Description: ส่วนท้ายเว็บไซต์ 4 คอลัมน์
 */

// ── Load options from DB ──
$footer_about_text  = get_option('footer_about_text',  'Leading manufacturer of electronic components with over 20 years of experience in the industry.');
$footer_quick_raw   = get_option('footer_quick_links', "About Us|/about\nContact|/contact");
$phone              = kv_format_phone_th(get_option('site_phone', ''));
$email              = get_option('site_email',    'info@company.com');
$address            = get_option('site_address',  '123 Industrial Zone, Bangkok, Thailand');
$company            = get_option('site_company_name', 'Electronic Components Co., Ltd.');
$copy               = get_option('site_copyright',    'All rights reserved.');

// Footer Products: ดึงจาก taxonomy จริง (sync อัตโนมัติ)
$footer_prod_links = get_terms([
    'taxonomy'   => 'product_category',
    'parent'     => 0,
    'hide_empty' => false,
    'orderby'    => 'menu_order',
    'order'      => 'ASC',
]);

// Parse quick links (format: "Label|/url" per line)
$parse_links = function($raw) {
    $links = [];
    foreach (array_filter(array_map('trim', explode("\n", $raw))) as $line) {
        $parts = explode('|', $line, 2);
        if (count($parts) === 2) {
            $links[] = ['label' => trim($parts[0]), 'url' => trim($parts[1])];
        }
    }
    return $links;
};
$footer_quick_links = $parse_links($footer_quick_raw);
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"60px","bottom":"30px"}},"color":{"background":"#1e293b"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-background" style="background-color:#1e293b;padding-top:60px;padding-bottom:30px">
    <!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"40px"},"margin":{"bottom":"40px"}}}} -->
    <div class="wp-block-columns" style="margin-bottom:40px">
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"level":4,"style":{"typography":{"fontSize":"18px"},"spacing":{"margin":{"bottom":"20px"}}},"textColor":"white"} -->
            <h4 class="wp-block-heading has-white-color has-text-color" style="margin-bottom:20px;font-size:18px">About Us</h4>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"style":{"color":{"text":"var(--theme-primary)"}}} -->
            <p class="has-text-color" style="color:var(--theme-primary)">Founded in 1988, KV Electronics Co., Ltd. has grown from a shared passion between two industry experts into a trusted manufacturer of high-quality magnetic components and electronic solutions. With a strong commitment to continuous improvement, the company strives to delight customers and consistently exceed expectations, while operating as an ISO-certified and BOI-promoted manufacturer. This long-standing dedication to excellence and reliability has remained at the heart of KV Electronics' operations for more than three decades.</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"level":4,"style":{"typography":{"fontSize":"18px"},"spacing":{"margin":{"bottom":"20px"}}},"textColor":"white"} -->
            <h4 class="wp-block-heading has-white-color has-text-color" style="margin-bottom:20px;font-size:18px">Products</h4>
            <!-- /wp:heading -->

            <!-- wp:list {"style":{"spacing":{"blockGap":"10px"},"color":{"text":"var(--theme-primary)"},"elements":{"link":{"color":{"text":"var(--theme-primary)"},":hover":{"color":{"text":"var(--theme-primary)"}}}}}} -->
            <ul class="has-text-color has-link-color" style="color:var(--theme-primary)">
                <?php foreach ($footer_prod_links as $term) : ?>
                <!-- wp:list-item -->
                <li><a href="<?php echo esc_url(get_term_link($term)); ?>"><?php echo esc_html($term->name); ?></a></li>
                <!-- /wp:list-item -->
                <?php endforeach; ?>
            </ul>
            <!-- /wp:list -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"level":4,"style":{"typography":{"fontSize":"18px"},"spacing":{"margin":{"bottom":"20px"}}},"textColor":"white"} -->
            <h4 class="wp-block-heading has-white-color has-text-color" style="margin-bottom:20px;font-size:18px">Quick Links</h4>
            <!-- /wp:heading -->

            <!-- wp:list {"style":{"spacing":{"blockGap":"10px"},"color":{"text":"var(--theme-primary)"},"elements":{"link":{"color":{"text":"var(--theme-primary)"},":hover":{"color":{"text":"var(--theme-primary)"}}}}}} -->
            <ul class="has-text-color has-link-color" style="color:var(--theme-primary)">
                <?php foreach ($footer_quick_links as $link) : ?>
                <!-- wp:list-item -->
                <li><a href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['label']); ?></a></li>
                <!-- /wp:list-item -->
                <?php endforeach; ?>
            </ul>
            <!-- /wp:list -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"level":4,"style":{"typography":{"fontSize":"18px"},"spacing":{"margin":{"bottom":"20px"}}},"textColor":"white"} -->
            <h4 class="wp-block-heading has-white-color has-text-color" style="margin-bottom:20px;font-size:18px">Contact Info</h4>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"style":{"color":{"text":"var(--theme-primary)"},"spacing":{"margin":{"bottom":"10px"}}}} -->
            <p class="has-text-color" style="color:var(--theme-primary);margin-bottom:10px">📍 <?php echo esc_html($address); ?></p>
            <!-- /wp:paragraph -->

            <!-- wp:paragraph {"style":{"color":{"text":"var(--theme-primary)"},"spacing":{"margin":{"bottom":"10px"}}}} -->
            <p class="has-text-color" style="color:var(--theme-primary);margin-bottom:10px">📞 <?php echo esc_html($phone); ?></p>
            <!-- /wp:paragraph -->

            <!-- wp:paragraph {"style":{"color":{"text":"var(--theme-primary)"}}} -->
            <p class="has-text-color" style="color:var(--theme-primary)">✉️ <?php echo esc_html($email); ?></p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->

    <!-- wp:separator {"style":{"color":{"background":"#334155"}},"className":"is-style-wide"} -->
    <hr class="wp-block-separator has-text-color has-alpha-channel-opacity has-background is-style-wide" style="background-color:#334155;color:#334155"/>
    <!-- /wp:separator -->

    <!-- wp:paragraph {"align":"center","style":{"color":{"text":"var(--theme-primary)"},"spacing":{"margin":{"top":"30px"}}}} -->
    <p class="has-text-align-center has-text-color" style="color:var(--theme-primary);margin-top:30px">© <?php echo date('Y'); ?> <?php echo esc_html($company); ?> <?php echo esc_html($copy); ?></p>
    <!-- /wp:paragraph -->
</div>
<!-- /wp:group -->
