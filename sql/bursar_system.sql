-- ============================================================
-- ISNM COMPLETE BURSAR FINANCIAL MANAGEMENT SYSTEM
-- Comprehensive SQL Schema with all required features
-- Database: igangaschoolofl_students_db
-- ============================================================

USE igangaschoolofl_students_db;

-- ============================================================
-- 1. BURSAR USER ACCOUNTS & AUTHENTICATION
-- ============================================================

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

-- ============================================================
-- 2. FEE STRUCTURES & PROGRAMS
-- ============================================================

CREATE TABLE IF NOT EXISTS programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_code VARCHAR(20) UNIQUE NOT NULL,
    program_name VARCHAR(255) NOT NULL,
    program_level ENUM('certificate', 'diploma', 'degree') NOT NULL,
    department VARCHAR(100),
    duration_years INT,
    description TEXT,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_program_code (program_code),
    INDEX idx_status (status)
);

CREATE TABLE IF NOT EXISTS fee_structures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    year_of_study INT NOT NULL,
    semester INT NOT NULL,
    tuition_fee DECIMAL(15,2) NOT NULL DEFAULT 0,
    accommodation_fee DECIMAL(15,2) DEFAULT 0,
    clinical_fee DECIMAL(15,2) DEFAULT 0,
    library_fee DECIMAL(15,2) DEFAULT 0,
    examination_fee DECIMAL(15,2) DEFAULT 0,
    registration_fee DECIMAL(15,2) DEFAULT 0,
    technology_fee DECIMAL(15,2) DEFAULT 0,
    other_fees DECIMAL(15,2) DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL,
    effective_date DATE NOT NULL,
    currency VARCHAR(3) DEFAULT 'UGX',
    status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_program (program_id),
    INDEX idx_year (academic_year),
    INDEX idx_status (status),
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE
);

-- ============================================================
-- 3. STUDENT FEES & INVOICING
-- ============================================================

CREATE TABLE IF NOT EXISTS student_fee_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    student_index_number VARCHAR(20) NOT NULL,
    program_id INT NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    year_of_study INT NOT NULL,
    fee_structure_id INT NOT NULL,
    assigned_date DATE NOT NULL,
    status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student (student_id),
    INDEX idx_index_number (student_index_number),
    INDEX idx_program (program_id),
    INDEX idx_academic_year (academic_year),
    FOREIGN KEY (program_id) REFERENCES programs(id),
    FOREIGN KEY (fee_structure_id) REFERENCES fee_structures(id)
);

CREATE TABLE IF NOT EXISTS student_invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    student_index_number VARCHAR(20) NOT NULL,
    student_name VARCHAR(255) NOT NULL,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    fee_structure_id INT NOT NULL,
    fee_assignment_id INT,
    academic_year VARCHAR(20) NOT NULL,
    semester INT NOT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    tuition_amount DECIMAL(15,2) DEFAULT 0,
    accommodation_amount DECIMAL(15,2) DEFAULT 0,
    clinical_amount DECIMAL(15,2) DEFAULT 0,
    library_amount DECIMAL(15,2) DEFAULT 0,
    examination_amount DECIMAL(15,2) DEFAULT 0,
    registration_amount DECIMAL(15,2) DEFAULT 0,
    technology_amount DECIMAL(15,2) DEFAULT 0,
    other_amount DECIMAL(15,2) DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL,
    amount_paid DECIMAL(15,2) DEFAULT 0,
    penalties_amount DECIMAL(15,2) DEFAULT 0,
    balance DECIMAL(15,2) NOT NULL,
    status ENUM('pending', 'partial', 'paid', 'overdue', 'cancelled', 'written_off') DEFAULT 'pending',
    payment_status ENUM('not_started', 'in_progress', 'completed', 'overdue') DEFAULT 'not_started',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student (student_id),
    INDEX idx_index_number (student_index_number),
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_status (status),
    INDEX idx_due_date (due_date),
    INDEX idx_academic_year (academic_year),
    FOREIGN KEY (fee_structure_id) REFERENCES fee_structures(id),
    FOREIGN KEY (fee_assignment_id) REFERENCES student_fee_assignments(id)
);

