<?php get_header(); ?>

<!-- ── Auth gate ──────────────────────────────────────────────────────────── -->
<div id="ng-team-gate" class="ng-team-gate">
    <div class="ng-team-gate__card">
        <div class="ng-logo"><span class="ng-logo__mark">NG</span><span class="ng-logo__text">Nepali<strong>Garage</strong></span></div>
        <h1>Team Dashboard</h1>
        <p>Sign in with your team account to manage vehicles, media, articles, and events.</p>
        <form id="ng-team-login-form" class="ng-team-gate__form" novalidate>
            <div class="ng-field">
                <label for="ng-tl-email">Email</label>
                <input id="ng-tl-email" type="email" autocomplete="email" required>
            </div>
            <div class="ng-field">
                <label for="ng-tl-password">Password</label>
                <input id="ng-tl-password" type="password" autocomplete="current-password" required>
            </div>
            <p id="ng-team-login-error" class="ng-team-gate__error" hidden></p>
            <button type="submit" class="ng-btn ng-btn--primary ng-btn--full" id="ng-tl-submit">Sign In</button>
        </form>
    </div>
</div>

<!-- ── Access denied ─────────────────────────────────────────────────────── -->
<div id="ng-team-denied" class="ng-team-gate" hidden>
    <div class="ng-team-gate__card">
        <h2>Access Restricted</h2>
        <p>Your account does not have team access. Contact an administrator to request access.</p>
        <button id="ng-team-signout-denied" class="ng-btn ng-btn--outline">Sign Out</button>
    </div>
</div>

