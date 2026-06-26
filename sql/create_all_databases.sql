-- ==============================================================================
-- ISNM (Iganga School of Nursing & Midwifery) ERP System
-- DATABASE IMPORT REFERENCE
-- 
-- IMPORTANT: On shared hosting, the databases are ALREADY created.
-- DO NOT run CREATE DATABASE statements.
-- 
-- Import each SQL file directly into its corresponding database via phpMyAdmin:
--   1. sql/ict/igangaschoolofl_ict.sql          → igangaschoolofl_ict
--   2. sql/students/igangaschoolofl_students_db.sql → igangaschoolofl_students_db
--   3. sql/staffs/igangaschoolofl_staffs_db.sql  → igangaschoolofl_staffs_db
--   4. sql/website/igangaschoolofl_website_db.sql → igangaschoolofl_website_db
-- ==============================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- ==============================================================================
-- DATABASES (Already exist on shared hosting — no CREATE DATABASE needed)
-- ==============================================================================
-- Main System / ICT  : igangaschoolofl_ict
-- Students           : igangaschoolofl_students_db
-- Staff              : igangaschoolofl_staffs_db
-- Website            : igangaschoolofl_website_db
-- ==============================================================================

-- Select the main database as a sanity check (no CREATE or USE required)
-- Simply verify existence by listing tables in the current database.
