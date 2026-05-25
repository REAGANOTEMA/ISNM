-- ============================================================
-- ISNM DIRECTOR FINANCE DASHBOARD SQL
-- Complete Financial Management System
-- ============================================================
-- 
-- ⚠️  CRITICAL PREREQUISITES - READ BEFORE EXECUTING ⚠️
-- 
-- BEFORE RUNNING THIS SCRIPT, YOU MUST EXECUTE THESE SCRIPTS IN ORDER:
-- 
--  1. sql/staffs/04_final_complete_staffs_database.sql
--     → Creates tables in igangaschoolofl_staffs_db database
--     → Creates: staff_roles, fee_accounts, universal_student_profiles, etc.
-- 
--  2. sql/bursar_system.sql
--     → Creates tables in igangaschoolofl_students_db database
--     → Creates: student_invoices, payments, sponsorships, budget_records, etc.
-- 
-- ❌ IF YOU SEE ERROR #1146 "TABLE DOESN'T EXIST", IT MEANS YOU SKIPPED THE PREREQUISITES
-- ❌ DO NOT CONTINUE UNTIL YOU HAVE RUN BOTH PREREQUISITE SCRIPTS
-- 
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
 '$2y$10$xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx', '+256701000005', 'Director Finance', 'Finance Department',
 (SELECT id FROM staff_roles WHERE role_name = 'Director Finance' LIMIT 1), 'Active', CURDATE(), NOW());

-- ============================================================
-- 2. FINANCE MANAGEMENT TABLES
-- Adding search and integration views
-- (Tables referenced are created by prerequisite scripts)
-- ============================================================

-- Finance Dashboard Summary View
-- ⚠️  WARNING: THE FOLLOWING VIEW REQUIRES TABLES FROM PREREQUISITE SCRIPTS
--    IF YOU GET "TABLE DOESN'T EXIST" ERRORS, YOU HAVE NOT RUN THE PREREQUISITES
--    SEE LINES 6-20 FOR REQUIRED PREREQUISITE SCRIPTS
CREATE OR REPLACE VIEW finance_dashboard_summary AS
SELECT 
    -- Student Fee Summary
    (SELECT COUNT(*) FROM igangaschoolofl_staffs_db.universal_student_profiles WHERE status = 'Active') as total_active_students,
    
    -- Invoice Summary
    (SELECT COUNT(*) FROM igangaschoolofl_students_db.student_invoices WHERE status = 'Pending') as pending_invoices,
    (SELECT COUNT(*) FROM igangaschoolofl_students_db.student_invoices WHERE status = 'Paid') as paid_invoices,
    (SELECT SUM(total_amount) FROM igangaschoolofl_students_db.student_invoices WHERE status = 'Pending') as pending_amount,
    (SELECT SUM(total_amount) FROM igangaschoolofl_students_db.student_invoices WHERE status = 'Paid') as collected_amount,
    
    -- Payment Summary
    (SELECT COUNT(*) FROM igangaschoolofl_students_db.payments WHERE status = 'Completed') as total_payments,
    (SELECT SUM(amount) FROM igangaschoolofl_students_db.payments WHERE status = 'Completed') as total_revenue,
    
    -- Budget Summary
    (SELECT COUNT(*) FROM igangaschoolofl_staffs_db.budget_records WHERE status = 'Active') as active_budgets,
    (SELECT SUM(total_budget) FROM igangaschoolofl_staffs_db.budget_records WHERE status = 'Active') as total_budget_allocated,
    (SELECT SUM(spent_amount) FROM igangaschoolofl_staffs_db.budget_records WHERE status = 'Active') as total_budget_spent,
    
    -- Scholarship Summary
    (SELECT COUNT(*) FROM igangaschoolofl_students_db.sponsorships WHERE status = 'Active') as active_scholarships,
    (SELECT SUM(amount) FROM igangaschoolofl_students_db.sponsorships WHERE status = 'Active') as total_scholarship_value;

-- Student Fee Balance View
CREATE OR REPLACE VIEW finance_student_balances AS
SELECT 
    sp.student_number,
    sp.full_name,
    sp.program,
    fa.amount as fee_balance,
    COALESCE(SUM(p.amount), 0) as amount_paid,
    (fa.amount - COALESCE(SUM(p.amount), 0)) as outstanding_balance,
    fa.status as fee_status
FROM igangaschoolofl_students_db.universal_student_profiles sp
JOIN igangaschoolofl_staffs_db.fee_accounts fa ON sp.id = fa.student_id
LEFT JOIN igangaschoolofl_students_db.payments p ON sp.id = p.student_id AND p.status = 'Completed'
WHERE fa.status IN ('Unpaid', 'Partially Paid', 'Overdue')
GROUP BY sp.id, sp.student_number, sp.full_name, sp.program, fa.amount, fa.status;

-- Revenue by Program View
CREATE OR REPLACE VIEW finance_revenue_by_program AS
SELECT 
    sp.program,
    COUNT(sp.id) as total_students,
    COUNT(fa.id) as students_with_fees,
    SUM(fa.total_amount) as total_assessed,
    COALESCE(SUM(CASE WHEN p.status = 'Completed' THEN p.amount ELSE 0 END), 0) as total_collected,
    (SUM(fa.total_amount) - COALESCE(SUM(CASE WHEN p.status = 'Completed' THEN p.amount ELSE 0 END), 0)) as total_outstanding
FROM igangaschoolofl_students_db.universal_student_profiles sp
JOIN igangaschoolofl_staffs_db.fee_accounts fa ON sp.id = fa.student_id
LEFT JOIN igangaschoolofl_students_db.payments p ON sp.id = p.student_id
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
    FROM igangaschoolofl_students_db.universal_student_profiles sp
    JOIN igangaschoolofl_staffs_db.fee_accounts fa ON sp.id = fa.student_id
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
    DECLARE v_payment_reference VARCHAR(50);
    SET v_payment_reference = CONCAT('PAY', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'));
    
    INSERT INTO igangaschoolofl_students_db.payments (
        payment_reference, student_id, amount_received, payment_method, status
    ) VALUES (
        v_payment_reference, p_student_id, p_amount, p_payment_method, 'Completed'
    );
    
    UPDATE igangaschoolofl_staffs_db.fee_accounts 
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