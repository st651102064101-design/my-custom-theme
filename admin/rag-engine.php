<?php
/**
 * RAG Search Engine — Level 3: Semantic Search + Vector-like Scoring
 * 
 * Architecture:
 * 1. Custom search index table (wp_rag_search_index) with FULLTEXT
 * 2. Synonym dictionary (Thai ↔ English, product aliases)
 * 3. TF-IDF-like scoring with weighted fields
 * 4. Query expansion, tokenization, stop-word removal
 * 5. Bilingual support (Thai + English)
 * 
 * @package KV_Electronics
 */

if (!defined('ABSPATH')) exit;

/* ============================================================
   1. DATABASE — Create / Upgrade search index table
   ============================================================ */

function rag_create_search_index_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'rag_search_index';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$table} (
        id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        post_id         BIGINT UNSIGNED NOT NULL DEFAULT 0,
        doc_type        VARCHAR(50)     NOT NULL DEFAULT 'product',
        title           VARCHAR(500)    NOT NULL DEFAULT '',
        content         LONGTEXT        NOT NULL,
        content_thai    TEXT            NOT NULL DEFAULT '',
        category        VARCHAR(255)    NOT NULL DEFAULT '',
        meta_json       LONGTEXT        NOT NULL DEFAULT '',
        url             VARCHAR(500)    NOT NULL DEFAULT '',
        tokens          LONGTEXT        NOT NULL DEFAULT '',
        word_count      INT UNSIGNED    NOT NULL DEFAULT 0,
        updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY idx_post (post_id, doc_type),
        FULLTEXT KEY ft_content (title, content, category, tokens)
    ) {$charset};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

