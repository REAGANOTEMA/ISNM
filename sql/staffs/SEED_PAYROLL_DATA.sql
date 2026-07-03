-- ============================================================
-- PAYROLL SEED DATA — Iganga School of Nursing and Midwifery
-- Target database: igangaschoolofl_staffs_db
-- ============================================================

USE igangaschoolofl_staffs_db;

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. PAYROLL EMPLOYEES (one per active staff member)
-- ============================================================
INSERT IGNORE INTO payroll_employees (staff_id, bank_name, bank_account, bank_code, tax_identification, nssf_number, salary_type, salary_grade, basic_salary, hire_date, status)
SELECT s.id,
    'Stanbic Bank Uganda',
    CONCAT('1000', LPAD(s.id, 8, '0')),
    'SBICUGKA',
    CONCAT('TIN', LPAD(s.id, 6, '0')),
    CONCAT('NSSF', LPAD(s.id, 6, '0')),
    'monthly',
    CASE r.role_name
        WHEN 'Director General' THEN 'UG1'
        WHEN 'School Principal' THEN 'UG1'
        WHEN 'CEO' THEN 'UG1'
        WHEN 'Deputy Principal' THEN 'UG2'
        WHEN 'Director Academics' THEN 'UG2'
        WHEN 'Director Finance' THEN 'UG2'
        WHEN 'Director Admissions' THEN 'UG2'
        WHEN 'Director ICT' THEN 'UG2'
        WHEN 'Academic Registrar' THEN 'UG3'
        WHEN 'School Bursar' THEN 'UG3'
        WHEN 'HR Manager' THEN 'UG3'
        WHEN 'Head of Nursing' THEN 'UG3'
        WHEN 'Head of Midwifery' THEN 'UG3'
        WHEN 'Senior Lecturer' THEN 'UG4'
        WHEN 'Lecturer' THEN 'UG4'
        WHEN 'School Secretary' THEN 'UG5'
        WHEN 'School Librarian' THEN 'UG5'
        WHEN 'Computer Lab Manager' THEN 'UG5'
        WHEN 'Skills Lab Manager' THEN 'UG5'
        WHEN 'Matron' THEN 'UG5'
        WHEN 'Warden' THEN 'UG5'
        WHEN 'Storekeeper' THEN 'UG5'
        WHEN 'Guild President' THEN 'UG6'
        WHEN 'Security Officer' THEN 'UG6'
        WHEN 'Sickbay Nurse' THEN 'UG6'
        WHEN 'Driver' THEN 'UG6'
        ELSE 'UG5'
    END,
    CASE r.role_name
        WHEN 'Director General' THEN 5500000
        WHEN 'School Principal' THEN 5000000
        WHEN 'CEO' THEN 5000000
        WHEN 'Deputy Principal' THEN 4000000
        WHEN 'Director Academics' THEN 3800000
        WHEN 'Director Finance' THEN 3500000
        WHEN 'Director Admissions' THEN 3500000
        WHEN 'Director ICT' THEN 3500000
        WHEN 'Academic Registrar' THEN 3000000
        WHEN 'School Bursar' THEN 3000000
        WHEN 'HR Manager' THEN 3000000
        WHEN 'Head of Nursing' THEN 3000000
        WHEN 'Head of Midwifery' THEN 3000000
        WHEN 'Senior Lecturer' THEN 2500000
        WHEN 'Lecturer' THEN 2000000
        WHEN 'School Secretary' THEN 1500000
        WHEN 'School Librarian' THEN 1500000
        WHEN 'Computer Lab Manager' THEN 1500000
        WHEN 'Skills Lab Manager' THEN 1500000
        WHEN 'Matron' THEN 1200000
        WHEN 'Warden' THEN 1200000
        WHEN 'Storekeeper' THEN 1200000
        WHEN 'Guild President' THEN 1000000
        WHEN 'Security Officer' THEN 800000
        WHEN 'Sickbay Nurse' THEN 1500000
        WHEN 'Driver' THEN 800000
        ELSE 1200000
    END,
    '2022-01-15',
    'active'
FROM staff s
JOIN staff_roles r ON s.role_id = r.id
WHERE s.status = 'Active';

-- ============================================================
-- 2. PAYROLL PERIODS
-- ============================================================
INSERT IGNORE INTO payroll_periods (period_name, start_date, end_date, status) VALUES
('January 2025', '2025-01-01', '2025-01-31', 'Open'),
('February 2025', '2025-02-01', '2025-02-28', 'Open'),
('March 2025', '2025-03-01', '2025-03-31', 'Open'),
('April 2025', '2025-04-01', '2025-04-30', 'Open'),
('May 2025', '2025-05-01', '2025-05-31', 'Open'),
('June 2025', '2025-06-01', '2025-06-30', 'Open'),
('July 2025', '2025-07-01', '2025-07-31', 'Open'),
('August 2025', '2025-08-01', '2025-08-31', 'Open'),
('September 2025', '2025-09-01', '2025-09-30', 'Open'),
('October 2025', '2025-10-01', '2025-10-31', 'Open'),
('November 2025', '2025-11-01', '2025-11-30', 'Open'),
('December 2025', '2025-12-01', '2025-12-31', 'Open');

