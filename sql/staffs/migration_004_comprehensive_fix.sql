-- ============================================================
-- ISNM ERP — COMPREHENSIVE MISSING TABLES MIGRATION
-- Compatible with MariaDB 10.4.32
-- Run in phpMyAdmin: Select ANY database, then import this file
-- The USE statement below forces the correct database
-- ============================================================

USE `igangaschoolofl_staffs_db`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;

-- ============================================================
-- 1. PAYROLL TABLES
-- ============================================================

DROP TABLE IF EXISTS `payroll_employee_allowances`;
CREATE TABLE `payroll_employee_allowances` (
  `id` int NOT NULL AUTO_INCREMENT,
  `payroll_employee_id` int NOT NULL,
  `allowance_type_id` int DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0,
  `is_taxable` tinyint(1) DEFAULT 1,
  `is_recurring` tinyint(1) DEFAULT 1,
  `effective_from` date DEFAULT NULL,
  `effective_to` date DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pea_employee` (`payroll_employee_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `payroll_employee_deductions`;
CREATE TABLE `payroll_employee_deductions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `payroll_employee_id` int NOT NULL,
  `deduction_type_id` int DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0,
  `is_recurring` tinyint(1) DEFAULT 1,
  `effective_from` date DEFAULT NULL,
  `effective_to` date DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ped_employee` (`payroll_employee_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `payroll_allowance_types`;
CREATE TABLE `payroll_allowance_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `allowance_code` varchar(20) NOT NULL,
  `allowance_name` varchar(100) NOT NULL,
  `description` text,
  `is_taxable` tinyint(1) DEFAULT 1,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_allowance_code` (`allowance_code`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `payroll_deduction_types`;
CREATE TABLE `payroll_deduction_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `deduction_code` varchar(20) NOT NULL,
  `deduction_name` varchar(100) NOT NULL,
  `description` text,
  `is_statutory` tinyint(1) DEFAULT 0,
  `category` varchar(50) DEFAULT 'other',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_deduction_code` (`deduction_code`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `payroll_loans`;
CREATE TABLE `payroll_loans` (
  `id` int NOT NULL AUTO_INCREMENT,
  `payroll_employee_id` int NOT NULL,
  `loan_number` varchar(50) NOT NULL,
  `loan_type` varchar(50) DEFAULT 'staff_loan',
  `principal_amount` decimal(15,2) NOT NULL DEFAULT 0,
  `interest_rate` decimal(5,2) DEFAULT 0,
  `installments` int DEFAULT 1,
  `installment_amount` decimal(15,2) DEFAULT 0,
  `loan_date` date DEFAULT NULL,
  `amount_paid` decimal(15,2) DEFAULT 0,
  `installments_paid` int DEFAULT 0,
  `status` enum('pending','active','completed','defaulted') DEFAULT 'pending',
  `created_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_loan_number` (`loan_number`),
  KEY `idx_loan_employee` (`payroll_employee_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `payroll_payments`;
CREATE TABLE `payroll_payments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `payroll_run_id` int NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(50) DEFAULT 'bank_transfer',
  `total_amount` decimal(15,2) DEFAULT 0,
  `employee_count` int DEFAULT 0,
  `reference_number` varchar(100) DEFAULT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `processed_by` int DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_payment_run` (`payroll_run_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `payroll_payslips`;
CREATE TABLE `payroll_payslips` (
  `id` int NOT NULL AUTO_INCREMENT,
  `payroll_item_id` int NOT NULL,
  `payroll_run_id` int NOT NULL,
  `payroll_employee_id` int NOT NULL,
  `staff_id` int NOT NULL,
  `payslip_number` varchar(50) NOT NULL,
  `payslip_html` longtext,
  `pdf_generated` tinyint(1) DEFAULT 0,
  `generated_by` int DEFAULT NULL,
  `generated_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_payslip_number` (`payslip_number`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `payroll_audit_logs`;
CREATE TABLE `payroll_audit_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int DEFAULT NULL,
  `old_values` text,
  `new_values` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_staff` (`staff_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `payroll_approval_history`;
CREATE TABLE `payroll_approval_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int NOT NULL,
  `action` varchar(50) NOT NULL,
  `step` varchar(100) DEFAULT NULL,
  `comments` text,
  `acted_by` int DEFAULT NULL,
  `acted_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_approval_entity` (`entity_type`,`entity_id`)
) ENGINE=InnoDB;

-- Seed default allowance types
INSERT INTO `payroll_allowance_types` (`id`,`allowance_code`,`allowance_name`,`is_taxable`) VALUES
(1,'HRA','Housing Allowance',1),
(2,'TRANSPORT','Transport Allowance',1),
(3,'MEDICAL','Medical Allowance',0),
(4,'LUNCH','Lunch Allowance',1),
(5,'UTILITY','Utility Allowance',1);

-- Seed default deduction types
INSERT INTO `payroll_deduction_types` (`id`,`deduction_code`,`deduction_name`,`is_statutory`,`category`) VALUES
(1,'NSSF','NSSF Employee',1,'statutory'),
(2,'PAYE','PAYE Tax',1,'statutory'),
(3,'LOAN','Staff Loan',0,'voluntary'),
(4,'ADVANCE','Salary Advance',0,'voluntary');

-- ============================================================
-- 2. STORE / INVENTORY TABLES
-- ============================================================

DROP TABLE IF EXISTS `store_categories`;
CREATE TABLE `store_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `description` text,
  `icon` varchar(50) DEFAULT 'fas fa-box',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `store_inventory`;
CREATE TABLE `store_inventory` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_id` int DEFAULT NULL,
  `item_name` varchar(200) NOT NULL,
  `item_code` varchar(50) DEFAULT NULL,
  `description` text,
  `unit` varchar(50) DEFAULT 'piece',
  `quantity` int DEFAULT 0,
  `reorder_level` int DEFAULT 10,
  `unit_cost` decimal(15,2) DEFAULT 0,
  `location` varchar(200) DEFAULT NULL,
  `supplier` varchar(200) DEFAULT NULL,
  `status` enum('active','inactive','discontinued') DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inventory_category` (`category_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `store_inventory_transactions`;
CREATE TABLE `store_inventory_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_id` int NOT NULL,
  `transaction_type` varchar(50) NOT NULL,
  `quantity` int NOT NULL,
  `quantity_before` int DEFAULT NULL,
  `quantity_after` int DEFAULT NULL,
  `reason` text,
  `created_by` int DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_trans_item` (`item_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `store_request_items`;
CREATE TABLE `store_request_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `request_id` int NOT NULL,
  `item_id` int NOT NULL,
  `quantity_requested` int NOT NULL DEFAULT 1,
  `quantity_fulfilled` int DEFAULT 0,
  `notes` text,
  `status` enum('pending','fulfilled','partial') DEFAULT 'pending',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ri_request` (`request_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `store_order_items`;
CREATE TABLE `store_order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `item_id` int NOT NULL,
  `quantity_ordered` int NOT NULL DEFAULT 1,
  `quantity_received` int DEFAULT 0,
  `unit_price` decimal(15,2) DEFAULT 0,
  `status` enum('pending','received','cancelled') DEFAULT 'pending',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_oi_order` (`order_id`)
) ENGINE=InnoDB;

INSERT INTO `store_categories` (`id`,`category_name`,`icon`,`status`) VALUES
(1,'Stationery','fas fa-pen','active'),
(2,'Furniture','fas fa-chair','active'),
(3,'Electronics','fas fa-laptop','active'),
(4,'Cleaning Supplies','fas fa-broom','active'),
(5,'Medical Supplies','fas fa-heartbeat','active'),
(6,'Laboratory Equipment','fas fa-flask','active'),
(7,'Printing Materials','fas fa-print','active'),
(8,'General','fas fa-boxes','active');

-- ============================================================
-- 3. HOSTEL / WARDEN TABLES
-- ============================================================

DROP TABLE IF EXISTS `hostel_inspections`;
CREATE TABLE `hostel_inspections` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hostel_room_id` int DEFAULT NULL,
  `inspection_date` date NOT NULL,
  `inspected_by` int DEFAULT NULL,
  `condition_rating` varchar(20) DEFAULT 'Good',
  `cleanliness_rating` varchar(20) DEFAULT 'Good',
  `findings` text,
  `recommendations` text,
  `status` enum('Open','In Progress','Completed','Closed') DEFAULT 'Open',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `hostel_maintenance_requests`;
CREATE TABLE `hostel_maintenance_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hostel_room_id` int DEFAULT NULL,
  `requested_by` int DEFAULT NULL,
  `issue_type` varchar(100) DEFAULT NULL,
  `description` text,
  `priority` enum('Low','Medium','High','Urgent') DEFAULT 'Medium',
  `status` enum('Open','In Progress','Completed','Closed') DEFAULT 'Open',
  `assigned_to` int DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `hostel_clearance`;
CREATE TABLE `hostel_clearance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `hostel_allocation_id` int DEFAULT NULL,
  `cleared_by` int DEFAULT NULL,
  `clearance_date` date DEFAULT NULL,
  `condition_notes` text,
  `key_returned` tinyint(1) DEFAULT 0,
  `status` enum('Pending','Cleared','Rejected') DEFAULT 'Pending',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- ============================================================
-- 4. LABORATORY TABLES
-- ============================================================

DROP TABLE IF EXISTS `lab_equipment`;
CREATE TABLE `lab_equipment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `equipment_code` varchar(50) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text,
  `category` varchar(100) DEFAULT NULL,
  `quantity` int DEFAULT 1,
  `available_quantity` int DEFAULT 1,
  `condition_status` varchar(50) DEFAULT 'Good',
  `location` varchar(200) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_cost` decimal(15,2) DEFAULT NULL,
  `supplier` varchar(200) DEFAULT NULL,
  `last_maintenance_date` date DEFAULT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `status` enum('active','maintenance','retired') DEFAULT 'active',
  `notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_equip_code` (`equipment_code`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `lab_equipment_checkouts`;
CREATE TABLE `lab_equipment_checkouts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `equipment_id` int NOT NULL,
  `student_id` int DEFAULT NULL,
  `checked_out_by` int DEFAULT NULL,
  `checkout_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `expected_return_date` datetime DEFAULT NULL,
  `actual_return_date` datetime DEFAULT NULL,
  `quantity_checked_out` int DEFAULT 1,
  `quantity_returned` int DEFAULT 0,
  `purpose` text,
  `status` enum('checked_out','returned','overdue') DEFAULT 'checked_out',
  `notes` text,
  PRIMARY KEY (`id`),
  KEY `idx_checkout_equipment` (`equipment_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `lab_practical_sessions`;
CREATE TABLE `lab_practical_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_code` varchar(50) NOT NULL,
  `title` varchar(200) NOT NULL,
  `course_name` varchar(200) DEFAULT NULL,
  `instructor_name` varchar(200) DEFAULT NULL,
  `description` text,
  `instructor` varchar(200) DEFAULT NULL,
  `program` varchar(100) DEFAULT NULL,
  `year_level` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `session_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `max_students` int DEFAULT 30,
  `status` enum('scheduled','ongoing','completed','cancelled') DEFAULT 'scheduled',
  `notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_session_code` (`session_code`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `lab_skills_demonstrations`;
CREATE TABLE `lab_skills_demonstrations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `skill_name` varchar(200) NOT NULL,
  `skill_category` varchar(100) DEFAULT NULL,
  `instructor` varchar(200) DEFAULT NULL,
  `date_demonstrated` date DEFAULT NULL,
  `competency` varchar(50) DEFAULT 'Beginner',
  `attempt_number` int DEFAULT 1,
  `notes` text,
  `next_review_date` date DEFAULT NULL,
  `verified_by` int DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `lab_consumables`;
CREATE TABLE `lab_consumables` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_name` varchar(200) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `quantity` int DEFAULT 0,
  `unit` varchar(50) DEFAULT 'piece',
  `min_stock_level` int DEFAULT 5,
  `unit_cost` decimal(15,2) DEFAULT 0,
  `supplier` varchar(200) DEFAULT NULL,
  `last_ordered_date` date DEFAULT NULL,
  `notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `lab_attendance`;
CREATE TABLE `lab_attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_id` int NOT NULL,
  `student_id` int NOT NULL,
  `student_name` varchar(200) DEFAULT NULL,
  `session` varchar(100) DEFAULT NULL,
  `attendance_status` varchar(50) DEFAULT 'Present',
  `check_in_time` time DEFAULT NULL,
  `marked_by` int DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_lab_attendance` (`session_id`,`student_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `lab_incidents`;
CREATE TABLE `lab_incidents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `incident_date` date NOT NULL,
  `incident_time` time DEFAULT NULL,
  `reported_by` int DEFAULT NULL,
  `incident_type` varchar(100) DEFAULT NULL,
  `severity` varchar(50) DEFAULT 'Medium',
  `description` text,
  `equipment_involved` varchar(200) DEFAULT NULL,
  `student_involved` varchar(200) DEFAULT NULL,
  `action_taken` text,
  `status` enum('open','investigating','resolved','closed') DEFAULT 'open',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `lab_id_card_requests`;
CREATE TABLE `lab_id_card_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `request_type` varchar(50) DEFAULT 'new',
  `reason` text,
  `status` enum('pending','approved','printed','rejected') DEFAULT 'pending',
  `requested_by` int DEFAULT NULL,
  `processed_by` int DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `lab_printing_jobs`;
CREATE TABLE `lab_printing_jobs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `document_name` varchar(200) DEFAULT NULL,
  `pages` int DEFAULT 1,
  `copies` int DEFAULT 1,
  `cost` decimal(10,2) DEFAULT 0,
  `status` enum('pending','printing','completed','cancelled') DEFAULT 'pending',
  `requested_by` int DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `lab_equipment_checkout`;
CREATE TABLE `lab_equipment_checkout` (
  `id` int NOT NULL AUTO_INCREMENT,
  `equipment_id` int DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `checked_out_by` int DEFAULT NULL,
  `checkout_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `expected_return_date` datetime DEFAULT NULL,
  `actual_return_date` datetime DEFAULT NULL,
  `quantity` int DEFAULT 1,
  `purpose` text,
  `status` varchar(50) DEFAULT 'checked_out',
  `notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lec_equipment` (`equipment_id`)
) ENGINE=InnoDB;

-- ============================================================
-- 5. SECURITY TABLES
-- ============================================================

DROP TABLE IF EXISTS `security_patrols`;
CREATE TABLE `security_patrols` (
  `id` int NOT NULL AUTO_INCREMENT,
  `guard_id` int DEFAULT NULL,
  `patrol_area` varchar(200) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `patrol_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `status` enum('Scheduled','In Progress','Completed','Cancelled') DEFAULT 'Scheduled',
  `notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `security_equipment`;
CREATE TABLE `security_equipment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `equipment_name` varchar(200) NOT NULL,
  `equipment_type` varchar(100) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `status` enum('Operational','Maintenance','Retired','Damaged') DEFAULT 'Operational',
  `purchase_date` date DEFAULT NULL,
  `last_maintenance_date` date DEFAULT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `security_emergency_contacts`;
CREATE TABLE `security_emergency_contacts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `contact_name` varchar(200) NOT NULL,
  `contact_type` varchar(100) DEFAULT NULL,
  `phone_number` varchar(50) DEFAULT NULL,
  `email` varchar(200) DEFAULT NULL,
  `organization` varchar(200) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `security_visitors`;
CREATE TABLE `security_visitors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `visitor_name` varchar(200) NOT NULL,
  `visitor_phone` varchar(50) DEFAULT NULL,
  `visitor_nature` varchar(200) DEFAULT NULL,
  `person_to_visit_name` varchar(200) DEFAULT NULL,
  `visit_date` date NOT NULL,
  `expected_arrival` time DEFAULT NULL,
  `actual_arrival` time DEFAULT NULL,
  `expected_departure` time DEFAULT NULL,
  `actual_departure` time DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Expected',
  `badge_number` varchar(50) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `access_control_logs`;
CREATE TABLE `access_control_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `person_name` varchar(200) DEFAULT NULL,
  `person_type` varchar(50) DEFAULT 'Visitor',
  `access_point` varchar(100) DEFAULT NULL,
  `access_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `access_type` varchar(20) DEFAULT 'Entry',
  `badge_number` varchar(50) DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `visitor_logs`;
CREATE TABLE `visitor_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `visitor_name` varchar(200) NOT NULL,
  `visitor_phone` varchar(50) DEFAULT NULL,
  `purpose` text,
  `person_to_visit` varchar(200) DEFAULT NULL,
  `check_in_time` datetime DEFAULT CURRENT_TIMESTAMP,
  `check_out_time` datetime DEFAULT NULL,
  `badge_number` varchar(50) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- ============================================================
-- 6. HR / STAFF MANAGEMENT TABLES
-- ============================================================

DROP TABLE IF EXISTS `staff_leave_requests`;
CREATE TABLE `staff_leave_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `leave_type_id` int DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text,
  `status` enum('Pending','Approved','Rejected','Cancelled') DEFAULT 'Pending',
  `approved_by` int DEFAULT NULL,
  `reviewed_by` int DEFAULT NULL,
  `approval_date` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lr_staff` (`staff_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `staff_disciplinary`;
CREATE TABLE `staff_disciplinary` (
  `id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `incident_date` date NOT NULL,
  `offense_type` varchar(100) DEFAULT NULL,
  `description` text,
  `action_taken` text,
  `status` enum('Open','Under Investigation','Resolved','Closed') DEFAULT 'Open',
  `reported_by` int DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_disc_staff` (`staff_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `staff_licenses`;
CREATE TABLE `staff_licenses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int DEFAULT NULL,
  `license_type` varchar(100) NOT NULL,
  `license_number` varchar(100) DEFAULT NULL,
  `issuing_body` varchar(200) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'valid',
  `document_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_license_staff` (`staff_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `staff_training`;
CREATE TABLE `staff_training` (
  `id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int DEFAULT NULL,
  `training_name` varchar(200) NOT NULL,
  `training_type` varchar(100) DEFAULT NULL,
  `provider` varchar(200) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Planned',
  `certificate_path` varchar(500) DEFAULT NULL,
  `cost` decimal(15,2) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_training_staff` (`staff_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `staff_recruitment`;
CREATE TABLE `staff_recruitment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `position_title` varchar(200) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `description` text,
  `requirements` text,
  `salary_range` varchar(100) DEFAULT NULL,
  `posted_date` date DEFAULT NULL,
  `closing_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Draft',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `job_applications`;
CREATE TABLE `job_applications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `position_id` int DEFAULT NULL,
  `applicant_name` varchar(200) NOT NULL,
  `email` varchar(200) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `cv_path` varchar(500) DEFAULT NULL,
  `cover_letter` text,
  `status` varchar(50) DEFAULT 'Received',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_app_position` (`position_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `disciplinary_actions`;
CREATE TABLE `disciplinary_actions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `incident_date` date NOT NULL,
  `offense_type` varchar(100) DEFAULT NULL,
  `description` text,
  `action_taken` text,
  `status` varchar(50) DEFAULT 'Open',
  `reported_by` int DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_da_staff` (`staff_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `professional_licenses`;
CREATE TABLE `professional_licenses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `staff_name` varchar(200) NOT NULL,
  `license_number` varchar(100) DEFAULT NULL,
  `license_type` varchar(100) NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `issuing_body` varchar(200) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `trainings`;
CREATE TABLE `trainings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `description` text,
  `training_type` varchar(100) DEFAULT NULL,
  `provider` varchar(200) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `max_participants` int DEFAULT 50,
  `status` varchar(50) DEFAULT 'Planned',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `employee_training`;
CREATE TABLE `employee_training` (
  `id` int NOT NULL AUTO_INCREMENT,
  `training_id` int NOT NULL,
  `staff_id` int NOT NULL,
  `status` varchar(50) DEFAULT 'Enrolled',
  `completion_date` date DEFAULT NULL,
  `certificate_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_et_training` (`training_id`),
  KEY `idx_et_staff` (`staff_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `onboarding_checklist`;
CREATE TABLE `onboarding_checklist` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_name` varchar(200) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `leave_types`;
CREATE TABLE `leave_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type_name` varchar(100) DEFAULT NULL,
  `leave_type_name` varchar(100) DEFAULT NULL,
  `days_per_year` int DEFAULT 0,
  `description` text,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `leave_requests`;
CREATE TABLE `leave_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `leave_type_id` int DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text,
  `status` varchar(50) DEFAULT 'Pending',
  `reviewed_by` int DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lreq_staff` (`staff_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `leave_balance`;
CREATE TABLE `leave_balance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int NOT NULL,
  `leave_type_id` int DEFAULT NULL,
  `year` int NOT NULL,
  `total_days` int DEFAULT 30,
  `used_days` int DEFAULT 0,
  `remaining_days` int DEFAULT 30,
  `balance_days` int DEFAULT 30,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lb_staff` (`staff_id`)
) ENGINE=InnoDB;

-- Seed leave types
INSERT INTO `leave_types` (`id`,`type_name`,`days_per_year`) VALUES
(1,'Annual Leave',30),
(2,'Sick Leave',14),
(3,'Maternity Leave',90),
(4,'Paternity Leave',7),
(5,'Compassionate Leave',5),
(6,'Study Leave',30),
(7,'Casual Leave',10);

-- ============================================================
-- 7. STUDENT WELFARE / DISCIPLINE TABLES
-- ============================================================

DROP TABLE IF EXISTS `student_welfare_cases`;
CREATE TABLE `student_welfare_cases` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int unsigned NOT NULL,
  `case_type` varchar(100) DEFAULT NULL,
  `description` text,
  `case_description` text,
  `immediate_actions` text,
  `severity` varchar(20) DEFAULT 'medium',
  `reported_by` int unsigned DEFAULT NULL,
  `status` varchar(30) DEFAULT 'open',
  `assigned_to` int unsigned DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `student_counseling_sessions`;
CREATE TABLE `student_counseling_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `session_date` date NOT NULL,
  `session_time` time DEFAULT NULL,
  `session_type` varchar(100) DEFAULT NULL,
  `issues_discussed` text,
  `location` varchar(200) DEFAULT NULL,
  `counselor_id` int DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Scheduled',
  `follow_up_date` date DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `student_discipline`;
CREATE TABLE `student_discipline` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `incident_type` varchar(100) DEFAULT NULL,
  `offense` varchar(200) DEFAULT NULL,
  `incident_date` date DEFAULT NULL,
  `action_taken` text,
  `status` varchar(50) DEFAULT 'Pending',
  `reported_by` int DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `student_activities`;
CREATE TABLE `student_activities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `activity_name` varchar(200) DEFAULT NULL,
  `activity_type` varchar(100) DEFAULT NULL,
  `activity_date` date DEFAULT NULL,
  `expected_participants` int DEFAULT 0,
  `location` varchar(200) DEFAULT NULL,
  `description` text,
  `status` varchar(50) DEFAULT 'Planned',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- ============================================================
-- 8. DIGITAL LEARNING / SKILLS LAB
-- ============================================================

DROP TABLE IF EXISTS `skills_laboratory`;
CREATE TABLE `skills_laboratory` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lab_name` varchar(200) NOT NULL,
  `location` varchar(200) DEFAULT NULL,
  `capacity` int DEFAULT 30,
  `status` varchar(50) DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- ============================================================
-- 9. ACADEMIC / EXAMINATION TABLES
-- ============================================================

DROP TABLE IF EXISTS `examination_results`;
CREATE TABLE `examination_results` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `course_id` int DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `ca_score` decimal(5,2) DEFAULT NULL,
  `exam_score` decimal(5,2) DEFAULT NULL,
  `total_score` decimal(5,2) DEFAULT NULL,
  `grade` varchar(5) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Draft',
  `entered_by` int DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `result_approvals`;
CREATE TABLE `result_approvals` (
  `id` int NOT NULL AUTO_INCREMENT,
  `result_id` int NOT NULL,
  `approved_by` int DEFAULT NULL,
  `approval_date` datetime DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `comments` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `teaching_resources`;
CREATE TABLE `teaching_resources` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `resource_type` varchar(100) DEFAULT NULL,
  `course_id` int DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `description` text,
  `uploaded_by` int DEFAULT NULL,
  `status` varchar(50) DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `grading_approval_workflow_log`;
CREATE TABLE `grading_approval_workflow_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `result_id` int DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `acted_by` int DEFAULT NULL,
  `comments` text,
  `acted_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- ============================================================
-- 10. TRANSPORT TABLES
-- ============================================================

DROP TABLE IF EXISTS `trip_logs`;
CREATE TABLE `trip_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `vehicle_id` int DEFAULT NULL,
  `driver_id` int DEFAULT NULL,
  `trip_date` date NOT NULL,
  `destination` varchar(200) DEFAULT NULL,
  `purpose` text,
  `start_km` int DEFAULT 0,
  `end_km` int DEFAULT 0,
  `fuel_cost` decimal(10,2) DEFAULT 0,
  `status` varchar(50) DEFAULT 'Scheduled',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- ============================================================
-- 11. AUDIT / ACCESS / CACHE TABLES
-- ============================================================

DROP TABLE IF EXISTS `access_logs`;
CREATE TABLE `access_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `user_type` varchar(50) DEFAULT 'staff',
  `action` varchar(200) NOT NULL,
  `module` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_access_user` (`user_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `cache_data`;
CREATE TABLE `cache_data` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cache_key` varchar(255) NOT NULL,
  `cache_value` longtext,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cache_key` (`cache_key`)
) ENGINE=InnoDB;

-- ============================================================
-- 12. CLINICAL / ASSESSMENT / ATTENDANCE
-- ============================================================

DROP TABLE IF EXISTS `clinical_training`;
CREATE TABLE `clinical_training` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `rotation_type` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `supervisor` varchar(200) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Scheduled',
  `evaluation_score` decimal(5,2) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `assessments`;
CREATE TABLE `assessments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `course_id` int DEFAULT NULL,
  `assessment_type` varchar(100) DEFAULT NULL,
  `total_marks` int DEFAULT 100,
  `due_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Draft',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `assessment_scores`;
CREATE TABLE `assessment_scores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `assessment_id` int NOT NULL,
  `student_id` int NOT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `feedback` text,
  `graded_by` int DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_score_assessment` (`assessment_id`),
  KEY `idx_score_student` (`student_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `attendance_status`;
CREATE TABLE `attendance_status` (
  `id` int NOT NULL AUTO_INCREMENT,
  `staff_id` int DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `check_in_time` time DEFAULT NULL,
  `check_out_time` time DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Present',
  `notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_att_staff` (`staff_id`)
) ENGINE=InnoDB;

-- ============================================================
-- 13. ADDITIONAL MISSING TABLES
-- ============================================================

DROP TABLE IF EXISTS `student_health_records`;
CREATE TABLE `student_health_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `record_type` varchar(100) DEFAULT NULL,
  `description` text,
  `recorded_by` int DEFAULT NULL,
  `record_date` date DEFAULT NULL,
  `notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_shr_student` (`student_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `student_emergency_contacts`;
CREATE TABLE `student_emergency_contacts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `contact_name` varchar(200) NOT NULL,
  `relationship` varchar(100) DEFAULT NULL,
  `phone_number` varchar(50) DEFAULT NULL,
  `email` varchar(200) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sec_student` (`student_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `student_guardian`;
CREATE TABLE `student_guardian` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `gname` varchar(200) DEFAULT NULL,
  `gphone` varchar(50) DEFAULT NULL,
  `gemail` varchar(200) DEFAULT NULL,
  `relationship` varchar(100) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sg_student` (`student_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `teacher_guardian`;
CREATE TABLE `teacher_guardian` (
  `id` int NOT NULL AUTO_INCREMENT,
  `teacher_id` int DEFAULT NULL,
  `gname` varchar(200) DEFAULT NULL,
  `gphone` varchar(50) DEFAULT NULL,
  `gemail` varchar(200) DEFAULT NULL,
  `relationship` varchar(100) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tg_teacher` (`teacher_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `student_finance`;
CREATE TABLE `student_finance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `transaction_type` varchar(50) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT 0,
  `balance` decimal(15,2) DEFAULT 0,
  `description` text,
  `reference_number` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sf_student` (`student_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `student_academic_profiles`;
CREATE TABLE `student_academic_profiles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `program` varchar(200) DEFAULT NULL,
  `level` varchar(20) DEFAULT NULL,
  `gpa` decimal(4,2) DEFAULT NULL,
  `total_credits` int DEFAULT 0,
  `academic_standing` varchar(50) DEFAULT 'Good',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sap_student` (`student_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `library_clearance`;
CREATE TABLE `library_clearance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `cleared_by` int DEFAULT NULL,
  `clearance_date` datetime DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `notes` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lc_student` (`student_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `route_schedules`;
CREATE TABLE `route_schedules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `route_name` varchar(200) DEFAULT NULL,
  `vehicle_id` int DEFAULT NULL,
  `driver_id` int DEFAULT NULL,
  `departure_time` time DEFAULT NULL,
  `arrival_time` time DEFAULT NULL,
  `route_start` varchar(200) DEFAULT NULL,
  `route_end` varchar(200) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `salary_structures`;
CREATE TABLE `salary_structures` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role_id` int DEFAULT NULL,
  `role_name` varchar(200) DEFAULT NULL,
  `base_salary` decimal(15,2) DEFAULT 0,
  `housing_allowance` decimal(15,2) DEFAULT 0,
  `transport_allowance` decimal(15,2) DEFAULT 0,
  `medical_allowance` decimal(15,2) DEFAULT 0,
  `effective_from` date DEFAULT NULL,
  `effective_to` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

-- ============================================================
-- 14. PAYROLL EXTENSION TABLES
-- ============================================================

DROP TABLE IF EXISTS `payroll_bonus`;
CREATE TABLE `payroll_bonus` (
  `id` int NOT NULL AUTO_INCREMENT,
  `payroll_employee_id` int DEFAULT NULL,
  `bonus_type` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT 0,
  `bonus_date` date DEFAULT NULL,
  `description` text,
  `approved_by` int DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pb_employee` (`payroll_employee_id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `payroll_periods`;
CREATE TABLE `payroll_periods` (
  `id` int NOT NULL AUTO_INCREMENT,
  `period_name` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Open',
  `processed_by` int DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `payroll_items`;
CREATE TABLE `payroll_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `payroll_run_id` int DEFAULT NULL,
  `payroll_employee_id` int DEFAULT NULL,
  `staff_id` int DEFAULT NULL,
  `basic_salary` decimal(15,2) DEFAULT 0,
  `total_allowances` decimal(15,2) DEFAULT 0,
  `total_deductions` decimal(15,2) DEFAULT 0,
  `net_salary` decimal(15,2) DEFAULT 0,
  `status` varchar(50) DEFAULT 'Draft',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pi_employee` (`payroll_employee_id`)
) ENGINE=InnoDB;

-- ============================================================
-- 15. STUDENTS_DB TABLES (cross-database, fully qualified)
-- ============================================================

DROP TABLE IF EXISTS `igangaschoolofl_students_db.notifications`;
CREATE TABLE `igangaschoolofl_students_db.notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `igangaschoolofl_students_db.fee_structure`;
CREATE TABLE `igangaschoolofl_students_db.fee_structure` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `fee_type` varchar(100) DEFAULT NULL,
  `total_amount` decimal(15,2) DEFAULT 0,
  `amount_paid` decimal(15,2) DEFAULT 0,
  `balance` decimal(15,2) DEFAULT 0,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_fs_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `igangaschoolofl_students_db.scholarships`;
CREATE TABLE `igangaschoolofl_students_db.scholarships` (
  `id` int NOT NULL AUTO_INCREMENT,
  `scholarship_name` varchar(200) NOT NULL,
  `description` text,
  `amount` decimal(15,2) DEFAULT 0,
  `eligibility` text,
  `status` varchar(50) DEFAULT 'Active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `igangaschoolofl_students_db.student_scholarships`;
CREATE TABLE `igangaschoolofl_students_db.student_scholarships` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `scholarship_id` int DEFAULT NULL,
  `awarded_date` date DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT 0,
  `status` varchar(50) DEFAULT 'Active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ss_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `igangaschoolofl_students_db.bank_accounts`;
CREATE TABLE `igangaschoolofl_students_db.bank_accounts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `bank_name` varchar(200) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `account_name` varchar(200) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ba_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `igangaschoolofl_students_db.journal_entries`;
CREATE TABLE `igangaschoolofl_students_db.journal_entries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `entry_date` date NOT NULL,
  `description` text,
  `reference_number` varchar(100) DEFAULT NULL,
  `total_debit` decimal(15,2) DEFAULT 0,
  `total_credit` decimal(15,2) DEFAULT 0,
  `status` varchar(50) DEFAULT 'Draft',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

DROP TABLE IF EXISTS `igangaschoolofl_students_db.journal_entry_lines`;
CREATE TABLE `igangaschoolofl_students_db.journal_entry_lines` (
  `id` int NOT NULL AUTO_INCREMENT,
  `entry_id` int DEFAULT NULL,
  `account_name` varchar(200) DEFAULT NULL,
  `debit` decimal(15,2) DEFAULT 0,
  `credit` decimal(15,2) DEFAULT 0,
  `description` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_jel_entry` (`entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;

-- ============================================================
-- VERIFICATION: Run this to confirm all tables were created
-- ============================================================
SELECT TABLE_NAME, TABLE_ROWS FROM information_schema.TABLES
WHERE TABLE_SCHEMA = 'igangaschoolofl_staffs_db'
AND TABLE_NAME IN (
  'payroll_allowance_types','payroll_deduction_types','payroll_employee_allowances',
  'payroll_employee_deductions','payroll_loans','payroll_payments','payroll_payslips',
  'payroll_audit_logs','payroll_approval_history','store_categories','store_inventory',
  'store_inventory_transactions','store_request_items','store_order_items',
  'hostel_inspections','hostel_maintenance_requests','hostel_clearance',
  'lab_equipment','lab_equipment_checkouts','lab_practical_sessions',
  'lab_skills_demonstrations','lab_consumables','lab_attendance','lab_incidents',
  'lab_id_card_requests','lab_printing_jobs','lab_equipment_checkout',
  'security_patrols','security_equipment','security_emergency_contacts',
  'security_visitors','access_control_logs','visitor_logs',
  'staff_leave_requests','staff_disciplinary','staff_licenses','staff_training',
  'staff_recruitment','job_applications','disciplinary_actions',
  'professional_licenses','trainings','employee_training','onboarding_checklist',
  'leave_types','leave_requests','leave_balance',
  'student_welfare_cases','student_counseling_sessions','student_discipline',
  'student_activities','skills_laboratory','examination_results',
  'result_approvals','teaching_resources','grading_approval_workflow_log',
  'trip_logs','access_logs','cache_data','clinical_training','assessments',
  'assessment_scores','attendance_status','student_health_records',
  'student_emergency_contacts','student_guardian','teacher_guardian',
  'student_finance','student_academic_profiles','library_clearance',
  'route_schedules','salary_structures','payroll_bonus','payroll_periods',
  'payroll_items'
) ORDER BY TABLE_NAME;
