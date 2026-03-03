<?php
/**
 * Theme Report Dashboard
 * 
 * Comprehensive system report page inspired by:
 * - Google Analytics Dashboard
 * - Shopify Admin Overview
 * - WooCommerce Status Report
 */

if (!defined('ABSPATH')) exit;

// ============================================
// 1b. DASHBOARD WIDGET
// ============================================
add_action('wp_dashboard_setup', function() {
    wp_add_dashboard_widget(
        'kv_theme_report_widget',
        '📊 System Report — Theme Report',
        'tr_render_report_page'
    );

    // Move widget to top of dashboard (normal column)
    global $wp_meta_boxes;
    $widget = $wp_meta_boxes['dashboard']['normal']['core']['kv_theme_report_widget'] ?? null;
    if ($widget) {
        unset($wp_meta_boxes['dashboard']['normal']['core']['kv_theme_report_widget']);
        array_unshift($wp_meta_boxes['dashboard']['normal']['core'], $widget);
    }
});

// ============================================
// 2. ENQUEUE ADMIN ASSETS
// ============================================
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook !== 'index.php') return;
    $is_dashboard = true;

    wp_enqueue_style(
        'tr-report-css',
        get_template_directory_uri() . '/assets/css/admin-report.css',
        [],
        filemtime(get_template_directory() . '/assets/css/admin-report.css')
    );

    // Dynamic color override from theme settings
    $color = get_option('theme_primary_color', '#0056d6');
    $hex = ltrim($color, '#');
    if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    $r = hexdec(substr($hex,0,2)); $g = hexdec(substr($hex,2,2)); $b = hexdec(substr($hex,4,2));
    $dark  = sprintf('#%02x%02x%02x', max(0,round($r*0.82)), max(0,round($g*0.82)), max(0,round($b*0.82)));
    $light = sprintf('#%02x%02x%02x', min(255,$r+round((255-$r)*0.92)), min(255,$g+round((255-$g)*0.92)), min(255,$b+round((255-$b)*0.92)));
    wp_add_inline_style('tr-report-css', ":root{--rp-primary:{$color};--rp-primary-dark:{$dark};--rp-primary-light:{$light};}");

    // Dashboard widget overrides — full-width, no extra padding
    if ($is_dashboard) {
        wp_add_inline_style('tr-report-css', "
            #kv_theme_report_widget { clear: both; }
            #kv_theme_report_widget .inside { padding: 0; margin: 0; }
            #kv_theme_report_widget .rp-wrap { background: transparent; padding: 0; }
            #kv_theme_report_widget .rp-header { border-radius: 0; margin-bottom: 16px; }
            .wrap #dashboard-widgets .postbox-container { width: 100% !important; }
        ");
    }
});

// ============================================
// 3. HELPER FUNCTIONS
// ============================================

/**
 * Format date/time in English
 * @param string $format  'full' = "February 21, 2026, 14:30", 'short' = "Feb 21, 2026", 'datetime' = "February 21, 2026, 14:30:00"
 * @param string|int|null $timestamp  Unix timestamp or date string. Null = now.
 */
function tr_thai_date($format = 'full', $timestamp = null) {
    $tz = new DateTimeZone('Asia/Bangkok');
    $dt = new DateTime('now', $tz);
    if ($timestamp !== null) {
        if (is_numeric($timestamp)) {
            $dt->setTimestamp((int)$timestamp);
        } else {
            $dt = new DateTime($timestamp, new DateTimeZone('UTC'));
        }
        $dt->setTimezone($tz);
    }

    switch ($format) {
        case 'short':
            return $dt->format('M j, Y');
        case 'datetime':
            return $dt->format('F j, Y, H:i:s');
        case 'full':
        default:
            return $dt->format('F j, Y, H:i');
    }
}

function tr_same_host_admin_url($query = '') {
    $query = ltrim((string)$query, '?');
    $scheme = is_ssl() ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? parse_url(home_url('/'), PHP_URL_HOST);
    if (empty($host)) {
        return admin_url('admin.php' . ($query ? ('?' . $query) : ''));
    }
    return $scheme . '://' . $host . '/wp-admin/admin.php' . ($query ? ('?' . $query) : '');
}

/**
 * Get all products with their categories and meta
 */
