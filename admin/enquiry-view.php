<?php
/** Admin: single enquiry — full detail, status, notes, print, resend emails. */
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$statuses = ['New','Contact Attempted','Contacted','Quote Sent','Follow-Up','Converted','Closed','Spam'];
$id = (int)($_GET['id'] ?? 0);
$enq = row('SELECT * FROM enquiries WHERE id = ?', [$id]);
if (!$enq) { admin_flash('Enquiry not found.', 'err'); redirect(site_url('admin/enquiries.php')); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_guard();
    $action = $_POST['action'] ?? '';
    if ($action === 'status' && in_array($_POST['status'] ?? '', $statuses, true)) {
        q('UPDATE enquiries SET status = ? WHERE id = ?', [$_POST['status'], $id]);
        log_activity('enquiry_status', "{$enq['reference']} → {$_POST['status']}");
        admin_flash('Status updated to ' . $_POST['status'] . '.');
    } elseif ($action === 'note' && trim((string)($_POST['note'] ?? '')) !== '') {
        q('INSERT INTO enquiry_notes (enquiry_id, admin_id, note) VALUES (?,?,?)', [$id, $_SESSION['admin_id'], trim((string)$_POST['note'])]);
        log_activity('enquiry_note', "Note added to {$enq['reference']}");
        admin_flash('Note added.');
    } elseif ($action === 'resend') {
        try {
            $result = send_enquiry_emails($enq);
            $okB = !empty($result['notify']['ok']);
            $okA = !empty($result['ack']['ok']);
            q('UPDATE enquiries SET email_delivery_status = ?, ack_delivery_status = ? WHERE id = ?',
                [$okB ? 'sent' : 'failed', $okA ? 'sent' : ($enq['ack_delivery_status'] === 'disabled' ? 'disabled' : 'failed'), $id]);
            admin_flash($okB && $okA ? 'Emails re-sent successfully.' : 'Resend attempted — check Email settings if failures persist.', $okB && $okA ? 'ok' : 'warn');
            log_activity('enquiry_resend', "Resent emails for {$enq['reference']}");
        } catch (Throwable $t) {
            admin_flash('Resend failed: ' . $t->getMessage(), 'err');
        }
    } elseif ($action === 'delete') {
        q('DELETE FROM enquiries WHERE id = ?', [$id]);
        log_activity('enquiry_delete', "Deleted enquiry {$enq['reference']}");
        admin_flash('Enquiry deleted.');
        redirect(site_url('admin/enquiries.php'));
    }
    redirect(site_url('admin/enquiry-view.php?id=' . $id));
}

$notes = rows('SELECT n.*, a.name admin_name FROM enquiry_notes n LEFT JOIN admins a ON a.id = n.admin_id WHERE n.enquiry_id = ? ORDER BY n.created_at DESC', [$id]);
$product = $enq['product_id'] ? row('SELECT id, slug, name FROM products WHERE id = ?', [$enq['product_id']]) : null;

admin_head('Enquiry ' . $enq['reference'], 'enquiries.php');
?>
<div class="panel-head no-print">
    <a class="btn btn-ghost" href="<?= e(site_url('admin/enquiries.php')) ?>">← All enquiries</a>
    <div>
        <button class="btn" onclick="window.print()">Print</button>
        <form method="post" class="inline" data-confirm="Resend the business notification and customer acknowledgement emails?">
            <?= csrf_field() ?><input type="hidden" name="action" value="resend">
            <button class="btn">Resend emails</button>
        </form>
        <form method="post" class="inline" data-confirm="Permanently delete this enquiry?">
            <?= csrf_field() ?><input type="hidden" name="action" value="delete">
            <button class="btn btn-danger">Delete</button>
        </form>
    </div>
</div>

