# NepaliGarage — Product Definition Record (PDR)

**Version:** 1.0
**Date:** 2026-06-07
**Status:** Approved — ready for Phase 1 development

---

## 1. Product Vision

NepaliGarage is Nepal's smart vehicle ownership platform.

It is not a blog. It is not a YouTube channel. It is not a copy of CarDekho.

It is the single place where a Nepali vehicle owner can:
- Compare cars honestly before buying
- Track their vehicle's full history after buying
- Find parts, garages, and repair guidance
- Know their vehicle's real resale value when selling

Every feature serves one of these four moments: **Buy. Own. Repair. Sell.**

---

## 2. Why Nepal Needs This

| Problem | Current situation | NepaliGarage solution |
|---------|------------------|----------------------|
| No reliable Nepal price data | Prices spread across Facebook groups, dealer showrooms, scattered news articles | Nepal on-road price estimator with customs duty formula, labeled confidence |
| No vehicle comparison built for Nepal | Global sites show MSRP; Nepal buyers pay customs duty + VAT + local handling | Comparison engine with Nepal-specific fields: tax, ground clearance, service network, parts availability |
| No service history platform | Paper receipts, phone photos, WhatsApp messages — all lost | Digital vehicle logbook: fuel, service, repair, documents, audit trail |
| No parts compatibility resource | Mechanics rely on memory; wrong parts ordered frequently | Parts interchange database, Nepal vendor directory, quote requests |
| No resale proof | Buyers can't verify a seller's service history | Shareable vehicle history report (PDF) generated from verified logs |
| No Nepal-specific repair guidance | Global repair sites don't account for Nepal roads, dust, altitude, monsoon | AI assistant trained on Nepal vehicle data and road conditions |

---

## 3. What NepaliGarage Is NOT

- Not a generic auto news blog
- Not a copy of any Indian auto website
- Not an AI chatbot with no data behind it
- Not a YouTube channel (though YouTube supports it)
- Not a used car marketplace (not in MVP phases)
- Not a vehicle auction platform (not in MVP phases)

---

## 4. User Personas

### Persona 1: The Buyer — Bikram
- 32 years old, Kathmandu, software engineer
- Budget: NPR 40–55 lakh for a family car
- Confused by EV options: BYD Atto 2, Toyota Ebella, Hyundai Creta EV
- Needs: honest comparison, Nepal on-road price, service network quality, real-world range on Nepal roads
- Pain: can't find data he trusts; dealer quotes vary widely

### Persona 2: The Owner — Rajesh
- 45 years old, Pokhara, business owner
- Owns: Toyota Hilux, Tata Nexon EV, Yamaha WR125R
- Needs: track service history, fuel costs, remind insurance renewals, prove service records when selling
- Pain: paper receipts are lost; can't prove car's history to buyer; no reminders system

### Persona 3: The Mechanic — Santosh
- 38 years old, Teku, runs an independent garage
- Needs: find part numbers for older vehicles, know which models share parts, advise customers on compatible alternatives
- Pain: parts discontinued, no reliable interchange database for Nepal market

### Persona 4: The Dealer — Suresh
- 50 years old, Kathmandu, imports and sells used cars
- Needs: buyer leads, platform to list inventory, credibility with inspection reports
- Pain: depends entirely on Facebook groups and word of mouth; no structured lead pipeline

### Persona 5: The Fleet Manager — Anish
- 40 years old, Kathmandu, logistics company
- Manages: 12 delivery vehicles
- Needs: track all vehicles from one dashboard, schedule maintenance, track fuel costs per vehicle, generate reports for accounting
- Pain: manages everything in spreadsheets; no system for multi-vehicle records

---

## 5. Core Product Modules

### Module 1: Car Comparison Engine (Phase 1)
Public, free, SEO-driven.

Users can compare 2–4 vehicles side by side using a structured template. Every data point has a source, confidence level, and last-verified date.

