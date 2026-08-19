<?php
/** 404 page */
declare(strict_types=1);
http_response_code(404);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/seo.php';

seo_set(['title' => 'Page Not Found | ' . SITE_NAME, 'robots' => 'noindex,follow']);
require __DIR__ . '/includes/header.php';
?>

<div class="container error-page">
    <div>
        <div class="error-code">404</div>
        <h1>This route doesn't go anywhere.</h1>
        <p>The page you're looking for may have moved or never existed. Let's get you back on the road.</p>
        <div class="hero-ctas" style="justify-content:center;">
            <a class="btn btn-primary" href="<?= e(site_url('shop')) ?>">Shop Mobility</a>
            <a class="btn btn-outline" href="<?= e(site_url()) ?>">Back Home</a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