<div class="panel-grid enquiry-grid">
    <section class="panel">
        <div class="enquiry-ref">
            <code><?= e($enq['reference']) ?></code>
            <span class="status st-<?= e(strtolower(str_replace(' ', '-', $enq['status']))) ?>"><?= e($enq['status']) ?></span>
        </div>
        <h2>Customer</h2>
        <dl class="detail-list">
            <dt>Name</dt><dd><?= e(trim($enq['first_name'] . ' ' . ($enq['last_name'] ?? ''))) ?></dd>
            <dt>Email</dt><dd><a href="mailto:<?= e($enq['email']) ?>"><?= e($enq['email']) ?></a>
                <button class="copy-btn" data-copy="<?= e($enq['email']) ?>" title="Copy email">⧉</button></dd>
            <dt>Phone</dt><dd><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $enq['phone'])) ?>"><?= e($enq['phone']) ?></a>
                <button class="copy-btn" data-copy="<?= e($enq['phone']) ?>" title="Copy phone">⧉</button></dd>
            <dt>Location</dt><dd><?= e($enq['city']) ?>, <?= e(provinces()[$enq['province']] ?? $enq['province']) ?><?= $enq['postal_code'] ? ' · ' . e($enq['postal_code']) : '' ?></dd>
            <dt>Prefers</dt><dd><?= e($enq['preferred_contact']) ?></dd>
            <dt>Received</dt><dd><?= e(date('F j, Y \a\t g:ia', strtotime($enq['created_at']))) ?></dd>
        </dl>
        <?php if ($enq['message']): ?>
        <h2>Message</h2>
        <div class="msg-box"><?= nl2br(e($enq['message'])) ?></div>
        <?php endif; ?>
        <h2>Tracking</h2>
        <dl class="detail-list">
            <dt>Page</dt><dd class="break"><?= e($enq['page_url'] ?? '—') ?></dd>
            <dt>UTM</dt><dd><?= e(implode(' / ', array_filter([$enq['utm_source'], $enq['utm_medium'], $enq['utm_campaign']])) ?: '—') ?></dd>
            <dt>Referrer</dt><dd class="break"><?= e($enq['referrer'] ?? '—') ?></dd>
            <dt>Business email</dt><dd><span class="status st-<?= $enq['email_delivery_status'] === 'sent' ? 'converted' : ($enq['email_delivery_status'] === 'failed' ? 'spam' : 'new') ?>"><?= e($enq['email_delivery_status']) ?></span></dd>
            <dt>Customer ack</dt><dd><span class="status st-<?= $enq['ack_delivery_status'] === 'sent' ? 'converted' : ($enq['ack_delivery_status'] === 'failed' ? 'spam' : 'new') ?>"><?= e($enq['ack_delivery_status']) ?></span></dd>
        </dl>
    </section>

    <div>
        <section class="panel">
            <h2>Product</h2>
            <dl class="detail-list">
                <dt>Product</dt><dd><?php if ($product): ?><a href="<?= e(product_url($product)) ?>" target="_blank" rel="noopener"><?= e($enq['product_name']) ?></a><?php else: ?><?= e($enq['product_name'] ?? 'General enquiry') ?><?php endif; ?></dd>
                <?php if ($enq['product_sku']): ?><dt>SKU</dt><dd><?= e($enq['product_sku']) ?></dd><?php endif; ?>
                <?php if ($enq['colour']): ?><dt>Colour</dt><dd><?= e($enq['colour']) ?></dd><?php endif; ?>
                <?php if ($enq['variant']): ?><dt>Option</dt><dd><?= e($enq['variant']) ?></dd><?php endif; ?>
                <?php if ($enq['price_shown'] !== null): ?><dt>Price shown</dt><dd><?= e(cad((float)$enq['price_shown'])) ?></dd><?php endif; ?>
            </dl>
        </section>

        <section class="panel no-print">
            <h2>Update status</h2>
            <form method="post" class="status-form">
                <?= csrf_field() ?><input type="hidden" name="action" value="status">
                <div class="status-picker">
                    <?php foreach ($statuses as $st): ?>
                    <label class="radio-pill"><input type="radio" name="status" value="<?= e($st) ?>" <?= $enq['status'] === $st ? 'checked' : '' ?>><span><?= e($st) ?></span></label>
                    <?php endforeach; ?>
                </div>
                <button class="btn btn-primary">Update status</button>
            </form>
        </section>

        <section class="panel no-print">
            <h2>Notes</h2>
            <?php foreach ($notes as $n): ?>
            <div class="note">
                <p><?= nl2br(e($n['note'])) ?></p>
                <small><?= e($n['admin_name'] ?? 'System') ?> · <?= e(date('M j, Y g:ia', strtotime($n['created_at']))) ?></small>
            </div>
            <?php endforeach; ?>
            <form method="post">
                <?= csrf_field() ?><input type="hidden" name="action" value="note">
                <textarea name="note" rows="3" placeholder="Add a note (call outcome, quote details…)" maxlength="2000" required></textarea>
                <button class="btn">Add note</button>
            </form>
        </section>
    </div>
</div>
<?php admin_foot(); ?>
