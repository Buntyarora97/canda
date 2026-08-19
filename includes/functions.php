<?php
/**
 * Shared helpers: escaping, formatting, settings, catalogue queries.
 */
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/security.php';

/* ------------------------------------------------------------ output ----- */
if (!function_exists('e')) {
    function e(?string $s): string
    {
        return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

/** Escape for use inside HTML attributes containing URLs. */
function eurl(?string $s): string
{
    $s = (string) $s;
    if ($s !== '' && !preg_match('#^(https?:)?//#i', $s) && $s[0] !== '/' && $s[0] !== '#'
        && !str_starts_with($s, 'tel:') && !str_starts_with($s, 'mailto:')) {
        return '#';
    }
    return e($s);
}

/** Format a price in Canadian dollars: $2,595 CAD */
function cad($amount): string
{
    if ($amount === null || $amount === '' || !is_numeric($amount)) {
        return '';
    }
    return '$' . number_format((float) $amount, fmod((float)$amount, 1.0) ? 2 : 0) . ' CAD';
}

function site_url(string $path = ''): string
{
    return SITE_URL . '/' . ltrim($path, '/');
}

function product_url(array $p): string
{
    return site_url('product/' . rawurlencode($p['slug']));
}

function category_url(array $c): string
{
    return site_url('category/' . rawurlencode($c['slug']));
}

function current_url(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    return site_url(ltrim($uri, '/'));
}

/* ------------------------------------------------------------ settings --- */
function setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (rows('SELECT setting_key, setting_value FROM settings') as $r) {
                $cache[$r['setting_key']] = $r['setting_value'];
            }
        } catch (Throwable $t) {
            $cache = [];
        }
    }
    return array_key_exists($key, $cache) ? (string) $cache[$key] : $default;
}

function setting_set(string $key, string $value): void
{
    q('INSERT INTO settings (setting_key, setting_value) VALUES (?,?)
       ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)', [$key, $value]);
}

/* ------------------------------------------------------------ images ----- */
/**
 * Build an <img> tag with responsive srcset. Uploaded images are stored as
 * name.webp / name.jpg plus name_thumb.webp / name_thumb.jpg when possible.
 */
function img_url(?string $file, string $bucket = 'products'): string
{
    if (!$file) {
        return site_url('assets/images/placeholder-product.svg');
    }
    return site_url('uploads/' . $bucket . '/' . ltrim($file, '/'));
}

function img_thumb_url(?string $file, string $bucket = 'products'): string
{
    if (!$file) {
        return site_url('assets/images/placeholder-product.svg');
    }
    $pi = pathinfo($file);
    $thumb = ($pi['dirname'] !== '.' ? $pi['dirname'] . '/' : '') . $pi['filename'] . '_thumb.' . ($pi['extension'] ?? 'jpg');
    $full = UPLOAD_DIR . '/' . $bucket . '/' . ltrim($thumb, '/');
    if (is_file($full)) {
        return site_url('uploads/' . $bucket . '/' . ltrim($thumb, '/'));
    }
    return img_url($file, $bucket);
}

/* ------------------------------------------------------------ catalogue -- */
const STOCK_BADGES = [
    'in_stock'     => 'IN STOCK',
    'pre_order'    => 'PRE-ORDER',
    'limited'      => 'LIMITED',
    'coming_soon'  => 'COMING SOON',
    'out_of_stock' => 'OUT OF STOCK',
];

function product_badge(array $p): string
{
    if (!empty($p['badge_override'])) {
        return strtoupper($p['badge_override']);
    }
    if (!empty($p['is_new_arrival'])) {
        return 'NEW';
    }
    if (!empty($p['is_best_seller'])) {
        return 'BEST SELLER';
    }
    return STOCK_BADGES[$p['stock_status']] ?? '';
}

function get_product_by_slug(string $slug): ?array
{
    return row('SELECT * FROM products WHERE slug = ? AND is_published = 1', [$slug]);
}