function rag_create_chat_logs_table() {
    global $wpdb;
    $table = $wpdb->prefix . 'rag_chat_logs';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS {$table} (
        id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        query_text       TEXT            NOT NULL,
        lang             VARCHAR(10)     NOT NULL DEFAULT 'en',
        result_count     INT UNSIGNED    NOT NULL DEFAULT 0,
        success          TINYINT(1)      NOT NULL DEFAULT 0,
        response_time_ms INT UNSIGNED    NOT NULL DEFAULT 0,
        ip_address       VARCHAR(45)     NOT NULL DEFAULT '',
        created_at       DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_created (created_at),
        KEY idx_success (success)
    ) {$charset};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
register_activation_hook(get_template_directory() . '/functions.php', 'rag_create_search_index_table');
register_activation_hook(get_template_directory() . '/functions.php', 'rag_create_chat_logs_table');

// Also create on theme switch
add_action('after_switch_theme', 'rag_create_search_index_table');
add_action('after_switch_theme', 'rag_create_chat_logs_table');

// Ensure table exists on admin init (safe — uses IF NOT EXISTS)
add_action('admin_init', function() {
    $done = get_option('rag_table_created', false);
    if (!$done) {
        rag_create_search_index_table();
        update_option('rag_table_created', '1');
    }

    if (get_option('rag_chat_logs_table_version') !== '1.0') {
        rag_create_chat_logs_table();
        update_option('rag_chat_logs_table_version', '1.0');
    }
});

function rag_log_chat_query($query, $lang, $result) {
    global $wpdb;

    $table = $wpdb->prefix . 'rag_chat_logs';
    $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
    if ($exists !== $table) {
        rag_create_chat_logs_table();
    }

    $sources = (is_array($result) && isset($result['sources']) && is_array($result['sources'])) ? $result['sources'] : [];
    $answer  = is_array($result) ? (string) ($result['answer'] ?? '') : '';

    $result_count = count($sources);
    $has_answer   = trim(wp_strip_all_tags($answer)) !== '';
    $success      = ($result_count > 0 && $has_answer) ? 1 : 0;
    $time_ms      = is_array($result) ? absint($result['search_time'] ?? 0) : 0;

    $wpdb->insert(
        $table,
        [
            'query_text'       => sanitize_text_field((string) $query),
            'lang'             => in_array($lang, ['en', 'th'], true) ? $lang : 'en',
            'result_count'     => $result_count,
            'success'          => $success,
            'response_time_ms' => $time_ms,
            'ip_address'       => sanitize_text_field($_SERVER['REMOTE_ADDR'] ?? ''),
            'created_at'       => current_time('mysql'),
        ],
        ['%s', '%s', '%d', '%d', '%d', '%s', '%s']
    );
}


/* ============================================================
   2. SYNONYM DICTIONARY — Thai ↔ English + Product Aliases
   ============================================================ */

function rag_get_synonym_map() {
    return array(
        // ── Thai → English Product Terms ──
        'หม้อแปลง'        => array('transformer', 'transformers'),
        'หม้อแปลงไฟฟ้า'    => array('transformer', 'power transformer'),
        'ตัวเหนี่ยวนำ'      => array('inductor', 'inductors'),
        'คอยล์'           => array('coil', 'coils'),
        'ขดลวด'          => array('coil', 'coils', 'winding'),
        'แผงวงจร'         => array('pcb', 'printed circuit board'),
        'แผงวงจรพิมพ์'     => array('pcb', 'printed circuit board'),
        'สินค้า'           => array('product', 'products'),
        'ผลิตภัณฑ์'        => array('product', 'products'),
        'ราคา'            => array('price', 'pricing', 'cost'),
        'ขนาด'           => array('size', 'dimension', 'size range'),
        'แรงดัน'          => array('voltage'),
        'กระแส'           => array('current', 'current rating'),
        'ความถี่'          => array('frequency'),
        'อุณหภูมิ'         => array('temperature', 'temp range'),
        'มาตรฐาน'        => array('standard', 'standards', 'certification'),
        'ติดต่อ'           => array('contact', 'contact us'),
        'ที่อยู่'           => array('address', 'location'),
        'โทรศัพท์'         => array('phone', 'telephone'),
        'อีเมล'           => array('email'),
        'บริษัท'           => array('company', 'about us'),
        'ข้อมูลบริษัท'      => array('company info', 'about us'),
        'ประกอบ'          => array('assembly', 'assemblies'),
        'สายไฟ'           => array('wire', 'wire assembly'),
        'คุณสมบัติ'         => array('specification', 'specs', 'features'),
        'รายละเอียด'       => array('detail', 'details', 'specification'),
        'ทั้งหมด'          => array('all', 'every'),
        'ประเภท'          => array('type', 'category', 'categories'),

        // ── English → Expanded ──
        'transformer'      => array('transformers', 'หม้อแปลง', 'power', 'flyback', 'isolation', 'gate drive', 'digital audio', 'safety agency'),
        'inductor'         => array('inductors', 'ตัวเหนี่ยวนำ', 'common mode', 'high current', 'surface mount', 'toroid', 'choke'),
        'coil'            => array('coils', 'คอยล์', 'air coil', 'copper foil', 'trapezoidal', 'universal winding'),
        'pcb'             => array('printed circuit board', 'circuit board', 'แผงวงจร'),
        'rohs'            => array('rohs3', 'rohs compliant', 'lead free', 'environment'),
        'ipc'             => array('ipc-a-610', 'ipc standard', 'quality'),
        'custom'          => array('custom wound', 'custom design', 'bespoke'),
        'assembly'        => array('assemblies', 'ประกอบ', 'integrated', 'wire assembly', 'pcb assembly'),
        'contact'         => array('ติดต่อ', 'phone', 'email', 'address', 'reach us'),
        'specs'           => array('specification', 'specifications', 'technical', 'datasheet'),
        'products'        => array('product', 'สินค้า', 'ผลิตภัณฑ์', 'catalog'),
        'voltage'         => array('แรงดัน', 'volt', 'v'),
        'temperature'     => array('อุณหภูมิ', 'temp', 'thermal'),
        'standard'        => array('standards', 'มาตรฐาน', 'certification', 'certified', 'compliant'),
        'size'            => array('ขนาด', 'dimension', 'dimensions', 'footprint'),
        'power'           => array('power transformer', 'watt', 'wattage'),
        'smd'             => array('surface mount', 'smt', 'surface mounted'),
        'company'         => array('บริษัท', 'kv electronics', 'about', 'who we are'),
        'quality'         => array('iso', 'iso 9001', 'iso 14001', 'boi', 'certification'),
    );
}


/* ============================================================
   3. TOKENIZER — Split, normalize, remove stop words
   ============================================================ */

function rag_tokenize($text) {
    $text = mb_strtolower($text);
    // Remove special chars but keep Thai characters
    $text = preg_replace('/[^\p{L}\p{N}\s\-\/\.]/u', ' ', $text);
    $words = preg_split('/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);

    // English stop words
    $stop = array_flip(array(
        'the','a','an','is','are','was','were','be','been','being',
        'have','has','had','do','does','did','will','would','shall','should',
        'can','could','may','might','must','need','dare','ought','used',
        'to','of','in','for','on','with','at','by','from','as','into',
        'through','during','before','after','above','below','between',
        'out','off','over','under','again','further','then','once',
        'here','there','when','where','why','how','all','each','every',
        'both','few','more','most','other','some','such','no','nor',
        'not','only','own','same','so','than','too','very','just',
        'i','me','my','we','our','you','your','he','him','his','she',
        'her','it','its','they','them','their','this','that','these',
        'those','what','which','who','whom',
        'and','but','or','if','because','about',
    ));

    // Thai stop words
    $thai_stop = array_flip(array(
        'ที่','ของ','ใน','และ','เป็น','มี','ได้','จะ','ไม่','แล้ว',
        'ก็','จาก','กับ','ให้','ไป','มา','ว่า','อยู่','แต่','ยัง',
        'เรา','คุณ','นี้','นั้น','กัน','บ้าง','ด้วย','ถ้า','หรือ',
        'เช่น','จึง','ทำ','คือ','เพราะ','โดย','แบบ','ครับ','ค่ะ',
    ));

    $filtered = array();
    foreach ($words as $w) {
        $w = trim($w, '-./');
        if (strlen($w) < 2 && !preg_match('/\p{Thai}/u', $w)) continue;
        if (isset($stop[$w]) || isset($thai_stop[$w])) continue;
        $filtered[] = $w;
    }
    return array_unique($filtered);
}


/* ============================================================
   4. INDEX BUILDER — Populate the search index
   ============================================================ */

function rag_build_search_index() {
    global $wpdb;
    $table = $wpdb->prefix . 'rag_search_index';
    $indexed = 0;

    // ── A. Index Products ──
    $products = get_posts(array(
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ));

    foreach ($products as $p) {
        $meta = get_post_meta($p->ID);
        $cats = wp_get_object_terms($p->ID, 'product_category', array('fields' => 'names'));
        $cat_str = is_array($cats) ? implode(', ', $cats) : '';

        // Build searchable content from all meta fields
        $content_parts = array($p->post_title, $p->post_content, $p->post_excerpt);
        $meta_fields = array(
            'pd_subtitle', 'pd_long_description', 'pd_size_range',
            'pd_output_range', 'pd_standards', 'pd_temp_range',
            'pd_package_type', 'pd_voltage', 'pd_current_rating',
            'pd_frequency', 'pd_impedance', 'pd_inductance',
            'pd_features', 'pd_sku',
        );
        $meta_data = array();
        foreach ($meta_fields as $field) {
            $val = isset($meta[$field][0]) ? $meta[$field][0] : '';
            if ($val) {
                $content_parts[] = $val;
                $meta_data[$field] = $val;
            }
        }

        $content = implode(' | ', array_filter($content_parts));
        $tokens = implode(' ', rag_tokenize($content . ' ' . $cat_str));

        $wpdb->replace($table, array(
            'post_id'       => $p->ID,
            'doc_type'      => 'product',
            'title'         => $p->post_title,
            'content'       => $content,
            'content_thai'  => '', // Products likely in English
            'category'      => $cat_str,
            'meta_json'     => wp_json_encode($meta_data),
            'url'           => get_permalink($p->ID),
            'tokens'        => $tokens,
            'word_count'    => str_word_count($content),
            'updated_at'    => current_time('mysql'),
        ));
        $indexed++;
    }

    // ── B. Index Product Categories (as separate documents) ──
    $categories = get_terms(array(
        'taxonomy'   => 'product_category',
        'hide_empty' => false,
    ));
    if (!is_wp_error($categories)) {
        foreach ($categories as $cat) {
            $parent_name = '';
            if ($cat->parent) {
                $parent = get_term($cat->parent, 'product_category');
                if ($parent && !is_wp_error($parent)) {
                    $parent_name = $parent->name;
                }
            }
            $content = sprintf(
                'Category: %s. %s. Parent: %s. Products in this category: %d.',
                $cat->name,
                $cat->description ?: 'Product category for ' . $cat->name,
                $parent_name ?: 'Top-level',
                $cat->count
            );
            $tokens = implode(' ', rag_tokenize($content));

            $wpdb->replace($table, array(
                'post_id'       => 0 - $cat->term_id, // negative to distinguish
                'doc_type'      => 'category',
                'title'         => $cat->name,
                'content'       => $content,
                'content_thai'  => '',
                'category'      => $parent_name,
                'meta_json'     => wp_json_encode(array(
                    'term_id' => $cat->term_id,
                    'count'   => $cat->count,
                    'parent'  => $parent_name,
                    'slug'    => $cat->slug,
                )),
                'url'           => get_term_link($cat),
                'tokens'        => $tokens,
                'word_count'    => str_word_count($content),
                'updated_at'    => current_time('mysql'),
            ));
            $indexed++;
        }
    }

    // ── C. Index Company Information (static knowledge base) ──
    $company_docs = rag_get_company_knowledge();
    foreach ($company_docs as $doc) {
        $tokens = implode(' ', rag_tokenize($doc['title'] . ' ' . $doc['content']));
        $wpdb->replace($table, array(
            'post_id'       => $doc['id'],
            'doc_type'      => 'company',
            'title'         => $doc['title'],
            'content'       => $doc['content'],
            'content_thai'  => isset($doc['thai']) ? $doc['thai'] : '',
            'category'      => 'Company Info',
            'meta_json'     => wp_json_encode(array('section' => $doc['section'])),
            'url'           => $doc['url'],
            'tokens'        => $tokens,
            'word_count'    => str_word_count($doc['content']),
            'updated_at'    => current_time('mysql'),
        ));
        $indexed++;
    }

    // ── D. Index WordPress pages ──
    $pages = get_posts(array(
        'post_type'      => 'page',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
    ));
    foreach ($pages as $pg) {
        $content = strip_tags($pg->post_content);
        if (mb_strlen($content) < 20) continue; // skip near-empty pages
        $tokens = implode(' ', rag_tokenize($pg->post_title . ' ' . $content));

        $wpdb->replace($table, array(
            'post_id'       => $pg->ID,
            'doc_type'      => 'page',
            'title'         => $pg->post_title,
            'content'       => mb_substr($content, 0, 5000),
            'content_thai'  => '',
            'category'      => 'Page',
            'meta_json'     => '{}',
            'url'           => get_permalink($pg->ID),
            'tokens'        => $tokens,
            'word_count'    => str_word_count($content),
            'updated_at'    => current_time('mysql'),
        ));
        $indexed++;
    }

    update_option('rag_last_indexed', current_time('mysql'));
    update_option('rag_index_count', $indexed);

    return $indexed;
}


/* ============================================================
   5. COMPANY KNOWLEDGE BASE — Static documents
   ============================================================ */

function rag_get_company_knowledge() {
    $site_email   = get_option('site_email', 'siriruk@kv-electronics.com');
    $site_phone   = get_option('site_phone', '+6621088521');
    $site_address = get_option('site_address', '988 Moo 2, Soi Thetsaban Bang Poo 60, Sukhumvit Road, Tumbol Thai Ban, Amphur Muang, Samut Prakan 10280, Thailand');
    $site_url     = home_url('/');

    return array(
        array(
            'id'      => -1000,
            'section' => 'contact',
            'title'   => 'Contact Information — KV Electronics',
            'content' => sprintf(
                'Contact KV Electronics Co., Ltd. Email: %s. Phone: %s. Address: %s. We welcome inquiries about our products. You can reach us by phone during business hours or send an email anytime.',
                $site_email, $site_phone, $site_address
            ),
            'thai'    => sprintf('ข้อมูลการติดต่อ บริษัท เควี อิเล็กทรอนิกส์ จำกัด อีเมล: %s โทรศัพท์: %s ที่อยู่: %s', $site_email, $site_phone, $site_address),
            'url'     => $site_url . 'contact/',
        ),
        array(
            'id'      => -1001,
            'section' => 'about',
            'title'   => 'About KV Electronics Co., Ltd.',
            'content' => 'KV Electronics Co., Ltd. is a manufacturer of electronic components including transformers, inductors, coils, and integrated assemblies. We are BOI Promoted and hold IPC-A-610 certification. Our products are RoHS3 compliant and conflict-free. We serve customers worldwide with custom and standard electronic components for various industries including telecommunications, power electronics, automotive, and consumer electronics.',
            'thai'    => 'บริษัท เควี อิเล็กทรอนิกส์ จำกัด เป็นผู้ผลิตชิ้นส่วนอิเล็กทรอนิกส์ รวมถึงหม้อแปลง ตัวเหนี่ยวนำ คอยล์ และชุดประกอบแบบครบวงจร ได้รับการส่งเสริมจาก BOI และได้รับรองมาตรฐาน IPC-A-610',
            'url'     => $site_url . 'about/',
        ),
        array(
            'id'      => -1002,
            'section' => 'products_overview',
            'title'   => 'Product Categories Overview',
            'content' => 'KV Electronics manufactures 4 main product categories: (1) Transformers — including Custom, Digital Audio, Flyback, Gate Drive, Isolation, Magnetic for Communication, Power, and Safety Agency Approved transformers. (2) Inductors — including Common Mode, Custom, High Current Output Filter, Input, Surface Mount, and Toroid & Chokes. (3) Coils — including Air Coils, Copper Foil, Custom Wound, Trapezoidal, and Universal Windings. (4) Integrated Assemblies — including Transformers with PCB Assemblies, Value added builds, and Wire assemblies.',
            'thai'    => 'เควี อิเล็กทรอนิกส์ ผลิตสินค้า 4 หมวดหมู่หลัก: (1) หม้อแปลง (2) ตัวเหนี่ยวนำ (3) คอยล์ (4) ชุดประกอบ',
            'url'     => $site_url,
        ),
        array(
            'id'      => -1003,
            'section' => 'certifications',
            'title'   => 'Quality & Certifications',
            'content' => 'KV Electronics maintains strict quality standards. Our certifications and compliance include: BOI Promoted — investment promotion by the Thai Board of Investment. IPC-A-610 — acceptability of electronic assemblies. RoHS3 — restriction of hazardous substances directive, lead-free manufacturing. Conflict-Free — our materials are sourced responsibly. Temperature ratings: 130°C and 155°C insulation classes. Custom foam packaging for safe delivery.',
            'thai'    => 'มาตรฐานคุณภาพ: BOI Promoted, IPC-A-610, RoHS3, Conflict-Free',
            'url'     => $site_url . 'about/',
        ),
        array(
            'id'      => -1004,
            'section' => 'capabilities',
            'title'   => 'Manufacturing Capabilities',
            'content' => 'KV Electronics offers a broad range of manufacturing capabilities for electronic components. We provide PCB transformers that are vacuum molded for very high voltage applications. Our capabilities include Printed Circuit Boards and PCB Assembly Services. We build products to meet industry standards and customer requirements. We serve industries including power electronics, telecommunications, automotive, medical devices, and industrial automation.',
            'thai'    => 'ความสามารถในการผลิต: หม้อแปลง PCB, ชุดประกอบแผงวงจร, ผลิตตามมาตรฐานอุตสาหกรรม',
            'url'     => $site_url . 'about/',
        ),
    );
}


/* ============================================================
   6. SEARCH ENGINE — The core RAG retrieval
   ============================================================ */

function rag_search($query_string, $lang = 'en') {
    global $wpdb;
    $table = $wpdb->prefix . 'rag_search_index';
    $start_time = microtime(true);

    // Check if table exists
    $exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
    if (!$exists) {
        rag_create_search_index_table();
        rag_build_search_index();
    }

    $total_indexed = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    if ($total_indexed === 0) {
        rag_build_search_index();
        $total_indexed = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    }

    // ── Step 1: Tokenize the query ──
    $tokens = rag_tokenize($query_string);
    $original_tokens = $tokens;

    // ── Step 2: Expand synonyms ──
    $synonym_map = rag_get_synonym_map();
    $expanded = array();
    $synonyms_used = array();

    foreach ($tokens as $token) {
        $expanded[] = $token;
        if (isset($synonym_map[$token])) {
            foreach ($synonym_map[$token] as $syn) {
                if (!in_array($syn, $expanded)) {
                    $expanded[] = $syn;
                    $synonyms_used[] = $token . '→' . $syn;
                }
            }
        }
    }

    // Also check multi-word tokens from the original query
    $query_lower = mb_strtolower($query_string);
    foreach ($synonym_map as $key => $syns) {
        if (mb_strpos($query_lower, $key) !== false && !in_array($key, $tokens)) {
            foreach ($syns as $syn) {
                if (!in_array($syn, $expanded)) {
                    $expanded[] = $syn;
                    $synonyms_used[] = $key . '→' . $syn;
                }
            }
        }
    }

    $expanded = array_unique($expanded);

    // ── Step 3: Build FULLTEXT search query ──
    $ft_terms = array();
    foreach ($expanded as $term) {
        $clean = preg_replace('/[^\p{L}\p{N}\-]/u', '', $term);
        if (mb_strlen($clean) >= 2) {
            $ft_terms[] = $clean;
        }
    }

    $results = array();

    // A) FULLTEXT search in BOOLEAN MODE
    if (!empty($ft_terms)) {
        $ft_query = implode(' ', array_map(function($t) { return $t; }, $ft_terms));
        $ft_query_esc = $wpdb->prepare('%s', $ft_query);

        $sql = "SELECT *, 
                MATCH(title, content, category, tokens) AGAINST({$ft_query_esc} IN BOOLEAN MODE) AS ft_score
                FROM {$table}
                WHERE MATCH(title, content, category, tokens) AGAINST({$ft_query_esc} IN BOOLEAN MODE)
                ORDER BY ft_score DESC
                LIMIT 20";
        
        $rows = $wpdb->get_results($sql);
        if ($rows) {
            foreach ($rows as $row) {
                $results[$row->id] = $row;
            }
        }
    }

    // B) LIKE fallback for short queries or Thai text
    if (count($results) < 3) {
        foreach ($expanded as $term) {
            if (mb_strlen($term) < 2) continue;
            $like_term = '%' . $wpdb->esc_like($term) . '%';
            $sql = $wpdb->prepare(
                "SELECT * FROM {$table} 
                 WHERE title LIKE %s OR content LIKE %s OR category LIKE %s OR tokens LIKE %s OR content_thai LIKE %s
                 LIMIT 10",
                $like_term, $like_term, $like_term, $like_term, $like_term
            );
            $rows = $wpdb->get_results($sql);
            if ($rows) {
                foreach ($rows as $row) {
                    if (!isset($results[$row->id])) {
                        $row->ft_score = 0;
                        $results[$row->id] = $row;
                    }
                }
            }
        }
    }

    // ── Step 4: Score and rank with semantic weighting ──
    $scored = array();
    foreach ($results as $row) {
        $score = rag_compute_semantic_score($row, $original_tokens, $expanded, $query_lower);
        $scored[] = array(
            'row'   => $row,
            'score' => $score,
        );
    }

    // Sort by score descending
    usort($scored, function($a, $b) {
        return $b['score'] - $a['score'];
    });

    // Take top 5
    $scored = array_slice($scored, 0, 5);

    // ── Step 5: Generate answer ──
    $answer = rag_generate_answer($query_string, $tokens, $scored, $lang);

    // Build source references
    $sources = array();
    $th_src_names = rag_get_thai_name_map();
    foreach ($scored as $item) {
        $r = $item['row'];
        $meta = json_decode($r->meta_json, true);
        if ($lang === 'th') {
            $title_th = isset($th_src_names[$r->title]) ? $th_src_names[$r->title] : $r->title;
            $cat_th   = isset($th_src_names[$r->category]) ? $th_src_names[$r->category] : $r->category;
            $excerpt  = !empty($r->content_thai)
                ? mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags($r->content_thai))), 0, 120) . '…'
                : mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags($r->content))), 0, 120) . '…';
            $sources[] = array(
                'title'    => $title_th,
                'excerpt'  => $excerpt,
                'category' => $cat_th,
                'url'      => $r->url,
                'score'    => round($item['score']),
                'type'     => $r->doc_type,
            );
        } else {
            $excerpt = mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags($r->content))), 0, 120) . '…';
            $sources[] = array(
                'title'    => $r->title,
                'excerpt'  => $excerpt,
                'category' => $r->category,
                'url'      => $r->url,
                'score'    => round($item['score']),
                'type'     => $r->doc_type,
            );
        }
    }

    $search_time = round((microtime(true) - $start_time) * 1000);

    // Generate follow-up suggestions
    $suggestions = rag_generate_suggestions($tokens, $scored, $lang);

    return array(
        'answer'        => $answer,
        'sources'       => $sources,
        'total_indexed' => $total_indexed,
        'search_time'   => $search_time,
        'search_mode'   => !empty($ft_terms) ? 'Semantic + FULLTEXT' : 'LIKE Fallback',
        'synonyms_used' => array_slice($synonyms_used, 0, 5),
        'tokens'        => $original_tokens,
        'expanded'      => $expanded,
        'suggestions'   => $suggestions,
    );
}