function tr_get_products_data() {
    $products = get_posts([
        'post_type'      => 'product',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

    $data = [];
    foreach ($products as $p) {
        $terms = wp_get_post_terms($p->ID, 'product_category');
        $gallery = json_decode(get_post_meta($p->ID, 'pd_gallery', true), true);
        $has_image = !empty($gallery) && is_array($gallery);

        $meta_keys = ['pd_subtitle', 'pd_sku', 'pd_size_range', 'pd_output_range',
                       'pd_temp_range', 'pd_package_type', 'pd_standards', 'pd_long_description',
                       'pd_voltage', 'pd_current_rating', 'pd_inductance', 'pd_impedance', 'pd_frequency', 'pd_features'];
        $filled_meta = 0;
        foreach ($meta_keys as $mk) {
            if (get_post_meta($p->ID, $mk, true)) $filled_meta++;
        }

        $data[] = [
            'id'           => $p->ID,
            'title'        => $p->post_title,
            'status'       => $p->post_status,
            'date'         => $p->post_date,
            'modified'     => $p->post_modified,
            'category'     => !empty($terms) ? $terms[0]->name : '—',
            'category_slug'=> !empty($terms) ? $terms[0]->slug : '',
            'has_image'    => $has_image,
            'image_count'  => $has_image ? count($gallery) : 0,
            'excerpt'      => !empty($p->post_excerpt),
            'filled_meta'  => $filled_meta,
            'total_meta'   => count($meta_keys),
            'permalink'    => get_permalink($p->ID),
            'edit_link'    => admin_url('admin.php?page=product-manager'),
        ];
    }
    return $data;
}

/**
 * Get taxonomy tree with counts
 */
function tr_get_taxonomy_tree() {
    $terms = get_terms([
        'taxonomy'   => 'product_category',
        'hide_empty' => false,
        'orderby'    => 'name',
    ]);

    if (is_wp_error($terms)) return [];

    $tree = [];
    $term_map = [];
    foreach ($terms as $t) {
        $products = get_posts([
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'no_found_rows' => true,
            'fields' => 'ids',
            'tax_query' => [[
                'taxonomy' => 'product_category',
                'field' => 'term_id',
                'terms' => $t->term_id,
                'include_children' => false,
            ]],
        ]);

        $cat_image = get_term_meta($t->term_id, 'cat_image', true);

        $term_map[$t->term_id] = [
            'id'        => $t->term_id,
            'name'      => $t->name,
            'slug'      => $t->slug,
            'parent'    => $t->parent,
            'count'     => count($products),
            'has_image' => !empty($cat_image),
            'children'  => [],
        ];
    }

    // Build hierarchy
    foreach ($term_map as $id => &$item) {
        if ($item['parent'] && isset($term_map[$item['parent']])) {
            $term_map[$item['parent']]['children'][] = &$item;
        } else {
            $tree[] = &$item;
        }
    }

    return $tree;
}

/**
 * Get theme settings status
 */
function tr_get_settings_status() {
    $settings = [
        'Contact Information' => [
            'site_phone' => 'Phone',
            'site_fax' => 'Fax',
            'site_email' => 'Email',
            'site_email_sales' => 'Sales Email',
            'site_address' => 'Address',
            'site_address_full' => 'Full Address',
            'site_hours_weekday' => 'Business Hours (Mon-Fri)',
            'site_hours_weekend' => 'Business Hours (Sat-Sun)',
            'site_map_embed' => 'Google Map',
        ],
        'Company' => [
            'site_company_name' => 'Company Name',
            'site_copyright' => 'Copyright',
            'site_logo_url' => 'Logo',
            'site_logo_light_url' => 'Logo Light',
        ],
        'Statistics' => [
            'site_years_experience' => 'Years of Experience',
            'site_total_products' => 'Total Products',
            'site_countries_served' => 'Countries Served',
            'site_happy_customers' => 'Customers',
            'site_founded_year' => 'Founded Year',
        ],
        'Theme Colors' => [
            'theme_primary_color' => 'Primary Color (30%)',
            'theme_accent_color' => 'Accent Color (10%)',
            'theme_bg_color' => 'Background Color (60%)',
        ],
        'Banner' => [
            'banner_bg_color' => 'Background Color',
            'banner_bg_image' => 'Banner Image',
            'banner_bg_video' => 'Banner Video',
            'banner_overlay' => 'Overlay Opacity',
        ],
        'About Us' => [
            'about_s1_heading' => 'Section 1 Heading',
            'about_s1_text1' => 'Content 1',
            'about_s1_text2' => 'Content 2',
            'about_s1_text3' => 'Content 3',
            'about_s1_image' => 'Section 1 Image',
            'about_mission_text' => 'Mission',
            'about_vision_text' => 'Vision',
            'about_values' => 'Values',
            'about_s2_image' => 'Section 2 Image',
        ],
    ];

    $result = [];
    foreach ($settings as $group => $fields) {
        $filled = 0;
        $items = [];
        foreach ($fields as $key => $label) {
            $val = get_option($key, '');
            $has_val = !empty($val);
            if ($has_val) $filled++;
            $items[] = [
                'key'   => $key,
                'label' => $label,
                'value' => $has_val ? (strlen($val) > 50 ? mb_substr($val, 0, 50) . '...' : $val) : '',
                'has'   => $has_val,
            ];
        }
        $result[] = [
            'group'  => $group,
            'fields' => $items,
            'filled' => $filled,
            'total'  => count($fields),
        ];
    }
    return $result;
}

function tr_table_exists($table_name) {
    global $wpdb;
    return $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;
}

function tr_get_business_reporting_data() {
    global $wpdb;

    $contact_table  = $wpdb->prefix . 'contact_submissions';
    $leads_table    = $wpdb->prefix . 'datasheet_leads';
    $rag_logs_table = $wpdb->prefix . 'rag_chat_logs';
    $posts_table    = $wpdb->posts;

    $data = [
        'contact_total'        => 0,
        'contact_today'        => 0,
        'contact_unique_email' => 0,
        'rfq_total'            => 0,
        'datasheet_total'      => 0,
        'datasheet_today'      => 0,
        'top_datasheet'        => [],
        'rag_total'            => 0,
        'rag_success'          => 0,
        'rag_success_rate'     => 0,
        'rag_avg_ms'           => 0,
        'rag_recent'           => [],
        'has_rag_logs'         => false,
    ];

    if (tr_table_exists($contact_table)) {
        $today = current_time('Y-m-d');
        $data['contact_total'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$contact_table}");
        $data['contact_today'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$contact_table} WHERE DATE(created_at) = %s", $today));
        $data['contact_unique_email'] = (int) $wpdb->get_var("SELECT COUNT(DISTINCT email) FROM {$contact_table}");
        $data['rfq_total'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$contact_table}
             WHERE product <> ''
                OR LOWER(subject) LIKE '%rfq%'
                OR LOWER(subject) LIKE '%quote%'
                OR LOWER(subject) LIKE '%quotation%'
                OR LOWER(message) LIKE '%rfq%'
                OR LOWER(message) LIKE '%quote%'
                OR LOWER(message) LIKE '%quotation%'"
        );
    }

    if (tr_table_exists($leads_table)) {
        $today = current_time('Y-m-d');
        $data['datasheet_total'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$leads_table}");
        $data['datasheet_today'] = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$leads_table} WHERE DATE(created_at) = %s", $today));
        $data['top_datasheet'] = $wpdb->get_results(
            "SELECT
                l.product_id,
                COALESCE(NULLIF(MAX(l.product_name), ''), MAX(p.post_title), 'Unknown Product') AS product_name,
                COUNT(*) AS downloads
             FROM {$leads_table} l
             LEFT JOIN {$posts_table} p ON p.ID = l.product_id
             GROUP BY l.product_id
             ORDER BY downloads DESC
             LIMIT 10",
            ARRAY_A
        );
    }

    if (tr_table_exists($rag_logs_table)) {
        $data['has_rag_logs'] = true;
        $data['rag_total']    = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$rag_logs_table}");
        $data['rag_success']  = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$rag_logs_table} WHERE success = 1");
        $data['rag_avg_ms']   = (int) $wpdb->get_var("SELECT COALESCE(AVG(response_time_ms), 0) FROM {$rag_logs_table}");
        if ($data['rag_total'] > 0) {
            $data['rag_success_rate'] = (int) round(($data['rag_success'] / $data['rag_total']) * 100);
        }
        $data['rag_recent'] = $wpdb->get_results(
            "SELECT query_text, lang, result_count, success, response_time_ms, created_at
             FROM {$rag_logs_table}
             ORDER BY id DESC
             LIMIT 10",
            ARRAY_A
        );
    }

    return $data;
}

