-- ============================================================
-- ISNM STAFF ACCOUNT SETUP — Run ONCE
-- Paste into phpMyAdmin SQL tab or run: mysql ... < this.sql
-- ============================================================
-- bcrypt hashes generated 2026-07-06
-- ============================================================

USE igangaschoolofl_staffs_db;

-- ── 1. Ensure staff_roles exist ──────────────────────────────
INSERT IGNORE INTO staff_roles (role_name, dashboard_path, role_level) VALUES
('Director General',                    'dashboards/director-general.php',  1),
('CEO',                                 'dashboards/ceo.php',              1),
('Director Academics',                  'dashboards/director-academics.php',2),
('Director Finance',                    'dashboards/director-finance.php',  2),
('School Principal',                    'dashboards/school-principal.php',  2),
('Deputy Principal',                    'dashboards/deputy-principal.php',  3),
('Academic Registrar',                  'dashboards/academic-registrar.php',3),
('School Bursar',                       'dashboards/school-bursar.php',     3),
('School Secretary',                    'dashboards/school-secretary.php',  4),
('HR Manager',                          'dashboards/hr-manager.php',        3),
('School Librarian',                    'dashboards/school-librarian.php',  4),
('Head of Nursing',                     'dashboards/head-nursing.php',      3),
('Head of Midwifery',                   'dashboards/head-midwifery.php',    3),
('Senior Lecturer',                     'dashboards/senior-lecturers.php',  4),
('Lecturer',                            'dashboards/lecturers.php',         4),
('Matron',                              'dashboards/matrons.php',           4),
('Warden',                              'dashboards/wardens.php',           4),
('Sickbay Nurse',                       'dashboards/sickbay.php',           4),
('Driver',                              'dashboards/drivers.php',           5),
('Security Officer',                    'dashboards/security.php',          5),
('Storekeeper',                         'dashboards/storekeeper.php',       5),
('Guild President',                     'dashboards/guild-president.php',   4),
('Director Admissions',                 'dashboards/director-admissions.php',2),
('Director Admissions & Requirements',  'dashboards/director-admissions.php',2),
('Director ICT',                        'dashboards/director-ict.php',      2),
('Computer Lab Manager',                'dashboards/computer_lab.php',      4),
('Skills Lab Manager',                  'dashboards/skills-lab.php',        4),
('Skills Lab Technician',               'dashboards/skills-lab.php',        5);

-- ── 2. Create / update staff accounts ────────────────────────
-- Column order: email, full_name, password, role_id, position, department, status, is_first_login, password_changed, hire_date

INSERT IGNORE INTO staff (email, full_name, password, role_id, position, department, status, is_first_login, password_changed, hire_date) VALUES

-- Leadership
('directorgeneral@igangaschoolofnursingandmidwifery.ac.ug', 'Director General',
 '$2y$10$oyDYgwVlrVdxkuqBN1/hGei2RrBsFEU0Zx03usRpcru.OHEHFe0lC',
 (SELECT id FROM staff_roles WHERE role_name='Director General' LIMIT 1),
 'Director General', 'Executive', 'Active', 0, 1, CURDATE()),

('ceo@igangaschoolofnursingandmidwifery.ac.ug', 'Chief Executive Officer',
 '$2y$10$LfakAho0G3z3k9IO8LQ5f.ZttedFPce/Y8.gHRWZ93b4UB0.vJXsC',
 (SELECT id FROM staff_roles WHERE role_name='CEO' LIMIT 1),
 'CEO', 'Executive', 'Active', 0, 1, CURDATE()),

-- Academic Affairs
('directoracademic@igangaschoolofnursingandmidwifery.ac.ug', 'Director Academics',
 '$2y$10$SRiViw0a/PvxIgNS0HTdzeNVAKC6k6f6PDlTAIuUjbN5KJTeWzWRi',
 (SELECT id FROM staff_roles WHERE role_name='Director Academics' LIMIT 1),
 'Director Academics', 'Academic Affairs', 'Active', 0, 1, CURDATE()),

('principal@igangaschoolofnursingandmidwifery.ac.ug', 'School Principal',
 '$2y$10$kxhC.LQHBKQchcMz5aDZ1O4gEwKaj3oKPCldYC/21NJFkJDJfHiOe',
 (SELECT id FROM staff_roles WHERE role_name='School Principal' LIMIT 1),
 'School Principal', 'Academic Affairs', 'Active', 0, 1, CURDATE()),

