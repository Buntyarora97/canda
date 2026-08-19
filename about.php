<?php
/** About GIO */
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/seo.php';

seo_set(['page_key' => 'about', 'canonical' => site_url('about')]);
$crumbs = [['label' => 'Home', 'url' => site_url()], ['label' => 'About GIO', 'url' => null]];
require __DIR__ . '/includes/header.php';
?>

<div class="page-hero">
    <div class="container">
        <?= render_breadcrumbs($crumbs) ?>
        <h1 style="margin-top:14px;">Mobility, made beautifully Canadian.</h1>
        <p class="lead">Proudly providing stylish, dependable electric scooters to enable people to regain mobility and the freedom of independence — for over a decade.</p>
    </div>
</div>

<div class="container content-narrow">
    <h2>Who we are</h2>
    <p>GIO Mobility Canada is based in Richmond, British Columbia. We design electric mobility scooters with care — and refine every model with feedback directly from the people who ride them. Our goal has never changed: mobility that feels like freedom, not a medical device.</p>

    <h2>Our philosophy</h2>
    <p>Too many mobility products look clinical and uninspiring. We believe the machine you ride every day should be something you're proud of — durable, loaded with useful features like bigger storage and bigger wheels, and styled more like a modern vehicle than a piece of hospital equipment.</p>

    <h2>Designed with riders, not just for them</h2>
    <p>Comfort, functionality and everyday usability guide every design decision: adjustable seating, straightforward controls, generous storage, USB charging and lighting that works when you need it. When riders tell us something could be better, it feeds directly into the next refinement.</p>

    <h2>Support that stays with you</h2>
    <p>Our US/Canada-based team knows these scooters hands-on. Every scooter includes a 12-month parts warranty, and our full parts department keeps your GIO on the road for years. Have a question about operation or need technical advice? Call <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', setting('store_phone', '1-855-907-4211'))) ?>" style="color:var(--brand);"><?= e(setting('store_phone', '1-855-907-4211')) ?></a>.</p>

    <div style="margin-top:44px;display:flex;gap:14px;flex-wrap:wrap;">
        <a class="btn btn-primary" href="<?= e(site_url('shop')) ?>">Shop the Lineup</a>
        <a class="btn btn-outline" href="<?= e(site_url('why-gio')) ?>">Why Choose GIO</a>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
