<?php
/** Product manuals library */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/seo.php';

$manuals = rows('SELECT m.*, p.name AS product_name, p.slug AS product_slug FROM manuals m LEFT JOIN products p ON p.id = m.product_id ORDER BY m.published_at DESC, m.title');

seo_set(['title' => 'Product Manuals | ' . SITE_NAME, 'canonical' => site_url('support/manuals')]);
$crumbs = [['label' => 'Home', 'url' => site_url()], ['label' => 'Support', 'url' => site_url('support')], ['label' => 'Product Manuals', 'url' => null]];
require dirname(__DIR__) . '/includes/header.php';
?>

<div class="page-hero">
    <div class="container">
        <?= render_breadcrumbs($crumbs) ?>
        <h1 style="margin-top:14px;">Product Manuals</h1>
        <p class="lead">Digital owner's manuals — assembly, operation, care and safety for every model.</p>
    </div>
</div>

<div class="container content-narrow">
    <?php if (!$manuals): ?>
    <div class="empty-state">
        <h3>Manuals are being added</h3>
        <p>Need your manual right away? Contact us and we'll send it directly.</p>
        <button type="button" class="btn btn-primary" data-enquire-general>Request a Manual</button>
    </div>
    <?php else: ?>
    <?php foreach ($manuals as $m): ?>
    <div class="manual-row">
        <span class="need-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M6 3h9l4 4v14H6V3zm8 0v5h5" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg></span>
        <span>
            <h3><?= e($m['title']) ?></h3>
            <p>
                <?php if ($m['product_slug']): ?><a href="<?= e(site_url('product/' . $m['product_slug'])) ?>" style="color:var(--brand);"><?= e($m['product_name']) ?></a> • <?php endif; ?>
                <?= e(trim(($m['language'] ?? 'English') . ($m['version'] ? ' • v' . $m['version'] : '') . ($m['published_at'] ? ' • ' . date('M Y', strtotime($m['published_at'])) : ''))) ?>
            </p>
        </span>
        <a class="btn btn-outline btn-sm" href="<?= e(img_url($m['file'], 'manuals')) ?>" target="_blank" rel="noopener">Download PDF</a>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require dirname(__DIR__) . '/includes/footer.php'; ?>
