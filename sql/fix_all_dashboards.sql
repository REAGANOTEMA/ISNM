-- ══════════════════════════════════════════════════════════════
-- ISNM: Comprehensive Dashboard Fix SQL (v2)
-- Run this ONCE in phpMyAdmin to fix all dashboard tables.
-- Safe to run multiple times (idempotent).
--
-- IMPORTANT: This handles column name mismatches between the
-- existing SQL dump tables and what the PHP dashboards expect.
-- ══════════════════════════════════════════════════════════════

SET sql_mode = '';

-- ────────────────────────────────────────────────
-- 1. WEBSITE DATABASE (igangaschool_website)
-- ────────────────────────────────────────────────
USE `igangaschool_website`;

-- ── news_categories ──
-- Existing table has: category_name, display_order
-- PHP code (dashboards/news.php) uses: name, sort_order
-- Fix: DROP and recreate with correct columns, re-seed
DROP TABLE IF EXISTS `news_categories`;
CREATE TABLE `news_categories` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `sort_order` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_nc_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `news_categories` (`name`, `slug`, `description`, `sort_order`, `is_active`) VALUES
('General','general','General news and announcements',1,1),
('Academic','academic','Academic news and updates',2,1),
('Events','events','Upcoming and past events',3,1),
('Sports','sports','Sports news and results',4,1),
('Announcements','announcements','Important announcements',5,1),
('Staff','staff','Staff-related news',6,1),
('Student Life','student-life','Student life and activities',7,1);

-- ── news ──
-- The dump table is missing columns that dashboards need.
-- Add missing columns to existing table first.
ALTER TABLE `news` ADD COLUMN IF NOT EXISTS `summary` TEXT AFTER `slug`;
ALTER TABLE `news` ADD COLUMN IF NOT EXISTS `category` VARCHAR(100) DEFAULT 'General' AFTER `excerpt`;
ALTER TABLE `news` ADD COLUMN IF NOT EXISTS `tags` VARCHAR(500) AFTER `category`;
ALTER TABLE `news` ADD COLUMN IF NOT EXISTS `is_featured` TINYINT(1) DEFAULT 0 AFTER `status`;
ALTER TABLE `news` ADD COLUMN IF NOT EXISTS `scheduled_at` DATETIME NULL AFTER `published_at`;
ALTER TABLE `news` ADD COLUMN IF NOT EXISTS `archived_at` DATETIME NULL AFTER `scheduled_at`;
ALTER TABLE `news` ADD COLUMN IF NOT EXISTS `views` INT DEFAULT 0 AFTER `author_name`;

-- Ensure status ENUM includes 'scheduled'
ALTER TABLE `news` MODIFY COLUMN `status` ENUM('draft','published','scheduled','archived') DEFAULT 'draft';

-- Create table only if it doesn't exist at all (with all columns)
CREATE TABLE IF NOT EXISTS `news` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(300) NOT NULL,
  `slug` VARCHAR(300) NOT NULL,
  `summary` TEXT,
  `content` LONGTEXT,
  `featured_image` VARCHAR(500),
  `category` VARCHAR(100) DEFAULT 'General',
  `tags` VARCHAR(500),
  `excerpt` TEXT,
  `status` ENUM('draft','published','scheduled','archived') DEFAULT 'draft',
  `is_featured` TINYINT(1) DEFAULT 0,
  `published_at` DATETIME NULL,
  `scheduled_at` DATETIME NULL,
  `archived_at` DATETIME NULL,
  `author_id` INT(11),
  `author_name` VARCHAR(200),
  `author_role` VARCHAR(200),
  `views` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_news_slug` (`slug`),
  KEY `idx_news_status` (`status`),
  KEY `idx_news_published` (`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── CMS tables ──
