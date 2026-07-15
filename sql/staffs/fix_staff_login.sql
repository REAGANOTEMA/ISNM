-- ============================================================
-- FIX: Staff Login - Missing Columns, Roles & Seed Data
-- Database: igangaschool_staffs
-- ============================================================
-- Run this in phpMyAdmin to fix "Invalid email or password"
-- This adds missing columns, seeds roles, and creates staff
--
-- PASSWORDS ARE MD5 HASHED. On first successful login, the
-- auth code will auto-upgrade them to bcrypt.
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- ============================================================
-- 1. ADD MISSING COLUMNS TO `staff` TABLE
-- ============================================================

SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff' AND COLUMN_NAME = 'staff_id');
SET @sql = IF(@exist = 0, 'ALTER TABLE `staff` ADD COLUMN `staff_id` varchar(20) DEFAULT NULL AFTER `id`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff' AND COLUMN_NAME = 'status');
SET @sql = IF(@exist = 0, 'ALTER TABLE `staff` ADD COLUMN `status` varchar(20) DEFAULT ''Active'' AFTER `is_active`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff' AND COLUMN_NAME = 'position');
SET @sql = IF(@exist = 0, 'ALTER TABLE `staff` ADD COLUMN `position` varchar(150) DEFAULT NULL AFTER `role_id`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff' AND COLUMN_NAME = 'department');
SET @sql = IF(@exist = 0, 'ALTER TABLE `staff` ADD COLUMN `department` varchar(150) DEFAULT NULL AFTER `position`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff' AND COLUMN_NAME = 'login_attempts');
SET @sql = IF(@exist = 0, 'ALTER TABLE `staff` ADD COLUMN `login_attempts` int(11) DEFAULT 0 AFTER `last_login`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff' AND COLUMN_NAME = 'locked_until');
SET @sql = IF(@exist = 0, 'ALTER TABLE `staff` ADD COLUMN `locked_until` datetime DEFAULT NULL AFTER `login_attempts`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff' AND COLUMN_NAME = 'is_first_login');
SET @sql = IF(@exist = 0, 'ALTER TABLE `staff` ADD COLUMN `is_first_login` tinyint(1) DEFAULT 0 AFTER `locked_until`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff' AND COLUMN_NAME = 'password_changed');
SET @sql = IF(@exist = 0, 'ALTER TABLE `staff` ADD COLUMN `password_changed` tinyint(1) DEFAULT 1 AFTER `is_first_login`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff' AND COLUMN_NAME = 'hire_date');
SET @sql = IF(@exist = 0, 'ALTER TABLE `staff` ADD COLUMN `hire_date` date DEFAULT NULL AFTER `status`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff' AND COLUMN_NAME = 'date_of_birth');
SET @sql = IF(@exist = 0, 'ALTER TABLE `staff` ADD COLUMN `date_of_birth` date DEFAULT NULL AFTER `phone`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff' AND COLUMN_NAME = 'gender');
SET @sql = IF(@exist = 0, 'ALTER TABLE `staff` ADD COLUMN `gender` enum(''Male'',''Female'',''Other'') DEFAULT NULL AFTER `date_of_birth`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff' AND COLUMN_NAME = 'nin');
SET @sql = IF(@exist = 0, 'ALTER TABLE `staff` ADD COLUMN `nin` varchar(20) DEFAULT NULL AFTER `gender`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff' AND COLUMN_NAME = 'profile_photo');
SET @sql = IF(@exist = 0, 'ALTER TABLE `staff` ADD COLUMN `profile_photo` varchar(255) DEFAULT NULL AFTER `password_changed`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff' AND COLUMN_NAME = 'staff_category');
SET @sql = IF(@exist = 0, 'ALTER TABLE `staff` ADD COLUMN `staff_category` enum(''teaching'',''non-teaching'',''clinical'',''administrative'') DEFAULT ''non-teaching'' AFTER `profile_photo`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 2. ADD MISSING COLUMNS TO `staff_roles` TABLE
-- ============================================================

SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff_roles' AND COLUMN_NAME = 'role_description');
SET @sql = IF(@exist = 0, 'ALTER TABLE `staff_roles` ADD COLUMN `role_description` text DEFAULT NULL AFTER `role_name`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff_roles' AND COLUMN_NAME = 'role_level');
SET @sql = IF(@exist = 0, 'ALTER TABLE `staff_roles` ADD COLUMN `role_level` int(11) DEFAULT 5 AFTER `role_description`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff_roles' AND COLUMN_NAME = 'dashboard_path');
SET @sql = IF(@exist = 0, 'ALTER TABLE `staff_roles` ADD COLUMN `dashboard_path` varchar(255) DEFAULT NULL AFTER `role_level`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff_roles' AND COLUMN_NAME = 'hierarchy_level');
SET @sql = IF(@exist = 0, 'ALTER TABLE `staff_roles` ADD COLUMN `hierarchy_level` int(11) DEFAULT 5 AFTER `role_level`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff_roles' AND COLUMN_NAME = 'permissions');
SET @sql = IF(@exist = 0, 'ALTER TABLE `staff_roles` ADD COLUMN `permissions` longtext DEFAULT NULL AFTER `dashboard_path`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @exist = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'staff_roles' AND COLUMN_NAME = 'updated_at');
SET @sql = IF(@exist = 0, 'ALTER TABLE `staff_roles` ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp() AFTER `created_at`', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 3. SEED staff_roles (INSERT IGNORE to avoid duplicates)
-- ============================================================

INSERT IGNORE INTO `staff_roles` (`role_name`, `role_level`, `hierarchy_level`, `dashboard_path`) VALUES
('Director General', 1, 1, 'dashboards/director-general.php'),
('CEO', 1, 1, 'dashboards/ceo.php'),
('Director Academics', 2, 2, 'dashboards/director-academics.php'),
('Director Finance', 2, 2, 'dashboards/director-finance.php'),
('Director ICT', 2, 2, 'dashboards/director-ict.php'),
('Director Admissions', 2, 2, 'dashboards/director-admissions.php'),
('School Principal', 2, 2, 'dashboards/school-principal.php'),
('Deputy Principal', 3, 3, 'dashboards/deputy-principal.php'),
('Academic Registrar', 3, 3, 'dashboards/academic-registrar.php'),
('School Bursar', 3, 3, 'dashboards/school-bursar.php'),
('School Secretary', 4, 4, 'dashboards/school-secretary.php'),
('HR Manager', 3, 3, 'dashboards/hr-manager.php'),
('School Librarian', 4, 4, 'dashboards/school-librarian.php'),
('Head of Nursing', 3, 3, 'dashboards/head-nursing.php'),
('Head of Midwifery', 3, 3, 'dashboards/head-midwifery.php'),
('Senior Lecturer', 4, 4, 'dashboards/senior-lecturers.php'),
('Lecturer', 5, 5, 'dashboards/lecturers.php'),
('Security Officer', 5, 5, 'dashboards/security.php'),
('Storekeeper', 5, 5, 'dashboards/storekeeper.php'),
('Driver', 6, 6, 'dashboards/drivers.php'),
('Matron', 4, 4, 'dashboards/matrons.php'),
('Warden', 5, 5, 'dashboards/wardens.php'),
('Guild President', 4, 4, 'dashboards/guild-president.php'),
('Sickbay Nurse', 5, 5, 'dashboards/sickbay.php'),
('System Administrator', 1, 1, 'dashboards/system-admin.php'),
('Computer Lab Manager', 4, 4, 'dashboards/computer_lab.php'),
('Skills Lab Manager', 4, 4, 'dashboards/skills-lab.php'),
('Skills Lab Technician', 5, 5, 'dashboards/skills-lab.php'),
('Events Coordinator', 4, 4, 'dashboards/events-manager.php'),
('Alumni Relations Officer', 4, 4, 'dashboards/alumni-manager.php');

-- ============================================================
-- 4. SEED staff ACCOUNTS (MD5 hashed passwords)
--    Auth code supports MD5 and auto-upgrades to bcrypt
-- ============================================================
-- Passwords (MD5 hashes):
--   DorisJoy2026 -> 45eaa46d02346e73e05be5523c7b0d93
--   Lovely2God   -> 2c5de25ca55d49be81e0649b71f3b282
--   Stephen123   -> f5d72724eab3a66e82a39eae693ad7a7
--   isnm2026     -> 245fcd87d437c1c0ece2dd9943eee778
--   Isnm2026     -> 59993d5ac39bc533a778e1a1c55687cd
--   Alexis2026   -> 9680c90d3226b72f1df95316fbff385e
--   isnm4life    -> f3e51a88ef311d56ba497a80c79025be
--   Life2save    -> d0d2225a958bf9b8b08f150162ede0a0
--   safty1st     -> 23856602556d9e58b30503c57bce0a56
--   Techno123    -> 59fbbd043302256833276008bcd001b3
--   2268926931   -> 2eb16e0f551fa465c0d5f752bed6002d
--   Eventful2026 -> e1c4deec4c0cf28a8f14a27e52e0eaed
--   Alumni2026   -> 133161cd962c583cf755da722d6c962f

INSERT IGNORE INTO `staff` (`staff_id`, `full_name`, `email`, `password`, `position`, `department`, `role_id`, `status`, `is_first_login`, `password_changed`, `hire_date`)
SELECT 'DG-001', 'Director General', 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug', '45eaa46d02346e73e05be5523c7b0d93', 'Director General', 'Executive', sr.id, 'Active', 0, 1, CURDATE()
FROM staff_roles sr WHERE sr.role_name = 'Director General'
AND NOT EXISTS (SELECT 1 FROM staff WHERE email = 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug');

INSERT IGNORE INTO `staff` (`staff_id`, `full_name`, `email`, `password`, `position`, `department`, `role_id`, `status`, `is_first_login`, `password_changed`, `hire_date`)
SELECT 'CEO-001', 'Chief Executive Officer', 'ceo@igangaschoolofnursingandmidwifery.ac.ug', '2c5de25ca55d49be81e0649b71f3b282', 'CEO', 'Executive', sr.id, 'Active', 0, 1, CURDATE()
FROM staff_roles sr WHERE sr.role_name = 'CEO'
AND NOT EXISTS (SELECT 1 FROM staff WHERE email = 'ceo@igangaschoolofnursingandmidwifery.ac.ug');

INSERT IGNORE INTO `staff` (`staff_id`, `full_name`, `email`, `password`, `position`, `department`, `role_id`, `status`, `is_first_login`, `password_changed`, `hire_date`)
SELECT 'DA-001', 'Director Academics', 'directoracademic@igangaschoolofnursingandmidwifery.ac.ug', 'f5d72724eab3a66e82a39eae693ad7a7', 'Director Academics', 'Academic Affairs', sr.id, 'Active', 0, 1, CURDATE()
FROM staff_roles sr WHERE sr.role_name = 'Director Academics'
AND NOT EXISTS (SELECT 1 FROM staff WHERE email = 'directoracademic@igangaschoolofnursingandmidwifery.ac.ug');

INSERT IGNORE INTO `staff` (`staff_id`, `full_name`, `email`, `password`, `position`, `department`, `role_id`, `status`, `is_first_login`, `password_changed`, `hire_date`)
SELECT 'DF-001', 'Director Finance', 'finance@igangaschoolofnursingandmidwifery.ac.ug', '45eaa46d02346e73e05be5523c7b0d93', 'Director Finance', 'Finance', sr.id, 'Active', 0, 1, CURDATE()
FROM staff_roles sr WHERE sr.role_name = 'Director Finance'
AND NOT EXISTS (SELECT 1 FROM staff WHERE email = 'finance@igangaschoolofnursingandmidwifery.ac.ug');

INSERT IGNORE INTO `staff` (`staff_id`, `full_name`, `email`, `password`, `position`, `department`, `role_id`, `status`, `is_first_login`, `password_changed`, `hire_date`)
SELECT 'PRIN-001', 'School Principal', 'principal@igangaschoolofnursingandmidwifery.ac.ug', '245fcd87d437c1c0ece2dd9943eee778', 'School Principal', 'Administration', sr.id, 'Active', 0, 1, CURDATE()
FROM staff_roles sr WHERE sr.role_name = 'School Principal'
AND NOT EXISTS (SELECT 1 FROM staff WHERE email = 'principal@igangaschoolofnursingandmidwifery.ac.ug');

INSERT IGNORE INTO `staff` (`staff_id`, `full_name`, `email`, `password`, `position`, `department`, `role_id`, `status`, `is_first_login`, `password_changed`, `hire_date`)
SELECT 'DP-001', 'Deputy Principal', 'dep-principal@igangaschoolofnursingandmidwifery.ac.ug', '59993d5ac39bc533a778e1a1c55687cd', 'Deputy Principal', 'Administration', sr.id, 'Active', 0, 1, CURDATE()
FROM staff_roles sr WHERE sr.role_name = 'Deputy Principal'
AND NOT EXISTS (SELECT 1 FROM staff WHERE email = 'dep-principal@igangaschoolofnursingandmidwifery.ac.ug');

INSERT IGNORE INTO `staff` (`staff_id`, `full_name`, `email`, `password`, `position`, `department`, `role_id`, `status`, `is_first_login`, `password_changed`, `hire_date`)
SELECT 'AR-001', 'Academic Registrar', 'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug', '2c5de25ca55d49be81e0649b71f3b282', 'Academic Registrar', 'Academic Registrar', sr.id, 'Active', 0, 1, CURDATE()
FROM staff_roles sr WHERE sr.role_name = 'Academic Registrar'
AND NOT EXISTS (SELECT 1 FROM staff WHERE email = 'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug');

INSERT IGNORE INTO `staff` (`staff_id`, `full_name`, `email`, `password`, `position`, `department`, `role_id`, `status`, `is_first_login`, `password_changed`, `hire_date`)
SELECT 'HR-001', 'HR Manager', 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug', '9680c90d3226b72f1df95316fbff385e', 'HR Manager', 'Human Resources', sr.id, 'Active', 0, 1, CURDATE()
FROM staff_roles sr WHERE sr.role_name = 'HR Manager'
AND NOT EXISTS (SELECT 1 FROM staff WHERE email = 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug');

INSERT IGNORE INTO `staff` (`staff_id`, `full_name`, `email`, `password`, `position`, `department`, `role_id`, `status`, `is_first_login`, `password_changed`, `hire_date`)
SELECT 'SEC-001', 'School Secretary', 'secretary@igangaschoolofnursingandmidwifery.ac.ug', '2c5de25ca55d49be81e0649b71f3b282', 'School Secretary', 'Administration', sr.id, 'Active', 0, 1, CURDATE()
FROM staff_roles sr WHERE sr.role_name = 'School Secretary'
AND NOT EXISTS (SELECT 1 FROM staff WHERE email = 'secretary@igangaschoolofnursingandmidwifery.ac.ug');

INSERT IGNORE INTO `staff` (`staff_id`, `full_name`, `email`, `password`, `position`, `department`, `role_id`, `status`, `is_first_login`, `password_changed`, `hire_date`)
SELECT 'LIB-001', 'School Librarian', 'library@igangaschoolofnursingandmidwifery.ac.ug', '245fcd87d437c1c0ece2dd9943eee778', 'School Librarian', 'Library', sr.id, 'Active', 0, 1, CURDATE()
FROM staff_roles sr WHERE sr.role_name = 'School Librarian'
AND NOT EXISTS (SELECT 1 FROM staff WHERE email = 'library@igangaschoolofnursingandmidwifery.ac.ug');

INSERT IGNORE INTO `staff` (`staff_id`, `full_name`, `email`, `password`, `position`, `department`, `role_id`, `status`, `is_first_login`, `password_changed`, `hire_date`)
SELECT 'NUR-001', 'Head of Nursing', 'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug', 'f3e51a88ef311d56ba497a80c79025be', 'Head of Nursing', 'Nursing', sr.id, 'Active', 0, 1, CURDATE()
FROM staff_roles sr WHERE sr.role_name = 'Head of Nursing'
AND NOT EXISTS (SELECT 1 FROM staff WHERE email = 'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug');

INSERT IGNORE INTO `staff` (`staff_id`, `full_name`, `email`, `password`, `position`, `department`, `role_id`, `status`, `is_first_login`, `password_changed`, `hire_date`)
SELECT 'MID-001', 'Head of Midwifery', 'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug', 'd0d2225a958bf9b8b08f150162ede0a0', 'Head of Midwifery', 'Midwifery', sr.id, 'Active', 0, 1, CURDATE()
FROM staff_roles sr WHERE sr.role_name = 'Head of Midwifery'
AND NOT EXISTS (SELECT 1 FROM staff WHERE email = 'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug');

INSERT IGNORE INTO `staff` (`staff_id`, `full_name`, `email`, `password`, `position`, `department`, `role_id`, `status`, `is_first_login`, `password_changed`, `hire_date`)
SELECT 'SL-001', 'Senior Lecturer', 'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug', '245fcd87d437c1c0ece2dd9943eee778', 'Senior Lecturer', 'Academic Affairs', sr.id, 'Active', 0, 1, CURDATE()
FROM staff_roles sr WHERE sr.role_name = 'Senior Lecturer'
AND NOT EXISTS (SELECT 1 FROM staff WHERE email = 'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug');

INSERT IGNORE INTO `staff` (`staff_id`, `full_name`, `email`, `password`, `position`, `department`, `role_id`, `status`, `is_first_login`, `password_changed`, `hire_date`)
SELECT 'LEC-001', 'Lecturer', 'lecturers@igangaschoolofnursingandmidwifery.ac.ug', 'f3e51a88ef311d56ba497a80c79025be', 'Lecturer', 'Academic Affairs', sr.id, 'Active', 0, 1, CURDATE()
FROM staff_roles sr WHERE sr.role_name = 'Lecturer'
AND NOT EXISTS (SELECT 1 FROM staff WHERE email = 'lecturers@igangaschoolofnursingandmidwifery.ac.ug');

INSERT IGNORE INTO `staff` (`staff_id`, `full_name`, `email`, `password`, `position`, `department`, `role_id`, `status`, `is_first_login`, `password_changed`, `hire_date`)
SELECT 'MAT-001', 'Matron', 'matron@igangaschoolofnursingandmidwifery.ac.ug', '59993d5ac39bc533a778e1a1c55687cd', 'Matron', 'Student Welfare', sr.id, 'Active', 0, 1, CURDATE()
FROM staff_roles sr WHERE sr.role_name = 'Matron'
AND NOT EXISTS (SELECT 1 FROM staff WHERE email = 'matron@igangaschoolofnursingandmidwifery.ac.ug');

INSERT IGNORE INTO `staff` (`staff_id`, `full_name`, `email`, `password`, `position`, `department`, `role_id`, `status`, `is_first_login`, `password_changed`, `hire_date`)
SELECT 'WAR-001', 'Warden', 'warden@igangaschoolofnursingandmidwifery.ac.ug', '2c5de25ca55d49be81e0649b71f3b282', 'Warden', 'Student Welfare', sr.id, 'Active', 0, 1, CURDATE()
FROM staff_roles sr WHERE sr.role_name = 'Warden'
AND NOT EXISTS (SELECT 1 FROM staff WHERE email = 'warden@igangaschoolofnursingandmidwifery.ac.ug');

INSERT IGNORE INTO `staff` (`staff_id`, `full_name`, `email`, `password`, `position`, `department`, `role_id`, `status`, `is_first_login`, `password_changed`, `hire_date`)
SELECT 'SKB-001', 'Sickbay Nurse', 'sickbay@igangaschoolofnursingandmidwifery.ac.ug', '245fcd87d437c1c0ece2dd9943eee778', 'Sickbay Nurse', 'Student Welfare', sr.id, 'Active', 0, 1, CURDATE()
FROM staff_roles sr WHERE sr.role_name = 'Sickbay Nurse'
AND NOT EXISTS (SELECT 1 FROM staff WHERE email = 'sickbay@igangaschoolofnursingandmidwifery.ac.ug');

INSERT IGNORE INTO `staff` (`staff_id`, `full_name`, `email`, `password`, `position`, `department`, `role_id`, `status`, `is_first_login`, `password_changed`, `hire_date`)
SELECT 'DRV-001', 'Driver', 'drivers@igangaschoolofnursingandmidwifery.ac.ug', 'f3e51a88ef311d56ba497a80c79025be', 'Driver', 'Transport', sr.id, 'Active', 0, 1, CURDATE()
FROM staff_roles sr WHERE sr.role_name = 'Driver'
AND NOT EXISTS (SELECT 1 FROM staff WHERE email = 'drivers@igangaschoolofnursingandmidwifery.ac.ug');

INSERT IGNORE INTO `staff` (`staff_id`, `full_name`, `email`, `password`, `position`, `department`, `role_id`, `status`, `is_first_login`, `password_changed`, `hire_date`)
SELECT 'SEC2-001', 'Security Officer', 'security@igangaschoolofnursingandmidwifery.ac.ug', '23856602556d9e58b30503c57bce0a56', 'Security Officer', 'Security', sr.id, 'Active', 0, 1, CURDATE()
FROM staff_roles sr WHERE sr.role_name = 'Security Officer'
AND NOT EXISTS (SELECT 1 FROM staff WHERE email = 'security@igangaschoolofnursingandmidwifery.ac.ug');

INSERT IGNORE INTO `staff` (`staff_id`, `full_name`, `email`, `password`, `position`, `department`, `role_id`, `status`, `is_first_login`, `password_changed`, `hire_date`)
SELECT 'STO-001', 'Storekeeper', 'store@igangaschoolofnursingandmidwifery.ac.ug', 'f3e51a88ef311d56ba497a80c79025be', 'Storekeeper', 'Store', sr.id, 'Active', 0, 1, CURDATE()
FROM staff_roles sr WHERE sr.role_name = 'Storekeeper'
AND NOT EXISTS (SELECT 1 FROM staff WHERE email = 'store@igangaschoolofnursingandmidwifery.ac.ug');

INSERT IGNORE INTO `staff` (`staff_id`, `full_name`, `email`, `password`, `position`, `department`, `role_id`, `status`, `is_first_login`, `password_changed`, `hire_date`)
SELECT 'G-001', 'Guild President', 'guildpresident@igangaschoolofnursingandmidwifery.ac.ug', 'f3e51a88ef311d56ba497a80c79025be', 'Guild President', 'Student Government', sr.id, 'Active', 0, 1, CURDATE()
FROM staff_roles sr WHERE sr.role_name = 'Guild President'
AND NOT EXISTS (SELECT 1 FROM staff WHERE email = 'guildpresident@igangaschoolofnursingandmidwifery.ac.ug');

INSERT IGNORE INTO `staff` (`staff_id`, `full_name`, `email`, `password`, `position`, `department`, `role_id`, `status`, `is_first_login`, `password_changed`, `hire_date`)
SELECT 'ADM-001', 'Director Admissions', 'admissions@igangaschoolofnursingandmidwifery.ac.ug', '2eb16e0f551fa465c0d5f752bed6002d', 'Director Admissions', 'Admissions', sr.id, 'Active', 0, 1, CURDATE()
FROM staff_roles sr WHERE sr.role_name = 'Director Admissions'
AND NOT EXISTS (SELECT 1 FROM staff WHERE email = 'admissions@igangaschoolofnursingandmidwifery.ac.ug');

INSERT IGNORE INTO `staff` (`staff_id`, `full_name`, `email`, `password`, `position`, `department`, `role_id`, `status`, `is_first_login`, `password_changed`, `hire_date`)
SELECT 'ICT-001', 'Director ICT', 'dannybict@igangaschoolofnursingandmidwifery.ac.ug', '2c5de25ca55d49be81e0649b71f3b282', 'Director ICT', 'ICT', sr.id, 'Active', 0, 1, CURDATE()
FROM staff_roles sr WHERE sr.role_name = 'Director ICT'
AND NOT EXISTS (SELECT 1 FROM staff WHERE email = 'dannybict@igangaschoolofnursingandmidwifery.ac.ug');

INSERT IGNORE INTO `staff` (`staff_id`, `full_name`, `email`, `password`, `position`, `department`, `role_id`, `status`, `is_first_login`, `password_changed`, `hire_date`)
SELECT 'SKL-001', 'Skills Lab Manager', 'skills-lab@igangaschoolofnursingandmidwifery.ac.ug', '2c5de25ca55d49be81e0649b71f3b282', 'Skills Lab Manager', 'Skills Laboratory', sr.id, 'Active', 0, 1, CURDATE()
FROM staff_roles sr WHERE sr.role_name = 'Skills Lab Manager'
AND NOT EXISTS (SELECT 1 FROM staff WHERE email = 'skills-lab@igangaschoolofnursingandmidwifery.ac.ug');

INSERT IGNORE INTO `staff` (`staff_id`, `full_name`, `email`, `password`, `position`, `department`, `role_id`, `status`, `is_first_login`, `password_changed`, `hire_date`)
SELECT 'CLB-001', 'Computer Lab Manager', 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug', 'f5d72724eab3a66e82a39eae693ad7a7', 'Computer Lab Manager', 'ICT', sr.id, 'Active', 0, 1, CURDATE()
FROM staff_roles sr WHERE sr.role_name = 'Computer Lab Manager'
AND NOT EXISTS (SELECT 1 FROM staff WHERE email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug');

INSERT IGNORE INTO `staff` (`staff_id`, `full_name`, `email`, `password`, `position`, `department`, `role_id`, `status`, `is_first_login`, `password_changed`, `hire_date`)
SELECT 'EVT-001', 'Events Coordinator', 'events@igangaschoolofnursingandmidwifery.ac.ug', 'e1c4deec4c0cf28a8f14a27e52e0eaed', 'Events Coordinator', 'Administration', sr.id, 'Active', 0, 1, CURDATE()
FROM staff_roles sr WHERE sr.role_name = 'Events Coordinator'
AND NOT EXISTS (SELECT 1 FROM staff WHERE email = 'events@igangaschoolofnursingandmidwifery.ac.ug');

INSERT IGNORE INTO `staff` (`staff_id`, `full_name`, `email`, `password`, `position`, `department`, `role_id`, `status`, `is_first_login`, `password_changed`, `hire_date`)
SELECT 'ALU-001', 'Alumni Relations Officer', 'alumni@igangaschoolofnursingandmidwifery.ac.ug', '133161cd962c583cf755da722d6c962f', 'Alumni Relations Officer', 'Administration', sr.id, 'Active', 0, 1, CURDATE()
FROM staff_roles sr WHERE sr.role_name = 'Alumni Relations Officer'
AND NOT EXISTS (SELECT 1 FROM staff WHERE email = 'alumni@igangaschoolofnursingandmidwifery.ac.ug');

-- ============================================================
-- 5. Reset any locked accounts
-- ============================================================
UPDATE `staff` SET `login_attempts` = 0, `locked_until` = NULL WHERE `locked_until` IS NOT NULL AND `locked_until` > NOW();

-- ============================================================
-- DONE! Credentials cheat sheet:
-- ============================================================
-- Director General:    directorgeneral@...ac.ug / DorisJoy2026
-- CEO:                 ceo@...ac.ug / Lovely2God
-- Director Academics:  directoracademic@...ac.ug / Stephen123
-- Director Finance:    finance@...ac.ug / DorisJoy2026
-- School Principal:    principal@...ac.ug / isnm2026
-- Deputy Principal:    dep-principal@...ac.ug / Isnm2026
-- Academic Registrar:  academicregistrar@...ac.ug / Lovely2God
-- HR Manager:          hr-manager@...ac.ug / Alexis2026
-- School Secretary:    secretary@...ac.ug / Lovely2God
-- School Librarian:    library@...ac.ug / isnm2026
-- Head of Nursing:     nursing-dep@...ac.ug / isnm4life
-- Head of Midwifery:   midwifery-dep@...ac.ug / Life2save
-- Director ICT:        dannybict@...ac.ug / Lovely2God
-- Director Admissions: admissions@...ac.ug / 2268926931
-- ============================================================
