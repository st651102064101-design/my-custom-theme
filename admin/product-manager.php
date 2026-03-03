<?php
/**
 * Product Manager — World-class Admin CRUD
 * 
 * SPA-like admin page for managing Products & Categories
 * Handles: AJAX endpoints, admin page rendering, asset enqueuing
 */

if (!defined('ABSPATH')) exit;

function pm_can_manage_products() {
    return current_user_can('edit_posts');
}

// ============================================
// 1. ADMIN MENU  (submenu under parent 'kv-manage')
// ============================================
add_action('admin_menu', function() {
    add_submenu_page(
        'kv-manage',
        'Product Manager',
        '📦 Product Manager',
        'edit_posts',
        'product-manager',
        'pm_render_admin_page'
    );
}, 30);

// Fallback: register page hook directly so admin.php?page=product-manager always resolves.
add_action('admin_menu', function() {
    add_menu_page(
        'Product Manager',
        'Product Manager',
        'edit_posts',
        'product-manager',
        'pm_render_admin_page',
        'dashicons-products',
        58
    );
    remove_menu_page('product-manager');
}, 31);

// ============================================
// 2. ENQUEUE ADMIN ASSETS
// ============================================
add_action('admin_enqueue_scripts', function($hook) {
    if (!in_array($hook, ['kv-manage_page_product-manager', 'toplevel_page_product-manager'], true)) return;

    wp_enqueue_media();
    wp_enqueue_editor();

    wp_enqueue_style(
        'pm-admin-css',
        get_template_directory_uri() . '/assets/css/admin-product-manager.css',
        [],
        filemtime(get_template_directory() . '/assets/css/admin-product-manager.css')
    );

    // Font Awesome 4 for spec icon previews
    wp_enqueue_style(
        'font-awesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css',
        [],
        '4.7.0'
    );

    wp_enqueue_script(
        'pm-admin-js',
        get_template_directory_uri() . '/assets/js/admin-product-manager.js',
        ['jquery'],
        filemtime(get_template_directory() . '/assets/js/admin-product-manager.js'),
        true
    );

    // Override --pm-primary CSS vars with theme's 30% secondary color
    $pm_color = get_option('theme_primary_color', '#0056d6');
    $pm_hex   = ltrim($pm_color, '#');
    if (strlen($pm_hex) === 3) $pm_hex = $pm_hex[0].$pm_hex[0].$pm_hex[1].$pm_hex[1].$pm_hex[2].$pm_hex[2];
    $pm_r = hexdec(substr($pm_hex,0,2)); $pm_g = hexdec(substr($pm_hex,2,2)); $pm_b = hexdec(substr($pm_hex,4,2));
    $pm_dark  = sprintf('#%02x%02x%02x', max(0,round($pm_r*0.82)), max(0,round($pm_g*0.82)), max(0,round($pm_b*0.82)));
    $pm_light = sprintf('#%02x%02x%02x', min(255,$pm_r+round((255-$pm_r)*0.92)), min(255,$pm_g+round((255-$pm_g)*0.92)), min(255,$pm_b+round((255-$pm_b)*0.92)));
    wp_add_inline_style('pm-admin-css', ":root{--pm-primary:{$pm_color};--pm-primary-dark:{$pm_dark};--pm-primary-light:{$pm_light};}");

    wp_localize_script('pm-admin-js', 'PM', [
        'ajax'       => admin_url('admin-ajax.php'),
        'nonce'      => wp_create_nonce('pm_nonce'),
        'theme'      => get_template_directory_uri(),
        'specFields' => pm_get_all_spec_fields(),
        'specIcons'  => pm_get_spec_icons(),
    ]);
});

