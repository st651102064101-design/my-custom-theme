<?php
/**
 * Template Name: CMC Series Product Detail
 * Description: หน้า Product Detail พร้อม Gallery Auto-Slide และแก้ภาพผ่าน Custom Fields
 *
 * วิธีแก้ภาพใน WordPress:
 * ไปที่ Admin → Pages → CMC Series → เปิด "Custom Fields" แล้วแก้ค่า:
 *   pd_image_1  = URL ภาพที่ 1
 *   pd_image_2  = URL ภาพที่ 2
 *   pd_image_3  = URL ภาพที่ 3
 *   pd_image_1_alt, pd_image_2_alt, pd_image_3_alt = ข้อความ alt
 */

// ค่า Default รูปภาพ (ใช้เมื่อยังไม่ได้ตั้ง Custom Field)
$default_images = [
    [
        'url' => 'https://schottmagnetics.com/wp-content/uploads/2021/07/Toroid-Inductor-Choke3-1024x768-1.jpg',
        'alt' => 'Common Mode Choke CMC Series - View 1',
    ],
    [
        'url' => 'https://schottmagnetics.com/wp-content/uploads/2021/07/Flyback-Transformer4-1024x768-1.jpg',
        'alt' => 'Common Mode Choke CMC Series - View 2',
    ],
    [
        'url' => 'https://schottmagnetics.com/wp-content/uploads/2021/07/Air-Coils4.jpg',
        'alt' => 'Common Mode Choke CMC Series - View 3',
    ],
];

// ดึงค่าจาก Custom Fields (ถ้ามี ให้ใช้แทน default)
$page_id = get_the_ID();
$images = [];
for ($i = 1; $i <= 3; $i++) {
    $url = get_post_meta($page_id, "pd_image_{$i}", true);
    $alt = get_post_meta($page_id, "pd_image_{$i}_alt", true);
    $images[] = [
        'url' => $url ?: $default_images[$i-1]['url'],
        'alt' => $alt ?: $default_images[$i-1]['alt'],
    ];
}

get_header(); ?>

<!-- Breadcrumb -->
<section class="page-header" style="padding:20px 24px;background:#f8fafc;">
    <nav aria-label="breadcrumb" style="display:flex;align-items:center;justify-content:center;gap:6px;flex-wrap:nowrap;font-size:14px;color:#64748b;margin:0;"><a href="<?php echo esc_url(home_url('/')); ?>" style="color:var(--theme-primary);white-space:nowrap;">Home</a><span>/</span><a href="<?php echo esc_url(home_url('/products/')); ?>" style="color:var(--theme-primary);white-space:nowrap;">Products</a><span>/</span><a href="<?php echo esc_url(home_url('/products/inductors/')); ?>" style="color:var(--theme-primary);white-space:nowrap;">Inductors</a><span>/</span><span style="color:#1e293b;white-space:nowrap;">CMC Series</span></nav>
</section>

