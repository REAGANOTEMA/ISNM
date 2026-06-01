-- ISNM Students Database Schema with Login Support
-- Database: igangaschoolofl_students_db

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS igangaschoolofl_students_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE igangaschoolofl_students_db;

-- Drop existing tables if they exist (for fresh installation)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS student_messages;
DROP TABLE IF EXISTS student_notifications;
DROP TABLE IF EXISTS student_downloads;
DROP TABLE IF EXISTS student_timetables;
DROP TABLE IF EXISTS student_fees;
DROP TABLE IF EXISTS student_attendance;
DROP TABLE IF EXISTS student_academic_records;
DROP TABLE IF EXISTS student_profiles;
DROP TABLE IF EXISTS student_password_resets;
DROP TABLE IF EXISTS students;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Students Table with Login Support
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_number VARCHAR(50) UNIQUE NOT NULL,
    registration_number VARCHAR(50) UNIQUE,
    national_student_id_number VARCHAR(50) UNIQUE,
    index_number VARCHAR(50) UNIQUE,
    
    first_name VARCHAR(100) NOT NULL,
    surname VARCHAR(100) NOT NULL,
    other_name VARCHAR(100),
    full_name VARCHAR(300),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255), -- For student login
    
    phone VARCHAR(20),
    mobile_number VARCHAR(20),
    program VARCHAR(100),
    course VARCHAR(100),
    current_year INT,
    year INT,
    level VARCHAR(50),
    set_name VARCHAR(50),
    current_semester VARCHAR(20),
    intake_date DATE,
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other') DEFAULT 'Other',
    nationality VARCHAR(100),
    address TEXT,
    
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    emergency_contact_email VARCHAR(100),
    
    guardian_name VARCHAR(200),
    guardian_phone VARCHAR(20),
    
    profile_picture VARCHAR(500),
    passport_photo VARCHAR(500),
    
    status ENUM('Active', 'Inactive', 'Graduated', 'Suspended', 'Withdrawn', 'deleted') DEFAULT 'Active',
    
    last_login TIMESTAMP NULL,
    locked_until TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    password_changed BOOLEAN DEFAULT FALSE,
    is_first_login BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student_number (student_number),
    INDEX idx_registration_number (registration_number),
    INDEX idx_national_id (national_student_id_number),
    INDEX idx_index_number (index_number),
    INDEX idx_email (email),
    INDEX idx_program (program),
    INDEX idx_course (course),
    INDEX idx_current_year (current_year),
    INDEX idx_year (year),
    INDEX idx_status (status)
);

DROP TRIGGER IF EXISTS students_before_insert;
DROP TRIGGER IF EXISTS students_before_update;

DELIMITER //
CREATE TRIGGER students_before_insert
BEFORE INSERT ON students
FOR EACH ROW
BEGIN
    IF NEW.full_name IS NULL OR NEW.full_name = '' THEN
        SET NEW.full_name = TRIM(CONCAT(NEW.first_name, ' ', COALESCE(NEW.other_name, ''), ' ', NEW.surname));
    END IF;

    IF (NEW.phone IS NULL OR NEW.phone = '') AND NEW.mobile_number IS NOT NULL THEN
        SET NEW.phone = NEW.mobile_number;
    END IF;
    IF (NEW.mobile_number IS NULL OR NEW.mobile_number = '') AND NEW.phone IS NOT NULL THEN
        SET NEW.mobile_number = NEW.phone;
    END IF;

    IF (NEW.program IS NULL OR NEW.program = '') AND NEW.course IS NOT NULL THEN
        SET NEW.program = NEW.course;
    END IF;
    IF (NEW.course IS NULL OR NEW.course = '') AND NEW.program IS NOT NULL THEN
        SET NEW.course = NEW.program;
    END IF;

    IF (NEW.current_year IS NULL OR NEW.current_year = 0) AND NEW.year IS NOT NULL THEN
        SET NEW.current_year = NEW.year;
    END IF;
    IF (NEW.year IS NULL OR NEW.year = 0) AND NEW.current_year IS NOT NULL THEN
        SET NEW.year = NEW.current_year;
    END IF;

    IF (NEW.profile_picture IS NULL OR NEW.profile_picture = '') AND NEW.passport_photo IS NOT NULL THEN
        SET NEW.profile_picture = NEW.passport_photo;
    END IF;
    IF (NEW.passport_photo IS NULL OR NEW.passport_photo = '') AND NEW.profile_picture IS NOT NULL THEN
        SET NEW.passport_photo = NEW.profile_picture;
    END IF;
END //

CREATE TRIGGER students_before_update
BEFORE UPDATE ON students
FOR EACH ROW
BEGIN
    IF NEW.full_name IS NULL OR NEW.full_name = '' THEN
        SET NEW.full_name = TRIM(CONCAT(NEW.first_name, ' ', COALESCE(NEW.other_name, ''), ' ', NEW.surname));
    END IF;

    IF (NEW.phone IS NULL OR NEW.phone = '') AND NEW.mobile_number IS NOT NULL THEN
        SET NEW.phone = NEW.mobile_number;
    END IF;
    IF (NEW.mobile_number IS NULL OR NEW.mobile_number = '') AND NEW.phone IS NOT NULL THEN
        SET NEW.mobile_number = NEW.phone;
    END IF;

    IF (NEW.program IS NULL OR NEW.program = '') AND NEW.course IS NOT NULL THEN
        SET NEW.program = NEW.course;
    END IF;
    IF (NEW.course IS NULL OR NEW.course = '') AND NEW.program IS NOT NULL THEN
        SET NEW.course = NEW.program;
    END IF;

    IF (NEW.current_year IS NULL OR NEW.current_year = 0) AND NEW.year IS NOT NULL THEN
        SET NEW.current_year = NEW.year;
    END IF;
    IF (NEW.year IS NULL OR NEW.year = 0) AND NEW.current_year IS NOT NULL THEN
        SET NEW.year = NEW.current_year;
    END IF;

    IF (NEW.profile_picture IS NULL OR NEW.profile_picture = '') AND NEW.passport_photo IS NOT NULL THEN
        SET NEW.profile_picture = NEW.passport_photo;
    END IF;
    IF (NEW.passport_photo IS NULL OR NEW.passport_photo = '') AND NEW.profile_picture IS NOT NULL THEN
        SET NEW.passport_photo = NEW.profile_picture;
    END IF;
