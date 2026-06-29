# Product Page Decision Hub Spec

Source of truth for the vehicle product page (`theme/nepaligarage-theme/page-vehicle.php`) as the primary buyer decision surface. URL pattern: `/cars/[brand]/[model]/`.

This spec describes the target state. Several hooks already exist in the template (`#ng-compare-btn`, `#ng-compare-note`, `#ng-vehicle-finance`, `.ng-vehicle-parts`); this spec aligns the rest of the page to them.

---

## 1. Section order

Render top to bottom in this exact priority order. Sections with no data collapse (render nothing) rather than showing an empty shell, except where a fallback is specified.

| # | Section | Anchor / class | Data source | Empty behavior |
|---|---------|----------------|-------------|----------------|
| 1 | Hero + price + CTAs + Add to Compare | `.ng-vehicle-hero` | `variants` row 0 + price | Always renders (price falls back to "Price on request") |
| 2 | Variant tabs | `.ng-vehicle-variants` | `variants` (>1) | Hidden when single variant |
| 3 | Key specs strip | `.ng-vehicle-keyspecs` | `variant_specs` key map | Hidden when no mapped keys |
| 4 | Finance snapshot | `#ng-vehicle-finance` | `ngt_vehicle_finance_programs()` | See §4 fallback |
| 5 | Gallery | `.ng-vehicle-gallery` | `vehicle_images` | Hidden when none |
| 6 | Full specifications | `.ng-vehicle-specs-tabbed` | grouped `variant_specs` | Hidden when none |
| 7 | Pros & cons | `.ng-vehicle-proscons` | `vehicle_pros_cons` | Renders with "research in progress" copy |
| 8 | Known issues | `.ng-vehicle-issues` | `vehicle_issues` | Renders with "no known issues" copy |
| 9 | Videos | `.ng-vehicle-videos` | `vehicle_videos` | Hidden when none |
| 10 | Compare alternatives | `.ng-vehicle-compare-cta` | compare state (JS) | Always renders (see §2) |
| 11 | Parts (reference + request) | `.ng-vehicle-parts` | `vehicle_parts` + request form | Reference hidden when none; request CTA always renders |
| 12 | Competition / similar cars | `.ng-vehicle-competition` | `vehicle_competition` | Hidden when none |

Note: the live template currently renders finance (4) before gallery (5) and places parts (11) before competition (12) — both already match this order. The new work is sections 10 (compare alternatives block) and the request half of 11.

---

## 2. Add to Compare button

The button already exists at `page-vehicle.php` hero actions (`#ng-compare-btn`). This spec defines its full behavior. Compare state is client-side, stored in `localStorage` under key `ng_compare` as an array of `{slug, brand_slug, model_slug, name}` objects (max 2 — see [[comparison-builder-spec]]).

### Placement
- Primary instance: hero actions row, after "Get a Quote" (current position is correct).
- Secondary instance: section 10 "Compare alternatives" block, as a full-width button on mobile.

### States
The button label and the live note (`#ng-compare-note`, `aria-live="polite"`) update from compare-list length:

| List state | Button label | Button style | `#ng-compare-note` text |
|------------|--------------|--------------|-------------------------|
| This car not in list, 0 others | `Add to Compare` | outline-white | (empty) |
| This car not in list, 1 other present | `Add to Compare` | outline-white | `1 car selected — add this to compare` |
| This car in list, 1/2 | `Added ✓ — pick one more` | solid red | `Added. Choose a second car to compare.` |
| This car in list, 2/2 ready | `Compare now →` | solid red | `2 cars ready. Compare now.` (links to `/compare/?a=…&b=…`) |
| List full (2), this car not in it | `Compare list full` (disabled) | outline muted | `Remove a car to add this one.` with inline "Clear list" link |

- Clicking when state is "2/2 ready" navigates to the compare builder with both slugs in URL (`/compare/?a=<slug>&b=<slug>`).
- Clicking "Add" toggles membership; clicking again removes this car.
- `data-compare-url` (legacy editorial fallback) is used only when JS compare state is empty AND a hardcoded editorial comparison exists for this model; otherwise ignored.

### Mobile behavior
- On viewports < 768px the hero actions stack vertically full-width in this order: Book a Test Drive, Get a Quote, Add to Compare, Download Brochure.
- A sticky bottom compare bar appears once ≥1 car is in the list (shared component, see [[comparison-builder-spec]] §3). It shows `1/2` or `2/2` and a "Compare" button. It does not duplicate the hero button; it is the global drawer.

---

## 3. Get a Quote button — contrast fix

**Bug:** `#ng-quote-btn` uses `ng-btn--outline-white`, which renders white text on the light hero background in some brand themes (e.g. BYD hero with light imagery), making the label unreadable.

