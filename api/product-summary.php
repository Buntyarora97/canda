<?php
/** Product summary JSON for the enquiry modal (server-side source of truth). */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';

$id = (int)($_GET['id'] ?? 0);
$p  = $id ? row('SELECT * FROM products WHERE id = ? AND is_published = 1', [$id]) : null;
if (!$p) {
    json_response(['ok' => false], 404);
}
$img   = product_primary_image($id);
$colour = row('SELECT name FROM product_variants WHERE product_id = ? AND type = "colour" ORDER BY is_default DESC, sort_order LIMIT 1', [$id]);

json_response([
    'ok' => true,
    'product' => [
        'id'             => (int) $p['id'],
        'name'           => $p['name'],
        'sku'            => $p['sku'],
        'thumb'          => $img ? img_thumb_url($img['file']) : site_url('assets/images/placeholder-product.svg'),
        'price_label'    => ($p['show_price'] && $p['price'] !== null) ? cad($p['price']) : 'Pricing on enquiry',
        'default_colour' => $colour['name'] ?? null,
    ],
]);
