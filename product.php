<?php
/** Product detail page */
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/seo.php';
require_once __DIR__ . '/includes/schema.php';

$slug = strtolower(trim((string)($_GET['slug'] ?? '')));
$p    = $slug ? get_product_by_slug($slug) : null;
if (!$p) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

q('UPDATE products SET views = views + 1 WHERE id = ?', [$p['id']]);

$images    = product_images((int)$p['id']);
$variants  = product_variants_all((int)$p['id']);
$colours   = array_values(array_filter($variants, fn($v) => $v['type'] === 'colour'));
$options   = array_values(array_filter($variants, fn($v) => $v['type'] === 'option'));
$specs     = product_specs((int)$p['id']);
$features  = product_features((int)$p['id']);
$videos    = product_videos((int)$p['id']);
$cats      = product_categories((int)$p['id']);
$faqs      = product_faqs((int)$p['id']);
$manuals   = rows('SELECT * FROM manuals WHERE product_id = ? ORDER BY published_at DESC', [$p['id']]);
$related   = product_related((int)$p['id']);
$badge     = product_badge($p);
$qs        = product_quick_specs((int)$p['id']);
$gallery   = array_merge($images, array_map(fn($v) => ['file' => null, 'alt' => $v['title'] ?? 'Video', 'video' => $v], $videos));

$mainImg = $images[0]['file'] ?? null;

seo_set([
    'title' => $p['seo_title'] ?: ($p['name'] . ' | ' . SITE_NAME),
    'description' => $p['seo_description'] ?: excerpt($p['short_description'] ?: $p['long_description'] ?: ''),
    'canonical' => product_url($p),
    'og_image' => img_url($mainImg),
    'og_type' => 'product',
]);
$crumbs = [['label' => 'Home', 'url' => site_url()], ['label' => 'Shop', 'url' => site_url('shop')]];
if ($cats) $crumbs[] = ['label' => $cats[0]['name'], 'url' => category_url($cats[0])];
$crumbs[] = ['label' => $p['name'], 'url' => null];
$GLOBALS['schemas'][] = schema_product($p, $images);
$GLOBALS['schemas'][] = schema_breadcrumbs($crumbs);
if ($faqs) $GLOBALS['schemas'][] = schema_faq($faqs);
$GLOBALS['page_js'] = 'product.js';
$GLOBALS['body_class'] = 'pdp';
require __DIR__ . '/includes/header.php';

$firstColour = $colours ? ($colours[array_search(1, array_column($colours, 'is_default'))] ?? $colours[0]) : null;
$canBuy = !in_array($p['stock_status'], ['out_of_stock', 'coming_soon'], true);
?>

