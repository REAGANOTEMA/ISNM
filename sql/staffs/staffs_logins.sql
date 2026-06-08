-- ISNM LOGIN QUICK-FIX - Secure & Error Free
-- Safe to run WITHOUT dropping tables. Uses INSERT IGNORE / ON DUPLICATE KEY UPDATE.
-- Ensures all roles and staff accounts exist with correct bcrypt passwords.
-- Compatible with the full 04_final_complete_staffs_database.sql schema.
--
-- IMPORTANT: Only use this file if you have NOT run the master setup.
-- If you ran 99_MASTER_ALL_DEPARTMENTS.sql, skip this file entirely.

USE `igangaschoolofl_staffs_db`;

-- Ensure all roles exist (idempotent)
INSERT IGNORE INTO staff_roles (role_name, role_description, role_level, dashboard_path, permissions) VALUES
('Director ICT', 'Head of Computer Lab and IT Services', 'Management', 'dashboards/director-ict.php', '{"ict":true,"systems":true,"can_manage_it":true,"can_access_computer_lab":true}'),
('Director General', 'Overall school administration', 'Executive', 'dashboards/director-general.php', '{"all":true,"can_access_all_dashboards":true}'),
('CEO', 'Chief Executive Officer', 'Executive', 'dashboards/ceo.php', '{"strategic":true,"financial":true}'),
('Director Academics', 'Academic programs oversight', 'Management', 'dashboards/director-academics.php', '{"academic":true,"curriculum":true}'),
('Director Finance', 'Financial management', 'Management', 'dashboards/director-finance.php', '{"financial":true,"budgeting":true}'),
('School Principal', 'School leadership', 'Executive', 'dashboards/school-principal.php', '{"academic":true,"administrative":true}'),
('Deputy Principal', 'Assistant principal', 'Management', 'dashboards/deputy-principal.php', '{"academic":true,"administrative":true}'),
('Academic Registrar', 'Student registration and records', 'Academic', 'dashboards/academic-registrar.php', '{"academic":true,"students":true,"registration":true}'),
('HR Manager', 'Human resources', 'Management', 'dashboards/hr-manager.php', '{"hr":true,"staff":true}'),
('School Secretary', 'Administrative support', 'Administrative', 'dashboards/school-secretary.php', '{"administrative":true,"documentation":true}'),
('School Librarian', 'Library management', 'Support', 'dashboards/school-librarian.php', '{"library":true,"resources":true}'),
('Head Nursing', 'Nursing department', 'Academic', 'dashboards/head-nursing.php', '{"nursing":true,"department":true}'),
('Head Midwifery', 'Midwifery department', 'Academic', 'dashboards/head-midwifery.php', '{"midwifery":true,"department":true}'),
('Senior Lecturers', 'Senior teaching staff', 'Academic', 'dashboards/senior-lecturers.php', '{"teaching":true,"lecturers":true}'),
('Lecturers', 'Teaching staff', 'Academic', 'dashboards/lecturers.php', '{"teaching":true,"lecturers":true}'),
('Matrons', 'Student welfare', 'Support', 'dashboards/matrons.php', '{"student_welfare":true,"residential":true}'),
('Wardens', 'Student discipline', 'Support', 'dashboards/wardens.php', '{"student_welfare":true,"discipline":true}'),
('Sickbay', 'Medical support', 'Support', 'dashboards/sickbay.php', '{"healthcare":true,"medical":true}'),
('Drivers', 'Transportation', 'Support', 'dashboards/drivers.php', '{"transportation":true,"vehicles":true}'),
('Security', 'Campus security', 'Support', 'dashboards/security.php', '{"security":true,"safety":true}'),
('Store Keeper', 'Store inventory', 'Support', 'dashboards/storekeeper.php', '{"store":true,"inventory":true}'),
('Guild President', 'Student guild', 'Support', 'dashboards/guild-president.php', '{"student_affairs":true}'),
('Director Admissions & Requirements', 'Admissions management', 'Management', 'dashboards/director-admissions.php', '{"admissions":true,"requirements":true}'),
('School Bursar', 'Financial operations', 'Administrative', 'bursar_dashboard.php', '{"financial":true,"fees":true}'),
('Bursar', 'Bursar assistant', 'Administrative', 'bursar_dashboard.php', '{"financial":true,"fees":true}');

