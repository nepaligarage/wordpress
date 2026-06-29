# NepaliGarage — Project Development Log

**Site:** https://nepaligarage.com
**Stack:** WordPress (theme + plugin) + Supabase (PostgreSQL)
**Current branch:** `phase-1/comparison-engine`

---

## 2026-06-29 — Session: Phase 2 — My Garage (owner logbook) ✅

### Summary
Filled the four gaps in the already-live (but zero-row) **free owner logbook**: vehicle **photo upload**, **video links**, recurring **tax/renewal obligations** (multi-country, Nepal-seeded), and an aggregated in-dashboard **Upcoming & Overdue** view. No outbound notifications — reminders are purely visual (due-soon/overdue badges). All logbook data flows through the browser Supabase SDK under `auth.uid() = user_id` RLS; photos stay in the **private** `vehicle-photos` bucket served by signed URLs.

### What shipped
| Change | File | Detail |
|--------|------|--------|
| Schema (migration) | `docs/migrations/2026-06-29-my-garage-owner-logbook.sql` | `user_vehicles` += `videos jsonb`, `country_code text`; new `vehicle_obligations` (RLS `_own`, `ng_touch_updated_at` trigger, due-date index); new `obligation_templates` (anon-read) seeded with 5 Nepal defaults (yearly tax, road tax, insurance, pollution, registration) |
| Photo upload | `dashboard.js` | Vehicle insert → `.select('id').single()` → upload files to `vehicle-photos/{uid}/{vehicleId}/…` → store paths in `photos` jsonb; cards hydrate via `createSignedUrls` (1h) |
| Video links | `page-dashboard.php`, `dashboard.js` | Repeatable URL+title rows in Add-Vehicle modal; `collectVideos()` → `videos` jsonb `{url,title,provider}`; rendered as chips on cards |
| Tax & Renewals tab | `page-dashboard.php`, `dashboard.js` | New nav tab + panel + Add/Mark-Paid modal; type select populated from `obligation_templates` by vehicle `country_code`; `next_due_date` auto-computed (last paid + period months); status badges reuse `ng-doc-card--valid/--warning/--expired` |
| Upcoming & Overdue | `page-dashboard.php`, `dashboard.js` | `loadUpcoming()` merges `documents.expiry_date` + `maintenance_logs.next_due_date` + `vehicle_obligations.next_due_date`, filters to within each item's reminder window, sorts soonest-first |
| Styles | `assets/css/dashboard.css` | Card photo strip, video chips, video-row inputs, obligation sub-line, Upcoming panel |
| Cache-bust | `functions.php` | `NGT_VERSION` 1.0.0 → 1.1.0 |

### Verified
- Migration applied via `psql` (direct connection): `videos`/`country_code` columns present, `vehicle_obligations` + `vehicle_obligations_own` policy present, 5 NP `obligation_templates` seeded.
- `dashboard.js` passes `node --check`.

### Notes / guardrails
- Seeded obligation templates are **owner-confirmable `estimated` defaults** (periods only, no legal amounts) — UI hint says to confirm rates/dates with the local authority.
- Multi-country is Nepal-first + `country_code`-aware; other countries can be seeded into `obligation_templates` without a rebuild.

### Deferred (acknowledged, separate plans)
- Editorial `/team/` Supabase workspace (create vehicles / upload media / post launch blogs).
- Periodic content + social automation (tax/discount/loan/exchange/event topics, draft-only).
- Outbound reminders (email/WhatsApp/SMS) — only if the in-dashboard view proves insufficient.

### Next
- Live verification at `/dashboard/`: add vehicle w/ photos + video, add an overdue obligation, confirm RLS isolation with a 2nd test user.

---

## 2026-06-29 — Session: Phase C — Finance / EMI System ✅

### Summary
Finance data moved out of hardcoded PHP into a Supabase **`brand_finance_programs`** table with RLS, a mandatory-source CHECK constraint, and an optional per-tenure child table. Every priced vehicle now shows an EMI calculator: official schemes when published, otherwise an **indicative estimate** with an editable rate and a clear disclaimer — no vehicle ever renders a hidden/empty finance block (spec §5/§7). Added **flat-rate** EMI math alongside the existing reducing-balance amortization.