END //
DELIMITER ;

-- 2. Student Profiles Table
CREATE TABLE student_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    bio TEXT,
    interests TEXT,
    skills TEXT,
    achievements TEXT,
    education_background TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_student_id (student_id)
);

-- 3. Academic Records Table
CREATE TABLE student_academic_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    semester VARCHAR(50),
    academic_year VARCHAR(20),
    subject VARCHAR(100),
    course_code VARCHAR(20),
    grade VARCHAR(10),
    marks DECIMAL(5,2),
    credits DECIMAL(3,1),
    gpa DECIMAL(3,2),
    cgpa DECIMAL(3,2),
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_semester (semester),
    INDEX idx_academic_year (academic_year),
    INDEX idx_subject (subject)
);

-- 4. Attendance Table
CREATE TABLE student_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    date DATE NOT NULL,
    subject VARCHAR(100),
    course_code VARCHAR(20),
    status ENUM('Present', 'Absent', 'Late', 'Excused') NOT NULL,
    remarks TEXT,
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_date (date),
    INDEX idx_subject (subject),
    INDEX idx_status (status)
);

-- 5. Fees Table
CREATE TABLE student_fees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    fee_type VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    due_date DATE,
    paid_date DATE,
    status ENUM('Unpaid', 'Partially Paid', 'Paid', 'Overdue') DEFAULT 'Unpaid',
    payment_method VARCHAR(50),
    receipt_number VARCHAR(50),
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_fee_type (fee_type),
    INDEX idx_status (status),
    INDEX idx_due_date (due_date)
);

-- 6. Timetables Table
CREATE TABLE student_timetables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    time_slot VARCHAR(50) NOT NULL,
    subject VARCHAR(100) NOT NULL,
    course_code VARCHAR(20),
    lecturer VARCHAR(100),
    classroom VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_day_of_week (day_of_week),
    INDEX idx_subject (subject)
);

-- 7. Notifications Table
CREATE TABLE student_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('General', 'Academic', 'Fee', 'Attendance', 'Exam', 'Event', 'Matron', 'Bursar') DEFAULT 'General',
    priority ENUM('Low', 'Medium', 'High', 'Urgent') DEFAULT 'Medium',
    is_read BOOLEAN DEFAULT FALSE,
    action_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_type (type),
    INDEX idx_priority (priority),
    INDEX idx_is_read (is_read)
);

-- 8. Downloads Table
CREATE TABLE student_downloads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size BIGINT,
    download_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_file_type (file_type),
    INDEX idx_created_at (created_at)
);

-- 9. Student Messages to Departments
CREATE TABLE student_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    department_email VARCHAR(100) NOT NULL, -- e.g., matrons@..., bursar@...
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    replied BOOLEAN DEFAULT FALSE,
    reply_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    replied_at TIMESTAMP NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_department_email (department_email),
    INDEX idx_is_read (is_read)
);

-- 10. Password Reset Tokens
CREATE TABLE student_password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    reset_token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    is_used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_reset_token (reset_token),
    INDEX idx_expires_at (expires_at)
);

-- Views for easy access

-- Student Login View
CREATE OR REPLACE VIEW student_login_view AS
SELECT 
    id,
    student_number,
    COALESCE(full_name, TRIM(CONCAT(first_name, ' ', COALESCE(other_name, ''), ' ', surname))) AS full_name,
    email,
    password,
    COALESCE(course, program) AS course,
    status,
    last_login,
    login_attempts,
    is_first_login
FROM students
WHERE status = 'Active';

-- Student Dashboard View
CREATE OR REPLACE VIEW student_dashboard_view AS
SELECT 
    s.id,
    s.student_number,
    COALESCE(s.full_name, TRIM(CONCAT(s.first_name, ' ', COALESCE(s.other_name, ''), ' ', s.surname))) AS full_name,
    COALESCE(s.course, s.program) AS course,
    COALESCE(s.year, s.current_year) AS year,
    s.set_name,
    s.email,
    COALESCE(s.profile_picture, s.passport_photo) AS profile_picture,
    COALESCE(sa.gpa, 0) as current_gpa,
    COALESCE(sf.balance, 0) as fee_balance,
    COALESCE(sa2.attendance_rate, 0) as attendance_rate
FROM students s
LEFT JOIN (
    SELECT student_id, gpa FROM student_academic_records 
    WHERE semester = (SELECT MAX(semester) FROM student_academic_records)
    GROUP BY student_id
) sa ON s.id = sa.student_id
LEFT JOIN (
    SELECT student_id, SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) * 100.0 / COUNT(*) as attendance_rate
    FROM student_attendance GROUP BY student_id
) sa2 ON s.id = sa2.student_id
LEFT JOIN (
    SELECT student_id, SUM(amount) as balance FROM student_fees 
    WHERE status IN ('Unpaid', 'Partially Paid', 'Overdue') GROUP BY student_id
) sf ON s.id = sf.student_id
WHERE s.status = 'Active';

-- End of Students Database Schema