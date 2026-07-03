-- ═══════════════════════════════════════════════════════════════════
-- BURSAR MODULE TABLES — Iganga School of Nursing & Midwifery
-- Drops and recreates all bursar tables to ensure matching schema
-- Safe to re-run (no user data expected in these tables yet)
-- ═══════════════════════════════════════════════════════════════════

USE igangaschoolofl_staffs_db;
SET FOREIGN_KEY_CHECKS = 0;

-- Drop alternate/bursar- prefixed tables first (created by LIKE, no data)
DROP TABLE IF EXISTS bursar_chart_of_accounts;
DROP TABLE IF EXISTS bursar_cashbook;
DROP TABLE IF EXISTS cash_book;
DROP TABLE IF EXISTS bursar_general_ledger;

-- Drop main bursar tables
DROP TABLE IF EXISTS fee_adjustments;
DROP TABLE IF EXISTS late_payment_settings;
DROP TABLE IF EXISTS payment_approvals;
DROP TABLE IF EXISTS bank_accounts;
DROP TABLE IF EXISTS chart_of_accounts;
DROP TABLE IF EXISTS cashbook;
DROP TABLE IF EXISTS general_ledger;
DROP TABLE IF EXISTS asset_categories;
DROP TABLE IF EXISTS assets;
DROP TABLE IF EXISTS donations;
DROP TABLE IF EXISTS ura_reports;
DROP TABLE IF EXISTS cost_centers;
DROP TABLE IF EXISTS expenses;

-- ═══════════════════════════════════════════════════════════════════
-- CREATE ALL TABLES WITH CONSISTENT SCHEMA
-- ═══════════════════════════════════════════════════════════════════

-- 1. Expenditure / Expenses
CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    expense_title VARCHAR(255),
    amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    category VARCHAR(100) DEFAULT 'General',
    requested_by VARCHAR(200) DEFAULT '',
    description TEXT,
    expense_date DATE DEFAULT NULL,
    date DATE DEFAULT NULL,
    status ENUM('pending','approved','rejected','paid') DEFAULT 'pending',
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Cost Centers
CREATE TABLE cost_centers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    code VARCHAR(50) DEFAULT NULL,
    department VARCHAR(200) DEFAULT '',
    budget DECIMAL(15,2) DEFAULT 0,
    allocated_amount DECIMAL(15,2) DEFAULT 0,
    status ENUM('active','inactive','closed') DEFAULT 'active',
    description TEXT,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tax / URA Reports
CREATE TABLE ura_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_name VARCHAR(200) NOT NULL,
    name VARCHAR(200),
    tax_period VARCHAR(50) DEFAULT NULL,
    period VARCHAR(50),
    amount DECIMAL(15,2) DEFAULT 0,
    tax_amount DECIMAL(15,2) DEFAULT 0,
    status ENUM('pending','filed','submitted') DEFAULT 'pending',
    filed_date DATE DEFAULT NULL,
    report_date DATE DEFAULT NULL,
    notes TEXT,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Donations
CREATE TABLE donations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    donor_name VARCHAR(255) NOT NULL,
    name VARCHAR(255),
    full_name VARCHAR(255),
    amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    payment_method VARCHAR(100) DEFAULT 'cash',
    method VARCHAR(100),
    purpose TEXT,
    notes TEXT,
    donation_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending','completed','cancelled') DEFAULT 'completed',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Assets
CREATE TABLE assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_name VARCHAR(255) NOT NULL,
    asset_code VARCHAR(100) DEFAULT NULL,
    asset_category_id INT DEFAULT NULL,
    category VARCHAR(100) DEFAULT NULL,
    purchase_cost DECIMAL(15,2) DEFAULT 0,
    value DECIMAL(15,2) DEFAULT 0,
    purchase_date DATE DEFAULT NULL,
    useful_life_years INT DEFAULT 5,
    salvage_value DECIMAL(15,2) DEFAULT 0,
    depreciation_method VARCHAR(50) DEFAULT 'Straight Line',
    depreciation_value DECIMAL(15,2) DEFAULT 0,
    status ENUM('new','available','in_use','under_maintenance','disposed') DEFAULT 'new',
    location VARCHAR(200) DEFAULT NULL,
    serial_number VARCHAR(100) DEFAULT NULL,
    notes TEXT,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Asset Categories
