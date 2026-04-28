-- ISNM School Management System - Database Maintenance
-- SQL queries for database maintenance, cleanup, and optimization

USE isnm_db;

-- ========================================
-- DATABASE CLEANUP QUERIES
-- ========================================

-- Clean up expired sessions
DELETE FROM user_sessions 
WHERE expires_at < NOW() OR is_active = FALSE;

-- Clean up old login attempts (older than 90 days)
DELETE FROM login_attempts 
WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 90 DAY);

-- Clean up used password reset tokens (older than 24 hours)
DELETE FROM password_resets 
WHERE used = TRUE OR expires_at < NOW();

-- Clean up inactive users (older than 1 year, never logged in)
DELETE FROM users 
WHERE type = 'student' 
  AND status = 'inactive' 
  AND last_login IS NULL 
  AND created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);

-- ========================================
-- DATABASE OPTIMIZATION QUERIES
-- ========================================

-- Optimize tables for better performance
OPTIMIZE TABLE users;
OPTIMIZE TABLE login_attempts;
OPTIMIZE TABLE user_sessions;
OPTIMIZE TABLE password_resets;
OPTIMIZE TABLE system_settings;

-- Analyze tables to update statistics
ANALYZE TABLE users;
ANALYZE TABLE login_attempts;
ANALYZE TABLE user_sessions;
ANALYZE TABLE password_resets;
ANALYZE TABLE system_settings;

-- ========================================
-- DATABASE BACKUP QUERIES
-- ========================================

-- Create backup of users table
CREATE TABLE users_backup LIKE users;
INSERT INTO users_backup SELECT * FROM users;

-- Create backup of login attempts (last 30 days only)
CREATE TABLE login_attempts_backup LIKE login_attempts;
INSERT INTO login_attempts_backup 
SELECT * FROM login_attempts 
WHERE attempt_time >= DATE_SUB(NOW(), INTERVAL 30 DAY);

-- ========================================
-- DATABASE INTEGRITY CHECKS
-- ========================================

-- Check for orphaned login attempts
SELECT COUNT(*) as orphaned_attempts
FROM login_attempts la
LEFT JOIN users u ON (la.user_identifier = u.email OR la.user_identifier = u.index_number)
WHERE u.id IS NULL;

-- Check for orphaned sessions
SELECT COUNT(*) as orphaned_sessions
FROM user_sessions us
LEFT JOIN users u ON us.user_id = u.id
WHERE u.id IS NULL;

-- Check for orphaned password resets
SELECT COUNT(*) as orphaned_resets
FROM password_resets pr
LEFT JOIN users u ON pr.user_id = u.id
WHERE u.id IS NULL;

-- Check for duplicate emails
SELECT email, COUNT(*) as count
FROM users 
WHERE type = 'staff' AND email IS NOT NULL
GROUP BY email
HAVING COUNT(*) > 1;

-- Check for duplicate index numbers
SELECT index_number, COUNT(*) as count
FROM users 
WHERE type = 'student' AND index_number IS NOT NULL
GROUP BY index_number
HAVING COUNT(*) > 1;

-- ========================================
-- DATABASE STATISTICS
-- ========================================

-- Get database size information
SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)'
FROM information_schema.tables 
WHERE table_schema = 'isnm_school'
ORDER BY (data_length + index_length) DESC;

-- Get user statistics
SELECT 
    'Total Users' as metric,
    COUNT(*) as value
FROM users
UNION ALL
SELECT 
    'Active Students' as metric,
    COUNT(*) as value
FROM users 
WHERE type = 'student' AND status = 'active'
UNION ALL
SELECT 
    'Active Staff' as metric,
    COUNT(*) as value
FROM users 
WHERE type = 'staff' AND status = 'active'
UNION ALL
SELECT 
    'Users Logged In Today' as metric,
    COUNT(*) as value
FROM users 
WHERE last_login >= DATE(NOW())
UNION ALL
SELECT 
    'Users Logged In This Week' as metric,
    COUNT(*) as value
FROM users 
WHERE last_login >= DATE_SUB(NOW(), INTERVAL 7 DAY);

-- Get login attempt statistics
SELECT 
    'Total Login Attempts' as metric,
    COUNT(*) as value
FROM login_attempts
UNION ALL
SELECT 
    'Successful Logins' as metric,
    COUNT(*) as value
FROM login_attempts 
WHERE success = TRUE
UNION ALL
SELECT 
    'Failed Logins' as metric,
    COUNT(*) as value
