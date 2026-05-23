-- ============================================================
-- ISNM DIRECTOR FINANCE DASHBOARD SQL
-- Complete Financial Management System
-- ============================================================

USE igangaschoolofl_staffs_db;

-- ============================================================
-- 1. DIRECTOR FINANCE USER ACCOUNTS
-- ============================================================

INSERT IGNORE INTO staff (
    staff_id, 
    full_name, 
    email, 
    password, 
    phone, 
    position, 
    department, 
    role_id, 
    status, 
    hire_date,
    created_at
) VALUES
('DF001', 'Director Finance', 'director_finance@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$director_finance@isnmHashedPassword', '+256701000005', 'Director Finance', 'Finance Department',
 (SELECT id FROM staff_roles WHERE role_name = 'Director Finance' LIMIT 1), 'Active', CURDATE(), NOW());

-- ============================================================
-- 2. FINANCE MANAGEMENT TABLES
-- Using existing tables from bursar_system.sql and 04_final_complete_staffs_database.sql
-- Adding search and integration views
-- ============================================================

-- Finance Dashboard Summary View
CREATE OR REPLACE VIEW finance_dashboard_summary AS
SELECT 
    -- Student Fee Summary
    (SELECT COUNT(*) FROM universal_student_profiles WHERE status = 'Active') as total_active_students,
    
    -- Invoice Summary
    (SELECT COUNT(*) FROM invoice_records WHERE status = 'Pending') as pending_invoices,
    (SELECT COUNT(*) FROM invoice_records WHERE status = 'Paid') as paid_invoices,
    (SELECT SUM(total_amount) FROM invoice_records WHERE status = 'Pending') as pending_amount,
    (SELECT SUM(total_amount) FROM invoice_records WHERE status = 'Paid') as collected_amount,
    
    -- Payment Summary
    (SELECT COUNT(*) FROM payment_records WHERE status = 'Completed') as total_payments,
    (SELECT SUM(amount) FROM payment_records WHERE status = 'Completed') as total_revenue,
    
    -- Budget Summary
    (SELECT COUNT(*) FROM budget_records WHERE status = 'Active') as active_budgets,
    (SELECT SUM(total_budget) FROM budget_records WHERE status = 'Active') as total_budget_allocated,
    (SELECT SUM(spent_amount) FROM budget_records WHERE status = 'Active') as total_budget_spent,
    
    -- Scholarship Summary
    (SELECT COUNT(*) FROM sponsorships WHERE status = 'Active') as active_scholarships,
    (SELECT SUM(amount) FROM sponsorships WHERE status = 'Active') as total_scholarship_value;

-- Student Fee Balance View
CREATE OR REPLACE VIEW finance_student_balances AS
SELECT 
    sp.student_number,
    sp.full_name,
    sp.program,
    fa.total_amount as fee_balance,
    COALESCE(SUM(p.amount), 0) as amount_paid,
    (fa.total_amount - COALESCE(SUM(p.amount), 0)) as outstanding_balance,
    fa.status as fee_status
FROM universal_student_profiles sp
JOIN fee_accounts fa ON sp.id = fa.student_id
LEFT JOIN payments p ON sp.id = p.student_id AND p.status = 'Completed'
WHERE fa.status IN ('Unpaid', 'Partially Paid', 'Overdue')
GROUP BY sp.id, sp.student_number, sp.full_name, sp.program, fa.total_amount, fa.status;

-- Revenue by Program View
CREATE OR REPLACE VIEW finance_revenue_by_program AS
SELECT 
    sp.program,
    COUNT(sp.id) as total_students,
    COUNT(fa.id) as students_with_fees,
    SUM(fa.total_amount) as total_assessed,
    COALESCE(SUM(CASE WHEN p.status = 'Completed' THEN p.amount ELSE 0 END), 0) as total_collected,
    (SUM(fa.total_amount) - COALESCE(SUM(CASE WHEN p.status = 'Completed' THEN p.amount ELSE 0 END), 0)) as total_outstanding
FROM universal_student_profiles sp
JOIN fee_accounts fa ON sp.id = fa.student_id
LEFT JOIN payments p ON sp.id = p.student_id
GROUP BY sp.program;

-- ============================================================
-- 3. PROCEDURES FOR DIRECTOR FINANCE
-- ============================================================

DELIMITER //

-- Generate student fee statement
CREATE PROCEDURE finance_generate_statement(IN p_student_id INT)
BEGIN
    SELECT 
        sp.student_number,
        sp.full_name,
        sp.program,
        fa.fee_type,
        fa.amount as assessed_amount,
        fa.due_date,
        fa.paid_amount,
        fa.balance,
        fa.status
    FROM universal_student_profiles sp
    JOIN fee_accounts fa ON sp.id = fa.student_id
    WHERE sp.id = p_student_id
    ORDER BY fa.due_date;
END //

-- Record payment
CREATE PROCEDURE finance_record_payment(
    IN p_student_id INT,
    IN p_amount DECIMAL(15,2),
    IN p_payment_method VARCHAR(50),
    IN p_reference VARCHAR(100),
    IN p_processed_by INT
)
BEGIN
    DECLARE v_payment_number VARCHAR(50);
    SET v_payment_number = CONCAT('PAY', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'));
    
    INSERT INTO payment_records (
        payment_number, student_id, amount, payment_method, payment_reference, processed_by
    ) VALUES (
        v_payment_number, p_student_id, p_amount, p_payment_method, p_reference, p_processed_by
    );
    
    UPDATE fee_accounts 
    SET paid_amount = paid_amount + p_amount,
        balance = balance - p_amount,
        status = CASE 
            WHEN balance - p_amount <= 0 THEN 'Paid'
            ELSE 'Partially Paid'
        END
    WHERE student_id = p_student_id AND status IN ('Unpaid', 'Partially Paid');
END //

-- Get overdue accounts
CREATE PROCEDURE finance_get_overdue_accounts()
BEGIN
    SELECT 
        sp.student_number,
        sp.full_name,
        sp.program,
        fa.fee_type,
        fa.amount,
        fa.due_date,
        fa.balance,
        DATEDIFF(CURDATE(), fa.due_date) as days_overdue
    FROM universal_student_profiles sp
    JOIN fee_accounts fa ON sp.id = fa.student_id
    WHERE fa.status = 'Overdue' AND fa.due_date < CURDATE()
    ORDER BY days_overdue DESC;
END //

DELIMITER ;

COMMIT;