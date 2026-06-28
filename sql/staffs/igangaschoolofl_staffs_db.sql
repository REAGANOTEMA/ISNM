-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net
--
-- Host: 127.0.0.1:3307
-- Generation Time: Jun 27, 2026 at 06:51 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

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

CREATE DATABASE IF NOT EXISTS `igangaschoolofl_staffs_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `igangaschoolofl_staffs_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------

--
-- Table structure for table `academic_approvals`
--

DROP TABLE IF EXISTS `academic_approvals`;
CREATE TABLE `academic_approvals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reference_type` varchar(50) NOT NULL COMMENT 'result|transcript|certificate|graduation',
  `reference_id` int(11) NOT NULL,
  `approval_level` varchar(50) NOT NULL COMMENT 'lecturer|hod|director_academics|registrar|principal|director_general',
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_aa_ref` (`reference_type`,`reference_id`),
  KEY `idx_aa_level` (`approval_level`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `academic_audit_logs`
--

DROP TABLE IF EXISTS `academic_audit_logs`;
CREATE TABLE `academic_audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `old_values` longtext DEFAULT NULL,
  `new_values` longtext DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_aal_action` (`action`),
  KEY `idx_aal_entity` (`entity_type`,`entity_id`),
  KEY `idx_aal_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `academic_calendar`
--

DROP TABLE IF EXISTS `academic_calendar`;
CREATE TABLE `academic_calendar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `calendar_id` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(30) DEFAULT NULL,
  `semester_start_date` date DEFAULT NULL,
  `semester_end_date` date DEFAULT NULL,
  `exam_start_date` date DEFAULT NULL,
  `exam_end_date` date DEFAULT NULL,
  `result_publication_date` date DEFAULT NULL,
  `registration_deadline` date DEFAULT NULL,
  `status` enum('Upcoming','Ongoing','Completed') DEFAULT 'Upcoming',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `calendar_id` (`calendar_id`),
  KEY `academic_year` (`academic_year`),
  KEY `semester` (`semester`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_calendar`
--

