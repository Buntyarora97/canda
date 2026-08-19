<?php
/** GIO Mobility Canada — Homepage */
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/seo.php';
require_once __DIR__ . '/includes/schema.php';

$banners   = active_banners();
$hero      = $banners[0] ?? null;
$heroImg   = $hero ? img_url($hero['desktop_image'], 'banners') : site_url('assets/images/placeholder-hero.jpg');
$heroImgM  = $hero && $hero['mobile_image'] ? img_url($hero['mobile_image'], 'banners') : $heroImg;

$bestSellers = list_products(['best_seller' => true, 'limit' => 12]);
$newArrivals = list_products(['new_arrival' => true, 'limit' => 12]);
$featured    = list_products(['featured' => true, 'limit' => 1])[0] ?? ($bestSellers[0] ?? null);
$heroProductImg = $featured ? product_primary_image((int)$featured['id']) : null;
$categories  = list_categories();
$reviews     = rows('SELECT r.*, p.name AS product_name FROM reviews r LEFT JOIN products p ON p.id = r.product_id WHERE r.is_published = 1 ORDER BY r.sort_order, r.id LIMIT 9');
$posts       = rows('SELECT p.*, (SELECT c.name FROM post_categories c JOIN post_category_map m ON m.category_id = c.id AND m.post_id = p.id LIMIT 1) AS cat_name FROM posts p WHERE p.is_published = 1 ORDER BY p.published_at DESC LIMIT 3');
$compareProducts = list_products(['limit' => 30, 'sort' => 'alpha']);

seo_set([
    'page_key' => 'home',
    'canonical' => site_url(),
    'hero_preload' => $heroImg,
]);
require __DIR__ . '/includes/header.php';
?>

<!-- ============ 1. HERO ============ -->
<section class="hero hero-luxury" data-luxury-hero>
    <div class="hero-media">
        <img src="<?= e($heroImg) ?>" srcset="<?= e($heroImgM) ?> 800w, <?= e($heroImg) ?> 1920w"
             sizes="100vw" alt="<?= e($hero['headline'] ?? 'GIO mobility scooter in a Canadian neighbourhood') ?>" fetchpriority="high">
    </div>
    <div class="hero-orb hero-orb-one" aria-hidden="true"></div>
    <div class="hero-orb hero-orb-two" aria-hidden="true"></div>
    <div class="hero-grid-lines" aria-hidden="true"></div>
    <div class="container hero-content">
        <span class="eyebrow hero-kicker"><?= e($hero['eyebrow'] ?? 'Mobility, Reimagined.') ?></span>
        <h1 class="hero-title"><?= e($hero['headline'] ?? 'Go Further. Live Freely.') ?></h1>
        <p class="hero-sub hero-copy"><?= e($hero['subheading'] ?? 'Discover stylish, thoughtfully designed mobility solutions made for comfort, confidence and everyday independence.') ?></p>
        <div class="hero-ctas hero-buttons">
            <a class="btn btn-primary btn-lg" href="<?= e(site_url('shop')) ?>"><?= e($hero['cta1_text'] ?? 'Explore Mobility') ?></a>
            <a class="btn btn-outline-light btn-lg" href="<?= e(site_url('shop')) ?>"><?= e($hero['cta2_text'] ?? 'Find Your GIO') ?></a>
        </div>
        <div class="hero-trust hero-trust-luxury">
            <span><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 12l5 5L20 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg> Canadian designed</span>
            <span><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 12l5 5L20 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg> 12-month parts warranty</span>
            <span><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 12l5 5L20 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg> Canada-wide delivery</span>
        </div>
    </div>
    <?php if ($featured && $heroProductImg): ?>
    <div class="hero-product-stage" aria-label="<?= e($featured['name']) ?>">
        <div class="hero-product-halo" aria-hidden="true"></div>
        <div class="hero-product-card">
            <div class="hero-product-card-top"><span class="hero-product-label">Featured ride</span><span class="hero-product-live"><i></i> In stock</span></div>
            <div class="hero-product-image-wrap"><img class="hero-product-image" src="<?= e(img_url($heroProductImg['file'])) ?>" alt="<?= e($heroProductImg['alt'] ?? $featured['name']) ?>"></div>
            <div class="hero-product-info">
                <div><span class="hero-product-kicker">GIO signature series</span><h2><?= e($featured['name']) ?></h2></div>
                <a class="hero-product-arrow" href="<?= e(product_url($featured)) ?>" aria-label="View <?= e($featured['name']) ?>"><svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
            </div>
        </div>
        <div class="hero-float-chip chip-range"><span class="chip-icon">↗</span><span><b>50 km</b><small>range</small></span></div>
        <div class="hero-float-chip chip-comfort"><span class="chip-icon">✦</span><span><b>Premium</b><small>comfort</small></span></div>
    </div>
    <?php endif; ?>
    <span class="hero-scroll-cue"><span class="hero-scroll-line"></span> Scroll to explore</span>
