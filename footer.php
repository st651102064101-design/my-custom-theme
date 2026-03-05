<!-- ===================== FOOTER ===================== -->
<footer class="footer-dark pt-5 pb-4">
    <div class="container">
        <div class="row g-4 mb-4">
            <div class="col-sm-6 col-lg-3">
                <h5 class="fw-semibold mb-3"><?php echo esc_html(get_option('footer_col1_title','About Us')); ?></h5>
                <p class="small" style="color: #cbd5e1;">
                    <?php echo nl2br(esc_html(get_option('footer_about_text', 'Founded in 1988, KV Electronics Co., Ltd. has grown from a shared passion between two industry experts into a trusted manufacturer of high-quality magnetic components and electronic solutions.'))); ?>
                </p>
            </div>
            <div class="col-sm-6 col-lg-3">
                <h5 class="fw-semibold mb-3"><?php echo esc_html(get_option('footer_col2_title','Products')); ?></h5>
                <ul class="list-unstyled small" style="color: #cbd5e1;">
                    <?php
                    $sort_order = my_theme_get_product_category_order();
                    $footer_cats = get_terms([
                        'taxonomy'   => 'product_category',
                        'parent'     => 0,
                        'hide_empty' => false,
                        'orderby'    => 'id',
                        'order'      => $sort_order,
                    ]);
                    if ($footer_cats && !is_wp_error($footer_cats)) :
                        foreach ($footer_cats as $fcat) :
                        $fcat_link_raw = get_term_link($fcat);
                        $fcat_link = (!is_wp_error($fcat_link_raw) && $fcat_link_raw)
                            ? $fcat_link_raw
                            : home_url('/products/');
                    ?>
                    <li class="mb-1"><a href="<?php echo esc_url($fcat_link); ?>" style="color: #cbd5e1; text-decoration: none;"><?php echo esc_html($fcat->name); ?></a></li>
                    <?php
                        endforeach;
                    endif;
                    ?>
                </ul>
            </div>
            <div class="col-sm-6 col-lg-3">
                <h5 class="fw-semibold mb-3"><?php echo esc_html(get_option('footer_col3_title','Quick Links')); ?></h5>
                <ul class="list-unstyled small" style="color: #cbd5e1;">
                    <?php
                    $footer_links_raw = get_option('footer_quick_links', "About Us|/about\nContact|/contact");
                    $footer_link_lines = array_filter(array_map('trim', explode("\n", $footer_links_raw)));
                    foreach ($footer_link_lines as $footer_link_line) :
                        $parts = explode('|', $footer_link_line, 2);
                        if (count($parts) === 2) :
                            $link_label = trim($parts[0]);
                            $link_url   = trim($parts[1]);
                            // ถ้า URL เริ่มด้วย / ให้ใช้ home_url() เพื่อสร้าง absolute URL
                            $full_url = (strpos($link_url, 'http') === 0) ? $link_url : home_url($link_url);
                    ?>
                    <li class="mb-1"><a href="<?php echo esc_url($full_url); ?>" style="color: #cbd5e1; text-decoration: none;"><?php echo esc_html($link_label); ?></a></li>
                    <?php
                        endif;
                    endforeach;
                    ?>
                </ul>
            </div>
            <div class="col-sm-6 col-lg-3">
                <h5 class="fw-semibold mb-3"><?php echo esc_html(get_option('footer_col4_title','Contact Info')); ?></h5>
                <?php
                $footer_phone_raw   = function_exists('kv_get_site_phone_raw_display')
                    ? kv_get_site_phone_raw_display('+66 2 108 8521')
                    : trim((string) get_option('site_phone', get_theme_mod('site_phone', '+66 2 108 8521')));
                $footer_phone_label = $footer_phone_raw !== '' ? $footer_phone_raw : '+66 2 108 8521';
                $footer_phone_href  = preg_replace('/[^0-9+]/', '', (string) $footer_phone_raw);
                $footer_email       = get_option('site_email', get_theme_mod('site_email', 'info@company.com'));
                ?>
                <ul class="list-unstyled small" style="color: #cbd5e1;">
                    <li class="mb-2">📍 <?php echo esc_html(get_option('site_address', get_theme_mod('site_address', '123 Industrial Zone, Bangkok, Thailand'))); ?></li>
                </ul>
                <div style="display:flex;gap:16px;align-items:flex-start;margin-top:18px;">
                    <div>
                        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-bottom:12px;">
                            <?php if (!empty($footer_phone_href)) : ?>
                                <a href="tel:<?php echo esc_attr($footer_phone_href); ?>" style="display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border-radius:999px;background:#0f172a;border:1px solid #334155;color:#fff;text-decoration:none;font-size:13px;">📞 <?php echo esc_html($footer_phone_label); ?></a>
                            <?php endif; ?>
                            <?php if (!empty($footer_email)) : ?>
                                <a href="mailto:<?php echo esc_attr($footer_email); ?>" style="display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border-radius:999px;background:#0f172a;border:1px solid #334155;color:#fff;text-decoration:none;font-size:13px;">✉️ <?php echo esc_html($footer_email); ?></a>
                            <?php endif; ?>
                        </div>
                        <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                            <a href="https://line.me/ti/p/~kriangkrai2042" target="_blank" rel="noopener noreferrer" style="width:48px;height:48px;border-radius:50%;background:#06C755;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(6,199,85,0.35);transition:transform .2s;text-decoration:none;" aria-label="Chat on LINE" onmouseenter="this.style.transform='scale(1.12)'" onmouseleave="this.style.transform='scale(1)'">
                                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="#fff"><path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386a.63.63 0 0 1-.63-.629V8.108a.63.63 0 0 1 .63-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016a.63.63 0 0 1-.63.629.626.626 0 0 1-.51-.262l-2.397-3.265v2.898a.63.63 0 0 1-.63.629.627.627 0 0 1-.629-.629V8.108a.63.63 0 0 1 .63-.63c.2 0 .38.095.51.262l2.397 3.265V8.108a.63.63 0 0 1 .63-.63.63.63 0 0 1 .629.63v4.771zm-5.741 0a.63.63 0 0 1-1.26 0V8.108a.63.63 0 0 1 1.26 0v4.771zm-2.527.629H4.856a.63.63 0 0 1-.63-.629V8.108a.63.63 0 0 1 1.26 0v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629zM24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"></path></svg>
                            </a>
                            <a href="https://www.facebook.com/KVElectronicsTH/" target="_blank" rel="noopener noreferrer" style="width:48px;height:48px;border-radius:50%;background:#1877F2;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(24,119,242,0.35);transition:transform .2s;text-decoration:none;" aria-label="Open Facebook" onmouseenter="this.style.transform='scale(1.12)'" onmouseleave="this.style.transform='scale(1)'">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="#fff"><path d="M13.5 22v-8h2.7l.4-3h-3.1V9.1c0-.9.3-1.6 1.6-1.6h1.7V4.7c-.3 0-1.3-.1-2.4-.1-2.4 0-4 1.4-4 4.2V11H8v3h2.4v8h3.1z"></path></svg>
                            </a>
                            <a href="https://www.instagram.com/kvelectronicsth/" target="_blank" rel="noopener noreferrer" style="width:48px;height:48px;border-radius:50%;background:radial-gradient(circle at 30% 107%, rgb(253, 244, 151) 0%, rgb(253, 244, 151) 5%, rgb(253, 89, 73) 45%, rgb(214, 36, 159) 60%, rgb(40, 90, 235) 90%);display:flex;align-items:center;justify-content:center;box-shadow:rgba(214, 36, 159, 0.35) 0px 4px 14px;transition:transform 0.2s;text-decoration:none;" aria-label="Open Instagram" onmouseenter="this.style.transform='scale(1.12)'" onmouseleave="this.style.transform='scale(1)'">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="5" ry="5"></rect><circle cx="12" cy="12" r="4"></circle><circle cx="17.5" cy="6.5" r="1"></circle></svg>
                            </a>
                            <a href="https://www.linkedin.com/company/kv-electronics-co-ltd" target="_blank" rel="noopener noreferrer" style="width:48px;height:48px;border-radius:50%;background:rgb(10, 102, 194);display:flex;align-items:center;justify-content:center;box-shadow:rgba(10, 102, 194, 0.35) 0px 4px 14px;transition:transform 0.2s;text-decoration:none;" aria-label="Open LinkedIn" onmouseenter="this.style.transform='scale(1.12)'" onmouseleave="this.style.transform='scale(1)'">
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="#fff"><path d="M6.94 8.5a1.56 1.56 0 1 1 0-3.12 1.56 1.56 0 0 1 0 3.12zM5.5 9.75h2.9V19h-2.9V9.75zM10.2 9.75h2.78v1.26h.04c.39-.73 1.34-1.5 2.75-1.5 2.94 0 3.48 1.93 3.48 4.44V19h-2.9v-4.47c0-1.07-.02-2.45-1.49-2.45-1.5 0-1.73 1.17-1.73 2.37V19H10.2V9.75z"></path></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <hr class="footer-divider">
        <p class="text-center small mb-0 pt-3" style="color: #cbd5e1;">
            &copy; <?php echo date('Y'); ?> <?php echo esc_html(get_option('site_company_name', 'Electronic Components Co., Ltd.')); ?> <?php echo esc_html(get_option('site_copyright', 'All rights reserved.')); ?>
        </p>
    </div>
</footer>


<?php wp_footer(); ?>
</body>
</html>