-- ============================================================
-- 4. PAYMENT PROCESSING
-- ============================================================

CREATE TABLE IF NOT EXISTS payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    method_name VARCHAR(50) UNIQUE NOT NULL,
    description VARCHAR(255),
    requires_verification BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_reference VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    student_index_number VARCHAR(20) NOT NULL,
    invoice_id INT,
    amount_received DECIMAL(15,2) NOT NULL,
    payment_method_id INT NOT NULL,
    payment_method ENUM('cash', 'bank_deposit', 'mobile_money', 'cheque', 'card', 'online') NOT NULL,
    payment_provider ENUM('mtn_momo', 'airtel_money', 'stanbic_bank', 'equity_bank', 'centenary_bank', 'dfcu_bank', 'standard_chartered', 'other') DEFAULT NULL,
    bank_name VARCHAR(100),
    account_number VARCHAR(50),
    account_holder_name VARCHAR(255),
    cheque_number VARCHAR(50),
    cheque_date DATE,
    transaction_reference VARCHAR(100),
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_date DATE NOT NULL,
    processed_by INT,
    verified_by INT,
    approved_by INT,
    status ENUM('pending', 'verified', 'approved', 'rejected', 'bounced') DEFAULT 'pending',
    verification_notes TEXT,
    receipt_generated BOOLEAN DEFAULT FALSE,
    receipt_number VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student (student_id),
    INDEX idx_reference (payment_reference),
    INDEX idx_status (status),
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_invoice (invoice_id),
    FOREIGN KEY (invoice_id) REFERENCES student_invoices(id),
    FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id)
);

CREATE TABLE IF NOT EXISTS payment_receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_number VARCHAR(50) UNIQUE NOT NULL,
    receipt_code VARCHAR(50),
    payment_id INT NOT NULL,
    student_id INT NOT NULL,
    student_index_number VARCHAR(20) NOT NULL,
    student_name VARCHAR(255) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    invoice_number VARCHAR(50),
    payment_method VARCHAR(50),
    receipt_date DATE NOT NULL,
    receipt_time TIME,
    printed_date TIMESTAMP NULL,
    printed_by INT,
    emailed_date TIMESTAMP NULL,
    generated_by INT,
    receipt_data LONGTEXT,
    pdf_path VARCHAR(255),
    status ENUM('generated', 'printed', 'emailed', 'viewed') DEFAULT 'generated',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_receipt_number (receipt_number),
    INDEX idx_payment (payment_id),
    INDEX idx_student (student_id),
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS proof_of_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50),
    original_name VARCHAR(255),
    file_size INT,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    verified_by INT,
    verified_date TIMESTAMP NULL,
    verification_notes TEXT,
    INDEX idx_payment (payment_id),
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE
);

-- ============================================================
-- 5. PENALTIES & LATE PAYMENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS penalty_configurations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    penalty_name VARCHAR(100) NOT NULL,
    penalty_type ENUM('late_payment', 'service_charge', 'processing_fee', 'other') NOT NULL,
    calculation_method ENUM('fixed_amount', 'percentage', 'daily_rate') NOT NULL,
    fixed_amount DECIMAL(15,2) DEFAULT 0,
    percentage_value DECIMAL(5,2) DEFAULT 0,
    daily_rate DECIMAL(15,2) DEFAULT 0,
    grace_days INT DEFAULT 0,
    max_penalty_amount DECIMAL(15,2) DEFAULT 0,
    applicable_to ENUM('all_fees', 'tuition_only', 'specific_fees') DEFAULT 'all_fees',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
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
    penalty_date DATE NOT NULL,
    status ENUM('pending', 'paid', 'waived', 'written_off') DEFAULT 'pending',
    waived_by INT,
    waived_reason TEXT,
    paid_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_student (student_id),
    INDEX idx_invoice (invoice_id),
    INDEX idx_status (status),
    FOREIGN KEY (invoice_id) REFERENCES student_invoices(id)
);

-- ============================================================
-- 6. SCHOLARSHIPS & SPONSORSHIPS
-- ============================================================