// ============================================
// 3. RENDER ADMIN PAGE
// ============================================
function pm_render_admin_page() {
    if (!pm_can_manage_products()) return;

    // Get stats
    $total_products   = wp_count_posts('product')->publish;
    $total_draft      = wp_count_posts('product')->draft;
    $total_categories = wp_count_terms(['taxonomy' => 'product_category', 'hide_empty' => false]);

    // Get recent products
    $recent = get_posts([
        'post_type'      => 'product',
        'posts_per_page' => 5,
        'orderby'        => 'modified',
        'order'          => 'DESC',
        'post_status'    => ['publish', 'draft'],
    ]);
    ?>
    <div id="pm-app" class="pm-wrap">
        <!-- Header -->
        <header class="pm-header">
            <div class="pm-header-left">
                <h1>
                    <span class="pm-logo-icon">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                    </span>
                    Product Manager
                </h1>
            </div>
            <div class="pm-header-right">
                <?php
                $pm_page = get_page_by_path('product-manager');
                if ($pm_page) :
                ?>
                <a href="<?php echo get_permalink($pm_page); ?>" class="pm-btn pm-btn-sm pm-btn-ghost" target="_blank" title="ดูหน้าเว็บ" style="gap:6px;text-decoration:none;">
                    🌐 ดูหน้าเว็บ
                </a>
                <a href="<?php echo admin_url('post.php?post=' . $pm_page->ID . '&action=edit'); ?>" class="pm-btn pm-btn-sm pm-btn-ghost" title="แก้ไขใน Block Editor" style="gap:6px;text-decoration:none;">
                    ✏️ Block Editor
                </a>
                <?php endif; ?>
                <div class="pm-stats">
                    <div class="pm-stat">
                        <span class="pm-stat-num"><?php echo intval($total_products); ?></span>
                        <span class="pm-stat-label">Published</span>
                    </div>
                    <div class="pm-stat">
                        <span class="pm-stat-num"><?php echo intval($total_draft); ?></span>
                        <span class="pm-stat-label">Drafts</span>
                    </div>
                    <div class="pm-stat">
                        <span class="pm-stat-num"><?php echo intval($total_categories); ?></span>
                        <span class="pm-stat-label">Categories</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Layout -->
        <div class="pm-layout">
            <!-- Sidebar: Category Tree -->
            <aside class="pm-sidebar" id="pm-sidebar">
                <div class="pm-sidebar-header">
                    <h3>📁 Categories</h3>
                    <button class="pm-btn pm-btn-sm pm-btn-primary" id="pm-add-cat-btn" title="เพิ่ม Category">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                </div>
                <div class="pm-sidebar-search">
                    <input type="text" id="pm-cat-search" placeholder="🔍 ค้นหา category...">
                </div>
                <div class="pm-cat-tree" id="pm-cat-tree">
                    <div class="pm-loading">กำลังโหลด...</div>
                </div>
                <div class="pm-sidebar-footer">
                    <button class="pm-btn pm-btn-sm pm-btn-ghost" id="pm-expand-all">Expand All</button>
                    <button class="pm-btn pm-btn-sm pm-btn-ghost" id="pm-collapse-all">Collapse All</button>
                </div>
            </aside>

            <!-- Main Content -->
            <main class="pm-main">
                <!-- Toolbar -->
                <div class="pm-toolbar">
                    <div class="pm-toolbar-left">
                        <div class="pm-search-box">
                            <svg class="pm-search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input type="text" id="pm-search" placeholder="ค้นหาสินค้า... (ชื่อ)">
                            <kbd class="pm-kbd">⌘K</kbd>
                        </div>
                        <select id="pm-status-filter" class="pm-select">
                            <option value="">All Status</option>
                            <option value="publish">Published</option>
                            <option value="draft">Draft</option>
                        </select>
                    </div>
                    <div class="pm-toolbar-right">
                        <div class="pm-view-toggle">
                            <button class="pm-view-btn active" data-view="table" title="Table View">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                            </button>
                            <button class="pm-view-btn" data-view="grid" title="Grid View">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                            </button>
                        </div>
                        <button class="pm-btn pm-btn-primary" id="pm-add-product-btn">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            เพิ่มสินค้าใหม่
                        </button>
                    </div>
                </div>

                <!-- Breadcrumb -->
                <div class="pm-breadcrumb" id="pm-breadcrumb">
                    <span class="pm-breadcrumb-item active">All Products</span>
                </div>

                <!-- Bulk Actions Bar (hidden by default) -->
                <div class="pm-bulk-bar" id="pm-bulk-bar" style="display:none;">
                    <span class="pm-bulk-count"><span id="pm-selected-count">0</span> selected</span>
                    <button class="pm-btn pm-btn-sm pm-btn-ghost" id="pm-bulk-move">📁 Move</button>
                    <button class="pm-btn pm-btn-sm pm-btn-danger" id="pm-bulk-delete">🗑️ Delete</button>
                    <button class="pm-btn pm-btn-sm pm-btn-ghost" id="pm-bulk-cancel">Cancel</button>
                </div>

                <!-- Product List -->
                <div class="pm-content" id="pm-content">
                    <div class="pm-loading">กำลังโหลดสินค้า...</div>
                </div>

                <!-- Pagination -->
                <div class="pm-pagination" id="pm-pagination"></div>
            </main>
        </div>

        <!-- Slide-over Panel: Product Form -->
        <div class="pm-overlay" id="pm-overlay"></div>
        <div class="pm-panel" id="pm-panel">
            <div class="pm-panel-header">
                <h2 id="pm-panel-title">เพิ่มสินค้าใหม่</h2>
                <button class="pm-panel-close" id="pm-panel-close">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="pm-panel-body">
                <form id="pm-product-form" autocomplete="off">
                    <input type="hidden" id="pm-edit-id" value="">

                    <!-- Tab Nav -->
                    <div class="pm-form-tabs">
                        <button type="button" class="pm-form-tab active" data-tab="general">📋 General</button>
                        <button type="button" class="pm-form-tab" data-tab="details">📝 Details</button>
                        <button type="button" class="pm-form-tab" data-tab="gallery">🖼️ Gallery</button>
                        <button type="button" class="pm-form-tab" data-tab="specs">⚙️ Specs</button>
                    </div>

                    <!-- Tab: General -->
                    <div class="pm-tab-content active" id="pm-tab-general">
                        <div class="pm-form-group">
                            <label class="pm-label required">ชื่อสินค้า</label>
                            <input type="text" id="pm-field-title" class="pm-input" placeholder="เช่น CMC-2012 Series" required>
                        </div>
                        <div class="pm-form-group">
                            <label class="pm-label">Subtitle</label>
                            <input type="text" id="pm-field-subtitle" class="pm-input" placeholder="คำอธิบายสั้น เช่น High Performance Common Mode Choke">
                        </div>
                        <div class="pm-form-group">
                            <label class="pm-label">Status</label>
                            <select id="pm-field-status" class="pm-input">
                                <option value="publish">✅ Published</option>
                                <option value="draft">📝 Draft</option>
                            </select>
                        </div>
                        <div class="pm-form-group">
                            <label class="pm-label">Category</label>
                            <select id="pm-field-category" class="pm-input">
                                <option value="">— Select Category —</option>
                            </select>
                        </div>
                        <div class="pm-form-group">
                            <label class="pm-label">Description <small>(รองรับ Rich Text: หัวข้อ, bullet, ตัวหนา, ลิงก์)</small></label>
                            <div id="pm-editor-wrap" style="margin-top:6px;">
                                <?php
                                wp_editor('', 'pm-field-content', [
                                    'textarea_name' => 'content',
                                    'textarea_rows' => 10,
                                    'media_buttons' => true,
                                    'teeny'         => false,
                                    'quicktags'     => true,
                                    'tinymce'       => [
                                        'toolbar1' => 'formatselect,bold,italic,underline,|,bullist,numlist,|,link,unlink,|,forecolor,|,alignleft,aligncenter,|,undo,redo,|,removeformat',
                                        'toolbar2' => '',
                                        'block_formats' => 'Paragraph=p;Heading 2=h2;Heading 3=h3;Heading 4=h4',
                                    ],
                                ]);
                                ?>
                            </div>
                        </div>

                        <!-- Per-product custom attributes -->
                        <div style="margin-top:20px;padding-top:16px;border-top:2px solid #e2e8f0;">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                                <label class="pm-label" style="margin:0;font-weight:700;color:#1e293b;">📝 Custom Attributes</label>
                                <button type="button" class="pm-btn pm-btn-sm pm-btn-primary" id="pm-add-attr-btn" style="padding:4px 12px;font-size:.8rem;">+ เพิ่ม</button>
                            </div>
                            <p class="pm-helper-text" style="margin-bottom:10px;font-size:.78rem;">เพิ่ม Attribute เฉพาะสินค้านี้ — เลือก Text (พิมพ์เอง) หรือ Select (ตัวเลือก)</p>
                            <div id="pm-product-attrs-wrap" style="display:flex;flex-direction:column;gap:8px;">
                                <!-- JS renders rows here -->
                            </div>
                            <input type="hidden" id="pm-field-custom-attrs" value="[]">
                        </div>
                    </div>

                    <!-- Tab: Details (for datasheet file) -->
                    <div class="pm-tab-content" id="pm-tab-details">
                        <p class="pm-helper-text">อัปโหลดไฟล์ Datasheet สำหรับสินค้านี้ (PDF)</p>

                        <div class="pm-form-group">
                            <label class="pm-label">Datasheet File</label>
                            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                                <input type="url" id="pm-field-datasheet" class="pm-input" placeholder="https://.../datasheet.pdf" style="flex:1;min-width:260px;">
                                <button type="button" class="pm-btn pm-btn-sm" id="pm-datasheet-upload-btn">📁 Upload</button>
                                <button type="button" class="pm-btn pm-btn-sm pm-btn-danger" id="pm-datasheet-clear-btn">✕ Clear</button>
                            </div>
                            <div id="pm-datasheet-preview" class="pm-helper-text" style="margin-top:8px;"></div>
                        </div>
                    </div>

                    <!-- Tab: Gallery -->
                    <div class="pm-tab-content" id="pm-tab-gallery">
                        <p class="pm-helper-text">อัปโหลดรูปสินค้าได้เป็นจำนวน — รูปแรกจะเป็นรูปหลัก</p>
                        <div class="pm-gallery-grid" id="pm-gallery-grid">
                            <!-- ไดนามิก generate จาก JS -->
                        </div>
                        <button type="button" class="pm-btn pm-btn-sm pm-btn-ghost" id="pm-add-gallery-btn" style="margin-top:12px;">+ เพิ่มรูป</button>
                        <input type="hidden" id="pm-field-gallery" value="">
                    </div>

                    <!-- Tab: Specs -->
                    <div class="pm-tab-content" id="pm-tab-specs">
                        <p class="pm-helper-text">กรอกข้อมูล Specifications ของสินค้า - เว้นว่างได้หากไม่มีข้อมูล</p>
                        <div id="pm-specs-fields-wrap">
                            <!-- Dynamically rendered from PM.specFields by JS -->
                        </div>

                        <div style="margin-top:16px;padding-top:16px;border-top:1px dashed #e2e8f0;">
                            <button type="button" class="pm-btn pm-btn-sm pm-btn-ghost" id="pm-icons-settings-btn" style="gap:6px;width:100%;justify-content:center;">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                                ⚙️ จัดการ Spec Fields
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="pm-panel-footer">
                <button class="pm-btn pm-btn-ghost" id="pm-panel-cancel">ยกเลิก</button>
                <button class="pm-btn pm-btn-primary pm-btn-lg" id="pm-save-product">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    บันทึก
                </button>
            </div>
        </div>

        <!-- Category Modal -->
        <div class="pm-modal-overlay" id="pm-cat-modal-overlay"></div>
        <div class="pm-modal" id="pm-cat-modal">
            <div class="pm-modal-header">
                <h3 id="pm-cat-modal-title">เพิ่ม Category</h3>
                <button class="pm-modal-close" id="pm-cat-modal-close">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="pm-modal-body">
                <form id="pm-cat-form" autocomplete="off">
                    <input type="hidden" id="pm-cat-edit-id" value="">
                    <div class="pm-form-group">
                        <label class="pm-label required">ชื่อ Category</label>
                        <input type="text" id="pm-cat-name" class="pm-input" placeholder="เช่น Flyback Transformers" required>
                    </div>
                    <div class="pm-form-group">
                        <label class="pm-label">Parent Category</label>
                        <select id="pm-cat-parent" class="pm-input">
                            <option value="0">— None (Top Level) —</option>
                        </select>
                    </div>
                    <!-- hidden fields to preserve data on save -->
                    <textarea id="pm-cat-desc" class="pm-input pm-textarea" style="display:none;"></textarea>
                    <textarea id="pm-cat-desc-long" class="pm-input pm-textarea" style="display:none;"></textarea>
                    <div id="pm-cat-specs-wrap" style="display:none;"></div>
                    <div class="pm-form-group">
                        <label class="pm-label">รูปภาพ Category</label>
                        <div class="pm-cat-image-wrap">
                            <div id="pm-cat-image-preview" class="pm-cat-img-preview"></div>
                            <input type="hidden" id="pm-cat-image" value="">
                            <button type="button" class="pm-btn pm-btn-sm pm-btn-ghost" id="pm-cat-image-btn">📷 Choose Image</button>
                            <button type="button" class="pm-btn pm-btn-sm pm-btn-ghost" id="pm-cat-image-remove" style="color:#ef4444;display:none;">✕ Remove</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="pm-modal-footer">
                <button class="pm-btn pm-btn-ghost" id="pm-cat-cancel">ยกเลิก</button>
                <button class="pm-btn pm-btn-primary" id="pm-cat-save">💾 บันทึก</button>
            </div>
        </div>

        <!-- Move Category Modal -->
        <div class="pm-modal-overlay" id="pm-move-modal-overlay"></div>
        <div class="pm-modal" id="pm-move-modal">
            <div class="pm-modal-header">
                <h3>📁 Move Products to Category</h3>
                <button class="pm-modal-close" id="pm-move-modal-close">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="pm-modal-body">
                <select id="pm-move-cat-select" class="pm-input">
                    <option value="">— Select Category —</option>
                </select>
            </div>
            <div class="pm-modal-footer">
                <button class="pm-btn pm-btn-ghost" id="pm-move-cancel">ยกเลิก</button>
                <button class="pm-btn pm-btn-primary" id="pm-move-confirm">📁 Move</button>
            </div>
        </div>

        <!-- Spec Fields Manager Modal -->
        <div class="pm-modal-overlay" id="pm-icons-modal-overlay"></div>
        <div class="pm-modal" id="pm-icons-modal" style="max-width:660px;max-height:85vh;display:flex;flex-direction:column;">
            <div class="pm-modal-header">
                <h3>⚙️ Spec Fields Manager</h3>
                <button class="pm-modal-close" id="pm-icons-modal-close">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>
            <div class="pm-modal-body" style="overflow-y:auto;flex:1;padding-bottom:0;">
                <p class="pm-helper-text" style="margin-bottom:16px;">
                    กำหนดไอคอนสำหรับ field มาตรฐาน และเพิ่ม custom field ใหม่ได้ที่ด้านล่าง<br>
                    <strong>FA:</strong> ใส่ Font Awesome 4 class เช่น <code>fa fa-bolt</code> &nbsp;
                    <strong>SVG:</strong> วาง &lt;svg&gt;...&lt;/svg&gt; โดยตรง
                </p>
                <!-- Fields list — rendered by JS -->
                <div id="pm-icons-fields" style="display:flex;flex-direction:column;gap:8px;margin-bottom:16px;"></div>
                <!-- Add custom field -->
                <div style="border:2px dashed #e2e8f0;border-radius:8px;padding:12px 14px;background:#f8fafc;">
                    <p style="font-size:.8rem;font-weight:600;color:#475569;margin:0 0 8px;">+ เพิ่ม Custom Field ใหม่</p>
                    <div style="display:grid;grid-template-columns:1fr auto;gap:8px;align-items:center;">
                        <input type="text" id="pm-new-field-label" class="pm-input" placeholder="ชื่อ field เช่น Power Rating" style="font-size:.875rem;">
                        <button type="button" class="pm-btn pm-btn-primary pm-btn-sm" id="pm-add-custom-field-btn" style="white-space:nowrap;">+ Add Field</button>
                    </div>
                </div>
            </div>
            <div class="pm-modal-footer">
                <button class="pm-btn pm-btn-ghost" id="pm-icons-cancel">ยกเลิก</button>
                <button class="pm-btn pm-btn-primary" id="pm-icons-save">💾 บันทึก Spec Fields</button>
            </div>
        </div>

        <!-- Toast Container -->
        <div class="pm-toast-container" id="pm-toast-container"></div>

        <!-- Delete Confirmation -->
        <div class="pm-modal-overlay" id="pm-delete-overlay"></div>
        <div class="pm-modal pm-modal-sm" id="pm-delete-modal">
            <div class="pm-modal-header pm-modal-header-danger">
                <h3>⚠️ ยืนยันการลบ</h3>
            </div>
            <div class="pm-modal-body">
                <p id="pm-delete-message">คุณต้องการลบข้อมูลนี้หรือไม่?</p>
            </div>
            <div class="pm-modal-footer">
                <button class="pm-btn pm-btn-ghost" id="pm-delete-cancel">ยกเลิก</button>
                <button class="pm-btn pm-btn-danger" id="pm-delete-confirm">🗑️ ลบ</button>
            </div>
        </div>
    </div>
    <?php
}

