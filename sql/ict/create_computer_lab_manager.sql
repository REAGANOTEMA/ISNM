-- Computer Lab Manager Account Creation Script
-- Uses correct tables from igangaschoolofl_staffs_db
-- This script creates/updates the Computer Lab Manager staff account

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

-- First ensure the Computer Lab Manager role exists
INSERT IGNORE INTO staff_roles (role_name, role_description, role_level, dashboard_path, permissions) VALUES
('Computer Lab Manager', 'Computer lab operations and IT support', 'Support', 'computer_lab.php', '{"ict": true, "lab_management": true, "it_support": true}');

-- Create/update the Computer Lab Manager account using ON DUPLICATE KEY UPDATE on email (UNIQUE column)
INSERT INTO staff (full_name, email, password, position, department, role_id, status, created_at)
SELECT 'Computer Lab Manager', 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'Computer Lab Manager', 'Information Technology',
        (SELECT id FROM staff_roles WHERE role_name = 'Computer Lab Manager'),
        'Active', NOW()
ON DUPLICATE KEY UPDATE
    full_name = 'Computer Lab Manager',
    position = 'Computer Lab Manager',
    department = 'Information Technology',
    password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    status = 'Active',
    updated_at = NOW();

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

SELECT 'Computer Lab Manager Account Created/Updated Successfully' as status,
       email, position, department, 'Password: Techno123' as credentials
FROM staff WHERE email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug';
