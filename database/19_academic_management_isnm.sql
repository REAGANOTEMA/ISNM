-- =====================================================
-- ISNM SCHOOL MANAGEMENT SYSTEM - ACADEMIC MANAGEMENT
-- Database: isnm_db
-- Supports all academic operations: programs, courses, examinations, attendance, etc.
-- =====================================================

USE isnm_db;

-- Drop existing tables if they exist to ensure clean setup
DROP TABLE IF EXISTS academic_programs;
DROP TABLE IF EXISTS academic_courses;
DROP TABLE IF EXISTS course_assignments;
DROP TABLE IF EXISTS student_academic_records;
DROP TABLE IF EXISTS academic_sessions;
DROP TABLE IF EXISTS academic_semesters;
DROP TABLE IF EXISTS course_registrations;
DROP TABLE IF EXISTS examinations;
DROP TABLE IF EXISTS exam_results;
DROP TABLE IF EXISTS attendance_records;
DROP TABLE IF EXISTS attendance_sessions;
DROP TABLE IF EXISTS grade_scales;
DROP TABLE IF EXISTS academic_calendars;
DROP TABLE IF EXISTS class_schedules;
DROP TABLE IF EXISTS course_materials;
DROP TABLE IF EXISTS assignment_submissions;
DROP TABLE IF EXISTS academic_performance;
DROP TABLE IF EXISTS graduation_requirements;
DROP TABLE IF EXISTS student_transcripts;

-- =====================================================
-- 1. ACADEMIC PROGRAMS
-- =====================================================
CREATE TABLE academic_programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_code VARCHAR(20) NOT NULL UNIQUE,
    program_name VARCHAR(255) NOT NULL,
    program_description TEXT NULL,
    program_level ENUM('Certificate', 'Diploma', 'Bachelor', 'Master', 'PhD') NOT NULL,
    department VARCHAR(100) NOT NULL,
    duration_years DECIMAL(3,1) NOT NULL,
    total_credits_required INT NOT NULL DEFAULT 0,
    admission_requirements JSON NULL,
    program_head_id INT NULL, -- Reference to users table
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (program_code),
    INDEX idx_level (program_level),
    INDEX idx_department (department),
    INDEX idx_active (is_active),
    INDEX idx_program_head (program_head_id),
    FOREIGN KEY (program_head_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- 2. ACADEMIC SESSIONS
-- =====================================================
CREATE TABLE academic_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_name VARCHAR(100) NOT NULL UNIQUE,
    session_code VARCHAR(20) NOT NULL UNIQUE,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_current BOOLEAN DEFAULT FALSE,
    registration_start_date DATE NULL,
    registration_end_date DATE NULL,
    exam_start_date DATE NULL,
    exam_end_date DATE NULL,
    results_release_date DATE NULL,
    status ENUM('upcoming', 'active', 'completed', 'archived') DEFAULT 'upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (session_code),
    INDEX idx_current (is_current),
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date)
);

-- =====================================================
-- 3. ACADEMIC SEMESTERS
-- =====================================================
CREATE TABLE academic_semesters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    semester_number INT NOT NULL, -- 1, 2, 3, etc.
    semester_name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_current BOOLEAN DEFAULT FALSE,
    registration_deadline DATE NULL,
    add_drop_deadline DATE NULL,
    exam_period_start DATE NULL,
    exam_period_end DATE NULL,
    status ENUM('upcoming', 'active', 'completed', 'archived') DEFAULT 'upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_session_semester (session_id, semester_number),
    INDEX idx_session (session_id),
    INDEX idx_current (is_current),
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date),
    FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE CASCADE
);

-- =====================================================
-- 4. ACADEMIC COURSES
-- =====================================================
CREATE TABLE academic_courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    course_title VARCHAR(255) NOT NULL,
    course_description TEXT NULL,
    program_id INT NOT NULL,
    level INT NOT NULL, -- Year level (1, 2, 3, 4, 5)
    semester INT NOT NULL, -- Semester number (1, 2, 3)
    credit_hours DECIMAL(3,1) NOT NULL DEFAULT 0,
    contact_hours INT NOT NULL DEFAULT 0,
    course_type ENUM('core', 'elective', 'prerequisite', 'general') NOT NULL DEFAULT 'core',
    prerequisites JSON NULL, -- Array of course codes
    course_objectives TEXT NULL,
    course_outline TEXT NULL,
    assessment_methods JSON NULL, -- Array of assessment methods
    recommended_books JSON NULL, -- Array of book references
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (course_code),
    INDEX idx_program (program_id),
    INDEX idx_level_semester (level, semester),
    INDEX idx_type (course_type),
    INDEX idx_active (is_active),
    FOREIGN KEY (program_id) REFERENCES academic_programs(id) ON DELETE CASCADE
);

-- =====================================================
-- 5. COURSE ASSIGNMENTS
-- =====================================================
CREATE TABLE course_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    lecturer_id INT NOT NULL,
    session_id INT NOT NULL,
    semester_id INT NOT NULL,
    assignment_type ENUM('lecturer', 'assistant', 'coordinator', 'examiner') NOT NULL DEFAULT 'lecturer',
    class_group VARCHAR(50) NULL, -- For multiple class groups
    teaching_load DECIMAL(4,1) NOT NULL DEFAULT 0, -- Hours per week
    is_primary BOOLEAN DEFAULT TRUE, -- Primary lecturer for the course
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_by INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    
    UNIQUE KEY unique_course_lecturer (course_id, lecturer_id, session_id, semester_id, assignment_type),
    INDEX idx_course (course_id),
    INDEX idx_lecturer (lecturer_id),
    INDEX idx_session (session_id),
    INDEX idx_semester (semester_id),
    INDEX idx_assignment_type (assignment_type),
    INDEX idx_active (is_active),
    FOREIGN KEY (course_id) REFERENCES academic_courses(id) ON DELETE CASCADE,
    FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES academic_semesters(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 6. STUDENT ACADEMIC RECORDS
-- =====================================================
CREATE TABLE student_academic_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    program_id INT NOT NULL,
    current_level INT NOT NULL DEFAULT 1,
    current_semester INT NOT NULL DEFAULT 1,
    admission_session_id INT NOT NULL,
    admission_semester_id INT NOT NULL,
    admission_date DATE NOT NULL,
    expected_graduation_date DATE NULL,
    academic_status ENUM('active', 'suspended', 'withdrawn', 'graduated', 'transferred') DEFAULT 'active',
    probation_status ENUM('none', 'academic', 'disciplinary') DEFAULT 'none',
    gpa_cumulative DECIMAL(3,2) DEFAULT 0.00,
    total_credits_earned INT DEFAULT 0,
    total_credits_attempted INT DEFAULT 0,
    last_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_student_program (student_id, program_id),
    INDEX idx_student (student_id),
    INDEX idx_program (program_id),
    INDEX idx_status (academic_status),
    INDEX idx_level_semester (current_level, current_semester),
    INDEX idx_admission (admission_session_id, admission_semester_id),
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (program_id) REFERENCES academic_programs(id) ON DELETE CASCADE,
    FOREIGN KEY (admission_session_id) REFERENCES academic_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (admission_semester_id) REFERENCES academic_semesters(id) ON DELETE CASCADE
);

