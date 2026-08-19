<?php
/** Admin: customer reviews — publish, feature order, delete. */
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_guard();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'save') {
            $rid = (int)($_POST['id'] ?? 0);
            $name = trim((string)($_POST['customer_name'] ?? ''));
            $text = trim((string)($_POST['review'] ?? ''));
            if ($name === '' || $text === '') throw new RuntimeException('Name and review text are required.');
            $photo = admin_upload_image('photo', 'reviews');
            $data = [
                'customer_name' => $name,
                'rating' => max(1, min(5, (int)($_POST['rating'] ?? 5))),
                'review' => $text,
                'product_id' => ($_POST['product_id'] ?? '') !== '' ? (int)$_POST['product_id'] : null,
                'source' => trim((string)($_POST['source'] ?? '')) ?: null,
                'source_url' => trim((string)($_POST['source_url'] ?? '')) ?: null,
                'is_published' => isset($_POST['is_published']) ? 1 : 0,
                'sort_order' => (int)($_POST['sort_order'] ?? 0),
            ];
            if ($rid) {
                if ($photo) { $old = val('SELECT photo FROM reviews WHERE id = ?', [$rid]); admin_delete_upload($old, 'reviews'); $data['photo'] = $photo; }
                $set = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($data)));
                q("UPDATE reviews SET $set WHERE id = :__id", $data + ['__id' => $rid]);
            } else {
                if ($photo) $data['photo'] = $photo;
                $cols = implode(',', array_keys($data));
                q("INSERT INTO reviews ($cols) VALUES (:" . implode(',:', array_keys($data)) . ")", $data);
            }
            log_activity('review_save', $name);
            admin_flash('Review saved.');
        } elseif ($action === 'toggle') {
            q('UPDATE reviews SET is_published = 1 - is_published WHERE id = ?', [(int)$_POST['id']]);
            admin_flash('Review visibility updated.');
        } elseif ($action === 'delete') {
            $old = val('SELECT photo FROM reviews WHERE id = ?', [(int)$_POST['id']]);
            admin_delete_upload($old, 'reviews');
            q('DELETE FROM reviews WHERE id = ?', [(int)$_POST['id']]);
            admin_flash('Review deleted.');
        }
    } catch (Throwable $t) {
        admin_flash($t->getMessage(), 'err');
    }
    redirect(site_url('admin/reviews.php'));
}

$edit = isset($_GET['edit']) ? row('SELECT * FROM reviews WHERE id = ?', [(int)$_GET['edit']]) : null;
$reviews = rows('SELECT r.*, p.name product_name FROM reviews r LEFT JOIN products p ON p.id = r.product_id ORDER BY r.sort_order, r.created_at DESC');
$products = rows('SELECT id, name FROM products ORDER BY name');

admin_head('Reviews', 'reviews.php');
?>
<div class="flash flash-warn">Only publish genuine customer reviews — never invent testimonials. Reviews you add here appear on the homepage and Customer Stories page.</div>
<div class="panel-grid">
<section class="panel">
    <h2><?= $edit ? 'Edit review' : 'Add review' ?></h2>
    <form method="post" enctype="multipart/form-data" class="admin-form">
        <?= csrf_field() ?><input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
        <label>Customer name <input name="customer_name" value="<?= e($edit['customer_name'] ?? '') ?>" required maxlength="90" placeholder="e.g. Margaret H., Kelowna BC"></label>
        <label>Rating <select name="rating"><?php for ($i = 5; $i >= 1; $i--): ?><option value="<?= $i ?>" <?= ($edit['rating'] ?? 5) == $i ? 'selected' : '' ?>><?= $i ?> star<?= $i > 1 ? 's' : '' ?></option><?php endfor; ?></select></label>
        <label>Review <textarea name="review" rows="4" required><?= e($edit['review'] ?? '') ?></textarea></label>
        <label>Product <select name="product_id"><option value="">—</option>
            <?php foreach ($products as $pr): ?><option value="<?= (int)$pr['id'] ?>" <?= ($edit['product_id'] ?? null) == $pr['id'] ? 'selected' : '' ?>><?= e($pr['name']) ?></option><?php endforeach; ?>
        </select></label>
        <div class="form-grid">
            <label>Source <input name="source" value="<?= e($edit['source'] ?? '') ?>" maxlength="90" placeholder="e.g. Google review"></label>
            <label>Source URL <input name="source_url" value="<?= e($edit['source_url'] ?? '') ?>" maxlength="255"></label>
        </div>
        <label>Photo (optional) <input type="file" name="photo" accept="image/*"></label>
        <label>Sort order <input type="number" name="sort_order" value="<?= (int)($edit['sort_order'] ?? 0) ?>"></label>
        <label class="check"><input type="checkbox" name="is_published" <?= ($edit['is_published'] ?? 0) ? 'checked' : '' ?>> Published</label>
        <button class="btn btn-primary"><?= $edit ? 'Save changes' : 'Add review' ?></button>
        <?php if ($edit): ?><a class="btn btn-ghost" href="<?= e(site_url('admin/reviews.php')) ?>">Cancel</a><?php endif; ?>
    </form>
</section>
<section class="panel">
    <h2>All reviews</h2>
    <table class="tbl">
        <thead><tr><th>Customer</th><th>Review</th><th>Rating</th><th>Product</th><th>Live</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($reviews as $r): ?>
        <tr>
            <td><strong><?= e($r['customer_name']) ?></strong><?= $r['source'] ? '<br><small class="muted">' . e($r['source']) . '</small>' : '' ?></td>
            <td><small><?= e(excerpt($r['review'], 90)) ?></small></td>
            <td><?= str_repeat('★', (int)$r['rating']) ?></td>
            <td><small><?= e($r['product_name'] ?? '—') ?></small></td>
            <td><?= $r['is_published'] ? '✓' : '—' ?></td>
            <td class="cell-actions">
                <a class="btn btn-sm" href="<?= e(site_url('admin/reviews.php?edit=' . (int)$r['id'])) ?>">Edit</a>
                <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-ghost"><?= $r['is_published'] ? 'Unpublish' : 'Publish' ?></button></form>
                <form method="post" class="inline" data-confirm="Delete this review?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$reviews): ?><tr><td colspan="6" class="muted">No reviews yet. The storefront shows a "stories coming soon" state until the first genuine review is published.</td></tr><?php endif; ?>
        </tbody>
    </table>
</section>
</div>
<?php admin_foot(); ?>
