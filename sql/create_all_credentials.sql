-- ============================================================
--  ISNM ERP SYSTEM
--  Iganga School of Nursing & Midwifery
--  Complete Login Credentials — All Departments & Positions
--  Generated: 2026-07-16
-- ============================================================
--
--  HOW TO USE:
--    Step 1: Run Section A on igangaschool_staffs database
--    Step 2: Run Section B on igangaschool_students database
--
--  LOGIN URL: staff-login.php
--  LOGOUT: redirects to staff-login.php
-- ============================================================


-- ============================================================
--  SECTION A: Run on igangaschool_staffs database
-- ============================================================

USE `igangaschool_staffs`;


-- ────────────────────────────────────────────────────────────
--  A1. STAFF ROLES (30 roles, idempotent)
-- ────────────────────────────────────────────────────────────

INSERT INTO `staff_roles` (`id`,`role_name`,`role_description`,`role_level`,`hierarchy_level`,`dashboard_path`,`is_active`)
VALUES
  (1,  'Director General',          'Executive leadership',              1, 1, 'dashboards/director-general.php',      1),
  (2,  'CEO',                       'Chief Executive Officer',           1, 1, 'dashboards/ceo.php',                    1),
  (3,  'Director Academics',        'Academic affairs oversight',        2, 2, 'dashboards/director-academics.php',     1),
  (4,  'Director Finance',          'Financial management oversight',    2, 2, 'dashboards/director-finance.php',       1),
  (5,  'Director ICT',              'ICT department head',               2, 2, 'dashboards/director-ict.php',           1),
  (6,  'Director Admissions',       'Admissions department head',        2, 2, 'dashboards/director-admissions.php',    1),
  (7,  'School Principal',          'School administration head',        2, 2, 'dashboards/school-principal.php',       1),
  (8,  'Deputy Principal',          'Deputy school administration',      3, 3, 'dashboards/deputy-principal.php',       1),
  (9,  'Academic Registrar',        'Academic records management',       3, 3, 'dashboards/academic-registrar.php',     1),
  (10, 'School Bursar',             'Financial operations',              3, 3, 'dashboards/school-bursar.php',          1),
  (11, 'School Secretary',          'Administrative support',            4, 4, 'dashboards/school-secretary.php',       1),
  (12, 'HR Manager',                'Human resource management',         3, 3, 'dashboards/hr-manager.php',             1),
  (13, 'School Librarian',          'Library management',                4, 4, 'dashboards/school-librarian.php',       1),
  (14, 'Head of Nursing',           'Nursing department head',           3, 3, 'dashboards/head-nursing.php',           1),
  (15, 'Head of Midwifery',         'Midwifery department head',         3, 3, 'dashboards/head-midwifery.php',         1),
  (16, 'Senior Lecturer',           'Senior academic staff',             4, 4, 'dashboards/senior-lecturers.php',       1),
  (17, 'Lecturer',                  'Academic teaching staff',           5, 5, 'dashboards/lecturers.php',              1),
  (18, 'Security Officer',          'Security and access control',       5, 5, 'dashboards/security.php',               1),
  (19, 'Storekeeper',               'Store and inventory management',    5, 5, 'dashboards/storekeeper.php',            1),
  (20, 'Driver',                    'Transport and logistics',           6, 6, 'dashboards/drivers.php',                1),
  (21, 'Matron',                    'Student welfare (female)',          4, 4, 'dashboards/matrons.php',                1),
  (22, 'Warden',                    'Hostel management',                 5, 5, 'dashboards/wardens.php',                1),
  (23, 'Guild President',           'Student government leader',         4, 4, 'dashboards/guild-president.php',        1),
  (24, 'Sickbay Nurse',             'Health services',                   5, 5, 'dashboards/sickbay.php',                1),
  (25, 'System Administrator',      'System and network admin',          1, 1, 'dashboards/system-admin.php',           1),
  (26, 'Computer Lab Manager',      'Computer lab operations',           4, 4, 'dashboards/computer_lab.php',           1),
  (27, 'Skills Lab Manager',        'Skills laboratory operations',      4, 4, 'dashboards/skills-lab.php',             1),
  (28, 'Skills Lab Technician',     'Skills laboratory support',         5, 5, 'dashboards/skills-lab.php',             1),
  (29, 'Events Coordinator',        'Events and activities',             4, 4, 'dashboards/events-manager.php',         1),
  (30, 'Alumni Relations Officer',  'Alumni engagement',                 4, 4, 'dashboards/alumni-manager.php',         1)
