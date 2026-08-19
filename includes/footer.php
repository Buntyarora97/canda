<?php
/** Global footer + enquiry modal + cookie notice + scripts. */
declare(strict_types=1);

$footerCats   = list_categories();
$storePhone   = setting('store_phone', '1-855-907-4211');
$storePhoneTel= preg_replace('/[^0-9+]/', '', $storePhone);
$storeEmail   = setting('store_email', 'support@gioelectric.zendesk.com');
$storeAddress = setting('store_address', 'Unit 1 - 11400 Twigg Place, Richmond, BC');
?>
</main>

<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <a class="brand brand-light" href="<?= e(site_url()) ?>">
                <img src="<?= e(site_url('assets/images/gio-logo-light.png')) ?>" srcset="<?= e(site_url('assets/images/gio-logo-light@2x.png')) ?> 2x" alt="GIO Mobility" width="116" height="35">
                <span class="brand-tag">MOBILITY&nbsp;CANADA</span>
            </a>
            <p class="footer-blurb">Stylish, dependable electric mobility — proudly designed for Canadians for over a decade.</p>
            <div class="social-row">
                <a href="<?= eurl(setting('social_facebook', '#')) ?>" aria-label="Facebook" rel="noopener"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13.5 21v-7h2.4l.4-3h-2.8V9.1c0-.9.3-1.5 1.6-1.5h1.3V4.9c-.3 0-1.1-.1-2-.1-2 0-3.4 1.2-3.4 3.5V11H8.5v3H11v7h2.5z"/></svg></a>
                <a href="<?= eurl(setting('social_instagram', '#')) ?>" aria-label="Instagram" rel="noopener"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3.5" y="3.5" width="17" height="17" rx="4.5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r="1.1" fill="currentColor" stroke="none"/></svg></a>
                <a href="<?= eurl(setting('social_youtube', '#')) ?>" aria-label="YouTube" rel="noopener"><svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M21.6 7.2a2.8 2.8 0 0 0-2-2C17.9 4.8 12 4.8 12 4.8s-5.9 0-7.6.4a2.8 2.8 0 0 0-2 2A29 29 0 0 0 2 12a29 29 0 0 0 .4 4.8 2.8 2.8 0 0 0 2 2c1.7.4 7.6.4 7.6.4s5.9 0 7.6-.4a2.8 2.8 0 0 0 2-2A29 29 0 0 0 22 12a29 29 0 0 0-.4-4.8zM10 15.2V8.8l5.2 3.2-5.2 3.2z"/></svg></a>
            </div>
        </div>
        <nav class="footer-col" aria-label="Shop">
            <h3>Shop</h3>
            <a href="<?= e(site_url('shop')) ?>">All Products</a>
            <?php foreach ($footerCats as $c): ?>
            <a href="<?= e(category_url($c)) ?>"><?= e($c['name']) ?></a>
            <?php endforeach; ?>
        </nav>
        <nav class="footer-col" aria-label="Support">
            <h3>Support</h3>
            <a href="<?= e(site_url('support/faqs')) ?>">FAQs</a>
            <a href="<?= e(site_url('support/ordering-guide')) ?>">Ordering Guide</a>
            <a href="<?= e(site_url('support/warranty')) ?>">Warranty</a>
            <a href="<?= e(site_url('support/shipping')) ?>">Shipping</a>
            <a href="<?= e(site_url('support/manuals')) ?>">Product Manuals</a>
            <a href="<?= e(site_url('contact')) ?>">Contact Support</a>
        </nav>
        <nav class="footer-col" aria-label="Company">
            <h3>About</h3>
            <a href="<?= e(site_url('about')) ?>">About GIO</a>
            <a href="<?= e(site_url('why-gio')) ?>">Why GIO</a>
            <a href="<?= e(site_url('stories')) ?>">Customer Stories</a>
            <a href="<?= e(site_url('blog')) ?>">Blog &amp; Guides</a>
        </nav>
        <div class="footer-col" aria-label="Contact">
            <h3>Contact</h3>
            <a href="tel:<?= e($storePhoneTel) ?>" class="footer-contact"><?= e($storePhone) ?></a>
            <a href="mailto:<?= e($storeEmail) ?>" class="footer-contact"><?= e($storeEmail) ?></a>
            <p class="footer-address"><?= e($storeAddress) ?></p>
            <form class="newsletter" id="newsletterForm" novalidate>
                <label for="newsletterEmail">Mobility news &amp; guides</label>
                <div class="newsletter-row">
                    <input type="email" id="newsletterEmail" name="email" placeholder="Your email" required>
                    <button type="submit" class="btn btn-primary btn-sm" aria-label="Subscribe">Join</button>
                </div>
                <p class="newsletter-msg" id="newsletterMsg" role="status"></p>
            </form>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container footer-bottom-row">
            <p>&copy; <?= date('Y') ?> <?= e(SITE_NAME) ?>. All rights reserved.</p>
            <nav aria-label="Legal">
                <a href="<?= e(site_url('privacy')) ?>">Privacy Policy</a>
                <a href="<?= e(site_url('terms')) ?>">Terms of Service</a>
                <a href="<?= e(site_url('accessibility')) ?>">Accessibility</a>
            </nav>
        </div>
    </div>
</footer>

<!-- Sticky mobile actions (suppressed on PDP — product page renders its own Buy Now bar) -->
<?php if (($GLOBALS['body_class'] ?? '') !== 'pdp'): ?>
<div class="mobile-actions">
    <a href="tel:<?= e($storePhoneTel) ?>" class="mobile-action">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
        Call
    </a>
    <button type="button" class="mobile-action mobile-action-primary" data-enquire-general>
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h16v11H9l-5 4V5z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
        Enquire
    </button>
</div>
<?php endif; ?>

<!-- Compare tray -->
<div class="compare-tray" id="compareTray" hidden>
    <div class="compare-tray-items" id="compareTrayItems"></div>
    <div class="compare-tray-actions">
        <button type="button" class="btn btn-ghost btn-sm" id="compareClear">Clear</button>
        <a href="<?= e(site_url('compare')) ?>" class="btn btn-primary btn-sm" id="compareGo">Compare <span id="compareTrayCount"></span></a>
    </div>
</div>

<?php require GIO_INCLUDES . '/enquiry-modal.php'; ?>

<div class="cookie-notice" id="cookieNotice" hidden>
    <p>We use essential cookies to keep the site working and remember your preferences. See our <a href="<?= e(site_url('privacy')) ?>">Privacy Policy</a>.</p>
    <button type="button" class="btn btn-primary btn-sm" id="cookieAccept">Got it</button>
</div>

<div class="toast" id="toast" role="status" aria-live="polite" hidden></div>

<script src="<?= e(site_url('assets/js/main.js')) ?>?v=1.0.0" defer></script>
<?php if (!empty($GLOBALS['page_js'])): ?>
<script src="<?= e(site_url('assets/js/' . $GLOBALS['page_js'])) ?>?v=1.0.0" defer></script>
<?php endif; ?>
</body>
</html>
