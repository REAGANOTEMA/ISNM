-- ============================================================================
-- ISNM - Create the two application databases and their users
-- ============================================================================
-- This file is the ONLY script that needs a privileged account (root / admin).
-- It must be run BEFORE importing the data dumps, because the dumps are
-- intentionally privilege-safe (they contain no CREATE DATABASE, USE,
-- CREATE USER, GRANT, or DEFINER clauses).
--
-- Databases:
--   igangaschool_system   - all ERP tables (students, staff, finance, ict, payroll, ...)
--   igangaschool_website  - public website content
--
-- Each database gets its own user (matching the database name) with access
-- from both localhost and remote host, using the database name as password.
--
-- Usage:
--   1. Run this file once as root:      mysql -uroot -p < create_databases_and_users.sql
--   2. Import the system dump:          mysql -uigangaschool_system -p'...' igangaschool_system < sql/system/igangaschool_system.sql
--   3. Import the website dump:         mysql -uigangaschool_website -p'...' igangaschool_website < sql/website/igangaschool_website.sql
--
-- Safe to re-run (idempotent).
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. Databases
-- ----------------------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `igangaschool_system`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE DATABASE IF NOT EXISTS `igangaschool_website`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 2. Users
-- ----------------------------------------------------------------------------
CREATE USER IF NOT EXISTS 'igangaschool_system'@'localhost'   IDENTIFIED BY 'gNfXJDpigN%[u@,f';
CREATE USER IF NOT EXISTS 'igangaschool_system'@'%'           IDENTIFIED BY 'gNfXJDpigN%[u@,f';
CREATE USER IF NOT EXISTS 'igangaschool_website'@'localhost'  IDENTIFIED BY '0OTG!R$F0,ad$F{[';
CREATE USER IF NOT EXISTS 'igangaschool_website'@'%'          IDENTIFIED BY '0OTG!R$F0,ad$F{[';

-- ----------------------------------------------------------------------------
-- 3. Grants
-- ----------------------------------------------------------------------------
GRANT ALL PRIVILEGES ON `igangaschool_system`.*  TO 'igangaschool_system'@'localhost';
GRANT ALL PRIVILEGES ON `igangaschool_system`.*  TO 'igangaschool_system'@'%';
GRANT ALL PRIVILEGES ON `igangaschool_website`.* TO 'igangaschool_website'@'localhost';
GRANT ALL PRIVILEGES ON `igangaschool_website`.* TO 'igangaschool_website'@'%';

-- Apply the privilege changes immediately.
FLUSH PRIVILEGES;
