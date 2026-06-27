-- Seed approval workflows and stages for all department categories
-- Run this against igangaschoolofl_staffs_db

-- 0. Create a procedure to add missing columns safely (runs in a single connection).
DROP PROCEDURE IF EXISTS sp_fix_stage_cols;
DELIMITER //
CREATE PROCEDURE sp_fix_stage_cols()
BEGIN
  IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA='igangaschoolofl_staffs_db' AND TABLE_NAME='approval_stages' AND COLUMN_NAME='assigned_role_id') THEN
    ALTER TABLE igangaschoolofl_staffs_db.approval_stages ADD COLUMN assigned_role_id INT DEFAULT NULL;
  END IF;
  IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA='igangaschoolofl_staffs_db' AND TABLE_NAME='approval_stages' AND COLUMN_NAME='assigned_role_name') THEN
    ALTER TABLE igangaschoolofl_staffs_db.approval_stages ADD COLUMN assigned_role_name VARCHAR(255) DEFAULT NULL;
  END IF;
  IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA='igangaschoolofl_staffs_db' AND TABLE_NAME='approval_stages' AND COLUMN_NAME='is_final') THEN
    ALTER TABLE igangaschoolofl_staffs_db.approval_stages ADD COLUMN is_final TINYINT(1) DEFAULT 0;
  END IF;
END //
DELIMITER ;
CALL sp_fix_stage_cols();
DROP PROCEDURE IF EXISTS sp_fix_stage_cols;

-- 1. Remove legacy workflow from previous migration (keep Student Registration — used by approval_integration.php)
DELETE s FROM igangaschoolofl_staffs_db.approval_stages s
  LEFT JOIN igangaschoolofl_staffs_db.approval_workflows w ON s.workflow_id = w.id
  WHERE w.workflow_name = 'General Institution Approval';

DELETE FROM igangaschoolofl_staffs_db.approval_workflows
  WHERE workflow_name = 'General Institution Approval';

-- 2. Update Store Requisition category if it exists from old migration
UPDATE igangaschoolofl_staffs_db.approval_workflows
  SET category = 'Store & Assets'
  WHERE workflow_name = 'Store Requisition' AND category = 'Store';

-- 3. Clear stages first (lets us recreate cleanly), then dedup+index both tables
DELETE FROM igangaschoolofl_staffs_db.approval_stages;

-- Indexes already exist from a prior run — skipping.

-- 5. Insert approval workflows
INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_workflows (workflow_name, category, description, is_active) VALUES
('General Department Request', 'General Administration', 'Standard approval workflow for general administrative requests requiring Director General sign-off', 1),
('HR Request', 'Human Resources', 'HR-related requests requiring Director General approval', 1),
('Finance Request', 'Finance', 'Financial requests and budget approvals requiring Director General sign-off', 1),
('ICT Request', 'ICT', 'ICT department requests requiring departmental review and Director General approval', 1),
('Academic Request', 'Academic', 'Academic affairs requests requiring Director General approval', 1),
('Admissions Request', 'Admissions', 'Admissions-related requests requiring Director General approval', 1),
('Library Request', 'Library', 'Library resource and service requests requiring Director General approval', 1),
('Store Requisition', 'Store & Assets', 'Store and asset requisitions requiring Director General approval', 1),
('Student Registration', 'Academic', 'Student registration requests requiring Director General approval', 1);

-- 6. Insert stages for ICT Request workflow (2-stage: Director ICT -> Director General)
INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_stages (workflow_id, stage_name, stage_order, assigned_role_id, assigned_role_name, is_final)
SELECT id, 'Director ICT Review', 1, NULL, 'Director ICT', 0 FROM igangaschoolofl_staffs_db.approval_workflows WHERE workflow_name = 'ICT Request';

INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_stages (workflow_id, stage_name, stage_order, assigned_role_id, assigned_role_name, is_final)
SELECT id, 'Director General Final Approval', 2, NULL, 'Director General', 1 FROM igangaschoolofl_staffs_db.approval_workflows WHERE workflow_name = 'ICT Request';

-- 7. Insert stages for all other workflows (1-stage: DG direct)
INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_stages (workflow_id, stage_name, stage_order, assigned_role_id, assigned_role_name, is_final)
SELECT id, 'Director General Approval', 1, NULL, 'Director General', 1 FROM igangaschoolofl_staffs_db.approval_workflows WHERE workflow_name = 'General Department Request';

INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_stages (workflow_id, stage_name, stage_order, assigned_role_id, assigned_role_name, is_final)
SELECT id, 'Director General Approval', 1, NULL, 'Director General', 1 FROM igangaschoolofl_staffs_db.approval_workflows WHERE workflow_name = 'HR Request';

INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_stages (workflow_id, stage_name, stage_order, assigned_role_id, assigned_role_name, is_final)
SELECT id, 'Director General Approval', 1, NULL, 'Director General', 1 FROM igangaschoolofl_staffs_db.approval_workflows WHERE workflow_name = 'Finance Request';

INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_stages (workflow_id, stage_name, stage_order, assigned_role_id, assigned_role_name, is_final)
SELECT id, 'Director General Approval', 1, NULL, 'Director General', 1 FROM igangaschoolofl_staffs_db.approval_workflows WHERE workflow_name = 'Academic Request';

INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_stages (workflow_id, stage_name, stage_order, assigned_role_id, assigned_role_name, is_final)
SELECT id, 'Director General Approval', 1, NULL, 'Director General', 1 FROM igangaschoolofl_staffs_db.approval_workflows WHERE workflow_name = 'Admissions Request';

INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_stages (workflow_id, stage_name, stage_order, assigned_role_id, assigned_role_name, is_final)
SELECT id, 'Director General Approval', 1, NULL, 'Director General', 1 FROM igangaschoolofl_staffs_db.approval_workflows WHERE workflow_name = 'Library Request';

INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_stages (workflow_id, stage_name, stage_order, assigned_role_id, assigned_role_name, is_final)
SELECT id, 'Director General Approval', 1, NULL, 'Director General', 1 FROM igangaschoolofl_staffs_db.approval_workflows WHERE workflow_name = 'Store Requisition';

INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_stages (workflow_id, stage_name, stage_order, assigned_role_id, assigned_role_name, is_final)
SELECT id, 'Director General Approval', 1, NULL, 'Director General', 1 FROM igangaschoolofl_staffs_db.approval_workflows WHERE workflow_name = 'Student Registration';
