<?php
/** Blog index */
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/seo.php';

$cats  = rows('SELECT * FROM post_categories ORDER BY name');
$catId = (int)($_GET['cat'] ?? 0);
$sql = 'SELECT p.*, (SELECT c.name FROM post_categories c JOIN post_category_map m ON m.category_id = c.id AND m.post_id = p.id LIMIT 1) AS cat_name
        FROM posts p WHERE p.is_published = 1';
$params = [];
if ($catId) {
    $sql .= ' AND p.id IN (SELECT post_id FROM post_category_map WHERE category_id = ?)';
    $params[] = $catId;
}
$sql .= ' ORDER BY p.published_at DESC';
$posts = rows($sql, $params);

seo_set(['page_key' => 'blog', 'canonical' => site_url('blog')]);
$crumbs = [['label' => 'Home', 'url' => site_url()], ['label' => 'Blog & Guides', 'url' => null]];
require __DIR__ . '/includes/header.php';
?>

<div class="page-hero">
    <div class="container">
        <?= render_breadcrumbs($crumbs) ?>
        <h1 style="margin-top:14px;">Blog &amp; Buying Guides</h1>
        <p class="lead">Practical reading for confident riders — choosing, charging, storing and enjoying your GIO.</p>
        <div class="active-chips" style="margin-top:18px;">
            <a class="chip" href="<?= e(site_url('blog')) ?>" <?= !$catId ? 'style="background:var(--graphite);color:#fff;border-color:var(--graphite);"' : '' ?>>All</a>
            <?php foreach ($cats as $c): ?>
            <a class="chip" href="<?= e(site_url('blog?cat=' . $c['id'])) ?>" <?= $catId === (int)$c['id'] ? 'style="background:var(--graphite);color:#fff;border-color:var(--graphite);"' : '' ?>><?= e($c['name']) ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="container" style="padding:clamp(36px,5vw,56px) 0 90px;">
    <?php if (!$posts): ?>
    <div class="empty-state"><h3>No articles yet</h3><p>New guides are published regularly — check back soon.</p></div>
    <?php else: ?>
    <div class="article-grid">
        <?php foreach ($posts as $post): ?>
        <a class="article-card" href="<?= e(site_url('blog/' . $post['slug'])) ?>">
            <span class="article-img"><img src="<?= e(img_thumb_url($post['featured_image'], 'posts')) ?>" alt="<?= e($post['title']) ?>" loading="lazy" width="640" height="400"></span>
            <span class="article-body">
                <span class="article-cat"><?= e($post['cat_name'] ?? 'Guide') ?></span>
                <h3><?= e($post['title']) ?></h3>
                <p><?= e($post['excerpt'] ?: excerpt($post['content'])) ?></p>
                <span class="article-date"><?= e(date('F j, Y', strtotime($post['published_at'] ?? $post['created_at']))) ?></span>
            </span>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
