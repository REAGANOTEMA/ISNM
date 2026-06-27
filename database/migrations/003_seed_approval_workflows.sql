-- Seed approval workflows and stages for all department categories
-- Run this against igangaschoolofl_staffs_db

-- Insert approval workflows
INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_workflows (workflow_name, category, description, is_active) VALUES
('General Department Request', 'General Administration', 'Standard approval workflow for general administrative requests requiring Director General sign-off', 1),
('HR Request', 'Human Resources', 'HR-related requests requiring Director General approval', 1),
('Finance Request', 'Finance', 'Financial requests and budget approvals requiring Director General sign-off', 1),
('ICT Request', 'ICT', 'ICT department requests requiring departmental review and Director General approval', 1),
('Academic Request', 'Academic', 'Academic affairs requests requiring Director General approval', 1),
('Admissions Request', 'Admissions', 'Admissions-related requests requiring Director General approval', 1),
('Library Request', 'Library', 'Library resource and service requests requiring Director General approval', 1),
('Store Requisition', 'Store & Assets', 'Store and asset requisitions requiring Director General approval', 1);

-- Insert stages for ICT Request workflow (2-stage: Director ICT -> Director General)
SET @ict_wf = (SELECT id FROM igangaschoolofl_staffs_db.approval_workflows WHERE workflow_name = 'ICT Request' LIMIT 1);

INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_stages (workflow_id, stage_name, stage_order, assigned_role_id, assigned_role_name, is_final) VALUES
(@ict_wf, 'Director ICT Review', 1, NULL, 'Director ICT', 0),
(@ict_wf, 'Director General Final Approval', 2, NULL, 'Director General', 1);

-- Insert stages for General Department Request (1-stage: DG direct)
SET @gen_wf = (SELECT id FROM igangaschoolofl_staffs_db.approval_workflows WHERE workflow_name = 'General Department Request' LIMIT 1);

INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_stages (workflow_id, stage_name, stage_order, assigned_role_id, assigned_role_name, is_final) VALUES
(@gen_wf, 'Director General Approval', 1, NULL, 'Director General', 1);

-- Insert stages for HR Request
SET @hr_wf = (SELECT id FROM igangaschoolofl_staffs_db.approval_workflows WHERE workflow_name = 'HR Request' LIMIT 1);

INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_stages (workflow_id, stage_name, stage_order, assigned_role_id, assigned_role_name, is_final) VALUES
(@hr_wf, 'Director General Approval', 1, NULL, 'Director General', 1);

-- Insert stages for Finance Request
SET @fin_wf = (SELECT id FROM igangaschoolofl_staffs_db.approval_workflows WHERE workflow_name = 'Finance Request' LIMIT 1);

INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_stages (workflow_id, stage_name, stage_order, assigned_role_id, assigned_role_name, is_final) VALUES
(@fin_wf, 'Director General Approval', 1, NULL, 'Director General', 1);

-- Insert stages for Academic Request
SET @acad_wf = (SELECT id FROM igangaschoolofl_staffs_db.approval_workflows WHERE workflow_name = 'Academic Request' LIMIT 1);

INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_stages (workflow_id, stage_name, stage_order, assigned_role_id, assigned_role_name, is_final) VALUES
(@acad_wf, 'Director General Approval', 1, NULL, 'Director General', 1);

-- Insert stages for Admissions Request
SET @adm_wf = (SELECT id FROM igangaschoolofl_staffs_db.approval_workflows WHERE workflow_name = 'Admissions Request' LIMIT 1);

INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_stages (workflow_id, stage_name, stage_order, assigned_role_id, assigned_role_name, is_final) VALUES
(@adm_wf, 'Director General Approval', 1, NULL, 'Director General', 1);

-- Insert stages for Library Request
SET @lib_wf = (SELECT id FROM igangaschoolofl_staffs_db.approval_workflows WHERE workflow_name = 'Library Request' LIMIT 1);

INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_stages (workflow_id, stage_name, stage_order, assigned_role_id, assigned_role_name, is_final) VALUES
(@lib_wf, 'Director General Approval', 1, NULL, 'Director General', 1);

-- Insert stages for Store Requisition
SET @store_wf = (SELECT id FROM igangaschoolofl_staffs_db.approval_workflows WHERE workflow_name = 'Store Requisition' LIMIT 1);

INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_stages (workflow_id, stage_name, stage_order, assigned_role_id, assigned_role_name, is_final) VALUES
(@store_wf, 'Director General Approval', 1, NULL, 'Director General', 1);