<!-- ── Team dashboard ────────────────────────────────────────────────────── -->
<div id="ng-team" class="ng-team" hidden>
    <aside class="ng-team__sidebar">
        <div class="ng-team__brand">
            <span class="ng-logo__mark">NG</span>
            <span>Team</span>
        </div>
        <nav class="ng-team__nav">
            <a class="ng-team__nav-item is-active" data-tab="overview">
                <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="1.5"/><rect x="14" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="1.5"/><rect x="3" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="1.5"/><rect x="14" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="1.5"/></svg>
                Overview
            </a>
            <a class="ng-team__nav-item" data-tab="brands">
                <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5"/><path d="M12 8v4l3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                Brands
            </a>
            <a class="ng-team__nav-item" data-tab="vehicles">
                <svg viewBox="0 0 24 24" fill="none"><path d="M3 17V11l4-6h10l4 6v6" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><circle cx="7.5" cy="17.5" r="2" stroke="currentColor" stroke-width="1.5"/><circle cx="16.5" cy="17.5" r="2" stroke="currentColor" stroke-width="1.5"/></svg>
                Vehicles
            </a>
            <a class="ng-team__nav-item" data-tab="research-content">
                <svg viewBox="0 0 24 24" fill="none"><path d="M4 5.5A2.5 2.5 0 016.5 3h11A2.5 2.5 0 0120 5.5v13a2.5 2.5 0 01-2.5 2.5h-11A2.5 2.5 0 014 18.5v-13z" stroke="currentColor" stroke-width="1.5"/><path d="M8 8h8M8 12h8M8 16h5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                Product Content
            </a>
            <a class="ng-team__nav-item" data-tab="accessories">
                <svg viewBox="0 0 24 24" fill="none"><path d="M12 2l3 7h7l-5.5 4 2 7L12 16l-6.5 4 2-7L2 9h7z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg>
                Accessories
            </a>
            <a class="ng-team__nav-item" data-tab="leads">
                <svg viewBox="0 0 24 24" fill="none"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="1.5"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.5"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" stroke="currentColor" stroke-width="1.5"/></svg>
                Leads
            </a>
            <a class="ng-team__nav-item" data-tab="showrooms">
                <svg viewBox="0 0 24 24" fill="none"><path d="M3 9.5L12 3l9 6.5V21H3V9.5z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><rect x="9" y="14" width="6" height="7" rx="1" stroke="currentColor" stroke-width="1.5"/></svg>
                Showrooms
            </a>
            <a class="ng-team__nav-item" data-tab="garages">
                <svg viewBox="0 0 24 24" fill="none"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                Garages
            </a>
            <a class="ng-team__nav-item" data-tab="content">
                <svg viewBox="0 0 24 24" fill="none"><path d="M14 3H6a2 2 0 00-2 2v14a2 2 0 002 2h12a2 2 0 002-2V9l-6-6z" stroke="currentColor" stroke-width="1.5"/><path d="M14 3v6h6M9 13h6M9 17h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                Editorial
            </a>
        </nav>
        <div class="ng-team__sidebar-footer">
            <span id="ng-team-user-label" class="ng-team__user-label"></span>
            <button id="ng-team-signout" class="ng-btn ng-btn--sm ng-btn--outline">Sign Out</button>
        </div>
    </aside>

    <main class="ng-team__main">
        <section class="ng-team__panel is-active" data-panel="overview">
            <h1>Overview</h1>
            <div class="ng-team-stats" id="ng-team-stats">
                <div class="ng-team-stat"><span class="ng-team-stat__val" id="stat-brands">—</span><span class="ng-team-stat__lbl">Brands</span></div>
                <div class="ng-team-stat"><span class="ng-team-stat__val" id="stat-models">—</span><span class="ng-team-stat__lbl">Models</span></div>
                <div class="ng-team-stat"><span class="ng-team-stat__val" id="stat-leads">—</span><span class="ng-team-stat__lbl">New Leads</span></div>
                <div class="ng-team-stat"><span class="ng-team-stat__val" id="stat-showrooms">—</span><span class="ng-team-stat__lbl">Showrooms</span></div>
            </div>
            <h2>Recent Leads</h2>
            <div id="ng-overview-leads" class="ng-team-table-wrap"><p class="ng-team-loading">Loading…</p></div>
        </section>

        <section class="ng-team__panel" data-panel="brands">
            <div class="ng-team__panel-header">
                <h1>Brands</h1>
                <button class="ng-btn ng-btn--primary ng-btn--sm" id="btn-add-brand">+ Add Brand</button>
            </div>
            <form id="ng-brand-form" class="ng-team-form" hidden>
                <input type="hidden" id="brand-edit-id">
                <div class="ng-team-form__grid">
                    <div class="ng-field"><label>Name</label><input id="brand-name" type="text" required></div>
                    <div class="ng-field"><label>Slug</label><input id="brand-slug" type="text" placeholder="byd" required></div>
                    <div class="ng-field ng-field--wide"><label>Logo URL</label><input id="brand-logo" type="url" placeholder="https://…"></div>
                </div>
                <div class="ng-team-form__actions">
                    <button type="submit" class="ng-btn ng-btn--primary">Save Brand</button>
                    <button type="button" class="ng-btn ng-btn--outline" id="btn-cancel-brand">Cancel</button>
                </div>
            </form>
            <div id="ng-brands-list" class="ng-team-table-wrap"><p class="ng-team-loading">Loading…</p></div>
        </section>

        <section class="ng-team__panel" data-panel="vehicles">
            <div class="ng-team__panel-header">
                <h1>Vehicles</h1>
                <button class="ng-btn ng-btn--primary ng-btn--sm" id="btn-add-model">+ Add Model</button>
            </div>
            <form id="ng-model-form" class="ng-team-form" hidden>
                <input type="hidden" id="model-edit-id">
                <div class="ng-team-form__grid">
                    <div class="ng-field"><label>Brand</label>
                        <select id="model-brand-id" required><option value="">— select —</option></select>
                    </div>
                    <div class="ng-field"><label>Model Name</label><input id="model-name" type="text" required></div>
                    <div class="ng-field"><label>Slug</label><input id="model-slug" type="text" placeholder="atto-3" required></div>
                    <div class="ng-field"><label>Logo URL</label><input id="model-logo" type="url"></div>
                </div>
                <div class="ng-team-form__actions">
                    <button type="submit" class="ng-btn ng-btn--primary">Save Model</button>
                    <button type="button" class="ng-btn ng-btn--outline" id="btn-cancel-model">Cancel</button>
                </div>
            </form>
            <form id="ng-variant-form" class="ng-team-form" hidden>
                <input type="hidden" id="variant-edit-id">
                <h3 id="variant-form-title">Add Variant</h3>
                <div class="ng-team-form__grid">
                    <div class="ng-field"><label>Model</label>
                        <select id="variant-model-id" required><option value="">— select —</option></select>
                    </div>
                    <div class="ng-field"><label>Variant Name</label><input id="variant-name" type="text" required></div>
                    <div class="ng-field"><label>Slug</label><input id="variant-slug" type="text" required></div>
                    <div class="ng-field"><label>Price (NPR)</label><input id="variant-price" type="number" min="0"></div>
                    <div class="ng-field"><label>Battery kWh</label><input id="variant-battery" type="number" step="0.1" placeholder="EV only"></div>
                    <div class="ng-field"><label>Range km</label><input id="variant-range" type="number" placeholder="EV only"></div>
                    <div class="ng-field"><label>Image URL</label><input id="variant-image" type="url"></div>
                    <div class="ng-field"><label><input id="variant-available" type="checkbox" checked> Available in Nepal</label></div>
                </div>
                <div class="ng-team-form__actions">
                    <button type="submit" class="ng-btn ng-btn--primary">Save Variant</button>
                    <button type="button" class="ng-btn ng-btn--outline" id="btn-cancel-variant">Cancel</button>
                </div>
            </form>
            <div id="ng-models-list" class="ng-team-table-wrap"><p class="ng-team-loading">Loading…</p></div>
        </section>

        <section class="ng-team__panel" data-panel="research-content">
            <div class="ng-team__panel-header ng-team__panel-header--stack">
                <div>
                    <h1>Product Content Manager</h1>
                    <p class="ng-team__lede">Manage the content that makes each product page feel complete: brochure, gallery, videos, parts, pros, issues, and competition.</p>
                </div>
            </div>

            <div class="ng-team-workspace">
                <div class="ng-team-workspace__header">
                    <div class="ng-field ng-team-workspace__picker">
                        <label for="ng-content-model-select">Choose vehicle model</label>
                        <select id="ng-content-model-select">
                            <option value="">— select a model —</option>
                        </select>
                    </div>
                    <div id="ng-content-model-summary" class="ng-team-workspace__summary">
                        <strong>No model selected</strong>
                        <span>Pick a model to load its brochure, media, videos, parts, issues, and competition links.</span>
                    </div>
                </div>

                <div id="ng-content-status" class="ng-team-inline-notice" hidden></div>

                <div class="ng-team-stats ng-team-stats--compact">
                    <div class="ng-team-stat"><span class="ng-team-stat__val" id="stat-gallery">—</span><span class="ng-team-stat__lbl">Gallery Images</span></div>
                    <div class="ng-team-stat"><span class="ng-team-stat__val" id="stat-videos">—</span><span class="ng-team-stat__lbl">Videos</span></div>
                    <div class="ng-team-stat"><span class="ng-team-stat__val" id="stat-parts">—</span><span class="ng-team-stat__lbl">Common Parts</span></div>
                    <div class="ng-team-stat"><span class="ng-team-stat__val" id="stat-issues">—</span><span class="ng-team-stat__lbl">Known Issues</span></div>
                </div>

                <div class="ng-team-content-grid">
                    <section class="ng-team-block">
                        <div class="ng-team-block__head">
                            <div>
                                <h2>Brochure</h2>
                                <p>Set the official brochure PDF URL shown on the product page.</p>
                            </div>
                        </div>
                        <form id="ng-brochure-form" class="ng-team-form ng-team-form--nested">
                            <div class="ng-team-form__grid">
                                <div class="ng-field ng-field--wide">
                                    <label for="ng-model-brochure-url">Brochure URL</label>
                                    <input id="ng-model-brochure-url" type="url" placeholder="https://example.com/brochure.pdf">
                                </div>
                            </div>
                            <div class="ng-team-form__actions">
                                <button type="submit" class="ng-btn ng-btn--primary">Save Brochure</button>
                            </div>
                        </form>
                    </section>

                    <section class="ng-team-block">
                        <div class="ng-team-block__head">
                            <div>
                                <h2>Gallery Images</h2>
                                <p>Curate the product gallery with display order and alt text.</p>
                            </div>
                            <button class="ng-btn ng-btn--primary ng-btn--sm" id="btn-add-gallery-image">+ Add Image</button>
                        </div>
                        <form id="ng-gallery-form" class="ng-team-form ng-team-form--nested" hidden>
                            <input type="hidden" id="gallery-edit-id">
                            <div class="ng-team-form__grid">
                                <div class="ng-field ng-field--wide"><label>Image URL</label><input id="gallery-url" type="url" required></div>
                                <div class="ng-field"><label>Alt Text</label><input id="gallery-alt" type="text"></div>
                                <div class="ng-field"><label>Type</label><input id="gallery-type" type="text" placeholder="exterior"></div>
                                <div class="ng-field"><label>Display Order</label><input id="gallery-order" type="number" min="0" value="0"></div>
                            </div>
                            <div class="ng-team-form__actions">
                                <button type="submit" class="ng-btn ng-btn--primary">Save Image</button>
                                <button type="button" class="ng-btn ng-btn--outline" id="btn-cancel-gallery-image">Cancel</button>
                            </div>
                        </form>
                        <div id="ng-gallery-list" class="ng-team-table-wrap"><p class="ng-team-empty">Select a model first.</p></div>
                    </section>

                    <section class="ng-team-block">
                        <div class="ng-team-block__head">
                            <div>
                                <h2>Review Videos</h2>
                                <p>Add playable YouTube videos with source labels and featured ordering.</p>
                            </div>
                            <button class="ng-btn ng-btn--primary ng-btn--sm" id="btn-add-video">+ Add Video</button>
                        </div>
                        <form id="ng-video-form" class="ng-team-form ng-team-form--nested" hidden>
                            <input type="hidden" id="video-edit-id">
                            <div class="ng-team-form__grid">
                                <div class="ng-field"><label>Title</label><input id="video-title" type="text" required></div>
                                <div class="ng-field ng-field--wide"><label>YouTube URL</label><input id="video-url" type="url" required></div>
                                <div class="ng-field"><label>Thumbnail URL</label><input id="video-thumb" type="url"></div>
                                <div class="ng-field"><label>Video Type</label><input id="video-type" type="text" placeholder="review"></div>
                                <div class="ng-field"><label>Source Name</label><input id="video-source" type="text" placeholder="YouTube channel"></div>
                                <div class="ng-field"><label>Display Order</label><input id="video-order" type="number" min="0" value="0"></div>
                                <div class="ng-field"><label><input id="video-featured" type="checkbox"> Featured video</label></div>
                            </div>
                            <div class="ng-team-form__actions">
                                <button type="submit" class="ng-btn ng-btn--primary">Save Video</button>
                                <button type="button" class="ng-btn ng-btn--outline" id="btn-cancel-video">Cancel</button>
                            </div>
                        </form>
                        <div id="ng-videos-list" class="ng-team-table-wrap"><p class="ng-team-empty">Select a model first.</p></div>
                    </section>

                    <section class="ng-team-block">
                        <div class="ng-team-block__head">
                            <div>
                                <h2>Common Parts</h2>
                                <p>Maintain the quick-reference parts section for ownership and maintenance.</p>
                            </div>
                            <button class="ng-btn ng-btn--primary ng-btn--sm" id="btn-add-part">+ Add Part</button>
                        </div>
                        <form id="ng-part-form" class="ng-team-form ng-team-form--nested" hidden>
                            <input type="hidden" id="part-edit-id">
                            <div class="ng-team-form__grid">
                                <div class="ng-field"><label>Part Name</label><input id="part-name" type="text" required></div>
                                <div class="ng-field"><label>Category</label><input id="part-category" type="text" placeholder="service"></div>
                                <div class="ng-field"><label>Price (NPR)</label><input id="part-price" type="number" min="0"></div>
                                <div class="ng-field"><label>Display Order</label><input id="part-order" type="number" min="0" value="0"></div>
                                <div class="ng-field ng-field--wide"><label>Image URL</label><input id="part-image" type="url"></div>
                                <div class="ng-field ng-field--wide"><label>Notes</label><textarea id="part-notes" rows="2"></textarea></div>
                                <div class="ng-field ng-field--wide"><label>Source URL</label><input id="part-source-url" type="url"></div>
                                <div class="ng-field"><label><input id="part-common" type="checkbox" checked> Mark as common part</label></div>
                            </div>
                            <div class="ng-team-form__actions">
                                <button type="submit" class="ng-btn ng-btn--primary">Save Part</button>
                                <button type="button" class="ng-btn ng-btn--outline" id="btn-cancel-part">Cancel</button>
                            </div>
                        </form>
                        <div id="ng-parts-list" class="ng-team-table-wrap"><p class="ng-team-empty">Select a model first.</p></div>
                    </section>

                    <section class="ng-team-block">
                        <div class="ng-team-block__head">
                            <div>
                                <h2>Pros &amp; Cons</h2>
                                <p>Add concise buying notes for what stands out and what to watch.</p>
                            </div>
                            <button class="ng-btn ng-btn--primary ng-btn--sm" id="btn-add-procon">+ Add Note</button>
                        </div>
                        <form id="ng-procon-form" class="ng-team-form ng-team-form--nested" hidden>
                            <input type="hidden" id="procon-edit-id">
                            <div class="ng-team-form__grid">
                                <div class="ng-field"><label>Type</label>
                                    <select id="procon-type">
                                        <option value="pro">Pro</option>
                                        <option value="con">Con</option>
                                    </select>
                                </div>
                                <div class="ng-field"><label>Display Order</label><input id="procon-order" type="number" min="0" value="0"></div>
                                <div class="ng-field ng-field--wide"><label>Content</label><textarea id="procon-content" rows="2" required></textarea></div>
                                <div class="ng-field"><label>Source Name</label><input id="procon-source-name" type="text"></div>
                                <div class="ng-field"><label>Source URL</label><input id="procon-source-url" type="url"></div>
                            </div>
                            <div class="ng-team-form__actions">
                                <button type="submit" class="ng-btn ng-btn--primary">Save Note</button>
                                <button type="button" class="ng-btn ng-btn--outline" id="btn-cancel-procon">Cancel</button>
                            </div>
                        </form>
                        <div id="ng-proscons-list" class="ng-team-table-wrap"><p class="ng-team-empty">Select a model first.</p></div>
                    </section>

                    <section class="ng-team-block">
                        <div class="ng-team-block__head">
                            <div>
                                <h2>Known Issues</h2>
                                <p>Track ownership caveats with source links and severity notes.</p>
                            </div>
                            <button class="ng-btn ng-btn--primary ng-btn--sm" id="btn-add-issue">+ Add Issue</button>
                        </div>
                        <form id="ng-issue-form" class="ng-team-form ng-team-form--nested" hidden>
                            <input type="hidden" id="issue-edit-id">
                            <div class="ng-team-form__grid">
                                <div class="ng-field"><label>Title</label><input id="issue-title" type="text" required></div>
                                <div class="ng-field"><label>Severity</label>
                                    <select id="issue-severity">
                                        <option value="1">Low</option>
                                        <option value="2">Medium</option>
                                        <option value="3">High</option>
                                    </select>
                                </div>
                                <div class="ng-field ng-field--wide"><label>Description</label><textarea id="issue-description" rows="3" required></textarea></div>
                                <div class="ng-field"><label>Source Name</label><input id="issue-source-name" type="text"></div>
                                <div class="ng-field"><label>Source URL</label><input id="issue-source-url" type="url"></div>
                            </div>
                            <div class="ng-team-form__actions">
                                <button type="submit" class="ng-btn ng-btn--primary">Save Issue</button>
                                <button type="button" class="ng-btn ng-btn--outline" id="btn-cancel-issue">Cancel</button>
                            </div>
                        </form>
                        <div id="ng-issues-list" class="ng-team-table-wrap"><p class="ng-team-empty">Select a model first.</p></div>
                    </section>

                    <section class="ng-team-block">
                        <div class="ng-team-block__head">
                            <div>
                                <h2>Competition</h2>
                                <p>Choose the most relevant rivals shown on the product page.</p>
                            </div>
                            <button class="ng-btn ng-btn--primary ng-btn--sm" id="btn-add-competition">+ Add Competitor</button>
                        </div>
                        <form id="ng-competition-form" class="ng-team-form ng-team-form--nested" hidden>
                            <input type="hidden" id="competition-edit-id">
                            <div class="ng-team-form__grid">
                                <div class="ng-field ng-field--wide"><label>Competitor Model</label>
                                    <select id="competition-model-id"><option value="">— select competitor —</option></select>
                                </div>
                                <div class="ng-field"><label>Display Order</label><input id="competition-order" type="number" min="0" value="0"></div>
                            </div>
                            <div class="ng-team-form__actions">
                                <button type="submit" class="ng-btn ng-btn--primary">Save Competitor</button>
                                <button type="button" class="ng-btn ng-btn--outline" id="btn-cancel-competition">Cancel</button>
                            </div>
                        </form>
                        <div id="ng-competition-list" class="ng-team-table-wrap"><p class="ng-team-empty">Select a model first.</p></div>
                    </section>
                </div>
            </div>
        </section>

        <section class="ng-team__panel" data-panel="accessories">
            <div class="ng-team__panel-header">
                <h1>Accessories</h1>
                <button class="ng-btn ng-btn--primary ng-btn--sm" id="btn-add-acc">+ Add Accessory</button>
            </div>
            <form id="ng-acc-form" class="ng-team-form" hidden>
                <input type="hidden" id="acc-edit-id">
                <div class="ng-team-form__grid">
                    <div class="ng-field ng-field--wide"><label>Name</label><input id="acc-name" type="text" required></div>
                    <div class="ng-field"><label>Category</label><input id="acc-category" type="text" placeholder="e.g. Protection"></div>
                    <div class="ng-field"><label>Price (NPR)</label><input id="acc-price" type="number" min="0"></div>
                    <div class="ng-field ng-field--wide"><label>Description</label><textarea id="acc-desc" rows="2"></textarea></div>
                    <div class="ng-field ng-field--wide"><label>Image URL</label><input id="acc-image" type="url"></div>
                    <div class="ng-field ng-field--wide"><label>Affiliate URL</label><input id="acc-affiliate" type="url"></div>
                </div>
                <div class="ng-team-form__actions">
                    <button type="submit" class="ng-btn ng-btn--primary">Save Accessory</button>
                    <button type="button" class="ng-btn ng-btn--outline" id="btn-cancel-acc">Cancel</button>
                </div>
            </form>
            <div id="ng-acc-list" class="ng-team-table-wrap"><p class="ng-team-loading">Loading…</p></div>
        </section>

        <section class="ng-team__panel" data-panel="leads">
            <div class="ng-team__panel-header">
                <h1>Leads</h1>
                <select id="ng-lead-filter-status" class="ng-team-select">
                    <option value="">All statuses</option>
                    <option value="new">New</option>
                    <option value="contacted">Contacted</option>
                    <option value="follow_up">Follow-up</option>
                    <option value="converted">Converted</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
            <div id="ng-leads-list" class="ng-team-table-wrap"><p class="ng-team-loading">Loading…</p></div>
        </section>

        <section class="ng-team__panel" data-panel="showrooms">
            <div class="ng-team__panel-header">
                <h1>Showrooms</h1>
                <button class="ng-btn ng-btn--primary ng-btn--sm" id="btn-add-showroom">+ Add Showroom</button>
            </div>
            <form id="ng-showroom-form" class="ng-team-form" hidden>
                <input type="hidden" id="showroom-edit-id">
                <div class="ng-team-form__grid">
                    <div class="ng-field ng-field--wide"><label>Name</label><input id="showroom-name" type="text" required></div>
                    <div class="ng-field"><label>City</label><input id="showroom-city" type="text"></div>
                    <div class="ng-field"><label>Phone</label><input id="showroom-phone" type="tel"></div>
                    <div class="ng-field"><label>Email</label><input id="showroom-email" type="email"></div>
                    <div class="ng-field ng-field--wide"><label>Address</label><input id="showroom-address" type="text"></div>
                    <div class="ng-field ng-field--wide"><label>Brand Slugs (comma separated)</label><input id="showroom-brands" type="text" placeholder="byd,toyota"></div>
                    <div class="ng-field ng-field--wide"><label>Google Maps URL</label><input id="showroom-maps" type="url"></div>
                </div>
                <div class="ng-team-form__actions">
                    <button type="submit" class="ng-btn ng-btn--primary">Save Showroom</button>
                    <button type="button" class="ng-btn ng-btn--outline" id="btn-cancel-showroom">Cancel</button>
                </div>
            </form>
            <div id="ng-showrooms-list" class="ng-team-table-wrap"><p class="ng-team-loading">Loading…</p></div>
        </section>

        <section class="ng-team__panel" data-panel="garages">
            <div class="ng-team__panel-header">
                <h1>Upgrade Garages</h1>
                <button class="ng-btn ng-btn--primary ng-btn--sm" id="btn-add-garage">+ Add Garage</button>
            </div>
            <form id="ng-garage-form" class="ng-team-form" hidden>
                <input type="hidden" id="garage-edit-id">
                <div class="ng-team-form__grid">
                    <div class="ng-field ng-field--wide"><label>Name</label><input id="garage-name" type="text" required></div>
                    <div class="ng-field"><label>City</label><input id="garage-city" type="text"></div>
                    <div class="ng-field"><label>Phone</label><input id="garage-phone" type="tel"></div>
                    <div class="ng-field"><label>Email</label><input id="garage-email" type="email"></div>
                    <div class="ng-field ng-field--wide"><label>Specialties (comma separated)</label><input id="garage-specs" type="text" placeholder="EV charging, wrap, tint"></div>
                    <div class="ng-field ng-field--wide"><label>Address</label><input id="garage-address" type="text"></div>
                    <div class="ng-field ng-field--wide"><label>Google Maps URL</label><input id="garage-maps" type="url"></div>
                </div>
                <div class="ng-team-form__actions">
                    <button type="submit" class="ng-btn ng-btn--primary">Save Garage</button>
                    <button type="button" class="ng-btn ng-btn--outline" id="btn-cancel-garage">Cancel</button>
                </div>
            </form>
            <div id="ng-garages-list" class="ng-team-table-wrap"><p class="ng-team-loading">Loading…</p></div>
        </section>

        <section class="ng-team__panel" data-panel="content">
            <h1>Editorial Publishing</h1>
            <p class="ng-team__lede">Use WordPress for blog articles and events. Use Claude Code only to draft research articles when it saves time.</p>

            <div class="ng-team-resource-grid">
                <a class="ng-team-resource-card" href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>" target="_blank" rel="noopener">
                    <strong>Write a new article</strong>
                    <span>Open the WordPress editor and publish a normal blog post.</span>
                </a>
                <a class="ng-team-resource-card" href="<?php echo esc_url( admin_url( 'edit.php?post_status=draft' ) ); ?>" target="_blank" rel="noopener">
                    <strong>Review drafts</strong>
                    <span>Approve AI-assisted drafts or continue editing unfinished posts.</span>
                </a>
                <a class="ng-team-resource-card" href="<?php echo esc_url( admin_url( 'edit.php?post_type=event' ) ); ?>" target="_blank" rel="noopener">
                    <strong>Manage events</strong>
                    <span>Add upcoming events, update details, and review past event entries.</span>
                </a>
                <a class="ng-team-resource-card" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=event' ) ); ?>" target="_blank" rel="noopener">
                    <strong>Create an event</strong>
                    <span>Open the event editor with date, venue, city, organizer, and link fields.</span>
                </a>
                <a class="ng-team-resource-card" href="<?php echo esc_url( admin_url( 'admin.php?page=ng-content-automation' ) ); ?>" target="_blank" rel="noopener">
                    <strong>Tracked brand news</strong>
                    <span>Choose which brands Claude Code should monitor for draft news articles.</span>
                </a>
            </div>

            <div class="ng-team-block">
                <div class="ng-team-block__head">
                    <div>
                        <h2>Recommended workflow</h2>
                        <p>Keep product intelligence in Supabase-backed sections and editorial publishing in WordPress.</p>
                    </div>
                </div>
                <ol class="ng-team-howto">
                    <li>Add or edit the vehicle in <strong>Vehicles</strong>.</li>
                    <li>Open <strong>Product Content</strong> to upload gallery URLs, videos, brochure, parts, issues, and competitors.</li>
                    <li>Use <strong>Write a new article</strong> for manual blog posts or run <code>/ng-brand-news BYD</code> in Claude Code to create a draft.</li>
                    <li>Use <strong>Create an event</strong> for launches, meetups, drives, or showcases, then publish when ready.</li>
                </ol>
            </div>
        </section>
    </main>
</div>

<?php get_footer(); ?>