# NepaliGarage — System Architecture Overview

**Status:** CONFIRMED — all decisions finalized 2026-06-07

---

## 1. Confirmed Architecture Decisions

| Decision | Choice | Reason |
|----------|--------|--------|
| WordPress → Supabase connection | Custom lightweight WordPress plugin | Simplest, no extra infrastructure, SEO stays in WordPress |
| User dashboard location | `/dashboard` path on WordPress | No subdomain complexity, single domain, easier to manage |
| AI assistant provider | Claude API (Anthropic) | Already in use, best reasoning, tool use support |
| User authentication | Supabase Auth (email + Google OAuth) | Built into Supabase, no extra service needed |
| Payment processing | Stripe | Most reliable, international, works for Nepal NPR |
| Frontend stack | WordPress + Pagelayer Pro + custom blocks | No separate framework needed for Phase 1 and 2 |

---

## 2. System Map

```
┌─────────────────────────────────────────────────────────────┐
│                    PUBLIC USERS                              │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│              WORDPRESS (nepaligarage.com)                    │
│                                                             │
│  Public pages (SEO):                                        │
│    /compare/[car-a]-vs-[car-b]                             │
│    /cars/[brand]/[model]                                    │
│    /ev-price-estimator                                      │
│    /news and /blog                                          │
│    /garages and /charging-stations                          │
│                                                             │
│  Authenticated pages:                                       │
│    /dashboard          ← My Garage (Phase 2)               │
│    /dashboard/vehicle/[id]                                  │
│    /dashboard/add-vehicle                                   │
│    /dashboard/documents                                     │
│    /dashboard/reminders                                     │
│    /dashboard/ask-ai                                        │
│                                                             │
│  Plugins: Pagelayer Pro, SiteSEO Pro, SpeedyCache,         │
│           FormLayer Pro, GoSMTP Pro, Loginizer Pro         │
│  Custom plugin: NepaliGarage Core (built by us)            │
└──────────────────────────┬──────────────────────────────────┘
                           │
              ┌────────────┴────────────┐
              │ Supabase REST API       │ Supabase Auth
              │ (PostgREST)             │ (JS SDK in browser)
              ▼                         ▼
┌─────────────────────────────────────────────────────────────┐
│           SUPABASE (eukghqvlvlveshchnugk)                   │
│                                                             │
│  Public data (read-only, no auth required):                │
│    brands, models, variants, spec_fields,                  │
│    variant_specs, spec_sources, prices,                    │
│    price_estimates, comparisons                            │
│                                                             │
│  User data (requires Supabase Auth session):               │
│    users, user_vehicles, fuel_logs,                        │
│    charging_logs, service_logs, repair_logs,               │
│    documents, reminders, audit_logs, subscriptions,        │
│    ai_conversations, ai_memory                             │
│                                                             │
│  Phase 4 data:                                             │
│    vehicle_parts, part_interchange, garages,               │
│    parts_vendors                                           │
└─────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│              CLAUDE API (Anthropic)                          │
│                                                             │
│  AI Vehicle Assistant (Phase 3)                            │
│  Called server-side from WordPress custom plugin           │
│  Context: user vehicle profile + NepaliGarage KB           │
│  Risk classifier built into every repair query             │
└─────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│              STRIPE                                          │
│                                                             │
│  Subscription billing (Phase 2)                            │
│  Plans: Free, Owner Plus (NPR 499/mo), Family, Fleet       │
│  Webhooks update Supabase subscriptions table              │
└─────────────────────────────────────────────────────────────┘
```

---

## 3. The Custom WordPress Plugin: NepaliGarage Core

This plugin is the bridge between WordPress and Supabase. It is built by us and handles:

**Phase 1 features:**
- Fetch vehicle data from Supabase and render comparison tables as Gutenberg blocks or shortcodes
- Nepal price estimator calculator (customs duty formula)
- Dealer/importer lead form submission → stored in Supabase

**Phase 2 additions:**
- Supabase Auth integration (login/logout, session management in WordPress)
- `/dashboard` page rendering using Supabase JS SDK
- Vehicle log forms (fuel, service, charging, documents)
- Reminder display and management

**Phase 3 additions:**
- AI assistant chat interface on `/dashboard/ask-ai`
- Server-side Claude API calls with vehicle context

**What the plugin never does:**
- Stores user vehicle data in WordPress — always Supabase
- Handles payments directly — always Stripe → Supabase webhook

---

## 4. Data Flow: Comparison Page

```
User visits /compare/byd-atto-2-vs-toyota-ebella

1. WordPress loads comparison page template
2. NepaliGarage Core plugin calls Supabase REST API:
   GET https://eukghqvlvlveshchnugk.supabase.co/rest/v1/variant_specs
   ?variant_id=in.(byd-atto-2-e3,toyota-ebella-61kwh)
3. Supabase returns: specs, sources, confidence levels, prices
4. Plugin renders structured comparison table
5. Each cell shows: value + confidence badge (green/amber/red)
6. "Get Nepal quote" button submits lead via FormLayer → Supabase leads table
```

---

## 5. Data Flow: Dashboard (Phase 2)

```
User visits /dashboard

1. WordPress loads dashboard page template
2. NepaliGarage Core plugin checks Supabase Auth session (via cookie/JWT)
3. If no session → redirect to /login
4. If session valid → Supabase JS SDK loads user vehicles
5. User actions (add log, upload doc, set reminder) → Supabase via JS SDK
6. All writes have audit_log entry created automatically via Supabase trigger
```

---

## 6. Connection Details

See `connection.md` in the project root for all credentials.

- WordPress REST API: `https://nepaligarage.com/wp-json/wp/v2/`
- Supabase project: `eukghqvlvlveshchnugk`
- Supabase REST: `https://eukghqvlvlveshchnugk.supabase.co/rest/v1/`
- Supabase Auth: `https://eukghqvlvlveshchnugk.supabase.co/auth/v1/`
