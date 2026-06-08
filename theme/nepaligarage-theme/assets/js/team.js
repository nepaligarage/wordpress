/* NepaliGarage — Team Dashboard */
(function () {
    'use strict';

    var sb;

    // ── Bootstrap ─────────────────────────────────────────────────────────────

    document.addEventListener('DOMContentLoaded', async function () {
        if (!window.ngSupabase) { return; }
        sb = window.ngSupabase;

        var res  = await sb.auth.getUser();
        var user = res.data && res.data.user;

        if (!user) { showGate(); return; }

        var role = (user.app_metadata && user.app_metadata.team_role) || '';
        if (role !== 'admin' && role !== 'operator') { showDenied(); return; }

        showDashboard(user, role);
        bindNav();
        bindForms(role);
        loadTab('overview');
    });

    // ── View switches ─────────────────────────────────────────────────────────

    function showGate() {
        document.getElementById('ng-team-gate').hidden = false;
        bindLoginForm();
    }

    function showDenied() {
        document.getElementById('ng-team-denied').hidden = false;
        qs('#ng-team-signout-denied').addEventListener('click', function () { sb.auth.signOut().then(function () { location.reload(); }); });
    }

    function showDashboard(user, role) {
        document.getElementById('ng-team-gate').hidden = true;
        document.getElementById('ng-team').hidden = false;
        qs('#ng-team-user-label').textContent = user.email;
        qs('#ng-team-signout').addEventListener('click', function () { sb.auth.signOut().then(function () { location.reload(); }); });
    }

    // ── Login ──────────────────────────────────────────────────────────────────

    function bindLoginForm() {
        var form = qs('#ng-team-login-form');
        if (!form) return;
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            var btn = qs('#ng-tl-submit');
            var err = qs('#ng-team-login-error');
            btn.disabled = true;
            btn.textContent = 'Signing in…';
            err.hidden = true;

            var res = await sb.auth.signInWithPassword({
                email:    qs('#ng-tl-email').value.trim(),
                password: qs('#ng-tl-password').value,
            });

            if (res.error) {
                err.textContent = res.error.message;
                err.hidden = false;
                btn.disabled = false;
                btn.textContent = 'Sign In';
                return;
            }
            location.reload();
        });
    }

    // ── Navigation ────────────────────────────────────────────────────────────

    function bindNav() {
        qsa('.ng-team__nav-item').forEach(function (item) {
            item.addEventListener('click', function () {
                var tab = item.dataset.tab;
                qsa('.ng-team__nav-item').forEach(function (i) { i.classList.remove('is-active'); });
                item.classList.add('is-active');
                qsa('.ng-team__panel').forEach(function (p) { p.classList.remove('is-active'); });
                var panel = qs('[data-panel="' + tab + '"]');
                if (panel) panel.classList.add('is-active');
                loadTab(tab);
            });
        });
    }

    function loadTab(tab) {
        if (tab === 'overview')    loadOverview();
        if (tab === 'brands')      loadBrands();
        if (tab === 'vehicles')    loadModels();
        if (tab === 'accessories') loadAccessories();
        if (tab === 'leads')       loadLeads();
        if (tab === 'showrooms')   loadShowrooms();
        if (tab === 'garages')     loadGarages();
    }

    // ── Overview ──────────────────────────────────────────────────────────────

    async function loadOverview() {
        var [brands, models, leads, showrooms] = await Promise.all([
            sb.from('brands').select('id', { count: 'exact', head: true }),
            sb.from('models').select('id', { count: 'exact', head: true }),
            sb.from('leads').select('id', { count: 'exact', head: true }).eq('status', 'new'),
            sb.from('showrooms').select('id', { count: 'exact', head: true }),
        ]);
        setText('stat-brands',    brands.count    ?? '—');
        setText('stat-models',    models.count    ?? '—');
        setText('stat-leads',     leads.count     ?? '—');
        setText('stat-showrooms', showrooms.count ?? '—');

        var res = await sb.from('leads').select('id,name,phone,type,status,created_at').order('created_at', { ascending: false }).limit(10);
        qs('#ng-overview-leads').innerHTML = leadsTableHTML(res.data || []);
    }

    // ── Brands ────────────────────────────────────────────────────────────────

    async function loadBrands() {
        var res = await sb.from('brands').select('*').order('name');
        var rows = (res.data || []).map(function (b) {
            return '<tr>' +
                '<td>' + (b.logo_url ? '<img class="ng-team-logo" src="' + esc(b.logo_url) + '" alt="">' : '—') + '</td>' +
                '<td><strong>' + esc(b.name) + '</strong></td>' +
                '<td><code>' + esc(b.slug) + '</code></td>' +
                '<td class="ng-team-actions">' +
                  btn('Edit', 'ng-btn--sm ng-btn--outline', 'data-edit-brand=\'' + esc(JSON.stringify(b)) + '\'') +
                  btn('Delete', 'ng-btn--sm ng-btn--outline ng-btn--danger', 'data-del-brand="' + b.id + '"') +
                '</td></tr>';
        });
        qs('#ng-brands-list').innerHTML = rows.length
            ? tableHTML(['Logo','Name','Slug',''], rows)
            : '<p class="ng-team-empty">No brands yet.</p>';

        qsa('[data-edit-brand]').forEach(function (el) {
            el.addEventListener('click', function () { openBrandForm(JSON.parse(el.dataset.editBrand)); });
        });
        qsa('[data-del-brand]').forEach(function (el) {
            el.addEventListener('click', async function () {
                if (!confirm('Delete this brand?')) return;
                await sb.from('brands').delete().eq('id', el.dataset.delBrand);
                loadBrands();
            });
        });
    }

    function openBrandForm(brand) {
        qs('#brand-edit-id').value  = brand ? brand.id   : '';
        qs('#brand-name').value     = brand ? brand.name : '';
        qs('#brand-slug').value     = brand ? brand.slug : '';
        qs('#brand-logo').value     = brand ? (brand.logo_url || '') : '';
        qs('#ng-brand-form').hidden = false;
        qs('#ng-brand-form').scrollIntoView({ behavior: 'smooth' });
    }

    // ── Models / Variants ─────────────────────────────────────────────────────

    async function loadModels() {
        var brandsRes = await sb.from('brands').select('id,name').order('name');
        var brands    = brandsRes.data || [];

        // Populate brand dropdowns
        var opts = brands.map(function (b) { return '<option value="' + b.id + '">' + esc(b.name) + '</option>'; }).join('');
        qs('#model-brand-id').innerHTML  = '<option value="">— select brand —</option>' + opts;

        var res  = await sb.from('models').select('*,brands(name)').order('name');
        var rows = (res.data || []).map(function (m) {
            return '<tr>' +
                '<td>' + esc((m.brands && m.brands.name) || '—') + '</td>' +
                '<td><strong>' + esc(m.name) + '</strong></td>' +
                '<td><code>' + esc(m.slug) + '</code></td>' +
                '<td class="ng-team-actions">' +
                  btn('+ Variant', 'ng-btn--sm ng-btn--primary', 'data-add-variant="' + m.id + '" data-model-name="' + esc(m.name) + '"') +
                  btn('Edit', 'ng-btn--sm ng-btn--outline', 'data-edit-model=\'' + esc(JSON.stringify({id:m.id,brand_id:m.brand_id,name:m.name,slug:m.slug,logo_url:m.logo_url})) + '\'') +
                '</td></tr>';
        });
        qs('#ng-models-list').innerHTML = rows.length
            ? tableHTML(['Brand','Model','Slug',''], rows)
            : '<p class="ng-team-empty">No models yet.</p>';

        qsa('[data-add-variant]').forEach(function (el) {
            el.addEventListener('click', function () { openVariantForm(el.dataset.addVariant, el.dataset.modelName); });
        });
        qsa('[data-edit-model]').forEach(function (el) {
            el.addEventListener('click', function () { openModelForm(JSON.parse(el.dataset.editModel)); });
        });

        // Populate variant model dropdown
        var mOpts = (res.data || []).map(function (m) { return '<option value="' + m.id + '">' + esc(m.name) + '</option>'; }).join('');
        qs('#variant-model-id').innerHTML = '<option value="">— select model —</option>' + mOpts;
    }

    function openModelForm(model) {
        qs('#model-edit-id').value   = model ? model.id       : '';
        qs('#model-brand-id').value  = model ? model.brand_id : '';
        qs('#model-name').value      = model ? model.name     : '';
        qs('#model-slug').value      = model ? model.slug     : '';
        qs('#model-logo').value      = model ? (model.logo_url || '') : '';
        qs('#ng-model-form').hidden  = false;
        qs('#ng-model-form').scrollIntoView({ behavior: 'smooth' });
    }

    function openVariantForm(modelId, modelName) {
        qs('#variant-edit-id').value    = '';
        qs('#variant-model-id').value   = modelId || '';
        qs('#variant-name').value       = '';
        qs('#variant-slug').value       = '';
        qs('#variant-price').value      = '';
        qs('#variant-battery').value    = '';
        qs('#variant-range').value      = '';
        qs('#variant-image').value      = '';
        qs('#variant-available').checked = true;
        qs('#variant-form-title').textContent = 'Add Variant' + (modelName ? ' — ' + modelName : '');
        qs('#ng-variant-form').hidden   = false;
        qs('#ng-variant-form').scrollIntoView({ behavior: 'smooth' });
    }

    // ── Accessories ───────────────────────────────────────────────────────────

    async function loadAccessories() {
        var res  = await sb.from('accessories').select('*').order('name');
        var rows = (res.data || []).map(function (a) {
            return '<tr>' +
                '<td><strong>' + esc(a.name) + '</strong></td>' +
                '<td>' + esc(a.category || '—') + '</td>' +
                '<td>' + (a.price_npr ? 'NPR ' + Number(a.price_npr).toLocaleString() : '—') + '</td>' +
                '<td class="ng-team-actions">' +
                  btn('Edit', 'ng-btn--sm ng-btn--outline', 'data-edit-acc=\'' + esc(JSON.stringify(a)) + '\'') +
                  btn('Delete', 'ng-btn--sm ng-btn--outline ng-btn--danger', 'data-del-acc="' + a.id + '"') +
                '</td></tr>';
        });
        qs('#ng-acc-list').innerHTML = rows.length
            ? tableHTML(['Name','Category','Price',''], rows)
            : '<p class="ng-team-empty">No accessories yet.</p>';

        qsa('[data-edit-acc]').forEach(function (el) {
            el.addEventListener('click', function () { openAccForm(JSON.parse(el.dataset.editAcc)); });
        });
        qsa('[data-del-acc]').forEach(function (el) {
            el.addEventListener('click', async function () {
                if (!confirm('Delete this accessory?')) return;
                await sb.from('accessories').delete().eq('id', el.dataset.delAcc);
                loadAccessories();
            });
        });
    }

    function openAccForm(acc) {
        qs('#acc-edit-id').value    = acc ? acc.id          : '';
        qs('#acc-name').value       = acc ? acc.name        : '';
        qs('#acc-category').value   = acc ? (acc.category   || '') : '';
        qs('#acc-price').value      = acc ? (acc.price_npr  || '') : '';
        qs('#acc-desc').value       = acc ? (acc.description|| '') : '';
        qs('#acc-image').value      = acc ? (acc.image_url  || '') : '';
        qs('#acc-affiliate').value  = acc ? (acc.affiliate_url || '') : '';
        qs('#ng-acc-form').hidden   = false;
        qs('#ng-acc-form').scrollIntoView({ behavior: 'smooth' });
    }

    // ── Leads ─────────────────────────────────────────────────────────────────

    async function loadLeads(status) {
        var query = sb.from('leads').select('id,name,phone,email,type,status,created_at').order('created_at', { ascending: false }).limit(50);
        if (status) query = query.eq('status', status);
        var res = await query;
        qs('#ng-leads-list').innerHTML = leadsTableHTML(res.data || []);

        qs('#ng-lead-filter-status').addEventListener('change', function () {
            loadLeads(this.value || undefined);
        });
    }

    function leadsTableHTML(leads) {
        if (!leads.length) return '<p class="ng-team-empty">No leads found.</p>';
        var rows = leads.map(function (l) {
            var d = l.created_at ? new Date(l.created_at).toLocaleDateString() : '—';
            return '<tr>' +
                '<td>' + esc(l.name || '—') + '</td>' +
                '<td>' + esc(l.phone || '—') + '</td>' +
                '<td>' + esc(l.type || '—') + '</td>' +
                '<td><span class="ng-team-badge ng-team-badge--' + esc(l.status || 'new') + '">' + esc(l.status || 'new') + '</span></td>' +
                '<td>' + d + '</td>' +
                '</tr>';
        });
        return tableHTML(['Name','Phone','Type','Status','Date'], rows);
    }

    // ── Showrooms ─────────────────────────────────────────────────────────────

    async function loadShowrooms() {
        var res  = await sb.from('showrooms').select('*').order('name');
        var rows = (res.data || []).map(function (s) {
            return '<tr>' +
                '<td><strong>' + esc(s.name) + '</strong></td>' +
                '<td>' + esc(s.city || '—') + '</td>' +
                '<td>' + esc((s.brand_slugs || []).join(', ') || '—') + '</td>' +
                '<td>' + esc(s.contact_phone || '—') + '</td>' +
                '<td class="ng-team-actions">' +
                  btn('Edit', 'ng-btn--sm ng-btn--outline', 'data-edit-showroom=\'' + esc(JSON.stringify(s)) + '\'') +
                  btn('Delete', 'ng-btn--sm ng-btn--outline ng-btn--danger', 'data-del-showroom="' + s.id + '"') +
                '</td></tr>';
        });
        qs('#ng-showrooms-list').innerHTML = rows.length
            ? tableHTML(['Name','City','Brands','Phone',''], rows)
            : '<p class="ng-team-empty">No showrooms yet.</p>';

        qsa('[data-edit-showroom]').forEach(function (el) {
            el.addEventListener('click', function () { openShowroomForm(JSON.parse(el.dataset.editShowroom)); });
        });
        qsa('[data-del-showroom]').forEach(function (el) {
            el.addEventListener('click', async function () {
                if (!confirm('Delete this showroom?')) return;
                await sb.from('showrooms').delete().eq('id', el.dataset.delShowroom);
                loadShowrooms();
            });
        });
    }

    function openShowroomForm(s) {
        qs('#showroom-edit-id').value  = s ? s.id    : '';
        qs('#showroom-name').value     = s ? s.name  : '';
        qs('#showroom-city').value     = s ? (s.city || '') : '';
        qs('#showroom-phone').value    = s ? (s.contact_phone || '') : '';
        qs('#showroom-email').value    = s ? (s.contact_email || '') : '';
        qs('#showroom-address').value  = s ? (s.address || '') : '';
        qs('#showroom-brands').value   = s ? ((s.brand_slugs || []).join(',')) : '';
        qs('#showroom-maps').value     = s ? (s.google_maps || '') : '';
        qs('#ng-showroom-form').hidden = false;
        qs('#ng-showroom-form').scrollIntoView({ behavior: 'smooth' });
    }

    // ── Garages ───────────────────────────────────────────────────────────────

    async function loadGarages() {
        var res  = await sb.from('upgrade_garages').select('*').order('name');
        var rows = (res.data || []).map(function (g) {
            return '<tr>' +
                '<td><strong>' + esc(g.name) + '</strong></td>' +
                '<td>' + esc(g.city || '—') + '</td>' +
                '<td>' + esc((g.specialties || []).join(', ') || '—') + '</td>' +
                '<td class="ng-team-actions">' +
                  btn('Edit', 'ng-btn--sm ng-btn--outline', 'data-edit-garage=\'' + esc(JSON.stringify(g)) + '\'') +
                  btn('Delete', 'ng-btn--sm ng-btn--outline ng-btn--danger', 'data-del-garage="' + g.id + '"') +
                '</td></tr>';
        });
        qs('#ng-garages-list').innerHTML = rows.length
            ? tableHTML(['Name','City','Specialties',''], rows)
            : '<p class="ng-team-empty">No garages yet.</p>';

        qsa('[data-edit-garage]').forEach(function (el) {
            el.addEventListener('click', function () { openGarageForm(JSON.parse(el.dataset.editGarage)); });
        });
        qsa('[data-del-garage]').forEach(function (el) {
            el.addEventListener('click', async function () {
                if (!confirm('Delete this garage?')) return;
                await sb.from('upgrade_garages').delete().eq('id', el.dataset.delGarage);
                loadGarages();
            });
        });
    }

    function openGarageForm(g) {
        qs('#garage-edit-id').value  = g ? g.id    : '';
        qs('#garage-name').value     = g ? g.name  : '';
        qs('#garage-city').value     = g ? (g.city || '') : '';
        qs('#garage-phone').value    = g ? (g.contact_phone || '') : '';
        qs('#garage-email').value    = g ? (g.contact_email || '') : '';
        qs('#garage-specs').value    = g ? ((g.specialties || []).join(',')) : '';
        qs('#garage-address').value  = g ? (g.address || '') : '';
        qs('#garage-maps').value     = g ? (g.google_maps || '') : '';
        qs('#ng-garage-form').hidden = false;
        qs('#ng-garage-form').scrollIntoView({ behavior: 'smooth' });
    }

    // ── Form bindings ─────────────────────────────────────────────────────────

    function bindForms(role) {
        // Brand form
        qs('#btn-add-brand').addEventListener('click', function () { openBrandForm(null); });
        qs('#btn-cancel-brand').addEventListener('click', function () { qs('#ng-brand-form').hidden = true; });
        qs('#ng-brand-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            var id   = qs('#brand-edit-id').value;
            var data = { name: qs('#brand-name').value.trim(), slug: qs('#brand-slug').value.trim(), logo_url: qs('#brand-logo').value.trim() || null };
            if (id) { await sb.from('brands').update(data).eq('id', id); }
            else    { await sb.from('brands').insert(data); }
            qs('#ng-brand-form').hidden = true;
            loadBrands();
        });

        // Model form
        qs('#btn-add-model').addEventListener('click', function () { openModelForm(null); });
        qs('#btn-cancel-model').addEventListener('click', function () { qs('#ng-model-form').hidden = true; });
        qs('#ng-model-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            var id   = qs('#model-edit-id').value;
            var data = { brand_id: qs('#model-brand-id').value, name: qs('#model-name').value.trim(), slug: qs('#model-slug').value.trim(), logo_url: qs('#model-logo').value.trim() || null };
            if (id) { await sb.from('models').update(data).eq('id', id); }
            else    { await sb.from('models').insert(data); }
            qs('#ng-model-form').hidden = true;
            loadModels();
        });

        // Variant form
        qs('#btn-cancel-variant').addEventListener('click', function () { qs('#ng-variant-form').hidden = true; });
        qs('#ng-variant-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            var id   = qs('#variant-edit-id').value;
            var data = {
                model_id:           qs('#variant-model-id').value,
                name:               qs('#variant-name').value.trim(),
                slug:               qs('#variant-slug').value.trim(),
                price_npr:          qs('#variant-price').value  || null,
                battery_kwh:        qs('#variant-battery').value || null,
                range_km:           qs('#variant-range').value  || null,
                image_url:          qs('#variant-image').value.trim() || null,
                is_available_nepal: qs('#variant-available').checked,
            };
            if (id) { await sb.from('variants').update(data).eq('id', id); }
            else    { await sb.from('variants').insert(data); }
            qs('#ng-variant-form').hidden = true;
            loadModels();
        });

        // Accessory form
        qs('#btn-add-acc').addEventListener('click', function () { openAccForm(null); });
        qs('#btn-cancel-acc').addEventListener('click', function () { qs('#ng-acc-form').hidden = true; });
        qs('#ng-acc-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            var id   = qs('#acc-edit-id').value;
            var data = { name: qs('#acc-name').value.trim(), category: qs('#acc-category').value.trim() || null, price_npr: qs('#acc-price').value || null, description: qs('#acc-desc').value.trim() || null, image_url: qs('#acc-image').value.trim() || null, affiliate_url: qs('#acc-affiliate').value.trim() || null };
            if (id) { await sb.from('accessories').update(data).eq('id', id); }
            else    { await sb.from('accessories').insert(data); }
            qs('#ng-acc-form').hidden = true;
            loadAccessories();
        });

        // Showroom form
        qs('#btn-add-showroom').addEventListener('click', function () { openShowroomForm(null); });
        qs('#btn-cancel-showroom').addEventListener('click', function () { qs('#ng-showroom-form').hidden = true; });
        qs('#ng-showroom-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            var id     = qs('#showroom-edit-id').value;
            var brands = qs('#showroom-brands').value.split(',').map(function (s) { return s.trim(); }).filter(Boolean);
            var data   = { name: qs('#showroom-name').value.trim(), city: qs('#showroom-city').value.trim() || null, contact_phone: qs('#showroom-phone').value.trim() || null, contact_email: qs('#showroom-email').value.trim() || null, address: qs('#showroom-address').value.trim() || null, brand_slugs: brands, google_maps: qs('#showroom-maps').value.trim() || null };
            if (id) { await sb.from('showrooms').update(data).eq('id', id); }
            else    { await sb.from('showrooms').insert(data); }
            qs('#ng-showroom-form').hidden = true;
            loadShowrooms();
        });

        // Garage form
        qs('#btn-add-garage').addEventListener('click', function () { openGarageForm(null); });
        qs('#btn-cancel-garage').addEventListener('click', function () { qs('#ng-garage-form').hidden = true; });
        qs('#ng-garage-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            var id    = qs('#garage-edit-id').value;
            var specs = qs('#garage-specs').value.split(',').map(function (s) { return s.trim(); }).filter(Boolean);
            var data  = { name: qs('#garage-name').value.trim(), city: qs('#garage-city').value.trim() || null, contact_phone: qs('#garage-phone').value.trim() || null, contact_email: qs('#garage-email').value.trim() || null, specialties: specs, address: qs('#garage-address').value.trim() || null, google_maps: qs('#garage-maps').value.trim() || null };
            if (id) { await sb.from('upgrade_garages').update(data).eq('id', id); }
            else    { await sb.from('upgrade_garages').insert(data); }
            qs('#ng-garage-form').hidden = true;
            loadGarages();
        });
    }

    // ── Utilities ─────────────────────────────────────────────────────────────

    function qs(sel)  { return document.querySelector(sel); }
    function qsa(sel) { return document.querySelectorAll(sel); }
    function setText(id, val) { var el = document.getElementById(id); if (el) el.textContent = val; }
    function esc(str) { return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
    function btn(label, cls, attrs) { return '<button class="ng-btn ' + cls + '" ' + (attrs || '') + '>' + label + '</button>'; }
    function tableHTML(headers, rows) {
        var ths = headers.map(function (h) { return '<th>' + h + '</th>'; }).join('');
        return '<table class="ng-team-table"><thead><tr>' + ths + '</tr></thead><tbody>' + rows.join('') + '</tbody></table>';
    }

})();
