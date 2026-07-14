-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 14, 2026 at 10:17 AM
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
-- Database: `igangaschool_ict`
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

CREATE DEFINER=`igangaschool`@`localhost` PROCEDURE `add_role_description_col_if_missing` ()   BEGIN
    DECLARE CONTINUE HANDLER FOR 1060 BEGIN END;
    ALTER TABLE staff_roles ADD COLUMN role_description TEXT AFTER role_name;
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
    -- Extend status enum
    ALTER TABLE `payroll_runs` MODIFY COLUMN `status` ENUM('draft','approved','processed','paid','completed','processing') DEFAULT 'draft';

    -- payroll_details: add housing_allowance, transport_allowance
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payroll_details' AND COLUMN_NAME='housing_allowance') THEN
        ALTER TABLE `payroll_details` ADD COLUMN `housing_allowance` DECIMAL(12,2) DEFAULT 0.00 AFTER `basic_salary`;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payroll_details' AND COLUMN_NAME='transport_allowance') THEN
        ALTER TABLE `payroll_details` ADD COLUMN `transport_allowance` DECIMAL(12,2) DEFAULT 0.00 AFTER `housing_allowance`;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payroll_details' AND COLUMN_NAME='`status`') THEN
        ALTER TABLE `payroll_details` ADD COLUMN `status` VARCHAR(20) DEFAULT 'calculated' AFTER `payment_status`;
    END IF;

    -- payroll_employees: add allowance columns
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payroll_employees' AND COLUMN_NAME='housing_allowance') THEN
        ALTER TABLE `payroll_employees` ADD COLUMN `housing_allowance` DECIMAL(12,2) DEFAULT 0.00 AFTER `basic_salary`;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payroll_employees' AND COLUMN_NAME='transport_allowance') THEN
        ALTER TABLE `payroll_employees` ADD COLUMN `transport_allowance` DECIMAL(12,2) DEFAULT 0.00 AFTER `housing_allowance`;
    END IF;

    -- salary_structures: add staff_id FK
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

    -- bursar_vat_reports: add created_at
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='bursar_vat_reports' AND COLUMN_NAME='created_at') THEN
        ALTER TABLE `bursar_vat_reports` ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `status`;
    END IF;

    -- bursar_withholding_tax: add period column
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='bursar_withholding_tax' AND COLUMN_NAME='period') THEN
        ALTER TABLE `bursar_withholding_tax` ADD COLUMN `period` VARCHAR(20) DEFAULT NULL AFTER `tax_date`;
    END IF;

    -- payroll_approvals: extend level enum to include CEO, add comments support
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
(1, 1, 'welcome', 'text', 'Welcome to Iganga School of Nursing and Midwifery', 'Established to provide quality nursing and midwifery education in Uganda and the region.', '<p>Iganga School of Nursing and Midwifery (ISNM) is a premier healthcare education institution dedicated to training competent, compassionate, and skilled nurses and midwives. Located in Iganga, Eastern Uganda, we have been at the forefront of healthcare education since 1997.</p><p>Our programs are designed to equip students with the knowledge, skills, and values needed to provide quality healthcare services in diverse settings.</p>', NULL, 'fade-up', NULL, NULL, 1, 1, NULL, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(2, 1, 'stats', 'stats', 'Our Impact in Numbers', 'Making a difference in healthcare education', NULL, NULL, 'fade-up', NULL, NULL, 2, 1, NULL, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(3, 1, 'why_choose', 'cards', 'Why Choose ISNM', 'Discover what makes us the preferred choice for healthcare education', NULL, NULL, 'fade-up', NULL, NULL, 3, 1, NULL, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(4, 1, 'cta', 'cta', 'Ready to Start Your Journey?', 'Join thousands of healthcare professionals trained at ISNM', NULL, NULL, 'fade-up', NULL, NULL, 10, 1, NULL, '2026-07-14 07:12:48', '2026-07-14 07:12:48');

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
(1, 'New Academic Year Orientation', 'new-academic-year-orientation-2026', 'Welcome ceremony and orientation for new and returning students for the 2026 academic year.', 'Welcome ceremony for all students', '2026-02-01', NULL, NULL, NULL, 'ISNM Main Campus', 'academic', NULL, NULL, NULL, 0, 0, 1, NULL, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(2, 'International Nurses Day Celebration', 'international-nurses-day-2026', 'Annual celebration of International Nurses Day with guest speakers, exhibitions, and awards.', 'Celebrating nursing excellence', '2026-05-12', NULL, NULL, NULL, 'ISNM Auditorium', 'ceremony', NULL, NULL, NULL, 0, 0, 1, NULL, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(3, 'Clinical Skills Workshop', 'clinical-skills-workshop-2026', 'Hands-on workshop for nursing students on advanced clinical skills and patient care techniques.', 'Advanced clinical skills training', '2026-06-15', NULL, NULL, NULL, 'ISNM Skills Laboratory', 'workshop', NULL, NULL, NULL, 0, 0, 1, NULL, '2026-07-14 07:12:48', '2026-07-14 07:12:48');

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
(1, 'general', 'What programs does ISNM offer?', 'ISNM offers Certificate in Nursing, Certificate in Midwifery, Diploma in Nursing (Extension), and Diploma in Midwifery (Extension) programs.', 'admissions', 1, 1, 0, '2026-07-14 07:12:48'),
(2, 'general', 'How do I apply to ISNM?', 'Applications can be submitted online through our application portal or in person at the admissions office. Required documents include academic certificates, national ID, and passport photos.', 'admissions', 2, 1, 0, '2026-07-14 07:12:48'),
(3, 'general', 'What are the admission requirements?', 'Requirements vary by program. Generally, candidates need O-Level certificates with at least 5 passes including English, Mathematics, Biology, and Chemistry.', 'admissions', 3, 1, 0, '2026-07-14 07:12:48'),
(4, 'general', 'How can I pay tuition fees?', 'Fees can be paid via Mobile Money (MTN/Airtel), bank transfer, or cash at the bursar\'s office. Online payment is also available through our payment portal.', 'finance', 4, 1, 0, '2026-07-14 07:12:48'),
(5, 'general', 'Does ISNM offer accommodation?', 'Yes, ISNM has on-campus hostel facilities for both male and female students. Allocation is based on availability and distance from home.', 'student_life', 5, 1, 0, '2026-07-14 07:12:48'),
(6, 'general', 'What career opportunities are available after graduation?', 'Graduates can work in hospitals, health centers, community health programs, NGOs, international organizations, and can pursue further education.', 'academic', 6, 1, 0, '2026-07-14 07:12:48'),
(7, 'general', 'Is ISNM accredited?', 'Yes, ISNM is fully accredited by the Uganda Nurses and Midwives Council (UNMC) and the National Council for Higher Education (NCHE).', 'general', 7, 1, 0, '2026-07-14 07:12:48'),
(8, 'general', 'How can I contact ISNM?', 'You can reach us by phone at +256 700 123 456, email at info@igangaschoolofnursing.ac.ug, or visit us at Iganga, Uganda.', 'general', 8, 1, 0, '2026-07-14 07:12:48');

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
(1, 'Campus Life', 'campus-life', NULL, NULL, 1, 1, '2026-07-14 07:12:48'),
(2, 'Graduation', 'graduation', NULL, NULL, 2, 1, '2026-07-14 07:12:48'),
(3, 'Clinical Training', 'clinical-training', NULL, NULL, 3, 1, '2026-07-14 07:12:48'),
(4, 'Sports & Activities', 'sports-activities', NULL, NULL, 4, 1, '2026-07-14 07:12:48'),
(5, 'Facilities', 'facilities', NULL, NULL, 5, 1, '2026-07-14 07:12:48'),
(6, 'Events', 'events', NULL, NULL, 6, 1, '2026-07-14 07:12:48');

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
(1, 'General', 'general', NULL, 'fas fa-newspaper', '#1A237E', 1, 1, '2026-07-14 07:12:48'),
(2, 'Academic', 'academic', NULL, 'fas fa-graduation-cap', '#2E7D32', 2, 1, '2026-07-14 07:12:48'),
(3, 'Admissions', 'admissions', NULL, 'fas fa-user-plus', '#E65100', 3, 1, '2026-07-14 07:12:48'),
(4, 'Events', 'events', NULL, 'fas fa-calendar-alt', '#6A1B9A', 4, 1, '2026-07-14 07:12:48'),
(5, 'Announcements', 'announcements', NULL, 'fas fa-bullhorn', '#C62828', 5, 1, '2026-07-14 07:12:48'),
(6, 'Student Life', 'student-life', NULL, 'fas fa-users', '#00838F', 6, 1, '2026-07-14 07:12:48'),
(7, 'Sports', 'sports', NULL, 'fas fa-football-ball', '#F57F17', 7, 1, '2026-07-14 07:12:48'),
(8, 'Research', 'research', NULL, 'fas fa-flask', '#1565C0', 8, 1, '2026-07-14 07:12:48');

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
(1, 'home', 'Home', NULL, 'dynamic', 'default', 'Welcome to ISNM', 'Training Competent and Caring Healthcare Professionals', NULL, 'rgba(26,35,126,0.7)', NULL, NULL, 'Iganga School of Nursing and Midwifery | Home', 'Premier healthcare education institution in Uganda', NULL, NULL, NULL, NULL, NULL, NULL, 'website', 'en_US', 'summary_large_image', NULL, NULL, 1, 0, 0, 1, 0, NULL, NULL, NULL, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(2, 'about', 'About Us', NULL, 'static', 'default', 'About ISNM', 'Excellence in Healthcare Education Since 1997', NULL, 'rgba(26,35,126,0.7)', NULL, NULL, 'About Us | Iganga School of Nursing and Midwifery', 'Learn about our history, mission, vision, and values', NULL, NULL, NULL, NULL, NULL, NULL, 'website', 'en_US', 'summary_large_image', NULL, NULL, 1, 0, 0, 2, 0, NULL, NULL, NULL, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(3, 'history', 'Our History', NULL, 'static', 'default', 'Our History', 'A Legacy of Healthcare Excellence', NULL, 'rgba(26,35,126,0.7)', NULL, NULL, 'Our History | Iganga School of Nursing and Midwifery', 'The rich history of ISNM since 1997', NULL, NULL, NULL, NULL, NULL, NULL, 'website', 'en_US', 'summary_large_image', NULL, NULL, 1, 0, 0, 3, 0, NULL, NULL, NULL, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(4, 'programs', 'Academic Programs', NULL, 'static', 'default', 'Academic Programs', 'Comprehensive Healthcare Education Programs', NULL, 'rgba(26,35,126,0.7)', NULL, NULL, 'Academic Programs | Iganga School of Nursing and Midwifery', 'Explore our Certificate, Diploma, and Degree programs', NULL, NULL, NULL, NULL, NULL, NULL, 'website', 'en_US', 'summary_large_image', NULL, NULL, 1, 0, 0, 4, 0, NULL, NULL, NULL, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(5, 'news', 'News & Events', NULL, 'dynamic', 'default', 'News & Events', 'Stay Updated with ISNM', NULL, 'rgba(26,35,126,0.7)', NULL, NULL, 'News & Events | Iganga School of Nursing and Midwifery', 'Latest news, events, and announcements from ISNM', NULL, NULL, NULL, NULL, NULL, NULL, 'website', 'en_US', 'summary_large_image', NULL, NULL, 1, 0, 0, 5, 0, NULL, NULL, NULL, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(6, 'contact', 'Contact Us', NULL, 'static', 'default', 'Contact Us', 'Get in Touch with ISNM', NULL, 'rgba(26,35,126,0.7)', NULL, NULL, 'Contact Us | Iganga School of Nursing and Midwifery', 'Contact information, map, and inquiry form', NULL, NULL, NULL, NULL, NULL, NULL, 'website', 'en_US', 'summary_large_image', NULL, NULL, 1, 0, 0, 6, 0, NULL, NULL, NULL, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(7, 'donate', 'Donate', NULL, 'static', 'default', 'Support ISNM', 'Your Donation Transforms Healthcare Education', NULL, 'rgba(26,35,126,0.7)', NULL, NULL, 'Donate | Iganga School of Nursing and Midwifery', 'Support nursing education in Uganda through donations', NULL, NULL, NULL, NULL, NULL, NULL, 'website', 'en_US', 'summary_large_image', NULL, NULL, 1, 0, 0, 7, 0, NULL, NULL, NULL, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(8, 'volunteer', 'Volunteer', NULL, 'static', 'default', 'Volunteer With Us', 'Make a Difference in Healthcare Education', NULL, 'rgba(26,35,126,0.7)', NULL, NULL, 'Volunteer | Iganga School of Nursing and Midwifery', 'Volunteer opportunities at Iganga School of Nursing', NULL, NULL, NULL, NULL, NULL, NULL, 'website', 'en_US', 'summary_large_image', NULL, NULL, 1, 0, 0, 8, 0, NULL, NULL, NULL, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(9, 'application', 'Apply Now', NULL, 'static', 'default', 'Apply to ISNM', 'Start Your Healthcare Career Today', NULL, 'rgba(26,35,126,0.7)', NULL, NULL, 'Apply Now | Iganga School of Nursing and Midwifery', 'Submit your application to Iganga School of Nursing', NULL, NULL, NULL, NULL, NULL, NULL, 'website', 'en_US', 'summary_large_image', NULL, NULL, 1, 0, 0, 9, 0, NULL, NULL, NULL, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(10, 'portal', 'Student Portal', NULL, 'dynamic', 'default', 'Student Portal', 'Access Your Academic Dashboard', NULL, 'rgba(26,35,126,0.7)', NULL, NULL, 'Student Portal | Iganga School of Nursing and Midwifery', 'Student login portal for academic resources', NULL, NULL, NULL, NULL, NULL, NULL, 'website', 'en_US', 'summary_large_image', NULL, NULL, 1, 0, 0, 10, 0, NULL, NULL, NULL, '2026-07-14 07:12:48', '2026-07-14 07:12:48');

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
(1, 'Director General', 'manage_all', NULL, NULL, 1, 1, 1, 1, 1, 1, 0, '2026-07-14 07:12:48'),
(2, 'CEO', 'edit_homepage', 'about', 'page', 0, 1, 0, 1, 0, 1, 0, '2026-07-14 07:12:48'),
(3, 'CEO', 'edit_ceo_message', 'about', 'content_block', 0, 1, 0, 0, 0, 1, 1, '2026-07-14 07:12:48'),
(4, 'Director Academics', 'manage_programs', 'programs', 'page', 1, 1, 0, 1, 0, 1, 1, '2026-07-14 07:12:48'),
(5, 'Director Academics', 'manage_news', NULL, 'news', 1, 1, 0, 1, 0, 1, 1, '2026-07-14 07:12:48'),
(6, 'School Principal', 'edit_principal_message', 'about', 'content_block', 0, 1, 0, 0, 0, 1, 1, '2026-07-14 07:12:48'),
(7, 'School Principal', 'manage_announcements', NULL, 'announcement', 1, 1, 1, 1, 0, 1, 0, '2026-07-14 07:12:48'),
(8, 'Director Finance', 'edit_tuition', 'programs', 'content_block', 0, 1, 0, 0, 0, 1, 1, '2026-07-14 07:12:48'),
(9, 'Director Finance', 'manage_donations', 'donate', 'page', 0, 1, 0, 1, 0, 1, 0, '2026-07-14 07:12:48'),
(10, 'School Bursar', 'edit_payment_info', 'donate', 'content_block', 0, 1, 0, 0, 0, 1, 1, '2026-07-14 07:12:48'),
(11, 'Director Admissions', 'manage_admissions', 'application', 'page', 1, 1, 0, 1, 0, 1, 1, '2026-07-14 07:12:48'),
(12, 'Academic Registrar', 'edit_registration', 'programs', 'content_block', 0, 1, 0, 0, 0, 1, 1, '2026-07-14 07:12:48'),
(13, 'HR Manager', 'manage_careers', 'contact', 'content_block', 1, 1, 1, 1, 0, 1, 0, '2026-07-14 07:12:48'),
(14, 'School Secretary', 'manage_notices', NULL, 'announcement', 1, 1, 0, 1, 0, 1, 0, '2026-07-14 07:12:48'),
(15, 'School Librarian', 'edit_library', 'about', 'content_block', 0, 1, 0, 0, 0, 1, 1, '2026-07-14 07:12:48'),
(16, 'Events Coordinator', 'manage_events', NULL, 'event', 1, 1, 1, 1, 0, 1, 0, '2026-07-14 07:12:48'),
(17, 'Events Coordinator', 'manage_gallery', NULL, 'gallery', 1, 1, 1, 1, 0, 1, 0, '2026-07-14 07:12:48'),
(18, 'Director ICT', 'manage_website_settings', NULL, 'setting', 1, 1, 0, 1, 0, 1, 0, '2026-07-14 07:12:48'),
(19, 'Director ICT', 'manage_banners', NULL, 'banner', 1, 1, 1, 1, 0, 1, 0, '2026-07-14 07:12:48'),
(20, 'Director ICT', 'manage_media', NULL, 'media', 1, 1, 1, 1, 0, 1, 0, '2026-07-14 07:12:48');

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
(1, 'general', 'school_name', 'Iganga School of Nursing and Midwifery', 'text', 'School Name', NULL, 1, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(2, 'general', 'school_motto', 'Quality Healthcare Education', 'text', 'School Motto', NULL, 2, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(3, 'general', 'school_tagline', 'Training Competent and Caring Healthcare Professionals', 'text', 'Tagline', NULL, 3, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(4, 'general', 'school_email', 'info@igangaschoolofnursing.ac.ug', 'text', 'Primary Email', NULL, 4, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(5, 'general', 'school_phone', '+256 700 123 456', 'text', 'Primary Phone', NULL, 5, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(6, 'general', 'school_address', 'Iganga, Uganda', 'text', 'Address', NULL, 6, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(7, 'general', 'school_pobox', 'P.O Box 123, Iganga', 'text', 'P.O. Box', NULL, 7, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(8, 'contact', 'admissions_email', 'admissions@igangaschoolofnursing.ac.ug', 'text', 'Admissions Email', NULL, 10, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(9, 'contact', 'bursar_email', 'bursar@igangaschoolofnursing.ac.ug', 'text', 'Bursar Email', NULL, 11, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(10, 'contact', 'principal_email', 'principal@igangaschoolofnursing.ac.ug', 'text', 'Principal Email', NULL, 12, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(11, 'contact', 'ict_email', 'ict@igangaschoolofnursing.ac.ug', 'text', 'ICT Email', NULL, 13, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(12, 'contact', 'emergency_phone', '+256 700 987 654', 'text', 'Emergency Phone', NULL, 14, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(13, 'seo', 'meta_title_suffix', '| Iganga School of Nursing and Midwifery', 'text', 'Title Suffix', NULL, 20, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(14, 'seo', 'default_meta_description', 'Iganga School of Nursing and Midwifery - Premier healthcare education institution in Uganda, training competent nurses and midwives.', 'textarea', 'Default Meta Description', NULL, 21, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(15, 'seo', 'google_analytics_id', '', 'text', 'Google Analytics ID', NULL, 22, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(16, 'seo', 'google_search_console', '', 'text', 'Search Console Verification', NULL, 23, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(17, 'social', 'facebook_url', 'https://facebook.com/igangaschoolofnursing', 'text', 'Facebook URL', NULL, 30, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(18, 'social', 'twitter_url', 'https://twitter.com/isnm_ug', 'text', 'Twitter URL', NULL, 31, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(19, 'social', 'instagram_url', 'https://instagram.com/isnm_ug', 'text', 'Instagram URL', NULL, 32, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(20, 'social', 'linkedin_url', 'https://linkedin.com/company/isnm', 'text', 'LinkedIn URL', NULL, 33, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(21, 'social', 'youtube_url', '', 'text', 'YouTube URL', NULL, 34, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(22, 'social', 'whatsapp_number', '+256700123456', 'text', 'WhatsApp Number', NULL, 35, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(23, 'homepage', 'hero_animation', 'fade', 'text', 'Hero Animation Style', NULL, 40, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(24, 'homepage', 'stats_counter_enabled', '1', 'boolean', 'Show Stats Counter', NULL, 41, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(25, 'homepage', 'testimonials_enabled', '1', 'boolean', 'Show Testimonials', NULL, 42, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(26, 'homepage', 'partners_enabled', '1', 'boolean', 'Show Partners', NULL, 43, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(27, 'homepage', 'gallery_preview_enabled', '1', 'boolean', 'Show Gallery Preview', NULL, 44, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(28, 'footer', 'developer_name', 'Reagan Otema', 'text', 'Developer Name', NULL, 50, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(29, 'footer', 'developer_url', 'https://reaganotema.com', 'text', 'Developer URL', NULL, 51, '2026-07-14 07:12:48', '2026-07-14 07:12:48'),
(30, 'footer', 'copyright_text', 'Iganga School of Nursing and Midwifery. All Rights Reserved.', 'text', 'Copyright Text', NULL, 52, '2026-07-14 07:12:48', '2026-07-14 07:12:48');

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
(1, 'facebook', 'https://facebook.com/igangaschoolofnursing', 'fab fa-facebook-f', 1, 1, '2026-07-14 07:12:48'),
(2, 'twitter', 'https://twitter.com/isnm_ug', 'fab fa-twitter', 1, 2, '2026-07-14 07:12:48'),
(3, 'instagram', 'https://instagram.com/isnm_ug', 'fab fa-instagram', 1, 3, '2026-07-14 07:12:48'),
(4, 'linkedin', 'https://linkedin.com/company/isnm', 'fab fa-linkedin-in', 1, 4, '2026-07-14 07:12:48'),
(5, 'youtube', '', 'fab fa-youtube', 0, 5, '2026-07-14 07:12:48'),
(6, 'whatsapp', 'https://wa.me/256700123456', 'fab fa-whatsapp', 1, 6, '2026-07-14 07:12:48');

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
(1, 'Sarah Nambogo', 'Registered Nurse, Mulago Hospital', NULL, 'alumni', 'ISNM gave me the foundation I needed to become a competent nurse. The clinical training and dedicated faculty prepared me for the real healthcare challenges.', 5, 1, 1, 1, '2026-07-14 07:12:48'),
(2, 'James Ochieng', 'Midwife, Iganga Health Center IV', NULL, 'alumni', 'The Certificate in Midwifery program at ISNM was transformative. I now serve my community with confidence and professional expertise.', 5, 1, 1, 2, '2026-07-14 07:12:48'),
(3, 'Grace Nakamya', 'Student, Diploma in Nursing', NULL, 'student', 'Choosing ISNM was the best decision of my life. The modern facilities, experienced lecturers, and supportive learning environment make every day worthwhile.', 5, 1, 1, 3, '2026-07-14 07:12:48'),
(4, 'Dr. Moses Wambamba', 'Medical Director, Iganga Hospital', NULL, 'partner', 'ISNM graduates consistently demonstrate clinical excellence and compassionate care. We are proud to partner with this exceptional institution.', 5, 1, 1, 4, '2026-07-14 07:12:48');

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
-- Table structure for table `computer_repairs`
--

CREATE TABLE `computer_repairs` (
  `id` int(11) NOT NULL,
  `repair_number` varchar(50) NOT NULL,
  `computer_id` int(11) DEFAULT NULL,
  `equipment_id` int(11) DEFAULT NULL,
  `reported_by` varchar(200) NOT NULL,
  `reporter_type` enum('student','staff','lecturer') DEFAULT 'student',
  `reporter_id` int(11) DEFAULT NULL,
  `issue_description` text NOT NULL,
  `issue_category` enum('hardware','software','network','other') DEFAULT 'other',
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `assigned_technician` varchar(200) DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `resolution` text DEFAULT NULL,
  `parts_replaced` text DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT 0.00,
  `status` enum('reported','diagnosed','in_progress','completed','closed','cancelled') DEFAULT 'reported',
  `reported_date` timestamp NULL DEFAULT current_timestamp(),
  `diagnosed_date` timestamp NULL DEFAULT NULL,
  `completed_date` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `course_catalog`
--

CREATE TABLE `course_catalog` (
  `id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `credit_hours` decimal(4,1) DEFAULT 0.0,
  `is_compulsory` tinyint(1) NOT NULL DEFAULT 0,
  `department` varchar(100) DEFAULT NULL,
  `level` varchar(50) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 'gpa_max', '4.00', 'Maximum GPA', 0, '2026-07-12 10:05:08'),
(2, 'pass_mark', '50', 'Minimum passing percentage', 0, '2026-07-12 10:05:08'),
(3, 'auto_gpa', '1', 'Auto-calculate GPA', 0, '2026-07-12 10:05:08');

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
  `program_id` int(11) DEFAULT 0,
  `academic_year` varchar(20) DEFAULT NULL,
  `graduation_year` varchar(20) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `remarks` text DEFAULT NULL,
  `submitted_by` int(11) DEFAULT 0,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ict_assets`
--

CREATE TABLE `ict_assets` (
  `id` int(11) NOT NULL,
  `asset_number` varchar(100) NOT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `serial_number` varchar(255) DEFAULT NULL,
  `asset_name` varchar(200) NOT NULL,
  `asset_type` enum('computer','printer','scanner','projector','network','server','ups','software','accessory','other') DEFAULT 'other',
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `current_status` enum('active','in_maintenance','retired','transferred') DEFAULT 'active',
  `assigned_staff_id` int(11) DEFAULT NULL,
  `assigned_department` varchar(200) DEFAULT NULL,
  `current_location` varchar(255) DEFAULT NULL,
  `purchase_cost` decimal(15,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ict_asset_assignments`
--

CREATE TABLE `ict_asset_assignments` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `assigned_to_staff_id` int(11) DEFAULT NULL,
  `assigned_department` varchar(200) DEFAULT NULL,
  `assignment_date` date NOT NULL,
  `expected_return_date` date DEFAULT NULL,
  `actual_return_date` date DEFAULT NULL,
  `assignment_notes` text DEFAULT NULL,
  `condition_at_assignment` varchar(200) DEFAULT NULL,
  `condition_at_return` varchar(200) DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `status` enum('active','returned','transferred') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ict_asset_categories`
--

CREATE TABLE `ict_asset_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ict_asset_categories`
--

INSERT INTO `ict_asset_categories` (`id`, `category_name`, `description`, `parent_id`, `created_at`, `updated_at`) VALUES
(1, 'Desktop Computers', 'Desktop workstations and PCs', NULL, '2026-06-26 19:25:21', '2026-06-26 19:25:21'),
(2, 'Laptops', 'Portable notebook computers', NULL, '2026-06-26 19:25:21', '2026-06-26 19:25:21'),
(3, 'Servers', 'Physical and virtual server systems', NULL, '2026-06-26 19:25:21', '2026-06-26 19:25:21'),
(4, 'Printers', 'All printer types including multi-function', NULL, '2026-06-26 19:25:21', '2026-06-26 19:25:21'),
(5, 'Scanners', 'Document and photo scanners', NULL, '2026-06-26 19:25:21', '2026-06-26 19:25:21'),
(6, 'Projectors', 'Multimedia projectors and displays', NULL, '2026-06-26 19:25:21', '2026-06-26 19:25:21'),
(7, 'Network Equipment', 'Routers, switches, access points', NULL, '2026-06-26 19:25:21', '2026-06-26 19:25:21'),
(8, 'UPS Systems', 'Uninterruptible power supplies', NULL, '2026-06-26 19:25:21', '2026-06-26 19:25:21'),
(9, 'Software', 'Licensed software packages', NULL, '2026-06-26 19:25:21', '2026-06-26 19:25:21'),
(10, 'Accessories', 'Peripherals and accessories', NULL, '2026-06-26 19:25:21', '2026-06-26 19:25:21');

-- --------------------------------------------------------

--
-- Table structure for table `ict_asset_maintenance`
--

CREATE TABLE `ict_asset_maintenance` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `maintenance_type` enum('routine','repair','upgrade','cleaning','other') DEFAULT 'routine',
  `description` text NOT NULL,
  `performed_by` varchar(200) DEFAULT NULL,
  `cost` decimal(15,2) DEFAULT 0.00,
  `parts_replaced` text DEFAULT NULL,
  `service_provider` varchar(200) DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
  `scheduled_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ict_asset_warranty`
--

CREATE TABLE `ict_asset_warranty` (
  `id` int(11) NOT NULL,
  `asset_id` int(11) NOT NULL,
  `warranty_provider` varchar(200) DEFAULT NULL,
  `warranty_type` enum('standard','extended','onsite','carry_in') DEFAULT 'standard',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `coverage_details` text DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `status` enum('active','expired','claimed') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ict_audit_logs`
--

CREATE TABLE `ict_audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `resource_type` varchar(100) DEFAULT NULL,
  `resource_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ict_backup_logs`
--

CREATE TABLE `ict_backup_logs` (
  `id` int(11) NOT NULL,
  `backup_id` int(11) DEFAULT NULL,
  `log_message` text NOT NULL,
  `log_level` enum('info','warning','error') DEFAULT 'info',
  `logged_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ict_device_categories`
--

CREATE TABLE `ict_device_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(200) NOT NULL,
  `device_type` enum('computer','printer','scanner','projector','network','server','ups','accessory','other') DEFAULT 'other',
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ict_device_categories`
--

INSERT INTO `ict_device_categories` (`id`, `category_name`, `device_type`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Desktop Computers', 'computer', 'Desktop workstations', '2026-06-26 19:25:21', '2026-06-26 19:25:21'),
(2, 'Laptops', 'computer', 'Portable computers', '2026-06-26 19:25:21', '2026-06-26 19:25:21'),
(3, 'Network Printers', 'printer', 'Network-connected printers', '2026-06-26 19:25:21', '2026-06-26 19:25:21'),
(4, 'Scanners', 'scanner', 'Document scanners', '2026-06-26 19:25:21', '2026-06-26 19:25:21'),
(5, 'Projectors', 'projector', 'Multimedia projectors', '2026-06-26 19:25:21', '2026-06-26 19:25:21'),
(6, 'Network Switches', 'network', 'Managed and unmanaged switches', '2026-06-26 19:25:21', '2026-06-26 19:25:21'),
(7, 'Routers', 'network', 'Network routers', '2026-06-26 19:25:21', '2026-06-26 19:25:21'),
(8, 'Access Points', 'network', 'Wireless access points', '2026-06-26 19:25:21', '2026-06-26 19:25:21'),
(9, 'Servers', 'server', 'Server systems', '2026-06-26 19:25:21', '2026-06-26 19:25:21'),
(10, 'UPS', 'ups', 'Power backup units', '2026-06-26 19:25:21', '2026-06-26 19:25:21');

-- --------------------------------------------------------

--
-- Table structure for table `ict_failed_logins`
--

CREATE TABLE `ict_failed_logins` (
  `id` int(11) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `attempted_at` timestamp NULL DEFAULT current_timestamp(),
  `reason` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ict_login_sessions`
--

CREATE TABLE `ict_login_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_type` enum('staff','student') DEFAULT 'staff',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `login_at` timestamp NULL DEFAULT current_timestamp(),
  `last_activity` timestamp NULL DEFAULT NULL,
  `logout_at` timestamp NULL DEFAULT NULL,
  `session_duration_sec` int(11) DEFAULT 0,
  `status` enum('active','expired','terminated') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ict_module_permissions`
--

CREATE TABLE `ict_module_permissions` (
  `id` int(11) NOT NULL,
  `module_name` varchar(100) NOT NULL,
  `role_keyword` varchar(50) NOT NULL,
  `can_view` tinyint(1) DEFAULT 1,
  `can_create` tinyint(1) DEFAULT 0,
  `can_edit` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0,
  `can_approve` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ict_network_logs`
--

CREATE TABLE `ict_network_logs` (
  `id` int(11) NOT NULL,
  `device_id` int(11) DEFAULT NULL,
  `log_type` enum('status_change','error','performance','security','config_change') DEFAULT 'status_change',
  `message` text NOT NULL,
  `severity` enum('info','warning','error','critical') DEFAULT 'info',
  `logged_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ict_security_logs`
--

CREATE TABLE `ict_security_logs` (
  `id` int(11) NOT NULL,
  `event_type` enum('login','logout','failed_login','permission_change','account_lock','password_change','user_create','user_delete','settings_change','other') NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `severity` enum('info','warning','critical') DEFAULT 'info',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ict_servers`
--

CREATE TABLE `ict_servers` (
  `id` int(11) NOT NULL,
  `server_name` varchar(200) NOT NULL,
  `hostname` varchar(200) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `server_type` enum('physical','virtual','cloud') DEFAULT 'physical',
  `os` varchar(100) DEFAULT NULL,
  `os_version` varchar(100) DEFAULT NULL,
  `cpu_cores` int(11) DEFAULT 0,
  `ram_gb` int(11) DEFAULT 0,
  `storage_gb` int(11) DEFAULT 0,
  `purpose` text DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `status` enum('online','offline','maintenance','decommissioned') DEFAULT 'online',
  `uptime_hours` int(11) DEFAULT 0,
  `last_reboot` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ict_system_alerts`
--

CREATE TABLE `ict_system_alerts` (
  `id` int(11) NOT NULL,
  `alert_type` enum('system','security','backup','performance','network','storage') NOT NULL,
  `severity` enum('info','warning','critical') DEFAULT 'info',
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `acknowledged_by` int(11) DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `status` enum('active','acknowledged','resolved') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ict_system_backups`
--

CREATE TABLE `ict_system_backups` (
  `id` int(11) NOT NULL,
  `backup_name` varchar(200) NOT NULL,
  `backup_type` enum('database','file','full','incremental') DEFAULT 'database',
  `target_database` varchar(100) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size_mb` decimal(15,2) DEFAULT 0.00,
  `checksum` varchar(64) DEFAULT NULL,
  `status` enum('running','completed','failed','verified') DEFAULT 'running',
  `initiated_by` int(11) DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ict_system_health`
--

CREATE TABLE `ict_system_health` (
  `id` int(11) NOT NULL,
  `check_type` enum('cpu','memory','disk','network','database','service') NOT NULL,
  `check_name` varchar(200) DEFAULT NULL,
  `status` enum('healthy','warning','critical','unknown') DEFAULT 'healthy',
  `value` varchar(255) DEFAULT NULL,
  `threshold` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `checked_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ict_system_notifications`
--

CREATE TABLE `ict_system_notifications` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `notification_type` enum('info','warning','critical','success') DEFAULT 'info',
  `category` varchar(100) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `is_dismissed` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ict_system_settings`
--

CREATE TABLE `ict_system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(100) DEFAULT 'general',
  `description` text DEFAULT NULL,
  `is_encrypted` tinyint(1) DEFAULT 0,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ict_system_settings`
--

INSERT INTO `ict_system_settings` (`id`, `setting_key`, `setting_value`, `setting_group`, `description`, `is_encrypted`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'session_timeout_minutes', '30', 'security', 'User session timeout in minutes', 0, NULL, '2026-06-26 19:25:21', '2026-06-26 19:25:21'),
(2, 'max_login_attempts', '5', 'security', 'Maximum failed login attempts before lockout', 0, NULL, '2026-06-26 19:25:21', '2026-06-26 19:25:21'),
(3, 'lockout_duration_minutes', '15', 'security', 'Account lockout duration in minutes', 0, NULL, '2026-06-26 19:25:21', '2026-06-26 19:25:21'),
(4, 'password_min_length', '8', 'security', 'Minimum password length', 0, NULL, '2026-06-26 19:25:21', '2026-06-26 19:25:21'),
(5, 'backup_retention_days', '30', 'backup', 'Days to retain backups', 0, NULL, '2026-06-26 19:25:21', '2026-06-26 19:25:21'),
(6, 'auto_backup_enabled', 'true', 'backup', 'Enable automatic scheduled backups', 0, NULL, '2026-06-26 19:25:21', '2026-06-26 19:25:21'),
(7, 'backup_time', '02:00', 'backup', 'Scheduled backup time', 0, NULL, '2026-06-26 19:25:21', '2026-06-26 19:25:21'),
(8, 'system_health_interval', '5', 'monitoring', 'System health check interval in minutes', 0, NULL, '2026-06-26 19:25:21', '2026-06-26 19:25:21'),
(9, 'notify_critical_alerts', 'true', 'alerts', 'Send notifications for critical alerts', 0, NULL, '2026-06-26 19:25:21', '2026-06-26 19:25:21'),
(10, 'maintenance_mode', 'false', 'system', 'System maintenance mode flag', 0, NULL, '2026-06-26 19:25:21', '2026-06-26 19:25:21');

-- --------------------------------------------------------

--
-- Table structure for table `ict_wifi_devices`
--

CREATE TABLE `ict_wifi_devices` (
  `id` int(11) NOT NULL,
  `device_name` varchar(200) NOT NULL,
  `ssid` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `mac_address` varchar(17) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `firmware_version` varchar(50) DEFAULT NULL,
  `status` enum('online','offline','maintenance') DEFAULT 'online',
  `connected_clients` int(11) DEFAULT 0,
  `max_clients` int(11) DEFAULT 50,
  `band` enum('2.4ghz','5ghz','dual') DEFAULT 'dual',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `id_card_print_history`
--

CREATE TABLE `id_card_print_history` (
  `id` int(11) NOT NULL,
  `card_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `print_type` enum('new','reprint','bulk') DEFAULT 'new',
  `reason` varchar(200) DEFAULT NULL,
  `printed_by` int(11) DEFAULT NULL,
  `print_date` timestamp NULL DEFAULT current_timestamp(),
  `copies` int(11) DEFAULT 1,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `id_card_replacements`
--

CREATE TABLE `id_card_replacements` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `original_card_id` int(11) DEFAULT NULL,
  `reason` enum('lost','damaged','stolen','name_change','info_update','other') NOT NULL,
  `description` text DEFAULT NULL,
  `charge_amount` decimal(10,2) DEFAULT 0.00,
  `payment_status` enum('pending','paid','waived') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `replacement_date` timestamp NULL DEFAULT current_timestamp(),
  `new_card_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','completed','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Table structure for table `it_support_tickets`
--

CREATE TABLE `it_support_tickets` (
  `id` int(11) NOT NULL,
  `ticket_number` varchar(50) NOT NULL,
  `requester_name` varchar(100) NOT NULL,
  `requester_email` varchar(100) DEFAULT NULL,
  `requester_type` enum('student','staff','faculty') NOT NULL,
  `issue_type` enum('hardware','software','network','account','other') NOT NULL,
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `description` text NOT NULL,
  `status` enum('open','in_progress','resolved','closed') DEFAULT 'open',
  `assigned_to` int(11) DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `it_support_tickets`
--

INSERT INTO `it_support_tickets` (`id`, `ticket_number`, `requester_name`, `requester_email`, `requester_type`, `issue_type`, `priority`, `description`, `status`, `assigned_to`, `resolution_notes`, `resolved_at`, `created_at`, `updated_at`) VALUES
(1, 'TKT-2024-001', 'John Mugisha', 'jmugisha@student.isnm.ac.ug', 'student', 'software', 'medium', 'Unable to access SPSS software on Lab A computers', 'open', NULL, NULL, NULL, '2026-06-14 18:38:56', '2026-06-14 18:38:56'),
(2, 'TKT-2024-002', 'Dr. Emily Achieng', 'eachieng@isnm.ac.ug', 'staff', 'hardware', 'high', 'Projector in Lab B not displaying properly', 'in_progress', NULL, NULL, NULL, '2026-06-14 18:38:56', '2026-06-14 18:38:56'),
(3, 'TKT-2024-003', 'Peter Kato', 'pkato@student.isnm.ac.ug', 'student', 'account', 'low', 'Forgot password for student portal', 'open', NULL, NULL, NULL, '2026-06-14 18:38:56', '2026-06-14 18:38:56'),
(4, 'TKT-2024-004', 'Ms. Ruth Akello', 'rakello@isnm.ac.ug', 'staff', 'network', 'critical', 'WiFi connection dropping frequently in Lab A', 'open', NULL, NULL, NULL, '2026-06-14 18:38:56', '2026-06-14 18:38:56');

-- --------------------------------------------------------

--
-- Table structure for table `lab_asset_assignments`
--

CREATE TABLE `lab_asset_assignments` (
  `id` int(11) NOT NULL,
  `asset_type` enum('computer','equipment','accessory') NOT NULL,
  `asset_id` int(11) NOT NULL,
  `assigned_to_type` enum('student','staff','lecturer','lab') DEFAULT 'lab',
  `assigned_to_id` int(11) DEFAULT NULL,
  `lab_room_id` int(11) DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `assigned_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `status` enum('active','returned','transferred') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_attendance`
--

CREATE TABLE `lab_attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `lab_room_id` int(11) DEFAULT NULL,
  `session_id` int(11) DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `computer_id` int(11) DEFAULT NULL,
  `seat_number` varchar(20) DEFAULT NULL,
  `status` enum('present','absent','late','excused') DEFAULT 'present',
  `marked_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_bookings`
--

CREATE TABLE `lab_bookings` (
  `id` int(11) NOT NULL,
  `booking_reference` varchar(50) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `instructor_name` varchar(100) NOT NULL,
  `instructor_email` varchar(100) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `time_slot` varchar(50) NOT NULL,
  `number_of_students` int(11) NOT NULL,
  `purpose` text DEFAULT NULL,
  `special_requirements` text DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `lab_assigned` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `lab_room_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lab_bookings`
--

INSERT INTO `lab_bookings` (`id`, `booking_reference`, `course_name`, `instructor_name`, `instructor_email`, `booking_date`, `time_slot`, `number_of_students`, `purpose`, `special_requirements`, `status`, `approved_by`, `lab_assigned`, `created_at`, `updated_at`, `lab_room_id`, `user_id`, `semester`) VALUES
(1, 'BK-2024-001', 'Introduction to Nursing Informatics', 'Dr. Sarah Johnson', 'sjohnson@isnm.ac.ug', '2024-06-10', '09:00 AM - 11:00 AM', 25, 'Practical session on electronic health records', NULL, 'confirmed', NULL, 'Lab A', '2026-06-14 18:38:55', '2026-06-14 18:38:55', NULL, NULL, NULL),
(2, 'BK-2024-002', 'Research Methods', 'Prof. Michael Okonkwo', 'mokonkwo@isnm.ac.ug', '2024-06-10', '02:00 PM - 04:00 PM', 30, 'Data analysis using SPSS', NULL, 'pending', NULL, 'Lab B', '2026-06-14 18:38:55', '2026-06-14 18:38:55', NULL, NULL, NULL),
(3, 'BK-2024-003', 'Computer Literacy', 'Ms. Grace Namukasa', 'gnamukasa@isnm.ac.ug', '2024-06-11', '09:00 AM - 11:00 AM', 20, 'Basic computer skills training', NULL, 'confirmed', NULL, 'Lab A', '2026-06-14 18:38:55', '2026-06-14 18:38:55', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `lab_computers`
--

CREATE TABLE `lab_computers` (
  `id` int(11) NOT NULL,
  `computer_id` varchar(50) NOT NULL,
  `computer_name` varchar(100) NOT NULL,
  `lab_name` varchar(100) DEFAULT NULL,
  `location` varchar(100) NOT NULL,
  `status` enum('online','offline','maintenance','deleted') DEFAULT 'online',
  `ip_address` varchar(45) DEFAULT NULL,
  `mac_address` varchar(17) DEFAULT NULL,
  `specifications` text DEFAULT NULL,
  `os_installed` varchar(100) DEFAULT NULL,
  `operating_system` varchar(100) DEFAULT NULL,
  `last_maintenance` date DEFAULT NULL,
  `next_maintenance` date DEFAULT NULL,
  `issues_reported` text DEFAULT NULL,
  `assigned_to` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lab_computers`
--

INSERT INTO `lab_computers` (`id`, `computer_id`, `computer_name`, `lab_name`, `location`, `status`, `ip_address`, `mac_address`, `specifications`, `os_installed`, `operating_system`, `last_maintenance`, `next_maintenance`, `issues_reported`, `assigned_to`, `purchase_date`, `warranty_expiry`, `created_at`, `updated_at`) VALUES
(1, 'LAB-A-001', 'Computer Lab A - Station 1', NULL, 'Lab A - Floor 1', 'online', '192.168.1.101', 'AA:BB:CC:DD:EE:01', 'Intel i5, 8GB RAM, 256GB SSD', 'Windows 11 Pro', NULL, '2024-05-01', '2024-08-01', NULL, NULL, NULL, NULL, '2026-06-14 18:38:55', '2026-06-14 18:38:55'),
(2, 'LAB-A-002', 'Computer Lab A - Station 2', NULL, 'Lab A - Floor 1', 'online', '192.168.1.102', 'AA:BB:CC:DD:EE:02', 'Intel i5, 8GB RAM, 256GB SSD', 'Windows 11 Pro', NULL, '2024-05-01', '2024-08-01', NULL, NULL, NULL, NULL, '2026-06-14 18:38:55', '2026-06-14 18:38:55'),
(3, 'LAB-A-003', 'Computer Lab A - Station 3', NULL, 'Lab A - Floor 1', 'offline', '192.168.1.103', 'AA:BB:CC:DD:EE:03', 'Intel i5, 8GB RAM, 256GB SSD', 'Windows 11 Pro', NULL, '2024-05-01', '2024-08-01', 'Hardware issue - PSU replacement needed', NULL, NULL, NULL, '2026-06-14 18:38:55', '2026-06-14 18:38:55'),
(4, 'LAB-B-001', 'Computer Lab B - Station 1', NULL, 'Lab B - Floor 2', 'online', '192.168.2.101', 'BB:CC:DD:EE:FF:01', 'Intel i7, 16GB RAM, 512GB SSD', 'Windows 11 Pro', NULL, '2024-05-15', '2024-08-15', NULL, NULL, NULL, NULL, '2026-06-14 18:38:55', '2026-06-14 18:38:55'),
(5, 'LAB-B-002', 'Computer Lab B - Station 2', NULL, 'Lab B - Floor 2', 'maintenance', '192.168.2.102', 'BB:CC:DD:EE:FF:02', 'Intel i7, 16GB RAM, 512GB SSD', 'Windows 11 Pro', NULL, '2024-05-15', '2024-08-15', 'OS reinstallation in progress', NULL, NULL, NULL, '2026-06-14 18:38:55', '2026-06-14 18:38:55');

-- --------------------------------------------------------

--
-- Table structure for table `lab_computer_assignments`
--

CREATE TABLE `lab_computer_assignments` (
  `id` int(11) NOT NULL,
  `computer_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `assignment_type` enum('student','staff','lecturer') DEFAULT 'student',
  `assigned_by` int(11) DEFAULT NULL,
  `assigned_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `status` enum('active','returned','transferred') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_consumables`
--

CREATE TABLE `lab_consumables` (
  `id` int(11) NOT NULL,
  `item_name` varchar(200) NOT NULL,
  `item_category` enum('toner','ink','paper','cable','mouse','keyboard','usb','cd_dvd','other') DEFAULT 'other',
  `quantity` int(11) NOT NULL DEFAULT 0,
  `reorder_level` int(11) DEFAULT 5,
  `unit` varchar(50) DEFAULT 'pcs',
  `unit_cost` decimal(10,2) DEFAULT 0.00,
  `supplier` varchar(200) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `last_restocked` date DEFAULT NULL,
  `status` enum('in_stock','low_stock','out_of_stock') DEFAULT 'in_stock',
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
  `equipment_name` varchar(200) NOT NULL,
  `equipment_type` enum('computer','printer','scanner','projector','ups','accessory','other') DEFAULT 'other',
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `serial_number` varchar(200) DEFAULT NULL,
  `lab_room_id` int(11) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `condition_status` enum('excellent','good','fair','poor','faulty','retired') DEFAULT 'good',
  `status` enum('available','in_use','maintenance','retired') DEFAULT 'available',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_equipment_checkout`
--

CREATE TABLE `lab_equipment_checkout` (
  `id` int(11) NOT NULL,
  `equipment_id` int(11) NOT NULL,
  `checked_out_to` varchar(200) NOT NULL,
  `borrower_type` enum('student','staff','lecturer') DEFAULT 'student',
  `borrower_id` int(11) DEFAULT NULL,
  `checkout_date` datetime NOT NULL,
  `expected_return` datetime DEFAULT NULL,
  `actual_return` datetime DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `condition_at_checkout` varchar(200) DEFAULT NULL,
  `condition_at_return` varchar(200) DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `status` enum('checked_out','returned','overdue','lost','damaged') DEFAULT 'checked_out',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_practical_sessions`
--

CREATE TABLE `lab_practical_sessions` (
  `id` int(11) NOT NULL,
  `session_code` varchar(50) NOT NULL,
  `course_name` varchar(200) NOT NULL,
  `instructor_name` varchar(200) DEFAULT NULL,
  `instructor_id` int(11) DEFAULT NULL,
  `lab_room_id` int(11) DEFAULT NULL,
  `session_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `program` varchar(200) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `max_students` int(11) DEFAULT 0,
  `status` enum('scheduled','ongoing','completed','cancelled') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_rooms`
--

CREATE TABLE `lab_rooms` (
  `id` int(11) NOT NULL,
  `room_name` varchar(100) NOT NULL,
  `room_code` varchar(20) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 0,
  `computer_count` int(11) NOT NULL DEFAULT 0,
  `location` varchar(200) DEFAULT NULL,
  `status` enum('active','inactive','maintenance') DEFAULT 'active',
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lab_rooms`
--

INSERT INTO `lab_rooms` (`id`, `room_name`, `room_code`, `capacity`, `computer_count`, `location`, `status`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Computer Lab A', 'LAB-A', 40, 40, 'Main Building, Ground Floor', 'active', NULL, '2026-06-26 19:08:42', '2026-06-26 19:08:42'),
(2, 'Computer Lab B', 'LAB-B', 30, 30, 'Main Building, Ground Floor', 'active', NULL, '2026-06-26 19:08:42', '2026-06-26 19:08:42'),
(3, 'Computer Lab C', 'LAB-C', 25, 25, 'Main Building, First Floor', 'active', NULL, '2026-06-26 19:08:42', '2026-06-26 19:08:42'),
(4, 'Computer Lab D', 'LAB-D', 20, 20, 'Main Building, First Floor', 'active', NULL, '2026-06-26 19:08:42', '2026-06-26 19:08:42'),
(5, 'Skills Lab', 'SKILLS-1', 15, 15, 'Clinical Building', 'active', NULL, '2026-06-26 19:08:42', '2026-06-26 19:08:42');

-- --------------------------------------------------------

--
-- Table structure for table `lab_usage_stats`
--

CREATE TABLE `lab_usage_stats` (
  `id` int(11) NOT NULL,
  `lab_name` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `total_sessions` int(11) DEFAULT 0,
  `total_users` int(11) DEFAULT 0,
  `peak_concurrent_users` int(11) DEFAULT 0,
  `average_session_duration` int(11) DEFAULT 0,
  `computers_used` int(11) DEFAULT 0,
  `computers_available` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lab_usage_stats`
--

INSERT INTO `lab_usage_stats` (`id`, `lab_name`, `date`, `total_sessions`, `total_users`, `peak_concurrent_users`, `average_session_duration`, `computers_used`, `computers_available`, `notes`, `created_at`) VALUES
(1, 'Lab A', '2024-06-05', 8, 45, 25, 90, 22, 25, NULL, '2026-06-14 18:38:56'),
(2, 'Lab B', '2024-06-05', 6, 35, 20, 85, 18, 20, NULL, '2026-06-14 18:38:56'),
(3, 'Lab A', '2024-06-06', 10, 55, 28, 95, 24, 25, NULL, '2026-06-14 18:38:56'),
(4, 'Lab B', '2024-06-06', 7, 40, 22, 80, 19, 20, NULL, '2026-06-14 18:38:56');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_logs`
--

CREATE TABLE `maintenance_logs` (
  `id` int(11) NOT NULL,
  `computer_id` varchar(50) NOT NULL,
  `maintenance_type` enum('routine','repair','upgrade','cleaning') NOT NULL,
  `description` text NOT NULL,
  `performed_by` varchar(100) NOT NULL,
  `cost` decimal(10,2) DEFAULT 0.00,
  `parts_replaced` text DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
  `scheduled_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `maintenance_logs`
--

INSERT INTO `maintenance_logs` (`id`, `computer_id`, `maintenance_type`, `description`, `performed_by`, `cost`, `parts_replaced`, `status`, `scheduled_date`, `completed_date`, `created_at`, `updated_at`) VALUES
(1, 'LAB-A-003', 'repair', 'Power supply unit replacement required', 'IT Technician - James', 150.00, NULL, 'scheduled', '2024-06-12', NULL, '2026-06-14 18:38:56', '2026-06-14 18:38:56'),
(2, 'LAB-B-002', 'routine', 'Operating system reinstallation and updates', 'IT Technician - Sarah', 0.00, NULL, 'in_progress', '2024-06-10', NULL, '2026-06-14 18:38:56', '2026-06-14 18:38:56'),
(3, 'LAB-A-003', 'repair', 'Power supply unit replacement required', 'IT Technician - James', 150.00, NULL, 'scheduled', '2024-06-12', NULL, '2026-06-15 04:19:05', '2026-06-15 04:19:05'),
(4, 'LAB-B-002', 'routine', 'Operating system reinstallation and updates', 'IT Technician - Sarah', 0.00, NULL, 'in_progress', '2024-06-10', NULL, '2026-06-15 04:19:05', '2026-06-15 04:19:05');

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
(1, 'PARA001', 'Paracetamol', 'Acetaminophen', 'Painkiller', 'Tablet', '500mg', NULL, NULL, 200, 'tablets', 50, 50.00, NULL, 'UGX', NULL, '2027-12-31', 'Cabinet A1', 0, '1-2 tablets every 4-6 hours as needed for pain/fever', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:34', '2026-06-20 08:42:34'),
(2, 'IBU001', 'Ibuprofen', 'Ibuprofen', 'Anti-inflammatory', 'Tablet', '400mg', NULL, NULL, 150, 'tablets', 30, 100.00, NULL, 'UGX', NULL, '2027-10-31', 'Cabinet A1', 0, '1 tablet 3 times daily after meals', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:34', '2026-06-20 08:42:34'),
(3, 'AMOX001', 'Amoxicillin', 'Amoxicillin', 'Antibiotic', 'Capsule', '500mg', NULL, NULL, 100, 'capsules', 20, 200.00, NULL, 'UGX', NULL, '2027-08-31', 'Cabinet B1', 1, '1 capsule 3 times daily for 7 days', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:34', '2026-06-20 08:42:34'),
(4, 'CTM001', 'Chlorpheniramine', 'Chlorpheniramine Maleate', 'Allergy', 'Tablet', '4mg', NULL, NULL, 100, 'tablets', 20, 50.00, NULL, 'UGX', NULL, '2027-11-30', 'Cabinet A2', 0, '1 tablet every 4-6 hours for allergies', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:34', '2026-06-20 08:42:34'),
(5, 'ORS001', 'Oral Rehydration Salts', 'ORS', 'Other', 'Powder', '20.5g/sachet', NULL, NULL, 100, 'sachets', 30, 500.00, NULL, 'UGX', NULL, '2028-06-30', 'Cabinet C1', 0, 'Dissolve 1 sachet in 1L water, drink after each loose stool', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:34', '2026-06-20 08:42:34'),
(6, 'ART001', 'Artemether/Lumefantrine', 'Coartem', 'Antimalarial', 'Tablet', '20/120mg', NULL, NULL, 60, 'tablets', 20, 1500.00, NULL, 'UGX', NULL, '2027-09-30', 'Cabinet B2', 1, '4 tablets twice daily for 3 days', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:34', '2026-06-20 08:42:34'),
(7, 'VITC001', 'Vitamin C', 'Ascorbic Acid', 'Vitamins', 'Tablet', '500mg', NULL, NULL, 300, 'tablets', 50, 30.00, NULL, 'UGX', NULL, '2028-12-31', 'Cabinet C1', 0, '1 tablet daily for immune support', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:34', '2026-06-20 08:42:34'),
(8, 'MET001', 'Metered Dose Inhaler', 'Salbutamol', 'Respiratory', 'Inhaler', '100mcg/dose', NULL, NULL, 10, 'inhalers', 3, 15000.00, NULL, 'UGX', NULL, '2027-06-30', 'Cabinet A3', 1, '1-2 puffs as needed for asthma symptoms', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:34', '2026-06-20 08:42:34'),
(9, 'ANT001', 'Antacid', 'Aluminum/Magnesium Hydroxide', 'Digestive', 'Tablet', '500mg', NULL, NULL, 200, 'tablets', 40, 100.00, NULL, 'UGX', NULL, '2027-11-30', 'Cabinet C1', 0, '1-2 tablets after meals or when symptomatic', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:34', '2026-06-20 08:42:34'),
(10, 'HYD001', 'Hydrocortisone Cream', 'Hydrocortisone', 'Dermatological', 'Cream', '1%', NULL, NULL, 20, 'tubes', 5, 5000.00, NULL, 'UGX', NULL, '2027-08-31', 'Cabinet D1', 0, 'Apply thin layer to affected area 2-3 times daily', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:34', '2026-06-20 08:42:34'),
(11, 'DIA001', 'Diazepam', 'Diazepam', 'Painkiller', 'Tablet', '5mg', NULL, NULL, 30, 'tablets', 10, 200.00, NULL, 'UGX', NULL, '2026-12-31', 'Cabinet B2', 1, '1 tablet at bedtime for anxiety or muscle spasms', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:34', '2026-06-20 08:42:34'),
(12, 'BAN001', 'Bandages', 'Cotton Bandage', 'First Aid', 'Other', '4 inches x 5 meters', NULL, NULL, 50, 'rolls', 10, 1500.00, NULL, 'UGX', NULL, '2029-12-31', 'Shelf E1', 0, 'For wound dressing and injury management', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:34', '2026-06-20 08:42:34'),
(13, 'GAU001', 'Gauze Swabs', 'Sterile Gauze', 'First Aid', 'Other', '10x10cm', NULL, NULL, 200, 'packs', 50, 800.00, NULL, 'UGX', NULL, '2029-12-31', 'Shelf E1', 0, 'Sterile swabs for wound cleaning and dressing', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:34', '2026-06-20 08:42:34'),
(14, 'GLU001', 'Glucose Powder', 'Dextrose', 'Vitamins', 'Powder', '500g', NULL, NULL, 10, 'packs', 3, 5000.00, NULL, 'UGX', NULL, '2028-06-30', 'Cabinet C1', 0, 'Mix 2 tablespoons in water for energy', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:34', '2026-06-20 08:42:34'),
(15, 'ALC001', 'Alcohol Swabs', 'Isopropyl Alcohol', 'First Aid', 'Solution', '70%', NULL, NULL, 300, 'swabs', 50, 100.00, NULL, 'UGX', NULL, '2028-12-31', 'Shelf E1', 0, 'Use for cleaning skin before injections', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:34', '2026-06-20 08:42:34'),
(16, 'CLO001', 'Chloroquine', 'Chloroquine Phosphate', 'Antimalarial', 'Tablet', '250mg', NULL, NULL, 50, 'tablets', 15, 300.00, NULL, 'UGX', NULL, '2027-05-31', 'Cabinet B2', 1, 'As prescribed for malaria treatment', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:34', '2026-06-20 08:42:34'),
(17, 'MEF001', 'Mefenamic Acid', 'Mefenamic Acid', 'Painkiller', 'Capsule', '500mg', NULL, NULL, 80, 'capsules', 20, 200.00, NULL, 'UGX', NULL, '2027-07-31', 'Cabinet A1', 0, '1 capsule 3 times daily for pain and inflammation', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:34', '2026-06-20 08:42:34'),
(18, 'METR001', 'Metronidazole', 'Metronidazole', 'Antibiotic', 'Tablet', '400mg', NULL, NULL, 100, 'tablets', 20, 150.00, NULL, 'UGX', NULL, '2027-09-30', 'Cabinet B1', 1, '1 tablet 3 times daily for 5-7 days', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:34', '2026-06-20 08:42:34'),
(19, 'DIC001', 'Diclofenac Gel', 'Diclofenac Diethylamine', 'Anti-inflammatory', 'Cream', '1%', NULL, NULL, 15, 'tubes', 5, 7000.00, NULL, 'UGX', NULL, '2027-10-31', 'Cabinet D1', 0, 'Apply to affected area 3-4 times daily', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:34', '2026-06-20 08:42:34'),
(20, 'CET001', 'Cetirizine', 'Cetirizine Hydrochloride', 'Allergy', 'Tablet', '10mg', NULL, NULL, 100, 'tablets', 20, 100.00, NULL, 'UGX', NULL, '2027-12-31', 'Cabinet A2', 0, '1 tablet daily for allergy symptoms', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:34', '2026-06-20 08:42:34'),
(21, 'ASP001', 'Aspirin', 'Acetylsalicylic Acid', 'Painkiller', 'Tablet', '300mg', NULL, NULL, 100, 'tablets', 25, 50.00, NULL, 'UGX', NULL, '2027-06-30', 'Cabinet A1', 0, '1-2 tablets every 4-6 hours for pain/fever', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:34', '2026-06-20 08:42:34'),
(22, 'ZIN001', 'Zinc Tablets', 'Zinc Sulfate', 'Vitamins', 'Tablet', '20mg', NULL, NULL, 150, 'tablets', 30, 100.00, NULL, 'UGX', NULL, '2028-09-30', 'Cabinet C1', 0, '1 tablet daily for immune support and wound healing', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:34', '2026-06-20 08:42:34'),
(23, 'CLOT001', 'Clotrimazole Cream', 'Clotrimazole', 'Antifungal', 'Cream', '1%', NULL, NULL, 15, 'tubes', 5, 4000.00, NULL, 'UGX', NULL, '2027-08-31', 'Cabinet D1', 0, 'Apply to affected area twice daily for 2 weeks', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:34', '2026-06-20 08:42:34'),
(24, 'EYE001', 'Eye Drops', 'Chloramphenicol', 'Other', 'Drops', '0.5%', NULL, NULL, 20, 'bottles', 5, 5000.00, NULL, 'UGX', NULL, '2027-04-30', 'Cabinet A3', 1, '1-2 drops in affected eye every 2-4 hours', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:34', '2026-06-20 08:42:34'),
(25, 'BET001', 'Betadine Solution', 'Povidone-Iodine', 'First Aid', 'Solution', '10%', NULL, NULL, 10, 'bottles', 3, 8000.00, NULL, 'UGX', NULL, '2028-03-31', 'Shelf E1', 0, 'Apply to wounds for disinfection', NULL, 'In Stock', NULL, NULL, '2026-06-20 08:42:34', '2026-06-20 08:42:34');

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
-- Table structure for table `network_devices`
--

CREATE TABLE `network_devices` (
  `id` int(11) NOT NULL,
  `device_name` varchar(100) NOT NULL,
  `device_type` enum('router','switch','access_point','firewall','server','other') NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `mac_address` varchar(17) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `status` enum('online','offline','maintenance') DEFAULT 'online',
  `firmware_version` varchar(50) DEFAULT NULL,
  `last_check` timestamp NULL DEFAULT NULL,
  `uptime_hours` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `network_devices`
--

INSERT INTO `network_devices` (`id`, `device_name`, `device_type`, `ip_address`, `mac_address`, `location`, `status`, `firmware_version`, `last_check`, `uptime_hours`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Main Router', 'router', '192.168.0.1', '00:11:22:33:44:55', 'Server Room', 'online', 'v2.1.0', NULL, 720, NULL, '2026-06-14 18:38:56', '2026-06-14 18:38:56'),
(2, 'Lab A Switch', 'switch', '192.168.1.1', '00:11:22:33:44:56', 'Lab A - Floor 1', 'online', 'v1.5.2', NULL, 480, NULL, '2026-06-14 18:38:56', '2026-06-14 18:38:56'),
(3, 'Lab B Switch', 'switch', '192.168.2.1', '00:11:22:33:44:57', 'Lab B - Floor 2', 'online', 'v1.5.2', NULL, 480, NULL, '2026-06-14 18:38:56', '2026-06-14 18:38:56'),
(4, 'WiFi Access Point A', 'access_point', '192.168.0.10', '00:11:22:33:44:58', 'Lab A - Floor 1', 'online', 'v3.2.1', NULL, 240, NULL, '2026-06-14 18:38:56', '2026-06-14 18:38:56'),
(5, 'WiFi Access Point B', 'access_point', '192.168.0.11', '00:11:22:33:44:59', 'Lab B - Floor 2', 'offline', 'v3.2.1', NULL, 0, 'Needs repair', '2026-06-14 18:38:56', '2026-06-14 18:38:56'),
(6, 'Firewall', 'firewall', '192.168.0.2', '00:11:22:33:44:60', 'Server Room', 'online', 'v4.0.0', NULL, 720, NULL, '2026-06-14 18:38:56', '2026-06-14 18:38:56'),
(7, 'Main Router', 'router', '192.168.0.1', '00:11:22:33:44:55', 'Server Room', 'online', 'v2.1.0', NULL, 720, NULL, '2026-06-15 04:19:05', '2026-06-15 04:19:05'),
(8, 'Lab A Switch', 'switch', '192.168.1.1', '00:11:22:33:44:56', 'Lab A - Floor 1', 'online', 'v1.5.2', NULL, 480, NULL, '2026-06-15 04:19:05', '2026-06-15 04:19:05'),
(9, 'Lab B Switch', 'switch', '192.168.2.1', '00:11:22:33:44:57', 'Lab B - Floor 2', 'online', 'v1.5.2', NULL, 480, NULL, '2026-06-15 04:19:05', '2026-06-15 04:19:05'),
(10, 'WiFi Access Point A', 'access_point', '192.168.0.10', '00:11:22:33:44:58', 'Lab A - Floor 1', 'online', 'v3.2.1', NULL, 240, NULL, '2026-06-15 04:19:05', '2026-06-15 04:19:05'),
(11, 'WiFi Access Point B', 'access_point', '192.168.0.11', '00:11:22:33:44:59', 'Lab B - Floor 2', 'offline', 'v3.2.1', NULL, 0, 'Needs repair', '2026-06-15 04:19:05', '2026-06-15 04:19:05'),
(12, 'Firewall', 'firewall', '192.168.0.2', '00:11:22:33:44:60', 'Server Room', 'online', 'v4.0.0', NULL, 720, NULL, '2026-06-15 04:19:05', '2026-06-15 04:19:05');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL COMMENT 'application, contact, feedback, complaint, system',
  `title` varchar(255) NOT NULL,
  `message` longtext NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `from_email` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
  `response_code` int(11) DEFAULT 0,
  `response_body` longtext DEFAULT NULL,
  `processed` tinyint(1) DEFAULT 0,
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

-- --------------------------------------------------------

--
-- Table structure for table `payment_providers`
--

CREATE TABLE `payment_providers` (
  `id` int(11) NOT NULL,
  `provider_key` varchar(50) NOT NULL,
  `provider_name` varchar(100) NOT NULL,
  `provider_type` enum('mobile_money','card','bank','wallet','crypto') NOT NULL,
  `is_enabled` tinyint(1) DEFAULT 0,
  `merchant_id` varchar(255) DEFAULT '',
  `api_key` varchar(255) DEFAULT '',
  `api_secret` varchar(512) DEFAULT '',
  `api_url` varchar(500) DEFAULT '',
  `callback_url` varchar(500) DEFAULT '',
  `webhook_secret` varchar(255) DEFAULT '',
  `config_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`config_json`)),
  `supported_currencies` varchar(255) DEFAULT 'UGX',
  `transaction_fee_percent` decimal(5,2) DEFAULT 0.00,
  `transaction_fee_fixed` decimal(10,2) DEFAULT 0.00,
  `min_amount` decimal(12,2) DEFAULT 0.00,
  `max_amount` decimal(12,2) DEFAULT 10000000.00,
  `status` enum('active','inactive','sandbox') DEFAULT 'sandbox',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_receipts`
--

CREATE TABLE `payment_receipts` (
  `id` int(11) NOT NULL,
  `receipt_number` varchar(50) NOT NULL,
  `transaction_id` int(11) NOT NULL,
  `receipt_type` enum('payment','refund','invoice') NOT NULL DEFAULT 'payment',
  `student_name` varchar(200) DEFAULT NULL,
  `student_number` varchar(50) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'UGX',
  `payment_method` varchar(100) DEFAULT NULL,
  `payment_date` datetime DEFAULT NULL,
  `description` text DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `emailed` tinyint(1) DEFAULT 0,
  `emailed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_reconciliation`
--

CREATE TABLE `payment_reconciliation` (
  `id` int(11) NOT NULL,
  `reconciliation_date` date NOT NULL,
  `provider_key` varchar(50) NOT NULL,
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
  `created_at` timestamp NULL DEFAULT current_timestamp()
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
  `reason` text DEFAULT NULL,
  `status` enum('pending','processing','successful','failed') DEFAULT 'pending',
  `initiated_by` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `payment_transactions`
--

CREATE TABLE `payment_transactions` (
  `id` int(11) NOT NULL,
  `transaction_ref` varchar(100) NOT NULL,
  `provider_key` varchar(50) NOT NULL,
  `provider_transaction_id` varchar(255) DEFAULT '',
  `payment_type` enum('student_fees','application','admission','graduation','hostel','library_fine','donation','volunteer','staff','misc') NOT NULL,
  `reference_type` varchar(50) DEFAULT '',
  `reference_id` int(11) DEFAULT 0,
  `student_id` int(11) DEFAULT 0,
  `staff_id` int(11) DEFAULT 0,
  `payer_name` varchar(255) DEFAULT '',
  `payer_phone` varchar(50) DEFAULT '',
  `payer_email` varchar(255) DEFAULT '',
  `amount` decimal(12,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'UGX',
  `fee_amount` decimal(12,2) DEFAULT 0.00,
  `net_amount` decimal(12,2) DEFAULT 0.00,
  `status` enum('pending','processing','successful','failed','cancelled','refunded','expired') DEFAULT 'pending',
  `status_message` varchar(500) DEFAULT '',
  `metadata_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata_json`)),
  `initiated_by` int(11) DEFAULT 0,
  `ip_address` varchar(45) DEFAULT '',
  `user_agent` text DEFAULT NULL,
  `callback_received_at` timestamp NULL DEFAULT NULL,
  `reconciled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `printing_charges`
--

CREATE TABLE `printing_charges` (
  `id` int(11) NOT NULL,
  `print_type` enum('bw','color','photocopy') NOT NULL,
  `paper_size` enum('A4','A3','letter','legal') DEFAULT 'A4',
  `charge_per_page` decimal(10,2) NOT NULL DEFAULT 0.00,
  `description` varchar(200) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `printing_charges`
--

INSERT INTO `printing_charges` (`id`, `print_type`, `paper_size`, `charge_per_page`, `description`, `is_active`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'bw', 'A4', 100.00, 'Black & White A4 per page', 1, NULL, '2026-06-26 19:08:42', '2026-06-26 19:08:42'),
(2, 'color', 'A4', 500.00, 'Colour A4 per page', 1, NULL, '2026-06-26 19:08:42', '2026-06-26 19:08:42'),
(3, 'photocopy', 'A4', 50.00, 'Photocopy A4 per page', 1, NULL, '2026-06-26 19:08:42', '2026-06-26 19:08:42'),
(4, 'bw', 'A3', 200.00, 'Black & White A3 per page', 1, NULL, '2026-06-26 19:08:42', '2026-06-26 19:08:42'),
(5, 'color', 'A3', 1000.00, 'Colour A3 per page', 1, NULL, '2026-06-26 19:08:42', '2026-06-26 19:08:42'),
(6, 'photocopy', 'A3', 100.00, 'Photocopy A3 per page', 1, NULL, '2026-06-26 19:08:42', '2026-06-26 19:08:42');

-- --------------------------------------------------------

--
-- Table structure for table `printing_jobs`
--

CREATE TABLE `printing_jobs` (
  `id` int(11) NOT NULL,
  `job_number` varchar(50) NOT NULL,
  `requester_name` varchar(200) NOT NULL,
  `requester_type` enum('student','staff') NOT NULL,
  `requester_id` int(11) DEFAULT NULL,
  `document_name` varchar(200) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `pages` int(11) NOT NULL DEFAULT 1,
  `copies` int(11) DEFAULT 1,
  `print_type` enum('bw','color','photocopy') DEFAULT 'bw',
  `paper_size` enum('A4','A3','letter','legal') DEFAULT 'A4',
  `charge_per_page` decimal(10,2) DEFAULT 0.00,
  `total_charge` decimal(10,2) DEFAULT 0.00,
  `payment_status` enum('pending','paid','waived') DEFAULT 'pending',
  `status` enum('pending','printing','completed','cancelled') DEFAULT 'pending',
  `printed_by` int(11) DEFAULT NULL,
  `printed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
(1, 'MLR', 'Malaria', 'Infectious', 'Fever, chills, headache, sweating, fatigue', 'Mosquito-borne parasitic infection common in tropical regions', 0, 'Artemisinin-based combination therapy, antimalarials', 'Active', NULL, '2026-06-20 08:42:33', '2026-06-20 08:42:33'),
(2, 'TYP', 'Typhoid', 'Infectious', 'Prolonged fever, abdominal pain, headache, constipation or diarrhea', 'Bacterial infection spread through contaminated food/water', 1, 'Antibiotics (ciprofloxacin, azithromycin), hydration', 'Active', NULL, '2026-06-20 08:42:33', '2026-06-20 08:42:33'),
(3, 'FLU', 'Influenza', 'Infectious', 'Fever, cough, sore throat, body aches, fatigue', 'Viral respiratory infection spread through droplets', 1, 'Rest, fluids, antipyretics, antivirals if severe', 'Active', NULL, '2026-06-20 08:42:33', '2026-06-20 08:42:33'),
(4, 'COLD', 'Common Cold', 'Infectious', 'Runny nose, sneezing, sore throat, cough, mild fever', 'Viral upper respiratory tract infection', 1, 'Rest, antihistamines, decongestants, vitamin C', 'Active', NULL, '2026-06-20 08:42:33', '2026-06-20 08:42:33'),
(5, 'URTI', 'Upper Respiratory Tract Infection', 'Infectious', 'Cough, sore throat, nasal congestion, fever', 'Bacterial or viral infection of upper airways', 1, 'Antibiotics if bacterial, rest, fluids', 'Active', NULL, '2026-06-20 08:42:33', '2026-06-20 08:42:33'),
(6, 'HDCH', 'Headache/Tension Headache', 'Non-Infectious', 'Head pain, pressure around forehead, neck tension', 'Common tension-type headache from stress or fatigue', 0, 'Rest, analgesics (paracetamol, ibuprofen)', 'Active', NULL, '2026-06-20 08:42:33', '2026-06-20 08:42:33'),
(7, 'GSTR', 'Gastritis', 'Non-Infectious', 'Abdominal pain, nausea, bloating, indigestion', 'Inflammation of stomach lining from diet, stress, or infection', 0, 'Antacids, dietary changes, proton pump inhibitors', 'Active', NULL, '2026-06-20 08:42:33', '2026-06-20 08:42:33'),
(8, 'DIAR', 'Diarrhea', 'Infectious', 'Loose watery stools, abdominal cramps, dehydration', 'Common infection from contaminated food/water or viruses', 1, 'ORS, hydration, antidiarrheals, antibiotics if bacterial', 'Active', NULL, '2026-06-20 08:42:33', '2026-06-20 08:42:33'),
(9, 'ALLG', 'Allergic Reaction', 'Non-Infectious', 'Rash, itching, sneezing, watery eyes, swelling', 'Immune response to allergens (food, dust, pollen, drugs)', 0, 'Antihistamines, corticosteroids, avoid triggers', 'Active', NULL, '2026-06-20 08:42:33', '2026-06-20 08:42:33'),
(10, 'INJR', 'Injury/Accident', 'Injury', 'Pain, swelling, bruising, bleeding, limited mobility', 'Physical trauma from falls, sports, or accidents', 0, 'First aid, rest, ice, compression, elevation, analgesics', 'Active', NULL, '2026-06-20 08:42:33', '2026-06-20 08:42:33'),
(11, 'ANEM', 'Anemia', 'Nutritional', 'Fatigue, weakness, pale skin, shortness of breath, dizziness', 'Low red blood cell count from iron deficiency or other causes', 0, 'Iron supplements, dietary changes, B12 if needed', 'Active', NULL, '2026-06-20 08:42:33', '2026-06-20 08:42:33'),
(12, 'MALN', 'Malnutrition', 'Nutritional', 'Weight loss, fatigue, poor growth, weakened immunity', 'Inadequate nutrient intake affecting overall health', 0, 'Nutritional supplementation, diet counseling', 'Active', NULL, '2026-06-20 08:42:33', '2026-06-20 08:42:33'),
(13, 'CONS', 'Constipation', 'Non-Infectious', 'Infrequent bowel movements, straining, hard stools', 'Common digestive issue from diet or lifestyle factors', 0, 'Increased fiber intake, hydration, laxatives if needed', 'Active', NULL, '2026-06-20 08:42:33', '2026-06-20 08:42:33'),
(14, 'SORE', 'Sore Throat', 'Infectious', 'Pain or scratchiness in throat, difficulty swallowing', 'Viral or bacterial throat infection', 1, 'Warm salt water gargle, lozenges, antibiotics if strep', 'Active', NULL, '2026-06-20 08:42:33', '2026-06-20 08:42:33'),
(15, 'EYEI', 'Eye Infection', 'Infectious', 'Redness, itching, discharge, swollen eyelids', 'Bacterial or viral conjunctivitis', 1, 'Antibiotic or antiviral eye drops, hygiene', 'Active', NULL, '2026-06-20 08:42:33', '2026-06-20 08:42:33'),
(16, 'SKIN', 'Skin Infection/Rash', 'Infectious', 'Redness, itching, bumps, blisters, peeling', 'Fungal, bacterial, or viral skin infection', 1, 'Topical or oral antibiotics/antifungals, hygiene', 'Active', NULL, '2026-06-20 08:42:33', '2026-06-20 08:42:33'),
(17, 'FATG', 'Fatigue/General Malaise', 'Non-Infectious', 'Tiredness, low energy, reduced motivation', 'General feeling of being unwell without specific diagnosis', 0, 'Rest, nutrition, hydration, stress management', 'Active', NULL, '2026-06-20 08:42:33', '2026-06-20 08:42:33'),
(18, 'MSTR', 'Menstrual Cramps', 'Non-Infectious', 'Lower abdominal pain, back pain, nausea during menstruation', 'Painful menstrual periods common in young women', 0, 'Analgesics, heat therapy, rest, NSAIDs', 'Active', NULL, '2026-06-20 08:42:33', '2026-06-20 08:42:33'),
(19, 'ANXT', 'Anxiety/Stress', 'Mental Health', 'Worry, restlessness, rapid heartbeat, difficulty concentrating', 'Mental health condition common among students under academic pressure', 0, 'Counseling, stress management, relaxation techniques', 'Active', NULL, '2026-06-20 08:42:33', '2026-06-20 08:42:33'),
(20, 'BACK', 'Back Pain', 'Non-Infectious', 'Lower or upper back pain, stiffness, muscle tension', 'Musculoskeletal pain from poor posture, heavy lifting, or strain', 0, 'Rest, analgesics, physiotherapy, posture correction', 'Active', NULL, '2026-06-20 08:42:33', '2026-06-20 08:42:33'),
(21, 'THRP', 'Throat Infection/Pharyngitis', 'Infectious', 'Sore throat, red tonsils, swollen lymph nodes, fever', 'Inflammation of the pharynx from viral or bacterial infection', 1, 'Antibiotics if bacterial, rest, warm fluids', 'Active', NULL, '2026-06-20 08:42:33', '2026-06-20 08:42:33'),
(22, 'TOOT', 'Toothache', 'Non-Infectious', 'Tooth pain, sensitivity, swelling around tooth', 'Dental pain from cavities, infection, or impaction', 0, 'Analgesics, dental referral, antibiotics if infected', 'Active', NULL, '2026-06-20 08:42:33', '2026-06-20 08:42:33'),
(23, 'URIN', 'Urinary Tract Infection', 'Infectious', 'Painful urination, frequent urination, lower abdominal pain', 'Bacterial infection of the urinary tract', 0, 'Antibiotics, increased fluid intake, cranberry', 'Active', NULL, '2026-06-20 08:42:33', '2026-06-20 08:42:33'),
(24, 'ACNE', 'Acne/Skin Breakout', 'Non-Infectious', 'Pimples, blackheads, whiteheads, inflamed skin', 'Common skin condition from hormonal changes and stress', 0, 'Topical treatments, hygiene, dietary changes', 'Active', NULL, '2026-06-20 08:42:33', '2026-06-20 08:42:33'),
(25, 'FUNG', 'Fungal Infection', 'Infectious', 'Itching, redness, peeling skin, rash with defined edges', 'Fungal skin infection common in tropical climates', 1, 'Antifungal creams or oral medication, keep area dry', 'Active', NULL, '2026-06-20 08:42:33', '2026-06-20 08:42:33');

-- --------------------------------------------------------

--
-- Table structure for table `software_installations`
--

CREATE TABLE `software_installations` (
  `id` int(11) NOT NULL,
  `software_id` int(11) DEFAULT NULL,
  `computer_id` int(11) DEFAULT NULL,
  `lab_room_id` int(11) DEFAULT NULL,
  `installed_by` varchar(200) DEFAULT NULL,
  `installation_date` date DEFAULT NULL,
  `license_key_used` varchar(200) DEFAULT NULL,
  `version_installed` varchar(50) DEFAULT NULL,
  `status` enum('installed','updated','removed') DEFAULT 'installed',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `software_inventory`
--

CREATE TABLE `software_inventory` (
  `id` int(11) NOT NULL,
  `software_name` varchar(200) NOT NULL,
  `version` varchar(50) DEFAULT NULL,
  `license_key` varchar(200) DEFAULT NULL,
  `license_type` enum('free','commercial','educational','trial') DEFAULT 'educational',
  `license_expiry` date DEFAULT NULL,
  `installation_count` int(11) DEFAULT 0,
  `update_available` tinyint(1) DEFAULT 0,
  `latest_version` varchar(50) DEFAULT NULL,
  `download_url` varchar(500) DEFAULT NULL,
  `category` enum('os','office','development','design','antivirus','utility','other') DEFAULT 'utility',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `software_inventory`
--

INSERT INTO `software_inventory` (`id`, `software_name`, `version`, `license_key`, `license_type`, `license_expiry`, `installation_count`, `update_available`, `latest_version`, `download_url`, `category`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Microsoft Office 365', '2024', NULL, 'educational', '2025-12-31', 50, 0, '2024', NULL, 'office', NULL, '2026-06-14 18:38:56', '2026-06-14 18:38:56'),
(2, 'SPSS Statistics', '29.0', NULL, 'commercial', '2024-12-31', 25, 1, '30.0', NULL, 'development', NULL, '2026-06-14 18:38:56', '2026-06-14 18:38:56'),
(3, 'Windows 11 Pro', '23H2', NULL, 'educational', '2026-06-30', 50, 0, '23H2', NULL, 'os', NULL, '2026-06-14 18:38:56', '2026-06-14 18:38:56'),
(4, 'Adobe Creative Cloud', '2024', NULL, 'educational', '2024-08-31', 15, 1, '2024.1', NULL, 'design', NULL, '2026-06-14 18:38:56', '2026-06-14 18:38:56'),
(5, 'Malwarebytes Antivirus', '5.0', NULL, 'commercial', '2025-01-15', 50, 0, '5.0', NULL, 'antivirus', NULL, '2026-06-14 18:38:56', '2026-06-14 18:38:56'),
(6, 'Microsoft Office 365', '2024', NULL, 'educational', '2025-12-31', 50, 0, '2024', NULL, 'office', NULL, '2026-06-15 04:19:05', '2026-06-15 04:19:05'),
(7, 'SPSS Statistics', '29.0', NULL, 'commercial', '2024-12-31', 25, 1, '30.0', NULL, 'development', NULL, '2026-06-15 04:19:05', '2026-06-15 04:19:05'),
(8, 'Windows 11 Pro', '23H2', NULL, 'educational', '2026-06-30', 50, 0, '23H2', NULL, 'os', NULL, '2026-06-15 04:19:05', '2026-06-15 04:19:05'),
(9, 'Adobe Creative Cloud', '2024', NULL, 'educational', '2024-08-31', 15, 1, '2024.1', NULL, 'design', NULL, '2026-06-15 04:19:05', '2026-06-15 04:19:05'),
(10, 'Malwarebytes Antivirus', '5.0', NULL, 'commercial', '2025-01-15', 50, 0, '5.0', NULL, 'antivirus', NULL, '2026-06-15 04:19:05', '2026-06-15 04:19:05');

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
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `student_number` varchar(50) DEFAULT NULL,
  `registration_number` varchar(50) DEFAULT NULL,
  `index_number` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `other_name` varchar(100) DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT 'Ugandan',
  `district` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `level` varchar(50) DEFAULT NULL,
  `set_name` varchar(100) DEFAULT NULL COMMENT 'e.g. Set 25, Set 28',
  `intake_year` year(4) DEFAULT NULL,
  `intake_period` varchar(50) DEFAULT NULL,
  `status` enum('Active','Inactive','Graduated','Suspended','Withdrawn','deleted') NOT NULL DEFAULT 'Active',
  `password` varchar(255) DEFAULT NULL,
  `is_first_login` tinyint(1) NOT NULL DEFAULT 1,
  `password_changed` tinyint(1) NOT NULL DEFAULT 0,
  `profile_picture` varchar(500) DEFAULT NULL,
  `passport_photo` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `student_academic_records`
--

CREATE TABLE `student_academic_records` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `course_code` varchar(50) DEFAULT NULL,
  `marks` decimal(5,2) DEFAULT NULL,
  `grade` varchar(5) DEFAULT NULL,
  `credit_hours` decimal(4,1) DEFAULT 0.0,
  `gpa` decimal(4,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `student_attendance`
--

CREATE TABLE `student_attendance` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `date` date DEFAULT NULL,
  `attendance_date` date DEFAULT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `status` enum('Present','Absent','Late','Excused','Holiday') NOT NULL DEFAULT 'Present',
  `subject` varchar(255) DEFAULT NULL,
  `lecturer` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_clinical_logbook_entries`
--

CREATE TABLE `student_clinical_logbook_entries` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `date` date DEFAULT NULL,
  `procedure_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `supervisor_name` varchar(255) DEFAULT NULL,
  `supervisor_comment` text DEFAULT NULL,
  `verification_status` enum('Pending','Verified','Rejected') NOT NULL DEFAULT 'Pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_clinical_placements`
--

CREATE TABLE `student_clinical_placements` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `facility_name` varchar(255) NOT NULL,
  `facility_location` varchar(255) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `supervisor_name` varchar(255) DEFAULT NULL,
  `supervisor_phone` varchar(20) DEFAULT NULL,
  `supervisor_email` varchar(100) DEFAULT NULL,
  `supervisor_evaluation` text DEFAULT NULL,
  `status` enum('Active','Completed','Upcoming','Cancelled') NOT NULL DEFAULT 'Active',
  `hours_completed` int(11) DEFAULT 0,
  `hours_required` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_competencies`
--

CREATE TABLE `student_competencies` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `skill_name` varchar(255) NOT NULL,
  `skill_category` varchar(100) DEFAULT NULL,
  `proficiency` enum('Not Attempted','Beginner','Intermediate','Competent','Expert') NOT NULL DEFAULT 'Not Attempted',
  `date_assessed` date DEFAULT NULL,
  `assessed_by` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_course_registrations`
--

CREATE TABLE `student_course_registrations` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `course_name` varchar(255) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `status` enum('Registered','Dropped','Completed','Incomplete') NOT NULL DEFAULT 'Registered',
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `student_fee_tracking`
--

CREATE TABLE `student_fee_tracking` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `fee_type` varchar(100) DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `paid` decimal(14,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `due_date` date DEFAULT NULL,
  `status` enum('Pending','Paid','Partial','Overdue','Waived') NOT NULL DEFAULT 'Pending',
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_hostel_allocations`
--

CREATE TABLE `student_hostel_allocations` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `hostel_name` varchar(100) DEFAULT NULL,
  `room_number` varchar(20) DEFAULT NULL,
  `bed_number` varchar(20) DEFAULT NULL,
  `check_in_date` date DEFAULT NULL,
  `check_out_date` date DEFAULT NULL,
  `status` enum('Active','Checked Out','Reserved') NOT NULL DEFAULT 'Active',
  `fee_per_semester` decimal(14,2) DEFAULT 0.00,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_id_cards`
--

CREATE TABLE `student_id_cards` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `card_number` varchar(50) NOT NULL,
  `registration_number` varchar(50) DEFAULT NULL,
  `program` varchar(200) DEFAULT NULL,
  `intake` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `photo_path` varchar(500) DEFAULT NULL,
  `qr_code` text DEFAULT NULL,
  `barcode` varchar(200) DEFAULT NULL,
  `status` enum('active','expired','lost','damaged','replaced') DEFAULT 'active',
  `issued_by` int(11) DEFAULT NULL,
  `issued_date` timestamp NULL DEFAULT current_timestamp(),
  `last_print_date` timestamp NULL DEFAULT NULL,
  `print_count` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_library_borrowing`
--

CREATE TABLE `student_library_borrowing` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `book_title` varchar(255) NOT NULL,
  `book_author` varchar(255) DEFAULT NULL,
  `isbn` varchar(50) DEFAULT NULL,
  `borrow_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `fine_amount` decimal(10,2) DEFAULT 0.00,
  `fine_paid` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('Borrowed','Returned','Overdue','Lost') NOT NULL DEFAULT 'Borrowed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_messages`
--

CREATE TABLE `student_messages` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `sender` varchar(255) DEFAULT 'System',
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_notifications`
--

CREATE TABLE `student_notifications` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL COMMENT 'NULL = broadcast to all',
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `type` enum('info','success','warning','danger','announcement') NOT NULL DEFAULT 'info',
  `priority` enum('Low','Normal','High','Urgent') NOT NULL DEFAULT 'Normal',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `link` varchar(500) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_payment_transactions`
--

CREATE TABLE `student_payment_transactions` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `fee_id` int(11) DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL,
  `payment_method` enum('Cash','Bank Transfer','Mobile Money','Cheque','Other') NOT NULL DEFAULT 'Cash',
  `transaction_ref` varchar(100) DEFAULT NULL,
  `paid_by` varchar(255) DEFAULT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `payment_date` timestamp NULL DEFAULT current_timestamp(),
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_profiles`
--

CREATE TABLE `student_profiles` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `bio` text DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `medical_info` text DEFAULT NULL,
  `next_of_kin` varchar(255) DEFAULT NULL,
  `next_of_kin_phone` varchar(20) DEFAULT NULL,
  `sponsor_name` varchar(255) DEFAULT NULL,
  `sponsor_phone` varchar(20) DEFAULT NULL,
  `sponsor_email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_requests`
--

CREATE TABLE `student_requests` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `request_type` enum('Leave of Absence','Deferral','Transfer','Withdrawal','Transcript','Other') NOT NULL DEFAULT 'Other',
  `reason` text NOT NULL,
  `status` enum('Pending','Approved','Rejected','Cancelled') NOT NULL DEFAULT 'Pending',
  `admin_response` text DEFAULT NULL,
  `responded_by` int(11) DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_semester_gpa`
--

CREATE TABLE `student_semester_gpa` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `total_credits` decimal(6,2) DEFAULT 0.00,
  `earned_credits` decimal(6,2) DEFAULT 0.00,
  `semester_gpa` decimal(4,2) DEFAULT 0.00,
  `cumulative_gpa` decimal(4,2) DEFAULT 0.00,
  `academic_standing` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `student_timetables`
--

CREATE TABLE `student_timetables` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `subject` varchar(255) NOT NULL,
  `lecturer` varchar(255) DEFAULT NULL,
  `room` varchar(100) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_warnings`
--

CREATE TABLE `student_warnings` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `warning_type` varchar(100) DEFAULT NULL,
  `severity` enum('Verbal','Written','Final','Suspension') NOT NULL DEFAULT 'Written',
  `reason` text NOT NULL,
  `issued_by` varchar(255) DEFAULT NULL,
  `issued_date` date DEFAULT NULL,
  `warning_date` date DEFAULT NULL,
  `status` enum('Active','Resolved','Expired') NOT NULL DEFAULT 'Active',
  `resolution` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
-- Table structure for table `v_computer_availability`
--

CREATE TABLE `v_computer_availability` (
  `location` varchar(100) DEFAULT NULL,
  `total_computers` bigint(20) DEFAULT NULL,
  `online_count` decimal(23,0) DEFAULT NULL,
  `offline_count` decimal(23,0) DEFAULT NULL,
  `maintenance_count` decimal(23,0) DEFAULT NULL,
  `availability_percentage` decimal(29,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

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
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `action` (`action`),
  ADD KEY `entity_type` (`entity_type`),
  ADD KEY `created_at` (`created_at`);

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
-- Indexes for table `computer_repairs`
--
ALTER TABLE `computer_repairs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `repair_number` (`repair_number`),
  ADD KEY `computer_id` (`computer_id`),
  ADD KEY `equipment_id` (`equipment_id`),
  ADD KEY `status` (`status`),
  ADD KEY `priority` (`priority`);

--
-- Indexes for table `course_catalog`
--
ALTER TABLE `course_catalog`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `idx_cc_level` (`level`),
  ADD KEY `idx_cc_status` (`status`);

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
-- Indexes for table `form_submissions`
--
ALTER TABLE `form_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type` (`form_type`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created` (`created_at`);

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
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_student_program` (`student_id`,`program_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `ict_assets`
--
ALTER TABLE `ict_assets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `asset_number` (`asset_number`),
  ADD KEY `asset_type` (`asset_type`),
  ADD KEY `current_status` (`current_status`),
  ADD KEY `assigned_staff_id` (`assigned_staff_id`),
  ADD KEY `warranty_expiry` (`warranty_expiry`);

--
-- Indexes for table `ict_asset_assignments`
--
ALTER TABLE `ict_asset_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_id` (`asset_id`),
  ADD KEY `assigned_to_staff_id` (`assigned_to_staff_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `ict_asset_categories`
--
ALTER TABLE `ict_asset_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_name` (`category_name`),
  ADD KEY `parent_id` (`parent_id`);

--
-- Indexes for table `ict_asset_maintenance`
--
ALTER TABLE `ict_asset_maintenance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_id` (`asset_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `ict_asset_warranty`
--
ALTER TABLE `ict_asset_warranty`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_id` (`asset_id`),
  ADD KEY `status` (`status`),
  ADD KEY `end_date` (`end_date`);

--
-- Indexes for table `ict_audit_logs`
--
ALTER TABLE `ict_audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `action` (`action`),
  ADD KEY `resource_type` (`resource_type`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `ict_backup_logs`
--
ALTER TABLE `ict_backup_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `backup_id` (`backup_id`),
  ADD KEY `logged_at` (`logged_at`);

--
-- Indexes for table `ict_device_categories`
--
ALTER TABLE `ict_device_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_name` (`category_name`);

--
-- Indexes for table `ict_failed_logins`
--
ALTER TABLE `ict_failed_logins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `username` (`username`),
  ADD KEY `ip_address` (`ip_address`),
  ADD KEY `attempted_at` (`attempted_at`);

--
-- Indexes for table `ict_login_sessions`
--
ALTER TABLE `ict_login_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `status` (`status`),
  ADD KEY `login_at` (`login_at`);

--
-- Indexes for table `ict_module_permissions`
--
ALTER TABLE `ict_module_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `module_role` (`module_name`,`role_keyword`);

--
-- Indexes for table `ict_network_logs`
--
ALTER TABLE `ict_network_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `device_id` (`device_id`),
  ADD KEY `log_type` (`log_type`),
  ADD KEY `severity` (`severity`),
  ADD KEY `logged_at` (`logged_at`);

--
-- Indexes for table `ict_security_logs`
--
ALTER TABLE `ict_security_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `event_type` (`event_type`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `created_at` (`created_at`),
  ADD KEY `ip_address` (`ip_address`);

--
-- Indexes for table `ict_servers`
--
ALTER TABLE `ict_servers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `server_name` (`server_name`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `ict_system_alerts`
--
ALTER TABLE `ict_system_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `alert_type` (`alert_type`),
  ADD KEY `severity` (`severity`),
  ADD KEY `status` (`status`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `ict_system_backups`
--
ALTER TABLE `ict_system_backups`
  ADD PRIMARY KEY (`id`),
  ADD KEY `backup_type` (`backup_type`),
  ADD KEY `status` (`status`),
  ADD KEY `started_at` (`started_at`);

--
-- Indexes for table `ict_system_health`
--
ALTER TABLE `ict_system_health`
  ADD PRIMARY KEY (`id`),
  ADD KEY `check_type` (`check_type`),
  ADD KEY `status` (`status`),
  ADD KEY `checked_at` (`checked_at`);

--
-- Indexes for table `ict_system_notifications`
--
ALTER TABLE `ict_system_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `notification_type` (`notification_type`),
  ADD KEY `is_read` (`is_read`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `ict_system_settings`
--
ALTER TABLE `ict_system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `setting_group` (`setting_group`);

--
-- Indexes for table `ict_wifi_devices`
--
ALTER TABLE `ict_wifi_devices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `id_card_print_history`
--
ALTER TABLE `id_card_print_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `card_id` (`card_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `printed_by` (`printed_by`),
  ADD KEY `print_date` (`print_date`);

--
-- Indexes for table `id_card_replacements`
--
ALTER TABLE `id_card_replacements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `original_card_id` (`original_card_id`),
  ADD KEY `new_card_id` (`new_card_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `intakes`
--
ALTER TABLE `intakes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_intake` (`intake_month`,`intake_year`),
  ADD KEY `idx_intake_status` (`status`);

--
-- Indexes for table `it_support_tickets`
--
ALTER TABLE `it_support_tickets`
  ADD KEY `idx_ist_status_priority` (`status`,`priority`);

--
-- Indexes for table `lab_asset_assignments`
--
ALTER TABLE `lab_asset_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `asset_type` (`asset_type`,`asset_id`),
  ADD KEY `assigned_to_type` (`assigned_to_type`,`assigned_to_id`),
  ADD KEY `lab_room_id` (`lab_room_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `lab_attendance`
--
ALTER TABLE `lab_attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `lab_room_id` (`lab_room_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `attendance_date` (`attendance_date`),
  ADD KEY `computer_id` (`computer_id`);

--
-- Indexes for table `lab_bookings`
--
ALTER TABLE `lab_bookings`
  ADD KEY `idx_lb_date_status` (`booking_date`,`status`);

--
-- Indexes for table `lab_computer_assignments`
--
ALTER TABLE `lab_computer_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `computer_id` (`computer_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `status` (`status`),
  ADD KEY `assigned_date` (`assigned_date`);

--
-- Indexes for table `lab_consumables`
--
ALTER TABLE `lab_consumables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_category` (`item_category`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `lab_equipment`
--
ALTER TABLE `lab_equipment`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `equipment_code` (`equipment_code`),
  ADD KEY `lab_room_id` (`lab_room_id`),
  ADD KEY `equipment_type` (`equipment_type`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `lab_equipment_checkout`
--
ALTER TABLE `lab_equipment_checkout`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipment_id` (`equipment_id`),
  ADD KEY `borrower_id` (`borrower_id`),
  ADD KEY `status` (`status`),
  ADD KEY `expected_return` (`expected_return`);

--
-- Indexes for table `lab_practical_sessions`
--
ALTER TABLE `lab_practical_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_code` (`session_code`),
  ADD KEY `lab_room_id` (`lab_room_id`),
  ADD KEY `session_date` (`session_date`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `lab_rooms`
--
ALTER TABLE `lab_rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `room_code` (`room_code`),
  ADD KEY `status` (`status`);

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
-- Indexes for table `network_devices`
--
ALTER TABLE `network_devices`
  ADD KEY `idx_nd_type_status` (`device_type`,`status`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_unread` (`staff_id`,`is_read`),
  ADD KEY `idx_created` (`created_at`),
  ADD KEY `idx_type` (`type`);

--
-- Indexes for table `notification_logs`
--
ALTER TABLE `notification_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `is_read` (`is_read`),
  ADD KEY `created_at` (`created_at`);

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
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `payment_receipts`
--
ALTER TABLE `payment_receipts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receipt_number` (`receipt_number`),
  ADD KEY `idx_receipt_transaction` (`transaction_id`),
  ADD KEY `idx_receipt_student` (`student_number`),
  ADD KEY `idx_receipt_date` (`created_at`);

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
  ADD KEY `idx_created` (`created_at`);

--
-- Indexes for table `payment_webhook_logs`
--
ALTER TABLE `payment_webhook_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_provider` (`provider_key`),
  ADD KEY `idx_event` (`event_type`),
  ADD KEY `idx_processed` (`processed`);

--
-- Indexes for table `printing_charges`
--
ALTER TABLE `printing_charges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `print_type_paper` (`print_type`,`paper_size`);

--
-- Indexes for table `printing_jobs`
--
ALTER TABLE `printing_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `job_number` (`job_number`),
  ADD KEY `requester_id` (`requester_id`),
  ADD KEY `status` (`status`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_endpoint` (`user_id`,`endpoint`);

--
-- Indexes for table `requirement_history`
--
ALTER TABLE `requirement_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_rh_app` (`applicant_id`),
  ADD KEY `idx_rh_action` (`action`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `setting_group` (`setting_group`);

--
-- Indexes for table `software_installations`
--
ALTER TABLE `software_installations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `software_id` (`software_id`),
  ADD KEY `computer_id` (`computer_id`),
  ADD KEY `lab_room_id` (`lab_room_id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_staff_role` (`role_id`),
  ADD KEY `idx_staff_email` (`email`);

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
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD UNIQUE KEY `student_number` (`student_number`),
  ADD UNIQUE KEY `registration_number` (`registration_number`),
  ADD KEY `idx_stu_name` (`full_name`),
  ADD KEY `idx_stu_program` (`program`),
  ADD KEY `idx_stu_set` (`set_name`),
  ADD KEY `idx_stu_status` (`status`),
  ADD KEY `idx_stu_phone` (`phone`),
  ADD KEY `idx_stu_email` (`email`);

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ar_student` (`student_id`),
  ADD KEY `idx_ar_course` (`course_code`),
  ADD KEY `idx_ar_year_sem` (`academic_year`,`semester`);

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
-- Indexes for table `student_attendance`
--
ALTER TABLE `student_attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_att_student` (`student_id`),
  ADD KEY `idx_att_date` (`date`),
  ADD KEY `idx_att_status` (`status`);

--
-- Indexes for table `student_clinical_logbook_entries`
--
ALTER TABLE `student_clinical_logbook_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cle_student` (`student_id`),
  ADD KEY `idx_cle_status` (`verification_status`);

--
-- Indexes for table `student_clinical_placements`
--
ALTER TABLE `student_clinical_placements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cp_student` (`student_id`),
  ADD KEY `idx_cp_status` (`status`);

--
-- Indexes for table `student_competencies`
--
ALTER TABLE `student_competencies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sc_student` (`student_id`),
  ADD KEY `idx_sc_category` (`skill_category`);

--
-- Indexes for table `student_course_registrations`
--
ALTER TABLE `student_course_registrations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_scr_student` (`student_id`),
  ADD KEY `idx_scr_course` (`course_code`),
  ADD KEY `idx_scr_year_sem` (`academic_year`,`semester`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `student_documents`
--
ALTER TABLE `student_documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_doc_app` (`applicant_id`),
  ADD KEY `idx_doc_ver` (`verification_status`);

--
-- Indexes for table `student_fee_tracking`
--
ALTER TABLE `student_fee_tracking`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ft_student` (`student_id`),
  ADD KEY `idx_ft_status` (`status`),
  ADD KEY `idx_ft_year` (`academic_year`);

--
-- Indexes for table `student_hostel_allocations`
--
ALTER TABLE `student_hostel_allocations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `idx_ha_status` (`status`);

--
-- Indexes for table `student_id_cards`
--
ALTER TABLE `student_id_cards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `card_number` (`card_number`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `status` (`status`),
  ADD KEY `expiry_date` (`expiry_date`);

--
-- Indexes for table `student_library_borrowing`
--
ALTER TABLE `student_library_borrowing`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lb_student` (`student_id`),
  ADD KEY `idx_lb_status` (`status`),
  ADD KEY `idx_lb_due` (`due_date`);

--
-- Indexes for table `student_messages`
--
ALTER TABLE `student_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sm_student` (`student_id`),
  ADD KEY `idx_sm_read` (`is_read`);

--
-- Indexes for table `student_notifications`
--
ALTER TABLE `student_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sn_student` (`student_id`),
  ADD KEY `idx_sn_read` (`is_read`),
  ADD KEY `idx_sn_type` (`type`);

--
-- Indexes for table `student_payment_transactions`
--
ALTER TABLE `student_payment_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pt_student` (`student_id`),
  ADD KEY `idx_pt_method` (`payment_method`),
  ADD KEY `fee_id` (`fee_id`);

--
-- Indexes for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`);

--
-- Indexes for table `student_requests`
--
ALTER TABLE `student_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sr_student` (`student_id`),
  ADD KEY `idx_sr_status` (`status`),
  ADD KEY `idx_sr_type` (`request_type`);

--
-- Indexes for table `student_semester_gpa`
--
ALTER TABLE `student_semester_gpa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sg_student` (`student_id`),
  ADD KEY `idx_sg_year_sem` (`academic_year`,`semester`);

--
-- Indexes for table `student_timetables`
--
ALTER TABLE `student_timetables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tt_student` (`student_id`),
  ADD KEY `idx_tt_day` (`day_of_week`),
  ADD KEY `idx_tt_time` (`start_time`);

--
-- Indexes for table `student_warnings`
--
ALTER TABLE `student_warnings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sw_student` (`student_id`),
  ADD KEY `idx_sw_status` (`status`);

--
-- Indexes for table `system_modules`
--
ALTER TABLE `system_modules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `module_key` (`module_key`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `is_active` (`is_active`);

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
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `complaint_submissions`
--
ALTER TABLE `complaint_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `computer_repairs`
--
ALTER TABLE `computer_repairs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `course_catalog`
--
ALTER TABLE `course_catalog`
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
-- AUTO_INCREMENT for table `feedback_submissions`
--
ALTER TABLE `feedback_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `form_submissions`
--
ALTER TABLE `form_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `gpa_settings`
--
ALTER TABLE `gpa_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
-- AUTO_INCREMENT for table `ict_assets`
--
ALTER TABLE `ict_assets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ict_asset_assignments`
--
ALTER TABLE `ict_asset_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ict_asset_categories`
--
ALTER TABLE `ict_asset_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `ict_asset_maintenance`
--
ALTER TABLE `ict_asset_maintenance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ict_asset_warranty`
--
ALTER TABLE `ict_asset_warranty`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ict_audit_logs`
--
ALTER TABLE `ict_audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ict_backup_logs`
--
ALTER TABLE `ict_backup_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ict_device_categories`
--
ALTER TABLE `ict_device_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `ict_failed_logins`
--
ALTER TABLE `ict_failed_logins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ict_login_sessions`
--
ALTER TABLE `ict_login_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ict_module_permissions`
--
ALTER TABLE `ict_module_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ict_network_logs`
--
ALTER TABLE `ict_network_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ict_security_logs`
--
ALTER TABLE `ict_security_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ict_servers`
--
ALTER TABLE `ict_servers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ict_system_alerts`
--
ALTER TABLE `ict_system_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ict_system_backups`
--
ALTER TABLE `ict_system_backups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ict_system_health`
--
ALTER TABLE `ict_system_health`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ict_system_notifications`
--
ALTER TABLE `ict_system_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ict_system_settings`
--
ALTER TABLE `ict_system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `ict_wifi_devices`
--
ALTER TABLE `ict_wifi_devices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `id_card_print_history`
--
ALTER TABLE `id_card_print_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `id_card_replacements`
--
ALTER TABLE `id_card_replacements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `intakes`
--
ALTER TABLE `intakes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_asset_assignments`
--
ALTER TABLE `lab_asset_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_attendance`
--
ALTER TABLE `lab_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_computer_assignments`
--
ALTER TABLE `lab_computer_assignments`
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
-- AUTO_INCREMENT for table `lab_equipment_checkout`
--
ALTER TABLE `lab_equipment_checkout`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_practical_sessions`
--
ALTER TABLE `lab_practical_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_rooms`
--
ALTER TABLE `lab_rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `printing_charges`
--
ALTER TABLE `printing_charges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `printing_jobs`
--
ALTER TABLE `printing_jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `push_subscriptions`
--
ALTER TABLE `push_subscriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `requirement_history`
--
ALTER TABLE `requirement_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `software_installations`
--
ALTER TABLE `software_installations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
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
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_academic_profiles`
--
ALTER TABLE `student_academic_profiles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_academic_records`
--
ALTER TABLE `student_academic_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_admission_tracking`
--
ALTER TABLE `student_admission_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_attendance`
--
ALTER TABLE `student_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_clinical_logbook_entries`
--
ALTER TABLE `student_clinical_logbook_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_clinical_placements`
--
ALTER TABLE `student_clinical_placements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_competencies`
--
ALTER TABLE `student_competencies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_course_registrations`
--
ALTER TABLE `student_course_registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_documents`
--
ALTER TABLE `student_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_fee_tracking`
--
ALTER TABLE `student_fee_tracking`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_hostel_allocations`
--
ALTER TABLE `student_hostel_allocations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_id_cards`
--
ALTER TABLE `student_id_cards`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_library_borrowing`
--
ALTER TABLE `student_library_borrowing`
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
-- AUTO_INCREMENT for table `student_payment_transactions`
--
ALTER TABLE `student_payment_transactions`
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
-- AUTO_INCREMENT for table `student_semester_gpa`
--
ALTER TABLE `student_semester_gpa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_timetables`
--
ALTER TABLE `student_timetables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_warnings`
--
ALTER TABLE `student_warnings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_modules`
--
ALTER TABLE `system_modules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

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
-- Constraints for table `student_academic_records`
--
ALTER TABLE `student_academic_records`
  ADD CONSTRAINT `student_academic_records_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_admission_tracking`
--
ALTER TABLE `student_admission_tracking`
  ADD CONSTRAINT `student_admission_tracking_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_attendance`
--
ALTER TABLE `student_attendance`
  ADD CONSTRAINT `student_attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_clinical_logbook_entries`
--
ALTER TABLE `student_clinical_logbook_entries`
  ADD CONSTRAINT `student_clinical_logbook_entries_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_clinical_placements`
--
ALTER TABLE `student_clinical_placements`
  ADD CONSTRAINT `student_clinical_placements_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_competencies`
--
ALTER TABLE `student_competencies`
  ADD CONSTRAINT `student_competencies_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_course_registrations`
--
ALTER TABLE `student_course_registrations`
  ADD CONSTRAINT `student_course_registrations_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_course_registrations_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `course_catalog` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_documents`
--
ALTER TABLE `student_documents`
  ADD CONSTRAINT `student_documents_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_fee_tracking`
--
ALTER TABLE `student_fee_tracking`
  ADD CONSTRAINT `student_fee_tracking_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_hostel_allocations`
--
ALTER TABLE `student_hostel_allocations`
  ADD CONSTRAINT `student_hostel_allocations_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_library_borrowing`
--
ALTER TABLE `student_library_borrowing`
  ADD CONSTRAINT `student_library_borrowing_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_messages`
--
ALTER TABLE `student_messages`
  ADD CONSTRAINT `student_messages_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_notifications`
--
ALTER TABLE `student_notifications`
  ADD CONSTRAINT `student_notifications_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_payment_transactions`
--
ALTER TABLE `student_payment_transactions`
  ADD CONSTRAINT `student_payment_transactions_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `student_payment_transactions_ibfk_2` FOREIGN KEY (`fee_id`) REFERENCES `student_fee_tracking` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `student_profiles`
--
ALTER TABLE `student_profiles`
  ADD CONSTRAINT `student_profiles_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_requests`
--
ALTER TABLE `student_requests`
  ADD CONSTRAINT `student_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_semester_gpa`
--
ALTER TABLE `student_semester_gpa`
  ADD CONSTRAINT `student_semester_gpa_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_timetables`
--
ALTER TABLE `student_timetables`
  ADD CONSTRAINT `student_timetables_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `student_warnings`
--
ALTER TABLE `student_warnings`
  ADD CONSTRAINT `student_warnings_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
