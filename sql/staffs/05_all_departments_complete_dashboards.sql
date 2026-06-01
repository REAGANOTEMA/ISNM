-- ============================================================
-- ISNM COMPLETE ALL DEPARTMENTS DASHBOARDS SQL
-- Comprehensive SQL Schema with all department dashboards
-- Includes staff accounts with proper emails and passwords
-- ============================================================

USE igangaschoolofl_staffs_db;

-- Ensure required roles exist (prevent NULL role_id when inserting staff)
INSERT IGNORE INTO staff_roles (role_name, role_description, role_level, dashboard_path, permissions) VALUES
('Director Admissions & Requirements', 'Admissions and requirements management', 'Management', 'dashboards/director-admissions.php', '{"admissions": true, "can_manage_applications": true}'),
('Guild President', 'Student leadership representative', 'Student', 'dashboards/guild-president.php', '{"student_leader": true, "can_access_student_affairs": true}');

-- ============================================================
-- 1. INSERT ALL DEPARTMENT STAFF ACCOUNTS WITH PROPER CREDENTIALS
-- Email format: department@igangaschoolofnursingandmidwifery.ac.ug
-- Password format: department@isnm
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
        password_changed,
        is_first_login,
        created_at
) VALUES
('DG001','Director General','directorgeneral@igangaschoolofnursingandmidwifery.ac.ug','DorisJoy2026', '+256701000001','Director General','Executive Office',(SELECT id FROM staff_roles WHERE role_name = 'Director General' LIMIT 1),'Active',CURDATE(),FALSE,TRUE,NOW()),
('CEO001','Chief Executive Officer','ceo@igangaschoolofnursingandmidwifery.ac.ug','Lovely2God', '+256701000002','Chief Executive Officer','Executive Office',(SELECT id FROM staff_roles WHERE role_name = 'CEO' LIMIT 1),'Active',CURDATE(),FALSE,TRUE,NOW()),
('DA001','Director Academics','directoracademic@igangaschoolofnursingandmidwifery.ac.ug','Stephen123', '+256701000003','Director Academics','Academic Affairs',(SELECT id FROM staff_roles WHERE role_name = 'Director Academics' LIMIT 1),'Active',CURDATE(),FALSE,TRUE,NOW()),
('DICT001','Director ICT','dannybict@igangaschoolofnursingandmidwifery.ac.ug','Lovely2God', '+256701000004','Director ICT','Information Technology',(SELECT id FROM staff_roles WHERE role_name = 'Director ICT' LIMIT 1),'Active',CURDATE(),FALSE,TRUE,NOW()),
('DF001','Director Finance','finance@igangaschoolofnursingandmidwifery.ac.ug','DorisJoy2026', '+256701000005','Director Finance','Finance Department',(SELECT id FROM staff_roles WHERE role_name = 'Director Finance' LIMIT 1),'Active',CURDATE(),FALSE,TRUE,NOW()),
('PRINC001','School Principal','principal@igangaschoolofnursingandmidwifery.ac.ug','isnm2026', '+256701000006','School Principal','Academic Affairs',(SELECT id FROM staff_roles WHERE role_name = 'School Principal' LIMIT 1),'Active',CURDATE(),FALSE,TRUE,NOW()),
('DEPUT001','Deputy Principal','dep-principal@igangaschoolofnursingandmidwifery.ac.ug','Isnm2026', '+256701000007','Deputy Principal','Academic Affairs',(SELECT id FROM staff_roles WHERE role_name = 'Deputy Principal' LIMIT 1),'Active',CURDATE(),FALSE,TRUE,NOW()),
('BURS001','School Bursar','bursar@igangaschoolofnursingandmidwifery.ac.ug','DorisJoy2026', '+256701000008','School Bursar','Finance Department',(SELECT id FROM staff_roles WHERE role_name = 'School Bursar' LIMIT 1),'Active',CURDATE(),FALSE,TRUE,NOW()),
('ADMS001','Director Admissions & Requirements','admissions@igangaschoolofnursingandmidwifery.ac.ug','2268926931', '+256701000009','Director Admissions & Requirements','Academic Affairs',(SELECT id FROM staff_roles WHERE role_name = 'Director Admissions & Requirements' LIMIT 1),'Active',CURDATE(),FALSE,TRUE,NOW()),
('REG001','Academic Registrar','academicregistrar@igangaschoolofnursingandmidwifery.ac.ug','Lovely2God', '+256701000010','Academic Registrar','Academic Affairs',(SELECT id FROM staff_roles WHERE role_name = 'Academic Registrar' LIMIT 1),'Active',CURDATE(),FALSE,TRUE,NOW()),
('HR001','HR Manager','hr-manager@igangaschoolofnursingandmidwifery.ac.ug','Alexis2026', '+256701000011','HR Manager','Human Resources',(SELECT id FROM staff_roles WHERE role_name = 'HR Manager' LIMIT 1),'Active',CURDATE(),FALSE,TRUE,NOW()),
('SEC001','School Secretary','secretary@igangaschoolofnursingandmidwifery.ac.ug','Lovely2God', '+256701000012','School Secretary','Administrative Support',(SELECT id FROM staff_roles WHERE role_name = 'School Secretary' LIMIT 1),'Active',CURDATE(),FALSE,TRUE,NOW()),
('LIB001','School Librarian','library@igangaschoolofnursingandmidwifery.ac.ug','isnm2026', '+256701000013','School Librarian','Library Services',(SELECT id FROM staff_roles WHERE role_name = 'School Librarian' LIMIT 1),'Active',CURDATE(),FALSE,TRUE,NOW()),
('NUR001','Head of Nursing','nursing-dep@igangaschoolofnursingandmidwifery.ac.ug','isnm4life', '+256701000014','Head of Nursing','Nursing Department',(SELECT id FROM staff_roles WHERE role_name = 'Head Nursing' LIMIT 1),'Active',CURDATE(),FALSE,TRUE,NOW()),
('MID001','Head of Midwifery','midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug','Life2save', '+256701000015','Head of Midwifery','Midwifery Department',(SELECT id FROM staff_roles WHERE role_name = 'Head Midwifery' LIMIT 1),'Active',CURDATE(),FALSE,TRUE,NOW()),
('SL001','Senior Lecturer','senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug','isnm2026', '+256701000016','Senior Lecturers','Academic Affairs',(SELECT id FROM staff_roles WHERE role_name = 'Senior Lecturers' LIMIT 1),'Active',CURDATE(),FALSE,TRUE,NOW()),
('LEC001','Lecturer','lecturers@igangaschoolofnursingandmidwifery.ac.ug','Isnm4life', '+256701000017','Lecturers','Academic Affairs',(SELECT id FROM staff_roles WHERE role_name = 'Lecturers' LIMIT 1),'Active',CURDATE(),FALSE,TRUE,NOW()),
('MAT001','Matron','matron@igangaschoolofnursingandmidwifery.ac.ug','Isnm2026', '+256701000018','Matrons','Student Affairs',(SELECT id FROM staff_roles WHERE role_name = 'Matrons' LIMIT 1),'Active',CURDATE(),FALSE,TRUE,NOW()),
('WAR001','Warden','warden@igangaschoolofnursingandmidwifery.ac.ug','Lovely2God', '+256701000019','Warden','Student Affairs',(SELECT id FROM staff_roles WHERE role_name = 'Wardens' LIMIT 1),'Active',CURDATE(),FALSE,TRUE,NOW()),
('SICK001','Sickbay','sickbay@igangaschoolofnursingandmidwifery.ac.ug','isnm2026', '+256701000020','Sickbay','Support',(SELECT id FROM staff_roles WHERE role_name = 'Sickbay' LIMIT 1),'Active',CURDATE(),FALSE,TRUE,NOW()),
('DRV001','Driver','drivers@igangaschoolofnursingandmidwifery.ac.ug','isnm4life', '+256701000021','Drivers','Support',(SELECT id FROM staff_roles WHERE role_name = 'Drivers' LIMIT 1),'Active',CURDATE(),FALSE,TRUE,NOW()),
('SECUR001','Security Officer','security@igangaschoolofnursingandmidwifery.ac.ug','safty1st', '+256701000022','Security','Security Services',(SELECT id FROM staff_roles WHERE role_name = 'Security' LIMIT 1),'Active',CURDATE(),FALSE,TRUE,NOW()),
('STK001','Store Keeper','store@igangaschoolofnursingandmidwifery.ac.ug','Isnm4life', '+256701000023','Store Keeper','Support',(SELECT id FROM staff_roles WHERE role_name = 'Store Keeper' LIMIT 1),'Active',CURDATE(),FALSE,TRUE,NOW()),
('GUILD001','Guild President','guildpresident@igangaschoolofnursingandmidwifery.ac.ug','isnm4life', '+256701000024','Guild President','Student Affairs',(SELECT id FROM staff_roles WHERE role_name = 'Guild President' LIMIT 1),'Active',CURDATE(),FALSE,TRUE,NOW());

