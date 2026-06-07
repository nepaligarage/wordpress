# Research: ChatGPT Strategy Discussion

**Date:** 2026-06-07
**Source:** ChatGPT conversation shared by founder
**Status:** Reviewed — key decisions extracted into architecture/system-overview.md and guardrails/product-guardrails.md

## Summary of key conclusions from this discussion

1. NepaliGarage should be an ecosystem platform, not just a blog (CarDekho model, not CarWow blog model)
2. Three-layer product: Public content → Owner dashboard → AI assistant
3. Build sequence: Comparison engine first, My Garage second, AI third
4. Parts interchange is the strongest defensible moat for Nepal
5. AI repair advice must have hard risk-level classification (see guardrails)
6. Data confidence labels (official/estimated/unverified) must be on every spec value
7. Audit logs are critical for resale value proof and fleet management
8. Monetization: SEO + dealer leads first, subscriptions second, marketplace third

## Key competitor learnings

- Edmunds: Multi-car comparison, ownership cost, real-world pricing
- Cars.com: Compare up to 4 cars by MSRP, fuel economy, crash tests
- CarWale/CarDekho: Indian-market structure closest to Nepal context
- V3Cars: Variant-by-variant "best value" analysis
- Carwow: Video-led trust → website traffic model
- KBB: Used-car valuation and dealer acquisition leads
- RockAuto: Parts catalog and part-number lookup (benchmark for parts finder)
- RepairPal: Nepal repair cost estimator model
- iFixit FixBot: AI repair assistant — shows opportunity AND risk

## Monetization phases

1. SEO + YouTube + dealer leads (Phase 1)
2. Subscriptions at NPR 499/month (Phase 2)
3. Parts marketplace + garage bookings (Phase 3/4)
4. Data products for banks, insurers, dealers (Phase 4/5)

## Full discussion

The full ChatGPT discussion is preserved in project records and covers:
- Global auto website benchmarks
- Revenue model analysis
- Repair/maintenance platform research
- Community research sources
- Three-layer product architecture
- Supabase schema outline
- Website navigation structure
- Comparison page template design
- Subscription plan tiers
- MVP sequencing (1: comparison, 2: garage, 3: AI, 4: parts, 5: resale)
- Business model recommendation
- PDR preparation guidance
