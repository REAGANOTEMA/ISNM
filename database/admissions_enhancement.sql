-- =============================================================
-- Admissions Enhancement Migration
-- Run this against the appropriate databases.
-- =============================================================

-- ── 1. academic_programs (staffs_db) ──
CREATE TABLE IF NOT EXISTS `academic_programs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `program_code` VARCHAR(50) NOT NULL UNIQUE,
  `program_name` VARCHAR(200) NOT NULL,
  `program_type` VARCHAR(50) DEFAULT NULL,
  `department` VARCHAR(200) DEFAULT NULL,
  `duration_years` DECIMAL(3,1) DEFAULT NULL,
  `status` VARCHAR(20) DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed programs
INSERT IGNORE INTO `academic_programs` (`program_code`, `program_name`, `program_type`, `department`, `duration_years`, `status`) VALUES
('CERT-NUR', 'Certificate in Nursing', 'Certificate', 'Nursing', 2.0, 'Active'),
('CERT-MID', 'Certificate in Midwifery', 'Certificate', 'Midwifery', 2.0, 'Active'),
('DIP-NUR', 'Diploma in Nursing', 'Diploma', 'Nursing', 3.0, 'Active'),
('DIP-MID', 'Diploma in Midwifery', 'Diploma', 'Midwifery', 3.0, 'Active');

-- ── 2. student_documents (staffs_db) ──
CREATE TABLE IF NOT EXISTS `student_documents` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `applicant_id` INT NOT NULL,
  `document_type` VARCHAR(100) NOT NULL DEFAULT 'Other',
  `document_title` VARCHAR(255) DEFAULT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `file_size` INT DEFAULT NULL,
  `mime_type` VARCHAR(100) DEFAULT NULL,
  `verification_status` VARCHAR(20) DEFAULT 'Pending',
  `verified_by` INT DEFAULT NULL,
  `verified_at` DATETIME DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `uploaded_by` INT DEFAULT NULL,
  `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_applicant` (`applicant_id`),
  KEY `idx_status` (`verification_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 3. notifications (website_db) ──
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `type` VARCHAR(50) NOT NULL DEFAULT 'info',
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT DEFAULT NULL,
  `link` VARCHAR(500) DEFAULT NULL,
  `icon` VARCHAR(50) DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_type` (`type`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 4. notification_reads (website_db) ──
CREATE TABLE IF NOT EXISTS `notification_reads` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `notification_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `read_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_notif_user` (`notification_id`, `user_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 5. Soft-delete columns for applicants (staffs_db) ──
-- MySQL does not support ADD COLUMN IF NOT EXISTS, so we check via INFORMATION_SCHEMA
SET @db = (SELECT DATABASE());
SET @exists_deleted_at = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='applicants' AND COLUMN_NAME='deleted_at');
SET @sql_deleted_at = IF(@exists_deleted_at = 0, 'ALTER TABLE `applicants` ADD COLUMN `deleted_at` DATETIME DEFAULT NULL AFTER `updated_at`', 'SELECT 1');
PREPARE stmt FROM @sql_deleted_at; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists_deleted_by = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='applicants' AND COLUMN_NAME='deleted_by');
SET @sql_deleted_by = IF(@exists_deleted_by = 0, 'ALTER TABLE `applicants` ADD COLUMN `deleted_by` INT DEFAULT NULL AFTER `deleted_at`', 'SELECT 1');
PREPARE stmt FROM @sql_deleted_by; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exists_idx = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='applicants' AND INDEX_NAME='idx_deleted');
SET @sql_idx = IF(@exists_idx = 0, 'ALTER TABLE `applicants` ADD KEY `idx_deleted` (`deleted_at`)', 'SELECT 1');
PREPARE stmt FROM @sql_idx; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ── 6. Indexes for students table (students_db) for performance ──
-- Run these against students_db:
-- CREATE INDEX IF NOT EXISTS idx_student_number ON students(student_number);
-- CREATE INDEX IF NOT EXISTS idx_student_name ON students(surname, first_name);
-- CREATE INDEX IF NOT EXISTS idx_student_phone ON students(phone);
-- CREATE INDEX IF NOT EXISTS idx_student_program ON students(program);
-- CREATE INDEX IF NOT EXISTS idx_student_status ON students(status);
-- CREATE INDEX IF NOT EXISTS idx_student_admission ON students(admission_number);
-- Since MySQL may not support IF NOT EXISTS for indexes, check manually:
-- ALTER TABLE students ADD INDEX idx_student_number(student_number(20));
-- ALTER TABLE students ADD INDEX idx_student_name(surname(30), first_name(30));
-- ALTER TABLE students ADD INDEX idx_student_phone(phone(15));
-- ALTER TABLE students ADD INDEX idx_student_program(program(30));
-- ALTER TABLE students ADD INDEX idx_student_status(status);
-- These have been applied via script. Add if re-running on fresh DB.