/* ============================================================
   7. SEMANTIC SCORING — TF-IDF-like weighted scoring
   ============================================================ */

function rag_compute_semantic_score($row, $original_tokens, $expanded_tokens, $query_lower) {
    $score = 0;
    $title_lower   = mb_strtolower($row->title);
    $content_lower = mb_strtolower($row->content);
    $cat_lower     = mb_strtolower($row->category);
    $tokens_lower  = mb_strtolower($row->tokens);
    $thai_lower    = mb_strtolower($row->content_thai);

    // ── Weight config ──
    $w_title   = 30;   // Title match = highest weight
    $w_cat     = 20;   // Category match
    $w_content = 10;   // Content match
    $w_thai    = 15;   // Thai content match
    $w_token   = 5;    // Token index match

    // ── A) Original token exact matches (highest value) ──
    foreach ($original_tokens as $token) {
        if (mb_strpos($title_lower, $token) !== false)   $score += $w_title;
        if (mb_strpos($cat_lower, $token) !== false)     $score += $w_cat;
        if (mb_strpos($content_lower, $token) !== false) $score += $w_content;
        if (mb_strpos($thai_lower, $token) !== false)    $score += $w_thai;
    }

    // ── B) Expanded synonym matches (lower weight) ──
    $synonym_hits = array_diff($expanded_tokens, $original_tokens);
    foreach ($synonym_hits as $syn) {
        if (mb_strpos($title_lower, $syn) !== false)   $score += $w_title * 0.6;
        if (mb_strpos($cat_lower, $syn) !== false)     $score += $w_cat * 0.6;
        if (mb_strpos($content_lower, $syn) !== false) $score += $w_content * 0.6;
    }

    // ── C) FULLTEXT relevance bonus ──
    if (isset($row->ft_score) && $row->ft_score > 0) {
        $score += min(30, $row->ft_score * 10);
    }

    // ── D) Doc type bonus ──
    if ($row->doc_type === 'product')  $score *= 1.2;  // Products are primary
    if ($row->doc_type === 'company')  $score *= 1.1;  // Company info slightly boosted
    if ($row->doc_type === 'category') $score *= 0.9;  // Categories lower

    // ── E) Exact phrase match bonus ──
    if (mb_strpos($content_lower, $query_lower) !== false) {
        $score += 25;
    }
    if (mb_strpos($title_lower, $query_lower) !== false) {
        $score += 40;
    }

    // Normalize to 0-100
    $score = min(100, max(0, round($score)));

    return $score;
}


