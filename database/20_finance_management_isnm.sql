-- =====================================================
-- ISNM SCHOOL MANAGEMENT SYSTEM - FINANCE MANAGEMENT
-- Database: isnm_db
-- Supports all financial operations: fees, payments, budget, expenses, etc.
-- =====================================================

USE isnm_db;

-- Drop existing tables if they exist to ensure clean setup
DROP TABLE IF EXISTS fee_categories;
DROP TABLE IF EXISTS fee_structure;
DROP TABLE IF EXISTS student_fee_accounts;
DROP TABLE IF EXISTS payment_transactions;
DROP TABLE IF EXISTS payment_methods;
DROP TABLE IF EXISTS payment_gateways;
DROP TABLE IF EXISTS budget_allocations;
DROP TABLE IF EXISTS expense_categories;
DROP TABLE IF EXISTS expense_records;
DROP TABLE IF EXISTS financial_reports;
DROP TABLE IF EXISTS fee waivers;
DROP TABLE IF EXISTS fee_discounts;
DROP TABLE IF EXISTS fee_refunds;
DROP TABLE IF EXISTS invoice_records;
DROP TABLE IF EXISTS receipt_records;
DROP TABLE IF EXISTS financial_audit_trail;
DROP TABLE IF EXISTS bank_accounts;
DROP TABLE IF EXISTS salary_payments;
DROP TABLE IF EXISTS vendor_payments;
DROP TABLE IF EXISTS revenue_streams;

-- =====================================================
-- 1. FEE CATEGORIES
-- =====================================================
CREATE TABLE fee_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_code VARCHAR(20) NOT NULL UNIQUE,
    category_name VARCHAR(255) NOT NULL,
    category_description TEXT NULL,
    category_type ENUM('tuition', 'accommodation', 'library', 'laboratory', 'examination', 'registration', 'development', 'other') NOT NULL,
    is_mandatory BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (category_code),
    INDEX idx_type (category_type),
    INDEX idx_active (is_active)
);

-- =====================================================
-- 2. FEE STRUCTURE
-- =====================================================
CREATE TABLE fee_structure (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    level INT NOT NULL, -- Academic level (1, 2, 3, 4, 5)
    semester INT NOT NULL, -- Semester number (1, 2, 3)
    fee_category_id INT NOT NULL,
    fee_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'UGX',
    payment_deadline DATE NULL,
    late_fee_amount DECIMAL(10,2) DEFAULT 0.00,
    late_fee_applied_after DATE NULL,
    is_active BOOLEAN DEFAULT TRUE,
    effective_from DATE NOT NULL,
    effective_to DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_fee_structure (program_id, level, semester, fee_category_id, effective_from),
    INDEX idx_program (program_id),
    INDEX idx_level_semester (level, semester),
    INDEX idx_category (fee_category_id),
    INDEX idx_active (is_active),
    INDEX idx_effective_dates (effective_from, effective_to),
    FOREIGN KEY (program_id) REFERENCES academic_programs(id) ON DELETE CASCADE,
    FOREIGN KEY (fee_category_id) REFERENCES fee_categories(id) ON DELETE CASCADE
);

-- =====================================================
-- 3. STUDENT FEE ACCOUNTS
-- =====================================================
CREATE TABLE student_fee_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    program_id INT NOT NULL,
    session_id INT NOT NULL,
    semester_id INT NOT NULL,
    total_fees DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    balance_due DECIMAL(12,2) GENERATED ALWAYS AS (total_fees - amount_paid) STORED,
    last_payment_date DATE NULL,
    payment_status ENUM('unpaid', 'partial', 'paid', 'overpaid', 'waived') DEFAULT 'unpaid',
    due_date DATE NOT NULL,
    late_fee_applied BOOLEAN DEFAULT FALSE,
    late_fee_amount DECIMAL(10,2) DEFAULT 0.00,
    waiver_amount DECIMAL(10,2) DEFAULT 0.00,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    account_status ENUM('active', 'suspended', 'closed', 'transferred') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_student_session_semester (student_id, session_id, semester_id),
    INDEX idx_student (student_id),
    INDEX idx_program (program_id),
    INDEX idx_session (session_id),
    INDEX idx_semester (semester_id),
    INDEX idx_status (payment_status),
    INDEX idx_due_date (due_date),
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (program_id) REFERENCES academic_programs(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES academic_semesters(id) ON DELETE CASCADE
);

-- =====================================================
-- 4. PAYMENT METHODS
-- =====================================================
CREATE TABLE payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    method_code VARCHAR(20) NOT NULL UNIQUE,
    method_name VARCHAR(100) NOT NULL,
    method_description TEXT NULL,
    method_type ENUM('cash', 'bank_transfer', 'mobile_money', 'credit_card', 'debit_card', 'cheque', 'online', 'other') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    processing_fee_percentage DECIMAL(5,2) DEFAULT 0.00,
    minimum_amount DECIMAL(10,2) DEFAULT 0.00,
    maximum_amount DECIMAL(12,2) DEFAULT 999999999.99,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (method_code),
    INDEX idx_type (method_type),
    INDEX idx_active (is_active)
);

-- =====================================================
-- 5. PAYMENT GATEWAYS
-- =====================================================
CREATE TABLE payment_gateways (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gateway_name VARCHAR(100) NOT NULL UNIQUE,
    gateway_provider VARCHAR(100) NOT NULL,
    gateway_type ENUM('mobile_money', 'bank', 'card', 'cryptocurrency', 'other') NOT NULL,
    api_endpoint VARCHAR(500) NULL,
    api_key_encrypted TEXT NULL,
    api_secret_encrypted TEXT NULL,
    merchant_id VARCHAR(100) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    supports_refunds BOOLEAN DEFAULT FALSE,
    supports_recurring BOOLEAN DEFAULT FALSE,
    transaction_fee_percentage DECIMAL(5,2) DEFAULT 0.00,
    fixed_transaction_fee DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_name (gateway_name),
    INDEX idx_provider (gateway_provider),
    INDEX idx_type (gateway_type),
    INDEX idx_active (is_active)
);

