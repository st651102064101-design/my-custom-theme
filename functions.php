<?php
/**
 * My Custom Theme Functions
 * 
 * Theme setup, block patterns registration, styles and scripts
 * Custom Post Type: Products + ACF fields
 */

if (!function_exists('kv_theme_bootstrap_error_logging')) {
    function kv_theme_bootstrap_error_logging() {
        if (defined('WP_DEBUG') && WP_DEBUG) return;
        if (!defined('WP_CONTENT_DIR')) return;

        $log_file = WP_CONTENT_DIR . '/kv-theme-runtime.log';
        @ini_set('log_errors', '1');
        @ini_set('display_errors', '0');
        @ini_set('error_log', $log_file);

        register_shutdown_function(function () use ($log_file) {
            $error = error_get_last();
            if (!$error) return;

            $fatal_types = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR];
            if (!in_array((int) $error['type'], $fatal_types, true)) return;

            $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
            $msg = sprintf(
                "[KV_FATAL] %s | URI: %s | %s in %s:%d\n",
                gmdate('c'),
                $uri,
                (string) ($error['message'] ?? ''),
                (string) ($error['file'] ?? ''),
                (int) ($error['line'] ?? 0)
            );

            @error_log($msg, 3, $log_file);
        });
    }

    kv_theme_bootstrap_error_logging();
}

add_action('admin_init', function () {
    global $concatenate_scripts, $compress_scripts, $compress_css;
    $concatenate_scripts = false;
    $compress_scripts = false;
    $compress_css = false;
}, 1);

add_filter('rest_url', function ($url, $path, $blog_id, $scheme) {
    if (!is_admin()) return $url;
    $home = (string) home_url('/');
    $is_local = strpos($home, 'localhost') !== false || strpos($home, '127.0.0.1') !== false || strpos($home, '.local') !== false;
    if (!$is_local) return $url;
    $route = '/' . ltrim((string) $path, '/');
    return add_query_arg('rest_route', $route, home_url('/index.php'));
}, 20, 4);

add_filter('rest_request_after_callbacks', function ($response, $handler, $request) {
    try {
        $route  = method_exists($request, 'get_route') ? (string) $request->get_route() : '';
        $method = method_exists($request, 'get_method') ? (string) $request->get_method() : '';
        if (strpos($route, '/wp/v2/pages/') !== 0) {
            return $response;
        }

        $status = 200;
        $err_code = '';
        $err_msg = '';

        if (is_wp_error($response)) {
            $status = (int) ($response->get_error_data()['status'] ?? 500);
            $err_code = (string) $response->get_error_code();
            $err_msg = (string) $response->get_error_message();
        } elseif ($response instanceof WP_REST_Response) {
            $status = (int) $response->get_status();
        }

        if ($status >= 400 || in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            $log_file = defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR . '/kv-rest-debug.log' : dirname(__DIR__, 2) . '/kv-rest-debug.log';
            $user = wp_get_current_user();
            $msg = sprintf(
                "[%s] route=%s method=%s status=%d user_id=%d user=%s can_edit_pages=%s can_edit_post=%s err_code=%s err_msg=%s uri=%s\n",
                gmdate('c'),
                $route,
                $method,
                $status,
                (int) get_current_user_id(),
                isset($user->user_login) ? (string) $user->user_login : '',
                current_user_can('edit_pages') ? 'yes' : 'no',
                preg_match('#/wp/v2/pages/(\d+)#', $route, $m) ? (current_user_can('edit_post', (int) $m[1]) ? 'yes' : 'no') : 'n/a',
                $err_code,
                str_replace(["\n", "\r"], ' ', $err_msg),
                isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : ''
            );
            @error_log($msg, 3, $log_file);
        }
    } catch (Throwable $e) {
    }

    return $response;
}, 99, 3);

/* ── IIS REST Auth Fix ──
   Auth is now handled by MU plugin (kv-iis-rest-fix.php).
   See wp-content/mu-plugins/kv-iis-rest-fix.php ── */

/* ── IIS CORS fix: IIS sends CORS headers via customHeaders,
      remove WordPress duplicates to avoid "multiple values" rejection ── */
add_action('rest_api_init', function () {
    $home = (string) home_url('/');
    $is_local = (strpos($home, 'localhost') !== false || strpos($home, '127.0.0.1') !== false || strpos($home, '.local') !== false);
    if ($is_local) return;

    // Remove WordPress built-in CORS header sender
    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');

    // After WP_REST_Server sends headers, strip the PHP-level CORS ones
    // so only IIS customHeaders remain (avoids duplicate header values)
    add_filter('rest_pre_serve_request', function ($served) {
        header_remove('Access-Control-Allow-Origin');
        header_remove('Access-Control-Allow-Methods');
        header_remove('Access-Control-Allow-Credentials');
        header_remove('Access-Control-Allow-Headers');
        header_remove('Access-Control-Expose-Headers');
        // IIS customHeaders handles all CORS headers uniformly
        return $served;
    }, 999);
}, 0);

/* ── IIS Verb Override Fix ──
   IIS/Plesk blocks PUT, PATCH, DELETE verbs (returns 403).
   WordPress's default httpV1Middleware sends POST + X-HTTP-Method-Override header,
   but IIS also blocks that header.
   Fix: Replace with POST + _method=VERB query parameter (WordPress REST API reads $_GET['_method']).
   This runs on production only (not localhost). ── */
add_action('enqueue_block_editor_assets', function () {
    $home = (string) home_url('/');
    $is_local = (strpos($home, 'localhost') !== false
              || strpos($home, '127.0.0.1') !== false
              || strpos($home, '.local') !== false);
    if ($is_local) return;

    $js = <<<'JS'
( function() {
    if ( ! wp || ! wp.apiFetch ) return;
    wp.apiFetch.use( function( options, next ) {
        var method = ( options.method || 'GET' ).toUpperCase();
        if ( method !== 'PUT' && method !== 'PATCH' && method !== 'DELETE' ) {
            return next( options );
        }
        // Convert to POST + _method query param
        options.method = 'POST';
        if ( options.path ) {
            options.path += ( options.path.indexOf('?') !== -1 ? '&' : '?' ) + '_method=' + method;
        }
        if ( options.url ) {
            options.url += ( options.url.indexOf('?') !== -1 ? '&' : '?' ) + '_method=' + method;
        }
        // Remove X-HTTP-Method-Override if httpV1Middleware already added it
        if ( options.headers ) {
            delete options.headers['X-HTTP-Method-Override'];
        }
        return next( options );
    } );
} )();
JS;
    wp_add_inline_script('wp-api-fetch', $js, 'after');
}, 1);

if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        $haystack = (string) $haystack;
        $needle = (string) $needle;
        if ($needle === '') return true;
        return strpos($haystack, $needle) !== false;
    }
}

if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        $haystack = (string) $haystack;
        $needle = (string) $needle;
        if ($needle === '') return true;
        return strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        $haystack = (string) $haystack;
        $needle = (string) $needle;
        if ($needle === '') return true;
        return substr($haystack, -strlen($needle)) === $needle;
    }
}

if (!function_exists('kv_theme_get_brevo_config')) {
    function kv_theme_get_brevo_config() {
        $get = static function($key, $default = '') {
            $const_keys = ['KV_BREVO_' . $key, 'BREVO_' . $key];
            foreach ($const_keys as $const_key) {
                if (defined($const_key)) {
                    return constant($const_key);
                }
            }

            $env_keys = ['KV_BREVO_' . $key, 'BREVO_' . $key];
            foreach ($env_keys as $env_key) {
                $value = getenv($env_key);
                if ($value !== false && $value !== '') {
                    return $value;
                }
            }

            $option_key = 'kv_brevo_' . strtolower($key);
            $option_value = get_option($option_key, null);
            if ($option_value !== null && $option_value !== '') {
                return $option_value;
            }

            return $default;
        };

        $api_key = trim((string) $get('API_KEY', ''));
        $from = sanitize_email((string) $get('FROM', get_option('admin_email', '')));
        $from_name = sanitize_text_field((string) $get('FROM_NAME', get_bloginfo('name')));

        return [
            'api_key' => $api_key,
            'from' => $from,
            'from_name' => $from_name,
            'enabled' => ($api_key !== '' && is_email($from)),
        ];
    }
}

if (!function_exists('kv_theme_parse_email_list')) {
    function kv_theme_parse_email_list($raw) {
        $items = is_array($raw) ? $raw : preg_split('/,/', (string) $raw);
        $items = is_array($items) ? $items : [];
        $parsed = [];

        foreach ($items as $item) {
            $item = trim((string) $item);
            if ($item === '') continue;

            $name = '';
            $email = '';

            if (preg_match('/^(.+?)\s*<([^>]+)>$/', $item, $m)) {
                $name = trim((string) $m[1], " \t\n\r\0\x0B\"'");
                $email = sanitize_email((string) $m[2]);
            } else {
                $email = sanitize_email($item);
            }

            if (!is_email($email)) continue;

            $entry = ['email' => $email];
            if ($name !== '') {
                $entry['name'] = $name;
            }

            $parsed[] = $entry;
        }

        return $parsed;
    }
}

if (!function_exists('kv_theme_detect_html_mail')) {
    function kv_theme_detect_html_mail($headers) {
        if (empty($headers)) return false;
        $lines = is_array($headers) ? $headers : preg_split('/\r\n|\r|\n/', (string) $headers);
        if (!is_array($lines)) return false;

        foreach ($lines as $line) {
            if (stripos((string) $line, 'content-type:') === false) continue;
            if (stripos((string) $line, 'text/html') !== false) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('kv_theme_set_last_mail_error')) {
    function kv_theme_set_last_mail_error($message) {
        $GLOBALS['kv_theme_last_mail_error'] = (string) $message;
    }
}

if (!function_exists('kv_theme_get_last_mail_error')) {
    function kv_theme_get_last_mail_error() {
        return isset($GLOBALS['kv_theme_last_mail_error']) ? (string) $GLOBALS['kv_theme_last_mail_error'] : '';
    }
}

if (!function_exists('kv_theme_set_last_mail_message_id')) {
    function kv_theme_set_last_mail_message_id($message_id) {
        $GLOBALS['kv_theme_last_mail_message_id'] = (string) $message_id;
    }
}

if (!function_exists('kv_theme_get_last_mail_message_id')) {
    function kv_theme_get_last_mail_message_id() {
        return isset($GLOBALS['kv_theme_last_mail_message_id']) ? (string) $GLOBALS['kv_theme_last_mail_message_id'] : '';
    }
}

add_filter('pre_wp_mail', function($return, $atts) {
    kv_theme_set_last_mail_error('');
    kv_theme_set_last_mail_message_id('');

    $brevo = kv_theme_get_brevo_config();
    if (empty($brevo['enabled'])) {
        return $return;
    }

    if (stripos((string) $brevo['api_key'], 'xsmtpsib-') === 0) {
        kv_theme_set_last_mail_error('คีย์ที่ใช้เป็น SMTP key (xsmtpsib-) แต่โหมดนี้ต้องใช้ Brevo API key (xkeysib-)');
        return false;
    }

    if (function_exists('kv_theme_is_non_routable_email') && kv_theme_is_non_routable_email($brevo['from'])) {
        kv_theme_set_last_mail_error('From Email ต้องเป็นโดเมนจริงที่รับส่งได้ (ห้ามใช้ .local/.test/.example) และต้องยืนยัน Sender ใน Brevo');
        return false;
    }

    $to = kv_theme_parse_email_list($atts['to'] ?? '');
    if (empty($to)) {
        kv_theme_set_last_mail_error('ไม่พบอีเมลผู้รับ (To)');
        return false;
    }

    $subject = (string) ($atts['subject'] ?? '');
    $message = (string) ($atts['message'] ?? '');
    $headers = $atts['headers'] ?? [];

    $payload = [
        'sender' => [
            'email' => (string) $brevo['from'],
            'name' => (string) $brevo['from_name'],
        ],
        'to' => $to,
        'subject' => $subject,
    ];

    if (kv_theme_detect_html_mail($headers)) {
        $payload['htmlContent'] = $message;
    } else {
        $payload['textContent'] = wp_strip_all_tags($message);
    }

    $response = wp_remote_post('https://api.brevo.com/v3/smtp/email', [
        'timeout' => 20,
        'headers' => [
            'accept' => 'application/json',
            'content-type' => 'application/json',
            'api-key' => (string) $brevo['api_key'],
        ],
        'body' => wp_json_encode($payload),
    ]);

    if (is_wp_error($response)) {
        $err = (string) $response->get_error_message();
        kv_theme_set_last_mail_error('Brevo request error: ' . $err);
        @error_log('[KV_MAIL] Brevo request error: ' . $err);
        return false;
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    $body = (string) wp_remote_retrieve_body($response);
    if ($code < 200 || $code >= 300) {
        kv_theme_set_last_mail_error('Brevo API failed (' . $code . '): ' . $body);
        @error_log('[KV_MAIL] Brevo API failed (' . $code . '): ' . $body);
        return false;
    }

    $json = json_decode($body, true);
    if (is_array($json) && !empty($json['messageId'])) {
        kv_theme_set_last_mail_message_id((string) $json['messageId']);
    }

    return true;
}, 5, 2);

if (!function_exists('kv_theme_is_non_routable_email')) {
    function kv_theme_is_non_routable_email($email) {
        $email = sanitize_email((string) $email);
        if (!is_email($email)) {
            return true;
        }

        $parts = explode('@', $email);
        $domain = strtolower((string) end($parts));
        if ($domain === '') {
            return true;
        }

        if (
            str_ends_with($domain, '.local') ||
            str_ends_with($domain, '.localhost') ||
            $domain === 'localhost' ||
            str_ends_with($domain, '.invalid') ||
            str_ends_with($domain, '.test') ||
            str_ends_with($domain, '.example')
        ) {
            return true;
        }

        return false;
    }
}

/**
 * Rebase a stored URL to the current request's origin.
 * Replaces the scheme+host of any stored URL with home_url()'s scheme+host
 * so images load correctly whether accessed via localhost or a LAN IP.
 */
function kv_rebase_url( $url ) {
    if ( empty( $url ) ) return '';
    $home   = home_url();
    $parsed = parse_url( $home );
    $origin = $parsed['scheme'] . '://' . $parsed['host'];
    if ( ! empty( $parsed['port'] ) ) {
        $origin .= ':' . $parsed['port'];
    }
    return preg_replace( '#^https?://[^/]+#', $origin, $url );
}
    function kv_safe_image_url($url) {
        if (empty($url)) return '';

        $url = kv_rebase_url((string) $url);
        $attachment_id = attachment_url_to_postid($url);
        if (!$attachment_id) {
            return $url;
        }

        $has_non_ascii = (bool) preg_match('/[^\x20-\x7E]/u', $url);
        $looks_utf8_encoded = (stripos($url, '%e0%') !== false);
        $is_iis = !empty($_SERVER['SERVER_SOFTWARE']) && stripos((string) $_SERVER['SERVER_SOFTWARE'], 'IIS') !== false;

        if ($has_non_ascii || $looks_utf8_encoded || ($is_iis && strpos($url, '/wp-content/uploads/') !== false)) {
            return add_query_arg([
                'kv_media_proxy' => '1',
                'id'             => (int) $attachment_id,
            ], site_url('/'));
        }

        return $url;
    }

    function kv_media_proxy_endpoint() {
        if (!isset($_GET['kv_media_proxy'])) {
            return;
        }

        $attachment_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        if ($attachment_id <= 0) {
            status_header(400);
            exit;
        }

        $mime = (string) get_post_mime_type($attachment_id);
        if ($mime === '' || strpos($mime, 'image/') !== 0) {
            status_header(403);
            exit;
        }

        $file = get_attached_file($attachment_id);
        if (!$file || !file_exists($file)) {
            status_header(404);
            exit;
        }

        nocache_headers();
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($file));
        header('Content-Disposition: inline; filename="' . basename($file) . '"');
        readfile($file);
        exit;
    }
    add_action('template_redirect', 'kv_media_proxy_endpoint', 0);

// Allow SVG upload in Media Library for admins (used by logo upload fields)
add_filter('upload_mimes', function($mimes) {
    if (!current_user_can('manage_options')) return $mimes;
    $mimes['svg']  = 'image/svg+xml';
    $mimes['svgz'] = 'image/svg+xml';
    return $mimes;
});

add_filter('wp_check_filetype_and_ext', function($data, $file, $filename, $mimes) {
    if (!current_user_can('manage_options')) return $data;
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if ($ext === 'svg' || $ext === 'svgz') {
        $data['ext']  = $ext;
        $data['type'] = 'image/svg+xml';
    }
    return $data;
}, 10, 4);

/**
 * แปลงเบอร์โทรรูปแบบสากล (+66...) ให้เป็นรูปแบบไทย (0X-XXX-XXXX)
 * ตัวอย่าง: +66 (0)2 323 9169-70 → 02-323-9169-70
 *          +6621088521            → 02-108-8521
 */
function kv_format_phone_th($raw) {
    if (empty($raw)) return (string)$raw;
    $s = trim((string)$raw);
    // +66 (0)X... → 0X...
    $s = preg_replace('/^\+66\s*\(0\)\s*/', '0', $s);
    // +66X... → 0X...
    $s = preg_replace('/^\+66\s*/', '0', $s);
    // Spaces → dashes
    $s = preg_replace('/\s+/', '-', $s);
    // Collapse double dashes
    $s = preg_replace('/-{2,}/', '-', $s);
    // Pure digits without dashes → add dash pattern
    if (!str_contains($s, '-')) {
        $len = strlen($s);
        if ($len === 9 && str_starts_with($s, '02')) {
            return '02-' . substr($s, 2, 3) . '-' . substr($s, 5);
        }
        if ($len === 10 && str_starts_with($s, '02')) {
            return '02-' . substr($s, 2, 4) . '-' . substr($s, 6);
        }
        if ($len === 10) {
            return substr($s, 0, 3) . '-' . substr($s, 3, 3) . '-' . substr($s, 6);
        }
    }
    return $s;
}

function kv_get_site_phone_raw_display($default = '+66 2 108 8521') {
    $raw = get_option('site_phone', get_theme_mod('site_phone', $default));
    if (function_exists('kv_clean_text_option_value')) {
        $raw = kv_clean_text_option_value($raw, $default);
    }
    $display = trim((string) $raw);
    return $display !== '' ? $display : $default;
}

function kv_get_site_fax_raw_display($default = '') {
    $raw = get_option('site_fax', get_theme_mod('site_fax', $default));
    if (function_exists('kv_clean_text_option_value')) {
        $raw = kv_clean_text_option_value($raw, $default);
    }
    return trim((string) $raw);
}

function my_theme_get_company_stats_values() {
    $years_auto    = get_option('site_years_auto', '0');
    $products_auto = get_option('site_products_auto', '0');
    $founded_year  = (int) get_option('site_founded_year', 1988);

    $years_exp = ($years_auto === '1' && $founded_year > 0)
        ? max(0, (int) date('Y') - $founded_year)
        : (int) get_option('site_years_experience', 20);

    if ($products_auto === '1') {
        $pc = wp_count_posts('product');
        $total_prod = isset($pc->publish) ? (int) $pc->publish : 0;
    } else {
        $total_prod = (int) get_option('site_total_products', 500);
    }

    return [
        'years'     => $years_exp,
        'products'  => $total_prod,
        'countries' => (int) get_option('site_countries_served', 50),
        'customers' => (int) get_option('site_happy_customers', 1000),
    ];
}

function my_theme_default_about_certifications() {
    return array(
        array('icon' => '🏆', 'title' => 'ISO 9001:2015', 'description' => 'Quality Management System Certified'),
        array('icon' => '🌿', 'title' => 'ISO 14001:2015', 'description' => 'Environmental Management Certified'),
        array('icon' => '🇹🇭', 'title' => 'BOI Promoted', 'description' => 'Thailand Board of Investment'),
        array('icon' => '✅', 'title' => 'RoHS3 & IPC-A-610', 'description' => 'Conflict Free Compliant'),
        array('icon' => '⚙️', 'title' => 'Custom Manufacturing', 'description' => 'Design-to-production support'),
    );
}

/**
 * Flush common WordPress page-cache plugins so the frontend reflects nav changes.
 */
function my_theme_flush_page_caches() {
    // LiteSpeed server-level purge via HTTP header (works even without WP plugin)
    if ( ! headers_sent() ) {
        header( 'X-LiteSpeed-Purge: *', true );
    }
    // LiteSpeed Cache (action-based)
    do_action( 'litespeed_purge_all' );
    // LiteSpeed Cache (direct class)
    if ( class_exists( '\LiteSpeed\Purge' ) ) {
        try { \LiteSpeed\Purge::purge_all(); } catch ( \Throwable $e ) {}
    }
    // WP Rocket
    if ( function_exists( 'rocket_clean_domain' ) ) rocket_clean_domain();
    // W3 Total Cache
    if ( function_exists( 'w3tc_flush_all' ) ) w3tc_flush_all();
    // WP Super Cache
    if ( function_exists( 'wp_cache_clear_cache' ) ) wp_cache_clear_cache();
    // WP Fastest Cache
    if ( function_exists( 'wpfc_clear_all_cache' ) ) wpfc_clear_all_cache();
    if ( class_exists( 'WpFastestCache' ) && method_exists( 'WpFastestCache', 'deleteCache' ) ) {
        try { ( new WpFastestCache() )->deleteCache( true ); } catch ( \Throwable $e ) {}
    }
    // Breeze (Cloudways)
    if ( function_exists( 'breeze_clear_all_cache' ) ) breeze_clear_all_cache();
    do_action( 'breeze_clear_all_cache' );
    // Cache Enabler
    do_action( 'cache_enabler_clear_complete_cache' );
    // Flying Press
    do_action( 'flying_press_purge_all' );
    // Comet Cache
    if ( class_exists( 'Comet_Cache' ) && method_exists( 'Comet_Cache', 'clearCache' ) ) {
        try { \Comet_Cache::clearCache(); } catch ( \Throwable $e ) {}
    }
    // Nginx Helper (Nginx FastCGI Cache)
    do_action( 'rt_nginx_helper_purge_all' );
    // SG Optimizer (SiteGround)
    if ( function_exists( 'sg_cachepress_purge_cache' ) ) sg_cachepress_purge_cache();
    // Autoptimize
    if ( class_exists( 'autoptimizeCache' ) && method_exists( 'autoptimizeCache', 'clearall' ) ) {
        try { \autoptimizeCache::clearall(); } catch ( \Throwable $e ) {}
    }
    // Varnish
    do_action( 'varnish_ip_purge' );
    // WordPress object cache (in-process)
    wp_cache_flush();
    // Generic action for other plugins
    do_action( 'my_theme_flush_page_caches' );
}

// Auto-flush page cache whenever nav_menu_items_json is created or updated
add_action( 'updated_option', function( $option ) {
    if ( $option === 'nav_menu_items_json' ) {
        my_theme_flush_page_caches();
    }
} );
add_action( 'added_option', function( $option ) {
    if ( $option === 'nav_menu_items_json' ) {
        my_theme_flush_page_caches();
    }
} );

/**
 * Sanitize nav_menu_items_json — array of menu item objects
 * Each item: { id, label, url, type, visible, new_tab }
 */
function my_theme_sanitize_nav_menu_items_json( $raw ) {
    $decoded = array();
    if ( is_array( $raw ) ) {
        $decoded = $raw;
    } elseif ( is_string( $raw ) && trim( $raw ) !== '' ) {
        // REST API body is already JSON-decoded; do NOT wp_unslash here as it would corrupt the JSON
        $decoded = json_decode( $raw, true );
        if ( ! is_array( $decoded ) ) {
            // Try once more after stripping any magic-quote-style slashes (POST form submissions)
            $decoded = json_decode( wp_unslash( $raw ), true );
        }
        if ( ! is_array( $decoded ) ) {
            return '';
        }
    }
    $allowed_types = array( 'home', 'products', 'custom', 'about', 'contact' );
    $items = array();
    foreach ( $decoded as $item ) {
        if ( ! is_array( $item ) ) continue;
        $type = sanitize_text_field( (string) ( $item['type'] ?? 'custom' ) );
        if ( ! in_array( $type, $allowed_types, true ) ) $type = 'custom';
        $url_raw = (string) ( $item['url'] ?? '' );
        // Allow relative paths or absolute URLs; strip obviously bad schemes
        $url_san = ( strpos( $url_raw, 'javascript' ) === false ) ? esc_url_raw( $url_raw ) : '';
        $items[] = array(
            'id'      => sanitize_key( (string) ( $item['id']    ?? ( 'item_' . count( $items ) ) ) ),
            'label'   => sanitize_text_field( (string) ( $item['label']   ?? '' ) ),
            'url'     => $url_san,
            'type'    => $type,
            'visible' => ! empty( $item['visible'] ),
            'new_tab' => ! empty( $item['new_tab']  ),
        );
    }
    return wp_json_encode( $items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
}

function my_theme_sanitize_about_certifications_json($raw) {
    $decoded = array();

    if (is_array($raw)) {
        $decoded = $raw;
    } elseif (is_string($raw) && trim($raw) !== '') {
        $decoded = json_decode(wp_unslash($raw), true);
        if (!is_array($decoded)) {
            $decoded = array();
        }
    }

    $items = array();
    foreach ($decoded as $item) {
        if (!is_array($item)) continue;
        if (count($items) >= 5) break;

        $icon = sanitize_text_field((string) ($item['icon'] ?? ''));
        $title = sanitize_text_field((string) ($item['title'] ?? ''));
        $description = sanitize_text_field((string) ($item['description'] ?? ''));

        if ($icon === '' && $title === '' && $description === '') continue;
        if ($icon === '') $icon = '✅';

        $items[] = array(
            'icon' => $icon,
            'title' => $title,
            'description' => $description,
        );
    }

    if (empty($items)) {
        $items = my_theme_default_about_certifications();
    }

    return wp_json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function my_theme_get_about_certifications() {
    $raw = get_option('about_certifications_json', '');
    $json = my_theme_sanitize_about_certifications_json($raw);
    $items = json_decode($json, true);
    if (!is_array($items) || empty($items)) {
        return my_theme_default_about_certifications();
    }

    $defaults = my_theme_default_about_certifications();
    if (count($items) < 5) {
        for ($index = count($items); $index < 5; $index++) {
            if (!isset($defaults[$index])) break;
            $items[] = $defaults[$index];
        }
    }

    if (count($items) > 5) {
        $items = array_slice($items, 0, 5);
    }

    return $items;
}

function my_theme_build_about_certifications_columns_html($items) {
    $cards = '';
    foreach ($items as $item) {
        $icon = esc_html((string) ($item['icon'] ?? '✅'));
        $title = esc_html((string) ($item['title'] ?? ''));
        $description = esc_html((string) ($item['description'] ?? ''));

        $cards .= '<!-- wp:column {"className":"kv-about-cert-item","style":{"spacing":{"padding":{"top":"30px","bottom":"30px","left":"30px","right":"30px"}}}} -->';
        $cards .= '<div class="wp-block-column kv-about-cert-item" style="padding-top:30px;padding-right:30px;padding-bottom:30px;padding-left:30px">';
        $cards .= '<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"48px"},"spacing":{"margin":{"bottom":"15px"}}}} -->';
        $cards .= '<p class="has-text-align-center" style="margin-bottom:15px;font-size:48px">' . $icon . '</p>';
        $cards .= '<!-- /wp:paragraph -->';
        $cards .= '<!-- wp:heading {"textAlign":"center","level":4,"style":{"typography":{"fontSize":"20px"},"spacing":{"margin":{"bottom":"10px"}}}} -->';
        $cards .= '<h4 class="wp-block-heading has-text-align-center" style="margin-bottom:10px;font-size:20px">' . $title . '</h4>';
        $cards .= '<!-- /wp:heading -->';
        $cards .= '<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#64748b"}}} -->';
        $cards .= '<p class="has-text-align-center has-text-color" style="color:#64748b">' . $description . '</p>';
        $cards .= '<!-- /wp:paragraph -->';
        $cards .= '</div>';
        $cards .= '<!-- /wp:column -->';
    }

    return '<!-- wp:columns {"className":"kv-about-cert-grid","style":{"spacing":{"blockGap":{"left":"30px"}}}} -->'
        . '<div class="wp-block-columns kv-about-cert-grid">'
        . $cards
        . '</div>'
        . '<!-- /wp:columns -->';
}

// Replace hardcoded stats numbers in About page block content with DB values
add_filter('the_content', function($content) {
    if (is_admin() || !is_page('about')) return $content;
    if (!is_string($content) || $content === '') return $content;

    try {
        $stats = my_theme_get_company_stats_values();
        if (!is_array($stats)) return $content;

        $map = [
            'Years Experience' => (string) ($stats['years'] ?? 0) . '+',
            'Products'         => (string) ($stats['products'] ?? 0) . '+',
            'Countries Served' => (string) ($stats['countries'] ?? 0) . '+',
            'Happy Customers'  => (string) ($stats['customers'] ?? 0) . '+',
        ];

        foreach ($map as $label => $value_text) {
            $pattern = '/(<p[^>]*has-primary-color[^>]*>)[^<]*(<\/p>\s*<p[^>]*>\s*' . preg_quote($label, '/') . '\s*<\/p>)/i';
            $updated = preg_replace_callback($pattern, function($m) use ($value_text) {
                return $m[1] . esc_html($value_text) . $m[2];
            }, $content, 1);

            if (is_string($updated) && $updated !== '') {
                $content = $updated;
            }
        }

        $cert_columns = my_theme_build_about_certifications_columns_html(my_theme_get_about_certifications());
        $content = preg_replace_callback(
            '/(<h2[^>]*>\s*Certifications\s*&(?:amp;)?\s*Quality\s*<\/h2>\s*<!-- \/wp:heading -->\s*)(<!-- wp:columns[\s\S]*?<!-- \/wp:columns -->)/i',
            function($m) use ($cert_columns) {
                return $m[1] . $cert_columns;
            },
            $content,
            1
        );
    } catch (Throwable $e) {
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log('[KV_ABOUT_FILTER] ' . $e->getMessage());
        }
    }

    return $content;
}, 25);

// Replace hardcoded contact phone text in Contact page block content with DB phone + fax values
add_filter('the_content', function($content) {
    if (is_admin() || !is_page('contact')) return $content;
    if (!is_string($content) || $content === '') return $content;

    $phone_text = kv_get_site_phone_raw_display('+66 2 108 8521');
    if ($phone_text === '') return $content;
    $fax_text = kv_get_site_fax_raw_display('');

    $phone_block_html = esc_html($phone_text);
    if ($fax_text !== '') {
        $phone_block_html .= '<br><span>Fax: ' . esc_html($fax_text) . '</span>';
    }

    $updated = preg_replace_callback(
        '/(<h4[^>]*>\s*Phone\s*<\/h4>\s*(?:<!--\s*\/wp:heading\s*-->\s*)?<p[^>]*>)(.*?)(<\/p>)/is',
        function($m) use ($phone_block_html) {
            return $m[1] . $phone_block_html . $m[3];
        },
        $content,
        1
    );

    return is_string($updated) ? $updated : $content;
}, 26);

// Keep favicon/site-icon URL on current origin (localhost / LAN) when DB stores another host
add_filter('get_site_icon_url', function($url) {
    if (empty($url)) return $url;
    return kv_rebase_url($url);
}, 10, 1);

// Fallback favicon: use theme logo option when Site Icon is not configured
add_action('wp_head', function() {
    if (is_admin() || has_site_icon()) return;

    $logo = kv_rebase_url(get_option('site_logo_url', ''));
    if (empty($logo)) return;

    echo '<link rel="icon" href="' . esc_url($logo) . '" sizes="32x32" />' . "\n";
    echo '<link rel="apple-touch-icon" href="' . esc_url($logo) . '" />' . "\n";
}, 1);

// ============================================
// PRODUCT MANAGER — Admin CRUD Pages
// ============================================
if (is_admin()) {
    require_once get_template_directory() . '/admin/product-manager.php';
}

// Front-end fallback for Product Manager spec helpers
// (admin/product-manager.php is loaded only in wp-admin)
if (!is_admin() && !function_exists('pm_get_all_spec_fields')) {
    function pm_get_all_spec_fields(): array {
        $builtin = [
            ['key' => 'pd_inductance',    'label' => 'Inductance'],
            ['key' => 'pd_current_rating','label' => 'Current Rating'],
            ['key' => 'pd_impedance',     'label' => 'Impedance @ 100MHz'],
            ['key' => 'pd_voltage',       'label' => 'Voltage Rating'],
            ['key' => 'pd_frequency',     'label' => 'Frequency Range'],
            ['key' => 'pd_dcr',           'label' => 'DC Resistance'],
            ['key' => 'pd_insulation',    'label' => 'Insulation Resistance'],
            ['key' => 'pd_hipot',         'label' => 'Hi-Pot (Dielectric)'],
            ['key' => 'pd_turns_ratio',   'label' => 'Turns Ratio'],
            ['key' => 'pd_power_rating',  'label' => 'Power Rating'],
            ['key' => 'pd_dimensions',    'label' => 'Dimensions (L x W x H)'],
            ['key' => 'pd_weight',        'label' => 'Weight'],
            ['key' => 'pd_pin_config',    'label' => 'Pin Configuration'],
            ['key' => 'pd_mount_type',    'label' => 'Mounting Type'],
            ['key' => 'pd_core_material', 'label' => 'Core Material'],
            ['key' => 'pd_land_pattern',  'label' => 'Land Pattern'],
            ['key' => 'pd_winding',       'label' => 'Winding Construction'],
            ['key' => 'pd_core_shape',    'label' => 'Core Shape'],
            ['key' => 'pd_core_size',     'label' => 'Core Size'],
            ['key' => 'pd_bobbin_pin',    'label' => 'Bobbin Pin Type'],
            ['key' => 'pd_wire_type',     'label' => 'Wire Type'],
            ['key' => 'pd_wire_size',     'label' => 'Wire Size'],
            ['key' => 'pd_size_range',    'label' => 'Size Range / Form Factor'],
            ['key' => 'pd_output_range',  'label' => 'Output Range'],
            ['key' => 'pd_temp_range',    'label' => 'Operating Temperatures'],
            ['key' => 'pd_package_type',  'label' => 'Packaging Options'],
            ['key' => 'pd_packing_qty',   'label' => 'Packing Quantity'],
            ['key' => 'pd_standards',     'label' => 'Standards'],
            ['key' => 'pd_compliance',    'label' => 'Environmental Compliance'],
            ['key' => 'pd_safety_certs',  'label' => 'Safety Certifications'],
            ['key' => 'pd_storage_conditions', 'label' => 'Storage Conditions'],
            ['key' => 'pd_msl',           'label' => 'Moisture Sensitivity Level'],
        ];

        $disabled = get_option('spec_disabled_builtin_fields', []);
        if (!is_array($disabled)) $disabled = [];
        $disabled = array_values(array_unique(array_map('sanitize_key', $disabled)));

        $builtin_active = array_values(array_filter($builtin, function($f) use ($disabled) {
            return !in_array($f['key'], $disabled, true);
        }));

        $custom_raw = get_option('spec_custom_fields', []);
        if (!is_array($custom_raw)) $custom_raw = [];
        $custom = [];
        foreach ($custom_raw as $f) {
            $key = sanitize_key($f['key'] ?? '');
            $label = sanitize_text_field($f['label'] ?? '');
            if ($key && $label) {
                $custom[] = ['key' => $key, 'label' => $label];
            }
        }

        $all = array_merge($builtin_active, $custom);
        $order = get_option('spec_field_order', []);
        if (!is_array($order) || empty($order)) return $all;

        $rank = [];
        foreach ($order as $i => $k) {
            $k = sanitize_key((string) $k);
            if ($k !== '') $rank[$k] = $i;
        }

        usort($all, function($a, $b) use ($rank) {
            $ka = (string) ($a['key'] ?? '');
            $kb = (string) ($b['key'] ?? '');
            $ra = array_key_exists($ka, $rank) ? $rank[$ka] : PHP_INT_MAX;
            $rb = array_key_exists($kb, $rank) ? $rank[$kb] : PHP_INT_MAX;
            if ($ra === $rb) return 0;
            return ($ra < $rb) ? -1 : 1;
        });

        return $all;
    }
}

if (!is_admin() && !function_exists('pm_get_spec_icons')) {
    function pm_get_spec_icons(): array {
        $defaults = [
            'pd_inductance'    => 'fa fa-bolt',
            'pd_current_rating'=> 'fa fa-tachometer',
            'pd_impedance'     => 'fa fa-signal',
            'pd_voltage'       => 'fa fa-plug',
            'pd_frequency'     => 'fa fa-line-chart',
            'pd_dcr'           => 'fa fa-flash',
            'pd_insulation'    => 'fa fa-shield',
            'pd_hipot'         => 'fa fa-superpowers',
            'pd_turns_ratio'   => 'fa fa-retweet',
            'pd_power_rating'  => 'fa fa-battery-full',
            'pd_dimensions'    => 'fa fa-arrows',
            'pd_weight'        => 'fa fa-balance-scale',
            'pd_pin_config'    => 'fa fa-sitemap',
            'pd_mount_type'    => 'fa fa-microchip',
            'pd_core_material' => 'fa fa-circle-o',
            'pd_land_pattern'  => 'fa fa-th',
            'pd_winding'       => 'fa fa-random',
            'pd_core_shape'    => 'fa fa-diamond',
            'pd_core_size'     => 'fa fa-arrows-alt',
            'pd_bobbin_pin'    => 'fa fa-thumb-tack',
            'pd_wire_type'     => 'fa fa-chain',
            'pd_wire_size'     => 'fa fa-text-width',
            'pd_size_range'    => 'fa fa-expand',
            'pd_output_range'  => 'fa fa-exchange',
            'pd_temp_range'    => 'fa fa-thermometer-half',
            'pd_package_type'  => 'fa fa-cube',
            'pd_packing_qty'   => 'fa fa-cubes',
            'pd_standards'     => 'fa fa-check-circle',
            'pd_compliance'    => 'fa fa-leaf',
            'pd_safety_certs'  => 'fa fa-certificate',
            'pd_storage_conditions' => 'fa fa-archive',
            'pd_msl'           => 'fa fa-tint',
        ];
        $saved = get_option('spec_field_icons', []);
        if (!is_array($saved)) $saved = [];
        return array_merge($defaults, $saved);
    }
}

// ============================================
// THEME REPORT — Admin Report Dashboard
// ============================================
if (is_admin()) {
    require_once get_template_directory() . '/admin/theme-report.php';
}

// ============================================
// RAG SEARCH ENGINE — Level 3 Semantic Search
// ============================================
require_once get_template_directory() . '/admin/rag-engine.php';

// ============================================
// ACCOUNT MANAGER — จัดการ Username / Email / Password
// ============================================
if (is_admin()) {
    require_once get_template_directory() . '/admin/account-manager.php';
}

// ============================================
// BLOCK EDITOR MANAGER — จัดการ Block Editor 100%
// ============================================
if (is_admin()) {
    require_once get_template_directory() . '/admin/block-editor-manager.php';
}

// ============================================
// BLOCK EDITOR GUIDE — คู่มือการใช้งาน Block Editor
// ============================================
if (is_admin()) {
    require_once get_template_directory() . '/admin/block-editor-guide.php';
}

// ============================================
// BLOCK NAVBAR & FOOTER SHORTCODES — [kv_navbar] / [kv_footer]
// ============================================
require_once get_template_directory() . '/admin/block-navbar-footer.php';

// ============================================
// CONTACT PAGE MIGRATION — Legacy HTML -> Core Blocks (Builder-friendly)
// ============================================
function kv_get_pattern_blocks_content($relative_pattern_path) {
    $file = trailingslashit(get_template_directory()) . ltrim($relative_pattern_path, '/');
    if (!file_exists($file)) return '';
    $raw = (string) file_get_contents($file);
    if ($raw === '') return '';
    return (string) preg_replace('/^\s*<\?php[\s\S]*?\?>\s*/', '', $raw, 1);
}

function kv_migrate_contact_page_to_core_blocks($force = false) {
    $target = get_page_by_path('contact', OBJECT, 'page');
    if (!$target) $target = get_page_by_path('contacts', OBJECT, 'page');
    if (!$target || empty($target->ID)) return false;

    $pattern_content = kv_get_pattern_blocks_content('patterns/page-contacts.php');
    if ($pattern_content === '') return false;

    $current = (string) get_post_field('post_content', $target->ID);
    $is_legacy_html =
        (strpos($current, '<!-- wp:html -->') !== false)
        && (
            strpos($current, 'contact-info') !== false
            || strpos($current, 'contact-form') !== false
            || strpos($current, 'id="cf-form"') !== false
        );

    if (!$force && !$is_legacy_html) return false;

    wp_update_post([
        'ID'           => (int) $target->ID,
        'post_content' => $pattern_content,
    ]);

    update_option('kv_contact_builder_migrated_v1', '1');
    return true;
}

function kv_upgrade_contact_form_shortcode_to_block($force = false) {
    $target = get_page_by_path('contact', OBJECT, 'page');
    if (!$target) $target = get_page_by_path('contacts', OBJECT, 'page');
    if (!$target || empty($target->ID)) return false;

    $current = (string) get_post_field('post_content', $target->ID);
    $has_short = (strpos($current, '[kv_contact_form]') !== false);
    $has_block = (strpos($current, '<!-- wp:kv/contact-form') !== false);

    if (!$force && (!$has_short || $has_block)) return false;

    $updated = preg_replace(
        '/<!--\s*wp:shortcode\s*-->\s*\[kv_contact_form\]\s*<!--\s*\/wp:shortcode\s*-->/m',
        '<!-- wp:kv/contact-form /-->',
        $current
    );

    if ($updated === null || $updated === $current) {
        $updated = str_replace('[kv_contact_form]', '<!-- wp:kv/contact-form /-->', $current);
    }

    if ($updated === $current) return false;

    wp_update_post([
        'ID'           => (int) $target->ID,
        'post_content' => $updated,
    ]);

    update_option('kv_contact_form_block_migrated_v1', '1');
    return true;
}

function kv_upgrade_contact_map_to_block($force = false) {
    $target = get_page_by_path('contact', OBJECT, 'page');
    if (!$target) $target = get_page_by_path('contacts', OBJECT, 'page');
    if (!$target || empty($target->ID)) return false;

    $current = (string) get_post_field('post_content', $target->ID);
    $has_map_block = (strpos($current, '<!-- wp:kv/google-map') !== false);

    $has_placeholder_text = (
        strpos($current, 'วางบล็อก Google Maps Embed หรือบล็อก HTML ได้ที่นี่') !== false
        || strpos($current, 'Google Map จะแสดงที่นี่') !== false
    );
    $has_html_iframe_map = (
        strpos($current, '<!-- wp:html -->') !== false
        && strpos($current, 'google.com/maps/embed') !== false
    );

    if (!$force && ($has_map_block || (!$has_placeholder_text && !$has_html_iframe_map))) return false;

    $map_block_markup = "<!-- wp:kv/google-map {\"height\":360,\"showInfoCard\":false,\"wrapperMT\":0,\"wrapperMB\":0,\"mapBorderRadius\":0} /-->";
    $updated = $current;

    if ($has_html_iframe_map) {
        $updated = preg_replace(
            '/<!--\s*wp:group\s*\{\"align\":\"full\"[\s\S]*?<!--\s*\/wp:group\s*-->/m',
            $map_block_markup,
            $updated,
            1
        );
    }

    if ($updated === $current && $has_placeholder_text) {
        $updated = preg_replace(
            '/<!--\s*wp:group\s*\{\"align\":\"full\"[\s\S]*?วางบล็อก Google Maps Embed หรือบล็อก HTML ได้ที่นี่[\s\S]*?<!--\s*\/wp:group\s*-->/m',
            $map_block_markup,
            $updated,
            1
        );
    }

    if ($updated === null || $updated === $current) {
        $updated = str_replace('<!-- wp:paragraph {"align":"center"} --><p class="has-text-align-center">วางบล็อก Google Maps Embed หรือบล็อก HTML ได้ที่นี่</p><!-- /wp:paragraph -->', $map_block_markup, $updated);
    }

    if ($updated === $current) return false;

    wp_update_post([
        'ID'           => (int) $target->ID,
        'post_content' => $updated,
    ]);

    update_option('kv_contact_map_block_migrated_v1', '1');
    return true;
}

add_action('admin_init', function() {
    if (!current_user_can('edit_pages')) return;

    // Manual migration trigger: /wp-admin/?kv_migrate_contact_builder=1
    if (isset($_GET['kv_migrate_contact_builder']) && $_GET['kv_migrate_contact_builder'] === '1') {
        $done = kv_migrate_contact_page_to_core_blocks(true);
        $redirect = remove_query_arg('kv_migrate_contact_builder');
        $redirect = add_query_arg('kv_migrate_contact_builder_done', $done ? '1' : '0', $redirect);
        wp_safe_redirect($redirect);
        exit;
    }

    // Manual upgrade trigger: /wp-admin/?kv_upgrade_contact_form_block=1
    if (isset($_GET['kv_upgrade_contact_form_block']) && $_GET['kv_upgrade_contact_form_block'] === '1') {
        $done = kv_upgrade_contact_form_shortcode_to_block(true);
        $redirect = remove_query_arg('kv_upgrade_contact_form_block');
        $redirect = add_query_arg('kv_upgrade_contact_form_block_done', $done ? '1' : '0', $redirect);
        wp_safe_redirect($redirect);
        exit;
    }

    // Manual upgrade trigger: /wp-admin/?kv_upgrade_contact_map_block=1
    if (isset($_GET['kv_upgrade_contact_map_block']) && $_GET['kv_upgrade_contact_map_block'] === '1') {
        $done = kv_upgrade_contact_map_to_block(true);
        $redirect = remove_query_arg('kv_upgrade_contact_map_block');
        $redirect = add_query_arg('kv_upgrade_contact_map_block_done', $done ? '1' : '0', $redirect);
        wp_safe_redirect($redirect);
        exit;
    }

    // One-time auto migration for legacy HTML contact page content
    if (get_option('kv_contact_builder_migrated_v1', '0') !== '1') {
        kv_migrate_contact_page_to_core_blocks(false);
    }

    // One-time upgrade: replace [kv_contact_form] shortcode with visual kv/contact-form block
    if (get_option('kv_contact_form_block_migrated_v1', '0') !== '1') {
        kv_upgrade_contact_form_shortcode_to_block(false);
    }

    // One-time upgrade: replace legacy map placeholder/html with kv/google-map block
    if (get_option('kv_contact_map_block_migrated_v1', '0') !== '1') {
        kv_upgrade_contact_map_to_block(false);
    }
});

// ============================================
// FRONT-END ENQUEUE — Page Templates (Report & Product Manager)
// ============================================
add_action('wp_enqueue_scripts', function() {
    if (!is_page()) return;

    $template = get_page_template_slug();
    $theme_dir = get_template_directory();
    $theme_uri = get_template_directory_uri();

    // ── Report Dashboard page template ──
    if ($template === 'page-theme-report.php') {
        wp_enqueue_style(
            'tr-report-css',
            $theme_uri . '/assets/css/admin-report.css',
            [],
            filemtime($theme_dir . '/assets/css/admin-report.css')
        );

        // Dynamic color override
        $color = get_option('theme_primary_color', '#0056d6');
        $hex = ltrim($color, '#');
        if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        $r = hexdec(substr($hex,0,2)); $g = hexdec(substr($hex,2,2)); $b = hexdec(substr($hex,4,2));
        $dark  = sprintf('#%02x%02x%02x', max(0,round($r*0.82)), max(0,round($g*0.82)), max(0,round($b*0.82)));
        $light = sprintf('#%02x%02x%02x', min(255,$r+round((255-$r)*0.92)), min(255,$g+round((255-$g)*0.92)), min(255,$b+round((255-$b)*0.92)));
        wp_add_inline_style('tr-report-css', ":root{--rp-primary:{$color};--rp-primary-dark:{$dark};--rp-primary-light:{$light};}");

        // Front-end override: reset the WP admin negative margins
        wp_add_inline_style('tr-report-css', '.report-dashboard-section .rp-wrap{margin:0;padding:0;}');
    }

    // ── Product Manager page template ──
    if ($template === 'page-product-manager.php') {
        wp_enqueue_media();

        wp_enqueue_style(
            'pm-admin-css',
            $theme_uri . '/assets/css/admin-product-manager.css',
            [],
            filemtime($theme_dir . '/assets/css/admin-product-manager.css')
        );

        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css',
            [],
            '4.7.0'
        );

        wp_enqueue_script(
            'pm-admin-js',
            $theme_uri . '/assets/js/admin-product-manager.js',
            ['jquery'],
            filemtime($theme_dir . '/assets/js/admin-product-manager.js'),
            true
        );

        // Dynamic color override
        $pm_color = get_option('theme_primary_color', '#0056d6');
        $pm_hex   = ltrim($pm_color, '#');
        if (strlen($pm_hex) === 3) $pm_hex = $pm_hex[0].$pm_hex[0].$pm_hex[1].$pm_hex[1].$pm_hex[2].$pm_hex[2];
        $pm_r = hexdec(substr($pm_hex,0,2)); $pm_g = hexdec(substr($pm_hex,2,2)); $pm_b = hexdec(substr($pm_hex,4,2));
        $pm_dark  = sprintf('#%02x%02x%02x', max(0,round($pm_r*0.82)), max(0,round($pm_g*0.82)), max(0,round($pm_b*0.82)));
        $pm_light = sprintf('#%02x%02x%02x', min(255,$pm_r+round((255-$pm_r)*0.92)), min(255,$pm_g+round((255-$pm_g)*0.92)), min(255,$pm_b+round((255-$pm_b)*0.92)));
        wp_add_inline_style('pm-admin-css', ":root{--pm-primary:{$pm_color};--pm-primary-dark:{$pm_dark};--pm-primary-light:{$pm_light};}");

        // Front-end override: reset WP admin negative margins, full-width
        wp_add_inline_style('pm-admin-css', '.product-manager-section .pm-wrap{margin:0;}');

        wp_localize_script('pm-admin-js', 'PM', [
            'ajax'       => admin_url('admin-ajax.php'),
            'nonce'      => wp_create_nonce('pm_nonce'),
            'theme'      => $theme_uri,
            'specFields' => [
                // --- Electrical ---
                ['key' => 'pd_inductance',      'label' => 'Inductance'],
                ['key' => 'pd_current_rating',  'label' => 'Rated Current (Ir)'],
                ['key' => 'pd_impedance',       'label' => 'Impedance @ 100MHz'],
                ['key' => 'pd_voltage',         'label' => 'Voltage Rating'],
                ['key' => 'pd_frequency',       'label' => 'Frequency Range'],
                ['key' => 'pd_dcr',             'label' => 'DC Resistance (DCR)'],
                ['key' => 'pd_insulation',      'label' => 'Insulation Resistance'],
                ['key' => 'pd_hipot',           'label' => 'Hi-Pot / Dielectric Strength'],
                ['key' => 'pd_turns_ratio',     'label' => 'Turns Ratio'],
                ['key' => 'pd_power_rating',    'label' => 'Power Rating'],
                ['key' => 'pd_temp_range',      'label' => 'Operating Temperature'],
                // --- Mechanical ---
                ['key' => 'pd_dimensions',      'label' => 'Dimensions (L x W x H)'],
                ['key' => 'pd_weight',          'label' => 'Weight'],
                ['key' => 'pd_pin_config',      'label' => 'Pin Configuration'],
                ['key' => 'pd_mount_type',      'label' => 'Mounting Type'],
                ['key' => 'pd_core_material',   'label' => 'Core Material'],
                ['key' => 'pd_wire_type',       'label' => 'Wire Type / Gauge'],
                ['key' => 'pd_land_pattern',    'label' => 'Land Pattern / Footprint'],
                // --- Size / Output ---
                ['key' => 'pd_size_range',      'label' => 'Size Range / Form Factor'],
                ['key' => 'pd_output_range',    'label' => 'Output Range'],
                // --- Compliance / Packaging ---
                ['key' => 'pd_package_type',    'label' => 'Packaging Type'],
                ['key' => 'pd_standards',       'label' => 'Quality Standards'],
                ['key' => 'pd_compliance',      'label' => 'Environmental Compliance (RoHS, REACH)'],
                ['key' => 'pd_packing_qty',     'label' => 'Packing Quantity'],
                // --- Misc ---
                ['key' => 'pd_status',          'label' => 'Product Status (Active/New/NRND)'],
                ['key' => 'pd_applications',    'label' => 'Applications (one per line)'],
                ['key' => 'pd_performance_notes','label'=> 'Performance Notes (one per line)'],
                // --- Schematic / Storage / Safety ---
                ['key' => 'pd_schematic_info',  'label' => 'Schematic / Winding Info (one per line)'],
                ['key' => 'pd_storage_conditions','label'=> 'Storage Conditions (temp & humidity)'],
                ['key' => 'pd_msl',             'label' => 'Moisture Sensitivity Level (MSL)'],
                ['key' => 'pd_safety_certs',    'label' => 'Safety Certifications (UL, CSA, etc.)'],
            ],
            'specIcons'  => pm_get_spec_icons(),
        ]);
    }
});

// ============================================
// CUSTOMIZER — CONTACT INFORMATION
// ============================================
add_action('customize_register', function($wp_customize) {
    $wp_customize->add_section('my_theme_contact_info', [
        'title'    => 'ข้อมูลติดต่อ (Contact Info)',
        'priority' => 30,
    ]);

    $fields = [
        'site_phone'   => ['label' => '📞 เบอร์โทรศัพท์',    'default' => ''],
        'site_fax'     => ['label' => '📠 เบอร์แฟกซ์',       'default' => ''],
        // ตั้งค่าเบอร์โทรใน Theme Settings (ปัจจุบัน: +6621088521)
        'site_email'   => ['label' => '✉️ อีเมล',            'default' => 'info@company.com'],
        'site_address' => ['label' => '📍 ที่อยู่',           'default' => '123 Industrial Zone, Bangkok, Thailand'],
    ];

    foreach ($fields as $key => $field) {
        $wp_customize->add_setting($key, [
            'default'           => $field['default'],
            'sanitize_callback' => 'sanitize_text_field',
            'transport'         => 'refresh',
        ]);
        $wp_customize->add_control($key, [
            'label'   => $field['label'],
            'section' => 'my_theme_contact_info',
            'type'    => 'text',
        ]);
    }
});

// Process shortcodes inside wp:html blocks (used in template parts / patterns)
add_filter('render_block_core/html', 'do_shortcode');

// Footer logo shortcode — white version for dark footer
add_shortcode('footer_logo', function() {
    $url = esc_url(kv_rebase_url(get_option('site_logo_light_url', get_option('site_logo_url', home_url('/wp-content/uploads/2026/02/New-Logo-Schott-1-1-300x120-1.jpg')))));
    $alt = esc_attr(get_option('nav_logo_alt', get_bloginfo('name')));
    return '<a href="' . esc_url(home_url('/')) . '" style="display:inline-block;"><img src="' . $url . '" alt="' . $alt . '" height="66" style="max-height:66px;width:auto;display:block;"></a>';
});

// Disable wpautop for product CPT (so shortcode blocks render cleanly)
// Use 'wp' action to ensure is_singular() is available before any rendering
add_action('wp', function() {
    if (is_singular('product') || is_tax('product_category')) {
        remove_filter('the_content', 'wpautop');
        remove_filter('the_content', 'wpautop', 10);
    }
});

// Safety net: strip <p> tags wpautop wraps around <script> / block-level HTML
// Runs on both the_content and shortcode block output
add_filter('the_content', function($content) {
    if (is_singular('product') || is_tax('product_category')) {
        // Remove <p> before opening block-level / script tags
        $content = preg_replace('/<p>\s*(<(?:script|style|div|section|nav|ul|ol|li|form|table)[^>]*>)/i', '$1', $content);
        // Remove </p> after closing block-level / script tags
        $content = preg_replace('/(<\/(?:script|style|div|section|nav|ul|ol|li|form|table)>)\s*<\/p>/i', '$1', $content);
    }
    return $content;
}, 999);

add_filter('render_block_core/shortcode', function($content) {
    // Strip p tags wpautop injects around script blocks
    $content = preg_replace('/<p>\s*(<(?:script|style|div|section|nav|ul|ol|li|form|table)[^>]*>)/i', '$1', $content);
    $content = preg_replace('/(<\/(?:script|style|div|section|nav|ul|ol|li|form|table)>)\s*<\/p>/i', '$1', $content);
    // Also clean p tags that leak inside script (between blank lines)
    $content = preg_replace_callback('/<script\b[^>]*>.*?<\/script>/is', function($m) {
        return preg_replace('/<\/?p>/i', '', $m[0]);
    }, $content);
    return $content;
}, 20);

// ============================================
// ADMIN PARENT MENU — จัดการ (groups product-manager + settings)
// ============================================
add_action('admin_menu', function() {
    // Parent menu
    add_menu_page(
        'Manage',
        '🗂️ Manage',
        'edit_posts',
        'kv-manage',
        '__return_null',          // no page for parent itself
        'dashicons-screenoptions',
        2.5
    );

    // Settings as first submenu
    add_submenu_page(
        'kv-manage',
        'Theme Settings',
        '⚙️ Theme Settings',
        'manage_options',
        'my-theme-settings',
        'my_theme_settings_page'
    );

    // Remove duplicate auto-generated submenu entry for parent
    remove_submenu_page('kv-manage', 'kv-manage');
});

add_action('admin_init', function() {
    // Contact Info
    register_setting('my_theme_settings', 'site_phone',        ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'site_fax',          ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'site_email',        ['sanitize_callback' => 'sanitize_email']);
    register_setting('my_theme_settings', 'site_email_sales',  ['sanitize_callback' => 'sanitize_email']);
    register_setting('my_theme_settings', 'site_address',      ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'site_address_full', ['sanitize_callback' => 'sanitize_textarea_field']);
    register_setting('my_theme_settings', 'site_hours_weekday',['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'site_hours_weekend',['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'site_map_embed',    ['sanitize_callback' => 'esc_url_raw']);
    // Mail (Brevo API - non SMTP)
    register_setting('my_theme_settings', 'kv_brevo_api_key',   ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'kv_brevo_from',      ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'kv_brevo_from_name', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'kv_brevo_test_to',   ['sanitize_callback' => 'sanitize_email']);
    // Company Info
    register_setting('my_theme_settings', 'site_company_name', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'site_copyright',    ['sanitize_callback' => 'sanitize_text_field']);
    // Stats
    register_setting('my_theme_settings', 'site_years_experience',  ['sanitize_callback' => 'absint']);
    register_setting('my_theme_settings', 'site_total_products',    ['sanitize_callback' => 'absint']);
    register_setting('my_theme_settings', 'site_countries_served',  ['sanitize_callback' => 'absint']);
    register_setting('my_theme_settings', 'site_happy_customers',   ['sanitize_callback' => 'absint']);
    register_setting('my_theme_settings', 'site_years_auto',        ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'site_products_auto',     ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'site_founded_year',      ['sanitize_callback' => 'absint']);
    // Logo
    register_setting('my_theme_settings', 'site_logo_url', ['sanitize_callback' => 'esc_url_raw']);
    register_setting('my_theme_settings', 'site_logo_light_url', ['sanitize_callback' => 'esc_url_raw']);
    // Theme Colors (60:30:10)
    register_setting('my_theme_settings', 'theme_primary_color', ['sanitize_callback' => 'sanitize_hex_color']);
    register_setting('my_theme_settings', 'theme_accent_color',  ['sanitize_callback' => 'sanitize_hex_color']);
    register_setting('my_theme_settings', 'theme_bg_color',      ['sanitize_callback' => 'sanitize_hex_color']);
    // Banner
    register_setting('my_theme_settings', 'banner_bg_color',  ['sanitize_callback' => 'sanitize_hex_color']);
    register_setting('my_theme_settings', 'banner_bg_image',  ['sanitize_callback' => 'esc_url_raw']);
    register_setting('my_theme_settings', 'banner_bg_video',  ['sanitize_callback' => 'esc_url_raw']);
    register_setting('my_theme_settings', 'banner_overlay',   ['sanitize_callback' => 'absint']);
    register_setting('my_theme_settings', 'banner_fadein_delay', ['sanitize_callback' => 'absint']);
    register_setting('my_theme_settings', 'banner_video_start', ['sanitize_callback' => function($v){ return max(0, (float) $v); }]);
    register_setting('my_theme_settings', 'banner_video_end', ['sanitize_callback' => function($v){ return max(0, (float) $v); }]);
    // About Us
    register_setting('my_theme_settings', 'about_s1_heading',     ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'about_s1_title1',      ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'about_s1_text1',       ['sanitize_callback' => 'sanitize_textarea_field']);
    register_setting('my_theme_settings', 'about_s1_title2',      ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'about_s1_text2',       ['sanitize_callback' => 'sanitize_textarea_field']);
    register_setting('my_theme_settings', 'about_s1_text3',       ['sanitize_callback' => 'sanitize_textarea_field']);
    register_setting('my_theme_settings', 'about_s1_image',       ['sanitize_callback' => 'esc_url_raw']);
    register_setting('my_theme_settings', 'about_s2_title1',      ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'about_s2_text1',       ['sanitize_callback' => 'sanitize_textarea_field']);
    register_setting('my_theme_settings', 'about_s2_title2',      ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'about_s2_text2',       ['sanitize_callback' => 'sanitize_textarea_field']);
    register_setting('my_theme_settings', 'about_s2_text3',       ['sanitize_callback' => 'sanitize_textarea_field']);
    register_setting('my_theme_settings', 'about_s2_image',       ['sanitize_callback' => 'esc_url_raw']);
    register_setting('my_theme_settings', 'about_mission_text',   ['sanitize_callback' => 'sanitize_textarea_field']);
    register_setting('my_theme_settings', 'about_vision_text',    ['sanitize_callback' => 'sanitize_textarea_field']);
    register_setting('my_theme_settings', 'about_values',         ['sanitize_callback' => 'sanitize_textarea_field']);
    register_setting('my_theme_settings', 'about_cta_heading',    ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'about_cta_text',       ['sanitize_callback' => 'sanitize_textarea_field']);
    register_setting('my_theme_settings', 'about_cta_btn_text',   ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'about_cta_btn_url',    ['sanitize_callback' => 'esc_url_raw']);
    register_setting('my_theme_settings', 'about_certifications_json', ['sanitize_callback' => 'my_theme_sanitize_about_certifications_json']);
    // Navbar
    register_setting('my_theme_settings', 'nav_logo_alt',          ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'nav_logo_height',       ['sanitize_callback' => 'absint']);
    register_setting('my_theme_settings', 'nav_home_label',         ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'nav_home_visible',       ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'nav_about_label',        ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'nav_about_url',          ['sanitize_callback' => 'esc_url_raw']);
    register_setting('my_theme_settings', 'nav_about_visible',      ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'nav_products_label',     ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'nav_products_visible',   ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'nav_contact_label',      ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'nav_contact_url',        ['sanitize_callback' => 'esc_url_raw']);
    register_setting('my_theme_settings', 'nav_contact_visible',    ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'nav_cta_text',           ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'nav_cta_url',            ['sanitize_callback' => 'esc_url_raw']);
    register_setting('my_theme_settings', 'nav_cta_visible',        ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'nav_custom_items',       ['sanitize_callback' => 'sanitize_textarea_field']);
    register_setting('my_theme_settings', 'nav_menu_items_json',    ['sanitize_callback' => 'my_theme_sanitize_nav_menu_items_json']);
    // Navbar style
    register_setting('my_theme_settings', 'nav_bg_color',           ['sanitize_callback' => 'sanitize_hex_color']);
    register_setting('my_theme_settings', 'nav_text_color',         ['sanitize_callback' => 'sanitize_hex_color']);
    register_setting('my_theme_settings', 'nav_hover_color',        ['sanitize_callback' => 'sanitize_hex_color']);
    register_setting('my_theme_settings', 'nav_active_color',       ['sanitize_callback' => 'sanitize_hex_color']);
    register_setting('my_theme_settings', 'nav_font_size',          ['sanitize_callback' => 'absint']);
    register_setting('my_theme_settings', 'nav_font_weight',        ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'nav_align',              ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'nav_sticky',             ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'nav_shadow',             ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'nav_cta_bg',             ['sanitize_callback' => 'sanitize_hex_color']);
    register_setting('my_theme_settings', 'nav_cta_text_color',     ['sanitize_callback' => 'sanitize_hex_color']);
    register_setting('my_theme_settings', 'nav_cta_radius',         ['sanitize_callback' => 'absint']);
    register_setting('my_theme_settings', 'nav_cta_font_size',      ['sanitize_callback' => 'absint']);
    register_setting('my_theme_settings', 'nav_padding_y',          ['sanitize_callback' => 'absint']);
    // Footer
    register_setting('my_theme_settings', 'footer_about_text',  ['sanitize_callback' => 'sanitize_textarea_field']);
    register_setting('my_theme_settings', 'footer_quick_links', ['sanitize_callback' => 'sanitize_textarea_field']);
    // Floating Chat Widget
    register_setting('my_theme_settings', 'chat_widget_enabled',  ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'chat_line_enabled',    ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'chat_line_id',         ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'chat_wechat_enabled',  ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'chat_wechat_id',       ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'chat_wechat_qr_url',   ['sanitize_callback' => 'esc_url_raw']);
    register_setting('my_theme_settings', 'chat_whatsapp_enabled', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'chat_whatsapp_number', ['sanitize_callback' => 'sanitize_text_field']);
    register_setting('my_theme_settings', 'social_facebook_url', ['sanitize_callback' => 'esc_url_raw']);
    register_setting('my_theme_settings', 'social_instagram_url', ['sanitize_callback' => 'esc_url_raw']);
    register_setting('my_theme_settings', 'social_linkedin_url', ['sanitize_callback' => 'esc_url_raw']);
    // RAG AI Chat
    register_setting('my_theme_settings', 'rag_chat_enabled', ['sanitize_callback' => 'sanitize_text_field']);
    // Gallery
    register_setting('my_theme_settings', 'gallery_interval', ['sanitize_callback' => 'absint']);
});

// Output theme color CSS variables on all frontend pages (60:30:10)
add_action('wp_head', function() {
    $primary = get_option('theme_primary_color', '#0056d6');
    $accent  = get_option('theme_accent_color',  '#4ecdc4');
    $bg      = get_option('theme_bg_color',      '#ffffff');

    // Auto-calculate dark variants (82% brightness)
    $makeDark = function($color) {
        $hex = ltrim($color, '#');
        if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        $r = max(0, round(hexdec(substr($hex,0,2)) * 0.82));
        $g = max(0, round(hexdec(substr($hex,2,2)) * 0.82));
        $b = max(0, round(hexdec(substr($hex,4,2)) * 0.82));
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    };

    $primary_dark = $makeDark($primary);
    $accent_dark  = $makeDark($accent);

    echo "<style>:root{"
        . "--theme-bg:"                         . esc_attr($bg) . ";"
        . "--theme-primary:"                    . esc_attr($primary) . ";"
        . "--theme-primary-dark:"               . esc_attr($primary_dark) . ";"
        . "--theme-accent:"                     . esc_attr($accent) . ";"
        . "--theme-accent-dark:"                . esc_attr($accent_dark) . ";"
        . "--wp--preset--color--primary:"       . esc_attr($primary) . ";"
        . "--wp--preset--color--primary-dark:"  . esc_attr($primary_dark) . ";"
        . "--wp--preset--color--accent:"        . esc_attr($accent) . ";"
        . "--wp--preset--color--accent-dark:"   . esc_attr($accent_dark) . ";"
        . "}"
        /* Gutenberg solid button bg → accent from DB */
        . ".wp-block-button:not(.is-style-outline) .wp-block-button__link:not([class*='has-'][class*='-background-color']){"
        .     "background-color:" . esc_attr($accent) . ";"
        .     "color:#fff;"
        . "}"
        . ".wp-block-button:not(.is-style-outline) .wp-block-button__link:not([class*='has-'][class*='-background-color']):hover{"
        .     "background-color:" . esc_attr($accent_dark) . ";"
        . "}"
        /* Gutenberg outline button → accent from DB */
        . ".wp-block-button.is-style-outline .wp-block-button__link:not([class*='has-'][class*='-color']){"
        .     "border-color:" . esc_attr($accent) . ";"
        .     "color:" . esc_attr($accent) . ";"
        . "}"
        . ".wp-block-button.is-style-outline .wp-block-button__link:not([class*='has-'][class*='-color']):hover{"
        .     "border-color:" . esc_attr($accent_dark) . ";"
        .     "color:" . esc_attr($accent_dark) . ";"
        . "}"
        . "</style>\n";
    echo "<script>window.themeColor='" . esc_js($primary) . "';window.themeColorDark='" . esc_js($primary_dark) . "';window.themeAccent='" . esc_js($accent) . "';</script>\n";
}, 100);

// Match About/Contact top banner height with home hero (block-content pages)
add_action('wp_head', function() {
    if (!is_page(['about', 'contact', 'contacts'])) return;
    echo "<style>
    .entry-content > .wp-block-group.alignfull:first-child{
        padding-top:100px !important;
        padding-bottom:100px !important;
        min-height:0 !important;
        display:flex !important;
        flex-direction:column !important;
        justify-content:center !important;
    }
    </style>\n";
}, 30);

// Enqueue WP media uploader on our settings page
add_action('admin_enqueue_scripts', function($hook) {
    $is_theme_settings_page = in_array($hook, ['toplevel_page_kv-manage', 'kv-manage_page_my-theme-settings'], true)
        || (isset($_GET['page']) && $_GET['page'] === 'my-theme-settings');
    if (!$is_theme_settings_page) return;
    wp_enqueue_media();
});

function my_theme_settings_page() {
    if (!current_user_can('manage_options')) return;

    $kv_mail_test_notice = null;

    // Sync: save to both wp_options AND theme_mods so get_theme_mod() still works
    if (isset($_POST['kv_theme_nonce']) && wp_verify_nonce($_POST['kv_theme_nonce'], 'kv_theme_settings_save')) {
        $fields = ['theme_primary_color','theme_accent_color','theme_bg_color','site_phone','site_fax','site_email','site_email_sales','site_address','site_address_full','site_hours_weekday','site_hours_weekend','site_map_embed','kv_brevo_api_key','kv_brevo_from','kv_brevo_from_name','kv_brevo_test_to','site_company_name','site_copyright','site_years_experience','site_total_products','site_countries_served','site_happy_customers','site_years_auto','site_products_auto','site_founded_year','site_logo_url','site_logo_light_url','banner_bg_color','banner_bg_image','banner_bg_video','banner_overlay','banner_fadein_delay','banner_video_start','banner_video_end','about_s1_heading','about_s1_title1','about_s1_text1','about_s1_title2','about_s1_text2','about_s1_text3','about_s1_image','about_s2_title1','about_s2_text1','about_s2_title2','about_s2_text2','about_s2_text3','about_s2_image','about_mission_text','about_vision_text','about_values','about_cta_heading','about_cta_text','about_cta_btn_text','about_cta_btn_url','about_certifications_json','nav_logo_alt','footer_about_text','footer_quick_links','chat_widget_enabled','chat_line_enabled','chat_line_id','chat_wechat_enabled','chat_wechat_id','chat_wechat_qr_url','chat_whatsapp_enabled','chat_whatsapp_number','social_facebook_url','social_instagram_url','social_linkedin_url','rag_chat_enabled','gallery_interval'];
        // Save nav menu JSON if present
        if ( isset( $_POST['nav_menu_items_json'] ) ) {
            update_option( 'nav_menu_items_json', my_theme_sanitize_nav_menu_items_json( stripslashes( $_POST['nav_menu_items_json'] ) ) );
        }
        foreach ($fields as $key) {
            if (isset($_POST[$key])) {
                if (in_array($key, ['site_email','site_email_sales','kv_brevo_test_to'], true)) {
                    $val = sanitize_email($_POST[$key]);
                } elseif ($key === 'kv_brevo_from') {
                    // Keep the exact latest user input in settings; validate only when sending.
                    $val = sanitize_text_field($_POST[$key]);
                } elseif ($key === 'kv_brevo_api_key') {
                    $val = sanitize_text_field($_POST[$key]);
                } elseif ($key === 'site_address_full') {
                    $val = sanitize_textarea_field($_POST[$key]);
                } elseif (in_array($key, ['site_logo_url','site_logo_light_url','banner_bg_image','banner_bg_video','about_s1_image','about_s2_image','about_cta_btn_url','site_map_embed','chat_wechat_qr_url','social_facebook_url','social_instagram_url','social_linkedin_url'])) {
                    $val = esc_url_raw($_POST[$key]);
                } elseif (in_array($key, ['about_s1_text1','about_s1_text2','about_s1_text3','about_s2_text1','about_s2_text2','about_s2_text3','about_mission_text','about_vision_text','about_values','about_cta_text','footer_about_text','footer_quick_links'])) {
                    $val = sanitize_textarea_field($_POST[$key]);
                } elseif ($key === 'about_certifications_json') {
                    $val = my_theme_sanitize_about_certifications_json($_POST[$key]);
                } elseif ($key === 'theme_primary_color') {
                    $val = sanitize_hex_color($_POST[$key]) ?: '#0056d6';
                } elseif ($key === 'theme_accent_color') {
                    $val = sanitize_hex_color($_POST[$key]) ?: '#4ecdc4';
                } elseif ($key === 'theme_bg_color') {
                    $val = sanitize_hex_color($_POST[$key]) ?: '#ffffff';
                } elseif ($key === 'banner_bg_color') {
                    $val = sanitize_hex_color($_POST[$key]) ?: '#0056d6';
                } elseif ($key === 'banner_overlay') {
                    $val = min(100, max(0, (int)$_POST[$key]));
                } elseif ($key === 'banner_fadein_delay') {
                    $val = min(30, max(0, (int)$_POST[$key]));
                } elseif ($key === 'banner_video_start' || $key === 'banner_video_end') {
                    $val = max(0, (float)$_POST[$key]);
                } elseif (in_array($key, ['site_years_experience','site_total_products','site_countries_served','site_happy_customers','site_founded_year'])) {
                    $val = absint($_POST[$key]);
                } elseif ($key === 'gallery_interval') {
                    $val = max(1000, min(30000, absint($_POST[$key])));
                } elseif (in_array($key, ['site_years_auto','site_products_auto'])) {
                    $val = in_array($_POST[$key], ['0','1']) ? $_POST[$key] : '0';
                } else {
                    $val = sanitize_text_field($_POST[$key]);
                }
                update_option($key, $val);
                set_theme_mod($key, $val);
            } else {
                // Handle unchecked checkboxes (not sent in POST)
                if (in_array($key, ['chat_widget_enabled','chat_line_enabled','chat_wechat_enabled','chat_whatsapp_enabled','rag_chat_enabled'])) {
                    update_option($key, '0');
                    set_theme_mod($key, '0');
                }
            }
        }
        // Clear common page caches so frontend reflects changes immediately
        my_theme_flush_page_caches();

        if (!empty($_POST['kv_brevo_send_test'])) {
            $test_to = isset($_POST['kv_brevo_test_to']) ? sanitize_email((string) $_POST['kv_brevo_test_to']) : '';
            if (!is_email($test_to)) {
                $kv_mail_test_notice = [
                    'type' => 'error',
                    'text' => '❌ ทดสอบส่งอีเมลไม่สำเร็จ: อีเมลปลายทางไม่ถูกต้อง',
                ];
            } else {
                $subject = '[KV Theme] Brevo Test Email';
                $message = "This is a test email sent from Theme Settings via Brevo API.\n\n";
                $message .= 'Site: ' . home_url('/') . "\n";
                $message .= 'Time (UTC): ' . gmdate('Y-m-d H:i:s') . "\n";
                $headers = ['Content-Type: text/plain; charset=UTF-8'];

                $sent = wp_mail($test_to, $subject, $message, $headers);
                if ($sent) {
                    $message_id = kv_theme_get_last_mail_message_id();
                    $detail = $message_id !== '' ? ('<br><small>Brevo messageId: ' . esc_html($message_id) . '</small>') : '';
                    $kv_mail_test_notice = [
                        'type' => 'success',
                        'text' => '✅ ส่งอีเมลทดสอบสำเร็จไปที่: ' . esc_html($test_to) . $detail,
                    ];
                } else {
                    $last_mail_error = kv_theme_get_last_mail_error();
                    $detail = $last_mail_error !== '' ? ('<br><small>' . esc_html($last_mail_error) . '</small>') : '';
                    $kv_mail_test_notice = [
                        'type' => 'error',
                        'text' => '❌ ทดสอบส่งอีเมลไม่สำเร็จ: กรุณาตรวจ API Key, Sender, และการยืนยันโดเมนใน Brevo' . $detail,
                    ];
                }
            }
        }

        echo '<div class="notice notice-success is-dismissible"><p>✅ บันทึกข้อมูลเรียบร้อย!</p></div>';
        if (is_array($kv_mail_test_notice) && !empty($kv_mail_test_notice['text'])) {
            $notice_class = ($kv_mail_test_notice['type'] === 'success') ? 'notice-success' : 'notice-error';
            echo '<div class="notice ' . esc_attr($notice_class) . ' is-dismissible"><p>' . wp_kses_post($kv_mail_test_notice['text']) . '</p></div>';
        }
    }

    $phone        = get_option('site_phone',         get_theme_mod('site_phone',    ''));
    $fax          = get_option('site_fax',           get_theme_mod('site_fax',      ''));
    $email        = get_option('site_email',         get_theme_mod('site_email',    'info@company.com'));
    $email_sales  = get_option('site_email_sales',   'sales@company.com');
    $address      = get_option('site_address',       get_theme_mod('site_address',  '123 Industrial Zone, Bangkok, Thailand'));
    $address_full = get_option('site_address_full',  "123 Industrial Zone\nBangna-Trad Road\nBangkok 10260, Thailand");
    $hours_wd     = get_option('site_hours_weekday', 'Monday – Friday: 8:00 AM – 5:00 PM');
    $hours_we     = get_option('site_hours_weekend', 'Saturday – Sunday: Closed');
    $map_embed    = get_option('site_map_embed', '');
    $company = get_option('site_company_name', get_theme_mod('site_company_name', 'Electronic Components Co., Ltd.'));
    $copy    = get_option('site_copyright',    get_theme_mod('site_copyright',    'All rights reserved.'));
    $site_company_name = $company;
    $site_copyright    = $copy;
    // Stats
    $years_auto    = get_option('site_years_auto',    '0');
    $products_auto = get_option('site_products_auto', '0');
    $founded_year  = (int) get_option('site_founded_year', 1988);
    $years_exp     = (int) get_option('site_years_experience', 20);
    $total_prod    = (int) get_option('site_total_products', 500);
    $countries     = get_option('site_countries_served', 50);
    $happy_cust    = get_option('site_happy_customers', 1000);
    $logo       = kv_rebase_url( get_option('site_logo_url', home_url('/wp-content/uploads/2026/02/New-Logo-Schott-1-1-300x120-1.jpg')) );
    $theme_primary = get_option('theme_primary_color', '#0056d6');
    $theme_accent  = get_option('theme_accent_color',  '#4ecdc4');
    $theme_bg      = get_option('theme_bg_color',      '#ffffff');
    $ban_color  = get_option('banner_bg_color', '#0056d6');
    $ban_image  = get_option('banner_bg_image', '');
    $ban_video  = get_option('banner_bg_video', '');
    $ban_overlay= get_option('banner_overlay', 60);
    $ban_fadein = (int) get_option('banner_fadein_delay', 5);
    $ban_video_start = (float) get_option('banner_video_start', 0);
    $ban_video_end   = (float) get_option('banner_video_end', 0);
    // About Us
    $ab_s1_heading   = get_option('about_s1_heading',   'Leading Electronic Components Manufacturer');
    $ab_s1_title1    = get_option('about_s1_title1',    '');
    $ab_s1_text1     = get_option('about_s1_text1',     'With over 20 years of experience in the electronic components industry, we have established ourselves as a trusted manufacturer and supplier of high-quality inductors, transformers, and antennas.');
    $ab_s1_title2    = get_option('about_s1_title2',    '');
    $ab_s1_text2     = get_option('about_s1_text2',     'Our commitment to quality, innovation, and customer satisfaction has made us a preferred partner for leading companies in automotive, telecommunications, industrial automation, and consumer electronics sectors.');
    $ab_s1_text3     = get_option('about_s1_text3',     'We operate state-of-the-art manufacturing facilities equipped with advanced automated production lines and rigorous quality control systems to ensure every product meets the highest international standards.');
    $ab_s1_image     = get_option('about_s1_image',     'https://schottmagnetics.com/wp-content/uploads/2021/07/Flyback-Transformer4-1024x768-1.jpg');
    $ab_mission      = get_option('about_mission_text', 'To provide innovative, reliable, and cost-effective electronic component solutions that enable our customers to succeed in their markets.');
    $ab_vision       = get_option('about_vision_text',  'To be the global leader in electronic components manufacturing, recognized for quality, innovation, and exceptional customer service.');
    $ab_values       = get_option('about_values',       "Quality Excellence\nCustomer Focus\nContinuous Innovation\nIntegrity & Trust\nEnvironmental Responsibility");
    $ab_s2_title1    = get_option('about_s2_title1',    '');
    $ab_s2_text1     = get_option('about_s2_text1',     '');
    $ab_s2_title2    = get_option('about_s2_title2',    '');
    $ab_s2_text2     = get_option('about_s2_text2',     '');
    $ab_s2_text3     = get_option('about_s2_text3',     '');
    $ab_s2_image     = get_option('about_s2_image',     '');
    $ab_cta_heading  = get_option('about_cta_heading',  'Partner With Us');
    $ab_cta_text     = get_option('about_cta_text',     "Let's discuss how we can support your electronic component needs");
    $ab_cta_btn_text = get_option('about_cta_btn_text', 'Contact Us');
    $ab_cta_btn_url  = get_option('about_cta_btn_url',  '/contact');
    // Navbar
    $nav_logo_alt       = get_option('nav_logo_alt', 'Company Logo');
    $nav_logo_height    = get_option('nav_logo_height', 50);
    $nav_home_label     = get_option('nav_home_label', 'Home');
    $nav_home_vis       = get_option('nav_home_visible', '1');
    $nav_about_label    = get_option('nav_about_label', 'About Us');
    $nav_about_url_val  = get_option('nav_about_url', '/about/');
    $nav_about_vis      = get_option('nav_about_visible', '1');
    $nav_products_label = get_option('nav_products_label', 'Products');
    $nav_products_vis   = get_option('nav_products_visible', '1');
    $nav_contact_label  = get_option('nav_contact_label', 'Contacts');
    $nav_contact_url_val= get_option('nav_contact_url', '/contact/');
    $nav_contact_vis    = get_option('nav_contact_visible', '1');
    $nav_cta_text_val   = get_option('nav_cta_text', '');
    $nav_cta_url_val    = get_option('nav_cta_url', '/contact/');
    $nav_cta_vis        = get_option('nav_cta_visible', '1');
    $nav_custom_items   = get_option('nav_custom_items', '');
    // Navbar style
    $nav_bg_color       = get_option('nav_bg_color', '#ffffff');
    $nav_text_color     = get_option('nav_text_color', '');
    $nav_hover_color    = get_option('nav_hover_color', '');
    $nav_active_color   = get_option('nav_active_color', '');
    $nav_font_size      = get_option('nav_font_size', 16);
    $nav_font_weight    = get_option('nav_font_weight', '500');
    $nav_align          = get_option('nav_align', 'center');
    $nav_sticky         = get_option('nav_sticky', '1');
    $nav_shadow         = get_option('nav_shadow', '1');
    $nav_cta_bg         = get_option('nav_cta_bg', '');
    $nav_cta_text_clr   = get_option('nav_cta_text_color', '#ffffff');
    $nav_cta_radius     = get_option('nav_cta_radius', 6);
    $nav_cta_font_size  = get_option('nav_cta_font_size', 14);
    $nav_padding_y      = get_option('nav_padding_y', 8);
    // Footer
    $footer_about_text  = get_option('footer_about_text',  'Leading manufacturer of electronic components with over 20 years of experience in the industry.');
    $footer_quick_links = get_option('footer_quick_links', "About Us|/about\nContact|/contact");
    // Chat Widget
    $chat_enabled       = get_option('chat_widget_enabled',  '1');
    // RAG AI Chat
    $rag_chat_enabled   = get_option('rag_chat_enabled', '1');
    $chat_line_on       = get_option('chat_line_enabled',    '1');
    $chat_line_id       = get_option('chat_line_id',         'kriangkrai2042');
    $chat_wechat_on     = get_option('chat_wechat_enabled',  '1');
    $chat_wechat_id     = get_option('chat_wechat_id',       'KVElectronics');
    $chat_wechat_qr     = get_option('chat_wechat_qr_url',   '');
    $chat_whatsapp_on   = get_option('chat_whatsapp_enabled', '1');
    $chat_whatsapp_num  = get_option('chat_whatsapp_number', '6621088521');

    // ── Render redesigned UI ─────────────────────────────────────
    include get_template_directory() . '/admin/theme-settings-ui.php';
}

// Redirect legacy block-editor-guide URL → combined settings page (Block Editor tab)
add_action('admin_init', function() {
    if (is_admin() && isset($_GET['page']) && $_GET['page'] === 'block-editor-guide') {
        wp_safe_redirect(admin_url('admin.php?page=my-theme-settings&tab=blockeditor'));
        exit;
    }
});

// Redirect page editing from post.php → Site Editor (shows full page with header/footer)
add_action('current_screen', function($screen) {
    if (
        $screen->base === 'post' &&
        $screen->post_type === 'page' &&
        isset($_GET['action']) && $_GET['action'] === 'edit' &&
        isset($_GET['post']) &&
        !isset($_GET['classic-editor'])
    ) {
        $post_id = (int) $_GET['post'];
        // Don't redirect product-manager page (uses custom PHP template)
        $pm_page = get_page_by_path('product-manager');
        if ($pm_page && $pm_page->ID === $post_id) return;

        wp_safe_redirect(admin_url('site-editor.php?postType=page&postId=' . $post_id));
        exit;
    }
});

// Keep "Classic Editor" fallback link in page list row actions
add_filter('page_row_actions', function($actions, $post) {
    if ($post->post_type === 'page') {
        $classic_url = add_query_arg(['classic-editor' => '1'], get_edit_post_link($post->ID, 'url'));
        $actions['classic_edit'] = '<a href="' . esc_url($classic_url) . '">Classic Edit</a>';
    }
    return $actions;
}, 10, 2);

// Redirect legacy /wp-admin/product-manager URL → proper admin page
add_action('init', function () {
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    if (
        strpos($request_uri, '/wp-admin/product-manager') !== false
        && strpos($request_uri, 'admin.php') === false
        && empty($_GET['page'])
    ) {
        wp_safe_redirect(admin_url('admin.php?page=product-manager'));
        exit;
    }
}, 1);

// Redirect legacy product list/editor URLs → Product Manager page
add_action('admin_init', function () {
    if (!is_admin() || !current_user_can('edit_posts')) return;

    $pagenow   = $GLOBALS['pagenow'] ?? '';
    $post_type = $_GET['post_type'] ?? '';

    if (($pagenow === 'edit.php' || $pagenow === 'post-new.php') && $post_type === 'product') {
        wp_safe_redirect(admin_url('admin.php?page=product-manager'));
        exit;
    }
}, 1);

// Handle invalid post edit URLs (e.g. deleted post ID) without wp_die fatal screen
add_action('admin_init', function () {
    if (!is_admin() || !current_user_can('edit_posts')) return;

    $pagenow = $GLOBALS['pagenow'] ?? '';
    if ($pagenow !== 'post.php') return;

    $action  = $_GET['action'] ?? '';
    $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;
    if ($action !== 'edit' || $post_id <= 0) return;

    if (get_post($post_id)) return;

    $front_page_id = (int) get_option('page_on_front', 0);
    if ($front_page_id > 0 && get_post($front_page_id)) {
        wp_safe_redirect(admin_url('post.php?post=' . $front_page_id . '&action=edit&missing_post=1'));
        exit;
    }

    wp_safe_redirect(admin_url('edit.php?post_type=page&missing_post=1'));
    exit;
}, 1);

add_action('admin_notices', function () {
    if (empty($_GET['missing_post'])) return;
    if (!current_user_can('edit_posts')) return;
    echo '<div class="notice notice-warning is-dismissible"><p>โพสต์/เพจที่เปิดแก้ไขไม่มีอยู่แล้ว ระบบพาไปหน้าที่แก้ไขได้แทนให้อัตโนมัติ</p></div>';
});

// Make get_theme_mod() also read from wp_options (so both systems are in sync)
add_filter('theme_mod_site_phone',   function($val) { $opt = get_option('site_phone');   return $opt ?: $val; });
add_filter('theme_mod_site_fax',     function($val) { $opt = get_option('site_fax');     return $opt ?: $val; });
add_filter('theme_mod_site_email',   function($val) { $opt = get_option('site_email');   return $opt ?: $val; });
add_filter('theme_mod_site_address', function($val) { $opt = get_option('site_address'); return $opt ?: $val; });

if (!function_exists('kv_get_product_view_url')) {
    function kv_get_product_view_url($product_id = 0, $product_name = '') {
        $product_id = absint($product_id);
        if ($product_id > 0) {
            $post = get_post($product_id);
            if ($post && $post->post_type === 'product' && $post->post_status !== 'trash') {
                $url = get_permalink($product_id);
                if ($url) return $url;
            }
        }

        $raw_name = trim((string) $product_name);
        if ($raw_name !== '') {
            $normalized = html_entity_decode($raw_name, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $normalized = trim(wp_strip_all_tags($normalized));
            $normalized = preg_replace('/[\x{2010}-\x{2015}\x{2212}]+/u', '-', $normalized);
            $normalized = preg_replace('/\s+/u', ' ', $normalized);

            $candidates = array_values(array_unique(array_filter([$normalized, $raw_name])));

            foreach ($candidates as $candidate) {
                $slug = sanitize_title($candidate);
                $by_path = get_page_by_path($slug, OBJECT, 'product');
                if ($by_path instanceof WP_Post && $by_path->post_status !== 'trash') {
                    $url = get_permalink($by_path->ID);
                    if ($url) return $url;
                }
            }

            global $wpdb;
            foreach ($candidates as $candidate) {
                $exact_id = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_title = %s AND post_status IN ('publish','private','draft','pending') ORDER BY ID DESC LIMIT 1",
                    $candidate
                ));
                if ($exact_id > 0) {
                    $url = get_permalink($exact_id);
                    if ($url) return $url;
                }
            }

            $q = new WP_Query([
                'post_type'      => 'product',
                'post_status'    => ['publish', 'private', 'draft', 'pending'],
                'posts_per_page' => 1,
                's'              => $normalized,
                'orderby'        => 'relevance',
                'order'          => 'DESC',
            ]);
            if ($q->have_posts()) {
                $pid = (int) $q->posts[0]->ID;
                wp_reset_postdata();
                $url = get_permalink($pid);
                if ($url) return $url;
            }
            wp_reset_postdata();

            $term_slug = sanitize_title($normalized);
            $term = get_term_by('slug', $term_slug, 'product_category');
            if ($term && !is_wp_error($term)) {
                $term_url = get_term_link($term);
                if (!is_wp_error($term_url)) return $term_url;
            }

            $terms = get_terms([
                'taxonomy'   => 'product_category',
                'hide_empty' => false,
                'search'     => $normalized,
                'number'     => 1,
            ]);
            if (!is_wp_error($terms) && !empty($terms)) {
                $term_url = get_term_link($terms[0]);
                if (!is_wp_error($term_url)) return $term_url;
            }

            return home_url('/products/?s=' . rawurlencode($normalized));
        }

        return home_url('/products/');
    }
}

add_shortcode('site_logo',         function() { return esc_url(kv_rebase_url(get_option('site_logo_url', home_url('/wp-content/uploads/2026/02/New-Logo-Schott-1-1-300x120-1.jpg')))); });
add_shortcode('site_logo_light',   function() { return esc_url(kv_rebase_url(get_option('site_logo_light_url', get_option('site_logo_url', home_url('/wp-content/uploads/2026/02/New-Logo-Schott-1-1-300x120-1.jpg'))))); });
add_shortcode('site_logo_img',     function($atts) {
    $a = shortcode_atts(['height' => '50', 'class' => '', 'style' => ''], $atts);
    $url = esc_url(kv_rebase_url(get_option('site_logo_url', home_url('/wp-content/uploads/2026/02/New-Logo-Schott-1-1-300x120-1.jpg'))));
    $alt = esc_attr(get_option('nav_logo_alt', get_bloginfo('name')));
    $h   = absint($a['height']);
    return '<img src="' . $url . '" alt="' . $alt . '" height="' . $h . '" style="max-height:' . $h . 'px;width:auto;display:block;' . esc_attr($a['style']) . '" class="' . esc_attr($a['class']) . '">';
});
add_shortcode('site_phone',        function() { return esc_html(kv_format_phone_th(get_option('site_phone',        get_theme_mod('site_phone',    '')))); });
add_shortcode('site_fax',          function() { return esc_html(kv_format_phone_th(get_option('site_fax',          get_theme_mod('site_fax',      '')))); });
add_shortcode('site_email',        function() { return esc_html(get_option('site_email',        get_theme_mod('site_email',    'info@company.com'))); });
add_shortcode('site_email_sales',  function() { return esc_html(get_option('site_email_sales',  'sales@company.com')); });
add_shortcode('site_address_full', function() { return nl2br(esc_html(get_option('site_address_full', "123 Industrial Zone\nBangna-Trad Road\nBangkok 10260, Thailand"))); });
add_shortcode('site_hours_weekday',function() { return esc_html(get_option('site_hours_weekday', 'Monday – Friday: 8:00 AM – 5:00 PM')); });
add_shortcode('site_hours_weekend',function() { return esc_html(get_option('site_hours_weekend', 'Saturday – Sunday: Closed')); });
add_shortcode('site_address',      function() { return esc_html(get_option('site_address',      get_theme_mod('site_address',      '123 Industrial Zone, Bangkok, Thailand'))); });
add_shortcode('site_company_name', function() { return esc_html(get_option('site_company_name', get_theme_mod('site_company_name', 'Electronic Components Co., Ltd.'))); });
add_shortcode('site_copyright',    function() { return esc_html(get_option('site_copyright',    get_theme_mod('site_copyright',    'All rights reserved.'))); });
add_shortcode('nav_logo_alt',      function() { return esc_attr(get_option('nav_logo_alt', get_bloginfo('name'))); });
add_shortcode('footer_about_text', function() { return wp_kses_post(get_option('footer_about_text', 'Leading manufacturer of electronic components with over 20 years of experience in the industry.')); });
add_shortcode('footer_quick_links', function() {
    $raw   = get_option('footer_quick_links', "About Us|/about\nContact|/contact");
    $lines = array_filter(array_map('trim', explode("\n", $raw)));
    $out   = '';
    foreach ($lines as $line) {
        $parts = explode('|', $line, 2);
        if (count($parts) === 2) {
            $label = esc_html(trim($parts[0]));
            $url   = esc_url(trim($parts[1]));
            $out  .= '<li><a href="' . $url . '">' . $label . '</a></li>';
        }
    }
    return $out;
});

// ── WhatsApp number normalizer ──────────────────────────────
// Accepts: 0980102587 / +66980102587 / 66980102587
// Returns: international digits only for wa.me (e.g. 66980102587)
function my_theme_wa_number(string $raw): string {
    $digits = preg_replace('/[^0-9]/', '', $raw); // strip all non-digits
    if ($digits === '') return '';
    if (str_starts_with($digits, '00')) {
        $digits = substr($digits, 2);               // 0066... → 66...
    } elseif (str_starts_with($digits, '0')) {
        $digits = '66' . substr($digits, 1);        // 09... → 669...
    }
    return $digits;
}

// ── Chat Buttons Shortcode ──────────────────────────────
// Usage: [chat_buttons] or [chat_buttons style="inline"]
// Renders LINE / WeChat / WhatsApp buttons from theme settings.
// Supports style="inline" (horizontal row for in-page use) or style="floating" (fixed-position widget).
add_shortcode('chat_buttons', function($atts) {
    $atts = shortcode_atts(['style' => 'inline'], $atts, 'chat_buttons');
    $is_floating = ($atts['style'] === 'floating');

    $line_on  = get_option('chat_line_enabled',    '1');
    $line_id  = get_option('chat_line_id',         'kriangkrai2042');
    $wc_on    = get_option('chat_wechat_enabled',  '1');
    $wc_id    = get_option('chat_wechat_id',       'KVElectronics');
    $wc_qr    = get_option('chat_wechat_qr_url',   '');
    $wa_on    = get_option('chat_whatsapp_enabled', '1');
    $wa_num   = get_option('chat_whatsapp_number', '6621088521');

    $any = ($line_on === '1' || $wc_on === '1' || $wa_on === '1');
    if (!$any) return '';

    $qr_src = $wc_qr ?: get_template_directory_uri() . '/assets/images/wechat-qr.svg';
    $uid    = 'cb-' . wp_unique_id();

    ob_start();
    if ($is_floating) : ?>
    <div id="<?php echo $uid; ?>" style="position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;align-items:flex-end;gap:12px;">
    <?php else : ?>
    <div id="<?php echo $uid; ?>" class="chat-buttons-inline" style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;">
    <?php endif;

    // WeChat QR Popup (only for floating style)
    if ($wc_on === '1' && $is_floating) : ?>
    <div id="<?php echo $uid; ?>-wc-popup" style="display:none;background:#fff;border-radius:12px;padding:20px;box-shadow:0 8px 32px rgba(0,0,0,0.18);text-align:center;min-width:220px;position:relative;"><span onclick="this.parentElement.style.display='none'" style="position:absolute;top:8px;right:12px;background:none;border:none;font-size:20px;cursor:pointer;color:#64748b;line-height:1;">&times;</span><div style="margin:0 0 12px;font-weight:600;color:#1e293b;font-size:14px;">Scan to chat on WeChat</div><div><img src="<?php echo esc_url($qr_src); ?>" alt="WeChat QR" width="160" height="160" style="border:1px solid #e2e8f0;border-radius:8px;" loading="lazy"></div><div style="margin:10px 0 0;font-size:12px;color:#94a3b8;">WeChat ID: <?php echo esc_html($wc_id); ?></div></div>
    <?php endif; ?>

    <?php if (!$is_floating) echo '<div style="display:flex;gap:10px;align-items:center;">'; ?>
    <?php if ($is_floating) echo '<div style="display:flex;flex-direction:column;gap:10px;align-items:center;">'; ?>

    <?php if ($line_on === '1' && $line_id) : ?>
    <a href="https://line.me/ti/p/~<?php echo esc_attr($line_id); ?>" target="_blank" rel="noopener noreferrer" style="width:52px;height:52px;border-radius:50%;background:#06C755;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(6,199,85,0.35);transition:transform .2s;text-decoration:none;" aria-label="Chat on LINE" onmouseenter="this.style.transform='scale(1.1)'" onmouseleave="this.style.transform='scale(1)'">
        <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="#fff"><path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386a.63.63 0 0 1-.63-.629V8.108a.63.63 0 0 1 .63-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016a.63.63 0 0 1-.63.629.626.626 0 0 1-.51-.262l-2.397-3.265v2.898a.63.63 0 0 1-.63.629.627.627 0 0 1-.629-.629V8.108a.63.63 0 0 1 .63-.63c.2 0 .38.095.51.262l2.397 3.265V8.108a.63.63 0 0 1 .63-.63.63.63 0 0 1 .629.63v4.771zm-5.741 0a.63.63 0 0 1-1.26 0V8.108a.63.63 0 0 1 1.26 0v4.771zm-2.527.629H4.856a.63.63 0 0 1-.63-.629V8.108a.63.63 0 0 1 1.26 0v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629zM24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/></svg>
    </a>
    <?php endif; ?>

    <?php if ($wc_on === '1') :
        if ($is_floating) : ?>
    <button onclick="var p=document.getElementById('<?php echo $uid; ?>-wc-popup');p.style.display=p.style.display==='none'?'block':'none';" style="width:52px;height:52px;border-radius:50%;background:#07C160;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(7,193,96,0.35);transition:transform .2s;" aria-label="Chat on WeChat" onmouseenter="this.style.transform='scale(1.1)'" onmouseleave="this.style.transform='scale(1)'">
        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="#fff"><path d="M8.691 2.188C3.891 2.188 0 5.476 0 9.53c0 2.212 1.17 4.203 3.002 5.55a.59.59 0 0 1 .213.665l-.39 1.48c-.019.07-.048.141-.048.213a.3.3 0 0 0 .3.3c.07 0 .14-.027.198-.063l1.83-1.067a.57.57 0 0 1 .449-.063 9.613 9.613 0 0 0 3.137.524c.302 0 .6-.013.893-.039a6.192 6.192 0 0 1-.253-1.72c0-3.682 3.477-6.674 7.759-6.674.254 0 .505.012.752.033C16.726 4.492 13.068 2.188 8.691 2.188zm-2.6 4.26a1.058 1.058 0 1 1 0 2.115 1.058 1.058 0 0 1 0-2.116zm5.203 0a1.058 1.058 0 1 1 0 2.115 1.058 1.058 0 0 1 0-2.116zM16.09 8.735c-3.752 0-6.803 2.614-6.803 5.836 0 3.222 3.051 5.836 6.803 5.836a8.17 8.17 0 0 0 2.593-.42.542.542 0 0 1 .42.059l1.517.885c.052.033.112.055.172.055a.25.25 0 0 0 .25-.25c0-.062-.024-.12-.04-.178l-.323-1.228a.553.553 0 0 1 .2-.622C22.725 17.543 24 15.762 24 13.57c0-3.222-3.547-5.836-7.91-5.836zm-2.418 3.776a.883.883 0 1 1 0 1.765.883.883 0 0 1 0-1.765zm4.84 0a.883.883 0 1 1 0 1.765.883.883 0 0 1 0-1.765z"/></svg>
    </button>
        <?php else : ?>
    <a href="weixin://dl/chat?<?php echo esc_attr($wc_id); ?>" style="width:52px;height:52px;border-radius:50%;background:#07C160;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(7,193,96,0.35);transition:transform .2s;text-decoration:none;" aria-label="Chat on WeChat" title="WeChat ID: <?php echo esc_attr($wc_id); ?>" onmouseenter="this.style.transform='scale(1.1)'" onmouseleave="this.style.transform='scale(1)'">
        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="#fff"><path d="M8.691 2.188C3.891 2.188 0 5.476 0 9.53c0 2.212 1.17 4.203 3.002 5.55a.59.59 0 0 1 .213.665l-.39 1.48c-.019.07-.048.141-.048.213a.3.3 0 0 0 .3.3c.07 0 .14-.027.198-.063l1.83-1.067a.57.57 0 0 1 .449-.063 9.613 9.613 0 0 0 3.137.524c.302 0 .6-.013.893-.039a6.192 6.192 0 0 1-.253-1.72c0-3.682 3.477-6.674 7.759-6.674.254 0 .505.012.752.033C16.726 4.492 13.068 2.188 8.691 2.188zm-2.6 4.26a1.058 1.058 0 1 1 0 2.115 1.058 1.058 0 0 1 0-2.116zm5.203 0a1.058 1.058 0 1 1 0 2.115 1.058 1.058 0 0 1 0-2.116zM16.09 8.735c-3.752 0-6.803 2.614-6.803 5.836 0 3.222 3.051 5.836 6.803 5.836a8.17 8.17 0 0 0 2.593-.42.542.542 0 0 1 .42.059l1.517.885c.052.033.112.055.172.055a.25.25 0 0 0 .25-.25c0-.062-.024-.12-.04-.178l-.323-1.228a.553.553 0 0 1 .2-.622C22.725 17.543 24 15.762 24 13.57c0-3.222-3.547-5.836-7.91-5.836zm-2.418 3.776a.883.883 0 1 1 0 1.765.883.883 0 0 1 0-1.765zm4.84 0a.883.883 0 1 1 0 1.765.883.883 0 0 1 0-1.765z"/></svg>
    </a>
        <?php endif;
    endif; ?>

    <?php if ($wa_on === '1' && $wa_num) : ?>
    <a href="https://wa.me/<?php echo esc_attr(my_theme_wa_number($wa_num)); ?>" target="_blank" rel="noopener noreferrer" style="width:52px;height:52px;border-radius:50%;background:#25D366;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(37,211,102,0.35);transition:transform .2s;text-decoration:none;" aria-label="Chat on WhatsApp" onmouseenter="this.style.transform='scale(1.1)'" onmouseleave="this.style.transform='scale(1)'">
        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="#fff"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
    </a>
    <?php endif; ?>

    </div>
    </div>
    <?php
    $html = ob_get_clean();
    // Strip whitespace between tags to prevent wpautop from injecting <br> / <p>
    $html = preg_replace('/>\s+</', '><', $html);
    // Remove any stray <p>/<br> tags injected by wpautop or content filters
    $html = preg_replace('#</?p[^>]*>#i', '', $html);
    $html = preg_replace('#<br\s*/?\s*>#i', '', $html);
    return $html;
});

// Prevent wpautop from mangling chat_buttons shortcode output
add_filter('the_content', function($content) {
    if (has_shortcode($content, 'chat_buttons')) {
        remove_filter('the_content', 'wpautop');
        $content = do_shortcode($content);
        add_filter('the_content', 'wpautop');
    }
    return $content;
}, 9);

// Clean up any stray <p>/<br>/</p> injected by WordPress content filters into chat widget HTML
add_filter('render_block', function($block_content, $block) {
    if ($block['blockName'] === 'core/shortcode' && strpos($block_content, 'position:fixed') !== false) {
        $block_content = preg_replace('#</p>#i', '', $block_content);
        $block_content = preg_replace('#<p>#i', '', $block_content);
        $block_content = preg_replace('#<br\s*/?\s*>#i', '', $block_content);
    }
    return $block_content;
}, 10, 2);

// ============================================================
// CONTACT PAGE SHORTCODES — Block-Editor Editable Sections
// ============================================================

/**
 * [kv_quality_badges]
 * Quality badges (ISO 9001, ISO 14001, BOI)
 */
add_shortcode('kv_quality_badges', function () {
    ob_start(); ?>
    <div style="margin-bottom:32px;padding:20px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;">
        <h5 style="margin:0 0 14px;font-size:14px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Quality Standards</h5>
        <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;">
            <div class="iso-badge" style="filter:grayscale(1);opacity:0.7;transition:filter .3s,opacity .3s;cursor:default;" onmouseenter="this.style.filter='grayscale(0)';this.style.opacity='1'" onmouseleave="this.style.filter='grayscale(1)';this.style.opacity='0.7'" title="ISO 9001:2015 Quality Management System">
                <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 200 200" role="img" aria-label="ISO 9001:2015 Certified"><circle cx="100" cy="100" r="95" fill="#fff" stroke="#1a5276" stroke-width="4"/><circle cx="100" cy="100" r="82" fill="none" stroke="#2980b9" stroke-width="2"/><text x="100" y="65" text-anchor="middle" font-family="Arial,sans-serif" font-size="18" font-weight="bold" fill="#1a5276">ISO</text><text x="100" y="95" text-anchor="middle" font-family="Arial,sans-serif" font-size="26" font-weight="bold" fill="#2980b9">9001</text><text x="100" y="118" text-anchor="middle" font-family="Arial,sans-serif" font-size="12" fill="#1a5276">:2015</text><text x="100" y="145" text-anchor="middle" font-family="Arial,sans-serif" font-size="10" fill="#7f8c8d">CERTIFIED</text><path d="M60 155 L100 170 L140 155" fill="none" stroke="#2980b9" stroke-width="2"/></svg>
            </div>
            <div class="iso-badge" style="filter:grayscale(1);opacity:0.7;transition:filter .3s,opacity .3s;cursor:default;" onmouseenter="this.style.filter='grayscale(0)';this.style.opacity='1'" onmouseleave="this.style.filter='grayscale(1)';this.style.opacity='0.7'" title="ISO 14001:2015 Environmental Management System">
                <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 200 200" role="img" aria-label="ISO 14001:2015 Certified"><circle cx="100" cy="100" r="95" fill="#fff" stroke="#196f3d" stroke-width="4"/><circle cx="100" cy="100" r="82" fill="none" stroke="#27ae60" stroke-width="2"/><text x="100" y="65" text-anchor="middle" font-family="Arial,sans-serif" font-size="18" font-weight="bold" fill="#196f3d">ISO</text><text x="100" y="95" text-anchor="middle" font-family="Arial,sans-serif" font-size="24" font-weight="bold" fill="#27ae60">14001</text><text x="100" y="118" text-anchor="middle" font-family="Arial,sans-serif" font-size="12" fill="#196f3d">:2015</text><text x="100" y="145" text-anchor="middle" font-family="Arial,sans-serif" font-size="10" fill="#7f8c8d">CERTIFIED</text><path d="M60 155 L100 170 L140 155" fill="none" stroke="#27ae60" stroke-width="2"/></svg>
            </div>
            <div class="iso-badge" style="filter:grayscale(1);opacity:0.7;transition:filter .3s,opacity .3s;cursor:default;" onmouseenter="this.style.filter='grayscale(0)';this.style.opacity='1'" onmouseleave="this.style.filter='grayscale(1)';this.style.opacity='0.7'" title="BOI Promoted — Thailand Board of Investment">
                <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 200 200" role="img" aria-label="BOI Promoted"><circle cx="100" cy="100" r="95" fill="#fff" stroke="#7b3f00" stroke-width="4"/><circle cx="100" cy="100" r="82" fill="none" stroke="#d4a017" stroke-width="2"/><text x="100" y="72" text-anchor="middle" font-family="Arial,sans-serif" font-size="26" font-weight="bold" fill="#d4a017">BOI</text><text x="100" y="100" text-anchor="middle" font-family="Arial,sans-serif" font-size="11" fill="#7b3f00">PROMOTED</text><text x="100" y="122" text-anchor="middle" font-family="Arial,sans-serif" font-size="9" fill="#7b3f00">THAILAND</text><text x="100" y="140" text-anchor="middle" font-family="Arial,sans-serif" font-size="8" fill="#94a3b8">BOARD OF INVESTMENT</text><path d="M65 155 L100 168 L135 155" fill="none" stroke="#d4a017" stroke-width="2"/></svg>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
});

/**
 * [kv_contact_address]
 * Address block with 📍 icon — pulls from wp_options.
 */
add_shortcode('kv_contact_address', function () {
    $addr = get_option('site_address_full', "123 Industrial Zone\nBangna-Trad Road\nBangkok 10260, Thailand");
    ob_start(); ?>
    <div style="display:flex;gap:16px;align-items:flex-start;margin-bottom:28px;">
        <div style="width:50px;height:50px;min-width:50px;border-radius:50%;background:#e8f0fe;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">📍</div>
        <div>
            <h4 style="margin:0 0 6px;font-size:17px;color:#1e293b;">Address</h4>
            <p style="margin:0;color:#64748b;line-height:1.6;"><?php echo nl2br(esc_html($addr)); ?></p>
        </div>
    </div>
    <?php
    return ob_get_clean();
});

/**
 * [kv_contact_phone]
 * Phone & Fax block — pulls from wp_options.
 */
add_shortcode('kv_contact_phone', function () {
    $phone     = get_option('site_phone', '');
    $fax       = get_option('site_fax', '');
    $phone_fmt = kv_format_phone_th($phone);
    $fax_fmt   = kv_format_phone_th($fax);
    $phone_tel = preg_replace('/[^0-9+]/', '', $phone);
    ob_start(); ?>
    <div style="display:flex;gap:16px;align-items:flex-start;margin-bottom:28px;">
        <div style="width:50px;height:50px;min-width:50px;border-radius:50%;background:#e8f0fe;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">📞</div>
        <div>
            <h4 style="margin:0 0 6px;font-size:17px;color:#1e293b;">Phone</h4>
            <p style="margin:0;color:#64748b;line-height:1.8;">
                <a href="tel:<?php echo esc_attr($phone_tel); ?>" style="color:#64748b;text-decoration:none;"><?php echo esc_html($phone_fmt ?: $phone); ?></a>
                <?php if ($fax) : ?><br><span><?php echo esc_html($fax_fmt ?: $fax); ?> (Fax)</span><?php endif; ?>
            </p>
        </div>
    </div>
    <?php
    return ob_get_clean();
});

/**
 * [kv_contact_email]
 * Email block — pulls from wp_options.
 */
add_shortcode('kv_contact_email', function () {
    $email       = get_option('site_email', 'info@company.com');
    ob_start(); ?>
    <div style="display:flex;gap:16px;align-items:flex-start;margin-bottom:28px;">
        <div style="width:50px;height:50px;min-width:50px;border-radius:50%;background:#e8f0fe;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">✉️</div>
        <div>
            <h4 style="margin:0 0 6px;font-size:17px;color:#1e293b;">Email</h4>
            <p style="margin:0;color:#64748b;line-height:1.8;">
                <a href="mailto:<?php echo esc_attr($email); ?>" style="color:#64748b;text-decoration:none;"><?php echo esc_html($email); ?></a>
            </p>
        </div>
    </div>
    <?php
    return ob_get_clean();
});

/**
 * [kv_about_intro]
 * About Us Section 1 — heading + 3 paragraphs pulled from wp_options.
 */
add_shortcode('kv_about_intro', function () {
    $title1 = get_option('about_s1_title1', '');
    $text1  = get_option('about_s1_text1',  '');
    $title2 = get_option('about_s1_title2', '');
    $text2  = get_option('about_s1_text2',  '');
    $text3  = get_option('about_s1_text3',  '');
    $image  = get_option('about_s1_image',  '');
    ob_start(); ?>
    <div class="kv-about-intro-wrap" style="display:flex;gap:50px;align-items:center;flex-wrap:wrap;">
        <div style="flex:1;min-width:280px;">
            <?php if ($title1) : ?><h4 style="margin:0 0 6px;font-size:16px;font-weight:600;color:#1e293b;"><?php echo esc_html($title1); ?></h4><?php endif; ?>
            <?php if ($text1)  : ?><p class="kv-about-text" style="color:#64748b;margin-bottom:15px;line-height:1.7;"><?php echo esc_html($text1); ?></p><?php endif; ?>
            <?php if ($title2) : ?><h4 style="margin:15px 0 6px;font-size:16px;font-weight:600;color:#1e293b;"><?php echo esc_html($title2); ?></h4><?php endif; ?>
            <?php if ($text2)  : ?><p class="kv-about-text" style="color:#64748b;margin-bottom:15px;line-height:1.7;"><?php echo esc_html($text2); ?></p><?php endif; ?>
            <?php if ($text3)  : ?><p class="kv-about-text" style="color:#64748b;margin-bottom:0;line-height:1.7;"><?php echo esc_html($text3); ?></p><?php endif; ?>
        </div>
        <?php if ($image) : ?>
        <div style="flex:1;min-width:280px;">
            <img src="<?php echo esc_url($image); ?>" alt="" style="width:100%;border-radius:12px;display:block;">
        </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
});

/**
 * [kv_about_s2]
 * About Us Section 2 — two-column layout (text left, image right) from wp_options.
 * Returns empty string if all fields are empty.
 */
add_shortcode('kv_about_s2', function () {
    $title1 = get_option('about_s2_title1', '');
    $text1  = get_option('about_s2_text1',  '');
    $title2 = get_option('about_s2_title2', '');
    $text2  = get_option('about_s2_text2',  '');
    $text3  = get_option('about_s2_text3',  '');
    $image  = get_option('about_s2_image',  '');
    if (!$title1 && !$text1 && !$title2 && !$text2 && !$text3 && !$image) return '';
    ob_start(); ?>
    <div class="kv-about-s2-wrap" style="display:flex;gap:50px;align-items:center;flex-wrap:wrap;">
        <?php if ($image) : ?>
        <div style="flex:1;min-width:280px;">
            <img src="<?php echo esc_url($image); ?>" alt="" style="width:100%;border-radius:12px;display:block;">
        </div>
        <?php endif; ?>
        <div style="flex:1;min-width:280px;">
            <?php if ($title1) : ?><h4 style="margin:0 0 6px;font-size:16px;font-weight:600;color:#1e293b;"><?php echo esc_html($title1); ?></h4><?php endif; ?>
            <?php if ($text1)  : ?><p class="kv-about-text" style="color:#64748b;margin-bottom:15px;line-height:1.7;"><?php echo esc_html($text1); ?></p><?php endif; ?>
            <?php if ($title2) : ?><h4 style="margin:15px 0 6px;font-size:16px;font-weight:600;color:#1e293b;"><?php echo esc_html($title2); ?></h4><?php endif; ?>
            <?php if ($text2)  : ?><p class="kv-about-text" style="color:#64748b;margin-bottom:15px;line-height:1.7;"><?php echo esc_html($text2); ?></p><?php endif; ?>
            <?php if ($text3)  : ?><p class="kv-about-text" style="color:#64748b;margin-bottom:0;line-height:1.7;"><?php echo esc_html($text3); ?></p><?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
});

/**
 * [kv_contact_hours]
 * Business Hours block — pulls from wp_options.
 */
add_shortcode('kv_contact_hours', function () {
    $wd = get_option('site_hours_weekday', 'Monday – Friday: 8:00 AM – 5:00 PM');
    $we = get_option('site_hours_weekend', 'Saturday – Sunday: Closed');
    ob_start(); ?>
    <div style="display:flex;gap:16px;align-items:flex-start;margin-bottom:28px;">
        <div style="width:50px;height:50px;min-width:50px;border-radius:50%;background:#e8f0fe;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">🕐</div>
        <div>
            <h4 style="margin:0 0 6px;font-size:17px;color:#1e293b;">Business Hours</h4>
            <p style="margin:0;color:#64748b;line-height:1.8;">
                <?php echo esc_html($wd); ?><br>
                <?php echo esc_html($we); ?>
            </p>
        </div>
    </div>
    <?php
    return ob_get_clean();
});

/**
 * [kv_chat_buttons_contact]
 * Chat-with-us section with 💬 icon and LINE/WeChat/WhatsApp buttons.
 */
add_shortcode('kv_chat_buttons_contact', function () {
    $line_on  = get_option('chat_line_enabled', '1');
    $line_id  = get_option('chat_line_id', 'kriangkrai2042');
    $wc_on    = get_option('chat_wechat_enabled', '1');
    $wc_id    = get_option('chat_wechat_id', '');
    $wc_qr    = get_option('chat_wechat_qr_url', '');
    $wa_on    = get_option('chat_whatsapp_enabled', '1');
    $wa_num   = get_option('chat_whatsapp_number', '6621088521');
    $facebook_url  = get_option('social_facebook_url', 'https://www.facebook.com/KVElectronicsTH/');
    $instagram_url = get_option('social_instagram_url', 'https://www.instagram.com/kvelectronicsth/');
    $linkedin_url  = get_option('social_linkedin_url', 'https://www.linkedin.com/company/kv-electronics-co-ltd');

    $any = ($line_on && $line_id) || ($wc_on) || ($wa_on && $wa_num) || !empty($facebook_url) || !empty($instagram_url) || !empty($linkedin_url);
    if (!$any) return '';

    ob_start(); ?>
    <div style="display:flex;gap:16px;align-items:flex-start;margin-bottom:28px;">
        <div style="width:50px;height:50px;min-width:50px;border-radius:50%;background:#e8f0fe;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">💬</div>
        <div>
            <h4 style="margin:0 0 10px;font-size:17px;color:#1e293b;">Chat with Us</h4>
            <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                <?php if ($line_on && $line_id) : ?>
                <a href="https://line.me/ti/p/~<?php echo esc_attr($line_id); ?>" target="_blank" rel="noopener noreferrer" style="width:48px;height:48px;border-radius:50%;background:#06C755;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(6,199,85,0.35);transition:transform .2s;text-decoration:none;" aria-label="Chat on LINE" onmouseenter="this.style.transform='scale(1.12)'" onmouseleave="this.style.transform='scale(1)'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="#fff"><path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386a.63.63 0 0 1-.63-.629V8.108a.63.63 0 0 1 .63-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016a.63.63 0 0 1-.63.629.626.626 0 0 1-.51-.262l-2.397-3.265v2.898a.63.63 0 0 1-.63.629.627.627 0 0 1-.629-.629V8.108a.63.63 0 0 1 .63-.63c.2 0 .38.095.51.262l2.397 3.265V8.108a.63.63 0 0 1 .63-.63.63.63 0 0 1 .629.63v4.771zm-5.741 0a.63.63 0 0 1-1.26 0V8.108a.63.63 0 0 1 1.26 0v4.771zm-2.527.629H4.856a.63.63 0 0 1-.63-.629V8.108a.63.63 0 0 1 1.26 0v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629zM24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/></svg>
                </a>
                <?php endif; ?>
                <?php if ($wc_on) : ?>
                <button onclick="var p=document.getElementById('kv-ct-wechat-popup');p.style.display=p.style.display==='none'?'flex':'none';" style="width:48px;height:48px;border-radius:50%;background:#07C160;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(7,193,96,0.35);transition:transform .2s;" aria-label="Chat on WeChat" onmouseenter="this.style.transform='scale(1.12)'" onmouseleave="this.style.transform='scale(1)'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#fff"><path d="M8.691 2.188C3.891 2.188 0 5.476 0 9.53c0 2.212 1.17 4.203 3.002 5.55a.59.59 0 0 1 .213.665l-.39 1.48c-.019.07-.048.141-.048.213a.3.3 0 0 0 .3.3c.07 0 .14-.027.198-.063l1.83-1.067a.57.57 0 0 1 .449-.063 9.613 9.613 0 0 0 3.137.524c.302 0 .6-.013.893-.039a6.192 6.192 0 0 1-.253-1.72c0-3.682 3.477-6.674 7.759-6.674.254 0 .505.012.752.033C16.726 4.492 13.068 2.188 8.691 2.188zm-2.6 4.26a1.058 1.058 0 1 1 0 2.115 1.058 1.058 0 0 1 0-2.116zm5.203 0a1.058 1.058 0 1 1 0 2.115 1.058 1.058 0 0 1 0-2.116zM16.09 8.735c-3.752 0-6.803 2.614-6.803 5.836 0 3.222 3.051 5.836 6.803 5.836a8.17 8.17 0 0 0 2.593-.42.542.542 0 0 1 .42.059l1.517.885c.052.033.112.055.172.055a.25.25 0 0 0 .25-.25c0-.062-.024-.12-.04-.178l-.323-1.228a.553.553 0 0 1 .2-.622C22.725 17.543 24 15.762 24 13.57c0-3.222-3.547-5.836-7.91-5.836zm-2.418 3.776a.883.883 0 1 1 0 1.765.883.883 0 0 1 0-1.765zm4.84 0a.883.883 0 1 1 0 1.765.883.883 0 0 1 0-1.765z"/></svg>
                </button>
                <?php endif; ?>
                <?php if ($wa_on && $wa_num) : ?>
                <a href="https://wa.me/<?php echo esc_attr(preg_replace('/\D/', '', $wa_num)); ?>" target="_blank" rel="noopener noreferrer" style="width:48px;height:48px;border-radius:50%;background:#25D366;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(37,211,102,0.35);transition:transform .2s;text-decoration:none;" aria-label="Chat on WhatsApp" onmouseenter="this.style.transform='scale(1.12)'" onmouseleave="this.style.transform='scale(1)'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#fff"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
                </a>
                <?php endif; ?>
                <?php if (!empty($facebook_url)) : ?>
                <a href="<?php echo esc_url($facebook_url); ?>" target="_blank" rel="noopener noreferrer" style="width:48px;height:48px;border-radius:50%;background:#1877F2;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(24,119,242,0.35);transition:transform .2s;text-decoration:none;" aria-label="Open Facebook" onmouseenter="this.style.transform='scale(1.12)'" onmouseleave="this.style.transform='scale(1)'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="#fff"><path d="M13.5 22v-8h2.7l.4-3h-3.1V9.1c0-.9.3-1.6 1.6-1.6h1.7V4.7c-.3 0-1.3-.1-2.4-.1-2.4 0-4 1.4-4 4.2V11H8v3h2.4v8h3.1z"/></svg>
                </a>
                <?php endif; ?>
                <?php if (!empty($instagram_url)) : ?>
                <a href="<?php echo esc_url($instagram_url); ?>" target="_blank" rel="noopener noreferrer" style="width:48px;height:48px;border-radius:50%;background:radial-gradient(circle at 30% 107%, #fdf497 0%, #fdf497 5%, #fd5949 45%, #d6249f 60%, #285AEB 90%);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(214,36,159,0.35);transition:transform .2s;text-decoration:none;" aria-label="Open Instagram" onmouseenter="this.style.transform='scale(1.12)'" onmouseleave="this.style.transform='scale(1)'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="5" ry="5"></rect><circle cx="12" cy="12" r="4"></circle><circle cx="17.5" cy="6.5" r="1"></circle></svg>
                </a>
                <?php endif; ?>
                <?php if (!empty($linkedin_url)) : ?>
                <a href="<?php echo esc_url($linkedin_url); ?>" target="_blank" rel="noopener noreferrer" style="width:48px;height:48px;border-radius:50%;background:#0A66C2;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(10,102,194,0.35);transition:transform .2s;text-decoration:none;" aria-label="Open LinkedIn" onmouseenter="this.style.transform='scale(1.12)'" onmouseleave="this.style.transform='scale(1)'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="#fff"><path d="M6.94 8.5a1.56 1.56 0 1 1 0-3.12 1.56 1.56 0 0 1 0 3.12zM5.5 9.75h2.9V19h-2.9V9.75zM10.2 9.75h2.78v1.26h.04c.39-.73 1.34-1.5 2.75-1.5 2.94 0 3.48 1.93 3.48 4.44V19h-2.9v-4.47c0-1.07-.02-2.45-1.49-2.45-1.5 0-1.73 1.17-1.73 2.37V19H10.2V9.75z"/></svg>
                </a>
                <?php endif; ?>
            </div>
            <?php if ($wc_on) : ?>
            <div id="kv-ct-wechat-popup" style="display:none;margin-top:12px;padding:16px;background:#fff;border-radius:10px;border:1px solid #e2e8f0;box-shadow:0 4px 16px rgba(0,0,0,0.08);flex-direction:column;align-items:center;gap:8px;max-width:220px;">
                <?php if ($wc_qr) : ?><img src="<?php echo esc_url($wc_qr); ?>" alt="WeChat QR Code" style="width:160px;height:160px;border-radius:8px;"><?php endif; ?>
                <p style="margin:0;font-size:13px;color:#64748b;"><?php echo $wc_id ? 'WeChat ID: <strong>' . esc_html($wc_id) . '</strong>' : 'Scan to chat on WeChat'; ?></p>
                <button onclick="this.parentElement.style.display='none'" style="margin-top:4px;padding:4px 16px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer;font-size:12px;color:#64748b;">Close</button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
});

/**
 * [kv_contact_form]
 * Full contact form with validation — product categories dropdown auto-populated.
 */
add_shortcode('kv_contact_form', function () {
    $theme_primary = get_option('theme_primary_color', '#0056d6');
    $theme_accent  = get_option('theme_accent_color', '#4ecdc4');

    // Get product categories for dropdown
    $cats = get_terms(['taxonomy' => 'product_category', 'hide_empty' => false, 'parent' => 0, 'orderby' => 'name']);

    ob_start(); ?>
    <div style="background:#f8fafc;border-radius:12px;padding:40px;">
        <h3 style="margin-top:0;margin-bottom:24px;font-size:22px;color:#1e293b;">Send us a Message</h3>
        <form class="contact-form-fields" id="cf-form">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label style="display:block;margin-bottom:6px;font-weight:500;color:#374151;">Contact name <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="name" required minlength="2" maxlength="100" placeholder="Your name" style="width:100%;padding:12px 16px;border:1px solid #d1d5db;border-radius:8px;font-size:15px;font-family:inherit;">
                </div>
                <div class="col-md-6">
                    <label style="display:block;margin-bottom:6px;font-weight:500;color:#374151;">Company name <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="company" required maxlength="150" placeholder="Your company" style="width:100%;padding:12px 16px;border:1px solid #d1d5db;border-radius:8px;font-size:15px;font-family:inherit;">
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label style="display:block;margin-bottom:6px;font-weight:500;color:#374151;">Industry <span style="color:#dc2626;">*</span></label>
                    <select name="industry" required style="width:100%;padding:12px 16px;border:1px solid #d1d5db;border-radius:8px;font-size:15px;font-family:inherit;background:#fff;">
                        <option value="">Please Select</option>
                        <option>Aerospace</option><option>Agriculture</option><option>Computer</option><option>Construction</option><option>Education</option><option>Electronics</option><option>Energy</option><option>Entertainment</option><option>Food</option><option>Health care</option><option>Hospitality</option><option>Manufacturing</option><option>Mining</option><option>Music</option><option>News Media</option><option>Pharmaceutical</option><option>Telecommunication</option><option>Transport</option><option>Worldwide web</option><option>Other</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label style="display:block;margin-bottom:6px;font-weight:500;color:#374151;">Organization type <span style="color:#dc2626;">*</span></label>
                    <select name="organization_type" required style="width:100%;padding:12px 16px;border:1px solid #d1d5db;border-radius:8px;font-size:15px;font-family:inherit;background:#fff;">
                        <option value="">Please Select</option>
                        <option>Individual</option><option>Company</option><option>Start-Up</option><option>Innovator</option><option>Organization</option>
                    </select>
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label style="display:block;margin-bottom:6px;font-weight:500;color:#374151;">Country <span style="color:#dc2626;">*</span></label>
                    <select name="country" required style="width:100%;padding:12px 16px;border:1px solid #d1d5db;border-radius:8px;font-size:15px;font-family:inherit;background:#fff;">
                        <option value="">Please Select</option>
                        <option>Thailand</option><option>Singapore</option><option>Malaysia</option><option>Viet Nam</option><option>Indonesia</option><option>Philippines (the)</option><option>Japan</option><option>Republic of Korea (the)</option><option>China</option><option>India</option><option>Australia</option><option>New Zealand</option><option>United States of America (the)</option><option>Canada</option><option>Mexico</option><option>Brazil</option><option>United Kingdom of Great Britain and Northern Ireland (the)</option><option>Germany</option><option>France</option><option>Netherlands (the)</option><option>Switzerland</option><option>Sweden</option><option>United Arab Emirates (the)</option><option>Saudi Arabia</option><option>South Africa</option><option>Other</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label style="display:block;margin-bottom:6px;font-weight:500;color:#374151;">Job title <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="job_title" required maxlength="120" placeholder="Your job title" style="width:100%;padding:12px 16px;border:1px solid #d1d5db;border-radius:8px;font-size:15px;font-family:inherit;">
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label style="display:block;margin-bottom:6px;font-weight:500;color:#374151;">Email address <span style="color:#dc2626;">*</span></label>
                    <input type="email" name="email" id="cf-email" required maxlength="254" placeholder="your@email.com" pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}" style="width:100%;padding:12px 16px;border:1px solid #d1d5db;border-radius:8px;font-size:15px;font-family:inherit;">
                    <small id="cf-email-err" style="color:#dc2626;font-size:12px;display:none;margin-top:4px;">Please enter a valid email address, e.g. name@gmail.com</small>
                </div>
                <div class="col-md-6">
                    <label style="display:block;margin-bottom:6px;font-weight:500;color:#374151;">Phone number <span style="color:#dc2626;">*</span></label>
                    <input type="tel" name="phone" id="cf-phone" required maxlength="10" minlength="9" inputmode="numeric" pattern="[0-9]{9,10}" placeholder="0XXXXXXXXX" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)" style="width:100%;padding:12px 16px;border:1px solid #d1d5db;border-radius:8px;font-size:15px;font-family:inherit;">
                    <small id="cf-phone-err" style="color:#dc2626;font-size:12px;display:none;margin-top:4px;">Please enter 9 to 15 digits only</small>
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label style="display:block;margin-bottom:6px;font-weight:500;color:#374151;">Interested products <span style="font-size:12px;color:#64748b;">Optional</span></label>
                    <input type="text" name="interested_products" maxlength="255" placeholder="Interested products" style="width:100%;padding:12px 16px;border:1px solid #d1d5db;border-radius:8px;font-size:15px;font-family:inherit;">
                </div>
                <div class="col-md-6">
                    <label style="display:block;margin-bottom:6px;font-weight:500;color:#374151;">File upload <span style="font-size:12px;color:#64748b;">Optional</span></label>
                    <input type="file" name="file_upload" accept="application/msword, application/vnd.ms-excel, application/vnd.ms-powerpoint, text/plain, application/pdf, image/*" style="width:100%;padding:10px 12px;border:1px solid #d1d5db;border-radius:8px;font-size:14px;font-family:inherit;background:#fff;">
                </div>
            </div>
            <div class="mb-3">
                <label style="display:block;margin-bottom:6px;font-weight:500;color:#374151;">Message <span style="color:#dc2626;">*</span></label>
                <textarea name="message" required minlength="10" maxlength="2000" placeholder="How can we help you?" style="width:100%;padding:12px 16px;border:1px solid #d1d5db;border-radius:8px;font-size:15px;font-family:inherit;min-height:130px;resize:vertical;"></textarea>
            </div>
            <div class="mb-3" style="margin-bottom:16px;">
                <label style="display:flex;gap:10px;align-items:flex-start;cursor:pointer;font-size:13px;color:#374151;line-height:1.5;">
                    <input type="checkbox" id="cf-pdpa" name="pdpa_consent" required style="margin-top:3px;min-width:18px;height:18px;accent-color:<?php echo esc_attr($theme_primary); ?>;cursor:pointer;" onchange="var b=document.getElementById('cf-submit-btn');b.disabled=!this.checked;b.style.opacity=this.checked?'1':'0.5';">
                    <span>I consent to KV Electronics collecting and storing my data for the purpose of responding to my inquiry in accordance with the <a href="<?php echo esc_url(home_url('/privacy-policy')); ?>" target="_blank" style="color:<?php echo esc_attr($theme_primary); ?>;text-decoration:underline;">Privacy Policy (PDPA)</a>. <span style="color:#dc2626;">*</span></span>
                </label>
            </div>
            <div id="cf-result" style="display:none;padding:12px 16px;border-radius:8px;margin-bottom:12px;font-size:14px;"></div>
            <button type="submit" id="cf-submit-btn" disabled style="width:100%;padding:13px 28px;background:<?php echo esc_attr($theme_accent); ?>;color:#fff;border:none;border-radius:8px;font-weight:600;font-size:15px;cursor:pointer;font-family:inherit;transition:opacity 0.2s;opacity:0.5;" onmouseover="if(!this.disabled)this.style.opacity='0.85'" onmouseout="this.style.opacity=this.disabled?'0.5':'1'">
                Send Message
            </button>
        </form>
    </div>
    <script>
    (function(){
        var emailInput = document.getElementById('cf-email');
        var phoneInput = document.getElementById('cf-phone');
        var emailErr   = document.getElementById('cf-email-err');
        var phoneErr   = document.getElementById('cf-phone-err');
        if (emailInput) {
            emailInput.addEventListener('input', function(){
                this.value = this.value.replace(/[^\x20-\x7E]/g, '');
            });
            emailInput.addEventListener('blur', function(){
                var full = /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/;
                if (emailErr) emailErr.style.display = (this.value && !full.test(this.value)) ? 'block' : 'none';
                this.style.borderColor = (this.value && !full.test(this.value)) ? '#dc2626' : '#d1d5db';
            });
        }
        if (phoneInput) {
            phoneInput.addEventListener('input', function(){
                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
                if (phoneErr) phoneErr.style.display = (this.value && (this.value.length < 9)) ? 'block' : 'none';
                this.style.borderColor = (this.value && this.value.length < 9) ? '#dc2626' : '#d1d5db';
            });
            phoneInput.addEventListener('keydown', function(e){
                if ([8,9,13,27,46,37,38,39,40].indexOf(e.keyCode) !== -1) return;
                if ((e.ctrlKey || e.metaKey) && [65,67,86,88].indexOf(e.keyCode) !== -1) return;
                if (e.key && e.key.length === 1 && !/[0-9]/.test(e.key)) e.preventDefault();
            });
        }
    })();
    </script>
    <?php
    return ob_get_clean();
});

/**
 * [kv_google_map]
 * Google Map embed + info card with directions & copy address.
 */
add_shortcode('kv_google_map', function () {
    $url           = get_option('site_map_embed', '');
    $address_full  = get_option('site_address_full',
        "988 Moo 2, Soi Thetsaban Bang Poo 60\nSukhumvit Road, Tumbol Thai Ban\nAmphur Muang, Samut Prakan 10280\nThailand");
    $address_one   = get_option('site_address',
        '988 Moo 2, Soi Thetsaban Bang Poo 60, Sukhumvit Road, Tumbol Thai Ban, Amphur Muang, Samut Prakan 10280, Thailand');
    $phone         = kv_format_phone_th(get_option('site_phone', ''));
    $phone_raw     = get_option('site_phone', '');
    $hours_wd      = get_option('site_hours_weekday', 'Monday – Friday: 8:00 AM – 5:00 PM');
    $hours_we      = get_option('site_hours_weekend', 'Saturday – Sunday: Closed');
    $theme_primary = get_option('theme_primary_color', '#0056d6');
    $hide_info_card = !is_admin() && is_page(array('contact', 'contacts'));

    $q              = urlencode($address_one);
    $directions_url = 'https://www.google.com/maps/dir/?api=1&destination=' . $q;
    $view_url       = 'https://www.google.com/maps/search/?api=1&query=' . $q;

    ob_start();
    if (!empty($url)) : ?>
    <style>
      .kv-google-map-wrapper{width:100vw!important;max-width:none!important;margin-left:calc(50% - 50vw)!important;margin-right:calc(50% - 50vw)!important;position:relative!important;box-sizing:border-box!important;overflow:visible!important;margin-top:0!important;margin-bottom:0!important;padding-top:0!important;padding-bottom:0!important;}
      html,body{overflow-x:clip!important;}
      main:has(.kv-google-map-wrapper),
      .wp-block-group:has(.kv-google-map-wrapper),
      .wp-block-post-content:has(.kv-google-map-wrapper),
      .has-global-padding:has(.kv-google-map-wrapper),
      .entry-content:has(.kv-google-map-wrapper){padding-bottom:0!important;margin-bottom:0!important;}
      /* Remove gap between map and footer */
      .wp-block-template-part:has(.footer-dark){margin-top:0!important;padding-top:0!important;}
      .wp-site-blocks > .wp-block-template-part:last-child{margin-top:0!important;}
      .wp-site-blocks{gap:0!important;}
      main + .wp-block-template-part{margin-top:0!important;}
    </style>
    <div class="kv-google-map-wrapper alignfull" style="margin-bottom:0;padding-bottom:0;">
        <!-- Map -->
        <div style="overflow:hidden;">
            <iframe src="<?php echo esc_url($url); ?>" width="100%" height="480" style="border:0;display:block;width:100%;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
        <?php if (!$hide_info_card) : ?>
        <!-- Info card -->
        <div style="margin-top:16px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px 24px;display:flex;flex-wrap:wrap;gap:20px;align-items:flex-start;justify-content:space-between;">
            <div style="flex:1;min-width:220px;">
                <div style="display:flex;gap:10px;align-items:flex-start;margin-bottom:14px;">
                    <span style="font-size:20px;line-height:1;">📍</span>
                    <div>
                        <div style="font-weight:600;color:#1e293b;margin-bottom:2px;">Address</div>
                        <div style="color:#64748b;font-size:14px;line-height:1.6;"><?php echo nl2br(esc_html($address_full)); ?></div>
                        <button onclick="navigator.clipboard.writeText(<?php echo json_encode($address_one); ?>).then(function(){var b=this;b.textContent='\u2713 Copied!';setTimeout(function(){b.textContent='Copy Address';},2000)}.bind(this))"
                                style="margin-top:6px;background:none;border:1px solid #cbd5e1;border-radius:6px;padding:3px 10px;font-size:12px;color:#475569;cursor:pointer;"
                                onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='none'">Copy Address</button>
                    </div>
                </div>
                <?php if ($phone) : ?>
                <div style="display:flex;gap:10px;align-items:center;margin-bottom:14px;">
                    <span style="font-size:20px;line-height:1;">📞</span>
                    <div>
                        <div style="font-weight:600;color:#1e293b;margin-bottom:2px;">Phone</div>
                        <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $phone_raw)); ?>" style="color:<?php echo esc_attr($theme_primary); ?>;font-size:15px;font-weight:600;text-decoration:none;"><?php echo esc_html($phone); ?></a>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($hours_wd) : ?>
                <div style="display:flex;gap:10px;align-items:flex-start;">
                    <span style="font-size:20px;line-height:1;">🕐</span>
                    <div>
                        <div style="font-weight:600;color:#1e293b;margin-bottom:2px;">Business Hours</div>
                        <div style="color:#64748b;font-size:14px;line-height:1.6;"><?php echo esc_html($hours_wd); ?><br><?php echo esc_html($hours_we); ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div style="display:flex;flex-direction:column;gap:10px;min-width:180px;">
                <a href="<?php echo esc_url($directions_url); ?>" target="_blank" rel="noopener"
                   style="display:flex;align-items:center;justify-content:center;gap:8px;background:<?php echo esc_attr($theme_primary); ?>;color:#fff;padding:12px 20px;border-radius:8px;font-weight:600;font-size:14px;text-decoration:none;transition:opacity 0.2s;"
                   onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M21.71 11.29l-9-9a1 1 0 0 0-1.42 0l-9 9a1 1 0 0 0 0 1.42l9 9a1 1 0 0 0 1.42 0l9-9a1 1 0 0 0 0-1.42zM14 14.5V12h-4v3H8v-4a1 1 0 0 1 1-1h5V7.5l3.5 3.5-3.5 3.5z"/></svg>
                    Get Directions
                </a>
                <a href="<?php echo esc_url($view_url); ?>" target="_blank" rel="noopener"
                   style="display:flex;align-items:center;justify-content:center;gap:8px;background:#fff;color:<?php echo esc_attr($theme_primary); ?>;padding:12px 20px;border-radius:8px;font-weight:600;font-size:14px;text-decoration:none;border:2px solid <?php echo esc_attr($theme_primary); ?>;transition:background 0.2s;"
                   onmouseover="this.style.background='<?php echo esc_attr($theme_primary); ?>';this.style.color='#fff'" onmouseout="this.style.background='#fff';this.style.color='<?php echo esc_attr($theme_primary); ?>'">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                    View on Google Maps
                </a>
                <?php if ($phone_raw) : ?>
                <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $phone_raw)); ?>"
                   style="display:flex;align-items:center;justify-content:center;gap:8px;background:#fff;color:#16a34a;padding:12px 20px;border-radius:8px;font-weight:600;font-size:14px;text-decoration:none;border:2px solid #16a34a;transition:background 0.2s;"
                   onmouseover="this.style.background='#16a34a';this.style.color='#fff'" onmouseout="this.style.background='#fff';this.style.color='#16a34a'">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1-9.4 0-17-7.6-17-17 0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1l-2.3 2.2z"/></svg>
                    Call Us
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php else : ?>
    <section style="background:#e2e8f0;min-height:350px;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:10px;color:#64748b;">
        <span style="font-size:32px;">📍</span>
        <p style="margin:0;font-size:16px;">Google Map will appear here</p>
        <p style="margin:0;font-size:13px;">Add your Google Maps Embed URL in <a href="<?php echo admin_url('admin.php?page=my-theme-settings'); ?>">Site Settings</a></p>
    </section>
    <?php endif;
    return ob_get_clean();
});

// ============================================
// DATASHEET LEADS — DB TABLE + AJAX + ADMIN
// ============================================

/**
 * สร้างตาราง datasheet_leads เมื่อเปิดใช้งาน theme
 */
function my_theme_create_leads_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'datasheet_leads';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        product_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        product_name VARCHAR(255) NOT NULL DEFAULT '',
        lead_name VARCHAR(255) NOT NULL DEFAULT '',
        lead_email VARCHAR(255) NOT NULL DEFAULT '',
        ip_address VARCHAR(45) NOT NULL DEFAULT '',
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY product_id (product_id),
        KEY lead_email (lead_email)
    ) {$charset};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
add_action('after_switch_theme', 'my_theme_create_leads_table');
// Also run on init (once) to ensure table exists
add_action('init', function() {
    if (get_option('my_theme_leads_table_version') !== '1.1') {
        my_theme_create_leads_table();
        update_option('my_theme_leads_table_version', '1.1');
    }
});

/**
 * AJAX: บันทึก lead + ส่ง URL สำหรับดาวน์โหลด PDF
 */
add_action('wp_ajax_save_datasheet_lead', 'my_theme_save_datasheet_lead');
add_action('wp_ajax_nopriv_save_datasheet_lead', 'my_theme_save_datasheet_lead');

function my_theme_save_datasheet_lead() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'datasheet_lead_nonce')) {
        wp_send_json_error(['message' => 'Invalid security token.']);
    }

    $name       = sanitize_text_field($_POST['lead_name'] ?? '');
    $email      = sanitize_email($_POST['lead_email'] ?? '');
    $product_id = absint($_POST['product_id'] ?? 0);

    if (!$name || !$email) {
        wp_send_json_error(['message' => 'Please enter your full name and email']);
    }
    if (!is_email($email)) {
        wp_send_json_error(['message' => 'Invalid email format']);
    }

    global $wpdb;
    $table = $wpdb->prefix . 'datasheet_leads';

    // บันทึกลง database
    $inserted = $wpdb->insert($table, [
        'product_id'   => $product_id,
        'product_name' => get_the_title($product_id) ?: 'Unknown Product',
        'lead_name'    => $name,
        'lead_email'   => $email,
        'ip_address'   => $_SERVER['REMOTE_ADDR'] ?? '',
        'created_at'   => current_time('mysql'),
    ], ['%d', '%s', '%s', '%s', '%s', '%s']);

    if ($inserted === false) {
        wp_send_json_error(['message' => 'ไม่สามารถบันทึกข้อมูลได้ กรุณาลองใหม่']);
    }

    // สร้าง URL สำหรับดาวน์โหลด PDF
    $uploaded_pdf = get_post_meta($product_id, 'pd_datasheet', true);
    if ($uploaded_pdf) {
        $download_url = $uploaded_pdf;
    } else {
        $download_url = add_query_arg('get_datasheet', '1', get_permalink($product_id));
    }

    wp_send_json_success([
        'message'      => 'บันทึกข้อมูลสำเร็จ',
        'download_url' => $download_url,
        'lead_id'      => $wpdb->insert_id,
    ]);
}

/**
 * Enqueue AJAX script สำหรับ frontend (product pages)
 */
add_action('wp_enqueue_scripts', function() {
    if (is_singular('product') || is_page()) {
        wp_localize_script('jquery', 'dsLeadAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('datasheet_lead_nonce'),
        ]);
    }
});

/**
 * Admin Submenu: Datasheet Downloads
 */
add_action('admin_menu', function() {
    add_submenu_page(
        'kv-manage',
        'Datasheet Downloads',
        '📥 Datasheet Downloads',
        'manage_options',
        'datasheet-leads',
        'my_theme_datasheet_leads_page'
    );
});

/**
 * Handle CSV export
 */
add_action('admin_init', function() {
    if (!isset($_GET['page'], $_GET['action']) || $_GET['page'] !== 'datasheet-leads' || $_GET['action'] !== 'export_csv') return;
    if (!current_user_can('manage_options')) return;
    if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'export_leads_csv')) return;

    global $wpdb;
    $table = $wpdb->prefix . 'datasheet_leads';
    $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC", ARRAY_A);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=datasheet-leads-' . date('Y-m-d') . '.csv');
    $out = fopen('php://output', 'w');
    // BOM for Excel UTF-8
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, ['ID', 'Product ID', 'Product Name', 'Name', 'Email', 'IP Address', 'Date']);
    foreach ($rows as $row) {
        fputcsv($out, [
            $row['id'], $row['product_id'], $row['product_name'],
            $row['lead_name'], $row['lead_email'], $row['ip_address'], $row['created_at']
        ]);
    }
    fclose($out);
    exit;
});

/**
 * Handle single lead deletion
 */
add_action('admin_init', function() {
    if (!isset($_GET['page'], $_GET['action'], $_GET['lead_id']) || $_GET['page'] !== 'datasheet-leads' || $_GET['action'] !== 'delete_lead') return;
    if (!current_user_can('manage_options')) return;
    if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'delete_lead_' . $_GET['lead_id'])) return;

    global $wpdb;
    $wpdb->delete($wpdb->prefix . 'datasheet_leads', ['id' => absint($_GET['lead_id'])], ['%d']);

    wp_redirect(admin_url('admin.php?page=datasheet-leads&deleted=1'));
    exit;
});

/**
 * Admin page: แสดงรายการ leads
 */
function my_theme_datasheet_leads_page() {
    if (!current_user_can('manage_options')) return;

    global $wpdb;
    $table = $wpdb->prefix . 'datasheet_leads';

    // Pagination
    $per_page = 20;
    $current_page = max(1, absint($_GET['paged'] ?? 1));
    $offset = ($current_page - 1) * $per_page;

    // Search
    $search = sanitize_text_field($_GET['s'] ?? '');
    $where = '';
    if ($search) {
        $like = '%' . $wpdb->esc_like($search) . '%';
        $where = $wpdb->prepare(" WHERE lead_name LIKE %s OR lead_email LIKE %s OR product_name LIKE %s", $like, $like, $like);
    }

    $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}{$where}");
    $total_pages = ceil($total / $per_page);
    $rows = $wpdb->get_results("SELECT * FROM {$table}{$where} ORDER BY created_at DESC LIMIT {$per_page} OFFSET {$offset}", ARRAY_A);

    // Stats
    $total_all     = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    $today_count   = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE DATE(created_at) = %s", current_time('Y-m-d')));
    $unique_emails = (int) $wpdb->get_var("SELECT COUNT(DISTINCT lead_email) FROM {$table}");

    $export_url = wp_nonce_url(admin_url('admin.php?page=datasheet-leads&action=export_csv'), 'export_leads_csv');
    ?>
    <div class="wrap">
        <h1 style="display:flex;align-items:center;gap:10px;">
            📥 Datasheet Download Leads
            <a href="<?php echo esc_url($export_url); ?>" class="page-title-action" style="background:#16a34a;color:#fff;border:none;">📊 Export CSV</a>
        </h1>

        <?php if (isset($_GET['deleted'])) : ?>
            <div class="notice notice-success is-dismissible"><p>Lead deleted successfully.</p></div>
        <?php endif; ?>

        <!-- Stats -->
        <div style="display:flex;gap:16px;margin:20px 0;">
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:16px 24px;flex:1;text-align:center;">
                <div style="font-size:28px;font-weight:700;color:var(--theme-primary);"><?php echo number_format($total_all); ?></div>
                <div style="color:#64748b;font-size:13px;">Total Downloads</div>
            </div>
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:16px 24px;flex:1;text-align:center;">
                <div style="font-size:28px;font-weight:700;color:#16a34a;"><?php echo number_format($today_count); ?></div>
                <div style="color:#64748b;font-size:13px;">Downloads Today</div>
            </div>
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:16px 24px;flex:1;text-align:center;">
                <div style="font-size:28px;font-weight:700;color:#9333ea;"><?php echo number_format($unique_emails); ?></div>
                <div style="color:#64748b;font-size:13px;">Unique Emails</div>
            </div>
        </div>

        <!-- Search -->
        <form method="get" style="margin-bottom:16px;">
            <input type="hidden" name="page" value="datasheet-leads">
            <div style="display:flex;gap:8px;align-items:center;">
                <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search name, email, product..." style="padding:6px 12px;border:1px solid #cbd5e1;border-radius:6px;width:300px;">
                <button type="submit" class="button">🔍 Search</button>
                <?php if ($search) : ?>
                    <a href="<?php echo admin_url('admin.php?page=datasheet-leads'); ?>" class="button">✕ Clear Search</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Table -->
        <table class="wp-list-table widefat fixed striped" style="border-radius:8px;overflow:hidden;">
            <thead>
                <tr>
                    <th style="width:50px;">#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Product</th>
                    <th style="width:130px;">IP</th>
                    <th style="width:160px;">Downloaded At</th>
                    <th style="width:80px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($rows) : ?>
                <?php foreach ($rows as $row) : ?>
                <tr>
                    <td><?php echo esc_html($row['id']); ?></td>
                    <td><strong><?php echo esc_html($row['lead_name']); ?></strong></td>
                    <td><a href="mailto:<?php echo esc_attr($row['lead_email']); ?>"><?php echo esc_html($row['lead_email']); ?></a></td>
                    <td>
                        <?php $product_url = kv_get_product_view_url($row['product_id'] ?? 0, $row['product_name'] ?? ''); ?>
                        <a href="<?php echo esc_url($product_url); ?>" target="_blank" rel="noopener"><?php echo esc_html($row['product_name']); ?></a>
                    </td>
                    <td style="font-size:12px;color:#64748b;"><?php echo esc_html($row['ip_address']); ?></td>
                    <td style="font-size:13px;"><?php echo esc_html(date('d/m/Y H:i', strtotime($row['created_at']))); ?></td>
                    <td>
                        <?php
                        $del_url = wp_nonce_url(
                            admin_url('admin.php?page=datasheet-leads&action=delete_lead&lead_id=' . $row['id']),
                            'delete_lead_' . $row['id']
                        );
                        ?>
                        <a href="<?php echo esc_url($del_url); ?>" onclick="return confirm('Confirm delete this lead?');" style="color:#dc2626;text-decoration:none;">🗑️ Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="7" style="text-align:center;padding:40px;color:#94a3b8;">
                        <?php echo $search ? 'No matching results found.' : 'No download leads yet.'; ?>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_pages > 1) : ?>
        <div class="tablenav" style="margin-top:16px;">
            <div class="tablenav-pages">
                <span class="displaying-num"><?php echo number_format($total); ?> items</span>
                <?php
                echo paginate_links([
                    'base'      => add_query_arg('paged', '%#%'),
                    'format'    => '',
                    'current'   => $current_page,
                    'total'     => $total_pages,
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                ]);
                ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

// ============================================
// CONTACT FORM — DB TABLE + AJAX + ADMIN
// ============================================

/**
 * สร้างตาราง contact_submissions
 */
function my_theme_create_contact_table() {
    global $wpdb;
    $table   = $wpdb->prefix . 'contact_submissions';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table} (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL DEFAULT '',
        company varchar(255) NOT NULL DEFAULT '',
        industry varchar(120) NOT NULL DEFAULT '',
        organization_type varchar(120) NOT NULL DEFAULT '',
        country varchar(120) NOT NULL DEFAULT '',
        job_title varchar(150) NOT NULL DEFAULT '',
        email varchar(255) NOT NULL DEFAULT '',
        phone varchar(50) NOT NULL DEFAULT '',
        subject varchar(100) NOT NULL DEFAULT '',
        product varchar(100) NOT NULL DEFAULT '',
        interested_products varchar(255) NOT NULL DEFAULT '',
        file_url text,
        message text NOT NULL,
        ip_address varchar(45) NOT NULL DEFAULT '',
        status varchar(20) NOT NULL DEFAULT 'new',
        created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY email (email),
        KEY status (status)
    ) {$charset};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    // Explicit ALTER TABLE fallback: add missing columns that dbDelta may skip
    $existing = $wpdb->get_col("SHOW COLUMNS FROM {$table}", 0);
    $needed = [
        'industry'            => "varchar(120) NOT NULL DEFAULT '' AFTER company",
        'organization_type'   => "varchar(120) NOT NULL DEFAULT '' AFTER industry",
        'country'             => "varchar(120) NOT NULL DEFAULT '' AFTER organization_type",
        'job_title'           => "varchar(150) NOT NULL DEFAULT '' AFTER country",
        'interested_products' => "varchar(255) NOT NULL DEFAULT '' AFTER product",
        'file_url'            => "text AFTER interested_products",
    ];
    foreach ($needed as $col => $def) {
        if (!in_array($col, $existing, true)) {
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN {$col} {$def}");
        }
    }
}
add_action('after_switch_theme', 'my_theme_create_contact_table');
add_action('init', function () {
    if (get_option('my_theme_contact_table_version') !== '1.3') {
        my_theme_create_contact_table();
        update_option('my_theme_contact_table_version', '1.3');
    }
});

/**
 * AJAX: บันทึก contact form submission
 */
add_action('wp_ajax_submit_contact_form',        'my_theme_submit_contact_form');
add_action('wp_ajax_nopriv_submit_contact_form', 'my_theme_submit_contact_form');

function my_theme_submit_contact_form() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'contact_form_nonce')) {
        wp_send_json_error(['message' => 'Invalid security token.']);
    }

    $name    = sanitize_text_field($_POST['cf_name']    ?? $_POST['name'] ?? '');
    $company = sanitize_text_field($_POST['cf_company'] ?? $_POST['company'] ?? '');
    $industry = sanitize_text_field($_POST['cf_industry'] ?? $_POST['industry'] ?? '');
    $organization_type = sanitize_text_field($_POST['cf_organization_type'] ?? $_POST['organization_type'] ?? '');
    $country = sanitize_text_field($_POST['cf_country'] ?? $_POST['country'] ?? '');
    $job_title = sanitize_text_field($_POST['cf_job_title'] ?? $_POST['job_title'] ?? '');
    $email   = sanitize_email($_POST['cf_email']        ?? $_POST['email'] ?? '');
    $phone   = sanitize_text_field($_POST['cf_phone']   ?? $_POST['phone'] ?? '');
    $phone_digits = preg_replace('/\D+/', '', $phone);
    $subject = sanitize_text_field(($_POST['cf_subject'] ?? $_POST['subject'] ?? $industry) ?: 'contact_inquiry');
    $product = sanitize_text_field($_POST['cf_product'] ?? $_POST['product'] ?? '');
    $interested_products = sanitize_text_field($_POST['cf_interested_products'] ?? $_POST['interested_products'] ?? '');
    $message = sanitize_textarea_field($_POST['cf_message'] ?? $_POST['message'] ?? '');

    // Validate required fields
    if (!$name || !$company || !$industry || !$organization_type || !$country || !$job_title || !$email || !$phone || !$message) {
        wp_send_json_error(['message' => 'Please complete all required fields.']);
    }
    if (!is_email($email)) {
        wp_send_json_error(['message' => 'Invalid email format.']);
    }
    if ($phone_digits === '' || strlen($phone_digits) > 10 || strlen($phone_digits) < 9) {
        wp_send_json_error(['message' => 'Phone number must be 9-10 digits.']);
    }
    if (mb_strlen(trim($message)) < 10) {
        wp_send_json_error(['message' => 'Message must be at least 10 characters.']);
    }
    $phone = $phone_digits;
    // PDPA consent required
    if (empty($_POST['cf_pdpa']) && empty($_POST['pdpa_consent'])) {
        wp_send_json_error(['message' => 'Please accept the Privacy Policy (PDPA) before submitting.']);
    }

    $file_url = '';
    $file_path = '';
    if (!empty($_FILES['cf_file']) && !empty($_FILES['cf_file']['name'])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $upload = wp_handle_upload($_FILES['cf_file'], ['test_form' => false]);
        if (!empty($upload['error'])) {
            wp_send_json_error(['message' => 'File upload failed: ' . $upload['error']]);
        }
        $file_url = esc_url_raw($upload['url'] ?? '');
        $file_path = isset($upload['file']) ? (string) $upload['file'] : '';
    }

    global $wpdb;
    $table    = $wpdb->prefix . 'contact_submissions';
    $payload = [
        'name'       => $name,
        'company'    => $company,
        'industry'   => $industry,
        'organization_type' => $organization_type,
        'country'    => $country,
        'job_title'  => $job_title,
        'email'      => $email,
        'phone'      => $phone,
        'subject'    => $subject,
        'product'    => $product,
        'interested_products' => $interested_products,
        'file_url'   => $file_url,
        'message'    => $message,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'status'     => 'new',
        'created_at' => current_time('mysql'),
    ];
    $formats_map = [
        'name' => '%s', 'company' => '%s', 'industry' => '%s', 'organization_type' => '%s',
        'country' => '%s', 'job_title' => '%s', 'email' => '%s', 'phone' => '%s',
        'subject' => '%s', 'product' => '%s', 'interested_products' => '%s',
        'file_url' => '%s', 'message' => '%s', 'ip_address' => '%s', 'status' => '%s',
        'created_at' => '%s',
    ];

    $existing_columns = $wpdb->get_col("SHOW COLUMNS FROM {$table}", 0);
    if (empty($existing_columns)) {
        my_theme_create_contact_table();
        $existing_columns = $wpdb->get_col("SHOW COLUMNS FROM {$table}", 0);
    }

    $insert_data = [];
    $insert_formats = [];
    foreach ($payload as $column => $value) {
        if (in_array($column, $existing_columns, true)) {
            $insert_data[$column] = $value;
            $insert_formats[] = $formats_map[$column] ?? '%s';
        }
    }

    $inserted = !empty($insert_data) ? $wpdb->insert($table, $insert_data, $insert_formats) : false;

    if ($inserted === false && strpos((string) $wpdb->last_error, 'Unknown column') !== false) {
        my_theme_create_contact_table();
        $inserted = $wpdb->insert($table, $insert_data, $insert_formats);
    }

    if ($inserted === false) {
        wp_send_json_error(['message' => 'Unable to save your submission. Please try again.']);
    }

    $to_email = sanitize_email(get_option('site_email', get_theme_mod('site_email', 'info@company.com')));
    $admin_email = sanitize_email(get_option('admin_email'));
    $recipients = [];
    if (is_email($to_email)) {
        $recipients[] = $to_email;
    }
    if (is_email($admin_email) && !in_array($admin_email, $recipients, true)) {
        $recipients[] = $admin_email;
    }
    if (empty($recipients)) {
        wp_send_json_error(['message' => 'No valid recipient email configured in site_email/admin_email.']);
    }

    $mail_subject = sprintf('[KV Website] New Contact Inquiry - %s', $subject ?: $industry ?: 'General Inquiry');
    $submitted_at = current_time('mysql');
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '-';

    $mail_body = '';
    $mail_body .= '<div style="background:#f8fafc;padding:24px;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Arial,sans-serif;color:#0f172a;">';
    $mail_body .= '<div style="max-width:760px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">';
    $mail_body .= '<div style="background:#0042aa;color:#ffffff;padding:16px 20px;">';
    $mail_body .= '<h2 style="margin:0;font-size:20px;line-height:1.3;">New Contact Form Submission</h2>';
    $mail_body .= '<p style="margin:6px 0 0;font-size:13px;opacity:.92;">This email is auto-generated from KV website contact form.</p>';
    $mail_body .= '</div>';

    $mail_body .= '<div style="padding:20px;">';
    $mail_body .= '<table role="presentation" cellspacing="0" cellpadding="0" style="width:100%;border-collapse:collapse;font-size:14px;">';
    $mail_body .= '<tr><td style="width:180px;padding:10px 12px;background:#f8fafc;border:1px solid #e2e8f0;font-weight:600;">Contact Name</td><td style="padding:10px 12px;border:1px solid #e2e8f0;">' . esc_html($name) . '</td></tr>';
    $mail_body .= '<tr><td style="padding:10px 12px;background:#f8fafc;border:1px solid #e2e8f0;font-weight:600;">Company Name</td><td style="padding:10px 12px;border:1px solid #e2e8f0;">' . esc_html($company) . '</td></tr>';
    $mail_body .= '<tr><td style="padding:10px 12px;background:#f8fafc;border:1px solid #e2e8f0;font-weight:600;">Industry</td><td style="padding:10px 12px;border:1px solid #e2e8f0;">' . esc_html($industry) . '</td></tr>';
    $mail_body .= '<tr><td style="padding:10px 12px;background:#f8fafc;border:1px solid #e2e8f0;font-weight:600;">Organization Type</td><td style="padding:10px 12px;border:1px solid #e2e8f0;">' . esc_html($organization_type) . '</td></tr>';
    $mail_body .= '<tr><td style="padding:10px 12px;background:#f8fafc;border:1px solid #e2e8f0;font-weight:600;">Country</td><td style="padding:10px 12px;border:1px solid #e2e8f0;">' . esc_html($country) . '</td></tr>';
    $mail_body .= '<tr><td style="padding:10px 12px;background:#f8fafc;border:1px solid #e2e8f0;font-weight:600;">Job Title</td><td style="padding:10px 12px;border:1px solid #e2e8f0;">' . esc_html($job_title) . '</td></tr>';
    $mail_body .= '<tr><td style="padding:10px 12px;background:#f8fafc;border:1px solid #e2e8f0;font-weight:600;">Email</td><td style="padding:10px 12px;border:1px solid #e2e8f0;"><a href="mailto:' . esc_attr($email) . '">' . esc_html($email) . '</a></td></tr>';
    $mail_body .= '<tr><td style="padding:10px 12px;background:#f8fafc;border:1px solid #e2e8f0;font-weight:600;">Phone Number</td><td style="padding:10px 12px;border:1px solid #e2e8f0;">' . esc_html($phone) . '</td></tr>';
    $mail_body .= '<tr><td style="padding:10px 12px;background:#f8fafc;border:1px solid #e2e8f0;font-weight:600;">Subject</td><td style="padding:10px 12px;border:1px solid #e2e8f0;">' . esc_html($subject !== '' ? $subject : '-') . '</td></tr>';
    $mail_body .= '<tr><td style="padding:10px 12px;background:#f8fafc;border:1px solid #e2e8f0;font-weight:600;">Interested Products</td><td style="padding:10px 12px;border:1px solid #e2e8f0;">' . esc_html($interested_products !== '' ? $interested_products : '-') . '</td></tr>';
    $mail_body .= '<tr><td style="padding:10px 12px;background:#f8fafc;border:1px solid #e2e8f0;font-weight:600;">File Upload</td><td style="padding:10px 12px;border:1px solid #e2e8f0;">' . ($file_url !== '' ? '<a href="' . esc_url($file_url) . '" target="_blank" rel="noopener">View uploaded file</a>' : '-') . '</td></tr>';
    $mail_body .= '<tr><td style="padding:10px 12px;background:#f8fafc;border:1px solid #e2e8f0;font-weight:600;vertical-align:top;">Message</td><td style="padding:10px 12px;border:1px solid #e2e8f0;white-space:normal;line-height:1.65;">' . nl2br(esc_html($message)) . '</td></tr>';
    $mail_body .= '</table>';

    $mail_body .= '<div style="margin-top:16px;padding:12px 14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;font-size:12px;color:#475569;">';
    $mail_body .= '<div><strong>Submitted at:</strong> ' . esc_html($submitted_at) . '</div>';
    $mail_body .= '<div><strong>IP Address:</strong> ' . esc_html($ip_address) . '</div>';
    $mail_body .= '</div>';

    $mail_body .= '</div>';
    $mail_body .= '</div>';
    $mail_body .= '</div>';
    $mail_headers = [
        'Content-Type: text/html; charset=UTF-8',
        'Reply-To: ' . sanitize_text_field($name) . ' <' . $email . '>',
    ];
    $mail_attachments = [];
    if ($file_path !== '' && file_exists($file_path)) {
        $mail_attachments[] = $file_path;
    }

    $mail_sent = wp_mail($recipients, $mail_subject, $mail_body, $mail_headers, $mail_attachments);
    if (!$mail_sent) {
        $recipient_text = implode(', ', $recipients);
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('KV contact mail failed. Recipients: ' . $recipient_text . ' Subject: ' . $mail_subject);
        }
        wp_send_json_error(['message' => 'Submission saved, but email notification could not be sent. Recipients: ' . $recipient_text]);
    }

    wp_send_json_success(['message' => 'Message sent successfully to: ' . implode(', ', $recipients)]);
}

/**
 * Enqueue contact form nonce ให้ทุกหน้า (attach to bootstrap handle)
 */
add_action('wp_enqueue_scripts', function () {
    wp_add_inline_script('bootstrap', 'var cfAjax = ' . wp_json_encode([
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('contact_form_nonce'),
    ]) . ';', 'before');

    // Contact form AJAX submit handler
    wp_add_inline_script('bootstrap', <<<'JS'
(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var forms = document.querySelectorAll('form#cf-form');
        if (!forms.length) return;

        forms.forEach(function (form) {
            var resultEl = form.querySelector('#cf-result') || form.querySelector('[id="cf-result"]');
            var btn = form.querySelector('button[type="submit"]');
            var resultTimer = null;

            function showResult(msg, isError, autoHideMs) {
                if (!resultEl) return;
                if (resultTimer) {
                    clearTimeout(resultTimer);
                    resultTimer = null;
                }
                resultEl.style.display = 'block';
                resultEl.style.background  = isError ? '#fee2e2' : '#dcfce7';
                resultEl.style.color       = isError ? '#991b1b' : '#166534';
                resultEl.style.border      = '1px solid ' + (isError ? '#fca5a5' : '#86efac');
                resultEl.textContent = msg;
                resultEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

                if (autoHideMs && autoHideMs > 0) {
                    resultTimer = setTimeout(function () {
                        resultEl.style.display = 'none';
                        resultEl.textContent = '';
                    }, autoHideMs);
                }
            }

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                form.classList.add('was-validated');
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                var pdpa = form.querySelector('[name="pdpa_consent"]');
                var getVal = function(name) {
                    var el = form.querySelector('[name="' + name + '"]');
                    return el ? (typeof el.value === 'string' ? el.value.trim() : el.value) : '';
                };

                var data = new FormData();
                data.append('action',        'submit_contact_form');
                data.append('nonce',         cfAjax.nonce);
                data.append('cf_name',       getVal('name'));
                data.append('cf_company',    getVal('company'));
                data.append('cf_industry',   getVal('industry'));
                data.append('cf_organization_type', getVal('organization_type'));
                data.append('cf_country',    getVal('country'));
                data.append('cf_job_title',  getVal('job_title'));
                data.append('cf_email',      getVal('email'));
                data.append('cf_phone',      getVal('phone'));
                data.append('cf_subject',    getVal('subject'));
                data.append('cf_product',    getVal('product'));
                data.append('cf_interested_products', getVal('interested_products'));
                data.append('cf_message',    getVal('message'));
                data.append('pdpa_consent',  pdpa && pdpa.checked ? '1' : '');

                var fileEl = form.querySelector('[name="file_upload"]');
                if (fileEl && fileEl.files && fileEl.files[0]) {
                    data.append('cf_file', fileEl.files[0]);
                }

                if (btn) { btn.disabled = true; btn.textContent = 'Sending...'; btn.style.opacity = '0.7'; }

                fetch(cfAjax.ajaxurl, { method: 'POST', body: data })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        if (res.success) {
                            showResult(res.data.message, false, 5000);
                            form.reset();
                            form.classList.remove('was-validated');
                            form.querySelectorAll('.is-valid, .is-invalid').forEach(function (el) {
                                el.classList.remove('is-valid', 'is-invalid');
                            });
                            if (btn) { btn.disabled = true; btn.style.opacity = '0.5'; btn.textContent = 'Send Message'; }
                        } else {
                            showResult(res.data.message || 'An error occurred. Please try again.', true);
                            if (btn) { btn.disabled = false; btn.style.opacity = '1'; btn.textContent = 'Send Message'; }
                        }
                    })
                    .catch(function () {
                        showResult('Unable to connect. Please try again.', true);
                        if (btn) { btn.disabled = false; btn.style.opacity = '1'; btn.textContent = 'Send Message'; }
                    });
            });
        });
    });
})();
JS
    );
}, 20);

/**
 * Admin Submenu: Contact Submissions
 */
add_action('admin_menu', function () {
    add_submenu_page(
        'kv-manage',
        'Contact Submissions',
        '✉️ Contact Submissions',
        'manage_options',
        'contact-submissions',
        'my_theme_contact_submissions_page'
    );
});

/**
 * Handle CSV export for contact submissions
 */
add_action('admin_init', function () {
    if (!isset($_GET['page'], $_GET['action']) || $_GET['page'] !== 'contact-submissions' || $_GET['action'] !== 'export_csv') return;
    if (!current_user_can('manage_options')) return;
    if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'export_contact_csv')) return;

    global $wpdb;
    $table = $wpdb->prefix . 'contact_submissions';
    $rows  = $wpdb->get_results("SELECT * FROM {$table} ORDER BY created_at DESC", ARRAY_A);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=contact-submissions-' . date('Y-m-d') . '.csv');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM for Excel
    fputcsv($out, ['ID', 'Name', 'Company', 'Email', 'Phone', 'Industry', 'Organization Type', 'Country', 'Job Title', 'Interested Products', 'Message', 'Attachment', 'IP', 'Status', 'Date']);
    foreach ($rows as $row) {
        fputcsv($out, [
            $row['id'] ?? '', $row['name'] ?? '', $row['company'] ?? '', $row['email'] ?? '', $row['phone'] ?? '',
            $row['industry'] ?? '', $row['organization_type'] ?? '', $row['country'] ?? '', $row['job_title'] ?? '',
            $row['interested_products'] ?? '', $row['message'] ?? '', $row['file_url'] ?? '', $row['ip_address'] ?? '',
            $row['status'] ?? '', $row['created_at'] ?? '',
        ]);
    }
    fclose($out);
    exit;
});

/**
 * Handle status update + deletion
 */
add_action('admin_init', function () {
    if (!isset($_GET['page']) || $_GET['page'] !== 'contact-submissions') return;
    if (!current_user_can('manage_options')) return;

    $action = $_GET['action'] ?? '';

    if ($action === 'delete' && isset($_GET['sub_id'])) {
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'delete_sub_' . $_GET['sub_id'])) return;
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'contact_submissions', ['id' => absint($_GET['sub_id'])], ['%d']);
        wp_redirect(admin_url('admin.php?page=contact-submissions&deleted=1'));
        exit;
    }

    if ($action === 'mark_read' && isset($_GET['sub_id'])) {
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'mark_read_' . $_GET['sub_id'])) return;
        global $wpdb;
        $wpdb->update($wpdb->prefix . 'contact_submissions', ['status' => 'read'], ['id' => absint($_GET['sub_id'])], ['%s'], ['%d']);
        wp_redirect(admin_url('admin.php?page=contact-submissions&marked=1'));
        exit;
    }
});

/**
 * Admin page: แสดงรายการ Contact Submissions
 */
function my_theme_contact_submissions_page() {
    if (!current_user_can('manage_options')) return;

    global $wpdb;
    $table = $wpdb->prefix . 'contact_submissions';

    $per_page     = 20;
    $current_page = max(1, absint($_GET['paged'] ?? 1));
    $offset       = ($current_page - 1) * $per_page;
    $search       = sanitize_text_field($_GET['s'] ?? '');
    $filter_status = sanitize_text_field($_GET['status_filter'] ?? '');

    $where_parts = [];
    if ($search) {
        $like = '%' . $wpdb->esc_like($search) . '%';
        $where_parts[] = $wpdb->prepare("(name LIKE %s OR email LIKE %s OR company LIKE %s OR message LIKE %s OR industry LIKE %s OR organization_type LIKE %s OR country LIKE %s OR job_title LIKE %s OR interested_products LIKE %s)", $like, $like, $like, $like, $like, $like, $like, $like, $like);
    }
    if ($filter_status) {
        $where_parts[] = $wpdb->prepare("status = %s", $filter_status);
    }
    $where = $where_parts ? ' WHERE ' . implode(' AND ', $where_parts) : '';

    $total       = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}{$where}");
    $total_pages = ceil($total / $per_page);
    $rows        = $wpdb->get_results("SELECT * FROM {$table}{$where} ORDER BY created_at DESC LIMIT {$per_page} OFFSET {$offset}", ARRAY_A);

    $total_all   = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    $new_count   = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE status = 'new'");
    $today_count = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE DATE(created_at) = %s", current_time('Y-m-d')));

    $export_url = wp_nonce_url(admin_url('admin.php?page=contact-submissions&action=export_csv'), 'export_contact_csv');

    ?>
    <div class="wrap">
        <h1 style="display:flex;align-items:center;gap:10px;">
            ✉️ Send Us a Message
            <a href="<?php echo esc_url($export_url); ?>" class="page-title-action" style="background:#16a34a;color:#fff;border:none;">📊 Export CSV</a>
        </h1>
        <p style="margin:0 0 14px;color:#64748b;">Fill out the form below and we'll get back to you as soon as possible.</p>

        <?php if (isset($_GET['deleted'])) : ?>
            <div class="notice notice-success is-dismissible"><p>Submission deleted successfully.</p></div>
        <?php endif; ?>
        <?php if (isset($_GET['marked'])) : ?>
            <div class="notice notice-success is-dismissible"><p>Status updated successfully.</p></div>
        <?php endif; ?>

        <!-- Stats -->
        <div style="display:flex;gap:16px;margin:20px 0;">
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:16px 24px;flex:1;text-align:center;">
                <div style="font-size:28px;font-weight:700;color:var(--theme-primary);"><?php echo number_format($total_all); ?></div>
                <div style="color:#64748b;font-size:13px;">Total</div>
            </div>
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:16px 24px;flex:1;text-align:center;">
                <div style="font-size:28px;font-weight:700;color:#dc2626;"><?php echo number_format($new_count); ?></div>
                <div style="color:#64748b;font-size:13px;">Unread</div>
            </div>
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:16px 24px;flex:1;text-align:center;">
                <div style="font-size:28px;font-weight:700;color:#16a34a;"><?php echo number_format($today_count); ?></div>
                <div style="color:#64748b;font-size:13px;">Today</div>
            </div>
        </div>

        <!-- Filter & Search -->
        <form method="get" style="margin-bottom:16px;">
            <input type="hidden" name="page" value="contact-submissions">
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="Search name, email, company, country, job title..." style="padding:6px 12px;border:1px solid #cbd5e1;border-radius:6px;width:360px;">
                <select name="status_filter" style="padding:6px 10px;border:1px solid #cbd5e1;border-radius:6px;">
                    <option value="">— All statuses —</option>
                    <option value="new"  <?php selected($filter_status, 'new'); ?>>Unread</option>
                    <option value="read" <?php selected($filter_status, 'read'); ?>>Read</option>
                </select>
                <button type="submit" class="button">🔍 Filter</button>
                <?php if ($search || $filter_status) : ?>
                    <a href="<?php echo admin_url('admin.php?page=contact-submissions'); ?>" class="button">✕ Clear</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Table -->
        <table class="wp-list-table widefat fixed striped" style="border-radius:8px;overflow:hidden;">
            <thead>
                <tr>
                    <th style="width:50px;">#</th>
                    <th>Name / Company</th>
                    <th>Email / Phone</th>
                    <th>Industry / Org / Country</th>
                    <th>Job / Interested Products</th>
                    <th>Message</th>
                    <th>Attachment</th>
                    <th style="width:80px;">Status</th>
                    <th style="width:130px;">Date</th>
                    <th style="width:100px;">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($rows) : ?>
                <?php foreach ($rows as $row) :
                    $is_new = ($row['status'] === 'new');
                    $row_style = $is_new ? 'font-weight:600;background:#fffbeb;' : '';
                    $del_url  = wp_nonce_url(admin_url('admin.php?page=contact-submissions&action=delete&sub_id=' . $row['id']), 'delete_sub_' . $row['id']);
                    $read_url = wp_nonce_url(admin_url('admin.php?page=contact-submissions&action=mark_read&sub_id=' . $row['id']), 'mark_read_' . $row['id']);
                ?>
                <tr style="<?php echo $row_style; ?>">
                    <td><?php echo esc_html($row['id']); ?></td>
                    <td>
                        <strong><?php echo esc_html($row['name']); ?></strong>
                        <?php if ($row['company']) : ?>
                            <br><span style="color:#64748b;font-size:12px;"><?php echo esc_html($row['company']); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="mailto:<?php echo esc_attr($row['email']); ?>"><?php echo esc_html($row['email']); ?></a>
                        <?php if ($row['phone']) : ?>
                            <br><span style="font-size:12px;color:#64748b;"><?php echo esc_html($row['phone']); ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;line-height:1.45;">
                        <strong><?php echo esc_html(($row['industry'] ?? '') ?: '-'); ?></strong>
                        <br><span style="color:#64748b;">Org: <?php echo esc_html(($row['organization_type'] ?? '') ?: '-'); ?></span>
                        <br><span style="color:#64748b;">Country: <?php echo esc_html(($row['country'] ?? '') ?: '-'); ?></span>
                    </td>
                    <td style="font-size:12px;line-height:1.45;">
                        <strong><?php echo esc_html(($row['job_title'] ?? '') ?: '-'); ?></strong>
                        <br><span style="color:#64748b;"><?php echo esc_html(($row['interested_products'] ?? '') ?: '-'); ?></span>
                    </td>
                    <td style="font-size:13px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo esc_attr($row['message']); ?>">
                        <?php echo esc_html($row['message']); ?>
                    </td>
                    <td style="font-size:12px;">
                        <?php if (!empty($row['file_url'])) : ?>
                            <a href="<?php echo esc_url($row['file_url']); ?>" target="_blank" rel="noopener">Download</a>
                        <?php else : ?>
                            <span style="color:#94a3b8;">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($is_new) : ?>
                            <span style="background:#dc2626;color:#fff;font-size:11px;padding:2px 8px;border-radius:999px;">New</span>
                        <?php else : ?>
                            <span style="background:#e2e8f0;color:#475569;font-size:11px;padding:2px 8px;border-radius:999px;">Read</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;"><?php echo esc_html(date('d/m/Y H:i', strtotime($row['created_at']))); ?></td>
                    <td style="white-space:nowrap;">
                        <?php if ($is_new) : ?>
                            <a href="<?php echo esc_url($read_url); ?>" style="color:var(--theme-primary);text-decoration:none;font-size:12px;">✓ Mark as read</a><br>
                        <?php endif; ?>
                        <a href="<?php echo esc_url($del_url); ?>" onclick="return confirm('Confirm deleting this submission?');" style="color:#dc2626;text-decoration:none;font-size:12px;">🗑️ Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="10" style="text-align:center;padding:40px;color:#94a3b8;">
                        <?php echo ($search || $filter_status) ? 'No matching submissions found.' : 'No contact submissions yet.'; ?>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_pages > 1) : ?>
        <div class="tablenav" style="margin-top:16px;">
            <div class="tablenav-pages">
                <span class="displaying-num"><?php echo number_format($total); ?> items</span>
                <?php
                echo paginate_links([
                    'base'      => add_query_arg('paged', '%#%'),
                    'format'    => '',
                    'current'   => $current_page,
                    'total'     => $total_pages,
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                ]);
                ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php
}

// ============================================
// PRODUCT DATASHEET PDF DOWNLOAD
// ============================================

/**
 * Register query var for datasheet download
 */
add_filter('query_vars', function($vars) {
    $vars[] = 'get_datasheet';
    return $vars;
});

/**
 * Intercept request and serve PDF
 */
add_action('template_redirect', function() {
    if (!get_query_var('get_datasheet') || !is_singular('product')) return;
    my_theme_serve_datasheet_pdf(get_queried_object_id());
    exit;
});

/**
 * Generate and output product datasheet PDF — World-Class Format (2-page)
 *
 * Page 1: Header + Photo + Description + Electrical Specs (5-col table)
 * Page 2: Mechanical/Construction + Schematic + Performance Curves + Compliance Badges + Storage
 */
function my_theme_serve_datasheet_pdf($product_id) {
    $post = get_post($product_id);
    if (!$post) { wp_die('Product not found.'); }

    /* ───────────────────── GATHER ALL DATA ───────────────────── */
    $title     = get_the_title($product_id);
    $subtitle  = get_post_meta($product_id, 'pd_subtitle', true) ?: get_the_excerpt($post);
    $sku       = get_post_meta($product_id, 'pd_sku', true);
    $long_desc = get_post_meta($product_id, 'pd_long_description', true) ?: $subtitle;
    $status    = get_post_meta($product_id, 'pd_status', true) ?: 'Active';

    // Features & Applications
    $features_raw = get_post_meta($product_id, 'pd_features', true);
    $features = $features_raw ? array_filter(array_map('trim', explode("\n", $features_raw))) : [];
    $apps_raw = get_post_meta($product_id, 'pd_applications', true);
    $applications = $apps_raw ? array_filter(array_map('trim', explode("\n", $apps_raw))) : [];

    // Electrical Specifications – 5-column mapping
    $spec_map = [
        'pd_inductance'     => ['name'=>'Inductance',                'sym'=>'L',   'unit'=>'',   'cond'=>''],
        'pd_current_rating' => ['name'=>'Rated Current',             'sym'=>'Ir',  'unit'=>'',   'cond'=>'dT=40C'],
        'pd_impedance'      => ['name'=>'Impedance',                 'sym'=>'Z',   'unit'=>'Ohm','cond'=>'@ 100MHz'],
        'pd_voltage'        => ['name'=>'Voltage Rating',            'sym'=>'V',   'unit'=>'',   'cond'=>''],
        'pd_frequency'      => ['name'=>'Frequency Range',           'sym'=>'f',   'unit'=>'',   'cond'=>''],
        'pd_dcr'            => ['name'=>'DC Resistance',             'sym'=>'Rdc', 'unit'=>'Ohm','cond'=>'Max @ 25C'],
        'pd_insulation'     => ['name'=>'Insulation Resistance',     'sym'=>'Riso','unit'=>'',   'cond'=>''],
        'pd_hipot'          => ['name'=>'Hi-Pot (Dielectric)',       'sym'=>'Viso','unit'=>'',   'cond'=>'60s'],
        'pd_turns_ratio'    => ['name'=>'Turns Ratio',               'sym'=>'N',   'unit'=>'',   'cond'=>''],
        'pd_power_rating'   => ['name'=>'Power Rating',              'sym'=>'P',   'unit'=>'W',  'cond'=>''],
        'pd_temp_range'     => ['name'=>'Operating Temperature',     'sym'=>'Top', 'unit'=>'C',  'cond'=>''],
    ];
    $specs = [];
    foreach ($spec_map as $key => $info) {
        $val = get_post_meta($product_id, $key, true);
        if ($val) {
            $unit = $info['unit'];
            if ($unit && stripos((string) $val, $unit) !== false) {
                $unit = '';
            }
            if ($unit === 'C' && preg_match('/(deg\s*C|°\s*C|\bC\b)/i', (string) $val)) {
                $unit = '';
            }
            $specs[] = ['name'=>$info['name'], 'sym'=>$info['sym'], 'val'=>$val, 'unit'=>$unit, 'cond'=>$info['cond']];
        }
    }

    // Mechanical / Construction
    $mech_fields = [
        'pd_dimensions'   =>'Dimensions (L x W x H)',
        'pd_weight'       =>'Weight',
        'pd_pin_config'   =>'Pin Configuration',
        'pd_mount_type'   =>'Mounting Type',
        'pd_core_material'=>'Core Material',
        'pd_wire_type'    =>'Wire Type / Gauge',
        'pd_land_pattern' =>'Recommended Land Pattern',
    ];
    $mech = [];

    // Schematic / Winding info
    $schematic_info = get_post_meta($product_id, 'pd_schematic_info', true);

    // Performance notes
    $perf_raw = get_post_meta($product_id, 'pd_performance_notes', true);
    $perf_notes = $perf_raw ? array_filter(array_map('trim', explode("\n", $perf_raw))) : [];

    // Compliance & Packaging
    $comp_fields = [
        'pd_standards'    =>'Quality Standards',
        'pd_compliance'   =>'Environmental Compliance',
        'pd_safety_certs' =>'Safety Certifications',
        'pd_package_type' =>'Packaging Type',
        'pd_packing_qty'  =>'Packing Quantity',
        'pd_size_range'   =>'Size Range / Capability',
        'pd_output_range' =>'Output Range',
    ];
    $compliance = [];

    // Storage
    $storage      = get_post_meta($product_id, 'pd_storage_conditions', true);
    $msl          = get_post_meta($product_id, 'pd_msl', true);

    // Category
    $terms = wp_get_object_terms($product_id, 'product_category');
    $category_name = (!empty($terms) && !is_wp_error($terms)) ? $terms[0]->name : '';

    // Core specs used for real calculations / diagrams
    $winding_raw   = get_post_meta($product_id, 'pd_winding', true);
    $core_shape_raw= get_post_meta($product_id, 'pd_core_shape', true);
    $core_size_raw = get_post_meta($product_id, 'pd_core_size', true);
    $wire_type_raw = get_post_meta($product_id, 'pd_wire_type', true);

    // Raw values for graph calculations
    $impedance_raw = (string) get_post_meta($product_id, 'pd_impedance', true);
    $frequency_raw = (string) get_post_meta($product_id, 'pd_frequency', true);
    $current_raw   = (string) get_post_meta($product_id, 'pd_current_rating', true);
    $temp_raw      = (string) get_post_meta($product_id, 'pd_temp_range', true);

    // Company
    $company      = get_option('blogname', 'KV Electronics');
    $site_url     = home_url('/');
    $site_email   = get_option('site_email', get_option('admin_email', ''));
    $site_phone   = get_option('site_phone', '');
    $site_address = get_option('site_address', '');

    /* ── Product Image (JPEG) ── */
    $imgData = null; $imgW = 0; $imgH = 0;
    $gallery = get_post_meta($product_id, 'pd_gallery', true);
    if ($gallery) {
        $imgs = is_array($gallery) ? $gallery : json_decode($gallery, true);
        if (!empty($imgs[0])) {
            $img_url = $imgs[0];
            $upload  = wp_upload_dir();
            $img_path = str_replace($upload['baseurl'], $upload['basedir'], $img_url);
            if (file_exists($img_path) && function_exists('getimagesize')) {
                $info = @getimagesize($img_path);
                if ($info && $info[2] === IMAGETYPE_JPEG) {
                    $imgData = file_get_contents($img_path);
                    $imgW = $info[0];
                    $imgH = $info[1];
                }
            }
        }
        $primary_sync = sanitize_hex_color($_POST['theme_primary_color'] ?? get_option('theme_primary_color', '#0056d6')) ?: '#0056d6';
        update_option('banner_bg_color', $primary_sync);
        set_theme_mod('banner_bg_color', $primary_sync);
    }

    /* ── Helpers ── */
    $clean = function($s) {
        $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $s = str_replace(["\xC2\xB0","\xE2\x80\x99"], ['deg',"'"], $s);
        $s = preg_replace('/[\x{2018}\x{2019}]/u', "'", $s);
        $s = preg_replace('/[\x{201C}\x{201D}]/u', '"', $s);
        $s = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/', '', $s);
        return trim($s);
    };
    $e = function($s) { return str_replace(['\\','(',')'], ['\\\\','\\(','\\)'], $s); };
    $wrap = function($text, $max = 85) {
        $text = trim($text);
        if (strlen($text) <= $max) return [$text];
        $lines = [];
        while (strlen($text) > $max) {
            $p = strrpos(substr($text, 0, $max), ' ');
            if ($p === false) $p = $max;
            $lines[] = substr($text, 0, $p);
            $text = ltrim(substr($text, $p));
        }
        if ($text !== '') $lines[] = $text;
        return $lines;
    };

    $normalize_cell_text = function($text) use ($clean) {
        $text = (string) $text;
        if ($text === '') return '';

        $text = str_replace(["\0", "\r\n", "\r"], ["\n", "\n", "\n"], $text);
        $text = preg_replace('/[•·●▪◦]+/u', "\n", $text);
        $text = preg_replace('/\s*\n\s*/', "\n", $text);

        $parts = array_values(array_filter(array_map('trim', explode("\n", $text)), function($line) {
            return $line !== '';
        }));

        if (empty($parts)) return $clean($text);
        if (count($parts) === 1) return $clean($parts[0]);
        return implode(' | ', array_map($clean, $parts));
    };

    foreach ($mech_fields as $k => $l) {
        $v = get_post_meta($product_id, $k, true);
        if ($v) $mech[] = ['label'=>$l, 'value'=>$normalize_cell_text($v)];
    }

    foreach ($comp_fields as $k => $l) {
        $v = get_post_meta($product_id, $k, true);
        if ($v) $compliance[] = ['label'=>$l, 'value'=>$normalize_cell_text($v)];
    }

    $parse_number = function($text) {
        if (!is_string($text) || $text === '') return null;
        if (preg_match('/-?\d+(?:\.\d+)?/', str_replace(',', '', $text), $m)) {
            return (float) $m[0];
        }
        return null;
    };

    $parse_eng_value = function($text, $default_unit = '') use ($parse_number) {
        $num = $parse_number($text);
        if ($num === null) return null;
        $unit = $default_unit;
        if (preg_match('/\b(k|m|g)?\s*(hz|ohm|a|v|w)\b/i', $text, $m)) {
            $prefix = strtolower($m[1] ?? '');
            $base_u = strtolower($m[2] ?? $default_unit);
            $mult = 1.0;
            if ($prefix === 'k') $mult = 1e3;
            if ($prefix === 'm') $mult = 1e6;
            if ($prefix === 'g') $mult = 1e9;
            $num *= $mult;
            $unit = $base_u;
        }
        return ['value' => $num, 'unit' => $unit];
    };

    $parse_range = function($text, $unit = '') use ($parse_eng_value, $parse_number) {
        if (!is_string($text) || trim($text) === '') return null;
        $matches = [];
        preg_match_all('/-?\d+(?:\.\d+)?\s*(?:[kmg]?\s*(?:hz|ohm|a|v|w|c))?/i', $text, $matches);
        $parts = array_values(array_filter(array_map('trim', $matches[0] ?? []), function($x){ return $x !== ''; }));
        if (count($parts) >= 2) {
            $a = $parse_eng_value($parts[0], $unit);
            $b = $parse_eng_value($parts[1], $unit);
            if ($a && $b) {
                return [
                    'min' => min($a['value'], $b['value']),
                    'max' => max($a['value'], $b['value']),
                    'unit' => $a['unit'] ?: $b['unit'] ?: $unit,
                ];
            }
        }
        $single = $parse_number($text);
        if ($single !== null) {
            return ['min' => $single, 'max' => $single, 'unit' => $unit];
        }
        return null;
    };

    $fmt_freq = function($hz) {
        if ($hz >= 1e9) return rtrim(rtrim(number_format($hz / 1e9, 2, '.', ''), '0'), '.') . 'GHz';
        if ($hz >= 1e6) return rtrim(rtrim(number_format($hz / 1e6, 2, '.', ''), '0'), '.') . 'MHz';
        if ($hz >= 1e3) return rtrim(rtrim(number_format($hz / 1e3, 2, '.', ''), '0'), '.') . 'kHz';
        return rtrim(rtrim(number_format($hz, 2, '.', ''), '0'), '.') . 'Hz';
    };

    // Build real graph inputs from DB
    $freq_range = $parse_range($frequency_raw, 'hz');
    $f_min = ($freq_range && $freq_range['min'] > 0) ? $freq_range['min'] : 1e3;
    $f_max = ($freq_range && $freq_range['max'] > $f_min) ? $freq_range['max'] : 1e9;

    $imp_val = $parse_eng_value($impedance_raw, 'ohm');
    $z_ref = ($imp_val && $imp_val['value'] > 0) ? $imp_val['value'] : 100.0;
    $f_ref = 1e6;
    if (preg_match('/@\s*(-?\d+(?:\.\d+)?)\s*([kmg]?\s*hz)/i', $impedance_raw, $m)) {
        $p = $parse_eng_value(trim($m[1] . ' ' . $m[2]), 'hz');
        if ($p && $p['value'] > 0) $f_ref = $p['value'];
    } else {
        $f_ref = sqrt($f_min * $f_max);
    }

    $current_val = $parse_eng_value($current_raw, 'a');
    $i_rated = ($current_val && $current_val['value'] > 0) ? $current_val['value'] : 1.0;
    $i_unit  = ($current_val && !empty($current_val['unit'])) ? strtoupper($current_val['unit']) : 'A';

    $temp_range = $parse_range($temp_raw, 'c');
    $t_min = $temp_range ? $temp_range['min'] : 25.0;
    $t_max = $temp_range ? $temp_range['max'] : 155.0;
    if ($t_max <= $t_min) $t_max = $t_min + 130;
    $t_mid = ($t_min + $t_max) / 2;

    // Dynamic compliance badges from DB values
    $badge_pool = [];
    foreach (['pd_standards', 'pd_compliance', 'pd_safety_certs'] as $bk) {
        $v = (string) get_post_meta($product_id, $bk, true);
        if ($v === '') continue;
        $tokens = preg_split('/[,\|\/;\n]+/', $v);
        foreach ((array) $tokens as $tk) {
            $tk = strtoupper(trim($tk));
            if ($tk === '' || strlen($tk) > 18) continue;
            $badge_pool[$tk] = true;
        }
    }
    $compliance_badges = array_slice(array_keys($badge_pool), 0, 6);
    if (!$compliance_badges) {
        $compliance_badges = ['ROHS', 'REACH'];
    }

    $filename = sanitize_title($title) . '-datasheet.pdf';
    $pageW = 595; $pageH = 842;

    /* ═══════════════════════════════════════════════════════════
       PAGE 1 — Header, Photo, Description, Electrical Specs
       ═══════════════════════════════════════════════════════════ */
    $p1 = '';

    // ── Blue Header Bar (70pt) ──
    $barH = 70; $barBot = $pageH - $barH;
    $p1 .= "0.000 0.337 0.839 rg\n0 {$barBot} {$pageW} {$barH} re f\n";
    $p1 .= "BT /F2 9 Tf 1 1 1 rg 50 " . ($barBot + 56) . " Td ({$e($clean($company))}) Tj ET\n";
    $p1 .= "BT /F2 9 Tf 1 1 1 rg 480 " . ($barBot + 56) . " Td (DATASHEET) Tj ET\n";
    // Title
    $tLen = strlen($clean($title));
    $tSz = $tLen > 30 ? 16 : 20;
    $p1 .= "BT /F2 {$tSz} Tf 1 1 1 rg 50 " . ($barBot + 28) . " Td ({$e($clean($title))}) Tj ET\n";
    // Subtitle in bar
    if ($subtitle) {
        $sub = substr($clean($subtitle), 0, 90);
        $p1 .= "BT /F1 8 Tf 0.85 0.90 1.0 rg 50 " . ($barBot + 12) . " Td ({$e($sub)}) Tj ET\n";
    }

    $y = $barBot - 14;

    // ── Status / Part Number / Category row ──
    $meta = [];
    if ($status)        $meta[] = "Status: {$clean($status)}";
    if ($sku)           $meta[] = "P/N: {$clean($sku)}";
    if ($category_name) $meta[] = "Category: {$clean($category_name)}";
    if ($meta) {
        // Small blue-gray badge row
        $p1 .= "0.941 0.961 0.992 rg 44 " . ($y - 6) . " 507 22 re f\n";
        $p1 .= "0.118 0.161 0.239 rg\n";
        $p1 .= "BT /F2 9 Tf 50 {$y} Td ({$e(implode('   |   ', $meta))}) Tj ET\n";
        $y -= 28;
    }

    // ── Product Photo (right) + Description (left) ──
    $descX = 50; $descW = 88;
    $imgPdfW = 0; $imgPdfH = 0;
    if ($imgData) {
        $imgPdfW = 150; $imgPdfH = intval(150 * $imgH / $imgW);
        if ($imgPdfH > 130) { $imgPdfH = 130; $imgPdfW = intval(130 * $imgW / $imgH); }
        $imgX = $pageW - 50 - $imgPdfW;
        $imgY = $y - $imgPdfH - 4;
        // Draw image (reference /Img1)
        $p1 .= "q {$imgPdfW} 0 0 {$imgPdfH} {$imgX} {$imgY} cm /Img1 Do Q\n";
        // Gray border around image
        $p1 .= "0.875 0.875 0.875 RG 0.5 w {$imgX} {$imgY} {$imgPdfW} {$imgPdfH} re S\n";
        $descW = 54; // narrower description
    }

    // Description
    if ($long_desc) {
        $p1 .= "0.118 0.161 0.239 rg\nBT /F2 12 Tf 50 {$y} Td (Description) Tj ET\n";
        $y -= 4;
        $p1 .= "0.000 0.337 0.839 rg 50 {$y} 80 2 re f\n";
        $y -= 14;
        $dLines = $wrap($clean($long_desc), $descW);
        $p1 .= "0.282 0.337 0.416 rg\nBT /F1 9.5 Tf 50 {$y} Td\n";
        foreach ($dLines as $dl) { $p1 .= "({$e($dl)}) Tj 0 -13 Td\n"; }
        $p1 .= "ET\n";
        $y -= count($dLines) * 13 + 6;
    }

    // Adjust y if image extends below description
    if ($imgData) {
        $imgBottom = ($barBot - 14 - 28) - $imgPdfH - 4;
        if ($imgBottom < $y) $y = $imgBottom - 6;
    }

    // ── Features & Applications (side by side) ──
    if ($features || $applications) {
        $p1 .= "0.894 0.906 0.918 rg 50 {$y} 495 1 re f\n";
        $y -= 16;
        $fY = $y; $aY = $y;

        if ($features) {
            $p1 .= "0.118 0.161 0.239 rg\nBT /F2 11 Tf 50 {$fY} Td (Key Features) Tj ET\n";
            $fY -= 4; $p1 .= "0.000 0.337 0.839 rg 50 {$fY} 85 2 re f\n"; $fY -= 14;
            foreach ($features as $f) {
                $f = ltrim($f, '- ');
                $fl = $wrap($clean($f), 40);
                $p1 .= "0.282 0.337 0.416 rg\nBT /F1 9 Tf 50 {$fY} Td\n";
                $first = true;
                foreach ($fl as $ln) {
                    $px = $first ? '- ' : '  ';
                    $p1 .= "({$e($px.$ln)}) Tj 0 -12 Td\n"; $first = false;
                }
                $p1 .= "ET\n"; $fY -= count($fl) * 12 + 1;
            }
        }
        if ($applications) {
            $p1 .= "0.118 0.161 0.239 rg\nBT /F2 11 Tf 310 {$aY} Td (Applications) Tj ET\n";
            $aY -= 4; $p1 .= "0.000 0.337 0.839 rg 310 {$aY} 85 2 re f\n"; $aY -= 14;
            foreach ($applications as $a) {
                $a = ltrim($a, '- ');
                $al = $wrap($clean($a), 40);
                $p1 .= "0.282 0.337 0.416 rg\nBT /F1 9 Tf 310 {$aY} Td\n";
                $first = true;
                foreach ($al as $ln) {
                    $px = $first ? '- ' : '  ';
                    $p1 .= "({$e($px.$ln)}) Tj 0 -12 Td\n"; $first = false;
                }
                $p1 .= "ET\n"; $aY -= count($al) * 12 + 1;
            }
        }
        $y = min($fY, $aY) - 4;
    }

    // ── Electrical Specifications (5-column professional table) ──
    if ($specs) {
        $p1 .= "0.894 0.906 0.918 rg 50 {$y} 495 1 re f\n";
        $y -= 16;
        $p1 .= "0.118 0.161 0.239 rg\nBT /F2 12 Tf 50 {$y} Td (Electrical Specifications) Tj ET\n";
        $y -= 4; $p1 .= "0.000 0.337 0.839 rg 50 {$y} 170 2 re f\n"; $y -= 14;

        // 5-col header: Parameter | Symbol | Value | Unit | Test Condition
        $cX = [55, 195, 265, 410, 460]; // column x positions
        $p1 .= "0.000 0.145 0.369 rg 50 " . ($y - 4) . " 495 20 re f\n"; // dark blue header
        $p1 .= "1 1 1 rg\n";
        $p1 .= "BT /F2 8.5 Tf {$cX[0]} {$y} Td (Parameter) Tj ET\n";
        $p1 .= "BT /F2 8.5 Tf {$cX[1]} {$y} Td (Symbol) Tj ET\n";
        $p1 .= "BT /F2 8.5 Tf {$cX[2]} {$y} Td (Value) Tj ET\n";
        $p1 .= "BT /F2 8.5 Tf {$cX[3]} {$y} Td (Unit) Tj ET\n";
        $p1 .= "BT /F2 8.5 Tf {$cX[4]} {$y} Td (Test Cond.) Tj ET\n";
        $y -= 22;

        foreach ($specs as $i => $sp) {
            if ($y < 78) break;
            // alternating row bg
            $bg = ($i % 2 === 0) ? "0.961 0.969 0.980 rg" : "1 1 1 rg";
            $p1 .= "{$bg} 50 " . ($y - 4) . " 495 19 re f\n";
            // vertical grid lines
            $p1 .= "0.875 0.886 0.898 RG 0.3 w\n";
            foreach ([190, 260, 405, 455] as $lx) {
                $p1 .= "{$lx} " . ($y - 4) . " m {$lx} " . ($y + 15) . " l S\n";
            }
            $p1 .= "0.282 0.337 0.416 rg\nBT /F1 8.5 Tf {$cX[0]} {$y} Td ({$e($clean($sp['name']))}) Tj ET\n";
            $p1 .= "0.000 0.337 0.839 rg\nBT /F2 8.5 Tf {$cX[1]} {$y} Td ({$e($clean($sp['sym']))}) Tj ET\n";
            $p1 .= "0.118 0.161 0.239 rg\nBT /F2 9 Tf {$cX[2]} {$y} Td ({$e($clean($sp['val']))}) Tj ET\n";
            $p1 .= "0.392 0.455 0.545 rg\nBT /F1 8.5 Tf {$cX[3]} {$y} Td ({$e($clean($sp['unit']))}) Tj ET\n";
            $p1 .= "0.392 0.455 0.545 rg\nBT /F1 8 Tf {$cX[4]} {$y} Td ({$e($clean($sp['cond']))}) Tj ET\n";
            $y -= 19;
        }
        // Bottom border of table
        $p1 .= "0.875 0.886 0.898 RG 0.5 w 50 " . ($y + 15) . " m 545 " . ($y + 15) . " l S\n";
        $y -= 8;
    }

    // ── Page 1 Footer ──
    $p1 .= my_ds_footer($e, $clean, $company, $site_url, $site_email, $site_phone, $site_address, 1, 2);

    /* ═══════════════════════════════════════════════════════════
       PAGE 2 — Mechanical, Schematic, Performance, Compliance
       ═══════════════════════════════════════════════════════════ */
    $p2 = '';

    // ── Page 2 Header Bar (same as page 1) ──
    $bar2H = 70; $bar2Bot = $pageH - $bar2H;
    $p2 .= "0.000 0.337 0.839 rg\n0 {$bar2Bot} {$pageW} {$bar2H} re f\n";
    $p2 .= "BT /F2 9 Tf 1 1 1 rg 50 " . ($bar2Bot + 56) . " Td ({$e($clean($company))}) Tj ET\n";
    $p2 .= "BT /F2 9 Tf 1 1 1 rg 480 " . ($bar2Bot + 56) . " Td (DATASHEET) Tj ET\n";
    $t2Sz = strlen($clean($title)) > 30 ? 16 : 20;
    $p2 .= "BT /F2 {$t2Sz} Tf 1 1 1 rg 50 " . ($bar2Bot + 28) . " Td ({$e($clean($title))}) Tj ET\n";
    // Subtitle line — "Technical Data" + P/N
    $p2Sub = 'Technical Data';
    if ($sku) $p2Sub .= '   |   P/N: ' . $clean($sku);
    $p2 .= "BT /F1 8 Tf 0.85 0.90 1.0 rg 50 " . ($bar2Bot + 12) . " Td ({$e($p2Sub)}) Tj ET\n";
    $y2 = $bar2Bot - 16;

    // ── Mechanical & Construction Table ──
    if ($mech) {
        $p2 .= "0.118 0.161 0.239 rg\nBT /F2 12 Tf 50 {$y2} Td (Mechanical & Construction) Tj ET\n";
        $y2 -= 4; $p2 .= "0.000 0.337 0.839 rg 50 {$y2} 180 2 re f\n"; $y2 -= 14;

        // table header
        $p2 .= "0.000 0.145 0.369 rg 50 " . ($y2 - 4) . " 495 20 re f\n";
        $p2 .= "1 1 1 rg\nBT /F2 9 Tf 55 {$y2} Td (Property) Tj ET\n";
        $p2 .= "BT /F2 9 Tf 310 {$y2} Td (Specification) Tj ET\n";
        $y2 -= 22;
        foreach ($mech as $i => $m) {
            $label_lines = $wrap($clean($m['label']), 42);
            $value_lines = $wrap($clean($m['value']), 52);
            $line_count  = max(count($label_lines), count($value_lines));
            $row_h       = max(19, ($line_count * 11) + 7);
            $text_y      = $y2 + $row_h - 15;

            $bg = ($i % 2 === 0) ? "0.961 0.969 0.980 rg" : "1 1 1 rg";
            $p2 .= "{$bg} 50 " . ($y2 - 4) . " 495 {$row_h} re f\n";
            $p2 .= "0.875 0.886 0.898 RG 0.3 w 305 " . ($y2 - 4) . " m 305 " . ($y2 - 4 + $row_h) . " l S\n";

            $p2 .= "0.282 0.337 0.416 rg\nBT /F1 9 Tf 55 {$text_y} Td\n";
            foreach ($label_lines as $line) {
                $p2 .= "({$e($line)}) Tj 0 -11 Td\n";
            }
            $p2 .= "ET\n";

            $p2 .= "0.118 0.161 0.239 rg\nBT /F2 9 Tf 310 {$text_y} Td\n";
            foreach ($value_lines as $line) {
                $p2 .= "({$e($line)}) Tj 0 -11 Td\n";
            }
            $p2 .= "ET\n";

            $y2 -= $row_h;
        }
        $p2 .= "0.875 0.886 0.898 RG 0.5 w 50 " . ($y2 + 15) . " m 545 " . ($y2 + 15) . " l S\n";
        $y2 -= 12;
    }

    // ── Schematic / Internal Winding Diagram ──
    $p2 .= "0.894 0.906 0.918 rg 50 {$y2} 495 1 re f\n"; $y2 -= 16;
    $p2 .= "0.118 0.161 0.239 rg\nBT /F2 12 Tf 50 {$y2} Td (Schematic Diagram) Tj ET\n";
    $y2 -= 4; $p2 .= "0.000 0.337 0.839 rg 50 {$y2} 140 2 re f\n"; $y2 -= 10;

    // Draw Transformer schematic symbol (left side)
    $schemX = 90; $schemY = $y2 - 80;
    // Dashed box
    $p2 .= "0.875 0.886 0.898 RG 0.5 w [3 3] 0 d 55 {$schemY} 200 100 re S [] 0 d\n";
    // Left coil (primary) - zigzag
    $coilX = $schemX + 30; $coilTop = $schemY + 80; $coilBot = $schemY + 20;
    $p2 .= "0.000 0.337 0.839 RG 1.5 w\n";
    $segments = 6; $segH = ($coilTop - $coilBot) / $segments;
    $p2 .= "{$coilX} {$coilTop} m\n";
    for ($si = 0; $si < $segments; $si++) {
        $ty = $coilTop - ($si + 1) * $segH;
        $cx = ($si % 2 === 0) ? $coilX - 12 : $coilX + 12;
        $p2 .= "{$cx} " . ($ty + $segH * 0.5) . " {$coilX} {$ty} v\n";
    }
    $p2 .= "S\n";
    // Right coil (secondary)
    $coilX2 = $schemX + 80;
    $p2 .= "0.839 0.145 0.000 RG 1.5 w\n";
    $p2 .= "{$coilX2} {$coilTop} m\n";
    for ($si = 0; $si < $segments; $si++) {
        $ty = $coilTop - ($si + 1) * $segH;
        $cx = ($si % 2 === 0) ? $coilX2 + 12 : $coilX2 - 12;
        $p2 .= "{$cx} " . ($ty + $segH * 0.5) . " {$coilX2} {$ty} v\n";
    }
    $p2 .= "S\n";
    // Core lines (two vertical parallel lines between coils)
    $coreX1 = $schemX + 50; $coreX2 = $schemX + 58;
    $p2 .= "0.282 0.337 0.416 RG 2 w\n";
    $p2 .= "{$coreX1} " . ($coilTop + 5) . " m {$coreX1} " . ($coilBot - 5) . " l S\n";
    $p2 .= "{$coreX2} " . ($coilTop + 5) . " m {$coreX2} " . ($coilBot - 5) . " l S\n";
    // Dot convention dots
    $p2 .= "0.000 0.337 0.839 rg\n" . ($coilX - 8) . " " . ($coilTop - 4) . " 3 3 re f\n";
    $p2 .= "0.839 0.145 0.000 rg\n" . ($coilX2 + 6) . " " . ($coilTop - 4) . " 3 3 re f\n";
    // Pin labels
    $p2 .= "0.118 0.161 0.239 rg\n";
    $p2 .= "BT /F2 8 Tf " . ($coilX - 30) . " " . ($coilTop - 2) . " Td (Pin 1) Tj ET\n";
    $p2 .= "BT /F2 8 Tf " . ($coilX - 30) . " " . ($coilBot - 2) . " Td (Pin 2) Tj ET\n";
    $p2 .= "BT /F2 8 Tf " . ($coilX2 + 16) . " " . ($coilTop - 2) . " Td (Pin 3) Tj ET\n";
    $p2 .= "BT /F2 8 Tf " . ($coilX2 + 16) . " " . ($coilBot - 2) . " Td (Pin 4) Tj ET\n";
    // Labels
    $p2 .= "BT /F1 7 Tf 0.282 0.337 0.416 rg " . ($coilX - 10) . " " . ($schemY + 8) . " Td (Primary) Tj ET\n";
    $p2 .= "BT /F1 7 Tf 0.282 0.337 0.416 rg " . ($coilX2 - 10) . " " . ($schemY + 8) . " Td (Secondary) Tj ET\n";

    // Schematic info text (right side)
    if ($schematic_info) {
        $siLines = array_filter(array_map('trim', explode("\n", $schematic_info)));
        $siY = $y2 - 6;
        $p2 .= "0.282 0.337 0.416 rg\nBT /F1 8.5 Tf 270 {$siY} Td\n";
        foreach ($siLines as $sl) {
            $p2 .= "({$e($clean($sl))}) Tj 0 -12 Td\n";
        }
        $p2 .= "ET\n";
    } else {
        // DB-derived schematic text — only show data that actually exists
        $siRows = [];

        // Pin configuration from DB
        $pin_config_raw = get_post_meta($product_id, 'pd_pin_config', true);
        if ($pin_config_raw) {
            $pin_config_raw = str_replace(["\0", "\r\n", "\r"], ["\n", "\n", "\n"], (string) $pin_config_raw);
            $pin_lines = array_filter(array_map('trim', explode("\n", $pin_config_raw)));
            foreach ($pin_lines as $pl) {
                $siRows[] = $clean($pl);
            }
        }

        if ($winding_raw) {
            $siRows[] = 'Winding: ' . $clean($winding_raw);
        }
        $core_line = trim(($core_shape_raw ? $clean($core_shape_raw) : '') . ($core_size_raw ? (' / ' . $clean($core_size_raw)) : ''));
        if ($core_line !== '') {
            $siRows[] = 'Core: ' . $core_line;
        }
        if ($wire_type_raw) {
            $siRows[] = 'Wire: ' . $normalize_cell_text($wire_type_raw);
        }

        // Turns ratio
        $turns_ratio_raw = get_post_meta($product_id, 'pd_turns_ratio', true);
        if ($turns_ratio_raw) {
            $siRows[] = 'Turns Ratio: ' . $clean($turns_ratio_raw);
        }

        // If no data at all, show a generic note
        if (empty($siRows)) {
            $siRows[] = 'See product documentation for details.';
        }

        $p2 .= "0.282 0.337 0.416 rg\nBT /F1 8.5 Tf 270 " . ($y2 - 6) . " Td\n";
        foreach ($siRows as $rowText) {
            $p2 .= "(" . $e($rowText) . ") Tj 0 -13 Td\n";
        }
        $p2 .= "ET\n";
    }
    $y2 = $schemY - 12;

    // ── Typical Performance Curves ──
    $p2 .= "0.894 0.906 0.918 rg 50 {$y2} 495 1 re f\n"; $y2 -= 16;
    $p2 .= "0.118 0.161 0.239 rg\nBT /F2 12 Tf 50 {$y2} Td (Typical Performance Characteristics) Tj ET\n";
    $y2 -= 4; $p2 .= "0.000 0.337 0.839 rg 50 {$y2} 220 2 re f\n"; $y2 -= 10;

    // Graph 1: Impedance vs Frequency (left)
    $gX1 = 60; $gY1 = $y2 - 120; $gW = 200; $gH = 110;
    // Graph border
    $p2 .= "0.875 0.886 0.898 RG 0.5 w {$gX1} {$gY1} {$gW} {$gH} re S\n";
    // Grid lines (horizontal)
    $p2 .= "0.941 0.949 0.961 RG 0.3 w\n";
    for ($gi = 1; $gi <= 4; $gi++) {
        $gy = $gY1 + $gi * ($gH / 5);
        $p2 .= "{$gX1} {$gy} m " . ($gX1 + $gW) . " {$gy} l S\n";
    }
    // Grid lines (vertical)
    for ($gi = 1; $gi <= 4; $gi++) {
        $gx = $gX1 + $gi * ($gW / 5);
        $p2 .= "{$gx} {$gY1} m {$gx} " . ($gY1 + $gH) . " l S\n";
    }
    // Impedance curve (calculated from DB: Z_ref @ f_ref, projected over frequency range)
    $imp_points = [];
    $npts = 18;
    $log_min = log10($f_min);
    $log_max = log10($f_max);
    $log_ref = log10(max($f_ref, 1));
    for ($pi = 0; $pi < $npts; $pi++) {
        $ratio = $pi / ($npts - 1);
        $lf = $log_min + (($log_max - $log_min) * $ratio);
        $freq = pow(10, $lf);
        $z = $z_ref * pow(max($freq, 1) / max($f_ref, 1), 0.55);
        $imp_points[] = ['f' => $freq, 'z' => max($z, 0.001)];
    }
    $z_max = 0.0;
    foreach ($imp_points as $pt) {
        if ($pt['z'] > $z_max) $z_max = $pt['z'];
    }
    if ($z_max <= 0) $z_max = 1;

    $p2 .= "0.000 0.337 0.839 RG 1.5 w\n";
    $first_pt = true;
    foreach ($imp_points as $pt) {
        $x = $gX1 + (($log_max > $log_min ? (log10($pt['f']) - $log_min) / ($log_max - $log_min) : 0) * $gW);
        $y_plot = $gY1 + (($pt['z'] / $z_max) * ($gH - 8));
        if ($first_pt) {
            $p2 .= sprintf("%.2f %.2f m ", $x, $y_plot);
            $first_pt = false;
        } else {
            $p2 .= sprintf("%.2f %.2f l ", $x, $y_plot);
        }
    }
    $p2 .= "S\n";
    // Axis labels
    $p2 .= "0.392 0.455 0.545 rg\nBT /F1 7 Tf {$gX1} " . ($gY1 - 10) . " Td ({$e($fmt_freq($f_min))}) Tj ET\n";
    $p2 .= "BT /F1 7 Tf " . ($gX1 + $gW / 2 - 14) . " " . ($gY1 - 10) . " Td ({$e($fmt_freq(sqrt($f_min * $f_max)))}) Tj ET\n";
    $p2 .= "BT /F1 7 Tf " . ($gX1 + $gW - 34) . " " . ($gY1 - 10) . " Td ({$e($fmt_freq($f_max))}) Tj ET\n";
    $p2 .= "BT /F2 8 Tf " . ($gX1 + $gW / 2 - 40) . " " . ($gY1 - 20) . " Td (Impedance vs Frequency) Tj ET\n";
    // Y-axis label
    $p2 .= "BT /F1 7 Tf " . ($gX1 - 6) . " " . ($gY1 + 5) . " Td (0) Tj ET\n";
    $p2 .= "BT /F1 7 Tf " . ($gX1 - 22) . " " . ($gY1 + $gH - 5) . " Td ({$e(number_format($z_max, 1) . ' Ohm')}) Tj ET\n";

    // Graph 2: Derating Curve (right)
    $gX2 = 320;
    $p2 .= "0.875 0.886 0.898 RG 0.5 w {$gX2} {$gY1} {$gW} {$gH} re S\n";
    // Grid
    $p2 .= "0.941 0.949 0.961 RG 0.3 w\n";
    for ($gi = 1; $gi <= 4; $gi++) {
        $gy = $gY1 + $gi * ($gH / 5);
        $p2 .= "{$gX2} {$gy} m " . ($gX2 + $gW) . " {$gy} l S\n";
        $gx = $gX2 + $gi * ($gW / 5);
        $p2 .= "{$gx} {$gY1} m {$gx} " . ($gY1 + $gH) . " l S\n";
    }
    // Derating line (calculated from DB: rated current over temperature range)
    $derating_pts = [];
    $knee_t = $t_min + (($t_max - $t_min) * 0.6);
    for ($pi = 0; $pi < 16; $pi++) {
        $ratio = $pi / 15;
        $t = $t_min + (($t_max - $t_min) * $ratio);
        $i_allow = $i_rated;
        if ($t > $knee_t) {
            $i_allow = $i_rated * max(0, 1 - (($t - $knee_t) / max(($t_max - $knee_t), 0.0001)));
        }
        $derating_pts[] = ['t' => $t, 'i' => $i_allow];
    }

    $p2 .= "0.839 0.145 0.000 RG 1.5 w\n";
    $first_pt = true;
    foreach ($derating_pts as $pt) {
        $x = $gX2 + ((($pt['t'] - $t_min) / max(($t_max - $t_min), 0.0001)) * $gW);
        $y_plot = $gY1 + (($pt['i'] / max($i_rated, 0.0001)) * ($gH - 8));
        if ($first_pt) {
            $p2 .= sprintf("%.2f %.2f m ", $x, $y_plot);
            $first_pt = false;
        } else {
            $p2 .= sprintf("%.2f %.2f l ", $x, $y_plot);
        }
    }
    $p2 .= "S\n";
    // Axis labels
    $p2 .= "0.392 0.455 0.545 rg\nBT /F1 7 Tf {$gX2} " . ($gY1 - 10) . " Td ({$e(rtrim(rtrim(number_format($t_min, 1, '.', ''), '0'), '.') . 'C')}) Tj ET\n";
    $p2 .= "BT /F1 7 Tf " . ($gX2 + $gW / 2 - 10) . " " . ($gY1 - 10) . " Td ({$e(rtrim(rtrim(number_format($t_mid, 1, '.', ''), '0'), '.') . 'C')}) Tj ET\n";
    $p2 .= "BT /F1 7 Tf " . ($gX2 + $gW - 22) . " " . ($gY1 - 10) . " Td ({$e(rtrim(rtrim(number_format($t_max, 1, '.', ''), '0'), '.') . 'C')}) Tj ET\n";
    $p2 .= "BT /F2 8 Tf " . ($gX2 + $gW / 2 - 30) . " " . ($gY1 - 20) . " Td (Current Derating Curve) Tj ET\n";
    $p2 .= "BT /F1 7 Tf " . ($gX2 - 18) . " " . ($gY1 + 5) . " Td (0 {$e($i_unit)}) Tj ET\n";
    $p2 .= "BT /F1 7 Tf " . ($gX2 - 24) . " " . ($gY1 + $gH - 5) . " Td ({$e(number_format($i_rated, 2) . ' ' . $i_unit)}) Tj ET\n";

    $y2 = $gY1 - 28;

    // ── Performance Notes (if any) ──
    if ($perf_notes && $y2 > 200) {
        $p2 .= "0.894 0.906 0.918 rg 50 {$y2} 495 1 re f\n"; $y2 -= 14;
        $p2 .= "0.118 0.161 0.239 rg\nBT /F2 11 Tf 50 {$y2} Td (Performance Notes) Tj ET\n";
        $y2 -= 4; $p2 .= "0.000 0.337 0.839 rg 50 {$y2} 120 2 re f\n"; $y2 -= 12;
        foreach ($perf_notes as $pn) {
            if ($y2 < 100) break;
            $pn = ltrim($pn, '- ');
            $pnL = $wrap($clean($pn), 90);
            $p2 .= "0.282 0.337 0.416 rg\nBT /F1 8.5 Tf 55 {$y2} Td\n";
            $first = true;
            foreach ($pnL as $pl) {
                $px = $first ? '- ' : '  '; $first = false;
                $p2 .= "({$e($px.$pl)}) Tj 0 -12 Td\n";
            }
            $p2 .= "ET\n"; $y2 -= count($pnL) * 12 + 1;
        }
        $y2 -= 4;
    }

    // ── Compliance, Safety & Packaging ──
    if ($compliance || $storage || $msl) {
        $p2 .= "0.894 0.906 0.918 rg 50 {$y2} 495 1 re f\n"; $y2 -= 16;
        $p2 .= "0.118 0.161 0.239 rg\nBT /F2 12 Tf 50 {$y2} Td (Compliance, Safety & Packaging) Tj ET\n";
        $y2 -= 4; $p2 .= "0.000 0.337 0.839 rg 50 {$y2} 200 2 re f\n"; $y2 -= 12;

        // Draw compliance badges (derived from DB fields)
        $badgeX = 55; $badgeY = $y2 - 26;
        foreach ($compliance_badges as $badge) {
            // Badge rounded rect (simplified as rect)
            $bw = strlen($badge) * 6 + 16;
            if (($badgeX + $bw) > 540) break;
            $p2 .= "0.133 0.545 0.133 rg {$badgeX} {$badgeY} {$bw} 20 re f\n"; // green bg
            $p2 .= "1 1 1 rg\nBT /F2 8 Tf " . ($badgeX + 8) . " " . ($badgeY + 6) . " Td ({$e($badge)}) Tj ET\n";
            $badgeX += $bw + 8;
        }
        $y2 = $badgeY - 10;

        // Compliance table
        if ($compliance) {
            $p2 .= "0.000 0.145 0.369 rg 50 " . ($y2 - 4) . " 495 19 re f\n";
            $p2 .= "1 1 1 rg\nBT /F2 8.5 Tf 55 {$y2} Td (Item) Tj ET\nBT /F2 8.5 Tf 310 {$y2} Td (Detail) Tj ET\n";
            $y2 -= 20;
            foreach ($compliance as $i => $c) {
                if ($y2 < 100) break;
                $label_lines = $wrap($clean($c['label']), 42);
                $value_lines = $wrap($clean($c['value']), 52);
                $line_count  = max(count($label_lines), count($value_lines));
                $row_h       = max(19, ($line_count * 10) + 7);
                $text_y      = $y2 + $row_h - 14;

                $bg = ($i % 2 === 0) ? "0.961 0.969 0.980 rg" : "1 1 1 rg";
                $p2 .= "{$bg} 50 " . ($y2 - 4) . " 495 {$row_h} re f\n";
                $p2 .= "0.875 0.886 0.898 RG 0.3 w 305 " . ($y2 - 4) . " m 305 " . ($y2 - 4 + $row_h) . " l S\n";

                $p2 .= "0.282 0.337 0.416 rg\nBT /F1 8.5 Tf 55 {$text_y} Td\n";
                foreach ($label_lines as $line) {
                    $p2 .= "({$e($line)}) Tj 0 -10 Td\n";
                }
                $p2 .= "ET\n";

                $p2 .= "0.118 0.161 0.239 rg\nBT /F2 8.5 Tf 310 {$text_y} Td\n";
                foreach ($value_lines as $line) {
                    $p2 .= "({$e($line)}) Tj 0 -10 Td\n";
                }
                $p2 .= "ET\n";

                $y2 -= $row_h;
            }
            $p2 .= "0.875 0.886 0.898 RG 0.5 w 50 " . ($y2 + 15) . " m 545 " . ($y2 + 15) . " l S\n";
            $y2 -= 6;
        }

        // Storage conditions — only render if enough room above footer
        if (($storage || $msl) && $y2 > 110) {
            $y2 -= 6;
            $p2 .= "0.118 0.161 0.239 rg\nBT /F2 10 Tf 50 {$y2} Td (Storage Conditions) Tj ET\n";
            $y2 -= 14;
            if ($storage && $y2 > 82) {
                $p2 .= "0.282 0.337 0.416 rg\nBT /F1 8.5 Tf 55 {$y2} Td ({$e($clean($storage))}) Tj ET\n";
                $y2 -= 13;
            }
            if ($msl && $y2 > 82) {
                $p2 .= "0.282 0.337 0.416 rg\nBT /F1 8.5 Tf 55 {$y2} Td (Moisture Sensitivity Level \\(MSL\\): {$e($clean($msl))}) Tj ET\n";
                $y2 -= 13;
            }
        }
    }

    // ── Page 2 Footer ──
    $p2 .= my_ds_footer($e, $clean, $company, $site_url, $site_email, $site_phone, $site_address, 2, 2);

    /* ═══════════════════════════════════════════════════════════
       ASSEMBLE PDF (2-page, optional image)
       ═══════════════════════════════════════════════════════════ */
    $p1Len = strlen($p1); $p2Len = strlen($p2);

    // Object indices
    // 1=Catalog, 2=Pages, 3=Page1, 4=Stream1, 5=Page2, 6=Stream2, 7=FontH, 8=FontHB, 9=GS
    $nextObj = 10;
    $imgObjId = null;

    $fontRes = "/Font << /F1 7 0 R /F2 8 0 R >> /ExtGState << /GS1 9 0 R >>";
    $imgRes = '';
    if ($imgData) {
        $imgObjId = $nextObj++;
        $imgRes = " /XObject << /Img1 {$imgObjId} 0 R >>";
    }
    $resDict = "<< {$fontRes}{$imgRes} >>";

    $objs = [];
    $objs[1] = "<< /Type /Catalog /Pages 2 0 R >>";
    $objs[2] = "<< /Type /Pages /Kids [3 0 R 5 0 R] /Count 2 >>";
    $objs[3] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$pageW} {$pageH}] /Contents 4 0 R /Resources {$resDict} >>";
    $objs[4] = "<< /Length {$p1Len} >>\nstream\n{$p1}\nendstream";
    $objs[5] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$pageW} {$pageH}] /Contents 6 0 R /Resources {$resDict} >>";
    $objs[6] = "<< /Length {$p2Len} >>\nstream\n{$p2}\nendstream";
    $objs[7] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>";
    $objs[8] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>";
    $objs[9] = "<< /Type /ExtGState /ca 1 /CA 1 >>";

    if ($imgData) {
        $imgLen = strlen($imgData);
        $objs[$imgObjId] = "<< /Type /XObject /Subtype /Image /Width {$imgW} /Height {$imgH} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length {$imgLen} >>\nstream\n{$imgData}\nendstream";
    }

    $totalObjs = max(array_keys($objs));
    $body = "%PDF-1.4\n";
    $offsets = [];
    for ($i = 1; $i <= $totalObjs; $i++) {
        if (!isset($objs[$i])) continue;
        $offsets[$i] = strlen($body);
        $body .= "{$i} 0 obj\n{$objs[$i]}\nendobj\n";
    }
    $xrefOffset = strlen($body);
    $xrefCount = $totalObjs + 1;
    $body .= "xref\n0 {$xrefCount}\n";
    $body .= "0000000000 65535 f \n";
    for ($i = 1; $i <= $totalObjs; $i++) {
        if (isset($offsets[$i])) {
            $body .= str_pad($offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        } else {
            $body .= "0000000000 00000 f \n";
        }
    }
    $body .= "trailer\n<< /Size {$xrefCount} /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($body));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    echo $body;
}

/**
 * Shared datasheet footer builder
 */
function my_ds_footer($e, $clean, $company, $site_url, $site_email, $site_phone, $site_address, $pageNum, $totalPages) {
    $f = '';
    $fy = 30;
    $f .= "0.894 0.906 0.918 rg 50 " . ($fy + 28) . " 495 1 re f\n";
    $parts = [];
    if ($site_url)   $parts[] = $clean($site_url);
    if ($site_email) $parts[] = $clean($site_email);
    if ($site_phone) $parts[] = $clean($site_phone);
    if ($parts) {
        $f .= "0.392 0.455 0.545 rg\nBT /F1 7.5 Tf 50 " . ($fy + 18) . " Td ({$e(implode('   |   ', $parts))}) Tj ET\n";
    }
    if ($site_address) {
        $f .= "BT /F1 7 Tf 0.392 0.455 0.545 rg 50 " . ($fy + 9) . " Td ({$e($clean($site_address))}) Tj ET\n";
    }
    $f .= "BT /F1 6.5 Tf 0.592 0.655 0.745 rg 50 {$fy} Td (Specifications subject to change without notice. For reference only.) Tj ET\n";
    $yr = date('Y');
    $f .= "BT /F1 7 Tf 0.592 0.655 0.745 rg 460 {$fy} Td (Page {$pageNum}/{$totalPages}) Tj ET\n";
    $f .= "BT /F1 6.5 Tf 0.592 0.655 0.745 rg 370 " . ($fy + 9) . " Td (Copyright {$yr} {$e($clean($company))}) Tj ET\n";
    return $f;
}


// ============================================
// 0. PRODUCTS CPT + TAXONOMY + ACF FIELDS
// ============================================

/**
 * Rewrite tag for product_category in product URLs
 */
add_action('init', function() {
    add_rewrite_tag('%product_category%', '([^/]+)');
}, 1);

/**
 * Register Custom Post Type: product
 * URL: /products/{category}/{product-slug}/
 */
add_action('init', function() {
    register_post_type('product', [
        'labels' => [
            'name'               => 'Products',
            'singular_name'      => 'Product',
            'add_new'            => 'Add New Product',
            'add_new_item'       => 'Add New Product',
            'edit_item'          => 'Edit Product',
            'new_item'           => 'New Product',
            'view_item'          => 'View Product',
            'search_items'       => 'Search Products',
            'not_found'          => 'No products found',
            'not_found_in_trash' => 'No products found in Trash',
            'all_items'          => 'All Products',
            'menu_name'          => 'Products',
        ],
        'public'       => true,
        'has_archive'  => false,
        'rewrite'      => ['slug' => 'products/%product_category%', 'with_front' => false],
        'menu_icon'    => 'dashicons-products',
        'menu_position'=> 5,
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'supports'     => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
        'show_in_rest' => true,
    ]);
    }, 20);

/**
 * Register Hierarchical Taxonomy: product_category
 * (Inductors > Common Mode Chokes, Output Chokes, etc.)
 */
add_action('init', function() {
    register_taxonomy('product_category', 'product', [
        'labels' => [
            'name'              => 'Product Categories',
            'singular_name'     => 'Product Category',
            'search_items'      => 'Search Categories',
            'all_items'         => 'All Categories',
            'parent_item'       => 'Parent Category',
            'parent_item_colon' => 'Parent Category:',
            'edit_item'         => 'Edit Category',
            'update_item'       => 'Update Category',
            'add_new_item'      => 'Add New Category',
            'new_item_name'     => 'New Category Name',
            'menu_name'         => 'Product Categories',
        ],
        'hierarchical'  => true, // ทำให้เป็น parent > child ได้
        'public'        => true,
        'rewrite'       => ['slug' => 'products', 'with_front' => false, 'hierarchical' => false],
        'show_in_rest'  => true,
        'show_admin_column' => true,
    ]);
});

// ============================================
// PRODUCT CATEGORY TERM META — image / description / specs
// ============================================

// Enqueue media on taxonomy edit pages
add_action('admin_enqueue_scripts', function($hook) {
    if (in_array($hook, ['edit-tags.php', 'term.php'])) {
        wp_enqueue_media();
        wp_add_inline_script('jquery-core', "
        jQuery(function($){
            $(document).on('click', '.cat-meta-img-btn', function(e){
                e.preventDefault();
                var btn    = $(this);
                var target = btn.data('target');
                var frame  = wp.media({ title: 'เลือกรูปภาพ Category', multiple: false });
                frame.on('select', function(){
                    var att = frame.state().get('selection').first().toJSON();
                    $('#' + target).val(att.url);
                    $('#' + target + '_preview').attr('src', att.url).show();
                });
                frame.open();
            });
            $(document).on('click', '.cat-meta-img-remove', function(e){
                e.preventDefault();
                var target = $(this).data('target');
                $('#' + target).val('');
                $('#' + target + '_preview').hide().attr('src','');
            });
        });
        ");
    }
});

// ── Edit form fields ──
add_action('product_category_edit_form_fields', function($term) {
    $image       = get_term_meta($term->term_id, 'cat_image', true);
    $desc_long   = get_term_meta($term->term_id, 'cat_description_long', true);
    $specs_raw   = get_term_meta($term->term_id, 'cat_specs', true);
    $specs       = $specs_raw ? json_decode($specs_raw, true) : [];
    // Ensure 6 rows
    while (count($specs) < 6) $specs[] = ['icon' => '', 'label' => '', 'value' => ''];
    $nonce = wp_create_nonce('cat_meta_save');
    ?>
    <input type="hidden" name="cat_meta_nonce" value="<?php echo esc_attr($nonce); ?>">

    <tr class="form-field">
        <th scope="row"><label for="cat_image">รูปภาพ Category</label></th>
        <td>
            <input type="url" id="cat_image" name="cat_image" value="<?php echo esc_attr($image); ?>" class="regular-text">
            <button type="button" class="button cat-meta-img-btn" data-target="cat_image" style="margin-left:6px;">📷 เลือกรูป</button>
            <button type="button" class="button cat-meta-img-remove" data-target="cat_image" style="margin-left:4px;color:red;">✕ ลบ</button>
            <br>
            <?php if ($image) : ?>
            <img id="cat_image_preview" src="<?php echo esc_url($image); ?>" style="margin-top:8px;max-height:80px;max-width:200px;border:1px solid #ddd;border-radius:6px;">
            <?php else : ?>
            <img id="cat_image_preview" src="" style="display:none;margin-top:8px;max-height:80px;max-width:200px;border:1px solid #ddd;border-radius:6px;">
            <?php endif; ?>
        </td>
    </tr>

    <tr class="form-field">
        <th scope="row"><label for="cat_description_long">คำอธิบายแบบเต็ม</label></th>
        <td>
            <textarea id="cat_description_long" name="cat_description_long" rows="5" class="large-text"><?php echo esc_textarea($desc_long); ?></textarea>
            <p class="description">แสดงในหน้า Category เป็น HTML พื้นฐาน (p, strong, a, h2 ได้)</p>
        </td>
    </tr>

    <tr class="form-field">
        <th scope="row">Specs (ข้อมูลจำเพาะ)</th>
        <td>
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr>
                        <th style="text-align:left;padding:4px 8px;width:140px;background:#f5f5f5;">Icon (fa-xxx)</th>
                        <th style="text-align:left;padding:4px 8px;background:#f5f5f5;">Label</th>
                        <th style="text-align:left;padding:4px 8px;background:#f5f5f5;">Value</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($specs as $i => $spec) : ?>
                <tr>
                    <td style="padding:4px 8px;">
                        <input type="text" name="cat_specs[<?php echo $i; ?>][icon]" value="<?php echo esc_attr($spec['icon'] ?? ''); ?>" placeholder="arrows-alt" style="width:100%;">
                    </td>
                    <td style="padding:4px 8px;">
                        <input type="text" name="cat_specs[<?php echo $i; ?>][label]" value="<?php echo esc_attr($spec['label'] ?? ''); ?>" placeholder="Size Range" style="width:100%;">
                    </td>
                    <td style="padding:4px 8px;">
                        <input type="text" name="cat_specs[<?php echo $i; ?>][value]" value="<?php echo esc_attr($spec['value'] ?? ''); ?>" placeholder="Custom sizes available" style="width:100%;">
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <p class="description">ชื่อ icon ดูได้จาก <a href="https://fontawesome.com/v4/icons/" target="_blank">Font Awesome 4</a> (เช่น arrows-alt, thermometer-three-quarters, truck, check)</p>
        </td>
    </tr>
    <?php
}, 10, 1);

// ── Add form fields ──
add_action('product_category_add_form_fields', function() {
    ?>
    <?php wp_nonce_field('cat_meta_save', 'cat_meta_nonce'); ?>
    <div class="form-field">
        <label for="cat_image">รูปภาพ Category</label>
        <input type="url" id="cat_image" name="cat_image" value="">
        <button type="button" class="button cat-meta-img-btn" data-target="cat_image" style="margin-left:6px;">📷 เลือกรูป</button>
        <img id="cat_image_preview" src="" style="display:none;margin-top:8px;max-height:80px;max-width:200px;border:1px solid #ddd;border-radius:6px;">
    </div>
    <div class="form-field">
        <label for="cat_description_long">คำอธิบายแบบเต็ม</label>
        <textarea id="cat_description_long" name="cat_description_long" rows="4" class="large-text"></textarea>
    </div>
    <?php
});

// ── Save term meta ──
add_action('created_product_category', 'my_theme_save_cat_meta');
add_action('edited_product_category',  'my_theme_save_cat_meta');
function my_theme_save_cat_meta($term_id) {
    if (!isset($_POST['cat_meta_nonce']) || !wp_verify_nonce($_POST['cat_meta_nonce'], 'cat_meta_save')) return;
    if (!current_user_can('manage_categories')) return;

    if (isset($_POST['cat_image'])) {
        update_term_meta($term_id, 'cat_image', esc_url_raw($_POST['cat_image']));
    }
    if (isset($_POST['cat_description_long'])) {
        update_term_meta($term_id, 'cat_description_long', wp_kses_post($_POST['cat_description_long']));
    }
    if (isset($_POST['cat_specs']) && is_array($_POST['cat_specs'])) {
        $clean = [];
        foreach ($_POST['cat_specs'] as $spec) {
            $label = sanitize_text_field($spec['label'] ?? '');
            $value = sanitize_text_field($spec['value'] ?? '');
            $icon  = sanitize_text_field($spec['icon'] ?? '');
            if ($label || $value) {
                $clean[] = ['icon' => $icon, 'label' => $label, 'value' => $value];
            }
        }
        update_term_meta($term_id, 'cat_specs', json_encode($clean, JSON_UNESCAPED_UNICODE));
    }
}

/**
 * Replace %product_category% in product permalinks with root parent category slug
 */
add_filter('post_type_link', function($link, $post) {
    if ($post->post_type !== 'product') return $link;

    $terms = wp_get_object_terms($post->ID, 'product_category');
    if ($terms && !is_wp_error($terms)) {
        // Walk up to root parent category
        $term = $terms[0];
        while ($term->parent) {
            $parent = get_term($term->parent, 'product_category');
            if ($parent && !is_wp_error($parent)) {
                $term = $parent;
            } else {
                break;
            }
        }
        $link = str_replace('%product_category%', $term->slug, $link);
    } else {
        $link = str_replace('%product_category%', 'uncategorized', $link);
    }
    return $link;
}, 10, 2);

/**
 * Remove category constraint from single product query
 * (product may be in child category, but URL shows root parent)
 */
add_filter('request', function($vars) {
    if (isset($vars['product']) && isset($vars['product_category'])) {
        unset($vars['product_category']);
    }
    return $vars;
});

/**
 * Redirect product_category taxonomy page → single product page
 * when a product with the same slug exists (e.g. /products/transformers-with-pcb-assemblies/ → single product)
 */
add_action('template_redirect', function() {
    if (!is_tax('product_category')) return;

    $term = get_queried_object();
    if (!$term || is_wp_error($term)) return;

    // ถ้า category มีลูก (child categories) → แสดงหน้า category ปกติ ไม่ redirect
    $child_terms = get_terms([
        'taxonomy'   => 'product_category',
        'parent'     => $term->term_id,
        'hide_empty' => false,
        'number'     => 1,
    ]);
    if (!empty($child_terms) && !is_wp_error($child_terms)) return;

    // ถ้า category มีสินค้าแค่ชิ้นเดียว (และไม่มี child) → redirect ไปหน้าสินค้านั้นเลย
    // ถ้ามีมากกว่า 1 ชิ้น → แสดงรายการทั้งหมดในหน้า category ปกติ
    $products = get_posts([
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => 2,
        'no_found_rows'  => true,
        'tax_query'      => [[
            'taxonomy'         => 'product_category',
            'field'            => 'term_id',
            'terms'            => $term->term_id,
            'include_children' => true,
        ]],
    ]);

    if (count($products) === 1) {
        wp_redirect(get_permalink($products[0]->ID), 302);
        exit;
    }
});

/**
 * Register ACF Field Group สำหรับ Products
 */
add_action('acf/include_fields', function() {
    if (!function_exists('acf_add_local_field_group')) return;

    acf_add_local_field_group([
        'key'    => 'group_product_details',
        'title'  => 'Product Details',
        'fields' => [
            // --- General Info ---
            [
                'key'   => 'field_pd_subtitle',
                'label' => 'Subtitle',
                'name'  => 'pd_subtitle',
                'type'  => 'text',
                'instructions' => 'เช่น "High impedance common mode choke for EMC filtering"',
            ],
            [
                'key'   => 'field_pd_sku',
                'label' => 'SKU / Part Number',
                'name'  => 'pd_sku',
                'type'  => 'text',
            ],

            // --- Gallery Images ---
            [
                'key'   => 'field_pd_gallery_tab',
                'label' => 'Gallery Images',
                'type'  => 'tab',
            ],
            [
                'key'          => 'field_pd_image_1',
                'label'        => 'Image 1 (Main)',
                'name'         => 'pd_image_1',
                'type'         => 'image',
                'return_format'=> 'url',
                'preview_size' => 'medium',
            ],
            [
                'key'          => 'field_pd_image_2',
                'label'        => 'Image 2',
                'name'         => 'pd_image_2',
                'type'         => 'image',
                'return_format'=> 'url',
                'preview_size' => 'medium',
            ],
            [
                'key'          => 'field_pd_image_3',
                'label'        => 'Image 3',
                'name'         => 'pd_image_3',
                'type'         => 'image',
                'return_format'=> 'url',
                'preview_size' => 'medium',
            ],

            // --- Key Features ---
            [
                'key'   => 'field_pd_features_tab',
                'label' => 'Key Features',
                'type'  => 'tab',
            ],
            [
                'key'          => 'field_pd_features',
                'label'        => 'Features (one per line)',
                'name'         => 'pd_features',
                'type'         => 'textarea',
                'rows'         => 6,
                'instructions' => 'พิมพ์ feature ทีละบรรทัด เช่น:\nHigh impedance for effective noise suppression\nWide frequency range: 100kHz – 100MHz',
            ],

            // --- Specifications ---
            [
                'key'   => 'field_pd_specs_tab',
                'label' => 'Specifications',
                'type'  => 'tab',
            ],
            [
                'key'   => 'field_pd_inductance',
                'label' => 'Inductance',
                'name'  => 'pd_inductance',
                'type'  => 'text',
                'instructions' => 'เช่น 1mH – 47mH',
            ],
            [
                'key'   => 'field_pd_current_rating',
                'label' => 'Current Rating',
                'name'  => 'pd_current_rating',
                'type'  => 'text',
                'instructions' => 'เช่น 0.5A – 30A',
            ],
            [
                'key'   => 'field_pd_impedance',
                'label' => 'Impedance',
                'name'  => 'pd_impedance',
                'type'  => 'text',
                'instructions' => 'เช่น 1000Ω – 5000Ω @ 100MHz',
            ],
            [
                'key'   => 'field_pd_temp_range',
                'label' => 'Operating Temperature',
                'name'  => 'pd_temp_range',
                'type'  => 'text',
                'instructions' => 'เช่น –40°C to +125°C',
            ],
            [
                'key'   => 'field_pd_package_type',
                'label' => 'Package Type',
                'name'  => 'pd_package_type',
                'type'  => 'text',
                'instructions' => 'เช่น Through-hole / SMD',
            ],
            [
                'key'   => 'field_pd_voltage',
                'label' => 'Voltage Rating',
                'name'  => 'pd_voltage',
                'type'  => 'text',
                'instructions' => 'เช่น 250VAC / 500VDC',
            ],
            [
                'key'   => 'field_pd_frequency',
                'label' => 'Frequency Range',
                'name'  => 'pd_frequency',
                'type'  => 'text',
                'instructions' => 'เช่น 100kHz – 100MHz',
            ],
            [
                'key'   => 'field_pd_size_range',
                'label' => 'Size Range / Form Factor',
                'name'  => 'pd_size_range',
                'type'  => 'text',
                'instructions' => 'เช่น Through-hole, SMD',
            ],
            [
                'key'   => 'field_pd_output_range',
                'label' => 'Output Range',
                'name'  => 'pd_output_range',
                'type'  => 'text',
                'instructions' => 'เช่น Built to meet industry standards',
            ],
            [
                'key'   => 'field_pd_standards',
                'label' => 'Standards',
                'name'  => 'pd_standards',
                'type'  => 'text',
                'instructions' => 'เช่น IPC-A-610, RoHS3, Conflict Free',
            ],
            [
                'key'   => 'field_pd_long_description',
                'label' => 'Long Description',
                'name'  => 'pd_long_description',
                'type'  => 'textarea',
                'rows'  => 6,
                'instructions' => 'คำอธิบายรายละเอียดเพิ่มเติมของสินค้า',
            ],

            // --- Datasheet ---
            [
                'key'   => 'field_pd_datasheet_tab',
                'label' => 'Datasheet',
                'type'  => 'tab',
            ],
            [
                'key'          => 'field_pd_datasheet',
                'label'        => 'Datasheet PDF',
                'name'         => 'pd_datasheet',
                'type'         => 'file',
                'return_format'=> 'url',
                'mime_types'   => 'pdf',
            ],
        ],
        'location' => [
            [
                [
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'product',
                ],
            ],
        ],
        'position'   => 'normal',
        'style'      => 'default',
        'menu_order'  => 0,
    ]);

    // --- ACF Field Group for Product Category Taxonomy ---
    acf_add_local_field_group([
        'key'    => 'group_product_category',
        'title'  => 'Category Details',
        'fields' => [
            [
                'key'          => 'field_cat_image',
                'label'        => 'Category Image',
                'name'         => 'cat_image',
                'type'         => 'image',
                'return_format'=> 'url',
                'preview_size' => 'medium',
                'instructions' => 'ภาพหน้าปกของ category นี้',
            ],
            [
                'key'          => 'field_cat_description',
                'label'        => 'Short Description',
                'name'         => 'cat_description',
                'type'         => 'textarea',
                'rows'         => 3,
                'instructions' => 'คำอธิบายสั้น ๆ แสดงใน product card บนหน้า Products',
            ],
        ],
        'location' => [
            [
                [
                    'param'    => 'taxonomy',
                    'operator' => '==',
                    'value'    => 'product_category',
                ],
            ],
        ],
        'position'  => 'normal',
        'style'     => 'default',
        'menu_order' => 0,
    ]);
});

/**
 * Flush rewrite rules once after CPT registration
 */
add_action('init', function() {
    if (get_option('product_cpt_flushed') !== '2') {
        flush_rewrite_rules();
        update_option('product_cpt_flushed', '2');
    }
}, 99);

// ============================================
// 1. THEME SETUP
// ============================================
// ============================================
// BOOTSTRAP 5 NAV MENU WALKER
// ============================================
class My_Bootstrap5_Walker extends Walker_Nav_Menu {
    public function start_lvl( &$output, $depth = 0, $args = null ) {
        $output .= '<ul class="dropdown-menu">';
    }
    public function end_lvl( &$output, $depth = 0, $args = null ) {
        $output .= '</ul>';
    }
    public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
        $item    = $data_object;
        $classes = empty( $item->classes ) ? [] : (array) $item->classes;
        $has_children = in_array( 'menu-item-has-children', $classes );
        $is_current   = in_array( 'current-menu-item', $classes ) || in_array( 'current-menu-ancestor', $classes );
        if ( $depth === 0 ) {
            $li_class = 'nav-item' . ( $has_children ? ' dropdown' : '' );
            $a_class  = 'nav-link' . ( $has_children ? ' dropdown-toggle' : '' ) . ( $is_current ? ' active' : '' );
            $extra    = $has_children ? ' data-bs-toggle="dropdown" aria-expanded="false"' : ( $is_current ? ' aria-current="page"' : '' );
            $output  .= '<li class="' . esc_attr( $li_class ) . '">';
            $output  .= '<a class="' . esc_attr( $a_class ) . '" href="' . esc_url( $item->url ) . '"' . $extra . '>' . esc_html( $item->title ) . '</a>';
        } else {
            $output .= '<li>';
            $output .= '<a class="dropdown-item" href="' . esc_url( $item->url ) . '">' . esc_html( $item->title ) . '</a>';
        }
    }
    public function end_el( &$output, $data_object, $depth = 0, $args = null ) {
        $output .= '</li>';
    }
}

add_action('after_setup_theme', function() {
    // Let WordPress generate the <title> tag
    add_theme_support('title-tag');

    // Register navigation menu locations
    register_nav_menus([
        'primary' => 'เมนูหลัก Navbar',
    ]);

    // Support block patterns
    add_theme_support('block-patterns');
    
    // Support editor styles
    add_theme_support('editor-styles');
    
    // Support wide and full alignment
    add_theme_support('align-wide');

    // Enable full-site editing surfaces in hybrid/classic mode
    add_theme_support('block-templates');
    add_theme_support('block-template-parts');
    
    // Enable Custom Fields panel in block editor for pages
    add_post_type_support('page', 'custom-fields');
    
    // Support custom colors
    $editor_primary = get_option('theme_primary_color', '#0056d6');
    $ep_hex = ltrim($editor_primary, '#');
    if (strlen($ep_hex) === 3) { $ep_hex = $ep_hex[0].$ep_hex[0].$ep_hex[1].$ep_hex[1].$ep_hex[2].$ep_hex[2]; }
    $ep_dark = sprintf('#%02x%02x%02x', max(0,round(hexdec(substr($ep_hex,0,2))*0.82)), max(0,round(hexdec(substr($ep_hex,2,2))*0.82)), max(0,round(hexdec(substr($ep_hex,4,2))*0.82)));
    add_theme_support('editor-color-palette', array(
        array(
            'name'  => 'Primary',
            'slug'  => 'primary',
            'color' => $editor_primary,
        ),
        array(
            'name'  => 'Primary Dark',
            'slug'  => 'primary-dark',
            'color' => $ep_dark,
        ),
        array(
            'name'  => 'White',
            'slug'  => 'white',
            'color' => '#ffffff',
        ),
        array(
            'name'  => 'Tertiary (Light Gray)',
            'slug'  => 'tertiary',
            'color' => '#f8fafc',
        ),
        array(
            'name'  => 'Dark Text',
            'slug'  => 'dark-text',
            'color' => '#1e293b',
        ),
        array(
            'name'  => 'Gray Text',
            'slug'  => 'gray-text',
            'color' => '#64748b',
        ),
        array(
            'name'  => 'Footer Dark',
            'slug'  => 'footer-dark',
            'color' => '#1e293b',
        ),
    ));
    
    // Inject styles into Gutenberg editor iframe via add_editor_style()
    // This is the ONLY reliable method for WP 6.3+ iframed editor
    add_editor_style('https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap');
    add_editor_style('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
    add_editor_style('assets/css/patterns.css');
    add_editor_style('assets/css/editor-style.css');
});

// Force block editor for every post type that supports editor + REST
add_filter('use_block_editor_for_post_type', function($use, $post_type) {
    if (!post_type_exists($post_type)) return $use;
    $obj = get_post_type_object($post_type);
    if (!$obj || empty($obj->show_in_rest)) return $use;
    return post_type_supports($post_type, 'editor') ? true : $use;
}, 20, 2);

// ============================================
// 2. REGISTER BLOCK PATTERN CATEGORY + PATTERNS
// ============================================
add_action('init', function() {
    register_block_pattern_category(
        'my-special-design',
        array( 
            'label' => 'ดีไซน์ของฉัน',
            'description' => 'Block patterns สำหรับเว็บไซต์ Electronic Components'
        )
    );
    
    register_block_pattern_category(
        'bootstrap-components',
        array( 
            'label' => 'Bootstrap Components',
            'description' => 'Bootstrap 5.3 Components ลากวางได้'
        )
    );
});

// Manual registration for ALL patterns to ensure they show up
add_action('init', function() {
    $registry = WP_Block_Patterns_Registry::get_instance();
    $pattern_dir = get_template_directory() . '/patterns/';
    
    // List all pattern files and their metadata
    $patterns = array(
        'header-navbar' => array(
            'title' => 'Header Navbar (Bootstrap 5)',
            'categories' => array('my-special-design'),
            'description' => 'Header พร้อม Logo, Navigation Menu และเบอร์โทรศัพท์',
        ),
        'hero-section' => array(
            'title' => 'Hero Section',
            'categories' => array('my-special-design'),
            'description' => 'หน้าหลักแบบ Hero พร้อมปุ่ม CTA',
        ),
        'product-cards' => array(
            'title' => 'Product Cards',
            'categories' => array('my-special-design'),
            'description' => 'การ์ดสินค้า 3 คอลัมน์',
        ),
        'features-grid' => array(
            'title' => 'Features Grid',
            'categories' => array('my-special-design'),
            'description' => 'ตาราง Features สินค้า',
        ),
        'cta-section' => array(
            'title' => 'CTA Section',
            'categories' => array('my-special-design'),
            'description' => 'Call to Action Section',
        ),
        'page-header' => array(
            'title' => 'Page Header',
            'categories' => array('my-special-design'),
            'description' => 'Header สำหรับแต่ละหน้า',
        ),
        'about-content' => array(
            'title' => 'About Content',
            'categories' => array('my-special-design'),
            'description' => 'เนื้อหาหน้า About Us',
        ),
        'stats-grid' => array(
            'title' => 'Stats Grid',
            'categories' => array('my-special-design'),
            'description' => 'ตัวเลขสถิติ',
        ),
        'contact-form' => array(
            'title' => 'Contact Form',
            'categories' => array('my-special-design'),
            'description' => 'แบบฟอร์มติดต่อ',
        ),
        'footer-section' => array(
            'title' => 'Footer Section',
            'categories' => array('my-special-design'),
            'description' => 'Footer เว็บไซต์',
        ),
        // Full Page Patterns
        'page-about' => array(
            'title' => '📄 Page: About Us (Full Page)',
            'categories' => array('my-special-design'),
            'description' => 'หน้า About Us แบบเต็มหน้า - Page Header, About Content, Stats, Mission, Certifications, CTA',
        ),
        'page-products' => array(
            'title' => '📄 Page: Products (Full Page)',
            'categories' => array('my-special-design'),
            'description' => 'หน้า Products แบบเต็มหน้า - Page Header, Product Categories, Applications, CTA',
        ),
        'page-contacts' => array(
            'title' => '📄 Page: Contacts (Full Page)',
            'categories' => array('my-special-design'),
            'description' => 'หน้า Contact Us แบบเต็มหน้า - Page Header, Contact Info, Form, Map',
        ),
        'page-inductors' => array(
            'title' => '📄 Page: Inductors (Product Category)',
            'categories' => array('my-special-design'),
            'description' => 'หน้า Inductors - รายการสินค้า Inductors พร้อม Subcategory Navigation',
        ),
        'page-transformers' => array(
            'title' => '📄 Page: Transformers (Product Category)',
            'categories' => array('my-special-design'),
            'description' => 'หน้า Transformers - รายการสินค้า Transformers พร้อม Subcategory Navigation',
        ),
        'page-antennas' => array(
            'title' => '📄 Page: Antennas (Product Category)',
            'categories' => array('my-special-design'),
            'description' => 'หน้า Antennas - รายการสินค้า Antennas พร้อม Subcategory Navigation',
        ),
        'page-product-detail' => array(
            'title' => '📄 Page: Product Detail (CMC Series)',
            'categories' => array('my-special-design'),
            'description' => 'หน้า Product Detail - Common Mode Choke CMC Series พร้อม Gallery, Specs, Related Products',
        ),
        // Bootstrap Components
        'bootstrap-buttons' => array(
            'title' => 'BS: Buttons (ปุ่ม)',
            'categories' => array('bootstrap-components', 'my-special-design'),
            'description' => 'ปุ่ม Bootstrap หลากหลายรูปแบบ',
        ),
        'bootstrap-cards' => array(
            'title' => 'BS: Cards (การ์ด)',
            'categories' => array('bootstrap-components', 'my-special-design'),
            'description' => 'การ์ด Bootstrap 3 คอลัมน์',
        ),
        'bootstrap-carousel' => array(
            'title' => 'BS: Carousel (สไลด์โชว์)',
            'categories' => array('bootstrap-components', 'my-special-design'),
            'description' => 'สไลด์โชว์รูปภาพ Bootstrap',
        ),
        'bootstrap-accordion' => array(
            'title' => 'BS: Accordion (FAQ)',
            'categories' => array('bootstrap-components', 'my-special-design'),
            'description' => 'FAQ พับเปิด-ปิดได้',
        ),
        'bootstrap-alerts' => array(
            'title' => 'BS: Alerts (แจ้งเตือน)',
            'categories' => array('bootstrap-components', 'my-special-design'),
            'description' => 'กล่องแจ้งเตือนหลายสี',
        ),
        'bootstrap-hero' => array(
            'title' => 'BS: Hero Banner',
            'categories' => array('bootstrap-components', 'my-special-design'),
            'description' => 'Hero Banner ขนาดใหญ่',
        ),
        'bootstrap-modal' => array(
            'title' => 'BS: Modal (Popup)',
            'categories' => array('bootstrap-components', 'my-special-design'),
            'description' => 'Popup Modal Dialog',
        ),
        'bootstrap-tabs' => array(
            'title' => 'BS: Tabs (แท็บ)',
            'categories' => array('bootstrap-components', 'my-special-design'),
            'description' => 'แท็บสลับเนื้อหา',
        ),
        'bootstrap-grid' => array(
            'title' => 'BS: Grid Layout (คอลัมน์)',
            'categories' => array('bootstrap-components', 'my-special-design'),
            'description' => 'Grid Layout 2, 3, 4 คอลัมน์',
        ),
        'bootstrap-form' => array(
            'title' => 'BS: Form (แบบฟอร์ม)',
            'categories' => array('bootstrap-components', 'my-special-design'),
            'description' => 'แบบฟอร์มติดต่อ Bootstrap',
        ),
        'bootstrap-pricing' => array(
            'title' => 'BS: Pricing Table (ตารางราคา)',
            'categories' => array('bootstrap-components', 'my-special-design'),
            'description' => 'ตารางเปรียบเทียบราคา 3 แพ็กเกจ',
        ),
        'bootstrap-testimonials' => array(
            'title' => 'BS: Testimonials (รีวิว)',
            'categories' => array('bootstrap-components', 'my-special-design'),
            'description' => 'รีวิวลูกค้า 3 คอลัมน์',
        ),
        'bootstrap-table' => array(
            'title' => 'BS: Table (ตาราง)',
            'categories' => array('bootstrap-components', 'my-special-design'),
            'description' => 'ตาราง Bootstrap responsive',
        ),
        'bootstrap-progress' => array(
            'title' => 'BS: Progress & Badges',
            'categories' => array('bootstrap-components', 'my-special-design'),
            'description' => 'Progress Bars และ Badges',
        ),
        // Chat Widget
        'chat-buttons' => array(
            'title' => '💬 Chat Buttons (LINE / WeChat / WhatsApp)',
            'categories' => array('my-special-design'),
            'description' => 'ปุ่มแชท LINE, WeChat, WhatsApp — ดึงค่าจาก Theme Settings',
        ),
    );
    
    foreach ($patterns as $pattern_name => $pattern_meta) {
        $slug = 'my-custom-theme/' . $pattern_name;
        
        // Skip if already registered (by auto-discovery)
        if ($registry->is_registered($slug)) {
            continue;
        }
        
        $file = $pattern_dir . $pattern_name . '.php';
        if (!file_exists($file)) {
            continue;
        }
        
        // Read file content, skip PHP header
        ob_start();
        include $file;
        $content = ob_get_clean();
        
        if (empty(trim($content))) {
            continue;
        }
        
        register_block_pattern($slug, array(
            'title'       => $pattern_meta['title'],
            'categories'  => $pattern_meta['categories'],
            'description' => $pattern_meta['description'],
            'content'     => $content,
        ));
    }
}, 20);

// ============================================
// PRODUCT DETAIL - Custom Fields Registration
// (แก้รูปภาพได้ใน WP Admin → Pages → Edit → Custom Fields)
// ============================================
add_action('init', function() {
    for ($i = 1; $i <= 3; $i++) {
        register_post_meta('page', "pd_image_{$i}", array(
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'string',
            'description'   => "Gallery Image {$i} URL",
        ));
        register_post_meta('page', "pd_image_{$i}_alt", array(
            'show_in_rest'  => true,
            'single'        => true,
            'type'          => 'string',
            'description'   => "Gallery Image {$i} Alt Text",
        ));
    }
});

// ============================================
// 3. ENQUEUE STYLES AND SCRIPTS
// ============================================

// Frontend styles and scripts
add_action('wp_enqueue_scripts', function() {
    // Google Fonts - Sarabun
    wp_enqueue_style(
        'google-fonts-sarabun',
        'https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap',
        array(),
        null
    );
    
    // Bootstrap 5.3 CSS
    wp_enqueue_style(
        'bootstrap',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
        array(),
        '5.3.3'
    );

    // Font Awesome 4.7
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css',
        array(),
        '4.7.0'
    );
    
    // Main theme stylesheet (after Bootstrap)
    wp_enqueue_style(
        'my-custom-theme-style',
        get_stylesheet_uri(),
        array('bootstrap'),
        wp_get_theme()->get('Version')
    );
    
    // Block patterns custom styles (after Bootstrap)
    wp_enqueue_style(
        'my-custom-theme-patterns',
        get_template_directory_uri() . '/assets/css/patterns.css',
        array('bootstrap', 'my-custom-theme-style'),
        wp_get_theme()->get('Version')
    );

    // Remove empty <p> tags generated by wpautop after block content
    wp_add_inline_style('my-custom-theme-style', '
        .site-main p:empty,
        .entry-content p:empty,
        .wp-block-post-content p:empty { display:none !important; margin:0 !important; padding:0 !important; }
    ');
    
    // Bootstrap 5.3 JavaScript Bundle (includes Popper)
    wp_enqueue_script(
        'bootstrap',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
        array(),
        '5.3.3',
        true
    );

    // Parametric Filter — only on product_category taxonomy pages
    if (is_tax('product_category')) {
        wp_enqueue_style(
            'parametric-filter',
            get_template_directory_uri() . '/assets/css/parametric-filter.css',
            array('my-custom-theme-style'),
            wp_get_theme()->get('Version')
        );
        wp_enqueue_script(
            'parametric-filter',
            get_template_directory_uri() . '/assets/js/parametric-filter.js',
            array(),
            wp_get_theme()->get('Version'),
            true
        );
    }
});

// Load Bootstrap JS in editor (for carousel, accordion, modal, tabs interactivity)
// CSS is handled via add_editor_style() above -JS still uses this hook
add_action('enqueue_block_editor_assets', function() {
    wp_enqueue_script(
        'bootstrap-editor',
        'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js',
        array(),
        '5.3.3',
        true
    );

    $editor_primary = get_option('theme_primary_color', '#0056d6');
    $editor_accent  = get_option('theme_accent_color', '#4ecdc4');
    $editor_bg      = get_option('theme_bg_color', '#ffffff');

    $editor_make_dark = function($color) {
        $hex = ltrim((string) $color, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        $r = max(0, round(hexdec(substr($hex, 0, 2)) * 0.82));
        $g = max(0, round(hexdec(substr($hex, 2, 2)) * 0.82));
        $b = max(0, round(hexdec(substr($hex, 4, 2)) * 0.82));
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    };

    $editor_primary_dark = $editor_make_dark($editor_primary);
    $editor_accent_dark  = $editor_make_dark($editor_accent);

    $editor_vars_css = ':root,.editor-styles-wrapper{'
        . '--theme-bg:' . esc_attr($editor_bg) . ';'
        . '--theme-primary:' . esc_attr($editor_primary) . ';'
        . '--theme-primary-dark:' . esc_attr($editor_primary_dark) . ';'
        . '--theme-accent:' . esc_attr($editor_accent) . ';'
        . '--theme-accent-dark:' . esc_attr($editor_accent_dark) . ';'
        . '}';
    wp_add_inline_style('wp-edit-blocks', $editor_vars_css);
});

// Hide legacy custom contact-info block from inserter (use Core Blocks + Social Icons instead)
add_filter('register_block_type_args', function($args, $name) {
    if ($name === 'kv/contact-info') {
        if (!isset($args['supports']) || !is_array($args['supports'])) {
            $args['supports'] = array();
        }
        $args['supports']['inserter'] = false;
    }
    return $args;
}, 20, 2);

// ============================================
// CUSTOM GUTENBERG BLOCKS — Contact Page
// ============================================

/**
 * Register custom contact page blocks with visual editor preview
 */
add_action('init', function() {
    // KV Contact Form Block
    register_block_type('kv/contact-form', array(
        'api_version' => 2,
        'editor_script' => 'kv-contact-blocks',
        'attributes' => array(
            'heading'            => array('type' => 'string', 'default' => 'Send Us a Message'),
            'subtitle'           => array('type' => 'string', 'default' => "Fill out the form below and we'll get back to you within 24 hours."),
            'headingSize'        => array('type' => 'number', 'default' => 24),
            'subtitleSize'       => array('type' => 'number', 'default' => 15),
            'headingColor'       => array('type' => 'string', 'default' => '#1e293b'),
            'subtitleColor'      => array('type' => 'string', 'default' => '#64748b'),
            'bgColor'            => array('type' => 'string', 'default' => '#ffffff'),
            'formBorderRadius'   => array('type' => 'number', 'default' => 12),
            'inputBg'            => array('type' => 'string', 'default' => '#f8fafc'),
            'inputBorderRadius'  => array('type' => 'number', 'default' => 8),
            'labelSize'          => array('type' => 'number', 'default' => 14),
            'nameLabel'          => array('type' => 'string', 'default' => 'Name'),
            'namePlaceholder'    => array('type' => 'string', 'default' => 'Your name'),
            'nameRequired'       => array('type' => 'boolean', 'default' => true),
            'companyLabel'       => array('type' => 'string', 'default' => 'Company'),
            'companyPlaceholder' => array('type' => 'string', 'default' => 'Your company'),
            'emailLabel'         => array('type' => 'string', 'default' => 'Email'),
            'emailPlaceholder'   => array('type' => 'string', 'default' => 'your@email.com'),
            'emailRequired'      => array('type' => 'boolean', 'default' => true),
            'phoneLabel'         => array('type' => 'string', 'default' => 'Phone'),
            'phonePlaceholder'   => array('type' => 'string', 'default' => '+66 xxx xxx xxxx'),
            'subjectLabel'       => array('type' => 'string', 'default' => 'Subject'),
            'subjectRequired'    => array('type' => 'boolean', 'default' => true),
            'subjectOptions'     => array('type' => 'string', 'default' => "General Inquiry\nRequest a Quote\nTechnical Support\nPartnership Opportunity\nOther"),
            'messageLabel'       => array('type' => 'string', 'default' => 'Message'),
            'messagePlaceholder' => array('type' => 'string', 'default' => 'How can we help you?'),
            'messageRequired'    => array('type' => 'boolean', 'default' => true),
            'consentText'        => array('type' => 'string', 'default' => 'I consent to KV Electronics collecting and storing my data for the purpose of responding to my inquiry in accordance with the Privacy Policy (PDPA).'),
            'buttonText'         => array('type' => 'string', 'default' => 'Send Message'),
            'buttonColor'        => array('type' => 'string', 'default' => '#4ecdc4'),
            'buttonTextColor'    => array('type' => 'string', 'default' => '#ffffff'),
            'buttonRadius'       => array('type' => 'number', 'default' => 8),
            'buttonSize'         => array('type' => 'number', 'default' => 16),
        ),
        'render_callback' => 'kv_render_contact_form_block'
    ));

    // KV Google Map Block
    register_block_type('kv/google-map', array(
        'api_version' => 2,
        'editor_script' => 'kv-contact-blocks',
        'attributes' => array(
            'mapUrl'         => array('type' => 'string',  'default' => ''),
            'height'         => array('type' => 'number',  'default' => 400),
            'directionsText' => array('type' => 'string',  'default' => 'Get Directions'),
            'viewMapText'    => array('type' => 'string',  'default' => 'View on Google Maps'),
            'callText'       => array('type' => 'string',  'default' => 'Call Us'),
            'addressLabel'   => array('type' => 'string',  'default' => 'Address'),
            'phoneLabel'     => array('type' => 'string',  'default' => 'Phone'),
            'hoursLabel'     => array('type' => 'string',  'default' => 'Business Hours'),
            'showInfoCard'   => array('type' => 'boolean', 'default' => true),
            'showAddress'    => array('type' => 'boolean', 'default' => true),
            'showPhone'      => array('type' => 'boolean', 'default' => true),
            'showHours'      => array('type' => 'boolean', 'default' => true),
            'showCTA'        => array('type' => 'boolean', 'default' => true),
            'cardBg'         => array('type' => 'string',  'default' => '#f8fafc'),
            'cardBorderRadius' => array('type' => 'number', 'default' => 12),
            'mapBorderRadius'  => array('type' => 'number', 'default' => 12),
            'buttonRadius'     => array('type' => 'number', 'default' => 8),
            'buttonFontSize'   => array('type' => 'number', 'default' => 14),
            'directionsBg'     => array('type' => 'string', 'default' => ''),
            'viewMapBg'        => array('type' => 'string', 'default' => '#ffffff'),
            'callBg'           => array('type' => 'string', 'default' => '#ffffff'),
            'callColor'        => array('type' => 'string', 'default' => '#16a34a'),
            'showCopy'         => array('type' => 'boolean', 'default' => true),
            'copyText'         => array('type' => 'string',  'default' => 'Copy Address'),
            'iconSize'         => array('type' => 'number',  'default' => 20),
            'wrapperMT'        => array('type' => 'number',  'default' => 40),
            'wrapperMB'        => array('type' => 'number',  'default' => 48),
        ),
        'render_callback' => 'kv_render_google_map_block'
    ));

    // KV Chat Buttons Block
    register_block_type('kv/chat-buttons', array(
        'api_version' => 2,
        'editor_script' => 'kv-contact-blocks',
        'attributes' => array(
            'layout' => array('type' => 'string', 'default' => 'horizontal')
        ),
        'render_callback' => 'kv_render_chat_buttons_block'
    ));

    // KV Quality Standards Block
    register_block_type('kv/quality-standards', array(
        'api_version' => 2,
        'editor_script' => 'kv-contact-blocks',
        'attributes' => array(
            'sectionLabel'   => array('type' => 'string',  'default' => 'QUALITY STANDARDS'),
            'sectionColor'   => array('type' => 'string',  'default' => '#64748b'),
            'sectionFontSize'=> array('type' => 'number',  'default' => 12),
            'bgColor'        => array('type' => 'string',  'default' => '#f8fafc'),
            'badgeSize'      => array('type' => 'number',  'default' => 90),
            'badgeGap'       => array('type' => 'number',  'default' => 20),
            'badges'         => array('type' => 'string',  'default' => '[{"label":"ISO","number":"9001","sub":"2015 CERTIFIED","color":"#22c55e","bg":"#f0fdf4","visible":true},{"label":"ISO","number":"14001","sub":"2015 CERTIFIED","color":"#22c55e","bg":"#f0fdf4","visible":true},{"label":"BOI","number":"","sub":"PROMOTED FACTORY","color":"#0056d6","bg":"#eff6ff","visible":true}]'),
        ),
        'render_callback' => 'kv_render_quality_standards_block'
    ));

    // KV Product Categories Block
    register_block_type('kv/product-categories', array(
        'api_version' => 2,
        'editor_script' => 'kv-contact-blocks',
        'attributes' => array(
            'columns'       => array('type' => 'number',  'default' => 3),
            'showImage'     => array('type' => 'boolean', 'default' => true),
            'showDesc'      => array('type' => 'boolean', 'default' => true),
            'showChildren'  => array('type' => 'boolean', 'default' => true),
            'maxChildren'   => array('type' => 'number',  'default' => 5),
            'visibleCards'  => array('type' => 'number',  'default' => 3),
            'buttonText'    => array('type' => 'string',  'default' => 'View Products →'),
        ),
        'render_callback' => 'kv_render_product_categories_block'
    ));

    // KV Applications Block
    register_block_type('kv/applications', array(
        'api_version' => 2,
        'editor_script' => 'kv-contact-blocks',
        'supports' => array('align' => array('full', 'wide')),
        'attributes' => array(
            'title'      => array('type' => 'string',  'default' => 'Applications'),
            'columns'    => array('type' => 'number',  'default' => 4),
            'bgColor'    => array('type' => 'string',  'default' => '#f8fafc'),
            'align'      => array('type' => 'string',  'default' => 'full'),
            'items'      => array('type' => 'string',  'default' => '[{"icon":"📡","title":"Telecommunications","desc":"5G infrastructure, network equipment"},{"icon":"🏭","title":"Industrial","desc":"Automation, motor drives, power supplies"},{"icon":"📱","title":"Consumer Electronics","desc":"Smartphones, IoT devices, wearables"}]'),
        ),
        'render_callback' => 'kv_render_applications_block'
    ));

    // KV Home Hero Block (editor preview + server render)
    register_block_type('kv/home-hero', array(
        'api_version' => 2,
        'editor_script' => 'kv-contact-blocks',
        'attributes' => array(
            'title' => array('type' => 'string', 'default' => 'KV Electronics | Home'),
            'subtitle' => array('type' => 'string', 'default' => ''),
            'primaryText' => array('type' => 'string', 'default' => 'View Products'),
            'primaryUrl' => array('type' => 'string', 'default' => '/products/'),
            'secondaryText' => array('type' => 'string', 'default' => 'Contact Us'),
            'secondaryUrl' => array('type' => 'string', 'default' => '/contact/'),
        ),
        'render_callback' => 'kv_render_home_hero_block'
    ));

    // KV Why Choose Us Block
    register_block_type('kv/why-choose', array(
        'api_version' => 2,
        'editor_script' => 'kv-contact-blocks',
        'attributes' => array(
            'title' => array('type' => 'string', 'default' => 'Why choose us'),
            'bodyLine1' => array('type' => 'string', 'default' => 'KV Electronics is more than a supplier—we are a long-term technical partner.'),
            'bodyLine2' => array('type' => 'string', 'default' => 'We support customers from design through mass production, ensuring stable quality, fast response, and continuous improvement.'),
        ),
        'render_callback' => 'kv_render_why_choose_block'
    ));

    // KV Ready to Get Started Block
    register_block_type('kv/ready-started', array(
        'api_version' => 2,
        'editor_script' => 'kv-contact-blocks',
        'attributes' => array(
            'title' => array('type' => 'string', 'default' => 'Ready to Get Started?'),
            'subtitle' => array('type' => 'string', 'default' => 'Contact us today for custom solutions and quotations'),
            'buttonText' => array('type' => 'string', 'default' => 'Get in Touch'),
            'buttonUrl' => array('type' => 'string', 'default' => '/contact/'),
        ),
        'render_callback' => 'kv_render_ready_started_block'
    ));

    // KV Contact Info Cards Block — fully editable (icons, titles, colors, data)
    register_block_type('kv/contact-info', array(
        'api_version' => 2,
        'editor_script' => 'kv-contact-blocks',
        'attributes' => array(
            'showAddress'  => array('type' => 'boolean', 'default' => true),
            'showPhone'    => array('type' => 'boolean', 'default' => true),
            'showEmail'    => array('type' => 'boolean', 'default' => true),
            'showHours'    => array('type' => 'boolean', 'default' => true),
            'showChat'     => array('type' => 'boolean', 'default' => true),
            'addressText'  => array('type' => 'string',  'default' => ''),
            'phoneText'    => array('type' => 'string',  'default' => ''),
            'emailText'    => array('type' => 'string',  'default' => ''),
            'hoursWeekday' => array('type' => 'string',  'default' => ''),
            'hoursWeekend' => array('type' => 'string',  'default' => ''),
            'addressIcon'  => array('type' => 'string',  'default' => "\xF0\x9F\x93\x8D"),
            'addressTitle' => array('type' => 'string',  'default' => 'Address'),
            'phoneIcon'    => array('type' => 'string',  'default' => "\xF0\x9F\x93\x9E"),
            'phoneTitle'   => array('type' => 'string',  'default' => 'Phone'),
            'emailIcon'    => array('type' => 'string',  'default' => "\xE2\x9C\x89\xEF\xB8\x8F"),
            'emailTitle'   => array('type' => 'string',  'default' => 'Email'),
            'hoursIcon'    => array('type' => 'string',  'default' => "\xF0\x9F\x95\x90"),
            'hoursTitle'   => array('type' => 'string',  'default' => 'Business Hours'),
            'chatIcon'     => array('type' => 'string',  'default' => "\xF0\x9F\x92\xAC"),
            'chatTitle'    => array('type' => 'string',  'default' => 'Chat with Us'),
            'chatLineLabel'     => array('type' => 'string',  'default' => 'LINE'),
            'chatWhatsappLabel' => array('type' => 'string',  'default' => 'WhatsApp'),
            'chatWechatLabel'   => array('type' => 'string',  'default' => 'WeChat'),
            'iconBg'       => array('type' => 'string',  'default' => '#e8f0fe'),
            'titleColor'   => array('type' => 'string',  'default' => '#1e293b'),
            'textColor'    => array('type' => 'string',  'default' => '#64748b'),
        ),
        'render_callback' => 'kv_render_contact_info_block'
    ));

    // KV About Intro Block (Section 1)
    register_block_type('kv/about-intro', array(
        'api_version' => 2,
        'editor_script' => 'kv-about-blocks',
        'render_callback' => function() { return do_shortcode('[kv_about_intro]'); }
    ));

    // KV About S2 Block (Section 2)
    register_block_type('kv/about-s2', array(
        'api_version' => 2,
        'editor_script' => 'kv-about-blocks',
        'render_callback' => function() { return do_shortcode('[kv_about_s2]'); }
    ));
});

// Enqueue block editor script
add_action('enqueue_block_editor_assets', function() {
    wp_enqueue_script(
        'kv-contact-blocks',
        get_template_directory_uri() . '/assets/js/blocks/contact-blocks.js',
        array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-api-fetch'),
        filemtime(get_template_directory() . '/assets/js/blocks/contact-blocks.js') . '-' . time(),
        true
    );
    wp_enqueue_script(
        'kv-about-blocks',
        get_template_directory_uri() . '/assets/js/blocks/about-blocks.js',
        array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-api-fetch'),
        file_exists(get_template_directory() . '/assets/js/blocks/about-blocks.js')
            ? filemtime(get_template_directory() . '/assets/js/blocks/about-blocks.js')
            : null,
        true
    );
    wp_enqueue_script(
        'kv-global-blocks',
        get_template_directory_uri() . '/assets/js/blocks/global-blocks.js',
        array('wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-api-fetch'),
        file_exists(get_template_directory() . '/assets/js/blocks/global-blocks.js')
            ? filemtime(get_template_directory() . '/assets/js/blocks/global-blocks.js')
            : null,
        true
    );
});

// Register Global Blocks (Navbar + Footer site-wide settings)
add_action('init', function() {
    register_block_type('kv/site-navbar', array(
        'api_version'     => 2,
        'editor_script'   => 'kv-global-blocks',
        'render_callback' => '__return_empty_string',
    ));
    register_block_type('kv/site-footer', array(
        'api_version'     => 2,
        'editor_script'   => 'kv-global-blocks',
        'render_callback' => '__return_empty_string',
    ));
});

// REST API for Site-wide Options (used by block editor panels)
add_action('rest_api_init', function() {
    $s_text = array(
        'site_phone','site_fax','site_email','site_email_sales','site_address','site_company_name',
        'site_copyright','site_hours_weekday','site_hours_weekend',
        'chat_line_enabled','chat_line_id',
        'chat_wechat_enabled','chat_wechat_id',
        'chat_whatsapp_enabled','chat_whatsapp_number',
        'nav_logo_alt','site_years_experience','site_total_products',
        'site_countries_served','site_happy_customers','site_founded_year','banner_overlay','banner_fadein_delay',
        'banner_video_start','banner_video_end',
        // Navbar editable labels/urls
        'nav_home_label','nav_home_visible','nav_about_label','nav_about_url','nav_about_visible',
        'nav_products_label','nav_products_visible','nav_contact_label','nav_contact_url','nav_contact_visible',
        'nav_cta_text','nav_cta_url','nav_cta_visible',
        // Navbar style settings
        'nav_font_weight','nav_align','nav_sticky','nav_shadow',
        'nav_font_size','nav_padding_y','nav_logo_height','nav_cta_radius','nav_cta_font_size',
        // Footer column titles
        'footer_col1_title','footer_col2_title','footer_col3_title','footer_col4_title',
        // Gallery
        'gallery_interval',
    );
    $s_url   = array('site_logo_url','site_logo_light_url','site_map_embed','chat_wechat_qr_url','banner_bg_image','banner_bg_video','social_facebook_url','social_instagram_url','social_linkedin_url');
    $s_float = array('banner_video_start','banner_video_end');
    $s_color = array('theme_primary_color','theme_accent_color','theme_bg_color','banner_bg_color',
        'nav_bg_color','nav_text_color','nav_hover_color','nav_active_color','nav_cta_bg','nav_cta_text_color',
    );
    $s_ta    = array('site_address_full','footer_about_text','footer_quick_links','nav_custom_items','about_certifications_json');
    $s_nav_json = array('nav_menu_items_json');

    register_rest_route('kv/v1', '/site-options', array(
        array(
            'methods'             => 'GET',
            'callback'            => function() use ($s_text, $s_url, $s_color, $s_ta, $s_nav_json) {
                $data = array();
                foreach (array_merge($s_text,$s_url,$s_color,$s_ta,$s_nav_json) as $k) $data[$k] = get_option($k, '');
                return rest_ensure_response($data);
            },
            'permission_callback' => '__return_true',
        ),
        array(
            'methods'             => 'POST',
            'callback'            => function(WP_REST_Request $req) use ($s_text, $s_url, $s_float, $s_color, $s_ta, $s_nav_json) {
                if (!current_user_can('manage_options')) {
                    return new WP_Error('forbidden', 'Forbidden', array('status' => 403));
                }
                $body = $req->get_json_params();
                foreach ($s_text as $k) {
                    if (!isset($body[$k])) continue;
                    if (in_array($k, $s_float, true)) {
                        update_option($k, max(0, (float) $body[$k]));
                    } elseif (in_array($k, ['site_email','site_email_sales'], true)) {
                        update_option($k, sanitize_email($body[$k]));
                    } else {
                        update_option($k, sanitize_text_field($body[$k]));
                    }
                }
                foreach ($s_url  as $k) { if (isset($body[$k])) update_option($k, esc_url_raw($body[$k])); }
                foreach ($s_color as $k) { if (isset($body[$k])) update_option($k, sanitize_hex_color($body[$k]) ?? ''); }
                foreach ($s_ta   as $k) {
                    if (!isset($body[$k])) continue;
                    if ($k === 'about_certifications_json') {
                        update_option($k, my_theme_sanitize_about_certifications_json($body[$k]));
                    } else {
                        update_option($k, sanitize_textarea_field($body[$k]));
                    }
                }
                foreach ($s_nav_json as $k) {
                    if (!isset($body[$k])) continue;
                    update_option($k, my_theme_sanitize_nav_menu_items_json($body[$k]));
                }
                $primary_sync = sanitize_hex_color(get_option('theme_primary_color', '#0056d6')) ?: '#0056d6';
                update_option('banner_bg_color', $primary_sync);
                // Clear page caches so the frontend reflects nav changes immediately
                my_theme_flush_page_caches();
                $resp = rest_ensure_response(array('success' => true));
                $resp->header( 'X-LiteSpeed-Purge', '*' );
                $resp->header( 'Cache-Control', 'no-cache, no-store, must-revalidate' );
                return $resp;
            },
            'permission_callback' => function() { return current_user_can('manage_options'); },
        ),
    ));
});

// REST API: flush page caches on demand
add_action('rest_api_init', function() {
    register_rest_route('kv/v1', '/flush-cache', array(
        'methods'             => 'POST',
        'callback'            => function() {
            if (!current_user_can('manage_options')) {
                return new WP_Error('forbidden', 'Forbidden', array('status' => 403));
            }
            my_theme_flush_page_caches();
            $resp = rest_ensure_response(array('success' => true, 'message' => 'Cache flushed'));
            $resp->header( 'X-LiteSpeed-Purge', '*' );
            $resp->header( 'Cache-Control', 'no-cache, no-store, must-revalidate' );
            return $resp;
        },
        'permission_callback' => function() { return current_user_can('manage_options'); },
    ));
});

// REST API for Product Categories (block editor editing)
add_action('rest_api_init', function() {
    // GET all root categories with meta
    register_rest_route('kv/v1', '/product-categories', array(
        array(
            'methods'             => 'GET',
            'callback'            => function() {
                $terms = get_terms(array(
                    'taxonomy'   => 'product_category',
                    'parent'     => 0,
                    'hide_empty' => false,
                    'orderby'    => 'id',
                    'order'      => get_option('my_theme_product_category_order', 'DESC'),
                ));
                if (is_wp_error($terms)) return rest_ensure_response(array());
                $data = array();
                foreach ($terms as $term) {
                    $children = get_terms(array(
                        'taxonomy'   => 'product_category',
                        'parent'     => $term->term_id,
                        'hide_empty' => false,
                    ));
                    $child_names = array();
                    if (!is_wp_error($children)) {
                        foreach ($children as $c) $child_names[] = $c->name;
                    }
                    $data[] = array(
                        'id'          => $term->term_id,
                        'name'        => $term->name,
                        'slug'        => $term->slug,
                        'description' => get_term_meta($term->term_id, 'cat_description', true) ?: $term->description,
                        'cat_image'   => get_term_meta($term->term_id, 'cat_image', true),
                        'link'        => get_term_link($term),
                        'children'    => $child_names,
                    );
                }
                return rest_ensure_response($data);
            },
            'permission_callback' => '__return_true',
        ),
    ));

    // POST/PATCH update a single category
    register_rest_route('kv/v1', '/product-category/(?P<id>\d+)', array(
        array(
            'methods'             => 'POST',
            'callback'            => function(WP_REST_Request $req) {
                if (!current_user_can('manage_categories')) {
                    return new WP_Error('forbidden', 'Forbidden', array('status' => 403));
                }
                $id   = (int) $req->get_param('id');
                $body = $req->get_json_params();
                if (!term_exists($id, 'product_category')) {
                    return new WP_Error('not_found', 'Term not found', array('status' => 404));
                }
                $upd = array();
                if (isset($body['name']))        $upd['name'] = sanitize_text_field($body['name']);
                if (isset($body['description'])) $upd['description'] = sanitize_textarea_field($body['description']);
                if (!empty($upd)) wp_update_term($id, 'product_category', $upd);
                if (isset($body['cat_image']))       update_term_meta($id, 'cat_image',       esc_url_raw($body['cat_image']));
                if (isset($body['cat_description'])) update_term_meta($id, 'cat_description', sanitize_textarea_field($body['cat_description']));
                return rest_ensure_response(array('success' => true, 'id' => $id));
            },
            'permission_callback' => function() { return current_user_can('manage_categories'); },
        ),
    ));
});

// REST API for About Us block options
add_action('rest_api_init', function() {
    register_rest_route('kv/v1', '/about-options', array(
        array(
            'methods'             => 'GET',
            'callback'            => function() {
                $keys = array(
                    'about_s1_title1','about_s1_text1','about_s1_title2','about_s1_text2',
                    'about_s1_text3','about_s1_image',
                    'about_s2_title1','about_s2_text1','about_s2_title2','about_s2_text2',
                    'about_s2_text3','about_s2_image'
                );
                $data = array();
                foreach ($keys as $k) $data[$k] = get_option($k, '');
                return rest_ensure_response($data);
            },
            'permission_callback' => '__return_true',
        ),
        array(
            'methods'             => 'POST',
            'callback'            => function(WP_REST_Request $req) {
                if (!current_user_can('manage_options')) {
                    return new WP_Error('forbidden', 'Forbidden', array('status' => 403));
                }
                $body = $req->get_json_params();
                $text_keys = array(
                    'about_s1_title1','about_s1_text1','about_s1_title2','about_s1_text2','about_s1_text3',
                    'about_s2_title1','about_s2_text1','about_s2_title2','about_s2_text2','about_s2_text3'
                );
                $url_keys = array('about_s1_image','about_s2_image');
                foreach ($text_keys as $k) {
                    if (isset($body[$k])) update_option($k, sanitize_textarea_field($body[$k]));
                }
                foreach ($url_keys as $k) {
                    if (isset($body[$k])) update_option($k, esc_url_raw($body[$k]));
                }
                return rest_ensure_response(array('success' => true));
            },
            'permission_callback' => function() { return current_user_can('manage_options'); },
        ),
    ));
});

/**
 * Render Contact Form Block
 */
function kv_render_contact_form_block($attributes) {
    $theme_primary = get_option('theme_primary_color', '#0056d6');
    $theme_accent  = get_option('theme_accent_color',  '#4ecdc4');

    // Read all attributes with defaults
    $heading      = isset($attributes['heading'])      ? $attributes['heading']      : 'Send Us a Message';
    $subtitle     = isset($attributes['subtitle'])     ? $attributes['subtitle']     : "Fill out the form below and we'll get back to you within 24 hours.";
    $nameLabel    = isset($attributes['nameLabel'])    ? $attributes['nameLabel']    : 'Name';
    $namePh       = isset($attributes['namePlaceholder']) ? $attributes['namePlaceholder'] : 'Your name';
    $nameReq      = isset($attributes['nameRequired']) ? (bool)$attributes['nameRequired'] : true;
    $compLabel    = isset($attributes['companyLabel']) ? $attributes['companyLabel'] : 'Company';
    $compPh       = isset($attributes['companyPlaceholder']) ? $attributes['companyPlaceholder'] : 'Your company';
    $emailLabel   = isset($attributes['emailLabel'])   ? $attributes['emailLabel']   : 'Email';
    $emailPh      = isset($attributes['emailPlaceholder']) ? $attributes['emailPlaceholder'] : 'your@email.com';
    $emailReq     = isset($attributes['emailRequired']) ? (bool)$attributes['emailRequired'] : true;
    $phoneLabel   = isset($attributes['phoneLabel'])   ? $attributes['phoneLabel']   : 'Phone';
    $phonePh      = isset($attributes['phonePlaceholder']) ? $attributes['phonePlaceholder'] : '+66 xxx xxx xxxx';
    $subjectLabel = isset($attributes['subjectLabel']) ? $attributes['subjectLabel'] : 'Subject';
    $subjectReq   = isset($attributes['subjectRequired']) ? (bool)$attributes['subjectRequired'] : true;
    $subjectOpts  = isset($attributes['subjectOptions']) ? $attributes['subjectOptions'] : "General Inquiry\nRequest a Quote\nTechnical Support\nPartnership Opportunity\nOther";
    $msgLabel     = isset($attributes['messageLabel']) ? $attributes['messageLabel'] : 'Message';
    $msgPh        = isset($attributes['messagePlaceholder']) ? $attributes['messagePlaceholder'] : 'How can we help you?';
    $msgReq       = isset($attributes['messageRequired']) ? (bool)$attributes['messageRequired'] : true;
    $consentText  = isset($attributes['consentText'])  ? $attributes['consentText']  : 'I consent to KV Electronics collecting and storing my data for the purpose of responding to my inquiry in accordance with the Privacy Policy (PDPA).';
    $buttonText   = isset($attributes['buttonText'])   ? $attributes['buttonText']   : 'Send Message';
    $buttonColor  = (!empty($attributes['buttonColor']) && $attributes['buttonColor'] !== '#4ecdc4') ? $attributes['buttonColor'] : $theme_accent;
    $buttonTxtClr = isset($attributes['buttonTextColor']) ? $attributes['buttonTextColor'] : '#ffffff';
    $buttonRadius = isset($attributes['buttonRadius']) ? (int)$attributes['buttonRadius'] : 8;
    $buttonFSize  = isset($attributes['buttonSize'])   ? (int)$attributes['buttonSize']   : 16;
    $headingSize  = isset($attributes['headingSize'])  ? (int)$attributes['headingSize']  : 24;
    $subtitleSize = isset($attributes['subtitleSize']) ? (int)$attributes['subtitleSize'] : 15;
    $headingColor = isset($attributes['headingColor']) ? $attributes['headingColor'] : '#1e293b';
    $subtitleColor= isset($attributes['subtitleColor'])? $attributes['subtitleColor']: '#64748b';
    $bgColor      = isset($attributes['bgColor'])      ? $attributes['bgColor']      : '#ffffff';
    $formBRadius  = isset($attributes['formBorderRadius']) ? (int)$attributes['formBorderRadius'] : 12;
    $inputBg      = isset($attributes['inputBg'])      ? $attributes['inputBg']      : '#f8fafc';
    $inputBRadius = isset($attributes['inputBorderRadius']) ? (int)$attributes['inputBorderRadius'] : 8;
    $labelSize    = isset($attributes['labelSize'])    ? (int)$attributes['labelSize']    : 14;

    $labelS = 'display:block;font-weight:600;margin-bottom:8px;color:#1e293b;font-size:' . $labelSize . 'px;';
    $inputS = 'width:100%;padding:12px 16px;border:1px solid #e2e8f0;border-radius:' . $inputBRadius . 'px;font-size:16px;box-sizing:border-box;background:' . esc_attr($inputBg) . ';';

    // Build subject <option> tags from newline-separated string
    $subjectLines = array_filter(array_map('trim', explode("\n", $subjectOpts)));

    ob_start();
    ?>
    <div class="kv-contact-form-wrapper" style="background:<?php echo esc_attr($bgColor); ?>;border-radius:<?php echo $formBRadius; ?>px;padding:30px;box-shadow:0 4px 6px -1px rgba(0,0,0,0.1);">
        <h3 style="font-size:<?php echo $headingSize; ?>px;margin:0 0 10px;color:<?php echo esc_attr($headingColor); ?>;"><?php echo wp_kses_post($heading); ?></h3>
        <p style="color:<?php echo esc_attr($subtitleColor); ?>;margin:0 0 25px;font-size:<?php echo $subtitleSize; ?>px;"><?php echo wp_kses_post($subtitle); ?></p>
        
        <form id="cf-form" class="needs-validation" novalidate>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
                <div>
                    <label for="cf-block-name" style="<?php echo $labelS; ?>">Contact name</label>
                    <input type="text" id="cf-block-name" name="name" <?php if($nameReq) echo 'required'; ?>
                           minlength="2" maxlength="100" class="form-control"
                           style="<?php echo $inputS; ?>" placeholder="<?php echo esc_attr($namePh); ?>">
                    <div class="invalid-feedback">Please enter contact name (2-100 characters).</div>
                </div>
                <div>
                    <label for="cf-block-company" style="<?php echo $labelS; ?>">Company name</label>
                    <input type="text" id="cf-block-company" name="company" required minlength="2" maxlength="150" class="form-control"
                           style="<?php echo $inputS; ?>" placeholder="<?php echo esc_attr($compPh); ?>">
                    <div class="invalid-feedback">Please enter company name (2-150 characters).</div>
                </div>
            </div>
            
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
                <div>
                    <label for="cf-block-industry" style="<?php echo $labelS; ?>">Industry</label>
                    <select id="cf-block-industry" name="industry" required class="form-select" style="<?php echo $inputS; ?>background:white;">
                        <option value="">Please Select</option>
                        <option>Aerospace</option><option>Agriculture</option><option>Computer</option><option>Construction</option><option>Education</option><option>Electronics</option><option>Energy</option><option>Entertainment</option><option>Food</option><option>Health care</option><option>Hospitality</option><option>Manufacturing</option><option>Mining</option><option>Music</option><option>News Media</option><option>Pharmaceutical</option><option>Telecommunication</option><option>Transport</option><option>Worldwide web</option><option>Other</option>
                    </select>
                    <div class="invalid-feedback">Please select your industry.</div>
                </div>
                <div>
                    <label for="cf-block-organization" style="<?php echo $labelS; ?>">Organization type</label>
                    <select id="cf-block-organization" name="organization_type" required class="form-select" style="<?php echo $inputS; ?>background:white;">
                        <option value="">Please Select</option>
                        <option>Individual</option><option>Company</option><option>Start-Up</option><option>Innovator</option><option>Organization</option>
                    </select>
                    <div class="invalid-feedback">Please select organization type.</div>
                </div>
            </div>
            
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
                <div>
                    <label for="cf-block-country" style="<?php echo $labelS; ?>">Country</label>
                    <select id="cf-block-country" name="country" required class="form-select" style="<?php echo $inputS; ?>background:white;">
                        <option value="">Please Select</option>
                        <option>Thailand</option><option>Singapore</option><option>Malaysia</option><option>Viet Nam</option><option>Indonesia</option><option>Philippines (the)</option><option>Japan</option><option>Republic of Korea (the)</option><option>China</option><option>India</option><option>Australia</option><option>New Zealand</option><option>United States of America (the)</option><option>Canada</option><option>Mexico</option><option>Brazil</option><option>United Kingdom of Great Britain and Northern Ireland (the)</option><option>Germany</option><option>France</option><option>Netherlands (the)</option><option>Switzerland</option><option>Sweden</option><option>United Arab Emirates (the)</option><option>Saudi Arabia</option><option>South Africa</option><option>Other</option>
                    </select>
                    <div class="invalid-feedback">Please select country.</div>
                </div>
                <div>
                    <label for="cf-block-job" style="<?php echo $labelS; ?>">Job title</label>
                    <input type="text" id="cf-block-job" name="job_title" required minlength="2" maxlength="100" class="form-control" style="<?php echo $inputS; ?>" placeholder="Your job title">
                    <div class="invalid-feedback">Please enter job title (2-100 characters).</div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
                <div>
                          <label for="cf-block-email" style="<?php echo $labelS; ?>"><?php echo esc_html($emailLabel); ?></label>
                    <input type="email" id="cf-block-email" name="email" <?php if($emailReq) echo 'required'; ?>
                              maxlength="150" class="form-control"
                              style="<?php echo $inputS; ?>" placeholder="<?php echo esc_attr($emailPh); ?>">
                          <div class="invalid-feedback">Please enter a valid email address.</div>
                </div>
                <div>
                          <label for="cf-block-phone" style="<?php echo $labelS; ?>">Phone number</label>
                          <input type="tel" id="cf-block-phone" name="phone" required minlength="9" maxlength="10" pattern="[0-9]{9,10}" inputmode="numeric" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)" class="form-control"
                           style="<?php echo $inputS; ?>" placeholder="<?php echo esc_attr($phonePh); ?>">
                          <div class="invalid-feedback">Please enter a valid phone number (9-10 digits).</div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
                <div>
                    <label for="cf-block-interested" style="<?php echo $labelS; ?>">Interested products <span style="font-size:12px;color:#64748b;font-weight:400;">Optional</span></label>
                    <input type="text" id="cf-block-interested" name="interested_products" maxlength="200" class="form-control" style="<?php echo $inputS; ?>" placeholder="Interested products">
                </div>
                <div>
                    <label for="cf-block-file" style="<?php echo $labelS; ?>">File upload <span style="font-size:12px;color:#64748b;font-weight:400;">Optional</span></label>
                    <input type="file" id="cf-block-file" name="file_upload" class="form-control" accept="application/msword, application/vnd.ms-excel, application/vnd.ms-powerpoint, text/plain, application/pdf, image/*" style="<?php echo $inputS; ?>padding:10px 12px;">
                </div>
            </div>
            
            <div style="margin-bottom:20px;">
                <label for="cf-block-message" style="<?php echo $labelS; ?>"><?php echo esc_html($msgLabel); ?></label>
                <textarea id="cf-block-message" name="message" rows="5" <?php if($msgReq) echo 'required'; ?>
                          minlength="10" maxlength="2000" class="form-control"
                          style="<?php echo $inputS; ?>resize:vertical;"
                          placeholder="<?php echo esc_attr($msgPh); ?>"></textarea>
                <div class="invalid-feedback">Please enter message (10-2000 characters).</div>
            </div>
            
            <div style="margin-bottom:20px;">
                <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;">
                    <input type="checkbox" id="cf-block-pdpa" name="pdpa_consent" required class="form-check-input" style="margin-top:4px;min-width:16px;height:16px;cursor:pointer;"
                           onchange="var b=document.getElementById('cf-submit-btn');b.disabled=!this.checked;b.style.opacity=this.checked?'1':'0.5';">
                    <span style="font-size:14px;color:#64748b;"><?php echo wp_kses_post($consentText); ?></span>
                </label>
                <div class="invalid-feedback">Please accept the Privacy Policy (PDPA).</div>
            </div>
            
            <div id="cf-result" style="display:none;padding:12px 16px;border-radius:8px;margin-bottom:12px;font-size:14px;"></div>

            <button type="submit" id="cf-submit-btn" disabled
                    style="background:<?php echo esc_attr($buttonColor); ?>;color:<?php echo esc_attr($buttonTxtClr); ?>;border:none;padding:14px 32px;border-radius:<?php echo $buttonRadius; ?>px;font-size:<?php echo $buttonFSize; ?>px;font-weight:600;cursor:pointer;width:100%;transition:opacity 0.2s;opacity:0.5;"
                    onmouseover="if(!this.disabled)this.style.opacity='0.9'" onmouseout="this.style.opacity=this.disabled?'0.5':'1'">
                <?php echo esc_html($buttonText); ?>
            </button>
        </form>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render Google Map Block
 */
function kv_render_google_map_block($attributes) {
    $map_url        = $attributes['mapUrl'] ?? '';
    $height         = $attributes['height'] ?? 480;
    $directions_txt = $attributes['directionsText'] ?? 'Get Directions';
    $viewmap_txt    = $attributes['viewMapText']    ?? 'View on Google Maps';
    $call_txt       = $attributes['callText']       ?? 'Call Us';
    $addr_label     = $attributes['addressLabel']   ?? 'Address';
    $phone_label    = $attributes['phoneLabel']     ?? 'Phone';
    $hours_label    = $attributes['hoursLabel']     ?? 'Business Hours';
    $show_info      = isset($attributes['showInfoCard']) ? (bool) $attributes['showInfoCard'] : true;
    $show_addr      = isset($attributes['showAddress'])  ? (bool) $attributes['showAddress']  : true;
    $show_phone     = isset($attributes['showPhone'])    ? (bool) $attributes['showPhone']    : true;
    $show_hours     = isset($attributes['showHours'])    ? (bool) $attributes['showHours']    : true;
    $show_cta       = isset($attributes['showCTA'])      ? (bool) $attributes['showCTA']      : true;
    $card_bg        = $attributes['cardBg'] ?? '#f8fafc';
    $card_br        = isset($attributes['cardBorderRadius']) ? (int)$attributes['cardBorderRadius'] : 12;
    $map_br         = isset($attributes['mapBorderRadius'])  ? (int)$attributes['mapBorderRadius']  : 12;
    $btn_radius     = isset($attributes['buttonRadius'])     ? (int)$attributes['buttonRadius']     : 8;
    $btn_fs         = isset($attributes['buttonFontSize'])   ? (int)$attributes['buttonFontSize']   : 14;
    $directions_bg  = $attributes['directionsBg'] ?? '';
    $viewmap_bg     = $attributes['viewMapBg']    ?? '#ffffff';
    $call_bg        = $attributes['callBg']       ?? '#ffffff';
    $call_color     = $attributes['callColor']    ?? '#16a34a';
    $show_copy      = isset($attributes['showCopy']) ? (bool) $attributes['showCopy'] : true;
    $copy_txt       = $attributes['copyText']     ?? 'Copy Address';
    $icon_size      = isset($attributes['iconSize']) ? (int)$attributes['iconSize'] : 20;
    $wrapper_mt     = isset($attributes['wrapperMT']) ? (int)$attributes['wrapperMT'] : 0;
    $wrapper_mb     = isset($attributes['wrapperMB']) ? (int)$attributes['wrapperMB'] : 0;

    $address_full = get_option('site_address_full',
        "988 Moo 2, Soi Thetsaban Bang Poo 60\nSukhumvit Road, Tumbol Thai Ban\nAmphur Muang, Samut Prakan 10280\nThailand");
    $address_one  = get_option('site_address',
        '988 Moo 2, Soi Thetsaban Bang Poo 60, Sukhumvit Road, Tumbol Thai Ban, Amphur Muang, Samut Prakan 10280, Thailand');
    $phone        = kv_format_phone_th(get_option('site_phone', ''));
    $phone_raw    = get_option('site_phone', '');
    $hours_wd     = get_option('site_hours_weekday', 'Monday – Friday: 8:00 AM – 5:00 PM');
    $hours_we     = get_option('site_hours_weekend', 'Saturday – Sunday: Closed');
    $theme_primary = get_option('theme_primary_color', '#0056d6');

    if (!is_admin() && is_page(array('contact', 'contacts'))) {
        $show_info = false;
    }

    $q             = urlencode($address_one);
    $directions_url = 'https://www.google.com/maps/dir/?api=1&destination=' . $q;
    $view_url       = 'https://www.google.com/maps/search/?api=1&query=' . $q;

    if (empty($map_url)) {
        $map_url = get_option('site_map_embed',
            'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3875.8!2d100.6!3d13.7!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMTPCsDQyJzAwLjAiTiAxMDDCsDM2JzAwLjAiRQ!5e0!3m2!1sen!2sth!4v1234567890');
    }

    ob_start();
    ?>
    <div class="kv-google-map-wrapper alignfull" style="margin-top:<?php echo $wrapper_mt; ?>px;margin-bottom:<?php echo $wrapper_mb; ?>px;">
        <div style="overflow:hidden;">
            <iframe src="<?php echo esc_url($map_url); ?>" width="100%" height="<?php echo esc_attr($height); ?>" style="border:0;display:block;width:100%;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>

        <?php if ($show_info) : ?>
        <div style="margin-top:16px;background:<?php echo esc_attr($card_bg); ?>;border:1px solid #e2e8f0;border-radius:<?php echo $card_br; ?>px;padding:20px 24px;display:flex;flex-wrap:wrap;gap:20px;align-items:flex-start;justify-content:space-between;">
            <div style="flex:1;min-width:220px;">
                <?php if ($show_addr) : ?>
                <div style="display:flex;gap:10px;align-items:flex-start;margin-bottom:14px;">
                    <span style="font-size:<?php echo $icon_size; ?>px;line-height:1;">📍</span>
                    <div>
                        <div style="font-weight:600;color:#1e293b;margin-bottom:2px;"><?php echo wp_kses_post($addr_label); ?></div>
                        <div style="color:#64748b;font-size:14px;line-height:1.6;"><?php echo nl2br(esc_html($address_full)); ?></div>
                        <?php if ($show_copy) : ?>
                        <button onclick="navigator.clipboard.writeText(<?php echo json_encode($address_one); ?>).then(function(){var b=this;b.textContent='\u2713 Copied!';setTimeout(function(){b.textContent='<?php echo esc_js($copy_txt); ?>';},2000)}.bind(this))"
                                style="margin-top:6px;background:none;border:1px solid #cbd5e1;border-radius:6px;padding:3px 10px;font-size:12px;color:#475569;cursor:pointer;transition:background 0.2s;"
                                onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='none'">
                            <?php echo esc_html($copy_txt); ?>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($show_phone && $phone) : ?>
                <div style="display:flex;gap:10px;align-items:center;margin-bottom:14px;">
                    <span style="font-size:<?php echo $icon_size; ?>px;line-height:1;">📞</span>
                    <div>
                        <div style="font-weight:600;color:#1e293b;margin-bottom:2px;"><?php echo wp_kses_post($phone_label); ?></div>
                        <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $phone_raw)); ?>" style="color:<?php echo esc_attr($theme_primary); ?>;font-size:15px;font-weight:600;text-decoration:none;"><?php echo esc_html($phone); ?></a>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($show_hours && $hours_wd) : ?>
                <div style="display:flex;gap:10px;align-items:flex-start;">
                    <span style="font-size:<?php echo $icon_size; ?>px;line-height:1;">🕐</span>
                    <div>
                        <div style="font-weight:600;color:#1e293b;margin-bottom:2px;"><?php echo wp_kses_post($hours_label); ?></div>
                        <div style="color:#64748b;font-size:14px;line-height:1.6;"><?php echo esc_html($hours_wd); ?><br><?php echo esc_html($hours_we); ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($show_cta) : ?>
            <div style="display:flex;flex-direction:column;gap:10px;min-width:180px;">
                <a href="<?php echo esc_url($directions_url); ?>" target="_blank" rel="noopener"
                   style="display:flex;align-items:center;justify-content:center;gap:8px;background:<?php echo esc_attr($directions_bg ?: $theme_primary); ?>;color:#fff;padding:12px 20px;border-radius:<?php echo $btn_radius; ?>px;font-weight:600;font-size:<?php echo $btn_fs; ?>px;text-decoration:none;transition:opacity 0.2s;"
                   onmouseover="this.style.opacity='0.85'" onmouseout="this.style.opacity='1'">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M21.71 11.29l-9-9a1 1 0 0 0-1.42 0l-9 9a1 1 0 0 0 0 1.42l9 9a1 1 0 0 0 1.42 0l9-9a1 1 0 0 0 0-1.42zM14 14.5V12h-4v3H8v-4a1 1 0 0 1 1-1h5V7.5l3.5 3.5-3.5 3.5z"/></svg>
                    <?php echo wp_kses_post($directions_txt); ?>
                </a>
                <a href="<?php echo esc_url($view_url); ?>" target="_blank" rel="noopener"
                   style="display:flex;align-items:center;justify-content:center;gap:8px;background:<?php echo esc_attr($viewmap_bg); ?>;color:<?php echo esc_attr($theme_primary); ?>;padding:12px 20px;border-radius:<?php echo $btn_radius; ?>px;font-weight:600;font-size:<?php echo $btn_fs; ?>px;text-decoration:none;border:2px solid <?php echo esc_attr($theme_primary); ?>;transition:background 0.2s;"
                   onmouseover="this.style.background='<?php echo esc_attr($theme_primary); ?>';this.style.color='#fff'" onmouseout="this.style.background='<?php echo esc_attr($viewmap_bg); ?>';this.style.color='<?php echo esc_attr($theme_primary); ?>'">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                    <?php echo wp_kses_post($viewmap_txt); ?>
                </a>
                <?php if ($phone_raw) : ?>
                <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $phone_raw)); ?>"
                   style="display:flex;align-items:center;justify-content:center;gap:8px;background:<?php echo esc_attr($call_bg); ?>;color:<?php echo esc_attr($call_color); ?>;padding:12px 20px;border-radius:<?php echo $btn_radius; ?>px;font-weight:600;font-size:<?php echo $btn_fs; ?>px;text-decoration:none;border:2px solid <?php echo esc_attr($call_color); ?>;transition:background 0.2s;"
                   onmouseover="this.style.background='<?php echo esc_attr($call_color); ?>';this.style.color='#fff'" onmouseout="this.style.background='<?php echo esc_attr($call_bg); ?>';this.style.color='<?php echo esc_attr($call_color); ?>'">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M6.6 10.8c1.4 2.8 3.8 5.1 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1-9.4 0-17-7.6-17-17 0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1l-2.3 2.2z"/></svg>
                    <?php echo wp_kses_post($call_txt); ?>
                </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render Chat Buttons Block
 */
function kv_render_chat_buttons_block($attributes) {
    $layout = $attributes['layout'] ?? 'horizontal';
    $flex_direction = $layout === 'vertical' ? 'column' : 'row';
    
    $line_on    = get_option('chat_line_enabled',    '1');
    $line_id    = get_option('chat_line_id',         'kvelectronics');
    $wc_on      = get_option('chat_wechat_enabled',  '1');
    $wechat_id  = get_option('chat_wechat_id',       get_option('contact_wechat', 'kvelectronics'));
    $wa_on      = get_option('chat_whatsapp_enabled', '1');
    $whatsapp   = get_option('chat_whatsapp_number', get_option('contact_whatsapp', '+66812345678'));
    
    ob_start();
    ?>
    <div class="kv-chat-buttons" style="display:flex;flex-direction:<?php echo esc_attr($flex_direction); ?>;flex-wrap:wrap;gap:12px;">
        <?php if ($line_on === '1' && $line_id) : ?>
        <a href="https://line.me/ti/p/~<?php echo esc_attr($line_id); ?>" target="_blank" rel="noopener"
           style="display:inline-flex;align-items:center;gap:10px;padding:12px 24px;background:#06c755;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;transition:opacity 0.2s;"
           onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63.349 0 .631.285.631.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.349 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.281.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
            </svg>
            LINE
        </a>
        <?php endif; ?>
        
        <?php if ($wc_on === '1') : ?>
        <a href="weixin://dl/chat?<?php echo esc_attr($wechat_id); ?>" target="_blank" rel="noopener"
           style="display:inline-flex;align-items:center;gap:10px;padding:12px 24px;background:#07c160;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;transition:opacity 0.2s;"
           onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M8.691 2.188C3.891 2.188 0 5.476 0 9.53c0 2.212 1.17 4.203 3.002 5.55a.59.59 0 0 1 .213.665l-.39 1.48c-.019.07-.048.141-.048.213 0 .163.13.295.29.295.095 0 .186-.036.254-.097l1.812-1.318a.588.588 0 0 1 .485-.086 10.5 10.5 0 0 0 3.073.452c4.8 0 8.691-3.288 8.691-7.342 0-4.054-3.891-7.342-8.691-7.342M5.893 8.135a.933.933 0 1 1 0 1.866.933.933 0 0 1 0-1.866m5.596 0a.933.933 0 1 1 0 1.866.933.933 0 0 1 0-1.866M24 14.871c0-3.412-3.447-6.18-7.698-6.18-.152 0-.302.006-.453.015.015.184.025.37.025.556 0 4.465-4.219 8.087-9.422 8.087-.338 0-.67-.018-1-.046 1.256 2.587 4.253 4.36 7.847 4.36a9.3 9.3 0 0 0 2.727-.4.523.523 0 0 1 .43.076l1.608 1.17c.06.054.141.086.225.086.143 0 .258-.117.258-.262 0-.064-.026-.127-.043-.189l-.346-1.315a.525.525 0 0 1 .189-.592C22.955 18.613 24 16.849 24 14.871m-10.27-2.174a.831.831 0 1 1 0 1.662.831.831 0 0 1 0-1.662m4.967 0a.831.831 0 1 1 0 1.662.831.831 0 0 1 0-1.662"/>
            </svg>
            WeChat
        </a>
        <?php endif; ?>
        
        <?php if ($wa_on === '1' && $whatsapp) : ?>
        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $whatsapp); ?>" target="_blank" rel="noopener"
           style="display:inline-flex;align-items:center;gap:10px;padding:12px 24px;background:#25d366;color:#fff;text-decoration:none;border-radius:8px;font-weight:600;transition:opacity 0.2s;"
           onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
            </svg>
            WhatsApp
        </a>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Render Quality Standards Block
 */
function kv_render_quality_standards_block($attributes) {
    return '';
}

// ============================================
// CONTACT INFO CARDS BLOCK — Render callback
// Replaces shortcodes [kv_contact_address] etc.
// ============================================
function kv_render_contact_info_block($attributes = []) {
    $show_addr  = isset($attributes['showAddress']) ? (bool) $attributes['showAddress'] : true;
    $show_phone = isset($attributes['showPhone'])   ? (bool) $attributes['showPhone']   : true;
    $show_email = isset($attributes['showEmail'])   ? (bool) $attributes['showEmail']   : true;
    $show_hours = isset($attributes['showHours'])   ? (bool) $attributes['showHours']   : true;
    $show_chat  = isset($attributes['showChat'])    ? (bool) $attributes['showChat']    : true;

    // Customizable icons, titles, colors from block attributes
    $addr_icon    = $attributes['addressIcon']  ?? "\xF0\x9F\x93\x8D";
    $addr_title   = $attributes['addressTitle'] ?? 'Address';
    $phone_icon   = $attributes['phoneIcon']    ?? "\xF0\x9F\x93\x9E";
    $phone_title  = $attributes['phoneTitle']   ?? 'Phone';
    $email_icon   = $attributes['emailIcon']    ?? "\xE2\x9C\x89\xEF\xB8\x8F";
    $email_title  = $attributes['emailTitle']   ?? 'Email';
    $hours_icon   = $attributes['hoursIcon']    ?? "\xF0\x9F\x95\x90";
    $hours_title  = $attributes['hoursTitle']   ?? 'Business Hours';
    $chat_icon    = $attributes['chatIcon']     ?? "\xF0\x9F\x92\xAC";
    $chat_title   = $attributes['chatTitle']    ?? 'Chat with Us';
    $icon_bg      = $attributes['iconBg']       ?? '#e8f0fe';
    $title_color  = $attributes['titleColor']   ?? '#1e293b';
    $text_color   = $attributes['textColor']    ?? '#64748b';

    // Page-level block data (fallback to site options when empty)
    $addr_full    = isset($attributes['addressText']) && $attributes['addressText'] !== ''
        ? (string) $attributes['addressText']
        : get_option('site_address_full', "123 Industrial Zone\nBangna-Trad Road\nBangkok 10260, Thailand");
    $phone        = isset($attributes['phoneText']) && $attributes['phoneText'] !== ''
        ? (string) $attributes['phoneText']
        : get_option('site_phone', '');
    $fax          = get_option('site_fax', '');
    $phone_fmt    = kv_format_phone_th($phone);
    $fax_fmt      = kv_format_phone_th($fax);
    $phone_tel    = preg_replace('/[^0-9+]/', '', $phone);
    $email        = isset($attributes['emailText']) && $attributes['emailText'] !== ''
        ? (string) $attributes['emailText']
        : get_option('site_email', 'info@company.com');
    $hours_wd     = isset($attributes['hoursWeekday']) && $attributes['hoursWeekday'] !== ''
        ? (string) $attributes['hoursWeekday']
        : get_option('site_hours_weekday', 'Monday – Friday: 8:00 AM – 5:00 PM');
    $hours_we     = isset($attributes['hoursWeekend']) && $attributes['hoursWeekend'] !== ''
        ? (string) $attributes['hoursWeekend']
        : get_option('site_hours_weekend', 'Saturday – Sunday: Closed');

    // Chat channels
    $line_on      = get_option('chat_line_enabled', '1');
    $line_id      = get_option('chat_line_id', 'kriangkrai2042');
    $wc_on        = get_option('chat_wechat_enabled', '1');
    $wc_id        = get_option('chat_wechat_id', '');
    $wc_qr        = get_option('chat_wechat_qr_url', '');
    $wa_on        = get_option('chat_whatsapp_enabled', '1');
    $wa_num       = get_option('chat_whatsapp_number', '6621088521');
    $facebook_url  = get_option('social_facebook_url', 'https://www.facebook.com/KVElectronicsTH/');
    $instagram_url = get_option('social_instagram_url', 'https://www.instagram.com/kvelectronicsth/');
    $linkedin_url  = get_option('social_linkedin_url', 'https://www.linkedin.com/company/kv-electronics-co-ltd');

    $icon_style = 'width:50px;height:50px;min-width:50px;border-radius:50%;background:' . esc_attr($icon_bg) . ';display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;';
    $card_style = 'display:flex;gap:16px;align-items:flex-start;margin-bottom:28px;';
    $h4_style   = 'margin:0 0 6px;font-size:17px;color:' . esc_attr($title_color) . ';';
    $p_style    = 'margin:0;color:' . esc_attr($text_color) . ';line-height:1.8;';

    ob_start();

    // ADDRESS
    if ($show_addr) : ?>
    <div style="<?php echo $card_style; ?>">
        <div style="<?php echo $icon_style; ?>"><?php echo esc_html($addr_icon); ?></div>
        <div>
            <h4 style="<?php echo $h4_style; ?>"><?php echo wp_kses_post($addr_title); ?></h4>
            <p style="<?php echo $p_style; ?>"><?php echo nl2br(esc_html($addr_full)); ?></p>
        </div>
    </div>
    <?php endif;

    // PHONE
    if ($show_phone && $phone) : ?>
    <div style="<?php echo $card_style; ?>">
        <div style="<?php echo $icon_style; ?>"><?php echo esc_html($phone_icon); ?></div>
        <div>
            <h4 style="<?php echo $h4_style; ?>"><?php echo wp_kses_post($phone_title); ?></h4>
            <p style="<?php echo $p_style; ?>">
                <a href="tel:<?php echo esc_attr($phone_tel); ?>" style="color:<?php echo esc_attr($text_color); ?>;text-decoration:none;"><?php echo esc_html($phone_fmt ?: $phone); ?></a>
                <?php if ($fax) : ?><br><span><?php echo esc_html($fax_fmt ?: $fax); ?> (Fax)</span><?php endif; ?>
            </p>
        </div>
    </div>
    <?php endif;

    // EMAIL
    if ($show_email) : ?>
    <div style="<?php echo $card_style; ?>">
        <div style="<?php echo $icon_style; ?>"><?php echo esc_html($email_icon); ?></div>
        <div>
            <h4 style="<?php echo $h4_style; ?>"><?php echo wp_kses_post($email_title); ?></h4>
            <p style="<?php echo $p_style; ?>">
                <a href="mailto:<?php echo esc_attr($email); ?>" style="color:<?php echo esc_attr($text_color); ?>;text-decoration:none;"><?php echo esc_html($email); ?></a>
            </p>
        </div>
    </div>
    <?php endif;

    // HOURS
    if ($show_hours) : ?>
    <div style="<?php echo $card_style; ?>">
        <div style="<?php echo $icon_style; ?>"><?php echo esc_html($hours_icon); ?></div>
        <div>
            <h4 style="<?php echo $h4_style; ?>"><?php echo wp_kses_post($hours_title); ?></h4>
            <p style="<?php echo $p_style; ?>">
                <?php echo esc_html($hours_wd); ?><br>
                <?php echo esc_html($hours_we); ?>
            </p>
        </div>
    </div>
    <?php endif;

    // CHAT
    if ($show_chat) :
        $any_chat = ($line_on && $line_id) || ($wc_on) || ($wa_on && $wa_num) || !empty($facebook_url) || !empty($instagram_url) || !empty($linkedin_url);
        if ($any_chat) : ?>
    <div style="<?php echo $card_style; ?>">
        <div style="<?php echo $icon_style; ?>"><?php echo esc_html($chat_icon); ?></div>
        <div>
            <h4 style="<?php echo $h4_style; ?>"><?php echo wp_kses_post($chat_title); ?></h4>
            <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                <?php if ($line_on && $line_id) : ?>
                <a href="https://line.me/ti/p/~<?php echo esc_attr($line_id); ?>" target="_blank" rel="noopener noreferrer" style="width:48px;height:48px;border-radius:50%;background:#06C755;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(6,199,85,0.35);transition:transform .2s;text-decoration:none;" aria-label="Chat on LINE" onmouseenter="this.style.transform='scale(1.12)'" onmouseleave="this.style.transform='scale(1)'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="#fff"><path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386a.63.63 0 0 1-.63-.629V8.108a.63.63 0 0 1 .63-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016a.63.63 0 0 1-.63.629.626.626 0 0 1-.51-.262l-2.397-3.265v2.898a.63.63 0 0 1-.63.629.627.627 0 0 1-.629-.629V8.108a.63.63 0 0 1 .63-.63c.2 0 .38.095.51.262l2.397 3.265V8.108a.63.63 0 0 1 .63-.63.63.63 0 0 1 .629.63v4.771zm-5.741 0a.63.63 0 0 1-1.26 0V8.108a.63.63 0 0 1 1.26 0v4.771zm-2.527.629H4.856a.63.63 0 0 1-.63-.629V8.108a.63.63 0 0 1 1.26 0v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629zM24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/></svg>
                </a>
                <?php endif; ?>
                <?php if ($wc_on) : ?>
                <button onclick="var p=document.getElementById('kv-ct-wechat-popup');p.style.display=p.style.display==='none'?'flex':'none';" style="width:48px;height:48px;border-radius:50%;background:#07C160;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(7,193,96,0.35);transition:transform .2s;" aria-label="Chat on WeChat" onmouseenter="this.style.transform='scale(1.12)'" onmouseleave="this.style.transform='scale(1)'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#fff"><path d="M8.691 2.188C3.891 2.188 0 5.476 0 9.53c0 2.212 1.17 4.203 3.002 5.55a.59.59 0 0 1 .213.665l-.39 1.48c-.019.07-.048.141-.048.213a.3.3 0 0 0 .3.3c.07 0 .14-.027.198-.063l1.83-1.067a.57.57 0 0 1 .449-.063 9.613 9.613 0 0 0 3.137.524c.302 0 .6-.013.893-.039a6.192 6.192 0 0 1-.253-1.72c0-3.682 3.477-6.674 7.759-6.674.254 0 .505.012.752.033C16.726 4.492 13.068 2.188 8.691 2.188zm-2.6 4.26a1.058 1.058 0 1 1 0 2.115 1.058 1.058 0 0 1 0-2.116zm5.203 0a1.058 1.058 0 1 1 0 2.115 1.058 1.058 0 0 1 0-2.116zM16.09 8.735c-3.752 0-6.803 2.614-6.803 5.836 0 3.222 3.051 5.836 6.803 5.836a8.17 8.17 0 0 0 2.593-.42.542.542 0 0 1 .42.059l1.517.885c.052.033.112.055.172.055a.25.25 0 0 0 .25-.25c0-.062-.024-.12-.04-.178l-.323-1.228a.553.553 0 0 1 .2-.622C22.725 17.543 24 15.762 24 13.57c0-3.222-3.547-5.836-7.91-5.836zm-2.418 3.776a.883.883 0 1 1 0 1.765.883.883 0 0 1 0-1.765zm4.84 0a.883.883 0 1 1 0 1.765.883.883 0 0 1 0-1.765z"/></svg>
                </button>
                <?php endif; ?>
                <?php if ($wa_on && $wa_num) : ?>
                <a href="https://wa.me/<?php echo esc_attr(preg_replace('/\D/', '', $wa_num)); ?>" target="_blank" rel="noopener noreferrer" style="width:48px;height:48px;border-radius:50%;background:#25D366;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(37,211,102,0.35);transition:transform .2s;text-decoration:none;" aria-label="Chat on WhatsApp" onmouseenter="this.style.transform='scale(1.12)'" onmouseleave="this.style.transform='scale(1)'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#fff"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
                </a>
                <?php endif; ?>
                <?php if (!empty($facebook_url)) : ?>
                <a href="<?php echo esc_url($facebook_url); ?>" target="_blank" rel="noopener noreferrer" style="width:48px;height:48px;border-radius:50%;background:#1877F2;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(24,119,242,0.35);transition:transform .2s;text-decoration:none;" aria-label="Open Facebook" onmouseenter="this.style.transform='scale(1.12)'" onmouseleave="this.style.transform='scale(1)'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="#fff"><path d="M13.5 22v-8h2.7l.4-3h-3.1V9.1c0-.9.3-1.6 1.6-1.6h1.7V4.7c-.3 0-1.3-.1-2.4-.1-2.4 0-4 1.4-4 4.2V11H8v3h2.4v8h3.1z"/></svg>
                </a>
                <?php endif; ?>
                <?php if (!empty($instagram_url)) : ?>
                <a href="<?php echo esc_url($instagram_url); ?>" target="_blank" rel="noopener noreferrer" style="width:48px;height:48px;border-radius:50%;background:radial-gradient(circle at 30% 107%, #fdf497 0%, #fdf497 5%, #fd5949 45%, #d6249f 60%, #285AEB 90%);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(214,36,159,0.35);transition:transform .2s;text-decoration:none;" aria-label="Open Instagram" onmouseenter="this.style.transform='scale(1.12)'" onmouseleave="this.style.transform='scale(1)'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="5" ry="5"></rect><circle cx="12" cy="12" r="4"></circle><circle cx="17.5" cy="6.5" r="1"></circle></svg>
                </a>
                <?php endif; ?>
                <?php if (!empty($linkedin_url)) : ?>
                <a href="<?php echo esc_url($linkedin_url); ?>" target="_blank" rel="noopener noreferrer" style="width:48px;height:48px;border-radius:50%;background:#0A66C2;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(10,102,194,0.35);transition:transform .2s;text-decoration:none;" aria-label="Open LinkedIn" onmouseenter="this.style.transform='scale(1.12)'" onmouseleave="this.style.transform='scale(1)'">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="#fff"><path d="M6.94 8.5a1.56 1.56 0 1 1 0-3.12 1.56 1.56 0 0 1 0 3.12zM5.5 9.75h2.9V19h-2.9V9.75zM10.2 9.75h2.78v1.26h.04c.39-.73 1.34-1.5 2.75-1.5 2.94 0 3.48 1.93 3.48 4.44V19h-2.9v-4.47c0-1.07-.02-2.45-1.49-2.45-1.5 0-1.73 1.17-1.73 2.37V19H10.2V9.75z"/></svg>
                </a>
                <?php endif; ?>
            </div>
            <?php if ($wc_on) : ?>
            <div id="kv-ct-wechat-popup" style="display:none;margin-top:12px;padding:16px;background:#fff;border-radius:10px;border:1px solid #e2e8f0;box-shadow:0 4px 16px rgba(0,0,0,0.08);flex-direction:column;align-items:center;gap:8px;max-width:220px;">
                <?php if ($wc_qr) : ?><img src="<?php echo esc_url($wc_qr); ?>" alt="WeChat QR Code" style="width:160px;height:160px;border-radius:8px;"><?php endif; ?>
                <p style="margin:0;font-size:13px;color:#64748b;"><?php echo $wc_id ? 'WeChat ID: <strong>' . esc_html($wc_id) . '</strong>' : 'Scan to chat on WeChat'; ?></p>
                <button onclick="this.parentElement.style.display='none'" style="margin-top:4px;padding:4px 16px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer;font-size:12px;color:#64748b;">Close</button>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
        endif;
    endif;

    return ob_get_clean();
}

/**
 * Render Product Categories Block (also used by [products_category_cards] shortcode)
 */
function kv_render_product_categories_block($attributes = []) {
    $columns      = isset($attributes['columns'])      ? (int) $attributes['columns']      : 3;
    $show_image   = isset($attributes['showImage'])     ? (bool) $attributes['showImage']   : true;
    $show_desc    = isset($attributes['showDesc'])      ? (bool) $attributes['showDesc']    : true;
    $show_children= isset($attributes['showChildren'])  ? (bool) $attributes['showChildren']: true;
    $max_children = isset($attributes['maxChildren'])   ? (int) $attributes['maxChildren']  : 5;
    $visible_count= isset($attributes['visibleCards'])  ? (int) $attributes['visibleCards'] : 3;
    $button_text  = isset($attributes['buttonText'])    ? $attributes['buttonText']         : 'View Products →';

    $sort_order = my_theme_get_product_category_order();
    $parent_cats = get_terms([
        'taxonomy'   => 'product_category',
        'parent'     => 0,
        'hide_empty' => false,
        'orderby'    => 'id',
        'order'      => $sort_order,
    ]);

    if (!$parent_cats || is_wp_error($parent_cats)) return '';

    $cards = [];
    foreach ($parent_cats as $cat) {
        $cat_image = get_term_meta($cat->term_id, 'cat_image', true);
        if (!$cat_image) {
            $first_product = get_posts([
                'post_type'      => 'product',
                'posts_per_page' => 1,
                'tax_query'      => [[
                    'taxonomy'         => 'product_category',
                    'field'            => 'term_id',
                    'terms'            => $cat->term_id,
                    'include_children' => true,
                ]],
                'orderby' => 'date',
                'order'   => 'DESC',
            ]);
            if ($first_product) {
                $cat_image = get_post_meta($first_product[0]->ID, 'pd_image_1', true);
                if (!$cat_image && has_post_thumbnail($first_product[0]->ID)) {
                    $cat_image = get_the_post_thumbnail_url($first_product[0]->ID, 'large');
                }
            }
        }

        if ($cat_image) {
            $cat_image = kv_safe_image_url($cat_image);
        }

        $cat_desc = get_term_meta($cat->term_id, 'cat_description', true);
        if (!$cat_desc) $cat_desc = $cat->description;

        $children = get_terms([
            'taxonomy'   => 'product_category',
            'parent'     => $cat->term_id,
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ]);

        $cards[] = [
            'cat'      => $cat,
            'image'    => $cat_image,
            'desc'     => $cat_desc,
            'children' => $children,
            'url'      => get_term_link($cat),
        ];
    }

    $col_width = round(100 / $columns, 3);
    $visible   = array_slice($cards, 0, $visible_count);
    $hidden    = array_slice($cards, $visible_count);
    $has_more  = count($hidden) > 0;

    $render_card = function($item) use ($show_image, $show_desc, $show_children, $max_children, $button_text, $col_width) {
        $cat      = $item['cat'];
        $image    = $item['image'];
        $desc     = $item['desc'];
        $children = $item['children'];
        $url      = $item['url'];
        ob_start(); ?>
        <div class="wp-block-column is-layout-flow kv-cat-card" style="flex:1;min-width:280px;max-width:calc(<?php echo $col_width; ?>% - 1rem);">
            <div class="wp-block-group product-card has-white-background-color has-background is-layout-flow" style="border-radius:12px;padding:0;height:100%;display:flex;flex-direction:column;">

                <?php if ($show_image) : ?>
                <?php if ($image) : ?>
                <figure style="margin:0;border-top-left-radius:12px;border-top-right-radius:12px;overflow:hidden;flex-shrink:0;">
                    <img decoding="async" src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($cat->name); ?>" style="width:100%;height:200px;object-fit:cover;display:block;">
                </figure>
                <?php else : ?>
                <div style="width:100%;height:200px;background:#f1f5f9;border-top-left-radius:12px;border-top-right-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#cbd5e1" viewBox="0 0 16 16"><path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/><path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2H2zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1H14z"/></svg>
                </div>
                <?php endif; ?>
                <?php endif; ?>

                <div style="padding:25px;display:flex;flex-direction:column;flex:1;">
                    <h3 style="margin-bottom:10px;font-size:24px;color:#1e293b;"><?php echo esc_html($cat->name); ?></h3>

                    <?php if ($show_desc && $desc) : ?>
                    <p style="color:#64748b;margin-bottom:15px;"><?php echo esc_html($desc); ?></p>
                    <?php endif; ?>

                    <?php if ($show_children && $children && !is_wp_error($children)) :
                        $ch_all    = $children;
                        $ch_limit  = $max_children > 0 ? $max_children : 5;
                        $ch_shown  = array_slice($ch_all, 0, $ch_limit);
                        $ch_extra  = count($ch_all) - count($ch_shown);
                    ?>
                    <ul style="color:#64748b;margin-bottom:20px;font-size:14px;padding-left:0;list-style:none;">
                        <?php foreach ($ch_shown as $child) : ?>
                        <li style="margin-bottom:4px;">
                            <a href="<?php echo esc_url(get_term_link($child)); ?>" style="color:#64748b;text-decoration:none;">
                                <?php echo esc_html($child->name); ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                        <?php if ($ch_extra > 0) : ?>
                        <li style="margin-bottom:4px;color:#94a3b8;font-size:13px;">+<?php echo $ch_extra; ?> more</li>
                        <?php endif; ?>
                    </ul>
                    <?php endif; ?>

                    <div class="kv-cat-card-btn-wrap" style="margin-top:auto;">
                        <a href="<?php echo esc_url($url); ?>" class="btn btn-outline-primary kv-cat-card-btn" style="border:2px solid var(--theme-accent);color:var(--theme-accent);padding:8px 20px;border-radius:6px;text-decoration:none;display:inline-block;">
                            <?php echo esc_html($button_text); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    };

    ob_start();
    ?>
    <style>
    .wp-block-group.has-tertiary-background-color.has-background > .wp-block-heading.has-text-align-center { margin-top:0 !important; }
    .cat-cards-hidden { display:none; }
    .cat-cards-row { display:flex; flex-wrap:wrap; gap:1.5rem; }
    .cat-show-more-btn { display:inline-flex;align-items:center;gap:8px;background:var(--theme-accent);color:#fff;border:none;padding:12px 32px;border-radius:8px;font-size:16px;cursor:pointer;margin-top:32px;transition:background 0.2s; }
    .cat-show-more-btn:hover { background:var(--theme-accent-dark); }
    .cat-show-more-wrap { text-align:center; }
    /* ── Responsive cards ── */
    @media (max-width: 991px) {
        .kv-cat-card { max-width:calc(50% - 0.75rem) !important; min-width:calc(50% - 0.75rem) !important; }
    }
    @media (max-width: 575px) {
        .kv-cat-card { max-width:100% !important; min-width:100% !important; flex-basis:100% !important; }
        .cat-cards-row { gap:1rem; }
        .kv-cat-card-btn-wrap { display:flex; justify-content:center; align-items:center; text-align:center; }
        .kv-cat-card-btn { display:inline-flex !important; justify-content:center; align-items:center; }
    }
    </style>

    <div class="container">
    <div class="cat-cards-row">
        <?php foreach ($visible as $item) echo $render_card($item); ?>
    </div>

    <?php if ($has_more) : ?>
    <div class="cat-cards-row" id="cat-cards-extra" style="margin-top:1.5rem;display:none;">
        <?php foreach ($hidden as $item) echo $render_card($item); ?>
    </div>
    <div class="cat-show-more-wrap">
        <button class="cat-show-more-btn" id="cat-show-more-btn" onclick="
            document.getElementById('cat-cards-extra').style.display='flex';
            this.style.display='none';
        ">
            Show More
            <svg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='currentColor' viewBox='0 0 16 16'><path fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/></svg>
        </button>
    </div>
    <?php endif; ?>
    </div>

    <?php
    return ob_get_clean();
}

// ============================================
// APPLICATIONS BLOCK — Render callback
// ============================================
function kv_render_applications_block($attributes = []) {
    $title   = $attributes['title']   ?? 'Applications';
    $columns = $attributes['columns'] ?? 4;
    $bgColor = $attributes['bgColor'] ?? '#f8fafc';
    $align   = $attributes['align']   ?? 'full';
    $items_json = $attributes['items'] ?? '[]';
    $items   = json_decode($items_json, true);
    if (!is_array($items) || empty($items)) return '';

    $align_class = $align === 'full' ? ' alignfull' : ($align === 'wide' ? ' alignwide' : '');

    ob_start();
    ?>
    <section class="kv-applications<?php echo $align_class; ?>" style="background:<?php echo esc_attr($bgColor); ?>;padding:60px 20px;box-sizing:border-box;">
        <div style="max-width:1200px;margin:0 auto;">
            <?php if ($title): ?>
            <h2 style="text-align:center;font-size:36px;font-weight:700;margin:0 0 50px;color:#1e293b;">
                <?php echo esc_html($title); ?>
            </h2>
            <?php endif; ?>
            <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:30px;">
                <?php foreach ($items as $item): ?>
                <div style="text-align:center;padding:30px;background:#fff;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,0.08);transition:transform .2s,box-shadow .2s;width:300px;max-width:100%;box-sizing:border-box;flex-shrink:0;">
                    <div style="font-size:48px;margin-bottom:15px;"><?php echo esc_html($item['icon'] ?? '📦'); ?></div>
                    <h4 style="font-size:20px;margin:0 0 10px;color:#1e293b;"><?php echo esc_html($item['title'] ?? ''); ?></h4>
                    <p style="color:#64748b;margin:0;line-height:1.6;"><?php echo esc_html($item['desc'] ?? ''); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
}

// ============================================
// PRODUCT CATEGORY SORT ORDER SETTINGS
// ============================================

/**
 * Add admin menu for product category settings
 */
add_action('admin_menu', function() {
    add_submenu_page(
        'edit.php?post_type=product',
        'Product Settings',
        'Settings',
        'manage_options',
        'product-settings',
        'my_theme_product_settings_page'
    );
});

/**
 * Settings page HTML
 */
function my_theme_product_settings_page() {
    if (!current_user_can('manage_options')) return;

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer('product_settings_nonce')) {
        $sort_order = sanitize_text_field($_POST['product_category_sort_order'] ?? 'DESC');
        if (in_array($sort_order, ['ASC', 'DESC'])) {
            update_option('my_theme_product_category_order', $sort_order);
            echo '<div class="notice notice-success"><p>Settings saved.</p></div>';
        }
    }

    $current_order = get_option('my_theme_product_category_order', 'DESC');
    ?>
    <div class="wrap">
        <h1>Product Settings</h1>
        <form method="post" style="max-width: 500px;">
            <?php wp_nonce_field('product_settings_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="product_category_sort_order">Product Category Sort Order</label>
                    </th>
                    <td>
                        <select id="product_category_sort_order" name="product_category_sort_order">
                            <option value="DESC" <?php selected($current_order, 'DESC'); ?>>
                                Descending (Newest First)
                            </option>
                            <option value="ASC" <?php selected($current_order, 'ASC'); ?>>
                                Ascending (Oldest First)
                            </option>
                        </select>
                        <p class="description">
                            Choose how product categories are sorted in the navbar and footer.
                        </p>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/**
 * Helper function to get current sort order
 */
function my_theme_get_product_category_order() {
    return get_option('my_theme_product_category_order', 'DESC');
}

// ============================================
// BLOCK THEME SHORTCODES
// ============================================

/**
 * [product_categories_nav] - Outputs nested dropdown items for navbar (hover + flyout submenus)
 */
add_shortcode('product_categories_nav', function() {
    $sort_order = my_theme_get_product_category_order();
    $_test = get_term_by('slug', 'test', 'product_category');
    $categories = get_terms([
        'taxonomy'   => 'product_category',
        'parent'     => 0,
        'hide_empty' => false,
        'orderby'    => 'id',
        'order'      => $sort_order,
        'exclude'    => $_test ? [$_test->term_id] : [],
    ]);

    if (!$categories || is_wp_error($categories)) return '';

    $current_request_path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    $active_term_ids = [];
    $active_root_slug = '';

    if (is_tax('product_category')) {
        $term = get_queried_object();
        if ($term && !is_wp_error($term)) {
            $active_term_ids[] = (int) $term->term_id;
            $walker = $term;
            while ($walker && !is_wp_error($walker) && !empty($walker->parent)) {
                $walker = get_term((int) $walker->parent, 'product_category');
                if ($walker && !is_wp_error($walker)) {
                    $active_term_ids[] = (int) $walker->term_id;
                } else {
                    break;
                }
            }
            if ($walker && !is_wp_error($walker)) {
                $active_root_slug = (string) $walker->slug;
            }
        }
    } elseif (is_singular('product')) {
        $product_id = (int) get_queried_object_id();
        $terms = wp_get_object_terms($product_id, 'product_category');
        if ($terms && !is_wp_error($terms)) {
            $selected = null;
            $selected_depth = -1;

            foreach ($terms as $term_item) {
                $active_term_ids[] = (int) $term_item->term_id;

                $depth = 0;
                $walker = $term_item;
                while ($walker && !is_wp_error($walker) && !empty($walker->parent)) {
                    $walker = get_term((int) $walker->parent, 'product_category');
                    if ($walker && !is_wp_error($walker)) {
                        $active_term_ids[] = (int) $walker->term_id;
                        $depth++;
                    } else {
                        break;
                    }
                }

                if ($depth > $selected_depth) {
                    $selected_depth = $depth;
                    $selected = $term_item;
                }
            }

            if ($selected && !is_wp_error($selected)) {
                $root = $selected;
                while ($root && !is_wp_error($root) && !empty($root->parent)) {
                    $root = get_term((int) $root->parent, 'product_category');
                    if (!$root || is_wp_error($root)) {
                        break;
                    }
                }
                if ($root && !is_wp_error($root)) {
                    $active_root_slug = (string) $root->slug;
                }
            }
        }
    }

    $active_term_ids = array_values(array_unique(array_map('intval', $active_term_ids)));

    $output = '';
    foreach ($categories as $cat) {
        $cat_url  = esc_url(get_term_link($cat));
        $cat_name = esc_html($cat->name);
        $cat_link = get_term_link($cat);
        $cat_path = (!is_wp_error($cat_link) && $cat_link) ? trim((string) parse_url($cat_link, PHP_URL_PATH), '/') : '';
        $cat_slug_prefix = 'products/' . $cat->slug;
        $is_active_cat = ($active_root_slug !== '' && $active_root_slug === $cat->slug)
            || in_array((int) $cat->term_id, $active_term_ids, true)
            || ($cat_path !== '' && $current_request_path !== '' && ($current_request_path === $cat_path || strpos($current_request_path, $cat_path . '/') === 0))
            || ($current_request_path === $cat_slug_prefix || strpos($current_request_path, $cat_slug_prefix . '/') === 0);

        // Get child terms (sub-categories)
        $children = get_terms([
            'taxonomy'   => 'product_category',
            'parent'     => $cat->term_id,
            'hide_empty' => false,
        ]);
        $has_children = $children && !is_wp_error($children) && count($children) > 0;

        if ($has_children) {
            $has_active_child = false;
            foreach ($children as $child_probe) {
                $child_probe_link = get_term_link($child_probe);
                $child_probe_path = (!is_wp_error($child_probe_link) && $child_probe_link) ? trim((string) parse_url($child_probe_link, PHP_URL_PATH), '/') : '';
                $child_probe_nested = 'products/' . $cat->slug . '/' . $child_probe->slug;
                $child_probe_flat = 'products/' . $child_probe->slug;
                $child_probe_active = in_array((int) $child_probe->term_id, $active_term_ids, true)
                    || ($child_probe_path !== '' && $current_request_path !== '' && ($current_request_path === $child_probe_path || strpos($current_request_path, $child_probe_path . '/') === 0))
                    || ($current_request_path === $child_probe_nested || strpos($current_request_path, $child_probe_nested . '/') === 0)
                    || ($current_request_path === $child_probe_flat || strpos($current_request_path, $child_probe_flat . '/') === 0);
                if ($child_probe_active) {
                    $has_active_child = true;
                    break;
                }
            }

            if ($has_active_child) {
                $is_active_cat = true;
            }

            $output .= '<li class="dropdown-submenu">';
            $output .= '<a class="dropdown-item d-flex justify-content-between align-items-center' . ($is_active_cat ? ' active' : '') . '" href="' . $cat_url . '" style="padding:9px 16px;font-size:15px;">' . $cat_name . '</a>';
            $output .= '<ul class="dropdown-menu" style="padding:6px 0;min-width:220px;">';
            foreach ($children as $child) {
                $child_link = get_term_link($child);
                $child_path = (!is_wp_error($child_link) && $child_link) ? trim((string) parse_url($child_link, PHP_URL_PATH), '/') : '';
                $child_slug_nested = 'products/' . $cat->slug . '/' . $child->slug;
                $child_slug_flat = 'products/' . $child->slug;
                $child_active = in_array((int) $child->term_id, $active_term_ids, true)
                    || ($child_path !== '' && $current_request_path !== '' && ($current_request_path === $child_path || strpos($current_request_path, $child_path . '/') === 0))
                    || ($current_request_path === $child_slug_nested || strpos($current_request_path, $child_slug_nested . '/') === 0)
                    || ($current_request_path === $child_slug_flat || strpos($current_request_path, $child_slug_flat . '/') === 0);

                $output .= '<li><a class="dropdown-item' . ($child_active ? ' active' : '') . '" href="' . esc_url($child_link) . '" style="font-size:14px;padding:7px 16px;">' . esc_html($child->name) . '</a></li>';
            }
            $output .= '<li><hr class="dropdown-divider"></li>';
            $output .= '<li><a class="dropdown-item dropdown-view-all-link" href="' . $cat_url . '" style="font-size:13px;color:var(--theme-primary);padding:7px 16px;">View all ' . $cat_name . ' &rarr;</a></li>';
            $output .= '</ul>';
            $output .= '</li>';
        } else {
            $output .= '<li><a class="dropdown-item' . ($is_active_cat ? ' active' : '') . '" href="' . $cat_url . '" style="padding:9px 16px;font-size:15px;">' . $cat_name . '</a></li>';
        }
    }

    $output .= '<li><hr class="dropdown-divider" style="margin:4px 0;"></li>';
    $output .= '<li><a class="dropdown-item dropdown-view-all-link" href="/products/" style="font-size:13px;color:var(--theme-primary);padding:8px 16px;">View All Products &rarr;</a></li>';

    return $output;
});

/**
 * [product_categories_footer] - Outputs list items for footer
 */
add_shortcode('product_categories_footer', function() {
    $sort_order = my_theme_get_product_category_order();
    $categories = get_terms([
        'taxonomy'   => 'product_category',
        'parent'     => 0,
        'hide_empty' => false,
        'orderby'    => 'id',
        'order'      => $sort_order,
    ]);
    
    if (!$categories || is_wp_error($categories)) return '';
    
    $output = '';
    foreach ($categories as $cat) {
        $output .= '<li class="mb-1"><a href="' . esc_url(get_term_link($cat)) . '" style="color: #cbd5e1; text-decoration: none;">' . esc_html($cat->name) . '</a></li>';
    }
    return $output;
});

/**
 * [current_year] - Outputs current year
 */
add_shortcode('current_year', function() {
    return wp_date('Y', null, new DateTimeZone('Asia/Bangkok'));
});

/**
 * [products_category_cards] - Delegates to kv/product-categories block render
 */
add_shortcode('products_category_cards', function() {
    return kv_render_product_categories_block([]);
});

/**
 * [kv_home_hero] - Stable Home Hero render (editor-safe via Shortcode block)
 */
add_shortcode('kv_home_hero', function() {
    return kv_render_home_hero_block(array());
});

/* Video preload removed — large file preload can delay page rendering */

function kv_render_home_hero_block($attributes = array()) {
    $attributes = wp_parse_args($attributes, array(
        'title' => 'KV Electronics | Home',
        'subtitle' => '',
        'primaryText' => 'View Products',
        'primaryUrl' => '/products/',
        'secondaryText' => 'Contact Us',
        'secondaryUrl' => '/contact/',
    ));

    $theme_primary_color = get_option('theme_primary_color', '#0056d6');
    $banner_bg_color = $theme_primary_color;
    $banner_bg_image = kv_rebase_url(get_option('banner_bg_image', ''));
    $banner_bg_video = kv_rebase_url(get_option('banner_bg_video', ''));
    $banner_overlay  = min(100, max(0, (int) get_option('banner_overlay', 60)));
    $banner_fadein_raw = get_option('banner_fadein_delay', 0);
    if ($banner_fadein_raw === '' || $banner_fadein_raw === null) {
        $banner_fadein_raw = 0;
    }
    $banner_fadein_delay = min(30, max(0, (int) $banner_fadein_raw));
    $banner_video_start = max(0, (float) get_option('banner_video_start', 0));
    $banner_video_end = max(0, (float) get_option('banner_video_end', 0));
    $overlay_alpha   = $banner_overlay / 100;
    $hero_state_key  = md5((string) $banner_bg_video . '|' . home_url('/') . '|' . (string) $banner_video_start . '|' . (string) $banner_video_end);

    // Build Media Fragment URI so the browser starts at the correct time natively
    $banner_video_src = $banner_bg_video;
    if (!empty($banner_bg_video) && ($banner_video_start > 0 || $banner_video_end > 0)) {
        $frag = '#t=' . number_format($banner_video_start, 1, '.', '');
        if ($banner_video_end > $banner_video_start) {
            $frag .= ',' . number_format($banner_video_end, 1, '.', '');
        }
        $banner_video_src = $banner_bg_video . $frag;
    }

    $hex = ltrim((string) $theme_primary_color, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    }
    if (!preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
        $hex = '0056d6';
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $overlay_color = sprintf('rgba(%d,%d,%d,%.3f)', $r, $g, $b, $overlay_alpha);
    $hero_visibility_class = ($banner_fadein_delay > 0) ? 'kv-hero-delayed' : 'kv-hero-instant';

    $section_style = 'margin-top:0;padding-top:100px;padding-bottom:100px;position:relative;overflow:hidden;background-color:' . esc_attr($banner_bg_color) . ';';
    if (!empty($banner_bg_image)) {
        $section_style .= 'background-image:url(' . esc_url($banner_bg_image) . ');background-size:cover;background-position:center;';
    }

    ob_start();
    ?>
    <style>
    .kv-home-hero .kv-hero-content { opacity: 1; visibility: visible; }
    .kv-home-hero .kv-hero-content.kv-hero-delayed { opacity: 1 !important; visibility: hidden !important; animation: none !important; transition: none !important; }
    .kv-home-hero .kv-hero-content.kv-hero-instant { opacity: 1 !important; visibility: visible !important; animation: none !important; transition: none !important; }
    </style>
    <div class="wp-block-group alignfull has-white-color has-text-color has-background kv-home-hero" data-hero-key="<?php echo esc_attr($hero_state_key); ?>" data-fade-delay="<?php echo esc_attr($banner_fadein_delay); ?>" style="<?php echo esc_attr($section_style); ?>">
        <?php if (!empty($banner_bg_video)) : ?>
        <video class="kv-hero-video-bg" muted autoplay playsinline webkit-playsinline preload="auto" src="<?php echo esc_url($banner_video_src); ?>" data-video-start="<?php echo esc_attr($banner_video_start); ?>" data-video-end="<?php echo esc_attr($banner_video_end); ?>" <?php if (!empty($banner_bg_image)) : ?>poster="<?php echo esc_url($banner_bg_image); ?>"<?php endif; ?> style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:0;background:transparent;"></video>
        <?php endif; ?>
        <span aria-hidden="true" style="position:absolute;inset:0;background:<?php echo esc_attr($overlay_color); ?>;z-index:1;"></span>

        <div class="kv-hero-content <?php echo esc_attr($hero_visibility_class); ?>" style="position:relative;z-index:2;max-width:1200px;margin:0 auto;padding:0 20px;">
            <h1 class="wp-block-heading has-text-align-center has-white-color has-text-color" style="font-size:48px;font-weight:700;margin-bottom:32px;"><?php echo esc_html($attributes['title']); ?></h1>
            <?php if (!empty($attributes['subtitle'])): ?>
            <p class="has-text-align-center has-white-color has-text-color" style="margin-top:0;margin-bottom:30px;font-size:20px;"><?php echo esc_html($attributes['subtitle']); ?></p>
            <?php endif; ?>
            <div class="wp-block-buttons is-layout-flex" style="justify-content:center;gap:15px;">
                <div class="wp-block-button"><a class="wp-block-button__link has-primary-color has-white-background-color has-text-color has-background wp-element-button" href="<?php echo esc_url(home_url($attributes['primaryUrl'])); ?>" style="border-radius:6px;"><?php echo esc_html($attributes['primaryText']); ?></a></div>
                <div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-white-color has-text-color wp-element-button" href="<?php echo esc_url(home_url($attributes['secondaryUrl'])); ?>" style="border-radius:6px;border-width:2px;border-color:#ffffff;color:#ffffff;"><?php echo esc_html($attributes['secondaryText']); ?></a></div>
            </div>
        </div>
        <script>
        (function(){
            /* ---- Find hero element ---- */
            var scriptEl = document.currentScript || null;
            var hero = scriptEl && scriptEl.closest ? scriptEl.closest('.kv-home-hero') : null;
            if (!hero) { var all = document.querySelectorAll('.kv-home-hero'); hero = all.length ? all[all.length-1] : null; }
            if (!hero) return;

            var content = hero.querySelector('.kv-hero-content');
            var video   = hero.querySelector('.kv-hero-video-bg');
            var revealDelay = Math.max(0, parseInt(hero.getAttribute('data-fade-delay') || '0', 10) || 0);
            var key     = hero.getAttribute('data-hero-key') || 'default';
            var timeKey = 'kvHeroTime:' + key;

            /* ---- Delay reveal helper (no fade animation) ---- */
            function showInstant() {
                if (!content) return;
                content.classList.remove('kv-hero-delayed');
                content.classList.add('kv-hero-instant');
                content.style.visibility = 'visible';
                content.style.opacity = '1';
            }
            if (revealDelay > 0) {
                setTimeout(showInstant, revealDelay * 1000);
            } else {
                showInstant();
            }

            /* ---- Time persistence helpers ---- */
            function getSavedTime() {
                var raw = null;
                try { raw = sessionStorage.getItem(timeKey); } catch(e) {}
                if (!raw) { try { raw = localStorage.getItem(timeKey); } catch(e) {} }
                var t = parseFloat(raw);
                return isNaN(t) ? 0 : t;
            }
            function saveTime(t) {
                var v = t.toFixed(2);
                try { sessionStorage.setItem(timeKey, v); } catch(e) {}
                try { localStorage.setItem(timeKey, v); } catch(e) {}
            }

            /* fade class is rendered by PHP/CSS; JS does not control initial fade state */

            if (!video) return;

            /* ---- Range config from data attributes ---- */
            var cfgStart = Math.max(0, parseFloat(video.getAttribute('data-video-start') || '0') || 0);
            var cfgEnd   = Math.max(0, parseFloat(video.getAttribute('data-video-end')   || '0') || 0);

            function getRange() {
                var dur = video.duration;
                if (!dur || !isFinite(dur)) return { s: cfgStart, e: cfgEnd > cfgStart ? cfgEnd : 99999 };
                var s = cfgStart < dur ? cfgStart : 0;
                var e = (cfgEnd > 0 && cfgEnd <= dur) ? cfgEnd : dur;
                if (e <= s) { s = 0; e = dur; }
                return { s: s, e: e };
            }

            /* ---- Ensure muted for autoplay policy ---- */
            video.muted = true;
            video.defaultMuted = true;

            /* ---- Play helper with promise handling ---- */
            function tryPlay() {
                try {
                    var p = video.play();
                    if (p && typeof p.catch === 'function') p.catch(function(){});
                } catch(e) {}
            }

            /* ---- Seek to correct position and play ---- */
            var inited = false;
            function initVideo() {
                if (inited) return;
                inited = true;
                var range = getRange();
                /* check saved time from previous visit */
                var saved = getSavedTime();
                var target = range.s;
                if (saved > range.s && saved < range.e) {
                    target = saved; /* resume from where user left off */
                }
                try { video.currentTime = target; } catch(e) {}
                tryPlay();
            }

            /* ---- Event: metadata ready ---- */
            video.addEventListener('loadedmetadata', function() { initVideo(); });

            /* ---- If already cached/loaded ---- */
            if (video.readyState >= 1) initVideo();

            /* ---- Fallback: try after delay ---- */
            setTimeout(function() {
                initVideo();
                if (video.paused) {
                    var tries = 0;
                    var iv = setInterval(function() {
                        tries++;
                        tryPlay();
                        if (!video.paused || tries >= 30) clearInterval(iv);
                    }, 500);
                }
            }, 800);

            /* ---- User-interaction unlock for restrictive browsers ---- */
            function unlock() {
                tryPlay();
                if (!video.paused) {
                    window.removeEventListener('click', unlock);
                    window.removeEventListener('touchstart', unlock);
                    window.removeEventListener('scroll', unlock);
                }
            }
            window.addEventListener('click', unlock, { passive: true });
            window.addEventListener('touchstart', unlock, { passive: true });
            window.addEventListener('scroll', unlock, { passive: true, once: true });

            /* ---- Loop within configured range + persist time ---- */
            var lastSaved = 0;
            video.addEventListener('timeupdate', function() {
                var range = getRange();
                var cur = video.currentTime;

                /* save position every ~1 second of change */
                if (Math.abs(cur - lastSaved) >= 1) {
                    lastSaved = cur;
                    saveTime(cur);
                }

                if (range.e <= range.s) return;
                /* past the end → loop to start */
                if (cur >= range.e) {
                    try { video.currentTime = range.s; } catch(e) {}
                    tryPlay();
                }
                /* before start (e.g. browser rewound to 0) → seek to start */
                else if (cur < range.s - 0.3) {
                    try { video.currentTime = range.s; } catch(e) {}
                }
            });

            /* ---- Save time on page leave ---- */
            function forceSave() { saveTime(video.currentTime || 0); }
            window.addEventListener('pagehide', forceSave);
            window.addEventListener('beforeunload', forceSave);
            document.addEventListener('visibilitychange', function() {
                if (document.visibilityState === 'hidden') forceSave();
            });

            /* ---- Resume if paused unexpectedly ---- */
            video.addEventListener('pause', function() {
                setTimeout(function(){ if (video.paused) tryPlay(); }, 200);
            });

            /* ---- Error: keep current text state (do not cancel fade) ---- */
            video.addEventListener('error', function() {});
        })();
        </script>
    </div>
    <?php
    return ob_get_clean();
}

function kv_render_why_choose_block($attributes = array()) {
    $attributes = wp_parse_args($attributes, array(
        'title' => 'Why choose us',
        'bodyLine1' => 'KV Electronics is more than a supplier—we are a long-term technical partner.',
        'bodyLine2' => 'We support customers from design through mass production, ensuring stable quality, fast response, and continuous improvement.',
    ));

    ob_start();
    ?>
    <div class="wp-block-group alignfull" style="padding-top:80px;padding-bottom:80px;">
        <div class="container">
            <h2 class="wp-block-heading has-text-align-center" style="margin-bottom:50px;font-size:36px;font-weight:700;"><?php echo esc_html($attributes['title']); ?></h2>
            <div style="max-width:980px;margin:0 auto;text-align:center;">
                <p style="margin:0 0 14px;color:#334155;font-size:20px;line-height:1.75;"><?php echo esc_html($attributes['bodyLine1']); ?></p>
                <p style="margin:0;color:#64748b;font-size:19px;line-height:1.85;"><?php echo esc_html($attributes['bodyLine2']); ?></p>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function kv_render_ready_started_block($attributes = array()) {
    $attributes = wp_parse_args($attributes, array(
        'title' => 'Ready to Get Started?',
        'subtitle' => 'Contact us today for custom solutions and quotations',
        'buttonText' => 'Get in Touch',
        'buttonUrl' => '/contact/',
    ));

    ob_start();
    ?>
    <div class="wp-block-group alignfull has-primary-background-color has-background" style="padding-top:60px;padding-bottom:60px;">
        <h2 class="wp-block-heading has-text-align-center has-white-color has-text-color" style="margin-bottom:15px;font-size:36px;"><?php echo esc_html($attributes['title']); ?></h2>
        <p class="has-text-align-center has-white-color has-text-color" style="margin-bottom:25px;font-size:18px;"><?php echo esc_html($attributes['subtitle']); ?></p>
        <div class="wp-block-buttons is-layout-flex" style="justify-content:center;">
            <div class="wp-block-button"><a class="wp-block-button__link has-primary-color has-white-background-color has-text-color has-background wp-element-button" href="<?php echo esc_url(home_url($attributes['buttonUrl'])); ?>" style="border-radius:6px;"><?php echo esc_html($attributes['buttonText']); ?></a></div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * [product_detail] - Outputs full product detail page content
 */
add_shortcode('product_detail', function() {
    if (!is_singular('product')) return '';
    
    global $post;
    $product_id = $post->ID;

    // --- Category & Breadcrumb ---
    $terms = wp_get_object_terms($product_id, 'product_category');
    $child_term = ($terms && !is_wp_error($terms)) ? $terms[0] : null;
    $parent_term = null;
    if ($child_term && $child_term->parent) {
        $parent_term = get_term($child_term->parent, 'product_category');
    }
    if ($child_term && !$child_term->parent) {
        $parent_term = $child_term;
        $child_term = null;
    }

    // --- ACF / Meta Fields ---
    $subtitle          = get_post_meta($product_id, 'pd_subtitle', true);

    // Gallery images - read from pd_gallery JSON array (fallback to old pd_image_1/2/3)
    $gallery_json = get_post_meta($product_id, 'pd_gallery', true);
    $images = [];
    
    if ($gallery_json && is_string($gallery_json)) {
        $decoded = json_decode($gallery_json, true);
        if (is_array($decoded)) {
            $images = array_filter(array_map(function($u) { return kv_rebase_url(esc_url($u)); }, $decoded));
        }
    }
    
    // Fallback to legacy pd_image_1, pd_image_2, pd_image_3 for backward compatibility
    if (empty($images)) {
        for ($i = 1; $i <= 3; $i++) {
            $url = get_post_meta($product_id, "pd_image_{$i}", true);
            if ($url) $images[] = kv_rebase_url(esc_url($url));
        }
    }
    
    if (empty($images) && has_post_thumbnail($product_id)) {
        $images[] = kv_rebase_url(get_the_post_thumbnail_url($product_id, 'large'));
    }

    // Specifications — use dynamic fields (builtin + custom)
    $all_spec_defs = function_exists('pm_get_all_spec_fields')
        ? pm_get_all_spec_fields()
        : [
            ['key' => 'pd_inductance',    'label' => 'Inductance'],
            ['key' => 'pd_current_rating','label' => 'Current Rating'],
            ['key' => 'pd_impedance',     'label' => 'Impedance @ 100MHz'],
            ['key' => 'pd_voltage',       'label' => 'Voltage Rating'],
            ['key' => 'pd_frequency',     'label' => 'Frequency Range'],
            ['key' => 'pd_size_range',    'label' => 'Size Range / Form Factor'],
            ['key' => 'pd_output_range',  'label' => 'Output Range'],
            ['key' => 'pd_temp_range',    'label' => 'Operating Temperatures'],
            ['key' => 'pd_package_type',  'label' => 'Packaging Options'],
            ['key' => 'pd_standards',     'label' => 'Standards'],
            ['key' => 'pd_winding',       'label' => 'Winding Construction'],
            ['key' => 'pd_core_shape',    'label' => 'Core Shape'],
            ['key' => 'pd_core_size',     'label' => 'Core Size'],
            ['key' => 'pd_bobbin_pin',    'label' => 'Bobbin Pin Type'],
            ['key' => 'pd_wire_type',     'label' => 'Wire Type'],
            ['key' => 'pd_wire_size',     'label' => 'Wire Size'],
        ];
    $spec_fields = [];
    foreach ($all_spec_defs as $sf) {
        $spec_fields[$sf['key']] = $sf['label'];
    }

    // Preferred display order for product specs
    $preferred_spec_order = [
        'pd_winding',
        'pd_core_shape',
        'pd_core_size',
        'pd_bobbin_pin',
        'pd_wire_type',
        'pd_wire_size',
        'pd_package_type',
        'pd_standards',
        'pd_temp_range',
    ];
    $ordered_spec_fields = [];
    foreach ($preferred_spec_order as $pref_key) {
        if (isset($spec_fields[$pref_key])) {
            $ordered_spec_fields[$pref_key] = $spec_fields[$pref_key];
        }
    }
    foreach ($spec_fields as $key => $label) {
        if (!isset($ordered_spec_fields[$key])) {
            $ordered_spec_fields[$key] = $label;
        }
    }
    $spec_fields = $ordered_spec_fields;

    // Load icons from DB (merged with defaults via pm_get_spec_icons if available)
    $spec_icons = function_exists('pm_get_spec_icons')
        ? pm_get_spec_icons()
        : array_merge([
            'pd_inductance'    => 'fa fa-bolt',
            'pd_current_rating'=> 'fa fa-tachometer',
            'pd_impedance'     => 'fa fa-signal',
            'pd_voltage'       => 'fa fa-plug',
            'pd_frequency'     => 'fa fa-line-chart',
            'pd_size_range'    => 'fa fa-expand',
            'pd_output_range'  => 'fa fa-exchange',
            'pd_temp_range'    => 'fa fa-thermometer-half',
            'pd_package_type'  => 'fa fa-cube',
            'pd_standards'     => 'fa fa-check-circle',
            'pd_winding'       => 'fa fa-random',
            'pd_core_shape'    => 'fa fa-diamond',
            'pd_core_size'     => 'fa fa-arrows-alt',
            'pd_bobbin_pin'    => 'fa fa-thumb-tack',
            'pd_wire_type'     => 'fa fa-chain',
            'pd_wire_size'     => 'fa fa-text-width',
        ], (array) get_option('spec_field_icons', []));
    $primary_specs = [];
    $other_specs = [];
    foreach ($spec_fields as $key => $label) {
        $val = get_post_meta($product_id, $key, true);
        if ($val !== '' && $val !== null && $val !== false) {
            $row = ['label' => $label, 'value' => (string) $val, 'icon' => $spec_icons[$key] ?? ''];
            if (in_array($key, $preferred_spec_order, true)) {
                $primary_specs[] = $row;
            } else {
                $other_specs[] = $row;
            }
        }
    }

    // Per-product custom attributes
    $custom_attrs_json = get_post_meta($product_id, 'pd_custom_attrs', true);
    $custom_attrs = $custom_attrs_json ? json_decode($custom_attrs_json, true) : [];
    if (is_array($custom_attrs)) {
        foreach ($custom_attrs as $attr) {
            $label = $attr['label'] ?? '';
            $value = $attr['value'] ?? '';
            if ($label !== '' && $value !== '') {
                $other_specs[] = ['label' => $label, 'value' => $value, 'icon' => ''];
            }
        }
    }

    $specs = array_merge($primary_specs, $other_specs);

    // Datasheet URL (uploaded file only)
    $datasheet_url = trim((string) get_post_meta($product_id, 'pd_datasheet', true));
    $series_name = get_the_title($product_id);

    ob_start();
    $_bg_color   = get_option('theme_primary_color', '#0056d6');
    $_banner_img = !empty($images) ? $images[0] : '';
    ?>
    <!-- Page Banner -->
    <section class="page-banner w-100" style="background-color:<?php echo esc_attr($_bg_color); ?>;padding:60px 0;position:relative;overflow:hidden;">
        <?php if ($_banner_img) : ?>
        <div style="position:absolute;inset:-30px;background-image:url('<?php echo esc_url($_banner_img); ?>');background-size:cover;background-position:center center;filter:blur(8px);transform:scale(1.15);z-index:0;opacity:0.9;"></div>
        <?php endif; ?>
        <div style="position:absolute;inset:0;background-color:<?php echo esc_attr($_bg_color); ?>;opacity:<?php echo $_banner_img ? '0.35' : '1'; ?>;z-index:1;"></div>
        <div class="container text-center position-relative" style="z-index:2;">
            <h1 style="color:#fff;font-size:clamp(28px,5vw,42px);font-weight:700;margin-bottom:12px;line-height:1.2;">
                <?php echo esc_html($series_name); ?>
            </h1>
            <nav aria-label="breadcrumb" style="display:inline-flex;align-items:center;gap:8px;color:rgba(255,255,255,0.85);font-size:15px;">
                <a href="<?php echo esc_url(home_url('/')); ?>" style="color:#fff;text-decoration:underline;white-space:nowrap;">Home</a>
                <span style="opacity:0.7;">/</span>
                <a href="<?php echo esc_url(home_url('/products/')); ?>" style="color:#fff;text-decoration:underline;white-space:nowrap;">Products</a>
                <?php if ($parent_term) : ?>
                    <span style="opacity:0.7;">/</span>
                    <a href="<?php echo esc_url(get_term_link($parent_term)); ?>" style="color:#fff;text-decoration:underline;white-space:nowrap;"><?php echo esc_html($parent_term->name); ?></a>
                <?php endif; ?>
                <span style="opacity:0.7;">/</span>
                <span style="white-space:nowrap;"><?php echo esc_html($series_name); ?></span>
            </nav>
        </div>
    </section>

    <!-- Product Detail -->
    <section class="product-detail" style="padding:60px 0;display:flex;justify-content:center;width:100%;">
        <div style="display:flex;flex-direction:column;align-items:center;max-width:900px;width:100%;padding:0 20px;">

            <!-- Gallery -->
            <?php if (!empty($images)) : ?>
            <div class="product-gallery" style="width:100%;margin-bottom:60px;">

                    <!-- Image wrapper -->
                    <div id="pd-img-wrapper" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,0.08);position:relative;cursor:crosshair;height:420px;">
                        <img id="pd-main-img" src="<?php echo esc_url($images[0]); ?>" alt="<?php echo esc_attr($series_name); ?> - View 1" style="width:100%;height:100%;object-fit:contain;display:block;transition:opacity 0.4s;opacity:1;">

                        <?php if (count($images) > 1) : ?><div style="position:absolute;bottom:12px;left:50%;transform:translateX(-50%);display:flex;gap:6px;"><?php foreach ($images as $idx => $img) : ?><span class="pd-dot" data-index="<?php echo $idx; ?>" style="width:8px;height:8px;border-radius:50%;background:<?php echo $idx === 0 ? 'var(--theme-accent)' : 'rgba(255,255,255,0.6)'; ?>;cursor:pointer;transition:background 0.3s;display:inline-block;"></span><?php endforeach; ?></div><?php endif; ?>
                        <!-- Progress bar (inside wrapper, absolute bottom) -->
                        <?php if (count($images) > 1) : ?><div style="position:absolute;bottom:0;left:0;right:0;height:3px;background:rgba(229,231,235,0.5);"><div id="pd-progress-bar" style="height:100%;width:0%;background:var(--theme-accent);transition:width 5s linear;"></div></div><?php endif; ?>
                    </div>

                    <!-- Thumbnails -->
                    <?php if (count($images) > 1) : ?>
                    <div style="display:flex;gap:10px;justify-content:center;margin-top:15px;"><?php foreach ($images as $idx => $img) : ?><img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($series_name); ?> - View <?php echo $idx + 1; ?>" class="pd-thumb<?php echo $idx === 0 ? ' active' : ''; ?>" data-index="<?php echo $idx; ?>" style="width:80px;height:60px;object-fit:cover;border-radius:8px;border:2px solid <?php echo $idx === 0 ? 'var(--theme-accent)' : '#e5e7eb'; ?>;cursor:pointer;transition:border-color 0.3s;"><?php endforeach; ?></div>
                    <?php endif; ?>

            </div>
            <?php endif; ?>

            <!-- Info -->
            <div class="product-info" style="width:100%;text-align:center;">
                <?php if (!empty($subtitle)) : ?>
                <p style="color:#334155;font-size:clamp(14px,2.2vw,18px);margin:0 0 22px;line-height:1.6;max-width:860px;margin-left:auto;margin-right:auto;">
                    <?php echo esc_html($subtitle); ?>
                </p>
                <?php endif; ?>
                <?php
                $post_obj = get_post($product_id);
                $post_content = $post_obj ? trim($post_obj->post_content) : '';
                if ($post_content) : ?>
                <div class="product-description" style="color:#475569;font-size:15px;line-height:1.8;margin-bottom:30px;text-align:left;">
                    <?php echo apply_filters('the_content', $post_content); ?>
                </div>
                <?php endif; ?>

                <?php if ($specs) : ?>
                <div style="margin-bottom:30px;">
                    <h3 style="font-size:18px;color:#1e293b;margin-bottom:15px;">Specifications</h3>
                    <table style="width:100%;border-collapse:collapse;">
                        <tbody>
                        <?php foreach ($specs as $i => $s) : ?>
                            <tr style="border-bottom:<?php echo ($i === count($specs) - 1) ? 'none' : '1px solid #e5e7eb'; ?>;">
                                <td style="padding:12px 0;color:#64748b;text-align:left;width:45%;vertical-align:top;">
                                    <div style="display:flex;align-items:flex-start;gap:10px;">
                                    <?php if (!empty($s['icon'])) : ?>
                                    <?php $ic = trim($s['icon']); if (strlen($ic) > 0 && $ic[0] === '<') : ?>
                                    <span style="color:var(--theme-primary);width:18px;height:18px;flex-shrink:0;display:inline-flex;align-items:center;justify-content:center;"><?php echo wp_kses_post($s['icon']); ?></span>
                                    <?php else : ?>
                                    <i class="<?php echo esc_attr($s['icon']); ?>" style="color:var(--theme-primary);width:18px;text-align:center;flex-shrink:0;line-height:1.6;display:inline-flex;align-items:center;justify-content:center;"></i>
                                    <?php endif; ?>
                                    <?php endif; ?>
                                    <span><?php echo esc_html($s['label']); ?></span>
                                    </div>
                                </td>
                                <td style="padding:12px 0;color:#1e293b;font-weight:500;text-align:left;white-space:pre-line;"><?php echo esc_html($s['value']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <div style="display:flex;gap:15px;justify-content:center;">
                    <?php if ($datasheet_url !== '') : ?>
                        <a href="#" class="btn btn-outline" style="padding:15px 40px;font-size:16px;" data-bs-toggle="modal" data-bs-target="#datasheetModal">Download Datasheet</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <?php if ($datasheet_url !== '') : ?>
    <div class="modal fade" id="datasheetModal" tabindex="-1" aria-labelledby="datasheetModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" style="border-bottom:1px solid #e5e7eb;">
                    <h5 class="modal-title fw-semibold" id="datasheetModalLabel" style="color:#1e293b;">Download Datasheet</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding:30px;">
                    <p style="color:#475569;margin-bottom:20px;">
                        Enter your name and email to receive the Datasheet for <?php echo esc_html($series_name); ?>
                    </p>
                    <div id="ds-error" style="display:none;background:#fef2f2;color:#dc2626;padding:10px 14px;border-radius:8px;margin-bottom:15px;font-size:14px;"></div>
                    <div style="margin-bottom:15px;">
                        <label style="display:block;font-weight:500;color:#1e293b;margin-bottom:6px;">Full Name</label>
                        <input type="text" id="ds-name" placeholder="e.g. John Smith" style="width:100%;padding:10px 14px;border:1px solid #cbd5e1;border-radius:8px;font-size:15px;outline:none;">
                    </div>
                    <div style="margin-bottom:5px;">
                        <label style="display:block;font-weight:500;color:#1e293b;margin-bottom:6px;">Email</label>
                        <input type="email" id="ds-email" placeholder="example@company.com" style="width:100%;padding:10px 14px;border:1px solid #cbd5e1;border-radius:8px;font-size:15px;outline:none;">
                    </div>
                </div>
                <div class="modal-footer" style="border-top:1px solid #e5e7eb;padding:20px 30px;gap:10px;">
                    <button type="button" class="btn btn-outline" data-bs-dismiss="modal" style="padding:10px 24px;">Cancel</button>
                    <button type="button" class="btn btn-primary" id="ds-submit" style="padding:10px 24px;">&#x1F4E5; Download Datasheet</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function() {
        var productId = <?php echo json_encode($product_id); ?>;
        var ajaxUrl   = <?php echo json_encode(admin_url('admin-ajax.php')); ?>;
        var nonce     = <?php echo json_encode(wp_create_nonce('datasheet_lead_nonce')); ?>;
        var submitBtn = document.getElementById("ds-submit");
        var errBox    = document.getElementById("ds-error");

        if (submitBtn) {
            submitBtn.addEventListener("click", function() {
                var nameVal  = document.getElementById("ds-name").value.trim();
                var emailVal = document.getElementById("ds-email").value.trim();
                errBox.style.display = "none";

                if (!nameVal || !emailVal) {
                    errBox.textContent = "Please enter your full name and email";
                    errBox.style.display = "block";
                    return;
                }
                var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRe.test(emailVal)) {
                    errBox.textContent = "Invalid email format";
                    errBox.style.display = "block";
                    return;
                }

                submitBtn.disabled = true;
                submitBtn.innerHTML = "⏳ Saving...";

                var fd = new FormData();
                fd.append("action", "save_datasheet_lead");
                fd.append("nonce", nonce);
                fd.append("lead_name", nameVal);
                fd.append("lead_email", emailVal);
                fd.append("product_id", productId);

                fetch(ajaxUrl, { method: "POST", body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(res) {
                        if (res.success) {
                            var modal = bootstrap.Modal.getInstance(document.getElementById("datasheetModal"));
                            if (modal) modal.hide();
                            document.getElementById("ds-name").value = "";
                            document.getElementById("ds-email").value = "";
                            var a = document.createElement("a");
                            a.href = res.data.download_url;
                            a.download = "";
                            a.target = "_blank";
                            document.body.appendChild(a);
                            a.click();
                            document.body.removeChild(a);
                        } else {
                            errBox.textContent = res.data.message || "An error occurred. Please try again.";
                            errBox.style.display = "block";
                        }
                    })
                    .catch(function() {
                        errBox.textContent = "Connection error. Please try again.";
                        errBox.style.display = "block";
                    })
                    .finally(function() {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = "\uD83D\uDCE5 Download Datasheet";
                    });
            });
        }
    })();
    </script>
    <?php endif; ?>

    <?php
    // Related Products
    $related_query_args = [
        'post_type'      => 'product',
        'posts_per_page' => 3,
        'post__not_in'   => [$product_id],
        'orderby'        => 'rand',
    ];
    if ($parent_term) {
        $related_query_args['tax_query'] = [[
            'taxonomy'         => 'product_category',
            'field'            => 'term_id',
            'terms'            => $parent_term->term_id,
            'include_children' => true,
        ]];
    }
    $related = new WP_Query($related_query_args);
    if ($related->have_posts()) :
    ?>
    <section class="pd-related">
        <div class="container">
            <h2>Related Products</h2>
            <div class="pd-related-grid">
                <?php while ($related->have_posts()) : $related->the_post();
                    $rel_img = '';
                    $rel_gallery = json_decode(get_post_meta(get_the_ID(), 'pd_gallery', true), true);
                    if (!empty($rel_gallery) && is_array($rel_gallery)) {
                        $rel_img = kv_rebase_url($rel_gallery[0]);
                    }
                    if (!$rel_img) {
                        $rel_img = kv_rebase_url(get_post_meta(get_the_ID(), 'pd_image_1', true));
                    }
                    if (!$rel_img && has_post_thumbnail()) {
                        $rel_img = kv_rebase_url(get_the_post_thumbnail_url(get_the_ID(), 'medium_large'));
                    }
                ?>
                <div class="pd-related-card">
                    <div class="pd-related-img">
                        <?php if ($rel_img) : ?>
                        <img src="<?php echo esc_url($rel_img); ?>" alt="<?php the_title_attribute(); ?>">
                        <?php else : ?>
                        <div style="width:100%;height:160px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="#cbd5e1" viewBox="0 0 16 16"><path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/><path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2H2zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1H14z"/></svg>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="pd-related-body">
                        <h3><?php the_title(); ?></h3>
                        <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 12)); ?></p>
                        <a href="<?php the_permalink(); ?>" class="btn-pd-outline">View Details</a>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>
    <?php
    wp_reset_postdata();
    endif;
    ?>

    <?php if (count($images) > 1) : ?>
    <script>
    (function () {
        var images = <?php echo json_encode(array_values($images)); ?>;
        var current = 0;
        var total = images.length;
        var interval = <?php echo max(1000, (int) get_option('gallery_interval', 5000)); ?>;
        var mainImg = document.getElementById("pd-main-img");
        var thumbs = document.querySelectorAll(".pd-thumb");
        var dots = document.querySelectorAll(".pd-dot");
        var bar = document.getElementById("pd-progress-bar");
        var timer = null;

        function goTo(idx) {
            current = (idx + total) % total;
            mainImg.style.opacity = "0";
            setTimeout(function () {
                mainImg.src = images[current];
                mainImg.style.opacity = "1";
            }, 200);
            thumbs.forEach(function (t, i) {
                t.style.borderColor = i === current ? (window.themeColor||'#0056d6') : "#e5e7eb";
            });
            dots.forEach(function (d, i) {
                d.style.background = i === current ? (window.themeColor||'#0056d6') : "rgba(255,255,255,0.6)";
            });
            bar.style.transition = "none";
            bar.style.width = "0%";
            setTimeout(function () {
                bar.style.transition = "width " + (interval / 1000) + "s linear";
                bar.style.width = "100%";
            }, 50);
        }

        var paused = false;
        var pausedAt = 0;
        var slideStartTime = 0;

        function startAuto(remaining) {
            clearTimeout(timer);
            var delay = (remaining !== undefined) ? remaining : interval;
            slideStartTime = Date.now();
            timer = setTimeout(function () {
                goTo(current + 1);
                startAuto();
            }, delay);
        }

        function pauseAuto() {
            if (paused) return;
            paused = true;
            clearTimeout(timer);
            var currentWidth = parseFloat(getComputedStyle(bar).width);
            var totalWidth   = parseFloat(getComputedStyle(bar.parentNode).width);
            var pct = totalWidth > 0 ? (currentWidth / totalWidth) * 100 : 0;
            bar.style.transition = "none";
            bar.style.width = pct + "%";
            var elapsed = Date.now() - slideStartTime;
            pausedAt = interval - elapsed;
            if (pausedAt < 0) pausedAt = 0;
        }

        function resumeAuto() {
            if (!paused) return;
            paused = false;
            var remaining = pausedAt;
            setTimeout(function () {
                bar.style.transition = "width " + (remaining / 1000) + "s linear";
                bar.style.width = "100%";
            }, 50);
            startAuto(remaining);
        }

        var gallery = document.querySelector(".product-gallery");
        if (gallery) {
            gallery.addEventListener("mouseenter", pauseAuto);
            gallery.addEventListener("mouseleave", resumeAuto);
        }

        thumbs.forEach(function (t) {
            t.addEventListener("click", function () {
                var idx = parseInt(this.getAttribute("data-index"));
                paused = false;
                goTo(idx);
                startAuto();
            });
        });

        dots.forEach(function (d) {
            d.addEventListener("click", function () {
                var idx = parseInt(this.getAttribute("data-index"));
                paused = false;
                goTo(idx);
                startAuto();
            });
        });

        goTo(0);
        startAuto();
    })();
    </script>
    <?php endif; ?>

    <!-- Image Zoom JS -->
    <script>
    (function() {
        var wrapper = document.getElementById('pd-img-wrapper');
        var mainImg = document.getElementById('pd-main-img');
        if (!wrapper || !mainImg) return;

        var MIN_ZOOM = 1.2, MAX_ZOOM = 5, currentZoom = 2.5;
        var active = false, dragging = false;
        var dragStartY = 0, dragStartZoom = 2.5;

        /* ── floating zoom-bar indicator ── */
        var ind = document.createElement('div');
        ind.style.cssText = 'display:none;position:fixed;pointer-events:none;z-index:99999;transform:translateY(-50%);user-select:none;';

        var inner = document.createElement('div');
        inner.style.cssText = 'background:rgba(15,23,42,0.85);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);' +
            'border-radius:22px;padding:8px 7px;display:flex;flex-direction:column;align-items:center;gap:4px;' +
            'box-shadow:0 4px 20px rgba(0,0,0,0.5);border:1px solid rgba(255,255,255,0.12);';

        var svgUp = '<svg width="12" height="12" viewBox="0 0 12 12"><path d="M6 2L2 8h8z" fill="#93dedd"/></svg>';
        var svgDn = '<svg width="12" height="12" viewBox="0 0 12 12"><path d="M6 10L2 4h8z" fill="#93dedd"/></svg>';

        var trackWrap = document.createElement('div');
        trackWrap.style.cssText = 'width:6px;height:68px;background:rgba(255,255,255,0.15);border-radius:3px;position:relative;overflow:hidden;';
        var fillEl = document.createElement('div');
        fillEl.style.cssText = 'position:absolute;bottom:0;left:0;right:0;border-radius:3px;background:linear-gradient(to top,var(--theme-accent-dark),var(--theme-accent));height:50%;';
        trackWrap.appendChild(fillEl);

        var labelEl = document.createElement('span');
        labelEl.style.cssText = 'color:#e2e8f0;font-size:9px;font-family:monospace;margin-top:1px;';
        labelEl.textContent = '2.5×';

        inner.innerHTML = '';
        inner.insertAdjacentHTML('beforeend', svgUp);
        inner.appendChild(trackWrap);
        inner.insertAdjacentHTML('beforeend', svgDn);
        inner.appendChild(labelEl);
        ind.appendChild(inner);
        document.body.appendChild(ind);

        function showIndicator(cx, cy) {
            var pct = (currentZoom - MIN_ZOOM) / (MAX_ZOOM - MIN_ZOOM) * 100;
            fillEl.style.height = pct + '%';
            labelEl.textContent = currentZoom.toFixed(1) + '×';
            ind.style.left = (cx + 22) + 'px';
            ind.style.top  = cy + 'px';
            ind.style.display = 'block';
        }

        function applyZoom(cx, cy) {
            var r = wrapper.getBoundingClientRect();
            mainImg.style.transformOrigin = ((cx - r.left) / r.width * 100) + '% ' + ((cy - r.top) / r.height * 100) + '%';
            mainImg.style.transform = 'scale(' + currentZoom + ')';
        }

        function onDocMove(e) {
            if (!dragging) return;
            var dy = dragStartY - e.clientY;
            currentZoom = Math.max(MIN_ZOOM, Math.min(MAX_ZOOM, dragStartZoom + dy * 0.025));
            mainImg.style.transform = 'scale(' + currentZoom + ')';
            showIndicator(e.clientX, e.clientY);
        }
        function onDocUp(e) {
            if (!dragging) return;
            dragging = false;
            document.removeEventListener('mousemove', onDocMove);
            document.removeEventListener('mouseup',   onDocUp);
            if (active) {
                wrapper.style.cursor = 'zoom-in';
                applyZoom(e.clientX, e.clientY);
                showIndicator(e.clientX, e.clientY);
            }
        }

        wrapper.addEventListener('mouseenter', function(e) {
            if (window.innerWidth < 992) return;
            active = true;
            mainImg.style.transition = 'none';
            wrapper.style.cursor = 'crosshair';
            applyZoom(e.clientX, e.clientY);
            showIndicator(e.clientX, e.clientY);
        });

        wrapper.addEventListener('mousemove', function(e) {
            if (!active || dragging || window.innerWidth < 992) return;
            applyZoom(e.clientX, e.clientY);
            showIndicator(e.clientX, e.clientY);
        });

        wrapper.addEventListener('mousedown', function(e) {
            if (!active || window.innerWidth < 992) return;
            e.preventDefault();
            dragging      = true;
            dragStartY    = e.clientY;
            dragStartZoom = currentZoom;
            wrapper.style.cursor = 'ns-resize';
            document.addEventListener('mousemove', onDocMove);
            document.addEventListener('mouseup',   onDocUp);
        });

        wrapper.addEventListener('mouseleave', function() {
            if (dragging) return;
            active = false;
            ind.style.display = 'none';
            mainImg.style.transition = 'transform 0.25s ease';
            mainImg.style.transform = '';
            mainImg.style.transformOrigin = '';
            wrapper.style.cursor = '';
        });

        window.addEventListener('resize', function() {
            if (window.innerWidth < 992) {
                active = dragging = false;
                document.removeEventListener('mousemove', onDocMove);
                document.removeEventListener('mouseup',   onDocUp);
                ind.style.display = 'none';
                mainImg.style.transform = '';
                mainImg.style.transformOrigin = '';
            }
        });
    })();
    </script>

    <?php
    return ob_get_clean();
});

/**
 * [product_category_archive] - Outputs product category archive content
 */
add_shortcode('product_category_archive', function() {
    if (!is_tax('product_category')) return '';
    
    $term = get_queried_object();
    $parent_term = null;
    if ($term->parent) {
        $parent_term = get_term($term->parent, 'product_category');
    }

    // Banner image: first product in this category
    $_bg_color   = get_option('theme_primary_color', '#0056d6');
    $_first_p = get_posts([
        'post_type'      => 'product',
        'posts_per_page' => 1,
        'post_status'    => ['publish', 'draft'],
        'tax_query'      => [[
            'taxonomy'         => 'product_category',
            'field'            => 'term_id',
            'terms'            => $term->term_id,
            'include_children' => true,
        ]],
        'orderby' => 'date', 'order' => 'DESC',
    ]);
    $_banner_img = '';
    if ($_first_p) {
        $_ga = json_decode(get_post_meta($_first_p[0]->ID, 'pd_gallery', true), true);
        if (!empty($_ga)) $_banner_img = $_ga[0];
        if (!$_banner_img) $_banner_img = get_post_meta($_first_p[0]->ID, 'pd_image_1', true);
        if (!$_banner_img && has_post_thumbnail($_first_p[0]->ID)) $_banner_img = get_the_post_thumbnail_url($_first_p[0]->ID, 'large');
    }

    ob_start();
    ?>
    <!-- Page Banner -->
    <section class="page-banner w-100" style="background-color:<?php echo esc_attr($_bg_color); ?>;padding:60px 0;position:relative;overflow:hidden;">
        <?php if ($_banner_img) : ?>
        <div style="position:absolute;inset:-30px;background-image:url('<?php echo esc_url($_banner_img); ?>');background-size:cover;background-position:center center;filter:blur(8px);transform:scale(1.15);z-index:0;opacity:0.9;"></div>
        <?php endif; ?>
        <div style="position:absolute;inset:0;background-color:<?php echo esc_attr($_bg_color); ?>;opacity:<?php echo $_banner_img ? '0.35' : '1'; ?>;z-index:1;"></div>
        <div class="container text-center position-relative" style="z-index:2;">
            <h1 style="color:#fff;font-size:clamp(28px,5vw,42px);font-weight:700;margin-bottom:12px;line-height:1.2;">
                <?php echo esc_html($term->name); ?>
            </h1>
            <nav aria-label="breadcrumb" style="display:inline-flex;align-items:center;gap:8px;color:rgba(255,255,255,0.85);font-size:15px;">
                <a href="<?php echo esc_url(home_url('/')); ?>" style="color:#fff;text-decoration:underline;white-space:nowrap;">Home</a>
                <span style="opacity:0.7;">/</span>
                <a href="<?php echo esc_url(home_url('/products/')); ?>" style="color:#fff;text-decoration:underline;white-space:nowrap;">Products</a>
                <?php if ($parent_term) : ?>
                    <span style="opacity:0.7;">/</span>
                    <a href="<?php echo esc_url(get_term_link($parent_term)); ?>" style="color:#fff;text-decoration:underline;white-space:nowrap;"><?php echo esc_html($parent_term->name); ?></a>
                <?php endif; ?>
                <span style="opacity:0.7;">/</span>
                <span style="white-space:nowrap;"><?php echo esc_html($term->name); ?></span>
            </nav>
        </div>
    </section>

    <!-- Product Cards -->
    <section style="padding:60px 0;">
        <div class="container">
            <div class="row g-4">
                <?php
                $products = new WP_Query([
                    'post_type'      => 'product',
                    'posts_per_page' => -1,
                    'tax_query'      => [[
                        'taxonomy'         => 'product_category',
                        'field'            => 'term_id',
                        'terms'            => $term->term_id,
                        'include_children' => true,
                    ]],
                    'orderby' => 'title',
                    'order'   => 'ASC',
                ]);

                if ($products->have_posts()) :
                    while ($products->have_posts()) : $products->the_post();
                        $image = '';
                        $gallery = json_decode(get_post_meta(get_the_ID(), 'pd_gallery', true), true);
                        if (!empty($gallery) && is_array($gallery)) {
                            $image = $gallery[0];
                        }
                        if (!$image) {
                            $image = get_post_meta(get_the_ID(), 'pd_image_1', true);
                        }
                        if (!$image && has_post_thumbnail()) {
                            $image = get_the_post_thumbnail_url(get_the_ID(), 'medium_large');
                        }
                        $spec_fields = [
                            'pd_inductance'    => 'Inductance',
                            'pd_current_rating'=> 'Current Rating',
                            'pd_impedance'     => 'Impedance',
                            'pd_voltage'       => 'Voltage Rating',
                            'pd_frequency'     => 'Frequency Range',
                            'pd_temp_range'    => 'Operating Temperature',
                            'pd_package_type'  => 'Package Type',
                        ];
                        $specs = [];
                        foreach ($spec_fields as $key => $label) {
                            $val = get_post_meta(get_the_ID(), $key, true);
                            if ($val) {
                                $specs[] = ['label' => $label, 'value' => $val];
                            }
                        }
                ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="product-item">
                        <?php if ($image) : ?>
                        <img decoding="async" src="<?php echo esc_url($image); ?>" alt="<?php the_title_attribute(); ?>">
                        <?php else : ?>
                        <div style="width:100%;height:200px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#cbd5e1" viewBox="0 0 16 16"><path d="M6.002 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/><path d="M2.002 1a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V3a2 2 0 0 0-2-2H2zm12 1a1 1 0 0 1 1 1v6.5l-3.777-1.947a.5.5 0 0 0-.577.093l-3.71 3.71-2.66-1.772a.5.5 0 0 0-.63.062L1.002 12V3a1 1 0 0 1 1-1H14z"/></svg>
                        </div>
                        <?php endif; ?>
                        <div class="product-info">
                            <h3><?php the_title(); ?></h3>
                            <p><?php echo esc_html(get_the_excerpt()); ?></p>
                            <a href="<?php the_permalink(); ?>" class="btn btn-outline">View Details</a>
                        </div>
                    </div>
                </div>
                <?php
                    endwhile;
                    wp_reset_postdata();
                else :
                ?>
                <div class="col-12 text-center" style="padding:60px 0;">
                    <div style="max-width:400px;margin:0 auto;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="#cbd5e1" viewBox="0 0 16 16" style="margin-bottom:20px;">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
                            <path d="M4.285 12.433a.5.5 0 0 0 .683-.183A3.5 3.5 0 0 1 8 10.5c1.295 0 2.426.703 3.032 1.75a.5.5 0 0 0 .866-.5A4.5 4.5 0 0 0 8 9.5a4.5 4.5 0 0 0-3.898 2.25.5.5 0 0 0 .183.683M7 6.5C7 7.328 6.552 8 6 8s-1-.672-1-1.5S5.448 5 6 5s1 .672 1 1.5m4 0c0 .828-.448 1.5-1 1.5s-1-.672-1-1.5S9.448 5 10 5s1 .672 1 1.5"/>
                        </svg>
                        <h4 style="color:#64748b;font-weight:600;">No products found</h4>
                        <p style="color:#94a3b8;">There are no products in this category yet.</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <?php
    return ob_get_clean();
});

/* ================================================================
   CUSTOM LOGIN PAGE – KV Electronics Branding
   ================================================================ */

function kv_clean_text_option_value($value, $fallback = '') {
    $text = trim((string) $value);
    if ($text === '') return (string) $fallback;

    $lower = strtolower($text);
    if (
        str_contains($lower, 'warning: undefined variable')
        || str_contains($lower, '/admin/theme-settings-ui.php')
        || str_contains($lower, ' on line ')
    ) {
        return (string) $fallback;
    }

    return $text;
}

add_action('init', function () {
    $targets = [
        'site_company_name'   => 'KV Electronics',
        'site_copyright'      => 'All rights reserved.',
        'site_phone'          => '',
        'site_fax'            => '',
        'site_email'          => 'info@company.com',
        'site_email_sales'    => 'sales@company.com',
        'site_address'        => '123 Industrial Zone, Bangkok, Thailand',
        'site_address_full'   => "123 Industrial Zone\nBangna-Trad Road\nBangkok 10260, Thailand",
        'site_hours_weekday'  => 'Monday – Friday: 8:00 AM – 5:00 PM',
        'site_hours_weekend'  => 'Saturday – Sunday: Closed',
        'site_map_embed'      => '',
    ];

    foreach ($targets as $key => $fallback) {
        $raw = get_option($key, get_theme_mod($key, $fallback));
        $clean = kv_clean_text_option_value($raw, $fallback);
        if ($clean !== $raw) {
            update_option($key, $clean);
            set_theme_mod($key, $clean);
        }
    }
});

add_filter('option_site_company_name', function ($value) {
    return kv_clean_text_option_value($value, 'KV Electronics');
});

add_filter('option_site_copyright', function ($value) {
    return kv_clean_text_option_value($value, 'All rights reserved.');
});

/**
 * 1) โลโก้ลิงก์ → หน้าแรก (แทน wordpress.org)
 */
add_filter('login_headerurl', function () {
    return home_url('/');
});

/**
 * 2) Tooltip ของโลโก้ → ชื่อบริษัท
 */
add_filter('login_headertext', function () {
    $company = get_option('site_company_name', get_theme_mod('site_company_name', 'KV Electronics'));
    return kv_clean_text_option_value($company, 'KV Electronics');
});

/**
 * 3) CSS หน้า Login — ดึงสีจาก Theme Settings (60:30:10)
 */
add_action('login_enqueue_scripts', function () {
    $logo = kv_rebase_url(get_option('site_logo_url', get_theme_mod('site_logo_url', '')));
    if (!$logo) {
        $logo = kv_rebase_url(get_site_icon_url(256));
    }
    if (!$logo) {
        $logo = kv_rebase_url(home_url('/wp-content/uploads/2026/02/New-Logo-Schott-1-1-300x120-1.jpg'));
    }
    $primary = get_option('theme_primary_color', '#0056d6');
    $accent  = get_option('theme_accent_color',  '#4ecdc4');
    $bg      = get_option('theme_bg_color',       '#ffffff');
    $company = kv_clean_text_option_value(
        get_option('site_company_name', get_theme_mod('site_company_name', 'KV Electronics')),
        'KV Electronics'
    );

    /* hex → rgb สำหรับ rgba() */
    $r = hexdec(substr($primary, 1, 2));
    $g = hexdec(substr($primary, 3, 2));
    $b = hexdec(substr($primary, 5, 2));
    ?>
    <style>
        /* ────── 60 % — Background ────── */
        body.login {
            background: <?php echo esc_attr($bg); ?> !important;
            background-image:
                radial-gradient(ellipse at 20% 50%, rgba(<?php echo "$r,$g,$b"; ?>, 0.04) 0%, transparent 50%),
                radial-gradient(ellipse at 80% 50%, rgba(<?php echo "$r,$g,$b"; ?>, 0.03) 0%, transparent 50%) !important;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        /* ────── Logo ────── */
        #login h1 a {
            <?php if ($logo) : ?>
            background-image: url('<?php echo esc_url($logo); ?>') !important;
            background-size: contain !important;
            background-position: center !important;
            background-repeat: no-repeat !important;
            width: 280px !important;
            height: 80px !important;
            <?php else : ?>
            background-image: none !important;
            width: auto !important;
            height: auto !important;
            font-size: 0;
            <?php endif; ?>
            margin: 0 auto 16px !important;
            padding: 0 !important;
        }

        <?php if (!$logo) : ?>
        #login h1 a::after {
            content: '<?php echo esc_js($company); ?>';
            font-size: 28px;
            font-weight: 700;
            color: <?php echo esc_attr($primary); ?>;
            letter-spacing: -0.5px;
        }
        <?php endif; ?>

        /* ────── Login Box ────── */
        .login form {
            background: #ffffff !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 16px !important;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08) !important;
            padding: 28px 32px !important;
        }

        .login form label {
            color: #334155 !important;
            font-weight: 600 !important;
            font-size: 13px !important;
        }

        .login form input[type="text"],
        .login form input[type="password"] {
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 8px !important;
            padding: 10px 14px !important;
            font-size: 14px !important;
            transition: border-color .2s, box-shadow .2s !important;
        }

        .login form input[type="text"]:focus,
        .login form input[type="password"]:focus {
            border-color: <?php echo esc_attr($primary); ?> !important;
            box-shadow: 0 0 0 3px rgba(<?php echo "$r,$g,$b"; ?>, 0.15) !important;
            outline: none !important;
        }

        /* ────── 30 % — Primary Button ────── */
        .wp-core-ui .button-primary {
            background: <?php echo esc_attr($primary); ?> !important;
            border-color: <?php echo esc_attr($primary); ?> !important;
            border-radius: 8px !important;
            padding: 8px 24px !important;
            font-size: 14px !important;
            font-weight: 600 !important;
            text-shadow: none !important;
            box-shadow: 0 2px 8px rgba(<?php echo "$r,$g,$b"; ?>, 0.25) !important;
            transition: opacity .2s, transform .1s !important;
            height: auto !important;
            line-height: 1.6 !important;
        }

        .wp-core-ui .button-primary:hover,
        .wp-core-ui .button-primary:focus {
            opacity: 0.9 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 12px rgba(<?php echo "$r,$g,$b"; ?>, 0.35) !important;
        }

        /* ────── 10 % — Accent links ────── */
        .login #nav a,
        .login #backtoblog a {
            color: <?php echo esc_attr($primary); ?> !important;
            text-decoration: none !important;
            font-size: 13px !important;
            transition: color .2s !important;
        }

        .login #nav a:hover,
        .login #backtoblog a:hover {
            color: <?php echo esc_attr($accent); ?> !important;
            text-decoration: underline !important;
        }

        .login #nav,
        .login #backtoblog {
            text-align: center !important;
            padding: 0 !important;
        }

        /* ────── Remember‑me ────── */
        .login .forgetmenot label {
            font-size: 13px !important;
        }

        /* ────── Error / Message boxes ────── */
        .login .message,
        .login .success,
        .login #login_error {
            border-radius: 8px !important;
            border-left: 4px solid <?php echo esc_attr($primary); ?> !important;
            font-size: 13px !important;
        }
        .login #login_error {
            border-left-color: #ef4444 !important;
        }

        /* ────── Copyright footer ────── */
        #login::after {
            content: '\00A9 <?php echo date('Y'); ?> <?php echo esc_js($company); ?>';
            display: block;
            text-align: center;
            color: #94a3b8;
            font-size: 12px;
            margin-top: 24px;
        }

        /* ────── Password eye icon ────── */
        .login .wp-pwd .button.wp-hide-pw {
            color: <?php echo esc_attr($primary); ?> !important;
        }

        /* ────── Responsive ────── */
        @media (max-width: 480px) {
            #login {
                width: 92% !important;
                padding: 16px !important;
            }
            .login form {
                padding: 20px !important;
            }
        }
    </style>
    <?php
});