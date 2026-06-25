-- ═══════════════════════════════════════════════════════════════
-- ISNM Bursar/Finance — Missing Tables Migration
-- Creates tables that PHP code references but don't exist in dumps.
-- All use CREATE TABLE IF NOT EXISTS — safe to re-run.
-- ═══════════════════════════════════════════════════════════════

-- 1. fee_payments (queried from staffs_db by school-bursar.php)
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.fee_payments (
    payment_id VARCHAR(50) PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    fee_account_id INT DEFAULT 0,
    amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0,
    payment_method VARCHAR(50) DEFAULT 'Cash',
    payment_reference VARCHAR(100) DEFAULT '',
    receipt_number VARCHAR(50) DEFAULT '',
    notes TEXT,
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'verified',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_student (student_id),
    INDEX idx_receipt (receipt_number),
    INDEX idx_date (payment_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. student_fee_accounts (queried from staffs_db by school-bursar.php)
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.student_fee_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    academic_year VARCHAR(20) DEFAULT '',
    invoice_number VARCHAR(50) DEFAULT '',
    total_fees DECIMAL(12,2) NOT NULL DEFAULT 0,
    amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0,
    balance DECIMAL(12,2) GENERATED ALWAYS AS (total_fees - amount_paid) STORED,
    due_date DATE DEFAULT NULL,
    last_payment_date DATE DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_student (student_id),
    INDEX idx_invoice (invoice_number),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. bursar_general_ledger (referenced by school-bursar.php and bursar-ledger.php)
CREATE TABLE IF NOT EXISTS igangaschoolofl_students_db.bursar_general_ledger (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entry_number VARCHAR(50) NOT NULL UNIQUE,
    account_id INT DEFAULT 0,
    cost_center_id INT DEFAULT 0,
    transaction_type ENUM('Debit','Credit') NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    reference_type VARCHAR(50) DEFAULT '',
    reference_id VARCHAR(50) DEFAULT '',
    description TEXT,
    entry_date DATE DEFAULT (CURDATE()),
    posted_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_entry_date (entry_date),
    INDEX idx_account (account_id),
    INDEX idx_ref (reference_type, reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. bursar_tax_periods (referenced by bursar-tax.php)
CREATE TABLE IF NOT EXISTS igangaschoolofl_students_db.bursar_tax_periods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    period_name VARCHAR(100) NOT NULL,
    fiscal_year VARCHAR(10) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('Open','Closed','Filed') DEFAULT 'Open',
    created_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_period (period_name, fiscal_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. bursar_tax_filings (referenced by bursar-tax.php)
CREATE TABLE IF NOT EXISTS igangaschoolofl_students_db.bursar_tax_filings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tax_period_id INT NOT NULL,
    filing_date DATE DEFAULT (CURDATE()),
    total_revenue DECIMAL(12,2) DEFAULT 0,
    total_tax DECIMAL(12,2) DEFAULT 0,
    filing_reference VARCHAR(100) DEFAULT '',
    status ENUM('Draft','Filed','Amended') DEFAULT 'Draft',
    filed_by INT DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_period (tax_period_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. income_tax_rates (referenced by bursar-tax.php)
CREATE TABLE IF NOT EXISTS igangaschoolofl_students_db.income_tax_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tax_bracket_name VARCHAR(100) NOT NULL,
    min_income DECIMAL(12,2) NOT NULL DEFAULT 0,
    max_income DECIMAL(12,2) DEFAULT NULL,
    tax_rate DECIMAL(5,2) NOT NULL DEFAULT 0,
    fiscal_year VARCHAR(10) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    INDEX idx_bracket (fiscal_year, min_income)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. bank_transactions (referenced by school-bursar.php reconciliation)
CREATE TABLE IF NOT EXISTS igangaschoolofl_students_db.bank_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_date DATE NOT NULL,
    description VARCHAR(255) DEFAULT '',
    reference VARCHAR(100) DEFAULT '',
    debit DECIMAL(12,2) DEFAULT 0,
    credit DECIMAL(12,2) DEFAULT 0,
    balance DECIMAL(12,2) DEFAULT 0,
    reconciled TINYINT(1) DEFAULT 0,
    reconciled_by INT DEFAULT 0,
    reconciled_at DATETIME DEFAULT NULL,
    bank_account VARCHAR(100) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date (transaction_date),
    INDEX idx_reconciled (reconciled),
    INDEX idx_account (bank_account)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. financial_messages (for bursar communications)
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.financial_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    recipient_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    attachment VARCHAR(255) DEFAULT '',
    is_read TINYINT(1) DEFAULT 0,
    read_at DATETIME DEFAULT NULL,
    sender_role VARCHAR(50) DEFAULT '',
    recipient_role VARCHAR(50) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sender (sender_id),
    INDEX idx_recipient (recipient_id),
    INDEX idx_read (is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. financial_notices (bursar/secretary announcements)
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.financial_notices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    author_id INT NOT NULL,
    author_role VARCHAR(50) DEFAULT '',
    priority ENUM('Low','Normal','High','Urgent') DEFAULT 'Normal',
    is_published TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_published (is_published),
    INDEX idx_priority (priority),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. bursar_requisition_reviews (requisition financial review by bursar)
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.bursar_requisition_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requisition_id INT NOT NULL,
    reviewer_id INT NOT NULL,
    review_action ENUM('recommend','return','reject','forward') NOT NULL,
    comments TEXT,
    reviewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_requisition (requisition_id),
    INDEX idx_reviewer (reviewer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. payroll_records in students_db (if missing, complementary to staffs_db version)
CREATE TABLE IF NOT EXISTS igangaschoolofl_students_db.payroll_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    month INT NOT NULL,
    year INT NOT NULL,
    gross_salary DECIMAL(12,2) DEFAULT 0,
    total_deductions DECIMAL(12,2) DEFAULT 0,
    net_salary DECIMAL(12,2) DEFAULT 0,
    processed_by INT DEFAULT 0,
    processing_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Draft','Processed','Approved','Paid') DEFAULT 'Draft',
    UNIQUE KEY uk_payroll (staff_id, month, year),
    INDEX idx_period (month, year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. payslips (dedicated table, complements generated_documents)
CREATE TABLE IF NOT EXISTS igangaschoolofl_students_db.payslips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    payroll_record_id INT DEFAULT 0,
    month INT NOT NULL,
    year INT NOT NULL,
    basic_salary DECIMAL(12,2) DEFAULT 0,
    allowances DECIMAL(12,2) DEFAULT 0,
    deductions DECIMAL(12,2) DEFAULT 0,
    net_pay DECIMAL(12,2) DEFAULT 0,
    payment_date DATE DEFAULT NULL,
    status ENUM('Generated','Sent','Paid') DEFAULT 'Generated',
    generated_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_staff (staff_id),
    INDEX idx_period (month, year),
    INDEX idx_payroll (payroll_record_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