FROM login_attempts 
WHERE success = FALSE
UNION ALL
SELECT 
    'Failed Logins Today' as metric,
    COUNT(*) as value
FROM login_attempts 
WHERE success = FALSE AND attempt_time >= DATE(NOW());

-- ========================================
-- DATABASE HEALTH CHECKS
-- ========================================

-- Check for users with weak passwords (staff only)
SELECT 
    id,
    email,
    full_name,
    role,
    LENGTH(password) as password_length
FROM users 
WHERE type = 'staff' 
  AND status = 'active'
  AND LENGTH(password) < 60
ORDER BY password_length ASC;

-- Check for users who haven't logged in recently
SELECT 
    id,
    full_name,
    email,
    role,
    type,
    last_login,
    created_at,
    DATEDIFF(NOW(), last_login) as days_since_login
FROM users 
WHERE status = 'active'
  AND last_login IS NOT NULL
  AND last_login < DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY days_since_login DESC;

-- Check for users with multiple failed login attempts
SELECT 
    id,
    full_name,
    email,
    index_number,
    role,
    type,
    login_attempts,
    locked_until,
    CASE 
        WHEN locked_until > NOW() THEN 'Currently Locked'
        ELSE 'Not Locked'
    END as lock_status
FROM users 
WHERE login_attempts > 0
ORDER BY login_attempts DESC;

-- ========================================
-- DATABASE MAINTENANCE PROCEDURES
-- ========================================

DELIMITER //

-- Procedure to perform daily maintenance
CREATE PROCEDURE IF NOT EXISTS daily_maintenance()
BEGIN
    -- Clean up expired sessions
    DELETE FROM user_sessions 
    WHERE expires_at < NOW() OR is_active = FALSE;
    
    -- Clean up old password reset tokens
    DELETE FROM password_resets 
    WHERE used = TRUE OR expires_at < NOW();
    
    -- Update system statistics
    UPDATE system_settings 
    SET setting_value = (
        SELECT COUNT(*) 
        FROM users 
        WHERE type = 'student' AND status = 'active'
    ),
    updated_at = NOW()
    WHERE setting_key = 'active_students_count';
    
    UPDATE system_settings 
    SET setting_value = (
        SELECT COUNT(*) 
        FROM users 
        WHERE type = 'staff' AND status = 'active'
    ),
    updated_at = NOW()
    WHERE setting_key = 'active_staff_count';
    
    SELECT 'Daily maintenance completed' as result;
END //

-- Procedure to perform weekly maintenance
CREATE PROCEDURE IF NOT EXISTS weekly_maintenance()
BEGIN
    -- Clean up old login attempts (older than 90 days)
    DELETE FROM login_attempts 
    WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 90 DAY);
    
    -- Optimize tables
    SET @sql = CONCAT('OPTIMIZE TABLE users, login_attempts, user_sessions, password_resets, system_settings');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
    
    -- Analyze tables
    SET @sql = CONCAT('ANALYZE TABLE users, login_attempts, user_sessions, password_resets, system_settings');
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
    
    SELECT 'Weekly maintenance completed' as result;
END //

-- Procedure to perform monthly maintenance
CREATE PROCEDURE IF NOT EXISTS monthly_maintenance()
BEGIN
    -- Create backup of users table
    DROP TABLE IF EXISTS users_backup;
    CREATE TABLE users_backup LIKE users;
    INSERT INTO users_backup SELECT * FROM users;
    
    -- Clean up inactive users (older than 1 year, never logged in)
    DELETE FROM users 
    WHERE type = 'student' 
      AND status = 'inactive' 
      AND last_login IS NULL 
      AND created_at < DATE_SUB(NOW(), INTERVAL 1 YEAR);
    
    -- Update system settings for new academic year if needed
    UPDATE system_settings 
    SET setting_value = CASE 
        WHEN MONTH(NOW()) = 1 THEN CONCAT(YEAR(NOW()), '-', YEAR(NOW()) + 1)
        ELSE setting_value
    END,
    updated_at = NOW()
    WHERE setting_key = 'academic_year';
    
    SELECT 'Monthly maintenance completed' as result;
END //

