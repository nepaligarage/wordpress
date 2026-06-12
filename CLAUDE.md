# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

---

## Project

**nepaligarage.com** — Nepal's automotive research and ownership platform. Live at https://nepaligarage.com.

Credentials, connection strings, and API keys are in `connection.md` (never commit this file). The Supabase project ID is `eukghqvlvlveshchnugk`.

---

## Deployments

There is no build step. Changes are deployed by zipping the relevant folder and uploading via WordPress admin.

**Theme changes:**
```bash
cd theme && zip -r nepaligarage-theme.zip nepaligarage-theme/ -x "*.DS_Store"
# Upload: Appearance → Themes → Add New → Upload Theme → Replace active
```

**Plugin changes:**
```bash
mkdir -p /tmp/ng_build/nepaligarage-core
cp -r plugin/assets plugin/includes plugin/templates plugin/nepaligarage-core.php /tmp/ng_build/nepaligarage-core/
cd /tmp/ng_build && zip -r nepaligarage-core.zip nepaligarage-core/ -x "*.DS_Store"
# Upload: Plugins → Add New → Upload Plugin → Replace active
# CRITICAL: zip must have nepaligarage-core/ as the top-level folder or WordPress will install as a separate plugin
```

**Verify WordPress REST API** (credentials in `connection.md` — never hardcode them here):
```bash
curl -sk -u "admin:<app password from connection.md>" https://nepaligarage.com/wp-json/wp/v2/users/me
```

**Verify Supabase** (connection string in `connection.md`):
```bash
psql "<direct connection string from connection.md>" -c "SELECT count(*) FROM brands;"
```

**Supabase MCP** is available — use `mcp__claude_ai_Supabase__execute_sql` for schema changes and data operations instead of manual SQL when possible.

---

## Architecture

### Data flow

```
Browser ──► WordPress PHP ──► ngt_supabase_get() ──► Supabase REST API (server-side)
                                                        (anon key, cached via WP transients)

Browser ──► Supabase JS SDK ──► Supabase Auth + RLS-protected tables
                                (anon key safe in browser; all user data behind RLS)

WordPress admin ──► NG_Admin / NG_Content_Automator ──► Supabase REST
                                                          (service role key, never in browser)
```

**WordPress is rendering engine + SEO layer. Supabase is the database. Never store structured vehicle data or user records in WordPress.**

### WordPress theme (`theme/nepaligarage-theme/`)

Custom theme — no parent theme dependency.

- `functions.php` — asset enqueuing, the `ngt_supabase_get()` helper, vehicle template router
- `front-page.php` — homepage: featured vehicles + brand marquee + comparisons (all data from Supabase)
- `page-vehicle.php` — auto-loaded for any `/cars/[brand]/[model]/` URL via `template_include` filter (no manual template assignment)
- `page-dashboard.php` — user dashboard (Supabase Auth + JS SDK)
- `header.php` / `footer.php` — nav, cookie bar, `wp_footer()`

**Key theme pattern — Supabase fetch:**
```php
$brands = ngt_supabase_get('brands', ['select' => 'id,name,slug,logo_url', 'order' => 'name.asc']);
```
Cache key prefix is `ngt2_`. Empty results are never cached (means bad key or cold data).

**Vehicle template routing** (`functions.php`): the `template_include` filter intercepts any 3-segment `/cars/[brand]/[model]/` URL and loads `page-vehicle.php`. WordPress pages for these URLs must exist; the template is loaded automatically without setting `_wp_page_template` meta.

### WordPress plugin (`plugin/`)

`nepaligarage-core.php` bootstraps on `plugins_loaded` → calls `ng_load_includes()`.

| Class | Purpose |
|-------|---------|
| `NG_Supabase` | Server-side Supabase client using anon key; used by comparison shortcode |
| `NG_Admin` | wp-admin dashboard: Leads, Orders, Vehicles, Accessories, Settings sub-pages |
| `NG_Content_Automator` | Tracked brands config + "Content Automation" admin page for the daily article pipeline |
| `NG_Comparison_Renderer` | Renders HTML comparison tables from variant data |
| `NG_Price_Estimator` | Nepal customs duty calculator (`[ng_price_estimator]` shortcode) |

**Admin reads data using the service role key** (`ng_supabase_service_key` WP option) — never the anon key. The anon key (`ng_supabase_key`) is only passed to the browser via `wp_localize_script` as `ngConfig.supabaseKey`.

**Custom WordPress roles:** `ng_operator` (content + leads + orders), `ng_dealer_partner` (leads + orders read-only). Custom capabilities: `ng_view_leads`, `ng_view_orders`, `ng_edit_vehicles`, `ng_manage_accessories`.

### Supabase schema

Key tables: `brands`, `models`, `variants`, `spec_fields`, `variant_specs`, `prices`, `accessories`, `vehicle_accessories`, `leads`, `orders`, `partners`.

PostgREST nested join syntax for vehicle detail queries:
```
GET /rest/v1/variants?select=*,models!inner(slug,name,brands(name,logo_url))&models.slug=eq.fortuner&is_available_nepal=eq.true
```

All application tables are in the `public` schema with RLS enabled.

### Frontend JS (`theme/nepaligarage-theme/assets/js/`)

| File | Purpose |
|------|---------|
| `nav.js` | Mobile menu, user dropdown, FAQ accordion, cookie consent (localStorage `ng_cookie_consent`) |
| `auth.js` | Supabase Auth: login/logout modal, session management, `ngConfig` global |
| `dashboard.js` | User dashboard: vehicle logs, documents, reminders via Supabase JS SDK |
| `enquiry.js` | Vehicle page: variant tab switching, enquiry modal → `leads` table, accessory orders → `orders` table |

`ngConfig` (injected via `wp_localize_script` on `ng-auth`): `supabaseUrl`, `supabaseKey`, `siteUrl`, `dashboardUrl`, `ajaxUrl`, `nonce`.

---

## Daily content automation

The `/ng-brand-news` skill (`~/.claude/skills/ng-brand-news/SKILL.md`) is the repeatable pipeline:

1. WebSearch brand + Nepal keywords (3 searches)
2. WebFetch top 3 source URLs for fact-checking
3. Cross-reference facts across ≥2 sources — omit single-source claims
4. Write 700–900 word article in defined structure
5. POST to WordPress REST API as `draft` with "Brand News" category

Run manually: `/ng-brand-news BYD` (or any brand name).
Tracked brands are managed at `wp-admin → NepaliGarage → Content Automation`.

---

## Hard rules (from `guardrails/product-guardrails.md`)

- Every displayed data point needs a source URL + confidence level (`official` | `estimated` | `unverified`)
- AI-written articles must contain Nepal-specific data — never publish global-only content
- All spec data lives in Supabase — never duplicate into WordPress post meta
- User authentication for `/dashboard` uses Supabase Auth, not WordPress sessions
- Service role key stays server-side only; anon key may go to browser (all data is behind RLS)
- Article drafts require human review before publish — the automation always posts as `draft`
