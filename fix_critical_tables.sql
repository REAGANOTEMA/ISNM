-- ============================================================
-- ISNM CRITICAL TABLES MIGRATION
-- Run this SQL on production to fix missing/malformed tables
-- Database: igangaschool_staffs (staff database)
-- Safe to run multiple times (IF NOT EXISTS / ADD COLUMN IF NOT EXISTS)
-- ============================================================

-- ──────────────────────────────────────────────
-- 1. BUDGET RECORDS (CRITICAL - was NEVER created)
-- Used by director-finance.php for budget management
-- ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `budget_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `budget_code` VARCHAR(50) DEFAULT '',
    `budget_name` VARCHAR(200) DEFAULT '',
    `budget_category` VARCHAR(100) DEFAULT '',
    `department` VARCHAR(100) DEFAULT '',
    `fiscal_year` VARCHAR(20) DEFAULT '',
    `allocated_amount` DECIMAL(14,2) DEFAULT 0,
    `spent_amount` DECIMAL(14,2) DEFAULT 0,
    `status` VARCHAR(50) DEFAULT 'Draft',
    `created_by` INT DEFAULT 0,
    `approved_by` INT DEFAULT 0,
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_br_status (`status`),
    INDEX idx_br_fiscal (`fiscal_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────
-- 2. APPROVAL REQUESTS (schema mismatch fix)
-- Code uses: workflow_id, request_number, requester_name, requester_role,
--   current_stage_id, current_stage_order, priority, reference_type,
--   reference_id, reference_url, approved_by, final_approval_at,
--   rejection_reason, updated_at, status='Active'|'Approved'|'Rejected'|'Returned'
-- Old migration had: ENUM('pending','approved','rejected') — WRONG
-- ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `approval_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `workflow_id` INT DEFAULT 0,
    `request_number` VARCHAR(100) DEFAULT '',
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `priority` VARCHAR(50) DEFAULT 'Medium',
    `requester_id` INT DEFAULT 0,
    `requester_name` VARCHAR(200) DEFAULT '',
    `requester_role` VARCHAR(100) DEFAULT '',
    `current_stage_id` INT DEFAULT 0,
    `current_stage_order` INT DEFAULT 1,
    `status` VARCHAR(50) DEFAULT 'Active',
    `reference_type` VARCHAR(100) DEFAULT NULL,
    `reference_id` INT DEFAULT NULL,
    `reference_url` VARCHAR(500) DEFAULT NULL,
    `approved_by` INT DEFAULT NULL,
    `final_approval_at` DATETIME DEFAULT NULL,
    `rejection_reason` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ar_status (`status`),
    INDEX idx_ar_requester (`requester_id`),
    INDEX idx_ar_workflow (`workflow_id`),
    INDEX idx_ar_ref (`reference_type`, `reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add any missing columns to existing approval_requests
ALTER TABLE `approval_requests` ADD COLUMN IF NOT EXISTS `workflow_id` INT DEFAULT 0;
ALTER TABLE `approval_requests` ADD COLUMN IF NOT EXISTS `request_number` VARCHAR(100) DEFAULT '';
ALTER TABLE `approval_requests` ADD COLUMN IF NOT EXISTS `requester_name` VARCHAR(200) DEFAULT '';
ALTER TABLE `approval_requests` ADD COLUMN IF NOT EXISTS `requester_role` VARCHAR(100) DEFAULT '';
ALTER TABLE `approval_requests` ADD COLUMN IF NOT EXISTS `current_stage_id` INT DEFAULT 0;
ALTER TABLE `approval_requests` ADD COLUMN IF NOT EXISTS `current_stage_order` INT DEFAULT 1;
ALTER TABLE `approval_requests` ADD COLUMN IF NOT EXISTS `priority` VARCHAR(50) DEFAULT 'Medium';
ALTER TABLE `approval_requests` ADD COLUMN IF NOT EXISTS `reference_type` VARCHAR(100) DEFAULT NULL;
ALTER TABLE `approval_requests` ADD COLUMN IF NOT EXISTS `reference_id` INT DEFAULT NULL;
ALTER TABLE `approval_requests` ADD COLUMN IF NOT EXISTS `reference_url` VARCHAR(500) DEFAULT NULL;
ALTER TABLE `approval_requests` ADD COLUMN IF NOT EXISTS `approved_by` INT DEFAULT NULL;
ALTER TABLE `approval_requests` ADD COLUMN IF NOT EXISTS `final_approval_at` DATETIME DEFAULT NULL;
ALTER TABLE `approval_requests` ADD COLUMN IF NOT EXISTS `rejection_reason` TEXT;
ALTER TABLE `approval_requests` ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE `approval_requests` ADD COLUMN IF NOT EXISTS `requester_type` VARCHAR(20) DEFAULT 'staff';