/* ============================================================
   7b. THAI NAME MAP — Shared translation map
   ============================================================ */

function rag_get_thai_name_map() {
    return array(
        // Parent categories
        'Transformers'              => 'หม้อแปลง',
        'Inductors'                 => 'ตัวเหนี่ยวนำ',
        'Coils'                     => 'คอยล์',
        'Integrated Assemblies'     => 'ชุดประกอบครบวงจร',
        // Transformer sub
        'Custom Transformers'       => 'หม้อแปลงสั่งทำพิเศษ',
        'Digital Audio'             => 'หม้อแปลงเสียงดิจิทัล',
        'Flyback'                   => 'หม้อแปลงฟลายแบ็ค',
        'Gate Drive'                => 'หม้อแปลงเกตไดรฟ์',
        'Isolation'                 => 'หม้อแปลงแยกกราวด์',
        'Magnetic Transformers for Communication' => 'หม้อแปลงแม่เหล็กสำหรับสื่อสาร',
        'Power'                     => 'หม้อแปลงกำลัง',
        'Safety Agency Approved'    => 'หม้อแปลงผ่านรับรองความปลอดภัย',
        // Inductor sub
        'Common Mode Inductors'     => 'ตัวเหนี่ยวนำคอมมอนโหมด',
        'Custom Inductors'          => 'ตัวเหนี่ยวนำสั่งทำพิเศษ',
        'High Current Output Filter Inductors' => 'ตัวเหนี่ยวนำกรองกระแสสูง',
        'Input Inductors'           => 'ตัวเหนี่ยวนำอินพุต',
        'Surface Mount'             => 'ตัวเหนี่ยวนำติดผิว (SMD)',
        'Toroid Inductors & Chokes' => 'ตัวเหนี่ยวนำทอรอยด์และโช้ค',
        // Coil sub
        'Air Coils'                 => 'คอยล์อากาศ',
        'Copper Foil'               => 'คอยล์ฟอยล์ทองแดง',
        'Custom Wound Coils'        => 'คอยล์พันสั่งทำพิเศษ',
        'Trapezoidal'               => 'คอยล์สี่เหลี่ยมคางหมู',
        'Universal Windings'        => 'คอยล์พันแบบสากล',
        // Assembly sub
        'Transformers with PCB Assemblies' => 'หม้อแปลงพร้อมชุดประกอบ PCB',
        'Value added builds using magnetics/wound coils' => 'ชุดประกอบเพิ่มมูลค่า',
        'Wire assemblies'           => 'ชุดสายไฟ',
        // Products
        'PCB Transformers'          => 'หม้อแปลง PCB',
        // Company docs
        'Contact Information — KV Electronics' => 'ข้อมูลติดต่อ — KV Electronics',
        'About KV Electronics Co., Ltd.'       => 'เกี่ยวกับ KV Electronics Co., Ltd.',
        'Product Categories Overview'          => 'ภาพรวมหมวดหมู่สินค้า',
        'Quality & Certifications'             => 'คุณภาพและใบรับรอง',
        'Manufacturing Capabilities'           => 'ความสามารถในการผลิต',
        // Category labels
        'Company Info'              => 'ข้อมูลบริษัท',
        'Page'                      => 'หน้าเว็บ',
        // Pages
        'About Us'                  => 'เกี่ยวกับเรา',
        'Contacts'                  => 'ติดต่อเรา',
        'Home'                      => 'หน้าแรก',
        'Products'                  => 'สินค้า',
    );
}


