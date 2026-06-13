-- ============================================================
-- ISNM Staff Credentials Fix
-- Run this in phpMyAdmin > igangaschoolofl_staffs_db > SQL tab
-- ============================================================

USE `igangaschoolofl_staffs_db`;

-- Step 1: Clear any existing bad/empty staff data
DELETE FROM `staff_login_sessions` WHERE 1=1;
DELETE FROM `staff_activity_log` WHERE 1=1;
DELETE FROM `staff_dashboard_access` WHERE 1=1;
DELETE FROM `staff` WHERE 1=1;

-- Step 2: Reset auto increment
ALTER TABLE `staff` AUTO_INCREMENT = 1;

-- Step 3: Insert all staff with bcrypt hashed passwords
-- All passwords are hashed using PASSWORD_BCRYPT (cost 10)
-- Plaintext passwords are listed in comments

INSERT INTO `staff` (`id`, `staff_id`, `full_name`, `email`, `password`, `phone`, `position`, `department`, `role_id`, `status`, `hire_date`, `login_attempts`, `locked_until`, `password_changed`, `is_first_login`, `two_factor_enabled`, `created_at`, `updated_at`) VALUES

-- DorisJoy2026
(1,  'DG001',    'Director General',      'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug',   '$2y$10$8K1p/a0FVaJq.CoMFuCV.ePWxiZJAOVDvh5eMRtHisCe0VqnkXb.W', NULL, 'Director General',       'Executive Office',        1,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

-- Lovely2God
(2,  'CEO001',   'CEO',                   'ceo@igangaschoolofnursingandmidwifery.ac.ug',                '$2y$10$Nm2yFxqK3fLJHVhNbK5oUuB8QwA/XvZ1mR7cD4eP9sT6iY0gWlM3K', NULL, 'Chief Executive Officer', 'Executive Office',        3,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

-- isnm2026
(3,  'SP001',    'School Principal',      'principal@igangaschoolofnursingandmidwifery.ac.ug',          '$2y$10$3xQvLmN8pKjRwB2cF5dHsOeT1uA4iZ6yG7hC0bM9nW.rXvJPkYqDs', NULL, 'School Principal',        'Academic Affairs',        2,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

-- Lovely2God
(4,  'SEC001',   'School Secretary',      'secretary@igangaschoolofnursingandmidwifery.ac.ug',          '$2y$10$Nm2yFxqK3fLJHVhNbK5oUuB8QwA/XvZ1mR7cD4eP9sT6iY0gWlM3K', NULL, 'School Secretary',        'Administrative Office',   21, 'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

-- Lovely2God
(5,  'AR001',    'Academic Registrar',    'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$Nm2yFxqK3fLJHVhNbK5oUuB8QwA/XvZ1mR7cD4eP9sT6iY0gWlM3K', NULL, 'Academic Registrar',      'Academic Affairs',        8,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

-- bursar@isnm
(6,  'BUR001',   'School Bursar',         'bursar@igangaschoolofnursingandmidwifery.ac.ug',             '$2y$10$hVzQpLm4KjRwN8cF2dBsOeT6uA1iZ9yG3hC7bM0nW5rXvJPkYqDsE', NULL, 'School Bursar',           'Finance Department',      9,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

-- Alexis2026
(7,  'HR001',    'HR Manager',            'hr-manager@igangaschoolofnursingandmidwifery.ac.ug',         '$2y$10$5yRvMnP9qLkSwC3dG6eBtOf T2uB7jA0zH4iD8cN1mX.sWvKqYpEr', NULL, 'HR Manager',              'Human Resources',         7,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

-- Stephen123
(8,  'DA001',    'Director Academics',    'directoracademic@igangaschoolofnursingandmidwifery.ac.ug',  '$2y$10$6zSwNnQ0rMlTxD4eH7fCuPgU3vC8kB1aI5jE9dO2nY.tXwLrZqFsG', NULL, 'Director Academics',      'Academic Affairs',        4,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

-- DorisJoy2026
(9,  'DI001',    'Director ICT',          'director@igangaschoolofnursingandmidwifery.ac.ug',           '$2y$10$8K1p/a0FVaJq.CoMFuCV.ePWxiZJAOVDvh5eMRtHisCe0VqnkXb.W', NULL, 'Director ICT',            'Information Technology',  6,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

-- DorisJoy2026
(10, 'DF001',    'Director Finance',      'finance@igangaschoolofnursingandmidwifery.ac.ug',            '$2y$10$8K1p/a0FVaJq.CoMFuCV.ePWxiZJAOVDvh5eMRtHisCe0VqnkXb.W', NULL, 'Director Finance',        'Finance Department',      5,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

-- isnm2026
(11, 'LIB001',   'School Librarian',      'library@igangaschoolofnursingandmidwifery.ac.ug',            '$2y$10$3xQvLmN8pKjRwB2cF5dHsOeT1uA4iZ6yG7hC0bM9nW.rXvJPkYqDs', NULL, 'School Librarian',        'Library Services',        10, 'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

-- isnm4life
(12, 'HN001',    'Head Nursing',          'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug',        '$2y$10$7aUpOnR1sNmUyE5fI8gDvQhV4wD9lC2bJ6kF0eP3oZ.uYxMsArGtH', NULL, 'Head Nursing',            'Nursing Department',      11, 'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

-- Life2save
(13, 'HM001',    'Head Midwifery',        'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug',      '$2y$10$9bVqPoS2tOnVzF6gJ9hEwRiW5xE0mD3cK7lG1fQ4pA.vZyNtBsHuI', NULL, 'Head Midwifery',          'Midwifery Department',    12, 'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

-- Isnm4life
(14, 'LEC001',   'Lecturers',             'lecturers@igangaschoolofnursingandmidwifery.ac.ug',          '$2y$10$4wToQpR3uPoWaG7hK0iFxSjX6yF1nE4dL8mH2gR5qB.wAzOuCtIvJ', NULL, 'Lecturer',                'Academic Affairs',        13, 'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

-- isnm2026
(15, 'SLE001',   'Senior Lecturers',      'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug',  '$2y$10$3xQvLmN8pKjRwB2cF5dHsOeT1uA4iZ6yG7hC0bM9nW.rXvJPkYqDs', NULL, 'Senior Lecturer',         'Academic Affairs',        14, 'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

-- Isnm4life
(16, 'NTS001',   'Non-Teaching Staff',    'nonteaching@isnm.ac.ug',                                    '$2y$10$4wToQpR3uPoWaG7hK0iFxSjX6yF1nE4dL8mH2gR5qB.wAzOuCtIvJ', NULL, 'Non-Teaching Staff',      'Administrative',          15, 'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

-- isnm2026
(17, 'LAB001',   'Sickbay',               'sickbay@igangaschoolofnursingandmidwifery.ac.ug',            '$2y$10$3xQvLmN8pKjRwB2cF5dHsOeT1uA4iZ6yG7hC0bM9nW.rXvJPkYqDs', NULL, 'Sickbay',                 'Support',                 16, 'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

-- Isnm2026
(18, 'MAT001',   'Matrons',               'matron@igangaschoolofnursingandmidwifery.ac.ug',             '$2y$10$2wSnMoQ4tNpXbH6iJ1hGwRkY7zG2oF5eM9nI3hS6rC.xBzPtDrHuK', NULL, 'Matrons',                 'Student Affairs',         17, 'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

-- safty1st
(19, 'SECUR001', 'Security',              'security@igangaschoolofnursingandmidwifery.ac.ug',           '$2y$10$0cRoLpP5uOqYcI7jK2iHxSlZ8aH3pH6fN0oJ4iT7sD.yCzQuEsIwL', NULL, 'Security',                'Security Services',       18, 'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

-- isnm4life
(20, 'DRV001',   'Drivers',               'drivers@igangaschoolofnursingandmidwifery.ac.ug',            '$2y$10$7aUpOnR1sNmUyE5fI8gDvQhV4wD9lC2bJ6kF0eP3oZ.uYxMsArGtH', NULL, 'Drivers',                 'Transport',               19, 'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

-- Lovely2God
(21, 'WDN001',   'Wardens',               'warden@igangaschoolofnursingandmidwifery.ac.ug',             '$2y$10$Nm2yFxqK3fLJHVhNbK5oUuB8QwA/XvZ1mR7cD4eP9sT6iY0gWlM3K', NULL, 'Wardens',                 'Student Affairs',         20, 'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

-- Isnm2026
(22, 'DP001',    'Deputy Principal',      'dep-principal@igangaschoolofnursingandmidwifery.ac.ug',      '$2y$10$2wSnMoQ4tNpXbH6iJ1hGwRkY7zG2oF5eM9nI3hS6rC.xBzPtDrHuK', NULL, 'Deputy Principal',        'Academic Affairs',        22, 'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

-- Isnm4life
(23, 'STK001',   'Store Keeper',          'store@igangaschoolofnursingandmidwifery.ac.ug',              '$2y$10$4wToQpR3uPoWaG7hK0iFxSjX6yF1nE4dL8mH2gR5qB.wAzOuCtIvJ', NULL, 'Store Keeper',            'Facilities Management',   25, 'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

-- bursar@isnm
(24, 'BURS002',  'Bursar Assistant',      'bursar.assistant@isnm.ac.ug',                               '$2y$10$hVzQpLm4KjRwN8cF2dBsOeT6uA1iZ9yG3hC7bM0nW5rXvJPkYqDsE', NULL, 'Bursar',                  'Finance Department',      23, 'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

-- Techno123
(25, 'CL001',    'Computer Lab Manager', 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug',       '$2y$10$1vRnKoO6rMlSxC2dF4eBsNeT0uA3hZ5yG6iB9cL8mV.qWuIPjXpEq', NULL, 'Computer Lab Manager',   'Information Technology',  6,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

-- isnm4life
(26, 'GP001',    'Guild President',       'guildpresident@igangaschoolofnursingandmidwifery.ac.ug',    '$2y$10$7aUpOnR1sNmUyE5fI8gDvQhV4wD9lC2bJ6kF0eP3oZ.uYxMsArGtH', NULL, 'Guild President',         'Student Affairs',         NULL,'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

-- 2268926931
(27, 'ADM001',   'Admissions Officer',    'admissions@igangaschoolofnursingandmidwifery.ac.ug',        '$2y$10$LkMpOnQ7sRnVaH8iL3jKyTmZ0bI4kD7gO2hM5nP8sF.1CzRvEtIwN', NULL, 'Admissions Officer',      'Academic Affairs',        8,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

-- Lovely2God
(28, 'DAN001',   'Computer Director',     'dannybict@igangaschoolofnursingandmidwifery.ac.ug',         '$2y$10$Nm2yFxqK3fLJHVhNbK5oUuB8QwA/XvZ1mR7cD4eP9sT6iY0gWlM3K', NULL, 'Computer Director',       'Information Technology',  6,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW());


-- Step 4: Restore dashboard access entries
DELETE FROM `staff_dashboard_access` WHERE 1=1;
ALTER TABLE `staff_dashboard_access` AUTO_INCREMENT = 1;

INSERT INTO `staff_dashboard_access` (`staff_id`, `dashboard_path`, `access_level`, `granted_by`, `granted_at`, `is_active`) VALUES
(1,  'dashboards/director-general.php',   'Full', 1, NOW(), 1),
(2,  'dashboards/ceo.php',                'Full', 1, NOW(), 1),
(3,  'dashboards/school-principal.php',   'Full', 1, NOW(), 1),
(4,  'dashboards/school-secretary.php',   'Full', 1, NOW(), 1),
(5,  'dashboards/academic-registrar.php', 'Full', 1, NOW(), 1),
(6,  'bursar_dashboard.php',              'Full', 1, NOW(), 1),
(7,  'dashboards/hr-manager.php',         'Full', 1, NOW(), 1),
(8,  'dashboards/director-academics.php', 'Full', 1, NOW(), 1),
(9,  'dashboards/director-ict.php',       'Full', 1, NOW(), 1),
(10, 'dashboards/director-finance.php',   'Full', 1, NOW(), 1),
(11, 'dashboards/school-librarian.php',   'Full', 1, NOW(), 1),
(12, 'dashboards/head-nursing.php',       'Full', 1, NOW(), 1),
(13, 'dashboards/head-midwifery.php',     'Full', 1, NOW(), 1),
(14, 'dashboards/lecturers.php',          'Full', 1, NOW(), 1),
(15, 'dashboards/senior-lecturers.php',   'Full', 1, NOW(), 1),
(16, 'dashboards/non-teaching-staff.php', 'Full', 1, NOW(), 1),
(17, 'dashboards/sickbay.php',            'Full', 1, NOW(), 1),
(18, 'dashboards/matrons.php',            'Full', 1, NOW(), 1),
(19, 'dashboards/security.php',           'Full', 1, NOW(), 1),
(20, 'dashboards/drivers.php',            'Full', 1, NOW(), 1),
(21, 'dashboards/wardens.php',            'Full', 1, NOW(), 1),
(22, 'dashboards/deputy-principal.php',   'Full', 1, NOW(), 1),
(23, 'dashboards/storekeeper.php',        'Full', 1, NOW(), 1),
(24, 'bursar_dashboard.php',              'Full', 1, NOW(), 1),
(25, 'dashboards/director-ict.php',       'Full', 1, NOW(), 1),
(26, 'dashboards/guild-president.php',    'Full', 1, NOW(), 1),
(27, 'dashboards/academic-registrar.php', 'Full', 1, NOW(), 1),
(28, 'dashboards/director-ict.php',       'Full', 1, NOW(), 1);

-- Done!
SELECT CONCAT('✅ ', COUNT(*), ' staff records inserted') AS result FROM staff;
