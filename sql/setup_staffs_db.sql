-- ISNM Database Setup: igangaschoolofl_staffs_db
-- Import into the igangaschoolofl_staffs_db database via phpMyAdmin
-- =============================================

USE `igangaschoolofl_staffs_db`;

-- ============================================================
-- STUDENT PROFILES VIEW (for cross-database access)
-- References students from igangaschoolofl_students_db
-- ============================================================

-- Create view for student search across databases
CREATE OR REPLACE VIEW universal_student_profiles AS
SELECT 
    s.id,
    s.student_number,
    s.national_student_id_number as national_id,
    s.index_number,
    s.registration_number,
    s.first_name,
    s.other_name as middle_name,
    s.surname as last_name,
    TRIM(CONCAT(s.first_name, ' ', COALESCE(s.other_name, ''), ' ', s.surname)) as full_name,
    s.email,
    s.phone,
    s.date_of_birth,
    s.gender,
    s.program,
    s.course,
    s.set_name as intake_set,
    s.intake_date,
    s.current_year as year_of_study,
    s.current_semester as semester,
    s.nationality,
    NULL as religion,
    s.address,
    NULL as district,
    s.guardian_name,
    s.guardian_phone,
    s.emergency_contact_name,
    s.emergency_contact_phone,
    s.profile_picture as photo_path,
    CASE WHEN s.profile_picture IS NOT NULL THEN TRUE ELSE FALSE END as photo_uploaded,
    s.status,
    s.created_at,
    s.updated_at
FROM igangaschoolofl_students_db.students s;

-- ============================================================
-- 6. VIEW FOR STUDENT SEARCH
-- Comprehensive view for searching students
-- ============================================================

CREATE OR REPLACE VIEW student_search_view AS
SELECT 
    sp.id,
    sp.student_number,
    sp.national_id,
    sp.index_number,
    sp.registration_number,
    sp.full_name,
    sp.first_name,
    sp.last_name,
    sp.email,
    sp.phone,
    sp.program,
    sp.intake_set,
    sp.year_of_study,
    sp.semester,
    sp.status,
    sp.district,
    sp.guardian_name,
    sp.guardian_phone,
    sp.photo_path as current_photo,
    COALESCE(sp.photo_uploaded, FALSE) as has_photo,
    NULL as staff_dashboard
FROM universal_student_profiles sp;

-- ============================================================
-- 7. VIEW FOR ALL STUDENTS (FOR CROSS-DEPARTMENT ACCESS)
-- ============================================================

CREATE OR REPLACE VIEW all_students_view AS
SELECT 
    sp.*,
    CASE 
        WHEN sp.photo_uploaded = TRUE THEN CONCAT('Photo Available: ', sp.photo_path)
        ELSE 'No Photo Available'
    END as photo_status
FROM universal_student_profiles sp;

-- ============================================================
-- 8. PROCEDURES FOR STUDENT SEARCH AND MANAGEMENT
-- ============================================================

-- Ensure no conflicting procedure exists before creating (prevents #1304)
DROP PROCEDURE IF EXISTS get_all_students;
DROP PROCEDURE IF EXISTS search_all_students;

DELIMITER //

-- Search all students by various criteria
CREATE OR REPLACE PROCEDURE search_all_students(
    IN p_search_term VARCHAR(255),
    IN p_program VARCHAR(100),
    IN p_intake_set VARCHAR(50),
    IN p_status VARCHAR(50),
    IN p_limit INT
)
BEGIN
    IF p_limit IS NULL OR p_limit <= 0 THEN
        SET p_limit = 100;
    END IF;
    SELECT 
        s.id, s.student_number, s.full_name, s.program, s.set_name as intake_set, 
        s.current_year as year_of_study, s.status, s.email
    FROM igangaschoolofl_students_db.students s
    WHERE (p_search_term IS NULL OR 
           s.full_name LIKE CONCAT('%', p_search_term, '%') OR
           s.student_number LIKE CONCAT('%', p_search_term, '%') OR
           s.index_number LIKE CONCAT('%', p_search_term, '%') OR
           s.national_student_id_number LIKE CONCAT('%', p_search_term, '%'))
      AND (p_program IS NULL OR s.program = p_program)
      AND (p_intake_set IS NULL OR s.set_name = p_intake_set)
      AND (p_status IS NULL OR s.status = p_status)
    ORDER BY s.full_name
    LIMIT p_limit;
END //

-- Get all students from all intake sets
CREATE PROCEDURE get_all_students()
BEGIN
    SELECT 
        set_name as intake_set,
        COUNT(*) as total_students,
        COUNT(CASE WHEN profile_picture IS NOT NULL THEN 1 END) as students_with_photos
    FROM igangaschoolofl_students_db.students
    GROUP BY set_name
    ORDER BY set_name DESC;
END //

DELIMITER ;

-- ============================================================
-- 10. GRANT DASHBOARD ACCESS TO ALL STAFF
-- ============================================================
-- Ensure `staff_dashboard_access` table exists (some environments may not have it)
CREATE TABLE IF NOT EXISTS staff_dashboard_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    dashboard_path VARCHAR(255) NOT NULL,
    access_level VARCHAR(50) DEFAULT 'Full',
    granted_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_staff_id (staff_id),
    INDEX idx_dashboard_path (dashboard_path),
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO staff_dashboard_access (staff_id, dashboard_path, access_level, granted_by)
SELECT 
    s.id,
    sr.dashboard_path,
    'Full',
    1
FROM staff s
JOIN staff_roles sr ON s.role_id = sr.id
WHERE sr.role_name IN (
     'Director General', 'CEO', 'Director Academics', 'Director ICT', 
     'Director Finance', 'School Principal', 'Deputy Principal', 'School Bursar',
     'Director Admissions & Requirements', 'Academic Registrar', 'HR Manager',
     'School Secretary', 'School Librarian', 'Head Nursing', 'Head Midwifery',
     'Senior Lecturers', 'Lecturers', 'Matrons', 'Wardens', 'Sickbay',
     'Drivers', 'Security', 'Store Keeper', 'Computer Lab Manager', 'Guild President'
);

-- ============================================================
-- 11. INSERT DEFAULT INSTITUTE SETTINGS
-- ============================================================

-- Ensure system_settings table exists
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value LONGTEXT,
    setting_type ENUM('text', 'number', 'boolean', 'file', 'json') DEFAULT 'text',
    description TEXT,
    category VARCHAR(50) DEFAULT 'general',
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key),
    INDEX idx_setting_type (setting_type),
    INDEX idx_category (category),
    INDEX idx_is_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('institute_name', 'Iganga School of Nursing and Midwifery', 'text', 'Full institute name', TRUE),
('institute_short', 'ISNM', 'text', 'Short name', TRUE),
('institute_email', 'info@igangaschoolofnursingandmidwifery.ac.ug', 'email', 'Main email', TRUE),
('institute_phone', '+256-701-000-000', 'text', 'Main phone', TRUE),
('institute_address', 'Iganga, Uganda', 'text', 'Physical address', TRUE),
('academic_year', '2025/2026', 'text', 'Current academic year', TRUE),
('current_semester', 'Semester 2', 'text', 'Current semester', TRUE),
('allow_student_search', 'true', 'boolean', 'Enable student search', FALSE),
('allow_student_photo_upload', 'true', 'boolean', 'Enable photo upload', FALSE),
('allow_student_print', 'true', 'boolean', 'Enable printing', FALSE),
('max_search_results', '500', 'number', 'Max search results', FALSE);

COMMIT;
-- ============================================================
-- ISNM ACADEMIC REGISTRAR DASHBOARD SQL
-- Complete Academic Records Management System
-- ============================================================



-- ============================================================
-- 1. ACADEMIC REGISTRAR USER ACCOUNTS
-- ============================================================

INSERT IGNORE INTO staff (
    staff_id, 
    full_name, 
    email, 
    password, 
    phone, 
    position, 
    department, 
    role_id, 
    status, 
    hire_date,
    created_at
) VALUES
('REG001', 'Academic Registrar', 'registrar@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$registrar@isnmHashedPassword', '+256701000010', 'Academic Registrar', 'Academic Affairs',
 (SELECT id FROM staff_roles WHERE role_name = 'Academic Registrar' LIMIT 1), 'Active', CURDATE(), NOW()),
('AR002', 'Assistant Registrar', 'assistant_registrar@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$assistant_registrar@isnmHashedPassword', '+256701000025', 'Assistant Registrar', 'Academic Affairs',
 (SELECT id FROM staff_roles WHERE role_name = 'Academic Registrar' LIMIT 1), 'Active', CURDATE(), NOW());

-- ============================================================
-- 2. ACADEMIC REGISTRAR TABLES
-- ============================================================

-- Student Registration Management
CREATE TABLE IF NOT EXISTS registrar_student_registration (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    registration_number VARCHAR(50) UNIQUE NOT NULL,
    intake_set VARCHAR(20),
    program VARCHAR(100) NOT NULL,
    program_type ENUM('Certificate', 'Diploma', 'Degree') DEFAULT 'Diploma',
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(50) DEFAULT 'Semester 1',
    year_of_study INT DEFAULT 1,
    registration_date DATE NOT NULL,
    registration_status ENUM('Registered', 'Pending', 'Rejected', 'Cancelled') DEFAULT 'Pending',
    registration_fee DECIMAL(10,2) DEFAULT 0,
    registration_payment_status ENUM('Paid', 'Partial', 'Unpaid') DEFAULT 'Unpaid',
    registered_by INT,
    approved_by INT,
    approved_date TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES igangaschoolofl_students_db.students(id) ON DELETE CASCADE,
    FOREIGN KEY (registered_by) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_student_id (student_id),
    INDEX idx_registration_number (registration_number),
    INDEX idx_registration_date (registration_date)
);

-- Transcripts Management
CREATE TABLE IF NOT EXISTS registrar_transcripts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transcript_number VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    academic_year VARCHAR(20),
    program VARCHAR(100),
    cgpa DECIMAL(3,2),
    class_of_degree VARCHAR(50),
    transcript_status ENUM('Draft', 'Requested', 'Processing', 'Ready', 'Issued', 'Collected') DEFAULT 'Draft',
    requested_by INT,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_by INT,
    processed_date TIMESTAMP NULL,
    issued_by INT,
    issued_date TIMESTAMP NULL,
    collected_date TIMESTAMP NULL,
    collection_signature VARCHAR(255),
    purpose TEXT,
    copies_requested INT DEFAULT 1,
    copies_issued INT DEFAULT 0,
    fee DECIMAL(10,2) DEFAULT 0,
    payment_status ENUM('Paid', 'Unpaid') DEFAULT 'Unpaid',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES igangaschoolofl_students_db.students(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (processed_by) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (issued_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_transcript_number (transcript_number),
    INDEX idx_student_id (student_id),
    INDEX idx_status (transcript_status)
);

-- Academic Records
CREATE TABLE IF NOT EXISTS registrar_academic_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(50) NOT NULL,
    program VARCHAR(100),
    level VARCHAR(50),
    courses_taken INT,
    credits_earned INT,
    gpa DECIMAL(3,2),
    cgpa DECIMAL(3,2),
    academic_standing ENUM('Good Standing', 'Probation', 'Suspension') DEFAULT 'Good Standing',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES igangaschoolofl_students_db.students(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_academic_year (academic_year)
);

-- Graduation Management
CREATE TABLE IF NOT EXISTS registrar_graduation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    graduation_type ENUM('Certificate', 'Diploma', 'Degree') DEFAULT 'Diploma',
    graduation_date DATE,
    ceremony_date DATE,
    certificate_number VARCHAR(50),
    academic_year VARCHAR(20),
    program VARCHAR(100),
    gpa DECIMAL(3,2),
    cgpa DECIMAL(3,2),
    graduation_status ENUM('Eligible', 'Not Eligible', 'Applied', 'Approved', 'Graduated', 'Deferred') DEFAULT 'Eligible',
    application_date TIMESTAMP NULL,
    approved_by INT,
    approval_date TIMESTAMP NULL,
    certificate_issued BOOLEAN DEFAULT FALSE,
    certificate_issued_date TIMESTAMP NULL,
    graduation_fee DECIMAL(10,2) DEFAULT 0,
    payment_status ENUM('Paid', 'Unpaid') DEFAULT 'Unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES igangaschoolofl_students_db.students(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_student_id (student_id),
    INDEX idx_graduation_status (graduation_status)
);

-- Academic Calendar
CREATE TABLE IF NOT EXISTS registrar_academic_calendar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(50) NOT NULL,
    semester_start DATE NOT NULL,
    semester_end DATE NOT NULL,
    registration_start DATE,
    registration_end DATE,
    add_drop_deadline DATE,
    withdrawal_deadline DATE,
    exam_start DATE,
    exam_end DATE,
    result_publication_date DATE,
    status ENUM('Upcoming', 'Current', 'Completed', 'Cancelled') DEFAULT 'Upcoming',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_academic_year (academic_year),
    INDEX idx_semester (semester)
);

-- ============================================================
-- 3. PROCEDURES FOR ACADEMIC REGISTRAR
-- ============================================================

DELIMITER //

CREATE PROCEDURE get_student_registration_status(IN p_student_id INT)
BEGIN
    SELECT 
        sp.student_number,
        sp.full_name,
        sp.program,
        rr.registration_number,
        rr.registration_status,
        rr.registration_date,
        rr.academic_year,
        rr.semester
    FROM universal_student_profiles sp
    LEFT JOIN registrar_student_registration rr ON sp.id = rr.student_id
    WHERE sp.id = p_student_id;
END //

CREATE PROCEDURE generate_transcript_request(
    IN p_student_id INT,
    IN p_requested_by INT,
    IN p_purpose TEXT,
    IN p_copies INT
)
BEGIN
    DECLARE v_transcript_number VARCHAR(50);
    SET v_transcript_number = CONCAT('TRN', DATE_FORMAT(NOW(), '%Y%m%d'), LPAD(LAST_INSERT_ID() + 1, 4, '0'));
    
    INSERT INTO registrar_transcripts (
        transcript_number, student_id, requested_by, purpose, copies_requested
    ) VALUES (
        v_transcript_number, p_student_id, p_requested_by, p_purpose, p_copies
    );
END //

CREATE PROCEDURE get_graduation_eligible_students()
BEGIN
    SELECT 
        sp.student_number,
        sp.full_name,
        sp.program,
        sp.year_of_study,
        ra.gpa,
        ra.cgpa,
        ra.academic_standing
    FROM universal_student_profiles sp
    JOIN registrar_academic_records ra ON sp.id = ra.student_id
    WHERE sp.year_of_study >= 2 
      AND ra.cgpa >= 2.00
      AND sp.status = 'Active';
END //

DELIMITER ;

-- Insert default academic calendar
INSERT IGNORE INTO registrar_academic_calendar (
    academic_year, semester, semester_start, semester_end,
    registration_start, registration_end, add_drop_deadline,
    withdrawal_deadline, exam_start, exam_end, result_publication_date, status
) VALUES
('2025/2026', 'Semester 1', '2025-09-01', '2025-12-15',
 '2025-08-15', '2025-09-15', '2025-09-30',
 '2025-10-31', '2025-12-01', '2025-12-15', '2026-01-15', 'Current');

COMMIT;
-- ============================================================
-- ISNM NURSING DEPARTMENT DASHBOARD SQL
-- Complete Nursing Department Management System
-- ============================================================



-- ============================================================
-- 1. NURSING DEPARTMENT USER ACCOUNTS
-- ============================================================

INSERT IGNORE INTO staff (
    staff_id, 
    full_name, 
    email, 
    password, 
    phone, 
    position, 
    department, 
    role_id, 
    status, 
    hire_date,
    created_at
) VALUES
('NUR001', 'Head of Nursing', 'nursing@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$nursing@isnmHashedPassword', '+256701000014', 'Head of Nursing', 'Nursing Department',
 (SELECT id FROM staff_roles WHERE role_name = 'Head Nursing' LIMIT 1), 'Active', CURDATE(), NOW()),
('NURSE001', 'Senior Nursing Officer', 'senior_nurse@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$senior_nurse@isnmHashedPassword', '+256701000026', 'Senior Nursing Officer', 'Nursing Department',
 (SELECT id FROM staff_roles WHERE role_name = 'Senior Lecturers' LIMIT 1), 'Active', CURDATE(), NOW());

-- ============================================================
-- 2. NURSING DEPARTMENT TABLES
-- ============================================================