('dep-principal@igangaschoolofnursingandmidwifery.ac.ug', 'Deputy Principal',
 '$2y$10$zghrtyzXQM.QxJ7pvB7kcOylecGg9pendgeHObrFtJE3eAitvwhtm',
 (SELECT id FROM staff_roles WHERE role_name='Deputy Principal' LIMIT 1),
 'Deputy Principal', 'Academic Affairs', 'Active', 0, 1, CURDATE()),

('academicregistrar@igangaschoolofnursingandmidwifery.ac.ug', 'Academic Registrar',
 '$2y$10$LfakAho0G3z3k9IO8LQ5f.ZttedFPce/Y8.gHRWZ93b4UB0.vJXsC',
 (SELECT id FROM staff_roles WHERE role_name='Academic Registrar' LIMIT 1),
 'Academic Registrar', 'Academic Affairs', 'Active', 0, 1, CURDATE()),

('nursing-dep@igangaschoolofnursingandmidwifery.ac.ug', 'Head of Nursing',
 '$2y$10$qvCOefpMA9d/kDW0/qyuYesRCqBY0eHATOdBqKw6UDwa4CqKDUT1.',
 (SELECT id FROM staff_roles WHERE role_name='Head of Nursing' LIMIT 1),
 'Head of Nursing', 'Nursing Department', 'Active', 0, 1, CURDATE()),

('midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug', 'Head of Midwifery',
 '$2y$10$6Yhp8wNpYTo3FojF1ICcZukxzest3CyThJMjz8LHg1zejxAXPXz1G',
 (SELECT id FROM staff_roles WHERE role_name='Head of Midwifery' LIMIT 1),
 'Head of Midwifery', 'Midwifery Department', 'Active', 0, 1, CURDATE()),

('senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug', 'Senior Lecturer',
 '$2y$10$kxhC.LQHBKQchcMz5aDZ1O4gEwKaj3oKPCldYC/21NJFkJDJfHiOe',
 (SELECT id FROM staff_roles WHERE role_name='Senior Lecturer' LIMIT 1),
 'Senior Lecturer', 'Academic Affairs', 'Active', 0, 1, CURDATE()),

('lecturers@igangaschoolofnursingandmidwifery.ac.ug', 'Lecturer',
 '$2y$10$kR3AwtYn.Diqxi1.Xlb8tuS7I02gfN7c51DfZmy6WEx4LdE3reDiC',
 (SELECT id FROM staff_roles WHERE role_name='Lecturer' LIMIT 1),
 'Lecturer', 'Academic Affairs', 'Active', 0, 1, CURDATE()),

-- Finance
('finance@igangaschoolofnursingandmidwifery.ac.ug', 'Director Finance',
 '$2y$10$oyDYgwVlrVdxkuqBN1/hGei2RrBsFEU0Zx03usRpcru.OHEHFe0lC',
 (SELECT id FROM staff_roles WHERE role_name='Director Finance' LIMIT 1),
 'Director Finance', 'Finance', 'Active', 0, 1, CURDATE()),

('bursar@igangaschoolofnursingandmidwifery.ac.ug', 'School Bursar',
 '$2y$10$fvSTyvidQkAH/A.p1T.88e95KqqRAErjSCYydx5tlR/WsksyJqrHS',
 (SELECT id FROM staff_roles WHERE role_name='School Bursar' LIMIT 1),
 'School Bursar', 'Finance', 'Active', 0, 1, CURDATE()),

-- HR & Admin
('hr-manager@igangaschoolofnursingandmidwifery.ac.ug', 'HR Manager',
 '$2y$10$zp0diXvAnxxdaSlLfIqY7ulmstzCNXANSkR7WU1WGoJy2vsRCJ.ju',
 (SELECT id FROM staff_roles WHERE role_name='HR Manager' LIMIT 1),
 'HR Manager', 'Human Resources', 'Active', 0, 1, CURDATE()),

('secretary@igangaschoolofnursingandmidwifery.ac.ug', 'School Secretary',
 '$2y$10$LfakAho0G3z3k9IO8LQ5f.ZttedFPce/Y8.gHRWZ93b4UB0.vJXsC',
 (SELECT id FROM staff_roles WHERE role_name='School Secretary' LIMIT 1),
 'School Secretary', 'Administration', 'Active', 0, 1, CURDATE()),

-- Student Services
('admissions@igangaschoolofnursingandmidwifery.ac.ug', 'Director Admissions',
 '$2y$10$cR6SCYiWEMiyCmXoURh30utVxc4U0017t7Lj9zyy3iV0NKn7QimOK',
 (SELECT id FROM staff_roles WHERE role_name='Director Admissions' LIMIT 1),
 'Director Admissions', 'Admissions', 'Active', 0, 1, CURDATE()),