CREATE TABLE IF NOT EXISTS scholarships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    student_index_number VARCHAR(20) NOT NULL,
    scholarship_code VARCHAR(50) UNIQUE,
    sponsor_name VARCHAR(255) NOT NULL,
    sponsor_type ENUM('government', 'ngo', 'private_company', 'individual', 'religious', 'institutional', 'other') NOT NULL,
    sponsorship_type ENUM('full', 'partial', 'conditional') NOT NULL,
    coverage_percentage DECIMAL(5,2) DEFAULT 100,
    coverage_details TEXT,
    tuition_coverage BOOLEAN DEFAULT TRUE,
    accommodation_coverage BOOLEAN DEFAULT FALSE,
    clinical_coverage BOOLEAN DEFAULT FALSE,
    other_fee_coverage BOOLEAN DEFAULT FALSE,
    amount_per_semester DECIMAL(15,2),
    start_date DATE NOT NULL,
    end_date DATE,
    renewable BOOLEAN DEFAULT TRUE,
    conditions TEXT,
    contact_person VARCHAR(255),
    contact_email VARCHAR(255),
    contact_phone VARCHAR(20),
    status ENUM('active', 'expired', 'suspended', 'completed', 'terminated') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student (student_id),
    INDEX idx_index_number (student_index_number),
    INDEX idx_status (status),
    INDEX idx_sponsor_type (sponsor_type)
);

-- ============================================================
-- 7. FEE ADJUSTMENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS fee_adjustments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    invoice_id INT,
    adjustment_type ENUM('discount', 'waiver', 'refund', 'addition', 'correction') NOT NULL,
    reason TEXT NOT NULL,
    description VARCHAR(255),
    adjustment_amount DECIMAL(15,2) NOT NULL,
    affected_components TEXT,
    approved_by INT,
    approved_date TIMESTAMP NULL,
    approval_notes TEXT,
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student (student_id),
    INDEX idx_invoice (invoice_id),
    INDEX idx_status (status),
    FOREIGN KEY (invoice_id) REFERENCES student_invoices(id)
);

-- ============================================================
-- 8. BUDGET & EXPENDITURE MANAGEMENT
-- ============================================================

CREATE TABLE IF NOT EXISTS cost_centers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cost_center_code VARCHAR(20) UNIQUE NOT NULL,
    cost_center_name VARCHAR(255) NOT NULL,
    department VARCHAR(100),
    manager_name VARCHAR(255),
    budget_owner INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    budget_code VARCHAR(50) UNIQUE NOT NULL,
    cost_center_id INT NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    budget_period ENUM('annual', 'semester', 'quarterly', 'monthly') DEFAULT 'annual',
    budget_start_date DATE NOT NULL,
    budget_end_date DATE NOT NULL,
    total_budget DECIMAL(15,2) NOT NULL,
    approved_by INT,
    approved_date TIMESTAMP NULL,
    status ENUM('draft', 'submitted', 'approved', 'active', 'closed', 'archived') DEFAULT 'draft',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cost_center (cost_center_id),
    INDEX idx_year (academic_year),
    INDEX idx_status (status),
    FOREIGN KEY (cost_center_id) REFERENCES cost_centers(id)
);

CREATE TABLE IF NOT EXISTS budget_lines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    budget_id INT NOT NULL,
    line_number INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    account_code VARCHAR(50),
    budgeted_amount DECIMAL(15,2) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_budget (budget_id),
    FOREIGN KEY (budget_id) REFERENCES budgets(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS expenditures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expenditure_code VARCHAR(50) UNIQUE NOT NULL,
    cost_center_id INT NOT NULL,
    budget_id INT,
    budget_line_id INT,
    expense_date DATE NOT NULL,
    expense_category VARCHAR(100) NOT NULL,
    description VARCHAR(255) NOT NULL,
    supplier_name VARCHAR(255),
    amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'UGX',
    receipt_reference VARCHAR(100),
    invoice_number VARCHAR(50),
    payment_method ENUM('cash', 'cheque', 'bank_transfer', 'mobile_money', 'card') NOT NULL,
    requested_by INT,
    approved_by INT,
    approved_date TIMESTAMP NULL,
    paid_by INT,
    paid_date TIMESTAMP NULL,
    status ENUM('pending', 'approved', 'paid', 'rejected', 'cancelled') DEFAULT 'pending',
    supporting_documents TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cost_center (cost_center_id),
    INDEX idx_budget (budget_id),
    INDEX idx_status (status),
    INDEX idx_expense_date (expense_date),
    FOREIGN KEY (cost_center_id) REFERENCES cost_centers(id),
    FOREIGN KEY (budget_id) REFERENCES budgets(id)
);

