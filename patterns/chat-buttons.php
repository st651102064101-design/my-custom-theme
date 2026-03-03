<?php
/**
 * Title: 💬 Chat Buttons (LINE / WeChat / WhatsApp)
 * Slug: my-custom-theme/chat-buttons
 * Categories: my-special-design
 * Description: ปุ่มแชท LINE, WeChat, WhatsApp — ดึงค่าจาก Theme Settings อัตโนมัติ
 * Keywords: chat, line, wechat, whatsapp, contact
 */
?>
<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
    <!-- wp:heading {"level":3,"style":{"typography":{"fontWeight":"600"}}} -->
    <h3 class="wp-block-heading" style="font-weight:600">Chat with Us</h3>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"style":{"color":{"text":"#64748b"}}} -->
    <p class="has-text-color" style="color:#64748b">Connect with us instantly through your preferred messaging platform.</p>
    <!-- /wp:paragraph -->

    <!-- wp:shortcode -->
    [chat_buttons style="inline"]
    <!-- /wp:shortcode -->
</div>
<!-- /wp:group -->
