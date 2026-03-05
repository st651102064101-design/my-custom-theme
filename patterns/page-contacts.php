<?php
/**
 * Title: 📄 Page: Contacts (Full Page)
 * Slug: my-custom-theme/page-contacts
 * Categories: my-special-design
 * Description: หน้า Contact Us แบบเต็มหน้า - รวม Page Header, Contact Info, Contact Form, Map
 * Keywords: contact, ติดต่อ, page
 * Block Types: core/post-content
 * Post Types: page
 */

$kv_contact_phone = function_exists('kv_get_site_phone_raw_display')
	? kv_get_site_phone_raw_display('+66 2 108 8521')
	: trim((string) get_option('site_phone', '+66 2 108 8521'));
$kv_contact_fax = function_exists('kv_get_site_fax_raw_display')
	? kv_get_site_fax_raw_display('')
	: trim((string) get_option('site_fax', ''));
?>
<!-- wp:cover {"overlayColor":"primary","align":"full","style":{"spacing":{"padding":{"top":"64px","bottom":"64px"}}}} -->
<div class="wp-block-cover alignfull" style="padding-top:64px;padding-bottom:64px"><span aria-hidden="true" class="wp-block-cover__background has-primary-background-color has-background-dim-100 has-background-dim"></span><div class="wp-block-cover__inner-container">
<!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"42px"},"spacing":{"margin":{"bottom":"10px"}}},"textColor":"white"} -->
<h1 class="wp-block-heading has-text-align-center has-white-color has-text-color" style="margin-bottom:10px;font-size:42px">Contact Us</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","textColor":"white"} -->
<p class="has-text-align-center has-white-color has-text-color">Home / Contact</p>
<!-- /wp:paragraph -->
</div></div>
<!-- /wp:cover -->

