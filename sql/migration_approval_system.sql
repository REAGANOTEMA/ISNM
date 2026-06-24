-- ============================================================
-- Migration: Approval System Schema Fixes & Enhancements
-- Run this on igangaschoolofl_staffs_db
-- ============================================================

-- 1. Fix store_requests.status ENUM — add missing values
ALTER TABLE `store_requests` 
MODIFY COLUMN `status` enum(
  'draft','pending','forwarded','pending_approval',
  'approved','rejected','partially_fulfilled','fulfilled','cancelled'
) DEFAULT 'pending';

-- 2. Fix store_orders.status ENUM — add pending_approval if missing
ALTER TABLE `store_orders`
MODIFY COLUMN `status` enum(
  'draft','pending_approval','approved','ordered',
  'partially_received','received','cancelled'
) DEFAULT 'draft';

-- 3. Add approval_request_id to store_orders if not exists
SET @dbname = 'igangaschoolofl_staffs_db';
SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS 
  WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'store_orders' AND COLUMN_NAME = 'approval_request_id');
SET @sql = IF(@exists = 0,
  'ALTER TABLE store_orders ADD COLUMN approval_request_id INT NULL AFTER status',
  'SELECT "approval_request_id already exists" AS status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Add approval_request_id to store_requests if not exists (ensure it's there)
SET @exists2 = (SELECT COUNT(*) FROM information_schema.COLUMNS 
  WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'store_requests' AND COLUMN_NAME = 'approval_request_id');
SET @sql2 = IF(@exists2 = 0,
  'ALTER TABLE store_requests ADD COLUMN approval_request_id INT NULL AFTER forwarded_to_role',
  'SELECT "store_requests.approval_request_id already exists" AS status');
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- 5. Add original_request_id to approval_requests (for linking back to source)
SET @exists3 = (SELECT COUNT(*) FROM information_schema.COLUMNS 
  WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'approval_requests' AND COLUMN_NAME = 'original_request_id');
SET @sql3 = IF(@exists3 = 0,
  'ALTER TABLE approval_requests ADD COLUMN original_request_id INT NULL AFTER reference_id, ADD COLUMN resubmitted_at DATETIME NULL AFTER original_request_id',
  'SELECT "approval_requests original_request_id already exists" AS status');
PREPARE stmt3 FROM @sql3;
EXECUTE stmt3;
DEALLOCATE PREPARE stmt3;

-- 6. Ensure approval_requests.status ENUM includes 'Returned'
SET @exists4 = (SELECT COUNT(*) FROM information_schema.COLUMNS 
  WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'approval_requests' AND COLUMN_NAME = 'status');
SET @statusType = (SELECT COLUMN_TYPE FROM information_schema.COLUMNS 
  WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'approval_requests' AND COLUMN_NAME = 'status');
-- If it's an enum without 'Returned', alter it
SET @sql4 = IF(@statusType IS NOT NULL AND @statusType NOT LIKE '%Returned%',
  'ALTER TABLE approval_requests MODIFY COLUMN status enum(''Active'',''Approved'',''Rejected'',''Returned'',''Cancelled'') DEFAULT ''Active''',
  'SELECT "approval_requests.status already has Returned" AS status');
PREPARE stmt4 FROM @sql4;
EXECUTE stmt4;
DEALLOCATE PREPARE stmt4;

-- 7. Create general approval workflows if they don't exist
INSERT IGNORE INTO approval_workflows (workflow_name, category, description, is_active, created_at)
VALUES 
  ('General Department Request', 'General Administration', 'Generic approval request from any department', 1, NOW()),
  ('HR Request', 'Human Resources', 'HR-related approval requests (recruitment, promotion, leave)', 1, NOW()),
  ('ICT Request', 'ICT', 'ICT-related approval requests (hardware, software, upgrades)', 1, NOW()),
  ('Library Request', 'Library', 'Library procurement and resource requests', 1, NOW()),
  ('Finance Request', 'Finance', 'Financial approval requests (budget, expenditure, refund)', 1, NOW()),
  ('Academic Request', 'Academic', 'Academic approval requests (curriculum, graduation, clearance)', 1, NOW()),
  ('Admissions Request', 'Admissions', 'Admissions-related approval requests', 1, NOW());

-- 8. Create approval stages for each workflow (single-stage DG approval)
INSERT IGNORE INTO approval_stages (workflow_id, stage_name, stage_order, assigned_role_id, created_at)
SELECT w.id, 'Director General Review', 1, NULL, NOW()
FROM approval_workflows w
WHERE w.is_active = 1
AND NOT EXISTS (
  SELECT 1 FROM approval_stages s WHERE s.workflow_id = w.id
);

-- 9. Add indexes for performance
SET @idxExists = (SELECT COUNT(*) FROM information_schema.STATISTICS 
  WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'approval_requests' AND INDEX_NAME = 'idx_requester_status');
SET @sql9 = IF(@idxExists = 0,
  'ALTER TABLE approval_requests ADD INDEX idx_requester_status (requester_id, status)',
  'SELECT "idx_requester_status already exists" AS status');
PREPARE stmt9 FROM @sql9;
EXECUTE stmt9;
DEALLOCATE PREPARE stmt9;

SET @idxExists2 = (SELECT COUNT(*) FROM information_schema.STATISTICS 
  WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'approval_requests' AND INDEX_NAME = 'idx_reference');
SET @sql10 = IF(@idxExists2 = 0,
  'ALTER TABLE approval_requests ADD INDEX idx_reference (reference_type, reference_id)',
  'SELECT "idx_reference already exists" AS status');
PREPARE stmt10 FROM @sql10;
EXECUTE stmt10;
DEALLOCATE PREPARE stmt10;
