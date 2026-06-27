-- Seed approval workflows and stages for all department categories
-- Run this against igangaschoolofl_staffs_db

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

-- 3. Remove existing duplicate rows so we can create unique indexes
DELETE t1 FROM igangaschoolofl_staffs_db.approval_workflows t1
  INNER JOIN igangaschoolofl_staffs_db.approval_workflows t2
  WHERE t1.id > t2.id AND t1.workflow_name = t2.workflow_name;

DELETE t1 FROM igangaschoolofl_staffs_db.approval_stages t1
  INNER JOIN igangaschoolofl_staffs_db.approval_stages t2
  WHERE t1.id > t2.id AND t1.workflow_id = t2.workflow_id AND t1.stage_order = t2.stage_order;

-- 4. Add unique constraints so INSERT IGNORE works on subsequent runs
SET @idx1 = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA='igangaschoolofl_staffs_db' AND TABLE_NAME='approval_workflows' AND INDEX_NAME='uq_workflow_name');
SET @sql1 = IF(@idx1 = 0, 'ALTER TABLE igangaschoolofl_staffs_db.approval_workflows ADD UNIQUE INDEX uq_workflow_name (workflow_name)', 'SELECT 1');
PREPARE stmt1 FROM @sql1;
EXECUTE stmt1;
DEALLOCATE PREPARE stmt1;

SET @idx2 = (SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA='igangaschoolofl_staffs_db' AND TABLE_NAME='approval_stages' AND INDEX_NAME='uq_workflow_stage_order');
SET @sql2 = IF(@idx2 = 0, 'ALTER TABLE igangaschoolofl_staffs_db.approval_stages ADD UNIQUE INDEX uq_workflow_stage_order (workflow_id, stage_order)', 'SELECT 1');
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

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
SET @ict_wf = (SELECT id FROM igangaschoolofl_staffs_db.approval_workflows WHERE workflow_name = 'ICT Request' LIMIT 1);

INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_stages (workflow_id, stage_name, stage_order, assigned_role_id, assigned_role_name, is_final) VALUES
(@ict_wf, 'Director ICT Review', 1, NULL, 'Director ICT', 0),
(@ict_wf, 'Director General Final Approval', 2, NULL, 'Director General', 1);

-- 7. Insert stages for General Department Request (1-stage: DG direct)
SET @gen_wf = (SELECT id FROM igangaschoolofl_staffs_db.approval_workflows WHERE workflow_name = 'General Department Request' LIMIT 1);

INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_stages (workflow_id, stage_name, stage_order, assigned_role_id, assigned_role_name, is_final) VALUES
(@gen_wf, 'Director General Approval', 1, NULL, 'Director General', 1);

-- 8. Insert stages for HR Request
SET @hr_wf = (SELECT id FROM igangaschoolofl_staffs_db.approval_workflows WHERE workflow_name = 'HR Request' LIMIT 1);

INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_stages (workflow_id, stage_name, stage_order, assigned_role_id, assigned_role_name, is_final) VALUES
(@hr_wf, 'Director General Approval', 1, NULL, 'Director General', 1);

-- 9. Insert stages for Finance Request
SET @fin_wf = (SELECT id FROM igangaschoolofl_staffs_db.approval_workflows WHERE workflow_name = 'Finance Request' LIMIT 1);

INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_stages (workflow_id, stage_name, stage_order, assigned_role_id, assigned_role_name, is_final) VALUES
(@fin_wf, 'Director General Approval', 1, NULL, 'Director General', 1);

-- 10. Insert stages for Academic Request
SET @acad_wf = (SELECT id FROM igangaschoolofl_staffs_db.approval_workflows WHERE workflow_name = 'Academic Request' LIMIT 1);

INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_stages (workflow_id, stage_name, stage_order, assigned_role_id, assigned_role_name, is_final) VALUES
(@acad_wf, 'Director General Approval', 1, NULL, 'Director General', 1);

-- 11. Insert stages for Admissions Request
SET @adm_wf = (SELECT id FROM igangaschoolofl_staffs_db.approval_workflows WHERE workflow_name = 'Admissions Request' LIMIT 1);

INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_stages (workflow_id, stage_name, stage_order, assigned_role_id, assigned_role_name, is_final) VALUES
(@adm_wf, 'Director General Approval', 1, NULL, 'Director General', 1);

-- 12. Insert stages for Library Request
SET @lib_wf = (SELECT id FROM igangaschoolofl_staffs_db.approval_workflows WHERE workflow_name = 'Library Request' LIMIT 1);

INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_stages (workflow_id, stage_name, stage_order, assigned_role_id, assigned_role_name, is_final) VALUES
(@lib_wf, 'Director General Approval', 1, NULL, 'Director General', 1);

-- 13. Insert stages for Store Requisition
SET @store_wf = (SELECT id FROM igangaschoolofl_staffs_db.approval_workflows WHERE workflow_name = 'Store Requisition' LIMIT 1);

INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_stages (workflow_id, stage_name, stage_order, assigned_role_id, assigned_role_name, is_final) VALUES
(@store_wf, 'Director General Approval', 1, NULL, 'Director General', 1);
