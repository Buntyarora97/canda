<?php
/** Admin: product list with search, filters, publish toggles, delete. */
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_guard();
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    $p = $id ? row('SELECT * FROM products WHERE id = ?', [$id]) : null;
    if ($p && $action === 'toggle_publish') {
        q('UPDATE products SET is_published = 1 - is_published WHERE id = ?', [$id]);
        log_activity('product_toggle', ($p['is_published'] ? 'Unpublished' : 'Published') . ' product #' . $id);
        admin_flash('Product visibility updated.');
    } elseif ($p && $action === 'delete') {
        foreach (product_images($id) as $img) admin_delete_upload($img['file'], 'products');
        q('DELETE FROM products WHERE id = ?', [$id]);
        log_activity('product_delete', 'Deleted product ' . $p['name']);
        admin_flash('Product deleted.');
    }
    redirect(site_url('admin/products.php'));
}

$q_ = trim((string)($_GET['q'] ?? ''));
$where = '1=1'; $args = [];
if ($q_ !== '') { $where .= ' AND (p.name LIKE ? OR p.sku LIKE ?)'; $args = ["%$q_%", "%$q_%"]; }
$products = rows("SELECT p.*,
        (SELECT file FROM product_images WHERE product_id = p.id ORDER BY is_featured DESC, sort_order LIMIT 1) img,
        (SELECT COUNT(*) FROM enquiries e WHERE e.product_id = p.id) enquiry_count
    FROM products p WHERE $where ORDER BY p.sort_order, p.id", $args);

admin_head('Products', 'products.php');
?>
<div class="panel-head">
    <form method="get" class="inline-search"><input type="search" name="q" value="<?= e($q_) ?>" placeholder="Search name or SKU…"><button class="btn btn-sm">Search</button></form>
    <a class="btn btn-primary" href="<?= e(site_url('admin/product-edit.php')) ?>">+ Add product</a>
</div>
<section class="panel">
<table class="tbl">
    <thead><tr><th></th><th>Product</th><th>Price</th><th>Stock</th><th>Flags</th><th>Enquiries</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($products as $p): ?>
        <tr>
            <td class="cell-img"><?php if ($p['img']): ?><img src="<?= e(img_thumb_url($p['img'])) ?>" alt="" width="52" height="52"><?php endif; ?></td>
            <td><strong><?= e($p['name']) ?></strong><br><small class="muted">SKU <?= e($p['sku']) ?> · /product/<?= e($p['slug']) ?></small></td>
            <td><?= $p['show_price'] && $p['price'] !== null ? e(cad((float)$p['price'])) : '<span class="muted">On enquiry</span>' ?></td>
            <td><?= e(STOCK_BADGES[$p['stock_status']] ?? $p['stock_status']) ?></td>
            <td class="flags">
                <?= $p['is_best_seller'] ? '<span class="chip">Best</span>' : '' ?>
                <?= $p['is_new_arrival'] ? '<span class="chip">New</span>' : '' ?>
                <?= $p['is_featured'] ? '<span class="chip">Featured</span>' : '' ?>
            </td>
            <td><?= (int)$p['enquiry_count'] ?></td>
            <td><?= $p['is_published'] ? '<span class="status st-converted">Live</span>' : '<span class="status st-closed">Hidden</span>' ?></td>
            <td class="cell-actions">
                <a class="btn btn-sm" href="<?= e(site_url('admin/product-edit.php?id=' . (int)$p['id'])) ?>">Edit</a>
                <a class="btn btn-sm btn-ghost" href="<?= e(product_url($p)) ?>" target="_blank" rel="noopener">View</a>
                <form method="post" class="inline" data-confirm="<?= $p['is_published'] ? 'Hide this product from the storefront?' : 'Publish this product?' ?>">
                    <?= csrf_field() ?><input type="hidden" name="action" value="toggle_publish"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button class="btn btn-sm btn-ghost"><?= $p['is_published'] ? 'Hide' : 'Publish' ?></button>
                </form>
                <form method="post" class="inline" data-confirm="Permanently delete <?= e($p['name']) ?>? This removes its images, specs and variants.">
                    <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button class="btn btn-sm btn-danger">Delete</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$products): ?><tr><td colspan="8" class="muted">No products found.</td></tr><?php endif; ?>
    </tbody>
</table>
</section>
<?php admin_foot(); ?>
