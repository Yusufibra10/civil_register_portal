-- =====================================================================
-- Phase 5A migration — run this ONLY if database/schema.sql (with the
-- Phase 4 additions) was already imported before Phase 5A. It extends
-- `citizens` with the fields the Citizens Management Module's intake
-- form collects.
--
-- Not idempotent (ALTER TABLE ADD COLUMN fails if a column already
-- exists) — run this exactly once against a database that predates it.
-- =====================================================================

ALTER TABLE citizens
    ADD COLUMN national_id_number VARCHAR(20)  NULL AFTER citizen_uid,
    ADD COLUMN marital_status     ENUM('Single','Married','Divorced','Widowed') NULL AFTER place_of_birth,
    ADD COLUMN nationality        VARCHAR(100) NOT NULL DEFAULT 'Somaliland' AFTER marital_status,
    ADD COLUMN occupation         VARCHAR(100) NULL AFTER nationality,
    ADD COLUMN email              VARCHAR(150) NULL AFTER phone,
    ADD COLUMN region             VARCHAR(100) NULL AFTER address,
    ADD COLUMN district           VARCHAR(100) NULL AFTER region,
    ADD COLUMN village            VARCHAR(100) NULL AFTER district,
    ADD COLUMN status             ENUM('active','inactive') NOT NULL DEFAULT 'active' AFTER village,
    ADD COLUMN registration_date  DATE NOT NULL DEFAULT (CURRENT_DATE) AFTER status;

ALTER TABLE citizens
    ADD UNIQUE KEY uq_citizens_national_id (national_id_number),
    ADD UNIQUE KEY uq_citizens_email (email),
    ADD KEY idx_citizens_region_district (region, district),
    ADD KEY idx_citizens_status (status);

-- The DEFAULT (CURRENT_DATE) above only back-fills registration_date for
-- existing rows at the moment this migration runs. From here on, the
-- application always supplies registration_date explicitly on INSERT
-- (see helpers/citizens_repository.php), so no ongoing default is relied on.
