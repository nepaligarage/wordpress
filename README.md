# NepaliGarage — Project Root

**Website:** https://nepaligarage.com
**Tagline:** Nepal's smart vehicle ownership platform.

## What this project is

NepaliGarage is a structured vehicle intelligence platform for Nepal — not a blog, not a YouTube channel. It combines:

- Public car comparison and price estimation (SEO + traffic)
- Vehicle owner dashboard with service, fuel, and document logs (subscription)
- AI vehicle assistant built on structured Nepal-specific data (differentiator)
- Parts compatibility finder and garage/vendor directory (moat)

## What this local folder contains

This folder is the **command centre** — guardrails, brand identity, architecture decisions, and product truth. It does NOT contain website files. The live website runs on WordPress + Supabase.

```
/brand           Brand identity: colors, typography, logo specs, tone of voice
/guardrails      Product rules: what we build, what we never build, AI safety rules
/architecture    System design: WordPress + Supabase structure, API contracts
/research        Competitor analysis, market research, PDR documents
connection.md    Verified credentials for WordPress and Supabase (never commit publicly)
```

## Connection reference

See `connection.md` for all verified WordPress and Supabase connection details.

## Current phase

**Phase 0: Foundation** — Brand identity, guardrails, architecture, and PDR definition.
No code is being written until Phase 0 is complete.