/**
 * Get system info
 */
function tr_get_system_info() {
    global $wpdb, $wp_version;

    $theme = wp_get_theme();
    $plugins = get_option('active_plugins', []);
    
    // Count theme files
    $theme_dir = get_template_directory();
    $php_files = glob($theme_dir . '/*.php');
    $pattern_files = glob($theme_dir . '/patterns/*.php');
    $css_files = glob($theme_dir . '/assets/css/*.css');
    $js_files = glob($theme_dir . '/assets/js/*.js');

    // DB size
    $db_size = $wpdb->get_var("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) FROM information_schema.TABLES WHERE table_schema = DATABASE()");
    
    // WP options count
    $options_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options}");

    // Timezone
    $tz_string = get_option('timezone_string', '');
    $gmt_offset = get_option('gmt_offset', 0);

    return [
        'wp_version'     => $wp_version,
        'php_version'    => PHP_VERSION,
        'mysql_version'  => $wpdb->get_var("SELECT VERSION()"),
        'theme_name'     => $theme->get('Name'),
        'theme_version'  => $theme->get('Version') ?: '1.0',
        'site_url'       => get_site_url(),
        'home_url'       => get_home_url(),
        'active_plugins' => count($plugins),
        'plugin_list'    => $plugins,
        'php_files'      => count($php_files ?: []),
        'pattern_files'  => count($pattern_files ?: []),
        'css_files'      => count($css_files ?: []),
        'js_files'       => count($js_files ?: []),
        'db_size'        => $db_size . ' MB',
        'options_count'  => $options_count,
        'timezone'       => $tz_string ?: 'UTC' . ($gmt_offset >= 0 ? '+' : '') . $gmt_offset,
        'memory_limit'   => WP_MEMORY_LIMIT,
        'max_upload'     => size_format(wp_max_upload_size()),
        'permalink'      => get_option('permalink_structure', 'Default'),
        'debug_mode'     => defined('WP_DEBUG') && WP_DEBUG ? 'ON' : 'OFF',
    ];
}

