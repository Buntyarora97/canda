<?php
/** Accessibility statement */
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/seo.php';

seo_set(['title' => 'Accessibility | ' . SITE_NAME, 'canonical' => site_url('accessibility')]);
$crumbs = [['label' => 'Home', 'url' => site_url()], ['label' => 'Accessibility', 'url' => null]];
require __DIR__ . '/includes/header.php';
?>

<div class="container content-narrow">
    <?= render_breadcrumbs($crumbs) ?>
    <h1 style="margin-top:20px;">Accessibility</h1>
    <p><?= e(SITE_NAME) ?> is built to be usable by everyone. This site aims for WCAG 2.1 AA fundamentals, including:</p>
    <ul>
        <li>Keyboard navigable menus, forms and galleries</li>
        <li>Visible focus states and sufficient colour contrast</li>
        <li>Alt text on meaningful images</li>
        <li>Respect for reduced-motion preferences</li>
        <li>Large tap targets and readable font sizes on mobile</li>
    </ul>
    <p>Prefer to shop by phone? Call <a href="tel:<?= e(preg_replace('/[^0-9+]/', '', setting('store_phone', '1-855-907-4211'))) ?>" style="color:var(--brand);"><?= e(setting('store_phone', '1-855-907-4211')) ?></a> (<?= e(setting('store_hours', 'Monday – Friday, 10am – 4pm Pacific')) ?>) and our team will gladly help you choose and order.</p>
    <p>If anything on this site is hard to use, tell us at <a href="mailto:<?= e(setting('store_email')) ?>" style="color:var(--brand);"><?= e(setting('store_email')) ?></a> and we'll fix it.</p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