function get_product(int $id): ?array
{
    return row('SELECT * FROM products WHERE id = ?', [$id]);
}

function product_images(int $productId): array
{
    return rows('SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order, id', [$productId]);
}

function product_primary_image(int $productId): ?array
{
    $img = row('SELECT * FROM product_images WHERE product_id = ? ORDER BY is_featured DESC, sort_order, id LIMIT 1', [$productId]);
    if (!$img) {
        $img = row('SELECT i.* FROM product_images i JOIN product_variants v ON v.product_id = i.product_id
                    WHERE i.product_id = ? ORDER BY i.sort_order, i.id LIMIT 1', [$productId]);
    }
    return $img;
}

function product_variants(int $productId, string $type = 'colour'): array
{
    return rows('SELECT * FROM product_variants WHERE product_id = ? AND type = ? ORDER BY sort_order, id', [$productId, $type]);
}

/** All variants of any type (for preselecting colour pages). */
function product_variants_all(int $productId): array
{
    return rows('SELECT * FROM product_variants WHERE product_id = ? ORDER BY type, sort_order, id', [$productId]);
}

function product_specs(int $productId): array
{
    $specs = rows('SELECT * FROM product_specs WHERE product_id = ? ORDER BY spec_group, sort_order, id', [$productId]);
    $grouped = [];
    foreach ($specs as $s) {
        $grouped[$s['spec_group']][] = $s;
    }
    return $grouped;
}

function product_features(int $productId): array
{
    return rows('SELECT * FROM product_features WHERE product_id = ? ORDER BY sort_order, id', [$productId]);
}

function product_videos(int $productId): array
{
    return rows('SELECT * FROM product_videos WHERE product_id = ? ORDER BY sort_order, id', [$productId]);
}

function product_categories(int $productId): array
{
    return rows('SELECT c.* FROM categories c
                 JOIN product_categories pc ON pc.category_id = c.id
                 WHERE pc.product_id = ? AND c.is_active = 1 ORDER BY c.sort_order', [$productId]);
}

function product_faqs(int $productId): array
{
    return rows('SELECT * FROM product_faqs WHERE product_id = ? ORDER BY sort_order, id', [$productId]);
}

function product_related(int $productId, int $limit = 4): array
{
    $rel = rows('SELECT p.* FROM products p
                 JOIN product_related pr ON pr.related_product_id = p.id
                 WHERE pr.product_id = ? AND p.is_published = 1 ORDER BY p.sort_order LIMIT ' . (int)$limit, [$productId]);
    if (count($rel) >= $limit) {
        return $rel;
    }
    // Fall back to same-category products.
    $exclude = array_merge([$productId], array_column($rel, 'id'));
    $ph = implode(',', array_fill(0, count($exclude), '?'));
    $extra = rows("SELECT DISTINCT p.* FROM products p
                   JOIN product_categories pc ON pc.product_id = p.id
                   WHERE pc.category_id IN (SELECT category_id FROM product_categories WHERE product_id = ?)
                     AND p.is_published = 1 AND p.id NOT IN ($ph)
                   ORDER BY p.is_best_seller DESC, p.sort_order LIMIT " . (int)($limit - count($rel)),
        array_merge([$productId], $exclude));
    return array_merge($rel, $extra);
}

/** Key quick specs used on cards / comparison. */
function product_quick_specs(int $productId): array
{
    $p = get_product($productId);
    if (!$p) return [];
    $out = [];
    if ($p['wheel_config'])   $out['wheels']   = ucwords(str_replace('-', ' ', $p['wheel_config']));
    if ($p['range_km'])       $out['range']    = $p['range_km'] . ' km range';
    if ($p['top_speed_kmh'])  $out['speed']    = $p['top_speed_kmh'] . ' km/h';
    if ($p['capacity_kg'])    $out['capacity'] = $p['capacity_kg'] . ' kg capacity';
    return $out;
}

function list_products(array $opts = []): array
{
    $where  = ['p.is_published = 1'];
    $params = [];
    $join   = '';

    if (!empty($opts['category_id'])) {
        $join .= ' JOIN product_categories pc ON pc.product_id = p.id AND pc.category_id = ' . (int)$opts['category_id'];
    }
    if (!empty($opts['wheel'])) {
        $where[] = 'p.wheel_config = ?';
        $params[] = $opts['wheel'];
    }
    if (isset($opts['stock']) && $opts['stock'] !== '' && $opts['stock'] !== 'any') {
        $where[] = 'p.stock_status = ?';
        $params[] = $opts['stock'];
    }
    if (!empty($opts['min_price'])) {
        $where[] = 'p.price >= ?';
        $params[] = (float)$opts['min_price'];
    }
    if (!empty($opts['max_price'])) {
        $where[] = 'p.price <= ?';
        $params[] = (float)$opts['max_price'];
    }
    if (!empty($opts['min_range'])) {
        $where[] = 'p.range_km >= ?';
        $params[] = (int)$opts['min_range'];
    }
    if (!empty($opts['min_capacity'])) {
        $where[] = 'p.capacity_kg >= ?';
        $params[] = (int)$opts['min_capacity'];
    }
    if (!empty($opts['search'])) {
        $where[] = '(p.name LIKE ? OR p.sku LIKE ? OR p.tagline LIKE ? OR p.short_description LIKE ? OR p.keywords LIKE ?)';
        $t = '%' . $opts['search'] . '%';
        array_push($params, $t, $t, $t, $t, $t);
    }
    if (!empty($opts['best_seller'])) $where[] = 'p.is_best_seller = 1';
    if (!empty($opts['new_arrival'])) $where[] = 'p.is_new_arrival = 1';
    if (!empty($opts['featured']))    $where[] = 'p.is_featured = 1';
    if (!empty($opts['ids'])) {
        $ids = array_map('intval', (array)$opts['ids']);
        $where[] = 'p.id IN (' . implode(',', $ids) . ')';
    }

    $sortMap = [
        'featured'    => 'p.is_featured DESC, p.sort_order ASC, p.id DESC',
        'best'        => 'p.is_best_seller DESC, p.sort_order ASC',
        'newest'      => 'p.created_at DESC',
        'price_asc'   => 'p.price IS NULL, p.price ASC',
        'price_desc'  => 'p.price IS NULL, p.price DESC',
        'alpha'       => 'p.name ASC',
    ];
    $orderBy = $sortMap[$opts['sort'] ?? 'featured'] ?? $sortMap['featured'];

    $limit  = max(1, (int)($opts['limit'] ?? 24));
    $page   = max(1, (int)($opts['page'] ?? 1));
    $offset = ($page - 1) * $limit;

    $sql = 'SELECT DISTINCT p.* FROM products p ' . $join . ' WHERE ' . implode(' AND ', $where)
         . ' ORDER BY ' . $orderBy . ' LIMIT ' . $limit . ' OFFSET ' . $offset;
    return rows($sql, $params);
}

function count_products(array $opts = []): int
{
    $where  = ['p.is_published = 1'];
    $params = [];
    $join   = '';
    if (!empty($opts['category_id'])) {
        $join .= ' JOIN product_categories pc ON pc.product_id = p.id AND pc.category_id = ' . (int)$opts['category_id'];
    }
    if (!empty($opts['wheel']))     { $where[] = 'p.wheel_config = ?';  $params[] = $opts['wheel']; }
    if (isset($opts['stock']) && $opts['stock'] !== '' && $opts['stock'] !== 'any') { $where[] = 'p.stock_status = ?'; $params[] = $opts['stock']; }
    if (!empty($opts['min_price'])) { $where[] = 'p.price >= ?';        $params[] = (float)$opts['min_price']; }
    if (!empty($opts['max_price'])) { $where[] = 'p.price <= ?';        $params[] = (float)$opts['max_price']; }
    if (!empty($opts['min_range'])) { $where[] = 'p.range_km >= ?';     $params[] = (int)$opts['min_range']; }
    if (!empty($opts['min_capacity'])) { $where[] = 'p.capacity_kg >= ?'; $params[] = (int)$opts['min_capacity']; }
    if (!empty($opts['search'])) {
        $where[] = '(p.name LIKE ? OR p.sku LIKE ? OR p.tagline LIKE ? OR p.short_description LIKE ? OR p.keywords LIKE ?)';
        $t = '%' . $opts['search'] . '%';
        array_push($params, $t, $t, $t, $t, $t);
    }
    if (!empty($opts['best_seller'])) $where[] = 'p.is_best_seller = 1';
    if (!empty($opts['new_arrival'])) $where[] = 'p.is_new_arrival = 1';
    return (int) val('SELECT COUNT(DISTINCT p.id) FROM products p ' . $join . ' WHERE ' . implode(' AND ', $where), $params);
}

function list_categories(bool $activeOnly = true): array
{
    return rows('SELECT * FROM categories ' . ($activeOnly ? 'WHERE is_active = 1 ' : '') . 'ORDER BY sort_order, name');
}

function get_category_by_slug(string $slug): ?array
{
    return row('SELECT * FROM categories WHERE slug = ? AND is_active = 1', [$slug]);
}

function active_banners(): array
{
    return rows("SELECT * FROM banners WHERE is_active = 1
                 AND (schedule_start IS NULL OR schedule_start <= NOW())
                 AND (schedule_end IS NULL OR schedule_end >= NOW())
                 ORDER BY sort_order, id");
}

function provinces(): array
{
    return ['AB'=>'Alberta','BC'=>'British Columbia','MB'=>'Manitoba','NB'=>'New Brunswick',
            'NL'=>'Newfoundland and Labrador','NS'=>'Nova Scotia','NT'=>'Northwest Territories',
            'NU'=>'Nunavut','ON'=>'Ontario','PE'=>'Prince Edward Island','QC'=>'Quebec',
            'SK'=>'Saskatchewan','YT'=>'Yukon'];
}

/* ------------------------------------------------------------ admin ------ */
function admin_logged_in(): bool
{
    secure_session();
    return !empty($_SESSION['admin_id']);
}

function require_admin(): void
{
    if (!admin_logged_in()) {
        header('Location: ' . site_url('admin/login.php'));
        exit;
    }
}

function current_admin(): ?array
{
    if (!admin_logged_in()) return null;
    return row('SELECT id, name, email, role, last_login_at FROM admins WHERE id = ?', [$_SESSION['admin_id']]);
}

function log_activity(string $action, string $details = ''): void
{
    try {
        q('INSERT INTO activity_logs (admin_id, action, details, ip_hash, created_at) VALUES (?,?,?,?,NOW())',
            [$_SESSION['admin_id'] ?? null, $action, mb_substr($details, 0, 500), hash_ip(client_ip())]);
    } catch (Throwable $t) { /* never break the request for logging */ }
}

/* ------------------------------------------------------------ misc ------- */
function excerpt(string $html, int $len = 160): string
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($html)));
    return mb_strlen($text) > $len ? mb_substr($text, 0, $len - 1) . '…' : $text;
}

function slugify(string $s): string
{
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-') ?: 'item';
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function json_response(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function upload_thumb_exists(string $file, string $bucket): bool
{
    $pi = pathinfo($file);
    $thumb = ($pi['dirname'] !== '.' ? $pi['dirname'] . '/' : '') . $pi['filename'] . '_thumb.' . ($pi['extension'] ?? 'jpg');
    return is_file(UPLOAD_DIR . '/' . $bucket . '/' . $thumb);
}
