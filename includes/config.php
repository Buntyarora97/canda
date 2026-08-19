<?php
/**
 * GIO Mobility Canada — Core Configuration
 *
 * IMPORTANT (shared hosting / cPanel):
 * The recommended setup is to place your real credentials in a file OUTSIDE
 * the public web root, e.g.  /home/USER/gio-config.php  (one level above
 * public_html). This file automatically loads it if present.
 *
 * Alternatively copy includes/config.sample.php to includes/config.local.php
 * and fill in your values (config.local.php is blocked by .htaccess rules and
 * should never be committed to version control).
 */

declare(strict_types=1);

/* ------------------------------------------------------------- basics ---- */
define('GIO_ROOT', dirname(__DIR__));
define('GIO_INCLUDES', __DIR__);

// Detect environment: set to 'production' on the live server.
if (!defined('GIO_ENV')) {
    define('GIO_ENV', getenv('GIO_ENV') ?: 'production');
}

/* ------------------------------------------- external override files ----- */
// 1) Preferred: one level above the web root (not publicly reachable).
$externalConfig = dirname(GIO_ROOT) . '/gio-config.php';
if (is_file($externalConfig)) {
    require $externalConfig;
}
// 2) Fallback: local config inside includes/ (denied by .htaccess).
if (is_file(GIO_INCLUDES . '/config.local.php')) {
    require GIO_INCLUDES . '/config.local.php';
}

/* ------------------------------------------------------------- database -- */
defined('DB_HOST') or define('DB_HOST', getenv('GIO_DB_HOST') ?: 'localhost');
defined('DB_NAME') or define('DB_NAME', getenv('GIO_DB_NAME') ?: 'gio_mobility');
defined('DB_USER') or define('DB_USER', getenv('GIO_DB_USER') ?: 'root');
defined('DB_PASS') or define('DB_PASS', getenv('GIO_DB_PASS') ?: '');
defined('DB_CHARSET') or define('DB_CHARSET', 'utf8mb4');

/* ------------------------------------------------------------- site ------ */
// Full base URL of the site, no trailing slash. Auto-detected when empty.
if (!defined('SITE_URL')) {
    // Keep generated asset/navigation URLs relative unless the real public
    // domain is explicitly configured. This prevents localhost/proxy URLs
    // from being sent to a visitor's browser.
    define('SITE_URL', rtrim((string)(getenv('GIO_SITE_URL') ?: ''), '/'));
}
defined('SITE_NAME') or define('SITE_NAME', 'GIO Mobility Canada');

/* ------------------------------------------------------------- mail ------ */
// These act as defaults; the admin Email Settings screen (stored in the
// database) takes precedence. Keeping them here lets you hard-set SMTP
// outside the database if you prefer.
defined('SMTP_HOST') or define('SMTP_HOST', getenv('GIO_SMTP_HOST') ?: '');
defined('SMTP_PORT') or define('SMTP_PORT', (int)(getenv('GIO_SMTP_PORT') ?: 587));
defined('SMTP_USER') or define('SMTP_USER', getenv('GIO_SMTP_USER') ?: '');
defined('SMTP_PASS') or define('SMTP_PASS', getenv('GIO_SMTP_PASS') ?: '');
defined('SMTP_ENCRYPTION') or define('SMTP_ENCRYPTION', getenv('GIO_SMTP_ENCRYPTION') ?: 'tls'); // tls | ssl | none

/* ------------------------------------------------------------- uploads --- */
define('UPLOAD_DIR', GIO_ROOT . '/uploads');
define('UPLOAD_URL', SITE_URL . '/uploads');
define('MAX_UPLOAD_BYTES', 8 * 1024 * 1024); // 8 MB per image

/* ------------------------------------------------------------- security -- */
// Random per-install key used for HMAC signing (CSRF, form tokens).
// CHANGE THIS after install (any long random string).
// Replit/shared-hosting friendly: prefer a managed secret, then the hosting
// environment variable, and only use the placeholder as an explicit warning.
defined('APP_KEY') or define(
    'APP_KEY',
    getenv('SESSION_SECRET') ?: (getenv('GIO_APP_KEY') ?: 'CHANGE-ME-to-a-long-random-string-64-chars-minimum')
);

/* ------------------------------------------------------------- errors ---- */
if (GIO_ENV === 'production') {
    error_reporting(0);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

date_default_timezone_set('America/Vancouver');

/* Start the hardened session before ANY output so the session cookie is
   always sent (CSRF depends on it). Safe to call repeatedly. */
require_once __DIR__ . '/security.php';
secure_session();