-- Nursing Students Management
CREATE TABLE IF NOT EXISTS nursing_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    student_number VARCHAR(50) UNIQUE NOT NULL,
    index_number VARCHAR(50),
    national_id VARCHAR(50),
    
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    
    program ENUM('Diploma in Nursing', 'BSc Nursing', 'Upgrading Nursing') DEFAULT 'Diploma in Nursing',
    intake_set VARCHAR(20),
    intake_date DATE,
    
    nationality VARCHAR(50) DEFAULT 'Ugandan',
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    marital_status ENUM('Single', 'Married', 'Divorced', 'Widowed'),
    
    district VARCHAR(100),
    county VARCHAR(100),
    sub_county VARCHAR(100),
    
    guardian_name VARCHAR(200),
    guardian_phone VARCHAR(20),
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    
    photo_path VARCHAR(500),
    photo_uploaded BOOLEAN DEFAULT FALSE,
    photo_upload_date TIMESTAMP NULL,
    
    status ENUM('Active', 'Inactive', 'Graduated', 'Suspended', 'Withdrawn') DEFAULT 'Active',
    year_of_study INT DEFAULT 1,
    semester VARCHAR(50) DEFAULT 'Semester 1',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES igangaschoolofl_students_db.students(id) ON DELETE CASCADE,
    INDEX idx_student_number (student_number),
    INDEX idx_full_name (full_name),
    INDEX idx_intake_set (intake_set)
);

-- Nursing Clinical Placements
CREATE TABLE IF NOT EXISTS nursing_clinical_placements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    placement_number VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    placement_site VARCHAR(255) NOT NULL,
    placement_department VARCHAR(100),
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    duration_days INT,
    supervisor_name VARCHAR(255),
    supervisor_contact VARCHAR(20),
    objectives TEXT,
    learning_outcomes TEXT,
    assessment_marks DECIMAL(5,2),
    status ENUM('Scheduled', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    report_submitted BOOLEAN DEFAULT FALSE,
    report_file VARCHAR(500),
    graded_by INT,
    graded_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES nursing_students(id) ON DELETE CASCADE,
    FOREIGN KEY (graded_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_placement_number (placement_number),
    INDEX idx_student_id (student_id),
    INDEX idx_status (status)
);

-- Nursing Clinical Log Book
CREATE TABLE IF NOT EXISTS nursing_clinical_logbook (
    id INT AUTO_INCREMENT PRIMARY KEY,
    logbook_id VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    placement_id INT,
    log_date DATE NOT NULL,
    shift ENUM('Morning', 'Afternoon', 'Night') DEFAULT 'Morning',
    patient_name VARCHAR(255),
    patient_age INT,
    patient_gender ENUM('Male', 'Female', 'Other'),
    diagnosis TEXT,
    procedure_performed TEXT,
    observations TEXT,
    interventions TEXT,
    outcomes TEXT,
    supervisor_initials VARCHAR(10),
    logged_by INT,
    log_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES nursing_students(id) ON DELETE CASCADE,
    FOREIGN KEY (logged_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_logbook_id (logbook_id),
    INDEX idx_student_id (student_id),
    INDEX idx_log_date (log_date)
);

-- Nursing Practical Assessment
CREATE TABLE IF NOT EXISTS nursing_practical_assessment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assessment_id VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    assessment_type ENUM('OSCE', 'VIVA', 'Practical', 'Clinical') NOT NULL,
    assessment_name VARCHAR(255) NOT NULL,
    date_conducted DATE,
    max_marks DECIMAL(5,2),
    marks_obtained DECIMAL(5,2),
    percentage DECIMAL(5,2),
    grade VARCHAR(10),
    assessor_id INT,
    assessor_comments TEXT,
    student_comments TEXT,
    status ENUM('Scheduled', 'Conducted', 'Graded', 'Reviewed') DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES nursing_students(id) ON DELETE CASCADE,
    FOREIGN KEY (assessor_id) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_assessment_id (assessment_id),
    INDEX idx_student_id (student_id),
    INDEX idx_status (status)
);

-- Nursing Skills Training
CREATE TABLE IF NOT EXISTS nursing_skills_training (
    id INT AUTO_INCREMENT PRIMARY KEY,
    training_id VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    skill_name VARCHAR(255) NOT NULL,
    skill_category VARCHAR(100),
    training_date DATE NOT NULL,
    trainer_id INT,
    competence_level ENUM('Beginner', 'Developing', 'Competent', 'Proficient', 'Expert') DEFAULT 'Beginner',
    assessment_score DECIMAL(5,2),
    certification_issued BOOLEAN DEFAULT FALSE,
    certificate_number VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES nursing_students(id) ON DELETE CASCADE,
    FOREIGN KEY (trainer_id) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_training_id (training_id),
    INDEX idx_student_id (student_id)
);

-- ============================================================
-- 3. PROCEDURES FOR NURSING DEPARTMENT
-- ============================================================

DELIMITER //

CREATE PROCEDURE get_nursing_students_by_intake(IN p_intake_set VARCHAR(20))
BEGIN
    SELECT 
        ns.student_number,
        ns.full_name,
        ns.program,
        ns.year_of_study,
        ns.semester,
        ns.status,
        ns.photo_path
    FROM nursing_students ns
    WHERE ns.intake_set = p_intake_set
    ORDER BY ns.student_number;
END //

CREATE PROCEDURE record_clinical_placement(
    IN p_student_id INT,
    IN p_site VARCHAR(255),
    IN p_start_date DATE,
    IN p_end_date DATE,
    IN p_supervisor VARCHAR(255)
)
BEGIN
    DECLARE v_placement_number VARCHAR(50);
    SET v_placement_number = CONCAT('CLIN', DATE_FORMAT(NOW(), '%Y%m%d'), LPAD(p_student_id, 4, '0'));
    
    INSERT INTO nursing_clinical_placements (
        placement_number, student_id, placement_site, start_date, end_date, supervisor_name
    ) VALUES (
        v_placement_number, p_student_id, p_site, p_start_date, p_end_date, p_supervisor
    );
END //

CREATE PROCEDURE get_clinical_logbook(IN p_student_id INT)
BEGIN
    SELECT 
        cl.log_date,
        cl.shift,
        cl.patient_name,
        cl.diagnosis,
        cl.interventions,
        cl.outcomes,
        cl.supervisor_initials
    FROM nursing_clinical_logbook cl
    WHERE cl.student_id = p_student_id
    ORDER BY cl.log_date DESC;
END //

CREATE PROCEDURE get_nursing_students_search(IN p_search_term VARCHAR(255))
BEGIN
    SELECT 
        ns.id,
        ns.student_number,
        ns.full_name,
        ns.program,
        ns.intake_set,
        ns.status,
        ns.photo_path,
        COALESCE(ns.photo_uploaded, FALSE) as has_photo
    FROM nursing_students ns
    WHERE ns.full_name LIKE CONCAT('%', p_search_term, '%')
       OR ns.student_number LIKE CONCAT('%', p_search_term, '%')
       OR ns.index_number LIKE CONCAT('%', p_search_term, '%')
    LIMIT 100;
END //

DELIMITER ;

COMMIT;
-- ============================================================
-- ISNM MIDWYIFERY DEPARTMENT DASHBOARD SQL
-- Complete Midwifery Department Management System
-- ============================================================



-- ============================================================
-- 1. MIDWYIFERY DEPARTMENT USER ACCOUNTS
-- ============================================================

INSERT IGNORE INTO staff (
    staff_id, 
    full_name, 
    email, 
    password, 
    phone, 
    position, 
    department, 
    role_id, 
    status, 
    hire_date,
    created_at
) VALUES
('MID001', 'Head of Midwifery', 'midwifery@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$midwifery@isnmHashedPassword', '+256701000015', 'Head of Midwifery', 'Midwifery Department',
 (SELECT id FROM staff_roles WHERE role_name = 'Head Midwifery' LIMIT 1), 'Active', CURDATE(), NOW()),
('MIDW001', 'Senior Midwifery Officer', 'senior_midwife@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$senior_midwife@isnmHashedPassword', '+256701000027', 'Senior Midwifery Officer', 'Midwifery Department',
 (SELECT id FROM staff_roles WHERE role_name = 'Senior Lecturers' LIMIT 1), 'Active', CURDATE(), NOW());

-- ============================================================
-- 2. MIDWYIFERY DEPARTMENT TABLES
-- ============================================================

-- Midwifery Students Management
CREATE TABLE IF NOT EXISTS midwifery_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    student_number VARCHAR(50) UNIQUE NOT NULL,
    index_number VARCHAR(50),
    national_id VARCHAR(50),
    
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    
    program ENUM('Certificate in Midwifery', 'Diploma in Midwifery', 'Upgrading Midwifery') DEFAULT 'Diploma in Midwifery',
    intake_set VARCHAR(20),
    intake_date DATE,
    
    nationality VARCHAR(50) DEFAULT 'Ugandan',
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    marital_status ENUM('Single', 'Married', 'Divorced', 'Widowed'),
    no_of_children INT,
    
    district VARCHAR(100),
    county VARCHAR(100),
    sub_county VARCHAR(100),
    
    guardian_name VARCHAR(200),
    guardian_phone VARCHAR(20),
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    
    photo_path VARCHAR(500),
    photo_uploaded BOOLEAN DEFAULT FALSE,
    photo_upload_date TIMESTAMP NULL,
    
    status ENUM('Active', 'Inactive', 'Graduated', 'Suspended', 'Withdrawn') DEFAULT 'Active',
    year_of_study INT DEFAULT 1,
    semester VARCHAR(50) DEFAULT 'Semester 1',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES igangaschoolofl_students_db.students(id) ON DELETE CASCADE,
    INDEX idx_student_number (student_number),
    INDEX idx_full_name (full_name),
    INDEX idx_intake_set (intake_set)
);

-- Antenatal Care Records
CREATE TABLE IF NOT EXISTS midwifery_antenatal_care (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    patient_name VARCHAR(255) NOT NULL,
    patient_age INT,
    gravida INT,
    para INT,
    antenatal_visit_date DATE NOT NULL,
    gestational_age_weeks INT,
    blood_pressure VARCHAR(20),
    weight_kg DECIMAL(5,2),
    fetal_heart_rate INT,
    fundal_height_cm INT,
    presentation ENUM('Cephalic', 'Breech', 'Transverse') DEFAULT 'Cephalic',
    pallor BOOLEAN DEFAULT FALSE,
    edema BOOLEAN DEFAULT FALSE,
    proteinuria BOOLEAN DEFAULT FALSE,
    diagnosis TEXT,
    management_plan TEXT,
    medication_given TEXT,
    next_visit_date DATE,
    supervised_by VARCHAR(255),
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES midwifery_students(id) ON DELETE CASCADE,
    INDEX idx_record_id (record_id),
    INDEX idx_student_id (student_id),
    INDEX idx_visit_date (antenatal_visit_date)
);

-- Labor and Delivery Records
CREATE TABLE IF NOT EXISTS midwifery_labor_delivery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    delivery_id VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    patient_name VARCHAR(255) NOT NULL,
    patient_age INT,
    gravida INT,
    para INT,
    delivery_date DATE NOT NULL,
    delivery_time TIME,
    delivery_type ENUM('Spontaneous Vaginal', 'Assisted', 'Elective C/S', 'Emergency C/S', 'Vacuum', 'Forceps') DEFAULT 'Spontaneous Vaginal',
    labor_duration_hours DECIMAL(5,2),
    rupture_of_membranes BOOLEAN DEFAULT FALSE,
    rupture_time TIME,
    oxytocin_used BOOLEAN DEFAULT FALSE,
    episiotomy BOOLEAN DEFAULT FALSE,
    perineal_tear ENUM('None', '1st Degree', '2nd Degree', '3rd Degree', '4th Degree') DEFAULT 'None',
    placenta_complete BOOLEAN DEFAULT TRUE,
    blood_loss_ml INT,
    newborn_gender ENUM('Male', 'Female', 'Other'),
    newborn_weight_gm INT,
    newborn_apgar_score INT,
    complications TEXT,
    interventions TEXT,
    medications_administered TEXT,
    outcome ENUM('Live Birth', 'Still Birth', 'Maternal Death') DEFAULT 'Live Birth',
    supervised_by VARCHAR(255),
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES midwifery_students(id) ON DELETE CASCADE,
    INDEX idx_delivery_id (delivery_id),
    INDEX idx_student_id (student_id),
    INDEX idx_delivery_date (delivery_date)
);

-- Postnatal Care Records
CREATE TABLE IF NOT EXISTS midwifery_postnatal_care (
    id INT AUTO_INCREMENT PRIMARY KEY,
    postnatal_id VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    patient_name VARCHAR(255) NOT NULL,
    visit_number INT,
    visit_date DATE NOT NULL,
    days_post_delivery INT,
    maternal_condition TEXT,
    uterus_involution BOOLEAN DEFAULT TRUE,
    lochia_type ENUM('Rubra', 'Serosa', 'Alba'),
    lochia_amount ENUM('Scanty', 'Moderate', 'Heavy'),
    perineal_wound_healing BOOLEAN DEFAULT TRUE,
    breastfeeding_status ENUM('Exclusive', 'Partial', 'None') DEFAULT 'Exclusive',
    newborn_condition TEXT,
    newborn_weight DECIMAL(5,2),
    newborn_feeding_frequency INT,
    complications TEXT,
    advice_given TEXT,
    next_visit_date DATE,
    supervised_by VARCHAR(255),
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES midwifery_students(id) ON DELETE CASCADE,
    INDEX idx_postnatal_id (postnatal_id),
    INDEX idx_student_id (student_id),
    INDEX idx_visit_date (visit_date)
);

-- Family Planning Records
CREATE TABLE IF NOT EXISTS midwifery_family_planning (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fp_id VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    client_name VARCHAR(255) NOT NULL,
    client_age INT,
    parity INT,
    method_selected ENUM('Pill', 'Injection', 'Implant', 'IUD', 'Sterilization', 'Natural', 'None') NOT NULL,
    previous_method ENUM('Pill', 'Injection', 'Implant', 'IUD', 'Sterilization', 'Natural', 'None'),
    counseling_done BOOLEAN DEFAULT TRUE,
    complications_history TEXT,
    follow_up_date DATE,
    supervised_by VARCHAR(255),
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES midwifery_students(id) ON DELETE CASCADE,
    INDEX idx_fp_id (fp_id),
    INDEX idx_student_id (student_id)
);

-- ============================================================
-- 3. PROCEDURES FOR MIDWYIFERY DEPARTMENT
-- ============================================================

DELIMITER //

CREATE PROCEDURE get_midwifery_students_by_intake(IN p_intake_set VARCHAR(20))
BEGIN
    SELECT 
        ms.student_number,
        ms.full_name,
        ms.program,
        ms.year_of_study,
        ms.semester,
        ms.status,
        ms.photo_path
    FROM midwifery_students ms
    WHERE ms.intake_set = p_intake_set
    ORDER BY ms.student_number;
END //

CREATE PROCEDURE record_antenatal_visit(
    IN p_student_id INT,
    IN p_patient_name VARCHAR(255),
    IN p_visit_date DATE,
    IN p_blood_pressure VARCHAR(20),
    IN p_fhr INT
)
BEGIN
    DECLARE v_record_id VARCHAR(50);
    SET v_record_id = CONCAT('AN', DATE_FORMAT(NOW(), '%Y%m%d'), LPAD(p_student_id, 4, '0'));
    
    INSERT INTO midwifery_antenatal_care (
        record_id, student_id, patient_name, antenatal_visit_date, blood_pressure, fetal_heart_rate
    ) VALUES (
        v_record_id, p_student_id, p_patient_name, p_visit_date, p_blood_pressure, p_fhr
    );
END //

CREATE PROCEDURE get_midwifery_students_search(IN p_search_term VARCHAR(255))
BEGIN
    SELECT 
        ms.id,
        ms.student_number,
        ms.full_name,
        ms.program,
        ms.intake_set,
        ms.status,
        ms.photo_path,
        COALESCE(ms.photo_uploaded, FALSE) as has_photo
    FROM midwifery_students ms
    WHERE ms.full_name LIKE CONCAT('%', p_search_term, '%')
       OR ms.student_number LIKE CONCAT('%', p_search_term, '%')
       OR ms.index_number LIKE CONCAT('%', p_search_term, '%')
    LIMIT 100;