<!-- Product Detail -->
<section class="product-detail" style="padding:60px 0;display:flex;justify-content:center;width:100%;">
    <div style="display:flex;flex-direction:column;align-items:center;max-width:900px;width:100%;padding:0 20px;">

        <!-- Gallery -->
        <div class="product-gallery" style="text-align:center;width:100%;margin-bottom:60px;">
            <div style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,0.08);position:relative;">
                <img id="pd-main-img"
                     src="<?php echo esc_url($images[0]['url']); ?>"
                     alt="<?php echo esc_attr($images[0]['alt']); ?>"
                     style="width:100%;height:auto;display:block;transition:opacity 0.4s ease;">

                <!-- Auto-slide indicator dots -->
                <div style="position:absolute;bottom:12px;left:50%;transform:translateX(-50%);display:flex;gap:6px;">
                    <?php for ($i = 0; $i < count($images); $i++) : ?>
                        <span class="pd-dot" data-index="<?php echo $i; ?>"
                              style="width:8px;height:8px;border-radius:50%;background:<?php echo $i === 0 ? 'var(--theme-accent)' : 'rgba(255,255,255,0.6)'; ?>;cursor:pointer;transition:background 0.3s;display:inline-block;"></span>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- Thumbnails -->
            <div style="display:flex;gap:10px;justify-content:center;margin-top:15px;">
                <?php foreach ($images as $idx => $img) : ?>
                    <img src="<?php echo esc_url($img['url']); ?>"
                         alt="<?php echo esc_attr($img['alt']); ?>"
                         class="pd-thumb <?php echo $idx === 0 ? 'active' : ''; ?>"
                         data-index="<?php echo $idx; ?>"
                         style="width:80px;height:60px;object-fit:cover;border-radius:8px;border:2px solid <?php echo $idx === 0 ? 'var(--theme-accent)' : '#e5e7eb'; ?>;cursor:pointer;transition:border-color 0.3s;">
                <?php endforeach; ?>
            </div>

            <!-- Auto-slide progress bar -->
            <div style="margin-top:10px;height:3px;background:#e5e7eb;border-radius:3px;overflow:hidden;">
                <div id="pd-progress-bar" style="height:100%;width:0%;background:var(--theme-accent);transition:width 5s linear;border-radius:3px;"></div>
            </div>
        </div>

        <!-- Info -->
        <div class="product-info" style="width:100%;text-align:center;">
            <h1 style="font-size:32px;color:#1e293b;margin-bottom:10px;">Common Mode Choke - CMC Series</h1>
            <p style="color:#64748b;font-size:16px;line-height:1.7;margin-bottom:30px;">
                High impedance common mode choke for EMC filtering in power lines and data cables.
                Designed for superior noise suppression in industrial and automotive applications.
            </p>

            <!-- Key Features -->
            <div style="background:#f8fafc;padding:25px;border-radius:12px;margin-bottom:30px;">
                <h3 style="font-size:18px;color:#1e293b;margin-bottom:15px;">Key Features</h3>
                <ul style="color:#475569;line-height:2;list-style:none;padding:0;">
                    <li>✓ High impedance for effective noise suppression</li>
                    <li>✓ Wide frequency range: 100kHz – 100MHz</li>
                    <li>✓ Low DC resistance for minimal power loss</li>
                    <li>✓ RoHS compliant materials</li>
                    <li>✓ Custom designs available</li>
                </ul>
            </div>

            <!-- Specifications -->
            <div style="margin-bottom:30px;">
                <h3 style="font-size:18px;color:#1e293b;margin-bottom:15px;">Specifications</h3>
                <table style="width:100%;border-collapse:collapse;">
                    <tr style="border-bottom:1px solid #e5e7eb;">
                        <td style="padding:12px 0;color:#64748b;">Inductance</td>
                        <td style="padding:12px 0;color:#1e293b;font-weight:500;">1mH – 47mH</td>
                    </tr>
                    <tr style="border-bottom:1px solid #e5e7eb;">
                        <td style="padding:12px 0;color:#64748b;">Current Rating</td>
                        <td style="padding:12px 0;color:#1e293b;font-weight:500;">0.5A – 30A</td>
                    </tr>
                    <tr style="border-bottom:1px solid #e5e7eb;">
                        <td style="padding:12px 0;color:#64748b;">Impedance @ 100MHz</td>
                        <td style="padding:12px 0;color:#1e293b;font-weight:500;">1000Ω – 5000Ω</td>
                    </tr>
                    <tr style="border-bottom:1px solid #e5e7eb;">
                        <td style="padding:12px 0;color:#64748b;">Operating Temperature</td>
                        <td style="padding:12px 0;color:#1e293b;font-weight:500;">–40°C to +125°C</td>
                    </tr>
                    <tr>
                        <td style="padding:12px 0;color:#64748b;">Package Type</td>
                        <td style="padding:12px 0;color:#1e293b;font-weight:500;">Through-hole / SMD</td>
                    </tr>
                </table>
            </div>

            <!-- CTA Buttons -->
            <div style="display:flex;gap:15px;justify-content:center;">
                <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="btn btn-primary" style="padding:15px 40px;font-size:16px;">Request Quote</a>
                <a href="#" class="btn btn-outline" style="padding:15px 40px;font-size:16px;" data-bs-toggle="modal" data-bs-target="#datasheetModal">Download Datasheet</a>
            </div>
        </div>

    </div>
