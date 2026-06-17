-- ==============================================================================
-- ISNM (Iganga School of Nursing & Midwifery) ERP System
-- MASTER DATABASE CREATION SCRIPT
-- Run this FIRST in phpMyAdmin before importing any other SQL files
-- ==============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ==============================================================================
-- DATABASE 1: Main System Database
-- Tables created by: database/isnm_complete_schema.sql
-- ==============================================================================
CREATE DATABASE IF NOT EXISTS `isnm_db`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- ==============================================================================
-- DATABASE 2: Students Database
-- Tables created by: sql/students/igangaschoolofl_students_db.sql
-- ==============================================================================
CREATE DATABASE IF NOT EXISTS `igangaschoolofl_students_db`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- ==============================================================================
-- DATABASE 3: Staff Database
-- Tables created by: sql/staffs/igangaschoolofl_staffs_db.sql
-- Also used by: sql/staffs/99_MASTER_ALL_DEPARTMENTS.sql
-- ==============================================================================
CREATE DATABASE IF NOT EXISTS `igangaschoolofl_staffs_db`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- ==============================================================================
-- DATABASE 4: Website Database
-- Tables created by: sql/website/igangaschoolofl_website_db.sql
-- ==============================================================================
CREATE DATABASE IF NOT EXISTS `igangaschoolofl_website_db`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- ==============================================================================
-- DATABASE 5: ICT Database
-- Tables created by: sql/ict/igangaschoolofl_ict.sql
-- ==============================================================================
CREATE DATABASE IF NOT EXISTS `igangaschoolofl_ict`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- ==============================================================================
-- Verification
-- ==============================================================================
SELECT 'All 5 databases created successfully:' AS 'Status'
UNION ALL
SELECT '1. isnm_db'
UNION ALL
SELECT '2. igangaschoolofl_students_db'
UNION ALL
SELECT '3. igangaschoolofl_staffs_db'
UNION ALL
SELECT '4. igangaschoolofl_website_db'
UNION ALL
SELECT '5. igangaschoolofl_ict';
