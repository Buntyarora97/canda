<?php
/** Admin: homepage banners with scheduling + desktop/mobile images. */
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_guard();
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'save') {
            $bid = (int)($_POST['id'] ?? 0);
            $headline = trim((string)($_POST['headline'] ?? ''));
            if ($headline === '') throw new RuntimeException('Headline is required.');
            $desktop = admin_upload_image('desktop_image', 'banners');
            $mobile  = admin_upload_image('mobile_image', 'banners');
            $fields = [
                'eyebrow' => trim((string)($_POST['eyebrow'] ?? '')) ?: null,
                'headline' => $headline,
                'subheading' => trim((string)($_POST['subheading'] ?? '')) ?: null,
                'cta1_text' => trim((string)($_POST['cta1_text'] ?? '')) ?: null,
                'cta1_url' => trim((string)($_POST['cta1_url'] ?? '')) ?: null,
                'cta2_text' => trim((string)($_POST['cta2_text'] ?? '')) ?: null,
                'cta2_url' => trim((string)($_POST['cta2_url'] ?? '')) ?: null,
                'text_alignment' => ($_POST['text_alignment'] ?? 'left') === 'center' ? 'center' : 'left',
                'overlay_opacity' => max(0, min(90, (int)($_POST['overlay_opacity'] ?? 60))),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'schedule_start' => trim((string)($_POST['schedule_start'] ?? '')) ?: null,
                'schedule_end' => trim((string)($_POST['schedule_end'] ?? '')) ?: null,
                'sort_order' => (int)($_POST['sort_order'] ?? 0),
            ];
            if ($bid) {
                $existing = row('SELECT * FROM banners WHERE id = ?', [$bid]);
                if (!$existing) throw new RuntimeException('Banner not found.');
                if ($desktop) { admin_delete_upload($existing['desktop_image'], 'banners'); $fields['desktop_image'] = $desktop; }
                if ($mobile)  { admin_delete_upload($existing['mobile_image'], 'banners');  $fields['mobile_image'] = $mobile; }
                $set = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($fields)));
                q("UPDATE banners SET $set WHERE id = :__id", $fields + ['__id' => $bid]);
                admin_flash('Banner saved.');
            } else {
                if (!$desktop) throw new RuntimeException('A desktop image is required for a new banner.');
                $fields['desktop_image'] = $desktop;
                $fields['mobile_image'] = $mobile;
                $cols = implode(',', array_keys($fields));
                q("INSERT INTO banners ($cols) VALUES (:" . implode(',:', array_keys($fields)) . ")", $fields);
                admin_flash('Banner added.');
            }
            log_activity('banner_save', $headline);
        } elseif ($action === 'delete') {
            $b = row('SELECT * FROM banners WHERE id = ?', [(int)$_POST['id']]);
            if ($b) { admin_delete_upload($b['desktop_image'], 'banners'); admin_delete_upload($b['mobile_image'], 'banners'); q('DELETE FROM banners WHERE id = ?', [$b['id']]); admin_flash('Banner deleted.'); log_activity('banner_delete', $b['headline']); }
        } elseif ($action === 'toggle') {
            q('UPDATE banners SET is_active = 1 - is_active WHERE id = ?', [(int)$_POST['id']]);
            admin_flash('Banner toggled.');
        }
    } catch (Throwable $t) {
        admin_flash($t->getMessage(), 'err');
    }
    redirect(site_url('admin/banners.php'));
}

$edit = isset($_GET['edit']) ? row('SELECT * FROM banners WHERE id = ?', [(int)$_GET['edit']]) : null;
$banners = rows('SELECT * FROM banners ORDER BY sort_order, id');