### What shipped
| Change | File | Detail |
|--------|------|--------|
| Schema (new) | Supabase `brand_finance_programs` + `brand_finance_program_terms` | RLS anon-read `is_active=true`; CHECK forces non-manual rows to carry `source_url`+`source_label`+`verified_at`; `updated_at` trigger; indexes on `(brand_id,is_active)` and `(model_id)` |
| Seed | Supabase | BYD Atto 2 official baseline (5.27% reducing, 40% min down, 84-mo, Cimex source), `is_active=true` |
| Data layer (rewrite) | `functions.php` `ngt_vehicle_finance_programs()` | Queries Supabase by `brand_id` + (`model_id` OR brand-wide null) via PostgREST `or=()`, model-specific ranked first; 15-min cache. Replaces the hardcoded BYD-only return |
| Row → contract map | `functions.php` `ngt_map_finance_program()` | Maps DB row to the JS program shape; derives `supported_years` from `max_tenure_months` (or child terms), builds source/verification note + processing-fee note, carries `interest_type` |
| Generic fallback (new) | `functions.php` `ngt_generic_finance_program()` | 12% reducing, 20% down, 1–7yr, **editable rate**, `is_generic=true`, indicative disclaimer |
| Always-render finance | `page-vehicle.php` | Falls back to generic program when no official scheme + price exists; dynamic eyebrow/title/helper; interest field editable (not readonly) in generic mode |
| Flat-rate + editable rate | `assets/js/enquiry.js` | `renderFinance()` branches `interest_type` (flat vs reducing); generic program reads rate from the editable field; term summary labels flat/reducing; new `input` listener on the interest field |

### Verified
- Exact PostgREST query (anon key + RLS + `or` filter + nested embed) returns the seeded BYD program — confirmed against live REST API.
- Seed row active and matches the previous hardcoded numbers (5.27% / 40% / 7yr), so the BYD Atto 2 page is unchanged for buyers but now DB-driven.
- `enquiry.js` passes `node --check`.

### Deferred (spec items not in this pass)
- Admin "Finance Programs" UI with operator-creates-draft / admin-approves workflow (spec §6) — mandatory-source rule is enforced for now by the DB CHECK constraint + render-time gating.
- Per-tenure variable rates (`brand_finance_program_terms`) are schema-ready but not yet surfaced in the UI (single program rate used).

### Next
- Phase D: parts request MVP (`part_requests` table, request form).

---

## 2026-06-29 — Session: Phase B — Builder-First Compare Hub ✅

### Summary
`/compare/` is now a **builder**, not just an editorial library. Users pick any two cars (brand / body type / search), get a shareable `?a=&b=` URL, and land on the existing server-rendered comparison. Editorial comparisons moved below as a secondary section. Shipped via auto-deploy (run 28357650345 → success).

### Commit
| Hash | Description |
|------|-------------|
| `30f609e` | feat: Phase B — builder-first /compare/ with two-slot vehicle picker |

### What shipped
| Change | File | Detail |
|--------|------|--------|
| Catalog helper | `functions.php` `ngt_compare_catalog()` | Fetches all `is_available_nepal` variants, groups to one entry per model (cheapest variant), nested brand/model embed; 15-min cache |
| Builder enqueue | `functions.php` | `compare-builder.js` + `ngCompareBuilder` (catalog + compareBase) localized, only on `is_page('compare')` |
| Builder UI (new) | `assets/js/compare-builder.js` | Two slots A/B, brand+body-type dropdowns, debounced search, result list (cap 80), dedupe same model across slots, localStorage `ng_compare_list` sync, `history.replaceState` shareable URL, hydrate from `?a=&b=` |
| Tray sync | `assets/js/compare.js` | Listens for `ng:compare:change` → re-render global tray when builder mutates list |
| Page restructure | `page-compare.php` | Builder section above benefits; hero CTA → "Build a comparison"; editorial library now secondary; result-page back btn → "Pick different cars" |
| Styles | `theme.css` | `.ng-cbuilder` builder styles using existing tokens; responsive A‑VS‑B grid at ≥860px |

### Verified live
- `compare-builder.js` → 200, contains `ngCompareBuilder`
- `/compare/` → builder root present, catalog inlined with **93 models**
- Builder URL `/compare/?a=byd-atto-3-standard-range&b=byd-seal-dynamic` → 200, comparison table renders, "Pick different cars" back button present

### Design decision (deviation from spec)
Spec §7 said URL uses **model** slugs; shipped reality uses **variant** slugs (the `[ng_compare]` renderer queries the `variants` table by slug, and the Phase A `ng_compare_list` already stores variant slugs). Stayed consistent with production to avoid orphaning existing compare lists.

### Deferred (spec items not in this pass)
- Live in-place car swap on the result page **without reload** (spec §3/§8) — currently the result page is server-rendered; swapping returns to the builder. Needs client-side comparison rendering or an AJAX endpoint.
- Finance EMI side-by-side block (spec §4.3) — belongs to Phase C.
- Spec-table "better value" highlighting (spec §4.2) — enhancement to `NG_Comparison_Renderer`.

