<?php
/** Admin dashboard: stats, enquiry trend, sources, top products. */
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$stats = [
    'new'       => (int) val("SELECT COUNT(*) FROM enquiries WHERE status = 'New'"),
    'week'      => (int) val("SELECT COUNT(*) FROM enquiries WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"),
    'month'     => (int) val("SELECT COUNT(*) FROM enquiries WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"),
    'total'     => (int) val("SELECT COUNT(*) FROM enquiries"),
    'products'  => (int) val("SELECT COUNT(*) FROM products WHERE is_published = 1"),
    'converted' => (int) val("SELECT COUNT(*) FROM enquiries WHERE status = 'Converted'"),
];
$convRate = $stats['total'] > 0 ? round($stats['converted'] / $stats['total'] * 100) : 0;

// 30-day enquiry trend
$trend = rows("SELECT DATE(created_at) d, COUNT(*) c FROM enquiries
               WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
               GROUP BY DATE(created_at)");
$trendMap = array_column($trend, 'c', 'd');
$trendLabels = []; $trendData = [];
for ($i = 29; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $trendLabels[] = date('M j', strtotime($d));
    $trendData[]   = (int)($trendMap[$d] ?? 0);
}

// lead sources
$sources = rows("SELECT CASE
        WHEN utm_source IS NOT NULL AND utm_source <> '' THEN utm_source
        WHEN referrer IS NOT NULL AND referrer <> '' THEN 'Referral'
        ELSE 'Direct' END src, COUNT(*) c
    FROM enquiries GROUP BY src ORDER BY c DESC LIMIT 6");

// top enquired products
$top = rows("SELECT product_name, COUNT(*) c FROM enquiries
             WHERE product_name IS NOT NULL GROUP BY product_name ORDER BY c DESC LIMIT 5");
$maxTop = max(1, ...array_map(fn($r) => (int)$r['c'], $top ?: [['c' => 1]]));

// latest enquiries
$latest = rows("SELECT id, reference, product_name, first_name, last_name, email, status, created_at
                FROM enquiries ORDER BY created_at DESC LIMIT 8");

// email failures needing attention
$mailIssues = (int) val("SELECT COUNT(*) FROM enquiries WHERE email_delivery_status = 'failed' OR ack_delivery_status = 'failed'");

admin_head('Dashboard', 'index.php');
?>
<?php if ($mailIssues > 0): ?>
<div class="flash flash-warn">⚠ <?= $mailIssues ?> enquir<?= $mailIssues === 1 ? 'y has' : 'ies have' ?> a failed email delivery — the leads are safe in the CRM. <a href="<?= e(site_url('admin/enquiries.php?mail=failed')) ?>">Review them →</a></div>
<?php endif; ?>

<div class="stat-grid">
    <div class="stat-card accent"><span class="stat-num"><?= $stats['new'] ?></span><span class="stat-label">New enquiries</span><a href="<?= e(site_url('admin/enquiries.php?status=New')) ?>" class="stat-link">View →</a></div>
    <div class="stat-card"><span class="stat-num"><?= $stats['week'] ?></span><span class="stat-label">Last 7 days</span></div>
    <div class="stat-card"><span class="stat-num"><?= $stats['month'] ?></span><span class="stat-label">Last 30 days</span></div>
    <div class="stat-card"><span class="stat-num"><?= $stats['total'] ?></span><span class="stat-label">All-time enquiries</span></div>
    <div class="stat-card"><span class="stat-num"><?= $stats['products'] ?></span><span class="stat-label">Published products</span></div>
    <div class="stat-card"><span class="stat-num"><?= $convRate ?>%</span><span class="stat-label">Conversion rate</span></div>
</div>

<div class="panel-grid">
    <section class="panel">
        <h2>Enquiry trend — last 30 days</h2>
        <canvas id="trendChart" height="220"
            data-labels="<?= e(json_encode($trendLabels)) ?>"
            data-values="<?= e(json_encode($trendData)) ?>"></canvas>
    </section>
    <section class="panel">
        <h2>Lead sources</h2>
        <?php if (!$sources): ?><p class="muted">No enquiries yet.</p><?php endif; ?>
        <ul class="bar-list">
        <?php foreach ($sources as $s): $pct = min(100, round($s['c'] / max(1, $stats['total']) * 100)); ?>
            <li><span class="bar-label"><?= e($s['src']) ?></span>
                <span class="bar-track"><span class="bar-fill" style="width:<?= $pct ?>%"></span></span>
                <span class="bar-num"><?= (int)$s['c'] ?></span></li>
        <?php endforeach; ?>
        </ul>
        <h2 style="margin-top:26px">Top enquired products</h2>
        <ul class="bar-list">
        <?php foreach ($top as $t): $pct = round($t['c'] / $maxTop * 100); ?>
            <li><span class="bar-label"><?= e($t['product_name']) ?></span>
                <span class="bar-track"><span class="bar-fill red" style="width:<?= $pct ?>%"></span></span>
                <span class="bar-num"><?= (int)$t['c'] ?></span></li>
        <?php endforeach; ?>
        <?php if (!$top): ?><p class="muted">No data yet.</p><?php endif; ?>
        </ul>
    </section>
</div>

<section class="panel">
    <div class="panel-head"><h2>Latest enquiries</h2><a class="btn btn-ghost btn-sm" href="<?= e(site_url('admin/enquiries.php')) ?>">Open CRM</a></div>
    <table class="tbl">
        <thead><tr><th>Reference</th><th>Customer</th><th>Product</th><th>Status</th><th>Received</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($latest as $enq): ?>
            <tr>
                <td><code><?= e($enq['reference']) ?></code></td>
                <td><?= e(trim($enq['first_name'] . ' ' . ($enq['last_name'] ?? ''))) ?><br><small class="muted"><?= e($enq['email']) ?></small></td>
                <td><?= e($enq['product_name'] ?? 'General') ?></td>
                <td><span class="status st-<?= e(strtolower(str_replace(' ', '-', $enq['status']))) ?>"><?= e($enq['status']) ?></span></td>
                <td><?= e(date('M j, g:ia', strtotime($enq['created_at']))) ?></td>
                <td><a class="btn btn-sm" href="<?= e(site_url('admin/enquiry-view.php?id=' . (int)$enq['id'])) ?>">Open</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$latest): ?><tr><td colspan="6" class="muted">No enquiries yet — they will appear here the moment a customer submits the form.</td></tr><?php endif; ?>
        </tbody>
    </table>
</section>
<?php admin_foot(); ?>
