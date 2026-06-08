-- ISNM FINAL LOGIN FIX - SECURE & ERROR FREE
-- Uses SHA2 password hashing. PHP auth code must also use SHA2 for verification.
-- No DROP DATABASE, no DROP TABLE - safe to run multiple times
-- Creates database, tables, roles, and all staff with hashed passwords

-- Env: localhost only. DB user must have CREATE/ALTER/INSERT grants on staffs_db.

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS igangaschoolofl_staffs_db 
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `igangaschoolofl_staffs_db`;

-- Disable foreign key checks for safe re-runs
SET FOREIGN_KEY_CHECKS = 0;

-- Drop and recreate only the 2 tables we need (safe - IF EXISTS)
DROP TABLE IF EXISTS staff;
DROP TABLE IF EXISTS staff_roles;

SET FOREIGN_KEY_CHECKS = 1;

-- Create roles table
CREATE TABLE staff_roles(
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(100) UNIQUE NOT NULL,
    dashboard_path VARCHAR(255)
);

-- Create staff table
CREATE TABLE staff(
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(50) UNIQUE,
    email VARCHAR(150) UNIQUE NOT NULL,
    `password` CHAR(64) NOT NULL,
    role_id INT,
    FOREIGN KEY(role_id) REFERENCES staff_roles(id)
);

-- Insert all roles
INSERT IGNORE INTO staff_roles(role_name,dashboard_path) VALUES
('Director ICT','dashboards/director-ict.php'),
('Director General','dashboards/director-general.php'),
('CEO','dashboards/ceo.php'),
('Director Academics','dashboards/director-academics.php'),
('Director Finance','dashboards/director-finance.php'),
('School Principal','dashboards/school-principal.php'),
('Deputy Principal','dashboards/deputy-principal.php'),
('Academic Registrar','dashboards/academic-registrar.php'),
('HR Manager','dashboards/hr-manager.php'),
('School Secretary','dashboards/school-secretary.php'),
('School Librarian','dashboards/school-librarian.php'),
('Head Nursing','dashboards/head-nursing.php'),
('Head Midwifery','dashboards/head-midwifery.php'),
('Senior Lecturers','dashboards/senior-lecturers.php'),
('Lecturers','dashboards/lecturers.php'),
('Matrons','dashboards/matrons.php'),
('Wardens','dashboards/wardens.php'),
('Sickbay','dashboards/sickbay.php'),
('Drivers','dashboards/drivers.php'),
('Security','dashboards/security.php'),
('Store Keeper','dashboards/storekeeper.php'),
('Guild President','dashboards/guild-president.php'),
('Director Admissions & Requirements','dashboards/director-admissions.php');

