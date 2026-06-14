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
(1,  'DG001',    'Director General',      'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug',   '$2y$10$X.lXpTdNh5ggsfqAHXui7eNQWY.v3u9YXgPIeboCslvnyKQHscCma', NULL, 'Director General',       'Executive Office',        1,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

(2,  'CEO001',    'CEO',      'ceo@igangaschoolofnursingandmidwifery.ac.ug',   '$2y$10$oOF8zJsDSinDlEEpyNQW1Olzvthsx01K14mcRJ6g46QGf9NWtwlbG', NULL, 'Chief Executive Officer',       'Executive Office',        3,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

(3,  'SP001',    'School Principal',      'principal@igangaschoolofnursingandmidwifery.ac.ug',   '$2y$10$Cp3pXSnKI7N8RwKW/mkv0.NYXNR5rhtqQSI6pSLD2gPIhwrLXFkJ2', NULL, 'School Principal',       'Academic Affairs',        2,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

(4,  'SEC001',    'School Secretary',      'secretary@igangaschoolofnursingandmidwifery.ac.ug',   '$2y$10$E9Cei.OqjOQz3CGpJBSERucBEOJb0M30TBTarqgo/xCcZnBjL1FEi', NULL, 'School Secretary',       'Administrative Office',        21,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

(5,  'AR001',    'Academic Registrar',      'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug',   '$2y$10$bD5xC0mnoRtn/PD.uV1O1OZxxfKRBY9ZW48I/r/lbsekf1YdX03Te', NULL, 'Academic Registrar',       'Academic Affairs',        8,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

(6,  'BUR001',    'School Bursar',      'bursar@igangaschoolofnursingandmidwifery.ac.ug',   '$2y$10$KYdWhNmLrmiEwhKawN/k7e87a23PhwKZdO/58l7N37H2coEON.JmK', NULL, 'School Bursar',       'Finance Department',        9,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

(7,  'HR001',    'HR Manager',      'hr-manager@igangaschoolofnursingandmidwifery.ac.ug',   '$2y$10$emL.nPc5VwNBc1LCli0iiOL7Ln.sXFLgGL7iUsmpsMum0qhpzkLNm', NULL, 'HR Manager',       'Human Resources',        7,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

(8,  'DA001',    'Director Academics',      'directoracademic@igangaschoolofnursingandmidwifery.ac.ug',   '$2y$10$XDQfi6Y/7tJWsoLLG2Fdy.DwRX5MeK1G3YYgckRNup5yWvg8/WoYW', NULL, 'Director Academics',       'Academic Affairs',        4,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

(9,  'DI001',    'Director ICT',      'director@igangaschoolofnursingandmidwifery.ac.ug',   '$2y$10$XaHUZTStKYVHLJJmFo2nFeXjVp6e2bZHDP26j5b6nNQWS/NKSpgTO', NULL, 'Director ICT',       'Information Technology',        6,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

(10,  'DF001',    'Director Finance',      'finance@igangaschoolofnursingandmidwifery.ac.ug',   '$2y$10$i1FOrtvASHRIhvDvfnlLoeYuG4xqmd0rA3KhBrtDQS92lsZWjhK6y', NULL, 'Director Finance',       'Finance Department',        5,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

(11,  'LIB001',    'School Librarian',      'library@igangaschoolofnursingandmidwifery.ac.ug',   '$2y$10$bgfRvup4AOTPpTORkipHVuO4PqVatVkUrElclruBbHuOu/OZt1LTK', NULL, 'School Librarian',       'Library Services',        10,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

(12,  'HN001',    'Head Nursing',      'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug',   '$2y$10$tqEl1jqQuqIKCSPKwZGf2esNsGHFNWlXaArKdlS4LRRWuDMnLuF.C', NULL, 'Head Nursing',       'Nursing Department',        11,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

(13,  'HM001',    'Head Midwifery',      'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug',   '$2y$10$7ohkcbLjX3Mfvj0N1V6ZSOw6IHEI65dyLnoaolfLD6ie8Y9G1j.Vi', NULL, 'Head Midwifery',       'Midwifery Department',        12,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

(14,  'LEC001',    'Lecturers',      'lecturers@igangaschoolofnursingandmidwifery.ac.ug',   '$2y$10$dhhUXK4feTNpx25pflVdKuQGV3JXNo5Y4OaeV04Ts4spTCOAfwI.q', NULL, 'Lecturer',       'Academic Affairs',        13,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

(15,  'SLE001',    'Senior Lecturers',      'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug',   '$2y$10$TkjYmEP9QB/OS/Q7/2ar/OH7IjipUxOu.9zIwJmMPqPiYnQQ.l56C', NULL, 'Senior Lecturer',       'Academic Affairs',        14,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

(16,  'NTS001',    'Non-Teaching Staff',      'nonteaching@isnm.ac.ug',   '$2y$10$ERD2K/gL6rapzF2eS.tJ.uvtjoJNFAR2yKFnwuQgXtPIqny5wq/FC', NULL, 'Non-Teaching Staff',       'Administrative',        15,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

(17,  'LAB001',    'Sickbay',      'sickbay@igangaschoolofnursingandmidwifery.ac.ug',   '$2y$10$2zPgtrLzpZvFJ7yn3pl8yO/97fIhrc0PA/0YnfKhge976E/YoGcIO', NULL, 'Sickbay',       'Support',        16,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

(18,  'MAT001',    'Matrons',      'matron@igangaschoolofnursingandmidwifery.ac.ug',   '$2y$10$IQprqqOeVEg7paCUDcm8muZ7SqlsGLkPS7RuRrgiyVVujbZPzJ3xu', NULL, 'Matrons',       'Student Affairs',        17,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

(19,  'SECUR001',    'Security',      'security@igangaschoolofnursingandmidwifery.ac.ug',   '$2y$10$mx2qQBZi78fztv0/2gAw.uYy27NJPoSDbnObtjPbDoRoAo/Y6hBgO', NULL, 'Security',       'Security Services',        18,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

(20,  'DRV001',    'Drivers',      'drivers@igangaschoolofnursingandmidwifery.ac.ug',   '$2y$10$SnRs4VJG5bw9mhOB6rWPnOX.aI9hSJkbyC.p1S4GiHEc04SzEb5RO', NULL, 'Drivers',       'Transport',        19,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

(21,  'WDN001',    'Wardens',      'warden@igangaschoolofnursingandmidwifery.ac.ug',   '$2y$10$gDhSyHsv66r7ZZQITI/bAerFTfvM7g7oy8rHUBiQJu//1C77bIuui', NULL, 'Wardens',       'Student Affairs',        20,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

(22,  'DP001',    'Deputy Principal',      'dep-principal@igangaschoolofnursingandmidwifery.ac.ug',   '$2y$10$BAJsXI912bCy.h11Hg9NSONFful3wmjb9rER1Pre6PXM.1KK0J362', NULL, 'Deputy Principal',       'Academic Affairs',        22,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

(23,  'STK001',    'Store Keeper',      'store@igangaschoolofnursingandmidwifery.ac.ug',   '$2y$10$YCEStXKaChIKeIm1z5z96eevygQo49UQnVwavj/EeJ7TkZA3fV47y', NULL, 'Store Keeper',       'Facilities Management',        25,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

(24,  'BURS002',    'Bursar Assistant',      'bursar.assistant@isnm.ac.ug',   '$2y$10$wCF6V.cK37YkD.THcsDA2u75wY5TAYnRpVdkyaVS99rnBGSrIecIS', NULL, 'Bursar',       'Finance Department',        23,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

(25,  'CL001',    'Computer Lab Manager',      'computer-lab@igangaschoolofnursingandmidwifery.ac.ug',   '$2y$10$u.TVxssJ57A2H.VpnDPGweym0TSZ4vxu9cpq5lG8y9a9unZ3IG6C6', NULL, 'Computer Lab Manager',       'Information Technology',        6,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

(26,  'GP001',    'Guild President',      'guildpresident@igangaschoolofnursingandmidwifery.ac.ug',   '$2y$10$enGISRD6jn2B3uYkXdiDYuDla2uOcvxY2XUR9y.keGNPLuIAsFkbS', NULL, 'Guild President',       'Student Affairs',        NULL,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

(27,  'ADM001',    'Admissions Officer',      'admissions@igangaschoolofnursingandmidwifery.ac.ug',   '$2y$10$VxwhckP07chkAOiHKaqTruC0ngqGeGT2/WufDswX.j1XHp.hrMgRG', NULL, 'Admissions Officer',       'Academic Affairs',        8,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW()),

(28,  'DAN001',    'Computer Director',      'dannybict@igangaschoolofnursingandmidwifery.ac.ug',   '$2y$10$btPNaIqbjdY2TFPfYjT3G.0tXi2PQ0ASQVHmjQm0FW1rLkU5qWcDC', NULL, 'Computer Director',       'Information Technology',        6,  'Active', '2026-01-01', 0, NULL, 1, 0, 0, NOW(), NOW());

-- Step 4: Restore dashboard access entries
DELETE FROM `staff_dashboard_access` WHERE 1=1;
ALTER TABLE `staff_dashboard_access` AUTO_INCREMENT = 1;

INSERT INTO `staff_dashboard_access` (`staff_id`, `dashboard_path`, `access_level`, `granted_by`, `granted_at`, `is_active`) VALUES
(1,  'dashboards/director-general.php',   'Full', 1, NOW(), 1),
(2,  'dashboards/ceo.php',   'Full', 1, NOW(), 1),
(3,  'dashboards/school-principal.php',   'Full', 1, NOW(), 1),
(4,  'dashboards/school-secretary.php',   'Full', 1, NOW(), 1),
(5,  'dashboards/academic-registrar.php',   'Full', 1, NOW(), 1),
(6,  'bursar_dashboard.php',   'Full', 1, NOW(), 1),
(7,  'dashboards/hr-manager.php',   'Full', 1, NOW(), 1),
(8,  'dashboards/director-academics.php',   'Full', 1, NOW(), 1),
(9,  'dashboards/director-ict.php',   'Full', 1, NOW(), 1),
(10,  'dashboards/director-finance.php',   'Full', 1, NOW(), 1),
(11,  'dashboards/school-librarian.php',   'Full', 1, NOW(), 1),
(12,  'dashboards/head-nursing.php',   'Full', 1, NOW(), 1),
(13,  'dashboards/head-midwifery.php',   'Full', 1, NOW(), 1),
(14,  'dashboards/lecturers.php',   'Full', 1, NOW(), 1),
(15,  'dashboards/senior-lecturers.php',   'Full', 1, NOW(), 1),
(16,  'dashboards/non-teaching-staff.php',   'Full', 1, NOW(), 1),
(17,  'dashboards/sickbay.php',   'Full', 1, NOW(), 1),
(18,  'dashboards/matrons.php',   'Full', 1, NOW(), 1),
(19,  'dashboards/security.php',   'Full', 1, NOW(), 1),
(20,  'dashboards/drivers.php',   'Full', 1, NOW(), 1),
(21,  'dashboards/wardens.php',   'Full', 1, NOW(), 1),
(22,  'dashboards/deputy-principal.php',   'Full', 1, NOW(), 1),
(23,  'dashboards/storekeeper.php',   'Full', 1, NOW(), 1),
(24,  'bursar_dashboard.php',   'Full', 1, NOW(), 1),
(25,  'dashboards/director-ict.php',   'Full', 1, NOW(), 1),
(26,  'dashboards/guild-president.php',   'Full', 1, NOW(), 1),
(27,  'dashboards/academic-registrar.php',   'Full', 1, NOW(), 1),
(28,  'dashboards/director-ict.php',   'Full', 1, NOW(), 1);

-- Done!
SELECT CONCAT('✅ ', COUNT(*), ' staff records inserted') AS result FROM staff;