/* ============================================================
   8. ANSWER GENERATOR — Build natural language response
   ============================================================ */

function rag_generate_answer($query, $tokens, $scored_results, $lang = 'en') {
    $is_thai = ($lang === 'th');

    // ── Thai translation map for product/category names ──
    $th_names = array(
        // Parent categories
        'Transformers'              => 'หม้อแปลง',
        'Inductors'                 => 'ตัวเหนี่ยวนำ',
        'Coils'                     => 'คอยล์',
        'Integrated Assemblies'     => 'ชุดประกอบครบวงจร',
        // Transformer subcategories
        'Custom Transformers'       => 'หม้อแปลงสั่งทำพิเศษ',
        'Digital Audio'             => 'หม้อแปลงเสียงดิจิทัล',
        'Flyback'                   => 'หม้อแปลงฟลายแบ็ค',
        'Gate Drive'                => 'หม้อแปลงเกตไดรฟ์',
        'Isolation'                 => 'หม้อแปลงแยกกราวด์',
        'Magnetic Transformers for Communication' => 'หม้อแปลงแม่เหล็กสำหรับการสื่อสาร',
        'Power'                     => 'หม้อแปลงกำลัง',
        'Safety Agency Approved'    => 'หม้อแปลงที่ผ่านการรับรองความปลอดภัย',
        // Inductor subcategories
        'Common Mode Inductors'     => 'ตัวเหนี่ยวนำคอมมอนโหมด',
        'Custom Inductors'          => 'ตัวเหนี่ยวนำสั่งทำพิเศษ',
        'High Current Output Filter Inductors' => 'ตัวเหนี่ยวนำกรองกระแสสูง',
        'Input Inductors'           => 'ตัวเหนี่ยวนำอินพุต',
        'Surface Mount'             => 'ตัวเหนี่ยวนำแบบติดผิว (SMD)',
        'Toroid Inductors & Chokes' => 'ตัวเหนี่ยวนำทอรอยด์และโช้ค',
        // Coil subcategories
        'Air Coils'                 => 'คอยล์อากาศ',
        'Copper Foil'               => 'คอยล์ฟอยล์ทองแดง',
        'Custom Wound Coils'        => 'คอยล์พันสั่งทำพิเศษ',
        'Trapezoidal'               => 'คอยล์สี่เหลี่ยมคางหมู',
        'Universal Windings'        => 'คอยล์พันแบบสากล',
        // Assembly subcategories
        'Transformers with PCB Assemblies' => 'หม้อแปลงพร้อมชุดประกอบ PCB',
        'Value added builds using magnetics/wound coils' => 'ชุดประกอบเพิ่มมูลค่า (แม่เหล็ก/ขดลวด)',
        'Wire assemblies'           => 'ชุดสายไฟ',
        // Products
        'PCB Transformers'          => 'หม้อแปลง PCB',
        // Misc
        'Company Info'              => 'ข้อมูลบริษัท',
        'Page'                      => 'หน้าเว็บ',
        // Pages
        'About Us'                  => 'เกี่ยวกับเรา',
        'Contacts'                  => 'ติดต่อเรา',
        'Home'                      => 'หน้าแรก',
        'Products'                  => 'สินค้า',
        // Company docs
        'Contact Information — KV Electronics' => 'ข้อมูลติดต่อ — KV Electronics',
        'About KV Electronics Co., Ltd.'       => 'เกี่ยวกับ KV Electronics Co., Ltd.',
        'Product Categories Overview'          => 'ภาพรวมหมวดหมู่สินค้า',
        'Quality & Certifications'             => 'คุณภาพและใบรับรอง',
        'Manufacturing Capabilities'           => 'ความสามารถในการผลิต',
    );

    // ── Thai translation map for doc_type ──
    $th_doc_type = array(
        'product'  => 'สินค้า',
        'category' => 'หมวดหมู่',
        'company'  => 'ข้อมูลบริษัท',
        'page'     => 'หน้าเว็บ',
    );

    // Helper: translate a name to Thai
    $to_thai = function($name) use ($th_names) {
        return isset($th_names[$name]) ? $th_names[$name] : $name;
    };

    if (empty($scored_results)) {
        if ($is_thai) {
            return '<p>ไม่พบข้อมูลที่ตรงกับคำค้นหาของคุณ สิ่งที่ฉันช่วยได้:</p>'
                 . '<ul>'
                 . '<li>🔌 <strong>สินค้า</strong> — หม้อแปลง, ตัวเหนี่ยวนำ, คอยล์, ชุดประกอบ</li>'
                 . '<li>📋 <strong>สเปค</strong> — ขนาด, แรงดัน, มาตรฐาน, อุณหภูมิ</li>'
                 . '<li>📞 <strong>ติดต่อ</strong> — อีเมล, โทรศัพท์, ที่อยู่</li>'
                 . '<li>🏭 <strong>บริษัท</strong> — เกี่ยวกับเรา, ใบรับรอง, ความสามารถ</li>'
                 . '</ul>';
        }
        return '<p>I couldn\'t find specific information matching your query. Here are some things I can help with:</p>'
             . '<ul>'
             . '<li>🔌 <strong>Products</strong> — Transformers, Inductors, Coils, Assemblies</li>'
             . '<li>📋 <strong>Specifications</strong> — Size, voltage, standards, temperature</li>'
             . '<li>📞 <strong>Contact</strong> — Email, phone, address</li>'
             . '<li>🏭 <strong>Company</strong> — About us, certifications, capabilities</li>'
             . '</ul>';
    }

    $best = $scored_results[0]['row'];
    $meta = json_decode($best->meta_json, true);
    $query_lower = mb_strtolower($query);

    // ── Build contextual answer based on document type and query intent ──
    $answer = '';

    // Detect intent
    $is_contact  = preg_match('/contact|email|phone|address|ติดต่อ|โทร|อีเมล|ที่อยู่/iu', $query_lower);
    $is_product  = preg_match('/product|สินค้า|ผลิตภัณฑ์|transform|inductor|coil|assembly|หม้อแปลง|ตัวเหนี่ยวนำ|คอยล์/iu', $query_lower);
    $is_spec     = preg_match('/spec|size|voltage|current|temp|standard|rating|คุณสมบัติ|ขนาด|แรงดัน|มาตรฐาน/iu', $query_lower);
    $is_quality  = preg_match('/quality|rohs|ipc|iso|cert|boi|มาตรฐาน/iu', $query_lower);
    $is_about    = preg_match('/about|company|who|บริษัท|เกี่ยวกับ/iu', $query_lower);
    $is_category = preg_match('/categor|type|kind|ประเภท|หมวด|all|ทั้งหมด|what.*offer|what.*have|what.*make/iu', $query_lower);

    if ($is_contact && $best->doc_type === 'company') {
        $answer .= $is_thai ? '<p>📞 <strong>ข้อมูลติดต่อ:</strong></p>' : '<p>📞 <strong>Contact Information:</strong></p>';
        $site_email = get_option('site_email', 'siriruk@kv-electronics.com');
        $site_phone = get_option('site_phone', '+6621088521');
        $site_addr  = get_option('site_address', '');
        $answer .= '<ul>';
        $answer .= '<li><strong>' . ($is_thai ? 'อีเมล' : 'Email') . ':</strong> <a href="mailto:' . esc_attr($site_email) . '">' . esc_html($site_email) . '</a></li>';
        $answer .= '<li><strong>' . ($is_thai ? 'โทรศัพท์' : 'Phone') . ':</strong> <a href="tel:' . esc_attr($site_phone) . '">' . esc_html($site_phone) . '</a></li>';
        if ($site_addr) {
            $answer .= '<li><strong>' . ($is_thai ? 'ที่อยู่' : 'Address') . ':</strong> ' . esc_html($site_addr) . '</li>';
        }
        $answer .= '</ul>';
    } elseif ($is_category || ($is_product && !$is_spec)) {
        // Category/product overview
        $answer .= $is_thai ? '<p>🏭 <strong>สายผลิตภัณฑ์ KV Electronics:</strong></p>' : '<p>🏭 <strong>KV Electronics Product Lines:</strong></p>';

        // If we have the overview doc, use Thai version
        if ($best->doc_type === 'company' && strpos($best->content, '4 main product categories') !== false) {
            if ($is_thai) {
                $answer .= '<p>KV Electronics ผลิตสินค้า 4 หมวดหมู่หลัก:</p>';
                $answer .= '<ul>';
                $answer .= '<li><strong>หม้อแปลง (Transformers)</strong> — Custom, Digital Audio, Flyback, Gate Drive, Isolation, Magnetic for Communication, Power, Safety Agency Approved</li>';
                $answer .= '<li><strong>ตัวเหนี่ยวนำ (Inductors)</strong> — Common Mode, Custom, High Current Output Filter, Input, Surface Mount (SMD), Toroid & Chokes</li>';
                $answer .= '<li><strong>คอยล์ (Coils)</strong> — Air Coils, Copper Foil, Custom Wound, Trapezoidal, Universal Windings</li>';
                $answer .= '<li><strong>ชุดประกอบครบวงจร (Integrated Assemblies)</strong> — Transformers with PCB Assemblies, Value Added Builds, Wire Assemblies</li>';
                $answer .= '</ul>';
            } else {
                $answer .= '<p>' . esc_html(mb_substr(strip_tags($best->content), 0, 400)) . '</p>';
            }
        } else {
            $answer .= '<ul>';
            foreach ($scored_results as $item) {
                $r = $item['row'];
                if ($is_thai) {
                    $title_th = $r->title;
                    $cat_th   = $r->category ? $r->category : '';
                    // Use content_thai if available, otherwise describe in Thai
                    if (!empty($r->content_thai)) {
                        $excerpt = mb_substr(strip_tags($r->content_thai), 0, 80);
                    } else {
                        // Build a Thai description based on doc_type
                        if ($r->doc_type === 'category') {
                            $cat_meta = json_decode($r->meta_json, true);
                            $count = isset($cat_meta['count']) ? $cat_meta['count'] : 0;
                            $parent = isset($cat_meta['parent']) ? $cat_meta['parent'] : '';
                            $excerpt = 'หมวดหมู่สินค้า' . ($parent ? ' ภายใต้ ' . $parent : '') . ($count ? ' (' . $count . ' รายการ)' : '');
                        } elseif ($r->doc_type === 'product') {
                            $excerpt = 'สินค้า — ' . trim(preg_replace('/\s+/', ' ', strip_tags($r->content)));
                            $excerpt = mb_substr($excerpt, 0, 80);
                        } elseif ($r->doc_type === 'page') {
                            $excerpt = 'หน้าเว็บ — ' . trim(preg_replace('/\s+/', ' ', strip_tags($r->content)));
                            $excerpt = mb_substr($excerpt, 0, 80);
                        } else {
                            $excerpt = trim(preg_replace('/\s+/', ' ', strip_tags($r->content)));
                            $excerpt = mb_substr($excerpt, 0, 80);
                        }
                    }
                    $answer .= '<li><strong>' . esc_html($title_th) . '</strong>';
                    if ($cat_th) $answer .= ' <em>(' . esc_html($cat_th) . ')</em>';
                    $answer .= ' — ' . esc_html($excerpt) . '</li>';
                } else {
                    $excerpt = mb_substr(trim(preg_replace('/\s+/', ' ', strip_tags($r->content))), 0, 80);
                    $answer .= '<li><strong>' . esc_html($r->title) . '</strong>';
                    if ($r->category) $answer .= ' <em>(' . esc_html($r->category) . ')</em>';
                    $answer .= ' — ' . esc_html($excerpt) . '…</li>';
                }
            }
            $answer .= '</ul>';
        }
    } elseif ($is_spec && $best->doc_type === 'product') {
        // Product specifications
        $title_display = $best->title;
        $answer .= $is_thai
            ? '<p>📋 <strong>สเปคของ ' . esc_html($title_display) . ':</strong></p>'
            : '<p>📋 <strong>Specifications for ' . esc_html($title_display) . ':</strong></p>';
        $answer .= '<ul>';
        if (!empty($meta['pd_standards']))    $answer .= '<li><strong>' . ($is_thai ? 'มาตรฐาน' : 'Standards') . ':</strong> ' . esc_html($meta['pd_standards']) . '</li>';
        if (!empty($meta['pd_temp_range']))   $answer .= '<li><strong>' . ($is_thai ? 'อุณหภูมิ' : 'Temperature') . ':</strong> ' . esc_html($meta['pd_temp_range']) . '</li>';
        if (!empty($meta['pd_size_range']))   $answer .= '<li><strong>' . ($is_thai ? 'ช่วงขนาด' : 'Size Range') . ':</strong> ' . esc_html($meta['pd_size_range']) . '</li>';
        if (!empty($meta['pd_output_range'])) $answer .= '<li><strong>' . ($is_thai ? 'ช่วงเอาต์พุต' : 'Output Range') . ':</strong> ' . esc_html($meta['pd_output_range']) . '</li>';
        if (!empty($meta['pd_voltage']))      $answer .= '<li><strong>' . ($is_thai ? 'แรงดัน' : 'Voltage') . ':</strong> ' . esc_html($meta['pd_voltage']) . '</li>';
        if (!empty($meta['pd_frequency']))    $answer .= '<li><strong>' . ($is_thai ? 'ความถี่' : 'Frequency') . ':</strong> ' . esc_html($meta['pd_frequency']) . '</li>';
        if (!empty($meta['pd_package_type'])) $answer .= '<li><strong>' . ($is_thai ? 'บรรจุภัณฑ์' : 'Packaging') . ':</strong> ' . esc_html($meta['pd_package_type']) . '</li>';
        $answer .= '</ul>';
        if ($is_thai && !empty($meta['pd_long_description'])) {
            $answer .= '<p><strong>รายละเอียด:</strong> ' . esc_html(mb_substr($meta['pd_long_description'], 0, 200)) . '…</p>';
        }
    } elseif ($is_quality) {
        $answer .= $is_thai ? '<p>✅ <strong>คุณภาพและใบรับรอง:</strong></p>' : '<p>✅ <strong>Quality & Certifications:</strong></p>';
        if ($is_thai) {
            // Use Thai content or build Thai summary
            if (!empty($best->content_thai)) {
                $answer .= '<p>' . esc_html(mb_substr(strip_tags($best->content_thai), 0, 400)) . '</p>';
            } else {
                $answer .= '<p>KV Electronics รักษามาตรฐานคุณภาพอย่างเข้มงวด ใบรับรองของเราประกอบด้วย:</p>';
                $answer .= '<ul>';
                $answer .= '<li><strong>BOI Promoted</strong> — ได้รับการส่งเสริมการลงทุนจากคณะกรรมการส่งเสริมการลงทุน</li>';
                $answer .= '<li><strong>IPC-A-610</strong> — มาตรฐานการยอมรับชุดประกอบอิเล็กทรอนิกส์</li>';
                $answer .= '<li><strong>RoHS3</strong> — ข้อกำหนดจำกัดสารอันตราย, การผลิตแบบไร้สารตะกั่ว</li>';
                $answer .= '<li><strong>Conflict-Free</strong> — วัตถุดิบจากแหล่งที่มีความรับผิดชอบ</li>';
                $answer .= '</ul>';
            }
        } else {
            $answer .= '<p>' . esc_html(mb_substr(strip_tags($best->content), 0, 400)) . '</p>';
        }
    } elseif ($is_about) {
        $answer .= $is_thai ? '<p>🏢 <strong>เกี่ยวกับ KV Electronics:</strong></p>' : '<p>🏢 <strong>About KV Electronics:</strong></p>';
        if ($is_thai) {
            if (!empty($best->content_thai)) {
                $answer .= '<p>' . esc_html(mb_substr(strip_tags($best->content_thai), 0, 400)) . '</p>';
            } else {
                $answer .= '<p>บริษัท เควี อิเล็กทรอนิกส์ จำกัด เป็นผู้ผลิตชิ้นส่วนอิเล็กทรอนิกส์ ';
                $answer .= 'รวมถึงหม้อแปลง ตัวเหนี่ยวนำ คอยล์ และชุดประกอบครบวงจร ';
                $answer .= 'ได้รับการส่งเสริมจาก BOI และได้รับรองมาตรฐาน IPC-A-610 ';
                $answer .= 'สินค้าของเราเป็นไปตามมาตรฐาน RoHS3 และ Conflict-Free ';
                $answer .= 'เราให้บริการลูกค้าทั่วโลกด้วยชิ้นส่วนอิเล็กทรอนิกส์แบบสั่งทำพิเศษและมาตรฐาน</p>';
            }
        } else {
            $answer .= '<p>' . esc_html(mb_substr(strip_tags($best->content), 0, 400)) . '</p>';
        }
    } else {
        // General — show top result info
        if ($is_thai) {
            $icon = $best->doc_type === 'product' ? '📦' : ($best->doc_type === 'company' ? '🏢' : '📁');
            $type_label = isset($th_doc_type[$best->doc_type]) ? $th_doc_type[$best->doc_type] : $best->doc_type;
            $title_th = $best->title;
            $answer .= '<p>' . $icon . ' <strong>' . esc_html($title_th) . '</strong> <em style="font-size:12px;color:#94a3b8;">(' . esc_html($type_label) . ')</em></p>';
            if (!empty($best->content_thai)) {
                $answer .= '<p>' . esc_html(mb_substr(strip_tags($best->content_thai), 0, 300)) . '</p>';
            } else {
                // Provide Thai wrapper around English content
                $answer .= '<p>' . esc_html(mb_substr(strip_tags($best->content), 0, 300)) . '</p>';
            }
        } else {
            $icon = $best->doc_type === 'product' ? '📦' : ($best->doc_type === 'company' ? '🏢' : '📁');
            $answer .= '<p>' . $icon . ' <strong>' . esc_html($best->title) . '</strong></p>';
            $answer .= '<p>' . esc_html(mb_substr(strip_tags($best->content), 0, 300)) . '</p>';
        }
    }

    return $answer;
}


