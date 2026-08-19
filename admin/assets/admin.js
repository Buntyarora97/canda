/* GIO Mobility — admin interactions */
(function () {
  'use strict';

  /* sidebar toggle (mobile) */
  var burger = document.getElementById('adminBurger');
  var side = document.getElementById('adminSide');
  if (burger && side) {
    burger.addEventListener('click', function () { side.classList.toggle('open'); });
    document.addEventListener('click', function (e) {
      if (side.classList.contains('open') && !side.contains(e.target) && !burger.contains(e.target)) {
        side.classList.remove('open');
      }
    });
  }

  /* confirm-on-submit forms */
  document.querySelectorAll('form[data-confirm]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!window.confirm(f.getAttribute('data-confirm'))) e.preventDefault();
    });
  });

  /* copy buttons */
  document.querySelectorAll('.copy-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var text = btn.getAttribute('data-copy');
      var done = function () { btn.textContent = '✓'; setTimeout(function () { btn.textContent = '⧉'; }, 1400); };
      if (navigator.clipboard) navigator.clipboard.writeText(text).then(done);
      else {
        var ta = document.createElement('textarea');
        ta.value = text; document.body.appendChild(ta); ta.select();
        document.execCommand('copy'); ta.remove(); done();
      }
    });
  });

  /* repeater rows */
  document.querySelectorAll('[data-add]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var block = document.getElementById(btn.getAttribute('data-add'));
      if (!block) return;
      var tpl = document.createElement('template');
      tpl.innerHTML = btn.getAttribute('data-row').trim();
      block.appendChild(tpl.content.firstChild);
    });
  });
  document.addEventListener('click', function (e) {
    if (e.target.classList && e.target.classList.contains('rep-del')) {
      var row = e.target.closest('.rep-row');
      if (row) row.remove();
    }
  });

  /* check-all */
  var checkAll = document.getElementById('checkAll');
  if (checkAll) {
    checkAll.addEventListener('change', function () {
      document.querySelectorAll('input[name="ids[]"]').forEach(function (cb) { cb.checked = checkAll.checked; });
    });
  }

  /* image reorder via drag & drop */
  var grid = document.querySelector('.img-grid');
  var reorderForm = document.getElementById('reorderForm');
  if (grid && reorderForm) {
    var dragged = null;
    grid.querySelectorAll('.img-card').forEach(function (card) {
      card.draggable = true;
      card.addEventListener('dragstart', function () { dragged = card; card.style.opacity = '.45'; });
      card.addEventListener('dragend', function () { card.style.opacity = ''; syncOrder(); });
      card.addEventListener('dragover', function (e) {
        e.preventDefault();
        if (!dragged || dragged === card) return;
        var cards = Array.prototype.slice.call(grid.querySelectorAll('.img-card'));
        if (cards.indexOf(dragged) < cards.indexOf(card)) card.after(dragged); else card.before(dragged);
      });
    });
    function syncOrder() {
      var holder = document.getElementById('orderInputs');
      holder.innerHTML = '';
      grid.querySelectorAll('.img-card').forEach(function (card, i) {
        var inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'order[' + i + ']';
        inp.value = card.getAttribute('data-img-id');
        holder.appendChild(inp);
      });
    }
    syncOrder();
  }

  /* dashboard trend chart (no dependencies) */
  var canvas = document.getElementById('trendChart');
  if (canvas) {
    var labels = JSON.parse(canvas.getAttribute('data-labels') || '[]');
    var values = JSON.parse(canvas.getAttribute('data-values') || '[]');
    var dpr = window.devicePixelRatio || 1;
    var w = canvas.parentNode.clientWidth - 48, h = 220;
    canvas.width = w * dpr; canvas.height = h * dpr;
    canvas.style.width = w + 'px'; canvas.style.height = h + 'px';
    var ctx = canvas.getContext('2d'); ctx.scale(dpr, dpr);
    var padL = 28, padB = 22, padT = 12, padR = 6;
    var max = Math.max(1, Math.max.apply(null, values));
    var plotW = w - padL - padR, plotH = h - padT - padB;
    ctx.strokeStyle = '#E6E7E8'; ctx.fillStyle = '#70747A'; ctx.font = '10.5px Inter, sans-serif';
    for (var g = 0; g <= 3; g++) {
      var y = padT + plotH - (plotH * g / 3);
      ctx.beginPath(); ctx.moveTo(padL, y); ctx.lineTo(w - padR, y); ctx.stroke();
      ctx.fillText(String(Math.round(max * g / 3)), 2, y + 3);
    }
    ctx.fillStyle = '#70747A';
    for (var i = 0; i < labels.length; i += 5) {
      var x = padL + plotW * i / Math.max(1, labels.length - 1);
      ctx.fillText(labels[i], Math.min(x, w - 34), h - 6);
    }
    if (values.length) {
      var grad = ctx.createLinearGradient(0, padT, 0, h - padB);
      grad.addColorStop(0, 'rgba(216,35,42,.22)'); grad.addColorStop(1, 'rgba(216,35,42,0)');
      ctx.beginPath();
      values.forEach(function (v, i) {
        var x = padL + plotW * i / Math.max(1, values.length - 1);
        var y = padT + plotH - plotH * v / max;
        i === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
      });
      ctx.strokeStyle = '#D8232A'; ctx.lineWidth = 2.2; ctx.stroke();
      ctx.lineTo(padL + plotW, padT + plotH); ctx.lineTo(padL, padT + plotH); ctx.closePath();
      ctx.fillStyle = grad; ctx.fill();
    }
  }
})();
