<?php
/**
 * Title: 📄 Page: Products (Full Page)
 * Slug: my-custom-theme/page-products
 * Categories: my-special-design
 * Description: หน้า Products แบบเต็มหน้า - รวม Page Header, Product Categories, Applications, CTA
 * Keywords: products, สินค้า, page
 * Block Types: core/post-content
 * Post Types: page
 */
?>
<!-- wp:cover {"overlayColor":"primary","align":"full","style":{"spacing":{"padding":{"top":"60px","bottom":"60px"}}}} -->
<div class="wp-block-cover alignfull" style="padding-top:60px;padding-bottom:60px">
    <span aria-hidden="true" class="wp-block-cover__background has-primary-background-color has-background-dim-100 has-background-dim"></span>
    <div class="wp-block-cover__inner-container">
        <!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"42px"},"spacing":{"margin":{"bottom":"10px"}}},"textColor":"white"} -->
        <h1 class="wp-block-heading has-text-align-center has-white-color has-text-color" style="margin-bottom:10px;font-size:42px">Our Products</h1>
        <!-- /wp:heading -->

        <!-- wp:paragraph {"align":"center","style":{"elements":{"link":{"color":{"text":"var:preset|color|white"}}}},"textColor":"white"} -->
        <p class="has-text-align-center has-white-color has-text-color has-link-color"><a href="/">Home</a> / Products</p>
        <!-- /wp:paragraph -->
    </div>