### Next
- Phase C: finance/EMI (`brand_finance_programs` table, shared downpayment+tenure controls)
- Phase D: parts request MVP

---

## 2026-06-29 — Session: CI/CD Auto-Deploy Live ✅

### Summary
GitHub Actions auto-deploy pipeline is fully operational. Every push to `main` or `phase-*/**` now rsyncs theme + plugin to the cPanel server and runs the route smoke test — no manual ZIP upload needed.

### What shipped
| Item | Detail |
|------|--------|
| `.github/workflows/deploy.yml` | Triggers on push to `main` / `phase-*/**`; SSH via `CPANEL_SSH_KEY` secret; rsync theme + plugin; verify routes |
| `automation/scripts/deploy.sh` | `HOST` → `nepaligarage` SSH config alias for local on-demand deploys |
| GitHub secrets | `CPANEL_SSH_KEY`, `CPANEL_SSH_HOST`, `CPANEL_SSH_USER`, `CPANEL_KNOWN_HOSTS` |

### Issues fixed during setup
- **`workflow` OAuth scope** — push of `.github/workflows/` rejected; resolved with a PAT carrying `repo` + `workflow` scopes
- **Branch glob** — `phase-*` doesn't match slashes; changed to `phase-*/**`
- **`error in libcrypto` (3 failed runs)** — `CPANEL_SSH_KEY` secret corrupted by manual copy-paste; **fixed by setting via gh CLI directly from the key file** (`gh secret set CPANEL_SSH_KEY < ~/.ssh/nepaligarage_deploy`)

### Verified live (run 28356508898 → success, 41s)
- `/compare/?a=byd-atto-2-standard-range&b=urban-cruiser-ebella-61kwh` → 200
- `compare.js` deployed & contains `ng_compare_list` → 200
- Compare tray present in homepage HTML (8 matches)
- Homepage → 200

### Now possible
- Claude can deploy autonomously: push to `phase-*/**` → live in ~60s, or run `bash automation/scripts/deploy.sh` locally

---

## 2026-06-29 — Session: Phase A Implementation (Agentic Loop)

### Summary
Fan-out of 3 parallel sub-agents → coder implementation → committed. All Phase A changes shipped.

### Commits this session
| Hash | Description |
|------|-------------|
| `800a0c3` | git-agent: commit pending files (header.php, footer.php, filter.js, docs/plans/) |
| `bde7535` | docs: add project development log |
| `749bfbb` | docs: add 5 product spec docs for course correction |
| `6425b58` | feat: Phase A — compare tray, dynamic compare route, CTA contrast |

### Agents run
- **git-agent** — committed 5 pending/untracked files from prior session
- **spec-writer** — wrote 5 spec docs to `docs/specs/` (product page, comparison builder, finance, parts, team workflow)
- **researcher** — deep-read 9 codebase files, produced `phase-a-brief.md` with exact line numbers and a critical slug-mismatch bug finding
- **coder** — implemented all 7 Phase A changes

### Phase A — What was implemented (commit `6425b58`)

| Change | File | Detail |
|--------|------|--------|
| CTA contrast fix | `vehicle.css:93-98` | Border opacity .62→.92, fill .06→.12 |
| Variant slug fix | `page-vehicle.php:303-310` | Added `data-variant-slug`, `data-price`, `data-thumb` (fixes model-vs-variant mismatch) |
| Compare tray (new) | `assets/js/compare.js` | Vanilla JS, localStorage `ng_compare_list`, 0/2→2/2 state, slot cards, remove, URL routing |
| Tray HTML | `footer.php:69` | `#ng-compare-tray` injected before `wp_footer()` — global across all pages |
| Tray CSS | `theme.css` | `.ng-compare-tray` styles using existing design tokens |
| Dynamic compare route | `page-compare.php:5-28` | `?a=slug&b=slug` handled at top, reuses `[ng_compare]` shortcode |
| Global enqueue | `functions.php:57-63` | `compare.js` enqueued alongside `nav.js` on all pages |

### Critical bug fixed
Previous code stored `modelSlug` in compare state, but `[ng_compare]` shortcode queries Supabase `variants` table by `variantSlug`. These are different values — model "Atto 2" has variants "atto-2-standard-range" etc. Fixed by adding `data-variant-slug` from `$active['slug']` (cheapest/default variant).

