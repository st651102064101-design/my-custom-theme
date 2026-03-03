<?php
/**
 * Block Editor Guide — คู่มือการใช้งาน Block Editor สำหรับลูกค้า
 *
 * แสดงวิธีใช้ WordPress Block Editor (Gutenberg) สำหรับ:
 * 1. Theme Builder (FSE) — ออกแบบโครงสร้างเว็บ
 * 2. Dynamic Content — เนื้อหาแบบไดนามิก
 * 3. Conversion Tools — เครื่องมือสร้างยอดขาย
 * 4. Integration — เชื่อมต่อระบบภายนอก
 * 5. Performance & SEO — ปรับแต่งประสิทธิภาพ
 */
if ( ! defined( 'ABSPATH' ) ) exit;

function kv_block_editor_guide_content() {
    $admin_url   = admin_url();
    $site_editor = admin_url('site-editor.php');
    $theme_json  = get_template_directory() . '/theme.json';
    $tj_data     = file_exists($theme_json) ? json_decode(file_get_contents($theme_json), true) : [];
    $primary     = get_option('theme_primary_color', '#2563eb');

    // Gather theme info
    $templates       = wp_get_theme()->get_page_templates();
    $custom_templates = $tj_data['customTemplates'] ?? [];
    $template_parts   = $tj_data['templateParts'] ?? [];
    $color_palette    = $tj_data['settings']['color']['palette'] ?? [];
    $font_families    = $tj_data['settings']['typography']['fontFamilies'] ?? [];
    $font_sizes       = $tj_data['settings']['typography']['fontSizes'] ?? [];
    $content_width    = $tj_data['settings']['layout']['contentSize'] ?? '1140px';
    $wide_width       = $tj_data['settings']['layout']['wideSize'] ?? '1320px';

    // Count registered blocks, patterns
    $block_count   = class_exists('WP_Block_Type_Registry') ? count(WP_Block_Type_Registry::get_instance()->get_all_registered()) : 0;
    $pattern_count = class_exists('WP_Block_Patterns_Registry') ? count(WP_Block_Patterns_Registry::get_instance()->get_all_registered()) : 0;

    // Installed plugins check
    $active_plugins = get_option('active_plugins', []);
    $plugin_checks = [
        'acf'          => ['label' => 'ACF (Advanced Custom Fields)', 'patterns' => ['advanced-custom-fields', 'acf/acf']],
        'yoast'        => ['label' => 'Yoast SEO', 'patterns' => ['wordpress-seo']],
        'rankmath'     => ['label' => 'Rank Math SEO', 'patterns' => ['seo-by-rank-math']],
        'woocommerce'  => ['label' => 'WooCommerce', 'patterns' => ['woocommerce']],
        'cf7'          => ['label' => 'Contact Form 7', 'patterns' => ['contact-form-7']],
        'wpforms'      => ['label' => 'WPForms', 'patterns' => ['wpforms-lite', 'wpforms']],
        'spectra'      => ['label' => 'Spectra (UAG Blocks)', 'patterns' => ['ultimate-addons-for-gutenberg', 'spectra']],
        'stackable'    => ['label' => 'Stackable', 'patterns' => ['stackable-ultimate-gutenberg-blocks']],
        'generateblocks' => ['label' => 'GenerateBlocks', 'patterns' => ['generateblocks']],
    ];

    $installed_plugins = [];
    foreach ($plugin_checks as $key => $info) {
        $found = false;
        foreach ($active_plugins as $p) {
            foreach ($info['patterns'] as $pat) {
                if (stripos($p, $pat) !== false) { $found = true; break 2; }
            }
        }
        $installed_plugins[$key] = $found;
    }
    ?>
    <div class="beg-wrap">
        <div class="beg-topbar">
            <h1><span class="beg-dot"></span> คู่มือ Block Editor — Site Builder Guide</h1>
            <div class="beg-topbar-actions">
                <a href="<?php echo esc_url($site_editor); ?>" class="beg-btn beg-btn-primary" target="_blank">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                    เปิด Site Editor
                </a>
                <a href="<?php echo esc_url(admin_url('customize.php')); ?>" class="beg-btn beg-btn-outline">
                    🎨 Customizer
                </a>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="beg-stats-row">
            <div class="beg-stat-card">
                <div class="beg-stat-num"><?php echo $block_count; ?></div>
                <div class="beg-stat-label">Blocks พร้อมใช้</div>
            </div>
            <div class="beg-stat-card">
                <div class="beg-stat-num"><?php echo $pattern_count; ?></div>
                <div class="beg-stat-label">Block Patterns</div>
            </div>
            <div class="beg-stat-card">
                <div class="beg-stat-num"><?php echo count($custom_templates) + 4; ?></div>
                <div class="beg-stat-label">Templates</div>
            </div>
            <div class="beg-stat-card">
                <div class="beg-stat-num"><?php echo count($template_parts); ?></div>
                <div class="beg-stat-label">Template Parts</div>
            </div>
            <div class="beg-stat-card">
                <div class="beg-stat-num"><?php echo count($color_palette); ?></div>
                <div class="beg-stat-label">สีในชุดธีม</div>
            </div>
        </div>

        <!-- Plugin Status -->
        <div class="beg-plugin-status">
            <h3>🔌 สถานะปลั๊กอินที่เกี่ยวข้อง</h3>
            <div class="beg-plugin-grid">
                <?php foreach ($plugin_checks as $key => $info): ?>
                <div class="beg-plugin-item <?php echo $installed_plugins[$key] ? 'active' : 'inactive'; ?>">
                    <span class="beg-plugin-dot"></span>
                    <?php echo esc_html($info['label']); ?>
                    <span class="beg-plugin-badge"><?php echo $installed_plugins[$key] ? '✓ Active' : '✗ ยังไม่ติดตั้ง'; ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════ -->
        <!-- 1. THEME BUILDER (FSE) -->
        <!-- ═══════════════════════════════════════════ -->
        <div class="beg-section">
            <div class="beg-section-header">
                <div class="beg-section-icon">🏗️</div>
                <div>
                    <h2>1. การสร้างโครงสร้างเว็บทั้งระบบ (Theme Builder / FSE)</h2>
                    <p>ออกแบบโครงสร้าง Header, Footer, Templates ทุกหน้าผ่าน Block Editor โดยไม่ต้องเขียนโค้ด</p>
                </div>
            </div>

            <div class="beg-feature-grid">
                <!-- Header & Footer -->
                <div class="beg-feature-card">
                    <div class="beg-feature-badge">Template Parts</div>
                    <h3>📐 Header & Footer</h3>
                    <p>ออกแบบเมนู, โลโก้, และส่วนท้ายเว็บให้เป็นเอกลักษณ์ในทุกๆ หน้า</p>
                    <div class="beg-how-to">
                        <strong>วิธีทำ:</strong>
                        <ol>
                            <li>ไปที่ <strong>Appearance → Editor</strong> (Site Editor)</li>
                            <li>คลิก <strong>"Template Parts"</strong> ในเมนูด้านซ้าย</li>
                            <li>เลือก <strong>Header</strong> หรือ <strong>Footer</strong></li>
                            <li>ใช้ Block Editor แก้ไข — เพิ่มบล็อก Site Logo, Navigation, Social Icons ฯลฯ</li>
                            <li>กด <strong>Save</strong> แล้วจะมีผลทุกหน้าทันที</li>
                        </ol>
                    </div>
                    <div class="beg-current-status">
                        <strong>สถานะปัจจุบัน:</strong>
                        <?php foreach ($template_parts as $tp): ?>
                        <span class="beg-tag"><?php echo esc_html($tp['title']); ?> (<?php echo esc_html($tp['area']); ?>)</span>
                        <?php endforeach; ?>
                    </div>
                    <a href="<?php echo esc_url($site_editor . '?path=%2Fpatterns&categoryType=wp_template_part&categoryId=header'); ?>" class="beg-link" target="_blank">→ แก้ไข Header</a>
                    <a href="<?php echo esc_url($site_editor . '?path=%2Fpatterns&categoryType=wp_template_part&categoryId=footer'); ?>" class="beg-link" target="_blank">→ แก้ไข Footer</a>
                </div>

                <!-- Templates -->
                <div class="beg-feature-card">
                    <div class="beg-feature-badge">Templates</div>
                    <h3>📄 แม่แบบ (Templates)</h3>
                    <p>สร้าง "แม่แบบ" สำหรับบทความ, สินค้า, หมวดหมู่ ครั้งเดียว — ข้อมูลจากฐานข้อมูลจะดึงมาใส่อัตโนมัติ</p>
                    <div class="beg-how-to">
                        <strong>วิธีทำ:</strong>
                        <ol>
                            <li>ไปที่ <strong>Appearance → Editor → Templates</strong></li>
                            <li>เลือก Template ที่ต้องการแก้ไข เช่น <code>Single Post</code>, <code>Product Detail</code></li>
                            <li>ใช้บล็อก <strong>Post Title</strong>, <strong>Post Content</strong>, <strong>Featured Image</strong>, <strong>Post Terms</strong> เพื่อแสดงข้อมูลจาก DB อัตโนมัติ</li>
                            <li>สามารถ <strong>สร้าง Template ใหม่</strong> ได้ โดยคลิก "Add New Template"</li>
                        </ol>
                    </div>
                    <div class="beg-template-list">
                        <strong>Templates ในธีมปัจจุบัน:</strong>
                        <div class="beg-template-grid">
                            <div class="beg-template-item">
                                <span class="beg-template-icon">📋</span>
                                <div>
                                    <strong>Index</strong>
                                    <small>หน้าหลักแสดงเนื้อหาทั่วไป</small>
                                </div>
                            </div>
                            <div class="beg-template-item">
                                <span class="beg-template-icon">📰</span>
                                <div>
                                    <strong>Single</strong>
                                    <small>แม่แบบบทความเดี่ยว</small>
                                </div>
                            </div>
                            <div class="beg-template-item">
                                <span class="beg-template-icon">📃</span>
                                <div>
                                    <strong>Page</strong>
                                    <small>แม่แบบหน้าเพจทั่วไป</small>
                                </div>
                            </div>
                            <?php foreach ($custom_templates as $ct): ?>
                            <div class="beg-template-item">
                                <span class="beg-template-icon">🎨</span>
                                <div>
                                    <strong><?php echo esc_html($ct['title']); ?></strong>
                                    <small>สำหรับ: <?php echo esc_html(implode(', ', $ct['postTypes'] ?? ['page'])); ?></small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <a href="<?php echo esc_url($site_editor . '?path=%2Ftemplates'); ?>" class="beg-link" target="_blank">→ จัดการ Templates</a>
                </div>

                <!-- 404 & Archive -->
                <div class="beg-feature-card">
                    <div class="beg-feature-badge">Special Pages</div>
                    <h3>🔍 404 & Archive Pages</h3>
                    <p>ออกแบบหน้า 404, Archive, Search Results ให้สวยงามตามต้องการ</p>
                    <div class="beg-how-to">
                        <strong>วิธีทำ:</strong>
                        <ol>
                            <li>ไปที่ <strong>Appearance → Editor → Templates</strong></li>
                            <li>คลิก <strong>"Add New Template"</strong></li>
                            <li>เลือก <strong>"Page: 404"</strong> หรือ <strong>"Archive"</strong></li>
                            <li>ออกแบบ Layout ด้วย Blocks ที่ต้องการ</li>
                            <li>ใช้บล็อก <strong>Query Loop</strong> สำหรับ Archive เพื่อแสดงรายการบทความอัตโนมัติ</li>
                        </ol>
                    </div>
                    <div class="beg-tip">
                        <strong>💡 Tip:</strong> ใช้บล็อก <strong>"Query Loop"</strong> เพื่อสร้างหน้า Archive ที่ดึงบทความตามหมวดหมู่, แท็ก, หรือ Post Type ได้อัตโนมัติ
                    </div>
                </div>

                <!-- Global Styles -->
                <div class="beg-feature-card">
                    <div class="beg-feature-badge">Global Styles</div>
                    <h3>🎨 Global Styles / theme.json</h3>
                    <p>เปลี่ยนสี, ฟอนต์, Spacing ทั้งเว็บได้ในคลิกเดียว</p>
                    <div class="beg-how-to">
                        <strong>วิธีทำ:</strong>
                        <ol>
                            <li>ใน Site Editor คลิกปุ่ม <strong>"Styles"</strong> (ไอคอนครึ่งวงกลม 🎨) มุมขวาบน</li>
                            <li>ปรับ <strong>Colors</strong> — สีหลัก, สีข้อความ, สีพื้นหลัง</li>
                            <li>ปรับ <strong>Typography</strong> — ฟอนต์, ขนาดตัวอักษร</li>
                            <li>ปรับ <strong>Layout</strong> — ความกว้าง Content, Padding</li>
                            <li>ทุกหน้าจะเปลี่ยนตามทันที!</li>
                        </ol>
                    </div>
                    <div class="beg-palette-preview">
                        <strong>Color Palette ปัจจุบัน:</strong>
                        <div class="beg-palette-swatches">
                            <?php foreach ($color_palette as $c): ?>
                            <div class="beg-swatch" style="background:<?php echo esc_attr($c['color']); ?>;" title="<?php echo esc_attr($c['name'] . ': ' . $c['color']); ?>"></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=my-theme-settings')); ?>" class="beg-link">→ Theme Settings (ตั้งค่าธีมละเอียด)</a>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════ -->
        <!-- 2. DYNAMIC CONTENT -->
        <!-- ═══════════════════════════════════════════ -->
        <div class="beg-section">
            <div class="beg-section-header">
                <div class="beg-section-icon">⚡</div>
                <div>
                    <h2>2. การจัดการข้อมูลแบบ Dynamic (Dynamic Content)</h2>
                    <p>เชื่อมต่อ Custom Fields, Post Types, เงื่อนไขแสดงผล — หัวใจของเว็บซับซ้อน</p>
                </div>
            </div>

            <div class="beg-feature-grid">
                <!-- Custom Post Types -->
                <div class="beg-feature-card">
                    <div class="beg-feature-badge beg-badge-green">มีอยู่แล้ว ✓</div>
                    <h3>📦 Custom Post Types</h3>
                    <p>ธีมนี้มี Custom Post Type "Product" พร้อมใช้งานแล้ว</p>
                    <div class="beg-how-to">
                        <strong>สิ่งที่ใช้งานได้แล้ว:</strong>
                        <ul>
                            <li>✅ <strong>Product</strong> CPT — จัดการสินค้าผ่าน <a href="<?php echo esc_url($admin_url . 'admin.php?page=product-manager'); ?>">Product Manager</a></li>
                            <li>✅ <strong>Product Category</strong> Taxonomy — จัดหมวดหมู่สินค้า</li>
                            <li>✅ Spec Fields (Inductance, Voltage, Current ฯลฯ)</li>
                            <li>✅ Template สำหรับ Single Product & Product Archive</li>
                        </ul>
                    </div>
                    <a href="<?php echo esc_url($admin_url . 'admin.php?page=product-manager'); ?>" class="beg-link">→ จัดการสินค้า</a>
                </div>

                <!-- ACF Integration -->
                <div class="beg-feature-card">
                    <div class="beg-feature-badge beg-badge-blue">แนะนำ</div>
                    <h3>🧩 Custom Fields (ACF)</h3>
                    <p>สร้าง "ฟิลด์ข้อมูล" พิเศษ แล้ว Block Editor จะดึงค่ามาแสดงอัตโนมัติ</p>
                    <div class="beg-how-to">
                        <strong>วิธีใช้ ACF กับ Block Editor:</strong>
                        <ol>
                            <li>ติดตั้ง <strong>ACF PRO</strong> จาก Plugins → Add New</li>
                            <li>สร้าง Field Group ใหม่ เช่น "ราคาเริ่มต้น", "คะแนนรีวิว", "แผนที่"</li>
                            <li>ใน Block Editor เลือกบล็อก Post Content และใช้ <strong>"ACF Block"</strong> แสดงค่าฟิลด์</li>
                            <li>หรือใช้ <strong>Bindings API</strong> ของ WP 6.5+ เพื่อผูกค่า custom field เข้ากับบล็อกโดยตรง</li>
                        </ol>
                    </div>
                    <div class="beg-code-example">
                        <strong>ตัวอย่าง Block Bindings API (WP 6.5+):</strong>
                        <pre><code>&lt;!-- wp:paragraph {
  "metadata": {
    "bindings": {
      "content": {
        "source": "core/post-meta",
        "args": { "key": "pd_inductance" }
      }
    }
  }
} --&gt;
&lt;p&gt;&lt;/p&gt;
&lt;!-- /wp:paragraph --&gt;</code></pre>
                    </div>
                    <?php if (!$installed_plugins['acf']): ?>
                    <div class="beg-warning">⚠️ ACF ยังไม่ได้ติดตั้ง — <a href="<?php echo esc_url($admin_url . 'plugin-install.php?s=advanced+custom+fields&tab=search&type=term'); ?>">ติดตั้งเลย</a></div>
                    <?php else: ?>
                    <div class="beg-success">✅ ACF ติดตั้งแล้ว — <a href="<?php echo esc_url($admin_url . 'edit.php?post_type=acf-field-group'); ?>">จัดการ Field Groups</a></div>
                    <?php endif; ?>
                </div>

                <!-- Conditional Display -->
                <div class="beg-feature-card">
                    <div class="beg-feature-badge beg-badge-purple">Advanced</div>
                    <h3>🔀 Logic & Conditions</h3>
                    <p>กำหนดเงื่อนไขแสดงผล เช่น แสดงปุ่มต่างกันตามสถานะสมาชิก</p>
                    <div class="beg-how-to">
                        <strong>วิธีทำ (หลายตัวเลือก):</strong>
                        <ol>
                            <li><strong>วิธีที่ 1 — Block Visibility Plugin:</strong> ติดตั้งปลั๊กอิน "Block Visibility" แล้วตั้งเงื่อนไขแสดง/ซ่อนบล็อกแต่ละตัว</li>
                            <li><strong>วิธีที่ 2 — Spectra / Stackable:</strong> ปลั๊กอิน Block Library ที่มี Conditional Display ในตัว</li>
                            <li><strong>วิธีที่ 3 — Code:</strong> ใช้ <code>render_block</code> filter ใน functions.php ซ่อน/แสดงบล็อกตาม <code>is_user_logged_in()</code></li>
                        </ol>
                    </div>
                    <div class="beg-code-example">
                        <strong>ตัวอย่าง PHP Filter:</strong>
                        <pre><code>// ซ่อนบล็อกที่มี CSS class "members-only" สำหรับผู้ที่ยังไม่ล็อกอิน
add_filter('render_block', function($html, $block) {
    $classes = $block['attrs']['className'] ?? '';
    if (strpos($classes, 'members-only') !== false && !is_user_logged_in()) {
        return ''; // ไม่แสดงบล็อกนี้
    }
    return $html;
}, 10, 2);</code></pre>
                    </div>
                </div>

                <!-- Query Loop -->
                <div class="beg-feature-card">
                    <div class="beg-feature-badge beg-badge-green">Built-in ✓</div>
                    <h3>🔄 Query Loop Block</h3>
                    <p>ดึงข้อมูล Posts, Products, หรือ CPT ใดๆ มาแสดงแบบ Grid/List อัตโนมัติ</p>
                    <div class="beg-how-to">
                        <strong>วิธีทำ:</strong>
                        <ol>
                            <li>เพิ่มบล็อก <strong>"Query Loop"</strong> ใน Block Editor</li>
                            <li>เลือก Post Type ที่ต้องการ (Posts, Products, etc.)</li>
                            <li>ตั้ง Filters: หมวดหมู่, แท็ก, จำนวนที่แสดง, การเรียงลำดับ</li>
                            <li>ออกแบบ Layout ภายใน Loop ด้วยบล็อก Post Title, Excerpt, Featured Image</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════ -->
        <!-- 3. CONVERSION TOOLS -->
        <!-- ═══════════════════════════════════════════ -->
        <div class="beg-section">
            <div class="beg-section-header">
                <div class="beg-section-icon">💰</div>
                <div>
                    <h2>3. เครื่องมือสร้างยอดขายและ Conversion</h2>
                    <p>Pop-ups, Mega Menu, WooCommerce Builder — เครื่องมือการตลาดผ่าน Block Editor</p>
                </div>
            </div>

            <div class="beg-feature-grid">
                <!-- Pop-ups & Modals -->
                <div class="beg-feature-card">
                    <div class="beg-feature-badge beg-badge-orange">Plugin Required</div>
                    <h3>💬 Pop-ups & Modals</h3>
                    <p>สร้างปุ่มเด้ง (Pop-up) เมื่อคนจะออกจากเว็บ หรือแสดงโปรโมชั่นตามเวลา</p>
                    <div class="beg-how-to">
                        <strong>ตัวเลือกที่แนะนำ:</strong>
                        <div class="beg-option-list">
                            <div class="beg-option">
                                <span class="beg-option-rank">1</span>
                                <div>
                                    <strong>Spectra (Free)</strong>
                                    <p>มีบล็อก Modal / Popup ใน Gutenberg พร้อม trigger ได้ตาม Exit Intent, Scroll, Time</p>
                                </div>
                            </div>
                            <div class="beg-option">
                                <span class="beg-option-rank">2</span>
                                <div>
                                    <strong>Jetrasuspended Popup Builder</strong>
                                    <p>ทำ Popup ด้วย Block Editor แยกต่างหาก มี conditions ละเอียด</p>
                                </div>
                            </div>
                            <div class="beg-option">
                                <span class="beg-option-rank">3</span>
                                <div>
                                    <strong>ธีมมี Bootstrap Modal Pattern</strong>
                                    <p>ใช้ pattern <code>bootstrap-modal</code> ที่มีอยู่ในธีมได้เลย</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="beg-tip">
                        <strong>💡 Tip:</strong> ธีมนี้มี Bootstrap Modal Pattern อยู่แล้ว — สามารถใส่ใน Block Editor แล้วเรียกผ่านปุ่มได้ทันที
                    </div>
                </div>

                <!-- Mega Menu -->
                <div class="beg-feature-card">
                    <div class="beg-feature-badge beg-badge-orange">Plugin Required</div>
                    <h3>📋 Mega Menu</h3>
                    <p>สร้างเมนูขนาดใหญ่ที่มีรูปภาพ, ไอคอน, หรือเนื้อหาซับซ้อน</p>
                    <div class="beg-how-to">
                        <strong>ตัวเลือกที่แนะนำ:</strong>
                        <ol>
                            <li><strong>Spectra Mega Menu:</strong> สร้าง Mega Menu ด้วย Gutenberg Block โดยตรง</li>
                            <li><strong>Navigation Block (WP Core):</strong> ใช้ได้พื้นฐาน — เพิ่ม Submenu ซ้อนได้หลายระดับ</li>
                            <li><strong>ธีมปัจจุบัน:</strong> รองรับ Dropdown + Submenu 2 ระดับ พร้อม Product Categories อัตโนมัติ</li>
                        </ol>
                    </div>
                    <div class="beg-current-status">
                        <strong>✅ ระบบเมนูปัจจุบัน:</strong>
                        <span class="beg-tag">Dropdown Multi-level</span>
                        <span class="beg-tag">Product Categories Auto</span>
                        <span class="beg-tag">CTA Button</span>
                        <span class="beg-tag">ตั้งค่าได้ใน Theme Settings</span>
                    </div>
                </div>

                <!-- WooCommerce -->
                <div class="beg-feature-card">
                    <div class="beg-feature-badge beg-badge-purple">Optional</div>
                    <h3>🛒 WooCommerce Builder</h3>
                    <p>ปรับแต่งหน้าตะกร้า, Checkout, รายละเอียดสินค้า — ถ้าเปิดร้านค้าออนไลน์</p>
                    <div class="beg-how-to">
                        <strong>สิ่งที่ WooCommerce + Block Editor ทำได้:</strong>
                        <ul>
                            <li>ใช้ <strong>WooCommerce Blocks</strong> แก้ไขหน้า Cart, Checkout ได้อิสระ</li>
                            <li>สร้าง <strong>Product Grid / Featured Product</strong> ด้วยบล็อก</li>
                            <li>ปรับแต่ง <strong>Single Product Template</strong> ใน Site Editor</li>
                        </ul>
                    </div>
                    <?php if (!$installed_plugins['woocommerce']): ?>
                    <div class="beg-info">ℹ️ WooCommerce ยังไม่ได้ติดตั้ง — ธีมนี้ใช้ระบบ Product Manager แทน ซึ่งเหมาะกับแค็ตตาล็อกสินค้าที่ไม่ต้องการตะกร้า</div>
                    <?php else: ?>
                    <div class="beg-success">✅ WooCommerce ติดตั้งแล้ว</div>
                    <?php endif; ?>
                </div>

                <!-- Chat Widget -->
                <div class="beg-feature-card">
                    <div class="beg-feature-badge beg-badge-green">มีอยู่แล้ว ✓</div>
                    <h3>💬 Chat Widget & RAG</h3>
                    <p>ระบบแชทสดและ AI Chat ที่จัดการได้ผ่าน Theme Settings</p>
                    <div class="beg-how-to">
                        <strong>ฟีเจอร์ที่มีอยู่แล้ว:</strong>
                        <ul>
                            <li>✅ <strong>LINE</strong> Chat Button</li>
                            <li>✅ <strong>WhatsApp</strong> Chat Button</li>
                            <li>✅ <strong>WeChat</strong> Chat Button (พร้อม QR Code)</li>
                            <li>✅ <strong>RAG AI Chat</strong> — ค้นหาข้อมูลสินค้าอัจฉริยะ</li>
                        </ul>
                    </div>
                    <a href="<?php echo esc_url($admin_url . 'admin.php?page=my-theme-settings'); ?>" class="beg-link">→ ตั้งค่า Chat ใน Theme Settings</a>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════ -->
        <!-- 4. INTEGRATION -->
        <!-- ═══════════════════════════════════════════ -->
        <div class="beg-section">
            <div class="beg-section-header">
                <div class="beg-section-icon">🔗</div>
                <div>
                    <h2>4. การเชื่อมต่อและระบบอัตโนมัติ (Integration)</h2>
                    <p>API, Form Builder, CRM, Google Sheets — เชื่อมต่อทุกระบบ</p>
                </div>
            </div>

            <div class="beg-feature-grid">
                <!-- API Integration -->
                <div class="beg-feature-card">
                    <div class="beg-feature-badge beg-badge-blue">แนะนำ</div>
                    <h3>🌐 API Integration</h3>
                    <p>เชื่อมต่อกับ Google Maps, ระบบสมาชิก, หรือระบบจองคิว</p>
                    <div class="beg-how-to">
                        <strong>ฟีเจอร์ที่ทำได้ใน Block Editor:</strong>
                        <ul>
                            <li><strong>Google Maps:</strong> ใช้บล็อก HTML หรือ Map Block ฝัง iframe ได้เลย — <em>ธีมนี้มี kv/google-map block พร้อมแล้ว</em></li>
                            <li><strong>YouTube / Vimeo:</strong> วาง URL แล้ว Block Editor จะ embed อัตโนมัติ (oEmbed)</li>
                            <li><strong>Custom API:</strong> ใช้บล็อก HTML + JavaScript ดึงข้อมูลจาก REST API</li>
                            <li><strong>Zapier / Make:</strong> เชื่อมต่อ Webhook กับ Form เพื่อส่งข้อมูลไประบบอื่น</li>
                        </ul>
                    </div>
                </div>

                <!-- Form Builder -->
                <div class="beg-feature-card">
                    <div class="beg-feature-badge beg-badge-green">มีอยู่แล้ว ✓</div>
                    <h3>📝 Form Builder</h3>
                    <p>ฟอร์มรับข้อมูล ส่งเข้า Email, CRM, Google Sheets ได้โดยตรง</p>
                    <div class="beg-how-to">
                        <strong>สิ่งที่มีอยู่ในธีม:</strong>
                        <ul>
                            <li>✅ <strong>Contact Form</strong> — kv/contact-form block พร้อมใช้งาน</li>
                            <li>✅ <strong>Datasheet Download Form</strong> — เก็บ Lead อัตโนมัติ</li>
                        </ul>
                        <strong>ต้องการเพิ่มเติม:</strong>
                        <ul>
                            <li><strong>WPForms:</strong> Drag & Drop form builder ที่เชื่อมได้กับ Mailchimp, Google Sheets, Salesforce</li>
                            <li><strong>Gravity Forms:</strong> ฟอร์มซับซ้อน — คำนวณราคา, ลอจิกเงื่อนไข, Multi-step</li>
                            <li><strong>Fluent Forms:</strong> ฟรีและครบ — Conversational Form, PDF Generator</li>
                        </ul>
                    </div>
                    <?php if ($installed_plugins['cf7']): ?>
                    <div class="beg-success">✅ Contact Form 7 ติดตั้งแล้ว</div>
                    <?php endif; ?>
                    <?php if ($installed_plugins['wpforms']): ?>
                    <div class="beg-success">✅ WPForms ติดตั้งแล้ว</div>
                    <?php endif; ?>
                </div>

                <!-- Datasheet / Lead Gen -->
                <div class="beg-feature-card">
                    <div class="beg-feature-badge beg-badge-green">มีอยู่แล้ว ✓</div>
                    <h3>📥 Datasheet Download / Lead Gen</h3>
                    <p>ระบบดาวน์โหลด Datasheet พร้อมเก็บข้อมูล Lead อัตโนมัติ</p>
                    <div class="beg-how-to">
                        <strong>ฟีเจอร์ที่มี:</strong>
                        <ul>
                            <li>✅ ฟอร์มดาวน์โหลด Datasheet (กรอกชื่อ-อีเมลก่อนดาวน์โหลด)</li>
                            <li>✅ เก็บ Lead ลงฐานข้อมูล พร้อม Export CSV</li>
                            <li>✅ ดูรายงานได้ที่ <a href="<?php echo esc_url($admin_url . 'admin.php?page=datasheet-leads'); ?>">Datasheet Downloads</a></li>
                        </ul>
                    </div>
                </div>

                <!-- REST API -->
                <div class="beg-feature-card">
                    <div class="beg-feature-badge beg-badge-green">มีอยู่แล้ว ✓</div>
                    <h3>🔌 REST API Endpoints</h3>
                    <p>ธีมนี้มี REST API สำหรับเชื่อมต่อกับระบบภายนอก</p>
                    <div class="beg-how-to">
                        <strong>API Endpoints ที่พร้อมใช้:</strong>
                        <ul>
                            <li><code>/wp-json/kv/v1/block-editor-settings</code> — ตั้งค่า Block Editor</li>
                            <li><code>/wp-json/kv/v1/products</code> — ค้นหาสินค้า</li>
                            <li><code>/wp-json/kv/v1/rag-search</code> — AI Search</li>
                            <li><code>/wp-json/wp/v2/product</code> — WordPress REST API สำหรับ Products</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════ -->
        <!-- 5. PERFORMANCE & SEO -->
        <!-- ═══════════════════════════════════════════ -->
        <div class="beg-section">
            <div class="beg-section-header">
                <div class="beg-section-icon">🚀</div>
                <div>
                    <h2>5. การปรับแต่งประสิทธิภาพและ SEO</h2>
                    <p>SEO, Image Optimization, Global Settings — ปรับแต่งเชิงเทคนิค</p>
                </div>
            </div>

            <div class="beg-feature-grid">
                <!-- SEO -->
                <div class="beg-feature-card">
                    <div class="beg-feature-badge beg-badge-blue">แนะนำ</div>
                    <h3>🔍 SEO Control</h3>
                    <p>กำหนดโครงสร้าง H1, H2, H3 ให้ถูกต้องตามหลัก SEO</p>
                    <div class="beg-how-to">
                        <strong>วิธีทำ SEO ด้วย Block Editor:</strong>
                        <ol>
                            <li><strong>Heading Blocks:</strong> ใช้บล็อก Heading เลือกระดับ H1-H6 ได้ง่ายๆ — ทุกหน้าควรมี H1 แค่ 1 ตัว</li>
                            <li><strong>Image Alt Text:</strong> คลิกรูปภาพ → ใส่ Alt Text ในแถบขวา</li>
                            <li><strong>Meta Title & Description:</strong> ติดตั้ง Yoast SEO หรือ Rank Math</li>
                            <li><strong>Schema Markup:</strong> ปลั๊กอิน SEO จะเพิ่ม Structured Data อัตโนมัติ</li>
                        </ol>
                    </div>
                    <?php if ($installed_plugins['yoast']): ?>
                    <div class="beg-success">✅ Yoast SEO ติดตั้งแล้ว</div>
                    <?php elseif ($installed_plugins['rankmath']): ?>
                    <div class="beg-success">✅ Rank Math ติดตั้งแล้ว</div>
                    <?php else: ?>
                    <div class="beg-warning">⚠️ ยังไม่มีปลั๊กอิน SEO — <a href="<?php echo esc_url($admin_url . 'plugin-install.php?s=yoast+seo&tab=search&type=term'); ?>">ติดตั้ง Yoast SEO</a> หรือ <a href="<?php echo esc_url($admin_url . 'plugin-install.php?s=rank+math&tab=search&type=term'); ?>">Rank Math</a></div>
                    <?php endif; ?>
                </div>

                <!-- Image Optimization -->
                <div class="beg-feature-card">
                    <div class="beg-feature-badge beg-badge-blue">แนะนำ</div>
                    <h3>🖼️ Image Optimization</h3>
                    <p>Lazy Load, WebP, ปรับขนาดอัตโนมัติ — ให้เว็บโหลดเร็ว</p>
                    <div class="beg-how-to">
                        <strong>สิ่งที่ WordPress ทำให้อัตโนมัติ:</strong>
                        <ol>
                            <li>✅ <strong>Lazy Loading:</strong> WordPress 5.5+ ใส่ <code>loading="lazy"</code> อัตโนมัติทุกรูป</li>
                            <li>✅ <strong>Responsive Images:</strong> WordPress สร้าง srcset หลายขนาดอัตโนมัติ</li>
                            <li>✅ <strong>WebP:</strong> WordPress 6.1+ แปลง JPEG/PNG เป็น WebP อัตโนมัติ (ถ้าเซิร์ฟเวอร์รองรับ)</li>
                        </ol>
                        <strong>ปลั๊กอินแนะนำเพิ่ม:</strong>
                        <ul>
                            <li><strong>ShortPixel / Imagify:</strong> บีบอัดรูปอัตโนมัติ + แปลง WebP/AVIF</li>
                            <li><strong>Perfmatters:</strong> ปิด Unused CSS/JS, Preload Key Resources</li>
                        </ul>
                    </div>
                </div>

                <!-- Global Settings -->
                <div class="beg-feature-card">
                    <div class="beg-feature-badge beg-badge-green">มีอยู่แล้ว ✓</div>
                    <h3>⚙️ Global Settings (Theme Settings)</h3>
                    <p>เปลี่ยนสี, ฟอนต์, Layout ทั้งเว็บในที่เดียว</p>
                    <div class="beg-how-to">
                        <strong>ตั้งค่าได้ที่:</strong>
                        <ul>
                            <li>✅ <strong><a href="<?php echo esc_url($admin_url . 'admin.php?page=my-theme-settings'); ?>">Theme Settings</a></strong> — สี, โลโก้, Contact Info, Navigation, Banner, About, Chat, Block Editor</li>
                            <li>✅ <strong>Site Editor → Styles</strong> — Global Colors, Typography, Layout</li>
                            <li>✅ <strong>Block Editor Manager</strong> — ควบคุม Blocks, Patterns, Colors, Font Sizes, CSS</li>
                        </ul>
                    </div>
                    <div class="beg-current-status">
                        <strong>Layout ปัจจุบัน:</strong>
                        <span class="beg-tag">Content: <?php echo esc_html($content_width); ?></span>
                        <span class="beg-tag">Wide: <?php echo esc_html($wide_width); ?></span>
                    </div>
                </div>

                <!-- Block Editor Manager -->
                <div class="beg-feature-card">
                    <div class="beg-feature-badge beg-badge-green">มีอยู่แล้ว ✓</div>
                    <h3>🧱 Block Editor Manager</h3>
                    <p>ควบคุม Block Editor 100% — ซ่อน/แสดงบล็อก, ปรับสี, ฟอนต์, Custom CSS</p>
                    <div class="beg-how-to">
                        <strong>ฟีเจอร์:</strong>
                        <ul>
                            <li>✅ เปิด/ปิดบล็อกแต่ละตัว (Block Visibility)</li>
                            <li>✅ เปิด/ปิด Pattern แต่ละตัว</li>
                            <li>✅ กำหนด Custom Colors & Font Sizes</li>
                            <li>✅ ปรับ Content/Wide Width</li>
                            <li>✅ Custom Editor CSS</li>
                            <li>✅ Freedom Mode — เปิดทุกอย่าง 100%</li>
                        </ul>
                    </div>
                    <a href="<?php echo esc_url($admin_url . 'admin.php?page=my-theme-settings'); ?>" class="beg-link">→ ตั้งค่าใน Theme Settings → Block Editor</a>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════ -->
        <!-- 6. PRODUCT MANAGER + BLOCK EDITOR -->
        <!-- ═══════════════════════════════════════════ -->
        <?php
        // Gather PM-specific data
        $pm_page = get_page_by_path('product-manager');
        $pm_page_edit_url = $pm_page ? admin_url('post.php?post=' . $pm_page->ID . '&action=edit') : '';
        $pm_page_view_url = $pm_page ? get_permalink($pm_page) : '';
        $product_count = wp_count_posts('product');
        $pm_published = isset($product_count->publish) ? (int)$product_count->publish : 0;
        $pm_draft = isset($product_count->draft) ? (int)$product_count->draft : 0;
        $pm_cat_count = wp_count_terms(['taxonomy' => 'product_category', 'hide_empty' => false]);
        $pm_spec_fields = function_exists('pm_get_all_spec_fields') ? pm_get_all_spec_fields() : [];
        ?>
        <div class="beg-section">
            <div class="beg-section-header" style="background:linear-gradient(135deg, #fef3c7 0%, #fce7f3 100%);border-color:#fde68a;">
                <div class="beg-section-icon">📦</div>
                <div>
                    <h2>6. Product Manager + Block Editor — แก้ไขหน้าจัดการสินค้า</h2>
                    <p>วิธีใช้ Block Editor แก้ไขหน้า Product Manager, Product Detail, Product Archive ทั้งระบบ</p>
                </div>
            </div>

            <!-- PM Stats -->
            <div class="beg-stats-row" style="margin-bottom:20px;">
                <div class="beg-stat-card">
                    <div class="beg-stat-num"><?php echo $pm_published; ?></div>
                    <div class="beg-stat-label">สินค้า Published</div>
                </div>
                <div class="beg-stat-card">
                    <div class="beg-stat-num"><?php echo $pm_draft; ?></div>
                    <div class="beg-stat-label">สินค้า Draft</div>
                </div>
                <div class="beg-stat-card">
                    <div class="beg-stat-num"><?php echo is_numeric($pm_cat_count) ? $pm_cat_count : 0; ?></div>
                    <div class="beg-stat-label">Categories</div>
                </div>
                <div class="beg-stat-card">
                    <div class="beg-stat-num"><?php echo count($pm_spec_fields); ?></div>
                    <div class="beg-stat-label">Spec Fields</div>
                </div>
            </div>

            <!-- Architecture Diagram -->
            <div class="beg-feature-card" style="margin-bottom:20px;background:linear-gradient(135deg,#f8fafc,#f1f5f9);border:2px dashed #cbd5e1;">
                <h3>🏛️ สถาปัตยกรรมระบบ Product Manager</h3>
                <p style="color:var(--beg-muted);margin-bottom:16px;">ระบบ Product Manager ทำงานร่วมกับ Block Editor ใน 4 จุดหลัก:</p>
                <div class="beg-arch-flow">
                    <div class="beg-arch-node beg-arch-admin">
                        <strong>1. Admin Panel</strong>
                        <small>CRUD สินค้า + Categories</small>
                        <code>📦 Product Manager</code>
                    </div>
                    <div class="beg-arch-arrow">→</div>
                    <div class="beg-arch-node beg-arch-db">
                        <strong>2. Database (CPT)</strong>
                        <small>Custom Post Type + Post Meta</small>
                        <code>wp_posts + wp_postmeta</code>
                    </div>
                    <div class="beg-arch-arrow">→</div>
                    <div class="beg-arch-node beg-arch-template">
                        <strong>3. Templates (FSE)</strong>
                        <small>Block Editor จัดการ Layout</small>
                        <code>single-product.html</code>
                    </div>
                    <div class="beg-arch-arrow">→</div>
                    <div class="beg-arch-node beg-arch-front">
                        <strong>4. หน้าเว็บ (Frontend)</strong>
                        <small>ข้อมูลแสดงอัตโนมัติ</small>
                        <code>/products/ชื่อสินค้า/</code>
                    </div>
                </div>
            </div>

            <div class="beg-feature-grid">

                <!-- A. Edit Product Manager Page Layout -->
                <div class="beg-feature-card">
                    <div class="beg-feature-badge beg-badge-green">แก้ไขได้ ✓</div>
                    <h3>📝 แก้ไขหน้า Product Manager ด้วย Block Editor</h3>
                    <p>หน้า Product Manager มี Page Template พิเศษ — สามารถเพิ่มเนื้อหาด้านบนได้ผ่าน Block Editor</p>
                    <div class="beg-how-to">
                        <strong>วิธีแก้ไข:</strong>
                        <ol>
                            <li>ไปที่ <strong>Pages → Product Manager</strong> (หรือคลิกปุ่มด้านล่าง)</li>
                            <li>Block Editor จะเปิดขึ้น — <strong>เนื้อหาที่คุณเพิ่มจะแสดงด้านบน Product Manager</strong></li>
                            <li>สามารถเพิ่ม <strong>ข้อความแนะนำ, ลิงก์ด่วน, รูปภาพ, หรือ Banner</strong> ได้</li>
                            <li>ส่วน Product Manager (ตาราง/ฟอร์ม) จะแสดงอัตโนมัติด้านล่างเสมอ</li>
                        </ol>
                    </div>
                    <div class="beg-tip">
                        <strong>💡 ใช้ได้ดีสำหรับ:</strong> เพิ่มข้อความ "คำแนะนำการจัดการสินค้า" หรือลิงก์ไปหน้า Products / Datasheet Downloads
                    </div>
                    <?php if ($pm_page): ?>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px;">
                        <a href="<?php echo esc_url($pm_page_edit_url); ?>" class="beg-btn beg-btn-primary" style="font-size:13px;padding:8px 16px;">✏️ แก้ไขใน Block Editor</a>
                        <a href="<?php echo esc_url($pm_page_view_url); ?>" class="beg-btn beg-btn-outline" style="font-size:13px;padding:8px 16px;" target="_blank">🌐 ดูหน้าเว็บ</a>
                    </div>
                    <?php else: ?>
                    <div class="beg-info">ℹ️ ยังไม่ได้สร้างหน้า "Product Manager" — สร้างเพจใหม่แล้วเลือก Template "จัดการสินค้า (Product Manager)"</div>
                    <?php endif; ?>
                </div>

                <!-- B. Edit Single Product Template -->
                <div class="beg-feature-card">
                    <div class="beg-feature-badge beg-badge-blue">FSE Template</div>
                    <h3>🎨 แก้ไข Template หน้ารายละเอียดสินค้า (Product Detail)</h3>
                    <p>ออกแบบ Layout ของหน้ารายละเอียดสินค้าทุกตัวผ่าน Site Editor</p>
                    <div class="beg-how-to">
                        <strong>วิธีทำ:</strong>
                        <ol>
                            <li>ไปที่ <strong>Appearance → Editor → Templates</strong></li>
                            <li>เลือก <strong>"Product Detail"</strong> (single-product)</li>
                            <li>Template ปัจจุบันใช้ <code>[product_detail]</code> shortcode ที่ดึงข้อมูลสินค้าอัตโนมัติ</li>
                            <li>คุณสามารถเพิ่ม <strong>บล็อกก่อนหรือหลัง</strong> shortcode ได้ เช่น CTA Banner, Related Products, Testimonials</li>
                        </ol>
                    </div>
                    <div class="beg-how-to">
                        <strong>สิ่งที่แสดงอัตโนมัติจาก Database:</strong>
                        <ul>
                            <li>✅ <strong>ชื่อสินค้า</strong> (Post Title)</li>
                            <li>✅ <strong>Subtitle</strong> (pd_subtitle custom field)</li>
                            <li>✅ <strong>Description</strong> (Post Content — Rich Text)</li>
                            <li>✅ <strong>Gallery รูปภาพ</strong> (pd_gallery — auto-slide)</li>
                            <li>✅ <strong>Specifications</strong> (<?php echo count($pm_spec_fields); ?> fields — Inductance, Voltage, etc.)</li>
                            <li>✅ <strong>Custom Attributes</strong> (pd_custom_attrs — เพิ่มได้ไม่จำกัด)</li>
                            <li>✅ <strong>Datasheet PDF</strong> (pd_datasheet — download กับ Lead Gen)</li>
                            <li>✅ <strong>Breadcrumb</strong> (Category hierarchy อัตโนมัติ)</li>
                            <li>✅ <strong>Related Products</strong> (สินค้าในหมวดเดียวกัน)</li>
                        </ul>
                    </div>
                    <a href="<?php echo esc_url($site_editor . '?path=%2Ftemplates'); ?>" class="beg-link" target="_blank">→ จัดการ Templates ใน Site Editor</a>
                </div>

                <!-- C. Edit Product Category Archive -->
                <div class="beg-feature-card">
                    <div class="beg-feature-badge beg-badge-blue">FSE Template</div>
                    <h3>📁 แก้ไข Template หน้าหมวดหมู่สินค้า (Category Archive)</h3>
                    <p>ออกแบบหน้าแสดงรายการสินค้าในแต่ละหมวดหมู่</p>
                    <div class="beg-how-to">
                        <strong>วิธีทำ:</strong>
                        <ol>
                            <li>ไปที่ <strong>Appearance → Editor → Templates</strong></li>
                            <li>เลือก <strong>"Archive: Product Category"</strong></li>
                            <li>Template ใช้ <code>[product_category_archive]</code> shortcode ที่แสดงสินค้าเป็น Grid อัตโนมัติ</li>
                            <li>สามารถเพิ่มบล็อก <strong>ก่อนหรือหลัง</strong> shortcode ได้</li>
                        </ol>
                    </div>
                    <div class="beg-how-to">
                        <strong>สิ่งที่แสดงอัตโนมัติ:</strong>
                        <ul>
                            <li>✅ <strong>Banner สี + รูป Blur</strong> (ใช้สีจาก Theme Primary + รูปสินค้าแรก)</li>
                            <li>✅ <strong>Breadcrumb</strong> (Home → Products → Category)</li>
                            <li>✅ <strong>Subcategory Cards</strong> (ถ้ามี child categories)</li>
                            <li>✅ <strong>Product Cards Grid</strong> (รูป + ชื่อ + Subtitle)</li>
                            <li>✅ <strong>Category Description & Specs</strong> (ถ้ากรอกใน PM)</li>
                        </ul>
                    </div>
                    <a href="<?php echo esc_url($site_editor . '?path=%2Ftemplates'); ?>" class="beg-link" target="_blank">→ จัดการ Templates</a>
                </div>

                <!-- D. Header & Footer with Products -->
                <div class="beg-feature-card">
                    <div class="beg-feature-badge beg-badge-green">อัตโนมัติ ✓</div>
                    <h3>📐 Header & Footer — Product Categories Menu</h3>
                    <p>เมนู Navigation แสดง Product Categories อัตโนมัติจาก Database</p>
                    <div class="beg-how-to">
                        <strong>สิ่งที่ทำอัตโนมัติ:</strong>
                        <ul>
                            <li>✅ <strong>Dropdown Menu</strong> — แสดง Product Categories เป็น submenu อัตโนมัติ</li>
                            <li>✅ <strong>Footer Product List</strong> — แสดงรายการ categories ใน footer</li>
                            <li>✅ <strong>Multi-level Submenu</strong> — รองรับ Parent → Child categories</li>
                        </ul>
                        <strong>ปรับแต่งได้ที่:</strong>
                        <ol>
                            <li><strong>Theme Settings → Navigation</strong> — ปรับชื่อเมนู, สี, ขนาดฟอนต์, CTA button</li>
                            <li><strong>Product Manager → Categories</strong> — เพิ่ม/ลบ/แก้ไข categories = เมนูอัปเดตทันที</li>
                        </ol>
                    </div>
                    <a href="<?php echo esc_url($admin_url . 'admin.php?page=my-theme-settings'); ?>" class="beg-link">→ ตั้งค่า Navigation ใน Theme Settings</a>
                </div>

                <!-- E. Dynamic Data — Custom Fields -->
                <div class="beg-feature-card">
                    <div class="beg-feature-badge beg-badge-purple">Advanced</div>
                    <h3>⚡ Dynamic Content — Custom Fields สินค้า</h3>
                    <p>ข้อมูลสินค้าทุกตัวถูกเก็บใน Custom Fields (Post Meta) ที่ Block Editor สามารถดึงมาใช้ได้</p>
                    <div class="beg-how-to">
                        <strong>Custom Fields ที่จัดการได้ผ่าน Product Manager:</strong>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-top:8px;">
                            <div style="font-size:12px;padding:4px 8px;background:#f1f5f9;border-radius:4px;"><code>pd_subtitle</code> — Subtitle</div>
                            <div style="font-size:12px;padding:4px 8px;background:#f1f5f9;border-radius:4px;"><code>pd_gallery</code> — Gallery JSON</div>
                            <div style="font-size:12px;padding:4px 8px;background:#f1f5f9;border-radius:4px;"><code>pd_datasheet</code> — Datasheet URL</div>
                            <div style="font-size:12px;padding:4px 8px;background:#f1f5f9;border-radius:4px;"><code>pd_custom_attrs</code> — Attributes</div>
                            <?php foreach (array_slice($pm_spec_fields, 0, 6) as $sf): ?>
                            <div style="font-size:12px;padding:4px 8px;background:#f1f5f9;border-radius:4px;"><code><?php echo esc_html($sf['key']); ?></code> — <?php echo esc_html($sf['label']); ?></div>
                            <?php endforeach; ?>
                            <?php if (count($pm_spec_fields) > 6): ?>
                            <div style="font-size:12px;padding:4px 8px;background:#fffbeb;border-radius:4px;color:#92400e;">+<?php echo count($pm_spec_fields) - 6; ?> fields อื่นๆ...</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="beg-code-example">
                        <strong>ตัวอย่าง: ดึงค่า Spec ด้วย Block Bindings API (WP 6.5+):</strong>
                        <pre><code>&lt;!-- wp:paragraph {
  "metadata": {
    "bindings": {
      "content": {
        "source": "core/post-meta",
        "args": { "key": "pd_inductance" }
      }
    }
  }
} --&gt;
&lt;p&gt;&lt;/p&gt;
&lt;!-- /wp:paragraph --&gt;</code></pre>
                    </div>
                    <div class="beg-code-example">
                        <strong>ตัวอย่าง: ดึงค่าด้วย PHP ใน Template:</strong>
                        <pre><code>// ดึง Subtitle ของสินค้าปัจจุบัน
$subtitle = get_post_meta(get_the_ID(), 'pd_subtitle', true);

// ดึง Gallery images
$gallery = json_decode(get_post_meta(get_the_ID(), 'pd_gallery', true), true);

// ดึง Specs ทั้งหมดแบบวนลูป
$spec_fields = pm_get_all_spec_fields();
foreach ($spec_fields as $sf) {
    $val = get_post_meta(get_the_ID(), $sf['key'], true);
    if ($val) echo $sf['label'] . ': ' . $val;
}</code></pre>
                    </div>
                </div>

                <!-- F. Spec Fields Manager -->
                <div class="beg-feature-card">
                    <div class="beg-feature-badge beg-badge-green">มีอยู่แล้ว ✓</div>
                    <h3>⚙️ Spec Fields Manager — จัดการฟิลด์ข้อมูลสินค้า</h3>
                    <p>เพิ่ม/ลบ/แก้ไข Specification Fields ได้ง่ายๆ ไม่ต้องเขียนโค้ด</p>
                    <div class="beg-how-to">
                        <strong>ฟีเจอร์:</strong>
                        <ul>
                            <li>✅ <strong><?php echo count($pm_spec_fields); ?> Active Fields</strong> — Inductance, Voltage, Dimensions, ฯลฯ</li>
                            <li>✅ <strong>เพิ่ม Custom Field</strong> — สร้างฟิลด์ใหม่ตามต้องการ</li>
                            <li>✅ <strong>ซ่อน/แสดง</strong> — ปิด Built-in fields ที่ไม่จำเป็น</li>
                            <li>✅ <strong>เปลี่ยนชื่อ Label</strong> — แก้ชื่อ field ได้</li>
                            <li>✅ <strong>เรียงลำดับ</strong> — Drag & Drop เรียงลำดับ spec fields</li>
                            <li>✅ <strong>ไอคอน</strong> — กำหนด Font Awesome / SVG ไอคอนให้แต่ละ field</li>
                        </ul>
                    </div>
                    <div class="beg-how-to">
                        <strong>วิธีเข้าถึง:</strong>
                        <ol>
                            <li>ไปที่ <strong>Product Manager</strong></li>
                            <li>เปิดฟอร์มสินค้า → แท็บ <strong>⚙️ Specs</strong></li>
                            <li>คลิก <strong>"⚙️ จัดการ Spec Fields"</strong> ด้านล่าง</li>
                        </ol>
                    </div>
                    <a href="<?php echo esc_url($admin_url . 'admin.php?page=product-manager'); ?>" class="beg-link">→ เปิด Product Manager</a>
                </div>

                <!-- G. Integration & API -->
                <div class="beg-feature-card">
                    <div class="beg-feature-badge beg-badge-green">มีอยู่แล้ว ✓</div>
                    <h3>🔌 REST API — เชื่อมต่อข้อมูลสินค้า</h3>
                    <p>ระบบ Product Manager มี REST API สำหรับเชื่อมต่อกับแอปภายนอก</p>
                    <div class="beg-how-to">
                        <strong>AJAX Endpoints (Admin):</strong>
                        <ul>
                            <li><code>pm_get_products</code> — ดึงรายการสินค้า (filter, search, paginate)</li>
                            <li><code>pm_get_product</code> — ดึงข้อมูลสินค้าเดียว</li>
                            <li><code>pm_save_product</code> — บันทึก/สร้างสินค้า</li>
                            <li><code>pm_get_categories</code> — ดึง Category tree</li>
                            <li><code>pm_save_category</code> — บันทึก Category</li>
                            <li><code>pm_get_spec_fields</code> — ดึง Spec Fields ทั้งหมด</li>
                        </ul>
                        <strong>WordPress REST API:</strong>
                        <ul>
                            <li><code>/wp-json/wp/v2/product</code> — CRUD สินค้า</li>
                            <li><code>/wp-json/wp/v2/product_category</code> — CRUD categories</li>
                            <li><code>/wp-json/kv/v1/rag-search</code> — AI ค้นหาสินค้า</li>
                        </ul>
                    </div>
                </div>

                <!-- H. Datasheet & Lead Gen -->
                <div class="beg-feature-card">
                    <div class="beg-feature-badge beg-badge-green">มีอยู่แล้ว ✓</div>
                    <h3>📥 Datasheet Download + Lead Generation</h3>
                    <p>ระบบดาวน์โหลด Datasheet พร้อมเก็บข้อมูล Lead อัตโนมัติ</p>
                    <div class="beg-how-to">
                        <strong>การทำงาน:</strong>
                        <ol>
                            <li>อัปโหลด PDF Datasheet ใน Product Manager → แท็บ Details</li>
                            <li>หน้าสินค้าจะแสดงปุ่ม <strong>"Download Datasheet"</strong></li>
                            <li>ผู้เยี่ยมชมกรอกชื่อ-อีเมลก่อนดาวน์โหลด → เก็บ Lead อัตโนมัติ</li>
                            <li>ดูรายงาน Lead ได้ที่ <strong>Manage → Datasheet Downloads</strong></li>
                        </ol>
                    </div>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:8px;">
                        <a href="<?php echo esc_url($admin_url . 'admin.php?page=datasheet-leads'); ?>" class="beg-link">→ ดู Datasheet Leads</a>
                        <a href="<?php echo esc_url($admin_url . 'admin.php?page=product-manager'); ?>" class="beg-link">→ จัดการ Datasheets ใน PM</a>
                    </div>
                </div>

            </div>

            <!-- Product Manager Workflow -->
            <div class="beg-feature-card" style="margin-top:20px;background:#fffbeb;border-color:#fde68a;">
                <h3>📋 สรุป: Workflow การแก้ไข Product ผ่าน Block Editor</h3>
                <div class="beg-workflow-steps">
                    <div class="beg-wf-step">
                        <div class="beg-wf-num">1</div>
                        <div>
                            <strong>จัดการข้อมูลสินค้า</strong>
                            <p>ไปที่ <a href="<?php echo esc_url($admin_url . 'admin.php?page=product-manager'); ?>">Product Manager</a> → เพิ่ม/แก้ไข/ลบ สินค้า, Categories, Specs, Gallery, Datasheet</p>
                        </div>
                    </div>
                    <div class="beg-wf-step">
                        <div class="beg-wf-num">2</div>
                        <div>
                            <strong>ออกแบบ Layout ใน Block Editor</strong>
                            <p>ไปที่ <strong>Appearance → Editor</strong> → แก้ไข Templates (single-product, archive) และ Template Parts (header, footer)</p>
                        </div>
                    </div>
                    <div class="beg-wf-step">
                        <div class="beg-wf-num">3</div>
                        <div>
                            <strong>ปรับแต่ง Global Styles</strong>
                            <p>ไปที่ <a href="<?php echo esc_url($admin_url . 'admin.php?page=my-theme-settings'); ?>">Theme Settings</a> → เปลี่ยนสี, ฟอนต์, Navigation — ทุกหน้าสินค้าเปลี่ยนตามทันที</p>
                        </div>
                    </div>
                    <div class="beg-wf-step">
                        <div class="beg-wf-num">4</div>
                        <div>
                            <strong>ดูผลลัพธ์</strong>
                            <p>เปิดหน้าเว็บ → ข้อมูลจาก DB + Layout จาก Block Editor = หน้าสินค้าแบบมืออาชีพ!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════ -->
        <!-- QUICK LINKS -->
        <!-- ═══════════════════════════════════════════ -->
        <div class="beg-section beg-quick-links-section">
            <h2>🔗 Quick Links — ลิงก์ไปหน้าที่ใช้บ่อย</h2>
            <div class="beg-quick-grid">
                <a href="<?php echo esc_url($site_editor); ?>" class="beg-quick-card" target="_blank">
                    <div class="beg-quick-icon">🏗️</div>
                    <strong>Site Editor (FSE)</strong>
                    <small>แก้ไขโครงสร้างเว็บทั้งหมด</small>
                </a>
                <a href="<?php echo esc_url($site_editor . '?path=%2Ftemplates'); ?>" class="beg-quick-card" target="_blank">
                    <div class="beg-quick-icon">📄</div>
                    <strong>Templates</strong>
                    <small>จัดการแม่แบบหน้าเว็บ</small>
                </a>
                <a href="<?php echo esc_url($site_editor . '?path=%2Fpatterns'); ?>" class="beg-quick-card" target="_blank">
                    <div class="beg-quick-icon">🧩</div>
                    <strong>Patterns</strong>
                    <small>Block Patterns & Template Parts</small>
                </a>
                <a href="<?php echo esc_url($admin_url . 'admin.php?page=my-theme-settings'); ?>" class="beg-quick-card">
                    <div class="beg-quick-icon">⚙️</div>
                    <strong>Theme Settings</strong>
                    <small>ตั้งค่าธีมทั้งหมด</small>
                </a>
                <a href="<?php echo esc_url($admin_url . 'admin.php?page=product-manager'); ?>" class="beg-quick-card">
                    <div class="beg-quick-icon">📦</div>
                    <strong>Product Manager</strong>
                    <small>จัดการสินค้า</small>
                </a>
                <a href="<?php echo esc_url($admin_url . 'edit.php?post_type=page'); ?>" class="beg-quick-card">
                    <div class="beg-quick-icon">📝</div>
                    <strong>Pages</strong>
                    <small>แก้ไขหน้าเพจด้วย Block Editor</small>
                </a>
                <a href="<?php echo esc_url($admin_url . 'upload.php'); ?>" class="beg-quick-card">
                    <div class="beg-quick-icon">🖼️</div>
                    <strong>Media Library</strong>
                    <small>จัดการรูปภาพและไฟล์</small>
                </a>
                <a href="<?php echo esc_url($admin_url . 'plugins.php'); ?>" class="beg-quick-card">
                    <div class="beg-quick-icon">🔌</div>
                    <strong>Plugins</strong>
                    <small>ติดตั้งปลั๊กอินเพิ่มเติม</small>
                </a>
            </div>
        </div>

        <!-- ═══════════════════════════════════════════ -->
        <!-- RECOMMENDED PLUGINS -->
        <!-- ═══════════════════════════════════════════ -->
        <div class="beg-section">
            <h2>🔌 ปลั๊กอินแนะนำสำหรับ Block Editor</h2>
            <p style="color:#64748b;margin-bottom:24px;">ปลั๊กอินเหล่านี้จะเพิ่มความสามารถให้ Block Editor ทำได้มากขึ้น</p>
            <div class="beg-plugin-recommend-grid">
                <?php
                $recommended = [
                    ['name' => 'Spectra', 'desc' => 'Block Library — Popup, Mega Menu, Accordion, Tabs, Star Rating, 30+ blocks', 'category' => 'Block Library', 'free' => true, 'slug' => 'ultimate-addons-for-gutenberg'],
                    ['name' => 'Stackable', 'desc' => 'Premium blocks — Advanced columns, Video Popup, Separator, 50+ blocks', 'category' => 'Block Library', 'free' => true, 'slug' => 'stackable-ultimate-gutenberg-blocks'],
                    ['name' => 'ACF PRO', 'desc' => 'Custom Fields — ฟิลด์ข้อมูลพิเศษ, Repeater, Gallery, Flexible Content', 'category' => 'Dynamic', 'free' => false, 'slug' => 'advanced-custom-fields'],
                    ['name' => 'Block Visibility', 'desc' => 'แสดง/ซ่อนบล็อกตามเงื่อนไข — User Role, Date/Time, URL Parameter', 'category' => 'Conditional', 'free' => true, 'slug' => 'block-visibility'],
                    ['name' => 'Yoast SEO', 'desc' => 'SEO — Meta Title, Description, Sitemap, Schema, Readability Analysis', 'category' => 'SEO', 'free' => true, 'slug' => 'wordpress-seo'],
                    ['name' => 'WPForms Lite', 'desc' => 'Form Builder — Drag & Drop, เชื่อม Mailchimp, Google Sheets', 'category' => 'Forms', 'free' => true, 'slug' => 'wpforms-lite'],
                    ['name' => 'ShortPixel', 'desc' => 'Image Optimization — บีบอัด, WebP, AVIF, Lazy Load', 'category' => 'Performance', 'free' => true, 'slug' => 'shortpixel-image-optimiser'],
                    ['name' => 'EditorsKit', 'desc' => 'เพิ่มฟีเจอร์ให้ Block Editor — Text Formatting, Block Guides, Copy/Paste Styles', 'category' => 'Editor Tools', 'free' => true, 'slug' => 'editorskit'],
                ];
                foreach ($recommended as $p): ?>
                <div class="beg-plugin-rcard">
                    <div class="beg-pr-top">
                        <span class="beg-pr-cat"><?php echo esc_html($p['category']); ?></span>
                        <span class="beg-pr-price"><?php echo $p['free'] ? '🆓 Free' : '💎 Premium'; ?></span>
                    </div>
                    <h4><?php echo esc_html($p['name']); ?></h4>
                    <p><?php echo esc_html($p['desc']); ?></p>
                    <a href="<?php echo esc_url($admin_url . 'plugin-install.php?s=' . urlencode($p['slug']) . '&tab=search&type=term'); ?>" class="beg-link">→ ติดตั้ง</a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div><!-- .beg-wrap -->

    <style>
    /* ═══ Block Editor Guide Styles ═══ */
    .beg-wrap { --beg-primary: <?php echo esc_attr($primary); ?>; --beg-bg: #f1f5f9; --beg-surface: #fff; --beg-border: #e2e8f0; --beg-text: #1e293b; --beg-muted: #64748b; --beg-radius: 12px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; font-size: 14px; color: var(--beg-text); max-width: 1200px; margin: 20px auto; padding: 0 20px; }
    .beg-wrap * { box-sizing: border-box; }

    /* Top Bar */
    .beg-topbar { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 28px; flex-wrap: wrap; }
    .beg-topbar h1 { margin: 0; font-size: 22px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
    .beg-dot { width: 10px; height: 10px; border-radius: 50%; background: var(--beg-primary); display: inline-block; }
    .beg-topbar-actions { display: flex; gap: 10px; flex-wrap: wrap; }
    .beg-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; text-decoration: none; cursor: pointer; transition: all .15s; border: none; }
    .beg-btn-primary { background: var(--beg-primary); color: #fff; }
    .beg-btn-primary:hover { opacity: 0.88; color: #fff; }
    .beg-btn-outline { background: var(--beg-surface); color: var(--beg-text); border: 1px solid var(--beg-border); }
    .beg-btn-outline:hover { border-color: var(--beg-primary); color: var(--beg-primary); }

    /* Stats Row */
    .beg-stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 14px; margin-bottom: 28px; }
    .beg-stat-card { background: var(--beg-surface); border: 1px solid var(--beg-border); border-radius: var(--beg-radius); padding: 20px; text-align: center; }
    .beg-stat-num { font-size: 28px; font-weight: 800; color: var(--beg-primary); }
    .beg-stat-label { font-size: 12px; color: var(--beg-muted); margin-top: 4px; }

    /* Plugin Status */
    .beg-plugin-status { background: var(--beg-surface); border: 1px solid var(--beg-border); border-radius: var(--beg-radius); padding: 20px 24px; margin-bottom: 28px; }
    .beg-plugin-status h3 { margin: 0 0 14px; font-size: 15px; }
    .beg-plugin-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 10px; }
    .beg-plugin-item { display: flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 8px; font-size: 13px; background: #f8fafc; border: 1px solid var(--beg-border); }
    .beg-plugin-item.active { background: #f0fdf4; border-color: #bbf7d0; }
    .beg-plugin-item.inactive { background: #fef2f2; border-color: #fecaca; }
    .beg-plugin-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
    .beg-plugin-item.active .beg-plugin-dot { background: #22c55e; }
    .beg-plugin-item.inactive .beg-plugin-dot { background: #ef4444; }
    .beg-plugin-badge { margin-left: auto; font-size: 11px; font-weight: 600; white-space: nowrap; }
    .beg-plugin-item.active .beg-plugin-badge { color: #16a34a; }
    .beg-plugin-item.inactive .beg-plugin-badge { color: #dc2626; }

    /* Sections */
    .beg-section { margin-bottom: 36px; }
    .beg-section h2 { margin: 0 0 4px; font-size: 18px; font-weight: 700; }
    .beg-section > p { color: var(--beg-muted); margin: 0 0 20px; }
    .beg-section-header { display: flex; gap: 16px; align-items: flex-start; margin-bottom: 24px; padding: 20px 24px; background: linear-gradient(135deg, #eff6ff 0%, #f0fdf4 100%); border-radius: var(--beg-radius); border: 1px solid #dbeafe; }
    .beg-section-icon { font-size: 32px; flex-shrink: 0; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; background: var(--beg-surface); border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
    .beg-section-header h2 { margin: 0 0 6px; }
    .beg-section-header p { margin: 0; color: var(--beg-muted); font-size: 13px; }

    /* Feature Grid */
    .beg-feature-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 18px; }
    .beg-feature-card { background: var(--beg-surface); border: 1px solid var(--beg-border); border-radius: var(--beg-radius); padding: 24px; position: relative; transition: box-shadow .2s; }
    .beg-feature-card:hover { box-shadow: 0 4px 20px rgba(0,0,0,.08); }
    .beg-feature-card h3 { margin: 8px 0 10px; font-size: 16px; }
    .beg-feature-card > p { color: var(--beg-muted); font-size: 13px; margin: 0 0 16px; line-height: 1.6; }
    .beg-feature-badge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; background: #dbeafe; color: #1d4ed8; }
    .beg-badge-green { background: #dcfce7; color: #15803d; }
    .beg-badge-blue { background: #dbeafe; color: #1d4ed8; }
    .beg-badge-purple { background: #f3e8ff; color: #7c3aed; }
    .beg-badge-orange { background: #fff7ed; color: #c2410c; }

    /* How-to */
    .beg-how-to { background: #f8fafc; border: 1px solid var(--beg-border); border-radius: 8px; padding: 16px; margin-bottom: 14px; }
    .beg-how-to strong { font-size: 12.5px; color: var(--beg-text); display: block; margin-bottom: 8px; }
    .beg-how-to ol, .beg-how-to ul { margin: 0; padding-left: 20px; }
    .beg-how-to li { font-size: 13px; line-height: 1.7; color: #475569; }
    .beg-how-to li strong { display: inline; }
    .beg-how-to code { background: #e2e8f0; padding: 1px 5px; border-radius: 4px; font-size: 12px; }

    /* Tags */
    .beg-tag { display: inline-block; padding: 3px 10px; background: #eff6ff; color: #2563eb; border-radius: 6px; font-size: 12px; font-weight: 500; margin: 2px 4px 2px 0; }

    /* Links */
    .beg-link { display: inline-block; color: var(--beg-primary); font-size: 13px; font-weight: 600; text-decoration: none; margin-top: 8px; }
    .beg-link:hover { text-decoration: underline; }

    /* Tips, Warnings, Success, Info */
    .beg-tip { background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 12px 16px; font-size: 13px; color: #92400e; margin-bottom: 14px; }
    .beg-warning { background: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px; padding: 10px 16px; font-size: 13px; color: #c2410c; margin-top: 10px; }
    .beg-success { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 10px 16px; font-size: 13px; color: #15803d; margin-top: 10px; }
    .beg-info { background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 10px 16px; font-size: 13px; color: #1d4ed8; margin-top: 10px; }
    .beg-warning a, .beg-success a, .beg-info a { color: inherit; text-decoration: underline; }

    /* Code Example */
    .beg-code-example { background: #1e293b; border-radius: 8px; padding: 16px; margin-bottom: 14px; overflow-x: auto; }
    .beg-code-example strong { color: #94a3b8; font-size: 12px; display: block; margin-bottom: 8px; }
    .beg-code-example pre { margin: 0; }
    .beg-code-example code { color: #e2e8f0; font-size: 12px; font-family: 'SF Mono', 'Fira Code', monospace; line-height: 1.6; white-space: pre-wrap; }

    /* Templates */
    .beg-template-list { margin-bottom: 14px; }
    .beg-template-list > strong { font-size: 12.5px; display: block; margin-bottom: 10px; }
    .beg-template-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
    .beg-template-item { display: flex; align-items: center; gap: 10px; padding: 10px 12px; background: #f8fafc; border: 1px solid var(--beg-border); border-radius: 8px; }
    .beg-template-icon { font-size: 20px; }
    .beg-template-item strong { font-size: 13px; display: block; }
    .beg-template-item small { font-size: 11px; color: var(--beg-muted); }

    /* Palette Swatches */
    .beg-palette-preview { margin-bottom: 14px; }
    .beg-palette-preview strong { font-size: 12.5px; display: block; margin-bottom: 8px; }
    .beg-palette-swatches { display: flex; gap: 6px; flex-wrap: wrap; }
    .beg-swatch { width: 32px; height: 32px; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 0 0 1px rgba(0,0,0,.15); cursor: help; }

    /* Options List */
    .beg-option-list { display: flex; flex-direction: column; gap: 10px; margin-top: 8px; }
    .beg-option { display: flex; gap: 12px; align-items: flex-start; }
    .beg-option-rank { width: 24px; height: 24px; border-radius: 50%; background: var(--beg-primary); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0; margin-top: 2px; }
    .beg-option strong { font-size: 13px; }
    .beg-option p { margin: 2px 0 0; font-size: 12px; color: var(--beg-muted); }

    /* Quick Links */
    .beg-quick-links-section h2 { margin-bottom: 16px; }
    .beg-quick-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 14px; }
    .beg-quick-card { display: flex; flex-direction: column; align-items: center; text-align: center; padding: 20px 16px; background: var(--beg-surface); border: 1px solid var(--beg-border); border-radius: var(--beg-radius); text-decoration: none; color: var(--beg-text); transition: all .2s; }
    .beg-quick-card:hover { border-color: var(--beg-primary); box-shadow: 0 4px 16px rgba(37,99,235,.12); transform: translateY(-2px); color: var(--beg-text); }
    .beg-quick-icon { font-size: 28px; margin-bottom: 8px; }
    .beg-quick-card strong { font-size: 13px; line-height: 1.4; }
    .beg-quick-card small { font-size: 11px; color: var(--beg-muted); margin-top: 4px; }

    /* Recommended Plugins */
    .beg-plugin-recommend-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 14px; }
    .beg-plugin-rcard { background: var(--beg-surface); border: 1px solid var(--beg-border); border-radius: var(--beg-radius); padding: 20px; }
    .beg-pr-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .beg-pr-cat { font-size: 11px; font-weight: 700; color: var(--beg-primary); background: #eff6ff; padding: 2px 8px; border-radius: 999px; }
    .beg-pr-price { font-size: 11px; font-weight: 600; }
    .beg-plugin-rcard h4 { margin: 0 0 6px; font-size: 14px; }
    .beg-plugin-rcard p { margin: 0 0 8px; font-size: 12.5px; color: var(--beg-muted); line-height: 1.5; }

    /* Current Status */
    .beg-current-status { margin-bottom: 14px; }
    .beg-current-status strong { font-size: 12.5px; display: inline; }

    /* Architecture Flow */
    .beg-arch-flow { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; justify-content: center; margin-top: 8px; }
    .beg-arch-node { background: var(--beg-surface); border: 2px solid var(--beg-border); border-radius: 10px; padding: 14px 18px; text-align: center; min-width: 160px; flex: 1; max-width: 220px; }
    .beg-arch-node strong { display: block; font-size: 13px; margin-bottom: 4px; }
    .beg-arch-node small { display: block; font-size: 11px; color: var(--beg-muted); margin-bottom: 6px; }
    .beg-arch-node code { display: block; font-size: 11px; background: #f1f5f9; padding: 3px 8px; border-radius: 4px; color: var(--beg-primary); }
    .beg-arch-admin { border-color: #fbbf24; background: #fffbeb; }
    .beg-arch-db { border-color: #60a5fa; background: #eff6ff; }
    .beg-arch-template { border-color: #a78bfa; background: #f5f3ff; }
    .beg-arch-front { border-color: #34d399; background: #ecfdf5; }
    .beg-arch-arrow { font-size: 24px; color: #94a3b8; font-weight: 700; flex-shrink: 0; }

    /* Workflow Steps */
    .beg-workflow-steps { display: flex; flex-direction: column; gap: 16px; margin-top: 16px; }
    .beg-wf-step { display: flex; gap: 16px; align-items: flex-start; }
    .beg-wf-num { width: 36px; height: 36px; border-radius: 50%; background: var(--beg-primary); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; flex-shrink: 0; }
    .beg-wf-step strong { display: block; font-size: 14px; margin-bottom: 2px; }
    .beg-wf-step p { margin: 0; font-size: 13px; color: var(--beg-muted); line-height: 1.6; }
    .beg-wf-step a { color: var(--beg-primary); }

    /* Responsive */
    @media (max-width: 782px) {
        .beg-feature-grid { grid-template-columns: 1fr; }
        .beg-template-grid { grid-template-columns: 1fr; }
        .beg-quick-grid { grid-template-columns: repeat(2, 1fr); }
        .beg-topbar { flex-direction: column; align-items: flex-start; }
        .beg-arch-flow { flex-direction: column; }
        .beg-arch-arrow { transform: rotate(90deg); }
        .beg-arch-node { max-width: 100%; }
    }
    </style>
    <?php
}

// Alias for backward compat (when called as standalone page)
function kv_block_editor_guide_page() {
    kv_block_editor_guide_content();
}