-- ============================================================
-- 9. GENERAL LEDGER & ACCOUNTS MANAGEMENT
-- ============================================================

CREATE TABLE IF NOT EXISTS chart_of_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_code VARCHAR(20) UNIQUE NOT NULL,
    account_name VARCHAR(255) NOT NULL,
    account_type ENUM('asset', 'liability', 'equity', 'revenue', 'expense') NOT NULL,
    sub_type VARCHAR(100),
    description TEXT,
    normal_balance ENUM('debit', 'credit') NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS general_ledger (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gl_entry_number VARCHAR(50) UNIQUE NOT NULL,
    account_code VARCHAR(20) NOT NULL,
    account_name VARCHAR(255) NOT NULL,
    reference_type VARCHAR(50),
    reference_id INT,
    transaction_date DATE NOT NULL,
    debit_amount DECIMAL(15,2) DEFAULT 0,
    credit_amount DECIMAL(15,2) DEFAULT 0,
    narration TEXT,
    posted_by INT,
    posted_date TIMESTAMP,
    status ENUM('draft', 'posted', 'reversed') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_account_code (account_code),
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_status (status)
);

CREATE TABLE IF NOT EXISTS cash_book (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_date DATE NOT NULL,
    transaction_number VARCHAR(50) UNIQUE NOT NULL,
    reference_type VARCHAR(50),
    reference_id INT,
    description VARCHAR(255),
    receipt_amount DECIMAL(15,2) DEFAULT 0,
    payment_amount DECIMAL(15,2) DEFAULT 0,
    balance DECIMAL(15,2),
    payment_method VARCHAR(50),
    received_from_or_paid_to VARCHAR(255),
    authorized_by INT,
    recorded_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_reference (reference_id)
);

-- ============================================================
-- 10. INVENTORY & ASSET TRACKING (Optional but included)
-- ============================================================

CREATE TABLE IF NOT EXISTS asset_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) UNIQUE NOT NULL,
    category_code VARCHAR(20),
    description TEXT,
    depreciation_rate DECIMAL(5,2),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_code VARCHAR(50) UNIQUE NOT NULL,
    asset_category_id INT NOT NULL,
    asset_name VARCHAR(255) NOT NULL,
    description TEXT,
    purchase_date DATE NOT NULL,
    purchase_cost DECIMAL(15,2) NOT NULL,
    supplier_name VARCHAR(255),
    location VARCHAR(255),
    assigned_to INT,
    warranty_expiry DATE,
    depreciation_start_date DATE,
    accumulated_depreciation DECIMAL(15,2) DEFAULT 0,
    book_value DECIMAL(15,2),
    status ENUM('new', 'in_use', 'under_maintenance', 'deprecated', 'disposed') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (asset_category_id),
    INDEX idx_status (status),
    FOREIGN KEY (asset_category_id) REFERENCES asset_categories(id)
);

-- ============================================================
-- 11. PAYROLL INTEGRATION (BURSAR SIDE)
-- ============================================================

CREATE TABLE IF NOT EXISTS staff_salaries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    salary_month VARCHAR(20) NOT NULL,
    basic_salary DECIMAL(15,2) NOT NULL,
    allowances DECIMAL(15,2) DEFAULT 0,
    deductions DECIMAL(15,2) DEFAULT 0,
    gross_salary DECIMAL(15,2) NOT NULL,
    net_salary DECIMAL(15,2) NOT NULL,
    payment_method ENUM('bank_transfer', 'cash', 'cheque', 'mobile_money') DEFAULT 'bank_transfer',
    bank_account VARCHAR(50),
    payment_date DATE,
    payment_status ENUM('pending', 'processed', 'paid', 'cancelled') DEFAULT 'pending',
    approved_by INT,
    paid_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_salary_month (salary_month),
    INDEX idx_status (payment_status)
);

