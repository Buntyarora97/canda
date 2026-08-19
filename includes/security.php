<?php
/**
 * Security helpers: secure sessions, CSRF tokens, rate limiting, honeypot.
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';

/** Start a hardened session (idempotent). */
function secure_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_name('GIOSESSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
    if (empty($_SESSION['_initiated'])) {
        session_regenerate_id(true);
        $_SESSION['_initiated'] = time();
    }
}

/* ------------------------------------------------------------ CSRF ------- */
function csrf_token(): string
{
    secure_session();
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(?string $token): bool
{
    secure_session();
    return is_string($token) && isset($_SESSION['_csrf'])
        && hash_equals($_SESSION['_csrf'], $token);
}

/* ------------------------------------------------------------ honeypot --- */
/** Returns true when the (hidden) honeypot field was filled => bot. */
function honeypot_tripped(array $post): bool
{
    return !empty($post['website']) || !empty($post['company_url']);
}

/* ------------------------------------------------------------ rate limit - */
/**
 * Simple IP+action rate limit using the activity-safe session store and a
 * DB bucket so it works across sessions. $max attempts per $windowSeconds.
 */
function rate_limited(string $action, int $max = 5, int $windowSeconds = 600): bool
{
    $ip = client_ip();
    $since = date('Y-m-d H:i:s', time() - $windowSeconds);
    $count = (int) val(
        'SELECT COUNT(*) FROM rate_limits WHERE action = ? AND ip_hash = ? AND created_at > ?',
        [$action, hash_ip($ip), $since]
    );
    return $count >= $max;
}

function rate_hit(string $action): void
{
    q('INSERT INTO rate_limits (action, ip_hash, created_at) VALUES (?,?,NOW())',
        [$action, hash_ip(client_ip())]);
    // Housekeeping (cheap, probabilistic).
    if (random_int(1, 50) === 1) {
        q('DELETE FROM rate_limits WHERE created_at < (NOW() - INTERVAL 2 DAY)');
    }
}

/* ------------------------------------------------------------ misc ------- */
function client_ip(): string
{
    // Do NOT trust X-Forwarded-For on shared hosting blindly.
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/** Store only a salted hash of the IP (privacy-friendly). */
function hash_ip(string $ip): string
{
    return hash_hmac('sha256', $ip, APP_KEY);
}

/** Trailing e() is defined in functions.php; guard against load order. */
if (!function_exists('e')) {
    function e(?string $s): string
    {
        return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
