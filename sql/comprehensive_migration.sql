-- ══════════════════════════════════════════════════════════════
-- ISNM: Comprehensive Database Migration
-- Run this ONCE in phpMyAdmin to ensure all tables/columns exist.
-- Safe to run multiple times (fully idempotent).
-- Target: MariaDB 10.11+
-- ══════════════════════════════════════════════════════════════

SET sql_mode = '';

-- ────────────────────────────────────────────────
-- 1. STUDENTS DATABASE (igangaschool_students)
-- ────────────────────────────────────────────────
USE `igangaschool_students`;

-- ── Add missing columns to students table ──
-- These columns are referenced by PHP code (ajax/update_student.php, student-add.php)
-- but may not exist in the base dump.

ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `district` varchar(100) DEFAULT NULL AFTER `address`;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `guardian_email` varchar(100) DEFAULT NULL AFTER `guardian_phone`;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `sponsor` varchar(200) DEFAULT NULL AFTER `emergency_contact_email`;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `marital_status` varchar(20) DEFAULT NULL AFTER `sponsor`;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `religion` varchar(50) DEFAULT NULL AFTER `marital_status`;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `student_category` varchar(50) DEFAULT NULL AFTER `religion`;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `year_of_study` int(11) DEFAULT NULL AFTER `current_year`;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `course_codes` text DEFAULT NULL AFTER `course`;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `created_at`;

-- Add missing indexes for search performance
ALTER TABLE `students` ADD INDEX IF NOT EXISTS `idx_stu_index_number` (`index_number`);
ALTER TABLE `students` ADD INDEX IF NOT EXISTS `idx_stu_registration_number` (`registration_number`);
ALTER TABLE `students` ADD INDEX IF NOT EXISTS `idx_stu_national_id` (`national_student_id_number`);
ALTER TABLE `students` ADD INDEX IF NOT EXISTS `idx_stu_phone` (`phone`);
ALTER TABLE `students` ADD INDEX IF NOT EXISTS `idx_stu_email` (`email`);
ALTER TABLE `students` ADD INDEX IF NOT EXISTS `idx_stu_program` (`program`);
ALTER TABLE `students` ADD INDEX IF NOT EXISTS `idx_stu_status` (`status`);