ON DUPLICATE KEY UPDATE
  `role_name`       = VALUES(`role_name`),
  `role_description`= VALUES(`role_description`),
  `role_level`      = VALUES(`role_level`),
  `hierarchy_level` = VALUES(`hierarchy_level`),
  `dashboard_path`  = VALUES(`dashboard_path`),
  `is_active`       = VALUES(`is_active`);


-- ────────────────────────────────────────────────────────────
--  A1b. Ensure staff table has all required columns
-- ────────────────────────────────────────────────────────────
ALTER TABLE `staff` ADD COLUMN IF NOT EXISTS `login_attempts` INT(11) DEFAULT 0;
ALTER TABLE `staff` ADD COLUMN IF NOT EXISTS `locked_until` DATETIME DEFAULT NULL;
ALTER TABLE `staff` ADD COLUMN IF NOT EXISTS `is_first_login` TINYINT(1) DEFAULT 1;
ALTER TABLE `staff` ADD COLUMN IF NOT EXISTS `password_changed` TINYINT(1) DEFAULT 0;


-- ────────────────────────────────────────────────────────────
--  A2. LEADERSHIP & STRATEGY
-- ────────────────────────────────────────────────────────────

-- Director General — DorisJoy2026
UPDATE `staff` SET
  `password` = '$2y$10$y.BKuKDLYdoUeFMfgXtqQOO3h4fYssrcoZB3aKgDX.VrvY4uqVG7q',
  `status` = 'Active', `is_active` = 1, `login_attempts` = 0, `locked_until` = NULL,
  `is_first_login` = 0, `password_changed` = 1
WHERE `id` = 1;

-- CEO — Lovely2God
UPDATE `staff` SET
  `password` = '$2y$10$WXB/sJwqKDHMUDZpYj3VZOwdj4Nrpw3lGGE2b/SYkAbODyglBOD8q',
  `status` = 'Active', `is_active` = 1, `login_attempts` = 0, `locked_until` = NULL,
  `is_first_login` = 0, `password_changed` = 1
WHERE `id` = 2;


-- ────────────────────────────────────────────────────────────
--  A3. ACADEMIC AFFAIRS
-- ────────────────────────────────────────────────────────────

-- Director Academics — Stephen123
UPDATE `staff` SET
  `password` = '$2y$10$erOn9wIoOagBWYuWUdD0j.Gihha85DXHtW.2Tdf2G1NxA98UrrB7y',
  `status` = 'Active', `is_active` = 1, `login_attempts` = 0, `locked_until` = NULL,
  `is_first_login` = 0, `password_changed` = 1
WHERE `id` = 3;

-- School Principal — isnm2026
UPDATE `staff` SET
  `password` = '$2y$10$u7JCF3voi9a1vA606rayJuxMZNiFPC0KY0/JKG24uiocTgwhBvX4O',
  `status` = 'Active', `is_active` = 1, `login_attempts` = 0, `locked_until` = NULL,
  `is_first_login` = 0, `password_changed` = 1
WHERE `id` = 5;

-- Deputy Principal — Isnm2026
UPDATE `staff` SET
  `password` = '$2y$10$7AaHUF/Nok9WVUjeHdMRWesGEQ7IVm5/lOwFvhmd3rm2DU9P6WXwO',
  `status` = 'Active', `is_active` = 1, `login_attempts` = 0, `locked_until` = NULL,
  `is_first_login` = 0, `password_changed` = 1
WHERE `id` = 6;

-- Academic Registrar — Lovely2God
UPDATE `staff` SET
  `password` = '$2y$10$WXB/sJwqKDHMUDZpYj3VZOwdj4Nrpw3lGGE2b/SYkAbODyglBOD8q',
  `status` = 'Active', `is_active` = 1, `login_attempts` = 0, `locked_until` = NULL,
  `is_first_login` = 0, `password_changed` = 1
WHERE `id` = 7;

-- Head of Nursing — isnm4life
UPDATE `staff` SET
  `password` = '$2y$10$yekD0q9asIJGIeIRauUlw.uquoCYqTrY3f6PmIzxCgraB8UUjLR.O',
  `status` = 'Active', `is_active` = 1, `login_attempts` = 0, `locked_until` = NULL,
  `is_first_login` = 0, `password_changed` = 1
WHERE `id` = 11;

-- Head of Midwifery — Life2save
UPDATE `staff` SET
  `password` = '$2y$10$w2zkfpBFn2L9rMAdGbtLduNrOIOWCknSWURIgNbIvXjdKj3kBuiH.',
  `status` = 'Active', `is_active` = 1, `login_attempts` = 0, `locked_until` = NULL,
  `is_first_login` = 0, `password_changed` = 1