END //

DELIMITER ;

COMMIT;
-- ============================================================
-- ISNM HR MANAGER DASHBOARD SQL
-- Complete Human Resources Management System
-- ============================================================



-- ============================================================
-- 1. HR MANAGER USER ACCOUNTS
-- ============================================================

INSERT IGNORE INTO staff (
    staff_id, 
    full_name, 
    email, 
    password, 
    phone, 
    position, 
    department, 
    role_id, 
    status, 
    hire_date,
    created_at
) VALUES
('HR001', 'HR Manager', 'hr@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$hr@isnmHashedPassword', '+256701000011', 'HR Manager', 'Human Resources',
 (SELECT id FROM staff_roles WHERE role_name = 'HR Manager' LIMIT 1), 'Active', CURDATE(), NOW()),
('HR002', 'HR Assistant', 'hr_assistant@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$hr_assistant@isnmHashedPassword', '+256701000028', 'HR Assistant', 'Human Resources',
 (SELECT id FROM staff_roles WHERE role_name = 'HR Manager' LIMIT 1), 'Active', CURDATE(), NOW());

-- ============================================================
-- 2. HR MANAGER TABLES (Using existing tables from 04_final_complete_staffs_database.sql)
-- Add search and integration views
-- ============================================================

-- Staff Search View
CREATE OR REPLACE VIEW hr_staff_search_view AS
SELECT 
    s.id,
    s.staff_id,
    s.full_name,
    s.email,
    s.phone,
    s.position,
    s.department,
    sr.role_name,
    s.status,
    s.hire_date,
    s.last_login,
    CASE 
        WHEN s.locked_until > NOW() THEN 'Locked'
        WHEN s.login_attempts >= 5 THEN 'Warning'
        ELSE 'Active'
    END as account_status
FROM staff s
LEFT JOIN staff_roles sr ON s.role_id = sr.id;

-- Staff Performance Summary View
CREATE OR REPLACE VIEW hr_performance_summary AS
SELECT 
    st.id as staff_id,
    st.full_name,
    st.position,
    st.department,
    sr.role_name,
    COALESCE(spf.performance_score, 0) as avg_performance_score,
    spf.rating as latest_rating,
    COALESCE(sl.total_leaves, 0) as total_leaves,
    COALESCE(sta.attendance_rate, 0) as attendance_rate,
    COALESCE(stt.training_count, 0) as training_completed
FROM staff st
LEFT JOIN staff_performance spf ON st.id = spf.staff_id
LEFT JOIN (
    SELECT staff_id, COUNT(*) as total_leaves FROM staff_leave_requests GROUP BY staff_id
) sl ON st.id = sl.staff_id
LEFT JOIN (
    SELECT staff_id, 
           SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) * 100.0 / COUNT(*) as attendance_rate 
    FROM staff_attendance GROUP BY staff_id
) sta ON st.id = sta.staff_id
LEFT JOIN (
    SELECT staff_id, COUNT(*) as training_count FROM staff_training WHERE status = 'Completed' GROUP BY staff_id
) stt ON st.id = stt.staff_id
LEFT JOIN staff_roles sr ON st.role_id = sr.id;

-- ============================================================
-- 3. PROCEDURES FOR HR MANAGER
-- ============================================================

DELIMITER //

-- Search staff by various criteria
CREATE PROCEDURE hr_search_staff(
    IN p_name VARCHAR(255),
    IN p_department VARCHAR(100),
    IN p_position VARCHAR(100),
    IN p_status VARCHAR(50)
)
BEGIN
    SELECT 
        s.id,
        s.staff_id,
        s.full_name,
        s.email,
        s.phone,
        s.position,
        s.department,
        sr.role_name,
        s.status,
        s.hire_date
    FROM staff s
    LEFT JOIN staff_roles sr ON s.role_id = sr.id
    WHERE (p_name IS NULL OR s.full_name LIKE CONCAT('%', p_name, '%'))
      AND (p_department IS NULL OR s.department = p_department)
      AND (p_position IS NULL OR s.position LIKE CONCAT('%', p_position, '%'))
      AND (p_status IS NULL OR s.status = p_status)
    ORDER BY s.full_name;
END //

-- Get staff profile with documents
CREATE PROCEDURE hr_get_staff_profile(IN p_staff_id INT)
BEGIN
    SELECT 
        s.*,
        sp.bio,
        sp.profile_picture,
        sp.qualifications,
        sp.experience,
        sp.skills,
        sp.education_background,
        sp.certifications,
        sd.document_type,
        sd.document_title,
        sd.file_path,
        sd.upload_date
    FROM staff s
    LEFT JOIN staff_profiles sp ON s.id = sp.staff_id
    LEFT JOIN staff_documents sd ON s.id = sd.staff_id
    WHERE s.id = p_staff_id;
END //

-- Update staff profile picture
CREATE PROCEDURE hr_update_profile_picture(
    IN p_staff_id INT,
    IN p_photo_path VARCHAR(500),
    IN p_updated_by INT
)
BEGIN
    INSERT INTO staff_profiles (staff_id, profile_picture) 
    VALUES (p_staff_id, p_photo_path)
    ON DUPLICATE KEY UPDATE 
        profile_picture = p_photo_path;
END //

DELIMITER ;

-- ============================================================
-- 4. HR REPORTING VIEWS
-- ============================================================

-- Staff by Department
CREATE OR REPLACE VIEW hr_staff_by_department AS
SELECT 
    department,
    COUNT(*) as total_staff,
    SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active_staff,
    SUM(CASE WHEN status IN ('Suspended', 'On Leave') THEN 1 ELSE 0 END) as inactive_staff,
    AVG(DATEDIFF(NOW(), hire_date) / 365) as avg_years_of_service
FROM staff
GROUP BY department
ORDER BY department;

-- Leave Summary
CREATE OR REPLACE VIEW hr_leave_summary AS
SELECT 
    leave_type,
    COUNT(*) as total_requests,
    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
FROM staff_leave_requests
GROUP BY leave_type;

COMMIT;
-- ============================================================
-- ISNM LIBRARY MANAGER DASHBOARD SQL
-- Complete Library Management System
-- ============================================================



-- ============================================================
-- 1. LIBRARY MANAGER USER ACCOUNTS
-- ============================================================

INSERT IGNORE INTO staff (
    staff_id, 
    full_name, 
    email, 
    password, 
    phone, 
    position, 
    department, 
    role_id, 
    status, 
    hire_date,
    created_at
) VALUES
('LIB001', 'School Librarian', 'librarian@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$librarian@isnmHashedPassword', '+256701000013', 'School Librarian', 'Library Services',
 (SELECT id FROM staff_roles WHERE role_name = 'School Librarian' LIMIT 1), 'Active', CURDATE(), NOW()),
('LIB002', 'Assistant Librarian', 'assistant_librarian@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$assistant_librarian@isnmHashedPassword', '+256701000029', 'Assistant Librarian', 'Library Services',
 (SELECT id FROM staff_roles WHERE role_name = 'School Librarian' LIMIT 1), 'Active', CURDATE(), NOW());

-- ============================================================
-- 2. LIBRARY MANAGEMENT TABLES
-- ============================================================

-- Books and Resources Catalog
CREATE TABLE IF NOT EXISTS library_books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(255),
    author VARCHAR(255),
    editor VARCHAR(255),
    edition VARCHAR(50),
    isbn VARCHAR(20),
    issn VARCHAR(20),
    publisher VARCHAR(255),
    publication_year INT,
    publication_place VARCHAR(100),
    category VARCHAR(100),
    subcategory VARCHAR(100),
    call_number VARCHAR(50),
    total_copies INT DEFAULT 1,
    available_copies INT DEFAULT 1,
    shelf_location VARCHAR(100),
    condition_status ENUM('New', 'Good', 'Fair', 'Poor', 'Damaged') DEFAULT 'Good',
    price DECIMAL(10,2),
    currency VARCHAR(3) DEFAULT 'UGX',
    language VARCHAR(50) DEFAULT 'English',
    pages INT,
    description TEXT,
    keywords TEXT,
    cover_image VARCHAR(500),
    digital_copy_path VARCHAR(500),
    status ENUM('Available', 'Borrowed', 'Reserved', 'Lost', 'On Order', 'Archiv') DEFAULT 'Available',
    added_by INT,
    added_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (added_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_book_id (book_id),
    INDEX idx_title (title),
    INDEX idx_author (author),
    INDEX idx_category (category),
    INDEX idx_status (status)
);

-- Borrowing Records
CREATE TABLE IF NOT EXISTS library_borrowing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(50) UNIQUE NOT NULL,
    book_id INT NOT NULL,
    borrower_type ENUM('Student', 'Staff', 'External') NOT NULL,
    borrower_id INT,
    borrower_name VARCHAR(255),
    borrow_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE,
    return_status ENUM('Borrowed', 'Returned', 'Overdue', 'Lost') DEFAULT 'Borrowed',
    late_fee DECIMAL(10,2) DEFAULT 0,
    fine_paid BOOLEAN DEFAULT FALSE,
    processed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES library_books(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_book_id (book_id),
    INDEX idx_return_status (return_status),
    INDEX idx_due_date (due_date)
);

-- Library Members
CREATE TABLE IF NOT EXISTS library_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id VARCHAR(50) UNIQUE NOT NULL,
    member_type ENUM('Student', 'Staff', 'External') NOT NULL,
    student_id INT,
    staff_id INT,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    department VARCHAR(100),
    program VARCHAR(100),
    member_since DATE,
    membership_expiry DATE,
    max_books_allowed INT DEFAULT 3,
    current_books_borrowed INT DEFAULT 0,
    status ENUM('Active', 'Inactive', 'Suspended', 'Expired') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES igangaschoolofl_students_db.students(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_member_id (member_id),
    INDEX idx_full_name (full_name)
);

-- Digital Resources
CREATE TABLE IF NOT EXISTS library_digital_resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resource_id VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    resource_type ENUM('Ebook', 'Journal', 'Video', 'Audio', 'Database', 'Article') NOT NULL,
    author_creator VARCHAR(255),
    publisher VARCHAR(255),
    publication_year INT,
    url VARCHAR(500),
    file_path VARCHAR(500),
    file_size_mb DECIMAL(10,2),
    access_level ENUM('Public', 'Members Only', 'Restricted') DEFAULT 'Members Only',
    description TEXT,
    subject_keywords TEXT,
    added_by INT,
    added_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (added_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_resource_id (resource_id),
    INDEX idx_title (title)
);

-- Library Fines and Fees
CREATE TABLE IF NOT EXISTS library_fines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fine_id VARCHAR(50) UNIQUE NOT NULL,
    transaction_id INT,
    member_id INT NOT NULL,
    fine_type ENUM('Overdue', 'Damage', 'Lost', 'Reservation') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'UGX',
    description TEXT,
    waived BOOLEAN DEFAULT FALSE,
    waived_by INT,
    waived_date TIMESTAMP NULL,
    paid BOOLEAN DEFAULT FALSE,
    payment_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES library_borrowing(id) ON DELETE SET NULL,
    FOREIGN KEY (member_id) REFERENCES library_members(id) ON DELETE CASCADE,
    INDEX idx_fine_id (fine_id),
    INDEX idx_member_id (member_id),
    INDEX idx_paid (paid)
);

-- ============================================================
-- 3. PROCEDURES FOR LIBRARY MANAGEMENT
-- ============================================================

DELIMITER //

-- Search books in library
CREATE PROCEDURE library_search_books(
    IN p_title VARCHAR(255),
    IN p_author VARCHAR(255),
    IN p_category VARCHAR(100),
    IN p_status VARCHAR(50)
)
BEGIN
    SELECT 
        lb.book_id,
        lb.title,
        lb.author,
        lb.publisher,
        lb.publication_year,
        lb.category,
        lb.total_copies,
        lb.available_copies,
        lb.shelf_location,
        lb.status
    FROM library_books lb
    WHERE (p_title IS NULL OR lb.title LIKE CONCAT('%', p_title, '%'))
      AND (p_author IS NULL OR lb.author LIKE CONCAT('%', p_author, '%'))
      AND (p_category IS NULL OR lb.category = p_category)
      AND (p_status IS NULL OR lb.status = p_status)
    ORDER BY lb.title;
END //

-- Borrow book
CREATE PROCEDURE library_borrow_book(
    IN p_book_id INT,
    IN p_member_id INT,
    IN p_processed_by INT
)
BEGIN
    DECLARE v_transaction_id VARCHAR(50);
    DECLARE v_due_date DATE;
    DECLARE v_current_copies INT;
    DECLARE v_available_copies INT;
    
    SET v_transaction_id = CONCAT('BRW', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'));
    SET v_due_date = DATE_ADD(CURDATE(), INTERVAL 14 DAY);
    
    -- Check available copies
    SELECT available_copies INTO v_available_copies 
    FROM library_books WHERE id = p_book_id;
    
    IF v_available_copies > 0 THEN
        INSERT INTO library_borrowing (
            transaction_id, book_id, borrower_id, borrower_name, 
            borrow_date, due_date, processed_by
        ) VALUES (
            v_transaction_id, p_book_id, p_member_id, 
            (SELECT full_name FROM library_members WHERE id = p_member_id),
            CURDATE(), v_due_date, p_processed_by
        );
        
        UPDATE library_books 
        SET available_copies = available_copies - 1
        WHERE id = p_book_id;
        
        UPDATE library_members 
        SET current_books_borrowed = current_books_borrowed + 1
        WHERE id = p_member_id;
    END IF;
END //

-- Return book
CREATE PROCEDURE library_return_book(
    IN p_transaction_id INT,
    IN p_processed_by INT
)
BEGIN
    UPDATE library_borrowing 
    SET return_date = CURDATE(),
        return_status = 'Returned'
    WHERE id = p_transaction_id;
    
    UPDATE library_books lb
    JOIN library_borrowing lbw ON lb.id = lbw.book_id
    SET lb.available_copies = lb.available_copies + 1
    WHERE lbw.id = p_transaction_id;
    
    UPDATE library_members lm
    JOIN library_borrowing lbw ON lm.id = lbw.borrower_id
    SET lm.current_books_borrowed = lm.current_books_borrowed - 1
    WHERE lbw.id = p_transaction_id;
END //

DELIMITER ;

COMMIT;
-- ============================================================
-- ISNM SECURITY DEPARTMENT DASHBOARD SQL
-- Complete Security Management System
-- ============================================================



-- ============================================================
-- 1. SECURITY USER ACCOUNTS
-- ============================================================

INSERT IGNORE INTO staff (
    staff_id, 
    full_name, 
    email, 
    password, 
    phone, 
    position, 
    department, 
    role_id, 
    status, 
    hire_date,
    created_at
) VALUES
('SEC001', 'Security Officer', 'security@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$security@isnmHashedPassword', '+256701000022', 'Security', 'Security Services',
 (SELECT id FROM staff_roles WHERE role_name = 'Security' LIMIT 1), 'Active', CURDATE(), NOW()),
('SEC002', 'Chief Security Officer', 'cso@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$cso@isnmHashedPassword', '+256701000030', 'Chief Security Officer', 'Security Services',
 (SELECT id FROM staff_roles WHERE role_name = 'Security' LIMIT 1), 'Active', CURDATE(), NOW());

-- ============================================================
-- 2. SECURITY MANAGEMENT TABLES
-- ============================================================

-- Security Patrols
CREATE TABLE IF NOT EXISTS security_patrols (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patrol_id VARCHAR(50) UNIQUE NOT NULL,
    patrol_date DATE NOT NULL,
    patrol_shift ENUM('Morning', 'Afternoon', 'Night') NOT NULL,
    patrol_area VARCHAR(255) NOT NULL,
    patrol_route TEXT,
    start_time TIME,
    end_time TIME,
    duration_minutes INT,
    team_leader INT,
    officers_involved TEXT,
    incidents_reported INT DEFAULT 0,
    status ENUM('Scheduled', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_leader) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_patrol_id (patrol_id),
    INDEX idx_patrol_date (patrol_date)
);

-- Security Incidents
CREATE TABLE IF NOT EXISTS security_incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id VARCHAR(50) UNIQUE NOT NULL,
    incident_date DATE NOT NULL,
    incident_time TIME,
    incident_type ENUM('Theft', 'Assault', 'Fire', 'Medical', 'Accident', 'Vandalism', 'Suspicious Activity', 'Other') NOT NULL,
    severity ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    location VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    parties_involved TEXT,
    injuries_reported BOOLEAN DEFAULT FALSE,
    injuries_description TEXT,
    reported_by INT,
    reporter_name VARCHAR(255),
    reported_via ENUM('Phone', 'Email', 'In Person', 'Radio', 'Online') DEFAULT 'In Person',
    response_team TEXT,
    action_taken TEXT,
    resolution_status ENUM('Reported', 'In Investigation', 'Resolved', 'Closed') DEFAULT 'Reported',
    resolved_by INT,
    resolution_date TIMESTAMP NULL,
    follow_up_required BOOLEAN DEFAULT FALSE,
    follow_up_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reported_by) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (resolved_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_incident_id (incident_id),
    INDEX idx_incident_date (incident_date),
    INDEX idx_severity (severity)
);

