/* NepaliGarage — Enquiry & Order Forms */
(function () {
    'use strict';

    var sb     = window.ngSupabase;
    var config = window.ngConfig;

    if (!sb || !config) return;

    // ── Enquiry modal ─────────────────────────────────────────────────────────

    var enquiryOverlay = document.getElementById('ng-enquiry-overlay');
    var enquiryClose   = document.getElementById('ng-enquiry-close');
    var enquiryForm    = document.getElementById('ng-enquiry-form');
    var enquiryTitle   = document.getElementById('ng-enquiry-title');
    var enquirySub     = document.getElementById('ng-enquiry-subtitle');
    var enquiryErr     = document.getElementById('ng-enquiry-error');
    var enquirySuccess = document.getElementById('ng-enquiry-success');
    var enquirySubmit  = document.getElementById('ng-enquiry-submit');

    function openEnquiry(variantId, leadType) {
        if (!enquiryOverlay) return;
        document.getElementById('ng-enq-variant-id').value = variantId || '';
        document.getElementById('ng-enq-lead-type').value  = leadType  || 'test_drive';

        if (leadType === 'quote_request') {
            if (enquiryTitle)   enquiryTitle.textContent = 'Get a Quote';
            if (enquirySub)     enquirySub.textContent   = 'We\'ll send you a detailed price breakdown within 24 hours.';
            if (enquirySubmit)  enquirySubmit.textContent = 'Request Quote';
        } else {
            if (enquiryTitle)   enquiryTitle.textContent = 'Book a Test Drive';
            if (enquirySub)     enquirySub.textContent   = 'We\'ll contact you within 24 hours to confirm.';
            if (enquirySubmit)  enquirySubmit.textContent = 'Submit Request';
        }

        resetForm(enquiryForm, enquiryErr, enquirySuccess);
        enquiryOverlay.classList.add('is-open');
        enquiryOverlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }

    function closeEnquiry() {
        if (!enquiryOverlay) return;
        enquiryOverlay.classList.remove('is-open');
        enquiryOverlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    // ── Wire enquiry triggers ─────────────────────────────────────────────────

    var enquireBtn = document.getElementById('ng-enquire-btn');
    var quoteBtn   = document.getElementById('ng-quote-btn');

    if (enquireBtn) {
        enquireBtn.addEventListener('click', function () {
            openEnquiry(enquireBtn.dataset.variantId, 'test_drive');
        });
    }

    if (quoteBtn) {
        quoteBtn.addEventListener('click', function () {
            openEnquiry(quoteBtn.dataset.variantId, 'quote_request');
        });
    }

    if (enquiryClose) enquiryClose.addEventListener('click', closeEnquiry);
    if (enquiryOverlay) {
        enquiryOverlay.addEventListener('click', function (e) {
            if (e.target === enquiryOverlay) closeEnquiry();
        });
    }

    // ── Submit enquiry ────────────────────────────────────────────────────────

    if (enquiryForm) {
        enquiryForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            if (!validate(enquiryForm, enquiryErr)) return;

            setLoading(enquirySubmit, true, 'Sending…');

            var variantId = document.getElementById('ng-enq-variant-id').value;
            var leadType  = document.getElementById('ng-enq-lead-type').value;
            var name      = document.getElementById('ng-enq-name').value.trim();
            var phone     = document.getElementById('ng-enq-phone').value.trim();
            var email     = document.getElementById('ng-enq-email').value.trim();
            var location  = document.getElementById('ng-enq-location').value;

            var payload = {
                variant_id: variantId || null,
                lead_type:  leadType,
                name:       name,
                phone:      phone,
                email:      email || null,
                message:    location || null,
            };

            var { error } = await sb.from('leads').insert(payload);

            if (error) {
                showErr(enquiryErr, 'Something went wrong. Please try again.');
                setLoading(enquirySubmit, false, 'Submit Request');
                return;
            }

            enquirySuccess.hidden = false;
            enquiryForm.querySelector('button[type=submit]').hidden = true;
            setTimeout(closeEnquiry, 3000);
        });
    }

    // ── Variant tab switching (update hero price + image) ─────────────────────

    var variantTabs  = document.querySelectorAll('.ng-variant-tab');
    var activePriceEl = document.getElementById('ng-active-price');
    var activePriceMetaEl = document.getElementById('ng-active-price-meta');
    var activeImageEl = document.getElementById('ng-active-image');

    variantTabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            variantTabs.forEach(function (t) { t.classList.remove('is-active'); });
            tab.classList.add('is-active');

            var vid   = tab.dataset.variantId;
        var price = tab.dataset.price;
        var priceLabel = tab.dataset.priceLabel;
        var image = tab.dataset.image;

            if (activePriceEl) {
        activePriceEl.textContent = priceLabel || (price
            ? 'NPR ' + parseInt(price).toLocaleString('en-IN')
            : 'Price on request');
        if (activePriceMetaEl) {
            var priceMeta = tab.querySelector('.ng-variant-tab__price-meta');
            activePriceMetaEl.innerHTML = priceMeta ? priceMeta.innerHTML : '';
        }
            }
            if (activeImageEl && image) {
                activeImageEl.src = image;
            }

            // Update CTA data-variant-id
            if (enquireBtn) enquireBtn.dataset.variantId = vid;
            if (quoteBtn)   quoteBtn.dataset.variantId   = vid;
        });
    });

    // ── Order modal (accessories) ─────────────────────────────────────────────

    var orderOverlay = document.getElementById('ng-order-overlay');
    var orderClose   = document.getElementById('ng-order-close');
    var orderForm    = document.getElementById('ng-order-form');
    var orderErr     = document.getElementById('ng-order-error');
    var orderSuccess = document.getElementById('ng-order-success');
    var orderSubmit  = document.getElementById('ng-order-submit');

    document.querySelectorAll('.ng-acc-order-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!orderOverlay) return;
            document.getElementById('ng-ord-acc-id').value    = btn.dataset.accId || '';
            document.getElementById('ng-ord-acc-name').value  = btn.dataset.accName || '';
            document.getElementById('ng-ord-acc-price').value = btn.dataset.accPrice || '0';
            var itemEl = document.getElementById('ng-order-item-name');
            if (itemEl) itemEl.textContent = btn.dataset.accName || '';
            resetForm(orderForm, orderErr, orderSuccess);
            orderOverlay.classList.add('is-open');
            orderOverlay.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        });
    });

    // Log affiliate clicks as orders
    document.querySelectorAll('.ng-acc-buy-btn').forEach(function (link) {
        link.addEventListener('click', function () {
            sb.from('orders').insert({
                order_type:         'accessory',
                item_id:            link.dataset.accId || null,
                item_name:          link.dataset.accName || null,
                unit_price:         parseFloat(link.dataset.accPrice) || null,
                total_npr:          parseFloat(link.dataset.accPrice) || null,
                affiliate_redirect: true,
                status:             'redirect',
            }).then(function () {}); // fire-and-forget
        });
    });

    function closeOrder() {
        if (!orderOverlay) return;
        orderOverlay.classList.remove('is-open');
        orderOverlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    if (orderClose) orderClose.addEventListener('click', closeOrder);
    if (orderOverlay) {
        orderOverlay.addEventListener('click', function (e) {
            if (e.target === orderOverlay) closeOrder();
        });
    }

    if (orderForm) {
        orderForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            if (!validate(orderForm, orderErr)) return;
            setLoading(orderSubmit, true, 'Placing…');

            var accId    = document.getElementById('ng-ord-acc-id').value;
            var accName  = document.getElementById('ng-ord-acc-name').value;
            var price    = parseFloat(document.getElementById('ng-ord-acc-price').value) || 0;
            var qty      = parseInt(document.getElementById('ng-ord-qty').value) || 1;
            var name     = document.getElementById('ng-ord-name').value.trim();
            var phone    = document.getElementById('ng-ord-phone').value.trim();
            var session  = await sb.auth.getSession();
            var uid      = session?.data?.session?.user?.id || null;

            var { error } = await sb.from('orders').insert({
                user_id:    uid,
                order_type: 'accessory',
                item_id:    accId || null,
                item_name:  accName,
                quantity:   qty,
                unit_price: price,
                total_npr:  price * qty,
                name:       name,
                phone:      phone,
                status:     'pending',
            });

            if (error) {
                showErr(orderErr, 'Something went wrong. Please try again.');
                setLoading(orderSubmit, false, 'Place Order');
                return;
            }

            orderSuccess.hidden = false;
            orderSubmit.hidden = true;
            setTimeout(closeOrder, 3000);
        });
    }

    // Esc key closes any open modal
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { closeEnquiry(); closeOrder(); }
    });

    // ── Helpers ───────────────────────────────────────────────────────────────

    function validate(form, errEl) {
        var required = form.querySelectorAll('[required]');
        for (var i = 0; i < required.length; i++) {
            if (!required[i].value.trim()) {
                showErr(errEl, 'Please fill in all required fields.');
                required[i].focus();
                return false;
            }
        }
        var phone = form.querySelector('[type=tel]');
        if (phone && !/^[0-9+\s\-]{7,15}$/.test(phone.value.trim())) {
            showErr(errEl, 'Please enter a valid phone number.');
            phone.focus();
            return false;
        }
        return true;
    }

    function showErr(el, msg) {
        if (!el) return;
        el.textContent = msg;
        el.hidden = false;
    }

    function resetForm(form, errEl, successEl) {
        if (form) form.reset();
        if (errEl)     { errEl.hidden = true;     errEl.textContent = ''; }
        if (successEl) { successEl.hidden = true; }
    }

    function setLoading(btn, loading, label) {
        if (!btn) return;
        btn.disabled = loading;
        btn.textContent = label;
    }

})();
