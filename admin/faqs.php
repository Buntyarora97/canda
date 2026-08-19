<?php
/** Admin: FAQs CRUD. */
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_guard();
    $action = $_POST['action'] ?? '';
    if ($action === 'save') {
        $fid = (int)($_POST['id'] ?? 0);
        $q_ = trim((string)($_POST['question'] ?? ''));
        $a_ = trim((string)($_POST['answer'] ?? ''));
        if ($q_ && $a_) {
            $data = [trim((string)($_POST['category'] ?? 'General')) ?: 'General', $q_, $a_,
                     ($_POST['product_id'] ?? '') !== '' ? (int)$_POST['product_id'] : null,
                     (int)($_POST['sort_order'] ?? 0), isset($_POST['is_published']) ? 1 : 0];
            if ($fid) {
                q('UPDATE faqs SET category=?, question=?, answer=?, product_id=?, sort_order=?, is_published=? WHERE id=?', [...$data, $fid]);
            } else {
                q('INSERT INTO faqs (category, question, answer, product_id, sort_order, is_published) VALUES (?,?,?,?,?,?)', $data);
            }
            log_activity('faq_save', $q_);
            admin_flash('FAQ saved.');
        } else admin_flash('Question and answer are both required.', 'err');
    } elseif ($action === 'delete') {
        q('DELETE FROM faqs WHERE id = ?', [(int)$_POST['id']]);
        admin_flash('FAQ deleted.');
    }
    redirect(site_url('admin/faqs.php'));
}

$edit = isset($_GET['edit']) ? row('SELECT * FROM faqs WHERE id = ?', [(int)$_GET['edit']]) : null;
$faqs = rows('SELECT f.*, p.name product_name FROM faqs f LEFT JOIN products p ON p.id = f.product_id ORDER BY f.category, f.sort_order, f.id');
$products = rows('SELECT id, name FROM products ORDER BY name');

admin_head('FAQs', 'faqs.php');
?>
<div class="panel-grid">
<section class="panel">
    <h2><?= $edit ? 'Edit FAQ' : 'Add FAQ' ?></h2>
    <form method="post" class="admin-form">
        <?= csrf_field() ?><input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
        <label>Category <input name="category" value="<?= e($edit['category'] ?? 'General') ?>" maxlength="60"></label>
        <label>Question <input name="question" value="<?= e($edit['question'] ?? '') ?>" required maxlength="255"></label>
        <label>Answer <textarea name="answer" rows="5" required><?= e($edit['answer'] ?? '') ?></textarea></label>
        <label>Related product (optional)
            <select name="product_id"><option value="">—</option>
            <?php foreach ($products as $pr): ?><option value="<?= (int)$pr['id'] ?>" <?= ($edit['product_id'] ?? null) == $pr['id'] ? 'selected' : '' ?>><?= e($pr['name']) ?></option><?php endforeach; ?>
            </select></label>
        <label>Sort order <input type="number" name="sort_order" value="<?= (int)($edit['sort_order'] ?? 0) ?>"></label>
        <label class="check"><input type="checkbox" name="is_published" <?= ($edit['is_published'] ?? 1) ? 'checked' : '' ?>> Published</label>
        <button class="btn btn-primary"><?= $edit ? 'Save changes' : 'Add FAQ' ?></button>
        <?php if ($edit): ?><a class="btn btn-ghost" href="<?= e(site_url('admin/faqs.php')) ?>">Cancel</a><?php endif; ?>
    </form>
</section>
<section class="panel">
    <h2>All FAQs (<?= count($faqs) ?>)</h2>
    <table class="tbl">
        <thead><tr><th>Category</th><th>Question</th><th>Product</th><th>Live</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($faqs as $f): ?>
        <tr>
            <td><span class="chip"><?= e($f['category']) ?></span></td>
            <td><?= e($f['question']) ?></td>
            <td><small><?= e($f['product_name'] ?? '—') ?></small></td>
            <td><?= $f['is_published'] ? '✓' : '—' ?></td>
            <td class="cell-actions">
                <a class="btn btn-sm" href="<?= e(site_url('admin/faqs.php?edit=' . (int)$f['id'])) ?>">Edit</a>
                <form method="post" class="inline" data-confirm="Delete this FAQ?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$f['id'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
</div>
<?php admin_foot(); ?>