**Comparison fields (mandatory for every vehicle):**
- Nepal on-road price (official or estimated)
- Variant available in Nepal
- Dimensions (L × W × H, wheelbase)
- Ground clearance
- Engine type / Motor type
- Displacement / Battery capacity (kWh)
- Power (BHP/kW) and Torque (Nm)
- Fuel type / Charging standard
- Range (ARAI or real-world Nepal estimate)
- Fuel economy / Energy consumption
- Transmission
- Seating capacity
- Boot space (litres)
- Airbags (number and positions)
- ADAS features
- Comfort features (sunroof, ventilated seats, wireless charging)
- Infotainment (screen size, Apple CarPlay, Android Auto)
- Warranty (years/km)
- Service interval
- Service network in Nepal (cities covered)
- Parts availability score (excellent/good/limited/poor)
- Maintenance cost estimate (annual, NPR)
- Resale value estimate (1 year, 3 year)
- Ground clearance suitability (Nepal roads: excellent/good/marginal/poor)
- Recommended for (city, highway, offroad, mixed)
- NepaliGarage verdict

### Module 2: Nepal Price Estimator (Phase 1)
Public, free.

Calculates Nepal on-road price from base India/international price using:
- Customs duty rate (varies by cc/kW)
- VAT (13%)
- Road tax (province-specific)
- Local handling/documentation
- Dealer margin estimate

Each output shows the formula used and is labeled "NepaliGarage estimate — not official."

### Module 3: Vehicle Database + Admin Panel (Phase 1)
Internal tool for content team.

Admin interface to add and edit:
- Brands, models, variants
- Spec values with source URLs
- Price data with confidence labels
- Comparison page configurations

### Module 4: Vehicle Owner Dashboard — My Garage (Phase 2)
Authenticated, subscription-based.

Users add their vehicles and log:
- Fuel fill-ups (date, litres, cost, odometer, station)
- EV charging sessions (date, kWh, cost, location, SOC before/after)
- Service visits (date, garage, work done, cost, odometer, parts replaced)
- Repairs (date, problem, fix, cost, mechanic name)
- Documents (bluebook, insurance, road tax, pollution test, inspection — with renewal dates)
- Reminders (insurance renewal, road tax, service due, pollution test)
- Photos (vehicle condition, accident photos, invoice scans)

Every entry generates an audit log: who added it, when, from what device.

### Module 5: AI Vehicle Assistant (Phase 3)
Authenticated, available to paid subscribers.

Answers questions in English and Nepali using:
- User's own vehicle records from My Garage
- NepaliGarage vehicle database
- Nepal-specific road, climate, and service knowledge
- Repair risk classification (Low/Medium/High/EV — see guardrails)

Example queries it handles:
- "When did I last change engine oil in my Nexon?"
- "My car vibrates going downhill from Nagarkot. What could it be?"
- "Which Hyundai/Kia models share parts with my Matrix?"
- "What should I check before driving Kathmandu to Pokhara?"
- "Prepare a buyer report for my Hilux."

### Module 6: Parts Finder (Phase 4)
Public directory + authenticated quote requests.

- Search by vehicle make/model/year
- View OEM part numbers
- See compatible models (verified interchange)
- See Nepal parts vendors (Teku, Birgunj, Butwal, importers)
- Request quote from multiple vendors
- Fitment confidence label: Confirmed / Likely / Unverified / Warning

### Module 7: Garage Directory (Phase 4)
Public, free.

Verified list of garages by:
- Location (city, area)
- Specialisation (EV, Japanese, Korean, Indian, European brands)
- Services offered
- Owner-reported rating (not fake reviews)
- Contact info

### Module 8: Resale Report (Phase 5)
Paid, generated from My Garage data.

A shareable PDF containing:
- Vehicle identity (make, model, variant, year, VIN if available)
- Full service history from My Garage logs
- Uploaded documents verification
- Repair and parts record
- NepaliGarage resale value estimate
- Ownership timeline

Used by sellers to prove vehicle condition to buyers.

---

## 6. MVP Scope — What Gets Built First

### MVP 1: Comparison Engine + Price Estimator
**Goal:** Create SEO traffic, trust, and structured data. Build the foundation the AI needs later.

**Deliverables:**
- Supabase schema: brands, models, variants, spec_fields, variant_specs, spec_sources, prices, price_estimates
- WordPress custom plugin: NepaliGarage Core v0.1
  - Fetches variant specs from Supabase
  - Renders comparison table with confidence badges
  - Nepal price estimator shortcode
- Admin panel to add/edit vehicle data (WordPress admin + Supabase direct)
- 10–15 initial comparison pages (see content list below)
- SEO structure: canonical URLs, schema markup, meta descriptions

