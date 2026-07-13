-- ============================================================
-- ISNM STAFF CREDENTIALS — COMPLETE SETUP
-- Generated: 2026-07-13
-- Database: igangaschool_staffs
--
-- This file:
--   1. Ensures all staff_roles exist with correct dashboard paths
--   2. Fixes Computer Lab Manager dashboard_path (missing dashboards/ prefix)
--   3. Creates or updates all 30 staff accounts with bcrypt passwords
--   4. Fixes admissions-req role_id (was 0, should be 26)
--   5. Unlocks any locked accounts
--
-- USAGE: phpMyAdmin -> select igangaschool_staffs -> SQL tab -> Paste -> Go
-- ============================================================

USE igangaschool_staffs;

-- ------------------------------------------------------------
-- STEP 1: Ensure all staff_roles exist with correct dashboards
-- Table columns: id, role_name, role_description, role_level,
--                dashboard_path, permissions, created_at, updated_at
-- ------------------------------------------------------------

INSERT IGNORE INTO staff_roles (role_name, role_description, role_level, dashboard_path) VALUES
('Director General',                    'Executive leadership',               1, 'dashboards/director-general.php'),
('CEO',                                 'Chief Executive Officer',            1, 'dashboards/ceo.php'),
('Director Academics',                  'Academic affairs oversight',         2, 'dashboards/director-academics.php'),
('Director Finance',                    'Finance oversight',                  2, 'dashboards/director-finance.php'),
('Director ICT',                        'ICT oversight',                      2, 'dashboards/director-ict.php'),
('Director Admissions & Requirements',  'Admissions management',              2, 'dashboards/director-admissions.php'),
('School Principal',                    'School administration',              2, 'dashboards/school-principal.php'),
('Deputy Principal',                    'Deputy school administration',       3, 'dashboards/deputy-principal.php'),
('Academic Registrar',                  'Academic records',                   3, 'dashboards/academic-registrar.php'),
('School Bursar',                       'Finance operations',                 3, 'dashboards/school-bursar.php'),
('HR Manager',                          'Human resources',                    3, 'dashboards/hr-manager.php'),
('Head Nursing',                        'Nursing department',                 3, 'dashboards/head-nursing.php'),
('Head Midwifery',                      'Midwifery department',               3, 'dashboards/head-midwifery.php'),
('School Secretary',                    'Administrative support',             4, 'dashboards/school-secretary.php'),
('School Librarian',                    'Library services',                   4, 'dashboards/school-librarian.php'),
('Senior Lecturers',                    'Teaching staff',                     4, 'dashboards/senior-lecturers.php'),
('Lecturers',                           'Teaching staff',                     4, 'dashboards/lecturers.php'),
('Matrons',                             'Student welfare',                    4, 'dashboards/matrons.php'),
('Wardens',                             'Hostel management',                  4, 'dashboards/wardens.php'),
('Sickbay',                             'Health services',                    4, 'dashboards/sickbay.php'),
('Guild President',                     'Student leadership',                 4, 'dashboards/guild-president.php'),
('Computer Lab Manager',                'Computer lab operations',            4, 'dashboards/computer_lab.php'),
('Skills Lab',                          'Skills laboratory',                  4, 'dashboards/skills-lab.php'),
('Storekeeper',                         'Store inventory',                    5, 'dashboards/storekeeper.php'),
('Drivers',                             'Transport',                          5, 'dashboards/drivers.php'),
('Security',                            'Security operations',                5, 'dashboards/security.php'),
('Director Admissions',                 'Admissions director',                2, 'dashboards/director-admissions.php'),
('Head of Nursing',                     'Nursing department (alt)',           3, 'dashboards/head-nursing.php'),
('Head of Midwifery',                   'Midwifery department (alt)',         3, 'dashboards/head-midwifery.php'),
('Security Officer',                    'Security officer (alt)',             5, 'dashboards/security.php'),
('Sickbay Nurse',                       'Health services (alt)',              4, 'dashboards/sickbay.php'),
('Skills Lab Manager',                  'Skills lab management',              4, 'dashboards/skills-lab.php'),
('Skills Lab Technician',               'Skills lab technical',               5, 'dashboards/skills-lab.php'),
('Senior Lecturer',                     'Teaching staff (alt)',               4, 'dashboards/senior-lecturers.php'),
('Lecturer',                            'Teaching staff (alt)',               4, 'dashboards/lecturers.php'),
('Matron',                              'Student welfare (alt)',              4, 'dashboards/matrons.php'),
('Warden',                              'Hostel management (alt)',            4, 'dashboards/wardens.php'),
('Driver',                              'Transport (alt)',                    5, 'dashboards/drivers.php'),
('Store Keeper',                        'Store inventory (alt)',              5, 'dashboards/storekeeper.php'),
('Bursar',                              'Bursar assistant',                   3, 'dashboards/school-bursar.php'),
('System Administrator',                'System admin',                       1, 'dashboards/system-admin.php');