<div class="container">
    <?= render_breadcrumbs($crumbs) ?>

    <div class="pdp-layout">
        <!-- ===================== GALLERY ===================== -->
        <div class="pdp-gallery" data-gallery>
            <?php if (count($gallery) > 1): ?>
            <div class="pdp-thumbs" role="tablist" aria-label="Product images">
                <?php foreach ($gallery as $i => $g): ?>
                <button class="pdp-thumb <?= $i === 0 ? 'active' : '' ?>" data-gallery-thumb="<?= $i ?>" role="tab" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>" aria-label="Image <?= $i + 1 ?>">
                    <?php if (!empty($g['video'])): ?>
                    <svg viewBox="0 0 24 24" width="26" height="26" fill="none" style="margin:auto"><circle cx="12" cy="12" r="10" stroke="#70747A" stroke-width="1.6"/><path d="M10 8.5l5 3.5-5 3.5v-7z" fill="#70747A"/></svg>
                    <?php else: ?>
                    <img src="<?= e(img_thumb_url($g['file'])) ?>" alt="" loading="lazy">
                    <?php endif; ?>
                </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="pdp-main" id="pdpMain">
                <?php if ($badge): ?><span class="card-badge pdp-badge badge-<?= e(slugify($badge)) ?>"><?= e($badge) ?></span><?php endif; ?>
                <img id="pdpMainImg" src="<?= e(img_url($mainImg)) ?>" alt="<?= e($images[0]['alt'] ?? $p['name']) ?>" width="1000" height="1000" fetchpriority="high">
                <div class="pdp-zoom" id="pdpZoom" aria-hidden="true"></div>
                <button class="pdp-expand" id="pdpExpand" aria-label="Open fullscreen gallery">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M9 4H4v5m11-5h5v5M9 20H4v-5m11 5h5v-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                </button>
                <span class="pdp-counter" id="pdpCounter">1 / <?= count($gallery) ?></span>
            </div>
        </div>

        <!-- ===================== INFO ===================== -->
        <div class="pdp-info">
            <?php if ($badge): ?><span class="card-badge badge-<?= e(slugify($badge)) ?>"><?= e($badge) ?></span><?php endif; ?>
            <h1 class="pdp-title"><?= e($p['name']) ?></h1>
            <p class="pdp-sku">Model <?= e($p['sku']) ?></p>
            <?php if ($p['tagline']): ?><p class="pdp-tagline"><?= e($p['tagline']) ?></p><?php endif; ?>

            <div class="pdp-price-row">
                <?php if ($p['show_price'] && $p['price'] !== null): ?>
                <span class="pdp-price"><?= e(cad($p['price'])) ?></span>
                <?php if ($p['compare_price'] && $p['compare_price'] > $p['price']): ?>
                <s class="pdp-compare"><?= e(cad($p['compare_price'])) ?></s>
                <span class="pdp-save">Save <?= e(cad($p['compare_price'] - $p['price'])) ?></span>
                <?php endif; ?>
                <?php else: ?>
                <span class="pdp-price" style="font-size:1.1rem;color:var(--brand);">Pricing confirmed on enquiry</span>
                <?php endif; ?>
            </div>

            <p class="pdp-availability">
                <span class="stock-dot" style="background:<?= $p['stock_status'] === 'in_stock' ? '#2FA45B' : ($p['stock_status'] === 'out_of_stock' ? '#70747A' : '#D9A02B') ?>"></span>
                <?= e($p['availability_text'] ?: (STOCK_BADGES[$p['stock_status']] ?? '')) ?>
            </p>

            <?php if ($colours): ?>
            <div class="pdp-block">
                <span class="block-label">Colour — <b id="colourName"><?= e($firstColour['name'] ?? '') ?></b></span>
                <div class="colour-select" role="radiogroup" aria-label="Colour">
                    <?php foreach ($colours as $i => $c): ?>
                    <button type="button" class="colour-option <?= $i === 0 ? 'active' : '' ?>" data-colour="<?= e($c['name']) ?>" aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>">
                        <span class="colour-dot" style="--dot:<?= e($c['hex'] ?: '#999') ?>"></span><?= e($c['name']) ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($options): ?>
            <div class="pdp-block">
                <span class="block-label">Options</span>
                <div class="colour-select">
                    <?php foreach ($options as $i => $o): ?>
                    <button type="button" class="colour-option option-pill <?= $i === 0 ? 'active' : '' ?>" data-option="<?= e($o['name']) ?>">
                        <?= e($o['name']) ?><?= $o['price'] ? ' +' . e(cad($o['price'])) : '' ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($qs): ?>
            <div class="pdp-block">
                <span class="block-label">Key specifications</span>
                <div class="pdp-keyspecs">
                    <?php
                    $icons = [
                        'wheels' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.6"/></svg>',
                        'range' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M4 17L9 6l4 8 3-5 4 8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
                        'speed' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M4 14a8 8 0 1 1 16 0M12 14l4-4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
                        'capacity' => '<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M12 4a3 3 0 1 1 0 6 3 3 0 0 1 0-6zM5 20c0-3.3 3.1-5.5 7-5.5s7 2.2 7 5.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
                    ];
                    foreach ($qs as $k => $v): ?>
                    <div class="keyspec"><?= $icons[$k] ?? '' ?><span><b><?= e($v) ?></b><span><?= e(ucfirst($k === 'wheels' ? 'configuration' : $k)) ?></span></span></div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($p['short_description']): ?>
            <p style="color:var(--grey);font-size:.95rem;"><?= e($p['short_description']) ?></p>
            <?php endif; ?>

            <div class="pdp-ctas">
                <?php if ($canBuy): ?>
                <button type="button" class="btn btn-primary btn-lg" id="pdpBuyNow"
                        data-buy-now data-product-id="<?= (int)$p['id'] ?>">Buy Now</button>
                <?php else: ?>
                <button type="button" class="btn btn-primary btn-lg" id="pdpBuyNow"
                        data-buy-now data-product-id="<?= (int)$p['id'] ?>">Enquire About Availability</button>
                <?php endif; ?>
                <button type="button" class="btn btn-outline btn-lg" data-buy-now data-product-id="<?= (int)$p['id'] ?>">Ask a Question</button>
            </div>
            <div class="pdp-assist">
                <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', setting('store_phone', '1-855-907-4211'))) ?>">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                    <?= e(setting('store_phone', '1-855-907-4211')) ?>
                </a>
                <button type="button" data-wishlist-toggle="<?= (int)$p['id'] ?>">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M12 20s-7.2-4.6-9.2-9C1.4 8 3 5 6.2 5c2 0 3.4 1.1 4.1 2.3h1.4C12.4 6.1 13.8 5 15.8 5 19 5 20.6 8 21.2 11c-2 4.4-9.2 9-9.2 9z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                    Wishlist
                </button>
                <button type="button" data-compare-toggle="<?= (int)$p['id'] ?>">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M8 3v18M16 3v18M3 8h5m-5 8h5m8-8h5m-5 8h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                    Compare
                </button>
                <button type="button" id="pdpShare">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><circle cx="6" cy="12" r="2.5" stroke="currentColor" stroke-width="1.6"/><circle cx="18" cy="6" r="2.5" stroke="currentColor" stroke-width="1.6"/><circle cx="18" cy="18" r="2.5" stroke="currentColor" stroke-width="1.6"/><path d="M8.2 10.8l7.6-3.6m-7.6 6 7.6 3.6" stroke="currentColor" stroke-width="1.6"/></svg>
                    Share
                </button>
            </div>
        </div>
    </div>

    <!-- ===================== TABS ===================== -->
    <div class="pdp-tabs" data-tabs>
        <div class="tab-nav" role="tablist">
            <button class="tab-btn active" data-tab="tab-overview" role="tab" aria-selected="true">Overview</button>
            <?php if ($features): ?><button class="tab-btn" data-tab="tab-features" role="tab">Features</button><?php endif; ?>
            <?php if ($specs): ?><button class="tab-btn" data-tab="tab-specs" role="tab">Specifications</button><?php endif; ?>
            <button class="tab-btn" data-tab="tab-included" role="tab">What's Included</button>
            <button class="tab-btn" data-tab="tab-shipping" role="tab">Shipping</button>
            <button class="tab-btn" data-tab="tab-warranty" role="tab">Warranty</button>
            <?php if ($manuals): ?><button class="tab-btn" data-tab="tab-downloads" role="tab">Downloads</button><?php endif; ?>
            <?php if ($faqs): ?><button class="tab-btn" data-tab="tab-faqs" role="tab">FAQs</button><?php endif; ?>
        </div>

        <div class="tab-panel active" id="tab-overview">
            <div class="rich"><?= $p['long_description'] ?: '<p>' . e($p['short_description'] ?? '') . '</p>' ?></div>
        </div>

        <?php if ($features): ?>
        <div class="tab-panel" id="tab-features">
            <div class="rich"><ul><?php foreach ($features as $f): ?><li><?= e($f['feature']) ?></li><?php endforeach; ?></ul></div>
        </div>
        <?php endif; ?>

        <?php if ($specs): ?>
        <div class="tab-panel" id="tab-specs">
            <?php foreach ($specs as $group => $rows): ?>
            <table class="spec-table">
                <caption><?= e($group) ?></caption>
                <tbody>
                    <?php foreach ($rows as $s): ?>
                    <tr><th scope="row"><?= e($s['spec_name']) ?></th><td><?= e($s['spec_value']) ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="tab-panel" id="tab-included">
            <div class="rich">
                <ul>
                    <li>Your <?= e(preg_replace('/ — .*/', '', $p['name'])) ?>, 90–95% assembled in its protective shipping crate</li>
                    <li>Portable charger for a standard 110V household outlet</li>
                    <li>Digital owner's manual with assembly and care instructions</li>
                    <li>12-month parts warranty from the day your scooter arrives</li>
                </ul>
            </div>
        </div>

        <div class="tab-panel" id="tab-shipping">
            <div class="rich">
                <p>In-stock products normally ship within 1–3 business days. Most scooters arrive within an estimated 4–10 business days; the All-Season Enclosed travels on more complex routing and takes an estimated 5–20 business days.</p>
                <p>A local delivery agent will call to arrange your delivery appointment. Drivers deliver the packaged scooter only — they do not unbox, assemble or demonstrate. If the crate arrives damaged, accept the shipment, photograph the damage and contact us right away.</p>
            </div>
        </div>

        <div class="tab-panel" id="tab-warranty">
            <div class="rich">
                <p>Every scooter includes a 12-month parts warranty covering defects, failures or manufacturing errors — replacement parts ship free of charge. Wear-and-tear items (tires, brake cables, brake pads) and damage from accidents, incorrect use or modification are excluded. Labour for part installation is not included.</p>
                <p>Need help? Call <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', setting('store_phone', '1-855-907-4211'))) ?>"><?= e(setting('store_phone', '1-855-907-4211')) ?></a> (ext. 2 for technical support) or email <a href="mailto:<?= e(setting('store_email')) ?>"><?= e(setting('store_email')) ?></a>.</p>
            </div>
        </div>

        <?php if ($manuals): ?>
        <div class="tab-panel" id="tab-downloads">
            <?php foreach ($manuals as $m): ?>
            <div class="manual-row">
                <span class="need-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M6 3h9l4 4v14H6V3zm8 0v5h5" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg></span>
                <span>
                    <h3><?= e($m['title']) ?></h3>
                    <p><?= e(trim(($m['language'] ?? '') . ($m['version'] ? ' • v' . $m['version'] : '') . ($m['published_at'] ? ' • ' . date('M Y', strtotime($m['published_at'])) : ''))) ?></p>
                </span>
                <a class="btn btn-outline btn-sm" href="<?= e(img_url($m['file'], 'manuals')) ?>" target="_blank" rel="noopener">Download PDF</a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($faqs): ?>
        <div class="tab-panel" id="tab-faqs">
            <div style="max-width:820px;">
                <?php foreach ($faqs as $f): ?>
                <div class="accordion">
                    <button class="accordion-head" aria-expanded="false"><?= e($f['question']) ?>
                        <svg class="acc-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    </button>
                    <div class="accordion-body"><div class="accordion-body-inner"><?= nl2br(e($f['answer'])) ?></div></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ===================== RELATED ===================== -->
    <?php if ($related): ?>
    <section class="section" style="padding-top:0;">
        <div class="section-head">
            <h2>You May Also Like</h2>
        </div>
        <div class="product-grid">
            <?php foreach (array_slice($related, 0, 4) as $rp): $card = $rp; require GIO_INCLUDES . '/product-card.php'; endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>

