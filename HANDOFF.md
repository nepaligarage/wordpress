# NepaliGarage Agent Handoff

Last updated: 2026-06-11, Codex continuation

This file is the coordination point when switching between Claude and Codex. Keep it short, factual, and updated at the end of every agent session.

## Current Priority

Work through the approved remediation plan in order. Do not start P1 or P2 work until P0 is actually verified.

## Task Board

- [~] P0.1 Rotate leaked credentials — WP DONE, Supabase pending owner
  - 2026-06-11 (Claude): new WordPress application password created via REST, verified, old one revoked; `connection.md` updated. Repo scrub of `CLAUDE.md` and PDR done.
  - Still pending (owner, dashboard-only): reset Supabase database password — `ALTER ROLE postgres` is blocked by Supabase from SQL; must use Dashboard → Settings → Database. Note: the leaked password is also the WP admin *login* password — change that too.
- [x] P0.2 Fix broken nav/footer links
  - Code-level fix complete, pending deployment/live curl verification.
  - `theme/nepaligarage-theme/page.php` was added as a generic WordPress page template so shortcode pages can render.
  - `theme/nepaligarage-theme/functions.php` now routes `/about/`, `/cars/`, `/compare/`, `/electric-vehicles/`, `/nepal-car-price-estimator/`, and `/new-cars/` to theme templates.
  - Added templates: `page-about.php`, `page-brand.php`, `page-electric-vehicles.php`, `page-new-cars.php`, `page-price-estimator.php`.
  - Removed public theme links to unbuilt Phase 4 URLs: `/garages/`, `/used-cars/`, `/parts-finder/`.
  - Do not remove Claude's partial `page.php` unless it is proven wrong.
- [x] P0.3 Make homepage claims honest
  - Homepage trust strip no longer claims official manufacturer data for all vehicles or 122 spec fields per vehicle.
  - Footer and compare intro copy no longer claim every visible data point is official-source backed.
- [ ] P1.1 Publish first comparison live
  - Code-side route/rendering is in place, pending Supabase publication and live verification.
  - Added `page-comparison-detail.php` and routed `/compare/[slug]/` to it.
  - Detail page fetches a saved comparison by slug and renders the existing `[ng_compare]` shortcode only when the comparison is published and has at least two variant slugs.
  - Added `wp-admin → NepaliGarage → Comparisons` to list saved comparisons and publish/unpublish them via service-role Supabase PATCH.
  - Comparison publish/unpublish clears comparison and theme Supabase transients.
  - Admin publish is guarded: rows without at least two variant slugs have the Publish button disabled, and the POST handler also refuses publishing them.
  - 2026-06-11 (Claude): comparison is published in Supabase (`published=true`); WP page id 7 published with corrected `[ng_compare]` slugs.
  - 2026-06-11 (Claude): FIXED schema mismatch found in pre-flight — the detail template/admin read `variant_slugs` but the table only had uuid `variant_ids`. Added `comparisons.variant_slugs text[]` and populated `{byd-atto-2-standard-range, urban-cruiser-ebella-61kwh}`.
  - Still required: live verify `/compare/[slug]/` after theme deploy.
- [x] P1.2 Render source/confidence badges on vehicle pages
  - Code-level fix complete, pending PHP syntax/live verification.
  - `page-vehicle.php` now fetches `variant_specs(*,spec_field(*),source:spec_sources(*))`.
  - Added a sourced-spec section for the default active variant with confidence badges and source links.
  - Added vehicle-page CSS and theme badge fallbacks for `official`, `estimated`, and `unverified`.
- [x] P1.3 Backfill sourced specs for top Nepal-relevant variants
  - 2026-06-11: DONE — 308 `official` rows + 13 `unverified` in `variant_specs`. 8 variants backfilled (creta-electric, kona-electric, ioniq-5, ev6, ev9, niro-ev, seltos, fortuner) via Sonnet agent. All official rows sourced from manufacturer spec pages (hyundai.com, kia.com, toyota.com global). Nepal importer sites were unreachable during backfill — no Nepal prices inserted via this pass (prices table already has 71 unverified rows from prior migration). Fable validated: 8 random official rows spot-checked, all have real manufacturer URLs and sensible values.
- [ ] P1.4 Fix pricing data model
  - Code-side display honesty is improved, pending real DB migration/backfill.
  - Added theme helpers: `ngt_prices_for_variants()`, `ngt_variant_price_display()`, and `ngt_price_badge_html()`.
  - Listing pages and vehicle pages now prefer rows from `prices` and show confidence/source metadata.
  - Fallback `variants.starting_price_npr` is displayed as `unverified` with `Unverified price cache`.
  - 2026-06-11 (Claude): Supabase migration DONE — 70 `starting_price_npr` values inserted into `prices` as `estimated_on_road` / `unverified` with a documented `spec_sources` row (`nepaligarage_estimate`); 1 pre-existing official price kept. `starting_price_npr` retained as display cache. → Mark this task [x] once live pages show the badges.