-- =====================================================
-- 6. PAYMENT TRANSACTIONS
-- =====================================================
CREATE TABLE payment_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(100) NOT NULL UNIQUE,
    student_fee_account_id INT NOT NULL,
    student_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_method_id INT NOT NULL,
    payment_gateway_id INT NULL,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    transaction_status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded') DEFAULT 'pending',
    payment_reference VARCHAR(255) NULL, -- Bank reference, mobile money reference, etc.
    external_transaction_id VARCHAR(255) NULL, -- Gateway transaction ID
    receipt_number VARCHAR(100) NULL,
    invoice_number VARCHAR(100) NULL,
    description TEXT NULL,
    processed_by INT NULL, -- Staff who processed the payment
    processing_fee DECIMAL(10,2) DEFAULT 0.00,
    net_amount DECIMAL(12,2) GENERATED ALWAYS AS (amount - processing_fee) STORED,
    currency VARCHAR(3) DEFAULT 'UGX',
    payment_notes TEXT NULL,
    failure_reason TEXT NULL,
    refund_amount DECIMAL(12,2) DEFAULT 0.00,
    refund_date TIMESTAMP NULL,
    refund_reason TEXT NULL,
    verified_by INT NULL,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_fee_account (student_fee_account_id),
    INDEX idx_student (student_id),
    INDEX idx_method (payment_method_id),
    INDEX idx_gateway (payment_gateway_id),
    INDEX idx_status (transaction_status),
    INDEX idx_date (transaction_date),
    INDEX idx_receipt (receipt_number),
    INDEX idx_invoice (invoice_number),
    INDEX idx_processed_by (processed_by),
    FOREIGN KEY (student_fee_account_id) REFERENCES student_fee_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_gateway_id) REFERENCES payment_gateways(id) ON DELETE SET NULL,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- 7. BUDGET ALLOCATIONS
-- =====================================================
CREATE TABLE budget_allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    budget_code VARCHAR(50) NOT NULL UNIQUE,
    budget_name VARCHAR(255) NOT NULL,
    budget_description TEXT NULL,
    department VARCHAR(100) NOT NULL,
    budget_category ENUM('operational', 'development', 'capital', 'maintenance', 'salary', 'scholarship', 'research', 'other') NOT NULL,
    fiscal_year VARCHAR(10) NOT NULL, -- e.g., '2024/2025'
    allocated_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    spent_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    remaining_amount DECIMAL(15,2) GENERATED ALWAYS AS (allocated_amount - spent_amount) STORED,
    budget_status ENUM('draft', 'approved', 'active', 'suspended', 'completed', 'exhausted') DEFAULT 'draft',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (budget_code),
    INDEX idx_department (department),
    INDEX idx_category (budget_category),
    INDEX idx_fiscal_year (fiscal_year),
    INDEX idx_status (budget_status),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_approved_by (approved_by),
    INDEX idx_created_by (created_by),
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 8. EXPENSE CATEGORIES
-- =====================================================
CREATE TABLE expense_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_code VARCHAR(20) NOT NULL UNIQUE,
    category_name VARCHAR(255) NOT NULL,
    category_description TEXT NULL,
    parent_category_id INT NULL,
    category_type ENUM('operational', 'capital', 'maintenance', 'salary', 'utility', 'supply', 'service', 'other') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (category_code),
    INDEX idx_parent (parent_category_id),
    INDEX idx_type (category_type),
    INDEX idx_active (is_active),
    FOREIGN KEY (parent_category_id) REFERENCES expense_categories(id) ON DELETE SET NULL
);

