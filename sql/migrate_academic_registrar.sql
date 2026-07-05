-- Academic Registrar - Complete Database Migration
-- Run this ONCE in phpMyAdmin (on igangaschoolofl_staffs_db)
-- Drops and recreates all tables + starter reference data

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS academic_calendar;
DROP TABLE IF EXISTS semesters;
DROP TABLE IF EXISTS intakes;
DROP TABLE IF EXISTS academic_programs;
DROP TABLE IF EXISTS academic_course_catalog;
DROP TABLE IF EXISTS examination_records;
DROP TABLE IF EXISTS grading_approval_workflow;
DROP TABLE IF EXISTS result_publications;
DROP TABLE IF EXISTS national_exam_results;
DROP TABLE IF EXISTS transcripts;
DROP TABLE IF EXISTS transcript_items;
DROP TABLE IF EXISTS transcript_templates;
DROP TABLE IF EXISTS certificates;
DROP TABLE IF EXISTS certificate_templates;
DROP TABLE IF EXISTS certificate_uploads;
DROP TABLE IF EXISTS certificate_verification;
DROP TABLE IF EXISTS graduation_candidates;
DROP TABLE IF EXISTS graduation_approvals;
DROP TABLE IF EXISTS student_progression;
DROP TABLE IF EXISTS gpa_settings;
DROP TABLE IF EXISTS grade_scales;
DROP TABLE IF EXISTS clinical_assessments;
DROP TABLE IF EXISTS clinical_placements;
DROP TABLE IF EXISTS academic_approvals;
DROP TABLE IF EXISTS academic_audit_logs;
DROP TABLE IF EXISTS communications;
DROP TABLE IF EXISTS registrar_student_registration;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. Academic Calendar
CREATE TABLE academic_calendar (
  id INT AUTO_INCREMENT PRIMARY KEY,
  academic_year VARCHAR(20) NOT NULL,
  start_date DATE NULL,
  end_date DATE NULL,
  is_current TINYINT(1) DEFAULT 0,
  status VARCHAR(50) DEFAULT 'Active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_year (academic_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Semesters
CREATE TABLE semesters (
  id INT AUTO_INCREMENT PRIMARY KEY,
  academic_year VARCHAR(20) NOT NULL,
  semester_name VARCHAR(100) NOT NULL,
  start_date DATE NULL,
  end_date DATE NULL,
  is_current TINYINT(1) DEFAULT 0,
  status VARCHAR(50) DEFAULT 'Active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Intakes
CREATE TABLE intakes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  intake_name VARCHAR(200) NOT NULL,
  academic_year VARCHAR(20) NOT NULL,
  start_date DATE NULL,
  end_date DATE NULL,
  status VARCHAR(50) DEFAULT 'Open',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Academic Programs
CREATE TABLE academic_programs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  program_code VARCHAR(50) NOT NULL UNIQUE,
  program_name VARCHAR(300) NOT NULL,
  program_type VARCHAR(100) DEFAULT '',
  department VARCHAR(200) DEFAULT '',
  duration_years INT DEFAULT 3,
  status VARCHAR(50) DEFAULT 'Active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Course Catalog
CREATE TABLE academic_course_catalog (
  id INT AUTO_INCREMENT PRIMARY KEY,
  course_code VARCHAR(50) NOT NULL,
  course_title VARCHAR(300) NOT NULL,
  credits DECIMAL(5,2) DEFAULT 0.00,
  program_code VARCHAR(50) DEFAULT '',
  year_of_study INT DEFAULT 1,
  semester VARCHAR(100) DEFAULT '',
  status VARCHAR(50) DEFAULT 'Active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_program (program_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Examination Records
CREATE TABLE examination_records (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  course_code VARCHAR(50) NOT NULL,
  exam_type VARCHAR(50) DEFAULT 'Final',
  marks_obtained DECIMAL(8,2) DEFAULT 0.00,
  total_marks DECIMAL(8,2) DEFAULT 100.00,
  grade VARCHAR(5) DEFAULT '',
  continuous_assessment_marks DECIMAL(8,2) DEFAULT 0.00,
  final_exam_marks DECIMAL(8,2) DEFAULT 0.00,
  grade_status VARCHAR(50) DEFAULT 'Pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_student (student_id),
  KEY idx_course (course_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Grading Approval Workflow
CREATE TABLE grading_approval_workflow (
  id INT AUTO_INCREMENT PRIMARY KEY,
  workflow_number INT NOT NULL UNIQUE,
  examination_record_id INT DEFAULT 0,
  hod_status VARCHAR(50) DEFAULT 'Pending',
  registrar_status VARCHAR(50) DEFAULT 'Pending',
  principal_status VARCHAR(50) DEFAULT 'Pending',
  hod_approved_by INT DEFAULT 0,
  registrar_approved_by INT DEFAULT 0,
  principal_approved_by INT DEFAULT 0,
  current_stage VARCHAR(100) DEFAULT 'HOD',
  published_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_record (examination_record_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Result Publications
CREATE TABLE result_publications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  academic_year VARCHAR(20) NOT NULL,
  semester VARCHAR(100) NOT NULL,
  published_by INT DEFAULT 0,
  published_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  status VARCHAR(50) DEFAULT 'Published',
  UNIQUE KEY uq_pub (academic_year, semester)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. National Exam Results
CREATE TABLE national_exam_results (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  course_code VARCHAR(50) NOT NULL,
  exam_body VARCHAR(100) DEFAULT 'UNEB',
  marks_obtained DECIMAL(8,2) DEFAULT 0.00,
  grade VARCHAR(5) DEFAULT '',
  academic_year VARCHAR(20) DEFAULT NULL,
  semester VARCHAR(100) DEFAULT NULL,
  status VARCHAR(50) DEFAULT 'Pending',
  entered_by INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Transcripts
CREATE TABLE transcripts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  transcript_number VARCHAR(50) UNIQUE,
  template_id INT DEFAULT 0,
  academic_year VARCHAR(20) DEFAULT NULL,
  semester VARCHAR(100) DEFAULT NULL,
  total_credit_hours DECIMAL(10,2) DEFAULT 0.00,
  cumulative_gpa DECIMAL(4,2) DEFAULT 0.00,
  status VARCHAR(50) DEFAULT 'Draft',
  generated_by INT DEFAULT 0,
  generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  is_archived TINYINT(1) DEFAULT 0,
  is_downloadable TINYINT(1) DEFAULT 0,
  KEY idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Transcript Items
CREATE TABLE transcript_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  transcript_id INT NOT NULL,
  course_code VARCHAR(50) NOT NULL,
  course_title VARCHAR(300) DEFAULT '',
  credit_hours DECIMAL(5,2) DEFAULT 0.00,
  marks_obtained DECIMAL(8,2) DEFAULT 0.00,
  grade VARCHAR(5) DEFAULT '',
  grade_point DECIMAL(4,2) DEFAULT 0.00,
  academic_year VARCHAR(20) DEFAULT NULL,
  semester VARCHAR(100) DEFAULT NULL,
  KEY idx_transcript (transcript_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. Transcript Templates
CREATE TABLE transcript_templates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  template_name VARCHAR(200) NOT NULL,
  template_html TEXT,
  orientation VARCHAR(20) DEFAULT 'portrait',
  is_default TINYINT(1) DEFAULT 0,
  status VARCHAR(50) DEFAULT 'Active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. Certificates
CREATE TABLE certificates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  certificate_number VARCHAR(50) UNIQUE,
  template_id INT DEFAULT 0,
  certificate_type VARCHAR(100) DEFAULT 'Certificate',
  program_name VARCHAR(300) DEFAULT NULL,
  completion_date DATE NULL,
  issue_date DATE DEFAULT NULL,
  status VARCHAR(50) DEFAULT 'Draft',
  generated_by INT DEFAULT 0,
  generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  is_archived TINYINT(1) DEFAULT 0,
  is_downloadable TINYINT(1) DEFAULT 0,
  KEY idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. Certificate Templates
CREATE TABLE certificate_templates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  template_name VARCHAR(200) NOT NULL,
  template_html TEXT,
  orientation VARCHAR(20) DEFAULT 'landscape',
  is_default TINYINT(1) DEFAULT 0,
  status VARCHAR(50) DEFAULT 'Active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 15. Certificate Uploads
CREATE TABLE certificate_uploads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  certificate_id INT NOT NULL,
  file_name VARCHAR(300) DEFAULT '',
  file_path VARCHAR(500) DEFAULT '',
  file_size INT DEFAULT 0,
  mime_type VARCHAR(100) DEFAULT '',
  uploaded_by INT DEFAULT 0,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_certificate (certificate_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 16. Certificate Verification
CREATE TABLE certificate_verification (
  id INT AUTO_INCREMENT PRIMARY KEY,
  certificate_number VARCHAR(50) NOT NULL,
  verified_by VARCHAR(200) DEFAULT NULL,
  verification_reference VARCHAR(100) DEFAULT NULL,
  verification_status VARCHAR(50) DEFAULT 'Verified',
  verified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_cert_number (certificate_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 17. Graduation Candidates
CREATE TABLE graduation_candidates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  program_id INT DEFAULT 0,
  academic_year VARCHAR(20) DEFAULT NULL,
  graduation_year VARCHAR(20) DEFAULT NULL,
  status VARCHAR(50) DEFAULT 'Pending',
  remarks TEXT,
  submitted_by INT DEFAULT 0,
  submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_student_program (student_id, program_id),
  KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 18. Graduation Approvals
CREATE TABLE graduation_approvals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  candidate_id INT NOT NULL,
  approved_by INT DEFAULT 0,
  approval_level VARCHAR(100) DEFAULT 'Registrar',
  status VARCHAR(50) DEFAULT 'Pending',
  remarks TEXT,
  approved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_candidate (candidate_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 19. Student Progression
CREATE TABLE student_progression (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  from_year INT DEFAULT 0,
  to_year INT DEFAULT 0,
  from_semester VARCHAR(100) DEFAULT NULL,
  to_semester VARCHAR(100) DEFAULT NULL,
  academic_year VARCHAR(20) DEFAULT NULL,
  progression_type VARCHAR(50) DEFAULT 'Promotion',
  approved_by INT DEFAULT 0,
  status VARCHAR(50) DEFAULT 'Approved',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 20. GPA Settings
CREATE TABLE gpa_settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(100) UNIQUE NOT NULL,
  setting_value TEXT,
  description VARCHAR(500) DEFAULT NULL,
  updated_by INT DEFAULT 0,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 21. Grade Scales
CREATE TABLE grade_scales (
  id INT AUTO_INCREMENT PRIMARY KEY,
  grade_letter VARCHAR(5) NOT NULL,
  grade_point DECIMAL(4,2) DEFAULT 0.00,
  min_percentage DECIMAL(5,2) DEFAULT 0.00,
  max_percentage DECIMAL(5,2) DEFAULT 100.00,
  status VARCHAR(50) DEFAULT 'Active',
  UNIQUE KEY uq_grade (grade_letter)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 22. Clinical Assessments
CREATE TABLE clinical_assessments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  clinical_placement_id INT DEFAULT 0,
  assessment_type VARCHAR(100) DEFAULT '',
  score DECIMAL(8,2) DEFAULT 0.00,
  max_score DECIMAL(8,2) DEFAULT 100.00,
  grade VARCHAR(5) DEFAULT '',
  assessed_by INT DEFAULT 0,
  assessment_date DATE DEFAULT NULL,
  remarks TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 23. Clinical Placements
CREATE TABLE clinical_placements (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  facility_name VARCHAR(300) NOT NULL,
  department VARCHAR(200) DEFAULT '',
  start_date DATE DEFAULT NULL,
  end_date DATE DEFAULT NULL,
  supervisor_name VARCHAR(200) DEFAULT '',
  supervisor_phone VARCHAR(50) DEFAULT '',
  status VARCHAR(50) DEFAULT 'Active',
  created_by INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 24. Academic Approvals
CREATE TABLE academic_approvals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  entity_type VARCHAR(100) NOT NULL,
  entity_id INT NOT NULL,
  requested_by INT DEFAULT 0,
  approved_by INT DEFAULT 0,
  approval_level VARCHAR(100) DEFAULT 'Registrar',
  status VARCHAR(50) DEFAULT 'Pending',
  remarks TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  approved_at DATETIME DEFAULT NULL,
  KEY idx_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 25. Academic Audit Logs
CREATE TABLE academic_audit_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  action VARCHAR(200) NOT NULL,
  entity_type VARCHAR(100) DEFAULT '',
  entity_id INT DEFAULT 0,
  description TEXT,
  ip_address VARCHAR(50) DEFAULT NULL,
  user_agent VARCHAR(500) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user (user_id),
  KEY idx_action (action),
  KEY idx_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 26. Communications
CREATE TABLE communications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  recipient_type VARCHAR(50) DEFAULT 'student',
  recipient_id INT DEFAULT 0,
  subject VARCHAR(300) NOT NULL,
  message TEXT NOT NULL,
  channel VARCHAR(50) DEFAULT 'portal',
  sent_by INT DEFAULT 0,
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 27. Registrar Student Registration
CREATE TABLE registrar_student_registration (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  academic_year VARCHAR(20) NOT NULL,
  semester VARCHAR(100) NOT NULL,
  registration_date DATE DEFAULT NULL,
  registration_status VARCHAR(50) DEFAULT 'Registered',
  registered_by INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_student (student_id),
  KEY idx_year (academic_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- STARTER REFERENCE DATA
-- ============================================================

-- Grade Scales
INSERT INTO grade_scales (grade_letter, grade_point, min_percentage, max_percentage) VALUES
('A',  4.00, 80.00, 100.00),
('B+', 3.70, 75.00, 79.99),
('B',  3.30, 70.00, 74.99),
('C+', 3.00, 65.00, 69.99),
('C',  2.70, 60.00, 64.99),
('D+', 2.30, 55.00, 59.99),
('D',  2.00, 50.00, 54.99),
('F',  0.00, 0.00,  49.99);

-- GPA Settings
INSERT INTO gpa_settings (setting_key, setting_value, description) VALUES
('gpa_max', '4.00', 'Maximum GPA value'),
('pass_mark', '50', 'Minimum passing percentage'),
('credit_hours_enabled', '1', 'Enable credit hour calculations'),
('auto_gpa', '1', 'Auto-calculate GPA on mark entry');

-- Academic Year
INSERT INTO academic_calendar (academic_year, start_date, end_date, is_current, status) VALUES
('2026', '2026-02-01', '2026-12-31', 1, 'Active');

-- Semesters
INSERT INTO semesters (academic_year, semester_name, start_date, end_date, is_current, status) VALUES
('2026', 'First Semester', '2026-02-01', '2026-06-30', 1, 'Active'),
('2026', 'Second Semester', '2026-08-01', '2026-12-31', 0, 'Active');

-- Programs
INSERT INTO academic_programs (program_code, program_name, program_type, department, duration_years) VALUES
('DIP-NUR', 'Diploma in Nursing', 'Diploma', 'Nursing', 3),
('DIP-MID', 'Diploma in Midwifery', 'Diploma', 'Midwifery', 3),
('CERT-EN', 'Certificate in Enrolled Nursing', 'Certificate', 'Nursing', 2),
('CERT-MID', 'Certificate in Midwifery', 'Certificate', 'Midwifery', 2);

-- Courses for Nursing Diploma (Year 1, Sem 1)
INSERT INTO academic_course_catalog (course_code, course_title, credits, program_code, year_of_study, semester) VALUES
('NUR101', 'Anatomy & Physiology I', 3.0, 'DIP-NUR', 1, 'First Semester'),
('NUR102', 'Fundamentals of Nursing', 4.0, 'DIP-NUR', 1, 'First Semester'),
('NUR103', 'Microbiology', 2.0, 'DIP-NUR', 1, 'First Semester'),
('MEF101', 'Maternal and Child Health', 3.0, 'DIP-NUR', 1, 'First Semester'),
('COM101', 'Communication Skills', 2.0, 'DIP-NUR', 1, 'First Semester');

-- Default Transcript Template
INSERT INTO transcript_templates (template_name, template_html, orientation, is_default, status) VALUES
('Standard Transcript', '<h3 class="text-center">IGANGA SCHOOL OF NURSING AND MIDWIFERY</h3><p class="text-center">ACADEMIC TRANSCRIPT</p>', 'portrait', 1, 'Active');

-- Default Certificate Template
INSERT INTO certificate_templates (template_name, template_html, orientation, is_default, status) VALUES
('Standard Certificate', '<h2>IGANGA SCHOOL OF NURSING AND MIDWIFERY</h2><h4>CERTIFICATE</h4>', 'landscape', 1, 'Active');
