-- ISNM School Management System - Staff Management Queries
-- SQL queries for managing staff accounts and login operations

USE isnm_db;

-- ========================================
-- STAFF LOGIN VERIFICATION QUERIES
-- ========================================

-- Query to verify staff login (email + password verification)
-- This is the core query used in auth-service.php
SELECT * FROM users 
WHERE email = ? AND 
      type = 'staff' AND 
      status = 'active';

-- Check if staff account is locked
SELECT locked_until, login_attempts 
FROM users 
WHERE email = ? AND 
      type = 'staff' AND 
      locked_until > NOW();

-- Record failed staff login attempt
UPDATE users 
SET login_attempts = login_attempts + 1,
    locked_until = CASE 
        WHEN login_attempts >= 4 THEN DATE_ADD(NOW(), INTERVAL 15 MINUTE)
        ELSE NULL 
    END
WHERE email = ? AND type = 'staff';

-- Reset staff login attempts on successful login
UPDATE users 
SET login_attempts = 0, 
    locked_until = NULL, 
    last_login = NOW()
WHERE id = ? AND type = 'staff';

-- ========================================
-- STAFF ACCOUNT CREATION QUERIES
-- ========================================

-- Check if email already exists
SELECT COUNT(*) as count 
FROM users 
WHERE email = ? AND type = 'staff';

-- Create new staff account
INSERT INTO users (
    full_name, 
    email, 
    phone, 
    password, 
    role, 
    type, 
    status
) VALUES (
    ?, -- full_name
    ?, -- email
    ?, -- phone
    ?, -- password (hashed)
    ?, -- role
    'staff', -- type
    'active' -- status
);

-- Update staff password
UPDATE users 
SET password = ?,
    updated_at = NOW()
WHERE id = ? AND type = 'staff';

-- ========================================
-- STAFF MANAGEMENT REPORTS
-- ========================================

-- Get all active staff
SELECT 
    id,
    email,
    full_name,
    phone,
    role,
    created_at,
    last_login,
    login_attempts
FROM users 
WHERE type = 'staff' AND status = 'active'
ORDER BY role, full_name;

-- Get staff by role category
SELECT 
    id,
    email,
    full_name,
    phone,
    role,
    CASE 
        WHEN role LIKE '%Director%' THEN 'Directors'
        WHEN role LIKE '%Principal%' THEN 'Management'
        WHEN role LIKE '%Registrar%' THEN 'Management'
        WHEN role LIKE '%Secretary%' THEN 'Administration'
        WHEN role LIKE '%Bursar%' OR role LIKE '%Accountant%' THEN 'Finance'
        WHEN role LIKE '%Lecturer%' OR role LIKE '%Senior%' THEN 'Academic'
        WHEN role LIKE '%Head%' THEN 'Academic'
        WHEN role LIKE '%Librarian%' THEN 'Support'
        WHEN role LIKE '%HR%' THEN 'Support'
        WHEN role LIKE '%Matron%' OR role LIKE '%Warden%' THEN 'Student Services'
        WHEN role LIKE '%Lab%' THEN 'Support'
        WHEN role LIKE '%Driver%' THEN 'Support'
        WHEN role LIKE '%Security%' THEN 'Support'
        ELSE 'Other'
    END as role_category,
    created_at,
    last_login
FROM users 
WHERE type = 'staff' AND status = 'active'
ORDER BY role_category, role, full_name;

