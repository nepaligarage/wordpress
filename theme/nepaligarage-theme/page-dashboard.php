<?php
/*
 * Template Name: My Garage Dashboard
 */
get_header();
?>

<div id="ng-dashboard" class="ng-dashboard" hidden>

    <div class="ng-dashboard__sidebar">
        <nav class="ng-dash-nav" aria-label="Dashboard navigation">
            <a href="#vehicles" class="ng-dash-nav__item ng-dash-nav__item--active" data-tab="vehicles">
                <svg viewBox="0 0 24 24" fill="none"><path d="M3 17V11l4-6h10l4 6v6" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><circle cx="7.5" cy="17.5" r="2" stroke="currentColor" stroke-width="1.5"/><circle cx="16.5" cy="17.5" r="2" stroke="currentColor" stroke-width="1.5"/></svg>
                My Vehicles
            </a>
            <a href="#maintenance" class="ng-dash-nav__item" data-tab="maintenance">
                <svg viewBox="0 0 24 24" fill="none"><path d="M12 20V4M4 12h16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><rect x="3" y="3" width="18" height="18" rx="3" stroke="currentColor" stroke-width="1.5"/></svg>
                Maintenance
            </a>
            <a href="#fuel" class="ng-dash-nav__item" data-tab="fuel">
                <svg viewBox="0 0 24 24" fill="none"><path d="M5 22V4a1 1 0 011-1h8a1 1 0 011 1v8h2a2 2 0 012 2v5a2 2 0 01-2 2H5z" stroke="currentColor" stroke-width="1.5"/><path d="M9 8h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                Fuel Log
            </a>
            <a href="#documents" class="ng-dash-nav__item" data-tab="documents">
                <svg viewBox="0 0 24 24" fill="none"><path d="M14 3H6a2 2 0 00-2 2v14a2 2 0 002 2h12a2 2 0 002-2V9l-6-6z" stroke="currentColor" stroke-width="1.5"/><path d="M14 3v6h6M9 13h6M9 17h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                Documents
            </a>
            <a href="#taxes" class="ng-dash-nav__item" data-tab="taxes">
                <svg viewBox="0 0 24 24" fill="none"><path d="M9 7h6M9 11h6M9 15h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><rect x="5" y="3" width="14" height="18" rx="2" stroke="currentColor" stroke-width="1.5"/></svg>
                Tax &amp; Renewals
            </a>
            <a href="#profile" class="ng-dash-nav__item" data-tab="profile">
                <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.5"/><path d="M4 20c0-4.418 3.582-8 8-8s8 3.582 8 8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                Profile
            </a>
        </nav>
    </div>

    <div class="ng-dashboard__main">

        <!-- ── My Vehicles ─────────────────────────────── -->
        <section class="ng-dash-panel" id="ng-panel-vehicles" data-panel="vehicles">
            <div class="ng-dash-panel__header">
                <h1>My Vehicles</h1>
                <button class="ng-btn ng-btn--primary ng-btn--sm" id="ng-add-vehicle-btn">+ Add Vehicle</button>
            </div>
            <div id="ng-upcoming" class="ng-upcoming" hidden></div>
            <div id="ng-vehicles-list" class="ng-vehicle-list">
                <div class="ng-loading">Loading your vehicles…</div>
            </div>
        </section>

        <!-- ── Maintenance ─────────────────────────────── -->
        <section class="ng-dash-panel" id="ng-panel-maintenance" data-panel="maintenance" hidden>
            <div class="ng-dash-panel__header">
                <h1>Maintenance Log</h1>
                <button class="ng-btn ng-btn--primary ng-btn--sm" id="ng-add-maint-btn">+ Add Entry</button>
            </div>
            <div class="ng-vehicle-selector" id="ng-maint-vehicle-selector"></div>
            <div id="ng-maintenance-list" class="ng-log-list">
                <p class="ng-empty-state">Select a vehicle to view its maintenance history.</p>
            </div>
        </section>

        <!-- ── Fuel Log ─────────────────────────────────── -->
        <section class="ng-dash-panel" id="ng-panel-fuel" data-panel="fuel" hidden>
            <div class="ng-dash-panel__header">
                <h1>Fuel Log</h1>
                <button class="ng-btn ng-btn--primary ng-btn--sm" id="ng-add-fuel-btn">+ Add Fill-up</button>
            </div>
            <div class="ng-vehicle-selector" id="ng-fuel-vehicle-selector"></div>
            <div id="ng-fuel-stats" class="ng-fuel-stats" hidden></div>
            <div id="ng-fuel-list" class="ng-log-list">
                <p class="ng-empty-state">Select a vehicle to view its fuel history.</p>
            </div>
        </section>

        <!-- ── Documents ───────────────────────────────── -->
        <section class="ng-dash-panel" id="ng-panel-documents" data-panel="documents" hidden>
            <div class="ng-dash-panel__header">
                <h1>Documents</h1>
                <button class="ng-btn ng-btn--primary ng-btn--sm" id="ng-add-doc-btn">+ Add Document</button>
            </div>
            <div class="ng-vehicle-selector" id="ng-doc-vehicle-selector"></div>
            <div id="ng-doc-list" class="ng-doc-grid">
                <p class="ng-empty-state">Select a vehicle to view its documents.</p>
            </div>
        </section>

        <!-- ── Tax & Renewals ──────────────────────────── -->
        <section class="ng-dash-panel" id="ng-panel-taxes" data-panel="taxes" hidden>
            <div class="ng-dash-panel__header">
                <h1>Tax &amp; Renewals</h1>
                <button class="ng-btn ng-btn--primary ng-btn--sm" id="ng-add-oblig-btn">+ Add / Mark Paid</button>
            </div>
            <p class="ng-field-hint" style="margin:-8px 0 12px;">Track recurring obligations — yearly vehicle tax, road tax, insurance, pollution. Defaults are starting points; confirm rates and dates with your local authority.</p>
            <div class="ng-vehicle-selector" id="ng-oblig-vehicle-selector"></div>
            <div id="ng-oblig-list" class="ng-doc-grid">
                <p class="ng-empty-state">Select a vehicle to view its tax &amp; renewal obligations.</p>
            </div>
        </section>

        <!-- ── Profile ─────────────────────────────────── -->
        <section class="ng-dash-panel" id="ng-panel-profile" data-panel="profile" hidden>
            <div class="ng-dash-panel__header">
                <h1>My Profile</h1>
            </div>
            <form id="ng-profile-form" class="ng-form ng-form--narrow">
                <div class="ng-form-group">
                    <label for="ng-profile-name">Full Name</label>
                    <input type="text" id="ng-profile-name" name="display_name" placeholder="Your name">
                </div>
                <div class="ng-form-group">
                    <label for="ng-profile-phone">Phone Number</label>
                    <input type="tel" id="ng-profile-phone" name="phone" placeholder="+977 98XXXXXXXX">
                </div>
                <div class="ng-form-group">
                    <label for="ng-profile-location">Location</label>
                    <input type="text" id="ng-profile-location" name="location" placeholder="Kathmandu, Bagmati">
                </div>
                <div class="ng-form-group">
                    <label>Email</label>
                    <input type="email" id="ng-profile-email" disabled class="ng-input--disabled">
                    <p class="ng-field-hint">Email cannot be changed here.</p>
                </div>
                <div class="ng-auth-error" id="ng-profile-error" hidden></div>
                <div class="ng-auth-success" id="ng-profile-success" hidden>Profile saved.</div>
                <button type="submit" class="ng-btn ng-btn--primary">Save Profile</button>
            </form>
        </section>

    </div><!-- .ng-dashboard__main -->