CREATE TABLE IF NOT EXISTS salary_components (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_name VARCHAR(100) NOT NULL,
    component_type ENUM('allowance', 'deduction') NOT NULL,
    amount DECIMAL(15,2),
    is_percentage BOOLEAN DEFAULT FALSE,
    percentage_of VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- 12. FINANCIAL REPORTS & ANALYTICS
-- ============================================================

CREATE TABLE IF NOT EXISTS financial_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_code VARCHAR(50) UNIQUE NOT NULL,
    report_name VARCHAR(255) NOT NULL,
    report_type ENUM('daily_collection', 'weekly_collection', 'monthly_collection', 'debtors_list', 'revenue_summary', 'student_statement', 'budget_vs_actual', 'trial_balance', 'income_statement', 'general_report') NOT NULL,
    report_period_start DATE NOT NULL,
    report_period_end DATE NOT NULL,
    generated_by INT,
    generated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    report_data LONGTEXT,
    pdf_path VARCHAR(255),
    excel_path VARCHAR(255),
    status ENUM('draft', 'finalized', 'archived') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_report_type (report_type),
    INDEX idx_generated_date (generated_date)
);

-- ============================================================
-- 13. COMMUNICATION & NOTIFICATIONS
-- ============================================================

CREATE TABLE IF NOT EXISTS fee_reminders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    invoice_id INT,
    reminder_type ENUM('due_reminder', 'overdue_reminder', 'final_notice') NOT NULL,
    reminder_date DATE NOT NULL,
    reminder_message TEXT,
    delivery_method ENUM('sms', 'email', 'both') DEFAULT 'sms',
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    sent_date TIMESTAMP NULL,
    response_received BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_student (student_id),
    INDEX idx_status (status)
);

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_user_id INT NOT NULL,
    notification_type VARCHAR(50),
    title VARCHAR(255),
    message TEXT,
    related_id INT,
    related_type VARCHAR(50),
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    action_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_recipient (recipient_user_id),
    INDEX idx_is_read (is_read)
);

-- ============================================================
-- 14. AUDIT TRAIL & ACTIVITY LOGS
-- ============================================================

CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    user_name VARCHAR(255),
    user_role VARCHAR(50),
    action_type VARCHAR(100),
    entity_type VARCHAR(50),
    entity_id INT,
    entity_name VARCHAR(255),
    old_values LONGTEXT,
    new_values LONGTEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    status ENUM('success', 'failure') DEFAULT 'success',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_action_type (action_type),
    INDEX idx_created_at (created_at)
);

-- ============================================================
-- 15. SYSTEM SETTINGS & CONFIGURATIONS
-- ============================================================

CREATE TABLE IF NOT EXISTS bursar_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value LONGTEXT,
    setting_type VARCHAR(50),
    description VARCHAR(255),
    is_editable BOOLEAN DEFAULT TRUE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- INSERT DEFAULT DATA
-- ============================================================

-- Insert payment methods
INSERT IGNORE INTO payment_methods (method_name, description, requires_verification) VALUES
('Cash', 'Direct cash payment', FALSE),
('Bank Deposit', 'Bank deposit payment', TRUE),
('Mobile Money (MTN)', 'MTN Mobile Money (MTN MoMo)', TRUE),
('Mobile Money (Airtel)', 'Airtel Money payment', TRUE),
('Cheque', 'Cheque payment', TRUE),
('Card', 'Credit/Debit card payment', FALSE),
('Online', 'Online bank transfer', TRUE);

-- Insert penalty configurations
INSERT IGNORE INTO penalty_configurations (penalty_name, penalty_type, calculation_method, grace_days, percentage_value, max_penalty_amount) VALUES
('Late Payment Penalty', 'late_payment', 'percentage', 7, 5, 500000),
('Service Charge', 'service_charge', 'fixed_amount', 0, 0, 0),
('Processing Fee', 'processing_fee', 'fixed_amount', 0, 0, 50000);

-- Insert cost centers
INSERT IGNORE INTO cost_centers (cost_center_code, cost_center_name, department) VALUES
('CC001', 'Administration', 'General'),
('CC002', 'Nursing Department', 'Academic'),
('CC003', 'Midwifery Department', 'Academic'),
('CC004', 'Facilities', 'Support'),
('CC005', 'Library', 'Support'),
('CC006', 'ICT', 'Support');