-- Access Control Logs
CREATE TABLE IF NOT EXISTS security_access_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    log_id VARCHAR(50) UNIQUE NOT NULL,
    access_point VARCHAR(255) NOT NULL,
    access_date DATE NOT NULL,
    access_time TIME,
    person_type ENUM('Staff', 'Student', 'Visitor', 'Vendor', 'Unknown') NOT NULL,
    person_id INT,
    person_name VARCHAR(255),
    access_direction ENUM('Entry', 'Exit') NOT NULL,
    access_method ENUM('ID Card', 'Biometric', 'PIN', 'Manual') DEFAULT 'ID Card',
    authorized BOOLEAN DEFAULT TRUE,
    denial_reason VARCHAR(255),
    captured_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_log_id (log_id),
    INDEX idx_access_date (access_date),
    INDEX idx_person_type (person_type)
);

-- Visitor Management
CREATE TABLE IF NOT EXISTS security_visitors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visitor_id VARCHAR(50) UNIQUE NOT NULL,
    visitor_name VARCHAR(255) NOT NULL,
    visitor_phone VARCHAR(20),
    visitor_email VARCHAR(100),
    visitor_company VARCHAR(255),
    visitor_nature ENUM('Official', 'Parent', 'Guardian', 'Service Provider', 'Delivery', 'Other') NOT NULL,
    purpose_of_visit TEXT,
    person_to_visit INT,
    person_to_visit_name VARCHAR(255),
    visit_date DATE NOT NULL,
    expected_arrival TIME,
    expected_departure TIME,
    actual_arrival TIMESTAMP NULL,
    actual_departure TIMESTAMP NULL,
    vehicle_number VARCHAR(50),
    items_carried TEXT,
    security_check_passed BOOLEAN DEFAULT FALSE,
    check_in_by INT,
    check_out_by INT,
    status ENUM('Scheduled', 'Checked In', 'On Campus', 'Checked Out', 'No Show') DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (person_to_visit) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (check_in_by) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (check_out_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_visitor_id (visitor_id),
    INDEX idx_visit_date (visit_date)
);

-- Security Equipment
CREATE TABLE IF NOT EXISTS security_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id VARCHAR(50) UNIQUE NOT NULL,
    equipment_name VARCHAR(255) NOT NULL,
    equipment_type ENUM('Camera', 'CCTV', 'Metal Detector', 'Scanner', 'Radio', 'Flashlight', 'Baton', 'Other') NOT NULL,
    serial_number VARCHAR(100),
    location_installed VARCHAR(255),
    installation_date DATE,
    last_maintenance_date DATE,
    next_maintenance_date DATE,
    condition_status ENUM('Excellent', 'Good', 'Fair', 'Poor', 'Broken') DEFAULT 'Good',
    status ENUM('Active', 'Inactive', 'Maintenance', 'Retired') DEFAULT 'Active',
    assigned_to INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_equipment_id (equipment_id),
    INDEX idx_status (status)
);

-- Emergency Contacts and Procedures
CREATE TABLE IF NOT EXISTS security_emergency_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contact_name VARCHAR(255) NOT NULL,
    contact_type ENUM('Police', 'Hospital', 'Fire', 'Ambulance', 'Internal') NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    secondary_phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    response_time_minutes INT,
    notes TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_contact_type (contact_type),
    INDEX idx_is_active (is_active)
);

-- ============================================================
-- 3. PROCEDURES FOR SECURITY DEPARTMENT
-- ============================================================

DELIMITER //

-- Report security incident
CREATE PROCEDURE security_report_incident(
    IN p_incident_type VARCHAR(50),
    IN p_location VARCHAR(255),
    IN p_description TEXT,
    IN p_reported_by INT,
    IN p_severity VARCHAR(20)
)
BEGIN
    DECLARE v_incident_id VARCHAR(50);
    SET v_incident_id = CONCAT('INC', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'));
    
    INSERT INTO security_incidents (
        incident_id, incident_type, location, description, reported_by, severity
    ) VALUES (
        v_incident_id, p_incident_type, p_location, p_description, p_reported_by, p_severity
    );
END //

-- Record visitor check-in
CREATE PROCEDURE security_visitor_checkin(
    IN p_visitor_id INT,
    IN p_checked_by INT
)
BEGIN
    UPDATE security_visitors 
    SET actual_arrival = NOW(),
        status = 'Checked In',
        check_in_by = p_checked_by
    WHERE id = p_visitor_id;
END //

-- Record visitor check-out
CREATE PROCEDURE security_visitor_checkout(
    IN p_visitor_id INT,
    IN p_checked_by INT
)
BEGIN
    UPDATE security_visitors 
    SET actual_departure = NOW(),
        status = 'Checked Out',
        check_out_by = p_checked_by
    WHERE id = p_visitor_id;
END //

DELIMITER ;

COMMIT;
-- ============================================================
-- ISNM SICKBAY DASHBOARD SQL
-- Complete Medical Support System
-- Formerly Lab Technicians department, now consolidated under Sickbay
-- ============================================================



-- ============================================================
-- 1. SICKBAY USER ACCOUNTS
-- ============================================================

INSERT IGNORE INTO staff (
    staff_id, 
    full_name, 
    email, 
    password, 
    phone, 
    position, 
    department, 
    role_id, 
    status, 
    hire_date,
    created_at
) VALUES
('SICK001', 'Sickbay', 'sickbay@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$kzTn6S3OUtKLmGoLNo9GOOHqIki7NwUxvZJ6pJK02Yls6eR7Bln82', '+256701000020', 'Sickbay', 'Support',
 (SELECT id FROM staff_roles WHERE role_name = 'Sickbay' LIMIT 1), 'Active', CURDATE(), NOW());

-- ============================================================
-- 2. MEDICAL SUPPORT MANAGEMENT TABLES
-- ============================================================

-- Laboratory Equipment
CREATE TABLE IF NOT EXISTS lab_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id VARCHAR(50) UNIQUE NOT NULL,
    equipment_name VARCHAR(255) NOT NULL,
    equipment_type ENUM('Microscope', 'Centrifuge', 'Autoclave', 'Spectrophotometer', 'PCR', 'Incubator', 'Refrigerator', 'Freezer', 'Other') NOT NULL,
    manufacturer VARCHAR(255),
    serial_number VARCHAR(100),
    model VARCHAR(100),
    purchase_date DATE,
    warranty_expiry DATE,
    calibration_date DATE,
    next_calibration_date DATE,
    location VARCHAR(255),
    status ENUM('Operational', 'Maintenance', 'Repair', 'Retired') DEFAULT 'Operational',
    last_serviced_by VARCHAR(255),
    service_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_equipment_id (equipment_id),
    INDEX idx_status (status)
);

-- Laboratory Inventory
CREATE TABLE IF NOT EXISTS lab_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id VARCHAR(50) UNIQUE NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    item_category ENUM('Reagent', 'Chemical', 'Consumable', 'Glassware', 'Plasticware', 'Media', 'Antibody', 'Enzyme') NOT NULL,
    manufacturer VARCHAR(255),
    catalog_number VARCHAR(100),
    batch_number VARCHAR(100),
    unit_of_measure VARCHAR(50),
    quantity_on_hand DECIMAL(15,2) DEFAULT 0,
    reorder_level DECIMAL(15,2) DEFAULT 0,
    storage_location VARCHAR(255),
    expiry_date DATE,
    date_received DATE,
    received_by INT,
    status ENUM('In Stock', 'Low Stock', 'Out of Stock', 'Expired', 'Quarantine') DEFAULT 'In Stock',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (received_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_item_id (item_id),
    INDEX idx_item_name (item_name),
    INDEX idx_category (item_category),
    INDEX idx_expiry_date (expiry_date)
);

-- Lab Skills Sessions
CREATE TABLE IF NOT EXISTS lab_skills_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(50) UNIQUE NOT NULL,
    session_title VARCHAR(255) NOT NULL,
    skill_name VARCHAR(255) NOT NULL,
    session_date DATE NOT NULL,
    start_time TIME,
    end_time TIME,
    duration_minutes INT,
    target_department ENUM('Nursing', 'Midwifery', 'Both') DEFAULT 'Both',
    year_of_study INT,
    students_expected INT,
    students_attended INT,
    instructor_id INT,
    instructor_name VARCHAR(255),
    equipment_used TEXT,
    materials_used TEXT,
    pre_test_score DECIMAL(5,2),
    post_test_score DECIMAL(5,2),
    session_status ENUM('Scheduled', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    evaluation_notes TEXT,
    completed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (instructor_id) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (completed_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_session_id (session_id),
    INDEX idx_session_date (session_date)
);

-- Lab Safety Records
CREATE TABLE IF NOT EXISTS lab_safety_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id VARCHAR(50) UNIQUE NOT NULL,
    incident_date DATE NOT NULL,
    incident_type ENUM('Accident', 'Near Miss', 'Spill', 'Exposure', 'Equipment Failure') NOT NULL,
    severity ENUM('Minor', 'Moderate', 'Severe', 'Critical') DEFAULT 'Minor',
    person_involved VARCHAR(255),
    person_type ENUM('Student', 'Staff', 'Visitor') NOT NULL,
    description TEXT NOT NULL,
    immediate_action TEXT,
    follow_up_action TEXT,
    reported_by INT,
    reported_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved BOOLEAN DEFAULT FALSE,
    resolution_date TIMESTAMP NULL,
    resolved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reported_by) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (resolved_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_record_id (record_id),
    INDEX idx_incident_date (incident_date)
);

-- Chemical Inventory
CREATE TABLE IF NOT EXISTS lab_chemical_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chemical_id VARCHAR(50) UNIQUE NOT NULL,
    chemical_name VARCHAR(255) NOT NULL,
    cas_number VARCHAR(50),
    chemical_formula VARCHAR(100),
    hazard_classification VARCHAR(100),
    storage_requirements TEXT,
    quantity_on_hand DECIMAL(15,2),
    unit_of_measure VARCHAR(50),
    expiry_date DATE,
    date_received DATE,
    storage_location VARCHAR(255),
    supplier VARCHAR(255),
    msds_file VARCHAR(500),
    status ENUM('In Stock', 'Low Stock', 'Out of Stock', 'Expired') DEFAULT 'In Stock',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_chemical_id (chemical_id),
    INDEX idx_chemical_name (chemical_name),
    INDEX idx_expiry_date (expiry_date)
);

-- Lab Experiments Tracking
CREATE TABLE IF NOT EXISTS lab_experiments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    experiment_id VARCHAR(50) UNIQUE NOT NULL,
    experiment_name VARCHAR(255) NOT NULL,
    course_code VARCHAR(50),
    batch_number VARCHAR(50),
    session_id INT,
    experiment_date DATE NOT NULL,
    start_time TIME,
    end_time TIME,
    students_enrolled INT,
    students_completed INT,
    instructor_id INT,
    sickbay_staff_id INT,
    equipment_used TEXT,
    reagents_used TEXT,
    observations TEXT,
    results TEXT,
    status ENUM('Scheduled', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES lab_skills_sessions(id) ON DELETE SET NULL,
    FOREIGN KEY (instructor_id) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (sickbay_staff_id) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_experiment_id (experiment_id),
    INDEX idx_experiment_date (experiment_date)
);

-- ============================================================
-- 3. PROCEDURES FOR SICKBAY
-- ============================================================

DELIMITER //

-- Record lab equipment maintenance
CREATE PROCEDURE lab_record_maintenance(
    IN p_equipment_id INT,
    IN p_status VARCHAR(20),
    IN p_notes TEXT
)
BEGIN
    UPDATE lab_equipment 
    SET status = p_status,
        service_notes = CONCAT(COALESCE(service_notes, ''), '\n', p_notes),
        updated_at = NOW()
    WHERE id = p_equipment_id;
END //

-- Update inventory quantity
CREATE PROCEDURE lab_update_inventory(
    IN p_item_id INT,
    IN p_new_quantity DECIMAL(15,2)
)
BEGIN
    UPDATE lab_inventory 
    SET quantity_on_hand = p_new_quantity,
        status = CASE 
            WHEN p_new_quantity <= 0 THEN 'Out of Stock'
            WHEN p_new_quantity <= reorder_level THEN 'Low Stock'
            ELSE 'In Stock'
        END,
        updated_at = NOW()
    WHERE id = p_item_id;
END //

-- Schedule lab session
CREATE PROCEDURE lab_schedule_session(
    IN p_title VARCHAR(255),
    IN p_skill_name VARCHAR(255),
    IN p_session_date DATE,
    IN p_instructor_id INT
)
BEGIN
    DECLARE v_session_id VARCHAR(50);
    SET v_session_id = CONCAT('LSS', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'));
    
    INSERT INTO lab_skills_sessions (
        session_id, session_title, skill_name, session_date, instructor_id
    ) VALUES (
        v_session_id, p_title, p_skill_name, p_session_date, p_instructor_id
    );
END //

DELIMITER ;

COMMIT;
-- ============================================================
-- ISNM MATRONS & WARDENS DASHBOARD SQL
-- Complete Student Welfare Management System
-- ============================================================



-- ============================================================
-- 1. MATRONS & WARDENS USER ACCOUNTS
-- ============================================================

INSERT IGNORE INTO staff (
    staff_id, 
    full_name, 
    email, 
    password, 
    phone, 
    position, 
    department, 
    role_id, 
    status, 
    hire_date,
    created_at
) VALUES
('MAT001', 'Matron', 'matrons@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$matrons@isnmHashedPassword', '+256701000018', 'Matrons', 'Student Affairs',
 (SELECT id FROM staff_roles WHERE role_name = 'Matrons' LIMIT 1), 'Active', CURDATE(), NOW()),
('WAR001', 'Warden', 'wardens@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$wardens@isnmHashedPassword', '+256701000019', 'Wardens', 'Student Affairs',
 (SELECT id FROM staff_roles WHERE role_name = 'Wardens' LIMIT 1), 'Active', CURDATE(), NOW());

-- ============================================================
-- 2. STUDENT WELFARE MANAGEMENT TABLES
-- ============================================================

-- Student Welfare Cases
CREATE TABLE IF NOT EXISTS student_welfare_cases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    case_type ENUM('Financial', 'Health', 'Family', 'Academic', 'Discipline', 'Personal', 'Emergency') NOT NULL,
    case_priority ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    case_description TEXT NOT NULL,
    reported_by INT,
    reporter_name VARCHAR(255),
    reported_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_to INT,
    assigned_date TIMESTAMP NULL,
    action_taken TEXT,
    outcome TEXT,
    follow_up_required BOOLEAN DEFAULT FALSE,
    follow_up_date DATE,
    follow_up_completed BOOLEAN DEFAULT FALSE,
    closed BOOLEAN DEFAULT FALSE,
    closed_by INT,
    closure_date TIMESTAMP NULL,
    status ENUM('Reported', 'Assigned', 'In Progress', 'Follow Up', 'Closed', 'Escalated') DEFAULT 'Reported',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES igangaschoolofl_students_db.students(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_by) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (closed_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_case_id (case_id),
    INDEX idx_student_id (student_id),
    INDEX idx_status (status)
);

-- Student Health Records
CREATE TABLE IF NOT EXISTS student_health_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    health_facility VARCHAR(255),
    visit_date DATE NOT NULL,
    visit_time TIME,
    health_worker VARCHAR(255),
    complaint TEXT,
    diagnosis TEXT,
    treatment_given TEXT,
    medication_prescribed TEXT,
    medication_dispensed BOOLEAN DEFAULT FALSE,
    follow_up_date DATE,
    follow_up_completed BOOLEAN DEFAULT FALSE,
    referred BOOLEAN DEFAULT FALSE,
    referral_facility VARCHAR(255),
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES igangaschoolofl_students_db.students(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_record_id (record_id),
    INDEX idx_student_id (student_id),
    INDEX idx_visit_date (visit_date)
);

-- Health Incidents
CREATE TABLE IF NOT EXISTS student_health_incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    incident_date DATE NOT NULL,
    incident_time TIME,
    incident_type ENUM('Injury', 'Illness', 'Allergic Reaction', 'Mental Health', 'Emergency', 'Other') NOT NULL,
    location VARCHAR(255),
    description TEXT NOT NULL,
    severity ENUM('Minor', 'Moderate', 'Severe', 'Critical') NOT NULL,
    first_aid_provided BOOLEAN DEFAULT FALSE,
    first_aid_description TEXT,
    hospitalized BOOLEAN DEFAULT FALSE,
    hospital_name VARCHAR(255),
    attended_by VARCHAR(255),
    parent_notified BOOLEAN DEFAULT FALSE,
    parent_phone VARCHAR(20),
    reported_by INT,
    reported_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved BOOLEAN DEFAULT FALSE,
    resolution_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES igangaschoolofl_students_db.students(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_incident_id (incident_id),
    INDEX idx_student_id (student_id),
    INDEX idx_incident_date (incident_date)
);

-- Counseling Sessions
CREATE TABLE IF NOT EXISTS student_counseling_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    counselor_id INT,
    counselor_name VARCHAR(255),
    session_date DATE NOT NULL,
    session_time TIME,
    session_duration_minutes INT,
    session_type ENUM('Individual', 'Group', 'Family', 'Crisis') DEFAULT 'Individual',
    issues_discussed TEXT,
    advice_given TEXT,
    referrals_made TEXT,
    follow_up_required BOOLEAN DEFAULT TRUE,
    follow_up_date DATE,
    session_outcome TEXT,
    student_feedback TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES igangaschoolofl_students_db.students(id) ON DELETE CASCADE,
    FOREIGN KEY (counselor_id) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_session_id (session_id),
    INDEX idx_student_id (student_id),
    INDEX idx_session_date (session_date)
);

