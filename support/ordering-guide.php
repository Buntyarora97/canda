<?php
/** Ordering guide */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/seo.php';

seo_set(['title' => 'Ordering Guide | ' . SITE_NAME, 'canonical' => site_url('support/ordering-guide')]);
$crumbs = [['label' => 'Home', 'url' => site_url()], ['label' => 'Support', 'url' => site_url('support')], ['label' => 'Ordering Guide', 'url' => null]];
require dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-hero">
    <div class="container">
        <?= render_breadcrumbs($crumbs) ?>
        <h1 style="margin-top:14px;">Ordering Guide</h1>
        <p class="lead">How a GIO scooter gets from our warehouse to your front door — step by step.</p>
    </div>
</div>

<div class="container content-narrow">
    <h2>Step 1 — Before ordering</h2>
    <ul>
        <li>Check each model's product page and browse the digital manual for assembly steps, so the scooter meets your needs.</li>
        <li>Ensure your scooter fits your space — measure doors, elevators and storage areas.</li>
        <li>Make sure its parking spot has a standard 110V outlet nearby for charging.</li>
    </ul>

    <h2>Step 2 — Placing your order</h2>
    <p>On this website, tap <strong>Buy Now</strong> on any product to send an enquiry — your selected model, colour and price are attached automatically. Our team will contact you to confirm availability and complete your order. You can also order by phone at <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', setting('store_phone', '1-855-907-4211'))) ?>" style="color:var(--brand);"><?= e(setting('store_phone', '1-855-907-4211')) ?></a> during business hours.</p>
    <p><em>No payment is taken on this website.</em></p>

    <h2>Step 3 — Processing &amp; shipping</h2>
    <ul>
        <li><strong>In stock</strong> products normally ship within 1–3 business days.</li>
        <li>Most scooters take an estimated 4–10 business days in transit.</li>
        <li>The All-Season Enclosed Scooter has more complex routing — estimated 5–20 business days.</li>
        <li>A local agent will call you to set up a delivery appointment before arrival.</li>
    </ul>

    <h2>Step 4 — Delivery day</h2>
    <ul>
        <li>Your scooter arrives crated; drivers deliver only — they do not unbox, assemble or demonstrate.</li>
        <li>If the crate looks damaged: accept the shipment, photograph the damage, inspect the scooter and contact us. Shipping damage is resolved with warranty replacement parts — refusing a shipment only delays resolution.</li>
    </ul>

    <h2>Step 5 — Your new scooter</h2>
    <ul>
        <li>Unbox and remove the shipping frame (a second person helps for larger models).</li>
        <li>Review the manual's assembly, operation, care and safety sections.</li>
        <li>Finish final assembly (like mirror installation) and give it a full first charge (6–8 hours for most models).</li>
        <li>Ride and enjoy!</li>
    </ul>

    <div style="margin-top:36px;"><a class="btn btn-primary" href="<?= e(site_url('shop')) ?>">Start Exploring</a></div>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
