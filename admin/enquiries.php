<?php
/** Admin: enquiries CRM — filter, search, paginate, CSV export. */
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$statuses = ['New','Contact Attempted','Contacted','Quote Sent','Follow-Up','Converted','Closed','Spam'];

/* CSV export */
if (($_GET['export'] ?? '') === 'csv') {
    $rows_ = rows('SELECT * FROM enquiries ORDER BY created_at DESC');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="gio-enquiries-' . date('Ymd-His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Reference','Date','Status','First name','Last name','Email','Phone','Province','City','Postal code','Preferred contact','Product','SKU','Variant','Colour','Price shown (CAD)','Message','UTM source','UTM medium','UTM campaign','Page URL','Business email','Customer ack']);
    foreach ($rows_ as $r) {
        fputcsv($out, [$r['reference'],$r['created_at'],$r['status'],$r['first_name'],$r['last_name'],$r['email'],$r['phone'],$r['province'],$r['city'],$r['postal_code'],$r['preferred_contact'],$r['product_name'],$r['product_sku'],$r['variant'],$r['colour'],$r['price_shown'],$r['message'],$r['utm_source'],$r['utm_medium'],$r['utm_campaign'],$r['page_url'],$r['email_delivery_status'],$r['ack_delivery_status']]);
    }
    log_activity('export_csv', 'Exported enquiries CSV');
    exit;
}

/* bulk status update */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_guard();
    $ids = array_map('intval', (array)($_POST['ids'] ?? []));
    $newStatus = (string)($_POST['bulk_status'] ?? '');
    if ($ids && in_array($newStatus, $statuses, true)) {
        $in = implode(',', array_fill(0, count($ids), '?'));
        q("UPDATE enquiries SET status = ? WHERE id IN ($in)", array_merge([$newStatus], $ids));
        log_activity('enquiry_bulk', "Set $newStatus on " . count($ids) . ' enquiries');
        admin_flash(count($ids) . ' enquir' . (count($ids) === 1 ? 'y' : 'ies') . ' updated.');
    }
    redirect(site_url('admin/enquiries.php?' . http_build_query(array_filter(['status' => $_GET['status'] ?? '', 'q' => $_GET['q'] ?? '']))));
}

$fStatus = (string)($_GET['status'] ?? '');
$fMail   = (string)($_GET['mail'] ?? '');
$fQ      = trim((string)($_GET['q'] ?? ''));
$page    = max(1, (int)($_GET['page'] ?? 1));
$per     = 25;

$where = '1=1'; $args = [];
if (in_array($fStatus, $statuses, true)) { $where .= ' AND status = ?'; $args[] = $fStatus; }
if ($fMail === 'failed') { $where .= " AND (email_delivery_status = 'failed' OR ack_delivery_status = 'failed')"; }
if ($fQ !== '') { $where .= ' AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ? OR product_name LIKE ? OR reference LIKE ?)'; $like = "%$fQ%"; array_push($args, $like, $like, $like, $like, $like, $like); }
$total = (int) val("SELECT COUNT(*) FROM enquiries WHERE $where", $args);
$pages = max(1, (int)ceil($total / $per));
$page = min($page, $pages);
$list = rows("SELECT * FROM enquiries WHERE $where ORDER BY created_at DESC LIMIT $per OFFSET " . (($page - 1) * $per), $args);

$counts = [];
foreach (rows('SELECT status, COUNT(*) c FROM enquiries GROUP BY status') as $r) $counts[$r['status']] = (int)$r['c'];

admin_head('Enquiries', 'enquiries.php');
?>
<div class="status-tabs">
    <a href="<?= e(site_url('admin/enquiries.php')) ?>" class="<?= $fStatus === '' ? 'active' : '' ?>">All <span><?= array_sum($counts) ?></span></a>
    <?php foreach ($statuses as $st): if (empty($counts[$st])) continue; ?>
    <a href="<?= e(site_url('admin/enquiries.php?status=' . urlencode($st))) ?>" class="<?= $fStatus === $st ? 'active' : '' ?>"><?= e($st) ?> <span><?= $counts[$st] ?></span></a>
    <?php endforeach; ?>