**First 15 comparison pages:**
1. BYD Atto 2 vs Toyota Urban Cruiser Ebella
2. BYD Atto 2 vs Hyundai Creta EV
3. BYD Atto 2 vs Tata Punch EV
4. BYD Atto 2 vs MG ZS EV
5. Toyota Urban Cruiser Ebella vs Suzuki e-Vitara
6. Hyundai Creta EV vs Tata Nexon EV
7. BYD Atto 3 vs MG ZS EV
8. Tata Nexon EV vs BYD Dolphin (if available in Nepal)
9. BYD Atto 2 vs BYD Atto 3
10. Hyundai Creta EV vs BYD Atto 2 (variant comparison: base vs top)
11. Best EV under NPR 50 lakh in Nepal (multi-car comparison)
12. Toyota Fortuner vs Mitsubishi Pajero Sport
13. Suzuki Swift vs Hyundai Grand i10 Nios
14. Tata Nexon vs Hyundai Venue
15. Toyota Hilux vs Isuzu D-Max

**NOT in MVP 1:** User accounts, AI assistant, parts finder, subscriptions.

---

### MVP 2: My Garage Dashboard
**Goal:** Subscription product. Recurring revenue. Owner loyalty.

**Deliverables:**
- Supabase Auth setup (email + Google OAuth)
- `/login` and `/signup` pages on WordPress
- `/dashboard` page — vehicle list view
- `/dashboard/vehicle/[id]` — full vehicle timeline
- Add/edit vehicle form
- Fuel log form + history
- Service log form + history
- Document upload (Supabase Storage)
- Reminder setup and display
- Stripe integration: Free, Owner Plus (NPR 499/month)
- Audit log (automatic via Supabase triggers)

**NOT in MVP 2:** AI assistant, parts finder, resale report.

---

### MVP 3: AI Vehicle Assistant
**Goal:** Strongest differentiator. Paid feature for Owner Plus subscribers.

