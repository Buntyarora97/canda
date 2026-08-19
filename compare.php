<?php
/** Full comparison page (reads the visitor's compare list from localStorage). */
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/seo.php';

$all = list_products(['limit' => 50, 'sort' => 'alpha']);

seo_set(['title' => 'Compare Mobility Scooters | ' . SITE_NAME, 'canonical' => site_url('compare')]);
$crumbs = [['label' => 'Home', 'url' => site_url()], ['label' => 'Compare Models', 'url' => null]];
require __DIR__ . '/includes/header.php';
?>

<div class="container">
    <?= render_breadcrumbs($crumbs) ?>
    <div class="page-hero-inner" style="padding:20px 0 26px;">
        <h1 style="font-size:clamp(1.9rem,4vw,2.8rem);">Compare Models</h1>
        <p class="lead" style="color:var(--grey);max-width:60ch;margin:0;">Choose up to three models — differences are highlighted so the right choice is obvious.</p>
    </div>

    <div class="compare-module" style="margin-bottom:90px;">
        <div class="compare-selects">
            <?php for ($slot = 1; $slot <= 3; $slot++): ?>
            <div class="compare-select">
                <label for="cmpSlot<?= $slot ?>">Model <?= $slot ?></label>
                <select id="cmpSlot<?= $slot ?>" class="compare-slot" data-slot="<?= $slot ?>">
                    <option value="">Select a model…</option>
                    <?php foreach ($all as $cp): ?>
                    <option value="<?= (int)$cp['id'] ?>"><?= e($cp['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endfor; ?>
        </div>
        <div class="compare-table-wrap" id="fullCompareWrap">
            <p class="compare-empty">Select at least two models above to compare them here.</p>
        </div>
    </div>
</div>

<script>
(function () {
  const wrap = document.getElementById('fullCompareWrap');
  const slots = Array.from(document.querySelectorAll('.compare-slot'));
  function esc(s){return String(s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));}
  /* Preselect from the visitor's compare tray */
  try {
    const saved = JSON.parse(localStorage.getItem('gio_compare') || '[]');
    saved.slice(0, 3).forEach((id, i) => { if (slots[i]) slots[i].value = id; });
  } catch (e) {}
  function load() {
    const ids = slots.map(s => s.value).filter(Boolean);
    if (ids.length < 2) { wrap.innerHTML = '<p class="compare-empty">Select at least two models above to compare them here.</p>'; return; }
    wrap.innerHTML = '<p class="compare-empty">Loading…</p>';
    fetch('/api/compare.php?ids=' + ids.join(','))
      .then(r => r.json())
      .then(data => {
        const ps = data.products || [];
        if (ps.length < 2) { wrap.innerHTML = '<p class="compare-empty">Select at least two models to compare.</p>'; return; }
        const maxDims = Math.max(...ps.map(p => (p.dimensions || []).length));
        const rowsDef = [
          ['', p => '<img src="' + p.thumb + '" alt="' + esc(p.name) + '" loading="lazy">'],
          ['Model', p => '<span class="model-name">' + esc(p.name) + '</span>'],
          ['Price', p => esc(p.price_label || 'On enquiry')],
          ['Wheel configuration', p => esc(p.wheels || '—')],
          ['Range', p => esc(p.range || '—')],
          ['Top speed', p => esc(p.speed || '—')],
          ['Capacity', p => esc(p.capacity || '—')],
          ['Battery', p => esc(p.battery || '—')],
          ['Key features', p => (p.features || []).slice(0, 6).map(esc).join('<br>') || '—'],
        ];
        for (let d = 0; d < maxDims; d++) {
          rowsDef.push(['Dimensions — ' + esc((ps.find(p => p.dimensions[d]) || {dimensions:[{}]}).dimensions[d].spec_name || ''), p => esc((p.dimensions[d] || {}).spec_value || '—')]);
        }
        let html = '<table class="compare-table"><tbody>';
        rowsDef.forEach(([label, fn]) => {
          const vals = ps.map(p => fn(p));
          const isDiff = new Set(vals).size > 1;
          html += '<tr><th>' + label + '</th>' + vals.map(v => '<td class="' + (isDiff && label ? 'diff' : '') + '">' + v + '</td>').join('') + '</tr>';
        });
        html += '<tr><th></th>' + ps.map(p =>
          '<td><a class="btn btn-outline btn-sm" href="' + p.url + '">View Details</a> ' +
          '<button class="btn btn-primary btn-sm" data-buy-now data-product-id="' + p.id + '">Buy Now</button></td>').join('') + '</tr>';
        wrap.innerHTML = html + '</tbody></table>';
      })
      .catch(() => { wrap.innerHTML = '<p class="compare-empty">Could not load comparison. Please try again.</p>'; });
  }
  slots.forEach(s => s.addEventListener('change', load));
  load();
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
