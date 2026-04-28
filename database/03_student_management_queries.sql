-- ISNM School Management System - Student Management Queries
-- SQL queries for managing student accounts and login operations

USE isnm_school;

-- ========================================
-- STUDENT LOGIN VERIFICATION QUERIES
-- ========================================

-- Query to verify student login (3-field verification)
-- This is the core query used in auth-service.php
SELECT * FROM users 
WHERE index_number = ? AND 
      full_name = ? AND 
      phone = ? AND 
      type = 'student' AND 
      status = 'active';

-- Check if student account is locked
SELECT locked_until, login_attempts 
FROM users 
WHERE index_number = ? AND 
      type = 'student' AND 
      locked_until > NOW();

-- Record failed student login attempt
UPDATE users 
SET login_attempts = login_attempts + 1,
    locked_until = CASE 
        WHEN login_attempts >= 4 THEN DATE_ADD(NOW(), INTERVAL 15 MINUTE)
        ELSE NULL 
    END
WHERE index_number = ? AND type = 'student';

-- Reset student login attempts on successful login
UPDATE users 
SET login_attempts = 0, 
    locked_until = NULL, 
    last_login = NOW()
WHERE id = ? AND type = 'student';

-- ========================================
-- STUDENT ACCOUNT CREATION QUERIES
-- ========================================

-- Check if index number already exists
SELECT COUNT(*) as count 
FROM users 
WHERE index_number = ? AND type = 'student';

-- Create new student account
INSERT INTO users (
    index_number, 
    full_name, 
    phone, 
    role, 
    type, 
    status
) VALUES (
    ?, -- index_number
    ?, -- full_name
    ?, -- phone
    'student', -- role
    'student', -- type
    'active' -- status
);

-- ========================================
-- STUDENT MANAGEMENT REPORTS
-- ========================================

-- Get all active students
SELECT 
    id,
    index_number,
    full_name,
    phone,
    created_at,
    last_login,
    login_attempts
FROM users 
WHERE type = 'student' AND status = 'active'
ORDER BY created_at DESC;

-- Get students by program type (from index_number)
SELECT 
    id,
    index_number,
    full_name,
    phone,
    CASE 
        WHEN index_number LIKE '%/CM/%' THEN 'Certificate in Midwifery'
        WHEN index_number LIKE '%/CN/%' THEN 'Certificate in Nursing'
        WHEN index_number LIKE '%/DMORDN/%' THEN 'Diploma in Midwifery'
        ELSE 'Unknown Program'
    END as program,
    created_at,
    last_login
FROM users 
WHERE type = 'student' AND status = 'active'
ORDER BY program, full_name;

