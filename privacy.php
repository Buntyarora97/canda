<?php
/** Privacy policy */
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/seo.php';

seo_set(['title' => 'Privacy Policy | ' . SITE_NAME, 'canonical' => site_url('privacy')]);
$crumbs = [['label' => 'Home', 'url' => site_url()], ['label' => 'Privacy Policy', 'url' => null]];
require __DIR__ . '/includes/header.php';
?>

<div class="container content-narrow">
    <?= render_breadcrumbs($crumbs) ?>
    <h1 style="margin-top:20px;">Privacy Policy</h1>
    <p style="color:var(--grey);font-size:.85rem;">Last updated: <?= e(setting('privacy_updated', date('F j, Y'))) ?></p>

    <h2>What we collect</h2>
    <p>When you send a product enquiry, we collect the contact details you provide — name, email, phone number, province, city, postal code, and your message — along with the product, variant and page you enquired about. We do not collect medical or health information, and we ask you not to include any in your messages.</p>

    <h2>How we use it</h2>
    <ul>
        <li>To respond to your enquiry and help you choose and purchase a product.</li>
        <li>To provide warranty, support and delivery coordination after a purchase.</li>
        <li>To send service communications about your enquiry or order.</li>
    </ul>

    <h2>What we don't do</h2>
    <ul>
        <li>We don't sell your personal information.</li>
        <li>We don't take payment on this website, so no card data is ever collected here.</li>
        <li>We don't use your enquiry details for unrelated marketing without your consent.</li>
    </ul>

    <h2>Cookies &amp; storage</h2>
    <p>We use essential cookies for site security (form protection) and your browser's local storage for convenience features like your wishlist and comparison list. These never leave your device.</p>

    <h2>Data retention</h2>
    <p>Enquiry records are retained for up to <?= e(setting('data_retention_months', '24')) ?> months to support follow-up and service, then removed or anonymized.</p>

    <h2>Your rights</h2>
    <p>You may request a copy, correction or deletion of your personal information at any time by emailing <a href="mailto:<?= e(setting('store_email')) ?>" style="color:var(--brand);"><?= e(setting('store_email')) ?></a> or calling <?= e(setting('store_phone', '1-855-907-4211')) ?>.</p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
