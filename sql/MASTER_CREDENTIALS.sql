-- ============================================================
-- ISNM MASTER CREDENTIALS UPDATE
-- All passwords updated to match user specifications
-- Run this AFTER 04_final_complete_staffs_database.sql
-- ============================================================
USE `igangaschoolofl_staffs_db`;

-- Password hash legend:
-- Techno123        => $2y$10$urZgn8VmD81qHF6.lQ3eoOJD4TdHGdreDzfotucSdrIgmEqDqeHgu
-- DorisJoy2026     => $2y$10$04.DbEy7SaXjwn0PeXx/R.skf7WR.PWLfLQymxPp4DBUvwwnKWceu
-- Lovely2God       => $2y$10$aLC/GlscC9hyn7PGTLtceeNaIrtSBOb3nN1zNq3WbGOoqUlq8t9n2
-- Stephen123       => $2y$10$s4HcGHA15OWVOUKHrc1zU.PiP3ZCYf0BGh.JLgCJR0R18zy0tzJzO
-- isnm2026         => $2y$10$.qu0222yVPH6N5l6Tpip8O88DlQKRUB3YaWTRFKKX0w15dO05Zo8u
-- Isnm2026         => $2y$10$fz2wUW.m5nI4yttBCyCq9.5GIyWWMN89159ZbKVeyhmJSX7PCxm8i
-- Alexis2026       => $2y$10$iC0FRXpscCKuQDj8t/RQNe8HP1szuxTU/O6apCjZfK2QnEIfIwYIG
-- isnm4life        => $2y$10$n3PwvLoehqkf9IJa1FUWHuWqTjjq1RXnPJqsKp8H30Nm2Iu0eQk6K
-- Life2save        => $2y$10$8nuoLwahPQEikvcIncW/R.TxQQGFVDWDWI9EVL3ZROdhtffuXd3Nu
-- Isnm4life        => $2y$10$EX4Ohm6Um/gOFU9y9Nyo..PnwqwdkL1XTBvodZ7G.eu1HwUBWrdzS
-- safty1st         => $2y$10$yemIoQdAtXgk3ZqeN.MvauNi3xNGe3VH3s8MmG33pgyiWVoJe02AW
-- 2268926931       => $2y$10$jsElwWv0bhBo8qOF3/47ze9HEwR.7UiRvmgI0wE2G4gkylrbTnIG.
-- bursar@isnm      => $2y$10$RRbhT2PyL7yHVzJd5El5We3U9PAxBI1CES7x9OlTLK.MgGm9K.F7a

-- Ensure roles exist for all departments
INSERT IGNORE INTO staff_roles (role_name, role_description, role_level, dashboard_path, permissions) VALUES
('Director General',              'Overall school administration - full access', 'Executive',      'dashboards/director-general.php',    '{"all":true,"super_admin":true}'),
('CEO',                           'Chief Executive Officer',                     'Executive',      'dashboards/ceo.php',                  '{"strategic":true,"financial":true}'),
('Director Academics',            'Academic programs oversight',                 'Management',     'dashboards/director-academics.php',   '{"academic":true,"curriculum":true}'),
('Director Finance',              'Financial management',                        'Management',     'dashboards/director-finance.php',     '{"financial":true,"budgeting":true}'),
('Director ICT',                  'ICT management',                              'Management',     'dashboards/director-ict.php',         '{"ict":true,"systems":true}'),
('School Principal',              'School leadership',                           'Executive',      'dashboards/school-principal.php',     '{"academic":true,"administrative":true}'),
('Deputy Principal',              'Assistant principal',                         'Management',     'dashboards/deputy-principal.php',     '{"academic":true,"administrative":true}'),
('Academic Registrar',            'Student registration and records',            'Academic',       'dashboards/academic-registrar.php',   '{"academic":true,"students":true,"registration":true}'),
('HR Manager',                    'Human resources',                             'Management',     'dashboards/hr-manager.php',           '{"hr":true,"staff":true}'),
('School Secretary',              'Administrative support',                      'Administrative', 'dashboards/school-secretary.php',     '{"administrative":true}'),
('School Librarian',              'Library management',                          'Support',        'dashboards/school-librarian.php',     '{"library":true}'),
('Head Nursing',                  'Nursing department head',                     'Academic',       'dashboards/head-nursing.php',         '{"nursing":true,"department":true}'),
('Head Midwifery',                'Midwifery department head',                   'Academic',       'dashboards/head-midwifery.php',       '{"midwifery":true,"department":true}'),
('Senior Lecturers',              'Senior teaching staff',                       'Academic',       'dashboards/senior-lecturers.php',     '{"teaching":true}'),
('Lecturers',                     'Teaching staff',                              'Academic',       'dashboards/lecturers.php',            '{"teaching":true}'),
('Matrons',                       'Student welfare',                             'Support',        'dashboards/matrons.php',              '{"student_welfare":true}'),
('Wardens',                       'Student discipline',                          'Support',        'dashboards/wardens.php',              '{"student_welfare":true,"discipline":true}'),
('Sickbay',                       'Medical support',                             'Support',        'dashboards/sickbay.php',              '{"healthcare":true}'),
('Drivers',                       'Transportation',                              'Support',        'dashboards/drivers.php',              '{"transportation":true}'),
('Security',                      'Campus security',                             'Support',        'dashboards/security.php',             '{"security":true}'),
('Store Keeper',                  'Store inventory',                             'Support',        'dashboards/storekeeper.php',          '{"store":true,"inventory":true}'),
('Guild President',               'Student guild',                               'Support',        'dashboards/guild-president.php',      '{"student_affairs":true}'),
('Director Admissions',           'Admissions and requirements',                 'Management',     'dashboards/director-admissions.php',  '{"admissions":true,"requirements":true}'),
('School Bursar',                 'Financial operations',                        'Administrative', 'bursar_dashboard.php',                '{"financial":true,"fees":true}')
ON DUPLICATE KEY UPDATE
    role_description = VALUES(role_description),
    dashboard_path   = VALUES(dashboard_path);