</section>

<!-- ============ 2. CATEGORY DISCOVERY ============ -->
<section class="section">
    <div class="container">
        <div class="section-head reveal">
            <div>
                <h2>Find Your Perfect Ride</h2>
                <p class="lead">Six ways into the GIO lineup — from nimble three-wheelers to the all-season cabin.</p>
            </div>
            <a class="head-link" href="<?= e(site_url('shop')) ?>">View all products <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
        </div>
        <div class="cat-grid">
            <?php foreach ($categories as $i => $c): ?>
            <a class="cat-card reveal" data-delay="<?= ($i % 3) + 1 ?>" href="<?= e(category_url($c)) ?>">
                <img src="<?= e(img_thumb_url($c['image'], 'categories')) ?>" alt="<?= e($c['name']) ?>" loading="lazy" width="640" height="500">
                <span class="cat-card-body">
                    <span>
                        <h3><?= e($c['name']) ?></h3>
                        <p><?= e($c['menu_label'] ?? '') ?></p>
                    </span>
                    <span class="cat-arrow"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                </span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ 3. BEST SELLERS ============ -->
<section class="section section-alt">
    <div class="container">
        <div class="section-head reveal">
            <div>
                <h2>Best Sellers</h2>
                <p class="lead">The models Canadians keep choosing.</p>
            </div>
            <a class="head-link" href="<?= e(site_url('best-sellers')) ?>">View all <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
        </div>
        <div class="product-grid">
            <?php foreach ($bestSellers as $p): $card = $p; require GIO_INCLUDES . '/product-card.php'; endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ 4. FEATURED PRODUCT STORY ============ -->
<?php if ($featured):
    $fImg = product_primary_image((int)$featured['id']);
    $fFeat = array_slice(array_column(product_features((int)$featured['id']), 'feature'), 0, 5);
?>
<section class="section section-dark">
    <div class="container story-grid">
        <div class="story-media reveal">
            <img src="<?= e(img_url($fImg['file'] ?? null)) ?>" alt="<?= e($fImg['alt'] ?? $featured['name']) ?>" loading="lazy" width="900" height="900">
        </div>
        <div class="reveal" data-delay="1">
            <span class="eyebrow">Featured Model</span>
            <h2><?= e($featured['name']) ?></h2>
            <p class="hero-sub"><?= e($featured['tagline'] ?? $featured['short_description'] ?? '') ?></p>
            <?php if ($fFeat): ?>
            <ul class="story-facts">
                <?php foreach ($fFeat as $f): ?>
                <li><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 12l5 5L20 6" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg><?= e($f) ?></li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
            <div class="story-ctas">
                <a class="btn btn-outline-light" href="<?= e(product_url($featured)) ?>">Explore Product</a>
                <button type="button" class="btn btn-primary" data-buy-now data-product-id="<?= (int)$featured['id'] ?>">Enquire Now</button>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ============ 5. NEW ARRIVALS ============ -->
<?php if ($newArrivals): ?>
<section class="section">
    <div class="container" data-carousel>
        <div class="section-head reveal">
            <div>
                <h2>What's New at GIO</h2>
                <p class="lead">Fresh arrivals from the design bench in Richmond, BC.</p>
            </div>
            <div class="carousel-nav">
                <button class="carousel-btn" data-car-prev aria-label="Previous"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M19 12H5m6 6-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                <button class="carousel-btn" data-car-next aria-label="Next"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
            </div>
        </div>
        <div class="h-scroll">
            <?php foreach ($newArrivals as $p): $card = $p; require GIO_INCLUDES . '/product-card.php'; endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ============ 6. SHOP BY NEED ============ -->
