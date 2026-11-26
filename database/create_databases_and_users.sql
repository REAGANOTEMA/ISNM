-- ============================================================================
-- ISNM - Create the two application databases and their users
-- ============================================================================
-- This file is the ONLY script that needs a privileged account (root / admin).
-- Run it BEFORE importing the data dumps.
--
-- SECURITY: no passwords are stored in this file. Replace the two placeholder
-- tokens below with strong, unique passwords before running it, then keep the
-- populated copy outside version control (or supply the values via your
-- deployment secret manager).
--
-- Databases:
--   igangaschool_system   - all ERP tables (students, staff, finance, ict, payroll, ...)
--   igangaschool_website  - public website content
--
-- Usage:
--   1. Copy this file to a private location and replace the password placeholders.
--   2. mysql -uroot -p < create_databases_and_users.sql
--   3. Import the dumps with the same credentials configured in .env.
--
-- Safe to re-run only after the placeholders are replaced (CREATE USER IF NOT
-- EXISTS does not rotate an existing password; use ALTER USER to rotate).
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `igangaschool_system`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

CREATE DATABASE IF NOT EXISTS `igangaschool_website`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

-- Replace REPLACE_WITH_STRONG_SYSTEM_PASSWORD before running.
CREATE USER IF NOT EXISTS 'igangaschool_system'@'localhost' IDENTIFIED BY 'REPLACE_WITH_STRONG_SYSTEM_PASSWORD';
CREATE USER IF NOT EXISTS 'igangaschool_system'@'%'         IDENTIFIED BY 'REPLACE_WITH_STRONG_SYSTEM_PASSWORD';

-- Replace REPLACE_WITH_STRONG_WEBSITE_PASSWORD before running.
CREATE USER IF NOT EXISTS 'igangaschool_website'@'localhost' IDENTIFIED BY 'REPLACE_WITH_STRONG_WEBSITE_PASSWORD';
CREATE USER IF NOT EXISTS 'igangaschool_website'@'%'         IDENTIFIED BY 'REPLACE_WITH_STRONG_WEBSITE_PASSWORD';

GRANT ALL PRIVILEGES ON `igangaschool_system`.*  TO 'igangaschool_system'@'localhost';
GRANT ALL PRIVILEGES ON `igangaschool_system`.*  TO 'igangaschool_system'@'%';
GRANT ALL PRIVILEGES ON `igangaschool_website`.* TO 'igangaschool_website'@'localhost';
GRANT ALL PRIVILEGES ON `igangaschool_website`.* TO 'igangaschool_website'@'%';

FLUSH PRIVILEGES;