**Fix:** introduce a dedicated quote-button modifier that guarantees contrast regardless of hero background.

- Add class `ng-btn--quote` to `#ng-quote-btn` alongside the existing button base. Replace `ng-btn--outline-white` with `ng-btn--quote`.
- CSS spec for `.ng-btn--quote` (theme stylesheet):
  - background: `transparent`
  - border: `2px solid #ffffff`
  - color: `#ffffff`
  - text-shadow: `0 1px 2px rgba(0,0,0,0.45)` (guarantees legibility over light photos)
  - on hover/focus: background `#ffffff`, color `#0a0a0a`
- Acceptance: label remains ≥ 4.5:1 contrast against the hero photo at all brand variants. Verify visually on BYD Atto 2 hero (known light background) and a dark-hero model.

Do not change `ng-btn--outline-white` globally — other surfaces rely on it.

---

## 4. Finance preview block

Lives at section 4 (`#ng-vehicle-finance`). Full data + formula rules are in [[finance-data-sourcing]]. Product-page responsibilities only:

### When an official scheme exists (`$has_finance` true)
Current template already renders: scheme selector, editable down payment (NPR, pre-filled to `min_downpayment_percent`), tenure dropdown (`supported_years`), read-only interest rate, and a results panel (vehicle price, down payment, monthly EMI, loan amount, total repayment, total interest). Keep this. Required additions:

- Source row must always show `source_name` + dated link. Append the verification date in human form: `Verified <verified_at, d M Y>`.
- The "Get finance quote for this setup" button (`#ng-finance-quote-btn`) opens the enquiry modal pre-filled with `lead_type = finance_quote`, carrying current down payment, tenure, and computed EMI as the message body.

### Fallback when no official scheme (`$has_finance` false)
Do not hide finance. Render a generic calculator variant of the block:

- Heading: `EMI estimate (indicative)`.
- Inputs: vehicle price (pre-filled, read-only), down payment (editable, default 20%), tenure dropdown (1–7 years), interest rate (editable input, default 12% p.a., labeled "Typical Nepal auto-loan rate — adjust to your bank's offer").
- A clear disclaimer chip: `No official brand finance scheme on file — this is a generic estimate, not a quoted offer.`
- Same reducing-balance EMI math as the official path.

---

## 5. Parts section

Lives at section 11 (`.ng-vehicle-parts`). Two layers; full request spec in [[parts-request-mvp]].

### Layer 1 — reference (existing)
Keep the current common-parts grid (`vehicle_parts`, verified rows). When empty, hide the grid but still render Layer 2.

### Layer 2 — parts request CTA (new)
- Always render below the reference grid, even when no reference parts exist.
- Heading: `Looking for a specific part?`
- Copy: `Tell us the part you need for your <brand> <model> and we'll try to source it through our network.`
- Button: `Request a part` (`.ng-part-request-btn`, solid red) opens the parts request modal (form fields and validation per [[parts-request-mvp]] §3).
- The modal posts to the `part_requests` table via the same AJAX/REST path used by leads. Requests are private lead capture — never listed publicly on the page.

---

## 6. Compare alternatives block (section 10)

A new section that doubles as the second decision nudge:

- If `vehicle_competition` rows exist, reuse them as suggested compare targets: each competitor card gets an "Add to compare" affordance that pushes that competitor's slug into the compare list.
- Heading: `Compare this with alternatives`.
- Render the live compare-state summary here too (mirrors `#ng-compare-note`), plus a "Go to comparison builder" link to `/compare/` carrying current selection.
- When the compare list already holds this car + one alternative, show "Compare now →" as the primary action.

---

## 7. Mobile-first rules (all sections)

- Single-column stacking below 768px; two-column grids (pros/cons, finance layout) collapse to one column.
- Spec tabs and variant tabs become horizontally scrollable chip rows (no wrapping), with momentum scroll.
- Sticky compare bar is the only fixed element; it must not overlap the footer cookie bar — give it `bottom` offset above the cookie consent strip when consent is unset.
- Tap targets ≥ 44px height for all CTAs and tabs.
- Finance number inputs use `inputmode="numeric"` and large tap-friendly steppers.

---

## 8. Acceptance checklist

- Sections render in the §1 order; empty sections behave per the table.
- Add to Compare cycles through all §2 states and persists across page loads via `localStorage`.
- "Get a Quote" label is readable on light and dark hero backgrounds (§3).
- Finance shows official block when a scheme exists, generic estimate otherwise — never hidden (§4).
- Parts request CTA always present; submissions land in `part_requests` and never display publicly (§5).
- Full page is single-column and usable at 360px width.
