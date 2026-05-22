-- Financial Management System Tables for ISNM
-- Run this script in your database

USE igangaschoolofl_students_db;

-- =============================================
-- 1. FEE STRUCTURE MANAGEMENT
-- =============================================

CREATE TABLE IF NOT EXISTS fee_structures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_name VARCHAR(100) NOT NULL,
    program_level ENUM('certificate', 'diploma', 'degree') NOT NULL,
    year INT NOT NULL,
    semester INT NOT NULL,
    tuition_fee DECIMAL(15,2) NOT NULL DEFAULT 0,
    accommodation_fee DECIMAL(15,2) DEFAULT 0,
    clinical_fee DECIMAL(15,2) DEFAULT 0,
    library_fee DECIMAL(15,2) DEFAULT 0,
    examination_fee DECIMAL(15,2) DEFAULT 0,
    registration_fee DECIMAL(15,2) DEFAULT 0,
    other_fees DECIMAL(15,2) DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL,
    effective_date DATE NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_program (program_name, program_level),
    INDEX idx_status (status)
);

-- =============================================
-- 2. STUDENT FEES & INVOICES
-- =============================================

CREATE TABLE IF NOT EXISTS student_invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    student_index_number VARCHAR(20) NOT NULL,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    fee_structure_id INT NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    semester INT NOT NULL,
    due_date DATE NOT NULL,
    total_amount DECIMAL(15,2) NOT NULL,
    amount_paid DECIMAL(15,2) DEFAULT 0,
    balance DECIMAL(15,2) NOT NULL,
    status ENUM('pending', 'partial', 'paid', 'overdue', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student (student_id),
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_status (status)
);

CREATE TABLE IF NOT EXISTS student_fee_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    fee_structure_id INT NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    assigned_date DATE NOT NULL,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_student (student_id),
    INDEX idx_fee_structure (fee_structure_id)
);

-- =============================================
-- 3. PAYMENT PROCESSING
-- =============================================

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_reference VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    student_index_number VARCHAR(20) NOT NULL,
    invoice_id INT,
    amount DECIMAL(15,2) NOT NULL,
    payment_method ENUM('cash', 'bank_deposit', 'mobile_money', 'cheque', 'card') NOT NULL,
    payment_provider ENUM('mtn_momo', 'airtel_money', 'stanbic_bank', 'equity_bank', 'centenary_bank', 'other') DEFAULT NULL,
    reference_number VARCHAR(100),
    bank_name VARCHAR(100),
    account_number VARCHAR(50),
    cheque_number VARCHAR(50),
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_date DATE NOT NULL,
    processed_by INT,
    status ENUM('pending', 'verified', 'approved', 'rejected') DEFAULT 'pending',
    receipt_generated BOOLEAN DEFAULT FALSE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student (student_id),
    INDEX idx_payment_ref (payment_reference),
    INDEX idx_status (status),
    INDEX idx_transaction_date (transaction_date)
);

CREATE TABLE IF NOT EXISTS payment_receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_number VARCHAR(50) UNIQUE NOT NULL,
    payment_id INT NOT NULL,
    student_id INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    receipt_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    generated_by INT,
    receipt_data LONGTEXT,
    pdf_path VARCHAR(255),
    status ENUM('generated', 'printed', 'emailed') DEFAULT 'generated',
    INDEX idx_receipt_number (receipt_number),
    INDEX idx_payment (payment_id)
);

CREATE TABLE IF NOT EXISTS proof_of_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50),
    original_name VARCHAR(255),
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    INDEX idx_payment (payment_id)
);

-- =============================================
-- 4. PENALTIES & LATE PAYMENTS
-- =============================================

CREATE TABLE IF NOT EXISTS penalty_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    penalty_name VARCHAR(100) NOT NULL,
    penalty_type ENUM('late_payment', 'service_charge', 'other') NOT NULL,
    calculation_method ENUM('fixed_amount', 'percentage', 'daily') NOT NULL,
    fixed_amount DECIMAL(15,2) DEFAULT 0,
    percentage_value DECIMAL(5,2) DEFAULT 0,
    daily_rate DECIMAL(15,2) DEFAULT 0,
    grace_days INT DEFAULT 0,
    max_penalty_amount DECIMAL(15,2) DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS student_penalties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    invoice_id INT,
    penalty_config_id INT NOT NULL,
    penalty_name VARCHAR(100) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    calculated_on DECIMAL(15,2),
    days_late INT,
    status ENUM('pending', 'paid', 'waived') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    paid_date TIMESTAMP NULL,
    INDEX idx_student (student_id),
    INDEX idx_status (status)
);

-- =============================================
-- 5. SCHOLARSHIPS & SPONSORSHIPS
-- =============================================

CREATE TABLE IF NOT EXISTS scholarships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    sponsor_name VARCHAR(255) NOT NULL,
    sponsor_type ENUM('government', 'ngo', 'private', 'religious', 'other') NOT NULL,
    sponsorship_type ENUM('full', 'partial', ' conditional') NOT NULL,
    coverage_percentage DECIMAL(5,2) DEFAULT 100,
    coverage_details TEXT,
    tuition_coverage BOOLEAN DEFAULT TRUE,
    accommodation_coverage BOOLEAN DEFAULT FALSE,
    other_fee_coverage BOOLEAN DEFAULT FALSE,
    start_date DATE NOT NULL,
    end_date DATE,
    status ENUM('active', 'expired', 'suspended', 'completed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student (student_id),
    INDEX idx_status (status)
);