-- ============================================================
-- DELETE old staff entries to re-insert with correct emails/passwords
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
    'bursar@igangaschoolofnursingandmidwifery.ac.ug'
);

-- ============================================================
-- INSERT ALL STAFF WITH CORRECT CREDENTIALS
-- ============================================================
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES

-- Computer Lab / ICT
('ICT001', 'Computer Lab Manager',
 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$urZgn8VmD81qHF6.lQ3eoOJD4TdHGdreDzfotucSdrIgmEqDqeHgu',
 'Computer Lab Manager', 'ICT Department',
 (SELECT id FROM staff_roles WHERE role_name='Director ICT' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- Director General - Doris Joy
('DG001', 'Doris Joy',
 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$04.DbEy7SaXjwn0PeXx/R.skf7WR.PWLfLQymxPp4DBUvwwnKWceu',
 'Director General', 'Executive Office',
 (SELECT id FROM staff_roles WHERE role_name='Director General' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- CEO
('CEO001', 'Chief Executive Officer',
 'ceo@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$aLC/GlscC9hyn7PGTLtceeNaIrtSBOb3nN1zNq3WbGOoqUlq8t9n2',
 'CEO', 'Executive Office',
 (SELECT id FROM staff_roles WHERE role_name='CEO' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- Director Academics - Mr. Stephen Bywaka
('DA001', 'Mr. Stephen Bywaka',
 'directoracademic@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$s4HcGHA15OWVOUKHrc1zU.PiP3ZCYf0BGh.JLgCJR0R18zy0tzJzO',
 'Director Academics', 'Academic Affairs',
 (SELECT id FROM staff_roles WHERE role_name='Director Academics' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- Director Finance (uses DorisJoy2026)
('DF001', 'Director Finance',
 'finance@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$04.DbEy7SaXjwn0PeXx/R.skf7WR.PWLfLQymxPp4DBUvwwnKWceu',
 'Director Finance', 'Finance Department',
 (SELECT id FROM staff_roles WHERE role_name='Director Finance' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- School Principal
('SP001', 'School Principal',
 'principal@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$.qu0222yVPH6N5l6Tpip8O88DlQKRUB3YaWTRFKKX0w15dO05Zo8u',
 'School Principal', 'Academic Affairs',
 (SELECT id FROM staff_roles WHERE role_name='School Principal' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- Deputy Principal
('DP001', 'Deputy Principal',
 'dep-principal@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$fz2wUW.m5nI4yttBCyCq9.5GIyWWMN89159ZbKVeyhmJSX7PCxm8i',
 'Deputy Principal', 'Academic Affairs',
 (SELECT id FROM staff_roles WHERE role_name='Deputy Principal' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- Academic Registrar
('AR001', 'Academic Registrar',
 'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$aLC/GlscC9hyn7PGTLtceeNaIrtSBOb3nN1zNq3WbGOoqUlq8t9n2',
 'Academic Registrar', 'Academic Affairs',
 (SELECT id FROM staff_roles WHERE role_name='Academic Registrar' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- HR Manager
('HR001', 'HR Manager',
 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$iC0FRXpscCKuQDj8t/RQNe8HP1szuxTU/O6apCjZfK2QnEIfIwYIG',
 'HR Manager', 'Human Resources',
 (SELECT id FROM staff_roles WHERE role_name='HR Manager' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- School Secretary
('SEC001', 'School Secretary',
 'secretary@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$aLC/GlscC9hyn7PGTLtceeNaIrtSBOb3nN1zNq3WbGOoqUlq8t9n2',
 'School Secretary', 'Administrative Office',
 (SELECT id FROM staff_roles WHERE role_name='School Secretary' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- School Librarian
('LIB001', 'School Librarian',
 'library@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$.qu0222yVPH6N5l6Tpip8O88DlQKRUB3YaWTRFKKX0w15dO05Zo8u',
 'School Librarian', 'Library Services',
 (SELECT id FROM staff_roles WHERE role_name='School Librarian' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- Head Nursing
('HN001', 'Head of Nursing',
 'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$n3PwvLoehqkf9IJa1FUWHuWqTjjq1RXnPJqsKp8H30Nm2Iu0eQk6K',
 'Head of Nursing', 'Nursing Department',
 (SELECT id FROM staff_roles WHERE role_name='Head Nursing' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- Head Midwifery
('HM001', 'Head of Midwifery',
 'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$8nuoLwahPQEikvcIncW/R.TxQQGFVDWDWI9EVL3ZROdhtffuXd3Nu',
 'Head of Midwifery', 'Midwifery Department',
 (SELECT id FROM staff_roles WHERE role_name='Head Midwifery' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- Senior Lecturers
('SL001', 'Senior Lecturers',
 'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$.qu0222yVPH6N5l6Tpip8O88DlQKRUB3YaWTRFKKX0w15dO05Zo8u',
 'Senior Lecturer', 'Academic Affairs',
 (SELECT id FROM staff_roles WHERE role_name='Senior Lecturers' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- Lecturers
('LEC001', 'Lecturers',
 'lecturers@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$EX4Ohm6Um/gOFU9y9Nyo..PnwqwdkL1XTBvodZ7G.eu1HwUBWrdzS',
 'Lecturer', 'Academic Affairs',
 (SELECT id FROM staff_roles WHERE role_name='Lecturers' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- Matrons
('MAT001', 'Matron',
 'matron@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$fz2wUW.m5nI4yttBCyCq9.5GIyWWMN89159ZbKVeyhmJSX7PCxm8i',
 'Matron', 'Student Affairs',
 (SELECT id FROM staff_roles WHERE role_name='Matrons' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- Wardens
('WDN001', 'Warden',
 'warden@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$aLC/GlscC9hyn7PGTLtceeNaIrtSBOb3nN1zNq3WbGOoqUlq8t9n2',
 'Warden', 'Student Affairs',
 (SELECT id FROM staff_roles WHERE role_name='Wardens' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- Sickbay
('SKB001', 'Sickbay Officer',
 'sickbay@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$.qu0222yVPH6N5l6Tpip8O88DlQKRUB3YaWTRFKKX0w15dO05Zo8u',
 'Sickbay Officer', 'Health Services',
 (SELECT id FROM staff_roles WHERE role_name='Sickbay' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- Drivers
('DRV001', 'Driver',
 'drivers@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$n3PwvLoehqkf9IJa1FUWHuWqTjjq1RXnPJqsKp8H30Nm2Iu0eQk6K',
 'Driver', 'Transport',
 (SELECT id FROM staff_roles WHERE role_name='Drivers' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- Security
('SECUR001', 'Security Officer',
 'security@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$yemIoQdAtXgk3ZqeN.MvauNi3xNGe3VH3s8MmG33pgyiWVoJe02AW',
 'Security Officer', 'Security Services',
 (SELECT id FROM staff_roles WHERE role_name='Security' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- Store Keeper
('STK001', 'Store Keeper',
 'store@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$EX4Ohm6Um/gOFU9y9Nyo..PnwqwdkL1XTBvodZ7G.eu1HwUBWrdzS',
 'Store Keeper', 'Facilities Management',
 (SELECT id FROM staff_roles WHERE role_name='Store Keeper' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- Guild President
('GP001', 'Guild President',
 'guildpresident@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$n3PwvLoehqkf9IJa1FUWHuWqTjjq1RXnPJqsKp8H30Nm2Iu0eQk6K',
 'Guild President', 'Student Affairs',
 (SELECT id FROM staff_roles WHERE role_name='Guild President' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- Director Admissions & Requirements
('ADM001', 'Director Admissions',
 'admissions@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$jsElwWv0bhBo8qOF3/47ze9HEwR.7UiRvmgI0wE2G4gkylrbTnIG.',
 'Director Admissions & Requirements', 'Admissions Office',
 (SELECT id FROM staff_roles WHERE role_name='Director Admissions' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- Director ICT (Danny)
('ICT002', 'Danny (ICT Director)',
 'dannybict@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$aLC/GlscC9hyn7PGTLtceeNaIrtSBOb3nN1zNq3WbGOoqUlq8t9n2',
 'Director ICT', 'ICT Department',
 (SELECT id FROM staff_roles WHERE role_name='Director ICT' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW()),

-- School Bursar
('BUR001', 'School Bursar',
 'bursar@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$RRbhT2PyL7yHVzJd5El5We3U9PAxBI1CES7x9OlTLK.MgGm9K.F7a',
 'School Bursar', 'Finance Department',
 (SELECT id FROM staff_roles WHERE role_name='School Bursar' LIMIT 1),
 'Active', CURDATE(), 0, 1, NOW());

-- ============================================================
-- Ensure academic_registrar_activity_log table exists in students DB
-- ============================================================
-- (run separately in igangaschoolofl_students_db)

SELECT CONCAT('Staff count: ', COUNT(*)) AS status FROM staff;
SELECT email, position FROM staff ORDER BY id;