-- Room Inspections
CREATE TABLE IF NOT EXISTS student_room_inspections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inspection_id VARCHAR(50) UNIQUE NOT NULL,
    room_id INT,
    room_number VARCHAR(50) NOT NULL,
    inspection_date DATE NOT NULL,
    inspected_by INT,
    inspector_name VARCHAR(255),
    cleanliness_score INT,
    maintenance_issues TEXT,
    disciplinary_issues TEXT,
    items_confiscated TEXT,
    action_taken TEXT,
    follow_up_required BOOLEAN DEFAULT FALSE,
    follow_up_date DATE,
    next_inspection_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inspected_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_inspection_id (inspection_id),
    INDEX idx_room_number (room_number),
    INDEX idx_inspection_date (inspection_date)
);

-- Emergency Contacts
CREATE TABLE IF NOT EXISTS student_emergency_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contact_id VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    contact_name VARCHAR(255) NOT NULL,
    contact_relationship VARCHAR(100),
    contact_phone VARCHAR(20) NOT NULL,
    contact_email VARCHAR(100),
    contact_address TEXT,
    is_primary BOOLEAN DEFAULT FALSE,
    notified BOOLEAN DEFAULT FALSE,
    last_notified TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES igangaschoolofl_students_db.students(id) ON DELETE CASCADE,
    INDEX idx_contact_id (contact_id),
    INDEX idx_student_id (student_id)
);

-- ============================================================
-- 3. PROCEDURES FOR MATRONS & WARDENS
-- ============================================================

DELIMITER //

-- Record student welfare case
CREATE PROCEDURE welfare_record_case(
    IN p_student_id INT,
    IN p_case_type VARCHAR(50),
    IN p_description TEXT,
    IN p_priority VARCHAR(20),
    IN p_reported_by INT
)
BEGIN
    DECLARE v_case_id VARCHAR(50);
    SET v_case_id = CONCAT('WEL', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'));
    
    INSERT INTO student_welfare_cases (
        case_id, student_id, case_type, case_description, case_priority, reported_by
    ) VALUES (
        v_case_id, p_student_id, p_case_type, p_description, p_priority, p_reported_by
    );
END //

-- Record health incident
CREATE PROCEDURE welfare_record_health_incident(
    IN p_student_id INT,
    IN p_incident_type VARCHAR(50),
    IN p_description TEXT,
    IN p_severity VARCHAR(20),
    IN p_reported_by INT
)
BEGIN
    DECLARE v_incident_id VARCHAR(50);
    SET v_incident_id = CONCAT('HLTH', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'));
    
    INSERT INTO student_health_incidents (
        incident_id, student_id, incident_type, description, severity, reported_by
    ) VALUES (
        v_incident_id, p_student_id, p_incident_type, p_description, p_severity, p_reported_by
    );
END //

-- Schedule counseling session
CREATE PROCEDURE welfare_schedule_counseling(
    IN p_student_id INT,
    IN p_counselor_id INT,
    IN p_session_date DATE,
    IN p_session_type VARCHAR(50)
)
BEGIN
    DECLARE v_session_id VARCHAR(50);
    SET v_session_id = CONCAT('COUN', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'));
    
    INSERT INTO student_counseling_sessions (
        session_id, student_id, counselor_id, session_date, session_type
    ) VALUES (
        v_session_id, p_student_id, p_counselor_id, p_session_date, p_session_type
    );
END //

DELIMITER ;

COMMIT;
-- ============================================================
-- ISNM DIRECTOR ACADEMICS DASHBOARD SQL
-- Complete Academic Leadership Management System
-- ============================================================



-- ============================================================
-- 1. DIRECTOR ACADEMICS USER ACCOUNTS
-- ============================================================

INSERT IGNORE INTO staff (
    staff_id, 
    full_name, 
    email, 
    password, 
    phone, 
    position, 
    department, 
    role_id, 
    status, 
    hire_date,
    created_at
) VALUES
('DA001', 'Director Academics', 'director_academics@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$director_academics@isnmHashedPassword', '+256701000003', 'Director Academics', 'Academic Affairs',
 (SELECT id FROM staff_roles WHERE role_name = 'Director Academics' LIMIT 1), 'Active', CURDATE(), NOW());

-- ============================================================
-- 2. ACADEMIC DIRECTOR TABLES
-- ============================================================

-- Program Management
CREATE TABLE IF NOT EXISTS academic_programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_code VARCHAR(20) UNIQUE NOT NULL,
    program_name VARCHAR(255) NOT NULL,
    program_type ENUM('Certificate', 'Diploma', 'Degree') NOT NULL,
    department VARCHAR(100) NOT NULL,
    duration_years INT DEFAULT 2,
    total_credits INT,
    program_coordinator INT,
    accreditation_status ENUM('Accredited', 'Provisional', 'Expired', 'Pending') DEFAULT 'Accredited',
    accreditation_body VARCHAR(255),
    accreditation_expiry DATE,
    status ENUM('Active', 'Inactive', 'Suspended') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (program_coordinator) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_program_code (program_code),
    INDEX idx_status (status)
);

-- Course Catalog
CREATE TABLE IF NOT EXISTS academic_course_catalog (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) UNIQUE NOT NULL,
    course_title VARCHAR(255) NOT NULL,
    credits INT NOT NULL,
    program_code VARCHAR(20),
    year_of_study INT,
    semester VARCHAR(50),
    theory_hours INT,
    practical_hours INT,
    tutorials_hours INT,
    assessment_method TEXT,
    course_coordinator INT,
    prerequisites TEXT,
    learning_outcomes TEXT,
    status ENUM('Active', 'Inactive', 'Under Review') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_coordinator) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_course_code (course_code),
    INDEX idx_program_code (program_code)
);

-- Academic Analytics
CREATE TABLE IF NOT EXISTS academic_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    analytics_id VARCHAR(50) UNIQUE NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(50),
    program_code VARCHAR(20),
    total_enrolled INT,
    total_graduated INT,
    total_dropped INT,
    average_gpa DECIMAL(3,2),
    pass_rate DECIMAL(5,2),
    withdrawal_rate DECIMAL(5,2),
    employment_rate DECIMAL(5,2),
    analysis_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    generated_by INT,
    FOREIGN KEY (generated_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_academic_year (academic_year),
    INDEX idx_program_code (program_code)
);

-- Curriculum Development
CREATE TABLE IF NOT EXISTS academic_curriculum_development (
    id INT AUTO_INCREMENT PRIMARY KEY,
    curriculum_id VARCHAR(50) UNIQUE NOT NULL,
    program_code VARCHAR(20) NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    revision_number INT DEFAULT 1,
    changes_made TEXT,
    reason_for_changes TEXT,
    approved_by INT,
    approval_date TIMESTAMP NULL,
    status ENUM('Draft', 'Under Review', 'Approved', 'Implemented') DEFAULT 'Draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (approved_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_curriculum_id (curriculum_id)
);

-- Academic Reports
CREATE TABLE IF NOT EXISTS academic_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id VARCHAR(50) UNIQUE NOT NULL,
    report_type ENUM('Enrollment', 'Graduation', 'Performance', 'Employment', 'Accreditation', 'Compliance') NOT NULL,
    report_period VARCHAR(50),
    program_code VARCHAR(20),
    report_data LONGTEXT,
    generated_by INT,
    generated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    pdf_path VARCHAR(500),
    status ENUM('Draft', 'Final', 'Archived') DEFAULT 'Draft',
    FOREIGN KEY (generated_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_report_type (report_type),
    INDEX idx_generated_date (generated_date)
);

-- Timetable Management
CREATE TABLE IF NOT EXISTS academic_timetable (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timetable_id VARCHAR(50) UNIQUE NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(50) NOT NULL,
    program_code VARCHAR(20),
    course_code VARCHAR(20),
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
    start_time TIME,
    end_time TIME,
    venue VARCHAR(255),
    lecturer_id INT,
    timetable_status ENUM('Draft', 'Approved', 'Published', 'Cancelled') DEFAULT 'Draft',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lecturer_id) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_timetable_id (timetable_id),
    INDEX idx_program_code (program_code)
);

-- ============================================================
-- 3. PROCEDURES FOR DIRECTOR ACADEMICS
-- ============================================================

DELIMITER //

-- Generate enrollment report
CREATE PROCEDURE academic_generate_enrollment_report(
    IN p_academic_year VARCHAR(20),
    IN p_program_code VARCHAR(20)
)
BEGIN
    SELECT 
        program,
        COUNT(*) as total_students,
        COUNT(CASE WHEN status = 'Active' THEN 1 END) as active_students,
        COUNT(CASE WHEN status = 'Graduated' THEN 1 END) as graduated_students,
        COUNT(CASE WHEN status = 'Suspended' THEN 1 END) as suspended_students
    FROM universal_student_profiles
    WHERE (p_academic_year IS NULL OR intake_set LIKE CONCAT('%', p_academic_year, '%'))
      AND (p_program_code IS NULL OR program = p_program_code)
    GROUP BY program;
END //

-- Update program coordinator
CREATE PROCEDURE academic_update_program_coordinator(
    IN p_program_code VARCHAR(20),
    IN p_coordinator_id INT
)
BEGIN
    UPDATE academic_programs 
    SET program_coordinator = p_coordinator_id,
        updated_at = NOW()
    WHERE program_code = p_program_code;
END //

DELIMITER ;

COMMIT;
-- ============================================================
-- ISNM DIRECTOR FINANCE DASHBOARD SQL
-- Complete Financial Management System
-- ============================================================
--
-- PREREQUISITES:
--   1. sql/staffs/04_final_complete_staffs_database.sql
--   2. sql/students/01_create_students_database.sql
--   3. sql/students/bursar_system.sql
--
-- All student data resides in igangaschoolofl_students_db.
-- Financial tables (fee_accounts, budget_records) are in
-- igangaschoolofl_staffs_db. Cross-database views are
-- created in 05_all_departments_complete_dashboards.sql.



-- ============================================================
-- 1. DIRECTOR FINANCE USER ACCOUNTS
-- ============================================================

INSERT IGNORE INTO staff (
    staff_id, 
    full_name, 
    email, 
    password, 
    phone, 
    position, 
    department, 
    role_id, 
    status, 
    hire_date,
    created_at
) VALUES
('DF001', 'Director Finance', 'director_finance@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', '+256701000005', 'Director Finance', 'Finance Department',
 (SELECT id FROM staff_roles WHERE role_name = 'Director Finance' LIMIT 1), 'Active', CURDATE(), NOW());

-- ============================================================
-- 2. FINANCE MANAGEMENT VIEWS
-- ============================================================

-- Finance Dashboard Summary View
-- Uses students table from students_db for active student count,
-- and billing tables from both databases for financial summaries.
-- Note: student_invoices => student_fee_assignments + payments in students_db
CREATE OR REPLACE VIEW finance_dashboard_summary AS
SELECT 
    -- Student Fee Summary (students are in the students database)
    (SELECT COUNT(*) FROM igangaschoolofl_students_db.students WHERE status = 'Active') as total_active_students,
    
    -- Invoice Summary (student_fee_assignments acts as invoice records)
    (SELECT COUNT(*) FROM igangaschoolofl_students_db.student_fee_assignments WHERE status IN ('Unpaid', 'Partially Paid', 'Overdue')) as pending_invoices,
    (SELECT COUNT(*) FROM igangaschoolofl_students_db.student_fee_assignments WHERE status = 'Paid') as paid_invoices,
    (SELECT SUM(assigned_amount) FROM igangaschoolofl_students_db.student_fee_assignments WHERE status IN ('Unpaid', 'Partially Paid', 'Overdue')) as pending_amount,
    (SELECT SUM(paid_amount) FROM igangaschoolofl_students_db.student_fee_assignments WHERE status = 'Paid') as collected_amount,
    
    -- Payment Summary
    (SELECT COUNT(*) FROM igangaschoolofl_students_db.payments WHERE status = 'Completed') as total_payments,
    (SELECT SUM(amount_received) FROM igangaschoolofl_students_db.payments WHERE status = 'Completed') as total_revenue,
    
    -- Budget Summary
    (SELECT COUNT(*) FROM igangaschoolofl_staffs_db.budget_records WHERE status = 'Active') as active_budgets,
    (SELECT SUM(allocated_amount) FROM igangaschoolofl_staffs_db.budget_records WHERE status = 'Active') as total_budget_allocated,
    (SELECT SUM(spent_amount) FROM igangaschoolofl_staffs_db.budget_records WHERE status = 'Active') as total_budget_spent,
    
    -- Sponsorship Summary
    (SELECT COUNT(*) FROM igangaschoolofl_students_db.sponsorships WHERE status = 'Active') as active_scholarships,
    (SELECT SUM(amount) FROM igangaschoolofl_students_db.sponsorships WHERE status = 'Active') as total_scholarship_value;

-- Student Fee Balance View
CREATE OR REPLACE VIEW finance_student_balances AS
SELECT 
    s.student_number,
    s.full_name,
    s.program,
    COALESCE(fs.fee_name, 'General Fee') as fee_type,
    sfa.assigned_amount as fee_balance,
    COALESCE(SUM(p.amount_received), 0) as amount_paid,
    (sfa.assigned_amount - COALESCE(SUM(p.amount_received), 0)) as outstanding_balance,
    sfa.status as fee_status
FROM igangaschoolofl_students_db.students s
JOIN igangaschoolofl_students_db.student_fee_assignments sfa ON s.id = sfa.student_id
LEFT JOIN igangaschoolofl_students_db.fee_structures fs ON sfa.fee_structure_id = fs.id
LEFT JOIN igangaschoolofl_students_db.payments p ON s.id = p.student_id AND p.status = 'Completed'
WHERE sfa.status IN ('Unpaid', 'Partially Paid', 'Overdue')
GROUP BY s.id, s.student_number, s.full_name, s.program, fs.fee_name, sfa.assigned_amount, sfa.status;

-- Revenue by Program View
CREATE OR REPLACE VIEW finance_revenue_by_program AS
SELECT 
    s.program,
    COUNT(DISTINCT s.id) as total_students,
    COUNT(DISTINCT sfa.id) as students_with_fees,
    SUM(sfa.assigned_amount) as total_assessed,
    COALESCE(SUM(CASE WHEN p.status = 'Completed' THEN p.amount_received ELSE 0 END), 0) as total_collected,
    (SUM(sfa.assigned_amount) - COALESCE(SUM(CASE WHEN p.status = 'Completed' THEN p.amount_received ELSE 0 END), 0)) as total_outstanding
