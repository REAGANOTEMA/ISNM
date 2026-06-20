-- ============================================================
-- ISNM Bursar System - Financial Tables Migration
-- Database: igangaschoolofl_staffs_db
-- Description: Creates all missing financial tables for the
--              bursar module. Each table uses IF NOT EXISTS
--              to avoid duplicate creation.
-- ============================================================

-- ---------------------------------------------------
-- 1. Student Billing & Fees Management
-- ---------------------------------------------------

-- Fee item catalog per program/year
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.bursar_fee_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255),
    category ENUM('tuition','accommodation','clinical','library','sports','caution','registration','other'),
    amount DECIMAL(12,2),
    program VARCHAR(255),
    academic_year VARCHAR(20),
    is_active TINYINT(1) DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Generated invoices per student
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.bursar_invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) UNIQUE,
    student_id VARCHAR(50),
    student_name VARCHAR(255),
    program VARCHAR(255),
    academic_year VARCHAR(20),
    semester VARCHAR(20),
    total_amount DECIMAL(12,2),
    amount_paid DECIMAL(12,2) DEFAULT 0,
    balance DECIMAL(12,2),
    due_date DATE,
    status ENUM('pending','partial','paid','overdue','cancelled','written_off') DEFAULT 'pending',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Late payment penalties
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.bursar_penalties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50),
    invoice_id INT,
    penalty_amount DECIMAL(12,2),
    reason VARCHAR(255),
    applied_date DATE,
    status ENUM('pending','waived','paid') DEFAULT 'pending',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Scholarships / sponsorships
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.bursar_scholarships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50),
    sponsor_name VARCHAR(255),
    sponsor_contact VARCHAR(100),
    sponsorship_type ENUM('full','partial','merit','need','other'),
    amount DECIMAL(12,2),
    percentage DECIMAL(5,2),
    start_date DATE,
    end_date DATE,
    status ENUM('active','expired','cancelled') DEFAULT 'active',
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 2. Payment Processing
-- ---------------------------------------------------

-- Detailed payment records
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.bursar_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_number VARCHAR(50) UNIQUE,
    student_id VARCHAR(50),
    invoice_id INT,
    amount DECIMAL(12,2),
    payment_method ENUM('cash','bank_transfer','mobile_money','cheque','pos','other'),
    payment_reference VARCHAR(255),
    mobile_number VARCHAR(20),
    transaction_id VARCHAR(100),
    receipt_number VARCHAR(50),
    proof_file VARCHAR(500),
    notes TEXT,
    status ENUM('pending','verified','approved','rejected','bounced') DEFAULT 'pending',
    verified_by INT,
    approved_by INT,
    payment_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Receipt records
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.bursar_receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_number VARCHAR(50) UNIQUE,
    payment_id INT,
    student_id VARCHAR(50),
    student_name VARCHAR(255),
    amount DECIMAL(12,2),
    amount_in_words VARCHAR(500),
    payment_method VARCHAR(50),
    payment_date DATE,
    generated_by INT,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 3. Financial Reports
-- ---------------------------------------------------

-- Daily collection snapshots (auto-populated via cron)
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.bursar_daily_collections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    collection_date DATE UNIQUE,
    total_collected DECIMAL(12,2) DEFAULT 0,
    transaction_count INT DEFAULT 0,
    cash_total DECIMAL(12,2) DEFAULT 0,
    mobile_money_total DECIMAL(12,2) DEFAULT 0,
    bank_total DECIMAL(12,2) DEFAULT 0,
    cheque_total DECIMAL(12,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 4. Budgeting & Expenditure
-- ---------------------------------------------------

-- Budget line items
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.bursar_budget_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    budget_id INT,
    category VARCHAR(255),
    description TEXT,
    allocated DECIMAL(12,2),
    spent DECIMAL(12,2) DEFAULT 0,
    remaining DECIMAL(12,2) GENERATED ALWAYS AS (allocated - spent) STORED,
    fiscal_year VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Expense records
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.bursar_expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expense_number VARCHAR(50) UNIQUE,
    budget_item_id INT,
    category VARCHAR(255),
    description TEXT,
    amount DECIMAL(12,2),
    vendor VARCHAR(255),
    payment_method VARCHAR(50),
    receipt_attachment VARCHAR(500),
    status ENUM('pending','approved','rejected','paid') DEFAULT 'pending',
    requested_by INT,
    approved_by INT,
    expense_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 5. Payroll
-- ---------------------------------------------------

-- Payroll records
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.bursar_payroll (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payroll_number VARCHAR(50) UNIQUE,
    staff_id INT,
    staff_name VARCHAR(255),
    basic_salary DECIMAL(12,2),
    allowances DECIMAL(12,2) DEFAULT 0,
    deductions DECIMAL(12,2) DEFAULT 0,
    net_pay DECIMAL(12,2) GENERATED ALWAYS AS (basic_salary + allowances - deductions) STORED,
    pay_period VARCHAR(20),
    payment_date DATE,
    status ENUM('draft','approved','paid','cancelled') DEFAULT 'draft',
    processed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Staff allowances
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.bursar_allowances (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT,
    allowance_type VARCHAR(100),
    amount DECIMAL(12,2),
    is_recurring TINYINT(1) DEFAULT 0,
    pay_period VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Staff deductions
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.bursar_deductions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT,
    deduction_type VARCHAR(100),
    amount DECIMAL(12,2),
    is_recurring TINYINT(1) DEFAULT 0,
    pay_period VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 6. Asset Financial Tracking
-- ---------------------------------------------------

-- Financial asset tracking
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.bursar_assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_name VARCHAR(255),
    category VARCHAR(100),
    purchase_date DATE,
    purchase_cost DECIMAL(12,2),
    current_value DECIMAL(12,2),
    depreciation_method ENUM('straight_line','declining','none') DEFAULT 'straight_line',
    depreciation_rate DECIMAL(5,2),
    useful_life_years INT,
    salvage_value DECIMAL(12,2) DEFAULT 0,
    supplier VARCHAR(255),
    invoice_reference VARCHAR(100),
    status ENUM('active','disposed','sold','written_off') DEFAULT 'active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 7. Communication Tools
-- ---------------------------------------------------

-- Fee reminder history
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.bursar_fee_reminders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50),
    reminder_type ENUM('email','sms','whatsapp','system'),
    message TEXT,
    sent_date DATE,
    status ENUM('sent','failed','pending') DEFAULT 'sent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------
-- 8. URA / Tax Reporting
-- ---------------------------------------------------

-- Tax records
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.bursar_tax_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tax_period VARCHAR(20),
    tax_type ENUM('withholding','vat','income_tax','other'),
    total_revenue DECIMAL(12,2),
    total_tax DECIMAL(12,2),
    filing_status ENUM('not_filed','filed','pending') DEFAULT 'not_filed',
    filed_date DATE,
    filed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