WHERE `id` = 12;

-- Senior Lecturer — isnm2026
UPDATE `staff` SET
  `password` = '$2y$10$u7JCF3voi9a1vA606rayJuxMZNiFPC0KY0/JKG24uiocTgwhBvX4O',
  `status` = 'Active', `is_active` = 1, `login_attempts` = 0, `locked_until` = NULL,
  `is_first_login` = 0, `password_changed` = 1
WHERE `id` = 13;

-- Lecturer — Isnm4life (capital I)
UPDATE `staff` SET
  `password` = '$2y$10$i1iR9ophK7vfAGM3zZRiieq/KAd14faOFPBT9Wd.30G6vh9T9Jhp2',
  `status` = 'Active', `is_active` = 1, `login_attempts` = 0, `locked_until` = NULL,
  `is_first_login` = 0, `password_changed` = 1
WHERE `id` = 14;


-- ────────────────────────────────────────────────────────────
--  A4. FINANCE & ACCOUNTS
-- ────────────────────────────────────────────────────────────

-- Director Finance — DorisJoy2026
UPDATE `staff` SET
  `password` = '$2y$10$y.BKuKDLYdoUeFMfgXtqQOO3h4fYssrcoZB3aKgDX.VrvY4uqVG7q',
  `status` = 'Active', `is_active` = 1, `login_attempts` = 0, `locked_until` = NULL,
  `is_first_login` = 0, `password_changed` = 1
WHERE `id` = 4;


-- ────────────────────────────────────────────────────────────
--  A5. HR & ADMINISTRATION
-- ────────────────────────────────────────────────────────────

-- HR Manager — Alexis2026
UPDATE `staff` SET
  `password` = '$2y$10$R9gdnzRVbjZSfYyWLMLNQuCGR8kALruSeGNve8gp0vk/XO5FDV4LW',
  `status` = 'Active', `is_active` = 1, `login_attempts` = 0, `locked_until` = NULL,
  `is_first_login` = 0, `password_changed` = 1
WHERE `id` = 8;

-- School Secretary — Lovely2God
UPDATE `staff` SET
  `password` = '$2y$10$WXB/sJwqKDHMUDZpYj3VZOwdj4Nrpw3lGGE2b/SYkAbODyglBOD8q',
  `status` = 'Active', `is_active` = 1, `login_attempts` = 0, `locked_until` = NULL,
  `is_first_login` = 0, `password_changed` = 1
WHERE `id` = 9;


-- ────────────────────────────────────────────────────────────
--  A6. STUDENT SERVICES
-- ────────────────────────────────────────────────────────────

-- School Librarian — isnm2026
UPDATE `staff` SET
  `password` = '$2y$10$u7JCF3voi9a1vA606rayJuxMZNiFPC0KY0/JKG24uiocTgwhBvX4O',
  `status` = 'Active', `is_active` = 1, `login_attempts` = 0, `locked_until` = NULL,
  `is_first_login` = 0, `password_changed` = 1
WHERE `id` = 10;

-- Matron — Isnm2026
UPDATE `staff` SET
  `password` = '$2y$10$7AaHUF/Nok9WVUjeHdMRWesGEQ7IVm5/lOwFvhmd3rm2DU9P6WXwO',
  `status` = 'Active', `is_active` = 1, `login_attempts` = 0, `locked_until` = NULL,
  `is_first_login` = 0, `password_changed` = 1
WHERE `id` = 15;

-- Warden — Lovely2God
UPDATE `staff` SET
  `password` = '$2y$10$WXB/sJwqKDHMUDZpYj3VZOwdj4Nrpw3lGGE2b/SYkAbODyglBOD8q',
  `status` = 'Active', `is_active` = 1, `login_attempts` = 0, `locked_until` = NULL,
  `is_first_login` = 0, `password_changed` = 1
WHERE `id` = 16;

-- Sickbay Nurse — isnm2026
UPDATE `staff` SET
  `password` = '$2y$10$u7JCF3voi9a1vA606rayJuxMZNiFPC0KY0/JKG24uiocTgwhBvX4O',
  `status` = 'Active', `is_active` = 1, `login_attempts` = 0, `locked_until` = NULL,
  `is_first_login` = 0, `password_changed` = 1
WHERE `id` = 17;

-- Guild President — isnm4life
UPDATE `staff` SET
  `password` = '$2y$10$yekD0q9asIJGIeIRauUlw.uquoCYqTrY3f6PmIzxCgraB8UUjLR.O',
  `status` = 'Active', `is_active` = 1, `login_attempts` = 0, `locked_until` = NULL,
  `is_first_login` = 0, `password_changed` = 1
