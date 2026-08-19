<?php
/** Admin bootstrap: auth, layout, upload helpers. */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/config.php';
require_once GIO_ROOT . '/includes/database.php';
require_once GIO_ROOT . '/includes/security.php';
require_once GIO_ROOT . '/includes/functions.php';
require_once GIO_ROOT . '/includes/mailer.php';

secure_session();

/* ------------------------------------------------------------ auth ------- */
function admin_flash(string $msg, string $type = 'ok'): void
{
    $_SESSION['admin_flash'][] = ['msg' => $msg, 'type' => $type];
}

function admin_take_flashes(): array
{
    $f = $_SESSION['admin_flash'] ?? [];
    unset($_SESSION['admin_flash']);
    return $f;
}

/* ------------------------------------------------------------ uploads ---- */
/**
 * Validate + store an uploaded image. Returns stored filename.
 * Generates a 600px _thumb and a .webp copy where GD supports it.
 * @throws RuntimeException on any validation failure.
 */
function admin_upload_image(string $field, string $bucket, int $maxW = 1920): ?string
{
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload failed (error ' . (int)$f['error'] . ').');
    if ($f['size'] > MAX_UPLOAD_BYTES) throw new RuntimeException('File is too large (max ' . round(MAX_UPLOAD_BYTES / 1048576) . ' MB).');

    $info = @getimagesize($f['tmp_name']);
    if ($info === false) throw new RuntimeException('File is not a valid image.');
    $mime = $info['mime'];
    $exts = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!isset($exts[$mime])) throw new RuntimeException('Only JPG, PNG, WEBP or GIF images are allowed.');
    if (!preg_match('/\.(jpe?g|png|webp|gif)$/i', $f['name'])) throw new RuntimeException('File extension does not match an image type.');

    $name = bin2hex(random_bytes(10)) . '.' . $exts[$mime];
    $dir  = GIO_ROOT . '/uploads/' . $bucket;
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) throw new RuntimeException('Upload directory is not writable.');

    switch ($mime) {
        case 'image/jpeg': $img = @imagecreatefromjpeg($f['tmp_name']); break;
        case 'image/png':  $img = @imagecreatefrompng($f['tmp_name']);  break;
        case 'image/webp': $img = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($f['tmp_name']) : false; break;
        default:           $img = @imagecreatefromgif($f['tmp_name']);  break;
    }
    if (!$img) {
        if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) throw new RuntimeException('Could not store the upload.');
        return $name;
    }
    $img = admin_normalize_image($img, $maxW);
    imagejpeg($img, $dir . '/' . $name, 86);

    // 600px thumbnail
    $w = imagesx($img); $h = imagesy($img);
    $scale = min(1, 600 / max($w, $h));
    $tw = max(1, (int)round($w * $scale)); $th = max(1, (int)round($h * $scale));
    $thumb = imagecreatetruecolor($tw, $th);
    imagecopyresampled($thumb, $img, 0, 0, 0, 0, $tw, $th, $w, $h);
    $pi = pathinfo($name);
    imagejpeg($thumb, $dir . '/' . $pi['filename'] . '_thumb.jpg', 82);
    if (function_exists('imagewebp')) {
        @imagewebp($img, $dir . '/' . $pi['filename'] . '.webp', 84);
        @imagewebp($thumb, $dir . '/' . $pi['filename'] . '_thumb.webp', 80);
    }
    imagedestroy($thumb);
    imagedestroy($img);
    return $name;
}

function admin_normalize_image(GdImage $img, int $maxW): GdImage
{
    $w = imagesx($img); $h = imagesy($img);
    if (max($w, $h) <= $maxW) return $img;
    $scale = $maxW / max($w, $h);
    $nw = max(1, (int)round($w * $scale)); $nh = max(1, (int)round($h * $scale));
    $out = imagecreatetruecolor($nw, $nh);
    imagealphablending($out, false);
    imagesavealpha($out, true);
    imagecopyresampled($out, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
    imagedestroy($img);
    return $out;
}

/** Validate + store a PDF upload. @throws RuntimeException */
function admin_upload_pdf(string $field, string $bucket): ?string
{
    if (empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Upload failed (error ' . (int)$f['error'] . ').');
    if ($f['size'] > MAX_UPLOAD_BYTES * 2) throw new RuntimeException('PDF is too large.');
    $fi = new finfo(FILEINFO_MIME_TYPE);
    if ($fi->file($f['tmp_name']) !== 'application/pdf') throw new RuntimeException('Only PDF files are allowed.');
    if (!preg_match('/\.pdf$/i', $f['name'])) throw new RuntimeException('File must have a .pdf extension.');
    $name = bin2hex(random_bytes(10)) . '.pdf';
    $dir  = GIO_ROOT . '/uploads/' . $bucket;
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) throw new RuntimeException('Upload directory is not writable.');
    if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) throw new RuntimeException('Could not store the upload.');
    return $name;
}

