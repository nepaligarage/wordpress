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
        if (name === 'taxes')       buildVehicleSelector('oblig');
        if (name === 'profile')     loadProfile();
    }

    // Activate from URL hash
    const hash = (window.location.hash || '#vehicles').slice(1);
    const validTabs = ['vehicles', 'maintenance', 'fuel', 'documents', 'taxes', 'profile'];
    if (validTabs.includes(hash)) switchTab(hash);

    // ── Vehicles ──────────────────────────────────────────────────────────────

    async function loadVehicles() {
        const list = document.getElementById('ng-vehicles-list');
        if (!list) return;
        list.innerHTML = '<div class="ng-loading">Loading…</div>';

        const { data, error } = await sb
            .from('user_vehicles')
            .select('*, variant:variants(name, model:models(name, brand:brands(name))), model:models(name, brand:brands(name))')
            .eq('user_id', user.id)
            .eq('is_active', true)
            .order('created_at', { ascending: false });

        if (error) { list.innerHTML = '<p class="ng-empty-state">Could not load vehicles.</p>'; return; }

        vehicles = data || [];
        renderVehicleList(list, vehicles);
        loadUpcoming();
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

        // Hydrate media (signed photo URLs + video chips) per card
        items.forEach(hydrateVehicleMedia);
    }

    function vehicleCard(v) {
        const brand = v.variant?.model?.brand?.name || v.model?.brand?.name || v.custom_make || '—';
        const model = v.variant ? `${v.variant.model?.name} ${v.variant.name}` : (v.model?.name || v.custom_model || '—');
        const name  = v.custom_name || model;
        const plate = v.plate_number ? `<span class="ng-garage-card__plate">${esc(v.plate_number)}</span>` : '';
        const km    = v.current_odometer_km ? `<div class="ng-garage-card__stat"><strong>${v.current_odometer_km.toLocaleString()} km</strong>Odometer</div>` : '';

        return `
        <div class="ng-garage-card" data-vehicle-id="${esc(v.id)}">
            <div class="ng-garage-card__media" data-media-for="${esc(v.id)}" hidden></div>
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

    // Resolve photos (private bucket → signed URLs) + video chips into a card
    async function hydrateVehicleMedia(v) {
        const box = document.querySelector(`.ng-garage-card__media[data-media-for="${cssEsc(v.id)}"]`);
        if (!box) return;

        let html = '';

        const photos = Array.isArray(v.photos) ? v.photos.filter(Boolean) : [];
        if (photos.length) {
            const { data } = await sb.storage.from('vehicle-photos').createSignedUrls(photos, 3600);
            const urls = (data || []).filter(d => d && d.signedUrl);
            if (urls.length) {
                html += '<div class="ng-garage-card__photos">' +
                    urls.map(d => `<img src="${esc(d.signedUrl)}" alt="" loading="lazy">`).join('') +
                    '</div>';
            }
        }

        const videos = Array.isArray(v.videos) ? v.videos.filter(x => x && x.url) : [];
        if (videos.length) {
            html += '<div class="ng-garage-card__videos">' +
                videos.map(vid => `<a class="ng-video-chip" href="${esc(vid.url)}" target="_blank" rel="noopener noreferrer">▶ ${esc(vid.title || vid.provider || 'Video')}</a>`).join('') +
                '</div>';
        }

        if (html) {
            box.innerHTML = html;
            box.hidden = false;
        }
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
        const vids = document.getElementById('ng-v-videos');
        if (vids) vids.innerHTML = '';
        if (vehicleErr) vehicleErr.hidden = true;
    }

    // ── Video-link rows (Add Vehicle modal) ───────────────────────────────────

    document.getElementById('ng-v-add-video')?.addEventListener('click', function () {
        addVideoRow();
    });

    function addVideoRow() {
        const wrap = document.getElementById('ng-v-videos');
        if (!wrap) return;
        const row = document.createElement('div');
        row.className = 'ng-video-row';
        row.innerHTML =
            '<input type="url" class="ng-v-video-url" placeholder="https://youtube.com/watch?v=…">' +
            '<input type="text" class="ng-v-video-title" placeholder="Title (optional)">' +
            '<button type="button" class="ng-link ng-v-video-remove" aria-label="Remove">&times;</button>';
        row.querySelector('.ng-v-video-remove').addEventListener('click', function () { row.remove(); });
        wrap.appendChild(row);
    }

    // Collect entered video links into the videos jsonb shape
    function collectVideos() {
        const rows = document.querySelectorAll('#ng-v-videos .ng-video-row');
        const out = [];
        rows.forEach(function (row) {
            const url = row.querySelector('.ng-v-video-url')?.value.trim();
            if (!url) return;
            const title = row.querySelector('.ng-v-video-title')?.value.trim() || null;
            out.push({ url: url, title: title, provider: videoProvider(url) });
        });
        return out;
    }

    function videoProvider(url) {
        if (/youtu\.?be/i.test(url))   return 'youtube';
        if (/vimeo\.com/i.test(url))   return 'vimeo';
        if (/facebook\.com/i.test(url)) return 'facebook';
        if (/tiktok\.com/i.test(url))  return 'tiktok';
        return 'link';
    }

    // Standardise a user-typed model name: capitalise the first letter of each
    // word, leave the rest as typed (preserves codes like "CR-V", "Atto 3").
    function normalizeModelName(raw) {
        return (raw || '').trim().replace(/\s+/g, ' ')
            .split(' ')
            .map(w => w ? w.charAt(0).toUpperCase() + w.slice(1) : w)
            .join(' ');
    }

    function slugify(s) {
        return (s || '').toLowerCase().trim().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
    }

    function setHint(el, msg, isError) {
        if (!el) return;
        el.textContent = msg;
        el.style.color = isError ? '#dc2626' : '';
    }

    // Insert a model option alphabetically (skipping the placeholder) if absent.
    function upsertModelOption(select, model) {
        if (Array.from(select.options).some(o => o.value === model.id)) return;
        const opt = document.createElement('option');
        opt.value = model.id;
        opt.textContent = model.name;
        const before = Array.from(select.options).slice(1)
            .find(o => o.textContent.localeCompare(model.name) > 0);
        select.insertBefore(opt, before || null);
    }

    // Add a user-contributed model to the shared catalog (dedupe case-insensitively).
    // Returns the model row {id,name} — existing if one already matches, else new.
    async function addUserModel(brandId, name, brandSlugStr) {
        const { data: existing } = await sb.from('models').select('id,name')
            .eq('brand_id', brandId).ilike('name', name).limit(1);
        if (existing && existing.length) return existing[0];

        const slug = `${brandSlugStr || 'model'}-${slugify(name)}`;
        const { data, error } = await sb.from('models')
            .insert({ brand_id: brandId, name, slug, source: 'user', created_by: user.id })
            .select('id,name')
            .single();
        if (!error) return data;

        // Race / slug clash → re-select whatever now matches
        const { data: retry } = await sb.from('models').select('id,name')
            .eq('brand_id', brandId).ilike('name', name).limit(1);
        return (retry && retry.length) ? retry[0] : null;
    }

    async function loadBrandsIntoForm() {
        const makeSelect    = document.getElementById('ng-v-make');
        const modelSelect   = document.getElementById('ng-v-model');
        const variantSelect = document.getElementById('ng-v-variant');
        const addWrap       = document.getElementById('ng-v-add-model-wrap');
        if (!makeSelect || !modelSelect || !variantSelect) return;

        const { data } = await sb.from('brands').select('id,name,slug').order('name');
        if (!data) return;

        const brandSlug = {};
        data.forEach(b => { brandSlug[b.id] = b.slug || slugify(b.name); });

        makeSelect.innerHTML = '<option value="">Select make…</option>' +
            data.map(b => `<option value="${esc(b.id)}">${esc(b.name)}</option>`).join('');

        function resetSelect(sel, placeholder) {
            sel.innerHTML = `<option value="">${placeholder}</option>`;
            sel.disabled = true;
        }

        async function loadModels(brandId) {
            resetSelect(modelSelect, 'Loading…');
            const { data: models } = await sb.from('models').select('id,name')
                .eq('brand_id', brandId).order('name');
            modelSelect.innerHTML = '<option value="">Select model…</option>' +
                (models || []).map(m => `<option value="${esc(m.id)}">${esc(m.name)}</option>`).join('');
            modelSelect.disabled = false;
            resetSelect(variantSelect, 'Select variant (optional)…');
        }

        async function loadVariants(modelId) {
            resetSelect(variantSelect, 'Loading…');
            const { data: variants } = await sb.from('variants').select('id,name')
                .eq('model_id', modelId).order('name');
            variantSelect.innerHTML = '<option value="">Select variant (optional)…</option>' +
                (variants || []).map(v => `<option value="${esc(v.id)}">${esc(v.name)}</option>`).join('');
            variantSelect.disabled = false;
        }

        // Re-selectable (no { once: true } — changing make/model again re-loads)
        makeSelect.addEventListener('change', function () {
            if (!makeSelect.value) {
                resetSelect(modelSelect, 'Select model…');
                resetSelect(variantSelect, 'Select variant…');
                if (addWrap) addWrap.hidden = true;
                return;
            }
            loadModels(makeSelect.value);
            if (addWrap) addWrap.hidden = false;
        });

        modelSelect.addEventListener('change', function () {
            if (modelSelect.value) loadVariants(modelSelect.value);
        });

        // ── "Model not listed? Add it" affordance ───────────────────────────
        const toggle  = document.getElementById('ng-v-add-model-toggle');
        const row     = document.getElementById('ng-v-add-model-row');
        const input   = document.getElementById('ng-v-new-model');
        const saveBtn = document.getElementById('ng-v-save-model');
        const cancel  = document.getElementById('ng-v-cancel-model');
        const hint    = document.getElementById('ng-v-add-model-hint');

        function showRow(show) {
            if (row)    row.hidden = !show;
            if (toggle) toggle.hidden = show;
            if (show && input) { input.value = ''; input.focus(); }
            if (!show) setHint(hint, 'Adds it to the shared list so other owners can pick it too.', false);
        }
        toggle?.addEventListener('click', () => showRow(true));
        cancel?.addEventListener('click', () => showRow(false));

        saveBtn?.addEventListener('click', async function () {
            const brandId = makeSelect.value;
            const name    = normalizeModelName(input?.value);
            if (!brandId) { setHint(hint, 'Pick a make first.', true); return; }
            if (!name)    { setHint(hint, 'Enter a model name.', true); return; }

            saveBtn.disabled = true;
            setHint(hint, 'Adding…', false);
            const model = await addUserModel(brandId, name, brandSlug[brandId]);
            saveBtn.disabled = false;
            if (!model) { setHint(hint, 'Could not add that model — please try again.', true); return; }

            upsertModelOption(modelSelect, model);
            modelSelect.disabled = false;
            modelSelect.value = model.id;
            loadVariants(model.id);
            showRow(false);
            setHint(hint, `“${model.name}” added — now selectable by everyone.`, false);
        });

        input?.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); saveBtn?.click(); }
        });
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
                model_id:             fd.get('model') || null,
                custom_make:          fd.get('custom_make') || null,
                custom_model:         fd.get('custom_model') || null,
                custom_name:          fd.get('custom_name') || null,
                plate_number:         fd.get('plate_number') || null,
                color:                fd.get('color') || null,
                purchase_date:        fd.get('purchase_date') || null,
                current_odometer_km:  parseInt(fd.get('current_odometer_km')) || 0,
                country_code:         fd.get('country_code') || 'NP',
                videos:               collectVideos(),
            };

            const { data: inserted, error } = await sb
                .from('user_vehicles')
                .insert(payload)
                .select('id')
                .single();

            if (error) {
                if (vehicleErr) { vehicleErr.textContent = error.message; vehicleErr.hidden = false; }
                submit.disabled = false;
                return;
            }

            // Upload photos to the private bucket, then store their paths
            const files = document.getElementById('ng-v-photos')?.files;
            if (files && files.length) {
                const paths = await uploadVehiclePhotos(inserted.id, files);
                if (paths.length) {
                    await sb.from('user_vehicles').update({ photos: paths }).eq('id', inserted.id);
                }
            }

            closeVehicleModal();
            await loadVehicles();
            submit.disabled = false;
        });
    }

    // Upload selected images to vehicle-photos/{uid}/{vehicleId}/… → return stored paths
    async function uploadVehiclePhotos(vehicleId, fileList) {
        const paths = [];
        for (let i = 0; i < fileList.length; i++) {
            const file = fileList[i];
            const safe = file.name.replace(/[^a-zA-Z0-9._-]/g, '_');
            const path = `${user.id}/${vehicleId}/${i}-${safe}`;
            const { error } = await sb.storage
                .from('vehicle-photos')
                .upload(path, file, { upsert: false, contentType: file.type || undefined });
            if (!error) paths.push(path);
        }
        return paths;
    }

    // ── Vehicle selector (for maintenance/fuel/docs tabs) ─────────────────────

    function buildVehicleSelector(context) {
        const selectorId = { maint: 'ng-maint-vehicle-selector', fuel: 'ng-fuel-vehicle-selector', doc: 'ng-doc-vehicle-selector', oblig: 'ng-oblig-vehicle-selector' }[context];
        const el = document.getElementById(selectorId);
        if (!el) return;

        if (!vehicles.length) {
            el.innerHTML = '<p style="font-size:.875rem;color:#64748b;">Add a vehicle first to log entries.</p>';
            return;
        }

        el.innerHTML = vehicles.map(function (v) {
            const label = v.custom_name || v.variant?.model?.name || v.model?.name || v.custom_model || 'Vehicle';
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
                if (context === 'oblig') loadObligations(activeVehicleId);
            });
        });

        // Auto-select first
        if (vehicles.length && !activeVehicleId) {
            activeVehicleId = vehicles[0].id;
            el.querySelector('.ng-vehicle-selector__btn')?.classList.add('is-active');
            if (context === 'maint') loadMaintenanceLogs(activeVehicleId);
            if (context === 'fuel')  loadFuelLogs(activeVehicleId);
            if (context === 'doc')   loadDocuments(activeVehicleId);
            if (context === 'oblig') loadObligations(activeVehicleId);
        } else if (vehicles.length && activeVehicleId) {
            // Re-entering a tab with an already-selected vehicle
            if (context === 'oblig') loadObligations(activeVehicleId);
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

    // ── Tax & Renewal obligations ─────────────────────────────────────────────

    const OBLIG_TYPE_LABELS = {
        yearly_vehicle_tax:    'Yearly Vehicle Tax',
        road_tax:              'Road / Transport Tax',
        insurance:             'Insurance Renewal',
        pollution:             'Pollution (Green Sticker)',
        registration_renewal: 'Registration Renewal',
        custom:                'Custom',
    };

    function activeVehicle() {
        return vehicles.find(v => v.id === activeVehicleId) || null;
    }

    async function loadObligations(vehicleId) {
        const grid = document.getElementById('ng-oblig-list');
        if (!grid) return;
        grid.innerHTML = '<div class="ng-loading">Loading…</div>';

        const { data } = await sb
            .from('vehicle_obligations')
            .select('*')
            .eq('user_vehicle_id', vehicleId)
            .eq('is_active', true)
            .order('next_due_date', { ascending: true });

        if (!data?.length) {
            grid.innerHTML = '<p class="ng-empty-state">No tax or renewal obligations yet. Add yearly tax, insurance, or pollution to start tracking due dates.</p>';
            return;
        }

        const now = new Date();
        grid.innerHTML = data.map(function (o) {
            const label = o.label || OBLIG_TYPE_LABELS[o.obligation_type] || o.obligation_type;
            let statusClass = '', dueStr = '';
            if (o.next_due_date) {
                const due = new Date(o.next_due_date);
                const daysLeft = Math.ceil((due - now) / 86400000);
                if (daysLeft < 0) {
                    statusClass = 'ng-doc-card--expired';
                    dueStr = `Overdue since ${o.next_due_date}`;
                } else if (daysLeft <= 30) {
                    statusClass = 'ng-doc-card--warning';
                    dueStr = `Due in ${daysLeft} days`;
                } else {
                    statusClass = 'ng-doc-card--valid';
                    dueStr = `Next due ${o.next_due_date}`;
                }
            }
            const amount = o.amount_paid ? `${esc(o.currency || 'NPR')} ${Number(o.amount_paid).toLocaleString()}` : '';
            const paid   = o.last_paid_date ? `Last paid ${esc(o.last_paid_date)}` : 'Not yet paid';
            return `
            <div class="ng-doc-card ${statusClass}">
                <div class="ng-doc-card__type">Every ${o.period_months} mo</div>
                <div class="ng-doc-card__title">${esc(label)}</div>
                <div class="ng-doc-card__sub">${paid}${amount ? ' · ' + amount : ''}</div>
                ${dueStr ? `<div class="ng-doc-card__expiry">${esc(dueStr)}</div>` : ''}
            </div>`;
        }).join('');
    }

    const obligOverlay = document.getElementById('ng-oblig-modal-overlay');
    const obligForm    = document.getElementById('ng-oblig-form');
    const obligErr     = document.getElementById('ng-oblig-error');

    document.getElementById('ng-add-oblig-btn')?.addEventListener('click', async function () {
        if (!activeVehicleId) { alert('Please select a vehicle first.'); return; }
        await populateObligationTypes();
        if (obligErr) obligErr.hidden = true;
        obligOverlay.classList.add('is-open'); obligOverlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    });
    document.getElementById('ng-oblig-modal-close')?.addEventListener('click', function () {
        obligOverlay.classList.remove('is-open'); document.body.style.overflow = '';
    });
    obligOverlay?.addEventListener('click', function (e) {
        if (e.target === obligOverlay) { obligOverlay.classList.remove('is-open'); document.body.style.overflow = ''; }
    });

    // Populate the obligation-type select from country templates (+ Custom)
    async function populateObligationTypes() {
        const sel = document.getElementById('ng-o-type');
        if (!sel) return;
        const v = activeVehicle();
        const country = (v && v.country_code) || 'NP';

        const { data } = await sb
            .from('obligation_templates')
            .select('obligation_type,label,default_period_months,display_order')
            .eq('country_code', country)
            .order('display_order', { ascending: true });

        obligTemplates = {};
        let opts = '<option value="">Select…</option>';
        (data || []).forEach(function (t) {
            obligTemplates[t.obligation_type] = t;
            opts += `<option value="${esc(t.obligation_type)}">${esc(t.label)}</option>`;
        });
        opts += '<option value="custom">Custom…</option>';
        sel.innerHTML = opts;
    }

    let obligTemplates = {};

    // Type change → prefill period, toggle custom label field
    document.getElementById('ng-o-type')?.addEventListener('change', function () {
        const type = this.value;
        const labelGroup = document.getElementById('ng-o-label-group');
        const periodInput = document.getElementById('ng-o-period');
        if (labelGroup) labelGroup.hidden = (type !== 'custom');
        const tpl = obligTemplates[type];
        if (tpl && periodInput) periodInput.value = tpl.default_period_months || 12;
        recomputeNextDue();
    });

    document.getElementById('ng-o-paid')?.addEventListener('change', recomputeNextDue);
    document.getElementById('ng-o-period')?.addEventListener('input', recomputeNextDue);

    function recomputeNextDue() {
        const paid   = document.getElementById('ng-o-paid')?.value;
        const months = parseInt(document.getElementById('ng-o-period')?.value);
        const next   = document.getElementById('ng-o-next');
        if (!next) return;
        if (paid && months > 0) {
            const d = new Date(paid + 'T00:00:00');
            d.setMonth(d.getMonth() + months);
            next.value = d.toISOString().slice(0, 10);
        } else {
            next.value = '';
        }
    }

    if (obligForm) {
        obligForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            const submit = obligForm.querySelector('[type=submit]');
            submit.disabled = true;
            if (obligErr) obligErr.hidden = true;

            const fd = new FormData(obligForm);
            const type = fd.get('obligation_type');
            const v = activeVehicle();
            const payload = {
                user_vehicle_id: activeVehicleId,
                user_id:         user.id,
                country_code:    (v && v.country_code) || 'NP',
                obligation_type: type,
                label:           type === 'custom' ? (fd.get('label') || 'Custom') : (OBLIG_TYPE_LABELS[type] || null),
                last_paid_date:  fd.get('last_paid_date') || null,
                period_months:   parseInt(fd.get('period_months')) || 12,
                next_due_date:   fd.get('next_due_date') || null,
                amount_paid:     fd.get('amount_paid') ? parseFloat(fd.get('amount_paid')) : null,
                notes:           fd.get('notes') || null,
            };

            const { error } = await sb.from('vehicle_obligations').insert(payload);
            if (error) {
                if (obligErr) { obligErr.textContent = error.message; obligErr.hidden = false; }
            } else {
                obligOverlay.classList.remove('is-open'); document.body.style.overflow = '';
                obligForm.reset();
                document.getElementById('ng-o-label-group').hidden = true;
                loadObligations(activeVehicleId);
                loadUpcoming();
            }
            submit.disabled = false;
        });
    }

    // ── Upcoming & Overdue (aggregated across docs + maintenance + obligations) ─

    async function loadUpcoming() {
        const box = document.getElementById('ng-upcoming');
        if (!box || !vehicles.length) return;

        const ids = vehicles.map(v => v.id);
        const vName = {};
        vehicles.forEach(v => { vName[v.id] = v.custom_name || v.variant?.model?.name || v.model?.name || v.custom_model || 'Vehicle'; });

        const [docs, maint, obligs] = await Promise.all([
            sb.from('documents').select('user_vehicle_id,title,doc_type,expiry_date,reminder_days').in('user_vehicle_id', ids),
            sb.from('maintenance_logs').select('user_vehicle_id,maintenance_type,next_due_date').in('user_vehicle_id', ids).not('next_due_date', 'is', null),
            sb.from('vehicle_obligations').select('user_vehicle_id,obligation_type,label,next_due_date').in('user_vehicle_id', ids).eq('is_active', true).not('next_due_date', 'is', null),
        ]);

        const items = [];
        (docs.data || []).forEach(function (d) {
            if (!d.expiry_date) return;
            items.push({ vehicle: vName[d.user_vehicle_id], label: d.title || labelize(d.doc_type), date: d.expiry_date, reminder: d.reminder_days || 14, kind: 'Document' });
        });
        (maint.data || []).forEach(function (m) {
            items.push({ vehicle: vName[m.user_vehicle_id], label: labelize(m.maintenance_type), date: m.next_due_date, reminder: 14, kind: 'Service' });
        });
        (obligs.data || []).forEach(function (o) {
            items.push({ vehicle: vName[o.user_vehicle_id], label: o.label || labelize(o.obligation_type), date: o.next_due_date, reminder: 30, kind: 'Tax/Renewal' });
        });

        const now = new Date();
        const relevant = items
            .map(function (it) {
                const due = new Date(it.date + 'T00:00:00');
                it.daysLeft = Math.ceil((due - now) / 86400000);
                return it;
            })
            .filter(it => it.daysLeft <= (it.reminder || 14))   // overdue or within its reminder window
            .sort((a, b) => a.daysLeft - b.daysLeft);

        if (!relevant.length) { box.hidden = true; box.innerHTML = ''; return; }

        box.innerHTML =
            '<div class="ng-upcoming__title">Upcoming &amp; Overdue</div>' +
            '<div class="ng-upcoming__list">' +
            relevant.map(function (it) {
                let cls = 'ng-upcoming__item--ok', when;
                if (it.daysLeft < 0)       { cls = 'ng-upcoming__item--overdue'; when = `Overdue ${Math.abs(it.daysLeft)}d`; }
                else if (it.daysLeft <= 7) { cls = 'ng-upcoming__item--soon';    when = `${it.daysLeft}d left`; }
                else                       { cls = 'ng-upcoming__item--soon';    when = `${it.daysLeft}d left`; }
                return `
                <div class="ng-upcoming__item ${cls}">
                    <span class="ng-upcoming__kind">${esc(it.kind)}</span>
                    <span class="ng-upcoming__label">${esc(it.label)} — ${esc(it.vehicle)}</span>
                    <span class="ng-upcoming__when">${esc(when)}</span>
                </div>`;
            }).join('') +
            '</div>';
        box.hidden = false;
    }

    function labelize(s) {
        return String(s || '').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
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

    // Escape a value for safe use inside a CSS attribute selector
    function cssEsc(str) {
        if (window.CSS && CSS.escape) return CSS.escape(String(str));
        return String(str).replace(/["\\\]]/g, '\\$&');
    }

    function today() {
        return new Date().toISOString().slice(0, 10);
    }

    // ── Start ─────────────────────────────────────────────────────────────────

    init();

})();