WHERE `id` = 21;


-- ────────────────────────────────────────────────────────────
--  A7. OPERATIONS & LOGISTICS
-- ────────────────────────────────────────────────────────────

-- Director ICT — Lovely2God
UPDATE `staff` SET
  `password` = '$2y$10$WXB/sJwqKDHMUDZpYj3VZOwdj4Nrpw3lGGE2b/SYkAbODyglBOD8q',
  `status` = 'Active', `is_active` = 1, `login_attempts` = 0, `locked_until` = NULL,
  `is_first_login` = 0, `password_changed` = 1
WHERE `id` = 23;

-- Skills Lab Manager — email changed to skillslab@... , password Lovely2God
UPDATE `staff` SET
  `email`    = 'skillslab@igangaschoolofnursingandmidwifery.ac.ug',
  `password` = '$2y$10$WXB/sJwqKDHMUDZpYj3VZOwdj4Nrpw3lGGE2b/SYkAbODyglBOD8q',
  `status` = 'Active', `is_active` = 1, `login_attempts` = 0, `locked_until` = NULL,
  `is_first_login` = 0, `password_changed` = 1
WHERE `id` = 24;

-- Computer Lab Manager — email changed to computerlab@... , password Techno123
UPDATE `staff` SET
  `email`    = 'computerlab@igangaschoolofnursingandmidwifery.ac.ug',
  `password` = '$2y$10$L8E0ikt7T0FpFDjPUYX8eenzvzzKCsviru2UO/.sYd.GCzXdgQ1DC',
  `status` = 'Active', `is_active` = 1, `login_attempts` = 0, `locked_until` = NULL,
  `is_first_login` = 0, `password_changed` = 1
WHERE `id` = 25;

-- Events Coordinator — Lovely2God
UPDATE `staff` SET
  `password` = '$2y$10$WXB/sJwqKDHMUDZpYj3VZOwdj4Nrpw3lGGE2b/SYkAbODyglBOD8q',
  `status` = 'Active', `is_active` = 1, `login_attempts` = 0, `locked_until` = NULL,
  `is_first_login` = 0, `password_changed` = 1
WHERE `id` = 26;

-- Alumni Relations Officer — Lovely2God
UPDATE `staff` SET
  `password` = '$2y$10$WXB/sJwqKDHMUDZpYj3VZOwdj4Nrpw3lGGE2b/SYkAbODyglBOD8q',
  `status` = 'Active', `is_active` = 1, `login_attempts` = 0, `locked_until` = NULL,
  `is_first_login` = 0, `password_changed` = 1
WHERE `id` = 27;

-- Director Finance — DorisJoy2026 (already updated above, skip duplicate)
-- Security Officer — safty1st
UPDATE `staff` SET
  `password` = '$2y$10$0TXXDluAYavgyjrnMqYqquhxnAn1FpQkPobTPEkth.FP5gEj3cHB.',
  `status` = 'Active', `is_active` = 1, `login_attempts` = 0, `locked_until` = NULL,
  `is_first_login` = 0, `password_changed` = 1
WHERE `id` = 19;

-- Storekeeper — Isnm4life
UPDATE `staff` SET
  `password` = '$2y$10$i1iR9ophK7vfAGM3zZRiieq/KAd14faOFPBT9Wd.30G6vh9T9Jhp2',
  `status` = 'Active', `is_active` = 1, `login_attempts` = 0, `locked_until` = NULL,
  `is_first_login` = 0, `password_changed` = 1
WHERE `id` = 20;

-- Driver — isnm4life
UPDATE `staff` SET
  `password` = '$2y$10$yekD0q9asIJGIeIRauUlw.uquoCYqTrY3f6PmIzxCgraB8UUjLR.O',
  `status` = 'Active', `is_active` = 1, `login_attempts` = 0, `locked_until` = NULL,
  `is_first_login` = 0, `password_changed` = 1
WHERE `id` = 18;


-- ────────────────────────────────────────────────────────────
--  A8. INSERT MISSING STAFF ACCOUNTS
-- ────────────────────────────────────────────────────────────

-- Director Admissions & Requirements — 2268926931
INSERT INTO `staff` (`staff_id`,`full_name`,`email`,`password`,`role_id`,`position`,`department`,`is_active`,`status`,`hire_date`,`login_attempts`,`is_first_login`,`password_changed`,`staff_category`)
VALUES ('ADM-REQ-001','Director Admissions & Requirements','admissions-req@igangaschoolofnursingandmidwifery.ac.ug',
  '$2y$10$x3SJ7CHJ0yD.528/b6WYVeyy3LPoDbD2Ckv7mcMs6Xw.9LRqsn53G',
  6,'Director Admissions','Admissions',1,'Active',CURDATE(),0,0,1,'non-teaching')
