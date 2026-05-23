-- ============================================================
-- ISNM HR MANAGER DASHBOARD SQL
-- Complete Human Resources Management System
-- ============================================================

USE igangaschoolofl_staffs_db;

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
    sp.profile_picture,
    sp.bio,
    sp.qualifications,
    CASE 
        WHEN s.locked_until > NOW() THEN 'Locked'
        WHEN s.login_attempts >= 5 THEN 'Warning'
        ELSE 'Active'
    END as account_status
FROM staff s
LEFT JOIN staff_profiles sp ON s.id = sp.staff_id
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
    COALESCE(st.training_count, 0) as training_completed
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
) st ON st.id = st.staff_id
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