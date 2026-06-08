-- ============================================================
-- ISNM MASTER SETUP - SINGLE FILE IMPORT FOR PHPMYADMIN
-- ============================================================
-- For phpMyAdmin: Import the single file "00_MASTER_ALL_IN_ONE.sql"
-- Alternatively, you can import each SQL file individually in the order shown below
-- ============================================================

-- Step 1: Create databases
SOURCE sql/00_create_all_databases.sql;

-- Step 2: Staffs Database Core Schema (MUST be BEFORE students for cross-database FK references)
USE `igangaschoolofl_staffs_db`;
SOURCE sql/staffs/04_final_complete_staffs_database.sql;

-- Step 3: Students Database Schema (with bursar financial tables)
-- Note: bursar_system.sql has FKs to igangaschoolofl_staffs_db.staff - requires staff table
USE `igangaschoolofl_students_db`;
SOURCE sql/students/01_create_students_database.sql;
SOURCE sql/students/bursar_system.sql;

-- Step 4: Department Dashboards (views reference students_db tables, so run after step 3)
USE `igangaschoolofl_staffs_db`;
SOURCE sql/staffs/05_all_departments_complete_dashboards.sql;

-- Step 5: Department-specific dashboard tables
SOURCE sql/staffs/06_academic_registrar_dashboard.sql;
SOURCE sql/staffs/07_nursing_department_dashboard.sql;
SOURCE sql/staffs/08_midwifery_department_dashboard.sql;
SOURCE sql/staffs/09_hr_manager_dashboard.sql;
SOURCE sql/staffs/10_library_dashboard.sql;
SOURCE sql/staffs/11_security_dashboard.sql;
SOURCE sql/staffs/12_sickbay_dashboard.sql;
SOURCE sql/staffs/13_matrons_wardens_dashboard.sql;
SOURCE sql/staffs/14_director_academics_dashboard.sql;

-- Step 6: Director Finance Dashboard (requires students_db fee tables from step 2)
SOURCE sql/staffs/15_director_finance_dashboard.sql;

-- Step 7: Student Management Permissions
SOURCE sql/staffs/16_student_management_permissions.sql;

-- Step 8: Compatibility views (fixes cross-schema references)
SOURCE sql/staffs/17_compatibility_views.sql;

-- Step 9: ICT Computer Lab Database
USE `igangaschoolofl_ict`;
SOURCE sql/ict/01_create_computer_lab_tables.sql;

-- Step 10: Create ICT accounts in staff database
USE `igangaschoolofl_staffs_db`;
SOURCE sql/ict/create_ict_director_account.sql;
SOURCE sql/ict/create_computer_lab_manager.sql;

-- Step 11: Staff login credentials
SOURCE sql/staffs/staffs_logins.sql;

-- Step 12: Website Database Schema
USE `igangaschoolofl_website_db`;
SOURCE sql/website/01_create_website_database.sql;

SELECT '========================================' as '';
SELECT 'ISNM COMPLETE SETUP FINISHED!' as '';
SELECT 'Databases: igangaschoolofl_staffs_db, igangaschoolofl_students_db, igangaschoolofl_website_db, igangaschoolofl_ict' as '';
SELECT 'Run this in MySQL CLI. For phpMyAdmin, import individual SQL files.' as '';
