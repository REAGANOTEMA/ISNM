-- ISNM Database Compatibility Layer
-- Creates compatibility views and tables for cross-schema references
-- Run this AFTER all main schema files

USE igangaschoolofl_staffs_db;

-- Compatibility: fee_payments -> payments (cross-database)
CREATE OR REPLACE VIEW fee_payments AS
SELECT 
    id,
    student_id,
    invoice_id AS fee_account_id,
    amount_received AS amount_paid,
    payment_method,
    payment_reference AS receipt_number,
    status,
    payment_date,
    notes,
    received_by AS processed_by,
    created_at,
    updated_at
FROM igangaschoolofl_students_db.payments;

-- Compatibility: student_fee_accounts -> student_fee_assignments (cross-database)
CREATE OR REPLACE VIEW student_fee_accounts AS
SELECT 
    id,
    student_id,
    fee_structure_id,
    assigned_amount AS total_fees,
    paid_amount AS amount_paid,
    balance,
    status,
    due_date,
    NULL AS receipt_number,
    assigned_by AS created_by,
    created_at,
    updated_at
FROM igangaschoolofl_students_db.student_fee_assignments;

-- Compatibility: users VIEW (already exists in 04_final_complete_staffs_database.sql)
-- Ensure it includes password for auth compatibility
CREATE OR REPLACE VIEW users AS
SELECT 
    s.id,
    s.staff_id AS username,
    s.full_name AS user_name,
    s.email,
    s.password,
    s.position,
    s.department,
    s.role_id,
    sr.role_name,
    sr.role_level,
    sr.dashboard_path,
    s.status,
    s.phone,
    s.address,
    s.hire_date,
    s.last_login,
    s.login_attempts,
    s.locked_until,
    s.is_first_login,
    s.created_at,
    s.updated_at
FROM staff s
JOIN staff_roles sr ON s.role_id = sr.id;

-- Compatibility: staff_users -> staff (for any remaining references)
CREATE OR REPLACE VIEW staff_users AS
SELECT 
    s.id,
    s.email,
    s.password AS password_hash,
    s.full_name,
    s.phone,
    s.position AS role,
    s.department,
    s.status AS is_active,
    s.is_first_login AS is_verified,
    s.created_at,
    s.updated_at
FROM staff s;

-- Compatibility: roles -> staff_roles
CREATE OR REPLACE VIEW roles AS
SELECT 
    id,
    role_name AS name,
    role_description AS description,
    permissions,
    created_at,
    updated_at
FROM staff_roles;

-- Compatibility: hr_users (minimal view for auth fallback)
CREATE OR REPLACE VIEW hr_users AS
SELECT 
    s.id,
    s.email,
    s.password AS password_hash,
    s.full_name,
    s.phone,
    s.position,
    s.department,
    s.status,
    s.created_at,
    s.updated_at
FROM staff s
WHERE s.department = 'Human Resources' OR s.position LIKE '%HR%';

-- Add indexes to views' underlying tables if not present
ALTER TABLE payment_records ADD INDEX idx_student_id (student_id);
ALTER TABLE payment_records ADD INDEX idx_status (status);
ALTER TABLE payment_records ADD INDEX idx_receipt_number (receipt_number);
ALTER TABLE fee_accounts ADD INDEX idx_student_id (student_id);
ALTER TABLE fee_accounts ADD INDEX idx_status (status);
