-- ============================================================
-- PAYROLL SEED DATA — Iganga School of Nursing and Midwifery
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. PAYROLL EMPLOYEES (one per active staff member)
-- ============================================================
INSERT IGNORE INTO payroll_employees (staff_id, payroll_number, employment_type, tax_identification, nssf_number, salary_type, salary_grade, basic_salary, monthly_salary, hire_date, status, payroll_status)
SELECT s.id,
    CONCAT('PAY', LPAD(s.id, 4, '0')),
    'full_time',
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
    'active',
    'active'
FROM staff s
JOIN staff_roles r ON s.role_id = r.id
WHERE s.status = 'Active';

-- ============================================================
-- 2. PAYROLL PERIODS
-- ============================================================
INSERT IGNORE INTO payroll_periods (period_name, month, year, period_code, frequency, start_date, end_date, payment_date, status) VALUES
('January 2025', 'January', 2025, '2025-01', 'monthly', '2025-01-01', '2025-01-31', '2025-01-28', 'Open'),
('February 2025', 'February', 2025, '2025-02', 'monthly', '2025-02-01', '2025-02-28', '2025-02-27', 'Open'),
('March 2025', 'March', 2025, '2025-03', 'monthly', '2025-03-01', '2025-03-31', '2025-03-28', 'Open'),
('April 2025', 'April', 2025, '2025-04', 'monthly', '2025-04-01', '2025-04-30', '2025-04-29', 'Open'),
('May 2025', 'May', 2025, '2025-05', 'monthly', '2025-05-01', '2025-05-31', '2025-05-29', 'Open'),
('June 2025', 'June', 2025, '2025-06', 'monthly', '2025-06-01', '2025-06-30', '2025-06-27', 'Open'),
('July 2025', 'July', 2025, '2025-07', 'monthly', '2025-07-01', '2025-07-31', '2025-07-29', 'Open'),
('August 2025', 'August', 2025, '2025-08', 'monthly', '2025-08-01', '2025-08-31', '2025-08-28', 'Open'),
('September 2025', 'September', 2025, '2025-09', 'monthly', '2025-09-01', '2025-09-30', '2025-09-29', 'Open'),
('October 2025', 'October', 2025, '2025-10', 'monthly', '2025-10-01', '2025-10-31', '2025-10-29', 'Open'),
('November 2025', 'November', 2025, '2025-11', 'monthly', '2025-11-01', '2025-11-30', '2025-11-27', 'Open'),
('December 2025', 'December', 2025, '2025-12', 'monthly', '2025-12-01', '2025-12-31', '2025-12-19', 'Open');

-- ============================================================
-- 3. PAYROLL RUN (January 2025 — processed)
-- ============================================================
INSERT IGNORE INTO payroll_runs (payroll_period_id, run_number, run_type, processed_at, total_employees, total_gross, total_tax, total_nssf, total_deductions, total_net, period, start_date, end_date, status, description)
SELECT
    pp.id,
    'RUN-2025-01',
    'normal',
    NOW(),
    (SELECT COUNT(*) FROM payroll_employees WHERE status='active'),
    (SELECT SUM(basic_salary) FROM payroll_employees WHERE status='active'),
    (SELECT ROUND(SUM(basic_salary) * 0.12, 2) FROM payroll_employees WHERE status='active'),
    (SELECT ROUND(SUM(basic_salary) * 0.10, 2) FROM payroll_employees WHERE status='active'),
    (SELECT ROUND(SUM(basic_salary) * 0.22, 2) FROM payroll_employees WHERE status='active'),
    (SELECT ROUND(SUM(basic_salary) * 0.78, 2) FROM payroll_employees WHERE status='active'),
    'January 2025',
    '2025-01-01',
    '2025-01-31',
    'approved',
    'January 2025 Salary Payment'
FROM payroll_periods pp
WHERE pp.period_code = '2025-01'
LIMIT 1;

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
INSERT IGNORE INTO payroll_settings (id, setting_key, setting_value, updated_by) VALUES
(1, 'nssf_rate', '10', 1),
(2, 'paye_rate', '12', 1),
(3, 'employer_nssf_rate', '10', 1),
(4, 'currency', 'UGX', 1),
(5, 'institution_name', 'Iganga School of Nursing and Midwifery', 1),
(6, 'payroll_start_day', '1', 1),
(7, 'payroll_end_day', '28', 1),
(8, 'payment_day', '28', 1),
(9, 'overtime_rate', '1.5', 1),
(10, 'tax_threshold', '235000', 1);

-- ============================================================
-- 7. LEAVE BALANCES (for each employee)
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
