-- =====================================================================
-- Phase 5B migration — run this ONLY if an earlier schema/migration was
-- already imported. Expands the application workflow from 5 to 7 steps
-- and adds two indexes the new list/filter pages rely on.
--
-- The workflow rename below UPDATEs rows in place rather than deleting
-- and re-inserting, specifically so any id_applications row that already
-- references the old 'pending' status id keeps pointing at a valid,
-- correctly-labeled row instead of being silently orphaned.
-- =====================================================================

UPDATE application_status SET code = 'submitted', label = 'Submitted', sort_order = 1 WHERE code = 'pending';
UPDATE application_status SET label = 'Under Review', sort_order = 3 WHERE code = 'in_review';
UPDATE application_status SET sort_order = 4 WHERE code = 'approved';
UPDATE application_status SET sort_order = 5 WHERE code = 'rejected';
UPDATE application_status SET sort_order = 7 WHERE code = 'ready_for_collection';

INSERT INTO application_status (code, label, sort_order) VALUES
    ('received', 'Received', 2),
    ('card_printed', 'Card Printed', 6)
ON DUPLICATE KEY UPDATE label = VALUES(label), sort_order = VALUES(sort_order);

ALTER TABLE birth_certificates ADD KEY idx_bc_status (status);
ALTER TABLE national_ids ADD KEY idx_nid_status (status);