<!-- Sticky mobile buy bar -->
<div class="mobile-actions" style="display:none;" id="pdpStickyBar">
    <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', setting('store_phone', '1-855-907-4211'))) ?>" class="mobile-action">Call Us</a>
    <button type="button" class="mobile-action mobile-action-primary" data-buy-now data-product-id="<?= (int)$p['id'] ?>">Buy Now</button>
</div>

<!-- Lightbox -->
<div class="lightbox" id="lightbox" hidden role="dialog" aria-modal="true" aria-label="Image viewer">
    <button class="lb-close" data-lb-close aria-label="Close">&times;</button>
    <button class="lb-nav lb-prev" data-lb-prev aria-label="Previous image"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M15 5l-7 7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
    <img id="lightboxImg" src="" alt="<?= e($p['name']) ?>">
    <button class="lb-nav lb-next" data-lb-next aria-label="Next image"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="M9 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
    <span class="lb-counter" id="lbCounter"></span>
</div>

<script>
window.GIO_PRODUCT = {
    id: <?= (int)$p['id'] ?>,
    images: <?= json_encode(array_map(fn($g) => !empty($g['video'])
        ? ['type' => 'video', 'provider' => $g['video']['provider'], 'id' => $g['video']['video_id'], 'poster' => $g['video']['poster'] ? img_url($g['video']['poster']) : img_url($mainImg)]
        : ['type' => 'image', 'src' => img_url($g['file']), 'thumb' => img_thumb_url($g['file']), 'alt' => $g['alt'] ?? $p['name']], $gallery), JSON_UNESCAPED_SLASHES) ?>
};
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