### Still pending (next session)
- Deploy theme ZIP to production and live-verify compare tray + `/compare/?a=&b=` route
- Phase B: convert `/compare/` hub from article-first to builder-first (vehicle picker by brand/type)
- Phase C: finance/EMI system (`brand_finance_programs` table, downpayment controls)
- Phase D: parts request MVP (`part_requests` table, request form)
- Owner action: rotate Supabase DB password

---

## 2026-06-29 — Session: Project Status Audit

### Summary
First dev log entry. Compiled from git history, HANDOFF.md, docs/plans, and codebase inspection.

---

## Phase 0 — Foundation (Completed: 2026-06-07)

**Commit:** `735da11`

- Brand identity defined (colors, typography, tone of voice) in `/brand/`
- Product guardrails written in `/guardrails/product-guardrails.md`
- Architecture decision: WordPress = rendering engine + SEO; Supabase = database of record
- PDR (Product Definition Record) written in `/research/NEPALIGARAGE_PDR.md`
- Supabase schema established: `brands`, `models`, `variants`, `spec_fields`, `variant_specs`, `prices`, `accessories`, `leads`, `orders`, `partners`
- Core rule locked: service role key stays server-side only; anon key may go to browser behind RLS

---

## Phase 1 — Comparison Engine (In Progress)

Branch: `phase-1/comparison-engine`

### 1.1 Plugin v0.1.0 (2026-06-07)

**Commit:** `203a94c`

- `nepaligarage-core.php` bootstrapped with `ng_load_includes()`
- Classes built: `NG_Supabase`, `NG_Admin`, `NG_Content_Automator`, `NG_Comparison_Renderer`, `NG_Price_Estimator`
- Custom WordPress roles: `ng_operator`, `ng_dealer_partner`
- `[ng_compare]` shortcode for rendering two-variant comparison tables
- `[ng_price_estimator]` shortcode for Nepal customs duty calculator
- WordPress admin sub-pages: Leads, Orders, Vehicles, Accessories, Settings, Comparisons

### 1.2 First Comparison Published (2026-06-07–11)

**Commits:** `969173f`, `5135f77`

- Comparison table rendered: **BYD Atto 2 vs Urban Cruiser eBella**
- Schema fix: added `comparisons.variant_slugs text[]` (table had only uuid `variant_ids`)
- Populated `{byd-atto-2-standard-range, urban-cruiser-ebella-61kwh}` in Supabase
- WP page id 7 published with `[ng_compare]` shortcode
- `/compare/[slug]/` routed to `page-comparison-detail.php`
- Publish guard: admin disables button when fewer than 2 variant slugs present

### 1.3 Routing & Page Templates (2026-06-11–12)

**Commits:** `affdb59`, `5135f77`

- `template_include` filter: any `/cars/[brand]/[model]/` URL → `page-vehicle.php` (no manual template assignment needed)
- New public templates added: `page-about.php`, `page-brand.php`, `page-compare.php`, `page-comparison-detail.php`, `page-contact.php`, `page-electric-vehicles.php`, `page-new-cars.php`, `page-news.php`, `page-price-estimator.php`, `page-privacy-policy.php`
- Removed links to unbuilt Phase 4 URLs: `/garages/`, `/used-cars/`, `/parts-finder/`
- `/cars/[brand]/` returns 404 when brand slug is unknown
- Vehicle detail 404 if URL brand slug doesn't match model's actual brand

### 1.4 Spec Data & Pricing Honesty (2026-06-11)

**Commit:** `5135f77`

- 308 `official` rows + 13 `unverified` rows inserted into `variant_specs`
- 8 variants backfilled: creta-electric, kona-electric, ioniq-5, ev6, ev9, niro-ev, seltos, fortuner
- All official rows sourced from manufacturer spec pages (hyundai.com, kia.com, toyota.com)
- 70 `starting_price_npr` values inserted into `prices` as `estimated_on_road` / `unverified`
- Theme helpers added: `ngt_prices_for_variants()`, `ngt_variant_price_display()`, `ngt_price_badge_html()`
- Confidence badges: `official` | `estimated` | `unverified` shown on vehicle/listing pages
- Homepage copy softened — no longer claims "official manufacturer data" or "122 spec fields"

### 1.5 Brand Marquee & Content Automation (2026-06-09–12)

**Commits:** `c192838`, `affdb59`

- Dual brand marquees on homepage: cars + bikes
- Rsync deploy script added: `automation/scripts/verify-public-routes.sh`, `automation/scripts/build-wordpress-zips.sh`
- `/ng-brand-news` skill: automated 700–900 word articles (WebSearch → fetch → cross-reference → WordPress REST draft)
- Always posts as `draft` — human review before publish

### 1.6 BYD Brand Focus (2026-06-14)