ON DUPLICATE KEY UPDATE
  `password` = VALUES(`password`), `status` = 'Active', `is_active` = 1,
  `login_attempts` = 0, `locked_until` = NULL, `is_first_login` = 0, `password_changed` = 1;

-- Director ICT (Alt) — Lovely2God
INSERT INTO `staff` (`staff_id`,`full_name`,`email`,`password`,`role_id`,`position`,`department`,`is_active`,`status`,`hire_date`,`login_attempts`,`is_first_login`,`password_changed`,`staff_category`)
VALUES ('ICT-ALT-001','Director ICT (Alt)','directorict@igangaschoolofnursingandmidwifery.ac.ug',
  '$2y$10$WXB/sJwqKDHMUDZpYj3VZOwdj4Nrpw3lGGE2b/SYkAbODyglBOD8q',
  5,'Director ICT','ICT',1,'Active',CURDATE(),0,0,1,'non-teaching')
ON DUPLICATE KEY UPDATE
  `password` = VALUES(`password`), `status` = 'Active', `is_active` = 1,
  `login_attempts` = 0, `locked_until` = NULL, `is_first_login` = 0, `password_changed` = 1;

-- Computer Lab (Alt) — Techno123
INSERT INTO `staff` (`staff_id`,`full_name`,`email`,`password`,`role_id`,`position`,`department`,`is_active`,`status`,`hire_date`,`login_attempts`,`is_first_login`,`password_changed`,`staff_category`)
VALUES ('CLB-ALT-001','Computer Lab','computer-lab@igangaschoolofnursingandmidwifery.ac.ug',
  '$2y$10$L8E0ikt7T0FpFDjPUYX8eenzvzzKCsviru2UO/.sYd.GCzXdgQ1DC',
  26,'Computer Lab Manager','ICT',1,'Active',CURDATE(),0,0,1,'non-teaching')
ON DUPLICATE KEY UPDATE
  `password` = VALUES(`password`), `status` = 'Active', `is_active` = 1,
  `login_attempts` = 0, `locked_until` = NULL, `is_first_login` = 0, `password_changed` = 1;

-- Skills Lab Technician — skills-lab@... / Lovely2God
INSERT INTO `staff` (`staff_id`,`full_name`,`email`,`password`,`role_id`,`position`,`department`,`is_active`,`status`,`hire_date`,`login_attempts`,`is_first_login`,`password_changed`,`staff_category`)
VALUES ('SKLT-001','Skills Lab Technician','skills-lab@igangaschoolofnursingandmidwifery.ac.ug',
  '$2y$10$WXB/sJwqKDHMUDZpYj3VZOwdj4Nrpw3lGGE2b/SYkAbODyglBOD8q',
  28,'Skills Lab Technician','Skills Laboratory',1,'Active',CURDATE(),0,0,1,'non-teaching')
ON DUPLICATE KEY UPDATE
  `password` = VALUES(`password`), `status` = 'Active', `is_active` = 1,
  `login_attempts` = 0, `locked_until` = NULL, `is_first_login` = 0, `password_changed` = 1;

-- System Administrator — admin@... / Lovely2God
INSERT INTO `staff` (`staff_id`,`full_name`,`email`,`password`,`role_id`,`position`,`department`,`is_active`,`status`,`hire_date`,`login_attempts`,`is_first_login`,`password_changed`,`staff_category`)
VALUES ('SYSADM-001','System Administrator','admin@igangaschoolofnursingandmidwifery.ac.ug',
  '$2y$10$WXB/sJwqKDHMUDZpYj3VZOwdj4Nrpw3lGGE2b/SYkAbODyglBOD8q',
  25,'System Administrator','ICT',1,'Active',CURDATE(),0,0,1,'non-teaching')
ON DUPLICATE KEY UPDATE
  `password` = VALUES(`password`), `status` = 'Active', `is_active` = 1,
  `login_attempts` = 0, `locked_until` = NULL, `is_first_login` = 0, `password_changed` = 1;


