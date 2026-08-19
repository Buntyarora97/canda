<?php
/** Category page */
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/seo.php';
require_once __DIR__ . '/includes/schema.php';

$slug = strtolower(trim((string)($_GET['slug'] ?? '')));
$cat  = $slug ? get_category_by_slug($slug) : null;
if (!$cat) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$opts = ['category_id' => (int)$cat['id'], 'sort' => $_GET['sort'] ?? 'featured', 'page' => (int)($_GET['page'] ?? 1), 'limit' => 12];
$products = list_products($opts);
$total = count_products(['category_id' => (int)$cat['id']]);
$pages = max(1, (int)ceil($total / $opts['limit']));

seo_set([
    'title' => $cat['name'] . ' | ' . SITE_NAME,
    'description' => $cat['description'] ?: 'Shop ' . $cat['name'] . ' at ' . SITE_NAME . '.',
    'canonical' => category_url($cat),
]);
$crumbs = [['label' => 'Home', 'url' => site_url()], ['label' => 'Shop', 'url' => site_url('shop')], ['label' => $cat['name'], 'url' => null]];
$GLOBALS['schemas'][] = schema_breadcrumbs($crumbs);
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <?= render_breadcrumbs($crumbs) ?>
    <div class="page-hero-inner" style="padding:20px 0 10px;">
        <h1 style="font-size:clamp(1.9rem,4vw,2.8rem);"><?= e($cat['name']) ?></h1>
        <?php if ($cat['description']): ?><p class="lead" style="color:var(--grey);max-width:64ch;margin:0;"><?= e($cat['description']) ?></p><?php endif; ?>
    </div>

    <div style="padding:20px 0 90px;">
        <div class="shop-toolbar">
            <span class="shop-count"><?= $total ?> product<?= $total === 1 ? '' : 's' ?></span>
            <div class="shop-sort">
                <label for="sortSelect">Sort</label>
                <select id="sortSelect" onchange="location.href=this.value">
                    <?php foreach (['featured' => 'Featured', 'best' => 'Best Selling', 'newest' => 'Newest', 'price_asc' => 'Price: Low to High', 'price_desc' => 'Price: High to Low', 'alpha' => 'Alphabetical'] as $val => $label): ?>
                    <option value="?sort=<?= e($val) ?>" <?= $opts['sort'] === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <?php if (!$products): ?>
        <div class="empty-state">
            <h3>No products in this category yet</h3>
            <p>New models are added regularly — or browse the full lineup in the meantime.</p>
            <a class="btn btn-dark" href="<?= e(site_url('shop')) ?>">View all products</a>
        </div>
        <?php else: ?>
        <div class="product-grid">
            <?php foreach ($products as $p): $card = $p; require GIO_INCLUDES . '/product-card.php'; endforeach; ?>
        </div>
        <?php if ($pages > 1): ?>
        <nav class="pagination" aria-label="Pagination">
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <?php if ($i === $opts['page']): ?><span class="current"><?= $i ?></span>
                <?php else: ?><a href="?sort=<?= e($opts['sort']) ?>&page=<?= $i ?>"><?= $i ?></a><?php endif; ?>
            <?php endfor; ?>
        </nav>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