**Commits:** `5950e71`, `274ef69`, `4f513af`, `bddac99`, `c88b4c0`, `e10537e`, `07174b3`, `7bca85a`, `0602467`

- BYD brand readiness audit documented in `docs/plans/2026-06-14-byd-brand-readiness-audit.md`
- BYD vehicle hero images and brochure fallbacks added
- Vehicle specs and highlights layout polished
- BYD Atto 2 finance and compare experience upgraded
- Team content manager and events workflow built (`page-team.php`, `archive-event.php`, `single-event.php`)
- WordPress 404 guessing disabled on `/cars/*` routes — treated as real pages
- Fallbacks added for cached vehicle pages (hero + brochure)
- `filter.js` added (untracked) for vehicle filtering

### 1.7 Pending / Incomplete in Phase 1

| Task | Status | Notes |
|------|--------|-------|
| P0.1 Rotate leaked credentials | Partial | WP app password rotated; Supabase DB password still pending (owner action required — must use Supabase Dashboard → Settings → Database) |
| P1.1 Live verify `/compare/[slug]/` | Pending | Theme needs to be deployed and live-checked |
| P1.4 Pricing badges on live pages | Pending | Code done; deployment verification needed |
| Live route verification | Pending | Run `automation/scripts/verify-public-routes.sh https://nepaligarage.com` |

---

## Product Course Correction (Documented: 2026-06-14)

**File:** `docs/plans/2026-06-14-product-course-correction.md`

A major product direction change was planned (documentation only, not yet implemented):

### What changes
1. **Compare starts from product pages** — "Add to Compare" button on every vehicle page; comparison becomes an active shortlist workflow
2. **Compare hub becomes a builder** — vehicle picker by brand/type/body; editorial comparisons secondary
3. **Finance becomes real buyer tooling** — user-adjustable downpayment, EMI from brand-supported schemes with official source URLs
4. **Parts become demand capture** — parts request/want-list form → future authorized supplier marketplace

### New data tables required
- `brand_finance_programs` — interest rates, tenure, partner name, source URL, verified_at
- `brand_finance_program_terms` — tenure slab options
- `part_requests` — model, part name, requester details, urgency, status

### Implementation sequence (not started)
- Phase A: UX correctness (fix CTA contrast, add Add to Compare, compare drawer, route to `/compare/`)
- Phase B: Compare hub → builder-first
- Phase C: Finance realism (schema, admin UI, EMI controls)
- Phase D: Parts demand capture (tab, request form, team review surface)

### Open questions (unresolved)
1. Compare: exactly 2 cars at launch, or up to 3?
2. Finance mini-widget on product pages or only on `/compare/`?
3. When no official finance scheme exists: generic calculator or hide EMI?
4. Parts requests: public on site, private lead capture, or both?
5. Supplier response channel: dashboard, email, WhatsApp, or admin mediation?
6. Shareable `/compare/` URL state? e.g. `/compare/?a=byd-atto-3&b=hyundai-creta-ev`

---

## Active Branches

| Branch | Purpose | Status |
|--------|---------|--------|
| `main` | Stable releases | Phase 0 foundation |
| `phase-1/comparison-engine` | Current dev | Active — see above |
| `phase-2/my-garage` | User vehicle logs | Not started |
| `phase-3/ai-assistant` | AI chat feature | Not started |
| `phase-4/parts-finder` | Parts marketplace | Not started |
| `automation/content-engine` | Daily article pipeline | Partially built |

---

## Uncommitted Changes (as of 2026-06-29)

| File | Status |
|------|--------|
| `theme/nepaligarage-theme/footer.php` | Modified (unstaged) |
| `theme/nepaligarage-theme/header.php` | Modified (unstaged) |
| `desing_page_wise` | Untracked file (design reference text) |
| `docs/plans/2026-06-14-product-course-correction.md` | Untracked |
| `theme/nepaligarage-theme/assets/js/filter.js` | Untracked |

---

## Immediate Next Steps

1. Review uncommitted changes in `header.php` and `footer.php` — commit or discard
2. Stage and commit the untracked files (`docs/plans/`, `filter.js`)
3. Deploy theme + plugin ZIPs to production via WordPress admin
4. Run `automation/scripts/verify-public-routes.sh https://nepaligarage.com`
5. Live-verify: `/compare/[slug]/`, vehicle pages with spec badges, pricing confidence badges
6. Owner action: rotate Supabase database password via Supabase Dashboard
7. Begin product course correction implementation — start with spec docs per section 6 of the plan

---

*Log maintained by Claude Code. Update this file at the end of each significant session.*
