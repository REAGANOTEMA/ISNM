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

-- 5. Student Finance Table
CREATE TABLE student_finance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    tuition_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    balance DECIMAL(10,2) GENERATED ALWAYS AS (tuition_fee - amount_paid) STORED,
    payment_method VARCHAR(50),
    payment_date DATE,
    payment_status ENUM('pending', 'partial', 'paid', 'overdue') DEFAULT 'pending',
    semester VARCHAR(50),
    academic_year VARCHAR(20),
    receipt_number VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_student_finance (student_id),
    INDEX idx_payment_status (payment_status),
    INDEX idx_academic_year (academic_year)
);

-- 6. Student Documents Table
CREATE TABLE student_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    document_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT,
    file_type VARCHAR(50),
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    uploaded_by INT,
    status ENUM('active', 'inactive', 'expired') DEFAULT 'active',
    expiry_date DATE,
    description TEXT,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_student_docs (student_id),
    INDEX idx_document_type (document_type),
    INDEX idx_status (status)
);

-- 7. Announcements Table
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    announcement_type ENUM('general', 'academic', 'finance', 'emergency', 'events') DEFAULT 'general',
    target_audience ENUM('all', 'students', 'staff', 'specific') DEFAULT 'all',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    posted_by INT NOT NULL,
    posted_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expiry_date DATE,
    status ENUM('draft', 'published', 'expired') DEFAULT 'draft',
    attachment_path VARCHAR(500),
    view_count INT DEFAULT 0,
    FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_announcement_type (announcement_type),
    INDEX idx_target_audience (target_audience),
    INDEX idx_priority (priority),
    INDEX idx_status (status),
    INDEX idx_posted_date (posted_date)
);

-- 8. Messages Table
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    message_type ENUM('text', 'document', 'announcement') DEFAULT 'text',
    attachment_path VARCHAR(500),
    sent_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_date TIMESTAMP NULL,
    status ENUM('sent', 'read', 'deleted') DEFAULT 'sent',
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    parent_message_id INT NULL,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_message_id) REFERENCES messages(id) ON DELETE SET NULL,
    INDEX idx_sender_receiver (sender_id, receiver_id),
    INDEX idx_status (status),
    INDEX idx_sent_date (sent_date),
    INDEX idx_priority (priority)
);

-- 9. Academic Records Table (for transcripts)
CREATE TABLE academic_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_code VARCHAR(20) NOT NULL,
    course_title VARCHAR(200) NOT NULL,
    credits INT NOT NULL,
    grade VARCHAR(10) NOT NULL,
    grade_points DECIMAL(3,2),
    semester VARCHAR(50) NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    lecturer VARCHAR(100),
    status ENUM('ongoing', 'completed', 'failed', 'withdrawn') DEFAULT 'ongoing',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_student_academic (student_id),
    INDEX idx_semester_year (semester, academic_year),
    INDEX idx_course_code (course_code)
);

-- 10. Audit Logs Table
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
