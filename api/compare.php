<?php
/** Compare data JSON for up to 3 product IDs. */
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/functions.php';

$ids = array_values(array_filter(array_map('intval', explode(',', (string)($_GET['ids'] ?? '')))));
$ids = array_slice($ids, 0, 3);
if (!$ids) {
    json_response(['products' => []]);
}

$products = list_products(['ids' => $ids, 'limit' => 3]);
// Preserve the requested order.
usort($products, fn($a, $b) => array_search($a['id'], $ids) <=> array_search($b['id'], $ids));

$out = [];
foreach ($products as $p) {
    $img = product_primary_image((int) $p['id']);
    $battery = row('SELECT spec_value FROM product_specs WHERE product_id = ? AND spec_group = "Battery" AND spec_name = "Battery" LIMIT 1', [$p['id']]);
    $feats = array_column(product_features((int) $p['id']), 'feature');
    $dims  = rows('SELECT spec_name, spec_value FROM product_specs WHERE product_id = ? AND spec_group = "Dimensions" ORDER BY sort_order LIMIT 4', [$p['id']]);
    $out[] = [
        'id'          => (int) $p['id'],
        'name'        => $p['name'],
        'url'         => site_url('product/' . $p['slug']),
        'thumb'       => $img ? img_thumb_url($img['file']) : site_url('assets/images/placeholder-product.svg'),
        'price_label' => ($p['show_price'] && $p['price'] !== null) ? cad($p['price']) : 'On enquiry',
        'wheels'      => $p['wheel_config'] ? ucwords(str_replace('-', ' ', $p['wheel_config'])) : null,
        'range'       => $p['range_km'] ? $p['range_km'] . ' km' : null,
        'speed'       => $p['top_speed_kmh'] ? 'Up to ' . $p['top_speed_kmh'] . ' km/h' : null,
        'capacity'    => $p['capacity_kg'] ? $p['capacity_kg'] . ' kg' : null,
        'battery'     => $battery['spec_value'] ?? null,
        'features'    => $feats,
        'dimensions'  => $dims,
    ];
}
json_response(['products' => $out]);
