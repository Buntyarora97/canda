<?php
/** Wishlist page — items live in the visitor's browser (localStorage). */
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/seo.php';

seo_set(['title' => 'Your Wishlist | ' . SITE_NAME, 'robots' => 'noindex,follow']);
$crumbs = [['label' => 'Home', 'url' => site_url()], ['label' => 'Wishlist', 'url' => null]];
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <?= render_breadcrumbs($crumbs) ?>
    <div class="page-hero-inner" style="padding:20px 0 26px;">
        <h1 style="font-size:clamp(1.9rem,4vw,2.8rem);">Your Wishlist</h1>
        <p class="lead" style="color:var(--grey);margin:0;">Saved on this device — no account needed.</p>
    </div>
    <div id="wishlistWrap" style="padding-bottom:90px;">
        <div class="product-grid" id="wishlistGrid"></div>
        <div class="empty-state" id="wishlistEmpty" hidden>
            <h3>Your wishlist is empty</h3>
            <p>Tap the heart on any product to save it here for later.</p>
            <a class="btn btn-dark" href="<?= e(site_url('shop')) ?>">Browse products</a>
        </div>
    </div>
</div>

<script>
(function () {
  const grid = document.getElementById('wishlistGrid');
  const empty = document.getElementById('wishlistEmpty');
  function esc(s){return String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
  function load() {
    let ids = [];
    try { ids = JSON.parse(localStorage.getItem('gio_wishlist') || '[]'); } catch (e) {}
    if (!ids.length) { grid.innerHTML = ''; empty.hidden = false; return; }
    fetch('api/compare.php?ids=' + ids.join(','))
      .then(r => r.json())
      .then(data => {
        const ps = data.products || [];
        empty.hidden = ps.length > 0;
        grid.innerHTML = ps.map(p =>
          '<article class="product-card"><a class="card-media" href="' + p.url + '">' +
          '<div class="card-img-wrap"><img class="card-img" src="' + p.thumb + '" alt="' + esc(p.name) + '" loading="lazy"></div></a>' +
          '<div class="card-body"><h3 class="card-title"><a href="' + p.url + '">' + esc(p.name) + '</a></h3>' +
          '<div class="card-price-row"><span class="card-price">' + esc(p.price_label) + '</span></div>' +
          '<div class="card-actions"><a class="btn btn-outline btn-sm" href="' + p.url + '">View Details</a>' +
          '<button class="btn btn-primary btn-sm" data-buy-now data-product-id="' + p.id + '">Buy Now</button></div></div></article>'
        ).join('');
      });
  }
  load();
  document.addEventListener('click', (e) => { if (e.target.closest('[data-wishlist-toggle]')) setTimeout(load, 50); });
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
