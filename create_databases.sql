-- Create all three databases for ISNM system
-- Run this script in phpMyAdmin or MySQL command line

-- Create staffs_db
CREATE DATABASE IF NOT EXISTS staffs_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create students_db  
CREATE DATABASE IF NOT EXISTS students_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Create website_db
CREATE DATABASE IF NOT EXISTS website_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Show databases
SHOW DATABASES LIKE '%isnm%';
SHOW DATABASES LIKE '%_db';