admin_head('Banners', 'banners.php');
?>
<div class="panel-grid">
<section class="panel">
    <h2><?= $edit ? 'Edit banner' : 'Add banner' ?></h2>
    <form method="post" enctype="multipart/form-data" class="admin-form">
        <?= csrf_field() ?><input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
        <label>Eyebrow <input name="eyebrow" value="<?= e($edit['eyebrow'] ?? '') ?>" maxlength="120"></label>
        <label>Headline <input name="headline" value="<?= e($edit['headline'] ?? '') ?>" required maxlength="190"></label>
        <label>Subheading <input name="subheading" value="<?= e($edit['subheading'] ?? '') ?>" maxlength="255"></label>
        <div class="form-grid">
            <label>Primary button text <input name="cta1_text" value="<?= e($edit['cta1_text'] ?? '') ?>" maxlength="60"></label>
            <label>Primary button URL <input name="cta1_url" value="<?= e($edit['cta1_url'] ?? '') ?>" maxlength="255" placeholder="/shop"></label>
            <label>Secondary button text <input name="cta2_text" value="<?= e($edit['cta2_text'] ?? '') ?>" maxlength="60"></label>
            <label>Secondary button URL <input name="cta2_url" value="<?= e($edit['cta2_url'] ?? '') ?>" maxlength="255"></label>
        </div>
        <label>Desktop image (1920×900) <input type="file" name="desktop_image" accept="image/*" <?= $edit ? '' : 'required' ?>></label>
        <label>Mobile image (1080×1350+) <input type="file" name="mobile_image" accept="image/*"></label>
        <?php if ($edit): ?>
        <div class="preview-pair">
            <img src="<?= e(img_thumb_url($edit['desktop_image'], 'banners')) ?>" alt="Desktop current">
            <?php if ($edit['mobile_image']): ?><img src="<?= e(img_thumb_url($edit['mobile_image'], 'banners')) ?>" alt="Mobile current"><?php endif; ?>
        </div>
        <?php endif; ?>
        <div class="form-grid">
            <label>Text alignment <select name="text_alignment">
                <option value="left" <?= ($edit['text_alignment'] ?? '') === 'left' ? 'selected' : '' ?>>Left</option>
                <option value="center" <?= ($edit['text_alignment'] ?? '') === 'center' ? 'selected' : '' ?>>Center</option>
            </select></label>
            <label>Overlay darkness (0–90) <input type="number" name="overlay_opacity" min="0" max="90" value="<?= (int)($edit['overlay_opacity'] ?? 60) ?>"></label>
            <label>Schedule start <input type="datetime-local" name="schedule_start" value="<?= !empty($edit['schedule_start']) ? e(date('Y-m-d\TH:i', strtotime($edit['schedule_start']))) : '' ?>"></label>
            <label>Schedule end <input type="datetime-local" name="schedule_end" value="<?= !empty($edit['schedule_end']) ? e(date('Y-m-d\TH:i', strtotime($edit['schedule_end']))) : '' ?>"></label>
            <label>Sort order <input type="number" name="sort_order" value="<?= (int)($edit['sort_order'] ?? 0) ?>"></label>
        </div>
        <label class="check"><input type="checkbox" name="is_active" <?= ($edit['is_active'] ?? 1) ? 'checked' : '' ?>> Active</label>
        <button class="btn btn-primary"><?= $edit ? 'Save changes' : 'Add banner' ?></button>
        <?php if ($edit): ?><a class="btn btn-ghost" href="<?= e(site_url('admin/banners.php')) ?>">Cancel</a><?php endif; ?>
    </form>
</section>
<section class="panel">
    <h2>Current banners</h2>
    <table class="tbl">
        <thead><tr><th></th><th>Headline</th><th>Window</th><th>Active</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($banners as $b): ?>
        <tr>
            <td class="cell-img"><img src="<?= e(img_thumb_url($b['desktop_image'], 'banners')) ?>" alt="" width="72" height="40" style="object-fit:cover"></td>
            <td><strong><?= e($b['headline']) ?></strong><br><small class="muted"><?= e($b['subheading'] ?? '') ?></small></td>
            <td><small><?= $b['schedule_start'] ? e(date('M j', strtotime($b['schedule_start']))) . ' → ' : 'Always' ?><?= $b['schedule_end'] ? e(date('M j', strtotime($b['schedule_end']))) : '' ?></small></td>
            <td><?= $b['is_active'] ? '✓' : '—' ?></td>
            <td class="cell-actions">
                <a class="btn btn-sm" href="<?= e(site_url('admin/banners.php?edit=' . (int)$b['id'])) ?>">Edit</a>
                <form method="post" class="inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><button class="btn btn-sm btn-ghost"><?= $b['is_active'] ? 'Disable' : 'Enable' ?></button></form>
                <form method="post" class="inline" data-confirm="Delete this banner?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
</div>
<?php admin_foot(); ?>
