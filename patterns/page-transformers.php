<?php
/**
 * Title: 📄 Page: Transformers (Product Category)
 * Slug: my-custom-theme/page-transformers
 * Categories: my-special-design
 * Description: หน้า Transformers - รายการสินค้า Transformers พร้อม Subcategory Navigation
 */
?>
<!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group">

<!-- Page Header -->
<!-- wp:cover {"overlayColor":"primary","minHeight":220,"isDark":true,"style":{"spacing":{"padding":{"top":"60px","bottom":"60px"}}}} -->
<div class="wp-block-cover is-dark" style="padding-top:60px;padding-bottom:60px;min-height:220px"><span aria-hidden="true" class="wp-block-cover__background has-primary-background-color has-background-dim-100 has-background-dim"></span><div class="wp-block-cover__inner-container">
<!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontWeight":"700","fontSize":"42px"},"elements":{"link":{"color":{"text":"var:preset|color|white"}}}},"textColor":"white"} -->
<h1 class="wp-block-heading has-text-align-center has-white-color has-text-color has-link-color" style="font-size:42px;font-weight:700">Transformers</h1>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","style":{"elements":{"link":{"color":{"text":"#ffffffcc"}}}},"fontSize":"small"} -->
<p class="has-text-align-center has-link-color has-small-font-size" style="color:#ffffffcc"><a href="/" style="color:#ffffffcc;text-decoration:none;">Home</a> / <a href="/products/" style="color:#ffffffcc;text-decoration:none;">Products</a> / <strong style="color:#fff">Transformers</strong></p>
<!-- /wp:paragraph -->
</div></div>
<!-- /wp:cover -->

<!-- Subcategory Navigation -->
<!-- wp:html -->
<div class="subcategory-nav-section" style="background:#f8fafc;padding:20px 0;margin-bottom:0;">
    <div class="container">
        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
            <a href="#" class="subcategory-pill active" style="padding:10px 20px;background:var(--theme-primary);border-radius:25px;color:#fff;font-weight:500;border:1px solid var(--theme-primary);text-decoration:none;font-size:14px;">All Transformers</a>
            <a href="#" class="subcategory-pill" style="padding:10px 20px;background:#fff;border-radius:25px;color:#374151;font-weight:500;border:1px solid #e2e8f0;text-decoration:none;font-size:14px;">Power Transformers</a>
            <a href="#" class="subcategory-pill" style="padding:10px 20px;background:#fff;border-radius:25px;color:#374151;font-weight:500;border:1px solid #e2e8f0;text-decoration:none;font-size:14px;">Potential Transformers</a>
            <a href="#" class="subcategory-pill" style="padding:10px 20px;background:#fff;border-radius:25px;color:#374151;font-weight:500;border:1px solid #e2e8f0;text-decoration:none;font-size:14px;">Isolation Transformers</a>
            <a href="#" class="subcategory-pill" style="padding:10px 20px;background:#fff;border-radius:25px;color:#374151;font-weight:500;border:1px solid #e2e8f0;text-decoration:none;font-size:14px;">Current Transformers</a>
        </div>
    </div>
</div>
<!-- /wp:html -->

