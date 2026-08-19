<?php
/**
 * PHP's built-in server does not read .htaccess. This tiny development router
 * mirrors the project's Apache pretty URLs without changing Hostinger setup.
 */
declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = ltrim(rawurldecode($uri), '/');
$root = __DIR__;

if ($path !== '' && is_file($root . '/' . $path)) {
    return false;
}

$routes = [
    '#^product/([a-z0-9-]+)/?$#i' => ['product.php', 'slug'],
    '#^category/([a-z0-9-]+)/?$#i' => ['category.php', 'slug'],
    '#^blog/([a-z0-9-]+)/?$#i' => ['article.php', 'slug'],
    '#^shop/?$#i' => ['shop.php', null],
    '#^best-sellers/?$#i' => ['shop.php', 'badge', 'best'],
    '#^new-arrivals/?$#i' => ['shop.php', 'badge', 'new'],
    '#^search/?$#i' => ['search.php', null],
    '#^compare/?$#i' => ['compare.php', null],
    '#^wishlist/?$#i' => ['wishlist.php', null],
    '#^about/?$#i' => ['about.php', null],
    '#^why-gio/?$#i' => ['why-gio.php', null],
    '#^stories/?$#i' => ['stories.php', null],
    '#^contact/?$#i' => ['contact.php', null],
    '#^blog/?$#i' => ['blog.php', null],
    '#^support/?$#i' => ['support/index.php', null],
    '#^support/(faqs|ordering-guide|warranty|shipping|manuals)/?$#i' => ['support/$1.php', null],
    '#^privacy/?$#i' => ['privacy.php', null],
    '#^terms/?$#i' => ['terms.php', null],
    '#^accessibility/?$#i' => ['accessibility.php', null],
    '#^sitemap\.xml$#i' => ['sitemap.php', null],
];

foreach ($routes as $pattern => $route) {
    if (!preg_match($pattern, $path, $matches)) {
        continue;
    }
    $target = $route[0];
    if (str_contains($target, '$1')) {
        $target = str_replace('$1', $matches[1], $target);
    }
    if ($route[1] !== null) {
        $_GET[$route[1]] = $route[2] ?? $matches[1];
    }
    require $root . '/' . $target;
    return true;
}

if ($path === '') {
    require $root . '/index.php';
    return true;
}

http_response_code(404);
require $root . '/404.php';