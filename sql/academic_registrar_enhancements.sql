-- ============================================================
-- Academic Registrar Management System - Schema Enhancements
-- Run this ONCE on the staffs_db (igangaschoolofl_staffs_db)
-- ============================================================

-- 1. Registrar Certificates Table
CREATE TABLE IF NOT EXISTS `registrar_certificates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `certificate_number` (`certificate_number`),
  KEY `student_id` (`student_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Registrar Transcript Requests Table (alongside existing registrar_transcripts)
CREATE TABLE IF NOT EXISTS `registrar_transcript_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_number` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `purpose` varchar(500) DEFAULT NULL,
  `copies_requested` int(11) DEFAULT '1',
  `copies_issued` int(11) DEFAULT '0',
  `fee` decimal(10,2) DEFAULT '0.00',
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
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `request_number` (`request_number`),
  KEY `student_id` (`student_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Registrar Settings Table
CREATE TABLE IF NOT EXISTS `registrar_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `setting_group` varchar(50) DEFAULT 'general',
  `description` varchar(500) DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default settings
INSERT IGNORE INTO `registrar_settings` (`setting_key`, `setting_value`, `setting_group`, `description`) VALUES
('current_academic_year', '2025', 'academic', 'Current active academic year'),
('current_semester', 'Semester 1', 'academic', 'Current active semester'),
('institution_name', 'ISNM', 'general', 'Institution Name'),
('transcript_fee', '50000', 'fees', 'Transcript processing fee'),
('certificate_fee', '100000', 'fees', 'Certificate processing fee'),
('grading_system', 'letter', 'academic', 'Grading system (letter/percentage/GPA)'),
('pass_mark', '50', 'academic', 'Minimum pass mark'),
('currency', 'UGX', 'general', 'Default currency'),
('auto_generate_transcripts', '1', 'settings', 'Auto-generate transcripts on grade approval'),
('graduation_batch', '2025', 'academic', 'Current graduation batch');

-- 4. View: Student Grouping Summary (by program, year, status)
CREATE OR REPLACE VIEW `view_student_grouping` AS
SELECT
  course AS program,
  current_year AS year_of_study,
  status,
  set_name,
  current_semester AS semester,
  COUNT(*) AS student_count
FROM igangaschoolofl_students_db.students
WHERE full_name IS NOT NULL AND full_name != ''
  AND LENGTH(full_name) > 3
  AND full_name NOT LIKE '%MINISTRY%'
  AND full_name NOT LIKE '%ACCOUNTABILITY%'
  AND full_name NOT LIKE '%VERIFICATION%'
  AND full_name NOT LIKE '%HEALTH EDUCATION%'
  AND full_name NOT LIKE '%……………………………………………………%'
GROUP BY course, current_year, status, set_name, current_semester
ORDER BY course, current_year, status;

-- 5. View: Program Grouping (by program_code)
CREATE OR REPLACE VIEW `view_program_grouping` AS
SELECT
  program_code AS department,
  course_code,
  course_title AS course_name,
  credits AS credit_hours,
  year_of_study AS course_level
FROM igangaschoolofl_staffs_db.academic_course_catalog
WHERE course_title IS NOT NULL AND course_title != ''
ORDER BY program_code, course_title;

-- 6. View: Document Grouping (by type, student)
CREATE OR REPLACE VIEW `view_document_grouping` AS
SELECT
  gd.document_type,
  gd.student_id,
  s.full_name AS student_name,
  s.course AS program,
  COUNT(*) AS document_count
FROM igangaschoolofl_staffs_db.generated_documents gd
LEFT JOIN igangaschoolofl_students_db.students s ON gd.student_id = s.id
WHERE gd.document_type IS NOT NULL
GROUP BY gd.document_type, gd.student_id, s.full_name, s.course
ORDER BY gd.document_type, s.full_name;  