</section>

<!-- Related Products -->
<section class="pd-related">
    <div class="container">
        <h2>Related Products</h2>
        <div class="pd-related-grid">
            <div class="pd-related-card">
                <div class="pd-related-img">
                    <img src="<?php echo esc_url($images[0]['url']); ?>" alt="Output Choke">
                </div>
                <div class="pd-related-body">
                    <h3>Output Choke - OC Series</h3>
                    <p>High current output chokes for DC/DC converters</p>
                    <a href="<?php echo esc_url(home_url('/products/inductors/')); ?>" class="btn-pd-outline">View Details</a>
                </div>
            </div>
            <div class="pd-related-card">
                <div class="pd-related-img">
                    <img src="<?php echo esc_url($images[1]['url']); ?>" alt="Power Inductor">
                </div>
                <div class="pd-related-body">
                    <h3>Power Inductor - PI Series</h3>
                    <p>High efficiency power inductors for switch mode supplies</p>
                    <a href="<?php echo esc_url(home_url('/products/inductors/')); ?>" class="btn-pd-outline">View Details</a>
                </div>
            </div>
            <div class="pd-related-card">
                <div class="pd-related-img">
                    <img src="<?php echo esc_url($images[2]['url']); ?>" alt="Toroidal Inductor">
                </div>
                <div class="pd-related-body">
                    <h3>Toroidal Inductor - TI Series</h3>
                    <p>Low EMI toroidal inductors for audio equipment</p>
                    <a href="<?php echo esc_url(home_url('/products/inductors/')); ?>" class="btn-pd-outline">View Details</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Datasheet Modal -->
<div class="modal fade" id="datasheetModal" tabindex="-1" aria-labelledby="datasheetModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="border-bottom:1px solid #e5e7eb;">
                <h5 class="modal-title fw-semibold" id="datasheetModalLabel" style="color:#1e293b;">
                    📄 Download Datasheet — CMC Series
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding:30px;">
                <p style="color:#475569;margin-bottom:20px;">
                    Enter your name and email to receive the Datasheet for CMC Series
                </p>
                <div id="ds-error" style="display:none;background:#fef2f2;color:#dc2626;padding:10px 14px;border-radius:8px;margin-bottom:15px;font-size:14px;"></div>
                <div style="margin-bottom:15px;">
                    <label style="display:block;font-weight:500;color:#1e293b;margin-bottom:6px;">Full Name</label>
                    <input type="text" id="ds-name" placeholder="e.g. John Smith"
                           style="width:100%;padding:10px 14px;border:1px solid #cbd5e1;border-radius:8px;font-size:15px;outline:none;">
                </div>
                <div style="margin-bottom:5px;">
                    <label style="display:block;font-weight:500;color:#1e293b;margin-bottom:6px;">Email</label>
                    <input type="email" id="ds-email" placeholder="example@company.com"
                           style="width:100%;padding:10px 14px;border:1px solid #cbd5e1;border-radius:8px;font-size:15px;outline:none;">
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #e5e7eb;padding:20px 30px;gap:10px;">
                <button type="button" class="btn btn-outline" data-bs-dismiss="modal" style="padding:10px 24px;">Cancel</button>
                <button type="button" class="btn btn-primary" id="ds-submit" style="padding:10px 24px;">📥 Download Datasheet</button>
            </div>
        </div>
    </div>