</div>
<!-- /wp:cover -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"80px","bottom":"80px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:80px;padding-bottom:80px">
    <!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":"36px","fontWeight":"700"},"spacing":{"margin":{"bottom":"50px"}}}} -->
    <h2 class="wp-block-heading has-text-align-center" style="margin-bottom:50px;font-size:36px;font-weight:700">Product Categories</h2>
    <!-- /wp:heading -->

    <!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"30px"}}}} -->
    <div class="wp-block-columns">
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:group {"style":{"border":{"radius":"12px"},"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"}}},"backgroundColor":"white","className":"product-card"} -->
            <div class="wp-block-group product-card has-white-background-color has-background" style="border-radius:12px;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
                <!-- wp:image {"sizeSlug":"large","style":{"border":{"radius":{"topLeft":"12px","topRight":"12px"}}}} -->
                <figure class="wp-block-image size-large" style="border-top-left-radius:12px;border-top-right-radius:12px"><img src="https://schottmagnetics.com/wp-content/uploads/2021/07/Toroid-Inductor-Choke3-1024x768-1.jpg" alt="Inductors"/></figure>
                <!-- /wp:image -->

                <!-- wp:group {"style":{"spacing":{"padding":{"top":"25px","right":"25px","bottom":"25px","left":"25px"}}}} -->
                <div class="wp-block-group" style="padding-top:25px;padding-right:25px;padding-bottom:25px;padding-left:25px">
                    <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"24px"},"spacing":{"margin":{"bottom":"10px"}}}} -->
                    <h3 class="wp-block-heading" style="margin-bottom:10px;font-size:24px">Inductors</h3>
                    <!-- /wp:heading -->

                    <!-- wp:paragraph {"style":{"color":{"text":"#64748b"},"spacing":{"margin":{"bottom":"15px"}}}} -->
                    <p class="has-text-color" style="color:#64748b;margin-bottom:15px">High-performance inductors for power electronics, EMC filtering, and signal processing applications.</p>
                    <!-- /wp:paragraph -->

                    <!-- wp:list {"style":{"color":{"text":"#64748b"},"typography":{"fontSize":"14px"},"spacing":{"blockGap":"4px","margin":{"bottom":"20px"}}}} -->
                    <ul class="has-text-color" style="color:#64748b;margin-bottom:20px;font-size:14px">
                        <!-- wp:list-item -->
                        <li>Common Mode Chokes</li>
                        <!-- /wp:list-item -->

                        <!-- wp:list-item -->
                        <li>Output Chokes</li>
                        <!-- /wp:list-item -->

                        <!-- wp:list-item -->
                        <li>Power Inductors</li>
                        <!-- /wp:list-item -->

                        <!-- wp:list-item -->
                        <li>SMD Inductors</li>
                        <!-- /wp:list-item -->
                    </ul>
                    <!-- /wp:list -->

                    <!-- wp:buttons -->
                    <div class="wp-block-buttons">
                        <!-- wp:button {"className":"is-style-outline","style":{"border":{"radius":"6px"}}} -->
                        <div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/product-category/inductors/" style="border-radius:6px">View Products →</a></div>
                        <!-- /wp:button -->
                    </div>
                    <!-- /wp:buttons -->
                </div>
                <!-- /wp:group -->
            </div>
            <!-- /wp:group -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:group {"style":{"border":{"radius":"12px"},"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"}}},"backgroundColor":"white","className":"product-card"} -->
            <div class="wp-block-group product-card has-white-background-color has-background" style="border-radius:12px;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
                <!-- wp:image {"sizeSlug":"large","style":{"border":{"radius":{"topLeft":"12px","topRight":"12px"}}}} -->
                <figure class="wp-block-image size-large" style="border-top-left-radius:12px;border-top-right-radius:12px"><img src="https://schottmagnetics.com/wp-content/uploads/2021/07/Flyback-Transformer4-1024x768-1.jpg" alt="Transformers"/></figure>
                <!-- /wp:image -->

                <!-- wp:group {"style":{"spacing":{"padding":{"top":"25px","right":"25px","bottom":"25px","left":"25px"}}}} -->
                <div class="wp-block-group" style="padding-top:25px;padding-right:25px;padding-bottom:25px;padding-left:25px">
                    <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"24px"},"spacing":{"margin":{"bottom":"10px"}}}} -->
                    <h3 class="wp-block-heading" style="margin-bottom:10px;font-size:24px">Transformers</h3>
                    <!-- /wp:heading -->

                    <!-- wp:paragraph {"style":{"color":{"text":"#64748b"},"spacing":{"margin":{"bottom":"15px"}}}} -->
                    <p class="has-text-color" style="color:#64748b;margin-bottom:15px">Reliable transformers for power conversion, isolation, and voltage regulation applications.</p>
                    <!-- /wp:paragraph -->

                    <!-- wp:list {"style":{"color":{"text":"#64748b"},"typography":{"fontSize":"14px"},"spacing":{"blockGap":"4px","margin":{"bottom":"20px"}}}} -->
                    <ul class="has-text-color" style="color:#64748b;margin-bottom:20px;font-size:14px">
                        <!-- wp:list-item -->
                        <li>Power Transformers</li>
                        <!-- /wp:list-item -->

                        <!-- wp:list-item -->
                        <li>Potential Transformers</li>
                        <!-- /wp:list-item -->

                        <!-- wp:list-item -->
                        <li>Isolation Transformers</li>
                        <!-- /wp:list-item -->

                        <!-- wp:list-item -->
                        <li>Current Transformers</li>
                        <!-- /wp:list-item -->
                    </ul>
                    <!-- /wp:list -->

                    <!-- wp:buttons -->
                    <div class="wp-block-buttons">
                        <!-- wp:button {"className":"is-style-outline","style":{"border":{"radius":"6px"}}} -->
                        <div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/product-category/transformers/" style="border-radius:6px">View Products →</a></div>
                        <!-- /wp:button -->
                    </div>
                    <!-- /wp:buttons -->
                </div>
                <!-- /wp:group -->
            </div>
            <!-- /wp:group -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:group {"style":{"border":{"radius":"12px"},"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"}}},"backgroundColor":"white","className":"product-card"} -->
            <div class="wp-block-group product-card has-white-background-color has-background" style="border-radius:12px;padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">
                <!-- wp:image {"sizeSlug":"large","style":{"border":{"radius":{"topLeft":"12px","topRight":"12px"}}}} -->
                <figure class="wp-block-image size-large" style="border-top-left-radius:12px;border-top-right-radius:12px"><img src="https://schottmagnetics.com/wp-content/uploads/2021/07/Air-Coils4.jpg" alt="Antennas"/></figure>
                <!-- /wp:image -->

                <!-- wp:group {"style":{"spacing":{"padding":{"top":"25px","right":"25px","bottom":"25px","left":"25px"}}}} -->
                <div class="wp-block-group" style="padding-top:25px;padding-right:25px;padding-bottom:25px;padding-left:25px">
                    <!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"24px"},"spacing":{"margin":{"bottom":"10px"}}}} -->
                    <h3 class="wp-block-heading" style="margin-bottom:10px;font-size:24px">Antennas</h3>
                    <!-- /wp:heading -->

                    <!-- wp:paragraph {"style":{"color":{"text":"#64748b"},"spacing":{"margin":{"bottom":"15px"}}}} -->
                    <p class="has-text-color" style="color:#64748b;margin-bottom:15px">Advanced antenna solutions for wireless communication and IoT applications.</p>
                    <!-- /wp:paragraph -->

                    <!-- wp:list {"style":{"color":{"text":"#64748b"},"typography":{"fontSize":"14px"},"spacing":{"blockGap":"4px","margin":{"bottom":"20px"}}}} -->
                    <ul class="has-text-color" style="color:#64748b;margin-bottom:20px;font-size:14px">
                        <!-- wp:list-item -->
                        <li>PCB Antennas</li>
                        <!-- /wp:list-item -->

                        <!-- wp:list-item -->
                        <li>Chip Antennas</li>
                        <!-- /wp:list-item -->

                        <!-- wp:list-item -->
                        <li>External Antennas</li>
                        <!-- /wp:list-item -->

                        <!-- wp:list-item -->
                        <li>RFID Antennas</li>
                        <!-- /wp:list-item -->
                    </ul>
                    <!-- /wp:list -->

                    <!-- wp:buttons -->
                    <div class="wp-block-buttons">
                        <!-- wp:button {"className":"is-style-outline","style":{"border":{"radius":"6px"}}} -->
                        <div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/products/" style="border-radius:6px">View Products →</a></div>
                        <!-- /wp:button -->
                    </div>
                    <!-- /wp:buttons -->
                </div>
                <!-- /wp:group -->
            </div>
            <!-- /wp:group -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"80px","bottom":"80px"}}},"backgroundColor":"tertiary","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-tertiary-background-color has-background" style="padding-top:80px;padding-bottom:80px">
    <!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":"36px","fontWeight":"700"},"spacing":{"margin":{"bottom":"50px"}}}} -->
    <h2 class="wp-block-heading has-text-align-center" style="margin-bottom:50px;font-size:36px;font-weight:700">Applications</h2>
    <!-- /wp:heading -->

    <!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"30px"}}}} -->
    <div class="wp-block-columns">
        <!-- wp:column {"style":{"spacing":{"padding":{"top":"30px","bottom":"30px","left":"30px","right":"30px"}}}} -->
        <div class="wp-block-column" style="padding-top:30px;padding-right:30px;padding-bottom:30px;padding-left:30px">
            <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"48px"},"spacing":{"margin":{"bottom":"15px"}}}} -->
            <p class="has-text-align-center" style="margin-bottom:15px;font-size:48px">🚗</p>
            <!-- /wp:paragraph -->

            <!-- wp:heading {"textAlign":"center","level":4,"style":{"typography":{"fontSize":"20px"},"spacing":{"margin":{"bottom":"10px"}}}} -->
            <h4 class="wp-block-heading has-text-align-center" style="margin-bottom:10px;font-size:20px">Automotive</h4>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"align":"center","style":{"color":{"text":"#64748b"}}} -->
            <p class="has-text-align-center has-text-color" style="color:#64748b">EV charging, ADAS, infotainment systems</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column {"style":{"spacing":{"padding":{"top":"30px","bottom":"30px","left":"30px","right":"30px"}}}} -->
        <div class="wp-block-column" style="padding-top:30px;padding-right:30px;padding-bottom:30px;padding-left:30px">
            <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"48px"},"spacing":{"margin":{"bottom":"15px"}}}} -->
            <p class="has-text-align-center" style="margin-bottom:15px;font-size:48px">📡</p>
            <!-- /wp:paragraph -->

            <!-- wp:heading {"textAlign":"center","level":4,"style":{"typography":{"fontSize":"20px"},"spacing":{"margin":{"bottom":"10px"}}}} -->
            <h4 class="wp-block-heading has-text-align-center" style="margin-bottom:10px;font-size:20px">Telecommunications</h4>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"align":"center","style":{"color":{"text":"#64748b"}}} -->
            <p class="has-text-align-center has-text-color" style="color:#64748b">5G infrastructure, network equipment</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column {"style":{"spacing":{"padding":{"top":"30px","bottom":"30px","left":"30px","right":"30px"}}}} -->
        <div class="wp-block-column" style="padding-top:30px;padding-right:30px;padding-bottom:30px;padding-left:30px">
            <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"48px"},"spacing":{"margin":{"bottom":"15px"}}}} -->
            <p class="has-text-align-center" style="margin-bottom:15px;font-size:48px">🏭</p>
            <!-- /wp:paragraph -->

            <!-- wp:heading {"textAlign":"center","level":4,"style":{"typography":{"fontSize":"20px"},"spacing":{"margin":{"bottom":"10px"}}}} -->
            <h4 class="wp-block-heading has-text-align-center" style="margin-bottom:10px;font-size:20px">Industrial</h4>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"align":"center","style":{"color":{"text":"#64748b"}}} -->
            <p class="has-text-align-center has-text-color" style="color:#64748b">Automation, motor drives, power supplies</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column {"style":{"spacing":{"padding":{"top":"30px","bottom":"30px","left":"30px","right":"30px"}}}} -->
        <div class="wp-block-column" style="padding-top:30px;padding-right:30px;padding-bottom:30px;padding-left:30px">
            <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"48px"},"spacing":{"margin":{"bottom":"15px"}}}} -->
            <p class="has-text-align-center" style="margin-bottom:15px;font-size:48px">📱</p>
            <!-- /wp:paragraph -->

            <!-- wp:heading {"textAlign":"center","level":4,"style":{"typography":{"fontSize":"20px"},"spacing":{"margin":{"bottom":"10px"}}}} -->
            <h4 class="wp-block-heading has-text-align-center" style="margin-bottom:10px;font-size:20px">Consumer Electronics</h4>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"align":"center","style":{"color":{"text":"#64748b"}}} -->
            <p class="has-text-align-center has-text-color" style="color:#64748b">Smartphones, IoT devices, wearables</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->

