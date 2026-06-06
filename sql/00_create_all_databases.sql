-- ============================================================
-- ISNM COMPLETE MASTER SETUP SQL
-- Run this file in phpMyAdmin SQL tab to set up everything
-- ============================================================

-- Create all four databases
CREATE DATABASE IF NOT EXISTS `igangaschoolofl_staffs_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `igangaschoolofl_students_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `igangaschoolofl_website_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `isnm_ict` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SELECT 'Databases created successfully' as status;