-- Get student login statistics
SELECT 
    COUNT(*) as total_students,
    COUNT(CASE WHEN last_login IS NOT NULL THEN 1 END) as students_who_logged_in,
    COUNT(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as active_this_week,
    COUNT(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as active_this_month
FROM users 
WHERE type = 'student' AND status = 'active';

-- Get students with failed login attempts
SELECT 
    index_number,
    full_name,
    phone,
    login_attempts,
    locked_until,
    CASE 
        WHEN locked_until > NOW() THEN 'Locked'
        ELSE 'Active'
    END as account_status
FROM users 
WHERE type = 'student' AND login_attempts > 0
ORDER BY login_attempts DESC, locked_until DESC;

-- ========================================
-- STUDENT SEARCH AND FILTER QUERIES
-- ========================================

-- Search students by name
SELECT 
    id,
    index_number,
    full_name,
    phone,
    created_at,
    last_login
FROM users 
WHERE type = 'student' 
  AND status = 'active' 
  AND full_name LIKE ?
ORDER BY full_name;

-- Search students by index number
SELECT 
    id,
    index_number,
    full_name,
    phone,
    created_at,
    last_login
FROM users 
WHERE type = 'student' 
  AND status = 'active' 
  AND index_number LIKE ?
ORDER BY index_number;

-- Get students by creation date range
SELECT 
    id,
    index_number,
    full_name,
    phone,
    created_at,
    last_login
FROM users 
WHERE type = 'student' 
  AND status = 'active' 
  AND created_at BETWEEN ? AND ?
ORDER BY created_at DESC;

-- ========================================
-- STUDENT ACCOUNT UPDATES
-- ========================================

-- Update student information
UPDATE users 
SET full_name = ?, 
    phone = ?,
    updated_at = NOW()
WHERE id = ? AND type = 'student';

-- Deactivate student account
UPDATE users 
SET status = 'inactive',
    updated_at = NOW()
WHERE id = ? AND type = 'student';

-- Suspend student account
UPDATE users 
SET status = 'suspended',
    updated_at = NOW()
WHERE id = ? AND type = 'student';

-- Reactivate student account
UPDATE users 
SET status = 'active',
    login_attempts = 0,
    locked_until = NULL,
    updated_at = NOW()
WHERE id = ? AND type = 'student';

-- ========================================
-- BULK STUDENT OPERATIONS
-- ========================================

-- Bulk create students (example for multiple insert)
INSERT INTO users (
    index_number, 
    full_name, 
    phone, 
    role, 
    type, 
    status
) VALUES
    ('U001/CM/001/24', 'Student Name 1', '0772123456', 'student', 'student', 'active'),
    ('U002/CM/002/24', 'Student Name 2', '0772123457', 'student', 'student', 'active'),
    ('U003/CM/003/24', 'Student Name 3', '0772123458', 'student', 'student', 'active');

-- Update multiple students status
UPDATE users 
SET status = ?
WHERE type = 'student' AND id IN (?, ?, ?);

-- ========================================
-- STUDENT LOGIN AUDIT
-- ========================================

-- Get student login history
SELECT 
    la.user_identifier,
    la.attempt_time,
    la.ip_address,
    la.success,
    la.failure_reason,
    u.full_name,
    u.index_number
FROM login_attempts la
JOIN users u ON (la.user_identifier = u.index_number)
WHERE la.user_type = 'student'
ORDER BY la.attempt_time DESC;

-- Get recent student login attempts (last 24 hours)
SELECT 
    la.user_identifier,
    la.attempt_time,
    la.ip_address,
    la.success,
    la.failure_reason,
    u.full_name
FROM login_attempts la
JOIN users u ON (la.user_identifier = u.index_number)
WHERE la.user_type = 'student' 
  AND la.attempt_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY la.attempt_time DESC;

-- ========================================
-- STORED PROCEDURES FOR STUDENT MANAGEMENT
-- ========================================

DELIMITER //

-- Procedure to create student account with validation
CREATE PROCEDURE IF NOT EXISTS create_student_account(
    IN p_index_number VARCHAR(50),
    IN p_full_name VARCHAR(255),
    IN p_phone VARCHAR(20),
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN
)
BEGIN
    DECLARE v_count INT DEFAULT 0;
    
    -- Check if index number already exists
    SELECT COUNT(*) INTO v_count
    FROM users 
    WHERE index_number = p_index_number AND type = 'student';
    
    IF v_count > 0 THEN
        SET p_result = 'Index number already exists';
        SET p_success = FALSE;
    ELSE
        -- Insert new student
        INSERT INTO users (
            index_number, full_name, phone, role, type, status
        ) VALUES (
            p_index_number, p_full_name, p_phone, 'student', 'student', 'active'
        );
        
        SET p_result = 'Student account created successfully';
        SET p_success = TRUE;
    END IF;
END //

-- Procedure to authenticate student
CREATE PROCEDURE IF NOT EXISTS authenticate_student(
    IN p_index_number VARCHAR(50),
    IN p_full_name VARCHAR(255),
    IN p_phone VARCHAR(20),
    IN p_ip_address VARCHAR(45),
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN,
    OUT p_user_id INT
)
BEGIN
    DECLARE v_count INT DEFAULT 0;
    DECLARE v_user_id INT DEFAULT NULL;
    DECLARE v_locked_until TIMESTAMP NULL;
    
    -- Check if account is locked
    SELECT locked_until INTO v_locked_until
    FROM users 
    WHERE index_number = p_index_number AND type = 'student';
    
    IF v_locked_until IS NOT NULL AND v_locked_until > NOW() THEN
        -- Log failed attempt
        CALL log_login_attempt(p_index_number, 'student', p_ip_address, NULL, FALSE, 'Account locked');
        SET p_result = 'Account temporarily locked due to multiple failed attempts';
        SET p_success = FALSE;
        SET p_user_id = NULL;
    ELSE
        -- Verify credentials
        SELECT id INTO v_user_id
        FROM users 
        WHERE index_number = p_index_number 
          AND full_name = p_full_name 
          AND phone = p_phone 
          AND type = 'student' 
          AND status = 'active';
        
        IF v_user_id IS NOT NULL THEN
            -- Success - reset attempts and update last login
            UPDATE users 
            SET login_attempts = 0, 
                locked_until = NULL, 
                last_login = NOW()
            WHERE id = v_user_id;
            
            -- Log successful attempt
            CALL log_login_attempt(p_index_number, 'student', p_ip_address, NULL, TRUE, NULL);
            
            SET p_result = 'Login successful';
            SET p_success = TRUE;
            SET p_user_id = v_user_id;
        ELSE
            -- Failed - increment attempts
            CALL increment_login_attempts(p_index_number);
            
            -- Log failed attempt
            CALL log_login_attempt(p_index_number, 'student', p_ip_address, NULL, FALSE, 'Invalid credentials');
            
            SET p_result = 'Invalid student credentials. All fields must match exactly.';
            SET p_success = FALSE;
            SET p_user_id = NULL;
        END IF;
    END IF;
END //

DELIMITER ;

-- Success message
SELECT 'Student management queries created successfully!' as message;
