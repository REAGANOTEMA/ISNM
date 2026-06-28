-- =============================================================================
-- Migration 002: Create Missing Tables
-- Generated: 2026-06-27
-- Description: Creates all missing tables referenced by PHP code
-- =============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- =============================================================================
-- PAYROLL MODULE TABLES
-- =============================================================================

-- 1. payroll_allowance_types
CREATE TABLE IF NOT EXISTS `payroll_allowance_types` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. payroll_employee_allowances
CREATE TABLE IF NOT EXISTS `payroll_employee_allowances` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `staff_id` INT(11) NOT NULL,
    `allowance_type_id` INT(11) NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `month` INT(2) NOT NULL,
    `year` INT(4) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_staff_id` (`staff_id`),
    INDEX `idx_allowance_type_id` (`allowance_type_id`),
    INDEX `idx_period` (`month`, `year`),
    CONSTRAINT `fk_ea_allowance_type` FOREIGN KEY (`allowance_type_id`) REFERENCES `payroll_allowance_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. payroll_deduction_types
CREATE TABLE IF NOT EXISTS `payroll_deduction_types` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. payroll_employee_deductions
CREATE TABLE IF NOT EXISTS `payroll_employee_deductions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `staff_id` INT(11) NOT NULL,
    `deduction_type_id` INT(11) NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `month` INT(2) NOT NULL,
    `year` INT(4) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_staff_id` (`staff_id`),
    INDEX `idx_deduction_type_id` (`deduction_type_id`),
    INDEX `idx_period` (`month`, `year`),
    CONSTRAINT `fk_ed_deduction_type` FOREIGN KEY (`deduction_type_id`) REFERENCES `payroll_deduction_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. payroll_bonuses
CREATE TABLE IF NOT EXISTS `payroll_bonuses` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `staff_id` INT(11) NOT NULL,
    `bonus_type` VARCHAR(50) NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `month` INT(2) NOT NULL,
    `year` INT(4) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_staff_id` (`staff_id`),
    INDEX `idx_period` (`month`, `year`),
    INDEX `idx_bonus_type` (`bonus_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. payroll_loans
CREATE TABLE IF NOT EXISTS `payroll_loans` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `staff_id` INT(11) NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `monthly_deduction` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `remaining_balance` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('pending','active','completed','cancelled') NOT NULL DEFAULT 'pending',
    `approved_by` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_staff_id` (`staff_id`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. payroll_payslips
CREATE TABLE IF NOT EXISTS `payroll_payslips` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `staff_id` INT(11) NOT NULL,
    `period_id` INT(11) NOT NULL,
    `basic_salary` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `allowances_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `deductions_total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `net_pay` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `generated_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_staff_id` (`staff_id`),
    INDEX `idx_period_id` (`period_id`),
    UNIQUE INDEX `idx_staff_period` (`staff_id`, `period_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. payroll_payments
CREATE TABLE IF NOT EXISTS `payroll_payments` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `staff_id` INT(11) NOT NULL,
    `payroll_run_id` INT(11) DEFAULT NULL,
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `payment_method` VARCHAR(50) NOT NULL DEFAULT 'bank_transfer',
    `payment_date` DATE NOT NULL,
    `reference_number` VARCHAR(100) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_staff_id` (`staff_id`),
    INDEX `idx_payment_date` (`payment_date`),
    INDEX `idx_reference_number` (`reference_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. payroll_reports
CREATE TABLE IF NOT EXISTS `payroll_reports` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `report_type` VARCHAR(50) NOT NULL,
    `period_start` DATE NOT NULL,
    `period_end` DATE NOT NULL,
    `generated_by` INT(11) NOT NULL,
    `report_data` LONGTEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_report_type` (`report_type`),
    INDEX `idx_period` (`period_start`, `period_end`),
    INDEX `idx_generated_by` (`generated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. payroll_settings
CREATE TABLE IF NOT EXISTS `payroll_settings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(100) NOT NULL,
    `setting_value` TEXT DEFAULT NULL,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. payroll_periods
CREATE TABLE IF NOT EXISTS `payroll_periods` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `month` INT(2) NOT NULL,
    `year` INT(4) NOT NULL,
    `status` ENUM('draft','open','processing','closed','paid') NOT NULL DEFAULT 'draft',
    `created_by` INT(11) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE INDEX `idx_month_year` (`month`, `year`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. payroll_items
CREATE TABLE IF NOT EXISTS `payroll_items` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `staff_id` INT(11) NOT NULL,
    `period_id` INT(11) NOT NULL,
    `basic_salary` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `allowances` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `deductions` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `net_pay` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_staff_id` (`staff_id`),
    INDEX `idx_period_id` (`period_id`),
    UNIQUE INDEX `idx_staff_period` (`staff_id`, `period_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- STUDENT WELFARE TABLES
-- =============================================================================

-- 13. student_discipline
CREATE TABLE IF NOT EXISTS `student_discipline` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `student_id` INT(11) NOT NULL,
    `incident_type` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `action_taken` TEXT DEFAULT NULL,
    `reported_by` INT(11) NOT NULL,
    `status` ENUM('pending','investigating','resolved','escalated') NOT NULL DEFAULT 'pending',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_student_id` (`student_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_reported_by` (`reported_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. student_counseling_sessions
CREATE TABLE IF NOT EXISTS `student_counseling_sessions` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `student_id` INT(11) NOT NULL,
    `counselor_id` INT(11) NOT NULL,
    `session_date` DATE NOT NULL,
    `notes` TEXT DEFAULT NULL,
    `follow_up_date` DATE DEFAULT NULL,
    `status` ENUM('scheduled','completed','cancelled','follow_up') NOT NULL DEFAULT 'scheduled',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_student_id` (`student_id`),
    INDEX `idx_counselor_id` (`counselor_id`),
    INDEX `idx_session_date` (`session_date`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. student_health_records
CREATE TABLE IF NOT EXISTS `student_health_records` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `student_id` INT(11) NOT NULL,
    `record_type` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `recorded_by` INT(11) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_student_id` (`student_id`),
    INDEX `idx_record_type` (`record_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. student_health_incidents
CREATE TABLE IF NOT EXISTS `student_health_incidents` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `student_id` INT(11) NOT NULL,
    `incident_type` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `severity` ENUM('low','medium','high','critical') NOT NULL DEFAULT 'low',
    `action_taken` TEXT DEFAULT NULL,
    `recorded_by` INT(11) NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_student_id` (`student_id`),
    INDEX `idx_severity` (`severity`),
    INDEX `idx_incident_type` (`incident_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. student_emergency_contacts
CREATE TABLE IF NOT EXISTS `student_emergency_contacts` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `student_id` INT(11) NOT NULL,
    `contact_name` VARCHAR(150) NOT NULL,
    `relationship` VARCHAR(50) NOT NULL,
    `phone` VARCHAR(20) NOT NULL,
    `email` VARCHAR(150) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_student_id` (`student_id`),
    INDEX `idx_phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. student_academic_profiles
CREATE TABLE IF NOT EXISTS `student_academic_profiles` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `student_id` INT(11) NOT NULL,
    `academic_year` VARCHAR(20) NOT NULL,
    `gpa` DECIMAL(3,2) DEFAULT NULL,
    `credits_earned` INT(11) NOT NULL DEFAULT 0,
    `academic_standing` ENUM('good','probation','suspension','expelled','graduated') NOT NULL DEFAULT 'good',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_student_id` (`student_id`),
    INDEX `idx_academic_year` (`academic_year`),
    INDEX `idx_academic_standing` (`academic_standing`),
    UNIQUE INDEX `idx_student_year` (`student_id`, `academic_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- STAFF/HR TABLES
-- =============================================================================

-- 19. staff_leave_requests
CREATE TABLE IF NOT EXISTS `staff_leave_requests` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `staff_id` INT(11) NOT NULL,
    `leave_type` VARCHAR(50) NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `reason` TEXT NOT NULL,
    `status` ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
    `approved_by` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_staff_id` (`staff_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_dates` (`start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. staff_training
CREATE TABLE IF NOT EXISTS `staff_training` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `staff_id` INT(11) NOT NULL,
    `training_name` VARCHAR(200) NOT NULL,
    `provider` VARCHAR(150) DEFAULT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE DEFAULT NULL,
    `status` ENUM('enrolled','in_progress','completed','cancelled') NOT NULL DEFAULT 'enrolled',
    `certificate_path` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_staff_id` (`staff_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_dates` (`start_date`, `end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. staff_licenses
CREATE TABLE IF NOT EXISTS `staff_licenses` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `staff_id` INT(11) NOT NULL,
    `license_type` VARCHAR(100) NOT NULL,
    `license_number` VARCHAR(100) NOT NULL,
    `issuing_authority` VARCHAR(150) DEFAULT NULL,
    `issue_date` DATE NOT NULL,
    `expiry_date` DATE DEFAULT NULL,
    `status` ENUM('active','expired','suspended','revoked') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_staff_id` (`staff_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_expiry_date` (`expiry_date`),
    INDEX `idx_license_number` (`license_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- COMMUNICATION TABLES
-- =============================================================================

-- 22. student_messages
CREATE TABLE IF NOT EXISTS `student_messages` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `sender_id` INT(11) NOT NULL,
    `recipient_id` INT(11) NOT NULL,
    `subject` VARCHAR(200) NOT NULL,
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_sender_id` (`sender_id`),
    INDEX `idx_recipient_id` (`recipient_id`),
    INDEX `idx_is_read` (`is_read`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 23. student_requests
CREATE TABLE IF NOT EXISTS `student_requests` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `student_id` INT(11) NOT NULL,
    `request_type` VARCHAR(100) NOT NULL,
    `description` TEXT NOT NULL,
    `status` ENUM('pending','in_progress','completed','rejected') NOT NULL DEFAULT 'pending',
    `assigned_to` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_student_id` (`student_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_assigned_to` (`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- ACTIVITY LOG
-- =============================================================================

-- 24. activity_log
CREATE TABLE IF NOT EXISTS `activity_log` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `user_type` ENUM('admin','staff','student') NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_user_type` (`user_type`),
    INDEX `idx_action` (`action`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- INSERT DEFAULT DATA
-- =============================================================================

-- Default payroll settings
INSERT IGNORE INTO `payroll_settings` (`setting_key`, `setting_value`) VALUES
('currency', 'PHP'),
('currency_symbol', '₱'),
('tax_rate', '0'),
('sss_rate', '0.045'),
('philhealth_rate', '0.0225'),
('pagibig_rate', '0.02'),
('overtime_rate', '1.25');

-- Default allowance types
INSERT IGNORE INTO `payroll_allowance_types` (`name`, `description`, `is_active`) VALUES
('Transportation Allowance', 'Monthly transportation allowance', 1),
('Meal Allowance', 'Monthly meal allowance', 1),
('Housing Allowance', 'Monthly housing allowance', 1),
('Clothing Allowance', 'Annual clothing allowance', 1),
('Rice Subsidy', 'Monthly rice subsidy', 1);

-- Default deduction types
INSERT IGNORE INTO `payroll_deduction_types` (`name`, `description`, `is_active`) VALUES
('SSS Contribution', 'Social Security System contribution', 1),
('PhilHealth', 'Philippine Health Insurance Corporation contribution', 1),
('Pag-IBIG', 'Home Development Mutual Fund contribution', 1),
('Withholding Tax', 'Income tax withholding', 1),
('Loan Payment', 'Salary loan deduction', 1);

COMMIT;

-- =============================================================================
-- END OF MIGRATION 002
-- =============================================================================
