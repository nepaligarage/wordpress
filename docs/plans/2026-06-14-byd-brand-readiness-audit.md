# BYD brand readiness audit — NepaliGarage

Date: 2026-06-14
Status: working audit for making NepaliGarage production-ready for one brand first
Target brand: BYD Nepal
Primary official source: https://cimex.com.np/

## Objective
Make NepaliGarage truly usable for one brand by ensuring BYD product pages are backed by complete, trustworthy data — not just layout.

This audit focuses on the actual product-page data contract already present in the theme and team dashboard.

## What the current product page expects from Supabase
The BYD product page currently expects the following structured data to exist:

1. Core catalog
- `brands`
- `models`
- `variants`
- `prices`
- `spec_fields`
- `variant_specs`

2. Product content layer
- `models.brochure_url`
- `vehicle_images`
- `vehicle_pros_cons`
- `vehicle_issues`
- `vehicle_competition`
- `vehicle_videos`
- `vehicle_parts`

3. Dealer context
- `showrooms`

If these are incomplete or missing, the page renders but does not feel authoritative.

## Hard database facts captured during audit
Current public-table counts:
- brands: 22
- models: 91
- variants: 160
- prices: 71
- variant_specs: 1119
- vehicle_images: 0
- vehicle_issues: 0
- vehicle_pros_cons: 0
- showrooms: 0

Missing public tables that the code already expects:
- `vehicle_videos`
- `vehicle_parts`
- `vehicle_competition`

Existing content tables and current coverage:
- `vehicle_images` exists but has 0 rows
- `vehicle_issues` exists but has 0 rows
- `vehicle_pros_cons` exists but has 0 rows

Conclusion:
The BYD product-page problem is primarily a data completeness problem, not a frontend-only problem.

## BYD catalog currently in Supabase
Current BYD models in `models`:
- Atto 1
- Atto 2
- Atto 3
- Seal
- Sealion 7

Current DB slugs:
- `byd-atto-1`
- `byd-atto-2`
- `byd-atto-3`
- `byd-seal`
- `byd-sealion-7`

## Important slug consistency note
The data currently uses brand-prefixed model slugs like `byd-atto-2`.
Some page logic was originally written assuming shorter slugs like `atto-2`.

Decision:
Use the Supabase convention as the canonical one for BYD:
- `byd-atto-1`
- `byd-atto-2`
- `byd-atto-3`
- `byd-seal`
- `byd-sealion-7`

Do not mix brand-prefixed and non-prefixed model slugs inside the same brand.

## Official BYD Nepal source footprint found during audit
Official sitemap and bundle evidence from Cimex shows these BYD source surfaces:
- `https://cimex.com.np/vehicles/byd-atto-3`
- `https://cimex.com.np/vehicles/byd-dolphin`
- `https://cimex.com.np/price-list`
- route evidence in JS bundles for:
  - `/atto1`
  - `/atto2`
  - `/atto3`
  - `/byd-seal`
  - `/byd-dolphin`
  - `/byd-m6`

Implication:
The official source footprint includes at least these BYD product surfaces:
- Atto 1
- Atto 2
- Atto 3
- Seal
- Dolphin
- M6

But NepaliGarage currently has:
- Atto 1
- Atto 2
- Atto 3
- Seal
- Sealion 7

This means BYD brand readiness is currently mismatched against the official source set.

## Recommendation on brand scope
To make BYD truly usable first, NepaliGarage should treat this as a 2-phase brand rollout.

### Phase A — ship one flagship model completely
Use BYD Atto 2 as the proof model.
It should become the quality benchmark for every future BYD model page.

### Phase B — normalize the rest of the BYD lineup
Bring the full BYD set into alignment with official Nepal availability and content depth.

## Atto 2 current state in Supabase
Atto 2 currently has 2 actual variants:
- `byd-atto-2-long-range`
- `byd-atto-2-standard-range`

Observed state:
- Long Range: no price rows, no spec rows, no image URL, no variant starting price
- Standard Range: 1 price row, 16 spec rows, no rich content tables populated

This means the current Atto 2 record is not yet product-grade.

## Official Atto 2 evidence captured from the official bundle
Extracted from the official Atto 2 bundle:
- WLTP range: 345 km
- battery capacity: 51.13 kWh
- torque: 290 Nm
- power: 100 kW
- ground clearance: 200 mm
- airbags: 6