function admin_delete_upload(?string $file, string $bucket): void
{
    if (!$file) return;
    $pi = pathinfo($file);
    $base = $pi['filename'];
    foreach ([$file, $base . '_thumb.jpg', $base . '.webp', $base . '_thumb.webp'] as $f) {
        $p = GIO_ROOT . '/uploads/' . $bucket . '/' . basename($f);
        if (is_file($p)) @unlink($p);
    }
}

/* ------------------------------------------------------------ layout ----- */
function admin_nav(): array
{
    return [
        ['index.php',      'Dashboard',  'M3 12l9-8 9 8M5 10v10h14V10'],
        ['enquiries.php',  'Enquiries',  'M4 6h16v12H4zM4 7l8 6 8-6'],
        ['products.php',   'Products',   'M4 7l8-4 8 4v10l-8 4-8-4zM4 7l8 4m0 0l8-4m-8 4v10'],
        ['categories.php', 'Categories', 'M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z'],
        ['banners.php',    'Banners',    'M3 5h18v12H3zM3 15l5-5 4 4 3-3 6 6'],
        ['faqs.php',       'FAQs',       'M12 18v-1m0-4a2.5 2.5 0 1 1 2.4-3.2c.4 1.2-.4 2.2-2.4 3.2M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18z'],
        ['posts.php',      'Blog',       'M5 4h14v16H5zM8 8h8M8 12h8M8 16h5'],
        ['manuals.php',    'Manuals',    'M6 3h9l4 4v14H6zM15 3v4h4M9 12h6M9 16h6'],
        ['reviews.php',    'Reviews',    'M12 3l2.7 5.6 6.1.8-4.5 4.2 1.1 6L12 16.8 6.6 19.6l1.1-6L3.2 9.4l6.1-.8z'],
        ['settings.php',   'Settings',   'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm7-3a7 7 0 0 0-.2-1.5l2-1.6-2-3.4-2.4 1a7 7 0 0 0-2.6-1.5L13.4 2h-2.8l-.4 2.5a7 7 0 0 0-2.6 1.5l-2.4-1-2 3.4 2 1.6a7 7 0 0 0 0 3l-2 1.6 2 3.4 2.4-1a7 7 0 0 0 2.6 1.5l.4 2.5h2.8l.4-2.5a7 7 0 0 0 2.6-1.5l2.4 1 2-3.4-2-1.6c.1-.5.2-1 .2-1.5z'],
        ['activity.php',   'Activity',   'M4 12h4l2-7 4 14 2-7h4'],
        ['profile.php',    'My Account', 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zm-8 8a8 8 0 0 1 16 0'],
    ];
}

function admin_head(string $title, string $active = ''): void
{
    $admin = current_admin();
    $newEnquiries = (int) val("SELECT COUNT(*) FROM enquiries WHERE status = 'New'");
    ?>
<!DOCTYPE html>
<html lang="en-CA">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> · GIO Mobility Admin</title>
<link rel="icon" href="<?= e(site_url('assets/images/favicon.png')) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(site_url('admin/assets/admin.css')) ?>">
</head>
<body class="admin-body">
<div class="admin-shell">
    <aside class="admin-side" id="adminSide">
        <a class="admin-brand" href="<?= e(site_url('admin/index.php')) ?>">
            <img src="<?= e(site_url('assets/images/gio-logo-light.png')) ?>" alt="GIO Mobility" width="92" height="28">
            <span>Admin</span>
        </a>
        <nav class="admin-nav">
            <?php foreach (admin_nav() as [$href, $label, $icon]): ?>
            <a href="<?= e(site_url('admin/' . $href)) ?>" class="<?= $active === $href ? 'active' : '' ?>">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="<?= e($icon) ?>"/></svg>
                <?= e($label) ?>
                <?php if ($href === 'enquiries.php' && $newEnquiries > 0): ?><span class="nav-badge"><?= $newEnquiries ?></span><?php endif; ?>
            </a>
            <?php endforeach; ?>
        </nav>
        <div class="admin-side-foot">
            <a href="<?= e(site_url()) ?>" target="_blank" rel="noopener">View storefront ↗</a>
            <a href="<?= e(site_url('admin/logout.php')) ?>">Sign out</a>
        </div>
    </aside>
    <div class="admin-main">
        <header class="admin-top">
            <button class="admin-burger" id="adminBurger" aria-label="Menu"><span></span><span></span><span></span></button>
            <h1><?= e($title) ?></h1>
            <div class="admin-user"><?= e($admin['name'] ?? 'Admin') ?><small><?= e($admin['email'] ?? '') ?></small></div>
        </header>
        <div class="admin-content">
        <?php foreach (admin_take_flashes() as $f): ?>
            <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
        <?php endforeach; ?>
    <?php
}

function admin_foot(): void
{
    ?>
        </div>
    </div>
</div>
<script src="<?= e(site_url('admin/assets/admin.js')) ?>" defer></script>
</body>
</html>
    <?php
}

/** CSRF guard for admin POST actions; dies on failure. */
function admin_guard(): void
{
    if (!csrf_verify($_POST['csrf'] ?? null)) {
        http_response_code(419);
        exit('Session expired. Please go back and try again.');
    }
}

require_admin();
