-- ISNM LOGIN FIX - All Accounts with Correct Passwords
-- Safe to run WITH or WITHOUT master setup.
-- Uses INSERT IGNORE / ON DUPLICATE KEY UPDATE so it won't break anything.
-- Ensures all roles and staff accounts exist with their correct bcrypt passwords.
--
-- NOTE: This file can be run on top of the master setup to fix passwords.
-- It will update existing accounts and insert missing ones.

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

-- ============================================================
-- STAFF ACCOUNTS WITH CORRECT PASSWORDS
-- Password hashes generated with password_hash() (PASSWORD_DEFAULT)
-- ============================================================

-- Computer Lab (ICT)
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('ICT001', 'ICT Department', 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$DMMHJfmRKhlq0p27/ESo1eSGDO5/9f/TZgk4.k2A3.7x9I2QpX.Tu', 'Director ICT', 'Information Communication Technology', (SELECT id FROM staff_roles WHERE role_name = 'Director ICT'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$DMMHJfmRKhlq0p27/ESo1eSGDO5/9f/TZgk4.k2A3.7x9I2QpX.Tu', position = 'Director ICT', department = 'Information Communication Technology', status = 'Active', is_first_login = TRUE, updated_at = NOW();

-- Computer Director (Danny BICT)
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('ICT002', 'ICT Director', 'dannybict@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$Lytn/lu03eHWkq6BJ1U31.ZLR/04IPi.c7RczJT0OHIvHlE7vGhKi', 'Director ICT', 'Information Technology', (SELECT id FROM staff_roles WHERE role_name = 'Director ICT'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'dannybict@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$Lytn/lu03eHWkq6BJ1U31.ZLR/04IPi.c7RczJT0OHIvHlE7vGhKi', position = 'Director ICT', department = 'Information Technology', status = 'Active', is_first_login = TRUE, updated_at = NOW();

-- Director General
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('DG001', 'Director General', 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$eiGSi0YZjyhpnlJZDIPjX.DPwGZyGtXtPkgsiYEpiffv4gZd87ugK', 'Director General', 'Executive Office', (SELECT id FROM staff_roles WHERE role_name = 'Director General'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$eiGSi0YZjyhpnlJZDIPjX.DPwGZyGtXtPkgsiYEpiffv4gZd87ugK', position = 'Director General', department = 'Executive Office', status = 'Active', is_first_login = TRUE, updated_at = NOW();

-- CEO
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('CEO001', 'CEO', 'ceo@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$MlEwPe6dr4v6T1xrqVKt..Uot0x/K84/ezvyO7S.NBTALBYvuvSfe', 'Chief Executive Officer', 'Executive Office', (SELECT id FROM staff_roles WHERE role_name = 'CEO'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'ceo@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$MlEwPe6dr4v6T1xrqVKt..Uot0x/K84/ezvyO7S.NBTALBYvuvSfe', position = 'Chief Executive Officer', department = 'Executive Office', status = 'Active', is_first_login = TRUE, updated_at = NOW();

-- Director Academics
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('DA001', 'Director Academics', 'directoracademic@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$GIZKHiTHTjQUFrMvaPhg8O0UF0bQDa1ioooaN8idtY2mrHM/umDXC', 'Director Academics', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Director Academics'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'directoracademic@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$GIZKHiTHTjQUFrMvaPhg8O0UF0bQDa1ioooaN8idtY2mrHM/umDXC', position = 'Director Academics', department = 'Academic Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

-- Director Finance
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('DF001', 'Director Finance', 'finance@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$ahdRmP2iiZLwj0p7vA83guFL3QA0VF12selmxJ89eoniowhpsTez2', 'Director Finance', 'Finance Department', (SELECT id FROM staff_roles WHERE role_name = 'Director Finance'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'finance@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$ahdRmP2iiZLwj0p7vA83guFL3QA0VF12selmxJ89eoniowhpsTez2', position = 'Director Finance', department = 'Finance Department', status = 'Active', is_first_login = TRUE, updated_at = NOW();

-- School Principal
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('SP001', 'School Principal', 'principal@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$yxJd25DvGLAGCZnizad4.OJ0VzFcv0N.a22Lhgs1QrHQGmjQAkRrS', 'School Principal', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'School Principal'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'principal@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$yxJd25DvGLAGCZnizad4.OJ0VzFcv0N.a22Lhgs1QrHQGmjQAkRrS', position = 'School Principal', department = 'Academic Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();
 
-- Deputy Principal
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('DP001', 'Deputy Principal', 'dep-principal@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$.P2.VeVT1xA5nL1f6pLtPuTOOqKrNGtzF1BbKCKCNmMu1VcWVP29G', 'Deputy Principal', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Deputy Principal'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'dep-principal@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$.P2.VeVT1xA5nL1f6pLtPuTOOqKrNGtzF1BbKCKCNmMu1VcWVP29G', position = 'Deputy Principal', department = 'Academic Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

-- Academic Registrar
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('AR001', 'Academic Registrar', 'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$tjp9vRwJgHDx3y/pjsaGcOKvODnIuS5AacGKa8thS.xEqmZjqE.8K', 'Academic Registrar', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Academic Registrar'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$tjp9vRwJgHDx3y/pjsaGcOKvODnIuS5AacGKa8thS.xEqmZjqE.8K', position = 'Academic Registrar', department = 'Academic Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

-- HR Manager
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('HR001', 'HR Manager', 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$F5AukHOfo738zDyQAD6ml.gzg1X7dRFwpHjEvKN15YcdMMA2RwECe', 'HR Manager', 'Human Resources', (SELECT id FROM staff_roles WHERE role_name = 'HR Manager'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$F5AukHOfo738zDyQAD6ml.gzg1X7dRFwpHjEvKN15YcdMMA2RwECe', position = 'HR Manager', department = 'Human Resources', status = 'Active', is_first_login = TRUE, updated_at = NOW();

-- School Secretary
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('SEC001', 'School Secretary', 'secretary@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$Uti21aFQR4BFiykX7gItnOLPiITWAidDYdoKx1mRUEvrmmOnILmvu', 'School Secretary', 'Administrative Office', (SELECT id FROM staff_roles WHERE role_name = 'School Secretary'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'secretary@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$Uti21aFQR4BFiykX7gItnOLPiITWAidDYdoKx1mRUEvrmmOnILmvu', position = 'School Secretary', department = 'Administrative Office', status = 'Active', is_first_login = TRUE, updated_at = NOW();

-- School Librarian
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('LIB001', 'School Librarian', 'library@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$qusMuMofxGj9zvKH.8ZELew0iy6fInIvO9SdYW1RIdaRl.kLRby6C', 'School Librarian', 'Library Services', (SELECT id FROM staff_roles WHERE role_name = 'School Librarian'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'library@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$qusMuMofxGj9zvKH.8ZELew0iy6fInIvO9SdYW1RIdaRl.kLRby6C', position = 'School Librarian', department = 'Library Services', status = 'Active', is_first_login = TRUE, updated_at = NOW();

-- Head Nursing
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('HN001', 'Head Nursing', 'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$oWr7NXab4WjcDfXzEVeYu.Fnx.guIVsYTtxCwvnMA18SNmx3fhmJC', 'Head Nursing', 'Nursing Department', (SELECT id FROM staff_roles WHERE role_name = 'Head Nursing'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$oWr7NXab4WjcDfXzEVeYu.Fnx.guIVsYTtxCwvnMA18SNmx3fhmJC', position = 'Head Nursing', department = 'Nursing Department', status = 'Active', is_first_login = TRUE, updated_at = NOW();

-- Head Midwifery
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('HM001', 'Head Midwifery', 'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$fIuQ1u9j0CcI7vmqDvDY0.BSitSJvGAG4WZgu.5/uWPvDlR34H3wC', 'Head Midwifery', 'Midwifery Department', (SELECT id FROM staff_roles WHERE role_name = 'Head Midwifery'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$fIuQ1u9j0CcI7vmqDvDY0.BSitSJvGAG4WZgu.5/uWPvDlR34H3wC', position = 'Head Midwifery', department = 'Midwifery Department', status = 'Active', is_first_login = TRUE, updated_at = NOW();

-- Lecturers
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('LEC001', 'Lecturers', 'lecturers@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$Chsg431DMWv1C/CA0kuKwOUBolMfAT3C8gXsYYmLb9ywuMUec9OQS', 'Lecturer', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Lecturers'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'lecturers@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$Chsg431DMWv1C/CA0kuKwOUBolMfAT3C8gXsYYmLb9ywuMUec9OQS', position = 'Lecturer', department = 'Academic Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

-- Senior Lecturers
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('SLE001', 'Senior Lecturers', 'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$bWrDY1uX.CDk7s/3emXmpOkJDJuJkKS0jNuy90Rfq6Z5etNOTP.Ou', 'Senior Lecturer', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Senior Lecturers'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$bWrDY1uX.CDk7s/3emXmpOkJDJuJkKS0jNuy90Rfq6Z5etNOTP.Ou', position = 'Senior Lecturer', department = 'Academic Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

-- Sickbay
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('LAB001', 'Sickbay', 'sickbay@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$ZcCtv7ecJKUjQe3rqVoehuZNz/lwd6flBjnjFWqyWsHsiGqTHAiGm', 'Sickbay', 'Support', (SELECT id FROM staff_roles WHERE role_name = 'Sickbay'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'sickbay@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$ZcCtv7ecJKUjQe3rqVoehuZNz/lwd6flBjnjFWqyWsHsiGqTHAiGm', position = 'Sickbay', department = 'Support', status = 'Active', is_first_login = TRUE, updated_at = NOW();

-- Matrons
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('MAT001', 'Matrons', 'matron@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$ef1NUTZNKGH3lBY4z1Ci3uVqoJlppcsGrDeG1HHsTFxtFkjay2yS2', 'Matrons', 'Student Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Matrons'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'matron@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$ef1NUTZNKGH3lBY4z1Ci3uVqoJlppcsGrDeG1HHsTFxtFkjay2yS2', position = 'Matrons', department = 'Student Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

-- Security
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('SECUR001', 'Security', 'security@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$T350Cp4oSaFYEv0YuwY9mOQyIsvTRgVow98/kxuOiiVbUXBWsANNi', 'Security', 'Security Services', (SELECT id FROM staff_roles WHERE role_name = 'Security'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'security@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$T350Cp4oSaFYEv0YuwY9mOQyIsvTRgVow98/kxuOiiVbUXBWsANNi', position = 'Security', department = 'Security Services', status = 'Active', is_first_login = TRUE, updated_at = NOW();

-- Drivers
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('DRV001', 'Drivers', 'drivers@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$BiD7I3vrSBPspALT0lNnnelpblIqWdXzkFEh1momE2l4r4.fdOF46', 'Drivers', 'Transport', (SELECT id FROM staff_roles WHERE role_name = 'Drivers'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'drivers@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$BiD7I3vrSBPspALT0lNnnelpblIqWdXzkFEh1momE2l4r4.fdOF46', position = 'Drivers', department = 'Transport', status = 'Active', is_first_login = TRUE, updated_at = NOW();

-- Wardens
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('WDN001', 'Wardens', 'warden@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$dbPX4zRG9liMJVx8.PGuGeHA9JrrJ/VYZUtYAf/0nT0vlzEQ3Os1m', 'Wardens', 'Student Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Wardens'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'warden@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$dbPX4zRG9liMJVx8.PGuGeHA9JrrJ/VYZUtYAf/0nT0vlzEQ3Os1m', position = 'Wardens', department = 'Student Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

-- Store Keeper
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('STK001', 'Store Keeper', 'store@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$tTtWq3poisOvsnCoZ01nyuV77d5nccNO9FEEF1gqhktGeFftYvNvK', 'Store Keeper', 'Facilities Management', (SELECT id FROM staff_roles WHERE role_name = 'Store Keeper'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'store@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$tTtWq3poisOvsnCoZ01nyuV77d5nccNO9FEEF1gqhktGeFftYvNvK', position = 'Store Keeper', department = 'Facilities Management', status = 'Active', is_first_login = TRUE, updated_at = NOW();

-- Guild President
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('GUILD001', 'Guild President', 'guildpresident@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$HMbwAHXSxqJv6e9UJ2WXIOp1ohG8w.Gov8x/PCe6lmv.gNFepLuRq', 'Guild President', 'Student Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Guild President'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'guildpresident@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$HMbwAHXSxqJv6e9UJ2WXIOp1ohG8w.Gov8x/PCe6lmv.gNFepLuRq', position = 'Guild President', department = 'Student Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

-- Director Admissions & Requirements
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('ADM001', 'Director Admissions & Requirements', 'admissions@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$p9ZOBOvgYPcdBSqA03mcXeGDWNHXlOM7PxKKD//7CKz9lY8E3u7Ly', 'Director Admissions & Requirements', 'Admissions', (SELECT id FROM staff_roles WHERE role_name = 'Director Admissions & Requirements'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'admissions@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$p9ZOBOvgYPcdBSqA03mcXeGDWNHXlOM7PxKKD//7CKz9lY8E3u7Ly', position = 'Director Admissions & Requirements', department = 'Admissions', status = 'Active', is_first_login = TRUE, updated_at = NOW();

-- School Bursar (staff table login)
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('BUR001', 'School Bursar', 'bursar@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$U61BKsKqMuX1LajK/sSOme3yETx/qnoNw75CxEiBr7mX8pd.922v.', 'School Bursar', 'Finance Department', (SELECT id FROM staff_roles WHERE role_name = 'School Bursar'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'bursar@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$U61BKsKqMuX1LajK/sSOme3yETx/qnoNw75CxEiBr7mX8pd.922v.', position = 'School Bursar', department = 'Finance Department', status = 'Active', is_first_login = TRUE, updated_at = NOW();

-- Bursar Assistant
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('BURS002', 'Bursar', 'bursar.assistant@isnm.ac.ug', '$2y$10$U61BKsKqMuX1LajK/sSOme3yETx/qnoNw75CxEiBr7mX8pd.922v.', 'Bursar', 'Finance Department', (SELECT id FROM staff_roles WHERE role_name = 'Bursar'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'bursar.assistant@isnm.ac.ug', password = '$2y$10$U61BKsKqMuX1LajK/sSOme3yETx/qnoNw75CxEiBr7mX8pd.922v.', position = 'Bursar', department = 'Finance Department', status = 'Active', is_first_login = TRUE, updated_at = NOW();

-- ============================================================
-- VERIFICATION
-- ============================================================
SELECT '========================================' AS info;
SELECT CONCAT('Roles: ', COUNT(*), ' | Staff: ', COUNT(*)) AS setup_check FROM staff_roles, staff;
SELECT 'Login fix complete. Use the passwords provided by the administrator.' AS status;
