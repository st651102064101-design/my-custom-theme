<?php
/**
 * Account Manager — จัดการ Username / Email / Password
 *
 * Submenu under ⚙️ ตั้งค่าเว็บไซต์
 * Allows safely changing admin login credentials
 */

if (!defined('ABSPATH')) exit;

/* ================================================================
   1. SUBMENU REGISTRATION
   ================================================================ */
add_action('admin_menu', function () {
    add_submenu_page(
        'my-theme-settings',           // parent slug
        'Account Manager',
        '🔐 Account Manager',
        'manage_options',
        'kv-account-manager',
        'kv_account_manager_page'
    );
}, 20);

/* ================================================================
   2. AJAX HANDLERS
   ================================================================ */

/* ── Change Username ── */
add_action('wp_ajax_kv_change_username', function () {
    check_ajax_referer('kv_account_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error(['msg' => 'Permission denied.']);

    $uid      = (int) get_current_user_id();
    $new_user = sanitize_user(wp_unslash($_POST['new_username'] ?? ''), true);

    if (empty($new_user))
        wp_send_json_error(['msg' => 'Username ไม่สามารถเว้นว่างได้']);
    if (strlen($new_user) < 3)
        wp_send_json_error(['msg' => 'Username ต้องมีอย่างน้อย 3 ตัวอักษร']);
    if (!validate_username($new_user))
        wp_send_json_error(['msg' => 'Username มีตัวอักษรไม่อนุญาต (ใช้ได้: a-z 0-9 . - _)']);

    // Check uniqueness
    $existing = get_user_by('login', $new_user);
    if ($existing && $existing->ID !== $uid)
        wp_send_json_error(['msg' => 'Username นี้ถูกใช้งานแล้ว']);

    global $wpdb;
    $result = $wpdb->update($wpdb->users, ['user_login' => $new_user], ['ID' => $uid]);
    if ($result === false)
        wp_send_json_error(['msg' => 'ไม่สามารถอัพเดต Username: ' . $wpdb->last_error]);

    clean_user_cache($uid);
    wp_send_json_success(['msg' => 'เปลี่ยน Username เรียบร้อย กรุณา Login ใหม่', 'new_username' => $new_user, 'logout' => true]);
});

/* ── Change Email ── */
add_action('wp_ajax_kv_change_email', function () {
    check_ajax_referer('kv_account_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error(['msg' => 'Permission denied.']);

    $uid       = (int) get_current_user_id();
    $new_email = sanitize_email(wp_unslash($_POST['new_email'] ?? ''));

    if (!is_email($new_email))
        wp_send_json_error(['msg' => 'รูปแบบ Email ไม่ถูกต้อง']);

    $existing = get_user_by('email', $new_email);
    if ($existing && $existing->ID !== $uid)
        wp_send_json_error(['msg' => 'Email นี้ถูกใช้งานแล้ว']);

    $result = wp_update_user(['ID' => $uid, 'user_email' => $new_email]);
    if (is_wp_error($result))
        wp_send_json_error(['msg' => $result->get_error_message()]);

    // Update admin_email option if this user owns it
    if (get_option('admin_email') === wp_get_current_user()->user_email) {
        update_option('admin_email', $new_email);
    }

    wp_send_json_success(['msg' => 'เปลี่ยน Email เรียบร้อย', 'new_email' => $new_email]);
});

/* ── Change Password ── */
add_action('wp_ajax_kv_change_password', function () {
    check_ajax_referer('kv_account_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error(['msg' => 'Permission denied.']);

    $uid       = (int) get_current_user_id();
    $current   = wp_unslash($_POST['current_password'] ?? '');
    $new_pass  = wp_unslash($_POST['new_password'] ?? '');
    $confirm   = wp_unslash($_POST['confirm_password'] ?? '');

    if (empty($current) || empty($new_pass) || empty($confirm))
        wp_send_json_error(['msg' => 'กรุณากรอกข้อมูลให้ครบ']);

    // Verify current password
    $user = get_user_by('id', $uid);
    if (!wp_check_password($current, $user->user_pass, $uid))
        wp_send_json_error(['msg' => 'รหัสผ่านปัจจุบันไม่ถูกต้อง']);

    if ($new_pass !== $confirm)
        wp_send_json_error(['msg' => 'รหัสผ่านใหม่ไม่ตรงกัน']);

    if (strlen($new_pass) < 8)
        wp_send_json_error(['msg' => 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร']);

    // Strength check
    $strength_score = 0;
    if (preg_match('/[a-z]/', $new_pass)) $strength_score++;
    if (preg_match('/[A-Z]/', $new_pass)) $strength_score++;
    if (preg_match('/[0-9]/', $new_pass)) $strength_score++;
    if (preg_match('/[^a-zA-Z0-9]/', $new_pass)) $strength_score++;
    if ($strength_score < 2)
        wp_send_json_error(['msg' => 'รหัสผ่านอ่อนเกินไป ควรมีตัวพิมพ์ใหญ่ ตัวเลข หรืออักขระพิเศษ']);

    wp_set_password($new_pass, $uid);
    wp_send_json_success(['msg' => 'เปลี่ยนรหัสผ่านเรียบร้อย กรุณา Login ใหม่', 'logout' => true]);
});

/* ── Get current user info ── */
add_action('wp_ajax_kv_get_account_info', function () {
    check_ajax_referer('kv_account_nonce', 'nonce');
    if (!current_user_can('manage_options')) wp_send_json_error();

    $user = wp_get_current_user();
    wp_send_json_success([
        'username'     => $user->user_login,
        'email'        => $user->user_email,
        'display_name' => $user->display_name,
        'registered'   => date_i18n(get_option('date_format'), strtotime($user->user_registered)),
        'role'         => implode(', ', $user->roles),
        'last_login'   => get_user_meta($user->ID, 'last_login', true) ?: 'ไม่มีข้อมูล',
        'login_url'    => wp_login_url(),
        'admin_url'    => admin_url(),
    ]);
});

/* ================================================================
   3. PAGE RENDER
   ================================================================ */
function kv_account_manager_page() {
    $user    = wp_get_current_user();
    $primary = get_option('theme_primary_color', '#0056d6');
    $r = hexdec(substr($primary, 1, 2));
    $g = hexdec(substr($primary, 3, 2));
    $b = hexdec(substr($primary, 5, 2));
    $nonce   = wp_create_nonce('kv_account_nonce');
    $ajaxurl = admin_url('admin-ajax.php');
    ?>
    <div class="wrap kv-acct-wrap">

    <!-- ════════════════════════════════════════
         INLINE STYLES
         ════════════════════════════════════════ -->
    <style>
        .kv-acct-wrap * { box-sizing: border-box; }
        .kv-acct-wrap {
            font-family: -apple-system, BlinkMacSystemFont, 'Sarabun', 'Segoe UI', sans-serif;
            max-width: 100%;
            padding-right: 20px;
        }
        .kv-acct-header {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 20px 24px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            margin: 16px 0 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .kv-acct-avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: <?php echo esc_attr($primary); ?>;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 700;
            color: #fff;
            flex-shrink: 0;
            overflow: hidden;
        }
        .kv-acct-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .kv-acct-meta h2 { font-size: 20px; font-weight: 700; color: #1e293b; margin: 0 0 4px; }
        .kv-acct-meta p { margin: 0; color: #64748b; font-size: 13px; }
        .kv-acct-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: rgba(<?php echo "$r,$g,$b"; ?>, 0.1);
            color: <?php echo esc_attr($primary); ?>;
            border-radius: 20px;
            padding: 2px 10px;
            font-size: 12px;
            font-weight: 600;
        }
        .kv-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }
        .kv-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .kv-card h3 {
            margin: 0 0 16px;
            font-size: 15px;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 8px;
            padding-bottom: 12px;
            border-bottom: 1px solid #f1f5f9;
        }
        .kv-field { margin-bottom: 14px; }
        .kv-field label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }
        .kv-field input {
            width: 100%;
            padding: 9px 12px;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            color: #1e293b;
            transition: border-color .2s, box-shadow .2s;
            background: #fff;
        }
        .kv-field input:focus {
            border-color: <?php echo esc_attr($primary); ?>;
            box-shadow: 0 0 0 3px rgba(<?php echo "$r,$g,$b"; ?>, 0.12);
            outline: none;
        }
        .kv-field input[readonly] {
            background: #f8fafc;
            color: #94a3b8;
            cursor: default;
        }
        .kv-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 9px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all .2s;
            border: none;
            width: 100%;
            justify-content: center;
            margin-top: 4px;
        }
        .kv-btn-primary {
            background: <?php echo esc_attr($primary); ?>;
            color: #fff;
        }
        .kv-btn-primary:hover {
            opacity: 0.88;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(<?php echo "$r,$g,$b"; ?>, 0.3);
        }
        .kv-btn-primary:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        .kv-btn-danger {
            background: #ef4444;
            color: #fff;
        }
        .kv-btn-danger:hover {
            background: #dc2626;
        }
        .kv-notice {
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            margin-top: 12px;
            display: none;
            align-items: center;
            gap: 8px;
        }
        .kv-notice.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .kv-notice.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .kv-notice.info {
            background: #dbeafe;
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }
        .kv-strength-bar {
            height: 6px;
            background: #e2e8f0;
            border-radius: 99px;
            margin-top: 6px;
            overflow: hidden;
        }
        .kv-strength-fill {
            height: 100%;
            border-radius: 99px;
            width: 0%;
            transition: width .3s, background .3s;
        }
        .kv-strength-text {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 3px;
        }
        .kv-info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
        }
        .kv-info-row:last-child { border-bottom: none; }
        .kv-info-row span:first-child { color: #64748b; }
        .kv-info-row span:last-child { color: #1e293b; font-weight: 600; word-break: break-all; }
        .kv-warning-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 13px;
            color: #92400e;
            margin-bottom: 16px;
            display: flex;
            gap: 8px;
            align-items: flex-start;
        }
        .kv-copy-btn {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 4px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            color: #475569;
            cursor: pointer;
            transition: background .2s;
            margin-left: 6px;
        }
        .kv-copy-btn:hover { background: #e2e8f0; }
        .kv-eye-toggle {
            position: relative;
        }
        .kv-eye-toggle input { padding-right: 40px; }
        .kv-eye-toggle button {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #94a3b8;
            padding: 4px;
            transition: color .2s;
        }
        .kv-eye-toggle button:hover { color: <?php echo esc_attr($primary); ?>; }

        @media (max-width: 1200px) { .kv-grid { grid-template-columns: repeat(2, 1fr); } }
        @media (max-width: 782px) { .kv-grid { grid-template-columns: 1fr; } }
    </style>

    <!-- ════════════════════════════════════════
         PAGE HEADER
         ════════════════════════════════════════ -->
    <div class="kv-acct-header">
        <div class="kv-acct-avatar" id="kv-avatar">
            <?php
            $avatar = get_avatar_url($user->ID, ['size' => 128]);
            if ($avatar) {
                echo '<img src="' . esc_url($avatar) . '" alt="">';
            } else {
                echo esc_html(strtoupper(substr($user->user_login, 0, 1)));
            }
            ?>
        </div>
        <div class="kv-acct-meta">
            <h2 id="kv-display-name"><?php echo esc_html($user->display_name ?: $user->user_login); ?></h2>
            <p>
                <span id="kv-header-email"><?php echo esc_html($user->user_email); ?></span>
                &nbsp;·&nbsp;
                <span class="kv-acct-badge">🛡️ <?php echo esc_html(implode(', ', $user->roles)); ?></span>
                &nbsp;·&nbsp;
                <span style="color:#94a3b8;font-size:12px;">สมาชิกตั้งแต่ <?php echo date_i18n(get_option('date_format'), strtotime($user->user_registered)); ?></span>
            </p>
        </div>
        <div style="margin-left:auto;text-align:right;">
            <a href="<?php echo esc_url(wp_login_url()); ?>" id="kv-logout-link" class="kv-btn kv-btn-danger" style="width:auto;text-decoration:none;" onclick="return confirm('ออกจากระบบ?')">
                🚪 ออกจากระบบ
            </a>
        </div>
    </div>

    <!-- ════════════════════════════════════════
         3-COLUMN GRID
         ════════════════════════════════════════ -->
    <div class="kv-grid">

        <!-- ── CARD 1: แก้ Username ── -->
        <div class="kv-card">
            <h3>👤 เปลี่ยน Username</h3>
            <div class="kv-warning-box">
                ⚠️ การเปลี่ยน Username จะทำให้คุณถูก Logout ออกจากระบบ และต้อง Login ใหม่ด้วย Username ใหม่
            </div>
            <div class="kv-field">
                <label>Username ปัจจุบัน</label>
                <input type="text" value="<?php echo esc_attr($user->user_login); ?>" readonly id="kv-current-username">
            </div>
            <div class="kv-field">
                <label>Username ใหม่ *</label>
                <input type="text" id="kv-new-username" placeholder="เช่น admin_kv หรือ kv_admin" autocomplete="off" autocapitalize="off" spellcheck="false">
            </div>
            <div class="kv-notice" id="kv-username-notice"></div>
            <button class="kv-btn kv-btn-primary" id="kv-save-username" style="margin-top:12px;">
                💾 บันทึก Username
            </button>
        </div>

        <!-- ── CARD 2: แก้ Email ── -->
        <div class="kv-card">
            <h3>📧 เปลี่ยน Email</h3>
            <div class="kv-field">
                <label>Email ปัจจุบัน</label>
                <input type="email" value="<?php echo esc_attr($user->user_email); ?>" readonly id="kv-current-email">
            </div>
            <div class="kv-field">
                <label>Email ใหม่ *</label>
                <input type="email" id="kv-new-email" placeholder="email@company.com" autocomplete="off">
            </div>
            <div class="kv-notice" id="kv-email-notice"></div>
            <button class="kv-btn kv-btn-primary" id="kv-save-email" style="margin-top:12px;">
                💾 บันทึก Email
            </button>
        </div>

        <!-- ── CARD 3: แก้ Password ── -->
        <div class="kv-card">
            <h3>🔑 เปลี่ยนรหัสผ่าน</h3>
            <div class="kv-warning-box">
                ⚠️ การเปลี่ยนรหัสผ่านจะทำให้คุณถูก Logout และต้อง Login ใหม่
            </div>
            <div class="kv-field">
                <label>รหัสผ่านปัจจุบัน *</label>
                <div class="kv-eye-toggle">
                    <input type="password" id="kv-current-pass" placeholder="••••••••" autocomplete="current-password">
                    <button type="button" onclick="kvTogglePass('kv-current-pass',this)" title="แสดง/ซ่อน">👁️</button>
                </div>
            </div>
            <div class="kv-field">
                <label>รหัสผ่านใหม่ * (อย่างน้อย 8 ตัว)</label>
                <div class="kv-eye-toggle">
                    <input type="password" id="kv-new-pass" placeholder="••••••••" autocomplete="new-password" oninput="kvCheckStrength(this.value)">
                    <button type="button" onclick="kvTogglePass('kv-new-pass',this)" title="แสดง/ซ่อน">👁️</button>
                </div>
                <div class="kv-strength-bar"><div class="kv-strength-fill" id="kv-strength-fill"></div></div>
                <div class="kv-strength-text" id="kv-strength-text">ความแข็งแรงของรหัสผ่าน</div>
            </div>
            <div class="kv-field">
                <label>ยืนยันรหัสผ่านใหม่ *</label>
                <div class="kv-eye-toggle">
                    <input type="password" id="kv-confirm-pass" placeholder="••••••••" autocomplete="new-password">
                    <button type="button" onclick="kvTogglePass('kv-confirm-pass',this)" title="แสดง/ซ่อน">👁️</button>
                </div>
            </div>
            <div class="kv-notice" id="kv-pass-notice"></div>
            <button class="kv-btn kv-btn-primary" id="kv-save-pass" style="margin-top:12px;">
                💾 บันทึกรหัสผ่าน
            </button>
        </div>

        <!-- ── CARD 4: ข้อมูลบัญชี ── -->
        <div class="kv-card">
            <h3>ℹ️ ข้อมูลบัญชีปัจจุบัน</h3>
            <div class="kv-info-row">
                <span>Username</span>
                <span>
                    <span id="kv-info-username"><?php echo esc_html($user->user_login); ?></span>
                    <button class="kv-copy-btn" onclick="kvCopy('kv-info-username',this)">📋 Copy</button>
                </span>
            </div>
            <div class="kv-info-row">
                <span>Email</span>
                <span>
                    <span id="kv-info-email"><?php echo esc_html($user->user_email); ?></span>
                    <button class="kv-copy-btn" onclick="kvCopy('kv-info-email',this)">📋 Copy</button>
                </span>
            </div>
            <div class="kv-info-row">
                <span>Role</span>
                <span><?php echo esc_html(implode(', ', $user->roles)); ?></span>
            </div>
            <div class="kv-info-row">
                <span>User ID</span>
                <span><?php echo esc_html($user->ID); ?></span>
            </div>
            <div class="kv-info-row">
                <span>สมัครสมาชิก</span>
                <span><?php echo date_i18n(get_option('date_format'), strtotime($user->user_registered)); ?></span>
            </div>
            <div class="kv-info-row">
                <span>URL Login</span>
                <span>
                    <a href="<?php echo esc_url(wp_login_url()); ?>" target="_blank" style="font-size:12px;color:inherit;"><?php echo esc_html(str_replace(['http://', 'https://'], '', wp_login_url())); ?></a>
                    <button class="kv-copy-btn" onclick="navigator.clipboard.writeText('<?php echo esc_js(wp_login_url()); ?>');this.textContent='✓ Copied!'">📋 Copy</button>
                </span>
            </div>
        </div>

        <!-- ── CARD 5: ส่งข้อมูลให้ลูกค้า ── -->
        <div class="kv-card">
            <h3>📤 ส่งข้อมูล Login ให้ลูกค้า</h3>
            <p style="color:#64748b;font-size:13px;margin:0 0 14px;">สร้างข้อความพร้อมส่งให้ลูกค้า (ไม่มีรหัสผ่าน — ควรแจ้งแยกต่างหาก)</p>
            <div class="kv-field">
                <label>ชื่อลูกค้า / บริษัท</label>
                <input type="text" id="kv-client-name" placeholder="เช่น คุณสมศรี / ABC Co., Ltd.">
            </div>
            <div class="kv-field">
                <label>รหัสผ่านที่ตั้งไว้</label>
                <div class="kv-eye-toggle">
                    <input type="password" id="kv-client-pass" placeholder="กรอกรหัสผ่านที่จะส่ง">
                    <button type="button" onclick="kvTogglePass('kv-client-pass',this)" title="แสดง/ซ่อน">👁️</button>
                </div>
            </div>
            <button class="kv-btn kv-btn-primary" onclick="kvGenerateHandover()" style="margin-bottom:10px;">
                📋 สร้างข้อความส่งมอบ
            </button>
            <textarea id="kv-handover-text" rows="8"
                style="width:100%;padding:10px 12px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-family:inherit;resize:vertical;display:none;"></textarea>
            <button class="kv-btn kv-btn-primary" onclick="kvCopyHandover()" id="kv-copy-handover" style="display:none;margin-top:8px;background:#10b981;">
                📋 Copy ข้อความ
            </button>
        </div>

        <!-- ── CARD 6: Security Tips ── -->
        <div class="kv-card">
            <h3>🛡️ คำแนะนำความปลอดภัย</h3>
            <div style="display:flex;flex-direction:column;gap:12px;">
                <?php
                $tips = [
                    ['✅', 'ตั้งรหัสผ่านอย่างน้อย 12 ตัวอักษร'],
                    ['✅', 'ผสม A-Z, a-z, 0-9 และอักขระพิเศษ !@#$'],
                    ['✅', 'ไม่ใช้ชื่อบริษัท วันเกิด หรือคำศัพท์ทั่วไป'],
                    ['✅', 'เปลี่ยนรหัสผ่านทุก 3-6 เดือน'],
                    ['✅', 'อย่าใช้รหัสผ่านเดียวกันกับ service อื่น'],
                    ['✅', 'แจ้งรหัสผ่านผ่านช่องทางที่ปลอดภัย (ไม่ผ่าน email)'],
                    ['✅', 'ลบ user ทดสอบออกก่อน go-live'],
                ];
                foreach ($tips as $tip) {
                    echo '<div style="display:flex;gap:10px;align-items:flex-start;font-size:13px;">';
                    echo '<span>' . $tip[0] . '</span>';
                    echo '<span style="color:#475569;">' . esc_html($tip[1]) . '</span>';
                    echo '</div>';
                }
                ?>
            </div>
            <div style="margin-top:20px;padding:14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;">
                <div style="font-weight:600;color:#166534;font-size:13px;margin-bottom:8px;">🔐 ตัวอย่างรหัสผ่านที่แข็งแกร่ง</div>
                <div id="kv-generated-pass" style="font-family:monospace;font-size:15px;font-weight:700;color:#15803d;letter-spacing:1px;">
                    <?php
                    $chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789!@#$%';
                    $pass = '';
                    for ($i = 0; $i < 14; $i++) $pass .= $chars[random_int(0, strlen($chars) - 1)];
                    echo esc_html($pass);
                    ?>
                </div>
                <div style="display:flex;gap:8px;margin-top:8px;">
                    <button class="kv-copy-btn" onclick="kvCopy('kv-generated-pass',this)" style="padding:4px 12px;">📋 Copy</button>
                    <button class="kv-copy-btn" onclick="kvRegenPass()" style="padding:4px 12px;">🔄 สร้างใหม่</button>
                    <button class="kv-copy-btn" onclick="kvUseGeneratedPass()" style="padding:4px 12px;color:#0056d6;">↑ ใช้รหัสนี้</button>
                </div>
            </div>
        </div>

    </div><!-- end .kv-grid -->

    <!-- ════════════════════════════════════════
         JAVASCRIPT
         ════════════════════════════════════════ -->
    <script>
    (function(){
        var AJAX   = <?php echo json_encode($ajaxurl); ?>;
        var NONCE  = <?php echo json_encode($nonce); ?>;
        var loginUrl = <?php echo json_encode(wp_login_url()); ?>;

        /* ── helper: show notice ── */
        function showNotice(id, type, msg) {
            var el = document.getElementById(id);
            el.className = 'kv-notice ' + type;
            el.textContent = (type === 'success' ? '✓ ' : type === 'error' ? '✗ ' : 'ℹ ') + msg;
            el.style.display = 'flex';
        }
        function hideNotice(id) { document.getElementById(id).style.display = 'none'; }

        /* ── helper: loading state ── */
        function setLoading(btnId, loading) {
            var btn = document.getElementById(btnId);
            btn.disabled = loading;
            btn.textContent = loading ? '⏳ กำลังบันทึก...' : btn.getAttribute('data-label');
        }

        /* ── save button labels ── */
        ['kv-save-username','kv-save-email','kv-save-pass'].forEach(function(id) {
            var el = document.getElementById(id);
            el.setAttribute('data-label', el.textContent);
        });

        /* ── Save Username ── */
        document.getElementById('kv-save-username').addEventListener('click', function() {
            var val = document.getElementById('kv-new-username').value.trim();
            if (!val) { showNotice('kv-username-notice','error','กรุณากรอก Username ใหม่'); return; }

            setLoading('kv-save-username', true);
            var fd = new FormData();
            fd.append('action','kv_change_username');
            fd.append('nonce', NONCE);
            fd.append('new_username', val);

            fetch(AJAX, {method:'POST', body:fd, credentials:'same-origin'})
                .then(function(r){ return r.json(); })
                .then(function(r) {
                    setLoading('kv-save-username', false);
                    if (r.success) {
                        showNotice('kv-username-notice','success', r.data.msg);
                        document.getElementById('kv-current-username').value = r.data.new_username;
                        document.getElementById('kv-info-username').textContent = r.data.new_username;
                        document.getElementById('kv-new-username').value = '';
                        if (r.data.logout) {
                            setTimeout(function(){ window.location.href = loginUrl; }, 2000);
                        }
                    } else {
                        showNotice('kv-username-notice','error', r.data.msg);
                    }
                })
                .catch(function(e){ setLoading('kv-save-username',false); showNotice('kv-username-notice','error','เกิดข้อผิดพลาด: '+e.message); });
        });

        /* ── Save Email ── */
        document.getElementById('kv-save-email').addEventListener('click', function() {
            var val = document.getElementById('kv-new-email').value.trim();
            if (!val) { showNotice('kv-email-notice','error','กรุณากรอก Email ใหม่'); return; }

            setLoading('kv-save-email', true);
            var fd = new FormData();
            fd.append('action','kv_change_email');
            fd.append('nonce', NONCE);
            fd.append('new_email', val);

            fetch(AJAX, {method:'POST', body:fd, credentials:'same-origin'})
                .then(function(r){ return r.json(); })
                .then(function(r) {
                    setLoading('kv-save-email', false);
                    if (r.success) {
                        showNotice('kv-email-notice','success', r.data.msg);
                        document.getElementById('kv-current-email').value = r.data.new_email;
                        document.getElementById('kv-info-email').textContent = r.data.new_email;
                        document.getElementById('kv-header-email').textContent = r.data.new_email;
                        document.getElementById('kv-new-email').value = '';
                    } else {
                        showNotice('kv-email-notice','error', r.data.msg);
                    }
                })
                .catch(function(e){ setLoading('kv-save-email',false); showNotice('kv-email-notice','error','เกิดข้อผิดพลาด: '+e.message); });
        });

        /* ── Save Password ── */
        document.getElementById('kv-save-pass').addEventListener('click', function() {
            var cur  = document.getElementById('kv-current-pass').value;
            var np   = document.getElementById('kv-new-pass').value;
            var conf = document.getElementById('kv-confirm-pass').value;
            if (!cur||!np||!conf) { showNotice('kv-pass-notice','error','กรุณากรอกข้อมูลให้ครบ'); return; }
            if (np !== conf) { showNotice('kv-pass-notice','error','รหัสผ่านใหม่ไม่ตรงกัน'); return; }

            setLoading('kv-save-pass', true);
            var fd = new FormData();
            fd.append('action','kv_change_password');
            fd.append('nonce', NONCE);
            fd.append('current_password', cur);
            fd.append('new_password', np);
            fd.append('confirm_password', conf);

            fetch(AJAX, {method:'POST', body:fd, credentials:'same-origin'})
                .then(function(r){ return r.json(); })
                .then(function(r) {
                    setLoading('kv-save-pass', false);
                    if (r.success) {
                        showNotice('kv-pass-notice','success', r.data.msg);
                        document.getElementById('kv-current-pass').value = '';
                        document.getElementById('kv-new-pass').value = '';
                        document.getElementById('kv-confirm-pass').value = '';
                        if (r.data.logout) {
                            setTimeout(function(){ window.location.href = loginUrl; }, 2000);
                        }
                    } else {
                        showNotice('kv-pass-notice','error', r.data.msg);
                    }
                })
                .catch(function(e){ setLoading('kv-save-pass',false); showNotice('kv-pass-notice','error','เกิดข้อผิดพลาด: '+e.message); });
        });

        /* ── Password strength meter ── */
        window.kvCheckStrength = function(val) {
            var score = 0;
            if (val.length >= 8)  score++;
            if (val.length >= 12) score++;
            if (/[a-z]/.test(val)) score++;
            if (/[A-Z]/.test(val)) score++;
            if (/[0-9]/.test(val)) score++;
            if (/[^a-zA-Z0-9]/.test(val)) score++;

            var fill = document.getElementById('kv-strength-fill');
            var text = document.getElementById('kv-strength-text');
            var pct  = Math.min(100, Math.round((score/6)*100));
            fill.style.width = pct + '%';

            if (score <= 1)      { fill.style.background='#ef4444'; text.textContent='อ่อนมาก — ไม่ปลอดภัย'; text.style.color='#ef4444'; }
            else if (score <= 2) { fill.style.background='#f97316'; text.textContent='อ่อน'; text.style.color='#f97316'; }
            else if (score <= 3) { fill.style.background='#eab308'; text.textContent='ปานกลาง'; text.style.color='#eab308'; }
            else if (score <= 4) { fill.style.background='#22c55e'; text.textContent='ดี'; text.style.color='#22c55e'; }
            else                 { fill.style.background='#10b981'; text.textContent='แข็งแกร่งมาก ✓'; text.style.color='#10b981'; }
        };

        /* ── Toggle password visibility ── */
        window.kvTogglePass = function(inputId, btn) {
            var inp = document.getElementById(inputId);
            inp.type = inp.type === 'password' ? 'text' : 'password';
            btn.textContent = inp.type === 'password' ? '👁️' : '🙈';
        };

        /* ── Copy text from element ── */
        window.kvCopy = function(elId, btn) {
            var text = document.getElementById(elId).textContent;
            navigator.clipboard.writeText(text).then(function() {
                var orig = btn.textContent;
                btn.textContent = '✓ Copied!';
                setTimeout(function(){ btn.textContent = orig; }, 2000);
            });
        };

        /* ── Random password generator ── */
        window.kvRegenPass = function() {
            var chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789!@#$%';
            var pass = '';
            var arr  = new Uint8Array(14);
            crypto.getRandomValues(arr);
            arr.forEach(function(v){ pass += chars[v % chars.length]; });
            document.getElementById('kv-generated-pass').textContent = pass;
        };

        window.kvUseGeneratedPass = function() {
            var pass = document.getElementById('kv-generated-pass').textContent;
            document.getElementById('kv-new-pass').value = pass;
            document.getElementById('kv-confirm-pass').value = pass;
            kvCheckStrength(pass);
            document.getElementById('kv-client-pass').value = pass;
        };

        /* ── Generate handover message ── */
        window.kvGenerateHandover = function() {
            var name = document.getElementById('kv-client-name').value.trim() || 'ลูกค้า';
            var pass = document.getElementById('kv-client-pass').value;
            var username = document.getElementById('kv-info-username').textContent;
            var email    = document.getElementById('kv-info-email').textContent;
            var url      = loginUrl;

            var msg = '📦 ข้อมูล Login เว็บไซต์\n';
            msg += '═══════════════════════\n';
            msg += 'เรียน ' + name + '\n\n';
            msg += 'นี่คือข้อมูลสำหรับเข้าใช้งานเว็บไซต์:\n\n';
            msg += '🌐 URL เว็บไซต์: ' + window.location.origin + '\n';
            msg += '🔐 หน้า Login: ' + url + '\n\n';
            msg += '👤 Username: ' + username + '\n';
            msg += '📧 Email: ' + email + '\n';
            if (pass) msg += '🔑 Password: ' + pass + '\n';
            msg += '\n═══════════════════════\n';
            msg += '⚠️ กรุณาเปลี่ยนรหัสผ่านหลังจาก Login ครั้งแรก\n';
            msg += '⚠️ อย่าเปิดเผยข้อมูลนี้กับบุคคลอื่น\n';
            msg += '═══════════════════════\n';
            msg += 'หากมีปัญหาติดต่อ: ' + document.getElementById('kv-info-email').textContent;

            var ta  = document.getElementById('kv-handover-text');
            var btn = document.getElementById('kv-copy-handover');
            ta.value = msg;
            ta.style.display = 'block';
            btn.style.display = 'flex';
        };

        window.kvCopyHandover = function() {
            var text = document.getElementById('kv-handover-text').value;
            navigator.clipboard.writeText(text).then(function() {
                var btn = document.getElementById('kv-copy-handover');
                var orig = btn.textContent;
                btn.textContent = '✓ Copied!';
                setTimeout(function(){ btn.textContent = orig; }, 2000);
            });
        };

    })();
    </script>

    </div><!-- .kv-acct-wrap -->
    <?php
}