**Deliverables:**
- Claude API integration in NepaliGarage Core plugin (server-side)
- `/dashboard/ask-ai` page
- Vehicle context injected from Supabase: service history, specs, documents
- Repair risk classifier (Low/Medium/High/EV)
- Conversation history stored in Supabase ai_conversations
- Vehicle memory summarization in ai_memory (so context doesn't reset)
- Bilingual support: English and Nepali

---

### MVP 4: Parts Finder
**Goal:** Moat. Nepal-specific data no global competitor has.

**Deliverables:**
- Supabase schema: vehicle_parts, part_interchange, parts_vendors
- Parts search page
- OEM part number display
- Compatible models display with fitment confidence
- Vendor directory
- Quote request form
- Manual verification workflow for content team

---

### MVP 5: Resale Report
**Goal:** Monetize data. Make My Garage essential for sellers.

**Deliverables:**
- PDF generation from vehicle logs and documents
- Resale value estimate formula (age, km, service history, Nepal market)
- Shareable public link (expires or is password-protected)
- Paid: NPR 500–1,500 per report or included in higher subscription tier

---

## 7. Database Schema — Supabase

### Phase 1 Tables

```sql
-- Core vehicle taxonomy
brands (id, name, country_of_origin, logo_url, created_at)
models (id, brand_id, name, body_type, created_at)
variants (id, model_id, name, year_from, year_to, created_at)

-- Specification system
spec_fields (id, key, label, unit, category, data_type, sort_order)
-- Categories: dimensions, powertrain, range, safety, comfort, infotainment,
--             warranty, ownership, nepal_context

variant_specs (id, variant_id, spec_field_id, value, display_value,
               confidence, source_id, created_at, updated_at)
-- confidence: 'official' | 'estimated' | 'unverified'

spec_sources (id, url, label, source_type, verified_date, verified_by, notes)
-- source_type: 'manufacturer_brochure' | 'importer_site' | 'govt_document' |
--              'nepaligarage_calculation' | 'community_report'

-- Pricing
prices (id, variant_id, price_type, amount_npr, effective_date,
        source_id, confidence, notes)
-- price_type: 'official_on_road' | 'base_ex_showroom' | 'estimated_on_road'

price_estimates (id, variant_id, base_price_usd, duty_rate_pct,
                 vat_rate_pct, road_tax_npr, handling_npr,
                 calculated_total_npr, formula_version, created_at)

-- Comparison pages
comparisons (id, slug, title, variant_ids, meta_description,
             published, created_at, updated_at)

-- Lead capture
leads (id, lead_type, variant_id, name, phone, email,
       message, dealer_notified, created_at)
-- lead_type: 'quote_request' | 'test_drive' | 'used_car_inquiry'
```

### Phase 2 Tables (added when My Garage is built)

```sql
-- Users (extends Supabase auth.users)
user_profiles (id, auth_user_id, full_name, phone, location_city,
               preferred_language, subscription_tier, created_at)

user_vehicles (id, user_id, variant_id, custom_name, year_of_manufacture,
               purchase_date, purchase_price_npr, current_odometer_km,
               fuel_type, color, plate_number, vin, notes, created_at)

fuel_logs (id, vehicle_id, user_id, date, odometer_km, litres,
           cost_npr, fuel_brand, station_name, full_tank, notes, created_at)

charging_logs (id, vehicle_id, user_id, date, odometer_km,
               kwh_added, soc_before_pct, soc_after_pct, cost_npr,
               location, charger_type, duration_minutes, notes, created_at)

service_logs (id, vehicle_id, user_id, date, odometer_km, garage_name,
              service_type, description, cost_npr, next_service_km,
              next_service_date, invoice_url, notes, created_at)

repair_logs (id, vehicle_id, user_id, date, odometer_km, problem_description,
             fix_description, parts_replaced, garage_name, cost_npr,
             invoice_url, notes, created_at)

documents (id, vehicle_id, user_id, doc_type, label, file_url,
           expiry_date, notes, created_at)
-- doc_type: 'bluebook' | 'insurance' | 'road_tax' | 'pollution_test' |
--           'inspection' | 'invoice' | 'warranty_card' | 'other'

reminders (id, vehicle_id, user_id, reminder_type, due_date,
           due_odometer_km, message, is_sent, is_dismissed, created_at)

audit_logs (id, table_name, record_id, action, old_values, new_values,
            user_id, created_at)
-- Populated automatically by Supabase triggers

subscriptions (id, user_id, stripe_customer_id, stripe_subscription_id,
               plan_name, status, current_period_end, created_at)
```

### Phase 3 Tables (added when AI assistant is built)

```sql
ai_conversations (id, user_id, vehicle_id, messages, created_at, updated_at)
-- messages: JSONB array of {role, content, timestamp}

ai_memory (id, user_id, vehicle_id, summary, key_facts, last_updated)
-- Compressed vehicle context to avoid re-sending full history on every query
```

### Phase 4 Tables (added when Parts Finder is built)

```sql
vehicle_parts (id, variant_id, part_name, part_category, oem_part_number,
               alternate_part_numbers, notes, source_id, created_at)

part_interchange (id, part_id, compatible_variant_id, fitment_confidence,
                  notes, source_id, verified_by, created_at)
-- fitment_confidence: 'confirmed' | 'likely' | 'unverified' | 'warning'

garages (id, name, city, area, address, phone, specialisations,
         ev_capable, map_url, verified, created_at)

parts_vendors (id, name, city, area, phone, whatsapp, specialisations,
               import_source, verified, created_at)

part_quote_requests (id, part_id, vehicle_id, user_id, status,
                     vendor_ids_notified, created_at)
```

---

## 8. WordPress Site Structure

### Navigation
```
Home
├── Compare Cars          /compare
│   └── [Car A] vs [Car B]   /compare/[slug]
├── New Cars              /new-cars
│   └── [Brand]           /cars/[brand]
│       └── [Model]       /cars/[brand]/[model]
├── EVs in Nepal          /electric-vehicles
├── Price Estimator       /nepal-car-price-estimator
├── Used Car Guide        /used-cars
├── Maintenance           /maintenance
├── Garages               /garages
├── Parts Finder          /parts-finder       (Phase 4)
├── News                  /news
├── My Garage             /dashboard          (Phase 2, authenticated)
└── About                 /about
```

### URL Structure Rules
- Comparison pages: `/compare/[brand-a]-[model-a]-vs-[brand-b]-[model-b]`
- Car pages: `/cars/[brand]/[model]/[variant]`
- News: `/news/[year]/[slug]`
- All lowercase, hyphens only, no underscores

---

## 9. Monetization Model

### Revenue Streams by Phase

**Phase 1 (Comparison Engine live):**
| Revenue stream | Method | NPR estimate |
|----------------|--------|-------------|
| Google AdSense | Display ads on public pages | Starts low, grows with traffic |
| Dealer lead generation | "Request Nepal quote" form → notify dealer | NPR 200–500 per lead |
| Sponsored comparison | Importer pays for featured/sponsored comparison | NPR 10,000–50,000 per month |
| YouTube sync | Video comparisons drive website traffic | YouTube AdSense |

**Phase 2 (My Garage live):**
| Plan | Price | Features |
|------|-------|----------|
| Free | NPR 0 | 1 vehicle, basic reminders, limited logs (last 6 entries) |
| Owner Plus | NPR 499/month | 1 vehicle, unlimited logs, document uploads, AI assistant, reminders |
| Family Garage | NPR 999/month | Up to 4 vehicles, shared access |
| Fleet/Workshop | NPR 3,499/month | Up to 15 vehicles, staff accounts, export reports |

**Phase 3 (Parts Finder + Garages live):**
| Revenue stream | Method |
|----------------|--------|
| Parts vendor listing | Monthly fee for verified listing |
| Parts quote commission | 5–10% of confirmed transaction value |
| Garage listing (premium) | Monthly fee for featured placement |
| Vehicle inspection report | NPR 1,500–5,000 per inspection (partner mechanic network) |

**Phase 4 (Resale Report):**
| Revenue stream | Method |
|----------------|--------|
| Resale report (pay-per-report) | NPR 799 per PDF report |
| Resale report (included) | Included in Family Garage and Fleet plans |
| Data licensing | Aggregated resale index, EV depreciation data → banks, insurers |

---

## 10. AI Assistant Design

### Scope
The AI assistant answers questions about a user's own vehicles using their logs, documents, and the NepaliGarage vehicle database. It is not a general-purpose chatbot.

### Context injected into every conversation
1. User's vehicle(s): make, model, variant, year, odometer
2. Last 20 service/repair/fuel log entries
3. Active reminders
4. Vehicle specs from NepaliGarage database
5. Nepal-specific context (common issues for that model in Nepal, service network, parts availability)

### Repair risk classification (mandatory)

Every query that involves a mechanical system must be classified before responding:

| Classification | Examples | Response rule |
|---------------|----------|---------------|
| LOW | Wiper blades, cabin air filter, tyre pressure, washer fluid, bulbs, fuses | Give full step-by-step guidance |
| MEDIUM | Spark plugs, brake pad check (visual), battery terminals, coolant top-up | Explain likely cause + recommend mechanic inspection before DIY |
| HIGH | Brake replacement, suspension, steering, airbag system, fuel system, clutch | Do NOT give DIY steps. State: "This requires a certified mechanic." |
| EV HIGH-VOLTAGE | Battery pack, inverter, charging system, motor, HV cables | Never DIY. Mandatory warning. Direct to authorized EV service center. |

### What the AI never does
- Invents part numbers
- Confirms compatibility without a verified source
- Gives a resale value without stating it is an estimate and showing the inputs
- Pretends to know Nepal-specific data it was not provided
- Gives DIY guidance for HIGH or EV HIGH-VOLTAGE repairs

### Language
Responds in the same language the user writes in (English or Nepali). Switches between them naturally if the user switches.

---

## 11. Content Quality Standards

Every comparison page must have:
- [ ] All mandatory spec fields filled (value + source + confidence + date)
- [ ] Nepal on-road price (official or clearly labeled estimate)
- [ ] Nepal road suitability assessment
- [ ] Service network assessment (cities covered in Nepal)
- [ ] Parts availability assessment
- [ ] NepaliGarage verdict section
- [ ] No unmatched rows (if Car A has a spec, Car B must have a value or a reason it is missing)
- [ ] Schema markup for SEO (FAQPage, Product, Review)
- [ ] Meta description under 160 characters

---

## 12. Development Roadmap

| Phase | Features | Estimated effort | Priority |
|-------|----------|-----------------|----------|
| Phase 0 | Brand, guardrails, PDR, folder structure, DB design | Done | Complete |
| Phase 1 | Supabase schema, WordPress plugin, 15 comparison pages, price estimator, admin panel | 3–4 weeks | Immediate |
| Phase 2 | Auth, My Garage dashboard, fuel/service logs, documents, reminders, Stripe | 4–5 weeks | After Phase 1 |
| Phase 3 | AI assistant (Claude API), conversation memory, risk classifier | 2–3 weeks | After Phase 2 |
| Phase 4 | Parts finder, garage directory, vendor backend | 3–4 weeks | After Phase 3 |
| Phase 5 | Resale report PDF, inspection marketplace, fleet tools | 3–4 weeks | After Phase 4 |

**Total MVP 1–3:** approximately 9–12 weeks of focused development.

---

## 13. Risks and Mitigations

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Vehicle spec data is wrong or outdated | High | High | Source + confidence label on every field; last-verified date; easy admin edit |
| AI gives dangerous repair advice | Medium | Very High | Hard risk classifier; HIGH and EV responses never give DIY steps |
| Subscription revenue is slow to start | High | Medium | Phase 1 revenue from dealer leads and ads; subscription is Phase 2 |
| WordPress + Supabase complexity grows | Medium | Medium | Keep all user data in Supabase; WordPress is display only |
| Nepal payment gateway issues | Medium | High | Use Stripe first; add local gateway (eSewa, Khalti) in Phase 2b |
| Data privacy for user vehicle records | Low | High | Supabase RLS (row-level security) on all user tables; audit logs |
| Competitors copy comparison pages | High | Low | Comparison pages are traffic generators; the moat is user data + parts interchange |

---

## 14. Claude Code Build Prompts

These are the exact prompts to use when starting development of each phase.

### Phase 1 Prompt
```
We are building NepaliGarage (nepaligarage.com), Nepal's smart vehicle ownership platform.

Phase 1: Build the comparison engine.

WordPress site: nepaligarage.com (admin: "admin", app password: "mc3n wNfB JKTy LCiX 6BAj GQUm")
Supabase: project eukghqvlvlveshchnugk (postgres / aiExecution@2026)
Connection details: see /Users/rozanpz/Documents/Claude/Projects/NepaliGarage/connection.md
PDR: see /Users/rozanpz/Documents/Claude/Projects/NepaliGarage/research/NEPALIGARAGE_PDR.md

Task:
1. Create the Phase 1 Supabase schema (brands, models, variants, spec_fields, variant_specs, spec_sources, prices, price_estimates, comparisons, leads tables)
2. Set up Supabase RLS: public read on vehicle data, no public write
3. Seed initial data: BYD Atto 2 and Toyota Urban Cruiser Ebella with all mandatory spec fields
4. Build WordPress plugin NepaliGarage Core v0.1:
   - Fetch variant specs from Supabase REST API
   - Render comparison table with confidence badges (green/amber/red)
   - Shortcode: [ng_compare variant_ids="..."]
   - Nepal price estimator shortcode: [ng_price_estimator]
5. Create the first comparison page: /compare/byd-atto-2-vs-toyota-urban-cruiser-ebella

Follow the guardrails in /Users/rozanpz/Documents/Claude/Projects/NepaliGarage/guardrails/product-guardrails.md
Brand colors: Nepali Red #C1121F, Deep Blue #1B2A6B, White #FFFFFF
```

### Phase 2 Prompt
```
NepaliGarage Phase 2: Build My Garage dashboard.

[Reference same connection details and PDR as Phase 1 prompt]

Task:
1. Set up Supabase Auth (email + Google OAuth)
2. Create Phase 2 Supabase tables: user_profiles, user_vehicles, fuel_logs, charging_logs, service_logs, repair_logs, documents, reminders, audit_logs, subscriptions
3. Set up RLS: users can only read/write their own records
4. Set up audit_log trigger: fires on INSERT/UPDATE/DELETE on all user tables
5. Create WordPress pages: /login, /signup, /dashboard, /dashboard/vehicle/[id]
6. Add Supabase JS SDK to NepaliGarage Core plugin
7. Build dashboard UI: vehicle list, add vehicle form, fuel log form, service log form, document upload, reminder setup
8. Integrate Stripe: Free and Owner Plus (NPR 499/month) plans
9. Stripe webhook → update Supabase subscriptions table
```
