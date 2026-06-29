-- My Garage — owner logbook gaps (Phase 2)
-- Adds per-vehicle video links + country, recurring tax/renewal obligations,
-- and a Nepal-seeded obligation-template reference for prefill.
-- All target tables are 0-row → plain ALTER/CREATE, no backfill.
-- RLS mirrors the existing user_vehicles_own policy (auth.uid() = user_id).

BEGIN;

-- 1. user_vehicles: video links + country -----------------------------------
ALTER TABLE public.user_vehicles
    ADD COLUMN IF NOT EXISTS videos       jsonb NOT NULL DEFAULT '[]'::jsonb,
    ADD COLUMN IF NOT EXISTS country_code text  NOT NULL DEFAULT 'NP';

-- 2. vehicle_obligations: recurring tax / renewal tracker --------------------
CREATE TABLE IF NOT EXISTS public.vehicle_obligations (
    id               uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    user_vehicle_id  uuid NOT NULL REFERENCES public.user_vehicles(id) ON DELETE CASCADE,
    user_id          uuid NOT NULL,
    country_code     text NOT NULL DEFAULT 'NP',
    obligation_type  text NOT NULL,           -- yearly_vehicle_tax|road_tax|insurance|pollution|registration_renewal|custom
    label            text,
    last_paid_date   date,
    period_months    int  NOT NULL DEFAULT 12,
    next_due_date    date,                     -- computed client-side: last_paid_date + period_months
    amount_paid      numeric(12,2),
    currency         text NOT NULL DEFAULT 'NPR',
    notes            text,
    is_active        boolean NOT NULL DEFAULT true,
    created_at       timestamptz NOT NULL DEFAULT now(),
    updated_at       timestamptz NOT NULL DEFAULT now(),
    CONSTRAINT vehicle_obligations_type_chk
        CHECK (obligation_type IN
            ('yearly_vehicle_tax','road_tax','insurance','pollution','registration_renewal','custom'))
);

CREATE INDEX IF NOT EXISTS vehicle_obligations_vehicle_idx
    ON public.vehicle_obligations (user_vehicle_id);
CREATE INDEX IF NOT EXISTS vehicle_obligations_due_idx
    ON public.vehicle_obligations (user_id, next_due_date);

-- updated_at trigger (reuse ng_touch_updated_at from Phase C if present)
CREATE OR REPLACE FUNCTION public.ng_touch_updated_at()
RETURNS trigger AS $$
BEGIN
    NEW.updated_at = now();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS vehicle_obligations_touch ON public.vehicle_obligations;
CREATE TRIGGER vehicle_obligations_touch
    BEFORE UPDATE ON public.vehicle_obligations
    FOR EACH ROW EXECUTE FUNCTION public.ng_touch_updated_at();

ALTER TABLE public.vehicle_obligations ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS vehicle_obligations_own ON public.vehicle_obligations;
CREATE POLICY vehicle_obligations_own
    ON public.vehicle_obligations FOR ALL
    USING (auth.uid() = user_id)
    WITH CHECK (auth.uid() = user_id);

-- 3. obligation_templates: country-aware prefill (public read) ---------------
CREATE TABLE IF NOT EXISTS public.obligation_templates (
    id                    uuid PRIMARY KEY DEFAULT gen_random_uuid(),
    country_code          text NOT NULL,
    obligation_type       text NOT NULL,
    label                 text NOT NULL,
    default_period_months int  NOT NULL DEFAULT 12,
    display_order         int  NOT NULL DEFAULT 0,
    notes                 text,
    CONSTRAINT obligation_templates_uniq UNIQUE (country_code, obligation_type)
);

ALTER TABLE public.obligation_templates ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS obligation_templates_anon_read ON public.obligation_templates;
CREATE POLICY obligation_templates_anon_read
    ON public.obligation_templates FOR SELECT
    USING (true);

-- Seed Nepal (NP) defaults. These are owner-confirmable starting points
-- (periods only; no authoritative legal amounts), labelled for the UI.
INSERT INTO public.obligation_templates
    (country_code, obligation_type, label, default_period_months, display_order, notes)
VALUES
    ('NP', 'yearly_vehicle_tax',     'Yearly Vehicle Tax',          12, 1, 'Annual provincial vehicle tax — confirm rate with Transport Office.'),
    ('NP', 'road_tax',               'Road / Transport Tax',        12, 2, 'Annual road tax — confirm with your province.'),
    ('NP', 'insurance',              'Insurance Renewal',           12, 3, 'Comprehensive/third-party policy renewal.'),
    ('NP', 'pollution',              'Pollution (Green Sticker)',   12, 4, 'Emissions test / green sticker.'),
    ('NP', 'registration_renewal',  'Registration Renewal',        12, 5, 'Bluebook registration renewal.')
ON CONFLICT (country_code, obligation_type) DO NOTHING;

COMMIT;
