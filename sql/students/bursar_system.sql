-- ============================================================
-- ISNM COMPLETE BURSAR FINANCIAL MANAGEMENT SYSTEM
-- Comprehensive SQL Schema with all required features
-- Database: igangaschoolofl_students_db
-- ============================================================

USE igangaschoolofl_students_db;

-- ============================================================
-- 1. BURSAR USER ACCOUNTS & AUTHENTICATION
-- ============================================================

-- Drop tables in correct order to avoid foreign key constraints
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS proof_of_payments;
DROP TABLE IF EXISTS payment_receipts;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS student_invoices;
DROP TABLE IF EXISTS student_penalties;
DROP TABLE IF EXISTS penalty_configurations;
DROP TABLE IF EXISTS fee_adjustments;
DROP TABLE IF EXISTS scholarships;
DROP TABLE IF EXISTS student_fee_assignments;
DROP TABLE IF EXISTS fee_structures;
DROP TABLE IF EXISTS programs;
DROP TABLE IF EXISTS bursar_users;
DROP TABLE IF EXISTS financial_reports;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS fee_reminders;
DROP TABLE IF EXISTS expenditure_records;
DROP TABLE IF EXISTS budget_records;
DROP TABLE IF EXISTS budgets;
DROP TABLE IF EXISTS cost_centers;
DROP TABLE IF EXISTS chart_of_accounts;
DROP TABLE IF EXISTS general_ledger;
DROP TABLE IF EXISTS cash_book;
DROP TABLE IF EXISTS assets;
DROP TABLE IF EXISTS asset_categories;
DROP TABLE IF EXISTS staff_salaries;
DROP TABLE IF EXISTS salary_components;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS bursar_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('bursar', 'accounts_assistant', 'auditor') DEFAULT 'bursar',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status)
);

-- ... rest of bursar schema ...
