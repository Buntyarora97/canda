<?php
/** Shipping page */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/seo.php';

seo_set(['title' => 'Shipping & Delivery | ' . SITE_NAME, 'canonical' => site_url('support/shipping')]);
$crumbs = [['label' => 'Home', 'url' => site_url()], ['label' => 'Support', 'url' => site_url('support')], ['label' => 'Shipping', 'url' => null]];
require dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-hero">
    <div class="container">
        <?= render_breadcrumbs($crumbs) ?>
        <h1 style="margin-top:14px;">Shipping &amp; Delivery</h1>
        <p class="lead">From our Richmond, BC warehouse to your door — here's exactly what to expect.</p>
    </div>
</div>

<div class="container content-narrow">
    <h2>Processing</h2>
    <p>In-stock products normally ship out within 1–3 business days. Out-of-stock models can often be pre-ordered to secure your unit — the estimated arrival window is listed on the product page and is subject to change.</p>

    <h2>Transit times</h2>
    <ul>
        <li>Most scooters: estimated <strong>4–10 business days</strong>.</li>
        <li>All-Season Enclosed Scooter: more complex routing, estimated <strong>5–20 business days</strong>.</li>
        <li>Destinations farther from our Vancouver-area warehouse will tend toward the higher end of the range. All times are estimates only.</li>
    </ul>

    <h2>Delivery appointment</h2>
    <p>You'll receive a call from a local agent to set up a delivery appointment before arrival. Trucks usually have a power tailgate for curbside delivery; smaller models may come by pallet jack close to your front door or garage.</p>

    <h2>What drivers do (and don't do)</h2>
    <p>Drivers deliver your packaged scooter only — they do not unbox, assemble, demonstrate operation or remove packing materials. If you need a hand, we recommend arranging help locally before delivery day.</p>

    <h2>If the crate arrives damaged</h2>
    <p>Accept the shipment and contact us right away. Photograph exterior crate damage, then inspect and document the scooter itself. Shipping damage is resolved with warranty replacement parts — refusing a shipment delays resolution and may add charges.</p>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
