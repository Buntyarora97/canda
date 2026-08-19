<?php
declare(strict_types=1);
require_once __DIR__ . '/_init.php';
log_activity('logout', 'Admin signed out');
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
redirect(site_url('admin/login.php'));
