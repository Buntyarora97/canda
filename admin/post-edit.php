<?php
/** Admin: blog post editor. */
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

$id = (int)($_GET['id'] ?? 0);
$post = $id ? row('SELECT * FROM posts WHERE id = ?', [$id]) : null;
if ($id && !$post) { admin_flash('Post not found.', 'err'); redirect(site_url('admin/posts.php')); }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_guard();
    $title = trim((string)($_POST['title'] ?? ''));
    $slug = trim((string)($_POST['slug'] ?? '')) ?: slugify($title);
    $data = [
        'title' => $title,
        'slug' => $slug,
        'excerpt' => trim((string)($_POST['excerpt'] ?? '')) ?: null,
        'content' => (string)($_POST['content'] ?? ''),
        'seo_title' => trim((string)($_POST['seo_title'] ?? '')) ?: null,
        'seo_description' => trim((string)($_POST['seo_description'] ?? '')) ?: null,
        'is_published' => isset($_POST['is_published']) ? 1 : 0,
        'published_at' => trim((string)($_POST['published_at'] ?? '')) ?: date('Y-m-d H:i:s'),
    ];
    if ($title === '') $errors[] = 'Title is required.';
    if (val('SELECT id FROM posts WHERE slug = ? AND id <> ?', [$slug, $id])) $errors[] = 'Slug is already in use.';
    try { $img = admin_upload_image('featured_image', 'posts'); } catch (RuntimeException $ex) { $errors[] = $ex->getMessage(); $img = null; }

    if (!$errors) {
        if ($post) {
            if ($img) { admin_delete_upload($post['featured_image'], 'posts'); $data['featured_image'] = $img; }
            $set = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($data)));
            q("UPDATE posts SET $set WHERE id = :__id", $data + ['__id' => $id]);
        } else {
            if ($img) $data['featured_image'] = $img;
            $cols = implode(',', array_keys($data));
            q("INSERT INTO posts ($cols) VALUES (:" . implode(',:', array_keys($data)) . ")", $data);
            $id = (int) db()->lastInsertId();
        }
        q('DELETE FROM post_category_map WHERE post_id = ?', [$id]);
        foreach ((array)($_POST['categories'] ?? []) as $cid) q('INSERT IGNORE INTO post_category_map (post_id, category_id) VALUES (?,?)', [$id, (int)$cid]);
        $newCat = trim((string)($_POST['new_category'] ?? ''));
        if ($newCat !== '') {
            q('INSERT IGNORE INTO post_categories (name, slug) VALUES (?,?)', [$newCat, slugify($newCat)]);
            $cid = (int) val('SELECT id FROM post_categories WHERE slug = ?', [slugify($newCat)]);
            if ($cid) q('INSERT IGNORE INTO post_category_map (post_id, category_id) VALUES (?,?)', [$id, $cid]);
        }
        log_activity('post_save', $title);
        admin_flash('Post saved.');
        redirect(site_url('admin/post-edit.php?id=' . $id));
    }
    $post = array_merge($post ?? [], $data);
}

$cats = rows('SELECT * FROM post_categories ORDER BY name');
$sel = $id ? array_column(rows('SELECT category_id FROM post_category_map WHERE post_id = ?', [$id]), 'category_id') : [];

admin_head($post ? 'Edit post' : 'New post', 'posts.php');
?>
<?php foreach ($errors as $err): ?><div class="flash flash-err"><?= e($err) ?></div><?php endforeach; ?>
<form method="post" enctype="multipart/form-data" class="admin-form">
<?= csrf_field() ?>
<section class="panel">
    <div class="form-grid">
        <label class="span2">Title <input name="title" value="<?= e($post['title'] ?? '') ?>" required maxlength="190"></label>
        <label>Slug <input name="slug" value="<?= e($post['slug'] ?? '') ?>" maxlength="190" placeholder="auto if blank"></label>
        <label>Publish date <input type="datetime-local" name="published_at" value="<?= !empty($post['published_at']) ? e(date('Y-m-d\TH:i', strtotime($post['published_at']))) : e(date('Y-m-d\TH:i')) ?>"></label>
        <label class="span2">Excerpt <textarea name="excerpt" rows="2" maxlength="320"><?= e($post['excerpt'] ?? '') ?></textarea></label>
        <label class="span2">Content (HTML allowed — headings, lists, paragraphs)
            <textarea name="content" rows="16"><?= e($post['content'] ?? '') ?></textarea></label>
        <label class="span2">Featured image <input type="file" name="featured_image" accept="image/*"></label>
        <?php if (!empty($post['featured_image'])): ?><img src="<?= e(img_thumb_url($post['featured_image'], 'posts')) ?>" alt="" class="preview-img"><?php endif; ?>
        <div class="span2"><strong>Categories</strong>
            <?php foreach ($cats as $c): ?>
            <label class="check"><input type="checkbox" name="categories[]" value="<?= (int)$c['id'] ?>" <?= in_array($c['id'], $sel) ? 'checked' : '' ?>> <?= e($c['name']) ?></label>
            <?php endforeach; ?>
            <input name="new_category" placeholder="+ new category" maxlength="90" style="margin-top:8px">
        </div>
        <label class="span2">SEO title <input name="seo_title" value="<?= e($post['seo_title'] ?? '') ?>" maxlength="190"></label>
        <label class="span2">SEO description <textarea name="seo_description" rows="2" maxlength="320"><?= e($post['seo_description'] ?? '') ?></textarea></label>
        <label class="check span2"><input type="checkbox" name="is_published" <?= ($post['is_published'] ?? 1) ? 'checked' : '' ?>> Published</label>
    </div>
</section>
<div class="save-bar">
    <button class="btn btn-primary btn-lg">Save post</button>
    <a class="btn btn-ghost" href="<?= e(site_url('admin/posts.php')) ?>">Back</a>
</div>
</form>
<?php admin_foot(); ?>