-- ============================================================
-- 3. PAYROLL RUN (January 2025 — processed)
-- ============================================================
INSERT IGNORE INTO payroll_runs (total_gross, total_deductions, total_net, period, start_date, end_date, status, description)
SELECT
    (SELECT SUM(basic_salary) FROM payroll_employees WHERE status='active'),
    (SELECT ROUND(SUM(basic_salary) * 0.22, 2) FROM payroll_employees WHERE status='active'),
    (SELECT ROUND(SUM(basic_salary) * 0.78, 2) FROM payroll_employees WHERE status='active'),
    'January 2025',
    '2025-01-01',
    '2025-01-31',
    'approved',
    'January 2025 Salary Payment';

-- ============================================================
-- 4. ALLOWANCES (sample for each employee)
-- ============================================================
INSERT IGNORE INTO payroll_allowances (staff_id, allowance_type, amount, month, is_recurring, created_by)
SELECT pe.staff_id,
    ELT(FLOOR(RAND()*3)+1, 'Housing Allowance', 'Transport Allowance', 'Medical Allowance'),
    ROUND(pe.basic_salary * ELT(FLOOR(RAND()*3)+1, 0.15, 0.10, 0.05), 0),
    'January',
    1,
    1
FROM payroll_employees pe WHERE pe.status = 'active';

-- ============================================================
-- 5. DEDUCTIONS (NSSF + PAYE for each employee)
-- ============================================================
INSERT IGNORE INTO payroll_deductions (staff_id, deduction_type, amount, month, is_recurring, created_by)
SELECT pe.staff_id, 'NSSF', ROUND(pe.basic_salary * 0.10, 0), 'January', 1, 1
FROM payroll_employees pe WHERE pe.status = 'active'
UNION ALL
SELECT pe.staff_id, 'PAYE', ROUND(pe.basic_salary * 0.12, 0), 'January', 1, 1
FROM payroll_employees pe WHERE pe.status = 'active';

-- ============================================================
-- 6. PAYROLL SETTINGS
-- ============================================================
INSERT IGNORE INTO payroll_settings (setting_key, setting_value) VALUES
('nssf_rate', '10'),
('paye_rate', '12'),
('employer_nssf_rate', '10'),
('currency', 'UGX'),
('institution_name', 'Iganga School of Nursing and Midwifery'),
('payroll_start_day', '1'),
('payroll_end_day', '28'),
('payment_day', '28'),
('overtime_rate', '1.5'),
('tax_threshold', '235000');

-- ============================================================
-- 7. LEAVE TYPES (ensure populated before leave_balances)
-- ============================================================
INSERT IGNORE INTO leave_types (type_name, leave_type_name, days_per_year, description, is_active) VALUES
('Annual', 'Annual Leave', 28, 'Annual paid leave', 1),
('Sick', 'Sick Leave', 15, 'Paid sick leave with medical certificate', 1),
('Study', 'Study Leave', 30, 'Leave for examinations and academic purposes', 1),
('Maternity', 'Maternity Leave', 90, 'Paid maternity leave', 1),
('Paternity', 'Paternity Leave', 14, 'Paid paternity leave', 1),
('Compassionate', 'Compassionate Leave', 10, 'Leave for bereavement or family emergencies', 1),
('Unpaid', 'Unpaid Leave', 30, 'Leave without pay', 1);

-- ============================================================
-- 8. LEAVE BALANCES (for each employee)
-- ============================================================
INSERT IGNORE INTO leave_balances (staff_id, leave_type_id, year, total_days, used_days, remaining_days)
SELECT pe.staff_id, lt.id, 2025, lt.days_per_year, 0, lt.days_per_year
FROM payroll_employees pe
CROSS JOIN leave_types lt
WHERE pe.status = 'active' AND lt.is_active = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- SUMMARY
-- ============================================================
SELECT 'PAYROLL SEED DATA COMPLETE' as Status,
    (SELECT COUNT(*) FROM payroll_employees) as PayrollEmployees,
    (SELECT COUNT(*) FROM payroll_periods) as Periods,
    (SELECT COUNT(*) FROM payroll_runs) as Runs,
    (SELECT COUNT(*) FROM payroll_allowances) as Allowances,
    (SELECT COUNT(*) FROM payroll_deductions) as Deductions,
    (SELECT COUNT(*) FROM leave_types) as LeaveTypes,
    (SELECT COUNT(*) FROM leave_balances) as LeaveBalances,
    (SELECT COUNT(*) FROM payroll_settings) as Settings;
