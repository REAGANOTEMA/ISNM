-- ISNM ICT Department Official Account Setup
-- Database: igangaschoolofl_staffs_db
-- This script creates/updates the ICT Director staff account

USE igangaschoolofl_staffs_db;

-- Safeguard: ensure role_description column exists in staff_roles
SET @dbname = DATABASE();
SELECT COUNT(*) INTO @col_exists
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'staff_roles' AND COLUMN_NAME = 'role_description';
SET @sql = IF(@col_exists = 0, 'ALTER TABLE staff_roles ADD COLUMN role_description TEXT AFTER role_name', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ensure the Director ICT role exists
INSERT IGNORE INTO staff_roles (role_name, role_description, role_level, dashboard_path, permissions)
VALUES ('Director ICT', 'Head of Computer Lab and IT Services - Independent Authority', 'Management', 'dashboards/director-ict.php', '{"ict":true,"systems":true,"can_manage_it":true,"can_access_computer_lab":true}');

-- Create/update the ICT Director account
-- Email: computer-lab@igangaschoolofnursingandmidwifery.ac.ug
-- Password: Techno123 (bcrypt hash below)
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at)
VALUES ('ICT001', 'ICT Department', 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'Director ICT', 'Information Communication Technology',
        (SELECT id FROM staff_roles WHERE role_name = 'Director ICT'),
        'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE
    staff_id = 'ICT001',
    position = 'Director ICT',
    department = 'Information Communication Technology',
    status = 'Active',
    updated_at = NOW();

-- Grant ICT-specific permissions
INSERT INTO staff_permissions (staff_id, module, permission_level, granted_by)
SELECT s.id, 'computer_lab', 'Admin', s.id
FROM staff s WHERE s.email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug'
ON DUPLICATE KEY UPDATE permission_level = 'Admin';

INSERT INTO staff_permissions (staff_id, module, permission_level, granted_by)
SELECT s.id, 'it_inventory', 'Admin', s.id
FROM staff s WHERE s.email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug'
ON DUPLICATE KEY UPDATE permission_level = 'Admin';

INSERT INTO staff_permissions (staff_id, module, permission_level, granted_by)
SELECT s.id, 'it_support', 'Admin', s.id
FROM staff s WHERE s.email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug'
ON DUPLICATE KEY UPDATE permission_level = 'Admin';

-- Log the account creation
INSERT INTO staff_activity_log (staff_id, activity_type, activity_description, module_accessed, ip_address, user_agent)
SELECT s.id, 'Account Created', 'ICT Department official account created/updated', 'authentication', 'SYSTEM', 'Account Setup Script'
FROM staff s WHERE s.email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug';

SELECT 'ICT Department Account Created Successfully' as status,
       email, position, department, 'Password: Techno123' as credentials,
       'Access: Director ICT Dashboard, Computer Lab, IT Inventory' as permissions
FROM staff WHERE email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug';