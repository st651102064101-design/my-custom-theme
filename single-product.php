<?php
/**
 * Template: Single Product
 * 
 * แสดง Product Detail — ดึงข้อมูลทั้งหมดจาก database (CPT + ACF)
 * Gallery auto-slide, Specs, Features, Datasheet Modal, Related Products
 */

get_header();

if (have_posts()) : while (have_posts()) : the_post();

$product_id = get_the_ID();
$product_subtitle = trim((string) get_post_meta($product_id, 'pd_subtitle', true));

// --- Category & Breadcrumb ---
$terms = wp_get_object_terms($product_id, 'product_category');
$child_term = ($terms && !is_wp_error($terms)) ? $terms[0] : null;
$parent_term = null;
if ($child_term && $child_term->parent) {
    $parent_term = get_term($child_term->parent, 'product_category');
}
// If the assigned term IS a parent (no parent of its own), use it as parent
if ($child_term && !$child_term->parent) {
    $parent_term = $child_term;
    $child_term = null;
}

// Count sibling products in child category (determines breadcrumb depth)
$_cat_product_count = 0;
if ($child_term) {
    $cat_count_q = new WP_Query([
        'post_type'      => 'product',
        'post_status'    => ['publish', 'draft'],
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'tax_query'      => [[
            'taxonomy'         => 'product_category',
            'field'            => 'term_id',
            'terms'            => $child_term->term_id,
            'include_children' => false,
        ]],
    ]);
    $_cat_product_count = (int) $cat_count_q->found_posts;
    wp_reset_postdata();
}