-- =====================================================
-- 9. EXPENSE RECORDS
-- =====================================================
CREATE TABLE expense_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expense_code VARCHAR(50) NOT NULL UNIQUE,
    expense_title VARCHAR(255) NOT NULL,
    expense_description TEXT NULL,
    expense_category_id INT NOT NULL,
    budget_allocation_id INT NULL,
    department VARCHAR(100) NOT NULL,
    expense_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'UGX',
    expense_date DATE NOT NULL,
    payment_method_id INT NOT NULL,
    vendor_name VARCHAR(255) NULL,
    vendor_contact VARCHAR(255) NULL,
    invoice_number VARCHAR(100) NULL,
    receipt_number VARCHAR(100) NULL,
    expense_status ENUM('pending', 'approved', 'rejected', 'paid', 'cancelled') DEFAULT 'pending',
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,
    paid_by INT NULL,
    paid_at TIMESTAMP NULL,
    payment_reference VARCHAR(255) NULL,
    supporting_documents JSON NULL, -- Array of document references
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (expense_code),
    INDEX idx_category (expense_category_id),
    INDEX idx_budget (budget_allocation_id),
    INDEX idx_department (department),
    INDEX idx_status (expense_status),
    INDEX idx_date (expense_date),
    INDEX idx_approved_by (approved_by),
    INDEX idx_paid_by (paid_by),
    INDEX idx_created_by (created_by),
    FOREIGN KEY (expense_category_id) REFERENCES expense_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (budget_allocation_id) REFERENCES budget_allocations(id) ON DELETE SET NULL,
    FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (paid_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 10. FINANCIAL REPORTS
-- =====================================================
CREATE TABLE financial_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_name VARCHAR(255) NOT NULL,
    report_type ENUM('income_statement', 'balance_sheet', 'cash_flow', 'budget_variance', 'fee_collection', 'expense_summary', 'trial_balance', 'custom') NOT NULL,
    report_period_start DATE NOT NULL,
    report_period_end DATE NOT NULL,
    report_format ENUM('PDF', 'Excel', 'CSV', 'Word') DEFAULT 'PDF',
    report_data JSON NULL, -- Store report summary data
    file_path VARCHAR(500) NULL,
    file_size INT NULL,
    generation_status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    generated_by INT NOT NULL,
    generated_at TIMESTAMP NULL,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    is_public BOOLEAN DEFAULT FALSE,
    download_count INT DEFAULT 0,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_type (report_type),
    INDEX idx_period (report_period_start, report_period_end),
    INDEX idx_status (generation_status),
    INDEX idx_generated_by (generated_by),
    INDEX idx_approved_by (approved_by),
    INDEX idx_public (is_public),
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- 11. FEE WAIVERS
-- =====================================================
CREATE TABLE fee_waivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    waiver_code VARCHAR(50) NOT NULL UNIQUE,
    waiver_name VARCHAR(255) NOT NULL,
    waiver_description TEXT NULL,
    waiver_type ENUM('full', 'partial', 'percentage', 'fixed_amount') NOT NULL,
    waiver_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    applicable_programs JSON NULL, -- Array of program IDs
    applicable_categories JSON NULL, -- Array of fee category IDs
    eligibility_criteria JSON NULL, -- JSON object with eligibility rules
    max_applications INT DEFAULT 0, -- 0 = unlimited
    current_applications INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    effective_from DATE NOT NULL,
    effective_to DATE NULL,
    created_by INT NOT NULL,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (waiver_code),
    INDEX idx_type (waiver_type),
    INDEX idx_active (is_active),
    INDEX idx_effective_dates (effective_from, effective_to),
    INDEX idx_created_by (created_by),
    INDEX idx_approved_by (approved_by),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- 12. FEE DISCOUNTS
-- =====================================================
CREATE TABLE fee_discounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    discount_code VARCHAR(50) NOT NULL UNIQUE,
    discount_name VARCHAR(255) NOT NULL,
    discount_description TEXT NULL,
    discount_type ENUM('percentage', 'fixed_amount', 'early_bird', 'sibling', 'merit_based') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    applicable_programs JSON NULL, -- Array of program IDs
    applicable_categories JSON NULL, -- Array of fee category IDs
    discount_conditions JSON NULL, -- JSON object with discount conditions
    max_uses INT DEFAULT 0, -- 0 = unlimited
    current_uses INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    effective_from DATE NOT NULL,
    effective_to DATE NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (discount_code),
    INDEX idx_type (discount_type),
    INDEX idx_active (is_active),
    INDEX idx_effective_dates (effective_from, effective_to),
    INDEX idx_created_by (created_by),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 13. FEE REFUNDS
-- =====================================================
CREATE TABLE fee_refunds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    refund_number VARCHAR(100) NOT NULL UNIQUE,
    student_fee_account_id INT NOT NULL,
    student_id INT NOT NULL,
    original_payment_id INT NULL, -- Reference to original payment transaction
    refund_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    refund_reason TEXT NOT NULL,
    refund_type ENUM('full', 'partial', 'overpayment', 'withdrawal', 'course_drop', 'other') NOT NULL,
    refund_status ENUM('pending', 'approved', 'rejected', 'processed', 'cancelled') DEFAULT 'pending',
    refund_method_id INT NOT NULL,
    bank_account_details JSON NULL, -- Bank account for refund
    refund_date DATE NULL,
    processed_by INT NULL,
    processed_at TIMESTAMP NULL,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    rejection_reason TEXT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_refund_number (refund_number),
    INDEX idx_fee_account (student_fee_account_id),
    INDEX idx_student (student_id),
    INDEX idx_original_payment (original_payment_id),
    INDEX idx_status (refund_status),
    INDEX idx_date (refund_date),
    INDEX idx_processed_by (processed_by),
    INDEX idx_approved_by (approved_by),
    INDEX idx_created_by (created_by),
    FOREIGN KEY (student_fee_account_id) REFERENCES student_fee_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (original_payment_id) REFERENCES payment_transactions(id) ON DELETE SET NULL,
    FOREIGN KEY (refund_method_id) REFERENCES payment_methods(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 14. INVOICE RECORDS
-- =====================================================
CREATE TABLE invoice_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(100) NOT NULL UNIQUE,
    student_fee_account_id INT NOT NULL,
    student_id INT NOT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    invoice_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    tax_amount DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(12,2) GENERATED ALWAYS AS (invoice_amount + tax_amount) STORED,
    invoice_status ENUM('draft', 'sent', 'paid', 'overdue', 'cancelled') DEFAULT 'draft',
    payment_terms VARCHAR(255) NULL,
    invoice_notes TEXT NULL,
    sent_date DATE NULL,
    sent_method VARCHAR(50) NULL, -- email, SMS, print, etc.
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_fee_account (student_fee_account_id),
    INDEX idx_student (student_id),
    INDEX idx_status (invoice_status),
    INDEX idx_date (invoice_date),
    INDEX idx_due_date (due_date),
    INDEX idx_created_by (created_by),
    FOREIGN KEY (student_fee_account_id) REFERENCES student_fee_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 15. RECEIPT RECORDS
-- =====================================================
CREATE TABLE receipt_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_number VARCHAR(100) NOT NULL UNIQUE,
    payment_transaction_id INT NOT NULL,
    student_fee_account_id INT NOT NULL,
    student_id INT NOT NULL,
    receipt_date DATE NOT NULL,
    receipt_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    payment_method VARCHAR(100) NOT NULL,
    payment_reference VARCHAR(255) NULL,
    receipt_type ENUM('full_payment', 'partial_payment', 'advance_payment', 'refund') NOT NULL,
    receipt_status ENUM('draft', 'issued', 'cancelled') DEFAULT 'draft',
    issued_by INT NOT NULL,
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    cancelled_by INT NULL,
    cancelled_at TIMESTAMP NULL,
    cancellation_reason TEXT NULL,
    receipt_notes TEXT NULL,
    
    INDEX idx_receipt_number (receipt_number),
    INDEX idx_payment_transaction (payment_transaction_id),
    INDEX idx_fee_account (student_fee_account_id),
    INDEX idx_student (student_id),
    INDEX idx_status (receipt_status),
    INDEX idx_date (receipt_date),
    INDEX idx_issued_by (issued_by),
    FOREIGN KEY (payment_transaction_id) REFERENCES payment_transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (student_fee_account_id) REFERENCES student_fee_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (issued_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (cancelled_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- 16. FINANCIAL AUDIT TRAIL
-- =====================================================
CREATE TABLE financial_audit_trail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(100) NOT NULL,
    record_id INT NOT NULL,
    action_type ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    changed_fields JSON NULL, -- Array of changed field names
    user_id INT NOT NULL,
    user_role VARCHAR(100) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    session_id VARCHAR(255) NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_table_record (table_name, record_id),
    INDEX idx_action (action_type),
    INDEX idx_user (user_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_session (session_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 17. BANK ACCOUNTS
-- =====================================================
CREATE TABLE bank_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_name VARCHAR(255) NOT NULL,
    bank_name VARCHAR(255) NOT NULL,
    account_number VARCHAR(50) NOT NULL UNIQUE,
    account_type ENUM('current', 'savings', 'fixed_deposit', 'other') NOT NULL,
    currency VARCHAR(3) DEFAULT 'UGX',
    opening_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    current_balance DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    bank_branch VARCHAR(255) NULL,
    swift_code VARCHAR(20) NULL,
    routing_number VARCHAR(50) NULL,
    account_status ENUM('active', 'inactive', 'suspended', 'closed') DEFAULT 'active',
    is_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_account_number (account_number),
    INDEX idx_bank_name (bank_name),
    INDEX idx_status (account_status),
    INDEX idx_default (is_default)
);

-- =====================================================
-- 18. SALARY PAYMENTS
-- =====================================================
CREATE TABLE salary_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_number VARCHAR(100) NOT NULL UNIQUE,
    staff_id INT NOT NULL,
    payment_period VARCHAR(50) NOT NULL, -- e.g., 'January 2025'
    gross_salary DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    basic_salary DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    allowances DECIMAL(10,2) DEFAULT 0.00,
    deductions DECIMAL(10,2) DEFAULT 0.00,
    tax_amount DECIMAL(10,2) DEFAULT 0.00,
    net_salary DECIMAL(12,2) GENERATED ALWAYS AS (gross_salary - deductions - tax_amount) STORED,
    payment_date DATE NOT NULL,
    payment_method_id INT NOT NULL,
    bank_account_id INT NULL,
    payment_status ENUM('pending', 'processed', 'failed', 'cancelled') DEFAULT 'pending',
    processed_by INT NULL,
    processed_at TIMESTAMP NULL,
    payment_reference VARCHAR(255) NULL,
    payslip_file_path VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_payment_number (payment_number),
    INDEX idx_staff (staff_id),
    INDEX idx_period (payment_period),
    INDEX idx_date (payment_date),
    INDEX idx_status (payment_status),
    INDEX idx_processed_by (processed_by),
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE CASCADE,
    FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id) ON DELETE SET NULL,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- 19. VENDOR PAYMENTS
-- =====================================================
CREATE TABLE vendor_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_number VARCHAR(100) NOT NULL UNIQUE,
    vendor_name VARCHAR(255) NOT NULL,
    vendor_contact VARCHAR(255) NULL,
    expense_record_id INT NULL, -- Link to expense record if applicable
    payment_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    payment_date DATE NOT NULL,
    payment_method_id INT NOT NULL,
    payment_purpose VARCHAR(255) NOT NULL,
    invoice_number VARCHAR(100) NULL,
    purchase_order_number VARCHAR(100) NULL,
    payment_status ENUM('pending', 'processed', 'failed', 'cancelled') DEFAULT 'pending',
    processed_by INT NULL,
    processed_at TIMESTAMP NULL,
    payment_reference VARCHAR(255) NULL,
    receipt_file_path VARCHAR(500) NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_payment_number (payment_number),
    INDEX idx_vendor (vendor_name),
    INDEX idx_expense (expense_record_id),
    INDEX idx_date (payment_date),
    INDEX idx_status (payment_status),
    INDEX idx_processed_by (processed_by),
    INDEX idx_created_by (created_by),
    FOREIGN KEY (expense_record_id) REFERENCES expense_records(id) ON DELETE SET NULL,
    FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 20. REVENUE STREAMS
-- =====================================================
CREATE TABLE revenue_streams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    stream_name VARCHAR(255) NOT NULL,
    stream_description TEXT NULL,
    stream_category ENUM('tuition', 'fees', 'services', 'rentals', 'donations', 'grants', 'investments', 'other') NOT NULL,
    stream_type ENUM('recurring', 'one_time', 'seasonal') NOT NULL,
    expected_amount DECIMAL(15,2) DEFAULT 0.00,
    actual_amount DECIMAL(15,2) DEFAULT 0.00,
    fiscal_year VARCHAR(10) NOT NULL,
    stream_status ENUM('active', 'inactive', 'discontinued') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_name (stream_name),
    INDEX idx_category (stream_category),
    INDEX idx_type (stream_type),
    INDEX idx_fiscal_year (fiscal_year),
    INDEX idx_status (stream_status)
);

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- Insert default fee categories
INSERT INTO fee_categories (category_code, category_name, category_description, category_type, is_mandatory) VALUES
('TUIT', 'Tuition Fees', 'Academic tuition fees for all programs', 'tuition', TRUE),
('REGF', 'Registration Fees', 'One-time registration fees', 'registration', TRUE),
('LIBF', 'Library Fees', 'Library access and resource fees', 'library', TRUE),
('LABF', 'Laboratory Fees', 'Laboratory equipment and consumables', 'laboratory', TRUE),
('EXAMF', 'Examination Fees', 'Internal and external examination fees', 'examination', TRUE),
('HOST', 'Hostel Fees', 'Accommodation and boarding fees', 'accommodation', FALSE),
('DEVF', 'Development Fees', 'School development and infrastructure fees', 'development', TRUE),
('IDCR', 'ID Card Fees', 'Student identification card fees', 'other', TRUE),
('UNIF', 'Uniform Fees', 'School uniform and lab coat fees', 'other', TRUE),
('MEDF', 'Medical Fees', 'Medical examination and insurance fees', 'other', TRUE);

-- Insert default payment methods
INSERT INTO payment_methods (method_code, method_name, method_description, method_type, processing_fee_percentage) VALUES
('CASH', 'Cash Payment', 'Direct cash payment at the finance office', 'cash', 0.00),
('BANK', 'Bank Transfer', 'Direct bank transfer to school account', 'bank_transfer', 0.00),
('MMT', 'Mobile Money', 'Mobile money payment (MTN, Airtel, etc.)', 'mobile_money', 2.50),
('CARD', 'Credit/Debit Card', 'Payment via credit or debit card', 'credit_card', 3.00),
('CHEQ', 'Cheque Payment', 'Payment via bank cheque', 'cheque', 0.00),
('ONLN', 'Online Payment', 'Payment through online portal', 'online', 2.00);

-- Insert default payment gateways
INSERT INTO payment_gateways (gateway_name, gateway_provider, gateway_type, is_active, supports_refunds, transaction_fee_percentage) VALUES
('MTN Mobile Money', 'MTN Uganda', 'mobile_money', TRUE, TRUE, 1.50),
('Airtel Money', 'Airtel Uganda', 'mobile_money', TRUE, TRUE, 1.50),
('Bank of Uganda', 'Bank of Uganda', 'bank', TRUE, FALSE, 0.00),
('Visa/Mastercard', 'Visa International', 'card', TRUE, TRUE, 2.50),
('PayPal', 'PayPal Inc.', 'card', FALSE, TRUE, 4.50);

-- Insert default expense categories
INSERT INTO expense_categories (category_code, category_name, category_description, category_type) VALUES
('SAL', 'Salaries and Wages', 'Staff and employee salaries', 'salary'),
('UTIL', 'Utilities', 'Water, electricity, internet, etc.', 'utility'),
('SUPP', 'Supplies', 'Office and academic supplies', 'supply'),
('MAINT', 'Maintenance', 'Building and equipment maintenance', 'maintenance'),
('RENT', 'Rent and Lease', 'Property rent and lease payments', 'operational'),
('TRAN', 'Transportation', 'Vehicle and transportation expenses', 'operational'),
('MARK', 'Marketing', 'Advertising and promotion expenses', 'operational'),
('PROF', 'Professional Services', 'Legal, accounting, consulting services', 'service'),
('CAPX', 'Capital Expenditure', 'Asset purchases and improvements', 'capital'),
('RES', 'Research', 'Research and development expenses', 'operational');

-- Insert default fee structure for Nursing program
INSERT INTO fee_structure (program_id, level, semester, fee_category_id, fee_amount, payment_deadline, effective_from) VALUES
-- Level 1 Semester 1
(1, 1, 1, 1, 2500000.00, '2024-09-15', '2024-09-01'), -- Tuition
(1, 1, 1, 2, 150000.00, '2024-09-15', '2024-09-01'), -- Registration
(1, 1, 1, 3, 100000.00, '2024-09-15', '2024-09-01'), -- Library
(1, 1, 1, 4, 200000.00, '2024-09-15', '2024-09-01'), -- Laboratory
(1, 1, 1, 5, 150000.00, '2024-09-15', '2024-09-01'), -- Examination
(1, 1, 1, 7, 300000.00, '2024-09-15', '2024-09-01'), -- Development
(1, 1, 1, 8, 50000.00, '2024-09-15', '2024-09-01'), -- ID Card
(1, 1, 1, 9, 200000.00, '2024-09-15', '2024-09-01'), -- Uniform

-- Level 1 Semester 2
(1, 1, 2, 1, 2500000.00, '2025-02-15', '2025-02-01'), -- Tuition
(1, 1, 2, 3, 100000.00, '2025-02-15', '2025-02-01'), -- Library
(1, 1, 2, 4, 200000.00, '2025-02-15', '2025-02-01'), -- Laboratory
(1, 1, 2, 5, 150000.00, '2025-02-15', '2025-02-01'), -- Examination;

-- Insert default fee structure for Midwifery program
INSERT INTO fee_structure (program_id, level, semester, fee_category_id, fee_amount, payment_deadline, effective_from) VALUES
-- Level 1 Semester 1
(2, 1, 1, 1, 2300000.00, '2024-09-15', '2024-09-01'), -- Tuition
(2, 1, 1, 2, 150000.00, '2024-09-15', '2024-09-01'), -- Registration
(2, 1, 1, 3, 100000.00, '2024-09-15', '2024-09-01'), -- Library
(2, 1, 1, 4, 180000.00, '2024-09-15', '2024-09-01'), -- Laboratory
(2, 1, 1, 5, 150000.00, '2024-09-15', '2024-09-01'), -- Examination
(2, 1, 1, 7, 300000.00, '2024-09-15', '2024-09-01'), -- Development
(2, 1, 1, 8, 50000.00, '2024-09-15', '2024-09-01'), -- ID Card
(2, 1, 1, 9, 200000.00, '2024-09-15', '2024-09-01'), -- Uniform

-- Level 1 Semester 2
(2, 1, 2, 1, 2300000.00, '2025-02-15', '2025-02-01'), -- Tuition
(2, 1, 2, 3, 100000.00, '2025-02-15', '2025-02-01'), -- Library
(2, 1, 2, 4, 180000.00, '2025-02-15', '2025-02-01'), -- Laboratory
(2, 1, 2, 5, 150000.00, '2025-02-15', '2025-02-01'), -- Examination;

-- Insert default bank accounts
INSERT INTO bank_accounts (account_name, bank_name, account_number, account_type, opening_balance, current_balance, is_default) VALUES
('ISNM Main Account', 'Stanbic Bank Uganda', '0101234567890', 'current', 50000000.00, 50000000.00, TRUE),
('ISNM Savings Account', 'Centenary Bank', '0209876543210', 'savings', 10000000.00, 10000000.00, FALSE);

-- Insert default budget allocations for 2024/2025
INSERT INTO budget_allocations (budget_code, budget_name, budget_description, department, budget_category, fiscal_year, allocated_amount, start_date, end_date, created_by, budget_status) VALUES
('SAL2024', 'Staff Salaries 2024/2025', 'Annual staff salary budget', 'Finance', 'salary', '2024/2025', 800000000.00, '2024-09-01', '2025-08-31', 1, 'approved'),
('OPR2024', 'Operational Expenses 2024/2025', 'Daily operational expenses', 'Administration', 'operational', '2024/2025', 200000000.00, '2024-09-01', '2025-08-31', 1, 'approved'),
('CAP2024', 'Capital Expenditure 2024/2025', 'Equipment and infrastructure purchases', 'Administration', 'capital', '2024/2025', 150000000.00, '2024-09-01', '2025-08-31', 1, 'approved'),
('SCH2024', 'Scholarships and Aid 2024/2025', 'Student scholarships and financial aid', 'Academic', 'scholarship', '2024/2025', 50000000.00, '2024-09-01', '2025-08-31', 1, 'approved');

-- Insert default revenue streams
INSERT INTO revenue_streams (stream_name, stream_description, stream_category, stream_type, expected_amount, fiscal_year) VALUES
('Tuition Fees', 'Student tuition fees from all programs', 'tuition', 'recurring', 1200000000.00, '2024/2025'),
('Registration Fees', 'One-time student registration fees', 'fees', 'one_time', 50000000.00, '2024/2025'),
('Hostel Fees', 'Student accommodation fees', 'fees', 'recurring', 200000000.00, '2024/2025'),
('Laboratory Fees', 'Laboratory and practical fees', 'fees', 'recurring', 150000000.00, '2024/2025'),
('Library Fees', 'Library and resource fees', 'fees', 'recurring', 80000000.00, '2024/2025'),
('Development Fees', 'School development fees', 'fees', 'recurring', 300000000.00, '2024/2025');

-- =====================================================
-- CREATE STORED PROCEDURES FOR FINANCIAL OPERATIONS
-- =====================================================

DELIMITER //

-- Procedure to create student fee account
CREATE PROCEDURE create_student_fee_account(
    IN p_student_id INT,
    IN p_program_id INT,
    IN p_session_id INT,
    IN p_semester_id INT,
    IN p_created_by INT
)
BEGIN
    DECLARE v_total_fees DECIMAL(12,2) DEFAULT 0.00;
    DECLARE v_due_date DATE;
    DECLARE v_fee_account_id INT;
    
    -- Calculate total fees for the student
    SELECT SUM(fs.fee_amount) INTO v_total_fees
    FROM fee_structure fs
    WHERE fs.program_id = p_program_id
    AND fs.level = (SELECT current_level FROM student_academic_records WHERE student_id = p_student_id)
    AND fs.semester = p_semester_id
    AND fs.is_active = TRUE
    AND (fs.effective_to IS NULL OR fs.effective_to >= CURDATE());
    
    -- Set due date (30 days from start of semester)
    SELECT DATE_ADD(start_date, INTERVAL 30 DAY) INTO v_due_date
    FROM academic_semesters
    WHERE id = p_semester_id;
    
    -- Create fee account
    INSERT INTO student_fee_accounts (
        student_id, program_id, session_id, semester_id, total_fees, due_date, created_at
    ) VALUES (
        p_student_id, p_program_id, p_session_id, p_semester_id, v_total_fees, v_due_date, CURRENT_TIMESTAMP
    );
    
    SET v_fee_account_id = LAST_INSERT_ID();
    
    -- Create invoice
    INSERT INTO invoice_records (
        invoice_number, student_fee_account_id, student_id, invoice_date, due_date, 
        invoice_amount, created_by
    ) VALUES (
        CONCAT('INV', DATE_FORMAT(NOW(), '%Y%m%d'), LPAD(v_fee_account_id, 6, '0')),
        v_fee_account_id, p_student_id, CURDATE(), v_due_date, v_total_fees, p_created_by
    );
    
    SELECT v_fee_account_id as fee_account_id, v_total_fees as total_fees, v_due_date as due_date;
END //

-- Procedure to process payment
CREATE PROCEDURE process_payment(
    IN p_student_fee_account_id INT,
    IN p_student_id INT,
    IN p_amount DECIMAL(12,2),
    IN p_payment_method_id INT,
    IN p_payment_gateway_id INT,
    IN p_payment_reference VARCHAR(255),
    IN p_description TEXT,
    IN p_processed_by INT
)
BEGIN
    DECLARE v_transaction_id VARCHAR(100);
    DECLARE v_receipt_number VARCHAR(100);
    DECLARE v_balance_due DECIMAL(12,2);
    DECLARE v_processing_fee DECIMAL(10,2);
    DECLARE v_net_amount DECIMAL(12,2);
    DECLARE v_payment_status ENUM('unpaid', 'partial', 'paid', 'overpaid');
    
    -- Get current balance and processing fee
    SELECT balance_due INTO v_balance_due
    FROM student_fee_accounts
    WHERE id = p_student_fee_account_id;
    
    SELECT processing_fee_percentage INTO v_processing_fee
    FROM payment_methods
    WHERE id = p_payment_method_id;
    
    -- Calculate processing fee and net amount
    SET v_processing_fee = p_amount * (v_processing_fee / 100);
    SET v_net_amount = p_amount - v_processing_fee;
    
    -- Generate transaction ID and receipt number
    SET v_transaction_id = CONCAT('TXN', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'), LPAD(p_student_id, 6, '0'));
    SET v_receipt_number = CONCAT('RCP', DATE_FORMAT(NOW(), '%Y%m%d'), LPAD(FLOOR(RAND() * 10000), 4, '0'));
    
    -- Insert payment transaction
    INSERT INTO payment_transactions (
        transaction_id, student_fee_account_id, student_id, amount, payment_method_id,
        payment_gateway_id, payment_reference, description, processed_by, processing_fee,
        net_amount, transaction_status, receipt_number
    ) VALUES (
        v_transaction_id, p_student_fee_account_id, p_student_id, p_amount, p_payment_method_id,
        p_payment_gateway_id, p_payment_reference, p_description, p_processed_by, v_processing_fee,
        v_net_amount, 'completed', v_receipt_number
    );
    
    -- Update fee account
    UPDATE student_fee_accounts 
    SET amount_paid = amount_paid + v_net_amount,
        last_payment_date = CURDATE(),
        payment_status = CASE 
            WHEN amount_paid + v_net_amount >= total_fees THEN 'paid'
            WHEN amount_paid + v_net_amount > 0 THEN 'partial'
            ELSE 'unpaid'
        END
    WHERE id = p_student_fee_account_id;
    
    -- Create receipt
    INSERT INTO receipt_records (
        receipt_number, payment_transaction_id, student_fee_account_id, student_id,
        receipt_date, receipt_amount, payment_method, payment_reference, receipt_type, issued_by
    ) VALUES (
        v_receipt_number, LAST_INSERT_ID(), p_student_fee_account_id, p_student_id,
        CURDATE(), v_net_amount, (SELECT method_name FROM payment_methods WHERE id = p_payment_method_id),
        p_payment_reference, 'full_payment', p_processed_by
    );
    
    -- Update invoice status if fully paid
    IF (SELECT amount_paid FROM student_fee_accounts WHERE id = p_student_fee_account_id) >= 
       (SELECT total_fees FROM student_fee_accounts WHERE id = p_student_fee_account_id) THEN
        UPDATE invoice_records 
        SET invoice_status = 'paid'
        WHERE student_fee_account_id = p_student_fee_account_id;
    END IF;
    
    SELECT v_transaction_id as transaction_id, v_receipt_number as receipt_number, v_net_amount as net_amount;
END //

-- Procedure to generate fee statement
CREATE PROCEDURE generate_fee_statement(
    IN p_student_id INT,
    IN p_session_id INT,
    IN p_semester_id INT
)
BEGIN
    -- Get student fee account details
    SELECT 
        sfa.id as fee_account_id,
        u.full_name,
        u.index_number,
        ap.program_name,
        s.session_name,
        sem.semester_name,
        sfa.total_fees,
        sfa.amount_paid,
        sfa.balance_due,
        sfa.payment_status,
        sfa.due_date,
        sfa.late_fee_applied,
        sfa.late_fee_amount,
        sfa.waiver_amount,
        sfa.discount_amount
    FROM student_fee_accounts sfa
    JOIN users u ON sfa.student_id = u.id
    JOIN academic_programs ap ON sfa.program_id = ap.id
    JOIN academic_sessions s ON sfa.session_id = s.id
    JOIN academic_semesters sem ON sfa.semester_id = sem.id
    WHERE sfa.student_id = p_student_id 
    AND sfa.session_id = p_session_id 
    AND sfa.semester_id = p_semester_id;
    
    -- Get fee breakdown
    SELECT 
        fc.category_name,
        fs.fee_amount,
        fc.category_type
    FROM fee_structure fs
    JOIN fee_categories fc ON fs.fee_category_id = fc.id
    WHERE fs.program_id = (SELECT program_id FROM student_fee_accounts 
                          WHERE student_id = p_student_id AND session_id = p_session_id AND semester_id = p_semester_id)
    AND fs.level = (SELECT current_level FROM student_academic_records WHERE student_id = p_student_id)
    AND fs.semester = p_semester_id
    AND fs.is_active = TRUE
    AND (fs.effective_to IS NULL OR fs.effective_to >= CURDATE());
    
    -- Get payment history
    SELECT 
        pt.transaction_id,
        pt.amount,
        pt.net_amount,
        pt.transaction_date,
        pm.method_name,
        pt.payment_reference,
        pt.receipt_number
    FROM payment_transactions pt
    JOIN payment_methods pm ON pt.payment_method_id = pm.id
    WHERE pt.student_id = p_student_id 
    AND pt.student_fee_account_id = (SELECT id FROM student_fee_accounts 
                                   WHERE student_id = p_student_id AND session_id = p_session_id AND semester_id = p_semester_id)
    ORDER BY pt.transaction_date DESC;
END //

-- Procedure to calculate late fees
CREATE PROCEDURE calculate_late_fees()
BEGIN
    DECLARE v_fee_account_id INT;
    DECLARE v_late_fee_amount DECIMAL(10,2);
    DECLARE v_days_overdue INT;
    DECLARE done INT DEFAULT FALSE;
    DECLARE fee_cursor CURSOR FOR 
        SELECT id, balance_due, DATEDIFF(CURDATE(), due_date)
        FROM student_fee_accounts 
        WHERE payment_status IN ('unpaid', 'partial') 
        AND due_date < CURDATE() 
        AND late_fee_applied = FALSE;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN fee_cursor;
    late_fee_loop: LOOP
        FETCH fee_cursor INTO v_fee_account_id, v_balance_due, v_days_overdue;
        IF done THEN
            LEAVE late_fee_loop;
        END IF;
        
        -- Calculate late fee (5% of balance due, applied after 30 days overdue)
        IF v_days_overdue > 30 THEN
            SET v_late_fee_amount = v_balance_due * 0.05;
            
            -- Update fee account with late fee
            UPDATE student_fee_accounts 
            SET late_fee_applied = TRUE,
                late_fee_amount = v_late_fee_amount,
                total_fees = total_fees + v_late_fee_amount
            WHERE id = v_fee_account_id;
        END IF;
    END LOOP;
    CLOSE fee_cursor;
    
    SELECT 'Late fees calculation completed' as message;
END //

-- Procedure to get financial summary
CREATE PROCEDURE get_financial_summary(IN p_fiscal_year VARCHAR(10))
BEGIN
    -- Revenue summary
    SELECT 
        'Revenue' as category,
        rs.stream_name as item,
        rs.expected_amount as budgeted,
        COALESCE(SUM(pt.net_amount), 0) as actual,
        COALESCE(SUM(pt.net_amount), 0) - rs.expected_amount as variance
    FROM revenue_streams rs
    LEFT JOIN payment_transactions pt ON pt.transaction_date >= '2024-09-01' 
        AND pt.transaction_date <= '2025-08-31' 
        AND pt.transaction_status = 'completed'
    WHERE rs.fiscal_year = p_fiscal_year
    GROUP BY rs.id, rs.stream_name, rs.expected_amount;
    
    -- Expense summary
    SELECT 
        'Expenses' as category,
        ec.category_name as item,
        COALESCE(ba.allocated_amount, 0) as budgeted,
        COALESCE(SUM(er.expense_amount), 0) as actual,
        COALESCE(ba.allocated_amount, 0) - COALESCE(SUM(er.expense_amount), 0) as variance
    FROM expense_categories ec
    LEFT JOIN budget_allocations ba ON ec.category_code = SUBSTRING(ba.budget_code, 1, 3) 
        AND ba.fiscal_year = p_fiscal_year
    LEFT JOIN expense_records er ON er.expense_category_id = ec.id 
        AND er.expense_date >= '2024-09-01' 
        AND er.expense_date <= '2025-08-31' 
        AND er.expense_status = 'paid'
    GROUP BY ec.id, ec.category_name, ba.allocated_amount;
    
    -- Overall summary
    SELECT 
        COUNT(DISTINCT pt.student_id) as total_students_paid,
        SUM(pt.net_amount) as total_revenue,
        SUM(er.expense_amount) as total_expenses,
        SUM(pt.net_amount) - SUM(er.expense_amount) as net_profit,
        COUNT(DISTINCT sfa.student_id) as total_students_with_accounts,
        COUNT(CASE WHEN sfa.payment_status = 'paid' THEN 1 END) as students_fully_paid,
        COUNT(CASE WHEN sfa.payment_status = 'partial' THEN 1 END) as students_partially_paid,
        COUNT(CASE WHEN sfa.payment_status = 'unpaid' THEN 1 END) as students_unpaid
    FROM payment_transactions pt
    CROSS JOIN expense_records er
    CROSS JOIN student_fee_accounts sfa
    WHERE pt.transaction_date >= '2024-09-01' 
    AND pt.transaction_date <= '2025-08-31' 
    AND pt.transaction_status = 'completed'
    AND er.expense_date >= '2024-09-01' 
    AND er.expense_date <= '2025-08-31' 
    AND er.expense_status = 'paid';
END //

-- Procedure to generate financial report
CREATE PROCEDURE generate_financial_report(
    IN p_report_type ENUM('income_statement', 'balance_sheet', 'cash_flow', 'fee_collection', 'expense_summary'),
    IN p_period_start DATE,
    IN p_period_end DATE,
    IN p_generated_by INT
)
BEGIN
    DECLARE v_report_id INT;
    DECLARE v_report_name VARCHAR(255);
    
    SET v_report_name = CONCAT(p_report_type, ' Report - ', DATE_FORMAT(p_period_start, '%d %b %Y'), ' to ', DATE_FORMAT(p_period_end, '%d %b %Y'));
    
    -- Insert financial report record
    INSERT INTO financial_reports (
        report_name, report_type, report_period_start, report_period_end, generated_by
    ) VALUES (
        v_report_name, p_report_type, p_period_start, p_period_end, p_generated_by
    );
    
    SET v_report_id = LAST_INSERT_ID();
    
    -- Generate report data based on type
    CASE p_report_type
        WHEN 'income_statement' THEN
            UPDATE financial_reports 
            SET report_data = (
                SELECT JSON_OBJECT(
                    'total_revenue', COALESCE(SUM(pt.net_amount), 0),
                    'total_expenses', COALESCE(SUM(er.expense_amount), 0),
                    'net_income', COALESCE(SUM(pt.net_amount), 0) - COALESCE(SUM(er.expense_amount), 0),
                    'revenue_breakdown', (
                        SELECT JSON_ARRAYAGG(
                            JSON_OBJECT(
                                'stream', rs.stream_name,
                                'amount', COALESCE(SUM(pt.net_amount), 0)
                            )
                        )
                        FROM revenue_streams rs
                        LEFT JOIN payment_transactions pt ON pt.transaction_date BETWEEN p_period_start AND p_period_end
                        WHERE rs.stream_category = 'tuition'
                        GROUP BY rs.stream_name
                    )
                )
                FROM payment_transactions pt
                CROSS JOIN expense_records er
                WHERE pt.transaction_date BETWEEN p_period_start AND p_period_end
                AND pt.transaction_status = 'completed'
                AND er.expense_date BETWEEN p_period_start AND p_period_end
                AND er.expense_status = 'paid'
            ),
            generation_status = 'completed',
            generated_at = CURRENT_TIMESTAMP
            WHERE id = v_report_id;
            
        WHEN 'fee_collection' THEN
            UPDATE financial_reports 
            SET report_data = (
                SELECT JSON_OBJECT(
                    'total_students', COUNT(DISTINCT sfa.student_id),
                    'total_accounts', COUNT(sfa.id),
                    'fully_paid', COUNT(CASE WHEN sfa.payment_status = 'paid' THEN 1 END),
                    'partially_paid', COUNT(CASE WHEN sfa.payment_status = 'partial' THEN 1 END),
                    'unpaid', COUNT(CASE WHEN sfa.payment_status = 'unpaid' THEN 1 END),
                    'total_fees', SUM(sfa.total_fees),
                    'total_collected', SUM(sfa.amount_paid),
                    'total_outstanding', SUM(sfa.balance_due),
                    'collection_rate', (SUM(sfa.amount_paid) / SUM(sfa.total_fees)) * 100
                )
                FROM student_fee_accounts sfa
                WHERE sfa.session_id = (SELECT id FROM academic_sessions WHERE is_current = TRUE)
            ),
            generation_status = 'completed',
            generated_at = CURRENT_TIMESTAMP
            WHERE id = v_report_id;
    END CASE;
    
    SELECT v_report_id as report_id, v_report_name as report_name;
END //

DELIMITER ;

-- =====================================================
-- CREATE VIEWS FOR FINANCIAL OPERATIONS
-- =====================================================

-- View for student fee summary
CREATE VIEW student_fee_summary AS
SELECT 
    u.id as student_id,
    u.full_name,
    u.index_number,
    ap.program_name,
    s.session_name,
    sem.semester_name,
    sfa.total_fees,
    sfa.amount_paid,
    sfa.balance_due,
    sfa.payment_status,
    sfa.due_date,
    CASE 
        WHEN sfa.due_date < CURDATE() AND sfa.balance_due > 0 THEN 'Overdue'
        WHEN sfa.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND sfa.balance_due > 0 THEN 'Due Soon'
        WHEN sfa.balance_due = 0 THEN 'Paid'
        ELSE 'Not Due'
    END as urgency_status
FROM users u
JOIN student_fee_accounts sfa ON u.id = sfa.student_id
JOIN academic_programs ap ON sfa.program_id = ap.id
JOIN academic_sessions s ON sfa.session_id = s.id
JOIN academic_semesters sem ON sfa.semester_id = sem.id
WHERE u.type = 'student';

-- View for payment transaction summary
CREATE VIEW payment_transaction_summary AS
SELECT 
    pt.id,
    pt.transaction_id,
    pt.amount,
    pt.net_amount,
    pt.transaction_date,
    pt.transaction_status,
    pm.method_name,
    u.full_name as student_name,
    u.index_number,
    ap.program_name,
    pt.receipt_number,
    pt.payment_reference
FROM payment_transactions pt
JOIN payment_methods pm ON pt.payment_method_id = pm.id
JOIN users u ON pt.student_id = u.id
JOIN student_fee_accounts sfa ON pt.student_fee_account_id = sfa.id
JOIN academic_programs ap ON sfa.program_id = ap.id;

-- View for budget utilization
CREATE VIEW budget_utilization AS
SELECT 
    ba.id as budget_id,
    ba.budget_code,
    ba.budget_name,
    ba.department,
    ba.allocated_amount,
    ba.spent_amount,
    ba.remaining_amount,
    ROUND((ba.spent_amount / ba.allocated_amount) * 100, 2) as utilization_percentage,
    CASE 
        WHEN ba.spent_amount >= ba.allocated_amount THEN 'Exhausted'
        WHEN (ba.spent_amount / ba.allocated_amount) >= 0.9 THEN 'High Usage'
        WHEN (ba.spent_amount / ba.allocated_amount) >= 0.7 THEN 'Moderate Usage'
        ELSE 'Low Usage'
    END as usage_status
FROM budget_allocations ba;

-- View for expense summary by category
CREATE VIEW expense_summary_by_category AS
SELECT 
    ec.category_name,
    ec.category_type,
    COUNT(er.id) as transaction_count,
    SUM(er.expense_amount) as total_amount,
    AVG(er.expense_amount) as average_amount,
    MIN(er.expense_amount) as minimum_amount,
    MAX(er.expense_amount) as maximum_amount,
    er.expense_date as latest_date
FROM expense_records er
JOIN expense_categories ec ON er.expense_category_id = ec.id
WHERE er.expense_status = 'paid'
GROUP BY ec.id, ec.category_name, ec.category_type
ORDER BY total_amount DESC;

-- View for revenue collection trend
CREATE VIEW revenue_collection_trend AS
SELECT 
    DATE_FORMAT(pt.transaction_date, '%Y-%m') as month,
    COUNT(DISTINCT pt.student_id) as unique_students,
    COUNT(pt.id) as transaction_count,
    SUM(pt.amount) as gross_amount,
    SUM(pt.net_amount) as net_amount,
    SUM(pt.processing_fee) as total_fees,
    pm.method_name
FROM payment_transactions pt
JOIN payment_methods pm ON pt.payment_method_id = pm.id
WHERE pt.transaction_status = 'completed'
GROUP BY DATE_FORMAT(pt.transaction_date, '%Y-%m'), pm.method_name
ORDER BY month DESC, pm.method_name;

-- =====================================================
-- TRIGGERS FOR AUTOMATIC FINANCIAL OPERATIONS
-- =====================================================

DELIMITER //

-- Trigger to log financial changes
CREATE TRIGGER after_payment_transaction_insert
AFTER INSERT ON payment_transactions
FOR EACH ROW
BEGIN
    -- Log to financial audit trail
    INSERT INTO financial_audit_trail (
        table_name, record_id, action_type, new_values, user_id, user_role
    ) VALUES (
        'payment_transactions', NEW.id, 'INSERT',
        JSON_OBJECT(
            'transaction_id', NEW.transaction_id,
            'amount', NEW.amount,
            'student_id', NEW.student_id,
            'payment_method_id', NEW.payment_method_id
        ),
        NEW.processed_by,
        (SELECT role FROM users WHERE id = NEW.processed_by)
    );
    
    -- Update dashboard activity log
    INSERT INTO dashboard_activity_logs (
        user_id, action, entity_type, entity_id, description
    ) VALUES (
        NEW.student_id, 'payment', 'fee', NEW.student_fee_account_id,
        CONCAT('Payment of ', NEW.amount, ' processed via ', (SELECT method_name FROM payment_methods WHERE id = NEW.payment_method_id))
    );
END //

-- Trigger to update budget when expense is paid
CREATE TRIGGER after_expense_record_update
AFTER UPDATE ON expense_records
FOR EACH ROW
BEGIN
    IF OLD.expense_status != 'paid' AND NEW.expense_status = 'paid' THEN
        -- Update budget allocation spent amount
        UPDATE budget_allocations 
        SET spent_amount = spent_amount + NEW.expense_amount
        WHERE id = NEW.budget_allocation_id;
        
        -- Log to financial audit trail
        INSERT INTO financial_audit_trail (
            table_name, record_id, action_type, new_values, user_id, user_role
        ) VALUES (
            'expense_records', NEW.id, 'UPDATE',
            JSON_OBJECT(
                'expense_status', 'paid',
                'expense_amount', NEW.expense_amount,
                'paid_by', NEW.paid_by
            ),
            NEW.paid_by,
            (SELECT role FROM users WHERE id = NEW.paid_by)
        );
    END IF;
END //

DELIMITER ;

-- =====================================================
-- FINAL SETUP COMPLETE
-- =====================================================

-- Grant necessary permissions for stored procedures
GRANT EXECUTE ON PROCEDURE create_student_fee_account TO 'root'@'localhost';
GRANT EXECUTE ON PROCEDURE process_payment TO 'root'@'localhost';
GRANT EXECUTE ON PROCEDURE generate_fee_statement TO 'root'@'localhost';
GRANT EXECUTE ON PROCEDURE calculate_late_fees TO 'root'@'localhost';
GRANT EXECUTE ON PROCEDURE get_financial_summary TO 'root'@'localhost';
GRANT EXECUTE ON PROCEDURE generate_financial_report TO 'root'@'localhost';

-- =====================================================
-- SETUP COMPLETE MESSAGE
-- =====================================================
SELECT 'ISNM Finance Management Setup Complete!' as status,
       COUNT(*) as total_tables_created
FROM information_schema.tables 
WHERE table_schema = 'isnm_db' 
AND table_name IN ('fee_categories', 'fee_structure', 'student_fee_accounts', 'payment_transactions', 'budget_allocations', 'expense_records', 'financial_reports');
