-- ISNM School Management System Database Setup
-- Create database and set character set

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS isnm_school 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Use the database
USE isnm_school;

-- Set timezone for proper timestamp handling
SET time_zone = '+03:00'; -- Uganda timezone

-- Create users table for unified authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    index_number VARCHAR(50) UNIQUE,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(20),
    password VARCHAR(255),
    role VARCHAR(50) NOT NULL,
    type ENUM('student', 'staff') NOT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    
    -- Indexes for performance
    INDEX idx_email (email),
    INDEX idx_index_number (index_number),
    INDEX idx_role (role),
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_login_attempts (login_attempts),
    INDEX idx_locked_until (locked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create audit log table for tracking login attempts
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_identifier VARCHAR(255) NOT NULL, -- email or index_number
    user_type ENUM('student', 'staff') NOT NULL,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent TEXT,
    success BOOLEAN DEFAULT FALSE,
    failure_reason VARCHAR(255),
    
    INDEX idx_user_identifier (user_identifier),
    INDEX idx_attempt_time (attempt_time),
    INDEX idx_success (success)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create session management table for enhanced security
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id),
    INDEX idx_expires_at (expires_at),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create password reset table for staff accounts
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    ip_address VARCHAR(45),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_token (token),
    INDEX idx_expires_at (expires_at),
    INDEX idx_used (used)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create system settings table
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('max_login_attempts', '5', 'Maximum number of failed login attempts before account lockout'),
('lockout_duration', '900', 'Account lockout duration in seconds (15 minutes)'),
('session_timeout', '1800', 'Session timeout in seconds (30 minutes)'),
('password_min_length', '8', 'Minimum password length for staff accounts'),
('require_password_change', '0', 'Require password change on first login'),
('maintenance_mode', '0', 'System maintenance mode (1=enabled, 0=disabled)'),
('school_name', 'Iganga School of Nursing and Midwifery', 'School name for system display'),
('school_logo', 'images/school-logo.png', 'Path to school logo image'),
('academic_year', '2024-2025', 'Current academic year')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Create stored procedures for common operations
DELIMITER //

-- Procedure to log login attempts
CREATE PROCEDURE IF NOT EXISTS log_login_attempt(
    IN p_identifier VARCHAR(255),
    IN p_user_type ENUM('student', 'staff'),
    IN p_ip_address VARCHAR(45),
    IN p_user_agent TEXT,
    IN p_success BOOLEAN,
    IN p_failure_reason VARCHAR(255)
)
BEGIN
    INSERT INTO login_attempts (
        user_identifier, user_type, ip_address, user_agent, success, failure_reason
    ) VALUES (
        p_identifier, p_user_type, p_ip_address, p_user_agent, p_success, p_failure_reason
    );
END //

-- Procedure to increment login attempts
CREATE PROCEDURE IF NOT EXISTS increment_login_attempts(
    IN p_identifier VARCHAR(255)
)
BEGIN
    UPDATE users 
    SET login_attempts = login_attempts + 1,
        locked_until = CASE 
            WHEN login_attempts >= 4 THEN DATE_ADD(NOW(), INTERVAL 15 MINUTE)
            ELSE NULL 
        END
    WHERE email = p_identifier OR index_number = p_identifier;
END //

-- Procedure to reset login attempts
CREATE PROCEDURE IF NOT EXISTS reset_login_attempts(
    IN p_user_id INT
)
BEGIN
    UPDATE users 
    SET login_attempts = 0,
        locked_until = NULL,
        last_login = NOW()
    WHERE id = p_user_id;
END //

DELIMITER ;

-- Create triggers for automatic cleanup
DELIMITER //

-- Trigger to clean up expired sessions
CREATE TRIGGER IF NOT EXISTS cleanup_expired_sessions
AFTER INSERT ON user_sessions
FOR EACH ROW
BEGIN
    DELETE FROM user_sessions 
    WHERE expires_at < NOW() OR is_active = FALSE;
END //

DELIMITER ;

-- Success message
SELECT 'Database setup completed successfully!' as message;
