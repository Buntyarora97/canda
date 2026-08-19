/* ============================================================
   GIO — product detail page: gallery, zoom, lightbox, variants
   ============================================================ */
(function () {
  'use strict';
  const $  = (s, c) => (c || document).querySelector(s);
  const $$ = (s, c) => Array.from((c || document).querySelectorAll(s));
  const P = window.GIO_PRODUCT;
  if (!P) return;

  const main      = $('#pdpMain');
  const mainImg   = $('#pdpMainImg');
  const zoom      = $('#pdpZoom');
  const counter   = $('#pdpCounter');
  const thumbs    = $$('[data-gallery-thumb]');
  const lightbox  = $('#lightbox');
  const lbImg     = $('#lightboxImg');
  const lbCounter = $('#lbCounter');
  let current = 0;

  /* ---------------- gallery switching ---------------- */
  function show(i) {
    const items = P.images;
    if (!items.length) return;
    current = (i + items.length) % items.length;
    const item = items[current];
    counter.textContent = (current + 1) + ' / ' + items.length;
    thumbs.forEach((t, j) => { t.classList.toggle('active', j === current); t.setAttribute('aria-selected', String(j === current)); });

    if (item.type === 'video') {
      const src = item.provider === 'youtube'
        ? 'https://www.youtube-nocookie.com/embed/' + item.id + '?autoplay=1&rel=0'
        : 'https://player.vimeo.com/video/' + item.id + '?autoplay=1';
      main.innerHTML = '<iframe src="' + src + '" style="position:absolute;inset:0;width:100%;height:100%;border:0;" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen title="Product video"></iframe>'
        + main.querySelector('.pdp-counter').outerHTML;
      return;
    }
    // Ensure the image element exists (restore after video).
    if (!$('#pdpMainImg')) {
      main.insertAdjacentHTML('afterbegin', '<img id="pdpMainImg" alt="" width="1000" height="1000"><div class="pdp-zoom" id="pdpZoom" aria-hidden="true"></div>');
      bindZoom();
    }
    const img = $('#pdpMainImg');
    img.src = item.src;
    img.alt = item.alt || '';
    $('#pdpZoom').style.backgroundImage = 'url("' + item.src + '")';
  }
  thumbs.forEach((t) => t.addEventListener('click', () => show(parseInt(t.dataset.galleryThumb, 10))));

  /* ---------------- hover zoom (desktop) ---------------- */
  function bindZoom() {
    const z = $('#pdpZoom');
    if (!z) return;
    main.addEventListener('mousemove', (e) => {
      const r = main.getBoundingClientRect();
      const x = ((e.clientX - r.left) / r.width) * 100;
      const y = ((e.clientY - r.top) / r.height) * 100;
      z.style.backgroundPosition = x + '% ' + y + '%';
    });
  }
  zoom && (zoom.style.backgroundSize = '220%');
  bindZoom();

  /* ---------------- swipe (mobile) ---------------- */
  let touchX = null;
  main.addEventListener('touchstart', (e) => { touchX = e.touches[0].clientX; }, { passive: true });
  main.addEventListener('touchend', (e) => {
    if (touchX === null) return;
    const dx = e.changedTouches[0].clientX - touchX;
    if (Math.abs(dx) > 45) show(current + (dx < 0 ? 1 : -1));
    touchX = null;
  }, { passive: true });

  /* ---------------- lightbox ---------------- */
  function openLb() {
    const item = P.images[current];
    if (!item || item.type !== 'image') return;
    lbImg.src = item.src;
    lbImg.alt = item.alt || '';
    lbCounter.textContent = (current + 1) + ' / ' + P.images.length;
    lightbox.hidden = false;
    requestAnimationFrame(() => lightbox.classList.add('open'));
    document.body.style.overflow = 'hidden';
  }
  function closeLb() {
    lightbox.classList.remove('open');
    document.body.style.overflow = '';
    setTimeout(() => { lightbox.hidden = true; }, 300);
  }
  function lbNav(dir) {
    let i = current;
    do { i = (i + dir + P.images.length) % P.images.length; } while (P.images[i].type !== 'image');
    show(i);
    openLb();
  }
  $('#pdpExpand').addEventListener('click', (e) => { e.stopPropagation(); openLb(); });
  main.addEventListener('click', (e) => { if (e.target.id === 'pdpMainImg') openLb(); });
  $('[data-lb-close]').addEventListener('click', closeLb);
  $('[data-lb-prev]').addEventListener('click', () => lbNav(-1));
  $('[data-lb-next]').addEventListener('click', () => lbNav(1));
  lightbox.addEventListener('click', (e) => { if (e.target === lightbox) closeLb(); });
  document.addEventListener('keydown', (e) => {
    if (lightbox.hidden) return;
    if (e.key === 'Escape') closeLb();
    if (e.key === 'ArrowLeft') lbNav(-1);
    if (e.key === 'ArrowRight') lbNav(1);
  });

  /* ---------------- variant selection → Buy Now payload ---------------- */
  let selectedColour = '';
  let selectedOption = '';
  function syncBuyButtons() {
    $$('[data-buy-now][data-product-id]').forEach((btn) => {
      if (parseInt(btn.dataset.productId, 10) !== P.id) return;
      btn.dataset.colour = selectedColour;
      btn.dataset.variant = selectedOption;
    });
  }
  $$('.colour-option[data-colour]').forEach((btn) => {
    btn.addEventListener('click', () => {
      $$('.colour-option[data-colour]').forEach((b) => { b.classList.remove('active'); b.setAttribute('aria-pressed', 'false'); });
      btn.classList.add('active');
      btn.setAttribute('aria-pressed', 'true');
      selectedColour = btn.dataset.colour;
      const nameEl = $('#colourName');
      if (nameEl) nameEl.textContent = selectedColour;
      syncBuyButtons();
    });
  });
  $$('.option-pill').forEach((btn) => {
    btn.addEventListener('click', () => {
      $$('.option-pill').forEach((b) => b.classList.remove('active'));
      btn.classList.add('active');
      selectedOption = btn.dataset.option;
      syncBuyButtons();
    });
  });
  const firstColour = $('.colour-option[data-colour]');
  if (firstColour) { selectedColour = firstColour.dataset.colour; }
  const firstOption = $('.option-pill');
  if (firstOption) { selectedOption = firstOption.dataset.option; }
  syncBuyButtons();

  /* ---------------- share ---------------- */
  const shareBtn = $('#pdpShare');
  shareBtn && shareBtn.addEventListener('click', async () => {
    const data = { title: document.title, url: location.href };
    if (navigator.share) { try { await navigator.share(data); } catch (e) {} }
    else {
      try { await navigator.clipboard.writeText(location.href); window.gioToast('Link copied to clipboard'); }
      catch (e) { window.gioToast('Copy this link: ' + location.href); }
    }
  });

  /* ---------------- show sticky bar on PDP (mobile) ---------------- */
  const sticky = $('#pdpStickyBar');
  if (sticky && window.matchMedia('(max-width: 720px)').matches) sticky.style.display = 'flex';
})();
