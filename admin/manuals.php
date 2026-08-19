<?php
/** Admin: product manuals (PDF). */
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_guard();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'save') {
            $title = trim((string)($_POST['title'] ?? ''));
            if ($title === '') throw new RuntimeException('Title is required.');
            $file = admin_upload_pdf('file', 'manuals');
            if (!$file) throw new RuntimeException('Please choose a PDF file.');
            q('INSERT INTO manuals (product_id, title, language, version, file, published_at) VALUES (?,?,?,?,?,?)', [
                ($_POST['product_id'] ?? '') !== '' ? (int)$_POST['product_id'] : null,
                $title,
                trim((string)($_POST['language'] ?? 'English')) ?: 'English',
                trim((string)($_POST['version'] ?? '')) ?: null,
                $file,
                trim((string)($_POST['published_at'] ?? '')) ?: null,
            ]);
            log_activity('manual_add', $title);
            admin_flash('Manual uploaded.');
        } elseif ($action === 'delete') {
            $m = row('SELECT * FROM manuals WHERE id = ?', [(int)$_POST['id']]);
            if ($m) {
                $p = GIO_ROOT . '/uploads/manuals/' . basename($m['file']);
                if (is_file($p)) @unlink($p);
                q('DELETE FROM manuals WHERE id = ?', [$m['id']]);
                admin_flash('Manual deleted.');
            }
        }
    } catch (Throwable $t) {
        admin_flash($t->getMessage(), 'err');
    }
    redirect(site_url('admin/manuals.php'));
}

$manuals = rows('SELECT m.*, p.name product_name FROM manuals m LEFT JOIN products p ON p.id = m.product_id ORDER BY m.created_at DESC');
$products = rows('SELECT id, name FROM products ORDER BY name');

admin_head('Manuals', 'manuals.php');
?>
<div class="panel-grid">
<section class="panel">
    <h2>Upload manual (PDF)</h2>
    <form method="post" enctype="multipart/form-data" class="admin-form">
        <?= csrf_field() ?><input type="hidden" name="action" value="save">
        <label>Title <input name="title" required maxlength="190" placeholder="e.g. Titan Premium Owner's Manual"></label>
        <label>Product
            <select name="product_id"><option value="">— General —</option>
            <?php foreach ($products as $pr): ?><option value="<?= (int)$pr['id'] ?>"><?= e($pr['name']) ?></option><?php endforeach; ?>
            </select></label>
        <div class="form-grid">
            <label>Language <input name="language" value="English" maxlength="30"></label>
            <label>Version <input name="version" maxlength="30" placeholder="e.g. 2026.1"></label>
        </div>
        <label>PDF file <input type="file" name="file" accept="application/pdf" required></label>
        <button class="btn btn-primary">Upload</button>
    </form>
</section>
<section class="panel">
    <h2>Library</h2>
    <table class="tbl">
        <thead><tr><th>Title</th><th>Product</th><th>Lang</th><th>Version</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($manuals as $m): ?>
        <tr>
            <td><strong><?= e($m['title']) ?></strong></td>
            <td><?= e($m['product_name'] ?? '—') ?></td>
            <td><?= e($m['language']) ?></td>
            <td><?= e($m['version'] ?? '—') ?></td>
            <td class="cell-actions">
                <a class="btn btn-sm btn-ghost" href="<?= e(site_url('uploads/manuals/' . rawurlencode($m['file']))) ?>" target="_blank" rel="noopener">View</a>
                <form method="post" class="inline" data-confirm="Delete this manual?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$manuals): ?><tr><td colspan="5" class="muted">No manuals uploaded yet.</td></tr><?php endif; ?>
        </tbody>
    </table>
</section>
</div>
<?php admin_foot(); ?>
