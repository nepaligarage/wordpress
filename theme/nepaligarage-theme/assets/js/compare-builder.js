(function () {
  'use strict';

  var cfg = window.ngCompareBuilder || {};
  var CATALOG = Array.isArray(cfg.catalog) ? cfg.catalog : [];
  var COMPARE_BASE = cfg.compareBase || '/compare/';
  var STORAGE_KEY = 'ng_compare_list'; // shared with compare.js (the global tray)
  var MAX = 2;

  var root = document.getElementById('ng-compare-builder');
  if (!root) return;

  // Per-slot picker filter state.
  var pickerState = [
    { brand: '', body: '', q: '' },
    { brand: '', body: '', q: '' }
  ];

  function readList() {
    try {
      var raw = localStorage.getItem(STORAGE_KEY);
      var list = raw ? JSON.parse(raw) : [];
      return Array.isArray(list) ? list.slice(0, MAX) : [];
    } catch (e) {
      return [];
    }
  }

  function saveList(list) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(list.slice(0, MAX)));
    } catch (e) {}
    // Tell the global tray (compare.js) to re-render.
    window.dispatchEvent(new Event('ng:compare:change'));
  }

  function esc(str) {
    return String(str == null ? '' : str)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }

  function formatPrice(n) {
    var num = parseInt(n, 10);
    if (!num || isNaN(num)) return 'Price on request';
    return 'NPR ' + num.toLocaleString('en-US');
  }

  function entryFromCatalog(cat) {
    return {
      variantSlug: cat.variant_slug,
      modelSlug: cat.model_slug,
      brandSlug: cat.brand_slug,
      label: (cat.brand_name + ' ' + cat.model_name).trim(),
      thumb: cat.thumb || '',
      price: cat.price != null ? String(cat.price) : '',
      url: '/cars/' + cat.brand_slug + '/' + cat.model_slug + '/'
    };
  }

  // Hydrate the saved list against the catalog so URL-loaded slugs get full data.
  function findCatalogByVariant(slug) {
    for (var i = 0; i < CATALOG.length; i++) {
      if (CATALOG[i].variant_slug === slug) return CATALOG[i];
    }
    return null;
  }

  function uniqueSorted(values) {
    var seen = {};
    var out = [];
    values.forEach(function (v) {
      v = (v || '').trim();
      if (v && !seen[v]) { seen[v] = 1; out.push(v); }
    });
    out.sort(function (a, b) { return a.toLowerCase() < b.toLowerCase() ? -1 : 1; });
    return out;
  }

  var BRANDS = uniqueSorted(CATALOG.map(function (c) { return c.brand_name; }));
  var BODIES = uniqueSorted(CATALOG.map(function (c) { return c.body_type; }));

  function updateUrl(list) {
    var qs = '';
    if (list[0]) qs += '?a=' + encodeURIComponent(list[0].variantSlug);
    if (list[1]) qs += (qs ? '&' : '?') + 'b=' + encodeURIComponent(list[1].variantSlug);
    try {
      window.history.replaceState({}, '', COMPARE_BASE + qs);
    } catch (e) {}
  }

  function filterCatalog(slotIndex, list) {
    var st = pickerState[slotIndex];
    var otherModel = list[slotIndex === 0 ? 1 : 0] ? list[slotIndex === 0 ? 1 : 0].modelSlug : null;
    var q = st.q.toLowerCase();
    return CATALOG.filter(function (c) {
      if (otherModel && c.model_slug === otherModel) return false;
      if (st.brand && c.brand_name !== st.brand) return false;
      if (st.body && c.body_type !== st.body) return false;
      if (q) {
        var hay = (c.brand_name + ' ' + c.model_name).toLowerCase();
        if (hay.indexOf(q) === -1) return false;
      }
      return true;
    });
  }

  function renderFilledSlot(slotIndex, item) {
    var thumb = item.thumb
      ? '<img class="ng-cbuilder__card-thumb" src="' + esc(item.thumb) + '" alt="' + esc(item.label) + '">'
      : '<span class="ng-cbuilder__card-thumb ng-cbuilder__card-thumb--placeholder">' + esc((item.label || '?').charAt(0)) + '</span>';
    return '' +
      '<div class="ng-cbuilder__slot is-filled" data-slot="' + slotIndex + '">' +
        '<span class="ng-cbuilder__slot-label">Car ' + (slotIndex === 0 ? 'A' : 'B') + '</span>' +
        '<div class="ng-cbuilder__card">' +
          thumb +
          '<div class="ng-cbuilder__card-body">' +
            '<strong class="ng-cbuilder__card-name">' + esc(item.label) + '</strong>' +
            '<span class="ng-cbuilder__card-price">' + esc(formatPrice(item.price)) + '</span>' +
          '</div>' +
          '<button type="button" class="ng-cbuilder__change" data-change="' + slotIndex + '">Change</button>' +
        '</div>' +
      '</div>';
  }

  function renderPickerSlot(slotIndex, list) {
    var st = pickerState[slotIndex];
    var results = filterCatalog(slotIndex, list);

    var brandOpts = '<option value="">All brands</option>' + BRANDS.map(function (b) {
      return '<option value="' + esc(b) + '"' + (st.brand === b ? ' selected' : '') + '>' + esc(b) + '</option>';
    }).join('');
    var bodyOpts = '<option value="">All body types</option>' + BODIES.map(function (b) {
      return '<option value="' + esc(b) + '"' + (st.body === b ? ' selected' : '') + '>' + esc(b) + '</option>';
    }).join('');

    var resultHtml = '';
    if (!results.length) {
      resultHtml = '<p class="ng-cbuilder__empty">No vehicles match these filters.</p>';
    } else {
      resultHtml = results.slice(0, 80).map(function (c) {
        var price = c.price != null ? formatPrice(c.price) : 'Price on request';
        return '' +
          '<button type="button" class="ng-cbuilder__result" data-pick="' + slotIndex + '" data-variant="' + esc(c.variant_slug) + '">' +
            '<span class="ng-cbuilder__result-name">' + esc((c.brand_name + ' ' + c.model_name).trim()) + '</span>' +
            '<span class="ng-cbuilder__result-meta">' + esc(c.body_type || (c.is_ev ? 'EV' : '')) + ' · ' + esc(price) + '</span>' +
          '</button>';
      }).join('');
    }

    return '' +
      '<div class="ng-cbuilder__slot is-empty" data-slot="' + slotIndex + '">' +
        '<span class="ng-cbuilder__slot-label">Car ' + (slotIndex === 0 ? 'A' : 'B') + '</span>' +
        '<div class="ng-cbuilder__picker">' +
          '<div class="ng-cbuilder__filters">' +
            '<select class="ng-cbuilder__filter" data-filter="brand" data-slot="' + slotIndex + '" aria-label="Filter by brand">' + brandOpts + '</select>' +
            '<select class="ng-cbuilder__filter" data-filter="body" data-slot="' + slotIndex + '" aria-label="Filter by body type">' + bodyOpts + '</select>' +
          '</div>' +
          '<input type="search" class="ng-cbuilder__search" data-slot="' + slotIndex + '" value="' + esc(st.q) + '" placeholder="Search by model name…" aria-label="Search vehicles">' +
          '<div class="ng-cbuilder__results">' + resultHtml + '</div>' +
        '</div>' +
      '</div>';
  }

  function render() {
    var list = readList();
    updateUrl(list);

    var slotsHtml = '';
    for (var i = 0; i < MAX; i++) {
      slotsHtml += list[i] ? renderFilledSlot(i, list[i]) : renderPickerSlot(i, list);
    }

    var ready = list.length === MAX;
    var ctaHtml;
    if (ready) {
      var url = COMPARE_BASE + '?a=' + encodeURIComponent(list[0].variantSlug) + '&b=' + encodeURIComponent(list[1].variantSlug);
      ctaHtml = '<a class="ng-btn ng-btn--red ng-btn--lg ng-cbuilder__go" href="' + esc(url) + '">Compare these two →</a>';
    } else {
      var need = MAX - list.length;
      ctaHtml = '<button class="ng-btn ng-btn--red ng-btn--lg ng-cbuilder__go" type="button" disabled>Pick ' + need + ' more car' + (need === 1 ? '' : 's') + '</button>';
    }

    var clearHtml = list.length
      ? '<button type="button" class="ng-cbuilder__clear" data-clear>Clear selection</button>'
      : '';

    root.innerHTML = '' +
      '<div class="ng-cbuilder__slots">' + slotsHtml +
        '<div class="ng-cbuilder__vs" aria-hidden="true">VS</div>' +
      '</div>' +
      '<div class="ng-cbuilder__actions">' + ctaHtml + clearHtml + '</div>';
  }

  // Resolve a slot index that currently holds a filled item, given a model to keep.
  function removeSlot(slotIndex) {
    var list = readList();
    if (list[slotIndex]) {
      list.splice(slotIndex, 1);
      saveList(list);
    }
    pickerState[slotIndex] = { brand: '', body: '', q: '' };
    render();
  }

  function pick(slotIndex, variantSlug) {
    var cat = findCatalogByVariant(variantSlug);
    if (!cat) return;
    var list = readList();
    var entry = entryFromCatalog(cat);

    // Guard against duplicate model in the other slot.
    var other = list[slotIndex === 0 ? 1 : 0];
    if (other && other.modelSlug === entry.modelSlug) return;

    list[slotIndex] = entry;
    // Compact: ensure no holes (if slot 0 empty but slot 1 filled, keep positions).
    saveList(list);
    render();
  }

  root.addEventListener('click', function (e) {
    var pickBtn = e.target.closest('[data-pick]');
    if (pickBtn) {
      pick(parseInt(pickBtn.getAttribute('data-pick'), 10), pickBtn.getAttribute('data-variant'));
      return;
    }
    var changeBtn = e.target.closest('[data-change]');
    if (changeBtn) {
      removeSlot(parseInt(changeBtn.getAttribute('data-change'), 10));
      return;
    }
    if (e.target.closest('[data-clear]')) {
      saveList([]);
      pickerState = [{ brand: '', body: '', q: '' }, { brand: '', body: '', q: '' }];
      render();
    }
  });

  root.addEventListener('change', function (e) {
    var sel = e.target.closest('[data-filter]');
    if (!sel) return;
    var slot = parseInt(sel.getAttribute('data-slot'), 10);
    pickerState[slot][sel.getAttribute('data-filter')] = sel.value;
    render();
  });

  var searchTimer = null;
  root.addEventListener('input', function (e) {
    var input = e.target.closest('.ng-cbuilder__search');
    if (!input) return;
    var slot = parseInt(input.getAttribute('data-slot'), 10);
    var val = input.value;
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(function () {
      pickerState[slot].q = val;
      render();
      // Restore focus + caret to the search field after re-render.
      var fresh = root.querySelector('.ng-cbuilder__search[data-slot="' + slot + '"]');
      if (fresh) { fresh.focus(); fresh.setSelectionRange(val.length, val.length); }
    }, 200);
  });

  // On first load, hydrate any URL-provided slugs (?a=&b=) into the saved list
  // so a shared link pre-fills the builder even if localStorage is empty.
  function hydrateFromUrl() {
    var params = new URLSearchParams(window.location.search);
    var a = params.get('a');
    var b = params.get('b');
    if (!a && !b) return;
    var list = readList();
    var slugs = [a, b];
    var next = [];
    for (var i = 0; i < MAX; i++) {
      if (slugs[i]) {
        var cat = findCatalogByVariant(slugs[i]);
        if (cat) { next[i] = entryFromCatalog(cat); continue; }
      }
      if (list[i]) next[i] = list[i];
    }
    next = next.filter(Boolean);
    if (next.length) saveList(next);
  }

  hydrateFromUrl();
  render();
})();