-- ============================================================
-- 2. STUDENT DATA INTEGRATION - UNIVERSAL STUDENT TABLE
-- Supports loading from multiple Excel files in students_data folder
-- ============================================================

-- Main student profiles table with all required fields
CREATE TABLE IF NOT EXISTS universal_student_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_number VARCHAR(50) UNIQUE NOT NULL,
    national_id VARCHAR(50) UNIQUE,
    index_number VARCHAR(50) UNIQUE,
    registration_number VARCHAR(50) UNIQUE,
    
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    
    email VARCHAR(100),
    phone VARCHAR(20),
    
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    
    program VARCHAR(100) NOT NULL,
    program_type ENUM('Certificate', 'Diploma', 'Degree') DEFAULT 'Diploma',
    intake_set VARCHAR(20),
    intake_date DATE,
    year_of_study INT DEFAULT 1,
    semester VARCHAR(50) DEFAULT 'Semester 1',
    academic_year VARCHAR(20),
    
    address TEXT,
    district VARCHAR(100),
    county VARCHAR(100),
    sub_county VARCHAR(100),
    parish VARCHAR(100),
    village VARCHAR(100),
    
    guardian_name VARCHAR(200),
    guardian_phone VARCHAR(20),
    guardian_relationship VARCHAR(50),
    guardian_address TEXT,
    
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    emergency_contact_relationship VARCHAR(50),
    
    nationality VARCHAR(50) DEFAULT 'Ugandan',
    religion VARCHAR(50),
    marital_status ENUM('Single', 'Married', 'Divorced', 'Widowed') DEFAULT 'Single',
    
    photo_path VARCHAR(500),
    photo_uploaded BOOLEAN DEFAULT FALSE,
    photo_upload_date TIMESTAMP NULL,
    
    status ENUM('Active', 'Inactive', 'Graduated', 'Suspended', 'Withdrawn', 'Transferred') DEFAULT 'Active',
    enrollment_status ENUM('Full Time', 'Part Time', 'Distance') DEFAULT 'Full Time',
    
    gpa DECIMAL(3,2) DEFAULT 0.00,
    cgpa DECIMAL(3,2) DEFAULT 0.00,
    
    created_by INT,
    updated_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_student_number (student_number),
    INDEX idx_national_id (national_id),
    INDEX idx_index_number (index_number),
    INDEX idx_full_name (full_name),
    INDEX idx_program (program),
    INDEX idx_status (status),
    INDEX idx_intake_set (intake_set),
    INDEX idx_academic_year (academic_year)
);

