-- ============================================================
-- ISNM DIRECTOR FINANCE DASHBOARD SQL
-- Complete Financial Management System
-- ============================================================
--
-- PREREQUISITES:
--   1. sql/staffs/04_final_complete_staffs_database.sql
--   2. sql/students/01_create_students_database.sql
--   3. sql/students/bursar_system.sql
--
-- All student data resides in igangaschoolofl_students_db.
-- Financial tables (fee_accounts, budget_records) are in
-- igangaschoolofl_staffs_db. Cross-database views are
-- created in 05_all_departments_complete_dashboards.sql.

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
 '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', '+256701000005', 'Director Finance', 'Finance Department',
 (SELECT id FROM staff_roles WHERE role_name = 'Director Finance' LIMIT 1), 'Active', CURDATE(), NOW());

-- ============================================================
-- 2. FINANCE MANAGEMENT VIEWS
-- ============================================================

-- Finance Dashboard Summary View
-- Uses students table from students_db for active student count,
-- and billing tables from both databases for financial summaries.
-- Note: student_invoices => student_fee_assignments + payments in students_db
CREATE OR REPLACE VIEW finance_dashboard_summary AS
SELECT 
    -- Student Fee Summary (students are in the students database)
    (SELECT COUNT(*) FROM igangaschoolofl_students_db.students WHERE status = 'Active') as total_active_students,
    
    -- Invoice Summary (student_fee_assignments acts as invoice records)
    (SELECT COUNT(*) FROM igangaschoolofl_students_db.student_fee_assignments WHERE status IN ('Unpaid', 'Partially Paid', 'Overdue')) as pending_invoices,
    (SELECT COUNT(*) FROM igangaschoolofl_students_db.student_fee_assignments WHERE status = 'Paid') as paid_invoices,
    (SELECT SUM(assigned_amount) FROM igangaschoolofl_students_db.student_fee_assignments WHERE status IN ('Unpaid', 'Partially Paid', 'Overdue')) as pending_amount,
    (SELECT SUM(paid_amount) FROM igangaschoolofl_students_db.student_fee_assignments WHERE status = 'Paid') as collected_amount,
    
    -- Payment Summary
    (SELECT COUNT(*) FROM igangaschoolofl_students_db.payments WHERE status = 'Completed') as total_payments,
    (SELECT SUM(amount_received) FROM igangaschoolofl_students_db.payments WHERE status = 'Completed') as total_revenue,
    
    -- Budget Summary
    (SELECT COUNT(*) FROM igangaschoolofl_staffs_db.budget_records WHERE status = 'Active') as active_budgets,
    (SELECT SUM(allocated_amount) FROM igangaschoolofl_staffs_db.budget_records WHERE status = 'Active') as total_budget_allocated,
    (SELECT SUM(spent_amount) FROM igangaschoolofl_staffs_db.budget_records WHERE status = 'Active') as total_budget_spent,
    
    -- Sponsorship Summary
    (SELECT COUNT(*) FROM igangaschoolofl_students_db.sponsorships WHERE status = 'Active') as active_scholarships,
    (SELECT SUM(amount) FROM igangaschoolofl_students_db.sponsorships WHERE status = 'Active') as total_scholarship_value;

-- Student Fee Balance View
CREATE OR REPLACE VIEW finance_student_balances AS
SELECT 
    s.student_number,
    s.full_name,
    s.program,
    COALESCE(fs.fee_name, 'General Fee') as fee_type,
    sfa.assigned_amount as fee_balance,
    COALESCE(SUM(p.amount_received), 0) as amount_paid,
    (sfa.assigned_amount - COALESCE(SUM(p.amount_received), 0)) as outstanding_balance,
    sfa.status as fee_status
FROM igangaschoolofl_students_db.students s
JOIN igangaschoolofl_students_db.student_fee_assignments sfa ON s.id = sfa.student_id
LEFT JOIN igangaschoolofl_students_db.fee_structures fs ON sfa.fee_structure_id = fs.id
LEFT JOIN igangaschoolofl_students_db.payments p ON s.id = p.student_id AND p.status = 'Completed'
WHERE sfa.status IN ('Unpaid', 'Partially Paid', 'Overdue')
GROUP BY s.id, s.student_number, s.full_name, s.program, fs.fee_name, sfa.assigned_amount, sfa.status;

-- Revenue by Program View
CREATE OR REPLACE VIEW finance_revenue_by_program AS
SELECT 
    s.program,
    COUNT(DISTINCT s.id) as total_students,
    COUNT(DISTINCT sfa.id) as students_with_fees,
    SUM(sfa.assigned_amount) as total_assessed,
    COALESCE(SUM(CASE WHEN p.status = 'Completed' THEN p.amount_received ELSE 0 END), 0) as total_collected,
    (SUM(sfa.assigned_amount) - COALESCE(SUM(CASE WHEN p.status = 'Completed' THEN p.amount_received ELSE 0 END), 0)) as total_outstanding
FROM igangaschoolofl_students_db.students s
JOIN igangaschoolofl_students_db.student_fee_assignments sfa ON s.id = sfa.student_id
LEFT JOIN igangaschoolofl_students_db.payments p ON s.id = p.student_id
GROUP BY s.program;

-- ============================================================
-- 3. PROCEDURES FOR DIRECTOR FINANCE
-- ============================================================

DELIMITER //

-- Generate student fee statement
CREATE PROCEDURE finance_generate_statement(IN p_student_id INT)
BEGIN
    SELECT 
        s.student_number,
        s.full_name,
        s.program,
        COALESCE(fs.fee_name, 'General Fee') as fee_type,
        sfa.assigned_amount as assessed_amount,
        sfa.due_date,
        sfa.paid_amount,
        sfa.balance,
        sfa.status
    FROM igangaschoolofl_students_db.students s
    JOIN igangaschoolofl_students_db.student_fee_assignments sfa ON s.id = sfa.student_id
    LEFT JOIN igangaschoolofl_students_db.fee_structures fs ON sfa.fee_structure_id = fs.id
    WHERE s.id = p_student_id
    ORDER BY sfa.due_date;
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
    
    UPDATE igangaschoolofl_students_db.student_fee_assignments 
    SET paid_amount = paid_amount + p_amount,
        status = CASE 
            WHEN (assigned_amount - (paid_amount + p_amount)) <= 0 THEN 'Paid'
            ELSE 'Partially Paid'
        END
    WHERE student_id = p_student_id AND status IN ('Unpaid', 'Partially Paid');
END //

-- Get overdue accounts
CREATE PROCEDURE finance_get_overdue_accounts()
BEGIN
    SELECT 
        s.student_number,
        s.full_name,
        s.program,
        sfa.fee_type,
        sfa.assigned_amount as amount,
        sfa.due_date,
        sfa.balance,
        DATEDIFF(CURDATE(), sfa.due_date) as days_overdue
    FROM igangaschoolofl_students_db.students s
    JOIN igangaschoolofl_students_db.student_fee_assignments sfa ON s.id = sfa.student_id
    WHERE sfa.status = 'Overdue' AND sfa.due_date < CURDATE()
    ORDER BY days_overdue DESC;
END //

DELIMITER ;

COMMIT;