-- Get staff login statistics
SELECT 
    COUNT(*) as total_staff,
    COUNT(CASE WHEN last_login IS NOT NULL THEN 1 END) as staff_who_logged_in,
    COUNT(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as active_this_week,
    COUNT(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as active_this_month
FROM users 
WHERE type = 'staff' AND status = 'active';

-- Get staff with failed login attempts
SELECT 
    email,
    full_name,
    role,
    login_attempts,
    locked_until,
    CASE 
        WHEN locked_until > NOW() THEN 'Locked'
        ELSE 'Active'
    END as account_status
FROM users 
WHERE type = 'staff' AND login_attempts > 0
ORDER BY login_attempts DESC, locked_until DESC;

-- ========================================
-- STAFF SEARCH AND FILTER QUERIES
-- ========================================

-- Search staff by name
SELECT 
    id,
    email,
    full_name,
    phone,
    role,
    created_at,
    last_login
FROM users 
WHERE type = 'staff' 
  AND status = 'active' 
  AND full_name LIKE ?
ORDER BY full_name;

-- Search staff by email
SELECT 
    id,
    email,
    full_name,
    phone,
    role,
    created_at,
    last_login
FROM users 
WHERE type = 'staff' 
  AND status = 'active' 
  AND email LIKE ?
ORDER BY email;

-- Search staff by role
SELECT 
    id,
    email,
    full_name,
    phone,
    role,
    created_at,
    last_login
FROM users 
WHERE type = 'staff' 
  AND status = 'active' 
  AND role LIKE ?
ORDER BY full_name;

-- Get staff by creation date range
SELECT 
    id,
    email,
    full_name,
    phone,
    role,
    created_at,
    last_login
FROM users 
WHERE type = 'staff' 
  AND status = 'active' 
  AND created_at BETWEEN ? AND ?
ORDER BY created_at DESC;

-- ========================================
-- STAFF ACCOUNT UPDATES
-- ========================================

-- Update staff information
UPDATE users 
SET full_name = ?, 
    phone = ?,
    role = ?,
    updated_at = NOW()
WHERE id = ? AND type = 'staff';

-- Update staff email (with uniqueness check)
UPDATE users 
SET email = ?,
    updated_at = NOW()
WHERE id = ? AND type = 'staff'
AND ? NOT IN (SELECT email FROM users WHERE type = 'staff' AND id != ?);

-- Deactivate staff account
UPDATE users 
SET status = 'inactive',
    updated_at = NOW()
WHERE id = ? AND type = 'staff';

-- Suspend staff account
UPDATE users 
SET status = 'suspended',
    updated_at = NOW()
WHERE id = ? AND type = 'staff';

-- Reactivate staff account
UPDATE users 
SET status = 'active',
    login_attempts = 0,
    locked_until = NULL,
    updated_at = NOW()
WHERE id = ? AND type = 'staff';

-- ========================================
-- BULK STAFF OPERATIONS
-- ========================================

-- Bulk create staff (example for multiple insert)
INSERT INTO users (
    full_name, 
    email, 
    phone, 
    password, 
    role, 
    type, 
    status
) VALUES
    ('Staff Name 1', 'staff1@isnm.ac.ug', '0772123456', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lecturers', 'staff', 'active'),
    ('Staff Name 2', 'staff2@isnm.ac.ug', '0772123457', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lecturers', 'staff', 'active'),
    ('Staff Name 3', 'staff3@isnm.ac.ug', '0772123458', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lecturers', 'staff', 'active');

-- Update multiple staff status
UPDATE users 
SET status = ?
WHERE type = 'staff' AND id IN (?, ?, ?);

-- ========================================
-- STAFF LOGIN AUDIT
-- ========================================

-- Get staff login history
SELECT 
    la.user_identifier,
    la.attempt_time,
    la.ip_address,
    la.success,
    la.failure_reason,
    u.full_name,
    u.email,
    u.role
FROM login_attempts la
JOIN users u ON (la.user_identifier = u.email)
WHERE la.user_type = 'staff'
ORDER BY la.attempt_time DESC;

-- Get recent staff login attempts (last 24 hours)
SELECT 
    la.user_identifier,
    la.attempt_time,
    la.ip_address,
    la.success,
    la.failure_reason,
    u.full_name,
    u.role
FROM login_attempts la
JOIN users u ON (la.user_identifier = u.email)
WHERE la.user_type = 'staff' 
  AND la.attempt_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY la.attempt_time DESC;

-- Get staff login statistics by role
SELECT 
    u.role,
    COUNT(*) as total_logins,
    COUNT(CASE WHEN la.success = TRUE THEN 1 END) as successful_logins,
    COUNT(CASE WHEN la.success = FALSE THEN 1 END) as failed_logins,
    MAX(la.attempt_time) as last_login_time
FROM users u
LEFT JOIN login_attempts la ON (u.email = la.user_identifier AND la.user_type = 'staff')
WHERE u.type = 'staff' AND u.status = 'active'
GROUP BY u.role
ORDER BY total_logins DESC;

-- ========================================
-- STAFF PERMISSION CHECKS
-- ========================================

-- Check if staff can create students
SELECT 
    id,
    full_name,
    role,
    CASE 
        WHEN LOWER(role) IN ('secretary', 'principal', 'accountant', 'school secretary', 'school principal', 'school bursar') THEN TRUE
        WHEN LOWER(role) LIKE '%director%' THEN TRUE
        ELSE FALSE
    END as can_create_students
FROM users 
WHERE type = 'staff' AND status = 'active' AND id = ?;

-- Check if staff can create other staff accounts
SELECT 
    id,
    full_name,
    role,
    CASE 
        WHEN LOWER(role) LIKE '%director%' THEN TRUE
        WHEN LOWER(role) LIKE '%admin%' THEN TRUE
        ELSE FALSE
    END as can_create_staff
FROM users 
WHERE type = 'staff' AND status = 'active' AND id = ?;

-- ========================================
-- STORED PROCEDURES FOR STAFF MANAGEMENT
-- ========================================

DELIMITER //

-- Procedure to create staff account with validation
CREATE PROCEDURE IF NOT EXISTS create_staff_account(
    IN p_full_name VARCHAR(255),
    IN p_email VARCHAR(255),
    IN p_phone VARCHAR(20),
    IN p_password VARCHAR(255),
    IN p_role VARCHAR(50),
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN
)
BEGIN
    DECLARE v_count INT DEFAULT 0;
    DECLARE v_hashed_password VARCHAR(255);
    
    -- Check if email already exists
    SELECT COUNT(*) INTO v_count
    FROM users 
    WHERE email = p_email AND type = 'staff';
    
    IF v_count > 0 THEN
        SET p_result = 'Email already exists';
        SET p_success = FALSE;
    ELSE
        -- Hash password
        SET v_hashed_password = PASSWORD(p_password);
        
        -- Insert new staff
        INSERT INTO users (
            full_name, email, phone, password, role, type, status
        ) VALUES (
            p_full_name, p_email, p_phone, v_hashed_password, p_role, 'staff', 'active'
        );
        
        SET p_result = 'Staff account created successfully';
        SET p_success = TRUE;
    END IF;
END //

-- Procedure to authenticate staff
CREATE PROCEDURE IF NOT EXISTS authenticate_staff(
    IN p_email VARCHAR(255),
    IN p_password VARCHAR(255),
    IN p_ip_address VARCHAR(45),
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN,
    OUT p_user_id INT
)
BEGIN
    DECLARE v_count INT DEFAULT 0;
    DECLARE v_user_id INT DEFAULT NULL;
    DECLARE v_hashed_password VARCHAR(255);
    DECLARE v_locked_until TIMESTAMP NULL;
    
    -- Check if account is locked
    SELECT locked_until INTO v_locked_until
    FROM users 
    WHERE email = p_email AND type = 'staff';
    
    IF v_locked_until IS NOT NULL AND v_locked_until > NOW() THEN
        -- Log failed attempt
        CALL log_login_attempt(p_email, 'staff', p_ip_address, NULL, FALSE, 'Account locked');
        SET p_result = 'Account temporarily locked due to multiple failed attempts';
        SET p_success = FALSE;
        SET p_user_id = NULL;
    ELSE
        -- Get user and verify password
        SELECT id, password INTO v_user_id, v_hashed_password
        FROM users 
        WHERE email = p_email 
          AND type = 'staff' 
          AND status = 'active';
        
        IF v_user_id IS NOT NULL AND PASSWORD(p_password) = v_hashed_password THEN
            -- Success - reset attempts and update last login
            UPDATE users 
            SET login_attempts = 0, 
                locked_until = NULL, 
                last_login = NOW()
            WHERE id = v_user_id;
            
            -- Log successful attempt
            CALL log_login_attempt(p_email, 'staff', p_ip_address, NULL, TRUE, NULL);
            
            SET p_result = 'Login successful';
            SET p_success = TRUE;
            SET p_user_id = v_user_id;
        ELSE
            -- Failed - increment attempts
            CALL increment_login_attempts(p_email);
            
            -- Log failed attempt
            CALL log_login_attempt(p_email, 'staff', p_ip_address, NULL, FALSE, 'Invalid credentials');
            
            SET p_result = 'Invalid email or password';
            SET p_success = FALSE;
            SET p_user_id = NULL;
        END IF;
    END IF;
END //

-- Procedure to change staff password
CREATE PROCEDURE IF NOT EXISTS change_staff_password(
    IN p_user_id INT,
    IN p_current_password VARCHAR(255),
    IN p_new_password VARCHAR(255),
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN
)
BEGIN
    DECLARE v_current_hash VARCHAR(255);
    DECLARE v_new_hash VARCHAR(255);
    
    -- Get current password
    SELECT password INTO v_current_hash
    FROM users 
    WHERE id = p_user_id AND type = 'staff' AND status = 'active';
    
    IF v_current_hash IS NOT NULL AND PASSWORD(p_current_password) = v_current_hash THEN
        -- Hash new password
        SET v_new_hash = PASSWORD(p_new_password);
        
        -- Update password
        UPDATE users 
        SET password = v_new_hash,
            updated_at = NOW()
        WHERE id = p_user_id AND type = 'staff';
        
        SET p_result = 'Password changed successfully';
        SET p_success = TRUE;
    ELSE
        SET p_result = 'Current password is incorrect';
        SET p_success = FALSE;
    END IF;
END //

DELIMITER ;

-- Success message
SELECT 'Staff management queries created successfully!' as message;
