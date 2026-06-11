-- ============================================================
-- ISNM MASTER CREDENTIALS SQL
-- Run this in: igangaschoolofl_staffs_db
-- All bcrypt passwords match the official credentials list
-- ============================================================
USE `igangaschoolofl_staffs_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- Ensure all roles exist
INSERT INTO staff_roles (role_name, role_description, role_level, dashboard_path, permissions)
VALUES
('Director General',       'Full system access - Doris Joy',           'Executive',      'dashboards/director-general.php',    '{"all":true,"super_admin":true}'),
('CEO',                    'Chief Executive Officer',                  'Executive',      'dashboards/ceo.php',                 '{"strategic":true}'),
('Director Academics',     'Academic programs oversight',              'Management',     'dashboards/director-academics.php',  '{"academic":true}'),
('Director Finance',       'Financial management',                     'Management',     'dashboards/director-finance.php',    '{"financial":true}'),
('Director ICT',           'ICT management',                           'Management',     'dashboards/director-ict.php',        '{"ict":true}'),
('School Principal',       'School leadership',                        'Executive',      'dashboards/school-principal.php',    '{"academic":true,"administrative":true}'),
('Deputy Principal',       'Assistant principal',                      'Management',     'dashboards/deputy-principal.php',    '{"academic":true}'),
('Academic Registrar',     'Student registration and records',         'Academic',       'dashboards/academic-registrar.php',  '{"academic":true,"students":true}'),
('HR Manager',             'Human resources',                          'Management',     'dashboards/hr-manager.php',          '{"hr":true,"staff":true}'),
('School Secretary',       'Administrative support',                   'Administrative', 'dashboards/school-secretary.php',    '{"administrative":true}'),
('School Librarian',       'Library management',                       'Support',        'dashboards/school-librarian.php',    '{"library":true}'),
('Head Nursing',           'Nursing department head',                  'Academic',       'dashboards/head-nursing.php',        '{"nursing":true}'),
('Head Midwifery',         'Midwifery department head',                'Academic',       'dashboards/head-midwifery.php',      '{"midwifery":true}'),
('Senior Lecturers',       'Senior teaching staff',                    'Academic',       'dashboards/senior-lecturers.php',    '{"teaching":true}'),
('Lecturers',              'Teaching staff',                           'Academic',       'dashboards/lecturers.php',           '{"teaching":true}'),
('Matrons',                'Student welfare',                          'Support',        'dashboards/matrons.php',             '{"student_welfare":true}'),
('Wardens',                'Student discipline & residential',         'Support',        'dashboards/wardens.php',             '{"discipline":true}'),
('Sickbay',                'Medical support',                          'Support',        'dashboards/sickbay.php',             '{"healthcare":true}'),
('Drivers',                'Transportation',                           'Support',        'dashboards/drivers.php',             '{"transportation":true}'),
('Security',               'Campus security',                          'Support',        'dashboards/security.php',            '{"security":true}'),
('Store Keeper',           'Store inventory management',               'Support',        'dashboards/storekeeper.php',         '{"store":true,"inventory":true}'),
('Guild President',        'Student guild leadership',                 'Support',        'dashboards/guild-president.php',     '{"student_affairs":true}'),
('Director Admissions',    'Admissions and requirements director',     'Management',     'dashboards/director-admissions.php', '{"admissions":true}'),
('School Bursar',          'Financial operations & fee management',    'Administrative', 'bursar_dashboard.php',               '{"financial":true,"fees":true}')
ON DUPLICATE KEY UPDATE
    role_description = VALUES(role_description),
    dashboard_path   = VALUES(dashboard_path),
    permissions      = VALUES(permissions);

-- ============================================================
-- Remove old staff entries to re-insert with correct credentials
-- ============================================================
DELETE FROM staff WHERE email IN (
    'computer-lab@igangaschoolofnursingandmidwifery.ac.ug',
    'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug',
    'ceo@igangaschoolofnursingandmidwifery.ac.ug',
    'directoracademic@igangaschoolofnursingandmidwifery.ac.ug',
    'finance@igangaschoolofnursingandmidwifery.ac.ug',
    'principal@igangaschoolofnursingandmidwifery.ac.ug',
    'dep-principal@igangaschoolofnursingandmidwifery.ac.ug',
    'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug',
    'hr-manager@igangaschoolofnursingandmidwifery.ac.ug',
    'secretary@igangaschoolofnursingandmidwifery.ac.ug',
    'library@igangaschoolofnursingandmidwifery.ac.ug',
    'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug',
    'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug',
    'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug',
    'lecturers@igangaschoolofnursingandmidwifery.ac.ug',
    'matron@igangaschoolofnursingandmidwifery.ac.ug',
    'warden@igangaschoolofnursingandmidwifery.ac.ug',
    'sickbay@igangaschoolofnursingandmidwifery.ac.ug',
    'drivers@igangaschoolofnursingandmidwifery.ac.ug',
    'security@igangaschoolofnursingandmidwifery.ac.ug',
    'store@igangaschoolofnursingandmidwifery.ac.ug',
    'guildpresident@igangaschoolofnursingandmidwifery.ac.ug',
    'admissions@igangaschoolofnursingandmidwifery.ac.ug',
    'dannybict@igangaschoolofnursingandmidwifery.ac.ug',
    'bursar@igangaschoolofnursingandmidwifery.ac.ug',
    'director.general@isnm.ac.ug', 'ceo@isnm.ac.ug',
    'bursar@isnm.ac.ug', 'director.academics@isnm.ac.ug',
    'director.ict@isnm.ac.ug', 'director.finance@isnm.ac.ug',
    'nonteaching@isnm.ac.ug', 'bursar.assistant@isnm.ac.ug'
);

-- ============================================================
-- INSERT ALL STAFF WITH OFFICIAL CREDENTIALS
-- Passwords (bcrypt):
--  Techno123   = $2y$10$urZgn8VmD81qHF6.lQ3eoOJD4TdHGdreDzfotucSdrIgmEqDqeHgu
--  DorisJoy2026= $2y$10$04.DbEy7SaXjwn0PeXx/R.skf7WR.PWLfLQymxPp4DBUvwwnKWceu
--  Lovely2God  = $2y$10$aLC/GlscC9hyn7PGTLtceeNaIrtSBOb3nN1zNq3WbGOoqUlq8t9n2
--  Stephen123  = $2y$10$s4HcGHA15OWVOUKHrc1zU.PiP3ZCYf0BGh.JLgCJR0R18zy0tzJzO
--  isnm2026    = $2y$10$.qu0222yVPH6N5l6Tpip8O88DlQKRUB3YaWTRFKKX0w15dO05Zo8u
--  Isnm2026    = $2y$10$fz2wUW.m5nI4yttBCyCq9.5GIyWWMN89159ZbKVeyhmJSX7PCxm8i
--  Alexis2026  = $2y$10$iC0FRXpscCKuQDj8t/RQNe8HP1szuxTU/O6apCjZfK2QnEIfIwYIG
--  isnm4life   = $2y$10$n3PwvLoehqkf9IJa1FUWHuWqTjjq1RXnPJqsKp8H30Nm2Iu0eQk6K
--  Life2save   = $2y$10$8nuoLwahPQEikvcIncW/R.TxQQGFVDWDWI9EVL3ZROdhtffuXd3Nu
--  Isnm4life   = $2y$10$EX4Ohm6Um/gOFU9y9Nyo..PnwqwdkL1XTBvodZ7G.eu1HwUBWrdzS
--  safty1st    = $2y$10$yemIoQdAtXgk3ZqeN.MvauNi3xNGe3VH3s8MmG33pgyiWVoJe02AW
--  2268926931  = $2y$10$jsElwWv0bhBo8qOF3/47ze9HEwR.7UiRvmgI0wE2G4gkylrbTnIG.
--  bursar@isnm = $2y$10$RRbhT2PyL7yHVzJd5El5We3U9PAxBI1CES7x9OlTLK.MgGm9K.F7a
-- ============================================================

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
-- computer-lab / Techno123
('ICT-LAB', 'Computer Lab Manager',
 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$urZgn8VmD81qHF6.lQ3eoOJD4TdHGdreDzfotucSdrIgmEqDqeHgu',
 'Computer Lab Manager', 'ICT Department',
 (SELECT id FROM staff_roles WHERE role_name='Director ICT' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- directorgeneral / DorisJoy2026
('DG-001', 'Doris Joy',
 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$04.DbEy7SaXjwn0PeXx/R.skf7WR.PWLfLQymxPp4DBUvwwnKWceu',
 'Director General', 'Executive Office',
 (SELECT id FROM staff_roles WHERE role_name='Director General' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- ceo / Lovely2God
('CEO-001', 'Chief Executive Officer',
 'ceo@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$aLC/GlscC9hyn7PGTLtceeNaIrtSBOb3nN1zNq3WbGOoqUlq8t9n2',
 'Chief Executive Officer', 'Executive Office',
 (SELECT id FROM staff_roles WHERE role_name='CEO' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- directoracademic / Stephen123
('DA-001', 'Mr. Stephen Bywaka',
 'directoracademic@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$s4HcGHA15OWVOUKHrc1zU.PiP3ZCYf0BGh.JLgCJR0R18zy0tzJzO',
 'Director Academics', 'Academic Affairs',
 (SELECT id FROM staff_roles WHERE role_name='Director Academics' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- finance / DorisJoy2026
('DF-001', 'Director Finance',
 'finance@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$04.DbEy7SaXjwn0PeXx/R.skf7WR.PWLfLQymxPp4DBUvwwnKWceu',
 'Director Finance', 'Finance Department',
 (SELECT id FROM staff_roles WHERE role_name='Director Finance' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- principal / isnm2026
('SP-001', 'School Principal',
 'principal@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$.qu0222yVPH6N5l6Tpip8O88DlQKRUB3YaWTRFKKX0w15dO05Zo8u',
 'School Principal', 'Academic Affairs',
 (SELECT id FROM staff_roles WHERE role_name='School Principal' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- dep-principal / Isnm2026
('DP-001', 'Deputy Principal',
 'dep-principal@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$fz2wUW.m5nI4yttBCyCq9.5GIyWWMN89159ZbKVeyhmJSX7PCxm8i',
 'Deputy Principal', 'Academic Affairs',
 (SELECT id FROM staff_roles WHERE role_name='Deputy Principal' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- academicregistrar / Lovely2God
('AR-001', 'Academic Registrar',
 'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$aLC/GlscC9hyn7PGTLtceeNaIrtSBOb3nN1zNq3WbGOoqUlq8t9n2',
 'Academic Registrar', 'Academic Affairs',
 (SELECT id FROM staff_roles WHERE role_name='Academic Registrar' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- hr-manager / Alexis2026
('HR-001', 'HR Manager',
 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$iC0FRXpscCKuQDj8t/RQNe8HP1szuxTU/O6apCjZfK2QnEIfIwYIG',
 'HR Manager', 'Human Resources',
 (SELECT id FROM staff_roles WHERE role_name='HR Manager' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- secretary / Lovely2God
('SEC-001', 'School Secretary',
 'secretary@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$aLC/GlscC9hyn7PGTLtceeNaIrtSBOb3nN1zNq3WbGOoqUlq8t9n2',
 'School Secretary', 'Administrative Office',
 (SELECT id FROM staff_roles WHERE role_name='School Secretary' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- library / isnm2026
('LIB-001', 'School Librarian',
 'library@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$.qu0222yVPH6N5l6Tpip8O88DlQKRUB3YaWTRFKKX0w15dO05Zo8u',
 'School Librarian', 'Library Services',
 (SELECT id FROM staff_roles WHERE role_name='School Librarian' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- nursing-dep / isnm4life
('HN-001', 'Head of Nursing',
 'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$n3PwvLoehqkf9IJa1FUWHuWqTjjq1RXnPJqsKp8H30Nm2Iu0eQk6K',
 'Head of Nursing', 'Nursing Department',
 (SELECT id FROM staff_roles WHERE role_name='Head Nursing' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- midwifery-dep / Life2save
('HM-001', 'Head of Midwifery',
 'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$8nuoLwahPQEikvcIncW/R.TxQQGFVDWDWI9EVL3ZROdhtffuXd3Nu',
 'Head of Midwifery', 'Midwifery Department',
 (SELECT id FROM staff_roles WHERE role_name='Head Midwifery' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- senior-lecturers / isnm2026
('SL-001', 'Senior Lecturers',
 'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$.qu0222yVPH6N5l6Tpip8O88DlQKRUB3YaWTRFKKX0w15dO05Zo8u',
 'Senior Lecturer', 'Academic Affairs',
 (SELECT id FROM staff_roles WHERE role_name='Senior Lecturers' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- lecturers / Isnm4life
('LEC-001', 'Lecturers',
 'lecturers@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$EX4Ohm6Um/gOFU9y9Nyo..PnwqwdkL1XTBvodZ7G.eu1HwUBWrdzS',
 'Lecturer', 'Academic Affairs',
 (SELECT id FROM staff_roles WHERE role_name='Lecturers' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- matron / Isnm2026
('MAT-001', 'Matron',
 'matron@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$fz2wUW.m5nI4yttBCyCq9.5GIyWWMN89159ZbKVeyhmJSX7PCxm8i',
 'Matron', 'Student Affairs',
 (SELECT id FROM staff_roles WHERE role_name='Matrons' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- warden / Lovely2God
('WDN-001', 'Warden',
 'warden@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$aLC/GlscC9hyn7PGTLtceeNaIrtSBOb3nN1zNq3WbGOoqUlq8t9n2',
 'Warden', 'Student Affairs',
 (SELECT id FROM staff_roles WHERE role_name='Wardens' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- sickbay / isnm2026
('SKB-001', 'Sickbay Officer',
 'sickbay@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$.qu0222yVPH6N5l6Tpip8O88DlQKRUB3YaWTRFKKX0w15dO05Zo8u',
 'Sickbay Officer', 'Health Services',
 (SELECT id FROM staff_roles WHERE role_name='Sickbay' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- drivers / isnm4life
('DRV-001', 'Driver',
 'drivers@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$n3PwvLoehqkf9IJa1FUWHuWqTjjq1RXnPJqsKp8H30Nm2Iu0eQk6K',
 'Driver', 'Transport',
 (SELECT id FROM staff_roles WHERE role_name='Drivers' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- security / safty1st
('SEC-G01', 'Security Officer',
 'security@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$yemIoQdAtXgk3ZqeN.MvauNi3xNGe3VH3s8MmG33pgyiWVoJe02AW',
 'Security Officer', 'Security Services',
 (SELECT id FROM staff_roles WHERE role_name='Security' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- store / Isnm4life
('STK-001', 'Store Keeper',
 'store@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$EX4Ohm6Um/gOFU9y9Nyo..PnwqwdkL1XTBvodZ7G.eu1HwUBWrdzS',
 'Store Keeper', 'Facilities Management',
 (SELECT id FROM staff_roles WHERE role_name='Store Keeper' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- guildpresident / isnm4life
('GP-001', 'Guild President',
 'guildpresident@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$n3PwvLoehqkf9IJa1FUWHuWqTjjq1RXnPJqsKp8H30Nm2Iu0eQk6K',
 'Guild President', 'Student Affairs',
 (SELECT id FROM staff_roles WHERE role_name='Guild President' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- admissions / 2268926931
('ADM-001', 'Director Admissions & Requirements',
 'admissions@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$jsElwWv0bhBo8qOF3/47ze9HEwR.7UiRvmgI0wE2G4gkylrbTnIG.',
 'Director Admissions & Requirements', 'Admissions Office',
 (SELECT id FROM staff_roles WHERE role_name='Director Admissions' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- dannybict / Lovely2God
('ICT-002', 'Danny (ICT Director)',
 'dannybict@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$aLC/GlscC9hyn7PGTLtceeNaIrtSBOb3nN1zNq3WbGOoqUlq8t9n2',
 'Director ICT', 'ICT Department',
 (SELECT id FROM staff_roles WHERE role_name='Director ICT' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- bursar / bursar@isnm
('BUR-001', 'School Bursar',
 'bursar@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$RRbhT2PyL7yHVzJd5El5We3U9PAxBI1CES7x9OlTLK.MgGm9K.F7a',
 'School Bursar', 'Finance Department',
 (SELECT id FROM staff_roles WHERE role_name='School Bursar' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW());

SET FOREIGN_KEY_CHECKS = 1;

-- Verification
SELECT staff_id, full_name, email, position, status FROM staff ORDER BY id;
SELECT 'Credentials loaded successfully.' AS result;