// ============================================
// 4. AJAX HANDLERS
// ============================================

/**
 * Get categories tree
 */
add_action('wp_ajax_pm_get_categories', function() {
    check_ajax_referer('pm_nonce', 'nonce');

    $terms = get_terms([
        'taxonomy'   => 'product_category',
        'hide_empty' => false,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ]);

    $tree = [];
    $map  = [];

    if ($terms && !is_wp_error($terms)) {
        foreach ($terms as $t) {
            $count = (new WP_Query([
                'post_type'      => 'product',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'post_status'    => ['publish', 'draft'],
                'tax_query'      => [[
                    'taxonomy'         => 'product_category',
                    'field'            => 'term_id',
                    'terms'            => $t->term_id,
                    'include_children' => true,
                ]],
            ]))->found_posts;

            $map[$t->term_id] = [
                'id'        => $t->term_id,
                'name'      => $t->name,
                'slug'      => $t->slug,
                'parent'    => $t->parent,
                'desc'      => get_term_meta($t->term_id, 'cat_description', true) ?: $t->description,
                'desc_long' => get_term_meta($t->term_id, 'cat_description_long', true),
                'specs'     => get_term_meta($t->term_id, 'cat_specs', true) ?: '[]',
                'image'     => get_term_meta($t->term_id, 'cat_image', true),
                'count'     => $count,
                'children'  => [],
            ];
        }

        foreach ($map as $id => &$node) {
            if ($node['parent'] && isset($map[$node['parent']])) {
                $map[$node['parent']]['children'][] = &$node;
            } else {
                $tree[] = &$node;
            }
        }
    }

    // Total product count (all, regardless of category assignment)
    $total_all = (new WP_Query([
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'post_status'    => ['publish', 'draft'],
    ]))->found_posts;

    wp_send_json_success([
        'tree'  => $tree,
        'total' => $total_all,
    ]);
});

