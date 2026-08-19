<?php
/** Admin: categories CRUD (inline). */
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_guard();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'save') {
            $cid = (int)($_POST['id'] ?? 0);
            $name = trim((string)($_POST['name'] ?? ''));
            if ($name === '') throw new RuntimeException('Category name is required.');
            $slug = trim((string)($_POST['slug'] ?? '')) ?: slugify($name);
            $image = admin_upload_image('image', 'categories');
            if ($cid) {
                if ($image) { $old = val('SELECT image FROM categories WHERE id = ?', [$cid]); admin_delete_upload($old, 'categories'); }
                $sql = 'UPDATE categories SET name=?, slug=?, description=?, menu_label=?, sort_order=?, is_active=?' . ($image ? ', image=?' : '') . ' WHERE id=?';
                $params = [$name, $slug, trim((string)($_POST['description'] ?? '')), trim((string)($_POST['menu_label'] ?? '')) ?: null,
                        (int)($_POST['sort_order'] ?? 0), isset($_POST['is_active']) ? 1 : 0];
                if ($image) $params[] = $image;
                $params[] = $cid;
                q($sql, $params);
                admin_flash('Category saved.');
            } else {
                q('INSERT INTO categories (name, slug, description, menu_label, image, sort_order, is_active) VALUES (?,?,?,?,?,?,?)',
                    [$name, $slug, trim((string)($_POST['description'] ?? '')), trim((string)($_POST['menu_label'] ?? '')) ?: null, $image, (int)($_POST['sort_order'] ?? 0), isset($_POST['is_active']) ? 1 : 0]);
                admin_flash('Category added.');
            }
            log_activity('category_save', $name);
        } elseif ($action === 'delete') {
            $cid = (int)$_POST['id'];
            $used = (int) val('SELECT COUNT(*) FROM product_categories WHERE category_id = ?', [$cid]);
            if ($used) throw new RuntimeException("Category is assigned to $used product(s). Reassign them first.");
            $old = val('SELECT image FROM categories WHERE id = ?', [$cid]);
            admin_delete_upload($old, 'categories');
            q('DELETE FROM categories WHERE id = ?', [$cid]);
            admin_flash('Category deleted.');
        }
    } catch (Throwable $t) {
        admin_flash($t->getMessage(), 'err');
    }
    redirect(site_url('admin/categories.php'));
}

$edit = isset($_GET['edit']) ? row('SELECT * FROM categories WHERE id = ?', [(int)$_GET['edit']]) : null;
$cats = rows('SELECT c.*, (SELECT COUNT(*) FROM product_categories pc WHERE pc.category_id = c.id) products FROM categories c ORDER BY c.sort_order, c.name');

admin_head('Categories', 'categories.php');
?>
<div class="panel-grid">
<section class="panel">
    <h2><?= $edit ? 'Edit category' : 'Add category' ?></h2>
    <form method="post" enctype="multipart/form-data" class="admin-form">
        <?= csrf_field() ?><input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
        <label>Name <input name="name" value="<?= e($edit['name'] ?? '') ?>" required maxlength="120"></label>
        <label>Slug <input name="slug" value="<?= e($edit['slug'] ?? '') ?>" maxlength="140" placeholder="auto if blank"></label>
        <label>Menu label <input name="menu_label" value="<?= e($edit['menu_label'] ?? '') ?>" maxlength="140" placeholder="Optional shorter label"></label>
        <label>Description <textarea name="description" rows="3"><?= e($edit['description'] ?? '') ?></textarea></label>
        <label>Image <input type="file" name="image" accept="image/*"></label>
        <?php if (!empty($edit['image'])): ?><img src="<?= e(img_thumb_url($edit['image'], 'categories')) ?>" alt="" class="preview-img"><?php endif; ?>
        <label>Sort order <input type="number" name="sort_order" value="<?= (int)($edit['sort_order'] ?? 0) ?>"></label>
        <label class="check"><input type="checkbox" name="is_active" <?= ($edit['is_active'] ?? 1) ? 'checked' : '' ?>> Active (visible in menus)</label>
        <button class="btn btn-primary"><?= $edit ? 'Save changes' : 'Add category' ?></button>
        <?php if ($edit): ?><a class="btn btn-ghost" href="<?= e(site_url('admin/categories.php')) ?>">Cancel</a><?php endif; ?>
    </form>
</section>
<section class="panel">
    <h2>All categories</h2>
    <table class="tbl">
        <thead><tr><th></th><th>Name</th><th>Slug</th><th>Products</th><th>Active</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($cats as $c): ?>
        <tr>
            <td class="cell-img"><?php if ($c['image']): ?><img src="<?= e(img_thumb_url($c['image'], 'categories')) ?>" alt="" width="52" height="52"><?php endif; ?></td>
            <td><strong><?= e($c['name']) ?></strong></td>
            <td><code><?= e($c['slug']) ?></code></td>
            <td><?= (int)$c['products'] ?></td>
            <td><?= $c['is_active'] ? '✓' : '—' ?></td>
            <td class="cell-actions">
                <a class="btn btn-sm" href="<?= e(site_url('admin/categories.php?edit=' . (int)$c['id'])) ?>">Edit</a>
                <form method="post" class="inline" data-confirm="Delete this category?">
                    <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                    <button class="btn btn-sm btn-danger">Delete</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
</div>
<?php admin_foot(); ?>