INSERT INTO `academic_calendar` (`id`, `calendar_id`, `academic_year`, `semester`, `semester_start_date`, `semester_end_date`, `exam_start_date`, `exam_end_date`, `result_publication_date`, `registration_deadline`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('1', 'CAL-2025-S1-001', '2025/2026', 'Semester 1', '2025-09-01', '2026-01-31', '2025-12-01', '2025-12-20', NULL, NULL, 'Ongoing', '1', '2026-06-18 21:12:21', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `academic_course_catalog`
--

DROP TABLE IF EXISTS `academic_course_catalog`;
CREATE TABLE `academic_course_catalog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_code` varchar(20) DEFAULT NULL,
  `course_name` varchar(200) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `credit_hours` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_code` (`course_code`),
  KEY `department` (`department`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_course_catalog`
--

INSERT INTO `academic_course_catalog` (`id`, `course_code`, `course_name`, `department`, `credit_hours`, `description`, `status`, `created_at`, `updated_at`) VALUES
('1', 'NUR101', 'Introduction to Nursing', 'Nursing', '0', NULL, 'Active', '2026-06-18 21:12:21', NULL),
('2', 'NUR102', 'Anatomy and Physiology', 'Nursing', '0', NULL, 'Active', '2026-06-18 21:12:21', NULL),
('3', 'NUR201', 'Medical-Surgical Nursing', 'Nursing', '0', NULL, 'Active', '2026-06-18 21:12:21', NULL),
('4', 'MID101', 'Introduction to Midwifery', 'Midwifery', '0', NULL, 'Active', '2026-06-18 21:12:21', NULL),
('5', 'MID102', 'Reproductive Health', 'Midwifery', '0', NULL, 'Active', '2026-06-18 21:12:21', NULL),
('6', 'COM101', 'Communication Skills', 'General Studies', '0', NULL, 'Active', '2026-06-18 21:12:21', NULL),
('7', 'BIO101', 'Biology', 'General Studies', '0', NULL, 'Active', '2026-06-18 21:12:21', NULL),
('8', 'CHEM101', 'Chemistry', 'General Studies', '0', NULL, 'Active', '2026-06-18 21:12:21', NULL),
('9', 'PHY101', 'Physics', 'General Studies', '0', NULL, 'Active', '2026-06-18 21:12:21', NULL),
('10', 'ENG101', 'English', 'General Studies', '0', NULL, 'Active', '2026-06-18 21:12:21', NULL),
('11', 'MATH101', 'Mathematics', 'General Studies', '0', NULL, 'Active', '2026-06-18 21:12:21', NULL),
('12', 'PHARM101', 'Pharmacology', 'Nursing', '0', NULL, 'Active', '2026-06-18 21:12:21', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `academic_curriculum_development`
--

DROP TABLE IF EXISTS `academic_curriculum_development`;
CREATE TABLE `academic_curriculum_development` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `program_id` int(10) unsigned DEFAULT NULL,
  `course_code` varchar(30) DEFAULT NULL,
  `course_name` varchar(200) DEFAULT NULL,
  `credit_hours` int(11) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `academic_programs`
--

DROP TABLE IF EXISTS `academic_programs`;
CREATE TABLE `academic_programs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_code` varchar(50) NOT NULL,
  `program_name` varchar(255) NOT NULL,
  `program_type` enum('Certificate','Diploma','Degree','Other') DEFAULT 'Certificate',
  `duration_years` decimal(3,1) DEFAULT 3.0,
  `department` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `program_code` (`program_code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_programs`
--

INSERT INTO `academic_programs` (`id`, `program_code`, `program_name`, `program_type`, `duration_years`, `department`, `status`, `created_at`) VALUES
('1', 'CERT-NUR', 'Certificate in Nursing', 'Certificate', '3.0', 'Nursing', 'Active', '2026-06-22 12:10:24'),
('2', 'CERT-MID', 'Certificate in Midwifery', 'Certificate', '3.0', 'Midwifery', 'Active', '2026-06-22 12:10:24'),
('3', 'DIP-NUR', 'Diploma in Nursing', 'Diploma', '3.0', 'Nursing', 'Active', '2026-06-22 12:10:24'),
('4', 'DIP-MID', 'Diploma in Midwifery', 'Diploma', '3.0', 'Midwifery', 'Active', '2026-06-22 12:10:24');

-- --------------------------------------------------------

--
-- Table structure for table `admission_activity_logs`
--

DROP TABLE IF EXISTS `admission_activity_logs`;
CREATE TABLE `admission_activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(50) DEFAULT 'admissions',
  `record_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_aal_user` (`user_id`),
  KEY `idx_aal_module` (`module`),
  KEY `idx_aal_action` (`action`),
  KEY `idx_aal_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admission_activity_logs`
--

INSERT INTO `admission_activity_logs` (`id`, `user_id`, `action`, `module`, `record_id`, `description`, `created_at`) VALUES
('1', '24', 'Create Student', 'students', '0', 'Created student: Otema Reagan (u004/cm/076)', '2026-06-22 13:01:24');

-- --------------------------------------------------------

--
-- Table structure for table `admission_notifications`
--

DROP TABLE IF EXISTS `admission_notifications`;
CREATE TABLE `admission_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `applicant_id` int(11) DEFAULT NULL,
  `recipient_type` varchar(50) DEFAULT 'applicant',
  `recipient_id` int(11) DEFAULT NULL,
  `title` varchar(300) NOT NULL,
  `message` text NOT NULL,
  `channel` varchar(50) DEFAULT 'portal',
  `sent_by` int(11) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admission_requirements`
--

DROP TABLE IF EXISTS `admission_requirements`;
CREATE TABLE `admission_requirements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requirement_name` varchar(200) NOT NULL,
  `type` varchar(50) DEFAULT 'Document',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_mandatory` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_req_name` (`requirement_name`),
  KEY `idx_req_active` (`is_active`),
  KEY `idx_req_order` (`display_order`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admission_requirements`
--

INSERT INTO `admission_requirements` (`id`, `requirement_name`, `type`, `description`, `is_active`, `is_mandatory`, `display_order`, `created_at`) VALUES
('1', 'Surgical Gloves', 'Document', NULL, '1', '1', '1', '2026-06-22 11:07:53'),
('2', 'Examination Gloves', 'Document', NULL, '1', '1', '2', '2026-06-22 11:07:53'),
('3', 'Photocopying Ream', 'Document', NULL, '1', '1', '3', '2026-06-22 11:07:53'),
('4', 'Ruled Paper Reams', 'Document', NULL, '1', '1', '4', '2026-06-22 11:07:53'),
('5', 'Omo', 'Document', NULL, '1', '1', '5', '2026-06-22 11:07:53'),
('6', 'Toilet Papers', 'Document', NULL, '1', '1', '6', '2026-06-22 11:07:53'),
('7', 'Compound Brooms', 'Document', NULL, '1', '1', '7', '2026-06-22 11:07:53'),
('8', 'Soft Brooms', 'Document', NULL, '1', '1', '8', '2026-06-22 11:07:53'),
('9', 'Rake', 'Document', NULL, '1', '1', '9', '2026-06-22 11:07:53'),
('10', 'Cobweb Brush', 'Document', NULL, '1', '1', '10', '2026-06-22 11:07:53'),
('11', 'Scrubbing Brush', 'Document', NULL, '1', '1', '11', '2026-06-22 11:07:53'),
('12', 'Squeezer', 'Document', NULL, '1', '1', '12', '2026-06-22 11:07:53'),
('13', 'Toilet Brush', 'Document', NULL, '1', '1', '13', '2026-06-22 11:07:53'),
('14', 'JIK', 'Document', NULL, '1', '1', '14', '2026-06-22 11:07:53'),
('15', 'Vim', 'Document', NULL, '1', '1', '15', '2026-06-22 11:07:53'),
('16', 'Mops', 'Document', NULL, '1', '1', '16', '2026-06-22 11:07:53'),
('17', 'Sanitizer', 'Document', NULL, '1', '1', '17', '2026-06-22 11:07:53'),
('18', 'Liquid Soap', 'Document', NULL, '1', '1', '18', '2026-06-22 11:07:53'),
('19', 'Face Masks', 'Document', NULL, '1', '1', '19', '2026-06-22 11:07:53'),
('20', 'Heavy Duty Gloves', 'Document', NULL, '1', '1', '20', '2026-06-22 11:07:53');

-- --------------------------------------------------------

--
-- Table structure for table `alert_recipients`
--

DROP TABLE IF EXISTS `alert_recipients`;
CREATE TABLE `alert_recipients` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `alert_id` int(10) unsigned DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `alerts`
--

DROP TABLE IF EXISTS `alerts`;
CREATE TABLE `alerts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `type` varchar(30) DEFAULT 'info',
  `status` varchar(30) DEFAULT 'active',
  `link` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `resolved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
CREATE TABLE `announcements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `body` text DEFAULT NULL,
  `target_audience` varchar(60) DEFAULT 'All',
  `priority` varchar(20) DEFAULT 'Normal',
  `posted_by` int(10) unsigned DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ann_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`id`, `title`, `body`, `target_audience`, `priority`, `posted_by`, `is_active`, `created_at`) VALUES
('1', 'Welcome to New Academic Year', 'We welcome all staff and students to the new academic year 2026. Let us work together for excellence.', 'All', 'High', '1', '1', '2026-06-19 23:58:56'),
('2', 'Staff Meeting Reminder', 'There will be a general staff meeting on Friday at 10:00 AM in the main hall.', 'Staff', 'Normal', '1', '1', '2026-06-19 23:58:56'),
('3', 'Maintenance Notice', 'The library will be closed for maintenance on Saturday.', 'All', 'Low', '1', '1', '2026-06-19 23:58:56');

-- --------------------------------------------------------

--
-- Table structure for table `applicant_messages`
--

DROP TABLE IF EXISTS `applicant_messages`;
CREATE TABLE `applicant_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `applicant_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `recipient_type` varchar(50) DEFAULT 'applicant',
  `subject` varchar(300) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applicant_requirement_status`
--

DROP TABLE IF EXISTS `applicant_requirement_status`;
CREATE TABLE `applicant_requirement_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `applicant_id` int(11) NOT NULL,
  `requirement_id` int(11) NOT NULL,
  `status` enum('Not Submitted','Submitted','Verified','Rejected','Missing') DEFAULT 'Not Submitted',
  `submitted_by` int(11) DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `rejected_by` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_app_req` (`applicant_id`,`requirement_id`),
  KEY `idx_ars_applicant` (`applicant_id`),
  KEY `idx_ars_requirement` (`requirement_id`),
  KEY `idx_ars_status` (`status`),
  CONSTRAINT `applicant_requirement_status_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `applicant_requirement_status_ibfk_2` FOREIGN KEY (`requirement_id`) REFERENCES `admission_requirements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applicants`
--

DROP TABLE IF EXISTS `applicants`;
CREATE TABLE `applicants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT 'Other',
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `guardian_name` varchar(255) DEFAULT NULL,
  `guardian_phone` varchar(20) DEFAULT NULL,
  `guardian_relationship` varchar(100) DEFAULT NULL,
  `application_number` varchar(50) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `intake` varchar(100) DEFAULT NULL,
  `admission_date` date DEFAULT NULL,
  `status` enum('New Applicant','Under Review','Approved','Rejected','Registered') DEFAULT 'New Applicant',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_app_number` (`application_number`),
  KEY `idx_applicant_name` (`full_name`),
  KEY `idx_applicant_phone` (`phone`),
  KEY `idx_applicant_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `appraisals`
--

DROP TABLE IF EXISTS `appraisals`;
CREATE TABLE `appraisals` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(10) unsigned NOT NULL,
  `appraisal_period` varchar(100) DEFAULT NULL,
  `rating` decimal(3,1) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `reviewer_id` int(10) unsigned DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `approval_actions`
--

DROP TABLE IF EXISTS `approval_actions`;
CREATE TABLE `approval_actions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `request_id` int(10) unsigned NOT NULL,
  `stage_id` int(10) unsigned DEFAULT NULL,
  `action_by` int(10) unsigned DEFAULT NULL,
  `action_type` varchar(30) NOT NULL,
  `comments` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `decision` varchar(30) DEFAULT NULL,
  `previous_stage_order` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_request` (`request_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `approval_actions`
--

INSERT INTO `approval_actions` (`id`, `request_id`, `stage_id`, `action_by`, `action_type`, `comments`, `notes`, `decision`, `previous_stage_order`, `created_at`) VALUES
('1', '3', '2', '1', 'reject', 'yes', NULL, 'Rejected', '2', '2026-06-24 01:32:00');

-- --------------------------------------------------------

--
-- Table structure for table `approval_requests`
--

DROP TABLE IF EXISTS `approval_requests`;
CREATE TABLE `approval_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `workflow_id` int(10) unsigned NOT NULL,
  `request_number` varchar(60) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` varchar(20) DEFAULT 'Medium',
  `requester_id` int(10) unsigned DEFAULT NULL,
  `requester_name` varchar(120) DEFAULT NULL,
  `requester_role` varchar(80) DEFAULT NULL,
  `current_stage_id` int(10) unsigned DEFAULT NULL,
  `current_stage_order` int(10) unsigned DEFAULT 1,
  `status` varchar(30) DEFAULT 'Active',
  `reference_type` varchar(60) DEFAULT NULL,
  `reference_id` int(10) unsigned DEFAULT NULL,
  `reference_url` varchar(255) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `final_approval_by` int(10) unsigned DEFAULT NULL,
  `final_approval_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_ref` (`reference_type`,`reference_id`),
  KEY `idx_requester` (`requester_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `approval_requests`
--

INSERT INTO `approval_requests` (`id`, `workflow_id`, `request_number`, `title`, `description`, `priority`, `requester_id`, `requester_name`, `requester_role`, `current_stage_id`, `current_stage_order`, `status`, `reference_type`, `reference_id`, `reference_url`, `rejection_reason`, `final_approval_by`, `final_approval_at`, `created_at`, `updated_at`) VALUES
('1', '1', 'REQ-20260620-A73F2B', 'Laboratory Equipment Restock', 'Request to restock essential laboratory equipment including microscopes and slides for Nursing dept.', 'High', '2', 'Mary Nalwoga', 'Head of Nursing', '2', '2', 'Active', 'store_requests', '1', NULL, NULL, NULL, NULL, '2026-06-19 22:47:50', '2026-06-20 00:47:50'),
('2', '1', 'REQ-20260620-B84C3D', 'Office Stationery Order', 'Monthly stationery supplies for administrative offices - paper, pens, folders, ink cartridges.', 'Medium', '3', 'James Okello', 'School Secretary', '2', '2', 'Active', 'store_requests', '2', NULL, NULL, NULL, NULL, '2026-06-19 19:47:50', '2026-06-20 00:47:50'),
('3', '1', 'REQ-20260619-C95D4E', 'Medical Consumables', 'Urgent restock of gloves, masks, sanitizers and first aid supplies for the sickbay.', 'Urgent', '4', 'Sarah Kyomugisha', 'Matron', '2', '2', 'Rejected', 'store_requests', '3', NULL, 'yes', NULL, NULL, '2026-06-19 00:47:50', '2026-06-24 01:32:00'),
('4', '2', 'REQ-20260620-D06E5F', 'New Student: Akello Grace', 'Registration application for Diploma Nursing program. Submitted by Registrar.', 'Normal', '5', 'Peter Okoth', 'Academic Registrar', '4', '2', 'Active', 'pending_students', '1', NULL, NULL, NULL, NULL, '2026-06-19 21:47:50', '2026-06-20 00:47:50'),
('5', '2', 'REQ-20260619-E17F6G', 'New Student: Bwire John', 'Registration application for Certificate Midwifery program. All documents verified.', 'Normal', '5', 'Peter Okoth', 'Academic Registrar', '4', '2', 'Active', 'pending_students', '2', NULL, NULL, NULL, NULL, '2026-06-19 00:47:50', '2026-06-20 00:47:50'),
('6', '3', 'REQ-20260620-F28G7H', 'End of Year Examination Schedule', 'Proposed examination timetable for the June 2026 semester. Requires DG sign-off.', 'Medium', '2', 'Mary Nalwoga', 'Head of Nursing', '5', '1', 'Active', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-19 18:47:50', '2026-06-20 00:47:50');

-- --------------------------------------------------------

--
-- Table structure for table `approval_stages`
--

DROP TABLE IF EXISTS `approval_stages`;
CREATE TABLE `approval_stages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `workflow_id` int(10) unsigned NOT NULL,
  `stage_name` varchar(120) NOT NULL,
  `stage_order` int(10) unsigned NOT NULL,
  `assigned_role_id` int(10) unsigned DEFAULT NULL,
  `assigned_role_name` varchar(80) DEFAULT NULL,
  `is_final` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_workflow_stage_order` (`workflow_id`,`stage_order`),
  KEY `idx_workflow` (`workflow_id`)
) ENGINE=InnoDB AUTO_INCREMENT=158 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `approval_stages`
--

INSERT INTO `approval_stages` (`id`, `workflow_id`, `stage_name`, `stage_order`, `assigned_role_id`, `assigned_role_name`, `is_final`, `created_at`) VALUES
('138', '125', 'Director ICT Review', '1', NULL, 'Director ICT', '0', '2026-06-27 00:17:17'),
('139', '125', 'Director General Final Approval', '2', NULL, 'Director General', '1', '2026-06-27 00:17:17'),
('140', '122', 'Director General Approval', '1', NULL, 'Director General', '1', '2026-06-27 00:17:17'),
('141', '123', 'Director General Approval', '1', NULL, 'Director General', '1', '2026-06-27 00:17:17'),
('142', '124', 'Director General Approval', '1', NULL, 'Director General', '1', '2026-06-27 00:17:17'),
('143', '126', 'Director General Approval', '1', NULL, 'Director General', '1', '2026-06-27 00:17:17'),
('144', '127', 'Director General Approval', '1', NULL, 'Director General', '1', '2026-06-27 00:17:17'),
('145', '128', 'Director General Approval', '1', NULL, 'Director General', '1', '2026-06-27 00:17:17'),
('146', '129', 'Director General Approval', '1', NULL, 'Director General', '1', '2026-06-27 00:17:17'),
('147', '130', 'Director General Approval', '1', NULL, 'Director General', '1', '2026-06-27 00:17:17');

-- --------------------------------------------------------

--
-- Table structure for table `approval_workflows`
--

DROP TABLE IF EXISTS `approval_workflows`;
CREATE TABLE `approval_workflows` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `workflow_name` varchar(120) NOT NULL,
  `category` varchar(60) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_workflow_name` (`workflow_name`)
) ENGINE=InnoDB AUTO_INCREMENT=132 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `approval_workflows`
--

INSERT INTO `approval_workflows` (`id`, `workflow_name`, `category`, `description`, `is_active`, `created_at`) VALUES
('122', 'General Department Request', 'General Administration', 'Standard approval workflow for general administrative requests requiring Director General sign-off', '1', '2026-06-27 00:17:17'),
('123', 'HR Request', 'Human Resources', 'HR-related requests requiring Director General approval', '1', '2026-06-27 00:17:17'),
('124', 'Finance Request', 'Finance', 'Financial requests and budget approvals requiring Director General sign-off', '1', '2026-06-27 00:17:17'),
('125', 'ICT Request', 'ICT', 'ICT department requests requiring departmental review and Director General approval', '1', '2026-06-27 00:17:17'),
('126', 'Academic Request', 'Academic', 'Academic affairs requests requiring Director General approval', '1', '2026-06-27 00:17:17'),
('127', 'Admissions Request', 'Admissions', 'Admissions-related requests requiring Director General approval', '1', '2026-06-27 00:17:17'),
('128', 'Library Request', 'Library', 'Library resource and service requests requiring Director General approval', '1', '2026-06-27 00:17:17'),
('129', 'Store Requisition', 'Store & Assets', 'Store and asset requisitions requiring Director General approval', '1', '2026-06-27 00:17:17'),
('130', 'Student Registration', 'Academic', 'Student registration requests requiring Director General approval', '1', '2026-06-27 00:17:17');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
CREATE TABLE `attendance` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `class_id` int(10) unsigned DEFAULT NULL,
  `subject_id` int(10) unsigned DEFAULT NULL,
  `date` date NOT NULL,
  `status` varchar(30) DEFAULT 'present',
  `remarks` text DEFAULT NULL,
  `marked_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `audit_trail`
--

DROP TABLE IF EXISTS `audit_trail`;
CREATE TABLE `audit_trail` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(10) unsigned DEFAULT NULL,
  `action_type` varchar(60) NOT NULL,
  `entity_type` varchar(60) DEFAULT NULL,
  `entity_id` int(10) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_at_staff` (`staff_id`),
  KEY `idx_at_action` (`action_type`),
  KEY `idx_at_entity` (`entity_type`,`entity_id`),
  KEY `idx_at_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `backup_management`
--

DROP TABLE IF EXISTS `backup_management`;
CREATE TABLE `backup_management` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `backup_name` varchar(200) DEFAULT NULL,
  `backup_type` varchar(50) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bank_accounts`
--

DROP TABLE IF EXISTS `bank_accounts`;
CREATE TABLE `bank_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bank_name` varchar(200) DEFAULT NULL,
  `account_name` varchar(200) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `balance` decimal(15,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `status` varchar(20) DEFAULT 'Active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_bank_reconciliation`
--

DROP TABLE IF EXISTS `bursar_bank_reconciliation`;
CREATE TABLE `bursar_bank_reconciliation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reconciliation_date` date NOT NULL,
  `bank_balance` decimal(15,2) NOT NULL,
  `book_balance` decimal(15,2) NOT NULL,
  `difference` decimal(15,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'unreconciled',
  `reconciled_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_cashbook`
--

DROP TABLE IF EXISTS `bursar_cashbook`;
CREATE TABLE `bursar_cashbook` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_date` date NOT NULL,
  `transaction_type` varchar(20) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `category` varchar(50) DEFAULT '',
  `reference` varchar(100) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cashbook_date` (`transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_chart_of_accounts`
--

DROP TABLE IF EXISTS `bursar_chart_of_accounts`;
CREATE TABLE `bursar_chart_of_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_code` varchar(20) NOT NULL,
  `account_name` varchar(200) NOT NULL,
  `account_type` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uqx_coa_code` (`account_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_discounts`
--

DROP TABLE IF EXISTS `bursar_discounts`;
CREATE TABLE `bursar_discounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fee_account_id` int(11) NOT NULL,
  `discount_type` varchar(20) NOT NULL,
  `discount_value` decimal(15,2) NOT NULL,
  `discount_amount` decimal(15,2) NOT NULL,
  `reason` text DEFAULT NULL,
  `applied_by` int(11) NOT NULL,
  `applied_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_bdiscounts_account` (`fee_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_general_ledger`
--

DROP TABLE IF EXISTS `bursar_general_ledger`;
CREATE TABLE `bursar_general_ledger` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entry_date` date NOT NULL,
  `account_code` varchar(20) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `debit_amount` decimal(15,2) DEFAULT 0.00,
  `credit_amount` decimal(15,2) DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_gledger_date` (`entry_date`),
  KEY `idx_gledger_account` (`account_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_payment_verification`
--

DROP TABLE IF EXISTS `bursar_payment_verification`;
CREATE TABLE `bursar_payment_verification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(50) NOT NULL,
  `fee_account_id` int(11) DEFAULT 0,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `payment_date` date NOT NULL,
  `proof_file` varchar(255) DEFAULT '',
  `status` varchar(20) DEFAULT 'pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_bverif_status` (`status`),
  KEY `idx_bverif_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_penalty_config`
--

DROP TABLE IF EXISTS `bursar_penalty_config`;
CREATE TABLE `bursar_penalty_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `penalty_name` varchar(200) NOT NULL,
  `penalty_type` varchar(20) NOT NULL,
  `penalty_value` decimal(15,2) NOT NULL,
  `grace_days` int(11) DEFAULT 0,
  `max_charge` decimal(15,2) DEFAULT 0.00,
  `status` varchar(20) DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_requisition_reviews`
--

DROP TABLE IF EXISTS `bursar_requisition_reviews`;
CREATE TABLE `bursar_requisition_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requester_id` int(11) NOT NULL,
  `item_description` varchar(500) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT 0.00,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_scholarships`
--

DROP TABLE IF EXISTS `bursar_scholarships`;
CREATE TABLE `bursar_scholarships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `scholarship_name` varchar(200) NOT NULL,
  `scholarship_type` varchar(50) NOT NULL,
  `scholarship_value` decimal(15,2) NOT NULL,
  `description` text DEFAULT NULL,
  `provider` varchar(200) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_sponsorships`
--

DROP TABLE IF EXISTS `bursar_sponsorships`;
CREATE TABLE `bursar_sponsorships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(50) NOT NULL,
  `sponsor_name` varchar(200) NOT NULL,
  `sponsor_contact` varchar(100) DEFAULT NULL,
  `sponsor_email` varchar(200) DEFAULT NULL,
  `coverage_percent` decimal(5,2) DEFAULT 100.00,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_bsponsorships_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_tax_filings`
--

DROP TABLE IF EXISTS `bursar_tax_filings`;
CREATE TABLE `bursar_tax_filings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tax_period_id` int(11) NOT NULL,
  `filing_date` date NOT NULL,
  `tax_type` varchar(50) NOT NULL,
  `total_revenue` decimal(15,2) DEFAULT 0.00,
  `tax_amount` decimal(15,2) DEFAULT 0.00,
  `due_date` date DEFAULT NULL,
  `filing_status` varchar(20) DEFAULT 'filed',
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_taxfiling_period` (`tax_period_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_tax_periods`
--

DROP TABLE IF EXISTS `bursar_tax_periods`;
CREATE TABLE `bursar_tax_periods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `period_name` varchar(100) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_vat_reports`
--

DROP TABLE IF EXISTS `bursar_vat_reports`;
CREATE TABLE `bursar_vat_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `output_vat` decimal(15,2) DEFAULT 0.00,
  `input_vat` decimal(15,2) DEFAULT 0.00,
  `net_vat` decimal(15,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'draft',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bursar_withholding_tax`
--

DROP TABLE IF EXISTS `bursar_withholding_tax`;
CREATE TABLE `bursar_withholding_tax` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tax_date` date NOT NULL,
  `payee_name` varchar(200) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `gross_amount` decimal(15,2) NOT NULL,
  `wht_rate` decimal(5,2) DEFAULT 6.00,
  `wht_amount` decimal(15,2) DEFAULT 0.00,
  `status` varchar(20) DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `idx_wht_date` (`tax_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_management`
--

DROP TABLE IF EXISTS `cache_management`;
CREATE TABLE `cache_management` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cache_key` varchar(255) NOT NULL,
  `cache_value` longtext DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cache_key` (`cache_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `certificate_templates`
--

DROP TABLE IF EXISTS `certificate_templates`;
CREATE TABLE `certificate_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_name` varchar(200) NOT NULL,
  `certificate_type` varchar(100) DEFAULT NULL,
  `template_data` longtext DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `certificate_uploads`
--

DROP TABLE IF EXISTS `certificate_uploads`;
CREATE TABLE `certificate_uploads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `certificate_type` varchar(100) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cu_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `certificate_verification`
--

DROP TABLE IF EXISTS `certificate_verification`;
CREATE TABLE `certificate_verification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `certificate_number` varchar(50) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `verification_code` varchar(100) NOT NULL,
  `verification_url` varchar(500) DEFAULT NULL,
  `verified_by` varchar(255) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_v_cert` (`certificate_number`),
  KEY `idx_v_code` (`verification_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `certificates`
--

DROP TABLE IF EXISTS `certificates`;
CREATE TABLE `certificates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `certificate_number` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `certificate_type` enum('National Certificate','Diploma','Completion Letter','Recommendation Letter','Training Certificate','Clinical Placement Certificate') NOT NULL,
  `program` varchar(255) DEFAULT NULL,
  `award` varchar(255) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `gpa` decimal(4,2) DEFAULT NULL,
  `cgpa` decimal(4,2) DEFAULT NULL,
  `class_of_award` varchar(100) DEFAULT NULL,
  `status` enum('draft','pending_principal','pending_dg','approved','rejected','released') DEFAULT 'draft',
  `requested_by` int(11) DEFAULT NULL,
  `requested_at` datetime DEFAULT NULL,
  `approved_by_registrar` int(11) DEFAULT NULL,
  `approved_at_registrar` datetime DEFAULT NULL,
  `approved_by_principal` int(11) DEFAULT NULL,
  `approved_at_principal` datetime DEFAULT NULL,
  `approved_by_dg` int(11) DEFAULT NULL,
  `approved_at_dg` datetime DEFAULT NULL,
  `rejected_by` int(11) DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `qr_code` varchar(500) DEFAULT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `student_downloadable` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `certificate_number` (`certificate_number`),
  KEY `idx_c_student` (`student_id`),
  KEY `idx_c_type` (`certificate_type`),
  KEY `idx_c_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `chemical_inventory`
--

DROP TABLE IF EXISTS `chemical_inventory`;
CREATE TABLE `chemical_inventory` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `chemical_name` varchar(200) DEFAULT NULL,
  `cas_number` varchar(50) DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT 0.00,
  `unit` varchar(30) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `storage_location` varchar(100) DEFAULT NULL,
  `safety_data_sheet` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

DROP TABLE IF EXISTS `classes`;
CREATE TABLE `classes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `code` varchar(30) DEFAULT NULL,
  `teacher_id` int(10) unsigned DEFAULT NULL,
  `room` varchar(50) DEFAULT NULL,
  `capacity` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clinical_assessments`
--

DROP TABLE IF EXISTS `clinical_assessments`;
CREATE TABLE `clinical_assessments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `placement_id` int(11) DEFAULT NULL,
  `assessment_date` date DEFAULT NULL,
  `skill_assessed` varchar(255) DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `max_score` decimal(5,2) DEFAULT 100.00,
  `passed` tinyint(1) DEFAULT 0,
  `assessed_by` int(11) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ca_student` (`student_id`),
  KEY `idx_ca_placement` (`placement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `clinical_placements`
--

DROP TABLE IF EXISTS `clinical_placements`;
CREATE TABLE `clinical_placements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `facility_name` varchar(300) NOT NULL,
  `department` varchar(200) DEFAULT '',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `supervisor_name` varchar(200) DEFAULT '',
  `supervisor_phone` varchar(50) DEFAULT '',
  `status` varchar(50) DEFAULT 'Active',
  `created_by` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `communication_channels`
--

DROP TABLE IF EXISTS `communication_channels`;
CREATE TABLE `communication_channels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department_code` varchar(20) NOT NULL,
  `department_name` varchar(255) NOT NULL,
  `routing_email` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_department_code` (`department_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `communications`
--

DROP TABLE IF EXISTS `communications`;
CREATE TABLE `communications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient_type` varchar(50) DEFAULT 'student',
  `recipient_id` int(11) DEFAULT 0,
  `subject` varchar(300) NOT NULL,
  `message` text NOT NULL,
  `channel` varchar(50) DEFAULT 'portal',
  `sent_by` int(11) DEFAULT 0,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `compliance_records`
--

DROP TABLE IF EXISTS `compliance_records`;
CREATE TABLE `compliance_records` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `compliance_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'compliant',
  `review_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `compliance_requirements`
--

DROP TABLE IF EXISTS `compliance_requirements`;
CREATE TABLE `compliance_requirements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `requirement_name` varchar(255) NOT NULL,
  `category` varchar(60) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `regulatory_body` varchar(120) DEFAULT NULL,
  `frequency` varchar(30) DEFAULT 'Annual',
  `status` enum('Compliant','Partial','Non-Compliant','Not Assessed','Exempt') DEFAULT 'Not Assessed',
  `due_date` date DEFAULT NULL,
  `last_assessment_date` date DEFAULT NULL,
  `assigned_to` int(10) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cr_status` (`status`),
  KEY `idx_cr_due` (`due_date`),
  KEY `idx_cr_category` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `compliance_requirements`
--

INSERT INTO `compliance_requirements` (`id`, `requirement_name`, `category`, `description`, `regulatory_body`, `frequency`, `status`, `due_date`, `last_assessment_date`, `assigned_to`, `notes`, `created_at`, `updated_at`) VALUES
('1', 'NCHE Annual Report', 'Academic', NULL, NULL, 'Annual', 'Not Assessed', '2026-09-18', NULL, NULL, NULL, '2026-06-20 01:28:34', NULL),
('2', 'UNMC License Renewal', 'Regulatory', NULL, NULL, 'Annual', 'Not Assessed', '2026-12-17', NULL, NULL, NULL, '2026-06-20 01:28:34', NULL),
('3', 'Fire Safety Inspection', 'Safety', NULL, NULL, 'Annual', 'Not Assessed', '2026-08-19', NULL, NULL, NULL, '2026-06-20 01:28:34', NULL),
('4', 'Tax Filing', 'Financial', NULL, NULL, 'Annual', 'Not Assessed', '2026-08-04', NULL, NULL, NULL, '2026-06-20 01:28:34', NULL),
('5', 'NCHE Annual Report', 'Academic', NULL, NULL, 'Annual', 'Not Assessed', '2026-09-18', NULL, NULL, NULL, '2026-06-20 01:41:08', NULL),
('6', 'UNMC License Renewal', 'Regulatory', NULL, NULL, 'Annual', 'Not Assessed', '2026-12-17', NULL, NULL, NULL, '2026-06-20 01:41:08', NULL),
('7', 'Fire Safety Inspection', 'Safety', NULL, NULL, 'Annual', 'Not Assessed', '2026-08-19', NULL, NULL, NULL, '2026-06-20 01:41:08', NULL),
('8', 'Tax Filing', 'Financial', NULL, NULL, 'Annual', 'Not Assessed', '2026-08-04', NULL, NULL, NULL, '2026-06-20 01:41:08', NULL),
('9', 'NCHE Annual Report', 'Academic', NULL, NULL, 'Annual', 'Not Assessed', '2026-09-18', NULL, NULL, NULL, '2026-06-20 01:45:03', NULL),
('10', 'UNMC License Renewal', 'Regulatory', NULL, NULL, 'Annual', 'Not Assessed', '2026-12-17', NULL, NULL, NULL, '2026-06-20 01:45:03', NULL),
('11', 'Fire Safety Inspection', 'Safety', NULL, NULL, 'Annual', 'Not Assessed', '2026-08-19', NULL, NULL, NULL, '2026-06-20 01:45:03', NULL),
('12', 'Tax Filing', 'Financial', NULL, NULL, 'Annual', 'Not Assessed', '2026-08-04', NULL, NULL, NULL, '2026-06-20 01:45:03', NULL),
('13', 'NCHE Annual Report', 'Academic', NULL, NULL, 'Annual', 'Not Assessed', '2026-09-18', NULL, NULL, NULL, '2026-06-20 01:46:53', NULL),
('14', 'UNMC License Renewal', 'Regulatory', NULL, NULL, 'Annual', 'Not Assessed', '2026-12-17', NULL, NULL, NULL, '2026-06-20 01:46:53', NULL),
('15', 'Fire Safety Inspection', 'Safety', NULL, NULL, 'Annual', 'Not Assessed', '2026-08-19', NULL, NULL, NULL, '2026-06-20 01:46:53', NULL),
('16', 'Tax Filing', 'Financial', NULL, NULL, 'Annual', 'Not Assessed', '2026-08-04', NULL, NULL, NULL, '2026-06-20 01:46:53', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `counseling_sessions`
--

DROP TABLE IF EXISTS `counseling_sessions`;
CREATE TABLE `counseling_sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `counselor_id` int(10) unsigned DEFAULT NULL,
  `session_date` datetime DEFAULT NULL,
  `session_type` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `status` varchar(30) DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_registrations`
--

DROP TABLE IF EXISTS `course_registrations`;
CREATE TABLE `course_registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(30) DEFAULT NULL,
  `status` enum('Pending','Submitted','Registered','Approved','Rejected','Dropped') DEFAULT 'Pending',
  `registration_date` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `course_id` (`course_id`),
  KEY `status` (`status`),
  KEY `idx_cr_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `daily_sick_records`
--

DROP TABLE IF EXISTS `daily_sick_records`;
CREATE TABLE `daily_sick_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `referred_to` varchar(255) DEFAULT NULL,
  `attended_by` varchar(200) DEFAULT NULL,
  `visit_date` date NOT NULL,
  `visit_time` time DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `record_number` (`record_number`),
  KEY `student_id` (`student_id`),
  KEY `sickness_id` (`sickness_id`),
  KEY `visit_date` (`visit_date`),
  KEY `status` (`status`),
  KEY `severity` (`severity`),
  KEY `student_name` (`student_name`),
  KEY `program` (`program`),
  KEY `dsr_student_date` (`student_id`,`visit_date`),
  CONSTRAINT `daily_sick_records_ibfk_1` FOREIGN KEY (`sickness_id`) REFERENCES `sickness_directory` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `data_ownership_rules`
--

DROP TABLE IF EXISTS `data_ownership_rules`;
CREATE TABLE `data_ownership_rules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int(10) unsigned NOT NULL,
  `department_code` varchar(20) DEFAULT NULL,
  `data_category` varchar(100) NOT NULL DEFAULT 'all',
  `access_level` enum('none','read','write','full') DEFAULT 'none',
  `is_owner` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_dor_role` (`role_id`),
  KEY `idx_dor_dept` (`department_code`),
  KEY `idx_dor_category` (`data_category`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `data_ownership_rules`
--

INSERT INTO `data_ownership_rules` (`id`, `role_id`, `department_code`, `data_category`, `access_level`, `is_owner`, `created_at`) VALUES
('1', '1', NULL, 'all', 'full', '1', '2026-06-20 01:28:34'),
('2', '3', NULL, 'all', 'full', '1', '2026-06-20 01:28:34'),
('3', '4', NULL, 'all', 'full', '1', '2026-06-20 01:28:34'),
('4', '1', NULL, 'all', 'full', '1', '2026-06-20 01:41:08'),
('5', '3', NULL, 'all', 'full', '1', '2026-06-20 01:41:08'),
('6', '4', NULL, 'all', 'full', '1', '2026-06-20 01:41:08'),
('7', '1', NULL, 'all', 'full', '1', '2026-06-20 01:45:02'),
('8', '3', NULL, 'all', 'full', '1', '2026-06-20 01:45:02'),
('9', '4', NULL, 'all', 'full', '1', '2026-06-20 01:45:02'),
('10', '1', NULL, 'all', 'full', '1', '2026-06-20 01:46:53'),
('11', '3', NULL, 'all', 'full', '1', '2026-06-20 01:46:53'),
('12', '4', NULL, 'all', 'full', '1', '2026-06-20 01:46:53');

-- --------------------------------------------------------

--
-- Table structure for table `data_sync_status`
--

DROP TABLE IF EXISTS `data_sync_status`;
CREATE TABLE `data_sync_status` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sync_type` varchar(100) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `records_processed` int(11) DEFAULT 0,
  `errors` int(11) DEFAULT 0,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `department_reviews`
--

DROP TABLE IF EXISTS `department_reviews`;
CREATE TABLE `department_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department` varchar(200) DEFAULT NULL,
  `reviewer_id` int(11) DEFAULT NULL,
  `review_period` varchar(50) DEFAULT NULL,
  `overall_score` decimal(5,2) DEFAULT NULL,
  `strengths` text DEFAULT NULL,
  `weaknesses` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `status` enum('draft','submitted','reviewed') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `department_targets`
--

DROP TABLE IF EXISTS `department_targets`;
CREATE TABLE `department_targets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `department_code` varchar(20) NOT NULL,
  `fiscal_year` varchar(10) DEFAULT NULL,
  `target_name` varchar(255) NOT NULL,
  `target_category` varchar(60) DEFAULT NULL,
  `target_value` decimal(12,2) DEFAULT NULL,
  `actual_value` decimal(12,2) DEFAULT NULL,
  `unit` varchar(30) DEFAULT NULL,
  `weight_pct` decimal(5,2) DEFAULT 100.00,
  `status` enum('Pending','In Progress','Achieved','Not Met','Delayed') DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_dt_dept` (`department_code`),
  KEY `idx_dt_fiscal` (`fiscal_year`),
  KEY `idx_dt_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
CREATE TABLE `departments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `code` varchar(30) DEFAULT NULL,
  `hod_id` int(10) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dg_read_notifications`
--

DROP TABLE IF EXISTS `dg_read_notifications`;
CREATE TABLE `dg_read_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_key` varchar(64) NOT NULL,
  `user_id` int(11) NOT NULL,
  `read_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_nk_uid` (`notification_key`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `director_departments`
--

DROP TABLE IF EXISTS `director_departments`;
CREATE TABLE `director_departments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int(10) unsigned NOT NULL,
  `department_code` varchar(20) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_dd_role_dept` (`role_id`,`department_code`),
  KEY `idx_dd_role` (`role_id`),
  KEY `idx_dd_dept` (`department_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `director_news`
--

DROP TABLE IF EXISTS `director_news`;
CREATE TABLE `director_news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `excerpt` text DEFAULT NULL,
  `featured_image` varchar(500) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `author_id` int(11) DEFAULT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `director_performance_reviews`
--

DROP TABLE IF EXISTS `director_performance_reviews`;
CREATE TABLE `director_performance_reviews` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(10) unsigned NOT NULL,
  `review_period` varchar(60) DEFAULT NULL,
  `fiscal_year` varchar(10) DEFAULT NULL,
  `overall_score` decimal(5,2) DEFAULT NULL,
  `targets_met` int(11) DEFAULT 0,
  `targets_total` int(11) DEFAULT 0,
  `summary` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `reviewed_by` int(10) unsigned DEFAULT NULL,
  `status` enum('Draft','Submitted','Approved','Archived') DEFAULT 'Draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_dpr_staff` (`staff_id`),
  KEY `idx_dpr_fiscal` (`fiscal_year`),
  KEY `idx_dpr_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `duty_roster`
--

DROP TABLE IF EXISTS `duty_roster`;
CREATE TABLE `duty_roster` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(10) unsigned DEFAULT NULL,
  `duty_date` date DEFAULT NULL,
  `shift` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `duty_rosters`
--

DROP TABLE IF EXISTS `duty_rosters`;
CREATE TABLE `duty_rosters` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(10) unsigned DEFAULT NULL,
  `duty_date` date DEFAULT NULL,
  `duty_type` varchar(100) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `status` varchar(30) DEFAULT 'scheduled',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `error_logs`
--

DROP TABLE IF EXISTS `error_logs`;
CREATE TABLE `error_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `error_type` varchar(100) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `file` varchar(500) DEFAULT NULL,
  `line` int(11) DEFAULT NULL,
  `stack_trace` text DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_results`
--

DROP TABLE IF EXISTS `exam_results`;
CREATE TABLE `exam_results` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `exam_id` int(10) unsigned NOT NULL,
  `student_id` int(10) unsigned NOT NULL,
  `marks_obtained` decimal(5,2) DEFAULT 0.00,
  `grade` varchar(5) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `exam_id` (`exam_id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_schedules`
--

DROP TABLE IF EXISTS `exam_schedules`;
CREATE TABLE `exam_schedules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `exam_id` int(10) unsigned NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room` varchar(50) DEFAULT NULL,
  `invigilator_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `examination_records`
--

DROP TABLE IF EXISTS `examination_records`;
CREATE TABLE `examination_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `exam_number` varchar(50) DEFAULT NULL,
  `exam_type` varchar(50) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `continuous_assessment_marks` decimal(8,2) DEFAULT NULL,
  `final_exam_marks` decimal(8,2) DEFAULT NULL,
  `marks_obtained` decimal(8,2) DEFAULT NULL,
  `total_marks` decimal(8,2) DEFAULT 100.00,
  `grade` varchar(5) DEFAULT NULL,
  `grade_status` enum('Not Entered','Entered','Approved','Published') DEFAULT 'Not Entered',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `course_code` (`course_code`),
  KEY `exam_type` (`exam_type`),
  KEY `idx_exam_student` (`student_id`),
  KEY `idx_exam_course` (`course_code`),
  KEY `idx_exam_status` (`grade_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exams`
--

DROP TABLE IF EXISTS `exams`;
CREATE TABLE `exams` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `subject_id` int(10) unsigned DEFAULT NULL,
  `class_id` int(10) unsigned DEFAULT NULL,
  `date` date DEFAULT NULL,
  `duration` int(11) DEFAULT 0,
  `total_marks` int(11) DEFAULT 100,
  `passing_marks` int(11) DEFAULT 50,
  `term` varchar(30) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
CREATE TABLE `expenses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `expense_number` varchar(60) DEFAULT NULL,
  `category` varchar(80) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `expense_date` date DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `approved_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_expenses_date` (`expense_date`),
  KEY `idx_expenses_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `expenses`
--

INSERT INTO `expenses` (`id`, `expense_number`, `category`, `description`, `amount`, `expense_date`, `status`, `approved_by`, `created_at`) VALUES
('1', NULL, 'Supplies', 'Sample Supplies expense', '1155541.00', '2014-01-20', 'approved', NULL, '2026-06-19 23:58:56'),
('2', NULL, 'Equipment', 'Sample Equipment expense', '281885.00', '2022-12-20', 'approved', NULL, '2026-06-19 23:58:56'),
('3', NULL, 'Salaries', 'Sample Salaries expense', '1791389.00', '2017-11-20', 'approved', NULL, '2026-06-19 23:58:56'),
('4', NULL, 'Transport', 'Sample Transport expense', '932502.00', '2019-06-20', 'approved', NULL, '2026-06-19 23:58:56'),
('5', NULL, 'Other', 'Sample Other expense', '1195613.00', '2015-10-20', 'approved', NULL, '2026-06-19 23:58:56'),
('6', NULL, 'Maintenance', 'Sample Maintenance expense', '1799641.00', '2021-08-20', 'approved', NULL, '2026-06-19 23:58:56'),
('7', NULL, 'Other', 'Sample Other expense', '577084.00', '2023-11-20', 'approved', NULL, '2026-06-19 23:58:56'),
('8', NULL, 'Other', 'Sample Other expense', '459948.00', '2015-08-20', 'approved', NULL, '2026-06-19 23:58:56'),
('9', NULL, 'Utilities', 'Sample Utilities expense', '1660252.00', '2013-05-20', 'approved', NULL, '2026-06-19 23:58:56'),
('10', NULL, 'Maintenance', 'Sample Maintenance expense', '1097576.00', '2022-01-20', 'approved', NULL, '2026-06-19 23:58:56'),
('11', NULL, 'Maintenance', 'Sample Maintenance expense', '1769462.00', '2016-02-20', 'approved', NULL, '2026-06-19 23:58:56'),
('12', NULL, 'Other', 'Sample Other expense', '1057051.00', '2012-12-20', 'approved', NULL, '2026-06-19 23:58:56'),
('13', NULL, 'Other', 'Sample Other expense', '99759.00', '2012-05-20', 'approved', NULL, '2026-06-19 23:58:56'),
('14', NULL, 'Supplies', 'Sample Supplies expense', '1509836.00', '2025-01-20', 'approved', NULL, '2026-06-19 23:58:56'),
('15', NULL, 'Equipment', 'Sample Equipment expense', '1842522.00', '2016-10-20', 'approved', NULL, '2026-06-19 23:58:56'),
('16', NULL, 'Other', 'Sample Other expense', '412867.00', '2020-02-20', 'approved', NULL, '2026-06-19 23:58:56'),
('17', NULL, 'Salaries', 'Sample Salaries expense', '349421.00', '2012-06-20', 'approved', NULL, '2026-06-19 23:58:56'),
('18', NULL, 'Maintenance', 'Sample Maintenance expense', '1440233.00', '2016-01-20', 'approved', NULL, '2026-06-19 23:58:56'),
('19', NULL, 'Utilities', 'Sample Utilities expense', '164347.00', '2017-03-20', 'approved', NULL, '2026-06-19 23:58:56'),
('20', NULL, 'Equipment', 'Sample Equipment expense', '585657.00', '2017-02-20', 'approved', NULL, '2026-06-19 23:58:56'),
('21', NULL, 'Equipment', 'Sample Equipment expense', '322309.00', '2015-09-20', 'approved', NULL, '2026-06-19 23:58:56'),
('22', NULL, 'Supplies', 'Sample Supplies expense', '1484606.00', '2020-11-20', 'approved', NULL, '2026-06-19 23:58:56'),
('23', NULL, 'Equipment', 'Sample Equipment expense', '185112.00', '2011-08-20', 'approved', NULL, '2026-06-19 23:58:56'),
('24', NULL, 'Equipment', 'Sample Equipment expense', '286701.00', '2013-05-20', 'approved', NULL, '2026-06-19 23:58:56'),
('25', NULL, 'Maintenance', 'Sample Maintenance expense', '1019441.00', '2020-12-20', 'approved', NULL, '2026-06-19 23:58:56'),
('26', NULL, 'Maintenance', 'Sample Maintenance expense', '778746.00', '2015-06-20', 'approved', NULL, '2026-06-19 23:58:56'),
('27', NULL, 'Other', 'Sample Other expense', '1680279.00', '2025-11-20', 'approved', NULL, '2026-06-19 23:58:56'),
('28', NULL, 'Supplies', 'Sample Supplies expense', '1579464.00', '2018-09-20', 'approved', NULL, '2026-06-19 23:58:56'),
('29', NULL, 'Salaries', 'Sample Salaries expense', '1274586.00', '2022-08-20', 'approved', NULL, '2026-06-19 23:58:56'),
('30', NULL, 'Other', 'Sample Other expense', '172348.00', '2011-07-20', 'approved', NULL, '2026-06-19 23:58:56');

-- --------------------------------------------------------

--
-- Table structure for table `facilities`
--

DROP TABLE IF EXISTS `facilities`;
CREATE TABLE `facilities` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(100) DEFAULT NULL,
  `capacity` int(11) DEFAULT 0,
  `location` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(30) DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `facility_bookings`
--

DROP TABLE IF EXISTS `facility_bookings`;
CREATE TABLE `facility_bookings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `facility_id` int(10) unsigned NOT NULL,
  `booked_by` int(10) unsigned NOT NULL,
  `purpose` text DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `approved_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_adjustments`
--

DROP TABLE IF EXISTS `fee_adjustments`;
CREATE TABLE `fee_adjustments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(50) NOT NULL,
  `adjustment_type` varchar(50) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_payments`
--

DROP TABLE IF EXISTS `fee_payments`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `fee_payments` AS select `igangaschoolofl_students_db`.`payments`.`id` AS `id`,`igangaschoolofl_students_db`.`payments`.`student_id` AS `student_id`,`igangaschoolofl_students_db`.`payments`.`invoice_id` AS `fee_account_id`,`igangaschoolofl_students_db`.`payments`.`amount_received` AS `amount_paid`,`igangaschoolofl_students_db`.`payments`.`payment_method` AS `payment_method`,`igangaschoolofl_students_db`.`payments`.`payment_reference` AS `receipt_number`,`igangaschoolofl_students_db`.`payments`.`status` AS `status`,`igangaschoolofl_students_db`.`payments`.`payment_date` AS `payment_date`,`igangaschoolofl_students_db`.`payments`.`notes` AS `notes`,`igangaschoolofl_students_db`.`payments`.`received_by` AS `processed_by`,`igangaschoolofl_students_db`.`payments`.`created_at` AS `created_at`,`igangaschoolofl_students_db`.`payments`.`updated_at` AS `updated_at` from `igangaschoolofl_students_db`.`payments`;

-- --------------------------------------------------------

--
-- Table structure for table `financial_messages`
--

DROP TABLE IF EXISTS `financial_messages`;
CREATE TABLE `financial_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `attachment` varchar(255) DEFAULT '',
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `sender_role` varchar(50) DEFAULT '',
  `recipient_role` varchar(50) DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sender` (`sender_id`),
  KEY `idx_recipient` (`recipient_id`),
  KEY `idx_read` (`is_read`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `financial_notices`
--

DROP TABLE IF EXISTS `financial_notices`;
CREATE TABLE `financial_notices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `author_id` int(11) NOT NULL,
  `author_role` varchar(50) DEFAULT '',
  `priority` enum('Low','Normal','High','Urgent') DEFAULT 'Normal',
  `is_published` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_published` (`is_published`),
  KEY `idx_priority` (`priority`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `generated_documents`
--

DROP TABLE IF EXISTS `generated_documents`;
CREATE TABLE `generated_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_type` varchar(50) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `document_title` varchar(255) DEFAULT NULL,
  `document_description` varchar(500) DEFAULT NULL,
  `month` int(11) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `gross_salary` decimal(12,2) DEFAULT 0.00,
  `net_pay` decimal(12,2) DEFAULT 0.00,
  `file_path` varchar(500) DEFAULT NULL,
  `document_content` longtext DEFAULT NULL,
  `access_code` varchar(100) DEFAULT NULL,
  `generation_date` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `document_type` (`document_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gpa_settings`
--

DROP TABLE IF EXISTS `gpa_settings`;
CREATE TABLE `gpa_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `gpa_settings`
--

INSERT INTO `gpa_settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES
('1', 'pass_mark', '50', 'Minimum pass percentage', '2026-06-26 06:25:09', '2026-06-26 06:25:09'),
('2', 'distinction_threshold', '80', 'Minimum percentage for Distinction', '2026-06-26 06:25:09', '2026-06-26 06:25:09'),
('3', 'credit_threshold', '60', 'Minimum percentage for Credit', '2026-06-26 06:25:09', '2026-06-26 06:25:09'),
('4', 'supplementary_min', '35', 'Minimum percentage eligible for supplementary exam', '2026-06-26 06:25:09', '2026-06-26 06:25:09'),
('5', 'max_supplementary_grade', 'C', 'Maximum grade after supplementary exam', '2026-06-26 06:25:09', '2026-06-26 06:25:09'),
('6', 'retake_max_attempts', '3', 'Maximum retake attempts allowed', '2026-06-26 06:25:09', '2026-06-26 06:25:09'),
('7', 'academic_probation_cgpa', '1.50', 'CGPA below this triggers academic probation', '2026-06-26 06:25:09', '2026-06-26 06:25:09'),
('8', 'suspension_cgpa', '1.00', 'CGPA below this triggers suspension', '2026-06-26 06:25:09', '2026-06-26 06:25:09'),
('9', 'graduation_min_cgpa', '2.00', 'Minimum CGPA required for graduation', '2026-06-26 06:25:09', '2026-06-26 06:25:09'),
('10', 'grading_system', 'letter', 'Grading system type', '2026-06-26 06:25:09', '2026-06-26 06:25:09');

-- --------------------------------------------------------

--
-- Table structure for table `grade_scale`
--

DROP TABLE IF EXISTS `grade_scale`;
CREATE TABLE `grade_scale` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grade_letter` varchar(5) NOT NULL,
  `grade_point` decimal(4,2) NOT NULL,
  `min_percentage` decimal(5,2) NOT NULL,
  `max_percentage` decimal(5,2) NOT NULL,
  `description` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `grade_letter` (`grade_letter`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `grade_scale`
--

INSERT INTO `grade_scale` (`id`, `grade_letter`, `grade_point`, `min_percentage`, `max_percentage`, `description`, `is_active`, `created_at`) VALUES
('1', 'A', '4.00', '80.00', '100.00', 'Distinction', '1', '2026-06-26 06:25:09'),
('2', 'B', '3.00', '70.00', '79.99', 'Credit', '1', '2026-06-26 06:25:09'),
('3', 'C', '2.00', '60.00', '69.99', 'Credit', '1', '2026-06-26 06:25:09'),
('4', 'D', '1.00', '50.00', '59.99', 'Pass', '1', '2026-06-26 06:25:09'),
('5', 'F', '0.00', '0.00', '49.99', 'Fail', '1', '2026-06-26 06:25:09');

-- --------------------------------------------------------

--
-- Table structure for table `grade_scales`
--

DROP TABLE IF EXISTS `grade_scales`;
CREATE TABLE `grade_scales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grade_letter` varchar(5) NOT NULL,
  `grade_point` decimal(4,2) DEFAULT 0.00,
  `min_percentage` decimal(5,2) DEFAULT 0.00,
  `max_percentage` decimal(5,2) DEFAULT 100.00,
  `status` varchar(50) DEFAULT 'Active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_grade` (`grade_letter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

DROP TABLE IF EXISTS `grades`;
CREATE TABLE `grades` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `subject_id` int(10) unsigned DEFAULT NULL,
  `grade` varchar(5) DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `term` varchar(30) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `grading_approval_workflow`
--

DROP TABLE IF EXISTS `grading_approval_workflow`;
CREATE TABLE `grading_approval_workflow` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `current_stage` varchar(100) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Returned') DEFAULT 'Pending',
  `comments` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `current_stage` (`current_stage`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `graduation_approvals`
--

DROP TABLE IF EXISTS `graduation_approvals`;
CREATE TABLE `graduation_approvals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `graduation_id` int(11) NOT NULL,
  `approval_level` enum('senate','principal','director_general') NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ga_grad` (`graduation_id`),
  KEY `idx_ga_level` (`approval_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `graduation_candidates`
--

DROP TABLE IF EXISTS `graduation_candidates`;
CREATE TABLE `graduation_candidates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `program` varchar(255) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `cgpa` decimal(4,2) DEFAULT NULL,
  `class_of_award` varchar(100) DEFAULT NULL,
  `total_credits` int(11) DEFAULT 0,
  `bursar_cleared` tinyint(1) DEFAULT 0,
  `library_cleared` tinyint(1) DEFAULT 0,
  `registrar_cleared` tinyint(1) DEFAULT 0,
  `hod_cleared` tinyint(1) DEFAULT 0,
  `is_eligible` tinyint(1) DEFAULT 0,
  `senate_approved` tinyint(1) DEFAULT 0,
  `senate_approved_at` datetime DEFAULT NULL,
  `principal_approved` tinyint(1) DEFAULT 0,
  `principal_approved_at` datetime DEFAULT NULL,
  `dg_approved` tinyint(1) DEFAULT 0,
  `dg_approved_at` datetime DEFAULT NULL,
  `status` enum('pending','eligible','approved','graduated','deferred') DEFAULT 'pending',
  `graduation_date` date DEFAULT NULL,
  `ceremony_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_gc_student` (`student_id`),
  KEY `idx_gc_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hostel_management`
--

DROP TABLE IF EXISTS `hostel_management`;
CREATE TABLE `hostel_management` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_number` varchar(20) NOT NULL,
  `hostel_name` varchar(100) NOT NULL,
  `capacity` int(11) NOT NULL,
  `occupied` int(11) DEFAULT 0,
  `room_type` enum('Single','Double','Dormitory') NOT NULL,
  `gender` enum('Male','Female','Mixed') NOT NULL,
  `status` enum('Available','Occupied','Under Maintenance') DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_number` (`room_number`),
  KEY `idx_room_number` (`room_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `hr_activity_log`
--

DROP TABLE IF EXISTS `hr_activity_log`;
CREATE TABLE `hr_activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `institutional_alerts`
--

DROP TABLE IF EXISTS `institutional_alerts`;
CREATE TABLE `institutional_alerts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `alert_type` varchar(30) DEFAULT 'info',
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `department_code` varchar(20) DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  `is_resolved` tinyint(1) DEFAULT 0,
  `resolved_by` int(10) unsigned DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ia_priority` (`priority`),
  KEY `idx_ia_resolved` (`is_resolved`),
  KEY `idx_ia_dept` (`department_code`),
  KEY `idx_ia_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `institutional_alerts`
--

INSERT INTO `institutional_alerts` (`id`, `title`, `description`, `alert_type`, `priority`, `department_code`, `source`, `is_resolved`, `resolved_by`, `resolved_at`, `created_by`, `created_at`) VALUES
('1', 'Staff Attendance Drop', 'Staff attendance dropped below 80% this week.', 'info', 'high', NULL, NULL, '0', NULL, NULL, NULL, '2026-06-20 01:28:34'),
('2', 'Fee Collection Target', 'Monthly fee collection at 65% of target.', 'info', 'medium', NULL, NULL, '0', NULL, NULL, NULL, '2026-06-20 01:28:34'),
('3', 'Exam Preparation', 'Final exams scheduled in 3 weeks.', 'info', 'low', NULL, NULL, '0', NULL, NULL, NULL, '2026-06-20 01:28:34'),
('4', 'Test Alert', 'Test', 'info', 'low', NULL, NULL, '0', NULL, NULL, NULL, '2026-06-20 01:33:53'),
('5', 'Staff Attendance Drop', 'Staff attendance dropped below 80% this week.', 'info', 'high', NULL, NULL, '0', NULL, NULL, NULL, '2026-06-20 01:37:17'),
('6', 'Fee Collection Target', 'Monthly fee collection at 65% of target.', 'info', 'medium', NULL, NULL, '0', NULL, NULL, NULL, '2026-06-20 01:37:17'),
('7', 'Exam Preparation', 'Final exams scheduled in 3 weeks.', 'info', 'low', NULL, NULL, '0', NULL, NULL, NULL, '2026-06-20 01:37:17'),
('8', 'Staff Attendance Drop', 'Staff attendance dropped below 80% this week.', 'info', 'high', NULL, NULL, '0', NULL, NULL, NULL, '2026-06-20 01:41:08'),
('9', 'Fee Collection Target', 'Monthly fee collection at 65% of target.', 'info', 'medium', NULL, NULL, '0', NULL, NULL, NULL, '2026-06-20 01:41:08'),
('10', 'Exam Preparation', 'Final exams scheduled in 3 weeks.', 'info', 'low', NULL, NULL, '0', NULL, NULL, NULL, '2026-06-20 01:41:08'),
('11', 'Staff Attendance Drop', 'Staff attendance dropped below 80% this week.', 'info', 'high', NULL, NULL, '0', NULL, NULL, NULL, '2026-06-20 01:45:03'),
('12', 'Fee Collection Target', 'Monthly fee collection at 65% of target.', 'info', 'medium', NULL, NULL, '0', NULL, NULL, NULL, '2026-06-20 01:45:03'),
('13', 'Exam Preparation', 'Final exams scheduled in 3 weeks.', 'info', 'low', NULL, NULL, '0', NULL, NULL, NULL, '2026-06-20 01:45:03'),
('14', 'Staff Attendance Drop', 'Staff attendance dropped below 80% this week.', 'info', 'high', NULL, NULL, '0', NULL, NULL, NULL, '2026-06-20 01:46:53'),
('15', 'Fee Collection Target', 'Monthly fee collection at 65% of target.', 'info', 'medium', NULL, NULL, '0', NULL, NULL, NULL, '2026-06-20 01:46:53'),
('16', 'Exam Preparation', 'Final exams scheduled in 3 weeks.', 'info', 'low', NULL, NULL, '0', NULL, NULL, NULL, '2026-06-20 01:46:53');

-- --------------------------------------------------------

--
-- Table structure for table `institutional_risks`
--

DROP TABLE IF EXISTS `institutional_risks`;
CREATE TABLE `institutional_risks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `risk_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `risk_category` varchar(60) DEFAULT NULL,
  `likelihood` enum('Rare','Unlikely','Possible','Likely','Almost Certain') DEFAULT 'Possible',
  `impact` enum('Negligible','Minor','Moderate','Major','Severe') DEFAULT 'Moderate',
  `risk_score` int(11) DEFAULT 0,
  `mitigation_strategy` text DEFAULT NULL,
  `contingency_plan` text DEFAULT NULL,
  `owner` int(10) unsigned DEFAULT NULL,
  `status` enum('Identified','Assessed','Mitigated','Monitoring','Closed') DEFAULT 'Identified',
  `target_resolution` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ir_score` (`risk_score`),
  KEY `idx_ir_status` (`status`),
  KEY `idx_ir_category` (`risk_category`),
  KEY `idx_ir_owner` (`owner`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `institutional_risks`
--

INSERT INTO `institutional_risks` (`id`, `risk_name`, `description`, `risk_category`, `likelihood`, `impact`, `risk_score`, `mitigation_strategy`, `contingency_plan`, `owner`, `status`, `target_resolution`, `created_at`, `updated_at`) VALUES
('1', 'Student Enrolment Decline', NULL, 'Operational', 'Possible', 'Major', '12', NULL, NULL, NULL, 'Identified', NULL, '2026-06-20 01:28:34', NULL),
('2', 'Staff Retention', NULL, 'HR', 'Likely', 'Moderate', '12', NULL, NULL, NULL, 'Monitoring', NULL, '2026-06-20 01:28:34', NULL),
('3', 'Budget Shortfall', NULL, 'Financial', 'Possible', 'Major', '12', NULL, NULL, NULL, 'Identified', NULL, '2026-06-20 01:28:34', NULL),
('4', 'Regulatory Non-Compliance', NULL, 'Compliance', 'Unlikely', 'Major', '6', NULL, NULL, NULL, 'Monitoring', NULL, '2026-06-20 01:28:34', NULL),
('5', 'Student Enrolment Decline', NULL, 'Operational', 'Possible', 'Major', '12', NULL, NULL, NULL, 'Identified', NULL, '2026-06-20 01:41:08', NULL),
('6', 'Staff Retention', NULL, 'HR', 'Likely', 'Moderate', '12', NULL, NULL, NULL, 'Monitoring', NULL, '2026-06-20 01:41:08', NULL),
('7', 'Budget Shortfall', NULL, 'Financial', 'Possible', 'Major', '12', NULL, NULL, NULL, 'Identified', NULL, '2026-06-20 01:41:08', NULL),
('8', 'Regulatory Non-Compliance', NULL, 'Compliance', 'Unlikely', 'Major', '6', NULL, NULL, NULL, 'Monitoring', NULL, '2026-06-20 01:41:08', NULL),
('9', 'Student Enrolment Decline', NULL, 'Operational', 'Possible', 'Major', '12', NULL, NULL, NULL, 'Identified', NULL, '2026-06-20 01:45:03', NULL),
('10', 'Staff Retention', NULL, 'HR', 'Likely', 'Moderate', '12', NULL, NULL, NULL, 'Monitoring', NULL, '2026-06-20 01:45:03', NULL),
('11', 'Budget Shortfall', NULL, 'Financial', 'Possible', 'Major', '12', NULL, NULL, NULL, 'Identified', NULL, '2026-06-20 01:45:03', NULL),
('12', 'Regulatory Non-Compliance', NULL, 'Compliance', 'Unlikely', 'Major', '6', NULL, NULL, NULL, 'Monitoring', NULL, '2026-06-20 01:45:03', NULL),
('13', 'Student Enrolment Decline', NULL, 'Operational', 'Possible', 'Major', '12', NULL, NULL, NULL, 'Identified', NULL, '2026-06-20 01:46:53', NULL),
('14', 'Staff Retention', NULL, 'HR', 'Likely', 'Moderate', '12', NULL, NULL, NULL, 'Monitoring', NULL, '2026-06-20 01:46:53', NULL),
('15', 'Budget Shortfall', NULL, 'Financial', 'Possible', 'Major', '12', NULL, NULL, NULL, 'Identified', NULL, '2026-06-20 01:46:53', NULL),
('16', 'Regulatory Non-Compliance', NULL, 'Compliance', 'Unlikely', 'Major', '6', NULL, NULL, NULL, 'Monitoring', NULL, '2026-06-20 01:46:53', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `intakes`
--

DROP TABLE IF EXISTS `intakes`;
CREATE TABLE `intakes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `intake_name` varchar(200) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
CREATE TABLE `inventory` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `item_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT 0,
  `unit` varchar(30) DEFAULT NULL,
  `unit_price` decimal(12,2) DEFAULT 0.00,
  `supplier` varchar(200) DEFAULT NULL,
  `reorder_level` int(11) DEFAULT 0,
  `location` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_reports`
--

DROP TABLE IF EXISTS `inventory_reports`;
CREATE TABLE `inventory_reports` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `report_type` varchar(100) DEFAULT NULL,
  `generated_by` int(10) unsigned DEFAULT NULL,
  `parameters` text DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `it_infrastructure`
--

DROP TABLE IF EXISTS `it_infrastructure`;
CREATE TABLE `it_infrastructure` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `asset_name` varchar(200) DEFAULT NULL,
  `asset_type` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'operational',
  `purchase_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_applications`
--

DROP TABLE IF EXISTS `job_applications`;
CREATE TABLE `job_applications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `recruitment_id` int(10) unsigned DEFAULT NULL,
  `applicant_name` varchar(200) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `cover_letter` text DEFAULT NULL,
  `resume_path` varchar(500) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `late_payment_settings`
--

DROP TABLE IF EXISTS `late_payment_settings`;
CREATE TABLE `late_payment_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leaves`
--

DROP TABLE IF EXISTS `leaves`;
CREATE TABLE `leaves` (
  `s_no` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` int(10) unsigned NOT NULL,
  `leave_type` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `send_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_by` int(10) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`s_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_books`
--

DROP TABLE IF EXISTS `library_books`;
CREATE TABLE `library_books` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `isbn` varchar(50) DEFAULT NULL,
  `publisher` varchar(200) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `available` int(11) DEFAULT 1,
  `shelf_location` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `library_fines`
--

DROP TABLE IF EXISTS `library_fines`;
CREATE TABLE `library_fines` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `borrowing_id` int(10) unsigned NOT NULL,
  `student_id` int(10) unsigned NOT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `reason` varchar(200) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'unpaid',
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `meal_tracking`
--

DROP TABLE IF EXISTS `meal_tracking`;
CREATE TABLE `meal_tracking` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned DEFAULT NULL,
  `meal_type` varchar(50) DEFAULT NULL,
  `meal_date` date DEFAULT NULL,
  `meal_time` time DEFAULT NULL,
  `status` varchar(30) DEFAULT 'served',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `medicine_stock`
--

DROP TABLE IF EXISTS `medicine_stock`;
CREATE TABLE `medicine_stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `medicine_code` (`medicine_code`),
  KEY `medicine_name` (`medicine_name`),
  KEY `category` (`category`),
  KEY `expiry_date` (`expiry_date`),
  KEY `status` (`status`),
  KEY `supplier` (`supplier`),
  KEY `ms_expiry_status` (`expiry_date`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `medicine_stock`
--

INSERT INTO `medicine_stock` (`id`, `medicine_code`, `medicine_name`, `generic_name`, `category`, `dosage_form`, `strength`, `manufacturer`, `supplier`, `quantity_in_stock`, `unit`, `reorder_level`, `unit_cost`, `selling_price`, `currency`, `batch_number`, `expiry_date`, `storage_location`, `requires_prescription`, `instructions`, `side_effects`, `status`, `last_restocked`, `created_by`, `created_at`, `updated_at`) VALUES
('1', 'PARA001', 'Paracetamol', 'Acetaminophen', 'Painkiller', 'Tablet', '500mg', NULL, NULL, '200', 'tablets', '50', '50.00', NULL, 'UGX', NULL, '2027-12-31', 'Cabinet A1', '0', '1-2 tablets every 4-6 hours as needed for pain/fever', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('2', 'IBU001', 'Ibuprofen', 'Ibuprofen', 'Anti-inflammatory', 'Tablet', '400mg', NULL, NULL, '150', 'tablets', '30', '100.00', NULL, 'UGX', NULL, '2027-10-31', 'Cabinet A1', '0', '1 tablet 3 times daily after meals', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('3', 'AMOX001', 'Amoxicillin', 'Amoxicillin', 'Antibiotic', 'Capsule', '500mg', NULL, NULL, '100', 'capsules', '20', '200.00', NULL, 'UGX', NULL, '2027-08-31', 'Cabinet B1', '1', '1 capsule 3 times daily for 7 days', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('4', 'CTM001', 'Chlorpheniramine', 'Chlorpheniramine Maleate', 'Allergy', 'Tablet', '4mg', NULL, NULL, '100', 'tablets', '20', '50.00', NULL, 'UGX', NULL, '2027-11-30', 'Cabinet A2', '0', '1 tablet every 4-6 hours for allergies', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('5', 'ORS001', 'Oral Rehydration Salts', 'ORS', 'Other', 'Powder', '20.5g/sachet', NULL, NULL, '100', 'sachets', '30', '500.00', NULL, 'UGX', NULL, '2028-06-30', 'Cabinet C1', '0', 'Dissolve 1 sachet in 1L water, drink after each loose stool', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('6', 'ART001', 'Artemether/Lumefantrine', 'Coartem', 'Antimalarial', 'Tablet', '20/120mg', NULL, NULL, '60', 'tablets', '20', '1500.00', NULL, 'UGX', NULL, '2027-09-30', 'Cabinet B2', '1', '4 tablets twice daily for 3 days', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('7', 'VITC001', 'Vitamin C', 'Ascorbic Acid', 'Vitamins', 'Tablet', '500mg', NULL, NULL, '300', 'tablets', '50', '30.00', NULL, 'UGX', NULL, '2028-12-31', 'Cabinet C1', '0', '1 tablet daily for immune support', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('8', 'MET001', 'Metered Dose Inhaler', 'Salbutamol', 'Respiratory', 'Inhaler', '100mcg/dose', NULL, NULL, '10', 'inhalers', '3', '15000.00', NULL, 'UGX', NULL, '2027-06-30', 'Cabinet A3', '1', '1-2 puffs as needed for asthma symptoms', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('9', 'ANT001', 'Antacid', 'Aluminum/Magnesium Hydroxide', 'Digestive', 'Tablet', '500mg', NULL, NULL, '200', 'tablets', '40', '100.00', NULL, 'UGX', NULL, '2027-11-30', 'Cabinet C1', '0', '1-2 tablets after meals or when symptomatic', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('10', 'HYD001', 'Hydrocortisone Cream', 'Hydrocortisone', 'Dermatological', 'Cream', '1%', NULL, NULL, '20', 'tubes', '5', '5000.00', NULL, 'UGX', NULL, '2027-08-31', 'Cabinet D1', '0', 'Apply thin layer to affected area 2-3 times daily', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('11', 'DIA001', 'Diazepam', 'Diazepam', 'Painkiller', 'Tablet', '5mg', NULL, NULL, '30', 'tablets', '10', '200.00', NULL, 'UGX', NULL, '2026-12-31', 'Cabinet B2', '1', '1 tablet at bedtime for anxiety or muscle spasms', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('12', 'BAN001', 'Bandages', 'Cotton Bandage', 'First Aid', 'Other', '4 inches x 5 meters', NULL, NULL, '50', 'rolls', '10', '1500.00', NULL, 'UGX', NULL, '2029-12-31', 'Shelf E1', '0', 'For wound dressing and injury management', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('13', 'GAU001', 'Gauze Swabs', 'Sterile Gauze', 'First Aid', 'Other', '10x10cm', NULL, NULL, '200', 'packs', '50', '800.00', NULL, 'UGX', NULL, '2029-12-31', 'Shelf E1', '0', 'Sterile swabs for wound cleaning and dressing', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('14', 'GLU001', 'Glucose Powder', 'Dextrose', 'Vitamins', 'Powder', '500g', NULL, NULL, '10', 'packs', '3', '5000.00', NULL, 'UGX', NULL, '2028-06-30', 'Cabinet C1', '0', 'Mix 2 tablespoons in water for energy', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('15', 'ALC001', 'Alcohol Swabs', 'Isopropyl Alcohol', 'First Aid', 'Solution', '70%', NULL, NULL, '300', 'swabs', '50', '100.00', NULL, 'UGX', NULL, '2028-12-31', 'Shelf E1', '0', 'Use for cleaning skin before injections', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('16', 'CLO001', 'Chloroquine', 'Chloroquine Phosphate', 'Antimalarial', 'Tablet', '250mg', NULL, NULL, '50', 'tablets', '15', '300.00', NULL, 'UGX', NULL, '2027-05-31', 'Cabinet B2', '1', 'As prescribed for malaria treatment', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('17', 'MEF001', 'Mefenamic Acid', 'Mefenamic Acid', 'Painkiller', 'Capsule', '500mg', NULL, NULL, '80', 'capsules', '20', '200.00', NULL, 'UGX', NULL, '2027-07-31', 'Cabinet A1', '0', '1 capsule 3 times daily for pain and inflammation', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('18', 'METR001', 'Metronidazole', 'Metronidazole', 'Antibiotic', 'Tablet', '400mg', NULL, NULL, '100', 'tablets', '20', '150.00', NULL, 'UGX', NULL, '2027-09-30', 'Cabinet B1', '1', '1 tablet 3 times daily for 5-7 days', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('19', 'DIC001', 'Diclofenac Gel', 'Diclofenac Diethylamine', 'Anti-inflammatory', 'Cream', '1%', NULL, NULL, '15', 'tubes', '5', '7000.00', NULL, 'UGX', NULL, '2027-10-31', 'Cabinet D1', '0', 'Apply to affected area 3-4 times daily', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('20', 'CET001', 'Cetirizine', 'Cetirizine Hydrochloride', 'Allergy', 'Tablet', '10mg', NULL, NULL, '100', 'tablets', '20', '100.00', NULL, 'UGX', NULL, '2027-12-31', 'Cabinet A2', '0', '1 tablet daily for allergy symptoms', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('21', 'ASP001', 'Aspirin', 'Acetylsalicylic Acid', 'Painkiller', 'Tablet', '300mg', NULL, NULL, '100', 'tablets', '25', '50.00', NULL, 'UGX', NULL, '2027-06-30', 'Cabinet A1', '0', '1-2 tablets every 4-6 hours for pain/fever', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('22', 'ZIN001', 'Zinc Tablets', 'Zinc Sulfate', 'Vitamins', 'Tablet', '20mg', NULL, NULL, '150', 'tablets', '30', '100.00', NULL, 'UGX', NULL, '2028-09-30', 'Cabinet C1', '0', '1 tablet daily for immune support and wound healing', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('23', 'CLOT001', 'Clotrimazole Cream', 'Clotrimazole', 'Antifungal', 'Cream', '1%', NULL, NULL, '15', 'tubes', '5', '4000.00', NULL, 'UGX', NULL, '2027-08-31', 'Cabinet D1', '0', 'Apply to affected area twice daily for 2 weeks', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('24', 'EYE001', 'Eye Drops', 'Chloramphenicol', 'Other', 'Drops', '0.5%', NULL, NULL, '20', 'bottles', '5', '5000.00', NULL, 'UGX', NULL, '2027-04-30', 'Cabinet A3', '1', '1-2 drops in affected eye every 2-4 hours', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('25', 'BET001', 'Betadine Solution', 'Povidone-Iodine', 'First Aid', 'Solution', '10%', NULL, NULL, '10', 'bottles', '3', '8000.00', NULL, 'UGX', NULL, '2028-03-31', 'Shelf E1', '0', 'Apply to wounds for disinfection', NULL, 'In Stock', NULL, NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44');

-- --------------------------------------------------------

--
-- Table structure for table `medicine_stock_transactions`
--

DROP TABLE IF EXISTS `medicine_stock_transactions`;
CREATE TABLE `medicine_stock_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_number` (`transaction_number`),
  KEY `medicine_id` (`medicine_id`),
  KEY `transaction_type` (`transaction_type`),
  KEY `transaction_date` (`transaction_date`),
  KEY `student_id` (`student_id`),
  KEY `mst_medicine_date` (`medicine_id`,`transaction_date`),
  CONSTRAINT `medicine_stock_transactions_ibfk_1` FOREIGN KEY (`medicine_id`) REFERENCES `medicine_stock` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `national_exam_results`
--

DROP TABLE IF EXISTS `national_exam_results`;
CREATE TABLE `national_exam_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `exam_type` varchar(100) DEFAULT NULL,
  `exam_year` varchar(20) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `grade` varchar(10) DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `national_exam_number` varchar(100) DEFAULT NULL,
  `certificate_number` varchar(100) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ner_student` (`student_id`),
  KEY `idx_ner_exam` (`exam_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `news_subscribers`
--

DROP TABLE IF EXISTS `news_subscribers`;
CREATE TABLE `news_subscribers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `user_type` enum('staff','student') NOT NULL,
  `subscribed` tinyint(1) DEFAULT 1,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_subscriber` (`user_id`,`user_type`),
  KEY `idx_ns_subscribed` (`subscribed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `news_views`
--

DROP TABLE IF EXISTS `news_views`;
CREATE TABLE `news_views` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `news_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_type` enum('staff','student','public') DEFAULT 'public',
  `ip_address` varchar(45) DEFAULT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_nv_news` (`news_id`),
  KEY `idx_nv_user` (`user_id`,`user_type`),
  KEY `idx_nv_date` (`viewed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notification_reads`
--

DROP TABLE IF EXISTS `notification_reads`;
CREATE TABLE `notification_reads` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `notification_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `notif_user` (`notification_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `type` varchar(50) DEFAULT 'info',
  `priority` varchar(20) DEFAULT 'normal',
  `audience` varchar(50) DEFAULT 'all',
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `onboarding_checklist`
--

DROP TABLE IF EXISTS `onboarding_checklist`;
CREATE TABLE `onboarding_checklist` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(10) unsigned DEFAULT NULL,
  `task_name` varchar(200) DEFAULT NULL,
  `assigned_to` int(10) unsigned DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `partner_schools`
--

DROP TABLE IF EXISTS `partner_schools`;
CREATE TABLE `partner_schools` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `contact_person` varchar(200) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `partnership_type` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(30) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `partnerships`
--

DROP TABLE IF EXISTS `partnerships`;
CREATE TABLE `partnerships` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `partner_name` varchar(200) DEFAULT NULL,
  `partner_type` varchar(100) DEFAULT NULL,
  `contact_person` varchar(200) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `mou_file` varchar(500) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'active',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
CREATE TABLE `password_resets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_approvals`
--

DROP TABLE IF EXISTS `payment_approvals`;
CREATE TABLE `payment_approvals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` int(11) NOT NULL,
  `payment_type` varchar(50) DEFAULT 'fee_payment',
  `requested_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approval_status` varchar(20) DEFAULT NULL,
  `approval_remarks` text DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_subscriptions`
--

DROP TABLE IF EXISTS `payment_subscriptions`;
CREATE TABLE `payment_subscriptions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `subscription_type` varchar(100) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `frequency` varchar(30) DEFAULT 'monthly',
  `next_due_date` date DEFAULT NULL,
  `status` varchar(30) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
CREATE TABLE `payments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned DEFAULT NULL,
  `amount_received` decimal(12,2) NOT NULL DEFAULT 0.00,
  `amount_paid` decimal(12,2) DEFAULT 0.00,
  `payment_method` varchar(60) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `reference` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_payments_date` (`payment_date`),
  KEY `idx_payments_status` (`status`),
  KEY `idx_payments_student` (`student_id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `student_id`, `amount_received`, `amount_paid`, `payment_method`, `payment_date`, `status`, `reference`, `created_at`) VALUES
('1', '1', '4303623.00', '0.00', 'Cheque', '2026-04-01', 'verified', NULL, '2026-06-19 23:58:56'),
('2', '1', '1154598.00', '0.00', 'Mobile Money', '2026-01-13', 'verified', NULL, '2026-06-19 23:58:56'),
('3', '1', '2373654.00', '0.00', 'POS', '2026-02-04', 'pending', NULL, '2026-06-19 23:58:56'),
('4', '1', '903361.00', '0.00', 'Bank Transfer', '2026-02-03', 'pending', NULL, '2026-06-19 23:58:56'),
('5', '1', '516178.00', '0.00', 'Mobile Money', '2026-04-15', 'approved', NULL, '2026-06-19 23:58:56'),
('6', '1', '3369769.00', '0.00', 'Bank Transfer', '2026-04-06', 'approved', NULL, '2026-06-19 23:58:56'),
('7', '1', '1195561.00', '0.00', 'Bank Transfer', '2026-02-28', 'verified', NULL, '2026-06-19 23:58:56'),
('8', '1', '2818435.00', '0.00', 'Bank Transfer', '2026-04-03', 'approved', NULL, '2026-06-19 23:58:56'),
('9', '1', '1694306.00', '0.00', 'POS', '2026-05-28', 'verified', NULL, '2026-06-19 23:58:56'),
('10', '1', '1310012.00', '0.00', 'Bank Transfer', '2026-05-23', 'pending', NULL, '2026-06-19 23:58:56'),
('11', '2', '4079351.00', '0.00', 'Cheque', '2026-01-18', 'approved', NULL, '2026-06-19 23:58:56'),
('12', '2', '3786321.00', '0.00', 'Mobile Money', '2026-05-14', 'approved', NULL, '2026-06-19 23:58:56'),
('13', '2', '4845372.00', '0.00', 'Cheque', '2026-06-12', 'verified', NULL, '2026-06-19 23:58:56'),
('14', '2', '2205793.00', '0.00', 'Cheque', '2026-02-07', 'verified', NULL, '2026-06-19 23:58:56'),
('15', '2', '3532582.00', '0.00', 'Cheque', '2026-02-11', 'pending', NULL, '2026-06-19 23:58:56'),
('16', '2', '4559246.00', '0.00', 'POS', '2026-01-07', 'pending', NULL, '2026-06-19 23:58:56'),
('17', '2', '1664302.00', '0.00', 'Bank Transfer', '2026-02-24', 'pending', NULL, '2026-06-19 23:58:56'),
('18', '2', '231198.00', '0.00', 'Cash', '2025-12-28', 'approved', NULL, '2026-06-19 23:58:56'),
('19', '2', '371793.00', '0.00', 'Mobile Money', '2025-12-30', 'pending', NULL, '2026-06-19 23:58:56'),
('20', '2', '4921083.00', '0.00', 'Bank Transfer', '2026-03-18', 'pending', NULL, '2026-06-19 23:58:56'),
('21', '3', '1347820.00', '0.00', 'Cheque', '2026-06-13', 'pending', NULL, '2026-06-19 23:58:56'),
('22', '3', '679021.00', '0.00', 'Mobile Money', '2026-03-04', 'approved', NULL, '2026-06-19 23:58:56'),
('23', '3', '841699.00', '0.00', 'Cash', '2025-12-25', 'pending', NULL, '2026-06-19 23:58:56'),
('24', '3', '2118353.00', '0.00', 'Cash', '2026-05-22', 'verified', NULL, '2026-06-19 23:58:56'),
('25', '3', '1529731.00', '0.00', 'Bank Transfer', '2026-01-03', 'verified', NULL, '2026-06-19 23:58:56'),
('26', '3', '150061.00', '0.00', 'Cash', '2026-05-06', 'approved', NULL, '2026-06-19 23:58:56'),
('27', '3', '2099931.00', '0.00', 'Mobile Money', '2026-01-17', 'approved', NULL, '2026-06-19 23:58:56'),
('28', '3', '3984452.00', '0.00', 'Mobile Money', '2026-04-29', 'verified', NULL, '2026-06-19 23:58:56'),
('29', '3', '1757402.00', '0.00', 'Bank Transfer', '2026-01-08', 'pending', NULL, '2026-06-19 23:58:56'),
('30', '3', '2363593.00', '0.00', 'Cash', '2026-04-15', 'pending', NULL, '2026-06-19 23:58:56'),
('31', '4', '4897316.00', '0.00', 'Cash', '2026-06-06', 'approved', NULL, '2026-06-19 23:58:56'),
('32', '4', '4530396.00', '0.00', 'POS', '2026-03-04', 'approved', NULL, '2026-06-19 23:58:56'),
('33', '4', '2981352.00', '0.00', 'Bank Transfer', '2026-01-17', 'pending', NULL, '2026-06-19 23:58:56'),
('34', '4', '1748722.00', '0.00', 'Bank Transfer', '2026-06-14', 'pending', NULL, '2026-06-19 23:58:56'),
('35', '4', '231509.00', '0.00', 'Cheque', '2026-01-22', 'pending', NULL, '2026-06-19 23:58:56'),
('36', '4', '306115.00', '0.00', 'Cash', '2026-01-13', 'approved', NULL, '2026-06-19 23:58:56'),
('37', '4', '4653839.00', '0.00', 'Cheque', '2026-04-17', 'pending', NULL, '2026-06-19 23:58:56'),
('38', '4', '3217739.00', '0.00', 'Mobile Money', '2026-04-10', 'approved', NULL, '2026-06-19 23:58:56'),
('39', '4', '1228940.00', '0.00', 'Mobile Money', '2026-05-09', 'pending', NULL, '2026-06-19 23:58:56'),
('40', '4', '1651005.00', '0.00', 'Cheque', '2026-01-06', 'approved', NULL, '2026-06-19 23:58:56'),
('41', '5', '4721389.00', '0.00', 'POS', '2026-02-09', 'approved', NULL, '2026-06-19 23:58:56'),
('42', '5', '149174.00', '0.00', 'POS', '2026-03-09', 'approved', NULL, '2026-06-19 23:58:56'),
('43', '5', '617859.00', '0.00', 'Mobile Money', '2025-12-25', 'approved', NULL, '2026-06-19 23:58:56'),
('44', '5', '3024579.00', '0.00', 'POS', '2025-12-30', 'approved', NULL, '2026-06-19 23:58:56'),
('45', '5', '4439374.00', '0.00', 'Cheque', '2026-05-05', 'verified', NULL, '2026-06-19 23:58:56'),
('46', '5', '333072.00', '0.00', 'Mobile Money', '2026-05-04', 'pending', NULL, '2026-06-19 23:58:56'),
('47', '5', '3767992.00', '0.00', 'Cash', '2026-06-20', 'pending', NULL, '2026-06-19 23:58:56'),
('48', '5', '189456.00', '0.00', 'Cheque', '2026-06-15', 'verified', NULL, '2026-06-19 23:58:56'),
('49', '5', '3666993.00', '0.00', 'Cash', '2026-04-25', 'approved', NULL, '2026-06-19 23:58:56'),
('50', '5', '4837535.00', '0.00', 'POS', '2026-03-31', 'approved', NULL, '2026-06-19 23:58:56');

-- --------------------------------------------------------

--
-- Table structure for table `payroll_allowances`
--

DROP TABLE IF EXISTS `payroll_allowances`;
CREATE TABLE `payroll_allowances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `allowance_type` varchar(100) NOT NULL,
  `amount` decimal(12,2) DEFAULT 0.00,
  `month` varchar(20) NOT NULL,
  `is_recurring` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_approvals`
--

DROP TABLE IF EXISTS `payroll_approvals`;
CREATE TABLE `payroll_approvals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payroll_run_id` int(11) NOT NULL,
  `level` enum('HR','PayrollOfficer','Bursar','DirectorFinance') NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_run_level` (`payroll_run_id`,`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_bonuses`
--

DROP TABLE IF EXISTS `payroll_bonuses`;
CREATE TABLE `payroll_bonuses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `bonus_type` varchar(100) NOT NULL,
  `amount` decimal(12,2) DEFAULT 0.00,
  `month` varchar(20) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_deductions`
--

DROP TABLE IF EXISTS `payroll_deductions`;
CREATE TABLE `payroll_deductions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `deduction_type` varchar(100) NOT NULL,
  `amount` decimal(12,2) DEFAULT 0.00,
  `month` varchar(20) NOT NULL,
  `is_recurring` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_details`
--

DROP TABLE IF EXISTS `payroll_details`;
CREATE TABLE `payroll_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payroll_run_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `basic_salary` decimal(12,2) DEFAULT 0.00,
  `total_allowances` decimal(12,2) DEFAULT 0.00,
  `overtime_pay` decimal(12,2) DEFAULT 0.00,
  `bonuses` decimal(12,2) DEFAULT 0.00,
  `gross_pay` decimal(12,2) DEFAULT 0.00,
  `paye_tax` decimal(12,2) DEFAULT 0.00,
  `nssf_employee` decimal(12,2) DEFAULT 0.00,
  `nssf_employer` decimal(12,2) DEFAULT 0.00,
  `other_deductions` decimal(12,2) DEFAULT 0.00,
  `leave_deductions` decimal(12,2) DEFAULT 0.00,
  `net_pay` decimal(12,2) DEFAULT 0.00,
  `paid_leave_days` decimal(5,1) DEFAULT 0.0,
  `unpaid_leave_days` decimal(5,1) DEFAULT 0.0,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_employees`
--

DROP TABLE IF EXISTS `payroll_employees`;
CREATE TABLE `payroll_employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account` varchar(50) DEFAULT NULL,
  `bank_code` varchar(20) DEFAULT NULL,
  `tax_identification` varchar(50) DEFAULT NULL,
  `nssf_number` varchar(50) DEFAULT NULL,
  `salary_type` enum('monthly','annual') DEFAULT 'monthly',
  `salary_grade` varchar(50) DEFAULT NULL,
  `basic_salary` decimal(12,2) DEFAULT 0.00,
  `hire_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_overtime`
--

DROP TABLE IF EXISTS `payroll_overtime`;
CREATE TABLE `payroll_overtime` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `hours` decimal(8,2) DEFAULT 0.00,
  `rate` decimal(10,2) DEFAULT 0.00,
  `total_pay` decimal(12,2) DEFAULT 0.00,
  `month` varchar(20) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payroll_records`
--

DROP TABLE IF EXISTS `payroll_records`;
CREATE TABLE `payroll_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `gross_salary` decimal(12,2) DEFAULT 0.00,
  `total_allowances` decimal(12,2) DEFAULT 0.00,
  `total_deductions` decimal(12,2) DEFAULT 0.00,
  `nssf_tax` decimal(12,2) DEFAULT 0.00,
  `paye_tax` decimal(12,2) DEFAULT 0.00,
  `net_salary` decimal(12,2) DEFAULT 0.00,
  `total_fees_collected` decimal(12,2) DEFAULT 0.00,
  `net_payment` decimal(12,2) DEFAULT 0.00,
  `processed_by` int(11) DEFAULT 0,
  `processing_date` datetime DEFAULT current_timestamp(),
  `status` enum('Draft','Processed','Approved','Paid') DEFAULT 'Draft',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_staff_period` (`staff_id`,`month`,`year`),
  KEY `idx_period` (`month`,`year`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `payroll_records`
--

INSERT INTO `payroll_records` (`id`, `staff_id`, `month`, `year`, `gross_salary`, `total_allowances`, `total_deductions`, `nssf_tax`, `paye_tax`, `net_salary`, `total_fees_collected`, `net_payment`, `processed_by`, `processing_date`, `status`, `approved_by`, `approved_at`) VALUES
('1', '1', '6', '2026', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '25', '2026-06-25 00:34:35', 'Processed', NULL, NULL),
('2', '2', '6', '2026', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '25', '2026-06-25 00:34:35', 'Processed', NULL, NULL),
('3', '3', '6', '2026', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '25', '2026-06-25 00:34:35', 'Processed', NULL, NULL),
('4', '4', '6', '2026', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '25', '2026-06-25 00:34:35', 'Processed', NULL, NULL),
('5', '5', '6', '2026', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '25', '2026-06-25 00:34:35', 'Processed', NULL, NULL),
('6', '6', '6', '2026', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '25', '2026-06-25 00:34:35', 'Processed', NULL, NULL),
('7', '7', '6', '2026', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '25', '2026-06-25 00:34:35', 'Processed', NULL, NULL),
('8', '8', '6', '2026', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '25', '2026-06-25 00:34:35', 'Processed', NULL, NULL),
('9', '9', '6', '2026', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '25', '2026-06-25 00:34:35', 'Processed', NULL, NULL),
('10', '10', '6', '2026', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '25', '2026-06-25 00:34:35', 'Processed', NULL, NULL),
('11', '11', '6', '2026', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '25', '2026-06-25 00:34:35', 'Processed', NULL, NULL),
('12', '12', '6', '2026', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '25', '2026-06-25 00:34:35', 'Processed', NULL, NULL),
('13', '13', '6', '2026', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '25', '2026-06-25 00:34:35', 'Processed', NULL, NULL),
('14', '14', '6', '2026', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '25', '2026-06-25 00:34:35', 'Processed', NULL, NULL),
('15', '15', '6', '2026', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '25', '2026-06-25 00:34:35', 'Processed', NULL, NULL),
('16', '16', '6', '2026', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '25', '2026-06-25 00:34:35', 'Processed', NULL, NULL),
('17', '17', '6', '2026', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '25', '2026-06-25 00:34:35', 'Processed', NULL, NULL),
('18', '18', '6', '2026', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '25', '2026-06-25 00:34:35', 'Processed', NULL, NULL),
('19', '19', '6', '2026', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '25', '2026-06-25 00:34:35', 'Processed', NULL, NULL),
('20', '20', '6', '2026', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '25', '2026-06-25 00:34:35', 'Processed', NULL, NULL),
('21', '21', '6', '2026', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '25', '2026-06-25 00:34:35', 'Processed', NULL, NULL),
('22', '22', '6', '2026', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '25', '2026-06-25 00:34:35', 'Processed', NULL, NULL),
('23', '23', '6', '2026', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '25', '2026-06-25 00:34:35', 'Processed', NULL, NULL),
('24', '24', '6', '2026', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '25', '2026-06-25 00:34:35', 'Processed', NULL, NULL),
('25', '25', '6', '2026', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '25', '2026-06-25 00:34:35', 'Processed', NULL, NULL),
('26', '51', '6', '2026', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '0.00', '25', '2026-06-25 00:34:35', 'Processed', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `payroll_runs`
--

DROP TABLE IF EXISTS `payroll_runs`;
CREATE TABLE `payroll_runs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `period` varchar(20) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `total_gross` decimal(15,2) DEFAULT 0.00,
  `total_deductions` decimal(15,2) DEFAULT 0.00,
  `total_net` decimal(15,2) DEFAULT 0.00,
  `status` enum('draft','approved','processed','paid') DEFAULT 'draft',
  `created_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payslips`
--

DROP TABLE IF EXISTS `payslips`;
CREATE TABLE `payslips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payslip_number` varchar(50) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `payroll_run_id` int(11) DEFAULT NULL,
  `payroll_detail_id` int(11) DEFAULT NULL,
  `salary_month` varchar(20) NOT NULL,
  `basic_salary` decimal(15,2) DEFAULT NULL,
  `allowances` decimal(15,2) DEFAULT NULL,
  `gross_salary` decimal(15,2) DEFAULT NULL,
  `deductions` decimal(15,2) DEFAULT NULL,
  `payment_ref` varchar(100) DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `net_salary` decimal(15,2) DEFAULT NULL,
  `payment_method` enum('bank_transfer','cash','cheque') DEFAULT 'bank_transfer',
  `payment_date` date DEFAULT NULL,
  `generated_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `viewed_by_employee` tinyint(1) DEFAULT 0,
  `viewed_date` timestamp NULL DEFAULT NULL,
  `status` enum('generated','approved','paid') DEFAULT 'generated',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `payslip_number` (`payslip_number`),
  KEY `idx_payslip_run` (`payroll_run_id`),
  KEY `idx_payslip_detail` (`payroll_detail_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pending_students`
--

DROP TABLE IF EXISTS `pending_students`;
CREATE TABLE `pending_students` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `student_number` varchar(60) NOT NULL,
  `program` varchar(120) DEFAULT NULL,
  `level` varchar(20) DEFAULT '1',
  `intake_year` varchar(4) DEFAULT NULL,
  `intake_period` varchar(20) DEFAULT 'January',
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `submitted_by` int(10) unsigned DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending_approval',
  `approval_request_id` int(10) unsigned DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_approval` (`approval_request_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pending_students`
--

INSERT INTO `pending_students` (`id`, `first_name`, `middle_name`, `last_name`, `student_number`, `program`, `level`, `intake_year`, `intake_period`, `phone`, `email`, `date_of_birth`, `submitted_by`, `status`, `approval_request_id`, `rejection_reason`, `created_at`) VALUES
('1', 'Akello', NULL, 'Grace', 'ISNM-2026-006', 'Diploma Nursing', '1', '2026', 'January', NULL, NULL, NULL, '5', 'pending_approval', '4', NULL, '2026-06-19 21:47:50'),
('2', 'Bwire', NULL, 'John', 'ISNM-2026-007', 'Certificate Midwifery', '1', '2026', 'January', NULL, NULL, NULL, '5', 'pending_approval', '5', NULL, '2026-06-19 00:47:50');

-- --------------------------------------------------------

--
-- Table structure for table `performance_indicators`
--

DROP TABLE IF EXISTS `performance_indicators`;
CREATE TABLE `performance_indicators` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `indicator_name` varchar(200) DEFAULT NULL,
  `target_value` decimal(12,2) DEFAULT NULL,
  `actual_value` decimal(12,2) DEFAULT NULL,
  `period` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `status` varchar(30) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `portal_messages`
--

DROP TABLE IF EXISTS `portal_messages`;
CREATE TABLE `portal_messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) DEFAULT NULL,
  `recipient_type` varchar(30) DEFAULT 'individual',
  `subject` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `professional_licenses`
--

DROP TABLE IF EXISTS `professional_licenses`;
CREATE TABLE `professional_licenses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(10) unsigned NOT NULL,
  `license_number` varchar(100) DEFAULT NULL,
  `license_type` varchar(100) DEFAULT NULL,
  `issuing_body` varchar(200) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` varchar(30) DEFAULT 'active',
  `document_path` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quality_assurance`
--

DROP TABLE IF EXISTS `quality_assurance`;
CREATE TABLE `quality_assurance` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `review_type` varchar(100) DEFAULT NULL,
  `reviewer_id` int(10) unsigned DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `findings` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `status` varchar(30) DEFAULT 'in_progress',
  `review_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recruitment`
--

DROP TABLE IF EXISTS `recruitment`;
CREATE TABLE `recruitment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `position_title` varchar(200) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `vacancies` int(11) DEFAULT 1,
  `requirements` text DEFAULT NULL,
  `status` varchar(30) DEFAULT 'open',
  `posted_date` date DEFAULT NULL,
  `closing_date` date DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recycle_bin`
--

DROP TABLE IF EXISTS `recycle_bin`;
CREATE TABLE `recycle_bin` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `original_table` varchar(100) DEFAULT NULL,
  `original_id` int(10) unsigned DEFAULT NULL,
  `data` longtext DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `restored_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrar_student_registration`
--

DROP TABLE IF EXISTS `registrar_student_registration`;
CREATE TABLE `registrar_student_registration` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` varchar(100) NOT NULL,
  `registration_date` date DEFAULT NULL,
  `registration_status` varchar(50) DEFAULT 'Registered',
  `registered_by` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_student` (`student_id`),
  KEY `idx_year` (`academic_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrar_transcript_requests`
--

DROP TABLE IF EXISTS `registrar_transcript_requests`;
CREATE TABLE `registrar_transcript_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `request_type` varchar(50) DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `requested_by` int(10) unsigned DEFAULT NULL,
  `processed_by` int(10) unsigned DEFAULT NULL,
  `fee_paid` decimal(10,2) DEFAULT 0.00,
  `generated_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `requirement_history`
--

DROP TABLE IF EXISTS `requirement_history`;
CREATE TABLE `requirement_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `applicant_id` int(11) NOT NULL,
  `requirement_id` int(11) DEFAULT NULL,
  `action` enum('Submitted','Verified','Rejected','Updated','Reset') NOT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rh_applicant` (`applicant_id`),
  KEY `idx_rh_requirement` (`requirement_id`),
  KEY `idx_rh_action` (`action`),
  KEY `idx_rh_created` (`created_at`),
  CONSTRAINT `requirement_history_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `requirement_history_ibfk_2` FOREIGN KEY (`requirement_id`) REFERENCES `admission_requirements` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `research_projects`
--

DROP TABLE IF EXISTS `research_projects`;
CREATE TABLE `research_projects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `project_title` varchar(300) DEFAULT NULL,
  `principal_investigator` varchar(200) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `funding_source` varchar(200) DEFAULT NULL,
  `budget` decimal(12,2) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(30) DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `result_publications`
--

DROP TABLE IF EXISTS `result_publications`;
CREATE TABLE `result_publications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publication_number` varchar(50) NOT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `status` enum('draft','scheduled','published','withdrawn') DEFAULT 'draft',
  `published_by` int(11) DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `scheduled_date` datetime DEFAULT NULL,
  `notification_sent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rp_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `name`, `description`, `created_at`) VALUES
('1', 'Director General', NULL, '2026-06-09 22:56:09'),
('2', 'CEO', NULL, '2026-06-09 22:56:09'),
('3', 'Director Academics', NULL, '2026-06-09 22:56:09'),
('4', 'Director Finance', NULL, '2026-06-09 22:56:09'),
('5', 'Director ICT', NULL, '2026-06-09 22:56:09'),
('6', 'School Principal', NULL, '2026-06-09 22:56:09'),
('7', 'Deputy Principal', NULL, '2026-06-09 22:56:09'),
('8', 'Academic Registrar', NULL, '2026-06-09 22:56:09'),
('9', 'HR Manager', NULL, '2026-06-09 22:56:09'),
('10', 'School Secretary', NULL, '2026-06-09 22:56:09'),
('11', 'School Librarian', NULL, '2026-06-09 22:56:09'),
('12', 'Head Nursing', NULL, '2026-06-09 22:56:09'),
('13', 'Head Midwifery', NULL, '2026-06-09 22:56:09'),
('14', 'Senior Lecturers', NULL, '2026-06-09 22:56:09'),
('15', 'Lecturers', NULL, '2026-06-09 22:56:09'),
('16', 'Matrons', NULL, '2026-06-09 22:56:09'),
('17', 'Wardens', NULL, '2026-06-09 22:56:09'),
('18', 'Sickbay', NULL, '2026-06-09 22:56:09'),
('19', 'Drivers', NULL, '2026-06-09 22:56:09'),
('20', 'Security', NULL, '2026-06-09 22:56:09'),
('21', 'Storekeeper', NULL, '2026-06-09 22:56:09'),
('22', 'Guild President', NULL, '2026-06-09 22:56:09'),
('23', 'Computer Lab Manager', NULL, '2026-06-09 22:56:09'),
('24', 'School Bursar', NULL, '2026-06-09 22:56:09'),
('25', 'Store Keeper', 'Store inventory', '2026-06-13 02:38:49'),
('26', 'Director Admissions & Requirements', 'Admissions management', '2026-06-13 02:38:49'),
('27', 'Bursar', 'Bursar assistant', '2026-06-13 02:38:49');

-- --------------------------------------------------------

--
-- Table structure for table `room_inspections`
--

DROP TABLE IF EXISTS `room_inspections`;
CREATE TABLE `room_inspections` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `room_id` int(10) unsigned DEFAULT NULL,
  `inspector_id` int(10) unsigned DEFAULT NULL,
  `inspection_date` date DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `findings` text DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `scholarships`
--

DROP TABLE IF EXISTS `scholarships`;
CREATE TABLE `scholarships` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `scholarship_name` varchar(200) DEFAULT NULL,
  `provider` varchar(200) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_access_logs`
--

DROP TABLE IF EXISTS `security_access_logs`;
CREATE TABLE `security_access_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `access_type` varchar(50) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `status` varchar(30) DEFAULT NULL,
  `accessed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `security_incidents`
--

DROP TABLE IF EXISTS `security_incidents`;
CREATE TABLE `security_incidents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `incident_type` varchar(100) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `reported_by` int(10) unsigned DEFAULT NULL,
  `status` varchar(30) DEFAULT 'reported',
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `semesters`
--

DROP TABLE IF EXISTS `semesters`;
CREATE TABLE `semesters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `academic_year` varchar(20) NOT NULL,
  `semester_name` varchar(100) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT 0,
  `status` varchar(50) DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sickbay_settings`
--

DROP TABLE IF EXISTS `sickbay_settings`;
CREATE TABLE `sickbay_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sickbay_settings`
--

INSERT INTO `sickbay_settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
('1', 'reorder_level', '10', '2026-06-19 22:59:38'),
('2', 'low_stock_threshold', '10', '2026-06-19 22:59:38'),
('3', 'auto_status', '1', '2026-06-19 22:59:38'),
('4', 'notify_low_stock', '1', '2026-06-19 22:59:38'),
('5', 'default_theme', 'default-blue', '2026-06-19 22:59:38');

-- --------------------------------------------------------

--
-- Table structure for table `sickness_directory`
--

DROP TABLE IF EXISTS `sickness_directory`;
CREATE TABLE `sickness_directory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `sickness_code` (`sickness_code`),
  KEY `sickness_name` (`sickness_name`),
  KEY `category` (`category`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sickness_directory`
--

INSERT INTO `sickness_directory` (`id`, `sickness_code`, `sickness_name`, `category`, `common_symptoms`, `description`, `is_contagious`, `typical_treatment`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
('1', 'MLR', 'Malaria', 'Infectious', 'Fever, chills, headache, sweating, fatigue', 'Mosquito-borne parasitic infection common in tropical regions', '0', 'Artemisinin-based combination therapy, antimalarials', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('2', 'TYP', 'Typhoid', 'Infectious', 'Prolonged fever, abdominal pain, headache, constipation or diarrhea', 'Bacterial infection spread through contaminated food/water', '1', 'Antibiotics (ciprofloxacin, azithromycin), hydration', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('3', 'FLU', 'Influenza', 'Infectious', 'Fever, cough, sore throat, body aches, fatigue', 'Viral respiratory infection spread through droplets', '1', 'Rest, fluids, antipyretics, antivirals if severe', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('4', 'COLD', 'Common Cold', 'Infectious', 'Runny nose, sneezing, sore throat, cough, mild fever', 'Viral upper respiratory tract infection', '1', 'Rest, antihistamines, decongestants, vitamin C', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('5', 'URTI', 'Upper Respiratory Tract Infection', 'Infectious', 'Cough, sore throat, nasal congestion, fever', 'Bacterial or viral infection of upper airways', '1', 'Antibiotics if bacterial, rest, fluids', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('6', 'HDCH', 'Headache/Tension Headache', 'Non-Infectious', 'Head pain, pressure around forehead, neck tension', 'Common tension-type headache from stress or fatigue', '0', 'Rest, analgesics (paracetamol, ibuprofen)', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('7', 'GSTR', 'Gastritis', 'Non-Infectious', 'Abdominal pain, nausea, bloating, indigestion', 'Inflammation of stomach lining from diet, stress, or infection', '0', 'Antacids, dietary changes, proton pump inhibitors', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('8', 'DIAR', 'Diarrhea', 'Infectious', 'Loose watery stools, abdominal cramps, dehydration', 'Common infection from contaminated food/water or viruses', '1', 'ORS, hydration, antidiarrheals, antibiotics if bacterial', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('9', 'ALLG', 'Allergic Reaction', 'Non-Infectious', 'Rash, itching, sneezing, watery eyes, swelling', 'Immune response to allergens (food, dust, pollen, drugs)', '0', 'Antihistamines, corticosteroids, avoid triggers', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('10', 'INJR', 'Injury/Accident', 'Injury', 'Pain, swelling, bruising, bleeding, limited mobility', 'Physical trauma from falls, sports, or accidents', '0', 'First aid, rest, ice, compression, elevation, analgesics', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('11', 'ANEM', 'Anemia', 'Nutritional', 'Fatigue, weakness, pale skin, shortness of breath, dizziness', 'Low red blood cell count from iron deficiency or other causes', '0', 'Iron supplements, dietary changes, B12 if needed', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('12', 'MALN', 'Malnutrition', 'Nutritional', 'Weight loss, fatigue, poor growth, weakened immunity', 'Inadequate nutrient intake affecting overall health', '0', 'Nutritional supplementation, diet counseling', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('13', 'CONS', 'Constipation', 'Non-Infectious', 'Infrequent bowel movements, straining, hard stools', 'Common digestive issue from diet or lifestyle factors', '0', 'Increased fiber intake, hydration, laxatives if needed', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('14', 'SORE', 'Sore Throat', 'Infectious', 'Pain or scratchiness in throat, difficulty swallowing', 'Viral or bacterial throat infection', '1', 'Warm salt water gargle, lozenges, antibiotics if strep', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('15', 'EYEI', 'Eye Infection', 'Infectious', 'Redness, itching, discharge, swollen eyelids', 'Bacterial or viral conjunctivitis', '1', 'Antibiotic or antiviral eye drops, hygiene', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('16', 'SKIN', 'Skin Infection/Rash', 'Infectious', 'Redness, itching, bumps, blisters, peeling', 'Fungal, bacterial, or viral skin infection', '1', 'Topical or oral antibiotics/antifungals, hygiene', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('17', 'FATG', 'Fatigue/General Malaise', 'Non-Infectious', 'Tiredness, low energy, reduced motivation', 'General feeling of being unwell without specific diagnosis', '0', 'Rest, nutrition, hydration, stress management', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('18', 'MSTR', 'Menstrual Cramps', 'Non-Infectious', 'Lower abdominal pain, back pain, nausea during menstruation', 'Painful menstrual periods common in young women', '0', 'Analgesics, heat therapy, rest, NSAIDs', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('19', 'ANXT', 'Anxiety/Stress', 'Mental Health', 'Worry, restlessness, rapid heartbeat, difficulty concentrating', 'Mental health condition common among students under academic pressure', '0', 'Counseling, stress management, relaxation techniques', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('20', 'BACK', 'Back Pain', 'Non-Infectious', 'Lower or upper back pain, stiffness, muscle tension', 'Musculoskeletal pain from poor posture, heavy lifting, or strain', '0', 'Rest, analgesics, physiotherapy, posture correction', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('21', 'THRP', 'Throat Infection/Pharyngitis', 'Infectious', 'Sore throat, red tonsils, swollen lymph nodes, fever', 'Inflammation of the pharynx from viral or bacterial infection', '1', 'Antibiotics if bacterial, rest, warm fluids', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('22', 'TOOT', 'Toothache', 'Non-Infectious', 'Tooth pain, sensitivity, swelling around tooth', 'Dental pain from cavities, infection, or impaction', '0', 'Analgesics, dental referral, antibiotics if infected', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('23', 'URIN', 'Urinary Tract Infection', 'Infectious', 'Painful urination, frequent urination, lower abdominal pain', 'Bacterial infection of the urinary tract', '0', 'Antibiotics, increased fluid intake, cranberry', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('24', 'ACNE', 'Acne/Skin Breakout', 'Non-Infectious', 'Pimples, blackheads, whiteheads, inflamed skin', 'Common skin condition from hormonal changes and stress', '0', 'Topical treatments, hygiene, dietary changes', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44'),
('25', 'FUNG', 'Fungal Infection', 'Infectious', 'Itching, redness, peeling skin, rash with defined edges', 'Fungal skin infection common in tropical climates', '1', 'Antifungal creams or oral medication, keep area dry', 'Active', NULL, '2026-06-19 22:53:44', '2026-06-19 22:53:44');

-- --------------------------------------------------------

--
-- Table structure for table `sports_events`
--

DROP TABLE IF EXISTS `sports_events`;
CREATE TABLE `sports_events` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `sport_type` varchar(100) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `event_date` datetime DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(30) DEFAULT 'upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sports_teams`
--

DROP TABLE IF EXISTS `sports_teams`;
CREATE TABLE `sports_teams` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `sport_type` varchar(100) DEFAULT NULL,
  `coach_id` int(10) unsigned DEFAULT NULL,
  `captain_id` int(10) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

DROP TABLE IF EXISTS `staff`;
CREATE TABLE `staff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` varchar(20) DEFAULT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `position` varchar(150) DEFAULT NULL,
  `department` varchar(150) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Active',
  `hire_date` date DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `is_first_login` tinyint(1) DEFAULT 1,
  `password_changed` tinyint(1) DEFAULT 0,
  `profile_photo` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `staff_id`, `full_name`, `email`, `phone`, `password`, `role_id`, `position`, `department`, `status`, `hire_date`, `last_login`, `login_attempts`, `locked_until`, `is_first_login`, `password_changed`, `profile_photo`, `address`, `created_at`, `updated_at`) VALUES
('1', NULL, 'Doris Joy Namugwanya', 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug', '', '$2y$10$9OkGyLqxrkWGQ380t05Kj./Gzu7DBUNM75BIileuHsw5nFDzPyksa', '1', 'Director General', 'Executive Office', 'Active', '2026-06-09', '2026-06-27 07:26:14', '0', NULL, '0', '1', NULL, NULL, '2026-06-09 22:56:10', '2026-06-27 07:26:14'),
('2', NULL, 'Doris Joy', 'ceo@igangaschoolofnursingandmidwifery.ac.ug', '', '$2y$10$xXJsVElSZzu.wTNPpSKh2e9mYwUnEz3Fh6N8LKh1qrwyaXbRDqZyC', '2', 'Chief Executive Officer', 'Executive Office', 'Active', '2026-06-09', '2026-06-25 09:22:04', '0', NULL, '0', '1', NULL, '', '2026-06-09 22:56:10', '2026-06-25 09:22:04'),
('3', NULL, 'Stephen Bywaka', 'directoracademic@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$0W2zpD9Mx9jrzFyGY0wzP.vfdAB8wu8JQU.UNPhQ73EM9ABy36r0q', '3', 'Director Academics', 'Academic Affairs', 'Active', '2026-06-09', '2026-06-25 03:11:18', '0', NULL, '0', '1', NULL, NULL, '2026-06-09 22:56:10', '2026-06-25 03:11:18'),
('4', NULL, 'Finance Director', 'finance@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$1B4WKBhbkTe8zAYkJbbEe.D9NtkuxflDZN356rGzPvD16QrWCKywu', '4', 'Director Finance', 'Finance Department', 'Active', '2026-06-09', '2026-06-25 09:22:40', '0', NULL, '0', '1', NULL, NULL, '2026-06-09 22:56:10', '2026-06-25 09:22:40'),
('5', NULL, 'School Principal', 'principal@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$4u3./3VtmlkZAT2xuF7MLudpeJ4AbZLKjxXryhjGKvaFeulUimvGW', '6', 'School Principal', 'Academic Affairs', 'Active', '2026-06-09', '2026-06-25 03:44:45', '0', NULL, '0', '1', NULL, NULL, '2026-06-09 22:56:11', '2026-06-25 03:44:45'),
('6', NULL, 'Deputy Principal', 'dep-principal@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$JszQnm6ppJ6ggmPqkZUHp.qg50dfBBcH7IHXh.2buKGazBNr3lATi', '7', 'Deputy Principal', 'Academic Affairs', 'Active', '2026-06-09', '2026-06-25 03:33:47', '0', NULL, '0', '1', NULL, NULL, '2026-06-09 22:56:11', '2026-06-25 03:33:47'),
('7', NULL, 'Academic Registrar', 'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug', '0772514889', '$2y$10$GO1MFp48tQvP0o4d4DlMZukTH6epueBuCaAu0EXKD0ZglCNFno5zi', '8', 'Academic Registrar', 'Academic Affairs', 'Active', '2026-06-09', '2026-06-26 06:36:27', '0', NULL, '0', '1', NULL, 'Lubas Road', '2026-06-09 22:56:11', '2026-06-26 06:36:27'),
('8', NULL, 'HR Manager', 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$fE/SVKQqJ4BYu2QlLdvlou5Vs1ug7OOivy8hcCdXzctlpKUZwvfP.', '9', 'HR Manager', 'Human Resources', 'Active', '2026-06-09', '2026-06-26 06:04:57', '0', NULL, '0', '1', NULL, NULL, '2026-06-09 22:56:14', '2026-06-26 06:04:57'),
('9', NULL, 'School Secretary', 'secretary@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$rV7s4oFYEGX.6STyluPxRO7AHKRJdI5fEBqg1XJDX9NKfCXCuSuea', '10', 'School Secretary', 'Administrative Office', 'Active', '2026-06-09', '2026-06-25 01:36:49', '0', NULL, '0', '1', NULL, NULL, '2026-06-09 22:56:23', '2026-06-25 01:36:49'),
('10', NULL, 'School Librarian', 'library@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$P/fxbkdmQ75Q4rv7x1HXz.34No68cJNJLHqSPki02VjdGbiKO83iS', '11', 'School Librarian', 'Library Services', 'Active', '2026-06-09', '2026-06-21 08:38:59', '0', NULL, '0', '1', NULL, NULL, '2026-06-09 22:56:29', '2026-06-21 08:38:59'),
('11', NULL, 'Head of Nursing', 'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$Iw8BStEfmuQ4THpt0djno.ZNV4KzveqG1R2yZtf2awMAz5u9EOi0a', '12', 'Head Nursing', 'Nursing Department', 'Active', '2026-06-09', '2026-06-13 03:14:38', '0', NULL, '0', '1', NULL, NULL, '2026-06-09 22:56:31', '2026-06-13 03:14:38'),
('12', NULL, 'Head of Midwifery', 'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$.sB5xOu5VTfjRndsyBY71uCRuX.Bn6mEm6bqQjb/5L3EmzCcpARCu', '13', 'Head Midwifery', 'Midwifery Department', 'Active', '2026-06-09', '2026-06-13 03:14:38', '0', NULL, '0', '1', NULL, NULL, '2026-06-09 22:56:33', '2026-06-13 03:14:38'),
('13', NULL, 'Senior Lecturers', 'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$331R3j5oa4oUjpgFDqZhTOANB4N8M41gU1CHXXIHg4LuylO6JMCwu', '14', 'Senior Lecturer', 'Academic Affairs', 'Active', '2026-06-09', '2026-06-13 03:14:38', '0', NULL, '0', '1', NULL, NULL, '2026-06-09 22:56:33', '2026-06-13 03:14:38'),
('14', NULL, 'Lecturers', 'lecturers@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$.kjo780DIjtfeTxVcarWq.mZcfcmxmCw.5c53/PaFXalTVBQMRCOG', '15', 'Lecturer', 'Academic Affairs', 'Active', '2026-06-09', '2026-06-13 03:14:38', '0', NULL, '0', '1', NULL, NULL, '2026-06-09 22:56:34', '2026-06-13 03:14:38'),
('15', NULL, 'Matron', 'matron@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$ymrXcnRhazxfrVpyNyaUk.R7naE6eUus6eFUEYdO0bw.HJmXOU7Qq', '16', 'Matrons', 'Student Affairs', 'Active', '2026-06-09', '2026-06-13 03:14:38', '0', NULL, '0', '1', NULL, NULL, '2026-06-09 22:56:34', '2026-06-13 03:14:38'),
('16', NULL, 'Warden', 'warden@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$5WAJaPKTb8xLi.SRfC6cD.UQ0JnCA5AqlRSS6aJdz9LD7C0gWtMty', '17', 'Wardens', 'Student Affairs', 'Active', '2026-06-09', '2026-06-13 03:14:38', '0', NULL, '0', '1', NULL, NULL, '2026-06-09 22:56:34', '2026-06-13 03:14:38'),
('17', NULL, 'Sickbay Officer', 'sickbay@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$xKCeFMFeDVhXZOxpEoQFPOBR8Cx60T7De1rIAnjAxaSSTmdwCN2Ym', '18', 'Sickbay', 'Support', 'Active', '2026-06-09', '2026-06-26 04:07:21', '0', NULL, '0', '1', NULL, NULL, '2026-06-09 22:56:34', '2026-06-26 04:07:21'),
('18', NULL, 'Driver', 'drivers@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$xZnL4zt/B7h0/E7SHNAhfe4MPYA4HhfioLU7qRQ0ORkv9eABxfIia', '19', 'Drivers', 'Transport', 'Active', '2026-06-09', '2026-06-13 03:14:39', '0', NULL, '0', '1', NULL, NULL, '2026-06-09 22:56:35', '2026-06-13 03:14:39'),
('19', NULL, 'Security Officer', 'security@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$H3mJR/813QrKDzaQMK/yC.HfM4mGpYwgPFmlZL3h/WyTSD4d5zsQq', '20', 'Security', 'Security Services', 'Active', '2026-06-09', '2026-06-13 03:14:39', '0', NULL, '0', '1', NULL, NULL, '2026-06-09 22:56:35', '2026-06-13 03:14:39'),
('20', NULL, 'Storekeeper', 'store@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$2BJLSl5d1x.KCCV83Unqv.LrM9MDrXGO.pm3Ly99plAGdjUJuxVhi', '21', 'Store Keeper', 'Facilities Management', 'Active', '2026-06-09', '2026-06-13 03:14:39', '0', NULL, '0', '1', NULL, NULL, '2026-06-09 22:56:36', '2026-06-13 03:14:39'),
('21', NULL, 'Guild President', 'guildpresident@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$2Acd3VjS07HN.YJHFjyzWOk9QsxmYpBY9oXDc1xwyPtKelUSpMtgi', '22', 'Guild President', 'Student Affairs', 'Active', '2026-06-09', '2026-06-13 03:14:39', '0', NULL, '0', '1', NULL, NULL, '2026-06-09 22:56:36', '2026-06-13 03:14:39'),
('22', NULL, 'Computer Lab Manager', 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$KlyNxRbEDLRbU4XO1uP6Ru9jjXAJP8owjUaneUmAAiK9s4eDUZnM2', '23', 'Director ICT', 'Information Communication Technology', 'Active', '2026-06-09', '2026-06-26 23:55:46', '0', NULL, '0', '1', NULL, NULL, '2026-06-09 22:56:36', '2026-06-26 23:55:46'),
('23', NULL, 'Danny ICT Director', 'dannybict@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$6au4jFh5fu7rXKWuAoKDauv.h9sQ6ONfUaBiGydeqh7JU2sO1BYoi', '5', 'Director ICT', 'Information Technology', 'Active', '2026-06-09', '2026-06-26 23:38:31', '0', NULL, '0', '1', NULL, NULL, '2026-06-09 22:56:36', '2026-06-26 23:38:31'),
('24', NULL, 'Admissions Officer Derrick', 'admissions@igangaschoolofnursingandmidwifery.ac.ug', '', '$2y$10$tLG3brrbgq6IfcHkV1O95ujGlp892EyxpFezOmACyrKA2f3b17NkG', '26', 'Director Admissions & Requirements', 'Admissions', 'Active', '2026-06-09', '2026-06-25 07:25:54', '0', NULL, '1', '1', NULL, NULL, '2026-06-09 22:56:37', '2026-06-25 07:25:54'),
('25', NULL, 'School Bursar', 'bursar@igangaschoolofnursingandmidwifery.ac.ug', NULL, '$2y$10$WgxHRWfiQH.Wv3UgHkiKIODKCs9wTXTkSxuEgBkQ6OyxTby/Tp.GG', '24', 'School Bursar', 'Finance Department', 'Active', '2026-06-10', '2026-06-26 06:04:57', '0', NULL, '0', '1', NULL, NULL, '2026-06-10 00:56:49', '2026-06-26 06:04:57'),
('51', 'BURS002', 'Bursar', 'bursar.assistant@isnm.ac.ug', NULL, '$2y$10$U61BKsKqMuX1LajK/sSOme3yETx/qnoNw75CxEiBr7mX8pd.922v.', '27', 'Bursar', 'Finance Department', 'Active', '2026-06-13', NULL, '0', NULL, '1', '0', NULL, NULL, '2026-06-13 02:38:49', '2026-06-13 02:38:49');

-- --------------------------------------------------------

--
-- Table structure for table `staff_activity_log`
--

DROP TABLE IF EXISTS `staff_activity_log`;
CREATE TABLE `staff_activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) DEFAULT NULL,
  `activity_type` varchar(100) DEFAULT NULL,
  `activity_description` text DEFAULT NULL,
  `module_accessed` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=229 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_activity_log`
--

INSERT INTO `staff_activity_log` (`id`, `staff_id`, `activity_type`, `activity_description`, `module_accessed`, `ip_address`, `user_agent`, `created_at`) VALUES
('1', '1', 'Login', 'User logged in successfully', 'authentication', '::1', 'curl/8.19.0', '2026-06-09 23:06:48'),
('2', '4', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-06-09 23:07:33'),
('3', '4', 'Login', 'User logged in successfully', 'authentication', '::1', 'curl/8.19.0', '2026-06-09 23:16:40'),
('4', '1', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-06-09 23:27:04'),
('5', '25', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-06-10 00:57:13'),
('6', '24', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-06-10 01:02:10'),
('7', '9', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-06-10 06:12:56'),
('8', '1', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 03:34:12'),
('9', '1', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 22:18:02'),
('10', '1', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:13:54'),
('11', '2', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:15:02'),
('12', '2', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:20:03'),
('13', '3', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:20:34'),
('14', '3', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:21:34'),
('15', '4', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:21:40'),
('16', '4', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:23:30'),
('17', '5', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:23:54'),
('18', '5', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:24:29'),
('19', '6', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:25:03'),
('20', '6', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:34:20'),
('21', '25', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:34:25'),
('22', '25', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:35:04'),
('23', '24', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:35:42'),
('24', '1', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 09:31:15'),
('25', '1', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 10:01:54'),
('26', '2', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 10:01:58'),
('27', '25', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 10:03:51'),
('28', '25', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 10:50:46'),
('29', '1', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 10:50:50'),
('30', '1', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 11:29:09'),
('31', '25', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 11:29:16'),
('32', '25', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 11:55:42'),
('33', '7', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 11:56:46'),
('34', '7', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:17:40'),
('35', '7', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:17:44'),
('36', '7', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:17:51'),
('37', '7', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:17:56'),
('38', '7', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:18:39'),
('39', '23', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:19:17'),
('40', '23', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:20:09'),
('41', '22', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:20:23'),
('42', '22', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:36:57'),
('43', '22', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:37:05'),
('44', '22', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:37:13'),
('45', '24', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:37:22'),
('46', '24', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:44:48'),
('47', '24', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:44:53'),
('48', '24', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:47:22'),
('49', '24', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:47:26'),
('50', '7', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 20:38:19'),
('51', '17', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 22:33:02'),
('52', '1', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 23:52:59'),
('53', '1', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 03:31:17'),
('54', '2', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 03:31:21'),
('55', '2', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 04:07:27'),
('56', '25', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 04:07:31'),
('57', '25', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 04:08:03'),
('58', '17', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 04:08:13'),
('59', '1', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 11:16:50'),
('60', '1', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 12:18:28'),
('61', '25', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 12:18:33'),
('62', '25', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 15:41:44'),
('63', '1', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 15:41:52'),
('64', '1', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 00:45:04'),
('65', '1', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 02:19:19'),
('66', '1', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 02:19:30'),
('67', '1', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 02:27:12'),
('68', '2', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 02:27:16'),
('69', '2', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 03:02:25'),
('70', '3', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 03:02:29'),
('71', '3', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 03:38:27'),
('72', '4', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 03:38:32'),
('73', '4', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 03:40:03'),
('74', '25', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 03:40:07'),
('75', '25', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 03:41:00'),
('76', '24', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 03:41:15'),
('77', '24', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 08:22:54'),
('78', '8', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 08:23:34'),
('79', '8', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 08:27:56'),
('80', '7', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 08:28:06'),
('81', '7', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 08:38:24'),
('82', '10', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 08:38:59'),
('83', '10', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 08:41:06'),
('84', '9', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 08:41:11'),
('85', '9', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 09:42:45'),
('86', '22', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 09:42:51'),
('87', '17', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 09:46:09'),
('88', '17', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 09:47:31'),
('89', '17', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 09:48:32'),
('90', '17', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 09:51:42'),
('91', '24', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 09:58:10'),
('92', '24', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 09:58:10'),
('93', '24', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 09:58:20'),
('94', '24', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:30:58'),
('95', '24', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:31:07'),
('96', '24', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:32:25'),
('97', '24', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:32:28'),
('98', '24', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:32:37'),
('99', '24', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:33:45'),
('100', '24', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:36:03'),
('101', '24', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:36:09'),
('102', '24', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:37:01'),
('103', '7', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:37:24'),
('104', '7', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 21:01:48'),
('105', '7', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 21:03:10'),
('106', '7', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 21:03:15'),
('107', '24', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 21:14:03'),
('108', '24', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 21:14:39'),
('109', '8', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 21:14:44'),
('110', '8', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 21:26:39'),
('111', '24', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 21:26:55'),
('112', '24', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 22:54:34'),
('113', '24', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 22:56:35'),
('114', '24', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 00:35:18'),
('115', '24', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 00:35:45'),
('116', '24', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 01:09:10'),
('117', '24', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 01:13:36'),
('118', '24', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 01:13:52'),
('119', '24', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 01:19:37'),
('120', '24', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 01:19:57'),
('121', '24', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 01:23:33'),
('122', '24', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 01:23:38'),
('123', '24', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 10:49:58'),
('124', '24', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 10:50:07'),
('125', '24', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 11:15:02'),
('126', '24', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 11:15:10'),
('127', '24', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 11:33:52'),
('128', '24', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 11:34:00'),
('129', '24', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 11:57:38'),
('130', '24', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 11:57:49'),
('131', '24', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 12:48:33'),
('132', '1', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 12:48:39'),
('133', '1', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 12:59:26'),
('134', '24', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 12:59:31'),
('135', '24', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 13:06:54'),
('136', '1', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 13:06:59'),
('137', '1', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 13:10:18'),
('138', '4', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 13:10:22'),
('139', '4', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 13:59:43'),
('140', '1', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 13:59:47'),
('141', '1', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 14:59:12'),
('142', '24', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 14:59:19'),
('143', '1', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 22:02:54'),
('144', '24', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 01:21:00'),
('145', '25', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 05:12:44'),
('146', '1', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 22:40:59'),
('147', '1', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 03:32:48'),
('148', '1', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 03:42:32'),
('149', '1', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 03:55:02'),
('150', '3', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 03:55:07'),
('151', '3', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 05:26:18'),
('152', '5', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 05:26:23'),
('153', '5', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 05:26:50'),
('154', '9', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 05:26:54'),
('155', '9', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 06:46:40'),
('156', '1', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 06:46:58'),
('157', '1', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 07:01:10'),
('158', '24', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 07:01:17'),
('159', '24', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 08:01:53'),
('160', '25', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 08:02:15'),
('161', '25', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 08:04:18'),
('162', '1', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 08:04:28'),
('163', '1', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 08:10:00'),
('164', '17', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 08:10:06'),
('165', '25', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 23:37:39'),
('166', '25', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 01:36:45'),
('167', '9', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 01:36:50'),
('168', '9', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 02:39:48'),
('169', '4', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 02:39:53'),
('170', '4', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 03:11:14'),
('171', '3', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 03:11:18'),
('172', '3', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 03:11:50'),
('173', '5', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 03:11:55'),
('174', '5', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 03:33:42'),
('175', '6', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 03:33:47'),
('176', '6', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 03:44:41'),
('177', '5', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 03:44:45'),
('178', '5', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 03:47:13'),
('179', '24', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 03:47:18'),
('180', '24', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 07:25:44'),
('181', '24', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 07:25:54'),
('182', '24', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 08:06:13'),
('183', '1', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 08:58:55'),
('184', '1', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 09:05:31'),
('185', '2', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 09:22:04'),
('186', '2', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 09:22:27'),
('187', '4', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 09:22:40'),
('188', '4', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 09:26:50'),
('189', '17', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 04:07:21'),
('190', '17', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 04:17:57'),
('191', '8', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 04:18:03'),
('192', '8', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 05:39:01'),
('193', '8', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 05:39:08'),
('194', '8', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 05:47:58'),
('195', '25', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 05:48:03'),
('196', '8', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8737', '2026-06-26 06:02:14'),
('197', '8', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8737', '2026-06-26 06:02:22'),
('198', '25', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8737', '2026-06-26 06:03:07'),
('199', '8', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8737', '2026-06-26 06:03:20'),
('200', '25', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 06:36:04'),
('201', '7', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 06:36:27'),
('202', '7', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 07:09:32'),
('203', '23', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 07:09:45'),
('204', '23', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 12:36:33'),
('205', '23', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 12:36:42'),
('206', '23', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 14:05:05'),
('207', '23', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 14:05:09'),
('208', '23', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 21:23:09'),
('209', '23', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 21:23:13'),
('210', '23', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 22:04:50'),
('211', '1', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 22:04:55'),
('212', '1', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 22:09:06'),
('213', '23', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 22:09:09'),
('214', '23', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 22:12:42'),
('215', '22', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 22:12:56'),
('216', '22', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 22:42:18'),
('217', '23', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 22:43:32'),
('218', '23', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 22:44:37'),
('219', '22', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 22:44:41'),
('220', '22', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 23:26:46'),
('221', '22', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 23:26:56'),
('222', '22', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 23:38:27'),
('223', '23', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 23:38:31'),
('224', '23', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 23:38:47'),
('225', '22', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 23:38:51'),
('226', '22', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 23:55:47'),
('227', '1', 'Login', 'User logged in successfully', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-27 07:26:14'),
('228', '1', 'Logout', 'User logged out', 'authentication', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-27 08:26:31');

-- --------------------------------------------------------

--
-- Table structure for table `staff_appraisals`
--

DROP TABLE IF EXISTS `staff_appraisals`;
CREATE TABLE `staff_appraisals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) DEFAULT NULL,
  `reviewer_id` int(11) DEFAULT NULL,
  `review_date` date DEFAULT NULL,
  `performance_score` decimal(5,2) DEFAULT NULL,
  `strengths` text DEFAULT NULL,
  `areas_improvement` text DEFAULT NULL,
  `overall_rating` varchar(50) DEFAULT NULL,
  `status` enum('draft','submitted','reviewed','completed') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_attendance`
--

DROP TABLE IF EXISTS `staff_attendance`;
CREATE TABLE `staff_attendance` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(10) unsigned NOT NULL,
  `date` date NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Present',
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `recorded_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_attendance_staff_date` (`staff_id`,`date`),
  KEY `idx_attendance_date` (`date`),
  KEY `idx_attendance_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_attendance`
--

INSERT INTO `staff_attendance` (`id`, `staff_id`, `date`, `status`, `time_in`, `time_out`, `remarks`, `recorded_by`, `created_at`) VALUES
('1', '1', '2026-06-20', 'Absent', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
('2', '2', '2026-06-20', 'On Leave', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
('3', '3', '2026-06-20', 'Present', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
('4', '4', '2026-06-20', 'On Leave', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
('5', '23', '2026-06-20', 'Present', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
('6', '5', '2026-06-20', 'Present', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
('7', '6', '2026-06-20', 'Late', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
('8', '7', '2026-06-20', 'Absent', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
('9', '24', '2026-06-20', 'Present', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
('10', '8', '2026-06-20', 'Present', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
('11', '9', '2026-06-20', 'On Leave', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
('12', '10', '2026-06-20', 'Present', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
('13', '11', '2026-06-20', 'Present', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
('14', '12', '2026-06-20', 'Present', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
('15', '13', '2026-06-20', 'Late', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
('16', '14', '2026-06-20', 'On Leave', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
('17', '15', '2026-06-20', 'Present', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
('18', '16', '2026-06-20', 'Present', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
('19', '17', '2026-06-20', 'Present', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56'),
('20', '18', '2026-06-20', 'Late', NULL, NULL, NULL, NULL, '2026-06-19 23:58:56');

-- --------------------------------------------------------

--
-- Table structure for table `staff_communications`
--

DROP TABLE IF EXISTS `staff_communications`;
CREATE TABLE `staff_communications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `sender_email` varchar(255) NOT NULL,
  `sender_name` varchar(255) NOT NULL,
  `recipient_type` enum('department','all_staff') NOT NULL DEFAULT 'department',
  `recipient_id` varchar(50) DEFAULT NULL,
  `recipient_name` varchar(255) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message_body` text NOT NULL,
  `priority` enum('Low','Normal','High','Urgent') NOT NULL DEFAULT 'Normal',
  `email_status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sender_id` (`sender_id`),
  KEY `idx_recipient_type` (`recipient_type`),
  KEY `idx_recipient_id` (`recipient_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_contracts`
--

DROP TABLE IF EXISTS `staff_contracts`;
CREATE TABLE `staff_contracts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(10) unsigned NOT NULL,
  `contract_type` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `salary` decimal(12,2) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'active',
  `document_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_departments`
--

DROP TABLE IF EXISTS `staff_departments`;
CREATE TABLE `staff_departments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `department_name` varchar(120) NOT NULL,
  `department_code` varchar(20) DEFAULT NULL,
  `department_level` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_dept_name` (`department_name`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_departments`
--

INSERT INTO `staff_departments` (`id`, `department_name`, `department_code`, `department_level`, `description`, `is_active`, `created_at`) VALUES
('1', 'Executive Leadership', 'EXEC', '1', NULL, '1', '2026-06-19 23:58:56'),
('2', 'Academic Affairs', 'ACAD', '2', NULL, '1', '2026-06-19 23:58:56'),
('3', 'Finance & Accounts', 'FIN', '3', NULL, '1', '2026-06-19 23:58:56'),
('4', 'Human Resources', 'HR', '4', NULL, '1', '2026-06-19 23:58:56'),
('5', 'Nursing Department', 'NUR', '5', NULL, '1', '2026-06-19 23:58:56'),
('6', 'Midwifery Department', 'MID', '6', NULL, '1', '2026-06-19 23:58:56'),
('7', 'ICT', 'ICT', '7', NULL, '1', '2026-06-19 23:58:56'),
('8', 'Admissions', 'ADM', '8', NULL, '1', '2026-06-19 23:58:56'),
('9', 'Library', 'LIB', '9', NULL, '1', '2026-06-19 23:58:56'),
('10', 'Security & Transport', 'SEC', '10', NULL, '1', '2026-06-19 23:58:56'),
('11', 'Store & Assets', 'STR', '11', NULL, '1', '2026-06-19 23:58:56'),
('12', 'Student Services', 'SVS', '12', NULL, '1', '2026-06-19 23:58:56');

-- --------------------------------------------------------

--
-- Table structure for table `staff_login_sessions`
--

DROP TABLE IF EXISTS `staff_login_sessions`;
CREATE TABLE `staff_login_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `session_token` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=129 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_login_sessions`
--

INSERT INTO `staff_login_sessions` (`id`, `staff_id`, `session_token`, `ip_address`, `user_agent`, `created_at`, `expires_at`) VALUES
('1', '1', 'pu2hvlihjqangi7jviepaf0ob7', '::1', 'curl/8.19.0', '2026-06-09 23:06:48', '2026-06-09 23:36:48'),
('2', '4', '83656fpgh06q9gouhm60nk3tuq', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-06-09 23:07:33', '2026-06-09 23:37:33'),
('3', '4', 'lh39hd80nldj2uegqkjhjk2efn', '::1', 'curl/8.19.0', '2026-06-09 23:16:40', '2026-06-09 23:46:40'),
('4', '1', '7ljqo58oc291b11bqi2s3cjffg', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-06-09 23:27:04', '2026-06-09 23:57:04'),
('5', '25', 'hlr81jh15cqvlf6nl6j8nlhk3f', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-06-10 00:57:13', '2026-06-10 01:27:13'),
('6', '24', 'ae3he9cgsdvgdf024bolec2r14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-06-10 01:02:10', '2026-06-10 01:32:10'),
('7', '9', 'dr24ed01jpd3hparhq890kpnf0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '2026-06-10 06:12:56', '2026-06-10 06:42:56'),
('8', '1', 'k8j0smrve1hncrjkq2he9fu0rh', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 03:34:12', '2026-06-17 04:04:12'),
('9', '1', '2f99647bj7odhsl4cj6vhlals8', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-17 22:18:02', '2026-06-17 22:48:02'),
('10', '2', 'suho7uaqglfdjpgt6f6bpr0nqb', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:15:02', '2026-06-18 02:45:02'),
('11', '3', 'gn380t4p7ebopr4pbmd83r3098', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:20:34', '2026-06-18 02:50:34'),
('12', '4', 'j0bvg0i2bsstfd5f2b71pnbhv2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:21:40', '2026-06-18 02:51:40'),
('13', '5', '1p2sqtjhn2q39oq8uok2bka2s6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:23:54', '2026-06-18 02:53:54'),
('14', '6', 'ebpn95qsf7pvk6jr5iad1vi3lk', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:25:03', '2026-06-18 02:55:03'),
('15', '25', 's2q2c95audemj51h44e3vmah41', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:34:25', '2026-06-18 03:04:25'),
('16', '24', 'qf5sbbkufe4onpt0j5qdg8cfp4', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 02:35:42', '2026-06-18 03:05:42'),
('17', '1', '1359ma7hua0fmmvl8espcd9an7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 09:31:15', '2026-06-18 10:01:15'),
('18', '2', 'blmvsuvsqc3h3fq4ed857p8asq', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 10:01:58', '2026-06-18 10:31:58'),
('19', '25', 'vv9i7126ujrh0ht0sekerd5vnq', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 10:03:51', '2026-06-18 10:33:51'),
('20', '1', 'sc7nqfk1p54kusvoh7959k81c7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 10:50:50', '2026-06-18 11:20:50'),
('21', '25', '6rclk83t17947n4pj1ngh9hj81', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 11:29:16', '2026-06-18 11:59:16'),
('22', '7', '30mj4uha05dsb1rdea48hkrgb5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 11:56:46', '2026-06-18 12:26:46'),
('23', '7', '2hlgnoq56hhvf37ar4im2ue7nm', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:17:44', '2026-06-18 13:47:44'),
('24', '7', 'gpqmln2qp7o00ek4rjjenf4khj', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:17:56', '2026-06-18 13:47:56'),
('25', '23', '34mpge2kds50ab697a1agal7us', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:19:17', '2026-06-18 13:49:17'),
('26', '22', 'mmt06pq180c82ofjuf8hgihiva', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:20:23', '2026-06-18 13:50:23'),
('27', '22', 'jef954p75gcad385f70ig9tadc', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:37:05', '2026-06-18 14:07:05'),
('28', '24', 't8g4s4ib33vp3villv4iv8p5no', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:37:22', '2026-06-18 14:07:22'),
('29', '24', 'h0l3knqrvi229h6laq5ltln2to', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:44:53', '2026-06-18 14:14:53'),
('30', '24', 'jv52bv72042nq2v2vileprunqr', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 13:47:26', '2026-06-18 14:17:26'),
('31', '7', '0pa58vehm4juir1f0924c929eu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-18 20:38:19', '2026-06-18 21:08:19'),
('32', '17', 'gv23nhcevrnc6cu2sqj1q6ksp0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 22:33:02', '2026-06-19 23:03:02'),
('33', '1', 'erd3bpes4jq9qfk173g5561tn7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-19 23:52:59', '2026-06-20 00:22:59'),
('34', '2', 'c31ettfnja46ueh449bkq22vh7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 03:31:21', '2026-06-20 04:01:21'),
('35', '25', '79007ugk7c1mi07d7m5c9l9c71', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 04:07:31', '2026-06-20 04:37:31'),
('36', '17', '6a3hb5erpafv3av162128t5dpb', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 04:08:13', '2026-06-20 04:38:13'),
('37', '1', 'qbin2lntmfe0ctm7s80b5ccsi6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 11:16:50', '2026-06-20 11:46:50'),
('38', '25', '63qd2kbtvalb6jlf259akkthcc', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 12:18:33', '2026-06-20 12:48:33'),
('39', '1', '5adsl9dnpdml0l9089vi78sk1j', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-20 15:41:52', '2026-06-20 16:11:52'),
('40', '1', 'titkd3lgrb6p0n2s92875b1f1l', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 00:45:04', '2026-06-21 01:15:04'),
('41', '1', 'np4ea04g9arhbh2ticlj8a8jk0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 02:19:30', '2026-06-21 02:49:30'),
('42', '2', '06f1nkaks13lc7ht4kvuq2sl56', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 02:27:16', '2026-06-21 02:57:16'),
('43', '3', 'dqv8stll1pfe9kmc8lkvchal44', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 03:02:29', '2026-06-21 03:32:29'),
('44', '4', 'mkqhi86baa63c035veice145ll', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 03:38:32', '2026-06-21 04:08:32'),
('45', '25', '5tu70v12sp531pvi6bnd4ktr1f', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 03:40:07', '2026-06-21 04:10:07'),
('46', '24', '2pgtv3ai29nri6qac8qrc9ff13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 03:41:15', '2026-06-21 04:11:15'),
('47', '8', '3fjn3qhpi54ad00ig5jr46adrh', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 08:23:34', '2026-06-21 08:53:34'),
('48', '7', 'mfufdau7qocjbtu885pm8ko2od', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 08:28:06', '2026-06-21 08:58:06'),
('49', '10', 'rvau0732fn5eb7aq561lc5qkm1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 08:38:59', '2026-06-21 09:08:59'),
('50', '9', 'vbguukqdpatqmm20c3m9gjjkcf', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 08:41:11', '2026-06-21 09:11:11'),
('51', '22', 'tq6q7ogmro8nmn0207kngvd21h', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 09:42:51', '2026-06-21 10:12:51'),
('52', '17', 'u7kckp0ni8u4jro21r3902smaj', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 09:46:09', '2026-06-21 10:16:09'),
('53', '17', 'vmp1feirc6evkuqm4kr14kivj1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 09:48:32', '2026-06-21 10:18:32'),
('54', '24', '0a98v19vemano2jnpabb2j1p2g', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 09:58:10', '2026-06-21 10:28:10'),
('55', '24', '0s7tbe4ouk2fiht3obv1nvphav', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 09:58:20', '2026-06-21 10:28:20'),
('56', '24', 'cukgdorpavsii00locajfjqpci', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:31:07', '2026-06-21 11:01:07'),
('57', '24', 'lslmk523ctp75jif1nie3uqd82', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:32:28', '2026-06-21 11:02:28'),
('58', '24', 'veofu3mv8j6t624aa4fs53p2fn', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:33:45', '2026-06-21 11:03:45'),
('59', '24', '4cqichecqd00evma1u7sbk3j2q', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:36:09', '2026-06-21 11:06:09'),
('60', '7', 'd522eht2ekupd06is4b5571tss', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 10:37:24', '2026-06-21 11:07:24'),
('61', '7', 'irrr02lhbcgfrpu4l69j7d7fvl', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 21:03:10', '2026-06-21 21:33:10'),
('62', '24', 'f2v52677oj0d7cv0fts20sga63', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 21:14:03', '2026-06-21 21:44:03'),
('63', '8', 'q96npe7qia97egg4delvjpcd0u', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 21:14:44', '2026-06-21 21:44:44'),
('64', '24', 'jmg7854n7jgeu8odup6l9iot29', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 21:26:55', '2026-06-21 21:56:55'),
('65', '24', '4tblptijta3l5tfta0pvb25601', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-21 22:56:35', '2026-06-21 23:26:35'),
('66', '24', 'hpq3utci8urukiaruh92vob8mn', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 00:35:45', '2026-06-22 01:05:45'),
('67', '24', 't49b41nfcaruon5ro15p15mltc', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 01:09:10', '2026-06-22 01:39:10'),
('68', '24', 'osoor0p43434atvgr7j66t22f6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 01:13:52', '2026-06-22 01:43:52'),
('69', '24', '0me8chv0u24pr6jfgg9oe23lov', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 01:19:57', '2026-06-22 01:49:57'),
('70', '24', 'kdlr506tct65oilrnuj67pb72i', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 01:23:38', '2026-06-22 01:53:38'),
('71', '24', 'jslvmsv36efl4ukgf1q7g2skrt', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 10:50:07', '2026-06-22 11:20:07'),
('72', '24', 'hdvul1svlg8ui13hcqk01cclda', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 11:15:10', '2026-06-22 11:45:10'),
('73', '24', '18d7dqitp2ml8j9nqte0qvvtbc', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 11:34:00', '2026-06-22 12:04:00'),
('74', '24', 'ur2fs528fiomfrd25ggfbntevu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 11:57:49', '2026-06-22 12:27:49'),
('75', '1', 'qrs5r3d0crst274csne31s6bik', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 12:48:39', '2026-06-22 13:18:39'),
('76', '24', 'pqumq7aq89oarcoi7u0vfc4euc', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 12:59:31', '2026-06-22 13:29:31'),
('77', '1', '4dfl0lha5fktfpih6dio8m629n', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 13:06:59', '2026-06-22 13:36:59'),
('78', '4', 'uoi1qs187pr2gd1799ousa63cu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 13:10:22', '2026-06-22 13:40:22'),
('79', '1', '1s9luflbvjvmdor92928kc9lbf', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 13:59:47', '2026-06-22 14:29:47'),
('80', '24', 'ttjcmn74g42n5lstnqmv6pijpu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 14:59:19', '2026-06-22 15:29:19'),
('81', '1', '1pmpbl6de5stu5mdelph4h475e', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-22 22:02:54', '2026-06-22 22:32:54'),
('82', '24', 'hljpb5ph7e3j24ckvan8mauluh', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 01:21:00', '2026-06-23 01:51:00'),
('83', '25', 'ks1tultbpko3s70j5fhq4e4t9h', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 05:12:44', '2026-06-23 05:42:44'),
('84', '1', 'rf0mklksts3um16lm1c90l2gbq', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-23 22:40:59', '2026-06-23 23:10:59'),
('85', '1', 'ppfvcia8sprhfv7t5i2dsn38a0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 03:42:32', '2026-06-24 04:12:32'),
('86', '3', 'lgp10qeu8kiecak9fv098pjglo', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 03:55:07', '2026-06-24 04:25:07'),
('87', '5', '6pu90rj74r1pq47q228fcjoniu', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 05:26:23', '2026-06-24 05:56:23'),
('88', '9', 'jfn6065k8m3goqpe3sprr17b6p', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 05:26:54', '2026-06-24 05:56:54'),
('89', '1', '794af9ukhkur706kvkfku12iaq', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 06:46:58', '2026-06-24 07:16:58'),
('90', '24', '9jebpeo9cqldprvn4khlrnr9vi', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 07:01:17', '2026-06-24 07:31:17'),
('91', '25', '8a4liolknvm9st9v1kv3r106fl', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 08:02:15', '2026-06-24 08:32:15'),
('92', '1', '25vgr9ffnilj3iaufkdi2olvgd', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 08:04:28', '2026-06-24 08:34:28'),
('93', '17', 'f584elfqqdtdaneqgvu4j2sgb5', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 08:10:06', '2026-06-24 08:40:06'),
('94', '25', 'ik5cfqa2gkgjgfmgmefk090jjd', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-24 23:37:39', '2026-06-25 00:07:39'),
('95', '9', '34ca3u1j6isj17ocoq43vba38c', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 01:36:50', '2026-06-25 02:06:50'),
('96', '4', 'e5vsb9gmjeun7kito01jamqs3d', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 02:39:53', '2026-06-25 03:09:53'),
('97', '3', '7rqduhslro0lq0nplmpbunpgf0', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 03:11:18', '2026-06-25 03:41:18'),
('98', '5', 'tl71050ga502dbhf0tggle7b8d', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 03:11:55', '2026-06-25 03:41:55'),
('99', '6', '89n9gmr0fjrhmuuolavg7bh7vj', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 03:33:47', '2026-06-25 04:03:47'),
('100', '5', 't5s26jd6cbasdfv24scv7oqgnc', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 03:44:45', '2026-06-25 04:14:45'),
('101', '24', 'p2ek6i7irhqbkkppvei7r15olf', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 03:47:18', '2026-06-25 04:17:18'),
('102', '24', 'btumr0h7kam4vbeliviv0vht80', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 07:25:54', '2026-06-25 07:55:54'),
('103', '1', 'affknfo0e0cod2oi2jru0qjgph', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 08:58:55', '2026-06-25 09:28:55'),
('104', '2', 'h447aeemqdhvj8dlaofabvmss9', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 09:22:04', '2026-06-25 09:52:04'),
('105', '4', 'a087m1fgf0fu8elbeoe57g6il1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-25 09:22:40', '2026-06-25 09:52:40'),
('106', '17', 'n9o9l07t52qa71jjmg3l9egl3q', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 04:07:21', '2026-06-26 04:37:21'),
('107', '8', 'j92qk81fhdbtt122h79ckue45f', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 04:18:03', '2026-06-26 04:48:03'),
('108', '8', 'edj83pm5bjgr8g6vbeci45ajod', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 05:39:08', '2026-06-26 06:09:08'),
('109', '25', '41s1vms3719jbporauptnbjumd', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 05:48:03', '2026-06-26 06:18:03'),
('110', '8', '3vg1268gsos1b49pha89j8qcdl', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8737', '2026-06-26 06:02:14', '2026-06-26 06:32:14'),
('111', '8', 't0va2mgidaq269fsdhp6dscgr3', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8737', '2026-06-26 06:02:22', '2026-06-26 06:32:22'),
('112', '25', 'gq0ncrfvok3ljs5trl2u2meclg', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8737', '2026-06-26 06:03:07', '2026-06-26 06:33:07'),
('113', '8', '22gvam5engahgclurhh82mppuh', '::1', 'Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8737', '2026-06-26 06:03:20', '2026-06-26 06:33:20'),
('114', '7', 'nj45vbijbkug1j88nncnts0oah', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 06:36:27', '2026-06-26 07:06:27'),
('115', '23', '9m3qh7jl3j8bq9fm0qafu02snb', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 07:09:45', '2026-06-26 07:39:45'),
('116', '23', '3tnohnrv7m0us60fmm0slh4vjn', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 12:36:42', '2026-06-26 13:06:42'),
('117', '23', 'odl6daac37jvuiaqq30ebv211t', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 14:05:09', '2026-06-26 14:35:09'),
('118', '23', 'vgh2eulctao8s12rdgdu77t1q7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 21:23:13', '2026-06-26 21:53:13'),
('119', '1', 'ijjcb8ppqqeg0eh07rctuob530', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 22:04:55', '2026-06-26 22:34:55'),
('120', '23', '98sb0ebgrcuh2adlceuj198qfm', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 22:09:09', '2026-06-26 22:39:09'),
('121', '22', '6undm5ctv6iltdc39d2cnr2kom', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 22:12:56', '2026-06-26 22:42:56'),
('122', '23', '1vhpuhmg2t9v6s93e4vt1gdasl', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 22:43:32', '2026-06-26 23:13:32'),
('123', '22', '7qj0v2vurgdm825hnht7cc6cki', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 22:44:41', '2026-06-26 23:14:41'),
('124', '22', '384f35su5t0cqbgqg2q623n8e7', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 23:26:56', '2026-06-26 23:56:56'),
('125', '23', 'cl13gqakjddlkkagtdm07brjs2', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 23:38:31', '2026-06-27 00:08:31'),
('126', '22', 'hvq4ej7521ircu9s4qdi9a4nu8', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 23:38:51', '2026-06-27 00:08:51'),
('127', '22', '89a3kokapu2e5308u309ftsdoa', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-26 23:55:47', '2026-06-27 00:25:47'),
('128', '1', 'obs5jdudi11f3h2ffk564c90j6', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', '2026-06-27 07:26:14', '2026-06-27 07:56:14');

-- --------------------------------------------------------

--
-- Table structure for table `staff_profiles`
--

DROP TABLE IF EXISTS `staff_profiles`;
CREATE TABLE `staff_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_id` (`staff_id`),
  CONSTRAINT `staff_profiles_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_profiles`
--

INSERT INTO `staff_profiles` (`id`, `staff_id`, `profile_picture`, `bio`, `department`, `phone`, `address`, `created_at`, `updated_at`) VALUES
('3', '1', NULL, '', NULL, NULL, NULL, '2026-06-24 00:08:59', '2026-06-24 00:08:59'),
('4', '24', NULL, '', NULL, NULL, NULL, '2026-06-24 07:01:59', '2026-06-24 07:01:59');

-- --------------------------------------------------------

--
-- Table structure for table `staff_resignations`
--

DROP TABLE IF EXISTS `staff_resignations`;
CREATE TABLE `staff_resignations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(10) unsigned NOT NULL,
  `resignation_date` date DEFAULT NULL,
  `last_working_date` date DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `approved_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_roles`
--

DROP TABLE IF EXISTS `staff_roles`;
CREATE TABLE `staff_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(100) NOT NULL,
  `role_description` text DEFAULT NULL,
  `role_level` int(11) DEFAULT 5,
  `dashboard_path` varchar(255) DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_roles`
--

INSERT INTO `staff_roles` (`id`, `role_name`, `role_description`, `role_level`, `dashboard_path`, `permissions`, `created_at`, `updated_at`) VALUES
('1', 'Director General', NULL, '1', 'dashboards/director-general.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
('2', 'CEO', NULL, '1', 'dashboards/ceo.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
('3', 'Director Academics', NULL, '2', 'dashboards/director-academics.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
('4', 'Director Finance', NULL, '2', 'dashboards/director-finance.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
('5', 'Director ICT', NULL, '2', 'dashboards/director-ict.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
('6', 'School Principal', NULL, '2', 'dashboards/school-principal.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
('7', 'Deputy Principal', NULL, '3', 'dashboards/deputy-principal.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
('8', 'Academic Registrar', NULL, '3', 'dashboards/academic-registrar.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
('9', 'HR Manager', NULL, '3', 'dashboards/hr-manager.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
('10', 'School Secretary', NULL, '4', 'dashboards/school-secretary.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
('11', 'School Librarian', NULL, '4', 'dashboards/school-librarian.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
('12', 'Head Nursing', NULL, '3', 'dashboards/head-nursing.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
('13', 'Head Midwifery', NULL, '3', 'dashboards/head-midwifery.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
('14', 'Senior Lecturers', NULL, '4', 'dashboards/senior-lecturers.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
('15', 'Lecturers', NULL, '5', 'dashboards/lecturers.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
('16', 'Matrons', NULL, '4', 'dashboards/matrons.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
('17', 'Wardens', NULL, '5', 'dashboards/wardens.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
('18', 'Sickbay', NULL, '5', 'dashboards/sickbay.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
('19', 'Drivers', NULL, '6', 'dashboards/drivers.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
('20', 'Security', NULL, '6', 'dashboards/security.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
('21', 'Storekeeper', NULL, '5', 'dashboards/storekeeper.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
('22', 'Guild President', NULL, '5', 'dashboards/guild-president.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
('23', 'Computer Lab Manager', NULL, '3', 'computer_lab.php', NULL, '2026-06-09 22:56:09', '2026-06-09 22:56:09'),
('24', 'School Bursar', NULL, '3', 'dashboards/school-bursar.php', NULL, '2026-06-09 22:56:09', '2026-06-26 05:57:33'),
('25', 'Store Keeper', 'Store inventory', '0', 'dashboards/storekeeper.php', '{\"store\":true,\"inventory\":true}', '2026-06-13 02:38:49', '2026-06-13 02:38:49'),
('26', 'Director Admissions & Requirements', 'Admissions management', '0', 'dashboards/director-admissions.php', '{\"admissions\":true,\"requirements\":true}', '2026-06-13 02:38:49', '2026-06-13 02:38:49'),
('27', 'Bursar', 'Bursar assistant', '0', 'dashboards/school-bursar.php', '{\"financial\":true,\"fees\":true}', '2026-06-13 02:38:49', '2026-06-26 05:57:33');

-- --------------------------------------------------------

--
-- Table structure for table `staff_salaries`
--

DROP TABLE IF EXISTS `staff_salaries`;
CREATE TABLE `staff_salaries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(10) unsigned NOT NULL,
  `basic_salary` decimal(12,2) DEFAULT 0.00,
  `allowances` decimal(12,2) DEFAULT 0.00,
  `overtime_rate` decimal(12,2) DEFAULT 0.00,
  `nssf_tax` decimal(12,2) DEFAULT 0.00,
  `paye_tax` decimal(12,2) DEFAULT 0.00,
  `effective_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `bonus` decimal(10,2) DEFAULT 0.00,
  `deductions` decimal(10,2) DEFAULT 0.00,
  `net_salary` decimal(12,2) DEFAULT 0.00,
  `payment_month` varchar(20) DEFAULT NULL,
  `payment_year` varchar(10) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_salaries`
--

INSERT INTO `staff_salaries` (`id`, `staff_id`, `basic_salary`, `allowances`, `overtime_rate`, `nssf_tax`, `paye_tax`, `effective_date`, `created_by`, `bonus`, `deductions`, `net_salary`, `payment_month`, `payment_year`, `status`, `created_at`, `updated_at`) VALUES
('1', '7', '1500000.00', '0.00', '0.00', '0.00', '0.02', '2026-06-25', '25', '0.00', '0.02', '1499999.98', NULL, NULL, 'Active', '2026-06-25 00:35:20', '2026-06-25 00:35:20');

-- --------------------------------------------------------

--
-- Table structure for table `store_orders`
--

DROP TABLE IF EXISTS `store_orders`;
CREATE TABLE `store_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(50) NOT NULL,
  `supplier` varchar(200) DEFAULT 'Internal Requisition',
  `notes` text DEFAULT NULL,
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `status` varchar(50) DEFAULT 'pending_approval',
  `requested_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `received_by` int(11) DEFAULT NULL,
  `received_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_orders_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `store_requests`
--

DROP TABLE IF EXISTS `store_requests`;
CREATE TABLE `store_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `request_number` varchar(60) NOT NULL,
  `requested_by` int(10) unsigned DEFAULT NULL,
  `department` varchar(80) DEFAULT NULL,
  `items` text DEFAULT NULL,
  `urgency` varchar(20) NOT NULL DEFAULT 'medium',
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `forwarded_to` int(10) unsigned DEFAULT NULL,
  `approval_request_id` int(10) unsigned DEFAULT NULL,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_request_number` (`request_number`),
  KEY `idx_store_status` (`status`),
  KEY `idx_store_urgency` (`urgency`),
  KEY `idx_approval` (`approval_request_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `store_requests`
--

INSERT INTO `store_requests` (`id`, `request_number`, `requested_by`, `department`, `items`, `urgency`, `status`, `forwarded_to`, `approval_request_id`, `approved_by`, `notes`, `created_at`, `updated_at`) VALUES
('1', 'SR-2026-0001', '1', NULL, NULL, 'medium', 'pending_approval', NULL, '1', NULL, NULL, '2026-06-08 08:58:56', '2026-06-20 00:47:50'),
('2', 'SR-2026-0002', '1', NULL, NULL, 'urgent', 'pending_approval', NULL, '2', NULL, NULL, '2026-06-10 08:58:56', '2026-06-20 00:47:50'),
('3', 'SR-2026-0003', '1', NULL, NULL, 'medium', 'pending_approval', NULL, '3', NULL, NULL, '2026-06-10 08:58:56', '2026-06-20 00:47:50'),
('4', 'SR-2026-0004', '1', NULL, NULL, 'high', 'pending', NULL, NULL, NULL, NULL, '2026-06-14 08:58:56', NULL),
('5', 'SR-2026-0005', '1', NULL, NULL, 'high', 'pending', NULL, NULL, NULL, NULL, '2026-06-18 08:58:56', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_admissions`
--

DROP TABLE IF EXISTS `student_admissions`;
CREATE TABLE `student_admissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `admission_date` date NOT NULL,
  `program` varchar(200) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `admission_type` varchar(50) DEFAULT 'regular',
  `status` varchar(30) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `idx_sa_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_discipline_records`
--

DROP TABLE IF EXISTS `student_discipline_records`;
CREATE TABLE `student_discipline_records` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `incident_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `reported_by` int(10) unsigned DEFAULT NULL,
  `action_taken` text DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_documents`
--

DROP TABLE IF EXISTS `student_documents`;
CREATE TABLE `student_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `applicant_id` int(11) NOT NULL,
  `requirement_id` int(11) DEFAULT NULL,
  `document_type` varchar(100) NOT NULL DEFAULT 'Other',
  `document_title` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `verification_status` enum('Pending','Verified','Rejected') DEFAULT 'Pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `document_status` varchar(50) DEFAULT 'Pending',
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_docs_applicant` (`applicant_id`),
  KEY `idx_docs_status` (`verification_status`),
  KEY `idx_doc_requirement` (`requirement_id`),
  CONSTRAINT `student_documents_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_fee_accounts`
--

DROP TABLE IF EXISTS `student_fee_accounts`;
CREATE TABLE `student_fee_accounts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `total_fees` decimal(12,2) DEFAULT 0.00,
  `amount_paid` decimal(12,2) DEFAULT 0.00,
  `balance` decimal(12,2) DEFAULT 0.00,
  `status` varchar(30) DEFAULT 'active',
  `due_date` date DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `program` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `status` (`status`),
  KEY `invoice_number` (`invoice_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_fee_assignments`
--

DROP TABLE IF EXISTS `student_fee_assignments`;
CREATE TABLE `student_fee_assignments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `fee_structure_id` int(10) unsigned DEFAULT NULL,
  `total_amount` decimal(12,2) DEFAULT NULL,
  `amount_paid` decimal(12,2) DEFAULT 0.00,
  `balance` decimal(12,2) DEFAULT 0.00,
  `academic_year` varchar(20) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'active',
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_fees`
--

DROP TABLE IF EXISTS `student_fees`;
CREATE TABLE `student_fees` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `fee_type` varchar(100) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT 0.00,
  `balance` decimal(12,2) DEFAULT 0.00,
  `due_date` date DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_hostel_allocations`
--

DROP TABLE IF EXISTS `student_hostel_allocations`;
CREATE TABLE `student_hostel_allocations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `room_id` int(10) unsigned DEFAULT NULL,
  `allocation_date` date DEFAULT NULL,
  `status` varchar(30) DEFAULT 'active',
  `checkout_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_invoices`
--

DROP TABLE IF EXISTS `student_invoices`;
CREATE TABLE `student_invoices` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned DEFAULT NULL,
  `invoice_number` varchar(60) DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `amount_paid` decimal(12,2) DEFAULT 0.00,
  `balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_invoices_status` (`status`),
  KEY `idx_invoices_student` (`student_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_invoices`
--

INSERT INTO `student_invoices` (`id`, `student_id`, `invoice_number`, `total_amount`, `amount_paid`, `balance`, `status`, `due_date`, `created_at`) VALUES
('1', '1', 'INV-2024-001', '1500000.00', '1000000.00', '500000.00', 'partial', '2024-12-31', '2026-06-19 23:59:17'),
('2', '2', 'INV-2024-002', '1200000.00', '1200000.00', '0.00', 'paid', '2024-11-30', '2026-06-19 23:59:17'),
('3', '3', 'INV-2024-003', '1500000.00', '0.00', '1500000.00', 'pending', '2025-01-31', '2026-06-19 23:59:17'),
('4', '4', 'INV-2024-004', '1800000.00', '800000.00', '1000000.00', 'partial', '2025-02-28', '2026-06-19 23:59:17'),
('5', '5', 'INV-2024-005', '1500000.00', '500000.00', '1000000.00', 'partial', '2025-03-31', '2026-06-19 23:59:17');

-- --------------------------------------------------------

--
-- Table structure for table `student_password_resets`
--

DROP TABLE IF EXISTS `student_password_resets`;
CREATE TABLE `student_password_resets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_penalties`
--

DROP TABLE IF EXISTS `student_penalties`;
CREATE TABLE `student_penalties` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `penalty_type` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `reason` text DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `waived_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_profiles`
--

DROP TABLE IF EXISTS `student_profiles`;
CREATE TABLE `student_profiles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `bio` text DEFAULT NULL,
  `emergency_contact` varchar(100) DEFAULT NULL,
  `emergency_phone` varchar(30) DEFAULT NULL,
  `medical_info` text DEFAULT NULL,
  `guardian_name` varchar(200) DEFAULT NULL,
  `guardian_phone` varchar(30) DEFAULT NULL,
  `guardian_email` varchar(150) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_progression`
--

DROP TABLE IF EXISTS `student_progression`;
CREATE TABLE `student_progression` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `from_year` int(11) DEFAULT NULL,
  `from_semester` varchar(50) DEFAULT NULL,
  `to_year` int(11) DEFAULT NULL,
  `to_semester` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `cgpa` decimal(4,2) DEFAULT NULL,
  `decision` enum('promoted','probation','repeat','withdrawn','supplementary') DEFAULT 'promoted',
  `remarks` text DEFAULT NULL,
  `decided_by` int(11) DEFAULT NULL,
  `decided_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sp_student` (`student_id`),
  KEY `idx_sp_decision` (`decision`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_sick_leave`
--

DROP TABLE IF EXISTS `student_sick_leave`;
CREATE TABLE `student_sick_leave` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `recommended_by` varchar(200) DEFAULT NULL,
  `recommender_title` varchar(100) DEFAULT NULL,
  `approved_by` varchar(200) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Expired','Extended') DEFAULT 'Pending',
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
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
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `leave_number` (`leave_number`),
  KEY `student_id` (`student_id`),
  KEY `sickness_id` (`sickness_id`),
  KEY `leave_from` (`leave_from`),
  KEY `leave_to` (`leave_to`),
  KEY `status` (`status`),
  KEY `student_name` (`student_name`),
  KEY `program` (`program`),
  KEY `ssl_student_status` (`student_id`,`status`),
  CONSTRAINT `student_sick_leave_ibfk_1` FOREIGN KEY (`sickness_id`) REFERENCES `sickness_directory` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_timetables`
--

DROP TABLE IF EXISTS `student_timetables`;
CREATE TABLE `student_timetables` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `course_code` varchar(30) DEFAULT NULL,
  `course_name` varchar(200) DEFAULT NULL,
  `day_of_week` varchar(20) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `lecturer` varchar(200) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_welfare_cases`
--

DROP TABLE IF EXISTS `student_welfare_cases`;
CREATE TABLE `student_welfare_cases` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `case_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(30) DEFAULT 'open',
  `assigned_to` int(10) unsigned DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `student_number` varchar(60) DEFAULT NULL,
  `full_name` varchar(200) DEFAULT NULL,
  `program` varchar(120) DEFAULT NULL,
  `level` varchar(20) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'Active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_student_number` (`student_number`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`id`, `first_name`, `last_name`, `student_number`, `full_name`, `program`, `level`, `status`) VALUES
('1', 'Grace', 'Nakato', 'ISNM-2024-001', 'Grace Nakato', 'Diploma Nursing', NULL, 'Active'),
('2', 'David', 'Ssali', 'ISNM-2024-002', 'David Ssali', 'Certificate Midwifery', NULL, 'Active'),
('3', 'Mary', 'Nalwoga', 'ISNM-2024-003', 'Mary Nalwoga', 'Certificate Nursing', NULL, 'Active'),
('4', 'James', 'Okello', 'ISNM-2024-004', 'James Okello', 'Diploma Midwifery', NULL, 'Active'),
('5', 'Sarah', 'Kyomugisha', 'ISNM-2024-005', 'Sarah Kyomugisha', 'Diploma Nursing', NULL, 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
CREATE TABLE `subjects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `code` varchar(30) DEFAULT NULL,
  `department_id` int(10) unsigned DEFAULT NULL,
  `credits` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscription_deductions`
--

DROP TABLE IF EXISTS `subscription_deductions`;
CREATE TABLE `subscription_deductions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `subscription_id` int(10) unsigned NOT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `deduction_date` date DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `reference` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `subscription_id` (`subscription_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

DROP TABLE IF EXISTS `system_logs`;
CREATE TABLE `system_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `log_type` varchar(50) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `log_type` (`log_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

DROP TABLE IF EXISTS `teachers`;
CREATE TABLE `teachers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(10) unsigned NOT NULL,
  `department_id` int(10) unsigned DEFAULT NULL,
  `specialization` varchar(200) DEFAULT NULL,
  `qualification` varchar(200) DEFAULT NULL,
  `employment_type` varchar(50) DEFAULT 'full-time',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teaching_quality_reviews`
--

DROP TABLE IF EXISTS `teaching_quality_reviews`;
CREATE TABLE `teaching_quality_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lecturer_id` int(11) DEFAULT NULL,
  `review_date` date DEFAULT NULL,
  `teaching_score` decimal(5,2) DEFAULT NULL,
  `course_code` varchar(50) DEFAULT NULL,
  `observer` varchar(200) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `status` enum('draft','completed','reviewed') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timetables`
--

DROP TABLE IF EXISTS `timetables`;
CREATE TABLE `timetables` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `class_id` int(10) unsigned DEFAULT NULL,
  `subject_id` int(10) unsigned DEFAULT NULL,
  `teacher_id` int(10) unsigned DEFAULT NULL,
  `day_of_week` varchar(20) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `room` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transcript_items`
--

DROP TABLE IF EXISTS `transcript_items`;
CREATE TABLE `transcript_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transcript_id` int(11) NOT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `course_name` varchar(255) DEFAULT NULL,
  `credit_units` decimal(4,1) DEFAULT 0.0,
  `marks` decimal(5,2) DEFAULT 0.00,
  `grade` varchar(5) DEFAULT NULL,
  `grade_points` decimal(4,2) DEFAULT 0.00,
  `semester_gpa` decimal(4,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ti_transcript` (`transcript_id`),
  CONSTRAINT `fk_ti_transcript` FOREIGN KEY (`transcript_id`) REFERENCES `transcripts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transcript_templates`
--

DROP TABLE IF EXISTS `transcript_templates`;
CREATE TABLE `transcript_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_name` varchar(200) NOT NULL,
  `template_data` longtext DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transcripts`
--

DROP TABLE IF EXISTS `transcripts`;
CREATE TABLE `transcripts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transcript_number` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `program` varchar(255) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `cgpa` decimal(4,2) DEFAULT 0.00,
  `total_credits` int(11) DEFAULT 0,
  `class_of_degree` varchar(100) DEFAULT NULL,
  `academic_standing` varchar(100) DEFAULT 'Good Standing',
  `purpose` text DEFAULT NULL,
  `status` enum('draft','pending','approved','rejected','generated','issued') DEFAULT 'draft',
  `requested_by` int(11) DEFAULT NULL,
  `requested_at` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejected_by` int(11) DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `generated_at` datetime DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `student_downloadable` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `transcript_number` (`transcript_number`),
  KEY `idx_t_student` (`student_id`),
  KEY `idx_t_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ura_reports`
--

DROP TABLE IF EXISTS `ura_reports`;
CREATE TABLE `ura_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_name` varchar(200) DEFAULT NULL,
  `tax_period` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT 0.00,
  `status` varchar(50) DEFAULT 'pending',
  `report_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

DROP TABLE IF EXISTS `user_sessions`;
CREATE TABLE `user_sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(200) DEFAULT NULL,
  `role` varchar(50) DEFAULT 'staff',
  `status` varchar(30) DEFAULT 'active',
  `staff_id` int(10) unsigned DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Views
--

DROP VIEW IF EXISTS `fee_payments`;
CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `fee_payments` AS select `igangaschoolofl_students_db`.`payments`.`id` AS `id`,`igangaschoolofl_students_db`.`payments`.`student_id` AS `student_id`,`igangaschoolofl_students_db`.`payments`.`invoice_id` AS `fee_account_id`,`igangaschoolofl_students_db`.`payments`.`amount_received` AS `amount_paid`,`igangaschoolofl_students_db`.`payments`.`payment_method` AS `payment_method`,`igangaschoolofl_students_db`.`payments`.`payment_reference` AS `receipt_number`,`igangaschoolofl_students_db`.`payments`.`status` AS `status`,`igangaschoolofl_students_db`.`payments`.`payment_date` AS `payment_date`,`igangaschoolofl_students_db`.`payments`.`notes` AS `notes`,`igangaschoolofl_students_db`.`payments`.`received_by` AS `processed_by`,`igangaschoolofl_students_db`.`payments`.`created_at` AS `created_at`,`igangaschoolofl_students_db`.`payments`.`updated_at` AS `updated_at` from `igangaschoolofl_students_db`.`payments`;

-- --------------------------------------------------------

--
-- Procedures
--

DROP PROCEDURE IF EXISTS `get_dashboard_statistics`;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_dashboard_statistics`(IN p_user_id INT, IN p_role VARCHAR(100))
BEGIN
    SELECT
        (SELECT COUNT(*) FROM igangaschoolofl_staffs_db.staff WHERE status='Active') AS total_staff,
        (SELECT COUNT(*) FROM igangaschoolofl_students_db.students WHERE status='Active') AS total_students,
        0 AS pending_applications,
        2 AS active_programs,
        0 AS total_revenue,
        0 AS total_expenses;
END;

DROP PROCEDURE IF EXISTS `get_staff_performance_summary`;
CREATE DEFINER=`root`@`localhost` PROCEDURE `get_staff_performance_summary`(IN p_user_id INT)
BEGIN
    SELECT s.id, s.full_name, sr.role_name, s.department, s.status,
           0 AS performance_score, 'Good' AS rating
    FROM staff s
    LEFT JOIN staff_roles sr ON s.role_id = sr.id
    WHERE s.status = 'Active'
    ORDER BY s.full_name
    LIMIT 20;
END;

--
-- Functions
--

-- --------------------------------------------------------

--
-- Triggers
--

SET FOREIGN_KEY_CHECKS = 1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