CREATE TABLE IF NOT EXISTS fee_adjustments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    invoice_id INT,
    adjustment_type ENUM('discount', 'waiver', 'refund', 'addition') NOT NULL,
    reason TEXT,
    amount DECIMAL(15,2) NOT NULL,
    approved_by INT,
    approved_date TIMESTAMP NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_student (student_id),
    INDEX idx_status (status)
);

-- =============================================
-- 6. BUDGET & EXPENDITURE MANAGEMENT
-- =============================================

CREATE TABLE IF NOT EXISTS budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    budget_name VARCHAR(255) NOT NULL,
    budget_category VARCHAR(100) NOT NULL,
    budget_type ENUM('annual', 'termly', 'monthly') NOT NULL,
    fiscal_year VARCHAR(20) NOT NULL,
    total_budget_amount DECIMAL(15,2) NOT NULL,
    allocated_amount DECIMAL(15,2) DEFAULT 0,
    spent_amount DECIMAL(15,2) DEFAULT 0,
    remaining_amount DECIMAL(15,2) DEFAULT 0,
    status ENUM('draft', 'active', 'closed', 'archived') DEFAULT 'draft',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (budget_category),
    INDEX idx_status (status)
);

CREATE TABLE IF NOT EXISTS departmental_budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    budget_id INT NOT NULL,
    department VARCHAR(100) NOT NULL,
    allocated_amount DECIMAL(15,2) NOT NULL,
    spent_amount DECIMAL(15,2) DEFAULT 0,
    remaining_amount DECIMAL(15,2) DEFAULT 0,
    status ENUM('active', 'exhausted', 'closed') DEFAULT 'active',
    FOREIGN KEY (budget_id) REFERENCES budgets(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expense_id VARCHAR(50) UNIQUE NOT NULL,
    description VARCHAR(255) NOT NULL,
    expense_category VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    amount DECIMAL(15,2) NOT NULL,
    expense_date DATE NOT NULL,
    payment_method ENUM('cash', 'bank_transfer', 'cheque', 'mobile_money') NOT NULL,
    budget_id INT,
    requested_by INT,
    approved_by INT,
    approval_date TIMESTAMP NULL,
    status ENUM('pending', 'approved', 'rejected', 'paid') DEFAULT 'pending',
    receipt_path VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_expense_id (expense_id),
    INDEX idx_status (status),
    INDEX idx_category (expense_category)
);

CREATE TABLE IF NOT EXISTS expense_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expense_id INT NOT NULL,
    approver_id INT NOT NULL,
    approval_level INT NOT NULL,
    status ENUM('approved', 'rejected') NOT NULL,
    comments TEXT,
    approval_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (expense_id) REFERENCES expenses(id) ON DELETE CASCADE
);

-- =============================================
-- 7. ACCOUNTS & LEDGER MANAGEMENT
-- =============================================

CREATE TABLE IF NOT EXISTS chart_of_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_code VARCHAR(20) UNIQUE NOT NULL,
    account_name VARCHAR(255) NOT NULL,
    account_type ENUM('asset', 'liability', 'equity', 'income', 'expense') NOT NULL,
    parent_account_id INT DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_account_code (account_code),
    INDEX idx_account_type (account_type)
);

CREATE TABLE IF NOT EXISTS general_ledger (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(50) NOT NULL,
    account_id INT NOT NULL,
    transaction_date DATE NOT NULL,
    description TEXT,
    debit_amount DECIMAL(15,2) DEFAULT 0,
    credit_amount DECIMAL(15,2) DEFAULT 0,
    running_balance DECIMAL(15,2) NOT NULL,
    reference_type ENUM('payment', 'expense', 'adjustment', 'journal') NOT NULL,
    reference_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_transaction (transaction_id),
    INDEX idx_account (account_id),
    INDEX idx_date (transaction_date)
);

CREATE TABLE IF NOT EXISTS cashbook (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_date DATE NOT NULL,
    description TEXT,
    reference_number VARCHAR(100),
    debit_amount DECIMAL(15,2) DEFAULT 0,
    credit_amount DECIMAL(15,2) DEFAULT 0,
    balance DECIMAL(15,2) NOT NULL,
    transaction_type ENUM('cash_in', 'cash_out') NOT NULL,
    payment_method VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date (transaction_date)
);

CREATE TABLE IF NOT EXISTS bank_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bank_name VARCHAR(100) NOT NULL,
    account_name VARCHAR(255) NOT NULL,
    account_number VARCHAR(50) NOT NULL,
    account_type VARCHAR(50),
    current_balance DECIMAL(15,2) DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS bank_reconciliations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bank_account_id INT NOT NULL,
    statement_date DATE NOT NULL,
    statement_balance DECIMAL(15,2) NOT NULL,
    book_balance DECIMAL(15,2) NOT NULL,
    difference DECIMAL(15,2) NOT NULL,
    status ENUM('draft', 'completed', 'adjusted') DEFAULT 'draft',
    reconciled_by INT,
    reconciliation_date TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id)
);