-- ────────────────────────────────────────────────────────────
--  A9. HR USERS TABLE (HR login fallback path)
-- ────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `hr_users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(200) NOT NULL,
  `role` VARCHAR(50) DEFAULT 'hr_manager',
  `status` VARCHAR(20) DEFAULT 'active',
  `login_attempts` INT(11) DEFAULT 0,
  `locked_until` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ensure columns exist on pre-existing tables
ALTER TABLE `hr_users` ADD COLUMN IF NOT EXISTS `login_attempts` INT(11) DEFAULT 0;
ALTER TABLE `hr_users` ADD COLUMN IF NOT EXISTS `locked_until` DATETIME DEFAULT NULL;

-- HR Manager — Alexis2026
INSERT INTO `hr_users` (`email`,`password_hash`,`full_name`,`role`,`status`)
VALUES ('hr-manager@igangaschoolofnursingandmidwifery.ac.ug',
  '$2y$10$R9gdnzRVbjZSfYyWLMLNQuCGR8kALruSeGNve8gp0vk/XO5FDV4LW',
  'HR Manager','hr_manager','active')
ON DUPLICATE KEY UPDATE
  `password_hash` = VALUES(`password_hash`), `status` = 'active',
  `login_attempts` = 0, `locked_until` = NULL;


-- ============================================================
--  SECTION B: Run on igangaschool_students database
-- ============================================================

USE `igangaschool_students`;


-- ────────────────────────────────────────────────────────────
--  B1. BURSAR USERS TABLE (bursar login path)
-- ────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `bursar_users` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(200) NOT NULL,
  `role` VARCHAR(50) DEFAULT 'bursar',
  `status` VARCHAR(20) DEFAULT 'active',
  `login_attempts` INT(11) DEFAULT 0,
  `locked_until` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ensure columns exist on pre-existing tables
ALTER TABLE `bursar_users` ADD COLUMN IF NOT EXISTS `login_attempts` INT(11) DEFAULT 0;
ALTER TABLE `bursar_users` ADD COLUMN IF NOT EXISTS `locked_until` DATETIME DEFAULT NULL;

-- School Bursar — bursar@isnm
INSERT INTO `bursar_users` (`email`,`password_hash`,`full_name`,`role`,`status`)
VALUES ('bursar@igangaschoolofnursingandmidwifery.ac.ug',
  '$2y$10$t8ruNUyglG3NmMVoQn10LOVy7rNJJkFM1lCD75BUvd9G62HMPQOLa',
  'School Bursar','bursar','active')
ON DUPLICATE KEY UPDATE
  `password_hash` = VALUES(`password_hash`), `status` = 'active',
  `login_attempts` = 0, `locked_until` = NULL;


-- ────────────────────────────────────────────────────────────
--  B1b. Ensure students table has all required columns
-- ────────────────────────────────────────────────────────────
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `intake_year` VARCHAR(10) DEFAULT NULL;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `intake_period` VARCHAR(20) DEFAULT NULL;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `login_attempts` INT(11) DEFAULT 0;
ALTER TABLE `students` ADD COLUMN IF NOT EXISTS `locked_until` TIMESTAMP NULL DEFAULT NULL;

-- Ensure id has AUTO_INCREMENT for new inserts
ALTER TABLE `students` MODIFY COLUMN `id` INT(11) NOT NULL AUTO_INCREMENT;

-- Back-fill intake_year/intake_period from intake_date where possible
UPDATE `students` SET
  `intake_year` = COALESCE(`intake_year`, YEAR(`intake_date`)),
  `intake_period` = COALESCE(`intake_period`, IF(MONTH(`intake_date`) <= 6, 'January', 'July'))
WHERE `intake_year` IS NULL OR `intake_period` IS NULL;


-- ────────────────────────────────────────────────────────────
--  B2. SAMPLE STUDENT ACCOUNTS — student@isnm
-- ────────────────────────────────────────────────────────────

-- Student 1: Comprehensive Nursing — CM-2026-001 / student@isnm
INSERT INTO `students` (
  `index_number`,`registration_number`,`student_number`,`first_name`,`surname`,`other_name`,
  `full_name`,`email`,`phone`,`program`,`course`,`year`,`level`,
  `set_name`,`intake_date`,`intake_year`,`intake_period`,`status`,`password`,`is_first_login`,`password_changed`,
  `gender`,`nationality`
) VALUES (
  'CM-2026-001','REG-2026-001','STU202600001',
  'John','Okello','James','John James Okello',
  'john.okello@student.isnm.ac.ug','0770000001',
  'Bachelor of Science in Nursing (Comprehensive)','BSc Nursing',1,'Year 1',
  '1','2026-01-15','2026','January','Active',
  '$2y$10$HxVkw2ihQPBwiK/fXa9Lqezmnw8KmKVSOMVGXfUynT09hDxYbadQe',
  0,1,'Male','Ugandan'
) ON DUPLICATE KEY UPDATE
  `password` = VALUES(`password`), `is_first_login` = 0, `password_changed` = 1, `status` = 'Active';

-- Student 2: Midwifery — MID-2026-001 / student@isnm
INSERT INTO `students` (
  `index_number`,`registration_number`,`student_number`,`first_name`,`surname`,`other_name`,
  `full_name`,`email`,`phone`,`program`,`course`,`year`,`level`,
  `set_name`,`intake_date`,`intake_year`,`intake_period`,`status`,`password`,`is_first_login`,`password_changed`,
  `gender`,`nationality`
) VALUES (
  'MID-2026-001','REG-2026-002','STU202600002',
  'Grace','Nambi','','Grace Nambi',
  'grace.nambi@student.isnm.ac.ug','0770000002',
  'Bachelor of Science in Midwifery','BSc Midwifery',1,'Year 1',
  '2','2026-01-15','2026','January','Active',
  '$2y$10$HxVkw2ihQPBwiK/fXa9Lqezmnw8KmKVSOMVGXfUynT09hDxYbadQe',
  0,1,'Female','Ugandan'
) ON DUPLICATE KEY UPDATE
  `password` = VALUES(`password`), `is_first_login` = 0, `password_changed` = 1, `status` = 'Active';

-- Student 3: Diploma — DIP-2026-001 / student@isnm
INSERT INTO `students` (
  `index_number`,`registration_number`,`student_number`,`first_name`,`surname`,`other_name`,
  `full_name`,`email`,`phone`,`program`,`course`,`year`,`level`,
  `set_name`,`intake_date`,`intake_year`,`intake_period`,`status`,`password`,`is_first_login`,`password_changed`,
  `gender`,`nationality`
) VALUES (
  'DIP-2026-001','REG-2026-003','STU202600003',
  'Samuel','Mugisha','Peter','Samuel Peter Mugisha',
  'samuel.mugisha@student.isnm.ac.ug','0770000003',
  'Diploma in Nursing/Midwifery','Dip Nursing',1,'Year 1',
  '3','2026-01-15','2026','January','Active',
  '$2y$10$HxVkw2ihQPBwiK/fXa9Lqezmnw8KmKVSOMVGXfUynT09hDxYbadQe',
  0,1,'Male','Ugandan'
) ON DUPLICATE KEY UPDATE
  `password` = VALUES(`password`), `is_first_login` = 0, `password_changed` = 1, `status` = 'Active';


-- ============================================================
--  COMPLETE CREDENTIALS REFERENCE
-- ============================================================
--
-- ┌──────────────────────────────────────────────────────────────────────────────────────────┐
-- │  LEADERSHIP & STRATEGY                                                                  │
-- ├─────────────────────┬──────────────────────────────────────────┬────────────┬────────────┤
-- │  Director General   │ directorgeneral@igangaschoolof...ac.ug   │ DorisJoy2026│ director-general │
-- │  CEO                │ ceo@igangaschoolof...ac.ug               │ Lovely2God  │ ceo        │
-- ├─────────────────────┴──────────────────────────────────────────┴────────────┴────────────┤
-- │  ACADEMIC AFFAIRS                                                                        │
-- ├─────────────────────┬──────────────────────────────────────────┬────────────┬────────────┤
-- │  Director Academics │ directoracademic@igangaschoolof...ac.ug  │ Stephen123 │ director-academics │
-- │  School Principal   │ principal@igangaschoolof...ac.ug         │ isnm2026   │ school-principal │
-- │  Deputy Principal   │ dep-principal@igangaschoolof...ac.ug     │ Isnm2026   │ deputy-principal │
-- │  Academic Registrar │ academicregistrar@igangaschoolof...ac.ug │ Lovely2God  │ academic-registrar │
-- │  Head of Nursing    │ nursing-dep@igangaschoolof...ac.ug       │ isnm4life  │ head-nursing │
-- │  Head of Midwifery  │ midwifery-dep@igangaschoolof...ac.ug     │ Life2save  │ head-midwifery │
-- │  Senior Lecturer    │ senior-lecturers@igangaschoolof...ac.ug  │ isnm2026   │ senior-lecturers │
-- │  Lecturer           │ lecturers@igangaschoolof...ac.ug         │ Isnm4life  │ lecturers   │
-- ├─────────────────────┴──────────────────────────────────────────┴────────────┴────────────┤
-- │  FINANCE & ACCOUNTS                                                                      │
-- ├─────────────────────┬──────────────────────────────────────────┬────────────┬────────────┤
-- │  Director Finance   │ finance@igangaschoolof...ac.ug           │ DorisJoy2026│ director-finance │
-- │  School Bursar      │ bursar@igangaschoolof...ac.ug            │ bursar@isnm│ school-bursar (bursar_users) │
-- ├─────────────────────┴──────────────────────────────────────────┴────────────┴────────────┤
-- │  HR & ADMINISTRATION                                                                     │
-- ├─────────────────────┬──────────────────────────────────────────┬────────────┬────────────┤
-- │  HR Manager         │ hr-manager@igangaschoolof...ac.ug        │ Alexis2026 │ hr-manager │
-- │  School Secretary   │ secretary@igangaschoolof...ac.ug         │ Lovely2God  │ school-secretary │
-- ├─────────────────────┴──────────────────────────────────────────┴────────────┴────────────┤
-- │  STUDENT SERVICES                                                                        │
-- ├─────────────────────┬──────────────────────────────────────────┬────────────┬────────────┤
-- │  Dir. Admissions    │ admissions@igangaschoolof...ac.ug        │ 2268926931 │ director-admissions │
-- │  Dir. Adm & Req     │ admissions-req@igangaschoolof...ac.ug    │ 2268926931 │ director-admissions │
-- │  School Librarian   │ library@igangaschoolof...ac.ug           │ isnm2026   │ school-librarian │
-- │  Matron             │ matron@igangaschoolof...ac.ug            │ Isnm2026   │ matrons    │
-- │  Warden             │ warden@igangaschoolof...ac.ug            │ Lovely2God  │ wardens    │
-- │  Sickbay Nurse      │ sickbay@igangaschoolof...ac.ug           │ isnm2026   │ sickbay    │
-- │  Guild President    │ guildpresident@igangaschoolof...ac.ug    │ isnm4life  │ guild-president │
-- ├─────────────────────┴──────────────────────────────────────────┴────────────┴────────────┤
-- │  OPERATIONS & LOGISTICS                                                                  │
-- ├─────────────────────┬──────────────────────────────────────────┬────────────┬────────────┤
-- │  Director ICT       │ dannybict@igangaschoolof...ac.ug         │ Lovely2God  │ director-ict │
-- │  Director ICT (Alt) │ directorict@igangaschoolof...ac.ug       │ Lovely2God  │ director-ict │
-- │  Computer Lab Mgr   │ computerlab@igangaschoolof...ac.ug       │ Techno123  │ computer_lab │
-- │  Computer Lab       │ computer-lab@igangaschoolof...ac.ug      │ Techno123  │ computer_lab │
-- │  Skills Lab Mgr     │ skillslab@igangaschoolof...ac.ug         │ Lovely2God  │ skills-lab │
-- │  Skills Lab Tech    │ skills-lab@igangaschoolof...ac.ug        │ Lovely2God  │ skills-lab │
-- │  Storekeeper        │ store@igangaschoolof...ac.ug             │ Isnm4life  │ storekeeper │
-- │  Driver             │ drivers@igangaschoolof...ac.ug           │ isnm4life  │ drivers    │
-- │  Security Officer   │ security@igangaschoolof...ac.ug          │ safty1st   │ security   │
-- ├─────────────────────┴──────────────────────────────────────────┴────────────┴────────────┤
-- │  OTHER ROLES                                                                             │
-- ├─────────────────────┬──────────────────────────────────────────┬────────────┬────────────┤
-- │  Events Coordinator │ events@igangaschoolof...ac.ug            │ Lovely2God  │ events-manager │
-- │  Alumni Officer     │ alumni@igangaschoolof...ac.ug            │ Lovely2God  │ alumni-manager │
-- │  System Admin       │ admin@igangaschoolof...ac.ug             │ Lovely2God  │ system-admin │
-- ├─────────────────────┴──────────────────────────────────────────┴────────────┴────────────┤
-- │  STUDENT PORTAL                                                                          │
-- ├─────────────────────┬──────────────────────────────────────────┬────────────┬────────────┤
-- │  Student 1          │ CM-2026-001 (index_number)               │ student@isnm│ Student Portal │
-- │  Student 2          │ MID-2026-001 (index_number)              │ student@isnm│ Student Portal │
-- │  Student 3          │ DIP-2026-001 (index_number)              │ student@isnm│ Student Portal │
-- └─────────────────────┴──────────────────────────────────────────┴────────────┴────────────┘
--
-- NOTE: Bursar login uses tryBursarAuth() → bursar_users table (students DB)
-- All other staff logins use tryStaffAuth() → staff table (staffs DB)
-- The login system auto-appends @igangaschoolofnursingandmidwifery.ac.ug
-- if you enter just the local part (e.g. "principal").
-- ============================================================
