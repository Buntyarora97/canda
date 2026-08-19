<?php
/** Admin: store settings + SMTP + test email + data retention. */
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$storeKeys = ['store_name','store_phone','store_email','store_address','store_hours','announcement_text','social_facebook','social_instagram','social_youtube','mail_notify_email','mail_cc_email','mail_send_ack','mail_footer','data_retention_months'];
$smtpKeys  = ['smtp_host','smtp_port','smtp_user','smtp_pass','smtp_encryption','mail_from_email','mail_from_name'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_guard();
    $action = $_POST['action'] ?? 'save_settings';

    if ($action === 'save_settings') {
        foreach (array_merge($storeKeys, $smtpKeys) as $k) {
            if (!array_key_exists($k, $_POST)) continue;
            if ($k === 'smtp_pass' && $_POST[$k] === '') continue; // keep existing password if left blank
            setting_set($k, trim((string)$_POST[$k]));
        }
        log_activity('settings_save', 'Updated settings');
        admin_flash('Settings saved.');
        redirect(site_url('admin/settings.php'));
    }

    if ($action === 'test_email') {
        $to = trim((string)($_POST['test_to'] ?? ''));
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            admin_flash('Enter a valid email address for the test.', 'err');
        } else {
            $result = send_mail($to, 'GIO Mobility — SMTP test',
                email_layout('SMTP test successful', '<p>This test message confirms your email settings are working.</p><p>Sent from the GIO Mobility admin panel.</p>'),
                'SMTP test successful — your GIO Mobility email settings are working.');
            if ($result['ok']) { admin_flash('Test email sent to ' . $to . ' via ' . $result['transport'] . '.'); log_activity('test_email', "OK to $to"); }
            else { admin_flash('Send failed: ' . ($result['error'] ?? 'unknown error'), 'err'); log_activity('test_email', "FAILED to $to: " . ($result['error'] ?? '')); }
        }
        redirect(site_url('admin/settings.php#smtp'));
    }

    if ($action === 'purge') {
        $days = max(30, (int) setting('data_retention_months', '24')) * 30;
        q("DELETE FROM enquiries WHERE created_at < DATE_SUB(NOW(), INTERVAL $days DAY) AND status IN ('Closed','Spam','Converted')");
        $n = db()->rowCount();
        log_activity('data_purge', "Purged $n closed enquiries older than $days days");
        admin_flash("$n closed/spam enquir" . ($n === 1 ? 'y' : 'ies') . " older than $days days purged.");
        redirect(site_url('admin/settings.php#retention'));
    }
}

$cfg = [];
foreach (array_merge($storeKeys, $smtpKeys) as $k) $cfg[$k] = setting($k, '');

admin_head('Settings', 'settings.php');
?>
<form method="post" class="admin-form">
<?= csrf_field() ?><input type="hidden" name="action" value="save_settings">
<section class="panel">
    <h2>Store information</h2>
    <div class="form-grid">
        <label>Store name <input name="store_name" value="<?= e($cfg['store_name']) ?>" maxlength="120"></label>
        <label>Phone <input name="store_phone" value="<?= e($cfg['store_phone']) ?>" maxlength="40"></label>
        <label>Support email <input name="store_email" value="<?= e($cfg['store_email']) ?>" maxlength="120"></label>
        <label>Business hours <input name="store_hours" value="<?= e($cfg['store_hours']) ?>" maxlength="120" placeholder="Mon–Fri 10am–4pm Pacific"></label>
        <label class="span2">Address <input name="store_address" value="<?= e($cfg['store_address']) ?>" maxlength="190"></label>
        <label class="span2">Announcement bar <input name="announcement_text" value="<?= e($cfg['announcement']) ?>" maxlength="190" placeholder="Shown at the very top of every page — leave blank to hide"></label>
    </div>