-- ── Student Profiles (for admission/registration tracking) ──
CREATE TABLE IF NOT EXISTS `student_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `admission_status` varchar(50) DEFAULT 'Registered',
  `fee_status` varchar(50) DEFAULT 'unpaid',
  `academic_status` varchar(50) DEFAULT 'Active',
  `profile_completed` tinyint(1) DEFAULT 0,
  `documents_verified` tinyint(1) DEFAULT 0,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sp_student` (`student_id`),
  KEY `idx_sp_admission` (`admission_status`),
  KEY `idx_sp_fee` (`fee_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Financial Profiles ──
CREATE TABLE IF NOT EXISTS `student_financial_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `total_fees` decimal(14,2) DEFAULT 0.00,
  `total_paid` decimal(14,2) DEFAULT 0.00,
  `balance` decimal(14,2) DEFAULT 0.00,
  `scholarship_amount` decimal(14,2) DEFAULT 0.00,
  `fee_status` enum('unpaid','partial','paid','overdue') DEFAULT 'unpaid',
  `last_payment_date` date DEFAULT NULL,
  `next_due_date` date DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sfp_student` (`student_id`),
  KEY `idx_sfp_status` (`fee_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Academic Profiles ──
CREATE TABLE IF NOT EXISTS `student_academic_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `current_gpa` decimal(4,2) DEFAULT NULL,
  `cumulative_credits` int(11) DEFAULT 0,
  `academic_standing` varchar(50) DEFAULT 'Good Standing',
  `advisor_id` int(11) DEFAULT NULL,
  `enrollment_date` date DEFAULT NULL,
  `expected_graduation` date DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sap_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Finance (for bursar payments tracking) ──
CREATE TABLE IF NOT EXISTS `student_finance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `tuition_fee` decimal(14,2) DEFAULT 0.00,
  `amount_paid` decimal(14,2) DEFAULT 0.00,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `payment_status` enum('pending','partial','paid','overdue') DEFAULT 'pending',
  `semester` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `receipt_number` varchar(100) DEFAULT NULL,
  `received_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sf_student` (`student_id`),
  KEY `idx_sf_status` (`payment_status`),
  KEY `idx_sf_year` (`academic_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Fee Structures (bursar-compatible schema) ──
CREATE TABLE IF NOT EXISTS `fee_structures` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fee_name` varchar(255) NOT NULL,
  `fee_type` varchar(50) DEFAULT 'Tuition',
  `amount` decimal(12,2) NOT NULL DEFAULT 0,
  `program_id` int(11) DEFAULT NULL,
  `academic_year` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_fs_type` (`fee_type`),
  KEY `idx_fs_program` (`program_id`),
  KEY `idx_fs_year` (`academic_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Requirements Status ──
CREATE TABLE IF NOT EXISTS `student_requirements_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `requirement_id` int(11) NOT NULL,
  `status` enum('Not Submitted','Pending','Submitted','Verified','Rejected','Missing') DEFAULT 'Not Submitted',
  `remarks` text DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_srs_student` (`student_id`),
  KEY `idx_srs_requirement` (`requirement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Requirement Categories ──
CREATE TABLE IF NOT EXISTS `requirement_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Disciplinary Records ──
CREATE TABLE IF NOT EXISTS `student_discipline` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `incident_type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `incident_date` date DEFAULT NULL,
  `action_taken` text DEFAULT NULL,
  `severity` enum('Low','Medium','High','Critical') DEFAULT 'Medium',
  `reported_by` int(11) DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `status` enum('Open','Under Review','Resolved','Closed') DEFAULT 'Open',
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sd_student` (`student_id`),
  KEY `idx_sd_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Medical Records ──
CREATE TABLE IF NOT EXISTS `student_medical` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `blood_group` varchar(10) DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `medical_conditions` text DEFAULT NULL,
  `medications` text DEFAULT NULL,
  `emergency_medical_notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sm_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Attendance ──
CREATE TABLE IF NOT EXISTS `student_attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `attendance_date` date DEFAULT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `status` enum('Present','Absent','Late','Excused') DEFAULT 'Present',
  `remarks` text DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sa_student` (`student_id`),
  KEY `idx_sa_date` (`attendance_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Academic Records ──
CREATE TABLE IF NOT EXISTS `student_academic_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `assessment_type` varchar(50) DEFAULT NULL,
  `marks` decimal(5,2) DEFAULT NULL,
  `grade` varchar(10) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `lecturer_id` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sar_student` (`student_id`),
  KEY `idx_sar_year` (`academic_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Examination Results ──
CREATE TABLE IF NOT EXISTS `examination_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT 0,
  `score` decimal(8,2) DEFAULT 0,
  `max_score` decimal(8,2) DEFAULT 100,
  `grade` varchar(10) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `entered_by` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_er_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Assessment Scores ──
CREATE TABLE IF NOT EXISTS `assessment_scores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `examination_session_id` int(11) DEFAULT 0,
  `student_id` int(11) NOT NULL,
  `score` decimal(8,2) DEFAULT 0,
  `max_score` decimal(8,2) DEFAULT 100,
  `entered_by` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_asc_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Course Catalog ──
CREATE TABLE IF NOT EXISTS `course_catalog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_code` varchar(50) NOT NULL,
  `course_name` varchar(300) NOT NULL,
  `program` varchar(200) DEFAULT '',
  `level` varchar(50) DEFAULT '',
  `semester` varchar(100) DEFAULT '',
  `credit_units` int(11) DEFAULT 0,
  `is_compulsory` tinyint(1) DEFAULT 1,
  `status` varchar(50) DEFAULT 'Active',
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cc_code` (`course_code`),
  KEY `idx_cc_program` (`program`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Course Registrations ──
CREATE TABLE IF NOT EXISTS `course_registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `course_code` varchar(50) DEFAULT '',
  `course_id` int(11) DEFAULT 0,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(100) DEFAULT NULL,
  `registration_status` varchar(50) DEFAULT 'Registered',
  `status` varchar(50) DEFAULT 'Registered',
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cr_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Clinical Placements ──
CREATE TABLE IF NOT EXISTS `clinical_placements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `facility_name` varchar(300) DEFAULT '',
  `department` varchar(200) DEFAULT '',
  `supervisor` varchar(200) DEFAULT '',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Active',
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cp_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Admissions ──
CREATE TABLE IF NOT EXISTS `student_admissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_number` varchar(50) DEFAULT NULL,
  `applicant_name` varchar(300) NOT NULL,
  `program` varchar(200) DEFAULT '',
  `academic_year` varchar(20) DEFAULT NULL,
  `admission_status` varchar(50) DEFAULT 'Applied',
  `application_date` date DEFAULT NULL,
  `decided_by` int(11) DEFAULT 0,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sa_app_number` (`application_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Invoices (bursar-compatible schema) ──
CREATE TABLE IF NOT EXISTS `student_invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `net_amount` decimal(12,2) DEFAULT 0.00,
  `amount` decimal(12,2) DEFAULT 0.00,
  `total_amount` decimal(14,2) DEFAULT 0.00,
  `amount_paid` decimal(14,2) DEFAULT 0.00,
  `balance` decimal(14,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Pending',
  `due_date` date DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_invoice_number` (`invoice_number`),
  KEY `idx_si_student` (`student_id`),
  KEY `idx_si_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Payments (bursar-compatible schema) ──
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_reference` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `amount_received` decimal(14,2) NOT NULL DEFAULT 0.00,
  `invoice_id` int(11) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT 'Cash',
  `transaction_ref` varchar(100) DEFAULT NULL,
  `slip_number` varchar(50) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Completed',
  `received_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `mobile_phone` varchar(20) DEFAULT NULL,
  `mobile_provider` varchar(50) DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_payment_ref` (`payment_reference`),
  KEY `idx_p_student` (`student_id`),
  KEY `idx_p_date` (`payment_date`),
  KEY `idx_p_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Fee Tracking (bursar balance tracking) ──
CREATE TABLE IF NOT EXISTS `student_fee_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `total_fees` decimal(12,2) DEFAULT 0.00,
  `amount_paid` decimal(12,2) DEFAULT 0.00,
  `balance` decimal(12,2) DEFAULT 0.00,
  `status` varchar(20) DEFAULT 'Pending',
  `academic_year` varchar(20) DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sft_student` (`student_id`),
  KEY `idx_sft_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Fee Assignments ──
CREATE TABLE IF NOT EXISTS `student_fee_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `fee_structure_id` int(11) DEFAULT NULL,
  `assigned_amount` decimal(12,2) DEFAULT 0.00,
  `paid_amount` decimal(12,2) DEFAULT 0.00,
  `status` varchar(20) DEFAULT 'Pending',
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sfa_student` (`student_id`),
  KEY `idx_sfa_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Fee Adjustments (discounts/waivers) ──
CREATE TABLE IF NOT EXISTS `fee_adjustments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `adjustment_number` varchar(50) DEFAULT NULL,
  `student_id` int(11) NOT NULL,
  `adjustment_type` varchar(50) DEFAULT 'Discount',
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_type` varchar(50) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_fa_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Notifications ──
CREATE TABLE IF NOT EXISTS `student_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `type` varchar(50) DEFAULT 'info',
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `priority` varchar(20) DEFAULT 'Normal',
  `is_read` tinyint(1) DEFAULT 0,
  `link` varchar(500) DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sn_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Announcements ──
CREATE TABLE IF NOT EXISTS `announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `target_audience` enum('All','Nursing','Midwifery','Year1','Year2','Year3','Staff') DEFAULT 'All',
  `priority` enum('Normal','High','Urgent') DEFAULT 'Normal',
  `posted_by` int(11) DEFAULT NULL,
  `expires_at` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Students (ensure minimal table exists if missing) ──
CREATE TABLE IF NOT EXISTS `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_number` varchar(50) NOT NULL,
  `registration_number` varchar(50) DEFAULT NULL,
  `national_student_id_number` varchar(50) DEFAULT NULL,
  `index_number` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `other_name` varchar(100) DEFAULT NULL,
  `full_name` varchar(300) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `mobile_number` varchar(20) DEFAULT NULL,
  `program` varchar(200) DEFAULT NULL,
  `course` varchar(200) DEFAULT NULL,
  `current_year` int(11) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `year_of_study` int(11) DEFAULT NULL,
  `level` varchar(50) DEFAULT NULL,
  `set_name` varchar(50) DEFAULT NULL,
  `current_semester` varchar(20) DEFAULT NULL,
  `intake_date` date DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT 'Other',
  `nationality` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `emergency_contact_email` varchar(100) DEFAULT NULL,
  `guardian_name` varchar(200) DEFAULT NULL,
  `guardian_phone` varchar(20) DEFAULT NULL,
  `guardian_email` varchar(100) DEFAULT NULL,
  `sponsor` varchar(200) DEFAULT NULL,
  `marital_status` varchar(20) DEFAULT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `student_category` varchar(50) DEFAULT NULL,
  `course_codes` text DEFAULT NULL,
  `profile_picture` varchar(500) DEFAULT NULL,
  `passport_photo` varchar(500) DEFAULT NULL,
  `status` enum('Active','Inactive','Graduated','Suspended','Withdrawn','deleted') DEFAULT 'Active',
  `last_login` timestamp NULL DEFAULT NULL,
  `locked_until` timestamp NULL DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `password_changed` tinyint(1) DEFAULT 0,
  `is_first_login` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `intake_year` varchar(10) DEFAULT NULL,
  `intake_period` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_stu_number` (`student_number`),
  KEY `idx_stu_status` (`status`),
  KEY `idx_stu_index` (`index_number`),
  KEY `idx_stu_reg` (`registration_number`),
  KEY `idx_stu_nsid` (`national_student_id_number`),
  KEY `idx_stu_phone` (`phone`),
  KEY `idx_stu_email` (`email`),
  KEY `idx_stu_program` (`program`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Expenses ──
CREATE TABLE IF NOT EXISTS `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `expense_title` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0,
  `category` varchar(100) DEFAULT NULL,
  `expense_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `recorded_by` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Budgets ──
CREATE TABLE IF NOT EXISTS `budgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `budget_name` varchar(255) NOT NULL,
  `budget_title` varchar(255) DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0,
  `amount` decimal(12,2) DEFAULT 0,
  `fiscal_year` varchar(20) DEFAULT NULL,
  `year` varchar(20) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Bank Reconciliation ──
CREATE TABLE IF NOT EXISTS `bank_reconciliation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reconciliation_date` date DEFAULT NULL,
  `bank_balance` decimal(14,2) DEFAULT 0.00,
  `book_balance` decimal(14,2) DEFAULT 0.00,
  `difference` decimal(14,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'completed',
  `reconciled_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Payroll Runs ──
CREATE TABLE IF NOT EXISTS `payroll_runs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `period` varchar(20) NOT NULL DEFAULT '',
  `description` varchar(255) DEFAULT NULL,
  `run_date` date DEFAULT NULL,
  `total_gross` decimal(14,2) DEFAULT 0.00,
  `total_deductions` decimal(14,2) DEFAULT 0.00,
  `total_net` decimal(14,2) DEFAULT 0.00,
  `employee_count` int(11) DEFAULT 0,
  `status` varchar(20) DEFAULT 'draft',
  `processed_by` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Payroll Details ──
CREATE TABLE IF NOT EXISTS `payroll_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payroll_run_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `basic_salary` decimal(14,2) DEFAULT 0.00,
  `gross_pay` decimal(14,2) DEFAULT 0.00,
  `paye_tax` decimal(14,2) DEFAULT 0.00,
  `nssf_employee` decimal(14,2) DEFAULT 0.00,
  `nssf_employer` decimal(14,2) DEFAULT 0.00,
  `other_deductions` decimal(14,2) DEFAULT 0.00,
  `net_pay` decimal(14,2) DEFAULT 0.00,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pd_run` (`payroll_run_id`),
  KEY `idx_pd_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Academic Records (transcripts) ──
CREATE TABLE IF NOT EXISTS `academic_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `course_code` varchar(50) DEFAULT NULL,
  `course_name` varchar(300) DEFAULT NULL,
  `credit_units` int(11) DEFAULT 0,
  `score` decimal(5,2) DEFAULT NULL,
  `grade` varchar(10) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ar_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Examination Records (exams-results dashboard) ──
CREATE TABLE IF NOT EXISTS `examination_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT 0,
  `course_code` varchar(50) DEFAULT NULL,
  `course_name` varchar(300) DEFAULT NULL,
  `exam_type` varchar(50) DEFAULT NULL,
  `score` decimal(8,2) DEFAULT 0,
  `max_score` decimal(8,2) DEFAULT 100,
  `grade` varchar(10) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `entered_by` int(11) DEFAULT 0,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_exr_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Documents ──
CREATE TABLE IF NOT EXISTS `student_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `document_name` varchar(300) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` int(11) DEFAULT 0,
  `status` varchar(50) DEFAULT 'Pending',
  `uploaded_by` int(11) DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sd_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Proof of Payments ──
CREATE TABLE IF NOT EXISTS `proof_of_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_name` varchar(300) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `amount_claimed` decimal(14,2) DEFAULT 0.00,
  `status` varchar(50) DEFAULT 'Pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pop_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Welfare Cases ──
CREATE TABLE IF NOT EXISTS `student_welfare_cases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `case_type` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `status` enum('open','in_progress','resolved','closed') DEFAULT 'open',
  `assigned_to` varchar(200) DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sw_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Counseling Sessions ──
CREATE TABLE IF NOT EXISTS `student_counseling_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `session_date` date DEFAULT NULL,
  `session_type` varchar(100) DEFAULT NULL,
  `counselor_name` varchar(200) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Scheduled',
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_scs_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Discipline Records (extended) ──
CREATE TABLE IF NOT EXISTS `student_discipline_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `violation_type` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `severity` enum('low','medium','high') DEFAULT 'medium',
  `action_taken` varchar(200) DEFAULT NULL,
  `status` enum('pending','resolved','appealed') DEFAULT 'pending',
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sdr_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Health Incidents ──
CREATE TABLE IF NOT EXISTS `student_health_incidents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `incident_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `action_taken` text DEFAULT NULL,
  `reported_by` int(11) DEFAULT NULL,
  `incident_date` date DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_shi_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Intakes ──
CREATE TABLE IF NOT EXISTS `intakes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `intake_name` varchar(200) NOT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `target_count` int(11) DEFAULT 0,
  `actual_count` int(11) DEFAULT 0,
  `status` varchar(50) default 'Planning',
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Programs ──
CREATE TABLE IF NOT EXISTS `programs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_name` varchar(300) NOT NULL,
  `program_code` varchar(50) DEFAULT NULL,
  `department` varchar(200) DEFAULT NULL,
  `duration_years` int(11) DEFAULT 3,
  `status` varchar(50) DEFAULT 'Active',
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Student Applications ──
CREATE TABLE IF NOT EXISTS `student_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_number` varchar(50) DEFAULT NULL,
  `applicant_name` varchar(300) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `program` varchar(200) DEFAULT '',
  `academic_year` varchar(20) DEFAULT NULL,
  `application_status` varchar(50) DEFAULT 'Applied',
  `application_date` date DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sapp_number` (`application_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Payment Transactions (gateway callbacks) ──
CREATE TABLE IF NOT EXISTS `payment_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_reference` varchar(50) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `amount` decimal(14,2) DEFAULT 0.00,
  `provider` varchar(50) DEFAULT NULL,
  `provider_ref` varchar(200) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `raw_response` text DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pt_ref` (`payment_reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Payment Callbacks ──
CREATE TABLE IF NOT EXISTS `payment_callbacks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `provider` varchar(50) DEFAULT NULL,
  `payload` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'received',
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ══════════════════════════════════════════════════════════════
-- DONE! All students database tables and columns ensured.
-- ══════════════════════════════════════════════════════════════