('admissions-req@igangaschoolofnursingandmidwifery.ac.ug', 'Director Admissions & Requirements',
 '$2y$10$cR6SCYiWEMiyCmXoURh30utVxc4U0017t7Lj9zyy3iV0NKn7QimOK',
 (SELECT id FROM staff_roles WHERE role_name='Director Admissions & Requirements' LIMIT 1),
 'Director Admissions & Requirements', 'Admissions', 'Active', 0, 1, CURDATE()),

('library@igangaschoolofnursingandmidwifery.ac.ug', 'School Librarian',
 '$2y$10$kxhC.LQHBKQchcMz5aDZ1O4gEwKaj3oKPCldYC/21NJFkJDJfHiOe',
 (SELECT id FROM staff_roles WHERE role_name='School Librarian' LIMIT 1),
 'School Librarian', 'Library Services', 'Active', 0, 1, CURDATE()),

('matron@igangaschoolofnursingandmidwifery.ac.ug', 'Matron',
 '$2y$10$zghrtyzXQM.QxJ7pvB7kcOylecGg9pendgeHObrFtJE3eAitvwhtm',
 (SELECT id FROM staff_roles WHERE role_name='Matron' LIMIT 1),
 'Matron', 'Student Welfare', 'Active', 0, 1, CURDATE()),

('warden@igangaschoolofnursingandmidwifery.ac.ug', 'Warden',
 '$2y$10$LfakAho0G3z3k9IO8LQ5f.ZttedFPce/Y8.gHRWZ93b4UB0.vJXsC',
 (SELECT id FROM staff_roles WHERE role_name='Warden' LIMIT 1),
 'Warden', 'Student Welfare', 'Active', 0, 1, CURDATE()),

('sickbay@igangaschoolofnursingandmidwifery.ac.ug', 'Sickbay Nurse',
 '$2y$10$kxhC.LQHBKQchcMz5aDZ1O4gEwKaj3oKPCldYC/21NJFkJDJfHiOe',
 (SELECT id FROM staff_roles WHERE role_name='Sickbay Nurse' LIMIT 1),
 'Sickbay Nurse', 'Health Services', 'Active', 0, 1, CURDATE()),

('guildpresident@igangaschoolofnursingandmidwifery.ac.ug', 'Guild President',
 '$2y$10$qvCOefpMA9d/kDW0/qyuYesRCqBY0eHATOdBqKw6UDwa4CqKDUT1.',
 (SELECT id FROM staff_roles WHERE role_name='Guild President' LIMIT 1),
 'Guild President', 'Student Leadership', 'Active', 0, 1, CURDATE()),

-- ICT
('dannybict@igangaschoolofnursingandmidwifery.ac.ug', 'Director ICT',
 '$2y$10$LfakAho0G3z3k9IO8LQ5f.ZttedFPce/Y8.gHRWZ93b4UB0.vJXsC',
 (SELECT id FROM staff_roles WHERE role_name='Director ICT' LIMIT 1),
 'Director ICT', 'ICT', 'Active', 0, 1, CURDATE()),

('directorict@igangaschoolofnursingandmidwifery.ac.ug', 'Director ICT',
 '$2y$10$LfakAho0G3z3k9IO8LQ5f.ZttedFPce/Y8.gHRWZ93b4UB0.vJXsC',
 (SELECT id FROM staff_roles WHERE role_name='Director ICT' LIMIT 1),
 'Director ICT', 'ICT', 'Active', 0, 1, CURDATE()),

('computerlab@igangaschoolofnursingandmidwifery.ac.ug', 'Computer Lab Manager',
 '$2y$10$VlvjrDifzF/NXpI1BVOxv.B8kMrUtkezs812UTKrUo45qSAYUMJX6',
 (SELECT id FROM staff_roles WHERE role_name='Computer Lab Manager' LIMIT 1),
 'Computer Lab Manager', 'ICT', 'Active', 0, 1, CURDATE()),

('computer-lab@igangaschoolofnursingandmidwifery.ac.ug', 'Computer Lab Manager',
 '$2y$10$VlvjrDifzF/NXpI1BVOxv.B8kMrUtkezs812UTKrUo45qSAYUMJX6',
 (SELECT id FROM staff_roles WHERE role_name='Computer Lab Manager' LIMIT 1),
 'Computer Lab Manager', 'ICT', 'Active', 0, 1, CURDATE()),