</section>
<section class="panel">
    <h2>Social</h2>
    <div class="form-grid">
        <label>Facebook <input name="social_facebook" value="<?= e($cfg['social_facebook']) ?>" maxlength="255"></label>
        <label>Instagram <input name="social_instagram" value="<?= e($cfg['social_instagram']) ?>" maxlength="255"></label>
        <label>YouTube <input name="social_youtube" value="<?= e($cfg['social_youtube']) ?>" maxlength="255"></label>
    </div>
</section>
<section class="panel" id="smtp">
    <h2>Email &amp; enquiries</h2>
    <div class="form-grid">
        <label>Notify email (receives new enquiries) <input type="email" name="mail_notify_email" value="<?= e($cfg['notify_email']) ?>" maxlength="120"></label>
        <label>Send customer acknowledgement <select name="mail_send_ack"><option value="1" <?= $cfg['mail_send_ack'] !== '0' ? 'selected' : '' ?>>Yes</option><option value="0" <?= $cfg['mail_send_ack'] === '0' ? 'selected' : '' ?>>No</option></select></label>
        <label>CC email (optional) <input name="mail_cc_email" value="<?= e($cfg['mail_cc_email']) ?>" maxlength="120"></label>
        <label class="span2">Email footer line <input name="mail_footer" value="<?= e($cfg['mail_footer']) ?>" maxlength="190"></label>
    </div>
    <h3>SMTP (optional — falls back to server mail())</h3>
    <div class="form-grid">
        <label>SMTP host <input name="smtp_host" value="<?= e($cfg['smtp_host']) ?>" maxlength="190" placeholder="mail.example.com"></label>
        <label>Port <input name="smtp_port" value="<?= e($cfg['smtp_port']) ?>" maxlength="6" placeholder="587"></label>
        <label>Username <input name="smtp_user" value="<?= e($cfg['smtp_user']) ?>" maxlength="190" autocomplete="off"></label>
        <label>Password <input type="password" name="smtp_pass" value="" placeholder="Leave blank to keep current" autocomplete="new-password"></label>
        <label>Encryption <select name="smtp_encryption">
            <?php foreach (['tls' => 'STARTTLS (587)', 'ssl' => 'SSL (465)', 'none' => 'None (25)'] as $k => $lbl): ?>
            <option value="<?= $k ?>" <?= $cfg['smtp_encryption'] === $k ? 'selected' : '' ?>><?= $lbl ?></option>
            <?php endforeach; ?>
        </select></label>
        <label>From email <input name="mail_from_email" value="<?= e($cfg['mail_from_email']) ?>" maxlength="120" placeholder="no-reply@yourdomain.ca"></label>
        <label>From name <input name="mail_from_name" value="<?= e($cfg['mail_from_name']) ?>" maxlength="90" placeholder="GIO Mobility Canada"></label>
    </div>
    <p class="muted">Tip: where hosting allows, SMTP credentials can instead live in <code>gio-config.php</code> outside the web root — see README.</p>
</section>
<section class="panel" id="retention">
    <h2>Data retention</h2>
    <label>Automatically purge closed/spam enquiries after (months) <input type="number" name="data_retention_months" min="1" value="<?= e($cfg['data_retention_months'] ?: '24') ?>"></label>
    <button class="btn btn-primary btn-lg">Save settings</button>
</section>
</form>

<section class="panel">
    <h2>Send a test email</h2>
    <form method="post" class="inline-search">
        <?= csrf_field() ?><input type="hidden" name="action" value="test_email">
        <input type="email" name="test_to" placeholder="you@example.com" required>
        <button class="btn">Send test</button>
    </form>
</section>
<section class="panel">
    <h2>Purge old data now</h2>
    <p class="muted">Permanently deletes closed, spam and converted enquiries older than the retention window above.</p>
    <form method="post" data-confirm="Permanently purge old closed enquiries? This cannot be undone.">
        <?= csrf_field() ?><input type="hidden" name="action" value="purge">
        <button class="btn btn-danger">Run purge</button>
    </form>
</section>
<?php admin_foot(); ?>
