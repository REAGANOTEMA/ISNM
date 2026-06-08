-- ============================================================
-- ISNM ACADEMIC REGISTRAR DASHBOARD SQL
-- Complete Academic Records Management System
-- ============================================================

USE igangaschoolofl_staffs_db;

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