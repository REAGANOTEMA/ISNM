-- ISNM ICT Department Official Account Setup
-- Database: igangaschoolofl_staffs_db
-- This script creates/updates the ICT Director staff account

-- Safeguard: ensure role_description column exists in staff_roles
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS add_ictd_role_description_col()
BEGIN
    DECLARE CONTINUE HANDLER FOR 1060 BEGIN END;
    ALTER TABLE igangaschoolofl_staffs_db.staff_roles ADD COLUMN role_description TEXT AFTER role_name;
END//
DELIMITER ;
CALL add_ictd_role_description_col();
DROP PROCEDURE IF EXISTS add_ictd_role_description_col;

-- Ensure the Director ICT role exists
INSERT IGNORE INTO igangaschoolofl_staffs_db.staff_roles (role_name, role_description, role_level, dashboard_path, permissions)
VALUES ('Director ICT', 'Head of Computer Lab and IT Services - Independent Authority', 'Management', 'dashboards/director-ict.php', '{"ict":true,"systems":true,"can_manage_it":true,"can_access_computer_lab":true}');

-- Create/update the ICT Director account
-- Email: computer-lab@igangaschoolofnursingandmidwifery.ac.ug
-- Password: Techno123 (bcrypt hash below)
INSERT INTO igangaschoolofl_staffs_db.staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at)
VALUES ('ICT001', 'ICT Department', 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'Director ICT', 'Information Communication Technology',
        (SELECT id FROM igangaschoolofl_staffs_db.staff_roles WHERE role_name = 'Director ICT'),
        'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE
    staff_id = 'ICT001',
    position = 'Director ICT',
    department = 'Information Communication Technology',
    status = 'Active',
    updated_at = NOW();

-- Grant ICT-specific permissions
INSERT INTO igangaschoolofl_staffs_db.staff_permissions (staff_id, module, permission_level, granted_by)
SELECT s.id, 'computer_lab', 'Admin', s.id
FROM igangaschoolofl_staffs_db.staff s WHERE s.email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug'
ON DUPLICATE KEY UPDATE permission_level = 'Admin';

INSERT INTO igangaschoolofl_staffs_db.staff_permissions (staff_id, module, permission_level, granted_by)
SELECT s.id, 'it_inventory', 'Admin', s.id
FROM igangaschoolofl_staffs_db.staff s WHERE s.email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug'
ON DUPLICATE KEY UPDATE permission_level = 'Admin';

INSERT INTO igangaschoolofl_staffs_db.staff_permissions (staff_id, module, permission_level, granted_by)
SELECT s.id, 'it_support', 'Admin', s.id
FROM igangaschoolofl_staffs_db.staff s WHERE s.email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug'
ON DUPLICATE KEY UPDATE permission_level = 'Admin';

-- Log the account creation
INSERT INTO igangaschoolofl_staffs_db.staff_activity_log (staff_id, activity_type, activity_description, module_accessed, ip_address, user_agent)
SELECT s.id, 'Account Created', 'ICT Department official account created/updated', 'authentication', 'SYSTEM', 'Account Setup Script'
FROM igangaschoolofl_staffs_db.staff s WHERE s.email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug';

SELECT 'ICT Department Account Created Successfully' as status,
       email, position, department, 'Password: Techno123' as credentials,
       'Access: Director ICT Dashboard, Computer Lab, IT Inventory' as permissions
FROM igangaschoolofl_staffs_db.staff WHERE email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug';