/**
 * Get products (paginated, filterable)
 */
add_action('wp_ajax_pm_get_products', function() {
    check_ajax_referer('pm_nonce', 'nonce');

    $page     = max(1, intval($_POST['page'] ?? 1));
    $per_page = 20;
    $search   = sanitize_text_field($_POST['search'] ?? '');
    $cat_id   = intval($_POST['category'] ?? 0);
    $status   = sanitize_text_field($_POST['status'] ?? '');

    $args = [
        'post_type'      => 'product',
        'posts_per_page' => $per_page,
        'paged'          => $page,
        'orderby'        => 'modified',
        'order'          => 'DESC',
        'post_status'    => $status ?: ['publish', 'draft'],
    ];

    if ($search) {
        $args['s'] = $search;
    }

    if ($cat_id) {
        $args['tax_query'] = [[
            'taxonomy'         => 'product_category',
            'field'            => 'term_id',
            'terms'            => $cat_id,
            'include_children' => true,
        ]];
    }

    $query = new WP_Query($args);
    $products = [];

    foreach ($query->posts as $p) {
        $terms = wp_get_object_terms($p->ID, 'product_category');
        $cat_name = '';
        $cat_id_val = 0;
        if ($terms && !is_wp_error($terms) && !empty($terms)) {
            $cat_name = $terms[0]->name;
            $cat_id_val = $terms[0]->term_id;
        }

        $gallery_json = get_post_meta($p->ID, 'pd_gallery', true);
        $gallery = $gallery_json ? json_decode($gallery_json, true) : [];
        $image = (!empty($gallery) && is_array($gallery)) ? $gallery[0] : '';
        if (!$image && has_post_thumbnail($p->ID)) {
            $image = get_the_post_thumbnail_url($p->ID, 'thumbnail');
        }

        $products[] = [
            'id'        => $p->ID,
            'title'     => $p->post_title,
            'slug'      => $p->post_name,
            'status'    => $p->post_status,
            'date'      => $p->post_date,
            'modified'  => $p->post_modified,
            'sku'       => get_post_meta($p->ID, 'pd_sku', true),
            'subtitle'  => get_post_meta($p->ID, 'pd_subtitle', true),
            'image'     => $image,
            'cat_name'  => $cat_name,
            'cat_id'    => $cat_id_val,
            'permalink' => get_permalink($p->ID),
        ];
    }

    wp_send_json_success([
        'products'    => $products,
        'total'       => $query->found_posts,
        'total_pages' => $query->max_num_pages,
        'page'        => $page,
        'per_page'    => $per_page,
    ]);
});

/**
 * Get single product for editing
 */
add_action('wp_ajax_pm_get_product', function() {
    check_ajax_referer('pm_nonce', 'nonce');

    $id = intval($_POST['id'] ?? 0);
    if (!$id) wp_send_json_error(['message' => 'Invalid product ID']);

    $p = get_post($id);
    if (!$p || $p->post_type !== 'product') {
        wp_send_json_error(['message' => 'Product not found']);
    }

    $terms = wp_get_object_terms($id, 'product_category');
    $cat_id = ($terms && !is_wp_error($terms) && !empty($terms)) ? $terms[0]->term_id : 0;

    $gallery_json = get_post_meta($id, 'pd_gallery', true);
    $gallery = $gallery_json ? json_decode($gallery_json, true) : [];

    // Per-product custom attributes
    $custom_attrs_json = get_post_meta($id, 'pd_custom_attrs', true);
    $custom_attrs = $custom_attrs_json ? json_decode($custom_attrs_json, true) : [];
    if (!is_array($custom_attrs)) $custom_attrs = [];

    $response = [
        'id'            => $p->ID,
        'title'         => $p->post_title,
        'content'       => $p->post_content,
        'status'        => $p->post_status,
        'category'      => $cat_id,
        'pd_sku'        => get_post_meta($id, 'pd_sku', true),
        'gallery'       => $gallery,
        'custom_attrs'  => $custom_attrs,
        // Details tab
        'pd_subtitle'   => get_post_meta($id, 'pd_subtitle', true),
        'pd_datasheet'  => get_post_meta($id, 'pd_datasheet', true),
    ];
    // Add all spec fields dynamically (builtin + custom)
    foreach (pm_get_all_spec_fields() as $sf) {
        $response[$sf['key']] = get_post_meta($id, $sf['key'], true);
    }
    wp_send_json_success($response);
});

