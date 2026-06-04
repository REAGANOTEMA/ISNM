-- ============================================================
-- STUDENT MANAGEMENT PROCEDURES AND PERMISSIONS
-- Allows Secretary and Director ICT to add/manage students
-- Database: igangaschoolofl_staffs_db
-- ============================================================

USE `igangaschoolofl_staffs_db`;

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
    staff_id VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
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

-- Ensure all roles have correct dashboard paths
UPDATE staff_roles SET dashboard_path = 'bursar_dashboard.php' WHERE role_name IN ('School Bursar', 'Bursar');
UPDATE staff_roles SET dashboard_path = 'dashboards/school-secretary.php' WHERE role_name IN ('School Secretary', 'Secretary');
UPDATE staff_roles SET dashboard_path = 'dashboards/director-admissions.php' WHERE role_name = 'Director Admissions & Requirements';
UPDATE staff_roles SET dashboard_path = 'dashboards/sickbay.php' WHERE role_name = 'Sickbay';

-- Grant dashboard access to Student Management for Secretary
INSERT IGNORE INTO staff_dashboard_access (staff_id, dashboard_path, access_level, granted_by)
SELECT s.id, 'dashboards/student-management.php', 'Full', 1
FROM staff s
JOIN staff_roles sr ON s.role_id = sr.id
WHERE sr.role_name = 'School Secretary';

-- Grant dashboard access to Student Management for Director ICT
INSERT IGNORE INTO staff_dashboard_access (staff_id, dashboard_path, access_level, granted_by)
SELECT s.id, 'dashboards/student-management.php', 'Full', 1
FROM staff s
JOIN staff_roles sr ON s.role_id = sr.id
WHERE sr.role_name = 'Director ICT';

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
