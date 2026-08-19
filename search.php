<?php
/** Search results page */
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/seo.php';

$q = trim((string)($_GET['q'] ?? ''));
$results = [];
$total = 0;
if (mb_strlen($q) >= 2) {
    $results = list_products(['search' => $q, 'limit' => 48]);
    $total = count_products(['search' => $q]);
}

seo_set([
    'title' => ($q ? 'Search: ' . $q : 'Search') . ' | ' . SITE_NAME,
    'robots' => 'noindex,follow',
]);
$crumbs = [['label' => 'Home', 'url' => site_url()], ['label' => 'Search', 'url' => null]];
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <?= render_breadcrumbs($crumbs) ?>
    <div class="page-hero-inner" style="padding:20px 0 10px;">
        <h1 style="font-size:clamp(1.9rem,4vw,2.8rem);"><?= $q ? 'Results for &ldquo;' . e($q) . '&rdquo;' : 'Search' ?></h1>
        <?php if ($q): ?><p class="lead" style="color:var(--grey);margin:0;"><?= $total ?> product<?= $total === 1 ? '' : 's' ?> found</p><?php endif; ?>
    </div>

    <div style="padding:16px 0 90px;">
        <?php if ($q && !$results): ?>
        <div class="empty-state">
            <h3>No matches for &ldquo;<?= e($q) ?>&rdquo;</h3>
            <p>Check the spelling, try a model name like &ldquo;Titan&rdquo; or &ldquo;Tron&rdquo;, or browse the full lineup.</p>
            <a class="btn btn-dark" href="<?= e(site_url('shop')) ?>">Shop all products</a>
        </div>
        <?php elseif ($results): ?>
        <div class="product-grid">
            <?php foreach ($results as $p): $card = $p; require GIO_INCLUDES . '/product-card.php'; endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <h3>What are you looking for?</h3>
            <p>Search by model name, category or feature — for example &ldquo;enclosed&rdquo;, &ldquo;walker&rdquo; or &ldquo;backup camera&rdquo;.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
