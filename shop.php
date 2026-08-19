<?php
/** Shop / collection page with filters, sorting, pagination. */
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/seo.php';
require_once __DIR__ . '/includes/schema.php';

$badge = $_GET['badge'] ?? '';
$opts = [
    'wheel'      => $_GET['wheel'] ?? '',
    'stock'      => $_GET['stock'] ?? '',
    'min_price'  => $_GET['min_price'] ?? '',
    'max_price'  => $_GET['max_price'] ?? '',
    'min_range'  => $_GET['min_range'] ?? '',
    'min_capacity' => $_GET['min_capacity'] ?? '',
    'sort'       => $_GET['sort'] ?? 'featured',
    'page'       => (int)($_GET['page'] ?? 1),
    'limit'      => 12,
];
if ($badge === 'best') $opts['best_seller'] = true;
if ($badge === 'new')  $opts['new_arrival'] = true;

$products = list_products($opts);
$total    = count_products($opts);
$pages    = max(1, (int) ceil($total / $opts['limit']));

$pageTitle = $badge === 'best' ? 'Best Sellers' : ($badge === 'new' ? 'New Arrivals' : 'Shop All Mobility Products');
$pageKey   = $badge === 'best' ? null : ($badge === 'new' ? null : 'shop');

seo_set([
    'title' => $pageTitle . ' | ' . SITE_NAME,
    'page_key' => $pageKey,
    'canonical' => site_url($badge === 'best' ? 'best-sellers' : ($badge === 'new' ? 'new-arrivals' : 'shop')),
]);

function shop_query(array $overrides = []): string
{
    $q = array_merge($_GET, $overrides);
    foreach ($q as $k => $v) { if ($v === '' || $v === null) unset($q[$k]); }
    return '?' . http_build_query($q);
}

$crumbs = [['label' => 'Home', 'url' => site_url()], ['label' => $pageTitle, 'url' => null]];
$GLOBALS['schemas'][] = schema_breadcrumbs($crumbs);
require __DIR__ . '/includes/header.php';

$sorts = ['featured' => 'Featured', 'best' => 'Best Selling', 'newest' => 'Newest',
          'price_asc' => 'Price: Low to High', 'price_desc' => 'Price: High to Low', 'alpha' => 'Alphabetical'];
$wheels = ['3-wheel' => '3-Wheel', '4-wheel' => '4-Wheel', 'enclosed' => 'Enclosed', 'walker' => 'Walker'];
$stocks = ['in_stock' => 'In Stock', 'pre_order' => 'Pre-Order', 'limited' => 'Limited'];
?>

