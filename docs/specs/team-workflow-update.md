# Team Workflow Update Spec

Source of truth for operational workflows across the team workspace. Covers who manages finance programs, parts requests, and content, and how the new course-correction features ([[finance-data-sourcing]], [[parts-request-mvp]]) fit existing roles.

Anchored to current state: `/team/` (`page-team.php` + `assets/js/team.js`) is Supabase-authenticated and gates on `app_metadata.team_role` being `admin` or `operator`. This is separate from WordPress admin login.

---

## 1. `/team/` authentication

- `/team/` authenticates via **Supabase Auth** (email/password), not WordPress sessions.
- Access is granted only when the signed-in Supabase user has `app_metadata.team_role = 'admin'` or `'operator'`. No other value (or absence) grants access.
- WordPress admin (`wp-admin`) is a **separate credential system** used for content publishing and the existing Leads/Orders/Vehicles screens. The two are not linked; a person may hold both independently.
- `app_metadata.team_role` is the single source of role truth for `/team/`. It is set in Supabase (not editable from the browser) and read from the JWT.

---

## 2. Roles & responsibilities

Two roles. `admin` is a superset of `operator`.

| Area | operator | admin |
|------|----------|-------|
| Finance programs | create / edit (saved as draft) | create / edit / **approve to live** / deactivate |
| Parts requests | review, change status, manual supplier outreach | all operator actions + reassign |
| Content (brand news) | draft / edit articles | draft / edit / **publish** |
| Vehicles / specs | edit (where capability granted) | full |
| Team user roles | — | manage `team_role` assignments |

Maps onto the existing WordPress custom roles where relevant (`ng_operator` = content + leads + orders; `ng_dealer_partner` = read-only). The Supabase `team_role` governs `/team/`; the WordPress roles govern `wp-admin`. Keep both in sync for any given staff member.

---

## 3. Finance program workflow

Drives the mandatory-source rule from [[finance-data-sourcing]] §6.

```
operator drafts program ──► fills mandatory source_url + source_label + verified_at
        │  (saved is_active = false)
        ▼
admin reviews source against sourcing hierarchy ──► approves ──► is_active = true (goes live)
                                              └─► rejects ──► back to operator with notes
```

Rules:
- Operator-created programs start `is_active = false` and are never shown on the site until an admin approves.
- The save form blocks a non-manual program without `source_url`, `source_label`, and `verified_at`. Manual estimates require an explicit "no official scheme available" acknowledgement.
- Admin verifies the source ranks correctly in the sourcing hierarchy (official > importer > bank > manual) before approval.
- Re-verification: when `verified_at` is older than 90 days, the program is flagged for an operator to re-confirm figures; admin re-approves if the rate changed.

---

## 4. Parts request workflow

Drives the private-capture model from [[parts-request-mvp]].

```
public submits request ──► part_requests (status = open)
        ▼
operator reviews queue ──► status = in_review ──► contacts supplier MANUALLY (phone / WhatsApp / email)
        ▼
outcome ──► status = fulfilled  (sourced)  OR  status = closed  (not available / abandoned)
```

Rules:
- Requests are private lead capture — never public on the site.
- Supplier outreach is **manual at launch**. There is no supplier-facing UI; the operator handles contact off-platform and records the outcome via status changes.
- Future supplier routing will use the reserved `assigned_to` / `supplier_notes` columns; not built now.
- Reviewed from the wp-admin "Parts Requests" queue (service-role read).

---

## 5. Content workflow

Unchanged from existing guardrails, restated for completeness:

- Brand-news articles are produced by the `/ng-brand-news` pipeline and **always posted as `draft`**.
- A human (operator or admin) reviews before publish; admin (or a `publish_posts`-capable role) performs the publish.
- No auto-publish. AI-written articles must contain Nepal-specific data or they are not published (per `guardrails/product-guardrails.md`).

---

## 6. Credential rotation

Operational hygiene tied to the project going live:

- During development, use a **dedicated Supabase team user** for `/team/` — not a shared or personal long-lived account.
- After the project goes live, **rotate** both:
  - WordPress admin credentials, and
  - any development/team Supabase credentials (and the team user's password).
- Do not continue using shared or long-lived development passwords post-launch.
- The Supabase service role key stays server-side only and is never exposed to the browser; rotating it requires updating the `ng_supabase_service_key` WP option.

---

## 7. Acceptance checklist

- `/team/` grants access only to Supabase users with `app_metadata.team_role` in (`admin`, `operator`); WordPress login is independent.
- Operators can draft finance programs but cannot make them live; admin approval is required (§3).
- Mandatory source rule enforced before any non-manual finance program can be saved.
- Parts requests flow `open → in_review → fulfilled/closed` with manual, off-platform supplier outreach (§4).
- Brand-news content remains draft-by-default with human review before publish (§5).
- Post-launch credential rotation documented and actioned for both WordPress and Supabase (§6).
