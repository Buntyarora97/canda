<?php
/** FAQ page — admin-managed, grouped by category. */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/seo.php';
require_once dirname(__DIR__) . '/includes/schema.php';

$faqs = rows('SELECT * FROM faqs WHERE is_published = 1 ORDER BY category, sort_order, id');
$grouped = [];
foreach ($faqs as $f) $grouped[$f['category']][] = $f;

seo_set(['title' => 'Frequently Asked Questions | ' . SITE_NAME, 'canonical' => site_url('support/faqs')]);
if ($faqs) $GLOBALS['schemas'][] = schema_faq($faqs);
$crumbs = [['label' => 'Home', 'url' => site_url()], ['label' => 'Support', 'url' => site_url('support')], ['label' => 'FAQs', 'url' => null]];
require dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-hero">
    <div class="container">
        <?= render_breadcrumbs($crumbs) ?>
        <h1 style="margin-top:14px;">Frequently Asked Questions</h1>
        <p class="lead">Quick answers on ordering, shipping, warranty and ownership.</p>
    </div>
</div>

<div class="container content-narrow">
    <?php foreach ($grouped as $cat => $items): ?>
    <h2 class="faq-group-title"><?= e($cat) ?></h2>
    <?php foreach ($items as $f): ?>
    <div class="accordion">
        <button class="accordion-head" aria-expanded="false"><?= e($f['question']) ?>
            <svg class="acc-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </button>
        <div class="accordion-body"><div class="accordion-body-inner"><?= nl2br(e($f['answer'])) ?></div></div>
    </div>
    <?php endforeach; ?>
    <?php endforeach; ?>

    <div style="margin-top:40px;padding:26px;background:var(--offwhite);border-radius:var(--radius);">
        <h3 class="mt-0">Still have a question?</h3>
        <p>Call <?= e(setting('store_phone', '1-855-907-4211')) ?> or send us a message — we're happy to help.</p>
        <button type="button" class="btn btn-primary" data-enquire-general>Contact Support</button>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