-- Procedure to check database health
CREATE PROCEDURE IF NOT EXISTS check_database_health()
BEGIN
    DECLARE v_orphaned_attempts INT DEFAULT 0;
    DECLARE v_orphaned_sessions INT DEFAULT 0;
    DECLARE v_orphaned_resets INT DEFAULT 0;
    DECLARE v_duplicate_emails INT DEFAULT 0;
    DECLARE v_duplicate_indices INT DEFAULT 0;
    
    -- Check orphaned records
    SELECT COUNT(*) INTO v_orphaned_attempts
    FROM login_attempts la
    LEFT JOIN users u ON (la.user_identifier = u.email OR la.user_identifier = u.index_number)
    WHERE u.id IS NULL;
    
    SELECT COUNT(*) INTO v_orphaned_sessions
    FROM user_sessions us
    LEFT JOIN users u ON us.user_id = u.id
    WHERE u.id IS NULL;
    
    SELECT COUNT(*) INTO v_orphaned_resets
    FROM password_resets pr
    LEFT JOIN users u ON pr.user_id = u.id
    WHERE u.id IS NULL;
    
    -- Check duplicates
    SELECT COUNT(*) INTO v_duplicate_emails
    FROM (
        SELECT email, COUNT(*) as cnt
        FROM users 
        WHERE type = 'staff' AND email IS NOT NULL
        GROUP BY email
        HAVING cnt > 1
    ) as dup_emails;
    
    SELECT COUNT(*) INTO v_duplicate_indices
    FROM (
        SELECT index_number, COUNT(*) as cnt
        FROM users 
        WHERE type = 'student' AND index_number IS NOT NULL
        GROUP BY index_number
        HAVING cnt > 1
    ) as dup_indices;
    
    -- Return health report
    SELECT 
        'Database Health Check' as check_type,
        'Orphaned Login Attempts' as metric,
        v_orphaned_attempts as value,
        CASE WHEN v_orphaned_attempts = 0 THEN 'OK' ELSE 'Warning' END as status
    UNION ALL
    SELECT 
        'Database Health Check' as check_type,
        'Orphaned Sessions' as metric,
        v_orphaned_sessions as value,
        CASE WHEN v_orphaned_sessions = 0 THEN 'OK' ELSE 'Warning' END as status
    UNION ALL
    SELECT 
        'Database Health Check' as check_type,
        'Orphaned Password Resets' as metric,
        v_orphaned_resets as value,
        CASE WHEN v_orphaned_resets = 0 THEN 'OK' ELSE 'Warning' END as status
    UNION ALL
    SELECT 
        'Database Health Check' as check_type,
        'Duplicate Emails' as metric,
        v_duplicate_emails as value,
        CASE WHEN v_duplicate_emails = 0 THEN 'OK' ELSE 'Error' END as status
    UNION ALL
    SELECT 
        'Database Health Check' as check_type,
        'Duplicate Index Numbers' as metric,
        v_duplicate_indices as value,
        CASE WHEN v_duplicate_indices = 0 THEN 'OK' ELSE 'Error' END as status;
END //

DELIMITER ;

-- ========================================
-- SCHEDULED MAINTENANCE SETUP
-- ========================================

-- Note: To set up scheduled maintenance, you would need to create MySQL events
-- This requires the MySQL event scheduler to be enabled

-- Enable event scheduler (run once)
-- SET GLOBAL event_scheduler = ON;

-- Create daily maintenance event (runs at 2:00 AM)
-- CREATE EVENT IF NOT EXISTS daily_maintenance_event
-- ON SCHEDULE EVERY 1 DAY
-- STARTS TIMESTAMP(CURRENT_DATE, '02:00:00')
-- DO CALL daily_maintenance();

-- Create weekly maintenance event (runs every Sunday at 3:00 AM)
-- CREATE EVENT IF NOT EXISTS weekly_maintenance_event
-- ON SCHEDULE EVERY 1 WEEK
-- STARTS TIMESTAMP(DATE_ADD(CURRENT_DATE, INTERVAL (7 - DAYOFWEEK(CURRENT_DATE)) DAY), '03:00:00')
-- DO CALL weekly_maintenance();

-- Create monthly maintenance event (runs on 1st of each month at 4:00 AM)
-- CREATE EVENT IF NOT EXISTS monthly_maintenance_event
-- ON SCHEDULE EVERY 1 MONTH
-- STARTS TIMESTAMP(LAST_DAY(CURRENT_DATE) + INTERVAL 1 DAY, '04:00:00')
-- DO CALL monthly_maintenance();

-- Success message
SELECT 'Database maintenance queries created successfully!' as message;
SELECT 'Run CALL daily_maintenance(), weekly_maintenance(), or monthly_maintenance() as needed' as note;
