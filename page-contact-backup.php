<?php
/**
 * Template Name: Contact Page
 * Template Post Type: page
 *
 * Automatically used for pages with slug "contact".
 * All contact data is pulled live from wp_options (managed at Theme Settings).
 */

// ── Pull contact data from DB ──────────────────────────────────────────────
$pc_phone        = function_exists('kv_get_site_phone_raw_display') ? kv_get_site_phone_raw_display('+66 2 108 8521') : trim((string) get_option('site_phone', '+66 2 108 8521'));
$pc_fax          = kv_format_phone_th(get_option('site_fax',   ''));
$pc_email        = get_option('site_email',         'info@company.com');
$pc_email_sales  = get_option('site_email_sales',   'sales@company.com');
$pc_address_full = get_option('site_address_full',  "123 Industrial Zone\nBangna-Trad Road\nBangkok 10260, Thailand");
$pc_hours_wd     = get_option('site_hours_weekday', 'Monday – Friday: 8:00 AM – 5:00 PM');
$pc_hours_we     = get_option('site_hours_weekend', 'Saturday – Sunday: Closed');
$pc_map_embed    = get_option('site_map_embed', '');
$theme_primary   = get_option('theme_primary_color', '#0056d6');
$theme_accent    = get_option('theme_accent_color',  '#4ecdc4');

get_header();

// ── Page Banner ─────────────────────────────────────────────────────────────
get_template_part('parts/page-banner');
?>

