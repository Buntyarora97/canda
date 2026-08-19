<?php
/** Admin: my account — name + change password. */
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$me = current_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_guard();
    $action = $_POST['action'] ?? '';
    if ($action === 'profile') {
        $name = trim((string)($_POST['name'] ?? ''));
        if ($name !== '') {
            q('UPDATE admins SET name = ? WHERE id = ?', [$name, $me['id']]);
            log_activity('profile_update', 'Updated display name');
            admin_flash('Profile updated.');
        }
    } elseif ($action === 'password') {
        $cur = (string)($_POST['current'] ?? '');
        $new = (string)($_POST['new'] ?? '');
        $confirm = (string)($_POST['confirm'] ?? '');
        $row_ = row('SELECT password_hash FROM admins WHERE id = ?', [$me['id']]);
        if (!password_verify($cur, $row_['password_hash'])) {
            admin_flash('Current password is incorrect.', 'err');
        } elseif (strlen($new) < 10) {
            admin_flash('New password must be at least 10 characters.', 'err');
        } elseif ($new !== $confirm) {
            admin_flash('New passwords do not match.', 'err');
        } else {
            q('UPDATE admins SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $me['id']]);
            log_activity('password_change', 'Password changed');
            admin_flash('Password changed successfully.');
        }
    }
    redirect(site_url('admin/profile.php'));
}

admin_head('My account', 'profile.php');
?>
<div class="panel-grid">
<section class="panel">
    <h2>Profile</h2>
    <dl class="detail-list">
        <dt>Email</dt><dd><?= e($me['email']) ?></dd>
        <dt>Role</dt><dd><?= e(ucfirst($me['role'])) ?></dd>
        <dt>Last sign-in</dt><dd><?= $me['last_login_at'] ? e(date('M j, Y g:ia', strtotime($me['last_login_at']))) : '—' ?></dd>
    </dl>
    <form method="post" class="admin-form">
        <?= csrf_field() ?><input type="hidden" name="action" value="profile">
        <label>Display name <input name="name" value="<?= e($me['name']) ?>" maxlength="100" required></label>
        <button class="btn">Save</button>
    </form>
</section>
<section class="panel">
    <h2>Change password</h2>
    <form method="post" class="admin-form">
        <?= csrf_field() ?><input type="hidden" name="action" value="password">
        <label>Current password <input type="password" name="current" required autocomplete="current-password"></label>
        <label>New password (min 10 characters) <input type="password" name="new" required minlength="10" autocomplete="new-password"></label>
        <label>Confirm new password <input type="password" name="confirm" required autocomplete="new-password"></label>
        <button class="btn btn-primary">Change password</button>
    </form>
</section>
</div>
<?php admin_foot(); ?>