- [ ] P2 Scope discipline and lead-capture validation
  - Not started.

## Current Worktree Notes

Expected local changes at this handoff:

- Modified: `CLAUDE.md`
- Modified: `research/NEPALIGARAGE_PDR.md`
- Modified: `plugin/includes/class-admin.php`
- Modified: `plugin/assets/css/admin.css`
- Modified theme files under `theme/nepaligarage-theme/` for routing, public pages, pricing badges, sourced specs, and copy fixes
- Untracked: `AGENTS.md`
- Untracked: `HANDOFF.md`
- Untracked: `automation/scripts/build-wordpress-zips.sh`
- Untracked: `automation/scripts/verify-public-routes.sh`
- Untracked: `theme/nepaligarage-theme/page.php`
- Untracked public templates: `page-about.php`, `page-brand.php`, `page-comparison-detail.php`, `page-contact.php`, `page-electric-vehicles.php`, `page-news.php`, `page-new-cars.php`, `page-price-estimator.php`, `page-privacy-policy.php`
- Deleted under `plugin-dist/`: old build artifacts appear removed
- Untracked under `plugin/` and `research/archive/`: generated zip/PDF artifacts
- Untracked: `.serena/`

Treat all of these as shared user/agent work. Do not revert without explicit instruction.

## Agent Switching Protocol

Before starting:

1. Read `AGENTS.md`, `CLAUDE.md`, and this file.
2. Run `git status --short`.
3. Inspect only files relevant to the next unchecked task.
4. If `rtk` exists, use it for shell commands. If `rtk` is unavailable, use normal commands with tight output limits.
5. Use Headroom for large command output or long context summaries.

While working:

1. Complete one small task at a time.
2. Prefer fixing broken links by creating honest minimal pages or removing/gating links to unbuilt Phase 4 surfaces.
3. Do not add new platform scope while P0/P1 remains incomplete.
4. Do not hardcode secrets anywhere except `connection.md`, which must stay gitignored.
5. Do not publish claims that are not backed by current Supabase data.

Before ending:

1. Update this file with the exact task status.
2. Run `git status --short`.
3. Record verification commands and results in the final response or in this file if the next agent needs them.
4. Leave the worktree in a coherent state: no half-written files, no unexplained generated artifacts.

## Verification Notes

- `rtk` is unavailable in this shell (`command not found`), so commands were run without it and output was kept narrow.
- Scan passed: `rg -n "home_url\\(\\s*'/(used-cars|garages|parts-finder)/|Official manufacturer|122 spec fields|official sources|All data sourced" theme/nepaligarage-theme` returned no matches.
- PHP syntax validation could not run because `php` is not installed in this shell (`command -v php` returned no path).
- Added deploy-time route verifier: `automation/scripts/verify-public-routes.sh https://nepaligarage.com`.
- Added package helper: `automation/scripts/build-wordpress-zips.sh` writes theme/plugin ZIPs to `/tmp/ng_build`.
- Sub-agent audit fixes applied:
  - Comparison publish readiness now uses slug-only fields to match the shortcode renderer.
  - Removed PHP 8-only `str_contains()` from admin notice handling.
  - Public `/compare/[slug]/` only fetches published comparisons and returns 404 for missing/draft/not-ready rows.
  - Price helper normalizes embedded `source` before reading offsets.
  - Removed the `/cars/*` routing `is_page()` guard so generated brand/model links can render.
  - `/compare/` explicitly returns HTTP 200 when routed through the template.
  - Unknown `/cars/[brand]/` returns 404.
  - Added explicit templates/routes for `/news/`, `/contact/`, and `/privacy-policy/`.
  - Vehicle detail returns 404 when the URL brand slug does not match the model's actual brand.
  - Softened homepage pricing/spec FAQ claims.

## Immediate Next Step

Continue P1.1 data publication and deployment verification:

1. If PHP CLI is available in the next environment, run `php -l` on touched PHP files before deployment.
2. Publish the first comparison from `wp-admin → NepaliGarage → Comparisons` or Supabase only after confirming its variant slug fields match the detail template extraction.
3. Backfill/migrate `prices` rows from denormalized variant prices with honest `unverified` confidence where sources are missing.
4. Package with `automation/scripts/build-wordpress-zips.sh`, deploy the theme/plugin ZIPs from `/tmp/ng_build`, then run `automation/scripts/verify-public-routes.sh https://nepaligarage.com`.
5. Live-verify: `/about/`, `/cars/`, `/cars/byd/`, `/compare/`, `/compare/[first-slug]/`, `/electric-vehicles/`, `/nepal-car-price-estimator/`, `/new-cars/`, header links, footer links, homepage tile links, one vehicle page with sourced specs, and price badges on listing/vehicle pages.
6. Owner will rotate the leaked WordPress app password and Supabase database password once the project is live.