</div><!-- #ng-dashboard -->

<!-- ── Auth gate (shown while loading / logged out) ──────────────────────── -->
<div id="ng-dashboard-gate" class="ng-gate">
    <div class="ng-gate__inner">
        <div class="ng-logo"><span class="ng-logo__mark">NG</span></div>
        <h2>Sign in to access My Garage</h2>
        <p>Track your vehicles, maintenance history, fuel logs, and documents — all in one place.</p>
        <button class="ng-btn ng-btn--primary ng-auth-trigger" data-tab="signin">Sign In</button>
        <p style="margin-top:1rem;font-size:.9rem;color:#64748b;">Don't have an account? <button class="ng-link ng-auth-trigger" data-tab="register">Create one free</button></p>
    </div>
</div>

<!-- ── Add Vehicle Modal ──────────────────────────────────────────────────── -->
<div class="ng-modal-overlay" id="ng-vehicle-modal-overlay" aria-hidden="true">
    <div class="ng-modal ng-modal--lg" role="dialog" aria-modal="true" aria-labelledby="ng-vehicle-modal-title">
        <button class="ng-modal__close" id="ng-vehicle-modal-close" aria-label="Close">&times;</button>
        <h2 class="ng-modal__title" id="ng-vehicle-modal-title">Add a Vehicle</h2>
        <form id="ng-vehicle-form" class="ng-form">

            <div class="ng-form-row">
                <div class="ng-form-group">
                    <label for="ng-v-make">Make</label>
                    <select id="ng-v-make" name="make" required>
                        <option value="">Select make…</option>
                    </select>
                </div>
                <div class="ng-form-group">
                    <label for="ng-v-model">Model</label>
                    <select id="ng-v-model" name="model" required disabled>
                        <option value="">Select model…</option>
                    </select>
                </div>
                <div class="ng-form-group">
                    <label for="ng-v-variant">Variant</label>
                    <select id="ng-v-variant" name="variant_id" disabled>
                        <option value="">Select variant…</option>
                    </select>
                </div>
            </div>

            <!-- Add a missing model to the shared catalog (revealed once a make is chosen) -->
            <div class="ng-add-model" id="ng-v-add-model-wrap" hidden>
                <button type="button" class="ng-link ng-add-model__toggle" id="ng-v-add-model-toggle">+ Model not listed? Add it</button>
                <div class="ng-add-model__row" id="ng-v-add-model-row" hidden>
                    <input type="text" id="ng-v-new-model" placeholder="e.g. Creta" autocomplete="off">
                    <button type="button" class="ng-btn ng-btn--sm" id="ng-v-save-model">Add</button>
                    <button type="button" class="ng-link" id="ng-v-cancel-model">Cancel</button>
                </div>
                <p class="ng-field-hint" id="ng-v-add-model-hint">Adds it to the shared list so other owners can pick it too.</p>
            </div>

            <details class="ng-form-details">
                <summary>Brand not listed? Enter manually</summary>
                <div class="ng-form-row" style="margin-top:.75rem;">
                    <div class="ng-form-group">
                        <label for="ng-v-custom-make">Brand (manual)</label>
                        <input type="text" id="ng-v-custom-make" name="custom_make" placeholder="e.g. Suzuki">
                    </div>
                    <div class="ng-form-group">
                        <label for="ng-v-custom-model">Model (manual)</label>
                        <input type="text" id="ng-v-custom-model" name="custom_model" placeholder="e.g. Swift">
                    </div>
                </div>
            </details>

            <div class="ng-form-row">
                <div class="ng-form-group">
                    <label for="ng-v-plate">Plate Number</label>
                    <input type="text" id="ng-v-plate" name="plate_number" placeholder="Ba 1 Cha 1234">
                </div>
                <div class="ng-form-group">
                    <label for="ng-v-color">Color</label>
                    <input type="text" id="ng-v-color" name="color" placeholder="Pearl White">
                </div>
            </div>

            <div class="ng-form-row">
                <div class="ng-form-group">
                    <label for="ng-v-purchase-date">Purchase Date</label>
                    <input type="date" id="ng-v-purchase-date" name="purchase_date">
                </div>
                <div class="ng-form-group">
                    <label for="ng-v-odometer">Current Odometer (km)</label>
                    <input type="number" id="ng-v-odometer" name="current_odometer_km" placeholder="15000" min="0">
                </div>
            </div>

            <div class="ng-form-row">
                <div class="ng-form-group">
                    <label for="ng-v-name">Nickname (optional)</label>
                    <input type="text" id="ng-v-name" name="custom_name" placeholder="My daily driver">
                </div>
                <div class="ng-form-group">
                    <label for="ng-v-country">Country</label>
                    <select id="ng-v-country" name="country_code">
                        <option value="NP" selected>Nepal</option>
                        <option value="IN">India</option>
                        <option value="AE">UAE</option>
                        <option value="AU">Australia</option>
                        <option value="GB">United Kingdom</option>
                        <option value="US">United States</option>
                        <option value="OTHER">Other</option>
                    </select>
                </div>
            </div>

            <div class="ng-form-group">
                <label for="ng-v-photos">Photos (optional)</label>
                <input type="file" id="ng-v-photos" name="photos" accept="image/*" multiple>
                <p class="ng-field-hint">Add photos of your vehicle. Stored privately — only you can see them.</p>
            </div>

            <div class="ng-form-group">
                <label>Video links (optional)</label>
                <div id="ng-v-videos"></div>
                <button type="button" class="ng-link" id="ng-v-add-video" style="font-size:.85rem;">+ Add a video link</button>
                <p class="ng-field-hint">Paste a YouTube or other video URL — links only, no upload.</p>
            </div>

            <div class="ng-auth-error" id="ng-vehicle-error" hidden></div>
            <button type="submit" class="ng-btn ng-btn--primary ng-btn--full">Add to My Garage</button>
        </form>
    </div>
