-- ISNM School Management System - Academic Management SQL
-- Comprehensive SQL for academic programs, courses, examinations, and student records

USE isnm_school;

-- ========================================
-- PROGRAM MANAGEMENT
-- ========================================

-- Drop existing tables if they exist to ensure clean creation
DROP TABLE IF EXISTS student_academic_records;
DROP TABLE IF EXISTS course_assignments;
DROP TABLE IF EXISTS examinations;
DROP TABLE IF EXISTS exam_results;
DROP TABLE IF EXISTS attendance_records;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS programs;

-- Programs table for managing academic programs
CREATE TABLE programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_code VARCHAR(20) NOT NULL UNIQUE,
    program_name VARCHAR(255) NOT NULL,
    program_type ENUM('certificate', 'diploma', 'degree') NOT NULL,
    duration_years DECIMAL(3,1) NOT NULL,
    description TEXT,
    admission_requirements TEXT,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_program_code (program_code),
    INDEX idx_program_type (program_type),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Courses table for managing individual courses
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    course_name VARCHAR(255) NOT NULL,
    program_id INT NOT NULL,
    semester ENUM('year1_sem1', 'year1_sem2', 'year2_sem1', 'year2_sem2', 'year3_sem1', 'year3_sem2') NOT NULL,
    credits DECIMAL(4,1) NOT NULL,
    contact_hours_per_week INT DEFAULT 3,
    description TEXT,
    prerequisites TEXT,
    learning_outcomes TEXT,
    assessment_methods TEXT,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_course_code (course_code),
    INDEX idx_program_id (program_id),
    INDEX idx_semester (semester),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Course assignments table for assigning staff to courses
