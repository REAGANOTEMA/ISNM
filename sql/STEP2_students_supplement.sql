-- ============================================================
-- ISNM STEP 2: RUN IN igangaschoolofl_students_db
-- Adds all missing tables needed by all dashboards
-- Safe to re-run (IF NOT EXISTS)
-- ============================================================
USE `igangaschoolofl_students_db`;

CREATE TABLE IF NOT EXISTS `academic_registrar_activity_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `activity` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `students_trash` (
  `id` int NOT NULL AUTO_INCREMENT,
  `original_id` int NOT NULL,
  `student_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `full_name` varchar(300) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `course` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `snapshot_data` longtext COLLATE utf8mb4_unicode_ci,
  `deleted_by` int DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `restored_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_original_id` (`original_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `graduation_candidates` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `graduation_date` date DEFAULT NULL,
  `status` enum('Pending','Cleared','Graduated','Deferred') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `clearance_bursar` tinyint(1) DEFAULT 0,
  `clearance_library` tinyint(1) DEFAULT 0,
  `clearance_registrar` tinyint(1) DEFAULT 0,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `clinical_placements_students` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `placement_site` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `supervisor_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `competency_score` decimal(5,2) DEFAULT NULL,
  `logbook_submitted` tinyint(1) DEFAULT 0,
  `supervisor_evaluation` text COLLATE utf8mb4_unicode_ci,
  `status` enum('Scheduled','Active','Completed','Withdrawn') COLLATE utf8mb4_unicode_ci DEFAULT 'Scheduled',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `student_discipline_records` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `case_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `incident_date` date DEFAULT NULL,
  `incident_type` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `action_taken` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Pending','Resolved','Closed') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `recorded_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_case` (`case_number`),
  KEY `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `student_hostel_allocations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `hostel_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `room_number` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `allocation_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `monthly_fee` decimal(10,2) DEFAULT 0.00,
  `status` enum('Active','Vacated','Transferred') COLLATE utf8mb4_unicode_ci DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `student_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `request_type` enum('Leave of Absence','Deferral','Transfer','Withdrawal','Other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `reason` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `supporting_doc` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `reviewed_by` int DEFAULT NULL,
  `review_notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `timetable` (
  `id` int NOT NULL AUTO_INCREMENT,
  `program` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `year_of_study` int DEFAULT 1,
  `semester` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday') COLLATE utf8mb4_unicode_ci NOT NULL,
  `time_slot` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_code` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lecturer` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `room` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `academic_year` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_program` (`program`),
  KEY `idx_day` (`day_of_week`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `announcements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_audience` enum('All','Nursing','Midwifery','Year1','Year2','Year3','Staff') COLLATE utf8mb4_unicode_ci DEFAULT 'All',
  `priority` enum('Normal','High','Urgent') COLLATE utf8mb4_unicode_ci DEFAULT 'Normal',
  `posted_by` int DEFAULT NULL,
  `expires_at` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_target` (`target_audience`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `library_fines` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `book_title` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `borrow_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `fine_amount` decimal(10,2) DEFAULT 0.00,
  `paid` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `department_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `request_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `from_department` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `to_department` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Store',
  `item_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int DEFAULT 1,
  `unit` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purpose` text COLLATE utf8mb4_unicode_ci,
  `urgency` enum('Normal','Urgent','Emergency') COLLATE utf8mb4_unicode_ci DEFAULT 'Normal',
  `status` enum('Pending','Approved','Rejected','Fulfilled') COLLATE utf8mb4_unicode_ci DEFAULT 'Pending',
  `requested_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_req_num` (`request_number`),
  KEY `idx_from_dept` (`from_department`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes to students table if missing
ALTER TABLE `students`
  ADD INDEX IF NOT EXISTS `idx_full_name` (`full_name`(100)),
  ADD INDEX IF NOT EXISTS `idx_set_name` (`set_name`),
  ADD INDEX IF NOT EXISTS `idx_intake_date` (`intake_date`);

SELECT 'Step 2 complete: Students DB supplement tables ready.' AS result;