-- =====================================================
-- 7. COURSE REGISTRATIONS
-- =====================================================
CREATE TABLE course_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    session_id INT NOT NULL,
    semester_id INT NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    registration_status ENUM('registered', 'dropped', 'completed', 'failed', 'withdrawn') DEFAULT 'registered',
    grade VARCHAR(5) NULL, -- A, B+, B, C+, C, D+, D, F, etc.
    grade_points DECIMAL(3,2) NULL,
    credit_hours DECIMAL(3,1) NOT NULL DEFAULT 0,
    attendance_percentage DECIMAL(5,2) DEFAULT 0.00,
    final_score DECIMAL(5,2) NULL,
    is_retake BOOLEAN DEFAULT FALSE,
    original_registration_id INT NULL, -- For retake courses
    dropped_at TIMESTAMP NULL,
    dropped_reason VARCHAR(255) NULL,
    completed_at TIMESTAMP NULL,
    
    UNIQUE KEY unique_student_course_session (student_id, course_id, session_id, semester_id),
    INDEX idx_student (student_id),
    INDEX idx_course (course_id),
    INDEX idx_session (session_id),
    INDEX idx_semester (semester_id),
    INDEX idx_status (registration_status),
    INDEX idx_grade (grade),
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES academic_courses(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES academic_semesters(id) ON DELETE CASCADE
);

