# Comparison Builder Spec

Source of truth for converting `/compare/` (`theme/nepaligarage-theme/page-compare.php`) from an editorial library into a user-driven comparison builder. Editorial comparisons remain as a secondary section.

**Launch scope decision: exactly 2 cars (Car A vs Car B).** Not 3. Two slots is simpler to build, lays out cleanly on mobile, and matches the existing `[ng_compare]` two-variant renderer and the hero metric card already advertising "2 cars side-by-side at a time". Up to 3 is deferred.

---

## 1. User flow

```
Product page ──Add to Compare──► localStorage ng_compare (max 2)
        │                              │
        │  2/2 ready → "Compare now"   │
        ▼                              ▼
/compare/?a=<slugA>&b=<slugB>  ◄── sticky compare bar "Compare"
        │
        ▼
Builder loads both cars pre-selected → renders comparison output
```

1. User adds Car A on its product page. Compare list = `[A]`.
2. User navigates to another car, adds it. List = `[A, B]`, now full.
3. Either CTA ("Compare now" button or sticky bar) routes to `/compare/?a=A&b=B`.
4. Builder reads URL params first, falls back to `localStorage` if params absent.
5. Builder fetches both vehicles from Supabase and renders the comparison output (§4).
6. User can swap either car via the pickers (§3) without leaving the page; URL and `localStorage` update in place.

---

## 2. Compare list state model

Single shared client module (`assets/js/compare.js`, new). Source of truth = `localStorage` key `ng_compare`:

```json
[{ "slug": "byd-atto-2", "brand_slug": "byd", "model_slug": "atto-2", "name": "BYD Atto 2" }]
```

- Max length 2. Adding a third is rejected (button shows "list full" state — see [[product-page-decision-hub]] §2).
- API surface used by product page + builder: `ngCompare.list()`, `ngCompare.add(car)`, `ngCompare.remove(slug)`, `ngCompare.clear()`, `ngCompare.has(slug)`, `ngCompare.compareUrl()`.
- `compareUrl()` returns `/compare/?a=<slug0>&b=<slug1>` when 2 present, `/compare/?a=<slug0>` when 1.
- Every mutation fires a `ng:compare:change` DOM event so the hero button, the section-10 block, and the sticky bar re-render.

---

## 3. Compare drawer / sticky bar + builder pickers

### Sticky compare bar (global, all pages)
- Appears when list length ≥ 1; hidden at 0.
- Fixed to viewport bottom (above cookie bar when consent unset).
- Contents: thumbnails/names of selected cars, a `0/2 · 1/2 · 2/2` counter, a "Clear" link, and a primary "Compare" button.
- States: at 1/2 the button reads `Add 1 more` (disabled) with helper "pick a second car"; at 2/2 it reads `Compare` and links to `compareUrl()`.

### Builder pickers (on `/compare/`)
Two slots side by side (stacked on mobile): Car A and Car B. Each empty slot shows a picker; each filled slot shows the chosen car with a "Change" / "Remove" control.

Picker controls (shared component per slot):
- **Brand filter** — dropdown from `brands` (`select=id,name,slug`, `order=name.asc`).
- **Body type filter** — dropdown of distinct `models.body_type` values.
- **Search** — text input filtering models/variants by name (client-side over a fetched candidate list, or debounced Supabase `ilike` on `models.name`).
- Result list shows model cards; selecting one resolves to its cheapest available variant (`is_available_nepal=eq.true`, `order=starting_price_npr.asc`) and fills the slot.

A car already chosen in the other slot is disabled in the result list (no comparing a car with itself).

---

## 4. Comparison output

Rendered once both slots are filled. Reuses the plugin renderer where possible (`plugin/templates/comparison-table.php` / `NG_Comparison_Renderer`), driven by the two resolved `variant_ids` instead of fixed shortcode args.

Output blocks, in order:

1. **Header strip** — both cars: image, brand + model + variant name, starting price, price badge. A "winner-neutral" headline (no auto-verdict).
2. **Spec table** — aligned rows from `variant_specs`, grouped by spec category (same grouping logic as the product page). Differences highlighted: when both have a numeric value, the better value (context-dependent: higher power, lower price, larger battery) gets a subtle highlight; non-comparable rows render plainly. Each value keeps its confidence badge + source link.
3. **Finance EMI side-by-side** — for each car, a compact EMI card driven by [[finance-data-sourcing]]. Shared controls above both cards: down payment (% slider) and tenure (dropdown) apply to both cars simultaneously so EMIs are compared on equal terms. Each card shows monthly EMI, down payment, loan amount. If only one car has an official scheme, the other falls back to the generic estimate, clearly labeled.
4. **Editorial verdict (conditional)** — only rendered if a published `comparisons` row exists matching this exact pair (match on the two model slugs, either order). Otherwise omitted entirely — never auto-generate a verdict.

---

## 5. Fallback: only one car selected

When `/compare/` loads with one slug (`?a=` only) or one item in `localStorage`:

- Fill slot A with the car.
- Slot B renders an **empty-slot prompt**: heading `Pick a car to compare against the <name>`, with the full picker (brand/body/search) active.
- Show a short helper: "Add a second car to see specs and EMI side by side."
- No spec/finance output until slot B is filled.

When zero cars: show the picker in both slots plus a hint to start from any car's product page.

---

## 6. Editorial comparisons coexistence

The existing editorial library does not disappear — it moves below the builder:

- Page order on `/compare/`: (1) builder hero + pickers, (2) comparison output when applicable, (3) **secondary section** "Editor's comparisons" = the existing `comparisons` featured + library grids (current `page-compare.php` markup), (4) benefits block.
- The editorial cards keep linking to their existing `/compare/<slug>/` editorial pages.
- The hero CTA "Browse Comparisons" anchors to the editorial section rather than being the primary action; the primary action is now the builder itself.

---

## 7. URL state (shareable)

- Canonical builder URL: `/compare/?a=<model-slug>&b=<model-slug>` (e.g. `/compare/?a=byd-atto-3&b=hyundai-creta-ev`). Slugs are model slugs; the builder resolves each to its default variant.
- One car: `/compare/?a=<slug>`.
- On every picker change, update the URL with `history.replaceState` (no reload) and sync `localStorage`.
- Loading priority: URL params win over `localStorage`; if params resolve to nothing (bad slug), fall back to `localStorage`, then to empty pickers.
- These query-string URLs are distinct from editorial `/compare/<slug>/` permalinks and must not 404 — `page-compare.php` already forces `status_header(200)`; keep that.
- Shareable: a user can copy the URL and a recipient sees the same two cars pre-loaded.

---

## 8. Acceptance checklist

- Adding 2 cars from product pages and clicking "Compare now" lands on `/compare/?a=…&b=…` with both pre-loaded.
- Builder pickers filter by brand, body type, and search; a car cannot be compared with itself.
- Spec table aligns rows and highlights the better value where comparable; sources/confidence preserved.
- Shared down payment + tenure controls drive both EMI cards; mixed official/generic schemes labeled correctly.
- One-car load shows the empty-slot prompt, not a broken layout.
- Editorial comparisons still reachable as a secondary section.
- URL is shareable and reproduces state; no reloads on picker changes.
