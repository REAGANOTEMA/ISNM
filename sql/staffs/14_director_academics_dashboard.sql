-- ============================================================
-- ISNM DIRECTOR ACADEMICS DASHBOARD SQL
-- Complete Academic Leadership Management System
-- ============================================================

USE igangaschoolofl_staffs_db;

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
        program_code,
        COUNT(*) as total_students,
        COUNT(CASE WHEN status = 'Active' THEN 1 END) as active_students,
        COUNT(CASE WHEN status = 'Graduated' THEN 1 END) as graduated_students,
        AVG(gpa) as average_gpa
    FROM universal_student_profiles
    WHERE academic_year = p_academic_year 
      AND (p_program_code IS NULL OR program = p_program_code)
    GROUP BY program_code;
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