-- User-contributed models — let vehicle owners add a missing model to the
-- shared catalog so the next owner can pick it from the dropdown.
--
-- Provenance is tracked (source='user', created_by) so the team can audit and
-- curate community submissions later. Per product guardrails, user-submitted
-- catalog rows are 'unverified' until an operator confirms them.
--
-- Idempotent: safe to re-run.

-- 1. Provenance columns on the curated catalog ------------------------------
ALTER TABLE public.models
    ADD COLUMN IF NOT EXISTS source     text NOT NULL DEFAULT 'catalog',  -- 'catalog' | 'user'
    ADD COLUMN IF NOT EXISTS created_by uuid;                              -- auth.uid() for user submissions

-- 2. Prevent duplicate / race-condition models within a brand ---------------
--    (case-insensitive; no existing duplicates so this is non-destructive)
CREATE UNIQUE INDEX IF NOT EXISTS models_brand_lower_name
    ON public.models (brand_id, lower(name));

-- 3. RLS: authenticated owners may INSERT only their own 'user' submissions --
--    Read stays public (public_read_models). Catalog/editorial writes remain
--    restricted to service role + team operators.
DROP POLICY IF EXISTS user_insert_models ON public.models;
CREATE POLICY user_insert_models ON public.models
    FOR INSERT TO authenticated
    WITH CHECK ( source = 'user' AND created_by = auth.uid() );

-- 4. Let a user_vehicle reference a model directly (variant optional). -------
--    Owners often know make+model but not the exact variant; without this a
--    model-only selection was silently dropped on save.
ALTER TABLE public.user_vehicles
    ADD COLUMN IF NOT EXISTS model_id uuid REFERENCES public.models(id) ON DELETE SET NULL;
