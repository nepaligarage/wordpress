(function () {
  'use strict';

  var STORAGE_KEY = 'ng_compare_list';
  var MAX = 2;

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
      localStorage.setItem(STORAGE_KEY, JSON.stringify(list));
    } catch (e) {}
  }

  function escapeHtml(str) {
    return String(str == null ? '' : str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function findIndex(list, variantSlug) {
    for (var i = 0; i < list.length; i++) {
      if (list[i].variantSlug === variantSlug) return i;
    }
    return -1;
  }

  var tray = document.getElementById('ng-compare-tray');
  var countEl = document.getElementById('ng-compare-count');
  var slotsEl = document.getElementById('ng-compare-slots');
  var goEl = document.getElementById('ng-compare-go');
  var clearEl = document.getElementById('ng-compare-clear');
  var titleEl = tray ? tray.querySelector('.ng-compare-tray__title') : null;
  var defaultTitle = titleEl ? titleEl.textContent : 'Compare Vehicles';
  var messageTimer = null;

  function showMessage(text) {
    if (!titleEl) return;
    titleEl.textContent = text;
    if (messageTimer) clearTimeout(messageTimer);
    messageTimer = setTimeout(function () {
      titleEl.textContent = defaultTitle;
    }, 2500);
  }

  function renderTray() {
    if (!tray) return;
    var list = readList();

    if (tray.hasAttribute('hidden')) tray.removeAttribute('hidden');

    if (countEl) {
      if (list.length === 2) {
        countEl.textContent = '2/2 — Ready!';
      } else {
        countEl.textContent = list.length + '/2';
      }
    }

    if (slotsEl) {
      var html = '';
      for (var i = 0; i < MAX; i++) {
        var item = list[i];
        if (item) {
          html += '<div class="ng-compare-tray__slot is-filled">';
          if (item.thumb) {
            html += '<img class="ng-compare-tray__slot-thumb" src="' + escapeHtml(item.thumb) + '" alt="' + escapeHtml(item.label) + '">';
          }
          html += '<span class="ng-compare-tray__slot-label">' + escapeHtml(item.label) + '</span>';
          html += '<button type="button" class="ng-compare-tray__slot-remove" data-remove="' + escapeHtml(item.variantSlug) + '" aria-label="Remove ' + escapeHtml(item.label) + '">&times;</button>';
          html += '</div>';
        } else {
          html += '<div class="ng-compare-tray__slot"><span class="ng-compare-tray__slot-empty">Add a vehicle to compare</span></div>';
        }
      }
      slotsEl.innerHTML = html;
    }

    if (goEl) {
      if (list.length === MAX) {
        goEl.setAttribute('aria-disabled', 'false');
      } else {
        goEl.setAttribute('aria-disabled', 'true');
      }
    }

    if (list.length >= 1) {
      tray.classList.add('is-open');
    } else {
      tray.classList.remove('is-open');
    }

    syncButtonLabel(list);
  }

  function syncButtonLabel(list) {
    var btn = document.getElementById('ng-compare-btn');
    if (!btn) return;
    var slug = btn.getAttribute('data-variant-slug') || '';
    if (slug && findIndex(list, slug) !== -1) {
      btn.textContent = 'Added ✓';
    } else {
      btn.textContent = 'Add to Compare';
    }
  }

  function handleCompareClick() {
    var btn = document.getElementById('ng-compare-btn');
    if (!btn) return;

    var variantSlug = btn.getAttribute('data-variant-slug') || '';
    var modelSlug = btn.getAttribute('data-model-slug') || '';
    var brandSlug = btn.getAttribute('data-brand-slug') || '';
    var label = btn.getAttribute('data-model-name') || '';
    var thumb = btn.getAttribute('data-thumb') || '';
    var price = btn.getAttribute('data-price') || '';

    if (!variantSlug) return;

    var list = readList();
    var existing = findIndex(list, variantSlug);

    if (existing !== -1) {
      list.splice(existing, 1);
      saveList(list);
      renderTray();
      return;
    }

    if (list.length >= MAX) {
      showMessage('Remove one vehicle first');
      tray.classList.add('is-open');
      return;
    }

    list.push({
      variantSlug: variantSlug,
      modelSlug: modelSlug,
      brandSlug: brandSlug,
      label: label,
      thumb: thumb,
      price: price,
      url: '/cars/' + brandSlug + '/' + modelSlug + '/'
    });
    saveList(list);
    renderTray();
  }

  document.addEventListener('DOMContentLoaded', function () {
    renderTray();

    var compareBtn = document.getElementById('ng-compare-btn');
    if (compareBtn) {
      compareBtn.addEventListener('click', handleCompareClick);
    }

    if (clearEl) {
      clearEl.addEventListener('click', function () {
        saveList([]);
        renderTray();
      });
    }

    if (slotsEl) {
      slotsEl.addEventListener('click', function (e) {
        var target = e.target.closest('[data-remove]');
        if (!target) return;
        var slug = target.getAttribute('data-remove');
        var list = readList();
        var idx = findIndex(list, slug);
        if (idx !== -1) {
          list.splice(idx, 1);
          saveList(list);
          renderTray();
        }
      });
    }

    if (goEl) {
      goEl.addEventListener('click', function (e) {
        e.preventDefault();
        var list = readList();
        if (list.length !== MAX) return;
        window.location.href = '/compare/?a=' + encodeURIComponent(list[0].variantSlug) + '&b=' + encodeURIComponent(list[1].variantSlug);
      });
    }
  });
})();
