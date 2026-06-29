-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 28, 2026 at 05:58 PM
-- Server version: 8.0.45
-- PHP Version: 8.2.12

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `igangaschoolofl_staffs_db`
--
DROP DATABASE IF EXISTS `igangaschoolofl_staffs_db`;
CREATE DATABASE IF NOT EXISTS `igangaschoolofl_staffs_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `igangaschoolofl_staffs_db`;

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_dashboard_statistics` (IN `p_user_id` INT, IN `p_role` VARCHAR(100))   BEGIN
    SELECT
        (SELECT COUNT(*) FROM igangaschoolofl_staffs_db.staff WHERE status='Active') AS total_staff,
        (SELECT COUNT(*) FROM igangaschoolofl_students_db.students WHERE status='Active') AS total_students,
        0 AS pending_applications,
        2 AS active_programs,
        0 AS total_revenue,
        0 AS total_expenses;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `get_staff_performance_summary` (IN `p_user_id` INT)   BEGIN
    SELECT s.id, s.full_name, sr.role_name, s.department, s.status,
           0 AS performance_score, 'Good' AS rating
    FROM staff s
    LEFT JOIN staff_roles sr ON s.role_id = sr.id
    WHERE s.status = 'Active'
    ORDER BY s.full_name
    LIMIT 20;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_fix_stage_cols` ()   BEGIN
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
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `academic_analytics`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `academic_analytics` (
  `id` int NOT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `semester` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `program` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metric_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metric_value` decimal(10,2) DEFAULT NULL,
  `calculated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `academic_approvals`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `academic_approvals` (
  `id` int NOT NULL,
  `reference_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'result|transcript|certificate|graduation',
  `reference_id` int NOT NULL,
  `approval_level` varchar(50) COLLATE utf8mb4_general_ci NOT NULL COMMENT 'lecturer|hod|director_academics|registrar|principal|director_general',
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `comments` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `academic_audit_logs`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `academic_audit_logs` (
  `id` int NOT NULL,
  `staff_id` int DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `entity_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `entity_id` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `old_values` longtext COLLATE utf8mb4_general_ci,
  `new_values` longtext COLLATE utf8mb4_general_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `academic_calendar`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `academic_calendar` (
  `id` int NOT NULL,
  `calendar_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `semester` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `semester_start_date` date DEFAULT NULL,
  `semester_end_date` date DEFAULT NULL,
  `exam_start_date` date DEFAULT NULL,
  `exam_end_date` date DEFAULT NULL,
  `result_publication_date` date DEFAULT NULL,
  `registration_deadline` date DEFAULT NULL,
  `status` enum('Upcoming','Ongoing','Completed') COLLATE utf8mb4_general_ci DEFAULT 'Upcoming',
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_calendar`
--

INSERT DELAYED IGNORE INTO `academic_calendar` (`id`, `calendar_id`, `academic_year`, `semester`, `semester_start_date`, `semester_end_date`, `exam_start_date`, `exam_end_date`, `result_publication_date`, `registration_deadline`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'CAL-2025-S1-001', '2025/2026', 'Semester 1', '2025-09-01', '2026-01-31', '2025-12-01', '2025-12-20', NULL, NULL, 'Ongoing', 1, '2026-06-18 21:12:21', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `academic_course_catalog`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `academic_course_catalog` (
  `id` int NOT NULL,
  `course_code` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `course_name` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `department` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `credit_hours` int DEFAULT '0',
  `description` text COLLATE utf8mb4_general_ci,
  `status` enum('Active','Inactive') COLLATE utf8mb4_general_ci DEFAULT 'Active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_course_catalog`
--

INSERT DELAYED IGNORE INTO `academic_course_catalog` (`id`, `course_code`, `course_name`, `department`, `credit_hours`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'NUR101', 'Introduction to Nursing', 'Nursing', 0, NULL, 'Active', '2026-06-18 21:12:21', NULL),
(2, 'NUR102', 'Anatomy and Physiology', 'Nursing', 0, NULL, 'Active', '2026-06-18 21:12:21', NULL),
(3, 'NUR201', 'Medical-Surgical Nursing', 'Nursing', 0, NULL, 'Active', '2026-06-18 21:12:21', NULL),
(4, 'MID101', 'Introduction to Midwifery', 'Midwifery', 0, NULL, 'Active', '2026-06-18 21:12:21', NULL),
(5, 'MID102', 'Reproductive Health', 'Midwifery', 0, NULL, 'Active', '2026-06-18 21:12:21', NULL),
(6, 'COM101', 'Communication Skills', 'General Studies', 0, NULL, 'Active', '2026-06-18 21:12:21', NULL),
(7, 'BIO101', 'Biology', 'General Studies', 0, NULL, 'Active', '2026-06-18 21:12:21', NULL),
(8, 'CHEM101', 'Chemistry', 'General Studies', 0, NULL, 'Active', '2026-06-18 21:12:21', NULL),
(9, 'PHY101', 'Physics', 'General Studies', 0, NULL, 'Active', '2026-06-18 21:12:21', NULL),
(10, 'ENG101', 'English', 'General Studies', 0, NULL, 'Active', '2026-06-18 21:12:21', NULL),
(11, 'MATH101', 'Mathematics', 'General Studies', 0, NULL, 'Active', '2026-06-18 21:12:21', NULL),
(12, 'PHARM101', 'Pharmacology', 'Nursing', 0, NULL, 'Active', '2026-06-18 21:12:21', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `academic_curriculum_development`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `academic_curriculum_development` (
  `id` int UNSIGNED NOT NULL,
  `program_id` int UNSIGNED DEFAULT NULL,
  `course_code` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `course_name` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `credit_hours` int DEFAULT NULL,
  `semester` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `year` int DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `academic_programs`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `academic_programs` (
  `id` int NOT NULL,
  `program_code` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `program_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `program_type` enum('Certificate','Diploma','Degree','Other') COLLATE utf8mb4_general_ci DEFAULT 'Certificate',
  `duration_years` decimal(3,1) DEFAULT '3.0',
  `department` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('Active','Inactive') COLLATE utf8mb4_general_ci DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_programs`
--

INSERT DELAYED IGNORE INTO `academic_programs` (`id`, `program_code`, `program_name`, `program_type`, `duration_years`, `department`, `status`, `created_at`) VALUES
(1, 'CERT-NUR', 'Certificate in Nursing', 'Certificate', 3.0, 'Nursing', 'Active', '2026-06-22 12:10:24'),
(2, 'CERT-MID', 'Certificate in Midwifery', 'Certificate', 3.0, 'Midwifery', 'Active', '2026-06-22 12:10:24'),
(3, 'DIP-NUR', 'Diploma in Nursing', 'Diploma', 3.0, 'Nursing', 'Active', '2026-06-22 12:10:24'),
(4, 'DIP-MID', 'Diploma in Midwifery', 'Diploma', 3.0, 'Midwifery', 'Active', '2026-06-22 12:10:24');

-- --------------------------------------------------------

--
-- Table structure for table `academic_records`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `academic_records` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `semester` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `year` int DEFAULT NULL,
  `course_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `course_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `course_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `credits` int DEFAULT '0',
  `assessment_marks` decimal(5,2) DEFAULT '0.00',
  `exam_marks` decimal(5,2) DEFAULT '0.00',
  `total_marks` decimal(5,2) DEFAULT '0.00',
  `grade` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `grade_points` decimal(4,2) DEFAULT '0.00',
  `gpa_contribution` decimal(4,2) DEFAULT '0.00',
  `gpa` decimal(4,2) DEFAULT '0.00',
  `lecturer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lecturer_id` int DEFAULT NULL,
  `assessment_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Exam',
  `marks` decimal(5,2) DEFAULT '0.00',
  `entered_by` int DEFAULT NULL,
  `graded_by` int DEFAULT NULL,
  `entry_date` date DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `academic_reports`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `academic_reports` (
  `id` int NOT NULL,
  `report_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `semester` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `program` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `generated_by` int DEFAULT NULL,
  `report_data` longtext COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'generated',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `academic_timetable`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `academic_timetable` (
  `id` int NOT NULL,
  `timetable_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `semester` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `program_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `course_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `course_id` int DEFAULT NULL,
  `day_of_week` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `venue` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lecturer_id` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `timetable_status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `access_control_logs`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `access_control_logs` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `user_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resource` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `access_time` datetime DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'success',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `accreditation_management`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `accreditation_management` (
  `id` int NOT NULL,
  `accreditation_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `body_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `valid_from` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `documents` json DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `activity` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `details` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `created_at_ts` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `module` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entity_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entity_id` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admission_activity_logs`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `admission_activity_logs` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'admissions',
  `record_id` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admission_activity_logs`
--

INSERT DELAYED IGNORE INTO `admission_activity_logs` (`id`, `user_id`, `action`, `module`, `record_id`, `description`, `created_at`) VALUES
(1, 24, 'Create Student', 'students', 0, 'Created student: Otema Reagan (u004/cm/076)', '2026-06-22 13:01:24');

-- --------------------------------------------------------

--
-- Table structure for table `admission_notifications`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `admission_notifications` (
  `id` int NOT NULL,
  `applicant_id` int DEFAULT NULL,
  `recipient_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'applicant',
  `recipient_id` int DEFAULT NULL,
  `title` varchar(300) COLLATE utf8mb4_general_ci NOT NULL,
  `message` text COLLATE utf8mb4_general_ci NOT NULL,
  `channel` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'portal',
  `sent_by` int NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admission_requirements`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `admission_requirements` (
  `id` int NOT NULL,
  `requirement_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Document',
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `is_mandatory` tinyint(1) DEFAULT '1',
  `display_order` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admission_requirements`
--

INSERT DELAYED IGNORE INTO `admission_requirements` (`id`, `requirement_name`, `type`, `description`, `is_active`, `is_mandatory`, `display_order`, `created_at`) VALUES
(1, 'Surgical Gloves', 'Document', NULL, 1, 1, 1, '2026-06-22 11:07:53'),
(2, 'Examination Gloves', 'Document', NULL, 1, 1, 2, '2026-06-22 11:07:53'),
(3, 'Photocopying Ream', 'Document', NULL, 1, 1, 3, '2026-06-22 11:07:53'),
(4, 'Ruled Paper Reams', 'Document', NULL, 1, 1, 4, '2026-06-22 11:07:53'),
(5, 'Omo', 'Document', NULL, 1, 1, 5, '2026-06-22 11:07:53'),
(6, 'Toilet Papers', 'Document', NULL, 1, 1, 6, '2026-06-22 11:07:53'),
(7, 'Compound Brooms', 'Document', NULL, 1, 1, 7, '2026-06-22 11:07:53'),
(8, 'Soft Brooms', 'Document', NULL, 1, 1, 8, '2026-06-22 11:07:53'),
(9, 'Rake', 'Document', NULL, 1, 1, 9, '2026-06-22 11:07:53'),
(10, 'Cobweb Brush', 'Document', NULL, 1, 1, 10, '2026-06-22 11:07:53'),
(11, 'Scrubbing Brush', 'Document', NULL, 1, 1, 11, '2026-06-22 11:07:53'),
(12, 'Squeezer', 'Document', NULL, 1, 1, 12, '2026-06-22 11:07:53'),
(13, 'Toilet Brush', 'Document', NULL, 1, 1, 13, '2026-06-22 11:07:53'),
(14, 'JIK', 'Document', NULL, 1, 1, 14, '2026-06-22 11:07:53'),
(15, 'Vim', 'Document', NULL, 1, 1, 15, '2026-06-22 11:07:53'),
(16, 'Mops', 'Document', NULL, 1, 1, 16, '2026-06-22 11:07:53'),
(17, 'Sanitizer', 'Document', NULL, 1, 1, 17, '2026-06-22 11:07:53'),
(18, 'Liquid Soap', 'Document', NULL, 1, 1, 18, '2026-06-22 11:07:53'),
(19, 'Face Masks', 'Document', NULL, 1, 1, 19, '2026-06-22 11:07:53'),
(20, 'Heavy Duty Gloves', 'Document', NULL, 1, 1, 20, '2026-06-22 11:07:53');

-- --------------------------------------------------------

--
-- Table structure for table `advanced_reports`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `advanced_reports` (
  `id` int NOT NULL,
  `report_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `report_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `report_data` longtext COLLATE utf8mb4_unicode_ci,
  `parameters` json DEFAULT NULL,
  `generated_by` int DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'generated',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `alerts`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `alerts` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `type` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'info',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `link` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `resolved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `alert_recipients`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `alert_recipients` (
  `id` int UNSIGNED NOT NULL,
  `alert_id` int UNSIGNED DEFAULT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `analytics_cache`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `analytics_cache` (
  `id` int NOT NULL,
  `cache_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cache_value` longtext COLLATE utf8mb4_unicode_ci,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `announcements` (
  `id` int UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `body` text COLLATE utf8mb4_general_ci,
  `target_audience` varchar(60) COLLATE utf8mb4_general_ci DEFAULT 'All',
  `priority` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'Normal',
  `posted_by` int UNSIGNED DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT DELAYED IGNORE INTO `announcements` (`id`, `title`, `body`, `target_audience`, `priority`, `posted_by`, `is_active`, `created_at`) VALUES
(1, 'Welcome to New Academic Year', 'We welcome all staff and students to the new academic year 2026. Let us work together for excellence.', 'All', 'High', 1, 1, '2026-06-19 23:58:56'),
(2, 'Staff Meeting Reminder', 'There will be a general staff meeting on Friday at 10:00 AM in the main hall.', 'Staff', 'Normal', 1, 1, '2026-06-19 23:58:56'),
(3, 'Maintenance Notice', 'The library will be closed for maintenance on Saturday.', 'All', 'Low', 1, 1, '2026-06-19 23:58:56');

-- --------------------------------------------------------

--
-- Table structure for table `api_keys`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `api_keys` (
  `id` int NOT NULL,
  `key_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int DEFAULT NULL,
  `permissions` json DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `expires_at` datetime DEFAULT NULL,
  `last_used_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applicants`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `applicants` (
  `id` int NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') COLLATE utf8mb4_unicode_ci DEFAULT 'Other',
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `guardian_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `guardian_relationship` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `application_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `program_id` int DEFAULT NULL,
  `intake` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `admission_date` date DEFAULT NULL,
  `status` enum('New Applicant','Under Review','Approved','Rejected','Registered') COLLATE utf8mb4_unicode_ci DEFAULT 'New Applicant',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applicant_messages`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `applicant_messages` (
  `id` int NOT NULL,
  `applicant_id` int NOT NULL,
  `sender_id` int NOT NULL,
  `recipient_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'applicant',
  `subject` varchar(300) COLLATE utf8mb4_general_ci NOT NULL,
  `message` text COLLATE utf8mb4_general_ci NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applicant_requirement_status`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `applicant_requirement_status` (
  `id` int NOT NULL,
  `applicant_id` int NOT NULL,
  `requirement_id` int NOT NULL,
  `status` enum('Not Submitted','Submitted','Verified','Rejected','Missing') COLLATE utf8mb4_unicode_ci DEFAULT 'Not Submitted',
  `submitted_by` int DEFAULT NULL,
  `verified_by` int DEFAULT NULL,
  `rejected_by` int DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `application_reviews`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `application_reviews` (
  `id` int NOT NULL,
  `application_id` int DEFAULT NULL,
  `reviewer_id` int DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT NULL,
  `comments` text COLLATE utf8mb4_unicode_ci,
  `recommendation` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appraisals`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `appraisals` (
  `id` int UNSIGNED NOT NULL,
  `staff_id` int UNSIGNED NOT NULL,
  `appraisal_period` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rating` decimal(3,1) DEFAULT NULL,
  `comments` text COLLATE utf8mb4_general_ci,
  `reviewer_id` int UNSIGNED DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appraisal_periods`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `appraisal_periods` (
  `id` int NOT NULL,
  `period_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appraisal_ratings`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `appraisal_ratings` (
  `id` int NOT NULL,
  `appraisal_id` int DEFAULT NULL,
  `staff_id` int DEFAULT NULL,
  `criteria` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT NULL,
  `comments` text COLLATE utf8mb4_unicode_ci,
  `rated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `approval_actions`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `approval_actions` (
  `id` int UNSIGNED NOT NULL,
  `request_id` int UNSIGNED NOT NULL,
  `stage_id` int UNSIGNED DEFAULT NULL,
  `action_by` int UNSIGNED DEFAULT NULL,
  `action_type` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `comments` text COLLATE utf8mb4_general_ci,
  `notes` text COLLATE utf8mb4_general_ci,
  `decision` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `previous_stage_order` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `approval_actions`
--

INSERT DELAYED IGNORE INTO `approval_actions` (`id`, `request_id`, `stage_id`, `action_by`, `action_type`, `comments`, `notes`, `decision`, `previous_stage_order`, `created_at`) VALUES
(1, 3, 2, 1, 'reject', 'yes', NULL, 'Rejected', 2, '2026-06-24 01:32:00');

-- --------------------------------------------------------

--
-- Table structure for table `approval_requests`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `approval_requests` (
  `id` int UNSIGNED NOT NULL,
  `workflow_id` int UNSIGNED NOT NULL,
  `request_number` varchar(60) COLLATE utf8mb4_general_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `priority` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'Medium',
  `requester_id` int UNSIGNED DEFAULT NULL,
  `requester_name` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `requester_role` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `current_stage_id` int UNSIGNED DEFAULT NULL,
  `current_stage_order` int UNSIGNED DEFAULT '1',
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'Active',
  `reference_type` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reference_id` int UNSIGNED DEFAULT NULL,
  `reference_url` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_general_ci,
  `final_approval_by` int UNSIGNED DEFAULT NULL,
  `final_approval_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `approval_requests`
--

INSERT DELAYED IGNORE INTO `approval_requests` (`id`, `workflow_id`, `request_number`, `title`, `description`, `priority`, `requester_id`, `requester_name`, `requester_role`, `current_stage_id`, `current_stage_order`, `status`, `reference_type`, `reference_id`, `reference_url`, `rejection_reason`, `final_approval_by`, `final_approval_at`, `created_at`, `updated_at`) VALUES
(1, 1, 'REQ-20260620-A73F2B', 'Laboratory Equipment Restock', 'Request to restock essential laboratory equipment including microscopes and slides for Nursing dept.', 'High', 2, 'Mary Nalwoga', 'Head of Nursing', 2, 2, 'Active', 'store_requests', 1, NULL, NULL, NULL, NULL, '2026-06-19 22:47:50', '2026-06-20 00:47:50'),
(2, 1, 'REQ-20260620-B84C3D', 'Office Stationery Order', 'Monthly stationery supplies for administrative offices - paper, pens, folders, ink cartridges.', 'Medium', 3, 'James Okello', 'School Secretary', 2, 2, 'Active', 'store_requests', 2, NULL, NULL, NULL, NULL, '2026-06-19 19:47:50', '2026-06-20 00:47:50'),
(3, 1, 'REQ-20260619-C95D4E', 'Medical Consumables', 'Urgent restock of gloves, masks, sanitizers and first aid supplies for the sickbay.', 'Urgent', 4, 'Sarah Kyomugisha', 'Matron', 2, 2, 'Rejected', 'store_requests', 3, NULL, 'yes', NULL, NULL, '2026-06-19 00:47:50', '2026-06-24 01:32:00'),
(4, 2, 'REQ-20260620-D06E5F', 'New Student: Akello Grace', 'Registration application for Diploma Nursing program. Submitted by Registrar.', 'Normal', 5, 'Peter Okoth', 'Academic Registrar', 4, 2, 'Active', 'pending_students', 1, NULL, NULL, NULL, NULL, '2026-06-19 21:47:50', '2026-06-20 00:47:50'),
(5, 2, 'REQ-20260619-E17F6G', 'New Student: Bwire John', 'Registration application for Certificate Midwifery program. All documents verified.', 'Normal', 5, 'Peter Okoth', 'Academic Registrar', 4, 2, 'Active', 'pending_students', 2, NULL, NULL, NULL, NULL, '2026-06-19 00:47:50', '2026-06-20 00:47:50'),
(6, 3, 'REQ-20260620-F28G7H', 'End of Year Examination Schedule', 'Proposed examination timetable for the June 2026 semester. Requires DG sign-off.', 'Medium', 2, 'Mary Nalwoga', 'Head of Nursing', 5, 1, 'Active', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-19 18:47:50', '2026-06-20 00:47:50');

-- --------------------------------------------------------

--
-- Table structure for table `approval_stages`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `approval_stages` (
  `id` int UNSIGNED NOT NULL,
  `workflow_id` int UNSIGNED NOT NULL,
  `stage_name` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `stage_order` int UNSIGNED NOT NULL,
  `assigned_role_id` int UNSIGNED DEFAULT NULL,
  `assigned_role_name` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_final` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `approval_stages`
--

INSERT DELAYED IGNORE INTO `approval_stages` (`id`, `workflow_id`, `stage_name`, `stage_order`, `assigned_role_id`, `assigned_role_name`, `is_final`, `created_at`) VALUES
(138, 125, 'Director ICT Review', 1, NULL, 'Director ICT', 0, '2026-06-27 00:17:17'),
(139, 125, 'Director General Final Approval', 2, NULL, 'Director General', 1, '2026-06-27 00:17:17'),
(140, 122, 'Director General Approval', 1, NULL, 'Director General', 1, '2026-06-27 00:17:17'),
(141, 123, 'Director General Approval', 1, NULL, 'Director General', 1, '2026-06-27 00:17:17'),
(142, 124, 'Director General Approval', 1, NULL, 'Director General', 1, '2026-06-27 00:17:17'),
(143, 126, 'Director General Approval', 1, NULL, 'Director General', 1, '2026-06-27 00:17:17'),
(144, 127, 'Director General Approval', 1, NULL, 'Director General', 1, '2026-06-27 00:17:17'),
(145, 128, 'Director General Approval', 1, NULL, 'Director General', 1, '2026-06-27 00:17:17'),
(146, 129, 'Director General Approval', 1, NULL, 'Director General', 1, '2026-06-27 00:17:17'),
(147, 130, 'Director General Approval', 1, NULL, 'Director General', 1, '2026-06-27 00:17:17');

-- --------------------------------------------------------

--
-- Table structure for table `approval_workflows`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `approval_workflows` (
  `id` int UNSIGNED NOT NULL,
  `workflow_name` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `category` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `approval_workflows`
--

INSERT DELAYED IGNORE INTO `approval_workflows` (`id`, `workflow_name`, `category`, `description`, `is_active`, `created_at`) VALUES
(122, 'General Department Request', 'General Administration', 'Standard approval workflow for general administrative requests requiring Director General sign-off', 1, '2026-06-27 00:17:17'),
(123, 'HR Request', 'Human Resources', 'HR-related requests requiring Director General approval', 1, '2026-06-27 00:17:17'),
(124, 'Finance Request', 'Finance', 'Financial requests and budget approvals requiring Director General sign-off', 1, '2026-06-27 00:17:17'),
(125, 'ICT Request', 'ICT', 'ICT department requests requiring departmental review and Director General approval', 1, '2026-06-27 00:17:17'),
(126, 'Academic Request', 'Academic', 'Academic affairs requests requiring Director General approval', 1, '2026-06-27 00:17:17'),
(127, 'Admissions Request', 'Admissions', 'Admissions-related requests requiring Director General approval', 1, '2026-06-27 00:17:17'),
(128, 'Library Request', 'Library', 'Library resource and service requests requiring Director General approval', 1, '2026-06-27 00:17:17'),
(129, 'Store Requisition', 'Store & Assets', 'Store and asset requisitions requiring Director General approval', 1, '2026-06-27 00:17:17'),
(130, 'Student Registration', 'Academic', 'Student registration requests requiring Director General approval', 1, '2026-06-27 00:17:17');

-- --------------------------------------------------------

--
-- Table structure for table `asset_depreciation`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `asset_depreciation` (
  `id` int NOT NULL,
  `asset_id` int DEFAULT NULL,
  `depreciation_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `annual_rate` decimal(5,2) DEFAULT NULL,
  `accumulated_depreciation` decimal(15,2) DEFAULT '0.00',
  `book_value` decimal(15,2) DEFAULT NULL,
  `depreciation_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `attendance` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `class_id` int UNSIGNED DEFAULT NULL,
  `subject_id` int UNSIGNED DEFAULT NULL,
  `date` date NOT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'present',
  `remarks` text COLLATE utf8mb4_general_ci,
  `marked_by` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_trail`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `audit_trail` (
  `id` int UNSIGNED NOT NULL,
  `staff_id` int UNSIGNED DEFAULT NULL,
  `action_type` varchar(60) COLLATE utf8mb4_general_ci NOT NULL,
  `entity_type` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `entity_id` int UNSIGNED DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `backup_management`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `backup_management` (
  `id` int UNSIGNED NOT NULL,
  `backup_name` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `backup_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `file_size` bigint DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bank_accounts`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `bank_accounts` (
  `id` int NOT NULL,
  `bank_name` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `account_name` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `account_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `balance` decimal(15,2) DEFAULT '0.00',
  `is_active` tinyint(1) DEFAULT '1',
  `status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bank_reconciliation`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `bank_reconciliation` (
  `id` int NOT NULL,
  `reconciliation_date` date NOT NULL,
  `bank_balance` decimal(15,2) DEFAULT NULL,
  `book_balance` decimal(15,2) DEFAULT NULL,
  `difference` decimal(15,2) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'unreconciled',
  `reconciled_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bank_reconciliations`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `bank_reconciliations` (
  `id` int NOT NULL,
  `reconciliation_date` date NOT NULL,
  `bank_balance` decimal(15,2) DEFAULT NULL,
  `book_balance` decimal(15,2) DEFAULT NULL,
  `difference` decimal(15,2) DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'unreconciled',
  `reconciled_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budget_lines`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `budget_lines` (
  `id` int NOT NULL,
  `budget_id` int DEFAULT NULL,
  `line_item` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `allocated_amount` decimal(15,2) DEFAULT '0.00',
  `spent_amount` decimal(15,2) DEFAULT '0.00',
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_allowances`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `bursar_allowances` (
  `id` int NOT NULL,
  `staff_id` int DEFAULT NULL,
  `allowance_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `effective_date` date DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_assets`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `bursar_assets` (
  `id` int NOT NULL,
  `asset_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `asset_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_cost` decimal(15,2) DEFAULT NULL,
  `current_value` decimal(15,2) DEFAULT NULL,
  `condition_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_bank_reconciliation`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `bursar_bank_reconciliation` (
  `id` int NOT NULL,
  `reconciliation_date` date NOT NULL,
  `bank_balance` decimal(15,2) NOT NULL,
  `book_balance` decimal(15,2) NOT NULL,
  `difference` decimal(15,2) DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_general_ci,
  `status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'unreconciled',
  `reconciled_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_budget_items`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `bursar_budget_items` (
  `id` int NOT NULL,
  `budget_id` int DEFAULT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `allocated_amount` decimal(15,2) DEFAULT NULL,
  `spent_amount` decimal(15,2) DEFAULT '0.00',
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_cashbook`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `bursar_cashbook` (
  `id` int NOT NULL,
  `transaction_date` date NOT NULL,
  `transaction_type` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `category` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '',
  `reference` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_chart_of_accounts`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `bursar_chart_of_accounts` (
  `id` int NOT NULL,
  `account_code` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `account_name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `account_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_daily_collections`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `bursar_daily_collections` (
  `id` int NOT NULL,
  `collection_date` date NOT NULL,
  `total_collected` decimal(15,2) DEFAULT '0.00',
  `collection_count` int DEFAULT '0',
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collected_by` int DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'recorded',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_deductions`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `bursar_deductions` (
  `id` int NOT NULL,
  `staff_id` int DEFAULT NULL,
  `deduction_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_discounts`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `bursar_discounts` (
  `id` int NOT NULL,
  `fee_account_id` int NOT NULL,
  `discount_type` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `discount_value` decimal(15,2) NOT NULL,
  `discount_amount` decimal(15,2) NOT NULL,
  `reason` text COLLATE utf8mb4_general_ci,
  `applied_by` int NOT NULL,
  `applied_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_expenses`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `bursar_expenses` (
  `id` int NOT NULL,
  `expense_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expense_date` date DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_fee_items`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `bursar_fee_items` (
  `id` int NOT NULL,
  `fee_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fee_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `fee_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_fee_reminders`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `bursar_fee_reminders` (
  `id` int NOT NULL,
  `student_id` int DEFAULT NULL,
  `fee_account_id` int DEFAULT NULL,
  `reminder_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `sent_at` datetime DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_general_ledger`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `bursar_general_ledger` (
  `id` int NOT NULL,
  `entry_date` date NOT NULL,
  `account_code` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reference` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `debit_amount` decimal(15,2) DEFAULT '0.00',
  `credit_amount` decimal(15,2) DEFAULT '0.00',
  `created_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_invoices`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `bursar_invoices` (
  `id` int NOT NULL,
  `invoice_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `paid_amount` decimal(15,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_payments`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `bursar_payments` (
  `id` int NOT NULL,
  `payment_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `invoice_id` int DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'completed',
  `processed_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_payment_verification`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `bursar_payment_verification` (
  `id` int NOT NULL,
  `student_id` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `fee_account_id` int DEFAULT '0',
  `amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `payment_reference` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `payment_date` date NOT NULL,
  `proof_file` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '',
  `status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `verified_by` int DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_payroll`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `bursar_payroll` (
  `id` int NOT NULL,
  `payroll_period` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `staff_id` int DEFAULT NULL,
  `basic_salary` decimal(15,2) DEFAULT NULL,
  `allowances` decimal(15,2) DEFAULT '0.00',
  `deductions` decimal(15,2) DEFAULT '0.00',
  `net_salary` decimal(15,2) DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `processed_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_penalties`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `bursar_penalties` (
  `id` int NOT NULL,
  `student_id` int DEFAULT NULL,
  `penalty_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_penalty_config`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `bursar_penalty_config` (
  `id` int NOT NULL,
  `penalty_name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `penalty_type` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `penalty_value` decimal(15,2) NOT NULL,
  `grace_days` int DEFAULT '0',
  `max_charge` decimal(15,2) DEFAULT '0.00',
  `status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_receipts`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `bursar_receipts` (
  `id` int NOT NULL,
  `receipt_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_id` int DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issued_by` int DEFAULT NULL,
  `issued_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_requisition_reviews`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `bursar_requisition_reviews` (
  `id` int NOT NULL,
  `requester_id` int NOT NULL,
  `item_description` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT '0.00',
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `reviewed_by` int DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_scholarships`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `bursar_scholarships` (
  `id` int NOT NULL,
  `scholarship_name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `scholarship_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `scholarship_value` decimal(15,2) NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `provider` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_settings`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `bursar_settings` (
  `id` int NOT NULL,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_sponsorships`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `bursar_sponsorships` (
  `id` int NOT NULL,
  `student_id` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `sponsor_name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `sponsor_contact` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sponsor_email` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `coverage_percent` decimal(5,2) DEFAULT '100.00',
  `status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_tax_filings`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `bursar_tax_filings` (
  `id` int NOT NULL,
  `tax_period_id` int NOT NULL,
  `filing_date` date NOT NULL,
  `tax_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `total_revenue` decimal(15,2) DEFAULT '0.00',
  `tax_amount` decimal(15,2) DEFAULT '0.00',
  `due_date` date DEFAULT NULL,
  `filing_status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'filed',
  `notes` text COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_tax_periods`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `bursar_tax_periods` (
  `id` int NOT NULL,
  `period_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_tax_records`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `bursar_tax_records` (
  `id` int NOT NULL,
  `staff_id` int DEFAULT NULL,
  `tax_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `tax_period` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_vat_reports`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `bursar_vat_reports` (
  `id` int NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `output_vat` decimal(15,2) DEFAULT '0.00',
  `input_vat` decimal(15,2) DEFAULT '0.00',
  `net_vat` decimal(15,2) DEFAULT '0.00',
  `notes` text COLLATE utf8mb4_general_ci,
  `status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'draft'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_withholding_tax`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `bursar_withholding_tax` (
  `id` int NOT NULL,
  `tax_date` date NOT NULL,
  `payee_name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gross_amount` decimal(15,2) NOT NULL,
  `wht_rate` decimal(5,2) DEFAULT '6.00',
  `wht_amount` decimal(15,2) DEFAULT '0.00',
  `status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_management`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `cache_management` (
  `id` int UNSIGNED NOT NULL,
  `cache_key` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `cache_value` longtext COLLATE utf8mb4_general_ci,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cashbook`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `cashbook` (
  `id` int NOT NULL,
  `transaction_date` date NOT NULL,
  `transaction_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `category` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `balance_after` decimal(15,2) DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `certificates` (
  `id` int NOT NULL,
  `certificate_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `student_id` int NOT NULL,
  `certificate_type` enum('National Certificate','Diploma','Completion Letter','Recommendation Letter','Training Certificate','Clinical Placement Certificate') COLLATE utf8mb4_general_ci NOT NULL,
  `program` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `award` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `gpa` decimal(4,2) DEFAULT NULL,
  `cgpa` decimal(4,2) DEFAULT NULL,
  `class_of_award` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('draft','pending_principal','pending_dg','approved','rejected','released') COLLATE utf8mb4_general_ci DEFAULT 'draft',
  `requested_by` int DEFAULT NULL,
  `requested_at` datetime DEFAULT NULL,
  `approved_by_registrar` int DEFAULT NULL,
  `approved_at_registrar` datetime DEFAULT NULL,
  `approved_by_principal` int DEFAULT NULL,
  `approved_at_principal` datetime DEFAULT NULL,
  `approved_by_dg` int DEFAULT NULL,
  `approved_at_dg` datetime DEFAULT NULL,
  `rejected_by` int DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_general_ci,
  `file_path` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `qr_code` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `barcode` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT '0',
  `student_downloadable` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `certificate_templates`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `certificate_templates` (
  `id` int NOT NULL,
  `template_name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `certificate_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `template_data` longtext COLLATE utf8mb4_general_ci,
  `is_default` tinyint(1) DEFAULT '0',
  `status` enum('active','inactive') COLLATE utf8mb4_general_ci DEFAULT 'active',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `certificate_uploads`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `certificate_uploads` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `certificate_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `uploaded_by` int DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT '0',
  `verified_by` int DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `certificate_verification`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `certificate_verification` (
  `id` int NOT NULL,
  `certificate_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `student_id` int DEFAULT NULL,
  `verification_code` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `verification_url` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `verified_by` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chemical_inventory`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `chemical_inventory` (
  `id` int UNSIGNED NOT NULL,
  `chemical_name` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cas_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT '0.00',
  `unit` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `storage_location` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `safety_data_sheet` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `classes` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `code` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `teacher_id` int UNSIGNED DEFAULT NULL,
  `room` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `capacity` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clinical_assessments`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `clinical_assessments` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `placement_id` int DEFAULT NULL,
  `assessment_date` date DEFAULT NULL,
  `skill_assessed` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `max_score` decimal(5,2) DEFAULT '100.00',
  `passed` tinyint(1) DEFAULT '0',
  `assessed_by` int DEFAULT NULL,
  `comments` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clinical_placements`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `clinical_placements` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `facility_name` varchar(300) COLLATE utf8mb4_general_ci NOT NULL,
  `department` varchar(200) COLLATE utf8mb4_general_ci DEFAULT '',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `supervisor_name` varchar(200) COLLATE utf8mb4_general_ci DEFAULT '',
  `supervisor_phone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '',
  `status` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Active',
  `created_by` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clinical_rotations`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `clinical_rotations` (
  `id` int NOT NULL,
  `student_id` int DEFAULT NULL,
  `rotation_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `facility` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `supervisor_id` int DEFAULT NULL,
  `hours_completed` int DEFAULT '0',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `communications`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `communications` (
  `id` int NOT NULL,
  `recipient_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'student',
  `recipient_id` int DEFAULT '0',
  `subject` varchar(300) COLLATE utf8mb4_general_ci NOT NULL,
  `message` text COLLATE utf8mb4_general_ci NOT NULL,
  `channel` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'portal',
  `sent_by` int DEFAULT '0',
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `communication_channels`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `communication_channels` (
  `id` int NOT NULL,
  `department_code` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `department_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `routing_email` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `compliance_records`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `compliance_records` (
  `id` int UNSIGNED NOT NULL,
  `compliance_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `department` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'compliant',
  `review_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `compliance_requirements`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `compliance_requirements` (
  `id` int UNSIGNED NOT NULL,
  `requirement_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `category` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `regulatory_body` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `frequency` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'Annual',
  `status` enum('Compliant','Partial','Non-Compliant','Not Assessed','Exempt') COLLATE utf8mb4_general_ci DEFAULT 'Not Assessed',
  `due_date` date DEFAULT NULL,
  `last_assessment_date` date DEFAULT NULL,
  `assigned_to` int UNSIGNED DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `compliance_requirements`
--

INSERT DELAYED IGNORE INTO `compliance_requirements` (`id`, `requirement_name`, `category`, `description`, `regulatory_body`, `frequency`, `status`, `due_date`, `last_assessment_date`, `assigned_to`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'NCHE Annual Report', 'Academic', NULL, NULL, 'Annual', 'Not Assessed', '2026-09-18', NULL, NULL, NULL, '2026-06-20 01:28:34', NULL),
(2, 'UNMC License Renewal', 'Regulatory', NULL, NULL, 'Annual', 'Not Assessed', '2026-12-17', NULL, NULL, NULL, '2026-06-20 01:28:34', NULL),
(3, 'Fire Safety Inspection', 'Safety', NULL, NULL, 'Annual', 'Not Assessed', '2026-08-19', NULL, NULL, NULL, '2026-06-20 01:28:34', NULL),
(4, 'Tax Filing', 'Financial', NULL, NULL, 'Annual', 'Not Assessed', '2026-08-04', NULL, NULL, NULL, '2026-06-20 01:28:34', NULL),
(5, 'NCHE Annual Report', 'Academic', NULL, NULL, 'Annual', 'Not Assessed', '2026-09-18', NULL, NULL, NULL, '2026-06-20 01:41:08', NULL),
(6, 'UNMC License Renewal', 'Regulatory', NULL, NULL, 'Annual', 'Not Assessed', '2026-12-17', NULL, NULL, NULL, '2026-06-20 01:41:08', NULL),
(7, 'Fire Safety Inspection', 'Safety', NULL, NULL, 'Annual', 'Not Assessed', '2026-08-19', NULL, NULL, NULL, '2026-06-20 01:41:08', NULL),
(8, 'Tax Filing', 'Financial', NULL, NULL, 'Annual', 'Not Assessed', '2026-08-04', NULL, NULL, NULL, '2026-06-20 01:41:08', NULL),
(9, 'NCHE Annual Report', 'Academic', NULL, NULL, 'Annual', 'Not Assessed', '2026-09-18', NULL, NULL, NULL, '2026-06-20 01:45:03', NULL),
(10, 'UNMC License Renewal', 'Regulatory', NULL, NULL, 'Annual', 'Not Assessed', '2026-12-17', NULL, NULL, NULL, '2026-06-20 01:45:03', NULL),
(11, 'Fire Safety Inspection', 'Safety', NULL, NULL, 'Annual', 'Not Assessed', '2026-08-19', NULL, NULL, NULL, '2026-06-20 01:45:03', NULL),
(12, 'Tax Filing', 'Financial', NULL, NULL, 'Annual', 'Not Assessed', '2026-08-04', NULL, NULL, NULL, '2026-06-20 01:45:03', NULL),
(13, 'NCHE Annual Report', 'Academic', NULL, NULL, 'Annual', 'Not Assessed', '2026-09-18', NULL, NULL, NULL, '2026-06-20 01:46:53', NULL),
(14, 'UNMC License Renewal', 'Regulatory', NULL, NULL, 'Annual', 'Not Assessed', '2026-12-17', NULL, NULL, NULL, '2026-06-20 01:46:53', NULL),
(15, 'Fire Safety Inspection', 'Safety', NULL, NULL, 'Annual', 'Not Assessed', '2026-08-19', NULL, NULL, NULL, '2026-06-20 01:46:53', NULL),
(16, 'Tax Filing', 'Financial', NULL, NULL, 'Annual', 'Not Assessed', '2026-08-04', NULL, NULL, NULL, '2026-06-20 01:46:53', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `compliance_tracking`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `compliance_tracking` (
  `id` int NOT NULL,
  `requirement_id` int DEFAULT NULL,
  `period` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `evidence_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `submitted_by` int DEFAULT NULL,
  `verified_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cost_centers`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `cost_centers` (
  `id` int NOT NULL,
  `center_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `center_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department_id` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `budget_allocated` decimal(15,2) DEFAULT '0.00',
  `budget_spent` decimal(15,2) DEFAULT '0.00',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `counseling_sessions`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `counseling_sessions` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `counselor_id` int UNSIGNED DEFAULT NULL,
  `session_date` datetime DEFAULT NULL,
  `session_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `follow_up_date` date DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_assignments`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `course_assignments` (
  `id` int NOT NULL,
  `lecturer_id` int DEFAULT NULL,
  `course_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `course_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `course_id` int DEFAULT NULL,
  `semester` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `classroom` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assigned_by` int DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_registrations`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `course_registrations` (
  `id` int NOT NULL,
  `student_id` int DEFAULT NULL,
  `course_id` int DEFAULT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `semester` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('Pending','Submitted','Registered','Approved','Rejected','Dropped') COLLATE utf8mb4_general_ci DEFAULT 'Pending',
  `registration_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_sick_records`
--
-- Creation: Jun 28, 2026 at 04:21 AM
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
  `is_deleted` tinyint(1) DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
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
-- Table structure for table `dashboard_configs`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `dashboard_configs` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `dashboard_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `config_data` json DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dashboard_updates`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `dashboard_updates` (
  `id` int NOT NULL,
  `dashboard_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `update_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `update_data` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `data_ownership_rules`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `data_ownership_rules` (
  `id` int UNSIGNED NOT NULL,
  `role_id` int UNSIGNED NOT NULL,
  `department_code` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `data_category` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'all',
  `access_level` enum('none','read','write','full') COLLATE utf8mb4_general_ci DEFAULT 'none',
  `is_owner` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `data_ownership_rules`
--

INSERT DELAYED IGNORE INTO `data_ownership_rules` (`id`, `role_id`, `department_code`, `data_category`, `access_level`, `is_owner`, `created_at`) VALUES
(1, 1, NULL, 'all', 'full', 1, '2026-06-20 01:28:34'),
(2, 3, NULL, 'all', 'full', 1, '2026-06-20 01:28:34'),
(3, 4, NULL, 'all', 'full', 1, '2026-06-20 01:28:34'),
(4, 1, NULL, 'all', 'full', 1, '2026-06-20 01:41:08'),
(5, 3, NULL, 'all', 'full', 1, '2026-06-20 01:41:08'),
(6, 4, NULL, 'all', 'full', 1, '2026-06-20 01:41:08'),
(7, 1, NULL, 'all', 'full', 1, '2026-06-20 01:45:02'),
(8, 3, NULL, 'all', 'full', 1, '2026-06-20 01:45:02'),
(9, 4, NULL, 'all', 'full', 1, '2026-06-20 01:45:02'),
(10, 1, NULL, 'all', 'full', 1, '2026-06-20 01:46:53'),
(11, 3, NULL, 'all', 'full', 1, '2026-06-20 01:46:53'),
(12, 4, NULL, 'all', 'full', 1, '2026-06-20 01:46:53');

-- --------------------------------------------------------

--
-- Table structure for table `data_sync_status`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `data_sync_status` (
  `id` int UNSIGNED NOT NULL,
  `sync_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `records_processed` int DEFAULT '0',
  `errors` int DEFAULT '0',
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `delegation_records`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `delegation_records` (
  `id` int NOT NULL,
  `delegated_by` int DEFAULT NULL,
  `delegated_to` int DEFAULT NULL,
  `duty_description` text COLLATE utf8mb4_unicode_ci,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departmental_budgets`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `departmental_budgets` (
  `id` int NOT NULL,
  `department_id` int DEFAULT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `allocated_amount` decimal(15,2) DEFAULT '0.00',
  `spent_amount` decimal(15,2) DEFAULT '0.00',
  `remaining_amount` decimal(15,2) DEFAULT '0.00',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `approved_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `departments` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `code` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `hod_id` int UNSIGNED DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `department_reviews`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `department_reviews` (
  `id` int NOT NULL,
  `department` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reviewer_id` int DEFAULT NULL,
  `review_period` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `overall_score` decimal(5,2) DEFAULT NULL,
  `strengths` text COLLATE utf8mb4_general_ci,
  `weaknesses` text COLLATE utf8mb4_general_ci,
  `recommendations` text COLLATE utf8mb4_general_ci,
  `status` enum('draft','submitted','reviewed') COLLATE utf8mb4_general_ci DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `department_targets`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `department_targets` (
  `id` int UNSIGNED NOT NULL,
  `department_code` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `fiscal_year` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `target_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `target_category` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `target_value` decimal(12,2) DEFAULT NULL,
  `actual_value` decimal(12,2) DEFAULT NULL,
  `unit` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `weight_pct` decimal(5,2) DEFAULT '100.00',
  `status` enum('Pending','In Progress','Achieved','Not Met','Delayed') COLLATE utf8mb4_general_ci DEFAULT 'Pending',
  `notes` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dg_read_notifications`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `dg_read_notifications` (
  `id` int NOT NULL,
  `notification_key` varchar(64) COLLATE utf8mb4_general_ci NOT NULL,
  `user_id` int NOT NULL,
  `read_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `director_departments`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `director_departments` (
  `id` int UNSIGNED NOT NULL,
  `role_id` int UNSIGNED NOT NULL,
  `department_code` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `is_primary` tinyint(1) DEFAULT '0',
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `director_news`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `director_news` (
  `id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `content` longtext COLLATE utf8mb4_general_ci,
  `excerpt` text COLLATE utf8mb4_general_ci,
  `featured_image` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `department_id` int DEFAULT NULL,
  `author_id` int DEFAULT NULL,
  `status` enum('draft','published','archived') COLLATE utf8mb4_general_ci DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `director_performance_reviews`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `director_performance_reviews` (
  `id` int UNSIGNED NOT NULL,
  `staff_id` int UNSIGNED NOT NULL,
  `review_period` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `fiscal_year` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `overall_score` decimal(5,2) DEFAULT NULL,
  `targets_met` int DEFAULT '0',
  `targets_total` int DEFAULT '0',
  `summary` text COLLATE utf8mb4_general_ci,
  `recommendations` text COLLATE utf8mb4_general_ci,
  `reviewed_by` int UNSIGNED DEFAULT NULL,
  `status` enum('Draft','Submitted','Approved','Archived') COLLATE utf8mb4_general_ci DEFAULT 'Draft',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disciplinary_actions`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `disciplinary_actions` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `incident_date` date DEFAULT NULL,
  `incident_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `action_taken` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Open',
  `reported_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disciplinary_cases`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `disciplinary_cases` (
  `id` int NOT NULL,
  `case_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `party_id` int DEFAULT NULL,
  `party_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `incident_date` date DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'open',
  `assigned_to` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `disciplinary_records`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `disciplinary_records` (
  `id` int NOT NULL,
  `student_id` int DEFAULT NULL,
  `staff_id` int DEFAULT NULL,
  `incident_date` date DEFAULT NULL,
  `incident_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `action_taken` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'open',
  `reported_by` int DEFAULT NULL,
  `resolved_by` int DEFAULT NULL,
  `resolved_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_generation_log`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `document_generation_log` (
  `id` int NOT NULL,
  `document_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `document_id` int DEFAULT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `generated_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `created_at_ts` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_print_configs`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `document_print_configs` (
  `id` int NOT NULL,
  `document_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paper_size` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'A4',
  `orientation` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'portrait',
  `margin_top` int DEFAULT '20',
  `margin_bottom` int DEFAULT '20',
  `margin_left` int DEFAULT '15',
  `margin_right` int DEFAULT '15',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_settings`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `document_settings` (
  `id` int NOT NULL,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `updated_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_templates`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `document_templates` (
  `id` int NOT NULL,
  `template_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `template_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `template_content` longtext COLLATE utf8mb4_unicode_ci,
  `is_default` tinyint(1) DEFAULT '0',
  `is_deleted` tinyint(1) DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `duty_roster`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `duty_roster` (
  `id` int UNSIGNED NOT NULL,
  `staff_id` int UNSIGNED DEFAULT NULL,
  `duty_date` date DEFAULT NULL,
  `shift` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `department` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `duty_rosters`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `duty_rosters` (
  `id` int UNSIGNED NOT NULL,
  `staff_id` int UNSIGNED DEFAULT NULL,
  `duty_date` date DEFAULT NULL,
  `duty_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `location` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'scheduled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `email_notifications_queue`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `email_notifications_queue` (
  `id` int NOT NULL,
  `recipient_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recipient_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_content` text COLLATE utf8mb4_unicode_ci,
  `email_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `priority` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'normal',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `scheduled_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `emergency_contacts`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `emergency_contacts` (
  `id` int NOT NULL,
  `contact_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `relationship` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_primary` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_secondary` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `staff_id` int DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `priority` int DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employment_contracts`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `employment_contracts` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `contract_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `salary` decimal(15,2) DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `terms` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employment_details`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `employment_details` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `employment_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `department_id` int DEFAULT NULL,
  `position` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salary_grade` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `error_logs`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `error_logs` (
  `id` int UNSIGNED NOT NULL,
  `error_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_general_ci,
  `file` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `line` int DEFAULT NULL,
  `stack_trace` text COLLATE utf8mb4_general_ci,
  `user_id` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `examination_records`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `examination_records` (
  `id` int NOT NULL,
  `exam_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `exam_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `course_code` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `continuous_assessment_marks` decimal(8,2) DEFAULT NULL,
  `final_exam_marks` decimal(8,2) DEFAULT NULL,
  `marks_obtained` decimal(8,2) DEFAULT NULL,
  `total_marks` decimal(8,2) DEFAULT '100.00',
  `grade` varchar(5) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `grade_status` enum('Not Entered','Entered','Approved','Published') COLLATE utf8mb4_general_ci DEFAULT 'Not Entered',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `exams` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `subject_id` int UNSIGNED DEFAULT NULL,
  `class_id` int UNSIGNED DEFAULT NULL,
  `date` date DEFAULT NULL,
  `duration` int DEFAULT '0',
  `total_marks` int DEFAULT '100',
  `passing_marks` int DEFAULT '50',
  `term` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_results`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `exam_results` (
  `id` int UNSIGNED NOT NULL,
  `exam_id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `marks_obtained` decimal(5,2) DEFAULT '0.00',
  `grade` varchar(5) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_schedules`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `exam_schedules` (
  `id` int UNSIGNED NOT NULL,
  `exam_id` int UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `invigilator_id` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenditures`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `expenditures` (
  `id` int NOT NULL,
  `expenditure_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department_id` int DEFAULT NULL,
  `budget_line_id` int DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `expenditure_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `expenses` (
  `id` int UNSIGNED NOT NULL,
  `expense_number` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `category` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `expense_date` date DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `approved_by` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT DELAYED IGNORE INTO `expenses` (`id`, `expense_number`, `category`, `description`, `amount`, `expense_date`, `status`, `approved_by`, `created_at`) VALUES
(1, NULL, 'Supplies', 'Sample Supplies expense', 1155541.00, '2014-01-20', 'approved', NULL, '2026-06-19 23:58:56'),
(2, NULL, 'Equipment', 'Sample Equipment expense', 281885.00, '2022-12-20', 'approved', NULL, '2026-06-19 23:58:56'),
(3, NULL, 'Salaries', 'Sample Salaries expense', 1791389.00, '2017-11-20', 'approved', NULL, '2026-06-19 23:58:56'),
(4, NULL, 'Transport', 'Sample Transport expense', 932502.00, '2019-06-20', 'approved', NULL, '2026-06-19 23:58:56'),
(5, NULL, 'Other', 'Sample Other expense', 1195613.00, '2015-10-20', 'approved', NULL, '2026-06-19 23:58:56'),
(6, NULL, 'Maintenance', 'Sample Maintenance expense', 1799641.00, '2021-08-20', 'approved', NULL, '2026-06-19 23:58:56'),
(7, NULL, 'Other', 'Sample Other expense', 577084.00, '2023-11-20', 'approved', NULL, '2026-06-19 23:58:56'),
(8, NULL, 'Other', 'Sample Other expense', 459948.00, '2015-08-20', 'approved', NULL, '2026-06-19 23:58:56'),
(9, NULL, 'Utilities', 'Sample Utilities expense', 1660252.00, '2013-05-20', 'approved', NULL, '2026-06-19 23:58:56'),
(10, NULL, 'Maintenance', 'Sample Maintenance expense', 1097576.00, '2022-01-20', 'approved', NULL, '2026-06-19 23:58:56'),
(11, NULL, 'Maintenance', 'Sample Maintenance expense', 1769462.00, '2016-02-20', 'approved', NULL, '2026-06-19 23:58:56'),
(12, NULL, 'Other', 'Sample Other expense', 1057051.00, '2012-12-20', 'approved', NULL, '2026-06-19 23:58:56'),
(13, NULL, 'Other', 'Sample Other expense', 99759.00, '2012-05-20', 'approved', NULL, '2026-06-19 23:58:56'),
(14, NULL, 'Supplies', 'Sample Supplies expense', 1509836.00, '2025-01-20', 'approved', NULL, '2026-06-19 23:58:56'),
(15, NULL, 'Equipment', 'Sample Equipment expense', 1842522.00, '2016-10-20', 'approved', NULL, '2026-06-19 23:58:56'),
(16, NULL, 'Other', 'Sample Other expense', 412867.00, '2020-02-20', 'approved', NULL, '2026-06-19 23:58:56'),
(17, NULL, 'Salaries', 'Sample Salaries expense', 349421.00, '2012-06-20', 'approved', NULL, '2026-06-19 23:58:56'),
(18, NULL, 'Maintenance', 'Sample Maintenance expense', 1440233.00, '2016-01-20', 'approved', NULL, '2026-06-19 23:58:56'),
(19, NULL, 'Utilities', 'Sample Utilities expense', 164347.00, '2017-03-20', 'approved', NULL, '2026-06-19 23:58:56'),
(20, NULL, 'Equipment', 'Sample Equipment expense', 585657.00, '2017-02-20', 'approved', NULL, '2026-06-19 23:58:56'),
(21, NULL, 'Equipment', 'Sample Equipment expense', 322309.00, '2015-09-20', 'approved', NULL, '2026-06-19 23:58:56'),
(22, NULL, 'Supplies', 'Sample Supplies expense', 1484606.00, '2020-11-20', 'approved', NULL, '2026-06-19 23:58:56'),
(23, NULL, 'Equipment', 'Sample Equipment expense', 185112.00, '2011-08-20', 'approved', NULL, '2026-06-19 23:58:56'),
(24, NULL, 'Equipment', 'Sample Equipment expense', 286701.00, '2013-05-20', 'approved', NULL, '2026-06-19 23:58:56'),
(25, NULL, 'Maintenance', 'Sample Maintenance expense', 1019441.00, '2020-12-20', 'approved', NULL, '2026-06-19 23:58:56'),
(26, NULL, 'Maintenance', 'Sample Maintenance expense', 778746.00, '2015-06-20', 'approved', NULL, '2026-06-19 23:58:56'),
(27, NULL, 'Other', 'Sample Other expense', 1680279.00, '2025-11-20', 'approved', NULL, '2026-06-19 23:58:56'),
(28, NULL, 'Supplies', 'Sample Supplies expense', 1579464.00, '2018-09-20', 'approved', NULL, '2026-06-19 23:58:56'),
(29, NULL, 'Salaries', 'Sample Salaries expense', 1274586.00, '2022-08-20', 'approved', NULL, '2026-06-19 23:58:56'),
(30, NULL, 'Other', 'Sample Other expense', 172348.00, '2011-07-20', 'approved', NULL, '2026-06-19 23:58:56');

-- --------------------------------------------------------

--
-- Table structure for table `expense_approvals`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `expense_approvals` (
  `id` int NOT NULL,
  `expense_id` int DEFAULT NULL,
  `expense_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `requested_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `facilities`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `facilities` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `capacity` int DEFAULT '0',
  `location` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `facility_bookings`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `facility_bookings` (
  `id` int UNSIGNED NOT NULL,
  `facility_id` int UNSIGNED NOT NULL,
  `booked_by` int UNSIGNED NOT NULL,
  `purpose` text COLLATE utf8mb4_general_ci,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `approved_by` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_accounts`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `fee_accounts` (
  `id` int NOT NULL,
  `student_id` int DEFAULT NULL,
  `fee_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT '0.00',
  `paid` decimal(15,2) DEFAULT '0.00',
  `balance` decimal(15,2) DEFAULT '0.00',
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `semester` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_adjustments`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `fee_adjustments` (
  `id` int NOT NULL,
  `student_id` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `adjustment_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `reason` text COLLATE utf8mb4_general_ci,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `fee_payments`
-- (See below for the actual view)
--
CREATE TABLE `fee_payments` (
`id` int
,`student_id` int
,`fee_account_id` int
,`amount_paid` decimal(12,2)
,`payment_method` enum('Cash','Bank Transfer','Mobile Money','Cheque','Card','Other')
,`receipt_number` varchar(50)
,`status` enum('Pending','Completed','Failed','Reversed')
,`payment_date` date
,`notes` text
,`processed_by` int
,`created_at` timestamp
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `financial_audit_log`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `financial_audit_log` (
  `id` int NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `table_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `performed_by` int DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_messages`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `financial_messages` (
  `id` int NOT NULL,
  `sender_id` int NOT NULL,
  `recipient_id` int NOT NULL,
  `subject` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `message` text COLLATE utf8mb4_general_ci NOT NULL,
  `attachment` varchar(255) COLLATE utf8mb4_general_ci DEFAULT '',
  `is_read` tinyint(1) DEFAULT '0',
  `read_at` datetime DEFAULT NULL,
  `sender_role` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '',
  `recipient_role` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_notices`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `financial_notices` (
  `id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `content` text COLLATE utf8mb4_general_ci NOT NULL,
  `author_id` int NOT NULL,
  `author_role` varchar(50) COLLATE utf8mb4_general_ci DEFAULT '',
  `priority` enum('Low','Normal','High','Urgent') COLLATE utf8mb4_general_ci DEFAULT 'Normal',
  `is_published` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_records`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `financial_records` (
  `id` int NOT NULL,
  `record_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_date` date DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fuel_management`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `fuel_management` (
  `id` int NOT NULL,
  `vehicle_id` int DEFAULT NULL,
  `fuel_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fuel_quantity` decimal(10,2) DEFAULT NULL,
  `cost_per_unit` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(15,2) DEFAULT NULL,
  `fueling_date` date DEFAULT NULL,
  `odometer_reading` int DEFAULT NULL,
  `driver_id` int DEFAULT NULL,
  `station` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `receipt_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `generated_documents`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `generated_documents` (
  `id` int NOT NULL,
  `document_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `staff_id` int DEFAULT NULL,
  `generated_by` int DEFAULT NULL,
  `document_title` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `document_description` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `month` int DEFAULT NULL,
  `year` int DEFAULT NULL,
  `gross_salary` decimal(12,2) DEFAULT '0.00',
  `net_pay` decimal(12,2) DEFAULT '0.00',
  `file_path` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `document_content` longtext COLLATE utf8mb4_general_ci,
  `access_code` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `generation_date` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gpa_settings`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `gpa_settings` (
  `id` int NOT NULL,
  `setting_key` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_general_ci,
  `description` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gpa_settings`
--

INSERT DELAYED IGNORE INTO `gpa_settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES
(1, 'pass_mark', '50', 'Minimum pass percentage', '2026-06-26 06:25:09', '2026-06-26 06:25:09'),
(2, 'distinction_threshold', '80', 'Minimum percentage for Distinction', '2026-06-26 06:25:09', '2026-06-26 06:25:09'),
(3, 'credit_threshold', '60', 'Minimum percentage for Credit', '2026-06-26 06:25:09', '2026-06-26 06:25:09'),
(4, 'supplementary_min', '35', 'Minimum percentage eligible for supplementary exam', '2026-06-26 06:25:09', '2026-06-26 06:25:09'),
(5, 'max_supplementary_grade', 'C', 'Maximum grade after supplementary exam', '2026-06-26 06:25:09', '2026-06-26 06:25:09'),
(6, 'retake_max_attempts', '3', 'Maximum retake attempts allowed', '2026-06-26 06:25:09', '2026-06-26 06:25:09'),
(7, 'academic_probation_cgpa', '1.50', 'CGPA below this triggers academic probation', '2026-06-26 06:25:09', '2026-06-26 06:25:09'),
(8, 'suspension_cgpa', '1.00', 'CGPA below this triggers suspension', '2026-06-26 06:25:09', '2026-06-26 06:25:09'),
(9, 'graduation_min_cgpa', '2.00', 'Minimum CGPA required for graduation', '2026-06-26 06:25:09', '2026-06-26 06:25:09'),
(10, 'grading_system', 'letter', 'Grading system type', '2026-06-26 06:25:09', '2026-06-26 06:25:09');

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `grades` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `subject_id` int UNSIGNED DEFAULT NULL,
  `grade` varchar(5) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `term` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grade_change_history`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `grade_change_history` (
  `id` int NOT NULL,
  `student_id` int DEFAULT NULL,
  `course_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `semester` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `old_grade` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `new_grade` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `old_marks` decimal(5,2) DEFAULT NULL,
  `new_marks` decimal(5,2) DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `changed_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grade_scale`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `grade_scale` (
  `id` int NOT NULL,
  `grade_letter` varchar(5) COLLATE utf8mb4_general_ci NOT NULL,
  `grade_point` decimal(4,2) NOT NULL,
  `min_percentage` decimal(5,2) NOT NULL,
  `max_percentage` decimal(5,2) NOT NULL,
  `description` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grade_scale`
--

INSERT DELAYED IGNORE INTO `grade_scale` (`id`, `grade_letter`, `grade_point`, `min_percentage`, `max_percentage`, `description`, `is_active`, `created_at`) VALUES
(1, 'A', 4.00, 80.00, 100.00, 'Distinction', 1, '2026-06-26 06:25:09'),
(2, 'B', 3.00, 70.00, 79.99, 'Credit', 1, '2026-06-26 06:25:09'),
(3, 'C', 2.00, 60.00, 69.99, 'Credit', 1, '2026-06-26 06:25:09'),
(4, 'D', 1.00, 50.00, 59.99, 'Pass', 1, '2026-06-26 06:25:09'),
(5, 'F', 0.00, 0.00, 49.99, 'Fail', 1, '2026-06-26 06:25:09');

-- --------------------------------------------------------

--
-- Table structure for table `grade_scales`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `grade_scales` (
  `id` int NOT NULL,
  `grade_letter` varchar(5) COLLATE utf8mb4_general_ci NOT NULL,
  `grade_point` decimal(4,2) DEFAULT '0.00',
  `min_percentage` decimal(5,2) DEFAULT '0.00',
  `max_percentage` decimal(5,2) DEFAULT '100.00',
  `status` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grading_approval_workflow`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `grading_approval_workflow` (
  `id` int NOT NULL,
  `student_id` int DEFAULT NULL,
  `exam_id` int DEFAULT NULL,
  `current_stage` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Returned') COLLATE utf8mb4_general_ci DEFAULT 'Pending',
  `comments` text COLLATE utf8mb4_general_ci,
  `reviewed_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grading_notifications`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `grading_notifications` (
  `id` int NOT NULL,
  `student_id` int DEFAULT NULL,
  `course_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `semester` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notification_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_unicode_ci,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `graduation_approvals`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `graduation_approvals` (
  `id` int NOT NULL,
  `graduation_id` int NOT NULL,
  `approval_level` enum('senate','principal','director_general') COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `comments` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `graduation_candidates`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `graduation_candidates` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `program` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cgpa` decimal(4,2) DEFAULT NULL,
  `class_of_award` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `total_credits` int DEFAULT '0',
  `bursar_cleared` tinyint(1) DEFAULT '0',
  `library_cleared` tinyint(1) DEFAULT '0',
  `registrar_cleared` tinyint(1) DEFAULT '0',
  `hod_cleared` tinyint(1) DEFAULT '0',
  `is_eligible` tinyint(1) DEFAULT '0',
  `senate_approved` tinyint(1) DEFAULT '0',
  `senate_approved_at` datetime DEFAULT NULL,
  `principal_approved` tinyint(1) DEFAULT '0',
  `principal_approved_at` datetime DEFAULT NULL,
  `dg_approved` tinyint(1) DEFAULT '0',
  `dg_approved_at` datetime DEFAULT NULL,
  `status` enum('pending','eligible','approved','graduated','deferred') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `graduation_date` date DEFAULT NULL,
  `ceremony_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `health_incidents`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `health_incidents` (
  `id` int NOT NULL,
  `incident_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `staff_id` int DEFAULT NULL,
  `incident_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `symptoms` text COLLATE utf8mb4_unicode_ci,
  `severity` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action_taken` text COLLATE utf8mb4_unicode_ci,
  `treatment_given` text COLLATE utf8mb4_unicode_ci,
  `referred_to` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parent_notified` tinyint(1) DEFAULT '0',
  `follow_up_date` date DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Reported',
  `reported_by` int DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hostel_management`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `hostel_management` (
  `id` int NOT NULL,
  `room_number` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `hostel_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `capacity` int NOT NULL,
  `occupied` int DEFAULT '0',
  `room_type` enum('Single','Double','Dormitory') COLLATE utf8mb4_general_ci NOT NULL,
  `gender` enum('Male','Female','Mixed') COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('Available','Occupied','Under Maintenance') COLLATE utf8mb4_general_ci DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hr_activity_log`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `hr_activity_log` (
  `id` int NOT NULL,
  `staff_id` int DEFAULT NULL,
  `action` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hr_activity_logs`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `hr_activity_logs` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `user_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_role` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entity_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hr_announcements`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `hr_announcements` (
  `id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` text COLLATE utf8mb4_unicode_ci,
  `priority` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'normal',
  `target_audience` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hr_reports`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `hr_reports` (
  `id` int NOT NULL,
  `report_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `report_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `report_data` longtext COLLATE utf8mb4_unicode_ci,
  `generated_by` int DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'generated',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hr_settings`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `hr_settings` (
  `id` int NOT NULL,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `description` text COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hr_users`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `hr_users` (
  `id` int NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `incident_reports`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `incident_reports` (
  `id` int NOT NULL,
  `report_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reported_by` int DEFAULT NULL,
  `incident_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `severity` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'open',
  `resolved_by` int DEFAULT NULL,
  `resolved_date` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `institutional_alerts`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `institutional_alerts` (
  `id` int UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `alert_type` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'info',
  `priority` enum('low','medium','high','critical') COLLATE utf8mb4_general_ci DEFAULT 'medium',
  `department_code` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `source` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_resolved` tinyint(1) DEFAULT '0',
  `resolved_by` int UNSIGNED DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `institutional_alerts`
--

INSERT DELAYED IGNORE INTO `institutional_alerts` (`id`, `title`, `description`, `alert_type`, `priority`, `department_code`, `source`, `is_resolved`, `resolved_by`, `resolved_at`, `created_by`, `created_at`) VALUES
(1, 'Staff Attendance Drop', 'Staff attendance dropped below 80% this week.', 'info', 'high', NULL, NULL, 0, NULL, NULL, NULL, '2026-06-20 01:28:34'),
(2, 'Fee Collection Target', 'Monthly fee collection at 65% of target.', 'info', 'medium', NULL, NULL, 0, NULL, NULL, NULL, '2026-06-20 01:28:34'),
(3, 'Exam Preparation', 'Final exams scheduled in 3 weeks.', 'info', 'low', NULL, NULL, 0, NULL, NULL, NULL, '2026-06-20 01:28:34'),
(4, 'Test Alert', 'Test', 'info', 'low', NULL, NULL, 0, NULL, NULL, NULL, '2026-06-20 01:33:53'),
(5, 'Staff Attendance Drop', 'Staff attendance dropped below 80% this week.', 'info', 'high', NULL, NULL, 0, NULL, NULL, NULL, '2026-06-20 01:37:17'),
(6, 'Fee Collection Target', 'Monthly fee collection at 65% of target.', 'info', 'medium', NULL, NULL, 0, NULL, NULL, NULL, '2026-06-20 01:37:17'),
(7, 'Exam Preparation', 'Final exams scheduled in 3 weeks.', 'info', 'low', NULL, NULL, 0, NULL, NULL, NULL, '2026-06-20 01:37:17'),
(8, 'Staff Attendance Drop', 'Staff attendance dropped below 80% this week.', 'info', 'high', NULL, NULL, 0, NULL, NULL, NULL, '2026-06-20 01:41:08'),
(9, 'Fee Collection Target', 'Monthly fee collection at 65% of target.', 'info', 'medium', NULL, NULL, 0, NULL, NULL, NULL, '2026-06-20 01:41:08'),
(10, 'Exam Preparation', 'Final exams scheduled in 3 weeks.', 'info', 'low', NULL, NULL, 0, NULL, NULL, NULL, '2026-06-20 01:41:08'),
(11, 'Staff Attendance Drop', 'Staff attendance dropped below 80% this week.', 'info', 'high', NULL, NULL, 0, NULL, NULL, NULL, '2026-06-20 01:45:03'),
(12, 'Fee Collection Target', 'Monthly fee collection at 65% of target.', 'info', 'medium', NULL, NULL, 0, NULL, NULL, NULL, '2026-06-20 01:45:03'),
(13, 'Exam Preparation', 'Final exams scheduled in 3 weeks.', 'info', 'low', NULL, NULL, 0, NULL, NULL, NULL, '2026-06-20 01:45:03'),
(14, 'Staff Attendance Drop', 'Staff attendance dropped below 80% this week.', 'info', 'high', NULL, NULL, 0, NULL, NULL, NULL, '2026-06-20 01:46:53'),
(15, 'Fee Collection Target', 'Monthly fee collection at 65% of target.', 'info', 'medium', NULL, NULL, 0, NULL, NULL, NULL, '2026-06-20 01:46:53'),
(16, 'Exam Preparation', 'Final exams scheduled in 3 weeks.', 'info', 'low', NULL, NULL, 0, NULL, NULL, NULL, '2026-06-20 01:46:53');

-- --------------------------------------------------------

--
-- Table structure for table `institutional_risks`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `institutional_risks` (
  `id` int UNSIGNED NOT NULL,
  `risk_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `risk_category` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `likelihood` enum('Rare','Unlikely','Possible','Likely','Almost Certain') COLLATE utf8mb4_general_ci DEFAULT 'Possible',
  `impact` enum('Negligible','Minor','Moderate','Major','Severe') COLLATE utf8mb4_general_ci DEFAULT 'Moderate',
  `risk_score` int DEFAULT '0',
  `mitigation_strategy` text COLLATE utf8mb4_general_ci,
  `contingency_plan` text COLLATE utf8mb4_general_ci,
  `owner` int UNSIGNED DEFAULT NULL,
  `status` enum('Identified','Assessed','Mitigated','Monitoring','Closed') COLLATE utf8mb4_general_ci DEFAULT 'Identified',
  `target_resolution` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `institutional_risks`
--

INSERT DELAYED IGNORE INTO `institutional_risks` (`id`, `risk_name`, `description`, `risk_category`, `likelihood`, `impact`, `risk_score`, `mitigation_strategy`, `contingency_plan`, `owner`, `status`, `target_resolution`, `created_at`, `updated_at`) VALUES
(1, 'Student Enrolment Decline', NULL, 'Operational', 'Possible', 'Major', 12, NULL, NULL, NULL, 'Identified', NULL, '2026-06-20 01:28:34', NULL),
(2, 'Staff Retention', NULL, 'HR', 'Likely', 'Moderate', 12, NULL, NULL, NULL, 'Monitoring', NULL, '2026-06-20 01:28:34', NULL),
(3, 'Budget Shortfall', NULL, 'Financial', 'Possible', 'Major', 12, NULL, NULL, NULL, 'Identified', NULL, '2026-06-20 01:28:34', NULL),
(4, 'Regulatory Non-Compliance', NULL, 'Compliance', 'Unlikely', 'Major', 6, NULL, NULL, NULL, 'Monitoring', NULL, '2026-06-20 01:28:34', NULL),
(5, 'Student Enrolment Decline', NULL, 'Operational', 'Possible', 'Major', 12, NULL, NULL, NULL, 'Identified', NULL, '2026-06-20 01:41:08', NULL),
(6, 'Staff Retention', NULL, 'HR', 'Likely', 'Moderate', 12, NULL, NULL, NULL, 'Monitoring', NULL, '2026-06-20 01:41:08', NULL),
(7, 'Budget Shortfall', NULL, 'Financial', 'Possible', 'Major', 12, NULL, NULL, NULL, 'Identified', NULL, '2026-06-20 01:41:08', NULL),
(8, 'Regulatory Non-Compliance', NULL, 'Compliance', 'Unlikely', 'Major', 6, NULL, NULL, NULL, 'Monitoring', NULL, '2026-06-20 01:41:08', NULL),
(9, 'Student Enrolment Decline', NULL, 'Operational', 'Possible', 'Major', 12, NULL, NULL, NULL, 'Identified', NULL, '2026-06-20 01:45:03', NULL),
(10, 'Staff Retention', NULL, 'HR', 'Likely', 'Moderate', 12, NULL, NULL, NULL, 'Monitoring', NULL, '2026-06-20 01:45:03', NULL),
(11, 'Budget Shortfall', NULL, 'Financial', 'Possible', 'Major', 12, NULL, NULL, NULL, 'Identified', NULL, '2026-06-20 01:45:03', NULL),
(12, 'Regulatory Non-Compliance', NULL, 'Compliance', 'Unlikely', 'Major', 6, NULL, NULL, NULL, 'Monitoring', NULL, '2026-06-20 01:45:03', NULL),
(13, 'Student Enrolment Decline', NULL, 'Operational', 'Possible', 'Major', 12, NULL, NULL, NULL, 'Identified', NULL, '2026-06-20 01:46:53', NULL),
(14, 'Staff Retention', NULL, 'HR', 'Likely', 'Moderate', 12, NULL, NULL, NULL, 'Monitoring', NULL, '2026-06-20 01:46:53', NULL),
(15, 'Budget Shortfall', NULL, 'Financial', 'Possible', 'Major', 12, NULL, NULL, NULL, 'Identified', NULL, '2026-06-20 01:46:53', NULL),
(16, 'Regulatory Non-Compliance', NULL, 'Compliance', 'Unlikely', 'Major', 6, NULL, NULL, NULL, 'Monitoring', NULL, '2026-06-20 01:46:53', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `intakes`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `intakes` (
  `id` int NOT NULL,
  `intake_name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Open',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `interview_scheduling`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `interview_scheduling` (
  `id` int NOT NULL,
  `application_id` int DEFAULT NULL,
  `interview_date` datetime DEFAULT NULL,
  `interviewer_id` int DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'scheduled',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `inventory` (
  `id` int UNSIGNED NOT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `quantity` int DEFAULT '0',
  `unit` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `unit_price` decimal(12,2) DEFAULT '0.00',
  `supplier` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reorder_level` int DEFAULT '0',
  `location` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `inventory_items` (
  `id` int NOT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int DEFAULT '0',
  `unit` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit_cost` decimal(15,2) DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reorder_level` int DEFAULT '0',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'in_stock',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_reports`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `inventory_reports` (
  `id` int UNSIGNED NOT NULL,
  `report_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `generated_by` int UNSIGNED DEFAULT NULL,
  `parameters` text COLLATE utf8mb4_general_ci,
  `file_path` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_transactions`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `inventory_transactions` (
  `id` int NOT NULL,
  `item_id` int DEFAULT NULL,
  `transaction_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int DEFAULT NULL,
  `unit_cost` decimal(15,2) DEFAULT NULL,
  `total_cost` decimal(15,2) DEFAULT NULL,
  `reference_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `performed_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoice_records`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `invoice_records` (
  `id` int NOT NULL,
  `invoice_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `paid_amount` decimal(15,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `it_infrastructure`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `it_infrastructure` (
  `id` int UNSIGNED NOT NULL,
  `asset_name` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `asset_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `model` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `serial_number` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `location` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'operational',
  `purchase_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_applications`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `job_applications` (
  `id` int UNSIGNED NOT NULL,
  `recruitment_id` int UNSIGNED DEFAULT NULL,
  `applicant_name` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cover_letter` text COLLATE utf8mb4_general_ci,
  `resume_path` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `applied_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_offers`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `job_offers` (
  `id` int NOT NULL,
  `application_id` int DEFAULT NULL,
  `staff_id` int DEFAULT NULL,
  `position` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salary` decimal(15,2) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `offered_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_vacancies`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `job_vacancies` (
  `id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department_id` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `requirements` text COLLATE utf8mb4_unicode_ci,
  `salary_range` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'open',
  `posted_date` date DEFAULT NULL,
  `closing_date` date DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_chemical_inventory`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `lab_chemical_inventory` (
  `id` int NOT NULL,
  `chemical_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `chemical_formula` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `unit` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `storage_location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hazard_level` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `reorder_level` decimal(10,2) DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'in_stock',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_equipment_maintenance`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `lab_equipment_maintenance` (
  `id` int NOT NULL,
  `equipment_id` int DEFAULT NULL,
  `maintenance_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `maintenance_date` date DEFAULT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `cost` decimal(15,2) DEFAULT NULL,
  `performed_by` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'completed',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_experiments`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `lab_experiments` (
  `id` int NOT NULL,
  `experiment_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `experiment_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `course_id` int DEFAULT NULL,
  `instructor_id` int DEFAULT NULL,
  `scheduled_date` date DEFAULT NULL,
  `duration_hours` decimal(4,1) DEFAULT NULL,
  `max_students` int DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'scheduled',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_inventory`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `lab_inventory` (
  `id` int NOT NULL,
  `item_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `item_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int DEFAULT '0',
  `unit` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reorder_level` int DEFAULT '0',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'in_stock',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_safety_records`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `lab_safety_records` (
  `id` int NOT NULL,
  `record_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hazard_level` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reported_by` int DEFAULT NULL,
  `action_taken` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'open',
  `inspection_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_skills_sessions`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `lab_skills_sessions` (
  `id` int NOT NULL,
  `session_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `skill_area` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `instructor_id` int DEFAULT NULL,
  `scheduled_date` date DEFAULT NULL,
  `duration_hours` decimal(4,1) DEFAULT NULL,
  `max_participants` int DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'scheduled',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `late_payment_settings`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `late_payment_settings` (
  `id` int NOT NULL,
  `setting_key` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_general_ci,
  `updated_by` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leaves`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `leaves` (
  `s_no` int UNSIGNED NOT NULL,
  `sender_id` int UNSIGNED NOT NULL,
  `leave_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `reason` text COLLATE utf8mb4_general_ci,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `send_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `approved_by` int UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_balance`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `leave_balance` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `leave_type_id` int DEFAULT NULL,
  `year` int DEFAULT NULL,
  `total_days` int DEFAULT '0',
  `used_days` int DEFAULT '0',
  `remaining_days` int DEFAULT '0',
  `balance_days` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_balances`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `leave_balances` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `leave_type_id` int DEFAULT NULL,
  `year` int DEFAULT NULL,
  `total_days` int DEFAULT '0',
  `used_days` int DEFAULT '0',
  `remaining_days` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_requests`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `leave_requests` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `leave_type_id` int DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `reviewed_by` int DEFAULT NULL,
  `approval_date` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leave_types`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `leave_types` (
  `id` int NOT NULL,
  `type_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `leave_type_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `days_per_year` int DEFAULT '0',
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_books`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `library_books` (
  `id` int UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `author` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `isbn` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `publisher` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `quantity` int DEFAULT '1',
  `available` int DEFAULT '1',
  `shelf_location` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_borrowing`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `library_borrowing` (
  `id` int NOT NULL,
  `book_id` int DEFAULT NULL,
  `member_id` int DEFAULT NULL,
  `borrow_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'borrowed',
  `renewal_count` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_digital_resources`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `library_digital_resources` (
  `id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resource_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_size` bigint DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uploaded_by` int DEFAULT NULL,
  `download_count` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `added_date` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_fines`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `library_fines` (
  `id` int UNSIGNED NOT NULL,
  `borrowing_id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `amount` decimal(10,2) DEFAULT '0.00',
  `reason` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'unpaid',
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_management`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `library_management` (
  `id` int NOT NULL,
  `book_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `author` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `isbn` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` int DEFAULT '1',
  `available` int DEFAULT '1',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Available',
  `location` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_members`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `library_members` (
  `id` int NOT NULL,
  `member_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `staff_id` int DEFAULT NULL,
  `member_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Student',
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `registration_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_transactions`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `library_transactions` (
  `id` int NOT NULL,
  `book_id` int DEFAULT NULL,
  `member_id` int DEFAULT NULL,
  `borrow_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'borrowed',
  `fine_amount` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `meal_tracking`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `meal_tracking` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED DEFAULT NULL,
  `meal_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `meal_date` date DEFAULT NULL,
  `meal_time` time DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'served',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicine_stock`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
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

INSERT DELAYED IGNORE INTO `medicine_stock` (`id`, `medicine_code`, `medicine_name`, `generic_name`, `category`, `dosage_form`, `strength`, `manufacturer`, `supplier`, `quantity_in_stock`, `unit`, `reorder_level`, `unit_cost`, `selling_price`, `currency`, `batch_number`, `expiry_date`, `storage_location`, `requires_prescription`, `instructions`, `side_effects`, `status`, `last_restocked`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'PARA001', 'Paracetamol', 'Acetaminophen', 'Painkiller', 'Tablet', '500mg', NULL, NULL, 200, 'tablets', 50, 50.00, NULL, 'UGX', NULL, '2027-12-31', 'Cabinet A1', 0, '1-2 tablets every 4-6 hours as needed for pain/fever', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(2, 'IBU001', 'Ibuprofen', 'Ibuprofen', 'Anti-inflammatory', 'Tablet', '400mg', NULL, NULL, 150, 'tablets', 30, 100.00, NULL, 'UGX', NULL, '2027-10-31', 'Cabinet A1', 0, '1 tablet 3 times daily after meals', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(3, 'AMOX001', 'Amoxicillin', 'Amoxicillin', 'Antibiotic', 'Capsule', '500mg', NULL, NULL, 100, 'capsules', 20, 200.00, NULL, 'UGX', NULL, '2027-08-31', 'Cabinet B1', 1, '1 capsule 3 times daily for 7 days', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(4, 'CTM001', 'Chlorpheniramine', 'Chlorpheniramine Maleate', 'Allergy', 'Tablet', '4mg', NULL, NULL, 100, 'tablets', 20, 50.00, NULL, 'UGX', NULL, '2027-11-30', 'Cabinet A2', 0, '1 tablet every 4-6 hours for allergies', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(5, 'ORS001', 'Oral Rehydration Salts', 'ORS', 'Other', 'Powder', '20.5g/sachet', NULL, NULL, 100, 'sachets', 30, 500.00, NULL, 'UGX', NULL, '2028-06-30', 'Cabinet C1', 0, 'Dissolve 1 sachet in 1L water, drink after each loose stool', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(6, 'ART001', 'Artemether/Lumefantrine', 'Coartem', 'Antimalarial', 'Tablet', '20/120mg', NULL, NULL, 60, 'tablets', 20, 1500.00, NULL, 'UGX', NULL, '2027-09-30', 'Cabinet B2', 1, '4 tablets twice daily for 3 days', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(7, 'VITC001', 'Vitamin C', 'Ascorbic Acid', 'Vitamins', 'Tablet', '500mg', NULL, NULL, 300, 'tablets', 50, 30.00, NULL, 'UGX', NULL, '2028-12-31', 'Cabinet C1', 0, '1 tablet daily for immune support', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(8, 'MET001', 'Metered Dose Inhaler', 'Salbutamol', 'Respiratory', 'Inhaler', '100mcg/dose', NULL, NULL, 10, 'inhalers', 3, 15000.00, NULL, 'UGX', NULL, '2027-06-30', 'Cabinet A3', 1, '1-2 puffs as needed for asthma symptoms', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(9, 'ANT001', 'Antacid', 'Aluminum/Magnesium Hydroxide', 'Digestive', 'Tablet', '500mg', NULL, NULL, 200, 'tablets', 40, 100.00, NULL, 'UGX', NULL, '2027-11-30', 'Cabinet C1', 0, '1-2 tablets after meals or when symptomatic', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(10, 'HYD001', 'Hydrocortisone Cream', 'Hydrocortisone', 'Dermatological', 'Cream', '1%', NULL, NULL, 20, 'tubes', 5, 5000.00, NULL, 'UGX', NULL, '2027-08-31', 'Cabinet D1', 0, 'Apply thin layer to affected area 2-3 times daily', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(11, 'DIA001', 'Diazepam', 'Diazepam', 'Painkiller', 'Tablet', '5mg', NULL, NULL, 30, 'tablets', 10, 200.00, NULL, 'UGX', NULL, '2026-12-31', 'Cabinet B2', 1, '1 tablet at bedtime for anxiety or muscle spasms', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(12, 'BAN001', 'Bandages', 'Cotton Bandage', 'First Aid', 'Other', '4 inches x 5 meters', NULL, NULL, 50, 'rolls', 10, 1500.00, NULL, 'UGX', NULL, '2029-12-31', 'Shelf E1', 0, 'For wound dressing and injury management', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(13, 'GAU001', 'Gauze Swabs', 'Sterile Gauze', 'First Aid', 'Other', '10x10cm', NULL, NULL, 200, 'packs', 50, 800.00, NULL, 'UGX', NULL, '2029-12-31', 'Shelf E1', 0, 'Sterile swabs for wound cleaning and dressing', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(14, 'GLU001', 'Glucose Powder', 'Dextrose', 'Vitamins', 'Powder', '500g', NULL, NULL, 10, 'packs', 3, 5000.00, NULL, 'UGX', NULL, '2028-06-30', 'Cabinet C1', 0, 'Mix 2 tablespoons in water for energy', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(15, 'ALC001', 'Alcohol Swabs', 'Isopropyl Alcohol', 'First Aid', 'Solution', '70%', NULL, NULL, 300, 'swabs', 50, 100.00, NULL, 'UGX', NULL, '2028-12-31', 'Shelf E1', 0, 'Use for cleaning skin before injections', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(16, 'CLO001', 'Chloroquine', 'Chloroquine Phosphate', 'Antimalarial', 'Tablet', '250mg', NULL, NULL, 50, 'tablets', 15, 300.00, NULL, 'UGX', NULL, '2027-05-31', 'Cabinet B2', 1, 'As prescribed for malaria treatment', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(17, 'MEF001', 'Mefenamic Acid', 'Mefenamic Acid', 'Painkiller', 'Capsule', '500mg', NULL, NULL, 80, 'capsules', 20, 200.00, NULL, 'UGX', NULL, '2027-07-31', 'Cabinet A1', 0, '1 capsule 3 times daily for pain and inflammation', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(18, 'METR001', 'Metronidazole', 'Metronidazole', 'Antibiotic', 'Tablet', '400mg', NULL, NULL, 100, 'tablets', 20, 150.00, NULL, 'UGX', NULL, '2027-09-30', 'Cabinet B1', 1, '1 tablet 3 times daily for 5-7 days', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(19, 'DIC001', 'Diclofenac Gel', 'Diclofenac Diethylamine', 'Anti-inflammatory', 'Cream', '1%', NULL, NULL, 15, 'tubes', 5, 7000.00, NULL, 'UGX', NULL, '2027-10-31', 'Cabinet D1', 0, 'Apply to affected area 3-4 times daily', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(20, 'CET001', 'Cetirizine', 'Cetirizine Hydrochloride', 'Allergy', 'Tablet', '10mg', NULL, NULL, 100, 'tablets', 20, 100.00, NULL, 'UGX', NULL, '2027-12-31', 'Cabinet A2', 0, '1 tablet daily for allergy symptoms', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(21, 'ASP001', 'Aspirin', 'Acetylsalicylic Acid', 'Painkiller', 'Tablet', '300mg', NULL, NULL, 100, 'tablets', 25, 50.00, NULL, 'UGX', NULL, '2027-06-30', 'Cabinet A1', 0, '1-2 tablets every 4-6 hours for pain/fever', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(22, 'ZIN001', 'Zinc Tablets', 'Zinc Sulfate', 'Vitamins', 'Tablet', '20mg', NULL, NULL, 150, 'tablets', 30, 100.00, NULL, 'UGX', NULL, '2028-09-30', 'Cabinet C1', 0, '1 tablet daily for immune support and wound healing', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(23, 'CLOT001', 'Clotrimazole Cream', 'Clotrimazole', 'Antifungal', 'Cream', '1%', NULL, NULL, 15, 'tubes', 5, 4000.00, NULL, 'UGX', NULL, '2027-08-31', 'Cabinet D1', 0, 'Apply to affected area twice daily for 2 weeks', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(24, 'EYE001', 'Eye Drops', 'Chloramphenicol', 'Other', 'Drops', '0.5%', NULL, NULL, 20, 'bottles', 5, 5000.00, NULL, 'UGX', NULL, '2027-04-30', 'Cabinet A3', 1, '1-2 drops in affected eye every 2-4 hours', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(25, 'BET001', 'Betadine Solution', 'Povidone-Iodine', 'First Aid', 'Solution', '10%', NULL, NULL, 10, 'bottles', 3, 8000.00, NULL, 'UGX', NULL, '2028-03-31', 'Shelf E1', 0, 'Apply to wounds for disinfection', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44');

-- --------------------------------------------------------

--
-- Table structure for table `medicine_stock_transactions`
--
-- Creation: Jun 28, 2026 at 04:21 AM
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
-- Table structure for table `midwifery_antenatal_care`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `midwifery_antenatal_care` (
  `id` int NOT NULL,
  `student_id` int DEFAULT NULL,
  `patient_id` int DEFAULT NULL,
  `visit_date` date DEFAULT NULL,
  `gestational_age` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `blood_pressure` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `fundal_height` decimal(5,1) DEFAULT NULL,
  `fetal_heart_rate` int DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `assessor_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `midwifery_family_planning`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `midwifery_family_planning` (
  `id` int NOT NULL,
  `student_id` int DEFAULT NULL,
  `patient_id` int DEFAULT NULL,
  `method` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `counseling_date` date DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `assessor_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `midwifery_labor_delivery`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `midwifery_labor_delivery` (
  `id` int NOT NULL,
  `student_id` int DEFAULT NULL,
  `patient_id` int DEFAULT NULL,
  `delivery_date` datetime DEFAULT NULL,
  `delivery_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `baby_weight` decimal(5,2) DEFAULT NULL,
  `apgar_score` int DEFAULT NULL,
  `complications` text COLLATE utf8mb4_unicode_ci,
  `outcome` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `assessor_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `midwifery_postnatal_care`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `midwifery_postnatal_care` (
  `id` int NOT NULL,
  `student_id` int DEFAULT NULL,
  `patient_id` int DEFAULT NULL,
  `visit_date` date DEFAULT NULL,
  `days_postpartum` int DEFAULT NULL,
  `maternal_condition` text COLLATE utf8mb4_unicode_ci,
  `baby_condition` text COLLATE utf8mb4_unicode_ci,
  `breastfeeding_status` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `assessor_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `midwifery_students`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `midwifery_students` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `program` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cohort` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clinical_hours` int DEFAULT '0',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `national_exam_results`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `national_exam_results` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `exam_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `exam_year` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `subject` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `grade` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `national_exam_number` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `certificate_number` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `news_images`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `news_images` (
  `id` int NOT NULL,
  `news_id` int DEFAULT NULL,
  `image_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_caption` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `is_primary` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `news_subscribers`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `news_subscribers` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `user_type` enum('staff','student') COLLATE utf8mb4_unicode_ci NOT NULL,
  `subscribed` tinyint(1) DEFAULT '1',
  `subscribed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `news_views`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `news_views` (
  `id` int NOT NULL,
  `news_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `user_type` enum('staff','student','public') COLLATE utf8mb4_unicode_ci DEFAULT 'public',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int UNSIGNED NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `message` text COLLATE utf8mb4_general_ci,
  `type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'info',
  `priority` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'normal',
  `audience` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'all',
  `created_by` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_reads`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `notification_reads` (
  `id` int UNSIGNED NOT NULL,
  `notification_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nursing_clinical_logbook`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `nursing_clinical_logbook` (
  `id` int NOT NULL,
  `student_id` int DEFAULT NULL,
  `placement_id` int DEFAULT NULL,
  `log_date` date DEFAULT NULL,
  `shift_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hours` decimal(4,1) DEFAULT NULL,
  `activities` text COLLATE utf8mb4_unicode_ci,
  `supervisor_signature` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nursing_clinical_placements`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `nursing_clinical_placements` (
  `id` int NOT NULL,
  `student_id` int DEFAULT NULL,
  `facility_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `supervisor_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nursing_practical_assessment`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `nursing_practical_assessment` (
  `id` int NOT NULL,
  `student_id` int DEFAULT NULL,
  `assessment_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `skill_area` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `max_score` decimal(5,2) DEFAULT NULL,
  `assessor_id` int DEFAULT NULL,
  `assessment_date` date DEFAULT NULL,
  `comments` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'completed',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nursing_skills_training`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `nursing_skills_training` (
  `id` int NOT NULL,
  `skill_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `skill_category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `duration_hours` decimal(5,1) DEFAULT NULL,
  `max_participants` int DEFAULT NULL,
  `instructor_id` int DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'scheduled',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `nursing_students`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `nursing_students` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `program` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cohort` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `clinical_hours` int DEFAULT '0',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `official_duties`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `official_duties` (
  `id` int NOT NULL,
  `role_id` int DEFAULT NULL,
  `duty_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `duty_description` text COLLATE utf8mb4_unicode_ci,
  `duty_icon` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `onboarding_checklist`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `onboarding_checklist` (
  `id` int UNSIGNED NOT NULL,
  `staff_id` int UNSIGNED DEFAULT NULL,
  `task_name` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `assigned_to` int UNSIGNED DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `partnerships`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `partnerships` (
  `id` int UNSIGNED NOT NULL,
  `partner_name` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `partner_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `contact_person` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `mou_file` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'active',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `partner_schools`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `partner_schools` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `contact_person` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_general_ci,
  `partnership_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `payments` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED DEFAULT NULL,
  `amount_received` decimal(12,2) NOT NULL DEFAULT '0.00',
  `amount_paid` decimal(12,2) DEFAULT '0.00',
  `payment_method` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `reference` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT DELAYED IGNORE INTO `payments` (`id`, `student_id`, `amount_received`, `amount_paid`, `payment_method`, `payment_date`, `status`, `reference`, `created_at`) VALUES
(1, 1, 4303623.00, 0.00, 'Cheque', '2026-04-01', 'verified', NULL, '2026-06-19 23:58:56'),
(2, 1, 1154598.00, 0.00, 'Mobile Money', '2026-01-13', 'verified', NULL, '2026-06-19 23:58:56'),
(3, 1, 2373654.00, 0.00, 'POS', '2026-02-04', 'pending', NULL, '2026-06-19 23:58:56'),
(4, 1, 903361.00, 0.00, 'Bank Transfer', '2026-02-03', 'pending', NULL, '2026-06-19 23:58:56'),
(5, 1, 516178.00, 0.00, 'Mobile Money', '2026-04-15', 'approved', NULL, '2026-06-19 23:58:56'),
(6, 1, 3369769.00, 0.00, 'Bank Transfer', '2026-04-06', 'approved', NULL, '2026-06-19 23:58:56'),
(7, 1, 1195561.00, 0.00, 'Bank Transfer', '2026-02-28', 'verified', NULL, '2026-06-19 23:58:56'),
(8, 1, 2818435.00, 0.00, 'Bank Transfer', '2026-04-03', 'approved', NULL, '2026-06-19 23:58:56'),
(9, 1, 1694306.00, 0.00, 'POS', '2026-05-28', 'verified', NULL, '2026-06-19 23:58:56'),
(10, 1, 1310012.00, 0.00, 'Bank Transfer', '2026-05-23', 'pending', NULL, '2026-06-19 23:58:56'),
(11, 2, 4079351.00, 0.00, 'Cheque', '2026-01-18', 'approved', NULL, '2026-06-19 23:58:56'),
(12, 2, 3786321.00, 0.00, 'Mobile Money', '2026-05-14', 'approved', NULL, '2026-06-19 23:58:56'),
(13, 2, 4845372.00, 0.00, 'Cheque', '2026-06-12', 'verified', NULL, '2026-06-19 23:58:56'),
(14, 2, 2205793.00, 0.00, 'Cheque', '2026-02-07', 'verified', NULL, '2026-06-19 23:58:56'),
(15, 2, 3532582.00, 0.00, 'Cheque', '2026-02-11', 'pending', NULL, '2026-06-19 23:58:56'),
(16, 2, 4559246.00, 0.00, 'POS', '2026-01-07', 'pending', NULL, '2026-06-19 23:58:56'),
(17, 2, 1664302.00, 0.00, 'Bank Transfer', '2026-02-24', 'pending', NULL, '2026-06-19 23:58:56'),
(18, 2, 231198.00, 0.00, 'Cash', '2025-12-28', 'approved', NULL, '2026-06-19 23:58:56'),
(19, 2, 371793.00, 0.00, 'Mobile Money', '2025-12-30', 'pending', NULL, '2026-06-19 23:58:56'),
(20, 2, 4921083.00, 0.00, 'Bank Transfer', '2026-03-18', 'pending', NULL, '2026-06-19 23:58:56'),
(21, 3, 1347820.00, 0.00, 'Cheque', '2026-06-13', 'pending', NULL, '2026-06-19 23:58:56'),
(22, 3, 679021.00, 0.00, 'Mobile Money', '2026-03-04', 'approved', NULL, '2026-06-19 23:58:56'),
(23, 3, 841699.00, 0.00, 'Cash', '2025-12-25', 'pending', NULL, '2026-06-19 23:58:56'),
(24, 3, 2118353.00, 0.00, 'Cash', '2026-05-22', 'verified', NULL, '2026-06-19 23:58:56'),
(25, 3, 1529731.00, 0.00, 'Bank Transfer', '2026-01-03', 'verified', NULL, '2026-06-19 23:58:56'),
(26, 3, 150061.00, 0.00, 'Cash', '2026-05-06', 'approved', NULL, '2026-06-19 23:58:56'),
(27, 3, 2099931.00, 0.00, 'Mobile Money', '2026-01-17', 'approved', NULL, '2026-06-19 23:58:56'),
(28, 3, 3984452.00, 0.00, 'Mobile Money', '2026-04-29', 'verified', NULL, '2026-06-19 23:58:56'),
(29, 3, 1757402.00, 0.00, 'Bank Transfer', '2026-01-08', 'pending', NULL, '2026-06-19 23:58:56'),
(30, 3, 2363593.00, 0.00, 'Cash', '2026-04-15', 'pending', NULL, '2026-06-19 23:58:56'),
(31, 4, 4897316.00, 0.00, 'Cash', '2026-06-06', 'approved', NULL, '2026-06-19 23:58:56'),
(32, 4, 4530396.00, 0.00, 'POS', '2026-03-04', 'approved', NULL, '2026-06-19 23:58:56'),
(33, 4, 2981352.00, 0.00, 'Bank Transfer', '2026-01-17', 'pending', NULL, '2026-06-19 23:58:56'),
(34, 4, 1748722.00, 0.00, 'Bank Transfer', '2026-06-14', 'pending', NULL, '2026-06-19 23:58:56'),
(35, 4, 231509.00, 0.00, 'Cheque', '2026-01-22', 'pending', NULL, '2026-06-19 23:58:56'),
(36, 4, 306115.00, 0.00, 'Cash', '2026-01-13', 'approved', NULL, '2026-06-19 23:58:56'),
(37, 4, 4653839.00, 0.00, 'Cheque', '2026-04-17', 'pending', NULL, '2026-06-19 23:58:56'),
(38, 4, 3217739.00, 0.00, 'Mobile Money', '2026-04-10', 'approved', NULL, '2026-06-19 23:58:56'),
(39, 4, 1228940.00, 0.00, 'Mobile Money', '2026-05-09', 'pending', NULL, '2026-06-19 23:58:56'),
(40, 4, 1651005.00, 0.00, 'Cheque', '2026-01-06', 'approved', NULL, '2026-06-19 23:58:56'),
(41, 5, 4721389.00, 0.00, 'POS', '2026-02-09', 'approved', NULL, '2026-06-19 23:58:56'),
(42, 5, 149174.00, 0.00, 'POS', '2026-03-09', 'approved', NULL, '2026-06-19 23:58:56'),
(43, 5, 617859.00, 0.00, 'Mobile Money', '2025-12-25', 'approved', NULL, '2026-06-19 23:58:56'),
(44, 5, 3024579.00, 0.00, 'POS', '2025-12-30', 'approved', NULL, '2026-06-19 23:58:56'),
(45, 5, 4439374.00, 0.00, 'Cheque', '2026-05-05', 'verified', NULL, '2026-06-19 23:58:56'),
(46, 5, 333072.00, 0.00, 'Mobile Money', '2026-05-04', 'pending', NULL, '2026-06-19 23:58:56'),
(47, 5, 3767992.00, 0.00, 'Cash', '2026-06-20', 'pending', NULL, '2026-06-19 23:58:56'),
(48, 5, 189456.00, 0.00, 'Cheque', '2026-06-15', 'verified', NULL, '2026-06-19 23:58:56'),
(49, 5, 3666993.00, 0.00, 'Cash', '2026-04-25', 'approved', NULL, '2026-06-19 23:58:56'),
(50, 5, 4837535.00, 0.00, 'POS', '2026-03-31', 'approved', NULL, '2026-06-19 23:58:56');

-- --------------------------------------------------------

--
-- Table structure for table `payment_approvals`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `payment_approvals` (
  `id` int NOT NULL,
  `payment_id` int NOT NULL,
  `payment_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'fee_payment',
  `requested_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `approval_status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `approval_remarks` text COLLATE utf8mb4_general_ci,
  `approved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_methods`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `payment_methods` (
  `id` int NOT NULL,
  `method_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `method_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_records`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `payment_records` (
  `id` int NOT NULL,
  `student_id` int DEFAULT NULL,
  `fee_account_id` int DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'Completed',
  `processed_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_routes`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `payment_routes` (
  `id` int NOT NULL,
  `route_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `route_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_subscriptions`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `payment_subscriptions` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `subscription_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `frequency` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'monthly',
  `next_due_date` date DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_allowances`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `payroll_allowances` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `allowance_type` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `amount` decimal(12,2) DEFAULT '0.00',
  `month` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `is_recurring` tinyint(1) DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_allowance_types`
--
-- Creation: Jun 28, 2026 at 05:57 AM
-- Last update: Jun 28, 2026 at 07:11 AM
--

CREATE TABLE IF NOT EXISTS `payroll_allowance_types` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_allowance_types`
--

INSERT DELAYED IGNORE INTO `payroll_allowance_types` (`id`, `name`, `description`, `is_active`, `created_at`) VALUES
(1, 'Transportation Allowance', 'Monthly transportation allowance', 1, '2026-06-28 05:57:40'),
(2, 'Meal Allowance', 'Monthly meal allowance', 1, '2026-06-28 05:57:40'),
(3, 'Housing Allowance', 'Monthly housing allowance', 1, '2026-06-28 05:57:40'),
(4, 'Clothing Allowance', 'Annual clothing allowance', 1, '2026-06-28 05:57:40'),
(5, 'Rice Subsidy', 'Monthly rice subsidy', 1, '2026-06-28 05:57:40'),
(6, 'Transportation Allowance', 'Monthly transportation allowance', 1, '2026-06-28 07:11:22'),
(7, 'Meal Allowance', 'Monthly meal allowance', 1, '2026-06-28 07:11:22'),
(8, 'Housing Allowance', 'Monthly housing allowance', 1, '2026-06-28 07:11:22'),
(9, 'Clothing Allowance', 'Annual clothing allowance', 1, '2026-06-28 07:11:22'),
(10, 'Rice Subsidy', 'Monthly rice subsidy', 1, '2026-06-28 07:11:22');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_approvals`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `payroll_approvals` (
  `id` int NOT NULL,
  `payroll_run_id` int NOT NULL,
  `level` enum('HR','PayrollOfficer','Bursar','DirectorFinance') COLLATE utf8mb4_general_ci NOT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `comments` text COLLATE utf8mb4_general_ci,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_bonuses`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `payroll_bonuses` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `bonus_type` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `amount` decimal(12,2) DEFAULT '0.00',
  `month` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_deductions`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `payroll_deductions` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `deduction_type` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `amount` decimal(12,2) DEFAULT '0.00',
  `month` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `is_recurring` tinyint(1) DEFAULT '0',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_deduction_types`
--
-- Creation: Jun 28, 2026 at 05:57 AM
-- Last update: Jun 28, 2026 at 07:11 AM
--

CREATE TABLE IF NOT EXISTS `payroll_deduction_types` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_deduction_types`
--

INSERT DELAYED IGNORE INTO `payroll_deduction_types` (`id`, `name`, `description`, `is_active`, `created_at`) VALUES
(1, 'SSS Contribution', 'Social Security System contribution', 1, '2026-06-28 05:57:40'),
(2, 'PhilHealth', 'Philippine Health Insurance Corporation contribution', 1, '2026-06-28 05:57:40'),
(3, 'Pag-IBIG', 'Home Development Mutual Fund contribution', 1, '2026-06-28 05:57:40'),
(4, 'Withholding Tax', 'Income tax withholding', 1, '2026-06-28 05:57:40'),
(5, 'Loan Payment', 'Salary loan deduction', 1, '2026-06-28 05:57:40'),
(6, 'SSS Contribution', 'Social Security System contribution', 1, '2026-06-28 07:11:22'),
(7, 'PhilHealth', 'Philippine Health Insurance Corporation contribution', 1, '2026-06-28 07:11:22'),
(8, 'Pag-IBIG', 'Home Development Mutual Fund contribution', 1, '2026-06-28 07:11:22'),
(9, 'Withholding Tax', 'Income tax withholding', 1, '2026-06-28 07:11:22'),
(10, 'Loan Payment', 'Salary loan deduction', 1, '2026-06-28 07:11:22');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_details`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `payroll_details` (
  `id` int NOT NULL,
  `payroll_run_id` int NOT NULL,
  `staff_id` int NOT NULL,
  `basic_salary` decimal(12,2) DEFAULT '0.00',
  `total_allowances` decimal(12,2) DEFAULT '0.00',
  `overtime_pay` decimal(12,2) DEFAULT '0.00',
  `bonuses` decimal(12,2) DEFAULT '0.00',
  `gross_pay` decimal(12,2) DEFAULT '0.00',
  `paye_tax` decimal(12,2) DEFAULT '0.00',
  `nssf_employee` decimal(12,2) DEFAULT '0.00',
  `nssf_employer` decimal(12,2) DEFAULT '0.00',
  `other_deductions` decimal(12,2) DEFAULT '0.00',
  `leave_deductions` decimal(12,2) DEFAULT '0.00',
  `net_pay` decimal(12,2) DEFAULT '0.00',
  `paid_leave_days` decimal(5,1) DEFAULT '0.0',
  `unpaid_leave_days` decimal(5,1) DEFAULT '0.0',
  `payment_method` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `payment_reference` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `payment_status` enum('pending','paid','failed') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_employees`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `payroll_employees` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `bank_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_account` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bank_code` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tax_identification` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nssf_number` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `salary_type` enum('monthly','annual') COLLATE utf8mb4_general_ci DEFAULT 'monthly',
  `salary_grade` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `basic_salary` decimal(12,2) DEFAULT '0.00',
  `hire_date` date DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_employee_allowances`
--
-- Creation: Jun 28, 2026 at 05:57 AM
--

CREATE TABLE IF NOT EXISTS `payroll_employee_allowances` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `allowance_type_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `month` int NOT NULL,
  `year` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_employee_deductions`
--
-- Creation: Jun 28, 2026 at 05:57 AM
--

CREATE TABLE IF NOT EXISTS `payroll_employee_deductions` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `deduction_type_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `month` int NOT NULL,
  `year` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_items`
--
-- Creation: Jun 28, 2026 at 05:57 AM
--

CREATE TABLE IF NOT EXISTS `payroll_items` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `period_id` int NOT NULL,
  `basic_salary` decimal(10,2) NOT NULL DEFAULT '0.00',
  `allowances` decimal(10,2) NOT NULL DEFAULT '0.00',
  `deductions` decimal(10,2) NOT NULL DEFAULT '0.00',
  `net_pay` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_loans`
--
-- Creation: Jun 28, 2026 at 05:57 AM
--

CREATE TABLE IF NOT EXISTS `payroll_loans` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `monthly_deduction` decimal(10,2) NOT NULL DEFAULT '0.00',
  `remaining_balance` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` enum('pending','active','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_overtime`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `payroll_overtime` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `hours` decimal(8,2) DEFAULT '0.00',
  `rate` decimal(10,2) DEFAULT '0.00',
  `total_pay` decimal(12,2) DEFAULT '0.00',
  `month` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_payments`
--
-- Creation: Jun 28, 2026 at 05:57 AM
--

CREATE TABLE IF NOT EXISTS `payroll_payments` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `payroll_run_id` int DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payment_method` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'bank_transfer',
  `payment_date` date NOT NULL,
  `reference_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_payslips`
--
-- Creation: Jun 28, 2026 at 05:57 AM
--

CREATE TABLE IF NOT EXISTS `payroll_payslips` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `period_id` int NOT NULL,
  `basic_salary` decimal(10,2) NOT NULL DEFAULT '0.00',
  `allowances_total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `deductions_total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `net_pay` decimal(10,2) NOT NULL DEFAULT '0.00',
  `generated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_periods`
--
-- Creation: Jun 28, 2026 at 05:57 AM
--

CREATE TABLE IF NOT EXISTS `payroll_periods` (
  `id` int NOT NULL,
  `month` int NOT NULL,
  `year` int NOT NULL,
  `status` enum('draft','open','processing','closed','paid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_records`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `payroll_records` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `month` int NOT NULL,
  `year` int NOT NULL,
  `gross_salary` decimal(12,2) DEFAULT '0.00',
  `total_allowances` decimal(12,2) DEFAULT '0.00',
  `total_deductions` decimal(12,2) DEFAULT '0.00',
  `nssf_tax` decimal(12,2) DEFAULT '0.00',
  `paye_tax` decimal(12,2) DEFAULT '0.00',
  `net_salary` decimal(12,2) DEFAULT '0.00',
  `total_fees_collected` decimal(12,2) DEFAULT '0.00',
  `net_payment` decimal(12,2) DEFAULT '0.00',
  `processed_by` int DEFAULT '0',
  `processing_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `status` enum('Draft','Processed','Approved','Paid') COLLATE utf8mb4_general_ci DEFAULT 'Draft',
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payroll_records`
--

INSERT DELAYED IGNORE INTO `payroll_records` (`id`, `staff_id`, `month`, `year`, `gross_salary`, `total_allowances`, `total_deductions`, `nssf_tax`, `paye_tax`, `net_salary`, `total_fees_collected`, `net_payment`, `processed_by`, `processing_date`, `status`, `approved_by`, `approved_at`) VALUES
(1, 1, 6, 2026, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 25, '2026-06-25 00:34:35', 'Processed', NULL, NULL),
(2, 2, 6, 2026, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 25, '2026-06-25 00:34:35', 'Processed', NULL, NULL),
(3, 3, 6, 2026, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 25, '2026-06-25 00:34:35', 'Processed', NULL, NULL),
(4, 4, 6, 2026, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 25, '2026-06-25 00:34:35', 'Processed', NULL, NULL),
(5, 5, 6, 2026, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 25, '2026-06-25 00:34:35', 'Processed', NULL, NULL),
(6, 6, 6, 2026, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 25, '2026-06-25 00:34:35', 'Processed', NULL, NULL),
(7, 7, 6, 2026, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 25, '2026-06-25 00:34:35', 'Processed', NULL, NULL),
(8, 8, 6, 2026, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 25, '2026-06-25 00:34:35', 'Processed', NULL, NULL),
(9, 9, 6, 2026, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 25, '2026-06-25 00:34:35', 'Processed', NULL, NULL),
(10, 10, 6, 2026, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 25, '2026-06-25 00:34:35', 'Processed', NULL, NULL),
(11, 11, 6, 2026, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 25, '2026-06-25 00:34:35', 'Processed', NULL, NULL),
(12, 12, 6, 2026, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 25, '2026-06-25 00:34:35', 'Processed', NULL, NULL),
(13, 13, 6, 2026, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 25, '2026-06-25 00:34:35', 'Processed', NULL, NULL),
(14, 14, 6, 2026, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 25, '2026-06-25 00:34:35', 'Processed', NULL, NULL),
(15, 15, 6, 2026, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 25, '2026-06-25 00:34:35', 'Processed', NULL, NULL),
(16, 16, 6, 2026, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 25, '2026-06-25 00:34:35', 'Processed', NULL, NULL),
(17, 17, 6, 2026, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 25, '2026-06-25 00:34:35', 'Processed', NULL, NULL),
(18, 18, 6, 2026, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 25, '2026-06-25 00:34:35', 'Processed', NULL, NULL),
(19, 19, 6, 2026, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 25, '2026-06-25 00:34:35', 'Processed', NULL, NULL),
(20, 20, 6, 2026, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 25, '2026-06-25 00:34:35', 'Processed', NULL, NULL),
(21, 21, 6, 2026, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 25, '2026-06-25 00:34:35', 'Processed', NULL, NULL),
(22, 22, 6, 2026, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 25, '2026-06-25 00:34:35', 'Processed', NULL, NULL),
(23, 23, 6, 2026, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 25, '2026-06-25 00:34:35', 'Processed', NULL, NULL),
(24, 24, 6, 2026, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 25, '2026-06-25 00:34:35', 'Processed', NULL, NULL),
(25, 25, 6, 2026, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 25, '2026-06-25 00:34:35', 'Processed', NULL, NULL),
(26, 51, 6, 2026, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 25, '2026-06-25 00:34:35', 'Processed', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payroll_reports`
--
-- Creation: Jun 28, 2026 at 05:57 AM
--

CREATE TABLE IF NOT EXISTS `payroll_reports` (
  `id` int NOT NULL,
  `report_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `generated_by` int NOT NULL,
  `report_data` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_runs`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `payroll_runs` (
  `id` int NOT NULL,
  `period` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `total_gross` decimal(15,2) DEFAULT '0.00',
  `total_deductions` decimal(15,2) DEFAULT '0.00',
  `total_net` decimal(15,2) DEFAULT '0.00',
  `status` enum('draft','approved','processed','paid') COLLATE utf8mb4_general_ci DEFAULT 'draft',
  `created_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_settings`
--
-- Creation: Jun 28, 2026 at 05:57 AM
-- Last update: Jun 28, 2026 at 07:11 AM
--

CREATE TABLE IF NOT EXISTS `payroll_settings` (
  `id` int NOT NULL,
  `setting_key` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payroll_settings`
--

INSERT DELAYED IGNORE INTO `payroll_settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'currency', 'PHP', '2026-06-28 05:57:40'),
(2, 'currency_symbol', '₱', '2026-06-28 05:57:40'),
(3, 'tax_rate', '0', '2026-06-28 05:57:40'),
(4, 'sss_rate', '0.045', '2026-06-28 05:57:40'),
(5, 'philhealth_rate', '0.0225', '2026-06-28 05:57:40'),
(6, 'pagibig_rate', '0.02', '2026-06-28 05:57:40'),
(7, 'overtime_rate', '1.25', '2026-06-28 05:57:40');

-- --------------------------------------------------------

--
-- Table structure for table `payslips`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `payslips` (
  `id` int NOT NULL,
  `payslip_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `staff_id` int NOT NULL,
  `payroll_run_id` int DEFAULT NULL,
  `payroll_detail_id` int DEFAULT NULL,
  `salary_month` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `basic_salary` decimal(15,2) DEFAULT NULL,
  `allowances` decimal(15,2) DEFAULT NULL,
  `gross_salary` decimal(15,2) DEFAULT NULL,
  `deductions` decimal(15,2) DEFAULT NULL,
  `payment_ref` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pdf_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `net_salary` decimal(15,2) DEFAULT NULL,
  `payment_method` enum('bank_transfer','cash','cheque') COLLATE utf8mb4_general_ci DEFAULT 'bank_transfer',
  `payment_date` date DEFAULT NULL,
  `generated_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `viewed_by_employee` tinyint(1) DEFAULT '0',
  `viewed_date` timestamp NULL DEFAULT NULL,
  `status` enum('generated','approved','paid') COLLATE utf8mb4_general_ci DEFAULT 'generated',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `penalty_config`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `penalty_config` (
  `id` int NOT NULL,
  `penalty_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `penalty_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `penalty_value` decimal(15,2) DEFAULT NULL,
  `grace_days` int DEFAULT '0',
  `max_charge` decimal(15,2) DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `penalty_configurations`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `penalty_configurations` (
  `id` int NOT NULL,
  `penalty_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `penalty_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pending_students`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `pending_students` (
  `id` int UNSIGNED NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `middle_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `student_number` varchar(60) COLLATE utf8mb4_general_ci NOT NULL,
  `program` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `level` varchar(20) COLLATE utf8mb4_general_ci DEFAULT '1',
  `intake_year` varchar(4) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `intake_period` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'January',
  `phone` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `submitted_by` int UNSIGNED DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'pending_approval',
  `approval_request_id` int UNSIGNED DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pending_students`
--

INSERT DELAYED IGNORE INTO `pending_students` (`id`, `first_name`, `middle_name`, `last_name`, `student_number`, `program`, `level`, `intake_year`, `intake_period`, `phone`, `email`, `date_of_birth`, `submitted_by`, `status`, `approval_request_id`, `rejection_reason`, `created_at`) VALUES
(1, 'Akello', NULL, 'Grace', 'ISNM-2026-006', 'Diploma Nursing', '1', '2026', 'January', NULL, NULL, NULL, 5, 'pending_approval', 4, NULL, '2026-06-19 21:47:50'),
(2, 'Bwire', NULL, 'John', 'ISNM-2026-007', 'Certificate Midwifery', '1', '2026', 'January', NULL, NULL, NULL, 5, 'pending_approval', 5, NULL, '2026-06-19 00:47:50');

-- --------------------------------------------------------

--
-- Table structure for table `performance_indicators`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `performance_indicators` (
  `id` int UNSIGNED NOT NULL,
  `indicator_name` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `target_value` decimal(12,2) DEFAULT NULL,
  `actual_value` decimal(12,2) DEFAULT NULL,
  `period` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `department` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `performance_metrics`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `performance_metrics` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `staff_id` int DEFAULT NULL,
  `metric_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metric_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metric_value` decimal(10,2) DEFAULT NULL,
  `metric_unit` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `target_value` decimal(10,2) DEFAULT NULL,
  `period` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recorded_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `performance_reviews`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `performance_reviews` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `reviewer_id` int DEFAULT NULL,
  `reviewed_by` int DEFAULT NULL,
  `review_period` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `overall_score` decimal(5,2) DEFAULT NULL,
  `comments` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `permissions` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `portal_messages`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `portal_messages` (
  `id` int UNSIGNED NOT NULL,
  `sender_id` int NOT NULL,
  `recipient_id` int DEFAULT NULL,
  `recipient_type` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'individual',
  `subject` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_general_ci,
  `is_read` tinyint(1) DEFAULT '0',
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `professional_licenses`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `professional_licenses` (
  `id` int UNSIGNED NOT NULL,
  `staff_id` int UNSIGNED NOT NULL,
  `license_number` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `license_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `issuing_body` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'active',
  `document_path` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `programs` (
  `id` int NOT NULL,
  `program_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `program_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department_id` int DEFAULT NULL,
  `duration_years` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `proof_of_payments`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `proof_of_payments` (
  `id` int NOT NULL,
  `payment_id` int DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uploaded_by` int DEFAULT NULL,
  `verified_by` int DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quality_assurance`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `quality_assurance` (
  `id` int UNSIGNED NOT NULL,
  `review_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `reviewer_id` int UNSIGNED DEFAULT NULL,
  `department` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `findings` text COLLATE utf8mb4_general_ci,
  `recommendations` text COLLATE utf8mb4_general_ci,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'in_progress',
  `review_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `real_time_updates`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `real_time_updates` (
  `id` int NOT NULL,
  `update_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `update_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `update_description` text COLLATE utf8mb4_unicode_ci,
  `update_data` json DEFAULT NULL,
  `priority` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'normal',
  `target_user` int DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT NULL,
  `created_at_ts` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `receipt_templates`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `receipt_templates` (
  `id` int NOT NULL,
  `template_name` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `template_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `template_content` longtext COLLATE utf8mb4_unicode_ci,
  `header_text` text COLLATE utf8mb4_unicode_ci,
  `footer_text` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recruitment`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `recruitment` (
  `id` int UNSIGNED NOT NULL,
  `position_title` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `department` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `vacancies` int DEFAULT '1',
  `requirements` text COLLATE utf8mb4_general_ci,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'open',
  `posted_date` date DEFAULT NULL,
  `closing_date` date DEFAULT NULL,
  `created_by` int UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recruitment_applications`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `recruitment_applications` (
  `id` int NOT NULL,
  `vacancy_id` int DEFAULT NULL,
  `applicant_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `applicant_email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `applicant_phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cv_path` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cover_letter` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'received',
  `reviewed_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recruitment_jobs`
--
-- Creation: Jun 27, 2026 at 05:00 PM
--

CREATE TABLE IF NOT EXISTS `recruitment_jobs` (
  `id` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department_id` int DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `requirements` text COLLATE utf8mb4_unicode_ci,
  `salary_range` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'open',
  `posted_date` date DEFAULT NULL,
  `closing_date` date DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recycle_bin`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `recycle_bin` (
  `id` int UNSIGNED NOT NULL,
  `original_table` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `original_id` int UNSIGNED DEFAULT NULL,
  `data` longtext COLLATE utf8mb4_general_ci,
  `deleted_by` int UNSIGNED DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `restored_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrar_academic_calendar`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `registrar_academic_calendar` (
  `id` int NOT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `semester` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `event_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrar_academic_records`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `registrar_academic_records` (
  `id` int NOT NULL,
  `student_id` int DEFAULT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `semester` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `record_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `record_data` longtext COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrar_graduation`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `registrar_graduation` (
  `id` int NOT NULL,
  `student_id` int DEFAULT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `program` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `graduation_date` date DEFAULT NULL,
  `classification` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrar_student_registration`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `registrar_student_registration` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `semester` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `registration_date` date DEFAULT NULL,
  `registration_status` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Registered',
  `registered_by` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrar_transcripts`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `registrar_transcripts` (
  `id` int NOT NULL,
  `transcript_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `student_id` int DEFAULT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `program` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transcript_status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `request_date` datetime DEFAULT NULL,
  `generated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrar_transcript_requests`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `registrar_transcript_requests` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `request_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `purpose` text COLLATE utf8mb4_general_ci,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `requested_by` int UNSIGNED DEFAULT NULL,
  `processed_by` int UNSIGNED DEFAULT NULL,
  `fee_paid` decimal(10,2) DEFAULT '0.00',
  `generated_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `requirement_history`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `requirement_history` (
  `id` int NOT NULL,
  `applicant_id` int NOT NULL,
  `requirement_id` int DEFAULT NULL,
  `action` enum('Submitted','Verified','Rejected','Updated','Reset') COLLATE utf8mb4_unicode_ci NOT NULL,
  `performed_by` int DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `research_projects`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `research_projects` (
  `id` int UNSIGNED NOT NULL,
  `project_title` varchar(300) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `principal_investigator` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `department` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `funding_source` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `budget` decimal(12,2) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `result_publication`
--
-- Creation: Jun 27, 2026 at 04:59 PM
--

CREATE TABLE IF NOT EXISTS `result_publication` (
  `id` int NOT NULL,
  `publication_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `semester` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `program` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `course_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scheduled_date` datetime DEFAULT NULL,
  `published_by` int DEFAULT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Published',
  `published_at` datetime DEFAULT NULL,
  `notification_sent` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `result_publications`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `result_publications` (
  `id` int NOT NULL,
  `publication_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `semester` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `program` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `course_code` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('draft','scheduled','published','withdrawn') COLLATE utf8mb4_general_ci DEFAULT 'draft',
  `published_by` int DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `scheduled_date` datetime DEFAULT NULL,
  `notification_sent` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `roles` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT DELAYED IGNORE INTO `roles` (`id`, `name`, `description`, `created_at`) VALUES
(1, 'Director General', NULL, '2026-06-09 22:56:09'),
(2, 'CEO', NULL, '2026-06-09 22:56:09'),
(3, 'Director Academics', NULL, '2026-06-09 22:56:09'),
(4, 'Director Finance', NULL, '2026-06-09 22:56:09'),
(5, 'Director ICT', NULL, '2026-06-09 22:56:09'),
(6, 'School Principal', NULL, '2026-06-09 22:56:09'),
(7, 'Deputy Principal', NULL, '2026-06-09 22:56:09'),
(8, 'Academic Registrar', NULL, '2026-06-09 22:56:09'),
(9, 'HR Manager', NULL, '2026-06-09 22:56:09'),
(10, 'School Secretary', NULL, '2026-06-09 22:56:09'),
(11, 'School Librarian', NULL, '2026-06-09 22:56:09'),
(12, 'Head Nursing', NULL, '2026-06-09 22:56:09'),
(13, 'Head Midwifery', NULL, '2026-06-09 22:56:09'),
(14, 'Senior Lecturers', NULL, '2026-06-09 22:56:09'),
(15, 'Lecturers', NULL, '2026-06-09 22:56:09'),
(16, 'Matrons', NULL, '2026-06-09 22:56:09'),
(17, 'Wardens', NULL, '2026-06-09 22:56:09'),
(18, 'Sickbay', NULL, '2026-06-09 22:56:09'),
(19, 'Drivers', NULL, '2026-06-09 22:56:09'),
(20, 'Security', NULL, '2026-06-09 22:56:09'),
(21, 'Storekeeper', NULL, '2026-06-09 22:56:09'),
(22, 'Guild President', NULL, '2026-06-09 22:56:09'),
(23, 'Computer Lab Manager', NULL, '2026-06-09 22:56:09'),
(24, 'School Bursar', NULL, '2026-06-09 22:56:09'),
(25, 'Store Keeper', 'Store inventory', '2026-06-13 02:38:49'),
(26, 'Director Admissions & Requirements', 'Admissions management', '2026-06-13 02:38:49'),
(27, 'Bursar', 'Bursar assistant', '2026-06-13 02:38:49');

-- --------------------------------------------------------

--
-- Table structure for table `room_inspections`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `room_inspections` (
  `id` int UNSIGNED NOT NULL,
  `room_id` int UNSIGNED DEFAULT NULL,
  `inspector_id` int UNSIGNED DEFAULT NULL,
  `inspection_date` date DEFAULT NULL,
  `score` int DEFAULT NULL,
  `findings` text COLLATE utf8mb4_general_ci,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `scholarships`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `scholarships` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `scholarship_name` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `provider` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_access_logs`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `security_access_logs` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `access_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `location` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `accessed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_incidents`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `security_incidents` (
  `id` int UNSIGNED NOT NULL,
  `incident_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `location` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `reported_by` int UNSIGNED DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'reported',
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `semesters`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `semesters` (
  `id` int NOT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `semester_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT '0',
  `status` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sickbay_settings`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `sickbay_settings` (
  `id` int NOT NULL,
  `setting_key` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_general_ci,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sickbay_settings`
--

INSERT DELAYED IGNORE INTO `sickbay_settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'reorder_level', '10', '2026-06-19 22:59:38'),
(2, 'low_stock_threshold', '10', '2026-06-19 22:59:38'),
(3, 'auto_status', '1', '2026-06-19 22:59:38'),
(4, 'notify_low_stock', '1', '2026-06-19 22:59:38'),
(5, 'default_theme', 'default-blue', '2026-06-19 22:59:38');

-- --------------------------------------------------------

--
-- Table structure for table `sickness_directory`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
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

INSERT DELAYED IGNORE INTO `sickness_directory` (`id`, `sickness_code`, `sickness_name`, `category`, `common_symptoms`, `description`, `is_contagious`, `typical_treatment`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'MLR', 'Malaria', 'Infectious', 'Fever, chills, headache, sweating, fatigue', 'Mosquito-borne parasitic infection common in tropical regions', 0, 'Artemisinin-based combination therapy, antimalarials', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(2, 'TYP', 'Typhoid', 'Infectious', 'Prolonged fever, abdominal pain, headache, constipation or diarrhea', 'Bacterial infection spread through contaminated food/water', 1, 'Antibiotics (ciprofloxacin, azithromycin), hydration', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(3, 'FLU', 'Influenza', 'Infectious', 'Fever, cough, sore throat, body aches, fatigue', 'Viral respiratory infection spread through droplets', 1, 'Rest, fluids, antipyretics, antivirals if severe', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(4, 'COLD', 'Common Cold', 'Infectious', 'Runny nose, sneezing, sore throat, cough, mild fever', 'Viral upper respiratory tract infection', 1, 'Rest, antihistamines, decongestants, vitamin C', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(5, 'URTI', 'Upper Respiratory Tract Infection', 'Infectious', 'Cough, sore throat, nasal congestion, fever', 'Bacterial or viral infection of upper airways', 1, 'Antibiotics if bacterial, rest, fluids', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(6, 'HDCH', 'Headache/Tension Headache', 'Non-Infectious', 'Head pain, pressure around forehead, neck tension', 'Common tension-type headache from stress or fatigue', 0, 'Rest, analgesics (paracetamol, ibuprofen)', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(7, 'GSTR', 'Gastritis', 'Non-Infectious', 'Abdominal pain, nausea, bloating, indigestion', 'Inflammation of stomach lining from diet, stress, or infection', 0, 'Antacids, dietary changes, proton pump inhibitors', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(8, 'DIAR', 'Diarrhea', 'Infectious', 'Loose watery stools, abdominal cramps, dehydration', 'Common infection from contaminated food/water or viruses', 1, 'ORS, hydration, antidiarrheals, antibiotics if bacterial', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(9, 'ALLG', 'Allergic Reaction', 'Non-Infectious', 'Rash, itching, sneezing, watery eyes, swelling', 'Immune response to allergens (food, dust, pollen, drugs)', 0, 'Antihistamines, corticosteroids, avoid triggers', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(10, 'INJR', 'Injury/Accident', 'Injury', 'Pain, swelling, bruising, bleeding, limited mobility', 'Physical trauma from falls, sports, or accidents', 0, 'First aid, rest, ice, compression, elevation, analgesics', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(11, 'ANEM', 'Anemia', 'Nutritional', 'Fatigue, weakness, pale skin, shortness of breath, dizziness', 'Low red blood cell count from iron deficiency or other causes', 0, 'Iron supplements, dietary changes, B12 if needed', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(12, 'MALN', 'Malnutrition', 'Nutritional', 'Weight loss, fatigue, poor growth, weakened immunity', 'Inadequate nutrient intake affecting overall health', 0, 'Nutritional supplementation, diet counseling', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(13, 'CONS', 'Constipation', 'Non-Infectious', 'Infrequent bowel movements, straining, hard stools', 'Common digestive issue from diet or lifestyle factors', 0, 'Increased fiber intake, hydration, laxatives if needed', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(14, 'SORE', 'Sore Throat', 'Infectious', 'Pain or scratchiness in throat, difficulty swallowing', 'Viral or bacterial throat infection', 1, 'Warm salt water gargle, lozenges, antibiotics if strep', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(15, 'EYEI', 'Eye Infection', 'Infectious', 'Redness, itching, discharge, swollen eyelids', 'Bacterial or viral conjunctivitis', 1, 'Antibiotic or antiviral eye drops, hygiene', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(16, 'SKIN', 'Skin Infection/Rash', 'Infectious', 'Redness, itching, bumps, blisters, peeling', 'Fungal, bacterial, or viral skin infection', 1, 'Topical or oral antibiotics/antifungals, hygiene', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(17, 'FATG', 'Fatigue/General Malaise', 'Non-Infectious', 'Tiredness, low energy, reduced motivation', 'General feeling of being unwell without specific diagnosis', 0, 'Rest, nutrition, hydration, stress management', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(18, 'MSTR', 'Menstrual Cramps', 'Non-Infectious', 'Lower abdominal pain, back pain, nausea during menstruation', 'Painful menstrual periods common in young women', 0, 'Analgesics, heat therapy, rest, NSAIDs', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(19, 'ANXT', 'Anxiety/Stress', 'Mental Health', 'Worry, restlessness, rapid heartbeat, difficulty concentrating', 'Mental health condition common among students under academic pressure', 0, 'Counseling, stress management, relaxation techniques', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(20, 'BACK', 'Back Pain', 'Non-Infectious', 'Lower or upper back pain, stiffness, muscle tension', 'Musculoskeletal pain from poor posture, heavy lifting, or strain', 0, 'Rest, analgesics, physiotherapy, posture correction', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(21, 'THRP', 'Throat Infection/Pharyngitis', 'Infectious', 'Sore throat, red tonsils, swollen lymph nodes, fever', 'Inflammation of the pharynx from viral or bacterial infection', 1, 'Antibiotics if bacterial, rest, warm fluids', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(22, 'TOOT', 'Toothache', 'Non-Infectious', 'Tooth pain, sensitivity, swelling around tooth', 'Dental pain from cavities, infection, or impaction', 0, 'Analgesics, dental referral, antibiotics if infected', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(23, 'URIN', 'Urinary Tract Infection', 'Infectious', 'Painful urination, frequent urination, lower abdominal pain', 'Bacterial infection of the urinary tract', 0, 'Antibiotics, increased fluid intake, cranberry', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(24, 'ACNE', 'Acne/Skin Breakout', 'Non-Infectious', 'Pimples, blackheads, whiteheads, inflamed skin', 'Common skin condition from hormonal changes and stress', 0, 'Topical treatments, hygiene, dietary changes', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
(25, 'FUNG', 'Fungal Infection', 'Infectious', 'Itching, redness, peeling skin, rash with defined edges', 'Fungal skin infection common in tropical climates', 1, 'Antifungal creams or oral medication, keep area dry', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44');

-- --------------------------------------------------------

--
-- Table structure for table `sports_events`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `sports_events` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `sport_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `location` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `event_date` datetime DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'upcoming',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sports_teams`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `sports_teams` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `sport_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `coach_id` int UNSIGNED DEFAULT NULL,
  `captain_id` int UNSIGNED DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `staff` (
  `id` int NOT NULL,
  `staff_id` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `full_name` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role_id` int DEFAULT NULL,
  `position` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `department` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_general_ci DEFAULT 'Active',
  `hire_date` date DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `login_attempts` int DEFAULT '0',
  `locked_until` datetime DEFAULT NULL,
  `is_first_login` tinyint(1) DEFAULT '1',
  `password_changed` tinyint(1) DEFAULT '0',
  `profile_photo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT DELAYED IGNORE INTO `staff` (`id`, `staff_id`, `full_name`, `email`, `phone`, `password`, `role_id`, `position`, `department`, `status`, `hire_date`, `last_login`, `login_attempts`, `locked_until`, `is_first_login`, `password_changed`, `profile_photo`, `address`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Doris Joy Namugwanya', 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug', '', '$2y$10$9OkGyLqxrkWGQ380t05Kj./Gzu7DBUNM75BIileuHsw5nFDzPyksa', 1, 'Director General', 'Executive Office', 'Active', '2026-06-09', '2026-06-27 07:26:14', 0, NULL, 0, 1, NULL, NULL, '2026-06-09 22:56:10', '2026-06-27 07:26:14'),
(2, NULL, 'Doris Joy', 'ceo@igangaschoolofnursingandmidwifery.ac.ug', '', '$2y$10$xXJsVElSZzu.wTNPpSKh2e9mYwUnEz3Fh6N8LKh1qrwyaXbRDqZyC', 2, 'Chief Executive Officer', 'Executive Office', 'Active', '2026-06-09', '2026-06-25 09:22:04', 0, NULL, 0, 1, NULL, '', '2026-06-09 22:56:10', '2026-06-25 09:22:04'),
(3, NULL, 'Stephen Bywaka', 'directoracademic@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$0W2zpD9Mx9jrzFyGY0wzP.vfdAB8wu8JQU.UNPhQ73EM9ABy36r0q', 3, 'Director Academics', 'Academic Affairs', 'Active', '2026-06-09', '2026-06-25 03:11:18', 0, NULL, 0, 1, NULL, NULL, '2026-06-09 22:56:10', '2026-06-25 03:11:18'),
(4, NULL, 'Finance Director', 'finance@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$1B4WKBhbkTe8zAYkJbbEe.D9NtkuxflDZN356rGzPvD16QrWCKywu', 4, 'Director Finance', 'Finance Department', 'Active', '2026-06-09', '2026-06-25 09:22:40', 0, NULL, 0, 1, NULL, NULL, '2026-06-09 22:56:10', '2026-06-25 09:22:40'),
(5, NULL, 'School Principal', 'principal@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$4u3./3VtmlkZAT2xuF7MLudpeJ4AbZLKjxXryhjGKvaFeulUimvGW', 6, 'School Principal', 'Academic Affairs', 'Active', '2026-06-09', '2026-06-25 03:44:45', 0, NULL, 0, 1, NULL, NULL, '2026-06-09 22:56:11', '2026-06-25 03:44:45'),
(6, NULL, 'Deputy Principal', 'dep-principal@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$JszQnm6ppJ6ggmPqkZUHp.qg50dfBBcH7IHXh.2buKGazBNr3lATi', 7, 'Deputy Principal', 'Academic Affairs', 'Active', '2026-06-09', '2026-06-25 03:33:47', 0, NULL, 0, 1, NULL, NULL, '2026-06-09 22:56:11', '2026-06-25 03:33:47'),
(7, NULL, 'Academic Registrar', 'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug', '0772514889', '$2y$10$GO1MFp48tQvP0o4d4DlMZukTH6epueBuCaAu0EXKD0ZglCNFno5zi', 8, 'Academic Registrar', 'Academic Affairs', 'Active', '2026-06-09', '2026-06-26 06:36:27', 0, NULL, 0, 1, NULL, 'Lubas Road', '2026-06-09 22:56:11', '2026-06-26 06:36:27'),
(8, NULL, 'HR Manager', 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$fE/SVKQqJ4BYu2QlLdvlou5Vs1ug7OOivy8hcCdXzctlpKUZwvfP.', 9, 'HR Manager', 'Human Resources', 'Active', '2026-06-09', '2026-06-26 06:04:57', 0, NULL, 0, 1, NULL, NULL, '2026-06-09 22:56:14', '2026-06-26 06:04:57'),
(9, NULL, 'School Secretary', 'secretary@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$rV7s4oFYEGX.6STyluPxRO7AHKRJdI5fEBqg1XJDX9NKfCXCuSuea', 10, 'School Secretary', 'Administrative Office', 'Active', '2026-06-09', '2026-06-25 01:36:49', 0, NULL, 0, 1, NULL, NULL, '2026-06-09 22:56:23', '2026-06-25 01:36:49'),
(10, NULL, 'School Librarian', 'library@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$P/fxbkdmQ75Q4rv7x1HXz.34No68cJNJLHqSPki02VjdGbiKO83iS', 11, 'School Librarian', 'Library Services', 'Active', '2026-06-09', '2026-06-21 08:38:59', 0, NULL, 0, 1, NULL, NULL, '2026-06-09 22:56:29', '2026-06-21 08:38:59'),
(11, NULL, 'Head of Nursing', 'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$Iw8BStEfmuQ4THpt0djno.ZNV4KzveqG1R2yZtf2awMAz5u9EOi0a', 12, 'Head Nursing', 'Nursing Department', 'Active', '2026-06-09', '2026-06-13 03:14:38', 0, NULL, 0, 1, NULL, NULL, '2026-06-09 22:56:31', '2026-06-13 03:14:38'),
(12, NULL, 'Head of Midwifery', 'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$.sB5xOu5VTfjRndsyBY71uCRuX.Bn6mEm6bqQjb/5L3EmzCcpARCu', 13, 'Head Midwifery', 'Midwifery Department', 'Active', '2026-06-09', '2026-06-13 03:14:38', 0, NULL, 0, 1, NULL, NULL, '2026-06-09 22:56:33', '2026-06-13 03:14:38'),
(13, NULL, 'Senior Lecturers', 'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$331R3j5oa4oUjpgFDqZhTOANB4N8M41gU1CHXXIHg4LuylO6JMCwu', 14, 'Senior Lecturer', 'Academic Affairs', 'Active', '2026-06-09', '2026-06-13 03:14:38', 0, NULL, 0, 1, NULL, NULL, '2026-06-09 22:56:33', '2026-06-13 03:14:38'),
(14, NULL, 'Lecturers', 'lecturers@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$.kjo780DIjtfeTxVcarWq.mZcfcmxmCw.5c53/PaFXalTVBQMRCOG', 15, 'Lecturer', 'Academic Affairs', 'Active', '2026-06-09', '2026-06-13 03:14:38', 0, NULL, 0, 1, NULL, NULL, '2026-06-09 22:56:34', '2026-06-13 03:14:38'),
(15, NULL, 'Matron', 'matron@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$ymrXcnRhazxfrVpyNyaUk.R7naE6eUus6eFUEYdO0bw.HJmXOU7Qq', 16, 'Matrons', 'Student Affairs', 'Active', '2026-06-09', '2026-06-13 03:14:38', 0, NULL, 0, 1, NULL, NULL, '2026-06-09 22:56:34', '2026-06-13 03:14:38'),
(16, NULL, 'Warden', 'warden@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$5WAJaPKTb8xLi.SRfC6cD.UQ0JnCA5AqlRSS6aJdz9LD7C0gWtMty', 17, 'Wardens', 'Student Affairs', 'Active', '2026-06-09', '2026-06-13 03:14:38', 0, NULL, 0, 1, NULL, NULL, '2026-06-09 22:56:34', '2026-06-13 03:14:38'),
(17, NULL, 'Sickbay Officer', 'sickbay@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$xKCeFMFeDVhXZOxpEoQFPOBR8Cx60T7De1rIAnjAxaSSTmdwCN2Ym', 18, 'Sickbay', 'Support', 'Active', '2026-06-09', '2026-06-26 04:07:21', 0, NULL, 0, 1, NULL, NULL, '2026-06-09 22:56:34', '2026-06-26 04:07:21'),
(18, NULL, 'Driver', 'drivers@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$xZnL4zt/B7h0/E7SHNAhfe4MPYA4HhfioLU7qRQ0ORkv9eABxfIia', 19, 'Drivers', 'Transport', 'Active', '2026-06-09', '2026-06-13 03:14:39', 0, NULL, 0, 1, NULL, NULL, '2026-06-09 22:56:35', '2026-06-13 03:14:39'),
(19, NULL, 'Security Officer', 'security@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$H3mJR/813QrKDzaQMK/yC.HfM4mGpYwgPFmlZL3h/WyTSD4d5zsQq', 20, 'Security', 'Security Services', 'Active', '2026-06-09', '2026-06-13 03:14:39', 0, NULL, 0, 1, NULL, NULL, '2026-06-09 22:56:35', '2026-06-13 03:14:39'),
(20, NULL, 'Storekeeper', 'store@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$2BJLSl5d1x.KCCV83Unqv.LrM9MDrXGO.pm3Ly99plAGdjUJuxVhi', 21, 'Store Keeper', 'Facilities Management', 'Active', '2026-06-09', '2026-06-13 03:14:39', 0, NULL, 0, 1, NULL, NULL, '2026-06-09 22:56:36', '2026-06-13 03:14:39'),
(21, NULL, 'Guild President', 'guildpresident@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$2Acd3VjS07HN.YJHFjyzWOk9QsxmYpBY9oXDc1xwyPtKelUSpMtgi', 22, 'Guild President', 'Student Affairs', 'Active', '2026-06-09', '2026-06-13 03:14:39', 0, NULL, 0, 1, NULL, NULL, '2026-06-09 22:56:36', '2026-06-13 03:14:39'),
(22, NULL, 'Computer Lab Manager', 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$KlyNxRbEDLRbU4XO1uP6Ru9jjXAJP8owjUaneUmAAiK9s4eDUZnM2', 23, 'Director ICT', 'Information Communication Technology', 'Active', '2026-06-09', '2026-06-26 23:55:46', 0, NULL, 0, 1, NULL, NULL, '2026-06-09 22:56:36', '2026-06-26 23:55:46'),
(23, NULL, 'Danny ICT Director', 'dannybict@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$6au4jFh5fu7rXKWuAoKDauv.h9sQ6ONfUaBiGydeqh7JU2sO1BYoi', 5, 'Director ICT', 'Information Technology', 'Active', '2026-06-09', '2026-06-26 23:38:31', 0, NULL, 0, 1, NULL, NULL, '2026-06-09 22:56:36', '2026-06-26 23:38:31'),
(24, NULL, 'Admissions Officer Derrick', 'admissions@igangaschoolofnursingandmidwifery.ac.ug', '', '$2y$10$tLG3brrbgq6IfcHkV1O95ujGlp892EyxpFezOmACyrKA2f3b17NkG', 26, 'Director Admissions & Requirements', 'Admissions', 'Active', '2026-06-09', '2026-06-25 07:25:54', 0, NULL, 1, 1, NULL, NULL, '2026-06-09 22:56:37', '2026-06-25 07:25:54'),
(25, NULL, 'School Bursar', 'bursar@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$WgxHRWfiQH.Wv3UgHkiKIODKCs9wTXTkSxuEgBkQ6OyxTby/Tp.GG', 24, 'School Bursar', 'Finance Department', 'Active', '2026-06-10', '2026-06-26 06:04:57', 0, NULL, 0, 1, NULL, NULL, '2026-06-10 00:56:49', '2026-06-26 06:04:57'),
(51, 'BURS002', 'Bursar', 'bursar.assistant@isnm.ac.ug', NULL, '$2y$10$U61BKsKqMuX1LajK/sSOme3yETx/qnoNw75CxEiBr7mX8pd.922v.', 27, 'Bursar', 'Finance Department', 'Active', '2026-06-13', NULL, 0, NULL, 1, 0, NULL, NULL, '2026-06-13 02:38:49', '2026-06-13 02:38:49');

-- --------------------------------------------------------

--
-- Table structure for table `staff_activity_log`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `staff_activity_log` (
  `id` int NOT NULL,
  `staff_id` int DEFAULT NULL,
  `activity_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `activity_description` text COLLATE utf8mb4_general_ci,
  `module_accessed` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_activity_log`
--

INSERT DELAYED IGNORE INTO `staff_activity_log` (`id`, `staff_id`, `activity_type`, `activity_description`, `module_accessed`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'Login', 'User logged in successfully', 'authentication', '::1', 'curl/8.19.0', '2026-06-09 23:06:48'),
(2, 4, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-06-09 23:07:33'),
(3, 4, 'Login', 'User logged in successfully', 'authentication', '::1', 'curl/8.19.0', '2026-06-09 23:16:40'),
(4, 1, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-06-09 23:27:04'),
(5, 25, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-06-10 00:57:13'),
(6, 24, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-06-10 01:02:10'),
(7, 9, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-06-10 06:12:56'),
(8, 1, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 03:34:12'),
(9, 1, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 22:18:02'),
(10, 1, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:13:54'),
(11, 2, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:15:02'),
(12, 2, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:20:03'),
(13, 3, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:20:34'),
(14, 3, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:21:34'),
(15, 4, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:21:40'),
(16, 4, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:23:30'),
(17, 5, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:23:54'),
(18, 5, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:24:29'),
(19, 6, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:25:03'),
(20, 6, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:34:20'),
(21, 25, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:34:25'),
(22, 25, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:35:04'),
(23, 24, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:35:42'),
(24, 1, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 09:31:15'),
(25, 1, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 10:01:54'),
(26, 2, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 10:01:58'),
(27, 25, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 10:03:51'),
(28, 25, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 10:50:46'),
(29, 1, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 10:50:50'),
(30, 1, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 11:29:09'),
(31, 25, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 11:29:16'),
(32, 25, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 11:55:42'),
(33, 7, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 11:56:46'),
(34, 7, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:17:40'),
(35, 7, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:17:44'),
(36, 7, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:17:51'),
(37, 7, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:17:56'),
(38, 7, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:18:39'),
(39, 23, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:19:17'),
(40, 23, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:20:09'),
(41, 22, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:20:23'),
(42, 22, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:36:57'),
(43, 22, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:37:05'),
(44, 22, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:37:13'),
(45, 24, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:37:22'),
(46, 24, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:44:48'),
(47, 24, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:44:53'),
(48, 24, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:47:22'),
(49, 24, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:47:26'),
(50, 7, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 20:38:19'),
(51, 17, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 22:33:02'),
(52, 1, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 23:52:59'),
(53, 1, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 03:31:17'),
(54, 2, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 03:31:21'),
(55, 2, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 04:07:27'),
(56, 25, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 04:07:31'),
(57, 25, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 04:08:03'),
(58, 17, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 04:08:13'),
(59, 1, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 11:16:50'),
(60, 1, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 12:18:28'),
(61, 25, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 12:18:33'),
(62, 25, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 15:41:44'),
(63, 1, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 15:41:52'),
(64, 1, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 00:45:04'),
(65, 1, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 02:19:19'),
(66, 1, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 02:19:30'),
(67, 1, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 02:27:12'),
(68, 2, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 02:27:16'),
(69, 2, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 03:02:25'),
(70, 3, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 03:02:29'),
(71, 3, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 03:38:27'),
(72, 4, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 03:38:32'),
(73, 4, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 03:40:03'),
(74, 25, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 03:40:07'),
(75, 25, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 03:41:00'),
(76, 24, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 03:41:15'),
(77, 24, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 08:22:54'),
(78, 8, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 08:23:34'),
(79, 8, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 08:27:56'),
(80, 7, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 08:28:06'),
(81, 7, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 08:38:24'),
(82, 10, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 08:38:59'),
(83, 10, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 08:41:06'),
(84, 9, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 08:41:11'),
(85, 9, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 09:42:45'),
(86, 22, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 09:42:51'),
(87, 17, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 09:46:09'),
(88, 17, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 09:47:31'),
(89, 17, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 09:48:32'),
(90, 17, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 09:51:42'),
(91, 24, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 09:58:10'),
(92, 24, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 09:58:10'),
(93, 24, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 09:58:20'),
(94, 24, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:30:58'),
(95, 24, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:31:07'),
(96, 24, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:32:25'),
(97, 24, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:32:28'),
(98, 24, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:32:37'),
(99, 24, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:33:45'),
(100, 24, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:36:03'),
(101, 24, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:36:09'),
(102, 24, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:37:01'),
(103, 7, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:37:24'),
(104, 7, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 21:01:48'),
(105, 7, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 21:03:10'),
(106, 7, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 21:03:15'),
(107, 24, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 21:14:03'),
(108, 24, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 21:14:39'),
(109, 8, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 21:14:44'),
(110, 8, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 21:26:39'),
(111, 24, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 21:26:55'),
(112, 24, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 22:54:34'),
(113, 24, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 22:56:35'),
(114, 24, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 00:35:18'),
(115, 24, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 00:35:45'),
(116, 24, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 01:09:10'),
(117, 24, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 01:13:36'),
(118, 24, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 01:13:52'),
(119, 24, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 01:19:37'),
(120, 24, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 01:19:57'),
(121, 24, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 01:23:33'),
(122, 24, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 01:23:38'),
(123, 24, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 10:49:58'),
(124, 24, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 10:50:07'),
(125, 24, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 11:15:02'),
(126, 24, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 11:15:10'),
(127, 24, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 11:33:52'),
(128, 24, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 11:34:00'),
(129, 24, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 11:57:38'),
(130, 24, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 11:57:49'),
(131, 24, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 12:48:33'),
(132, 1, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 12:48:39'),
(133, 1, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 12:59:26'),
(134, 24, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 12:59:31'),
(135, 24, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 13:06:54'),
(136, 1, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 13:06:59'),
(137, 1, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 13:10:18'),
(138, 4, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 13:10:22'),
(139, 4, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 13:59:43'),
(140, 1, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 13:59:47'),
(141, 1, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 14:59:12'),
(142, 24, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 14:59:19'),
(143, 1, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 22:02:54'),
(144, 24, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 01:21:00'),
(145, 25, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 05:12:44'),
(146, 1, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 22:40:59'),
(147, 1, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 03:32:48'),
(148, 1, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 03:42:32'),
(149, 1, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 03:55:02'),
(150, 3, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 03:55:07'),
(151, 3, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 05:26:18'),
(152, 5, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 05:26:23'),
(153, 5, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 05:26:50'),
(154, 9, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 05:26:54'),
(155, 9, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 06:46:40'),
(156, 1, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 06:46:58'),
(157, 1, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 07:01:10'),
(158, 24, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 07:01:17'),
(159, 24, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 08:01:53'),
(160, 25, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 08:02:15'),
(161, 25, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 08:04:18'),
(162, 1, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 08:04:28'),
(163, 1, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 08:10:00'),
(164, 17, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 08:10:06'),
(165, 25, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 23:37:39'),
(166, 25, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 01:36:45'),
(167, 9, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 01:36:50'),
(168, 9, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 02:39:48'),
(169, 4, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 02:39:53'),
(170, 4, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 03:11:14'),
(171, 3, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 03:11:18'),
(172, 3, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 03:11:50'),
(173, 5, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 03:11:55'),
(174, 5, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 03:33:42'),
(175, 6, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 03:33:47'),
(176, 6, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 03:44:41'),
(177, 5, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 03:44:45'),
(178, 5, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 03:47:13'),
(179, 24, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 03:47:18'),
(180, 24, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 07:25:44'),
(181, 24, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 07:25:54'),
(182, 24, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 08:06:13'),
(183, 1, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 08:58:55'),
(184, 1, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 09:05:31'),
(185, 2, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 09:22:04'),
(186, 2, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 09:22:27'),
(187, 4, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 09:22:40'),
(188, 4, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 09:26:50'),
(189, 17, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 04:07:21'),
(190, 17, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 04:17:57'),
(191, 8, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 04:18:03'),
(192, 8, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 05:39:01'),
(193, 8, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 05:39:08'),
(194, 8, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 05:47:58'),
(195, 25, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 05:48:03'),
(196, 8, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8737', '2026-06-26 06:02:14'),
(197, 8, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8737', '2026-06-26 06:02:22'),
(198, 25, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8737', '2026-06-26 06:03:07'),
(199, 8, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8737', '2026-06-26 06:03:20'),
(200, 25, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 06:36:04'),
(201, 7, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 06:36:27'),
(202, 7, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 07:09:32'),
(203, 23, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 07:09:45'),
(204, 23, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 12:36:33'),
(205, 23, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 12:36:42'),
(206, 23, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 14:05:05'),
(207, 23, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 14:05:09'),
(208, 23, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 21:23:09'),
(209, 23, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 21:23:13'),
(210, 23, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 22:04:50'),
(211, 1, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 22:04:55'),
(212, 1, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 22:09:06'),
(213, 23, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 22:09:09'),
(214, 23, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 22:12:42'),
(215, 22, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 22:12:56'),
(216, 22, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 22:42:18'),
(217, 23, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 22:43:32'),
(218, 23, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 22:44:37'),
(219, 22, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 22:44:41'),
(220, 22, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 23:26:46'),
(221, 22, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 23:26:56'),
(222, 22, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 23:38:27'),
(223, 23, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 23:38:31'),
(224, 23, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 23:38:47'),
(225, 22, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 23:38:51'),
(226, 22, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 23:55:47'),
(227, 1, 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-27 07:26:14'),
(228, 1, 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-27 08:26:31');

-- --------------------------------------------------------

--
-- Table structure for table `staff_appraisals`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `staff_appraisals` (
  `id` int NOT NULL,
  `staff_id` int DEFAULT NULL,
  `reviewer_id` int DEFAULT NULL,
  `review_date` date DEFAULT NULL,
  `performance_score` decimal(5,2) DEFAULT NULL,
  `strengths` text COLLATE utf8mb4_general_ci,
  `areas_improvement` text COLLATE utf8mb4_general_ci,
  `overall_rating` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('draft','submitted','reviewed','completed') COLLATE utf8mb4_general_ci DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_attendance`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `staff_attendance` (
  `id` int UNSIGNED NOT NULL,
  `staff_id` int UNSIGNED NOT NULL,
  `date` date NOT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Present',
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_general_ci,
  `recorded_by` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_attendance`
--

INSERT DELAYED IGNORE INTO `staff_attendance` (`id`, `staff_id`, `date`, `status`, `time_in`, `time_out`, `remarks`, `recorded_by`, `created_at`) VALUES
(1, 1, '2026-06-20', 'Absent', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
(2, 2, '2026-06-20', 'On Leave', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
(3, 3, '2026-06-20', 'Present', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
(4, 4, '2026-06-20', 'On Leave', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
(5, 23, '2026-06-20', 'Present', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
(6, 5, '2026-06-20', 'Present', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
(7, 6, '2026-06-20', 'Late', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
(8, 7, '2026-06-20', 'Absent', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
(9, 24, '2026-06-20', 'Present', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
(10, 8, '2026-06-20', 'Present', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
(11, 9, '2026-06-20', 'On Leave', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
(12, 10, '2026-06-20', 'Present', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
(13, 11, '2026-06-20', 'Present', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
(14, 12, '2026-06-20', 'Present', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
(15, 13, '2026-06-20', 'Late', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
(16, 14, '2026-06-20', 'On Leave', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
(17, 15, '2026-06-20', 'Present', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
(18, 16, '2026-06-20', 'Present', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
(19, 17, '2026-06-20', 'Present', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
(20, 18, '2026-06-20', 'Late', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56');

-- --------------------------------------------------------

--
-- Table structure for table `staff_communications`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `staff_communications` (
  `id` int NOT NULL,
  `sender_id` int NOT NULL,
  `sender_email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `sender_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `recipient_type` enum('department','all_staff') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'department',
  `recipient_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `recipient_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `subject` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `message_body` text COLLATE utf8mb4_general_ci NOT NULL,
  `priority` enum('Low','Normal','High','Urgent') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Normal',
  `email_status` enum('pending','sent','failed') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_contracts`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `staff_contracts` (
  `id` int UNSIGNED NOT NULL,
  `staff_id` int UNSIGNED NOT NULL,
  `contract_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `salary` decimal(12,2) DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'active',
  `document_path` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_departments`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `staff_departments` (
  `id` int UNSIGNED NOT NULL,
  `department_name` varchar(120) COLLATE utf8mb4_general_ci NOT NULL,
  `department_code` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `department_level` int DEFAULT '0',
  `description` text COLLATE utf8mb4_general_ci,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_departments`
--

INSERT DELAYED IGNORE INTO `staff_departments` (`id`, `department_name`, `department_code`, `department_level`, `description`, `is_active`, `created_at`) VALUES
(1, 'Executive Leadership', 'EXEC', 1, NULL, 1, '2026-06-19 23:58:56'),
(2, 'Academic Affairs', 'ACAD', 2, NULL, 1, '2026-06-19 23:58:56'),
(3, 'Finance & Accounts', 'FIN', 3, NULL, 1, '2026-06-19 23:58:56'),
(4, 'Human Resources', 'HR', 4, NULL, 1, '2026-06-19 23:58:56'),
(5, 'Nursing Department', 'NUR', 5, NULL, 1, '2026-06-19 23:58:56'),
(6, 'Midwifery Department', 'MID', 6, NULL, 1, '2026-06-19 23:58:56'),
(7, 'ICT', 'ICT', 7, NULL, 1, '2026-06-19 23:58:56'),
(8, 'Admissions', 'ADM', 8, NULL, 1, '2026-06-19 23:58:56'),
(9, 'Library', 'LIB', 9, NULL, 1, '2026-06-19 23:58:56'),
(10, 'Security & Transport', 'SEC', 10, NULL, 1, '2026-06-19 23:58:56'),
(11, 'Store & Assets', 'STR', 11, NULL, 1, '2026-06-19 23:58:56'),
(12, 'Student Services', 'SVS', 12, NULL, 1, '2026-06-19 23:58:56');

-- --------------------------------------------------------

--
-- Table structure for table `staff_leave_requests`
--
-- Creation: Jun 28, 2026 at 05:57 AM
--

CREATE TABLE IF NOT EXISTS `staff_leave_requests` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `leave_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','approved','rejected','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_licenses`
--
-- Creation: Jun 28, 2026 at 05:57 AM
--

CREATE TABLE IF NOT EXISTS `staff_licenses` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `license_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `license_number` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `issuing_authority` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issue_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('active','expired','suspended','revoked') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_login_sessions`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `staff_login_sessions` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `session_token` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_login_sessions`
--

INSERT DELAYED IGNORE INTO `staff_login_sessions` (`id`, `staff_id`, `session_token`, `ip_address`, `user_agent`, `created_at`, `expires_at`) VALUES
(1, 1, 'pu2hvlihjqangi7jviepaf0ob7', '::1', 'curl/8.19.0', '2026-06-09 23:06:48', '2026-06-09 23:36:48'),
(2, 4, '83656fpgh06q9gouhm60nk3tuq', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-06-09 23:07:33', '2026-06-09 23:37:33'),
(3, 4, 'lh39hd80nldj2uegqkjhjk2efn', '::1', 'curl/8.19.0', '2026-06-09 23:16:40', '2026-06-09 23:46:40'),
(4, 1, '7ljqo58oc291b11bqi2s3cjffg', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-06-09 23:27:04', '2026-06-09 23:57:04'),
(5, 25, 'hlr81jh15cqvlf6nl6j8nlhk3f', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-06-10 00:57:13', '2026-06-10 01:27:13'),
(6, 24, 'ae3he9cgsdvgdf024bolec2r14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-06-10 01:02:10', '2026-06-10 01:32:10'),
(7, 9, 'dr24ed01jpd3hparhq890kpnf0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-06-10 06:12:56', '2026-06-10 06:42:56'),
(8, 1, 'k8j0smrve1hncrjkq2he9fu0rh', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 03:34:12', '2026-06-17 04:04:12'),
(9, 1, '2f99647bj7odhsl4cj6vhlals8', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 22:18:02', '2026-06-17 22:48:02'),
(10, 2, 'suho7uaqglfdjpgt6f6bpr0nqb', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:15:02', '2026-06-18 02:45:02'),
(11, 3, 'gn380t4p7ebopr4pbmd83r3098', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:20:34', '2026-06-18 02:50:34'),
(12, 4, 'j0bvg0i2bsstfd5f2b71pnbhv2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:21:40', '2026-06-18 02:51:40'),
(13, 5, '1p2sqtjhn2q39oq8uok2bka2s6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:23:54', '2026-06-18 02:53:54'),
(14, 6, 'ebpn95qsf7pvk6jr5iad1vi3lk', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:25:03', '2026-06-18 02:55:03'),
(15, 25, 's2q2c95audemj51h44e3vmah41', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:34:25', '2026-06-18 03:04:25'),
(16, 24, 'qf5sbbkufe4onpt0j5qdg8cfp4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:35:42', '2026-06-18 03:05:42'),
(17, 1, '1359ma7hua0fmmvl8espcd9an7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 09:31:15', '2026-06-18 10:01:15'),
(18, 2, 'blmvsuvsqc3h3fq4ed857p8asq', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 10:01:58', '2026-06-18 10:31:58'),
(19, 25, 'vv9i7126ujrh0ht0sekerd5vnq', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 10:03:51', '2026-06-18 10:33:51'),
(20, 1, 'sc7nqfk1p54kusvoh7959k81c7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 10:50:50', '2026-06-18 11:20:50'),
(21, 25, '6rclk83t17947n4pj1ngh9hj81', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 11:29:16', '2026-06-18 11:59:16'),
(22, 7, '30mj4uha05dsb1rdea48hkrgb5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 11:56:46', '2026-06-18 12:26:46'),
(23, 7, '2hlgnoq56hhvf37ar4im2ue7nm', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:17:44', '2026-06-18 13:47:44'),
(24, 7, 'gpqmln2qp7o00ek4rjjenf4khj', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:17:56', '2026-06-18 13:47:56'),
(25, 23, '34mpge2kds50ab697a1agal7us', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:19:17', '2026-06-18 13:49:17'),
(26, 22, 'mmt06pq180c82ofjuf8hgihiva', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:20:23', '2026-06-18 13:50:23'),
(27, 22, 'jef954p75gcad385f70ig9tadc', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:37:05', '2026-06-18 14:07:05'),
(28, 24, 't8g4s4ib33vp3villv4iv8p5no', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:37:22', '2026-06-18 14:07:22'),
(29, 24, 'h0l3knqrvi229h6laq5ltln2to', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:44:53', '2026-06-18 14:14:53'),
(30, 24, 'jv52bv72042nq2v2vileprunqr', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:47:26', '2026-06-18 14:17:26'),
(31, 7, '0pa58vehm4juir1f0924c929eu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 20:38:19', '2026-06-18 21:08:19'),
(32, 17, 'gv23nhcevrnc6cu2sqj1q6ksp0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 22:33:02', '2026-06-19 23:03:02'),
(33, 1, 'erd3bpes4jq9qfk173g5561tn7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 23:52:59', '2026-06-20 00:22:59'),
(34, 2, 'c31ettfnja46ueh449bkq22vh7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 03:31:21', '2026-06-20 04:01:21'),
(35, 25, '79007ugk7c1mi07d7m5c9l9c71', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 04:07:31', '2026-06-20 04:37:31'),
(36, 17, '6a3hb5erpafv3av162128t5dpb', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 04:08:13', '2026-06-20 04:38:13'),
(37, 1, 'qbin2lntmfe0ctm7s80b5ccsi6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 11:16:50', '2026-06-20 11:46:50'),
(38, 25, '63qd2kbtvalb6jlf259akkthcc', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 12:18:33', '2026-06-20 12:48:33'),
(39, 1, '5adsl9dnpdml0l9089vi78sk1j', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 15:41:52', '2026-06-20 16:11:52'),
(40, 1, 'titkd3lgrb6p0n2s92875b1f1l', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 00:45:04', '2026-06-21 01:15:04'),
(41, 1, 'np4ea04g9arhbh2ticlj8a8jk0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 02:19:30', '2026-06-21 02:49:30'),
(42, 2, '06f1nkaks13lc7ht4kvuq2sl56', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 02:27:16', '2026-06-21 02:57:16'),
(43, 3, 'dqv8stll1pfe9kmc8lkvchal44', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 03:02:29', '2026-06-21 03:32:29'),
(44, 4, 'mkqhi86baa63c035veice145ll', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 03:38:32', '2026-06-21 04:08:32'),
(45, 25, '5tu70v12sp531pvi6bnd4ktr1f', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 03:40:07', '2026-06-21 04:10:07'),
(46, 24, '2pgtv3ai29nri6qac8qrc9ff13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 03:41:15', '2026-06-21 04:11:15'),
(47, 8, '3fjn3qhpi54ad00ig5jr46adrh', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 08:23:34', '2026-06-21 08:53:34'),
(48, 7, 'mfufdau7qocjbtu885pm8ko2od', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 08:28:06', '2026-06-21 08:58:06'),
(49, 10, 'rvau0732fn5eb7aq561lc5qkm1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 08:38:59', '2026-06-21 09:08:59'),
(50, 9, 'vbguukqdpatqmm20c3m9gjjkcf', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 08:41:11', '2026-06-21 09:11:11'),
(51, 22, 'tq6q7ogmro8nmn0207kngvd21h', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 09:42:51', '2026-06-21 10:12:51'),
(52, 17, 'u7kckp0ni8u4jro21r3902smaj', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 09:46:09', '2026-06-21 10:16:09'),
(53, 17, 'vmp1feirc6evkuqm4kr14kivj1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 09:48:32', '2026-06-21 10:18:32'),
(54, 24, '0a98v19vemano2jnpabb2j1p2g', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 09:58:10', '2026-06-21 10:28:10'),
(55, 24, '0s7tbe4ouk2fiht3obv1nvphav', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 09:58:20', '2026-06-21 10:28:20'),
(56, 24, 'cukgdorpavsii00locajfjqpci', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:31:07', '2026-06-21 11:01:07'),
(57, 24, 'lslmk523ctp75jif1nie3uqd82', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:32:28', '2026-06-21 11:02:28'),
(58, 24, 'veofu3mv8j6t624aa4fs53p2fn', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:33:45', '2026-06-21 11:03:45'),
(59, 24, '4cqichecqd00evma1u7sbk3j2q', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:36:09', '2026-06-21 11:06:09'),
(60, 7, 'd522eht2ekupd06is4b5571tss', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:37:24', '2026-06-21 11:07:24'),
(61, 7, 'irrr02lhbcgfrpu4l69j7d7fvl', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 21:03:10', '2026-06-21 21:33:10'),
(62, 24, 'f2v52677oj0d7cv0fts20sga63', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 21:14:03', '2026-06-21 21:44:03'),
(63, 8, 'q96npe7qia97egg4delvjpcd0u', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 21:14:44', '2026-06-21 21:44:44'),
(64, 24, 'jmg7854n7jgeu8odup6l9iot29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 21:26:55', '2026-06-21 21:56:55'),
(65, 24, '4tblptijta3l5tfta0pvb25601', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 22:56:35', '2026-06-21 23:26:35'),
(66, 24, 'hpq3utci8urukiaruh92vob8mn', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 00:35:45', '2026-06-22 01:05:45'),
(67, 24, 't49b41nfcaruon5ro15p15mltc', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 01:09:10', '2026-06-22 01:39:10'),
(68, 24, 'osoor0p43434atvgr7j66t22f6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 01:13:52', '2026-06-22 01:43:52'),
(69, 24, '0me8chv0u24pr6jfgg9oe23lov', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 01:19:57', '2026-06-22 01:49:57'),
(70, 24, 'kdlr506tct65oilrnuj67pb72i', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 01:23:38', '2026-06-22 01:53:38'),
(71, 24, 'jslvmsv36efl4ukgf1q7g2skrt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 10:50:07', '2026-06-22 11:20:07'),
(72, 24, 'hdvul1svlg8ui13hcqk01cclda', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 11:15:10', '2026-06-22 11:45:10'),
(73, 24, '18d7dqitp2ml8j9nqte0qvvtbc', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 11:34:00', '2026-06-22 12:04:00'),
(74, 24, 'ur2fs528fiomfrd25ggfbntevu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 11:57:49', '2026-06-22 12:27:49'),
(75, 1, 'qrs5r3d0crst274csne31s6bik', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 12:48:39', '2026-06-22 13:18:39'),
(76, 24, 'pqumq7aq89oarcoi7u0vfc4euc', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 12:59:31', '2026-06-22 13:29:31'),
(77, 1, '4dfl0lha5fktfpih6dio8m629n', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 13:06:59', '2026-06-22 13:36:59'),
(78, 4, 'uoi1qs187pr2gd1799ousa63cu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 13:10:22', '2026-06-22 13:40:22'),
(79, 1, '1s9luflbvjvmdor92928kc9lbf', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 13:59:47', '2026-06-22 14:29:47'),
(80, 24, 'ttjcmn74g42n5lstnqmv6pijpu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 14:59:19', '2026-06-22 15:29:19'),
(81, 1, '1pmpbl6de5stu5mdelph4h475e', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 22:02:54', '2026-06-22 22:32:54'),
(82, 24, 'hljpb5ph7e3j24ckvan8mauluh', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 01:21:00', '2026-06-23 01:51:00'),
(83, 25, 'ks1tultbpko3s70j5fhq4e4t9h', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 05:12:44', '2026-06-23 05:42:44'),
(84, 1, 'rf0mklksts3um16lm1c90l2gbq', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 22:40:59', '2026-06-23 23:10:59'),
(85, 1, 'ppfvcia8sprhfv7t5i2dsn38a0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 03:42:32', '2026-06-24 04:12:32'),
(86, 3, 'lgp10qeu8kiecak9fv098pjglo', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 03:55:07', '2026-06-24 04:25:07'),
(87, 5, '6pu90rj74r1pq47q228fcjoniu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 05:26:23', '2026-06-24 05:56:23'),
(88, 9, 'jfn6065k8m3goqpe3sprr17b6p', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 05:26:54', '2026-06-24 05:56:54'),
(89, 1, '794af9ukhkur706kvkfku12iaq', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 06:46:58', '2026-06-24 07:16:58'),
(90, 24, '9jebpeo9cqldprvn4khlrnr9vi', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 07:01:17', '2026-06-24 07:31:17'),
(91, 25, '8a4liolknvm9st9v1kv3r106fl', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 08:02:15', '2026-06-24 08:32:15'),
(92, 1, '25vgr9ffnilj3iaufkdi2olvgd', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 08:04:28', '2026-06-24 08:34:28'),
(93, 17, 'f584elfqqdtdaneqgvu4j2sgb5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 08:10:06', '2026-06-24 08:40:06'),
(94, 25, 'ik5cfqa2gkgjgfmgmefk090jjd', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 23:37:39', '2026-06-25 00:07:39'),
(95, 9, '34ca3u1j6isj17ocoq43vba38c', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 01:36:50', '2026-06-25 02:06:50'),
(96, 4, 'e5vsb9gmjeun7kito01jamqs3d', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 02:39:53', '2026-06-25 03:09:53'),
(97, 3, '7rqduhslro0lq0nplmpbunpgf0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 03:11:18', '2026-06-25 03:41:18'),
(98, 5, 'tl71050ga502dbhf0tggle7b8d', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 03:11:55', '2026-06-25 03:41:55'),
(99, 6, '89n9gmr0fjrhmuuolavg7bh7vj', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 03:33:47', '2026-06-25 04:03:47'),
(100, 5, 't5s26jd6cbasdfv24scv7oqgnc', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 03:44:45', '2026-06-25 04:14:45'),
(101, 24, 'p2ek6i7irhqbkkppvei7r15olf', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 03:47:18', '2026-06-25 04:17:18'),
(102, 24, 'btumr0h7kam4vbeliviv0vht80', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 07:25:54', '2026-06-25 07:55:54'),
(103, 1, 'affknfo0e0cod2oi2jru0qjgph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 08:58:55', '2026-06-25 09:28:55'),
(104, 2, 'h447aeemqdhvj8dlaofabvmss9', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 09:22:04', '2026-06-25 09:52:04'),
(105, 4, 'a087m1fgf0fu8elbeoe57g6il1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 09:22:40', '2026-06-25 09:52:40'),
(106, 17, 'n9o9l07t52qa71jjmg3l9egl3q', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 04:07:21', '2026-06-26 04:37:21'),
(107, 8, 'j92qk81fhdbtt122h79ckue45f', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 04:18:03', '2026-06-26 04:48:03'),
(108, 8, 'edj83pm5bjgr8g6vbeci45ajod', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 05:39:08', '2026-06-26 06:09:08'),
(109, 25, '41s1vms3719jbporauptnbjumd', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 05:48:03', '2026-06-26 06:18:03'),
(110, 8, '3vg1268gsos1b49pha89j8qcdl', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8737', '2026-06-26 06:02:14', '2026-06-26 06:32:14'),
(111, 8, 't0va2mgidaq269fsdhp6dscgr3', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8737', '2026-06-26 06:02:22', '2026-06-26 06:32:22'),
(112, 25, 'gq0ncrfvok3ljs5trl2u2meclg', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8737', '2026-06-26 06:03:07', '2026-06-26 06:33:07'),
(113, 8, '22gvam5engahgclurhh82mppuh', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8737', '2026-06-26 06:03:20', '2026-06-26 06:33:20'),
(114, 7, 'nj45vbijbkug1j88nncnts0oah', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 06:36:27', '2026-06-26 07:06:27'),
(115, 23, '9m3qh7jl3j8bq9fm0qafu02snb', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 07:09:45', '2026-06-26 07:39:45'),
(116, 23, '3tnohnrv7m0us60fmm0slh4vjn', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 12:36:42', '2026-06-26 13:06:42'),
(117, 23, 'odl6daac37jvuiaqq30ebv211t', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 14:05:09', '2026-06-26 14:35:09'),
(118, 23, 'vgh2eulctao8s12rdgdu77t1q7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 21:23:13', '2026-06-26 21:53:13'),
(119, 1, 'ijjcb8ppqqeg0eh07rctuob530', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 22:04:55', '2026-06-26 22:34:55'),
(120, 23, '98sb0ebgrcuh2adlceuj198qfm', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 22:09:09', '2026-06-26 22:39:09'),
(121, 22, '6undm5ctv6iltdc39d2cnr2kom', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 22:12:56', '2026-06-26 22:42:56'),
(122, 23, '1vhpuhmg2t9v6s93e4vt1gdasl', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 22:43:32', '2026-06-26 23:13:32'),
(123, 22, '7qj0v2vurgdm825hnht7cc6cki', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 22:44:41', '2026-06-26 23:14:41'),
(124, 22, '384f35su5t0cqbgqg2q623n8e7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 23:26:56', '2026-06-26 23:56:56'),
(125, 23, 'cl13gqakjddlkkagtdm07brjs2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 23:38:31', '2026-06-27 00:08:31'),
(126, 22, 'hvq4ej7521ircu9s4qdi9a4nu8', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 23:38:51', '2026-06-27 00:08:51'),
(127, 22, '89a3kokapu2e5308u309ftsdoa', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 23:55:47', '2026-06-27 00:25:47'),
(128, 1, 'obs5jdudi11f3h2ffk564c90j6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-27 07:26:14', '2026-06-27 07:56:14');

-- --------------------------------------------------------

--
-- Table structure for table `staff_profiles`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `staff_profiles` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `profile_picture` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `bio` text COLLATE utf8mb4_general_ci,
  `department` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_profiles`
--

INSERT DELAYED IGNORE INTO `staff_profiles` (`id`, `staff_id`, `profile_picture`, `bio`, `department`, `phone`, `address`, `created_at`, `updated_at`) VALUES
(3, 1, NULL, '', NULL, NULL, NULL, '2026-06-24 00:08:59', '2026-06-24 00:08:59'),
(4, 24, NULL, '', NULL, NULL, NULL, '2026-06-24 07:01:59', '2026-06-24 07:01:59');

-- --------------------------------------------------------

--
-- Table structure for table `staff_resignations`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `staff_resignations` (
  `id` int UNSIGNED NOT NULL,
  `staff_id` int UNSIGNED NOT NULL,
  `resignation_date` date DEFAULT NULL,
  `last_working_date` date DEFAULT NULL,
  `reason` text COLLATE utf8mb4_general_ci,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `approved_by` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_roles`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `staff_roles` (
  `id` int NOT NULL,
  `role_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `role_description` text COLLATE utf8mb4_general_ci,
  `role_level` int DEFAULT '5',
  `dashboard_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ;

--
-- Dumping data for table `staff_roles`
--

INSERT DELAYED IGNORE INTO `staff_roles` (`id`, `role_name`, `role_description`, `role_level`, `dashboard_path`, `permissions`, `created_at`, `updated_at`) VALUES
(1, 'Director General', NULL, 1, 'dashboards/director-general.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
(2, 'CEO', NULL, 1, 'dashboards/ceo.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
(3, 'Director Academics', NULL, 2, 'dashboards/director-academics.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
(4, 'Director Finance', NULL, 2, 'dashboards/director-finance.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
(5, 'Director ICT', NULL, 2, 'dashboards/director-ict.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
(6, 'School Principal', NULL, 2, 'dashboards/school-principal.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
(7, 'Deputy Principal', NULL, 3, 'dashboards/deputy-principal.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
(8, 'Academic Registrar', NULL, 3, 'dashboards/academic-registrar.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
(9, 'HR Manager', NULL, 3, 'dashboards/hr-manager.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
(10, 'School Secretary', NULL, 4, 'dashboards/school-secretary.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
(11, 'School Librarian', NULL, 4, 'dashboards/school-librarian.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
(12, 'Head Nursing', NULL, 3, 'dashboards/head-nursing.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
(13, 'Head Midwifery', NULL, 3, 'dashboards/head-midwifery.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
(14, 'Senior Lecturers', NULL, 4, 'dashboards/senior-lecturers.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
(15, 'Lecturers', NULL, 5, 'dashboards/lecturers.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
(16, 'Matrons', NULL, 4, 'dashboards/matrons.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
(17, 'Wardens', NULL, 5, 'dashboards/wardens.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
(18, 'Sickbay', NULL, 5, 'dashboards/sickbay.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
(19, 'Drivers', NULL, 6, 'dashboards/drivers.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
(20, 'Security', NULL, 6, 'dashboards/security.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
(21, 'Storekeeper', NULL, 5, 'dashboards/storekeeper.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
(22, 'Guild President', NULL, 5, 'dashboards/guild-president.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
(23, 'Computer Lab Manager', NULL, 3, 'computer_lab.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
(24, 'School Bursar', NULL, 3, 'dashboards/school-bursar.php', NULL, '2026-06-09 22:56:09', '2026-06-26 05:57:33'),
(25, 'Store Keeper', 'Store inventory', 0, 'dashboards/storekeeper.php', '{\"store\":true,\"inventory\":true}', '2026-06-13 02:38:49', '2026-06-13 02:38:49'),
(26, 'Director Admissions & Requirements', 'Admissions management', 0, 'dashboards/director-admissions.php', '{\"admissions\":true,\"requirements\":true}', '2026-06-13 02:38:49', '2026-06-13 02:38:49'),
(27, 'Bursar', 'Bursar assistant', 0, 'dashboards/school-bursar.php', '{\"financial\":true,\"fees\":true}', '2026-06-13 02:38:49', '2026-06-26 05:57:33');

-- --------------------------------------------------------

--
-- Table structure for table `staff_salaries`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `staff_salaries` (
  `id` int UNSIGNED NOT NULL,
  `staff_id` int UNSIGNED NOT NULL,
  `basic_salary` decimal(12,2) DEFAULT '0.00',
  `allowances` decimal(12,2) DEFAULT '0.00',
  `overtime_rate` decimal(12,2) DEFAULT '0.00',
  `nssf_tax` decimal(12,2) DEFAULT '0.00',
  `paye_tax` decimal(12,2) DEFAULT '0.00',
  `effective_date` date DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `bonus` decimal(10,2) DEFAULT '0.00',
  `deductions` decimal(10,2) DEFAULT '0.00',
  `net_salary` decimal(12,2) DEFAULT '0.00',
  `payment_month` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `payment_year` varchar(10) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_salaries`
--

INSERT DELAYED IGNORE INTO `staff_salaries` (`id`, `staff_id`, `basic_salary`, `allowances`, `overtime_rate`, `nssf_tax`, `paye_tax`, `effective_date`, `created_by`, `bonus`, `deductions`, `net_salary`, `payment_month`, `payment_year`, `status`, `created_at`, `updated_at`) VALUES
(1, 7, 1500000.00, 0.00, 0.00, 0.00, 0.02, '2026-06-25', 25, 0.00, 0.02, 1499999.98, NULL, NULL, 'Active', '2026-06-25 00:35:20', '2026-06-25 00:35:20');

-- --------------------------------------------------------

--
-- Table structure for table `staff_training`
--
-- Creation: Jun 28, 2026 at 05:57 AM
--

CREATE TABLE IF NOT EXISTS `staff_training` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `training_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `provider` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('enrolled','in_progress','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'enrolled',
  `certificate_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `store_orders`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `store_orders` (
  `id` int NOT NULL,
  `order_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `supplier` varchar(200) COLLATE utf8mb4_general_ci DEFAULT 'Internal Requisition',
  `notes` text COLLATE utf8mb4_general_ci,
  `total_amount` decimal(15,2) DEFAULT '0.00',
  `status` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'pending_approval',
  `requested_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `received_by` int DEFAULT NULL,
  `received_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `store_requests`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `store_requests` (
  `id` int UNSIGNED NOT NULL,
  `request_number` varchar(60) COLLATE utf8mb4_general_ci NOT NULL,
  `requested_by` int UNSIGNED DEFAULT NULL,
  `department` varchar(80) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `items` text COLLATE utf8mb4_general_ci,
  `urgency` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'medium',
  `status` varchar(30) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `forwarded_to` int UNSIGNED DEFAULT NULL,
  `approval_request_id` int UNSIGNED DEFAULT NULL,
  `approved_by` int UNSIGNED DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `store_requests`
--

INSERT DELAYED IGNORE INTO `store_requests` (`id`, `request_number`, `requested_by`, `department`, `items`, `urgency`, `status`, `forwarded_to`, `approval_request_id`, `approved_by`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'SR-2026-0001', 1, NULL, NULL, 'medium', 'pending_approval', NULL, 1, NULL, NULL, '2026-06-08 08:58:56', '2026-06-20 00:47:50'),
(2, 'SR-2026-0002', 1, NULL, NULL, 'urgent', 'pending_approval', NULL, 2, NULL, NULL, '2026-06-10 08:58:56', '2026-06-20 00:47:50'),
(3, 'SR-2026-0003', 1, NULL, NULL, 'medium', 'pending_approval', NULL, 3, NULL, NULL, '2026-06-10 08:58:56', '2026-06-20 00:47:50'),
(4, 'SR-2026-0004', 1, NULL, NULL, 'high', 'pending', NULL, NULL, NULL, NULL, '2026-06-14 08:58:56', NULL),
(5, 'SR-2026-0005', 1, NULL, NULL, 'high', 'pending', NULL, NULL, NULL, NULL, '2026-06-18 08:58:56', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `students`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `students` (
  `id` int UNSIGNED NOT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `student_number` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `full_name` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `program` varchar(120) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `level` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT DELAYED IGNORE INTO `students` (`id`, `first_name`, `last_name`, `student_number`, `full_name`, `program`, `level`, `status`) VALUES
(1, 'Grace', 'Nakato', 'ISNM-2024-001', 'Grace Nakato', 'Diploma Nursing', NULL, 'Active'),
(2, 'David', 'Ssali', 'ISNM-2024-002', 'David Ssali', 'Certificate Midwifery', NULL, 'Active'),
(3, 'Mary', 'Nalwoga', 'ISNM-2024-003', 'Mary Nalwoga', 'Certificate Nursing', NULL, 'Active'),
(4, 'James', 'Okello', 'ISNM-2024-004', 'James Okello', 'Diploma Midwifery', NULL, 'Active'),
(5, 'Sarah', 'Kyomugisha', 'ISNM-2024-005', 'Sarah Kyomugisha', 'Diploma Nursing', NULL, 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `student_academic_profiles`
--
-- Creation: Jun 28, 2026 at 05:57 AM
--

CREATE TABLE IF NOT EXISTS `student_academic_profiles` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gpa` decimal(3,2) DEFAULT NULL,
  `credits_earned` int NOT NULL DEFAULT '0',
  `academic_standing` enum('good','probation','suspension','expelled','graduated') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'good',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_admissions`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `student_admissions` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `admission_date` date NOT NULL,
  `program` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `admission_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'regular',
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_counseling_sessions`
--
-- Creation: Jun 28, 2026 at 05:57 AM
--

CREATE TABLE IF NOT EXISTS `student_counseling_sessions` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `counselor_id` int NOT NULL,
  `session_date` date NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `follow_up_date` date DEFAULT NULL,
  `status` enum('scheduled','completed','cancelled','follow_up') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_discipline`
--
-- Creation: Jun 28, 2026 at 05:57 AM
--

CREATE TABLE IF NOT EXISTS `student_discipline` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `incident_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `action_taken` text COLLATE utf8mb4_unicode_ci,
  `reported_by` int NOT NULL,
  `status` enum('pending','investigating','resolved','escalated') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_discipline_records`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `student_discipline_records` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `incident_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `reported_by` int UNSIGNED DEFAULT NULL,
  `action_taken` text COLLATE utf8mb4_general_ci,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_documents`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `student_documents` (
  `id` int NOT NULL,
  `applicant_id` int NOT NULL,
  `requirement_id` int DEFAULT NULL,
  `document_type` varchar(100) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'Other',
  `document_title` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `file_name` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_general_ci NOT NULL,
  `file_size` int DEFAULT NULL,
  `mime_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `verification_status` enum('Pending','Verified','Rejected') COLLATE utf8mb4_general_ci DEFAULT 'Pending',
  `verified_by` int DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `remarks` text COLLATE utf8mb4_general_ci,
  `document_status` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'Pending',
  `uploaded_by` int NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_emergency_contacts`
--
-- Creation: Jun 28, 2026 at 05:57 AM
--

CREATE TABLE IF NOT EXISTS `student_emergency_contacts` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `contact_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `relationship` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_fees`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `student_fees` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `fee_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT '0.00',
  `balance` decimal(12,2) DEFAULT '0.00',
  `due_date` date DEFAULT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_fee_accounts`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `student_fee_accounts` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `invoice_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_fees` decimal(12,2) DEFAULT '0.00',
  `amount_paid` decimal(12,2) DEFAULT '0.00',
  `balance` decimal(12,2) DEFAULT '0.00',
  `status` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `due_date` date DEFAULT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `semester` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `program` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_fee_assignments`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `student_fee_assignments` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `fee_structure_id` int UNSIGNED DEFAULT NULL,
  `total_amount` decimal(12,2) DEFAULT NULL,
  `amount_paid` decimal(12,2) DEFAULT '0.00',
  `balance` decimal(12,2) DEFAULT '0.00',
  `academic_year` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'active',
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_health_incidents`
--
-- Creation: Jun 28, 2026 at 05:57 AM
--

CREATE TABLE IF NOT EXISTS `student_health_incidents` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `incident_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` enum('low','medium','high','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'low',
  `action_taken` text COLLATE utf8mb4_unicode_ci,
  `recorded_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_health_records`
--
-- Creation: Jun 28, 2026 at 05:57 AM
--

CREATE TABLE IF NOT EXISTS `student_health_records` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `record_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `recorded_by` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_hostel_allocations`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `student_hostel_allocations` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `room_id` int UNSIGNED DEFAULT NULL,
  `allocation_date` date DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'active',
  `checkout_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_invoices`
--
-- Creation: Jun 28, 2026 at 04:21 AM
-- Last update: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `student_invoices` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED DEFAULT NULL,
  `invoice_number` varchar(60) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `amount_paid` decimal(12,2) DEFAULT '0.00',
  `balance` decimal(12,2) NOT NULL DEFAULT '0.00',
  `status` varchar(30) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_invoices`
--

INSERT DELAYED IGNORE INTO `student_invoices` (`id`, `student_id`, `invoice_number`, `total_amount`, `amount_paid`, `balance`, `status`, `due_date`, `created_at`) VALUES
(1, 1, 'INV-2024-001', 1500000.00, 1000000.00, 500000.00, 'partial', '2024-12-31', '2026-06-19 23:59:17'),
(2, 2, 'INV-2024-002', 1200000.00, 1200000.00, 0.00, 'paid', '2024-11-30', '2026-06-19 23:59:17'),
(3, 3, 'INV-2024-003', 1500000.00, 0.00, 1500000.00, 'pending', '2025-01-31', '2026-06-19 23:59:17'),
(4, 4, 'INV-2024-004', 1800000.00, 800000.00, 1000000.00, 'partial', '2025-02-28', '2026-06-19 23:59:17'),
(5, 5, 'INV-2024-005', 1500000.00, 500000.00, 1000000.00, 'partial', '2025-03-31', '2026-06-19 23:59:17');

-- --------------------------------------------------------

--
-- Table structure for table `student_messages`
--
-- Creation: Jun 28, 2026 at 05:57 AM
--

CREATE TABLE IF NOT EXISTS `student_messages` (
  `id` int NOT NULL,
  `sender_id` int NOT NULL,
  `recipient_id` int NOT NULL,
  `subject` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_password_resets`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `student_password_resets` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_penalties`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `student_penalties` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `penalty_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT '0.00',
  `reason` text COLLATE utf8mb4_general_ci,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `waived_by` int UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_profiles`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `student_profiles` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `bio` text COLLATE utf8mb4_general_ci,
  `emergency_contact` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `emergency_phone` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `medical_info` text COLLATE utf8mb4_general_ci,
  `guardian_name` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `guardian_phone` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `guardian_email` varchar(150) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_progression`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `student_progression` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `from_year` int DEFAULT NULL,
  `from_semester` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `to_year` int DEFAULT NULL,
  `to_semester` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cgpa` decimal(4,2) DEFAULT NULL,
  `decision` enum('promoted','probation','repeat','withdrawn','supplementary') COLLATE utf8mb4_general_ci DEFAULT 'promoted',
  `remarks` text COLLATE utf8mb4_general_ci,
  `decided_by` int DEFAULT NULL,
  `decided_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_requests`
--
-- Creation: Jun 28, 2026 at 05:57 AM
--

CREATE TABLE IF NOT EXISTS `student_requests` (
  `id` int NOT NULL,
  `student_id` int NOT NULL,
  `request_type` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','in_progress','completed','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `assigned_to` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_sick_leave`
--
-- Creation: Jun 28, 2026 at 04:21 AM
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
  `recommended_by` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `recommender_title` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `approved_by` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Expired','Extended') COLLATE utf8mb4_general_ci DEFAULT 'Pending',
  `is_deleted` tinyint(1) DEFAULT '0',
  `deleted_at` timestamp NULL DEFAULT NULL,
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
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `student_timetables` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `course_code` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `course_name` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `day_of_week` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `location` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `lecturer` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `semester` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_welfare_cases`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `student_welfare_cases` (
  `id` int UNSIGNED NOT NULL,
  `student_id` int UNSIGNED NOT NULL,
  `case_type` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'open',
  `assigned_to` int UNSIGNED DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `subjects` (
  `id` int UNSIGNED NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `code` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `department_id` int UNSIGNED DEFAULT NULL,
  `credits` int DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscription_deductions`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `subscription_deductions` (
  `id` int UNSIGNED NOT NULL,
  `subscription_id` int UNSIGNED NOT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `deduction_date` date DEFAULT NULL,
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `reference` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `system_logs` (
  `id` int UNSIGNED NOT NULL,
  `log_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `message` text COLLATE utf8mb4_general_ci,
  `user_id` int UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `teachers` (
  `id` int UNSIGNED NOT NULL,
  `staff_id` int UNSIGNED NOT NULL,
  `department_id` int UNSIGNED DEFAULT NULL,
  `specialization` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `qualification` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `employment_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'full-time',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teaching_quality_reviews`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `teaching_quality_reviews` (
  `id` int NOT NULL,
  `lecturer_id` int DEFAULT NULL,
  `review_date` date DEFAULT NULL,
  `teaching_score` decimal(5,2) DEFAULT NULL,
  `course_code` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `observer` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `feedback` text COLLATE utf8mb4_general_ci,
  `status` enum('draft','completed','reviewed') COLLATE utf8mb4_general_ci DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timetables`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `timetables` (
  `id` int UNSIGNED NOT NULL,
  `class_id` int UNSIGNED DEFAULT NULL,
  `subject_id` int UNSIGNED DEFAULT NULL,
  `teacher_id` int UNSIGNED DEFAULT NULL,
  `day_of_week` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `room` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `semester` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transcripts`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `transcripts` (
  `id` int NOT NULL,
  `transcript_number` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `student_id` int NOT NULL,
  `program` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `cgpa` decimal(4,2) DEFAULT '0.00',
  `total_credits` int DEFAULT '0',
  `class_of_degree` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `academic_standing` varchar(100) COLLATE utf8mb4_general_ci DEFAULT 'Good Standing',
  `purpose` text COLLATE utf8mb4_general_ci,
  `status` enum('draft','pending','approved','rejected','generated','issued') COLLATE utf8mb4_general_ci DEFAULT 'draft',
  `requested_by` int DEFAULT NULL,
  `requested_at` datetime DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejected_by` int DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejection_reason` text COLLATE utf8mb4_general_ci,
  `generated_by` int DEFAULT NULL,
  `generated_at` datetime DEFAULT NULL,
  `file_path` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT '0',
  `student_downloadable` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transcript_items`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `transcript_items` (
  `id` int NOT NULL,
  `transcript_id` int NOT NULL,
  `semester` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `course_code` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `course_name` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `credit_units` decimal(4,1) DEFAULT '0.0',
  `marks` decimal(5,2) DEFAULT '0.00',
  `grade` varchar(5) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `grade_points` decimal(4,2) DEFAULT '0.00',
  `semester_gpa` decimal(4,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transcript_templates`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `transcript_templates` (
  `id` int NOT NULL,
  `template_name` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `template_data` longtext COLLATE utf8mb4_general_ci,
  `is_default` tinyint(1) DEFAULT '0',
  `status` enum('active','inactive') COLLATE utf8mb4_general_ci DEFAULT 'active',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ura_reports`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `ura_reports` (
  `id` int NOT NULL,
  `report_name` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `tax_period` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT '0.00',
  `status` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `report_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int UNSIGNED NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(200) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `role` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'staff',
  `status` varchar(30) COLLATE utf8mb4_general_ci DEFAULT 'active',
  `staff_id` int UNSIGNED DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_general_ci,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure for view `fee_payments`
--
DROP TABLE IF EXISTS `fee_payments`;
-- Creation: Jun 28, 2026 at 04:21 AM
--

CREATE OR REPLACE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `fee_payments`  AS SELECT `igangaschoolofl_students_db`.`payments`.`id` AS `id`, `igangaschoolofl_students_db`.`payments`.`student_id` AS `student_id`, `igangaschoolofl_students_db`.`payments`.`invoice_id` AS `fee_account_id`, `igangaschoolofl_students_db`.`payments`.`amount_received` AS `amount_paid`, `igangaschoolofl_students_db`.`payments`.`payment_method` AS `payment_method`, `igangaschoolofl_students_db`.`payments`.`payment_reference` AS `receipt_number`, `igangaschoolofl_students_db`.`payments`.`status` AS `status`, `igangaschoolofl_students_db`.`payments`.`payment_date` AS `payment_date`, `igangaschoolofl_students_db`.`payments`.`notes` AS `notes`, `igangaschoolofl_students_db`.`payments`.`received_by` AS `processed_by`, `igangaschoolofl_students_db`.`payments`.`created_at` AS `created_at`, `igangaschoolofl_students_db`.`payments`.`updated_at` AS `updated_at` FROM `igangaschoolofl_students_db`.`payments` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_analytics`
--
ALTER TABLE `academic_analytics`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `academic_approvals`
--
ALTER TABLE `academic_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_aa_ref` (`reference_type`,`reference_id`),
  ADD KEY `idx_aa_level` (`approval_level`,`status`);

--
-- Indexes for table `academic_audit_logs`
--
ALTER TABLE `academic_audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_aal_action` (`action`),
  ADD KEY `idx_aal_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_aal_staff` (`staff_id`);

--
-- Indexes for table `academic_calendar`
--
ALTER TABLE `academic_calendar`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `calendar_id` (`calendar_id`),
  ADD KEY `academic_year` (`academic_year`),
  ADD KEY `semester` (`semester`);

--
-- Indexes for table `academic_course_catalog`
--
ALTER TABLE `academic_course_catalog`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `department` (`department`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `academic_curriculum_development`
--
ALTER TABLE `academic_curriculum_development`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `academic_programs`
--
ALTER TABLE `academic_programs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `program_code` (`program_code`);

--
-- Indexes for table `academic_records`
--
ALTER TABLE `academic_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_academic_year` (`academic_year`);

--
-- Indexes for table `academic_reports`
--
ALTER TABLE `academic_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `academic_timetable`
--
ALTER TABLE `academic_timetable`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `access_control_logs`
--
ALTER TABLE `access_control_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_access_time` (`access_time`);

--
-- Indexes for table `accreditation_management`
--
ALTER TABLE `accreditation_management`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_module` (`module`);

--
-- Indexes for table `admission_activity_logs`
--
ALTER TABLE `admission_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_aal_user` (`user_id`),
  ADD KEY `idx_aal_module` (`module`),
  ADD KEY `idx_aal_action` (`action`),
  ADD KEY `idx_aal_created` (`created_at`);

--
-- Indexes for table `admission_notifications`
--
ALTER TABLE `admission_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admission_requirements`
--
ALTER TABLE `admission_requirements`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_req_name` (`requirement_name`),
  ADD KEY `idx_req_active` (`is_active`),
  ADD KEY `idx_req_order` (`display_order`);

--
-- Indexes for table `advanced_reports`
--
ALTER TABLE `advanced_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `alerts`
--
ALTER TABLE `alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `alert_recipients`
--
ALTER TABLE `alert_recipients`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `analytics_cache`
--
ALTER TABLE `analytics_cache`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_cache_key` (`cache_key`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ann_active` (`is_active`);

--
-- Indexes for table `api_keys`
--
ALTER TABLE `api_keys`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_api_key` (`api_key`);

--
-- Indexes for table `applicants`
--
ALTER TABLE `applicants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_app_number` (`application_number`),
  ADD KEY `idx_applicant_name` (`full_name`),
  ADD KEY `idx_applicant_phone` (`phone`),
  ADD KEY `idx_applicant_status` (`status`);

--
-- Indexes for table `applicant_messages`
--
ALTER TABLE `applicant_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `applicant_requirement_status`
--
ALTER TABLE `applicant_requirement_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_app_req` (`applicant_id`,`requirement_id`),
  ADD KEY `idx_ars_applicant` (`applicant_id`),
  ADD KEY `idx_ars_requirement` (`requirement_id`),
  ADD KEY `idx_ars_status` (`status`);

--
-- Indexes for table `application_reviews`
--
ALTER TABLE `application_reviews`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `appraisals`
--
ALTER TABLE `appraisals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `appraisal_periods`
--
ALTER TABLE `appraisal_periods`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `appraisal_ratings`
--
ALTER TABLE `appraisal_ratings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `approval_actions`
--
ALTER TABLE `approval_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_request` (`request_id`);

--
-- Indexes for table `approval_requests`
--
ALTER TABLE `approval_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_ref` (`reference_type`,`reference_id`),
  ADD KEY `idx_requester` (`requester_id`);

--
-- Indexes for table `approval_stages`
--
ALTER TABLE `approval_stages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_workflow_stage_order` (`workflow_id`,`stage_order`),
  ADD KEY `idx_workflow` (`workflow_id`);

--
-- Indexes for table `approval_workflows`
--
ALTER TABLE `approval_workflows`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_workflow_name` (`workflow_name`);

--
-- Indexes for table `asset_depreciation`
--
ALTER TABLE `asset_depreciation`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `audit_trail`
--
ALTER TABLE `audit_trail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_at_staff` (`staff_id`),
  ADD KEY `idx_at_action` (`action_type`),
  ADD KEY `idx_at_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_at_created` (`created_at`);

--
-- Indexes for table `backup_management`
--
ALTER TABLE `backup_management`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bank_accounts`
--
ALTER TABLE `bank_accounts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bank_reconciliation`
--
ALTER TABLE `bank_reconciliation`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bank_reconciliations`
--
ALTER TABLE `bank_reconciliations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `budget_lines`
--
ALTER TABLE `budget_lines`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bursar_allowances`
--
ALTER TABLE `bursar_allowances`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bursar_assets`
--
ALTER TABLE `bursar_assets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bursar_bank_reconciliation`
--
ALTER TABLE `bursar_bank_reconciliation`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bursar_budget_items`
--
ALTER TABLE `bursar_budget_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bursar_cashbook`
--
ALTER TABLE `bursar_cashbook`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cashbook_date` (`transaction_date`);

--
-- Indexes for table `bursar_chart_of_accounts`
--
ALTER TABLE `bursar_chart_of_accounts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uqx_coa_code` (`account_code`);

--
-- Indexes for table `bursar_daily_collections`
--
ALTER TABLE `bursar_daily_collections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bursar_deductions`
--
ALTER TABLE `bursar_deductions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bursar_discounts`
--
ALTER TABLE `bursar_discounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bdiscounts_account` (`fee_account_id`);

--
-- Indexes for table `bursar_expenses`
--
ALTER TABLE `bursar_expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bursar_fee_items`
--
ALTER TABLE `bursar_fee_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bursar_fee_reminders`
--
ALTER TABLE `bursar_fee_reminders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bursar_general_ledger`
--
ALTER TABLE `bursar_general_ledger`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_gledger_date` (`entry_date`),
  ADD KEY `idx_gledger_account` (`account_code`);

--
-- Indexes for table `bursar_invoices`
--
ALTER TABLE `bursar_invoices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bursar_payments`
--
ALTER TABLE `bursar_payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bursar_payment_verification`
--
ALTER TABLE `bursar_payment_verification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bverif_status` (`status`),
  ADD KEY `idx_bverif_student` (`student_id`);

--
-- Indexes for table `bursar_payroll`
--
ALTER TABLE `bursar_payroll`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bursar_penalties`
--
ALTER TABLE `bursar_penalties`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bursar_penalty_config`
--
ALTER TABLE `bursar_penalty_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bursar_receipts`
--
ALTER TABLE `bursar_receipts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bursar_requisition_reviews`
--
ALTER TABLE `bursar_requisition_reviews`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bursar_scholarships`
--
ALTER TABLE `bursar_scholarships`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bursar_settings`
--
ALTER TABLE `bursar_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bursar_sponsorships`
--
ALTER TABLE `bursar_sponsorships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bsponsorships_student` (`student_id`);

--
-- Indexes for table `bursar_tax_filings`
--
ALTER TABLE `bursar_tax_filings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_taxfiling_period` (`tax_period_id`);

--
-- Indexes for table `bursar_tax_periods`
--
ALTER TABLE `bursar_tax_periods`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bursar_tax_records`
--
ALTER TABLE `bursar_tax_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bursar_vat_reports`
--
ALTER TABLE `bursar_vat_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bursar_withholding_tax`
--
ALTER TABLE `bursar_withholding_tax`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_wht_date` (`tax_date`);

--
-- Indexes for table `cache_management`
--
ALTER TABLE `cache_management`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cache_key` (`cache_key`);

--
-- Indexes for table `cashbook`
--
ALTER TABLE `cashbook`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_transaction_date` (`transaction_date`);

--
-- Indexes for table `certificates`
--
ALTER TABLE `certificates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `certificate_number` (`certificate_number`),
  ADD KEY `idx_c_student` (`student_id`),
  ADD KEY `idx_c_type` (`certificate_type`),
  ADD KEY `idx_c_status` (`status`);

--
-- Indexes for table `certificate_templates`
--
ALTER TABLE `certificate_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `certificate_uploads`
--
ALTER TABLE `certificate_uploads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cu_student` (`student_id`);

--
-- Indexes for table `certificate_verification`
--
ALTER TABLE `certificate_verification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_v_cert` (`certificate_number`),
  ADD KEY `idx_v_code` (`verification_code`);

--
-- Indexes for table `chemical_inventory`
--
ALTER TABLE `chemical_inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `clinical_assessments`
--
ALTER TABLE `clinical_assessments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ca_student` (`student_id`),
  ADD KEY `idx_ca_placement` (`placement_id`);

--
-- Indexes for table `clinical_placements`
--
ALTER TABLE `clinical_placements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student` (`student_id`);

--
-- Indexes for table `clinical_rotations`
--
ALTER TABLE `clinical_rotations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `communications`
--
ALTER TABLE `communications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `communication_channels`
--
ALTER TABLE `communication_channels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_department_code` (`department_code`);

--
-- Indexes for table `compliance_records`
--
ALTER TABLE `compliance_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `compliance_requirements`
--
ALTER TABLE `compliance_requirements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cr_status` (`status`),
  ADD KEY `idx_cr_due` (`due_date`),
  ADD KEY `idx_cr_category` (`category`);

--
-- Indexes for table `compliance_tracking`
--
ALTER TABLE `compliance_tracking`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cost_centers`
--
ALTER TABLE `cost_centers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `counseling_sessions`
--
ALTER TABLE `counseling_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `course_assignments`
--
ALTER TABLE `course_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lecturer` (`lecturer_id`);

--
-- Indexes for table `course_registrations`
--
ALTER TABLE `course_registrations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `status` (`status`),
  ADD KEY `idx_cr_student` (`student_id`);

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
-- Indexes for table `dashboard_configs`
--
ALTER TABLE `dashboard_configs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dashboard_updates`
--
ALTER TABLE `dashboard_updates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `data_ownership_rules`
--
ALTER TABLE `data_ownership_rules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dor_role` (`role_id`),
  ADD KEY `idx_dor_dept` (`department_code`),
  ADD KEY `idx_dor_category` (`data_category`);

--
-- Indexes for table `data_sync_status`
--
ALTER TABLE `data_sync_status`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `delegation_records`
--
ALTER TABLE `delegation_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `departmental_budgets`
--
ALTER TABLE `departmental_budgets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `department_reviews`
--
ALTER TABLE `department_reviews`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `department_targets`
--
ALTER TABLE `department_targets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dt_dept` (`department_code`),
  ADD KEY `idx_dt_fiscal` (`fiscal_year`),
  ADD KEY `idx_dt_status` (`status`);

--
-- Indexes for table `dg_read_notifications`
--
ALTER TABLE `dg_read_notifications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_nk_uid` (`notification_key`,`user_id`);

--
-- Indexes for table `director_departments`
--
ALTER TABLE `director_departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_dd_role_dept` (`role_id`,`department_code`),
  ADD KEY `idx_dd_role` (`role_id`),
  ADD KEY `idx_dd_dept` (`department_code`);

--
-- Indexes for table `director_news`
--
ALTER TABLE `director_news`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `director_performance_reviews`
--
ALTER TABLE `director_performance_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dpr_staff` (`staff_id`),
  ADD KEY `idx_dpr_fiscal` (`fiscal_year`),
  ADD KEY `idx_dpr_status` (`status`);

--
-- Indexes for table `disciplinary_actions`
--
ALTER TABLE `disciplinary_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`);

--
-- Indexes for table `disciplinary_cases`
--
ALTER TABLE `disciplinary_cases`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `disciplinary_records`
--
ALTER TABLE `disciplinary_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_staff_id` (`staff_id`);

--
-- Indexes for table `document_generation_log`
--
ALTER TABLE `document_generation_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `document_print_configs`
--
ALTER TABLE `document_print_configs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `document_settings`
--
ALTER TABLE `document_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `document_templates`
--
ALTER TABLE `document_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `duty_roster`
--
ALTER TABLE `duty_roster`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `duty_rosters`
--
ALTER TABLE `duty_rosters`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `email_notifications_queue`
--
ALTER TABLE `email_notifications_queue`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `emergency_contacts`
--
ALTER TABLE `emergency_contacts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employment_contracts`
--
ALTER TABLE `employment_contracts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`);

--
-- Indexes for table `employment_details`
--
ALTER TABLE `employment_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`);

--
-- Indexes for table `error_logs`
--
ALTER TABLE `error_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `examination_records`
--
ALTER TABLE `examination_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `course_code` (`course_code`),
  ADD KEY `exam_type` (`exam_type`),
  ADD KEY `idx_exam_student` (`student_id`),
  ADD KEY `idx_exam_course` (`course_code`),
  ADD KEY `idx_exam_status` (`grade_status`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `exam_results`
--
ALTER TABLE `exam_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `exam_id` (`exam_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `exam_schedules`
--
ALTER TABLE `exam_schedules`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `expenditures`
--
ALTER TABLE `expenditures`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_expenses_date` (`expense_date`),
  ADD KEY `idx_expenses_status` (`status`);

--
-- Indexes for table `expense_approvals`
--
ALTER TABLE `expense_approvals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `facilities`
--
ALTER TABLE `facilities`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `facility_bookings`
--
ALTER TABLE `facility_bookings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fee_accounts`
--
ALTER TABLE `fee_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`);

--
-- Indexes for table `fee_adjustments`
--
ALTER TABLE `fee_adjustments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `financial_audit_log`
--
ALTER TABLE `financial_audit_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `financial_messages`
--
ALTER TABLE `financial_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sender` (`sender_id`),
  ADD KEY `idx_recipient` (`recipient_id`),
  ADD KEY `idx_read` (`is_read`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `financial_notices`
--
ALTER TABLE `financial_notices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_published` (`is_published`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `financial_records`
--
ALTER TABLE `financial_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fuel_management`
--
ALTER TABLE `fuel_management`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `generated_documents`
--
ALTER TABLE `generated_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `document_type` (`document_type`);

--
-- Indexes for table `gpa_settings`
--
ALTER TABLE `gpa_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `grade_change_history`
--
ALTER TABLE `grade_change_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `grade_scale`
--
ALTER TABLE `grade_scale`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `grade_letter` (`grade_letter`);

--
-- Indexes for table `grade_scales`
--
ALTER TABLE `grade_scales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_grade` (`grade_letter`);

--
-- Indexes for table `grading_approval_workflow`
--
ALTER TABLE `grading_approval_workflow`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `current_stage` (`current_stage`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `grading_notifications`
--
ALTER TABLE `grading_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `graduation_approvals`
--
ALTER TABLE `graduation_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ga_grad` (`graduation_id`),
  ADD KEY `idx_ga_level` (`approval_level`);

--
-- Indexes for table `graduation_candidates`
--
ALTER TABLE `graduation_candidates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_gc_student` (`student_id`),
  ADD KEY `idx_gc_status` (`status`);

--
-- Indexes for table `health_incidents`
--
ALTER TABLE `health_incidents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`);

--
-- Indexes for table `hostel_management`
--
ALTER TABLE `hostel_management`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_number` (`room_number`),
  ADD KEY `idx_room_number` (`room_number`);

--
-- Indexes for table `hr_activity_log`
--
ALTER TABLE `hr_activity_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hr_activity_logs`
--
ALTER TABLE `hr_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Indexes for table `hr_announcements`
--
ALTER TABLE `hr_announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hr_reports`
--
ALTER TABLE `hr_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hr_settings`
--
ALTER TABLE `hr_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hr_users`
--
ALTER TABLE `hr_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_email` (`email`);

--
-- Indexes for table `incident_reports`
--
ALTER TABLE `incident_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `institutional_alerts`
--
ALTER TABLE `institutional_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ia_priority` (`priority`),
  ADD KEY `idx_ia_resolved` (`is_resolved`),
  ADD KEY `idx_ia_dept` (`department_code`),
  ADD KEY `idx_ia_created` (`created_at`);

--
-- Indexes for table `institutional_risks`
--
ALTER TABLE `institutional_risks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ir_score` (`risk_score`),
  ADD KEY `idx_ir_status` (`status`),
  ADD KEY `idx_ir_category` (`risk_category`),
  ADD KEY `idx_ir_owner` (`owner`);

--
-- Indexes for table `intakes`
--
ALTER TABLE `intakes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `interview_scheduling`
--
ALTER TABLE `interview_scheduling`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_reports`
--
ALTER TABLE `inventory_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_item_id` (`item_id`);

--
-- Indexes for table `invoice_records`
--
ALTER TABLE `invoice_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `it_infrastructure`
--
ALTER TABLE `it_infrastructure`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `job_applications`
--
ALTER TABLE `job_applications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `job_offers`
--
ALTER TABLE `job_offers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `job_vacancies`
--
ALTER TABLE `job_vacancies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `lab_chemical_inventory`
--
ALTER TABLE `lab_chemical_inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lab_equipment_maintenance`
--
ALTER TABLE `lab_equipment_maintenance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lab_experiments`
--
ALTER TABLE `lab_experiments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lab_inventory`
--
ALTER TABLE `lab_inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lab_safety_records`
--
ALTER TABLE `lab_safety_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lab_skills_sessions`
--
ALTER TABLE `lab_skills_sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `late_payment_settings`
--
ALTER TABLE `late_payment_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `leaves`
--
ALTER TABLE `leaves`
  ADD PRIMARY KEY (`s_no`);

--
-- Indexes for table `leave_balance`
--
ALTER TABLE `leave_balance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`);

--
-- Indexes for table `leave_balances`
--
ALTER TABLE `leave_balances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`);

--
-- Indexes for table `leave_requests`
--
ALTER TABLE `leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`);

--
-- Indexes for table `leave_types`
--
ALTER TABLE `leave_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `library_books`
--
ALTER TABLE `library_books`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `library_borrowing`
--
ALTER TABLE `library_borrowing`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `library_digital_resources`
--
ALTER TABLE `library_digital_resources`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `library_fines`
--
ALTER TABLE `library_fines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `library_management`
--
ALTER TABLE `library_management`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `library_members`
--
ALTER TABLE `library_members`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`);

--
-- Indexes for table `library_transactions`
--
ALTER TABLE `library_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_member_id` (`member_id`);

--
-- Indexes for table `meal_tracking`
--
ALTER TABLE `meal_tracking`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `midwifery_antenatal_care`
--
ALTER TABLE `midwifery_antenatal_care`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `midwifery_family_planning`
--
ALTER TABLE `midwifery_family_planning`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `midwifery_labor_delivery`
--
ALTER TABLE `midwifery_labor_delivery`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `midwifery_postnatal_care`
--
ALTER TABLE `midwifery_postnatal_care`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `midwifery_students`
--
ALTER TABLE `midwifery_students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`);

--
-- Indexes for table `national_exam_results`
--
ALTER TABLE `national_exam_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ner_student` (`student_id`),
  ADD KEY `idx_ner_exam` (`exam_type`);

--
-- Indexes for table `news_images`
--
ALTER TABLE `news_images`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `news_subscribers`
--
ALTER TABLE `news_subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_subscriber` (`user_id`,`user_type`),
  ADD KEY `idx_ns_subscribed` (`subscribed`);

--
-- Indexes for table `news_views`
--
ALTER TABLE `news_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_nv_news` (`news_id`),
  ADD KEY `idx_nv_user` (`user_id`,`user_type`),
  ADD KEY `idx_nv_date` (`viewed_at`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notification_reads`
--
ALTER TABLE `notification_reads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `notif_user` (`notification_id`,`user_id`);

--
-- Indexes for table `nursing_clinical_logbook`
--
ALTER TABLE `nursing_clinical_logbook`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `nursing_clinical_placements`
--
ALTER TABLE `nursing_clinical_placements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `nursing_practical_assessment`
--
ALTER TABLE `nursing_practical_assessment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `nursing_skills_training`
--
ALTER TABLE `nursing_skills_training`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `nursing_students`
--
ALTER TABLE `nursing_students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`);

--
-- Indexes for table `official_duties`
--
ALTER TABLE `official_duties`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `onboarding_checklist`
--
ALTER TABLE `onboarding_checklist`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `partnerships`
--
ALTER TABLE `partnerships`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `partner_schools`
--
ALTER TABLE `partner_schools`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `token` (`token`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_payments_date` (`payment_date`),
  ADD KEY `idx_payments_status` (`status`),
  ADD KEY `idx_payments_student` (`student_id`);

--
-- Indexes for table `payment_approvals`
--
ALTER TABLE `payment_approvals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_methods`
--
ALTER TABLE `payment_methods`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_records`
--
ALTER TABLE `payment_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`);

--
-- Indexes for table `payment_routes`
--
ALTER TABLE `payment_routes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_subscriptions`
--
ALTER TABLE `payment_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `payroll_allowances`
--
ALTER TABLE `payroll_allowances`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payroll_allowance_types`
--
ALTER TABLE `payroll_allowance_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `payroll_approvals`
--
ALTER TABLE `payroll_approvals`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_run_level` (`payroll_run_id`,`level`);

--
-- Indexes for table `payroll_bonuses`
--
ALTER TABLE `payroll_bonuses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payroll_deductions`
--
ALTER TABLE `payroll_deductions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payroll_deduction_types`
--
ALTER TABLE `payroll_deduction_types`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_is_active` (`is_active`);

--
-- Indexes for table `payroll_details`
--
ALTER TABLE `payroll_details`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payroll_employees`
--
ALTER TABLE `payroll_employees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `staff_id` (`staff_id`);

--
-- Indexes for table `payroll_employee_allowances`
--
ALTER TABLE `payroll_employee_allowances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_allowance_type_id` (`allowance_type_id`),
  ADD KEY `idx_period` (`month`,`year`);

--
-- Indexes for table `payroll_employee_deductions`
--
ALTER TABLE `payroll_employee_deductions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_deduction_type_id` (`deduction_type_id`),
  ADD KEY `idx_period` (`month`,`year`);

--
-- Indexes for table `payroll_items`
--
ALTER TABLE `payroll_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_staff_period` (`staff_id`,`period_id`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_period_id` (`period_id`);

--
-- Indexes for table `payroll_loans`
--
ALTER TABLE `payroll_loans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `payroll_overtime`
--
ALTER TABLE `payroll_overtime`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payroll_payments`
--
ALTER TABLE `payroll_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_payment_date` (`payment_date`),
  ADD KEY `idx_reference_number` (`reference_number`);

--
-- Indexes for table `payroll_payslips`
--
ALTER TABLE `payroll_payslips`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_staff_period` (`staff_id`,`period_id`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_period_id` (`period_id`);

--
-- Indexes for table `payroll_periods`
--
ALTER TABLE `payroll_periods`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_month_year` (`month`,`year`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `payroll_records`
--
ALTER TABLE `payroll_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_staff_period` (`staff_id`,`month`,`year`),
  ADD KEY `idx_period` (`month`,`year`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `payroll_reports`
--
ALTER TABLE `payroll_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_report_type` (`report_type`),
  ADD KEY `idx_period` (`period_start`,`period_end`),
  ADD KEY `idx_generated_by` (`generated_by`);

--
-- Indexes for table `payroll_runs`
--
ALTER TABLE `payroll_runs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payroll_settings`
--
ALTER TABLE `payroll_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_setting_key` (`setting_key`);

--
-- Indexes for table `payslips`
--
ALTER TABLE `payslips`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `payslip_number` (`payslip_number`),
  ADD KEY `idx_payslip_run` (`payroll_run_id`),
  ADD KEY `idx_payslip_detail` (`payroll_detail_id`);

--
-- Indexes for table `penalty_config`
--
ALTER TABLE `penalty_config`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `penalty_configurations`
--
ALTER TABLE `penalty_configurations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pending_students`
--
ALTER TABLE `pending_students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_approval` (`approval_request_id`);

--
-- Indexes for table `performance_indicators`
--
ALTER TABLE `performance_indicators`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `performance_metrics`
--
ALTER TABLE `performance_metrics`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `performance_reviews`
--
ALTER TABLE `performance_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `portal_messages`
--
ALTER TABLE `portal_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `professional_licenses`
--
ALTER TABLE `professional_licenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `proof_of_payments`
--
ALTER TABLE `proof_of_payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `quality_assurance`
--
ALTER TABLE `quality_assurance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `real_time_updates`
--
ALTER TABLE `real_time_updates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `receipt_templates`
--
ALTER TABLE `receipt_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `recruitment`
--
ALTER TABLE `recruitment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `recruitment_applications`
--
ALTER TABLE `recruitment_applications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `recruitment_jobs`
--
ALTER TABLE `recruitment_jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `recycle_bin`
--
ALTER TABLE `recycle_bin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `registrar_academic_calendar`
--
ALTER TABLE `registrar_academic_calendar`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `registrar_academic_records`
--
ALTER TABLE `registrar_academic_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `registrar_graduation`
--
ALTER TABLE `registrar_graduation`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `registrar_student_registration`
--
ALTER TABLE `registrar_student_registration`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_year` (`academic_year`);

--
-- Indexes for table `registrar_transcripts`
--
ALTER TABLE `registrar_transcripts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `registrar_transcript_requests`
--
ALTER TABLE `registrar_transcript_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `requirement_history`
--
ALTER TABLE `requirement_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rh_applicant` (`applicant_id`),
  ADD KEY `idx_rh_requirement` (`requirement_id`),
  ADD KEY `idx_rh_action` (`action`),
  ADD KEY `idx_rh_created` (`created_at`);

--
-- Indexes for table `research_projects`
--
ALTER TABLE `research_projects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `result_publication`
--
ALTER TABLE `result_publication`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `result_publications`
--
ALTER TABLE `result_publications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rp_status` (`status`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `room_inspections`
--
ALTER TABLE `room_inspections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `scholarships`
--
ALTER TABLE `scholarships`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `security_access_logs`
--
ALTER TABLE `security_access_logs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `security_incidents`
--
ALTER TABLE `security_incidents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `semesters`
--
ALTER TABLE `semesters`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sickbay_settings`
--
ALTER TABLE `sickbay_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

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
-- Indexes for table `sports_events`
--
ALTER TABLE `sports_events`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sports_teams`
--
ALTER TABLE `sports_teams`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `staff_activity_log`
--
ALTER TABLE `staff_activity_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff_appraisals`
--
ALTER TABLE `staff_appraisals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff_attendance`
--
ALTER TABLE `staff_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_attendance_staff_date` (`staff_id`,`date`),
  ADD KEY `idx_attendance_date` (`date`),
  ADD KEY `idx_attendance_status` (`status`);

--
-- Indexes for table `staff_communications`
--
ALTER TABLE `staff_communications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sender_id` (`sender_id`),
  ADD KEY `idx_recipient_type` (`recipient_type`),
  ADD KEY `idx_recipient_id` (`recipient_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `staff_contracts`
--
ALTER TABLE `staff_contracts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `staff_departments`
--
ALTER TABLE `staff_departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_dept_name` (`department_name`);

--
-- Indexes for table `staff_leave_requests`
--
ALTER TABLE `staff_leave_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_dates` (`start_date`,`end_date`);

--
-- Indexes for table `staff_licenses`
--
ALTER TABLE `staff_licenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_expiry_date` (`expiry_date`),
  ADD KEY `idx_license_number` (`license_number`);

--
-- Indexes for table `staff_login_sessions`
--
ALTER TABLE `staff_login_sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff_profiles`
--
ALTER TABLE `staff_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `staff_id` (`staff_id`);

--
-- Indexes for table `staff_resignations`
--
ALTER TABLE `staff_resignations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`);

--
-- Indexes for table `staff_roles`
--
ALTER TABLE `staff_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `staff_salaries`
--
ALTER TABLE `staff_salaries`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff_training`
--
ALTER TABLE `staff_training`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_dates` (`start_date`,`end_date`);

--
-- Indexes for table `store_orders`
--
ALTER TABLE `store_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_orders_status` (`status`);

--
-- Indexes for table `store_requests`
--
ALTER TABLE `store_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_request_number` (`request_number`),
  ADD KEY `idx_store_status` (`status`),
  ADD KEY `idx_store_urgency` (`urgency`),
  ADD KEY `idx_approval` (`approval_request_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_student_number` (`student_number`);

--
-- Indexes for table `student_academic_profiles`
--
ALTER TABLE `student_academic_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_student_year` (`student_id`,`academic_year`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_academic_year` (`academic_year`),
  ADD KEY `idx_academic_standing` (`academic_standing`);

--
-- Indexes for table `student_admissions`
--
ALTER TABLE `student_admissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `idx_sa_student` (`student_id`);

--
-- Indexes for table `student_counseling_sessions`
--
ALTER TABLE `student_counseling_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_counselor_id` (`counselor_id`),
  ADD KEY `idx_session_date` (`session_date`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `student_discipline`
--
ALTER TABLE `student_discipline`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_reported_by` (`reported_by`);

--
-- Indexes for table `student_discipline_records`
--
ALTER TABLE `student_discipline_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `student_documents`
--
ALTER TABLE `student_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_docs_applicant` (`applicant_id`),
  ADD KEY `idx_docs_status` (`verification_status`),
  ADD KEY `idx_doc_requirement` (`requirement_id`);

--
-- Indexes for table `student_emergency_contacts`
--
ALTER TABLE `student_emergency_contacts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_phone` (`phone`);

--
-- Indexes for table `student_fees`
--
ALTER TABLE `student_fees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `student_fee_accounts`
--
ALTER TABLE `student_fee_accounts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `status` (`status`),
  ADD KEY `invoice_number` (`invoice_number`);

--
-- Indexes for table `student_fee_assignments`
--
ALTER TABLE `student_fee_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `student_health_incidents`
--
ALTER TABLE `student_health_incidents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_incident_type` (`incident_type`);

--
-- Indexes for table `student_health_records`
--
ALTER TABLE `student_health_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_record_type` (`record_type`);

--
-- Indexes for table `student_hostel_allocations`
--
ALTER TABLE `student_hostel_allocations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `student_invoices`
--
ALTER TABLE `student_invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_invoices_status` (`status`),
  ADD KEY `idx_invoices_student` (`student_id`);

--
-- Indexes for table `student_messages`
--
ALTER TABLE `student_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sender_id` (`sender_id`),
  ADD KEY `idx_recipient_id` (`recipient_id`),
  ADD KEY `idx_is_read` (`is_read`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `student_password_resets`
--
ALTER TABLE `student_password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `token` (`token`);

--
-- Indexes for table `student_penalties`
--
ALTER TABLE `student_penalties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- Indexes for table `student_progression`
--
ALTER TABLE `student_progression`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sp_student` (`student_id`),
  ADD KEY `idx_sp_decision` (`decision`);

--
-- Indexes for table `student_requests`
--
ALTER TABLE `student_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_id` (`student_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_assigned_to` (`assigned_to`);

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
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `student_welfare_cases`
--
ALTER TABLE `student_welfare_cases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subscription_deductions`
--
ALTER TABLE `subscription_deductions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subscription_id` (`subscription_id`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `log_type` (`log_type`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `teaching_quality_reviews`
--
ALTER TABLE `teaching_quality_reviews`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `timetables`
--
ALTER TABLE `timetables`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transcripts`
--
ALTER TABLE `transcripts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transcript_number` (`transcript_number`),
  ADD KEY `idx_t_student` (`student_id`),
  ADD KEY `idx_t_status` (`status`);

--
-- Indexes for table `transcript_items`
--
ALTER TABLE `transcript_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ti_transcript` (`transcript_id`);

--
-- Indexes for table `transcript_templates`
--
ALTER TABLE `transcript_templates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ura_reports`
--
ALTER TABLE `ura_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `token` (`token`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_analytics`
--
ALTER TABLE `academic_analytics`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `academic_approvals`
--
ALTER TABLE `academic_approvals`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `academic_audit_logs`
--
ALTER TABLE `academic_audit_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `academic_calendar`
--
ALTER TABLE `academic_calendar`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `academic_course_catalog`
--
ALTER TABLE `academic_course_catalog`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `academic_curriculum_development`
--
ALTER TABLE `academic_curriculum_development`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `academic_programs`
--
ALTER TABLE `academic_programs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `academic_records`
--
ALTER TABLE `academic_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `academic_reports`
--
ALTER TABLE `academic_reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `academic_timetable`
--
ALTER TABLE `academic_timetable`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `access_control_logs`
--
ALTER TABLE `access_control_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `accreditation_management`
--
ALTER TABLE `accreditation_management`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admission_activity_logs`
--
ALTER TABLE `admission_activity_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `admission_notifications`
--
ALTER TABLE `admission_notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admission_requirements`
--
ALTER TABLE `admission_requirements`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `advanced_reports`
--
ALTER TABLE `advanced_reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `alerts`
--
ALTER TABLE `alerts`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `alert_recipients`
--
ALTER TABLE `alert_recipients`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `analytics_cache`
--
ALTER TABLE `analytics_cache`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `api_keys`
--
ALTER TABLE `api_keys`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `applicants`
--
ALTER TABLE `applicants`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `applicant_messages`
--
ALTER TABLE `applicant_messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `applicant_requirement_status`
--
ALTER TABLE `applicant_requirement_status`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `application_reviews`
--
ALTER TABLE `application_reviews`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appraisals`
--
ALTER TABLE `appraisals`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appraisal_periods`
--
ALTER TABLE `appraisal_periods`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appraisal_ratings`
--
ALTER TABLE `appraisal_ratings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `approval_actions`
--
ALTER TABLE `approval_actions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `approval_requests`
--
ALTER TABLE `approval_requests`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `approval_stages`
--
ALTER TABLE `approval_stages`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=158;

--
-- AUTO_INCREMENT for table `approval_workflows`
--
ALTER TABLE `approval_workflows`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=132;

--
-- AUTO_INCREMENT for table `asset_depreciation`
--
ALTER TABLE `asset_depreciation`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_trail`
--
ALTER TABLE `audit_trail`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `backup_management`
--
ALTER TABLE `backup_management`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bank_accounts`
--
ALTER TABLE `bank_accounts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bank_reconciliation`
--
ALTER TABLE `bank_reconciliation`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bank_reconciliations`
--
ALTER TABLE `bank_reconciliations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budget_lines`
--
ALTER TABLE `budget_lines`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_allowances`
--
ALTER TABLE `bursar_allowances`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_assets`
--
ALTER TABLE `bursar_assets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_bank_reconciliation`
--
ALTER TABLE `bursar_bank_reconciliation`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_budget_items`
--
ALTER TABLE `bursar_budget_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_cashbook`
--
ALTER TABLE `bursar_cashbook`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_chart_of_accounts`
--
ALTER TABLE `bursar_chart_of_accounts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_daily_collections`
--
ALTER TABLE `bursar_daily_collections`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_deductions`
--
ALTER TABLE `bursar_deductions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_discounts`
--
ALTER TABLE `bursar_discounts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_expenses`
--
ALTER TABLE `bursar_expenses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_fee_items`
--
ALTER TABLE `bursar_fee_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_fee_reminders`
--
ALTER TABLE `bursar_fee_reminders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_general_ledger`
--
ALTER TABLE `bursar_general_ledger`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_invoices`
--
ALTER TABLE `bursar_invoices`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_payments`
--
ALTER TABLE `bursar_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_payment_verification`
--
ALTER TABLE `bursar_payment_verification`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_payroll`
--
ALTER TABLE `bursar_payroll`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_penalties`
--
ALTER TABLE `bursar_penalties`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_penalty_config`
--
ALTER TABLE `bursar_penalty_config`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_receipts`
--
ALTER TABLE `bursar_receipts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_requisition_reviews`
--
ALTER TABLE `bursar_requisition_reviews`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_scholarships`
--
ALTER TABLE `bursar_scholarships`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_settings`
--
ALTER TABLE `bursar_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_sponsorships`
--
ALTER TABLE `bursar_sponsorships`
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
-- AUTO_INCREMENT for table `bursar_tax_records`
--
ALTER TABLE `bursar_tax_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_vat_reports`
--
ALTER TABLE `bursar_vat_reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_withholding_tax`
--
ALTER TABLE `bursar_withholding_tax`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cache_management`
--
ALTER TABLE `cache_management`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cashbook`
--
ALTER TABLE `cashbook`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `certificates`
--
ALTER TABLE `certificates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `certificate_templates`
--
ALTER TABLE `certificate_templates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `certificate_uploads`
--
ALTER TABLE `certificate_uploads`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `certificate_verification`
--
ALTER TABLE `certificate_verification`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chemical_inventory`
--
ALTER TABLE `chemical_inventory`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clinical_assessments`
--
ALTER TABLE `clinical_assessments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clinical_placements`
--
ALTER TABLE `clinical_placements`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clinical_rotations`
--
ALTER TABLE `clinical_rotations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `communications`
--
ALTER TABLE `communications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `communication_channels`
--
ALTER TABLE `communication_channels`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `compliance_records`
--
ALTER TABLE `compliance_records`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `compliance_requirements`
--
ALTER TABLE `compliance_requirements`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `compliance_tracking`
--
ALTER TABLE `compliance_tracking`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cost_centers`
--
ALTER TABLE `cost_centers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `counseling_sessions`
--
ALTER TABLE `counseling_sessions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_assignments`
--
ALTER TABLE `course_assignments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_registrations`
--
ALTER TABLE `course_registrations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `daily_sick_records`
--
ALTER TABLE `daily_sick_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dashboard_configs`
--
ALTER TABLE `dashboard_configs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dashboard_updates`
--
ALTER TABLE `dashboard_updates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `data_ownership_rules`
--
ALTER TABLE `data_ownership_rules`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `data_sync_status`
--
ALTER TABLE `data_sync_status`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `delegation_records`
--
ALTER TABLE `delegation_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departmental_budgets`
--
ALTER TABLE `departmental_budgets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `department_reviews`
--
ALTER TABLE `department_reviews`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `department_targets`
--
ALTER TABLE `department_targets`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dg_read_notifications`
--
ALTER TABLE `dg_read_notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `director_departments`
--
ALTER TABLE `director_departments`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `director_news`
--
ALTER TABLE `director_news`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `director_performance_reviews`
--
ALTER TABLE `director_performance_reviews`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `disciplinary_actions`
--
ALTER TABLE `disciplinary_actions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `disciplinary_cases`
--
ALTER TABLE `disciplinary_cases`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `disciplinary_records`
--
ALTER TABLE `disciplinary_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document_generation_log`
--
ALTER TABLE `document_generation_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document_print_configs`
--
ALTER TABLE `document_print_configs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document_settings`
--
ALTER TABLE `document_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document_templates`
--
ALTER TABLE `document_templates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `duty_roster`
--
ALTER TABLE `duty_roster`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `duty_rosters`
--
ALTER TABLE `duty_rosters`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `email_notifications_queue`
--
ALTER TABLE `email_notifications_queue`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `emergency_contacts`
--
ALTER TABLE `emergency_contacts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employment_contracts`
--
ALTER TABLE `employment_contracts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employment_details`
--
ALTER TABLE `employment_details`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `error_logs`
--
ALTER TABLE `error_logs`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `examination_records`
--
ALTER TABLE `examination_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_results`
--
ALTER TABLE `exam_results`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_schedules`
--
ALTER TABLE `exam_schedules`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenditures`
--
ALTER TABLE `expenditures`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `expense_approvals`
--
ALTER TABLE `expense_approvals`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `facilities`
--
ALTER TABLE `facilities`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `facility_bookings`
--
ALTER TABLE `facility_bookings`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_accounts`
--
ALTER TABLE `fee_accounts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_adjustments`
--
ALTER TABLE `fee_adjustments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `financial_audit_log`
--
ALTER TABLE `financial_audit_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `financial_messages`
--
ALTER TABLE `financial_messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `financial_notices`
--
ALTER TABLE `financial_notices`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `financial_records`
--
ALTER TABLE `financial_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fuel_management`
--
ALTER TABLE `fuel_management`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `generated_documents`
--
ALTER TABLE `generated_documents`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gpa_settings`
--
ALTER TABLE `gpa_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grade_change_history`
--
ALTER TABLE `grade_change_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grade_scale`
--
ALTER TABLE `grade_scale`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `grade_scales`
--
ALTER TABLE `grade_scales`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grading_approval_workflow`
--
ALTER TABLE `grading_approval_workflow`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `grading_notifications`
--
ALTER TABLE `grading_notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `graduation_approvals`
--
ALTER TABLE `graduation_approvals`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `graduation_candidates`
--
ALTER TABLE `graduation_candidates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `health_incidents`
--
ALTER TABLE `health_incidents`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hostel_management`
--
ALTER TABLE `hostel_management`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hr_activity_log`
--
ALTER TABLE `hr_activity_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hr_activity_logs`
--
ALTER TABLE `hr_activity_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hr_announcements`
--
ALTER TABLE `hr_announcements`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hr_reports`
--
ALTER TABLE `hr_reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hr_settings`
--
ALTER TABLE `hr_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hr_users`
--
ALTER TABLE `hr_users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `incident_reports`
--
ALTER TABLE `incident_reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `institutional_alerts`
--
ALTER TABLE `institutional_alerts`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `institutional_risks`
--
ALTER TABLE `institutional_risks`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `intakes`
--
ALTER TABLE `intakes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `interview_scheduling`
--
ALTER TABLE `interview_scheduling`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_reports`
--
ALTER TABLE `inventory_reports`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoice_records`
--
ALTER TABLE `invoice_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `it_infrastructure`
--
ALTER TABLE `it_infrastructure`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_applications`
--
ALTER TABLE `job_applications`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_offers`
--
ALTER TABLE `job_offers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_vacancies`
--
ALTER TABLE `job_vacancies`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_chemical_inventory`
--
ALTER TABLE `lab_chemical_inventory`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_equipment_maintenance`
--
ALTER TABLE `lab_equipment_maintenance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_experiments`
--
ALTER TABLE `lab_experiments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_inventory`
--
ALTER TABLE `lab_inventory`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_safety_records`
--
ALTER TABLE `lab_safety_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_skills_sessions`
--
ALTER TABLE `lab_skills_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `late_payment_settings`
--
ALTER TABLE `late_payment_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leaves`
--
ALTER TABLE `leaves`
  MODIFY `s_no` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_balance`
--
ALTER TABLE `leave_balance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_balances`
--
ALTER TABLE `leave_balances`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_requests`
--
ALTER TABLE `leave_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leave_types`
--
ALTER TABLE `leave_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_books`
--
ALTER TABLE `library_books`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_borrowing`
--
ALTER TABLE `library_borrowing`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_digital_resources`
--
ALTER TABLE `library_digital_resources`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_fines`
--
ALTER TABLE `library_fines`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_management`
--
ALTER TABLE `library_management`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_members`
--
ALTER TABLE `library_members`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_transactions`
--
ALTER TABLE `library_transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `meal_tracking`
--
ALTER TABLE `meal_tracking`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicine_stock`
--
ALTER TABLE `medicine_stock`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `medicine_stock_transactions`
--
ALTER TABLE `medicine_stock_transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `midwifery_antenatal_care`
--
ALTER TABLE `midwifery_antenatal_care`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `midwifery_family_planning`
--
ALTER TABLE `midwifery_family_planning`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `midwifery_labor_delivery`
--
ALTER TABLE `midwifery_labor_delivery`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `midwifery_postnatal_care`
--
ALTER TABLE `midwifery_postnatal_care`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `midwifery_students`
--
ALTER TABLE `midwifery_students`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `national_exam_results`
--
ALTER TABLE `national_exam_results`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `news_images`
--
ALTER TABLE `news_images`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `news_subscribers`
--
ALTER TABLE `news_subscribers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `news_views`
--
ALTER TABLE `news_views`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_reads`
--
ALTER TABLE `notification_reads`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nursing_clinical_logbook`
--
ALTER TABLE `nursing_clinical_logbook`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nursing_clinical_placements`
--
ALTER TABLE `nursing_clinical_placements`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nursing_practical_assessment`
--
ALTER TABLE `nursing_practical_assessment`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nursing_skills_training`
--
ALTER TABLE `nursing_skills_training`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `nursing_students`
--
ALTER TABLE `nursing_students`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `official_duties`
--
ALTER TABLE `official_duties`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `onboarding_checklist`
--
ALTER TABLE `onboarding_checklist`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `partnerships`
--
ALTER TABLE `partnerships`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `partner_schools`
--
ALTER TABLE `partner_schools`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT for table `payment_approvals`
--
ALTER TABLE `payment_approvals`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_methods`
--
ALTER TABLE `payment_methods`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_records`
--
ALTER TABLE `payment_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_routes`
--
ALTER TABLE `payment_routes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_subscriptions`
--
ALTER TABLE `payment_subscriptions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_allowances`
--
ALTER TABLE `payroll_allowances`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_allowance_types`
--
ALTER TABLE `payroll_allowance_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `payroll_approvals`
--
ALTER TABLE `payroll_approvals`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_bonuses`
--
ALTER TABLE `payroll_bonuses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_deductions`
--
ALTER TABLE `payroll_deductions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_deduction_types`
--
ALTER TABLE `payroll_deduction_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `payroll_details`
--
ALTER TABLE `payroll_details`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_employees`
--
ALTER TABLE `payroll_employees`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_employee_allowances`
--
ALTER TABLE `payroll_employee_allowances`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_employee_deductions`
--
ALTER TABLE `payroll_employee_deductions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_items`
--
ALTER TABLE `payroll_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_loans`
--
ALTER TABLE `payroll_loans`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_overtime`
--
ALTER TABLE `payroll_overtime`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_payments`
--
ALTER TABLE `payroll_payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_payslips`
--
ALTER TABLE `payroll_payslips`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_periods`
--
ALTER TABLE `payroll_periods`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_records`
--
ALTER TABLE `payroll_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `payroll_reports`
--
ALTER TABLE `payroll_reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_runs`
--
ALTER TABLE `payroll_runs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_settings`
--
ALTER TABLE `payroll_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `payslips`
--
ALTER TABLE `payslips`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `penalty_config`
--
ALTER TABLE `penalty_config`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `penalty_configurations`
--
ALTER TABLE `penalty_configurations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pending_students`
--
ALTER TABLE `pending_students`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `performance_indicators`
--
ALTER TABLE `performance_indicators`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `performance_metrics`
--
ALTER TABLE `performance_metrics`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `performance_reviews`
--
ALTER TABLE `performance_reviews`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `portal_messages`
--
ALTER TABLE `portal_messages`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `professional_licenses`
--
ALTER TABLE `professional_licenses`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `quality_assurance`
--
ALTER TABLE `quality_assurance`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `real_time_updates`
--
ALTER TABLE `real_time_updates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `receipt_templates`
--
ALTER TABLE `receipt_templates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recruitment`
--
ALTER TABLE `recruitment`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recruitment_applications`
--
ALTER TABLE `recruitment_applications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recruitment_jobs`
--
ALTER TABLE `recruitment_jobs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recycle_bin`
--
ALTER TABLE `recycle_bin`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registrar_academic_calendar`
--
ALTER TABLE `registrar_academic_calendar`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registrar_academic_records`
--
ALTER TABLE `registrar_academic_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registrar_graduation`
--
ALTER TABLE `registrar_graduation`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registrar_student_registration`
--
ALTER TABLE `registrar_student_registration`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registrar_transcripts`
--
ALTER TABLE `registrar_transcripts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registrar_transcript_requests`
--
ALTER TABLE `registrar_transcript_requests`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `requirement_history`
--
ALTER TABLE `requirement_history`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `research_projects`
--
ALTER TABLE `research_projects`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `result_publication`
--
ALTER TABLE `result_publication`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `result_publications`
--
ALTER TABLE `result_publications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `room_inspections`
--
ALTER TABLE `room_inspections`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `scholarships`
--
ALTER TABLE `scholarships`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_access_logs`
--
ALTER TABLE `security_access_logs`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `security_incidents`
--
ALTER TABLE `security_incidents`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `semesters`
--
ALTER TABLE `semesters`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sickbay_settings`
--
ALTER TABLE `sickbay_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `sickness_directory`
--
ALTER TABLE `sickness_directory`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `sports_events`
--
ALTER TABLE `sports_events`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sports_teams`
--
ALTER TABLE `sports_teams`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `staff_activity_log`
--
ALTER TABLE `staff_activity_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=229;

--
-- AUTO_INCREMENT for table `staff_appraisals`
--
ALTER TABLE `staff_appraisals`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_attendance`
--
ALTER TABLE `staff_attendance`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `staff_communications`
--
ALTER TABLE `staff_communications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_contracts`
--
ALTER TABLE `staff_contracts`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_departments`
--
ALTER TABLE `staff_departments`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `staff_leave_requests`
--
ALTER TABLE `staff_leave_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_licenses`
--
ALTER TABLE `staff_licenses`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_login_sessions`
--
ALTER TABLE `staff_login_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=129;

--
-- AUTO_INCREMENT for table `staff_profiles`
--
ALTER TABLE `staff_profiles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `staff_resignations`
--
ALTER TABLE `staff_resignations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_roles`
--
ALTER TABLE `staff_roles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_salaries`
--
ALTER TABLE `staff_salaries`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `staff_training`
--
ALTER TABLE `staff_training`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `store_orders`
--
ALTER TABLE `store_orders`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `store_requests`
--
ALTER TABLE `store_requests`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `student_academic_profiles`
--
ALTER TABLE `student_academic_profiles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_admissions`
--
ALTER TABLE `student_admissions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_counseling_sessions`
--
ALTER TABLE `student_counseling_sessions`
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
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_documents`
--
ALTER TABLE `student_documents`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_emergency_contacts`
--
ALTER TABLE `student_emergency_contacts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_fees`
--
ALTER TABLE `student_fees`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_fee_accounts`
--
ALTER TABLE `student_fee_accounts`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_fee_assignments`
--
ALTER TABLE `student_fee_assignments`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_health_incidents`
--
ALTER TABLE `student_health_incidents`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_health_records`
--
ALTER TABLE `student_health_records`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_hostel_allocations`
--
ALTER TABLE `student_hostel_allocations`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_invoices`
--
ALTER TABLE `student_invoices`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `student_messages`
--
ALTER TABLE `student_messages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_password_resets`
--
ALTER TABLE `student_password_resets`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_penalties`
--
ALTER TABLE `student_penalties`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_profiles`
--
ALTER TABLE `student_profiles`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_progression`
--
ALTER TABLE `student_progression`
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
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_welfare_cases`
--
ALTER TABLE `student_welfare_cases`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscription_deductions`
--
ALTER TABLE `subscription_deductions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teaching_quality_reviews`
--
ALTER TABLE `teaching_quality_reviews`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timetables`
--
ALTER TABLE `timetables`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transcripts`
--
ALTER TABLE `transcripts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transcript_items`
--
ALTER TABLE `transcript_items`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transcript_templates`
--
ALTER TABLE `transcript_templates`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ura_reports`
--
ALTER TABLE `ura_reports`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applicant_requirement_status`
--
ALTER TABLE `applicant_requirement_status`
  ADD CONSTRAINT `applicant_requirement_status_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `applicant_requirement_status_ibfk_2` FOREIGN KEY (`requirement_id`) REFERENCES `admission_requirements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `daily_sick_records`
--
ALTER TABLE `daily_sick_records`
  ADD CONSTRAINT `daily_sick_records_ibfk_1` FOREIGN KEY (`sickness_id`) REFERENCES `sickness_directory` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `medicine_stock_transactions`
--
ALTER TABLE `medicine_stock_transactions`
  ADD CONSTRAINT `medicine_stock_transactions_ibfk_1` FOREIGN KEY (`medicine_id`) REFERENCES `medicine_stock` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payroll_employee_allowances`
--
ALTER TABLE `payroll_employee_allowances`
  ADD CONSTRAINT `fk_ea_allowance_type` FOREIGN KEY (`allowance_type_id`) REFERENCES `payroll_allowance_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `payroll_employee_deductions`
--
ALTER TABLE `payroll_employee_deductions`
  ADD CONSTRAINT `fk_ed_deduction_type` FOREIGN KEY (`deduction_type_id`) REFERENCES `payroll_deduction_types` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `requirement_history`
--
ALTER TABLE `requirement_history`
  ADD CONSTRAINT `requirement_history_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `requirement_history_ibfk_2` FOREIGN KEY (`requirement_id`) REFERENCES `admission_requirements` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `staff_profiles`
--
ALTER TABLE `staff_profiles`
  ADD CONSTRAINT `staff_profiles_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_documents`
--
ALTER TABLE `student_documents`
  ADD CONSTRAINT `student_documents_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_sick_leave`
--
ALTER TABLE `student_sick_leave`
  ADD CONSTRAINT `student_sick_leave_ibfk_1` FOREIGN KEY (`sickness_id`) REFERENCES `sickness_directory` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints for table `transcript_items`
--
ALTER TABLE `transcript_items`
  ADD CONSTRAINT `fk_ti_transcript` FOREIGN KEY (`transcript_id`) REFERENCES `transcripts` (`id`) ON DELETE CASCADE;
SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
