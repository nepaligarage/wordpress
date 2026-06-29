# NepaliGarage Product/Comparison Course Correction Plan

> For Hermes: stay in documentation/planning mode only. Do not implement from this file until the user explicitly asks.

Goal: turn NepaliGarage from a mostly editorial comparison library into a user-driven vehicle decision workflow centered on product pages, interactive comparison, finance realism, and future parts demand capture.

Architecture:
- Make the vehicle product page the primary decision surface.
- Treat comparison as a user-built workflow, not only a library of prewritten articles.
- Fold finance/EMI into comparison and product decisioning instead of keeping it as a detached generic calculator.
- Reframe parts from a static reference block into the first step of a future demand + supplier marketplace.

Tech surfaces in scope later:
- WordPress theme templates
- existing comparison shortcode/plugin
- Supabase tables and admin flows
- team dashboard `/team/`
- optional crawler/research workflow for official finance schemes

---

## 1. What the user changed

The latest product direction changes the project in these important ways:

1. Comparison should start from product pages.
   - Every vehicle page should support "Add to Compare".
   - Users should be able to add another vehicle from there.
   - Comparison becomes an active shortlist workflow, not just a reading page.

2. The compare hub should stop behaving like an article library first.
   - It should let users discover/select cars by brand, make, and type.
   - Prewritten editorial comparisons can remain, but they are secondary.

3. Finance needs to become real buyer tooling.
   - Comparison should include user-changeable downpayment.
   - EMI should be recalculated from that input.
   - EMI options should come from actual brand-supported schemes where available.
   - Those schemes should be sourced from official brand/dealer pages, with timestamps.

4. The current standalone price estimator likely becomes less central.
   - It may remain for import/on-road estimation.
   - But buyer financing should move into product + comparison flows.

5. Parts are no longer just informative content.
   - Product pages need a real parts tab/section.
   - Users should be able to post a want/request for hard-to-find parts.
   - This is the seed of a future authorized supplier network.

6. Product page UX issues now matter to roadmap, not just polish.
   - Missing compare CTA is a product gap.
   - unreadable Get a Quote button text is a design bug.

---

## 2. Current-state audit from code

### Product page today
File: `theme/nepaligarage-theme/page-vehicle.php`

Current state:
- has key specs strip
- has full spec tabs
- has Book a Test Drive button
- has Get a Quote button
- has videos, known issues, pros/cons, competition, and common parts
- does NOT have Add to Compare
- does NOT have compare state management
- does NOT have finance/downpayment/EMI tooling
- common parts section exists, but not a request/want-list workflow

Important lines:
- CTA area: around lines 268-275
- spec tabs start: around lines 357-400
- common parts section starts: around line 523

### Compare hub today
File: `theme/nepaligarage-theme/page-compare.php`

Current state:
- fetches published rows from `comparisons`
- presents featured + latest comparison pages
- behaves like a comparison library/editorial archive
- does NOT provide a vehicle picker by brand/type
- does NOT support building a comparison from user-selected vehicles

### Comparison engine today
Files:
- `plugin/nepaligarage-core.php`
- `plugin/templates/comparison-table.php`

Current state:
- `[ng_compare]` expects fixed `variant_ids`
- comparison is rendered for two known variants
- no add/remove vehicle workflow
- no compare drawer/cart state
- no finance module inside comparison

### Price estimator today
File: `plugin/includes/class-price-estimator.php`

Current state:
- generic Nepal on-road estimator
- asks for base USD price and vehicle type
- calculates customs duty/VAT/road tax/handling
- does NOT calculate EMI
- does NOT support downpayment
- does NOT pull brand finance offers

### Team login today
Files:
- `theme/nepaligarage-theme/page-team.php`
- `theme/nepaligarage-theme/assets/js/team.js`

Current state:
- `/team/` uses Supabase Auth email/password sign-in
- access is allowed only when `user.app_metadata.team_role` is `admin` or `operator`
- there is no hardcoded team email/password in these files
- this is separate from WordPress admin login

---

## 3. New product decisions to lock before implementation

### Decision A — Primary comparison entry point
Recommended:
- make product pages the main comparison entry point
- add sticky compare state (0/2 selected, 1/2 selected, ready to compare)
- keep `/compare/` as a builder + saved comparisons page
- downgrade article-style comparison listing to a secondary block lower on the page

Reason:
- this matches real buyer behavior better than sending them to a library first

### Decision B — Separate compare tab
Recommended:
- do not remove `/compare/`
- reposition it as the builder workspace and saved comparison destination
- remove the dependency on users finding comparisons only through editorial cards

Reason:
- product-page compare action still needs a destination/workspace

### Decision C — Price estimator role
Recommended split:
- keep current estimator for import/on-road estimate use cases
- add a separate finance layer for shortlist buyers:
  - vehicle price
  - user-entered downpayment
  - tenure options
  - bank/brand scheme selection
  - EMI output

Reason:
- import estimation and consumer finance are different jobs

### Decision D — Parts MVP
Recommended:
- keep existing common-parts info block
- add a new Parts Request / Want List workflow as the first transactional parts feature
- defer full supplier marketplace until provider onboarding is ready

Reason:
- captures intent immediately without pretending supplier coverage already exists

---

## 4. Proposed revised information architecture

### Product page becomes the decision hub
Sections in priority order:
1. hero + price + quote/test-drive + add-to-compare
2. key specs
3. finance snapshot (price, downpayment, EMI preview)
4. full specifications
5. pros/cons
6. known issues
7. videos
8. compare alternatives / selected compare state
9. parts
10. competition / similar cars

