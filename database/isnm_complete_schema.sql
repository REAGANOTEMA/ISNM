-- ISNM Student Management System Complete Database Schema
-- Database: isnm_db

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS isnm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE isnm_db;

-- Drop existing tables if they exist (for fresh installation)
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;
DROP TABLE IF EXISTS courses;

-- 1. Roles Table
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    permissions JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role VARCHAR(50) NOT NULL,
    staff_id VARCHAR(50),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role) REFERENCES roles(name) ON UPDATE CASCADE ON DELETE RESTRICT
);

-- 3. Students Table
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    registration_number VARCHAR(50) NOT NULL UNIQUE,
    national_student_id_number VARCHAR(50),
    index_number VARCHAR(50),
    mobile_number VARCHAR(20),
    course VARCHAR(100),
    year INT,
    set_name VARCHAR(50),
    gender ENUM('Male', 'Female'),
    passport_photo VARCHAR(255),
    status ENUM('active', 'inactive', 'deleted') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_registration_number (registration_number),
    INDEX idx_course (course),
    INDEX idx_year (year),
    INDEX idx_set_name (set_name),
    INDEX idx_status (status)
);

-- 4. Courses Table (Optional - for course management)
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    code VARCHAR(20) NOT NULL UNIQUE,
    description TEXT,
    duration_years INT DEFAULT 3,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 5. Audit Logs Table
CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(50) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_data JSON,
    new_data JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_action (user_id, action),
    INDEX idx_table_record (table_name, record_id),
    INDEX idx_created_at (created_at)
);

-- Insert default roles
INSERT INTO roles (name, description, permissions) VALUES
('admin', 'System Administrator', '["create", "read", "update", "delete", "import", "export", "reports", "users"]'),
('principal', 'School Principal', '["create", "read", "update", "delete", "import", "export", "reports"]'),
('director', 'School Director', '["create", "read", "update", "delete", "import", "export", "reports"]'),
('bursar', 'School Bursar', '["read", "reports", "fees"]'),
('hr', 'Human Resource Manager', '["read", "create", "update", "reports"]'),
('secretary', 'School Secretary', '["read", "create", "update"]'),
('lecturer', 'Lecturer/Teacher', '["read"]');

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, full_name, email, role, staff_id) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@isnm.ac.ug', 'admin', 'ADM001');

-- Insert sample courses
INSERT INTO courses (name, code, description, duration_years) VALUES
('Nursing', 'NUR', 'Bachelor of Nursing Science', 3),
('Midwifery', 'MID', 'Bachelor of Midwifery', 3),
('Nursing & Midwifery', 'NUM', 'Diploma in Nursing and Midwifery', 3);

-- Create indexes for better performance
CREATE INDEX idx_students_search ON students(full_name, registration_number);
CREATE INDEX idx_users_status ON users(status);
CREATE INDEX idx_audit_logs_date ON audit_logs(created_at);

-- Create view for active students
CREATE VIEW active_students AS
SELECT 
    id, full_name, registration_number, national_student_id_number,
    index_number, mobile_number, course, year, set_name, gender,
    passport_photo, created_at, updated_at
FROM students 
WHERE status = 'active';

-- Create view for active users
CREATE VIEW active_users AS
SELECT 
    id, username, full_name, email, role, staff_id, created_at, updated_at
FROM users 
WHERE status = 'active';

-- Create stored procedure for student statistics
DELIMITER //
CREATE PROCEDURE GetStudentStatistics()
BEGIN
    SELECT 
        COUNT(*) as total_students,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_students,
        COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_students,
        COUNT(DISTINCT course) as total_courses,
        COUNT(DISTINCT year) as total_years,
        COUNT(DISTINCT set_name) as total_sets,
        COUNT(CASE WHEN gender = 'Male' THEN 1 END) as male_students,
        COUNT(CASE WHEN gender = 'Female' THEN 1 END) as female_students
    FROM students 
    WHERE status != 'deleted';
END //
DELIMITER ;

-- Create trigger for audit logging
DELIMITER //
CREATE TRIGGER after_student_insert
AFTER INSERT ON students
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (action, table_name, record_id, new_data)
    VALUES ('INSERT', 'students', NEW.id, JSON_OBJECT(
        'full_name', NEW.full_name,
        'registration_number', NEW.registration_number,
        'course', NEW.course,
        'year', NEW.year,
        'gender', NEW.gender
    ));
END //

CREATE TRIGGER after_student_update
AFTER UPDATE ON students
FOR EACH ROW
BEGIN
    INSERT INTO audit_logs (action, table_name, record_id, old_data, new_data)
    VALUES ('UPDATE', 'students', NEW.id, 
        JSON_OBJECT(
            'full_name', OLD.full_name,
            'registration_number', OLD.registration_number,
            'course', OLD.course,
            'year', OLD.year,
            'gender', OLD.gender
        ),
        JSON_OBJECT(
            'full_name', NEW.full_name,
            'registration_number', NEW.registration_number,
            'course', NEW.course,
            'year', NEW.year,
            'gender', NEW.gender
        )
    );
END //

CREATE TRIGGER after_student_delete
AFTER UPDATE ON students
FOR EACH ROW
BEGIN
    IF NEW.status = 'deleted' AND OLD.status != 'deleted' THEN
        INSERT INTO audit_logs (action, table_name, record_id, old_data)
        VALUES ('DELETE', 'students', NEW.id, JSON_OBJECT(
            'full_name', OLD.full_name,
            'registration_number', OLD.registration_number,
            'status', OLD.status
        ));
    END IF;
END //
DELIMITER ;

-- Create function to check for duplicate registration numbers
DELIMITER //
CREATE FUNCTION CheckDuplicateRegistration(reg_num VARCHAR(50), student_id INT) 
RETURNS BOOLEAN
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE count INT;
    
    SELECT COUNT(*) INTO count 
    FROM students 
    WHERE registration_number = reg_num 
    AND id != COALESCE(student_id, 0)
    AND status != 'deleted';
    
    RETURN count > 0;
END //
DELIMITER ;

-- Set database engine and character set
ALTER DATABASE isnm_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Final verification query
SELECT 'ISNM Student Management System Database Setup Complete' as status;