-- Student photos table for profile pictures
CREATE TABLE IF NOT EXISTS student_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    old_photo_path VARCHAR(500),
    new_photo_path VARCHAR(500),
    photo_action ENUM('upload', 'update', 'delete', 'print') NOT NULL,
    action_by INT,
    action_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (student_id) REFERENCES universal_student_profiles(id) ON DELETE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_action_date (action_date)
);

-- Student search index for fast searching
CREATE TABLE IF NOT EXISTS student_search_index (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    search_field VARCHAR(50) NOT NULL,
    search_value VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES universal_student_profiles(id) ON DELETE CASCADE,
    INDEX idx_search_field (search_field),
    INDEX idx_search_value (search_value),
    FULLTEXT idx_search_full (search_value)
);

-- ============================================================
-- 3. STUDENT DATA IMPORT TRACKING
-- Tracks which Excel files have been imported
-- ============================================================

CREATE TABLE IF NOT EXISTS student_data_imports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    source_file VARCHAR(255) NOT NULL,
    intake_set VARCHAR(50),
    total_records INT,
    imported_records INT,
    skipped_records INT,
    import_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    imported_by INT,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    error_log TEXT,
    INDEX idx_source_file (source_file),
    INDEX idx_import_date (import_date),
    INDEX idx_status (status)
);

