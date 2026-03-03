<?php
// The main template file — Full Bootstrap layout
get_header();
?>

<style>
    .hero-section { background-color: var(--theme-primary); min-height: 500px; }
    .cta-section { background-color: var(--theme-primary); }
    .feature-icon { font-size: 3rem; line-height: 1; }
    .stats-counter { font-size: 2.5rem; font-weight: 700; color: var(--theme-accent); }
</style>

<!-- ===================== HERO ===================== -->
<section class="hero-section d-flex align-items-center">
    <div class="container py-5">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <h1 class="display-4 fw-bold text-white mb-5">KV Electronics | Home</h1>
                <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                    <a href="/products" class="btn btn-light btn-lg px-5 fw-semibold text-primary">View Products</a>
                    <a href="/contact" class="btn btn-outline-light btn-lg px-5 fw-semibold">Contact Us</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===================== PRODUCT CATEGORIES ===================== -->
<section class="py-5" style="background-color:#f8fafc;">
    <div class="container py-4">
        <h2 class="text-center fw-bold mb-5" style="font-size:2.25rem;">Our Product Categories</h2>
        <div class="row g-4">
            <!-- Inductors -->
            <div class="col-md-4">
                <div class="product-card card h-100 shadow-sm border-0">
                    <img src="https://schottmagnetics.com/wp-content/uploads/2021/07/Toroid-Inductor-Choke3-1024x768-1.jpg"
                         alt="Inductors" class="card-img-top">
                    <div class="card-body p-4 d-flex flex-column">
                        <h3 class="card-title h5 fw-bold">Inductors</h3>
                        <p class="card-text text-muted flex-grow-1">
                            Common mode chokes, Output chokes, and various inductor solutions for power electronics
                        </p>
                        <a href="/products/inductors" class="btn btn-outline-primary mt-3 align-self-start">Learn More →</a>
                    </div>
                </div>
            </div>
            <!-- Transformers -->
            <div class="col-md-4">
                <div class="product-card card h-100 shadow-sm border-0">
                    <img src="https://schottmagnetics.com/wp-content/uploads/2021/07/Flyback-Transformer4-1024x768-1.jpg"
                         alt="Transformers" class="card-img-top">
                    <div class="card-body p-4 d-flex flex-column">
                        <h3 class="card-title h5 fw-bold">Transformers</h3>
                        <p class="card-text text-muted flex-grow-1">
                            Power transformers, Potential transformers designed for reliability and efficiency
                        </p>
                        <a href="/products/transformers" class="btn btn-outline-primary mt-3 align-self-start">Learn More →</a>
                    </div>
                </div>
            </div>
            <!-- Antennas -->
            <div class="col-md-4">
                <div class="product-card card h-100 shadow-sm border-0">
                    <img src="https://schottmagnetics.com/wp-content/uploads/2021/07/Air-Coils4.jpg"
                         alt="Antennas" class="card-img-top">
                    <div class="card-body p-4 d-flex flex-column">
                        <h3 class="card-title h5 fw-bold">Antennas</h3>
                        <p class="card-text text-muted flex-grow-1">
                            High-performance antenna solutions for communication and wireless applications
                        </p>
                        <a href="/products/antennas" class="btn btn-outline-primary mt-3 align-self-start">Learn More →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===================== STATS ===================== -->
<section class="py-5 bg-primary text-white">
    <div class="container">
        <div class="row g-4 text-center">
            <?php
            // Pull stats from database — with auto-calc support
            $years_auto    = get_option('site_years_auto',    '0');
            $products_auto = get_option('site_products_auto', '0');
            $founded_year  = (int) get_option('site_founded_year', 1988);
            $years_exp = ($years_auto === '1' && $founded_year > 0)
                ? max(0, (int)date('Y') - $founded_year)
                : (int) get_option('site_years_experience', 20);
            if ($products_auto === '1') {
                $pc = wp_count_posts('product');
                $total_prod = isset($pc->publish) ? (int)$pc->publish : 0;
            } else {
                $total_prod = (int) get_option('site_total_products', 500);
            }
            $countries   = get_option('site_countries_served', 50);
            $happy_cust  = get_option('site_happy_customers', 1000);
            ?>
            <div class="col-6 col-md-3">
                <div class="fw-bold mb-1" style="font-size:2.5rem;"><?php echo esc_html($years_exp); ?>+</div>
                <div class="opacity-75">Years Experience</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="fw-bold mb-1" style="font-size:2.5rem;"><?php echo esc_html($total_prod); ?>+</div>
                <div class="opacity-75">Products</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="fw-bold mb-1" style="font-size:2.5rem;"><?php echo esc_html($countries); ?>+</div>
                <div class="opacity-75">Countries</div>
            </div>
            <div class="col-6 col-md-3">
                <div class="fw-bold mb-1" style="font-size:2.5rem;"><?php echo esc_html($happy_cust); ?>+</div>
                <div class="opacity-75">Happy Clients</div>
            </div>
        </div>
    </div>
</section>

<!-- ===================== WHY CHOOSE US ===================== -->
<section class="py-5">
    <div class="container py-4">
        <h2 class="text-center fw-bold mb-4" style="font-size:2.25rem;">Why choose us</h2>
        <div class="mx-auto" style="max-width:980px;">
            <p class="text-center mb-3" style="font-size:1.25rem;line-height:1.75;color:#334155;">
                KV Electronics is more than a supplier—we are a long-term technical partner.
            </p>
            <p class="text-center mb-0" style="font-size:1.2rem;line-height:1.85;color:#64748b;">
                We support customers from design through mass production, ensuring stable quality, fast response, and continuous improvement.
            </p>
        </div>
    </div>
</section>

<!-- ===================== CTA ===================== -->
<section class="cta-section py-5 text-white text-center">
    <div class="container py-3">
        <h2 class="fw-bold mb-3">Ready to Get Started?</h2>
        <p class="lead mb-4 opacity-90">Contact us today for custom solutions and quotations</p>
        <a href="/contact" class="btn btn-light btn-lg px-5 fw-semibold text-primary">Get in Touch</a>
    </div>
</section>

<?php get_footer(); ?>