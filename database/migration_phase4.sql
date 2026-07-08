-- =====================================================================
-- Phase 4 migration — run this ONLY if database/schema.sql was already
-- imported before Phase 4. It adds what authentication needs: the
-- remember-me table and the three application roles.
-- Safe to run once; re-running is harmless (CREATE TABLE will simply
-- fail with "already exists" if applied twice, and the INSERT below is
-- idempotent via ON DUPLICATE KEY UPDATE).
-- =====================================================================

CREATE TABLE IF NOT EXISTS remember_tokens (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    selector        CHAR(24) NOT NULL,
    validator_hash  CHAR(64) NOT NULL,
    expires_at      DATETIME NOT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_remember_selector (selector),
    KEY idx_remember_user (user_id),
    KEY idx_remember_expires (expires_at),
    CONSTRAINT fk_remember_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO roles (name, description) VALUES
    ('Admin',   'Full system access, including user management and settings.'),
    ('Officer', 'Registers and processes citizens, certificates, and applications.'),
    ('Viewer',  'Read-only access to records and reports.')
ON DUPLICATE KEY UPDATE description = VALUES(description);