<!-- wp:group {"align":"full","className":"kv-contact-builder","style":{"spacing":{"padding":{"top":"56px","bottom":"56px"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull kv-contact-builder" style="padding-top:56px;padding-bottom:56px">
<!-- wp:columns {"verticalAlignment":"top","style":{"spacing":{"blockGap":{"left":"36px"}}}} -->
<div class="wp-block-columns are-vertically-aligned-top">
<!-- wp:column {"verticalAlignment":"top","width":"42%"} -->
<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:42%">
<!-- wp:heading {"level":2,"style":{"typography":{"fontSize":"30px"},"spacing":{"margin":{"bottom":"24px"}}}} -->
<h2 class="wp-block-heading" style="margin-bottom:24px;font-size:30px">Get in Touch</h2>
<!-- /wp:heading -->

<!-- wp:group {"style":{"spacing":{"blockGap":"14px"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group">
<!-- wp:group {"className":"kv-contact-item","style":{"spacing":{"padding":{"top":"18px","right":"18px","bottom":"18px","left":"18px"}},"border":{"radius":"0px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group kv-contact-item" style="border-radius:0px;padding-top:18px;padding-right:18px;padding-bottom:18px;padding-left:18px">
<!-- wp:columns {"verticalAlignment":"top"} -->
<div class="wp-block-columns are-vertically-aligned-top">
<!-- wp:column {"width":"54px"} -->
<div class="wp-block-column" style="flex-basis:54px"><!-- wp:paragraph {"fontSize":"large"} --><p class="has-large-font-size">📍</p><!-- /wp:paragraph --></div>
<!-- /wp:column -->
<!-- wp:column -->
<div class="wp-block-column">
<!-- wp:heading {"level":4,"style":{"spacing":{"margin":{"bottom":"4px"}}}} -->
<h4 class="wp-block-heading" style="margin-bottom:4px">Address</h4>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>KV Electronics Co., Ltd.<br>988 Moo 2, Soi Thetsaban Bang Poo 60<br>Samut Prakan 10280, Thailand</p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"kv-contact-item","style":{"spacing":{"padding":{"top":"18px","right":"18px","bottom":"18px","left":"18px"}},"border":{"radius":"0px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group kv-contact-item" style="border-radius:0px;padding-top:18px;padding-right:18px;padding-bottom:18px;padding-left:18px">
<!-- wp:columns {"verticalAlignment":"top"} --><div class="wp-block-columns are-vertically-aligned-top">
<!-- wp:column {"width":"54px"} --><div class="wp-block-column" style="flex-basis:54px"><!-- wp:paragraph {"fontSize":"large"} --><p class="has-large-font-size">📞</p><!-- /wp:paragraph --></div><!-- /wp:column -->
<!-- wp:column --><div class="wp-block-column"><!-- wp:heading {"level":4,"style":{"spacing":{"margin":{"bottom":"4px"}}}} --><h4 class="wp-block-heading" style="margin-bottom:4px">Phone</h4><!-- /wp:heading --><!-- wp:paragraph --><p><?php echo esc_html($kv_contact_phone !== '' ? $kv_contact_phone : '+66 2 108 8521'); ?><?php if ($kv_contact_fax !== '') : ?><br><span>Fax: <?php echo esc_html($kv_contact_fax); ?></span><?php endif; ?></p><!-- /wp:paragraph --></div><!-- /wp:column -->
</div><!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"kv-contact-item","style":{"spacing":{"padding":{"top":"18px","right":"18px","bottom":"18px","left":"18px"}},"border":{"radius":"0px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group kv-contact-item" style="border-radius:0px;padding-top:18px;padding-right:18px;padding-bottom:18px;padding-left:18px">
<!-- wp:columns {"verticalAlignment":"top"} --><div class="wp-block-columns are-vertically-aligned-top">
<!-- wp:column {"width":"54px"} --><div class="wp-block-column" style="flex-basis:54px"><!-- wp:paragraph {"fontSize":"large"} --><p class="has-large-font-size">✉️</p><!-- /wp:paragraph --></div><!-- /wp:column -->
<!-- wp:column --><div class="wp-block-column"><!-- wp:heading {"level":4,"style":{"spacing":{"margin":{"bottom":"4px"}}}} --><h4 class="wp-block-heading" style="margin-bottom:4px">Email</h4><!-- /wp:heading --><!-- wp:paragraph --><p>info@company.com</p><!-- /wp:paragraph --></div><!-- /wp:column -->
</div><!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"kv-contact-item","style":{"spacing":{"padding":{"top":"18px","right":"18px","bottom":"18px","left":"18px"}},"border":{"radius":"0px"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group kv-contact-item" style="border-radius:0px;padding-top:18px;padding-right:18px;padding-bottom:18px;padding-left:18px">
<!-- wp:columns {"verticalAlignment":"top"} --><div class="wp-block-columns are-vertically-aligned-top">
<!-- wp:column {"width":"54px"} --><div class="wp-block-column" style="flex-basis:54px"><!-- wp:paragraph {"fontSize":"large"} --><p class="has-large-font-size">💬</p><!-- /wp:paragraph --></div><!-- /wp:column -->
<!-- wp:column --><div class="wp-block-column"><!-- wp:heading {"level":4,"style":{"spacing":{"margin":{"bottom":"8px"}}}} --><h4 class="wp-block-heading" style="margin-bottom:8px">Chat with Us</h4><!-- /wp:heading -->
<!-- wp:social-links {"iconColor":"white","iconColorValue":"#ffffff","openInNewTab":true,"size":"has-normal-icon-size","style":{"spacing":{"blockGap":{"left":"8px"}}},"className":"is-style-pill-shape"} -->
<ul class="wp-block-social-links has-normal-icon-size has-icon-color is-style-pill-shape"><!-- wp:social-link {"url":"https://line.me/","service":"line"} /-->
<!-- wp:social-link {"url":"https://wa.me/6621088521","service":"whatsapp"} /-->
<!-- wp:social-link {"url":"https://www.facebook.com/","service":"facebook"} /-->
<!-- wp:social-link {"url":"https://www.instagram.com/","service":"instagram"} /-->
<!-- wp:social-link {"url":"https://www.linkedin.com/","service":"linkedin"} /--></ul>
<!-- /wp:social-links --></div><!-- /wp:column -->
</div><!-- /wp:columns --></div>
<!-- /wp:group -->
</div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"top","width":"58%"} -->
<div class="wp-block-column is-vertically-aligned-top" style="flex-basis:58%">
<!-- wp:kv/contact-form /-->
</div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->

<!-- wp:kv/google-map {"height":360,"showInfoCard":false,"wrapperMT":0,"wrapperMB":0,"mapBorderRadius":0} /-->