-- ──────────────────────────────────────────────
-- 3. APPROVAL WORKFLOWS
-- ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `approval_workflows` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `workflow_name` VARCHAR(255) DEFAULT '',
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `category` VARCHAR(100) DEFAULT '',
    `target_table` VARCHAR(100),
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE `approval_workflows` ADD COLUMN IF NOT EXISTS `workflow_name` VARCHAR(255) DEFAULT '';
ALTER TABLE `approval_workflows` ADD COLUMN IF NOT EXISTS `category` VARCHAR(100) DEFAULT '';

-- ──────────────────────────────────────────────
-- 4. APPROVAL STAGES (schema mismatch fix)
-- Code uses: stage_order, is_final, assigned_role_id, assigned_role_name
-- Old migration used: approval_order, approver_role — WRONG column names
-- ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `approval_stages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `workflow_id` INT NOT NULL,
    `stage_name` VARCHAR(255) NOT NULL,
    `stage_order` INT DEFAULT 1,
    `approver_role` VARCHAR(100) DEFAULT '',
    `assigned_role_id` INT DEFAULT 0,
    `assigned_role_name` VARCHAR(100) DEFAULT '',
    `is_final` TINYINT(1) DEFAULT 0,
    `is_mandatory` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_as_workflow (`workflow_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE `approval_stages` ADD COLUMN IF NOT EXISTS `stage_order` INT DEFAULT 1;
ALTER TABLE `approval_stages` ADD COLUMN IF NOT EXISTS `is_final` TINYINT(1) DEFAULT 0;
ALTER TABLE `approval_stages` ADD COLUMN IF NOT EXISTS `assigned_role_id` INT DEFAULT 0;
ALTER TABLE `approval_stages` ADD COLUMN IF NOT EXISTS `assigned_role_name` VARCHAR(100) DEFAULT '';

-- ──────────────────────────────────────────────
-- 5. APPROVAL ACTIONS (schema mismatch fix)
-- Code uses: request_id, action_by, action_type, comments, notes, decision, previous_stage_order
-- Old migration had: approval_request_id, approver_id, action — WRONG
-- ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `approval_actions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `request_id` INT DEFAULT 0,
    `approval_request_id` INT DEFAULT 0,
    `stage_id` INT DEFAULT 0,
    `action_by` INT DEFAULT 0,
    `approver_id` INT DEFAULT 0,
    `action_type` VARCHAR(50) DEFAULT '',
    `action` VARCHAR(20) DEFAULT '',
    `comments` TEXT,
    `notes` TEXT,
    `decision` VARCHAR(50) DEFAULT '',
    `previous_stage_order` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_aa_request (`request_id`),
    INDEX idx_aa_action_by (`action_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE `approval_actions` ADD COLUMN IF NOT EXISTS `request_id` INT DEFAULT 0;
