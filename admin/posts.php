<?php
/** Admin: blog posts list + delete. */
declare(strict_types=1);
require_once __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    admin_guard();
    if (($_POST['action'] ?? '') === 'delete') {
        $pid = (int)$_POST['id'];
        $img = val('SELECT featured_image FROM posts WHERE id = ?', [$pid]);
        admin_delete_upload($img, 'posts');
        q('DELETE FROM posts WHERE id = ?', [$pid]);
        log_activity('post_delete', "Post #$pid");
        admin_flash('Post deleted.');
    }
    redirect(site_url('admin/posts.php'));
}

$posts = rows('SELECT p.*, (SELECT GROUP_CONCAT(pc.name SEPARATOR ", ") FROM post_category_map m JOIN post_categories pc ON pc.id = m.category_id WHERE m.post_id = p.id) cats FROM posts p ORDER BY p.published_at DESC');

admin_head('Blog', 'posts.php');
?>
<div class="panel-head"><span class="muted"><?= count($posts) ?> posts</span>
    <a class="btn btn-primary" href="<?= e(site_url('admin/post-edit.php')) ?>">+ New post</a></div>
<section class="panel">
<table class="tbl">
    <thead><tr><th></th><th>Title</th><th>Categories</th><th>Published</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($posts as $post): ?>
    <tr>
        <td class="cell-img"><?php if ($post['featured_image']): ?><img src="<?= e(img_thumb_url($post['featured_image'], 'posts')) ?>" alt="" width="64" height="42" style="object-fit:cover"><?php endif; ?></td>
        <td><strong><?= e($post['title']) ?></strong><br><small class="muted">/blog/<?= e($post['slug']) ?></small></td>
        <td><small><?= e($post['cats'] ?? '—') ?></small></td>
        <td><?= $post['published_at'] ? e(date('M j, Y', strtotime($post['published_at']))) : '—' ?></td>
        <td><?= $post['is_published'] ? '<span class="status st-converted">Live</span>' : '<span class="status st-closed">Draft</span>' ?></td>
        <td class="cell-actions">
            <a class="btn btn-sm" href="<?= e(site_url('admin/post-edit.php?id=' . (int)$post['id'])) ?>">Edit</a>
            <a class="btn btn-sm btn-ghost" href="<?= e(site_url('blog/' . $post['slug'])) ?>" target="_blank" rel="noopener">View</a>
            <form method="post" class="inline" data-confirm="Delete this post?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$post['id'] ?>"><button class="btn btn-sm btn-danger">Delete</button></form>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</section>
<?php admin_foot(); ?>