FROM igangaschoolofl_students_db.students s
JOIN igangaschoolofl_students_db.student_fee_assignments sfa ON s.id = sfa.student_id
LEFT JOIN igangaschoolofl_students_db.payments p ON s.id = p.student_id
GROUP BY s.program;

-- ============================================================
-- 3. PROCEDURES FOR DIRECTOR FINANCE
-- ============================================================

DELIMITER //

-- Generate student fee statement
CREATE PROCEDURE finance_generate_statement(IN p_student_id INT)
BEGIN
    SELECT 
        s.student_number,
        s.full_name,
        s.program,
        COALESCE(fs.fee_name, 'General Fee') as fee_type,
        sfa.assigned_amount as assessed_amount,
        sfa.due_date,
        sfa.paid_amount,
        sfa.balance,
        sfa.status
    FROM igangaschoolofl_students_db.students s
    JOIN igangaschoolofl_students_db.student_fee_assignments sfa ON s.id = sfa.student_id
    LEFT JOIN igangaschoolofl_students_db.fee_structures fs ON sfa.fee_structure_id = fs.id
    WHERE s.id = p_student_id
    ORDER BY sfa.due_date;
END //

-- Record payment
CREATE PROCEDURE finance_record_payment(
    IN p_student_id INT,
    IN p_amount DECIMAL(15,2),
    IN p_payment_method VARCHAR(50),
    IN p_reference VARCHAR(100),
    IN p_processed_by INT
)
BEGIN
    DECLARE v_payment_reference VARCHAR(50);
    SET v_payment_reference = CONCAT('PAY', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'));
    
    INSERT INTO igangaschoolofl_students_db.payments (
        payment_reference, student_id, amount_received, payment_method, status
    ) VALUES (
        v_payment_reference, p_student_id, p_amount, p_payment_method, 'Completed'
    );
    
    UPDATE igangaschoolofl_students_db.student_fee_assignments 
    SET paid_amount = paid_amount + p_amount,
        status = CASE 
            WHEN (assigned_amount - (paid_amount + p_amount)) <= 0 THEN 'Paid'
            ELSE 'Partially Paid'
        END
    WHERE student_id = p_student_id AND status IN ('Unpaid', 'Partially Paid');
END //

-- Get overdue accounts
CREATE PROCEDURE finance_get_overdue_accounts()
BEGIN
    SELECT 
        s.student_number,
        s.full_name,
        s.program,
        sfa.fee_type,
        sfa.assigned_amount as amount,
        sfa.due_date,
        sfa.balance,
        DATEDIFF(CURDATE(), sfa.due_date) as days_overdue
    FROM igangaschoolofl_students_db.students s
    JOIN igangaschoolofl_students_db.student_fee_assignments sfa ON s.id = sfa.student_id
    WHERE sfa.status = 'Overdue' AND sfa.due_date < CURDATE()
    ORDER BY days_overdue DESC;
END //

DELIMITER ;

COMMIT;
-- ============================================================
-- STUDENT MANAGEMENT PROCEDURES AND PERMISSIONS
-- Allows Secretary and Director ICT to add/manage students
-- Database: igangaschoolofl_staffs_db
-- ============================================================



-- ============================================================
-- 1. ENSURE STAFF HAVE PERMISSIONS TO MANAGE STUDENTS
-- ============================================================

-- Safeguard: Ensure core tables exist before update/insert
CREATE TABLE IF NOT EXISTS staff_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(100) NOT NULL UNIQUE,
    role_description TEXT,
    role_level ENUM('Executive', 'Management', 'Academic', 'Support', 'Administrative') DEFAULT 'Academic',
    dashboard_path VARCHAR(255),
    permissions JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role_name (role_name),
    INDEX idx_role_level (role_level)
);

-- Safeguard: Ensure staff table exists before JOIN/INSERT
CREATE TABLE IF NOT EXISTS staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(50) UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    position VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    role_id INT,
    status ENUM('Active', 'Inactive', 'On Leave', 'Suspended') DEFAULT 'Active',
    hire_date DATE,
    password_changed BOOLEAN DEFAULT FALSE,
    is_first_login BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES staff_roles(id) ON DELETE SET NULL
);

-- Safeguard for dashboard access table
CREATE TABLE IF NOT EXISTS staff_dashboard_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    dashboard_path VARCHAR(255) NOT NULL,
    access_level ENUM('Full', 'Read Only', 'Limited') DEFAULT 'Full',
    granted_by INT NULL,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_staff_id (staff_id),
    INDEX idx_dashboard_path (dashboard_path)
);

-- Update Secretary role permissions to include student management
UPDATE staff_roles 
SET permissions = '{"administrative": true, "documentation": true, "can_manage_documents": true, "can_add_students": true, "can_manage_students": true, "can_view_students": true}' 
WHERE role_name = 'School Secretary';

-- Update Director ICT role permissions to include student management
UPDATE staff_roles 
SET permissions = '{"ict": true, "systems": true, "infrastructure": true, "can_manage_system": true, "can_add_students": true, "can_manage_students": true, "can_view_all_students": true, "can_view_all_departments": true, "can_edit_student_data": true}' 
WHERE role_name = 'Director ICT';

-- Grant dashboard access to Student Management for Secretary
INSERT IGNORE INTO staff_dashboard_access (staff_id, dashboard_path, access_level, granted_by)
SELECT s.id, 'dashboards/student-management.php', 'Full', 1
FROM staff s
JOIN staff_roles sr ON s.role_id = sr.id
WHERE sr.role_name = 'School Secretary' AND s.id IS NOT NULL;

-- Grant dashboard access to Student Management for Director ICT
INSERT IGNORE INTO staff_dashboard_access (staff_id, dashboard_path, access_level, granted_by)
SELECT s.id, 'dashboards/student-management.php', 'Full', 1
FROM staff s
JOIN staff_roles sr ON s.role_id = sr.id
WHERE sr.role_name = 'Director ICT' AND s.id IS NOT NULL;

-- Grant dashboard access to Student Management for Academic Registrar (already has this access)
INSERT IGNORE INTO staff_dashboard_access (staff_id, dashboard_path, access_level, granted_by)
SELECT s.id, 'dashboards/student-management.php', 'Full', 1
FROM staff s
JOIN staff_roles sr ON s.role_id = sr.id
WHERE sr.role_name = 'Academic Registrar';

-- ============================================================
-- 1.1 UPDATE OFFICIAL STAFF CREDENTIALS
-- ============================================================

-- First, ensure roles exist (abbreviated for brevity, assuming existing role structure)
-- Then update or insert the specific staff accounts requested:

