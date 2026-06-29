(function () {
    'use strict';

    var cards = [];
    var state = {
        type:   '',
        brands: [],
        fuel:   '',
        year:   '',
        price:  '',
        sort:   'year-desc'
    };

    var PRICE_BANDS = {
        'under-5l':  [0,        500000],
        '5l-15l':    [500000,   1500000],
        '15l-50l':   [1500000,  5000000],
        '50l-1cr':   [5000000,  10000000],
        'above-1cr': [10000000, Infinity]
    };

    function init() {
        document.querySelectorAll('.ng-vehicle-card[data-type]').forEach(function (el) {
            cards.push({
                el:       el,
                brand:    el.dataset.brand    || '',
                bodyType: el.dataset.bodyType || '',
                type:     el.dataset.type     || '',
                fuel:     el.dataset.fuel     || '',
                year:     el.dataset.year     || '',
                price:    parseFloat(el.dataset.price) || 0,
                name:     (el.querySelector('.ng-vehicle-card__name') || {}).textContent || ''
            });
        });

        restoreFromHash();
        bindPills();
        bindSorts();
        bindDrawer();
        bindAccordions();
        applyFilters();
    }

    // ── Accordion ──────────────────────────────────────────────────────────────

    function bindAccordions() {
        document.querySelectorAll('.ng-sb-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var section = btn.closest('.ng-sb-section');
                if (section) section.classList.toggle('is-open');
            });
        });
    }

    // ── Pill + checkbox bindings ───────────────────────────────────────────────

    function bindPills() {
        // Button-style pills (sidebar radio buttons + mobile drawer pills)
        document.querySelectorAll('button[data-filter]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var filter = btn.dataset.filter;
                var value  = btn.dataset.value;

                if (filter === 'brand') {
                    var idx = state.brands.indexOf(value);
                    if (idx === -1) {
                        state.brands.push(value);
                    } else {
                        state.brands.splice(idx, 1);
                    }
                } else if (value === '') {
                    state[filter] = '';
                } else {
                    state[filter] = state[filter] === value ? '' : value;
                }

                updatePillStates();
                applyFilters();
                updateHash();
            });
        });

        // Checkbox inputs (sidebar brand multi-select)
        document.querySelectorAll('input[data-filter]').forEach(function (input) {
            input.addEventListener('change', function () {
                var value = input.dataset.value;
                if (input.checked) {
                    if (state.brands.indexOf(value) === -1) state.brands.push(value);
                } else {
                    var idx = state.brands.indexOf(value);
                    if (idx !== -1) state.brands.splice(idx, 1);
                }
                updatePillStates();
                applyFilters();
                updateHash();
            });
        });
    }

    function bindSorts() {
        document.querySelectorAll('.ng-filter-sort').forEach(function (el) {
            el.addEventListener('change', function () {
                state.sort = el.value;
                document.querySelectorAll('.ng-filter-sort').forEach(function (s) { s.value = state.sort; });
                applyFilters();
                updateHash();
            });
        });
    }

    // ── Drawer bindings ────────────────────────────────────────────────────────

    function bindDrawer() {
        var drawer   = document.getElementById('ngFilterDrawer');
        var overlay  = document.getElementById('ngFilterOverlay');
        var openBtn  = document.getElementById('ngFilterDrawerOpen');
        var closeBtn = document.getElementById('ngFilterDrawerClose');
        var applyBtn = document.getElementById('ngFilterDrawerApply');

        function openDrawer() {
            if (drawer)  drawer.classList.add('is-open');
            if (overlay) overlay.classList.add('is-open');
            document.body.style.overflow = 'hidden';
        }
        function closeDrawer() {
            if (drawer)  drawer.classList.remove('is-open');
            if (overlay) overlay.classList.remove('is-open');
            document.body.style.overflow = '';
        }

        if (openBtn)  openBtn.addEventListener('click',  openDrawer);
        if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
        if (applyBtn) applyBtn.addEventListener('click', closeDrawer);
        if (overlay)  overlay.addEventListener('click',  closeDrawer);

        document.querySelectorAll('.ng-filter-clear-all').forEach(function (btn) {
            btn.addEventListener('click', function () {
                state.type   = '';
                state.brands = [];
                state.fuel   = '';
                state.year   = '';
                state.price  = '';
                updatePillStates();
                applyFilters();
                updateHash();
            });
        });
    }

    // ── Filter + sort ──────────────────────────────────────────────────────────

    function applyFilters() {
        var total   = cards.length;
        var visible = 0;

        cards.forEach(function (c) {
            var show = matchesFilters(c);
            c.el.hidden = !show;
            if (show) visible++;
        });

        // Re-order each section grid independently
        document.querySelectorAll('.ng-listing-section').forEach(function (section) {
            var grid = section.querySelector('.ng-listing-grid');
            if (!grid) return;
            var sectionCards = cards.filter(function (c) { return grid.contains(c.el); });
            sortCards(sectionCards).forEach(function (c) { grid.appendChild(c.el); });
            var anyVisible = sectionCards.some(function (c) { return !c.el.hidden; });
            section.hidden = !anyVisible;
        });

        var countEl = document.getElementById('ngResultsCount');
        if (countEl) countEl.textContent = 'Showing ' + visible + ' of ' + total + ' vehicles';

        var badge = document.getElementById('ngFilterCount');
        if (badge) {
            var n = countActiveFilters();
            badge.textContent = n;
            badge.hidden = n === 0;
        }

        renderActiveChips();
        updateSectionCounts();
    }

    function matchesFilters(c) {
        if (state.type   && c.type !== state.type) return false;
        if (state.brands.length && state.brands.indexOf(c.brand) === -1) return false;
        if (state.fuel   && c.fuel !== state.fuel) return false;
        if (state.year   && c.year !== state.year) return false;
        if (state.price) {
            var band = PRICE_BANDS[state.price];
            if (band) {
                if (c.price <= 0 || c.price < band[0] || c.price >= band[1]) return false;
            }
        }
        return true;
    }

    function sortCards(arr) {
        return arr.slice().sort(function (a, b) {
            switch (state.sort) {
                case 'price-asc':
                    if (!a.price && !b.price) return 0;
                    if (!a.price) return 1;
                    if (!b.price) return -1;
                    return a.price - b.price;
                case 'price-desc':
                    if (!a.price && !b.price) return 0;
                    if (!a.price) return 1;
                    if (!b.price) return -1;
                    return b.price - a.price;
                case 'az':
                    return a.name.localeCompare(b.name);
                default: // year-desc
                    return (parseInt(b.year) || 0) - (parseInt(a.year) || 0);
            }
        });
    }

    // ── UI state sync ──────────────────────────────────────────────────────────

    function updatePillStates() {
        // Button pills
        document.querySelectorAll('button[data-filter]').forEach(function (btn) {
            var filter = btn.dataset.filter;
            var value  = btn.dataset.value;
            var active = filter === 'brand'
                ? state.brands.indexOf(value) !== -1
                : state[filter] === value;
            btn.classList.toggle('ng-filter-pill--active', active);
        });

        // Checkbox inputs
        document.querySelectorAll('input[data-filter]').forEach(function (input) {
            input.checked = state.brands.indexOf(input.dataset.value) !== -1;
        });
    }

    function updateSectionCounts() {
        document.querySelectorAll('[data-filter-section]').forEach(function (section) {
            var filter = section.dataset.filterSection;
            var count  = filter === 'brand' ? state.brands.length : (state[filter] ? 1 : 0);
            var badge  = section.querySelector('.ng-sb-count');
            if (badge) {
                badge.textContent = count;
                badge.hidden = count === 0;
            }
        });
    }

    function renderActiveChips() {
        var row = document.getElementById('ngFilterActiveRow');
        if (!row) return;

        var chips = [];
        var LABELS = {
            type: { car: 'Cars & SUVs', bike: 'Bikes' },
            fuel: { petrol: 'Petrol', diesel: 'Diesel', ev: 'EV', hybrid: 'Hybrid' }
        };

        if (state.type)  chips.push({ label: LABELS.type[state.type] || state.type, key: 'type' });
        state.brands.forEach(function (b) {
            var label = b.charAt(0).toUpperCase() + b.slice(1).replace(/-/g, ' ');
            chips.push({ label: label, key: 'brand', value: b });
        });
        if (state.fuel)  chips.push({ label: LABELS.fuel[state.fuel] || state.fuel, key: 'fuel' });
        if (state.year)  chips.push({ label: state.year, key: 'year' });
        if (state.price) chips.push({ label: state.price.replace(/-/g, ' '), key: 'price' });

        row.innerHTML = '';
        chips.forEach(function (chip) {
            var btn = document.createElement('button');
            btn.className   = 'ng-filter-active-chip';
            btn.textContent = chip.label + ' ×';
            btn.addEventListener('click', function () {
                if (chip.key === 'brand') {
                    var idx = state.brands.indexOf(chip.value);
                    if (idx !== -1) state.brands.splice(idx, 1);
                } else {
                    state[chip.key] = '';
                }
                updatePillStates();
                applyFilters();
                updateHash();
            });
            row.appendChild(btn);
        });
        row.hidden = chips.length === 0;
    }

    function countActiveFilters() {
        return (state.type ? 1 : 0) + state.brands.length +
               (state.fuel ? 1 : 0) + (state.year ? 1 : 0) + (state.price ? 1 : 0);
    }

    // ── Hash state ─────────────────────────────────────────────────────────────

    function updateHash() {
        var parts = [];
        if (state.type)          parts.push('type='  + state.type);
        if (state.brands.length) parts.push('brand=' + state.brands.join(','));
        if (state.fuel)          parts.push('fuel='  + state.fuel);
        if (state.year)          parts.push('year='  + state.year);
        if (state.price)         parts.push('price=' + state.price);
        if (state.sort !== 'year-desc') parts.push('sort=' + state.sort);
        var hash = parts.length ? '#' + parts.join('&') : window.location.pathname + window.location.search;
        history.replaceState(null, '', hash);
    }

    function restoreFromHash() {
        var hash = window.location.hash.replace(/^#/, '');
        if (!hash) return;
        hash.split('&').forEach(function (part) {
            var kv = part.split('=');
            if (kv.length !== 2) return;
            var k = kv[0], v = kv[1];
            if (k === 'brand') {
                state.brands = v.split(',').filter(Boolean);
            } else if (k === 'sort') {
                state.sort = v;
                document.querySelectorAll('.ng-filter-sort').forEach(function (s) { s.value = v; });
            } else if (k in state) {
                state[k] = v;
            }
        });
        updatePillStates();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
