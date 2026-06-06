-- Computer Lab Manager Account Creation Script
-- Uses correct tables from igangaschoolofl_staffs_db

USE igangaschoolofl_staffs_db;

INSERT INTO staff (full_name, email, password, position, department, role_id, status, created_at)
SELECT 'Computer Lab Manager', 'computerlab@igangaschoolofnursingandmidwifery.ac.ug',
       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
       'Computer Lab Manager', 'Information Communication Technology',
       id, 'Active', NOW()
FROM staff_roles WHERE role_name = 'Director ICT'
ON DUPLICATE KEY UPDATE
    position = 'Computer Lab Manager',
    department = 'Information Communication Technology',
    status = 'Active',
    updated_at = NOW();

INSERT INTO staff_permissions (staff_id, module, permission_level, granted_by)
SELECT s.id, 'computer_lab', 'Admin', s.id
FROM staff s WHERE s.email = 'computerlab@igangaschoolofnursingandmidwifery.ac.ug'
ON DUPLICATE KEY UPDATE permission_level = 'Admin';

INSERT INTO staff_permissions (staff_id, module, permission_level, granted_by)
SELECT s.id, 'it_inventory', 'Admin', s.id
FROM staff s WHERE s.email = 'computerlab@igangaschoolofnursingandmidwifery.ac.ug'
ON DUPLICATE KEY UPDATE permission_level = 'Admin';

INSERT INTO staff_permissions (staff_id, module, permission_level, granted_by)
SELECT s.id, 'it_support', 'Admin', s.id
FROM staff s WHERE s.email = 'computerlab@igangaschoolofnursingandmidwifery.ac.ug'
ON DUPLICATE KEY UPDATE permission_level = 'Admin';

INSERT INTO staff_activity_log (staff_id, activity_type, activity_description, module_accessed, ip_address, user_agent)
SELECT s.id, 'Account Created', 'Computer Lab Manager account created', 'authentication', 'SYSTEM', 'Setup Script'
FROM staff s WHERE s.email = 'computerlab@igangaschoolofnursingandmidwifery.ac.ug';

SELECT 'Computer Lab Manager Account Created Successfully' as status,
       email, position, department, 'Password: LabManager123' as credentials
FROM staff WHERE email = 'computerlab@igangaschoolofnursingandmidwifery.ac.ug';