<!-- wp:cover {"overlayColor":"primary","align":"full","style":{"spacing":{"padding":{"top":"60px","bottom":"0px"}}}} -->
<div class="wp-block-cover alignfull" style="padding-top:60px;padding-bottom:0px">
    <span aria-hidden="true" class="wp-block-cover__background has-primary-background-color has-background-dim-100 has-background-dim"></span>
    <div class="wp-block-cover__inner-container" style="padding-top:30px;padding-bottom:30px;">
        <!-- wp:heading {"textAlign":"center","style":{"typography":{"fontSize":"36px"},"spacing":{"margin":{"bottom":"0px"}}},"textColor":"white"} -->
        <h2 class="wp-block-heading has-text-align-center has-white-color has-text-color" style="margin-bottom:0px;font-size:36px">Need Custom Solutions?</h2>
        <!-- /wp:heading -->

        <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"18px"},"spacing":{"margin":{"bottom":"0px"}}},"textColor":"white"} -->
        <p class="has-text-align-center has-white-color has-text-color" style="margin-bottom:0px;font-size:18px">We offer custom design and manufacturing services to meet your specific requirements</p>
        <!-- /wp:paragraph -->

        <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"0px"}}}} -->
        <div class="wp-block-buttons" style="margin-top:0px;">
            <!-- wp:button {"backgroundColor":"white","textColor":"primary","style":{"border":{"radius":"6px"}}} -->
            <div class="wp-block-button"><a class="wp-block-button__link has-primary-color has-white-background-color has-text-color has-background wp-element-button" href="/contact" style="border-radius:6px">Request a Quote</a></div>
            <!-- /wp:button -->
        </div>
        <!-- /wp:buttons -->
    </div>
</div>
<!-- /wp:cover -->