</div>

<!-- ── Add Maintenance Modal ──────────────────────────────────────────────── -->
<div class="ng-modal-overlay" id="ng-maint-modal-overlay" aria-hidden="true">
    <div class="ng-modal" role="dialog" aria-modal="true">
        <button class="ng-modal__close" id="ng-maint-modal-close" aria-label="Close">&times;</button>
        <h2 class="ng-modal__title">Log Maintenance</h2>
        <form id="ng-maint-form" class="ng-form">
            <div class="ng-form-row">
                <div class="ng-form-group">
                    <label for="ng-m-date">Date</label>
                    <input type="date" id="ng-m-date" name="log_date" required>
                </div>
                <div class="ng-form-group">
                    <label for="ng-m-type">Type</label>
                    <select id="ng-m-type" name="maintenance_type" required>
                        <option value="">Select type…</option>
                        <option value="oil_change">Oil Change</option>
                        <option value="tyre_rotation">Tyre Rotation</option>
                        <option value="tyre_replacement">Tyre Replacement</option>
                        <option value="brake_service">Brake Service</option>
                        <option value="battery">Battery Replacement</option>
                        <option value="filter">Air/Cabin Filter</option>
                        <option value="suspension">Suspension / Alignment</option>
                        <option value="ac_service">AC Service</option>
                        <option value="electrical">Electrical</option>
                        <option value="insurance_renewal">Insurance Renewal</option>
                        <option value="registration_renewal">Registration Renewal</option>
                        <option value="general_service">General Service</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>
            <div class="ng-form-group">
                <label for="ng-m-desc">Description</label>
                <textarea id="ng-m-desc" name="description" rows="2" placeholder="What was done?"></textarea>
            </div>
            <div class="ng-form-row">
                <div class="ng-form-group">
                    <label for="ng-m-cost">Cost (NPR)</label>
                    <input type="number" id="ng-m-cost" name="cost_npr" placeholder="2500" min="0">
                </div>
                <div class="ng-form-group">
                    <label for="ng-m-odometer">Odometer (km)</label>
                    <input type="number" id="ng-m-odometer" name="odometer_km" placeholder="15000" min="0">
                </div>
            </div>
            <div class="ng-form-group">
                <label for="ng-m-garage">Garage / Service Center</label>
                <input type="text" id="ng-m-garage" name="garage_name" placeholder="SAL Autohaus, Kathmandu">
            </div>
            <div class="ng-auth-error" id="ng-maint-error" hidden></div>
            <button type="submit" class="ng-btn ng-btn--primary ng-btn--full">Save Entry</button>
        </form>
    </div>
