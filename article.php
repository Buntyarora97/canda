<?php
/** Blog article page */
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/seo.php';
require_once __DIR__ . '/includes/schema.php';

$slug = strtolower(trim((string)($_GET['slug'] ?? '')));
$post = $slug ? row('SELECT * FROM posts WHERE slug = ? AND is_published = 1', [$slug]) : null;
if (!$post) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}
$catName = val('SELECT c.name FROM post_categories c JOIN post_category_map m ON m.category_id = c.id WHERE m.post_id = ? LIMIT 1', [$post['id']]);
$more = rows('SELECT * FROM posts WHERE is_published = 1 AND id != ? ORDER BY published_at DESC LIMIT 3', [$post['id']]);

seo_set([
    'title' => $post['seo_title'] ?: ($post['title'] . ' | ' . SITE_NAME),
    'description' => $post['seo_description'] ?: ($post['excerpt'] ?: excerpt($post['content'])),
    'canonical' => site_url('blog/' . $post['slug']),
    'og_image' => $post['featured_image'] ? img_url($post['featured_image'], 'posts') : site_url('assets/images/og-default.jpg'),
    'og_type' => 'article',
]);
$GLOBALS['schemas'][] = schema_article($post);
$crumbs = [['label' => 'Home', 'url' => site_url()], ['label' => 'Blog', 'url' => site_url('blog')], ['label' => $post['title'], 'url' => null]];
$GLOBALS['schemas'][] = schema_breadcrumbs($crumbs);
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <?= render_breadcrumbs($crumbs) ?>
    <article class="content-narrow" style="margin:0 auto;">
        <p class="article-cat" style="margin:20px 0 8px;"><?= e($catName ?? 'Guide') ?></p>
        <h1 style="font-size:clamp(1.9rem,4vw,2.8rem);"><?= e($post['title']) ?></h1>
        <p class="article-date" style="margin-bottom:26px;"><?= e(date('F j, Y', strtotime($post['published_at'] ?? $post['created_at']))) ?></p>
        <?php if ($post['featured_image']): ?>
        <img src="<?= e(img_url($post['featured_image'], 'posts')) ?>" alt="<?= e($post['title']) ?>" style="border-radius:var(--radius);margin-bottom:30px;" width="1200" height="750">
        <?php endif; ?>
        <div class="rich"><?= $post['content'] ?></div>
        <div style="margin-top:44px;padding:26px;background:var(--offwhite);border-radius:var(--radius);">
            <h3 class="mt-0">Questions about choosing a model?</h3>
            <p style="margin-bottom:16px;">Our team is happy to help you compare options — no pressure, no payment taken online.</p>
            <button type="button" class="btn btn-primary" data-enquire-general>Ask the GIO Team</button>
        </div>
    </article>

    <?php if ($more): ?>
    <section style="padding-bottom:90px;">
        <div class="section-head"><h2>Keep reading</h2></div>
        <div class="article-grid">
            <?php foreach ($more as $mp): ?>
            <a class="article-card" href="<?= e(site_url('blog/' . $mp['slug'])) ?>">
                <span class="article-img"><img src="<?= e(img_thumb_url($mp['featured_image'], 'posts')) ?>" alt="<?= e($mp['title']) ?>" loading="lazy" width="640" height="400"></span>
                <span class="article-body">
                    <h3><?= e($mp['title']) ?></h3>
                    <span class="article-date"><?= e(date('F j, Y', strtotime($mp['published_at'] ?? $mp['created_at']))) ?></span>
                </span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
