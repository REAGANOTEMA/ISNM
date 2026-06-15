-- Computer Lab Manager Account Creation Script
-- Uses correct tables from igangaschoolofl_staffs_db
-- This script creates/updates the Computer Lab Manager staff account

-- Safeguard: ensure role_description column exists in staff_roles
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS add_clm_role_description_col()
BEGIN
    DECLARE CONTINUE HANDLER FOR 1060 BEGIN END;
    ALTER TABLE igangaschoolofl_staffs_db.staff_roles ADD COLUMN role_description TEXT AFTER role_name;
END//
DELIMITER ;
CALL add_clm_role_description_col();
DROP PROCEDURE IF EXISTS add_clm_role_description_col;

-- First ensure the Computer Lab Manager role exists
INSERT IGNORE INTO igangaschoolofl_staffs_db.staff_roles (role_name, role_description, role_level, dashboard_path, permissions) VALUES
('Computer Lab Manager', 'Computer lab operations and IT support', 'Support', 'computer_lab.php', '{"ict": true, "lab_management": true, "it_support": true}');

-- Create/update the Computer Lab Manager account using ON DUPLICATE KEY UPDATE on email (UNIQUE column)
INSERT INTO igangaschoolofl_staffs_db.staff (staff_id, full_name, email, password, position, department, role_id, status, created_at)
SELECT 'CL001', 'Computer Lab Manager', 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'Computer Lab Manager', 'Information Technology',
        (SELECT id FROM igangaschoolofl_staffs_db.staff_roles WHERE role_name = 'Computer Lab Manager'),
        'Active', NOW()
ON DUPLICATE KEY UPDATE
    staff_id = 'CL001',
    full_name = 'Computer Lab Manager',
    position = 'Computer Lab Manager',
    department = 'Information Technology',
    password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    status = 'Active',
    updated_at = NOW();

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

SELECT 'Computer Lab Manager Account Created/Updated Successfully' as status,
       email, position, department, 'Password: Techno123' as credentials
FROM igangaschoolofl_staffs_db.staff WHERE email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug';