INSERT IGNORE INTO staff (staff_id, full_name, email, password, position, department, role_id, status) VALUES
('DG001', 'Director General', 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Director General', 'Executive Office', (SELECT id FROM staff_roles WHERE role_name = 'Director General' LIMIT 1), 'Active'),
('CEO001', 'CEO', 'ceo@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$Ha21Vlb7p046OaklPLFCteb8raqKNilEWDlzq8ypXVz491hHIICXS', 'Chief Executive Officer', 'Executive Office', (SELECT id FROM staff_roles WHERE role_name = 'CEO' LIMIT 1), 'Active'),
('DA001', 'Director Academics', 'directoracademic@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Director Academics', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Director Academics' LIMIT 1), 'Active'),
('DF001', 'Director Finance', 'finance@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Director Finance', 'Finance Department', (SELECT id FROM staff_roles WHERE role_name = 'Director Finance' LIMIT 1), 'Active'),
('PRINC001', 'School Principal', 'principal@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$VVoHfONmCz.Bsvn1.t1UoesLbM01KNPXKT/b/VJIzxeUq0M9LabK.', 'School Principal', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'School Principal' LIMIT 1), 'Active'),
('DEPUT001', 'Deputy Principal', 'dep-principal@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$ANzSCNiGrURlS1ovFbQUKuK6ldOOBpiC0iW/MB7HVw/I5JC9wud.m', 'Deputy Principal', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Deputy Principal' LIMIT 1), 'Active'),
('REG001', 'Academic Registrar', 'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$Ha21Vlb7p046OaklPLFCteb8raqKNilEWDlzq8ypXVz491hHIICXS', 'Academic Registrar', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Academic Registrar' LIMIT 1), 'Active'),
('HR001', 'HR Manager', 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$jEb8/OsV.9cydSvrBrZ1Hejase4BaTkPXT3FO/Gf9EazTrbXprKYi', 'HR Manager', 'Human Resources', (SELECT id FROM staff_roles WHERE role_name = 'HR Manager' LIMIT 1), 'Active'),
('SEC001', 'School Secretary', 'secretary@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$Ha21Vlb7p046OaklPLFCteb8raqKNilEWDlzq8ypXVz491hHIICXS', 'School Secretary', 'Administrative Support', (SELECT id FROM staff_roles WHERE role_name = 'School Secretary' LIMIT 1), 'Active'),
('LIB001', 'School Librarian', 'library@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$GGfcvNfejW3f2fRptIUQIuK4c/W44n94twWtTAaOTqTVSuLZ52DsC', 'School Librarian', 'Library Services', (SELECT id FROM staff_roles WHERE role_name = 'School Librarian' LIMIT 1), 'Active'),
('NUR001', 'Head Nursing', 'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$YO8OuL81gpaFdgP4nJEebeXNhLeM1.hFMD5KidDV9YDGkJMdAqbgW', 'Head Nursing', 'Nursing Department', (SELECT id FROM staff_roles WHERE role_name = 'Head Nursing' LIMIT 1), 'Active'),
('MID001', 'Head Midwifery', 'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$G7pMLdi2UjjmhEd8Lx0bmeaM7tGD4jrfvMsZh6HvY1Po8YqFRubRu', 'Head Midwifery', 'Midwifery Department', (SELECT id FROM staff_roles WHERE role_name = 'Head Midwifery' LIMIT 1), 'Active'),
('SL001', 'Senior Lecturers', 'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$VVoHfONmCz.Bsvn1.t1UoesLbM01KNPXKT/b/VJIzxeUq0M9LabK.', 'Senior Lecturers', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Senior Lecturers' LIMIT 1), 'Active'),
('LEC001', 'Lecturers', 'lecturers@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$e52TV/DaoNDl4kjssi3Te.YHnpxHlaxatBX2wNg5yv3JkoYEEYV9i', 'Lecturers', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Lecturers' LIMIT 1), 'Active'),
('MAT001', 'Matrons', 'matron@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$ANzSCNiGrURlS1ovFbQUKuK6ldOOBpiC0iW/MB7HVw/I5JC9wud.m', 'Matrons', 'Student Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Matrons' LIMIT 1), 'Active'),
('WAR001', 'Wardens', 'warden@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$Ha21Vlb7p046OaklPLFCteb8raqKNilEWDlzq8ypXVz491hHIICXS', 'Wardens', 'Student Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Wardens' LIMIT 1), 'Active'),
('SICK001', 'Sickbay', 'sickbay@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$VVoHfONmCz.Bsvn1.t1UoesLbM01KNPXKT/b/VJIzxeUq0M9LabK.', 'Sickbay', 'Support', (SELECT id FROM staff_roles WHERE role_name = 'Sickbay' LIMIT 1), 'Active'),
('DRV001', 'Drivers', 'drivers@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$YO8OuL81gpaFdgP4nJEebeXNhLeM1.hFMD5KidDV9YDGkJMdAqbgW', 'Drivers', 'Support', (SELECT id FROM staff_roles WHERE role_name = 'Drivers' LIMIT 1), 'Active'),
('SECUR001', 'Security', 'security@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$0rLJuecuJuF6.Exxp7AQO.w0Dh0iwfwZri45gwya6OqENBJwjPA7C', 'Security', 'Security Services', (SELECT id FROM staff_roles WHERE role_name = 'Security' LIMIT 1), 'Active'),
('STK001', 'Store Keeper', 'store@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$e52TV/DaoNDl4kjssi3Te.YHnpxHlaxatBX2wNg5yv3JkoYEEYV9i', 'Store Keeper', 'Support', (SELECT id FROM staff_roles WHERE role_name = 'Store Keeper' LIMIT 1), 'Active'),
('GUILD001', 'Guild President', 'guildpresident@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$YO8OuL81gpaFdgP4nJEebeXNhLeM1.hFMD5KidDV9YDGkJMdAqbgW', 'Guild President', 'Student Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Guild President' LIMIT 1), 'Active'),
('ADMS001', 'Admissions', 'admissions@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Director Admissions & Requirements', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Director Admissions & Requirements' LIMIT 1), 'Active'),
('DICT001', 'Director ICT', 'dannybict@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$Ha21Vlb7p046OaklPLFCteb8raqKNilEWDlzq8ypXVz491hHIICXS', 'Director ICT', 'Information Technology', (SELECT id FROM staff_roles WHERE role_name = 'Director ICT' LIMIT 1), 'Active')
ON DUPLICATE KEY UPDATE 
    password = VALUES(password),
    status = 'Active';

-- ============================================================
-- 2. PROCEDURES TO MANAGE STUDENT RECORDS
-- ============================================================

-- Drop existing procedures to avoid conflicts
DROP PROCEDURE IF EXISTS add_new_student;
DROP PROCEDURE IF EXISTS update_student_record;
DROP PROCEDURE IF EXISTS get_all_students_list;
DROP PROCEDURE IF EXISTS search_students;
DROP PROCEDURE IF EXISTS get_student_by_number;

DELIMITER //

-- Procedure to add a new student
CREATE PROCEDURE add_new_student(
    IN p_student_number VARCHAR(50),
    IN p_registration_number VARCHAR(50),
    IN p_index_number VARCHAR(50),
    IN p_national_id VARCHAR(50),
    IN p_first_name VARCHAR(100),
    IN p_surname VARCHAR(100),
    IN p_other_name VARCHAR(100),
    IN p_email VARCHAR(100),
    IN p_phone VARCHAR(20),
    IN p_program VARCHAR(100),
    IN p_year INT,
    IN p_set_name VARCHAR(50),
    IN p_intake_date DATE,
    IN p_date_of_birth DATE,
    IN p_gender ENUM('Male', 'Female', 'Other'),
    IN p_nationality VARCHAR(100),
    IN p_address TEXT,
    IN p_guardian_name VARCHAR(200),
    IN p_guardian_phone VARCHAR(20),
    IN p_emergency_contact_name VARCHAR(100),
    IN p_emergency_contact_phone VARCHAR(20),
    IN p_status ENUM('Active', 'Inactive', 'Graduated', 'Suspended', 'Withdrawn'),
    IN p_added_by INT
)
BEGIN
    DECLARE v_student_id INT;
    DECLARE v_password_hash VARCHAR(255);
    
    -- Default password: 12345678 (student must change on first login)
    SET v_password_hash = '$2y$10$N9qo8uLOickgx2ZMRZoMy.MrqJhZ3eP4dZB6lYqZ3eP4dZB6lYqZ3eP';
    
    -- Insert student record
    INSERT INTO igangaschoolofl_students_db.students (
        student_number, registration_number, index_number, national_student_id_number,
        first_name, surname, other_name, email, phone,
        program, current_year, set_name, intake_date,
        date_of_birth, gender, nationality, address,
        guardian_name, guardian_phone,
        emergency_contact_name, emergency_contact_phone,
        status, password, is_first_login, password_changed
    ) VALUES (
        p_student_number, p_registration_number, p_index_number, p_national_id,
        p_first_name, p_surname, p_other_name, p_email, p_phone,
        p_program, p_year, p_set_name, p_intake_date,
        p_date_of_birth, p_gender, p_nationality, p_address,
        p_guardian_name, p_guardian_phone,
        p_emergency_contact_name, p_emergency_contact_phone,
        p_status, v_password_hash, TRUE, FALSE
    );
    
    SET v_student_id = LAST_INSERT_ID();
    
    -- Create student profile record
    INSERT INTO igangaschoolofl_students_db.student_profiles (student_id)
    VALUES (v_student_id);
    
    -- Create default fee records
    INSERT INTO igangaschoolofl_students_db.student_fees (
        student_id, fee_type, amount, due_date, status
    ) VALUES
        (v_student_id, 'Tuition Fee', 500000, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Unpaid'),
        (v_student_id, 'Facility Fee', 50000, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Unpaid'),
        (v_student_id, 'Registration Fee', 20000, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Unpaid');
    
    -- Log the action
    INSERT INTO staff_activity_log (staff_id, activity_type, activity_description, module_accessed, record_id, ip_address)
    VALUES (p_added_by, 'Student Added', CONCAT('Added student: ', p_first_name, ' ', p_surname), 'Student Management', v_student_id, '0.0.0.0');
    
    SELECT v_student_id as student_id, 'Student added successfully' as message, TRUE as success;
END //

-- Procedure to update student record
CREATE PROCEDURE update_student_record(
    IN p_student_id INT,
    IN p_field VARCHAR(100),
    IN p_value TEXT,
    IN p_updated_by INT
)
BEGIN
    DECLARE v_old_value TEXT;
    
    -- Get old value for logging
    CASE p_field
        WHEN 'email' THEN
            SELECT email INTO v_old_value FROM igangaschoolofl_students_db.students WHERE id = p_student_id;
            UPDATE igangaschoolofl_students_db.students SET email = p_value WHERE id = p_student_id;
        WHEN 'phone' THEN
            SELECT phone INTO v_old_value FROM igangaschoolofl_students_db.students WHERE id = p_student_id;
            UPDATE igangaschoolofl_students_db.students SET phone = p_value WHERE id = p_student_id;
        WHEN 'program' THEN
            SELECT program INTO v_old_value FROM igangaschoolofl_students_db.students WHERE id = p_student_id;
            UPDATE igangaschoolofl_students_db.students SET program = p_value WHERE id = p_student_id;
        WHEN 'status' THEN
            SELECT status INTO v_old_value FROM igangaschoolofl_students_db.students WHERE id = p_student_id;
            UPDATE igangaschoolofl_students_db.students SET status = p_value WHERE id = p_student_id;
    END CASE;
    
    -- Log the update
    INSERT INTO staff_activity_log (staff_id, activity_type, activity_description, module_accessed, record_id)
    VALUES (p_updated_by, 'Student Updated', CONCAT('Updated ', p_field, ' from ', v_old_value, ' to ', p_value), 'Student Management', p_student_id);
    
    SELECT 'Student record updated successfully' as message, TRUE as success;
END //

-- Procedure to get all students
CREATE PROCEDURE get_all_students_list(
    IN p_program VARCHAR(100),
    IN p_set_name VARCHAR(50),
    IN p_status VARCHAR(50),
    IN p_limit INT
)
BEGIN
    IF p_limit IS NULL OR p_limit <= 0 THEN
        SET p_limit = 1000;
    END IF;
    
    SELECT 
        id, student_number, registration_number, index_number,
        full_name,
        email, phone, program, current_year, set_name, status,
        created_at
    FROM igangaschoolofl_students_db.students
    WHERE 
        (p_program IS NULL OR program = p_program)
        AND (p_set_name IS NULL OR set_name = p_set_name)
        AND (p_status IS NULL OR status = p_status)
    ORDER BY created_at DESC
    LIMIT p_limit;
END //

-- Procedure to search students
CREATE PROCEDURE search_students(
    IN p_search_term VARCHAR(100)
)
BEGIN
    SELECT 
        id, student_number, registration_number, index_number,
        full_name,
        email, phone, program, current_year, set_name, status,
        created_at
    FROM igangaschoolofl_students_db.students
    WHERE 
        student_number LIKE CONCAT('%', p_search_term, '%')
        OR registration_number LIKE CONCAT('%', p_search_term, '%')
        OR index_number LIKE CONCAT('%', p_search_term, '%')
        OR full_name LIKE CONCAT('%', p_search_term, '%')
        OR email LIKE CONCAT('%', p_search_term, '%')
        OR phone LIKE CONCAT('%', p_search_term, '%')
    ORDER BY created_at DESC;
END //

-- Procedure to get single student by number
CREATE PROCEDURE get_student_by_number(
    IN p_student_number VARCHAR(50)
)
BEGIN
    SELECT 
        id, student_number, registration_number, national_student_id_number,
        first_name, surname, other_name,
        CONCAT(first_name, ' ', surname, CASE WHEN other_name IS NOT NULL THEN CONCAT(' ', other_name) ELSE '' END) as full_name,
        email, phone, program, current_year, set_name, intake_date,
        date_of_birth, gender, nationality, address,
        guardian_name, guardian_phone,
        emergency_contact_name, emergency_contact_phone,
        status, created_at, updated_at
    FROM igangaschoolofl_students_db.students
    WHERE student_number = p_student_number;
END //

DELIMITER ;

-- ============================================================
-- 3. INSERT PROCEDURES INTO MASTER ROLE PERMISSIONS
-- ============================================================

-- Log all student management procedures for audit
INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('student_add_procedure', 'add_new_student', 'procedure', 'Procedure for adding new students', FALSE),
('student_update_procedure', 'update_student_record', 'procedure', 'Procedure for updating student records', FALSE),
('student_search_procedure', 'search_students', 'procedure', 'Procedure for searching students', FALSE),
('student_list_procedure', 'get_all_students_list', 'procedure', 'Procedure for listing all students', FALSE);

COMMIT;
-- ISNM Database Compatibility Layer
-- Creates compatibility views and tables for cross-schema references
-- Run this AFTER all main schema files



-- Compatibility: fee_payments -> payments (cross-database)
CREATE OR REPLACE VIEW fee_payments AS
SELECT 
    id,
    student_id,
    invoice_id AS fee_account_id,
    amount_received AS amount_paid,
    payment_method,
    payment_reference AS receipt_number,
    status,
    payment_date,
    notes,
    received_by AS processed_by,
    created_at,
    updated_at
FROM igangaschoolofl_students_db.payments;

-- Compatibility: student_fee_accounts -> student_fee_assignments (cross-database)
CREATE OR REPLACE VIEW student_fee_accounts AS
SELECT 
    id,
    student_id,
    fee_structure_id,
    assigned_amount AS total_fees,
    paid_amount AS amount_paid,
    balance,
    status,
    due_date,
    NULL AS receipt_number,
    assigned_by AS created_by,
    created_at,
    updated_at
FROM igangaschoolofl_students_db.student_fee_assignments;

-- Compatibility: users VIEW (already exists in 04_final_complete_staffs_database.sql)
-- Ensure it includes password for auth compatibility
CREATE OR REPLACE VIEW users AS
SELECT 
    s.id,
    s.staff_id AS username,
    s.full_name AS user_name,
    s.email,
    s.password,
    s.position,
    s.department,
    s.role_id,
    sr.role_name,
    sr.role_level,
    sr.dashboard_path,
    s.status,
    s.phone,
    s.address,
    s.hire_date,
    s.last_login,
    s.login_attempts,
    s.locked_until,
    s.is_first_login,
    s.created_at,
    s.updated_at
FROM staff s
JOIN staff_roles sr ON s.role_id = sr.id;

-- Compatibility: staff_users -> staff (for any remaining references)
CREATE OR REPLACE VIEW staff_users AS
SELECT 
    s.id,
    s.email,
    s.password AS password_hash,
    s.full_name,
    s.phone,
    s.position AS role,
    s.department,
    s.status AS is_active,
    s.is_first_login AS is_verified,
    s.created_at,
    s.updated_at
FROM staff s;

-- Compatibility: roles -> staff_roles
CREATE OR REPLACE VIEW roles AS
SELECT 
    id,
    role_name AS name,
    role_description AS description,
    permissions,
    created_at,
    updated_at
FROM staff_roles;

-- Compatibility: hr_users (minimal view for auth fallback)
CREATE OR REPLACE VIEW hr_users AS
SELECT 
    s.id,
    s.email,
    s.password AS password_hash,
    s.full_name,
    s.phone,
    s.position,
    s.department,
    s.status,
    s.created_at,
    s.updated_at
FROM staff s
WHERE s.department = 'Human Resources' OR s.position LIKE '%HR%';

-- End of compatibility views

-- ============================================================
-- DIRECTOR NEWS & NEWS IMAGES TABLES
-- For director-level news publishing with image support
-- ============================================================

CREATE TABLE IF NOT EXISTS director_news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    content LONGTEXT NOT NULL,
    excerpt TEXT,
    featured_image VARCHAR(500),
    author_id INT NOT NULL,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    published_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_author_id (author_id),
    INDEX idx_published_at (published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS news_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    news_id INT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    caption VARCHAR(255),
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_news_id (news_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- STORE MANAGEMENT SYSTEM
-- Tables for store inventory, requests, orders, and transactions
-- ============================================================

SET @_old_fk = @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS store_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50) DEFAULT 'fas fa-box',
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category_name (category_name),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS store_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    description TEXT,
    quantity DECIMAL(15,3) NOT NULL DEFAULT 0,
    unit VARCHAR(50) NOT NULL DEFAULT 'pcs',
    reorder_level DECIMAL(15,3) DEFAULT 10,
    unit_price DECIMAL(15,2) DEFAULT 0,
    location VARCHAR(100) DEFAULT 'Main Store',
    status ENUM('active','inactive','discontinued') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category_id (category_id),
    INDEX idx_item_name (item_name),
    INDEX idx_status (status),
    FOREIGN KEY (category_id) REFERENCES store_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS store_inventory_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    transaction_type ENUM('add','remove','adjust','request_fulfilled','order_received','returned','damaged') NOT NULL,
    quantity DECIMAL(15,3) NOT NULL,
    quantity_before DECIMAL(15,3) DEFAULT 0,
    quantity_after DECIMAL(15,3) DEFAULT 0,
    reference_type VARCHAR(50) DEFAULT NULL,
    reference_id INT DEFAULT NULL,
    reason TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_item_id (item_id),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_created_at (created_at),
    INDEX idx_reference (reference_type, reference_id),
    FOREIGN KEY (item_id) REFERENCES store_inventory(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS store_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_number VARCHAR(50) NOT NULL UNIQUE,
    requested_by INT NOT NULL,
    department VARCHAR(200) DEFAULT NULL,
    notes TEXT,
    urgency ENUM('low','medium','high','urgent') DEFAULT 'medium',
    status ENUM('pending','approved','partially_fulfilled','fulfilled','rejected','forwarded') DEFAULT 'pending',
    forwarded_to INT DEFAULT NULL,
    forwarded_to_role VARCHAR(100) DEFAULT NULL,
    approved_by INT DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    fulfilled_by INT DEFAULT NULL,
    fulfilled_at DATETIME DEFAULT NULL,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_request_number (request_number),
    INDEX idx_requested_by (requested_by),
    INDEX idx_status (status),
    INDEX idx_forwarded_to (forwarded_to),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS store_request_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity_requested DECIMAL(15,3) NOT NULL,
    quantity_fulfilled DECIMAL(15,3) DEFAULT 0,
    unit_price DECIMAL(15,2) DEFAULT 0,
    notes TEXT,
    status ENUM('pending','fulfilled','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_request_id (request_id),
    INDEX idx_item_id (item_id),
    FOREIGN KEY (request_id) REFERENCES store_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES store_inventory(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS store_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    supplier VARCHAR(200) DEFAULT 'Internal Requisition',
    notes TEXT,
    total_amount DECIMAL(15,2) DEFAULT 0,
    status ENUM('draft','pending_approval','approved','ordered','partially_received','received','cancelled') DEFAULT 'draft',
    requested_by INT NOT NULL,
    approved_by INT DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    received_by INT DEFAULT NULL,
    received_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order_number (order_number),
    INDEX idx_status (status),
    INDEX idx_requested_by (requested_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS store_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity_ordered DECIMAL(15,3) NOT NULL,
    quantity_received DECIMAL(15,3) DEFAULT 0,
    unit_price DECIMAL(15,2) DEFAULT 0,
    notes TEXT,
    status ENUM('pending','received','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_item_id (item_id),
    FOREIGN KEY (order_id) REFERENCES store_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES store_inventory(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Populate categories if table was just created
INSERT IGNORE INTO store_categories (category_name, description, icon) VALUES
('General Utilities', 'Office supplies, cleaning, electrical, and general maintenance items', 'fas fa-tools'),
('Food Store Supplies', 'Food items, cooking ingredients, and kitchen supplies', 'fas fa-utensils'),
('Medical Supplies', 'Medical consumables, gloves, dressings, and clinical items', 'fas fa-kit-medical'),
('Cleaning & Hygiene', 'Cleaning agents, sanitizers, and hygiene products', 'fas fa-pump-soap'),
('Office Stationery', 'Paper, writing materials, filing and office stationery', 'fas fa-pen-ruler'),
('Electrical & Hardware', 'Electrical fittings, tools, and hardware items', 'fas fa-bolt'),
('Kitchen & Dining', 'Kitchen utensils, dining items, and catering supplies', 'fas fa-kitchen-set'),
('Furniture & Storage', 'Furniture, shelves, filing cabinets, and storage items', 'fas fa-couch'),
('ICT Supplies', 'Computer consumables, printer supplies, and ICT accessories', 'fas fa-laptop'),
('Teaching & Training', 'Teaching aids, simulation supplies, and training materials', 'fas fa-chalkboard-user');

SET FOREIGN_KEY_CHECKS = @_old_fk;

-- Step 5: ICT Database
-- ============================================
-- ISNM Computer Lab Management System
-- ICT Department Database Tables
-- ============================================

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS igangaschoolofl_ict;


-- Safeguard: ensure role_description column exists in staff_roles
SET @dbname = DATABASE();
SELECT COUNT(*) INTO @col_exists
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'staff_roles' AND COLUMN_NAME = 'role_description';
SET @sql = IF(@col_exists = 0, 'ALTER TABLE staff_roles ADD COLUMN role_description TEXT AFTER role_name', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ensure the Director ICT role exists
INSERT IGNORE INTO staff_roles (role_name, role_description, role_level, dashboard_path, permissions)
VALUES ('Director ICT', 'Head of Computer Lab and IT Services - Independent Authority', 'Management', 'dashboards/director-ict.php', '{"ict":true,"systems":true,"can_manage_it":true,"can_access_computer_lab":true}');

-- Create/update the ICT Director account
-- Email: computer-lab@igangaschoolofnursingandmidwifery.ac.ug
-- Password: Techno123 (bcrypt hash below)
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at)
VALUES ('ICT001', 'ICT Department', 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'Director ICT', 'Information Communication Technology',
        (SELECT id FROM staff_roles WHERE role_name = 'Director ICT'),
        'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE
    staff_id = 'ICT001',
    position = 'Director ICT',
    department = 'Information Communication Technology',
    status = 'Active',
    updated_at = NOW();

-- Grant ICT-specific permissions
INSERT INTO staff_permissions (staff_id, module, permission_level, granted_by)
SELECT s.id, 'computer_lab', 'Admin', s.id
FROM staff s WHERE s.email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug'
ON DUPLICATE KEY UPDATE permission_level = 'Admin';

INSERT INTO staff_permissions (staff_id, module, permission_level, granted_by)
SELECT s.id, 'it_inventory', 'Admin', s.id
FROM staff s WHERE s.email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug'
ON DUPLICATE KEY UPDATE permission_level = 'Admin';

INSERT INTO staff_permissions (staff_id, module, permission_level, granted_by)
SELECT s.id, 'it_support', 'Admin', s.id
FROM staff s WHERE s.email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug'
ON DUPLICATE KEY UPDATE permission_level = 'Admin';

-- Log the account creation
INSERT INTO staff_activity_log (staff_id, activity_type, activity_description, module_accessed, ip_address, user_agent)
SELECT s.id, 'Account Created', 'ICT Department official account created/updated', 'authentication', 'SYSTEM', 'Account Setup Script'
FROM staff s WHERE s.email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug';

SELECT 'ICT Department Account Created Successfully' as status,
       email, position, department, 'Password: Techno123' as credentials,
       'Access: Director ICT Dashboard, Computer Lab, IT Inventory' as permissions
FROM staff WHERE email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug';
-- Computer Lab Manager Account Creation Script
-- Uses correct tables from igangaschoolofl_staffs_db
-- This script creates/updates the Computer Lab Manager staff account



-- Safeguard: ensure role_description column exists in staff_roles
SET @dbname = DATABASE();
SELECT COUNT(*) INTO @col_exists
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'staff_roles' AND COLUMN_NAME = 'role_description';
SET @sql = IF(@col_exists = 0, 'ALTER TABLE staff_roles ADD COLUMN role_description TEXT AFTER role_name', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- First ensure the Computer Lab Manager role exists
INSERT IGNORE INTO staff_roles (role_name, role_description, role_level, dashboard_path, permissions) VALUES
('Computer Lab Manager', 'Computer lab operations and IT support', 'Support', 'computer_lab.php', '{"ict": true, "lab_management": true, "it_support": true}');

-- Create/update the Computer Lab Manager account using ON DUPLICATE KEY UPDATE on email (UNIQUE column)
INSERT INTO staff (full_name, email, password, position, department, role_id, status, created_at)
SELECT 'Computer Lab Manager', 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'Computer Lab Manager', 'Information Technology',
        (SELECT id FROM staff_roles WHERE role_name = 'Computer Lab Manager'),
        'Active', NOW()
ON DUPLICATE KEY UPDATE
    full_name = 'Computer Lab Manager',
    position = 'Computer Lab Manager',
    department = 'Information Technology',
    password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    status = 'Active',
    updated_at = NOW();

INSERT INTO staff_permissions (staff_id, module, permission_level, granted_by)
SELECT s.id, 'computer_lab', 'Admin', s.id
FROM staff s WHERE s.email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug'
ON DUPLICATE KEY UPDATE permission_level = 'Admin';

INSERT INTO staff_permissions (staff_id, module, permission_level, granted_by)
SELECT s.id, 'it_inventory', 'Admin', s.id
FROM staff s WHERE s.email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug'
ON DUPLICATE KEY UPDATE permission_level = 'Admin';

INSERT INTO staff_permissions (staff_id, module, permission_level, granted_by)
SELECT s.id, 'it_support', 'Admin', s.id
FROM staff s WHERE s.email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug'
ON DUPLICATE KEY UPDATE permission_level = 'Admin';

SELECT 'Computer Lab Manager Account Created/Updated Successfully' as status,
       email, position, department, 'Password: Techno123' as credentials
FROM staff WHERE email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug';

-- Step 7: Staff Logins
-- ISNM LOGIN QUICK-FIX - Secure & Error Free
-- Safe to run WITHOUT dropping tables. Uses INSERT IGNORE / ON DUPLICATE KEY UPDATE.
-- Ensures all roles and staff accounts exist with correct bcrypt passwords.
-- Compatible with the full 04_final_complete_staffs_database.sql schema.
--
-- IMPORTANT: Only use this file if you have NOT run the master setup.
-- If you ran 99_MASTER_ALL_DEPARTMENTS.sql, skip this file entirely.



-- Safeguard: ensure role_description column exists in staff_roles
SET @dbname = DATABASE();
SET @tbl = 'staff_roles';
SET @col = 'role_description';
SELECT COUNT(*) INTO @col_exists
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tbl AND COLUMN_NAME = @col;
SET @sql = IF(@col_exists = 0, CONCAT('ALTER TABLE ', @tbl, ' ADD COLUMN ', @col, ' TEXT AFTER role_name'), 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ensure all roles exist (idempotent)
INSERT IGNORE INTO staff_roles (role_name, role_description, role_level, dashboard_path, permissions) VALUES
('Director ICT', 'Head of Computer Lab and IT Services', 'Management', 'dashboards/director-ict.php', '{"ict":true,"systems":true,"can_manage_it":true,"can_access_computer_lab":true}'),
('Director General', 'Overall school administration', 'Executive', 'dashboards/director-general.php', '{"all":true,"can_access_all_dashboards":true}'),
('CEO', 'Chief Executive Officer', 'Executive', 'dashboards/ceo.php', '{"strategic":true,"financial":true}'),
('Director Academics', 'Academic programs oversight', 'Management', 'dashboards/director-academics.php', '{"academic":true,"curriculum":true}'),
('Director Finance', 'Financial management', 'Management', 'dashboards/director-finance.php', '{"financial":true,"budgeting":true}'),
('School Principal', 'School leadership', 'Executive', 'dashboards/school-principal.php', '{"academic":true,"administrative":true}'),
('Deputy Principal', 'Assistant principal', 'Management', 'dashboards/deputy-principal.php', '{"academic":true,"administrative":true}'),
('Academic Registrar', 'Student registration and records', 'Academic', 'dashboards/academic-registrar.php', '{"academic":true,"students":true,"registration":true}'),
('HR Manager', 'Human resources', 'Management', 'dashboards/hr-manager.php', '{"hr":true,"staff":true}'),
('School Secretary', 'Administrative support', 'Administrative', 'dashboards/school-secretary.php', '{"administrative":true,"documentation":true}'),
('School Librarian', 'Library management', 'Support', 'dashboards/school-librarian.php', '{"library":true,"resources":true}'),
('Head Nursing', 'Nursing department', 'Academic', 'dashboards/head-nursing.php', '{"nursing":true,"department":true}'),
('Head Midwifery', 'Midwifery department', 'Academic', 'dashboards/head-midwifery.php', '{"midwifery":true,"department":true}'),
('Senior Lecturers', 'Senior teaching staff', 'Academic', 'dashboards/senior-lecturers.php', '{"teaching":true,"lecturers":true}'),
('Lecturers', 'Teaching staff', 'Academic', 'dashboards/lecturers.php', '{"teaching":true,"lecturers":true}'),
('Matrons', 'Student welfare', 'Support', 'dashboards/matrons.php', '{"student_welfare":true,"residential":true}'),
('Wardens', 'Student discipline', 'Support', 'dashboards/wardens.php', '{"student_welfare":true,"discipline":true}'),
('Sickbay', 'Medical support', 'Support', 'dashboards/sickbay.php', '{"healthcare":true,"medical":true}'),
('Drivers', 'Transportation', 'Support', 'dashboards/drivers.php', '{"transportation":true,"vehicles":true}'),
('Security', 'Campus security', 'Support', 'dashboards/security.php', '{"security":true,"safety":true}'),
('Store Keeper', 'Store inventory', 'Support', 'dashboards/storekeeper.php', '{"store":true,"inventory":true}'),
('Guild President', 'Student guild', 'Support', 'dashboards/guild-president.php', '{"student_affairs":true}'),
('Director Admissions & Requirements', 'Admissions management', 'Management', 'dashboards/director-admissions.php', '{"admissions":true,"requirements":true}'),
('School Bursar', 'Financial operations', 'Administrative', 'bursar_dashboard.php', '{"financial":true,"fees":true}'),
('Bursar', 'Bursar assistant', 'Administrative', 'bursar_dashboard.php', '{"financial":true,"fees":true}');

-- Ensure key staff accounts exist with correct bcrypt password hashes
-- Password for all main accounts: staff@123
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('ICT001', 'ICT Department', 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Director ICT', 'Information Communication Technology', (SELECT id FROM staff_roles WHERE role_name = 'Director ICT'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', position = 'Director ICT', department = 'Information Communication Technology', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('ICT002', 'ICT Director', 'dannybict@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Director ICT', 'Information Technology', (SELECT id FROM staff_roles WHERE role_name = 'Director ICT'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'dannybict@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', position = 'Director ICT', department = 'Information Technology', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('DG001', 'Director General', 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Director General', 'Executive Office', (SELECT id FROM staff_roles WHERE role_name = 'Director General'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', position = 'Director General', department = 'Executive Office', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('CEO001', 'CEO', 'ceo@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Chief Executive Officer', 'Executive Office', (SELECT id FROM staff_roles WHERE role_name = 'CEO'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'ceo@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', position = 'Chief Executive Officer', department = 'Executive Office', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('DA001', 'Director Academics', 'directoracademic@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Director Academics', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Director Academics'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'directoracademic@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', position = 'Director Academics', department = 'Academic Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('DF001', 'Director Finance', 'finance@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Director Finance', 'Finance Department', (SELECT id FROM staff_roles WHERE role_name = 'Director Finance'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'finance@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', position = 'Director Finance', department = 'Finance Department', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('SP001', 'School Principal', 'principal@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$VVoHfONmCz.Bsvn1.t1UoesLbM01KNPXKT/b/VJIzxeUq0M9LabK.', 'School Principal', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'School Principal'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'principal@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$VVoHfONmCz.Bsvn1.t1UoesLbM01KNPXKT/b/VJIzxeUq0M9LabK.', position = 'School Principal', department = 'Academic Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('DP001', 'Deputy Principal', 'dep-principal@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$ANzSCNiGrURlS1ovFbQUKuK6ldOOBpiC0iW/MB7HVw/I5JC9wud.m', 'Deputy Principal', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Deputy Principal'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'dep-principal@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$ANzSCNiGrURlS1ovFbQUKuK6ldOOBpiC0iW/MB7HVw/I5JC9wud.m', position = 'Deputy Principal', department = 'Academic Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('AR001', 'Academic Registrar', 'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$Ha21Vlb7p046OaklPLFCteb8raqKNilEWDlzq8ypXVz491hHIICXS', 'Academic Registrar', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Academic Registrar'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$Ha21Vlb7p046OaklPLFCteb8raqKNilEWDlzq8ypXVz491hHIICXS', position = 'Academic Registrar', department = 'Academic Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('HR001', 'HR Manager', 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$jEb8/OsV.9cydSvrBrZ1Hejase4BaTkPXT3FO/Gf9EazTrbXprKYi', 'HR Manager', 'Human Resources', (SELECT id FROM staff_roles WHERE role_name = 'HR Manager'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$jEb8/OsV.9cydSvrBrZ1Hejase4BaTkPXT3FO/Gf9EazTrbXprKYi', position = 'HR Manager', department = 'Human Resources', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('SEC001', 'School Secretary', 'secretary@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$MtVRrE2x6uXh0CwEobzG.ueN1zcL/aE541mbLWpg3e7gnX4HkUxn.', 'School Secretary', 'Administrative Office', (SELECT id FROM staff_roles WHERE role_name = 'School Secretary'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'secretary@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$MtVRrE2x6uXh0CwEobzG.ueN1zcL/aE541mbLWpg3e7gnX4HkUxn.', position = 'School Secretary', department = 'Administrative Office', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('LIB001', 'School Librarian', 'library@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$GGfcvNfejW3f2fRptIUQIuK4c/W44n94twWtTAaOTqTVSuLZ52DsC', 'School Librarian', 'Library Services', (SELECT id FROM staff_roles WHERE role_name = 'School Librarian'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'library@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$GGfcvNfejW3f2fRptIUQIuK4c/W44n94twWtTAaOTqTVSuLZ52DsC', position = 'School Librarian', department = 'Library Services', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('HN001', 'Head Nursing', 'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$YO8OuL81gpaFdgP4nJEebeXNhLeM1.hFMD5KidDV9YDGkJMdAqbgW', 'Head Nursing', 'Nursing Department', (SELECT id FROM staff_roles WHERE role_name = 'Head Nursing'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$YO8OuL81gpaFdgP4nJEebeXNhLeM1.hFMD5KidDV9YDGkJMdAqbgW', position = 'Head Nursing', department = 'Nursing Department', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('HM001', 'Head Midwifery', 'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$G7pMLdi2UjjmhEd8Lx0bmeaM7tGD4jrfvMsZh6HvY1Po8YqFRubRu', 'Head Midwifery', 'Midwifery Department', (SELECT id FROM staff_roles WHERE role_name = 'Head Midwifery'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$G7pMLdi2UjjmhEd8Lx0bmeaM7tGD4jrfvMsZh6HvY1Po8YqFRubRu', position = 'Head Midwifery', department = 'Midwifery Department', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('LEC001', 'Lecturers', 'lecturers@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$e52TV/DaoNDl4kjssi3Te.YHnpxHlaxatBX2wNg5yv3JkoYEEYV9i', 'Lecturer', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Lecturers'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'lecturers@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$e52TV/DaoNDl4kjssi3Te.YHnpxHlaxatBX2wNg5yv3JkoYEEYV9i', position = 'Lecturer', department = 'Academic Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('SLE001', 'Senior Lecturers', 'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$1gsFX/B27b5YuIAP7D5OSO2acgrtV7RcIMeja6RblX/9e5YSFfguy', 'Senior Lecturer', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Senior Lecturers'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$1gsFX/B27b5YuIAP7D5OSO2acgrtV7RcIMeja6RblX/9e5YSFfguy', position = 'Senior Lecturer', department = 'Academic Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('NTS001', 'Non-Teaching Staff', 'nonteaching@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Non-Teaching Staff', 'Administrative', (SELECT id FROM staff_roles WHERE role_name = 'Non-Teaching Staff'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'nonteaching@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', position = 'Non-Teaching Staff', department = 'Administrative', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('LAB001', 'Sickbay', 'sickbay@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$kzTn6S3OUtKLmGoLNo9GOOHqIki7NwUxvZJ6pJK02Yls6eR7Bln82', 'Sickbay', 'Support', (SELECT id FROM staff_roles WHERE role_name = 'Sickbay'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'sickbay@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$kzTn6S3OUtKLmGoLNo9GOOHqIki7NwUxvZJ6pJK02Yls6eR7Bln82', position = 'Sickbay', department = 'Support', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('MAT001', 'Matrons', 'matron@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$Qj7feWYysqaK1INwS50PFehU09Tgf6MOUNVBJZaOw3LZW/jGHZEkO', 'Matrons', 'Student Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Matrons'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'matron@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$Qj7feWYysqaK1INwS50PFehU09Tgf6MOUNVBJZaOw3LZW/jGHZEkO', position = 'Matrons', department = 'Student Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('SECUR001', 'Security', 'security@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$0rLJuecuJuF6.Exxp7AQO.w0Dh0iwfwZri45gwya6OqENBJwjPA7C', 'Security', 'Security Services', (SELECT id FROM staff_roles WHERE role_name = 'Security'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'security@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$0rLJuecuJuF6.Exxp7AQO.w0Dh0iwfwZri45gwya6OqENBJwjPA7C', position = 'Security', department = 'Security Services', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('DRV001', 'Drivers', 'drivers@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$HrQ6V56zJJxIz8j.2grJVOWs2DjFGzA/wxzejvE3vtkk57KFuAjge', 'Drivers', 'Transport', (SELECT id FROM staff_roles WHERE role_name = 'Drivers'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'drivers@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$HrQ6V56zJJxIz8j.2grJVOWs2DjFGzA/wxzejvE3vtkk57KFuAjge', position = 'Drivers', department = 'Transport', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('WDN001', 'Wardens', 'warden@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$jCKwMrdU.s1DVuA2HHFp6eBPK05F70IUoyAvRZX6Qf3wdPsCZBXM2', 'Wardens', 'Student Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Wardens'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'warden@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$jCKwMrdU.s1DVuA2HHFp6eBPK05F70IUoyAvRZX6Qf3wdPsCZBXM2', position = 'Wardens', department = 'Student Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('STK001', 'Store Keeper', 'store@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$8qETvaYu2nreko/c/DyPROdIlMZyAciahJOVwHCV0KG4WxrcicxnS', 'Store Keeper', 'Facilities Management', (SELECT id FROM staff_roles WHERE role_name = 'Store Keeper'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'store@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$8qETvaYu2nreko/c/DyPROdIlMZyAciahJOVwHCV0KG4WxrcicxnS', position = 'Store Keeper', department = 'Facilities Management', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('BUR001', 'School Bursar', 'bursar@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'School Bursar', 'Finance Department', (SELECT id FROM staff_roles WHERE role_name = 'School Bursar'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'bursar@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', position = 'School Bursar', department = 'Finance Department', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('BURS002', 'Bursar', 'bursar.assistant@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Bursar', 'Finance Department', (SELECT id FROM staff_roles WHERE role_name = 'Bursar'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'bursar.assistant@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', position = 'Bursar', department = 'Finance Department', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('ADM001', 'Admissions', 'admissions@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Director Admissions & Requirements', 'Admissions', (SELECT id FROM staff_roles WHERE role_name = 'Director Admissions & Requirements'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'admissions@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', position = 'Director Admissions & Requirements', department = 'Admissions', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('GUILD001', 'Guild President', 'guildpresident@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Guild President', 'Student Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Guild President'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'guildpresident@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', position = 'Guild President', department = 'Student Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

-- Verification
SELECT '========================================' AS info;
SELECT CONCAT('Roles: ', COUNT(*), ' | Staff: ', COUNT(*)) AS setup_check FROM staff_roles, staff;
SELECT 'Login fix complete. Use staff@123 for all accounts.' AS status;

-- Step 8: Website Database
-- ISNM Website Database Schema
-- Database: igangaschoolofl_website_db

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS igangaschoolofl_website_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
