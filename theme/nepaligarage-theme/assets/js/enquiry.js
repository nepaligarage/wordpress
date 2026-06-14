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

    function openEnquiry(variantId, leadType, contextLine) {
        if (!enquiryOverlay) return;
        document.getElementById('ng-enq-variant-id').value = variantId || '';
        document.getElementById('ng-enq-lead-type').value  = leadType  || 'test_drive';

        if (leadType === 'quote_request') {
            if (enquiryTitle)   enquiryTitle.textContent = 'Get a Quote';
            if (enquirySub)     enquirySub.textContent   = contextLine || 'We\'ll send you a detailed price breakdown within 24 hours.';
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
    var compareBtn = document.getElementById('ng-compare-btn');
    var compareNote = document.getElementById('ng-compare-note');
    var financeWrap = document.getElementById('ng-vehicle-finance');
    var financeProgram = document.getElementById('ng-finance-program');
    var financeDownpayment = document.getElementById('ng-finance-downpayment');
    var financeYears = document.getElementById('ng-finance-years');
    var financeInterest = document.getElementById('ng-finance-interest');
    var financeQuoteBtn = document.getElementById('ng-finance-quote-btn');

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

    var compareStorageKey = 'ng_compare_models';
    var financePrograms = parseFinancePrograms();
    var activeVariantPrice = financeWrap ? toNumber(financeWrap.dataset.priceAmount) : 0;

    if (compareBtn) {
        compareBtn.addEventListener('click', function () {
            var current = getCurrentCompareVehicle();
            if (!current) return;

            var saved = readCompareVehicles().filter(function (item) {
                return !(item.brandSlug === current.brandSlug && item.modelSlug === current.modelSlug);
            });

            saved.unshift(current);
            saved = saved.slice(0, 2);
            writeCompareVehicles(saved);
            renderCompareState(current.label + ' added to compare.');
        });
    }

    if (financeProgram) {
        financeProgram.addEventListener('change', function () {
            syncFinanceInputs(true);
            renderFinance();
        });
    }

    if (financeDownpayment) {
        financeDownpayment.addEventListener('input', renderFinance);
        financeDownpayment.addEventListener('blur', function () {
            syncFinanceInputs(false);
            renderFinance();
        });
    }

    if (financeYears) {
        financeYears.addEventListener('change', renderFinance);
    }

    if (financeQuoteBtn) {
        financeQuoteBtn.addEventListener('click', function () {
            var financeContext = buildFinanceQuoteSubtitle();
            var financeVariantId = quoteBtn ? quoteBtn.dataset.variantId : (enquireBtn ? enquireBtn.dataset.variantId : '');
            openEnquiry(financeVariantId, 'quote_request', financeContext);
        });
    }

    renderCompareState();
    syncFinanceInputs(true);
    renderFinance();

    function parseFinancePrograms() {
        if (!financeWrap || !financeWrap.dataset.financePrograms) return [];
        try {
            var parsed = JSON.parse(financeWrap.dataset.financePrograms);
            return Array.isArray(parsed) ? parsed : [];
        } catch (err) {
            return [];
        }
    }

    function toNumber(value) {
        var numeric = parseFloat(value || '0');
        return isNaN(numeric) ? 0 : numeric;
    }

    function roundToStep(value, step) {
        if (!step) return value;
        return Math.round(value / step) * step;
    }

    function formatNpr(value) {
        var numeric = Math.round(toNumber(value));
        return 'NPR ' + numeric.toLocaleString('en-IN');
    }

    function getCurrentCompareVehicle() {
        if (!compareBtn) return null;
        return {
            brandSlug: compareBtn.dataset.brandSlug || '',
            modelSlug: compareBtn.dataset.modelSlug || '',
            label: compareBtn.dataset.modelName || document.title || 'Vehicle',
            url: window.location.href
        };
    }

    function readCompareVehicles() {
        try {
            var raw = window.localStorage.getItem(compareStorageKey);
            var parsed = raw ? JSON.parse(raw) : [];
            return Array.isArray(parsed) ? parsed : [];
        } catch (err) {
            return [];
        }
    }

    function writeCompareVehicles(vehicles) {
        try {
            window.localStorage.setItem(compareStorageKey, JSON.stringify(vehicles));
        } catch (err) {
            return;
        }
    }

    function renderCompareState(message) {
        if (!compareBtn || !compareNote) return;

        var current = getCurrentCompareVehicle();
        var saved = readCompareVehicles();
        var exists = current && saved.some(function (item) {
            return item.brandSlug === current.brandSlug && item.modelSlug === current.modelSlug;
        });
        var compareUrl = compareBtn.dataset.compareUrl || '';

        compareBtn.textContent = exists ? 'Added to Compare' : 'Add to Compare';

        if (message) {
            compareNote.textContent = message;
            if (compareUrl) {
                compareNote.innerHTML = compareNote.textContent + ' <a href="' + compareUrl + '">Open live comparison</a>';
            }
            return;
        }

        if (exists && compareUrl) {
            compareNote.innerHTML = 'This model is saved for comparison. <a href="' + compareUrl + '">Open live comparison</a>';
        } else if (exists) {
            compareNote.textContent = 'This model is saved for comparison.';
        } else if (compareUrl) {
            compareNote.innerHTML = 'Start here, then continue with the live BYD Atto 2 comparison. <a href="' + compareUrl + '">View it now</a>';
        } else {
            compareNote.textContent = 'Save this vehicle to your compare shortlist.';
        }
    }

    function getSelectedFinanceProgram() {
        if (!financePrograms.length) return null;
        var selectedId = financeProgram ? financeProgram.value : financePrograms[0].id;
        for (var i = 0; i < financePrograms.length; i++) {
            if (financePrograms[i].id === selectedId) return financePrograms[i];
        }
        return financePrograms[0];
    }

    function syncFinanceInputs(forceReset) {
        var program = getSelectedFinanceProgram();
        if (!program || !financeDownpayment) return;

        var minPercent = toNumber(program.min_downpayment_percent);
        var maxPercent = toNumber(program.max_downpayment_percent || 90);
        var minAmount = roundToStep(activeVariantPrice * minPercent / 100, 10000);
        var maxAmount = roundToStep(activeVariantPrice * maxPercent / 100, 10000);
        var currentAmount = roundToStep(toNumber(financeDownpayment.value), 10000);

        if (!currentAmount || forceReset || currentAmount < minAmount) currentAmount = minAmount;
        if (maxAmount && currentAmount > maxAmount) currentAmount = maxAmount;
        financeDownpayment.value = Math.max(0, currentAmount);

        if (financeInterest) {
            financeInterest.value = toNumber(program.interest_rate).toFixed(2) + '% p.a.';
        }

        if (financeYears) {
            var supportedYears = Array.isArray(program.supported_years) ? program.supported_years : [];
            var normalizedYears = supportedYears.map(function (year) {
                return parseInt(year, 10);
            }).filter(function (year) {
                return !isNaN(year) && year > 0;
            });
            var selectedYears = parseInt(financeYears.value || program.default_years || normalizedYears[0] || 1, 10);
            financeYears.innerHTML = normalizedYears.map(function (year) {
                return '<option value="' + year + '">' + year + ' years</option>';
            }).join('');
            if (normalizedYears.indexOf(selectedYears) === -1) {
                selectedYears = parseInt(program.default_years || normalizedYears[normalizedYears.length - 1] || 1, 10);
            }
            financeYears.value = String(selectedYears);
        }
    }

    function renderFinance() {
        if (!financeWrap) return;
        var program = getSelectedFinanceProgram();
        if (!program) return;

        activeVariantPrice = toNumber(financeWrap.dataset.priceAmount || activeVariantPrice);
        if (!activeVariantPrice) return;

        syncFinanceInputs(false);

        var downpaymentAmount = toNumber(financeDownpayment ? financeDownpayment.value : 0);
        var downpaymentPercent = activeVariantPrice ? (downpaymentAmount / activeVariantPrice) * 100 : 0;
        var loanAmount = Math.max(activeVariantPrice - downpaymentAmount, 0);
        var years = parseInt(financeYears ? financeYears.value : (program.default_years || 1), 10) || 1;
        var months = years * 12;
        var annualRate = toNumber(program.interest_rate);
        var monthlyRate = annualRate / 12 / 100;
        var monthlyEmi = months ? loanAmount / months : 0;

        if (monthlyRate > 0 && months > 0) {
            monthlyEmi = loanAmount * monthlyRate * Math.pow(1 + monthlyRate, months) / (Math.pow(1 + monthlyRate, months) - 1);
        }

        var totalPayable = monthlyEmi * months;
        var totalInterest = Math.max(totalPayable - loanAmount, 0);

        setFinanceText('ng-finance-price', formatNpr(activeVariantPrice));
        setFinanceText('ng-finance-downpayment-display', formatNpr(downpaymentAmount));
        setFinanceText('ng-finance-downpayment-percent', downpaymentPercent.toFixed(1) + '% of vehicle price');
        setFinanceText('ng-finance-monthly-emi', formatNpr(monthlyEmi));
        setFinanceText('ng-finance-term-summary', years + ' years • ' + annualRate.toFixed(2) + '% p.a.');
        setFinanceText('ng-finance-loan-amount', formatNpr(loanAmount));
        setFinanceText('ng-finance-total-payable', formatNpr(totalPayable + downpaymentAmount));
        setFinanceText('ng-finance-total-interest', formatNpr(totalInterest));
    }

    function setFinanceText(id, value) {
        var el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    function buildFinanceQuoteSubtitle() {
        var monthlyEmi = document.getElementById('ng-finance-monthly-emi');
        var years = financeYears ? financeYears.value : '';
        var downpayment = financeDownpayment ? formatNpr(financeDownpayment.value) : 'your selected down payment';
        if (!monthlyEmi) {
            return 'We\'ll send you a detailed price breakdown within 24 hours.';
        }
        return 'We\'ll send you a detailed quote for your current plan — ' + downpayment + ' down payment over ' + years + ' years, currently estimated at ' + monthlyEmi.textContent + ' per month.';
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
        var priceAmount = tab.dataset.priceAmount;
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
            if (financeWrap && priceAmount) {
                financeWrap.dataset.priceAmount = priceAmount;
                activeVariantPrice = toNumber(priceAmount);
                syncFinanceInputs(true);
                renderFinance();
            }
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
