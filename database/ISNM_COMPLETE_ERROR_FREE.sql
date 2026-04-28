-- =====================================================
-- ISNM SCHOOL MANAGEMENT SYSTEM - COMPLETE ERROR-FREE DATABASE
-- Database: isnm_db
-- This is the single master file containing all tables, procedures, and data
-- Run this file to create the complete system without any errors
-- =====================================================

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS isnm_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

-- Use the database
USE isnm_db;

-- Set timezone for proper timestamp handling
SET time_zone = '+03:00'; -- Uganda timezone

-- Disable foreign key checks for initial setup
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- PART 1: CORE AUTHENTICATION SYSTEM
-- =====================================================

-- Drop existing tables if they exist to ensure clean setup
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS user_sessions;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS system_settings;

-- Users table - Unified storage for students and staff
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NULL,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NULL,
    phone VARCHAR(20) UNIQUE NULL,
    index_number VARCHAR(50) UNIQUE NULL, -- For students
    password VARCHAR(255) NULL, -- Hashed password for staff only
    role VARCHAR(100) NOT NULL DEFAULT 'student',
    type ENUM('student', 'staff') NOT NULL DEFAULT 'student',
    gender ENUM('Male', 'Female', 'Other') NULL,
    date_of_birth DATE NULL,
    address TEXT NULL,
    profile_picture VARCHAR(500) NULL,
    status ENUM('active', 'inactive', 'suspended', 'graduated') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_username (username),
    INDEX idx_email (email),
    INDEX idx_phone (phone),
    INDEX idx_index_number (index_number),
    INDEX idx_role (role),
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_login_attempts (login_attempts),
    INDEX idx_locked_until (locked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Login attempts tracking
CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL, -- email, phone, or index_number
    attempt_count INT DEFAULT 1,
    is_locked BOOLEAN DEFAULT FALSE,
    locked_until TIMESTAMP NULL,
    last_attempt_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    
    INDEX idx_identifier (identifier),
    INDEX idx_locked (is_locked),
    INDEX idx_locked_until (locked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User sessions
CREATE TABLE user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    
    INDEX idx_session_id (session_id),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at),
    INDEX idx_is_active (is_active),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Password resets
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_token (token),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System settings
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    setting_description TEXT NULL,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    is_public BOOLEAN DEFAULT FALSE,
    updated_by INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_key (setting_key),
    INDEX idx_public (is_public),
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PART 2: ACADEMIC MANAGEMENT SYSTEM
-- =====================================================

-- Drop existing academic tables
DROP TABLE IF EXISTS academic_programs;
DROP TABLE IF EXISTS academic_sessions;
DROP TABLE IF EXISTS academic_semesters;
DROP TABLE IF EXISTS academic_courses;
DROP TABLE IF EXISTS student_academic_records;
DROP TABLE IF EXISTS course_registrations;
DROP TABLE IF EXISTS examinations;
DROP TABLE IF EXISTS exam_results;
DROP TABLE IF EXISTS grade_scales;
DROP TABLE IF EXISTS attendance_sessions;
DROP TABLE IF EXISTS attendance_records;

-- Academic programs
CREATE TABLE academic_programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_code VARCHAR(20) NOT NULL UNIQUE,
    program_name VARCHAR(255) NOT NULL,
    program_description TEXT NULL,
    program_level ENUM('Certificate', 'Diploma', 'Bachelor', 'Master', 'PhD') NOT NULL,
    department VARCHAR(100) NOT NULL,
    duration_years DECIMAL(3,1) NOT NULL,
    total_credits_required INT NOT NULL DEFAULT 0,
    program_head_id INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (program_code),
    INDEX idx_level (program_level),
    INDEX idx_department (department),
    INDEX idx_active (is_active),
    FOREIGN KEY (program_head_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Academic sessions
CREATE TABLE academic_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_name VARCHAR(100) NOT NULL UNIQUE,
    session_code VARCHAR(20) NOT NULL UNIQUE,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_current BOOLEAN DEFAULT FALSE,
    registration_start_date DATE NULL,
    registration_end_date DATE NULL,
    exam_start_date DATE NULL,
    exam_end_date DATE NULL,
    status ENUM('upcoming', 'active', 'completed', 'archived') DEFAULT 'upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (session_code),
    INDEX idx_current (is_current),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Academic semesters
CREATE TABLE academic_semesters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    semester_number INT NOT NULL,
    semester_name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_current BOOLEAN DEFAULT FALSE,
    registration_deadline DATE NULL,
    exam_period_start DATE NULL,
    exam_period_end DATE NULL,
    status ENUM('upcoming', 'active', 'completed', 'archived') DEFAULT 'upcoming',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_session_semester (session_id, semester_number),
    INDEX idx_session (session_id),
    INDEX idx_current (is_current),
    INDEX idx_status (status),
    FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Academic courses
CREATE TABLE academic_courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    course_title VARCHAR(255) NOT NULL,
    course_description TEXT NULL,
    program_id INT NOT NULL,
    level INT NOT NULL,
    semester INT NOT NULL,
    credit_hours DECIMAL(3,1) NOT NULL DEFAULT 0,
    contact_hours INT NOT NULL DEFAULT 0,
    course_type ENUM('core', 'elective', 'prerequisite', 'general') NOT NULL DEFAULT 'core',
    prerequisites JSON NULL,
    course_objectives TEXT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (course_code),
    INDEX idx_program (program_id),
    INDEX idx_level_semester (level, semester),
    INDEX idx_type (course_type),
    INDEX idx_active (is_active),
    FOREIGN KEY (program_id) REFERENCES academic_programs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Student academic records
CREATE TABLE student_academic_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    program_id INT NOT NULL,
    current_level INT NOT NULL DEFAULT 1,
    current_semester INT NOT NULL DEFAULT 1,
    admission_session_id INT NOT NULL,
    admission_semester_id INT NOT NULL,
    admission_date DATE NOT NULL,
    academic_status ENUM('active', 'suspended', 'withdrawn', 'graduated', 'transferred') DEFAULT 'active',
    gpa_cumulative DECIMAL(3,2) DEFAULT 0.00,
    total_credits_earned INT DEFAULT 0,
    last_updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_student_program (student_id, program_id),
    INDEX idx_student (student_id),
    INDEX idx_program (program_id),
    INDEX idx_status (academic_status),
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (program_id) REFERENCES academic_programs(id) ON DELETE CASCADE,
    FOREIGN KEY (admission_session_id) REFERENCES academic_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (admission_semester_id) REFERENCES academic_semesters(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Course registrations
CREATE TABLE course_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    session_id INT NOT NULL,
    semester_id INT NOT NULL,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    registration_status ENUM('registered', 'dropped', 'completed', 'failed', 'withdrawn') DEFAULT 'registered',
    grade VARCHAR(5) NULL,
    grade_points DECIMAL(3,2) NULL,
    credit_hours DECIMAL(3,1) NOT NULL DEFAULT 0,
    attendance_percentage DECIMAL(5,2) DEFAULT 0.00,
    final_score DECIMAL(5,2) NULL,
    
    UNIQUE KEY unique_student_course_session (student_id, course_id, session_id, semester_id),
    INDEX idx_student (student_id),
    INDEX idx_course (course_id),
    INDEX idx_session (session_id),
    INDEX idx_semester (semester_id),
    INDEX idx_status (registration_status),
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES academic_courses(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES academic_semesters(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Examinations
CREATE TABLE examinations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    session_id INT NOT NULL,
    semester_id INT NOT NULL,
    exam_title VARCHAR(255) NOT NULL,
    exam_type ENUM('quiz', 'assignment', 'midterm', 'final', 'practical', 'oral', 'project') NOT NULL,
    exam_date DATE NOT NULL,
    exam_time TIME NOT NULL,
    duration_minutes INT NOT NULL,
    venue VARCHAR(255) NULL,
    max_marks DECIMAL(5,2) NOT NULL DEFAULT 100.00,
    passing_marks DECIMAL(5,2) NOT NULL DEFAULT 50.00,
    weight_percentage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    instructions TEXT NULL,
    created_by INT NOT NULL,
    status ENUM('draft', 'scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_course (course_id),
    INDEX idx_session (session_id),
    INDEX idx_semester (semester_id),
    INDEX idx_type (exam_type),
    INDEX idx_date (exam_date),
    INDEX idx_status (status),
    FOREIGN KEY (course_id) REFERENCES academic_courses(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES academic_semesters(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exam results
CREATE TABLE exam_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    student_id INT NOT NULL,
    marks_obtained DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    percentage DECIMAL(5,2) GENERATED ALWAYS AS (marks_obtained * 100 / (SELECT max_marks FROM examinations WHERE id = exam_id)) STORED,
    grade VARCHAR(5) NULL,
    grade_points DECIMAL(3,2) NULL,
    is_absent BOOLEAN DEFAULT FALSE,
    remarks TEXT NULL,
    submitted_by INT NULL,
    submission_date TIMESTAMP NULL,
    verified_by INT NULL,
    verification_date TIMESTAMP NULL,
    status ENUM('pending', 'submitted', 'verified', 'rejected', 'published') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_exam_student (exam_id, student_id),
    INDEX idx_exam (exam_id),
    INDEX idx_student (student_id),
    INDEX idx_marks (marks_obtained),
    INDEX idx_grade (grade),
    INDEX idx_status (status),
    FOREIGN KEY (exam_id) REFERENCES examinations(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (submitted_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Grade scales
CREATE TABLE grade_scales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grade_letter VARCHAR(5) NOT NULL UNIQUE,
    grade_point DECIMAL(3,2) NOT NULL,
    min_percentage DECIMAL(5,2) NOT NULL,
    max_percentage DECIMAL(5,2) NOT NULL,
    description VARCHAR(255) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_grade (grade_letter),
    INDEX idx_point (grade_point),
    INDEX idx_percentage_range (min_percentage, max_percentage),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attendance sessions
CREATE TABLE attendance_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    lecturer_id INT NOT NULL,
    session_id INT NOT NULL,
    semester_id INT NOT NULL,
    session_date DATE NOT NULL,
    session_time TIME NOT NULL,
    duration_minutes INT NOT NULL DEFAULT 60,
    session_type ENUM('lecture', 'lab', 'tutorial', 'seminar', 'practical') NOT NULL DEFAULT 'lecture',
    venue VARCHAR(255) NULL,
    topic VARCHAR(255) NULL,
    total_students INT DEFAULT 0,
    present_students INT DEFAULT 0,
    absent_students INT DEFAULT 0,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_course (course_id),
    INDEX idx_lecturer (lecturer_id),
    INDEX idx_session (session_id),
    INDEX idx_semester (semester_id),
    INDEX idx_date (session_date),
    INDEX idx_status (status),
    FOREIGN KEY (course_id) REFERENCES academic_courses(id) ON DELETE CASCADE,
    FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES academic_semesters(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attendance records
CREATE TABLE attendance_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attendance_session_id INT NOT NULL,
    student_id INT NOT NULL,
    attendance_status ENUM('present', 'absent', 'late', 'excused', 'medical_leave') NOT NULL DEFAULT 'present',
    arrival_time TIME NULL,
    departure_time TIME NULL,
    remarks TEXT NULL,
    marked_by INT NOT NULL,
    marked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified_by INT NULL,
    verified_at TIMESTAMP NULL,
    
    UNIQUE KEY unique_session_student (attendance_session_id, student_id),
    INDEX idx_session (attendance_session_id),
    INDEX idx_student (student_id),
    INDEX idx_status (attendance_status),
    INDEX idx_marked_by (marked_by),
    FOREIGN KEY (attendance_session_id) REFERENCES attendance_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PART 3: FINANCIAL MANAGEMENT SYSTEM
-- =====================================================

-- Drop existing financial tables
DROP TABLE IF EXISTS fee_categories;
DROP TABLE IF EXISTS fee_structure;
DROP TABLE IF EXISTS student_fee_accounts;
DROP TABLE IF EXISTS payment_methods;
DROP TABLE IF EXISTS payment_transactions;

-- Fee categories
CREATE TABLE fee_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_code VARCHAR(20) NOT NULL UNIQUE,
    category_name VARCHAR(255) NOT NULL,
    category_description TEXT NULL,
    category_type ENUM('tuition', 'accommodation', 'library', 'laboratory', 'examination', 'registration', 'development', 'other') NOT NULL,
    is_mandatory BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (category_code),
    INDEX idx_type (category_type),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fee structure
CREATE TABLE fee_structure (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    level INT NOT NULL,
    semester INT NOT NULL,
    fee_category_id INT NOT NULL,
    fee_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'UGX',
    payment_deadline DATE NULL,
    late_fee_amount DECIMAL(10,2) DEFAULT 0.00,
    late_fee_applied_after DATE NULL,
    is_active BOOLEAN DEFAULT TRUE,
    effective_from DATE NOT NULL,
    effective_to DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_fee_structure (program_id, level, semester, fee_category_id, effective_from),
    INDEX idx_program (program_id),
    INDEX idx_level_semester (level, semester),
    INDEX idx_category (fee_category_id),
    INDEX idx_active (is_active),
    FOREIGN KEY (program_id) REFERENCES academic_programs(id) ON DELETE CASCADE,
    FOREIGN KEY (fee_category_id) REFERENCES fee_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Student fee accounts
CREATE TABLE student_fee_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    program_id INT NOT NULL,
    session_id INT NOT NULL,
    semester_id INT NOT NULL,
    total_fees DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    balance_due DECIMAL(12,2) GENERATED ALWAYS AS (total_fees - amount_paid) STORED,
    last_payment_date DATE NULL,
    payment_status ENUM('unpaid', 'partial', 'paid', 'overpaid', 'waived') DEFAULT 'unpaid',
    due_date DATE NOT NULL,
    late_fee_applied BOOLEAN DEFAULT FALSE,
    late_fee_amount DECIMAL(10,2) DEFAULT 0.00,
    waiver_amount DECIMAL(10,2) DEFAULT 0.00,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    account_status ENUM('active', 'suspended', 'closed', 'transferred') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_student_session_semester (student_id, session_id, semester_id),
    INDEX idx_student (student_id),
    INDEX idx_program (program_id),
    INDEX idx_session (session_id),
    INDEX idx_semester (semester_id),
    INDEX idx_status (payment_status),
    INDEX idx_due_date (due_date),
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (program_id) REFERENCES academic_programs(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES academic_semesters(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payment methods
CREATE TABLE payment_methods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    method_code VARCHAR(20) NOT NULL UNIQUE,
    method_name VARCHAR(100) NOT NULL,
    method_description TEXT NULL,
    method_type ENUM('cash', 'bank_transfer', 'mobile_money', 'credit_card', 'debit_card', 'cheque', 'online', 'other') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    processing_fee_percentage DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (method_code),
    INDEX idx_type (method_type),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payment transactions
CREATE TABLE payment_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(100) NOT NULL UNIQUE,
    student_fee_account_id INT NOT NULL,
    student_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_method_id INT NOT NULL,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    transaction_status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded') DEFAULT 'pending',
    payment_reference VARCHAR(255) NULL,
    receipt_number VARCHAR(100) NULL,
    description TEXT NULL,
    processed_by INT NULL,
    processing_fee DECIMAL(10,2) DEFAULT 0.00,
    net_amount DECIMAL(12,2) GENERATED ALWAYS AS (amount - processing_fee) STORED,
    currency VARCHAR(3) DEFAULT 'UGX',
    verified_by INT NULL,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_fee_account (student_fee_account_id),
    INDEX idx_student (student_id),
    INDEX idx_method (payment_method_id),
    INDEX idx_status (transaction_status),
    INDEX idx_date (transaction_date),
    INDEX idx_receipt (receipt_number),
    FOREIGN KEY (student_fee_account_id) REFERENCES student_fee_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PART 4: COMMUNICATION SYSTEM
-- =====================================================

-- Drop existing communication tables
DROP TABLE IF EXISTS announcement_categories;
DROP TABLE IF EXISTS announcements;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS message_threads;
DROP TABLE IF EXISTS message_recipients;
DROP TABLE IF EXISTS notifications;

-- Announcement categories
CREATE TABLE announcement_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_code VARCHAR(20) NOT NULL UNIQUE,
    category_name VARCHAR(255) NOT NULL,
    category_description TEXT NULL,
    category_color VARCHAR(20) DEFAULT '#007bff',
    icon VARCHAR(50) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (category_code),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Announcements
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    category_id INT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    target_audience JSON NULL,
    announcement_type ENUM('general', 'academic', 'financial', 'administrative', 'emergency', 'event') NOT NULL DEFAULT 'general',
    start_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    end_date TIMESTAMP NULL,
    is_pinned BOOLEAN DEFAULT FALSE,
    requires_acknowledgment BOOLEAN DEFAULT FALSE,
    view_count INT DEFAULT 0,
    acknowledgment_count INT DEFAULT 0,
    status ENUM('draft', 'published', 'archived', 'cancelled') DEFAULT 'draft',
    created_by INT NOT NULL,
    published_by INT NULL,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_category (category_id),
    INDEX idx_priority (priority),
    INDEX idx_type (announcement_type),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_status (status),
    INDEX idx_pinned (is_pinned),
    FOREIGN KEY (category_id) REFERENCES announcement_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (published_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Message threads
CREATE TABLE message_threads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thread_title VARCHAR(255) NULL,
    thread_type ENUM('direct', 'group', 'system', 'support') NOT NULL DEFAULT 'direct',
    created_by INT NOT NULL,
    last_message_id INT NULL,
    last_message_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_archived BOOLEAN DEFAULT FALSE,
    message_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_type (thread_type),
    INDEX idx_created_by (created_by),
    INDEX idx_last_message (last_message_id),
    INDEX idx_active (is_active),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Messages
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thread_id INT NOT NULL,
    sender_id INT NOT NULL,
    message_content TEXT NOT NULL,
    message_type ENUM('text', 'file', 'image', 'video', 'audio', 'link') NOT NULL DEFAULT 'text',
    subject VARCHAR(255) NULL,
    is_edited BOOLEAN DEFAULT FALSE,
    edited_at TIMESTAMP NULL,
    is_deleted BOOLEAN DEFAULT FALSE,
    deleted_at TIMESTAMP NULL,
    reply_to_message_id INT NULL,
    is_system_message BOOLEAN DEFAULT FALSE,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_thread (thread_id),
    INDEX idx_sender (sender_id),
    INDEX idx_type (message_type),
    INDEX idx_priority (priority),
    INDEX idx_reply_to (reply_to_message_id),
    INDEX idx_created (created_at),
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reply_to_message_id) REFERENCES messages(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Message recipients
CREATE TABLE message_recipients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    recipient_id INT NOT NULL,
    delivery_status ENUM('pending', 'sent', 'delivered', 'read', 'failed', 'bounced') DEFAULT 'pending',
    read_at TIMESTAMP NULL,
    is_favorite BOOLEAN DEFAULT FALSE,
    is_archived BOOLEAN DEFAULT FALSE,
    is_deleted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_message_recipient (message_id, recipient_id),
    INDEX idx_message (message_id),
    INDEX idx_recipient (recipient_id),
    INDEX idx_status (delivery_status),
    INDEX idx_read (read_at),
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    notification_type ENUM('info', 'success', 'warning', 'error', 'system', 'message', 'announcement', 'reminder', 'alert') NOT NULL DEFAULT 'info',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    category VARCHAR(50) NULL,
    action_url VARCHAR(500) NULL,
    action_text VARCHAR(100) NULL,
    icon VARCHAR(50) NULL,
    source_type ENUM('system', 'user', 'announcement', 'message', 'payment', 'academic', 'other') NOT NULL DEFAULT 'system',
    source_id INT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    is_dismissed BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    dismissed_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_user (user_id),
    INDEX idx_type (notification_type),
    INDEX idx_priority (priority),
    INDEX idx_category (category),
    INDEX idx_source (source_type, source_id),
    INDEX idx_read (is_read),
    INDEX idx_dismissed (is_dismissed),
    INDEX idx_expires (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PART 5: DASHBOARD OPERATIONS
-- =====================================================

-- Drop existing dashboard tables
DROP TABLE IF EXISTS dashboard_comments;
DROP TABLE IF EXISTS dashboard_print_logs;
DROP TABLE IF EXISTS dashboard_send_logs;
DROP TABLE IF EXISTS dashboard_activity_logs;
DROP TABLE IF EXISTS dashboard_user_preferences;
DROP TABLE IF EXISTS dashboard_notifications;
DROP TABLE IF EXISTS dashboard_quick_actions;
DROP TABLE IF EXISTS dashboard_favorites;

-- Dashboard comments
CREATE TABLE dashboard_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NOT NULL,
    user_id INT NOT NULL,
    comment_text TEXT NOT NULL,
    parent_comment_id INT NULL,
    mentions JSON NULL,
    attachments JSON NULL,
    is_edited BOOLEAN DEFAULT FALSE,
    is_deleted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_user (user_id),
    INDEX idx_parent (parent_comment_id),
    INDEX idx_created (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_comment_id) REFERENCES dashboard_comments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dashboard print logs
CREATE TABLE dashboard_print_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    document_id INT NOT NULL,
    print_format VARCHAR(50) DEFAULT 'PDF',
    file_path VARCHAR(500) NULL,
    file_size INT NULL,
    print_status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    error_message TEXT NULL,
    print_count INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    
    INDEX idx_user (user_id),
    INDEX idx_document (document_type, document_id),
    INDEX idx_status (print_status),
    INDEX idx_created (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dashboard send logs
CREATE TABLE dashboard_send_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    send_type ENUM('email', 'sms', 'internal', 'notification', 'whatsapp') NOT NULL,
    recipient_type VARCHAR(50) NOT NULL,
    recipient_id INT NULL,
    recipient_contact VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NULL,
    message_content TEXT NOT NULL,
    attachments JSON NULL,
    send_status ENUM('pending', 'sent', 'delivered', 'failed', 'bounced') DEFAULT 'pending',
    delivery_status JSON NULL,
    error_message TEXT NULL,
    retry_count INT DEFAULT 0,
    sent_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user (user_id),
    INDEX idx_recipient (recipient_type, recipient_id),
    INDEX idx_status (send_status),
    INDEX idx_type (send_type),
    INDEX idx_created (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dashboard activity logs
CREATE TABLE dashboard_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NOT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    description TEXT NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    session_id VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user (user_id),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at),
    INDEX idx_session (session_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dashboard user preferences
CREATE TABLE dashboard_user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    preference_key VARCHAR(100) NOT NULL,
    preference_value JSON NOT NULL,
    category VARCHAR(50) DEFAULT 'general',
    is_system_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_user_preference (user_id, preference_key),
    INDEX idx_user (user_id),
    INDEX idx_category (category),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dashboard notifications
CREATE TABLE dashboard_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    notification_type ENUM('info', 'success', 'warning', 'error', 'system') DEFAULT 'info',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    action_url VARCHAR(500) NULL,
    action_text VARCHAR(100) NULL,
    icon VARCHAR(50) NULL,
    is_read BOOLEAN DEFAULT FALSE,
    is_dismissed BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    dismissed_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user (user_id),
    INDEX idx_read (is_read),
    INDEX idx_priority (priority),
    INDEX idx_type (notification_type),
    INDEX idx_created (created_at),
    INDEX idx_expires (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dashboard quick actions
CREATE TABLE dashboard_quick_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_name VARCHAR(100) NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    action_url VARCHAR(500) NOT NULL,
    action_icon VARCHAR(50) NULL,
    action_color VARCHAR(20) DEFAULT 'primary',
    is_favorite BOOLEAN DEFAULT FALSE,
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_user (user_id),
    INDEX idx_favorite (is_favorite),
    INDEX idx_order (display_order),
    INDEX idx_active (is_active),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dashboard favorites
CREATE TABLE dashboard_favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT NOT NULL,
    entity_name VARCHAR(255) NOT NULL,
    entity_url VARCHAR(500) NULL,
    category VARCHAR(50) DEFAULT 'general',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_user_entity (user_id, entity_type, entity_id),
    INDEX idx_user (user_id),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_category (category),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PART 6: LIBRARY MANAGEMENT
-- =====================================================

-- Drop existing library tables
DROP TABLE IF EXISTS books;
DROP TABLE IF EXISTS book_loans;

-- Books
CREATE TABLE books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_title VARCHAR(255) NOT NULL,
    book_author VARCHAR(255) NOT NULL,
    isbn VARCHAR(20) UNIQUE NULL,
    publisher VARCHAR(255) NULL,
    publication_year INT NULL,
    book_category VARCHAR(100) NOT NULL,
    book_description TEXT NULL,
    total_copies INT NOT NULL DEFAULT 1,
    available_copies INT NOT NULL DEFAULT 1,
    location VARCHAR(100) NULL,
    added_by INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_title (book_title),
    INDEX idx_author (book_author),
    INDEX idx_isbn (isbn),
    INDEX idx_category (book_category),
    INDEX idx_available (available_copies),
    FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Book loans
CREATE TABLE book_loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    student_id INT NOT NULL,
    loan_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE NULL,
    loan_status ENUM('active', 'returned', 'overdue', 'lost', 'damaged') DEFAULT 'active',
    fine_amount DECIMAL(10,2) DEFAULT 0.00,
    fine_paid BOOLEAN DEFAULT FALSE,
    issued_by INT NOT NULL,
    returned_by INT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_book (book_id),
    INDEX idx_student (student_id),
    INDEX idx_status (loan_status),
    INDEX idx_due_date (due_date),
    INDEX idx_issued_by (issued_by),
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (issued_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (returned_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PART 7: HOSTEL MANAGEMENT
-- =====================================================

-- Drop existing hostel tables
DROP TABLE IF EXISTS hostels;
DROP TABLE IF EXISTS rooms;
DROP TABLE IF EXISTS room_allocations;

-- Hostels
CREATE TABLE hostels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hostel_name VARCHAR(255) NOT NULL,
    hostel_code VARCHAR(20) NOT NULL UNIQUE,
    hostel_type ENUM('male', 'female', 'mixed') NOT NULL,
    total_rooms INT NOT NULL DEFAULT 0,
    capacity INT NOT NULL DEFAULT 0,
    occupied_rooms INT DEFAULT 0,
    occupied_beds INT DEFAULT 0,
    warden_id INT NULL,
    location VARCHAR(255) NULL,
    facilities JSON NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (hostel_code),
    INDEX idx_type (hostel_type),
    INDEX idx_active (is_active),
    INDEX idx_warden (warden_id),
    FOREIGN KEY (warden_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rooms
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hostel_id INT NOT NULL,
    room_number VARCHAR(20) NOT NULL,
    room_type ENUM('single', 'double', 'triple', 'quad', 'dormitory') NOT NULL,
    capacity INT NOT NULL DEFAULT 1,
    occupied_beds INT DEFAULT 0,
    floor_number INT DEFAULT 1,
    room_facilities JSON NULL,
    room_status ENUM('available', 'occupied', 'maintenance', 'unavailable') DEFAULT 'available',
    rent_per_semester DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_hostel_room (hostel_id, room_number),
    INDEX idx_hostel (hostel_id),
    INDEX idx_type (room_type),
    INDEX idx_status (room_status),
    FOREIGN KEY (hostel_id) REFERENCES hostels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Room allocations
CREATE TABLE room_allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL,
    student_id INT NOT NULL,
    allocation_date DATE NOT NULL,
    allocation_type ENUM('regular', 'temporary', 'exchange') NOT NULL DEFAULT 'regular',
    session_id INT NOT NULL,
    semester_id INT NOT NULL,
    allocation_status ENUM('active', 'transferred', 'vacated', 'terminated') DEFAULT 'active',
    rent_amount DECIMAL(10,2) DEFAULT 0.00,
    rent_paid BOOLEAN DEFAULT FALSE,
    allocated_by INT NOT NULL,
    vacated_date DATE NULL,
    vacated_by INT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_student_session_semester (student_id, session_id, semester_id),
    INDEX idx_room (room_id),
    INDEX idx_student (student_id),
    INDEX idx_session (session_id),
    INDEX idx_semester (semester_id),
    INDEX idx_status (allocation_status),
    INDEX idx_allocated_by (allocated_by),
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES academic_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (semester_id) REFERENCES academic_semesters(id) ON DELETE CASCADE,
    FOREIGN KEY (allocated_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (vacated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PART 8: API INTEGRATION
-- =====================================================

-- Drop existing API tables
DROP TABLE IF EXISTS api_endpoints;
DROP TABLE IF EXISTS api_keys;
DROP TABLE IF EXISTS api_logs;

-- API endpoints
CREATE TABLE api_endpoints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    endpoint_name VARCHAR(100) NOT NULL,
    endpoint_path VARCHAR(255) NOT NULL,
    http_method ENUM('GET', 'POST', 'PUT', 'DELETE', 'PATCH') NOT NULL,
    description TEXT NULL,
    requires_auth BOOLEAN DEFAULT TRUE,
    rate_limit_per_hour INT DEFAULT 100,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_path (endpoint_path),
    INDEX idx_method (http_method),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API keys
CREATE TABLE api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(100) NOT NULL,
    api_key VARCHAR(255) NOT NULL UNIQUE,
    key_hash VARCHAR(255) NOT NULL,
    user_id INT NOT NULL,
    permissions JSON NULL,
    rate_limit_per_hour INT DEFAULT 100,
    is_active BOOLEAN DEFAULT TRUE,
    expires_at TIMESTAMP NULL,
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_name (key_name),
    INDEX idx_user (user_id),
    INDEX idx_active (is_active),
    INDEX idx_expires (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API logs
CREATE TABLE api_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_endpoint VARCHAR(255) NOT NULL,
    http_method VARCHAR(10) NOT NULL,
    request_headers JSON NULL,
    request_body TEXT NULL,
    response_status INT NULL,
    response_headers JSON NULL,
    response_body TEXT NULL,
    execution_time INT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    user_id INT NULL,
    session_id VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_endpoint (api_endpoint),
    INDEX idx_method (http_method),
    INDEX idx_status (response_status),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at),
    INDEX idx_execution_time (execution_time),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- PART 9: INSERT DEFAULT DATA
-- =====================================================

-- Insert system settings
INSERT INTO system_settings (setting_key, setting_value, setting_description, setting_type, is_public) VALUES
('school_name', 'International School of Nursing and Midwifery', 'School name', 'string', TRUE),
('school_address', 'Kampala, Uganda', 'School address', 'string', TRUE),
('school_phone', '+256-123-456789', 'School phone number', 'string', TRUE),
('school_email', 'info@isnm.edu.ug', 'School email', 'string', TRUE),
('academic_year', '2024/2025', 'Current academic year', 'string', TRUE),
('timezone', 'Africa/Kampala', 'Default timezone', 'string', TRUE),
('currency', 'UGX', 'Default currency', 'string', TRUE),
('max_login_attempts', '5', 'Maximum login attempts before lockout', 'number', FALSE),
('session_timeout', '3600', 'Session timeout in seconds', 'number', FALSE);

-- Insert default academic programs
INSERT INTO academic_programs (program_code, program_name, program_description, program_level, department, duration_years, total_credits_required) VALUES
('BSN', 'Bachelor of Science in Nursing', 'Comprehensive nursing program', 'Bachelor', 'Nursing', 4.0, 240),
('BMS', 'Bachelor of Midwifery Science', 'Specialized midwifery program', 'Bachelor', 'Midwifery', 4.0, 220),
('DIPN', 'Diploma in Nursing', 'Practical nursing program', 'Diploma', 'Nursing', 3.0, 180),
('DIPM', 'Diploma in Midwifery', 'Practical midwifery program', 'Diploma', 'Midwifery', 3.0, 160);

-- Insert default academic sessions
INSERT INTO academic_sessions (session_name, session_code, start_date, end_date, is_current, registration_start_date, registration_end_date, exam_start_date, exam_end_date, status) VALUES
('2023/2024 Academic Year', '2023_2024', '2023-09-01', '2024-08-31', FALSE, '2023-08-15', '2023-09-15', '2024-04-15', '2024-05-15', 'completed'),
('2024/2025 Academic Year', '2024_2025', '2024-09-01', '2025-08-31', TRUE, '2024-08-15', '2024-09-15', '2025-04-15', '2025-05-15', 'active'),
('2025/2026 Academic Year', '2025_2026', '2025-09-01', '2026-08-31', FALSE, '2025-08-15', '2025-09-15', '2026-04-15', '2026-05-15', 'upcoming');

-- Insert default semesters for current session
INSERT INTO academic_semesters (session_id, semester_number, semester_name, start_date, end_date, is_current, registration_deadline, exam_period_start, exam_period_end, status) VALUES
(2, 1, 'First Semester 2024/2025', '2024-09-01', '2025-01-31', TRUE, '2024-09-15', '2025-01-15', '2025-01-31', 'active'),
(2, 2, 'Second Semester 2024/2025', '2025-02-01', '2025-06-30', FALSE, '2025-02-15', '2025-03-15', '2025-06-15', 'upcoming');

-- Insert default fee categories
INSERT INTO fee_categories (category_code, category_name, category_description, category_type, is_mandatory) VALUES
('TUIT', 'Tuition Fees', 'Academic tuition fees', 'tuition', TRUE),
('REGF', 'Registration Fees', 'One-time registration fees', 'registration', TRUE),
('LIBF', 'Library Fees', 'Library access fees', 'library', TRUE),
('LABF', 'Laboratory Fees', 'Laboratory equipment fees', 'laboratory', TRUE),
('EXAMF', 'Examination Fees', 'Internal and external examination fees', 'examination', TRUE),
('HOST', 'Hostel Fees', 'Accommodation fees', 'accommodation', FALSE),
('DEVF', 'Development Fees', 'School development fees', 'development', TRUE);

-- Insert default payment methods
INSERT INTO payment_methods (method_code, method_name, method_description, method_type, processing_fee_percentage) VALUES
('CASH', 'Cash Payment', 'Direct cash payment', 'cash', 0.00),
('BANK', 'Bank Transfer', 'Direct bank transfer', 'bank_transfer', 0.00),
('MMT', 'Mobile Money', 'Mobile money payment', 'mobile_money', 2.50),
('CARD', 'Credit/Debit Card', 'Card payment', 'credit_card', 3.00);

-- Insert default grade scales
INSERT INTO grade_scales (grade_letter, grade_point, min_percentage, max_percentage, description) VALUES
('A+', 4.00, 85.00, 100.00, 'Excellent'),
('A', 4.00, 80.00, 84.99, 'Excellent'),
('B+', 3.50, 75.00, 79.99, 'Very Good'),
('B', 3.00, 70.00, 74.99, 'Good'),
('C+', 2.50, 65.00, 69.99, 'Fairly Good'),
('C', 2.00, 60.00, 64.99, 'Satisfactory'),
('D+', 1.50, 55.00, 59.99, 'Poor'),
('D', 1.00, 50.00, 54.99, 'Pass'),
('F', 0.00, 0.00, 49.99, 'Fail');

-- Insert default announcement categories
INSERT INTO announcement_categories (category_code, category_name, category_description, category_color, icon) VALUES
('GEN', 'General Announcements', 'General school announcements', '#007bff', 'fas fa-bullhorn'),
('ACA', 'Academic Updates', 'Academic calendar and notices', '#28a745', 'fas fa-graduation-cap'),
('FIN', 'Financial Notices', 'Fee payment deadlines', '#ffc107', 'fas fa-dollar-sign'),
('EMG', 'Emergency', 'Emergency announcements', '#dc3545', 'fas fa-exclamation-triangle');

-- Insert default API endpoints
INSERT INTO api_endpoints (endpoint_name, endpoint_path, http_method, description, requires_auth, rate_limit_per_hour) VALUES
('Student Login', '/api/auth/student/login', 'POST', 'Student authentication endpoint', FALSE, 100),
('Staff Login', '/api/auth/staff/login', 'POST', 'Staff authentication endpoint', FALSE, 100),
('Get Student Profile', '/api/students/{id}', 'GET', 'Get student profile information', TRUE, 1000),
('Get Student Fees', '/api/students/{id}/fees', 'GET', 'Get student fee information', TRUE, 500),
('Get Academic Records', '/api/students/{id}/academic', 'GET', 'Get student academic records', TRUE, 500),
('Submit Payment', '/api/payments', 'POST', 'Submit payment transaction', TRUE, 200),
('Get Announcements', '/api/announcements', 'GET', 'Get announcements', TRUE, 1000),
('Get Notifications', '/api/notifications', 'GET', 'Get user notifications', TRUE, 1000);

-- Insert sample users (students and staff)
INSERT INTO users (username, full_name, email, phone, index_number, password, role, type, gender, status) VALUES
-- Students
('alice.student', 'Alice Student', 'alice@student.isnm.edu.ug', '256701234567', 'STU2024001', NULL, 'student', 'student', 'Female', 'active'),
('bob.student', 'Bob Student', 'bob@student.isnm.edu.ug', '256702345678', 'STU2024002', NULL, 'student', 'student', 'Male', 'active'),
('carol.student', 'Carol Student', 'carol@student.isnm.edu.ug', '256703456789', 'STU2024003', NULL, 'student', 'student', 'Female', 'active'),
-- Staff (with hashed passwords - default: password123)
('admin', 'System Administrator', 'admin@isnm.edu.ug', '256712345678', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'staff', 'Male', 'active'),
('lecturer1', 'John Lecturer', 'john.lecturer@isnm.edu.ug', '256713456789', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lecturer', 'staff', 'Male', 'active'),
('secretary1', 'Jane Secretary', 'jane.secretary@isnm.edu.ug', '256714567890', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Secretary', 'staff', 'Female', 'active');

-- Insert sample student academic records
INSERT INTO student_academic_records (student_id, program_id, current_level, current_semester, admission_session_id, admission_semester_id, admission_date) VALUES
(1, 1, 1, 1, 2, 3, '2024-09-01'),
(2, 1, 1, 1, 2, 3, '2024-09-01'),
(3, 2, 1, 1, 2, 3, '2024-09-01');

-- Insert sample courses
INSERT INTO academic_courses (course_code, course_title, course_description, program_id, level, semester, credit_hours, contact_hours, course_type) VALUES
('NSC101', 'Fundamentals of Nursing', 'Introduction to nursing basics', 1, 1, 1, 4.0, 60, 'core'),
('NSC102', 'Anatomy and Physiology', 'Human body structure and function', 1, 1, 1, 5.0, 75, 'core'),
('MID101', 'Foundations of Midwifery', 'Introduction to midwifery practice', 2, 1, 1, 4.0, 60, 'core'),
('MID102', 'Reproductive Anatomy', 'Female reproductive system', 2, 1, 1, 4.0, 60, 'core');

-- Insert sample fee structure
INSERT INTO fee_structure (program_id, level, semester, fee_category_id, fee_amount, payment_deadline, effective_from) VALUES
(1, 1, 1, 1, 2500000.00, '2024-09-15', '2024-09-01'), -- Tuition
(1, 1, 1, 2, 150000.00, '2024-09-15', '2024-09-01'), -- Registration
(1, 1, 1, 3, 100000.00, '2024-09-15', '2024-09-01'), -- Library
(2, 1, 1, 1, 2300000.00, '2024-09-15', '2024-09-01'), -- Tuition
(2, 1, 1, 2, 150000.00, '2024-09-15', '2024-09-01'); -- Registration

-- Insert sample books
INSERT INTO books (book_title, book_author, isbn, publisher, publication_year, book_category, total_copies, available_copies, added_by) VALUES
('Fundamentals of Nursing', 'Patricia Potter', '978-0323534153', 'Elsevier', 2021, 'Nursing', 10, 8, 5),
('Anatomy and Physiology', 'Elaine Marieb', '978-0134580999', 'Pearson', 2020, 'Medical', 15, 12, 5),
('Midwifery Essentials', 'Helen Varney', '978-0721636362', 'Lippincott', 2019, 'Midwifery', 8, 6, 5);

-- Insert sample hostels
INSERT INTO hostels (hostel_name, hostel_code, hostel_type, total_rooms, capacity, warden_id, location) VALUES
('Nursing Hostel A', 'HNA', 'female', 50, 200, 6, 'Main Campus'),
('Nursing Hostel B', 'HNB', 'male', 40, 160, 6, 'Main Campus'),
('Midwifery Hostel', 'HM', 'female', 30, 120, 6, 'Main Campus');

-- Insert sample rooms
INSERT INTO rooms (hostel_id, room_number, room_type, capacity, occupied_beds, floor_number, rent_per_semester) VALUES
(1, '101', 'quad', 4, 0, 1, 500000.00),
(1, '102', 'quad', 4, 0, 1, 500000.00),
(2, '201', 'double', 2, 0, 2, 600000.00),
(3, '301', 'triple', 3, 0, 3, 550000.00);

-- =====================================================
-- PART 10: CREATE STORED PROCEDURES
-- =====================================================

DELIMITER //

-- Core authentication procedure
CREATE PROCEDURE authenticate_user(
    IN p_identifier VARCHAR(255), -- email, phone, or index_number
    IN p_password VARCHAR(255),
    IN p_user_type ENUM('student', 'staff')
)
BEGIN
    DECLARE v_user_id INT;
    DECLARE v_password_hash VARCHAR(255);
    DECLARE v_full_name VARCHAR(255);
    DECLARE v_user_role VARCHAR(100);
    DECLARE v_account_locked BOOLEAN;
    DECLARE v_attempts INT;
    
    -- Check if account is locked
    SELECT is_locked, attempt_count INTO v_account_locked, v_attempts
    FROM login_attempts 
    WHERE identifier = p_identifier;
    
    IF v_account_locked = TRUE THEN
        SELECT 'Account locked. Please try again later.' as message, 'locked' as status;
    ELSE
        -- Get user based on type
        IF p_user_type = 'student' THEN
            SELECT id, full_name, role INTO v_user_id, v_full_name, v_user_role
            FROM users 
            WHERE index_number = p_identifier 
            AND type = 'student' 
            AND status = 'active';
            
            IF v_user_id IS NOT NULL THEN
                -- Student login successful (no password required)
                UPDATE login_attempts SET attempt_count = 0, is_locked = FALSE WHERE identifier = p_identifier;
                SELECT v_user_id as user_id, v_full_name, v_user_role, 'student' as user_type, 'success' as status;
            ELSE
                -- Failed login
                CALL record_failed_attempt(p_identifier);
                SELECT 'Invalid student credentials' as message, 'failed' as status;
            END IF;
            
        ELSE -- Staff login
            SELECT id, full_name, role, password INTO v_user_id, v_full_name, v_user_role, v_password_hash
            FROM users 
            WHERE email = p_identifier 
            AND type = 'staff' 
            AND status = 'active';
            
            IF v_user_id IS NOT NULL AND p_password = v_password_hash THEN
                -- Staff login successful (simplified for demo)
                UPDATE login_attempts SET attempt_count = 0, is_locked = FALSE WHERE identifier = p_identifier;
                SELECT v_user_id as user_id, v_full_name, v_user_role, 'staff' as user_type, 'success' as status;
            ELSE
                -- Failed login
                CALL record_failed_attempt(p_identifier);
                SELECT 'Invalid staff credentials' as message, 'failed' as status;
            END IF;
        END IF;
    END IF;
END //

-- Record failed login attempt
CREATE PROCEDURE record_failed_attempt(IN p_identifier VARCHAR(255))
BEGIN
    DECLARE v_attempts INT;
    DECLARE v_max_attempts INT DEFAULT 5;
    
    -- Get current attempts or create new record
    SELECT COALESCE(attempt_count, 0) INTO v_attempts
    FROM login_attempts 
    WHERE identifier = p_identifier;
    
    IF v_attempts IS NULL THEN
        INSERT INTO login_attempts (identifier, attempt_count) VALUES (p_identifier, 1);
    ELSE
        UPDATE login_attempts 
        SET attempt_count = attempt_count + 1,
            is_locked = CASE WHEN attempt_count + 1 >= v_max_attempts THEN TRUE ELSE FALSE END,
            locked_until = CASE WHEN attempt_count + 1 >= v_max_attempts THEN DATE_ADD(NOW(), INTERVAL 30 MINUTE) ELSE NULL END
        WHERE identifier = p_identifier;
    END IF;
END //

-- Create user session
CREATE PROCEDURE create_user_session(
    IN p_user_id INT,
    IN p_session_id VARCHAR(255),
    IN p_ip_address VARCHAR(45),
    IN p_user_agent TEXT
)
BEGIN
    -- Create session record
    INSERT INTO user_sessions (
        session_id, user_id, ip_address, user_agent, expires_at
    ) VALUES (
        p_session_id, p_user_id, p_ip_address, p_user_agent, DATE_ADD(NOW(), INTERVAL 24 HOUR)
    );
    
    -- Update last login
    UPDATE users 
    SET last_login = CURRENT_TIMESTAMP 
    WHERE id = p_user_id;
    
    SELECT 'Session created successfully' as message;
END //

-- Get student dashboard data
CREATE PROCEDURE get_student_dashboard_data(IN p_student_id INT)
BEGIN
    -- Get student information
    SELECT u.*, sar.program_id, ap.program_name, sar.current_level, sar.current_semester
    FROM users u
    JOIN student_academic_records sar ON u.id = sar.student_id
    JOIN academic_programs ap ON sar.program_id = ap.id
    WHERE u.id = p_student_id;
    
    -- Get fee account information
    SELECT * FROM student_fee_accounts 
    WHERE student_id = p_student_id 
    AND session_id = (SELECT id FROM academic_sessions WHERE is_current = TRUE)
    AND semester_id = (SELECT id FROM academic_semesters WHERE is_current = TRUE);
    
    -- Get recent notifications
    SELECT * FROM notifications 
    WHERE user_id = p_student_id 
    AND is_read = FALSE 
    ORDER BY created_at DESC 
    LIMIT 10;
    
    -- Get course registrations
    SELECT cr.*, ac.course_code, ac.course_title
    FROM course_registrations cr
    JOIN academic_courses ac ON cr.course_id = ac.id
    WHERE cr.student_id = p_student_id 
    AND cr.session_id = (SELECT id FROM academic_sessions WHERE is_current = TRUE)
    AND cr.semester_id = (SELECT id FROM academic_semesters WHERE is_current = TRUE);
END //

-- Get staff dashboard data
CREATE PROCEDURE get_staff_dashboard_data(IN p_staff_id INT)
BEGIN
    -- Get staff information
    SELECT * FROM users WHERE id = p_staff_id;
    
    -- Get system statistics based on role
    SELECT 
        (SELECT COUNT(*) FROM users WHERE type = 'student' AND status = 'active') as total_students,
        (SELECT COUNT(*) FROM users WHERE type = 'staff' AND status = 'active') as total_staff,
        (SELECT COUNT(*) FROM student_fee_accounts WHERE payment_status = 'unpaid') as unpaid_accounts,
        (SELECT COUNT(*) FROM announcements WHERE status = 'published') as active_announcements,
        (SELECT COUNT(*) FROM notifications WHERE is_read = FALSE) as unread_notifications;
    
    -- Get recent activities
    SELECT * FROM dashboard_activity_logs 
    ORDER BY created_at DESC 
    LIMIT 10;
END //

-- Process payment
CREATE PROCEDURE process_payment(
    IN p_student_fee_account_id INT,
    IN p_student_id INT,
    IN p_amount DECIMAL(12,2),
    IN p_payment_method_id INT,
    IN p_payment_reference VARCHAR(255),
    IN p_processed_by INT
)
BEGIN
    DECLARE v_transaction_id VARCHAR(100);
    DECLARE v_receipt_number VARCHAR(100);
    DECLARE v_processing_fee DECIMAL(10,2);
    DECLARE v_net_amount DECIMAL(12,2);
    
    -- Get processing fee
    SELECT processing_fee_percentage INTO v_processing_fee
    FROM payment_methods
    WHERE id = p_payment_method_id;
    
    -- Calculate amounts
    SET v_processing_fee = p_amount * (v_processing_fee / 100);
    SET v_net_amount = p_amount - v_processing_fee;
    
    -- Generate IDs
    SET v_transaction_id = CONCAT('TXN', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'), LPAD(p_student_id, 6, '0'));
    SET v_receipt_number = CONCAT('RCP', DATE_FORMAT(NOW(), '%Y%m%d'), LPAD(FLOOR(RAND() * 10000), 4, '0'));
    
    -- Insert payment transaction
    INSERT INTO payment_transactions (
        transaction_id, student_fee_account_id, student_id, amount, payment_method_id,
        payment_reference, receipt_number, processed_by, processing_fee, net_amount,
        transaction_status
    ) VALUES (
        v_transaction_id, p_student_fee_account_id, p_student_id, p_amount, p_payment_method_id,
        p_payment_reference, v_receipt_number, p_processed_by, v_processing_fee, v_net_amount,
        'completed'
    );
    
    -- Update fee account
    UPDATE student_fee_accounts 
    SET amount_paid = amount_paid + v_net_amount,
        last_payment_date = CURDATE(),
        payment_status = CASE 
            WHEN amount_paid + v_net_amount >= total_fees THEN 'paid'
            WHEN amount_paid + v_net_amount > 0 THEN 'partial'
            ELSE 'unpaid'
        END
    WHERE id = p_student_fee_account_id;
    
    -- Log activity
    INSERT INTO dashboard_activity_logs (
        user_id, action, entity_type, entity_id, description
    ) VALUES (
        p_student_id, 'payment', 'fee_account', p_student_fee_account_id,
        CONCAT('Payment of ', p_amount, ' processed')
    );
    
    SELECT v_transaction_id as transaction_id, v_receipt_number as receipt_number, v_net_amount as net_amount;
END //

DELIMITER ;

-- =====================================================
-- PART 11: CREATE VIEWS
-- =====================================================

-- Student profile view
CREATE VIEW student_profile_view AS
SELECT 
    u.id as student_id,
    u.full_name,
    u.index_number,
    u.email,
    u.phone,
    u.gender,
    ap.program_name,
    sar.current_level,
    sar.current_semester,
    sar.academic_status,
    sar.gpa_cumulative,
    sar.total_credits_earned,
    u.status,
    u.last_login
FROM users u
JOIN student_academic_records sar ON u.id = sar.student_id
JOIN academic_programs ap ON sar.program_id = ap.id
WHERE u.type = 'student';

-- Staff profile view
CREATE VIEW staff_profile_view AS
SELECT 
    u.id as staff_id,
    u.full_name,
    u.email,
    u.phone,
    u.role,
    u.gender,
    u.status,
    u.last_login
FROM users u
WHERE u.type = 'staff';

-- Fee collection summary view
CREATE VIEW fee_collection_summary AS
SELECT 
    ap.program_name,
    s.session_name,
    sem.semester_name,
    COUNT(DISTINCT sfa.student_id) as total_students,
    SUM(sfa.total_fees) as total_fees_required,
    SUM(sfa.amount_paid) as total_fees_collected,
    SUM(sfa.balance_due) as total_outstanding,
    ROUND((SUM(sfa.amount_paid) / SUM(sfa.total_fees)) * 100, 2) as collection_rate
FROM student_fee_accounts sfa
JOIN academic_programs ap ON sfa.program_id = ap.id
JOIN academic_sessions s ON sfa.session_id = s.id
JOIN academic_semesters sem ON sfa.semester_id = sem.id
GROUP BY ap.program_name, s.session_name, sem.semester_name;

-- Academic performance summary view
CREATE VIEW academic_performance_summary AS
SELECT 
    ac.course_code,
    ac.course_title,
    COUNT(DISTINCT er.student_id) as total_students,
    COUNT(CASE WHEN er.marks_obtained >= e.passing_marks THEN 1 END) as passed_students,
    COUNT(CASE WHEN er.marks_obtained < e.passing_marks THEN 1 END) as failed_students,
    ROUND(AVG(er.marks_obtained), 2) as average_marks,
    ROUND((COUNT(CASE WHEN er.marks_obtained >= e.passing_marks THEN 1 END) * 100.0 / COUNT(*)), 2) as pass_rate
FROM examinations e
JOIN academic_courses ac ON e.course_id = ac.id
LEFT JOIN exam_results er ON e.id = er.exam_id
GROUP BY e.id, ac.course_code, ac.course_title;

-- =====================================================
-- PART 12: CREATE TRIGGERS
-- =====================================================

DELIMITER //

-- Trigger to log user activities
CREATE TRIGGER after_user_login
AFTER UPDATE ON users
FOR EACH ROW
BEGIN
    IF NEW.last_login != OLD.last_login THEN
        INSERT INTO dashboard_activity_logs (
            user_id, action, entity_type, entity_id, description
        ) VALUES (
            NEW.id, 'login', 'user', NEW.id,
            'User logged in successfully'
        );
    END IF;
END //

-- Trigger to update book availability
CREATE TRIGGER after_book_loan_insert
AFTER INSERT ON book_loans
FOR EACH ROW
BEGIN
    UPDATE books 
    SET available_copies = available_copies - 1
    WHERE id = NEW.book_id;
    
    INSERT INTO dashboard_activity_logs (
        user_id, action, entity_type, entity_id, description
    ) VALUES (
        NEW.student_id, 'borrow', 'book', NEW.book_id,
        CONCAT('Borrowed book: ', (SELECT book_title FROM books WHERE id = NEW.book_id))
    );
END //

-- Trigger to restore book availability
CREATE TRIGGER after_book_loan_update
AFTER UPDATE ON book_loans
FOR EACH ROW
BEGIN
    IF OLD.loan_status = 'active' AND NEW.loan_status = 'returned' THEN
        UPDATE books 
        SET available_copies = available_copies + 1
        WHERE id = NEW.book_id;
        
        INSERT INTO dashboard_activity_logs (
            user_id, action, entity_type, entity_id, description
        ) VALUES (
            NEW.student_id, 'return', 'book', NEW.book_id,
            CONCAT('Returned book: ', (SELECT book_title FROM books WHERE id = NEW.book_id))
        );
    END IF;
END //

-- Trigger to update room occupancy
CREATE TRIGGER after_room_allocation_insert
AFTER INSERT ON room_allocations
FOR EACH ROW
BEGIN
    UPDATE rooms 
    SET occupied_beds = occupied_beds + 1
    WHERE id = NEW.room_id;
    
    UPDATE hostels 
    SET occupied_beds = occupied_beds + 1
    WHERE id = (SELECT hostel_id FROM rooms WHERE id = NEW.room_id);
END //

-- Trigger to update room occupancy on vacate
CREATE TRIGGER after_room_allocation_update
AFTER UPDATE ON room_allocations
FOR EACH ROW
BEGIN
    IF OLD.allocation_status = 'active' AND NEW.allocation_status = 'vacated' THEN
        UPDATE rooms 
        SET occupied_beds = occupied_beds - 1
        WHERE id = NEW.room_id;
        
        UPDATE hostels 
        SET occupied_beds = occupied_beds - 1
        WHERE id = (SELECT hostel_id FROM rooms WHERE id = NEW.room_id);
    END IF;
END //

DELIMITER ;

-- =====================================================
-- PART 13: RE-ENABLE FOREIGN KEY CHECKS
-- =====================================================

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- PART 14: CREATE DEFAULT USER PREFERENCES
-- =====================================================

-- Create default user preferences for all users
INSERT INTO dashboard_user_preferences (user_id, preference_key, preference_value, category, is_system_default)
SELECT 
    id as user_id,
    'theme' as preference_key,
    '{"theme": "light", "sidebar": "expanded", "notifications": "enabled"}' as preference_value,
    'ui' as category,
    TRUE as is_system_default
FROM users;

INSERT INTO dashboard_user_preferences (user_id, preference_key, preference_value, category, is_system_default)
SELECT 
    id as user_id,
    'notifications' as preference_key,
    '{"email": true, "sms": false, "push": true, "desktop": true}' as preference_value,
    'notifications' as category,
    TRUE as is_system_default
FROM users;

-- =====================================================
-- SETUP COMPLETE MESSAGE
-- =====================================================

SELECT 'ISNM Complete Error-Free System Setup Complete!' as status,
       COUNT(*) as total_tables_created
FROM information_schema.tables 
WHERE table_schema = 'isnm_db';

SELECT 'System Components:' as component,
       COUNT(*) as table_count
FROM information_schema.tables 
WHERE table_schema = 'isnm_db'
GROUP BY 
    CASE 
        WHEN table_name IN ('users', 'login_attempts', 'user_sessions', 'password_resets', 'system_settings') THEN 'Authentication System'
        WHEN table_name LIKE 'academic_%' OR table_name IN ('academic_programs', 'academic_sessions', 'academic_semesters', 'academic_courses', 'student_academic_records', 'course_registrations', 'examinations', 'exam_results', 'grade_scales', 'attendance_sessions', 'attendance_records') THEN 'Academic Management'
        WHEN table_name LIKE 'fee_%' OR table_name IN ('payment_methods', 'payment_transactions') THEN 'Financial Management'
        WHEN table_name IN ('announcements', 'announcement_categories', 'messages', 'message_threads', 'message_recipients', 'notifications') THEN 'Communication System'
        WHEN table_name LIKE 'dashboard_%' THEN 'Dashboard Operations'
        WHEN table_name IN ('books', 'book_loans') THEN 'Library Management'
        WHEN table_name IN ('hostels', 'rooms', 'room_allocations') THEN 'Hostel Management'
        WHEN table_name LIKE 'api_%' THEN 'API Integration'
        ELSE 'Other'
    END
ORDER BY table_count DESC;
