-- ============================================================
-- ISNM MASTER SETUP - RUN IN MySQL COMMAND LINE
-- For phpMyAdmin: import each SQL file individually
-- ============================================================

-- Step 1: Staff Database Core Schema
USE `igangaschoolofl_staffs_db`;
SOURCE sql/staffs/04_final_complete_staffs_database.sql;

-- Step 2: Department Dashboards & Accounts
SOURCE sql/staffs/05_all_departments_complete_dashboards.sql;

-- Step 3: Department-specific dashboard tables
SOURCE sql/staffs/06_academic_registrar_dashboard.sql;
SOURCE sql/staffs/07_nursing_department_dashboard.sql;
SOURCE sql/staffs/08_midwifery_department_dashboard.sql;
SOURCE sql/staffs/09_hr_manager_dashboard.sql;
SOURCE sql/staffs/10_library_dashboard.sql;
SOURCE sql/staffs/11_security_dashboard.sql;
SOURCE sql/staffs/12_sickbay_dashboard.sql;
SOURCE sql/staffs/13_matrons_wardens_dashboard.sql;
SOURCE sql/staffs/14_director_academics_dashboard.sql;
SOURCE sql/staffs/15_director_finance_dashboard.sql;
SOURCE sql/staffs/16_student_management_permissions.sql;

-- Step 4: Compatibility views (fixes cross-schema references)
SOURCE sql/staffs/17_compatibility_views.sql;

-- Step 5: Students Database Schema
USE `igangaschoolofl_students_db`;
SOURCE sql/students/01_create_students_database.sql;
SOURCE sql/students/bursar_system.sql;

-- Step 6: Website Database Schema
USE `igangaschoolofl_website_db`;
SOURCE sql/website/01_create_website_database.sql;

-- Step 7: ICT Computer Lab Database
USE `igangaschoolofl_ict`;
SOURCE sql/ict/01_create_computer_lab_tables.sql;

-- Step 8: Create ICT accounts in staff database
USE `igangaschoolofl_staffs_db`;
SOURCE sql/ict/create_ict_director_account.sql;
SOURCE sql/ict/create_computer_lab_manager.sql;

SELECT '========================================' as '';
SELECT 'ISNM COMPLETE SETUP FINISHED!' as '';
SELECT 'Databases: igangaschoolofl_staffs_db, igangaschoolofl_students_db, igangaschoolofl_website_db, igangaschoolofl_ict' as '';
SELECT 'Run this in MySQL CLI. For phpMyAdmin, import individual SQL files.' as '';