/**
 * Suggest unique product title from selected category name.
 * Example: Flyback, Flyback (1), Flyback (2)
 */
add_action('wp_ajax_pm_suggest_product_title', function() {
    check_ajax_referer('pm_nonce', 'nonce');

    if (!pm_can_manage_products()) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    $cat_id = intval($_POST['category'] ?? 0);
    if (!$cat_id) {
        wp_send_json_error(['message' => 'Invalid category']);
    }

    $term = get_term($cat_id, 'product_category');
    if (!$term || is_wp_error($term)) {
        wp_send_json_error(['message' => 'Category not found']);
    }

    $base = sanitize_text_field($term->name);
    if ($base === '') {
        wp_send_json_error(['message' => 'Invalid category name']);
    }

    global $wpdb;
    $like = $wpdb->esc_like($base) . '%';
    $titles = $wpdb->get_col($wpdb->prepare(
        "SELECT post_title
         FROM {$wpdb->posts}
         WHERE post_type = 'product'
           AND post_status != 'trash'
           AND post_title LIKE %s",
        $like
    ));

    $used = [];
    $pattern = '/^' . preg_quote($base, '/') . ' \((\d+)\)$/u';
    foreach ((array) $titles as $t) {
        if ($t === $base) {
            $used[0] = true;
            continue;
        }
        if (preg_match($pattern, $t, $m)) {
            $used[intval($m[1])] = true;
        }
    }

    $n = 0;
    while (isset($used[$n])) {
        $n++;
    }
    $suggested = $n === 0 ? $base : $base . ' (' . $n . ')';

    wp_send_json_success([
        'title' => $suggested,
        'base'  => $base,
    ]);
});

/**
 * Save product (create or update)
 */
add_action('wp_ajax_pm_save_product', function() {
    check_ajax_referer('pm_nonce', 'nonce');

    if (!pm_can_manage_products()) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    $id       = intval($_POST['id'] ?? 0);
    $title    = sanitize_text_field($_POST['title'] ?? '');
    $content  = wp_kses_post($_POST['content'] ?? '');
    $status   = in_array($_POST['status'] ?? '', ['publish', 'draft']) ? $_POST['status'] : 'publish';
    $cat_id   = intval($_POST['category'] ?? 0);

    if (!$title) {
        wp_send_json_error(['message' => 'กรุณาใส่ชื่อสินค้า']);
    }

    $post_data = [
        'post_title'   => $title,
        'post_content' => $content,
        'post_status'  => $status,
        'post_type'    => 'product',
    ];

    if ($id) {
        $post_data['ID'] = $id;
        $result = wp_update_post($post_data, true);
    } else {
        $result = wp_insert_post($post_data, true);
    }

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }

    $product_id = $id ?: $result;

    // Assign category
    if ($cat_id) {
        wp_set_object_terms($product_id, [$cat_id], 'product_category');
    } else {
        wp_set_object_terms($product_id, [], 'product_category');
    }

    // Save meta fields
    // SKU
    if (isset($_POST['pd_sku'])) {
        update_post_meta($product_id, 'pd_sku', sanitize_text_field($_POST['pd_sku']));
    }

    // Details tab
    if (isset($_POST['pd_subtitle'])) {
        update_post_meta($product_id, 'pd_subtitle', sanitize_text_field($_POST['pd_subtitle']));
    }
    if (isset($_POST['pd_datasheet'])) {
        update_post_meta($product_id, 'pd_datasheet', esc_url_raw($_POST['pd_datasheet']));
    }

    // Gallery (JSON array of URLs)
    $gallery_raw = $_POST['pd_gallery'] ?? '';
    $gallery = json_decode(stripslashes($gallery_raw), true);
    if (!is_array($gallery)) $gallery = [];
    $gallery_clean = array_map('esc_url_raw', array_filter($gallery));
    update_post_meta($product_id, 'pd_gallery', json_encode($gallery_clean));

    // All spec fields (builtin + custom) — dynamically
    foreach (pm_get_all_spec_fields() as $sf) {
        if (!array_key_exists($sf['key'], $_POST)) {
            continue;
        }
        $val = sanitize_text_field($_POST[$sf['key']] ?? '');
        update_post_meta($product_id, $sf['key'], $val);
    }

    // Per-product custom attributes (JSON array of {label, type, value, options})
    $custom_attrs_raw = stripslashes($_POST['custom_attrs'] ?? '[]');
    $custom_attrs = json_decode($custom_attrs_raw, true);
    if (!is_array($custom_attrs)) $custom_attrs = [];
    $custom_attrs_clean = [];
    foreach ($custom_attrs as $attr) {
        $label   = sanitize_text_field($attr['label'] ?? '');
        $type    = in_array(($attr['type'] ?? 'text'), ['text', 'select'], true) ? $attr['type'] : 'text';
        $value   = sanitize_text_field($attr['value'] ?? '');
        $options = sanitize_text_field($attr['options'] ?? '');
        if ($label !== '') {
            $item = ['label' => $label, 'type' => $type, 'value' => $value];
            if ($type === 'select') $item['options'] = $options;
            $custom_attrs_clean[] = $item;
        }
    }
    update_post_meta($product_id, 'pd_custom_attrs', json_encode($custom_attrs_clean, JSON_UNESCAPED_UNICODE));

    wp_send_json_success([
        'id'      => $product_id,
        'message' => $id ? 'อัปเดตสินค้าสำเร็จ!' : 'เพิ่มสินค้าใหม่สำเร็จ!',
    ]);
});

/**
 * Delete product
 */
add_action('wp_ajax_pm_delete_product', function() {
    check_ajax_referer('pm_nonce', 'nonce');

    if (!pm_can_manage_products()) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    $id = intval($_POST['id'] ?? 0);
    if (!$id) wp_send_json_error(['message' => 'Invalid ID']);

    $result = wp_delete_post($id, true);
    if (!$result) {
        wp_send_json_error(['message' => 'ลบสินค้าไม่สำเร็จ']);
    }

    wp_send_json_success(['message' => 'ลบสินค้าสำเร็จ!']);
});

/**
 * Bulk delete products
 */
add_action('wp_ajax_pm_bulk_delete', function() {
    check_ajax_referer('pm_nonce', 'nonce');

    if (!pm_can_manage_products()) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    $ids = array_map('intval', $_POST['ids'] ?? []);
    $deleted = 0;

    foreach ($ids as $id) {
        if ($id && wp_delete_post($id, true)) {
            $deleted++;
        }
    }

    wp_send_json_success(['message' => "ลบสินค้า {$deleted} รายการสำเร็จ!", 'deleted' => $deleted]);
});

