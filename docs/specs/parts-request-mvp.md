# Parts Request MVP Spec

Source of truth for the parts demand-capture MVP. Upgrades the product-page parts area ([[product-page-decision-hub]] §5) from static reference content into the first transactional parts feature: a private request/want-list that seeds a future authorized-supplier network.

Scope at launch: capture demand only. No supplier-facing UI, no public request board, no fulfilment automation.

---

## 1. Schema — `part_requests`

Supabase `public` schema, RLS enabled. One row per submitted parts request.

| Column | Type | Notes |
|--------|------|-------|
| `id` | uuid PK | `gen_random_uuid()` |
| `model_id` | uuid FK → `models.id` | required; the car the part is for |
| `variant_id` | uuid FK → `variants.id` nullable | optional precise variant |
| `part_name` | text | required; free text, e.g. "front brake pads" |
| `part_category` | text nullable | optional taxonomy, e.g. `brakes`, `electrical`, `body`, `service` |
| `vehicle_year_text` | text nullable | free text year/range, e.g. "2023" or "2022–2024" |
| `notes` | text nullable | extra detail (OEM vs aftermarket, symptoms) |
| `requester_name` | text nullable | optional |
| `requester_phone` | text | **required** — primary contact |
| `requester_city` | text | **required** — routing/logistics |
| `urgency` | text enum | `low` \| `normal` \| `urgent`, default `normal` |
| `status` | text enum | `open` \| `in_review` \| `fulfilled` \| `closed`, default `open` |
| `created_at` | timestamptz default now() | |

Constraints:
- `urgency in ('low','normal','urgent')`, `status in ('open','in_review','fulfilled','closed')`.
- Index on `(status, created_at desc)` for the review queue, and `(model_id)`.

### Future-state columns (add later, not at launch)
Reserved for supplier routing once partner onboarding exists — do not build UI for these now:
- `assigned_to` uuid nullable — team member / supplier handling the request.
- `supplier_notes` text nullable — internal routing notes.

These are explicitly deferred; the schema can be migrated to add them without breaking the MVP.

---

## 2. RLS

- **Insert:** allowed for anonymous (anon key) via the request form — this is public lead intake. Insert policy permits only the request columns; `status` defaults server-side to `open`.
- **Select/update:** service role only (team/admin). No anon read — requests are private and must never be queryable from the browser.
- This mirrors how `leads` are handled: write-only from the public site, read behind the service role.

---

## 3. Product-page parts section

Two layers (see [[product-page-decision-hub]] §5).

### Layer 1 — reference content (existing `vehicle_parts`)
Common replacement parts grid: part name, category, optional price, notes, source link. Verified rows only. Hidden when empty. This is informational, not transactional.

### Layer 2 — request form
A "Request a part" button opens a modal (reuse the enquiry-modal pattern in `page-vehicle.php`). Fields:

| Field | Control | Required | Validation |
|-------|---------|----------|------------|
| Part name | text | yes | non-empty, trimmed, ≤ 120 chars |
| Part category | select (predefined list + "Other") | no | from allowed taxonomy |
| Vehicle year | text | no | ≤ 20 chars |
| Variant | hidden/select (pre-filled with active variant) | no | must belong to this model |
| Notes | textarea | no | ≤ 500 chars |
| Your name | text | no | ≤ 80 chars |
| Phone | tel | yes | required; Nepal mobile format (10 digits / 98XXXXXXXX) |
| City | select (same city list as enquiry modal) | yes | required, non-empty |
| Urgency | radio (low / normal / urgent) | no | defaults `normal` |

- `model_id` is injected server-side from the page context, not trusted from the client.
- On submit: validate at the boundary, insert into `part_requests`, show a success state ("Request received — we'll contact you if we can source it"). On failure show an inline error.
- Honeypot field + basic rate limiting to deter spam (same approach as leads).

---

## 4. Visibility

- Parts requests are **private lead capture**. They are never rendered publicly on the site — no public want-list board, no counts, no requester details exposed.
- The only surfaces that read requests are the team/admin queue (§5).

---

## 5. Team / admin review

- A "Parts Requests" screen under `wp-admin → NepaliGarage` (alongside Leads/Orders), reading via the service role key.
- Queue view: columns for created date, model, part name, city, urgency, status; default sort newest first; filter by status and urgency.
- Row actions: change `status` (`open → in_review → fulfilled / closed`), view full notes and contact details.
- For launch, supplier outreach is **manual** — the reviewer contacts a supplier by phone/WhatsApp/email outside the system and updates status. See [[team-workflow-update]] for the operational flow.

---

## 6. Future state (not in MVP)

- Supplier routing using the reserved `assigned_to` / `supplier_notes` columns.
- Supplier-facing dashboard or notifications.
- Aggregated demand signals (most-requested parts per model) to prioritize which suppliers to onboard.

None of these are built now; the MVP only captures and lets the team review intent.

---

## 7. Acceptance checklist

- `part_requests` exists with RLS: anon insert-only, service-role read/update.
- Request modal validates: part name, phone, and city required; everything else optional.
- `model_id` set server-side, never trusted from the client.
- Submissions appear in the wp-admin Parts Requests queue and nowhere public.
- Status transitions work (`open / in_review / fulfilled / closed`).
- Reference parts grid and request CTA coexist; CTA shows even when no reference parts exist.
