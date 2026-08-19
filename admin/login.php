<?php
/** Admin login — throttled, CSRF-protected, premium look. */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once GIO_ROOT . '/includes/database.php';
require_once GIO_ROOT . '/includes/security.php';
require_once GIO_ROOT . '/includes/functions.php';

secure_session();
if (admin_logged_in()) redirect(site_url('admin/index.php'));

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif (rate_limited('admin_login', 5, 900)) {
        $error = 'Too many sign-in attempts. Please wait 15 minutes and try again.';
    } else {
        $email = trim((string)($_POST['email'] ?? ''));
        $pass  = (string)($_POST['password'] ?? '');
        $admin = row('SELECT * FROM admins WHERE email = ?', [$email]);
        if ($admin && password_verify($pass, $admin['password_hash'])) {
            if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
                q('UPDATE admins SET password_hash = ? WHERE id = ?', [password_hash($pass, PASSWORD_DEFAULT), $admin['id']]);
            }
            session_regenerate_id(true);
            $_SESSION['admin_id'] = (int) $admin['id'];
            q('UPDATE admins SET last_login_at = NOW() WHERE id = ?', [$admin['id']]);
            log_activity('login', 'Admin signed in');
            redirect(site_url('admin/index.php'));
        }
        rate_hit('admin_login');
        log_activity('login_failed', 'Failed sign-in for ' . mb_substr($email, 0, 60));
        $error = 'Incorrect email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en-CA">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Sign in · GIO Mobility Admin</title>
<link rel="icon" href="<?= e(site_url('assets/images/favicon.png')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(site_url('admin/assets/admin.css')) ?>">
</head>
<body class="login-body">
<div class="login-card">
    <div class="login-brand">
        <img src="<?= e(site_url('assets/images/gio-logo-light.png')) ?>" srcset="<?= e(site_url('assets/images/gio-logo-light@2x.png')) ?> 2x" alt="GIO Mobility" width="128" height="39">
        <p>Mobility Canada · Admin</p>
    </div>
    <form method="post" action="" class="login-form" autocomplete="off">
        <?= csrf_field() ?>
        <h1>Welcome back</h1>
        <p class="login-sub">Sign in to manage products, enquiries and content.</p>
        <?php if ($error): ?><div class="flash flash-err"><?= e($error) ?></div><?php endif; ?>
        <label>Email address
            <input type="email" name="email" required maxlength="190" autocomplete="username" autofocus>
        </label>
        <label>Password
            <input type="password" name="password" required autocomplete="current-password">
        </label>
        <button type="submit" class="btn-login">Sign in</button>
    </form>
    <p class="login-foot">Protected area · Activity is logged</p>
</div>
</body>
</html>
