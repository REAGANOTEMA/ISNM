-- ============================================================
-- ISNM MASTER SQL - ALL DEPARTMENTS DASHBOARDS
-- Run this file to set up the complete system
-- ============================================================

-- Execute all SQL files in order
-- NOTE: phpMyAdmin's SQL editor does not support the client-only `SOURCE` command.
-- If you are running these files inside phpMyAdmin, import each referenced file individually in the order below.
-- Alternatively run from the MySQL client (recommended) using: mysql -u user -p < file.sql

-- 0. Database Foundations and Student System
SOURCE create_databases.sql;
USE igangaschoolofl_staffs_db;
SET FOREIGN_KEY_CHECKS = 0;

SOURCE sql/students/01_create_students_database.sql;
SOURCE sql/students/bursar_system.sql;

-- 1. Core staffs database with all departments
SOURCE sql/staffs/04_final_complete_staffs_database.sql;

-- 2. All departments complete dashboards with student search
SOURCE sql/staffs/05_all_departments_complete_dashboards.sql;

-- 3. Academic Registrar Dashboard
SOURCE sql/staffs/06_academic_registrar_dashboard.sql;

-- 4. Nursing Department Dashboard
SOURCE sql/staffs/07_nursing_department_dashboard.sql;

-- 5. Midwifery Department Dashboard
SOURCE sql/staffs/08_midwifery_department_dashboard.sql;

-- 6. HR Manager Dashboard
SOURCE sql/staffs/09_hr_manager_dashboard.sql;

-- 7. Library Dashboard
SOURCE sql/staffs/10_library_dashboard.sql;

-- 8. Security Dashboard
SOURCE sql/staffs/11_security_dashboard.sql;

-- 9. Sickbay Dashboard (formerly Lab Technicians)
SOURCE sql/staffs/12_sickbay_dashboard.sql;

-- 10. Matrons & Wardens Dashboard
SOURCE sql/staffs/13_matrons_wardens_dashboard.sql;

-- 11. Director Academics Dashboard
SOURCE sql/staffs/14_director_academics_dashboard.sql;

-- 12. Director Finance Dashboard
SOURCE sql/staffs/15_director_finance_dashboard.sql;

-- 13. Student Management Permissions and Procedures
SOURCE sql/staffs/16_student_management_permissions.sql;
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- OFFICIAL STAFF LOGIN CREDENTIALS SUMMARY (NOT FOR STUDENTS)
-- ============================================================
-- Director General: directorgeneral@igangaschoolofnursingandmidwifery.ac.ug / DorisJoy2026
-- CEO: ceo@igangaschoolofnursingandmidwifery.ac.ug / Lovely2God
-- Director Academics: directoracademic@igangaschoolofnursingandmidwifery.ac.ug / Stephen123
-- Director Finance: finance@igangaschoolofnursingandmidwifery.ac.ug / DorisJoy2026
-- School Principal: principal@igangaschoolofnursingandmidwifery.ac.ug / isnm2026
-- Deputy Principal: dep-principal@igangaschoolofnursingandmidwifery.ac.ug / Isnm2026
-- Academic Registrar: academicregistrar@igangaschoolofnursingandmidwifery.ac.ug / Lovely2God
-- HR Manager: hr-manager@igangaschoolofnursingandmidwifery.ac.ug / Alexis2026
-- School Secretary: secretary@igangaschoolofnursingandmidwifery.ac.ug / Lovely2God
-- School Librarian: library@igangaschoolofnursingandmidwifery.ac.ug / isnm2026
-- Head of Nursing: nursing-dep@igangaschoolofnursingandmidwifery.ac.ug / isnm4life
-- Head of Midwifery: midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug / Life2save
-- Senior Lecturers: senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug / isnm2026
-- Lecturers: lecturers@igangaschoolofnursingandmidwifery.ac.ug / Isnm4life
-- Matrons: matron@igangaschoolofnursingandmidwifery.ac.ug / Isnm2026
-- Wardens: warden@igangaschoolofnursingandmidwifery.ac.ug / Lovely2God
-- Sickbay: sickbay@igangaschoolofnursingandmidwifery.ac.ug / isnm2026
-- Drivers: drivers@igangaschoolofnursingandmidwifery.ac.ug / isnm4life
-- Security: security@igangaschoolofnursingandmidwifery.ac.ug / safty1st
-- Store Keeper: store@igangaschoolofnursingandmidwifery.ac.ug / Isnm4life
-- Guild President: guildpresident@igangaschoolofnursingandmidwifery.ac.ug / isnm4life
-- Admissions: admissions@igangaschoolofnursingandmidwifery.ac.ug / 2268926931
-- Director ICT: dannybict@igangaschoolofnursingandmidwifery.ac.ug / Lovely2God
-- ============================================================

COMMIT;