-- =====================================================
-- 8. EXAMINATIONS
-- =====================================================
CREATE TABLE examinations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    session_id INT NOT NULL,
    semester_id INT NOT NULL,
    exam_title VARCHAR(255) NOT NULL,
    exam_type ENUM('quiz', 'assignment', 'midterm', 'final', 'practical', 'oral', 'project') NOT NULL,
    exam_date DATE NOT NULL,
    exam_time TIME NOT NULL,
    duration_minutes INT NOT NULL,
    venue VARCHAR(255) NULL,
    max_marks DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    passing_marks DECIMAL(5,2) NOT NULL DEFAULT 50.00,
    weight_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00, -- Weight in final grade
    instructions TEXT NULL,
    created_by INT NOT NULL,
    status ENUM('draft', 'scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_course (course_id),
    INDEX idx_session (session_id),
    INDEX idx_semester (semester_id),
    INDEX idx_type (exam_type),
    INDEX idx_date (exam_date),
    INDEX idx_status (status),
    FOREIGN KEY (course_id) REFERENCES academic_courses(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES academic_semesters(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 9. EXAM RESULTS
-- =====================================================
CREATE TABLE exam_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    student_id INT NOT NULL,
    marks_obtained DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    percentage DECIMAL(5,2) GENERATED ALWAYS AS (marks_obtained * 100 / (SELECT max_marks FROM examinations WHERE id = exam_id)) STORED,
    grade VARCHAR(5) NULL,
    grade_points DECIMAL(3,2) NULL,
    is_absent BOOLEAN DEFAULT FALSE,
    remarks TEXT NULL,
    submitted_by INT NULL,
    submission_date TIMESTAMP NULL,
    verified_by INT NULL,
    verification_date TIMESTAMP NULL,
    status ENUM('pending', 'submitted', 'verified', 'rejected', 'published') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_exam_student (exam_id, student_id),
    INDEX idx_exam (exam_id),
    INDEX idx_student (student_id),
    INDEX idx_marks (marks_obtained),
    INDEX idx_grade (grade),
    INDEX idx_status (status),
    FOREIGN KEY (exam_id) REFERENCES examinations(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- 10. ATTENDANCE SESSIONS
-- =====================================================
CREATE TABLE attendance_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    lecturer_id INT NOT NULL,
    session_id INT NOT NULL,
    semester_id INT NOT NULL,
    session_date DATE NOT NULL,
    session_time TIME NOT NULL,
    duration_minutes INT NOT NULL DEFAULT 60,
    session_type ENUM('lecture', 'lab', 'tutorial', 'seminar', 'practical') NOT NULL DEFAULT 'lecture',
    venue VARCHAR(255) NULL,
    topic VARCHAR(255) NULL,
    total_students INT DEFAULT 0,
    present_students INT DEFAULT 0,
    absent_students INT DEFAULT 0,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_course (course_id),
    INDEX idx_lecturer (lecturer_id),
    INDEX idx_session (session_id),
    INDEX idx_semester (semester_id),
    INDEX idx_date (session_date),
    INDEX idx_status (status),
    FOREIGN KEY (course_id) REFERENCES academic_courses(id) ON DELETE CASCADE,
    FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES academic_semesters(id) ON DELETE CASCADE
);

-- =====================================================
-- 11. ATTENDANCE RECORDS
-- =====================================================
CREATE TABLE attendance_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attendance_session_id INT NOT NULL,
    student_id INT NOT NULL,
    attendance_status ENUM('present', 'absent', 'late', 'excused', 'medical_leave') NOT NULL DEFAULT 'present',
    arrival_time TIME NULL,
    departure_time TIME NULL,
    remarks TEXT NULL,
    marked_by INT NOT NULL,
    marked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_by INT NULL,
    verified_at TIMESTAMP NULL,
    
    UNIQUE KEY unique_session_student (attendance_session_id, student_id),
    INDEX idx_session (attendance_session_id),
    INDEX idx_student (student_id),
    INDEX idx_status (attendance_status),
    INDEX idx_marked_by (marked_by),
    FOREIGN KEY (attendance_session_id) REFERENCES attendance_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- 12. GRADE SCALES
-- =====================================================
CREATE TABLE grade_scales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grade_letter VARCHAR(5) NOT NULL UNIQUE,
    grade_point DECIMAL(3,2) NOT NULL,
    min_percentage DECIMAL(5,2) NOT NULL,
    max_percentage DECIMAL(5,2) NOT NULL,
    description VARCHAR(255) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_grade (grade_letter),
    INDEX idx_point (grade_point),
    INDEX idx_percentage_range (min_percentage, max_percentage),
    INDEX idx_active (is_active)
);

-- =====================================================
-- 13. ACADEMIC CALENDAR
-- =====================================================
CREATE TABLE academic_calendars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    semester_id INT NULL,
    event_title VARCHAR(255) NOT NULL,
    event_description TEXT NULL,
    event_type ENUM('academic', 'examination', 'holiday', 'registration', 'orientation', 'graduation', 'meeting') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_all_day BOOLEAN DEFAULT FALSE,
    venue VARCHAR(255) NULL,
    target_audience JSON NULL, -- Array of roles/departments
    is_mandatory BOOLEAN DEFAULT FALSE,
    status ENUM('draft', 'published', 'cancelled', 'completed') DEFAULT 'draft',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_session (session_id),
    INDEX idx_semester (semester_id),
    INDEX idx_type (event_type),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_status (status),
    FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES academic_semesters(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 14. CLASS SCHEDULES
-- =====================================================
CREATE TABLE class_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    lecturer_id INT NOT NULL,
    session_id INT NOT NULL,
    semester_id INT NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    venue VARCHAR(255) NULL,
    class_type ENUM('lecture', 'lab', 'tutorial', 'seminar', 'practical') NOT NULL DEFAULT 'lecture',
    frequency ENUM('weekly', 'biweekly', 'monthly') NOT NULL DEFAULT 'weekly',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_course (course_id),
    INDEX idx_lecturer (lecturer_id),
    INDEX idx_session (session_id),
    INDEX idx_semester (semester_id),
    INDEX idx_day_time (day_of_week, start_time),
    INDEX idx_active (is_active),
    FOREIGN KEY (course_id) REFERENCES academic_courses(id) ON DELETE CASCADE,
    FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES academic_semesters(id) ON DELETE CASCADE
);

-- =====================================================
-- 15. COURSE MATERIALS
-- =====================================================
CREATE TABLE course_materials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    session_id INT NOT NULL,
    semester_id INT NOT NULL,
    material_title VARCHAR(255) NOT NULL,
    material_type ENUM('lecture_note', 'assignment', 'reference', 'video', 'audio', 'presentation', 'document') NOT NULL,
    material_description TEXT NULL,
    file_path VARCHAR(500) NULL,
    file_size INT NULL,
    mime_type VARCHAR(100) NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    uploaded_by INT NOT NULL,
    is_public BOOLEAN DEFAULT TRUE,
    download_count INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    
    INDEX idx_course (course_id),
    INDEX idx_session (session_id),
    INDEX idx_semester (semester_id),
    INDEX idx_type (material_type),
    INDEX idx_uploaded_by (uploaded_by),
    INDEX idx_public (is_public),
    INDEX idx_active (is_active),
    FOREIGN KEY (course_id) REFERENCES academic_courses(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES academic_semesters(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 16. ASSIGNMENT SUBMISSIONS
-- =====================================================
CREATE TABLE assignment_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    assignment_id INT NOT NULL, -- Reference to examinations table where exam_type = 'assignment'
    student_id INT NOT NULL,
    submission_title VARCHAR(255) NULL,
    submission_text TEXT NULL,
    file_path VARCHAR(500) NULL,
    file_size INT NULL,
    mime_type VARCHAR(100) NULL,
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    due_date TIMESTAMP NOT NULL,
    is_late BOOLEAN DEFAULT FALSE,
    late_days INT DEFAULT 0,
    marks_obtained DECIMAL(5,2) NULL,
    max_marks DECIMAL(5,2) NOT NULL,
    grade VARCHAR(5) NULL,
    feedback TEXT NULL,
    graded_by INT NULL,
    graded_at TIMESTAMP NULL,
    status ENUM('submitted', 'graded', 'returned', 'rejected') DEFAULT 'submitted',
    
    UNIQUE KEY unique_assignment_student (assignment_id, student_id),
    INDEX idx_course (course_id),
    INDEX idx_assignment (assignment_id),
    INDEX idx_student (student_id),
    INDEX idx_submission_date (submission_date),
    INDEX idx_due_date (due_date),
    INDEX idx_status (status),
    FOREIGN KEY (course_id) REFERENCES academic_courses(id) ON DELETE CASCADE,
    FOREIGN KEY (assignment_id) REFERENCES examinations(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (graded_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- 17. ACADEMIC PERFORMANCE
-- =====================================================
CREATE TABLE academic_performance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    session_id INT NOT NULL,
    semester_id INT NOT NULL,
    gpa_semester DECIMAL(3,2) DEFAULT 0.00,
    gpa_cumulative DECIMAL(3,2) DEFAULT 0.00,
    total_credits_earned INT DEFAULT 0,
    total_credits_attempted INT DEFAULT 0,
    courses_registered INT DEFAULT 0,
    courses_completed INT DEFAULT 0,
    courses_failed INT DEFAULT 0,
    attendance_percentage DECIMAL(5,2) DEFAULT 0.00,
    academic_standing ENUM('excellent', 'good', 'satisfactory', 'probation', 'warning') DEFAULT 'satisfactory',
    honors_status ENUM('none', 'deans_list', 'honors', 'high_honors', 'highest_honors') DEFAULT 'none',
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_student_session_semester (student_id, session_id, semester_id),
    INDEX idx_student (student_id),
    INDEX idx_session (session_id),
    INDEX idx_semester (semester_id),
    INDEX idx_gpa_semester (gpa_semester),
    INDEX idx_gpa_cumulative (gpa_cumulative),
    INDEX idx_standing (academic_standing),
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES academic_semesters(id) ON DELETE CASCADE
);

-- =====================================================
-- 18. GRADUATION REQUIREMENTS
-- =====================================================
CREATE TABLE graduation_requirements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    requirement_type ENUM('credits', 'courses', 'gpa', 'attendance', 'internship', 'thesis', 'comprehensive_exam') NOT NULL,
    requirement_name VARCHAR(255) NOT NULL,
    requirement_value DECIMAL(10,2) NOT NULL, -- Could be credits, GPA, percentage, etc.
    requirement_unit VARCHAR(20) NULL, -- 'credits', 'percentage', 'gpa_points', etc.
    description TEXT NULL,
    is_mandatory BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_program (program_id),
    INDEX idx_type (requirement_type),
    INDEX idx_mandatory (is_mandatory),
    INDEX idx_active (is_active),
    FOREIGN KEY (program_id) REFERENCES academic_programs(id) ON DELETE CASCADE
);

-- =====================================================
-- 19. STUDENT TRANSCRIPTS
-- =====================================================
CREATE TABLE student_transcripts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    transcript_type ENUM('official', 'unofficial', 'provisional') NOT NULL,
    session_id INT NOT NULL,
    semester_id INT NULL, -- NULL for full transcript
    generated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    generated_by INT NOT NULL,
    file_path VARCHAR(500) NULL,
    file_size INT NULL,
    gpa_cumulative DECIMAL(3,2) NULL,
    total_credits_earned INT NULL,
    academic_standing VARCHAR(50) NULL,
    honors_awarded JSON NULL,
    status ENUM('draft', 'generated', 'verified', 'issued', 'cancelled') DEFAULT 'draft',
    issued_date TIMESTAMP NULL,
    issued_to VARCHAR(255) NULL,
    purpose VARCHAR(255) NULL,
    verification_code VARCHAR(50) NULL UNIQUE,
    expires_at TIMESTAMP NULL,
    
    INDEX idx_student (student_id),
    INDEX idx_type (transcript_type),
    INDEX idx_session (session_id),
    INDEX idx_semester (semester_id),
    INDEX idx_status (status),
    INDEX idx_verification_code (verification_code),
    INDEX idx_generated_by (generated_by),
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES academic_semesters(id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- Insert default academic programs
INSERT INTO academic_programs (program_code, program_name, program_description, program_level, department, duration_years, total_credits_required) VALUES
('BSN', 'Bachelor of Science in Nursing', 'Comprehensive nursing program preparing students for professional nursing practice', 'Bachelor', 'Nursing', 4.0, 240),
('BMS', 'Bachelor of Midwifery Science', 'Specialized program in midwifery and maternal healthcare', 'Bachelor', 'Midwifery', 4.0, 220),
('DIPN', 'Diploma in Nursing', 'Practical nursing program for entry-level nursing positions', 'Diploma', 'Nursing', 3.0, 180),
('DIPM', 'Diploma in Midwifery', 'Practical midwifery program for maternal healthcare', 'Diploma', 'Midwifery', 3.0, 160),
('CRTN', 'Certificate in Nursing', 'Basic nursing certificate program', 'Certificate', 'Nursing', 2.0, 120),
('CRTM', 'Certificate in Midwifery', 'Basic midwifery certificate program', 'Certificate', 'Midwifery', 2.0, 100);

-- Insert default academic sessions
INSERT INTO academic_sessions (session_name, session_code, start_date, end_date, is_current, registration_start_date, registration_end_date, exam_start_date, exam_end_date, status) VALUES
('2023/2024 Academic Year', '2023_2024', '2023-09-01', '2024-08-31', FALSE, '2023-08-15', '2023-09-15', '2024-04-15', '2024-05-15', 'completed'),
('2024/2025 Academic Year', '2024_2025', '2024-09-01', '2025-08-31', TRUE, '2024-08-15', '2024-09-15', '2025-04-15', '2025-05-15', 'active'),
('2025/2026 Academic Year', '2025_2026', '2025-09-01', '2026-08-31', FALSE, '2025-08-15', '2025-09-15', '2026-04-15', '2026-05-15', 'upcoming');

-- Insert default semesters for current session
INSERT INTO academic_semesters (session_id, semester_number, semester_name, start_date, end_date, is_current, registration_deadline, add_drop_deadline, exam_period_start, exam_period_end, status) VALUES
(2, 1, 'First Semester 2024/2025', '2024-09-01', '2025-01-31', TRUE, '2024-09-15', '2024-10-15', '2025-01-15', '2025-01-31', 'active'),
(2, 2, 'Second Semester 2024/2025', '2025-02-01', '2025-06-30', FALSE, '2025-02-15', '2025-03-15', '2025-06-15', '2025-06-30', 'upcoming');

-- Insert default courses for Nursing program
INSERT INTO academic_courses (course_code, course_title, course_description, program_id, level, semester, credit_hours, contact_hours, course_type, prerequisites, course_objectives) VALUES
('NSC101', 'Fundamentals of Nursing', 'Introduction to basic nursing concepts and skills', 1, 1, 1, 4.0, 60, 'core', NULL, 'Provide foundational nursing knowledge and skills'),
('NSC102', 'Anatomy and Physiology', 'Study of human body structure and function', 1, 1, 1, 5.0, 75, 'core', NULL, 'Understand human anatomy and physiological processes'),
('NSC103', 'Pharmacology', 'Study of drugs and their effects on the body', 1, 1, 2, 3.0, 45, 'core', '["NSC102"]', 'Understand drug actions, interactions, and nursing implications'),
('NSC104', 'Medical-Surgical Nursing I', 'Nursing care for adult patients with medical-surgical conditions', 1, 2, 1, 5.0, 75, 'core', '["NSC101","NSC102"]', 'Provide nursing care for medical-surgical patients'),
('NSC105', 'Pediatric Nursing', 'Nursing care for infants, children, and adolescents', 1, 2, 2, 4.0, 60, 'core', '["NSC101","NSC102"]', 'Provide specialized nursing care for pediatric patients'),
('NSC201', 'Obstetric and Gynecological Nursing', 'Nursing care for women during pregnancy, childbirth, and postpartum', 1, 3, 1, 5.0, 75, 'core', '["NSC104"]', 'Provide comprehensive nursing care for women'),
('NSC202', 'Community Health Nursing', 'Nursing care in community settings', 1, 3, 2, 4.0, 60, 'core', '["NSC104","NSC105"]', 'Promote health and prevent disease in communities'),
('NSC203', 'Psychiatric Mental Health Nursing', 'Nursing care for patients with mental health disorders', 1, 4, 1, 4.0, 60, 'core', '["NSC104"]', 'Provide nursing care for patients with mental health conditions'),
('NSC204', 'Nursing Research and Evidence-Based Practice', 'Introduction to nursing research methodology', 1, 4, 2, 3.0, 45, 'core', '["NSC104","NSC105"]', 'Understand research principles and evidence-based practice'),
('NSC205', 'Nursing Leadership and Management', 'Principles of nursing leadership and management', 1, 4, 2, 3.0, 45, 'core', '["NSC201","NSC202"]', 'Develop leadership and management skills in nursing');

-- Insert default courses for Midwifery program
INSERT INTO academic_courses (course_code, course_title, course_description, program_id, level, semester, credit_hours, contact_hours, course_type, prerequisites, course_objectives) VALUES
('MID101', 'Foundations of Midwifery', 'Introduction to midwifery practice and principles', 2, 1, 1, 4.0, 60, 'core', NULL, 'Provide foundational midwifery knowledge'),
('MID102', 'Reproductive Anatomy and Physiology', 'Study of female reproductive system', 2, 1, 1, 4.0, 60, 'core', NULL, 'Understand female reproductive anatomy and physiology'),
('MID103', 'Antenatal Care', 'Care during pregnancy', 2, 1, 2, 4.0, 60, 'core', '["MID102"]', 'Provide comprehensive antenatal care'),
('MID104', 'Intrapartum Care', 'Care during labor and delivery', 2, 2, 1, 5.0, 75, 'core', '["MID103"]', 'Provide safe intrapartum care'),
('MID105', 'Postnatal Care', 'Care after childbirth', 2, 2, 2, 4.0, 60, 'core', '["MID104"]', 'Provide comprehensive postnatal care'),
('MID201', 'Neonatal Care', 'Care for newborn infants', 2, 3, 1, 4.0, 60, 'core', '["MID104","MID105"]', 'Provide specialized neonatal care'),
('MID202', 'High-Risk Pregnancy', 'Management of complicated pregnancies', 2, 3, 2, 4.0, 60, 'core', '["MID103","MID104"]', 'Manage high-risk pregnancy cases'),
('MID203', 'Family Planning', 'Contraception and family planning services', 2, 4, 1, 3.0, 45, 'core', '["MID105"]', 'Provide family planning counseling and services'),
('MID204', 'Midwifery Research', 'Research methods in midwifery', 2, 4, 2, 3.0, 45, 'core', '["MID201","MID202"]', 'Understand midwifery research principles'),
('MID205', 'Professional Midwifery Practice', 'Professional aspects of midwifery', 2, 4, 2, 3.0, 45, 'core', '["MID201","MID202"]', 'Develop professional midwifery practice');

-- Insert default grade scales
INSERT INTO grade_scales (grade_letter, grade_point, min_percentage, max_percentage, description) VALUES
('A+', 4.00, 85.00, 100.00, 'Excellent'),
('A', 4.00, 80.00, 84.99, 'Excellent'),
('B+', 3.50, 75.00, 79.99, 'Very Good'),
('B', 3.00, 70.00, 74.99, 'Good'),
('C+', 2.50, 65.00, 69.99, 'Fairly Good'),
('C', 2.00, 60.00, 64.99, 'Satisfactory'),
('D+', 1.50, 55.00, 59.99, 'Poor'),
('D', 1.00, 50.00, 54.99, 'Pass'),
('F', 0.00, 0.00, 49.99, 'Fail');

-- Insert graduation requirements for Nursing program
INSERT INTO graduation_requirements (program_id, requirement_type, requirement_name, requirement_value, requirement_unit, description, is_mandatory) VALUES
(1, 'credits', 'Total Credit Hours', 240.00, 'credits', 'Total credits required for graduation', TRUE),
(1, 'gpa', 'Minimum Cumulative GPA', 2.00, 'gpa_points', 'Minimum GPA required for graduation', TRUE),
(1, 'attendance', 'Minimum Attendance', 75.00, 'percentage', 'Minimum attendance percentage required', TRUE),
(1, 'courses', 'Core Courses', 20.00, 'courses', 'All core courses must be completed', TRUE),
(1, 'internship', 'Clinical Internship', 1.00, 'semesters', 'Clinical internship requirement', TRUE);

-- Insert graduation requirements for Midwifery program
INSERT INTO graduation_requirements (program_id, requirement_type, requirement_name, requirement_value, requirement_unit, description, is_mandatory) VALUES
(2, 'credits', 'Total Credit Hours', 220.00, 'credits', 'Total credits required for graduation', TRUE),
(2, 'gpa', 'Minimum Cumulative GPA', 2.00, 'gpa_points', 'Minimum GPA required for graduation', TRUE),
(2, 'attendance', 'Minimum Attendance', 75.00, 'percentage', 'Minimum attendance percentage required', TRUE),
(2, 'courses', 'Core Courses', 18.00, 'courses', 'All core courses must be completed', TRUE),
(2, 'internship', 'Clinical Internship', 1.00, 'semesters', 'Clinical internship requirement', TRUE);

-- =====================================================
-- CREATE STORED PROCEDURES FOR ACADEMIC OPERATIONS
-- =====================================================

DELIMITER //

-- Procedure to register student for courses
CREATE PROCEDURE register_student_courses(
    IN p_student_id INT,
    IN p_session_id INT,
    IN p_semester_id INT,
    IN p_course_ids JSON, -- Array of course IDs
    IN p_registered_by INT
)
BEGIN
    DECLARE v_course_id INT;
    DECLARE v_course_count INT DEFAULT 0;
    DECLARE v_credit_hours DECIMAL(3,1);
    DECLARE v_total_credits DECIMAL(5,1) DEFAULT 0;
    DECLARE v_max_credits INT DEFAULT 24; -- Maximum credits per semester
    DECLARE done INT DEFAULT FALSE;
    DECLARE course_cursor CURSOR FOR SELECT value FROM JSON_TABLE(p_course_ids, '$[*]' COLUMNS (value INT PATH '$')) AS jt;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    -- Check if student is eligible for registration
    SELECT COUNT(*) INTO v_course_count 
    FROM course_registrations 
    WHERE student_id = p_student_id 
    AND session_id = p_session_id 
    AND semester_id = p_semester_id 
    AND registration_status = 'registered';
    
    IF v_course_count > 0 THEN
        SELECT 'Student already has registered courses for this semester' as message;
    ELSE
        OPEN course_cursor;
        read_loop: LOOP
            FETCH course_cursor INTO v_course_id;
            IF done THEN
                LEAVE read_loop;
            END IF;
            
            -- Get course credit hours
            SELECT credit_hours INTO v_credit_hours 
            FROM academic_courses 
            WHERE id = v_course_id;
            
            -- Check credit limit
            IF v_total_credits + v_credit_hours <= v_max_credits THEN
                -- Register for course
                INSERT INTO course_registrations (
                    student_id, course_id, session_id, semester_id, credit_hours
                ) VALUES (
                    p_student_id, v_course_id, p_session_id, p_semester_id, v_credit_hours
                );
                
                SET v_total_credits = v_total_credits + v_credit_hours;
            END IF;
        END LOOP;
        CLOSE course_cursor;
        
        -- Update student academic record
        UPDATE student_academic_records 
        SET last_updated_at = CURRENT_TIMESTAMP 
        WHERE student_id = p_student_id;
        
        SELECT CONCAT('Successfully registered for ', v_course_count, ' courses with ', v_total_credits, ' credits') as message;
    END IF;
END //

-- Procedure to calculate student GPA
CREATE PROCEDURE calculate_student_gpa(
    IN p_student_id INT,
    IN p_session_id INT,
    IN p_semester_id INT
)
BEGIN
    DECLARE v_gpa_semester DECIMAL(3,2) DEFAULT 0.00;
    DECLARE v_gpa_cumulative DECIMAL(3,2) DEFAULT 0.00;
    DECLARE v_total_quality_points DECIMAL(8,2) DEFAULT 0.00;
    DECLARE v_total_credits DECIMAL(5,1) DEFAULT 0.00;
    DECLARE v_cumulative_quality_points DECIMAL(8,2) DEFAULT 0.00;
    DECLARE v_cumulative_credits DECIMAL(5,1) DEFAULT 0.00;
    
    -- Calculate semester GPA
    SELECT 
        SUM(cr.credit_hours * COALESCE(er.grade_points, 0)) as quality_points,
        SUM(cr.credit_hours) as credits
    INTO v_total_quality_points, v_total_credits
    FROM course_registrations cr
    LEFT JOIN exam_results er ON cr.course_id = (
        SELECT course_id FROM examinations e 
        WHERE e.session_id = p_session_id 
        AND e.semester_id = p_semester_id 
        AND e.exam_type = 'final'
        LIMIT 1
    ) AND er.student_id = p_student_id
    WHERE cr.student_id = p_student_id 
    AND cr.session_id = p_session_id 
    AND cr.semester_id = p_semester_id 
    AND cr.registration_status = 'completed'
    AND cr.grade IS NOT NULL;
    
    IF v_total_credits > 0 THEN
        SET v_gpa_semester = v_total_quality_points / v_total_credits;
    END IF;
    
    -- Calculate cumulative GPA
    SELECT 
        SUM(cr.credit_hours * COALESCE(er.grade_points, 0)) as quality_points,
        SUM(cr.credit_hours) as credits
    INTO v_cumulative_quality_points, v_cumulative_credits
    FROM course_registrations cr
    LEFT JOIN exam_results er ON cr.course_id = (
        SELECT e.course_id FROM examinations e 
        WHERE e.exam_type = 'final'
        AND EXISTS (
            SELECT 1 FROM course_registrations cr2 
            WHERE cr2.course_id = e.course_id 
            AND cr2.student_id = cr.student_id
        )
        LIMIT 1
    ) AND er.student_id = p_student_id
    WHERE cr.student_id = p_student_id 
    AND cr.registration_status = 'completed'
    AND cr.grade IS NOT NULL;
    
    IF v_cumulative_credits > 0 THEN
        SET v_gpa_cumulative = v_cumulative_quality_points / v_cumulative_credits;
    END IF;
    
    -- Update academic performance
    INSERT INTO academic_performance (
        student_id, session_id, semester_id, gpa_semester, gpa_cumulative,
        total_credits_earned, total_credits_attempted, courses_registered, courses_completed
    ) VALUES (
        p_student_id, p_session_id, p_semester_id, v_gpa_semester, v_gpa_cumulative,
        v_total_credits, v_total_credits, 
        (SELECT COUNT(*) FROM course_registrations WHERE student_id = p_student_id AND session_id = p_session_id AND semester_id = p_semester_id),
        (SELECT COUNT(*) FROM course_registrations WHERE student_id = p_student_id AND session_id = p_session_id AND semester_id = p_semester_id AND registration_status = 'completed')
    ) ON DUPLICATE KEY UPDATE
        gpa_semester = VALUES(gpa_semester),
        gpa_cumulative = VALUES(gpa_cumulative),
        total_credits_earned = VALUES(total_credits_earned),
        total_credits_attempted = VALUES(total_credits_attempted),
        calculated_at = CURRENT_TIMESTAMP;
    
    -- Update student academic record
    UPDATE student_academic_records 
    SET gpa_cumulative = v_gpa_cumulative,
        total_credits_earned = v_cumulative_credits,
        last_updated_at = CURRENT_TIMESTAMP
    WHERE student_id = p_student_id;
    
    SELECT v_gpa_semester as semester_gpa, v_gpa_cumulative as cumulative_gpa;
END //

-- Procedure to mark attendance
CREATE PROCEDURE mark_attendance(
    IN p_attendance_session_id INT,
    IN p_student_ids JSON, -- Array of student IDs
    IN p_attendance_status ENUM('present', 'absent', 'late', 'excused', 'medical_leave'),
    IN p_marked_by INT
)
BEGIN
    DECLARE v_student_id INT;
    DECLARE v_done INT DEFAULT FALSE;
    DECLARE attendance_cursor CURSOR FOR SELECT value FROM JSON_TABLE(p_student_ids, '$[*]' COLUMNS (value INT PATH '$')) AS jt;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = TRUE;
    
    OPEN attendance_cursor;
    attendance_loop: LOOP
        FETCH attendance_cursor INTO v_student_id;
        IF v_done THEN
            LEAVE attendance_loop;
        END IF;
        
        -- Insert or update attendance record
        INSERT INTO attendance_records (
            attendance_session_id, student_id, attendance_status, marked_by
        ) VALUES (
            p_attendance_session_id, v_student_id, p_attendance_status, p_marked_by
        ) ON DUPLICATE KEY UPDATE
            attendance_status = VALUES(attendance_status),
            marked_by = VALUES(marked_by),
            marked_at = CURRENT_TIMESTAMP;
    END LOOP;
    CLOSE attendance_cursor;
    
    -- Update attendance session statistics
    UPDATE attendance_sessions 
    SET 
        total_students = (SELECT COUNT(*) FROM course_registrations cr WHERE cr.course_id = (SELECT course_id FROM attendance_sessions WHERE id = p_attendance_session_id)),
        present_students = (SELECT COUNT(*) FROM attendance_records WHERE attendance_session_id = p_attendance_session_id AND attendance_status = 'present'),
        absent_students = (SELECT COUNT(*) FROM attendance_records WHERE attendance_session_id = p_attendance_session_id AND attendance_status = 'absent')
    WHERE id = p_attendance_session_id;
    
    SELECT CONCAT('Attendance marked for ', JSON_LENGTH(p_student_ids), ' students') as message;
END //

-- Procedure to generate student transcript
CREATE PROCEDURE generate_student_transcript(
    IN p_student_id INT,
    IN p_transcript_type ENUM('official', 'unofficial', 'provisional'),
    IN p_session_id INT,
    IN p_semester_id INT, -- NULL for full transcript
    IN p_generated_by INT,
    IN p_purpose VARCHAR(255)
)
BEGIN
    DECLARE v_transcript_id INT;
    DECLARE v_gpa_cumulative DECIMAL(3,2);
    DECLARE v_total_credits INT;
    DECLARE v_verification_code VARCHAR(50);
    
    -- Get student academic data
    SELECT 
        COALESCE(ap.gpa_cumulative, sar.gpa_cumulative, 0.00),
        COALESCE(ap.total_credits_earned, sar.total_credits_earned, 0)
    INTO v_gpa_cumulative, v_total_credits
    FROM student_academic_records sar
    LEFT JOIN academic_performance ap ON sar.student_id = ap.student_id 
        AND (p_session_id IS NULL OR ap.session_id = p_session_id)
        AND (p_semester_id IS NULL OR ap.semester_id = p_semester_id)
    WHERE sar.student_id = p_student_id
    LIMIT 1;
    
    -- Generate verification code
    SET v_verification_code = CONCAT('ISNM', DATE_FORMAT(NOW(), '%Y%m%d'), LPAD(p_student_id, 6, '0'), LPAD(FLOOR(RAND() * 10000), 4, '0'));
    
    -- Insert transcript record
    INSERT INTO student_transcripts (
        student_id, transcript_type, session_id, semester_id, generated_by,
        gpa_cumulative, total_credits_earned, purpose, verification_code,
        expires_at
    ) VALUES (
        p_student_id, p_transcript_type, p_session_id, p_semester_id, p_generated_by,
        v_gpa_cumulative, v_total_credits, p_purpose, v_verification_code,
        DATE_ADD(NOW(), INTERVAL 6 MONTH)
    );
    
    SET v_transcript_id = LAST_INSERT_ID();
    
    SELECT v_transcript_id as transcript_id, v_verification_code as verification_code;
END //

-- Procedure to get student academic summary
CREATE PROCEDURE get_student_academic_summary(IN p_student_id INT)
BEGIN
    -- Get student basic info
    SELECT u.id, u.full_name, u.index_number, u.email, u.phone,
           sar.program_id, ap.program_name, sar.current_level, sar.current_semester,
           sar.academic_status, sar.gpa_cumulative, sar.total_credits_earned
    FROM users u
    JOIN student_academic_records sar ON u.id = sar.student_id
    JOIN academic_programs ap ON sar.program_id = ap.id
    WHERE u.id = p_student_id;
    
    -- Get current semester courses
    SELECT cr.course_id, ac.course_code, ac.course_title, cr.credit_hours, 
           cr.registration_status, cr.grade, cr.grade_points
    FROM course_registrations cr
    JOIN academic_courses ac ON cr.course_id = ac.id
    WHERE cr.student_id = p_student_id 
    AND cr.session_id = (SELECT id FROM academic_sessions WHERE is_current = TRUE)
    AND cr.semester_id = (SELECT id FROM academic_semesters WHERE is_current = TRUE);
    
    -- Get recent academic performance
    SELECT session_name, semester_name, gpa_semester, gpa_cumulative, 
           total_credits_earned, academic_standing, honors_status
    FROM academic_performance ap
    JOIN academic_sessions s ON ap.session_id = s.id
    JOIN academic_semesters sem ON ap.semester_id = sem.id
    WHERE ap.student_id = p_student_id
    ORDER BY s.start_date DESC, sem.start_date DESC
    LIMIT 5;
END //

DELIMITER ;

-- =====================================================
-- CREATE VIEWS FOR ACADEMIC OPERATIONS
-- =====================================================

-- View for student course registration summary
CREATE VIEW student_course_registration_summary AS
SELECT 
    u.id as student_id,
    u.full_name,
    u.index_number,
    ap.program_name,
    s.session_name,
    sem.semester_name,
    COUNT(cr.id) as registered_courses,
    SUM(cr.credit_hours) as total_credits,
    COUNT(CASE WHEN cr.grade IS NOT NULL THEN 1 END) as completed_courses,
    COUNT(CASE WHEN cr.grade IN ('F', 'D', 'D+') THEN 1 END) as failed_courses,
    AVG(CASE WHEN cr.grade_points IS NOT NULL THEN cr.grade_points END) as average_grade_points
FROM users u
JOIN student_academic_records sar ON u.id = sar.student_id
JOIN academic_programs ap ON sar.program_id = ap.id
JOIN course_registrations cr ON u.id = cr.student_id
JOIN academic_sessions s ON cr.session_id = s.id
JOIN academic_semesters sem ON cr.semester_id = sem.id
GROUP BY u.id, u.full_name, u.index_number, ap.program_name, s.session_name, sem.semester_name;

-- View for course enrollment statistics
CREATE VIEW course_enrollment_statistics AS
SELECT 
    ac.id as course_id,
    ac.course_code,
    ac.course_title,
    ap.program_name,
    ac.level,
    ac.semester,
    COUNT(cr.id) as enrolled_students,
    SUM(cr.credit_hours) as total_credit_hours,
    COUNT(CASE WHEN cr.registration_status = 'completed' THEN 1 END) as completed_students,
    COUNT(CASE WHEN cr.grade IN ('F', 'D', 'D+') THEN 1 END) as failed_students,
    AVG(CASE WHEN cr.grade_points IS NOT NULL THEN cr.grade_points END) as average_grade_points
FROM academic_courses ac
JOIN academic_programs ap ON ac.program_id = ap.id
LEFT JOIN course_registrations cr ON ac.id = cr.course_id
GROUP BY ac.id, ac.course_code, ac.course_title, ap.program_name, ac.level, ac.semester;

-- View for lecturer course assignments
CREATE VIEW lecturer_course_assignments AS
SELECT 
    u.id as lecturer_id,
    u.full_name as lecturer_name,
    u.email as lecturer_email,
    ac.id as course_id,
    ac.course_code,
    ac.course_title,
    ap.program_name,
    s.session_name,
    sem.semester_name,
    ca.assignment_type,
    ca.teaching_load,
    ca.is_primary
FROM users u
JOIN course_assignments ca ON u.id = ca.lecturer_id
JOIN academic_courses ac ON ca.course_id = ac.id
JOIN academic_programs ap ON ac.program_id = ap.id
JOIN academic_sessions s ON ca.session_id = s.id
JOIN academic_semesters sem ON ca.semester_id = sem.id
WHERE ca.is_active = TRUE;

-- View for examination results summary
CREATE VIEW examination_results_summary AS
SELECT 
    e.id as exam_id,
    e.exam_title,
    e.exam_type,
    ac.course_code,
    ac.course_title,
    s.session_name,
    sem.semester_name,
    e.exam_date,
    e.max_marks,
    COUNT(er.id) as total_students,
    COUNT(CASE WHEN er.marks_obtained >= e.passing_marks THEN 1 END) as passed_students,
    COUNT(CASE WHEN er.marks_obtained < e.passing_marks THEN 1 END) as failed_students,
    AVG(er.marks_obtained) as average_marks,
    MAX(er.marks_obtained) as highest_marks,
    MIN(er.marks_obtained) as lowest_marks
FROM examinations e
JOIN academic_courses ac ON e.course_id = ac.id
JOIN academic_sessions s ON e.session_id = s.id
JOIN academic_semesters sem ON e.semester_id = sem.id
LEFT JOIN exam_results er ON e.id = er.exam_id
GROUP BY e.id, e.exam_title, e.exam_type, ac.course_code, ac.course_title, s.session_name, sem.semester_name, e.exam_date, e.max_marks;

-- View for attendance statistics
CREATE VIEW attendance_statistics AS
SELECT 
    ac.id as course_id,
    ac.course_code,
    ac.course_title,
    s.session_name,
    sem.semester_name,
    COUNT(DISTINCT ats.id) as total_sessions,
    COUNT(DISTINCT ar.student_id) as total_students,
    COUNT(CASE WHEN ar.attendance_status = 'present' THEN 1 END) as total_present,
    COUNT(CASE WHEN ar.attendance_status = 'absent' THEN 1 END) as total_absent,
    ROUND(COUNT(CASE WHEN ar.attendance_status = 'present' THEN 1 END) * 100.0 / 
          (COUNT(CASE WHEN ar.attendance_status = 'present' THEN 1 END) + 
           COUNT(CASE WHEN ar.attendance_status = 'absent' THEN 1 END)), 2) as attendance_percentage
FROM academic_courses ac
JOIN attendance_sessions ats ON ac.id = ats.course_id
JOIN academic_sessions s ON ats.session_id = s.id
JOIN academic_semesters sem ON ats.semester_id = sem.id
LEFT JOIN attendance_records ar ON ats.id = ar.attendance_session_id
GROUP BY ac.id, ac.course_code, ac.course_title, s.session_name, sem.semester_name;

-- =====================================================
-- TRIGGERS FOR AUTOMATIC CALCULATIONS
-- =====================================================

DELIMITER //

-- Trigger to update course registration statistics
CREATE TRIGGER after_course_registration_insert
AFTER INSERT ON course_registrations
FOR EACH ROW
BEGIN
    -- Update student academic record
    UPDATE student_academic_records 
    SET last_updated_at = CURRENT_TIMESTAMP 
    WHERE student_id = NEW.student_id;
    
    -- Log the activity
    INSERT INTO dashboard_activity_logs (
        user_id, action, entity_type, entity_id, description
    ) VALUES (
        NEW.student_id, 'create', 'course_registration', NEW.id,
        CONCAT('Registered for course: ', (SELECT course_title FROM academic_courses WHERE id = NEW.course_id))
    );
END //

-- Trigger to update exam results statistics
CREATE TRIGGER after_exam_result_insert
AFTER INSERT ON exam_results
FOR EACH ROW
BEGIN
    DECLARE v_grade VARCHAR(5);
    DECLARE v_grade_points DECIMAL(3,2);
    
    -- Calculate grade based on percentage
    SELECT grade_letter, grade_point INTO v_grade, v_grade_points
    FROM grade_scales 
    WHERE NEW.percentage >= min_percentage AND NEW.percentage <= max_percentage
    LIMIT 1;
    
    -- Update the exam result with grade
    UPDATE exam_results 
    SET grade = v_grade, grade_points = v_grade_points
    WHERE id = NEW.id;
    
    -- Update course registration with grade
    UPDATE course_registrations 
    SET grade = v_grade, grade_points = v_grade_points
    WHERE student_id = NEW.student_id 
    AND course_id = (SELECT course_id FROM examinations WHERE id = NEW.exam_id);
END //

DELIMITER ;

-- =====================================================
-- FINAL SETUP COMPLETE
-- =====================================================

-- Grant necessary permissions for stored procedures
GRANT EXECUTE ON PROCEDURE register_student_courses TO 'root'@'localhost';
GRANT EXECUTE ON PROCEDURE calculate_student_gpa TO 'root'@'localhost';
GRANT EXECUTE ON PROCEDURE mark_attendance TO 'root'@'localhost';
GRANT EXECUTE ON PROCEDURE generate_student_transcript TO 'root'@'localhost';
GRANT EXECUTE ON PROCEDURE get_student_academic_summary TO 'root'@'localhost';

-- =====================================================
-- SETUP COMPLETE MESSAGE
-- =====================================================
SELECT 'ISNM Academic Management Setup Complete!' as status,
       COUNT(*) as total_tables_created
FROM information_schema.tables 
WHERE table_schema = 'isnm_db' 
AND table_name IN ('academic_programs', 'academic_courses', 'course_assignments', 'student_academic_records', 'examinations', 'exam_results', 'attendance_records', 'grade_scales');