-- Insert chart of accounts
INSERT IGNORE INTO chart_of_accounts (account_code, account_name, account_type, normal_balance) VALUES
('1000', 'Bank Account', 'asset', 'debit'),
('1100', 'Cash', 'asset', 'debit'),
('1200', 'Accounts Receivable', 'asset', 'debit'),
('1500', 'Fixed Assets', 'asset', 'debit'),
('2000', 'Accounts Payable', 'liability', 'credit'),
('2100', 'Staff Salaries Payable', 'liability', 'credit'),
('3000', 'Retained Earnings', 'equity', 'credit'),
('4000', 'Tuition Fee Revenue', 'revenue', 'credit'),
('4100', 'Accommodation Revenue', 'revenue', 'credit'),
('4200', 'Other Fee Revenue', 'revenue', 'credit'),
('5000', 'Staff Salaries', 'expense', 'debit'),
('5100', 'Utilities', 'expense', 'debit'),
('5200', 'Supplies', 'expense', 'debit'),
('5300', 'Maintenance', 'expense', 'debit'),
('5400', 'Miscellaneous', 'expense', 'debit');

-- Insert Bursar user with proper credentials
-- Email: bursar@igangaschoolofnursingandmidwifery.ac.ug
-- Password: bursar@isnm
INSERT IGNORE INTO bursar_users (email, password_hash, full_name, phone, role, status) 
VALUES ('bursar@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$bursar@isnmHashedPasswordValue', 'Bursar', '+256701000000', 'bursar', 'active');

-- ============================================================
-- STUDENT DATA INTEGRATION FOR BURSAR
-- View to link student data for fee management
-- ============================================================
CREATE OR REPLACE VIEW bursar_student_view AS
SELECT 
    sp.id as student_id,
    sp.student_number,
    sp.full_name,
    sp.program,
    sp.year_of_study,
    sp.email,
    sp.phone,
    COALESCE(SUM(fr.total_amount), 0) as total_fees_assessed,
    COALESCE(SUM(CASE WHEN fr.status = 'Paid' THEN fr.total_amount ELSE 0 END), 0) as total_fees_paid,
    (COALESCE(SUM(fr.total_amount), 0) - COALESCE(SUM(CASE WHEN fr.status = 'Paid' THEN fr.total_amount ELSE 0 END), 0)) as balance_due
FROM universal_student_profiles sp
LEFT JOIN fee_accounts fr ON sp.id = fr.student_id
GROUP BY sp.id, sp.student_number, sp.full_name, sp.program, sp.year_of_study, sp.email, sp.phone;

-- Insert default system settings
INSERT IGNORE INTO bursar_settings (setting_key, setting_value, setting_type, description) VALUES
('institution_name', 'Iganga School of Nursing and Midwifery', 'text', 'Institution full name'),
('institution_short_code', 'ISNM', 'text', 'Institution short code'),
('institution_address', 'Iganga, Uganda', 'text', 'Institution address'),
('institution_phone', '+256-701-000-000', 'text', 'Institution contact phone'),
('institution_email', 'info@igangaschoolofnursingandmidwifery.ac.ug', 'text', 'Institution email'),
('institution_website', 'www.igangaschoolofnursingandmidwifery.ac.ug', 'text', 'Institution website'),
('currency_symbol', 'UGX', 'text', 'Currency symbol'),
('currency_code', 'UGX', 'text', 'Currency code'),
('decimal_places', '2', 'number', 'Decimal places for currency'),
('current_academic_year', '2025/2026', 'text', 'Current academic year'),
('enable_mobile_money_integration', 'true', 'boolean', 'Enable mobile money integration'),
('enable_bank_integration', 'false', 'boolean', 'Enable bank integration'),
('receipt_prefix', 'REC', 'text', 'Receipt number prefix'),
('invoice_prefix', 'INV', 'text', 'Invoice number prefix'),
('grace_period_days', '7', 'number', 'Grace period before penalty in days'),
('require_payment_verification', 'true', 'boolean', 'Require verification for bank deposits');

COMMIT;
