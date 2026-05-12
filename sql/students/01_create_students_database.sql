-- ISNM Students Database Schema
-- Database: students_db

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS students_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE students_db;

-- Drop existing tables if they exist (for fresh installation)
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS student_profiles;
DROP TABLE IF EXISTS student_academic_records;
DROP TABLE IF EXISTS student_attendance;
DROP TABLE IF EXISTS student_fees;
DROP TABLE IF EXISTS student_timetables;
DROP TABLE IF EXISTS student_notifications;
DROP TABLE IF EXISTS student_downloads;

-- 1. Students Table
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    registration_number VARCHAR(50) NOT NULL UNIQUE,
    national_student_id_number VARCHAR(50),
    index_number VARCHAR(50),
    mobile_number VARCHAR(20),
    email VARCHAR(100),
    course VARCHAR(100),
    year INT,
    set_name VARCHAR(50),
    intake_date DATE,
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other') DEFAULT 'Other',
    address TEXT,
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    status ENUM('Active', 'Inactive', 'Graduated', 'Suspended') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_registration_number (registration_number),
    INDEX idx_national_id (national_student_id_number),
    INDEX idx_index_number (index_number),
    INDEX idx_course (course),
    INDEX idx_year (year),
    INDEX idx_status (status)
);

-- 2. Student Profiles Table
CREATE TABLE student_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    bio TEXT,
    profile_picture VARCHAR(255),
    interests TEXT,
    skills TEXT,
    achievements TEXT,
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
    grade VARCHAR(10),
    marks DECIMAL(5,2),
    credits DECIMAL(3,1),
    gpa DECIMAL(3,2),
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
    status ENUM('Present', 'Absent', 'Late', 'Excused') NOT NULL,
    remarks TEXT,
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
    type ENUM('General', 'Academic', 'Fee', 'Attendance', 'Exam', 'Event') DEFAULT 'General',
    priority ENUM('Low', 'Medium', 'High', 'Urgent') DEFAULT 'Medium',
    is_read BOOLEAN DEFAULT FALSE,
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

-- End of Students Database Schema