-- =============================================
-- 8. INVENTORY & ASSET TRACKING
-- =============================================

CREATE TABLE IF NOT EXISTS assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_tag VARCHAR(50) UNIQUE NOT NULL,
    asset_name VARCHAR(255) NOT NULL,
    asset_category VARCHAR(100) NOT NULL,
    description TEXT,
    purchase_date DATE,
    purchase_value DECIMAL(15,2) NOT NULL,
    supplier VARCHAR(255),
    depreciation_method ENUM('straight_line', 'reducing_balance', 'none') DEFAULT 'straight_line',
    useful_life_years INT DEFAULT 5,
    salvage_value DECIMAL(15,2) DEFAULT 0,
    accumulated_depreciation DECIMAL(15,2) DEFAULT 0,
    net_book_value DECIMAL(15,2) NOT NULL,
    location VARCHAR(255),
    custodian VARCHAR(255),
    status ENUM('active', 'disposed', 'written_off') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_asset_tag (asset_tag),
    INDEX idx_category (asset_category)
);

CREATE TABLE IF NOT EXISTS asset_depreciation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    depreciation_date DATE NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    depreciation_method ENUM('straight_line', 'reducing_balance') NOT NULL,
    period_start DATE,
    period_end DATE,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS inventory_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_code VARCHAR(50) UNIQUE NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    item_category VARCHAR(100),
    quantity INT NOT NULL,
    unit_cost DECIMAL(15,2) NOT NULL,
    total_value DECIMAL(15,2) NOT NULL,
    supplier VARCHAR(255),
    purchase_date DATE,
    location VARCHAR(255),
    status ENUM('available', 'issued', 'damaged', 'disposed') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =============================================
-- 9. PAYROLL MANAGEMENT
-- =============================================

CREATE TABLE IF NOT EXISTS staff_salaries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    base_salary DECIMAL(15,2) NOT NULL,
    allowances JSON,
    deductions JSON,
    net_salary DECIMAL(15,2) NOT NULL,
    payment_month DATE NOT NULL,
    payment_date DATE,
    status ENUM('pending', 'processed', 'paid') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_staff (staff_id),
    INDEX idx_payment_month (payment_month)
);

CREATE TABLE IF NOT EXISTS payslips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payslip_number VARCHAR(50) UNIQUE NOT NULL,
    staff_id INT NOT NULL,
    payment_month DATE NOT NULL,
    base_salary DECIMAL(15,2) NOT NULL,
    allowances JSON,
    deductions JSON,
    gross_pay DECIMAL(15,2) NOT NULL,
    total_deductions DECIMAL(15,2) NOT NULL,
    net_pay DECIMAL(15,2) NOT NULL,
    generated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    generated_by INT,
    pdf_path VARCHAR(255),
    INDEX idx_payslip_number (payslip_number),
    INDEX idx_staff (staff_id)
);

-- =============================================
-- 10. COMMUNICATION & NOTIFICATIONS
-- =============================================

CREATE TABLE IF NOT EXISTS communication_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('sms', 'email', 'overdue_notice', 'payment_confirmation', 'announcement') NOT NULL,
    recipient_type ENUM('student', 'staff', 'group') NOT NULL,
    recipient_id INT,
    recipient_contact VARCHAR(255),
    subject VARCHAR(255),
    message TEXT,
    status ENUM('pending', 'sent', 'failed', 'delivered') DEFAULT 'pending',
    sent_date TIMESTAMP NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_sent_date (sent_date)
);

CREATE TABLE IF NOT EXISTS fee_reminders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    invoice_id INT,
    reminder_type ENUM('gentle', 'firm', 'final', 'overdue') NOT NULL,
    reminder_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    method ENUM('sms', 'email', 'both') NOT NULL,
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    reminder_number INT DEFAULT 1,
    INDEX idx_student (student_id),
    INDEX idx_invoice (invoice_id)
);

-- =============================================
-- 11. ACTIVITY LOGS & AUDIT TRAIL
-- =============================================

CREATE TABLE IF NOT EXISTS financial_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action_type VARCHAR(50) NOT NULL,
    table_name VARCHAR(100) NOT NULL,
    record_id INT,
    old_values JSON,
    new_values JSON,
    user_id INT,
    user_role VARCHAR(50),
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action_type (action_type),
    INDEX idx_user (user_id),
    INDEX idx_created_at (created_at)
);

-- =============================================
-- 12. URA REPORTING
-- =============================================

CREATE TABLE IF NOT EXISTS ura_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_type VARCHAR(50) NOT NULL,
    report_period VARCHAR(20) NOT NULL,
    report_data JSON,
    generated_by INT,
    generated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('draft', 'submitted', 'approved') DEFAULT 'draft',
    file_path VARCHAR(255),
    INDEX idx_report_type (report_type),
    INDEX idx_status (status)
);