CREATE TABLE asset_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(200) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. General Ledger
CREATE TABLE general_ledger (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_date DATE DEFAULT NULL,
    date DATE DEFAULT NULL,
    account_name VARCHAR(200) DEFAULT NULL,
    account_code VARCHAR(50) DEFAULT NULL,
    description TEXT,
    debit_amount DECIMAL(15,2) DEFAULT 0,
    debit DECIMAL(15,2) DEFAULT 0,
    credit_amount DECIMAL(15,2) DEFAULT 0,
    credit DECIMAL(15,2) DEFAULT 0,
    reference VARCHAR(100) DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Bursar General Ledger (exact copy via CREATE not LIKE)
CREATE TABLE bursar_general_ledger (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_date DATE DEFAULT NULL,
    date DATE DEFAULT NULL,
    account_name VARCHAR(200) DEFAULT NULL,
    account_code VARCHAR(50) DEFAULT NULL,
    description TEXT,
    debit_amount DECIMAL(15,2) DEFAULT 0,
    debit DECIMAL(15,2) DEFAULT 0,
    credit_amount DECIMAL(15,2) DEFAULT 0,
    credit DECIMAL(15,2) DEFAULT 0,
    reference VARCHAR(100) DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Cashbook
CREATE TABLE cashbook (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_date DATE DEFAULT NULL,
    date DATE DEFAULT NULL,
    description TEXT,
    reference_number VARCHAR(100) DEFAULT NULL,
    debit_amount DECIMAL(15,2) DEFAULT 0,
    cash_in DECIMAL(15,2) DEFAULT 0,
    amount DECIMAL(15,2) DEFAULT 0,
    credit_amount DECIMAL(15,2) DEFAULT 0,
    cash_out DECIMAL(15,2) DEFAULT 0,
    running_balance DECIMAL(15,2) DEFAULT 0,
    balance DECIMAL(15,2) DEFAULT 0,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Cash Book (alternate name - exact copy)
CREATE TABLE cash_book LIKE cashbook;

-- 11. Bursar Cashbook (alternate name - exact copy)
CREATE TABLE bursar_cashbook LIKE cashbook;

-- 12. Chart of Accounts
CREATE TABLE chart_of_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_code VARCHAR(50) NOT NULL,
    account_name VARCHAR(200) NOT NULL,
    account_type ENUM('asset','liability','equity','income','expense') DEFAULT 'asset',
    balance DECIMAL(15,2) DEFAULT 0,
    status ENUM('active','inactive','closed') DEFAULT 'active',
    description TEXT,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. Bursar Chart of Accounts (exact copy)
CREATE TABLE bursar_chart_of_accounts LIKE chart_of_accounts;

-- 14. Bank Accounts (for reconciliation)
CREATE TABLE bank_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_name VARCHAR(200) NOT NULL,
    bank_name VARCHAR(200) DEFAULT NULL,
    account_number VARCHAR(100) NOT NULL,
    current_balance DECIMAL(15,2) DEFAULT 0,
    balance DECIMAL(15,2) DEFAULT 0,
    status ENUM('active','inactive','closed') DEFAULT 'active',
    is_active TINYINT DEFAULT 1,
    description TEXT,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 15. Payment Approvals
CREATE TABLE payment_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    payment_type VARCHAR(50) DEFAULT 'fee_payment',
    requested_by INT DEFAULT NULL,
    approved_by INT DEFAULT NULL,
    approval_status ENUM('pending','approved','rejected') DEFAULT 'pending',
    approval_remarks TEXT,
    approved_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 16. Late Payment Settings
CREATE TABLE late_payment_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_by INT DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 17. Fee Adjustments
CREATE TABLE fee_adjustments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    adjustment_type ENUM('discount','waiver','refund','penalty') DEFAULT 'discount',
    type VARCHAR(50) DEFAULT 'discount',
    amount DECIMAL(15,2) NOT NULL DEFAULT 0,
    reason TEXT,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ═══════════════════════════════════════════════════════════════════
-- SEED DATA
-- ═══════════════════════════════════════════════════════════════════

-- Asset Categories
INSERT IGNORE INTO asset_categories (category_name, description) VALUES
('Furniture', 'Desks, chairs, tables, cabinets'),
('Electronics', 'Computers, printers, projectors'),
('Medical Equipment', 'Beds, monitors, diagnostic tools'),
('Vehicles', 'School vehicles, ambulances'),
('Buildings', 'School buildings and structures'),
('Library', 'Books and library equipment');

-- Chart of Accounts (using explicit column list that matches exactly)
INSERT IGNORE INTO chart_of_accounts (account_code, account_name, account_type, balance, status) VALUES
('1000', 'Cash', 'asset', 0, 'active'),
('1100', 'Bank Accounts', 'asset', 0, 'active'),
('1200', 'Accounts Receivable', 'asset', 0, 'active'),
('2000', 'Accounts Payable', 'liability', 0, 'active'),
('3000', 'Retained Earnings', 'equity', 0, 'active'),
('4000', 'Tuition Fees', 'income', 0, 'active'),
('4100', 'Donations', 'income', 0, 'active'),
('5000', 'Salaries', 'expense', 0, 'active'),
('5100', 'Utilities', 'expense', 0, 'active'),
('5200', 'Supplies', 'expense', 0, 'active');

-- Bursar Chart of Accounts (explicit column list, not SELECT *)
INSERT IGNORE INTO bursar_chart_of_accounts (account_code, account_name, account_type, balance, status)
SELECT account_code, account_name, account_type, balance, status FROM chart_of_accounts
WHERE account_code IN ('1000','1100','1200','2000','3000','4000','4100','5000','5100','5200');

-- Bank Accounts
INSERT IGNORE INTO bank_accounts (bank_name, account_name, account_number, current_balance, balance, status, is_active) VALUES
('Stanbic Bank', 'School Operations Account', '9030001234567', 5000000.00, 5000000.00, 'active', 1),
('Centenary Bank', 'School Tuition Account', '3200123456', 15000000.00, 15000000.00, 'active', 1),
('DFCU Bank', 'School Development Fund', '0100123456789', 3000000.00, 3000000.00, 'active', 1);

-- Cost Centers
INSERT IGNORE INTO cost_centers (name, code, department, budget, status) VALUES
('Academic Affairs', 'CC-001', 'Academic', 200000000, 'active'),
('Administration', 'CC-002', 'Admin', 100000000, 'active'),
('Clinical Services', 'CC-003', 'Clinical', 150000000, 'active'),
('Library', 'CC-004', 'Library', 50000000, 'active'),
('Hostel Management', 'CC-005', 'Welfare', 80000000, 'active');

-- Late Payment Default Settings
INSERT IGNORE INTO late_payment_settings (setting_key, setting_value) VALUES
('grace_period_days', '15'),
('late_fee_percentage', '5'),
('late_fee_fixed', '20000'),
('max_late_fee', '100000');

-- Sample Expenses
INSERT IGNORE INTO expenses (title, expense_title, amount, category, requested_by, expense_date, status) VALUES
('Office Supplies - January', 'Office Supplies - January', 2500000, 'Administrative', 'Bursar', CURDATE(), 'approved'),
('Electricity Bill - January', 'Electricity Bill - January', 1800000, 'Utilities', 'Bursar', CURDATE(), 'paid'),
('Internet Subscription', 'Internet Subscription', 500000, 'Utilities', 'ICT Department', DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'approved'),
('Cleaning Materials', 'Cleaning Materials', 350000, 'General', 'Matron', DATE_SUB(CURDATE(), INTERVAL 7 DAY), 'pending');

-- Sample Donations
INSERT IGNORE INTO donations (donor_name, amount, payment_method, purpose, donation_date, status) VALUES
('John Doe Foundation', 5000000, 'bank', 'Library renovation fund', DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'completed'),
('Parents Association', 2000000, 'cash', 'Sports equipment', DATE_SUB(CURDATE(), INTERVAL 20 DAY), 'completed'),
('Anonymous Donor', 1000000, 'mobile_money', 'Student welfare', DATE_SUB(CURDATE(), INTERVAL 15 DAY), 'completed');

-- Sample Assets
INSERT IGNORE INTO assets (asset_name, asset_code, asset_category_id, purchase_cost, purchase_date, useful_life_years, salvage_value, status) VALUES
('Dell Desktop Computers (x20)', 'AST-001', 2, 30000000, '2024-01-15', 5, 3000000, 'available'),
('HP LaserJet Printers (x5)', 'AST-002', 2, 7500000, '2024-01-15', 3, 750000, 'in_use'),
('Hospital Beds (x30)', 'AST-003', 3, 45000000, '2024-02-01', 10, 4500000, 'new'),
('School Bus - Toyota Coaster', 'AST-004', 4, 180000000, '2023-06-01', 15, 18000000, 'in_use'),
('Library Books Collection', 'AST-005', 6, 15000000, '2024-03-01', 5, 1500000, 'available'),
('Projectors (x10)', 'AST-006', 2, 12000000, '2024-04-01', 5, 1200000, 'in_use');

-- Sample General Ledger
INSERT IGNORE INTO general_ledger (transaction_date, account_name, description, debit_amount, credit_amount) VALUES
(CURDATE(), 'Cash', 'Opening balance', 10000000, 0),
(CURDATE(), 'Tuition Fees', 'Fee collection - January', 0, 5000000),
(DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Bank Accounts', 'Bank deposit', 5000000, 0),
(DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'Salaries', 'Staff salary payment', 0, 8000000),
(DATE_SUB(CURDATE(), INTERVAL 5 DAY), 'Utilities', 'Electricity bill', 0, 1800000);

-- Bursar General Ledger (explicit column list)
INSERT IGNORE INTO bursar_general_ledger (transaction_date, account_name, description, debit_amount, credit_amount)
SELECT transaction_date, account_name, description, debit_amount, credit_amount FROM general_ledger;

-- Sample Cashbook
INSERT IGNORE INTO cashbook (transaction_date, description, reference_number, debit_amount, credit_amount, running_balance) VALUES
(CURDATE(), 'Opening Balance', 'OP-001', 10000000, 0, 10000000),
(CURDATE(), 'Tuition Fee Collection - Student A', 'RCPT-20250101-001', 2500000, 0, 12500000),
(DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'Bank Deposit', 'DEP-001', 0, 5000000, 7500000),
(DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'Office Supplies Purchase', 'PO-001', 0, 2500000, 5000000),
(DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'Electricity Bill Payment', 'UTIL-001', 0, 1800000, 3200000);

-- Bursar Cashbook (explicit column list)
INSERT IGNORE INTO bursar_cashbook (transaction_date, description, reference_number, debit_amount, credit_amount, running_balance)
SELECT transaction_date, description, reference_number, debit_amount, credit_amount, running_balance FROM cashbook;

-- Sample Tax Reports
INSERT IGNORE INTO ura_reports (report_name, tax_period, amount, tax_amount, status, filed_date) VALUES
('PAYE Return - January 2025', '2025-01', 80000000, 9600000, 'filed', '2025-02-10'),
('NSSF Return - January 2025', '2025-01', 80000000, 8000000, 'filed', '2025-02-10'),
('Withholding Tax - Q1 2025', '2025-Q1', 200000000, 12000000, 'pending', NULL);

SET FOREIGN_KEY_CHECKS = 1;

SELECT CONCAT('✓ Bursar tables created and seeded successfully on ', NOW()) AS result;