('skillslab@igangaschoolofnursingandmidwifery.ac.ug', 'Skills Lab Manager',
 '$2y$10$LfakAho0G3z3k9IO8LQ5f.ZttedFPce/Y8.gHRWZ93b4UB0.vJXsC',
 (SELECT id FROM staff_roles WHERE role_name='Skills Lab Manager' LIMIT 1),
 'Skills Lab Manager', 'Skills Laboratory', 'Active', 0, 1, CURDATE()),

('skills-lab@igangaschoolofnursingandmidwifery.ac.ug', 'Skills Lab Technician',
 '$2y$10$LfakAho0G3z3k9IO8LQ5f.ZttedFPce/Y8.gHRWZ93b4UB0.vJXsC',
 (SELECT id FROM staff_roles WHERE role_name='Skills Lab Technician' LIMIT 1),
 'Skills Lab Technician', 'Skills Laboratory', 'Active', 0, 1, CURDATE()),

-- Logistics
('store@igangaschoolofnursingandmidwifery.ac.ug', 'Storekeeper',
 '$2y$10$kR3AwtYn.Diqxi1.Xlb8tuS7I02gfN7c51DfZmy6WEx4LdE3reDiC',
 (SELECT id FROM staff_roles WHERE role_name='Storekeeper' LIMIT 1),
 'Storekeeper', 'Logistics', 'Active', 0, 1, CURDATE()),

('drivers@igangaschoolofnursingandmidwifery.ac.ug', 'Driver',
 '$2y$10$qvCOefpMA9d/kDW0/qyuYesRCqBY0eHATOdBqKw6UDwa4CqKDUT1.',
 (SELECT id FROM staff_roles WHERE role_name='Driver' LIMIT 1),
 'Driver', 'Logistics', 'Active', 0, 1, CURDATE()),

('security@igangaschoolofnursingandmidwifery.ac.ug', 'Security Officer',
 '$2y$10$HFpnuTgqdCgB.a.Yv/CIhuyOvFycl4Yz342v9F20CAs9vUKr7xvNO',
 (SELECT id FROM staff_roles WHERE role_name='Security Officer' LIMIT 1),
 'Security Officer', 'Security', 'Active', 0, 1, CURDATE());

