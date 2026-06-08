-- ============================================================
-- ISNM MASTER SQL - ALL DEPARTMENTS DASHBOARDS
-- Run this file in MySQL CLI (Command Line Client):
--   mysql -u root -p < sql/staffs/99_MASTER_ALL_DEPARTMENTS.sql
-- DO NOT use phpMyAdmin SQL import for this file.
-- For phpMyAdmin, import each file individually in the order below.
-- ============================================================

-- 0. Database Foundations and Student System
USE `igangaschoolofl_staffs_db`;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Create all databases
SOURCE ../00_create_all_databases.sql;

-- 2. Students database schema & bursar system
USE `igangaschoolofl_students_db`;
SOURCE ../students/01_create_students_database.sql;
SOURCE ../students/bursar_system.sql;

-- 3. Core staffs database
USE `igangaschoolofl_staffs_db`;
SOURCE 04_final_complete_staffs_database.sql;

-- 4. All departments complete dashboards with student search
USE `igangaschoolofl_staffs_db`;
SOURCE 05_all_departments_complete_dashboards.sql;

-- 5. Academic Registrar Dashboard
SOURCE 06_academic_registrar_dashboard.sql;

-- 6. Nursing Department Dashboard
SOURCE 07_nursing_department_dashboard.sql;

-- 7. Midwifery Department Dashboard
SOURCE 08_midwifery_department_dashboard.sql;

-- 8. HR Manager Dashboard
SOURCE 09_hr_manager_dashboard.sql;

-- 9. Library Dashboard
SOURCE 10_library_dashboard.sql;

-- 10. Security Dashboard
SOURCE 11_security_dashboard.sql;

-- 11. Sickbay Dashboard (formerly Lab Technicians)
SOURCE 12_sickbay_dashboard.sql;

-- 12. Matrons & Wardens Dashboard
SOURCE 13_matrons_wardens_dashboard.sql;

-- 13. Director Academics Dashboard
SOURCE 14_director_academics_dashboard.sql;

-- 14. Director Finance Dashboard (references students_db tables - requires bursar_system.sql above)
SOURCE 15_director_finance_dashboard.sql;

-- 15. Student Management Permissions and Procedures
USE `igangaschoolofl_staffs_db`;
SOURCE 16_student_management_permissions.sql;

-- 16. Compatibility views (fixes cross-schema references - must be last)
SOURCE 17_compatibility_views.sql;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- 17. Website Database Schema
-- ============================================================
USE `igangaschoolofl_website_db`;
SOURCE sql/website/01_create_website_database.sql;

-- ============================================================
-- 18. ICT Computer Lab Database
-- ============================================================
USE `igangaschoolofl_ict`;
SOURCE sql/ict/01_create_computer_lab_tables.sql;

-- ============================================================
-- 19. ICT Staff Accounts (in staffs database)
-- ============================================================
USE `igangaschoolofl_staffs_db`;
SOURCE sql/ict/create_ict_director_account.sql;
SOURCE sql/ict/create_computer_lab_manager.sql;

COMMIT;