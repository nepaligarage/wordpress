<?php get_header(); ?>

<!-- ── Auth gate ──────────────────────────────────────────────────────────── -->
<div id="ng-team-gate" class="ng-team-gate">
    <div class="ng-team-gate__card">
        <div class="ng-logo"><span class="ng-logo__mark">NG</span><span class="ng-logo__text">Nepali<strong>Garage</strong></span></div>
        <h1>Team Dashboard</h1>
        <p>Sign in with your team account to continue.</p>
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
                Content
            </a>
        </nav>
        <div class="ng-team__sidebar-footer">
            <span id="ng-team-user-label" class="ng-team__user-label"></span>
            <button id="ng-team-signout" class="ng-btn ng-btn--sm ng-btn--outline">Sign Out</button>
        </div>
    </aside>

    <main class="ng-team__main">

        <!-- Overview -->
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

        <!-- Brands -->
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

        <!-- Vehicles -->
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

        <!-- Accessories -->
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

        <!-- Leads -->
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

        <!-- Showrooms -->
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

        <!-- Garages -->
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

        <!-- Content -->
        <section class="ng-team__panel" data-panel="content">
            <h1>Content Pipeline</h1>
            <p>Articles are generated by Claude Code and posted as drafts for human review.</p>
            <div class="ng-team-content-actions">
                <a href="<?php echo esc_url( admin_url( 'edit.php?post_status=draft' ) ); ?>" class="ng-btn ng-btn--primary" target="_blank">Review Drafts in WP Admin →</a>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=ng-content-automation' ) ); ?>" class="ng-btn ng-btn--outline" target="_blank">Manage Tracked Brands →</a>
            </div>
            <h2>How to publish</h2>
            <ol class="ng-team-howto">
                <li>Run <code>/ng-brand-news BYD</code> in Claude Code</li>
                <li>Claude researches, fact-checks, and posts a draft</li>
                <li>Review the draft above</li>
                <li>Edit if needed → Publish</li>
            </ol>
        </section>

    </main>

</div><!-- #ng-team -->

<?php get_footer(); ?>
