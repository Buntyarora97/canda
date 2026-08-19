/* ============================================================
   GIO MOBILITY CANADA — storefront interactions (vanilla JS)
   ============================================================ */
(function () {
  'use strict';

  const $  = (s, c) => (c || document).querySelector(s);
  const $$ = (s, c) => Array.from((c || document).querySelectorAll(s));
  const appUrl = (path) => window.gioUrl ? window.gioUrl(path) : ('/' + String(path).replace(/^\/+/, ''));
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* ---------------- toast ---------------- */
  let toastTimer;
  function toast(msg, isError) {
    const t = $('#toast');
    if (!t) return;
    t.textContent = msg;
    t.classList.toggle('error', !!isError);
    t.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { t.hidden = true; }, 4200);
  }
  window.gioToast = toast;

  /* ---------------- header scroll state ---------------- */
  const header = $('#siteHeader');
  const onScroll = () => header && header.classList.toggle('is-scrolled', window.scrollY > 8);
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });

  /* ---------------- mobile menu ---------------- */
  const navToggle = $('#navToggle');
  const mobileMenu = $('#mobileMenu');
  function setMenu(open) {
    if (!mobileMenu || !navToggle) return;
    mobileMenu.hidden = !open;
    navToggle.setAttribute('aria-expanded', String(open));
    navToggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    document.body.style.overflow = open ? 'hidden' : '';
    requestAnimationFrame(() => mobileMenu.classList.toggle('open', open));
  }
  navToggle && navToggle.addEventListener('click', () => setMenu(mobileMenu.hidden));
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      setMenu(false);
      closeSearch();
      closeEnquiry();
    }
  });

  /* ---------------- search overlay + instant suggestions ---------------- */
  const overlay = $('#searchOverlay');
  const searchInput = $('#searchInput');
  const suggestions = $('#searchSuggestions');
  function openSearch() {
    if (!overlay) return;
    overlay.hidden = false;
    requestAnimationFrame(() => overlay.classList.add('open'));
    document.body.style.overflow = 'hidden';
    setTimeout(() => searchInput && searchInput.focus(), 120);
  }
  function closeSearch() {
    if (!overlay || overlay.hidden) return;
    overlay.classList.remove('open');
    document.body.style.overflow = '';
    setTimeout(() => { overlay.hidden = true; }, 300);
  }
  $('#searchOpen') && $('#searchOpen').addEventListener('click', openSearch);
  $('#searchClose') && $('#searchClose').addEventListener('click', closeSearch);
  overlay && overlay.addEventListener('click', (e) => { if (e.target === overlay) closeSearch(); });

  let searchTimer;
  searchInput && searchInput.addEventListener('input', () => {
    clearTimeout(searchTimer);
    const q = searchInput.value.trim();
    if (q.length < 2) { suggestions.innerHTML = ''; return; }
    searchTimer = setTimeout(async () => {
      try {
        const res = await fetch(appUrl('api/search.php?q=' + encodeURIComponent(q)));
        const data = await res.json();
        renderSuggestions(data.results || [], q);
      } catch (e) { /* network hiccup — stay silent */ }
    }, 220);
  });
  function renderSuggestions(items, q) {
    if (!suggestions) return;
    if (!items.length) {
      suggestions.innerHTML = '<a class="suggestion suggestion-all" href="' + appUrl('search?q=' + encodeURIComponent(q)) + '">Search for &ldquo;' + escHtml(q) + '&rdquo;</a>';
      return;
    }
    suggestions.innerHTML = items.map((p) =>
      '<a class="suggestion" href="' + p.url + '" role="option">' +
        '<img src="' + p.thumb + '" alt="" loading="lazy">' +
        '<span><span class="suggestion-name">' + escHtml(p.name) + '</span><br>' +
        '<span class="suggestion-price">' + escHtml(p.price_label) + '</span></span>' +
      '</a>'
    ).join('') +
    '<a class="suggestion suggestion-all" href="' + appUrl('search?q=' + encodeURIComponent(q)) + '">See all results for &ldquo;' + escHtml(q) + '&rdquo;</a>';
  }
  function escHtml(s) {
    return String(s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  }

  /* ---------------- reveal on scroll ---------------- */
  const revealEls = $$('.reveal');
  if ('IntersectionObserver' in window && !reducedMotion) {
    const io = new IntersectionObserver((entries) => {
      entries.forEach((en) => {
        if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
    revealEls.forEach((el) => io.observe(el));
  } else {
    revealEls.forEach((el) => el.classList.add('in'));
  }

  /* ---------------- animated counters ---------------- */
  $$('.stat b[data-count]').forEach((el) => {
    const target = parseInt(el.dataset.count, 10) || 0;
    if (reducedMotion) { el.textContent = target.toLocaleString('en-CA') + (el.dataset.suffix || ''); return; }
    const io = new IntersectionObserver((ents) => {
      ents.forEach((en) => {
        if (!en.isIntersecting) return;
        io.unobserve(el);
        const t0 = performance.now(), dur = 1400;
        (function tick(now) {
          const p = Math.min(1, (now - t0) / dur);
          const eased = 1 - Math.pow(1 - p, 3);
          el.textContent = Math.round(target * eased).toLocaleString('en-CA') + (el.dataset.suffix || '');
          if (p < 1) requestAnimationFrame(tick);
        })(t0);
      });
    }, { threshold: 0.5 });
    io.observe(el);
  });

  /* ---------------- horizontal carousels ---------------- */
  $$('[data-carousel]').forEach((wrap) => {
    const track = $('.h-scroll', wrap);
    const prev = $('[data-car-prev]', wrap);
    const next = $('[data-car-next]', wrap);
    if (!track || !prev || !next) return;
    const step = () => Math.min(track.clientWidth * 0.8, 700);
    prev.addEventListener('click', () => track.scrollBy({ left: -step(), behavior: 'smooth' }));
    next.addEventListener('click', () => track.scrollBy({ left: step(), behavior: 'smooth' }));
    const update = () => {
      prev.disabled = track.scrollLeft <= 4;
      next.disabled = track.scrollLeft + track.clientWidth >= track.scrollWidth - 4;
    };
    track.addEventListener('scroll', update, { passive: true });
    update();
  });

  /* ---------------- accordions ---------------- */
  $$('.accordion-head').forEach((head) => {
    head.addEventListener('click', () => {
      const acc = head.closest('.accordion');
      const body = $('.accordion-body', acc);
      const open = acc.classList.toggle('open');
      head.setAttribute('aria-expanded', String(open));
      body.style.maxHeight = open ? body.scrollHeight + 'px' : '0px';
    });
  });

  /* ---------------- tabs ---------------- */
  $$('.tab-nav').forEach((nav) => {
    const scope = nav.closest('[data-tabs]') || document;
    $$('.tab-btn', nav).forEach((btn) => {
      btn.addEventListener('click', () => {
        $$('.tab-btn', nav).forEach((b) => { b.classList.remove('active'); b.setAttribute('aria-selected', 'false'); });
        btn.classList.add('active');
        btn.setAttribute('aria-selected', 'true');
        $$('.tab-panel', scope).forEach((p) => p.classList.toggle('active', p.id === btn.dataset.tab));
      });
    });
  });

  /* ---------------- wishlist (localStorage) ---------------- */
  const WL_KEY = 'gio_wishlist';
  const CP_KEY = 'gio_compare';
  const CART_KEY = 'gio_enquiry_cart';
  const getList = (k) => { try { return JSON.parse(localStorage.getItem(k)) || []; } catch (e) { return []; } };
  const setList = (k, v) => localStorage.setItem(k, JSON.stringify(v));

  function refreshCartUI() {
    const cart = getList(CART_KEY);
    const badge = $('#cartCount');
    if (badge) { badge.hidden = cart.length === 0; badge.textContent = cart.length; }
  }
  refreshCartUI();

  function refreshWishlistUI() {
    const list = getList(WL_KEY);
    const badge = $('#wishlistCount');
    if (badge) { badge.hidden = list.length === 0; badge.textContent = list.length; }
    $$('[data-wishlist-toggle]').forEach((btn) => {
      const active = list.includes(parseInt(btn.dataset.wishlistToggle, 10));
      btn.classList.toggle('active', active);
      btn.setAttribute('aria-pressed', String(active));
    });
  }
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-wishlist-toggle]');
    if (!btn) return;
    e.preventDefault();
    const id = parseInt(btn.dataset.wishlistToggle, 10);
    let list = getList(WL_KEY);
    if (list.includes(id)) { list = list.filter((x) => x !== id); toast('Removed from wishlist'); }
    else { list.push(id); toast('Added to your wishlist'); }
    setList(WL_KEY, list);
    refreshWishlistUI();
  });
  refreshWishlistUI();

  /* ---------------- compare (max 3) ---------------- */
  function refreshCompareTray() {
    const tray = $('#compareTray');
    if (!tray) return;
    const list = getList(CP_KEY);
    if (!list.length) { tray.hidden = true; return; }
    fetch(appUrl('api/compare.php?ids=' + list.join(',')))
      .then((r) => r.json())
      .then((data) => {
        const items = $('#compareTrayItems');
        items.innerHTML = (data.products || []).map((p) =>
          '<img src="' + p.thumb + '" alt="' + escHtml(p.name) + '" title="' + escHtml(p.name) + '">'
        ).join('');
        $('#compareTrayCount').textContent = '(' + list.length + ')';
        tray.hidden = false;
      })
      .catch(() => { tray.hidden = true; });
  }
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-compare-toggle]');
    if (!btn) return;
    e.preventDefault();
    const id = parseInt(btn.dataset.compareToggle, 10);
    let list = getList(CP_KEY);
    if (list.includes(id)) { list = list.filter((x) => x !== id); toast('Removed from comparison'); }
    else if (list.length >= 3) { toast('You can compare up to 3 models', true); return; }
    else { list.push(id); toast('Added to comparison'); }
    setList(CP_KEY, list);
    refreshCompareTray();
  });
  const clearBtn = $('#compareClear');
  clearBtn && clearBtn.addEventListener('click', () => { setList(CP_KEY, []); refreshCompareTray(); });
  refreshCompareTray();

  /* ---------------- newsletter ---------------- */
  const nlForm = $('#newsletterForm');
  nlForm && nlForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const msg = $('#newsletterMsg');
    const email = $('#newsletterEmail').value.trim();
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email)) {
      msg.textContent = 'Please enter a valid email address.';
      msg.classList.add('error');
      return;
    }
    try {
      const res = await fetch(appUrl('api/newsletter.php'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'email=' + encodeURIComponent(email),
      });
      const data = await res.json();
      msg.textContent = data.message || 'Thanks for joining!';
      msg.classList.toggle('error', !data.ok);
      if (data.ok) nlForm.reset();
    } catch (err) {
      msg.textContent = 'Something went wrong. Please try again.';
      msg.classList.add('error');
    }
  });

  /* ---------------- cookie notice ---------------- */
  const cookieNotice = $('#cookieNotice');
  if (cookieNotice && !localStorage.getItem('gio_cookies_ok')) {
    setTimeout(() => { cookieNotice.hidden = false; }, 1800);
  }
  $('#cookieAccept') && $('#cookieAccept').addEventListener('click', () => {
    localStorage.setItem('gio_cookies_ok', '1');
    cookieNotice.hidden = true;
  });

  /* ============================================================
     ENQUIRY MODAL (Buy Now flow)
     ============================================================ */
  const modal = $('#enquiryModal');
  const form = $('#enquiryForm');
  let lastFocus = null;

  function fillTracking() {
    const params = new URLSearchParams(location.search);
    $('#fPageUrl').value = location.href;
    $('#fUtmSource').value = params.get('utm_source') || '';
    $('#fUtmMedium').value = params.get('utm_medium') || '';
    $('#fUtmCampaign').value = params.get('utm_campaign') || '';
    $('#fReferrer').value = document.referrer || '';
  }

  async function openEnquiry(productId, opts) {
    if (!modal) return;
    opts = opts || {};
    lastFocus = document.activeElement;
    $('#enquiryFormView').hidden = false;
    $('#enquirySuccessView').hidden = true;
    form.reset();
    clearErrors();
    fillTracking();

    const productCard = $('#enquiryProductCard');
    const generalCard = $('#enquiryGeneralCard');

    if (productId) {
      try {
        const res = await fetch(appUrl('api/product-summary.php?id=' + encodeURIComponent(productId)));
        const data = await res.json();
        if (!data.ok) throw new Error('not found');
        const p = data.product;
        const cart = getList(CART_KEY).filter((item) => item.id !== p.id);
        cart.unshift({ id: p.id, name: p.name, thumb: p.thumb, colour: opts.colour || p.default_colour || '', variant: opts.variant || '' });
        setList(CART_KEY, cart.slice(0, 5));
        refreshCartUI();
        toast('Added to your enquiry cart');
        $('#fProductId').value = p.id;
        $('#fColour').value = (opts.colour || p.default_colour || '');
        $('#fVariant').value = (opts.variant || '');
        $('#enquiryTitle').textContent = 'the ' + p.name + '?';
        $('#enquiryThumb').src = p.thumb;
        $('#enquiryThumb').alt = p.name;
        const bits = [];
        if (p.sku) bits.push('SKU: ' + p.sku);
        const chosen = opts.colour || p.default_colour;
        if (chosen) bits.push('Colour: ' + chosen);
        if (opts.variant) bits.push('Variant: ' + opts.variant);
        if (p.price_label) bits.push(p.price_label);
        $('#enquiryMeta').textContent = bits.join('  •  ');
        productCard.hidden = false;
        generalCard.hidden = true;
      } catch (err) {
        toast('Sorry, that product could not be loaded. Please try again.', true);
        return;
      }
    } else {
      $('#fProductId').value = '';
      $('#fColour').value = '';
      $('#fVariant').value = '';
      productCard.hidden = true;
      generalCard.hidden = false;
    }

    modal.hidden = false;
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(() => modal.classList.add('open'));
    setTimeout(() => { const f = $('#fFirstName'); f && f.focus(); }, 250);
  }
  window.gioOpenEnquiry = openEnquiry;

  function closeEnquiry() {
    if (!modal || modal.hidden) return;
    modal.classList.remove('open');
    document.body.style.overflow = '';
    setTimeout(() => { modal.hidden = true; }, 350);
    if (lastFocus && lastFocus.focus) lastFocus.focus();
  }
  document.addEventListener('click', (e) => {
    if (e.target.closest('[data-modal-close]')) closeEnquiry();
    const buy = e.target.closest('[data-buy-now]');
    if (buy) {
      e.preventDefault();
      openEnquiry(parseInt(buy.dataset.productId, 10), {
        colour: buy.dataset.colour || '',
        variant: buy.dataset.variant || '',
      });
    }
    if (e.target.closest('[data-enquire-general]')) openEnquiry(null);
    if (e.target.closest('[data-cart-open]')) {
      const cart = getList(CART_KEY);
      if (cart[0]) openEnquiry(cart[0].id, { colour: cart[0].colour, variant: cart[0].variant });
      else openEnquiry(null);
    }
  });

  /* focus trap */
  modal && modal.addEventListener('keydown', (e) => {
    if (e.key !== 'Tab') return;
    const focusables = $$('button, input, select, textarea, a[href]', modal).filter((el) => !el.disabled && el.offsetParent !== null);
    if (!focusables.length) return;
    const first = focusables[0], last = focusables[focusables.length - 1];
    if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
    else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
  });

  function clearErrors() {
    $$('.field-error', form).forEach((el) => { el.textContent = ''; });
    $$('.invalid', form).forEach((el) => el.classList.remove('invalid'));
  }
  function showErrors(errors) {
    Object.keys(errors).forEach((name) => {
      const slot = $('[data-error-for="' + name + '"]', form);
      const field = $('[name="' + name + '"]', form);
      if (slot) slot.textContent = errors[name];
      if (field) field.classList.add('invalid');
    });
    const firstBad = $('.invalid', form);
    firstBad && firstBad.focus();
  }

  let submitting = false;
  form && form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (submitting) return; // prevent double submission
    clearErrors();
    submitting = true;
    const btn = $('#enquirySubmit');
    btn.disabled = true;
    $('.btn-label', btn).textContent = 'Sending…';
    $('.btn-spinner', btn).hidden = false;

    try {
      const res = await fetch(appUrl('api/enquiry-submit.php'), {
        method: 'POST',
        body: new FormData(form),
      });
      const data = await res.json();
      if (data.ok) {
        $('#enquiryFormView').hidden = true;
        const sv = $('#enquirySuccessView');
        sv.hidden = false;
        if (data.product_name) {
          $('#successProduct').hidden = false;
          $('#successName').textContent = data.product_name;
          $('#successThumb').src = data.product_thumb || '';
          $('#successRef').textContent = data.reference;
        } else {
          $('#successProduct').hidden = true;
        }
        sv.scrollIntoView({ block: 'nearest' });
      } else if (data.errors) {
        showErrors(data.errors);
      } else {
        toast(data.message || 'Something went wrong. Please try again or call us.', true);
      }
    } catch (err) {
      toast('Network error — please check your connection and try again.', true);
    } finally {
      submitting = false;
      btn.disabled = false;
      $('.btn-label', btn).textContent = 'Send Enquiry';
      $('.btn-spinner', btn).hidden = true;
    }
  });

  /* ---------------- filter drawer (mobile) ---------------- */
  const filterToggle = $('#filterToggle');
  const filterPanel = $('#filterPanel');
  filterToggle && filterToggle.addEventListener('click', () => filterPanel.classList.toggle('open'));
  $('#filterClose') && $('#filterClose').addEventListener('click', () => filterPanel.classList.remove('open'));

  /* ---------------- subtle hero parallax ---------------- */
  const heroMedia = $('.hero-media img');
  if (heroMedia && !reducedMotion) {
    window.addEventListener('scroll', () => {
      const y = window.scrollY;
      if (y < window.innerHeight) {
        heroMedia.style.transform = 'scale(' + (1.08 - Math.min(y / window.innerHeight, 1) * 0.08) + ') translateY(' + y * 0.12 + 'px)';
      }
    }, { passive: true });
  }
})();
