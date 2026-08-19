<?php
/**
 * JSON-LD structured data builders (schema.org).
 * Ratings/offers are only emitted when backed by real, current data.
 */
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function schema_print(array $schema): string
{
    return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
}

function schema_organization(): array
{
    return [
        '@context' => 'https://schema.org',
        '@type'    => 'Organization',
        'name'     => SITE_NAME,
        'url'      => SITE_URL,
        'logo'     => site_url('assets/images/gio-logo@2x.png'),
        'telephone' => setting('store_phone', '1-855-907-4211'),
        'email'    => setting('store_email', 'support@gioelectric.zendesk.com'),
        'address'  => [
            '@type' => 'PostalAddress',
            'streetAddress'   => setting('store_address', 'Unit 1 - 11400 Twigg Place'),
            'addressLocality' => 'Richmond',
            'addressRegion'   => 'BC',
            'addressCountry'  => 'CA',
        ],
    ];
}

function schema_website(): array
{
    return [
        '@context' => 'https://schema.org',
        '@type'    => 'WebSite',
        'name'     => SITE_NAME,
        'url'      => SITE_URL,
        'potentialAction' => [
            '@type'       => 'SearchAction',
            'target'      => ['@type' => 'EntryPoint', 'urlTemplate' => site_url('search?q={search_term_string}')],
            'query-input' => 'required name=search_term_string',
        ],
    ];
}

function schema_product(array $p, array $images): array
{
    $s = [
        '@context' => 'https://schema.org',
        '@type'    => 'Product',
        'name'     => $p['name'],
        'sku'      => $p['sku'],
        'url'      => product_url($p),
        'brand'    => ['@type' => 'Brand', 'name' => 'GIO Mobility'],
    ];
    if (!empty($p['short_description'])) {
        $s['description'] = $p['short_description'];
    }
    $imgs = [];
    foreach ($images as $img) {
        $imgs[] = img_url($img['file']);
    }
    if ($imgs) $s['image'] = $imgs;

    // Offer only when we have a verified price and a truthful availability.
    if ($p['show_price'] && $p['price'] !== null && $p['price'] !== '') {
        $avail = [
            'in_stock'     => 'https://schema.org/InStock',
            'pre_order'    => 'https://schema.org/PreOrder',
            'limited'      => 'https://schema.org/LimitedAvailability',
            'coming_soon'  => 'https://schema.org/PreOrder',
            'out_of_stock' => 'https://schema.org/OutOfStock',
        ][$p['stock_status']] ?? 'https://schema.org/InStock';
        $s['offers'] = [
            '@type'         => 'Offer',
            'priceCurrency' => 'CAD',
            'price'         => number_format((float)$p['price'], 2, '.', ''),
            'availability'  => $avail,
            'url'           => product_url($p),
        ];
    }
    return $s;
}

function schema_breadcrumbs(array $crumbs): array
{
    $items = [];
    $pos = 1;
    foreach ($crumbs as $c) {
        $item = ['@type' => 'ListItem', 'position' => $pos++, 'name' => $c['label']];
        if (!empty($c['url'])) $item['item'] = str_starts_with($c['url'], 'http') ? $c['url'] : site_url(ltrim($c['url'], '/'));
        $items[] = $item;
    }
    return ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items];
}

function schema_faq(array $faqs): array
{
    $ents = [];
    foreach ($faqs as $f) {
        $ents[] = [
            '@type' => 'Question',
            'name'  => $f['question'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => strip_tags($f['answer'])],
        ];
    }
    return ['@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $ents];
}

function schema_article(array $post): array
{
    return [
        '@context' => 'https://schema.org',
        '@type'    => 'Article',
        'headline' => $post['title'],
        'description' => $post['excerpt'] ?: excerpt($post['content']),
        'image'    => !empty($post['featured_image']) ? img_url($post['featured_image'], 'posts') : site_url('assets/images/og-default.jpg'),
        'datePublished' => date(DATE_ATOM, strtotime($post['published_at'] ?: $post['created_at'])),
        'dateModified'  => date(DATE_ATOM, strtotime($post['updated_at'] ?: $post['created_at'])),
        'author'   => ['@type' => 'Organization', 'name' => SITE_NAME, 'url' => SITE_URL],
        'publisher'=> schema_organization(),
        'mainEntityOfPage' => site_url('blog/' . $post['slug']),
    ];
}
