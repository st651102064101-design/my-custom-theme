<?php
/**
 * Template Part: Page Banner
 *
 * แสดง Banner หัวหน้า — พื้นหลังและรูปภาพปรับได้จาก ตั้งค่าเว็บไซต์
 *
 * ตัวแปร wp_options ที่ใช้:
 *   banner_bg_color  — สีพื้นหลัง HEX  (ค่าเริ่มต้น: var(--theme-primary))
 *   banner_bg_image  — URL รูปพื้นหลัง (ไม่บังคับ)
 *   banner_overlay   — ความทึบของ overlay เมื่อมีรูปพื้นหลัง 0-100 (ค่าเริ่มต้น: 60)
 */

// ไม่แสดง banner ในหน้าแรก (static front page)
if (is_front_page()) return;

$bg_color  = get_option('theme_primary_color', '#0056d6');
$bg_image  = get_option('banner_bg_image', '');
$overlay   = (int) get_option('banner_overlay', 60);
$page_slug = is_page() ? get_post_field('post_name', get_queried_object_id()) : '';
$is_tall_banner_page = in_array($page_slug, ['about', 'contact', 'contacts'], true);
$banner_padding = $is_tall_banner_page ? '120px' : '60px';

// สร้าง inline style สำหรับ section
$section_style = 'background-color:' . esc_attr($bg_color) . ';padding:' . esc_attr($banner_padding) . ' 0;position:relative;overflow:hidden;';
if ($bg_image) {
    $section_style .= 'background-image:url(' . esc_url($bg_image) . ');background-size:cover;background-position:center center;';
}
if ($is_tall_banner_page) {
    $section_style .= 'min-height:420px;display:flex;align-items:center;';
}

// คำนวณ overlay opacity (0-1)
$opacity = round($overlay / 100, 2);
?>
<section class="page-banner w-100" style="<?php echo $section_style; ?>">
    <?php if ($bg_image) : ?>
        <div style="position:absolute;inset:0;background-color:<?php echo esc_attr($bg_color); ?>;opacity:<?php echo $opacity; ?>;z-index:0;"></div>
    <?php endif; ?>
    <div class="container text-center position-relative" style="z-index:1;">
        <h1 style="color:#fff;font-size:clamp(28px,5vw,42px);font-weight:700;margin-bottom:12px;line-height:1.2;">
            <?php echo esc_html(get_the_title()); ?>
        </h1>
        <nav aria-label="breadcrumb" style="display:inline-flex;align-items:center;gap:8px;color:rgba(255,255,255,0.85);font-size:15px;">
            <a href="<?php echo esc_url(home_url('/')); ?>" style="color:#fff;text-decoration:underline;white-space:nowrap;">Home</a>
            <span style="opacity:0.7;">/</span>
            <span style="white-space:nowrap;"><?php echo esc_html(get_the_title()); ?></span>
        </nav>
    </div>
</section>
