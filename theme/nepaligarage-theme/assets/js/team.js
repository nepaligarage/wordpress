/* NepaliGarage — Team Dashboard */
(function () {
    'use strict';

    var sb;
    var state = {
        contentModels: [],
        selectedModelId: '',
        selectedModel: null,
    };

    document.addEventListener('DOMContentLoaded', async function () {
        if (!window.ngSupabase) return;
        sb = window.ngSupabase;

        var res  = await sb.auth.getUser();
        var user = res.data && res.data.user;

        if (!user) {
            showGate();
            return;
        }

        var role = (user.app_metadata && user.app_metadata.team_role) || '';
        if (role !== 'admin' && role !== 'operator') {
            showDenied();
            return;
        }

        showDashboard(user);
        bindNav();
        bindForms();
        loadTab('overview');
    });

    function showGate() {
        document.getElementById('ng-team-gate').hidden = false;
        bindLoginForm();
    }

    function showDenied() {
        document.getElementById('ng-team-denied').hidden = false;
        qs('#ng-team-signout-denied').addEventListener('click', function () {
            sb.auth.signOut().then(function () { location.reload(); });
        });
    }

    function showDashboard(user) {
        document.getElementById('ng-team-gate').hidden = true;
        document.getElementById('ng-team').hidden = false;
        qs('#ng-team-user-label').textContent = user.email;
        qs('#ng-team-signout').addEventListener('click', function () {
            sb.auth.signOut().then(function () { location.reload(); });
        });
    }

    function bindLoginForm() {
        var form = qs('#ng-team-login-form');
        if (!form || form.dataset.bound === '1') return;
        form.dataset.bound = '1';
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            var btn = qs('#ng-tl-submit');
            var err = qs('#ng-team-login-error');
            btn.disabled = true;
            btn.textContent = 'Signing in…';
            err.hidden = true;

            var res = await sb.auth.signInWithPassword({
                email: qs('#ng-tl-email').value.trim(),
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
        if (tab === 'overview')         loadOverview();
        if (tab === 'brands')           loadBrands();
        if (tab === 'vehicles')         loadModels();
        if (tab === 'research-content') loadResearchContent();
        if (tab === 'accessories')      loadAccessories();
        if (tab === 'leads')            loadLeads();
        if (tab === 'showrooms')        loadShowrooms();
        if (tab === 'garages')          loadGarages();
    }

    async function loadOverview() {
        var [brands, models, leads, showrooms] = await Promise.all([
            sb.from('brands').select('id', { count: 'exact', head: true }),
            sb.from('models').select('id', { count: 'exact', head: true }),
            sb.from('leads').select('id', { count: 'exact', head: true }).eq('status', 'new'),
            sb.from('showrooms').select('id', { count: 'exact', head: true }),
        ]);

        setText('stat-brands', brands.count ?? '—');
        setText('stat-models', models.count ?? '—');
        setText('stat-leads', leads.count ?? '—');
        setText('stat-showrooms', showrooms.count ?? '—');

        var res = await sb.from('leads').select('id,name,phone,email,type,status,created_at').order('created_at', { ascending: false }).limit(10);
        qs('#ng-overview-leads').innerHTML = leadsTableHTML(res.data || []);
    }

    async function loadBrands() {
        var res = await sb.from('brands').select('*').order('name');
        var rows = (res.data || []).map(function (b) {
            return '<tr>' +
                '<td>' + (b.logo_url ? '<img class="ng-team-logo" src="' + esc(b.logo_url) + '" alt="">' : '—') + '</td>' +
                '<td><strong>' + esc(b.name) + '</strong></td>' +
                '<td><code>' + esc(b.slug) + '</code></td>' +
                '<td class="ng-team-actions">' +
                  btn('Edit', 'ng-btn--sm ng-btn--outline', 'data-edit-brand=\'' + escAttrJSON(b) + '\'') +
                  btn('Delete', 'ng-btn--sm ng-btn--outline ng-btn--danger', 'data-del-brand="' + esc(b.id) + '"') +
                '</td></tr>';
        });
        qs('#ng-brands-list').innerHTML = rows.length ? tableHTML(['Logo', 'Name', 'Slug', ''], rows) : '<p class="ng-team-empty">No brands yet.</p>';

        qsa('[data-edit-brand]').forEach(function (el) {
            el.addEventListener('click', function () { openBrandForm(parseDataAttrJSON(el.dataset.editBrand)); });
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
        qs('#brand-edit-id').value = brand ? brand.id : '';
        qs('#brand-name').value = brand ? brand.name : '';
        qs('#brand-slug').value = brand ? brand.slug : '';
        qs('#brand-logo').value = brand ? (brand.logo_url || '') : '';
        qs('#ng-brand-form').hidden = false;
        qs('#ng-brand-form').scrollIntoView({ behavior: 'smooth' });
    }

    async function loadModels() {
        var brandsRes = await sb.from('brands').select('id,name').order('name');
        var brands = brandsRes.data || [];
        var opts = brands.map(function (b) { return '<option value="' + esc(b.id) + '">' + esc(b.name) + '</option>'; }).join('');
        qs('#model-brand-id').innerHTML = '<option value="">— select brand —</option>' + opts;

        var res = await sb.from('models').select('*,brands(name)').order('name');
        var rows = (res.data || []).map(function (m) {
            return '<tr>' +
                '<td>' + esc((m.brands && m.brands.name) || '—') + '</td>' +
                '<td><strong>' + esc(m.name) + '</strong></td>' +
                '<td><code>' + esc(m.slug) + '</code></td>' +
                '<td class="ng-team-actions">' +
                  btn('+ Variant', 'ng-btn--sm ng-btn--primary', 'data-add-variant="' + esc(m.id) + '" data-model-name="' + esc(m.name) + '"') +
                  btn('Edit', 'ng-btn--sm ng-btn--outline', 'data-edit-model=\'' + escAttrJSON({id:m.id,brand_id:m.brand_id,name:m.name,slug:m.slug,logo_url:m.logo_url}) + '\'') +
                '</td></tr>';
        });
        qs('#ng-models-list').innerHTML = rows.length ? tableHTML(['Brand', 'Model', 'Slug', ''], rows) : '<p class="ng-team-empty">No models yet.</p>';

        qsa('[data-add-variant]').forEach(function (el) {
            el.addEventListener('click', function () { openVariantForm(el.dataset.addVariant, el.dataset.modelName); });
        });
        qsa('[data-edit-model]').forEach(function (el) {
            el.addEventListener('click', function () { openModelForm(parseDataAttrJSON(el.dataset.editModel)); });
        });

        var mOpts = (res.data || []).map(function (m) { return '<option value="' + esc(m.id) + '">' + esc(m.name) + '</option>'; }).join('');
        qs('#variant-model-id').innerHTML = '<option value="">— select model —</option>' + mOpts;
    }

    function openModelForm(model) {
        qs('#model-edit-id').value = model ? model.id : '';
        qs('#model-brand-id').value = model ? model.brand_id : '';
        qs('#model-name').value = model ? model.name : '';
        qs('#model-slug').value = model ? model.slug : '';
        qs('#model-logo').value = model ? (model.logo_url || '') : '';
        qs('#ng-model-form').hidden = false;
        qs('#ng-model-form').scrollIntoView({ behavior: 'smooth' });
    }

    function openVariantForm(modelId, modelName) {
        qs('#variant-edit-id').value = '';
        qs('#variant-model-id').value = modelId || '';
        qs('#variant-name').value = '';
        qs('#variant-slug').value = '';
        qs('#variant-price').value = '';
        qs('#variant-battery').value = '';
        qs('#variant-range').value = '';
        qs('#variant-image').value = '';
        qs('#variant-available').checked = true;
        qs('#variant-form-title').textContent = 'Add Variant' + (modelName ? ' — ' + modelName : '');
        qs('#ng-variant-form').hidden = false;
        qs('#ng-variant-form').scrollIntoView({ behavior: 'smooth' });
    }

    async function loadResearchContent() {
        clearContentNotice();
        var selectedId = localStorage.getItem('ngSelectedContentModel') || state.selectedModelId || '';
        var res = await sb.from('models').select('id,name,slug,brochure_url,brands(name,slug)').order('name');
        state.contentModels = res.data || [];

        var options = state.contentModels.map(function (m) {
            var brandName = m.brands && m.brands.name ? m.brands.name + ' ' : '';
            return '<option value="' + esc(m.id) + '">' + esc(brandName + m.name) + '</option>';
        }).join('');
        qs('#ng-content-model-select').innerHTML = '<option value="">— select a model —</option>' + options;

        if (selectedId && state.contentModels.some(function (m) { return m.id === selectedId; })) {
            qs('#ng-content-model-select').value = selectedId;
            state.selectedModelId = selectedId;
            state.selectedModel = findContentModel(selectedId);
            await loadSelectedModelContent();
        } else {
            state.selectedModelId = '';
            state.selectedModel = null;
            renderNoContentModel();
        }
    }

    function findContentModel(modelId) {
        return state.contentModels.find(function (m) { return m.id === modelId; }) || null;
    }

    function renderNoContentModel() {
        qs('#ng-content-model-summary').innerHTML = '<strong>No model selected</strong><span>Pick a model to load its brochure, media, videos, parts, issues, and competition links.</span>';
        qs('#ng-model-brochure-url').value = '';
        setText('stat-gallery', '—');
        setText('stat-videos', '—');
        setText('stat-parts', '—');
        setText('stat-issues', '—');
        [
            '#ng-gallery-list', '#ng-videos-list', '#ng-parts-list', '#ng-proscons-list', '#ng-issues-list', '#ng-competition-list'
        ].forEach(function (sel) {
            var el = qs(sel);
            if (el) el.innerHTML = '<p class="ng-team-empty">Select a model first.</p>';
        });
        closeContentForms();
    }

    async function loadSelectedModelContent() {
        if (!state.selectedModelId) {
            renderNoContentModel();
            return;
        }

        state.selectedModel = findContentModel(state.selectedModelId);
        localStorage.setItem('ngSelectedContentModel', state.selectedModelId);

        if (state.selectedModel) {
            var brandName = state.selectedModel.brands && state.selectedModel.brands.name ? state.selectedModel.brands.name + ' ' : '';
            qs('#ng-content-model-summary').innerHTML = '<strong>' + esc(brandName + state.selectedModel.name) + '</strong><span>/cars/' + esc((state.selectedModel.brands && state.selectedModel.brands.slug) || 'brand') + '/' + esc(state.selectedModel.slug || '') + '/</span>';
            qs('#ng-model-brochure-url').value = state.selectedModel.brochure_url || '';
        }

        var galleryQ = sb.from('vehicle_images').select('*').eq('model_id', state.selectedModelId).order('display_order', { ascending: true });
        var videosQ = sb.from('vehicle_videos').select('*').eq('model_id', state.selectedModelId).order('is_featured', { ascending: false }).order('display_order', { ascending: true });
        var partsQ = sb.from('vehicle_parts').select('*').eq('model_id', state.selectedModelId).order('is_common', { ascending: false }).order('display_order', { ascending: true });
        var prosQ = sb.from('vehicle_pros_cons').select('*').eq('model_id', state.selectedModelId).order('type', { ascending: true }).order('display_order', { ascending: true });
        var issuesQ = sb.from('vehicle_issues').select('*').eq('model_id', state.selectedModelId).order('severity', { ascending: false }).order('created_at', { ascending: false });
        var compQ = sb.from('vehicle_competition').select('id,display_order,competitor_model_id,competitor:models!competitor_model_id(id,name,slug,brands(name))').eq('model_id', state.selectedModelId).order('display_order', { ascending: true });

        var results = await Promise.all([galleryQ, videosQ, partsQ, prosQ, issuesQ, compQ]);
        var gallery = results[0].data || [];
        var videos = results[1].data || [];
        var parts = results[2].data || [];
        var prosCons = results[3].data || [];
        var issues = results[4].data || [];
        var competition = results[5].data || [];

        setText('stat-gallery', gallery.length);
        setText('stat-videos', videos.length);
        setText('stat-parts', parts.length);
        setText('stat-issues', issues.length);

        renderGallery(gallery);
        renderVideos(videos);
        renderParts(parts);
        renderProsCons(prosCons);
        renderIssues(issues);
        renderCompetition(competition);
        populateCompetitionOptions();
    }

    function renderGallery(items) {
        var rows = items.map(function (item) {
            return '<tr>' +
                '<td>' + (item.url ? '<img class="ng-team-thumb" src="' + esc(item.url) + '" alt="">' : '—') + '</td>' +
                '<td><a href="' + esc(item.url || '#') + '" target="_blank" rel="noopener">' + esc(item.alt || item.url || 'Open image') + '</a></td>' +
                '<td>' + esc(item.type || '—') + '</td>' +
                '<td>' + esc(item.display_order || 0) + '</td>' +
                '<td class="ng-team-actions">' +
                    btn('Edit', 'ng-btn--sm ng-btn--outline', 'data-edit-gallery=\'' + escAttrJSON(item) + '\'') +
                    btn('Delete', 'ng-btn--sm ng-btn--outline ng-btn--danger', 'data-del-gallery="' + esc(item.id) + '"') +
                '</td>' +
            '</tr>';
        });
        qs('#ng-gallery-list').innerHTML = rows.length ? tableHTML(['Preview', 'Image', 'Type', 'Order', ''], rows) : '<p class="ng-team-empty">No gallery images yet.</p>';
        qsa('[data-edit-gallery]').forEach(function (el) {
            el.addEventListener('click', function () { openGalleryForm(parseDataAttrJSON(el.dataset.editGallery)); });
        });
        qsa('[data-del-gallery]').forEach(function (el) {
            el.addEventListener('click', async function () {
                if (!confirm('Delete this image?')) return;
                await sb.from('vehicle_images').delete().eq('id', el.dataset.delGallery);
                await loadSelectedModelContent();
            });
        });
    }

    function renderVideos(items) {
        var rows = items.map(function (item) {
            return '<tr>' +
                '<td><strong>' + esc(item.title || 'Untitled') + '</strong></td>' +
                '<td>' + esc(item.video_type || '—') + '</td>' +
                '<td>' + esc(item.source_name || '—') + '</td>' +
                '<td>' + (item.is_featured ? 'Featured' : '—') + '</td>' +
                '<td>' + esc(item.display_order || 0) + '</td>' +
                '<td class="ng-team-actions">' +
                    btn('Edit', 'ng-btn--sm ng-btn--outline', 'data-edit-video=\'' + escAttrJSON(item) + '\'') +
                    btn('Delete', 'ng-btn--sm ng-btn--outline ng-btn--danger', 'data-del-video="' + esc(item.id) + '"') +
                '</td>' +
            '</tr>';
        });
        qs('#ng-videos-list').innerHTML = rows.length ? tableHTML(['Title', 'Type', 'Source', 'Featured', 'Order', ''], rows) : '<p class="ng-team-empty">No videos yet.</p>';
        qsa('[data-edit-video]').forEach(function (el) {
            el.addEventListener('click', function () { openVideoForm(parseDataAttrJSON(el.dataset.editVideo)); });
        });
        qsa('[data-del-video]').forEach(function (el) {
            el.addEventListener('click', async function () {
                if (!confirm('Delete this video?')) return;
                await sb.from('vehicle_videos').delete().eq('id', el.dataset.delVideo);
                await loadSelectedModelContent();
            });
        });
    }

    function renderParts(items) {
        var rows = items.map(function (item) {
            return '<tr>' +
                '<td><strong>' + esc(item.part_name || '—') + '</strong></td>' +
                '<td>' + esc(item.part_category || '—') + '</td>' +
                '<td>' + (item.price_npr ? 'NPR ' + Number(item.price_npr).toLocaleString() : '—') + '</td>' +
                '<td>' + (item.is_common ? 'Common' : '—') + '</td>' +
                '<td class="ng-team-actions">' +
                    btn('Edit', 'ng-btn--sm ng-btn--outline', 'data-edit-part=\'' + escAttrJSON(item) + '\'') +
                    btn('Delete', 'ng-btn--sm ng-btn--outline ng-btn--danger', 'data-del-part="' + esc(item.id) + '"') +
                '</td>' +
            '</tr>';
        });
        qs('#ng-parts-list').innerHTML = rows.length ? tableHTML(['Part', 'Category', 'Price', 'Flag', ''], rows) : '<p class="ng-team-empty">No parts yet.</p>';
        qsa('[data-edit-part]').forEach(function (el) {
            el.addEventListener('click', function () { openPartForm(parseDataAttrJSON(el.dataset.editPart)); });
        });
        qsa('[data-del-part]').forEach(function (el) {
            el.addEventListener('click', async function () {
                if (!confirm('Delete this part?')) return;
                await sb.from('vehicle_parts').delete().eq('id', el.dataset.delPart);
                await loadSelectedModelContent();
            });
        });
    }

    function renderProsCons(items) {
        var rows = items.map(function (item) {
            return '<tr>' +
                '<td><span class="ng-team-badge ng-team-badge--' + esc(item.type || 'pro') + '">' + esc((item.type || 'pro').toUpperCase()) + '</span></td>' +
                '<td>' + esc(item.content || '—') + '</td>' +
                '<td>' + esc(item.source_name || '—') + '</td>' +
                '<td>' + esc(item.display_order || 0) + '</td>' +
                '<td class="ng-team-actions">' +
                    btn('Edit', 'ng-btn--sm ng-btn--outline', 'data-edit-procon=\'' + escAttrJSON(item) + '\'') +
                    btn('Delete', 'ng-btn--sm ng-btn--outline ng-btn--danger', 'data-del-procon="' + esc(item.id) + '"') +
                '</td>' +
            '</tr>';
        });
        qs('#ng-proscons-list').innerHTML = rows.length ? tableHTML(['Type', 'Content', 'Source', 'Order', ''], rows) : '<p class="ng-team-empty">No pros or cons yet.</p>';
        qsa('[data-edit-procon]').forEach(function (el) {
            el.addEventListener('click', function () { openProConForm(parseDataAttrJSON(el.dataset.editProcon)); });
        });
        qsa('[data-del-procon]').forEach(function (el) {
            el.addEventListener('click', async function () {
                if (!confirm('Delete this note?')) return;
                await sb.from('vehicle_pros_cons').delete().eq('id', el.dataset.delProcon);
                await loadSelectedModelContent();
            });
        });
    }

    function renderIssues(items) {
        var rows = items.map(function (item) {
            var severity = Number(item.severity || 1);
            var label = severity >= 3 ? 'High' : (severity === 2 ? 'Medium' : 'Low');
            return '<tr>' +
                '<td><strong>' + esc(item.title || '—') + '</strong></td>' +
                '<td>' + esc(label) + '</td>' +
                '<td>' + esc(item.source_name || '—') + '</td>' +
                '<td class="ng-team-actions">' +
                    btn('Edit', 'ng-btn--sm ng-btn--outline', 'data-edit-issue=\'' + escAttrJSON(item) + '\'') +
                    btn('Delete', 'ng-btn--sm ng-btn--outline ng-btn--danger', 'data-del-issue="' + esc(item.id) + '"') +
                '</td>' +
            '</tr>';
        });
        qs('#ng-issues-list').innerHTML = rows.length ? tableHTML(['Title', 'Severity', 'Source', ''], rows) : '<p class="ng-team-empty">No issues yet.</p>';
        qsa('[data-edit-issue]').forEach(function (el) {
            el.addEventListener('click', function () { openIssueForm(parseDataAttrJSON(el.dataset.editIssue)); });
        });
        qsa('[data-del-issue]').forEach(function (el) {
            el.addEventListener('click', async function () {
                if (!confirm('Delete this issue?')) return;
                await sb.from('vehicle_issues').delete().eq('id', el.dataset.delIssue);
                await loadSelectedModelContent();
            });
        });
    }

    function renderCompetition(items) {
        var rows = items.map(function (item) {
            var competitor = item.competitor || {};
            var name = ((competitor.brands && competitor.brands.name) ? competitor.brands.name + ' ' : '') + (competitor.name || 'Unknown model');
            return '<tr>' +
                '<td><strong>' + esc(name) + '</strong></td>' +
                '<td><code>' + esc(competitor.slug || '—') + '</code></td>' +
                '<td>' + esc(item.display_order || 0) + '</td>' +
                '<td class="ng-team-actions">' +
                    btn('Edit', 'ng-btn--sm ng-btn--outline', 'data-edit-competition=\'' + escAttrJSON(item) + '\'') +
                    btn('Delete', 'ng-btn--sm ng-btn--outline ng-btn--danger', 'data-del-competition="' + esc(item.id) + '"') +
                '</td>' +
            '</tr>';
        });
        qs('#ng-competition-list').innerHTML = rows.length ? tableHTML(['Competitor', 'Slug', 'Order', ''], rows) : '<p class="ng-team-empty">No competitors yet.</p>';
        qsa('[data-edit-competition]').forEach(function (el) {
            el.addEventListener('click', function () { openCompetitionForm(parseDataAttrJSON(el.dataset.editCompetition)); });
        });
        qsa('[data-del-competition]').forEach(function (el) {
            el.addEventListener('click', async function () {
                if (!confirm('Delete this competitor?')) return;
                await sb.from('vehicle_competition').delete().eq('id', el.dataset.delCompetition);
                await loadSelectedModelContent();
            });
        });
    }

    function populateCompetitionOptions() {
        var options = state.contentModels
            .filter(function (m) { return m.id !== state.selectedModelId; })
            .map(function (m) {
                var brandName = m.brands && m.brands.name ? m.brands.name + ' ' : '';
                return '<option value="' + esc(m.id) + '">' + esc(brandName + m.name) + '</option>';
            }).join('');
        qs('#competition-model-id').innerHTML = '<option value="">— select competitor —</option>' + options;
    }

    async function loadAccessories() {
        var res = await sb.from('accessories').select('*').order('name');
        var rows = (res.data || []).map(function (a) {
            return '<tr>' +
                '<td><strong>' + esc(a.name) + '</strong></td>' +
                '<td>' + esc(a.category || '—') + '</td>' +
                '<td>' + (a.price_npr ? 'NPR ' + Number(a.price_npr).toLocaleString() : '—') + '</td>' +
                '<td class="ng-team-actions">' +
                    btn('Edit', 'ng-btn--sm ng-btn--outline', 'data-edit-acc=\'' + escAttrJSON(a) + '\'') +
                    btn('Delete', 'ng-btn--sm ng-btn--outline ng-btn--danger', 'data-del-acc="' + esc(a.id) + '"') +
                '</td></tr>';
        });
        qs('#ng-acc-list').innerHTML = rows.length ? tableHTML(['Name', 'Category', 'Price', ''], rows) : '<p class="ng-team-empty">No accessories yet.</p>';

        qsa('[data-edit-acc]').forEach(function (el) {
            el.addEventListener('click', function () { openAccForm(parseDataAttrJSON(el.dataset.editAcc)); });
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
        qs('#acc-edit-id').value = acc ? acc.id : '';
        qs('#acc-name').value = acc ? acc.name : '';
        qs('#acc-category').value = acc ? (acc.category || '') : '';
        qs('#acc-price').value = acc ? (acc.price_npr || '') : '';
        qs('#acc-desc').value = acc ? (acc.description || '') : '';
        qs('#acc-image').value = acc ? (acc.image_url || '') : '';
        qs('#acc-affiliate').value = acc ? (acc.affiliate_url || '') : '';
        qs('#ng-acc-form').hidden = false;
        qs('#ng-acc-form').scrollIntoView({ behavior: 'smooth' });
    }

    async function loadLeads(status) {
        var query = sb.from('leads').select('id,name,phone,email,type,status,created_at').order('created_at', { ascending: false }).limit(50);
        if (status) query = query.eq('status', status);
        var res = await query;
        qs('#ng-leads-list').innerHTML = leadsTableHTML(res.data || []);
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
        return tableHTML(['Name', 'Phone', 'Type', 'Status', 'Date'], rows);
    }

    async function loadShowrooms() {
        var res = await sb.from('showrooms').select('*').order('name');
        var rows = (res.data || []).map(function (s) {
            return '<tr>' +
                '<td><strong>' + esc(s.name) + '</strong></td>' +
                '<td>' + esc(s.city || '—') + '</td>' +
                '<td>' + esc((s.brand_slugs || []).join(', ') || '—') + '</td>' +
                '<td>' + esc(s.contact_phone || '—') + '</td>' +
                '<td class="ng-team-actions">' +
                    btn('Edit', 'ng-btn--sm ng-btn--outline', 'data-edit-showroom=\'' + escAttrJSON(s) + '\'') +
                    btn('Delete', 'ng-btn--sm ng-btn--outline ng-btn--danger', 'data-del-showroom="' + esc(s.id) + '"') +
                '</td></tr>';
        });
        qs('#ng-showrooms-list').innerHTML = rows.length ? tableHTML(['Name', 'City', 'Brands', 'Phone', ''], rows) : '<p class="ng-team-empty">No showrooms yet.</p>';

        qsa('[data-edit-showroom]').forEach(function (el) {
            el.addEventListener('click', function () { openShowroomForm(parseDataAttrJSON(el.dataset.editShowroom)); });
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
        qs('#showroom-edit-id').value = s ? s.id : '';
        qs('#showroom-name').value = s ? s.name : '';
        qs('#showroom-city').value = s ? (s.city || '') : '';
        qs('#showroom-phone').value = s ? (s.contact_phone || '') : '';
        qs('#showroom-email').value = s ? (s.contact_email || '') : '';
        qs('#showroom-address').value = s ? (s.address || '') : '';
        qs('#showroom-brands').value = s ? ((s.brand_slugs || []).join(',')) : '';
        qs('#showroom-maps').value = s ? (s.google_maps || '') : '';
        qs('#ng-showroom-form').hidden = false;
        qs('#ng-showroom-form').scrollIntoView({ behavior: 'smooth' });
    }

    async function loadGarages() {
        var res = await sb.from('upgrade_garages').select('*').order('name');
        var rows = (res.data || []).map(function (g) {
            return '<tr>' +
                '<td><strong>' + esc(g.name) + '</strong></td>' +
                '<td>' + esc(g.city || '—') + '</td>' +
                '<td>' + esc((g.specialties || []).join(', ') || '—') + '</td>' +
                '<td class="ng-team-actions">' +
                    btn('Edit', 'ng-btn--sm ng-btn--outline', 'data-edit-garage=\'' + escAttrJSON(g) + '\'') +
                    btn('Delete', 'ng-btn--sm ng-btn--outline ng-btn--danger', 'data-del-garage="' + esc(g.id) + '"') +
                '</td></tr>';
        });
        qs('#ng-garages-list').innerHTML = rows.length ? tableHTML(['Name', 'City', 'Specialties', ''], rows) : '<p class="ng-team-empty">No garages yet.</p>';

        qsa('[data-edit-garage]').forEach(function (el) {
            el.addEventListener('click', function () { openGarageForm(parseDataAttrJSON(el.dataset.editGarage)); });
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
        qs('#garage-edit-id').value = g ? g.id : '';
        qs('#garage-name').value = g ? g.name : '';
        qs('#garage-city').value = g ? (g.city || '') : '';
        qs('#garage-phone').value = g ? (g.contact_phone || '') : '';
        qs('#garage-email').value = g ? (g.contact_email || '') : '';
        qs('#garage-specs').value = g ? ((g.specialties || []).join(',')) : '';
        qs('#garage-address').value = g ? (g.address || '') : '';
        qs('#garage-maps').value = g ? (g.google_maps || '') : '';
        qs('#ng-garage-form').hidden = false;
        qs('#ng-garage-form').scrollIntoView({ behavior: 'smooth' });
    }

    function bindForms() {
        bindSimpleFormButtons();
        bindCoreForms();
        bindResearchContentForms();
    }

    function bindSimpleFormButtons() {
        bindClick('#btn-add-brand', function () { openBrandForm(null); });
        bindClick('#btn-cancel-brand', function () { qs('#ng-brand-form').hidden = true; });
        bindClick('#btn-add-model', function () { openModelForm(null); });
        bindClick('#btn-cancel-model', function () { qs('#ng-model-form').hidden = true; });
        bindClick('#btn-cancel-variant', function () { qs('#ng-variant-form').hidden = true; });
        bindClick('#btn-add-acc', function () { openAccForm(null); });
        bindClick('#btn-cancel-acc', function () { qs('#ng-acc-form').hidden = true; });
        bindClick('#btn-add-showroom', function () { openShowroomForm(null); });
        bindClick('#btn-cancel-showroom', function () { qs('#ng-showroom-form').hidden = true; });
        bindClick('#btn-add-garage', function () { openGarageForm(null); });
        bindClick('#btn-cancel-garage', function () { qs('#ng-garage-form').hidden = true; });

        var leadFilter = qs('#ng-lead-filter-status');
        if (leadFilter && leadFilter.dataset.bound !== '1') {
            leadFilter.dataset.bound = '1';
            leadFilter.addEventListener('change', function () {
                loadLeads(this.value || undefined);
            });
        }
    }

    function bindCoreForms() {
        bindSubmit('#ng-brand-form', async function () {
            var id = qs('#brand-edit-id').value;
            var data = { name: qs('#brand-name').value.trim(), slug: qs('#brand-slug').value.trim(), logo_url: qs('#brand-logo').value.trim() || null };
            if (id) await sb.from('brands').update(data).eq('id', id);
            else await sb.from('brands').insert(data);
            qs('#ng-brand-form').hidden = true;
            loadBrands();
        });

        bindSubmit('#ng-model-form', async function () {
            var id = qs('#model-edit-id').value;
            var data = { brand_id: qs('#model-brand-id').value, name: qs('#model-name').value.trim(), slug: qs('#model-slug').value.trim(), logo_url: qs('#model-logo').value.trim() || null };
            if (id) await sb.from('models').update(data).eq('id', id);
            else await sb.from('models').insert(data);
            qs('#ng-model-form').hidden = true;
            loadModels();
            loadResearchContent();
        });

        bindSubmit('#ng-variant-form', async function () {
            var id = qs('#variant-edit-id').value;
            var data = {
                model_id: qs('#variant-model-id').value,
                name: qs('#variant-name').value.trim(),
                slug: qs('#variant-slug').value.trim(),
                price_npr: qs('#variant-price').value || null,
                battery_kwh: qs('#variant-battery').value || null,
                range_km: qs('#variant-range').value || null,
                image_url: qs('#variant-image').value.trim() || null,
                is_available_nepal: qs('#variant-available').checked,
            };
            if (id) await sb.from('variants').update(data).eq('id', id);
            else await sb.from('variants').insert(data);
            qs('#ng-variant-form').hidden = true;
            loadModels();
        });

        bindSubmit('#ng-acc-form', async function () {
            var id = qs('#acc-edit-id').value;
            var data = {
                name: qs('#acc-name').value.trim(),
                category: qs('#acc-category').value.trim() || null,
                price_npr: qs('#acc-price').value || null,
                description: qs('#acc-desc').value.trim() || null,
                image_url: qs('#acc-image').value.trim() || null,
                affiliate_url: qs('#acc-affiliate').value.trim() || null,
            };
            if (id) await sb.from('accessories').update(data).eq('id', id);
            else await sb.from('accessories').insert(data);
            qs('#ng-acc-form').hidden = true;
            loadAccessories();
        });

        bindSubmit('#ng-showroom-form', async function () {
            var id = qs('#showroom-edit-id').value;
            var brands = qs('#showroom-brands').value.split(',').map(function (s) { return s.trim(); }).filter(Boolean);
            var data = {
                name: qs('#showroom-name').value.trim(),
                city: qs('#showroom-city').value.trim() || null,
                contact_phone: qs('#showroom-phone').value.trim() || null,
                contact_email: qs('#showroom-email').value.trim() || null,
                address: qs('#showroom-address').value.trim() || null,
                brand_slugs: brands,
                google_maps: qs('#showroom-maps').value.trim() || null,
            };
            if (id) await sb.from('showrooms').update(data).eq('id', id);
            else await sb.from('showrooms').insert(data);
            qs('#ng-showroom-form').hidden = true;
            loadShowrooms();
        });

        bindSubmit('#ng-garage-form', async function () {
            var id = qs('#garage-edit-id').value;
            var specs = qs('#garage-specs').value.split(',').map(function (s) { return s.trim(); }).filter(Boolean);
            var data = {
                name: qs('#garage-name').value.trim(),
                city: qs('#garage-city').value.trim() || null,
                contact_phone: qs('#garage-phone').value.trim() || null,
                contact_email: qs('#garage-email').value.trim() || null,
                specialties: specs,
                address: qs('#garage-address').value.trim() || null,
                google_maps: qs('#garage-maps').value.trim() || null,
            };
            if (id) await sb.from('upgrade_garages').update(data).eq('id', id);
            else await sb.from('upgrade_garages').insert(data);
            qs('#ng-garage-form').hidden = true;
            loadGarages();
        });
    }

    function bindResearchContentForms() {
        var modelSelect = qs('#ng-content-model-select');
        if (modelSelect && modelSelect.dataset.bound !== '1') {
            modelSelect.dataset.bound = '1';
            modelSelect.addEventListener('change', async function () {
                state.selectedModelId = this.value || '';
                state.selectedModel = findContentModel(state.selectedModelId);
                await loadSelectedModelContent();
            });
        }

        bindClick('#btn-add-gallery-image', function () { requireContentModel(openGalleryForm); });
        bindClick('#btn-cancel-gallery-image', function () { qs('#ng-gallery-form').hidden = true; });
        bindClick('#btn-add-video', function () { requireContentModel(openVideoForm); });
        bindClick('#btn-cancel-video', function () { qs('#ng-video-form').hidden = true; });
        bindClick('#btn-add-part', function () { requireContentModel(openPartForm); });
        bindClick('#btn-cancel-part', function () { qs('#ng-part-form').hidden = true; });
        bindClick('#btn-add-procon', function () { requireContentModel(openProConForm); });
        bindClick('#btn-cancel-procon', function () { qs('#ng-procon-form').hidden = true; });
        bindClick('#btn-add-issue', function () { requireContentModel(openIssueForm); });
        bindClick('#btn-cancel-issue', function () { qs('#ng-issue-form').hidden = true; });
        bindClick('#btn-add-competition', function () { requireContentModel(openCompetitionForm); });
        bindClick('#btn-cancel-competition', function () { qs('#ng-competition-form').hidden = true; });

        bindSubmit('#ng-brochure-form', async function () {
            if (!ensureContentModel()) return;
            var url = qs('#ng-model-brochure-url').value.trim() || null;
            await sb.from('models').update({ brochure_url: url }).eq('id', state.selectedModelId);
            var model = findContentModel(state.selectedModelId);
            if (model) model.brochure_url = url;
            showContentNotice('Brochure saved.');
        });

        bindSubmit('#ng-gallery-form', async function () {
            if (!ensureContentModel()) return;
            var id = qs('#gallery-edit-id').value;
            var data = {
                model_id: state.selectedModelId,
                url: qs('#gallery-url').value.trim(),
                alt: qs('#gallery-alt').value.trim() || null,
                type: qs('#gallery-type').value.trim() || null,
                display_order: toNumberOrZero(qs('#gallery-order').value),
            };
            await upsertSupabase('vehicle_images', id, data);
            qs('#ng-gallery-form').hidden = true;
            await loadSelectedModelContent();
            showContentNotice('Gallery updated.');
        });

        bindSubmit('#ng-video-form', async function () {
            if (!ensureContentModel()) return;
            var id = qs('#video-edit-id').value;
            var url = qs('#video-url').value.trim();
            var data = {
                model_id: state.selectedModelId,
                title: qs('#video-title').value.trim(),
                youtube_url: url,
                youtube_video_id: extractYouTubeId(url) || null,
                thumbnail_url: qs('#video-thumb').value.trim() || null,
                video_type: qs('#video-type').value.trim() || null,
                source_name: qs('#video-source').value.trim() || null,
                display_order: toNumberOrZero(qs('#video-order').value),
                is_featured: qs('#video-featured').checked,
                is_verified: true,
            };
            await upsertSupabase('vehicle_videos', id, data);
            qs('#ng-video-form').hidden = true;
            await loadSelectedModelContent();
            showContentNotice('Video saved.');
        });

        bindSubmit('#ng-part-form', async function () {
            if (!ensureContentModel()) return;
            var id = qs('#part-edit-id').value;
            var data = {
                model_id: state.selectedModelId,
                part_name: qs('#part-name').value.trim(),
                part_category: qs('#part-category').value.trim() || null,
                price_npr: qs('#part-price').value || null,
                image_url: qs('#part-image').value.trim() || null,
                notes: qs('#part-notes').value.trim() || null,
                source_url: qs('#part-source-url').value.trim() || null,
                display_order: toNumberOrZero(qs('#part-order').value),
                is_common: qs('#part-common').checked,
                is_verified: true,
            };
            await upsertSupabase('vehicle_parts', id, data);
            qs('#ng-part-form').hidden = true;
            await loadSelectedModelContent();
            showContentNotice('Part saved.');
        });

        bindSubmit('#ng-procon-form', async function () {
            if (!ensureContentModel()) return;
            var id = qs('#procon-edit-id').value;
            var data = {
                model_id: state.selectedModelId,
                type: qs('#procon-type').value,
                content: qs('#procon-content').value.trim(),
                source_name: qs('#procon-source-name').value.trim() || null,
                source_url: qs('#procon-source-url').value.trim() || null,
                display_order: toNumberOrZero(qs('#procon-order').value),
                is_verified: true,
            };
            await upsertSupabase('vehicle_pros_cons', id, data);
            qs('#ng-procon-form').hidden = true;
            await loadSelectedModelContent();
            showContentNotice('Pros/cons note saved.');
        });

        bindSubmit('#ng-issue-form', async function () {
            if (!ensureContentModel()) return;
            var id = qs('#issue-edit-id').value;
            var data = {
                model_id: state.selectedModelId,
                title: qs('#issue-title').value.trim(),
                description: qs('#issue-description').value.trim(),
                severity: toNumberOrZero(qs('#issue-severity').value) || 1,
                source_name: qs('#issue-source-name').value.trim() || null,
                source_url: qs('#issue-source-url').value.trim() || null,
                is_verified: true,
            };
            await upsertSupabase('vehicle_issues', id, data);
            qs('#ng-issue-form').hidden = true;
            await loadSelectedModelContent();
            showContentNotice('Issue saved.');
        });

        bindSubmit('#ng-competition-form', async function () {
            if (!ensureContentModel()) return;
            var id = qs('#competition-edit-id').value;
            var data = {
                model_id: state.selectedModelId,
                competitor_model_id: qs('#competition-model-id').value,
                display_order: toNumberOrZero(qs('#competition-order').value),
            };
            await upsertSupabase('vehicle_competition', id, data);
            qs('#ng-competition-form').hidden = true;
            await loadSelectedModelContent();
            showContentNotice('Competitor saved.');
        });
    }

    async function upsertSupabase(table, id, data) {
        var res;
        if (id) res = await sb.from(table).update(data).eq('id', id);
        else res = await sb.from(table).insert(data);
        if (res.error) throw res.error;
        return res;
    }

    function requireContentModel(fn) {
        if (!ensureContentModel()) return;
        fn(null);
    }

    function ensureContentModel() {
        if (state.selectedModelId) return true;
        showContentNotice('Select a vehicle model first.', true);
        return false;
    }

    function openGalleryForm(item) {
        qs('#gallery-edit-id').value = item ? item.id : '';
        qs('#gallery-url').value = item ? (item.url || '') : '';
        qs('#gallery-alt').value = item ? (item.alt || '') : '';
        qs('#gallery-type').value = item ? (item.type || '') : '';
        qs('#gallery-order').value = item ? (item.display_order || 0) : 0;
        qs('#ng-gallery-form').hidden = false;
        qs('#ng-gallery-form').scrollIntoView({ behavior: 'smooth' });
    }

    function openVideoForm(item) {
        qs('#video-edit-id').value = item ? item.id : '';
        qs('#video-title').value = item ? (item.title || '') : '';
        qs('#video-url').value = item ? (item.youtube_url || '') : '';
        qs('#video-thumb').value = item ? (item.thumbnail_url || '') : '';
        qs('#video-type').value = item ? (item.video_type || '') : '';
        qs('#video-source').value = item ? (item.source_name || '') : '';
        qs('#video-order').value = item ? (item.display_order || 0) : 0;
        qs('#video-featured').checked = !!(item && item.is_featured);
        qs('#ng-video-form').hidden = false;
        qs('#ng-video-form').scrollIntoView({ behavior: 'smooth' });
    }

    function openPartForm(item) {
        qs('#part-edit-id').value = item ? item.id : '';
        qs('#part-name').value = item ? (item.part_name || '') : '';
        qs('#part-category').value = item ? (item.part_category || '') : '';
        qs('#part-price').value = item ? (item.price_npr || '') : '';
        qs('#part-image').value = item ? (item.image_url || '') : '';
        qs('#part-notes').value = item ? (item.notes || '') : '';
        qs('#part-source-url').value = item ? (item.source_url || '') : '';
        qs('#part-order').value = item ? (item.display_order || 0) : 0;
        qs('#part-common').checked = item ? !!item.is_common : true;
        qs('#ng-part-form').hidden = false;
        qs('#ng-part-form').scrollIntoView({ behavior: 'smooth' });
    }

    function openProConForm(item) {
        qs('#procon-edit-id').value = item ? item.id : '';
        qs('#procon-type').value = item ? (item.type || 'pro') : 'pro';
        qs('#procon-content').value = item ? (item.content || '') : '';
        qs('#procon-source-name').value = item ? (item.source_name || '') : '';
        qs('#procon-source-url').value = item ? (item.source_url || '') : '';
        qs('#procon-order').value = item ? (item.display_order || 0) : 0;
        qs('#ng-procon-form').hidden = false;
        qs('#ng-procon-form').scrollIntoView({ behavior: 'smooth' });
    }

    function openIssueForm(item) {
        qs('#issue-edit-id').value = item ? item.id : '';
        qs('#issue-title').value = item ? (item.title || '') : '';
        qs('#issue-description').value = item ? (item.description || '') : '';
        qs('#issue-severity').value = item ? (item.severity || 1) : 1;
        qs('#issue-source-name').value = item ? (item.source_name || '') : '';
        qs('#issue-source-url').value = item ? (item.source_url || '') : '';
        qs('#ng-issue-form').hidden = false;
        qs('#ng-issue-form').scrollIntoView({ behavior: 'smooth' });
    }

    function openCompetitionForm(item) {
        populateCompetitionOptions();
        qs('#competition-edit-id').value = item ? item.id : '';
        qs('#competition-model-id').value = item ? (item.competitor_model_id || '') : '';
        qs('#competition-order').value = item ? (item.display_order || 0) : 0;
        qs('#ng-competition-form').hidden = false;
        qs('#ng-competition-form').scrollIntoView({ behavior: 'smooth' });
    }

    function closeContentForms() {
        ['#ng-gallery-form', '#ng-video-form', '#ng-part-form', '#ng-procon-form', '#ng-issue-form', '#ng-competition-form'].forEach(function (sel) {
            var el = qs(sel);
            if (el) el.hidden = true;
        });
    }

    function showContentNotice(message, isError) {
        var el = qs('#ng-content-status');
        if (!el) return;
        el.textContent = message;
        el.hidden = !message;
        el.classList.toggle('is-error', !!isError);
    }

    function clearContentNotice() {
        showContentNotice('', false);
    }

    function bindClick(sel, fn) {
        var el = qs(sel);
        if (!el || el.dataset.bound === '1') return;
        el.dataset.bound = '1';
        el.addEventListener('click', fn);
    }

    function bindSubmit(sel, fn) {
        var form = qs(sel);
        if (!form || form.dataset.bound === '1') return;
        form.dataset.bound = '1';
        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            try {
                await fn();
            } catch (error) {
                console.error(error);
                showContentNotice(error.message || 'Something went wrong.', true);
            }
        });
    }

    function extractYouTubeId(url) {
        var match = String(url || '').match(/(?:youtube(?:-nocookie)?\.com\/(?:[^/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([A-Za-z0-9_-]{11})/);
        return match ? match[1] : '';
    }

    function toNumberOrZero(value) {
        var num = Number(value);
        return Number.isFinite(num) ? num : 0;
    }

    function parseDataAttrJSON(value) {
        try {
            return JSON.parse(value);
        } catch (e) {
            return null;
        }
    }

    function qs(sel) { return document.querySelector(sel); }
    function qsa(sel) { return document.querySelectorAll(sel); }
    function setText(id, val) { var el = document.getElementById(id); if (el) el.textContent = val; }
    function esc(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
    function escAttrJSON(obj) { return esc(JSON.stringify(obj)); }
    function btn(label, cls, attrs) { return '<button type="button" class="ng-btn ' + cls + '" ' + (attrs || '') + '>' + label + '</button>'; }
    function tableHTML(headers, rows) {
        var ths = headers.map(function (h) { return '<th>' + h + '</th>'; }).join('');
        return '<table class="ng-team-table"><thead><tr>' + ths + '</tr></thead><tbody>' + rows.join('') + '</tbody></table>';
    }
})();
