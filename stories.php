<?php
/** Customer stories — real reviews only (managed in admin). */
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/seo.php';

$reviews = rows('SELECT r.*, p.name AS product_name, p.slug AS product_slug FROM reviews r LEFT JOIN products p ON p.id = r.product_id WHERE r.is_published = 1 ORDER BY r.sort_order, r.id DESC');

seo_set(['page_key' => 'stories', 'canonical' => site_url('stories')]);
$crumbs = [['label' => 'Home', 'url' => site_url()], ['label' => 'Customer Stories', 'url' => null]];
require __DIR__ . '/includes/header.php';
?>

<div class="page-hero">
    <div class="container">
        <?= render_breadcrumbs($crumbs) ?>
        <h1 style="margin-top:14px;">Customer Stories</h1>
        <p class="lead">Real experiences from GIO riders — shared with their permission.</p>
    </div>
</div>

<div class="container" style="padding:clamp(36px,5vw,60px) 0 90px;">
    <?php if ($reviews): ?>
    <div class="review-grid">
        <?php foreach ($reviews as $r): ?>
        <figure class="review-card">
            <div class="review-stars" aria-label="<?= (int)$r['rating'] ?> out of 5 stars"><?= str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5 - (int)$r['rating']) ?></div>
            <blockquote class="review-text">&ldquo;<?= e($r['review']) ?>&rdquo;</blockquote>
            <figcaption class="review-meta">
                <?php if ($r['photo']): ?><img src="<?= e(img_thumb_url($r['photo'], 'reviews')) ?>" alt="" width="46" height="46" loading="lazy"><?php endif; ?>
                <span>
                    <span class="review-name"><?= e($r['customer_name']) ?></span><br>
                    <span class="review-sub">
                        <?php if ($r['product_slug']): ?><a href="<?= e(site_url('product/' . $r['product_slug'])) ?>" style="color:var(--brand);"><?= e($r['product_name']) ?></a><?php endif; ?>
                        <?= $r['source'] ? e(' • ' . $r['source']) : '' ?>
                    </span>
                </span>
            </figcaption>
        </figure>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <h3>Customer stories coming soon</h3>
        <p>We're gathering stories from real GIO riders — with their permission — and will publish them here. Ride a GIO and want to share? <a href="<?= e(site_url('contact')) ?>" style="color:var(--brand);">Contact our team</a>.</p>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
