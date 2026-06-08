/* NepaliGarage — Navigation */
(function () {
    'use strict';

    const hamburger = document.getElementById('ng-hamburger');
    const nav       = document.querySelector('.ng-nav');
    const header    = document.getElementById('ng-header');

    // Mobile menu toggle
    if (hamburger && nav) {
        hamburger.addEventListener('click', function () {
            const isOpen = nav.classList.toggle('is-open');
            hamburger.classList.toggle('is-open', isOpen);
            hamburger.setAttribute('aria-expanded', String(isOpen));
        });

        // Close menu on outside click
        document.addEventListener('click', function (e) {
            if (!header.contains(e.target)) {
                nav.classList.remove('is-open');
                hamburger.classList.remove('is-open');
                hamburger.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // User dropdown toggle
    const userTrigger  = document.getElementById('ng-user-trigger');
    const userDropdown = document.getElementById('ng-user-dropdown');

    if (userTrigger && userDropdown) {
        userTrigger.addEventListener('click', function () {
            const isOpen = userDropdown.hidden;
            userDropdown.hidden = !isOpen;
            userTrigger.setAttribute('aria-expanded', String(isOpen));
        });

        document.addEventListener('click', function (e) {
            if (!userTrigger.closest('.ng-user-menu').contains(e.target)) {
                userDropdown.hidden = true;
                userTrigger.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // FAQ accordion
    document.querySelectorAll('.ng-faq__question').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const answer  = btn.nextElementSibling;
            const isOpen  = btn.getAttribute('aria-expanded') === 'true';

            // Close all others
            document.querySelectorAll('.ng-faq__question').forEach(function (b) {
                b.setAttribute('aria-expanded', 'false');
                if (b.nextElementSibling) b.nextElementSibling.hidden = true;
            });

            if (!isOpen) {
                btn.setAttribute('aria-expanded', 'true');
                answer.hidden = false;
            }
        });
    });

    // ── Cookie consent (one-time) ─────────────────────────────────────────────
    var cookieBar     = document.getElementById('ng-cookie-bar');
    var cookieAccept  = document.getElementById('ng-cookie-accept');
    var cookieDecline = document.getElementById('ng-cookie-decline');

    if (cookieBar && !localStorage.getItem('ng_cookie_consent')) {
        cookieBar.hidden = false;
        setTimeout(function () { cookieBar.classList.add('is-visible'); }, 600);
    }

    function dismissCookieBar(decision) {
        localStorage.setItem('ng_cookie_consent', decision);
        cookieBar.classList.remove('is-visible');
        setTimeout(function () { cookieBar.hidden = true; }, 400);
    }

    if (cookieAccept)  cookieAccept.addEventListener('click',  function () { dismissCookieBar('accepted'); });
    if (cookieDecline) cookieDecline.addEventListener('click', function () { dismissCookieBar('declined'); });

    // ── Brand marquee upgrade (cars + bikes) ──────────────────────────────────
    (function () {
        var body = document.body;
        if (!body.classList.contains('home') && !body.classList.contains('front-page')) return;

        var cfg = window.ngConfig;
        if (!cfg || !cfg.supabaseUrl || !cfg.supabaseKey) return;

        function buildTrack(brands, linkPrefix) {
            var frag = document.createDocumentFragment();
            [false, true].forEach(function (aria) {
                brands.forEach(function (b) {
                    var a = document.createElement('a');
                    a.href = cfg.siteUrl + '/' + linkPrefix + '/' + (b.slug || b.name.toLowerCase().replace(/\s+/g, '-')) + '/';
                    a.className = 'ng-brand-tile';
                    if (aria) { a.setAttribute('aria-hidden', 'true'); a.setAttribute('tabindex', '-1'); }
                    if (b.logo_url) {
                        var img = document.createElement('img');
                        img.src = b.logo_url; img.alt = aria ? '' : b.name; img.loading = 'lazy';
                        a.appendChild(img);
                    } else {
                        var sp = document.createElement('span');
                        sp.className = 'ng-brand-tile__name'; sp.textContent = b.name;
                        a.appendChild(sp);
                    }
                    frag.appendChild(a);
                });
            });
            var track = document.createElement('div');
            track.className = 'ng-brand-track';
            track.appendChild(frag);
            return track;
        }

        function buildMarquee(brands, reverse, ariaLabel) {
            var m = document.createElement('div');
            m.className = 'ng-brand-marquee' + (reverse ? ' ng-brand-marquee--reverse' : '');
            m.setAttribute('aria-label', ariaLabel);
            m.appendChild(buildTrack(brands, reverse ? 'bikes' : 'cars'));
            return m;
        }

        function upgradeBrands(cars, bikes) {
            var sec = null;
            document.querySelectorAll('.ng-section').forEach(function (s) {
                var h = s.querySelector('h2');
                if (h && h.textContent.trim() === 'Browse by Brand') sec = s;
            });
            if (!sec) return;
            var container = sec.querySelector('.ng-container');
            if (!container) return;

            var hdr = container.querySelector('.ng-section__header');
            container.innerHTML = '';
            if (hdr) container.appendChild(hdr);

            if (cars.length) {
                var cl = document.createElement('p');
                cl.className = 'ng-brand-type-label'; cl.textContent = 'Cars & SUVs';
                container.appendChild(cl);
                container.appendChild(buildMarquee(cars, false, 'Browse car brands'));
            }
            if (bikes.length) {
                var bl = document.createElement('p');
                bl.className = 'ng-brand-type-label'; bl.style.marginTop = '24px'; bl.textContent = 'Bikes & Scooters';
                container.appendChild(bl);
                container.appendChild(buildMarquee(bikes, true, 'Browse bike brands'));
            }

            var more = document.createElement('div');
            more.className = 'ng-brand-more';
            more.innerHTML = '<a href="' + cfg.siteUrl + '/new-cars/" class="ng-btn ng-btn--outline">All Cars →</a>' +
                             '<a href="' + cfg.siteUrl + '/bikes/" class="ng-btn ng-btn--outline">All Bikes →</a>';
            container.appendChild(more);
        }

        var base = cfg.supabaseUrl.replace(/\/$/, '') + '/rest/v1';
        var h = { 'apikey': cfg.supabaseKey, 'Authorization': 'Bearer ' + cfg.supabaseKey };

        Promise.all([
            fetch(base + '/brands?select=id,name,slug,logo_url,type&type=eq.car&order=name.asc', { headers: h }),
            fetch(base + '/brands?select=id,name,slug,logo_url,type&type=eq.bike&order=name.asc', { headers: h }),
        ]).then(function (rs) {
            return Promise.all(rs.map(function (r) { return r.json(); }));
        }).then(function (data) {
            if ((data[0] && data[0].length) || (data[1] && data[1].length)) {
                upgradeBrands(data[0] || [], data[1] || []);
            }
        }).catch(function () {});
    })();

})();
