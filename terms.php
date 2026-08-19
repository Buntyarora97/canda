<?php
/** Terms of service */
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/seo.php';

seo_set(['title' => 'Terms of Service | ' . SITE_NAME, 'canonical' => site_url('terms')]);
$crumbs = [['label' => 'Home', 'url' => site_url()], ['label' => 'Terms of Service', 'url' => null]];
require __DIR__ . '/includes/header.php';
?>

<div class="container content-narrow">
    <?= render_breadcrumbs($crumbs) ?>
    <h1 style="margin-top:20px;">Terms of Service</h1>

    <h2>Enquiries, not online orders</h2>
    <p>This website collects product enquiries. Submitting an enquiry — including via the Buy Now button — does not create an order, a contract of sale, or any payment obligation. No payment is taken on this website. A member of our team will contact you to confirm availability, final pricing and delivery before any sale is completed.</p>

    <h2>Product information</h2>
    <p>We work to keep specifications, pricing and availability accurate, but they may change without notice and are confirmed at the time of purchase. Range, speed and similar performance figures are estimates under favourable conditions; real-world results vary with load, terrain, temperature and use.</p>

    <h2>Use of products</h2>
    <p>Requirements for operating mobility devices vary by province and municipality. You are responsible for verifying and complying with your local regulations. Always operate your scooter in accordance with the owner's manual.</p>

    <h2>Intellectual property</h2>
    <p>All content on this site — text, imagery, logos and design — is the property of <?= e(SITE_NAME) ?> or its licensors and may not be reproduced without permission.</p>

    <h2>Contact</h2>
    <p>Questions about these terms? Email <a href="mailto:<?= e(setting('store_email')) ?>" style="color:var(--brand);"><?= e(setting('store_email')) ?></a> or call <?= e(setting('store_phone', '1-855-907-4211')) ?>.</p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
