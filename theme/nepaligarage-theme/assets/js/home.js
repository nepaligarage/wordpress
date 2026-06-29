/**
 * Homepage hero — tabbed search widget (Find a car / Estimate price / Compare).
 * Pure client-side routing; no Supabase calls. Catalog + URLs come from `ngHome`
 * (localized in functions.php via ngt_compare_catalog()).
 */
(function () {
    'use strict';

    var cfg = window.ngHome || {};
    var CATALOG     = Array.isArray(cfg.catalog) ? cfg.catalog : [];
    var CARS_BASE   = cfg.carsBase    || '/cars/';
    var NEWCARS_URL = cfg.newCarsBase || '/new-cars/';
    var COMPARE_URL = cfg.compareBase || '/compare/';

    function esc(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // ── Tabs ───────────────────────────────────────────────────────────────────
    var tabs   = Array.prototype.slice.call(document.querySelectorAll('.ng-herosearch__tab'));
    var panels = Array.prototype.slice.call(document.querySelectorAll('.ng-herosearch__panel'));

    function activateTab(key) {
        tabs.forEach(function (t) {
            var on = t.getAttribute('data-hstab') === key;
            t.classList.toggle('is-active', on);
            t.setAttribute('aria-selected', on ? 'true' : 'false');
            t.tabIndex = on ? 0 : -1;
        });
        panels.forEach(function (p) {
            var on = p.getAttribute('data-hspanel') === key;
            p.classList.toggle('is-active', on);
            p.hidden = !on;
        });
    }

    tabs.forEach(function (t) {
        t.addEventListener('click', function () { activateTab(t.getAttribute('data-hstab')); });
        t.addEventListener('keydown', function (e) {
            if (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft') return;
            e.preventDefault();
            var i = tabs.indexOf(t);
            var next = e.key === 'ArrowRight' ? (i + 1) % tabs.length : (i - 1 + tabs.length) % tabs.length;
            tabs[next].focus();
            activateTab(tabs[next].getAttribute('data-hstab'));
        });
    });

    // ── Catalog helpers ─────────────────────────────────────────────────────────
    function uniqueBrands() {
        var seen = {}, out = [];
        CATALOG.forEach(function (c) {
            if (c.brand_slug && !seen[c.brand_slug]) {
                seen[c.brand_slug] = 1;
                out.push({ slug: c.brand_slug, name: c.brand_name });
            }
        });
        out.sort(function (a, b) { return a.name.toLowerCase() < b.name.toLowerCase() ? -1 : 1; });
        return out;
    }
    var BRANDS = uniqueBrands();

    function fillMakes(sel) {
        if (!sel) return;
        var html = '<option value="">Make</option>';
        BRANDS.forEach(function (b) {
            html += '<option value="' + esc(b.slug) + '">' + esc(b.name) + '</option>';
        });
        sel.innerHTML = html;
    }

    function fillModels(sel, brandSlug) {
        if (!sel) return;
        if (!brandSlug) {
            sel.innerHTML = '<option value="">Model</option>';
            sel.disabled = true;
            return;
        }
        var models = CATALOG.filter(function (c) { return c.brand_slug === brandSlug; })
            .sort(function (a, b) { return a.model_name.toLowerCase() < b.model_name.toLowerCase() ? -1 : 1; });
        var html = '<option value="">Model</option>';
        models.forEach(function (m) {
            html += '<option value="' + esc(m.variant_slug) + '">' + esc(m.model_name) + '</option>';
        });
        sel.innerHTML = html;
        sel.disabled = false;
    }

    function findByVariant(slug) {
        for (var i = 0; i < CATALOG.length; i++) {
            if (CATALOG[i].variant_slug === slug) return CATALOG[i];
        }
        return null;
    }

    // Wire a Make → Model dependent pair; returns the two elements.
    function wirePair(makeId, modelId) {
        var make  = document.getElementById(makeId);
        var model = document.getElementById(modelId);
        fillMakes(make);
        fillModels(model, '');
        if (make) {
            make.addEventListener('change', function () { fillModels(model, make.value); });
        }
        return { make: make, model: model };
    }

    function go(url) { window.location.href = url; }

    // ── Find a car ───────────────────────────────────────────────────────────────
    var find = wirePair('ng-hs-find-make', 'ng-hs-find-model');
    var findGo = document.getElementById('ng-hs-find-go');
    if (findGo) {
        findGo.addEventListener('click', function () {
            if (!find.make) return;
            var variant = find.model && find.model.value;
            if (variant) {
                var cat = findByVariant(variant);
                if (cat) { go(CARS_BASE + cat.brand_slug + '/' + cat.model_slug + '/'); return; }
            }
            if (find.make.value) { go(NEWCARS_URL + '#brand=' + encodeURIComponent(find.make.value)); return; }
            go(NEWCARS_URL);
        });
    }

    // ── Compare ──────────────────────────────────────────────────────────────────
    var cmpA = wirePair('ng-hs-cmp-a-make', 'ng-hs-cmp-a-model');
    var cmpB = wirePair('ng-hs-cmp-b-make', 'ng-hs-cmp-b-model');
    var cmpGo = document.getElementById('ng-hs-cmp-go');
    if (cmpGo) {
        cmpGo.addEventListener('click', function () {
            var a = cmpA.model && cmpA.model.value;
            var b = cmpB.model && cmpB.model.value;
            if (a && b)      { go(COMPARE_URL + '?a=' + encodeURIComponent(a) + '&b=' + encodeURIComponent(b)); }
            else if (a || b) { go(COMPARE_URL + '?a=' + encodeURIComponent(a || b)); }
            else             { go(COMPARE_URL); }
        });
    }
})();