/* ============================================================
   9. FOLLOW-UP SUGGESTIONS
   ============================================================ */

function rag_generate_suggestions($tokens, $scored_results, $lang = 'en') {
    $is_thai = ($lang === 'th');
    $suggestions = array();

    // Based on what we found, suggest related queries
    $found_types = array();
    foreach ($scored_results as $item) {
        $found_types[] = $item['row']->doc_type;
    }
    $found_types = array_unique($found_types);

    if (!in_array('product', $found_types)) {
        $suggestions[] = $is_thai ? 'แสดงสินค้าทั้งหมด' : 'Show me all products';
    }
    if (!in_array('company', $found_types)) {
        $suggestions[] = $is_thai ? 'ข้อมูลติดต่อ' : 'Contact information';
    }

    // Context-aware suggestions
    $all_tokens = implode(' ', $tokens);
    if (strpos($all_tokens, 'transformer') !== false || strpos($all_tokens, 'หม้อแปลง') !== false) {
        $suggestions[] = $is_thai ? 'สินค้าตัวเหนี่ยวนำ' : 'Inductor products';
        $suggestions[] = $is_thai ? 'สเปคหม้อแปลง' : 'Transformer specifications';
    } elseif (strpos($all_tokens, 'inductor') !== false || strpos($all_tokens, 'ตัวเหนี่ยวนำ') !== false) {
        $suggestions[] = $is_thai ? 'สินค้าหม้อแปลง' : 'Transformer products';
        $suggestions[] = $is_thai ? 'สเปคตัวเหนี่ยวนำ' : 'Inductor specifications';
    } elseif (strpos($all_tokens, 'contact') !== false || strpos($all_tokens, 'ติดต่อ') !== false) {
        $suggestions[] = $is_thai ? 'ใบรับรองคุณภาพ' : 'Company certifications';
        $suggestions[] = $is_thai ? 'หมวดหมู่สินค้า' : 'Product categories';
    } else {
        $suggestions[] = $is_thai ? 'ใบรับรองคุณภาพ' : 'Quality certifications';
        $suggestions[] = $is_thai ? 'ความสามารถในการผลิต' : 'Manufacturing capabilities';
    }

    return array_slice($suggestions, 0, 4);
}


