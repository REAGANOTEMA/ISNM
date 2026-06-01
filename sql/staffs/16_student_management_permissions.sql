-- ============================================================
-- STUDENT MANAGEMENT PROCEDURES AND PERMISSIONS
-- Allows Secretary and Director ICT to add/manage students
-- Database: igangaschoolofl_staffs_db
-- ============================================================

USE igangaschoolofl_staffs_db;

-- ============================================================
-- 1. ENSURE STAFF HAVE PERMISSIONS TO MANAGE STUDENTS
-- ============================================================

-- Ensure the dashboard access table exists (some installs may not have it)
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
        student_number, registration_number, national_student_id_number,
        first_name, surname, other_name, email, phone,
        program, current_year, set_name, intake_date,
        date_of_birth, gender, nationality, address,
        guardian_name, guardian_phone,
        emergency_contact_name, emergency_contact_phone,
        status, password, is_first_login, password_changed
    ) VALUES (
        p_student_number, p_registration_number, p_national_id,
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
        id, student_number, registration_number, 
        CONCAT(first_name, ' ', surname) as full_name,
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
        id, student_number, registration_number, 
        CONCAT(first_name, ' ', surname) as full_name,
        email, phone, program, current_year, set_name, status,
        created_at
    FROM igangaschoolofl_students_db.students
    WHERE 
        student_number LIKE CONCAT('%', p_search_term, '%')
        OR registration_number LIKE CONCAT('%', p_search_term, '%')
        OR CONCAT(first_name, ' ', surname) LIKE CONCAT('%', p_search_term, '%')
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
