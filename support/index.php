<?php
/** Support hub */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/seo.php';

seo_set(['page_key' => 'support', 'canonical' => site_url('support')]);
$crumbs = [['label' => 'Home', 'url' => site_url()], ['label' => 'Support', 'url' => null]];
require dirname(__DIR__) . '/includes/header.php';

$pages = [
    ['FAQs', 'Fast answers on ordering, warranty, charging and more.', '/support/faqs', 'M9.1 9a3 3 0 0 1 5.8 1c0 2-3 2.5-3 4.5M12 18.5v.5'],
    ['Ordering Guide', 'From enquiry to delivery, step by step.', '/support/ordering-guide', 'M5 4h14M5 9h14M5 14h9M5 19h6'],
    ['Warranty', 'Your 12-month parts warranty, explained.', '/support/warranty', 'M12 3l7 3v5c0 5-3.5 8.5-7 10-3.5-1.5-7-5-7-10V6l7-3z'],
    ['Shipping', 'Timelines, delivery appointments and what to expect.', '/support/shipping', 'M5 17h14M7 17V9h7l3 4v4M9 9V6h5'],
    ['Product Manuals', 'Digital owner\'s manuals for every model.', '/support/manuals', 'M6 3h9l4 4v14H6V3zm8 0v5h5'],
    ['Contact Support', 'Talk to our Vancouver-based team.', '/contact', 'M4 5h16v11H9l-5 4V5z'],
];
?>

<div class="page-hero">
    <div class="container">
        <?= render_breadcrumbs($crumbs) ?>
        <h1 style="margin-top:14px;">Support that travels with you</h1>
        <p class="lead">Everything you need before and after your purchase — from real people in Richmond, BC.</p>
    </div>
</div>

<div class="container" style="padding:clamp(36px,5vw,60px) 0 90px;">
    <div class="support-grid">
        <?php foreach ($pages as [$t, $d, $u, $icon]): ?>
        <a class="support-card" href="<?= eurl($u) ?>">
            <span class="need-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="<?= e($icon) ?>" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
            <span><h3><?= e($t) ?></h3><p><?= e($d) ?></p></span>
            <svg class="go-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
