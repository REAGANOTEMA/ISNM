-- ============================================================
-- ISNM COMPLETE ALL DEPARTMENTS DASHBOARDS SQL
-- Staff-specific tables only - student data is in students database
-- Run AFTER 04_final_complete_staffs_database.sql
-- ============================================================

USE igangaschoolofl_staffs_db;

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
    COALESCE(s.full_name, TRIM(CONCAT(s.first_name, ' ', COALESCE(s.other_name, ''), ' ', s.surname))) as full_name,
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
    s.religion,
    s.address,
    s.district,
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