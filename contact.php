<?php
/** Contact page */
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/seo.php';

seo_set(['page_key' => 'contact', 'canonical' => site_url('contact')]);
$crumbs = [['label' => 'Home', 'url' => site_url()], ['label' => 'Contact', 'url' => null]];
require __DIR__ . '/includes/header.php';
$phone = setting('store_phone', '1-855-907-4211');
?>

<div class="page-hero">
    <div class="container">
        <?= render_breadcrumbs($crumbs) ?>
        <h1 style="margin-top:14px;">Contact the GIO team</h1>
        <p class="lead">Questions about a model, an order or parts? We accept phone enquiries during business hours (<?= e(setting('store_hours', 'Monday – Friday, 10am – 4pm Pacific')) ?>).</p>
    </div>
</div>

<div class="container contact-grid">
    <div class="contact-card">
        <div class="contact-line">
            <span class="need-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg></span>
            <div><b>Phone</b><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $phone)) ?>"><?= e($phone) ?></a><br><span>Press 2 for technical support</span></div>
        </div>
        <div class="contact-line">
            <span class="need-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M4 6h16v12H4z" stroke="currentColor" stroke-width="1.6"/><path d="m4 7 8 6 8-6" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg></span>
            <div><b>Email</b><a href="mailto:<?= e(setting('store_email')) ?>"><?= e(setting('store_email')) ?></a></div>
        </div>
        <div class="contact-line">
            <span class="need-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 21s7-6 7-11a7 7 0 1 0-14 0c0 5 7 11 7 11z" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="1.6"/></svg></span>
            <div><b>Address</b><span><?= e(setting('store_address', 'Unit 1 - 11400 Twigg Place, Richmond, BC')) ?></span></div>
        </div>
        <div class="contact-line">
            <span class="need-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/><path d="M12 7v5l3 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></span>
            <div><b>Hours</b><span><?= e(setting('store_hours', 'Monday – Friday, 10am – 4pm Pacific')) ?></span></div>
        </div>
    </div>

    <div>
        <h2 style="font-size:1.5rem;">Send us a message</h2>
        <p style="color:var(--grey);">The form opens with your product attached automatically when you use Buy Now on a product page — or send a general question here.</p>
        <button type="button" class="btn btn-primary btn-lg" data-enquire-general>Open Enquiry Form</button>
        <p style="color:var(--grey);font-size:.85rem;margin-top:16px;">Prefer to browse first? <a href="<?= e(site_url('shop')) ?>" style="color:var(--brand);font-weight:600;">Shop all models</a> and use Buy Now on any product.</p>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