<div class="container">
    <?= render_breadcrumbs($crumbs) ?>
    <div class="page-hero-inner" style="padding:20px 0 6px;">
        <h1 style="font-size:clamp(1.9rem,4vw,2.8rem);"><?= e($pageTitle) ?></h1>
        <p class="lead" style="color:var(--grey);margin:0;">
            <?= $badge === 'best' ? 'The models Canadians keep choosing.'
              : ($badge === 'new' ? 'The latest additions to the GIO lineup.'
              : 'Every GIO model, in one place — filter by what matters to you.') ?>
        </p>
    </div>

    <div class="shop-layout">
        <!-- Filters -->
        <aside class="filter-panel" id="filterPanel" aria-label="Product filters">
            <form method="get" action="<?= e(site_url($badge === 'best' ? 'best-sellers' : ($badge === 'new' ? 'new-arrivals' : 'shop'))) ?>">
                <?php if ($badge): ?><input type="hidden" name="badge" value="<?= e($badge) ?>"><?php endif; ?>
                <div class="filter-group">
                    <h4>Wheel Configuration</h4>
                    <?php foreach ($wheels as $val => $label): ?>
                    <label class="filter-check"><input type="radio" name="wheel" value="<?= e($val) ?>" <?= $opts['wheel'] === $val ? 'checked' : '' ?>> <?= e($label) ?></label>
                    <?php endforeach; ?>
                    <label class="filter-check"><input type="radio" name="wheel" value="" <?= $opts['wheel'] === '' ? 'checked' : '' ?>> Any</label>
                </div>
                <div class="filter-group">
                    <h4>Availability</h4>
                    <?php foreach ($stocks as $val => $label): ?>
                    <label class="filter-check"><input type="radio" name="stock" value="<?= e($val) ?>" <?= $opts['stock'] === $val ? 'checked' : '' ?>> <?= e($label) ?></label>
                    <?php endforeach; ?>
                    <label class="filter-check"><input type="radio" name="stock" value="any" <?= $opts['stock'] === '' || $opts['stock'] === 'any' ? 'checked' : '' ?>> Any</label>
                </div>
                <div class="filter-group">
                    <h4>Price (CAD)</h4>
                    <div style="display:flex;gap:8px;">
                        <input type="number" name="min_price" placeholder="Min" min="0" value="<?= e((string)$opts['min_price']) ?>" style="width:50%;padding:.55em .8em;border:1.5px solid var(--border);border-radius:10px;font:inherit;font-size:.88rem;">
                        <input type="number" name="max_price" placeholder="Max" min="0" value="<?= e((string)$opts['max_price']) ?>" style="width:50%;padding:.55em .8em;border:1.5px solid var(--border);border-radius:10px;font:inherit;font-size:.88rem;">
                    </div>
                </div>
                <div class="filter-group">
                    <h4>Minimum Range</h4>
                    <?php foreach (['' => 'Any', '30' => '30 km+', '50' => '50 km+', '70' => '70 km+'] as $val => $label): ?>
                    <label class="filter-check"><input type="radio" name="min_range" value="<?= e($val) ?>" <?= (string)$opts['min_range'] === (string)$val ? 'checked' : '' ?>> <?= e($label) ?></label>
                    <?php endforeach; ?>
                </div>
                <div class="filter-group">
                    <h4>Minimum Capacity</h4>
                    <?php foreach (['' => 'Any', '140' => '140 kg+', '200' => '200 kg+'] as $val => $label): ?>
                    <label class="filter-check"><input type="radio" name="min_capacity" value="<?= e($val) ?>" <?= (string)$opts['min_capacity'] === (string)$val ? 'checked' : '' ?>> <?= e($label) ?></label>
                    <?php endforeach; ?>
                </div>
                <button type="submit" class="btn btn-primary filter-apply">Apply Filters</button>
                <a href="<?= e(site_url($badge === 'best' ? 'best-sellers' : ($badge === 'new' ? 'new-arrivals' : 'shop'))) ?>" class="btn btn-ghost filter-apply" style="text-align:center;">Clear all</a>
            </form>
        </aside>

        <!-- Results -->
        <div>
            <div class="shop-toolbar">
                <button class="btn btn-outline btn-sm filter-toggle-btn" id="filterToggle" type="button">Filters</button>
                <span class="shop-count"><?= $total ?> product<?= $total === 1 ? '' : 's' ?></span>
                <div class="shop-sort">
                    <label for="sortSelect">Sort</label>
                    <select id="sortSelect" onchange="location.href=this.value">
                        <?php foreach ($sorts as $val => $label): ?>
                        <option value="<?= e(shop_query(['sort' => $val, 'page' => null])) ?>" <?= $opts['sort'] === $val ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <?php
            $chips = [];
            if ($opts['wheel']) $chips[] = ['Wheel: ' . $wheels[$opts['wheel']], shop_query(['wheel' => null, 'page' => null])];
            if ($opts['stock'] && $opts['stock'] !== 'any') $chips[] = ['Availability: ' . ($stocks[$opts['stock']] ?? $opts['stock']), shop_query(['stock' => null, 'page' => null])];
            if ($opts['min_price']) $chips[] = ['From $' . (int)$opts['min_price'], shop_query(['min_price' => null, 'page' => null])];
            if ($opts['max_price']) $chips[] = ['Up to $' . (int)$opts['max_price'], shop_query(['max_price' => null, 'page' => null])];
            if ($opts['min_range']) $chips[] = ['Range ' . (int)$opts['min_range'] . ' km+', shop_query(['min_range' => null, 'page' => null])];
            if ($opts['min_capacity']) $chips[] = ['Capacity ' . (int)$opts['min_capacity'] . ' kg+', shop_query(['min_capacity' => null, 'page' => null])];
            if ($chips): ?>
            <div class="active-chips">
                <?php foreach ($chips as [$label, $url]): ?>
                <span class="chip"><?= e($label) ?> <a href="<?= e($url) ?>" aria-label="Remove filter">&times;</a></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!$products): ?>
            <div class="empty-state">
                <h3>No products found</h3>
                <p>Try removing a filter or two — or browse the full lineup.</p>
                <a class="btn btn-dark" href="<?= e(site_url('shop')) ?>">View all products</a>
            </div>
            <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $p): $card = $p; require GIO_INCLUDES . '/product-card.php'; endforeach; ?>
            </div>

            <?php if ($pages > 1): ?>
            <nav class="pagination" aria-label="Pagination">
                <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <?php if ($i === $opts['page']): ?>
                    <span class="current" aria-current="page"><?= $i ?></span>
                    <?php else: ?>
                    <a href="<?= e(shop_query(['page' => $i])) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