-- ── 3. Update passwords for any existing accounts ────────────
UPDATE staff SET password='$2y$10$oyDYgwVlrVdxkuqBN1/hGei2RrBsFEU0Zx03usRpcru.OHEHFe0lC', is_first_login=0, password_changed=1 WHERE email='directorgeneral@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password='$2y$10$LfakAho0G3z3k9IO8LQ5f.ZttedFPce/Y8.gHRWZ93b4UB0.vJXsC', is_first_login=0, password_changed=1 WHERE email='ceo@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password='$2y$10$SRiViw0a/PvxIgNS0HTdzeNVAKC6k6f6PDlTAIuUjbN5KJTeWzWRi', is_first_login=0, password_changed=1 WHERE email='directoracademic@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password='$2y$10$oyDYgwVlrVdxkuqBN1/hGei2RrBsFEU0Zx03usRpcru.OHEHFe0lC', is_first_login=0, password_changed=1 WHERE email='finance@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password='$2y$10$kxhC.LQHBKQchcMz5aDZ1O4gEwKaj3oKPCldYC/21NJFkJDJfHiOe', is_first_login=0, password_changed=1 WHERE email='principal@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password='$2y$10$zghrtyzXQM.QxJ7pvB7kcOylecGg9pendgeHObrFtJE3eAitvwhtm', is_first_login=0, password_changed=1 WHERE email='dep-principal@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password='$2y$10$LfakAho0G3z3k9IO8LQ5f.ZttedFPce/Y8.gHRWZ93b4UB0.vJXsC', is_first_login=0, password_changed=1 WHERE email='academicregistrar@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password='$2y$10$zp0diXvAnxxdaSlLfIqY7ulmstzCNXANSkR7WU1WGoJy2vsRCJ.ju', is_first_login=0, password_changed=1 WHERE email='hr-manager@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password='$2y$10$LfakAho0G3z3k9IO8LQ5f.ZttedFPce/Y8.gHRWZ93b4UB0.vJXsC', is_first_login=0, password_changed=1 WHERE email='secretary@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password='$2y$10$fvSTyvidQkAH/A.p1T.88e95KqqRAErjSCYydx5tlR/WsksyJqrHS', is_first_login=0, password_changed=1 WHERE email='bursar@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password='$2y$10$kxhC.LQHBKQchcMz5aDZ1O4gEwKaj3oKPCldYC/21NJFkJDJfHiOe', is_first_login=0, password_changed=1 WHERE email='library@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password='$2y$10$qvCOefpMA9d/kDW0/qyuYesRCqBY0eHATOdBqKw6UDwa4CqKDUT1.', is_first_login=0, password_changed=1 WHERE email='nursing-dep@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password='$2y$10$6Yhp8wNpYTo3FojF1ICcZukxzest3CyThJMjz8LHg1zejxAXPXz1G', is_first_login=0, password_changed=1 WHERE email='midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password='$2y$10$kxhC.LQHBKQchcMz5aDZ1O4gEwKaj3oKPCldYC/21NJFkJDJfHiOe', is_first_login=0, password_changed=1 WHERE email='senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password='$2y$10$kR3AwtYn.Diqxi1.Xlb8tuS7I02gfN7c51DfZmy6WEx4LdE3reDiC', is_first_login=0, password_changed=1 WHERE email='lecturers@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password='$2y$10$zghrtyzXQM.QxJ7pvB7kcOylecGg9pendgeHObrFtJE3eAitvwhtm', is_first_login=0, password_changed=1 WHERE email='matron@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password='$2y$10$LfakAho0G3z3k9IO8LQ5f.ZttedFPce/Y8.gHRWZ93b4UB0.vJXsC', is_first_login=0, password_changed=1 WHERE email='warden@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password='$2y$10$kxhC.LQHBKQchcMz5aDZ1O4gEwKaj3oKPCldYC/21NJFkJDJfHiOe', is_first_login=0, password_changed=1 WHERE email='sickbay@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password='$2y$10$qvCOefpMA9d/kDW0/qyuYesRCqBY0eHATOdBqKw6UDwa4CqKDUT1.', is_first_login=0, password_changed=1 WHERE email='drivers@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password='$2y$10$HFpnuTgqdCgB.a.Yv/CIhuyOvFycl4Yz342v9F20CAs9vUKr7xvNO', is_first_login=0, password_changed=1 WHERE email='security@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password='$2y$10$kR3AwtYn.Diqxi1.Xlb8tuS7I02gfN7c51DfZmy6WEx4LdE3reDiC', is_first_login=0, password_changed=1 WHERE email='store@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password='$2y$10$qvCOefpMA9d/kDW0/qyuYesRCqBY0eHATOdBqKw6UDwa4CqKDUT1.', is_first_login=0, password_changed=1 WHERE email='guildpresident@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password='$2y$10$cR6SCYiWEMiyCmXoURh30utVxc4U0017t7Lj9zyy3iV0NKn7QimOK', is_first_login=0, password_changed=1 WHERE email='admissions@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password='$2y$10$cR6SCYiWEMiyCmXoURh30utVxc4U0017t7Lj9zyy3iV0NKn7QimOK', is_first_login=0, password_changed=1 WHERE email='admissions-req@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password='$2y$10$LfakAho0G3z3k9IO8LQ5f.ZttedFPce/Y8.gHRWZ93b4UB0.vJXsC', is_first_login=0, password_changed=1 WHERE email='dannybict@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password='$2y$10$LfakAho0G3z3k9IO8LQ5f.ZttedFPce/Y8.gHRWZ93b4UB0.vJXsC', is_first_login=0, password_changed=1 WHERE email='directorict@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password='$2y$10$VlvjrDifzF/NXpI1BVOxv.B8kMrUtkezs812UTKrUo45qSAYUMJX6', is_first_login=0, password_changed=1 WHERE email='computerlab@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password='$2y$10$VlvjrDifzF/NXpI1BVOxv.B8kMrUtkezs812UTKrUo45qSAYUMJX6', is_first_login=0, password_changed=1 WHERE email='computer-lab@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password='$2y$10$LfakAho0G3z3k9IO8LQ5f.ZttedFPce/Y8.gHRWZ93b4UB0.vJXsC', is_first_login=0, password_changed=1 WHERE email='skillslab@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password='$2y$10$LfakAho0G3z3k9IO8LQ5f.ZttedFPce/Y8.gHRWZ93b4UB0.vJXsC', is_first_login=0, password_changed=1 WHERE email='skills-lab@igangaschoolofnursingandmidwifery.ac.ug';

SELECT 'ALL ACCOUNTS SET UP SUCCESSFULLY' AS Status;