Also captured official media paths for Atto 2, including:
- `/atto2/main.jpg`
- `/atto2/ext10.jpg`
- `/atto2/interior1.jpg`
- `/atto2/interior3.jpg`
- `/atto2/interior4.jpg`
- `/atto2/interior5.jpg`
- `/atto2/interior6.jpg`
- `/atto2/kv1.jpg`
- `/atto2/kv2.jpg`
- `/atto2/kv3.jpg`
- `/atto2/kv4.jpg`
- `/atto2/saf1.png`
- `/atto2/saf2.png`
- `/atto2/saf3.png`
- `/atto2/tec1.png`
- `/atto2/tec2.webp`
- `/atto2/tec3.webp`
- plus color/exterior assets such as `atto2black.png`, `atto2green.png`, `atto2grey.png`, `atto2silver.png`

This is enough evidence to begin a clean official-source gallery and core spec backfill for Atto 2.

## BYD data readiness matrix

### 1. Brand layer
Need for BYD:
- brand logo URL verified
- BYD showroom contact(s) in Nepal
- official source link(s) recorded

Current state:
- brand exists
- showroom data is empty

### 2. Model layer
Need for every BYD model:
- clean canonical slug
- body type
- EV boolean
- brochure URL
- content ownership status

Current state:
- models exist
- brochure URLs appear empty in current BYD rows
- content ownership is not yet operationalized

### 3. Variant layer
Need for every BYD variant:
- variant image
- official Nepal availability
- price row
- source-backed specs
- descriptive body text

Current state:
- only Atto 2 Standard Range has meaningful depth
- most other BYD model variants appear underfilled

### 4. Rich product content layer
Need per model:
- 8–12 gallery images
- 2–4 verified videos
- 5–8 pros/cons notes
- 3–6 known issues or ownership watch-outs
- 4–8 parts/common consumables
- 3–4 competitor links
- brochure URL

Current state:
- not populated
- some required tables do not exist yet

## Minimum viable BYD rollout standard
A BYD model should not be called production-ready unless it has:

### Required
- official brochure URL
- hero image
- minimum 6 gallery images
- at least 1 valid price row
- at least 12 useful spec rows
- source/confidence on all displayed specs
- at least 3 pros
- at least 3 cons
- at least 2 verified review videos
- at least 2 competitor links
- at least 1 showroom entry for BYD Nepal

### Strongly recommended
- known issues section populated honestly
- parts availability notes
- service-center context for Nepal
- finance source note and verified baseline

## Practical rollout order for BYD

### Step 1 — complete Atto 2 fully
Backfill:
- brochure URL
- gallery images
- verified videos
- pros/cons
- known issues
- competitors
- showroom record
- variant price/source cleanup
- Long Range variant completion

### Step 2 — fix lineup parity against official BYD Nepal footprint
Review whether NepaliGarage should add:
- Dolphin
- M6

And review whether `Sealion 7` is officially live and should remain in the initial BYD launch set.

### Step 3 — complete the next 2 BYD models
Suggested order:
1. Atto 3
2. Seal

## Exact blockers preventing BYD from being "ready"
1. Rich content tables are partly missing
2. Existing rich content tables have zero rows
3. BYD model coverage does not fully match the official source footprint
4. Variant completeness is uneven across BYD
5. Showroom data is empty
6. Slug consistency needs to be enforced for brand-wide reliability

## Recommended immediate implementation decisions
1. Canonicalize BYD model slugs to the current Supabase convention
2. Create the missing rich-content tables now
3. Treat Atto 2 as the first fully-complete BYD benchmark page
4. Backfill only official or explicitly-labeled estimated data
5. Do not expand to other brands until BYD meets the minimum standard above

## Concrete next data tasks
1. Create `vehicle_videos`
2. Create `vehicle_parts`
3. Create `vehicle_competition`
4. Insert BYD showroom row(s)
5. Backfill Atto 2 gallery from official asset set
6. Backfill Atto 2 official core specs from official source evidence
7. Add Atto 2 brochure URL
8. Add Atto 2 verified video records
9. Add Atto 2 pros/cons with source references
10. Add Atto 2 issue/watch-out records with honest sourcing
11. Add Atto 2 competitor rows
12. Audit whether Dolphin and M6 should be added before calling BYD complete

## Final recommendation
Do not define "BYD ready" as "all BYD pages exist".
Define it as:
- one complete benchmark model (Atto 2)
- missing content schema created
- official-source-aligned BYD lineup reviewed
- at least 3 BYD product pages meeting the minimum viable standard

That is the threshold where NepaliGarage starts to feel like a trustworthy Nepal-first BYD research destination rather than a partially-seeded catalog.
