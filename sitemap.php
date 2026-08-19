<?php
/** Dynamic XML sitemap */
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/xml; charset=utf-8');

$urls = [
    ['', '1.0', 'weekly'],
    ['shop', '0.9', 'daily'],
    ['best-sellers', '0.8', 'weekly'],
    ['new-arrivals', '0.8', 'weekly'],
    ['compare', '0.5', 'monthly'],
    ['about', '0.6', 'monthly'],
    ['why-gio', '0.6', 'monthly'],
    ['stories', '0.6', 'weekly'],
    ['contact', '0.6', 'monthly'],
    ['blog', '0.7', 'weekly'],
    ['support', '0.6', 'monthly'],
    ['support/faqs', '0.6', 'monthly'],
    ['support/ordering-guide', '0.6', 'monthly'],
    ['support/warranty', '0.6', 'monthly'],
    ['support/shipping', '0.6', 'monthly'],
    ['support/manuals', '0.6', 'monthly'],
    ['privacy', '0.3', 'yearly'],
    ['terms', '0.3', 'yearly'],
    ['accessibility', '0.3', 'yearly'],
];

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($urls as [$path, $pri, $freq]): ?>
  <url><loc><?= e(site_url($path)) ?></loc><changefreq><?= $freq ?></changefreq><priority><?= $pri ?></priority></url>
<?php endforeach; ?>
<?php foreach (rows('SELECT slug, updated_at FROM products WHERE is_published = 1') as $p): ?>
  <url><loc><?= e(site_url('product/' . $p['slug'])) ?></loc><lastmod><?= e(date('Y-m-d', strtotime($p['updated_at']))) ?></lastmod><changefreq>weekly</changefreq><priority>0.9</priority></url>
<?php endforeach; ?>
<?php foreach (rows('SELECT slug FROM categories WHERE is_active = 1') as $c): ?>
  <url><loc><?= e(site_url('category/' . $c['slug'])) ?></loc><changefreq>weekly</changefreq><priority>0.8</priority></url>
<?php endforeach; ?>
<?php foreach (rows('SELECT slug, updated_at FROM posts WHERE is_published = 1') as $post): ?>
  <url><loc><?= e(site_url('blog/' . $post['slug'])) ?></loc><lastmod><?= e(date('Y-m-d', strtotime($post['updated_at']))) ?></lastmod><changefreq>monthly</changefreq><priority>0.6</priority></url>
<?php endforeach; ?>
</urlset>
