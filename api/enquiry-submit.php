<?php
/**
 * Enquiry submission endpoint (POST, AJAX).
 * Pipeline: CSRF → honeypot → rate limit → validate → save to MySQL
 *           → notify business + acknowledge customer → JSON response.
 * A mail failure NEVER loses the enquiry — delivery status is recorded.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/mailer.php';

secure_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['ok' => false, 'message' => 'Method not allowed.'], 405);
}

/* ---- CSRF ---- */
if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    json_response(['ok' => false, 'message' => 'Your session expired. Please refresh the page and try again.'], 419);
}

/* ---- Honeypot (bots only) — pretend success ---- */
if (honeypot_tripped($_POST)) {
    json_response(['ok' => true, 'reference' => 'GIO-XXXXXX', 'product_name' => null, 'product_thumb' => null]);
}

/* ---- Rate limit: 5 enquiries / 10 minutes / IP ---- */
if (rate_limited('enquiry', 5, 600)) {
    json_response(['ok' => false, 'message' => 'Too many submissions. Please wait a few minutes or call us at ' . setting('store_phone', '1-855-907-4211') . '.'], 429);
}

/* ---- Validate ---- */
$errors = [];
$firstName = trim((string)($_POST['first_name'] ?? ''));
$lastName  = trim((string)($_POST['last_name'] ?? ''));
$email     = trim((string)($_POST['email'] ?? ''));
$phone     = trim((string)($_POST['phone'] ?? ''));
$province  = strtoupper(trim((string)($_POST['province'] ?? '')));
$city      = trim((string)($_POST['city'] ?? ''));
$postal    = strtoupper(trim((string)($_POST['postal_code'] ?? '')));
$pref      = ($_POST['preferred_contact'] ?? 'Email') === 'Phone' ? 'Phone' : 'Email';
$message   = trim((string)($_POST['message'] ?? ''));
$consent   = !empty($_POST['consent']);

if ($firstName === '' || mb_strlen($firstName) > 60) $errors['first_name'] = 'Please enter your first name.';
if (mb_strlen($lastName) > 60) $errors['last_name'] = 'Last name is too long.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 120) $errors['email'] = 'Please enter a valid email address.';
$digits = preg_replace('/\D/', '', $phone);
if (strlen($digits) < 7 || strlen($digits) > 15) $errors['phone'] = 'Please enter a valid phone number.';
if (!array_key_exists($province, provinces())) $errors['province'] = 'Please select your province.';
if ($city === '' || mb_strlen($city) > 80) $errors['city'] = 'Please enter your city.';
if ($postal !== '' && !preg_match('/^[A-Z]\d[A-Z][ ]?\d[A-Z]\d$/', $postal)) $errors['postal_code'] = 'Format: A1A 1A1';
if (mb_strlen($message) > 2000) $errors['message'] = 'Please keep your message under 2000 characters.';
if (!$consent) $errors['consent'] = 'Please confirm we may contact you about this enquiry.';

/* ---- Product (re-verified server-side — never trust the browser) ---- */
$productId   = (int)($_POST['product_id'] ?? 0);
$product     = $productId ? row('SELECT * FROM products WHERE id = ? AND is_published = 1', [$productId]) : null;
if (!$product) $productId = 0; // unknown/unpublished product => general enquiry (FK safety)
$productName = $product['name'] ?? null;
$productSku  = $product['sku'] ?? null;
$priceShown  = ($product && $product['show_price']) ? $product['price'] : null;
$thumb       = null;
$colour      = trim((string)($_POST['colour'] ?? ''));
$variant     = trim((string)($_POST['variant'] ?? ''));

if ($product) {
    // Colour must match a real variant of this product when provided.
    if ($colour !== '') {
        $valid = val('SELECT COUNT(*) FROM product_variants WHERE product_id = ? AND type = "colour" AND name = ?', [$productId, $colour]);
        if (!$valid) $colour = '';
    }
    if ($variant !== '') {
        $valid = val('SELECT COUNT(*) FROM product_variants WHERE product_id = ? AND type = "option" AND name = ?', [$productId, $variant]);
        if (!$valid) $variant = '';
    }
    $img = product_primary_image($productId);
    $thumb = $img ? img_thumb_url($img['file']) : null;
}

if ($errors) {
    json_response(['ok' => false, 'errors' => $errors], 422);
}

/* ---- Tracking fields (sanitized) ---- */
$pageUrl  = mb_substr(trim((string)($_POST['page_url'] ?? '')), 0, 255);
$referrer = mb_substr(trim((string)($_POST['referrer'] ?? '')), 0, 255);
$utmS     = mb_substr(trim((string)($_POST['utm_source'] ?? '')), 0, 120);
$utmM     = mb_substr(trim((string)($_POST['utm_medium'] ?? '')), 0, 120);
$utmC     = mb_substr(trim((string)($_POST['utm_campaign'] ?? '')), 0, 120);
// Page URL must be same-site; otherwise drop it.
if ($pageUrl !== '' && !str_starts_with($pageUrl, SITE_URL)) $pageUrl = '';

/* ---- Save ---- */
$ref = 'GIO-' . strtoupper(bin2hex(random_bytes(3))) . '-' . date('ymd');
q('INSERT INTO enquiries
   (reference, product_id, product_name, product_sku, variant, colour, price_shown, page_url,
    first_name, last_name, email, phone, province, city, postal_code, preferred_contact, message,
    consent, utm_source, utm_medium, utm_campaign, referrer, ip_hash, status, email_delivery_status, ack_delivery_status, created_at)
   VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,"New","pending","pending", NOW())',
  [$ref, $productId ?: null, $productName, $productSku, $variant ?: null, $colour ?: null, $priceShown,
   $pageUrl ?: null, $firstName, $lastName ?: null, $email, $phone, $province, $city, $postal ?: null,
   $pref, $message ?: null, $consent ? 1 : 0, $utmS ?: null, $utmM ?: null, $utmC ?: null,
   $referrer ?: null, hash_ip(client_ip())]);
$enquiryId = (int) db()->lastInsertId();
rate_hit('enquiry');

/* ---- Emails (never fatal) ---- */
$enq = row('SELECT * FROM enquiries WHERE id = ?', [$enquiryId]);
try {
    $results = send_enquiry_emails($enq);
    q('UPDATE enquiries SET email_delivery_status = ?, ack_delivery_status = ? WHERE id = ?', [
        $results['notify']['ok'] ? 'sent' : ($results['notify']['transport'] === 'none' ? 'pending' : 'failed'),
        $results['ack']['ok'] ? 'sent' : ($results['ack']['transport'] === 'none' ? 'disabled' : 'failed'),
        $enquiryId,
    ]);
} catch (Throwable $t) {
    error_log('Enquiry mail error: ' . $t->getMessage());
    q('UPDATE enquiries SET email_delivery_status = "failed" WHERE id = ?', [$enquiryId]);
}

json_response([
    'ok' => true,
    'reference' => $ref,
    'product_name' => $productName,
    'product_thumb' => $thumb,
]);
