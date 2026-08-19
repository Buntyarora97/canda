<?php
/**
 * Premium product card. Expects $card (product row) in scope.
 * Usage:  $card = $p; require GIO_INCLUDES . '/product-card.php';
 */
declare(strict_types=1);

$cardImg  = product_primary_image((int) $card['id']);
$cardImgs = product_images((int) $card['id']);
$cardImg2 = $cardImgs[1] ?? null;
$cardVars = product_variants((int) $card['id']);
$badge    = product_badge($card);
$qs       = product_quick_specs((int) $card['id']);
$qsIcons  = [
    'wheels'   => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.6"/><circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.6"/></svg>',
    'range'    => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 17L9 6l4 8 3-5 4 8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
    'speed'    => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 14a8 8 0 1 1 16 0" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M12 14l4-4.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
    'capacity' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 4a3 3 0 1 1 0 6 3 3 0 0 1 0-6zM5 20c0-3.3 3.1-5.5 7-5.5s7 2.2 7 5.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>',
];
?>
<article class="product-card reveal" data-product-id="<?= (int) $card['id'] ?>">
    <a class="card-media" href="<?= e(product_url($card)) ?>" aria-label="View <?= e($card['name']) ?>">
        <?php if ($badge): ?><span class="card-badge badge-<?= e(slugify($badge)) ?>"><?= e($badge) ?></span><?php endif; ?>
        <button type="button" class="card-wish" data-wishlist-toggle="<?= (int) $card['id'] ?>" aria-label="Add to wishlist" aria-pressed="false">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 20s-7.2-4.6-9.2-9C1.4 8 3 5 6.2 5c2 0 3.4 1.1 4.1 2.3h1.4C12.4 6.1 13.8 5 15.8 5 19 5 20.6 8 21.2 11c-2 4.4-9.2 9-9.2 9z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
        </button>
        <div class="card-img-wrap">
            <img class="card-img card-img-main"
                 src="<?= e(img_thumb_url($cardImg['file'] ?? null)) ?>"
                 alt="<?= e($cardImg['alt'] ?? $card['name']) ?>"
                 width="640" height="640" loading="lazy" decoding="async">
            <?php if ($cardImg2): ?>
            <img class="card-img card-img-alt"
                 src="<?= e(img_thumb_url($cardImg2['file'])) ?>"
                 alt="" width="640" height="640" loading="lazy" decoding="async" aria-hidden="true">
            <?php endif; ?>
        </div>
    </a>
    <div class="card-body">
        <h3 class="card-title"><a href="<?= e(product_url($card)) ?>"><?= e($card['name']) ?></a></h3>
        <?php if (!empty($card['tagline'])): ?><p class="card-tagline"><?= e($card['tagline']) ?></p><?php endif; ?>

        <div class="card-price-row">
            <?php if (!empty($card['show_price']) && $card['price'] !== null): ?>
                <span class="card-price"><?= e(cad($card['price'])) ?></span>
                <?php if (!empty($card['compare_price']) && $card['compare_price'] > $card['price']): ?>
                    <s class="card-compare"><?= e(cad($card['compare_price'])) ?></s>
                <?php endif; ?>
            <?php else: ?>
                <span class="card-price card-price-enquire">Pricing on enquiry</span>
            <?php endif; ?>
        </div>

        <?php if ($cardVars): ?>
        <div class="card-colours" aria-label="Available colours">
            <?php foreach ($cardVars as $v): ?>
            <span class="colour-dot" style="--dot:<?= e($v['hex'] ?: '#999') ?>" title="<?= e($v['name']) ?>"></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="card-stock <?= $card['stock_status'] === 'in_stock' ? 'in' : ($card['stock_status'] === 'out_of_stock' ? 'out' : 'mid') ?>">
            <span class="stock-dot" aria-hidden="true"></span><?= e(STOCK_BADGES[$card['stock_status']] ?? '') ?>
        </div>

        <?php if ($qs): ?>
        <ul class="card-specs">
            <?php $i = 0; foreach ($qs as $k => $v): if ($i++ >= 3) break; ?>
            <li title="<?= e($v) ?>"><?= $qsIcons[$k] ?? '' ?><span><?= e($v) ?></span></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

        <div class="card-actions">
            <a class="btn btn-outline btn-sm" href="<?= e(product_url($card)) ?>">View Details</a>
            <?php if ($card['stock_status'] !== 'out_of_stock' && $card['stock_status'] !== 'coming_soon'): ?>
            <button type="button" class="btn btn-primary btn-sm"
                    data-buy-now data-product-id="<?= (int) $card['id'] ?>">Buy Now</button>
            <?php else: ?>
            <button type="button" class="btn btn-primary btn-sm"
                    data-buy-now data-product-id="<?= (int) $card['id'] ?>">Enquire</button>
            <?php endif; ?>
        </div>
    </div>
</article>
