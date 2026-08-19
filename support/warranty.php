<?php
/** Warranty page */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/seo.php';

seo_set(['title' => 'Warranty | ' . SITE_NAME, 'canonical' => site_url('support/warranty')]);
$crumbs = [['label' => 'Home', 'url' => site_url()], ['label' => 'Support', 'url' => site_url('support')], ['label' => 'Warranty', 'url' => null]];
require dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-hero">
    <div class="container">
        <?= render_breadcrumbs($crumbs) ?>
        <h1 style="margin-top:14px;">Warranty</h1>
        <p class="lead">Every GIO scooter includes a 12-month parts warranty — from the day your scooter arrives.</p>
    </div>
</div>

<div class="container content-narrow">
    <h2>What's covered</h2>
    <p>Your warranty covers any part defect, failure or manufacturing error. All claims are verified by our technician, and when parts are required they are shipped to you free of charge. No warranty registration is required.</p>

    <h2>What's not covered</h2>
    <ul>
        <li>General wear-and-tear items — tires, brake cables, brake pads and similar.</li>
        <li>Issues related to accidents, incorrect use, improper care or modification.</li>
        <li>Labour costs for installing replacement parts (installation is your responsibility — yourself, a friend, family member, hired technician or repair shop).</li>
        <li>Batteries that were not maintained, stored or cared for per the scooter's instructions.</li>
    </ul>

    <h2>Making a claim</h2>
    <p>Contact technical support at <a href="mailto:<?= e(setting('store_email')) ?>" style="color:var(--brand);"><?= e(setting('store_email')) ?></a> or call <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', setting('store_phone', '1-855-907-4211'))) ?>" style="color:var(--brand);"><?= e(setting('store_phone', '1-855-907-4211')) ?></a> (ext. 2) with your order number and details of the issue. Our technician will troubleshoot with you and guide you to a solution — which may include testing to verify a component, an instructional video, or parts shipped under warranty.</p>

    <h2>Outside of warranty?</h2>
    <p>We're still here to help. Our team will do its best to identify the cause and solution; any required parts can be ordered from our full parts department.</p>

    <h2>Returns</h2>
    <p>You have 30 days to request a return. The product must be in its original packaging, in new condition with no signs of use. Return shipping is the customer's responsibility — or we can arrange it and deduct the cost from your refund.</p>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