// ============================================
// 4. RENDER PAGE
// ============================================
function tr_render_report_page() {
    $products = tr_get_products_data();
    $taxonomy = tr_get_taxonomy_tree();
    $settings = tr_get_settings_status();
    $system   = tr_get_system_info();
    $biz      = tr_get_business_reporting_data();

    // Stats
    $total_products  = count($products);
    $published       = count(array_filter($products, fn($p) => $p['status'] === 'publish'));
    $draft           = count(array_filter($products, fn($p) => $p['status'] === 'draft'));
    $with_images     = count(array_filter($products, fn($p) => $p['has_image']));
    $with_excerpt    = count(array_filter($products, fn($p) => $p['excerpt']));
    $total_categories = 0;
    $empty_categories = 0;
    $count_cats = function($tree) use (&$count_cats, &$total_categories, &$empty_categories) {
        foreach ($tree as $t) {
            $total_categories++;
            if ($t['count'] === 0 && empty($t['children'])) $empty_categories++;
            if (!empty($t['children'])) $count_cats($t['children']);
        }
    };
    $count_cats($taxonomy);

    // Settings completion
    $settings_filled = array_sum(array_column($settings, 'filled'));
    $settings_total  = array_sum(array_column($settings, 'total'));
    $settings_pct    = $settings_total > 0 ? round($settings_filled / $settings_total * 100) : 0;

    // Content completeness score
    $completeness_items = [
        ['label' => 'Products with Images', 'score' => $total_products > 0 ? round($with_images / $total_products * 100) : 0],
        ['label' => 'Products with Excerpt', 'score' => $total_products > 0 ? round($with_excerpt / $total_products * 100) : 0],
        ['label' => 'Theme Settings', 'score' => $settings_pct],
    ];

    // Avg meta filled
    $avg_meta = $total_products > 0 ? round(array_sum(array_column($products, 'filled_meta')) / $total_products / 14 * 100) : 0;
    $completeness_items[] = ['label' => 'Product Data (Spec Fields)', 'score' => $avg_meta];

    $overall_score = count($completeness_items) > 0 ? round(array_sum(array_column($completeness_items, 'score')) / count($completeness_items)) : 0;

    ?>
    <div class="rp-wrap">

        <!-- Header -->
        <div class="rp-header">
            <div class="rp-header-left">
                <h1>📊 System Report</h1>
                <p class="rp-subtitle">Theme Development Report — <?php echo esc_html(get_bloginfo('name')); ?></p>
            </div>
            <div class="rp-header-right">
                <span class="rp-badge rp-badge-info">
                    <?php echo esc_html(tr_thai_date('full')); ?>
                </span>
                <?php
                $rp_page = get_page_by_path('theme-report');
                if ($rp_page) :
                ?>
                <a href="<?php echo get_permalink($rp_page); ?>" class="rp-btn rp-btn-primary" target="_blank">🌐 View Page</a>
                <a href="<?php echo admin_url('post.php?post=' . $rp_page->ID . '&action=edit'); ?>" class="rp-btn rp-btn-primary">✏️ Edit in Block Editor</a>
                <?php endif; ?>
                <button class="rp-btn rp-btn-primary" onclick="window.print()">🖨️ Print Report</button>
            </div>
        </div>

        <!-- Overall Score -->
        <div class="rp-score-banner">
            <div class="rp-score-circle">
                <svg viewBox="0 0 36 36">
                    <path class="rp-score-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"></path>
                    <path class="rp-score-fill" stroke-dasharray="<?php echo $overall_score; ?>, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"></path>
                </svg>
                <span class="rp-score-text"><?php echo $overall_score; ?>%</span>
            </div>
            <div class="rp-score-info">
                <h2>Website Completion Score</h2>
                <p>Calculated from product images, content quality, spec fields, and site settings.</p>
                <div class="rp-score-details">
                    <?php foreach ($completeness_items as $ci) : ?>
                    <div class="rp-score-item">
                        <span><?php echo esc_html($ci['label']); ?></span>
                        <div class="rp-progress-bar">
                            <div class="rp-progress-fill" style="width:<?php echo $ci['score']; ?>%"></div>
                        </div>
                        <span class="rp-score-val <?php echo $ci['score'] >= 80 ? 'good' : ($ci['score'] >= 50 ? 'warn' : 'bad'); ?>">
                            <?php echo $ci['score']; ?>%
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="rp-stats-grid">
            <div class="rp-stat-card">
                <div class="rp-stat-icon" style="background:#dbeafe;color:#2563eb;">📦</div>
                <div class="rp-stat-body">
                    <h3><?php echo $total_products; ?></h3>
                    <p>Total Products</p>
                </div>
                <div class="rp-stat-detail">
                    <span class="good"><?php echo $published; ?> Published</span>
                    <?php if ($draft) : ?><span class="warn"><?php echo $draft; ?> Draft</span><?php endif; ?>
                </div>
            </div>
            <div class="rp-stat-card">
                <div class="rp-stat-icon" style="background:#fef3c7;color:#d97706;">📁</div>
                <div class="rp-stat-body">
                    <h3><?php echo $total_categories; ?></h3>
                    <p>Total Categories</p>
                </div>
                <div class="rp-stat-detail">
                    <?php if ($empty_categories) : ?><span class="warn"><?php echo $empty_categories; ?> Empty</span><?php endif; ?>
                    <span class="good"><?php echo $total_categories - $empty_categories; ?> With Products/Children</span>
                </div>
            </div>
            <div class="rp-stat-card">
                <div class="rp-stat-icon" style="background:#d1fae5;color:#059669;">🖼️</div>
                <div class="rp-stat-body">
                    <h3><?php echo $with_images; ?>/<?php echo $total_products; ?></h3>
                    <p>Products with Images</p>
                </div>
                <div class="rp-stat-detail">
                    <?php $missing = $total_products - $with_images; ?>
                    <?php if ($missing) : ?><span class="bad"><?php echo $missing; ?> Missing Images</span><?php endif; ?>
                    <?php if (!$missing) : ?><span class="good">Complete ✓</span><?php endif; ?>
                </div>
            </div>
            <div class="rp-stat-card">
                <div class="rp-stat-icon" style="background:#ede9fe;color:#7c3aed;">⚙️</div>
                <div class="rp-stat-body">
                    <h3><?php echo $settings_filled; ?>/<?php echo $settings_total; ?></h3>
                    <p>Settings Completed</p>
                </div>
                <div class="rp-stat-detail">
                    <span class="<?php echo $settings_pct >= 80 ? 'good' : ($settings_pct >= 50 ? 'warn' : 'bad'); ?>">
                        <?php echo $settings_pct; ?>% Complete
                    </span>
                </div>
            </div>
        </div>

        <!-- Business Reporting -->
        <div class="rp-card rp-card-full">
            <div class="rp-card-header">
                <h2>📈 Dashboard / Reporting (B2B)</h2>
            </div>
            <div class="rp-card-body">
                <div class="rp-stats-grid">
                    <div class="rp-stat-card">
                        <div class="rp-stat-icon" style="background:#ecfeff;color:#0e7490;">👥</div>
                        <div class="rp-stat-body">
                            <h3><?php echo number_format($biz['contact_unique_email']); ?></h3>
                            <p>Unique Contact Customers</p>
                        </div>
                        <div class="rp-stat-detail">
                            <span class="good"><?php echo number_format($biz['contact_total']); ?> Total Submissions</span>
                            <span class="warn"><?php echo number_format($biz['contact_today']); ?> Today</span>
                        </div>
                    </div>

                    <div class="rp-stat-card">
                        <div class="rp-stat-icon" style="background:#fef3c7;color:#b45309;">🧾</div>
                        <div class="rp-stat-body">
                            <h3><?php echo number_format($biz['rfq_total']); ?></h3>
                            <p>Request for Quote (RFQ)</p>
                        </div>
                        <div class="rp-stat-detail">
                            <span class="good">From Contact Forms</span>
                        </div>
                    </div>

                    <div class="rp-stat-card">
                        <div class="rp-stat-icon" style="background:#dcfce7;color:#15803d;">📥</div>
                        <div class="rp-stat-body">
                            <h3><?php echo number_format($biz['datasheet_total']); ?></h3>
                            <p>Datasheet Downloads</p>
                        </div>
                        <div class="rp-stat-detail">
                            <span class="warn"><?php echo number_format($biz['datasheet_today']); ?> Today</span>
                            <span class="good">Top Products below</span>
                        </div>
                    </div>

                    <div class="rp-stat-card">
                        <div class="rp-stat-icon" style="background:#ede9fe;color:#6d28d9;">🤖</div>
                        <div class="rp-stat-body">
                            <h3><?php echo $biz['rag_success_rate']; ?>%</h3>
                            <p>RAG Chat Success Rate</p>
                        </div>
                        <div class="rp-stat-detail">
                            <span class="good"><?php echo number_format($biz['rag_success']); ?>/<?php echo number_format($biz['rag_total']); ?> Success</span>
                            <span class="warn">Avg <?php echo number_format($biz['rag_avg_ms']); ?> ms</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Two Column -->
        <div class="rp-grid-2">

            <!-- Products Table -->
            <div class="rp-card rp-card-full">
                <div class="rp-card-header">
                    <h2>📦 Product List</h2>
                    <a href="<?php echo admin_url('admin.php?page=product-manager'); ?>" class="rp-btn rp-btn-sm">Open Product Manager →</a>
                </div>
                <div class="rp-card-body rp-table-wrap">
                    <table class="rp-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Images</th>
                                <th>Specs</th>
                                <th>Last Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($products)) : ?>
                            <tr><td colspan="7" class="rp-empty">No products yet</td></tr>
                            <?php endif; ?>
                            <?php foreach ($products as $p) : ?>
                            <tr>
                                <td class="rp-id">#<?php echo $p['id']; ?></td>
                                <td>
                                    <strong><?php echo esc_html($p['title']); ?></strong>
                                    <?php if (!$p['excerpt']) : ?><span class="rp-tag warn">No Excerpt</span><?php endif; ?>
                                </td>
                                <td><span class="rp-tag"><?php echo esc_html($p['category']); ?></span></td>
                                <td>
                                    <span class="rp-status <?php echo $p['status']; ?>">
                                        <?php echo $p['status'] === 'publish' ? 'Published' : ucfirst($p['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($p['has_image']) : ?>
                                    <span class="rp-check good">✓ <?php echo $p['image_count']; ?> images</span>
                                    <?php else : ?>
                                    <span class="rp-check bad">✗ None</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="rp-mini-bar">
                                        <div class="rp-mini-fill" style="width:<?php echo round($p['filled_meta']/$p['total_meta']*100); ?>%"></div>
                                    </div>
                                    <span class="rp-mini-text"><?php echo $p['filled_meta']; ?>/<?php echo $p['total_meta']; ?></span>
                                </td>
                                <td class="rp-date"><?php echo esc_html(tr_thai_date('short', $p['modified'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Taxonomy Tree -->
            <div class="rp-card">
                <div class="rp-card-header">
                    <h2>🗂️ Category Structure</h2>
                </div>
                <div class="rp-card-body">
                    <?php
                    function tr_render_tree($items, $depth = 0) {
                        foreach ($items as $item) :
                    ?>
                    <div class="rp-tree-item" style="padding-left:<?php echo $depth * 24; ?>px;">
                        <div class="rp-tree-row">
                            <span class="rp-tree-icon"><?php echo empty($item['children']) ? '📄' : '📁'; ?></span>
                            <span class="rp-tree-name"><?php echo esc_html($item['name']); ?></span>
                            <span class="rp-tree-count <?php echo $item['count'] === 0 && empty($item['children']) ? 'empty' : ''; ?>">
                                <?php echo $item['count']; ?> products
                            </span>
                            <?php if (!$item['has_image']) : ?>
                            <span class="rp-tag warn" style="font-size:10px;">No image</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php
                        if (!empty($item['children'])) tr_render_tree($item['children'], $depth + 1);
                        endforeach;
                    }
                    tr_render_tree($taxonomy);
                    ?>
                </div>
            </div>

            <!-- Settings Status -->
            <div class="rp-card">
                <div class="rp-card-header">
                    <h2>⚙️ Settings Status</h2>
                    <a href="<?php echo esc_url(tr_same_host_admin_url('page=my-theme-settings&tab=appearance')); ?>" class="rp-btn rp-btn-sm">Open Settings →</a>
                </div>
                <div class="rp-card-body">
                    <?php foreach ($settings as $group) : ?>
                    <div class="rp-settings-group">
                        <div class="rp-settings-header">
                            <h3><?php echo esc_html($group['group']); ?></h3>
                            <span class="rp-score-val <?php echo $group['filled'] === $group['total'] ? 'good' : ($group['filled'] > 0 ? 'warn' : 'bad'); ?>">
                                <?php echo $group['filled']; ?>/<?php echo $group['total']; ?>
                            </span>
                        </div>
                        <div class="rp-settings-items">
                            <?php foreach ($group['fields'] as $f) : ?>
                            <div class="rp-setting-item <?php echo $f['has'] ? 'filled' : 'empty'; ?>">
                                <span class="rp-setting-check"><?php echo $f['has'] ? '✓' : '✗'; ?></span>
                                <span class="rp-setting-label"><?php echo esc_html($f['label']); ?></span>
                                <?php if ($f['has'] && $f['value']) : ?>
                                <span class="rp-setting-value"><?php echo esc_html($f['value']); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="rp-card rp-card-full">
                <div class="rp-card-header">
                    <h2>🏆 Top Datasheet Downloads</h2>
                    <a href="<?php echo admin_url('admin.php?page=datasheet-leads'); ?>" class="rp-btn rp-btn-sm">Open Datasheet Leads →</a>
                </div>
                <div class="rp-card-body rp-table-wrap">
                    <table class="rp-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th>Downloads</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($biz['top_datasheet'])) : ?>
                            <tr><td colspan="3" class="rp-empty">No datasheet download data yet</td></tr>
                            <?php else : ?>
                                <?php foreach ($biz['top_datasheet'] as $idx => $row) : ?>
                                <tr>
                                    <td class="rp-id">#<?php echo $idx + 1; ?></td>
                                    <td>
                                        <?php $product_url = kv_get_product_view_url($row['product_id'] ?? 0, $row['product_name'] ?? ''); ?>
                                        <a href="<?php echo esc_url($product_url); ?>" target="_blank" rel="noopener">
                                            <?php echo esc_html($row['product_name']); ?>
                                        </a>
                                    </td>
                                    <td><strong><?php echo number_format((int) $row['downloads']); ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="rp-card rp-card-full">
                <div class="rp-card-header">
                    <h2>🧠 RAG Chat History & Success</h2>
                    <a href="<?php echo admin_url('admin.php?page=rag-search-settings'); ?>" class="rp-btn rp-btn-sm">Open RAG Settings →</a>
                </div>
                <div class="rp-card-body rp-table-wrap">
                    <?php if (!$biz['has_rag_logs']) : ?>
                        <p class="rp-empty" style="margin:0;">No RAG log table yet. It will be created automatically when chat is used.</p>
                    <?php elseif (empty($biz['rag_recent'])) : ?>
                        <p class="rp-empty" style="margin:0;">No chatbot history yet.</p>
                    <?php else : ?>
                        <table class="rp-table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Query</th>
                                    <th>Lang</th>
                                    <th>Results</th>
                                    <th>Status</th>
                                    <th>Latency</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($biz['rag_recent'] as $row) : ?>
                                <tr>
                                    <td class="rp-date"><?php echo esc_html(tr_thai_date('datetime', $row['created_at'])); ?></td>
                                    <td><?php echo esc_html(mb_strimwidth((string) $row['query_text'], 0, 90, '...')); ?></td>
                                    <td><span class="rp-tag"><?php echo esc_html(strtoupper((string) $row['lang'])); ?></span></td>
                                    <td><?php echo number_format((int) $row['result_count']); ?></td>
                                    <td>
                                        <span class="rp-status <?php echo !empty($row['success']) ? 'publish' : 'draft'; ?>">
                                            <?php echo !empty($row['success']) ? 'Success' : 'No Answer'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo number_format((int) $row['response_time_ms']); ?> ms</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- System Info -->
        <div class="rp-card rp-card-system">
            <div class="rp-card-header">
                <h2>🖥️ System Information</h2>
            </div>
            <div class="rp-card-body">
                <div class="rp-sys-grid">
                    <div class="rp-sys-group">
                        <h4>WordPress</h4>
                        <div class="rp-sys-item"><span>WordPress Version</span><span><?php echo esc_html($system['wp_version']); ?></span></div>
                        <div class="rp-sys-item"><span>Site URL</span><span><?php echo esc_html($system['site_url']); ?></span></div>
                        <div class="rp-sys-item"><span>Permalink</span><span><code><?php echo esc_html($system['permalink']); ?></code></span></div>
                        <div class="rp-sys-item"><span>Debug Mode</span><span class="<?php echo $system['debug_mode'] === 'ON' ? 'rp-tag warn' : ''; ?>"><?php echo $system['debug_mode']; ?></span></div>
                        <div class="rp-sys-item"><span>Timezone</span><span><?php echo esc_html($system['timezone']); ?></span></div>
                        <div class="rp-sys-item"><span>Active Plugins</span><span><?php echo $system['active_plugins']; ?></span></div>
                    </div>
                    <div class="rp-sys-group">
                        <h4>Server</h4>
                        <div class="rp-sys-item"><span>PHP Version</span><span><?php echo esc_html($system['php_version']); ?></span></div>
                        <div class="rp-sys-item"><span>MySQL Version</span><span><?php echo esc_html($system['mysql_version']); ?></span></div>
                        <div class="rp-sys-item"><span>Memory Limit</span><span><?php echo esc_html($system['memory_limit']); ?></span></div>
                        <div class="rp-sys-item"><span>Max Upload</span><span><?php echo esc_html($system['max_upload']); ?></span></div>
                        <div class="rp-sys-item"><span>DB Size</span><span><?php echo esc_html($system['db_size']); ?></span></div>
                        <div class="rp-sys-item"><span>Options Count</span><span><?php echo number_format($system['options_count']); ?></span></div>
                    </div>
                    <div class="rp-sys-group">
                        <h4>Theme Files</h4>
                        <div class="rp-sys-item"><span>Theme</span><span><?php echo esc_html($system['theme_name']); ?> v<?php echo esc_html($system['theme_version']); ?></span></div>
                        <div class="rp-sys-item"><span>PHP Files (root)</span><span><?php echo $system['php_files']; ?> files</span></div>
                        <div class="rp-sys-item"><span>Block Patterns</span><span><?php echo $system['pattern_files']; ?> files</span></div>
                        <div class="rp-sys-item"><span>CSS Files</span><span><?php echo $system['css_files']; ?> files</span></div>
                        <div class="rp-sys-item"><span>JS Files</span><span><?php echo $system['js_files']; ?> files</span></div>
                        <?php if (!empty($system['plugin_list'])) : ?>
                        <div class="rp-sys-item rp-sys-plugins">
                            <span>Plugins</span>
                            <span>
                                <?php foreach ($system['plugin_list'] as $pl) : ?>
                                <code><?php echo esc_html(basename(dirname($pl)) ?: basename($pl)); ?></code>
                                <?php endforeach; ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Color Preview -->
        <div class="rp-card">
            <div class="rp-card-header">
                <h2>🎨 Color System (60:30:10)</h2>
                <a href="<?php echo esc_url(tr_same_host_admin_url('page=my-theme-settings&tab=appearance')); ?>" class="rp-btn rp-btn-sm">Edit Colors →</a>
            </div>
            <div class="rp-card-body">
                <div class="rp-color-grid">
                    <?php
                    $colors = [
                        ['label' => 'Background Color (60%)', 'key' => 'theme_bg_color', 'default' => '#ffffff'],
                        ['label' => 'Primary Color (30%)', 'key' => 'theme_primary_color', 'default' => '#0056d6'],
                        ['label' => 'Accent Color (10%)', 'key' => 'theme_accent_color', 'default' => '#4ecdc4'],
                    ];
                    foreach ($colors as $c) :
                        $val = get_option($c['key'], $c['default']);
                    ?>
                    <div class="rp-color-item">
                        <div class="rp-color-swatch" style="background:<?php echo esc_attr($val); ?>"></div>
                        <div class="rp-color-info">
                            <strong><?php echo esc_html($c['label']); ?></strong>
                            <code><?php echo esc_html($val); ?></code>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="rp-footer">
            <p>Generated by Theme Report System — <?php echo esc_html(tr_thai_date('datetime')); ?></p>
        </div>

    </div>
    <?php
}