// Gallery images
$images = [];
for ($i = 1; $i <= 3; $i++) {
    $url = get_post_meta($product_id, "pd_image_{$i}", true);
    if ($url) $images[] = kv_rebase_url($url);
}
if (empty($images) && has_post_thumbnail()) {
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
$spec_icons = function_exists('pm_get_spec_icons') ? pm_get_spec_icons() : [];

$spec_fields = [];
foreach ($all_spec_defs as $sf) {
    $spec_fields[$sf['key']] = $sf['label'];
}

// Preferred display order for product specs
$preferred_spec_order = [
    'pd_inductance',
    'pd_current_rating',
    'pd_impedance',
    'pd_voltage',
    'pd_frequency',
    'pd_dcr',
    'pd_insulation',
    'pd_hipot',
    'pd_turns_ratio',
    'pd_power_rating',
    'pd_dimensions',
    'pd_weight',
    'pd_pin_config',
    'pd_mount_type',
    'pd_core_material',
    'pd_land_pattern',
    'pd_winding',
    'pd_core_shape',
    'pd_core_size',
    'pd_bobbin_pin',
    'pd_wire_type',
    'pd_wire_size',
    'pd_size_range',
    'pd_output_range',
    'pd_temp_range',
    'pd_package_type',
    'pd_packing_qty',
    'pd_standards',
    'pd_compliance',
    'pd_safety_certs',
    'pd_storage_conditions',
    'pd_msl',
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

// Datasheet: use uploaded PDF only
$datasheet_url = trim((string) get_post_meta($product_id, 'pd_datasheet', true));
?>

<?php
// Banner settings — use theme primary color (60:30:10)
$_bg_color   = get_option('theme_primary_color', '#0056d6');
$_banner_img = !empty($images) ? $images[0] : '';
?>
<!-- Page Banner -->
<section class="page-banner w-100" style="background-color:<?php echo esc_attr($_bg_color); ?>;padding:60px 0;position:relative;overflow:hidden;">

    <?php if ($_banner_img) : ?>
    <!-- Blurred background image layer -->
    <div style="position:absolute;inset:-30px;background-image:url('<?php echo esc_url($_banner_img); ?>');background-size:cover;background-position:center center;filter:blur(8px);transform:scale(1.15);z-index:0;opacity:0.9;"></div>
    <?php endif; ?>

    <!-- Dark color overlay -->
    <div style="position:absolute;inset:0;background-color:<?php echo esc_attr($_bg_color); ?>;opacity:<?php echo $_banner_img ? '0.35' : '1'; ?>;z-index:1;"></div>
    <div class="container text-center position-relative" style="z-index:2;">
        <h1 style="color:#fff;font-size:clamp(28px,5vw,42px);font-weight:700;margin-bottom:12px;line-height:1.2;">
            <?php the_title(); ?>
        </h1>
        <nav aria-label="breadcrumb" style="display:inline-flex;align-items:center;gap:8px;color:rgba(255,255,255,0.85);font-size:15px;">
            <a href="<?php echo esc_url(home_url('/')); ?>" style="color:#fff;text-decoration:underline;white-space:nowrap;">Home</a>
            <span style="opacity:0.7;">/</span>
            <a href="<?php echo esc_url(home_url('/products/')); ?>" style="color:#fff;text-decoration:underline;white-space:nowrap;">Products</a>
            <?php if ($parent_term) : ?>
                <span style="opacity:0.7;">/</span>
                <a href="<?php echo esc_url(get_term_link($parent_term)); ?>" style="color:#fff;text-decoration:underline;white-space:nowrap;"><?php echo esc_html($parent_term->name); ?></a>
            <?php endif; ?>
            <?php if ($child_term) : ?>
                <span style="opacity:0.7;">/</span>
                <?php if ($_cat_product_count > 1) : ?>
                    <a href="<?php echo esc_url(get_term_link($child_term)); ?>" style="color:#fff;text-decoration:underline;white-space:nowrap;"><?php echo esc_html($child_term->name); ?></a>
                    <span style="opacity:0.7;">/</span>
                    <span style="white-space:nowrap;"><?php the_title(); ?></span>
                <?php else : ?>
                    <span style="white-space:nowrap;"><?php echo esc_html($child_term->name); ?></span>
                <?php endif; ?>
            <?php else : ?>
                <span style="opacity:0.7;">/</span>
                <span style="white-space:nowrap;"><?php the_title(); ?></span>
            <?php endif; ?>
        </nav>
    </div>
</section>

<!-- Product Detail -->
<section class="product-detail" style="padding:60px 0;">
    <div class="container">
        <div style="display:flex;flex-direction:column;align-items:center;width:100%;">

        <!-- Gallery -->
        <?php if (!empty($images)) : ?>
        <div class="product-gallery" style="width:100%;margin-bottom:60px;">

                <!-- Image wrapper -->
                <div id="pd-img-wrapper" style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,0.08);position:relative;cursor:crosshair;height:420px;">
                    <img id="pd-main-img" src="<?php echo esc_url($images[0]); ?>" alt="<?php the_title_attribute(); ?> - View 1" style="width:100%;height:100%;object-fit:contain;display:block;transition:opacity 0.4s;opacity:1;">
                    <!-- Dots -->
                    <?php if (count($images) > 1) : ?><div style="position:absolute;bottom:12px;left:50%;transform:translateX(-50%);display:flex;gap:6px;"><?php foreach ($images as $idx => $img) : ?><span class="pd-dot" data-index="<?php echo $idx; ?>" style="width:8px;height:8px;border-radius:50%;background:<?php echo $idx === 0 ? 'var(--theme-accent)' : 'rgba(255,255,255,0.6)'; ?>;cursor:pointer;transition:background 0.3s;display:inline-block;"></span><?php endforeach; ?></div><?php endif; ?>
                    <!-- Progress bar (inside wrapper, absolute bottom) -->
                    <?php if (count($images) > 1) : ?><div style="position:absolute;bottom:0;left:0;right:0;height:3px;background:rgba(229,231,235,0.5);"><div id="pd-progress-bar" style="height:100%;width:0%;background:var(--theme-accent);transition:width 5s linear;"></div></div><?php endif; ?>
                </div>

                <!-- Thumbnails -->
                <?php if (count($images) > 1) : ?>
                <div style="display:flex;gap:10px;justify-content:center;margin-top:15px;"><?php foreach ($images as $idx => $img) : ?><img src="<?php echo esc_url($img); ?>" alt="<?php the_title_attribute(); ?> - View <?php echo $idx + 1; ?>" class="pd-thumb<?php echo $idx === 0 ? ' active' : ''; ?>" data-index="<?php echo $idx; ?>" style="width:80px;height:60px;object-fit:cover;border-radius:8px;border:2px solid <?php echo $idx === 0 ? 'var(--theme-accent)' : '#e5e7eb'; ?>;cursor:pointer;transition:border-color 0.3s;"><?php endforeach; ?></div>
                <?php endif; ?>

        </div>
        <?php endif; ?>

        <!-- Info -->
        <div class="product-info" style="width:100%;text-align:center;">
            <?php if ($product_subtitle !== '') : ?>
            <p style="color:#334155;font-size:clamp(14px,2.2vw,18px);margin:0 0 22px;line-height:1.6;max-width:860px;margin-left:auto;margin-right:auto;">
                <?php echo esc_html($product_subtitle); ?>
            </p>
            <?php endif; ?>
            <?php
            global $post;
            $post_content = isset($post->post_content) ? trim($post->post_content) : '';
            if ($post_content) : ?>
            <div class="product-description" style="color:#475569;font-size:15px;line-height:1.8;margin-bottom:30px;text-align:left;">
                <?php echo apply_filters('the_content', $post_content); ?>
            </div>
            <?php endif; ?>

            <!-- Specifications -->
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
                                    <i class="<?php echo esc_attr($ic); ?>" style="color:var(--theme-primary);width:18px;text-align:center;flex-shrink:0;line-height:1.6;display:inline-flex;align-items:center;justify-content:center;"></i>
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

            <!-- CTA Buttons -->
            <div style="display:flex;gap:15px;justify-content:center;">
                <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="btn btn-primary" style="padding:15px 40px;font-size:16px;">Request Quote</a>
                <?php if ($datasheet_url !== '') : ?>
                    <a href="#" class="btn btn-outline" style="padding:15px 40px;font-size:16px;" data-bs-toggle="modal" data-bs-target="#datasheetModal">Download Datasheet</a>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /.container -->
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

<!-- Related Products -->
<?php
$_related_ids = [];
$_limit = 6;

// Tier 1: same subcategory (if exists)
if ($child_term && count($_related_ids) < $_limit) {
    $q = new WP_Query([
        'post_type'      => 'product',
        'posts_per_page' => $_limit,
        'post__not_in'   => array_merge([$product_id], $_related_ids),
        'fields'         => 'ids',
        'orderby'        => 'rand',
        'tax_query'      => [[
            'taxonomy'         => 'product_category',
            'field'            => 'term_id',
            'terms'            => $child_term->term_id,
            'include_children' => false,
        ]],
    ]);
    $_related_ids = array_merge($_related_ids, $q->posts);
    wp_reset_postdata();
}

// Tier 2: same parent category
if ($parent_term && count($_related_ids) < $_limit) {
    $q = new WP_Query([
        'post_type'      => 'product',
        'posts_per_page' => $_limit - count($_related_ids),
        'post__not_in'   => array_merge([$product_id], $_related_ids),
        'fields'         => 'ids',
        'orderby'        => 'rand',
        'tax_query'      => [[
            'taxonomy'         => 'product_category',
            'field'            => 'term_id',
            'terms'            => $parent_term->term_id,
            'include_children' => true,
        ]],
    ]);
    $_related_ids = array_merge($_related_ids, $q->posts);
    wp_reset_postdata();
}

// Tier 3: any product
if (count($_related_ids) < $_limit) {
    $q = new WP_Query([
        'post_type'      => 'product',
        'posts_per_page' => $_limit - count($_related_ids),
        'post__not_in'   => array_merge([$product_id], $_related_ids),
        'fields'         => 'ids',
        'orderby'        => 'rand',
    ]);
    $_related_ids = array_merge($_related_ids, $q->posts);
    wp_reset_postdata();
}

$related = new WP_Query([
    'post_type'      => 'product',
    'post__in'       => empty($_related_ids) ? [0] : $_related_ids,
    'posts_per_page' => $_limit,
    'orderby'        => 'post__in',
]);

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

<!-- Auto-slide + Click JS -->
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
            t.style.borderColor = i === current ? "var(--theme-accent)" : "#e5e7eb";
        });
        dots.forEach(function (d, i) {
            d.style.background = i === current ? "var(--theme-accent)" : "rgba(255,255,255,0.6)";
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

    inner.innerHTML = svgUp;
    inner.appendChild(trackWrap);
    inner.innerHTML += svgDn;   /* note: resets—rebuild properly */

    /* rebuild cleanly */
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

    /* ── drag handlers on document (so drag works outside wrapper) ── */
    function onDocMove(e) {
        if (!dragging) return;
        var dy = dragStartY - e.clientY;        /* up = positive = zoom in */
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
            /* re-apply zoom at current mouse (if still inside) */
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
        if (dragging) return;   /* keep zoom alive while dragging outside */
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

<?php endwhile; endif; ?>

<?php get_footer(); ?>
