-- ============================================================
-- ISNM NURSING DEPARTMENT DASHBOARD SQL
-- Complete Nursing Department Management System
-- ============================================================

USE igangaschoolofl_staffs_db;

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