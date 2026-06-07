-- Computer Lab Manager Account Creation Script
-- Uses correct tables from igangaschoolofl_staffs_db
-- Email: computer-lab@igangaschoolofnursingandmidwifery.ac.ug
-- Password: Techno123

USE igangaschoolofl_staffs_db;

-- First ensure the Computer Lab Manager role exists
INSERT IGNORE INTO staff_roles (role_name, role_description, role_level, dashboard_path, permissions) VALUES
('Computer Lab Manager', 'Computer lab operations and IT support', 'Support', 'computer_lab.php', '{"ict": true, "lab_management": true, "it_support": true}');

INSERT INTO staff (full_name, email, password, position, department, role_id, status, created_at)
SELECT 'Computer Lab Manager', 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug',
       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',  -- Temporary hash, will be updated
       'Computer Lab Manager', 'Information Technology',
       id, 'Active', NOW()
FROM staff_roles WHERE role_name = 'Computer Lab Manager'
ON DUPLICATE KEY UPDATE
    position = 'Computer Lab Manager',
    department = 'Information Technology',
    status = 'Active',
    updated_at = NOW();

-- Update password to match the plaintext password for testing
UPDATE staff SET password = 'Techno123', password_changed = FALSE, is_first_login = TRUE WHERE email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug';

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

SELECT 'Computer Lab Manager Account Created Successfully' as status,
       email, position, department, 'Password: Techno123' as credentials
FROM staff WHERE email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug';