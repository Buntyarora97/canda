<?php
/**
 * Global header: announcement bar, sticky nav, mega menu, mobile menu.
 * Expects functions.php, seo.php already loaded by the page.
 */
declare(strict_types=1);

$seo = seo_get();
$navCategories = list_categories();
$announcement  = setting('announcement_text', 'Designed for Canadian Mobility • Product Support Available');
$storePhone    = setting('store_phone', '1-855-907-4211');
$storePhoneTel = preg_replace('/[^0-9+]/', '', $storePhone);
$headerSchemas = $GLOBALS['schemas'] ?? [];
?><!DOCTYPE html>
<html lang="en-CA">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#111315">
    <meta name="gio-base-url" content="<?= e(SITE_URL) ?>">
    <?= render_seo_tags() ?>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= e(site_url('assets/images/favicon.png')) ?>">
    <link rel="apple-touch-icon" href="<?= e(site_url('assets/images/apple-touch-icon.png')) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(site_url('assets/css/style.css')) ?>?v=1.1.0">
    <?php if (!empty($seo['hero_preload'])): ?>
    <link rel="preload" as="image" href="<?= e($seo['hero_preload']) ?>" fetchpriority="high">
    <?php endif; ?>
    <?= schema_print(schema_organization()) ?>
    <?= schema_print(schema_website()) ?>
    <?php foreach ($headerSchemas as $sc) echo schema_print($sc); ?>
    <script>window.GIO_BASE_URL = <?= json_encode(rtrim(SITE_URL, '/'), JSON_UNESCAPED_SLASHES) ?>; window.gioUrl = function (path) { return (window.GIO_BASE_URL ? window.GIO_BASE_URL + '/' : '/') + String(path).replace(/^\/+/, ''); };</script>
</head>
<body class="<?= e($GLOBALS['body_class'] ?? '') ?>">
<a class="skip-link" href="#main">Skip to content</a>

<div class="announcement" role="region" aria-label="Announcement">
    <span class="announcement-pulse" aria-hidden="true"></span>
    <p><?= e($announcement) ?></p>
</div>

<header class="site-header" id="siteHeader">
    <div class="header-inner container">
        <button class="nav-toggle" id="navToggle" aria-label="Open menu" aria-expanded="false" aria-controls="mobileMenu">
            <span></span><span></span><span></span>
        </button>

        <a class="brand" href="<?= e(site_url()) ?>" aria-label="GIO Mobility Canada — home">
            <img class="brand-logo-voltiva" src="<?= e(site_url('assets/images/voltiva-logo.png')) ?>"
                 srcset="<?= e(site_url('assets/images/voltiva-logo.png')) ?> 1x, <?= e(site_url('assets/images/voltiva-logo@2x.png')) ?> 2x"
                 alt="Voltiva Electric Mobility" width="110" height="83">
            <span class="brand-tag">ELECTRIC&nbsp;MOBILITY</span>
        </a>

        <nav class="main-nav" aria-label="Primary">
            <ul>
                <li class="has-mega">
                    <a href="<?= e(site_url('shop')) ?>" aria-haspopup="true">Shop <svg class="chev" width="10" height="6" viewBox="0 0 10 6" aria-hidden="true"><path d="M1 1l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></a>
                    <div class="mega" role="menu">
                        <div class="mega-grid">
                            <a class="mega-link" href="<?= e(site_url('shop')) ?>">
                                <span class="mega-title">All Mobility Products</span>
                                <span class="mega-sub">Browse the full lineup</span>
                            </a>
                            <?php foreach ($navCategories as $c): ?>
                            <a class="mega-link" href="<?= e(category_url($c)) ?>">
                                <span class="mega-title"><?= e($c['name']) ?></span>
                                <span class="mega-sub"><?= e($c['menu_label'] ?? '') ?></span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </li>
                <li><a href="<?= e(site_url('best-sellers')) ?>">Best Sellers</a></li>
                <li><a href="<?= e(site_url('new-arrivals')) ?>">New Arrivals</a></li>
                <li><a href="<?= e(site_url('why-gio')) ?>">Why GIO</a></li>
                <li><a href="<?= e(site_url('stories')) ?>">Customer Stories</a></li>
                <li class="has-mega">
                    <a href="<?= e(site_url('support')) ?>" aria-haspopup="true">Support <svg class="chev" width="10" height="6" viewBox="0 0 10 6" aria-hidden="true"><path d="M1 1l4 4 4-4" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg></a>
                    <div class="mega mega-slim" role="menu">
                        <div class="mega-grid">
                            <a class="mega-link" href="<?= e(site_url('support/faqs')) ?>"><span class="mega-title">FAQs</span></a>
                            <a class="mega-link" href="<?= e(site_url('support/ordering-guide')) ?>"><span class="mega-title">Ordering Guide</span></a>
                            <a class="mega-link" href="<?= e(site_url('support/manuals')) ?>"><span class="mega-title">Product Manuals</span></a>
                            <a class="mega-link" href="<?= e(site_url('support/warranty')) ?>"><span class="mega-title">Warranty</span></a>
                            <a class="mega-link" href="<?= e(site_url('support/shipping')) ?>"><span class="mega-title">Shipping</span></a>
                            <a class="mega-link" href="<?= e(site_url('contact')) ?>"><span class="mega-title">Contact Support</span></a>
                        </div>
                    </div>
                </li>
                <li><a href="<?= e(site_url('about')) ?>">About</a></li>
                <li><a href="<?= e(site_url('blog')) ?>">Blog</a></li>
            </ul>
        </nav>

        <div class="header-actions">
            <button class="icon-btn" id="searchOpen" aria-label="Search products">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/><path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </button>
            <a class="icon-btn" href="<?= e(site_url('wishlist')) ?>" aria-label="Wishlist">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 20s-7.2-4.6-9.2-9C1.4 8 3 5 6.2 5c2 0 3.4 1.1 4.1 2.3h1.4C12.4 6.1 13.8 5 15.8 5 19 5 20.6 8 21.2 11c-2 4.4-9.2 9-9.2 9z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                <span class="count-badge" id="wishlistCount" hidden>0</span>
            </a>
            <button class="icon-btn" data-enquire-general aria-label="Make an enquiry">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h16v11H9l-5 4V5z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
            </button>
            <a class="btn btn-primary btn-sm header-cta" href="<?= e(site_url('shop')) ?>">Shop Mobility</a>
        </div>
    </div>