/* ============================================================
   10. AJAX HANDLER
   ============================================================ */

add_action('wp_ajax_rag_search', 'rag_handle_search');
add_action('wp_ajax_nopriv_rag_search', 'rag_handle_search');

function rag_handle_search() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'rag_search_nonce')) {
        wp_send_json_error('Invalid security token.');
    }

    $query = isset($_POST['query']) ? sanitize_text_field(wp_unslash($_POST['query'])) : '';
    $lang  = isset($_POST['lang']) ? sanitize_text_field($_POST['lang']) : 'en';
    if (!in_array($lang, array('en', 'th'))) $lang = 'en';

    if (empty($query) || mb_strlen($query) < 2) {
        wp_send_json_error($lang === 'th' ? 'กรุณาพิมพ์คำค้นหาที่ยาวกว่านี้' : 'Please enter a longer search query.');
    }

    $result = rag_search($query, $lang);
    rag_log_chat_query($query, $lang, $result);
    wp_send_json_success($result);
}


/* ============================================================
   11. ADMIN — Rebuild Index (via tools page)
   ============================================================ */

add_action('wp_ajax_rag_rebuild_index', 'rag_handle_rebuild_index');

function rag_handle_rebuild_index() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied.');
    }
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'rag_rebuild_nonce')) {
        wp_send_json_error('Invalid security token.');
    }

    $count = rag_build_search_index();
    wp_send_json_success(array(
        'indexed' => $count,
        'time'    => current_time('mysql'),
    ));
}


