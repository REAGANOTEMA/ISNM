-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 25, 2026 at 06:35 PM
-- Server version: 8.0.45
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `igangaschoolofl_students_db`
--

DELIMITER $$
--
-- Procedures
--
CREATE PROCEDURE `AddColIfMissing` (IN `p_schema` VARCHAR(255), IN `p_table` VARCHAR(255), IN `p_col` VARCHAR(255), IN `p_def` TEXT)   BEGIN
    DECLARE cnt INT DEFAULT 0;
    SELECT COUNT(*) INTO cnt FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = p_schema AND TABLE_NAME = p_table AND COLUMN_NAME = p_col;
    IF cnt = 0 THEN
        SET @s = CONCAT('ALTER TABLE `', p_schema, '`.`', p_table, '` ADD COLUMN `', p_col, '` ', p_def);
        PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `academic_registrar_activity_log`
--

CREATE TABLE IF NOT EXISTS `academic_registrar_activity_log` (
  `id` int NOT NULL,
  `activity` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE IF NOT EXISTS `announcements` (
  `id` int NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_audience` enum('All','Nursing','Midwifery','Year1','Year2','Year3','Staff') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'All',
  `priority` enum('Normal','High','Urgent') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Normal',
  `posted_by` int DEFAULT NULL,
  `expires_at` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE IF NOT EXISTS `assets` (
  `id` int NOT NULL,
  `asset_tag` varchar(50) NOT NULL,
  `asset_name` varchar(255) NOT NULL,
  `category_id` int DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_price` decimal(12,2) DEFAULT NULL,
  `current_value` decimal(12,2) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `assigned_to` int DEFAULT NULL,
  `status` enum('Active','Disposed','Lost','Under Maintenance') DEFAULT 'Active',
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_categories`
--

CREATE TABLE IF NOT EXISTS `asset_categories` (
  `id` int NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text,
  `depreciation_rate` decimal(5,2) DEFAULT '0.00',
  `useful_life_years` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bank_transactions`
--

CREATE TABLE IF NOT EXISTS `bank_transactions` (
  `id` int NOT NULL,
  `transaction_date` date NOT NULL,
  `description` varchar(255) DEFAULT '',
  `reference` varchar(100) DEFAULT '',
  `debit` decimal(12,2) DEFAULT '0.00',
  `credit` decimal(12,2) DEFAULT '0.00',
  `balance` decimal(12,2) DEFAULT '0.00',
  `reconciled` tinyint(1) DEFAULT '0',
  `reconciled_by` int DEFAULT '0',
  `reconciled_at` datetime DEFAULT NULL,
  `bank_account` varchar(100) DEFAULT '',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
--

CREATE TABLE IF NOT EXISTS `budgets` (
  `id` int NOT NULL,
  `budget_name` varchar(255) NOT NULL,
  `fiscal_year` varchar(20) NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('Draft','Approved','Active','Closed') DEFAULT 'Draft',
  `approved_by` int DEFAULT NULL,
  `approved_date` timestamp NULL DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budget_records`
--

CREATE TABLE IF NOT EXISTS `budget_records` (
  `id` int NOT NULL,
  `budget_id` int NOT NULL,
  `budget_item` varchar(255) NOT NULL,
  `allocated_amount` decimal(12,2) NOT NULL,
  `spent_amount` decimal(12,2) DEFAULT '0.00',
  `remaining_amount` decimal(12,2) GENERATED ALWAYS AS ((`allocated_amount` - `spent_amount`)) STORED,
  `status` enum('Active','Exhausted','Cancelled') DEFAULT 'Active',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_general_ledger`
--

CREATE TABLE IF NOT EXISTS `bursar_general_ledger` (
  `id` int NOT NULL,
  `entry_number` varchar(50) NOT NULL,
  `account_id` int DEFAULT '0',
  `cost_center_id` int DEFAULT '0',
  `transaction_type` enum('Debit','Credit') NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `reference_type` varchar(50) DEFAULT '',
  `reference_id` varchar(50) DEFAULT '',
  `description` text,
  `entry_date` date DEFAULT (curdate()),
  `posted_by` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_tax_filings`
--

CREATE TABLE IF NOT EXISTS `bursar_tax_filings` (
  `id` int NOT NULL,
  `tax_period_id` int NOT NULL,
  `filing_date` date DEFAULT (curdate()),
  `total_revenue` decimal(12,2) DEFAULT '0.00',
  `total_tax` decimal(12,2) DEFAULT '0.00',
  `filing_reference` varchar(100) DEFAULT '',
  `status` enum('Draft','Filed','Amended') DEFAULT 'Draft',
  `filed_by` int DEFAULT '0',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_tax_periods`
--

CREATE TABLE IF NOT EXISTS `bursar_tax_periods` (
  `id` int NOT NULL,
  `period_name` varchar(100) NOT NULL,
  `fiscal_year` varchar(10) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('Open','Closed','Filed') DEFAULT 'Open',
  `created_by` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_users`
--

CREATE TABLE IF NOT EXISTS `bursar_users` (
  `id` int NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('bursar','accounts_assistant','auditor') DEFAULT 'bursar',
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cash_book`
--

CREATE TABLE IF NOT EXISTS `cash_book` (
  `id` int NOT NULL,
  `entry_number` varchar(50) NOT NULL,
  `entry_type` enum('Receipt','Payment') NOT NULL,
  `description` text NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `balance` decimal(15,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `related_student_id` int DEFAULT NULL,
  `transaction_date` date DEFAULT (curdate()),
  `recorded_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chart_of_accounts`
--

CREATE TABLE IF NOT EXISTS `chart_of_accounts` (
  `id` int NOT NULL,
  `account_code` varchar(20) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `account_type` enum('Asset','Liability','Equity','Revenue','Expense') NOT NULL,
  `parent_account_id` int DEFAULT NULL,
  `description` text,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chart_of_accounts`
--

INSERT INTO `chart_of_accounts` (`id`, `account_code`, `account_name`, `account_type`, `parent_account_id`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, '1000', 'Cash and Cash Equivalents', 'Asset', NULL, 'Cash on hand and in bank', 1, '2026-06-14 19:51:20', '2026-06-14 19:51:20'),
(2, '1100', 'Accounts Receivable', 'Asset', NULL, 'Student fees receivable', 1, '2026-06-14 19:51:20', '2026-06-14 19:51:20'),
(3, '1200', 'Inventory', 'Asset', NULL, 'Supplies and inventory', 1, '2026-06-14 19:51:20', '2026-06-14 19:51:20'),
(4, '1500', 'Fixed Assets', 'Asset', NULL, 'Property, plant and equipment', 1, '2026-06-14 19:51:20', '2026-06-14 19:51:20'),
(5, '2000', 'Accounts Payable', 'Liability', NULL, 'Amounts owed to suppliers', 1, '2026-06-14 19:51:20', '2026-06-14 19:51:20'),
(6, '2100', 'Accrued Liabilities', 'Liability', NULL, 'Accrued expenses', 1, '2026-06-14 19:51:20', '2026-06-14 19:51:20'),
(7, '3000', 'Net Assets', 'Equity', NULL, 'Institution net worth', 1, '2026-06-14 19:51:20', '2026-06-14 19:51:20'),
(8, '4000', 'Tuition Revenue', 'Revenue', NULL, 'Income from student tuition', 1, '2026-06-14 19:51:20', '2026-06-14 19:51:20'),
(9, '4100', 'Registration Revenue', 'Revenue', NULL, 'Income from student registration', 1, '2026-06-14 19:51:20', '2026-06-14 19:51:20'),
(10, '4200', 'Other Revenue', 'Revenue', NULL, 'Miscellaneous income', 1, '2026-06-14 19:51:20', '2026-06-14 19:51:20'),
(11, '5000', 'Salary Expenses', 'Expense', NULL, 'Staff salaries and wages', 1, '2026-06-14 19:51:20', '2026-06-14 19:51:20'),
(12, '5100', 'Administrative Expenses', 'Expense', NULL, 'Office and administrative costs', 1, '2026-06-14 19:51:20', '2026-06-14 19:51:20'),
(13, '5200', 'Operational Expenses', 'Expense', NULL, 'Day-to-day operational costs', 1, '2026-06-14 19:51:20', '2026-06-14 19:51:20'),
(14, '5300', 'Maintenance Expenses', 'Expense', NULL, 'Facility maintenance costs', 1, '2026-06-14 19:51:20', '2026-06-14 19:51:20');

-- --------------------------------------------------------

--
-- Table structure for table `clinical_placements`
--

CREATE TABLE IF NOT EXISTS `clinical_placements` (
  `id` int NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `facility_name` varchar(255) NOT NULL,
  `facility_location` varchar(255) DEFAULT NULL,
  `supervisor_name` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `hours_completed` int DEFAULT '0',
  `skills_assessment` text,
  `status` enum('Active','Completed','Cancelled') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clinical_placements_students`
--

CREATE TABLE IF NOT EXISTS `clinical_placements_students` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `placement_site` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `supervisor_name` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `competency_score` decimal(5,2) DEFAULT NULL,
  `logbook_submitted` tinyint(1) DEFAULT '0',
  `supervisor_evaluation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `status` enum('Scheduled','Active','Completed','Withdrawn') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Scheduled',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_submissions`
--

CREATE TABLE IF NOT EXISTS `contact_submissions` (
  `id` int NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read','replied') DEFAULT 'unread',
  `notified` tinyint(1) DEFAULT '0',
  `replied_at` datetime DEFAULT NULL,
  `replied_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cost_centers`
--

CREATE TABLE IF NOT EXISTS `cost_centers` (
  `id` int NOT NULL,
  `cost_center_code` varchar(20) NOT NULL,
  `cost_center_name` varchar(255) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `description` text,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cost_centers`
--

INSERT INTO `cost_centers` (`id`, `cost_center_code`, `cost_center_name`, `department`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'CC-EXEC', 'Executive Office', 'Executive Office', NULL, 1, '2026-06-14 19:51:20', '2026-06-14 19:51:20'),
(2, 'CC-NUR', 'Nursing Department', 'Nursing Department', NULL, 1, '2026-06-14 19:51:20', '2026-06-14 19:51:20'),
(3, 'CC-MID', 'Midwifery Department', 'Midwifery Department', NULL, 1, '2026-06-14 19:51:20', '2026-06-14 19:51:20'),
(4, 'CC-ACAD', 'Academic Affairs', 'Academic Affairs', NULL, 1, '2026-06-14 19:51:20', '2026-06-14 19:51:20'),
(5, 'CC-FIN', 'Finance Department', 'Finance Department', NULL, 1, '2026-06-14 19:51:20', '2026-06-14 19:51:20'),
(6, 'CC-HR', 'Human Resources', 'Human Resources', NULL, 1, '2026-06-14 19:51:20', '2026-06-14 19:51:20'),
(7, 'CC-LIB', 'Library Services', 'Library Services', NULL, 1, '2026-06-14 19:51:20', '2026-06-14 19:51:20'),
(8, 'CC-STU', 'Student Affairs', 'Student Affairs', NULL, 1, '2026-06-14 19:51:20', '2026-06-14 19:51:20'),
(9, 'CC-SEC', 'Security Services', 'Security Services', NULL, 1, '2026-06-14 19:51:20', '2026-06-14 19:51:20'),
(10, 'CC-ICT', 'Information Technology', 'Information Technology', NULL, 1, '2026-06-14 19:51:20', '2026-06-14 19:51:20'),
(11, 'CC-FAC', 'Facilities Management', 'Facilities Management', NULL, 1, '2026-06-14 19:51:20', '2026-06-14 19:51:20');

-- --------------------------------------------------------

--
-- Table structure for table `daily_sick_records`
--

CREATE TABLE IF NOT EXISTS `daily_sick_records` (
  `id` int NOT NULL,
  `record_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `student_id` int NOT NULL,
  `student_name` varchar(300) COLLATE utf8mb4_general_ci NOT NULL,
  `student_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `program` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `year_of_study` int DEFAULT NULL,
  `sickness_id` int DEFAULT NULL,
  `sickness_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `temperature` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `blood_pressure` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `symptoms` text COLLATE utf8mb4_general_ci,
  `diagnosis` text COLLATE utf8mb4_general_ci,
  `treatment_given` text COLLATE utf8mb4_general_ci,
  `medicines_prescribed` text COLLATE utf8mb4_general_ci,
  `severity` enum('Mild','Moderate','Severe','Critical') COLLATE utf8mb4_general_ci DEFAULT 'Mild',
  `status` enum('Treated','Referred','Admitted','Discharged','Follow-up','Critical') COLLATE utf8mb4_general_ci DEFAULT 'Treated',
  `referred_to` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `attended_by` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `visit_date` date NOT NULL,
  `visit_time` time DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `department_requests`
--

CREATE TABLE IF NOT EXISTS `department_requests` (
  `id` int NOT NULL,
  `request_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `from_department` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `to_department` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Store',
  `item_name` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int DEFAULT '1',
  `unit` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purpose` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `urgency` enum('Normal','Urgent','Emergency') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Normal',
  `status` enum('Pending','Approved','Rejected','Fulfilled') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `requested_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `donations`
--

CREATE TABLE IF NOT EXISTS `donations` (
  `id` int NOT NULL,
  `donor_name` varchar(200) NOT NULL,
  `donor_email` varchar(255) NOT NULL,
  `donor_phone` varchar(50) NOT NULL,
  `donor_address` varchar(500) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_provider` varchar(50) DEFAULT NULL,
  `transaction_reference` varchar(100) DEFAULT NULL,
  `purpose` varchar(200) DEFAULT 'General Donation',
  `notes` text,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `notified` tinyint(1) DEFAULT '0',
  `acknowledged_at` datetime DEFAULT NULL,
  `acknowledged_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenditure_records`
--

CREATE TABLE IF NOT EXISTS `expenditure_records` (
  `id` int NOT NULL,
  `expenditure_number` varchar(50) NOT NULL,
  `budget_record_id` int DEFAULT NULL,
  `expenditure_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `receipt_number` varchar(100) DEFAULT NULL,
  `expenditure_date` date DEFAULT (curdate()),
  `approved_by` int DEFAULT NULL,
  `recorded_by` int DEFAULT NULL,
  `supporting_document` varchar(500) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_adjustments`
--

CREATE TABLE IF NOT EXISTS `fee_adjustments` (
  `id` int NOT NULL,
  `adjustment_number` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `invoice_id` int DEFAULT NULL,
  `adjustment_type` enum('Discount','Waiver','Penalty','Refund','Other') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `reason` text NOT NULL,
  `approved_by` int DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_reminders`
--

CREATE TABLE IF NOT EXISTS `fee_reminders` (
  `id` int NOT NULL,
  `reminder_number` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `invoice_id` int DEFAULT NULL,
  `reminder_type` enum('Email','SMS','Letter','Call') DEFAULT 'Email',
  `reminder_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_by` int DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_structures`
--

CREATE TABLE IF NOT EXISTS `fee_structures` (
  `id` int NOT NULL,
  `fee_name` varchar(255) NOT NULL,
  `fee_type` enum('Tuition','Registration','Library','Laboratory','Examination','Graduation','Other') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `program_id` int DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `is_mandatory` tinyint(1) DEFAULT '1',
  `due_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_clearance`
--

CREATE TABLE IF NOT EXISTS `financial_clearance` (
  `id` int NOT NULL,
  `student_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Annual',
  `clearance_status` enum('Cleared','Not Cleared','Pending Review') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending Review',
  `cleared_by` int DEFAULT NULL,
  `cleared_at` timestamp NULL DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_reports`
--

CREATE TABLE IF NOT EXISTS `financial_reports` (
  `id` int NOT NULL,
  `report_name` varchar(255) NOT NULL,
  `report_type` enum('Income Statement','Balance Sheet','Cash Flow','Budget vs Actual','Fee Collection','Expenditure','Custom') NOT NULL,
  `report_period` varchar(50) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `report_data` longtext,
  `generated_by` int DEFAULT NULL,
  `generated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('Draft','Final','Archived') DEFAULT 'Draft',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `general_ledger`
--

CREATE TABLE IF NOT EXISTS `general_ledger` (
  `id` int NOT NULL,
  `entry_number` varchar(50) NOT NULL,
  `account_id` int NOT NULL,
  `cost_center_id` int DEFAULT NULL,
  `transaction_type` enum('Debit','Credit') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int DEFAULT NULL,
  `description` text,
  `transaction_date` date DEFAULT (curdate()),
  `posted_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `graduation_candidates`
--

CREATE TABLE IF NOT EXISTS `graduation_candidates` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `academic_year` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `graduation_date` date DEFAULT NULL,
  `status` enum('Pending','Cleared','Graduated','Deferred') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `clearance_bursar` tinyint(1) DEFAULT '0',
  `clearance_library` tinyint(1) DEFAULT '0',
  `clearance_registrar` tinyint(1) DEFAULT '0',
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hostel_allocations`
--

CREATE TABLE IF NOT EXISTS `hostel_allocations` (
  `id` int NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `room_id` int NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `check_in_date` date DEFAULT (curdate()),
  `check_out_date` date DEFAULT NULL,
  `status` enum('Active','Checked Out','Cancelled') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hostel_rooms`
--

CREATE TABLE IF NOT EXISTS `hostel_rooms` (
  `id` int NOT NULL,
  `room_number` varchar(20) NOT NULL,
  `hostel_name` varchar(100) NOT NULL,
  `capacity` int NOT NULL DEFAULT '4',
  `occupancy` int NOT NULL DEFAULT '0',
  `fee_per_semester` decimal(12,2) DEFAULT '0.00',
  `status` enum('Available','Full','Maintenance') DEFAULT 'Available',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `income_tax_rates`
--

CREATE TABLE IF NOT EXISTS `income_tax_rates` (
  `id` int NOT NULL,
  `tax_bracket_name` varchar(100) NOT NULL,
  `min_income` decimal(12,2) NOT NULL DEFAULT '0.00',
  `max_income` decimal(12,2) DEFAULT NULL,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `fiscal_year` varchar(10) NOT NULL,
  `is_active` tinyint(1) DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_attendance`
--

CREATE TABLE IF NOT EXISTS `lab_attendance` (
  `id` int NOT NULL,
  `session_id` int NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `attendance_status` enum('present','absent','late','excused') DEFAULT 'present',
  `check_in_time` time DEFAULT NULL,
  `check_out_time` time DEFAULT NULL,
  `marked_by` int DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_consumables`
--

CREATE TABLE IF NOT EXISTS `lab_consumables` (
  `id` int NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT '0.00',
  `unit` varchar(50) NOT NULL DEFAULT 'pieces',
  `min_stock_level` decimal(10,2) DEFAULT '10.00',
  `unit_cost` decimal(10,2) DEFAULT '0.00',
  `supplier` varchar(255) DEFAULT NULL,
  `last_ordered_date` date DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_equipment`
--

CREATE TABLE IF NOT EXISTS `lab_equipment` (
  `id` int NOT NULL,
  `equipment_code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `category` enum('mannequin','model','instrument','furniture','consumable','other') NOT NULL DEFAULT 'other',
  `quantity` int NOT NULL DEFAULT '1',
  `available_quantity` int NOT NULL DEFAULT '1',
  `condition_status` enum('excellent','good','fair','poor') DEFAULT 'good',
  `location` varchar(255) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_cost` decimal(12,2) DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `last_maintenance_date` date DEFAULT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `status` enum('active','maintenance','retired') DEFAULT 'active',
  `image_url` varchar(500) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_equipment_checkouts`
--

CREATE TABLE IF NOT EXISTS `lab_equipment_checkouts` (
  `id` int NOT NULL,
  `equipment_id` int NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `checked_out_by` int NOT NULL COMMENT 'staff_id',
  `checkout_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `expected_return_date` date NOT NULL,
  `actual_return_date` datetime DEFAULT NULL,
  `quantity_checked_out` int NOT NULL DEFAULT '1',
  `quantity_returned` int DEFAULT '0',
  `purpose` varchar(255) DEFAULT NULL,
  `notes` text,
  `status` enum('checked_out','returned','overdue','lost_damaged') DEFAULT 'checked_out',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_incidents`
--

CREATE TABLE IF NOT EXISTS `lab_incidents` (
  `id` int NOT NULL,
  `incident_date` date NOT NULL DEFAULT (curdate()),
  `incident_time` time DEFAULT NULL,
  `reported_by` int DEFAULT NULL,
  `incident_type` enum('injury','equipment_damage','safety_hazard','near_miss','other') NOT NULL DEFAULT 'other',
  `severity` enum('minor','moderate','serious','critical') DEFAULT 'minor',
  `description` text NOT NULL,
  `equipment_involved` varchar(255) DEFAULT NULL,
  `student_involved` varchar(255) DEFAULT NULL,
  `action_taken` text,
  `status` enum('open','investigating','resolved','closed') DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_practical_sessions`
--

CREATE TABLE IF NOT EXISTS `lab_practical_sessions` (
  `id` int NOT NULL,
  `session_code` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `instructor` varchar(255) DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `session_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `max_students` int DEFAULT '30',
  `status` enum('scheduled','ongoing','completed','cancelled') DEFAULT 'scheduled',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_skills_demonstrations`
--

CREATE TABLE IF NOT EXISTS `lab_skills_demonstrations` (
  `id` int NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `skill_name` varchar(255) NOT NULL,
  `skill_category` varchar(100) DEFAULT NULL,
  `instructor` varchar(255) DEFAULT NULL,
  `date_demonstrated` date NOT NULL DEFAULT (curdate()),
  `competency` enum('exceeds_expectations','meets_expectations','needs_improvement','unsatisfactory') DEFAULT 'meets_expectations',
  `attempt_number` int DEFAULT '1',
  `notes` text,
  `next_review_date` date DEFAULT NULL,
  `verified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `late_payment_settings`
--

CREATE TABLE IF NOT EXISTS `late_payment_settings` (
  `id` int NOT NULL,
  `setting_key` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `late_payment_settings`
--

INSERT INTO `late_payment_settings` (`id`, `setting_key`, `setting_value`, `description`, `updated_by`, `updated_at`) VALUES
(1, 'grace_period_days', '15', 'Days after due date before late fee applies', NULL, '2026-06-21 08:58:13'),
(2, 'late_fee_percentage', '5', 'Percentage penalty on outstanding amount', NULL, '2026-06-21 08:58:13'),
(3, 'late_fee_fixed', '20000', 'Fixed late fee amount (UGX)', NULL, '2026-06-21 08:58:13'),
(4, 'max_late_fee', '100000', 'Maximum late fee cap (UGX)', NULL, '2026-06-21 08:58:13');

-- --------------------------------------------------------

--
-- Table structure for table `library_books`
--

CREATE TABLE IF NOT EXISTS `library_books` (
  `id` int NOT NULL,
  `book_title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `isbn` varchar(50) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `publication_year` year DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `total_copies` int DEFAULT '1',
  `available_copies` int DEFAULT '1',
  `shelf_location` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_borrowing`
--

CREATE TABLE IF NOT EXISTS `library_borrowing` (
  `id` int NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `book_id` int NOT NULL,
  `borrow_date` date DEFAULT (curdate()),
  `due_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `fine_amount` decimal(10,2) DEFAULT '0.00',
  `fine_paid` tinyint(1) DEFAULT '0',
  `status` enum('Borrowed','Returned','Overdue','Lost') DEFAULT 'Borrowed',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_fines`
--

CREATE TABLE IF NOT EXISTS `library_fines` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `book_title` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `borrow_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `fine_amount` decimal(10,2) DEFAULT '0.00',
  `paid` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicine_stock`
--

CREATE TABLE IF NOT EXISTS `medicine_stock` (
  `id` int NOT NULL,
  `medicine_code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `medicine_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `generic_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `category` enum('Antibiotic','Painkiller','Anti-inflammatory','Antimalarial','Antiviral','Antifungal','Vitamins','First Aid','Allergy','Digestive','Respiratory','Dermatological','Ophthalmic','Other') COLLATE utf8mb4_general_ci DEFAULT 'Other',
  `dosage_form` enum('Tablet','Capsule','Syrup','Injection','Cream','Ointment','Drops','Inhaler','Suppository','Powder','Solution','Other') COLLATE utf8mb4_general_ci DEFAULT 'Tablet',
  `strength` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `manufacturer` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `supplier` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `quantity_in_stock` int NOT NULL DEFAULT '0',
  `unit` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pcs',
  `reorder_level` int DEFAULT '10',
  `unit_cost` decimal(15,2) DEFAULT NULL,
  `selling_price` decimal(15,2) DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_general_ci DEFAULT 'UGX',
  `batch_number` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `storage_location` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `requires_prescription` tinyint(1) DEFAULT '0',
  `instructions` text COLLATE utf8mb4_general_ci,
  `side_effects` text COLLATE utf8mb4_general_ci,
  `status` enum('In Stock','Low Stock','Out of Stock','Expired','Discontinued') COLLATE utf8mb4_general_ci DEFAULT 'In Stock',
  `last_restocked` date DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicine_stock`
--

INSERT INTO `medicine_stock` (`id`, `medicine_code`, `medicine_name`, `generic_name`, `category`, `dosage_form`, `strength`, `manufacturer`, `supplier`, `quantity_in_stock`, `unit`, `reorder_level`, `unit_cost`, `selling_price`, `currency`, `batch_number`, `expiry_date`, `storage_location`, `requires_prescription`, `instructions`, `side_effects`, `status`, `last_restocked`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'PARA001', 'Paracetamol', 'Acetaminophen', 'Painkiller', 'Tablet', '500mg', NULL, NULL, 200, 'tablets', 50, 50.00, NULL, 'UGX', NULL, '2027-12-31', 'Cabinet A1', 0, '1-2 tablets every 4-6 hours as needed for pain/fever', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(2, 'IBU001', 'Ibuprofen', 'Ibuprofen', 'Anti-inflammatory', 'Tablet', '400mg', NULL, NULL, 150, 'tablets', 30, 100.00, NULL, 'UGX', NULL, '2027-10-31', 'Cabinet A1', 0, '1 tablet 3 times daily after meals', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(3, 'AMOX001', 'Amoxicillin', 'Amoxicillin', 'Antibiotic', 'Capsule', '500mg', NULL, NULL, 100, 'capsules', 20, 200.00, NULL, 'UGX', NULL, '2027-08-31', 'Cabinet B1', 1, '1 capsule 3 times daily for 7 days', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(4, 'CTM001', 'Chlorpheniramine', 'Chlorpheniramine Maleate', 'Allergy', 'Tablet', '4mg', NULL, NULL, 100, 'tablets', 20, 50.00, NULL, 'UGX', NULL, '2027-11-30', 'Cabinet A2', 0, '1 tablet every 4-6 hours for allergies', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(5, 'ORS001', 'Oral Rehydration Salts', 'ORS', 'Other', 'Powder', '20.5g/sachet', NULL, NULL, 100, 'sachets', 30, 500.00, NULL, 'UGX', NULL, '2028-06-30', 'Cabinet C1', 0, 'Dissolve 1 sachet in 1L water, drink after each loose stool', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(6, 'ART001', 'Artemether/Lumefantrine', 'Coartem', 'Antimalarial', 'Tablet', '20/120mg', NULL, NULL, 60, 'tablets', 20, 1500.00, NULL, 'UGX', NULL, '2027-09-30', 'Cabinet B2', 1, '4 tablets twice daily for 3 days', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(7, 'VITC001', 'Vitamin C', 'Ascorbic Acid', 'Vitamins', 'Tablet', '500mg', NULL, NULL, 300, 'tablets', 50, 30.00, NULL, 'UGX', NULL, '2028-12-31', 'Cabinet C1', 0, '1 tablet daily for immune support', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(8, 'MET001', 'Metered Dose Inhaler', 'Salbutamol', 'Respiratory', 'Inhaler', '100mcg/dose', NULL, NULL, 10, 'inhalers', 3, 15000.00, NULL, 'UGX', NULL, '2027-06-30', 'Cabinet A3', 1, '1-2 puffs as needed for asthma symptoms', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(9, 'ANT001', 'Antacid', 'Aluminum/Magnesium Hydroxide', 'Digestive', 'Tablet', '500mg', NULL, NULL, 200, 'tablets', 40, 100.00, NULL, 'UGX', NULL, '2027-11-30', 'Cabinet C1', 0, '1-2 tablets after meals or when symptomatic', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(10, 'HYD001', 'Hydrocortisone Cream', 'Hydrocortisone', 'Dermatological', 'Cream', '1%', NULL, NULL, 20, 'tubes', 5, 5000.00, NULL, 'UGX', NULL, '2027-08-31', 'Cabinet D1', 0, 'Apply thin layer to affected area 2-3 times daily', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(11, 'DIA001', 'Diazepam', 'Diazepam', 'Painkiller', 'Tablet', '5mg', NULL, NULL, 30, 'tablets', 10, 200.00, NULL, 'UGX', NULL, '2026-12-31', 'Cabinet B2', 1, '1 tablet at bedtime for anxiety or muscle spasms', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(12, 'BAN001', 'Bandages', 'Cotton Bandage', 'First Aid', 'Other', '4 inches x 5 meters', NULL, NULL, 50, 'rolls', 10, 1500.00, NULL, 'UGX', NULL, '2029-12-31', 'Shelf E1', 0, 'For wound dressing and injury management', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(13, 'GAU001', 'Gauze Swabs', 'Sterile Gauze', 'First Aid', 'Other', '10x10cm', NULL, NULL, 200, 'packs', 50, 800.00, NULL, 'UGX', NULL, '2029-12-31', 'Shelf E1', 0, 'Sterile swabs for wound cleaning and dressing', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(14, 'GLU001', 'Glucose Powder', 'Dextrose', 'Vitamins', 'Powder', '500g', NULL, NULL, 10, 'packs', 3, 5000.00, NULL, 'UGX', NULL, '2028-06-30', 'Cabinet C1', 0, 'Mix 2 tablespoons in water for energy', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(15, 'ALC001', 'Alcohol Swabs', 'Isopropyl Alcohol', 'First Aid', 'Solution', '70%', NULL, NULL, 300, 'swabs', 50, 100.00, NULL, 'UGX', NULL, '2028-12-31', 'Shelf E1', 0, 'Use for cleaning skin before injections', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(16, 'CLO001', 'Chloroquine', 'Chloroquine Phosphate', 'Antimalarial', 'Tablet', '250mg', NULL, NULL, 50, 'tablets', 15, 300.00, NULL, 'UGX', NULL, '2027-05-31', 'Cabinet B2', 1, 'As prescribed for malaria treatment', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(17, 'MEF001', 'Mefenamic Acid', 'Mefenamic Acid', 'Painkiller', 'Capsule', '500mg', NULL, NULL, 80, 'capsules', 20, 200.00, NULL, 'UGX', NULL, '2027-07-31', 'Cabinet A1', 0, '1 capsule 3 times daily for pain and inflammation', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(18, 'METR001', 'Metronidazole', 'Metronidazole', 'Antibiotic', 'Tablet', '400mg', NULL, NULL, 100, 'tablets', 20, 150.00, NULL, 'UGX', NULL, '2027-09-30', 'Cabinet B1', 1, '1 tablet 3 times daily for 5-7 days', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(19, 'DIC001', 'Diclofenac Gel', 'Diclofenac Diethylamine', 'Anti-inflammatory', 'Cream', '1%', NULL, NULL, 15, 'tubes', 5, 7000.00, NULL, 'UGX', NULL, '2027-10-31', 'Cabinet D1', 0, 'Apply to affected area 3-4 times daily', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(20, 'CET001', 'Cetirizine', 'Cetirizine Hydrochloride', 'Allergy', 'Tablet', '10mg', NULL, NULL, 100, 'tablets', 20, 100.00, NULL, 'UGX', NULL, '2027-12-31', 'Cabinet A2', 0, '1 tablet daily for allergy symptoms', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(21, 'ASP001', 'Aspirin', 'Acetylsalicylic Acid', 'Painkiller', 'Tablet', '300mg', NULL, NULL, 100, 'tablets', 25, 50.00, NULL, 'UGX', NULL, '2027-06-30', 'Cabinet A1', 0, '1-2 tablets every 4-6 hours for pain/fever', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(22, 'ZIN001', 'Zinc Tablets', 'Zinc Sulfate', 'Vitamins', 'Tablet', '20mg', NULL, NULL, 150, 'tablets', 30, 100.00, NULL, 'UGX', NULL, '2028-09-30', 'Cabinet C1', 0, '1 tablet daily for immune support and wound healing', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(23, 'CLOT001', 'Clotrimazole Cream', 'Clotrimazole', 'Antifungal', 'Cream', '1%', NULL, NULL, 15, 'tubes', 5, 4000.00, NULL, 'UGX', NULL, '2027-08-31', 'Cabinet D1', 0, 'Apply to affected area twice daily for 2 weeks', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(24, 'EYE001', 'Eye Drops', 'Chloramphenicol', 'Other', 'Drops', '0.5%', NULL, NULL, 20, 'bottles', 5, 5000.00, NULL, 'UGX', NULL, '2027-04-30', 'Cabinet A3', 1, '1-2 drops in affected eye every 2-4 hours', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(25, 'BET001', 'Betadine Solution', 'Povidone-Iodine', 'First Aid', 'Solution', '10%', NULL, NULL, 10, 'bottles', 3, 8000.00, NULL, 'UGX', NULL, '2028-03-31', 'Shelf E1', 0, 'Apply to wounds for disinfection', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19');

-- --------------------------------------------------------

--
-- Table structure for table `medicine_stock_transactions`
--

CREATE TABLE IF NOT EXISTS `medicine_stock_transactions` (
  `id` int NOT NULL,
  `transaction_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `medicine_id` int NOT NULL,
  `transaction_type` enum('Purchase','Issue','Return','Adjustment','Damage','Expired') COLLATE utf8mb4_general_ci NOT NULL,
  `quantity` int NOT NULL,
  `unit_cost` decimal(15,2) DEFAULT NULL,
  `total_cost` decimal(15,2) DEFAULT NULL,
  `currency` varchar(10) COLLATE utf8mb4_general_ci DEFAULT 'UGX',
  `reference` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `issued_to` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `purpose` text COLLATE utf8mb4_general_ci,
  `performed_by` int DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE IF NOT EXISTS `news` (
  `id` int NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext,
  `excerpt` text,
  `featured_image` varchar(500) DEFAULT NULL,
  `author_id` int DEFAULT NULL,
  `author_name` varchar(255) DEFAULT NULL,
  `author_role` varchar(255) DEFAULT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int NOT NULL,
  `notification_type` enum('fee_reminder','payment_received','invoice_generated','budget_alert','system') DEFAULT 'system',
  `recipient_type` enum('student','staff','bursar') NOT NULL,
  `recipient_id` int DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `channel` enum('email','sms','in_app') DEFAULT 'in_app',
  `is_read` tinyint(1) DEFAULT '0',
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE IF NOT EXISTS `payments` (
  `id` int NOT NULL,
  `payment_reference` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `invoice_id` int DEFAULT NULL,
  `amount_received` decimal(12,2) NOT NULL,
  `payment_method` enum('Cash','Bank Transfer','Mobile Money','Cheque','Card','Other') DEFAULT 'Cash',
  `payment_date` date DEFAULT (curdate()),
  `transaction_ref` varchar(100) DEFAULT NULL,
  `slip_number` varchar(100) DEFAULT NULL,
  `status` enum('Pending','Completed','Failed','Reversed') DEFAULT 'Completed',
  `received_by` int DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_receipts`
--

CREATE TABLE IF NOT EXISTS `payment_receipts` (
  `id` int NOT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `payment_id` int NOT NULL,
  `student_id` int NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `receipt_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `issued_by` int DEFAULT NULL,
  `voided` tinyint(1) DEFAULT '0',
  `voided_at` timestamp NULL DEFAULT NULL,
  `voided_by` int DEFAULT NULL,
  `void_reason` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_subscriptions`
--

CREATE TABLE IF NOT EXISTS `payment_subscriptions` (
  `id` int NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `subscription_type` enum('fee_installment','hostel','library','other') NOT NULL DEFAULT 'fee_installment',
  `reference_type` varchar(50) DEFAULT NULL COMMENT 'eg: fee_structure_id, hostel_room_id',
  `reference_id` int DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `installment_amount` decimal(12,2) NOT NULL,
  `frequency` enum('monthly','weekly','quarterly') NOT NULL DEFAULT 'monthly',
  `total_installments` int NOT NULL,
  `installments_collected` int NOT NULL DEFAULT '0',
  `start_date` date NOT NULL DEFAULT (curdate()),
  `next_due_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `payment_method` enum('mobile_money','bank','cash') DEFAULT 'mobile_money',
  `payment_provider` varchar(50) DEFAULT NULL COMMENT 'mtn_momo, airtel_money, etc.',
  `phone_number` varchar(20) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account` varchar(50) DEFAULT NULL,
  `status` enum('active','paused','completed','cancelled','failed') NOT NULL DEFAULT 'active',
  `notes` text,
  `created_by` varchar(50) DEFAULT NULL COMMENT 'student_id or staff_id who created',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_records`
--

CREATE TABLE IF NOT EXISTS `payroll_records` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `month` int NOT NULL,
  `year` int NOT NULL,
  `gross_salary` decimal(12,2) DEFAULT '0.00',
  `total_deductions` decimal(12,2) DEFAULT '0.00',
  `net_salary` decimal(12,2) DEFAULT '0.00',
  `processed_by` int DEFAULT '0',
  `processing_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('Draft','Processed','Approved','Paid') DEFAULT 'Draft'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payslips`
--

CREATE TABLE IF NOT EXISTS `payslips` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `payroll_record_id` int DEFAULT '0',
  `month` int NOT NULL,
  `year` int NOT NULL,
  `basic_salary` decimal(12,2) DEFAULT '0.00',
  `allowances` decimal(12,2) DEFAULT '0.00',
  `deductions` decimal(12,2) DEFAULT '0.00',
  `net_pay` decimal(12,2) DEFAULT '0.00',
  `payment_date` date DEFAULT NULL,
  `status` enum('Generated','Sent','Paid') DEFAULT 'Generated',
  `generated_by` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `penalty_configurations`
--

CREATE TABLE IF NOT EXISTS `penalty_configurations` (
  `id` int NOT NULL,
  `penalty_name` varchar(100) NOT NULL,
  `penalty_type` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT '0.00',
  `description` text,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `penalty_configurations`
--

INSERT INTO `penalty_configurations` (`id`, `penalty_name`, `penalty_type`, `amount`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Late Registration', 'Late Fee', 50000.00, 'Penalty for late course registration', 1, '2026-06-14 19:51:20', '2026-06-14 19:51:20'),
(2, 'Late Payment (1-7 days)', 'Late Fee', 10000.00, 'Penalty for fee payment 1-7 days after due date', 1, '2026-06-14 19:51:20', '2026-06-14 19:51:20'),
(3, 'Late Payment (8-14 days)', 'Late Fee', 25000.00, 'Penalty for fee payment 8-14 days after due date', 1, '2026-06-14 19:51:20', '2026-06-14 19:51:20'),
(4, 'Late Payment (15+ days)', 'Late Fee', 50000.00, 'Penalty for fee payment more than 15 days after due date', 1, '2026-06-14 19:51:20', '2026-06-14 19:51:20'),
(5, 'Lost Library Book', 'Replacement', 30000.00, 'Replacement fee for lost library book', 1, '2026-06-14 19:51:20', '2026-06-14 19:51:20'),
(6, 'Damaged Property', 'Damage', 20000.00, 'Penalty for damaging school property', 1, '2026-06-14 19:51:20', '2026-06-14 19:51:20'),
(7, 'ID Card Replacement', 'Administrative', 10000.00, 'Fee for replacement of lost student ID card', 1, '2026-06-14 19:51:20', '2026-06-14 19:51:20');

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE IF NOT EXISTS `programs` (
  `id` int NOT NULL,
  `program_code` varchar(20) NOT NULL,
  `program_name` varchar(255) NOT NULL,
  `program_type` enum('Certificate','Diploma','Degree') DEFAULT 'Diploma',
  `duration_years` int DEFAULT '2',
  `total_fee` decimal(12,2) DEFAULT '0.00',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `proof_of_payments`
--

CREATE TABLE IF NOT EXISTS `proof_of_payments` (
  `id` int NOT NULL,
  `proof_number` varchar(50) NOT NULL,
  `payment_id` int NOT NULL,
  `student_id` int NOT NULL,
  `document_path` varchar(500) DEFAULT NULL,
  `uploaded_by` int DEFAULT NULL,
  `verified` tinyint(1) DEFAULT '0',
  `verified_by` int DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrar_certificates`
--

CREATE TABLE IF NOT EXISTS `registrar_certificates` (
  `id` int NOT NULL,
  `certificate_number` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `graduation_date` date DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `certificate_type` enum('Certificate','Diploma','Degree','Transcript') DEFAULT 'Certificate',
  `gpa` decimal(5,2) DEFAULT NULL,
  `cgpa` decimal(5,2) DEFAULT NULL,
  `class_of_award` varchar(100) DEFAULT NULL,
  `status` enum('Draft','Generated','Issued','Collected','Cancelled') DEFAULT 'Draft',
  `generated_by` int DEFAULT NULL,
  `generated_date` datetime DEFAULT NULL,
  `issued_by` int DEFAULT NULL,
  `issued_date` datetime DEFAULT NULL,
  `collected_by` varchar(255) DEFAULT NULL,
  `collected_date` datetime DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrar_settings`
--

CREATE TABLE IF NOT EXISTS `registrar_settings` (
  `id` int NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `setting_group` varchar(50) DEFAULT 'general',
  `description` varchar(500) DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `registrar_settings`
--

INSERT INTO `registrar_settings` (`id`, `setting_key`, `setting_value`, `setting_group`, `description`, `updated_by`, `updated_at`) VALUES
(1, 'current_academic_year', '2025', 'academic', 'Current active academic year', NULL, '2026-06-19 06:48:04'),
(2, 'current_semester', 'Semester 1', 'academic', 'Current active semester', NULL, '2026-06-19 06:48:04'),
(3, 'institution_name', 'ISNM', 'general', 'Institution Name', NULL, '2026-06-19 06:48:04'),
(4, 'transcript_fee', '50000', 'fees', 'Transcript processing fee', NULL, '2026-06-19 06:48:04'),
(5, 'certificate_fee', '100000', 'fees', 'Certificate processing fee', NULL, '2026-06-19 06:48:04'),
(6, 'grading_system', 'letter', 'academic', 'Grading system (letter/percentage/GPA)', NULL, '2026-06-19 06:48:04'),
(7, 'pass_mark', '50', 'academic', 'Minimum pass mark', NULL, '2026-06-19 06:48:04'),
(8, 'currency', 'UGX', 'general', 'Default currency', NULL, '2026-06-19 06:48:04'),
(9, 'auto_generate_transcripts', '1', 'settings', 'Auto-generate transcripts on grade approval', NULL, '2026-06-19 06:48:04'),
(10, 'graduation_batch', '2025', 'academic', 'Current graduation batch', NULL, '2026-06-19 06:48:04');

-- --------------------------------------------------------

--
-- Table structure for table `registrar_transcript_requests`
--

CREATE TABLE IF NOT EXISTS `registrar_transcript_requests` (
  `id` int NOT NULL,
  `request_number` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `purpose` varchar(500) DEFAULT NULL,
  `copies_requested` int DEFAULT '1',
  `copies_issued` int DEFAULT '0',
  `fee` decimal(10,2) DEFAULT '0.00',
  `payment_status` enum('Pending','Paid','Waived') DEFAULT 'Pending',
  `status` enum('Pending','Processing','Ready','Issued','Collected','Rejected') DEFAULT 'Pending',
  `requested_by` varchar(255) DEFAULT NULL,
  `request_date` datetime DEFAULT NULL,
  `processed_by` int DEFAULT NULL,
  `processed_date` datetime DEFAULT NULL,
  `issued_by` int DEFAULT NULL,
  `issued_date` datetime DEFAULT NULL,
  `collected_by` varchar(255) DEFAULT NULL,
  `collected_date` datetime DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `salary_components`
--

CREATE TABLE IF NOT EXISTS `salary_components` (
  `id` int NOT NULL,
  `component_name` varchar(100) NOT NULL,
  `component_type` enum('Earning','Deduction') DEFAULT 'Earning',
  `description` text,
  `is_percentage` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sickness_directory`
--

CREATE TABLE IF NOT EXISTS `sickness_directory` (
  `id` int NOT NULL,
  `sickness_code` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `sickness_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `category` enum('Infectious','Non-Infectious','Chronic','Injury','Mental Health','Nutritional','Other') COLLATE utf8mb4_general_ci DEFAULT 'Other',
  `common_symptoms` text COLLATE utf8mb4_general_ci,
  `description` text COLLATE utf8mb4_general_ci,
  `is_contagious` tinyint(1) DEFAULT '0',
  `typical_treatment` text COLLATE utf8mb4_general_ci,
  `status` enum('Active','Inactive') COLLATE utf8mb4_general_ci DEFAULT 'Active',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sickness_directory`
--

INSERT INTO `sickness_directory` (`id`, `sickness_code`, `sickness_name`, `category`, `common_symptoms`, `description`, `is_contagious`, `typical_treatment`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'MLR', 'Malaria', 'Infectious', 'Fever, chills, headache, sweating, fatigue', 'Mosquito-borne parasitic infection common in tropical regions', 0, 'Artemisinin-based combination therapy, antimalarials', 'Active', NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(2, 'TYP', 'Typhoid', 'Infectious', 'Prolonged fever, abdominal pain, headache, constipation or diarrhea', 'Bacterial infection spread through contaminated food/water', 1, 'Antibiotics (ciprofloxacin, azithromycin), hydration', 'Active', NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(3, 'FLU', 'Influenza', 'Infectious', 'Fever, cough, sore throat, body aches, fatigue', 'Viral respiratory infection spread through droplets', 1, 'Rest, fluids, antipyretics, antivirals if severe', 'Active', NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(4, 'COLD', 'Common Cold', 'Infectious', 'Runny nose, sneezing, sore throat, cough, mild fever', 'Viral upper respiratory tract infection', 1, 'Rest, antihistamines, decongestants, vitamin C', 'Active', NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(5, 'URTI', 'Upper Respiratory Tract Infection', 'Infectious', 'Cough, sore throat, nasal congestion, fever', 'Bacterial or viral infection of upper airways', 1, 'Antibiotics if bacterial, rest, fluids', 'Active', NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(6, 'HDCH', 'Headache/Tension Headache', 'Non-Infectious', 'Head pain, pressure around forehead, neck tension', 'Common tension-type headache from stress or fatigue', 0, 'Rest, analgesics (paracetamol, ibuprofen)', 'Active', NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(7, 'GSTR', 'Gastritis', 'Non-Infectious', 'Abdominal pain, nausea, bloating, indigestion', 'Inflammation of stomach lining from diet, stress, or infection', 0, 'Antacids, dietary changes, proton pump inhibitors', 'Active', NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(8, 'DIAR', 'Diarrhea', 'Infectious', 'Loose watery stools, abdominal cramps, dehydration', 'Common infection from contaminated food/water or viruses', 1, 'ORS, hydration, antidiarrheals, antibiotics if bacterial', 'Active', NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(9, 'ALLG', 'Allergic Reaction', 'Non-Infectious', 'Rash, itching, sneezing, watery eyes, swelling', 'Immune response to allergens (food, dust, pollen, drugs)', 0, 'Antihistamines, corticosteroids, avoid triggers', 'Active', NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(10, 'INJR', 'Injury/Accident', 'Injury', 'Pain, swelling, bruising, bleeding, limited mobility', 'Physical trauma from falls, sports, or accidents', 0, 'First aid, rest, ice, compression, elevation, analgesics', 'Active', NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(11, 'ANEM', 'Anemia', 'Nutritional', 'Fatigue, weakness, pale skin, shortness of breath, dizziness', 'Low red blood cell count from iron deficiency or other causes', 0, 'Iron supplements, dietary changes, B12 if needed', 'Active', NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(12, 'MALN', 'Malnutrition', 'Nutritional', 'Weight loss, fatigue, poor growth, weakened immunity', 'Inadequate nutrient intake affecting overall health', 0, 'Nutritional supplementation, diet counseling', 'Active', NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(13, 'CONS', 'Constipation', 'Non-Infectious', 'Infrequent bowel movements, straining, hard stools', 'Common digestive issue from diet or lifestyle factors', 0, 'Increased fiber intake, hydration, laxatives if needed', 'Active', NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(14, 'SORE', 'Sore Throat', 'Infectious', 'Pain or scratchiness in throat, difficulty swallowing', 'Viral or bacterial throat infection', 1, 'Warm salt water gargle, lozenges, antibiotics if strep', 'Active', NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(15, 'EYEI', 'Eye Infection', 'Infectious', 'Redness, itching, discharge, swollen eyelids', 'Bacterial or viral conjunctivitis', 1, 'Antibiotic or antiviral eye drops, hygiene', 'Active', NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(16, 'SKIN', 'Skin Infection/Rash', 'Infectious', 'Redness, itching, bumps, blisters, peeling', 'Fungal, bacterial, or viral skin infection', 1, 'Topical or oral antibiotics/antifungals, hygiene', 'Active', NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(17, 'FATG', 'Fatigue/General Malaise', 'Non-Infectious', 'Tiredness, low energy, reduced motivation', 'General feeling of being unwell without specific diagnosis', 0, 'Rest, nutrition, hydration, stress management', 'Active', NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(18, 'MSTR', 'Menstrual Cramps', 'Non-Infectious', 'Lower abdominal pain, back pain, nausea during menstruation', 'Painful menstrual periods common in young women', 0, 'Analgesics, heat therapy, rest, NSAIDs', 'Active', NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(19, 'ANXT', 'Anxiety/Stress', 'Mental Health', 'Worry, restlessness, rapid heartbeat, difficulty concentrating', 'Mental health condition common among students under academic pressure', 0, 'Counseling, stress management, relaxation techniques', 'Active', NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(20, 'BACK', 'Back Pain', 'Non-Infectious', 'Lower or upper back pain, stiffness, muscle tension', 'Musculoskeletal pain from poor posture, heavy lifting, or strain', 0, 'Rest, analgesics, physiotherapy, posture correction', 'Active', NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(21, 'THRP', 'Throat Infection/Pharyngitis', 'Infectious', 'Sore throat, red tonsils, swollen lymph nodes, fever', 'Inflammation of the pharynx from viral or bacterial infection', 1, 'Antibiotics if bacterial, rest, warm fluids', 'Active', NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(22, 'TOOT', 'Toothache', 'Non-Infectious', 'Tooth pain, sensitivity, swelling around tooth', 'Dental pain from cavities, infection, or impaction', 0, 'Analgesics, dental referral, antibiotics if infected', 'Active', NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(23, 'URIN', 'Urinary Tract Infection', 'Infectious', 'Painful urination, frequent urination, lower abdominal pain', 'Bacterial infection of the urinary tract', 0, 'Antibiotics, increased fluid intake, cranberry', 'Active', NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(24, 'ACNE', 'Acne/Skin Breakout', 'Non-Infectious', 'Pimples, blackheads, whiteheads, inflamed skin', 'Common skin condition from hormonal changes and stress', 0, 'Topical treatments, hygiene, dietary changes', 'Active', NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19'),
(25, 'FUNG', 'Fungal Infection', 'Infectious', 'Itching, redness, peeling skin, rash with defined edges', 'Fungal skin infection common in tropical climates', 1, 'Antifungal creams or oral medication, keep area dry', 'Active', NULL, '2026-06-20 08:42:19', '2026-06-20 08:42:19');

-- --------------------------------------------------------

--
-- Table structure for table `sponsorships`
--

CREATE TABLE IF NOT EXISTS `sponsorships` (
  `id` int NOT NULL,
  `sponsorship_code` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `sponsor_name` varchar(255) NOT NULL,
  `sponsor_type` enum('Government','NGO','Private','Self','Other') DEFAULT 'Self',
  `sponsorship_type` enum('Full','Partial','Tuition Only','Other') DEFAULT 'Partial',
  `amount` decimal(12,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `terms_conditions` text,
  `status` enum('Active','Expired','Cancelled') DEFAULT 'Active',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_salaries`
--

CREATE TABLE IF NOT EXISTS `staff_salaries` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `base_salary` decimal(12,2) NOT NULL,
  `allowances` decimal(12,2) DEFAULT '0.00',
  `deductions` decimal(12,2) DEFAULT '0.00',
  `net_salary` decimal(12,2) GENERATED ALWAYS AS (((`base_salary` + `allowances`) - `deductions`)) STORED,
  `effective_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('Active','Inactive','Pending') DEFAULT 'Active',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE IF NOT EXISTS `students` (
  `id` int NOT NULL,
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
  `program` varchar(100) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `current_year` int DEFAULT NULL,
  `year` int DEFAULT NULL,
  `level` varchar(50) DEFAULT NULL,
  `set_name` varchar(50) DEFAULT NULL,
  `current_semester` varchar(20) DEFAULT NULL,
  `intake_date` date DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT 'Other',
  `nationality` varchar(100) DEFAULT NULL,
  `address` text,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `emergency_contact_email` varchar(100) DEFAULT NULL,
  `guardian_name` varchar(200) DEFAULT NULL,
  `guardian_phone` varchar(20) DEFAULT NULL,
  `profile_picture` varchar(500) DEFAULT NULL,
  `passport_photo` varchar(500) DEFAULT NULL,
  `status` enum('Active','Inactive','Graduated','Suspended','Withdrawn','deleted') DEFAULT 'Active',
  `last_login` timestamp NULL DEFAULT NULL,
  `locked_until` timestamp NULL DEFAULT NULL,
  `login_attempts` int DEFAULT '0',
  `password_changed` tinyint(1) DEFAULT '0',
  `is_first_login` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `students`
--
DELIMITER $$
CREATE TRIGGER `students_before_insert` BEFORE INSERT ON `students` FOR EACH ROW BEGIN
    IF NEW.full_name IS NULL OR NEW.full_name = '' THEN
        SET NEW.full_name = TRIM(CONCAT(NEW.first_name, ' ', COALESCE(NEW.other_name, ''), ' ', NEW.surname));
    END IF;

    IF (NEW.phone IS NULL OR NEW.phone = '') AND NEW.mobile_number IS NOT NULL THEN
        SET NEW.phone = NEW.mobile_number;
    END IF;
    IF (NEW.mobile_number IS NULL OR NEW.mobile_number = '') AND NEW.phone IS NOT NULL THEN
        SET NEW.mobile_number = NEW.phone;
    END IF;

    IF (NEW.program IS NULL OR NEW.program = '') AND NEW.course IS NOT NULL THEN
        SET NEW.program = NEW.course;
    END IF;
    IF (NEW.course IS NULL OR NEW.course = '') AND NEW.program IS NOT NULL THEN
        SET NEW.course = NEW.program;
    END IF;

    IF (NEW.current_year IS NULL OR NEW.current_year = 0) AND NEW.year IS NOT NULL THEN
        SET NEW.current_year = NEW.year;
    END IF;
    IF (NEW.year IS NULL OR NEW.year = 0) AND NEW.current_year IS NOT NULL THEN
        SET NEW.year = NEW.current_year;
    END IF;

    IF (NEW.profile_picture IS NULL OR NEW.profile_picture = '') AND NEW.passport_photo IS NOT NULL THEN
        SET NEW.profile_picture = NEW.passport_photo;
    END IF;
    IF (NEW.passport_photo IS NULL OR NEW.passport_photo = '') AND NEW.profile_picture IS NOT NULL THEN
        SET NEW.passport_photo = NEW.profile_picture;
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `students_before_update` BEFORE UPDATE ON `students` FOR EACH ROW BEGIN
    IF NEW.full_name IS NULL OR NEW.full_name = '' THEN
        SET NEW.full_name = TRIM(CONCAT(NEW.first_name, ' ', COALESCE(NEW.other_name, ''), ' ', NEW.surname));
    END IF;

    IF (NEW.phone IS NULL OR NEW.phone = '') AND NEW.mobile_number IS NOT NULL THEN
        SET NEW.phone = NEW.mobile_number;
    END IF;
    IF (NEW.mobile_number IS NULL OR NEW.mobile_number = '') AND NEW.phone IS NOT NULL THEN
        SET NEW.mobile_number = NEW.phone;
    END IF;

    IF (NEW.program IS NULL OR NEW.program = '') AND NEW.course IS NOT NULL THEN
        SET NEW.program = NEW.course;
    END IF;
    IF (NEW.course IS NULL OR NEW.course = '') AND NEW.program IS NOT NULL THEN
        SET NEW.course = NEW.program;
    END IF;

    IF (NEW.current_year IS NULL OR NEW.current_year = 0) AND NEW.year IS NOT NULL THEN
        SET NEW.current_year = NEW.year;
    END IF;
    IF (NEW.year IS NULL OR NEW.year = 0) AND NEW.current_year IS NOT NULL THEN
        SET NEW.year = NEW.current_year;
    END IF;

    IF (NEW.profile_picture IS NULL OR NEW.profile_picture = '') AND NEW.passport_photo IS NOT NULL THEN
        SET NEW.profile_picture = NEW.passport_photo;
    END IF;
    IF (NEW.passport_photo IS NULL OR NEW.passport_photo = '') AND NEW.profile_picture IS NOT NULL THEN
        SET NEW.passport_photo = NEW.profile_picture;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `students_trash`
--

CREATE TABLE IF NOT EXISTS `students_trash` (
  `id` int NOT NULL,
  `original_id` int NOT NULL,
  `student_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `full_name` varchar(300) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `course` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `snapshot_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `deleted_by` int DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `restored_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_academic_records`
--

CREATE TABLE IF NOT EXISTS `student_academic_records` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `grade` varchar(10) DEFAULT NULL,
  `marks` decimal(5,2) DEFAULT NULL,
  `credits` decimal(3,1) DEFAULT NULL,
  `gpa` decimal(3,2) DEFAULT NULL,
  `cgpa` decimal(3,2) DEFAULT NULL,
  `remarks` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_attendance`
--

CREATE TABLE IF NOT EXISTS `student_attendance` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `date` date NOT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `status` enum('Present','Absent','Late','Excused') NOT NULL,
  `remarks` text,
  `recorded_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_course_registrations`
--

CREATE TABLE IF NOT EXISTS `student_course_registrations` (
  `id` int NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `course_id` int NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `registration_date` date DEFAULT (curdate()),
  `status` enum('Registered','Dropped','Completed') DEFAULT 'Registered',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `student_dashboard_view`
-- (See below for the actual view)
--
CREATE TABLE IF NOT EXISTS `student_dashboard_view` (
`id` int
,`student_number` varchar(50)
,`full_name` varchar(302)
,`course` varchar(100)
,`year` bigint
,`set_name` varchar(50)
,`email` varchar(100)
,`profile_picture` varchar(500)
,`current_gpa` decimal(3,2)
,`fee_balance` decimal(32,2)
,`attendance_rate` decimal(31,5)
);

-- --------------------------------------------------------

--
-- Table structure for table `student_discipline`
--

CREATE TABLE IF NOT EXISTS `student_discipline` (
  `id` int NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `incident_date` date NOT NULL,
  `incident_type` varchar(100) NOT NULL,
  `description` text,
  `action_taken` varchar(255) DEFAULT NULL,
  `action_date` date DEFAULT NULL,
  `reported_by` int DEFAULT NULL,
  `status` enum('Open','Resolved','Appealed') DEFAULT 'Open',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_discipline_records`
--

CREATE TABLE IF NOT EXISTS `student_discipline_records` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `case_number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `incident_date` date DEFAULT NULL,
  `incident_type` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `action_taken` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Pending','Resolved','Closed') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `recorded_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_downloads`
--

CREATE TABLE IF NOT EXISTS `student_downloads` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` bigint DEFAULT NULL,
  `download_count` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_fees`
--

CREATE TABLE IF NOT EXISTS `student_fees` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `fee_type` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date DEFAULT NULL,
  `paid_date` date DEFAULT NULL,
  `status` enum('Unpaid','Partially Paid','Paid','Overdue') DEFAULT 'Unpaid',
  `payment_method` varchar(50) DEFAULT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `remarks` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_fee_assignments`
--

CREATE TABLE IF NOT EXISTS `student_fee_assignments` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `fee_structure_id` int NOT NULL,
  `assigned_amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) DEFAULT '0.00',
  `balance` decimal(10,2) GENERATED ALWAYS AS ((`assigned_amount` - `paid_amount`)) STORED,
  `status` enum('Unpaid','Partially Paid','Paid','Waived') DEFAULT 'Unpaid',
  `due_date` date DEFAULT NULL,
  `assigned_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_hostel_allocations`
--

CREATE TABLE IF NOT EXISTS `student_hostel_allocations` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `hostel_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `room_number` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `allocation_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `monthly_fee` decimal(10,2) DEFAULT '0.00',
  `status` enum('Active','Vacated','Transferred') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_invoices`
--

CREATE TABLE IF NOT EXISTS `student_invoices` (
  `id` int NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `fee_assignment_id` int DEFAULT NULL,
  `fee_type` varchar(100) NOT NULL,
  `description` text,
  `total_amount` decimal(12,2) NOT NULL,
  `discount_amount` decimal(12,2) DEFAULT '0.00',
  `net_amount` decimal(12,2) GENERATED ALWAYS AS ((`total_amount` - `discount_amount`)) STORED,
  `amount_paid` decimal(12,2) DEFAULT '0.00',
  `balance` decimal(12,2) GENERATED ALWAYS AS ((`net_amount` - `amount_paid`)) STORED,
  `status` enum('Draft','Pending','Partially Paid','Paid','Overdue','Cancelled','Waived') DEFAULT 'Pending',
  `due_date` date DEFAULT NULL,
  `issue_date` date DEFAULT (curdate()),
  `payment_method` varchar(50) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `student_login_view`
-- (See below for the actual view)
--
CREATE TABLE IF NOT EXISTS `student_login_view` (
`id` int
,`student_number` varchar(50)
,`full_name` varchar(302)
,`email` varchar(100)
,`password` varchar(255)
,`course` varchar(100)
,`status` enum('Active','Inactive','Graduated','Suspended','Withdrawn','deleted')
,`last_login` timestamp
,`login_attempts` int
,`is_first_login` tinyint(1)
);

-- --------------------------------------------------------

--
-- Table structure for table `student_messages`
--

CREATE TABLE IF NOT EXISTS `student_messages` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `department_email` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `replied` tinyint(1) DEFAULT '0',
  `reply_message` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `replied_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_notifications`
--

CREATE TABLE IF NOT EXISTS `student_notifications` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('General','Academic','Fee','Attendance','Exam','Event','Matron','Bursar') DEFAULT 'General',
  `priority` enum('Low','Medium','High','Urgent') DEFAULT 'Medium',
  `is_read` tinyint(1) DEFAULT '0',
  `action_url` varchar(500) DEFAULT NULL,
  `link_type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `link_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_password_resets`
--

CREATE TABLE IF NOT EXISTS `student_password_resets` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `reset_token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL,
  `is_used` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_penalties`
--

CREATE TABLE IF NOT EXISTS `student_penalties` (
  `id` int NOT NULL,
  `penalty_number` varchar(50) NOT NULL,
  `student_id` int NOT NULL,
  `penalty_type` varchar(100) NOT NULL,
  `amount` decimal(10,2) DEFAULT '0.00',
  `reason` text,
  `applied_by` int DEFAULT NULL,
  `applied_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `waived` tinyint(1) DEFAULT '0',
  `waived_by` int DEFAULT NULL,
  `waived_at` timestamp NULL DEFAULT NULL,
  `waiver_reason` text,
  `status` enum('Active','Waived','Paid') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_profiles`
--

CREATE TABLE IF NOT EXISTS `student_profiles` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `bio` text,
  `interests` text,
  `skills` text,
  `achievements` text,
  `education_background` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_requests`
--

CREATE TABLE IF NOT EXISTS `student_requests` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `request_type` enum('Leave of Absence','Deferral','Transfer','Withdrawal','Other') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `supporting_doc` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `reviewed_by` int DEFAULT NULL,
  `review_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_sick_leave`
--

CREATE TABLE IF NOT EXISTS `student_sick_leave` (
  `id` int NOT NULL,
  `leave_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `student_id` int NOT NULL,
  `student_name` varchar(300) COLLATE utf8mb4_general_ci NOT NULL,
  `student_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `program` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `year_of_study` int DEFAULT NULL,
  `sickness_id` int DEFAULT NULL,
  `sickness_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `diagnosis` text COLLATE utf8mb4_general_ci,
  `leave_from` date NOT NULL,
  `leave_to` date NOT NULL,
  `total_days` int GENERATED ALWAYS AS (((to_days(`leave_to`) - to_days(`leave_from`)) + 1)) STORED,
  `leave_type` enum('Medical','Sick','Maternity','Injury','Quarantine','Other') COLLATE utf8mb4_general_ci DEFAULT 'Sick',
  `recommended_by` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `recommender_title` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `approved_by` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Expired','Extended') COLLATE utf8mb4_general_ci DEFAULT 'Pending',
  `extended_to` date DEFAULT NULL,
  `extension_reason` text COLLATE utf8mb4_general_ci,
  `doctor_notes` text COLLATE utf8mb4_general_ci,
  `bed_rest_required` tinyint(1) DEFAULT '1',
  `parent_guardian_notified` tinyint(1) DEFAULT '0',
  `matron_notified` tinyint(1) DEFAULT '0',
  `class_teacher_notified` tinyint(1) DEFAULT '0',
  `documents_submitted` tinyint(1) DEFAULT '0',
  `documents_path` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `return_date_actual` date DEFAULT NULL,
  `return_notes` text COLLATE utf8mb4_general_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_timetables`
--

CREATE TABLE IF NOT EXISTS `student_timetables` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `time_slot` varchar(50) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `lecturer` varchar(100) DEFAULT NULL,
  `classroom` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscription_deductions`
--

CREATE TABLE IF NOT EXISTS `subscription_deductions` (
  `id` int NOT NULL,
  `subscription_id` int NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `installment_number` int NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `due_date` date NOT NULL,
  `processed_date` datetime DEFAULT NULL,
  `status` enum('pending','success','failed','skipped') NOT NULL DEFAULT 'pending',
  `payment_reference` varchar(50) DEFAULT NULL,
  `payment_id` int DEFAULT NULL COMMENT 'FK to payments.id if successful',
  `failure_reason` text,
  `attempt_count` int DEFAULT '0',
  `last_attempt_date` datetime DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timetable`
--

CREATE TABLE IF NOT EXISTS `timetable` (
  `id` int NOT NULL,
  `program` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `year_of_study` int DEFAULT '1',
  `semester` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `time_slot` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_code` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lecturer` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `room` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `academic_year` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_document_grouping`
-- (See below for the actual view)
--
CREATE TABLE IF NOT EXISTS `view_document_grouping` (
`document_type` enum('Transcript','Result Slip','Certificate','Receipt','Payslip','Report','Invoice','Timetable','Exam Schedule','Leave Form','Performance Review')
,`student_id` int
,`student_name` varchar(300)
,`program` varchar(100)
,`document_count` bigint
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_program_grouping`
-- (See below for the actual view)
--
CREATE TABLE IF NOT EXISTS `view_program_grouping` (
`department` varchar(20)
,`course_code` varchar(20)
,`course_name` varchar(255)
,`credit_hours` int
,`course_level` int
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_student_grouping`
-- (See below for the actual view)
--
CREATE TABLE IF NOT EXISTS `view_student_grouping` (
`program` varchar(100)
,`year_of_study` int
,`status` enum('Active','Inactive','Graduated','Suspended','Withdrawn','deleted')
,`set_name` varchar(50)
,`semester` varchar(20)
,`student_count` bigint
);

-- --------------------------------------------------------

--
-- Structure for view `student_dashboard_view`
--
DROP TABLE IF EXISTS `student_dashboard_view`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `student_dashboard_view`  AS SELECT `s`.`id` AS `id`, `s`.`student_number` AS `student_number`, coalesce(`s`.`full_name`,trim(concat(`s`.`first_name`,' ',coalesce(`s`.`other_name`,''),' ',`s`.`surname`))) AS `full_name`, coalesce(`s`.`course`,`s`.`program`) AS `course`, coalesce(`s`.`year`,`s`.`current_year`) AS `year`, `s`.`set_name` AS `set_name`, `s`.`email` AS `email`, coalesce(`s`.`profile_picture`,`s`.`passport_photo`) AS `profile_picture`, coalesce(`sa`.`gpa`,0) AS `current_gpa`, coalesce(`sf`.`balance`,0) AS `fee_balance`, coalesce(`sa2`.`attendance_rate`,0) AS `attendance_rate` FROM (((`students` `s` left join (select `student_academic_records`.`student_id` AS `student_id`,`student_academic_records`.`gpa` AS `gpa` from `student_academic_records` where (`student_academic_records`.`semester` = (select max(`student_academic_records`.`semester`) from `student_academic_records`)) group by `student_academic_records`.`student_id`) `sa` on((`s`.`id` = `sa`.`student_id`))) left join (select `student_attendance`.`student_id` AS `student_id`,((sum((case when (`student_attendance`.`status` = 'Present') then 1 else 0 end)) * 100.0) / count(0)) AS `attendance_rate` from `student_attendance` group by `student_attendance`.`student_id`) `sa2` on((`s`.`id` = `sa2`.`student_id`))) left join (select `student_fees`.`student_id` AS `student_id`,sum(`student_fees`.`amount`) AS `balance` from `student_fees` where (`student_fees`.`status` in ('Unpaid','Partially Paid','Overdue')) group by `student_fees`.`student_id`) `sf` on((`s`.`id` = `sf`.`student_id`))) WHERE (`s`.`status` = 'Active') ;

-- --------------------------------------------------------

--
-- Structure for view `student_login_view`
--
DROP TABLE IF EXISTS `student_login_view`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `student_login_view`  AS SELECT `students`.`id` AS `id`, `students`.`student_number` AS `student_number`, coalesce(`students`.`full_name`,trim(concat(`students`.`first_name`,' ',coalesce(`students`.`other_name`,''),' ',`students`.`surname`))) AS `full_name`, `students`.`email` AS `email`, `students`.`password` AS `password`, coalesce(`students`.`course`,`students`.`program`) AS `course`, `students`.`status` AS `status`, `students`.`last_login` AS `last_login`, `students`.`login_attempts` AS `login_attempts`, `students`.`is_first_login` AS `is_first_login` FROM `students` WHERE (`students`.`status` = 'Active') ;

-- --------------------------------------------------------

--
-- Structure for view `view_document_grouping`
--
DROP TABLE IF EXISTS `view_document_grouping`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `view_document_grouping`  AS SELECT `gd`.`document_type` AS `document_type`, `gd`.`student_id` AS `student_id`, `s`.`full_name` AS `student_name`, `s`.`course` AS `program`, count(0) AS `document_count` FROM (`igangaschoolofl_staffs_db`.`generated_documents` `gd` left join `students` `s` on((`gd`.`student_id` = `s`.`id`))) WHERE (`gd`.`document_type` is not null) GROUP BY `gd`.`document_type`, `gd`.`student_id`, `s`.`full_name`, `s`.`course` ORDER BY `gd`.`document_type` ASC, `s`.`full_name` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `view_program_grouping`
--
DROP TABLE IF EXISTS `view_program_grouping`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `view_program_grouping`  AS SELECT `igangaschoolofl_staffs_db`.`academic_course_catalog`.`program_code` AS `department`, `igangaschoolofl_staffs_db`.`academic_course_catalog`.`course_code` AS `course_code`, `igangaschoolofl_staffs_db`.`academic_course_catalog`.`course_title` AS `course_name`, `igangaschoolofl_staffs_db`.`academic_course_catalog`.`credits` AS `credit_hours`, `igangaschoolofl_staffs_db`.`academic_course_catalog`.`year_of_study` AS `course_level` FROM `igangaschoolofl_staffs_db`.`academic_course_catalog` WHERE ((`igangaschoolofl_staffs_db`.`academic_course_catalog`.`course_title` is not null) AND (`igangaschoolofl_staffs_db`.`academic_course_catalog`.`course_title` <> '')) ORDER BY `igangaschoolofl_staffs_db`.`academic_course_catalog`.`program_code` ASC, `igangaschoolofl_staffs_db`.`academic_course_catalog`.`course_title` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `view_student_grouping`
--
DROP TABLE IF EXISTS `view_student_grouping`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY INVOKER VIEW `view_student_grouping`  AS SELECT `students`.`course` AS `program`, `students`.`current_year` AS `year_of_study`, `students`.`status` AS `status`, `students`.`set_name` AS `set_name`, `students`.`current_semester` AS `semester`, count(0) AS `student_count` FROM `students` WHERE ((`students`.`full_name` is not null) AND (`students`.`full_name` <> '') AND (length(`students`.`full_name`) > 3) AND (not((`students`.`full_name` like '%MINISTRY%'))) AND (not((`students`.`full_name` like '%ACCOUNTABILITY%'))) AND (not((`students`.`full_name` like '%VERIFICATION%'))) AND (not((`students`.`full_name` like '%HEALTH EDUCATION%'))) AND (not((`students`.`full_name` like '%……………………………………………………%')))) GROUP BY `students`.`course`, `students`.`current_year`, `students`.`status`, `students`.`set_name`, `students`.`current_semester` ORDER BY `students`.`course` ASC, `students`.`current_year` ASC, `students`.`status` ASC ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_registrar_activity_log`
--
ALTER TABLE `academic_registrar_activity_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_target` (`target_audience`),
  ADD KEY `idx_active` (`is_active`);

--
-- Indexes for table `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `asset_tag` (`asset_tag`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_asset_tag` (`asset_tag`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_assets_status` (`status`),
  ADD KEY `idx_assets_category` (`category_id`);

--
-- Indexes for table `asset_categories`
--
ALTER TABLE `asset_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `bank_transactions`
--
ALTER TABLE `bank_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_date` (`transaction_date`),
  ADD KEY `idx_reconciled` (`reconciled`),
  ADD KEY `idx_account` (`bank_account`);

--
-- Indexes for table `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fiscal_year` (`fiscal_year`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_budgets_fiscal_year` (`fiscal_year`),
  ADD KEY `idx_budgets_status` (`status`);

--
-- Indexes for table `budget_records`
--
ALTER TABLE `budget_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_budget_id` (`budget_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_br_budget` (`budget_id`);

--
-- Indexes for table `bursar_general_ledger`
--
ALTER TABLE `bursar_general_ledger`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `entry_number` (`entry_number`),
  ADD KEY `idx_entry_date` (`entry_date`),
  ADD KEY `idx_account` (`account_id`),
  ADD KEY `idx_ref` (`reference_type`,`reference_id`);

--
-- Indexes for table `bursar_tax_filings`
--
ALTER TABLE `bursar_tax_filings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_period` (`tax_period_id`);

--
-- Indexes for table `bursar_tax_periods`
--
ALTER TABLE `bursar_tax_periods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_period` (`period_name`,`fiscal_year`);

--
-- Indexes for table `bursar_users`
--
ALTER TABLE `bursar_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `cash_book`
--
ALTER TABLE `cash_book`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `entry_number` (`entry_number`),
  ADD KEY `related_student_id` (`related_student_id`),
  ADD KEY `recorded_by` (`recorded_by`),
  ADD KEY `idx_entry_number` (`entry_number`),
  ADD KEY `idx_entry_type` (`entry_type`),
  ADD KEY `idx_transaction_date` (`transaction_date`),
  ADD KEY `idx_cb_date` (`transaction_date`);

--
-- Indexes for table `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `account_code` (`account_code`),
  ADD KEY `parent_account_id` (`parent_account_id`),
  ADD KEY `idx_account_code` (`account_code`),
  ADD KEY `idx_account_type` (`account_type`),
  ADD KEY `idx_coa_type` (`account_type`);

--
-- Indexes for table `clinical_placements`
--
ALTER TABLE `clinical_placements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `clinical_placements_students`
--
ALTER TABLE `clinical_placements_students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`);

--
-- Indexes for table `cost_centers`
--
ALTER TABLE `cost_centers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cost_center_code` (`cost_center_code`),
  ADD KEY `idx_cost_center_code` (`cost_center_code`);

--
-- Indexes for table `daily_sick_records`
--
ALTER TABLE `daily_sick_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `record_number` (`record_number`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `sickness_id` (`sickness_id`),
  ADD KEY `visit_date` (`visit_date`),
  ADD KEY `status` (`status`),
  ADD KEY `severity` (`severity`),
  ADD KEY `student_name` (`student_name`),
  ADD KEY `program` (`program`),
  ADD KEY `dsr_student_date` (`student_id`,`visit_date`);

--
-- Indexes for table `department_requests`
--
ALTER TABLE `department_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_req_num` (`request_number`),
  ADD KEY `idx_from_dept` (`from_department`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `expenditure_records`
--
ALTER TABLE `expenditure_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `expenditure_number` (`expenditure_number`),
  ADD KEY `budget_record_id` (`budget_record_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_expenditure_number` (`expenditure_number`),
  ADD KEY `idx_expenditure_date` (`expenditure_date`),
  ADD KEY `idx_recorded_by` (`recorded_by`),
  ADD KEY `idx_er_date` (`expenditure_date`),
  ADD KEY `idx_er_budget` (`budget_record_id`);

--
-- Indexes for table `fee_adjustments`
--
ALTER TABLE `fee_adjustments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `adjustment_number` (`adjustment_number`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_adjustment_number` (`adjustment_number`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_fa_student` (`student_id`);

--
-- Indexes for table `fee_reminders`
--
ALTER TABLE `fee_reminders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reminder_number` (`reminder_number`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `sent_by` (`sent_by`),
  ADD KEY `idx_reminder_number` (`reminder_number`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_reminder_date` (`reminder_date`),
  ADD KEY `idx_fr_student` (`student_id`);

--
-- Indexes for table `fee_structures`
--
ALTER TABLE `fee_structures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `idx_fee_type` (`fee_type`),
  ADD KEY `idx_academic_year` (`academic_year`),
  ADD KEY `idx_fee_structures_program_id` (`program_id`),
  ADD KEY `idx_fee_structures_academic_year` (`academic_year`),
  ADD KEY `idx_fs_academic_year` (`academic_year`),
  ADD KEY `idx_fs_program` (`program_id`);

--
-- Indexes for table `financial_clearance`
--
ALTER TABLE `financial_clearance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_clearance` (`student_id`,`academic_year`,`semester`),
  ADD KEY `idx_fc_student` (`student_id`),
  ADD KEY `idx_fc_status` (`clearance_status`),
  ADD KEY `idx_fc_year` (`academic_year`);

--
-- Indexes for table `financial_reports`
--
ALTER TABLE `financial_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `generated_by` (`generated_by`),
  ADD KEY `idx_report_type` (`report_type`),
  ADD KEY `idx_generated_at` (`generated_at`);

--
-- Indexes for table `general_ledger`
--
ALTER TABLE `general_ledger`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `entry_number` (`entry_number`),
  ADD KEY `cost_center_id` (`cost_center_id`),
  ADD KEY `posted_by` (`posted_by`),
  ADD KEY `idx_entry_number` (`entry_number`),
  ADD KEY `idx_account_id` (`account_id`),
  ADD KEY `idx_transaction_date` (`transaction_date`),
  ADD KEY `idx_gl_date` (`transaction_date`),
  ADD KEY `idx_gl_account` (`account_id`),
  ADD KEY `idx_gl_type` (`transaction_type`);

--
-- Indexes for table `graduation_candidates`
--
ALTER TABLE `graduation_candidates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `hostel_allocations`
--
ALTER TABLE `hostel_allocations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_hostel_allocations_room_id` (`room_id`),
  ADD KEY `idx_ha_student` (`student_id`),
  ADD KEY `idx_ha_status` (`status`);

--
-- Indexes for table `hostel_rooms`
--
ALTER TABLE `hostel_rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_number` (`room_number`),
  ADD KEY `idx_hr_status` (`status`);

--
-- Indexes for table `income_tax_rates`
--
ALTER TABLE `income_tax_rates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bracket` (`fiscal_year`,`min_income`);

--
-- Indexes for table `lab_attendance`
--
ALTER TABLE `lab_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_session_student` (`session_id`,`student_id`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_status` (`attendance_status`);

--
-- Indexes for table `lab_consumables`
--
ALTER TABLE `lab_consumables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_stock` (`quantity`);

--
-- Indexes for table `lab_equipment`
--
ALTER TABLE `lab_equipment`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `equipment_code` (`equipment_code`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `lab_equipment_checkouts`
--
ALTER TABLE `lab_equipment_checkouts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipment_id` (`equipment_id`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_expected_return` (`expected_return_date`);

--
-- Indexes for table `lab_incidents`
--
ALTER TABLE `lab_incidents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_date` (`incident_date`),
  ADD KEY `idx_type` (`incident_type`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `lab_practical_sessions`
--
ALTER TABLE `lab_practical_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_code` (`session_code`),
  ADD KEY `idx_date` (`session_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `lab_skills_demonstrations`
--
ALTER TABLE `lab_skills_demonstrations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_skill` (`skill_name`),
  ADD KEY `idx_competency` (`competency`);

--
-- Indexes for table `late_payment_settings`
--
ALTER TABLE `late_payment_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `library_books`
--
ALTER TABLE `library_books`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `library_borrowing`
--
ALTER TABLE `library_borrowing`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_library_borrowing_book_id` (`book_id`);

--
-- Indexes for table `library_fines`
--
ALTER TABLE `library_fines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`);

--
-- Indexes for table `medicine_stock`
--
ALTER TABLE `medicine_stock`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `medicine_code` (`medicine_code`),
  ADD KEY `medicine_name` (`medicine_name`),
  ADD KEY `category` (`category`),
  ADD KEY `expiry_date` (`expiry_date`),
  ADD KEY `status` (`status`),
  ADD KEY `supplier` (`supplier`),
  ADD KEY `ms_expiry_status` (`expiry_date`,`status`);

--
-- Indexes for table `medicine_stock_transactions`
--
ALTER TABLE `medicine_stock_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_number` (`transaction_number`),
  ADD KEY `medicine_id` (`medicine_id`),
  ADD KEY `transaction_type` (`transaction_type`),
  ADD KEY `transaction_date` (`transaction_date`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `mst_medicine_date` (`medicine_id`,`transaction_date`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_recipient_type` (`recipient_type`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payment_reference` (`payment_reference`),
  ADD KEY `invoice_id` (`invoice_id`),
  ADD KEY `received_by` (`received_by`),
  ADD KEY `idx_payment_reference` (`payment_reference`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_payment_date` (`payment_date`),
  ADD KEY `idx_payments_student_id` (`student_id`),
  ADD KEY `idx_payments_invoice_id` (`invoice_id`),
  ADD KEY `idx_payments_status` (`status`),
  ADD KEY `idx_payments_payment_date` (`payment_date`),
  ADD KEY `idx_payments_received_by` (`received_by`),
  ADD KEY `idx_payments_student` (`student_id`),
  ADD KEY `idx_payments_date` (`payment_date`),
  ADD KEY `idx_payments_ref` (`payment_reference`);

--
-- Indexes for table `payment_receipts`
--
ALTER TABLE `payment_receipts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_number` (`receipt_number`),
  ADD KEY `issued_by` (`issued_by`),
  ADD KEY `voided_by` (`voided_by`),
  ADD KEY `idx_receipt_number` (`receipt_number`),
  ADD KEY `idx_payment_id` (`payment_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_payment_receipts_payment_id` (`payment_id`),
  ADD KEY `idx_payment_receipts_student_id` (`student_id`);

--
-- Indexes for table `payment_subscriptions`
--
ALTER TABLE `payment_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_next_due` (`next_due_date`);

--
-- Indexes for table `payroll_records`
--
ALTER TABLE `payroll_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_payroll` (`staff_id`,`month`,`year`),
  ADD KEY `idx_period` (`month`,`year`);

--
-- Indexes for table `payslips`
--
ALTER TABLE `payslips`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff` (`staff_id`),
  ADD KEY `idx_period` (`month`,`year`),
  ADD KEY `idx_payroll` (`payroll_record_id`);

--
-- Indexes for table `penalty_configurations`
--
ALTER TABLE `penalty_configurations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `penalty_name` (`penalty_name`),
  ADD KEY `idx_penalty_name` (`penalty_name`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `program_code` (`program_code`),
  ADD KEY `idx_program_code` (`program_code`);

--
-- Indexes for table `proof_of_payments`
--
ALTER TABLE `proof_of_payments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `proof_number` (`proof_number`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `verified_by` (`verified_by`),
  ADD KEY `idx_proof_number` (`proof_number`),
  ADD KEY `idx_payment_id` (`payment_id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_pop_student` (`student_id`);

--
-- Indexes for table `registrar_certificates`
--
ALTER TABLE `registrar_certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `certificate_number` (`certificate_number`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `registrar_settings`
--
ALTER TABLE `registrar_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `registrar_transcript_requests`
--
ALTER TABLE `registrar_transcript_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `request_number` (`request_number`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `salary_components`
--
ALTER TABLE `salary_components`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `component_name` (`component_name`);

--
-- Indexes for table `sickness_directory`
--
ALTER TABLE `sickness_directory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sickness_code` (`sickness_code`),
  ADD KEY `sickness_name` (`sickness_name`),
  ADD KEY `category` (`category`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `sponsorships`
--
ALTER TABLE `sponsorships`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sponsorship_code` (`sponsorship_code`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_sponsorship_code` (`sponsorship_code`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_sponsorships_student_id` (`student_id`);

--
-- Indexes for table `staff_salaries`
--
ALTER TABLE `staff_salaries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_effective_date` (`effective_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_number` (`student_number`),
  ADD UNIQUE KEY `registration_number` (`registration_number`),
  ADD UNIQUE KEY `national_student_id_number` (`national_student_id_number`),
  ADD UNIQUE KEY `index_number` (`index_number`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_student_number` (`student_number`),
  ADD KEY `idx_registration_number` (`registration_number`),
  ADD KEY `idx_national_id` (`national_student_id_number`),
  ADD KEY `idx_index_number` (`index_number`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_program` (`program`),
  ADD KEY `idx_course` (`course`),
  ADD KEY `idx_current_year` (`current_year`),
  ADD KEY `idx_year` (`year`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_full_name` (`full_name`(100)),
  ADD KEY `idx_set_name` (`set_name`),
  ADD KEY `idx_intake_date` (`intake_date`),
  ADD KEY `idx_students_student_number` (`student_number`),
  ADD KEY `idx_students_email` (`email`),
  ADD KEY `idx_students_program` (`program`),
  ADD KEY `idx_students_status` (`status`),
  ADD KEY `idx_students_name` (`surname`,`first_name`);

--
-- Indexes for table `students_trash`
--
ALTER TABLE `students_trash`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_original_id` (`original_id`);

--
-- Indexes for table `student_academic_records`
--
ALTER TABLE `student_academic_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_semester` (`semester`),
  ADD KEY `idx_academic_year` (`academic_year`),
  ADD KEY `idx_subject` (`subject`),
  ADD KEY `idx_academic_records_student_id` (`student_id`);

--
-- Indexes for table `student_attendance`
--
ALTER TABLE `student_attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_subject` (`subject`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_student_attendance_student_id` (`student_id`);

--
-- Indexes for table `student_course_registrations`
--
ALTER TABLE `student_course_registrations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cr_student` (`student_id`),
  ADD KEY `idx_cr_status` (`status`);

--
-- Indexes for table `student_discipline`
--
ALTER TABLE `student_discipline`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_discipline_student_id` (`student_id`);

--
-- Indexes for table `student_discipline_records`
--
ALTER TABLE `student_discipline_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_case` (`case_number`),
  ADD KEY `idx_student_id` (`student_id`);

--
-- Indexes for table `student_downloads`
--
ALTER TABLE `student_downloads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_file_type` (`file_type`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `student_fees`
--
ALTER TABLE `student_fees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_fee_type` (`fee_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_due_date` (`due_date`),
  ADD KEY `idx_student_fees_student_id` (`student_id`),
  ADD KEY `idx_sf_student_id` (`student_id`),
  ADD KEY `idx_sf_status` (`status`);

--
-- Indexes for table `student_fee_assignments`
--
ALTER TABLE `student_fee_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fee_structure_id` (`fee_structure_id`),
  ADD KEY `assigned_by` (`assigned_by`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_due_date` (`due_date`),
  ADD KEY `idx_fee_assignments_student_id` (`student_id`),
  ADD KEY `idx_fee_assignments_fee_structure_id` (`fee_structure_id`);

--
-- Indexes for table `student_hostel_allocations`
--
ALTER TABLE `student_hostel_allocations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`);

--
-- Indexes for table `student_invoices`
--
ALTER TABLE `student_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`),
  ADD KEY `fee_assignment_id` (`fee_assignment_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_invoice_number` (`invoice_number`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_due_date` (`due_date`),
  ADD KEY `idx_student_invoices_student_id` (`student_id`),
  ADD KEY `idx_student_invoices_status` (`status`),
  ADD KEY `idx_student_invoices_due_date` (`due_date`),
  ADD KEY `idx_si_student_id` (`student_id`),
  ADD KEY `idx_si_status` (`status`),
  ADD KEY `idx_si_created` (`created_at`),
  ADD KEY `idx_si_student_status` (`student_id`,`status`);

--
-- Indexes for table `student_messages`
--
ALTER TABLE `student_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_department_email` (`department_email`),
  ADD KEY `idx_is_read` (`is_read`);

--
-- Indexes for table `student_notifications`
--
ALTER TABLE `student_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_is_read` (`is_read`);

--
-- Indexes for table `student_password_resets`
--
ALTER TABLE `student_password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `idx_reset_token` (`reset_token`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `student_penalties`
--
ALTER TABLE `student_penalties`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `penalty_number` (`penalty_number`),
  ADD KEY `applied_by` (`applied_by`),
  ADD KEY `waived_by` (`waived_by`),
  ADD KEY `idx_penalty_number` (`penalty_number`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`);

--
-- Indexes for table `student_requests`
--
ALTER TABLE `student_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `student_sick_leave`
--
ALTER TABLE `student_sick_leave`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `leave_number` (`leave_number`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `sickness_id` (`sickness_id`),
  ADD KEY `leave_from` (`leave_from`),
  ADD KEY `leave_to` (`leave_to`),
  ADD KEY `status` (`status`),
  ADD KEY `student_name` (`student_name`),
  ADD KEY `program` (`program`),
  ADD KEY `ssl_student_status` (`student_id`,`status`);

--
-- Indexes for table `student_timetables`
--
ALTER TABLE `student_timetables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_day_of_week` (`day_of_week`),
  ADD KEY `idx_subject` (`subject`);

--
-- Indexes for table `subscription_deductions`
--
ALTER TABLE `subscription_deductions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_subscription` (`subscription_id`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `timetable`
--
ALTER TABLE `timetable`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_program` (`program`),
  ADD KEY `idx_day` (`day_of_week`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_registrar_activity_log`
--
ALTER TABLE `academic_registrar_activity_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `asset_categories`
--
ALTER TABLE `asset_categories`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bank_transactions`
--
ALTER TABLE `bank_transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budgets`
--
ALTER TABLE `budgets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budget_records`
--
ALTER TABLE `budget_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_general_ledger`
--
ALTER TABLE `bursar_general_ledger`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_tax_filings`
--
ALTER TABLE `bursar_tax_filings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_tax_periods`
--
ALTER TABLE `bursar_tax_periods`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_users`
--
ALTER TABLE `bursar_users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cash_book`
--
ALTER TABLE `cash_book`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `clinical_placements`
--
ALTER TABLE `clinical_placements`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clinical_placements_students`
--
ALTER TABLE `clinical_placements_students`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cost_centers`
--
ALTER TABLE `cost_centers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `daily_sick_records`
--
ALTER TABLE `daily_sick_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `department_requests`
--
ALTER TABLE `department_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenditure_records`
--
ALTER TABLE `expenditure_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_adjustments`
--
ALTER TABLE `fee_adjustments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_reminders`
--
ALTER TABLE `fee_reminders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_structures`
--
ALTER TABLE `fee_structures`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `financial_clearance`
--
ALTER TABLE `financial_clearance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `financial_reports`
--
ALTER TABLE `financial_reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `general_ledger`
--
ALTER TABLE `general_ledger`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `graduation_candidates`
--
ALTER TABLE `graduation_candidates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hostel_allocations`
--
ALTER TABLE `hostel_allocations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hostel_rooms`
--
ALTER TABLE `hostel_rooms`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `income_tax_rates`
--
ALTER TABLE `income_tax_rates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_attendance`
--
ALTER TABLE `lab_attendance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_consumables`
--
ALTER TABLE `lab_consumables`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_equipment`
--
ALTER TABLE `lab_equipment`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_equipment_checkouts`
--
ALTER TABLE `lab_equipment_checkouts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_incidents`
--
ALTER TABLE `lab_incidents`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_practical_sessions`
--
ALTER TABLE `lab_practical_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_skills_demonstrations`
--
ALTER TABLE `lab_skills_demonstrations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `late_payment_settings`
--
ALTER TABLE `late_payment_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `library_books`
--
ALTER TABLE `library_books`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_borrowing`
--
ALTER TABLE `library_borrowing`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_fines`
--
ALTER TABLE `library_fines`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicine_stock`
--
ALTER TABLE `medicine_stock`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `medicine_stock_transactions`
--
ALTER TABLE `medicine_stock_transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_receipts`
--
ALTER TABLE `payment_receipts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_subscriptions`
--
ALTER TABLE `payment_subscriptions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_records`
--
ALTER TABLE `payroll_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payslips`
--
ALTER TABLE `payslips`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `penalty_configurations`
--
ALTER TABLE `penalty_configurations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `proof_of_payments`
--
ALTER TABLE `proof_of_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registrar_certificates`
--
ALTER TABLE `registrar_certificates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registrar_settings`
--
ALTER TABLE `registrar_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `registrar_transcript_requests`
--
ALTER TABLE `registrar_transcript_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `salary_components`
--
ALTER TABLE `salary_components`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sickness_directory`
--
ALTER TABLE `sickness_directory`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `sponsorships`
--
ALTER TABLE `sponsorships`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_salaries`
--
ALTER TABLE `staff_salaries`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students_trash`
--
ALTER TABLE `students_trash`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_academic_records`
--
ALTER TABLE `student_academic_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_attendance`
--
ALTER TABLE `student_attendance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_course_registrations`
--
ALTER TABLE `student_course_registrations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_discipline`
--
ALTER TABLE `student_discipline`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_discipline_records`
--
ALTER TABLE `student_discipline_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_downloads`
--
ALTER TABLE `student_downloads`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_fees`
--
ALTER TABLE `student_fees`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_fee_assignments`
--
ALTER TABLE `student_fee_assignments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_hostel_allocations`
--
ALTER TABLE `student_hostel_allocations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_invoices`
--
ALTER TABLE `student_invoices`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_messages`
--
ALTER TABLE `student_messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_notifications`
--
ALTER TABLE `student_notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_password_resets`
--
ALTER TABLE `student_password_resets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_penalties`
--
ALTER TABLE `student_penalties`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_profiles`
--
ALTER TABLE `student_profiles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_requests`
--
ALTER TABLE `student_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_sick_leave`
--
ALTER TABLE `student_sick_leave`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_timetables`
--
ALTER TABLE `student_timetables`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscription_deductions`
--
ALTER TABLE `subscription_deductions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timetable`
--
ALTER TABLE `timetable`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assets`
--
ALTER TABLE `assets`
  ADD CONSTRAINT `assets_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `asset_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `assets_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `igangaschoolofl_staffs_db`.`staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `assets_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `igangaschoolofl_staffs_db`.`staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `budget_records`
--
ALTER TABLE `budget_records`
  ADD CONSTRAINT `budget_records_ibfk_1` FOREIGN KEY (`budget_id`) REFERENCES `budgets` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cash_book`
--
ALTER TABLE `cash_book`
  ADD CONSTRAINT `cash_book_ibfk_1` FOREIGN KEY (`related_student_id`) REFERENCES `students` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cash_book_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `igangaschoolofl_staffs_db`.`staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  ADD CONSTRAINT `chart_of_accounts_ibfk_1` FOREIGN KEY (`parent_account_id`) REFERENCES `chart_of_accounts` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `daily_sick_records`
--
ALTER TABLE `daily_sick_records`
  ADD CONSTRAINT `daily_sick_records_ibfk_1` FOREIGN KEY (`sickness_id`) REFERENCES `sickness_directory` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `expenditure_records`
--
ALTER TABLE `expenditure_records`
  ADD CONSTRAINT `expenditure_records_ibfk_1` FOREIGN KEY (`budget_record_id`) REFERENCES `budget_records` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `expenditure_records_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `igangaschoolofl_staffs_db`.`staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `expenditure_records_ibfk_3` FOREIGN KEY (`recorded_by`) REFERENCES `igangaschoolofl_staffs_db`.`staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `fee_adjustments`
--
ALTER TABLE `fee_adjustments`
  ADD CONSTRAINT `fee_adjustments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fee_adjustments_ibfk_2` FOREIGN KEY (`invoice_id`) REFERENCES `student_invoices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fee_adjustments_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `igangaschoolofl_staffs_db`.`staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fee_adjustments_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `igangaschoolofl_staffs_db`.`staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `fee_reminders`
--
ALTER TABLE `fee_reminders`
  ADD CONSTRAINT `fee_reminders_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fee_reminders_ibfk_2` FOREIGN KEY (`invoice_id`) REFERENCES `student_invoices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fee_reminders_ibfk_3` FOREIGN KEY (`sent_by`) REFERENCES `igangaschoolofl_staffs_db`.`staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `fee_structures`
--
ALTER TABLE `fee_structures`
  ADD CONSTRAINT `fee_structures_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `financial_reports`
--
ALTER TABLE `financial_reports`
  ADD CONSTRAINT `financial_reports_ibfk_1` FOREIGN KEY (`generated_by`) REFERENCES `igangaschoolofl_staffs_db`.`staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `general_ledger`
--
ALTER TABLE `general_ledger`
  ADD CONSTRAINT `general_ledger_ibfk_1` FOREIGN KEY (`account_id`) REFERENCES `chart_of_accounts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `general_ledger_ibfk_2` FOREIGN KEY (`cost_center_id`) REFERENCES `cost_centers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `general_ledger_ibfk_3` FOREIGN KEY (`posted_by`) REFERENCES `igangaschoolofl_staffs_db`.`staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `lab_attendance`
--
ALTER TABLE `lab_attendance`
  ADD CONSTRAINT `lab_attendance_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `lab_practical_sessions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lab_equipment_checkouts`
--
ALTER TABLE `lab_equipment_checkouts`
  ADD CONSTRAINT `lab_equipment_checkouts_ibfk_1` FOREIGN KEY (`equipment_id`) REFERENCES `lab_equipment` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `medicine_stock_transactions`
--
ALTER TABLE `medicine_stock_transactions`
  ADD CONSTRAINT `medicine_stock_transactions_ibfk_1` FOREIGN KEY (`medicine_id`) REFERENCES `medicine_stock` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`invoice_id`) REFERENCES `student_invoices` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payments_ibfk_3` FOREIGN KEY (`received_by`) REFERENCES `igangaschoolofl_staffs_db`.`staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `payment_receipts`
--
ALTER TABLE `payment_receipts`
  ADD CONSTRAINT `payment_receipts_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_receipts_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `payment_receipts_ibfk_3` FOREIGN KEY (`issued_by`) REFERENCES `igangaschoolofl_staffs_db`.`staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `payment_receipts_ibfk_4` FOREIGN KEY (`voided_by`) REFERENCES `igangaschoolofl_staffs_db`.`staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `proof_of_payments`
--
ALTER TABLE `proof_of_payments`
  ADD CONSTRAINT `proof_of_payments_ibfk_1` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `proof_of_payments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `proof_of_payments_ibfk_3` FOREIGN KEY (`uploaded_by`) REFERENCES `igangaschoolofl_staffs_db`.`staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `proof_of_payments_ibfk_4` FOREIGN KEY (`verified_by`) REFERENCES `igangaschoolofl_staffs_db`.`staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sponsorships`
--
ALTER TABLE `sponsorships`
  ADD CONSTRAINT `sponsorships_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sponsorships_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `igangaschoolofl_staffs_db`.`staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `staff_salaries`
--
ALTER TABLE `staff_salaries`
  ADD CONSTRAINT `staff_salaries_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `igangaschoolofl_staffs_db`.`staff` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `staff_salaries_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `igangaschoolofl_staffs_db`.`staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_academic_records`
--
ALTER TABLE `student_academic_records`
  ADD CONSTRAINT `student_academic_records_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_attendance`
--
ALTER TABLE `student_attendance`
  ADD CONSTRAINT `student_attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_downloads`
--
ALTER TABLE `student_downloads`
  ADD CONSTRAINT `student_downloads_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_fees`
--
ALTER TABLE `student_fees`
  ADD CONSTRAINT `student_fees_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_fee_assignments`
--
ALTER TABLE `student_fee_assignments`
  ADD CONSTRAINT `student_fee_assignments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_fee_assignments_ibfk_2` FOREIGN KEY (`fee_structure_id`) REFERENCES `fee_structures` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_fee_assignments_ibfk_3` FOREIGN KEY (`assigned_by`) REFERENCES `igangaschoolofl_staffs_db`.`staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_invoices`
--
ALTER TABLE `student_invoices`
  ADD CONSTRAINT `student_invoices_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_invoices_ibfk_2` FOREIGN KEY (`fee_assignment_id`) REFERENCES `student_fee_assignments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `student_invoices_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `igangaschoolofl_staffs_db`.`staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_messages`
--
ALTER TABLE `student_messages`
  ADD CONSTRAINT `student_messages_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_notifications`
--
ALTER TABLE `student_notifications`
  ADD CONSTRAINT `student_notifications_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_password_resets`
--
ALTER TABLE `student_password_resets`
  ADD CONSTRAINT `student_password_resets_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_penalties`
--
ALTER TABLE `student_penalties`
  ADD CONSTRAINT `student_penalties_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_penalties_ibfk_2` FOREIGN KEY (`applied_by`) REFERENCES `igangaschoolofl_staffs_db`.`staff` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `student_penalties_ibfk_3` FOREIGN KEY (`waived_by`) REFERENCES `igangaschoolofl_staffs_db`.`staff` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD CONSTRAINT `student_profiles_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student_sick_leave`
--
ALTER TABLE `student_sick_leave`
  ADD CONSTRAINT `student_sick_leave_ibfk_1` FOREIGN KEY (`sickness_id`) REFERENCES `sickness_directory` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `student_timetables`
--
ALTER TABLE `student_timetables`
  ADD CONSTRAINT `student_timetables_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
