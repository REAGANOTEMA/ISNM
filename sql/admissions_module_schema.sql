-- =============================================================================
-- ISNM Admissions Module — Complete Schema Migration
-- Target DB: igangaschoolofl_staffs_db (all admissions data lives here)
-- Integrates with: students_db.programs, students_db.students, website_db.student_applications
-- Safe to run multiple times (uses IF NOT EXISTS / CREATE OR REPLACE)
-- =============================================================================

-- ── 1. ACADEMIC PROGRAMS (master list, duplicates students_db.programs for admissions) ──
CREATE TABLE IF NOT EXISTS `academic_programs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `program_code` VARCHAR(20) NOT NULL UNIQUE,
  `program_name` VARCHAR(255) NOT NULL,
  `program_type` ENUM('Certificate','Diploma','Degree','Short Course') NOT NULL DEFAULT 'Diploma',
  `department` VARCHAR(100) DEFAULT NULL,
  `duration_years` DECIMAL(3,1) NOT NULL DEFAULT 2.0,
  `total_fee` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_program_status` (`status`),
  INDEX `idx_program_type` (`program_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. INTAKE PERIODS ──
CREATE TABLE IF NOT EXISTS `intakes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `intake_name` VARCHAR(100) NOT NULL,
  `intake_month` VARCHAR(20) NOT NULL,
  `intake_year` YEAR NOT NULL,
  `application_start` DATE DEFAULT NULL,
  `application_deadline` DATE DEFAULT NULL,
  `status` ENUM('Open','Closed','Upcoming') NOT NULL DEFAULT 'Upcoming',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_intake` (`intake_month`,`intake_year`),
  INDEX `idx_intake_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. APPLICANTS (core table — every admission starts here) ──
CREATE TABLE IF NOT EXISTS `applicants` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `application_number` VARCHAR(30) NOT NULL UNIQUE,
  `student_number` VARCHAR(50) DEFAULT NULL UNIQUE,
  `registration_number` VARCHAR(50) DEFAULT NULL,
  `portal_username` VARCHAR(100) DEFAULT NULL,
  `portal_password_hash` VARCHAR(255) DEFAULT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(100) DEFAULT NULL,
  `middle_name` VARCHAR(100) DEFAULT NULL,
  `surname` VARCHAR(100) DEFAULT NULL,
  `gender` ENUM('Male','Female','Other') DEFAULT NULL,
  `date_of_birth` DATE DEFAULT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `alternative_phone` VARCHAR(20) DEFAULT NULL,
  `nationality` VARCHAR(100) DEFAULT 'Ugandan',
  `district` VARCHAR(100) DEFAULT NULL,
  `county` VARCHAR(100) DEFAULT NULL,
  `religion` VARCHAR(50) DEFAULT NULL,
  `marital_status` ENUM('Single','Married','Divorced','Widowed') DEFAULT 'Single',
  `address` TEXT DEFAULT NULL,
  `photo_path` VARCHAR(500) DEFAULT NULL,
  `program_id` INT DEFAULT NULL,
  `intake` VARCHAR(50) DEFAULT NULL,
  `intake_id` INT DEFAULT NULL,
  `application_source` ENUM('Online','Manual','Walk-in','Referral','Other') DEFAULT 'Online',
  `status` ENUM('New','Under Review','Waiting for Documents','Requirements Verified','Interview Scheduled','Approved','Rejected','Registered','Withdrawn') NOT NULL DEFAULT 'New',
  `rejection_reason` TEXT DEFAULT NULL,
  `previous_education` TEXT DEFAULT NULL,
  `previous_institution` VARCHAR(255) DEFAULT NULL,
  `previous_qualification` VARCHAR(255) DEFAULT NULL,
  `last_attended_school` VARCHAR(255) DEFAULT NULL,
  `guardian_name` VARCHAR(200) DEFAULT NULL,
  `guardian_phone` VARCHAR(20) DEFAULT NULL,
  `guardian_email` VARCHAR(100) DEFAULT NULL,
  `guardian_relationship` VARCHAR(50) DEFAULT NULL,
  `emergency_contact_name` VARCHAR(100) DEFAULT NULL,
  `emergency_contact_phone` VARCHAR(20) DEFAULT NULL,
  `submitted_at` TIMESTAMP NULL DEFAULT NULL,
  `reviewed_by` INT DEFAULT NULL,
  `reviewed_at` TIMESTAMP NULL DEFAULT NULL,
  `approved_by` INT DEFAULT NULL,
  `approved_at` TIMESTAMP NULL DEFAULT NULL,
  `registered_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_applicant_status` (`status`),
  INDEX `idx_applicant_program` (`program_id`),
  INDEX `idx_applicant_intake` (`intake`),
  INDEX `idx_applicant_created` (`created_at`),
  INDEX `idx_applicant_name` (`full_name`),
  INDEX `idx_applicant_email` (`email`),
  INDEX `idx_applicant_phone` (`phone`),
  INDEX `idx_applicant_nationality` (`nationality`),
  INDEX `idx_applicant_district` (`district`),
  INDEX `idx_applicant_gender` (`gender`),
  CONSTRAINT `fk_applicant_program` FOREIGN KEY (`program_id`) REFERENCES `academic_programs`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 4. ADMISSION REQUIREMENTS (master checklist) ──
CREATE TABLE IF NOT EXISTS `admission_requirements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `requirement_name` VARCHAR(255) NOT NULL,
  `type` ENUM('Document','Certificate','ID','Photo','Form','Other') NOT NULL DEFAULT 'Document',
  `display_order` INT NOT NULL DEFAULT 0,
  `is_mandatory` TINYINT(1) NOT NULL DEFAULT 1,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_req_active` (`is_active`),
  INDEX `idx_req_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 5. PER-APPLICANT REQUIREMENT STATUS ──
CREATE TABLE IF NOT EXISTS `applicant_requirement_status` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `applicant_id` INT NOT NULL,
  `requirement_id` INT NOT NULL,
  `status` ENUM('Not Submitted','Pending','Submitted','Verified','Rejected','Missing','Received') NOT NULL DEFAULT 'Not Submitted',
  `remarks` TEXT DEFAULT NULL,
  `submitted_by` INT DEFAULT NULL,
  `submitted_at` TIMESTAMP NULL DEFAULT NULL,
  `verified_by` INT DEFAULT NULL,
  `verified_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_app_req` (`applicant_id`,`requirement_id`),
  INDEX `idx_ars_status` (`status`),
  CONSTRAINT `fk_ars_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicants`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ars_requirement` FOREIGN KEY (`requirement_id`) REFERENCES `admission_requirements`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 6. STUDENT DOCUMENTS (uploaded files) ──
CREATE TABLE IF NOT EXISTS `student_documents` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `applicant_id` INT NOT NULL,
  `requirement_id` INT DEFAULT NULL,
  `document_name` VARCHAR(255) NOT NULL,
  `document_type` VARCHAR(100) DEFAULT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `file_size` INT DEFAULT NULL,
  `file_mime` VARCHAR(100) DEFAULT NULL,
  `verification_status` ENUM('Pending','Verified','Rejected') NOT NULL DEFAULT 'Pending',
  `verification_remarks` TEXT DEFAULT NULL,
  `verified_by` INT DEFAULT NULL,
  `verified_at` TIMESTAMP NULL DEFAULT NULL,
  `document_status` ENUM('Active','Deleted') NOT NULL DEFAULT 'Active',
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_doc_applicant` (`applicant_id`),
  INDEX `idx_doc_verification` (`verification_status`),
  CONSTRAINT `fk_doc_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicants`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 7. REQUIREMENT CHANGE HISTORY (audit trail) ──
CREATE TABLE IF NOT EXISTS `requirement_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `applicant_id` INT NOT NULL,
  `requirement_id` INT DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `previous_status` VARCHAR(50) DEFAULT NULL,
  `new_status` VARCHAR(50) DEFAULT NULL,
  `performed_by` INT DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_rh_applicant` (`applicant_id`),
  INDEX `idx_rh_created` (`created_at`),
  CONSTRAINT `fk_rh_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicants`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 8. ADMISSION ACTIVITY LOGS (general audit) ──
CREATE TABLE IF NOT EXISTS `admission_activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `applicant_id` INT DEFAULT NULL,
  `user_id` INT DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_aal_applicant` (`applicant_id`),
  INDEX `idx_aal_user` (`user_id`),
  INDEX `idx_aal_action` (`action`),
  INDEX `idx_aal_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 9. STUDENT ADMISSION TRACKING (per-applicant progress summary) ──
CREATE TABLE IF NOT EXISTS `student_admission_tracking` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_number` VARCHAR(50) DEFAULT NULL,
  `application_number` VARCHAR(30) NOT NULL,
  `applicant_id` INT DEFAULT NULL,
  `program` VARCHAR(255) DEFAULT NULL,
  `intake` VARCHAR(50) DEFAULT NULL,
  `admission_date` DATE DEFAULT NULL,
  `admission_status` ENUM('Pending','Under Review','Requirements Pending','Approved','Rejected','Registered') NOT NULL DEFAULT 'Pending',
  `requirements_total` INT NOT NULL DEFAULT 0,
  `requirements_completed` INT NOT NULL DEFAULT 0,
  `documents_uploaded` INT NOT NULL DEFAULT 0,
  `interview_scheduled` TINYINT(1) NOT NULL DEFAULT 0,
  `interview_date` DATETIME DEFAULT NULL,
  `interview_notes` TEXT DEFAULT NULL,
  `communication_count` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_tracking_app` (`application_number`),
  INDEX `idx_tracking_status` (`admission_status`),
  INDEX `idx_tracking_applicant` (`applicant_id`),
  CONSTRAINT `fk_tracking_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicants`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 10. ADMISSION NOTIFICATIONS ──
CREATE TABLE IF NOT EXISTS `admission_notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `applicant_id` INT DEFAULT NULL,
  `user_id` INT DEFAULT NULL,
  `type` ENUM('info','success','warning','danger') NOT NULL DEFAULT 'info',
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `link` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_notif_applicant` (`applicant_id`),
  INDEX `idx_notif_user` (`user_id`),
  INDEX `idx_notif_read` (`is_read`),
  INDEX `idx_notif_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 11. ADMISSION INTERVIEWS ──
CREATE TABLE IF NOT EXISTS `admission_interviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `applicant_id` INT NOT NULL,
  `interviewer_id` INT DEFAULT NULL,
  `interview_date` DATETIME NOT NULL,
  `interview_mode` ENUM('In-Person','Online','Phone') NOT NULL DEFAULT 'In-Person',
  `interview_link` VARCHAR(500) DEFAULT NULL,
  `interview_score` DECIMAL(5,2) DEFAULT NULL,
  `interview_outcome` ENUM('Pass','Fail','Pending','Reschedule') DEFAULT 'Pending',
  `notes` TEXT DEFAULT NULL,
  `recommendation` TEXT DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_int_applicant` (`applicant_id`),
  INDEX `idx_int_date` (`interview_date`),
  INDEX `idx_int_outcome` (`interview_outcome`),
  CONSTRAINT `fk_int_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicants`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 12. ADMISSION COMMUNICATIONS ──
CREATE TABLE IF NOT EXISTS `admission_communications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `applicant_id` INT NOT NULL,
  `sender_id` INT DEFAULT NULL,
  `communication_type` ENUM('Email','SMS','Portal','WhatsApp','Internal Note') NOT NULL DEFAULT 'Portal',
  `subject` VARCHAR(255) DEFAULT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('Sent','Delivered','Read','Failed') DEFAULT 'Sent',
  `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_com_applicant` (`applicant_id`),
  INDEX `idx_com_type` (`communication_type`),
  INDEX `idx_com_sent` (`sent_at`),
  CONSTRAINT `fk_com_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicants`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 13. ADMISSION DECISIONS (approve/reject decision history) ──
CREATE TABLE IF NOT EXISTS `admission_decisions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `applicant_id` INT NOT NULL,
  `decision` ENUM('Approved','Rejected','Deferred','Waitlisted') NOT NULL,
  `decision_reason` TEXT DEFAULT NULL,
  `decided_by` INT DEFAULT NULL,
  `decided_at` TIMESTAMP NULL DEFAULT NULL,
  `notified_applicant` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_dec_applicant` (`applicant_id`),
  INDEX `idx_dec_decision` (`decision`),
  CONSTRAINT `fk_dec_applicant` FOREIGN KEY (`applicant_id`) REFERENCES `applicants`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 14. ADMISSION REPORTS CACHE (pre-computed stats) ──
CREATE TABLE IF NOT EXISTS `admission_reports_cache` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `report_key` VARCHAR(100) NOT NULL UNIQUE,
  `report_data` JSON DEFAULT NULL,
  `generated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_cache_key` (`report_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── SEED DEFAULT ADMISSION REQUIREMENTS (if empty) ──
INSERT IGNORE INTO `admission_requirements` (`id`,`requirement_name`,`type`,`display_order`,`is_mandatory`) VALUES
(1,'Academic Certificates (UCE/UACE)','Document',1,1),
(2,'Result Slips','Document',2,1),
(3,'National ID (or Birth Certificate)','ID',3,1),
(4,'Passport Photos (2)','Photo',4,1),
(5,'Birth Certificate','Document',5,1),
(6,'Recommendation Letter','Document',6,1),
(7,'Medical Examination Form','Form',7,1),
(8,'Previous School Transcript','Document',8,0),
(9,'Baptism Card (if applicable)','Document',9,0),
(10,'Letter of Sponsorship','Document',10,0);

-- ── MIGRATE EXISTING INTAKES TABLE (if created by academic-registrar.php with old schema) ──
-- Adds missing columns if the table already exists without them (e.g. intake_month, intake_year)
SET @col_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='intakes' AND COLUMN_NAME='intake_month');
SET @migrate_sql = IF(@col_exists = 0,
  'ALTER TABLE intakes
    ADD COLUMN intake_month VARCHAR(20) NOT NULL AFTER intake_name,
    ADD COLUMN intake_year YEAR NOT NULL DEFAULT \'2026\' AFTER intake_month,
    ADD COLUMN application_start DATE DEFAULT NULL AFTER intake_year,
    ADD COLUMN application_deadline DATE DEFAULT NULL AFTER application_start,
    MODIFY status ENUM(\'Open\',\'Closed\',\'Upcoming\') NOT NULL DEFAULT \'Upcoming\'',
  'SELECT 1');
PREPARE stmt FROM @migrate_sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ── SEED DEFAULT INTAKES (if empty) ──
INSERT IGNORE INTO `intakes` (`intake_name`,`intake_month`,`intake_year`,`application_start`,`application_deadline`,`status`) VALUES
('January 2026','January',2026,'2025-09-01','2026-01-15','Open'),
('May 2026','May',2026,'2026-01-01','2026-05-15','Upcoming'),
('August 2026','August',2026,'2026-04-01','2026-08-15','Upcoming');

-- ── SEED DEFAULT ACADEMIC PROGRAMS from students_db.programs (if empty) ──
-- NOTE: The dashboard auto-syncs from students_db.programs on page load
INSERT IGNORE INTO `academic_programs` (`program_code`,`program_name`,`program_type`,`duration_years`)
SELECT CONCAT('PGM-',id), program_name, program_type, duration_years
FROM `igangaschoolofl_students_db`.`programs` p
WHERE p.is_active=1 AND NOT EXISTS (SELECT 1 FROM `academic_programs` ap WHERE ap.program_name=p.program_name COLLATE utf8mb4_general_ci LIMIT 1);

-- =============================================================================
-- END OF ADMISSIONS MODULE SCHEMA
-- =============================================================================