</div>

<!-- ── Add Fuel Modal ─────────────────────────────────────────────────────── -->
<div class="ng-modal-overlay" id="ng-fuel-modal-overlay" aria-hidden="true">
    <div class="ng-modal" role="dialog" aria-modal="true">
        <button class="ng-modal__close" id="ng-fuel-modal-close" aria-label="Close">&times;</button>
        <h2 class="ng-modal__title">Log Fill-up</h2>
        <form id="ng-fuel-form" class="ng-form">
            <div class="ng-form-row">
                <div class="ng-form-group">
                    <label for="ng-f-date">Date</label>
                    <input type="date" id="ng-f-date" name="log_date" required>
                </div>
                <div class="ng-form-group">
                    <label for="ng-f-type">Fuel Type</label>
                    <select id="ng-f-type" name="fuel_type">
                        <option value="petrol">Petrol</option>
                        <option value="diesel">Diesel</option>
                        <option value="electric">Electric (kWh)</option>
                        <option value="cng">CNG</option>
                    </select>
                </div>
            </div>
            <div class="ng-form-row">
                <div class="ng-form-group">
                    <label for="ng-f-liters">Liters / kWh</label>
                    <input type="number" id="ng-f-liters" name="liters" step="0.01" required placeholder="35.5" min="0">
                </div>
                <div class="ng-form-group">
                    <label for="ng-f-cost">Cost (NPR)</label>
                    <input type="number" id="ng-f-cost" name="cost_npr" required placeholder="4800" min="0">
                </div>
            </div>
            <div class="ng-form-row">
                <div class="ng-form-group">
                    <label for="ng-f-odometer">Odometer (km)</label>
                    <input type="number" id="ng-f-odometer" name="odometer_km" required placeholder="15400" min="0">
                </div>
                <div class="ng-form-group ng-form-group--check">
                    <label>
                        <input type="checkbox" name="full_tank" id="ng-f-full" checked>
                        Full tank
                    </label>
                </div>
            </div>
            <div class="ng-form-group">
                <label for="ng-f-station">Fuel Station</label>
                <input type="text" id="ng-f-station" name="fuel_station" placeholder="NOC, Koteshwor">
            </div>
            <div class="ng-auth-error" id="ng-fuel-error" hidden></div>
            <button type="submit" class="ng-btn ng-btn--primary ng-btn--full">Save Fill-up</button>
        </form>
    </div>