<!-- ── Contact Section ──────────────────────────────────────────────────── -->
<section style="padding:60px 0;">
    <div class="container">
        <div class="row g-5">

            <!-- Left: Get in Touch Info -->
            <div class="col-lg-5">
                <h2 style="font-size:28px;margin-bottom:30px;color:#1e293b;">Get in Touch</h2>

                <!-- Quality Standards Badges -->
                <div style="margin-bottom:32px;padding:20px;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;">
                    <h5 style="margin:0 0 14px;font-size:14px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.5px;">Quality Standards</h5>
                    <div style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;">
                        <!-- ISO 9001 Badge -->
                        <div class="iso-badge" style="filter:grayscale(1);opacity:0.7;transition:filter .3s,opacity .3s;cursor:default;" onmouseenter="this.style.filter='grayscale(0)';this.style.opacity='1'" onmouseleave="this.style.filter='grayscale(1)';this.style.opacity='0.7'" title="ISO 9001:2015 Quality Management System">
                            <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 200 200" role="img" aria-label="ISO 9001:2015 Certified">
                                <circle cx="100" cy="100" r="95" fill="#fff" stroke="#1a5276" stroke-width="4"/>
                                <circle cx="100" cy="100" r="82" fill="none" stroke="#2980b9" stroke-width="2"/>
                                <text x="100" y="65" text-anchor="middle" font-family="Arial,sans-serif" font-size="18" font-weight="bold" fill="#1a5276">ISO</text>
                                <text x="100" y="95" text-anchor="middle" font-family="Arial,sans-serif" font-size="26" font-weight="bold" fill="#2980b9">9001</text>
                                <text x="100" y="118" text-anchor="middle" font-family="Arial,sans-serif" font-size="12" fill="#1a5276">:2015</text>
                                <text x="100" y="145" text-anchor="middle" font-family="Arial,sans-serif" font-size="10" fill="#7f8c8d">CERTIFIED</text>
                                <path d="M60 155 L100 170 L140 155" fill="none" stroke="#2980b9" stroke-width="2"/>
                            </svg>
                        </div>
                        <!-- ISO 14001 Badge -->
                        <div class="iso-badge" style="filter:grayscale(1);opacity:0.7;transition:filter .3s,opacity .3s;cursor:default;" onmouseenter="this.style.filter='grayscale(0)';this.style.opacity='1'" onmouseleave="this.style.filter='grayscale(1)';this.style.opacity='0.7'" title="ISO 14001:2015 Environmental Management System">
                            <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 200 200" role="img" aria-label="ISO 14001:2015 Certified">
                                <circle cx="100" cy="100" r="95" fill="#fff" stroke="#196f3d" stroke-width="4"/>
                                <circle cx="100" cy="100" r="82" fill="none" stroke="#27ae60" stroke-width="2"/>
                                <text x="100" y="65" text-anchor="middle" font-family="Arial,sans-serif" font-size="18" font-weight="bold" fill="#196f3d">ISO</text>
                                <text x="100" y="95" text-anchor="middle" font-family="Arial,sans-serif" font-size="24" font-weight="bold" fill="#27ae60">14001</text>
                                <text x="100" y="118" text-anchor="middle" font-family="Arial,sans-serif" font-size="12" fill="#196f3d">:2015</text>
                                <text x="100" y="145" text-anchor="middle" font-family="Arial,sans-serif" font-size="10" fill="#7f8c8d">CERTIFIED</text>
                                <path d="M60 155 L100 170 L140 155" fill="none" stroke="#27ae60" stroke-width="2"/>
                            </svg>
                        </div>
                        <!-- BOI Badge -->
                        <div class="iso-badge" style="filter:grayscale(1);opacity:0.7;transition:filter .3s,opacity .3s;cursor:default;" onmouseenter="this.style.filter='grayscale(0)';this.style.opacity='1'" onmouseleave="this.style.filter='grayscale(1)';this.style.opacity='0.7'" title="BOI Promoted — Thailand Board of Investment">
                            <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 200 200" role="img" aria-label="BOI Promoted">
                                <circle cx="100" cy="100" r="95" fill="#fff" stroke="#7b3f00" stroke-width="4"/>
                                <circle cx="100" cy="100" r="82" fill="none" stroke="#d4a017" stroke-width="2"/>
                                <text x="100" y="72" text-anchor="middle" font-family="Arial,sans-serif" font-size="26" font-weight="bold" fill="#d4a017">BOI</text>
                                <text x="100" y="100" text-anchor="middle" font-family="Arial,sans-serif" font-size="11" fill="#7b3f00">PROMOTED</text>
                                <text x="100" y="122" text-anchor="middle" font-family="Arial,sans-serif" font-size="9" fill="#7b3f00">THAILAND</text>
                                <text x="100" y="140" text-anchor="middle" font-family="Arial,sans-serif" font-size="8" fill="#94a3b8">BOARD OF INVESTMENT</text>
                                <path d="M65 155 L100 168 L135 155" fill="none" stroke="#d4a017" stroke-width="2"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <div style="display:flex;flex-direction:column;gap:28px;">

                    <!-- Address -->
                    <div style="display:flex;gap:16px;align-items:flex-start;">
                        <div style="width:50px;height:50px;min-width:50px;border-radius:50%;background:#e8f0fe;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">📍</div>
                        <div>
                            <h4 style="margin:0 0 6px;font-size:17px;color:#1e293b;">Address</h4>
                            <p style="margin:0;color:#64748b;line-height:1.6;"><?php echo nl2br(esc_html($pc_address_full)); ?></p>
                        </div>
                    </div>

                    <!-- Phone & Fax -->
                    <div style="display:flex;gap:16px;align-items:flex-start;">
                        <div style="width:50px;height:50px;min-width:50px;border-radius:50%;background:#e8f0fe;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">📞</div>
                        <div>
                            <h4 style="margin:0 0 6px;font-size:17px;color:#1e293b;">Phone</h4>
                            <p style="margin:0;color:#64748b;line-height:1.8;">
                                <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $pc_phone)); ?>" style="color:#64748b;text-decoration:none;"><?php echo esc_html($pc_phone); ?></a><br>
                                <?php if ($pc_fax) : ?>
                                <span><?php echo esc_html($pc_fax); ?> (Fax)</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <!-- Email -->
                    <div style="display:flex;gap:16px;align-items:flex-start;">
                        <div style="width:50px;height:50px;min-width:50px;border-radius:50%;background:#e8f0fe;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">✉️</div>
                        <div>
                            <h4 style="margin:0 0 6px;font-size:17px;color:#1e293b;">Email</h4>
                            <p style="margin:0;color:#64748b;line-height:1.8;">
                                <a href="mailto:<?php echo esc_attr($pc_email); ?>" style="color:#64748b;text-decoration:none;"><?php echo esc_html($pc_email); ?></a>
                            </p>
                        </div>
                    </div>

                    <!-- Business Hours -->
                    <div style="display:flex;gap:16px;align-items:flex-start;">
                        <div style="width:50px;height:50px;min-width:50px;border-radius:50%;background:#e8f0fe;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">🕐</div>
                        <div>
                            <h4 style="margin:0 0 6px;font-size:17px;color:#1e293b;">Business Hours</h4>
                            <p style="margin:0;color:#64748b;line-height:1.8;">
                                <?php echo esc_html($pc_hours_wd); ?><br>
                                <?php echo esc_html($pc_hours_we); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Chat with Us -->
                    <?php
                    $ct_line_enabled    = get_option('chat_line_enabled', '1');
                    $ct_line_id         = get_option('chat_line_id', 'kriangkrai2042');
                    $ct_wechat_enabled  = get_option('chat_wechat_enabled', '1');
                    $ct_wechat_id       = get_option('chat_wechat_id', '');
                    $ct_wechat_qr       = get_option('chat_wechat_qr_url', '');
                    $ct_whatsapp_enabled = get_option('chat_whatsapp_enabled', '1');
                    $ct_whatsapp_number = get_option('chat_whatsapp_number', '6621088521');
                    $ct_has_chat = ($ct_line_enabled && $ct_line_id) || ($ct_wechat_enabled) || ($ct_whatsapp_enabled && $ct_whatsapp_number);
                    ?>
                    <?php if ($ct_has_chat) : ?>
                    <div style="display:flex;gap:16px;align-items:flex-start;">
                        <div style="width:50px;height:50px;min-width:50px;border-radius:50%;background:#e8f0fe;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0;">💬</div>
                        <div>
                            <h4 style="margin:0 0 10px;font-size:17px;color:#1e293b;">Chat with Us</h4>
                            <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                                <?php if ($ct_line_enabled && $ct_line_id) : ?>
                                <a href="https://line.me/ti/p/~<?php echo esc_attr($ct_line_id); ?>" target="_blank" rel="noopener noreferrer" style="width:48px;height:48px;border-radius:50%;background:#06C755;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(6,199,85,0.35);transition:transform .2s,box-shadow .2s;text-decoration:none;" aria-label="Chat on LINE" onmouseenter="this.style.transform='scale(1.12)'" onmouseleave="this.style.transform='scale(1)'">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24" fill="#fff"><path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386a.63.63 0 0 1-.63-.629V8.108a.63.63 0 0 1 .63-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016a.63.63 0 0 1-.63.629.626.626 0 0 1-.51-.262l-2.397-3.265v2.898a.63.63 0 0 1-.63.629.627.627 0 0 1-.629-.629V8.108a.63.63 0 0 1 .63-.63c.2 0 .38.095.51.262l2.397 3.265V8.108a.63.63 0 0 1 .63-.63.63.63 0 0 1 .629.63v4.771zm-5.741 0a.63.63 0 0 1-1.26 0V8.108a.63.63 0 0 1 1.26 0v4.771zm-2.527.629H4.856a.63.63 0 0 1-.63-.629V8.108a.63.63 0 0 1 1.26 0v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629zM24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/></svg>
                                </a>
                                <?php endif; ?>
                                <?php if ($ct_wechat_enabled) : ?>
                                <button id="ct-wechat-btn" onclick="var p=document.getElementById('ct-wechat-popup');p.style.display=p.style.display==='none'?'flex':'none';" style="width:48px;height:48px;border-radius:50%;background:#07C160;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(7,193,96,0.35);transition:transform .2s,box-shadow .2s;" aria-label="Chat on WeChat" onmouseenter="this.style.transform='scale(1.12)'" onmouseleave="this.style.transform='scale(1)'">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#fff"><path d="M8.691 2.188C3.891 2.188 0 5.476 0 9.53c0 2.212 1.17 4.203 3.002 5.55a.59.59 0 0 1 .213.665l-.39 1.48c-.019.07-.048.141-.048.213a.3.3 0 0 0 .3.3c.07 0 .14-.027.198-.063l1.83-1.067a.57.57 0 0 1 .449-.063 9.613 9.613 0 0 0 3.137.524c.302 0 .6-.013.893-.039a6.192 6.192 0 0 1-.253-1.72c0-3.682 3.477-6.674 7.759-6.674.254 0 .505.012.752.033C16.726 4.492 13.068 2.188 8.691 2.188zm-2.6 4.26a1.058 1.058 0 1 1 0 2.115 1.058 1.058 0 0 1 0-2.116zm5.203 0a1.058 1.058 0 1 1 0 2.115 1.058 1.058 0 0 1 0-2.116zM16.09 8.735c-3.752 0-6.803 2.614-6.803 5.836 0 3.222 3.051 5.836 6.803 5.836a8.17 8.17 0 0 0 2.593-.42.542.542 0 0 1 .42.059l1.517.885c.052.033.112.055.172.055a.25.25 0 0 0 .25-.25c0-.062-.024-.12-.04-.178l-.323-1.228a.553.553 0 0 1 .2-.622C22.725 17.543 24 15.762 24 13.57c0-3.222-3.547-5.836-7.91-5.836zm-2.418 3.776a.883.883 0 1 1 0 1.765.883.883 0 0 1 0-1.765zm4.84 0a.883.883 0 1 1 0 1.765.883.883 0 0 1 0-1.765z"/></svg>
                                </button>
                                <?php endif; ?>
                                <?php if ($ct_whatsapp_enabled && $ct_whatsapp_number) : ?>
                                <a href="https://wa.me/<?php echo esc_attr(preg_replace('/\D/', '', $ct_whatsapp_number)); ?>" target="_blank" rel="noopener noreferrer" style="width:48px;height:48px;border-radius:50%;background:#25D366;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 14px rgba(37,211,102,0.35);transition:transform .2s,box-shadow .2s;text-decoration:none;" aria-label="Chat on WhatsApp" onmouseenter="this.style.transform='scale(1.12)'" onmouseleave="this.style.transform='scale(1)'">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="#fff"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>
                                </a>
                                <?php endif; ?>
                            </div>
                            <?php if ($ct_wechat_enabled) : ?>
                            <!-- WeChat QR Popup -->
                            <div id="ct-wechat-popup" style="display:none;margin-top:12px;padding:16px;background:#fff;border-radius:10px;border:1px solid #e2e8f0;box-shadow:0 4px 16px rgba(0,0,0,0.08);flex-direction:column;align-items:center;gap:8px;max-width:220px;">
                                <?php if ($ct_wechat_qr) : ?>
                                <img src="<?php echo esc_url($ct_wechat_qr); ?>" alt="WeChat QR Code" style="width:160px;height:160px;border-radius:8px;">
                                <?php endif; ?>
                                <p style="margin:0;font-size:13px;color:#64748b;">
                                    <?php if ($ct_wechat_id) : ?>
                                    WeChat ID: <strong><?php echo esc_html($ct_wechat_id); ?></strong>
                                    <?php else : ?>
                                    Scan to chat on WeChat
                                    <?php endif; ?>
                                </p>
                                <button onclick="this.parentElement.style.display='none'" style="margin-top:4px;padding:4px 16px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:6px;cursor:pointer;font-size:12px;color:#64748b;">Close</button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
            </div><!-- /col -->

            <!-- Right: Contact Form -->
            <div class="col-lg-7">
                <div style="background:#f8fafc;border-radius:12px;padding:40px;">
                    <h3 style="margin-top:0;margin-bottom:24px;font-size:22px;color:#1e293b;">Send us a Message</h3>
                    <form class="contact-form-fields" id="cf-form">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label style="display:block;margin-bottom:6px;font-weight:500;color:#374151;">Full Name <span style="color:#dc2626;">*</span></label>
                                <input type="text" name="name" required minlength="2" maxlength="100" placeholder="Your name" style="width:100%;padding:12px 16px;border:1px solid #d1d5db;border-radius:8px;font-size:15px;font-family:inherit;">
                            </div>
                            <div class="col-md-6">
                                <label style="display:block;margin-bottom:6px;font-weight:500;color:#374151;">Company Name</label>
                                <input type="text" name="company" maxlength="100" placeholder="Your company" style="width:100%;padding:12px 16px;border:1px solid #d1d5db;border-radius:8px;font-size:15px;font-family:inherit;">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label style="display:block;margin-bottom:6px;font-weight:500;color:#374151;">Email Address <span style="color:#dc2626;">*</span></label>
                                <input type="email" name="email" id="cf-email" required maxlength="254" placeholder="your@email.com" pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}" style="width:100%;padding:12px 16px;border:1px solid #d1d5db;border-radius:8px;font-size:15px;font-family:inherit;">
                                <small id="cf-email-err" style="color:#dc2626;font-size:12px;display:none;margin-top:4px;">กรุณากรอกอีเมลภาษาอังกฤษเท่านั้น เช่น name@gmail.com</small>
                            </div>
                            <div class="col-md-6">
                                <label style="display:block;margin-bottom:6px;font-weight:500;color:#374151;">Phone Number</label>
                                <input type="tel" name="phone" id="cf-phone" maxlength="10" minlength="9" inputmode="numeric" pattern="[0-9]{9,10}" placeholder="0XXXXXXXXX" oninput="this.value=this.value.replace(/[^0-9]/g,'')" style="width:100%;padding:12px 16px;border:1px solid #d1d5db;border-radius:8px;font-size:15px;font-family:inherit;">
                                <small id="cf-phone-err" style="color:#dc2626;font-size:12px;display:none;margin-top:4px;">กรอกได้เฉพาะตัวเลข 9-10 หลัก</small>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label style="display:block;margin-bottom:6px;font-weight:500;color:#374151;">Subject <span style="color:#dc2626;">*</span></label>
                                <select name="subject" required style="width:100%;padding:12px 16px;border:1px solid #d1d5db;border-radius:8px;font-size:15px;font-family:inherit;background:#fff;">
                                    <option value="">Select a subject</option>
                                    <option value="quote">Request a Quote</option>
                                    <option value="technical">Technical Support</option>
                                    <option value="sales">Sales Inquiry</option>
                                    <option value="partnership">Partnership</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label style="display:block;margin-bottom:6px;font-weight:500;color:#374151;">Product Interest</label>
                                <select name="product" style="width:100%;padding:12px 16px;border:1px solid #d1d5db;border-radius:8px;font-size:15px;font-family:inherit;background:#fff;">
                                    <option value="">Select a product category</option>
                                    <?php
                                    $pc_cats = get_terms([
                                        'taxonomy'   => 'product_category',
                                        'hide_empty' => false,
                                        'parent'     => 0,
                                        'orderby'    => 'name',
                                    ]);
                                    if (!is_wp_error($pc_cats) && !empty($pc_cats)) :
                                        foreach ($pc_cats as $parent_cat) :
                                            $children = get_terms([
                                                'taxonomy'   => 'product_category',
                                                'hide_empty' => false,
                                                'parent'     => $parent_cat->term_id,
                                                'orderby'    => 'name',
                                            ]);
                                            if (!is_wp_error($children) && !empty($children)) : ?>
                                                <optgroup label="<?php echo esc_attr($parent_cat->name); ?>">
                                                    <?php foreach ($children as $child) : ?>
                                                        <option value="<?php echo esc_attr($child->slug); ?>"><?php echo esc_html($child->name); ?></option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            <?php else : ?>
                                                <option value="<?php echo esc_attr($parent_cat->slug); ?>"><?php echo esc_html($parent_cat->name); ?></option>
                                            <?php endif;
                                        endforeach;
                                    endif;
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label style="display:block;margin-bottom:6px;font-weight:500;color:#374151;">Message <span style="color:#dc2626;">*</span></label>
                            <textarea name="message" required minlength="10" maxlength="2000" placeholder="How can we help you?" style="width:100%;padding:12px 16px;border:1px solid #d1d5db;border-radius:8px;font-size:15px;font-family:inherit;min-height:130px;resize:vertical;"></textarea>
                        </div>
                        <div class="mb-3" style="margin-bottom:16px;">
                            <label style="display:flex;gap:10px;align-items:flex-start;cursor:pointer;font-size:13px;color:#374151;line-height:1.5;">
                                <input type="checkbox" id="cf-pdpa" name="pdpa_consent" required style="margin-top:3px;min-width:18px;height:18px;accent-color:<?php echo esc_attr($theme_primary); ?>;cursor:pointer;" onchange="var b=document.getElementById('cf-submit-btn');b.disabled=!this.checked;b.style.opacity=this.checked?'1':'0.5';">
                                <span>I consent to KV Electronics collecting and storing my data for the purpose of responding to my inquiry in accordance with the <a href="<?php echo esc_url(home_url('/privacy-policy')); ?>" target="_blank" style="color:<?php echo esc_attr($theme_primary); ?>;text-decoration:underline;">Privacy Policy (PDPA)</a>. <span style="color:#dc2626;">*</span></span>
                            </label>
                        </div>
                        <div id="cf-result" style="display:none;padding:12px 16px;border-radius:8px;margin-bottom:12px;font-size:14px;"></div>
                        <button type="submit" id="cf-submit-btn" disabled style="width:100%;padding:13px 28px;background:<?php echo esc_attr($theme_accent); ?>;color:#fff;border:none;border-radius:8px;font-weight:600;font-size:15px;cursor:pointer;font-family:inherit;transition:opacity 0.2s;opacity:0.5;" onmouseover="if(!this.disabled)this.style.opacity='0.85'" onmouseout="this.style.opacity=this.disabled?'0.5':'1'">
                            Send Message
                        </button>
                    </form>
                    <script>
                    (function(){
                        var emailInput = document.getElementById('cf-email');
                        var phoneInput = document.getElementById('cf-phone');
                        var emailErr   = document.getElementById('cf-email-err');
                        var phoneErr   = document.getElementById('cf-phone-err');
                        if (emailInput) {
                            emailInput.addEventListener('input', function(){
                                this.value = this.value.replace(/[^\x20-\x7E]/g, '');
                            });
                            emailInput.addEventListener('blur', function(){
                                var full = /^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/;
                                if (emailErr) emailErr.style.display = (this.value && !full.test(this.value)) ? 'block' : 'none';
                                this.style.borderColor = (this.value && !full.test(this.value)) ? '#dc2626' : '#d1d5db';
                            });
                        }
                        if (phoneInput) {
                            phoneInput.addEventListener('input', function(){
                                this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
                                if (phoneErr) phoneErr.style.display = (this.value && this.value.length < 9) ? 'block' : 'none';
                                this.style.borderColor = (this.value && this.value.length < 9) ? '#dc2626' : '#d1d5db';
                            });
                            phoneInput.addEventListener('keydown', function(e){
                                if ([8,9,13,27,46,37,38,39,40].indexOf(e.keyCode) !== -1) return;
                                if ((e.ctrlKey || e.metaKey) && [65,67,86,88].indexOf(e.keyCode) !== -1) return;
                                if (e.key && e.key.length === 1 && !/[0-9]/.test(e.key)) e.preventDefault();
                            });
                        }
                    })();
                    </script>
                </div>
            </div><!-- /col -->

        </div><!-- /row -->
    </div><!-- /container -->
</section>

<!-- ── Google Map ──────────────────────────────────────────────────────────────────── -->
<?php if (!empty($pc_map_embed)) : ?>
<section style="line-height:0;">
    <iframe
        src="<?php echo esc_url($pc_map_embed); ?>"
        width="100%"
        height="400"
        style="border:0;display:block;width:100%;"
        allowfullscreen=""
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"
    ></iframe>
</section>
<?php else : ?>
<section style="background:#e2e8f0;min-height:350px;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:10px;color:#64748b;">
    <span style="font-size:32px;">📍</span>
    <p style="margin:0;font-size:16px;">Google Map จะแสดงที่นี่</p>
    <p style="margin:0;font-size:13px;">ไปเพิ่ม Google Maps Embed URL ใน <a href="<?php echo admin_url('admin.php?page=my-theme-settings'); ?>">ที่ตั้งค่าเว็บไซต์</a></p>
</section>
<?php endif; ?>

<?php get_footer(); ?>