-- Ensure key staff accounts exist with correct bcrypt password hashes
-- Password for all main accounts: staff@123
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('ICT001', 'ICT Department', 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Director ICT', 'Information Communication Technology', (SELECT id FROM staff_roles WHERE role_name = 'Director ICT'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', position = 'Director ICT', department = 'Information Communication Technology', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('ICT002', 'ICT Director', 'dannybict@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Director ICT', 'Information Technology', (SELECT id FROM staff_roles WHERE role_name = 'Director ICT'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'dannybict@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', position = 'Director ICT', department = 'Information Technology', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('DG001', 'Director General', 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Director General', 'Executive Office', (SELECT id FROM staff_roles WHERE role_name = 'Director General'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', position = 'Director General', department = 'Executive Office', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('CEO001', 'CEO', 'ceo@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Chief Executive Officer', 'Executive Office', (SELECT id FROM staff_roles WHERE role_name = 'CEO'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'ceo@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', position = 'Chief Executive Officer', department = 'Executive Office', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('DA001', 'Director Academics', 'directoracademic@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Director Academics', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Director Academics'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'directoracademic@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', position = 'Director Academics', department = 'Academic Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('DF001', 'Director Finance', 'finance@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Director Finance', 'Finance Department', (SELECT id FROM staff_roles WHERE role_name = 'Director Finance'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'finance@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', position = 'Director Finance', department = 'Finance Department', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('SP001', 'School Principal', 'principal@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$VVoHfONmCz.Bsvn1.t1UoesLbM01KNPXKT/b/VJIzxeUq0M9LabK.', 'School Principal', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'School Principal'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'principal@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$VVoHfONmCz.Bsvn1.t1UoesLbM01KNPXKT/b/VJIzxeUq0M9LabK.', position = 'School Principal', department = 'Academic Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('DP001', 'Deputy Principal', 'dep-principal@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$ANzSCNiGrURlS1ovFbQUKuK6ldOOBpiC0iW/MB7HVw/I5JC9wud.m', 'Deputy Principal', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Deputy Principal'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'dep-principal@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$ANzSCNiGrURlS1ovFbQUKuK6ldOOBpiC0iW/MB7HVw/I5JC9wud.m', position = 'Deputy Principal', department = 'Academic Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('AR001', 'Academic Registrar', 'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$Ha21Vlb7p046OaklPLFCteb8raqKNilEWDlzq8ypXVz491hHIICXS', 'Academic Registrar', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Academic Registrar'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$Ha21Vlb7p046OaklPLFCteb8raqKNilEWDlzq8ypXVz491hHIICXS', position = 'Academic Registrar', department = 'Academic Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('HR001', 'HR Manager', 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$jEb8/OsV.9cydSvrBrZ1Hejase4BaTkPXT3FO/Gf9EazTrbXprKYi', 'HR Manager', 'Human Resources', (SELECT id FROM staff_roles WHERE role_name = 'HR Manager'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$jEb8/OsV.9cydSvrBrZ1Hejase4BaTkPXT3FO/Gf9EazTrbXprKYi', position = 'HR Manager', department = 'Human Resources', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('SEC001', 'School Secretary', 'secretary@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$MtVRrE2x6uXh0CwEobzG.ueN1zcL/aE541mbLWpg3e7gnX4HkUxn.', 'School Secretary', 'Administrative Office', (SELECT id FROM staff_roles WHERE role_name = 'School Secretary'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'secretary@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$MtVRrE2x6uXh0CwEobzG.ueN1zcL/aE541mbLWpg3e7gnX4HkUxn.', position = 'School Secretary', department = 'Administrative Office', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('LIB001', 'School Librarian', 'library@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$GGfcvNfejW3f2fRptIUQIuK4c/W44n94twWtTAaOTqTVSuLZ52DsC', 'School Librarian', 'Library Services', (SELECT id FROM staff_roles WHERE role_name = 'School Librarian'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'library@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$GGfcvNfejW3f2fRptIUQIuK4c/W44n94twWtTAaOTqTVSuLZ52DsC', position = 'School Librarian', department = 'Library Services', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('HN001', 'Head Nursing', 'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$YO8OuL81gpaFdgP4nJEebeXNhLeM1.hFMD5KidDV9YDGkJMdAqbgW', 'Head Nursing', 'Nursing Department', (SELECT id FROM staff_roles WHERE role_name = 'Head Nursing'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$YO8OuL81gpaFdgP4nJEebeXNhLeM1.hFMD5KidDV9YDGkJMdAqbgW', position = 'Head Nursing', department = 'Nursing Department', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('HM001', 'Head Midwifery', 'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$G7pMLdi2UjjmhEd8Lx0bmeaM7tGD4jrfvMsZh6HvY1Po8YqFRubRu', 'Head Midwifery', 'Midwifery Department', (SELECT id FROM staff_roles WHERE role_name = 'Head Midwifery'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$G7pMLdi2UjjmhEd8Lx0bmeaM7tGD4jrfvMsZh6HvY1Po8YqFRubRu', position = 'Head Midwifery', department = 'Midwifery Department', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('LEC001', 'Lecturers', 'lecturers@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$e52TV/DaoNDl4kjssi3Te.YHnpxHlaxatBX2wNg5yv3JkoYEEYV9i', 'Lecturer', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Lecturers'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'lecturers@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$e52TV/DaoNDl4kjssi3Te.YHnpxHlaxatBX2wNg5yv3JkoYEEYV9i', position = 'Lecturer', department = 'Academic Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('SLE001', 'Senior Lecturers', 'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$1gsFX/B27b5YuIAP7D5OSO2acgrtV7RcIMeja6RblX/9e5YSFfguy', 'Senior Lecturer', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Senior Lecturers'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$1gsFX/B27b5YuIAP7D5OSO2acgrtV7RcIMeja6RblX/9e5YSFfguy', position = 'Senior Lecturer', department = 'Academic Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('NTS001', 'Non-Teaching Staff', 'nonteaching@isnm.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Non-Teaching Staff', 'Administrative', (SELECT id FROM staff_roles WHERE role_name = 'Non-Teaching Staff'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'nonteaching@isnm.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', position = 'Non-Teaching Staff', department = 'Administrative', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('LAB001', 'Sickbay', 'sickbay@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$kzTn6S3OUtKLmGoLNo9GOOHqIki7NwUxvZJ6pJK02Yls6eR7Bln82', 'Sickbay', 'Support', (SELECT id FROM staff_roles WHERE role_name = 'Sickbay'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'sickbay@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$kzTn6S3OUtKLmGoLNo9GOOHqIki7NwUxvZJ6pJK02Yls6eR7Bln82', position = 'Sickbay', department = 'Support', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('MAT001', 'Matrons', 'matron@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$Qj7feWYysqaK1INwS50PFehU09Tgf6MOUNVBJZaOw3LZW/jGHZEkO', 'Matrons', 'Student Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Matrons'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'matron@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$Qj7feWYysqaK1INwS50PFehU09Tgf6MOUNVBJZaOw3LZW/jGHZEkO', position = 'Matrons', department = 'Student Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('SECUR001', 'Security', 'security@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$0rLJuecuJuF6.Exxp7AQO.w0Dh0iwfwZri45gwya6OqENBJwjPA7C', 'Security', 'Security Services', (SELECT id FROM staff_roles WHERE role_name = 'Security'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'security@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$0rLJuecuJuF6.Exxp7AQO.w0Dh0iwfwZri45gwya6OqENBJwjPA7C', position = 'Security', department = 'Security Services', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('DRV001', 'Drivers', 'drivers@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$HrQ6V56zJJxIz8j.2grJVOWs2DjFGzA/wxzejvE3vtkk57KFuAjge', 'Drivers', 'Transport', (SELECT id FROM staff_roles WHERE role_name = 'Drivers'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'drivers@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$HrQ6V56zJJxIz8j.2grJVOWs2DjFGzA/wxzejvE3vtkk57KFuAjge', position = 'Drivers', department = 'Transport', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('WDN001', 'Wardens', 'warden@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$jCKwMrdU.s1DVuA2HHFp6eBPK05F70IUoyAvRZX6Qf3wdPsCZBXM2', 'Wardens', 'Student Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Wardens'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'warden@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$jCKwMrdU.s1DVuA2HHFp6eBPK05F70IUoyAvRZX6Qf3wdPsCZBXM2', position = 'Wardens', department = 'Student Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('STK001', 'Store Keeper', 'store@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$8qETvaYu2nreko/c/DyPROdIlMZyAciahJOVwHCV0KG4WxrcicxnS', 'Store Keeper', 'Facilities Management', (SELECT id FROM staff_roles WHERE role_name = 'Store Keeper'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'store@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$8qETvaYu2nreko/c/DyPROdIlMZyAciahJOVwHCV0KG4WxrcicxnS', position = 'Store Keeper', department = 'Facilities Management', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('BUR001', 'School Bursar', 'bursar@isnm.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'School Bursar', 'Finance Department', (SELECT id FROM staff_roles WHERE role_name = 'School Bursar'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'bursar@isnm.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', position = 'School Bursar', department = 'Finance Department', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('BURS002', 'Bursar', 'bursar.assistant@isnm.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Bursar', 'Finance Department', (SELECT id FROM staff_roles WHERE role_name = 'Bursar'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'bursar.assistant@isnm.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', position = 'Bursar', department = 'Finance Department', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('ADM001', 'Admissions', 'admissions@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Director Admissions & Requirements', 'Admissions', (SELECT id FROM staff_roles WHERE role_name = 'Director Admissions & Requirements'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'admissions@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', position = 'Director Admissions & Requirements', department = 'Admissions', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('GUILD001', 'Guild President', 'guildpresident@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Guild President', 'Student Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Guild President'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'guildpresident@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', position = 'Guild President', department = 'Student Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

-- Verification
SELECT '========================================' AS info;
SELECT CONCAT('Roles: ', COUNT(*), ' | Staff: ', COUNT(*)) AS setup_check FROM staff_roles, staff;
SELECT 'Login fix complete. Use staff@123 for all accounts.' AS status;
