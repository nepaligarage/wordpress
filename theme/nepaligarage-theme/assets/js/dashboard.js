/* NepaliGarage — My Garage Dashboard */
(function () {
    'use strict';

    const dashboard = document.getElementById('ng-dashboard');
    const gate      = document.getElementById('ng-dashboard-gate');

    if (!dashboard || !gate) return;

    let sb      = null;
    let user    = null;
    let vehicles = [];
    let activeVehicleId = null;
    const activeTab = { name: 'vehicles' };

    // ── Wait for auth.js to initialise Supabase ───────────────────────────────

    function init() {
        if (window.ngSupabase) {
            sb = window.ngSupabase;
            document.addEventListener('ngAuthState', onAuthState);
            sb.auth.getSession().then(function ({ data: { session } }) {
                handleSession(session);
            });
        } else {
            setTimeout(init, 100);
        }
    }

    function onAuthState(e) {
        handleSession(e.detail.session);
    }

    function handleSession(session) {
        if (session && session.user) {
            user = session.user;
            dashboard.hidden = false;
            gate.hidden = true;
            loadVehicles();
        } else {
            dashboard.hidden = true;
            gate.hidden = false;
        }
    }

    // ── Tab routing ───────────────────────────────────────────────────────────

    document.querySelectorAll('.ng-dash-nav__item').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            const tab = link.dataset.tab;
            switchTab(tab);
        });
    });

    function switchTab(name) {
        activeTab.name = name;

        document.querySelectorAll('.ng-dash-nav__item').forEach(function (l) {
            l.classList.toggle('ng-dash-nav__item--active', l.dataset.tab === name);
        });

        document.querySelectorAll('.ng-dash-panel').forEach(function (p) {
            p.hidden = p.dataset.panel !== name;
        });

        // Load panel data
        if (name === 'maintenance') buildVehicleSelector('maint');
        if (name === 'fuel')        buildVehicleSelector('fuel');
        if (name === 'documents')   buildVehicleSelector('doc');
        if (name === 'profile')     loadProfile();
    }

    // Activate from URL hash
    const hash = (window.location.hash || '#vehicles').slice(1);
    const validTabs = ['vehicles', 'maintenance', 'fuel', 'documents', 'profile'];
    if (validTabs.includes(hash)) switchTab(hash);

    // ── Vehicles ──────────────────────────────────────────────────────────────

    async function loadVehicles() {
        const list = document.getElementById('ng-vehicles-list');
        if (!list) return;
        list.innerHTML = '<div class="ng-loading">Loading…</div>';

        const { data, error } = await sb
            .from('user_vehicles')
            .select('*, variant:variants(name, model:models(name, brand:brands(name)))')
            .eq('user_id', user.id)
            .eq('is_active', true)
            .order('created_at', { ascending: false });

        if (error) { list.innerHTML = '<p class="ng-empty-state">Could not load vehicles.</p>'; return; }

        vehicles = data || [];
        renderVehicleList(list, vehicles);
    }

    function renderVehicleList(container, items) {
        const cards = items.map(vehicleCard).join('');
        const addBtn = `
            <button class="ng-add-card" id="ng-add-vehicle-btn2">
                <svg viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                <span>Add another vehicle</span>
            </button>`;
        container.innerHTML = cards + addBtn;

        document.getElementById('ng-add-vehicle-btn2')?.addEventListener('click', openVehicleModal);
    }

    function vehicleCard(v) {
        const brand = v.variant?.model?.brand?.name || v.custom_make || '—';
        const model = v.variant ? `${v.variant.model?.name} ${v.variant.name}` : (v.custom_model || '—');
        const name  = v.custom_name || model;
        const plate = v.plate_number ? `<span class="ng-garage-card__plate">${esc(v.plate_number)}</span>` : '';
        const km    = v.current_odometer_km ? `<div class="ng-garage-card__stat"><strong>${v.current_odometer_km.toLocaleString()} km</strong>Odometer</div>` : '';

        return `
        <div class="ng-garage-card" data-vehicle-id="${esc(v.id)}">
            <div class="ng-garage-card__header">
                <div class="ng-garage-card__make">${esc(brand)}</div>
                <div class="ng-garage-card__name">${esc(name)}</div>
                ${plate}
            </div>
            <div class="ng-garage-card__body">
                <div class="ng-garage-card__stats">${km}</div>
                <div class="ng-garage-card__actions">
                    <button class="ng-btn ng-btn--outline ng-btn--sm" onclick="ngDashboard.selectVehicle('${esc(v.id)}','maintenance')">Service log</button>
                    <button class="ng-btn ng-btn--outline ng-btn--sm" onclick="ngDashboard.selectVehicle('${esc(v.id)}','fuel')">Fuel log</button>
                </div>
            </div>
        </div>`;
    }

    // ── Vehicle modal ─────────────────────────────────────────────────────────

    const vehicleOverlay = document.getElementById('ng-vehicle-modal-overlay');
    const vehicleForm    = document.getElementById('ng-vehicle-form');
    const vehicleErr     = document.getElementById('ng-vehicle-error');

    document.getElementById('ng-add-vehicle-btn')?.addEventListener('click', openVehicleModal);
    document.getElementById('ng-vehicle-modal-close')?.addEventListener('click', closeVehicleModal);
    vehicleOverlay?.addEventListener('click', function (e) { if (e.target === vehicleOverlay) closeVehicleModal(); });

    function openVehicleModal() {
        vehicleOverlay.classList.add('is-open');
        vehicleOverlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        loadBrandsIntoForm();
    }
    function closeVehicleModal() {
        vehicleOverlay.classList.remove('is-open');
        vehicleOverlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        vehicleForm?.reset();
        if (vehicleErr) vehicleErr.hidden = true;
    }

    async function loadBrandsIntoForm() {
        const makeSelect = document.getElementById('ng-v-make');
        if (!makeSelect) return;

        const { data } = await sb.from('brands').select('id,name,slug').order('name');
        if (!data) return;

        makeSelect.innerHTML = '<option value="">Select make…</option>' +
            data.map(b => `<option value="${esc(b.id)}">${esc(b.name)}</option>`).join('');

        makeSelect.addEventListener('change', async function () {
            const modelSelect = document.getElementById('ng-v-model');
            modelSelect.innerHTML = '<option value="">Loading…</option>';
            modelSelect.disabled = true;

            const { data: models } = await sb.from('models').select('id,name').eq('brand_id', makeSelect.value).order('name');
            modelSelect.innerHTML = '<option value="">Select model…</option>' +
                (models || []).map(m => `<option value="${esc(m.id)}">${esc(m.name)}</option>`).join('');
            modelSelect.disabled = false;

            modelSelect.addEventListener('change', async function () {
                const variantSelect = document.getElementById('ng-v-variant');
                variantSelect.innerHTML = '<option value="">Loading…</option>';
                variantSelect.disabled = true;

                const { data: variants } = await sb.from('variants').select('id,name').eq('model_id', modelSelect.value).order('name');
                variantSelect.innerHTML = '<option value="">Select variant (optional)…</option>' +
                    (variants || []).map(v => `<option value="${esc(v.id)}">${esc(v.name)}</option>`).join('');
                variantSelect.disabled = false;
            }, { once: true });
        }, { once: true });
    }

    if (vehicleForm) {
        vehicleForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const submit = vehicleForm.querySelector('[type=submit]');
            submit.disabled = true;
            if (vehicleErr) vehicleErr.hidden = true;

            const fd = new FormData(vehicleForm);
            const payload = {
                user_id:              user.id,
                variant_id:           fd.get('variant_id') || null,
                custom_make:          fd.get('custom_make') || null,
                custom_model:         fd.get('custom_model') || null,
                custom_name:          fd.get('custom_name') || null,
                plate_number:         fd.get('plate_number') || null,
                color:                fd.get('color') || null,
                purchase_date:        fd.get('purchase_date') || null,
                current_odometer_km:  parseInt(fd.get('current_odometer_km')) || 0,
            };

            const { error } = await sb.from('user_vehicles').insert(payload);

            if (error) {
                if (vehicleErr) { vehicleErr.textContent = error.message; vehicleErr.hidden = false; }
            } else {
                closeVehicleModal();
                await loadVehicles();
            }
            submit.disabled = false;
        });
    }

    // ── Vehicle selector (for maintenance/fuel/docs tabs) ─────────────────────

    function buildVehicleSelector(context) {
        const selectorId = { maint: 'ng-maint-vehicle-selector', fuel: 'ng-fuel-vehicle-selector', doc: 'ng-doc-vehicle-selector' }[context];
        const el = document.getElementById(selectorId);
        if (!el) return;

        if (!vehicles.length) {
            el.innerHTML = '<p style="font-size:.875rem;color:#64748b;">Add a vehicle first to log entries.</p>';
            return;
        }

        el.innerHTML = vehicles.map(function (v) {
            const label = v.custom_name || v.variant?.model?.name || v.custom_model || 'Vehicle';
            const active = v.id === activeVehicleId ? 'is-active' : '';
            return `<button class="ng-vehicle-selector__btn ${active}" data-id="${esc(v.id)}" data-ctx="${context}">${esc(label)}</button>`;
        }).join('');

        el.querySelectorAll('.ng-vehicle-selector__btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                activeVehicleId = btn.dataset.id;
                el.querySelectorAll('.ng-vehicle-selector__btn').forEach(b => b.classList.remove('is-active'));
                btn.classList.add('is-active');

                if (context === 'maint') loadMaintenanceLogs(activeVehicleId);
                if (context === 'fuel')  loadFuelLogs(activeVehicleId);
                if (context === 'doc')   loadDocuments(activeVehicleId);
            });
        });

        // Auto-select first
        if (vehicles.length && !activeVehicleId) {
            activeVehicleId = vehicles[0].id;
            el.querySelector('.ng-vehicle-selector__btn')?.classList.add('is-active');
            if (context === 'maint') loadMaintenanceLogs(activeVehicleId);
            if (context === 'fuel')  loadFuelLogs(activeVehicleId);
            if (context === 'doc')   loadDocuments(activeVehicleId);
        }
    }

    // ── Maintenance ───────────────────────────────────────────────────────────

    async function loadMaintenanceLogs(vehicleId) {
        const list = document.getElementById('ng-maintenance-list');
        if (!list) return;
        list.innerHTML = '<div class="ng-loading">Loading…</div>';

        const { data, error } = await sb
            .from('maintenance_logs')
            .select('*')
            .eq('user_vehicle_id', vehicleId)
            .order('log_date', { ascending: false });

        if (error || !data?.length) {
            list.innerHTML = '<p class="ng-empty-state">No maintenance entries yet. Click "+ Add Entry" to log your first service.</p>';
            return;
        }

        const icons = { oil_change: '🛢️', tyre_rotation: '🔄', tyre_replacement: '🔧', brake_service: '🛑', battery: '🔋', filter: '💨', suspension: '⚙️', ac_service: '❄️', electrical: '⚡', insurance_renewal: '📋', registration_renewal: '📄', general_service: '🔧', other: '🔩' };

        list.innerHTML = data.map(function (log) {
            const icon  = icons[log.maintenance_type] || '🔧';
            const label = log.maintenance_type.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            const cost  = log.cost_npr ? `NPR ${Number(log.cost_npr).toLocaleString()}` : '';
            const km    = log.odometer_km ? `${Number(log.odometer_km).toLocaleString()} km` : '';
            const sub   = [log.garage_name, km].filter(Boolean).join(' · ');
            return `
            <div class="ng-log-item">
                <div class="ng-log-item__icon">${icon}</div>
                <div>
                    <div class="ng-log-item__label">${esc(label)}${log.description ? ' — ' + esc(log.description) : ''}</div>
                    ${sub ? `<div class="ng-log-item__sub">${esc(sub)}</div>` : ''}
                </div>
                <div class="ng-log-item__date">${esc(log.log_date)}</div>
                <div class="ng-log-item__cost">${esc(cost)}</div>
            </div>`;
        }).join('');
    }

    const maintOverlay = document.getElementById('ng-maint-modal-overlay');
    const maintForm    = document.getElementById('ng-maint-form');

    document.getElementById('ng-add-maint-btn')?.addEventListener('click', function () {
        if (!activeVehicleId) { alert('Please select a vehicle first.'); return; }
        maintOverlay.classList.add('is-open'); maintOverlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        document.getElementById('ng-m-date').value = today();
    });
    document.getElementById('ng-maint-modal-close')?.addEventListener('click', function () {
        maintOverlay.classList.remove('is-open'); document.body.style.overflow = '';
    });

    if (maintForm) {
        maintForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const submit = maintForm.querySelector('[type=submit]');
            submit.disabled = true;
            const fd = new FormData(maintForm);
            const { error } = await sb.from('maintenance_logs').insert({
                user_vehicle_id:  activeVehicleId,
                user_id:          user.id,
                log_date:         fd.get('log_date'),
                maintenance_type: fd.get('maintenance_type'),
                description:      fd.get('description') || null,
                cost_npr:         fd.get('cost_npr') ? parseFloat(fd.get('cost_npr')) : null,
                odometer_km:      fd.get('odometer_km') ? parseInt(fd.get('odometer_km')) : null,
                garage_name:      fd.get('garage_name') || null,
            });
            if (!error) {
                maintOverlay.classList.remove('is-open'); document.body.style.overflow = '';
                maintForm.reset();
                loadMaintenanceLogs(activeVehicleId);
            }
            submit.disabled = false;
        });
    }

    // ── Fuel Log ──────────────────────────────────────────────────────────────

    async function loadFuelLogs(vehicleId) {
        const list  = document.getElementById('ng-fuel-list');
        const stats = document.getElementById('ng-fuel-stats');
        if (!list) return;
        list.innerHTML = '<div class="ng-loading">Loading…</div>';

        const { data } = await sb
            .from('fuel_logs')
            .select('*')
            .eq('user_vehicle_id', vehicleId)
            .order('log_date', { ascending: false });

        if (!data?.length) {
            if (stats) stats.hidden = true;
            list.innerHTML = '<p class="ng-empty-state">No fuel entries yet. Log your first fill-up.</p>';
            return;
        }

        // Compute stats
        const totalCost   = data.reduce((s, r) => s + Number(r.cost_npr || 0), 0);
        const totalLiters = data.reduce((s, r) => s + Number(r.liters || 0), 0);
        const odomReadings = data.map(r => r.odometer_km).filter(Boolean).sort((a,b) => b-a);
        let avgPer100 = '—';
        if (odomReadings.length >= 2 && totalLiters > 0) {
            const distKm = odomReadings[0] - odomReadings[odomReadings.length - 1];
            avgPer100 = ((totalLiters / distKm) * 100).toFixed(1) + ' L';
        }

        if (stats) {
            stats.hidden = false;
            stats.innerHTML = `
                <div class="ng-fuel-stat"><div class="ng-fuel-stat__value">NPR ${totalCost.toLocaleString()}</div><div class="ng-fuel-stat__label">Total fuel spend</div></div>
                <div class="ng-fuel-stat"><div class="ng-fuel-stat__value">${totalLiters.toFixed(1)} L</div><div class="ng-fuel-stat__label">Total fuel</div></div>
                <div class="ng-fuel-stat"><div class="ng-fuel-stat__value">${avgPer100}</div><div class="ng-fuel-stat__label">Avg per 100 km</div></div>`;
        }

        list.innerHTML = data.map(function (log) {
            const cost = `NPR ${Number(log.cost_npr).toLocaleString()}`;
            const sub  = [log.fuel_station, log.odometer_km ? Number(log.odometer_km).toLocaleString() + ' km' : ''].filter(Boolean).join(' · ');
            return `
            <div class="ng-log-item">
                <div class="ng-log-item__icon">⛽</div>
                <div>
                    <div class="ng-log-item__label">${esc(Number(log.liters).toFixed(1))} L ${esc(log.fuel_type || 'petrol')}</div>
                    ${sub ? `<div class="ng-log-item__sub">${esc(sub)}</div>` : ''}
                </div>
                <div class="ng-log-item__date">${esc(log.log_date)}</div>
                <div class="ng-log-item__cost">${esc(cost)}</div>
            </div>`;
        }).join('');
    }

    const fuelOverlay = document.getElementById('ng-fuel-modal-overlay');
    const fuelForm    = document.getElementById('ng-fuel-form');

    document.getElementById('ng-add-fuel-btn')?.addEventListener('click', function () {
        if (!activeVehicleId) { alert('Please select a vehicle first.'); return; }
        fuelOverlay.classList.add('is-open'); fuelOverlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        document.getElementById('ng-f-date').value = today();
    });
    document.getElementById('ng-fuel-modal-close')?.addEventListener('click', function () {
        fuelOverlay.classList.remove('is-open'); document.body.style.overflow = '';
    });

    if (fuelForm) {
        fuelForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const submit = fuelForm.querySelector('[type=submit]');
            submit.disabled = true;
            const fd = new FormData(fuelForm);
            const { error } = await sb.from('fuel_logs').insert({
                user_vehicle_id: activeVehicleId,
                user_id:         user.id,
                log_date:        fd.get('log_date'),
                liters:          parseFloat(fd.get('liters')),
                cost_npr:        parseFloat(fd.get('cost_npr')),
                odometer_km:     parseInt(fd.get('odometer_km')),
                full_tank:       fd.get('full_tank') === 'on',
                fuel_station:    fd.get('fuel_station') || null,
                fuel_type:       fd.get('fuel_type'),
            });
            if (!error) {
                fuelOverlay.classList.remove('is-open'); document.body.style.overflow = '';
                fuelForm.reset();
                loadFuelLogs(activeVehicleId);
            }
            submit.disabled = false;
        });
    }

    // ── Documents ─────────────────────────────────────────────────────────────

    async function loadDocuments(vehicleId) {
        const grid = document.getElementById('ng-doc-list');
        if (!grid) return;
        grid.innerHTML = '<div class="ng-loading">Loading…</div>';

        const { data } = await sb
            .from('documents')
            .select('*')
            .eq('user_vehicle_id', vehicleId)
            .order('expiry_date', { ascending: true });

        if (!data?.length) {
            grid.innerHTML = '<p class="ng-empty-state">No documents yet. Add your insurance, bluebook, or warranty.</p>';
            return;
        }

        const now = new Date();
        grid.innerHTML = data.map(function (doc) {
            let statusClass = '';
            let expiryStr   = '';
            if (doc.expiry_date) {
                const expiry  = new Date(doc.expiry_date);
                const daysLeft = Math.ceil((expiry - now) / 86400000);
                if (daysLeft < 0) {
                    statusClass = 'ng-doc-card--expired';
                    expiryStr   = `Expired ${doc.expiry_date}`;
                } else if (daysLeft <= 30) {
                    statusClass = 'ng-doc-card--warning';
                    expiryStr   = `Expires in ${daysLeft} days`;
                } else {
                    statusClass = 'ng-doc-card--valid';
                    expiryStr   = `Expires ${doc.expiry_date}`;
                }
            }
            const typeLabel = doc.doc_type.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            return `
            <div class="ng-doc-card ${statusClass}">
                <div class="ng-doc-card__type">${esc(typeLabel)}</div>
                <div class="ng-doc-card__title">${esc(doc.title)}</div>
                ${expiryStr ? `<div class="ng-doc-card__expiry">${esc(expiryStr)}</div>` : ''}
            </div>`;
        }).join('');
    }

    const docOverlay = document.getElementById('ng-doc-modal-overlay');
    const docForm    = document.getElementById('ng-doc-form');

    document.getElementById('ng-add-doc-btn')?.addEventListener('click', function () {
        if (!activeVehicleId) { alert('Please select a vehicle first.'); return; }
        docOverlay.classList.add('is-open'); docOverlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    });
    document.getElementById('ng-doc-modal-close')?.addEventListener('click', function () {
        docOverlay.classList.remove('is-open'); document.body.style.overflow = '';
    });

    if (docForm) {
        docForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const submit = docForm.querySelector('[type=submit]');
            submit.disabled = true;
            const fd = new FormData(docForm);
            const { error } = await sb.from('documents').insert({
                user_vehicle_id: activeVehicleId,
                user_id:         user.id,
                doc_type:        fd.get('doc_type'),
                title:           fd.get('title'),
                issue_date:      fd.get('issue_date') || null,
                expiry_date:     fd.get('expiry_date') || null,
                reminder_days:   parseInt(fd.get('reminder_days')) || 30,
            });
            if (!error) {
                docOverlay.classList.remove('is-open'); document.body.style.overflow = '';
                docForm.reset();
                loadDocuments(activeVehicleId);
            }
            submit.disabled = false;
        });
    }

    // ── Profile ───────────────────────────────────────────────────────────────

    async function loadProfile() {
        if (!user) return;
        document.getElementById('ng-profile-email').value = user.email || '';

        const { data } = await sb.from('user_profiles').select('*').eq('user_id', user.id).single();
        if (data) {
            document.getElementById('ng-profile-name').value     = data.display_name || '';
            document.getElementById('ng-profile-phone').value    = data.phone || '';
            document.getElementById('ng-profile-location').value = data.location || '';
        }
    }

    document.getElementById('ng-profile-form')?.addEventListener('submit', async function (e) {
        e.preventDefault();
        const submit = e.target.querySelector('[type=submit]');
        submit.disabled = true;
        const err  = document.getElementById('ng-profile-error');
        const succ = document.getElementById('ng-profile-success');
        if (err)  err.hidden = true;
        if (succ) succ.hidden = true;

        const payload = {
            user_id:      user.id,
            display_name: document.getElementById('ng-profile-name').value.trim() || null,
            phone:        document.getElementById('ng-profile-phone').value.trim() || null,
            location:     document.getElementById('ng-profile-location').value.trim() || null,
        };

        const { error } = await sb.from('user_profiles').upsert(payload, { onConflict: 'user_id' });
        if (error) {
            if (err) { err.textContent = error.message; err.hidden = false; }
        } else {
            if (succ) { succ.hidden = false; setTimeout(() => { succ.hidden = true; }, 3000); }
        }
        submit.disabled = false;
    });

    // ── Public API (for inline onclick in vehicle cards) ──────────────────────

    window.ngDashboard = {
        selectVehicle: function (id, tab) {
            activeVehicleId = id;
            switchTab(tab);
        },
    };

    // ── Utilities ─────────────────────────────────────────────────────────────

    function esc(str) {
        if (str == null) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }

    function today() {
        return new Date().toISOString().slice(0, 10);
    }

    // ── Start ─────────────────────────────────────────────────────────────────

    init();

})();
