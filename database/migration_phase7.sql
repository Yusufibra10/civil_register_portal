-- =====================================================================
-- Phase 7 migration — run this ONLY if an earlier schema/migration was
-- already imported. Adds an index to support the rate-limiting checks
-- introduced in Phase 7 (login throttle, public verify/track lookup
-- throttle), which filter activity_logs by action + created_at. Without
-- this index those queries fall back to a full scan of idx_logs_date.
-- Unlike the data-seeding migrations for earlier phases, this is a
-- one-time schema change: running it twice on the same database will
-- fail with "Duplicate key name" (harmlessly — it just means the index
-- is already there).
-- =====================================================================

ALTER TABLE activity_logs
    ADD INDEX idx_logs_action_date (action, created_at);