CREATE TABLE IF NOT EXISTS `cms_events` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `event_date` DATE,
  `event_type` VARCHAR(50) DEFAULT 'general',
  `location` VARCHAR(255),
  `start_time` TIME,
  `end_time` TIME,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_by` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cms_testimonials` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `content` TEXT NOT NULL,
  `author_name` VARCHAR(200) NOT NULL,
  `author_role` VARCHAR(100),
  `rating` INT DEFAULT 5,
  `is_featured` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cms_faqs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `question` TEXT NOT NULL,
  `answer` TEXT NOT NULL,
  `category` VARCHAR(100) DEFAULT 'General',
  `sort_order` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cms_settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT,
  `setting_type` VARCHAR(50) DEFAULT 'text',
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cms_setting` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Website form tables ──
CREATE TABLE IF NOT EXISTS `contact_submissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(200) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `subject` VARCHAR(255) DEFAULT NULL,
  `message` TEXT,
  `status` VARCHAR(20) DEFAULT 'New',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `volunteer_applications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(100) DEFAULT NULL,
  `last_name` VARCHAR(100) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `profession` VARCHAR(100) DEFAULT NULL,
  `opportunity` VARCHAR(200) DEFAULT NULL,
  `status` VARCHAR(20) DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `donations` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `donor_name` VARCHAR(200) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `amount` DECIMAL(14,2) DEFAULT 0.00,
  `payment_method` VARCHAR(50),
  `status` VARCHAR(20) DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `student_applications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(100) DEFAULT NULL,
  `surname` VARCHAR(100) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `program_applied` VARCHAR(200) DEFAULT NULL,
  `status` VARCHAR(20) DEFAULT 'Pending',
  `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `website_announcements` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `content` LONGTEXT,
  `category` VARCHAR(100) DEFAULT 'General',
  `author` VARCHAR(200) DEFAULT NULL,
  `image_url` VARCHAR(500) DEFAULT NULL,
  `featured` TINYINT(1) DEFAULT 0,
  `status` VARCHAR(20) DEFAULT 'draft',
  `views` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ────────────────────────────────────────────────
-- 2. STAFF DATABASE (igangaschool_staffs)
-- ────────────────────────────────────────────────
USE `igangaschool_staffs`;

-- ── news_categories (staff DB) ──
-- Same fix as website DB: drop and recreate with correct columns
DROP TABLE IF EXISTS `news_categories`;
CREATE TABLE `news_categories` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `sort_order` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_nc_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `news_categories` (`name`, `slug`, `description`, `sort_order`, `is_active`) VALUES
('General','general','General news and announcements',1,1),
('Academic','academic','Academic news and updates',2,1),
('Events','events','Upcoming and past events',3,1),
('Sports','sports','Sports news and results',4,1),
('Announcements','announcements','Important announcements',5,1),
('Staff','staff','Staff-related news',6,1),
('Student Life','student-life','Student life and activities',7,1);

-- ── Director News (staff DB master copy for news management) ──
CREATE TABLE IF NOT EXISTS `director_news` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(300) NOT NULL,
  `slug` VARCHAR(300),
  `content` LONGTEXT,
  `excerpt` TEXT,
  `featured_image` VARCHAR(500),
  `author_id` INT(11),
  `author_name` VARCHAR(200),
  `status` ENUM('draft','published','archived') DEFAULT 'draft',
  `published_at` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dn_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── News (staff DB) ──
-- The dump table is missing columns that dashboards/news.php needs.
-- Add missing columns to existing table.
ALTER TABLE `news` ADD COLUMN IF NOT EXISTS `summary` TEXT AFTER `slug`;
ALTER TABLE `news` ADD COLUMN IF NOT EXISTS `category` VARCHAR(100) DEFAULT 'General' AFTER `excerpt`;
ALTER TABLE `news` ADD COLUMN IF NOT EXISTS `tags` VARCHAR(500) AFTER `category`;
ALTER TABLE `news` ADD COLUMN IF NOT EXISTS `is_featured` TINYINT(1) DEFAULT 0 AFTER `status`;
ALTER TABLE `news` ADD COLUMN IF NOT EXISTS `scheduled_at` DATETIME NULL AFTER `published_at`;
ALTER TABLE `news` ADD COLUMN IF NOT EXISTS `archived_at` DATETIME NULL AFTER `scheduled_at`;
ALTER TABLE `news` ADD COLUMN IF NOT EXISTS `views` INT DEFAULT 0 AFTER `author_name`;

-- Ensure status ENUM includes 'scheduled' and 'archived'
ALTER TABLE `news` MODIFY COLUMN `status` ENUM('draft','published','scheduled','archived') DEFAULT 'draft';

-- Create table only if it doesn't exist at all (with all columns)
CREATE TABLE IF NOT EXISTS `news` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(300) NOT NULL,
  `slug` VARCHAR(300),
  `summary` TEXT,
  `content` LONGTEXT,
  `featured_image` VARCHAR(500),
  `category` VARCHAR(100) DEFAULT 'General',
  `tags` VARCHAR(500),
  `excerpt` TEXT,
  `status` ENUM('draft','published','scheduled','archived') DEFAULT 'draft',
  `is_featured` TINYINT(1) DEFAULT 0,
  `published_at` DATETIME NULL,
  `scheduled_at` DATETIME NULL,
  `archived_at` DATETIME NULL,
  `author_id` INT(11),
  `author_name` VARCHAR(200),
  `views` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_news_slug` (`slug`),
  KEY `idx_news_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── News Views ──
CREATE TABLE IF NOT EXISTS `news_views` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `news_id` INT(11) NOT NULL,
  `user_id` INT(11) DEFAULT NULL,
  `user_type` VARCHAR(20) DEFAULT 'public',
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `viewed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nv_news` (`news_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── CMS tables (staff DB) ──
CREATE TABLE IF NOT EXISTS `cms_events` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `event_date` DATE,
  `event_type` VARCHAR(50) DEFAULT 'general',
  `location` VARCHAR(255),
  `start_time` TIME,
  `end_time` TIME,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_by` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cms_testimonials` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `content` TEXT NOT NULL,
  `author_name` VARCHAR(200) NOT NULL,
  `author_role` VARCHAR(100),
  `rating` INT DEFAULT 5,
  `is_featured` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cms_faqs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `question` TEXT NOT NULL,
  `answer` TEXT NOT NULL,
  `category` VARCHAR(100) DEFAULT 'General',
  `sort_order` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `cms_settings` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT,
  `setting_type` VARCHAR(50) DEFAULT 'text',
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cms_setting` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Institutional Alerts ──
CREATE TABLE IF NOT EXISTS `institutional_alerts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT,
  `alert_title` VARCHAR(255) DEFAULT NULL,
  `alert_message` TEXT DEFAULT NULL,
  `alert_type` VARCHAR(50) DEFAULT 'info',
  `severity` VARCHAR(20) DEFAULT 'info',
  `priority` VARCHAR(20) DEFAULT 'Medium',
  `category` VARCHAR(100) DEFAULT 'other',
  `department_code` VARCHAR(50) DEFAULT NULL,
  `target_role` VARCHAR(100),
  `source_url` VARCHAR(500) DEFAULT NULL,
  `is_auto_generated` TINYINT(1) DEFAULT 0,
  `is_read` TINYINT(1) DEFAULT 0,
  `is_resolved` TINYINT(1) DEFAULT 0,
  `expires_at` DATETIME DEFAULT NULL,
  `resolved_by` INT(11) DEFAULT NULL,
  `resolved_at` DATETIME DEFAULT NULL,
  `created_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ia_priority` (`priority`),
  KEY `idx_ia_resolved` (`is_resolved`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Alert Recipients ──
CREATE TABLE IF NOT EXISTS `alert_recipients` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `alert_id` INT(11) NOT NULL,
  `recipient_id` INT(11) NOT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ar_alert` (`alert_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Staff Activity Log ──
CREATE TABLE IF NOT EXISTS `staff_activity_log` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `staff_id` INT(11) DEFAULT NULL,
  `activity_type` VARCHAR(50) DEFAULT NULL,
  `activity_description` TEXT,
  `module_accessed` VARCHAR(100) DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sal_staff` (`staff_id`),
  KEY `idx_sal_time` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Staff Attendance ──
CREATE TABLE IF NOT EXISTS `staff_attendance` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `staff_id` INT(11) NOT NULL,
  `date` DATE NOT NULL,
  `time_in` TIME DEFAULT NULL,
  `time_out` TIME DEFAULT NULL,
  `status` ENUM('Present','Absent','Late','On Leave') DEFAULT 'Present',
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sa_staff` (`staff_id`),
  KEY `idx_sa_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Staff Departments ──
CREATE TABLE IF NOT EXISTS `staff_departments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `department_name` VARCHAR(200) NOT NULL,
  `department_code` VARCHAR(20) DEFAULT NULL,
  `department_level` INT DEFAULT 0,
  `head_of_department` INT(11) DEFAULT NULL,
  `description` TEXT,
  `status` ENUM('Active','Inactive') DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Staff Roles ──
-- The existing dump may have extra columns (role_level, dashboard_path, is_executive)
-- Ensure the base columns exist
CREATE TABLE IF NOT EXISTS `staff_roles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `role_name` VARCHAR(100) NOT NULL,
  `role_slug` VARCHAR(50) DEFAULT NULL,
  `description` TEXT,
  `permissions` TEXT,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sr_slug` (`role_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Expenses ──
CREATE TABLE IF NOT EXISTS `expenses` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `description` VARCHAR(500) NOT NULL,
  `amount` DECIMAL(14,2) DEFAULT 0.00,
  `category` VARCHAR(100) DEFAULT NULL,
  `department` VARCHAR(100) DEFAULT NULL,
  `expense_date` DATE DEFAULT NULL,
  `status` ENUM('pending','approved','paid','rejected') DEFAULT 'pending',
  `approved_by` INT(11) DEFAULT NULL,
  `created_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Student Application Requirements ──
CREATE TABLE IF NOT EXISTS `student_application_requirements` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_number` VARCHAR(50) NOT NULL,
  `requirement_name` VARCHAR(200) NOT NULL,
  `category` VARCHAR(100) DEFAULT 'General',
  `status` ENUM('Pending','Cleared','Missing','Submitted') DEFAULT 'Pending',
  `verified_by` VARCHAR(200) DEFAULT NULL,
  `verified_at` DATETIME DEFAULT NULL,
  `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sar_student` (`student_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Pending Students (DG approval queue) ──
CREATE TABLE IF NOT EXISTS `pending_students` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `first_name` VARCHAR(100) NOT NULL,
  `middle_name` VARCHAR(100) DEFAULT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `student_number` VARCHAR(50) DEFAULT NULL,
  `program` VARCHAR(200) DEFAULT NULL,
  `level` VARCHAR(20) DEFAULT '1',
  `intake_year` VARCHAR(10) DEFAULT NULL,
  `intake_period` VARCHAR(50) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `date_of_birth` DATE DEFAULT NULL,
  `submitted_by` INT(11) DEFAULT NULL,
  `status` ENUM('pending_approval','approved','rejected') DEFAULT 'pending_approval',
  `approved_by` INT(11) DEFAULT NULL,
  `approved_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ps_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Academic Programs ──
CREATE TABLE IF NOT EXISTS `academic_programs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `program_code` VARCHAR(20) NOT NULL,
  `program_name` VARCHAR(255) NOT NULL,
  `program_type` ENUM('Certificate','Diploma','Degree','Short Course') DEFAULT 'Diploma',
  `department` VARCHAR(100) DEFAULT NULL,
  `duration_years` DECIMAL(3,1) DEFAULT 2.0,
  `total_fee` DECIMAL(14,2) DEFAULT 0.00,
  `status` ENUM('Active','Inactive') DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Quality Assurance ──
-- FIX: The existing dump creates this with columns (review_type, reviewer, score)
-- that don't match what the PHP code expects (review_area, reviewed_by, review_date).
-- DROP and recreate with correct columns matching dashboards/quality-assurance.php
-- and dashboards/director-academics.php
DROP TABLE IF EXISTS `quality_assurance`;
CREATE TABLE `quality_assurance` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `review_title` VARCHAR(300) DEFAULT NULL,
  `department` VARCHAR(200) DEFAULT NULL,
  `review_area` VARCHAR(200) DEFAULT NULL,
  `findings` TEXT,
  `recommendations` TEXT,
  `reviewed_by` INT(11) DEFAULT NULL,
  `review_date` DATE DEFAULT NULL,
  `status` ENUM('Draft','Pending','Pass','Fail','Needs Improvement','Completed','Reviewed') DEFAULT 'Draft',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_qa_dept` (`department`),
  KEY `idx_qa_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Quality Assurance Reviews (used by CEO dashboard) ──
-- Ensure this table has the reviewed_by column that ceo.php JOINs on
CREATE TABLE IF NOT EXISTS `quality_assurance_reviews` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `review_title` VARCHAR(300) DEFAULT NULL,
  `description` TEXT,
  `reviewed_by` INT(11) DEFAULT NULL,
  `review_date` DATE DEFAULT NULL,
  `status` VARCHAR(50) DEFAULT 'Pending',
  `findings` TEXT,
  `recommendations` TEXT,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_qar_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Performance Indicators ──
-- FIX: Existing dump is missing indicator_category column that quality-assurance.php uses
-- DROP and recreate with correct columns
DROP TABLE IF EXISTS `performance_indicators`;
CREATE TABLE `performance_indicators` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `indicator_name` VARCHAR(200) DEFAULT NULL,
  `indicator_category` VARCHAR(100) DEFAULT 'General',
  `target_value` DECIMAL(12,2) DEFAULT NULL,
  `actual_value` DECIMAL(12,2) DEFAULT NULL,
  `period` VARCHAR(50) DEFAULT NULL,
  `department` VARCHAR(100) DEFAULT NULL,
  `status` VARCHAR(30) DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pi_status` (`status`),
  KEY `idx_pi_category` (`indicator_category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default performance indicators
INSERT IGNORE INTO `performance_indicators` (`indicator_name`, `indicator_category`, `target_value`, `status`) VALUES
('Student Pass Rate','Academic',75.00,'active'),
('Staff Attendance Rate','HR',90.00,'active'),
('Student Enrollment Growth','Admissions',10.00,'active'),
('Fee Collection Rate','Finance',85.00,'active'),
('Library Utilization','Facilities',60.00,'active'),
('Clinical Placement Completion','Clinical',80.00,'active'),
('ICT Infrastructure Uptime','ICT',99.00,'active'),
('Student Satisfaction Score','Quality',4.00,'active');

-- ── Curriculum Development ──
CREATE TABLE IF NOT EXISTS `curriculum_development` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `program_name` VARCHAR(100) NOT NULL,
  `course_code` VARCHAR(50) NOT NULL,
  `course_title` VARCHAR(150) NOT NULL,
  `credit_units` INT DEFAULT 0,
  `status` VARCHAR(50) DEFAULT 'Draft',
  `developed_by` VARCHAR(100) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Academic Curriculum Development ──
CREATE TABLE IF NOT EXISTS `academic_curriculum_development` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `program_code` VARCHAR(50) NOT NULL,
  `revision_number` INT DEFAULT 1,
  `academic_year` VARCHAR(20) DEFAULT NULL,
  `description` TEXT,
  `status` VARCHAR(50) DEFAULT 'Draft',
  `created_by` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_program` (`program_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Approval Workflow tables ──
CREATE TABLE IF NOT EXISTS `approval_requests` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `workflow_id` INT(11) DEFAULT 0,
  `request_number` VARCHAR(100) DEFAULT '',
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `priority` VARCHAR(50) DEFAULT 'Medium',
  `requester_id` INT(11) DEFAULT 0,
  `requester_name` VARCHAR(200) DEFAULT '',
  `requester_role` VARCHAR(100) DEFAULT '',
  `current_stage_id` INT(11) DEFAULT 0,
  `current_stage_order` INT(11) DEFAULT 1,
  `status` VARCHAR(50) DEFAULT 'Active',
  `reference_type` VARCHAR(100) DEFAULT NULL,
  `reference_id` INT(11) DEFAULT NULL,
  `reference_url` VARCHAR(500) DEFAULT NULL,
  `approved_by` INT(11) DEFAULT NULL,
  `final_approval_at` DATETIME DEFAULT NULL,
  `rejection_reason` TEXT,
  `requester_type` VARCHAR(20) DEFAULT 'staff',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ar_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `approval_actions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `request_id` INT(11) DEFAULT 0,
  `approval_request_id` INT(11) DEFAULT 0,
  `stage_id` INT(11) DEFAULT 0,
  `action_by` INT(11) DEFAULT 0,
  `approver_id` INT(11) DEFAULT 0,
  `action_type` VARCHAR(50) DEFAULT '',
  `action` VARCHAR(20) DEFAULT '',
  `comments` TEXT,
  `notes` TEXT,
  `decision` VARCHAR(50) DEFAULT '',
  `previous_stage_order` INT(11) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_aa_request` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `approval_workflows` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `workflow_name` VARCHAR(255) DEFAULT '',
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT,
  `category` VARCHAR(100) DEFAULT '',
  `target_table` VARCHAR(100) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `approval_stages` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `workflow_id` INT(11) NOT NULL,
  `stage_name` VARCHAR(255) NOT NULL,
  `stage_order` INT(11) DEFAULT 1,
  `approver_role` VARCHAR(100) DEFAULT '',
  `assigned_role_id` INT(11) DEFAULT 0,
  `assigned_role_name` VARCHAR(100) DEFAULT '',
  `is_final` TINYINT(1) DEFAULT 0,
  `is_mandatory` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_as_workflow` (`workflow_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Staff Notifications ──
CREATE TABLE IF NOT EXISTS `staff_notifications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT,
  `link` VARCHAR(500) DEFAULT NULL,
  `type` VARCHAR(50) DEFAULT 'info',
  `icon` VARCHAR(50) DEFAULT 'fas fa-bell',
  `target_audience` VARCHAR(50) DEFAULT 'all',
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `staff_notification_reads` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `notification_id` INT(11) NOT NULL,
  `staff_id` INT(11) NOT NULL,
  `read_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_snr_notif` (`notification_id`),
  KEY `idx_snr_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Academic Records ──
CREATE TABLE IF NOT EXISTS `academic_records` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) NOT NULL,
  `course_id` INT(11) DEFAULT NULL,
  `lecturer_id` INT(11) DEFAULT NULL,
  `assessment_type` VARCHAR(50) DEFAULT NULL,
  `marks` DECIMAL(5,2) DEFAULT NULL,
  `score` DECIMAL(5,2) DEFAULT NULL,
  `total_marks` DECIMAL(5,2) DEFAULT NULL,
  `grade` VARCHAR(10) DEFAULT NULL,
  `semester` VARCHAR(50) DEFAULT NULL,
  `academic_year` VARCHAR(20) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ar_student` (`student_id`),
  KEY `idx_ar_lecturer` (`lecturer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Academic Course Catalog ──
CREATE TABLE IF NOT EXISTS `academic_course_catalog` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `course_code` VARCHAR(20) NOT NULL,
  `course_name` VARCHAR(200) NOT NULL,
  `course_title` VARCHAR(200) DEFAULT NULL,
  `credits` INT DEFAULT 0,
  `credit_units` INT DEFAULT 0,
  `program_code` VARCHAR(20) DEFAULT NULL,
  `year_of_study` INT DEFAULT NULL,
  `semester` VARCHAR(50) DEFAULT NULL,
  `department` VARCHAR(100) DEFAULT NULL,
  `level` INT DEFAULT 1,
  `status` ENUM('Active','Inactive') DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_acc_code` (`course_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Academic Timetable ──
CREATE TABLE IF NOT EXISTS `academic_timetable` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `course_id` INT(11) DEFAULT NULL,
  `lecturer_id` INT(11) DEFAULT NULL,
  `day_of_week` VARCHAR(20) DEFAULT NULL,
  `start_time` TIME DEFAULT NULL,
  `end_time` TIME DEFAULT NULL,
  `room` VARCHAR(100) DEFAULT NULL,
  `semester` VARCHAR(50) DEFAULT NULL,
  `academic_year` VARCHAR(20) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Course Assignments ──
CREATE TABLE IF NOT EXISTS `course_assignments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `course_id` INT(11) DEFAULT NULL,
  `course_code` VARCHAR(20) DEFAULT NULL,
  `lecturer_id` INT(11) DEFAULT NULL,
  `academic_year` VARCHAR(20) DEFAULT NULL,
  `semester` VARCHAR(50) DEFAULT NULL,
  `assigned_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Courses ──
CREATE TABLE IF NOT EXISTS `courses` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `course_code` VARCHAR(20) NOT NULL,
  `course_name` VARCHAR(200) NOT NULL,
  `course_title` VARCHAR(200) DEFAULT NULL,
  `department` VARCHAR(100) DEFAULT NULL,
  `credit_units` INT DEFAULT 0,
  `status` ENUM('Active','Inactive') DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Generated Documents ──
CREATE TABLE IF NOT EXISTS `generated_documents` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `document_type` VARCHAR(50) NOT NULL,
  `student_id` INT(11) DEFAULT NULL,
  `generated_by` INT(11) DEFAULT NULL,
  `file_path` VARCHAR(500) DEFAULT NULL,
  `status` VARCHAR(20) DEFAULT 'generated',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Institutional Alerts (add missing columns to existing table) ──
-- The dump table has: id, title, description, alert_type, priority, department_code,
-- source, is_resolved, resolved_by, resolved_at, created_by, created_at
-- PHP code also needs: message, expires_at, is_read, severity, category,
-- target_role, source_url, is_auto_generated, alert_title, alert_message
ALTER TABLE `institutional_alerts` ADD COLUMN IF NOT EXISTS `message` TEXT AFTER `title`;
ALTER TABLE `institutional_alerts` ADD COLUMN IF NOT EXISTS `alert_title` VARCHAR(255) DEFAULT NULL AFTER `message`;
ALTER TABLE `institutional_alerts` ADD COLUMN IF NOT EXISTS `alert_message` TEXT DEFAULT NULL AFTER `alert_title`;
ALTER TABLE `institutional_alerts` ADD COLUMN IF NOT EXISTS `severity` VARCHAR(20) DEFAULT 'info' AFTER `alert_type`;
ALTER TABLE `institutional_alerts` ADD COLUMN IF NOT EXISTS `category` VARCHAR(100) DEFAULT 'other' AFTER `priority`;
ALTER TABLE `institutional_alerts` ADD COLUMN IF NOT EXISTS `target_role` VARCHAR(100) DEFAULT NULL AFTER `department_code`;
ALTER TABLE `institutional_alerts` ADD COLUMN IF NOT EXISTS `source_url` VARCHAR(500) DEFAULT NULL AFTER `source`;
ALTER TABLE `institutional_alerts` ADD COLUMN IF NOT EXISTS `is_auto_generated` TINYINT(1) DEFAULT 0 AFTER `source_url`;
ALTER TABLE `institutional_alerts` ADD COLUMN IF NOT EXISTS `is_read` TINYINT(1) DEFAULT 0 AFTER `is_auto_generated`;
ALTER TABLE `institutional_alerts` ADD COLUMN IF NOT EXISTS `expires_at` DATETIME DEFAULT NULL AFTER `is_resolved`;

-- ── Staff Audit Logs ──
CREATE TABLE IF NOT EXISTS `staff_audit_logs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) DEFAULT NULL,
  `action` VARCHAR(100) DEFAULT NULL,
  `entity_type` VARCHAR(100) DEFAULT NULL,
  `entity_id` INT(11) DEFAULT NULL,
  `description` TEXT,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sal_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Departments (for CEO dashboard) ──
CREATE TABLE IF NOT EXISTS `departments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(200) NOT NULL,
  `code` VARCHAR(20) DEFAULT NULL,
  `head_id` INT(11) DEFAULT NULL,
  `description` TEXT,
  `status` ENUM('Active','Inactive') DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Store Requests (for DG approvals) ──
CREATE TABLE IF NOT EXISTS `store_requests` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `request_number` VARCHAR(50) NOT NULL,
  `requested_by` INT(11) DEFAULT NULL,
  `requester_name` VARCHAR(200) DEFAULT '',
  `department` VARCHAR(100) DEFAULT '',
  `urgency` ENUM('low','medium','high','urgent') DEFAULT 'medium',
  `description` TEXT,
  `status` ENUM('pending','forwarded','pending_approval','approved','rejected') DEFAULT 'pending',
  `approved_by` INT(11) DEFAULT NULL,
  `approved_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sr_number` (`request_number`),
  KEY `idx_sr_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Transport Trips (for DG approvals) ──
CREATE TABLE IF NOT EXISTS `transport_trips` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `route_id` INT(11) DEFAULT NULL,
  `vehicle_id` INT(11) DEFAULT NULL,
  `trip_date` DATE DEFAULT NULL,
  `departure_time` TIME DEFAULT NULL,
  `arrival_time` TIME DEFAULT NULL,
  `passengers` INT DEFAULT 0,
  `purpose` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
  `dg_approval_status` ENUM('pending','approved','rejected') DEFAULT 'pending',
  `dg_approved_by` INT(11) DEFAULT NULL,
  `dg_approved_at` DATETIME DEFAULT NULL,
  `created_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Transport Routes ──
CREATE TABLE IF NOT EXISTS `transport_routes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `route_name` VARCHAR(200) NOT NULL,
  `start_location` VARCHAR(200) DEFAULT NULL,
  `end_location` VARCHAR(200) DEFAULT NULL,
  `distance_km` DECIMAL(8,2) DEFAULT NULL,
  `fare` DECIMAL(10,2) DEFAULT 0.00,
  `status` ENUM('active','inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Transport Vehicles ──
CREATE TABLE IF NOT EXISTS `transport_vehicles` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `vehicle_name` VARCHAR(200) NOT NULL,
  `plate_number` VARCHAR(20) DEFAULT NULL,
  `capacity` INT DEFAULT 0,
  `status` ENUM('active','maintenance','retired') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ────────────────────────────────────────────────
-- 3. STUDENTS DATABASE (igangaschool_students)
-- ────────────────────────────────────────────────
USE `igangaschool_students`;

-- ── Student Notifications ──
CREATE TABLE IF NOT EXISTS `student_notifications` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) NOT NULL,
  `type` VARCHAR(50) DEFAULT 'info',
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT,
  `priority` VARCHAR(20) DEFAULT 'Normal',
  `is_read` TINYINT(1) DEFAULT 0,
  `link` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sn_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Announcements ──
CREATE TABLE IF NOT EXISTS `announcements` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `body` TEXT NOT NULL,
  `target_audience` ENUM('All','Nursing','Midwifery','Year1','Year2','Year3','Staff') DEFAULT 'All',
  `priority` ENUM('Normal','High','Urgent') DEFAULT 'Normal',
  `posted_by` INT(11) DEFAULT NULL,
  `expires_at` DATE DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Payments ──
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) NOT NULL,
  `amount_received` DECIMAL(14,2) DEFAULT 0.00,
  `payment_date` DATE DEFAULT NULL,
  `payment_method` VARCHAR(50) DEFAULT NULL,
  `reference_number` VARCHAR(100) DEFAULT NULL,
  `status` ENUM('pending','verified','approved','rejected') DEFAULT 'pending',
  `received_by` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_p_student` (`student_id`),
  KEY `idx_p_date` (`payment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Student Invoices ──
CREATE TABLE IF NOT EXISTS `student_invoices` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) NOT NULL,
  `invoice_number` VARCHAR(50) DEFAULT NULL,
  `total_amount` DECIMAL(14,2) DEFAULT 0.00,
  `amount_paid` DECIMAL(14,2) DEFAULT 0.00,
  `balance` DECIMAL(14,2) DEFAULT 0.00,
  `status` ENUM('pending','partial','paid','overdue','cancelled') DEFAULT 'pending',
  `due_date` DATE DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_si_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Students (ensure table exists if somehow missing) ──
CREATE TABLE IF NOT EXISTS `students` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_number` VARCHAR(50) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `surname` VARCHAR(100) NOT NULL,
  `full_name` VARCHAR(255) DEFAULT NULL,
  `gender` ENUM('Male','Female','Other') DEFAULT NULL,
  `date_of_birth` DATE DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `program` VARCHAR(200) DEFAULT NULL,
  `course` VARCHAR(200) DEFAULT NULL,
  `year` INT DEFAULT 1,
  `level` VARCHAR(20) DEFAULT '1',
  `status` ENUM('Active','Inactive','Graduated','Suspended','Expelled') DEFAULT 'Active',
  `admission_date` DATE DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_stu_number` (`student_number`),
  KEY `idx_stu_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ══════════════════════════════════════════════════════════════
-- DONE! All dashboard tables ensured with correct column names.
-- ══════════════════════════════════════════════════════════════