-- ============================================================
-- 4. STUDENT PROFILE EDITING LOG
-- Tracks all profile changes
-- ============================================================

CREATE TABLE IF NOT EXISTS student_profile_edits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    field_changed VARCHAR(100) NOT NULL,
    old_value TEXT,
    new_value TEXT,
    edit_reason TEXT,
    edited_by INT,
    edit_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    action_type ENUM('edit', 'photo_update', 'photo_delete', 'photo_print') DEFAULT 'edit',
    FOREIGN KEY (student_id) REFERENCES universal_student_profiles(id) ON DELETE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_edit_date (edit_date),
    INDEX idx_field_changed (field_changed)
);

-- ============================================================
-- 5. STUDENT REPORTS AND PRINTING
-- Supports generating and printing reports
-- ============================================================

CREATE TABLE IF NOT EXISTS student_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_number VARCHAR(50) UNIQUE NOT NULL,
    report_type ENUM('student_list', 'single_profile', 'bulk_export', 'id_cards', 'transcripts', 'class_list', 'search_results') NOT NULL,
    report_format ENUM('pdf', 'excel', 'csv', 'html') DEFAULT 'pdf',
    generated_by INT,
    generation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    report_data LONGTEXT,
    file_path VARCHAR(500),
    file_size INT,
    records_count INT,
    status ENUM('generated', 'printed', 'downloaded', 'archived') DEFAULT 'generated',
    print_count INT DEFAULT 0,
    last_printed TIMESTAMP NULL,
    FOREIGN KEY (generated_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_report_type (report_type),
    INDEX idx_generation_date (generation_date)
);

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
    sp.academic_year,
    sp.gpa,
    sp.cgpa,
    sp.status,
    sp.district,
    sp.guardian_name,
    sp.guardian_phone,
    sp.photo_path as current_photo,
    COALESCE(sp.photo_uploaded, FALSE) as has_photo,
    sr.dashboard_path as staff_dashboard
FROM universal_student_profiles sp
LEFT JOIN staff s ON s.id = sp.created_by
LEFT JOIN staff_roles sr ON s.role_id = sr.id;

-- ============================================================
-- 7. VIEW FOR ALL STUDENTS (FOR CROSS-DEPARTMENT ACCESS)
-- ============================================================

CREATE OR REPLACE VIEW all_students_view AS
SELECT 
    sp.*,
    CASE 
        WHEN sp.photo_uploaded = TRUE THEN CONCAT('Available: ', sp.photo_path)
        ELSE 'No Photo Available'
    END as photo_status,
    CASE 
        WHEN sp.gpa >= 3.5 THEN 'Excellent'
        WHEN sp.gpa >= 3.0 THEN 'Very Good'
        WHEN sp.gpa >= 2.5 THEN 'Good'
        WHEN sp.gpa >= 2.0 THEN 'Satisfactory'
        ELSE 'Needs Improvement'
    END as academic_standing