ALTER TABLE `approval_actions` ADD COLUMN IF NOT EXISTS `approval_request_id` INT DEFAULT 0;
ALTER TABLE `approval_actions` ADD COLUMN IF NOT EXISTS `action_by` INT DEFAULT 0;
ALTER TABLE `approval_actions` ADD COLUMN IF NOT EXISTS `action_type` VARCHAR(50) DEFAULT '';
ALTER TABLE `approval_actions` ADD COLUMN IF NOT EXISTS `notes` TEXT;
ALTER TABLE `approval_actions` ADD COLUMN IF NOT EXISTS `decision` VARCHAR(50) DEFAULT '';
ALTER TABLE `approval_actions` ADD COLUMN IF NOT EXISTS `previous_stage_order` INT DEFAULT 0;

-- ──────────────────────────────────────────────
-- 6. STORE REQUESTS (ensure all columns exist)
-- ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `store_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `request_number` VARCHAR(50) DEFAULT '',
    `requested_by` INT DEFAULT 0,
    `requester_name` VARCHAR(255) DEFAULT '',
    `requester_role` VARCHAR(50) DEFAULT '',
    `department` VARCHAR(100) DEFAULT '',
    `urgency` VARCHAR(50) DEFAULT 'Normal',
    `status` VARCHAR(50) DEFAULT 'pending',
    `notes` TEXT,
    `items` TEXT,
    `rejection_reason` TEXT,
    `fulfilled_by` INT DEFAULT NULL,
    `fulfilled_at` DATETIME DEFAULT NULL,
    `approved_by` INT DEFAULT NULL,
    `approved_at` DATETIME DEFAULT NULL,
    `approval_request_id` INT DEFAULT NULL,
    `forwarded_to` INT DEFAULT NULL,
    `forwarded_to_role` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sr_status (`status`),
    INDEX idx_sr_by (`requested_by`),
    INDEX idx_sr_number (`request_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE `store_requests` ADD COLUMN IF NOT EXISTS `approved_by` INT DEFAULT NULL;
ALTER TABLE `store_requests` ADD COLUMN IF NOT EXISTS `approved_at` DATETIME DEFAULT NULL;
ALTER TABLE `store_requests` ADD COLUMN IF NOT EXISTS `approval_request_id` INT DEFAULT NULL;
ALTER TABLE `store_requests` ADD COLUMN IF NOT EXISTS `forwarded_to` INT DEFAULT NULL;
ALTER TABLE `store_requests` ADD COLUMN IF NOT EXISTS `forwarded_to_role` VARCHAR(100) DEFAULT NULL;
ALTER TABLE `store_requests` ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;
ALTER TABLE `store_requests` ADD COLUMN IF NOT EXISTS `rejection_reason` TEXT;
ALTER TABLE `store_requests` ADD COLUMN IF NOT EXISTS `fulfilled_by` INT DEFAULT NULL;
ALTER TABLE `store_requests` ADD COLUMN IF NOT EXISTS `fulfilled_at` DATETIME DEFAULT NULL;

