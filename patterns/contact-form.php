<?php
/**
 * Title: Contact Form Section
 * Slug: my-custom-theme/contact-form
 * Categories: my-special-design
 * Description: ส่วนติดต่อพร้อมข้อมูลและฟอร์ม
 */
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"60px","bottom":"60px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull" style="padding-top:60px;padding-bottom:60px">
    <!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"50px"}}}} -->
    <div class="wp-block-columns">
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:html -->
            <?php
            $cf_phone        = kv_format_phone_th(get_option('site_phone', ''));
            $cf_fax          = kv_format_phone_th(get_option('site_fax',   ''));
            $cf_email        = get_option('site_email',         'info@company.com');
            $cf_email_sales  = get_option('site_email_sales',   'sales@company.com');
            $cf_address_full = get_option('site_address_full',  "123 Industrial Zone\nBangna-Trad Road\nBangkok 10260, Thailand");
            $cf_hours_wd     = get_option('site_hours_weekday', 'Monday – Friday: 8:00 AM – 5:00 PM');
            $cf_hours_we     = get_option('site_hours_weekend', 'Saturday – Sunday: Closed');
            ?>
            <div class="contact-info">
                <h2 style="font-size:28px;margin-bottom:30px;color:#1e293b;">Get in Touch</h2>

                <div class="contact-info-item">
                    <div class="contact-icon">📍</div>
                    <div class="contact-text">
                        <h4>Address</h4>
                        <p><?php echo nl2br(esc_html($cf_address_full)); ?></p>
                    </div>
                </div>

                <div class="contact-info-item">
                    <div class="contact-icon">📞</div>
                    <div class="contact-text">
                        <h4>Phone</h4>
                        <p><?php echo esc_html($cf_phone); ?><br><?php echo esc_html($cf_fax); ?> (Fax)</p>
                    </div>
                </div>

                <div class="contact-info-item">
                    <div class="contact-icon">✉️</div>
                    <div class="contact-text">
                        <h4>Email</h4>
                        <p><?php echo esc_html($cf_email); ?><br><?php echo esc_html($cf_email_sales); ?></p>
                    </div>
                </div>

                <div class="contact-info-item">
                    <div class="contact-icon">🕐</div>
                    <div class="contact-text">
                        <h4>Business Hours</h4>
                        <p><?php echo esc_html($cf_hours_wd); ?><br><?php echo esc_html($cf_hours_we); ?></p>
                    </div>
                </div>
            </div>
            <!-- /wp:html -->
        </div>
        <!-- /wp:column -->

        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:html -->
            <div class="contact-form">
                <h3>Send us a Message</h3>
                <div id="cf-result" style="display:none;padding:14px 18px;border-radius:8px;margin-bottom:16px;font-size:15px;"></div>
                <form id="cf-form" action="#" method="POST">
                    <div class="form-group">
                        <label for="cf-name">Full Name *</label>
                        <input type="text" id="cf-name" name="name" required placeholder="Your name">
                    </div>
                    <div class="form-group">
                        <label for="cf-company">Company Name</label>
                        <input type="text" id="cf-company" name="company" placeholder="Your company">
                    </div>
                    <div class="form-group">
                        <label for="cf-email">Email Address *</label>
                        <input type="email" id="cf-email" name="email" required placeholder="your@email.com">
                    </div>
                    <div class="form-group">
                        <label for="cf-phone">Phone Number</label>
                        <input type="tel" id="cf-phone" name="phone" placeholder="0XXXXXXXXX">
                    </div>
                    <div class="form-group">
                        <label for="cf-subject">Subject *</label>
                        <select id="cf-subject" name="subject" required>
                            <option value="">Select a subject</option>
                            <option value="quote">Request a Quote</option>
                            <option value="technical">Technical Support</option>
                            <option value="sales">Sales Inquiry</option>
                            <option value="partnership">Partnership</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="cf-product">Product Interest</label>
                        <select id="cf-product" name="product">
                            <option value="">Select a product category</option>
                            <option value="inductors">Inductors</option>
                            <option value="transformers">Transformers</option>
                            <option value="antennas">Antennas</option>
                            <option value="multiple">Multiple Products</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="cf-message">Message *</label>
                        <textarea id="cf-message" name="message" required placeholder="Please describe your requirements..."></textarea>
                    </div>
                    <div class="form-group" style="display:flex;gap:10px;align-items:flex-start;">
                        <input type="checkbox" id="cf-pdpa-cf" name="pdpa_consent" required style="margin-top:3px;min-width:16px;height:16px;cursor:pointer;" onchange="var b=this.form.querySelector('button[type=submit]');b.disabled=!this.checked;b.style.opacity=this.checked?'1':'0.5';">
                        <label for="cf-pdpa-cf" style="font-weight:400;font-size:13px;cursor:pointer;line-height:1.5;">I consent to KV Electronics collecting and storing my data for the purpose of responding to my inquiry in accordance with the Privacy Policy (PDPA). *</label>
                    </div>
                    <button type="submit" disabled style="opacity:0.5;">Send Message</button>
                </form>
            </div>
            <!-- /wp:html -->
        </div>
        <!-- /wp:column -->
    </div>
    <!-- /wp:columns -->
</div>
<!-- /wp:group -->
