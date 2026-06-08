-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 08, 2026 at 04:49 PM
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

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `id` int NOT NULL,
  `asset_tag` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `asset_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_id` int DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_price` decimal(12,2) DEFAULT NULL,
  `current_value` decimal(12,2) DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assigned_to` int DEFAULT NULL,
  `status` enum('Active','Disposed','Lost','Under Maintenance') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_categories`
--

CREATE TABLE `asset_categories` (
  `id` int NOT NULL,
  `category_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `depreciation_rate` decimal(5,2) DEFAULT '0.00',
  `useful_life_years` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
--

CREATE TABLE `budgets` (
  `id` int NOT NULL,
  `budget_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fiscal_year` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('Draft','Approved','Active','Closed') COLLATE utf8mb4_unicode_ci DEFAULT 'Draft',
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

CREATE TABLE `budget_records` (
  `id` int NOT NULL,
  `budget_id` int NOT NULL,
  `budget_item` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `allocated_amount` decimal(12,2) NOT NULL,
  `spent_amount` decimal(12,2) DEFAULT '0.00',
  `remaining_amount` decimal(12,2) GENERATED ALWAYS AS ((`allocated_amount` - `spent_amount`)) STORED,
  `status` enum('Active','Exhausted','Cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_users`
--

CREATE TABLE `bursar_users` (
  `id` int NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('bursar','accounts_assistant','auditor') COLLATE utf8mb4_unicode_ci DEFAULT 'bursar',
  `status` enum('active','inactive','suspended') COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cash_book`
--

CREATE TABLE `cash_book` (
  `id` int NOT NULL,
  `entry_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entry_type` enum('Receipt','Payment') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `balance` decimal(15,2) NOT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `related_student_id` int DEFAULT NULL,
  `transaction_date` date DEFAULT (curdate()),
  `recorded_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chart_of_accounts`
--

CREATE TABLE `chart_of_accounts` (
  `id` int NOT NULL,
  `account_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_type` enum('Asset','Liability','Equity','Revenue','Expense') COLLATE utf8mb4_unicode_ci NOT NULL,
  `parent_account_id` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chart_of_accounts`
--

INSERT INTO `chart_of_accounts` (`id`, `account_code`, `account_name`, `account_type`, `parent_account_id`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, '1000', 'Cash and Cash Equivalents', 'Asset', NULL, 'Cash on hand and in bank', 1, '2026-06-08 14:42:24', '2026-06-08 14:42:24'),
(2, '1100', 'Accounts Receivable', 'Asset', NULL, 'Student fees receivable', 1, '2026-06-08 14:42:24', '2026-06-08 14:42:24'),
(3, '1200', 'Inventory', 'Asset', NULL, 'Supplies and inventory', 1, '2026-06-08 14:42:24', '2026-06-08 14:42:24'),
(4, '1500', 'Fixed Assets', 'Asset', NULL, 'Property, plant and equipment', 1, '2026-06-08 14:42:24', '2026-06-08 14:42:24'),
(5, '2000', 'Accounts Payable', 'Liability', NULL, 'Amounts owed to suppliers', 1, '2026-06-08 14:42:24', '2026-06-08 14:42:24'),
(6, '2100', 'Accrued Liabilities', 'Liability', NULL, 'Accrued expenses', 1, '2026-06-08 14:42:24', '2026-06-08 14:42:24'),
(7, '3000', 'Net Assets', 'Equity', NULL, 'Institution net worth', 1, '2026-06-08 14:42:24', '2026-06-08 14:42:24'),
(8, '4000', 'Tuition Revenue', 'Revenue', NULL, 'Income from student tuition', 1, '2026-06-08 14:42:24', '2026-06-08 14:42:24'),
(9, '4100', 'Registration Revenue', 'Revenue', NULL, 'Income from student registration', 1, '2026-06-08 14:42:24', '2026-06-08 14:42:24'),
(10, '4200', 'Other Revenue', 'Revenue', NULL, 'Miscellaneous income', 1, '2026-06-08 14:42:24', '2026-06-08 14:42:24'),
(11, '5000', 'Salary Expenses', 'Expense', NULL, 'Staff salaries and wages', 1, '2026-06-08 14:42:24', '2026-06-08 14:42:24'),
(12, '5100', 'Administrative Expenses', 'Expense', NULL, 'Office and administrative costs', 1, '2026-06-08 14:42:24', '2026-06-08 14:42:24'),
(13, '5200', 'Operational Expenses', 'Expense', NULL, 'Day-to-day operational costs', 1, '2026-06-08 14:42:24', '2026-06-08 14:42:24'),
(14, '5300', 'Maintenance Expenses', 'Expense', NULL, 'Facility maintenance costs', 1, '2026-06-08 14:42:24', '2026-06-08 14:42:24');

-- --------------------------------------------------------

--
-- Table structure for table `cost_centers`
--

CREATE TABLE `cost_centers` (
  `id` int NOT NULL,
  `cost_center_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cost_center_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cost_centers`
--

INSERT INTO `cost_centers` (`id`, `cost_center_code`, `cost_center_name`, `department`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'CC-EXEC', 'Executive Office', 'Executive Office', NULL, 1, '2026-06-08 14:42:24', '2026-06-08 14:42:24'),
(2, 'CC-NUR', 'Nursing Department', 'Nursing Department', NULL, 1, '2026-06-08 14:42:24', '2026-06-08 14:42:24'),
(3, 'CC-MID', 'Midwifery Department', 'Midwifery Department', NULL, 1, '2026-06-08 14:42:24', '2026-06-08 14:42:24'),
(4, 'CC-ACAD', 'Academic Affairs', 'Academic Affairs', NULL, 1, '2026-06-08 14:42:24', '2026-06-08 14:42:24'),
(5, 'CC-FIN', 'Finance Department', 'Finance Department', NULL, 1, '2026-06-08 14:42:24', '2026-06-08 14:42:24'),
(6, 'CC-HR', 'Human Resources', 'Human Resources', NULL, 1, '2026-06-08 14:42:24', '2026-06-08 14:42:24'),
(7, 'CC-LIB', 'Library Services', 'Library Services', NULL, 1, '2026-06-08 14:42:24', '2026-06-08 14:42:24'),
(8, 'CC-STU', 'Student Affairs', 'Student Affairs', NULL, 1, '2026-06-08 14:42:24', '2026-06-08 14:42:24'),
(9, 'CC-SEC', 'Security Services', 'Security Services', NULL, 1, '2026-06-08 14:42:24', '2026-06-08 14:42:24'),
(10, 'CC-ICT', 'Information Technology', 'Information Technology', NULL, 1, '2026-06-08 14:42:24', '2026-06-08 14:42:24'),
(11, 'CC-FAC', 'Facilities Management', 'Facilities Management', NULL, 1, '2026-06-08 14:42:24', '2026-06-08 14:42:24');

-- --------------------------------------------------------

--
-- Table structure for table `expenditure_records`
--

CREATE TABLE `expenditure_records` (
  `id` int NOT NULL,
  `expenditure_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `budget_record_id` int DEFAULT NULL,
  `expenditure_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expenditure_date` date DEFAULT (curdate()),
  `approved_by` int DEFAULT NULL,
  `recorded_by` int DEFAULT NULL,
  `supporting_document` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_adjustments`
--

CREATE TABLE `fee_adjustments` (
  `id` int NOT NULL,
  `adjustment_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_id` int NOT NULL,
  `invoice_id` int DEFAULT NULL,
  `adjustment_type` enum('Discount','Waiver','Penalty','Refund','Other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `approved_by` int DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_reminders`
--

CREATE TABLE `fee_reminders` (
  `id` int NOT NULL,
  `reminder_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_id` int NOT NULL,
  `invoice_id` int DEFAULT NULL,
  `reminder_type` enum('Email','SMS','Letter','Call') COLLATE utf8mb4_unicode_ci DEFAULT 'Email',
  `reminder_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_by` int DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_structures`
--

CREATE TABLE `fee_structures` (
  `id` int NOT NULL,
  `fee_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fee_type` enum('Tuition','Registration','Library','Laboratory','Examination','Graduation','Other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `program_id` int DEFAULT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `semester` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_mandatory` tinyint(1) DEFAULT '1',
  `due_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_reports`
--

CREATE TABLE `financial_reports` (
  `id` int NOT NULL,
  `report_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `report_type` enum('Income Statement','Balance Sheet','Cash Flow','Budget vs Actual','Fee Collection','Expenditure','Custom') COLLATE utf8mb4_unicode_ci NOT NULL,
  `report_period` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `report_data` longtext COLLATE utf8mb4_unicode_ci,
  `generated_by` int DEFAULT NULL,
  `generated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('Draft','Final','Archived') COLLATE utf8mb4_unicode_ci DEFAULT 'Draft',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `general_ledger`
--

CREATE TABLE `general_ledger` (
  `id` int NOT NULL,
  `entry_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_id` int NOT NULL,
  `cost_center_id` int DEFAULT NULL,
  `transaction_type` enum('Debit','Credit') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `reference_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_id` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `transaction_date` date DEFAULT (curdate()),
  `posted_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `notification_type` enum('fee_reminder','payment_received','invoice_generated','budget_alert','system') COLLATE utf8mb4_unicode_ci DEFAULT 'system',
  `recipient_type` enum('student','staff','bursar') COLLATE utf8mb4_unicode_ci NOT NULL,
  `recipient_id` int DEFAULT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel` enum('email','sms','in_app') COLLATE utf8mb4_unicode_ci DEFAULT 'in_app',
  `is_read` tinyint(1) DEFAULT '0',
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int NOT NULL,
  `payment_reference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_id` int NOT NULL,
  `invoice_id` int DEFAULT NULL,
  `amount_received` decimal(12,2) NOT NULL,
  `payment_method` enum('Cash','Bank Transfer','Mobile Money','Cheque','Card','Other') COLLATE utf8mb4_unicode_ci DEFAULT 'Cash',
  `payment_date` date DEFAULT (curdate()),
  `transaction_ref` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slip_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Pending','Completed','Failed','Reversed') COLLATE utf8mb4_unicode_ci DEFAULT 'Completed',
  `received_by` int DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_receipts`
--

CREATE TABLE `payment_receipts` (
  `id` int NOT NULL,
  `receipt_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_id` int NOT NULL,
  `student_id` int NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `issued_by` int DEFAULT NULL,
  `voided` tinyint(1) DEFAULT '0',
  `voided_at` timestamp NULL DEFAULT NULL,
  `voided_by` int DEFAULT NULL,
  `void_reason` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `penalty_configurations`
--

CREATE TABLE `penalty_configurations` (
  `id` int NOT NULL,
  `penalty_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `penalty_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT '0.00',
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `penalty_configurations`
--

INSERT INTO `penalty_configurations` (`id`, `penalty_name`, `penalty_type`, `amount`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Late Registration', 'Late Fee', 50000.00, 'Penalty for late course registration', 1, '2026-06-08 14:42:24', '2026-06-08 14:42:24'),
(2, 'Late Payment (1-7 days)', 'Late Fee', 10000.00, 'Penalty for fee payment 1-7 days after due date', 1, '2026-06-08 14:42:24', '2026-06-08 14:42:24'),
(3, 'Late Payment (8-14 days)', 'Late Fee', 25000.00, 'Penalty for fee payment 8-14 days after due date', 1, '2026-06-08 14:42:24', '2026-06-08 14:42:24'),
(4, 'Late Payment (15+ days)', 'Late Fee', 50000.00, 'Penalty for fee payment more than 15 days after due date', 1, '2026-06-08 14:42:24', '2026-06-08 14:42:24'),
(5, 'Lost Library Book', 'Replacement', 30000.00, 'Replacement fee for lost library book', 1, '2026-06-08 14:42:24', '2026-06-08 14:42:24'),
(6, 'Damaged Property', 'Damage', 20000.00, 'Penalty for damaging school property', 1, '2026-06-08 14:42:24', '2026-06-08 14:42:24'),
(7, 'ID Card Replacement', 'Administrative', 10000.00, 'Fee for replacement of lost student ID card', 1, '2026-06-08 14:42:24', '2026-06-08 14:42:24');

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` int NOT NULL,
  `program_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `program_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `program_type` enum('Certificate','Diploma','Degree') COLLATE utf8mb4_unicode_ci DEFAULT 'Diploma',
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

CREATE TABLE `proof_of_payments` (
  `id` int NOT NULL,
  `proof_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payment_id` int NOT NULL,
  `student_id` int NOT NULL,
  `document_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uploaded_by` int DEFAULT NULL,
  `verified` tinyint(1) DEFAULT '0',
  `verified_by` int DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `salary_components`
--

CREATE TABLE `salary_components` (
  `id` int NOT NULL,
  `component_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `component_type` enum('Earning','Deduction') COLLATE utf8mb4_unicode_ci DEFAULT 'Earning',
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_percentage` tinyint(1) DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sponsorships`
--

CREATE TABLE `sponsorships` (
  `id` int NOT NULL,
  `sponsorship_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_id` int NOT NULL,
  `sponsor_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sponsor_type` enum('Government','NGO','Private','Self','Other') COLLATE utf8mb4_unicode_ci DEFAULT 'Self',
  `sponsorship_type` enum('Full','Partial','Tuition Only','Other') COLLATE utf8mb4_unicode_ci DEFAULT 'Partial',
  `amount` decimal(12,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `terms_conditions` text COLLATE utf8mb4_unicode_ci,
  `status` enum('Active','Expired','Cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_salaries`
--

CREATE TABLE `staff_salaries` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `base_salary` decimal(12,2) NOT NULL,
  `allowances` decimal(12,2) DEFAULT '0.00',
  `deductions` decimal(12,2) DEFAULT '0.00',
  `net_salary` decimal(12,2) GENERATED ALWAYS AS (((`base_salary` + `allowances`) - `deductions`)) STORED,
  `effective_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('Active','Inactive','Pending') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int NOT NULL,
  `student_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `registration_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `national_student_id_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `index_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `surname` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `other_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `full_name` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `program` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `course` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_year` int DEFAULT NULL,
  `year` int DEFAULT NULL,
  `level` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `set_name` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_semester` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `intake_date` date DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') COLLATE utf8mb4_unicode_ci DEFAULT 'Other',
  `nationality` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `emergency_contact_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profile_picture` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `passport_photo` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Active','Inactive','Graduated','Suspended','Withdrawn','deleted') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
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
-- Table structure for table `student_academic_records`
--

CREATE TABLE `student_academic_records` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `semester` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `course_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `grade` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `marks` decimal(5,2) DEFAULT NULL,
  `credits` decimal(3,1) DEFAULT NULL,
  `gpa` decimal(3,2) DEFAULT NULL,
  `cgpa` decimal(3,2) DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_attendance`
--

CREATE TABLE `student_attendance` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `date` date NOT NULL,
  `subject` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `course_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Present','Absent','Late','Excused') COLLATE utf8mb4_unicode_ci NOT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `recorded_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `student_dashboard_view`
-- (See below for the actual view)
--
CREATE TABLE `student_dashboard_view` (
`attendance_rate` decimal(31,5)
,`course` varchar(100)
,`current_gpa` decimal(3,2)
,`email` varchar(100)
,`fee_balance` decimal(32,2)
,`full_name` varchar(302)
,`id` int
,`profile_picture` varchar(500)
,`set_name` varchar(50)
,`student_number` varchar(50)
,`year` bigint
);

-- --------------------------------------------------------

--
-- Table structure for table `student_downloads`
--

CREATE TABLE `student_downloads` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint DEFAULT NULL,
  `download_count` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_fees`
--

CREATE TABLE `student_fees` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `fee_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date DEFAULT NULL,
  `paid_date` date DEFAULT NULL,
  `status` enum('Unpaid','Partially Paid','Paid','Overdue') COLLATE utf8mb4_unicode_ci DEFAULT 'Unpaid',
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_fee_assignments`
--

CREATE TABLE `student_fee_assignments` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `fee_structure_id` int NOT NULL,
  `assigned_amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) DEFAULT '0.00',
  `balance` decimal(10,2) GENERATED ALWAYS AS ((`assigned_amount` - `paid_amount`)) STORED,
  `status` enum('Unpaid','Partially Paid','Paid','Waived') COLLATE utf8mb4_unicode_ci DEFAULT 'Unpaid',
  `due_date` date DEFAULT NULL,
  `assigned_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_invoices`
--

CREATE TABLE `student_invoices` (
  `id` int NOT NULL,
  `invoice_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_id` int NOT NULL,
  `fee_assignment_id` int DEFAULT NULL,
  `fee_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `total_amount` decimal(12,2) NOT NULL,
  `discount_amount` decimal(12,2) DEFAULT '0.00',
  `net_amount` decimal(12,2) GENERATED ALWAYS AS ((`total_amount` - `discount_amount`)) STORED,
  `amount_paid` decimal(12,2) DEFAULT '0.00',
  `balance` decimal(12,2) GENERATED ALWAYS AS ((`net_amount` - `amount_paid`)) STORED,
  `status` enum('Draft','Pending','Partially Paid','Paid','Overdue','Cancelled','Waived') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `due_date` date DEFAULT NULL,
  `issue_date` date DEFAULT (curdate()),
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `student_login_view`
-- (See below for the actual view)
--
CREATE TABLE `student_login_view` (
`course` varchar(100)
,`email` varchar(100)
,`full_name` varchar(302)
,`id` int
,`is_first_login` tinyint(1)
,`last_login` timestamp
,`login_attempts` int
,`password` varchar(255)
,`status` enum('Active','Inactive','Graduated','Suspended','Withdrawn','deleted')
,`student_number` varchar(50)
);

-- --------------------------------------------------------

--
-- Table structure for table `student_messages`
--

CREATE TABLE `student_messages` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `department_email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `replied` tinyint(1) DEFAULT '0',
  `reply_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `replied_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_notifications`
--

CREATE TABLE `student_notifications` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('General','Academic','Fee','Attendance','Exam','Event','Matron','Bursar') COLLATE utf8mb4_unicode_ci DEFAULT 'General',
  `priority` enum('Low','Medium','High','Urgent') COLLATE utf8mb4_unicode_ci DEFAULT 'Medium',
  `is_read` tinyint(1) DEFAULT '0',
  `action_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_password_resets`
--

CREATE TABLE `student_password_resets` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `reset_token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` timestamp NOT NULL,
  `is_used` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_penalties`
--

CREATE TABLE `student_penalties` (
  `id` int NOT NULL,
  `penalty_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_id` int NOT NULL,
  `penalty_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) DEFAULT '0.00',
  `reason` text COLLATE utf8mb4_unicode_ci,
  `applied_by` int DEFAULT NULL,
  `applied_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `waived` tinyint(1) DEFAULT '0',
  `waived_by` int DEFAULT NULL,
  `waived_at` timestamp NULL DEFAULT NULL,
  `waiver_reason` text COLLATE utf8mb4_unicode_ci,
  `status` enum('Active','Waived','Paid') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_profiles`
--

CREATE TABLE `student_profiles` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `bio` text COLLATE utf8mb4_unicode_ci,
  `interests` text COLLATE utf8mb4_unicode_ci,
  `skills` text COLLATE utf8mb4_unicode_ci,
  `achievements` text COLLATE utf8mb4_unicode_ci,
  `education_background` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_timetables`
--

CREATE TABLE `student_timetables` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') COLLATE utf8mb4_unicode_ci NOT NULL,
  `time_slot` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lecturer` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `classroom` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure for view `student_dashboard_view`
--
DROP TABLE IF EXISTS `student_dashboard_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `student_dashboard_view`  AS SELECT `s`.`id` AS `id`, `s`.`student_number` AS `student_number`, coalesce(`s`.`full_name`,trim(concat(`s`.`first_name`,' ',coalesce(`s`.`other_name`,''),' ',`s`.`surname`))) AS `full_name`, coalesce(`s`.`course`,`s`.`program`) AS `course`, coalesce(`s`.`year`,`s`.`current_year`) AS `year`, `s`.`set_name` AS `set_name`, `s`.`email` AS `email`, coalesce(`s`.`profile_picture`,`s`.`passport_photo`) AS `profile_picture`, coalesce(`sa`.`gpa`,0) AS `current_gpa`, coalesce(`sf`.`balance`,0) AS `fee_balance`, coalesce(`sa2`.`attendance_rate`,0) AS `attendance_rate` FROM (((`students` `s` left join (select `student_academic_records`.`student_id` AS `student_id`,`student_academic_records`.`gpa` AS `gpa` from `student_academic_records` where (`student_academic_records`.`semester` = (select max(`student_academic_records`.`semester`) from `student_academic_records`)) group by `student_academic_records`.`student_id`) `sa` on((`s`.`id` = `sa`.`student_id`))) left join (select `student_attendance`.`student_id` AS `student_id`,((sum((case when (`student_attendance`.`status` = 'Present') then 1 else 0 end)) * 100.0) / count(0)) AS `attendance_rate` from `student_attendance` group by `student_attendance`.`student_id`) `sa2` on((`s`.`id` = `sa2`.`student_id`))) left join (select `student_fees`.`student_id` AS `student_id`,sum(`student_fees`.`amount`) AS `balance` from `student_fees` where (`student_fees`.`status` in ('Unpaid','Partially Paid','Overdue')) group by `student_fees`.`student_id`) `sf` on((`s`.`id` = `sf`.`student_id`))) WHERE (`s`.`status` = 'Active') ;

-- --------------------------------------------------------

--
-- Structure for view `student_login_view`
--
DROP TABLE IF EXISTS `student_login_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `student_login_view`  AS SELECT `students`.`id` AS `id`, `students`.`student_number` AS `student_number`, coalesce(`students`.`full_name`,trim(concat(`students`.`first_name`,' ',coalesce(`students`.`other_name`,''),' ',`students`.`surname`))) AS `full_name`, `students`.`email` AS `email`, `students`.`password` AS `password`, coalesce(`students`.`course`,`students`.`program`) AS `course`, `students`.`status` AS `status`, `students`.`last_login` AS `last_login`, `students`.`login_attempts` AS `login_attempts`, `students`.`is_first_login` AS `is_first_login` FROM `students` WHERE (`students`.`status` = 'Active') ;

--
-- Indexes for dumped tables
--

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
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `asset_categories`
--
ALTER TABLE `asset_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fiscal_year` (`fiscal_year`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `budget_records`
--
ALTER TABLE `budget_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_budget_id` (`budget_id`),
  ADD KEY `idx_status` (`status`);

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
  ADD KEY `idx_transaction_date` (`transaction_date`);

--
-- Indexes for table `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `account_code` (`account_code`),
  ADD KEY `parent_account_id` (`parent_account_id`),
  ADD KEY `idx_account_code` (`account_code`),
  ADD KEY `idx_account_type` (`account_type`);

--
-- Indexes for table `cost_centers`
--
ALTER TABLE `cost_centers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cost_center_code` (`cost_center_code`),
  ADD KEY `idx_cost_center_code` (`cost_center_code`);

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
  ADD KEY `idx_recorded_by` (`recorded_by`);

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
  ADD KEY `idx_status` (`status`);

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
  ADD KEY `idx_reminder_date` (`reminder_date`);

--
-- Indexes for table `fee_structures`
--
ALTER TABLE `fee_structures`
  ADD PRIMARY KEY (`id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `idx_fee_type` (`fee_type`),
  ADD KEY `idx_academic_year` (`academic_year`);

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
  ADD KEY `idx_transaction_date` (`transaction_date`);

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
  ADD KEY `idx_payment_date` (`payment_date`);

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
  ADD KEY `idx_student_id` (`student_id`);

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
  ADD KEY `idx_student_id` (`student_id`);

--
-- Indexes for table `salary_components`
--
ALTER TABLE `salary_components`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `component_name` (`component_name`);

--
-- Indexes for table `sponsorships`
--
ALTER TABLE `sponsorships`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sponsorship_code` (`sponsorship_code`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_sponsorship_code` (`sponsorship_code`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_status` (`status`);

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
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `student_academic_records`
--
ALTER TABLE `student_academic_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_semester` (`semester`),
  ADD KEY `idx_academic_year` (`academic_year`),
  ADD KEY `idx_subject` (`subject`);

--
-- Indexes for table `student_attendance`
--
ALTER TABLE `student_attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_subject` (`subject`),
  ADD KEY `idx_status` (`status`);

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
  ADD KEY `idx_due_date` (`due_date`);

--
-- Indexes for table `student_fee_assignments`
--
ALTER TABLE `student_fee_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fee_structure_id` (`fee_structure_id`),
  ADD KEY `assigned_by` (`assigned_by`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_due_date` (`due_date`);

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
  ADD KEY `idx_due_date` (`due_date`);

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
-- Indexes for table `student_timetables`
--
ALTER TABLE `student_timetables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_day_of_week` (`day_of_week`),
  ADD KEY `idx_subject` (`subject`);

--
-- AUTO_INCREMENT for dumped tables
--

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
-- AUTO_INCREMENT for table `cost_centers`
--
ALTER TABLE `cost_centers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

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
-- AUTO_INCREMENT for table `salary_components`
--
ALTER TABLE `salary_components`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `student_timetables`
--
ALTER TABLE `student_timetables`
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
-- Constraints for table `student_timetables`
--
ALTER TABLE `student_timetables`
  ADD CONSTRAINT `student_timetables_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