CREATE TABLE course_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    staff_id INT NOT NULL,
    role ENUM('lecturer', 'assistant', 'coordinator') DEFAULT 'lecturer',
    assigned_date DATE NOT NULL,
    end_date DATE NULL,
    status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
    assigned_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id),
    UNIQUE KEY unique_course_staff (course_id, staff_id, status),
    INDEX idx_course_id (course_id),
    INDEX idx_staff_id (staff_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Student academic records
CREATE TABLE student_academic_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    semester ENUM('year1_sem1', 'year1_sem2', 'year2_sem1', 'year2_sem2', 'year3_sem1', 'year3_sem2') NOT NULL,
    academic_year VARCHAR(9) NOT NULL, -- Format: 2024-2025
    registration_date DATE NOT NULL,
    grade DECIMAL(4,2) NULL,
    grade_letter VARCHAR(2) NULL,
    gpa_points DECIMAL(3,2) NULL,
    attendance_percentage DECIMAL(5,2) DEFAULT 0,
    status ENUM('registered', 'in_progress', 'completed', 'failed', 'withdrawn', 'deferred') DEFAULT 'registered',
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_course_semester (student_id, course_id, semester, academic_year),
    INDEX idx_student_id (student_id),
    INDEX idx_course_id (course_id),
    INDEX idx_semester (semester),
    INDEX idx_academic_year (academic_year),
    INDEX idx_status (status),
    INDEX idx_grade (grade)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- EXAMINATIONS AND ASSESSMENTS
-- ========================================

-- Examinations table
CREATE TABLE examinations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    exam_name VARCHAR(255) NOT NULL,
    exam_type ENUM('quiz', 'assignment', 'midterm', 'final', 'practical', 'oral', 'project') NOT NULL,
    total_marks DECIMAL(5,2) NOT NULL,
    passing_marks DECIMAL(5,2) NOT NULL,
    exam_date DATE NOT NULL,
    exam_duration INT NOT NULL, -- Duration in minutes
    exam_location VARCHAR(255),
    instructions TEXT,
    created_by INT NOT NULL,
    status ENUM('draft', 'scheduled', 'in_progress', 'completed', 'cancelled', 'postponed') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_course_id (course_id),
    INDEX idx_exam_type (exam_type),
    INDEX idx_exam_date (exam_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exam results
CREATE TABLE exam_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    student_id INT NOT NULL,
    marks_obtained DECIMAL(5,2) NOT NULL,
    grade VARCHAR(2),
    grade_points DECIMAL(3,2),
    percentage DECIMAL(5,2) GENERATED ALWAYS AS (ROUND((marks_obtained / (SELECT total_marks FROM examinations WHERE id = exam_id)) * 100, 2)) STORED,
    remarks TEXT,
    submission_notes TEXT,
    submitted_by INT NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified BOOLEAN DEFAULT FALSE,
    verified_by INT NULL,
    verified_at TIMESTAMP NULL,
    verification_notes TEXT,
    status ENUM('submitted', 'verified', 'rejected', 'pending_review') DEFAULT 'submitted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (exam_id) REFERENCES examinations(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (submitted_by) REFERENCES users(id),
    FOREIGN KEY (verified_by) REFERENCES users(id),
    UNIQUE KEY unique_exam_result (exam_id, student_id),
    INDEX idx_exam_id (exam_id),
    INDEX idx_student_id (student_id),
    INDEX idx_marks_obtained (marks_obtained),
    INDEX idx_grade (grade),
    INDEX idx_verified (verified),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- ATTENDANCE MANAGEMENT
-- ========================================

-- Attendance records
CREATE TABLE attendance_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    attendance_status ENUM('present', 'absent', 'late', 'excused', 'sick_leave', 'authorized_absence') NOT NULL,
    arrival_time TIME NULL,
    departure_time TIME NULL,
    marked_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES users(id),
    UNIQUE KEY unique_attendance (student_id, course_id, attendance_date),
    INDEX idx_student_id (student_id),
    INDEX idx_course_id (course_id),
    INDEX idx_attendance_date (attendance_date),
    INDEX idx_attendance_status (attendance_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attendance patterns for tracking student attendance trends
CREATE TABLE attendance_patterns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    month DATE NOT NULL, -- First day of the month
    total_sessions INT NOT NULL DEFAULT 0,
    present_sessions INT NOT NULL DEFAULT 0,
    absent_sessions INT NOT NULL DEFAULT 0,
    late_sessions INT NOT NULL DEFAULT 0,
    excused_sessions INT NOT NULL DEFAULT 0,
    attendance_rate DECIMAL(5,2) GENERATED ALWAYS AS (ROUND((present_sessions * 100.0) / total_sessions, 2)) STORED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_course_month (student_id, course_id, month),
    INDEX idx_student_id (student_id),
    INDEX idx_course_id (course_id),
    INDEX idx_month (month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- TIMETABLE AND SCHEDULE MANAGEMENT
-- ========================================

-- Timetable table
CREATE TABLE timetable (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    staff_id INT NOT NULL,
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    room VARCHAR(100),
    semester ENUM('year1_sem1', 'year1_sem2', 'year2_sem1', 'year2_sem2', 'year3_sem1', 'year3_sem2') NOT NULL,
    academic_year VARCHAR(9) NOT NULL,
    session_type ENUM('lecture', 'lab', 'tutorial', 'seminar', 'practical') DEFAULT 'lecture',
    recurring BOOLEAN DEFAULT TRUE,
    status ENUM('active', 'inactive', 'cancelled') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_course_id (course_id),
    INDEX idx_staff_id (staff_id),
    INDEX idx_day_of_week (day_of_week),
    INDEX idx_start_time (start_time),
    INDEX idx_semester (semester),
    INDEX idx_academic_year (academic_year),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- GRADE SCALING AND GPA MANAGEMENT
-- ========================================

-- Grade scaling table for different programs
CREATE TABLE grade_scales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    grade_letter VARCHAR(2) NOT NULL,
    min_mark DECIMAL(4,2) NOT NULL,
    max_mark DECIMAL(4,2) NOT NULL,
    grade_points DECIMAL(3,2) NOT NULL,
    description VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE,
    UNIQUE KEY unique_program_grade (program_id, grade_letter),
    INDEX idx_program_id (program_id),
    INDEX idx_grade_letter (grade_letter)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- INSERT DEFAULT DATA
-- ========================================

-- Insert default programs
INSERT INTO programs (program_code, program_name, program_type, duration_years, description, admission_requirements, created_by) VALUES
('CM', 'Certificate in Midwifery', 'certificate', 2.0, '2-year certificate program in midwifery focusing on maternal and child health care', 'UACE with at least 2 principal passes in Biology and Chemistry, O-Level with at least 5 passes', 1),
('CN', 'Certificate in Nursing', 'certificate', 2.0, '2-year certificate program in general nursing covering basic medical and surgical nursing', 'UACE with at least 2 principal passes in Biology and Chemistry, O-Level with at least 5 passes', 1),
('DMORDN', 'Diploma in Midwifery', 'diploma', 3.0, '3-year diploma program in midwifery with advanced clinical skills and management', 'UACE with at least 3 principal passes including Biology, O-Level with at least 5 passes, Certificate in Midwifery or Nursing', 1)
ON DUPLICATE KEY UPDATE program_name = VALUES(program_name);

-- Get program IDs for use in other tables
SET @cm_program_id = (SELECT id FROM programs WHERE program_code = 'CM');
SET @cn_program_id = (SELECT id FROM programs WHERE program_code = 'CN');
SET @dmordn_program_id = (SELECT id FROM programs WHERE program_code = 'DMORDN');

-- Insert default courses for CM program
INSERT INTO courses (course_code, course_name, program_id, semester, credits, contact_hours_per_week, description, prerequisites, learning_outcomes, assessment_methods, created_by) VALUES
('CM101', 'Anatomy and Physiology I', @cm_program_id, 'year1_sem1', 3.0, 4, 'Basic human anatomy and physiology focusing on body systems and functions', 'None', 'Understand basic human anatomy and physiology', 'Quizzes, Midterm, Final Exam, Practical', 1),
('CM102', 'Nursing Fundamentals I', @cm_program_id, 'year1_sem1', 4.0, 5, 'Introduction to nursing principles, ethics, and basic nursing skills', 'None', 'Demonstrate basic nursing skills and ethical practice', 'Assignments, Skills Lab, Exams', 1),
('CM103', 'Pharmacology I', @cm_program_id, 'year1_sem1', 2.0, 3, 'Basic pharmacology for nurses including drug calculations and administration', 'None', 'Understand basic pharmacology and drug administration', 'Quizzes, Practical Exams', 1),
('CM104', 'Microbiology', @cm_program_id, 'year1_sem2', 3.0, 4, 'Medical microbiology focusing on pathogens relevant to midwifery', 'CM101', 'Identify common pathogens and infection control', 'Lab Work, Exams', 1),
('CM105', 'Midwifery I', @cm_program_id, 'year1_sem2', 4.0, 5, 'Introduction to midwifery principles and basic midwifery skills', 'CM101, CM102', 'Demonstrate basic midwifery skills', 'Skills Lab, Clinical Practice, Exams', 1),
('CM106', 'Psychology for Midwives', @cm_program_id, 'year1_sem2', 2.0, 3, 'Psychological aspects of pregnancy, childbirth, and postpartum care', 'None', 'Understand psychological aspects of maternity care', 'Assignments, Case Studies, Exams', 1),
('CM201', 'Antenatal Care', @cm_program_id, 'year2_sem1', 4.0, 5, 'Comprehensive antenatal care including assessment and monitoring', 'CM105', 'Provide comprehensive antenatal care', 'Clinical Practice, Case Studies, Exams', 1),
('CM202', 'Intrapartum Care', @cm_program_id, 'year2_sem1', 4.0, 6, 'Management of labor and delivery including emergency procedures', 'CM105, CM201', 'Manage normal and complicated labor', 'Simulations, Clinical Practice, Exams', 1),
('CM203', 'Postnatal Care', @cm_program_id, 'year2_sem1', 3.0, 4, 'Postnatal care for mother and newborn including complications', 'CM201, CM202', 'Provide comprehensive postnatal care', 'Clinical Practice, Case Studies, Exams', 1),
('CM204', 'Neonatal Care', @cm_program_id, 'year2_sem1', 3.0, 4, 'Newborn assessment, care, and management of neonatal complications', 'CM203', 'Provide comprehensive neonatal care', 'Skills Lab, Clinical Practice, Exams', 1),
('CM205', 'Family Planning', @cm_program_id, 'year2_sem2', 2.0, 3, 'Family planning methods, counseling, and reproductive health', 'CM201, CM202', 'Provide family planning counseling', 'Case Studies, Role Play, Exams', 1),
('CM206', 'Research Methods in Midwifery', @cm_program_id, 'year2_sem2', 2.0, 3, 'Introduction to research methodology and evidence-based practice in midwifery', 'None', 'Understand research principles in midwifery', 'Research Project, Presentation', 1)
ON DUPLICATE KEY UPDATE course_name = VALUES(course_name);

-- Insert default courses for CN program
INSERT INTO courses (course_code, course_name, program_id, semester, credits, contact_hours_per_week, description, prerequisites, learning_outcomes, assessment_methods, created_by) VALUES
('CN101', 'Anatomy and Physiology I', @cn_program_id, 'year1_sem1', 3.0, 4, 'Basic human anatomy and physiology focusing on body systems and functions', 'None', 'Understand basic human anatomy and physiology', 'Quizzes, Midterm, Final Exam, Practical', 1),
('CN102', 'Nursing Fundamentals I', @cn_program_id, 'year1_sem1', 4.0, 5, 'Introduction to nursing principles, ethics, and basic nursing skills', 'None', 'Demonstrate basic nursing skills and ethical practice', 'Assignments, Skills Lab, Exams', 1),
('CN103', 'Psychology for Nurses', @cn_program_id, 'year1_sem1', 2.0, 3, 'Basic psychology and therapeutic communication in nursing', 'None', 'Apply psychological principles in nursing care', 'Assignments, Role Play, Exams', 1),
('CN104', 'Medical-Surgical Nursing I', @cn_program_id, 'year1_sem2', 4.0, 5, 'Medical-surgical nursing focusing on common health problems', 'CN101, CN102', 'Provide basic medical-surgical nursing care', 'Case Studies, Clinical Practice, Exams', 1),
('CN105', 'Community Health Nursing I', @cn_program_id, 'year1_sem2', 3.0, 4, 'Community health nursing principles and practice', 'None', 'Understand community health nursing concepts', 'Community Visits, Projects, Exams', 1),
('CN106', 'Nutrition and Dietetics', @cn_program_id, 'year1_sem2', 2.0, 3, 'Nutrition principles and dietary management in health and illness', 'None', 'Provide basic nutritional guidance', 'Case Studies, Meal Planning, Exams', 1),
('CN201', 'Medical-Surgical Nursing II', @cn_program_id, 'year2_sem1', 4.0, 5, 'Advanced medical-surgical nursing with complex health problems', 'CN104', 'Provide advanced medical-surgical nursing care', 'Case Studies, Clinical Practice, Exams', 1),
('CN202', 'Pediatric Nursing', @cn_program_id, 'year2_sem1', 3.0, 4, 'Nursing care of children from infancy to adolescence', 'CN101, CN102', 'Provide comprehensive pediatric nursing care', 'Skills Lab, Clinical Practice, Exams', 1),
('CN203', 'Mental Health Nursing', @cn_program_id, 'year2_sem1', 3.0, 4, 'Mental health nursing principles and practice', 'CN103', 'Provide basic mental health nursing care', 'Case Studies, Role Play, Exams', 1),
('CN204', 'Emergency Nursing', @cn_program_id, 'year2_sem1', 3.0, 4, 'Emergency nursing care and disaster management', 'CN201', 'Provide emergency nursing care', 'Simulations, Drills, Exams', 1),
('CN205', 'Community Health Nursing II', @cn_program_id, 'year2_sem2', 3.0, 4, 'Advanced community health nursing and program planning', 'CN105', 'Plan and implement community health programs', 'Community Projects, Presentations', 1),
('CN206', 'Leadership and Management in Nursing', @cn_program_id, 'year2_sem2', 2.0, 3, 'Nursing leadership principles and management skills', 'None', 'Understand nursing leadership concepts', 'Case Studies, Projects, Exams', 1)
ON DUPLICATE KEY UPDATE course_name = VALUES(course_name);

-- Insert default courses for DMORDN program
INSERT INTO courses (course_code, course_name, program_id, semester, credits, contact_hours_per_week, description, prerequisites, learning_outcomes, assessment_methods, created_by) VALUES
('DM101', 'Advanced Anatomy', @dmordn_program_id, 'year1_sem1', 3.0, 4, 'Advanced human anatomy with emphasis on reproductive system', 'None', 'Demonstrate advanced anatomical knowledge', 'Quizzes, Practical Exams, Dissection', 1),
('DM102', 'Advanced Midwifery I', @dmordn_program_id, 'year1_sem1', 4.0, 5, 'Advanced midwifery principles and evidence-based practice', 'None', 'Apply evidence-based midwifery practice', 'Research Projects, Clinical Practice, Exams', 1),
('DM103', 'Research Methods', @dmordn_program_id, 'year1_sem1', 2.0, 3, 'Nursing research methods, statistics, and evidence-based practice', 'None', 'Conduct basic nursing research', 'Research Project, Presentation, Exams', 1),
('DM104', 'Obstetrics and Gynecology', @dmordn_program_id, 'year1_sem2', 4.0, 5, 'Advanced OB/GYN topics including high-risk pregnancies', 'DM101, DM102', 'Manage complex obstetric and gynecological conditions', 'Case Studies, Simulations, Exams', 1),
('DM105', 'Neonatology', @dmordn_program_id, 'year1_sem2', 3.0, 4, 'Advanced neonatal care and management of complications', 'None', 'Provide advanced neonatal care', 'Skills Lab, Clinical Practice, Exams', 1),
('DM106', 'Maternal Mental Health', @dmordn_program_id, 'year1_sem2', 2.0, 3, 'Mental health issues in pregnancy and postpartum period', 'None', 'Provide mental health support to mothers', 'Case Studies, Counseling Practice', 1),
('DM201', 'High-Risk Pregnancy Management', @dmordn_program_id, 'year2_sem1', 4.0, 5, 'Management of high-risk pregnancies and complications', 'DM104', 'Manage high-risk pregnancies effectively', 'Simulations, Clinical Practice, Exams', 1),
('DM202', 'Emergency Obstetric Care', @dmordn_program_id, 'year2_sem1', 3.0, 4, 'Emergency obstetric care and life-saving procedures', 'DM104, DM201', 'Provide emergency obstetric care', 'Simulations, Drills, Exams', 1),
('DM203', 'Midwifery Leadership', @dmordn_program_id, 'year2_sem1', 2.0, 3, 'Leadership and management in midwifery practice', 'None', 'Demonstrate leadership skills in midwifery', 'Projects, Presentations, Exams', 1),
('DM204', 'Quality Improvement in Midwifery', @dmordn_program_id, 'year2_sem1', 2.0, 3, 'Quality improvement methods and evidence-based practice', 'DM103', 'Implement quality improvement projects', 'QI Projects, Presentations', 1),
('DM205', 'Global Midwifery Issues', @dmordn_program_id, 'year2_sem2', 2.0, 3, 'Global perspectives on midwifery and international health', 'None', 'Understand global midwifery issues', 'Research, Presentations, Exams', 1),
('DM206', 'Professional Development', @dmordn_program_id, 'year2_sem2', 2.0, 3, 'Professional development and career planning in midwifery', 'None', 'Plan professional development', 'Portfolio, Presentations, Exams', 1)
ON DUPLICATE KEY UPDATE course_name = VALUES(course_name);

-- Insert grade scales for all programs
INSERT INTO grade_scales (program_id, grade_letter, min_mark, max_mark, grade_points, description) VALUES
(@cm_program_id, 'A', 80, 100, 4.0, 'Excellent'),
(@cm_program_id, 'B+', 75, 79, 3.5, 'Very Good'),
(@cm_program_id, 'B', 70, 74, 3.0, 'Good'),
(@cm_program_id, 'C+', 65, 69, 2.5, 'Fairly Good'),
(@cm_program_id, 'C', 60, 64, 2.0, 'Fair'),
(@cm_program_id, 'D', 50, 59, 1.0, 'Poor'),
(@cm_program_id, 'F', 0, 49, 0.0, 'Fail'),
(@cn_program_id, 'A', 80, 100, 4.0, 'Excellent'),
(@cn_program_id, 'B+', 75, 79, 3.5, 'Very Good'),
(@cn_program_id, 'B', 70, 74, 3.0, 'Good'),
(@cn_program_id, 'C+', 65, 69, 2.5, 'Fairly Good'),
(@cn_program_id, 'C', 60, 64, 2.0, 'Fair'),
(@cn_program_id, 'D', 50, 59, 1.0, 'Poor'),
(@cn_program_id, 'F', 0, 49, 0.0, 'Fail'),
(@dmordn_program_id, 'A', 80, 100, 4.0, 'Excellent'),
(@dmordn_program_id, 'B+', 75, 79, 3.5, 'Very Good'),
(@dmordn_program_id, 'B', 70, 74, 3.0, 'Good'),
(@dmordn_program_id, 'C+', 65, 69, 2.5, 'Fairly Good'),
(@dmordn_program_id, 'C', 60, 64, 2.0, 'Fair'),
(@dmordn_program_id, 'D', 50, 59, 1.0, 'Poor'),
(@dmordn_program_id, 'F', 0, 49, 0.0, 'Fail')
ON DUPLICATE KEY UPDATE description = VALUES(description);

-- ========================================
-- CREATE VIEWS FOR EASY ACCESS
-- ========================================

-- Student academic summary view
CREATE OR REPLACE VIEW student_academic_summary AS
SELECT 
    u.id as student_id,
    u.full_name,
    u.index_number,
    p.program_name,
    p.program_type,
    COUNT(DISTINCT sar.course_id) as total_courses,
    COUNT(CASE WHEN sar.status = 'completed' THEN 1 END) as completed_courses,
    COUNT(CASE WHEN sar.status = 'in_progress' THEN 1 END) as in_progress_courses,
    AVG(CASE WHEN sar.status = 'completed' THEN sar.gpa_points END) as current_gpa,
    SUM(CASE WHEN sar.status = 'completed' THEN c.credits ELSE 0 END) as completed_credits,
    MAX(sar.academic_year) as current_academic_year,
    MAX(sar.semester) as current_semester,
    COUNT(CASE WHEN ar.attendance_status = 'present' THEN 1 END) as total_present,
    COUNT(ar.attendance_status) as total_attendance_days,
    ROUND((COUNT(CASE WHEN ar.attendance_status = 'present' THEN 1 END) * 100.0) / COUNT(ar.attendance_status), 2) as attendance_rate
FROM users u
LEFT JOIN student_academic_records sar ON u.id = sar.student_id
LEFT JOIN courses c ON sar.course_id = c.id
LEFT JOIN programs p ON c.program_id = p.id
LEFT JOIN attendance_records ar ON u.id = ar.student_id
WHERE u.type = 'student' AND u.status = 'active'
GROUP BY u.id, u.full_name, u.index_number, p.program_name, p.program_type;

-- Course summary view
CREATE OR REPLACE VIEW course_summary AS
SELECT 
    c.id,
    c.course_code,
    c.course_name,
    p.program_name,
    c.semester,
    c.credits,
    c.status,
    COUNT(DISTINCT sar.student_id) as enrolled_students,
    COUNT(CASE WHEN sar.status = 'completed' THEN 1 END) as completed_students,
    COUNT(CASE WHEN sar.status = 'in_progress' THEN 1 END) as in_progress_students,
    AVG(CASE WHEN sar.status = 'completed' THEN sar.gpa_points END) as average_gpa,
    COUNT(DISTINCT ca.staff_id) as assigned_staff,
    COUNT(DISTINCT e.id) as total_examinations
FROM courses c
LEFT JOIN programs p ON c.program_id = p.id
LEFT JOIN student_academic_records sar ON c.id = sar.course_id
LEFT JOIN course_assignments ca ON c.id = ca.course_id AND ca.status = 'active'
LEFT JOIN examinations e ON c.id = e.course_id
GROUP BY c.id, c.course_code, c.course_name, p.program_name, c.semester, c.credits, c.status;

-- ========================================
-- STORED PROCEDURES FOR ACADEMIC OPERATIONS
-- ========================================

DELIMITER //

-- Procedure to register student for courses
CREATE PROCEDURE IF NOT EXISTS register_student_courses(
    IN p_student_id INT,
    IN p_academic_year VARCHAR(9),
    IN p_semester VARCHAR(20),
    IN p_program_id INT,
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN
)
BEGIN
    DECLARE v_course_count INT DEFAULT 0;
    DECLARE v_registered_count INT DEFAULT 0;
    
    -- Get all courses for the program and semester
    SELECT COUNT(*) INTO v_course_count
    FROM courses 
    WHERE program_id = p_program_id AND semester = p_semester AND status = 'active';
    
    IF v_course_count = 0 THEN
        SET p_result = 'No courses found for this program and semester';
        SET p_success = FALSE;
    ELSE
        -- Register student for all courses
        INSERT INTO student_academic_records (
            student_id, course_id, semester, academic_year, registration_date, status
        )
        SELECT p_student_id, c.id, p_semester, p_academic_year, CURDATE(), 'registered'
        FROM courses c
        WHERE c.program_id = p_program_id AND c.semester = p_semester AND c.status = 'active'
        AND c.id NOT IN (
            SELECT course_id FROM student_academic_records 
            WHERE student_id = p_student_id AND semester = p_semester AND academic_year = p_academic_year
        );
        
        SET v_registered_count = ROW_COUNT();
        
        -- Log activity
        INSERT INTO activity_logs (user_id, action, description, table_name, record_id)
        VALUES (p_student_id, 'COURSE_REGISTRATION', CONCAT('Registered for ', v_registered_count, ' courses'), 'student_academic_records', p_student_id);
        
        SET p_result = CONCAT('Successfully registered for ', v_registered_count, ' courses');
        SET p_success = TRUE;
    END IF;
END //

-- Procedure to calculate student GPA
CREATE PROCEDURE IF NOT EXISTS calculate_student_gpa(
    IN p_student_id INT,
    IN p_academic_year VARCHAR(9),
    OUT p_gpa DECIMAL(3,2),
    OUT p_result VARCHAR(255)
)
BEGIN
    SELECT AVG(sar.gpa_points) INTO p_gpa
    FROM student_academic_records sar
    JOIN courses c ON sar.course_id = c.id
    JOIN programs p ON c.program_id = p.id
    JOIN grade_scales gs ON p.id = gs.program_id AND sar.grade_letter = gs.grade_letter
    WHERE sar.student_id = p_student_id 
      AND sar.academic_year = p_academic_year 
      AND sar.status = 'completed' 
      AND sar.grade IS NOT NULL;
    
    IF p_gpa IS NOT NULL THEN
        SET p_result = 'GPA calculated successfully';
    ELSE
        SET p_gpa = 0.00;
        SET p_result = 'No completed courses found for GPA calculation';
    END IF;
END //

-- Procedure to update attendance patterns
CREATE PROCEDURE IF NOT EXISTS update_attendance_patterns(
    IN p_student_id INT,
    IN p_course_id INT,
    IN p_month DATE, -- First day of month
    OUT p_result VARCHAR(255)
)
BEGIN
    -- Update attendance patterns for the month
    INSERT INTO attendance_patterns (
        student_id, course_id, month, total_sessions, present_sessions, absent_sessions, late_sessions, excused_sessions
    )
    SELECT 
        p_student_id,
        p_course_id,
        p_month,
        COUNT(*),
        COUNT(CASE WHEN attendance_status = 'present' THEN 1 END),
        COUNT(CASE WHEN attendance_status = 'absent' THEN 1 END),
        COUNT(CASE WHEN attendance_status = 'late' THEN 1 END),
        COUNT(CASE WHEN attendance_status = 'excused' THEN 1 END)
    FROM attendance_records
    WHERE student_id = p_student_id 
      AND course_id = p_course_id 
      AND attendance_date >= p_month 
      AND attendance_date < DATE_ADD(p_month, INTERVAL 1 MONTH)
    ON DUPLICATE KEY UPDATE
        total_sessions = VALUES(total_sessions),
        present_sessions = VALUES(present_sessions),
        absent_sessions = VALUES(absent_sessions),
        late_sessions = VALUES(late_sessions),
        excused_sessions = VALUES(excused_sessions),
        updated_at = NOW();
    
    SET p_result = 'Attendance patterns updated successfully';
END //

DELIMITER ;

-- Success message
SELECT 'Academic management SQL created successfully!' as message;
SELECT 'All tables, views, and stored procedures for academic management are ready for use' as note;