/* ============================================================
   12. ENQUEUE SCRIPTS & STYLES
   ============================================================ */

add_action('wp_enqueue_scripts', 'rag_enqueue_chat_assets');

function rag_enqueue_chat_assets() {
    // Check if RAG chat is enabled in settings
    if (get_option('rag_chat_enabled', '1') !== '1') {
        return;
    }

    $theme_dir = get_template_directory();
    $theme_uri = get_template_directory_uri();

    wp_enqueue_style(
        'rag-chat-css',
        $theme_uri . '/assets/css/rag-chat.css',
        array(),
        filemtime($theme_dir . '/assets/css/rag-chat.css')
    );

    wp_enqueue_script(
        'rag-chat-js',
        $theme_uri . '/assets/js/rag-chat.js',
        array(),
        filemtime($theme_dir . '/assets/js/rag-chat.js'),
        true
    );

    wp_localize_script('rag-chat-js', 'ragChatConfig', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('rag_search_nonce'),
        'siteUrl' => home_url('/'),
    ));
}


/* ============================================================
   13. AUTO-INDEX — Rebuild when products are saved/deleted
   ============================================================ */

add_action('save_post_product', 'rag_auto_reindex', 20);
add_action('delete_post', 'rag_auto_reindex', 20);
add_action('edited_product_category', 'rag_auto_reindex_terms', 20);
add_action('created_product_category', 'rag_auto_reindex_terms', 20);

function rag_auto_reindex($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    // Debounce with transient
    if (get_transient('rag_reindex_pending')) return;
    set_transient('rag_reindex_pending', 1, 30);
    rag_build_search_index();
    delete_transient('rag_reindex_pending');
}

function rag_auto_reindex_terms($term_id) {
    if (get_transient('rag_reindex_pending')) return;
    set_transient('rag_reindex_pending', 1, 30);
    rag_build_search_index();
    delete_transient('rag_reindex_pending');
}


/* ============================================================
   14. ADMIN PAGE — RAG Settings in WP Admin sidebar
   ============================================================ */

add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'RAG Search Engine',
        'RAG Search',
        'manage_options',
        'rag-search-settings',
        'rag_admin_settings_page'
    );
});

function rag_admin_settings_page() {
    $last_indexed = get_option('rag_last_indexed', 'Never');
    $index_count  = get_option('rag_index_count', 0);
    ?>
    <div class="wrap">
        <h1>🔍 RAG Search Engine — Settings</h1>
        <div style="max-width:700px;">
            <div class="card" style="padding:20px;margin-top:20px;">
                <h2>Search Index Status</h2>
                <table class="form-table">
                    <tr>
                        <th>Last Indexed</th>
                        <td><strong><?php echo esc_html($last_indexed); ?></strong></td>
                    </tr>
                    <tr>
                        <th>Documents Indexed</th>
                        <td><strong><?php echo (int)$index_count; ?></strong></td>
                    </tr>
                    <tr>
                        <th>Synonym Entries</th>
                        <td><strong><?php echo count(rag_get_synonym_map()); ?></strong></td>
                    </tr>
                </table>
                <p>
                    <button type="button" class="button button-primary" id="rag-rebuild-btn" style="font-size:14px;padding:6px 20px;">
                        🔄 Rebuild Search Index
                    </button>
                    <span id="rag-rebuild-status" style="margin-left:12px;"></span>
                </p>
            </div>

            <div class="card" style="padding:20px;margin-top:20px;">
                <h2>How it Works</h2>
                <ol>
                    <li><strong>Indexing</strong> — Extracts product data, categories, and company info into a searchable FULLTEXT index.</li>
                    <li><strong>Tokenization</strong> — Splits queries into meaningful tokens with stop word removal (EN + TH).</li>
                    <li><strong>Synonym Expansion</strong> — Maps Thai ↔ English terms and product aliases for semantic matching.</li>
                    <li><strong>Weighted Scoring</strong> — TF-IDF-like scoring with field weights (Title=30, Category=20, Content=10).</li>
                    <li><strong>Answer Generation</strong> — Builds contextual answers from matched documents.</li>
                </ol>
            </div>

            <div class="card" style="padding:20px;margin-top:20px;">
                <h2>Test Search</h2>
                <p>
                    <input type="text" id="rag-test-query" placeholder="Enter test query..." style="width:70%;padding:6px 10px;">
                    <button type="button" class="button" id="rag-test-btn">Test</button>
                </p>
                <pre id="rag-test-result" style="background:#f1f5f9;padding:15px;border-radius:6px;white-space:pre-wrap;max-height:400px;overflow:auto;display:none;"></pre>
            </div>
        </div>
    </div>

    <script>
    (function(){
        // Rebuild index
        document.getElementById('rag-rebuild-btn').addEventListener('click', function(){
            var btn = this;
            var status = document.getElementById('rag-rebuild-status');
            btn.disabled = true;
            status.textContent = 'Rebuilding...';
            
            var fd = new FormData();
            fd.append('action', 'rag_rebuild_index');
            fd.append('nonce', '<?php echo wp_create_nonce("rag_rebuild_nonce"); ?>');
            
            fetch(ajaxurl, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                btn.disabled = false;
                if(data.success) {
                    status.innerHTML = '<span style="color:green;">✅ Indexed <strong>' + data.data.indexed + '</strong> documents at ' + data.data.time + '</span>';
                    setTimeout(() => location.reload(), 1500);
                } else {
                    status.innerHTML = '<span style="color:red;">❌ ' + (data.data || 'Error') + '</span>';
                }
            })
            .catch(e => {
                btn.disabled = false;
                status.innerHTML = '<span style="color:red;">❌ Network error</span>';
            });
        });

        // Test search
        document.getElementById('rag-test-btn').addEventListener('click', function(){
            var query = document.getElementById('rag-test-query').value.trim();
            if(!query) return;
            var pre = document.getElementById('rag-test-result');
            pre.style.display = 'block';
            pre.textContent = 'Searching...';
            
            var fd = new FormData();
            fd.append('action', 'rag_search');
            fd.append('nonce', '<?php echo wp_create_nonce("rag_search_nonce"); ?>');
            fd.append('query', query);
            
            fetch(ajaxurl, { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                pre.textContent = JSON.stringify(data, null, 2);
            })
            .catch(e => {
                pre.textContent = 'Error: ' + e.message;
            });
        });

        document.getElementById('rag-test-query').addEventListener('keydown', function(e){
            if(e.key === 'Enter') document.getElementById('rag-test-btn').click();
        });
    })();
    </script>
    <?php
}