/**
 * Bulk move products to category
 */
add_action('wp_ajax_pm_bulk_move', function() {
    check_ajax_referer('pm_nonce', 'nonce');

    if (!pm_can_manage_products()) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    $ids    = array_map('intval', $_POST['ids'] ?? []);
    $cat_id = intval($_POST['category'] ?? 0);
    $moved  = 0;

    foreach ($ids as $id) {
        if ($id) {
            wp_set_object_terms($id, [$cat_id], 'product_category');
            $moved++;
        }
    }

    wp_send_json_success(['message' => "ย้ายสินค้า {$moved} รายการสำเร็จ!", 'moved' => $moved]);
});

/**
 * Save category (create or update)
 */
add_action('wp_ajax_pm_save_category', function() {
    check_ajax_referer('pm_nonce', 'nonce');

    if (!pm_can_manage_products()) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    $id        = intval($_POST['id'] ?? 0);
    $name      = sanitize_text_field($_POST['name'] ?? '');
    $parent    = intval($_POST['parent'] ?? 0);
    $desc      = sanitize_textarea_field($_POST['desc'] ?? '');
    $desc_long = wp_kses_post($_POST['desc_long'] ?? '');
    $specs_raw = $_POST['specs'] ?? '[]';
    $image     = esc_url_raw($_POST['image'] ?? '');

    // Validate & sanitize specs JSON
    $specs_arr = json_decode(stripslashes($specs_raw), true);
    if (!is_array($specs_arr)) $specs_arr = [];
    $specs_clean = [];
    foreach ($specs_arr as $s) {
        if (!empty($s['label'])) {
            $specs_clean[] = [
                'label' => sanitize_text_field($s['label']),
                'value' => sanitize_text_field($s['value'] ?? ''),
                'icon'  => sanitize_text_field($s['icon'] ?? ''),
            ];
        }
    }

    if (!$name) {
        wp_send_json_error(['message' => 'กรุณาใส่ชื่อ Category']);
    }

    if ($id) {
        $result = wp_update_term($id, 'product_category', [
            'name'        => $name,
            'parent'      => $parent,
            'description' => $desc,
        ]);
    } else {
        $result = wp_insert_term($name, 'product_category', [
            'parent'      => $parent,
            'description' => $desc,
        ]);
    }

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }

    $term_id = is_array($result) ? $result['term_id'] : $id;

    // Save all term meta
    update_term_meta($term_id, 'cat_image', $image);
    update_term_meta($term_id, 'cat_description', $desc);
    update_term_meta($term_id, 'cat_description_long', $desc_long);
    update_term_meta($term_id, 'cat_specs', json_encode($specs_clean));

    wp_send_json_success([
        'id'      => $term_id,
        'message' => $id ? 'อัปเดต Category สำเร็จ!' : 'เพิ่ม Category สำเร็จ!',
    ]);
});

/**
 * Delete category
 */
add_action('wp_ajax_pm_delete_category', function() {
    check_ajax_referer('pm_nonce', 'nonce');

    if (!pm_can_manage_products()) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    $id = intval($_POST['id'] ?? 0);
    if (!$id) wp_send_json_error(['message' => 'Invalid ID']);

    $result = wp_delete_term($id, 'product_category');
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }

    wp_send_json_success(['message' => 'ลบ Category สำเร็จ!']);
});

/**
 * Seed default categories
 */
add_action('wp_ajax_pm_seed_categories', function() {
    check_ajax_referer('pm_nonce', 'nonce');

    if (!pm_can_manage_products()) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    $categories = [
        'Transformer' => [
            'Flyback',
            'Forward',
            'Resonant',
            'Power',
            'Gate Drive',
            'Telecom (Enercon)',
        ],
        'Inductor' => [
            'Common-Mode Choke',
            'Output Filter',
            'Input Filter',
            'Power Factor Correction Choke',
        ],
        'Antenna' => [
            'Ferrite Core Antenna',
            'Air Coil',
        ],
    ];

    $created = 0;

    foreach ($categories as $parent_name => $children) {
        // Check if parent already exists
        $parent = term_exists($parent_name, 'product_category');
        if (!$parent) {
            $parent = wp_insert_term($parent_name, 'product_category');
            if (!is_wp_error($parent)) $created++;
        }

        if (is_wp_error($parent)) continue;
        $parent_id = is_array($parent) ? $parent['term_id'] : $parent;

        foreach ($children as $child_name) {
            $child = term_exists($child_name, 'product_category', $parent_id);
            if (!$child) {
                $result = wp_insert_term($child_name, 'product_category', ['parent' => $parent_id]);
                if (!is_wp_error($result)) $created++;
            }
        }
    }

    wp_send_json_success(['message' => "สร้าง {$created} categories สำเร็จ!", 'created' => $created]);
});

// ============================================
// SPEC FIELDS — helpers: builtin + custom
// ============================================

/**
 * Get custom spec fields added via the admin UI.
 * Stored in the 'spec_custom_fields' WP option.
 */
function pm_get_custom_spec_fields(): array {
    $fields = get_option('spec_custom_fields', []);
    if (!is_array($fields)) return [];
    $clean = [];
    foreach ($fields as $f) {
        $key   = sanitize_key($f['key'] ?? '');
        $label = sanitize_text_field($f['label'] ?? '');
        if ($key && $label) {
            $clean[] = ['key' => $key, 'label' => $label];
        }
    }
    return $clean;
}

/**
 * Built-in spec fields (always available in manager).
 */
