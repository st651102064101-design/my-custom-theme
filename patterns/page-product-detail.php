<?php
/**
 * Title: 📄 Page: Product Detail (CMC Series)
 * Slug: my-custom-theme/page-product-detail
 * Categories: my-special-design
 * Description: หน้า Product Detail - Common Mode Choke CMC Series พร้อม Gallery, Specs, Related Products
 */
?>
<!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group">

<!-- Breadcrumb -->
<!-- wp:html -->
<section class="page-header" style="padding:20px 24px;background:#f8fafc;">
    <nav aria-label="breadcrumb" style="display:flex;align-items:center;justify-content:center;gap:6px;flex-wrap:nowrap;font-size:14px;color:#64748b;margin:0;"><a href="/" style="color:var(--theme-primary);white-space:nowrap;">Home</a><span>/</span><a href="/products/" style="color:var(--theme-primary);white-space:nowrap;">Products</a><span>/</span><a href="/products/inductors/" style="color:var(--theme-primary);white-space:nowrap;">Inductors</a><span>/</span><span style="color:#1e293b;white-space:nowrap;">CMC Series</span></nav>
</section>
<!-- /wp:html -->

<!-- Product Detail Section -->
<!-- wp:html -->
<section class="product-detail" style="padding:60px 0;display:flex;justify-content:center;width:100%;">
    <div style="display:flex;flex-direction:column;align-items:center;max-width:900px;width:100%;padding:0 20px;">

        <!-- Gallery -->
        <div class="product-gallery" style="text-align:center;width:100%;margin-bottom:60px;">
            <div style="background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 15px rgba(0,0,0,0.08);">
                <img id="pd-main-img" src="https://schottmagnetics.com/wp-content/uploads/2021/07/Toroid-Inductor-Choke3-1024x768-1.jpg" alt="Common Mode Choke" style="width:100%;height:auto;display:block;">
            </div>
            <div style="display:flex;gap:10px;justify-content:center;margin-top:15px;">
                <img src="https://schottmagnetics.com/wp-content/uploads/2021/07/Toroid-Inductor-Choke3-1024x768-1.jpg" alt="Thumbnail 1" class="pd-thumb active" style="width:80px;height:60px;object-fit:cover;border-radius:8px;border:2px solid var(--theme-primary);cursor:pointer;">
                <img src="https://schottmagnetics.com/wp-content/uploads/2021/07/Flyback-Transformer4-1024x768-1.jpg" alt="Thumbnail 2" class="pd-thumb" style="width:80px;height:60px;object-fit:cover;border-radius:8px;border:2px solid #e5e7eb;cursor:pointer;">
                <img src="https://schottmagnetics.com/wp-content/uploads/2021/07/Air-Coils4.jpg" alt="Thumbnail 3" class="pd-thumb" style="width:80px;height:60px;object-fit:cover;border-radius:8px;border:2px solid #e5e7eb;cursor:pointer;">
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
                <a href="/contact/" class="btn btn-primary" style="padding:15px 40px;font-size:16px;">Request Quote</a>
                <a href="#" class="btn btn-outline" style="padding:15px 40px;font-size:16px;">Download Datasheet</a>
            </div>
        </div>

    </div>
</section>
<script>
document.querySelectorAll(".pd-thumb").forEach(function(thumb){
    thumb.addEventListener("click",function(){
        document.getElementById("pd-main-img").src=this.src;
        document.querySelectorAll(".pd-thumb").forEach(function(t){t.style.borderColor="#e5e7eb"});
        this.style.borderColor="var(--theme-primary)";
    });
});
</script>
<!-- /wp:html -->

<!-- Related Products -->
<!-- wp:html -->
<section class="pd-related">
    <div class="container">
        <h2>Related Products</h2>
        <div class="pd-related-grid">

            <div class="pd-related-card">
                <div class="pd-related-img">
                    <img src="https://schottmagnetics.com/wp-content/uploads/2021/07/Toroid-Inductor-Choke3-1024x768-1.jpg" alt="Output Choke">
                </div>
                <div class="pd-related-body">
                    <h3>Output Choke - OC Series</h3>
                    <p>High current output chokes for DC/DC converters</p>
                    <a href="/inductors/" class="btn-pd-outline">View Details</a>
                </div>
            </div>

            <div class="pd-related-card">
                <div class="pd-related-img">
                    <img src="https://schottmagnetics.com/wp-content/uploads/2021/07/Flyback-Transformer4-1024x768-1.jpg" alt="Power Inductor">
                </div>
                <div class="pd-related-body">
                    <h3>Power Inductor - PI Series</h3>
                    <p>High efficiency power inductors for switch mode supplies</p>
                    <a href="/inductors/" class="btn-pd-outline">View Details</a>
                </div>
            </div>

            <div class="pd-related-card">
                <div class="pd-related-img">
                    <img src="https://schottmagnetics.com/wp-content/uploads/2021/07/Air-Coils4.jpg" alt="Toroidal Inductor">
                </div>
                <div class="pd-related-body">
                    <h3>Toroidal Inductor - TI Series</h3>
                    <p>Low EMI toroidal inductors for audio equipment</p>
                    <a href="/inductors/" class="btn-pd-outline">View Details</a>
                </div>
            </div>

        </div>
    </div>
</section>
<!-- /wp:html -->

</div>
<!-- /wp:group -->