</header>

<!-- Search overlay -->
<div class="search-overlay" id="searchOverlay" hidden>
    <div class="search-panel" role="dialog" aria-modal="true" aria-label="Search">
        <form class="search-form" action="<?= e(site_url('search')) ?>" method="get" role="search">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8"/><path d="M20 20l-3.5-3.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            <input type="search" id="searchInput" name="q" placeholder="Search scooters, walkers, parts…" autocomplete="off" aria-label="Search products">
            <button type="button" class="icon-btn" id="searchClose" aria-label="Close search">&times;</button>
        </form>
        <div class="search-suggestions" id="searchSuggestions" role="listbox" aria-label="Search suggestions"></div>
    </div>
</div>

<!-- Mobile menu -->
<div class="mobile-menu" id="mobileMenu" hidden>
    <nav aria-label="Mobile">
        <ul class="mobile-nav">
            <li><a href="<?= e(site_url('shop')) ?>">Shop All</a></li>
            <?php foreach ($navCategories as $c): ?>
            <li><a href="<?= e(category_url($c)) ?>"><?= e($c['name']) ?></a></li>
            <?php endforeach; ?>
            <li><a href="<?= e(site_url('best-sellers')) ?>">Best Sellers</a></li>
            <li><a href="<?= e(site_url('new-arrivals')) ?>">New Arrivals</a></li>
            <li><a href="<?= e(site_url('why-gio')) ?>">Why GIO</a></li>
            <li><a href="<?= e(site_url('stories')) ?>">Customer Stories</a></li>
            <li><a href="<?= e(site_url('support')) ?>">Support</a></li>
            <li><a href="<?= e(site_url('about')) ?>">About</a></li>
            <li><a href="<?= e(site_url('blog')) ?>">Blog</a></li>
            <li><a href="<?= e(site_url('contact')) ?>">Contact</a></li>
        </ul>
        <div class="mobile-menu-foot">
            <a class="btn btn-outline btn-block" href="tel:<?= e($storePhoneTel) ?>">Call <?= e($storePhone) ?></a>
        </div>
    </nav>
</div>

<main id="main">
