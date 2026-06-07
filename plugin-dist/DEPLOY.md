# Plugin Deployment Instructions

## One-time manual step required

The NepaliGarage Core plugin must be uploaded via WordPress admin (REST API only supports wordpress.org plugins).

### Step 1 — Upload plugin
1. Log in to https://nepaligarage.com/wp-admin
2. Go to: Plugins → Add New → Upload Plugin
3. Upload: `nepaligarage-core.zip` (in this folder)
4. Click Install Now → Activate Plugin

### Step 2 — Get Supabase Anon Key
1. Log in to https://supabase.com/dashboard/project/eukghqvlvlveshchnugk/settings/api
2. Copy the "Project API Keys → anon / public" key

### Step 3 — Configure plugin
1. In WordPress admin go to: Settings → NepaliGarage
2. Supabase URL: https://eukghqvlvlveshchnugk.supabase.co (pre-filled)
3. Supabase Anon Key: paste the key from Step 2
4. USD/NPR Rate: 137 (update to current rate)
5. EV Duty Rate: 0.40 (40% — update if policy changes)
6. Click Save Settings

### Step 4 — Publish comparison page
Once plugin is active and configured:
1. Go to Pages → BYD Atto 2 vs Toyota Urban Cruiser Ebella (Draft)
2. Review the shortcode is in place: [ng_compare variant_ids="byd-atto-2-standard-range,toyota-urban-cruiser-ebella-61kwh"]
3. Note: The comparison table will show "Data temporarily unavailable" until spec data is added to Supabase
4. Click Publish when ready

### WordPress pages created (ready)
- /compare/                              ← Published (parent page)
- /compare/byd-atto-2-vs-toyota-...      ← Draft (awaiting plugin + spec data)
- /nepal-car-price-estimator/            ← Draft (awaiting plugin)

### Supabase data status
- Schema: ✅ All 10 Phase 1 tables live
- Brands: ✅ BYD, Toyota
- Models: ✅ BYD Atto 2, Toyota Urban Cruiser Ebella
- Variants: ✅ 3 variants seeded
- Spec data: ⏳ Empty — needs real verified spec values
- Prices: ⏳ Empty — needs official/estimated NPR prices

### Next: Add spec data
Bring verified spec data from official sources to Claude Code and it will populate variant_specs and prices via psql.
