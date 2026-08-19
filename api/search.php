<?php
/** Instant search suggestions JSON. */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';

$q = trim((string)($_GET['q'] ?? ''));
if (mb_strlen($q) < 2 || mb_strlen($q) > 80) {
    json_response(['results' => []]);
}

$like = '%' . $q . '%';
$results = rows(
    'SELECT DISTINCT p.id, p.name, p.slug, p.price, p.show_price FROM products p
     LEFT JOIN product_categories pc ON pc.product_id = p.id
     LEFT JOIN categories c ON c.id = pc.category_id
     WHERE p.is_published = 1
       AND (p.name LIKE ? OR p.sku LIKE ? OR p.tagline LIKE ? OR p.keywords LIKE ? OR c.name LIKE ?
            OR p.id IN (SELECT product_id FROM product_features WHERE feature LIKE ?))
     ORDER BY p.is_best_seller DESC, p.sort_order LIMIT 8',
    [$like, $like, $like, $like, $like, $like]
);

$out = [];
foreach ($results as $p) {
    $img = product_primary_image((int) $p['id']);
    $out[] = [
        'name'        => $p['name'],
        'url'         => site_url('product/' . $p['slug']),
        'thumb'       => $img ? img_thumb_url($img['file']) : site_url('assets/images/placeholder-product.svg'),
        'price_label' => ($p['show_price'] && $p['price'] !== null) ? cad($p['price']) : 'Pricing on enquiry',
    ];
}
json_response(['results' => $out]);