<!-- Product List -->
<!-- wp:html -->
<section style="padding:60px 0;">
    <div class="container">
        <div class="row g-4">

            <!-- Product 1: Power Transformer -->
            <div class="col-12 col-md-6 col-lg-4">
            <div class="product-item">
                <img src="https://schottmagnetics.com/wp-content/uploads/2021/07/Flyback-Transformer4-1024x768-1.jpg" alt="Power Transformer">
                <div class="product-info">
                    <h3>Power Transformer - PT Series</h3>
                    <p>High efficiency power transformers for AC/DC power conversion and distribution systems.</p>
                    <table class="specs-table">
                        <tr><td><strong>Power Rating:</strong></td><td>5W - 5kW</td></tr>
                        <tr><td><strong>Frequency:</strong></td><td>50Hz - 500kHz</td></tr>
                    </table>
                    <a href="#" class="btn btn-outline">View Details</a>
                </div>
            </div>
            </div>

            <!-- Product 2: Flyback Transformer -->
            <div class="col-12 col-md-6 col-lg-4">
            <div class="product-item">
                <img src="https://schottmagnetics.com/wp-content/uploads/2021/07/Flyback-Transformer4-1024x768-1.jpg" alt="Flyback Transformer">
                <div class="product-info">
                    <h3>Flyback Transformer - FBT Series</h3>
                    <p>Compact flyback transformers for isolated DC/DC converters and adapters.</p>
                    <table class="specs-table">
                        <tr><td><strong>Power Rating:</strong></td><td>1W - 150W</td></tr>
                        <tr><td><strong>Efficiency:</strong></td><td>&gt; 90%</td></tr>
                    </table>
                    <a href="#" class="btn btn-outline">View Details</a>
                </div>
            </div>
            </div>

            <!-- Product 3: Isolation Transformer -->
            <div class="col-12 col-md-6 col-lg-4">
            <div class="product-item">
                <img src="https://schottmagnetics.com/wp-content/uploads/2021/07/Flyback-Transformer4-1024x768-1.jpg" alt="Isolation Transformer">
                <div class="product-info">
                    <h3>Isolation Transformer - ISO Series</h3>
                    <p>Medical and industrial grade isolation transformers with reinforced insulation.</p>
                    <table class="specs-table">
                        <tr><td><strong>Isolation:</strong></td><td>4kVAC / 5.6kVDC</td></tr>
                        <tr><td><strong>Standard:</strong></td><td>IEC 60601 / UL</td></tr>
                    </table>
                    <a href="#" class="btn btn-outline">View Details</a>
                </div>
            </div>
            </div>

            <!-- Product 4: Current Transformer -->
            <div class="col-12 col-md-6 col-lg-4">
            <div class="product-item">
                <img src="https://schottmagnetics.com/wp-content/uploads/2021/07/Toroid-Inductor-Choke3-1024x768-1.jpg" alt="Current Transformer">
                <div class="product-info">
                    <h3>Current Transformer - CT Series</h3>
                    <p>Precision current sensing transformers for metering and protection applications.</p>
                    <table class="specs-table">
                        <tr><td><strong>Ratio:</strong></td><td>1:50 - 1:5000</td></tr>
                        <tr><td><strong>Accuracy:</strong></td><td>Class 0.5 / 1.0</td></tr>
                    </table>
                    <a href="#" class="btn btn-outline">View Details</a>
                </div>
            </div>
            </div>

            <!-- Product 5: Gate Drive Transformer -->
            <div class="col-12 col-md-6 col-lg-4">
            <div class="product-item">
                <img src="https://schottmagnetics.com/wp-content/uploads/2021/07/Toroid-Inductor-Choke3-1024x768-1.jpg" alt="Gate Drive Transformer">
                <div class="product-info">
                    <h3>Gate Drive Transformer - GDT Series</h3>
                    <p>Fast switching gate drive transformers for MOSFET and IGBT driver circuits.</p>
                    <table class="specs-table">
                        <tr><td><strong>Rise Time:</strong></td><td>&lt; 50ns</td></tr>
                        <tr><td><strong>Isolation:</strong></td><td>2.5kVDC</td></tr>
                    </table>
                    <a href="#" class="btn btn-outline">View Details</a>
                </div>
            </div>
            </div>

            <!-- Product 6: Potential Transformer -->
            <div class="col-12 col-md-6 col-lg-4">
            <div class="product-item">
                <img src="https://schottmagnetics.com/wp-content/uploads/2021/07/Flyback-Transformer4-1024x768-1.jpg" alt="Potential Transformer">
                <div class="product-info">
                    <h3>Potential Transformer - VT Series</h3>
                    <p>High accuracy voltage sensing transformers for energy metering systems.</p>
                    <table class="specs-table">
                        <tr><td><strong>Input Voltage:</strong></td><td>100V - 600V AC</td></tr>
                        <tr><td><strong>Accuracy:</strong></td><td>Class 0.2 / 0.5</td></tr>
                    </table>
                    <a href="#" class="btn btn-outline">View Details</a>
                </div>
            </div>
            </div>

        </div>
    </div>
</section>

<!-- /wp:html -->

<!-- CTA Section -->
<!-- wp:cover {"overlayColor":"primary","minHeight":200,"isDark":true,"style":{"spacing":{"padding":{"top":"60px","bottom":"60px"}}}} -->
<div class="wp-block-cover is-dark" style="padding-top:60px;padding-bottom:60px;min-height:200px"><span aria-hidden="true" class="wp-block-cover__background has-primary-background-color has-background-dim-100 has-background-dim"></span><div class="wp-block-cover__inner-container">
<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontWeight":"700","fontSize":"32px"},"elements":{"link":{"color":{"text":"var:preset|color|white"}}}},"textColor":"white"} -->
<h2 class="wp-block-heading has-text-align-center has-white-color has-text-color has-link-color" style="font-size:32px;font-weight:700">Need Custom Transformers?</h2>
<!-- /wp:heading -->
<!-- wp:paragraph {"align":"center","style":{"elements":{"link":{"color":{"text":"#ffffffcc"}}}},"fontSize":"medium"} -->
<p class="has-text-align-center has-link-color has-medium-font-size" style="color:#ffffffcc">We design and manufacture custom transformers to meet your exact specifications</p>
<!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"25px"}}}} -->
<div class="wp-block-buttons" style="margin-top:25px">
<!-- wp:button {"style":{"color":{"background":"#ffffff","text":"var(--theme-primary)"},"border":{"radius":"8px"},"typography":{"fontWeight":"600"}},"className":"shadow-sm"} -->
<div class="wp-block-button shadow-sm" style="font-weight:600"><a class="wp-block-button__link has-text-color has-background wp-element-button" href="/contact/" style="border-radius:8px;color:var(--theme-primary);background-color:#ffffff">Request a Quote</a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div></div>
<!-- /wp:cover -->

</div>
<!-- /wp:group -->
