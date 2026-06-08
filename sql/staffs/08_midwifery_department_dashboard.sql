-- ============================================================
-- ISNM MIDWYIFERY DEPARTMENT DASHBOARD SQL
-- Complete Midwifery Department Management System
-- ============================================================

USE igangaschoolofl_staffs_db;

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