FROM universal_student_profiles sp;

-- ============================================================
-- 8. PROCEDURES FOR STUDENT SEARCH AND MANAGEMENT
-- ============================================================

-- Ensure no conflicting procedure exists before creating (prevents #1304)
DROP PROCEDURE IF EXISTS get_all_students;

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
        id, student_number, full_name, program, intake_set, 
        year_of_study, status, gpa, photo_path
    FROM universal_student_profiles
    WHERE (p_search_term IS NULL OR 
           full_name LIKE CONCAT('%', p_search_term, '%') OR
           student_number LIKE CONCAT('%', p_search_term, '%') OR
           index_number LIKE CONCAT('%', p_search_term, '%') OR
           national_id LIKE CONCAT('%', p_search_term, '%'))
      AND (p_program IS NULL OR program = p_program)
      AND (p_intake_set IS NULL OR intake_set = p_intake_set)
      AND (p_status IS NULL OR status = p_status)
    ORDER BY full_name
    LIMIT p_limit;
END //

-- Get all students from all intake sets
CREATE PROCEDURE get_all_students()
BEGIN
    SELECT 
        intake_set,
        COUNT(*) as total_students,
        COUNT(CASE WHEN photo_uploaded = TRUE THEN 1 END) as students_with_photos,
        AVG(gpa) as average_gpa
    FROM universal_student_profiles
    GROUP BY intake_set
    ORDER BY intake_set DESC;
END //

-- Update student photo
DROP PROCEDURE IF EXISTS update_student_photo//
CREATE PROCEDURE update_student_photo(
    IN p_student_id INT,
    IN p_new_photo_path VARCHAR(500),
    IN p_updated_by INT
)
BEGIN
    UPDATE universal_student_profiles 
    SET photo_path = p_new_photo_path,
        photo_uploaded = TRUE,
        photo_upload_date = NOW(),
        updated_by = p_updated_by,
        updated_at = NOW()
    WHERE id = p_student_id;
    
    INSERT INTO student_photos (student_id, new_photo_path, photo_action, action_by, notes)
    VALUES (p_student_id, p_new_photo_path, 'upload', p_updated_by, 'Photo updated');
END //

-- Delete student photo
CREATE PROCEDURE delete_student_photo(
    IN p_student_id INT,
    IN p_deleted_by INT
)
BEGIN
    UPDATE universal_student_profiles 
    SET photo_path = NULL,
        photo_uploaded = FALSE,
        updated_by = p_deleted_by,
        updated_at = NOW()
    WHERE id = p_student_id;
    
    INSERT INTO student_photos (student_id, photo_action, action_by, notes)
    VALUES (p_student_id, 'delete', p_deleted_by, 'Photo deleted');
END //

-- Print student profile (logs the print action)
CREATE PROCEDURE print_student_profile(
    IN p_student_id INT,
    IN p_printed_by INT
)
BEGIN
    INSERT INTO student_profile_edits (student_id, field_changed, action_type, edited_by)
    VALUES (p_student_id, 'print', 'photo_print', p_printed_by);
END //

DELIMITER ;

-- ============================================================
-- 9. TRIGGERS FOR AUTOMATED ACTIONS
-- ============================================================

DELIMITER //

-- Auto-update full_name when first/last name changes
CREATE TRIGGER update_full_name
BEFORE UPDATE ON universal_student_profiles
FOR EACH ROW
BEGIN
    SET NEW.full_name = CONCAT(NEW.first_name, ' ', NEW.last_name);
END //

-- Log search activity
-- Note: MySQL does not support AFTER SELECT triggers
-- Search logging should be implemented at the application level

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
     'Drivers', 'Security', 'Store Keeper'
 );

-- ============================================================
-- 11. INSERT DEFAULT INSTITUTE SETTINGS
-- ============================================================

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