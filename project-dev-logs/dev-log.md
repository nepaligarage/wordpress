# NepaliGarage — Project Development Log

**Site:** https://nepaligarage.com
**Stack:** WordPress (theme + plugin) + Supabase (PostgreSQL)
**Current branch:** `phase-1/comparison-engine`

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
