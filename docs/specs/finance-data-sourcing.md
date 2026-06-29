# Finance Data & Sourcing Spec

Source of truth for the EMI / brand-finance system used by the product page finance snapshot ([[product-page-decision-hub]] §4) and the comparison builder EMI cards ([[comparison-builder-spec]] §4). The existing `ngt_vehicle_finance_programs()` helper and `#ng-vehicle-finance` block are the integration points.

Hard rule from `guardrails/product-guardrails.md`: every displayed data point needs a source URL + confidence level. Finance programs are no exception — a non-manual scheme without a source URL must not go live.

---

## 1. Schema — `brand_finance_programs`

Supabase `public` schema, RLS enabled. One row per published finance program for a brand (and optionally a specific model).

| Column | Type | Notes |
|--------|------|-------|
| `id` | uuid PK | `gen_random_uuid()` |
| `brand_id` | uuid FK → `brands.id` | required |
| `model_id` | uuid FK → `models.id` nullable | null = applies to whole brand |
| `partner_name` | text | bank / finance partner, e.g. "NMB Bank" |
| `program_name` | text | e.g. "BYD Nepal EV Loan" |
| `interest_type` | text enum | `reducing` \| `flat` (drives EMI math, §3) |
| `interest_rate` | numeric(5,2) | annual % p.a. |
| `min_downpayment_percent` | numeric(5,2) | lowest official down payment % |
| `max_tenure_months` | int | upper bound for tenure |
| `processing_fee_npr` | numeric(12,2) nullable | one-time fee, shown as a note |
| `insurance_notes` | text nullable | free-text caveats (comprehensive insurance required, etc.) |
| `source_url` | text | **mandatory unless `interest_type`/data is manual estimate** (see §5) |
| `source_label` | text | display name of the source, e.g. "BYD Nepal official finance page" |
| `verified_at` | timestamptz | when a human last confirmed the figures |
| `is_active` | boolean default true | inactive programs never render |
| `created_at` | timestamptz default now() | |
| `updated_at` | timestamptz default now() | |

Constraints:
- `interest_type in ('reducing','flat')`.
- DB-level check or admin-level validation: if the program is not flagged manual, `source_url` and `verified_at` must be non-null.
- Index on `(brand_id, is_active)` and `(model_id)`.

RLS: public read of `is_active = true` rows via anon key; insert/update only via service role (admin/team), per the existing data-flow rules in `CLAUDE.md`.

---

## 2. Schema — `brand_finance_program_terms` (optional child)

Use only when a brand publishes discrete tenure slabs with different rates (otherwise rely on the parent row's single rate + `max_tenure_months`).

| Column | Type | Notes |
|--------|------|-------|
| `id` | uuid PK | |
| `program_id` | uuid FK → `brand_finance_programs.id` | cascade delete |
| `tenure_months` | int | e.g. 12, 24, 36, 60 |
| `interest_rate` | numeric(5,2) nullable | overrides parent rate for this slab if set |
| `min_downpayment_percent` | numeric(5,2) nullable | overrides parent if set |
| `display_order` | int default 0 | |

When child terms exist, the tenure dropdown is populated from them and the rate switches per selected slab. When absent, tenure options are derived from `max_tenure_months` (yearly steps up to the max) at a single rate.

---

## 3. EMI calculation

### Reducing balance (default, `interest_type = 'reducing'`)
Standard amortized monthly payment:

```
P = principal = vehicle_price - down_payment
r = monthly rate = (annual_rate / 100) / 12
n = tenure in months
EMI = P * r * (1 + r)^n / ((1 + r)^n - 1)     (when r > 0)
EMI = P / n                                    (when r = 0)
```

Derived outputs:
- `total_repayment = EMI * n`
- `total_interest = total_repayment - P`
- `loan_amount = P`

### Flat rate (`interest_type = 'flat'`)
```
total_interest = P * (annual_rate / 100) * (n / 12)
EMI = (P + total_interest) / n
```
Label flat-rate EMIs with a note: "Flat-rate scheme — effective cost is higher than the headline rate."

Rounding: display EMI and totals as whole NPR (`number_format`, no decimals). Compute in float, round only at display.

`processing_fee_npr` is shown as a separate one-time note, never folded into EMI.

---

## 4. User inputs

Shown in the finance block (product page) and the shared controls (compare builder):

| Input | Control | Default | Editable |
|-------|---------|---------|----------|
| Vehicle price | read-only display | active variant price (`$active_amount`) | no |
| Down payment | number input (NPR) + % helper; slider on mobile | `price * min_downpayment_percent / 100` | yes; min = `min_downpayment_percent`, max = 90% |
| Tenure | dropdown | program default / lowest slab | yes; bounded by `max_tenure_months` or child terms |
| Scheme selector | dropdown of active schemes for the brand/model | first active program (lowest down payment) | yes |
| Interest rate | read-only (official) / editable (generic fallback only) | program rate | only in generic mode |

Down-payment validation: if a user enters below `min_downpayment_percent`, clamp up and show "Minimum official down payment is X%."

---

## 5. Source display & data-sourcing hierarchy

### Display (mandatory for official schemes)
Every official EMI block shows: `program_name` · `partner_name` · `source_label` linking to `source_url` (opens new tab, `rel="noopener nofollow"`) · `Verified <verified_at, d M Y>`.

### Sourcing hierarchy (preference order when researching a scheme)
1. Official brand finance page (e.g. BYD Nepal / Cimex official finance section).
2. Authorized importer / dealer page.
3. Partner bank's published auto-loan page.
4. Manual estimate by the team — **allowed only when 1–3 yield nothing**, and must be labeled "Estimated — no official scheme published" with `source_url` optionally null but `verified_at` still set.

The stored `source_label` should name which tier the figure came from so reviewers can audit it.

### Fallback rule (no official scheme)
Never hide EMI. When no `is_active` program exists for the brand/model, render the **generic calculator** ([[product-page-decision-hub]] §4 fallback): editable rate (default 12% p.a. reducing), default 20% down, 1–7 year tenure, with the explicit disclaimer that this is an indicative estimate, not a quoted offer. The generic path uses the same §3 reducing-balance math.

---

## 6. Admin / team UI

Managed from the team workspace, not casually editable (see [[team-workflow-update]]):

- **Who:** operators can create and edit finance programs; an admin must approve before `is_active` flips to true (operator-created rows start `is_active = false`).
- **Mandatory source rule (enforced in UI):** the save form blocks submission of a non-manual program unless `source_url` and `source_label` are filled and `verified_at` is set. Manual estimates require an explicit "no official scheme available" checkbox to bypass the URL requirement.
- **Surface:** a "Finance Programs" screen listing programs by brand with status (draft / live / inactive), source link, and verified date. Re-verification prompt when `verified_at` is older than 90 days (figures drift; rates change).
- Writes use the service role key server-side only; never expose it to the browser.

---

## 7. Acceptance checklist

- `brand_finance_programs` exists with RLS; anon reads only `is_active` rows.
- Non-manual programs cannot be saved without `source_url` + `source_label` + `verified_at`.
- Reducing and flat EMIs compute per §3; flat schemes carry the effective-cost note.
- Down payment clamps to `min_downpayment_percent`; tenure bounded by `max_tenure_months` / child terms.
- Official blocks always show source link + verified date; generic fallback shows the indicative disclaimer.
- No vehicle ever shows a hidden/empty finance section — official or generic always renders when a price exists.
- Operator-created programs require admin approval before going live.
