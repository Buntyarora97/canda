<?php
/** Newsletter signup (footer). */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'message' => 'Method not allowed.'], 405);
}
$email = trim((string)($_POST['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
    json_response(['ok' => false, 'message' => 'Please enter a valid email address.'], 422);
}
if (rate_limited('newsletter', 5, 600)) {
    json_response(['ok' => false, 'message' => 'Too many attempts — please try again later.'], 429);
}
rate_hit('newsletter');
try {
    q('INSERT INTO newsletter_subscribers (email) VALUES (?) ON DUPLICATE KEY UPDATE email = email', [$email]);
    json_response(['ok' => true, 'message' => 'Thanks for joining — you\'re on the list!']);
} catch (Throwable $t) {
    json_response(['ok' => false, 'message' => 'Something went wrong. Please try again.'], 500);
}