-- Insert all staff with hashed passwords (SHA2-256)
INSERT INTO staff(staff_id,email,`password`,role_id) VALUES
('ICT001','computer-lab@igangaschoolofnursingandmidwifery.ac.ug',SHA2('Techno123',256),(SELECT id FROM staff_roles WHERE role_name='Director ICT')),
('DG001','directorgeneral@igangaschoolofnursingandmidwifery.ac.ug',SHA2('DorisJoy2026',256),(SELECT id FROM staff_roles WHERE role_name='Director General')),
('CEO001','ceo@igangaschoolofnursingandmidwifery.ac.ug',SHA2('Lovely2God',256),(SELECT id FROM staff_roles WHERE role_name='CEO')),
('DA001','directoracademic@igangaschoolofnursingandmidwifery.ac.ug',SHA2('Stephen123',256),(SELECT id FROM staff_roles WHERE role_name='Director Academics')),
('FIN001','finance@igangaschoolofnursingandmidwifery.ac.ug',SHA2('DorisJoy2026',256),(SELECT id FROM staff_roles WHERE role_name='Director Finance')),
('PR001','principal@igangaschoolofnursingandmidwifery.ac.ug',SHA2('isnm2026',256),(SELECT id FROM staff_roles WHERE role_name='School Principal')),
('DP001','dep-principal@igangaschoolofnursingandmidwifery.ac.ug',SHA2('Isnm2026',256),(SELECT id FROM staff_roles WHERE role_name='Deputy Principal')),
('REG001','academicregistrar@igangaschoolofnursingandmidwifery.ac.ug',SHA2('Lovely2God',256),(SELECT id FROM staff_roles WHERE role_name='Academic Registrar')),
('HR001','hr-manager@igangaschoolofnursingandmidwifery.ac.ug',SHA2('Alexis2026',256),(SELECT id FROM staff_roles WHERE role_name='HR Manager')),
('SEC001','secretary@igangaschoolofnursingandmidwifery.ac.ug',SHA2('Lovely2God',256),(SELECT id FROM staff_roles WHERE role_name='School Secretary')),
('LIB001','library@igangaschoolofnursingandmidwifery.ac.ug',SHA2('isnm2026',256),(SELECT id FROM staff_roles WHERE role_name='School Librarian')),
('NUR001','nursing-dep@igangaschoolofnursingandmidwifery.ac.ug',SHA2('isnm4life',256),(SELECT id FROM staff_roles WHERE role_name='Head Nursing')),
('MID001','midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug',SHA2('Life2save',256),(SELECT id FROM staff_roles WHERE role_name='Head Midwifery')),
('SLE001','senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug',SHA2('isnm2026',256),(SELECT id FROM staff_roles WHERE role_name='Senior Lecturers')),
('LEC001','lecturers@igangaschoolofnursingandmidwifery.ac.ug',SHA2('Isnm4life',256),(SELECT id FROM staff_roles WHERE role_name='Lecturers')),
('MAT001','matron@igangaschoolofnursingandmidwifery.ac.ug',SHA2('Isnm2026',256),(SELECT id FROM staff_roles WHERE role_name='Matrons')),
('WD001','warden@igangaschoolofnursingandmidwifery.ac.ug',SHA2('Lovely2God',256),(SELECT id FROM staff_roles WHERE role_name='Wardens')),
('SICK001','sickbay@igangaschoolofnursingandmidwifery.ac.ug',SHA2('isnm2026',256),(SELECT id FROM staff_roles WHERE role_name='Sickbay')),
('DRV001','drivers@igangaschoolofnursingandmidwifery.ac.ug',SHA2('isnm4life',256),(SELECT id FROM staff_roles WHERE role_name='Drivers')),
('SEC002','security@igangaschoolofnursingandmidwifery.ac.ug',SHA2('safty1st',256),(SELECT id FROM staff_roles WHERE role_name='Security')),
('STK001','store@igangaschoolofnursingandmidwifery.ac.ug',SHA2('Isnm4life',256),(SELECT id FROM staff_roles WHERE role_name='Store Keeper')),
('GUILD001','guildpresident@igangaschoolofnursingandmidwifery.ac.ug',SHA2('isnm4life',256),(SELECT id FROM staff_roles WHERE role_name='Guild President')),
('AD001','admissions@igangaschoolofnursingandmidwifery.ac.ug',SHA2('2268926931',256),(SELECT id FROM staff_roles WHERE role_name='Director Admissions & Requirements')),
('ICT002','dannybict@igangaschoolofnursingandmidwifery.ac.ug',SHA2('Lovely2God',256),(SELECT id FROM staff_roles WHERE role_name='Director ICT'));

-- Verification
SELECT '========================================' AS '';
SELECT 'SETUP COMPLETE!' AS status;
SELECT '' AS '';
SELECT 'Total roles: ' AS info, COUNT(*) AS value FROM staff_roles;
SELECT 'Total staff: ' AS info, COUNT(*) AS value FROM staff;
SELECT '' AS '';
SELECT 'ALL LOGIN CREDENTIALS (email / password):' AS '';
SELECT 'computer-lab@... / Techno123' AS '';
SELECT 'directorgeneral@... / DorisJoy2026' AS '';
SELECT 'ceo@... / Lovely2God' AS '';
SELECT 'directoracademic@... / Stephen123' AS '';
SELECT 'finance@... / DorisJoy2026' AS '';
SELECT 'principal@... / isnm2026' AS '';
SELECT 'dep-principal@... / Isnm2026' AS '';
SELECT 'academicregistrar@... / Lovely2God' AS '';
SELECT 'hr-manager@... / Alexis2026' AS '';
SELECT 'secretary@... / Lovely2God' AS '';
SELECT 'library@... / isnm2026' AS '';
SELECT 'nursing-dep@... / isnm4life' AS '';
SELECT 'midwifery-dep@... / Life2save' AS '';
SELECT 'senior-lecturers@... / isnm2026' AS '';
SELECT 'lecturers@... / Isnm4life' AS '';
SELECT 'matron@... / Isnm2026' AS '';
SELECT 'warden@... / Lovely2God' AS '';
SELECT 'sickbay@... / isnm2026' AS '';
SELECT 'drivers@... / isnm4life' AS '';
SELECT 'security@... / safty1st' AS '';
SELECT 'store@... / Isnm4life' AS '';
SELECT 'guildpresident@... / isnm4life' AS '';
SELECT 'admissions@... / 2268926931' AS '';
SELECT 'dannybict@... / Lovely2God' AS '';