### Compare experience becomes a builder
Main functions:
- choose car A / car B
- filter by brand
- filter by body type
- search by model/variant
- auto-carry selected vehicles from product page
- edit downpayment/tenure in comparison view
- show EMI comparison next to price/specs
- keep editorial verdicts only where they are truly available

### Parts becomes a 2-layer system
Layer 1: research/reference
- common parts
- notes
- source links
- compatibility later

Layer 2: demand capture
- request a part for this model
- specify part name, year/variant, urgency, city, contact
- future routing to authorized suppliers

---

## 5. Data model changes implied by the new direction

### Comparison/session state
Need later:
- client-side compare list (localStorage or URL state)
- optional saved comparison records if persistence is needed

### Finance programs
Need later new table(s), likely:
- `brand_finance_programs`
  - id
  - brand_id
  - partner_name
  - program_name
  - interest_type
  - interest_rate
  - min_downpayment_percent
  - max_tenure_months
  - processing_fee_npr
  - insurance_notes
  - source_url
  - source_label
  - verified_at
  - is_active
- optional child table for exact tenure slabs if brands publish discrete options
  - `brand_finance_program_terms`

Important rule:
- official source URL and verification date must be mandatory for non-manual schemes

### Parts request / want list
Need later new table:
- `part_requests`
  - id
  - model_id
  - variant_id nullable
  - part_name
  - part_category
  - vehicle_year_text
  - notes
  - requester_name
  - requester_phone
  - requester_city
  - urgency
  - status
  - created_at

Later supplier side tables can be added after partner onboarding.

---

## 6. Documentation-first backlog (do this before coding)

### Doc 1 — Product Page Decision Hub Spec
Define:
- exact hero CTA order
- compare button behavior
- compare state messaging
- finance preview block
- parts tab/request UX
- mobile behavior

### Doc 2 — Comparison Builder Functional Spec
Define:
- user flow from product page to compare page
- compare drawer/cart behavior
- builder filters by brand/make/type
- fallback behavior when only one car is selected
- how editorial comparisons coexist with custom comparisons

### Doc 3 — Finance Data & Sourcing Spec
Define:
- official finance source hierarchy
- manual override rules
- how EMI is calculated
- which inputs are user editable
- what happens when a brand publishes no official finance scheme

### Doc 4 — Parts Request MVP Spec
Define:
- product-page parts tab structure
- request form fields
- moderation rules
- supplier-ready future state
- what is public vs internal

### Doc 5 — Team Workflow Update Spec
Define:
- whether `/team/` manages finance programs
- whether `/team/` manages part requests
- who reviews official finance sources
- who handles supplier outreach later

---

## 7. Recommended implementation order after documentation approval

### Phase A — UX correctness and buyer flow
1. fix Get a Quote button contrast issue
2. add Add to Compare on product page
3. add compare state/drawer
4. route selected cars into `/compare/`

### Phase B — Replace compare hub architecture
1. convert `/compare/` from article-first to builder-first
2. add filters by brand and type
3. add searchable vehicle picker
4. keep saved/editorial comparisons as a secondary section

### Phase C — Finance realism
1. create finance program schema
2. add admin/team management UI
3. add downpayment + tenure controls in compare/product pages
4. calculate EMI from selected scheme
5. display source + verification date

### Phase D — Parts demand capture
1. upgrade product-page parts area into real tab/section
2. add request/want-list form
3. store requests in Supabase
4. add team/admin review surface

---

## 8. Team login answer to preserve operational clarity

Important distinction:
- `/team/` is Supabase-authenticated, not standard WordPress-login authenticated
- team access depends on a Supabase user account having `app_metadata.team_role = admin|operator`
- WordPress admin credentials are a separate system

Operational recommendation:
- during development, use a dedicated Supabase team user for `/team/`
- after project completion, rotate both:
  - WordPress admin credentials
  - any development/team Supabase credentials

Do not continue using shared or long-lived development passwords.

---

## 9. Immediate conclusions

This is not a minor feature request. It changes product emphasis in four ways:
- from static comparison content to interactive comparison workflow
- from generic estimator to real finance-aware decision tooling
- from passive parts content to demand capture for future marketplace supply
- from compare-page-first discovery to product-page-first buyer journey

That means the roadmap should now be:
1. freeze documentation/specs for the new buyer journey
2. update data models/specs
3. implement compare workflow
4. implement finance sourcing + EMI logic
5. implement parts request MVP

Not the old sequence of only polishing editorial comparison pages.

---

## 10. Open questions for the next documentation pass

These should be answered in the next spec, not in ad hoc code:
1. Should compare allow exactly 2 cars at launch, or up to 3?
2. Should finance comparison live only on `/compare/`, or also as a mini widget on product pages?
3. When no official finance scheme exists, should we show a generic EMI calculator or hide EMI entirely?
4. Should parts requests be public on the site, private lead capture, or both?
5. Should supplier responses later happen by dashboard, email, WhatsApp, or admin mediation?
6. Do we want `/compare/` URL state that can be shared, e.g. `/compare/?a=byd-atto-3&b=hyundai-creta-ev`?

---

## 11. Recommended next paperwork deliverable

Next document to produce:
- `docs/plans/2026-06-14-comparison-builder-spec.md`

That spec should be the implementation source of truth for:
- Add to Compare on product pages
- compare drawer behavior
- compare builder page layout
- finance controls
- relationship with existing editorial comparisons