<section class="section section-alt">
    <div class="container">
        <div class="section-head reveal">
            <div>
                <h2>Shop by Need</h2>
                <p class="lead">Start with how you live — we'll point you to the right machine.</p>
            </div>
        </div>
        <div class="need-grid">
            <?php
            $needs = [
                ['Long-Range Mobility', 'Up to 75 km per charge for full-day adventures.', '/shop?min_range=60', 'M13 2 4 14h6l-1 8 9-12h-6l1-8z'],
                ['Easy Operation', 'Simplified controls and gentle throttles for confident riding.', '/category/accessible-operation', 'M12 4a8 8 0 1 0 8 8M12 8v4l3 2'],
                ['All-Weather Comfort', 'Heated, enclosed cabins built for Canadian seasons.', '/category/enclosed-mobility', 'M12 3v3m6.4.6-2.2 2.2M21 12h-3M6.6 6.6 4.4 4.4M6 12H3m9 9v-3'],
                ['Maximum Stability', 'Four points of contact and a planted, confident feel.', '/category/4-wheel-mobility', 'M12 3l9 5-9 5-9-5 9-5zM3 13l9 5 9-5'],
                ['Everyday Errands', 'Baskets, bins and storage for groceries and gear.', '/shop', 'M6 7h12l-1.2 12H7.2L6 7zm3 0a3 3 0 0 1 6 0'],
                ['Portable Mobility', 'Lightweight folding designs that travel with you.', '/category/mobility-walkers', 'M8 21h8M12 17v4M5 4h14v9a4 4 0 0 1-4 4H9a4 4 0 0 1-4-4V4z'],
            ];
            foreach ($needs as $i => [$t, $d, $u, $icon]): ?>
            <div class="need-card reveal" data-delay="<?= ($i % 3) + 1 ?>">
                <span class="need-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="<?= e($icon) ?>" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                <h3><?= e($t) ?></h3>
                <p><?= e($d) ?></p>
                <a class="head-link" href="<?= eurl($u) ?>">Shop this need <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ 7. WHY GIO ============ -->
<section class="section">
    <div class="container">
        <div class="section-head reveal">
            <div>
                <h2>Why GIO</h2>
                <p class="lead">Proudly providing stylish, dependable electric scooters for over a decade.</p>
            </div>
            <a class="head-link" href="<?= e(site_url('why-gio')) ?>">Learn more <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
        </div>
        <div class="why-grid">
            <?php
            $why = [
                ['Canadian Designed', 'Based in Vancouver, every GIO scooter is designed with care and refined with feedback directly from customers.', 'M12 3v18M5 8l7-5 7 5M7 21h10'],
                ['Thoughtful Features', 'Bigger storage, bigger wheels, USB charging and lighting — the details that matter day to day.', 'M12 2l2.4 6.9H22l-5.7 4.3 2.1 6.8-6.4-4.2-6.4 4.2 2.1-6.8L2 8.9h7.6L12 2z'],
                ['Comfort-Focused', 'Adjustable seats, easy controls and smooth suspension tuned for real-world comfort.', 'M4 12c2-4 6-6 8-6s6 2 8 6c-2 4-6 6-8 6s-6-2-8-6z'],
                ['Experienced Support', 'A US/Canada-based team with hands-on product experience — no scripts, no overseas call centres.', 'M4 5h16v11H9l-5 4V5z'],
                ['Parts Availability', 'A full parts department keeps your GIO on the road for years, with warranty parts shipped free.', 'M12 3a9 9 0 1 0 9 9M12 7v5l3 3'],
                ['Everyday Independence', 'Mobility built around real life — reconnect with your community on your own schedule.', 'M12 21s-7.5-4.7-9.5-9.2C1.2 8.4 3 5.4 6.2 5.4c2 0 3.5 1.1 4.2 2.4h3.2c.7-1.3 2.2-2.4 4.2-2.4 3.2 0 5 3 3.7 6.4C19.5 16.3 12 21 12 21z'],
            ];
            foreach ($why as $i => [$t, $d, $icon]): ?>
            <div class="why-card reveal" data-delay="<?= ($i % 3) + 1 ?>">
                <span class="need-icon"><svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="<?= e($icon) ?>" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                <h3><?= e($t) ?></h3>
                <p><?= e($d) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ 8. LIFESTYLE BANNER ============ -->