</div>
<?php
// Find linked product for CMC Series
$cmc_product = get_posts(['post_type' => 'product', 's' => 'CMC Series', 'posts_per_page' => 1]);
$cmc_product_id = $cmc_product ? $cmc_product[0]->ID : $page_id;
$cmc_datasheet_url = get_post_meta($cmc_product_id, 'pd_datasheet', true);
if (!$cmc_datasheet_url && $cmc_product) {
    $cmc_datasheet_url = add_query_arg('get_datasheet', '1', get_permalink($cmc_product_id));
}
?>
<script>
(function() {
    var productId = <?php echo json_encode($cmc_product_id); ?>;
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
            submitBtn.textContent = "⏳ Saving...";

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
                    submitBtn.textContent = "📥 Download Datasheet";
                });
        });
    }
})();
</script>

<!-- Auto-slide + Click JS -->
<script>
(function () {
    var images = <?php echo json_encode(array_column($images, 'url')); ?>;
    var current = 0;
    var total = images.length;
    var interval = 5000; // 5 วินาที
    var mainImg = document.getElementById("pd-main-img");
    var thumbs = document.querySelectorAll(".pd-thumb");
    var dots = document.querySelectorAll(".pd-dot");
    var bar = document.getElementById("pd-progress-bar");
    var timer = null;

    function goTo(idx) {
        current = (idx + total) % total;

        // เปลี่ยนภาพหลัก (fade)
        mainImg.style.opacity = "0";
        setTimeout(function () {
            mainImg.src = images[current];
            mainImg.style.opacity = "1";
        }, 200);

        // อัปเดต thumbnail borders
        thumbs.forEach(function (t, i) {
            t.style.borderColor = i === current ? "var(--theme-accent)" : "#e5e7eb";
        });

        // อัปเดต dots
        dots.forEach(function (d, i) {
            d.style.background = i === current ? "var(--theme-accent)" : "rgba(255,255,255,0.6)";
        });

        // reset progress bar
        bar.style.transition = "none";
        bar.style.width = "0%";
        setTimeout(function () {
            bar.style.transition = "width " + (interval / 1000) + "s linear";
            bar.style.width = "100%";
        }, 50);
    }

    var paused = false;
    var pausedAt = 0;       // เวลาที่ pause (ms นับจากเริ่ม slide)
    var slideStartTime = 0; // timestamp ที่เริ่ม slide ปัจจุบัน

    function startAuto(remaining) {
        clearInterval(timer);
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

        // หยุด progress bar — จับ width ปัจจุบันแล้วหยุด transition
        var currentWidth = parseFloat(getComputedStyle(bar).width);
        var totalWidth   = parseFloat(getComputedStyle(bar.parentNode).width);
        var pct = totalWidth > 0 ? (currentWidth / totalWidth) * 100 : 0;
        bar.style.transition = "none";
        bar.style.width = pct + "%";

        // คำนวณเวลาที่เหลือ
        var elapsed = Date.now() - slideStartTime;
        pausedAt = interval - elapsed;
        if (pausedAt < 0) pausedAt = 0;
    }

    function resumeAuto() {
        if (!paused) return;
        paused = false;

        // ต่อ progress bar จากจุดที่หยุด
        var currentPct = parseFloat(bar.style.width) || 0;
        var remaining  = pausedAt;
        setTimeout(function () {
            bar.style.transition = "width " + (remaining / 1000) + "s linear";
            bar.style.width = "100%";
        }, 50);

        startAuto(remaining);
    }

    // Hover บนกล่อง gallery ทั้งหมด (ภาพหลัก + thumbnails)
    var gallery = document.querySelector(".product-gallery");
    if (gallery) {
        gallery.addEventListener("mouseenter", pauseAuto);
        gallery.addEventListener("mouseleave", resumeAuto);
    }

    // Click on thumbnail
    thumbs.forEach(function (t) {
        t.addEventListener("click", function () {
            var idx = parseInt(this.getAttribute("data-index"));
            paused = false; // force reset pause state
            goTo(idx);
            startAuto();
        });
    });

    // Click on dots
    dots.forEach(function (d) {
        d.addEventListener("click", function () {
            var idx = parseInt(this.getAttribute("data-index"));
            paused = false;
            goTo(idx);
            startAuto();
        });
    });

    // Start
    goTo(0);
    startAuto();
})();
</script>

<?php get_footer(); ?>
