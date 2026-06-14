-- NepaliGarage — BYD content layer schema
-- Date: 2026-06-14
-- Purpose: create the missing product-content tables already expected by
-- page-vehicle.php and the Team Dashboard so one-brand rollout (BYD) can be
-- completed with structured data instead of ad-hoc content.

create extension if not exists pgcrypto;

-- ---------------------------------------------------------------------------
-- vehicle_videos
-- Used by:
-- - theme/nepaligarage-theme/page-vehicle.php
-- - theme/nepaligarage-theme/assets/js/team.js
-- ---------------------------------------------------------------------------
create table if not exists public.vehicle_videos (
    id uuid primary key default gen_random_uuid(),
    model_id uuid not null references public.models(id) on delete cascade,
    title text not null,
    youtube_url text not null,
    youtube_video_id text,
    thumbnail_url text,
    video_type text,
    source_name text,
    display_order integer not null default 0,
    is_featured boolean not null default false,
    is_verified boolean not null default false,
    created_at timestamptz not null default now()
);

create index if not exists idx_vehicle_videos_model_id on public.vehicle_videos(model_id);
create index if not exists idx_vehicle_videos_featured_order on public.vehicle_videos(model_id, is_featured desc, display_order asc);

-- ---------------------------------------------------------------------------
-- vehicle_parts
-- Used by:
-- - theme/nepaligarage-theme/page-vehicle.php
-- - theme/nepaligarage-theme/assets/js/team.js
-- ---------------------------------------------------------------------------
create table if not exists public.vehicle_parts (
    id uuid primary key default gen_random_uuid(),
    model_id uuid not null references public.models(id) on delete cascade,
    part_name text not null,
    part_category text,
    price_npr numeric,
    image_url text,
    notes text,
    source_url text,
    display_order integer not null default 0,
    is_common boolean not null default false,
    is_verified boolean not null default false,
    created_at timestamptz not null default now()
);

create index if not exists idx_vehicle_parts_model_id on public.vehicle_parts(model_id);
create index if not exists idx_vehicle_parts_common_order on public.vehicle_parts(model_id, is_common desc, display_order asc);

-- ---------------------------------------------------------------------------
-- vehicle_competition
-- Used by:
-- - theme/nepaligarage-theme/page-vehicle.php
-- - theme/nepaligarage-theme/assets/js/team.js
-- ---------------------------------------------------------------------------
create table if not exists public.vehicle_competition (
    id uuid primary key default gen_random_uuid(),
    model_id uuid not null references public.models(id) on delete cascade,
    competitor_model_id uuid not null references public.models(id) on delete cascade,
    display_order integer not null default 0,
    created_at timestamptz not null default now(),
    constraint vehicle_competition_unique_pair unique (model_id, competitor_model_id)
);

create index if not exists idx_vehicle_competition_model_id on public.vehicle_competition(model_id);
create index if not exists idx_vehicle_competition_competitor_model_id on public.vehicle_competition(competitor_model_id);

-- ---------------------------------------------------------------------------
-- Optional integrity/cleanup suggestions for existing tables
-- ---------------------------------------------------------------------------
create index if not exists idx_vehicle_images_model_order on public.vehicle_images(model_id, display_order asc);
create index if not exists idx_vehicle_pros_cons_model_type_order on public.vehicle_pros_cons(model_id, type, display_order asc);
create index if not exists idx_vehicle_issues_model_created on public.vehicle_issues(model_id, created_at desc);
create index if not exists idx_models_brand_slug on public.models(brand_id, slug);
create index if not exists idx_variants_model_slug on public.variants(model_id, slug);

-- ---------------------------------------------------------------------------
-- Recommended follow-up data work for BYD (not executed automatically here)
-- ---------------------------------------------------------------------------
-- 1. Insert BYD showroom rows into public.showrooms.
-- 2. Backfill models.brochure_url for BYD models.
-- 3. Insert official Atto 2 gallery rows into public.vehicle_images.
-- 4. Insert Atto 2 videos into public.vehicle_videos.
-- 5. Insert Atto 2 parts into public.vehicle_parts.
-- 6. Insert Atto 2 competitor relationships into public.vehicle_competition.
-- 7. Insert Atto 2 pros/cons and issues with verified source references.
