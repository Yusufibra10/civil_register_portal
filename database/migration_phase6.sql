-- =====================================================================
-- Phase 6 migration — run this ONLY if an earlier schema/migration was
-- already imported. Seeds the permissions catalog and the grants for
-- the three built-in roles. Idempotent: safe to run more than once.
-- =====================================================================

INSERT INTO permissions (name, module, description) VALUES
    ('citizens.view',          'Citizens',          'View citizen records'),
    ('citizens.manage',        'Citizens',          'Create, edit, and delete citizen records'),
    ('birth_certificates.view','Birth Certificates','View birth certificates'),
    ('birth_certificates.manage','Birth Certificates','Register, edit, and revoke birth certificates'),
    ('national_ids.view',      'National ID',       'View National ID cards'),
    ('national_ids.manage',    'National ID',       'Mark National ID cards as collected'),
    ('applications.view',      'Applications',      'View ID applications'),
    ('applications.manage',    'Applications',      'Submit applications and change their status'),
    ('reports.view',           'Reports',           'View and export reports'),
    ('users.view',             'Users',             'View user accounts'),
    ('users.manage',           'Users',             'Create, edit, delete, and reassign user accounts'),
    ('roles.manage',           'Roles',             'Manage roles and their permission grants'),
    ('settings.manage',        'Settings',          'Manage system settings'),
    ('activity_logs.view',     'Audit',             'View the system activity log')
ON DUPLICATE KEY UPDATE module = VALUES(module), description = VALUES(description);

-- Admin: every permission.
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'Admin'
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Officer: operational modules, view + manage, but no administrative modules.
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'Officer'
  AND p.name IN (
      'citizens.view', 'citizens.manage',
      'birth_certificates.view', 'birth_certificates.manage',
      'national_ids.view', 'national_ids.manage',
      'applications.view', 'applications.manage',
      'reports.view'
  )
ON DUPLICATE KEY UPDATE role_id = role_id;

-- Viewer: read-only across records and reports.
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'Viewer'
  AND p.name IN (
      'citizens.view', 'birth_certificates.view',
      'national_ids.view', 'applications.view', 'reports.view'
  )
ON DUPLICATE KEY UPDATE role_id = role_id;
