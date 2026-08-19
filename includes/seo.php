<?php
/**
 * SEO helpers: per-page meta, Open Graph, Twitter cards, canonical.
 * Pages set $GLOBALS['seo'] before including header.php.
 */
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function seo_set(array $meta): void
{
    $GLOBALS['seo'] = array_merge($GLOBALS['seo'] ?? [], $meta);
}

function seo_get(): array
{
    $seo = $GLOBALS['seo'] ?? [];
    $defaults = [
        'title'       => SITE_NAME . ' — Premium Electric Mobility Scooters',
        'description' => 'Shop Canadian-designed electric mobility scooters: 3-wheel, 4-wheel, all-season enclosed models and mobility walkers. Stylish, dependable mobility for everyday independence.',
        'canonical'   => current_url(),
        'og_image'    => site_url('assets/images/og-default.jpg'),
        'og_type'     => 'website',
        'robots'      => 'index,follow',
    ];
    // Allow DB overrides via seo_meta table (keyed by page_key).
    if (!empty($seo['page_key'])) {
        try {
            $m = row('SELECT * FROM seo_meta WHERE page_key = ?', [$seo['page_key']]);
            if ($m) {
                if (!empty($m['title']))       $defaults['title'] = $m['title'];
                if (!empty($m['description'])) $defaults['description'] = $m['description'];
                if (!empty($m['og_image']))    $defaults['og_image'] = $m['og_image'];
                if (!empty($m['canonical']))   $defaults['canonical'] = $m['canonical'];
            }
        } catch (Throwable $t) { /* table may not exist pre-install */ }
    }
    return array_merge($defaults, array_filter($seo, fn($v) => $v !== null && $v !== '' && $v !== []));
}

function render_seo_tags(): string
{
    $s = seo_get();
    $out = [];
    $out[] = '<title>' . e($s['title']) . '</title>';
    $out[] = '<meta name="description" content="' . e($s['description']) . '">';
    $out[] = '<meta name="robots" content="' . e($s['robots']) . '">';
    $out[] = '<link rel="canonical" href="' . e($s['canonical']) . '">';
    $out[] = '<meta property="og:site_name" content="' . e(SITE_NAME) . '">';
    $out[] = '<meta property="og:type" content="' . e($s['og_type']) . '">';
    $out[] = '<meta property="og:title" content="' . e($s['title']) . '">';
    $out[] = '<meta property="og:description" content="' . e($s['description']) . '">';
    $out[] = '<meta property="og:url" content="' . e($s['canonical']) . '">';
    $out[] = '<meta property="og:image" content="' . e($s['og_image']) . '">';
    $out[] = '<meta name="twitter:card" content="summary_large_image">';
    $out[] = '<meta name="twitter:title" content="' . e($s['title']) . '">';
    $out[] = '<meta name="twitter:description" content="' . e($s['description']) . '">';
    $out[] = '<meta name="twitter:image" content="' . e($s['og_image']) . '">';
    return implode("\n    ", $out);
}

function render_breadcrumbs(array $crumbs): string
{
    // $crumbs: [ ['label'=>..., 'url'=>...|null], ... ]
    $html = '<nav class="breadcrumbs" aria-label="Breadcrumb"><ol>';
    $last = count($crumbs) - 1;
    foreach ($crumbs as $i => $c) {
        $html .= '<li' . ($i === $last ? ' aria-current="page"' : '') . '>';
        if (!empty($c['url']) && $i !== $last) {
            $html .= '<a href="' . eurl($c['url']) . '">' . e($c['label']) . '</a>';
        } else {
            $html .= '<span>' . e($c['label']) . '</span>';
        }
        $html .= '</li>';
    }
    return $html . '</ol></nav>';
}
