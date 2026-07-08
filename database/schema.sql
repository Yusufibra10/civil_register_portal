-- =====================================================================
-- Civil Registry Portal (E-Government System)
-- Phase 2 — Database Schema
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- GROUP A: ACCESS CONTROL
-- ---------------------------------------------------------------------

CREATE TABLE roles (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(50)  NOT NULL,
    description     VARCHAR(255) NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_roles_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permissions (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100) NOT NULL,
    module          VARCHAR(50)  NOT NULL,
    description     VARCHAR(255) NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_permissions_name (name),
    KEY idx_permissions_module (module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE role_permissions (
    role_id         INT UNSIGNED NOT NULL,
    permission_id   INT UNSIGNED NOT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_rp_role FOREIGN KEY (role_id) REFERENCES roles(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_rp_permission FOREIGN KEY (permission_id) REFERENCES permissions(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id         INT UNSIGNED NOT NULL,
    full_name       VARCHAR(150) NOT NULL,
    email           VARCHAR(150) NOT NULL,
    username        VARCHAR(50)  NOT NULL,
    password        VARCHAR(255) NOT NULL,
    phone           VARCHAR(20)  NULL,
    profile_photo   VARCHAR(255) NULL,
    status          ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
    last_login_at   TIMESTAMP NULL DEFAULT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uq_users_email (email),
    UNIQUE KEY uq_users_username (username),
    KEY idx_users_role (role_id),
    KEY idx_users_status (status),
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Added in Phase 4 for "Remember Me": selector/validator persistent-login
-- tokens. Never stores the raw token — only a lookup selector and a
-- SHA-256 hash of the validator half.
CREATE TABLE remember_tokens (
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

-- ---------------------------------------------------------------------
-- GROUP B: CIVIL REGISTRY CORE
-- ---------------------------------------------------------------------

CREATE TABLE citizens (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    citizen_uid         VARCHAR(20)  NOT NULL,
    national_id_number  VARCHAR(20)  NULL,
    first_name          VARCHAR(100) NOT NULL,
    middle_name         VARCHAR(100) NULL,
    last_name           VARCHAR(100) NOT NULL,
    gender              ENUM('Male','Female') NOT NULL,
    date_of_birth       DATE NOT NULL,
    place_of_birth      VARCHAR(150) NOT NULL,
    marital_status      ENUM('Single','Married','Divorced','Widowed') NULL,
    nationality         VARCHAR(100) NOT NULL DEFAULT 'Somaliland',
    occupation          VARCHAR(100) NULL,
    phone               VARCHAR(20)  NULL,
    email               VARCHAR(150) NULL,
    address             VARCHAR(255) NULL,
    region              VARCHAR(100) NULL,
    district            VARCHAR(100) NULL,
    village             VARCHAR(100) NULL,
    status              ENUM('active','inactive') NOT NULL DEFAULT 'active',
    registration_date   DATE NOT NULL,
    photo_path          VARCHAR(255) NULL,
    created_by          INT UNSIGNED NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at          TIMESTAMP NULL DEFAULT NULL,
    UNIQUE KEY uq_citizens_uid (citizen_uid),
    UNIQUE KEY uq_citizens_national_id (national_id_number),
    UNIQUE KEY uq_citizens_email (email),
    KEY idx_citizens_name (last_name, first_name),
    KEY idx_citizens_dob (date_of_birth),
    KEY idx_citizens_region_district (region, district),
    KEY idx_citizens_status (status),
    CONSTRAINT fk_citizens_created_by FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE birth_certificates (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    certificate_number      VARCHAR(30) NOT NULL,
    citizen_id              BIGINT UNSIGNED NOT NULL,
    father_citizen_id        BIGINT UNSIGNED NULL,
    father_full_name        VARCHAR(150) NULL,
    father_national_id      VARCHAR(20) NULL,
    mother_citizen_id        BIGINT UNSIGNED NULL,
    mother_full_name        VARCHAR(150) NULL,
    mother_national_id      VARCHAR(20) NULL,
    registration_date       DATE NOT NULL,
    place_of_registration   VARCHAR(150) NULL,
    issued_by               INT UNSIGNED NULL,
    status                  ENUM('active','revoked') NOT NULL DEFAULT 'active',
    created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_bc_certificate_number (certificate_number),
    UNIQUE KEY uq_bc_citizen (citizen_id),
    KEY idx_bc_registration_date (registration_date),
    KEY idx_bc_status (status),
    CONSTRAINT fk_bc_citizen FOREIGN KEY (citizen_id) REFERENCES citizens(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_bc_father FOREIGN KEY (father_citizen_id) REFERENCES citizens(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_bc_mother FOREIGN KEY (mother_citizen_id) REFERENCES citizens(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_bc_issued_by FOREIGN KEY (issued_by) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE national_ids (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    id_number           VARCHAR(20) NOT NULL,
    citizen_id          BIGINT UNSIGNED NOT NULL,
    issue_date          DATE NOT NULL,
    expiry_date         DATE NOT NULL,
    status              ENUM('active','expired','revoked') NOT NULL DEFAULT 'active',
    issued_by           INT UNSIGNED NULL,
    card_printed_at     TIMESTAMP NULL DEFAULT NULL,
    collected_at        TIMESTAMP NULL DEFAULT NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_nid_number (id_number),
    UNIQUE KEY uq_nid_citizen (citizen_id),
    KEY idx_nid_expiry (expiry_date),
    KEY idx_nid_status (status),
    CONSTRAINT fk_nid_citizen FOREIGN KEY (citizen_id) REFERENCES citizens(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_nid_issued_by FOREIGN KEY (issued_by) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- GROUP C: APPLICATIONS & WORKFLOW
-- ---------------------------------------------------------------------

CREATE TABLE application_status (
    id          TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(30) NOT NULL,
    label       VARCHAR(50) NOT NULL,
    sort_order  TINYINT UNSIGNED NOT NULL DEFAULT 0,
    UNIQUE KEY uq_status_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE id_applications (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_number  VARCHAR(30) NOT NULL,
    citizen_id          BIGINT UNSIGNED NOT NULL,
    current_status_id   TINYINT UNSIGNED NOT NULL DEFAULT 1,
    applied_date        DATE NOT NULL,
    assigned_to         INT UNSIGNED NULL,
    remarks             VARCHAR(255) NULL,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_app_number (application_number),
    KEY idx_app_status_date (current_status_id, applied_date),
    KEY idx_app_citizen (citizen_id),
    CONSTRAINT fk_app_citizen FOREIGN KEY (citizen_id) REFERENCES citizens(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_app_status FOREIGN KEY (current_status_id) REFERENCES application_status(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_app_assigned_to FOREIGN KEY (assigned_to) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE id_application_status_history (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id  BIGINT UNSIGNED NOT NULL,
    status_id       TINYINT UNSIGNED NOT NULL,
    changed_by      INT UNSIGNED NULL,
    remarks         VARCHAR(255) NULL,
    changed_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ash_application (application_id, changed_at),
    CONSTRAINT fk_ash_application FOREIGN KEY (application_id) REFERENCES id_applications(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ash_status FOREIGN KEY (status_id) REFERENCES application_status(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_ash_changed_by FOREIGN KEY (changed_by) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- GROUP D: SUPPORTING SYSTEMS
-- ---------------------------------------------------------------------

CREATE TABLE documents (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    citizen_id              BIGINT UNSIGNED NULL,
    birth_certificate_id    BIGINT UNSIGNED NULL,
    id_application_id       BIGINT UNSIGNED NULL,
    document_type           VARCHAR(50) NOT NULL,
    file_path               VARCHAR(255) NOT NULL,
    uploaded_by             INT UNSIGNED NULL,
    uploaded_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_doc_citizen (citizen_id),
    KEY idx_doc_bc (birth_certificate_id),
    KEY idx_doc_app (id_application_id),
    CONSTRAINT fk_doc_citizen FOREIGN KEY (citizen_id) REFERENCES citizens(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_doc_bc FOREIGN KEY (birth_certificate_id) REFERENCES birth_certificates(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_doc_app FOREIGN KEY (id_application_id) REFERENCES id_applications(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_doc_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT chk_doc_single_owner CHECK (
        (citizen_id IS NOT NULL) + (birth_certificate_id IS NOT NULL) + (id_application_id IS NOT NULL) = 1
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    title       VARCHAR(150) NOT NULL,
    message     VARCHAR(255) NOT NULL,
    type        VARCHAR(30) NOT NULL DEFAULT 'info',
    is_read     TINYINT(1) NOT NULL DEFAULT 0,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notif_user_read (user_id, is_read),
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reports (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_type     VARCHAR(50) NOT NULL,
    date_from       DATE NULL,
    date_to         DATE NULL,
    filters         JSON NULL,
    file_path       VARCHAR(255) NULL,
    generated_by    INT UNSIGNED NULL,
    generated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_reports_type_date (report_type, generated_at),
    CONSTRAINT fk_reports_generated_by FOREIGN KEY (generated_by) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE settings (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key     VARCHAR(100) NOT NULL,
    setting_value   TEXT NULL,
    setting_group   VARCHAR(50) NOT NULL DEFAULT 'general',
    updated_by      INT UNSIGNED NULL,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_settings_key (setting_key),
    CONSTRAINT fk_settings_updated_by FOREIGN KEY (updated_by) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activity_logs (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NULL,
    action          VARCHAR(100) NOT NULL,
    module          VARCHAR(50) NULL,
    description     VARCHAR(255) NULL,
    ip_address      VARCHAR(45) NULL,
    user_agent      VARCHAR(255) NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_logs_user_date (user_id, created_at),
    KEY idx_logs_date (created_at),
    KEY idx_logs_action_date (action, created_at),
    CONSTRAINT fk_logs_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- SEED: application_status lookup values
-- ---------------------------------------------------------------------
-- Seven-step workflow (Phase 5B): submitted/received/in_review lead to
-- either approved (-> card_printed -> ready_for_collection) or rejected,
-- a terminal branch. See helpers/applications_repository.php for the
-- transition graph this drives.
INSERT INTO application_status (code, label, sort_order) VALUES
    ('submitted',           'Submitted',            1),
    ('received',            'Received',             2),
    ('in_review',           'Under Review',         3),
    ('approved',            'Approved',             4),
    ('rejected',            'Rejected',             5),
    ('card_printed',        'Card Printed',         6),
    ('ready_for_collection','Ready for Collection', 7);

-- ---------------------------------------------------------------------
-- SEED: roles (Phase 4)
-- Demo user accounts for each role are created by database/seed.php,
-- which hashes passwords with PHP's password_hash() rather than storing
-- a hash in raw SQL.
-- ---------------------------------------------------------------------
INSERT INTO roles (name, description) VALUES
    ('Admin',   'Full system access, including user management and settings.'),
    ('Officer', 'Registers and processes citizens, certificates, and applications.'),
    ('Viewer',  'Read-only access to records and reports.');

-- ---------------------------------------------------------------------
-- SEED: permissions + role_permissions (Phase 6)
-- A "view / manage" pair per module is deliberately coarse — enough to
-- scale to new modules without an admin having to reason about dozens
-- of individually-named actions. See helpers/roles_repository.php.
-- ---------------------------------------------------------------------
INSERT INTO permissions (name, module, description) VALUES
    ('citizens.view',            'Citizens',           'View citizen records'),
    ('citizens.manage',          'Citizens',           'Create, edit, and delete citizen records'),
    ('birth_certificates.view',  'Birth Certificates', 'View birth certificates'),
    ('birth_certificates.manage','Birth Certificates', 'Register, edit, and revoke birth certificates'),
    ('national_ids.view',        'National ID',        'View National ID cards'),
    ('national_ids.manage',      'National ID',        'Mark National ID cards as collected'),
    ('applications.view',        'Applications',       'View ID applications'),
    ('applications.manage',      'Applications',       'Submit applications and change their status'),
    ('reports.view',             'Reports',            'View and export reports'),
    ('users.view',               'Users',              'View user accounts'),
    ('users.manage',             'Users',              'Create, edit, delete, and reassign user accounts'),
    ('roles.manage',             'Roles',              'Manage roles and their permission grants'),
    ('settings.manage',          'Settings',           'Manage system settings'),
    ('activity_logs.view',       'Audit',              'View the system activity log');

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p WHERE r.name = 'Admin';

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name = 'Officer'
  AND p.name IN (
      'citizens.view', 'citizens.manage',
      'birth_certificates.view', 'birth_certificates.manage',
      'national_ids.view', 'national_ids.manage',
      'applications.view', 'applications.manage',
      'reports.view'
  );

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name = 'Viewer'
  AND p.name IN (
      'citizens.view', 'birth_certificates.view',
      'national_ids.view', 'applications.view', 'reports.view'
  );
