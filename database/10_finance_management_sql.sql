-- ISNM School Management System - Finance Management SQL
-- Comprehensive SQL for fee management, payments, financial reporting, and budget tracking

USE isnm_school;

-- ========================================
-- FINANCIAL MANAGEMENT TABLES
-- ========================================

-- Drop existing tables if they exist to ensure clean creation
DROP TABLE IF EXISTS payment_transactions;
DROP TABLE IF EXISTS student_fee_accounts;
DROP TABLE IF EXISTS fee_structure;
DROP TABLE IF EXISTS fee_categories;
DROP TABLE IF EXISTS budget_allocations;
DROP TABLE IF EXISTS expense_records;
DROP TABLE IF EXISTS financial_reports;

-- Fee categories table
CREATE TABLE fee_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_code VARCHAR(20) NOT NULL UNIQUE,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_mandatory BOOLEAN DEFAULT TRUE,
    is_refundable BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_category_code (category_code),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fee structure table
CREATE TABLE fee_structure (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    academic_year VARCHAR(9) NOT NULL, -- Format: 2024-2025
    semester ENUM('year1_sem1', 'year1_sem2', 'year2_sem1', 'year2_sem2', 'year3_sem1', 'year3_sem2') NOT NULL,
    tuition_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    registration_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    library_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    lab_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    examination_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    accommodation_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    medical_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    development_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    other_fees DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_fee DECIMAL(10,2) GENERATED ALWAYS AS (
        tuition_fee + registration_fee + library_fee + lab_fee + examination_fee + 
        accommodation_fee + medical_fee + development_fee + other_fees
    ) STORED,
    status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    UNIQUE KEY unique_fee_structure (program_id, academic_year, semester),
    INDEX idx_program_id (program_id),
    INDEX idx_academic_year (academic_year),
    INDEX idx_semester (semester),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Student fee accounts
CREATE TABLE student_fee_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    academic_year VARCHAR(9) NOT NULL,
    semester ENUM('year1_sem1', 'year1_sem2', 'year2_sem1', 'year2_sem2', 'year3_sem1', 'year3_sem2') NOT NULL,
    total_fee DECIMAL(10,2) NOT NULL,
    amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0,
    balance DECIMAL(10,2) GENERATED ALWAYS AS (total_fee - amount_paid) STORED,
    payment_status ENUM('unpaid', 'partial', 'paid', 'overdue', 'refunded') DEFAULT 'unpaid',
    due_date DATE NOT NULL,
    last_payment_date TIMESTAMP NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    scholarship_amount DECIMAL(10,2) DEFAULT 0,
    penalty_amount DECIMAL(10,2) DEFAULT 0,
    status ENUM('active', 'inactive', 'closed') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    UNIQUE KEY unique_student_fee_account (student_id, academic_year, semester),
    INDEX idx_student_id (student_id),
    INDEX idx_academic_year (academic_year),
    INDEX idx_semester (semester),
    INDEX idx_payment_status (payment_status),
    INDEX idx_due_date (due_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payment transactions
CREATE TABLE payment_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    fee_account_id INT NOT NULL,
    transaction_id VARCHAR(100) NOT NULL UNIQUE,
    transaction_reference VARCHAR(100),
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'bank_transfer', 'mobile_money', 'cheque', 'credit_card', 'bank_draft') NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    receipt_number VARCHAR(100),
    bank_name VARCHAR(100),
    transaction_code VARCHAR(100),
    paid_by VARCHAR(255),
    contact_number VARCHAR(20),
    collected_by INT NOT NULL,
    verified_by INT NULL,
    verification_date TIMESTAMP NULL,
    notes TEXT,
    status ENUM('pending', 'completed', 'failed', 'refunded', 'cancelled') DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (fee_account_id) REFERENCES student_fee_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (collected_by) REFERENCES users(id),
    FOREIGN KEY (verified_by) REFERENCES users(id),
    INDEX idx_student_id (student_id),
    INDEX idx_fee_account_id (fee_account_id),
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_payment_date (payment_date),
    INDEX idx_payment_method (payment_method),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Budget allocations table
CREATE TABLE budget_allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    budget_code VARCHAR(50) NOT NULL UNIQUE,
    budget_name VARCHAR(255) NOT NULL,
    department ENUM('academics', 'finance', 'administration', 'student_services', 'infrastructure', 'maintenance', 'library', 'hostel') NOT NULL,
    allocated_amount DECIMAL(12,2) NOT NULL,
    spent_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    remaining_amount DECIMAL(12,2) GENERATED ALWAYS AS (allocated_amount - spent_amount) STORED,
    fiscal_year VARCHAR(9) NOT NULL, -- Format: 2024-2025
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive', 'completed', 'suspended') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_budget_code (budget_code),
    INDEX idx_department (department),
    INDEX idx_fiscal_year (fiscal_year),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Expense records table
CREATE TABLE expense_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    budget_allocation_id INT NOT NULL,
    expense_code VARCHAR(50) NOT NULL UNIQUE,
    expense_title VARCHAR(255) NOT NULL,
    expense_category ENUM('salaries', 'utilities', 'maintenance', 'supplies', 'equipment', 'travel', 'training', 'events', 'construction', 'other') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    expense_date DATE NOT NULL,
    vendor_name VARCHAR(255),
    invoice_number VARCHAR(100),
    receipt_number VARCHAR(100),
    payment_method ENUM('cash', 'bank_transfer', 'cheque', 'mobile_money') NOT NULL,
    approved_by INT NOT NULL,
    approved_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    paid_by INT NULL,
    paid_date TIMESTAMP NULL,
    description TEXT,
    supporting_documents TEXT, -- JSON array of file paths
    status ENUM('pending', 'approved', 'paid', 'rejected', 'cancelled') DEFAULT 'approved',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (budget_allocation_id) REFERENCES budget_allocations(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id),
    FOREIGN KEY (paid_by) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_budget_allocation_id (budget_allocation_id),
    INDEX idx_expense_category (expense_category),
    INDEX idx_expense_date (expense_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Financial reports table
CREATE TABLE financial_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_name VARCHAR(255) NOT NULL,
    report_type ENUM('monthly', 'quarterly', 'annual', 'custom') NOT NULL,
    report_period VARCHAR(20) NOT NULL, -- e.g., "2024-03", "Q1-2024", "2024-2025"
    total_income DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_expenses DECIMAL(12,2) NOT NULL DEFAULT 0,
    net_amount DECIMAL(12,2) GENERATED ALWAYS AS (total_income - total_expenses) STORED,
    student_fees_collected DECIMAL(12,2) NOT NULL DEFAULT 0,
    other_income DECIMAL(12,2) NOT NULL DEFAULT 0,
    salaries_expenses DECIMAL(12,2) NOT NULL DEFAULT 0,
    operational_expenses DECIMAL(12,2) NOT NULL DEFAULT 0,
    capital_expenses DECIMAL(12,2) NOT NULL DEFAULT 0,
    other_expenses DECIMAL(12,2) NOT NULL DEFAULT 0,
    report_data LONGTEXT, -- JSON data for detailed breakdown
    generated_by INT NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('draft', 'final', 'archived') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (generated_by) REFERENCES users(id),
    INDEX idx_report_type (report_type),
    INDEX idx_report_period (report_period),
    INDEX idx_generated_at (generated_at),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- INSERT DEFAULT DATA
-- ========================================

-- Insert default fee categories
INSERT INTO fee_categories (category_code, category_name, description, is_mandatory, is_refundable, created_by) VALUES
('TUITION', 'Tuition Fee', 'Main tuition fee for academic instruction', TRUE, FALSE, 1),
('REGISTRATION', 'Registration Fee', 'One-time registration fee per semester', TRUE, FALSE, 1),
('LIBRARY', 'Library Fee', 'Library access and resource fee', TRUE, FALSE, 1),
('LAB', 'Laboratory Fee', 'Laboratory equipment and consumables fee', TRUE, FALSE, 1),
('EXAMINATION', 'Examination Fee', 'Examination and assessment fee', TRUE, FALSE, 1),
('ACCOMMODATION', 'Accommodation Fee', 'Hostel accommodation fee', FALSE, FALSE, 1),
('MEDICAL', 'Medical Fee', 'Medical services and insurance fee', TRUE, FALSE, 1),
('DEVELOPMENT', 'Development Fee', 'School development and infrastructure fee', TRUE, FALSE, 1),
('OTHER', 'Other Fees', 'Miscellaneous fees and charges', FALSE, TRUE, 1)
ON DUPLICATE KEY UPDATE category_name = VALUES(category_name);

-- Get program IDs for fee structure
SET @cm_program_id = (SELECT id FROM programs WHERE program_code = 'CM');
SET @cn_program_id = (SELECT id FROM programs WHERE program_code = 'CN');
SET @dmordn_program_id = (SELECT id FROM programs WHERE program_code = 'DMORDN');

-- Insert comprehensive fee structure for all programs
INSERT INTO fee_structure (
    program_id, academic_year, semester, tuition_fee, registration_fee, library_fee, lab_fee, 
    examination_fee, accommodation_fee, medical_fee, development_fee, other_fees, created_by
) VALUES
-- CM Program Year 1
(@cm_program_id, '2024-2025', 'year1_sem1', 1500000, 50000, 100000, 200000, 150000, 300000, 75000, 100000, 50000, 1),
(@cm_program_id, '2024-2025', 'year1_sem2', 1500000, 0, 100000, 200000, 150000, 300000, 75000, 100000, 50000, 1),
-- CM Program Year 2
(@cm_program_id, '2024-2025', 'year2_sem1', 1600000, 0, 100000, 250000, 150000, 300000, 75000, 100000, 50000, 1),
(@cm_program_id, '2024-2025', 'year2_sem2', 1600000, 0, 100000, 250000, 150000, 300000, 75000, 100000, 50000, 1),

-- CN Program Year 1
(@cn_program_id, '2024-2025', 'year1_sem1', 1500000, 50000, 100000, 200000, 150000, 300000, 75000, 100000, 50000, 1),
(@cn_program_id, '2024-2025', 'year1_sem2', 1500000, 0, 100000, 200000, 150000, 300000, 75000, 100000, 50000, 1),
-- CN Program Year 2
(@cn_program_id, '2024-2025', 'year2_sem1', 1600000, 0, 100000, 250000, 150000, 300000, 75000, 100000, 50000, 1),
(@cn_program_id, '2024-2025', 'year2_sem2', 1600000, 0, 100000, 250000, 150000, 300000, 75000, 100000, 50000, 1),

-- DMORDN Program Year 1
(@dmordn_program_id, '2024-2025', 'year1_sem1', 2000000, 50000, 150000, 250000, 200000, 350000, 100000, 125000, 75000, 1),
(@dmordn_program_id, '2024-2025', 'year1_sem2', 2000000, 0, 150000, 250000, 200000, 350000, 100000, 125000, 75000, 1),
-- DMORDN Program Year 2
(@dmordn_program_id, '2024-2025', 'year2_sem1', 2200000, 0, 150000, 300000, 200000, 350000, 100000, 125000, 75000, 1),
(@dmordn_program_id, '2024-2025', 'year2_sem2', 2200000, 0, 150000, 300000, 200000, 350000, 100000, 125000, 75000, 1),
-- DMORDN Program Year 3
(@dmordn_program_id, '2024-2025', 'year3_sem1', 2400000, 0, 150000, 350000, 200000, 350000, 100000, 125000, 75000, 1),
(@dmordn_program_id, '2024-2025', 'year3_sem2', 2400000, 0, 150000, 350000, 200000, 350000, 100000, 125000, 75000, 1)
ON DUPLICATE KEY UPDATE 
    tuition_fee = VALUES(tuition_fee),
    registration_fee = VALUES(registration_fee),
    library_fee = VALUES(library_fee),
    lab_fee = VALUES(lab_fee),
    examination_fee = VALUES(examination_fee),
    accommodation_fee = VALUES(accommodation_fee),
    medical_fee = VALUES(medical_fee),
    development_fee = VALUES(development_fee),
    other_fees = VALUES(other_fees);

-- Insert default budget allocations
INSERT INTO budget_allocations (
    budget_code, budget_name, department, allocated_amount, fiscal_year, start_date, end_date, description, created_by
) VALUES
('ACAD-2024-2025', 'Academic Department Budget', 'academics', 500000000, '2024-2025', '2024-07-01', '2025-06-30', 'Budget for academic operations including salaries and teaching materials', 1),
('FIN-2024-2025', 'Finance Department Budget', 'finance', 100000000, '2024-2025', '2024-07-01', '2025-06-30', 'Budget for finance department operations and banking services', 1),
('ADMIN-2024-2025', 'Administration Budget', 'administration', 200000000, '2024-2025', '2024-07-01', '2025-06-30', 'Budget for administrative operations and overhead costs', 1),
('STUDENT-2024-2025', 'Student Services Budget', 'student_services', 150000000, '2024-2025', '2024-07-01', '2025-06-30', 'Budget for student services including counseling and activities', 1),
('INFRA-2024-2025', 'Infrastructure Budget', 'infrastructure', 300000000, '2024-2025', '2024-07-01', '2025-06-30', 'Budget for infrastructure maintenance and development', 1),
('LIB-2024-2025', 'Library Budget', 'library', 80000000, '2024-2025', '2024-07-01', '2025-06-30', 'Budget for library operations and book acquisitions', 1),
('HOSTEL-2024-2025', 'Hostel Budget', 'hostel', 120000000, '2024-2025', '2024-07-01', '2025-06-30', 'Budget for hostel operations and maintenance', 1)
ON DUPLICATE KEY UPDATE 
    budget_name = VALUES(budget_name),
    allocated_amount = VALUES(allocated_amount),
    description = VALUES(description);

-- ========================================
-- CREATE VIEWS FOR FINANCIAL REPORTING
-- ========================================

-- Fee collection summary view
CREATE OR REPLACE VIEW fee_collection_summary AS
SELECT 
    fs.academic_year,
    fs.semester,
    p.program_name,
    p.program_type,
    COUNT(DISTINCT sfa.student_id) as total_students,
    SUM(sfa.total_fee) as total_fees,
    SUM(sfa.amount_paid) as total_collected,
    SUM(sfa.balance) as total_balance,
    COUNT(CASE WHEN sfa.payment_status = 'paid' THEN 1 END) as fully_paid,
    COUNT(CASE WHEN sfa.payment_status = 'partial' THEN 1 END) as partially_paid,
    COUNT(CASE WHEN sfa.payment_status = 'unpaid' THEN 1 END) as unpaid,
    COUNT(CASE WHEN sfa.payment_status = 'overdue' THEN 1 END) as overdue,
    ROUND((SUM(sfa.amount_paid) * 100.0) / SUM(sfa.total_fee), 2) as collection_rate
FROM fee_structure fs
JOIN student_fee_accounts sfa ON fs.id = sfa.fee_structure_id
JOIN programs p ON fs.program_id = p.id
GROUP BY fs.academic_year, fs.semester, p.program_name, p.program_type
ORDER BY fs.academic_year DESC, fs.semester;

-- Payment methods summary view
CREATE OR REPLACE VIEW payment_methods_summary AS
SELECT 
    payment_method,
    COUNT(*) as transaction_count,
    SUM(amount) as total_amount,
    AVG(amount) as average_amount,
    MIN(amount) as minimum_amount,
    MAX(amount) as maximum_amount,
    DATE(payment_date) as payment_date
FROM payment_transactions
WHERE status = 'completed'
GROUP BY payment_method, DATE(payment_date)
ORDER BY payment_date DESC;

-- Budget utilization view
CREATE OR REPLACE VIEW budget_utilization AS
SELECT 
    ba.budget_code,
    ba.budget_name,
    ba.department,
    ba.allocated_amount,
    ba.spent_amount,
    ba.remaining_amount,
    ROUND((ba.spent_amount * 100.0) / ba.allocated_amount, 2) as utilization_rate,
    CASE 
        WHEN ba.spent_amount = 0 THEN 'Not Started'
        WHEN ba.spent_amount < ba.allocated_amount * 0.5 THEN 'Under Utilized'
        WHEN ba.spent_amount < ba.allocated_amount * 0.9 THEN 'On Track'
        WHEN ba.spent_amount < ba.allocated_amount THEN 'Nearly Exhausted'
        ELSE 'Exhausted'
    END as utilization_status,
    ba.fiscal_year,
    ba.status
FROM budget_allocations ba
ORDER BY ba.fiscal_year DESC, ba.department;

-- ========================================
-- STORED PROCEDURES FOR FINANCIAL OPERATIONS
-- ========================================

DELIMITER //

-- Procedure to create student fee accounts
CREATE PROCEDURE IF NOT EXISTS create_student_fee_accounts(
    IN p_student_id INT,
    IN p_academic_year VARCHAR(9),
    IN p_program_id INT,
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN
)
BEGIN
    DECLARE v_semester_count INT DEFAULT 0;
    DECLARE v_created_count INT DEFAULT 0;
    
    -- Get all semesters for the program
    SELECT COUNT(DISTINCT semester) INTO v_semester_count
    FROM fee_structure 
    WHERE program_id = p_program_id AND academic_year = p_academic_year AND status = 'active';
    
    IF v_semester_count = 0 THEN
        SET p_result = 'No fee structure found for this program and academic year';
        SET p_success = FALSE;
    ELSE
        -- Create fee accounts for all semesters
        INSERT INTO student_fee_accounts (
            student_id, academic_year, semester, total_fee, due_date, created_by
        )
        SELECT 
            p_student_id,
            p_academic_year,
            fs.semester,
            fs.total_fee,
            CASE 
                WHEN fs.semester LIKE '%sem1%' THEN DATE(CONCAT(SUBSTRING(p_academic_year, 1, 4), '-08-15'))
                ELSE DATE(CONCAT(SUBSTRING(p_academic_year, 1, 4), '-01-15'))
            END,
            1
        FROM fee_structure fs
        WHERE fs.program_id = p_program_id 
          AND fs.academic_year = p_academic_year 
          AND fs.status = 'active'
        AND fs.id NOT IN (
            SELECT fee_structure_id FROM student_fee_accounts 
            WHERE student_id = p_student_id AND academic_year = p_academic_year
        );
        
        SET v_created_count = ROW_COUNT();
        
        -- Log activity
        INSERT INTO activity_logs (user_id, action, description, table_name, record_id)
        VALUES (p_student_id, 'FEE_ACCOUNT_CREATE', CONCAT('Created ', v_created_count, ' fee accounts'), 'student_fee_accounts', p_student_id);
        
        SET p_result = CONCAT('Successfully created ', v_created_count, ' fee accounts');
        SET p_success = TRUE;
    END IF;
END //

-- Procedure to process payment
CREATE PROCEDURE IF NOT EXISTS process_payment(
    IN p_student_id INT,
    IN p_fee_account_id INT,
    IN p_amount DECIMAL(10,2),
    IN p_payment_method VARCHAR(20),
    IN p_receipt_number VARCHAR(100),
    IN p_paid_by VARCHAR(255),
    IN p_collected_by INT,
    IN p_notes TEXT,
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN,
    OUT p_transaction_id INT
)
BEGIN
    DECLARE v_balance DECIMAL(10,2);
    DECLARE v_transaction_exists INT DEFAULT 0;
    DECLARE v_receipt_exists INT DEFAULT 0;
    
    -- Check if fee account exists and belongs to student
    SELECT COUNT(*) INTO v_transaction_exists
    FROM student_fee_accounts 
    WHERE id = p_fee_account_id AND student_id = p_student_id AND status = 'active';
    
    -- Check if receipt number already exists
    SELECT COUNT(*) INTO v_receipt_exists
    FROM payment_transactions 
    WHERE receipt_number = p_receipt_number AND status = 'completed';
    
    IF v_transaction_exists = 0 THEN
        SET p_result = 'Invalid fee account or access denied';
        SET p_success = FALSE;
        SET p_transaction_id = NULL;
    ELSEIF v_receipt_exists > 0 THEN
        SET p_result = 'Receipt number already exists';
        SET p_success = FALSE;
        SET p_transaction_id = NULL;
    ELSE
        -- Get current balance
        SELECT balance INTO v_balance
        FROM student_fee_accounts 
        WHERE id = p_fee_account_id;
        
        -- Validate payment amount
        IF p_amount <= 0 THEN
            SET p_result = 'Payment amount must be greater than 0';
            SET p_success = FALSE;
            SET p_transaction_id = NULL;
        ELSE
            -- Generate transaction ID
            SET p_transaction_id = CONCAT('TXN', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'), LPAD(p_student_id, 6, '0'));
            
            -- Insert payment transaction
            INSERT INTO payment_transactions (
                student_id, fee_account_id, transaction_id, amount, payment_method, 
                receipt_number, paid_by, collected_by, notes, status
            ) VALUES (
                p_student_id, p_fee_account_id, p_transaction_id, p_amount, p_payment_method,
                p_receipt_number, p_paid_by, p_collected_by, p_notes, 'completed'
            );
            
            -- Update fee account
            UPDATE student_fee_accounts 
            SET amount_paid = amount_paid + p_amount,
                last_payment_date = NOW(),
                payment_status = CASE 
                    WHEN (balance - p_amount) <= 0 THEN 'paid'
                    WHEN amount_paid + p_amount > 0 THEN 'partial'
                    ELSE 'unpaid'
                END,
                updated_at = NOW()
            WHERE id = p_fee_account_id;
            
            -- Log activity
            INSERT INTO activity_logs (user_id, action, description, table_name, record_id)
            VALUES (p_collected_by, 'PAYMENT_PROCESS', CONCAT('Processed payment of ', p_amount), 'payment_transactions', LAST_INSERT_ID());
            
            SET p_result = 'Payment processed successfully';
            SET p_success = TRUE;
        END IF;
    END IF;
END //

-- Procedure to generate financial report
CREATE PROCEDURE IF NOT EXISTS generate_financial_report(
    IN p_report_type VARCHAR(20),
    IN p_report_period VARCHAR(20),
    IN p_generated_by INT,
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN,
    OUT p_report_id INT
)
BEGIN
    DECLARE v_total_income DECIMAL(12,2);
    DECLARE v_total_expenses DECIMAL(12,2);
    DECLARE v_student_fees DECIMAL(12,2);
    DECLARE v_other_income DECIMAL(12,2);
    DECLARE v_salaries DECIMAL(12,2);
    DECLARE v_operational DECIMAL(12,2);
    DECLARE v_capital DECIMAL(12,2);
    DECLARE v_other_expenses DECIMAL(12,2);
    
    -- Calculate income
    SELECT COALESCE(SUM(amount), 0) INTO v_student_fees
    FROM payment_transactions 
    WHERE status = 'completed' 
      AND (
        (p_report_type = 'monthly' AND DATE_FORMAT(payment_date, '%Y-%m') = p_report_period) OR
        (p_report_type = 'quarterly' AND CONCAT('Q', QUARTER(payment_date), '-', YEAR(payment_date)) = p_report_period) OR
        (p_report_type = 'annual' AND DATE_FORMAT(payment_date, '%Y') = p_report_period)
      );
    
    -- Calculate expenses
    SELECT COALESCE(SUM(amount), 0) INTO v_salaries
    FROM expense_records 
    WHERE status = 'paid' AND expense_category = 'salaries'
      AND (
        (p_report_type = 'monthly' AND DATE_FORMAT(expense_date, '%Y-%m') = p_report_period) OR
        (p_report_type = 'quarterly' AND CONCAT('Q', QUARTER(expense_date), '-', YEAR(expense_date)) = p_report_period) OR
        (p_report_type = 'annual' AND DATE_FORMAT(expense_date, '%Y') = p_report_period)
      );
    
    SELECT COALESCE(SUM(amount), 0) INTO v_operational
    FROM expense_records 
    WHERE status = 'paid' AND expense_category IN ('utilities', 'maintenance', 'supplies')
      AND (
        (p_report_type = 'monthly' AND DATE_FORMAT(expense_date, '%Y-%m') = p_report_period) OR
        (p_report_type = 'quarterly' AND CONCAT('Q', QUARTER(expense_date), '-', YEAR(expense_date)) = p_report_period) OR
        (p_report_type = 'annual' AND DATE_FORMAT(expense_date, '%Y') = p_report_period)
      );
    
    SELECT COALESCE(SUM(amount), 0) INTO v_capital
    FROM expense_records 
    WHERE status = 'paid' AND expense_category IN ('equipment', 'construction')
      AND (
        (p_report_type = 'monthly' AND DATE_FORMAT(expense_date, '%Y-%m') = p_report_period) OR
        (p_report_type = 'quarterly' AND CONCAT('Q', QUARTER(expense_date), '-', YEAR(expense_date)) = p_report_period) OR
        (p_report_type = 'annual' AND DATE_FORMAT(expense_date, '%Y') = p_report_period)
      );
    
    SELECT COALESCE(SUM(amount), 0) INTO v_other_expenses
    FROM expense_records 
    WHERE status = 'paid' AND expense_category = 'other'
      AND (
        (p_report_type = 'monthly' AND DATE_FORMAT(expense_date, '%Y-%m') = p_report_period) OR
        (p_report_type = 'quarterly' AND CONCAT('Q', QUARTER(expense_date), '-', YEAR(expense_date)) = p_report_period) OR
        (p_report_type = 'annual' AND DATE_FORMAT(expense_date, '%Y') = p_report_period)
      );
    
    -- Calculate totals
    SET v_total_income = v_student_fees + v_other_income;
    SET v_total_expenses = v_salaries + v_operational + v_capital + v_other_expenses;
    
    -- Insert financial report
    INSERT INTO financial_reports (
        report_name, report_type, report_period, total_income, total_expenses,
        student_fees_collected, other_income, salaries_expenses, operational_expenses,
        capital_expenses, other_expenses, generated_by
    ) VALUES (
        CONCAT(p_report_type, ' Financial Report - ', p_report_period),
        p_report_type, p_report_period, v_total_income, v_total_expenses,
        v_student_fees, v_other_income, v_salaries, v_operational,
        v_capital, v_other_expenses, p_generated_by
    );
    
    SET p_report_id = LAST_INSERT_ID();
    
    -- Log activity
    INSERT INTO activity_logs (user_id, action, description, table_name, record_id)
    VALUES (p_generated_by, 'FINANCIAL_REPORT_GENERATE', CONCAT('Generated ', p_report_type, ' report for ', p_report_period), 'financial_reports', p_report_id);
    
    SET p_result = 'Financial report generated successfully';
    SET p_success = TRUE;
END //

-- Procedure to check overdue payments
CREATE PROCEDURE IF NOT EXISTS check_overdue_payments(
    OUT p_overdue_count INT,
    OUT p_overdue_amount DECIMAL(12,2)
)
BEGIN
    SELECT 
        COUNT(*) as overdue_count,
        SUM(balance) as overdue_amount
    FROM student_fee_accounts 
    WHERE payment_status IN ('unpaid', 'partial') 
      AND due_date < CURDATE() 
      AND status = 'active';
    
    SET p_overdue_count = (SELECT COUNT(*) FROM student_fee_accounts WHERE payment_status IN ('unpaid', 'partial') AND due_date < CURDATE() AND status = 'active');
    SET p_overdue_amount = (SELECT COALESCE(SUM(balance), 0) FROM student_fee_accounts WHERE payment_status IN ('unpaid', 'partial') AND due_date < CURDATE() AND status = 'active');
END //

DELIMITER ;

-- Success message
SELECT 'Finance management SQL created successfully!' as message;
SELECT 'All tables, views, and stored procedures for financial management are ready for use' as note;