</div>

<div class="panel-head">
    <form method="get" class="inline-search">
        <?php if ($fStatus): ?><input type="hidden" name="status" value="<?= e($fStatus) ?>"><?php endif; ?>
        <input type="search" name="q" value="<?= e($fQ) ?>" placeholder="Search name, email, phone, product, reference…">
        <button class="btn btn-sm">Search</button>
    </form>
    <a class="btn" href="<?= e(site_url('admin/enquiries.php?export=csv')) ?>">Export CSV</a>
</div>

<form method="post">
<?= csrf_field() ?>
<section class="panel flush">
<table class="tbl">
    <thead><tr>
        <th><input type="checkbox" id="checkAll" aria-label="Select all"></th>
        <th>Reference</th><th>Customer</th><th>Contact</th><th>Product</th><th>Email</th><th>Status</th><th>Received</th><th></th>
    </tr></thead>
    <tbody>
    <?php foreach ($list as $enq): ?>
        <tr>
            <td><input type="checkbox" name="ids[]" value="<?= (int)$enq['id'] ?>" aria-label="Select"></td>
            <td><code><?= e($enq['reference']) ?></code></td>
            <td><strong><?= e(trim($enq['first_name'] . ' ' . ($enq['last_name'] ?? ''))) ?></strong><br><small class="muted"><?= e($enq['city']) ?>, <?= e($enq['province']) ?></small></td>
            <td><small><?= e($enq['email']) ?><br><?= e($enq['phone']) ?> · prefers <?= e($enq['preferred_contact']) ?></small></td>
            <td><?= e($enq['product_name'] ?? 'General enquiry') ?><?= $enq['colour'] ? '<br><small class="muted">' . e($enq['colour']) . '</small>' : '' ?></td>
            <td>
                <span class="mail-dot <?= e($enq['email_delivery_status']) ?>" title="Business notification: <?= e($enq['email_delivery_status']) ?>"></span>
                <span class="mail-dot <?= e($enq['ack_delivery_status']) ?>" title="Customer acknowledgement: <?= e($enq['ack_delivery_status']) ?>"></span>
            </td>
            <td><span class="status st-<?= e(strtolower(str_replace(' ', '-', $enq['status']))) ?>"><?= e($enq['status']) ?></span></td>
            <td><small><?= e(date('M j, Y', strtotime($enq['created_at']))) ?><br><?= e(date('g:ia', strtotime($enq['created_at']))) ?></small></td>
            <td><a class="btn btn-sm" href="<?= e(site_url('admin/enquiry-view.php?id=' . (int)$enq['id'])) ?>">Open</a></td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$list): ?><tr><td colspan="9" class="muted">No enquiries match these filters.</td></tr><?php endif; ?>
    </tbody>
</table>
</section>

<div class="bulk-bar">
    <select name="bulk_status">
        <option value="">Set status…</option>
        <?php foreach ($statuses as $st): ?><option value="<?= e($st) ?>"><?= e($st) ?></option><?php endforeach; ?>
    </select>
    <button class="btn">Apply to selected</button>
</div>
</form>

<?php if ($pages > 1): ?>
<nav class="pager">
    <?php for ($i = 1; $i <= $pages; $i++):
        $qs = http_build_query(array_filter(['status' => $fStatus, 'q' => $fQ, 'mail' => $fMail, 'page' => $i])); ?>
    <a class="<?= $i === $page ? 'active' : '' ?>" href="<?= e(site_url('admin/enquiries.php?' . $qs)) ?>"><?= $i ?></a>
    <?php endfor; ?>
</nav>
<?php endif; ?>
<?php admin_foot(); ?>