<?php $life = $banners[1] ?? null; ?>
<section class="section section-alt">
    <div class="container">
        <div class="lifestyle reveal">
            <img src="<?= e($life ? img_url($life['desktop_image'], 'banners') : site_url('assets/images/placeholder-hero.jpg')) ?>" alt="<?= e($life['headline'] ?? 'GIO scooter beside a Canadian waterfront') ?>" loading="lazy" width="1600" height="900">
            <div class="lifestyle-content">
                <h2><?= e($life['headline'] ?? "Your World Shouldn't Get Smaller.") ?></h2>
                <p><?= e($life['subheading'] ?? 'Rediscover everyday freedom with mobility built around real life.') ?></p>
                <a class="btn btn-primary" href="<?= eurl($life['cta1_url'] ?? '/about') ?>"><?= e($life['cta1_text'] ?? 'Discover GIO') ?></a>
            </div>
        </div>
    </div>
</section>

<!-- ============ 9. COMPARE MODELS ============ -->
<section class="section">
    <div class="container">
        <div class="section-head reveal">
            <div>
                <h2>Compare Models</h2>
                <p class="lead">Pick up to three models and see them side by side.</p>
            </div>
            <a class="head-link" href="<?= e(site_url('compare')) ?>">View full comparison <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
        </div>
        <div class="compare-module reveal">
            <div class="compare-selects">
                <?php for ($slot = 1; $slot <= 3; $slot++): ?>
                <div class="compare-select">
                    <label for="compareSlot<?= $slot ?>">Model <?= $slot ?></label>
                    <select id="compareSlot<?= $slot ?>" class="compare-slot" data-slot="<?= $slot ?>">
                        <option value="">Select a model…</option>
                        <?php foreach ($compareProducts as $cp): ?>
                        <option value="<?= (int)$cp['id'] ?>"><?= e($cp['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endfor; ?>
            </div>
            <div class="compare-table-wrap" id="compareTableWrap">
                <p class="compare-empty">Select at least two models above to compare them here.</p>
            </div>
        </div>
    </div>
</section>

<!-- ============ 10. CUSTOMER STORIES ============ -->
<section class="section section-alt">
    <div class="container">
        <div class="section-head reveal">
            <div>
                <h2>Customer Stories</h2>
                <p class="lead">Real experiences from GIO riders across Canada.</p>
            </div>
            <a class="head-link" href="<?= e(site_url('stories')) ?>">All stories <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
        </div>
        <?php if ($reviews): ?>
        <div class="review-grid">
            <?php foreach (array_slice($reviews, 0, 3) as $r): ?>
            <figure class="review-card reveal">
                <div class="review-stars" aria-label="<?= (int)$r['rating'] ?> out of 5 stars"><?= str_repeat('★', (int)$r['rating']) . str_repeat('☆', 5 - (int)$r['rating']) ?></div>
                <blockquote class="review-text">&ldquo;<?= e($r['review']) ?>&rdquo;</blockquote>
                <figcaption class="review-meta">
                    <?php if ($r['photo']): ?><img src="<?= e(img_thumb_url($r['photo'], 'reviews')) ?>" alt="" width="46" height="46" loading="lazy"><?php endif; ?>
                    <span>
                        <span class="review-name"><?= e($r['customer_name']) ?></span><br>
                        <span class="review-sub"><?= e(trim(($r['product_name'] ?? '') . ($r['source'] ? ' • ' . $r['source'] : ''))) ?></span>
                    </span>
                </figcaption>
            </figure>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state reveal">
            <h3>Customer stories coming soon</h3>
            <p>We're collecting real rider stories — check back shortly, or share yours with our team.</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ============ 11. SUPPORT ============ -->
<section class="section">
    <div class="container">
        <div class="section-head reveal">
            <div>
                <h2>Support That Travels With You</h2>
                <p class="lead">Real people, real answers — before and long after your purchase.</p>
            </div>
        </div>
        <div class="support-grid">
            <?php
            $support = [
                ['Product Manuals', 'Digital owner\'s manuals for every model.', '/support/manuals', 'M6 3h9l4 4v14H6V3zm8 0v5h5'],
                ['Frequently Asked Questions', 'Fast answers to common questions.', '/support/faqs', 'M9.1 9a3 3 0 0 1 5.8 1c0 2-3 2.5-3 4.5M12 18.5v.5'],
                ['Parts & Accessories', 'Genuine GIO batteries, tires and more.', '/category/parts-accessories', 'M12 3a9 9 0 1 0 9 9M12 7v5l3 3'],
                ['Contact Support', 'Talk to our Vancouver-based team.', '/contact', 'M4 5h16v11H9l-5 4V5z'],
                ['Warranty Information', '12-month parts warranty on every scooter.', '/support/warranty', 'M12 3l7 3v5c0 5-3.5 8.5-7 10-3.5-1.5-7-5-7-10V6l7-3z'],
                ['Ordering Guide', 'From enquiry to delivery, step by step.', '/support/ordering-guide', 'M5 4h14M5 9h14M5 14h9M5 19h6'],
            ];
            foreach ($support as $i => [$t, $d, $u, $icon]): ?>
            <a class="support-card reveal" data-delay="<?= ($i % 3) + 1 ?>" href="<?= eurl($u) ?>">
                <span class="need-icon"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"><path d="<?= e($icon) ?>" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                <span>
                    <h3><?= e($t) ?></h3>
                    <p><?= e($d) ?></p>
                </span>
                <svg class="go-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============ 12. BUYING GUIDE ============ -->
<section class="section section-alt">
    <div class="container">
        <div class="section-head reveal">
            <div>
                <h2>How Buying Works</h2>
                <p class="lead">This site collects enquiries rather than taking online payment — a real person confirms everything with you.</p>
            </div>
        </div>
        <div class="steps">
            <div class="step reveal"><span class="step-num">01</span><h3>Explore</h3><p>Browse models, colours and specs — filter by what matters to you.</p></div>
            <div class="step reveal" data-delay="1"><span class="step-num">02</span><h3>Compare</h3><p>Put up to three models side by side and compare range, speed and features.</p></div>
            <div class="step reveal" data-delay="2"><span class="step-num">03</span><h3>Send Enquiry</h3><p>Tap Buy Now on any product — no payment is taken online.</p></div>
            <div class="step reveal" data-delay="3"><span class="step-num">04</span><h3>GIO Team Contacts You</h3><p>We confirm details, answer questions and complete your order together.</p></div>
        </div>
    </div>
</section>

<!-- ============ 13. NEWS / GUIDES ============ -->
<?php if ($posts): ?>
<section class="section">
    <div class="container">
        <div class="section-head reveal">
            <div>
                <h2>Guides &amp; News</h2>
                <p class="lead">Practical reading for confident riders.</p>
            </div>
            <a class="head-link" href="<?= e(site_url('blog')) ?>">All articles <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
        </div>
        <div class="article-grid">
            <?php foreach ($posts as $i => $post): ?>
            <a class="article-card reveal" data-delay="<?= $i + 1 ?>" href="<?= e(site_url('blog/' . $post['slug'])) ?>">
                <span class="article-img"><img src="<?= e(img_thumb_url($post['featured_image'], 'posts')) ?>" alt="<?= e($post['title']) ?>" loading="lazy" width="640" height="400"></span>
                <span class="article-body">
                    <span class="article-cat"><?= e($post['cat_name'] ?? 'Guide') ?></span>
                    <h3><?= e($post['title']) ?></h3>
                    <p><?= e($post['excerpt'] ?: excerpt($post['content'])) ?></p>
                    <span class="article-date"><?= e(date('F j, Y', strtotime($post['published_at'] ?? $post['created_at']))) ?></span>
                </span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ============ 14. FINAL CTA ============ -->
<section class="section section-dark final-cta">
    <div class="container">
        <h2 class="reveal">Ready to Find Your GIO?</h2>
        <p class="reveal" data-delay="1">Explore the lineup, compare your favourites, and send an enquiry — our Canadian team takes it from there.</p>
        <div class="hero-ctas reveal" data-delay="2">
            <a class="btn btn-primary btn-lg" href="<?= e(site_url('shop')) ?>">Shop Models</a>
            <button type="button" class="btn btn-outline-light btn-lg" data-enquire-general>Talk to Our Team</button>
        </div>
    </div>
</section>

<script>
/* Luxury hero motion. GSAP enhances the scene; CSS remains the accessible fallback. */
(function () {
  const hero = document.querySelector('[data-luxury-hero]');
  if (!hero || !window.gsap || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  const q = (s) => hero.querySelector(s);
  const tl = gsap.timeline({ defaults: { ease: 'power3.out' } });
  tl.from(q('.hero-kicker'), { y: 22, opacity: 0, duration: .7 })
    .from(q('.hero-title'), { y: 52, opacity: 0, duration: 1.05 }, '-=.35')
    .from(q('.hero-copy'), { y: 26, opacity: 0, duration: .75 }, '-=.55')
    .from(q('.hero-buttons'), { y: 22, opacity: 0, duration: .65 }, '-=.45')
    .from(q('.hero-trust-luxury'), { y: 16, opacity: 0, duration: .6 }, '-=.35')
    .from(q('.hero-product-card'), { x: 110, y: 35, rotation: 5, opacity: 0, duration: 1.15 }, '-=.95')
    .from(q('.hero-float-chip'), { scale: .7, opacity: 0, stagger: .14, duration: .6 }, '-=.65');
  gsap.to(q('.hero-product-card'), { y: -13, rotation: -.7, duration: 3.4, repeat: -1, yoyo: true, ease: 'sine.inOut' });
  gsap.to(q('.hero-product-halo'), { scale: 1.12, opacity: .65, duration: 2.8, repeat: -1, yoyo: true, ease: 'sine.inOut' });
  gsap.to(q('.chip-range'), { y: -9, x: 4, duration: 2.5, repeat: -1, yoyo: true, ease: 'sine.inOut' });
  gsap.to(q('.chip-comfort'), { y: 10, x: -4, duration: 2.9, repeat: -1, yoyo: true, ease: 'sine.inOut' });
  hero.addEventListener('mousemove', function (event) {
    const rect = hero.getBoundingClientRect();
    const x = (event.clientX - rect.left) / rect.width - .5;
    const y = (event.clientY - rect.top) / rect.height - .5;
    gsap.to(q('.hero-product-stage'), { x: x * 18, y: y * 12, duration: .8, overwrite: true });
    gsap.to(q('.hero-grid-lines'), { x: x * -10, y: y * -7, duration: 1, overwrite: true });
  });
})();

/* Homepage compare module — loads live data from /api/compare.php */
(function () {
  const wrap = document.getElementById('compareTableWrap');
  const slots = Array.from(document.querySelectorAll('.compare-slot'));
  function esc(s){return String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
  function load() {
    const ids = slots.map(s => s.value).filter(Boolean);
    if (ids.length < 2) { wrap.innerHTML = '<p class="compare-empty">Select at least two models above to compare them here.</p>'; return; }
    fetch(window.gioUrl('api/compare.php?ids=' + ids.join(',')))
      .then(r => r.json())
      .then(data => {
        const ps = data.products || [];
        if (ps.length < 2) return;
        const rowsDef = [
          ['', p => '<img src="' + p.thumb + '" alt="' + esc(p.name) + '" loading="lazy">', true],
          ['Model', p => '<span class="model-name">' + esc(p.name) + '</span>'],
          ['Price', p => esc(p.price_label || 'On enquiry')],
          ['Wheel configuration', p => esc(p.wheels || '—')],
          ['Range', p => esc(p.range || '—')],
          ['Top speed', p => esc(p.speed || '—')],
          ['Capacity', p => esc(p.capacity || '—')],
          ['Battery', p => esc(p.battery || '—')],
          ['Key features', p => (p.features || []).slice(0, 4).map(esc).join('<br>') || '—'],
        ];
        let html = '<table class="compare-table"><tbody>';
        rowsDef.forEach(([label, fn]) => {
          const vals = ps.map(p => fn(p));
          const isDiff = new Set(vals).size > 1;
          html += '<tr><th>' + esc(label) + '</th>' + vals.map(v => '<td class="' + (isDiff && label ? 'diff' : '') + '">' + v + '</td>').join('') + '</tr>';
        });
        html += '<tr><th></th>' + ps.map(p => '<td><a class="btn btn-outline btn-sm" href="' + p.url + '">View Details</a></td>').join('') + '</tr>';
        wrap.innerHTML = html + '</tbody></table>';
      });
  }
  slots.forEach(s => s.addEventListener('change', load));
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
