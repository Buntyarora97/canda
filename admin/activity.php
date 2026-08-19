<?php
/** Admin: activity log viewer. */
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$page = max(1, (int)($_GET['page'] ?? 1));
$per = 50;
$total = (int) val('SELECT COUNT(*) FROM activity_logs');
$pages = max(1, (int)ceil($total / $per));
$page = min($page, $pages);
$logs = rows('SELECT l.*, a.name admin_name, a.email FROM activity_logs l LEFT JOIN admins a ON a.id = l.admin_id ORDER BY l.id DESC LIMIT ' . $per . ' OFFSET ' . (($page - 1) * $per));

admin_head('Activity log', 'activity.php');
?>
<section class="panel flush">
<table class="tbl">
    <thead><tr><th>When</th><th>Admin</th><th>Action</th><th>Details</th></tr></thead>
    <tbody>
    <?php foreach ($logs as $l): ?>
    <tr>
        <td><small><?= e(date('M j, Y g:ia', strtotime($l['created_at']))) ?></small></td>
        <td><?= e($l['admin_name'] ?? '—') ?></td>
        <td><span class="chip"><?= e($l['action']) ?></span></td>
        <td><small><?= e($l['details'] ?? '') ?></small></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$logs): ?><tr><td colspan="4" class="muted">No activity recorded yet.</td></tr><?php endif; ?>
    </tbody>
</table>
</section>
<?php if ($pages > 1): ?>
<nav class="pager">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
    <a class="<?= $i === $page ? 'active' : '' ?>" href="<?= e(site_url('admin/activity.php?page=' . $i)) ?>"><?= $i ?></a>
    <?php endfor; ?>
</nav>
<?php endif; ?>
<?php admin_foot(); ?>