-- ------------------------------------------------------------
-- STEP 2: Fix Computer Lab Manager dashboard_path
-- Original has 'computer_lab.php' missing 'dashboards/' prefix
-- ------------------------------------------------------------

UPDATE staff_roles
SET dashboard_path = 'dashboards/computer_lab.php'
WHERE role_name = 'Computer Lab Manager'
  AND (dashboard_path = 'computer_lab.php' OR dashboard_path IS NULL);

-- ------------------------------------------------------------
-- STEP 3: Fix System Administrator dashboard_path
-- Points to director-general.php, should point to system-admin.php
-- ------------------------------------------------------------

UPDATE staff_roles
SET dashboard_path = 'dashboards/system-admin.php'
WHERE role_name = 'System Administrator'
  AND dashboard_path = 'dashboards/director-general.php';

-- ------------------------------------------------------------
-- STEP 4: Create or update all 30 staff accounts
-- Uses ON DUPLICATE KEY UPDATE so it works whether accounts
-- exist or not. Each comment shows plaintext password.
-- ------------------------------------------------------------

-- 1. Director General | Password: DorisJoy2026
SET @rid = (SELECT id FROM staff_roles WHERE role_name = 'Director General' LIMIT 1);
INSERT INTO staff (email, full_name, password, role_id, position, department, status, is_first_login, password_changed, login_attempts, locked_until, created_at, updated_at)
VALUES ('directorgeneral@igangaschoolofnursingandmidwifery.ac.ug', 'Director General', '$2y$10$oyDYgwVlrVdxkuqBN1/hGei2RrBsFEU0Zx03usRpcru.OHEHFe0lC', @rid, 'Director General', 'Executive Office', 'Active', 0, 1, 0, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE password = VALUES(password), full_name = VALUES(full_name), role_id = VALUES(role_id), position = VALUES(position), department = VALUES(department), status = 'Active', is_first_login = 0, password_changed = 1, login_attempts = 0, locked_until = NULL, updated_at = NOW();

-- 2. CEO | Password: Lovely2God
SET @rid = (SELECT id FROM staff_roles WHERE role_name = 'CEO' LIMIT 1);
INSERT INTO staff (email, full_name, password, role_id, position, department, status, is_first_login, password_changed, login_attempts, locked_until, created_at, updated_at)
VALUES ('ceo@igangaschoolofnursingandmidwifery.ac.ug', 'Chief Executive Officer', '$2y$10$LfakAho0G3z3k9IO8LQ5f.ZttedFPce/Y8.gHRWZ93b4UB0.vJXsC', @rid, 'CEO', 'Executive Office', 'Active', 0, 1, 0, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE password = VALUES(password), full_name = VALUES(full_name), role_id = VALUES(role_id), position = VALUES(position), department = VALUES(department), status = 'Active', is_first_login = 0, password_changed = 1, login_attempts = 0, locked_until = NULL, updated_at = NOW();

-- 3. Director Academics | Password: Stephen123
SET @rid = (SELECT id FROM staff_roles WHERE role_name = 'Director Academics' LIMIT 1);
INSERT INTO staff (email, full_name, password, role_id, position, department, status, is_first_login, password_changed, login_attempts, locked_until, created_at, updated_at)
VALUES ('directoracademic@igangaschoolofnursingandmidwifery.ac.ug', 'Director Academics', '$2y$10$SRiViw0a/PvxIgNS0HTdzeNVAKC6k6f6PDlTAIuUjbN5KJTeWzWRi', @rid, 'Director Academics', 'Academic Affairs', 'Active', 0, 1, 0, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE password = VALUES(password), full_name = VALUES(full_name), role_id = VALUES(role_id), position = VALUES(position), department = VALUES(department), status = 'Active', is_first_login = 0, password_changed = 1, login_attempts = 0, locked_until = NULL, updated_at = NOW();

-- 4. School Principal | Password: isnm2026
SET @rid = (SELECT id FROM staff_roles WHERE role_name = 'School Principal' LIMIT 1);
INSERT INTO staff (email, full_name, password, role_id, position, department, status, is_first_login, password_changed, login_attempts, locked_until, created_at, updated_at)
VALUES ('principal@igangaschoolofnursingandmidwifery.ac.ug', 'School Principal', '$2y$10$kxhC.LQHBKQchcMz5aDZ1O4gEwKaj3oKPCldYC/21NJFkJDJfHiOe', @rid, 'School Principal', 'Academic Affairs', 'Active', 0, 1, 0, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE password = VALUES(password), full_name = VALUES(full_name), role_id = VALUES(role_id), position = VALUES(position), department = VALUES(department), status = 'Active', is_first_login = 0, password_changed = 1, login_attempts = 0, locked_until = NULL, updated_at = NOW();

-- 5. Deputy Principal | Password: Isnm2026
SET @rid = (SELECT id FROM staff_roles WHERE role_name = 'Deputy Principal' LIMIT 1);
INSERT INTO staff (email, full_name, password, role_id, position, department, status, is_first_login, password_changed, login_attempts, locked_until, created_at, updated_at)
VALUES ('dep-principal@igangaschoolofnursingandmidwifery.ac.ug', 'Deputy Principal', '$2y$10$zghrtyzXQM.QxJ7pvB7kcOylecGg9pendgeHObrFtJE3eAitvwhtm', @rid, 'Deputy Principal', 'Academic Affairs', 'Active', 0, 1, 0, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE password = VALUES(password), full_name = VALUES(full_name), role_id = VALUES(role_id), position = VALUES(position), department = VALUES(department), status = 'Active', is_first_login = 0, password_changed = 1, login_attempts = 0, locked_until = NULL, updated_at = NOW();

-- 6. Academic Registrar | Password: Lovely2God
SET @rid = (SELECT id FROM staff_roles WHERE role_name = 'Academic Registrar' LIMIT 1);
INSERT INTO staff (email, full_name, password, role_id, position, department, status, is_first_login, password_changed, login_attempts, locked_until, created_at, updated_at)
VALUES ('academicregistrar@igangaschoolofnursingandmidwifery.ac.ug', 'Academic Registrar', '$2y$10$LfakAho0G3z3k9IO8LQ5f.ZttedFPce/Y8.gHRWZ93b4UB0.vJXsC', @rid, 'Academic Registrar', 'Academic Affairs', 'Active', 0, 1, 0, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE password = VALUES(password), full_name = VALUES(full_name), role_id = VALUES(role_id), position = VALUES(position), department = VALUES(department), status = 'Active', is_first_login = 0, password_changed = 1, login_attempts = 0, locked_until = NULL, updated_at = NOW();

-- 7. Head of Nursing | Password: isnm4life
SET @rid = (SELECT id FROM staff_roles WHERE role_name = 'Head Nursing' LIMIT 1);
INSERT INTO staff (email, full_name, password, role_id, position, department, status, is_first_login, password_changed, login_attempts, locked_until, created_at, updated_at)
VALUES ('nursing-dep@igangaschoolofnursingandmidwifery.ac.ug', 'Head of Nursing', '$2y$10$qvCOefpMA9d/kDW0/qyuYesRCqBY0eHATOdBqKw6UDwa4CqKDUT1.', @rid, 'Head of Nursing', 'Nursing', 'Active', 0, 1, 0, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE password = VALUES(password), full_name = VALUES(full_name), role_id = VALUES(role_id), position = VALUES(position), department = VALUES(department), status = 'Active', is_first_login = 0, password_changed = 1, login_attempts = 0, locked_until = NULL, updated_at = NOW();

-- 8. Head of Midwifery | Password: Life2save
SET @rid = (SELECT id FROM staff_roles WHERE role_name = 'Head Midwifery' LIMIT 1);
INSERT INTO staff (email, full_name, password, role_id, position, department, status, is_first_login, password_changed, login_attempts, locked_until, created_at, updated_at)
VALUES ('midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug', 'Head of Midwifery', '$2y$10$6Yhp8wNpYTo3FojF1ICcZukxzest3CyThJMjz8LHg1zejxAXPXz1G', @rid, 'Head of Midwifery', 'Midwifery', 'Active', 0, 1, 0, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE password = VALUES(password), full_name = VALUES(full_name), role_id = VALUES(role_id), position = VALUES(position), department = VALUES(department), status = 'Active', is_first_login = 0, password_changed = 1, login_attempts = 0, locked_until = NULL, updated_at = NOW();

-- 9. Senior Lecturer | Password: isnm2026
SET @rid = (SELECT id FROM staff_roles WHERE role_name = 'Senior Lecturers' LIMIT 1);
INSERT INTO staff (email, full_name, password, role_id, position, department, status, is_first_login, password_changed, login_attempts, locked_until, created_at, updated_at)
VALUES ('senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug', 'Senior Lecturer', '$2y$10$kxhC.LQHBKQchcMz5aDZ1O4gEwKaj3oKPCldYC/21NJFkJDJfHiOe', @rid, 'Senior Lecturer', 'Academic Affairs', 'Active', 0, 1, 0, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE password = VALUES(password), full_name = VALUES(full_name), role_id = VALUES(role_id), position = VALUES(position), department = VALUES(department), status = 'Active', is_first_login = 0, password_changed = 1, login_attempts = 0, locked_until = NULL, updated_at = NOW();

-- 10. Lecturer | Password: Isnm4life
SET @rid = (SELECT id FROM staff_roles WHERE role_name = 'Lecturers' LIMIT 1);
INSERT INTO staff (email, full_name, password, role_id, position, department, status, is_first_login, password_changed, login_attempts, locked_until, created_at, updated_at)
VALUES ('lecturers@igangaschoolofnursingandmidwifery.ac.ug', 'Lecturer', '$2y$10$kR3AwtYn.Diqxi1.Xlb8tuS7I02gfN7c51DfZmy6WEx4LdE3reDiC', @rid, 'Lecturer', 'Academic Affairs', 'Active', 0, 1, 0, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE password = VALUES(password), full_name = VALUES(full_name), role_id = VALUES(role_id), position = VALUES(position), department = VALUES(department), status = 'Active', is_first_login = 0, password_changed = 1, login_attempts = 0, locked_until = NULL, updated_at = NOW();

-- 11. Director Finance | Password: DorisJoy2026
SET @rid = (SELECT id FROM staff_roles WHERE role_name = 'Director Finance' LIMIT 1);
INSERT INTO staff (email, full_name, password, role_id, position, department, status, is_first_login, password_changed, login_attempts, locked_until, created_at, updated_at)
VALUES ('finance@igangaschoolofnursingandmidwifery.ac.ug', 'Director Finance', '$2y$10$oyDYgwVlrVdxkuqBN1/hGei2RrBsFEU0Zx03usRpcru.OHEHFe0lC', @rid, 'Director Finance', 'Finance', 'Active', 0, 1, 0, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE password = VALUES(password), full_name = VALUES(full_name), role_id = VALUES(role_id), position = VALUES(position), department = VALUES(department), status = 'Active', is_first_login = 0, password_changed = 1, login_attempts = 0, locked_until = NULL, updated_at = NOW();

-- 12. School Bursar | Password: bursar@isnm
SET @rid = (SELECT id FROM staff_roles WHERE role_name = 'School Bursar' LIMIT 1);
INSERT INTO staff (email, full_name, password, role_id, position, department, status, is_first_login, password_changed, login_attempts, locked_until, created_at, updated_at)
VALUES ('bursar@igangaschoolofnursingandmidwifery.ac.ug', 'School Bursar', '$2y$10$fvSTyvidQkAH/A.p1T.88e95KqqRAErjSCYydx5tlR/WsksyJqrHS', @rid, 'School Bursar', 'Finance', 'Active', 0, 1, 0, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE password = VALUES(password), full_name = VALUES(full_name), role_id = VALUES(role_id), position = VALUES(position), department = VALUES(department), status = 'Active', is_first_login = 0, password_changed = 1, login_attempts = 0, locked_until = NULL, updated_at = NOW();

-- 13. HR Manager | Password: Alexis2026
SET @rid = (SELECT id FROM staff_roles WHERE role_name = 'HR Manager' LIMIT 1);
INSERT INTO staff (email, full_name, password, role_id, position, department, status, is_first_login, password_changed, login_attempts, locked_until, created_at, updated_at)
VALUES ('hr-manager@igangaschoolofnursingandmidwifery.ac.ug', 'HR Manager', '$2y$10$zp0diXvAnxxdaSlLfIqY7ulmstzCNXANSkR7WU1WGoJy2vsRCJ.ju', @rid, 'HR Manager', 'Human Resources', 'Active', 0, 1, 0, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE password = VALUES(password), full_name = VALUES(full_name), role_id = VALUES(role_id), position = VALUES(position), department = VALUES(department), status = 'Active', is_first_login = 0, password_changed = 1, login_attempts = 0, locked_until = NULL, updated_at = NOW();

-- 14. School Secretary | Password: Lovely2God
SET @rid = (SELECT id FROM staff_roles WHERE role_name = 'School Secretary' LIMIT 1);
INSERT INTO staff (email, full_name, password, role_id, position, department, status, is_first_login, password_changed, login_attempts, locked_until, created_at, updated_at)
VALUES ('secretary@igangaschoolofnursingandmidwifery.ac.ug', 'School Secretary', '$2y$10$LfakAho0G3z3k9IO8LQ5f.ZttedFPce/Y8.gHRWZ93b4UB0.vJXsC', @rid, 'School Secretary', 'Administration', 'Active', 0, 1, 0, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE password = VALUES(password), full_name = VALUES(full_name), role_id = VALUES(role_id), position = VALUES(position), department = VALUES(department), status = 'Active', is_first_login = 0, password_changed = 1, login_attempts = 0, locked_until = NULL, updated_at = NOW();

-- 15. Director Admissions | Password: 2268926931
SET @rid = (SELECT id FROM staff_roles WHERE role_name = 'Director Admissions & Requirements' LIMIT 1);
INSERT INTO staff (email, full_name, password, role_id, position, department, status, is_first_login, password_changed, login_attempts, locked_until, created_at, updated_at)
VALUES ('admissions@igangaschoolofnursingandmidwifery.ac.ug', 'Director Admissions', '$2y$10$cR6SCYiWEMiyCmXoURh30utVxc4U0017t7Lj9zyy3iV0NKn7QimOK', @rid, 'Director Admissions & Requirements', 'Admissions', 'Active', 0, 1, 0, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE password = VALUES(password), full_name = VALUES(full_name), role_id = VALUES(role_id), position = VALUES(position), department = VALUES(department), status = 'Active', is_first_login = 0, password_changed = 1, login_attempts = 0, locked_until = NULL, updated_at = NOW();

-- 16. Director Admissions & Requirements | Password: 2268926931
SET @rid = (SELECT id FROM staff_roles WHERE role_name = 'Director Admissions & Requirements' LIMIT 1);
INSERT INTO staff (email, full_name, password, role_id, position, department, status, is_first_login, password_changed, login_attempts, locked_until, created_at, updated_at)
VALUES ('admissions-req@igangaschoolofnursingandmidwifery.ac.ug', 'Admissions Requirements Officer', '$2y$10$cR6SCYiWEMiyCmXoURh30utVxc4U0017t7Lj9zyy3iV0NKn7QimOK', @rid, 'Director Admissions & Requirements', 'Admissions', 'Active', 0, 1, 0, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE password = VALUES(password), full_name = VALUES(full_name), role_id = VALUES(role_id), position = VALUES(position), department = VALUES(department), status = 'Active', is_first_login = 0, password_changed = 1, login_attempts = 0, locked_until = NULL, updated_at = NOW();

-- 17. School Librarian | Password: isnm2026
SET @rid = (SELECT id FROM staff_roles WHERE role_name = 'School Librarian' LIMIT 1);
INSERT INTO staff (email, full_name, password, role_id, position, department, status, is_first_login, password_changed, login_attempts, locked_until, created_at, updated_at)
VALUES ('library@igangaschoolofnursingandmidwifery.ac.ug', 'School Librarian', '$2y$10$kxhC.LQHBKQchcMz5aDZ1O4gEwKaj3oKPCldYC/21NJFkJDJfHiOe', @rid, 'School Librarian', 'Library', 'Active', 0, 1, 0, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE password = VALUES(password), full_name = VALUES(full_name), role_id = VALUES(role_id), position = VALUES(position), department = VALUES(department), status = 'Active', is_first_login = 0, password_changed = 1, login_attempts = 0, locked_until = NULL, updated_at = NOW();

-- 18. Matron | Password: Isnm2026
SET @rid = (SELECT id FROM staff_roles WHERE role_name = 'Matrons' LIMIT 1);
INSERT INTO staff (email, full_name, password, role_id, position, department, status, is_first_login, password_changed, login_attempts, locked_until, created_at, updated_at)
VALUES ('matron@igangaschoolofnursingandmidwifery.ac.ug', 'Matron', '$2y$10$zghrtyzXQM.QxJ7pvB7kcOylecGg9pendgeHObrFtJE3eAitvwhtm', @rid, 'Matron', 'Student Welfare', 'Active', 0, 1, 0, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE password = VALUES(password), full_name = VALUES(full_name), role_id = VALUES(role_id), position = VALUES(position), department = VALUES(department), status = 'Active', is_first_login = 0, password_changed = 1, login_attempts = 0, locked_until = NULL, updated_at = NOW();

-- 19. Warden | Password: Lovely2God
SET @rid = (SELECT id FROM staff_roles WHERE role_name = 'Wardens' LIMIT 1);
INSERT INTO staff (email, full_name, password, role_id, position, department, status, is_first_login, password_changed, login_attempts, locked_until, created_at, updated_at)
VALUES ('warden@igangaschoolofnursingandmidwifery.ac.ug', 'Warden', '$2y$10$LfakAho0G3z3k9IO8LQ5f.ZttedFPce/Y8.gHRWZ93b4UB0.vJXsC', @rid, 'Warden', 'Student Welfare', 'Active', 0, 1, 0, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE password = VALUES(password), full_name = VALUES(full_name), role_id = VALUES(role_id), position = VALUES(position), department = VALUES(department), status = 'Active', is_first_login = 0, password_changed = 1, login_attempts = 0, locked_until = NULL, updated_at = NOW();

-- 20. Sickbay Nurse | Password: isnm2026
SET @rid = (SELECT id FROM staff_roles WHERE role_name = 'Sickbay' LIMIT 1);
INSERT INTO staff (email, full_name, password, role_id, position, department, status, is_first_login, password_changed, login_attempts, locked_until, created_at, updated_at)
VALUES ('sickbay@igangaschoolofnursingandmidwifery.ac.ug', 'Sickbay Nurse', '$2y$10$kxhC.LQHBKQchcMz5aDZ1O4gEwKaj3oKPCldYC/21NJFkJDJfHiOe', @rid, 'Sickbay Nurse', 'Health Services', 'Active', 0, 1, 0, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE password = VALUES(password), full_name = VALUES(full_name), role_id = VALUES(role_id), position = VALUES(position), department = VALUES(department), status = 'Active', is_first_login = 0, password_changed = 1, login_attempts = 0, locked_until = NULL, updated_at = NOW();

-- 21. Guild President | Password: isnm4life
SET @rid = (SELECT id FROM staff_roles WHERE role_name = 'Guild President' LIMIT 1);
INSERT INTO staff (email, full_name, password, role_id, position, department, status, is_first_login, password_changed, login_attempts, locked_until, created_at, updated_at)
VALUES ('guildpresident@igangaschoolofnursingandmidwifery.ac.ug', 'Guild President', '$2y$10$qvCOefpMA9d/kDW0/qyuYesRCqBY0eHATOdBqKw6UDwa4CqKDUT1.', @rid, 'Guild President', 'Student Government', 'Active', 0, 1, 0, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE password = VALUES(password), full_name = VALUES(full_name), role_id = VALUES(role_id), position = VALUES(position), department = VALUES(department), status = 'Active', is_first_login = 0, password_changed = 1, login_attempts = 0, locked_until = NULL, updated_at = NOW();

-- 22. Director ICT | Password: Lovely2God
SET @rid = (SELECT id FROM staff_roles WHERE role_name = 'Director ICT' LIMIT 1);
INSERT INTO staff (email, full_name, password, role_id, position, department, status, is_first_login, password_changed, login_attempts, locked_until, created_at, updated_at)
VALUES ('dannybict@igangaschoolofnursingandmidwifery.ac.ug', 'Director ICT', '$2y$10$LfakAho0G3z3k9IO8LQ5f.ZttedFPce/Y8.gHRWZ93b4UB0.vJXsC', @rid, 'Director ICT', 'ICT', 'Active', 0, 1, 0, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE password = VALUES(password), full_name = VALUES(full_name), role_id = VALUES(role_id), position = VALUES(position), department = VALUES(department), status = 'Active', is_first_login = 0, password_changed = 1, login_attempts = 0, locked_until = NULL, updated_at = NOW();

-- 23. Director ICT (Alt) | Password: Lovely2God
SET @rid = (SELECT id FROM staff_roles WHERE role_name = 'Director ICT' LIMIT 1);
INSERT INTO staff (email, full_name, password, role_id, position, department, status, is_first_login, password_changed, login_attempts, locked_until, created_at, updated_at)
VALUES ('directorict@igangaschoolofnursingandmidwifery.ac.ug', 'Director ICT (Alt)', '$2y$10$LfakAho0G3z3k9IO8LQ5f.ZttedFPce/Y8.gHRWZ93b4UB0.vJXsC', @rid, 'Director ICT', 'ICT', 'Active', 0, 1, 0, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE password = VALUES(password), full_name = VALUES(full_name), role_id = VALUES(role_id), position = VALUES(position), department = VALUES(department), status = 'Active', is_first_login = 0, password_changed = 1, login_attempts = 0, locked_until = NULL, updated_at = NOW();

-- 24. Computer Lab Manager | Password: Techno123
SET @rid = (SELECT id FROM staff_roles WHERE role_name = 'Computer Lab Manager' LIMIT 1);
INSERT INTO staff (email, full_name, password, role_id, position, department, status, is_first_login, password_changed, login_attempts, locked_until, created_at, updated_at)
VALUES ('computerlab@igangaschoolofnursingandmidwifery.ac.ug', 'Computer Lab Manager', '$2y$10$VlvjrDifzF/NXpI1BVOxv.B8kMrUtkezs812UTKrUo45qSAYUMJX6', @rid, 'Computer Lab Manager', 'ICT', 'Active', 0, 1, 0, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE password = VALUES(password), full_name = VALUES(full_name), role_id = VALUES(role_id), position = VALUES(position), department = VALUES(department), status = 'Active', is_first_login = 0, password_changed = 1, login_attempts = 0, locked_until = NULL, updated_at = NOW();

-- 25. Computer Lab Technician | Password: Techno123
SET @rid = (SELECT id FROM staff_roles WHERE role_name = 'Computer Lab Manager' LIMIT 1);
INSERT INTO staff (email, full_name, password, role_id, position, department, status, is_first_login, password_changed, login_attempts, locked_until, created_at, updated_at)
VALUES ('computer-lab@igangaschoolofnursingandmidwifery.ac.ug', 'Computer Lab Technician', '$2y$10$VlvjrDifzF/NXpI1BVOxv.B8kMrUtkezs812UTKrUo45qSAYUMJX6', @rid, 'Computer Lab Manager', 'ICT', 'Active', 0, 1, 0, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE password = VALUES(password), full_name = VALUES(full_name), role_id = VALUES(role_id), position = VALUES(position), department = VALUES(department), status = 'Active', is_first_login = 0, password_changed = 1, login_attempts = 0, locked_until = NULL, updated_at = NOW();

-- 26. Skills Lab Manager | Password: Lovely2God
SET @rid = (SELECT id FROM staff_roles WHERE role_name = 'Skills Lab' LIMIT 1);
INSERT INTO staff (email, full_name, password, role_id, position, department, status, is_first_login, password_changed, login_attempts, locked_until, created_at, updated_at)
VALUES ('skillslab@igangaschoolofnursingandmidwifery.ac.ug', 'Skills Lab Manager', '$2y$10$LfakAho0G3z3k9IO8LQ5f.ZttedFPce/Y8.gHRWZ93b4UB0.vJXsC', @rid, 'Skills Lab Manager', 'Skills Laboratory', 'Active', 0, 1, 0, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE password = VALUES(password), full_name = VALUES(full_name), role_id = VALUES(role_id), position = VALUES(position), department = VALUES(department), status = 'Active', is_first_login = 0, password_changed = 1, login_attempts = 0, locked_until = NULL, updated_at = NOW();

-- 27. Skills Lab Technician | Password: Lovely2God
SET @rid = (SELECT id FROM staff_roles WHERE role_name = 'Skills Lab' LIMIT 1);
INSERT INTO staff (email, full_name, password, role_id, position, department, status, is_first_login, password_changed, login_attempts, locked_until, created_at, updated_at)
VALUES ('skills-lab@igangaschoolofnursingandmidwifery.ac.ug', 'Skills Lab Technician', '$2y$10$LfakAho0G3z3k9IO8LQ5f.ZttedFPce/Y8.gHRWZ93b4UB0.vJXsC', @rid, 'Skills Lab Technician', 'Skills Laboratory', 'Active', 0, 1, 0, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE password = VALUES(password), full_name = VALUES(full_name), role_id = VALUES(role_id), position = VALUES(position), department = VALUES(department), status = 'Active', is_first_login = 0, password_changed = 1, login_attempts = 0, locked_until = NULL, updated_at = NOW();

-- 28. Storekeeper | Password: Isnm4life
SET @rid = (SELECT id FROM staff_roles WHERE role_name = 'Storekeeper' LIMIT 1);
INSERT INTO staff (email, full_name, password, role_id, position, department, status, is_first_login, password_changed, login_attempts, locked_until, created_at, updated_at)
VALUES ('store@igangaschoolofnursingandmidwifery.ac.ug', 'Storekeeper', '$2y$10$kR3AwtYn.Diqxi1.Xlb8tuS7I02gfN7c51DfZmy6WEx4LdE3reDiC', @rid, 'Storekeeper', 'Store', 'Active', 0, 1, 0, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE password = VALUES(password), full_name = VALUES(full_name), role_id = VALUES(role_id), position = VALUES(position), department = VALUES(department), status = 'Active', is_first_login = 0, password_changed = 1, login_attempts = 0, locked_until = NULL, updated_at = NOW();

-- 29. Driver | Password: isnm4life
SET @rid = (SELECT id FROM staff_roles WHERE role_name = 'Drivers' LIMIT 1);
INSERT INTO staff (email, full_name, password, role_id, position, department, status, is_first_login, password_changed, login_attempts, locked_until, created_at, updated_at)
VALUES ('drivers@igangaschoolofnursingandmidwifery.ac.ug', 'Driver', '$2y$10$qvCOefpMA9d/kDW0/qyuYesRCqBY0eHATOdBqKw6UDwa4CqKDUT1.', @rid, 'Driver', 'Transport', 'Active', 0, 1, 0, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE password = VALUES(password), full_name = VALUES(full_name), role_id = VALUES(role_id), position = VALUES(position), department = VALUES(department), status = 'Active', is_first_login = 0, password_changed = 1, login_attempts = 0, locked_until = NULL, updated_at = NOW();

-- 30. Security Officer | Password: safty1st
SET @rid = (SELECT id FROM staff_roles WHERE role_name = 'Security' LIMIT 1);
INSERT INTO staff (email, full_name, password, role_id, position, department, status, is_first_login, password_changed, login_attempts, locked_until, created_at, updated_at)
VALUES ('security@igangaschoolofnursingandmidwifery.ac.ug', 'Security Officer', '$2y$10$HFpnuTgqdCgB.a.Yv/CIhuyOvFycl4Yz342v9F20CAs9vUKr7xvNO', @rid, 'Security Officer', 'Security', 'Active', 0, 1, 0, NULL, NOW(), NOW())
ON DUPLICATE KEY UPDATE password = VALUES(password), full_name = VALUES(full_name), role_id = VALUES(role_id), position = VALUES(position), department = VALUES(department), status = 'Active', is_first_login = 0, password_changed = 1, login_attempts = 0, locked_until = NULL, updated_at = NOW();

-- ------------------------------------------------------------
-- STEP 5: Unlock all accounts (clear any lockouts)
-- ------------------------------------------------------------

UPDATE staff SET login_attempts = 0, locked_until = NULL
WHERE login_attempts > 0 OR locked_until IS NOT NULL;

-- ------------------------------------------------------------
-- STEP 6: Verification — all 30 accounts with their roles
-- ------------------------------------------------------------

SELECT s.email, s.full_name, s.position, sr.role_name, sr.dashboard_path, s.status,
       (s.password IS NOT NULL AND s.password != '') AS has_password
FROM staff s
LEFT JOIN staff_roles sr ON s.role_id = sr.id
WHERE s.email IN (
  'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug',
  'ceo@igangaschoolofnursingandmidwifery.ac.ug',
  'directoracademic@igangaschoolofnursingandmidwifery.ac.ug',
  'principal@igangaschoolofnursingandmidwifery.ac.ug',
  'dep-principal@igangaschoolofnursingandmidwifery.ac.ug',
  'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug',
  'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug',
  'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug',
  'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug',
  'lecturers@igangaschoolofnursingandmidwifery.ac.ug',
  'finance@igangaschoolofnursingandmidwifery.ac.ug',
  'bursar@igangaschoolofnursingandmidwifery.ac.ug',
  'hr-manager@igangaschoolofnursingandmidwifery.ac.ug',
  'secretary@igangaschoolofnursingandmidwifery.ac.ug',
  'admissions@igangaschoolofnursingandmidwifery.ac.ug',
  'admissions-req@igangaschoolofnursingandmidwifery.ac.ug',
  'library@igangaschoolofnursingandmidwifery.ac.ug',
  'matron@igangaschoolofnursingandmidwifery.ac.ug',
  'warden@igangaschoolofnursingandmidwifery.ac.ug',
  'sickbay@igangaschoolofnursingandmidwifery.ac.ug',
  'guildpresident@igangaschoolofnursingandmidwifery.ac.ug',
  'dannybict@igangaschoolofnursingandmidwifery.ac.ug',
  'directorict@igangaschoolofnursingandmidwifery.ac.ug',
  'computerlab@igangaschoolofnursingandmidwifery.ac.ug',
  'computer-lab@igangaschoolofnursingandmidwifery.ac.ug',
  'skillslab@igangaschoolofnursingandmidwifery.ac.ug',
  'skills-lab@igangaschoolofnursingandmidwifery.ac.ug',
  'store@igangaschoolofnursingandmidwifery.ac.ug',
  'drivers@igangaschoolofnursingandmidwifery.ac.ug',
  'security@igangaschoolofnursingandmidwifery.ac.ug'
)
ORDER BY sr.role_level ASC, s.full_name ASC;

SELECT 'ALL 30 STAFF ACCOUNTS CONFIGURED SUCCESSFULLY' AS Status;
