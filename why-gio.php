<?php
/** Why GIO page */
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/seo.php';

seo_set(['page_key' => 'why-gio', 'canonical' => site_url('why-gio')]);
$crumbs = [['label' => 'Home', 'url' => site_url()], ['label' => 'Why GIO', 'url' => null]];
require __DIR__ . '/includes/header.php';
?>

<div class="page-hero">
    <div class="container">
        <?= render_breadcrumbs($crumbs) ?>
        <h1 style="margin-top:14px;">Why Canadians choose GIO.</h1>
        <p class="lead">Design-led scooters, direct pricing and a support team that actually knows the machines.</p>
    </div>
</div>

<div class="container" style="padding:clamp(40px,5vw,70px) 0 90px;">
    <div class="why-grid">
        <div class="why-card">
            <span class="need-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M12 3v18M5 8l7-5 7 5M7 21h10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            <h3>Canadian-Designed Quality</h3>
            <p>Based in Vancouver, each GIO scooter is designed with care and refined with feedback directly from customers.</p>
        </div>
        <div class="why-card">
            <span class="need-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M12 2v20M17 7H9.5a2.5 2.5 0 0 0 0 5h5a2.5 2.5 0 0 1 0 5H7" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></span>
            <h3>Dedicated to Affordability</h3>
            <p>Don't overpay for similar scooters at higher prices. Shop direct with GIO for the best prices.</p>
        </div>
        <div class="why-card">
            <span class="need-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M4 5h16v11H9l-5 4V5z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg></span>
            <h3>Experienced Support Team</h3>
            <p>No scripted conversations or foreign call centres — our US/Canada-based team has hands-on experience with every product.</p>
        </div>
        <div class="why-card">
            <span class="need-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M5 17h14M7 17V9h7l3 4v4M9 9V6h5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            <h3>90–95% Assembled on Arrival</h3>
            <p>Remove your scooter from its crate, finish simple final steps like mirror installation, give it a first charge — and ride.</p>
        </div>
        <div class="why-card">
            <span class="need-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M12 3l7 3v5c0 5-3.5 8.5-7 10-3.5-1.5-7-5-7-10V6l7-3z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg></span>
            <h3>12-Month Parts Warranty</h3>
            <p>Coverage against part failures, defects and manufacturing errors, with replacement parts shipped free of charge.</p>
        </div>
        <div class="why-card">
            <span class="need-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M12 3a9 9 0 1 0 9 9M12 7v5l3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></span>
            <h3>Service Made Easy</h3>
            <p>Technical advice by phone, a full parts department, and warranty support that starts from the day your scooter arrives.</p>
        </div>
    </div>

    <div class="section-dark" style="border-radius:var(--radius);padding:clamp(36px,5vw,60px);margin-top:44px;">
        <h2 style="margin-bottom:12px;">Half the cost. Double the features.</h2>
        <p style="color:rgba(255,255,255,.75);max-width:64ch;">Traditional medical scooters can cost $4,000+ while looking awkward and uninspiring. GIO scooters are durable, loaded with bigger storage and bigger wheels — and look great doing it. In most places, they don't require a licence, insurance or registration (please verify your local requirements).</p>
        <a class="btn btn-primary" href="<?= e(site_url('shop')) ?>">Explore the Lineup</a>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
