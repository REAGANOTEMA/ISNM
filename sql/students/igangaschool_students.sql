-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 22, 2026 at 04:59 PM
-- Server version: 10.11.18-MariaDB
-- PHP Version: 8.4.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `igangaschool_students`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`igangaschool`@`localhost` PROCEDURE `AddColIfMissing` (IN `p_schema` VARCHAR(255), IN `p_table` VARCHAR(255), IN `p_col` VARCHAR(255), IN `p_def` TEXT)   BEGIN
    DECLARE cnt INT DEFAULT 0;
    SELECT COUNT(*) INTO cnt FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = p_schema AND TABLE_NAME = p_table AND COLUMN_NAME = p_col;
    IF cnt = 0 THEN
        SET @s = CONCAT('ALTER TABLE `', p_schema, '`.`', p_table, '` ADD COLUMN `', p_col, '` ', p_def);
        PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END$$

CREATE DEFINER=`igangaschool`@`localhost` PROCEDURE `MigratePayroll` ()   BEGIN
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payroll_runs' AND COLUMN_NAME='total_paye') THEN
        ALTER TABLE `payroll_runs` ADD COLUMN `total_paye` DECIMAL(15,2) DEFAULT 0.00 AFTER `total_gross`;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payroll_runs' AND COLUMN_NAME='total_nssf') THEN
        ALTER TABLE `payroll_runs` ADD COLUMN `total_nssf` DECIMAL(15,2) DEFAULT 0.00 AFTER `total_paye`;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payroll_runs' AND COLUMN_NAME='run_date') THEN
        ALTER TABLE `payroll_runs` ADD COLUMN `run_date` DATE DEFAULT NULL AFTER `end_date`;
    END IF;
    
    ALTER TABLE `payroll_runs` MODIFY COLUMN `status` ENUM('draft','approved','processed','paid','completed','processing') DEFAULT 'draft';

    
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payroll_details' AND COLUMN_NAME='housing_allowance') THEN
        ALTER TABLE `payroll_details` ADD COLUMN `housing_allowance` DECIMAL(12,2) DEFAULT 0.00 AFTER `basic_salary`;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payroll_details' AND COLUMN_NAME='transport_allowance') THEN
        ALTER TABLE `payroll_details` ADD COLUMN `transport_allowance` DECIMAL(12,2) DEFAULT 0.00 AFTER `housing_allowance`;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payroll_details' AND COLUMN_NAME='`status`') THEN
        ALTER TABLE `payroll_details` ADD COLUMN `status` VARCHAR(20) DEFAULT 'calculated' AFTER `payment_status`;
    END IF;

    
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payroll_employees' AND COLUMN_NAME='housing_allowance') THEN
        ALTER TABLE `payroll_employees` ADD COLUMN `housing_allowance` DECIMAL(12,2) DEFAULT 0.00 AFTER `basic_salary`;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payroll_employees' AND COLUMN_NAME='transport_allowance') THEN
        ALTER TABLE `payroll_employees` ADD COLUMN `transport_allowance` DECIMAL(12,2) DEFAULT 0.00 AFTER `housing_allowance`;
    END IF;

    
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='salary_structures' AND COLUMN_NAME='staff_id') THEN
        ALTER TABLE `salary_structures` ADD COLUMN `staff_id` INT(11) DEFAULT NULL AFTER `id`;
        ALTER TABLE `salary_structures` ADD INDEX `idx_ss_staff_id` (`staff_id`);
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='salary_structures' AND COLUMN_NAME='housing_allowance') THEN
        ALTER TABLE `salary_structures` ADD COLUMN `housing_allowance` DECIMAL(12,2) DEFAULT 0.00 AFTER `base_salary`;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='salary_structures' AND COLUMN_NAME='transport_allowance') THEN
        ALTER TABLE `salary_structures` ADD COLUMN `transport_allowance` DECIMAL(12,2) DEFAULT 0.00 AFTER `housing_allowance`;
    END IF;

    
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='bursar_vat_reports' AND COLUMN_NAME='created_at') THEN
        ALTER TABLE `bursar_vat_reports` ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `status`;
    END IF;

    
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='bursar_withholding_tax' AND COLUMN_NAME='period') THEN
        ALTER TABLE `bursar_withholding_tax` ADD COLUMN `period` VARCHAR(20) DEFAULT NULL AFTER `tax_date`;
    END IF;

    
    ALTER TABLE `payroll_approvals` MODIFY COLUMN `level` ENUM('HR','PayrollOfficer','Bursar','DirectorFinance','CEO') NOT NULL;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `academic_programs`
--

CREATE TABLE `academic_programs` (
  `id` int(11) NOT NULL,
  `program_code` varchar(20) NOT NULL,
  `program_name` varchar(255) NOT NULL,
  `program_type` enum('Certificate','Diploma','Degree','Short Course') NOT NULL DEFAULT 'Diploma',
  `department` varchar(100) DEFAULT NULL,
  `duration_years` decimal(3,1) NOT NULL DEFAULT 2.0,
  `total_fee` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `academic_records`
--

CREATE TABLE `academic_records` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_code` varchar(50) DEFAULT NULL,
  `course_name` varchar(300) DEFAULT NULL,
  `credit_units` int(11) DEFAULT 0,
  `score` decimal(5,2) DEFAULT NULL,
  `grade` varchar(10) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `academic_registrar_activity_log`
--

CREATE TABLE `academic_registrar_activity_log` (
  `id` int(11) NOT NULL,
  `activity` text NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` varchar(50) NOT NULL,
  `s_no` int(11) NOT NULL,
  `fname` varchar(100) DEFAULT NULL,
  `lname` varchar(100) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `image` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admission_activity_logs`
--

CREATE TABLE `admission_activity_logs` (
  `id` int(11) NOT NULL,
  `applicant_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admission_communications`
--

CREATE TABLE `admission_communications` (
  `id` int(11) NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `communication_type` enum('Email','SMS','Portal','WhatsApp','Internal Note') NOT NULL DEFAULT 'Portal',
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('Sent','Delivered','Read','Failed') DEFAULT 'Sent',
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admission_decisions`
--

CREATE TABLE `admission_decisions` (
  `id` int(11) NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `decision` enum('Approved','Rejected','Deferred','Waitlisted') NOT NULL,
  `decision_reason` text DEFAULT NULL,
  `decided_by` int(11) DEFAULT NULL,
  `decided_at` timestamp NULL DEFAULT NULL,
  `notified_applicant` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admission_interviews`
--

CREATE TABLE `admission_interviews` (
  `id` int(11) NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `interviewer_id` int(11) DEFAULT NULL,
  `interview_date` datetime NOT NULL,
  `interview_mode` enum('In-Person','Online','Phone') NOT NULL DEFAULT 'In-Person',
  `interview_link` varchar(500) DEFAULT NULL,
  `interview_score` decimal(5,2) DEFAULT NULL,
  `interview_outcome` enum('Pass','Fail','Pending','Reschedule') DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `recommendation` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admission_notifications`
--

CREATE TABLE `admission_notifications` (
  `id` int(11) NOT NULL,
  `applicant_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `type` enum('info','success','warning','danger') NOT NULL DEFAULT 'info',
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `link` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admission_requirements`
--

CREATE TABLE `admission_requirements` (
  `id` int(11) NOT NULL,
  `requirement_name` varchar(255) NOT NULL,
  `type` enum('Document','Certificate','ID','Photo','Form','Other') NOT NULL DEFAULT 'Document',
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_mandatory` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `target_audience` enum('All','Nursing','Midwifery','Year1','Year2','Year3','Staff') DEFAULT 'All',
  `priority` enum('Normal','High','Urgent') DEFAULT 'Normal',
  `posted_by` int(11) DEFAULT NULL,
  `expires_at` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `body`, `target_audience`, `priority`, `posted_by`, `expires_at`, `is_active`, `created_at`) VALUES
(1, 'Welcome to New Academic Year 2024/2025', 'We welcome all students and staff to the new academic year. Registration is now open for all programs. Please complete your registration before the deadline.', 'All', 'High', 5, '2025-03-31', 1, '2026-07-03 04:38:06'),
(2, 'Semester 1 Examination Schedule Released', 'The examination timetable for Semester 1 has been released. All students should check their examination dates and venues. Examinations begin on 10th December 2024.', 'All', 'High', 7, '2025-01-15', 1, '2026-07-03 04:38:06'),
(3, 'Clinical Placement Guidelines', 'All Diploma Year 2 and Year 3 students scheduled for clinical placements must attend the orientation session on Friday 15th November 2024. Bring your clinical gear.', '', 'Normal', 3, '2025-01-31', 1, '2026-07-03 04:38:06'),
(4, 'Staff Training Workshop', 'All staff members are invited to a capacity building workshop on ICT Skills for Education on 20th November 2024. Attendance is mandatory.', 'Staff', 'Normal', 23, '2025-01-15', 1, '2026-07-03 04:38:06'),
(5, 'Fee Payment Deadline Reminder', 'Students with outstanding fees are reminded that the deadline for Semester 1 fee payment is 30th September 2024. Defaulters will not be allowed to sit for examinations.', '', 'Urgent', 25, '2024-10-31', 1, '2026-07-03 04:38:06'),
(6, 'Library Hours Extended During Exams', 'The library will extend its operating hours during the examination period. The library will now be open from 7:00 AM to 9:00 PM on weekdays.', 'All', '', 10, '2025-01-15', 1, '2026-07-03 04:38:06'),
(7, 'Health and Safety Protocols', 'All students and staff are reminded to follow the health and safety protocols at all times. Hand washing stations are available at all entry points.', 'All', 'Normal', 5, '2025-06-30', 1, '2026-07-03 04:38:06'),
(8, 'Sports Week Activities', 'The annual sports week will be held from 18th to 22nd November 2024. All students are encouraged to participate. Registration at the Guild Office.', '', '', 21, '2025-01-31', 1, '2026-07-03 04:38:06'),
(9, 'Nursing Council Registration Update', 'Final year students are reminded to complete their Nursing and Midwifery Council registration. The deadline has been extended to 31st January 2025.', '', 'High', 7, '2025-02-28', 1, '2026-07-03 04:38:06'),
(10, 'Holiday Notice - Christmas Break', 'The institution will close for Christmas break on 20th December 2024 and reopen on 6th January 2025. Merry Christmas and Happy New Year!', 'All', '', 5, '2025-01-31', 1, '2026-07-03 04:38:06'),
(11, 'Welcome to New Academic Year 2024/2025', 'We welcome all students and staff to the new academic year. Registration is now open for all programs. Please complete your registration before the deadline.', 'All', 'High', 5, '2025-03-31', 1, '2026-07-03 04:51:14'),
(12, 'Semester 1 Examination Schedule Released', 'The examination timetable for Semester 1 has been released. All students should check their examination dates and venues. Examinations begin on 10th December 2024.', 'All', 'High', 7, '2025-01-15', 1, '2026-07-03 04:51:14'),
(13, 'Clinical Placement Guidelines', 'All Diploma Year 2 and Year 3 students scheduled for clinical placements must attend the orientation session on Friday 15th November 2024. Bring your clinical gear.', '', 'Normal', 3, '2025-01-31', 1, '2026-07-03 04:51:14'),
(14, 'Staff Training Workshop', 'All staff members are invited to a capacity building workshop on ICT Skills for Education on 20th November 2024. Attendance is mandatory.', 'Staff', 'Normal', 23, '2025-01-15', 1, '2026-07-03 04:51:14'),
(15, 'Fee Payment Deadline Reminder', 'Students with outstanding fees are reminded that the deadline for Semester 1 fee payment is 30th September 2024. Defaulters will not be allowed to sit for examinations.', '', 'Urgent', 25, '2024-10-31', 1, '2026-07-03 04:51:14'),
(16, 'Library Hours Extended During Exams', 'The library will extend its operating hours during the examination period. The library will now be open from 7:00 AM to 9:00 PM on weekdays.', 'All', '', 10, '2025-01-15', 1, '2026-07-03 04:51:14'),
(17, 'Health and Safety Protocols', 'All students and staff are reminded to follow the health and safety protocols at all times. Hand washing stations are available at all entry points.', 'All', 'Normal', 5, '2025-06-30', 1, '2026-07-03 04:51:14'),
(18, 'Sports Week Activities', 'The annual sports week will be held from 18th to 22nd November 2024. All students are encouraged to participate. Registration at the Guild Office.', '', '', 21, '2025-01-31', 1, '2026-07-03 04:51:14'),
(19, 'Nursing Council Registration Update', 'Final year students are reminded to complete their Nursing and Midwifery Council registration. The deadline has been extended to 31st January 2025.', '', 'High', 7, '2025-02-28', 1, '2026-07-03 04:51:14'),
(20, 'Holiday Notice - Christmas Break', 'The institution will close for Christmas break on 20th December 2024 and reopen on 6th January 2025. Merry Christmas and Happy New Year!', 'All', '', 5, '2025-01-31', 1, '2026-07-03 04:51:14');

-- --------------------------------------------------------

--
-- Table structure for table `applicants`
--

CREATE TABLE `applicants` (
  `id` int(11) NOT NULL,
  `application_number` varchar(30) NOT NULL,
  `student_number` varchar(50) DEFAULT NULL,
  `registration_number` varchar(50) DEFAULT NULL,
  `portal_username` varchar(100) DEFAULT NULL,
  `portal_password_hash` varchar(255) DEFAULT NULL,
  `full_name` varchar(255) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `surname` varchar(100) DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `alternative_phone` varchar(20) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT 'Ugandan',
  `district` varchar(100) DEFAULT NULL,
  `county` varchar(100) DEFAULT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `marital_status` enum('Single','Married','Divorced','Widowed') DEFAULT 'Single',
  `address` text DEFAULT NULL,
  `photo_path` varchar(500) DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `intake` varchar(50) DEFAULT NULL,
  `intake_id` int(11) DEFAULT NULL,
  `application_source` enum('Online','Manual','Walk-in','Referral','Other') DEFAULT 'Online',
  `status` enum('New','Under Review','Waiting for Documents','Requirements Verified','Interview Scheduled','Approved','Rejected','Registered','Withdrawn') NOT NULL DEFAULT 'New',
  `rejection_reason` text DEFAULT NULL,
  `previous_education` text DEFAULT NULL,
  `previous_institution` varchar(255) DEFAULT NULL,
  `previous_qualification` varchar(255) DEFAULT NULL,
  `guardian_name` varchar(200) DEFAULT NULL,
  `guardian_phone` varchar(20) DEFAULT NULL,
  `guardian_email` varchar(100) DEFAULT NULL,
  `guardian_relationship` varchar(50) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `registered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applicant_requirement_status`
--

CREATE TABLE `applicant_requirement_status` (
  `id` int(11) NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `requirement_id` int(11) NOT NULL,
  `status` enum('Not Submitted','Pending','Submitted','Verified','Rejected','Missing','Received','Not Yet Given') NOT NULL DEFAULT 'Not Submitted',
  `remarks` text DEFAULT NULL COMMENT 'System/admin remarks',
  `director_notes` text DEFAULT NULL COMMENT 'Admission Director private notes',
  `submitted_by` int(11) DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `applicant_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `program` varchar(200) DEFAULT NULL,
  `intake` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `application_type` enum('Admission','Transfer','Readmission','Scholarship','Other') DEFAULT 'Admission',
  `status` enum('Pending','Reviewed','Accepted','Rejected','Waitlisted','Enrolled') DEFAULT 'Pending',
  `submitted_at` timestamp NULL DEFAULT current_timestamp(),
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `visitor_name` varchar(200) DEFAULT NULL,
  `visitor_phone` varchar(50) DEFAULT NULL,
  `visitor_email` varchar(100) DEFAULT NULL,
  `staff_id` int(11) DEFAULT 0,
  `appointment_date` date DEFAULT NULL,
  `appointment_time` time DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `status` enum('pending','approved','completed','cancelled') DEFAULT 'pending',
  `created_by` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `approval_actions`
--

CREATE TABLE `approval_actions` (
  `id` int(11) NOT NULL,
  `request_id` int(11) DEFAULT 0,
  `approval_request_id` int(11) DEFAULT 0,
  `stage_id` int(11) DEFAULT 0,
  `action_by` int(11) DEFAULT 0,
  `approver_id` int(11) DEFAULT 0,
  `action_type` varchar(50) DEFAULT '',
  `action` varchar(20) DEFAULT '',
  `comments` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `decision` varchar(50) DEFAULT '',
  `previous_stage_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `approval_requests`
--

CREATE TABLE `approval_requests` (
  `id` int(11) NOT NULL,
  `workflow_id` int(11) DEFAULT 0,
  `request_number` varchar(100) DEFAULT '',
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` varchar(50) DEFAULT 'Medium',
  `requester_id` int(11) DEFAULT 0,
  `requester_name` varchar(200) DEFAULT '',
  `requester_role` varchar(100) DEFAULT '',
  `current_stage_id` int(11) DEFAULT 0,
  `current_stage_order` int(11) DEFAULT 1,
  `status` varchar(50) DEFAULT 'Active',
  `reference_type` varchar(100) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `reference_url` varchar(500) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `final_approval_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `requester_type` varchar(20) DEFAULT 'staff'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `approval_stages`
--

CREATE TABLE `approval_stages` (
  `id` int(11) NOT NULL,
  `workflow_id` int(11) NOT NULL,
  `stage_name` varchar(255) NOT NULL,
  `stage_order` int(11) DEFAULT 1,
  `approver_role` varchar(100) DEFAULT '',
  `assigned_role_id` int(11) DEFAULT 0,
  `assigned_role_name` varchar(100) DEFAULT '',
  `is_final` tinyint(1) DEFAULT 0,
  `is_mandatory` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `approval_workflows`
--

CREATE TABLE `approval_workflows` (
  `id` int(11) NOT NULL,
  `workflow_name` varchar(255) DEFAULT '',
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT '',
  `target_table` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assessment_scores`
--

CREATE TABLE `assessment_scores` (
  `id` int(11) NOT NULL,
  `examination_session_id` int(11) DEFAULT 0,
  `student_id` int(11) NOT NULL,
  `score` decimal(8,2) DEFAULT 0.00,
  `max_score` decimal(8,2) DEFAULT 100.00,
  `entered_by` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

CREATE TABLE `assets` (
  `id` int(11) NOT NULL,
  `asset_tag` varchar(50) NOT NULL,
  `asset_name` varchar(255) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_price` decimal(12,2) DEFAULT NULL,
  `current_value` decimal(12,2) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `status` enum('Active','Disposed','Lost','Under Maintenance') DEFAULT 'Active',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `asset_categories`
--

CREATE TABLE `asset_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `depreciation_rate` decimal(5,2) DEFAULT 0.00,
  `useful_life_years` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `asset_categories`
--

INSERT INTO `asset_categories` (`id`, `category_name`, `description`, `depreciation_rate`, `useful_life_years`, `created_at`, `updated_at`) VALUES
(1, 'Furniture', 'Desks, chairs, tables, cabinets', 0.00, NULL, '2026-07-03 04:33:38', '2026-07-14 16:51:36'),
(2, 'Electronics', 'Computers, printers, projectors', 0.00, NULL, '2026-07-03 04:33:38', '2026-07-14 16:51:36'),
(3, 'Medical Equipment', 'Beds, monitors, diagnostic tools', 0.00, NULL, '2026-07-03 04:33:38', '2026-07-14 16:51:36'),
(4, 'Vehicles', 'School vehicles, ambulances', 0.00, NULL, '2026-07-03 04:33:38', '2026-07-14 16:51:36'),
(5, 'Buildings', 'School buildings and structures', 0.00, NULL, '2026-07-03 04:33:38', '2026-07-14 16:51:36'),
(6, 'Library', 'Books and library equipment', 0.00, NULL, '2026-07-03 04:33:38', '2026-07-14 16:51:36');

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `id` int(11) NOT NULL,
  `lecturer_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `course_name` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_findings`
--

CREATE TABLE `audit_findings` (
  `id` int(11) NOT NULL,
  `finding_title` varchar(300) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `department` varchar(200) DEFAULT NULL,
  `status` enum('open','in_progress','resolved','closed') DEFAULT 'open',
  `reported_by` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(100) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bank_accounts`
--

CREATE TABLE `bank_accounts` (
  `id` int(11) NOT NULL,
  `account_name` varchar(200) NOT NULL,
  `bank_name` varchar(200) DEFAULT NULL,
  `account_number` varchar(100) NOT NULL,
  `current_balance` decimal(15,2) DEFAULT 0.00,
  `balance` decimal(15,2) DEFAULT 0.00,
  `status` enum('active','inactive','closed') DEFAULT 'active',
  `is_active` tinyint(4) DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bank_reconciliation`
--

CREATE TABLE `bank_reconciliation` (
  `id` int(11) NOT NULL,
  `reconciliation_date` date DEFAULT NULL,
  `bank_balance` decimal(14,2) DEFAULT 0.00,
  `book_balance` decimal(14,2) DEFAULT 0.00,
  `difference` decimal(14,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'completed',
  `reconciled_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bank_transactions`
--

CREATE TABLE `bank_transactions` (
  `id` int(11) NOT NULL,
  `transaction_date` date NOT NULL,
  `description` varchar(255) DEFAULT '',
  `reference` varchar(100) DEFAULT '',
  `debit` decimal(12,2) DEFAULT 0.00,
  `credit` decimal(12,2) DEFAULT 0.00,
  `balance` decimal(12,2) DEFAULT 0.00,
  `reconciled` tinyint(1) DEFAULT 0,
  `reconciled_by` int(11) DEFAULT 0,
  `reconciled_at` datetime DEFAULT NULL,
  `bank_account` varchar(100) DEFAULT '',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
--

CREATE TABLE `budgets` (
  `id` int(11) NOT NULL,
  `budget_name` varchar(255) NOT NULL,
  `fiscal_year` varchar(20) NOT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('Draft','Approved','Active','Closed') DEFAULT 'Draft',
  `approved_by` int(11) DEFAULT NULL,
  `approved_date` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budget_approvals`
--

CREATE TABLE `budget_approvals` (
  `id` int(11) NOT NULL,
  `budget_id` int(11) DEFAULT 0,
  `request_type` varchar(50) DEFAULT NULL,
  `requested_by` int(11) DEFAULT 0,
  `amount` decimal(14,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','changes_requested','escalated') DEFAULT 'pending',
  `approver_id` int(11) DEFAULT 0,
  `approver_name` varchar(200) DEFAULT NULL,
  `approver_comments` text DEFAULT NULL,
  `escalated_to` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budget_records`
--

CREATE TABLE `budget_records` (
  `id` int(11) NOT NULL,
  `budget_id` int(11) NOT NULL,
  `budget_item` varchar(255) NOT NULL,
  `allocated_amount` decimal(12,2) NOT NULL,
  `spent_amount` decimal(12,2) DEFAULT 0.00,
  `remaining_amount` decimal(12,2) GENERATED ALWAYS AS (`allocated_amount` - `spent_amount`) STORED,
  `status` enum('Active','Exhausted','Cancelled') DEFAULT 'Active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_cashbook`
--

CREATE TABLE `bursar_cashbook` (
  `id` int(11) NOT NULL,
  `transaction_date` date DEFAULT NULL,
  `date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `debit_amount` decimal(15,2) DEFAULT 0.00,
  `cash_in` decimal(15,2) DEFAULT 0.00,
  `amount` decimal(15,2) DEFAULT 0.00,
  `credit_amount` decimal(15,2) DEFAULT 0.00,
  `cash_out` decimal(15,2) DEFAULT 0.00,
  `running_balance` decimal(15,2) DEFAULT 0.00,
  `balance` decimal(15,2) DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_chart_of_accounts`
--

CREATE TABLE `bursar_chart_of_accounts` (
  `id` int(11) NOT NULL,
  `account_code` varchar(20) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `account_type` enum('Asset','Liability','Equity','Revenue','Expense') NOT NULL,
  `parent_account_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_general_ledger`
--

CREATE TABLE `bursar_general_ledger` (
  `id` int(11) NOT NULL,
  `entry_number` varchar(50) NOT NULL,
  `account_id` int(11) DEFAULT 0,
  `cost_center_id` int(11) DEFAULT 0,
  `transaction_type` enum('Debit','Credit') NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `reference_type` varchar(50) DEFAULT '',
  `reference_id` varchar(50) DEFAULT '',
  `description` text DEFAULT NULL,
  `entry_date` date DEFAULT curdate(),
  `posted_by` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_tax_filings`
--

CREATE TABLE `bursar_tax_filings` (
  `id` int(11) NOT NULL,
  `tax_period_id` int(11) NOT NULL,
  `filing_date` date DEFAULT curdate(),
  `total_revenue` decimal(12,2) DEFAULT 0.00,
  `total_tax` decimal(12,2) DEFAULT 0.00,
  `filing_reference` varchar(100) DEFAULT '',
  `status` enum('Draft','Filed','Amended') DEFAULT 'Draft',
  `filed_by` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_tax_periods`
--

CREATE TABLE `bursar_tax_periods` (
  `id` int(11) NOT NULL,
  `period_name` varchar(100) NOT NULL,
  `fiscal_year` varchar(10) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('Open','Closed','Filed') DEFAULT 'Open',
  `created_by` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_users`
--

CREATE TABLE `bursar_users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('bursar','accounts_assistant','auditor') DEFAULT 'bursar',
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `bursar_users`
--

INSERT INTO `bursar_users` (`id`, `email`, `password_hash`, `full_name`, `phone`, `role`, `status`, `last_login`, `created_at`, `updated_at`, `login_attempts`, `locked_until`) VALUES
(1, 'bursar@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$t8ruNUyglG3NmMVoQn10LOVy7rNJJkFM1lCD75BUvd9G62HMPQOLa', 'School Bursar', NULL, 'bursar', 'active', NULL, '2026-07-16 19:31:18', '2026-07-16 19:31:18', 0, NULL),
(2, 'bursar@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$t8ruNUyglG3NmMVoQn10LOVy7rNJJkFM1lCD75BUvd9G62HMPQOLa', 'School Bursar', NULL, 'bursar', 'active', NULL, '2026-07-16 19:52:40', '2026-07-16 19:52:40', 0, NULL),
(3, 'bursar@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$t8ruNUyglG3NmMVoQn10LOVy7rNJJkFM1lCD75BUvd9G62HMPQOLa', 'School Bursar', NULL, 'bursar', 'active', NULL, '2026-07-16 19:52:51', '2026-07-16 19:52:51', 0, NULL),
(4, 'bursar@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$t8ruNUyglG3NmMVoQn10LOVy7rNJJkFM1lCD75BUvd9G62HMPQOLa', 'School Bursar', NULL, 'bursar', 'active', NULL, '2026-07-16 19:53:02', '2026-07-16 19:53:02', 0, NULL),
(5, 'bursar@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$t8ruNUyglG3NmMVoQn10LOVy7rNJJkFM1lCD75BUvd9G62HMPQOLa', 'School Bursar', NULL, 'bursar', 'active', NULL, '2026-07-16 19:53:14', '2026-07-16 19:53:14', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `buses`
--

CREATE TABLE `buses` (
  `s_no` int(11) NOT NULL,
  `bus_id` varchar(50) NOT NULL,
  `bus_title` varchar(100) DEFAULT NULL,
  `bus_number` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bus_root`
--

CREATE TABLE `bus_root` (
  `s_no` int(11) NOT NULL,
  `bus_id` varchar(50) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `arrival_time` varchar(20) DEFAULT NULL,
  `serial` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bus_staff`
--

CREATE TABLE `bus_staff` (
  `s_no` int(11) NOT NULL,
  `id` varchar(50) NOT NULL,
  `bus_id` varchar(50) NOT NULL,
  `name` varchar(150) DEFAULT NULL,
  `contact` varchar(30) DEFAULT NULL,
  `role` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `capital_projects`
--

CREATE TABLE `capital_projects` (
  `id` int(11) NOT NULL,
  `project_name` varchar(300) DEFAULT NULL,
  `project_code` varchar(100) DEFAULT NULL,
  `budget` decimal(14,2) DEFAULT 0.00,
  `spent` decimal(14,2) DEFAULT 0.00,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('planning','active','completed','cancelled') DEFAULT 'planning',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cashbook`
--

CREATE TABLE `cashbook` (
  `id` int(11) NOT NULL,
  `transaction_date` date DEFAULT NULL,
  `date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `debit_amount` decimal(15,2) DEFAULT 0.00,
  `cash_in` decimal(15,2) DEFAULT 0.00,
  `amount` decimal(15,2) DEFAULT 0.00,
  `credit_amount` decimal(15,2) DEFAULT 0.00,
  `cash_out` decimal(15,2) DEFAULT 0.00,
  `running_balance` decimal(15,2) DEFAULT 0.00,
  `balance` decimal(15,2) DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cash_book`
--

CREATE TABLE `cash_book` (
  `id` int(11) NOT NULL,
  `entry_number` varchar(50) NOT NULL,
  `entry_type` enum('Receipt','Payment') NOT NULL,
  `description` text NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `balance` decimal(15,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `related_student_id` int(11) DEFAULT NULL,
  `transaction_date` date DEFAULT curdate(),
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chart_of_accounts`
--

CREATE TABLE `chart_of_accounts` (
  `id` int(11) NOT NULL,
  `account_code` varchar(20) NOT NULL,
  `account_name` varchar(255) NOT NULL,
  `account_type` enum('Asset','Liability','Equity','Revenue','Expense') NOT NULL,
  `parent_account_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
-- Table structure for table `circulars`
--

CREATE TABLE `circulars` (
  `id` int(11) NOT NULL,
  `title` varchar(300) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `issued_by` varchar(200) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `file_name` varchar(300) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `status` enum('draft','issued','archived') DEFAULT 'issued',
  `created_by` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clinical_placements`
--

CREATE TABLE `clinical_placements` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `facility_name` varchar(255) NOT NULL,
  `facility_location` varchar(255) DEFAULT NULL,
  `supervisor_name` varchar(255) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `hours_completed` int(11) DEFAULT 0,
  `skills_assessment` text DEFAULT NULL,
  `status` enum('Active','Completed','Cancelled') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clinical_placements_students`
--

CREATE TABLE `clinical_placements_students` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `placement_site` varchar(200) NOT NULL,
  `supervisor_name` varchar(150) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `competency_score` decimal(5,2) DEFAULT NULL,
  `logbook_submitted` tinyint(1) DEFAULT 0,
  `supervisor_evaluation` text DEFAULT NULL,
  `status` enum('Scheduled','Active','Completed','Withdrawn') DEFAULT 'Scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clinical_sites`
--

CREATE TABLE `clinical_sites` (
  `id` int(11) NOT NULL,
  `site_name` varchar(200) NOT NULL,
  `location` varchar(200) DEFAULT NULL,
  `capacity` int(11) DEFAULT 20,
  `contact_person` varchar(100) DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `clinical_sites`
--

INSERT INTO `clinical_sites` (`id`, `site_name`, `location`, `capacity`, `contact_person`, `contact_phone`, `status`, `created_at`) VALUES
(1, 'Iganga Regional Referral Hospital', 'Iganga Town', 30, 'Dr. Wasswa Moses', '+256-772-123456', 'Active', '2026-07-03 04:51:14'),
(2, 'Iganga Health Centre IV', 'Iganga Municipality', 20, 'Sr. Namukasa Florence', '+256-782-234567', 'Active', '2026-07-03 04:51:14'),
(3, 'Bugiri District Hospital', 'Bugiri Town', 25, 'Dr. Ochieng James', '+256-702-345678', 'Active', '2026-07-03 04:51:14'),
(4, 'Namutumba Health Centre III', 'Namutumba', 15, 'Sr. Nabirye Sarah', '+256-772-456789', 'Active', '2026-07-03 04:51:14'),
(5, 'Kaliro Health Centre III', 'Kaliro Town', 15, 'Mr. Wamboga John', '+256-782-567890', 'Active', '2026-07-03 04:51:14'),
(6, 'Mayuge Health Centre III', 'Mayuge District', 12, 'Dr. Mugisha Patrick', '+256-702-678901', 'Active', '2026-07-03 04:51:14'),
(7, 'Busolwe Hospital', 'Butaleja District', 20, 'Sr. Ajok Betty', '+256-772-789012', 'Active', '2026-07-03 04:51:14'),
(8, 'Kamuli District Hospital', 'Kamuli Town', 25, 'Dr. Ssemwanga Robert', '+256-782-890123', 'Active', '2026-07-03 04:51:14');

-- --------------------------------------------------------

--
-- Table structure for table `cms_approvals`
--

CREATE TABLE `cms_approvals` (
  `id` int(11) NOT NULL,
  `content_type` varchar(50) NOT NULL,
  `content_id` int(11) NOT NULL,
  `submitted_by` int(11) NOT NULL,
  `submitted_by_name` varchar(200) DEFAULT NULL,
  `submitted_by_role` varchar(100) DEFAULT NULL,
  `reviewer_id` int(11) DEFAULT NULL,
  `reviewer_name` varchar(200) DEFAULT NULL,
  `status` enum('draft','pending_review','approved','rejected','revision_requested','published') NOT NULL DEFAULT 'draft',
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `notes` text DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cms_audit_log`
--

CREATE TABLE `cms_audit_log` (
  `id` bigint(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(200) DEFAULT NULL,
  `user_role` varchar(100) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `content_type` varchar(50) DEFAULT NULL,
  `content_id` int(11) DEFAULT NULL,
  `content_title` varchar(255) DEFAULT NULL,
  `old_values` longtext DEFAULT NULL,
  `new_values` longtext DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cms_banners`
--

CREATE TABLE `cms_banners` (
  `id` int(11) NOT NULL,
  `page_slug` varchar(150) DEFAULT 'home',
  `title` varchar(255) NOT NULL,
  `subtitle` text DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `mobile_image_url` varchar(500) DEFAULT NULL,
  `link_url` varchar(500) DEFAULT NULL,
  `link_text` varchar(100) DEFAULT NULL,
  `overlay_color` varchar(30) DEFAULT 'rgba(26,35,126,0.7)',
  `text_color` varchar(20) DEFAULT '#ffffff',
  `text_position` enum('center','left','right','bottom-left') DEFAULT 'center',
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cms_content_blocks`
--

CREATE TABLE `cms_content_blocks` (
  `id` int(11) NOT NULL,
  `page_id` int(11) DEFAULT NULL,
  `block_key` varchar(100) NOT NULL,
  `block_type` enum('text','html','image','gallery','video','stats','cards','timeline','testimonials','cta','faq','accordion','map','embed','custom') NOT NULL DEFAULT 'text',
  `title` varchar(255) DEFAULT NULL,
  `subtitle` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `settings` longtext DEFAULT NULL,
  `animation` varchar(50) DEFAULT 'fade-up',
  `background_style` varchar(100) DEFAULT NULL,
  `text_color` varchar(20) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_published` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cms_content_blocks`
--

INSERT INTO `cms_content_blocks` (`id`, `page_id`, `block_key`, `block_type`, `title`, `subtitle`, `content`, `settings`, `animation`, `background_style`, `text_color`, `sort_order`, `is_published`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 1, 'welcome', 'text', 'Welcome to Iganga School of Nursing and Midwifery', 'Established to provide quality nursing and midwifery education in Uganda and the region.', '<p>Iganga School of Nursing and Midwifery (ISNM) is a premier healthcare education institution dedicated to training competent, compassionate, and skilled nurses and midwives. Located in Iganga, Eastern Uganda, we have been at the forefront of healthcare education since 1997.</p><p>Our programs are designed to equip students with the knowledge, skills, and values needed to provide quality healthcare services in diverse settings.</p>', NULL, 'fade-up', NULL, NULL, 1, 1, NULL, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(2, 1, 'stats', 'stats', 'Our Impact in Numbers', 'Making a difference in healthcare education', NULL, NULL, 'fade-up', NULL, NULL, 2, 1, NULL, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(3, 1, 'why_choose', 'cards', 'Why Choose ISNM', 'Discover what makes us the preferred choice for healthcare education', NULL, NULL, 'fade-up', NULL, NULL, 3, 1, NULL, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(4, 1, 'cta', 'cta', 'Ready to Start Your Journey?', 'Join thousands of healthcare professionals trained at ISNM', NULL, NULL, 'fade-up', NULL, NULL, 10, 1, NULL, '2026-07-14 07:12:24', '2026-07-14 07:12:24');

-- --------------------------------------------------------

--
-- Table structure for table `cms_events`
--

CREATE TABLE `cms_events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` longtext DEFAULT NULL,
  `short_description` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `event_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `event_type` enum('academic','ceremony','workshop','seminar','conference','sports','social','other') DEFAULT 'other',
  `image_url` varchar(500) DEFAULT NULL,
  `registration_url` varchar(500) DEFAULT NULL,
  `max_participants` int(11) DEFAULT NULL,
  `current_participants` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_published` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cms_events`
--

INSERT INTO `cms_events` (`id`, `title`, `slug`, `description`, `short_description`, `event_date`, `end_date`, `event_time`, `end_time`, `location`, `event_type`, `image_url`, `registration_url`, `max_participants`, `current_participants`, `is_featured`, `is_published`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'New Academic Year Orientation', 'new-academic-year-orientation-2026', 'Welcome ceremony and orientation for new and returning students for the 2026 academic year.', 'Welcome ceremony for all students', '2026-02-01', NULL, NULL, NULL, 'ISNM Main Campus', 'academic', NULL, NULL, NULL, 0, 0, 1, NULL, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(2, 'International Nurses Day Celebration', 'international-nurses-day-2026', 'Annual celebration of International Nurses Day with guest speakers, exhibitions, and awards.', 'Celebrating nursing excellence', '2026-05-12', NULL, NULL, NULL, 'ISNM Auditorium', 'ceremony', NULL, NULL, NULL, 0, 0, 1, NULL, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(3, 'Clinical Skills Workshop', 'clinical-skills-workshop-2026', 'Hands-on workshop for nursing students on advanced clinical skills and patient care techniques.', 'Advanced clinical skills training', '2026-06-15', NULL, NULL, NULL, 'ISNM Skills Laboratory', 'workshop', NULL, NULL, NULL, 0, 0, 1, NULL, '2026-07-14 07:12:24', '2026-07-14 07:12:24');

-- --------------------------------------------------------

--
-- Table structure for table `cms_faqs`
--

CREATE TABLE `cms_faqs` (
  `id` int(11) NOT NULL,
  `page_slug` varchar(150) DEFAULT 'general',
  `question` varchar(500) NOT NULL,
  `answer` longtext NOT NULL,
  `category` varchar(100) DEFAULT 'general',
  `sort_order` int(11) DEFAULT 0,
  `is_published` tinyint(1) DEFAULT 1,
  `helpful_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cms_faqs`
--

INSERT INTO `cms_faqs` (`id`, `page_slug`, `question`, `answer`, `category`, `sort_order`, `is_published`, `helpful_count`, `created_at`) VALUES
(1, 'general', 'What programs does ISNM offer?', 'ISNM offers Certificate in Nursing, Certificate in Midwifery, Diploma in Nursing (Extension), and Diploma in Midwifery (Extension) programs.', 'admissions', 1, 1, 0, '2026-07-14 07:12:24'),
(2, 'general', 'How do I apply to ISNM?', 'Applications can be submitted online through our application portal or in person at the admissions office. Required documents include academic certificates, national ID, and passport photos.', 'admissions', 2, 1, 0, '2026-07-14 07:12:24'),
(3, 'general', 'What are the admission requirements?', 'Requirements vary by program. Generally, candidates need O-Level certificates with at least 5 passes including English, Mathematics, Biology, and Chemistry.', 'admissions', 3, 1, 0, '2026-07-14 07:12:24'),
(4, 'general', 'How can I pay tuition fees?', 'Fees can be paid via Mobile Money (MTN/Airtel), bank transfer, or cash at the bursar\'s office. Online payment is also available through our payment portal.', 'finance', 4, 1, 0, '2026-07-14 07:12:24'),
(5, 'general', 'Does ISNM offer accommodation?', 'Yes, ISNM has on-campus hostel facilities for both male and female students. Allocation is based on availability and distance from home.', 'student_life', 5, 1, 0, '2026-07-14 07:12:24'),
(6, 'general', 'What career opportunities are available after graduation?', 'Graduates can work in hospitals, health centers, community health programs, NGOs, international organizations, and can pursue further education.', 'academic', 6, 1, 0, '2026-07-14 07:12:24'),
(7, 'general', 'Is ISNM accredited?', 'Yes, ISNM is fully accredited by the Uganda Nurses and Midwives Council (UNMC) and the National Council for Higher Education (NCHE).', 'general', 7, 1, 0, '2026-07-14 07:12:24'),
(8, 'general', 'How can I contact ISNM?', 'You can reach us by phone at +256 700 123 456, email at info@igangaschoolofnursing.ac.ug, or visit us at Iganga, Uganda.', 'general', 8, 1, 0, '2026-07-14 07:12:24');

-- --------------------------------------------------------

--
-- Table structure for table `cms_gallery_categories`
--

CREATE TABLE `cms_gallery_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `cover_image` varchar(500) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cms_gallery_categories`
--

INSERT INTO `cms_gallery_categories` (`id`, `name`, `slug`, `description`, `cover_image`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'Campus Life', 'campus-life', NULL, NULL, 1, 1, '2026-07-14 07:12:24'),
(2, 'Graduation', 'graduation', NULL, NULL, 2, 1, '2026-07-14 07:12:24'),
(3, 'Clinical Training', 'clinical-training', NULL, NULL, 3, 1, '2026-07-14 07:12:24'),
(4, 'Sports & Activities', 'sports-activities', NULL, NULL, 4, 1, '2026-07-14 07:12:24'),
(5, 'Facilities', 'facilities', NULL, NULL, 5, 1, '2026-07-14 07:12:24'),
(6, 'Events', 'events', NULL, NULL, 6, 1, '2026-07-14 07:12:24');

-- --------------------------------------------------------

--
-- Table structure for table `cms_gallery_images`
--

CREATE TABLE `cms_gallery_images` (
  `id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `image_url` varchar(500) NOT NULL,
  `thumbnail_url` varchar(500) DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_featured` tinyint(1) DEFAULT 0,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cms_media`
--

CREATE TABLE `cms_media` (
  `id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `file_size` bigint(20) DEFAULT 0,
  `mime_type` varchar(100) DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `folder` varchar(100) DEFAULT 'uploads',
  `width` int(11) DEFAULT NULL,
  `height` int(11) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `usage_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cms_news_categories`
--

CREATE TABLE `cms_news_categories` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(100) DEFAULT 'fas fa-newspaper',
  `color` varchar(20) DEFAULT '#1A237E',
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cms_news_categories`
--

INSERT INTO `cms_news_categories` (`id`, `name`, `slug`, `description`, `icon`, `color`, `sort_order`, `is_active`, `created_at`) VALUES
(1, 'General', 'general', NULL, 'fas fa-newspaper', '#1A237E', 1, 1, '2026-07-14 07:12:24'),
(2, 'Academic', 'academic', NULL, 'fas fa-graduation-cap', '#2E7D32', 2, 1, '2026-07-14 07:12:24'),
(3, 'Admissions', 'admissions', NULL, 'fas fa-user-plus', '#E65100', 3, 1, '2026-07-14 07:12:24'),
(4, 'Events', 'events', NULL, 'fas fa-calendar-alt', '#6A1B9A', 4, 1, '2026-07-14 07:12:24'),
(5, 'Announcements', 'announcements', NULL, 'fas fa-bullhorn', '#C62828', 5, 1, '2026-07-14 07:12:24'),
(6, 'Student Life', 'student-life', NULL, 'fas fa-users', '#00838F', 6, 1, '2026-07-14 07:12:24'),
(7, 'Sports', 'sports', NULL, 'fas fa-football-ball', '#F57F17', 7, 1, '2026-07-14 07:12:24'),
(8, 'Research', 'research', NULL, 'fas fa-flask', '#1565C0', 8, 1, '2026-07-14 07:12:24');

-- --------------------------------------------------------

--
-- Table structure for table `cms_pages`
--

CREATE TABLE `cms_pages` (
  `id` int(11) NOT NULL,
  `slug` varchar(150) NOT NULL,
  `title` varchar(255) NOT NULL,
  `subtitle` varchar(500) DEFAULT NULL,
  `page_type` enum('static','dynamic','system') NOT NULL DEFAULT 'static',
  `template` varchar(100) DEFAULT 'default',
  `hero_title` varchar(255) DEFAULT NULL,
  `hero_subtitle` varchar(500) DEFAULT NULL,
  `hero_image` varchar(500) DEFAULT NULL,
  `hero_overlay_color` varchar(20) DEFAULT 'rgba(26,35,126,0.7)',
  `content` longtext DEFAULT NULL,
  `sidebar_content` text DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `og_title` varchar(255) DEFAULT NULL,
  `og_description` text DEFAULT NULL,
  `og_image` varchar(500) DEFAULT NULL,
  `canonical_url` varchar(500) DEFAULT NULL,
  `schema_type` varchar(50) DEFAULT NULL,
  `schema_data` longtext DEFAULT NULL,
  `og_type` varchar(50) DEFAULT 'website',
  `og_locale` varchar(10) DEFAULT 'en_US',
  `twitter_card` varchar(50) DEFAULT 'summary_large_image',
  `twitter_site` varchar(100) DEFAULT NULL,
  `twitter_creator` varchar(100) DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 1,
  `is_featured` tinyint(1) DEFAULT 0,
  `allow_comments` tinyint(1) DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `page_views` bigint(20) DEFAULT 0,
  `last_viewed_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cms_pages`
--

INSERT INTO `cms_pages` (`id`, `slug`, `title`, `subtitle`, `page_type`, `template`, `hero_title`, `hero_subtitle`, `hero_image`, `hero_overlay_color`, `content`, `sidebar_content`, `meta_title`, `meta_description`, `og_title`, `og_description`, `og_image`, `canonical_url`, `schema_type`, `schema_data`, `og_type`, `og_locale`, `twitter_card`, `twitter_site`, `twitter_creator`, `is_published`, `is_featured`, `allow_comments`, `sort_order`, `page_views`, `last_viewed_at`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'home', 'Home', NULL, 'dynamic', 'default', 'Welcome to ISNM', 'Training Competent and Caring Healthcare Professionals', NULL, 'rgba(26,35,126,0.7)', NULL, NULL, 'Iganga School of Nursing and Midwifery | Home', 'Premier healthcare education institution in Uganda', NULL, NULL, NULL, NULL, NULL, NULL, 'website', 'en_US', 'summary_large_image', NULL, NULL, 1, 0, 0, 1, 0, NULL, NULL, NULL, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(2, 'about', 'About Us', NULL, 'static', 'default', 'About ISNM', 'Excellence in Healthcare Education Since 1997', NULL, 'rgba(26,35,126,0.7)', NULL, NULL, 'About Us | Iganga School of Nursing and Midwifery', 'Learn about our history, mission, vision, and values', NULL, NULL, NULL, NULL, NULL, NULL, 'website', 'en_US', 'summary_large_image', NULL, NULL, 1, 0, 0, 2, 0, NULL, NULL, NULL, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(3, 'history', 'Our History', NULL, 'static', 'default', 'Our History', 'A Legacy of Healthcare Excellence', NULL, 'rgba(26,35,126,0.7)', NULL, NULL, 'Our History | Iganga School of Nursing and Midwifery', 'The rich history of ISNM since 1997', NULL, NULL, NULL, NULL, NULL, NULL, 'website', 'en_US', 'summary_large_image', NULL, NULL, 1, 0, 0, 3, 0, NULL, NULL, NULL, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(4, 'programs', 'Academic Programs', NULL, 'static', 'default', 'Academic Programs', 'Comprehensive Healthcare Education Programs', NULL, 'rgba(26,35,126,0.7)', NULL, NULL, 'Academic Programs | Iganga School of Nursing and Midwifery', 'Explore our Certificate, Diploma, and Degree programs', NULL, NULL, NULL, NULL, NULL, NULL, 'website', 'en_US', 'summary_large_image', NULL, NULL, 1, 0, 0, 4, 0, NULL, NULL, NULL, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(5, 'news', 'News & Events', NULL, 'dynamic', 'default', 'News & Events', 'Stay Updated with ISNM', NULL, 'rgba(26,35,126,0.7)', NULL, NULL, 'News & Events | Iganga School of Nursing and Midwifery', 'Latest news, events, and announcements from ISNM', NULL, NULL, NULL, NULL, NULL, NULL, 'website', 'en_US', 'summary_large_image', NULL, NULL, 1, 0, 0, 5, 0, NULL, NULL, NULL, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(6, 'contact', 'Contact Us', NULL, 'static', 'default', 'Contact Us', 'Get in Touch with ISNM', NULL, 'rgba(26,35,126,0.7)', NULL, NULL, 'Contact Us | Iganga School of Nursing and Midwifery', 'Contact information, map, and inquiry form', NULL, NULL, NULL, NULL, NULL, NULL, 'website', 'en_US', 'summary_large_image', NULL, NULL, 1, 0, 0, 6, 0, NULL, NULL, NULL, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(7, 'donate', 'Donate', NULL, 'static', 'default', 'Support ISNM', 'Your Donation Transforms Healthcare Education', NULL, 'rgba(26,35,126,0.7)', NULL, NULL, 'Donate | Iganga School of Nursing and Midwifery', 'Support nursing education in Uganda through donations', NULL, NULL, NULL, NULL, NULL, NULL, 'website', 'en_US', 'summary_large_image', NULL, NULL, 1, 0, 0, 7, 0, NULL, NULL, NULL, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(8, 'volunteer', 'Volunteer', NULL, 'static', 'default', 'Volunteer With Us', 'Make a Difference in Healthcare Education', NULL, 'rgba(26,35,126,0.7)', NULL, NULL, 'Volunteer | Iganga School of Nursing and Midwifery', 'Volunteer opportunities at Iganga School of Nursing', NULL, NULL, NULL, NULL, NULL, NULL, 'website', 'en_US', 'summary_large_image', NULL, NULL, 1, 0, 0, 8, 0, NULL, NULL, NULL, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(9, 'application', 'Apply Now', NULL, 'static', 'default', 'Apply to ISNM', 'Start Your Healthcare Career Today', NULL, 'rgba(26,35,126,0.7)', NULL, NULL, 'Apply Now | Iganga School of Nursing and Midwifery', 'Submit your application to Iganga School of Nursing', NULL, NULL, NULL, NULL, NULL, NULL, 'website', 'en_US', 'summary_large_image', NULL, NULL, 1, 0, 0, 9, 0, NULL, NULL, NULL, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(10, 'portal', 'Student Portal', NULL, 'dynamic', 'default', 'Student Portal', 'Access Your Academic Dashboard', NULL, 'rgba(26,35,126,0.7)', NULL, NULL, 'Student Portal | Iganga School of Nursing and Midwifery', 'Student login portal for academic resources', NULL, NULL, NULL, NULL, NULL, NULL, 'website', 'en_US', 'summary_large_image', NULL, NULL, 1, 0, 0, 10, 0, NULL, NULL, NULL, '2026-07-14 07:12:24', '2026-07-14 07:12:24');

-- --------------------------------------------------------

--
-- Table structure for table `cms_page_views`
--

CREATE TABLE `cms_page_views` (
  `id` bigint(20) NOT NULL,
  `page_slug` varchar(150) NOT NULL,
  `visitor_ip` varchar(45) DEFAULT NULL,
  `visitor_agent` text DEFAULT NULL,
  `referer_url` varchar(500) DEFAULT NULL,
  `session_id` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `device_type` enum('desktop','mobile','tablet','unknown') DEFAULT 'unknown',
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cms_partners`
--

CREATE TABLE `cms_partners` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `logo_url` varchar(500) DEFAULT NULL,
  `website_url` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `partner_type` enum('donor','academic','healthcare','government','ngo','corporate','other') DEFAULT 'other',
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cms_revisions`
--

CREATE TABLE `cms_revisions` (
  `id` int(11) NOT NULL,
  `content_type` varchar(50) NOT NULL,
  `content_id` int(11) NOT NULL,
  `revision_number` int(11) DEFAULT 1,
  `title` varchar(255) DEFAULT NULL,
  `content_snapshot` longtext DEFAULT NULL,
  `changes_summary` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_by_name` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cms_role_permissions`
--

CREATE TABLE `cms_role_permissions` (
  `id` int(11) NOT NULL,
  `role_name` varchar(100) NOT NULL,
  `permission` varchar(100) NOT NULL,
  `page_slug` varchar(150) DEFAULT NULL,
  `content_type` varchar(50) DEFAULT NULL,
  `can_create` tinyint(1) DEFAULT 0,
  `can_edit` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0,
  `can_publish` tinyint(1) DEFAULT 0,
  `can_approve` tinyint(1) DEFAULT 0,
  `can_view` tinyint(1) DEFAULT 1,
  `requires_approval` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cms_role_permissions`
--

INSERT INTO `cms_role_permissions` (`id`, `role_name`, `permission`, `page_slug`, `content_type`, `can_create`, `can_edit`, `can_delete`, `can_publish`, `can_approve`, `can_view`, `requires_approval`, `created_at`) VALUES
(1, 'Director General', 'manage_all', NULL, NULL, 1, 1, 1, 1, 1, 1, 0, '2026-07-14 07:12:24'),
(2, 'CEO', 'edit_homepage', 'about', 'page', 0, 1, 0, 1, 0, 1, 0, '2026-07-14 07:12:24'),
(3, 'CEO', 'edit_ceo_message', 'about', 'content_block', 0, 1, 0, 0, 0, 1, 1, '2026-07-14 07:12:24'),
(4, 'Director Academics', 'manage_programs', 'programs', 'page', 1, 1, 0, 1, 0, 1, 1, '2026-07-14 07:12:24'),
(5, 'Director Academics', 'manage_news', NULL, 'news', 1, 1, 0, 1, 0, 1, 1, '2026-07-14 07:12:24'),
(6, 'School Principal', 'edit_principal_message', 'about', 'content_block', 0, 1, 0, 0, 0, 1, 1, '2026-07-14 07:12:24'),
(7, 'School Principal', 'manage_announcements', NULL, 'announcement', 1, 1, 1, 1, 0, 1, 0, '2026-07-14 07:12:24'),
(8, 'Director Finance', 'edit_tuition', 'programs', 'content_block', 0, 1, 0, 0, 0, 1, 1, '2026-07-14 07:12:24'),
(9, 'Director Finance', 'manage_donations', 'donate', 'page', 0, 1, 0, 1, 0, 1, 0, '2026-07-14 07:12:24'),
(10, 'School Bursar', 'edit_payment_info', 'donate', 'content_block', 0, 1, 0, 0, 0, 1, 1, '2026-07-14 07:12:24'),
(11, 'Director Admissions', 'manage_admissions', 'application', 'page', 1, 1, 0, 1, 0, 1, 1, '2026-07-14 07:12:24'),
(12, 'Academic Registrar', 'edit_registration', 'programs', 'content_block', 0, 1, 0, 0, 0, 1, 1, '2026-07-14 07:12:24'),
(13, 'HR Manager', 'manage_careers', 'contact', 'content_block', 1, 1, 1, 1, 0, 1, 0, '2026-07-14 07:12:24'),
(14, 'School Secretary', 'manage_notices', NULL, 'announcement', 1, 1, 0, 1, 0, 1, 0, '2026-07-14 07:12:24'),
(15, 'School Librarian', 'edit_library', 'about', 'content_block', 0, 1, 0, 0, 0, 1, 1, '2026-07-14 07:12:24'),
(16, 'Events Coordinator', 'manage_events', NULL, 'event', 1, 1, 1, 1, 0, 1, 0, '2026-07-14 07:12:24'),
(17, 'Events Coordinator', 'manage_gallery', NULL, 'gallery', 1, 1, 1, 1, 0, 1, 0, '2026-07-14 07:12:24'),
(18, 'Director ICT', 'manage_website_settings', NULL, 'setting', 1, 1, 0, 1, 0, 1, 0, '2026-07-14 07:12:24'),
(19, 'Director ICT', 'manage_banners', NULL, 'banner', 1, 1, 1, 1, 0, 1, 0, '2026-07-14 07:12:24'),
(20, 'Director ICT', 'manage_media', NULL, 'media', 1, 1, 1, 1, 0, 1, 0, '2026-07-14 07:12:24');

-- --------------------------------------------------------

--
-- Table structure for table `cms_settings`
--

CREATE TABLE `cms_settings` (
  `id` int(11) NOT NULL,
  `setting_group` varchar(50) NOT NULL DEFAULT 'general',
  `setting_key` varchar(100) NOT NULL,
  `setting_value` longtext DEFAULT NULL,
  `value_type` enum('text','textarea','json','image','boolean','integer','color') DEFAULT 'text',
  `label` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cms_settings`
--

INSERT INTO `cms_settings` (`id`, `setting_group`, `setting_key`, `setting_value`, `value_type`, `label`, `description`, `sort_order`, `created_at`, `updated_at`) VALUES
(1, 'general', 'school_name', 'Iganga School of Nursing and Midwifery', 'text', 'School Name', NULL, 1, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(2, 'general', 'school_motto', 'Quality Healthcare Education', 'text', 'School Motto', NULL, 2, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(3, 'general', 'school_tagline', 'Training Competent and Caring Healthcare Professionals', 'text', 'Tagline', NULL, 3, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(4, 'general', 'school_email', 'info@igangaschoolofnursing.ac.ug', 'text', 'Primary Email', NULL, 4, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(5, 'general', 'school_phone', '+256 700 123 456', 'text', 'Primary Phone', NULL, 5, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(6, 'general', 'school_address', 'Iganga, Uganda', 'text', 'Address', NULL, 6, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(7, 'general', 'school_pobox', 'P.O Box 123, Iganga', 'text', 'P.O. Box', NULL, 7, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(8, 'contact', 'admissions_email', 'admissions@igangaschoolofnursing.ac.ug', 'text', 'Admissions Email', NULL, 10, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(9, 'contact', 'bursar_email', 'bursar@igangaschoolofnursing.ac.ug', 'text', 'Bursar Email', NULL, 11, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(10, 'contact', 'principal_email', 'principal@igangaschoolofnursing.ac.ug', 'text', 'Principal Email', NULL, 12, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(11, 'contact', 'ict_email', 'ict@igangaschoolofnursing.ac.ug', 'text', 'ICT Email', NULL, 13, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(12, 'contact', 'emergency_phone', '+256 700 987 654', 'text', 'Emergency Phone', NULL, 14, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(13, 'seo', 'meta_title_suffix', '| Iganga School of Nursing and Midwifery', 'text', 'Title Suffix', NULL, 20, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(14, 'seo', 'default_meta_description', 'Iganga School of Nursing and Midwifery - Premier healthcare education institution in Uganda, training competent nurses and midwives.', 'textarea', 'Default Meta Description', NULL, 21, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(15, 'seo', 'google_analytics_id', '', 'text', 'Google Analytics ID', NULL, 22, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(16, 'seo', 'google_search_console', '', 'text', 'Search Console Verification', NULL, 23, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(17, 'social', 'facebook_url', 'https://facebook.com/igangaschoolofnursing', 'text', 'Facebook URL', NULL, 30, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(18, 'social', 'twitter_url', 'https://twitter.com/isnm_ug', 'text', 'Twitter URL', NULL, 31, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(19, 'social', 'instagram_url', 'https://instagram.com/isnm_ug', 'text', 'Instagram URL', NULL, 32, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(20, 'social', 'linkedin_url', 'https://linkedin.com/company/isnm', 'text', 'LinkedIn URL', NULL, 33, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(21, 'social', 'youtube_url', '', 'text', 'YouTube URL', NULL, 34, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(22, 'social', 'whatsapp_number', '+256700123456', 'text', 'WhatsApp Number', NULL, 35, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(23, 'homepage', 'hero_animation', 'fade', 'text', 'Hero Animation Style', NULL, 40, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(24, 'homepage', 'stats_counter_enabled', '1', 'boolean', 'Show Stats Counter', NULL, 41, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(25, 'homepage', 'testimonials_enabled', '1', 'boolean', 'Show Testimonials', NULL, 42, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(26, 'homepage', 'partners_enabled', '1', 'boolean', 'Show Partners', NULL, 43, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(27, 'homepage', 'gallery_preview_enabled', '1', 'boolean', 'Show Gallery Preview', NULL, 44, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(28, 'footer', 'developer_name', 'Reagan Otema', 'text', 'Developer Name', NULL, 50, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(29, 'footer', 'developer_url', 'https://reaganotema.com', 'text', 'Developer URL', NULL, 51, '2026-07-14 07:12:24', '2026-07-14 07:12:24'),
(30, 'footer', 'copyright_text', 'Iganga School of Nursing and Midwifery. All Rights Reserved.', 'text', 'Copyright Text', NULL, 52, '2026-07-14 07:12:24', '2026-07-14 07:12:24');

-- --------------------------------------------------------

--
-- Table structure for table `cms_social_links`
--

CREATE TABLE `cms_social_links` (
  `id` int(11) NOT NULL,
  `platform` varchar(50) NOT NULL,
  `url` varchar(500) NOT NULL,
  `icon` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cms_social_links`
--

INSERT INTO `cms_social_links` (`id`, `platform`, `url`, `icon`, `is_active`, `sort_order`, `created_at`) VALUES
(1, 'facebook', 'https://facebook.com/igangaschoolofnursing', 'fab fa-facebook-f', 1, 1, '2026-07-14 07:12:24'),
(2, 'twitter', 'https://twitter.com/isnm_ug', 'fab fa-twitter', 1, 2, '2026-07-14 07:12:24'),
(3, 'instagram', 'https://instagram.com/isnm_ug', 'fab fa-instagram', 1, 3, '2026-07-14 07:12:24'),
(4, 'linkedin', 'https://linkedin.com/company/isnm', 'fab fa-linkedin-in', 1, 4, '2026-07-14 07:12:24'),
(5, 'youtube', '', 'fab fa-youtube', 0, 5, '2026-07-14 07:12:24'),
(6, 'whatsapp', 'https://wa.me/256700123456', 'fab fa-whatsapp', 1, 6, '2026-07-14 07:12:24');

-- --------------------------------------------------------

--
-- Table structure for table `cms_staff_directory`
--

CREATE TABLE `cms_staff_directory` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `full_name` varchar(200) NOT NULL,
  `position` varchar(200) DEFAULT NULL,
  `department` varchar(150) DEFAULT NULL,
  `qualification` varchar(300) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `photo_url` varchar(500) DEFAULT NULL,
  `email` varchar(200) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `office_location` varchar(200) DEFAULT NULL,
  `office_hours` varchar(200) DEFAULT NULL,
  `is_leadership` tinyint(1) DEFAULT 0,
  `is_published` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cms_testimonials`
--

CREATE TABLE `cms_testimonials` (
  `id` int(11) NOT NULL,
  `author_name` varchar(200) NOT NULL,
  `author_title` varchar(200) DEFAULT NULL,
  `author_image` varchar(500) DEFAULT NULL,
  `author_role` enum('student','alumni','staff','parent','partner') DEFAULT 'student',
  `content` text NOT NULL,
  `rating` tinyint(1) DEFAULT 5,
  `is_featured` tinyint(1) DEFAULT 0,
  `is_published` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cms_testimonials`
--

INSERT INTO `cms_testimonials` (`id`, `author_name`, `author_title`, `author_image`, `author_role`, `content`, `rating`, `is_featured`, `is_published`, `sort_order`, `created_at`) VALUES
(1, 'Sarah Nambogo', 'Registered Nurse, Mulago Hospital', NULL, 'alumni', 'ISNM gave me the foundation I needed to become a competent nurse. The clinical training and dedicated faculty prepared me for the real healthcare challenges.', 5, 1, 1, 1, '2026-07-14 07:12:24'),
(2, 'James Ochieng', 'Midwife, Iganga Health Center IV', NULL, 'alumni', 'The Certificate in Midwifery program at ISNM was transformative. I now serve my community with confidence and professional expertise.', 5, 1, 1, 2, '2026-07-14 07:12:24'),
(3, 'Grace Nakamya', 'Student, Diploma in Nursing', NULL, 'student', 'Choosing ISNM was the best decision of my life. The modern facilities, experienced lecturers, and supportive learning environment make every day worthwhile.', 5, 1, 1, 3, '2026-07-14 07:12:24'),
(4, 'Dr. Moses Wambamba', 'Medical Director, Iganga Hospital', NULL, 'partner', 'ISNM graduates consistently demonstrate clinical excellence and compassionate care. We are proud to partner with this exceptional institution.', 5, 1, 1, 4, '2026-07-14 07:12:24');

-- --------------------------------------------------------

--
-- Table structure for table `committee_actions`
--

CREATE TABLE `committee_actions` (
  `id` int(11) NOT NULL,
  `meeting_id` int(11) DEFAULT NULL,
  `action` text DEFAULT NULL,
  `responsible` varchar(200) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('pending','in_progress','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `communication_log`
--

CREATE TABLE `communication_log` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `sender_name` varchar(200) DEFAULT NULL,
  `recipient_role` varchar(100) DEFAULT NULL,
  `subject` varchar(300) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `complaint_submissions`
--

CREATE TABLE `complaint_submissions` (
  `id` int(11) NOT NULL,
  `complainant_name` varchar(255) NOT NULL,
  `complainant_email` varchar(255) NOT NULL,
  `complainant_phone` varchar(20) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `severity` varchar(50) DEFAULT 'medium' COMMENT 'low, medium, high, urgent',
  `status` varchar(50) DEFAULT 'filed' COMMENT 'filed, acknowledged, investigating, resolved, closed',
  `assigned_to` int(11) DEFAULT NULL,
  `resolution` longtext DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `compliance_alerts`
--

CREATE TABLE `compliance_alerts` (
  `id` int(11) NOT NULL,
  `alert_title` varchar(300) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `compliance_type` enum('financial','ura','regulatory') DEFAULT 'financial',
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `status` enum('open','acknowledged','resolved') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `compliance_tracking`
--

CREATE TABLE `compliance_tracking` (
  `id` int(11) NOT NULL,
  `department` varchar(200) DEFAULT NULL,
  `compliance_type` varchar(200) DEFAULT NULL,
  `status` enum('compliant','non_compliant','pending_review') DEFAULT 'pending_review',
  `notes` text DEFAULT NULL,
  `reviewed_by` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_directory`
--

CREATE TABLE `contact_directory` (
  `id` int(11) NOT NULL,
  `full_name` varchar(200) DEFAULT NULL,
  `organization` varchar(200) DEFAULT NULL,
  `position` varchar(200) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_submissions`
--

CREATE TABLE `contact_submissions` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read','replied') DEFAULT 'unread',
  `notified` tinyint(1) DEFAULT 0,
  `replied_at` datetime DEFAULT NULL,
  `replied_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `correspondence`
--

CREATE TABLE `correspondence` (
  `id` int(11) NOT NULL,
  `type` enum('incoming','outgoing') DEFAULT 'incoming',
  `reference` varchar(100) DEFAULT NULL,
  `sender_name` varchar(200) DEFAULT NULL,
  `recipient_name` varchar(200) DEFAULT NULL,
  `subject` varchar(300) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `date_received` date DEFAULT NULL,
  `date_sent` date DEFAULT NULL,
  `file_name` varchar(300) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `status` enum('pending','actioned','closed','archived') DEFAULT 'pending',
  `handled_by` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cost_centers`
--

CREATE TABLE `cost_centers` (
  `id` int(11) NOT NULL,
  `cost_center_code` varchar(20) NOT NULL,
  `cost_center_name` varchar(255) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `id` int(11) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `credits` int(11) DEFAULT 0,
  `level` varchar(50) DEFAULT NULL,
  `department` varchar(200) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_assignments`
--

CREATE TABLE `course_assignments` (
  `id` int(11) NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  `lecturer_id` int(11) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `status` enum('Active','Inactive','Completed') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `course_assignments`
--

INSERT INTO `course_assignments` (`id`, `course_id`, `lecturer_id`, `academic_year`, `semester`, `assigned_by`, `status`, `created_at`) VALUES
(1, 1, 14, '2024/2025', 'Semester 1', 3, 'Active', '2026-07-03 04:51:14'),
(2, 2, 14, '2024/2025', 'Semester 1', 3, 'Active', '2026-07-03 04:51:14'),
(3, 3, 14, '2024/2025', 'Semester 1', 3, 'Active', '2026-07-03 04:51:14'),
(4, 7, 14, '2024/2025', 'Semester 3', 3, 'Active', '2026-07-03 04:51:14'),
(5, 8, 14, '2024/2025', 'Semester 3', 3, 'Active', '2026-07-03 04:51:14'),
(6, 9, 14, '2024/2025', 'Semester 3', 3, 'Active', '2026-07-03 04:51:14'),
(7, 11, 14, '2024/2025', 'Semester 1', 3, 'Active', '2026-07-03 04:51:14'),
(8, 12, 14, '2024/2025', 'Semester 1', 3, 'Active', '2026-07-03 04:51:14'),
(9, 13, 14, '2024/2025', 'Semester 1', 3, 'Active', '2026-07-03 04:51:14'),
(10, 17, 14, '2024/2025', 'Semester 3', 3, 'Active', '2026-07-03 04:51:14'),
(11, 18, 14, '2024/2025', 'Semester 3', 3, 'Active', '2026-07-03 04:51:14'),
(12, 20, 14, '2024/2025', 'Semester 1', 3, 'Active', '2026-07-03 04:51:14'),
(13, 21, 14, '2024/2025', 'Semester 1', 3, 'Active', '2026-07-03 04:51:14'),
(14, 22, 14, '2024/2025', 'Semester 1', 3, 'Active', '2026-07-03 04:51:14'),
(15, 26, 14, '2024/2025', 'Semester 3', 3, 'Active', '2026-07-03 04:51:14'),
(16, 27, 14, '2024/2025', 'Semester 3', 3, 'Active', '2026-07-03 04:51:14'),
(17, 28, 14, '2024/2025', 'Semester 3', 3, 'Active', '2026-07-03 04:51:14'),
(18, 31, 14, '2024/2025', 'Semester 5', 3, 'Active', '2026-07-03 04:51:14'),
(19, 32, 14, '2024/2025', 'Semester 5', 3, 'Active', '2026-07-03 04:51:14'),
(20, 33, 14, '2024/2025', 'Semester 5', 3, 'Active', '2026-07-03 04:51:14'),
(21, 34, 14, '2024/2025', 'Semester 5', 3, 'Active', '2026-07-03 04:51:14'),
(22, 37, 14, '2024/2025', 'Semester 1', 3, 'Active', '2026-07-03 04:51:14'),
(23, 38, 14, '2024/2025', 'Semester 1', 3, 'Active', '2026-07-03 04:51:14'),
(24, 39, 14, '2024/2025', 'Semester 1', 3, 'Active', '2026-07-03 04:51:14'),
(25, 40, 14, '2024/2025', 'Semester 3', 3, 'Active', '2026-07-03 04:51:14'),
(26, 41, 14, '2024/2025', 'Semester 3', 3, 'Active', '2026-07-03 04:51:14'),
(27, 42, 14, '2024/2025', 'Semester 5', 3, 'Active', '2026-07-03 04:51:14'),
(28, 43, 14, '2024/2025', 'Semester 1', 3, 'Active', '2026-07-03 04:51:14'),
(29, 44, 14, '2024/2025', 'Semester 1', 3, 'Active', '2026-07-03 04:51:14'),
(30, 45, 14, '2024/2025', 'Semester 3', 3, 'Active', '2026-07-03 04:51:14');

-- --------------------------------------------------------

--
-- Table structure for table `course_catalog`
--

CREATE TABLE `course_catalog` (
  `id` int(11) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `program` varchar(200) DEFAULT NULL,
  `level` varchar(50) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `credit_hours` int(11) DEFAULT 0,
  `is_compulsory` tinyint(1) DEFAULT 1,
  `status` varchar(20) DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `course_catalog`
--

INSERT INTO `course_catalog` (`id`, `course_code`, `course_name`, `program`, `level`, `semester`, `credit_hours`, `is_compulsory`, `status`, `created_at`) VALUES
(1, 'CNN101', 'Fundamentals of Nursing I', 'Certificate in Nursing', 'Certificate', 'Semester 1', 4, 1, 'Active', '2026-07-03 03:56:26'),
(2, 'CNN102', 'Anatomy & Physiology I', 'Certificate in Nursing', 'Certificate', 'Semester 1', 3, 1, 'Active', '2026-07-03 03:56:26'),
(3, 'CNN103', 'Community Health Nursing I', 'Certificate in Nursing', 'Certificate', 'Semester 1', 3, 1, 'Active', '2026-07-03 03:56:26'),
(4, 'CNN104', 'Medical Surgical Nursing I', 'Certificate in Nursing', 'Certificate', 'Semester 2', 4, 1, 'Active', '2026-07-03 03:56:26'),
(5, 'CNN105', 'Anatomy & Physiology II', 'Certificate in Nursing', 'Certificate', 'Semester 2', 3, 1, 'Active', '2026-07-03 03:56:26'),
(6, 'CNN106', 'Pharmacology I', 'Certificate in Nursing', 'Certificate', 'Semester 2', 3, 1, 'Active', '2026-07-03 03:56:26'),
(7, 'CNN201', 'Fundamentals of Nursing II', 'Certificate in Nursing', 'Certificate', 'Semester 3', 4, 1, 'Active', '2026-07-03 03:56:26'),
(8, 'CNN202', 'Psychiatric Nursing', 'Certificate in Nursing', 'Certificate', 'Semester 3', 3, 1, 'Active', '2026-07-03 03:56:26'),
(9, 'CNN203', 'Pediatric Nursing', 'Certificate in Nursing', 'Certificate', 'Semester 3', 3, 1, 'Active', '2026-07-03 03:56:26'),
(10, 'CNN204', 'Community Health Nursing II', 'Certificate in Nursing', 'Certificate', 'Semester 4', 4, 1, 'Active', '2026-07-03 03:56:26'),
(11, 'CNM101', 'Introduction to Midwifery', 'Certificate in Midwifery', 'Certificate', 'Semester 1', 4, 1, 'Active', '2026-07-03 03:56:26'),
(12, 'CNM102', 'Anatomy for Midwives', 'Certificate in Midwifery', 'Certificate', 'Semester 1', 3, 1, 'Active', '2026-07-03 03:56:26'),
(13, 'CNM103', 'Fundamentals of Midwifery Care', 'Certificate in Midwifery', 'Certificate', 'Semester 1', 4, 1, 'Active', '2026-07-03 03:56:26'),
(14, 'CNM104', 'Antenatal Care', 'Certificate in Midwifery', 'Certificate', 'Semester 2', 4, 1, 'Active', '2026-07-03 03:56:26'),
(15, 'CNM105', 'Labour & Delivery Management', 'Certificate in Midwifery', 'Certificate', 'Semester 2', 5, 1, 'Active', '2026-07-03 03:56:26'),
(16, 'CNM106', 'Postnatal Care', 'Certificate in Midwifery', 'Certificate', 'Semester 2', 3, 1, 'Active', '2026-07-03 03:56:26'),
(17, 'CNM201', 'Emergency Midwifery', 'Certificate in Midwifery', 'Certificate', 'Semester 3', 4, 1, 'Active', '2026-07-03 03:56:26'),
(18, 'CNM202', 'Neonatal Care', 'Certificate in Midwifery', 'Certificate', 'Semester 3', 3, 1, 'Active', '2026-07-03 03:56:26'),
(19, 'CNM203', 'Community Midwifery', 'Certificate in Midwifery', 'Certificate', 'Semester 4', 4, 1, 'Active', '2026-07-03 03:56:26'),
(20, 'DNM101', 'Nursing Science I', 'Diploma in Nursing', 'Diploma', 'Semester 1', 4, 1, 'Active', '2026-07-03 03:56:26'),
(21, 'DNM102', 'Human Anatomy & Physiology I', 'Diploma in Nursing', 'Diploma', 'Semester 1', 3, 1, 'Active', '2026-07-03 03:56:26'),
(22, 'DNM103', 'Nutrition & Dietetics', 'Diploma in Nursing', 'Diploma', 'Semester 1', 3, 1, 'Active', '2026-07-03 03:56:26'),
(23, 'DNM104', 'Medical Surgical Nursing I', 'Diploma in Nursing', 'Diploma', 'Semester 2', 5, 1, 'Active', '2026-07-03 03:56:26'),
(24, 'DNM105', 'Pharmacology I', 'Diploma in Nursing', 'Diploma', 'Semester 2', 3, 1, 'Active', '2026-07-03 03:56:26'),
(25, 'DNM106', 'Pathology & Microbiology', 'Diploma in Nursing', 'Diploma', 'Semester 2', 3, 1, 'Active', '2026-07-03 03:56:26'),
(26, 'DNM201', 'Medical Surgical Nursing II', 'Diploma in Nursing', 'Diploma', 'Semester 3', 5, 1, 'Active', '2026-07-03 03:56:26'),
(27, 'DNM202', 'Pediatric Nursing', 'Diploma in Nursing', 'Diploma', 'Semester 3', 4, 1, 'Active', '2026-07-03 03:56:26'),
(28, 'DNM203', 'Psychiatric Nursing', 'Diploma in Nursing', 'Diploma', 'Semester 3', 3, 1, 'Active', '2026-07-03 03:56:26'),
(29, 'DNM204', 'Community Health Nursing I', 'Diploma in Nursing', 'Diploma', 'Semester 4', 4, 1, 'Active', '2026-07-03 03:56:26'),
(30, 'DNM205', 'Nursing Research', 'Diploma in Nursing', 'Diploma', 'Semester 4', 3, 0, 'Active', '2026-07-03 03:56:26'),
(31, 'DNM301', 'Medical Surgical Nursing III', 'Diploma in Nursing', 'Diploma', 'Semester 5', 5, 1, 'Active', '2026-07-03 03:56:26'),
(32, 'DNM302', 'Community Health Nursing II', 'Diploma in Nursing', 'Diploma', 'Semester 5', 4, 1, 'Active', '2026-07-03 03:56:26'),
(33, 'DNM303', 'Nursing Management & Leadership', 'Diploma in Nursing', 'Diploma', 'Semester 5', 4, 1, 'Active', '2026-07-03 03:56:26'),
(34, 'DNM304', 'Clinical Practicum I', 'Diploma in Nursing', 'Diploma', 'Semester 5', 6, 1, 'Active', '2026-07-03 03:56:26'),
(35, 'DNM305', 'Final Clinical Practicum', 'Diploma in Nursing', 'Diploma', 'Semester 6', 8, 1, 'Active', '2026-07-03 03:56:26'),
(36, 'DNM306', 'Nursing Ethics & Legal Issues', 'Diploma in Nursing', 'Diploma', 'Semester 6', 3, 1, 'Active', '2026-07-03 03:56:26'),
(37, 'DMM101', 'Midwifery Science I', 'Diploma in Midwifery', 'Diploma', 'Semester 1', 4, 1, 'Active', '2026-07-03 03:56:26'),
(38, 'DMM102', 'Anatomy for Midwives', 'Diploma in Midwifery', 'Diploma', 'Semester 1', 3, 1, 'Active', '2026-07-03 03:56:26'),
(39, 'DMM103', 'Reproductive Health', 'Diploma in Midwifery', 'Diploma', 'Semester 1', 3, 1, 'Active', '2026-07-03 03:56:26'),
(40, 'DMM201', 'Advanced Midwifery Practice', 'Diploma in Midwifery', 'Diploma', 'Semester 3', 5, 1, 'Active', '2026-07-03 03:56:26'),
(41, 'DMM202', 'Maternal Health', 'Diploma in Midwifery', 'Diploma', 'Semester 3', 4, 1, 'Active', '2026-07-03 03:56:26'),
(42, 'DMM301', 'Midwifery Clinical Practicum', 'Diploma in Midwifery', 'Diploma', 'Semester 5', 8, 1, 'Active', '2026-07-03 03:56:26'),
(43, 'DNE101', 'Foundations of Education', 'Diploma in Nursing Education', 'Diploma', 'Semester 1', 3, 1, 'Active', '2026-07-03 03:56:26'),
(44, 'DNE102', 'Educational Psychology', 'Diploma in Nursing Education', 'Diploma', 'Semester 1', 3, 1, 'Active', '2026-07-03 03:56:26'),
(45, 'DNE201', 'Curriculum Development', 'Diploma in Nursing Education', 'Diploma', 'Semester 3', 4, 1, 'Active', '2026-07-03 03:56:26'),
(46, 'DNE202', 'Teaching Methods in Nursing', 'Diploma in Nursing Education', 'Diploma', 'Semester 3', 4, 1, 'Active', '2026-07-03 03:56:26'),
(47, 'DNE301', 'Practice Teaching', 'Diploma in Nursing Education', 'Diploma', 'Semester 5', 6, 1, 'Active', '2026-07-03 03:56:26'),
(48, 'CNN101', 'Fundamentals of Nursing I', 'Certificate in Nursing', 'Certificate', 'Semester 1', 4, 1, 'Active', '2026-07-03 04:05:12'),
(49, 'CNN102', 'Anatomy & Physiology I', 'Certificate in Nursing', 'Certificate', 'Semester 1', 3, 1, 'Active', '2026-07-03 04:05:12'),
(50, 'CNN103', 'Community Health Nursing I', 'Certificate in Nursing', 'Certificate', 'Semester 1', 3, 1, 'Active', '2026-07-03 04:05:12'),
(51, 'CNN104', 'Medical Surgical Nursing I', 'Certificate in Nursing', 'Certificate', 'Semester 2', 4, 1, 'Active', '2026-07-03 04:05:12'),
(52, 'CNN105', 'Anatomy & Physiology II', 'Certificate in Nursing', 'Certificate', 'Semester 2', 3, 1, 'Active', '2026-07-03 04:05:12'),
(53, 'CNN106', 'Pharmacology I', 'Certificate in Nursing', 'Certificate', 'Semester 2', 3, 1, 'Active', '2026-07-03 04:05:12'),
(54, 'CNN201', 'Fundamentals of Nursing II', 'Certificate in Nursing', 'Certificate', 'Semester 3', 4, 1, 'Active', '2026-07-03 04:05:12'),
(55, 'CNN202', 'Psychiatric Nursing', 'Certificate in Nursing', 'Certificate', 'Semester 3', 3, 1, 'Active', '2026-07-03 04:05:12'),
(56, 'CNN203', 'Pediatric Nursing', 'Certificate in Nursing', 'Certificate', 'Semester 3', 3, 1, 'Active', '2026-07-03 04:05:12'),
(57, 'CNN204', 'Community Health Nursing II', 'Certificate in Nursing', 'Certificate', 'Semester 4', 4, 1, 'Active', '2026-07-03 04:05:12'),
(58, 'CNM101', 'Introduction to Midwifery', 'Certificate in Midwifery', 'Certificate', 'Semester 1', 4, 1, 'Active', '2026-07-03 04:05:12'),
(59, 'CNM102', 'Anatomy for Midwives', 'Certificate in Midwifery', 'Certificate', 'Semester 1', 3, 1, 'Active', '2026-07-03 04:05:12'),
(60, 'CNM103', 'Fundamentals of Midwifery Care', 'Certificate in Midwifery', 'Certificate', 'Semester 1', 4, 1, 'Active', '2026-07-03 04:05:12'),
(61, 'CNM104', 'Antenatal Care', 'Certificate in Midwifery', 'Certificate', 'Semester 2', 4, 1, 'Active', '2026-07-03 04:05:12'),
(62, 'CNM105', 'Labour & Delivery Management', 'Certificate in Midwifery', 'Certificate', 'Semester 2', 5, 1, 'Active', '2026-07-03 04:05:12'),
(63, 'CNM106', 'Postnatal Care', 'Certificate in Midwifery', 'Certificate', 'Semester 2', 3, 1, 'Active', '2026-07-03 04:05:12'),
(64, 'CNM201', 'Emergency Midwifery', 'Certificate in Midwifery', 'Certificate', 'Semester 3', 4, 1, 'Active', '2026-07-03 04:05:12'),
(65, 'CNM202', 'Neonatal Care', 'Certificate in Midwifery', 'Certificate', 'Semester 3', 3, 1, 'Active', '2026-07-03 04:05:12'),
(66, 'CNM203', 'Community Midwifery', 'Certificate in Midwifery', 'Certificate', 'Semester 4', 4, 1, 'Active', '2026-07-03 04:05:12'),
(67, 'DNM101', 'Nursing Science I', 'Diploma in Nursing', 'Diploma', 'Semester 1', 4, 1, 'Active', '2026-07-03 04:05:12'),
(68, 'DNM102', 'Human Anatomy & Physiology I', 'Diploma in Nursing', 'Diploma', 'Semester 1', 3, 1, 'Active', '2026-07-03 04:05:12'),
(69, 'DNM103', 'Nutrition & Dietetics', 'Diploma in Nursing', 'Diploma', 'Semester 1', 3, 1, 'Active', '2026-07-03 04:05:12'),
(70, 'DNM104', 'Medical Surgical Nursing I', 'Diploma in Nursing', 'Diploma', 'Semester 2', 5, 1, 'Active', '2026-07-03 04:05:12'),
(71, 'DNM105', 'Pharmacology I', 'Diploma in Nursing', 'Diploma', 'Semester 2', 3, 1, 'Active', '2026-07-03 04:05:12'),
(72, 'DNM106', 'Pathology & Microbiology', 'Diploma in Nursing', 'Diploma', 'Semester 2', 3, 1, 'Active', '2026-07-03 04:05:12'),
(73, 'DNM201', 'Medical Surgical Nursing II', 'Diploma in Nursing', 'Diploma', 'Semester 3', 5, 1, 'Active', '2026-07-03 04:05:12'),
(74, 'DNM202', 'Pediatric Nursing', 'Diploma in Nursing', 'Diploma', 'Semester 3', 4, 1, 'Active', '2026-07-03 04:05:12'),
(75, 'DNM203', 'Psychiatric Nursing', 'Diploma in Nursing', 'Diploma', 'Semester 3', 3, 1, 'Active', '2026-07-03 04:05:12'),
(76, 'DNM204', 'Community Health Nursing I', 'Diploma in Nursing', 'Diploma', 'Semester 4', 4, 1, 'Active', '2026-07-03 04:05:12'),
(77, 'DNM205', 'Nursing Research', 'Diploma in Nursing', 'Diploma', 'Semester 4', 3, 0, 'Active', '2026-07-03 04:05:12'),
(78, 'DNM301', 'Medical Surgical Nursing III', 'Diploma in Nursing', 'Diploma', 'Semester 5', 5, 1, 'Active', '2026-07-03 04:05:12'),
(79, 'DNM302', 'Community Health Nursing II', 'Diploma in Nursing', 'Diploma', 'Semester 5', 4, 1, 'Active', '2026-07-03 04:05:12'),
(80, 'DNM303', 'Nursing Management & Leadership', 'Diploma in Nursing', 'Diploma', 'Semester 5', 4, 1, 'Active', '2026-07-03 04:05:12'),
(81, 'DNM304', 'Clinical Practicum I', 'Diploma in Nursing', 'Diploma', 'Semester 5', 6, 1, 'Active', '2026-07-03 04:05:12'),
(82, 'DNM305', 'Final Clinical Practicum', 'Diploma in Nursing', 'Diploma', 'Semester 6', 8, 1, 'Active', '2026-07-03 04:05:12'),
(83, 'DNM306', 'Nursing Ethics & Legal Issues', 'Diploma in Nursing', 'Diploma', 'Semester 6', 3, 1, 'Active', '2026-07-03 04:05:12'),
(84, 'DMM101', 'Midwifery Science I', 'Diploma in Midwifery', 'Diploma', 'Semester 1', 4, 1, 'Active', '2026-07-03 04:05:12'),
(85, 'DMM102', 'Anatomy for Midwives', 'Diploma in Midwifery', 'Diploma', 'Semester 1', 3, 1, 'Active', '2026-07-03 04:05:12'),
(86, 'DMM103', 'Reproductive Health', 'Diploma in Midwifery', 'Diploma', 'Semester 1', 3, 1, 'Active', '2026-07-03 04:05:12'),
(87, 'DMM201', 'Advanced Midwifery Practice', 'Diploma in Midwifery', 'Diploma', 'Semester 3', 5, 1, 'Active', '2026-07-03 04:05:12'),
(88, 'DMM202', 'Maternal Health', 'Diploma in Midwifery', 'Diploma', 'Semester 3', 4, 1, 'Active', '2026-07-03 04:05:12'),
(89, 'DMM301', 'Midwifery Clinical Practicum', 'Diploma in Midwifery', 'Diploma', 'Semester 5', 8, 1, 'Active', '2026-07-03 04:05:12'),
(90, 'DNE101', 'Foundations of Education', 'Diploma in Nursing Education', 'Diploma', 'Semester 1', 3, 1, 'Active', '2026-07-03 04:05:12'),
(91, 'DNE102', 'Educational Psychology', 'Diploma in Nursing Education', 'Diploma', 'Semester 1', 3, 1, 'Active', '2026-07-03 04:05:12'),
(92, 'DNE201', 'Curriculum Development', 'Diploma in Nursing Education', 'Diploma', 'Semester 3', 4, 1, 'Active', '2026-07-03 04:05:12'),
(93, 'DNE202', 'Teaching Methods in Nursing', 'Diploma in Nursing Education', 'Diploma', 'Semester 3', 4, 1, 'Active', '2026-07-03 04:05:12'),
(94, 'DNE301', 'Practice Teaching', 'Diploma in Nursing Education', 'Diploma', 'Semester 5', 6, 1, 'Active', '2026-07-03 04:05:12'),
(95, 'CNN101', 'Fundamentals of Nursing I', 'Certificate in Nursing', 'Certificate', 'Semester 1', 4, 1, 'Active', '2026-07-03 04:38:06'),
(96, 'CNN102', 'Anatomy & Physiology I', 'Certificate in Nursing', 'Certificate', 'Semester 1', 3, 1, 'Active', '2026-07-03 04:38:06'),
(97, 'CNN103', 'Community Health Nursing I', 'Certificate in Nursing', 'Certificate', 'Semester 1', 3, 1, 'Active', '2026-07-03 04:38:06'),
(98, 'CNN104', 'Medical Surgical Nursing I', 'Certificate in Nursing', 'Certificate', 'Semester 2', 4, 1, 'Active', '2026-07-03 04:38:06'),
(99, 'CNN105', 'Anatomy & Physiology II', 'Certificate in Nursing', 'Certificate', 'Semester 2', 3, 1, 'Active', '2026-07-03 04:38:06'),
(100, 'CNN106', 'Pharmacology I', 'Certificate in Nursing', 'Certificate', 'Semester 2', 3, 1, 'Active', '2026-07-03 04:38:06'),
(101, 'CNN201', 'Fundamentals of Nursing II', 'Certificate in Nursing', 'Certificate', 'Semester 3', 4, 1, 'Active', '2026-07-03 04:38:06'),
(102, 'CNN202', 'Psychiatric Nursing', 'Certificate in Nursing', 'Certificate', 'Semester 3', 3, 1, 'Active', '2026-07-03 04:38:06'),
(103, 'CNN203', 'Pediatric Nursing', 'Certificate in Nursing', 'Certificate', 'Semester 3', 3, 1, 'Active', '2026-07-03 04:38:06'),
(104, 'CNN204', 'Community Health Nursing II', 'Certificate in Nursing', 'Certificate', 'Semester 4', 4, 1, 'Active', '2026-07-03 04:38:06'),
(105, 'CNM101', 'Introduction to Midwifery', 'Certificate in Midwifery', 'Certificate', 'Semester 1', 4, 1, 'Active', '2026-07-03 04:38:06'),
(106, 'CNM102', 'Anatomy for Midwives', 'Certificate in Midwifery', 'Certificate', 'Semester 1', 3, 1, 'Active', '2026-07-03 04:38:06'),
(107, 'CNM103', 'Fundamentals of Midwifery Care', 'Certificate in Midwifery', 'Certificate', 'Semester 1', 4, 1, 'Active', '2026-07-03 04:38:06'),
(108, 'CNM104', 'Antenatal Care', 'Certificate in Midwifery', 'Certificate', 'Semester 2', 4, 1, 'Active', '2026-07-03 04:38:06'),
(109, 'CNM105', 'Labour & Delivery Management', 'Certificate in Midwifery', 'Certificate', 'Semester 2', 5, 1, 'Active', '2026-07-03 04:38:06'),
(110, 'CNM106', 'Postnatal Care', 'Certificate in Midwifery', 'Certificate', 'Semester 2', 3, 1, 'Active', '2026-07-03 04:38:06'),
(111, 'CNM201', 'Emergency Midwifery', 'Certificate in Midwifery', 'Certificate', 'Semester 3', 4, 1, 'Active', '2026-07-03 04:38:06'),
(112, 'CNM202', 'Neonatal Care', 'Certificate in Midwifery', 'Certificate', 'Semester 3', 3, 1, 'Active', '2026-07-03 04:38:06'),
(113, 'CNM203', 'Community Midwifery', 'Certificate in Midwifery', 'Certificate', 'Semester 4', 4, 1, 'Active', '2026-07-03 04:38:06'),
(114, 'DNM101', 'Nursing Science I', 'Diploma in Nursing', 'Diploma', 'Semester 1', 4, 1, 'Active', '2026-07-03 04:38:06'),
(115, 'DNM102', 'Human Anatomy & Physiology I', 'Diploma in Nursing', 'Diploma', 'Semester 1', 3, 1, 'Active', '2026-07-03 04:38:06'),
(116, 'DNM103', 'Nutrition & Dietetics', 'Diploma in Nursing', 'Diploma', 'Semester 1', 3, 1, 'Active', '2026-07-03 04:38:06'),
(117, 'DNM104', 'Medical Surgical Nursing I', 'Diploma in Nursing', 'Diploma', 'Semester 2', 5, 1, 'Active', '2026-07-03 04:38:06'),
(118, 'DNM105', 'Pharmacology I', 'Diploma in Nursing', 'Diploma', 'Semester 2', 3, 1, 'Active', '2026-07-03 04:38:06'),
(119, 'DNM106', 'Pathology & Microbiology', 'Diploma in Nursing', 'Diploma', 'Semester 2', 3, 1, 'Active', '2026-07-03 04:38:06'),
(120, 'DNM201', 'Medical Surgical Nursing II', 'Diploma in Nursing', 'Diploma', 'Semester 3', 5, 1, 'Active', '2026-07-03 04:38:06'),
(121, 'DNM202', 'Pediatric Nursing', 'Diploma in Nursing', 'Diploma', 'Semester 3', 4, 1, 'Active', '2026-07-03 04:38:06'),
(122, 'DNM203', 'Psychiatric Nursing', 'Diploma in Nursing', 'Diploma', 'Semester 3', 3, 1, 'Active', '2026-07-03 04:38:06'),
(123, 'DNM204', 'Community Health Nursing I', 'Diploma in Nursing', 'Diploma', 'Semester 4', 4, 1, 'Active', '2026-07-03 04:38:06'),
(124, 'DNM205', 'Nursing Research', 'Diploma in Nursing', 'Diploma', 'Semester 4', 3, 0, 'Active', '2026-07-03 04:38:06'),
(125, 'DNM301', 'Medical Surgical Nursing III', 'Diploma in Nursing', 'Diploma', 'Semester 5', 5, 1, 'Active', '2026-07-03 04:38:06'),
(126, 'DNM302', 'Community Health Nursing II', 'Diploma in Nursing', 'Diploma', 'Semester 5', 4, 1, 'Active', '2026-07-03 04:38:06'),
(127, 'DNM303', 'Nursing Management & Leadership', 'Diploma in Nursing', 'Diploma', 'Semester 5', 4, 1, 'Active', '2026-07-03 04:38:06'),
(128, 'DNM304', 'Clinical Practicum I', 'Diploma in Nursing', 'Diploma', 'Semester 5', 6, 1, 'Active', '2026-07-03 04:38:06'),
(129, 'DNM305', 'Final Clinical Practicum', 'Diploma in Nursing', 'Diploma', 'Semester 6', 8, 1, 'Active', '2026-07-03 04:38:06'),
(130, 'DNM306', 'Nursing Ethics & Legal Issues', 'Diploma in Nursing', 'Diploma', 'Semester 6', 3, 1, 'Active', '2026-07-03 04:38:06'),
(131, 'DMM101', 'Midwifery Science I', 'Diploma in Midwifery', 'Diploma', 'Semester 1', 4, 1, 'Active', '2026-07-03 04:38:06'),
(132, 'DMM102', 'Anatomy for Midwives', 'Diploma in Midwifery', 'Diploma', 'Semester 1', 3, 1, 'Active', '2026-07-03 04:38:06'),
(133, 'DMM103', 'Reproductive Health', 'Diploma in Midwifery', 'Diploma', 'Semester 1', 3, 1, 'Active', '2026-07-03 04:38:06'),
(134, 'DMM201', 'Advanced Midwifery Practice', 'Diploma in Midwifery', 'Diploma', 'Semester 3', 5, 1, 'Active', '2026-07-03 04:38:06'),
(135, 'DMM202', 'Maternal Health', 'Diploma in Midwifery', 'Diploma', 'Semester 3', 4, 1, 'Active', '2026-07-03 04:38:06'),
(136, 'DMM301', 'Midwifery Clinical Practicum', 'Diploma in Midwifery', 'Diploma', 'Semester 5', 8, 1, 'Active', '2026-07-03 04:38:06'),
(137, 'DNE101', 'Foundations of Education', 'Diploma in Nursing Education', 'Diploma', 'Semester 1', 3, 1, 'Active', '2026-07-03 04:38:06'),
(138, 'DNE102', 'Educational Psychology', 'Diploma in Nursing Education', 'Diploma', 'Semester 1', 3, 1, 'Active', '2026-07-03 04:38:06'),
(139, 'DNE201', 'Curriculum Development', 'Diploma in Nursing Education', 'Diploma', 'Semester 3', 4, 1, 'Active', '2026-07-03 04:38:06'),
(140, 'DNE202', 'Teaching Methods in Nursing', 'Diploma in Nursing Education', 'Diploma', 'Semester 3', 4, 1, 'Active', '2026-07-03 04:38:06'),
(141, 'DNE301', 'Practice Teaching', 'Diploma in Nursing Education', 'Diploma', 'Semester 5', 6, 1, 'Active', '2026-07-03 04:38:06'),
(142, 'CNN101', 'Fundamentals of Nursing I', 'Certificate in Nursing', 'Certificate', 'Semester 1', 4, 1, 'Active', '2026-07-03 04:51:14'),
(143, 'CNN102', 'Anatomy & Physiology I', 'Certificate in Nursing', 'Certificate', 'Semester 1', 3, 1, 'Active', '2026-07-03 04:51:14'),
(144, 'CNN103', 'Community Health Nursing I', 'Certificate in Nursing', 'Certificate', 'Semester 1', 3, 1, 'Active', '2026-07-03 04:51:14'),
(145, 'CNN104', 'Medical Surgical Nursing I', 'Certificate in Nursing', 'Certificate', 'Semester 2', 4, 1, 'Active', '2026-07-03 04:51:14'),
(146, 'CNN105', 'Anatomy & Physiology II', 'Certificate in Nursing', 'Certificate', 'Semester 2', 3, 1, 'Active', '2026-07-03 04:51:14'),
(147, 'CNN106', 'Pharmacology I', 'Certificate in Nursing', 'Certificate', 'Semester 2', 3, 1, 'Active', '2026-07-03 04:51:14'),
(148, 'CNN201', 'Fundamentals of Nursing II', 'Certificate in Nursing', 'Certificate', 'Semester 3', 4, 1, 'Active', '2026-07-03 04:51:14'),
(149, 'CNN202', 'Psychiatric Nursing', 'Certificate in Nursing', 'Certificate', 'Semester 3', 3, 1, 'Active', '2026-07-03 04:51:14'),
(150, 'CNN203', 'Pediatric Nursing', 'Certificate in Nursing', 'Certificate', 'Semester 3', 3, 1, 'Active', '2026-07-03 04:51:14'),
(151, 'CNN204', 'Community Health Nursing II', 'Certificate in Nursing', 'Certificate', 'Semester 4', 4, 1, 'Active', '2026-07-03 04:51:14'),
(152, 'CNM101', 'Introduction to Midwifery', 'Certificate in Midwifery', 'Certificate', 'Semester 1', 4, 1, 'Active', '2026-07-03 04:51:14'),
(153, 'CNM102', 'Anatomy for Midwives', 'Certificate in Midwifery', 'Certificate', 'Semester 1', 3, 1, 'Active', '2026-07-03 04:51:14'),
(154, 'CNM103', 'Fundamentals of Midwifery Care', 'Certificate in Midwifery', 'Certificate', 'Semester 1', 4, 1, 'Active', '2026-07-03 04:51:14'),
(155, 'CNM104', 'Antenatal Care', 'Certificate in Midwifery', 'Certificate', 'Semester 2', 4, 1, 'Active', '2026-07-03 04:51:14'),
(156, 'CNM105', 'Labour & Delivery Management', 'Certificate in Midwifery', 'Certificate', 'Semester 2', 5, 1, 'Active', '2026-07-03 04:51:14'),
(157, 'CNM106', 'Postnatal Care', 'Certificate in Midwifery', 'Certificate', 'Semester 2', 3, 1, 'Active', '2026-07-03 04:51:14'),
(158, 'CNM201', 'Emergency Midwifery', 'Certificate in Midwifery', 'Certificate', 'Semester 3', 4, 1, 'Active', '2026-07-03 04:51:14'),
(159, 'CNM202', 'Neonatal Care', 'Certificate in Midwifery', 'Certificate', 'Semester 3', 3, 1, 'Active', '2026-07-03 04:51:14'),
(160, 'CNM203', 'Community Midwifery', 'Certificate in Midwifery', 'Certificate', 'Semester 4', 4, 1, 'Active', '2026-07-03 04:51:14'),
(161, 'DNM101', 'Nursing Science I', 'Diploma in Nursing', 'Diploma', 'Semester 1', 4, 1, 'Active', '2026-07-03 04:51:14'),
(162, 'DNM102', 'Human Anatomy & Physiology I', 'Diploma in Nursing', 'Diploma', 'Semester 1', 3, 1, 'Active', '2026-07-03 04:51:14'),
(163, 'DNM103', 'Nutrition & Dietetics', 'Diploma in Nursing', 'Diploma', 'Semester 1', 3, 1, 'Active', '2026-07-03 04:51:14'),
(164, 'DNM104', 'Medical Surgical Nursing I', 'Diploma in Nursing', 'Diploma', 'Semester 2', 5, 1, 'Active', '2026-07-03 04:51:14'),
(165, 'DNM105', 'Pharmacology I', 'Diploma in Nursing', 'Diploma', 'Semester 2', 3, 1, 'Active', '2026-07-03 04:51:14'),
(166, 'DNM106', 'Pathology & Microbiology', 'Diploma in Nursing', 'Diploma', 'Semester 2', 3, 1, 'Active', '2026-07-03 04:51:14'),
(167, 'DNM201', 'Medical Surgical Nursing II', 'Diploma in Nursing', 'Diploma', 'Semester 3', 5, 1, 'Active', '2026-07-03 04:51:14'),
(168, 'DNM202', 'Pediatric Nursing', 'Diploma in Nursing', 'Diploma', 'Semester 3', 4, 1, 'Active', '2026-07-03 04:51:14'),
(169, 'DNM203', 'Psychiatric Nursing', 'Diploma in Nursing', 'Diploma', 'Semester 3', 3, 1, 'Active', '2026-07-03 04:51:14'),
(170, 'DNM204', 'Community Health Nursing I', 'Diploma in Nursing', 'Diploma', 'Semester 4', 4, 1, 'Active', '2026-07-03 04:51:14'),
(171, 'DNM205', 'Nursing Research', 'Diploma in Nursing', 'Diploma', 'Semester 4', 3, 0, 'Active', '2026-07-03 04:51:14'),
(172, 'DNM301', 'Medical Surgical Nursing III', 'Diploma in Nursing', 'Diploma', 'Semester 5', 5, 1, 'Active', '2026-07-03 04:51:14'),
(173, 'DNM302', 'Community Health Nursing II', 'Diploma in Nursing', 'Diploma', 'Semester 5', 4, 1, 'Active', '2026-07-03 04:51:14'),
(174, 'DNM303', 'Nursing Management & Leadership', 'Diploma in Nursing', 'Diploma', 'Semester 5', 4, 1, 'Active', '2026-07-03 04:51:14'),
(175, 'DNM304', 'Clinical Practicum I', 'Diploma in Nursing', 'Diploma', 'Semester 5', 6, 1, 'Active', '2026-07-03 04:51:14'),
(176, 'DNM305', 'Final Clinical Practicum', 'Diploma in Nursing', 'Diploma', 'Semester 6', 8, 1, 'Active', '2026-07-03 04:51:14'),
(177, 'DNM306', 'Nursing Ethics & Legal Issues', 'Diploma in Nursing', 'Diploma', 'Semester 6', 3, 1, 'Active', '2026-07-03 04:51:14'),
(178, 'DMM101', 'Midwifery Science I', 'Diploma in Midwifery', 'Diploma', 'Semester 1', 4, 1, 'Active', '2026-07-03 04:51:14'),
(179, 'DMM102', 'Anatomy for Midwives', 'Diploma in Midwifery', 'Diploma', 'Semester 1', 3, 1, 'Active', '2026-07-03 04:51:14'),
(180, 'DMM103', 'Reproductive Health', 'Diploma in Midwifery', 'Diploma', 'Semester 1', 3, 1, 'Active', '2026-07-03 04:51:14'),
(181, 'DMM201', 'Advanced Midwifery Practice', 'Diploma in Midwifery', 'Diploma', 'Semester 3', 5, 1, 'Active', '2026-07-03 04:51:14'),
(182, 'DMM202', 'Maternal Health', 'Diploma in Midwifery', 'Diploma', 'Semester 3', 4, 1, 'Active', '2026-07-03 04:51:14'),
(183, 'DMM301', 'Midwifery Clinical Practicum', 'Diploma in Midwifery', 'Diploma', 'Semester 5', 8, 1, 'Active', '2026-07-03 04:51:14'),
(184, 'DNE101', 'Foundations of Education', 'Diploma in Nursing Education', 'Diploma', 'Semester 1', 3, 1, 'Active', '2026-07-03 04:51:14'),
(185, 'DNE102', 'Educational Psychology', 'Diploma in Nursing Education', 'Diploma', 'Semester 1', 3, 1, 'Active', '2026-07-03 04:51:14'),
(186, 'DNE201', 'Curriculum Development', 'Diploma in Nursing Education', 'Diploma', 'Semester 3', 4, 1, 'Active', '2026-07-03 04:51:14'),
(187, 'DNE202', 'Teaching Methods in Nursing', 'Diploma in Nursing Education', 'Diploma', 'Semester 3', 4, 1, 'Active', '2026-07-03 04:51:14'),
(188, 'DNE301', 'Practice Teaching', 'Diploma in Nursing Education', 'Diploma', 'Semester 5', 6, 1, 'Active', '2026-07-03 04:51:14');

-- --------------------------------------------------------

--
-- Table structure for table `course_prerequisites`
--

CREATE TABLE `course_prerequisites` (
  `id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `prerequisite_code` varchar(20) NOT NULL,
  `minimum_grade` varchar(5) DEFAULT 'D',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_registrations`
--

CREATE TABLE `course_registrations` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_code` varchar(50) DEFAULT '',
  `course_id` int(11) DEFAULT 0,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(100) DEFAULT NULL,
  `registration_status` varchar(50) DEFAULT 'Registered',
  `status` varchar(50) DEFAULT 'Registered',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_sick_records`
--

CREATE TABLE `daily_sick_records` (
  `id` int(11) NOT NULL,
  `record_number` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `student_name` varchar(300) NOT NULL,
  `student_number` varchar(50) DEFAULT NULL,
  `program` varchar(100) DEFAULT NULL,
  `year_of_study` int(11) DEFAULT NULL,
  `sickness_id` int(11) DEFAULT NULL,
  `sickness_name` varchar(255) DEFAULT NULL,
  `temperature` varchar(20) DEFAULT NULL,
  `blood_pressure` varchar(20) DEFAULT NULL,
  `symptoms` text DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment_given` text DEFAULT NULL,
  `medicines_prescribed` text DEFAULT NULL,
  `severity` enum('Mild','Moderate','Severe','Critical') DEFAULT 'Mild',
  `status` enum('Treated','Referred','Admitted','Discharged','Follow-up','Critical') DEFAULT 'Treated',
  `referred_to` varchar(255) DEFAULT NULL,
  `attended_by` varchar(200) DEFAULT NULL,
  `visit_date` date NOT NULL,
  `visit_time` time DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `department_performance`
--

CREATE TABLE `department_performance` (
  `id` int(11) NOT NULL,
  `department` varchar(200) DEFAULT NULL,
  `metric` varchar(200) DEFAULT NULL,
  `value` decimal(14,2) DEFAULT NULL,
  `period` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `department_requests`
--

CREATE TABLE `department_requests` (
  `id` int(11) NOT NULL,
  `request_number` varchar(50) NOT NULL,
  `from_department` varchar(100) NOT NULL,
  `to_department` varchar(100) NOT NULL DEFAULT 'Store',
  `item_name` varchar(200) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `unit` varchar(50) DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `urgency` enum('Normal','Urgent','Emergency') DEFAULT 'Normal',
  `status` enum('Pending','Approved','Rejected','Fulfilled') DEFAULT 'Pending',
  `requested_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deputy_tasks`
--

CREATE TABLE `deputy_tasks` (
  `id` int(11) NOT NULL,
  `task_title` varchar(300) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `assigned_by` varchar(200) DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `document_tracking`
--

CREATE TABLE `document_tracking` (
  `id` int(11) NOT NULL,
  `doc_title` varchar(300) DEFAULT NULL,
  `doc_type` varchar(100) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `file_name` varchar(300) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('draft','filed','archived') DEFAULT 'draft',
  `uploaded_by` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `donations`
--

CREATE TABLE `donations` (
  `id` int(11) NOT NULL,
  `donor_name` varchar(200) NOT NULL,
  `donor_email` varchar(255) NOT NULL,
  `donor_phone` varchar(50) NOT NULL,
  `donor_address` varchar(500) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_provider` varchar(50) DEFAULT NULL,
  `transaction_reference` varchar(100) DEFAULT NULL,
  `purpose` varchar(200) DEFAULT 'General Donation',
  `notes` text DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `notified` tinyint(1) DEFAULT 0,
  `acknowledged_at` datetime DEFAULT NULL,
  `acknowledged_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `event_type` varchar(100) DEFAULT 'general',
  `start_date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL,
  `all_day` tinyint(1) DEFAULT 0,
  `location` varchar(255) DEFAULT NULL,
  `color` varchar(20) DEFAULT '#3b82f6',
  `created_by` int(11) DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 1,
  `status` varchar(50) DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `examination_records`
--

CREATE TABLE `examination_records` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `exam_type` varchar(50) DEFAULT 'Final',
  `marks_obtained` decimal(8,2) DEFAULT 0.00,
  `total_marks` decimal(8,2) DEFAULT 100.00,
  `grade` varchar(5) DEFAULT '',
  `continuous_assessment_marks` decimal(8,2) DEFAULT 0.00,
  `final_exam_marks` decimal(8,2) DEFAULT 0.00,
  `grade_status` varchar(50) DEFAULT 'Pending',
  `entered_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `examination_results`
--

CREATE TABLE `examination_results` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT 0,
  `score` decimal(8,2) DEFAULT 0.00,
  `max_score` decimal(8,2) DEFAULT 100.00,
  `grade` varchar(10) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `entered_by` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

CREATE TABLE `exams` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `class_id` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `duration` int(11) DEFAULT 0,
  `total_marks` int(11) DEFAULT 100,
  `passing_marks` int(11) DEFAULT 50,
  `term` varchar(20) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `exams`
--

INSERT INTO `exams` (`id`, `name`, `type`, `subject_id`, `class_id`, `date`, `duration`, `total_marks`, `passing_marks`, `term`, `academic_year`, `status`, `created_at`) VALUES
(1, 'Fundamentals of Nursing I - CAT1', 'CAT', NULL, NULL, '2024-10-15', 60, 30, 15, 'Term 1', '2024/2025', 'completed', '2026-07-03 03:56:26'),
(2, 'Fundamentals of Nursing I - Final', 'Final', NULL, NULL, '2024-12-10', 180, 100, 50, 'Term 1', '2024/2025', 'scheduled', '2026-07-03 03:56:26'),
(3, 'Anatomy & Physiology I - CAT1', 'CAT', NULL, NULL, '2024-10-16', 60, 30, 15, 'Term 1', '2024/2025', 'completed', '2026-07-03 03:56:26'),
(4, 'Anatomy & Physiology I - Final', 'Final', NULL, NULL, '2024-12-11', 180, 100, 50, 'Term 1', '2024/2025', 'scheduled', '2026-07-03 03:56:26'),
(5, 'Intro to Midwifery - CAT1', 'CAT', NULL, NULL, '2024-10-17', 60, 30, 15, 'Term 1', '2024/2025', 'completed', '2026-07-03 03:56:26'),
(6, 'Intro to Midwifery - Final', 'Final', NULL, NULL, '2024-12-12', 180, 100, 50, 'Term 1', '2024/2025', 'scheduled', '2026-07-03 03:56:26'),
(7, 'Nursing Science I - CAT1', 'CAT', NULL, NULL, '2024-10-18', 60, 30, 15, 'Term 1', '2024/2025', 'completed', '2026-07-03 03:56:26'),
(8, 'Nursing Science I - Final', 'Final', NULL, NULL, '2024-12-13', 180, 100, 50, 'Term 1', '2024/2025', 'scheduled', '2026-07-03 03:56:26'),
(9, 'Med Surg Nursing I - CAT1', 'CAT', NULL, NULL, '2025-02-20', 60, 30, 15, 'Term 2', '2024/2025', 'scheduled', '2026-07-03 03:56:26'),
(10, 'Med Surg Nursing I - Final', 'Final', NULL, NULL, '2025-04-25', 180, 100, 50, 'Term 2', '2024/2025', 'scheduled', '2026-07-03 03:56:26'),
(11, 'Community Health I - CAT1', 'CAT', NULL, NULL, '2024-10-20', 60, 30, 15, 'Term 1', '2024/2025', 'completed', '2026-07-03 03:56:26'),
(12, 'Community Health I - Final', 'Final', NULL, NULL, '2024-12-15', 180, 100, 50, 'Term 1', '2024/2025', 'scheduled', '2026-07-03 03:56:26'),
(13, 'Pharmacology I - CAT1', 'CAT', NULL, NULL, '2025-02-22', 60, 30, 15, 'Term 2', '2024/2025', 'scheduled', '2026-07-03 03:56:26'),
(14, 'Med Surg Nursing II - CAT1', 'CAT', NULL, NULL, '2025-06-10', 60, 30, 15, 'Term 3', '2024/2025', 'scheduled', '2026-07-03 03:56:26'),
(15, 'Med Surg Nursing II - Final', 'Final', NULL, NULL, '2025-08-15', 180, 100, 50, 'Term 3', '2024/2025', 'scheduled', '2026-07-03 03:56:26'),
(16, 'Fundamentals of Nursing I - CAT1', 'CAT', NULL, NULL, '2024-10-15', 60, 30, 15, 'Term 1', '2024/2025', 'completed', '2026-07-03 04:05:12'),
(17, 'Fundamentals of Nursing I - Final', 'Final', NULL, NULL, '2024-12-10', 180, 100, 50, 'Term 1', '2024/2025', 'scheduled', '2026-07-03 04:05:12'),
(18, 'Anatomy & Physiology I - CAT1', 'CAT', NULL, NULL, '2024-10-16', 60, 30, 15, 'Term 1', '2024/2025', 'completed', '2026-07-03 04:05:12'),
(19, 'Anatomy & Physiology I - Final', 'Final', NULL, NULL, '2024-12-11', 180, 100, 50, 'Term 1', '2024/2025', 'scheduled', '2026-07-03 04:05:12'),
(20, 'Intro to Midwifery - CAT1', 'CAT', NULL, NULL, '2024-10-17', 60, 30, 15, 'Term 1', '2024/2025', 'completed', '2026-07-03 04:05:12'),
(21, 'Intro to Midwifery - Final', 'Final', NULL, NULL, '2024-12-12', 180, 100, 50, 'Term 1', '2024/2025', 'scheduled', '2026-07-03 04:05:12'),
(22, 'Nursing Science I - CAT1', 'CAT', NULL, NULL, '2024-10-18', 60, 30, 15, 'Term 1', '2024/2025', 'completed', '2026-07-03 04:05:12'),
(23, 'Nursing Science I - Final', 'Final', NULL, NULL, '2024-12-13', 180, 100, 50, 'Term 1', '2024/2025', 'scheduled', '2026-07-03 04:05:12'),
(24, 'Med Surg Nursing I - CAT1', 'CAT', NULL, NULL, '2025-02-20', 60, 30, 15, 'Term 2', '2024/2025', 'scheduled', '2026-07-03 04:05:12'),
(25, 'Med Surg Nursing I - Final', 'Final', NULL, NULL, '2025-04-25', 180, 100, 50, 'Term 2', '2024/2025', 'scheduled', '2026-07-03 04:05:12'),
(26, 'Community Health I - CAT1', 'CAT', NULL, NULL, '2024-10-20', 60, 30, 15, 'Term 1', '2024/2025', 'completed', '2026-07-03 04:05:12'),
(27, 'Community Health I - Final', 'Final', NULL, NULL, '2024-12-15', 180, 100, 50, 'Term 1', '2024/2025', 'scheduled', '2026-07-03 04:05:12'),
(28, 'Pharmacology I - CAT1', 'CAT', NULL, NULL, '2025-02-22', 60, 30, 15, 'Term 2', '2024/2025', 'scheduled', '2026-07-03 04:05:12'),
(29, 'Med Surg Nursing II - CAT1', 'CAT', NULL, NULL, '2025-06-10', 60, 30, 15, 'Term 3', '2024/2025', 'scheduled', '2026-07-03 04:05:12'),
(30, 'Med Surg Nursing II - Final', 'Final', NULL, NULL, '2025-08-15', 180, 100, 50, 'Term 3', '2024/2025', 'scheduled', '2026-07-03 04:05:12'),
(31, 'Fundamentals of Nursing I - CAT1', 'CAT', NULL, NULL, '2024-10-15', 60, 30, 15, 'Term 1', '2024/2025', 'completed', '2026-07-03 04:38:06'),
(32, 'Fundamentals of Nursing I - Final', 'Final', NULL, NULL, '2024-12-10', 180, 100, 50, 'Term 1', '2024/2025', 'scheduled', '2026-07-03 04:38:06'),
(33, 'Anatomy & Physiology I - CAT1', 'CAT', NULL, NULL, '2024-10-16', 60, 30, 15, 'Term 1', '2024/2025', 'completed', '2026-07-03 04:38:06'),
(34, 'Anatomy & Physiology I - Final', 'Final', NULL, NULL, '2024-12-11', 180, 100, 50, 'Term 1', '2024/2025', 'scheduled', '2026-07-03 04:38:06'),
(35, 'Intro to Midwifery - CAT1', 'CAT', NULL, NULL, '2024-10-17', 60, 30, 15, 'Term 1', '2024/2025', 'completed', '2026-07-03 04:38:06'),
(36, 'Intro to Midwifery - Final', 'Final', NULL, NULL, '2024-12-12', 180, 100, 50, 'Term 1', '2024/2025', 'scheduled', '2026-07-03 04:38:06'),
(37, 'Nursing Science I - CAT1', 'CAT', NULL, NULL, '2024-10-18', 60, 30, 15, 'Term 1', '2024/2025', 'completed', '2026-07-03 04:38:06'),
(38, 'Nursing Science I - Final', 'Final', NULL, NULL, '2024-12-13', 180, 100, 50, 'Term 1', '2024/2025', 'scheduled', '2026-07-03 04:38:06'),
(39, 'Med Surg Nursing I - CAT1', 'CAT', NULL, NULL, '2025-02-20', 60, 30, 15, 'Term 2', '2024/2025', 'scheduled', '2026-07-03 04:38:06'),
(40, 'Med Surg Nursing I - Final', 'Final', NULL, NULL, '2025-04-25', 180, 100, 50, 'Term 2', '2024/2025', 'scheduled', '2026-07-03 04:38:06'),
(41, 'Community Health I - CAT1', 'CAT', NULL, NULL, '2024-10-20', 60, 30, 15, 'Term 1', '2024/2025', 'completed', '2026-07-03 04:38:06'),
(42, 'Community Health I - Final', 'Final', NULL, NULL, '2024-12-15', 180, 100, 50, 'Term 1', '2024/2025', 'scheduled', '2026-07-03 04:38:06'),
(43, 'Pharmacology I - CAT1', 'CAT', NULL, NULL, '2025-02-22', 60, 30, 15, 'Term 2', '2024/2025', 'scheduled', '2026-07-03 04:38:06'),
(44, 'Med Surg Nursing II - CAT1', 'CAT', NULL, NULL, '2025-06-10', 60, 30, 15, 'Term 3', '2024/2025', 'scheduled', '2026-07-03 04:38:06'),
(45, 'Med Surg Nursing II - Final', 'Final', NULL, NULL, '2025-08-15', 180, 100, 50, 'Term 3', '2024/2025', 'scheduled', '2026-07-03 04:38:06'),
(46, 'Fundamentals of Nursing I - CAT1', 'CAT', NULL, NULL, '2024-10-15', 60, 30, 15, 'Term 1', '2024/2025', 'completed', '2026-07-03 04:51:14'),
(47, 'Fundamentals of Nursing I - Final', 'Final', NULL, NULL, '2024-12-10', 180, 100, 50, 'Term 1', '2024/2025', 'scheduled', '2026-07-03 04:51:14'),
(48, 'Anatomy & Physiology I - CAT1', 'CAT', NULL, NULL, '2024-10-16', 60, 30, 15, 'Term 1', '2024/2025', 'completed', '2026-07-03 04:51:14'),
(49, 'Anatomy & Physiology I - Final', 'Final', NULL, NULL, '2024-12-11', 180, 100, 50, 'Term 1', '2024/2025', 'scheduled', '2026-07-03 04:51:14'),
(50, 'Intro to Midwifery - CAT1', 'CAT', NULL, NULL, '2024-10-17', 60, 30, 15, 'Term 1', '2024/2025', 'completed', '2026-07-03 04:51:14'),
(51, 'Intro to Midwifery - Final', 'Final', NULL, NULL, '2024-12-12', 180, 100, 50, 'Term 1', '2024/2025', 'scheduled', '2026-07-03 04:51:14'),
(52, 'Nursing Science I - CAT1', 'CAT', NULL, NULL, '2024-10-18', 60, 30, 15, 'Term 1', '2024/2025', 'completed', '2026-07-03 04:51:14'),
(53, 'Nursing Science I - Final', 'Final', NULL, NULL, '2024-12-13', 180, 100, 50, 'Term 1', '2024/2025', 'scheduled', '2026-07-03 04:51:14'),
(54, 'Med Surg Nursing I - CAT1', 'CAT', NULL, NULL, '2025-02-20', 60, 30, 15, 'Term 2', '2024/2025', 'scheduled', '2026-07-03 04:51:14'),
(55, 'Med Surg Nursing I - Final', 'Final', NULL, NULL, '2025-04-25', 180, 100, 50, 'Term 2', '2024/2025', 'scheduled', '2026-07-03 04:51:14'),
(56, 'Community Health I - CAT1', 'CAT', NULL, NULL, '2024-10-20', 60, 30, 15, 'Term 1', '2024/2025', 'completed', '2026-07-03 04:51:14'),
(57, 'Community Health I - Final', 'Final', NULL, NULL, '2024-12-15', 180, 100, 50, 'Term 1', '2024/2025', 'scheduled', '2026-07-03 04:51:14'),
(58, 'Pharmacology I - CAT1', 'CAT', NULL, NULL, '2025-02-22', 60, 30, 15, 'Term 2', '2024/2025', 'scheduled', '2026-07-03 04:51:14'),
(59, 'Med Surg Nursing II - CAT1', 'CAT', NULL, NULL, '2025-06-10', 60, 30, 15, 'Term 3', '2024/2025', 'scheduled', '2026-07-03 04:51:14'),
(60, 'Med Surg Nursing II - Final', 'Final', NULL, NULL, '2025-08-15', 180, 100, 50, 'Term 3', '2024/2025', 'scheduled', '2026-07-03 04:51:14');

-- --------------------------------------------------------

--
-- Table structure for table `exam_results`
--

CREATE TABLE `exam_results` (
  `id` int(11) NOT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `marks_obtained` decimal(5,2) DEFAULT NULL,
  `grade` varchar(5) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `exam_results`
--

INSERT INTO `exam_results` (`id`, `exam_id`, `student_id`, `marks_obtained`, `grade`, `remarks`, `created_at`) VALUES
(1, 1, 0, 96.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(2, 3, 0, 61.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(3, 5, 0, 89.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(4, 7, 0, 73.00, 'B', 'Pass', '2026-07-03 04:51:14'),
(5, 9, 0, 67.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(6, 11, 0, 74.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(7, 13, 0, 63.00, 'B+', 'Pass', '2026-07-03 04:51:14'),
(8, 14, 0, 72.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(9, 16, 0, 69.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(10, 18, 0, 94.00, 'B', 'Pass', '2026-07-03 04:51:14'),
(11, 20, 0, 61.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(12, 22, 0, 89.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(13, 24, 0, 88.00, 'B+', 'Pass', '2026-07-03 04:51:14'),
(14, 26, 0, 85.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(15, 28, 0, 76.00, 'B', 'Pass', '2026-07-03 04:51:14'),
(16, 29, 0, 62.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(17, 31, 0, 65.00, 'B', 'Pass', '2026-07-03 04:51:14'),
(18, 33, 0, 73.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(19, 35, 0, 98.00, 'B', 'Pass', '2026-07-03 04:51:14'),
(20, 37, 0, 84.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(21, 39, 0, 60.00, 'B+', 'Pass', '2026-07-03 04:51:14'),
(22, 41, 0, 67.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(23, 43, 0, 62.00, 'B+', 'Pass', '2026-07-03 04:51:14'),
(24, 44, 0, 80.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(25, 46, 0, 78.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(26, 48, 0, 62.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(27, 50, 0, 67.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(28, 52, 0, 99.00, 'B', 'Pass', '2026-07-03 04:51:14'),
(29, 54, 0, 64.00, 'B', 'Pass', '2026-07-03 04:51:14'),
(30, 56, 0, 91.00, 'B+', 'Pass', '2026-07-03 04:51:14'),
(31, 58, 0, 60.00, 'B+', 'Pass', '2026-07-03 04:51:14'),
(32, 59, 0, 64.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(33, 1, 0, 96.00, 'B', 'Pass', '2026-07-03 04:51:14'),
(34, 3, 0, 76.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(35, 5, 0, 84.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(36, 7, 0, 99.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(37, 9, 0, 63.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(38, 11, 0, 86.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(39, 13, 0, 97.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(40, 14, 0, 98.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(41, 16, 0, 90.00, 'B', 'Pass', '2026-07-03 04:51:14'),
(42, 18, 0, 99.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(43, 20, 0, 95.00, 'B', 'Pass', '2026-07-03 04:51:14'),
(44, 22, 0, 82.00, 'B', 'Pass', '2026-07-03 04:51:14'),
(45, 24, 0, 79.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(46, 26, 0, 77.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(47, 28, 0, 63.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(48, 29, 0, 83.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(49, 31, 0, 82.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(50, 33, 0, 74.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(51, 35, 0, 60.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(52, 37, 0, 60.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(53, 39, 0, 97.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(54, 41, 0, 85.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(55, 43, 0, 83.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(56, 44, 0, 76.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(57, 46, 0, 88.00, 'B+', 'Pass', '2026-07-03 04:51:14'),
(58, 48, 0, 86.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(59, 50, 0, 74.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(60, 52, 0, 61.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(61, 54, 0, 71.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(62, 56, 0, 63.00, 'B', 'Pass', '2026-07-03 04:51:14'),
(63, 58, 0, 80.00, 'B+', 'Pass', '2026-07-03 04:51:14'),
(64, 59, 0, 89.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(65, 1, 0, 85.00, 'B+', 'Pass', '2026-07-03 04:51:14'),
(66, 3, 0, 79.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(67, 5, 0, 70.00, 'B+', 'Pass', '2026-07-03 04:51:14'),
(68, 7, 0, 84.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(69, 9, 0, 63.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(70, 11, 0, 72.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(71, 13, 0, 65.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(72, 14, 0, 95.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(73, 16, 0, 84.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(74, 18, 0, 92.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(75, 20, 0, 97.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(76, 22, 0, 86.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(77, 24, 0, 97.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(78, 26, 0, 94.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(79, 28, 0, 67.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(80, 29, 0, 96.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(81, 31, 0, 82.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(82, 33, 0, 75.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(83, 35, 0, 72.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(84, 37, 0, 70.00, 'B', 'Pass', '2026-07-03 04:51:14'),
(85, 39, 0, 96.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(86, 41, 0, 66.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(87, 43, 0, 97.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(88, 44, 0, 73.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(89, 46, 0, 91.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(90, 48, 0, 97.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(91, 50, 0, 87.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(92, 52, 0, 88.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(93, 54, 0, 81.00, 'B+', 'Pass', '2026-07-03 04:51:14'),
(94, 56, 0, 80.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(95, 58, 0, 99.00, 'B', 'Pass', '2026-07-03 04:51:14'),
(96, 59, 0, 92.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(97, 1, 0, 69.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(98, 3, 0, 71.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(99, 5, 0, 71.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(100, 7, 0, 93.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(101, 9, 0, 70.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(102, 11, 0, 86.00, 'B', 'Pass', '2026-07-03 04:51:14'),
(103, 13, 0, 89.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(104, 14, 0, 91.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(105, 16, 0, 61.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(106, 18, 0, 93.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(107, 20, 0, 89.00, 'B+', 'Pass', '2026-07-03 04:51:14'),
(108, 22, 0, 66.00, 'B+', 'Pass', '2026-07-03 04:51:14'),
(109, 24, 0, 86.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(110, 26, 0, 67.00, 'B+', 'Pass', '2026-07-03 04:51:14'),
(111, 28, 0, 77.00, 'B+', 'Pass', '2026-07-03 04:51:14'),
(112, 29, 0, 72.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(113, 31, 0, 77.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(114, 33, 0, 87.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(115, 35, 0, 93.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(116, 37, 0, 98.00, 'B', 'Pass', '2026-07-03 04:51:14'),
(117, 39, 0, 83.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(118, 41, 0, 95.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(119, 43, 0, 88.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(120, 44, 0, 82.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(121, 46, 0, 67.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(122, 48, 0, 73.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(123, 50, 0, 90.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(124, 52, 0, 87.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(125, 54, 0, 68.00, 'B+', 'Pass', '2026-07-03 04:51:14'),
(126, 56, 0, 83.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(127, 58, 0, 96.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(128, 59, 0, 95.00, 'B+', 'Pass', '2026-07-03 04:51:14'),
(129, 1, 0, 95.00, 'B', 'Pass', '2026-07-03 04:51:14'),
(130, 3, 0, 89.00, 'B', 'Pass', '2026-07-03 04:51:14'),
(131, 5, 0, 82.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(132, 7, 0, 71.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(133, 9, 0, 79.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(134, 11, 0, 89.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(135, 13, 0, 83.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(136, 14, 0, 74.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(137, 16, 0, 85.00, 'B+', 'Pass', '2026-07-03 04:51:14'),
(138, 18, 0, 76.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(139, 20, 0, 94.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(140, 22, 0, 87.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(141, 24, 0, 65.00, 'B', 'Pass', '2026-07-03 04:51:14'),
(142, 26, 0, 87.00, 'B+', 'Pass', '2026-07-03 04:51:14'),
(143, 28, 0, 87.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(144, 29, 0, 84.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(145, 31, 0, 79.00, 'B+', 'Pass', '2026-07-03 04:51:14'),
(146, 33, 0, 96.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(147, 35, 0, 79.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(148, 37, 0, 93.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(149, 39, 0, 83.00, 'B+', 'Pass', '2026-07-03 04:51:14'),
(150, 41, 0, 85.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(151, 43, 0, 94.00, 'B+', 'Pass', '2026-07-03 04:51:14'),
(152, 44, 0, 60.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(153, 46, 0, 71.00, 'B', 'Pass', '2026-07-03 04:51:14'),
(154, 48, 0, 78.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(155, 50, 0, 82.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(156, 52, 0, 79.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(157, 54, 0, 63.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(158, 56, 0, 66.00, 'B+', 'Pass', '2026-07-03 04:51:14'),
(159, 58, 0, 60.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(160, 59, 0, 70.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(161, 1, 0, 91.00, 'B+', 'Pass', '2026-07-03 04:51:14'),
(162, 3, 0, 82.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(163, 5, 0, 99.00, 'B', 'Pass', '2026-07-03 04:51:14'),
(164, 7, 0, 63.00, 'B+', 'Pass', '2026-07-03 04:51:14'),
(165, 9, 0, 97.00, 'B+', 'Pass', '2026-07-03 04:51:14'),
(166, 11, 0, 99.00, 'B', 'Pass', '2026-07-03 04:51:14'),
(167, 13, 0, 77.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(168, 14, 0, 76.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(169, 16, 0, 85.00, 'B', 'Pass', '2026-07-03 04:51:14'),
(170, 18, 0, 86.00, 'B', 'Pass', '2026-07-03 04:51:14'),
(171, 20, 0, 60.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(172, 22, 0, 61.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(173, 24, 0, 90.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(174, 26, 0, 61.00, 'B+', 'Pass', '2026-07-03 04:51:14'),
(175, 28, 0, 60.00, 'B', 'Pass', '2026-07-03 04:51:14'),
(176, 29, 0, 62.00, 'B', 'Pass', '2026-07-03 04:51:14'),
(177, 31, 0, 63.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(178, 33, 0, 81.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(179, 35, 0, 78.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(180, 37, 0, 93.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(181, 39, 0, 83.00, 'B+', 'Pass', '2026-07-03 04:51:14'),
(182, 41, 0, 83.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(183, 43, 0, 66.00, 'B+', 'Pass', '2026-07-03 04:51:14'),
(184, 44, 0, 95.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(185, 46, 0, 64.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(186, 48, 0, 78.00, 'B+', 'Pass', '2026-07-03 04:51:14'),
(187, 50, 0, 71.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(188, 52, 0, 86.00, 'B', 'Pass', '2026-07-03 04:51:14'),
(189, 54, 0, 88.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(190, 56, 0, 74.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(191, 58, 0, 80.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(192, 59, 0, 80.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(193, 1, 0, 62.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(194, 3, 0, 83.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(195, 5, 0, 87.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(196, 7, 0, 86.00, 'B+', 'Pass', '2026-07-03 04:51:14'),
(197, 9, 0, 87.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(198, 11, 0, 95.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(199, 13, 0, 96.00, 'A', 'Pass', '2026-07-03 04:51:14'),
(200, 14, 0, 67.00, 'A', 'Pass', '2026-07-03 04:51:14');

-- --------------------------------------------------------

--
-- Table structure for table `expenditure_approvals`
--

CREATE TABLE `expenditure_approvals` (
  `id` int(11) NOT NULL,
  `budget_id` int(11) DEFAULT 0,
  `request_type` varchar(50) DEFAULT NULL,
  `requested_by` int(11) DEFAULT 0,
  `amount` decimal(14,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','changes_requested','escalated') DEFAULT 'pending',
  `approver_id` int(11) DEFAULT 0,
  `approver_name` varchar(200) DEFAULT NULL,
  `approver_comments` text DEFAULT NULL,
  `escalated_to` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenditure_records`
--

CREATE TABLE `expenditure_records` (
  `id` int(11) NOT NULL,
  `expenditure_number` varchar(50) NOT NULL,
  `budget_record_id` int(11) DEFAULT NULL,
  `expenditure_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `receipt_number` varchar(100) DEFAULT NULL,
  `expenditure_date` date DEFAULT curdate(),
  `approved_by` int(11) DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `supporting_document` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `expense_title` varchar(255) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `category` varchar(100) DEFAULT 'General',
  `requested_by` varchar(200) DEFAULT '',
  `description` text DEFAULT NULL,
  `expense_date` date DEFAULT NULL,
  `date` date DEFAULT NULL,
  `status` enum('pending','approved','rejected','paid') DEFAULT 'pending',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `s_no` int(11) NOT NULL,
  `sender_id` varchar(50) DEFAULT NULL,
  `receiver_id` varchar(50) DEFAULT NULL,
  `msg` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `feedback_submissions`
--

CREATE TABLE `feedback_submissions` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `rating` int(11) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `feedback` longtext NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'received',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_adjustments`
--

CREATE TABLE `fee_adjustments` (
  `id` int(11) NOT NULL,
  `adjustment_number` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `adjustment_type` enum('Discount','Waiver','Penalty','Refund','Other') NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `reason` text NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_reminders`
--

CREATE TABLE `fee_reminders` (
  `id` int(11) NOT NULL,
  `reminder_number` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `reminder_type` enum('Email','SMS','Letter','Call') DEFAULT 'Email',
  `reminder_date` timestamp NULL DEFAULT current_timestamp(),
  `sent_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_structure`
--

CREATE TABLE `fee_structure` (
  `id` int(11) NOT NULL,
  `program` varchar(200) DEFAULT NULL,
  `level` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `fee_type` varchar(50) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `fee_structure`
--

INSERT INTO `fee_structure` (`id`, `program`, `level`, `academic_year`, `semester`, `fee_type`, `amount`, `description`, `is_active`, `created_at`) VALUES
(1, 'Certificate in Nursing', 'Certificate', '2024/2025', 'Semester 1', 'Tuition', 850000.00, 'Semester 1 Tuition Fee', 1, '2026-07-03 03:56:26'),
(2, 'Certificate in Nursing', 'Certificate', '2024/2025', 'Semester 1', 'Functional', 150000.00, 'Functional Fee', 1, '2026-07-03 03:56:26'),
(3, 'Certificate in Nursing', 'Certificate', '2024/2025', 'Semester 1', 'Examination', 50000.00, 'Examination Fee', 1, '2026-07-03 03:56:26'),
(4, 'Certificate in Nursing', 'Certificate', '2024/2025', 'Semester 1', 'Practical', 100000.00, 'Practical / Clinical Fee', 1, '2026-07-03 03:56:26'),
(5, 'Certificate in Midwifery', 'Certificate', '2024/2025', 'Semester 1', 'Tuition', 900000.00, 'Semester 1 Tuition Fee', 1, '2026-07-03 03:56:26'),
(6, 'Certificate in Midwifery', 'Certificate', '2024/2025', 'Semester 1', 'Functional', 150000.00, 'Functional Fee', 1, '2026-07-03 03:56:26'),
(7, 'Certificate in Midwifery', 'Certificate', '2024/2025', 'Semester 1', 'Examination', 50000.00, 'Examination Fee', 1, '2026-07-03 03:56:26'),
(8, 'Certificate in Midwifery', 'Certificate', '2024/2025', 'Semester 1', 'Practical', 120000.00, 'Practical / Clinical Fee', 1, '2026-07-03 03:56:26'),
(9, 'Diploma in Nursing', 'Diploma', '2024/2025', 'Semester 1', 'Tuition', 1200000.00, 'Semester 1 Tuition Fee', 1, '2026-07-03 03:56:26'),
(10, 'Diploma in Nursing', 'Diploma', '2024/2025', 'Semester 1', 'Functional', 200000.00, 'Functional Fee', 1, '2026-07-03 03:56:26'),
(11, 'Diploma in Nursing', 'Diploma', '2024/2025', 'Semester 1', 'Examination', 75000.00, 'Examination Fee', 1, '2026-07-03 03:56:26'),
(12, 'Diploma in Nursing', 'Diploma', '2024/2025', 'Semester 1', 'Practical', 150000.00, 'Practical / Clinical Fee', 1, '2026-07-03 03:56:26'),
(13, 'Diploma in Midwifery', 'Diploma', '2024/2025', 'Semester 1', 'Tuition', 1250000.00, 'Semester 1 Tuition Fee', 1, '2026-07-03 03:56:26'),
(14, 'Diploma in Midwifery', 'Diploma', '2024/2025', 'Semester 1', 'Functional', 200000.00, 'Functional Fee', 1, '2026-07-03 03:56:26'),
(15, 'Diploma in Midwifery', 'Diploma', '2024/2025', 'Semester 1', 'Examination', 75000.00, 'Examination Fee', 1, '2026-07-03 03:56:26'),
(16, 'Diploma in Midwifery', 'Diploma', '2024/2025', 'Semester 1', 'Practical', 160000.00, 'Practical / Clinical Fee', 1, '2026-07-03 03:56:26'),
(17, 'Diploma in Nursing Education', 'Diploma', '2024/2025', 'Semester 1', 'Tuition', 1100000.00, 'Semester 1 Tuition Fee', 1, '2026-07-03 03:56:26'),
(18, 'Diploma in Nursing Education', 'Diploma', '2024/2025', 'Semester 1', 'Functional', 180000.00, 'Functional Fee', 1, '2026-07-03 03:56:26'),
(19, 'Diploma in Nursing Education', 'Diploma', '2024/2025', 'Semester 1', 'Examination', 75000.00, 'Examination Fee', 1, '2026-07-03 03:56:26'),
(20, 'Diploma in Nursing Education', 'Diploma', '2024/2025', 'Semester 1', 'Practical', 130000.00, 'Practical / Clinical Fee', 1, '2026-07-03 03:56:26'),
(21, 'Certificate in Nursing', 'Certificate', '2024/2025', 'Semester 1', 'Tuition', 850000.00, 'Semester 1 Tuition Fee', 1, '2026-07-03 04:05:12'),
(22, 'Certificate in Nursing', 'Certificate', '2024/2025', 'Semester 1', 'Functional', 150000.00, 'Functional Fee', 1, '2026-07-03 04:05:12'),
(23, 'Certificate in Nursing', 'Certificate', '2024/2025', 'Semester 1', 'Examination', 50000.00, 'Examination Fee', 1, '2026-07-03 04:05:12'),
(24, 'Certificate in Nursing', 'Certificate', '2024/2025', 'Semester 1', 'Practical', 100000.00, 'Practical / Clinical Fee', 1, '2026-07-03 04:05:12'),
(25, 'Certificate in Midwifery', 'Certificate', '2024/2025', 'Semester 1', 'Tuition', 900000.00, 'Semester 1 Tuition Fee', 1, '2026-07-03 04:05:12'),
(26, 'Certificate in Midwifery', 'Certificate', '2024/2025', 'Semester 1', 'Functional', 150000.00, 'Functional Fee', 1, '2026-07-03 04:05:12'),
(27, 'Certificate in Midwifery', 'Certificate', '2024/2025', 'Semester 1', 'Examination', 50000.00, 'Examination Fee', 1, '2026-07-03 04:05:12'),
(28, 'Certificate in Midwifery', 'Certificate', '2024/2025', 'Semester 1', 'Practical', 120000.00, 'Practical / Clinical Fee', 1, '2026-07-03 04:05:12'),
(29, 'Diploma in Nursing', 'Diploma', '2024/2025', 'Semester 1', 'Tuition', 1200000.00, 'Semester 1 Tuition Fee', 1, '2026-07-03 04:05:12'),
(30, 'Diploma in Nursing', 'Diploma', '2024/2025', 'Semester 1', 'Functional', 200000.00, 'Functional Fee', 1, '2026-07-03 04:05:12'),
(31, 'Diploma in Nursing', 'Diploma', '2024/2025', 'Semester 1', 'Examination', 75000.00, 'Examination Fee', 1, '2026-07-03 04:05:12'),
(32, 'Diploma in Nursing', 'Diploma', '2024/2025', 'Semester 1', 'Practical', 150000.00, 'Practical / Clinical Fee', 1, '2026-07-03 04:05:12'),
(33, 'Diploma in Midwifery', 'Diploma', '2024/2025', 'Semester 1', 'Tuition', 1250000.00, 'Semester 1 Tuition Fee', 1, '2026-07-03 04:05:12'),
(34, 'Diploma in Midwifery', 'Diploma', '2024/2025', 'Semester 1', 'Functional', 200000.00, 'Functional Fee', 1, '2026-07-03 04:05:12'),
(35, 'Diploma in Midwifery', 'Diploma', '2024/2025', 'Semester 1', 'Examination', 75000.00, 'Examination Fee', 1, '2026-07-03 04:05:12'),
(36, 'Diploma in Midwifery', 'Diploma', '2024/2025', 'Semester 1', 'Practical', 160000.00, 'Practical / Clinical Fee', 1, '2026-07-03 04:05:12'),
(37, 'Diploma in Nursing Education', 'Diploma', '2024/2025', 'Semester 1', 'Tuition', 1100000.00, 'Semester 1 Tuition Fee', 1, '2026-07-03 04:05:12'),
(38, 'Diploma in Nursing Education', 'Diploma', '2024/2025', 'Semester 1', 'Functional', 180000.00, 'Functional Fee', 1, '2026-07-03 04:05:12'),
(39, 'Diploma in Nursing Education', 'Diploma', '2024/2025', 'Semester 1', 'Examination', 75000.00, 'Examination Fee', 1, '2026-07-03 04:05:12'),
(40, 'Diploma in Nursing Education', 'Diploma', '2024/2025', 'Semester 1', 'Practical', 130000.00, 'Practical / Clinical Fee', 1, '2026-07-03 04:05:12'),
(41, 'Certificate in Nursing', 'Certificate', '2024/2025', 'Semester 1', 'Tuition', 850000.00, 'Semester 1 Tuition Fee', 1, '2026-07-03 04:38:06'),
(42, 'Certificate in Nursing', 'Certificate', '2024/2025', 'Semester 1', 'Functional', 150000.00, 'Functional Fee', 1, '2026-07-03 04:38:06'),
(43, 'Certificate in Nursing', 'Certificate', '2024/2025', 'Semester 1', 'Examination', 50000.00, 'Examination Fee', 1, '2026-07-03 04:38:06'),
(44, 'Certificate in Nursing', 'Certificate', '2024/2025', 'Semester 1', 'Practical', 100000.00, 'Practical / Clinical Fee', 1, '2026-07-03 04:38:06'),
(45, 'Certificate in Midwifery', 'Certificate', '2024/2025', 'Semester 1', 'Tuition', 900000.00, 'Semester 1 Tuition Fee', 1, '2026-07-03 04:38:06'),
(46, 'Certificate in Midwifery', 'Certificate', '2024/2025', 'Semester 1', 'Functional', 150000.00, 'Functional Fee', 1, '2026-07-03 04:38:06'),
(47, 'Certificate in Midwifery', 'Certificate', '2024/2025', 'Semester 1', 'Examination', 50000.00, 'Examination Fee', 1, '2026-07-03 04:38:06'),
(48, 'Certificate in Midwifery', 'Certificate', '2024/2025', 'Semester 1', 'Practical', 120000.00, 'Practical / Clinical Fee', 1, '2026-07-03 04:38:06'),
(49, 'Diploma in Nursing', 'Diploma', '2024/2025', 'Semester 1', 'Tuition', 1200000.00, 'Semester 1 Tuition Fee', 1, '2026-07-03 04:38:06'),
(50, 'Diploma in Nursing', 'Diploma', '2024/2025', 'Semester 1', 'Functional', 200000.00, 'Functional Fee', 1, '2026-07-03 04:38:06'),
(51, 'Diploma in Nursing', 'Diploma', '2024/2025', 'Semester 1', 'Examination', 75000.00, 'Examination Fee', 1, '2026-07-03 04:38:06'),
(52, 'Diploma in Nursing', 'Diploma', '2024/2025', 'Semester 1', 'Practical', 150000.00, 'Practical / Clinical Fee', 1, '2026-07-03 04:38:06'),
(53, 'Diploma in Midwifery', 'Diploma', '2024/2025', 'Semester 1', 'Tuition', 1250000.00, 'Semester 1 Tuition Fee', 1, '2026-07-03 04:38:06'),
(54, 'Diploma in Midwifery', 'Diploma', '2024/2025', 'Semester 1', 'Functional', 200000.00, 'Functional Fee', 1, '2026-07-03 04:38:06'),
(55, 'Diploma in Midwifery', 'Diploma', '2024/2025', 'Semester 1', 'Examination', 75000.00, 'Examination Fee', 1, '2026-07-03 04:38:06'),
(56, 'Diploma in Midwifery', 'Diploma', '2024/2025', 'Semester 1', 'Practical', 160000.00, 'Practical / Clinical Fee', 1, '2026-07-03 04:38:06'),
(57, 'Diploma in Nursing Education', 'Diploma', '2024/2025', 'Semester 1', 'Tuition', 1100000.00, 'Semester 1 Tuition Fee', 1, '2026-07-03 04:38:06'),
(58, 'Diploma in Nursing Education', 'Diploma', '2024/2025', 'Semester 1', 'Functional', 180000.00, 'Functional Fee', 1, '2026-07-03 04:38:06'),
(59, 'Diploma in Nursing Education', 'Diploma', '2024/2025', 'Semester 1', 'Examination', 75000.00, 'Examination Fee', 1, '2026-07-03 04:38:06'),
(60, 'Diploma in Nursing Education', 'Diploma', '2024/2025', 'Semester 1', 'Practical', 130000.00, 'Practical / Clinical Fee', 1, '2026-07-03 04:38:06'),
(61, 'Certificate in Nursing', 'Certificate', '2024/2025', 'Semester 1', 'Tuition', 850000.00, 'Semester 1 Tuition Fee', 1, '2026-07-03 04:51:14'),
(62, 'Certificate in Nursing', 'Certificate', '2024/2025', 'Semester 1', 'Functional', 150000.00, 'Functional Fee', 1, '2026-07-03 04:51:14'),
(63, 'Certificate in Nursing', 'Certificate', '2024/2025', 'Semester 1', 'Examination', 50000.00, 'Examination Fee', 1, '2026-07-03 04:51:14'),
(64, 'Certificate in Nursing', 'Certificate', '2024/2025', 'Semester 1', 'Practical', 100000.00, 'Practical / Clinical Fee', 1, '2026-07-03 04:51:14'),
(65, 'Certificate in Midwifery', 'Certificate', '2024/2025', 'Semester 1', 'Tuition', 900000.00, 'Semester 1 Tuition Fee', 1, '2026-07-03 04:51:14'),
(66, 'Certificate in Midwifery', 'Certificate', '2024/2025', 'Semester 1', 'Functional', 150000.00, 'Functional Fee', 1, '2026-07-03 04:51:14'),
(67, 'Certificate in Midwifery', 'Certificate', '2024/2025', 'Semester 1', 'Examination', 50000.00, 'Examination Fee', 1, '2026-07-03 04:51:14'),
(68, 'Certificate in Midwifery', 'Certificate', '2024/2025', 'Semester 1', 'Practical', 120000.00, 'Practical / Clinical Fee', 1, '2026-07-03 04:51:14'),
(69, 'Diploma in Nursing', 'Diploma', '2024/2025', 'Semester 1', 'Tuition', 1200000.00, 'Semester 1 Tuition Fee', 1, '2026-07-03 04:51:14'),
(70, 'Diploma in Nursing', 'Diploma', '2024/2025', 'Semester 1', 'Functional', 200000.00, 'Functional Fee', 1, '2026-07-03 04:51:14'),
(71, 'Diploma in Nursing', 'Diploma', '2024/2025', 'Semester 1', 'Examination', 75000.00, 'Examination Fee', 1, '2026-07-03 04:51:14'),
(72, 'Diploma in Nursing', 'Diploma', '2024/2025', 'Semester 1', 'Practical', 150000.00, 'Practical / Clinical Fee', 1, '2026-07-03 04:51:14'),
(73, 'Diploma in Midwifery', 'Diploma', '2024/2025', 'Semester 1', 'Tuition', 1250000.00, 'Semester 1 Tuition Fee', 1, '2026-07-03 04:51:14'),
(74, 'Diploma in Midwifery', 'Diploma', '2024/2025', 'Semester 1', 'Functional', 200000.00, 'Functional Fee', 1, '2026-07-03 04:51:14'),
(75, 'Diploma in Midwifery', 'Diploma', '2024/2025', 'Semester 1', 'Examination', 75000.00, 'Examination Fee', 1, '2026-07-03 04:51:14'),
(76, 'Diploma in Midwifery', 'Diploma', '2024/2025', 'Semester 1', 'Practical', 160000.00, 'Practical / Clinical Fee', 1, '2026-07-03 04:51:14'),
(77, 'Diploma in Nursing Education', 'Diploma', '2024/2025', 'Semester 1', 'Tuition', 1100000.00, 'Semester 1 Tuition Fee', 1, '2026-07-03 04:51:14'),
(78, 'Diploma in Nursing Education', 'Diploma', '2024/2025', 'Semester 1', 'Functional', 180000.00, 'Functional Fee', 1, '2026-07-03 04:51:14'),
(79, 'Diploma in Nursing Education', 'Diploma', '2024/2025', 'Semester 1', 'Examination', 75000.00, 'Examination Fee', 1, '2026-07-03 04:51:14'),
(80, 'Diploma in Nursing Education', 'Diploma', '2024/2025', 'Semester 1', 'Practical', 130000.00, 'Practical / Clinical Fee', 1, '2026-07-03 04:51:14');

-- --------------------------------------------------------

--
-- Table structure for table `fee_structures`
--

CREATE TABLE `fee_structures` (
  `id` int(11) NOT NULL,
  `fee_name` varchar(255) NOT NULL,
  `fee_type` enum('Tuition','Registration','Library','Laboratory','Examination','Graduation','Other') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `is_mandatory` tinyint(1) DEFAULT 1,
  `due_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `finance_assets`
--

CREATE TABLE `finance_assets` (
  `id` int(11) NOT NULL,
  `asset_name` varchar(300) DEFAULT NULL,
  `asset_tag` varchar(100) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_price` decimal(14,2) DEFAULT 0.00,
  `current_value` decimal(14,2) DEFAULT 0.00,
  `depreciation_rate` decimal(5,2) DEFAULT 0.00,
  `location` varchar(200) DEFAULT NULL,
  `assigned_to` varchar(200) DEFAULT NULL,
  `status` enum('active','disposed','maintenance') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `finance_messages`
--

CREATE TABLE `finance_messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT 0,
  `sender_name` varchar(200) DEFAULT NULL,
  `recipient_role` varchar(100) DEFAULT NULL,
  `subject` varchar(300) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `finance_notices`
--

CREATE TABLE `finance_notices` (
  `id` int(11) NOT NULL,
  `title` varchar(300) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `audience` varchar(100) DEFAULT NULL,
  `published_by` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_clearance`
--

CREATE TABLE `financial_clearance` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` varchar(20) DEFAULT 'Annual',
  `clearance_status` enum('Cleared','Not Cleared','Pending Review') DEFAULT 'Pending Review',
  `cleared_by` int(11) DEFAULT NULL,
  `cleared_at` timestamp NULL DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_messages`
--

CREATE TABLE `financial_messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `sender_role` varchar(100) DEFAULT NULL,
  `recipient_role` varchar(100) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_notices`
--

CREATE TABLE `financial_notices` (
  `id` int(11) NOT NULL,
  `title` varchar(200) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `audience` varchar(50) DEFAULT 'all',
  `published_by` int(11) DEFAULT NULL,
  `published_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_reports`
--

CREATE TABLE `financial_reports` (
  `id` int(11) NOT NULL,
  `report_name` varchar(255) NOT NULL,
  `report_type` enum('Income Statement','Balance Sheet','Cash Flow','Budget vs Actual','Fee Collection','Expenditure','Custom') NOT NULL,
  `report_period` varchar(50) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `report_data` longtext DEFAULT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `generated_at` timestamp NULL DEFAULT current_timestamp(),
  `status` enum('Draft','Final','Archived') DEFAULT 'Draft',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `form_submissions`
--

CREATE TABLE `form_submissions` (
  `id` int(11) NOT NULL,
  `form_type` varchar(50) NOT NULL COMMENT 'application, contact, feedback, complaint, volunteer',
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` longtext DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending' COMMENT 'pending, read, responded, closed',
  `assigned_to` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `general_ledger`
--

CREATE TABLE `general_ledger` (
  `id` int(11) NOT NULL,
  `entry_number` varchar(50) NOT NULL,
  `account_id` int(11) NOT NULL,
  `cost_center_id` int(11) DEFAULT NULL,
  `transaction_type` enum('Debit','Credit') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `transaction_date` date DEFAULT curdate(),
  `posted_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gpa_settings`
--

CREATE TABLE `gpa_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `updated_by` int(11) DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gpa_settings`
--

INSERT INTO `gpa_settings` (`id`, `setting_key`, `setting_value`, `description`, `updated_by`, `updated_at`) VALUES
(1, 'gpa_max', '4.00', 'Maximum GPA', 0, '2026-07-12 10:04:39'),
(2, 'pass_mark', '50', 'Minimum passing percentage', 0, '2026-07-12 10:04:39'),
(3, 'auto_gpa', '1', 'Auto-calculate GPA', 0, '2026-07-12 10:04:39');

-- --------------------------------------------------------

--
-- Table structure for table `graduation_approvals`
--

CREATE TABLE `graduation_approvals` (
  `id` int(11) NOT NULL,
  `candidate_id` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT 0,
  `approval_level` varchar(100) DEFAULT 'Registrar',
  `status` varchar(50) DEFAULT 'Pending',
  `remarks` text DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `graduation_candidates`
--

CREATE TABLE `graduation_candidates` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `graduation_date` date DEFAULT NULL,
  `status` enum('Pending','Cleared','Graduated','Deferred') DEFAULT 'Pending',
  `clearance_bursar` tinyint(1) DEFAULT 0,
  `clearance_library` tinyint(1) DEFAULT 0,
  `clearance_registrar` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `guild_feedback`
--

CREATE TABLE `guild_feedback` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `priority` enum('normal','important','urgent') DEFAULT 'normal',
  `status` enum('pending','reviewed','acted') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hostel_allocations`
--

CREATE TABLE `hostel_allocations` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `room_id` int(11) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `check_in_date` date DEFAULT curdate(),
  `check_out_date` date DEFAULT NULL,
  `status` enum('Active','Checked Out','Cancelled') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hostel_blocks`
--

CREATE TABLE `hostel_blocks` (
  `id` int(11) NOT NULL,
  `block_name` varchar(100) NOT NULL,
  `total_rooms` int(11) DEFAULT 0,
  `gender` enum('Male','Female','Mixed') DEFAULT 'Mixed',
  `status` enum('Active','Inactive','Maintenance') DEFAULT 'Active',
  `warden_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `hostel_blocks`
--

INSERT INTO `hostel_blocks` (`id`, `block_name`, `total_rooms`, `gender`, `status`, `warden_id`, `created_at`) VALUES
(1, 'Block A - Queen Anne', 24, 'Female', 'Active', NULL, '2026-07-03 04:51:14'),
(2, 'Block B - Victoria', 24, 'Female', 'Active', NULL, '2026-07-03 04:51:14'),
(3, 'Block C - Florence Nightingale', 16, 'Female', 'Active', NULL, '2026-07-03 04:51:14'),
(4, 'Block D - Mary Seacole', 16, 'Female', 'Active', NULL, '2026-07-03 04:51:14'),
(5, 'Block E - Male Hostel', 16, 'Male', 'Active', NULL, '2026-07-03 04:51:14');

-- --------------------------------------------------------

--
-- Table structure for table `hostel_rooms`
--

CREATE TABLE `hostel_rooms` (
  `id` int(11) NOT NULL,
  `room_number` varchar(20) NOT NULL,
  `hostel_name` varchar(100) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 4,
  `occupancy` int(11) NOT NULL DEFAULT 0,
  `fee_per_semester` decimal(12,2) DEFAULT 0.00,
  `status` enum('Available','Full','Maintenance') DEFAULT 'Available',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hostel_rooms`
--

INSERT INTO `hostel_rooms` (`id`, `room_number`, `hostel_name`, `capacity`, `occupancy`, `fee_per_semester`, `status`, `created_at`) VALUES
(1, 'QA-1-01', 'Block A - Queen Anne', 4, 3, 250000.00, 'Available', '2026-07-03 04:51:14'),
(2, 'QA-2-01', 'Block A - Queen Anne', 4, 0, 250000.00, 'Full', '2026-07-03 04:51:14'),
(3, 'QA-3-01', 'Block A - Queen Anne', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(4, 'VB-1-01', 'Block B - Victoria', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(5, 'VB-2-01', 'Block B - Victoria', 4, 0, 250000.00, 'Full', '2026-07-03 04:51:14'),
(6, 'VB-3-01', 'Block B - Victoria', 4, 2, 250000.00, 'Available', '2026-07-03 04:51:14'),
(7, 'FN-1-01', 'Block C - Florence Nightingale', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(8, 'FN-2-01', 'Block C - Florence Nightingale', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(9, 'FN-3-01', 'Block C - Florence Nightingale', 4, 0, 250000.00, 'Full', '2026-07-03 04:51:14'),
(10, 'MS-1-01', 'Block D - Mary Seacole', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(11, 'MS-2-01', 'Block D - Mary Seacole', 4, 1, 250000.00, 'Available', '2026-07-03 04:51:14'),
(12, 'MS-3-01', 'Block D - Mary Seacole', 4, 2, 250000.00, 'Available', '2026-07-03 04:51:14'),
(13, 'MH-1-01', 'Block E - Male Hostel', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(14, 'MH-2-01', 'Block E - Male Hostel', 4, 3, 250000.00, 'Available', '2026-07-03 04:51:14'),
(15, 'QA-1-02', 'Block A - Queen Anne', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(16, 'QA-2-02', 'Block A - Queen Anne', 4, 0, 250000.00, 'Full', '2026-07-03 04:51:14'),
(17, 'QA-3-02', 'Block A - Queen Anne', 4, 0, 250000.00, 'Full', '2026-07-03 04:51:14'),
(18, 'VB-1-02', 'Block B - Victoria', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(19, 'VB-2-02', 'Block B - Victoria', 4, 1, 250000.00, 'Available', '2026-07-03 04:51:14'),
(20, 'VB-3-02', 'Block B - Victoria', 4, 0, 250000.00, 'Full', '2026-07-03 04:51:14'),
(21, 'FN-1-02', 'Block C - Florence Nightingale', 4, 0, 250000.00, 'Full', '2026-07-03 04:51:14'),
(22, 'FN-2-02', 'Block C - Florence Nightingale', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(23, 'FN-3-02', 'Block C - Florence Nightingale', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(24, 'MS-1-02', 'Block D - Mary Seacole', 4, 3, 250000.00, 'Full', '2026-07-03 04:51:14'),
(25, 'MS-2-02', 'Block D - Mary Seacole', 4, 1, 250000.00, 'Available', '2026-07-03 04:51:14'),
(26, 'MS-3-02', 'Block D - Mary Seacole', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(27, 'MH-1-02', 'Block E - Male Hostel', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(28, 'MH-2-02', 'Block E - Male Hostel', 4, 0, 250000.00, 'Full', '2026-07-03 04:51:14'),
(29, 'QA-1-03', 'Block A - Queen Anne', 4, 2, 250000.00, 'Available', '2026-07-03 04:51:14'),
(30, 'QA-2-03', 'Block A - Queen Anne', 4, 1, 250000.00, 'Available', '2026-07-03 04:51:14'),
(31, 'QA-3-03', 'Block A - Queen Anne', 4, 0, 250000.00, 'Full', '2026-07-03 04:51:14'),
(32, 'VB-1-03', 'Block B - Victoria', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(33, 'VB-2-03', 'Block B - Victoria', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(34, 'VB-3-03', 'Block B - Victoria', 4, 0, 250000.00, 'Full', '2026-07-03 04:51:14'),
(35, 'FN-1-03', 'Block C - Florence Nightingale', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(36, 'FN-2-03', 'Block C - Florence Nightingale', 4, 0, 250000.00, 'Full', '2026-07-03 04:51:14'),
(37, 'FN-3-03', 'Block C - Florence Nightingale', 4, 0, 250000.00, 'Full', '2026-07-03 04:51:14'),
(38, 'MS-1-03', 'Block D - Mary Seacole', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(39, 'MS-2-03', 'Block D - Mary Seacole', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(40, 'MS-3-03', 'Block D - Mary Seacole', 4, 2, 250000.00, 'Available', '2026-07-03 04:51:14'),
(41, 'MH-1-03', 'Block E - Male Hostel', 4, 3, 250000.00, 'Available', '2026-07-03 04:51:14'),
(42, 'MH-2-03', 'Block E - Male Hostel', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(43, 'QA-1-04', 'Block A - Queen Anne', 4, 2, 250000.00, 'Available', '2026-07-03 04:51:14'),
(44, 'QA-2-04', 'Block A - Queen Anne', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(45, 'QA-3-04', 'Block A - Queen Anne', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(46, 'VB-1-04', 'Block B - Victoria', 4, 3, 250000.00, 'Full', '2026-07-03 04:51:14'),
(47, 'VB-2-04', 'Block B - Victoria', 4, 3, 250000.00, 'Available', '2026-07-03 04:51:14'),
(48, 'VB-3-04', 'Block B - Victoria', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(49, 'FN-1-04', 'Block C - Florence Nightingale', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(50, 'FN-2-04', 'Block C - Florence Nightingale', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(51, 'FN-3-04', 'Block C - Florence Nightingale', 4, 1, 250000.00, 'Available', '2026-07-03 04:51:14'),
(52, 'MS-1-04', 'Block D - Mary Seacole', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(53, 'MS-2-04', 'Block D - Mary Seacole', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(54, 'MS-3-04', 'Block D - Mary Seacole', 4, 1, 250000.00, 'Full', '2026-07-03 04:51:14'),
(55, 'MH-1-04', 'Block E - Male Hostel', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(56, 'MH-2-04', 'Block E - Male Hostel', 4, 3, 250000.00, 'Full', '2026-07-03 04:51:14'),
(57, 'QA-1-05', 'Block A - Queen Anne', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(58, 'QA-2-05', 'Block A - Queen Anne', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(59, 'QA-3-05', 'Block A - Queen Anne', 4, 3, 250000.00, 'Available', '2026-07-03 04:51:14'),
(60, 'VB-1-05', 'Block B - Victoria', 4, 1, 250000.00, 'Available', '2026-07-03 04:51:14'),
(61, 'VB-2-05', 'Block B - Victoria', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(62, 'VB-3-05', 'Block B - Victoria', 4, 3, 250000.00, 'Full', '2026-07-03 04:51:14'),
(63, 'FN-1-05', 'Block C - Florence Nightingale', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(64, 'FN-2-05', 'Block C - Florence Nightingale', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(65, 'FN-3-05', 'Block C - Florence Nightingale', 4, 3, 250000.00, 'Available', '2026-07-03 04:51:14'),
(66, 'MS-1-05', 'Block D - Mary Seacole', 4, 0, 250000.00, 'Full', '2026-07-03 04:51:14'),
(67, 'MS-2-05', 'Block D - Mary Seacole', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(68, 'MS-3-05', 'Block D - Mary Seacole', 4, 0, 250000.00, 'Full', '2026-07-03 04:51:14'),
(69, 'MH-1-05', 'Block E - Male Hostel', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(70, 'MH-2-05', 'Block E - Male Hostel', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(71, 'QA-1-06', 'Block A - Queen Anne', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(72, 'QA-2-06', 'Block A - Queen Anne', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(73, 'QA-3-06', 'Block A - Queen Anne', 4, 2, 250000.00, 'Available', '2026-07-03 04:51:14'),
(74, 'VB-1-06', 'Block B - Victoria', 4, 3, 250000.00, 'Full', '2026-07-03 04:51:14'),
(75, 'VB-2-06', 'Block B - Victoria', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(76, 'VB-3-06', 'Block B - Victoria', 4, 0, 250000.00, 'Full', '2026-07-03 04:51:14'),
(77, 'FN-1-06', 'Block C - Florence Nightingale', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(78, 'FN-2-06', 'Block C - Florence Nightingale', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(79, 'FN-3-06', 'Block C - Florence Nightingale', 4, 1, 250000.00, 'Available', '2026-07-03 04:51:14'),
(80, 'MS-1-06', 'Block D - Mary Seacole', 4, 1, 250000.00, 'Available', '2026-07-03 04:51:14'),
(81, 'MS-2-06', 'Block D - Mary Seacole', 4, 0, 250000.00, 'Full', '2026-07-03 04:51:14'),
(82, 'MS-3-06', 'Block D - Mary Seacole', 4, 2, 250000.00, 'Available', '2026-07-03 04:51:14'),
(83, 'MH-1-06', 'Block E - Male Hostel', 4, 0, 250000.00, 'Full', '2026-07-03 04:51:14'),
(84, 'MH-2-06', 'Block E - Male Hostel', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(85, 'QA-1-07', 'Block A - Queen Anne', 4, 3, 250000.00, 'Full', '2026-07-03 04:51:14'),
(86, 'QA-2-07', 'Block A - Queen Anne', 4, 2, 250000.00, 'Full', '2026-07-03 04:51:14'),
(87, 'QA-3-07', 'Block A - Queen Anne', 4, 3, 250000.00, 'Available', '2026-07-03 04:51:14'),
(88, 'VB-1-07', 'Block B - Victoria', 4, 3, 250000.00, 'Full', '2026-07-03 04:51:14'),
(89, 'VB-2-07', 'Block B - Victoria', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(90, 'VB-3-07', 'Block B - Victoria', 4, 0, 250000.00, 'Full', '2026-07-03 04:51:14'),
(91, 'FN-1-07', 'Block C - Florence Nightingale', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(92, 'FN-2-07', 'Block C - Florence Nightingale', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(93, 'FN-3-07', 'Block C - Florence Nightingale', 4, 2, 250000.00, 'Available', '2026-07-03 04:51:14'),
(94, 'MS-1-07', 'Block D - Mary Seacole', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(95, 'MS-2-07', 'Block D - Mary Seacole', 4, 3, 250000.00, 'Available', '2026-07-03 04:51:14'),
(96, 'MS-3-07', 'Block D - Mary Seacole', 4, 0, 250000.00, 'Full', '2026-07-03 04:51:14'),
(97, 'MH-1-07', 'Block E - Male Hostel', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(98, 'MH-2-07', 'Block E - Male Hostel', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(99, 'QA-1-08', 'Block A - Queen Anne', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(100, 'QA-2-08', 'Block A - Queen Anne', 4, 1, 250000.00, 'Full', '2026-07-03 04:51:14'),
(101, 'QA-3-08', 'Block A - Queen Anne', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(102, 'VB-1-08', 'Block B - Victoria', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(103, 'VB-2-08', 'Block B - Victoria', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(104, 'VB-3-08', 'Block B - Victoria', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(105, 'FN-1-08', 'Block C - Florence Nightingale', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(106, 'FN-2-08', 'Block C - Florence Nightingale', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(107, 'FN-3-08', 'Block C - Florence Nightingale', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(108, 'MS-1-08', 'Block D - Mary Seacole', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(109, 'MS-2-08', 'Block D - Mary Seacole', 4, 1, 250000.00, 'Available', '2026-07-03 04:51:14'),
(110, 'MS-3-08', 'Block D - Mary Seacole', 4, 0, 250000.00, 'Full', '2026-07-03 04:51:14'),
(111, 'MH-1-08', 'Block E - Male Hostel', 4, 0, 250000.00, 'Available', '2026-07-03 04:51:14'),
(112, 'MH-2-08', 'Block E - Male Hostel', 4, 1, 250000.00, 'Full', '2026-07-03 04:51:14');

-- --------------------------------------------------------

--
-- Table structure for table `improvement_tracking`
--

CREATE TABLE `improvement_tracking` (
  `id` int(11) NOT NULL,
  `area` varchar(200) DEFAULT NULL,
  `improvement_action` text DEFAULT NULL,
  `target_date` date DEFAULT NULL,
  `progress` decimal(5,2) DEFAULT 0.00,
  `status` enum('planned','in_progress','completed') DEFAULT 'planned',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `income_tax_rates`
--

CREATE TABLE `income_tax_rates` (
  `id` int(11) NOT NULL,
  `tax_bracket_name` varchar(100) NOT NULL,
  `min_income` decimal(12,2) NOT NULL DEFAULT 0.00,
  `max_income` decimal(12,2) DEFAULT NULL,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 0.00,
  `fiscal_year` varchar(10) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `institutional_kpis`
--

CREATE TABLE `institutional_kpis` (
  `id` int(11) NOT NULL,
  `kpi_name` varchar(300) DEFAULT NULL,
  `kpi_category` varchar(200) DEFAULT NULL,
  `target_value` decimal(14,2) DEFAULT NULL,
  `current_value` decimal(14,2) DEFAULT NULL,
  `period` varchar(50) DEFAULT NULL,
  `status` enum('on_track','at_risk','behind') DEFAULT 'on_track',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `intakes`
--

CREATE TABLE `intakes` (
  `id` int(11) NOT NULL,
  `intake_name` varchar(100) NOT NULL,
  `intake_month` varchar(20) NOT NULL,
  `intake_year` year(4) NOT NULL,
  `application_start` date DEFAULT NULL,
  `application_deadline` date DEFAULT NULL,
  `status` enum('Open','Closed','Upcoming') NOT NULL DEFAULT 'Upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_attendance`
--

CREATE TABLE `lab_attendance` (
  `id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `attendance_status` enum('present','absent','late','excused') DEFAULT 'present',
  `check_in_time` time DEFAULT NULL,
  `check_out_time` time DEFAULT NULL,
  `marked_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_consumables`
--

CREATE TABLE `lab_consumables` (
  `id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit` varchar(50) NOT NULL DEFAULT 'pieces',
  `min_stock_level` decimal(10,2) DEFAULT 10.00,
  `unit_cost` decimal(10,2) DEFAULT 0.00,
  `supplier` varchar(255) DEFAULT NULL,
  `last_ordered_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_equipment`
--

CREATE TABLE `lab_equipment` (
  `id` int(11) NOT NULL,
  `equipment_code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('mannequin','model','instrument','furniture','consumable','other') NOT NULL DEFAULT 'other',
  `quantity` int(11) NOT NULL DEFAULT 1,
  `available_quantity` int(11) NOT NULL DEFAULT 1,
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
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_equipment_checkouts`
--

CREATE TABLE `lab_equipment_checkouts` (
  `id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `checked_out_by` int(11) NOT NULL COMMENT 'staff_id',
  `checkout_date` datetime DEFAULT current_timestamp(),
  `expected_return_date` date NOT NULL,
  `actual_return_date` datetime DEFAULT NULL,
  `quantity_checked_out` int(11) NOT NULL DEFAULT 1,
  `quantity_returned` int(11) DEFAULT 0,
  `purpose` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('checked_out','returned','overdue','lost_damaged') DEFAULT 'checked_out',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_incidents`
--

CREATE TABLE `lab_incidents` (
  `id` int(11) NOT NULL,
  `incident_date` date NOT NULL DEFAULT curdate(),
  `incident_time` time DEFAULT NULL,
  `reported_by` int(11) DEFAULT NULL,
  `incident_type` enum('injury','equipment_damage','safety_hazard','near_miss','other') NOT NULL DEFAULT 'other',
  `severity` enum('minor','moderate','serious','critical') DEFAULT 'minor',
  `description` text NOT NULL,
  `equipment_involved` varchar(255) DEFAULT NULL,
  `student_involved` varchar(255) DEFAULT NULL,
  `action_taken` text DEFAULT NULL,
  `status` enum('open','investigating','resolved','closed') DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_inventory_items`
--

CREATE TABLE `lab_inventory_items` (
  `id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT 'General',
  `quantity` int(11) DEFAULT 0,
  `minimum_level` int(11) DEFAULT 0,
  `unit` varchar(50) DEFAULT 'piece',
  `location` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive','Out of Stock') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_practical_sessions`
--

CREATE TABLE `lab_practical_sessions` (
  `id` int(11) NOT NULL,
  `session_code` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `instructor` varchar(255) DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `year_level` varchar(50) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `session_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `max_students` int(11) DEFAULT 30,
  `status` enum('scheduled','ongoing','completed','cancelled') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_skills_demonstrations`
--

CREATE TABLE `lab_skills_demonstrations` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `skill_name` varchar(255) NOT NULL,
  `skill_category` varchar(100) DEFAULT NULL,
  `instructor` varchar(255) DEFAULT NULL,
  `date_demonstrated` date NOT NULL DEFAULT curdate(),
  `competency` enum('exceeds_expectations','meets_expectations','needs_improvement','unsatisfactory') DEFAULT 'meets_expectations',
  `attempt_number` int(11) DEFAULT 1,
  `notes` text DEFAULT NULL,
  `next_review_date` date DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `late_payment_settings`
--

CREATE TABLE `late_payment_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
-- Table structure for table `lesson_plans`
--

CREATE TABLE `lesson_plans` (
  `id` int(11) NOT NULL,
  `lecturer_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `topic` varchar(255) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `course_name` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `objectives` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_acquisitions`
--

CREATE TABLE `library_acquisitions` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `isbn` varchar(50) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `publication_year` year(4) DEFAULT NULL,
  `acquisition_type` enum('Purchase','Donation','Exchange','Subscription','Other') DEFAULT 'Purchase',
  `quantity` int(11) DEFAULT 1,
  `unit_cost` decimal(15,2) DEFAULT NULL,
  `total_cost` decimal(15,2) DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `acquisition_date` date NOT NULL,
  `received_date` date DEFAULT NULL,
  `shelf_location` varchar(100) DEFAULT NULL,
  `status` enum('Ordered','Received','Processed','Rejected') DEFAULT 'Ordered',
  `acquired_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_books`
--

CREATE TABLE `library_books` (
  `id` int(11) NOT NULL,
  `book_title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `isbn` varchar(50) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `publication_year` year(4) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `total_copies` int(11) DEFAULT 1,
  `available_copies` int(11) DEFAULT 1,
  `shelf_location` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `library_books`
--

INSERT INTO `library_books` (`id`, `book_title`, `author`, `isbn`, `publisher`, `publication_year`, `category`, `total_copies`, `available_copies`, `shelf_location`, `created_at`) VALUES
(1, 'Myles Textbook for Midwives', 'Jayne Marshall', '978-0702051876', 'Elsevier', '2021', 'Textbook', 6, 5, 'Section A - Shelf 1', '2026-07-03 04:38:06'),
(2, 'Myles Textbook for Midwives', 'Jayne Marshall', '978-0702051876', 'Elsevier', '2021', 'Textbook', 6, 5, 'Section A - Shelf 1', '2026-07-03 04:51:14'),
(3, 'Fundamentals of Nursing', 'Carol Taylor', '978-1496384584', 'Wolters Kluwer', '2022', 'Textbook', 10, 8, 'Section A - Shelf 2', '2026-07-03 04:38:06'),
(4, 'Fundamentals of Nursing', 'Carol Taylor', '978-1496384584', 'Wolters Kluwer', '2022', 'Textbook', 10, 8, 'Section A - Shelf 2', '2026-07-03 04:51:14'),
(5, 'Medical-Surgical Nursing', 'Donna Ignatavicius', '978-0323596480', 'Elsevier', '2021', 'Textbook', 5, 4, 'Section A - Shelf 3', '2026-07-03 04:38:06'),
(6, 'Medical-Surgical Nursing', 'Donna Ignatavicius', '978-0323596480', 'Elsevier', '2021', 'Textbook', 5, 4, 'Section A - Shelf 3', '2026-07-03 04:51:14'),
(7, 'Anatomy and Physiology for Nurses', 'Roger Watson', '978-1608318023', 'Saunders', '2020', 'Textbook', 7, 6, 'Section A - Shelf 4', '2026-07-03 04:38:06'),
(8, 'Anatomy and Physiology for Nurses', 'Roger Watson', '978-1608318023', 'Saunders', '2020', 'Textbook', 7, 6, 'Section A - Shelf 4', '2026-07-03 04:51:14'),
(9, 'Pharmacology for Nurses', 'Michael Weatherley', '978-0702077111', 'Elsevier', '2022', 'Textbook', 4, 3, 'Section A - Shelf 5', '2026-07-03 04:38:06'),
(10, 'Pharmacology for Nurses', 'Michael Weatherley', '978-0702077111', 'Elsevier', '2022', 'Textbook', 4, 3, 'Section A - Shelf 5', '2026-07-03 04:51:14'),
(11, 'Psychiatric Mental Health Nursing', 'Mary Ann Boyd', '978-1496309112', 'Wolters Kluwer', '2021', 'Textbook', 5, 4, 'Section B - Shelf 1', '2026-07-03 04:38:06'),
(12, 'Psychiatric Mental Health Nursing', 'Mary Ann Boyd', '978-1496309112', 'Wolters Kluwer', '2021', 'Textbook', 5, 4, 'Section B - Shelf 1', '2026-07-03 04:51:14'),
(13, 'Community Health Nursing', 'Mary Jo Clark', '978-1284165210', 'Jones & Bartlett', '2022', 'Textbook', 5, 5, 'Section B - Shelf 2', '2026-07-03 04:38:06'),
(14, 'Community Health Nursing', 'Mary Jo Clark', '978-1284165210', 'Jones & Bartlett', '2022', 'Textbook', 5, 5, 'Section B - Shelf 2', '2026-07-03 04:51:14'),
(15, 'Maternal Child Nursing Care', 'Shannon Perry', '978-1496309112', 'Elsevier', '2022', 'Textbook', 6, 6, 'Section B - Shelf 3', '2026-07-03 04:38:06'),
(16, 'Maternal Child Nursing Care', 'Shannon Perry', '978-1496309112', 'Elsevier', '2022', 'Textbook', 6, 6, 'Section B - Shelf 3', '2026-07-03 04:51:14'),
(17, 'Pediatric Nursing', 'Mary Jo Brancaglioni', '978-1608317790', 'Saunders', '2021', 'Textbook', 4, 3, 'Section B - Shelf 4', '2026-07-03 04:38:06'),
(18, 'Pediatric Nursing', 'Mary Jo Brancaglioni', '978-1608317790', 'Saunders', '2021', 'Textbook', 4, 3, 'Section B - Shelf 4', '2026-07-03 04:51:14'),
(19, 'Clinical Skills for Nursing', 'Elizabeth Boahene', '978-0702073144', 'Elsevier', '2023', 'Reference', 5, 5, 'Section C - Shelf 1', '2026-07-03 04:38:06'),
(20, 'Clinical Skills for Nursing', 'Elizabeth Boahene', '978-0702073144', 'Elsevier', '2023', 'Reference', 5, 5, 'Section C - Shelf 1', '2026-07-03 04:51:14'),
(21, 'Nursing Research Methods', 'Diane Polit', '978-1119538639', 'Wolters Kluwer', '2020', 'Reference', 4, 4, 'Section C - Shelf 2', '2026-07-03 04:38:06'),
(22, 'Nursing Research Methods', 'Diane Polit', '978-1119538639', 'Wolters Kluwer', '2020', 'Reference', 4, 4, 'Section C - Shelf 2', '2026-07-03 04:51:14'),
(23, 'Nursing Ethics & Professional Responsibility', 'Janie Butts', '978-0323476638', 'Jones & Bartlett', '2022', 'Reference', 3, 3, 'Section C - Shelf 3', '2026-07-03 04:38:06'),
(24, 'Nursing Ethics & Professional Responsibility', 'Janie Butts', '978-0323476638', 'Jones & Bartlett', '2022', 'Reference', 3, 3, 'Section C - Shelf 3', '2026-07-03 04:51:14'),
(25, 'Clinical Handbook of Fluids Electrolytes', 'Linda Honan', '978-1496384591', 'Wolters Kluwer', '2021', 'Handbook', 3, 2, 'Section C - Shelf 4', '2026-07-03 04:38:06'),
(26, 'Clinical Handbook of Fluids Electrolytes', 'Linda Honan', '978-1496384591', 'Wolters Kluwer', '2021', 'Handbook', 3, 2, 'Section C - Shelf 4', '2026-07-03 04:51:14'),
(27, 'Nursing Diagnosis Handbook', 'Gail Ackley', '978-0135218334', 'Elsevier', '2022', 'Handbook', 7, 6, 'Section D - Shelf 1', '2026-07-03 04:38:06'),
(28, 'Nursing Diagnosis Handbook', 'Gail Ackley', '978-0135218334', 'Elsevier', '2022', 'Handbook', 7, 6, 'Section D - Shelf 1', '2026-07-03 04:51:14'),
(29, 'UGANDA Nursing and Midwifery Council Guidelines', 'UNMC', '978-1719643436', 'UNMC Press', '2023', 'Regulation', 12, 10, 'Section D - Shelf 2', '2026-07-03 04:38:06'),
(30, 'UGANDA Nursing and Midwifery Council Guidelines', 'UNMC', '978-1719643436', 'UNMC Press', '2023', 'Regulation', 12, 10, 'Section D - Shelf 2', '2026-07-03 04:51:14'),
(31, 'Oxford Dictionary of Medical Terms', 'Oxford University Press', '978-0198765432', 'Oxford', '2020', 'Dictionary', 3, 3, 'Reference Desk', '2026-07-03 04:38:06'),
(32, 'Oxford Dictionary of Medical Terms', 'Oxford University Press', '978-0198765432', 'Oxford', '2020', 'Dictionary', 3, 3, 'Reference Desk', '2026-07-03 04:51:14'),
(33, 'Holes Human Anatomy & Physiology', 'David Shier', '978-0143774617', 'McGraw Hill', '2021', 'Textbook', 5, 4, 'Section A - Shelf 6', '2026-07-03 04:38:06'),
(34, 'Holes Human Anatomy & Physiology', 'David Shier', '978-0143774617', 'McGraw Hill', '2021', 'Textbook', 5, 4, 'Section A - Shelf 6', '2026-07-03 04:51:14'),
(35, 'Lippincott Manual of Nursing Practice', 'Sandra Nettina', '978-1605479767', 'Wolters Kluwer', '2022', 'Handbook', 5, 5, 'Reference Desk', '2026-07-03 04:38:06'),
(36, 'Lippincott Manual of Nursing Practice', 'Sandra Nettina', '978-1605479767', 'Wolters Kluwer', '2022', 'Handbook', 5, 5, 'Reference Desk', '2026-07-03 04:51:14'),
(37, 'Brunner & Suddarths Textbook of Medical-Surgical Nursing', 'Janice Hinkle', '978-0323555968', 'Wolters Kluwer', '2022', 'Textbook', 8, 6, 'Section A - Shelf 7', '2026-07-03 04:38:06'),
(38, 'Brunner & Suddarths Textbook of Medical-Surgical Nursing', 'Janice Hinkle', '978-0323555968', 'Wolters Kluwer', '2022', 'Textbook', 8, 6, 'Section A - Shelf 7', '2026-07-03 04:51:14'),
(39, 'Foundations of Nursing', 'Cooper Gosnell', '978-0134444819', 'Elsevier', '2020', 'Textbook', 8, 7, 'Section A - Shelf 8', '2026-07-03 04:38:06'),
(40, 'Foundations of Nursing', 'Cooper Gosnell', '978-0134444819', 'Elsevier', '2020', 'Textbook', 8, 7, 'Section A - Shelf 8', '2026-07-03 04:51:14');

-- --------------------------------------------------------

--
-- Table structure for table `library_borrowing`
--

CREATE TABLE `library_borrowing` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `book_id` int(11) NOT NULL,
  `borrow_date` date DEFAULT curdate(),
  `due_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `fine_amount` decimal(10,2) DEFAULT 0.00,
  `fine_paid` tinyint(1) DEFAULT 0,
  `status` enum('Borrowed','Returned','Overdue','Lost') DEFAULT 'Borrowed',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `library_borrowing`
--

INSERT INTO `library_borrowing` (`id`, `student_id`, `book_id`, `borrow_date`, `due_date`, `return_date`, `fine_amount`, `fine_paid`, `status`, `created_at`) VALUES
(1, 'ISNM/0001/25', 3, '2024-10-21', '2024-11-04', '2024-09-22', 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(2, 'ISNM/0002/25', 11, '2024-10-02', '2024-09-22', NULL, 16217.00, 1, 'Returned', '2026-07-03 04:51:14'),
(3, 'ISNM/0003/25', 10, '2024-09-29', '2024-11-08', NULL, 17106.00, 1, 'Overdue', '2026-07-03 04:51:14'),
(4, 'ISNM/0004/25', 19, '2024-09-30', '2024-10-22', '2024-10-11', 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(5, 'ISNM/0005/25', 12, '2024-09-24', '2024-09-30', NULL, 18876.00, 1, 'Returned', '2026-07-03 04:51:14'),
(6, 'ISNM/0006/25', 1, '2024-10-20', '2024-11-12', '2024-09-19', 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(7, 'ISNM/0007/25', 5, '2024-09-20', '2024-11-09', '2024-10-10', 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(8, 'ISNM/0008/25', 16, '2024-10-26', '2024-10-02', '2024-10-21', 1120.00, 0, 'Returned', '2026-07-03 04:51:14'),
(9, 'ISNM/0009/25', 19, '2024-10-22', '2024-10-17', NULL, 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(10, 'ISNM/0010/25', 17, '2024-09-23', '2024-10-06', '2024-09-25', 13131.00, 0, 'Returned', '2026-07-03 04:51:14'),
(11, 'ISNM/0011/25', 10, '2024-09-18', '2024-09-19', '2024-10-01', 22094.00, 1, 'Returned', '2026-07-03 04:51:14'),
(12, 'ISNM/0012/25', 17, '2024-09-11', '2024-10-11', '2024-09-21', 0.00, 0, 'Returned', '2026-07-03 04:51:14'),
(13, 'ISNM/0013/25', 8, '2024-09-11', '2024-10-28', NULL, 0.00, 0, 'Returned', '2026-07-03 04:51:14'),
(14, 'ISNM/0014/25', 13, '2024-09-01', '2024-09-21', '2024-10-09', 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(15, 'ISNM/0015/25', 12, '2024-10-28', '2024-11-12', NULL, 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(16, 'ISNM/0016/25', 5, '2024-09-27', '2024-10-19', '2024-11-13', 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(17, 'ISNM/0017/25', 3, '2024-10-06', '2024-10-19', NULL, 9609.00, 0, 'Returned', '2026-07-03 04:51:14'),
(18, 'ISNM/0018/25', 16, '2024-09-24', '2024-10-24', NULL, 7773.00, 1, 'Returned', '2026-07-03 04:51:14'),
(19, 'ISNM/0019/25', 11, '2024-10-01', '2024-09-16', '2024-11-10', 29770.00, 0, 'Returned', '2026-07-03 04:51:14'),
(20, 'ISNM/0020/25', 7, '2024-10-28', '2024-11-08', '2024-10-09', 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(21, 'ISNM/0021/25', 18, '2024-10-19', '2024-10-09', '2024-10-25', 0.00, 0, 'Returned', '2026-07-03 04:51:14'),
(22, 'ISNM/0022/25', 4, '2024-09-23', '2024-10-08', '2024-09-15', 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(23, 'ISNM/0023/25', 2, '2024-10-25', '2024-10-03', '2024-09-19', 37754.00, 1, 'Returned', '2026-07-03 04:51:14'),
(24, 'ISNM/0024/25', 17, '2024-09-14', '2024-10-22', '2024-10-18', 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(25, 'ISNM/0025/25', 12, '2024-10-08', '2024-10-04', '2024-11-08', 0.00, 0, 'Returned', '2026-07-03 04:51:14'),
(26, 'ISNM/0026/25', 1, '2024-09-05', '2024-10-05', '2024-10-02', 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(27, 'ISNM/0027/25', 6, '2024-10-19', '2024-09-30', '2024-10-25', 26520.00, 1, 'Returned', '2026-07-03 04:51:14'),
(28, 'ISNM/0028/25', 6, '2024-10-05', '2024-11-11', NULL, 0.00, 0, 'Borrowed', '2026-07-03 04:51:14'),
(29, 'ISNM/0029/25', 7, '2024-10-23', '2024-10-11', '2024-10-15', 30380.00, 1, 'Borrowed', '2026-07-03 04:51:14'),
(30, 'ISNM/0030/25', 16, '2024-10-20', '2024-10-30', NULL, 0.00, 0, 'Overdue', '2026-07-03 04:51:14'),
(31, 'ISNM/0031/25', 12, '2024-10-28', '2024-09-15', NULL, 2161.00, 0, 'Borrowed', '2026-07-03 04:51:14'),
(32, 'ISNM/0032/25', 12, '2024-09-26', '2024-10-04', '2024-11-01', 8004.00, 0, 'Returned', '2026-07-03 04:51:14'),
(33, 'ISNM/0033/25', 7, '2024-10-18', '2024-09-18', '2024-09-29', 0.00, 0, 'Returned', '2026-07-03 04:51:14'),
(34, 'ISNM/0034/25', 7, '2024-09-26', '2024-09-26', '2024-10-27', 0.00, 1, 'Borrowed', '2026-07-03 04:51:14'),
(35, 'ISNM/0035/25', 14, '2024-09-05', '2024-10-05', '2024-10-11', 9984.00, 1, 'Returned', '2026-07-03 04:51:14'),
(36, 'ISNM/0036/25', 15, '2024-10-29', '2024-10-29', '2024-10-12', 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(37, 'ISNM/0037/25', 15, '2024-09-29', '2024-09-22', NULL, 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(38, 'ISNM/0038/25', 19, '2024-10-03', '2024-11-08', '2024-09-21', 0.00, 1, 'Overdue', '2026-07-03 04:51:14'),
(39, 'ISNM/0039/25', 20, '2024-09-01', '2024-09-20', '2024-10-27', 0.00, 1, 'Borrowed', '2026-07-03 04:51:14'),
(40, 'ISNM/0040/25', 9, '2024-09-13', '2024-11-01', '2024-10-05', 0.00, 0, 'Borrowed', '2026-07-03 04:51:14'),
(41, 'ISNM/0041/24', 7, '2024-10-01', '2024-10-20', '2024-10-16', 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(42, 'ISNM/0042/24', 14, '2024-10-30', '2024-09-16', NULL, 0.00, 1, 'Overdue', '2026-07-03 04:51:14'),
(43, 'ISNM/0043/24', 15, '2024-10-09', '2024-09-15', NULL, 6210.00, 0, 'Overdue', '2026-07-03 04:51:14'),
(44, 'ISNM/0044/24', 1, '2024-09-21', '2024-10-21', '2024-09-24', 25020.00, 0, 'Borrowed', '2026-07-03 04:51:14'),
(45, 'ISNM/0045/24', 15, '2024-10-28', '2024-10-21', NULL, 15663.00, 1, 'Returned', '2026-07-03 04:51:14'),
(46, 'ISNM/0046/24', 17, '2024-09-24', '2024-10-12', NULL, 0.00, 0, 'Returned', '2026-07-03 04:51:14'),
(47, 'ISNM/0047/24', 11, '2024-09-10', '2024-09-30', '2024-09-30', 34733.00, 1, 'Returned', '2026-07-03 04:51:14'),
(48, 'ISNM/0048/24', 18, '2024-10-21', '2024-10-19', '2024-09-26', 15672.00, 0, 'Overdue', '2026-07-03 04:51:14'),
(49, 'ISNM/0049/24', 6, '2024-10-04', '2024-11-11', NULL, 0.00, 0, 'Returned', '2026-07-03 04:51:14'),
(50, 'ISNM/0050/24', 14, '2024-09-13', '2024-09-15', '2024-09-29', 22160.00, 1, 'Borrowed', '2026-07-03 04:51:14'),
(51, 'ISNM/0051/24', 14, '2024-09-12', '2024-11-05', '2024-09-25', 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(52, 'ISNM/0052/24', 10, '2024-10-22', '2024-11-11', NULL, 10653.00, 0, 'Returned', '2026-07-03 04:51:14'),
(53, 'ISNM/0053/24', 8, '2024-09-04', '2024-09-21', '2024-10-22', 49151.00, 1, 'Returned', '2026-07-03 04:51:14'),
(54, 'ISNM/0054/24', 1, '2024-09-05', '2024-10-05', '2024-10-14', 5302.00, 1, 'Returned', '2026-07-03 04:51:14'),
(55, 'ISNM/0055/24', 17, '2024-09-14', '2024-10-22', '2024-10-18', 0.00, 1, 'Borrowed', '2026-07-03 04:51:14'),
(56, 'ISNM/0056/24', 15, '2024-09-14', '2024-11-13', NULL, 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(57, 'ISNM/0057/24', 14, '2024-10-15', '2024-10-25', NULL, 14981.00, 0, 'Overdue', '2026-07-03 04:51:14'),
(58, 'ISNM/0058/24', 19, '2024-09-25', '2024-09-28', '2024-11-08', 1890.00, 1, 'Returned', '2026-07-03 04:51:14'),
(59, 'ISNM/0059/24', 5, '2024-09-18', '2024-11-02', NULL, 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(60, 'ISNM/0060/24', 12, '2024-10-30', '2024-09-26', NULL, 0.00, 0, 'Borrowed', '2026-07-03 04:51:14'),
(61, 'ISNM/0061/24', 12, '2024-10-28', '2024-09-16', NULL, 0.00, 0, 'Overdue', '2026-07-03 04:51:14'),
(62, 'ISNM/0062/24', 14, '2024-09-27', '2024-09-26', '2024-10-14', 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(63, 'ISNM/0063/24', 16, '2024-09-10', '2024-10-16', NULL, 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(64, 'ISNM/0064/24', 11, '2024-09-18', '2024-11-04', '2024-09-23', 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(65, 'ISNM/0065/24', 12, '2024-09-11', '2024-09-23', NULL, 0.00, 0, 'Overdue', '2026-07-03 04:51:14'),
(66, 'ISNM/0066/24', 3, '2024-09-08', '2024-09-29', '2024-10-15', 17001.00, 1, 'Returned', '2026-07-03 04:51:14'),
(67, 'ISNM/0067/24', 6, '2024-10-07', '2024-09-26', NULL, 0.00, 0, 'Returned', '2026-07-03 04:51:14'),
(68, 'ISNM/0068/24', 10, '2024-10-24', '2024-09-15', '2024-11-04', 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(69, 'ISNM/0069/24', 7, '2024-10-11', '2024-10-07', '2024-09-16', 0.00, 0, 'Returned', '2026-07-03 04:51:14'),
(70, 'ISNM/0070/24', 6, '2024-10-28', '2024-11-08', '2024-10-24', 0.00, 0, 'Borrowed', '2026-07-03 04:51:14'),
(71, 'ISNM/0071/24', 5, '2024-09-12', '2024-10-02', '2024-10-21', 0.00, 0, 'Borrowed', '2026-07-03 04:51:14'),
(72, 'ISNM/0072/24', 13, '2024-10-27', '2024-11-03', NULL, 3299.00, 0, 'Returned', '2026-07-03 04:51:14'),
(73, 'ISNM/0073/24', 4, '2024-10-20', '2024-10-26', '2024-10-18', 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(74, 'ISNM/0074/24', 18, '2024-09-01', '2024-10-04', '2024-09-18', 0.00, 0, 'Overdue', '2026-07-03 04:51:14'),
(75, 'ISNM/0075/24', 13, '2024-09-11', '2024-09-21', NULL, 45653.00, 0, 'Overdue', '2026-07-03 04:51:14'),
(76, 'ISNM/0076/24', 7, '2024-10-26', '2024-10-23', '2024-09-25', 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(77, 'ISNM/0077/24', 12, '2024-09-19', '2024-11-06', '2024-10-18', 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(78, 'ISNM/0078/24', 14, '2024-09-19', '2024-10-15', '2024-10-11', 0.00, 1, 'Overdue', '2026-07-03 04:51:14'),
(79, 'ISNM/0079/24', 5, '2024-09-30', '2024-10-25', '2024-10-21', 0.00, 0, 'Returned', '2026-07-03 04:51:14'),
(80, 'ISNM/0080/24', 19, '2024-10-11', '2024-10-22', NULL, 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(81, 'ISNM/0081/24', 9, '2024-10-02', '2024-10-09', '2024-11-07', 0.00, 0, 'Borrowed', '2026-07-03 04:51:14'),
(82, 'ISNM/0082/24', 13, '2024-10-09', '2024-10-07', '2024-10-25', 0.00, 0, 'Returned', '2026-07-03 04:51:14'),
(83, 'ISNM/0083/24', 5, '2024-10-07', '2024-10-11', '2024-10-22', 49951.00, 0, 'Borrowed', '2026-07-03 04:51:14'),
(84, 'ISNM/0084/24', 12, '2024-10-25', '2024-11-05', '2024-10-09', 0.00, 0, 'Returned', '2026-07-03 04:51:14'),
(85, 'ISNM/0085/24', 3, '2024-10-19', '2024-10-29', NULL, 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(86, 'ISNM/0086/24', 7, '2024-10-28', '2024-11-05', '2024-10-20', 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(87, 'ISNM/0087/24', 19, '2024-10-06', '2024-09-20', '2024-10-01', 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(88, 'ISNM/0088/24', 8, '2024-10-07', '2024-11-03', '2024-09-28', 0.00, 1, 'Overdue', '2026-07-03 04:51:14'),
(89, 'ISNM/0089/24', 19, '2024-10-14', '2024-11-09', '2024-09-30', 0.00, 1, 'Borrowed', '2026-07-03 04:51:14'),
(90, 'ISNM/0090/24', 7, '2024-09-23', '2024-11-09', '2024-11-03', 0.00, 0, 'Returned', '2026-07-03 04:51:14'),
(91, 'ISNM/0091/23', 5, '2024-10-01', '2024-11-06', '2024-10-23', 0.00, 0, 'Returned', '2026-07-03 04:51:14'),
(92, 'ISNM/0092/23', 6, '2024-09-07', '2024-10-29', '2024-10-22', 0.00, 0, 'Returned', '2026-07-03 04:51:14'),
(93, 'ISNM/0093/23', 2, '2024-09-12', '2024-10-26', '2024-10-17', 2063.00, 0, 'Returned', '2026-07-03 04:51:14'),
(94, 'ISNM/0094/23', 7, '2024-10-21', '2024-09-29', '2024-11-09', 0.00, 0, 'Returned', '2026-07-03 04:51:14'),
(95, 'ISNM/0095/23', 16, '2024-09-21', '2024-10-13', NULL, 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(96, 'ISNM/0096/23', 10, '2024-09-24', '2024-10-18', '2024-10-14', 0.00, 0, 'Returned', '2026-07-03 04:51:14'),
(97, 'ISNM/0097/23', 2, '2024-09-23', '2024-10-24', NULL, 49840.00, 0, 'Returned', '2026-07-03 04:51:14'),
(98, 'ISNM/0098/23', 20, '2024-10-14', '2024-10-31', '2024-09-26', 29904.00, 0, 'Returned', '2026-07-03 04:51:14'),
(99, 'ISNM/0099/23', 16, '2024-09-01', '2024-10-25', '2024-10-22', 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(100, 'ISNM/0100/23', 7, '2024-09-02', '2024-09-23', '2024-10-27', 0.00, 0, 'Returned', '2026-07-03 04:51:14'),
(101, 'ISNM/0101/23', 7, '2024-10-16', '2024-10-31', '2024-10-25', 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(102, 'ISNM/0102/23', 17, '2024-10-17', '2024-10-05', '2024-11-05', 0.00, 0, 'Returned', '2026-07-03 04:51:14'),
(103, 'ISNM/0103/23', 20, '2024-09-18', '2024-10-19', '2024-09-28', 0.00, 0, 'Borrowed', '2026-07-03 04:51:14'),
(104, 'ISNM/0104/23', 16, '2024-10-05', '2024-10-13', '2024-09-15', 42644.00, 0, 'Overdue', '2026-07-03 04:51:14'),
(105, 'ISNM/0105/23', 13, '2024-09-27', '2024-09-28', '2024-10-17', 0.00, 0, 'Returned', '2026-07-03 04:51:14'),
(106, 'ISNM/0106/23', 15, '2024-10-12', '2024-09-28', NULL, 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(107, 'ISNM/0107/23', 3, '2024-10-02', '2024-09-27', '2024-10-18', 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(108, 'ISNM/0108/23', 5, '2024-10-09', '2024-10-15', '2024-09-30', 0.00, 0, 'Borrowed', '2026-07-03 04:51:14'),
(109, 'ISNM/0109/23', 16, '2024-10-05', '2024-10-15', '2024-11-02', 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(110, 'ISNM/0110/23', 18, '2024-09-20', '2024-09-20', '2024-09-20', 0.00, 1, 'Overdue', '2026-07-03 04:51:14'),
(111, 'ISNM/0111/23', 20, '2024-10-04', '2024-11-08', '2024-10-21', 0.00, 0, 'Returned', '2026-07-03 04:51:14'),
(112, 'ISNM/0112/23', 3, '2024-09-14', '2024-11-05', '2024-10-01', 0.00, 0, 'Returned', '2026-07-03 04:51:14'),
(113, 'ISNM/0113/23', 16, '2024-10-02', '2024-10-02', '2024-10-27', 42292.00, 1, 'Returned', '2026-07-03 04:51:14'),
(114, 'ISNM/0114/23', 16, '2024-09-01', '2024-10-22', NULL, 10486.00, 1, 'Returned', '2026-07-03 04:51:14'),
(115, 'ISNM/0115/23', 18, '2024-10-02', '2024-09-19', '2024-10-31', 0.00, 1, 'Borrowed', '2026-07-03 04:51:14'),
(116, 'ISNM/0116/23', 3, '2024-09-30', '2024-09-21', NULL, 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(117, 'ISNM/0117/23', 4, '2024-10-14', '2024-09-22', '2024-11-09', 0.00, 0, 'Returned', '2026-07-03 04:51:14'),
(118, 'ISNM/0118/23', 9, '2024-10-03', '2024-10-11', '2024-10-13', 6480.00, 1, 'Borrowed', '2026-07-03 04:51:14'),
(119, 'ISNM/0119/23', 20, '2024-10-25', '2024-10-23', '2024-10-20', 0.00, 0, 'Overdue', '2026-07-03 04:51:14'),
(120, 'ISNM/0120/23', 11, '2024-09-22', '2024-09-30', NULL, 0.00, 1, 'Overdue', '2026-07-03 04:51:14'),
(121, 'ISNM/0121/23', 17, '2024-09-22', '2024-10-03', '2024-10-01', 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(122, 'ISNM/0122/23', 1, '2024-09-30', '2024-10-10', '2024-10-28', 3372.00, 1, 'Returned', '2026-07-03 04:51:14'),
(123, 'ISNM/0123/23', 7, '2024-09-23', '2024-11-07', '2024-09-26', 22043.00, 1, 'Returned', '2026-07-03 04:51:14'),
(124, 'ISNM/0124/23', 4, '2024-10-10', '2024-11-03', NULL, 0.00, 1, 'Overdue', '2026-07-03 04:51:14'),
(125, 'ISNM/0125/23', 7, '2024-10-20', '2024-09-25', '2024-10-06', 0.00, 0, 'Returned', '2026-07-03 04:51:14'),
(126, 'ISNM/0126/23', 10, '2024-09-08', '2024-09-21', NULL, 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(127, 'ISNM/0127/23', 20, '2024-10-30', '2024-09-18', '2024-10-24', 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(128, 'ISNM/0128/23', 7, '2024-10-25', '2024-10-22', '2024-09-22', 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(129, 'ISNM/0129/23', 12, '2024-09-20', '2024-09-15', NULL, 0.00, 0, 'Returned', '2026-07-03 04:51:14'),
(130, 'ISNM/0130/23', 12, '2024-09-09', '2024-11-10', NULL, 0.00, 0, 'Returned', '2026-07-03 04:51:14'),
(131, 'ISNM/0131/23', 10, '2024-09-18', '2024-09-19', '2024-09-30', 9311.00, 1, 'Borrowed', '2026-07-03 04:51:14'),
(132, 'ISNM/0132/23', 16, '2024-09-28', '2024-11-10', '2024-10-30', 41121.00, 1, 'Returned', '2026-07-03 04:51:14'),
(133, 'ISNM/0133/23', 6, '2024-10-21', '2024-10-07', '2024-10-26', 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(134, 'ISNM/0134/23', 2, '2024-10-12', '2024-09-28', NULL, 0.00, 0, 'Returned', '2026-07-03 04:51:14'),
(135, 'ISNM/0135/23', 16, '2024-10-22', '2024-09-17', '2024-11-01', 0.00, 1, 'Borrowed', '2026-07-03 04:51:14'),
(136, 'ISNM/0136/23', 6, '2024-09-27', '2024-10-05', '2024-11-05', 0.00, 0, 'Borrowed', '2026-07-03 04:51:14'),
(137, 'ISNM/0137/23', 3, '2024-10-30', '2024-10-22', NULL, 36401.00, 0, 'Returned', '2026-07-03 04:51:14'),
(138, 'ISNM/0138/23', 8, '2024-09-11', '2024-10-31', '2024-10-01', 0.00, 0, 'Returned', '2026-07-03 04:51:14'),
(139, 'ISNM/0139/23', 11, '2024-09-27', '2024-10-20', '2024-10-05', 1440.00, 1, 'Returned', '2026-07-03 04:51:14'),
(140, 'ISNM/0140/23', 10, '2024-10-20', '2024-10-24', '2024-09-17', 47909.00, 0, 'Returned', '2026-07-03 04:51:14'),
(141, 'ISNM/0141/23', 2, '2024-10-22', '2024-09-23', NULL, 0.00, 1, 'Borrowed', '2026-07-03 04:51:14'),
(142, 'ISNM/0142/23', 9, '2024-10-20', '2024-11-06', '2024-11-06', 0.00, 1, 'Borrowed', '2026-07-03 04:51:14'),
(143, 'ISNM/0143/23', 5, '2024-09-04', '2024-10-21', '2024-10-19', 0.00, 0, 'Returned', '2026-07-03 04:51:14'),
(144, 'ISNM/0144/23', 7, '2024-10-19', '2024-09-15', '2024-11-09', 34942.00, 1, 'Returned', '2026-07-03 04:51:14'),
(145, 'ISNM/0145/23', 1, '2024-10-03', '2024-10-21', '2024-09-24', 0.00, 0, 'Returned', '2026-07-03 04:51:14'),
(146, 'ISNM/0146/23', 11, '2024-09-30', '2024-11-09', NULL, 0.00, 0, 'Borrowed', '2026-07-03 04:51:14'),
(147, 'ISNM/0147/23', 10, '2024-09-01', '2024-10-19', '2024-10-18', 0.00, 0, 'Returned', '2026-07-03 04:51:14'),
(148, 'ISNM/0148/23', 20, '2024-10-17', '2024-09-15', '2024-10-18', 0.00, 1, 'Returned', '2026-07-03 04:51:14'),
(149, 'ISNM/0149/23', 12, '2024-09-15', '2024-10-18', NULL, 0.00, 1, 'Borrowed', '2026-07-03 04:51:14'),
(150, 'ISNM/0150/23', 17, '2024-10-08', '2024-10-30', '2024-09-21', 15153.00, 1, 'Returned', '2026-07-03 04:51:14');

-- --------------------------------------------------------

--
-- Table structure for table `library_fines`
--

CREATE TABLE `library_fines` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `book_title` varchar(200) DEFAULT NULL,
  `borrow_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `fine_amount` decimal(10,2) DEFAULT 0.00,
  `paid` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `marks`
--

CREATE TABLE `marks` (
  `s_no` int(11) NOT NULL,
  `exam_id` varchar(50) NOT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `marks` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicine_stock`
--

CREATE TABLE `medicine_stock` (
  `id` int(11) NOT NULL,
  `medicine_code` varchar(50) NOT NULL,
  `medicine_name` varchar(255) NOT NULL,
  `generic_name` varchar(255) DEFAULT NULL,
  `category` enum('Antibiotic','Painkiller','Anti-inflammatory','Antimalarial','Antiviral','Antifungal','Vitamins','First Aid','Allergy','Digestive','Respiratory','Dermatological','Ophthalmic','Other') DEFAULT 'Other',
  `dosage_form` enum('Tablet','Capsule','Syrup','Injection','Cream','Ointment','Drops','Inhaler','Suppository','Powder','Solution','Other') DEFAULT 'Tablet',
  `strength` varchar(100) DEFAULT NULL,
  `manufacturer` varchar(200) DEFAULT NULL,
  `supplier` varchar(200) DEFAULT NULL,
  `quantity_in_stock` int(11) NOT NULL DEFAULT 0,
  `unit` varchar(50) NOT NULL DEFAULT 'pcs',
  `reorder_level` int(11) DEFAULT 10,
  `unit_cost` decimal(15,2) DEFAULT NULL,
  `selling_price` decimal(15,2) DEFAULT NULL,
  `currency` varchar(10) DEFAULT 'UGX',
  `batch_number` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `storage_location` varchar(100) DEFAULT NULL,
  `requires_prescription` tinyint(1) DEFAULT 0,
  `instructions` text DEFAULT NULL,
  `side_effects` text DEFAULT NULL,
  `status` enum('In Stock','Low Stock','Out of Stock','Expired','Discontinued') DEFAULT 'In Stock',
  `last_restocked` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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

CREATE TABLE `medicine_stock_transactions` (
  `id` int(11) NOT NULL,
  `transaction_number` varchar(50) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `transaction_type` enum('Purchase','Issue','Return','Adjustment','Damage','Expired') NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_cost` decimal(15,2) DEFAULT NULL,
  `total_cost` decimal(15,2) DEFAULT NULL,
  `currency` varchar(10) DEFAULT 'UGX',
  `reference` varchar(200) DEFAULT NULL,
  `issued_to` varchar(200) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `meetings`
--

CREATE TABLE `meetings` (
  `id` int(11) NOT NULL,
  `title` varchar(300) DEFAULT NULL,
  `meeting_type` varchar(100) DEFAULT NULL,
  `meeting_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `agenda` text DEFAULT NULL,
  `minutes` text DEFAULT NULL,
  `status` enum('scheduled','ongoing','completed','cancelled') DEFAULT 'scheduled',
  `created_by` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `meeting_actions`
--

CREATE TABLE `meeting_actions` (
  `id` int(11) NOT NULL,
  `meeting_id` int(11) DEFAULT NULL,
  `action_item` text DEFAULT NULL,
  `assigned_to` varchar(200) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('pending','in_progress','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `meeting_attendees`
--

CREATE TABLE `meeting_attendees` (
  `id` int(11) NOT NULL,
  `meeting_id` int(11) NOT NULL,
  `attendee_name` varchar(200) DEFAULT NULL,
  `attendee_role` varchar(100) DEFAULT NULL,
  `attended` enum('pending','present','absent') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `meeting_minutes`
--

CREATE TABLE `meeting_minutes` (
  `id` int(11) NOT NULL,
  `meeting_id` int(11) DEFAULT NULL,
  `agenda_item` varchar(300) DEFAULT NULL,
  `discussion` text DEFAULT NULL,
  `resolution` text DEFAULT NULL,
  `action_items` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) DEFAULT NULL,
  `subject` varchar(500) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `priority` varchar(20) DEFAULT 'normal',
  `is_read` tinyint(1) DEFAULT 0,
  `is_archived` tinyint(1) DEFAULT 0,
  `parent_id` int(11) DEFAULT NULL,
  `has_attachment` tinyint(1) DEFAULT 0,
  `attachment_path` varchar(500) DEFAULT NULL,
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `module_permissions`
--

CREATE TABLE `module_permissions` (
  `id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `can_view` tinyint(1) DEFAULT 1,
  `can_create` tinyint(1) DEFAULT 0,
  `can_edit` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `news`
--

CREATE TABLE `news` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `excerpt` text DEFAULT NULL,
  `featured_image` varchar(500) DEFAULT NULL,
  `author_id` int(11) DEFAULT NULL,
  `author_name` varchar(255) DEFAULT NULL,
  `author_role` varchar(255) DEFAULT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notes`
--

CREATE TABLE `notes` (
  `s_no` int(11) NOT NULL,
  `sender_id` varchar(50) DEFAULT NULL,
  `editor_id` varchar(50) DEFAULT NULL,
  `class` varchar(50) DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `comment` text DEFAULT NULL,
  `file` varchar(500) DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notice`
--

CREATE TABLE `notice` (
  `s_no` int(11) NOT NULL,
  `sender_id` varchar(50) DEFAULT NULL,
  `editor_id` varchar(50) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `body` text DEFAULT NULL,
  `importance` varchar(50) DEFAULT NULL,
  `file` varchar(500) DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `notification_type` enum('fee_reminder','payment_received','invoice_generated','budget_alert','system') DEFAULT 'system',
  `recipient_type` enum('student','staff','bursar') NOT NULL,
  `recipient_id` int(11) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `channel` enum('email','sms','in_app') DEFAULT 'in_app',
  `is_read` tinyint(1) DEFAULT 0,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_logs`
--

CREATE TABLE `notification_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `type` varchar(50) DEFAULT 'info',
  `icon` varchar(100) DEFAULT NULL,
  `link` varchar(500) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `official_letters`
--

CREATE TABLE `official_letters` (
  `id` int(11) NOT NULL,
  `letter_type` varchar(100) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `title` varchar(300) DEFAULT NULL,
  `recipient_name` varchar(200) DEFAULT NULL,
  `recipient_address` text DEFAULT NULL,
  `body` text DEFAULT NULL,
  `letter_date` date DEFAULT NULL,
  `issued_by` varchar(200) DEFAULT NULL,
  `file_name` varchar(300) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `status` enum('draft','issued','signed','archived') DEFAULT 'draft',
  `created_by` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `payment_reference` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `amount_received` decimal(12,2) NOT NULL,
  `payment_method` enum('Cash','Bank Transfer','Mobile Money','Cheque','Card','Other') DEFAULT 'Cash',
  `payment_date` date DEFAULT curdate(),
  `transaction_ref` varchar(100) DEFAULT NULL,
  `slip_number` varchar(100) DEFAULT NULL,
  `status` enum('Pending','Completed','Failed','Reversed') DEFAULT 'Completed',
  `received_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `payment_reference`, `student_id`, `invoice_id`, `amount_received`, `payment_method`, `payment_date`, `transaction_ref`, `slip_number`, `status`, `received_by`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'PAY000000-01', 0, NULL, 112982.00, 'Cheque', '2024-09-19', 'TXN185821', 'SLIP452361', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(2, 'PAY000000-01', 0, NULL, 452173.00, 'Cash', '2024-09-12', 'TXN57050', 'SLIP154655', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(3, 'PAY000000-01', 0, NULL, 401064.00, 'Bank Transfer', '2024-09-25', 'TXN994875', 'SLIP193444', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(4, 'PAY000000-01', 0, NULL, 591296.00, 'Mobile Money', '2024-09-12', 'TXN579063', 'SLIP749140', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(5, 'PAY000000-01', 0, NULL, 104255.00, 'Cheque', '2024-09-27', 'TXN365248', 'SLIP975855', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(6, 'PAY000000-01', 0, NULL, 491767.00, 'Cheque', '2024-09-05', 'TXN29359', 'SLIP346983', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(7, 'PAY000000-01', 0, NULL, 423422.00, 'Cash', '2024-08-02', 'TXN549244', 'SLIP668795', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(8, 'PAY000000-01', 0, NULL, 448121.00, 'Mobile Money', '2024-08-18', 'TXN2624', 'SLIP156850', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(9, 'PAY000000-01', 0, NULL, 488190.00, 'Mobile Money', '2024-09-13', 'TXN403976', 'SLIP837069', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(10, 'PAY000000-01', 0, NULL, 586709.00, 'Mobile Money', '2024-09-21', 'TXN228228', 'SLIP563608', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(11, 'PAY000000-01', 0, NULL, 166678.00, 'Cheque', '2024-08-29', 'TXN470825', 'SLIP914898', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(12, 'PAY000000-01', 0, NULL, 181010.00, 'Cash', '2024-09-20', 'TXN8672', 'SLIP520420', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(13, 'PAY000000-01', 0, NULL, 388045.00, 'Mobile Money', '2024-09-22', 'TXN380779', 'SLIP300892', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(14, 'PAY000000-01', 0, NULL, 281062.00, 'Cheque', '2024-08-28', 'TXN542949', 'SLIP354677', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(15, 'PAY000000-01', 0, NULL, 172268.00, 'Bank Transfer', '2024-09-21', 'TXN322303', 'SLIP32560', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(16, 'PAY000000-01', 0, NULL, 197947.00, 'Cheque', '2024-09-19', 'TXN460909', 'SLIP840787', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(17, 'PAY000000-01', 0, NULL, 510605.00, 'Bank Transfer', '2024-08-28', 'TXN523054', 'SLIP250796', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(18, 'PAY000000-01', 0, NULL, 442409.00, 'Bank Transfer', '2024-08-19', 'TXN505275', 'SLIP614138', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(19, 'PAY000000-01', 0, NULL, 377433.00, 'Cheque', '2024-09-29', 'TXN179283', 'SLIP911385', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(20, 'PAY000000-01', 0, NULL, 109540.00, 'Mobile Money', '2024-09-14', 'TXN661256', 'SLIP59283', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(21, 'PAY000000-01', 0, NULL, 256323.00, 'Mobile Money', '2024-09-29', 'TXN788703', 'SLIP976610', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(22, 'PAY000000-01', 0, NULL, 358473.00, 'Bank Transfer', '2024-09-13', 'TXN653532', 'SLIP96729', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(23, 'PAY000000-01', 0, NULL, 361524.00, 'Mobile Money', '2024-08-04', 'TXN305577', 'SLIP359433', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(24, 'PAY000000-01', 0, NULL, 540217.00, 'Mobile Money', '2024-09-28', 'TXN918631', 'SLIP659016', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(25, 'PAY000000-01', 0, NULL, 369593.00, 'Bank Transfer', '2024-09-28', 'TXN727611', 'SLIP707505', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(26, 'PAY000000-01', 0, NULL, 277346.00, 'Bank Transfer', '2024-08-12', 'TXN437', 'SLIP430218', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(27, 'PAY000000-01', 0, NULL, 174892.00, 'Mobile Money', '2024-09-20', 'TXN835109', 'SLIP649601', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(28, 'PAY000000-01', 0, NULL, 471340.00, 'Cheque', '2024-09-05', 'TXN680892', 'SLIP619658', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(29, 'PAY000000-01', 0, NULL, 127806.00, 'Mobile Money', '2024-09-25', 'TXN385837', 'SLIP143321', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(30, 'PAY000000-01', 0, NULL, 379546.00, 'Mobile Money', '2024-08-10', 'TXN654597', 'SLIP822333', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(31, 'PAY000000-01', 0, NULL, 173937.00, 'Mobile Money', '2024-09-25', 'TXN774007', 'SLIP115371', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(32, 'PAY000000-01', 0, NULL, 227416.00, 'Cheque', '2024-09-22', 'TXN594636', 'SLIP345909', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(33, 'PAY000000-01', 0, NULL, 572820.00, 'Bank Transfer', '2024-09-06', 'TXN5759', 'SLIP182494', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(34, 'PAY000000-01', 0, NULL, 547597.00, 'Cheque', '2024-09-27', 'TXN998943', 'SLIP124064', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(35, 'PAY000000-01', 0, NULL, 411745.00, 'Bank Transfer', '2024-09-21', 'TXN43402', 'SLIP649496', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(36, 'PAY000000-01', 0, NULL, 158640.00, 'Bank Transfer', '2024-09-20', 'TXN274889', 'SLIP861266', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(37, 'PAY000000-01', 0, NULL, 340835.00, 'Cheque', '2024-09-10', 'TXN915087', 'SLIP542193', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(38, 'PAY000000-01', 0, NULL, 582853.00, 'Cash', '2024-08-07', 'TXN957306', 'SLIP448642', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(39, 'PAY000000-01', 0, NULL, 285645.00, 'Bank Transfer', '2024-08-27', 'TXN662160', 'SLIP994565', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(40, 'PAY000000-01', 0, NULL, 593175.00, 'Cheque', '2024-09-16', 'TXN61976', 'SLIP966195', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(41, 'PAY000000-01', 0, NULL, 422525.00, 'Mobile Money', '2024-09-11', 'TXN510853', 'SLIP459760', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(42, 'PAY000000-01', 0, NULL, 483120.00, 'Mobile Money', '2024-09-27', 'TXN448747', 'SLIP361025', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(43, 'PAY000000-01', 0, NULL, 329442.00, 'Cash', '2024-09-10', 'TXN766386', 'SLIP791675', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(44, 'PAY000000-01', 0, NULL, 429609.00, 'Cheque', '2024-09-07', 'TXN375161', 'SLIP992792', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(45, 'PAY000000-01', 0, NULL, 519241.00, 'Cash', '2024-09-03', 'TXN131486', 'SLIP993281', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(46, 'PAY000000-01', 0, NULL, 385976.00, 'Cheque', '2024-09-11', 'TXN778843', 'SLIP843068', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(47, 'PAY000000-01', 0, NULL, 539406.00, 'Cheque', '2024-09-11', 'TXN844710', 'SLIP159982', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(48, 'PAY000000-01', 0, NULL, 232890.00, 'Cheque', '2024-08-27', 'TXN690312', 'SLIP109257', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(49, 'PAY000000-01', 0, NULL, 337675.00, 'Cash', '2024-09-19', 'TXN947247', 'SLIP279733', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(50, 'PAY000000-01', 0, NULL, 378461.00, 'Cheque', '2024-08-04', 'TXN445233', 'SLIP57287', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(51, 'PAY000000-01', 0, NULL, 575368.00, 'Bank Transfer', '2024-08-04', 'TXN539039', 'SLIP524496', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(52, 'PAY000000-01', 0, NULL, 102681.00, 'Mobile Money', '2024-08-16', 'TXN892636', 'SLIP711627', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(53, 'PAY000000-01', 0, NULL, 540113.00, 'Mobile Money', '2024-09-11', 'TXN654040', 'SLIP198536', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(54, 'PAY000000-01', 0, NULL, 115279.00, 'Bank Transfer', '2024-09-11', 'TXN799708', 'SLIP915784', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(55, 'PAY000000-01', 0, NULL, 189900.00, 'Cash', '2024-08-14', 'TXN639185', 'SLIP539456', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(56, 'PAY000000-01', 0, NULL, 489863.00, 'Mobile Money', '2024-08-04', 'TXN469964', 'SLIP163364', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(57, 'PAY000000-01', 0, NULL, 303464.00, 'Bank Transfer', '2024-08-31', 'TXN876105', 'SLIP874684', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(58, 'PAY000000-01', 0, NULL, 472552.00, 'Cash', '2024-08-17', 'TXN55764', 'SLIP462719', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(59, 'PAY000000-01', 0, NULL, 173152.00, 'Mobile Money', '2024-08-17', 'TXN359489', 'SLIP963694', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(60, 'PAY000000-01', 0, NULL, 470004.00, 'Cheque', '2024-09-19', 'TXN696938', 'SLIP10409', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(61, 'PAY000000-01', 0, NULL, 580614.00, 'Cheque', '2024-09-29', 'TXN629730', 'SLIP175967', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(62, 'PAY000000-01', 0, NULL, 595321.00, 'Mobile Money', '2024-08-10', 'TXN497263', 'SLIP22395', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(63, 'PAY000000-01', 0, NULL, 410093.00, 'Cash', '2024-08-19', 'TXN439689', 'SLIP273883', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(64, 'PAY000000-01', 0, NULL, 125174.00, 'Mobile Money', '2024-09-29', 'TXN706901', 'SLIP536192', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(65, 'PAY000000-01', 0, NULL, 380129.00, 'Cash', '2024-08-17', 'TXN835804', 'SLIP330661', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(66, 'PAY000000-01', 0, NULL, 172947.00, 'Bank Transfer', '2024-08-15', 'TXN36256', 'SLIP432056', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(67, 'PAY000000-01', 0, NULL, 125757.00, 'Cheque', '2024-09-09', 'TXN378085', 'SLIP933055', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(68, 'PAY000000-01', 0, NULL, 365513.00, 'Cheque', '2024-09-11', 'TXN865787', 'SLIP268733', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(69, 'PAY000000-01', 0, NULL, 473152.00, 'Cheque', '2024-08-24', 'TXN162490', 'SLIP649382', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(70, 'PAY000000-01', 0, NULL, 479721.00, 'Cheque', '2024-09-28', 'TXN287759', 'SLIP537826', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(71, 'PAY000000-01', 0, NULL, 512929.00, 'Bank Transfer', '2024-08-07', 'TXN959992', 'SLIP495507', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(72, 'PAY000000-01', 0, NULL, 398780.00, 'Bank Transfer', '2024-09-12', 'TXN64784', 'SLIP182745', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(73, 'PAY000000-01', 0, NULL, 459686.00, 'Cash', '2024-08-06', 'TXN279239', 'SLIP141120', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(74, 'PAY000000-01', 0, NULL, 533943.00, 'Cheque', '2024-09-28', 'TXN135305', 'SLIP746412', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(75, 'PAY000000-01', 0, NULL, 263075.00, 'Mobile Money', '2024-09-28', 'TXN721026', 'SLIP667795', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(76, 'PAY000000-01', 0, NULL, 187949.00, 'Cheque', '2024-09-21', 'TXN635814', 'SLIP620597', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(77, 'PAY000000-01', 0, NULL, 197772.00, 'Cash', '2024-09-29', 'TXN617265', 'SLIP107300', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(78, 'PAY000000-01', 0, NULL, 442350.00, 'Cash', '2024-08-28', 'TXN964868', 'SLIP462530', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(79, 'PAY000000-01', 0, NULL, 309023.00, 'Bank Transfer', '2024-08-16', 'TXN187408', 'SLIP159845', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(80, 'PAY000000-01', 0, NULL, 218499.00, 'Bank Transfer', '2024-09-18', 'TXN965094', 'SLIP376594', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(81, 'PAY000000-01', 0, NULL, 593844.00, 'Cheque', '2024-08-05', 'TXN975160', 'SLIP635122', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(82, 'PAY000000-01', 0, NULL, 225066.00, 'Mobile Money', '2024-09-28', 'TXN844484', 'SLIP294204', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(83, 'PAY000000-01', 0, NULL, 568783.00, 'Cheque', '2024-08-13', 'TXN651365', 'SLIP616612', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(84, 'PAY000000-01', 0, NULL, 164482.00, 'Cheque', '2024-09-05', 'TXN555304', 'SLIP12358', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(85, 'PAY000000-01', 0, NULL, 297939.00, 'Cheque', '2024-09-01', 'TXN792848', 'SLIP392359', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(86, 'PAY000000-01', 0, NULL, 391626.00, 'Bank Transfer', '2024-09-26', 'TXN513271', 'SLIP727862', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(87, 'PAY000000-01', 0, NULL, 149749.00, 'Mobile Money', '2024-08-17', 'TXN413473', 'SLIP254253', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(88, 'PAY000000-01', 0, NULL, 115423.00, 'Mobile Money', '2024-09-21', 'TXN149713', 'SLIP154089', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(89, 'PAY000000-01', 0, NULL, 260652.00, 'Cash', '2024-09-15', 'TXN354122', 'SLIP498471', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(90, 'PAY000000-01', 0, NULL, 314996.00, 'Bank Transfer', '2024-09-28', 'TXN950128', 'SLIP802384', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(91, 'PAY000000-01', 0, NULL, 180769.00, 'Mobile Money', '2024-09-01', 'TXN388810', 'SLIP389795', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(92, 'PAY000000-01', 0, NULL, 491273.00, 'Bank Transfer', '2024-08-23', 'TXN615403', 'SLIP969754', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(93, 'PAY000000-01', 0, NULL, 101281.00, 'Cash', '2024-08-31', 'TXN239723', 'SLIP668388', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(94, 'PAY000000-01', 0, NULL, 411387.00, 'Cash', '2024-09-10', 'TXN49932', 'SLIP224031', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(95, 'PAY000000-01', 0, NULL, 585180.00, 'Cash', '2024-09-29', 'TXN398114', 'SLIP28238', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(96, 'PAY000000-01', 0, NULL, 573422.00, 'Bank Transfer', '2024-08-25', 'TXN86553', 'SLIP211716', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(97, 'PAY000000-01', 0, NULL, 499462.00, 'Mobile Money', '2024-08-25', 'TXN924480', 'SLIP420672', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(98, 'PAY000000-01', 0, NULL, 264959.00, 'Mobile Money', '2024-09-26', 'TXN577947', 'SLIP45327', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(99, 'PAY000000-01', 0, NULL, 346398.00, 'Mobile Money', '2024-08-10', 'TXN823995', 'SLIP635188', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(100, 'PAY000000-01', 0, NULL, 451978.00, 'Bank Transfer', '2024-09-27', 'TXN953355', 'SLIP889182', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(101, 'PAY000000-01', 0, NULL, 392924.00, 'Mobile Money', '2024-09-03', 'TXN969597', 'SLIP195177', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(102, 'PAY000000-01', 0, NULL, 133546.00, 'Bank Transfer', '2024-09-02', 'TXN492162', 'SLIP815635', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(103, 'PAY000000-01', 0, NULL, 400844.00, 'Bank Transfer', '2024-08-01', 'TXN328552', 'SLIP634856', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(104, 'PAY000000-01', 0, NULL, 194314.00, 'Cash', '2024-09-07', 'TXN19169', 'SLIP214915', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(105, 'PAY000000-01', 0, NULL, 108534.00, 'Mobile Money', '2024-08-10', 'TXN437172', 'SLIP730477', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(106, 'PAY000000-01', 0, NULL, 270437.00, 'Bank Transfer', '2024-09-02', 'TXN171501', 'SLIP231320', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(107, 'PAY000000-01', 0, NULL, 421050.00, 'Bank Transfer', '2024-09-09', 'TXN732327', 'SLIP692476', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(108, 'PAY000000-01', 0, NULL, 232700.00, 'Cash', '2024-08-28', 'TXN509594', 'SLIP192985', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(109, 'PAY000000-01', 0, NULL, 318071.00, 'Bank Transfer', '2024-09-12', 'TXN696495', 'SLIP381423', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(110, 'PAY000000-01', 0, NULL, 508815.00, 'Cheque', '2024-08-16', 'TXN500957', 'SLIP705223', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(111, 'PAY000000-01', 0, NULL, 111624.00, 'Cash', '2024-09-25', 'TXN663854', 'SLIP519932', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(112, 'PAY000000-01', 0, NULL, 404050.00, 'Mobile Money', '2024-09-04', 'TXN453938', 'SLIP532066', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(113, 'PAY000000-01', 0, NULL, 249258.00, 'Cheque', '2024-09-05', 'TXN242755', 'SLIP454628', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(114, 'PAY000000-01', 0, NULL, 372438.00, 'Mobile Money', '2024-08-11', 'TXN757753', 'SLIP285226', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(115, 'PAY000000-01', 0, NULL, 176435.00, 'Cheque', '2024-08-06', 'TXN697861', 'SLIP234972', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(116, 'PAY000000-01', 0, NULL, 140637.00, 'Bank Transfer', '2024-08-16', 'TXN212994', 'SLIP274547', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(117, 'PAY000000-01', 0, NULL, 466878.00, 'Cheque', '2024-08-02', 'TXN586676', 'SLIP860129', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(118, 'PAY000000-01', 0, NULL, 370310.00, 'Cash', '2024-09-29', 'TXN590429', 'SLIP977002', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(119, 'PAY000000-01', 0, NULL, 156863.00, 'Bank Transfer', '2024-09-20', 'TXN321851', 'SLIP68424', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(120, 'PAY000000-01', 0, NULL, 288281.00, 'Bank Transfer', '2024-08-16', 'TXN257578', 'SLIP513761', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(121, 'PAY000000-01', 0, NULL, 498035.00, 'Mobile Money', '2024-09-18', 'TXN718563', 'SLIP171348', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(122, 'PAY000000-01', 0, NULL, 450525.00, 'Cheque', '2024-09-21', 'TXN290773', 'SLIP895231', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(123, 'PAY000000-01', 0, NULL, 401920.00, 'Mobile Money', '2024-09-21', 'TXN279560', 'SLIP829752', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(124, 'PAY000000-01', 0, NULL, 255041.00, 'Cash', '2024-08-23', 'TXN694181', 'SLIP344320', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(125, 'PAY000000-01', 0, NULL, 419529.00, 'Cash', '2024-09-23', 'TXN985444', 'SLIP243760', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(126, 'PAY000000-01', 0, NULL, 231232.00, 'Bank Transfer', '2024-08-08', 'TXN846018', 'SLIP876600', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(127, 'PAY000000-01', 0, NULL, 522475.00, 'Bank Transfer', '2024-08-27', 'TXN414667', 'SLIP753624', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(128, 'PAY000000-01', 0, NULL, 362061.00, 'Mobile Money', '2024-08-14', 'TXN52339', 'SLIP582772', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(129, 'PAY000000-01', 0, NULL, 478423.00, 'Cash', '2024-09-24', 'TXN437346', 'SLIP459687', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(130, 'PAY000000-01', 0, NULL, 593198.00, 'Bank Transfer', '2024-09-18', 'TXN368287', 'SLIP425211', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(131, 'PAY000000-01', 0, NULL, 110597.00, 'Cheque', '2024-08-06', 'TXN949479', 'SLIP483110', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(132, 'PAY000000-01', 0, NULL, 383555.00, 'Mobile Money', '2024-08-14', 'TXN990191', 'SLIP261651', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(133, 'PAY000000-01', 0, NULL, 268839.00, 'Cheque', '2024-08-31', 'TXN810567', 'SLIP540302', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(134, 'PAY000000-01', 0, NULL, 234905.00, 'Bank Transfer', '2024-09-19', 'TXN971990', 'SLIP366103', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(135, 'PAY000000-01', 0, NULL, 557271.00, 'Mobile Money', '2024-09-07', 'TXN718827', 'SLIP708906', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(136, 'PAY000000-01', 0, NULL, 294026.00, 'Cheque', '2024-09-24', 'TXN77225', 'SLIP675408', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(137, 'PAY000000-01', 0, NULL, 172685.00, 'Bank Transfer', '2024-08-05', 'TXN233151', 'SLIP964746', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(138, 'PAY000000-01', 0, NULL, 162140.00, 'Bank Transfer', '2024-08-16', 'TXN133294', 'SLIP877607', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(139, 'PAY000000-01', 0, NULL, 594080.00, 'Mobile Money', '2024-09-04', 'TXN953140', 'SLIP39460', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(140, 'PAY000000-01', 0, NULL, 268938.00, 'Bank Transfer', '2024-09-20', 'TXN493885', 'SLIP945292', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(141, 'PAY000000-01', 0, NULL, 222405.00, 'Mobile Money', '2024-08-13', 'TXN867701', 'SLIP719174', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(142, 'PAY000000-01', 0, NULL, 596383.00, 'Cheque', '2024-08-04', 'TXN847393', 'SLIP77170', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(143, 'PAY000000-01', 0, NULL, 521835.00, 'Cheque', '2024-08-25', 'TXN55381', 'SLIP67372', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(144, 'PAY000000-01', 0, NULL, 185358.00, 'Bank Transfer', '2024-09-14', 'TXN771488', 'SLIP621914', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(145, 'PAY000000-01', 0, NULL, 497554.00, 'Cash', '2024-08-10', 'TXN488958', 'SLIP953777', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(146, 'PAY000000-01', 0, NULL, 251008.00, 'Bank Transfer', '2024-08-21', 'TXN742253', 'SLIP698166', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(147, 'PAY000000-01', 0, NULL, 232036.00, 'Cash', '2024-08-21', 'TXN7868', 'SLIP28071', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(148, 'PAY000000-01', 0, NULL, 158376.00, 'Mobile Money', '2024-08-09', 'TXN238790', 'SLIP751489', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(149, 'PAY000000-01', 0, NULL, 120539.00, 'Cheque', '2024-09-07', 'TXN304142', 'SLIP626565', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(150, 'PAY000000-01', 0, NULL, 210201.00, 'Cash', '2024-08-28', 'TXN584840', 'SLIP573138', 'Completed', 25, 'Tuition Fee Payment', '2026-07-03 04:51:14', '2026-07-14 16:52:52');

-- --------------------------------------------------------

--
-- Table structure for table `payment_approvals`
--

CREATE TABLE `payment_approvals` (
  `id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `payment_type` varchar(50) DEFAULT 'fee_payment',
  `requested_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approval_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approval_remarks` text DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_audit_log`
--

CREATE TABLE `payment_audit_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_type` enum('staff','student','system') DEFAULT 'staff',
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `old_values` longtext DEFAULT NULL,
  `new_values` longtext DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_callbacks`
--

CREATE TABLE `payment_callbacks` (
  `id` int(11) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `provider_key` varchar(50) NOT NULL,
  `callback_type` enum('webhook','return_url','polling') NOT NULL,
  `request_method` varchar(10) DEFAULT 'POST',
  `request_headers` text DEFAULT NULL,
  `request_body` longtext DEFAULT NULL,
  `request_ip` varchar(45) DEFAULT NULL,
  `response_code` int(11) DEFAULT 0,
  `response_body` longtext DEFAULT NULL,
  `processed` tinyint(1) DEFAULT 0,
  `processing_error` text DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_gateway_settings`
--

CREATE TABLE `payment_gateway_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` longtext DEFAULT NULL,
  `setting_group` varchar(50) DEFAULT 'general',
  `description` varchar(255) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payment_gateway_settings`
--

INSERT INTO `payment_gateway_settings` (`id`, `setting_key`, `setting_value`, `setting_group`, `description`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'gateway_enabled', '1', 'general', 'Master switch for the payment gateway', NULL, '2026-07-14 07:52:06', '2026-07-14 07:52:06'),
(2, 'default_currency', 'UGX', 'general', 'Default payment currency', NULL, '2026-07-14 07:52:06', '2026-07-14 07:52:06'),
(3, 'payment_timeout_minutes', '30', 'general', 'Minutes before a pending payment expires', NULL, '2026-07-14 07:52:06', '2026-07-14 07:52:06'),
(4, 'auto_verify_enabled', '1', 'general', 'Automatically verify pending payments via cron', NULL, '2026-07-14 07:52:06', '2026-07-14 07:52:06'),
(5, 'receipt_prefix', 'ISNM', 'receipts', 'Prefix for receipt numbers', NULL, '2026-07-14 07:52:06', '2026-07-14 07:52:06'),
(6, 'receipt_starting_number', '10001', 'receipts', 'Starting receipt sequence number', NULL, '2026-07-14 07:52:06', '2026-07-14 07:52:06'),
(7, 'notification_email', 'finance@isnm.ac.ug', 'notifications', 'Email for payment notifications', NULL, '2026-07-14 07:52:06', '2026-07-14 07:52:06'),
(8, 'callback_base_url', '', 'webhooks', 'Base URL for payment callbacks', NULL, '2026-07-14 07:52:06', '2026-07-14 07:52:06'),
(9, 'bank_name', 'Stanbic Bank Uganda', 'bank_transfer', 'Bank name for transfers', NULL, '2026-07-14 07:52:06', '2026-07-14 07:52:06'),
(10, 'bank_account_name', 'Iganga School of Nursing and Midwifery', 'bank_transfer', 'Account name', NULL, '2026-07-14 07:52:06', '2026-07-14 07:52:06'),
(11, 'bank_account_number', '', 'bank_transfer', 'Bank account number', NULL, '2026-07-14 07:52:06', '2026-07-14 07:52:06'),
(12, 'bank_swift_code', '', 'bank_transfer', 'SWIFT/BIC code', NULL, '2026-07-14 07:52:06', '2026-07-14 07:52:06'),
(13, 'bank_branch', 'Iganga', 'bank_transfer', 'Bank branch', NULL, '2026-07-14 07:52:06', '2026-07-14 07:52:06');

-- --------------------------------------------------------

--
-- Table structure for table `payment_providers`
--

CREATE TABLE `payment_providers` (
  `id` int(11) NOT NULL,
  `provider_key` varchar(50) NOT NULL,
  `provider_name` varchar(100) NOT NULL,
  `provider_type` enum('mobile_money','card','bank','wallet','crypto') NOT NULL,
  `provider_category` enum('local','international','bank','mobile_money') NOT NULL DEFAULT 'local',
  `logo_url` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `api_base_url` varchar(255) DEFAULT NULL,
  `is_enabled` tinyint(1) DEFAULT 0,
  `merchant_id` varchar(255) DEFAULT '',
  `api_key` varchar(255) DEFAULT '',
  `api_secret` varchar(512) DEFAULT '',
  `public_key` text DEFAULT NULL,
  `private_key` text DEFAULT NULL,
  `api_url` varchar(500) DEFAULT '',
  `callback_url` varchar(500) DEFAULT '',
  `return_url` varchar(500) DEFAULT NULL,
  `webhook_secret` varchar(255) DEFAULT '',
  `hmac_secret` text DEFAULT NULL,
  `certificate_path` varchar(255) DEFAULT NULL,
  `config_data` longtext DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `total_transactions` int(11) DEFAULT 0,
  `total_volume` decimal(15,2) DEFAULT 0.00,
  `last_transaction_at` datetime DEFAULT NULL,
  `config_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `supported_currencies` varchar(255) DEFAULT 'UGX',
  `fee_type` enum('fixed','percentage','both','none') DEFAULT 'none',
  `fee_fixed` decimal(10,2) DEFAULT 0.00,
  `fee_percentage` decimal(5,2) DEFAULT 0.00,
  `transaction_fee_percent` decimal(5,2) DEFAULT 0.00,
  `transaction_fee_fixed` decimal(10,2) DEFAULT 0.00,
  `min_amount` decimal(12,2) DEFAULT 0.00,
  `max_amount` decimal(12,2) DEFAULT 10000000.00,
  `status` enum('active','inactive','sandbox') DEFAULT 'sandbox',
  `is_test_mode` tinyint(1) DEFAULT 1,
  `test_api_base_url` varchar(255) DEFAULT NULL,
  `test_api_key` text DEFAULT NULL,
  `test_api_secret` text DEFAULT NULL,
  `test_merchant_id` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

--
-- Dumping data for table `payment_providers`
--

INSERT INTO `payment_providers` (`id`, `provider_key`, `provider_name`, `provider_type`, `provider_category`, `logo_url`, `description`, `api_base_url`, `is_enabled`, `merchant_id`, `api_key`, `api_secret`, `public_key`, `private_key`, `api_url`, `callback_url`, `return_url`, `webhook_secret`, `hmac_secret`, `certificate_path`, `config_data`, `sort_order`, `total_transactions`, `total_volume`, `last_transaction_at`, `config_json`, `supported_currencies`, `fee_type`, `fee_fixed`, `fee_percentage`, `transaction_fee_percent`, `transaction_fee_fixed`, `min_amount`, `max_amount`, `status`, `is_test_mode`, `test_api_base_url`, `test_api_key`, `test_api_secret`, `test_merchant_id`, `created_at`, `updated_at`) VALUES
(1, 'mtn_momo', 'MTN Mobile Money', 'mobile_money', 'mobile_money', NULL, 'MTN MoMo mobile money payments for Uganda', 'https://sandbox.momodeveloper.mtn.com', 0, '', '', '', NULL, NULL, 'https://proxy.momoapi.mtn.com', '', NULL, '', NULL, NULL, NULL, 1, 0, 0.00, NULL, NULL, 'UGX', 'none', 0.00, 0.00, 0.00, 0.00, 0.00, 10000000.00, 'sandbox', 1, NULL, NULL, NULL, NULL, '2026-07-14 07:52:06', '2026-07-14 07:52:06'),
(2, 'airtel_money', 'Airtel Money', 'mobile_money', 'mobile_money', NULL, 'Airtel Money mobile payments for Uganda', 'https://openapi.airtel.ug/sandbox', 0, '', '', '', NULL, NULL, 'https://openapi.airtel.ug', '', NULL, '', NULL, NULL, NULL, 2, 0, 0.00, NULL, NULL, 'UGX', 'none', 0.00, 0.00, 0.00, 0.00, 0.00, 10000000.00, 'sandbox', 1, NULL, NULL, NULL, NULL, '2026-07-14 07:52:06', '2026-07-14 07:52:06'),
(3, 'stanbic_bank', 'Stanbic Bank', 'bank', 'bank', NULL, 'Stanbic Bank direct transfers and EFT', NULL, 0, '', '', '', NULL, NULL, NULL, '', NULL, '', NULL, NULL, NULL, 3, 0, 0.00, NULL, NULL, 'UGX', 'none', 0.00, 0.00, 0.00, 0.00, 0.00, 10000000.00, 'inactive', 1, NULL, NULL, NULL, NULL, '2026-07-14 07:52:06', '2026-07-14 07:52:06'),
(4, 'centenary_bank', 'Centenary Bank', 'bank', 'bank', NULL, 'Centenary Bank transfers', NULL, 0, '', '', '', NULL, NULL, NULL, '', NULL, '', NULL, NULL, NULL, 4, 0, 0.00, NULL, NULL, 'UGX', 'none', 0.00, 0.00, 0.00, 0.00, 0.00, 10000000.00, 'inactive', 1, NULL, NULL, NULL, NULL, '2026-07-14 07:52:06', '2026-07-14 07:52:06'),
(5, 'stanbic_card', 'Stanbic Card Services', 'card', 'international', NULL, 'Visa/Mastercard via Stanbic', NULL, 0, '', '', '', NULL, NULL, NULL, '', NULL, '', NULL, NULL, NULL, 5, 0, 0.00, NULL, NULL, 'UGX', 'none', 0.00, 0.00, 0.00, 0.00, 0.00, 10000000.00, 'inactive', 1, NULL, NULL, NULL, NULL, '2026-07-14 07:52:06', '2026-07-14 07:52:06'),
(6, 'flutterwave', 'Flutterwave', 'card', 'international', NULL, 'Flutterwave - Cards, Mobile Money, Bank across Africa', 'https://api.flutterwave.com', 0, '', '', '', NULL, NULL, 'https://api.flutterwave.com', '', NULL, '', NULL, NULL, NULL, 6, 0, 0.00, NULL, NULL, 'UGX', 'none', 0.00, 0.00, 0.00, 0.00, 0.00, 10000000.00, 'sandbox', 1, NULL, NULL, NULL, NULL, '2026-07-14 07:52:06', '2026-07-14 07:52:06'),
(7, 'pesapal', 'PesaPal', 'card', 'local', NULL, 'PesaPal - Cards and Mobile Money in East Africa', 'https://www.pesapal.com/api', 0, '', '', '', NULL, NULL, 'https://www.pesapal.com/api', '', NULL, '', NULL, NULL, NULL, 7, 0, 0.00, NULL, NULL, 'UGX', 'none', 0.00, 0.00, 0.00, 0.00, 0.00, 10000000.00, 'sandbox', 1, NULL, NULL, NULL, NULL, '2026-07-14 07:52:06', '2026-07-14 07:52:06'),
(8, 'stripe', 'Stripe', 'card', 'international', NULL, 'Stripe - International card payments (Visa, Mastercard, AMEX)', 'https://api.stripe.com/v1', 0, '', '', '', NULL, NULL, 'https://api.stripe.com/v1', '', NULL, '', NULL, NULL, NULL, 8, 0, 0.00, NULL, NULL, 'USD', 'none', 0.00, 0.00, 0.00, 0.00, 0.00, 10000000.00, 'sandbox', 1, NULL, NULL, NULL, NULL, '2026-07-14 07:52:06', '2026-07-14 07:52:06'),
(9, 'paypal', 'PayPal', 'wallet', 'international', NULL, 'PayPal wallet payments', 'https://api-m.sandbox.paypal.com', 0, '', '', '', NULL, NULL, 'https://api-m.paypal.com', '', NULL, '', NULL, NULL, NULL, 9, 0, 0.00, NULL, NULL, 'USD', 'none', 0.00, 0.00, 0.00, 0.00, 0.00, 10000000.00, 'sandbox', 1, NULL, NULL, NULL, NULL, '2026-07-14 07:52:06', '2026-07-14 07:52:06'),
(10, 'direct_bank', 'Direct Bank Transfer', 'bank', 'bank', NULL, 'Manual bank transfer with proof-of-payment verification', NULL, 0, '', '', '', NULL, NULL, NULL, '', NULL, '', NULL, NULL, NULL, 10, 0, 0.00, NULL, NULL, 'UGX', 'none', 0.00, 0.00, 0.00, 0.00, 0.00, 10000000.00, 'active', 0, NULL, NULL, NULL, NULL, '2026-07-14 07:52:06', '2026-07-14 07:52:06'),
(11, 'cash', 'Cash Payment', '', 'local', NULL, 'Cash payments recorded at finance office', NULL, 0, '', '', '', NULL, NULL, NULL, '', NULL, '', NULL, NULL, NULL, 11, 0, 0.00, NULL, NULL, 'UGX', 'none', 0.00, 0.00, 0.00, 0.00, 0.00, 10000000.00, 'active', 0, NULL, NULL, NULL, NULL, '2026-07-14 07:52:06', '2026-07-14 07:52:06'),
(12, 'cheque', 'Cheque Payment', '', 'local', NULL, 'Cheque payments', NULL, 0, '', '', '', NULL, NULL, NULL, '', NULL, '', NULL, NULL, NULL, 12, 0, 0.00, NULL, NULL, 'UGX', 'none', 0.00, 0.00, 0.00, 0.00, 0.00, 10000000.00, 'active', 0, NULL, NULL, NULL, NULL, '2026-07-14 07:52:06', '2026-07-14 07:52:06');

-- --------------------------------------------------------

--
-- Table structure for table `payment_receipts`
--

CREATE TABLE `payment_receipts` (
  `id` int(11) NOT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `receipt_date` timestamp NULL DEFAULT current_timestamp(),
  `issued_by` int(11) DEFAULT NULL,
  `voided` tinyint(1) DEFAULT 0,
  `voided_at` timestamp NULL DEFAULT NULL,
  `voided_by` int(11) DEFAULT NULL,
  `void_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_reconciliation`
--

CREATE TABLE `payment_reconciliation` (
  `id` int(11) NOT NULL,
  `reconciliation_date` date NOT NULL,
  `provider_key` varchar(50) NOT NULL,
  `expected_amount` decimal(15,2) DEFAULT 0.00,
  `actual_amount` decimal(15,2) DEFAULT 0.00,
  `difference` decimal(15,2) DEFAULT 0.00,
  `expected_count` int(11) DEFAULT 0,
  `actual_count` int(11) DEFAULT 0,
  `total_transactions` int(11) DEFAULT 0,
  `successful_count` int(11) DEFAULT 0,
  `failed_count` int(11) DEFAULT 0,
  `total_amount` decimal(14,2) DEFAULT 0.00,
  `total_fees` decimal(12,2) DEFAULT 0.00,
  `total_refunds` decimal(12,2) DEFAULT 0.00,
  `net_amount` decimal(14,2) DEFAULT 0.00,
  `status` enum('pending','completed','discrepancy') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `reconciled_by` int(11) DEFAULT 0,
  `reconciled_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_refunds`
--

CREATE TABLE `payment_refunds` (
  `id` int(11) NOT NULL,
  `refund_ref` varchar(100) NOT NULL,
  `original_transaction_id` int(11) NOT NULL,
  `provider_key` varchar(50) NOT NULL,
  `provider_refund_id` varchar(255) DEFAULT '',
  `amount` decimal(12,2) NOT NULL,
  `currency` varchar(10) NOT NULL DEFAULT 'UGX',
  `reason` text DEFAULT NULL,
  `status` enum('pending','processing','successful','failed') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `initiated_by` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_subscriptions`
--

CREATE TABLE `payment_subscriptions` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `subscription_type` enum('fee_installment','hostel','library','other') NOT NULL DEFAULT 'fee_installment',
  `reference_type` varchar(50) DEFAULT NULL COMMENT 'eg: fee_structure_id, hostel_room_id',
  `reference_id` int(11) DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `installment_amount` decimal(12,2) NOT NULL,
  `frequency` enum('monthly','weekly','quarterly') NOT NULL DEFAULT 'monthly',
  `total_installments` int(11) NOT NULL,
  `installments_collected` int(11) NOT NULL DEFAULT 0,
  `start_date` date NOT NULL DEFAULT curdate(),
  `next_due_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `payment_method` enum('mobile_money','bank','cash') DEFAULT 'mobile_money',
  `payment_provider` varchar(50) DEFAULT NULL COMMENT 'mtn_momo, airtel_money, etc.',
  `phone_number` varchar(20) DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account` varchar(50) DEFAULT NULL,
  `status` enum('active','paused','completed','cancelled','failed') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_by` varchar(50) DEFAULT NULL COMMENT 'student_id or staff_id who created',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

CREATE TABLE `payment_transactions` (
  `id` int(11) NOT NULL,
  `transaction_ref` varchar(100) NOT NULL,
  `provider_key` varchar(50) NOT NULL,
  `provider_transaction_id` varchar(255) DEFAULT '',
  `transaction_type` enum('payment','refund','reversal','withdrawal','topup') NOT NULL DEFAULT 'payment',
  `payment_type` enum('student_fees','application','admission','graduation','hostel','library_fine','donation','volunteer','staff','misc') NOT NULL,
  `reference_type` varchar(50) DEFAULT '',
  `reference_id` int(11) DEFAULT 0,
  `student_id` int(11) DEFAULT 0,
  `staff_id` int(11) DEFAULT 0,
  `applicant_id` int(11) DEFAULT NULL,
  `payer_name` varchar(255) DEFAULT '',
  `payer_phone` varchar(50) DEFAULT '',
  `payer_email` varchar(255) DEFAULT '',
  `amount` decimal(12,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'UGX',
  `amount_received` decimal(15,2) DEFAULT NULL,
  `fee_amount` decimal(12,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `net_amount` decimal(12,2) DEFAULT 0.00,
  `status` enum('pending','processing','successful','failed','cancelled','refunded','expired') DEFAULT 'pending',
  `status_reason` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `metadata` longtext DEFAULT NULL,
  `initiated_at` datetime DEFAULT current_timestamp(),
  `completed_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `verification_attempts` int(11) DEFAULT 0,
  `last_verification_at` datetime DEFAULT NULL,
  `status_message` varchar(500) DEFAULT '',
  `metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `initiated_by` int(11) DEFAULT 0,
  `ip_address` varchar(45) DEFAULT '',
  `user_agent` text DEFAULT NULL,
  `idempotency_key` varchar(100) DEFAULT NULL,
  `callback_received_at` timestamp NULL DEFAULT NULL,
  `reconciled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Table structure for table `payment_webhook_logs`
--

CREATE TABLE `payment_webhook_logs` (
  `id` int(11) NOT NULL,
  `provider_key` varchar(50) NOT NULL,
  `event_type` varchar(100) DEFAULT '',
  `payload` longtext DEFAULT NULL,
  `signature` varchar(512) DEFAULT '',
  `signature_valid` tinyint(1) DEFAULT NULL,
  `processed` tinyint(1) DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_approvals`
--

CREATE TABLE `payroll_approvals` (
  `id` int(11) NOT NULL,
  `budget_id` int(11) DEFAULT 0,
  `request_type` varchar(50) DEFAULT NULL,
  `requested_by` int(11) DEFAULT 0,
  `amount` decimal(14,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `status` enum('pending','approved','rejected','changes_requested','escalated') DEFAULT 'pending',
  `approver_id` int(11) DEFAULT 0,
  `approver_name` varchar(200) DEFAULT NULL,
  `approver_comments` text DEFAULT NULL,
  `escalated_to` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_details`
--

CREATE TABLE `payroll_details` (
  `id` int(11) NOT NULL,
  `payroll_run_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `basic_salary` decimal(14,2) DEFAULT 0.00,
  `gross_pay` decimal(14,2) DEFAULT 0.00,
  `paye_tax` decimal(14,2) DEFAULT 0.00,
  `nssf_employee` decimal(14,2) DEFAULT 0.00,
  `nssf_employer` decimal(14,2) DEFAULT 0.00,
  `other_deductions` decimal(14,2) DEFAULT 0.00,
  `net_pay` decimal(14,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_history`
--

CREATE TABLE `payroll_history` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `gross_salary` decimal(14,2) DEFAULT 0.00,
  `deductions` decimal(14,2) DEFAULT 0.00,
  `net_salary` decimal(14,2) DEFAULT 0.00,
  `payment_date` date DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT '',
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_records`
--

CREATE TABLE `payroll_records` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `gross_salary` decimal(12,2) DEFAULT 0.00,
  `total_deductions` decimal(12,2) DEFAULT 0.00,
  `net_salary` decimal(12,2) DEFAULT 0.00,
  `processed_by` int(11) DEFAULT 0,
  `processing_date` timestamp NULL DEFAULT current_timestamp(),
  `status` enum('Draft','Processed','Approved','Paid') DEFAULT 'Draft'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_runs`
--

CREATE TABLE `payroll_runs` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payslips`
--

CREATE TABLE `payslips` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `payroll_record_id` int(11) DEFAULT 0,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `basic_salary` decimal(12,2) DEFAULT 0.00,
  `allowances` decimal(12,2) DEFAULT 0.00,
  `deductions` decimal(12,2) DEFAULT 0.00,
  `net_pay` decimal(12,2) DEFAULT 0.00,
  `payment_date` date DEFAULT NULL,
  `status` enum('Generated','Sent','Paid') DEFAULT 'Generated',
  `generated_by` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `penalty_configurations`
--

CREATE TABLE `penalty_configurations` (
  `id` int(11) NOT NULL,
  `penalty_name` varchar(100) NOT NULL,
  `penalty_type` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
-- Table structure for table `principal_notices`
--

CREATE TABLE `principal_notices` (
  `id` int(11) NOT NULL,
  `title` varchar(300) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `audience` varchar(100) DEFAULT NULL,
  `published_by` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `principal_notices`
--

INSERT INTO `principal_notices` (`id`, `title`, `content`, `audience`, `published_by`, `created_at`) VALUES
(1, 'APPLICATIONS NOW OPEN!', 'Dear Prospective Students, Parents, and Guardians,\r\nOn behalf of Iganga School of Nursing and Midwifery, I am delighted to announce that applications for admission are now officially open.\r\nIf you aspire to build a rewarding career in nursing or midwifery and become a compassionate, skilled healthcare professional, this is your opportunity to join a respected institution committed to academic excellence, professionalism, and quality healthcare training.\r\nWe warmly invite all qualified applicants to apply and take the first step toward a fulfilling future in the healthcare profession.\r\nWhy Choose Iganga School of Nursing and Midwifery?\r\nQuality nursing and midwifery education\r\nExperienced and dedicated tutors\r\nPractical, hands-on clinical training\r\nA supportive learning environment\r\nCommitment to excellence and professional growth\r\nApply today and secure your place for the upcoming intake.\r\nFor application procedures and further inquiries, please contact the admissions office or visit the school during working hours.\r\nWe look forward to welcoming you to the Iganga School of Nursing and Midwifery family.', 'All', 'School Principal', '2026-06-28 09:36:43');

-- --------------------------------------------------------

--
-- Table structure for table `procurement_requests`
--

CREATE TABLE `procurement_requests` (
  `id` int(11) NOT NULL,
  `pr_number` varchar(100) DEFAULT NULL,
  `title` varchar(300) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(14,2) DEFAULT 0.00,
  `department` varchar(200) DEFAULT NULL,
  `supplier_name` varchar(200) DEFAULT NULL,
  `status` enum('draft','pending','approved','rejected') DEFAULT 'draft',
  `requested_by` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `id` int(11) NOT NULL,
  `program_code` varchar(20) NOT NULL,
  `program_name` varchar(255) NOT NULL,
  `program_type` enum('Certificate','Diploma','Degree') DEFAULT 'Diploma',
  `duration_years` int(11) DEFAULT 2,
  `total_fee` decimal(12,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`id`, `program_code`, `program_name`, `program_type`, `duration_years`, `total_fee`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'CNM', 'Certificate in Midwifery', 'Certificate', 2, 1220000.00, 1, '2026-07-02 07:23:48', '2026-07-14 16:52:52'),
(2, 'CNN', 'Certificate in Nursing', 'Certificate', 2, 1150000.00, 1, '2026-07-02 07:23:48', '2026-07-14 16:52:52'),
(3, 'DNM', 'Diploma in Nursing', 'Diploma', 3, 1625000.00, 1, '2026-07-02 07:23:48', '2026-07-14 16:52:52'),
(4, 'DMM', 'Diploma in Midwifery', 'Diploma', 3, 1685000.00, 1, '2026-07-02 07:23:48', '2026-07-14 16:52:52'),
(5, 'DNE', 'Diploma in Nursing Education', 'Diploma', 3, 1485000.00, 1, '2026-07-02 07:23:48', '2026-07-14 16:52:52'),
(6, 'BNM', 'Bachelor of Science in Nursing', 'Degree', 4, 3100000.00, 1, '2026-07-02 07:23:48', '2026-07-14 16:52:52'),
(7, 'CNM', 'Certificate in Midwifery', 'Certificate', 2, 1220000.00, 1, '2026-07-02 08:08:51', '2026-07-14 16:52:52'),
(8, 'CNN', 'Certificate in Nursing', 'Certificate', 2, 1150000.00, 1, '2026-07-02 08:08:51', '2026-07-14 16:52:52'),
(9, 'DNM', 'Diploma in Nursing', 'Diploma', 3, 1625000.00, 1, '2026-07-02 08:08:51', '2026-07-14 16:52:52'),
(10, 'DMM', 'Diploma in Midwifery', 'Diploma', 3, 1685000.00, 1, '2026-07-02 08:08:51', '2026-07-14 16:52:52'),
(11, 'DNE', 'Diploma in Nursing Education', 'Diploma', 3, 1485000.00, 1, '2026-07-02 08:08:51', '2026-07-14 16:52:52'),
(12, 'BNM', 'Bachelor of Science in Nursing', 'Degree', 4, 3100000.00, 1, '2026-07-02 08:08:51', '2026-07-14 16:52:52'),
(13, 'CNM', 'Certificate in Midwifery', 'Certificate', 2, 1220000.00, 1, '2026-07-03 03:56:26', '2026-07-14 16:52:52'),
(14, 'CNN', 'Certificate in Nursing', 'Certificate', 2, 1150000.00, 1, '2026-07-03 03:56:26', '2026-07-14 16:52:52'),
(15, 'DNM', 'Diploma in Nursing', 'Diploma', 3, 1625000.00, 1, '2026-07-03 03:56:26', '2026-07-14 16:52:52'),
(16, 'DMM', 'Diploma in Midwifery', 'Diploma', 3, 1685000.00, 1, '2026-07-03 03:56:26', '2026-07-14 16:52:52'),
(17, 'DNE', 'Diploma in Nursing Education', 'Diploma', 3, 1485000.00, 1, '2026-07-03 03:56:26', '2026-07-14 16:52:52'),
(18, 'BNM', 'Bachelor of Science in Nursing', 'Degree', 4, 3100000.00, 1, '2026-07-03 03:56:26', '2026-07-14 16:52:52'),
(19, 'CNM', 'Certificate in Midwifery', 'Certificate', 2, 1220000.00, 1, '2026-07-03 04:05:12', '2026-07-14 16:52:52'),
(20, 'CNN', 'Certificate in Nursing', 'Certificate', 2, 1150000.00, 1, '2026-07-03 04:05:12', '2026-07-14 16:52:52'),
(21, 'DNM', 'Diploma in Nursing', 'Diploma', 3, 1625000.00, 1, '2026-07-03 04:05:12', '2026-07-14 16:52:52'),
(22, 'DMM', 'Diploma in Midwifery', 'Diploma', 3, 1685000.00, 1, '2026-07-03 04:05:12', '2026-07-14 16:52:52'),
(23, 'DNE', 'Diploma in Nursing Education', 'Diploma', 3, 1485000.00, 1, '2026-07-03 04:05:12', '2026-07-14 16:52:52'),
(24, 'BNM', 'Bachelor of Science in Nursing', 'Degree', 4, 3100000.00, 1, '2026-07-03 04:05:12', '2026-07-14 16:52:52'),
(25, 'CNM', 'Certificate in Midwifery', 'Certificate', 2, 1220000.00, 1, '2026-07-03 04:38:06', '2026-07-14 16:52:52'),
(26, 'CNN', 'Certificate in Nursing', 'Certificate', 2, 1150000.00, 1, '2026-07-03 04:38:06', '2026-07-14 16:52:52'),
(27, 'DNM', 'Diploma in Nursing', 'Diploma', 3, 1625000.00, 1, '2026-07-03 04:38:06', '2026-07-14 16:52:52'),
(28, 'DMM', 'Diploma in Midwifery', 'Diploma', 3, 1685000.00, 1, '2026-07-03 04:38:06', '2026-07-14 16:52:52'),
(29, 'DNE', 'Diploma in Nursing Education', 'Diploma', 3, 1485000.00, 1, '2026-07-03 04:38:06', '2026-07-14 16:52:52'),
(30, 'BNM', 'Bachelor of Science in Nursing', 'Degree', 4, 3100000.00, 1, '2026-07-03 04:38:06', '2026-07-14 16:52:52'),
(31, 'CNM', 'Certificate in Midwifery', 'Certificate', 2, 1220000.00, 1, '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(32, 'CNN', 'Certificate in Nursing', 'Certificate', 2, 1150000.00, 1, '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(33, 'DNM', 'Diploma in Nursing', 'Diploma', 3, 1625000.00, 1, '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(34, 'DMM', 'Diploma in Midwifery', 'Diploma', 3, 1685000.00, 1, '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(35, 'DNE', 'Diploma in Nursing Education', 'Diploma', 3, 1485000.00, 1, '2026-07-03 04:51:14', '2026-07-14 16:52:52'),
(36, 'BNM', 'Bachelor of Science in Nursing', 'Degree', 4, 3100000.00, 1, '2026-07-03 04:51:14', '2026-07-14 16:52:52');

-- --------------------------------------------------------

--
-- Table structure for table `proof_of_payments`
--

CREATE TABLE `proof_of_payments` (
  `id` int(11) NOT NULL,
  `proof_number` varchar(50) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `document_path` varchar(500) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `push_subscriptions`
--

CREATE TABLE `push_subscriptions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `endpoint` varchar(500) NOT NULL,
  `auth_key` varchar(255) DEFAULT NULL,
  `p256dh_key` varchar(255) DEFAULT NULL,
  `device_type` varchar(50) DEFAULT 'desktop',
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quality_assurance`
--

CREATE TABLE `quality_assurance` (
  `id` int(11) NOT NULL,
  `review_title` varchar(300) DEFAULT NULL,
  `review_type` varchar(200) DEFAULT NULL,
  `department` varchar(200) DEFAULT NULL,
  `reviewer` varchar(200) DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `findings` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `status` enum('draft','completed','reviewed') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quality_assurance_reviews`
--

CREATE TABLE `quality_assurance_reviews` (
  `id` int(11) NOT NULL,
  `review_title` varchar(300) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `review_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `findings` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrar_certificates`
--

CREATE TABLE `registrar_certificates` (
  `id` int(11) NOT NULL,
  `certificate_number` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `graduation_date` date DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `certificate_type` enum('Certificate','Diploma','Degree','Transcript') DEFAULT 'Certificate',
  `gpa` decimal(5,2) DEFAULT NULL,
  `cgpa` decimal(5,2) DEFAULT NULL,
  `class_of_award` varchar(100) DEFAULT NULL,
  `status` enum('Draft','Generated','Issued','Collected','Cancelled') DEFAULT 'Draft',
  `generated_by` int(11) DEFAULT NULL,
  `generated_date` datetime DEFAULT NULL,
  `issued_by` int(11) DEFAULT NULL,
  `issued_date` datetime DEFAULT NULL,
  `collected_by` varchar(255) DEFAULT NULL,
  `collected_date` datetime DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrar_settings`
--

CREATE TABLE `registrar_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(50) DEFAULT 'general',
  `description` varchar(500) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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

CREATE TABLE `registrar_transcript_requests` (
  `id` int(11) NOT NULL,
  `request_number` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `purpose` varchar(500) DEFAULT NULL,
  `copies_requested` int(11) DEFAULT 1,
  `copies_issued` int(11) DEFAULT 0,
  `fee` decimal(10,2) DEFAULT 0.00,
  `payment_status` enum('Pending','Paid','Waived') DEFAULT 'Pending',
  `status` enum('Pending','Processing','Ready','Issued','Collected','Rejected') DEFAULT 'Pending',
  `requested_by` varchar(255) DEFAULT NULL,
  `request_date` datetime DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `processed_date` datetime DEFAULT NULL,
  `issued_by` int(11) DEFAULT NULL,
  `issued_date` datetime DEFAULT NULL,
  `collected_by` varchar(255) DEFAULT NULL,
  `collected_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reminders`
--

CREATE TABLE `reminders` (
  `s_no` int(11) NOT NULL,
  `id` varchar(50) NOT NULL,
  `message` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `request_tracking`
--

CREATE TABLE `request_tracking` (
  `id` int(11) NOT NULL,
  `request_title` varchar(300) DEFAULT NULL,
  `request_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `assigned_to` varchar(200) DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') DEFAULT 'normal',
  `status` enum('pending','approved','rejected','completed') DEFAULT 'pending',
  `requested_by` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `requirement_categories`
--

CREATE TABLE `requirement_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `requirement_history`
--

CREATE TABLE `requirement_history` (
  `id` int(11) NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `requirement_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `previous_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `risk_register`
--

CREATE TABLE `risk_register` (
  `id` int(11) NOT NULL,
  `risk_name` varchar(300) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `likelihood` enum('low','medium','high') DEFAULT 'medium',
  `impact` enum('low','medium','high') DEFAULT 'medium',
  `mitigation` text DEFAULT NULL,
  `status` enum('active','monitored','resolved') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `salary_components`
--

CREATE TABLE `salary_components` (
  `id` int(11) NOT NULL,
  `component_name` varchar(100) NOT NULL,
  `component_type` enum('Earning','Deduction') DEFAULT 'Earning',
  `description` text DEFAULT NULL,
  `is_percentage` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `secretary_messages`
--

CREATE TABLE `secretary_messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT 0,
  `sender_name` varchar(200) DEFAULT NULL,
  `recipient_type` varchar(50) DEFAULT NULL,
  `recipient_id` int(11) DEFAULT 0,
  `subject` varchar(300) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `attachment` varchar(500) DEFAULT NULL,
  `is_read` tinyint(4) DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(100) DEFAULT 'general',
  `description` varchar(500) DEFAULT NULL,
  `is_public` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sickness_directory`
--

CREATE TABLE `sickness_directory` (
  `id` int(11) NOT NULL,
  `sickness_code` varchar(20) NOT NULL,
  `sickness_name` varchar(255) NOT NULL,
  `category` enum('Infectious','Non-Infectious','Chronic','Injury','Mental Health','Nutritional','Other') DEFAULT 'Other',
  `common_symptoms` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_contagious` tinyint(1) DEFAULT 0,
  `typical_treatment` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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

CREATE TABLE `sponsorships` (
  `id` int(11) NOT NULL,
  `sponsorship_code` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `sponsor_name` varchar(255) NOT NULL,
  `sponsor_type` enum('Government','NGO','Private','Self','Other') DEFAULT 'Self',
  `sponsorship_type` enum('Full','Partial','Tuition Only','Other') DEFAULT 'Partial',
  `amount` decimal(12,2) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `terms_conditions` text DEFAULT NULL,
  `status` enum('Active','Expired','Cancelled') DEFAULT 'Active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_leave`
--

CREATE TABLE `staff_leave` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `leave_type` varchar(50) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `days_taken` int(11) DEFAULT 0,
  `reason` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_profiles`
--

CREATE TABLE `staff_profiles` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `profile_picture` varchar(500) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `qualifications` text DEFAULT NULL,
  `specialization` varchar(255) DEFAULT NULL,
  `office_location` varchar(255) DEFAULT NULL,
  `office_phone` varchar(50) DEFAULT NULL,
  `emergency_contact` varchar(100) DEFAULT NULL,
  `emergency_phone` varchar(50) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `national_id` varchar(100) DEFAULT NULL,
  `employment_date` date DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_roles`
--

CREATE TABLE `staff_roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_salaries`
--

CREATE TABLE `staff_salaries` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `base_salary` decimal(12,2) NOT NULL,
  `allowances` decimal(12,2) DEFAULT 0.00,
  `deductions` decimal(12,2) DEFAULT 0.00,
  `net_salary` decimal(12,2) GENERATED ALWAYS AS (`base_salary` + `allowances` - `deductions`) STORED,
  `effective_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('Active','Inactive','Pending') DEFAULT 'Active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_tasks`
--

CREATE TABLE `staff_tasks` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `category` varchar(100) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `assigned_by` varchar(255) DEFAULT NULL,
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_training`
--

CREATE TABLE `staff_training` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `training_name` varchar(255) NOT NULL,
  `training_type` varchar(100) DEFAULT NULL,
  `provider` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `staff_trainings`
-- (See below for the actual view)
--
CREATE TABLE `staff_trainings` (
`id` int(11)
,`staff_id` int(11)
,`training_name` varchar(255)
,`training_type` varchar(100)
,`provider` varchar(255)
,`start_date` date
,`end_date` date
,`status` varchar(50)
,`notes` text
,`created_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `store_categories`
--

CREATE TABLE `store_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(200) DEFAULT '',
  `description` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `store_inventory`
--

CREATE TABLE `store_inventory` (
  `id` int(11) NOT NULL,
  `item_code` varchar(50) DEFAULT '',
  `item_name` varchar(200) DEFAULT '',
  `category_id` int(11) DEFAULT NULL,
  `unit` varchar(50) DEFAULT '',
  `quantity` decimal(14,2) DEFAULT 0.00,
  `reorder_level` decimal(14,2) DEFAULT 0.00,
  `unit_cost` decimal(14,2) DEFAULT 0.00,
  `location` varchar(200) DEFAULT '',
  `batch_number` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `supplier` varchar(200) DEFAULT '',
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `store_inventory_transactions`
--

CREATE TABLE `store_inventory_transactions` (
  `id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT 0,
  `transaction_type` varchar(50) DEFAULT '',
  `quantity` decimal(14,2) DEFAULT 0.00,
  `quantity_before` decimal(14,2) DEFAULT NULL,
  `quantity_after` decimal(14,2) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `store_requests`
--

CREATE TABLE `store_requests` (
  `id` int(11) NOT NULL,
  `request_number` varchar(50) DEFAULT '',
  `requested_by` int(11) DEFAULT 0,
  `requester_name` varchar(255) DEFAULT '',
  `requester_role` varchar(50) DEFAULT '',
  `department` varchar(100) DEFAULT '',
  `urgency` varchar(50) DEFAULT 'Normal',
  `status` varchar(50) DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `items` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `fulfilled_by` int(11) DEFAULT NULL,
  `fulfilled_at` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approval_request_id` int(11) DEFAULT NULL,
  `forwarded_to` int(11) DEFAULT NULL,
  `forwarded_to_role` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `store_request_items`
--

CREATE TABLE `store_request_items` (
  `id` int(11) NOT NULL,
  `request_id` int(11) DEFAULT 0,
  `item_id` int(11) DEFAULT 0,
  `quantity_requested` decimal(14,2) DEFAULT 0.00,
  `quantity_fulfilled` decimal(14,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `strategic_initiatives`
--

CREATE TABLE `strategic_initiatives` (
  `id` int(11) NOT NULL,
  `plan_id` int(11) DEFAULT NULL,
  `initiative_name` varchar(300) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `target_date` date DEFAULT NULL,
  `progress` decimal(5,2) DEFAULT 0.00,
  `status` enum('not_started','in_progress','completed') DEFAULT 'not_started',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `strategic_plans`
--

CREATE TABLE `strategic_plans` (
  `id` int(11) NOT NULL,
  `plan_name` varchar(300) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('draft','active','completed','cancelled') DEFAULT 'draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
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
  `course_codes` text DEFAULT NULL,
  `current_year` int(11) DEFAULT NULL,
  `year_of_study` int(11) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
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
  `sponsor` varchar(200) DEFAULT NULL,
  `marital_status` varchar(20) DEFAULT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `student_category` varchar(50) DEFAULT NULL,
  `guardian_name` varchar(200) DEFAULT NULL,
  `guardian_phone` varchar(20) DEFAULT NULL,
  `guardian_email` varchar(100) DEFAULT NULL,
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
  `intake_period` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `student_number`, `registration_number`, `national_student_id_number`, `index_number`, `first_name`, `surname`, `other_name`, `full_name`, `email`, `password`, `phone`, `mobile_number`, `program`, `course`, `course_codes`, `current_year`, `year_of_study`, `year`, `level`, `set_name`, `current_semester`, `intake_date`, `date_of_birth`, `gender`, `nationality`, `address`, `district`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_email`, `sponsor`, `marital_status`, `religion`, `student_category`, `guardian_name`, `guardian_phone`, `guardian_email`, `profile_picture`, `passport_photo`, `status`, `last_login`, `locked_until`, `login_attempts`, `password_changed`, `is_first_login`, `created_at`, `updated_at`, `intake_year`, `intake_period`) VALUES
(1, 'ISNM/0001/25', NULL, NULL, 'UACE/CNM/0001', 'Mary', 'Muwonge', NULL, 'Sarah Ssenyonjo', 'student1@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-774571227', '+256-774571227', 'Certificate in Midwifery', 'Certificate in Midwifery', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Mayuge', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(2, 'ISNM/0002/25', NULL, NULL, 'UACE/CNM/0002', 'Peace', 'Nakamya', NULL, 'Jane Ochieng', 'student2@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-776779291', '+256-776779291', 'Certificate in Midwifery', 'Certificate in Midwifery', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Jinja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(3, 'ISNM/0003/25', NULL, NULL, 'UACE/CNM/0003', 'Moses', 'Okello', NULL, 'David Nanteza', 'student3@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-781279895', '+256-781279895', 'Certificate in Midwifery', 'Certificate in Midwifery', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Jinja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(4, 'ISNM/0004/25', NULL, NULL, 'UACE/CNM/0004', 'Ruth', 'Sserwadda', NULL, 'Mary Ssenyonjo', 'student4@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-781236556', '+256-781236556', 'Certificate in Midwifery', 'Certificate in Midwifery', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Jinja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(5, 'ISNM/0005/25', NULL, NULL, 'UACE/CNM/0005', 'Jane', 'Muwonge', NULL, 'John Okello', 'student5@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-775043713', '+256-775043713', 'Certificate in Midwifery', 'Certificate in Midwifery', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(6, 'ISNM/0006/25', NULL, NULL, 'UACE/CNM/0006', 'Esther', 'Nabirye', NULL, 'Mary Ochieng', 'student6@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-784260559', '+256-784260559', 'Certificate in Midwifery', 'Certificate in Midwifery', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Male', 'Ugandan', 'Jinja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(7, 'ISNM/0007/25', NULL, NULL, 'UACE/CNM/0007', 'Peace', 'Nabirye', NULL, 'Joy Nabirye', 'student7@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-703988337', '+256-703988337', 'Certificate in Midwifery', 'Certificate in Midwifery', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(8, 'ISNM/0008/25', NULL, NULL, 'UACE/CNM/0008', 'Faith', 'Namukwaya', NULL, 'Sarah Ssenyonjo', 'student8@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-703728063', '+256-703728063', 'Certificate in Midwifery', 'Certificate in Midwifery', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Jinja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(9, 'ISNM/0009/25', NULL, NULL, 'UACE/CNM/0009', 'Esther', 'Nakamya', NULL, 'John Okello', 'student9@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-782284500', '+256-782284500', 'Certificate in Midwifery', 'Certificate in Midwifery', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Kamuli', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(10, 'ISNM/0010/25', NULL, NULL, 'UACE/CNM/0010', 'David', 'Kintu', NULL, 'Alice Nanteza', 'student10@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-785019393', '+256-785019393', 'Certificate in Midwifery', 'Certificate in Midwifery', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Iganga', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(11, 'ISNM/0011/25', NULL, NULL, 'UACE/CNM/0011', 'Joy', 'Wasswa', NULL, 'Esther Nakato', 'student11@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-788356144', '+256-788356144', 'Certificate in Midwifery', 'Certificate in Midwifery', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Jinja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 1, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(12, 'ISNM/0012/25', NULL, NULL, 'UACE/CNM/0012', 'Esther', 'Lubega', NULL, 'Faith Sserwadda', 'student12@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-777122632', '+256-777122632', 'Certificate in Midwifery', 'Certificate in Midwifery', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Male', 'Ugandan', 'Jinja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(13, 'ISNM/0013/25', NULL, NULL, 'UACE/CNM/0013', 'John', 'Wasswa', NULL, 'Faith Nakamya', 'student13@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-778607555', '+256-778607555', 'Certificate in Midwifery', 'Certificate in Midwifery', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(14, 'ISNM/0014/25', NULL, NULL, 'UACE/CNM/0014', 'Peter', 'Mukasa', NULL, 'David Nanteza', 'student14@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-772111880', '+256-772111880', 'Certificate in Midwifery', 'Certificate in Midwifery', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Jinja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(15, 'ISNM/0015/25', NULL, NULL, 'UACE/CNM/0015', 'Samuel', 'Wasswa', NULL, 'Mary Kizza', 'student15@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-780170078', '+256-780170078', 'Certificate in Midwifery', 'Certificate in Midwifery', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Mayuge', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(16, 'ISNM/0016/25', NULL, NULL, 'UACE/CNM/0016', 'Samuel', 'Kizza', NULL, 'David Ochieng', 'student16@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-785312802', '+256-785312802', 'Certificate in Midwifery', 'Certificate in Midwifery', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Mayuge', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(17, 'ISNM/0017/25', NULL, NULL, 'UACE/CNM/0017', 'Samuel', 'Lubega', NULL, 'Jane Nakato', 'student17@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-774130995', '+256-774130995', 'Certificate in Midwifery', 'Certificate in Midwifery', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Kamuli', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(18, 'ISNM/0018/25', NULL, NULL, 'UACE/CNM/0018', 'Esther', 'Ochieng', NULL, 'Grace Nakato', 'student18@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-707727624', '+256-707727624', 'Certificate in Midwifery', 'Certificate in Midwifery', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Male', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(19, 'ISNM/0019/25', NULL, NULL, 'UACE/CNM/0019', 'Mary', 'Muwonge', NULL, 'Grace Nabirye', 'student19@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-708314660', '+256-708314660', 'Certificate in Midwifery', 'Certificate in Midwifery', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(20, 'ISNM/0020/25', NULL, NULL, 'UACE/CNM/0020', 'Peter', 'Nanteza', NULL, 'Ruth Kintu', 'student20@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-781502155', '+256-781502155', 'Certificate in Midwifery', 'Certificate in Midwifery', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Jinja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(21, 'ISNM/0021/25', NULL, NULL, 'UACE/CNN/0021', 'Mary', 'Namukwaya', NULL, 'Sarah Muwonge', 'student21@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-782204948', '+256-782204948', 'Certificate in Nursing', 'Certificate in Nursing', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Mayuge', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(22, 'ISNM/0022/25', NULL, NULL, 'UACE/CNN/0022', 'Ruth', 'Kizza', NULL, 'Moses Nakamya', 'student22@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-700903908', '+256-700903908', 'Certificate in Nursing', 'Certificate in Nursing', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Jinja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(23, 'ISNM/0023/25', NULL, NULL, 'UACE/CNN/0023', 'Ruth', 'Ochieng', NULL, 'Joy Nakato', 'student23@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-709011142', '+256-709011142', 'Certificate in Nursing', 'Certificate in Nursing', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Mayuge', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(24, 'ISNM/0024/25', NULL, NULL, 'UACE/CNN/0024', 'Mary', 'Nabirye', NULL, 'John Okello', 'student24@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-782412749', '+256-782412749', 'Certificate in Nursing', 'Certificate in Nursing', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Male', 'Ugandan', 'Kamuli', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(25, 'ISNM/0025/25', NULL, NULL, 'UACE/CNN/0025', 'Samuel', 'Sserwadda', NULL, 'Joy Nanteza', 'student25@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-772757319', '+256-772757319', 'Certificate in Nursing', 'Certificate in Nursing', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(26, 'ISNM/0026/25', NULL, NULL, 'UACE/CNN/0026', 'Grace', 'Ssenyonjo', NULL, 'Sarah Mukasa', 'student26@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-781924183', '+256-781924183', 'Certificate in Nursing', 'Certificate in Nursing', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Jinja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(27, 'ISNM/0027/25', NULL, NULL, 'UACE/CNN/0027', 'Ruth', 'Mukasa', NULL, 'David Nakamya', 'student27@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-789578199', '+256-789578199', 'Certificate in Nursing', 'Certificate in Nursing', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Jinja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(28, 'ISNM/0028/25', NULL, NULL, 'UACE/CNN/0028', 'Moses', 'Ochieng', NULL, 'Esther Ssenyonjo', 'student28@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-784557465', '+256-784557465', 'Certificate in Nursing', 'Certificate in Nursing', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Mayuge', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(29, 'ISNM/0029/25', NULL, NULL, 'UACE/CNN/0029', 'David', 'Ochieng', NULL, 'Peter Muwonge', 'student29@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-784068713', '+256-784068713', 'Certificate in Nursing', 'Certificate in Nursing', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Iganga', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(30, 'ISNM/0030/25', NULL, NULL, 'UACE/CNN/0030', 'John', 'Namukwaya', NULL, 'Peace Kizza', 'student30@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-789042037', '+256-789042037', 'Certificate in Nursing', 'Certificate in Nursing', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Male', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(31, 'ISNM/0031/25', NULL, NULL, 'UACE/CNN/0031', 'Moses', 'Nabirye', NULL, 'Ruth Nakato', 'student31@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-774563401', '+256-774563401', 'Certificate in Nursing', 'Certificate in Nursing', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Mayuge', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(32, 'ISNM/0032/25', NULL, NULL, 'UACE/CNN/0032', 'Esther', 'Nanteza', NULL, 'Faith Namukwaya', 'student32@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-708761693', '+256-708761693', 'Certificate in Nursing', 'Certificate in Nursing', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Iganga', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(33, 'ISNM/0033/25', NULL, NULL, 'UACE/CNN/0033', 'Mary', 'Ochieng', NULL, 'Samuel Mukasa', 'student33@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-787886390', '+256-787886390', 'Certificate in Nursing', 'Certificate in Nursing', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Iganga', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(34, 'ISNM/0034/25', NULL, NULL, 'UACE/CNN/0034', 'Peter', 'Okello', NULL, 'Esther Kintu', 'student34@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-706063539', '+256-706063539', 'Certificate in Nursing', 'Certificate in Nursing', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Iganga', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(35, 'ISNM/0035/25', NULL, NULL, 'UACE/CNN/0035', 'Peter', 'Mukasa', NULL, 'John Kintu', 'student35@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-707425760', '+256-707425760', 'Certificate in Nursing', 'Certificate in Nursing', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Kamuli', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(36, 'ISNM/0036/25', NULL, NULL, 'UACE/CNN/0036', 'Moses', 'Namukwaya', NULL, 'Moses Kintu', 'student36@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-789443939', '+256-789443939', 'Certificate in Nursing', 'Certificate in Nursing', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Male', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(37, 'ISNM/0037/25', NULL, NULL, 'UACE/CNN/0037', 'Mary', 'Ssenyonjo', NULL, 'Peter Kintu', 'student37@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-786637356', '+256-786637356', 'Certificate in Nursing', 'Certificate in Nursing', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Kamuli', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(38, 'ISNM/0038/25', NULL, NULL, 'UACE/CNN/0038', 'Grace', 'Kizza', NULL, 'Ruth Nabirye', 'student38@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-779129500', '+256-779129500', 'Certificate in Nursing', 'Certificate in Nursing', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(39, 'ISNM/0039/25', NULL, NULL, 'UACE/CNN/0039', 'Esther', 'Nabirye', NULL, 'Sarah Ssenyonjo', 'student39@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-775114752', '+256-775114752', 'Certificate in Nursing', 'Certificate in Nursing', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Iganga', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(40, 'ISNM/0040/25', NULL, NULL, 'UACE/CNN/0040', 'Alice', 'Kizza', NULL, 'Mary Okello', 'student40@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-772095035', '+256-772095035', 'Certificate in Nursing', 'Certificate in Nursing', NULL, 1, NULL, 1, 'Certificate', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Mayuge', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(41, 'ISNM/0041/24', NULL, NULL, 'UACE/DNM/0041', 'Joy', 'Ssenyonjo', NULL, 'Esther Nakamya', 'student41@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-778993733', '+256-778993733', 'Diploma in Nursing', 'Diploma in Nursing', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Kamuli', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(42, 'ISNM/0042/24', NULL, NULL, 'UACE/DNM/0042', 'Sarah', 'Ssenyonjo', NULL, 'Ruth Nakamya', 'student42@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-702057084', '+256-702057084', 'Diploma in Nursing', 'Diploma in Nursing', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Male', 'Ugandan', 'Mayuge', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(43, 'ISNM/0043/24', NULL, NULL, 'UACE/DNM/0043', 'David', 'Wasswa', NULL, 'Jane Nakamya', 'student43@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-789414023', '+256-789414023', 'Diploma in Nursing', 'Diploma in Nursing', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Kamuli', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(44, 'ISNM/0044/24', NULL, NULL, 'UACE/DNM/0044', 'David', 'Lubega', NULL, 'John Mukasa', 'student44@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-771067107', '+256-771067107', 'Diploma in Nursing', 'Diploma in Nursing', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Jinja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(45, 'ISNM/0045/24', NULL, NULL, 'UACE/DNM/0045', 'Samuel', 'Ochieng', NULL, 'Samuel Sserwadda', 'student45@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-778896965', '+256-778896965', 'Diploma in Nursing', 'Diploma in Nursing', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Iganga', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(46, 'ISNM/0046/24', NULL, NULL, 'UACE/DNM/0046', 'Peter', 'Mukasa', NULL, 'Grace Sserwadda', 'student46@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-787898635', '+256-787898635', 'Diploma in Nursing', 'Diploma in Nursing', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(47, 'ISNM/0047/24', NULL, NULL, 'UACE/DNM/0047', 'Sarah', 'Muwonge', NULL, 'Moses Nabirye', 'student47@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-774581010', '+256-774581010', 'Diploma in Nursing', 'Diploma in Nursing', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(48, 'ISNM/0048/24', NULL, NULL, 'UACE/DNM/0048', 'Peace', 'Nanteza', NULL, 'Joy Wasswa', 'student48@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-770178473', '+256-770178473', 'Diploma in Nursing', 'Diploma in Nursing', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Male', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(49, 'ISNM/0049/24', NULL, NULL, 'UACE/DNM/0049', 'Peter', 'Wasswa', NULL, 'Samuel Kintu', 'student49@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-704546145', '+256-704546145', 'Diploma in Nursing', 'Diploma in Nursing', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Iganga', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(50, 'ISNM/0050/24', NULL, NULL, 'UACE/DNM/0050', 'Samuel', 'Nakato', NULL, 'David Nanteza', 'student50@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-772195098', '+256-772195098', 'Diploma in Nursing', 'Diploma in Nursing', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Jinja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(51, 'ISNM/0051/24', NULL, NULL, 'UACE/DNM/0051', 'Ruth', 'Sserwadda', NULL, 'Esther Kizza', 'student51@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-784744390', '+256-784744390', 'Diploma in Nursing', 'Diploma in Nursing', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(52, 'ISNM/0052/24', NULL, NULL, 'UACE/DNM/0052', 'Peace', 'Lubega', NULL, 'Jane Sserwadda', 'student52@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-784913420', '+256-784913420', 'Diploma in Nursing', 'Diploma in Nursing', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(53, 'ISNM/0053/24', NULL, NULL, 'UACE/DNM/0053', 'Joy', 'Ochieng', NULL, 'Samuel Wasswa', 'student53@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-704965732', '+256-704965732', 'Diploma in Nursing', 'Diploma in Nursing', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Jinja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(54, 'ISNM/0054/24', NULL, NULL, 'UACE/DNM/0054', 'Alice', 'Ssenyonjo', NULL, 'Jane Kintu', 'student54@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-783688931', '+256-783688931', 'Diploma in Nursing', 'Diploma in Nursing', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Male', 'Ugandan', 'Jinja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(55, 'ISNM/0055/24', NULL, NULL, 'UACE/DNM/0055', 'Esther', 'Namukwaya', NULL, 'Jane Namukwaya', 'student55@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-786236264', '+256-786236264', 'Diploma in Nursing', 'Diploma in Nursing', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Kamuli', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(56, 'ISNM/0056/24', NULL, NULL, 'UACE/DNM/0056', 'Ruth', 'Wasswa', NULL, 'Samuel Kintu', 'student56@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-703507071', '+256-703507071', 'Diploma in Nursing', 'Diploma in Nursing', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(57, 'ISNM/0057/24', NULL, NULL, 'UACE/DNM/0057', 'Peter', 'Kintu', NULL, 'David Sserwadda', 'student57@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-771885879', '+256-771885879', 'Diploma in Nursing', 'Diploma in Nursing', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(58, 'ISNM/0058/24', NULL, NULL, 'UACE/DNM/0058', 'Peter', 'Mukasa', NULL, 'David Kintu', 'student58@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-773974868', '+256-773974868', 'Diploma in Nursing', 'Diploma in Nursing', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(59, 'ISNM/0059/24', NULL, NULL, 'UACE/DNM/0059', 'John', 'Namukwaya', NULL, 'Alice Ochieng', 'student59@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-709375762', '+256-709375762', 'Diploma in Nursing', 'Diploma in Nursing', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Iganga', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(60, 'ISNM/0060/24', NULL, NULL, 'UACE/DNM/0060', 'Joy', 'Namukwaya', NULL, 'Joy Ssenyonjo', 'student60@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-782151802', '+256-782151802', 'Diploma in Nursing', 'Diploma in Nursing', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Male', 'Ugandan', 'Iganga', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(61, 'ISNM/0061/24', NULL, NULL, 'UACE/DNM/0061', 'Grace', 'Namukwaya', NULL, 'Jane Nakato', 'student61@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-770184003', '+256-770184003', 'Diploma in Nursing', 'Diploma in Nursing', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Mayuge', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(62, 'ISNM/0062/24', NULL, NULL, 'UACE/DNM/0062', 'David', 'Nakato', NULL, 'Peter Nakamya', 'student62@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-708222949', '+256-708222949', 'Diploma in Nursing', 'Diploma in Nursing', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(63, 'ISNM/0063/24', NULL, NULL, 'UACE/DNM/0063', 'Jane', 'Nakamya', NULL, 'Sarah Nanteza', 'student63@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-705229417', '+256-705229417', 'Diploma in Nursing', 'Diploma in Nursing', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(64, 'ISNM/0064/24', NULL, NULL, 'UACE/DNM/0064', 'Moses', 'Nanteza', NULL, 'Grace Ochieng', 'student64@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-775654586', '+256-775654586', 'Diploma in Nursing', 'Diploma in Nursing', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Kamuli', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(65, 'ISNM/0065/24', NULL, NULL, 'UACE/DNM/0065', 'Esther', 'Wasswa', NULL, 'Mary Kizza', 'student65@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-781748308', '+256-781748308', 'Diploma in Nursing', 'Diploma in Nursing', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Kamuli', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(66, 'ISNM/0066/24', NULL, NULL, 'UACE/DNM/0066', 'Sarah', 'Ssenyonjo', NULL, 'Alice Kizza', 'student66@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-700988607', '+256-700988607', 'Diploma in Nursing', 'Diploma in Nursing', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Male', 'Ugandan', 'Iganga', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(67, 'ISNM/0067/24', NULL, NULL, 'UACE/DNM/0067', 'Faith', 'Mukasa', NULL, 'Esther Lubega', 'student67@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-770387594', '+256-770387594', 'Diploma in Nursing', 'Diploma in Nursing', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Kamuli', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(68, 'ISNM/0068/24', NULL, NULL, 'UACE/DNM/0068', 'Jane', 'Muwonge', NULL, 'John Mukasa', 'student68@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-771188826', '+256-771188826', 'Diploma in Nursing', 'Diploma in Nursing', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Kamuli', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(69, 'ISNM/0069/24', NULL, NULL, 'UACE/DNM/0069', 'Samuel', 'Okello', NULL, 'Ruth Ochieng', 'student69@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-783249504', '+256-783249504', 'Diploma in Nursing', 'Diploma in Nursing', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(70, 'ISNM/0070/24', NULL, NULL, 'UACE/DNM/0070', 'Peace', 'Wasswa', NULL, 'Peter Kintu', 'student70@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-707752690', '+256-707752690', 'Diploma in Nursing', 'Diploma in Nursing', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(71, 'ISNM/0071/24', NULL, NULL, 'UACE/DMM/0071', 'David', 'Muwonge', NULL, 'Alice Lubega', 'student71@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-780560207', '+256-780560207', 'Diploma in Midwifery', 'Diploma in Midwifery', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(72, 'ISNM/0072/24', NULL, NULL, 'UACE/DMM/0072', 'Joy', 'Lubega', NULL, 'Faith Wasswa', 'student72@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-781327322', '+256-781327322', 'Diploma in Midwifery', 'Diploma in Midwifery', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Male', 'Ugandan', 'Mayuge', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(73, 'ISNM/0073/24', NULL, NULL, 'UACE/DMM/0073', 'Peace', 'Okello', NULL, 'Mary Sserwadda', 'student73@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-777382056', '+256-777382056', 'Diploma in Midwifery', 'Diploma in Midwifery', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(74, 'ISNM/0074/24', NULL, NULL, 'UACE/DMM/0074', 'Grace', 'Namukwaya', NULL, 'Peace Sserwadda', 'student74@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-774208337', '+256-774208337', 'Diploma in Midwifery', 'Diploma in Midwifery', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Iganga', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(75, 'ISNM/0075/24', NULL, NULL, 'UACE/DMM/0075', 'Esther', 'Sserwadda', NULL, 'Samuel Nakamya', 'student75@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-774107687', '+256-774107687', 'Diploma in Midwifery', 'Diploma in Midwifery', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(76, 'ISNM/0076/24', NULL, NULL, 'UACE/DMM/0076', 'Faith', 'Nakamya', NULL, 'Faith Muwonge', 'student76@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-786791936', '+256-786791936', 'Diploma in Midwifery', 'Diploma in Midwifery', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(77, 'ISNM/0077/24', NULL, NULL, 'UACE/DMM/0077', 'Mary', 'Ssenyonjo', NULL, 'David Kizza', 'student77@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-780318876', '+256-780318876', 'Diploma in Midwifery', 'Diploma in Midwifery', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(78, 'ISNM/0078/24', NULL, NULL, 'UACE/DMM/0078', 'Esther', 'Sserwadda', NULL, 'John Kizza', 'student78@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-702962891', '+256-702962891', 'Diploma in Midwifery', 'Diploma in Midwifery', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Male', 'Ugandan', 'Iganga', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(79, 'ISNM/0079/24', NULL, NULL, 'UACE/DMM/0079', 'Mary', 'Kintu', NULL, 'Esther Kintu', 'student79@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-704378691', '+256-704378691', 'Diploma in Midwifery', 'Diploma in Midwifery', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(80, 'ISNM/0080/24', NULL, NULL, 'UACE/DMM/0080', 'John', 'Ochieng', NULL, 'Peter Ssenyonjo', 'student80@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-770329121', '+256-770329121', 'Diploma in Midwifery', 'Diploma in Midwifery', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(81, 'ISNM/0081/24', NULL, NULL, 'UACE/DMM/0081', 'Faith', 'Namukwaya', NULL, 'Mary Ochieng', 'student81@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-780482903', '+256-780482903', 'Diploma in Midwifery', 'Diploma in Midwifery', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Kamuli', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(82, 'ISNM/0082/24', NULL, NULL, 'UACE/DMM/0082', 'Alice', 'Kintu', NULL, 'Peace Nakato', 'student82@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-775910832', '+256-775910832', 'Diploma in Midwifery', 'Diploma in Midwifery', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(83, 'ISNM/0083/24', NULL, NULL, 'UACE/DMM/0083', 'Peace', 'Muwonge', NULL, 'Peter Kizza', 'student83@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-779177049', '+256-779177049', 'Diploma in Midwifery', 'Diploma in Midwifery', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Iganga', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(84, 'ISNM/0084/24', NULL, NULL, 'UACE/DMM/0084', 'Mary', 'Sserwadda', NULL, 'Faith Wasswa', 'student84@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-782343559', '+256-782343559', 'Diploma in Midwifery', 'Diploma in Midwifery', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Male', 'Ugandan', 'Jinja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(85, 'ISNM/0085/24', NULL, NULL, 'UACE/DMM/0085', 'Peace', 'Sserwadda', NULL, 'Mary Muwonge', 'student85@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-700702837', '+256-700702837', 'Diploma in Midwifery', 'Diploma in Midwifery', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Iganga', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(86, 'ISNM/0086/24', NULL, NULL, 'UACE/DMM/0086', 'Moses', 'Nabirye', NULL, 'Ruth Namukwaya', 'student86@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-705633283', '+256-705633283', 'Diploma in Midwifery', 'Diploma in Midwifery', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Kamuli', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(87, 'ISNM/0087/24', NULL, NULL, 'UACE/DMM/0087', 'Grace', 'Nabirye', NULL, 'Mary Muwonge', 'student87@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-709698010', '+256-709698010', 'Diploma in Midwifery', 'Diploma in Midwifery', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Iganga', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(88, 'ISNM/0088/24', NULL, NULL, 'UACE/DMM/0088', 'Alice', 'Nabirye', NULL, 'Grace Namukwaya', 'student88@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-774207258', '+256-774207258', 'Diploma in Midwifery', 'Diploma in Midwifery', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Jinja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(89, 'ISNM/0089/24', NULL, NULL, 'UACE/DMM/0089', 'Alice', 'Namukwaya', NULL, 'Alice Muwonge', 'student89@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-779644139', '+256-779644139', 'Diploma in Midwifery', 'Diploma in Midwifery', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(90, 'ISNM/0090/24', NULL, NULL, 'UACE/DMM/0090', 'Faith', 'Nakato', NULL, 'David Nakato', 'student90@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-701964811', '+256-701964811', 'Diploma in Midwifery', 'Diploma in Midwifery', NULL, 2, NULL, 2, 'Diploma', NULL, NULL, NULL, NULL, 'Male', 'Ugandan', 'Mayuge', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(91, 'ISNM/0091/23', NULL, NULL, 'UACE/DNE/0091', 'Peter', 'Kintu', NULL, 'Sarah Nanteza', 'student91@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-707456515', '+256-707456515', 'Diploma in Nursing Education', 'Diploma in Nursing Education', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(92, 'ISNM/0092/23', NULL, NULL, 'UACE/DNE/0092', 'Sarah', 'Ssenyonjo', NULL, 'Jane Nanteza', 'student92@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-703110553', '+256-703110553', 'Diploma in Nursing Education', 'Diploma in Nursing Education', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(93, 'ISNM/0093/23', NULL, NULL, 'UACE/DNE/0093', 'John', 'Namukwaya', NULL, 'Peace Okello', 'student93@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-706268467', '+256-706268467', 'Diploma in Nursing Education', 'Diploma in Nursing Education', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Iganga', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(94, 'ISNM/0094/23', NULL, NULL, 'UACE/DNE/0094', 'Grace', 'Ssenyonjo', NULL, 'Grace Ssenyonjo', 'student94@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-707729037', '+256-707729037', 'Diploma in Nursing Education', 'Diploma in Nursing Education', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Mayuge', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July');
INSERT INTO `students` (`id`, `student_number`, `registration_number`, `national_student_id_number`, `index_number`, `first_name`, `surname`, `other_name`, `full_name`, `email`, `password`, `phone`, `mobile_number`, `program`, `course`, `course_codes`, `current_year`, `year_of_study`, `year`, `level`, `set_name`, `current_semester`, `intake_date`, `date_of_birth`, `gender`, `nationality`, `address`, `district`, `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_email`, `sponsor`, `marital_status`, `religion`, `student_category`, `guardian_name`, `guardian_phone`, `guardian_email`, `profile_picture`, `passport_photo`, `status`, `last_login`, `locked_until`, `login_attempts`, `password_changed`, `is_first_login`, `created_at`, `updated_at`, `intake_year`, `intake_period`) VALUES
(95, 'ISNM/0095/23', NULL, NULL, 'UACE/DNE/0095', 'Ruth', 'Muwonge', NULL, 'Peter Wasswa', 'student95@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-702229232', '+256-702229232', 'Diploma in Nursing Education', 'Diploma in Nursing Education', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Iganga', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(96, 'ISNM/0096/23', NULL, NULL, 'UACE/DNE/0096', 'Samuel', 'Nabirye', NULL, 'David Muwonge', 'student96@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-775787748', '+256-775787748', 'Diploma in Nursing Education', 'Diploma in Nursing Education', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Male', 'Ugandan', 'Kamuli', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(97, 'ISNM/0097/23', NULL, NULL, 'UACE/DNE/0097', 'Esther', 'Kizza', NULL, 'Sarah Okello', 'student97@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-709144794', '+256-709144794', 'Diploma in Nursing Education', 'Diploma in Nursing Education', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Iganga', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(98, 'ISNM/0098/23', NULL, NULL, 'UACE/DNE/0098', 'Alice', 'Namukwaya', NULL, 'Jane Mukasa', 'student98@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-770803830', '+256-770803830', 'Diploma in Nursing Education', 'Diploma in Nursing Education', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(99, 'ISNM/0099/23', NULL, NULL, 'UACE/DNE/0099', 'Joy', 'Kintu', NULL, 'Grace Lubega', 'student99@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-778936284', '+256-778936284', 'Diploma in Nursing Education', 'Diploma in Nursing Education', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(100, 'ISNM/0100/23', NULL, NULL, 'UACE/DNE/0100', 'Moses', 'Namukwaya', NULL, 'John Namukwaya', 'student100@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-777276039', '+256-777276039', 'Diploma in Nursing Education', 'Diploma in Nursing Education', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Kamuli', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(101, 'ISNM/0101/23', NULL, NULL, 'UACE/DNE/0101', 'Mary', 'Nakamya', NULL, 'Peace Lubega', 'student101@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-778611329', '+256-778611329', 'Diploma in Nursing Education', 'Diploma in Nursing Education', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Mayuge', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(102, 'ISNM/0102/23', NULL, NULL, 'UACE/DNE/0102', 'Peace', 'Okello', NULL, 'Esther Nabirye', 'student102@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-789652358', '+256-789652358', 'Diploma in Nursing Education', 'Diploma in Nursing Education', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Male', 'Ugandan', 'Jinja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(103, 'ISNM/0103/23', NULL, NULL, 'UACE/DNE/0103', 'Peace', 'Nanteza', NULL, 'Jane Nabirye', 'student103@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-774669700', '+256-774669700', 'Diploma in Nursing Education', 'Diploma in Nursing Education', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Jinja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(104, 'ISNM/0104/23', NULL, NULL, 'UACE/DNE/0104', 'Sarah', 'Nakato', NULL, 'Joy Wasswa', 'student104@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-706474214', '+256-706474214', 'Diploma in Nursing Education', 'Diploma in Nursing Education', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(105, 'ISNM/0105/23', NULL, NULL, 'UACE/DNE/0105', 'Ruth', 'Namukwaya', NULL, 'Samuel Ssenyonjo', 'student105@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-785793679', '+256-785793679', 'Diploma in Nursing Education', 'Diploma in Nursing Education', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Jinja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(106, 'ISNM/0106/23', NULL, NULL, 'UACE/DNE/0106', 'Peter', 'Lubega', NULL, 'Mary Ochieng', 'student106@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-787326480', '+256-787326480', 'Diploma in Nursing Education', 'Diploma in Nursing Education', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Jinja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(107, 'ISNM/0107/23', NULL, NULL, 'UACE/DNE/0107', 'Peace', 'Nanteza', NULL, 'Sarah Ochieng', 'student107@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-708782505', '+256-708782505', 'Diploma in Nursing Education', 'Diploma in Nursing Education', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Mayuge', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(108, 'ISNM/0108/23', NULL, NULL, 'UACE/DNE/0108', 'Peace', 'Wasswa', NULL, 'Grace Wasswa', 'student108@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-773306947', '+256-773306947', 'Diploma in Nursing Education', 'Diploma in Nursing Education', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Male', 'Ugandan', 'Mayuge', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(109, 'ISNM/0109/23', NULL, NULL, 'UACE/DNE/0109', 'Mary', 'Okello', NULL, 'Joy Ochieng', 'student109@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-788873342', '+256-788873342', 'Diploma in Nursing Education', 'Diploma in Nursing Education', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Iganga', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(110, 'ISNM/0110/23', NULL, NULL, 'UACE/DNE/0110', 'David', 'Ssenyonjo', NULL, 'Sarah Nakato', 'student110@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-774319221', '+256-774319221', 'Diploma in Nursing Education', 'Diploma in Nursing Education', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Kamuli', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(111, 'ISNM/0111/23', NULL, NULL, 'UACE/BNM/0111', 'Faith', 'Kizza', NULL, 'David Okello', 'student111@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-770352439', '+256-770352439', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(112, 'ISNM/0112/23', NULL, NULL, 'UACE/BNM/0112', 'Esther', 'Wasswa', NULL, 'Jane Kintu', 'student112@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-787234563', '+256-787234563', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Kamuli', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(113, 'ISNM/0113/23', NULL, NULL, 'UACE/BNM/0113', 'John', 'Mukasa', NULL, 'Peace Namukwaya', 'student113@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-788968397', '+256-788968397', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Mayuge', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(114, 'ISNM/0114/23', NULL, NULL, 'UACE/BNM/0114', 'Joy', 'Lubega', NULL, 'David Wasswa', 'student114@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-770111618', '+256-770111618', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Male', 'Ugandan', 'Iganga', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(115, 'ISNM/0115/23', NULL, NULL, 'UACE/BNM/0115', 'Esther', 'Muwonge', NULL, 'Alice Muwonge', 'student115@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-773868815', '+256-773868815', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Jinja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(116, 'ISNM/0116/23', NULL, NULL, 'UACE/BNM/0116', 'Alice', 'Nakato', NULL, 'Sarah Kizza', 'student116@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-703897961', '+256-703897961', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(117, 'ISNM/0117/23', NULL, NULL, 'UACE/BNM/0117', 'Esther', 'Kizza', NULL, 'David Okello', 'student117@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-782735922', '+256-782735922', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Mayuge', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(118, 'ISNM/0118/23', NULL, NULL, 'UACE/BNM/0118', 'Ruth', 'Kintu', NULL, 'Sarah Nabirye', 'student118@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-779544120', '+256-779544120', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(119, 'ISNM/0119/23', NULL, NULL, 'UACE/BNM/0119', 'Ruth', 'Nakato', NULL, 'Faith Ssenyonjo', 'student119@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-788750458', '+256-788750458', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Jinja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(120, 'ISNM/0120/23', NULL, NULL, 'UACE/BNM/0120', 'John', 'Ssenyonjo', NULL, 'Jane Nakamya', 'student120@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-708298256', '+256-708298256', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Male', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(121, 'ISNM/0121/23', NULL, NULL, 'UACE/BNM/0121', 'Mary', 'Kizza', NULL, 'Samuel Lubega', 'student121@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-776638003', '+256-776638003', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Jinja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(122, 'ISNM/0122/23', NULL, NULL, 'UACE/BNM/0122', 'Samuel', 'Mukasa', NULL, 'Grace Kizza', 'student122@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-702398474', '+256-702398474', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Kamuli', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(123, 'ISNM/0123/23', NULL, NULL, 'UACE/BNM/0123', 'Sarah', 'Kintu', NULL, 'David Namukwaya', 'student123@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-773040163', '+256-773040163', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(124, 'ISNM/0124/23', NULL, NULL, 'UACE/BNM/0124', 'Mary', 'Lubega', NULL, 'Alice Muwonge', 'student124@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-776200061', '+256-776200061', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Iganga', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(125, 'ISNM/0125/23', NULL, NULL, 'UACE/BNM/0125', 'Esther', 'Muwonge', NULL, 'Ruth Nabirye', 'student125@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-783854961', '+256-783854961', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Kamuli', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(126, 'ISNM/0126/23', NULL, NULL, 'UACE/BNM/0126', 'Jane', 'Okello', NULL, 'Peter Namukwaya', 'student126@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-780195603', '+256-780195603', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Male', 'Ugandan', 'Jinja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(127, 'ISNM/0127/23', NULL, NULL, 'UACE/BNM/0127', 'John', 'Wasswa', NULL, 'John Nabirye', 'student127@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-700147629', '+256-700147629', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Jinja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(128, 'ISNM/0128/23', NULL, NULL, 'UACE/BNM/0128', 'Esther', 'Nakamya', NULL, 'Peace Nanteza', 'student128@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-703247691', '+256-703247691', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Iganga', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(129, 'ISNM/0129/23', NULL, NULL, 'UACE/BNM/0129', 'Sarah', 'Namukwaya', NULL, 'John Nakato', 'student129@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-705370294', '+256-705370294', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Kamuli', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(130, 'ISNM/0130/23', NULL, NULL, 'UACE/BNM/0130', 'Ruth', 'Namukwaya', NULL, 'Peter Nanteza', 'student130@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-773191526', '+256-773191526', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Jinja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(131, 'ISNM/0131/23', NULL, NULL, 'UACE/BNM/0131', 'John', 'Ochieng', NULL, 'Jane Ochieng', 'student131@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-779818316', '+256-779818316', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Kamuli', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(132, 'ISNM/0132/23', NULL, NULL, 'UACE/BNM/0132', 'Mary', 'Mukasa', NULL, 'Sarah Kintu', 'student132@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-789279968', '+256-789279968', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Male', 'Ugandan', 'Jinja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(133, 'ISNM/0133/23', NULL, NULL, 'UACE/BNM/0133', 'Sarah', 'Namukwaya', NULL, 'Moses Ssenyonjo', 'student133@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-776894125', '+256-776894125', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Iganga', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(134, 'ISNM/0134/23', NULL, NULL, 'UACE/BNM/0134', 'Peter', 'Ochieng', NULL, 'John Okello', 'student134@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-788814668', '+256-788814668', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(135, 'ISNM/0135/23', NULL, NULL, 'UACE/BNM/0135', 'Samuel', 'Kintu', NULL, 'Sarah Mukasa', 'student135@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-787082209', '+256-787082209', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Jinja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(136, 'ISNM/0136/23', NULL, NULL, 'UACE/BNM/0136', 'Sarah', 'Kizza', NULL, 'Alice Kizza', 'student136@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-772069777', '+256-772069777', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Mayuge', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(137, 'ISNM/0137/23', NULL, NULL, 'UACE/BNM/0137', 'Ruth', 'Wasswa', NULL, 'Sarah Nakato', 'student137@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-776502037', '+256-776502037', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Kamuli', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(138, 'ISNM/0138/23', NULL, NULL, 'UACE/BNM/0138', 'Faith', 'Wasswa', NULL, 'Ruth Lubega', 'student138@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-771525324', '+256-771525324', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Male', 'Ugandan', 'Kamuli', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(139, 'ISNM/0139/23', NULL, NULL, 'UACE/BNM/0139', 'Grace', 'Sserwadda', NULL, 'David Lubega', 'student139@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-789051629', '+256-789051629', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(140, 'ISNM/0140/23', NULL, NULL, 'UACE/BNM/0140', 'Moses', 'Nabirye', NULL, 'Mary Kintu', 'student140@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-708857305', '+256-708857305', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Mayuge', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(141, 'ISNM/0141/23', NULL, NULL, 'UACE/BNM/0141', 'Sarah', 'Sserwadda', NULL, 'Esther Nakato', 'student141@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-702819948', '+256-702819948', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Jinja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(142, 'ISNM/0142/23', NULL, NULL, 'UACE/BNM/0142', 'John', 'Ssenyonjo', NULL, 'Ruth Muwonge', 'student142@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-707780517', '+256-707780517', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Kamuli', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(143, 'ISNM/0143/23', NULL, NULL, 'UACE/BNM/0143', 'Faith', 'Okello', NULL, 'Ruth Okello', 'student143@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-709800177', '+256-709800177', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(144, 'ISNM/0144/23', NULL, NULL, 'UACE/BNM/0144', 'Esther', 'Wasswa', NULL, 'Jane Nabirye', 'student144@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-777854116', '+256-777854116', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Male', 'Ugandan', 'Kamuli', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(145, 'ISNM/0145/23', NULL, NULL, 'UACE/BNM/0145', 'Sarah', 'Kintu', NULL, 'Peter Muwonge', 'student145@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-783672096', '+256-783672096', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Iganga', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(146, 'ISNM/0146/23', NULL, NULL, 'UACE/BNM/0146', 'Mary', 'Ssenyonjo', NULL, 'David Kizza', 'student146@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-789642933', '+256-789642933', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Iganga', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(147, 'ISNM/0147/23', NULL, NULL, 'UACE/BNM/0147', 'Sarah', 'Okello', NULL, 'Sarah Sserwadda', 'student147@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-789421624', '+256-789421624', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Jinja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(148, 'ISNM/0148/23', NULL, NULL, 'UACE/BNM/0148', 'Ruth', 'Ochieng', NULL, 'Peace Nanteza', 'student148@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-708379498', '+256-708379498', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Mayuge', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(149, 'ISNM/0149/23', NULL, NULL, 'UACE/BNM/0149', 'Peter', 'Nanteza', NULL, 'Esther Nanteza', 'student149@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-787109143', '+256-787109143', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Female', 'Ugandan', 'Bugiri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(150, 'ISNM/0150/23', NULL, NULL, 'UACE/BNM/0150', 'Mary', 'Lubega', NULL, 'Ruth Nakamya', 'student150@isnm.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '+256-779197463', '+256-779197463', 'Bachelor of Science in Nursing', 'Bachelor of Science in Nursing', NULL, 3, NULL, 3, 'Degree', NULL, NULL, NULL, NULL, 'Male', 'Ugandan', 'Jinja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 0, 1, '2026-07-03 04:51:13', '2026-07-16 19:52:40', NULL, 'July'),
(151, 'STU202600001', 'REG-2026-001', NULL, 'CM-2026-001', 'John', 'Okello', 'James', 'John James Okello', 'john.okello@student.isnm.ac.ug', '$2y$10$HxVkw2ihQPBwiK/fXa9Lqezmnw8KmKVSOMVGXfUynT09hDxYbadQe', '0770000001', '0770000001', 'Bachelor of Science in Nursing (Comprehensive)', 'BSc Nursing', NULL, 1, NULL, 1, 'Year 1', '1', NULL, '2026-01-15', NULL, 'Male', 'Ugandan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 1, 0, '2026-07-16 19:52:40', '2026-07-16 19:52:40', '2026', 'January'),
(152, 'STU202600002', 'REG-2026-002', NULL, 'MID-2026-001', 'Grace', 'Nambi', '', 'Grace Nambi', 'grace.nambi@student.isnm.ac.ug', '$2y$10$HxVkw2ihQPBwiK/fXa9Lqezmnw8KmKVSOMVGXfUynT09hDxYbadQe', '0770000002', '0770000002', 'Bachelor of Science in Midwifery', 'BSc Midwifery', NULL, 1, NULL, 1, 'Year 1', '2', NULL, '2026-01-15', NULL, 'Female', 'Ugandan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 1, 0, '2026-07-16 19:52:40', '2026-07-16 19:52:40', '2026', 'January'),
(153, 'STU202600003', 'REG-2026-003', NULL, 'DIP-2026-001', 'Samuel', 'Mugisha', 'Peter', 'Samuel Peter Mugisha', 'samuel.mugisha@student.isnm.ac.ug', '$2y$10$HxVkw2ihQPBwiK/fXa9Lqezmnw8KmKVSOMVGXfUynT09hDxYbadQe', '0770000003', '0770000003', 'Diploma in Nursing/Midwifery', 'Dip Nursing', NULL, 1, NULL, 1, 'Year 1', '3', NULL, '2026-01-15', NULL, 'Male', 'Ugandan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 1, 0, '2026-07-16 19:52:40', '2026-07-16 19:52:40', '2026', 'January'),
(154, 'STU202600001', 'REG-2026-001', NULL, 'CM-2026-001', 'John', 'Okello', 'James', 'John James Okello', 'john.okello@student.isnm.ac.ug', '$2y$10$HxVkw2ihQPBwiK/fXa9Lqezmnw8KmKVSOMVGXfUynT09hDxYbadQe', '0770000001', '0770000001', 'Bachelor of Science in Nursing (Comprehensive)', 'BSc Nursing', NULL, 1, NULL, 1, 'Year 1', '1', NULL, '2026-01-15', NULL, 'Male', 'Ugandan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 1, 0, '2026-07-16 19:52:51', '2026-07-16 19:52:51', '2026', 'January'),
(155, 'STU202600002', 'REG-2026-002', NULL, 'MID-2026-001', 'Grace', 'Nambi', '', 'Grace Nambi', 'grace.nambi@student.isnm.ac.ug', '$2y$10$HxVkw2ihQPBwiK/fXa9Lqezmnw8KmKVSOMVGXfUynT09hDxYbadQe', '0770000002', '0770000002', 'Bachelor of Science in Midwifery', 'BSc Midwifery', NULL, 1, NULL, 1, 'Year 1', '2', NULL, '2026-01-15', NULL, 'Female', 'Ugandan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 1, 0, '2026-07-16 19:52:51', '2026-07-16 19:52:51', '2026', 'January'),
(156, 'STU202600003', 'REG-2026-003', NULL, 'DIP-2026-001', 'Samuel', 'Mugisha', 'Peter', 'Samuel Peter Mugisha', 'samuel.mugisha@student.isnm.ac.ug', '$2y$10$HxVkw2ihQPBwiK/fXa9Lqezmnw8KmKVSOMVGXfUynT09hDxYbadQe', '0770000003', '0770000003', 'Diploma in Nursing/Midwifery', 'Dip Nursing', NULL, 1, NULL, 1, 'Year 1', '3', NULL, '2026-01-15', NULL, 'Male', 'Ugandan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 1, 0, '2026-07-16 19:52:51', '2026-07-16 19:52:51', '2026', 'January'),
(157, 'STU202600001', 'REG-2026-001', NULL, 'CM-2026-001', 'John', 'Okello', 'James', 'John James Okello', 'john.okello@student.isnm.ac.ug', '$2y$10$HxVkw2ihQPBwiK/fXa9Lqezmnw8KmKVSOMVGXfUynT09hDxYbadQe', '0770000001', '0770000001', 'Bachelor of Science in Nursing (Comprehensive)', 'BSc Nursing', NULL, 1, NULL, 1, 'Year 1', '1', NULL, '2026-01-15', NULL, 'Male', 'Ugandan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 1, 0, '2026-07-16 19:53:02', '2026-07-16 19:53:02', '2026', 'January'),
(158, 'STU202600002', 'REG-2026-002', NULL, 'MID-2026-001', 'Grace', 'Nambi', '', 'Grace Nambi', 'grace.nambi@student.isnm.ac.ug', '$2y$10$HxVkw2ihQPBwiK/fXa9Lqezmnw8KmKVSOMVGXfUynT09hDxYbadQe', '0770000002', '0770000002', 'Bachelor of Science in Midwifery', 'BSc Midwifery', NULL, 1, NULL, 1, 'Year 1', '2', NULL, '2026-01-15', NULL, 'Female', 'Ugandan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 1, 0, '2026-07-16 19:53:02', '2026-07-16 19:53:02', '2026', 'January'),
(159, 'STU202600003', 'REG-2026-003', NULL, 'DIP-2026-001', 'Samuel', 'Mugisha', 'Peter', 'Samuel Peter Mugisha', 'samuel.mugisha@student.isnm.ac.ug', '$2y$10$HxVkw2ihQPBwiK/fXa9Lqezmnw8KmKVSOMVGXfUynT09hDxYbadQe', '0770000003', '0770000003', 'Diploma in Nursing/Midwifery', 'Dip Nursing', NULL, 1, NULL, 1, 'Year 1', '3', NULL, '2026-01-15', NULL, 'Male', 'Ugandan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 1, 0, '2026-07-16 19:53:02', '2026-07-16 19:53:02', '2026', 'January'),
(160, 'STU202600001', 'REG-2026-001', NULL, 'CM-2026-001', 'John', 'Okello', 'James', 'John James Okello', 'john.okello@student.isnm.ac.ug', '$2y$10$HxVkw2ihQPBwiK/fXa9Lqezmnw8KmKVSOMVGXfUynT09hDxYbadQe', '0770000001', '0770000001', 'Bachelor of Science in Nursing (Comprehensive)', 'BSc Nursing', NULL, 1, NULL, 1, 'Year 1', '1', NULL, '2026-01-15', NULL, 'Male', 'Ugandan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 1, 0, '2026-07-16 19:53:14', '2026-07-16 19:53:14', '2026', 'January'),
(161, 'STU202600002', 'REG-2026-002', NULL, 'MID-2026-001', 'Grace', 'Nambi', '', 'Grace Nambi', 'grace.nambi@student.isnm.ac.ug', '$2y$10$HxVkw2ihQPBwiK/fXa9Lqezmnw8KmKVSOMVGXfUynT09hDxYbadQe', '0770000002', '0770000002', 'Bachelor of Science in Midwifery', 'BSc Midwifery', NULL, 1, NULL, 1, 'Year 1', '2', NULL, '2026-01-15', NULL, 'Female', 'Ugandan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 1, 0, '2026-07-16 19:53:14', '2026-07-16 19:53:14', '2026', 'January'),
(162, 'STU202600003', 'REG-2026-003', NULL, 'DIP-2026-001', 'Samuel', 'Mugisha', 'Peter', 'Samuel Peter Mugisha', 'samuel.mugisha@student.isnm.ac.ug', '$2y$10$HxVkw2ihQPBwiK/fXa9Lqezmnw8KmKVSOMVGXfUynT09hDxYbadQe', '0770000003', '0770000003', 'Diploma in Nursing/Midwifery', 'Dip Nursing', NULL, 1, NULL, 1, 'Year 1', '3', NULL, '2026-01-15', NULL, 'Male', 'Ugandan', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Active', NULL, NULL, 0, 1, 0, '2026-07-16 19:53:14', '2026-07-16 19:53:14', '2026', 'January');

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

CREATE TABLE `students_trash` (
  `id` int(11) NOT NULL,
  `original_id` int(11) NOT NULL,
  `student_number` varchar(50) DEFAULT NULL,
  `full_name` varchar(300) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `course` varchar(100) DEFAULT NULL,
  `snapshot_data` longtext DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT current_timestamp(),
  `restored_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_academic_profiles`
--

CREATE TABLE `student_academic_profiles` (
  `id` int(11) NOT NULL,
  `student_number` varchar(50) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `program` varchar(255) DEFAULT NULL,
  `academic_year` year(4) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `status` enum('Active','Completed','Dropped','Transferred') NOT NULL DEFAULT 'Active',
  `gpa` decimal(4,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_academic_profiles`
--

INSERT INTO `student_academic_profiles` (`id`, `student_number`, `full_name`, `program`, `academic_year`, `semester`, `status`, `gpa`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 'STU202644246', 'Daniel Kizza', 'Diploma in Nursing', '2026', NULL, 'Active', NULL, NULL, '2026-07-05 17:36:37', '2026-07-05 17:36:37');

-- --------------------------------------------------------

--
-- Table structure for table `student_academic_records`
--

CREATE TABLE `student_academic_records` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `grade` varchar(10) DEFAULT NULL,
  `marks` decimal(5,2) DEFAULT NULL,
  `credits` decimal(3,1) DEFAULT NULL,
  `gpa` decimal(3,2) DEFAULT NULL,
  `cgpa` decimal(3,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_academic_records`
--

INSERT INTO `student_academic_records` (`id`, `student_id`, `semester`, `academic_year`, `subject`, `course_code`, `grade`, `marks`, `credits`, `gpa`, `cgpa`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 67.73, 4.0, 3.05, 2.88, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(2, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B', 63.05, 4.0, 2.52, 3.77, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(3, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B', 83.82, 4.0, 3.50, 3.32, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(4, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 60.72, 4.0, 3.84, 3.14, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(5, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 98.97, 4.0, 3.27, 3.48, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(6, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 86.91, 4.0, 2.77, 3.83, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(7, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 92.35, 4.0, 3.04, 3.07, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(8, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 98.26, 4.0, 2.98, 3.62, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(9, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 83.28, 4.0, 3.42, 3.00, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(10, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 64.76, 4.0, 2.68, 2.87, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(11, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 86.90, 4.0, 3.56, 3.30, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(12, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 60.79, 4.0, 3.30, 3.40, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(13, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 70.91, 4.0, 2.67, 3.64, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(14, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 98.07, 4.0, 3.13, 2.89, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(15, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B', 85.71, 4.0, 2.78, 2.50, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(16, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 69.05, 4.0, 3.69, 2.94, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(17, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B+', 82.79, 4.0, 2.72, 2.52, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(18, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 62.61, 4.0, 3.20, 2.72, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(19, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 69.36, 4.0, 2.74, 2.66, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(20, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B+', 84.20, 4.0, 2.74, 3.99, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(21, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 74.84, 4.0, 3.18, 2.71, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(22, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 71.25, 4.0, 3.08, 2.65, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(23, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 75.29, 4.0, 3.85, 3.03, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(24, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B', 73.69, 4.0, 3.65, 3.73, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(25, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 77.70, 4.0, 3.81, 2.58, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(26, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 61.17, 4.0, 2.85, 2.62, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(27, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 67.75, 4.0, 3.87, 3.98, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(28, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B', 87.15, 4.0, 2.82, 2.57, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(29, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 91.30, 4.0, 2.74, 3.17, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(30, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 78.21, 4.0, 4.00, 3.44, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(31, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B+', 76.26, 4.0, 3.64, 3.39, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(32, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 84.27, 4.0, 2.50, 2.79, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(33, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 66.67, 4.0, 3.99, 3.18, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(34, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B', 83.69, 4.0, 3.53, 3.47, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(35, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B+', 62.91, 4.0, 3.42, 3.78, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(36, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 82.36, 4.0, 3.29, 3.91, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(37, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B+', 93.51, 4.0, 3.47, 3.57, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(38, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 98.36, 4.0, 3.91, 3.74, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(39, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 62.17, 4.0, 3.03, 3.40, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(40, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 98.15, 4.0, 3.87, 3.55, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(41, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 89.61, 4.0, 3.09, 3.63, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(42, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 85.72, 4.0, 3.21, 3.16, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(43, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 82.44, 4.0, 3.22, 3.56, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(44, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B', 72.86, 4.0, 3.49, 3.02, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(45, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 85.77, 4.0, 2.52, 2.72, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(46, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 97.16, 4.0, 3.44, 3.04, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(47, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 79.86, 4.0, 3.60, 2.78, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(48, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 61.97, 4.0, 2.62, 2.90, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(49, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B+', 96.02, 4.0, 3.41, 3.00, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(50, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 66.73, 4.0, 3.02, 2.84, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(51, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B+', 84.62, 4.0, 3.62, 3.81, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(52, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B', 91.84, 4.0, 3.80, 3.92, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(53, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B+', 81.34, 4.0, 2.99, 2.55, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(54, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B+', 75.76, 4.0, 3.43, 3.86, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(55, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 88.05, 4.0, 3.18, 2.73, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(56, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 85.00, 4.0, 3.81, 3.22, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(57, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 81.95, 4.0, 3.02, 2.62, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(58, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 84.95, 4.0, 3.99, 2.67, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(59, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 80.46, 4.0, 3.77, 3.57, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(60, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B+', 80.26, 4.0, 3.72, 3.33, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(61, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 99.34, 4.0, 3.89, 3.54, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(62, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 72.34, 4.0, 3.27, 3.44, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(63, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 66.37, 4.0, 3.95, 3.06, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(64, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 85.05, 4.0, 2.93, 3.34, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(65, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 97.63, 4.0, 3.91, 3.80, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(66, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 98.49, 4.0, 2.92, 3.25, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(67, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 93.10, 4.0, 2.71, 2.84, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(68, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 95.07, 4.0, 2.87, 3.42, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(69, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 89.27, 4.0, 3.58, 3.10, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(70, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 60.34, 4.0, 3.28, 3.34, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(71, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B+', 68.72, 4.0, 2.95, 3.75, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(72, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B+', 83.42, 4.0, 2.92, 3.45, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(73, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 89.66, 4.0, 3.59, 3.10, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(74, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 95.42, 4.0, 3.97, 2.86, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(75, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B+', 74.62, 4.0, 3.88, 3.24, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(76, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 61.86, 4.0, 2.68, 3.19, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(77, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 75.10, 4.0, 2.54, 2.51, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(78, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 89.92, 4.0, 3.82, 2.71, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(79, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B+', 80.61, 4.0, 3.60, 2.68, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(80, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 87.95, 4.0, 2.88, 2.75, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(81, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B+', 74.89, 4.0, 2.62, 2.94, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(82, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B', 70.51, 4.0, 3.67, 2.65, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(83, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B+', 69.45, 4.0, 3.30, 3.94, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(84, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B', 89.69, 4.0, 3.29, 3.14, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(85, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 74.61, 4.0, 2.87, 2.69, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(86, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 66.58, 4.0, 2.64, 3.95, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(87, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 94.93, 4.0, 3.56, 3.85, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(88, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 68.96, 4.0, 3.95, 2.75, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(89, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 64.40, 4.0, 3.68, 3.41, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(90, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 83.97, 4.0, 3.91, 3.84, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(91, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 84.49, 4.0, 2.62, 3.37, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(92, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 78.69, 4.0, 3.12, 3.51, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(93, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B+', 93.16, 4.0, 2.81, 3.30, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(94, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B+', 66.11, 4.0, 3.67, 3.15, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(95, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 95.92, 4.0, 3.95, 2.72, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(96, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 85.81, 4.0, 3.67, 3.95, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(97, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 84.21, 4.0, 3.28, 3.67, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(98, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 73.23, 4.0, 3.48, 2.93, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(99, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 76.66, 4.0, 3.58, 3.04, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(100, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 63.29, 4.0, 3.27, 2.99, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(101, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B+', 69.61, 4.0, 3.52, 3.51, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(102, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 87.52, 4.0, 3.11, 3.97, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(103, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 79.25, 4.0, 3.02, 2.95, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(104, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 74.57, 4.0, 3.20, 2.84, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(105, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 99.55, 4.0, 3.62, 3.64, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(106, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 83.44, 4.0, 2.81, 2.91, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(107, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 98.42, 4.0, 3.30, 3.66, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(108, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B+', 66.40, 4.0, 3.76, 3.56, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(109, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B+', 84.96, 4.0, 3.00, 3.67, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(110, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 70.20, 4.0, 3.27, 3.70, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(111, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 96.41, 4.0, 2.75, 2.64, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(112, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 83.67, 4.0, 2.55, 3.10, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(113, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 68.64, 4.0, 3.15, 3.30, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(114, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 66.01, 4.0, 3.56, 2.61, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(115, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B', 81.61, 4.0, 3.27, 3.92, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(116, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B', 71.22, 4.0, 3.80, 3.22, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(117, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 82.98, 4.0, 3.20, 3.41, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(118, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 74.11, 4.0, 3.79, 2.86, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(119, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 76.46, 4.0, 2.76, 3.47, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(120, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 84.07, 4.0, 3.82, 3.41, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(121, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 62.97, 4.0, 2.86, 3.95, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(122, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B+', 74.64, 4.0, 3.36, 3.65, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(123, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B', 95.99, 4.0, 3.65, 2.68, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(124, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 67.71, 4.0, 2.55, 3.37, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(125, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 70.63, 4.0, 3.89, 3.76, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(126, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 81.78, 4.0, 3.23, 3.69, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(127, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 67.98, 4.0, 3.17, 3.46, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(128, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 72.82, 4.0, 2.59, 3.03, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(129, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 91.66, 4.0, 2.87, 3.81, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(130, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 79.89, 4.0, 3.42, 3.37, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(131, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B', 72.60, 4.0, 2.66, 3.38, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(132, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 72.26, 4.0, 3.54, 3.32, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(133, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 84.47, 4.0, 2.66, 3.56, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(134, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B+', 68.02, 4.0, 2.63, 3.75, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(135, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 99.14, 4.0, 2.81, 2.67, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(136, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 73.83, 4.0, 3.88, 3.33, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(137, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B', 94.70, 4.0, 2.81, 3.13, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(138, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 67.21, 4.0, 3.16, 3.46, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(139, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 83.99, 4.0, 2.92, 3.41, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(140, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B', 92.32, 4.0, 3.76, 3.66, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(141, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 77.30, 4.0, 2.66, 2.87, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(142, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 94.31, 4.0, 3.29, 2.57, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(143, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 66.73, 4.0, 3.78, 3.66, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(144, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 68.90, 4.0, 2.77, 2.87, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(145, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 87.72, 4.0, 3.11, 3.91, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(146, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 85.75, 4.0, 3.61, 3.64, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(147, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 87.44, 4.0, 3.47, 2.76, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(148, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 66.57, 4.0, 2.51, 3.31, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(149, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'A', 91.10, 4.0, 3.77, 3.87, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(150, 0, 'Semester 1', '2024/2025', 'Fundamentals of Nursing I', 'CNN101', 'B', 89.76, 4.0, 3.45, 3.93, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(151, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 74.97, 3.0, 3.00, 3.33, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(152, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 63.80, 3.0, 2.84, 3.77, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(153, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 71.26, 3.0, 3.57, 3.56, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(154, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 95.45, 3.0, 2.84, 3.23, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(155, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 68.30, 3.0, 3.76, 3.39, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(156, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 74.69, 3.0, 3.33, 3.48, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(157, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 66.80, 3.0, 3.95, 2.98, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(158, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 82.46, 3.0, 3.54, 3.68, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(159, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 94.03, 3.0, 3.60, 2.65, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(160, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 71.94, 3.0, 3.29, 3.62, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(161, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B+', 63.31, 3.0, 3.84, 2.84, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(162, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 82.65, 3.0, 3.23, 3.58, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(163, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B+', 72.58, 3.0, 3.90, 3.57, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(164, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 89.07, 3.0, 2.97, 3.08, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(165, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 92.18, 3.0, 2.57, 3.71, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(166, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 62.51, 3.0, 3.44, 3.92, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(167, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 74.91, 3.0, 3.01, 3.38, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(168, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 91.00, 3.0, 2.73, 3.18, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(169, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 86.31, 3.0, 3.81, 3.11, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(170, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 92.39, 3.0, 3.75, 3.59, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(171, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B', 98.46, 3.0, 3.10, 2.67, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(172, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 82.05, 3.0, 3.43, 3.15, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(173, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 69.16, 3.0, 2.85, 3.21, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(174, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 96.13, 3.0, 3.29, 3.87, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(175, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B', 75.33, 3.0, 2.61, 2.84, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(176, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 95.43, 3.0, 3.53, 3.66, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(177, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 90.69, 3.0, 3.07, 3.39, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(178, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 76.08, 3.0, 3.25, 2.92, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(179, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 90.50, 3.0, 2.57, 3.90, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(180, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 97.22, 3.0, 2.51, 2.88, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(181, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B', 69.74, 3.0, 2.59, 3.37, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(182, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 93.88, 3.0, 2.62, 3.80, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(183, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B+', 96.78, 3.0, 2.63, 3.53, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(184, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B+', 77.44, 3.0, 3.72, 3.63, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(185, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 73.81, 3.0, 3.65, 3.70, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(186, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 62.79, 3.0, 2.90, 2.71, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(187, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 99.31, 3.0, 2.92, 3.16, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(188, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 78.42, 3.0, 2.86, 3.73, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(189, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 75.27, 3.0, 3.71, 3.85, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(190, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B+', 63.37, 3.0, 3.16, 3.93, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(191, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 73.84, 3.0, 3.11, 4.00, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(192, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 94.42, 3.0, 3.98, 3.04, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(193, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 65.53, 3.0, 2.73, 3.05, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(194, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 88.99, 3.0, 3.29, 3.20, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(195, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 75.35, 3.0, 3.47, 2.61, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(196, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 97.45, 3.0, 3.08, 2.69, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(197, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 60.42, 3.0, 3.42, 2.57, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(198, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 90.17, 3.0, 3.46, 3.89, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(199, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 94.61, 3.0, 2.70, 2.62, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(200, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 88.07, 3.0, 3.32, 3.44, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(201, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 84.06, 3.0, 3.28, 3.67, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(202, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 77.75, 3.0, 2.72, 3.09, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(203, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 80.42, 3.0, 3.90, 2.72, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(204, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 65.61, 3.0, 3.94, 3.05, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(205, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 89.17, 3.0, 3.62, 3.32, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(206, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 91.61, 3.0, 3.24, 2.66, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(207, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B+', 86.14, 3.0, 3.05, 3.83, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(208, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 97.64, 3.0, 3.62, 3.87, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(209, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 95.14, 3.0, 3.13, 3.21, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(210, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B', 69.29, 3.0, 3.74, 3.18, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(211, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 79.19, 3.0, 2.65, 2.58, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(212, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 88.25, 3.0, 3.42, 3.90, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(213, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 75.89, 3.0, 3.20, 2.71, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(214, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B', 79.60, 3.0, 2.83, 3.42, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(215, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 70.78, 3.0, 2.62, 3.38, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(216, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 91.18, 3.0, 3.65, 3.26, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(217, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B+', 67.68, 3.0, 2.88, 3.55, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(218, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 84.62, 3.0, 3.77, 3.06, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(219, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 83.47, 3.0, 3.86, 3.66, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(220, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B', 85.42, 3.0, 3.90, 3.62, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(221, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 80.87, 3.0, 3.62, 2.75, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(222, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 78.15, 3.0, 3.25, 2.69, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(223, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B', 71.78, 3.0, 3.15, 2.92, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(224, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B+', 70.28, 3.0, 2.69, 3.79, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(225, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 63.44, 3.0, 3.44, 3.80, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(226, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 88.25, 3.0, 2.72, 3.42, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(227, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 70.19, 3.0, 3.13, 3.01, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(228, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 65.66, 3.0, 3.12, 3.46, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(229, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 95.67, 3.0, 3.36, 2.77, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(230, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B', 84.12, 3.0, 3.55, 3.53, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(231, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 82.33, 3.0, 3.74, 3.18, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(232, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 84.86, 3.0, 3.57, 3.57, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(233, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 99.61, 3.0, 3.51, 3.07, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(234, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 72.50, 3.0, 3.84, 3.27, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(235, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 60.05, 3.0, 2.91, 3.06, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(236, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B', 81.11, 3.0, 2.84, 3.32, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(237, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B+', 97.10, 3.0, 3.68, 2.75, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(238, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 93.36, 3.0, 3.65, 3.01, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(239, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 97.38, 3.0, 3.25, 3.56, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(240, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B', 99.41, 3.0, 3.83, 3.22, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(241, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 69.93, 3.0, 2.54, 3.10, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(242, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 75.54, 3.0, 2.78, 3.66, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(243, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B', 99.47, 3.0, 3.11, 2.59, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(244, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B', 98.38, 3.0, 2.61, 3.22, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(245, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B', 84.95, 3.0, 3.75, 2.96, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(246, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B', 88.66, 3.0, 2.70, 3.26, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(247, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B', 74.57, 3.0, 3.04, 3.57, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(248, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 73.31, 3.0, 2.76, 3.81, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(249, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 85.17, 3.0, 3.39, 2.62, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(250, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 95.67, 3.0, 3.36, 2.81, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(251, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 96.71, 3.0, 3.50, 3.36, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(252, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 82.99, 3.0, 2.95, 3.68, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(253, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B+', 91.49, 3.0, 3.42, 3.58, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(254, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 83.28, 3.0, 3.50, 3.38, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(255, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 94.33, 3.0, 3.29, 2.58, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(256, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 69.33, 3.0, 2.70, 3.97, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(257, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 79.37, 3.0, 3.96, 3.10, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(258, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B', 91.67, 3.0, 3.01, 2.98, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(259, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 98.03, 3.0, 2.51, 2.78, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(260, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 98.03, 3.0, 2.58, 3.10, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(261, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 62.80, 3.0, 3.68, 3.58, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(262, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B', 77.49, 3.0, 2.65, 2.76, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(263, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 73.54, 3.0, 3.97, 3.82, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(264, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 88.92, 3.0, 2.79, 3.69, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(265, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 82.11, 3.0, 3.41, 3.05, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(266, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B', 60.13, 3.0, 3.96, 3.77, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(267, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 63.96, 3.0, 3.26, 2.86, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(268, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 86.08, 3.0, 2.86, 2.85, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(269, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 84.79, 3.0, 3.55, 3.48, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(270, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B+', 77.89, 3.0, 3.86, 2.78, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(271, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B+', 96.07, 3.0, 3.98, 2.82, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(272, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B+', 74.87, 3.0, 2.54, 2.51, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(273, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 92.85, 3.0, 2.80, 3.29, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(274, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B+', 93.22, 3.0, 3.09, 3.24, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(275, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B+', 78.94, 3.0, 3.71, 3.41, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(276, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 71.40, 3.0, 3.34, 3.92, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(277, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B', 88.05, 3.0, 3.12, 3.96, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(278, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 66.43, 3.0, 3.94, 2.95, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(279, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 72.50, 3.0, 3.44, 2.81, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(280, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B', 69.05, 3.0, 3.58, 3.90, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(281, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 86.75, 3.0, 3.80, 2.98, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(282, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 60.84, 3.0, 2.68, 3.31, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(283, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 60.62, 3.0, 2.65, 3.20, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(284, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B+', 71.58, 3.0, 3.18, 3.10, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(285, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 60.11, 3.0, 2.64, 3.17, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(286, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 79.97, 3.0, 3.38, 3.17, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(287, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 99.61, 3.0, 3.33, 3.70, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(288, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 69.47, 3.0, 2.81, 2.98, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(289, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 98.14, 3.0, 3.73, 2.85, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(290, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 96.01, 3.0, 3.00, 3.97, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(291, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 81.32, 3.0, 3.97, 2.95, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(292, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 96.12, 3.0, 3.75, 3.19, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(293, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 82.78, 3.0, 3.22, 3.55, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(294, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B', 89.13, 3.0, 2.65, 3.00, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(295, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 89.12, 3.0, 3.41, 3.78, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(296, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 88.83, 3.0, 2.84, 3.98, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(297, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'B', 81.06, 3.0, 3.82, 3.74, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(298, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 98.57, 3.0, 3.03, 3.83, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(299, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 65.53, 3.0, 3.42, 3.49, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(300, 0, 'Semester 1', '2024/2025', 'Anatomy & Physiology I', 'CNN102', 'A', 73.37, 3.0, 2.92, 3.09, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(301, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'B', 87.59, 3.0, 2.84, 2.60, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(302, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 62.18, 3.0, 2.98, 3.15, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(303, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'B+', 68.94, 3.0, 3.69, 2.96, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(304, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'B+', 75.13, 3.0, 3.44, 3.98, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(305, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'B', 87.15, 3.0, 2.90, 2.92, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(306, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 69.13, 3.0, 2.95, 3.71, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(307, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'B', 63.61, 3.0, 3.30, 3.10, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(308, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 89.25, 3.0, 3.25, 2.98, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(309, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'B+', 72.90, 3.0, 2.57, 2.88, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(310, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'B+', 98.61, 3.0, 2.84, 2.84, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(311, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 82.83, 3.0, 3.26, 3.74, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(312, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 83.37, 3.0, 2.62, 3.48, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(313, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'B', 83.67, 3.0, 3.36, 2.63, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(314, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 75.23, 3.0, 3.57, 3.14, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(315, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 88.43, 3.0, 3.33, 3.47, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(316, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 96.08, 3.0, 3.71, 2.97, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(317, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'B+', 83.07, 3.0, 3.21, 3.44, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(318, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 90.11, 3.0, 3.37, 3.47, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(319, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 80.51, 3.0, 2.64, 3.88, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(320, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 96.69, 3.0, 3.36, 2.68, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54');
INSERT INTO `student_academic_records` (`id`, `student_id`, `semester`, `academic_year`, `subject`, `course_code`, `grade`, `marks`, `credits`, `gpa`, `cgpa`, `remarks`, `created_at`, `updated_at`) VALUES
(321, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 62.40, 3.0, 3.47, 2.56, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(322, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'B', 78.12, 3.0, 3.24, 2.68, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(323, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'B', 78.48, 3.0, 3.78, 3.81, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(324, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 74.98, 3.0, 3.22, 2.92, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(325, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 60.21, 3.0, 2.67, 3.33, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(326, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 77.50, 3.0, 3.90, 3.02, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(327, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 84.99, 3.0, 2.99, 3.64, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(328, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 94.41, 3.0, 3.72, 3.23, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(329, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 77.93, 3.0, 2.95, 2.75, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(330, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 63.93, 3.0, 3.60, 3.07, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(331, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 74.01, 3.0, 3.48, 2.84, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(332, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'B', 79.84, 3.0, 3.79, 3.72, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(333, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 99.38, 3.0, 3.20, 3.08, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(334, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 78.62, 3.0, 3.63, 3.06, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(335, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 96.60, 3.0, 3.63, 2.53, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(336, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 64.38, 3.0, 2.57, 3.88, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(337, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 78.50, 3.0, 3.97, 3.28, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(338, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 90.95, 3.0, 3.79, 3.99, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(339, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 96.02, 3.0, 3.06, 2.76, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(340, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 66.98, 3.0, 3.49, 3.65, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(341, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 97.40, 3.0, 2.71, 3.84, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(342, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'B+', 92.08, 3.0, 2.84, 3.60, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(343, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 87.88, 3.0, 3.32, 3.48, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(344, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 66.14, 3.0, 3.85, 2.55, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(345, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 68.95, 3.0, 3.59, 3.95, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(346, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 73.56, 3.0, 3.63, 3.62, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(347, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 64.62, 3.0, 2.75, 3.23, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(348, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 67.84, 3.0, 2.78, 3.02, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(349, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'B+', 90.53, 3.0, 2.85, 3.80, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(350, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 86.24, 3.0, 2.98, 3.46, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(351, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'B', 73.50, 3.0, 2.63, 3.12, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(352, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 91.17, 3.0, 3.23, 2.66, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(353, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'B+', 87.28, 3.0, 3.25, 3.17, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(354, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 75.55, 3.0, 3.55, 3.02, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(355, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 62.32, 3.0, 3.15, 4.00, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(356, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 76.92, 3.0, 2.60, 2.59, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(357, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'B', 77.52, 3.0, 2.70, 3.04, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(358, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 94.29, 3.0, 2.69, 2.60, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(359, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 81.22, 3.0, 3.72, 3.24, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(360, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'B+', 90.78, 3.0, 2.76, 3.35, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(361, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 92.92, 3.0, 2.80, 3.32, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(362, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'B+', 71.09, 3.0, 3.43, 2.91, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(363, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 86.94, 3.0, 3.81, 3.02, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(364, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'B+', 80.47, 3.0, 3.74, 3.39, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(365, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 88.48, 3.0, 2.59, 2.75, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(366, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 91.49, 3.0, 3.94, 3.17, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(367, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 74.64, 3.0, 3.71, 3.93, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(368, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 91.53, 3.0, 3.93, 3.12, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(369, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'B+', 98.94, 3.0, 3.62, 3.71, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(370, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 81.33, 3.0, 2.95, 3.83, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(371, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 61.59, 3.0, 3.37, 3.67, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(372, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'B', 96.39, 3.0, 2.66, 3.71, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(373, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 63.18, 3.0, 2.95, 2.92, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(374, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 82.50, 3.0, 3.06, 2.78, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(375, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 78.66, 3.0, 3.88, 2.79, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(376, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'B', 87.25, 3.0, 2.54, 2.61, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(377, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 72.10, 3.0, 3.39, 2.60, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(378, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 81.72, 3.0, 2.60, 3.58, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(379, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 90.33, 3.0, 3.47, 3.91, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(380, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 63.50, 3.0, 2.63, 2.77, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(381, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 83.72, 3.0, 2.63, 3.50, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(382, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'B', 70.33, 3.0, 3.15, 3.11, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(383, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 74.68, 3.0, 3.53, 3.00, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(384, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 99.79, 3.0, 2.77, 3.87, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(385, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'B', 66.47, 3.0, 3.16, 3.55, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(386, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'B+', 91.74, 3.0, 3.01, 2.96, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(387, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 88.56, 3.0, 3.98, 3.69, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(388, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'B+', 73.60, 3.0, 3.51, 3.03, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(389, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 83.87, 3.0, 3.69, 2.78, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(390, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 64.98, 3.0, 2.52, 3.56, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(391, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 74.06, 3.0, 2.91, 2.94, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(392, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 78.94, 3.0, 3.02, 2.99, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(393, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 96.65, 3.0, 3.77, 3.22, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(394, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 97.14, 3.0, 2.52, 2.95, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(395, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 71.49, 3.0, 2.69, 3.67, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(396, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 71.67, 3.0, 3.81, 3.24, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(397, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 91.46, 3.0, 3.05, 3.23, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(398, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 64.89, 3.0, 3.50, 3.95, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(399, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 70.66, 3.0, 3.75, 3.05, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(400, 0, 'Semester 1', '2024/2025', 'Community Health Nursing I', 'CNN103', 'A', 81.61, 3.0, 3.58, 3.97, 'Pass', '2026-07-03 04:51:14', '2026-07-14 16:52:54');

-- --------------------------------------------------------

--
-- Table structure for table `student_admissions`
--

CREATE TABLE `student_admissions` (
  `id` int(11) NOT NULL,
  `application_number` varchar(50) DEFAULT NULL,
  `applicant_name` varchar(300) NOT NULL,
  `program` varchar(200) DEFAULT '',
  `academic_year` varchar(20) DEFAULT NULL,
  `admission_status` varchar(50) DEFAULT 'Applied',
  `application_date` date DEFAULT NULL,
  `decided_by` int(11) DEFAULT 0,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_admission_tracking`
--

CREATE TABLE `student_admission_tracking` (
  `id` int(11) NOT NULL,
  `student_number` varchar(50) DEFAULT NULL,
  `application_number` varchar(30) NOT NULL,
  `applicant_id` int(11) DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `intake` varchar(50) DEFAULT NULL,
  `admission_date` date DEFAULT NULL,
  `admission_status` enum('Pending','Under Review','Requirements Pending','Approved','Rejected','Registered') NOT NULL DEFAULT 'Pending',
  `requirements_total` int(11) NOT NULL DEFAULT 0,
  `requirements_completed` int(11) NOT NULL DEFAULT 0,
  `documents_uploaded` int(11) NOT NULL DEFAULT 0,
  `interview_scheduled` tinyint(1) NOT NULL DEFAULT 0,
  `interview_date` datetime DEFAULT NULL,
  `interview_notes` text DEFAULT NULL,
  `communication_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_appeals`
--

CREATE TABLE `student_appeals` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `appeal_type` varchar(200) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `outcome` varchar(500) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_applications`
--

CREATE TABLE `student_applications` (
  `id` int(11) NOT NULL,
  `application_number` varchar(50) DEFAULT NULL,
  `applicant_name` varchar(300) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `program` varchar(200) DEFAULT '',
  `academic_year` varchar(20) DEFAULT NULL,
  `application_status` varchar(50) DEFAULT 'Applied',
  `application_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_attendance`
--

CREATE TABLE `student_attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `status` enum('Present','Absent','Late','Excused') NOT NULL,
  `remarks` text DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_attendance`
--

INSERT INTO `student_attendance` (`id`, `student_id`, `date`, `subject`, `course_code`, `status`, `remarks`, `recorded_by`, `created_at`, `updated_at`) VALUES
(1, 0, '2024-09-03', NULL, 'CNN101', 'Late', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(2, 0, '2024-09-10', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(3, 0, '2024-09-17', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(4, 0, '2024-09-24', NULL, 'CNN101', 'Absent', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(5, 0, '2024-10-01', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(6, 0, '2024-09-03', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(7, 0, '2024-09-10', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(8, 0, '2024-09-17', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(9, 0, '2024-09-24', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(10, 0, '2024-10-01', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(11, 0, '2024-09-03', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(12, 0, '2024-09-10', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(13, 0, '2024-09-17', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(14, 0, '2024-09-24', NULL, 'CNN101', 'Late', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(15, 0, '2024-10-01', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(16, 0, '2024-09-03', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(17, 0, '2024-09-10', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(18, 0, '2024-09-17', NULL, 'CNN101', 'Late', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(19, 0, '2024-09-24', NULL, 'CNN101', 'Late', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(20, 0, '2024-10-01', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(21, 0, '2024-09-03', NULL, 'CNN101', 'Absent', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(22, 0, '2024-09-10', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(23, 0, '2024-09-17', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(24, 0, '2024-09-24', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(25, 0, '2024-10-01', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(26, 0, '2024-09-03', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(27, 0, '2024-09-10', NULL, 'CNN101', 'Absent', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(28, 0, '2024-09-17', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(29, 0, '2024-09-24', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(30, 0, '2024-10-01', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(31, 0, '2024-09-03', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(32, 0, '2024-09-10', NULL, 'CNN101', 'Late', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(33, 0, '2024-09-17', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(34, 0, '2024-09-24', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(35, 0, '2024-10-01', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(36, 0, '2024-09-03', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(37, 0, '2024-09-10', NULL, 'CNN101', 'Absent', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(38, 0, '2024-09-17', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(39, 0, '2024-09-24', NULL, 'CNN101', 'Absent', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(40, 0, '2024-10-01', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(41, 0, '2024-09-03', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(42, 0, '2024-09-10', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(43, 0, '2024-09-17', NULL, 'CNN101', 'Late', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(44, 0, '2024-09-24', NULL, 'CNN101', 'Absent', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(45, 0, '2024-10-01', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(46, 0, '2024-09-03', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(47, 0, '2024-09-10', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(48, 0, '2024-09-17', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(49, 0, '2024-09-24', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(50, 0, '2024-10-01', NULL, 'CNN101', 'Late', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(51, 0, '2024-09-03', NULL, 'CNN101', 'Absent', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(52, 0, '2024-09-10', NULL, 'CNN101', 'Late', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(53, 0, '2024-09-17', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(54, 0, '2024-09-24', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(55, 0, '2024-10-01', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(56, 0, '2024-09-03', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(57, 0, '2024-09-10', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(58, 0, '2024-09-17', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(59, 0, '2024-09-24', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(60, 0, '2024-10-01', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(61, 0, '2024-09-03', NULL, 'CNN101', 'Late', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(62, 0, '2024-09-10', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(63, 0, '2024-09-17', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(64, 0, '2024-09-24', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(65, 0, '2024-10-01', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(66, 0, '2024-09-03', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(67, 0, '2024-09-10', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(68, 0, '2024-09-17', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(69, 0, '2024-09-24', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(70, 0, '2024-10-01', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(71, 0, '2024-09-03', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(72, 0, '2024-09-10', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(73, 0, '2024-09-17', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(74, 0, '2024-09-24', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(75, 0, '2024-10-01', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(76, 0, '2024-09-03', NULL, 'CNN101', 'Late', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(77, 0, '2024-09-10', NULL, 'CNN101', 'Late', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(78, 0, '2024-09-17', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(79, 0, '2024-09-24', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(80, 0, '2024-10-01', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(81, 0, '2024-09-03', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(82, 0, '2024-09-10', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(83, 0, '2024-09-17', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(84, 0, '2024-09-24', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(85, 0, '2024-10-01', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(86, 0, '2024-09-03', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(87, 0, '2024-09-10', NULL, 'CNN101', 'Absent', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(88, 0, '2024-09-17', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(89, 0, '2024-09-24', NULL, 'CNN101', 'Late', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(90, 0, '2024-10-01', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(91, 0, '2024-09-03', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(92, 0, '2024-09-10', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(93, 0, '2024-09-17', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(94, 0, '2024-09-24', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(95, 0, '2024-10-01', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(96, 0, '2024-09-03', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(97, 0, '2024-09-10', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(98, 0, '2024-09-17', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(99, 0, '2024-09-24', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54'),
(100, 0, '2024-10-01', NULL, 'CNN101', 'Present', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:52:54');

-- --------------------------------------------------------

--
-- Table structure for table `student_counseling_sessions`
--

CREATE TABLE `student_counseling_sessions` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `session_date` date DEFAULT NULL,
  `session_type` varchar(100) DEFAULT NULL,
  `counselor_name` varchar(200) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Scheduled',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_course_registrations`
--

CREATE TABLE `student_course_registrations` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `course_id` int(11) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `registration_date` date DEFAULT curdate(),
  `status` enum('Registered','Dropped','Completed') DEFAULT 'Registered',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_course_registrations`
--

INSERT INTO `student_course_registrations` (`id`, `student_id`, `course_id`, `academic_year`, `semester`, `registration_date`, `status`, `created_at`) VALUES
(1, '0', 1, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(2, '0', 1, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(3, '0', 1, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(4, '0', 1, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(5, '0', 1, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(6, '0', 1, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(7, '0', 1, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(8, '0', 1, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(9, '0', 1, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(10, '0', 1, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(11, '0', 1, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(12, '0', 1, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(13, '0', 1, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(14, '0', 1, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(15, '0', 1, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(16, '0', 1, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(17, '0', 1, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(18, '0', 1, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(19, '0', 1, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(20, '0', 1, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(21, '0', 2, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(22, '0', 2, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(23, '0', 2, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(24, '0', 2, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(25, '0', 2, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(26, '0', 2, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(27, '0', 2, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(28, '0', 2, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(29, '0', 2, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(30, '0', 2, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(31, '0', 2, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(32, '0', 2, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(33, '0', 2, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(34, '0', 2, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(35, '0', 2, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(36, '0', 2, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(37, '0', 2, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(38, '0', 2, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(39, '0', 2, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(40, '0', 2, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(41, '0', 3, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(42, '0', 3, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(43, '0', 3, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(44, '0', 3, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(45, '0', 3, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(46, '0', 3, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(47, '0', 3, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(48, '0', 3, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(49, '0', 3, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(50, '0', 3, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(51, '0', 3, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(52, '0', 3, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(53, '0', 3, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(54, '0', 3, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(55, '0', 3, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(56, '0', 3, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(57, '0', 3, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(58, '0', 3, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(59, '0', 3, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(60, '0', 3, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(61, '0', 11, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(62, '0', 11, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(63, '0', 11, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(64, '0', 11, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(65, '0', 11, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(66, '0', 11, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(67, '0', 11, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(68, '0', 11, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(69, '0', 11, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(70, '0', 11, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(71, '0', 11, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(72, '0', 11, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(73, '0', 11, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(74, '0', 11, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(75, '0', 11, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(76, '0', 11, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(77, '0', 11, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(78, '0', 11, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(79, '0', 11, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(80, '0', 11, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(81, '0', 12, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(82, '0', 12, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(83, '0', 12, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(84, '0', 12, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(85, '0', 12, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(86, '0', 12, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(87, '0', 12, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(88, '0', 12, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(89, '0', 12, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(90, '0', 12, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(91, '0', 12, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(92, '0', 12, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(93, '0', 12, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(94, '0', 12, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(95, '0', 12, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(96, '0', 12, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(97, '0', 12, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(98, '0', 12, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(99, '0', 12, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(100, '0', 12, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(101, '0', 13, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(102, '0', 13, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(103, '0', 13, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(104, '0', 13, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(105, '0', 13, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(106, '0', 13, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(107, '0', 13, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(108, '0', 13, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(109, '0', 13, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(110, '0', 13, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(111, '0', 13, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(112, '0', 13, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(113, '0', 13, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(114, '0', 13, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(115, '0', 13, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(116, '0', 13, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(117, '0', 13, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(118, '0', 13, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(119, '0', 13, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(120, '0', 13, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(121, '0', 48, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(122, '0', 48, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(123, '0', 48, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(124, '0', 48, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(125, '0', 48, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(126, '0', 48, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(127, '0', 48, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(128, '0', 48, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(129, '0', 48, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(130, '0', 48, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(131, '0', 48, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(132, '0', 48, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(133, '0', 48, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(134, '0', 48, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(135, '0', 48, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(136, '0', 48, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(137, '0', 48, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(138, '0', 48, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(139, '0', 48, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(140, '0', 48, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(141, '0', 49, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(142, '0', 49, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(143, '0', 49, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(144, '0', 49, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(145, '0', 49, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(146, '0', 49, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(147, '0', 49, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(148, '0', 49, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(149, '0', 49, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(150, '0', 49, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(151, '0', 49, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(152, '0', 49, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(153, '0', 49, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(154, '0', 49, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(155, '0', 49, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(156, '0', 49, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(157, '0', 49, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(158, '0', 49, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(159, '0', 49, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(160, '0', 49, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(161, '0', 50, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(162, '0', 50, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(163, '0', 50, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(164, '0', 50, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(165, '0', 50, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(166, '0', 50, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(167, '0', 50, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(168, '0', 50, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(169, '0', 50, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(170, '0', 50, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(171, '0', 50, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(172, '0', 50, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(173, '0', 50, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(174, '0', 50, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(175, '0', 50, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(176, '0', 50, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(177, '0', 50, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(178, '0', 50, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(179, '0', 50, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(180, '0', 50, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(181, '0', 58, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(182, '0', 58, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(183, '0', 58, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(184, '0', 58, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(185, '0', 58, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(186, '0', 58, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(187, '0', 58, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(188, '0', 58, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(189, '0', 58, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(190, '0', 58, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(191, '0', 58, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(192, '0', 58, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(193, '0', 58, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(194, '0', 58, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(195, '0', 58, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(196, '0', 58, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(197, '0', 58, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(198, '0', 58, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(199, '0', 58, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(200, '0', 58, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(201, '0', 59, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(202, '0', 59, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(203, '0', 59, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(204, '0', 59, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(205, '0', 59, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(206, '0', 59, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(207, '0', 59, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(208, '0', 59, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(209, '0', 59, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(210, '0', 59, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(211, '0', 59, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(212, '0', 59, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(213, '0', 59, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(214, '0', 59, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(215, '0', 59, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(216, '0', 59, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(217, '0', 59, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(218, '0', 59, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(219, '0', 59, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(220, '0', 59, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(221, '0', 60, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(222, '0', 60, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(223, '0', 60, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(224, '0', 60, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(225, '0', 60, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(226, '0', 60, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(227, '0', 60, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(228, '0', 60, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(229, '0', 60, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(230, '0', 60, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(231, '0', 60, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(232, '0', 60, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(233, '0', 60, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(234, '0', 60, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(235, '0', 60, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(236, '0', 60, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(237, '0', 60, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(238, '0', 60, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(239, '0', 60, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(240, '0', 60, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(241, '0', 95, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(242, '0', 95, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(243, '0', 95, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(244, '0', 95, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(245, '0', 95, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(246, '0', 95, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(247, '0', 95, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(248, '0', 95, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(249, '0', 95, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(250, '0', 95, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(251, '0', 95, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(252, '0', 95, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(253, '0', 95, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(254, '0', 95, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(255, '0', 95, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(256, '0', 95, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(257, '0', 95, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(258, '0', 95, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(259, '0', 95, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(260, '0', 95, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(261, '0', 96, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(262, '0', 96, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(263, '0', 96, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(264, '0', 96, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(265, '0', 96, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(266, '0', 96, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(267, '0', 96, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(268, '0', 96, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(269, '0', 96, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(270, '0', 96, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(271, '0', 96, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(272, '0', 96, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(273, '0', 96, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(274, '0', 96, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(275, '0', 96, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(276, '0', 96, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(277, '0', 96, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(278, '0', 96, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(279, '0', 96, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(280, '0', 96, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(281, '0', 97, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(282, '0', 97, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(283, '0', 97, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(284, '0', 97, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(285, '0', 97, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(286, '0', 97, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(287, '0', 97, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(288, '0', 97, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(289, '0', 97, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(290, '0', 97, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(291, '0', 97, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(292, '0', 97, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(293, '0', 97, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(294, '0', 97, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(295, '0', 97, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(296, '0', 97, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(297, '0', 97, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(298, '0', 97, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(299, '0', 97, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(300, '0', 97, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(301, '0', 105, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(302, '0', 105, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(303, '0', 105, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(304, '0', 105, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(305, '0', 105, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(306, '0', 105, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(307, '0', 105, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(308, '0', 105, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(309, '0', 105, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(310, '0', 105, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(311, '0', 105, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(312, '0', 105, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(313, '0', 105, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(314, '0', 105, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(315, '0', 105, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(316, '0', 105, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(317, '0', 105, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(318, '0', 105, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(319, '0', 105, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(320, '0', 105, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(321, '0', 106, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(322, '0', 106, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(323, '0', 106, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(324, '0', 106, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(325, '0', 106, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(326, '0', 106, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(327, '0', 106, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(328, '0', 106, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(329, '0', 106, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(330, '0', 106, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(331, '0', 106, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(332, '0', 106, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(333, '0', 106, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(334, '0', 106, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(335, '0', 106, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(336, '0', 106, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(337, '0', 106, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(338, '0', 106, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(339, '0', 106, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(340, '0', 106, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(341, '0', 107, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(342, '0', 107, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(343, '0', 107, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(344, '0', 107, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(345, '0', 107, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(346, '0', 107, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(347, '0', 107, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(348, '0', 107, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(349, '0', 107, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(350, '0', 107, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(351, '0', 107, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(352, '0', 107, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(353, '0', 107, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(354, '0', 107, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(355, '0', 107, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(356, '0', 107, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(357, '0', 107, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(358, '0', 107, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(359, '0', 107, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(360, '0', 107, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(361, '0', 142, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(362, '0', 142, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(363, '0', 142, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(364, '0', 142, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(365, '0', 142, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(366, '0', 142, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(367, '0', 142, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(368, '0', 142, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(369, '0', 142, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(370, '0', 142, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(371, '0', 142, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(372, '0', 142, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(373, '0', 142, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(374, '0', 142, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(375, '0', 142, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(376, '0', 142, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(377, '0', 142, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(378, '0', 142, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(379, '0', 142, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(380, '0', 142, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(381, '0', 143, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(382, '0', 143, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(383, '0', 143, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(384, '0', 143, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(385, '0', 143, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(386, '0', 143, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(387, '0', 143, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(388, '0', 143, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(389, '0', 143, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(390, '0', 143, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(391, '0', 143, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(392, '0', 143, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(393, '0', 143, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(394, '0', 143, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(395, '0', 143, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(396, '0', 143, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(397, '0', 143, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(398, '0', 143, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(399, '0', 143, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(400, '0', 143, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(401, '0', 144, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(402, '0', 144, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(403, '0', 144, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(404, '0', 144, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(405, '0', 144, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(406, '0', 144, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(407, '0', 144, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(408, '0', 144, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(409, '0', 144, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(410, '0', 144, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(411, '0', 144, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(412, '0', 144, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(413, '0', 144, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(414, '0', 144, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(415, '0', 144, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(416, '0', 144, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(417, '0', 144, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(418, '0', 144, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(419, '0', 144, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(420, '0', 144, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(421, '0', 152, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(422, '0', 152, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(423, '0', 152, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(424, '0', 152, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(425, '0', 152, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(426, '0', 152, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(427, '0', 152, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(428, '0', 152, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(429, '0', 152, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(430, '0', 152, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(431, '0', 152, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(432, '0', 152, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(433, '0', 152, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(434, '0', 152, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(435, '0', 152, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(436, '0', 152, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(437, '0', 152, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(438, '0', 152, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(439, '0', 152, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(440, '0', 152, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(441, '0', 153, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(442, '0', 153, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(443, '0', 153, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(444, '0', 153, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(445, '0', 153, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(446, '0', 153, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(447, '0', 153, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(448, '0', 153, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(449, '0', 153, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(450, '0', 153, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(451, '0', 153, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(452, '0', 153, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(453, '0', 153, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(454, '0', 153, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(455, '0', 153, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(456, '0', 153, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(457, '0', 153, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(458, '0', 153, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(459, '0', 153, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(460, '0', 153, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(461, '0', 154, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(462, '0', 154, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(463, '0', 154, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(464, '0', 154, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(465, '0', 154, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(466, '0', 154, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(467, '0', 154, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(468, '0', 154, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(469, '0', 154, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(470, '0', 154, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(471, '0', 154, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(472, '0', 154, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(473, '0', 154, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(474, '0', 154, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(475, '0', 154, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(476, '0', 154, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(477, '0', 154, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(478, '0', 154, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(479, '0', 154, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14'),
(480, '0', 154, '2024/2025', 'Semester 1', '2026-07-03', 'Registered', '2026-07-03 04:51:14');

-- --------------------------------------------------------

--
-- Stand-in structure for view `student_dashboard_view`
-- (See below for the actual view)
--
CREATE TABLE `student_dashboard_view` (
`id` int(11)
,`student_number` varchar(50)
,`full_name` varchar(302)
,`course` varchar(100)
,`year` int(11)
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

CREATE TABLE `student_discipline` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `incident_date` date NOT NULL,
  `incident_type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `action_taken` varchar(255) DEFAULT NULL,
  `action_date` date DEFAULT NULL,
  `reported_by` int(11) DEFAULT NULL,
  `status` enum('Open','Resolved','Appealed') DEFAULT 'Open',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_discipline`
--

INSERT INTO `student_discipline` (`id`, `student_id`, `incident_date`, `incident_type`, `description`, `action_taken`, `action_date`, `reported_by`, `status`, `created_at`) VALUES
(1, '183366', '2024-10-10', 'Minor', 'Late submission of assignment', 'Warning issued', '2024-10-12', NULL, 'Resolved', '2026-07-03 04:51:14'),
(2, '183364', '2024-10-15', 'Minor', 'Absence from practical session', 'Make-up session scheduled', '2024-10-17', NULL, 'Resolved', '2026-07-03 04:51:14'),
(3, '183362', '2024-11-01', 'Major', 'Plagiarism in coursework', 'Under review', NULL, NULL, 'Open', '2026-07-03 04:51:14'),
(4, '183359', '2024-10-20', 'Minor', 'Uniform violation', 'Verbal warning', '2024-10-21', NULL, 'Resolved', '2026-07-03 04:51:14'),
(5, '183357', '2024-11-05', 'Minor', 'Noise in dormitory after hours', 'Written warning', '2024-11-06', NULL, 'Resolved', '2026-07-03 04:51:14'),
(6, '183354', '2024-11-10', 'Major', 'Unauthorized absence from clinical', 'Parent notified', '2024-11-12', NULL, 'Resolved', '2026-07-03 04:51:14');

-- --------------------------------------------------------

--
-- Table structure for table `student_discipline_records`
--

CREATE TABLE `student_discipline_records` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `case_number` varchar(50) NOT NULL,
  `incident_date` date DEFAULT NULL,
  `incident_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `action_taken` varchar(100) DEFAULT NULL,
  `status` enum('Pending','Resolved','Closed') DEFAULT 'Pending',
  `recorded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_documents`
--

CREATE TABLE `student_documents` (
  `id` int(11) NOT NULL,
  `applicant_id` int(11) NOT NULL,
  `requirement_id` int(11) DEFAULT NULL,
  `document_name` varchar(255) NOT NULL,
  `document_type` varchar(100) DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `file_mime` varchar(100) DEFAULT NULL,
  `verification_status` enum('Pending','Verified','Rejected') NOT NULL DEFAULT 'Pending',
  `verification_remarks` text DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `document_status` enum('Active','Deleted') NOT NULL DEFAULT 'Active',
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_downloads`
--

CREATE TABLE `student_downloads` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `download_count` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_fees`
--

CREATE TABLE `student_fees` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `fee_type` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date DEFAULT NULL,
  `paid_date` date DEFAULT NULL,
  `status` enum('Unpaid','Partially Paid','Paid','Overdue') DEFAULT 'Unpaid',
  `payment_method` varchar(50) DEFAULT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_fee_assignments`
--

CREATE TABLE `student_fee_assignments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `fee_structure_id` int(11) NOT NULL,
  `assigned_amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `balance` decimal(10,2) GENERATED ALWAYS AS (`assigned_amount` - `paid_amount`) STORED,
  `status` enum('Unpaid','Partially Paid','Paid','Waived') DEFAULT 'Unpaid',
  `due_date` date DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_fee_tracking`
--

CREATE TABLE `student_fee_tracking` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `fee_type` varchar(50) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT 0.00,
  `amount_paid` decimal(12,2) DEFAULT 0.00,
  `balance` decimal(12,2) DEFAULT 0.00,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `student_fee_tracking`
--

INSERT INTO `student_fee_tracking` (`id`, `student_id`, `fee_type`, `amount`, `amount_paid`, `balance`, `academic_year`, `semester`, `due_date`, `status`, `created_at`) VALUES
(1, 0, 'Tuition', 1220000.00, 781334.00, 438666.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(2, 0, 'Tuition', 1220000.00, 761717.00, 458283.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(3, 0, 'Tuition', 1220000.00, 464587.00, 755413.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(4, 0, 'Tuition', 1220000.00, 637782.00, 582218.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(5, 0, 'Tuition', 1220000.00, 795152.00, 424848.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(6, 0, 'Tuition', 1220000.00, 262414.00, 957586.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(7, 0, 'Tuition', 1220000.00, 326613.00, 893387.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(8, 0, 'Tuition', 1220000.00, 645824.00, 574176.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(9, 0, 'Tuition', 1220000.00, 449280.00, 770720.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(10, 0, 'Tuition', 1220000.00, 908928.00, 311072.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(11, 0, 'Tuition', 1220000.00, 596803.00, 623197.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(12, 0, 'Tuition', 1220000.00, 857232.00, 362768.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(13, 0, 'Tuition', 1220000.00, 695749.00, 524251.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(14, 0, 'Tuition', 1220000.00, 707052.00, 512948.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(15, 0, 'Tuition', 1220000.00, 448012.00, 771988.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(16, 0, 'Tuition', 1220000.00, 718906.00, 501094.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(17, 0, 'Tuition', 1220000.00, 450493.00, 769507.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(18, 0, 'Tuition', 1220000.00, 695748.00, 524252.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(19, 0, 'Tuition', 1220000.00, 327261.00, 892739.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(20, 0, 'Tuition', 1220000.00, 949063.00, 270937.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(21, 0, 'Tuition', 1150000.00, 363530.00, 786470.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(22, 0, 'Tuition', 1150000.00, 370462.00, 779538.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(23, 0, 'Tuition', 1150000.00, 561720.00, 588280.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(24, 0, 'Tuition', 1150000.00, 697215.00, 452785.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(25, 0, 'Tuition', 1150000.00, 800916.00, 349084.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(26, 0, 'Tuition', 1150000.00, 912937.00, 237063.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(27, 0, 'Tuition', 1150000.00, 361935.00, 788065.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(28, 0, 'Tuition', 1150000.00, 470866.00, 679134.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(29, 0, 'Tuition', 1150000.00, 268524.00, 881476.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(30, 0, 'Tuition', 1150000.00, 530025.00, 619975.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(31, 0, 'Tuition', 1150000.00, 844553.00, 305447.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(32, 0, 'Tuition', 1150000.00, 832690.00, 317310.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(33, 0, 'Tuition', 1150000.00, 629793.00, 520207.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(34, 0, 'Tuition', 1150000.00, 450895.00, 699105.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(35, 0, 'Tuition', 1150000.00, 965095.00, 184905.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(36, 0, 'Tuition', 1150000.00, 872790.00, 277210.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(37, 0, 'Tuition', 1150000.00, 468665.00, 681335.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(38, 0, 'Tuition', 1150000.00, 324958.00, 825042.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(39, 0, 'Tuition', 1150000.00, 818793.00, 331207.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(40, 0, 'Tuition', 1150000.00, 519092.00, 630908.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(41, 0, 'Tuition', 1625000.00, 739083.00, 885917.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(42, 0, 'Tuition', 1625000.00, 338139.00, 1286861.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(43, 0, 'Tuition', 1625000.00, 873446.00, 751554.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(44, 0, 'Tuition', 1625000.00, 752812.00, 872188.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(45, 0, 'Tuition', 1625000.00, 943724.00, 681276.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(46, 0, 'Tuition', 1625000.00, 660183.00, 964817.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(47, 0, 'Tuition', 1625000.00, 269744.00, 1355256.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(48, 0, 'Tuition', 1625000.00, 768173.00, 856827.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(49, 0, 'Tuition', 1625000.00, 431634.00, 1193366.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(50, 0, 'Tuition', 1625000.00, 453649.00, 1171351.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(51, 0, 'Tuition', 1625000.00, 773345.00, 851655.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(52, 0, 'Tuition', 1625000.00, 705778.00, 919222.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(53, 0, 'Tuition', 1625000.00, 208856.00, 1416144.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(54, 0, 'Tuition', 1625000.00, 326944.00, 1298056.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(55, 0, 'Tuition', 1625000.00, 808156.00, 816844.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(56, 0, 'Tuition', 1625000.00, 459948.00, 1165052.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(57, 0, 'Tuition', 1625000.00, 475270.00, 1149730.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(58, 0, 'Tuition', 1625000.00, 796509.00, 828491.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(59, 0, 'Tuition', 1625000.00, 756736.00, 868264.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(60, 0, 'Tuition', 1625000.00, 394153.00, 1230847.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(61, 0, 'Tuition', 1625000.00, 300555.00, 1324445.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(62, 0, 'Tuition', 1625000.00, 920320.00, 704680.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(63, 0, 'Tuition', 1625000.00, 299933.00, 1325067.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(64, 0, 'Tuition', 1625000.00, 938707.00, 686293.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(65, 0, 'Tuition', 1625000.00, 393734.00, 1231266.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(66, 0, 'Tuition', 1625000.00, 552552.00, 1072448.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(67, 0, 'Tuition', 1625000.00, 581556.00, 1043444.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(68, 0, 'Tuition', 1625000.00, 250125.00, 1374875.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(69, 0, 'Tuition', 1625000.00, 905957.00, 719043.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(70, 0, 'Tuition', 1625000.00, 379413.00, 1245587.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(71, 0, 'Tuition', 1685000.00, 579193.00, 1105807.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(72, 0, 'Tuition', 1685000.00, 757728.00, 927272.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(73, 0, 'Tuition', 1685000.00, 251061.00, 1433939.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(74, 0, 'Tuition', 1685000.00, 382121.00, 1302879.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(75, 0, 'Tuition', 1685000.00, 957424.00, 727576.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(76, 0, 'Tuition', 1685000.00, 240758.00, 1444242.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(77, 0, 'Tuition', 1685000.00, 531517.00, 1153483.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(78, 0, 'Tuition', 1685000.00, 935312.00, 749688.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(79, 0, 'Tuition', 1685000.00, 482011.00, 1202989.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(80, 0, 'Tuition', 1685000.00, 204117.00, 1480883.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(81, 0, 'Tuition', 1685000.00, 974552.00, 710448.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(82, 0, 'Tuition', 1685000.00, 860413.00, 824587.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(83, 0, 'Tuition', 1685000.00, 378406.00, 1306594.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(84, 0, 'Tuition', 1685000.00, 710792.00, 974208.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(85, 0, 'Tuition', 1685000.00, 618741.00, 1066259.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(86, 0, 'Tuition', 1685000.00, 761332.00, 923668.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(87, 0, 'Tuition', 1685000.00, 950435.00, 734565.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(88, 0, 'Tuition', 1685000.00, 668181.00, 1016819.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(89, 0, 'Tuition', 1685000.00, 289601.00, 1395399.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(90, 0, 'Tuition', 1685000.00, 843461.00, 841539.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(91, 0, 'Tuition', 1500000.00, 748502.00, 751498.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(92, 0, 'Tuition', 1500000.00, 212128.00, 1287872.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(93, 0, 'Tuition', 1500000.00, 215136.00, 1284864.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(94, 0, 'Tuition', 1500000.00, 239295.00, 1260705.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(95, 0, 'Tuition', 1500000.00, 351069.00, 1148931.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(96, 0, 'Tuition', 1500000.00, 837462.00, 662538.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(97, 0, 'Tuition', 1500000.00, 534101.00, 965899.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(98, 0, 'Tuition', 1500000.00, 758119.00, 741881.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(99, 0, 'Tuition', 1500000.00, 388294.00, 1111706.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(100, 0, 'Tuition', 1500000.00, 267115.00, 1232885.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(101, 0, 'Tuition', 1500000.00, 770692.00, 729308.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(102, 0, 'Tuition', 1500000.00, 452116.00, 1047884.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(103, 0, 'Tuition', 1500000.00, 548506.00, 951494.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(104, 0, 'Tuition', 1500000.00, 386179.00, 1113821.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(105, 0, 'Tuition', 1500000.00, 885380.00, 614620.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(106, 0, 'Tuition', 1500000.00, 668364.00, 831636.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(107, 0, 'Tuition', 1500000.00, 485678.00, 1014322.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(108, 0, 'Tuition', 1500000.00, 223302.00, 1276698.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(109, 0, 'Tuition', 1500000.00, 259474.00, 1240526.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(110, 0, 'Tuition', 1500000.00, 427465.00, 1072535.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(111, 0, 'Tuition', 1500000.00, 358903.00, 1141097.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(112, 0, 'Tuition', 1500000.00, 312120.00, 1187880.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(113, 0, 'Tuition', 1500000.00, 283893.00, 1216107.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(114, 0, 'Tuition', 1500000.00, 283106.00, 1216894.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(115, 0, 'Tuition', 1500000.00, 363851.00, 1136149.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(116, 0, 'Tuition', 1500000.00, 769938.00, 730062.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(117, 0, 'Tuition', 1500000.00, 958137.00, 541863.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(118, 0, 'Tuition', 1500000.00, 680872.00, 819128.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(119, 0, 'Tuition', 1500000.00, 329949.00, 1170051.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(120, 0, 'Tuition', 1500000.00, 207130.00, 1292870.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(121, 0, 'Tuition', 1500000.00, 645804.00, 854196.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(122, 0, 'Tuition', 1500000.00, 807632.00, 692368.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(123, 0, 'Tuition', 1500000.00, 300747.00, 1199253.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(124, 0, 'Tuition', 1500000.00, 480838.00, 1019162.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(125, 0, 'Tuition', 1500000.00, 501952.00, 998048.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(126, 0, 'Tuition', 1500000.00, 867246.00, 632754.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(127, 0, 'Tuition', 1500000.00, 230374.00, 1269626.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(128, 0, 'Tuition', 1500000.00, 750131.00, 749869.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(129, 0, 'Tuition', 1500000.00, 459535.00, 1040465.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(130, 0, 'Tuition', 1500000.00, 647283.00, 852717.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(131, 0, 'Tuition', 1500000.00, 857810.00, 642190.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(132, 0, 'Tuition', 1500000.00, 547201.00, 952799.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(133, 0, 'Tuition', 1500000.00, 762577.00, 737423.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(134, 0, 'Tuition', 1500000.00, 371282.00, 1128718.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(135, 0, 'Tuition', 1500000.00, 968679.00, 531321.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(136, 0, 'Tuition', 1500000.00, 329551.00, 1170449.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(137, 0, 'Tuition', 1500000.00, 941719.00, 558281.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(138, 0, 'Tuition', 1500000.00, 319943.00, 1180057.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(139, 0, 'Tuition', 1500000.00, 974556.00, 525444.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(140, 0, 'Tuition', 1500000.00, 512954.00, 987046.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(141, 0, 'Tuition', 1500000.00, 241102.00, 1258898.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(142, 0, 'Tuition', 1500000.00, 266647.00, 1233353.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(143, 0, 'Tuition', 1500000.00, 409930.00, 1090070.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(144, 0, 'Tuition', 1500000.00, 249708.00, 1250292.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(145, 0, 'Tuition', 1500000.00, 618752.00, 881248.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(146, 0, 'Tuition', 1500000.00, 544634.00, 955366.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(147, 0, 'Tuition', 1500000.00, 666918.00, 833082.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(148, 0, 'Tuition', 1500000.00, 700687.00, 799313.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(149, 0, 'Tuition', 1500000.00, 502680.00, 997320.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14'),
(150, 0, 'Tuition', 1500000.00, 211341.00, 1288659.00, '2024/2025', 'Semester 1', '2024-09-30', 'Partial', '2026-07-03 04:51:14');

-- --------------------------------------------------------

--
-- Table structure for table `student_finance`
--

CREATE TABLE `student_finance` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_financial_profiles`
--

CREATE TABLE `student_financial_profiles` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `total_fees` decimal(14,2) DEFAULT 0.00,
  `total_paid` decimal(14,2) DEFAULT 0.00,
  `balance` decimal(14,2) DEFAULT 0.00,
  `scholarship_amount` decimal(14,2) DEFAULT 0.00,
  `fee_status` enum('unpaid','partial','paid','overdue') DEFAULT 'unpaid',
  `last_payment_date` date DEFAULT NULL,
  `next_due_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_health_incidents`
--

CREATE TABLE `student_health_incidents` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `incident_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `action_taken` text DEFAULT NULL,
  `reported_by` int(11) DEFAULT NULL,
  `incident_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_hostel_allocations`
--

CREATE TABLE `student_hostel_allocations` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `hostel_name` varchar(100) DEFAULT NULL,
  `room_number` varchar(20) DEFAULT NULL,
  `allocation_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `monthly_fee` decimal(10,2) DEFAULT 0.00,
  `status` enum('Active','Vacated','Transferred') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_invoices`
--

CREATE TABLE `student_invoices` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `fee_assignment_id` int(11) DEFAULT NULL,
  `fee_type` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL,
  `discount_amount` decimal(12,2) DEFAULT 0.00,
  `net_amount` decimal(12,2) GENERATED ALWAYS AS (`total_amount` - `discount_amount`) STORED,
  `amount_paid` decimal(12,2) DEFAULT 0.00,
  `balance` decimal(12,2) GENERATED ALWAYS AS (`net_amount` - `amount_paid`) STORED,
  `status` enum('Draft','Pending','Partially Paid','Paid','Overdue','Cancelled','Waived') DEFAULT 'Pending',
  `due_date` date DEFAULT NULL,
  `issue_date` date DEFAULT curdate(),
  `payment_method` varchar(50) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_invoices`
--

INSERT INTO `student_invoices` (`id`, `invoice_number`, `student_id`, `fee_assignment_id`, `fee_type`, `description`, `total_amount`, `discount_amount`, `amount_paid`, `status`, `due_date`, `issue_date`, `payment_method`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1220000.00, 0.00, 848665.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(2, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1220000.00, 0.00, 609304.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(3, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1220000.00, 0.00, 400523.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(4, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1220000.00, 0.00, 874703.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(5, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1220000.00, 0.00, 671946.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(6, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1220000.00, 0.00, 635623.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(7, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1220000.00, 0.00, 262276.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(8, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1220000.00, 0.00, 104511.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(9, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1220000.00, 0.00, 435728.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(10, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1220000.00, 0.00, 165107.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(11, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1220000.00, 0.00, 218352.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(12, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1220000.00, 0.00, 496440.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(13, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1220000.00, 0.00, 127145.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(14, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1220000.00, 0.00, 646407.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(15, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1220000.00, 0.00, 350597.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(16, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1220000.00, 0.00, 513766.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(17, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1220000.00, 0.00, 617041.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(18, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1220000.00, 0.00, 643907.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(19, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1220000.00, 0.00, 468411.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(20, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1220000.00, 0.00, 310337.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(21, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1150000.00, 0.00, 846451.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(22, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1150000.00, 0.00, 801244.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(23, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1150000.00, 0.00, 566870.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(24, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1150000.00, 0.00, 330617.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(25, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1150000.00, 0.00, 652477.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(26, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1150000.00, 0.00, 570535.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(27, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1150000.00, 0.00, 795244.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(28, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1150000.00, 0.00, 564614.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(29, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1150000.00, 0.00, 337341.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(30, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1150000.00, 0.00, 692862.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(31, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1150000.00, 0.00, 752288.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(32, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1150000.00, 0.00, 782856.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(33, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1150000.00, 0.00, 757416.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(34, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1150000.00, 0.00, 538513.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(35, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1150000.00, 0.00, 320318.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(36, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1150000.00, 0.00, 686050.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(37, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1150000.00, 0.00, 769297.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(38, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1150000.00, 0.00, 888334.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(39, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1150000.00, 0.00, 433779.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(40, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1150000.00, 0.00, 203893.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(41, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1625000.00, 0.00, 418131.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(42, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1625000.00, 0.00, 578974.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(43, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1625000.00, 0.00, 740477.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(44, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1625000.00, 0.00, 265465.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(45, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1625000.00, 0.00, 605896.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(46, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1625000.00, 0.00, 533085.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(47, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1625000.00, 0.00, 747738.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(48, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1625000.00, 0.00, 439437.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(49, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1625000.00, 0.00, 653970.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(50, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1625000.00, 0.00, 251538.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(51, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1625000.00, 0.00, 795783.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(52, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1625000.00, 0.00, 724301.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(53, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1625000.00, 0.00, 334158.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(54, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1625000.00, 0.00, 197886.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(55, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1625000.00, 0.00, 686955.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(56, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1625000.00, 0.00, 341119.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(57, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1625000.00, 0.00, 344728.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(58, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1625000.00, 0.00, 600287.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(59, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1625000.00, 0.00, 267250.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(60, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1625000.00, 0.00, 235390.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(61, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1625000.00, 0.00, 275202.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(62, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1625000.00, 0.00, 569839.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(63, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1625000.00, 0.00, 323591.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(64, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1625000.00, 0.00, 608438.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(65, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1625000.00, 0.00, 371416.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(66, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1625000.00, 0.00, 731768.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(67, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1625000.00, 0.00, 844592.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(68, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1625000.00, 0.00, 327658.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(69, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1625000.00, 0.00, 604513.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(70, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1625000.00, 0.00, 339594.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(71, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 584429.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(72, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 203362.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(73, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 763527.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(74, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 707549.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(75, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 347163.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(76, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 313168.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(77, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 424353.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(78, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 282263.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(79, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 838255.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(80, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 844487.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(81, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 807668.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(82, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 604883.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(83, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 501410.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(84, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 592400.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(85, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 557773.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(86, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 111664.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(87, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 385001.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(88, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 690015.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(89, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 595074.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(90, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 805322.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(91, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 541392.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(92, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 190992.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(93, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 830787.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(94, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 280959.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(95, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 412435.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(96, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 319296.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(97, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 259177.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(98, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 237997.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(99, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 312455.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(100, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 748286.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(101, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 304064.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(102, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 775464.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(103, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 465126.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(104, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 699241.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(105, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 400826.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(106, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 606406.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(107, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 129554.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(108, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 328554.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(109, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 354106.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(110, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 684871.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(111, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 662035.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(112, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 355562.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(113, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 491709.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(114, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 491856.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(115, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 884157.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(116, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 445214.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(117, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 273601.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(118, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 732363.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(119, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 341014.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(120, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 207979.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(121, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 716857.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(122, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 460346.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(123, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 851162.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(124, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 374770.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(125, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 820363.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(126, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 477508.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(127, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 626453.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(128, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 799740.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(129, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 419342.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(130, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 397492.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(131, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 629432.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(132, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 254687.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(133, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 885139.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(134, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 361632.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(135, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 652747.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(136, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 478837.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(137, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 335947.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(138, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 143225.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(139, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 408283.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(140, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 711739.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(141, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 633849.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(142, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 134026.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(143, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 268585.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(144, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 840847.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(145, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 898480.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(146, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 269861.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(147, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 153866.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(148, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 659746.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(149, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 337135.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46'),
(150, 'INV000000-S1', 0, NULL, 'Tuition', NULL, 1500000.00, 0.00, 406435.00, 'Partially Paid', '2024-09-30', '2024-08-01', NULL, NULL, '2026-07-03 04:51:14', '2026-07-14 16:54:46');

-- --------------------------------------------------------

--
-- Table structure for table `student_login_attempts`
--

CREATE TABLE `student_login_attempts` (
  `id` int(11) NOT NULL,
  `student_number` varchar(50) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `attempted_at` datetime DEFAULT current_timestamp(),
  `success` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `student_login_view`
-- (See below for the actual view)
--
CREATE TABLE `student_login_view` (
`id` int(11)
,`student_number` varchar(50)
,`full_name` varchar(302)
,`email` varchar(100)
,`password` varchar(255)
,`course` varchar(100)
,`status` enum('Active','Inactive','Graduated','Suspended','Withdrawn','deleted')
,`last_login` timestamp
,`login_attempts` int(11)
,`is_first_login` tinyint(1)
);

-- --------------------------------------------------------

--
-- Table structure for table `student_medical`
--

CREATE TABLE `student_medical` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `blood_group` varchar(10) DEFAULT NULL,
  `allergies` text DEFAULT NULL,
  `medical_conditions` text DEFAULT NULL,
  `medications` text DEFAULT NULL,
  `emergency_medical_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_messages`
--

CREATE TABLE `student_messages` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `department_email` varchar(100) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `replied` tinyint(1) DEFAULT 0,
  `reply_message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `replied_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_notifications`
--

CREATE TABLE `student_notifications` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `type` enum('General','Academic','Fee','Attendance','Exam','Event','Matron','Bursar') DEFAULT 'General',
  `priority` enum('Low','Medium','High','Urgent') DEFAULT 'Medium',
  `is_read` tinyint(1) DEFAULT 0,
  `action_url` varchar(500) DEFAULT NULL,
  `link_type` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `link_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_password_resets`
--

CREATE TABLE `student_password_resets` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `reset_token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_penalties`
--

CREATE TABLE `student_penalties` (
  `id` int(11) NOT NULL,
  `penalty_number` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `penalty_type` varchar(100) NOT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `reason` text DEFAULT NULL,
  `applied_by` int(11) DEFAULT NULL,
  `applied_date` timestamp NULL DEFAULT current_timestamp(),
  `waived` tinyint(1) DEFAULT 0,
  `waived_by` int(11) DEFAULT NULL,
  `waived_at` timestamp NULL DEFAULT NULL,
  `waiver_reason` text DEFAULT NULL,
  `status` enum('Active','Waived','Paid') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_profiles`
--

CREATE TABLE `student_profiles` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `bio` text DEFAULT NULL,
  `interests` text DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `achievements` text DEFAULT NULL,
  `education_background` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_requests`
--

CREATE TABLE `student_requests` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `request_type` enum('Leave of Absence','Deferral','Transfer','Withdrawal','Other') NOT NULL,
  `reason` text NOT NULL,
  `supporting_doc` varchar(500) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `review_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_requirements_status`
--

CREATE TABLE `student_requirements_status` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `requirement_id` int(11) NOT NULL,
  `status` enum('Not Submitted','Pending','Submitted','Verified','Rejected','Missing') DEFAULT 'Not Submitted',
  `remarks` text DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_semester_gpa`
--

CREATE TABLE `student_semester_gpa` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `total_credits` int(11) DEFAULT 0,
  `earned_credits` int(11) DEFAULT 0,
  `semester_gpa` decimal(4,2) DEFAULT NULL,
  `cumulative_gpa` decimal(4,2) DEFAULT NULL,
  `academic_standing` enum('Good Standing','Probation','Dismissed','Suspended','Graduated') DEFAULT 'Good Standing',
  `credits_attempted` int(11) DEFAULT 0,
  `credits_passed` int(11) DEFAULT 0,
  `courses_completed` int(11) DEFAULT 0,
  `courses_failed` int(11) DEFAULT 0,
  `calculated_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `student_semester_gpa`
--

INSERT INTO `student_semester_gpa` (`id`, `student_id`, `academic_year`, `semester`, `total_credits`, `earned_credits`, `semester_gpa`, `cumulative_gpa`, `academic_standing`, `credits_attempted`, `credits_passed`, `courses_completed`, `courses_failed`, `calculated_at`, `created_at`) VALUES
(1, 0, '2024/2025', 'Semester 1', 18, 13, 3.36, 2.09, 'Good Standing', 18, 15, 6, 1, NULL, '2026-07-03 04:51:14');

-- --------------------------------------------------------

--
-- Table structure for table `student_sick_leave`
--

CREATE TABLE `student_sick_leave` (
  `id` int(11) NOT NULL,
  `leave_number` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `student_name` varchar(300) NOT NULL,
  `student_number` varchar(50) DEFAULT NULL,
  `program` varchar(100) DEFAULT NULL,
  `year_of_study` int(11) DEFAULT NULL,
  `sickness_id` int(11) DEFAULT NULL,
  `sickness_name` varchar(255) DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `leave_from` date NOT NULL,
  `leave_to` date NOT NULL,
  `total_days` int(11) GENERATED ALWAYS AS (to_days(`leave_to`) - to_days(`leave_from`) + 1) STORED,
  `leave_type` enum('Medical','Sick','Maternity','Injury','Quarantine','Other') DEFAULT 'Sick',
  `recommended_by` varchar(200) NOT NULL,
  `recommender_title` varchar(100) DEFAULT NULL,
  `approved_by` varchar(200) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Expired','Extended') DEFAULT 'Pending',
  `extended_to` date DEFAULT NULL,
  `extension_reason` text DEFAULT NULL,
  `doctor_notes` text DEFAULT NULL,
  `bed_rest_required` tinyint(1) DEFAULT 1,
  `parent_guardian_notified` tinyint(1) DEFAULT 0,
  `matron_notified` tinyint(1) DEFAULT 0,
  `class_teacher_notified` tinyint(1) DEFAULT 0,
  `documents_submitted` tinyint(1) DEFAULT 0,
  `documents_path` varchar(500) DEFAULT NULL,
  `return_date_actual` date DEFAULT NULL,
  `return_notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_status_history`
--

CREATE TABLE `student_status_history` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `status_type` varchar(50) NOT NULL,
  `old_value` varchar(100) DEFAULT NULL,
  `new_value` varchar(100) DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_timetables`
--

CREATE TABLE `student_timetables` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `time_slot` varchar(50) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `lecturer` varchar(100) DEFAULT NULL,
  `classroom` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_welfare_cases`
--

CREATE TABLE `student_welfare_cases` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `case_type` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `status` enum('open','in_progress','resolved','closed') DEFAULT 'open',
  `assigned_to` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscription_deductions`
--

CREATE TABLE `subscription_deductions` (
  `id` int(11) NOT NULL,
  `subscription_id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `installment_number` int(11) NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `due_date` date NOT NULL,
  `processed_date` datetime DEFAULT NULL,
  `status` enum('pending','success','failed','skipped') NOT NULL DEFAULT 'pending',
  `payment_reference` varchar(50) DEFAULT NULL,
  `payment_id` int(11) DEFAULT NULL COMMENT 'FK to payments.id if successful',
  `failure_reason` text DEFAULT NULL,
  `attempt_count` int(11) DEFAULT 0,
  `last_attempt_date` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `supplier_name` varchar(300) DEFAULT NULL,
  `contact_person` varchar(200) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `performance_rating` decimal(5,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplier_payments`
--

CREATE TABLE `supplier_payments` (
  `id` int(11) NOT NULL,
  `supplier_id` int(11) DEFAULT 0,
  `payment_number` varchar(100) DEFAULT NULL,
  `amount` decimal(14,2) DEFAULT 0.00,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `invoice_ref` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_by` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `syllabus`
--

CREATE TABLE `syllabus` (
  `s_no` int(11) NOT NULL,
  `class` varchar(50) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `file` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_modules`
--

CREATE TABLE `system_modules` (
  `id` int(11) NOT NULL,
  `module_key` varchar(100) NOT NULL,
  `module_name` varchar(255) NOT NULL,
  `icon` varchar(100) DEFAULT 'fas fa-puzzle-piece',
  `parent_id` int(11) DEFAULT NULL,
  `route` varchar(500) DEFAULT NULL,
  `order_index` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timetable`
--

CREATE TABLE `timetable` (
  `id` int(11) NOT NULL,
  `program` varchar(100) NOT NULL,
  `year_of_study` int(11) DEFAULT 1,
  `semester` varchar(50) DEFAULT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') NOT NULL,
  `time_slot` varchar(50) NOT NULL,
  `subject` varchar(150) NOT NULL,
  `course_code` varchar(30) DEFAULT NULL,
  `lecturer` varchar(150) DEFAULT NULL,
  `room` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `timetable`
--

INSERT INTO `timetable` (`id`, `program`, `year_of_study`, `semester`, `day_of_week`, `time_slot`, `subject`, `course_code`, `lecturer`, `room`, `academic_year`, `created_by`, `created_at`) VALUES
(1, 'Certificate in Nursing', 1, 'Semester 1', 'Monday', '08:00-10:00', 'Fundamentals of Nursing I', 'CNN101', 'Sr. Nakamya Florence', 'Lecture Hall A', '2024/2025', NULL, '2026-07-03 04:05:12'),
(2, 'Certificate in Nursing', 1, 'Semester 1', 'Wednesday', '10:00-12:00', 'Fundamentals of Nursing I', 'CNN101', 'Sr. Nakamya Florence', 'Skills Lab 1', '2024/2025', NULL, '2026-07-03 04:05:12'),
(3, 'Certificate in Nursing', 1, 'Semester 1', 'Tuesday', '08:00-10:00', 'Anatomy & Physiology I', 'CNN102', 'Mr. Okello David', 'Lecture Hall B', '2024/2025', NULL, '2026-07-03 04:05:12'),
(4, 'Certificate in Nursing', 1, 'Semester 1', 'Thursday', '14:00-16:00', 'Anatomy & Physiology I', 'CNN102', 'Mr. Okello David', 'Lecture Hall B', '2024/2025', NULL, '2026-07-03 04:05:12'),
(5, 'Certificate in Nursing', 1, 'Semester 1', 'Wednesday', '08:00-10:00', 'Community Health Nursing I', 'CNN103', 'Mrs. Nabirye Sarah', 'Lecture Hall A', '2024/2025', NULL, '2026-07-03 04:05:12'),
(6, 'Certificate in Nursing', 1, 'Semester 1', 'Friday', '08:00-12:00', 'Community Health Nursing I', 'CNN103', 'Mrs. Nabirye Sarah', 'Community Site', '2024/2025', NULL, '2026-07-03 04:05:12'),
(7, 'Certificate in Midwifery', 1, 'Semester 1', 'Monday', '10:00-12:00', 'Introduction to Midwifery', 'CNM101', 'Mrs. Musimenta Grace', 'Lecture Hall A', '2024/2025', NULL, '2026-07-03 04:05:12'),
(8, 'Certificate in Midwifery', 1, 'Semester 1', 'Thursday', '08:00-10:00', 'Introduction to Midwifery', 'CNM101', 'Mrs. Musimenta Grace', 'Lecture Hall A', '2024/2025', NULL, '2026-07-03 04:05:12'),
(9, 'Certificate in Midwifery', 1, 'Semester 1', 'Tuesday', '10:00-12:00', 'Anatomy for Midwives', 'CNM102', 'Mr. Okello David', 'Lecture Hall B', '2024/2025', NULL, '2026-07-03 04:05:12'),
(10, 'Certificate in Midwifery', 1, 'Semester 1', 'Wednesday', '14:00-16:00', 'Fundamentals of Midwifery Care', 'CNM103', 'Mrs. Musimenta Grace', 'Skills Lab 2', '2024/2025', NULL, '2026-07-03 04:05:12'),
(11, 'Certificate in Midwifery', 1, 'Semester 1', 'Friday', '10:00-12:00', 'Fundamentals of Midwifery Care', 'CNM103', 'Mrs. Musimenta Grace', 'Skills Lab 2', '2024/2025', NULL, '2026-07-03 04:05:12'),
(12, 'Diploma in Nursing', 1, 'Semester 1', 'Monday', '08:00-10:00', 'Nursing Science I', 'DNM101', 'Dr. Mubiru John', 'Lecture Hall C', '2024/2025', NULL, '2026-07-03 04:05:12'),
(13, 'Diploma in Nursing', 1, 'Semester 1', 'Thursday', '10:00-12:00', 'Nursing Science I', 'DNM101', 'Dr. Mubiru John', 'Lecture Hall C', '2024/2025', NULL, '2026-07-03 04:05:12'),
(14, 'Diploma in Nursing', 1, 'Semester 1', 'Tuesday', '14:00-16:00', 'Human Anatomy & Physiology I', 'DNM102', 'Mr. Okello David', 'Lecture Hall B', '2024/2025', NULL, '2026-07-03 04:05:12'),
(15, 'Diploma in Nursing', 1, 'Semester 1', 'Wednesday', '10:00-12:00', 'Nutrition & Dietetics', 'DNM103', 'Mrs. Nalwoga Christine', 'Lecture Hall C', '2024/2025', NULL, '2026-07-03 04:05:12'),
(16, 'Diploma in Nursing', 1, 'Semester 2', 'Monday', '14:00-16:00', 'Medical Surgical Nursing I', 'DNM104', 'Sr. Nakamya Florence', 'Skills Lab 1', '2024/2025', NULL, '2026-07-03 04:05:12'),
(17, 'Diploma in Nursing', 1, 'Semester 2', 'Friday', '08:00-12:00', 'Medical Surgical Nursing I', 'DNM104', 'Sr. Nakamya Florence', 'Ward 3', '2024/2025', NULL, '2026-07-03 04:05:12'),
(18, 'Diploma in Midwifery', 1, 'Semester 1', 'Tuesday', '08:00-10:00', 'Midwifery Science I', 'DMM101', 'Mrs. Musimenta Grace', 'Lecture Hall A', '2024/2025', NULL, '2026-07-03 04:05:12'),
(19, 'Diploma in Midwifery', 1, 'Semester 1', 'Wednesday', '08:00-10:00', 'Anatomy for Midwives', 'DMM102', 'Mr. Okello David', 'Lecture Hall B', '2024/2025', NULL, '2026-07-03 04:05:12'),
(20, 'Diploma in Midwifery', 1, 'Semester 1', 'Friday', '14:00-16:00', 'Reproductive Health', 'DMM103', 'Mrs. Musimenta Grace', 'Lecture Hall A', '2024/2025', NULL, '2026-07-03 04:05:12'),
(21, 'Diploma in Nursing Education', 1, 'Semester 1', 'Monday', '10:00-12:00', 'Foundations of Education', 'DNE101', 'Dr. Waswa Robert', 'Lecture Hall D', '2024/2025', NULL, '2026-07-03 04:05:12'),
(22, 'Diploma in Nursing Education', 1, 'Semester 1', 'Thursday', '14:00-16:00', 'Educational Psychology', 'DNE102', 'Dr. Waswa Robert', 'Lecture Hall D', '2024/2025', NULL, '2026-07-03 04:05:12'),
(23, 'Diploma in Nursing', 2, 'Semester 3', 'Monday', '08:00-10:00', 'Medical Surgical Nursing II', 'DNM201', 'Dr. Mubiru John', 'Lecture Hall C', '2024/2025', NULL, '2026-07-03 04:05:12'),
(24, 'Diploma in Nursing', 2, 'Semester 3', 'Wednesday', '14:00-16:00', 'Pediatric Nursing', 'DNM202', 'Sr. Nakamya Florence', 'Lecture Hall A', '2024/2025', NULL, '2026-07-03 04:05:12'),
(25, 'Diploma in Nursing', 2, 'Semester 3', 'Friday', '10:00-12:00', 'Psychiatric Nursing', 'DNM203', 'Mrs. Nabirye Sarah', 'Lecture Hall B', '2024/2025', NULL, '2026-07-03 04:05:12'),
(26, 'Diploma in Nursing', 3, 'Semester 5', 'Tuesday', '08:00-12:00', 'Clinical Practicum I', 'DNM304', 'Head of Nursing', 'Iganga RRH', '2024/2025', NULL, '2026-07-03 04:05:12'),
(27, 'Diploma in Nursing', 3, 'Semester 5', 'Thursday', '10:00-12:00', 'Nursing Management & Leadership', 'DNM303', 'Dr. Mubiru John', 'Lecture Hall C', '2024/2025', NULL, '2026-07-03 04:05:12'),
(28, 'Certificate in Nursing', 1, 'Semester 1', 'Monday', '08:00-10:00', 'Fundamentals of Nursing I', 'CNN101', 'Sr. Nakamya Florence', 'Lecture Hall A', '2024/2025', NULL, '2026-07-03 04:38:06'),
(29, 'Certificate in Nursing', 1, 'Semester 1', 'Wednesday', '10:00-12:00', 'Fundamentals of Nursing I', 'CNN101', 'Sr. Nakamya Florence', 'Skills Lab 1', '2024/2025', NULL, '2026-07-03 04:38:06'),
(30, 'Certificate in Nursing', 1, 'Semester 1', 'Tuesday', '08:00-10:00', 'Anatomy & Physiology I', 'CNN102', 'Mr. Okello David', 'Lecture Hall B', '2024/2025', NULL, '2026-07-03 04:38:06'),
(31, 'Certificate in Nursing', 1, 'Semester 1', 'Thursday', '14:00-16:00', 'Anatomy & Physiology I', 'CNN102', 'Mr. Okello David', 'Lecture Hall B', '2024/2025', NULL, '2026-07-03 04:38:06'),
(32, 'Certificate in Nursing', 1, 'Semester 1', 'Wednesday', '08:00-10:00', 'Community Health Nursing I', 'CNN103', 'Mrs. Nabirye Sarah', 'Lecture Hall A', '2024/2025', NULL, '2026-07-03 04:38:06'),
(33, 'Certificate in Nursing', 1, 'Semester 1', 'Friday', '08:00-12:00', 'Community Health Nursing I', 'CNN103', 'Mrs. Nabirye Sarah', 'Community Site', '2024/2025', NULL, '2026-07-03 04:38:06'),
(34, 'Certificate in Midwifery', 1, 'Semester 1', 'Monday', '10:00-12:00', 'Introduction to Midwifery', 'CNM101', 'Mrs. Musimenta Grace', 'Lecture Hall A', '2024/2025', NULL, '2026-07-03 04:38:06'),
(35, 'Certificate in Midwifery', 1, 'Semester 1', 'Thursday', '08:00-10:00', 'Introduction to Midwifery', 'CNM101', 'Mrs. Musimenta Grace', 'Lecture Hall A', '2024/2025', NULL, '2026-07-03 04:38:06'),
(36, 'Certificate in Midwifery', 1, 'Semester 1', 'Tuesday', '10:00-12:00', 'Anatomy for Midwives', 'CNM102', 'Mr. Okello David', 'Lecture Hall B', '2024/2025', NULL, '2026-07-03 04:38:06'),
(37, 'Certificate in Midwifery', 1, 'Semester 1', 'Wednesday', '14:00-16:00', 'Fundamentals of Midwifery Care', 'CNM103', 'Mrs. Musimenta Grace', 'Skills Lab 2', '2024/2025', NULL, '2026-07-03 04:38:06'),
(38, 'Certificate in Midwifery', 1, 'Semester 1', 'Friday', '10:00-12:00', 'Fundamentals of Midwifery Care', 'CNM103', 'Mrs. Musimenta Grace', 'Skills Lab 2', '2024/2025', NULL, '2026-07-03 04:38:06'),
(39, 'Diploma in Nursing', 1, 'Semester 1', 'Monday', '08:00-10:00', 'Nursing Science I', 'DNM101', 'Dr. Mubiru John', 'Lecture Hall C', '2024/2025', NULL, '2026-07-03 04:38:06'),
(40, 'Diploma in Nursing', 1, 'Semester 1', 'Thursday', '10:00-12:00', 'Nursing Science I', 'DNM101', 'Dr. Mubiru John', 'Lecture Hall C', '2024/2025', NULL, '2026-07-03 04:38:06'),
(41, 'Diploma in Nursing', 1, 'Semester 1', 'Tuesday', '14:00-16:00', 'Human Anatomy & Physiology I', 'DNM102', 'Mr. Okello David', 'Lecture Hall B', '2024/2025', NULL, '2026-07-03 04:38:06'),
(42, 'Diploma in Nursing', 1, 'Semester 1', 'Wednesday', '10:00-12:00', 'Nutrition & Dietetics', 'DNM103', 'Mrs. Nalwoga Christine', 'Lecture Hall C', '2024/2025', NULL, '2026-07-03 04:38:06'),
(43, 'Diploma in Nursing', 1, 'Semester 2', 'Monday', '14:00-16:00', 'Medical Surgical Nursing I', 'DNM104', 'Sr. Nakamya Florence', 'Skills Lab 1', '2024/2025', NULL, '2026-07-03 04:38:06'),
(44, 'Diploma in Nursing', 1, 'Semester 2', 'Friday', '08:00-12:00', 'Medical Surgical Nursing I', 'DNM104', 'Sr. Nakamya Florence', 'Ward 3', '2024/2025', NULL, '2026-07-03 04:38:06'),
(45, 'Diploma in Midwifery', 1, 'Semester 1', 'Tuesday', '08:00-10:00', 'Midwifery Science I', 'DMM101', 'Mrs. Musimenta Grace', 'Lecture Hall A', '2024/2025', NULL, '2026-07-03 04:38:06'),
(46, 'Diploma in Midwifery', 1, 'Semester 1', 'Wednesday', '08:00-10:00', 'Anatomy for Midwives', 'DMM102', 'Mr. Okello David', 'Lecture Hall B', '2024/2025', NULL, '2026-07-03 04:38:06'),
(47, 'Diploma in Midwifery', 1, 'Semester 1', 'Friday', '14:00-16:00', 'Reproductive Health', 'DMM103', 'Mrs. Musimenta Grace', 'Lecture Hall A', '2024/2025', NULL, '2026-07-03 04:38:06'),
(48, 'Diploma in Nursing Education', 1, 'Semester 1', 'Monday', '10:00-12:00', 'Foundations of Education', 'DNE101', 'Dr. Waswa Robert', 'Lecture Hall D', '2024/2025', NULL, '2026-07-03 04:38:06'),
(49, 'Diploma in Nursing Education', 1, 'Semester 1', 'Thursday', '14:00-16:00', 'Educational Psychology', 'DNE102', 'Dr. Waswa Robert', 'Lecture Hall D', '2024/2025', NULL, '2026-07-03 04:38:06'),
(50, 'Diploma in Nursing', 2, 'Semester 3', 'Monday', '08:00-10:00', 'Medical Surgical Nursing II', 'DNM201', 'Dr. Mubiru John', 'Lecture Hall C', '2024/2025', NULL, '2026-07-03 04:38:06'),
(51, 'Diploma in Nursing', 2, 'Semester 3', 'Wednesday', '14:00-16:00', 'Pediatric Nursing', 'DNM202', 'Sr. Nakamya Florence', 'Lecture Hall A', '2024/2025', NULL, '2026-07-03 04:38:06'),
(52, 'Diploma in Nursing', 2, 'Semester 3', 'Friday', '10:00-12:00', 'Psychiatric Nursing', 'DNM203', 'Mrs. Nabirye Sarah', 'Lecture Hall B', '2024/2025', NULL, '2026-07-03 04:38:06'),
(53, 'Diploma in Nursing', 3, 'Semester 5', 'Tuesday', '08:00-12:00', 'Clinical Practicum I', 'DNM304', 'Head of Nursing', 'Iganga RRH', '2024/2025', NULL, '2026-07-03 04:38:06'),
(54, 'Diploma in Nursing', 3, 'Semester 5', 'Thursday', '10:00-12:00', 'Nursing Management & Leadership', 'DNM303', 'Dr. Mubiru John', 'Lecture Hall C', '2024/2025', NULL, '2026-07-03 04:38:06'),
(55, 'Certificate in Nursing', 1, 'Semester 1', 'Monday', '08:00-10:00', 'Fundamentals of Nursing I', 'CNN101', 'Sr. Nakamya Florence', 'Lecture Hall A', '2024/2025', NULL, '2026-07-03 04:51:14'),
(56, 'Certificate in Nursing', 1, 'Semester 1', 'Wednesday', '10:00-12:00', 'Fundamentals of Nursing I', 'CNN101', 'Sr. Nakamya Florence', 'Skills Lab 1', '2024/2025', NULL, '2026-07-03 04:51:14'),
(57, 'Certificate in Nursing', 1, 'Semester 1', 'Tuesday', '08:00-10:00', 'Anatomy & Physiology I', 'CNN102', 'Mr. Okello David', 'Lecture Hall B', '2024/2025', NULL, '2026-07-03 04:51:14'),
(58, 'Certificate in Nursing', 1, 'Semester 1', 'Thursday', '14:00-16:00', 'Anatomy & Physiology I', 'CNN102', 'Mr. Okello David', 'Lecture Hall B', '2024/2025', NULL, '2026-07-03 04:51:14'),
(59, 'Certificate in Nursing', 1, 'Semester 1', 'Wednesday', '08:00-10:00', 'Community Health Nursing I', 'CNN103', 'Mrs. Nabirye Sarah', 'Lecture Hall A', '2024/2025', NULL, '2026-07-03 04:51:14'),
(60, 'Certificate in Nursing', 1, 'Semester 1', 'Friday', '08:00-12:00', 'Community Health Nursing I', 'CNN103', 'Mrs. Nabirye Sarah', 'Community Site', '2024/2025', NULL, '2026-07-03 04:51:14'),
(61, 'Certificate in Midwifery', 1, 'Semester 1', 'Monday', '10:00-12:00', 'Introduction to Midwifery', 'CNM101', 'Mrs. Musimenta Grace', 'Lecture Hall A', '2024/2025', NULL, '2026-07-03 04:51:14'),
(62, 'Certificate in Midwifery', 1, 'Semester 1', 'Thursday', '08:00-10:00', 'Introduction to Midwifery', 'CNM101', 'Mrs. Musimenta Grace', 'Lecture Hall A', '2024/2025', NULL, '2026-07-03 04:51:14'),
(63, 'Certificate in Midwifery', 1, 'Semester 1', 'Tuesday', '10:00-12:00', 'Anatomy for Midwives', 'CNM102', 'Mr. Okello David', 'Lecture Hall B', '2024/2025', NULL, '2026-07-03 04:51:14'),
(64, 'Certificate in Midwifery', 1, 'Semester 1', 'Wednesday', '14:00-16:00', 'Fundamentals of Midwifery Care', 'CNM103', 'Mrs. Musimenta Grace', 'Skills Lab 2', '2024/2025', NULL, '2026-07-03 04:51:14'),
(65, 'Certificate in Midwifery', 1, 'Semester 1', 'Friday', '10:00-12:00', 'Fundamentals of Midwifery Care', 'CNM103', 'Mrs. Musimenta Grace', 'Skills Lab 2', '2024/2025', NULL, '2026-07-03 04:51:14'),
(66, 'Diploma in Nursing', 1, 'Semester 1', 'Monday', '08:00-10:00', 'Nursing Science I', 'DNM101', 'Dr. Mubiru John', 'Lecture Hall C', '2024/2025', NULL, '2026-07-03 04:51:14'),
(67, 'Diploma in Nursing', 1, 'Semester 1', 'Thursday', '10:00-12:00', 'Nursing Science I', 'DNM101', 'Dr. Mubiru John', 'Lecture Hall C', '2024/2025', NULL, '2026-07-03 04:51:14'),
(68, 'Diploma in Nursing', 1, 'Semester 1', 'Tuesday', '14:00-16:00', 'Human Anatomy & Physiology I', 'DNM102', 'Mr. Okello David', 'Lecture Hall B', '2024/2025', NULL, '2026-07-03 04:51:14'),
(69, 'Diploma in Nursing', 1, 'Semester 1', 'Wednesday', '10:00-12:00', 'Nutrition & Dietetics', 'DNM103', 'Mrs. Nalwoga Christine', 'Lecture Hall C', '2024/2025', NULL, '2026-07-03 04:51:14'),
(70, 'Diploma in Nursing', 1, 'Semester 2', 'Monday', '14:00-16:00', 'Medical Surgical Nursing I', 'DNM104', 'Sr. Nakamya Florence', 'Skills Lab 1', '2024/2025', NULL, '2026-07-03 04:51:14'),
(71, 'Diploma in Nursing', 1, 'Semester 2', 'Friday', '08:00-12:00', 'Medical Surgical Nursing I', 'DNM104', 'Sr. Nakamya Florence', 'Ward 3', '2024/2025', NULL, '2026-07-03 04:51:14'),
(72, 'Diploma in Midwifery', 1, 'Semester 1', 'Tuesday', '08:00-10:00', 'Midwifery Science I', 'DMM101', 'Mrs. Musimenta Grace', 'Lecture Hall A', '2024/2025', NULL, '2026-07-03 04:51:14'),
(73, 'Diploma in Midwifery', 1, 'Semester 1', 'Wednesday', '08:00-10:00', 'Anatomy for Midwives', 'DMM102', 'Mr. Okello David', 'Lecture Hall B', '2024/2025', NULL, '2026-07-03 04:51:14'),
(74, 'Diploma in Midwifery', 1, 'Semester 1', 'Friday', '14:00-16:00', 'Reproductive Health', 'DMM103', 'Mrs. Musimenta Grace', 'Lecture Hall A', '2024/2025', NULL, '2026-07-03 04:51:14'),
(75, 'Diploma in Nursing Education', 1, 'Semester 1', 'Monday', '10:00-12:00', 'Foundations of Education', 'DNE101', 'Dr. Waswa Robert', 'Lecture Hall D', '2024/2025', NULL, '2026-07-03 04:51:14'),
(76, 'Diploma in Nursing Education', 1, 'Semester 1', 'Thursday', '14:00-16:00', 'Educational Psychology', 'DNE102', 'Dr. Waswa Robert', 'Lecture Hall D', '2024/2025', NULL, '2026-07-03 04:51:14'),
(77, 'Diploma in Nursing', 2, 'Semester 3', 'Monday', '08:00-10:00', 'Medical Surgical Nursing II', 'DNM201', 'Dr. Mubiru John', 'Lecture Hall C', '2024/2025', NULL, '2026-07-03 04:51:14'),
(78, 'Diploma in Nursing', 2, 'Semester 3', 'Wednesday', '14:00-16:00', 'Pediatric Nursing', 'DNM202', 'Sr. Nakamya Florence', 'Lecture Hall A', '2024/2025', NULL, '2026-07-03 04:51:14'),
(79, 'Diploma in Nursing', 2, 'Semester 3', 'Friday', '10:00-12:00', 'Psychiatric Nursing', 'DNM203', 'Mrs. Nabirye Sarah', 'Lecture Hall B', '2024/2025', NULL, '2026-07-03 04:51:14'),
(80, 'Diploma in Nursing', 3, 'Semester 5', 'Tuesday', '08:00-12:00', 'Clinical Practicum I', 'DNM304', 'Head of Nursing', 'Iganga RRH', '2024/2025', NULL, '2026-07-03 04:51:14'),
(81, 'Diploma in Nursing', 3, 'Semester 5', 'Thursday', '10:00-12:00', 'Nursing Management & Leadership', 'DNM303', 'Dr. Mubiru John', 'Lecture Hall C', '2024/2025', NULL, '2026-07-03 04:51:14');

-- --------------------------------------------------------

--
-- Table structure for table `time_table`
--

CREATE TABLE `time_table` (
  `s_no` int(11) NOT NULL,
  `class` varchar(50) DEFAULT NULL,
  `section` varchar(50) DEFAULT NULL,
  `start_time` varchar(20) DEFAULT NULL,
  `end_time` varchar(20) DEFAULT NULL,
  `mon` varchar(100) DEFAULT NULL,
  `tue` varchar(100) DEFAULT NULL,
  `wed` varchar(100) DEFAULT NULL,
  `thu` varchar(100) DEFAULT NULL,
  `fri` varchar(100) DEFAULT NULL,
  `sat` varchar(100) DEFAULT NULL,
  `editor_id` varchar(50) DEFAULT NULL,
  `timestamp` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transcripts`
--

CREATE TABLE `transcripts` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(100) DEFAULT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) DEFAULT 'draft',
  `pdf_path` varchar(500) DEFAULT NULL,
  `is_official` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transcript_items`
--

CREATE TABLE `transcript_items` (
  `id` int(11) NOT NULL,
  `transcript_id` int(11) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `course_title` varchar(300) DEFAULT '',
  `credit_hours` decimal(5,2) DEFAULT 0.00,
  `marks_obtained` decimal(8,2) DEFAULT 0.00,
  `grade` varchar(5) DEFAULT '',
  `grade_point` decimal(4,2) DEFAULT 0.00,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ura_reports`
--

CREATE TABLE `ura_reports` (
  `id` int(11) NOT NULL,
  `report_name` varchar(200) NOT NULL,
  `name` varchar(200) DEFAULT NULL,
  `tax_period` varchar(50) DEFAULT NULL,
  `period` varchar(50) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT 0.00,
  `tax_amount` decimal(15,2) DEFAULT 0.00,
  `status` enum('pending','filed','submitted') DEFAULT 'pending',
  `filed_date` date DEFAULT NULL,
  `report_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `device_type` varchar(50) DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `view_program_grouping`
--

CREATE TABLE `view_program_grouping` (
  `department` varchar(20) DEFAULT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `course_name` varchar(255) DEFAULT NULL,
  `credit_hours` int(11) DEFAULT NULL,
  `course_level` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `view_student_grouping`
--

CREATE TABLE `view_student_grouping` (
  `program` varchar(100) DEFAULT NULL,
  `year_of_study` int(11) DEFAULT NULL,
  `status` enum('Active','Inactive','Graduated','Suspended','Withdrawn','deleted') DEFAULT NULL,
  `set_name` varchar(50) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `student_count` bigint(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `volunteer_applications`
--

CREATE TABLE `volunteer_applications` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `skills` longtext DEFAULT NULL,
  `availability` longtext DEFAULT NULL,
  `motivation` longtext DEFAULT NULL,
  `experience` longtext DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending' COMMENT 'pending, reviewed, accepted, rejected, interviewed',
  `reviewed_by` int(11) DEFAULT NULL,
  `review_date` timestamp NULL DEFAULT NULL,
  `decision` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `website_announcements`
--

CREATE TABLE `website_announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `category` varchar(100) DEFAULT NULL COMMENT 'General, Academic, Administrative, Event, etc.',
  `author` varchar(255) DEFAULT NULL COMMENT 'Director or staff name',
  `image_url` varchar(500) DEFAULT NULL,
  `featured` tinyint(1) DEFAULT 0 COMMENT 'Show on homepage',
  `status` varchar(50) DEFAULT 'published' COMMENT 'draft, published, archived',
  `views` int(11) DEFAULT 0,
  `published_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_programs`
--
ALTER TABLE `academic_programs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `program_code` (`program_code`),
  ADD KEY `idx_prog_status` (`status`);

--
-- Indexes for table `academic_records`
--
ALTER TABLE `academic_records`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ar_student` (`student_id`);

--
-- Indexes for table `academic_registrar_activity_log`
--
ALTER TABLE `academic_registrar_activity_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_s_no` (`s_no`);

--
-- Indexes for table `admission_activity_logs`
--
ALTER TABLE `admission_activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_log_app` (`applicant_id`),
  ADD KEY `idx_log_user` (`user_id`),
  ADD KEY `idx_log_created` (`created_at`);

--
-- Indexes for table `admission_communications`
--
ALTER TABLE `admission_communications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_com_app` (`applicant_id`),
  ADD KEY `idx_com_type` (`communication_type`);

--
-- Indexes for table `admission_decisions`
--
ALTER TABLE `admission_decisions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dec_app` (`applicant_id`);

--
-- Indexes for table `admission_interviews`
--
ALTER TABLE `admission_interviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_int_app` (`applicant_id`),
  ADD KEY `idx_int_date` (`interview_date`);

--
-- Indexes for table `admission_notifications`
--
ALTER TABLE `admission_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_n_app` (`applicant_id`),
  ADD KEY `idx_n_user` (`user_id`),
  ADD KEY `idx_n_read` (`is_read`);

--
-- Indexes for table `admission_requirements`
--
ALTER TABLE `admission_requirements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_req_active` (`is_active`),
  ADD KEY `idx_req_order` (`display_order`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `applicants`
--
ALTER TABLE `applicants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `application_number` (`application_number`),
  ADD UNIQUE KEY `student_number` (`student_number`),
  ADD KEY `idx_app_status` (`status`),
  ADD KEY `idx_app_program` (`program_id`),
  ADD KEY `idx_app_intake` (`intake`),
  ADD KEY `idx_app_name` (`full_name`),
  ADD KEY `idx_app_phone` (`phone`),
  ADD KEY `idx_app_email` (`email`),
  ADD KEY `idx_app_created` (`created_at`),
  ADD KEY `intake_id` (`intake_id`);

--
-- Indexes for table `applicant_requirement_status`
--
ALTER TABLE `applicant_requirement_status`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_app_req` (`applicant_id`,`requirement_id`),
  ADD KEY `idx_ars_status` (`status`),
  ADD KEY `requirement_id` (`requirement_id`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_app_student` (`student_id`),
  ADD KEY `idx_app_status` (`status`),
  ADD KEY `idx_app_program` (`program`),
  ADD KEY `idx_app_email` (`email`);

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `approval_actions`
--
ALTER TABLE `approval_actions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_aa_request` (`request_id`),
  ADD KEY `idx_aa_action_by` (`action_by`);

--
-- Indexes for table `approval_requests`
--
ALTER TABLE `approval_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ar_status` (`status`),
  ADD KEY `idx_ar_requester` (`requester_id`),
  ADD KEY `idx_ar_workflow` (`workflow_id`),
  ADD KEY `idx_ar_ref` (`reference_type`,`reference_id`);

--
-- Indexes for table `approval_stages`
--
ALTER TABLE `approval_stages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_as_workflow` (`workflow_id`);

--
-- Indexes for table `approval_workflows`
--
ALTER TABLE `approval_workflows`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `assessment_scores`
--
ALTER TABLE `assessment_scores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_asc_student` (`student_id`);

--
-- Indexes for table `assets`
--
ALTER TABLE `assets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `asset_categories`
--
ALTER TABLE `asset_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lecturer_id` (`lecturer_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `audit_findings`
--
ALTER TABLE `audit_findings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `action` (`action`),
  ADD KEY `entity_type` (`entity_type`),
  ADD KEY `created_at` (`created_at`);

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
-- Indexes for table `bank_transactions`
--
ALTER TABLE `bank_transactions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `budget_approvals`
--
ALTER TABLE `budget_approvals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `budget_records`
--
ALTER TABLE `budget_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bursar_cashbook`
--
ALTER TABLE `bursar_cashbook`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bursar_chart_of_accounts`
--
ALTER TABLE `bursar_chart_of_accounts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bursar_general_ledger`
--
ALTER TABLE `bursar_general_ledger`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bursar_tax_filings`
--
ALTER TABLE `bursar_tax_filings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bursar_tax_periods`
--
ALTER TABLE `bursar_tax_periods`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `bursar_users`
--
ALTER TABLE `bursar_users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `buses`
--
ALTER TABLE `buses`
  ADD PRIMARY KEY (`s_no`),
  ADD KEY `idx_bus_id` (`bus_id`);

--
-- Indexes for table `bus_root`
--
ALTER TABLE `bus_root`
  ADD PRIMARY KEY (`s_no`),
  ADD KEY `idx_bus_id` (`bus_id`);

--
-- Indexes for table `bus_staff`
--
ALTER TABLE `bus_staff`
  ADD PRIMARY KEY (`s_no`),
  ADD KEY `idx_bus_id` (`bus_id`);

--
-- Indexes for table `capital_projects`
--
ALTER TABLE `capital_projects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cashbook`
--
ALTER TABLE `cashbook`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cash_book`
--
ALTER TABLE `cash_book`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `circulars`
--
ALTER TABLE `circulars`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `clinical_placements`
--
ALTER TABLE `clinical_placements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `clinical_placements_students`
--
ALTER TABLE `clinical_placements_students`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `clinical_sites`
--
ALTER TABLE `clinical_sites`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cms_approvals`
--
ALTER TABLE `cms_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_approval_content` (`content_type`,`content_id`),
  ADD KEY `idx_approval_status` (`status`),
  ADD KEY `idx_approval_submitter` (`submitted_by`),
  ADD KEY `idx_approval_reviewer` (`reviewer_id`);

--
-- Indexes for table `cms_audit_log`
--
ALTER TABLE `cms_audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_user` (`user_id`),
  ADD KEY `idx_audit_action` (`action`),
  ADD KEY `idx_audit_content` (`content_type`,`content_id`),
  ADD KEY `idx_audit_date` (`created_at`);

--
-- Indexes for table `cms_banners`
--
ALTER TABLE `cms_banners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_banner_page` (`page_slug`),
  ADD KEY `idx_banner_active` (`is_active`),
  ADD KEY `idx_banner_sort` (`sort_order`);

--
-- Indexes for table `cms_content_blocks`
--
ALTER TABLE `cms_content_blocks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_block_page` (`page_id`),
  ADD KEY `idx_block_key` (`block_key`),
  ADD KEY `idx_block_sort` (`sort_order`);

--
-- Indexes for table `cms_events`
--
ALTER TABLE `cms_events`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_event_date` (`event_date`),
  ADD KEY `idx_event_type` (`event_type`),
  ADD KEY `idx_event_published` (`is_published`);

--
-- Indexes for table `cms_faqs`
--
ALTER TABLE `cms_faqs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_faq_page` (`page_slug`),
  ADD KEY `idx_faq_category` (`category`),
  ADD KEY `idx_faq_sort` (`sort_order`);

--
-- Indexes for table `cms_gallery_categories`
--
ALTER TABLE `cms_gallery_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `cms_gallery_images`
--
ALTER TABLE `cms_gallery_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_gallery_cat` (`category_id`),
  ADD KEY `idx_gallery_sort` (`sort_order`);

--
-- Indexes for table `cms_media`
--
ALTER TABLE `cms_media`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_media_type` (`file_type`),
  ADD KEY `idx_media_folder` (`folder`),
  ADD KEY `idx_media_uploaded` (`uploaded_by`);

--
-- Indexes for table `cms_news_categories`
--
ALTER TABLE `cms_news_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `cms_pages`
--
ALTER TABLE `cms_pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_page_type` (`page_type`),
  ADD KEY `idx_published` (`is_published`),
  ADD KEY `idx_sort` (`sort_order`);

--
-- Indexes for table `cms_page_views`
--
ALTER TABLE `cms_page_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pv_page` (`page_slug`),
  ADD KEY `idx_pv_date` (`viewed_at`),
  ADD KEY `idx_pv_device` (`device_type`);

--
-- Indexes for table `cms_partners`
--
ALTER TABLE `cms_partners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_partner_type` (`partner_type`),
  ADD KEY `idx_partner_active` (`is_active`);

--
-- Indexes for table `cms_revisions`
--
ALTER TABLE `cms_revisions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rev_content` (`content_type`,`content_id`),
  ADD KEY `idx_rev_created` (`created_at`);

--
-- Indexes for table `cms_role_permissions`
--
ALTER TABLE `cms_role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_permission_page` (`role_name`,`permission`,`page_slug`),
  ADD KEY `idx_perm_role` (`role_name`),
  ADD KEY `idx_perm_page` (`page_slug`);

--
-- Indexes for table `cms_settings`
--
ALTER TABLE `cms_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_setting_group` (`setting_group`);

--
-- Indexes for table `cms_social_links`
--
ALTER TABLE `cms_social_links`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `platform` (`platform`);

--
-- Indexes for table `cms_staff_directory`
--
ALTER TABLE `cms_staff_directory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_dept` (`department`),
  ADD KEY `idx_staff_leader` (`is_leadership`),
  ADD KEY `idx_staff_published` (`is_published`);

--
-- Indexes for table `cms_testimonials`
--
ALTER TABLE `cms_testimonials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_test_featured` (`is_featured`),
  ADD KEY `idx_test_published` (`is_published`),
  ADD KEY `idx_test_sort` (`sort_order`);

--
-- Indexes for table `committee_actions`
--
ALTER TABLE `committee_actions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `communication_log`
--
ALTER TABLE `communication_log`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `complaint_submissions`
--
ALTER TABLE `complaint_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`complainant_email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_severity` (`severity`),
  ADD KEY `idx_department` (`department`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `compliance_alerts`
--
ALTER TABLE `compliance_alerts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `compliance_tracking`
--
ALTER TABLE `compliance_tracking`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_directory`
--
ALTER TABLE `contact_directory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_submissions`
--
ALTER TABLE `contact_submissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `correspondence`
--
ALTER TABLE `correspondence`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cost_centers`
--
ALTER TABLE `cost_centers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `idx_courses_department` (`department`),
  ADD KEY `idx_courses_status` (`status`);

--
-- Indexes for table `course_assignments`
--
ALTER TABLE `course_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_course` (`course_id`),
  ADD KEY `idx_lecturer` (`lecturer_id`),
  ADD KEY `idx_year_sem` (`academic_year`,`semester`);

--
-- Indexes for table `course_catalog`
--
ALTER TABLE `course_catalog`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `course_prerequisites`
--
ALTER TABLE `course_prerequisites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_course_prereq` (`course_code`,`prerequisite_code`),
  ADD KEY `idx_course` (`course_code`);

--
-- Indexes for table `course_registrations`
--
ALTER TABLE `course_registrations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cr_student` (`student_id`);

--
-- Indexes for table `daily_sick_records`
--
ALTER TABLE `daily_sick_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `department_performance`
--
ALTER TABLE `department_performance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `department_requests`
--
ALTER TABLE `department_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deputy_tasks`
--
ALTER TABLE `deputy_tasks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `document_tracking`
--
ALTER TABLE `document_tracking`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `donations`
--
ALTER TABLE `donations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_type` (`event_type`),
  ADD KEY `start_date` (`start_date`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `examination_records`
--
ALTER TABLE `examination_records`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_student_course_exam` (`student_id`,`course_code`,`exam_type`);

--
-- Indexes for table `examination_results`
--
ALTER TABLE `examination_results`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_er_student` (`student_id`);

--
-- Indexes for table `exams`
--
ALTER TABLE `exams`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `exam_results`
--
ALTER TABLE `exam_results`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `expenditure_approvals`
--
ALTER TABLE `expenditure_approvals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `expenditure_records`
--
ALTER TABLE `expenditure_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`s_no`),
  ADD KEY `idx_sender` (`sender_id`),
  ADD KEY `idx_receiver` (`receiver_id`);

--
-- Indexes for table `feedback_submissions`
--
ALTER TABLE `feedback_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_rating` (`rating`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `fee_adjustments`
--
ALTER TABLE `fee_adjustments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fee_reminders`
--
ALTER TABLE `fee_reminders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fee_structure`
--
ALTER TABLE `fee_structure`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fee_structures`
--
ALTER TABLE `fee_structures`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `finance_assets`
--
ALTER TABLE `finance_assets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `finance_messages`
--
ALTER TABLE `finance_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `finance_notices`
--
ALTER TABLE `finance_notices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `financial_clearance`
--
ALTER TABLE `financial_clearance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `financial_messages`
--
ALTER TABLE `financial_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `financial_notices`
--
ALTER TABLE `financial_notices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `financial_reports`
--
ALTER TABLE `financial_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `form_submissions`
--
ALTER TABLE `form_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`form_type`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `general_ledger`
--
ALTER TABLE `general_ledger`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gpa_settings`
--
ALTER TABLE `gpa_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `graduation_approvals`
--
ALTER TABLE `graduation_approvals`
  ADD PRIMARY KEY (`id`),
  ADD KEY `candidate_id` (`candidate_id`);

--
-- Indexes for table `graduation_candidates`
--
ALTER TABLE `graduation_candidates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `guild_feedback`
--
ALTER TABLE `guild_feedback`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `hostel_allocations`
--
ALTER TABLE `hostel_allocations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hostel_blocks`
--
ALTER TABLE `hostel_blocks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_block_name` (`block_name`);

--
-- Indexes for table `hostel_rooms`
--
ALTER TABLE `hostel_rooms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `improvement_tracking`
--
ALTER TABLE `improvement_tracking`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `income_tax_rates`
--
ALTER TABLE `income_tax_rates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `institutional_kpis`
--
ALTER TABLE `institutional_kpis`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `intakes`
--
ALTER TABLE `intakes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_intake` (`intake_month`,`intake_year`),
  ADD KEY `idx_intake_status` (`status`);

--
-- Indexes for table `lab_attendance`
--
ALTER TABLE `lab_attendance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lab_consumables`
--
ALTER TABLE `lab_consumables`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lab_equipment`
--
ALTER TABLE `lab_equipment`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lab_equipment_checkouts`
--
ALTER TABLE `lab_equipment_checkouts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lab_incidents`
--
ALTER TABLE `lab_incidents`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lab_inventory_items`
--
ALTER TABLE `lab_inventory_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lab_practical_sessions`
--
ALTER TABLE `lab_practical_sessions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lab_skills_demonstrations`
--
ALTER TABLE `lab_skills_demonstrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `late_payment_settings`
--
ALTER TABLE `late_payment_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `lesson_plans`
--
ALTER TABLE `lesson_plans`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lecturer_id` (`lecturer_id`);

--
-- Indexes for table `library_acquisitions`
--
ALTER TABLE `library_acquisitions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_la_date` (`acquisition_date`),
  ADD KEY `idx_la_type` (`acquisition_type`),
  ADD KEY `idx_la_status` (`status`),
  ADD KEY `idx_la_isbn` (`isbn`);

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
-- Indexes for table `library_fines`
--
ALTER TABLE `library_fines`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `marks`
--
ALTER TABLE `marks`
  ADD PRIMARY KEY (`s_no`),
  ADD KEY `idx_exam_id` (`exam_id`),
  ADD KEY `idx_student_id` (`student_id`);

--
-- Indexes for table `medicine_stock`
--
ALTER TABLE `medicine_stock`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `medicine_stock_transactions`
--
ALTER TABLE `medicine_stock_transactions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `meetings`
--
ALTER TABLE `meetings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `meeting_actions`
--
ALTER TABLE `meeting_actions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `meeting_attendees`
--
ALTER TABLE `meeting_attendees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `meeting_minutes`
--
ALTER TABLE `meeting_minutes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `recipient_id` (`recipient_id`),
  ADD KEY `is_read` (`is_read`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `module_permissions`
--
ALTER TABLE `module_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `module_role` (`module_id`,`role_id`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `news`
--
ALTER TABLE `news`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notes`
--
ALTER TABLE `notes`
  ADD PRIMARY KEY (`s_no`),
  ADD KEY `idx_class` (`class`);

--
-- Indexes for table `notice`
--
ALTER TABLE `notice`
  ADD PRIMARY KEY (`s_no`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notification_logs`
--
ALTER TABLE `notification_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `is_read` (`is_read`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `official_letters`
--
ALTER TABLE `official_letters`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pay_student_date` (`student_id`,`payment_date`);

--
-- Indexes for table `payment_approvals`
--
ALTER TABLE `payment_approvals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_audit_log`
--
ALTER TABLE `payment_audit_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_audit_user` (`user_id`),
  ADD KEY `idx_audit_action` (`action`),
  ADD KEY `idx_audit_entity` (`entity_type`,`entity_id`),
  ADD KEY `idx_audit_date` (`created_at`);

--
-- Indexes for table `payment_callbacks`
--
ALTER TABLE `payment_callbacks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_transaction` (`transaction_id`),
  ADD KEY `idx_provider` (`provider_key`),
  ADD KEY `idx_processed` (`processed`);

--
-- Indexes for table `payment_gateway_settings`
--
ALTER TABLE `payment_gateway_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `idx_settings_group` (`setting_group`);

--
-- Indexes for table `payment_providers`
--
ALTER TABLE `payment_providers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `provider_key` (`provider_key`),
  ADD KEY `idx_provider_type` (`provider_type`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_provider_status` (`status`),
  ADD KEY `idx_provider_category` (`provider_category`);

--
-- Indexes for table `payment_receipts`
--
ALTER TABLE `payment_receipts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_reconciliation`
--
ALTER TABLE `payment_reconciliation`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_date_provider` (`reconciliation_date`,`provider_key`),
  ADD KEY `idx_date` (`reconciliation_date`);

--
-- Indexes for table `payment_refunds`
--
ALTER TABLE `payment_refunds`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `refund_ref` (`refund_ref`),
  ADD KEY `idx_original` (`original_transaction_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `payment_subscriptions`
--
ALTER TABLE `payment_subscriptions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `transaction_ref` (`transaction_ref`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_staff` (`staff_id`),
  ADD KEY `idx_provider` (`provider_key`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_payment_type` (`payment_type`),
  ADD KEY `idx_reference` (`reference_type`,`reference_id`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_payment_provider` (`provider_key`),
  ADD KEY `idx_payment_student` (`student_id`),
  ADD KEY `idx_payment_staff` (`staff_id`),
  ADD KEY `idx_payment_status` (`status`),
  ADD KEY `idx_payment_date` (`created_at`),
  ADD KEY `idx_idempotency` (`idempotency_key`),
  ADD KEY `idx_provider_txn` (`provider_transaction_id`);

--
-- Indexes for table `payment_webhook_logs`
--
ALTER TABLE `payment_webhook_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_provider` (`provider_key`),
  ADD KEY `idx_event` (`event_type`),
  ADD KEY `idx_processed` (`processed`);

--
-- Indexes for table `payroll_approvals`
--
ALTER TABLE `payroll_approvals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payroll_details`
--
ALTER TABLE `payroll_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pd_run` (`payroll_run_id`),
  ADD KEY `idx_pd_staff` (`staff_id`);

--
-- Indexes for table `payroll_history`
--
ALTER TABLE `payroll_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff` (`staff_id`);

--
-- Indexes for table `payroll_records`
--
ALTER TABLE `payroll_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payroll_runs`
--
ALTER TABLE `payroll_runs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payslips`
--
ALTER TABLE `payslips`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `penalty_configurations`
--
ALTER TABLE `penalty_configurations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `principal_notices`
--
ALTER TABLE `principal_notices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `procurement_requests`
--
ALTER TABLE `procurement_requests`
  ADD PRIMARY KEY (`id`);

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
-- Indexes for table `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_endpoint` (`user_id`,`endpoint`);

--
-- Indexes for table `quality_assurance`
--
ALTER TABLE `quality_assurance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `quality_assurance_reviews`
--
ALTER TABLE `quality_assurance_reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_review_date` (`review_date`);

--
-- Indexes for table `registrar_certificates`
--
ALTER TABLE `registrar_certificates`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `registrar_settings`
--
ALTER TABLE `registrar_settings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `registrar_transcript_requests`
--
ALTER TABLE `registrar_transcript_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `reminders`
--
ALTER TABLE `reminders`
  ADD PRIMARY KEY (`s_no`),
  ADD KEY `idx_id` (`id`);

--
-- Indexes for table `request_tracking`
--
ALTER TABLE `request_tracking`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `requirement_categories`
--
ALTER TABLE `requirement_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `requirement_history`
--
ALTER TABLE `requirement_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rh_app` (`applicant_id`),
  ADD KEY `idx_rh_action` (`action`);

--
-- Indexes for table `risk_register`
--
ALTER TABLE `risk_register`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `salary_components`
--
ALTER TABLE `salary_components`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `secretary_messages`
--
ALTER TABLE `secretary_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `setting_group` (`setting_group`);

--
-- Indexes for table `sickness_directory`
--
ALTER TABLE `sickness_directory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sponsorships`
--
ALTER TABLE `sponsorships`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_staff_role` (`role_id`),
  ADD KEY `idx_staff_email` (`email`);

--
-- Indexes for table `staff_leave`
--
ALTER TABLE `staff_leave`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_leave_type` (`leave_type`);

--
-- Indexes for table `staff_profiles`
--
ALTER TABLE `staff_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `staff_id` (`staff_id`);

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
-- Indexes for table `staff_tasks`
--
ALTER TABLE `staff_tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`);

--
-- Indexes for table `staff_training`
--
ALTER TABLE `staff_training`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `store_categories`
--
ALTER TABLE `store_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `store_inventory`
--
ALTER TABLE `store_inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_si_status` (`status`),
  ADD KEY `idx_si_category` (`category_id`);

--
-- Indexes for table `store_inventory_transactions`
--
ALTER TABLE `store_inventory_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sit_item` (`item_id`);

--
-- Indexes for table `store_requests`
--
ALTER TABLE `store_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sr_status` (`status`),
  ADD KEY `idx_sr_by` (`requested_by`),
  ADD KEY `idx_sr_number` (`request_number`);

--
-- Indexes for table `store_request_items`
--
ALTER TABLE `store_request_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sri_request` (`request_id`);

--
-- Indexes for table `strategic_initiatives`
--
ALTER TABLE `strategic_initiatives`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `strategic_plans`
--
ALTER TABLE `strategic_plans`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_stu_email` (`email`),
  ADD KEY `idx_stu_status` (`status`),
  ADD KEY `idx_stu_index_number` (`index_number`),
  ADD KEY `idx_stu_registration_number` (`registration_number`),
  ADD KEY `idx_stu_national_id` (`national_student_id_number`),
  ADD KEY `idx_stu_phone` (`phone`),
  ADD KEY `idx_stu_program` (`program`);

--
-- Indexes for table `students_trash`
--
ALTER TABLE `students_trash`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_academic_profiles`
--
ALTER TABLE `student_academic_profiles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sap_student` (`student_number`),
  ADD KEY `idx_sap_year` (`academic_year`),
  ADD KEY `idx_sap_program` (`program`);

--
-- Indexes for table `student_academic_records`
--
ALTER TABLE `student_academic_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_admissions`
--
ALTER TABLE `student_admissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_sa_app_number` (`application_number`);

--
-- Indexes for table `student_admission_tracking`
--
ALTER TABLE `student_admission_tracking`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_track_app` (`application_number`),
  ADD KEY `idx_track_status` (`admission_status`),
  ADD KEY `idx_track_student` (`student_number`),
  ADD KEY `applicant_id` (`applicant_id`);

--
-- Indexes for table `student_appeals`
--
ALTER TABLE `student_appeals`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_applications`
--
ALTER TABLE `student_applications`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_sapp_number` (`application_number`);

--
-- Indexes for table `student_attendance`
--
ALTER TABLE `student_attendance`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_counseling_sessions`
--
ALTER TABLE `student_counseling_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_scs_student` (`student_id`);

--
-- Indexes for table `student_course_registrations`
--
ALTER TABLE `student_course_registrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_discipline`
--
ALTER TABLE `student_discipline`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_discipline_records`
--
ALTER TABLE `student_discipline_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_documents`
--
ALTER TABLE `student_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_doc_app` (`applicant_id`),
  ADD KEY `idx_doc_ver` (`verification_status`);

--
-- Indexes for table `student_downloads`
--
ALTER TABLE `student_downloads`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_fees`
--
ALTER TABLE `student_fees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_fee_assignments`
--
ALTER TABLE `student_fee_assignments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_fee_tracking`
--
ALTER TABLE `student_fee_tracking`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_finance`
--
ALTER TABLE `student_finance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sf_student` (`student_id`),
  ADD KEY `idx_sf_status` (`payment_status`),
  ADD KEY `idx_sf_year` (`academic_year`);

--
-- Indexes for table `student_financial_profiles`
--
ALTER TABLE `student_financial_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_sfp_student` (`student_id`),
  ADD KEY `idx_sfp_status` (`fee_status`);

--
-- Indexes for table `student_health_incidents`
--
ALTER TABLE `student_health_incidents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_shi_student` (`student_id`);

--
-- Indexes for table `student_hostel_allocations`
--
ALTER TABLE `student_hostel_allocations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_invoices`
--
ALTER TABLE `student_invoices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_login_attempts`
--
ALTER TABLE `student_login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_student_number` (`student_number`),
  ADD KEY `idx_attempted_at` (`attempted_at`);

--
-- Indexes for table `student_medical`
--
ALTER TABLE `student_medical`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_sm_student` (`student_id`);

--
-- Indexes for table `student_messages`
--
ALTER TABLE `student_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_notifications`
--
ALTER TABLE `student_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_password_resets`
--
ALTER TABLE `student_password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_penalties`
--
ALTER TABLE `student_penalties`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_requests`
--
ALTER TABLE `student_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_requirements_status`
--
ALTER TABLE `student_requirements_status`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_srs_student` (`student_id`),
  ADD KEY `idx_srs_requirement` (`requirement_id`);

--
-- Indexes for table `student_semester_gpa`
--
ALTER TABLE `student_semester_gpa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_student_semester` (`student_id`,`academic_year`,`semester`),
  ADD KEY `idx_gpa` (`semester_gpa`);

--
-- Indexes for table `student_sick_leave`
--
ALTER TABLE `student_sick_leave`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_status_history`
--
ALTER TABLE `student_status_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_timetables`
--
ALTER TABLE `student_timetables`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_welfare_cases`
--
ALTER TABLE `student_welfare_cases`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `subscription_deductions`
--
ALTER TABLE `subscription_deductions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `supplier_payments`
--
ALTER TABLE `supplier_payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `syllabus`
--
ALTER TABLE `syllabus`
  ADD PRIMARY KEY (`s_no`),
  ADD KEY `idx_class_subject` (`class`,`subject`);

--
-- Indexes for table `system_modules`
--
ALTER TABLE `system_modules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `module_key` (`module_key`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `is_active` (`is_active`);

--
-- Indexes for table `timetable`
--
ALTER TABLE `timetable`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `time_table`
--
ALTER TABLE `time_table`
  ADD PRIMARY KEY (`s_no`),
  ADD KEY `idx_class_section` (`class`,`section`);

--
-- Indexes for table `transcripts`
--
ALTER TABLE `transcripts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `transcript_items`
--
ALTER TABLE `transcript_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transcript_id` (`transcript_id`);

--
-- Indexes for table `ura_reports`
--
ALTER TABLE `ura_reports`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `session_token` (`session_token`),
  ADD KEY `last_activity` (`last_activity`);

--
-- Indexes for table `volunteer_applications`
--
ALTER TABLE `volunteer_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `website_announcements`
--
ALTER TABLE `website_announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_featured` (`featured`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_published` (`published_at`);
ALTER TABLE `website_announcements` ADD FULLTEXT KEY `idx_search` (`title`,`content`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_programs`
--
ALTER TABLE `academic_programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `academic_records`
--
ALTER TABLE `academic_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `academic_registrar_activity_log`
--
ALTER TABLE `academic_registrar_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `s_no` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admission_activity_logs`
--
ALTER TABLE `admission_activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admission_communications`
--
ALTER TABLE `admission_communications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admission_decisions`
--
ALTER TABLE `admission_decisions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admission_interviews`
--
ALTER TABLE `admission_interviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admission_notifications`
--
ALTER TABLE `admission_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admission_requirements`
--
ALTER TABLE `admission_requirements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `applicants`
--
ALTER TABLE `applicants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `applicant_requirement_status`
--
ALTER TABLE `applicant_requirement_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `approval_actions`
--
ALTER TABLE `approval_actions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `approval_requests`
--
ALTER TABLE `approval_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `approval_stages`
--
ALTER TABLE `approval_stages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `approval_workflows`
--
ALTER TABLE `approval_workflows`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessment_scores`
--
ALTER TABLE `assessment_scores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assets`
--
ALTER TABLE `assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `asset_categories`
--
ALTER TABLE `asset_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_findings`
--
ALTER TABLE `audit_findings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bank_accounts`
--
ALTER TABLE `bank_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bank_reconciliation`
--
ALTER TABLE `bank_reconciliation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bank_transactions`
--
ALTER TABLE `bank_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budgets`
--
ALTER TABLE `budgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budget_approvals`
--
ALTER TABLE `budget_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budget_records`
--
ALTER TABLE `budget_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_cashbook`
--
ALTER TABLE `bursar_cashbook`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_chart_of_accounts`
--
ALTER TABLE `bursar_chart_of_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_general_ledger`
--
ALTER TABLE `bursar_general_ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_tax_filings`
--
ALTER TABLE `bursar_tax_filings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_tax_periods`
--
ALTER TABLE `bursar_tax_periods`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bursar_users`
--
ALTER TABLE `bursar_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `buses`
--
ALTER TABLE `buses`
  MODIFY `s_no` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bus_root`
--
ALTER TABLE `bus_root`
  MODIFY `s_no` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bus_staff`
--
ALTER TABLE `bus_staff`
  MODIFY `s_no` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `capital_projects`
--
ALTER TABLE `capital_projects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cashbook`
--
ALTER TABLE `cashbook`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cash_book`
--
ALTER TABLE `cash_book`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `chart_of_accounts`
--
ALTER TABLE `chart_of_accounts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `circulars`
--
ALTER TABLE `circulars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clinical_placements`
--
ALTER TABLE `clinical_placements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clinical_placements_students`
--
ALTER TABLE `clinical_placements_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `clinical_sites`
--
ALTER TABLE `clinical_sites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `cms_approvals`
--
ALTER TABLE `cms_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cms_audit_log`
--
ALTER TABLE `cms_audit_log`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cms_banners`
--
ALTER TABLE `cms_banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cms_content_blocks`
--
ALTER TABLE `cms_content_blocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `cms_events`
--
ALTER TABLE `cms_events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cms_faqs`
--
ALTER TABLE `cms_faqs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `cms_gallery_categories`
--
ALTER TABLE `cms_gallery_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `cms_gallery_images`
--
ALTER TABLE `cms_gallery_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cms_media`
--
ALTER TABLE `cms_media`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cms_news_categories`
--
ALTER TABLE `cms_news_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `cms_pages`
--
ALTER TABLE `cms_pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `cms_page_views`
--
ALTER TABLE `cms_page_views`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cms_partners`
--
ALTER TABLE `cms_partners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cms_revisions`
--
ALTER TABLE `cms_revisions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cms_role_permissions`
--
ALTER TABLE `cms_role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `cms_settings`
--
ALTER TABLE `cms_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `cms_social_links`
--
ALTER TABLE `cms_social_links`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `cms_staff_directory`
--
ALTER TABLE `cms_staff_directory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cms_testimonials`
--
ALTER TABLE `cms_testimonials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `committee_actions`
--
ALTER TABLE `committee_actions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `communication_log`
--
ALTER TABLE `communication_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `complaint_submissions`
--
ALTER TABLE `complaint_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `compliance_alerts`
--
ALTER TABLE `compliance_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `compliance_tracking`
--
ALTER TABLE `compliance_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact_directory`
--
ALTER TABLE `contact_directory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact_submissions`
--
ALTER TABLE `contact_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `correspondence`
--
ALTER TABLE `correspondence`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cost_centers`
--
ALTER TABLE `cost_centers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_assignments`
--
ALTER TABLE `course_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `course_catalog`
--
ALTER TABLE `course_catalog`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=189;

--
-- AUTO_INCREMENT for table `course_prerequisites`
--
ALTER TABLE `course_prerequisites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_registrations`
--
ALTER TABLE `course_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `daily_sick_records`
--
ALTER TABLE `daily_sick_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `department_performance`
--
ALTER TABLE `department_performance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `department_requests`
--
ALTER TABLE `department_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deputy_tasks`
--
ALTER TABLE `deputy_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `document_tracking`
--
ALTER TABLE `document_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `donations`
--
ALTER TABLE `donations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `examination_records`
--
ALTER TABLE `examination_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `examination_results`
--
ALTER TABLE `examination_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exams`
--
ALTER TABLE `exams`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `exam_results`
--
ALTER TABLE `exam_results`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=201;

--
-- AUTO_INCREMENT for table `expenditure_approvals`
--
ALTER TABLE `expenditure_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenditure_records`
--
ALTER TABLE `expenditure_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `s_no` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `feedback_submissions`
--
ALTER TABLE `feedback_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_adjustments`
--
ALTER TABLE `fee_adjustments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_reminders`
--
ALTER TABLE `fee_reminders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_structure`
--
ALTER TABLE `fee_structure`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT for table `fee_structures`
--
ALTER TABLE `fee_structures`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `finance_assets`
--
ALTER TABLE `finance_assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `finance_messages`
--
ALTER TABLE `finance_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `finance_notices`
--
ALTER TABLE `finance_notices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `financial_clearance`
--
ALTER TABLE `financial_clearance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `financial_messages`
--
ALTER TABLE `financial_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `financial_notices`
--
ALTER TABLE `financial_notices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `financial_reports`
--
ALTER TABLE `financial_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `form_submissions`
--
ALTER TABLE `form_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `general_ledger`
--
ALTER TABLE `general_ledger`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gpa_settings`
--
ALTER TABLE `gpa_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `graduation_approvals`
--
ALTER TABLE `graduation_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `graduation_candidates`
--
ALTER TABLE `graduation_candidates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `guild_feedback`
--
ALTER TABLE `guild_feedback`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hostel_allocations`
--
ALTER TABLE `hostel_allocations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `hostel_blocks`
--
ALTER TABLE `hostel_blocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `hostel_rooms`
--
ALTER TABLE `hostel_rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

--
-- AUTO_INCREMENT for table `improvement_tracking`
--
ALTER TABLE `improvement_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `income_tax_rates`
--
ALTER TABLE `income_tax_rates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `institutional_kpis`
--
ALTER TABLE `institutional_kpis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `intakes`
--
ALTER TABLE `intakes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_attendance`
--
ALTER TABLE `lab_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_consumables`
--
ALTER TABLE `lab_consumables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_equipment`
--
ALTER TABLE `lab_equipment`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_equipment_checkouts`
--
ALTER TABLE `lab_equipment_checkouts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_incidents`
--
ALTER TABLE `lab_incidents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_inventory_items`
--
ALTER TABLE `lab_inventory_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_practical_sessions`
--
ALTER TABLE `lab_practical_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_skills_demonstrations`
--
ALTER TABLE `lab_skills_demonstrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `late_payment_settings`
--
ALTER TABLE `late_payment_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `lesson_plans`
--
ALTER TABLE `lesson_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_acquisitions`
--
ALTER TABLE `library_acquisitions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `library_books`
--
ALTER TABLE `library_books`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `library_borrowing`
--
ALTER TABLE `library_borrowing`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=151;

--
-- AUTO_INCREMENT for table `library_fines`
--
ALTER TABLE `library_fines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `marks`
--
ALTER TABLE `marks`
  MODIFY `s_no` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `medicine_stock`
--
ALTER TABLE `medicine_stock`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `medicine_stock_transactions`
--
ALTER TABLE `medicine_stock_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `meetings`
--
ALTER TABLE `meetings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `meeting_actions`
--
ALTER TABLE `meeting_actions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `meeting_attendees`
--
ALTER TABLE `meeting_attendees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `meeting_minutes`
--
ALTER TABLE `meeting_minutes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `module_permissions`
--
ALTER TABLE `module_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `news`
--
ALTER TABLE `news`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notes`
--
ALTER TABLE `notes`
  MODIFY `s_no` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notice`
--
ALTER TABLE `notice`
  MODIFY `s_no` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notification_logs`
--
ALTER TABLE `notification_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `official_letters`
--
ALTER TABLE `official_letters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=151;

--
-- AUTO_INCREMENT for table `payment_approvals`
--
ALTER TABLE `payment_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_audit_log`
--
ALTER TABLE `payment_audit_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_callbacks`
--
ALTER TABLE `payment_callbacks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_gateway_settings`
--
ALTER TABLE `payment_gateway_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `payment_providers`
--
ALTER TABLE `payment_providers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_receipts`
--
ALTER TABLE `payment_receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_reconciliation`
--
ALTER TABLE `payment_reconciliation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_refunds`
--
ALTER TABLE `payment_refunds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_subscriptions`
--
ALTER TABLE `payment_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_transactions`
--
ALTER TABLE `payment_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_webhook_logs`
--
ALTER TABLE `payment_webhook_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_approvals`
--
ALTER TABLE `payroll_approvals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_details`
--
ALTER TABLE `payroll_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_history`
--
ALTER TABLE `payroll_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_records`
--
ALTER TABLE `payroll_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payroll_runs`
--
ALTER TABLE `payroll_runs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payslips`
--
ALTER TABLE `payslips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `penalty_configurations`
--
ALTER TABLE `penalty_configurations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `principal_notices`
--
ALTER TABLE `principal_notices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `procurement_requests`
--
ALTER TABLE `procurement_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `proof_of_payments`
--
ALTER TABLE `proof_of_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quality_assurance`
--
ALTER TABLE `quality_assurance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `quality_assurance_reviews`
--
ALTER TABLE `quality_assurance_reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registrar_certificates`
--
ALTER TABLE `registrar_certificates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `registrar_settings`
--
ALTER TABLE `registrar_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `registrar_transcript_requests`
--
ALTER TABLE `registrar_transcript_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reminders`
--
ALTER TABLE `reminders`
  MODIFY `s_no` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `request_tracking`
--
ALTER TABLE `request_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `requirement_categories`
--
ALTER TABLE `requirement_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `requirement_history`
--
ALTER TABLE `requirement_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `risk_register`
--
ALTER TABLE `risk_register`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `salary_components`
--
ALTER TABLE `salary_components`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `secretary_messages`
--
ALTER TABLE `secretary_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sickness_directory`
--
ALTER TABLE `sickness_directory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `sponsorships`
--
ALTER TABLE `sponsorships`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_leave`
--
ALTER TABLE `staff_leave`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_profiles`
--
ALTER TABLE `staff_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_roles`
--
ALTER TABLE `staff_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_salaries`
--
ALTER TABLE `staff_salaries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_tasks`
--
ALTER TABLE `staff_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_training`
--
ALTER TABLE `staff_training`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `store_categories`
--
ALTER TABLE `store_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `store_inventory`
--
ALTER TABLE `store_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `store_inventory_transactions`
--
ALTER TABLE `store_inventory_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `store_requests`
--
ALTER TABLE `store_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `store_request_items`
--
ALTER TABLE `store_request_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `strategic_initiatives`
--
ALTER TABLE `strategic_initiatives`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `strategic_plans`
--
ALTER TABLE `strategic_plans`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=163;

--
-- AUTO_INCREMENT for table `students_trash`
--
ALTER TABLE `students_trash`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_academic_profiles`
--
ALTER TABLE `student_academic_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `student_academic_records`
--
ALTER TABLE `student_academic_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=401;

--
-- AUTO_INCREMENT for table `student_admissions`
--
ALTER TABLE `student_admissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_admission_tracking`
--
ALTER TABLE `student_admission_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_appeals`
--
ALTER TABLE `student_appeals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_applications`
--
ALTER TABLE `student_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_attendance`
--
ALTER TABLE `student_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

--
-- AUTO_INCREMENT for table `student_counseling_sessions`
--
ALTER TABLE `student_counseling_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_course_registrations`
--
ALTER TABLE `student_course_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=481;

--
-- AUTO_INCREMENT for table `student_discipline`
--
ALTER TABLE `student_discipline`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `student_discipline_records`
--
ALTER TABLE `student_discipline_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_documents`
--
ALTER TABLE `student_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_downloads`
--
ALTER TABLE `student_downloads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_fees`
--
ALTER TABLE `student_fees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_fee_assignments`
--
ALTER TABLE `student_fee_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_fee_tracking`
--
ALTER TABLE `student_fee_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=151;

--
-- AUTO_INCREMENT for table `student_finance`
--
ALTER TABLE `student_finance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_financial_profiles`
--
ALTER TABLE `student_financial_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_health_incidents`
--
ALTER TABLE `student_health_incidents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_hostel_allocations`
--
ALTER TABLE `student_hostel_allocations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_invoices`
--
ALTER TABLE `student_invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=151;

--
-- AUTO_INCREMENT for table `student_login_attempts`
--
ALTER TABLE `student_login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_medical`
--
ALTER TABLE `student_medical`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_messages`
--
ALTER TABLE `student_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_notifications`
--
ALTER TABLE `student_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_password_resets`
--
ALTER TABLE `student_password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_penalties`
--
ALTER TABLE `student_penalties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_profiles`
--
ALTER TABLE `student_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_requests`
--
ALTER TABLE `student_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_requirements_status`
--
ALTER TABLE `student_requirements_status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_semester_gpa`
--
ALTER TABLE `student_semester_gpa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `student_sick_leave`
--
ALTER TABLE `student_sick_leave`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_status_history`
--
ALTER TABLE `student_status_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_timetables`
--
ALTER TABLE `student_timetables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_welfare_cases`
--
ALTER TABLE `student_welfare_cases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscription_deductions`
--
ALTER TABLE `subscription_deductions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplier_payments`
--
ALTER TABLE `supplier_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `syllabus`
--
ALTER TABLE `syllabus`
  MODIFY `s_no` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_modules`
--
ALTER TABLE `system_modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timetable`
--
ALTER TABLE `timetable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=82;

--
-- AUTO_INCREMENT for table `time_table`
--
ALTER TABLE `time_table`
  MODIFY `s_no` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transcripts`
--
ALTER TABLE `transcripts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transcript_items`
--
ALTER TABLE `transcript_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ura_reports`
--
ALTER TABLE `ura_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `volunteer_applications`
--
ALTER TABLE `volunteer_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `website_announcements`
--
ALTER TABLE `website_announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- --------------------------------------------------------

--
-- Structure for view `staff_trainings`
--
DROP TABLE IF EXISTS `staff_trainings`;

CREATE ALGORITHM=UNDEFINED DEFINER=`igangaschool`@`localhost` SQL SECURITY DEFINER VIEW `staff_trainings`  AS SELECT `staff_training`.`id` AS `id`, `staff_training`.`staff_id` AS `staff_id`, `staff_training`.`training_name` AS `training_name`, `staff_training`.`training_type` AS `training_type`, `staff_training`.`provider` AS `provider`, `staff_training`.`start_date` AS `start_date`, `staff_training`.`end_date` AS `end_date`, `staff_training`.`status` AS `status`, `staff_training`.`notes` AS `notes`, `staff_training`.`created_at` AS `created_at` FROM `staff_training` ;

-- --------------------------------------------------------

--
-- Structure for view `student_dashboard_view`
--
DROP TABLE IF EXISTS `student_dashboard_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`igangaschool`@`localhost` SQL SECURITY DEFINER VIEW `student_dashboard_view`  AS SELECT `s`.`id` AS `id`, `s`.`student_number` AS `student_number`, coalesce(`s`.`full_name`,trim(concat(`s`.`first_name`,' ',coalesce(`s`.`other_name`,''),' ',`s`.`surname`))) AS `full_name`, coalesce(`s`.`course`,`s`.`program`) AS `course`, coalesce(`s`.`year`,`s`.`current_year`) AS `year`, `s`.`set_name` AS `set_name`, `s`.`email` AS `email`, coalesce(`s`.`profile_picture`,`s`.`passport_photo`) AS `profile_picture`, coalesce(`sa`.`gpa`,0) AS `current_gpa`, coalesce(`sf`.`balance`,0) AS `fee_balance`, coalesce(`sa2`.`attendance_rate`,0) AS `attendance_rate` FROM (((`students` `s` left join (select `student_academic_records`.`student_id` AS `student_id`,`student_academic_records`.`gpa` AS `gpa` from `student_academic_records` where `student_academic_records`.`semester` = (select max(`student_academic_records`.`semester`) from `student_academic_records`) group by `student_academic_records`.`student_id`) `sa` on(`s`.`id` = `sa`.`student_id`)) left join (select `student_attendance`.`student_id` AS `student_id`,sum(case when `student_attendance`.`status` = 'Present' then 1 else 0 end) * 100.0 / count(0) AS `attendance_rate` from `student_attendance` group by `student_attendance`.`student_id`) `sa2` on(`s`.`id` = `sa2`.`student_id`)) left join (select `student_fees`.`student_id` AS `student_id`,sum(`student_fees`.`amount`) AS `balance` from `student_fees` where `student_fees`.`status` in ('Unpaid','Partially Paid','Overdue') group by `student_fees`.`student_id`) `sf` on(`s`.`id` = `sf`.`student_id`)) WHERE `s`.`status` = 'Active' ;

-- --------------------------------------------------------

--
-- Structure for view `student_login_view`
--
DROP TABLE IF EXISTS `student_login_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`igangaschool`@`localhost` SQL SECURITY DEFINER VIEW `student_login_view`  AS SELECT `students`.`id` AS `id`, `students`.`student_number` AS `student_number`, coalesce(`students`.`full_name`,trim(concat(`students`.`first_name`,' ',coalesce(`students`.`other_name`,''),' ',`students`.`surname`))) AS `full_name`, `students`.`email` AS `email`, `students`.`password` AS `password`, coalesce(`students`.`course`,`students`.`program`) AS `course`, `students`.`status` AS `status`, `students`.`last_login` AS `last_login`, `students`.`login_attempts` AS `login_attempts`, `students`.`is_first_login` AS `is_first_login` FROM `students` WHERE `students`.`status` = 'Active' ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admission_communications`
--
ALTER TABLE `admission_communications`
  ADD CONSTRAINT `admission_communications_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `admission_decisions`
--
ALTER TABLE `admission_decisions`
  ADD CONSTRAINT `admission_decisions_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `admission_interviews`
--
ALTER TABLE `admission_interviews`
  ADD CONSTRAINT `admission_interviews_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `applicants`
--
ALTER TABLE `applicants`
  ADD CONSTRAINT `applicants_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `academic_programs` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `applicants_ibfk_2` FOREIGN KEY (`intake_id`) REFERENCES `intakes` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `applicant_requirement_status`
--
ALTER TABLE `applicant_requirement_status`
  ADD CONSTRAINT `applicant_requirement_status_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applicant_requirement_status_ibfk_2` FOREIGN KEY (`requirement_id`) REFERENCES `admission_requirements` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cms_content_blocks`
--
ALTER TABLE `cms_content_blocks`
  ADD CONSTRAINT `fk_block_page` FOREIGN KEY (`page_id`) REFERENCES `cms_pages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cms_gallery_images`
--
ALTER TABLE `cms_gallery_images`
  ADD CONSTRAINT `fk_gallery_cat` FOREIGN KEY (`category_id`) REFERENCES `cms_gallery_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `requirement_history`
--
ALTER TABLE `requirement_history`
  ADD CONSTRAINT `requirement_history_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `staff_roles` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_admission_tracking`
--
ALTER TABLE `student_admission_tracking`
  ADD CONSTRAINT `student_admission_tracking_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_documents`
--
ALTER TABLE `student_documents`
  ADD CONSTRAINT `student_documents_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