function pm_default_builtin_spec_fields(): array {
    return [
        // Electrical
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
        // Mechanical
        ['key' => 'pd_dimensions',    'label' => 'Dimensions (L x W x H)'],
        ['key' => 'pd_weight',        'label' => 'Weight'],
        ['key' => 'pd_pin_config',    'label' => 'Pin Configuration'],
        ['key' => 'pd_mount_type',    'label' => 'Mounting Type'],
        ['key' => 'pd_core_material', 'label' => 'Core Material'],
        ['key' => 'pd_land_pattern',  'label' => 'Land Pattern'],
        // Construction
        ['key' => 'pd_winding',       'label' => 'Winding Construction'],
        ['key' => 'pd_core_shape',    'label' => 'Core Shape'],
        ['key' => 'pd_core_size',     'label' => 'Core Size'],
        ['key' => 'pd_bobbin_pin',    'label' => 'Bobbin Pin Type'],
        ['key' => 'pd_wire_type',     'label' => 'Wire Type'],
        ['key' => 'pd_wire_size',     'label' => 'Wire Size'],
        // Compliance & Packaging
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
}

/**
 * Built-in spec fields with optional admin label overrides.
 */
function pm_get_builtin_spec_fields(): array {
    $fields = pm_default_builtin_spec_fields();
    $overrides = get_option('spec_builtin_field_labels', []);
    if (!is_array($overrides)) $overrides = [];

    foreach ($fields as &$f) {
        $key = $f['key'];
        if (isset($overrides[$key])) {
            $label = sanitize_text_field($overrides[$key]);
            if ($label !== '') {
                $f['label'] = $label;
            }
        }
    }
    unset($f);

    return $fields;
}

/**
 * Built-in fields that are hidden/removed from product forms by admin.
 */
function pm_get_disabled_builtin_spec_keys(): array {
    $keys = get_option('spec_disabled_builtin_fields', []);
    if (!is_array($keys)) return [];
    $builtin_keys = array_column(pm_get_builtin_spec_fields(), 'key');
    $clean = [];
    foreach ($keys as $k) {
        $k = sanitize_key($k);
        if ($k && in_array($k, $builtin_keys, true)) {
            $clean[] = $k;
        }
    }
    return array_values(array_unique($clean));
}

/**
 * Get saved spec field order keys.
 */
function pm_get_spec_field_order_keys(): array {
    $keys = get_option('spec_field_order', []);
    if (!is_array($keys)) return [];
    $clean = [];
    foreach ($keys as $k) {
        $k = sanitize_key($k);
        if ($k !== '') $clean[] = $k;
    }
    return array_values(array_unique($clean));
}

/**
 * Sort fields by saved order; unknown keys stay at the end in original order.
 */
function pm_sort_spec_fields_by_saved_order(array $fields): array {
    $order = pm_get_spec_field_order_keys();
    if (empty($order)) return $fields;

    $rank = [];
    foreach ($order as $i => $k) {
        $rank[$k] = $i;
    }

    usort($fields, function($a, $b) use ($rank) {
        $ka = (string) ($a['key'] ?? '');
        $kb = (string) ($b['key'] ?? '');
        $ra = array_key_exists($ka, $rank) ? $rank[$ka] : PHP_INT_MAX;
        $rb = array_key_exists($kb, $rank) ? $rank[$kb] : PHP_INT_MAX;
        if ($ra === $rb) return 0;
        return ($ra < $rb) ? -1 : 1;
    });

    return $fields;
}

/**
 * Get ALL ACTIVE spec fields: built-in (except disabled) + custom.
 */
function pm_get_all_spec_fields(): array {
    $disabled_keys = pm_get_disabled_builtin_spec_keys();
    $builtin_active = array_values(array_filter(pm_get_builtin_spec_fields(), function($f) use ($disabled_keys) {
        return !in_array($f['key'], $disabled_keys, true);
    }));
    return pm_sort_spec_fields_by_saved_order(array_merge($builtin_active, pm_get_custom_spec_fields()));
}

// ============================================
// SPEC FIELD ICONS — get / save
// ============================================

/**
 * Default icon map (Font Awesome 4 class names)
 */
function pm_default_spec_icons(): array {
    return [
        // Electrical
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
        // Mechanical
        'pd_dimensions'    => 'fa fa-arrows',
        'pd_weight'        => 'fa fa-balance-scale',
        'pd_pin_config'    => 'fa fa-sitemap',
        'pd_mount_type'    => 'fa fa-microchip',
        'pd_core_material' => 'fa fa-circle-o',
        'pd_land_pattern'  => 'fa fa-th',
        // Construction
        'pd_winding'       => 'fa fa-random',
        'pd_core_shape'    => 'fa fa-diamond',
        'pd_core_size'     => 'fa fa-arrows-alt',
        'pd_bobbin_pin'    => 'fa fa-thumb-tack',
        'pd_wire_type'     => 'fa fa-chain',
        'pd_wire_size'     => 'fa fa-text-width',
        // Compliance & Packaging
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
}

/**
 * Get current spec field icons (merged with defaults).
 * Custom fields default to empty string icon.
 */
function pm_get_spec_icons(): array {
    $saved = get_option('spec_field_icons', []);
    if (!is_array($saved)) $saved = [];
    // Custom fields default to empty icon
    $custom_defaults = [];
    foreach (pm_get_custom_spec_fields() as $f) {
        $custom_defaults[$f['key']] = '';
    }
    return array_merge(pm_default_spec_icons(), $custom_defaults, $saved);
}

add_action('wp_ajax_pm_get_field_icons', function() {
    check_ajax_referer('pm_nonce', 'nonce');
    wp_send_json_success(['icons' => pm_get_spec_icons()]);
});

/**
 * Get all spec fields with icon data — for Spec Fields Manager modal
 */
add_action('wp_ajax_pm_get_spec_fields', function() {
    check_ajax_referer('pm_nonce', 'nonce');
    $builtin_fields = pm_get_builtin_spec_fields();
    $custom_fields  = pm_get_custom_spec_fields();
    $all            = pm_sort_spec_fields_by_saved_order(array_merge($builtin_fields, $custom_fields));
    $disabled_builtin_keys = pm_get_disabled_builtin_spec_keys();
    $icons  = pm_get_spec_icons();

    $builtin_keys = array_column($builtin_fields, 'key');

    $result = [];
    foreach ($all as $f) {
        $is_builtin = in_array($f['key'], $builtin_keys, true);
        $icon_val = $icons[$f['key']] ?? '';
        $result[] = [
            'key'        => $f['key'],
            'label'      => $f['label'],
            'builtin'    => $is_builtin,
            'disabled'   => $is_builtin && in_array($f['key'], $disabled_builtin_keys, true),
            'icon_value' => $icon_val,
            'icon_type'  => (strlen(trim($icon_val)) > 0 && trim($icon_val)[0] === '<') ? 'svg' : 'fa',
        ];
    }
    wp_send_json_success(['fields' => $result]);
});

/**
 * Save spec fields (custom fields list) + all icons (FA class or SVG markup)
 */
add_action('wp_ajax_pm_save_spec_fields', function() {
    check_ajax_referer('pm_nonce', 'nonce');
    if (!pm_can_manage_products()) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    // --- Save custom fields list ---
    $custom_raw = stripslashes($_POST['custom_fields'] ?? '[]');
    $custom_arr = json_decode($custom_raw, true);
    if (!is_array($custom_arr)) $custom_arr = [];

    $custom_clean = [];
    foreach ($custom_arr as $f) {
        $key   = sanitize_key($f['key'] ?? '');
        $label = sanitize_text_field($f['label'] ?? '');
        if ($key && $label) {
            $custom_clean[] = ['key' => $key, 'label' => $label];
        }
    }
    update_option('spec_custom_fields', $custom_clean);

    // --- Save built-in labels (editable attrs) ---
    $builtin_labels_raw = stripslashes($_POST['builtin_labels'] ?? '{}');
    $builtin_labels_arr = json_decode($builtin_labels_raw, true);
    if (!is_array($builtin_labels_arr)) $builtin_labels_arr = [];

    $builtin_default_map = [];
    foreach (pm_default_builtin_spec_fields() as $f) {
        $builtin_default_map[$f['key']] = $f['label'];
    }

    $builtin_labels_clean = [];
    foreach ($builtin_labels_arr as $key => $label) {
        $key = sanitize_key($key);
        if (!isset($builtin_default_map[$key])) continue;
        $label = sanitize_text_field($label);
        if ($label !== '' && $label !== $builtin_default_map[$key]) {
            $builtin_labels_clean[$key] = $label;
        }
    }
    update_option('spec_builtin_field_labels', $builtin_labels_clean);

    // --- Save disabled built-in field keys ("delete attr" for built-ins) ---
    $disabled_raw = stripslashes($_POST['disabled_builtin_keys'] ?? '[]');
    $disabled_arr = json_decode($disabled_raw, true);
    if (!is_array($disabled_arr)) $disabled_arr = [];

    $builtin_keys = array_column(pm_get_builtin_spec_fields(), 'key');
    $disabled_clean = [];
    foreach ($disabled_arr as $k) {
        $k = sanitize_key($k);
        if ($k && in_array($k, $builtin_keys, true)) {
            $disabled_clean[] = $k;
        }
    }
    update_option('spec_disabled_builtin_fields', array_values(array_unique($disabled_clean)));

    // --- Save global field order ---
    $field_order_raw = stripslashes($_POST['field_order'] ?? '[]');
    $field_order_arr = json_decode($field_order_raw, true);
    if (!is_array($field_order_arr)) $field_order_arr = [];

    $all_builtin_keys = array_column(pm_get_builtin_spec_fields(), 'key');
    $all_custom_keys  = array_column($custom_clean, 'key');
    $all_allowed_keys = array_unique(array_merge($all_builtin_keys, $all_custom_keys));

    $field_order_clean = [];
    foreach ($field_order_arr as $k) {
        $k = sanitize_key($k);
        if ($k && in_array($k, $all_allowed_keys, true)) {
            $field_order_clean[] = $k;
        }
    }
    // Keep disabled built-ins in order list too (appended) so re-enable gets stable position.
    foreach ($disabled_clean as $k) {
        if (!in_array($k, $field_order_clean, true)) {
            $field_order_clean[] = $k;
        }
    }
    update_option('spec_field_order', array_values(array_unique($field_order_clean)));

    // --- Save icons ---
    $icons_raw = stripslashes($_POST['icons'] ?? '{}');
    $icons_arr = json_decode($icons_raw, true);
    if (!is_array($icons_arr)) $icons_arr = [];

    // Build valid key allowlist
    $allowed_keys_base = array_column(pm_get_builtin_spec_fields(), 'key');
    $allowed_keys_new  = array_column($custom_clean, 'key');
    $allowed_keys      = array_unique(array_merge($allowed_keys_base, $allowed_keys_new));

    $svg_allowed_tags = [
        'svg'      => ['xmlns' => true, 'viewbox' => true, 'viewBox' => true, 'width' => true, 'height' => true,
                        'fill' => true, 'stroke' => true, 'stroke-width' => true, 'stroke-linecap' => true,
                        'stroke-linejoin' => true, 'aria-hidden' => true, 'class' => true, 'style' => true,
                        'preserveaspectratio' => true, 'preserveAspectRatio' => true,
                        'role' => true, 'focusable' => true],
        'path'     => ['d' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true,
                        'stroke-linecap' => true, 'stroke-linejoin' => true, 'fill-rule' => true,
                        'clip-rule' => true, 'opacity' => true, 'transform' => true],
        'circle'   => ['cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'opacity' => true],
        'rect'     => ['x' => true, 'y' => true, 'width' => true, 'height' => true, 'rx' => true, 'ry' => true,
                        'fill' => true, 'stroke' => true, 'opacity' => true, 'transform' => true],
        'line'     => ['x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'stroke' => true, 'stroke-width' => true, 'opacity' => true],
        'polyline' => ['points' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'opacity' => true],
        'polygon'  => ['points' => true, 'fill' => true, 'stroke' => true, 'opacity' => true],
        'g'        => ['fill' => true, 'stroke' => true, 'transform' => true, 'opacity' => true, 'class' => true],
        'ellipse'  => ['cx' => true, 'cy' => true, 'rx' => true, 'ry' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true, 'opacity' => true],
        'use'      => ['href' => true, 'xlink:href' => true, 'x' => true, 'y' => true],
        'defs'     => [],
        'symbol'   => ['id' => true, 'viewBox' => true],
        'linearGradient' => ['id' => true, 'x1' => true, 'y1' => true, 'x2' => true, 'y2' => true, 'gradientUnits' => true, 'gradientTransform' => true],
        'radialGradient' => ['id' => true, 'cx' => true, 'cy' => true, 'r' => true, 'fx' => true, 'fy' => true, 'gradientUnits' => true, 'gradientTransform' => true],
        'stop'     => ['offset' => true, 'stop-color' => true, 'stop-opacity' => true],
        'clipPath' => ['id' => true],
        'mask'     => ['id' => true, 'x' => true, 'y' => true, 'width' => true, 'height' => true],
        'title'    => [],
    ];

    $icons_clean = [];
    foreach ($icons_arr as $key => $val) {
        $key = sanitize_key($key);
        if (!in_array($key, $allowed_keys, true)) continue;
        $val = trim($val);
        if (strlen($val) > 0 && $val[0] === '<') {
            $icons_clean[$key] = wp_kses($val, $svg_allowed_tags);
        } else {
            $icons_clean[$key] = sanitize_text_field($val);
        }
    }
    update_option('spec_field_icons', $icons_clean);

    wp_send_json_success([
        'message'     => 'บันทึก Spec Fields สำเร็จ!',
        'spec_fields' => pm_get_all_spec_fields(),
        'icons'       => pm_get_spec_icons(),
    ]);
});

add_action('wp_ajax_pm_save_field_icons', function() {
    check_ajax_referer('pm_nonce', 'nonce');
    if (!pm_can_manage_products()) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    $allowed_keys = array_column(pm_get_all_spec_fields(), 'key');
    $icons_raw    = $_POST['icons'] ?? [];
    if (!is_array($icons_raw)) {
        wp_send_json_error(['message' => 'Invalid data']);
    }

    $icons_clean = [];
    foreach ($allowed_keys as $key) {
        $val = sanitize_text_field($icons_raw[$key] ?? '');
        $icons_clean[$key] = $val;
    }

    update_option('spec_field_icons', $icons_clean);
    wp_send_json_success(['message' => 'บันทึก Icons สำเร็จ!', 'icons' => $icons_clean]);
});