-- ──────────────────────────────────────────────
-- 7. STORE REQUEST ITEMS
-- ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `store_request_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `request_id` INT DEFAULT 0,
    `item_id` INT DEFAULT 0,
    `quantity_requested` DECIMAL(14,2) DEFAULT 0,
    `quantity_fulfilled` DECIMAL(14,2) DEFAULT 0,
    `notes` TEXT,
    `status` VARCHAR(50) DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sri_request (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────
-- 8. STORE CATEGORIES
-- ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `store_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_name` VARCHAR(200) DEFAULT '',
    `description` TEXT,
    `status` VARCHAR(20) DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────
-- 9. STORE INVENTORY
-- ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `store_inventory` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_code` VARCHAR(50) DEFAULT '',
    `item_name` VARCHAR(200) DEFAULT '',
    `category_id` INT DEFAULT NULL,
    `unit` VARCHAR(50) DEFAULT '',
    `quantity` DECIMAL(14,2) DEFAULT 0,
    `reorder_level` DECIMAL(14,2) DEFAULT 0,
    `unit_cost` DECIMAL(14,2) DEFAULT 0,
    `location` VARCHAR(200) DEFAULT '',
    `batch_number` VARCHAR(100) DEFAULT NULL,
    `expiry_date` DATE DEFAULT NULL,
    `supplier` VARCHAR(200) DEFAULT '',
    `status` VARCHAR(20) DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_si_status (`status`),
    INDEX idx_si_category (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────
-- 10. STORE INVENTORY TRANSACTIONS
-- ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `store_inventory_transactions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `item_id` INT DEFAULT 0,
    `transaction_type` VARCHAR(50) DEFAULT '',
    `quantity` DECIMAL(14,2) DEFAULT 0,
    `quantity_before` DECIMAL(14,2) DEFAULT NULL,
    `quantity_after` DECIMAL(14,2) DEFAULT NULL,
    `reason` TEXT,
    `created_by` INT DEFAULT NULL,
    `reference_type` VARCHAR(50) DEFAULT NULL,
    `reference_id` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sit_item (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────
-- 11. BUDGET APPROVALS
-- ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `budget_approvals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `budget_id` INT DEFAULT 0,
    `request_type` VARCHAR(50) DEFAULT '',
    `requested_by` INT DEFAULT 0,
    `amount` DECIMAL(14,2) DEFAULT 0,
    `description` TEXT,
    `status` ENUM('pending','approved','rejected','changes_requested','escalated') DEFAULT 'pending',
    `approver_id` INT DEFAULT 0,
    `approver_name` VARCHAR(200) DEFAULT '',
    `approver_comments` TEXT,
    `escalated_to` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────
-- 12. EXPENDITURE APPROVALS
-- ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `expenditure_approvals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `budget_id` INT DEFAULT 0,
    `request_type` VARCHAR(50) DEFAULT '',
    `requested_by` INT DEFAULT 0,
    `amount` DECIMAL(14,2) DEFAULT 0,
    `description` TEXT,
    `status` ENUM('pending','approved','rejected','changes_requested','escalated') DEFAULT 'pending',
    `approver_id` INT DEFAULT 0,
    `approver_name` VARCHAR(200) DEFAULT '',
    `approver_comments` TEXT,
    `escalated_to` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────
-- 13. PROCUREMENT REQUESTS
-- ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `procurement_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `pr_number` VARCHAR(100) DEFAULT '',
    `title` VARCHAR(300) DEFAULT '',
    `description` TEXT,
    `amount` DECIMAL(14,2) DEFAULT 0,
    `department` VARCHAR(200) DEFAULT '',
    `supplier_name` VARCHAR(200) DEFAULT '',
    `status` ENUM('draft','pending','approved','rejected') DEFAULT 'draft',
    `requested_by` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────
-- 14. SUPPLIERS
-- ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `suppliers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `supplier_name` VARCHAR(300) DEFAULT '',
    `contact_person` VARCHAR(200) DEFAULT '',
    `phone` VARCHAR(50) DEFAULT '',
    `email` VARCHAR(100) DEFAULT '',
    `address` TEXT,
    `category` VARCHAR(100) DEFAULT '',
    `status` ENUM('active','inactive') DEFAULT 'active',
    `performance_rating` DECIMAL(5,2) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────
-- 15. SUPPLIER PAYMENTS
-- ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `supplier_payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `supplier_id` INT DEFAULT 0,
    `payment_number` VARCHAR(100) DEFAULT '',
    `amount` DECIMAL(14,2) DEFAULT 0,
    `payment_method` VARCHAR(50) DEFAULT '',
    `payment_date` DATE DEFAULT NULL,
    `invoice_ref` VARCHAR(100) DEFAULT '',
    `status` VARCHAR(50) DEFAULT 'pending',
    `created_by` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────
-- 16. FINANCE ASSETS
-- ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `finance_assets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `asset_name` VARCHAR(300) DEFAULT '',
    `asset_tag` VARCHAR(100) DEFAULT '',
    `category` VARCHAR(100) DEFAULT '',
    `purchase_date` DATE DEFAULT NULL,
    `purchase_price` DECIMAL(14,2) DEFAULT 0,
    `current_value` DECIMAL(14,2) DEFAULT 0,
    `depreciation_rate` DECIMAL(5,2) DEFAULT 0,
    `location` VARCHAR(200) DEFAULT '',
    `assigned_to` VARCHAR(200) DEFAULT '',
    `status` ENUM('active','disposed','maintenance') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────
-- 17. CAPITAL PROJECTS
-- ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `capital_projects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `project_name` VARCHAR(300) DEFAULT '',
    `project_code` VARCHAR(100) DEFAULT '',
    `budget` DECIMAL(14,2) DEFAULT 0,
    `spent` DECIMAL(14,2) DEFAULT 0,
    `start_date` DATE DEFAULT NULL,
    `end_date` DATE DEFAULT NULL,
    `status` ENUM('planning','active','completed','cancelled') DEFAULT 'planning',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────
-- 18. AUDIT FINDINGS
-- ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `audit_findings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `finding_title` VARCHAR(300) DEFAULT '',
    `description` TEXT,
    `severity` ENUM('low','medium','high','critical') DEFAULT 'medium',
    `department` VARCHAR(200) DEFAULT '',
    `status` ENUM('open','in_progress','resolved','closed') DEFAULT 'open',
    `reported_by` VARCHAR(200) DEFAULT '',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────
-- 19. RISK REGISTER
-- ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `risk_register` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `risk_name` VARCHAR(300) DEFAULT '',
    `description` TEXT,
    `category` VARCHAR(100) DEFAULT '',
    `likelihood` ENUM('low','medium','high') DEFAULT 'medium',
    `impact` ENUM('low','medium','high') DEFAULT 'medium',
    `mitigation` TEXT,
    `status` ENUM('active','monitored','resolved') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────
-- 20. COMPLIANCE ALERTS
-- ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `compliance_alerts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `alert_title` VARCHAR(300) DEFAULT '',
    `description` TEXT,
    `compliance_type` ENUM('financial','ura','regulatory') DEFAULT 'financial',
    `severity` ENUM('low','medium','high','critical') DEFAULT 'medium',
    `status` ENUM('open','acknowledged','resolved') DEFAULT 'open',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────
-- 21. FINANCE MESSAGES
-- ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `finance_messages` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `sender_id` INT DEFAULT 0,
    `sender_name` VARCHAR(200) DEFAULT '',
    `recipient_role` VARCHAR(100) DEFAULT '',
    `subject` VARCHAR(300) DEFAULT '',
    `message` TEXT,
    `is_read` TINYINT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────
-- 22. FINANCE NOTICES
-- ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `finance_notices` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(300) DEFAULT '',
    `content` TEXT,
    `audience` VARCHAR(100) DEFAULT '',
    `published_by` VARCHAR(200) DEFAULT '',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────
-- 23. PAYROLL APPROVALS (ensure exists)
-- ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `payroll_approvals` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `budget_id` INT DEFAULT 0,
    `request_type` VARCHAR(50) DEFAULT '',
    `requested_by` INT DEFAULT 0,
    `amount` DECIMAL(14,2) DEFAULT 0,
    `description` TEXT,
    `status` ENUM('pending','approved','rejected','changes_requested','escalated') DEFAULT 'pending',
    `approver_id` INT DEFAULT 0,
    `approver_name` VARCHAR(200) DEFAULT '',
    `approver_comments` TEXT,
    `escalated_to` INT DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ──────────────────────────────────────────────
-- 24. PAYROLL HISTORY (ensure exists)
-- ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `payroll_history` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `staff_id` INT NOT NULL,
    `gross_salary` DECIMAL(14,2) DEFAULT 0,
    `deductions` DECIMAL(14,2) DEFAULT 0,
    `net_salary` DECIMAL(14,2) DEFAULT 0,
    `payment_date` DATE DEFAULT NULL,
    `payment_method` VARCHAR(50) DEFAULT '',
    `status` VARCHAR(50) DEFAULT 'pending',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_staff (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- SEED DATA: Approval Workflows and Stages
-- Required for submitStoreForApproval() and submitStudentForApproval() to work
-- ============================================================

-- Seed default approval workflows (IF NOT EXISTS)
INSERT IGNORE INTO `approval_workflows` (`id`, `workflow_name`, `name`, `description`, `category`, `target_table`, `is_active`)
VALUES
(1, 'Store Requisition', 'Store Requisition', 'Store requisition approval workflow for department requests', 'store', 'store_requests', 1),
(2, 'Student Registration', 'Student Registration', 'Student registration approval by Director General', 'academic', 'pending_students', 1),
(3, 'Budget Approval', 'Budget Approval', 'Budget allocation and spending approval', 'finance', 'budget_records', 1),
(4, 'Expenditure Approval', 'Expenditure Approval', 'Expenditure request approval', 'finance', 'expenditure_approvals', 1),
(5, 'Payroll Approval', 'Payroll Approval', 'Payroll processing approval', 'finance', 'payroll_approvals', 1),
(6, 'Procurement Approval', 'Procurement Approval', 'Procurement request approval', 'finance', 'procurement_requests', 1);

-- Seed stages for Store Requisition workflow (id=1)
-- Stage 1: HOD/Supervisor review (if role_level >= 3)
INSERT IGNORE INTO `approval_stages` (`id`, `workflow_id`, `stage_name`, `stage_order`, `approver_role`, `assigned_role_id`, `assigned_role_name`, `is_final`, `is_mandatory`)
VALUES
(1, 1, 'HOD Review', 1, '', 0, '', 0, 1),
(2, 1, 'DG Final Approval', 2, 'Director General', 0, 'Director General', 1, 1);

-- Seed stages for Student Registration workflow (id=2)
INSERT IGNORE INTO `approval_stages` (`id`, `workflow_id`, `stage_name`, `stage_order`, `approver_role`, `assigned_role_id`, `assigned_role_name`, `is_final`, `is_mandatory`)
VALUES
(3, 2, 'Academic Review', 1, '', 0, '', 0, 1),
(4, 2, 'DG Final Approval', 2, 'Director General', 0, 'Director General', 1, 1);

-- Seed stages for Budget Approval (id=3)
INSERT IGNORE INTO `approval_stages` (`id`, `workflow_id`, `stage_name`, `stage_order`, `approver_role`, `assigned_role_id`, `assigned_role_name`, `is_final`, `is_mandatory`)
VALUES
(5, 3, 'Finance Review', 1, '', 0, '', 0, 1),
(6, 3, 'DG Final Approval', 2, 'Director General', 0, 'Director General', 1, 1);

-- Seed stages for Expenditure Approval (id=4)
INSERT IGNORE INTO `approval_stages` (`id`, `workflow_id`, `stage_name`, `stage_order`, `approver_role`, `assigned_role_id`, `assigned_role_name`, `is_final`, `is_mandatory`)
VALUES
(7, 4, 'Bursar Review', 1, '', 0, '', 0, 1),
(8, 4, 'DG Final Approval', 2, 'Director General', 0, 'Director General', 1, 1);

-- Seed stages for Payroll Approval (id=5)
INSERT IGNORE INTO `approval_stages` (`id`, `workflow_id`, `stage_name`, `stage_order`, `approver_role`, `assigned_role_id`, `assigned_role_name`, `is_final`, `is_mandatory`)
VALUES
(9, 5, 'HR Review', 1, '', 0, '', 0, 1),
(10, 5, 'DG Final Approval', 2, 'Director General', 0, 'Director General', 1, 1);

-- Seed stages for Procurement Approval (id=6)
INSERT IGNORE INTO `approval_stages` (`id`, `workflow_id`, `stage_name`, `stage_order`, `approver_role`, `assigned_role_id`, `assigned_role_name`, `is_final`, `is_mandatory`)
VALUES
(11, 6, 'Procurement Review', 1, '', 0, '', 0, 1),
(12, 6, 'DG Final Approval', 2, 'Director General', 0, 'Director General', 1, 1);

-- ============================================================
-- DONE - All critical tables created/fixed + workflows seeded
-- ============================================================