</div>

<!-- ── Add Document Modal ─────────────────────────────────────────────────── -->
<div class="ng-modal-overlay" id="ng-doc-modal-overlay" aria-hidden="true">
    <div class="ng-modal" role="dialog" aria-modal="true">
        <button class="ng-modal__close" id="ng-doc-modal-close" aria-label="Close">&times;</button>
        <h2 class="ng-modal__title">Add Document</h2>
        <form id="ng-doc-form" class="ng-form">
            <div class="ng-form-row">
                <div class="ng-form-group">
                    <label for="ng-d-type">Document Type</label>
                    <select id="ng-d-type" name="doc_type" required>
                        <option value="">Select type…</option>
                        <option value="bluebook">Bluebook (Registration)</option>
                        <option value="insurance">Insurance Certificate</option>
                        <option value="pollution">Pollution Certificate</option>
                        <option value="tax_clearance">Tax Clearance</option>
                        <option value="warranty">Warranty Card</option>
                        <option value="service_book">Service Book</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="ng-form-group">
                    <label for="ng-d-title">Title</label>
                    <input type="text" id="ng-d-title" name="title" required placeholder="Insurance 2081/82">
                </div>
            </div>
            <div class="ng-form-row">
                <div class="ng-form-group">
                    <label for="ng-d-issue">Issue Date</label>
                    <input type="date" id="ng-d-issue" name="issue_date">
                </div>
                <div class="ng-form-group">
                    <label for="ng-d-expiry">Expiry Date</label>
                    <input type="date" id="ng-d-expiry" name="expiry_date">
                </div>
            </div>
            <div class="ng-form-group">
                <label for="ng-d-reminder">Remind me (days before expiry)</label>
                <select id="ng-d-reminder" name="reminder_days">
                    <option value="7">7 days before</option>
                    <option value="14">14 days before</option>
                    <option value="30" selected>30 days before</option>
                    <option value="60">60 days before</option>
                </select>
            </div>
            <div class="ng-auth-error" id="ng-doc-error" hidden></div>
            <button type="submit" class="ng-btn ng-btn--primary ng-btn--full">Save Document</button>
        </form>
    </div>
</div>

<!-- ── Add Obligation Modal ───────────────────────────────────────────────── -->
<div class="ng-modal-overlay" id="ng-oblig-modal-overlay" aria-hidden="true">
    <div class="ng-modal" role="dialog" aria-modal="true">
        <button class="ng-modal__close" id="ng-oblig-modal-close" aria-label="Close">&times;</button>
        <h2 class="ng-modal__title">Tax / Renewal</h2>
        <form id="ng-oblig-form" class="ng-form">
            <div class="ng-form-group">
                <label for="ng-o-type">Obligation</label>
                <select id="ng-o-type" name="obligation_type" required>
                    <option value="">Select…</option>
                </select>
            </div>
            <div class="ng-form-group" id="ng-o-label-group" hidden>
                <label for="ng-o-label">Label</label>
                <input type="text" id="ng-o-label" name="label" placeholder="e.g. Annual road tax">
            </div>
            <div class="ng-form-row">
                <div class="ng-form-group">
                    <label for="ng-o-paid">Last Paid Date</label>
                    <input type="date" id="ng-o-paid" name="last_paid_date">
                </div>
                <div class="ng-form-group">
                    <label for="ng-o-period">Renews Every (months)</label>
                    <input type="number" id="ng-o-period" name="period_months" value="12" min="1" required>
                </div>
            </div>
            <div class="ng-form-row">
                <div class="ng-form-group">
                    <label for="ng-o-amount">Amount Paid (optional)</label>
                    <input type="number" id="ng-o-amount" name="amount_paid" placeholder="5000" min="0" step="0.01">
                </div>
                <div class="ng-form-group">
                    <label for="ng-o-next">Next Due</label>
                    <input type="date" id="ng-o-next" name="next_due_date" class="ng-input--disabled" readonly>
                    <p class="ng-field-hint">Auto-calculated from last paid + period.</p>
                </div>
            </div>
            <div class="ng-form-group">
                <label for="ng-o-notes">Notes (optional)</label>
                <textarea id="ng-o-notes" name="notes" rows="2" placeholder="Reference number, office, etc."></textarea>
            </div>
            <div class="ng-auth-error" id="ng-oblig-error" hidden></div>
            <button type="submit" class="ng-btn ng-btn--primary ng-btn--full">Save</button>
        </form>
    </div>
</div>

<?php get_footer(); ?>
