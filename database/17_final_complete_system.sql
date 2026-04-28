-- ISNM School Management System - Final Complete System Setup
-- Master SQL file to set up the entire database system with all dashboard operations
-- This file includes all tables, procedures, and functionality for complete dashboard operations

USE isnm_db;

-- ========================================
-- SYSTEM INITIALIZATION AND SETUP
-- ========================================

-- Disable foreign key checks for initial setup
SET FOREIGN_KEY_CHECKS = 0;

-- ========================================
-- CORE AUTHENTICATION AND USER MANAGEMENT
-- ========================================

-- Create users table if it doesn't exist
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    index_number VARCHAR(50) UNIQUE,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE,
    phone VARCHAR(20),
    password VARCHAR(255),
    role VARCHAR(50) NOT NULL,
    type ENUM('student', 'staff') NOT NULL,
    status ENUM('active', 'inactive', 'suspended', 'deleted') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    address TEXT,
    emergency_contact_name VARCHAR(255),
    emergency_contact_phone VARCHAR(20),
    profile_picture VARCHAR(500),
    
    INDEX idx_email (email),
    INDEX idx_index_number (index_number),
    INDEX idx_role (role),
    INDEX idx_type (type),
    INDEX idx_status (status),
    INDEX idx_login_attempts (login_attempts),
    INDEX idx_locked_until (locked_until)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Login attempts tracking
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_identifier VARCHAR(255) NOT NULL,
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

-- Session management
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

-- Password reset tokens
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    ip_address VARCHAR(45),
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_expires_at (expires_at),
    INDEX idx_used (used)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- ACADEMIC MANAGEMENT
-- ========================================

-- Programs
CREATE TABLE IF NOT EXISTS programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_code VARCHAR(20) NOT NULL UNIQUE,
    program_name VARCHAR(255) NOT NULL,
    program_type ENUM('certificate', 'diploma', 'degree') NOT NULL,
    duration_years DECIMAL(3,1) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_program_code (program_code),
    INDEX idx_program_type (program_type),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Courses
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    course_name VARCHAR(255) NOT NULL,
    program_id INT NOT NULL,
    semester ENUM('year1_sem1', 'year1_sem2', 'year2_sem1', 'year2_sem2', 'year3_sem1', 'year3_sem2') NOT NULL,
    credits DECIMAL(4,1) NOT NULL,
    contact_hours_per_week INT DEFAULT 3,
    description TEXT,
    prerequisites TEXT,
    learning_outcomes TEXT,
    assessment_methods TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_course_code (course_code),
    INDEX idx_program_id (program_id),
    INDEX idx_semester (semester),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Course assignments
CREATE TABLE IF NOT EXISTS course_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    staff_id INT NOT NULL,
    role ENUM('lecturer', 'assistant', 'coordinator') DEFAULT 'lecturer',
    assigned_date DATE NOT NULL,
    end_date DATE NULL,
    status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
    assigned_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id),
    UNIQUE KEY unique_course_staff (course_id, staff_id, status),
    INDEX idx_course_id (course_id),
    INDEX idx_staff_id (staff_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Student academic records
CREATE TABLE IF NOT EXISTS student_academic_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    semester ENUM('year1_sem1', 'year1_sem2', 'year2_sem1', 'year2_sem2', 'year3_sem1', 'year3_sem2') NOT NULL,
    academic_year VARCHAR(9) NOT NULL,
    registration_date DATE NOT NULL,
    grade DECIMAL(4,2),
    grade_letter VARCHAR(2),
    gpa_points DECIMAL(3,2),
    attendance_percentage DECIMAL(5,2) DEFAULT 0,
    status ENUM('registered', 'in_progress', 'completed', 'failed', 'withdrawn', 'deferred') DEFAULT 'registered',
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_course_semester (student_id, course_id, semester, academic_year),
    INDEX idx_student_id (student_id),
    INDEX idx_course_id (course_id),
    INDEX idx_semester (semester),
    INDEX idx_academic_year (academic_year),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Examinations
CREATE TABLE IF NOT EXISTS examinations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    exam_name VARCHAR(255) NOT NULL,
    exam_type ENUM('quiz', 'assignment', 'midterm', 'final', 'practical', 'oral', 'project') NOT NULL,
    total_marks DECIMAL(5,2) NOT NULL,
    passing_marks DECIMAL(5,2) NOT NULL,
    exam_date DATE NOT NULL,
    exam_duration INT NOT NULL,
    exam_location VARCHAR(255),
    instructions TEXT,
    created_by INT NOT NULL,
    status ENUM('draft', 'scheduled', 'in_progress', 'completed', 'cancelled', 'postponed') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_course_id (course_id),
    INDEX idx_exam_type (exam_type),
    INDEX idx_exam_date (exam_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exam results
CREATE TABLE IF NOT EXISTS exam_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    student_id INT NOT NULL,
    marks_obtained DECIMAL(5,2) NOT NULL,
    grade VARCHAR(2),
    grade_points DECIMAL(3,2),
    percentage DECIMAL(5,2) GENERATED ALWAYS AS (ROUND((marks_obtained / (SELECT total_marks FROM examinations WHERE id = exam_id)) * 100, 2)) STORED,
    remarks TEXT,
    submission_notes TEXT,
    submitted_by INT NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified BOOLEAN DEFAULT FALSE,
    verified_by INT NULL,
    verified_at TIMESTAMP NULL,
    verification_notes TEXT,
    status ENUM('submitted', 'verified', 'rejected', 'pending_review') DEFAULT 'submitted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (exam_id) REFERENCES examinations(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (submitted_by) REFERENCES users(id),
    FOREIGN KEY (verified_by) REFERENCES users(id),
    UNIQUE KEY unique_exam_result (exam_id, student_id),
    INDEX idx_exam_id (exam_id),
    INDEX idx_student_id (student_id),
    INDEX idx_marks_obtained (marks_obtained),
    INDEX idx_grade (grade),
    INDEX idx_verified (verified),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Attendance records
CREATE TABLE IF NOT EXISTS attendance_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    attendance_status ENUM('present', 'absent', 'late', 'excused', 'sick_leave', 'authorized_absence') NOT NULL,
    arrival_time TIME NULL,
    departure_time TIME NULL,
    marked_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES users(id),
    UNIQUE KEY unique_attendance (student_id, course_id, attendance_date),
    INDEX idx_student_id (student_id),
    INDEX idx_course_id (course_id),
    INDEX idx_attendance_date (attendance_date),
    INDEX idx_attendance_status (attendance_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- FINANCIAL MANAGEMENT
-- ========================================

-- Fee structure
CREATE TABLE IF NOT EXISTS fee_structure (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    academic_year VARCHAR(9) NOT NULL,
    semester ENUM('year1_sem1', 'year1_sem2', 'year2_sem1', 'year2_sem2', 'year3_sem1', 'year3_sem2') NOT NULL,
    tuition_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    registration_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    library_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    lab_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    examination_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    accommodation_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    medical_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    development_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    other_fees DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_fee DECIMAL(10,2) GENERATED ALWAYS AS (
        tuition_fee + registration_fee + library_fee + lab_fee + examination_fee + 
        accommodation_fee + medical_fee + development_fee + other_fees
    ) STORED,
    status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    UNIQUE KEY unique_fee_structure (program_id, academic_year, semester),
    INDEX idx_program_id (program_id),
    INDEX idx_academic_year (academic_year),
    INDEX idx_semester (semester),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Student fee accounts
CREATE TABLE IF NOT EXISTS student_fee_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    academic_year VARCHAR(9) NOT NULL,
    semester ENUM('year1_sem1', 'year1_sem2', 'year2_sem1', 'year2_sem2', 'year3_sem1', 'year3_sem2') NOT NULL,
    total_fee DECIMAL(10,2) NOT NULL,
    amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0,
    balance DECIMAL(10,2) GENERATED ALWAYS AS (total_fee - amount_paid) STORED,
    payment_status ENUM('unpaid', 'partial', 'paid', 'overdue', 'refunded') DEFAULT 'unpaid',
    due_date DATE NOT NULL,
    last_payment_date TIMESTAMP NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    scholarship_amount DECIMAL(10,2) DEFAULT 0,
    penalty_amount DECIMAL(10,2) DEFAULT 0,
    status ENUM('active', 'inactive', 'closed') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    UNIQUE KEY unique_student_fee_account (student_id, academic_year, semester),
    INDEX idx_student_id (student_id),
    INDEX idx_academic_year (academic_year),
    INDEX idx_semester (semester),
    INDEX idx_payment_status (payment_status),
    INDEX idx_due_date (due_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payment transactions
CREATE TABLE IF NOT EXISTS payment_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    fee_account_id INT NOT NULL,
    transaction_id VARCHAR(100) NOT NULL UNIQUE,
    transaction_reference VARCHAR(100),
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'bank_transfer', 'mobile_money', 'cheque', 'credit_card', 'bank_draft') NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    receipt_number VARCHAR(100),
    bank_name VARCHAR(100),
    transaction_code VARCHAR(100),
    paid_by VARCHAR(255),
    contact_number VARCHAR(20),
    collected_by INT NOT NULL,
    verified_by INT NULL,
    verification_date TIMESTAMP NULL,
    notes TEXT,
    status ENUM('pending', 'completed', 'failed', 'refunded', 'cancelled') DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (fee_account_id) REFERENCES student_fee_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (collected_by) REFERENCES users(id),
    FOREIGN KEY (verified_by) REFERENCES users(id),
    INDEX idx_student_id (student_id),
    INDEX idx_fee_account_id (fee_account_id),
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_payment_date (payment_date),
    INDEX idx_payment_method (payment_method),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Budget allocations
CREATE TABLE IF NOT EXISTS budget_allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    budget_code VARCHAR(50) NOT NULL UNIQUE,
    budget_name VARCHAR(255) NOT NULL,
    department ENUM('academics', 'finance', 'administration', 'student_services', 'infrastructure', 'maintenance', 'library', 'hostel') NOT NULL,
    allocated_amount DECIMAL(12,2) NOT NULL,
    spent_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    remaining_amount DECIMAL(12,2) GENERATED ALWAYS AS (allocated_amount - spent_amount) STORED,
    fiscal_year VARCHAR(9) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive', 'completed', 'suspended') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_budget_code (budget_code),
    INDEX idx_department (department),
    INDEX idx_fiscal_year (fiscal_year),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Expense records
CREATE TABLE IF NOT EXISTS expense_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    budget_allocation_id INT NOT NULL,
    expense_code VARCHAR(50) NOT NULL UNIQUE,
    expense_title VARCHAR(255) NOT NULL,
    expense_category ENUM('salaries', 'utilities', 'maintenance', 'supplies', 'equipment', 'travel', 'training', 'events', 'construction', 'other') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    expense_date DATE NOT NULL,
    vendor_name VARCHAR(255),
    invoice_number VARCHAR(100),
    receipt_number VARCHAR(100),
    payment_method ENUM('cash', 'bank_transfer', 'cheque', 'mobile_money') NOT NULL,
    approved_by INT NOT NULL,
    approved_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    paid_by INT NULL,
    paid_date TIMESTAMP NULL,
    description TEXT,
    supporting_documents TEXT,
    status ENUM('pending', 'approved', 'paid', 'rejected', 'cancelled') DEFAULT 'approved',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (budget_allocation_id) REFERENCES budget_allocations(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id),
    FOREIGN KEY (paid_by) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_budget_allocation_id (budget_allocation_id),
    INDEX idx_expense_category (expense_category),
    INDEX idx_expense_date (expense_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- COMMUNICATION SYSTEM
-- ========================================

-- Messages
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(255) NOT NULL,
    message_text TEXT NOT NULL,
    message_type ENUM('individual', 'broadcast', 'announcement', 'notice', 'reminder', 'alert') NOT NULL,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    sender_id INT NOT NULL,
    parent_message_id INT NULL,
    thread_id INT NULL,
    is_system_message BOOLEAN DEFAULT FALSE,
    auto_reply BOOLEAN DEFAULT FALSE,
    scheduled_send_time TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    allow_reply BOOLEAN DEFAULT TRUE,
    require_read_receipt BOOLEAN DEFAULT FALSE,
    status ENUM('draft', 'sent', 'delivered', 'read', 'archived', 'deleted') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (parent_message_id) REFERENCES messages(id),
    INDEX idx_sender_id (sender_id),
    INDEX idx_message_type (message_type),
    INDEX idx_priority (priority),
    INDEX idx_parent_message_id (parent_message_id),
    INDEX idx_thread_id (thread_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Message recipients
CREATE TABLE IF NOT EXISTS message_recipients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    recipient_id INT NOT NULL,
    recipient_type ENUM('user', 'role', 'program', 'department') NOT NULL,
    recipient_value VARCHAR(100) NOT NULL,
    delivery_status ENUM('pending', 'delivered', 'read', 'failed', 'bounced') DEFAULT 'pending',
    read_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    failed_reason TEXT,
    read_receipt_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(id),
    UNIQUE KEY unique_message_recipient (message_id, recipient_id),
    INDEX idx_message_id (message_id),
    INDEX idx_recipient_id (recipient_id),
    INDEX idx_delivery_status (delivery_status),
    INDEX idx_read_at (read_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Message attachments
CREATE TABLE IF NOT EXISTS message_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size DECIMAL(10,2),
    file_type VARCHAR(100),
    mime_type VARCHAR(100),
    download_count INT DEFAULT 0,
    is_embedded BOOLEAN DEFAULT FALSE,
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id),
    INDEX idx_message_id (message_id),
    INDEX idx_filename (filename),
    INDEX idx_file_type (file_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Announcements
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    category_id INT NOT NULL,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    target_audience ENUM('all', 'students', 'staff', 'management', 'specific_program', 'specific_role') NOT NULL,
    program_filter VARCHAR(20) NULL,
    role_filter VARCHAR(100) NULL,
    start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_date TIMESTAMP NULL,
    is_pinned BOOLEAN DEFAULT FALSE,
    allow_comments BOOLEAN DEFAULT TRUE,
    requires_acknowledgment BOOLEAN DEFAULT FALSE,
    created_by INT NOT NULL,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    status ENUM('draft', 'published', 'archived', 'expired') DEFAULT 'draft',
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    INDEX idx_category_id (category_id),
    INDEX idx_priority (priority),
    INDEX idx_target_audience (target_audience),
    INDEX idx_start_date (start_date),
    INDEX idx_end_date (end_date),
    INDEX idx_status (status),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Announcement categories
CREATE TABLE IF NOT EXISTS announcement_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_code VARCHAR(20) NOT NULL UNIQUE,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    color VARCHAR(20),
    is_system BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_category_code (category_code),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notification_type ENUM('message', 'announcement', 'payment_reminder', 'exam_result', 'attendance', 'system', 'deadline', 'alert') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    reference_id INT NULL,
    reference_type VARCHAR(50) NULL,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    action_required BOOLEAN DEFAULT FALSE,
    action_url VARCHAR(500),
    action_button_text VARCHAR(100),
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_notification_type (notification_type),
    INDEX idx_priority (priority),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at),
    INDEX idx_reference_id (reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- LIBRARY MANAGEMENT
-- ========================================

-- Books
CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    isbn VARCHAR(20) UNIQUE,
    publisher VARCHAR(255),
    publication_year INT,
    category VARCHAR(100),
    total_copies INT NOT NULL DEFAULT 1,
    available_copies INT NOT NULL DEFAULT 1,
    location VARCHAR(100),
    description TEXT,
    status ENUM('available', 'unavailable', 'lost', 'damaged') DEFAULT 'available',
    added_by INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (added_by) REFERENCES users(id),
    INDEX idx_isbn (isbn),
    INDEX idx_author (author),
    INDEX idx_category (category),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Book loans
CREATE TABLE IF NOT EXISTS book_loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    student_id INT NOT NULL,
    loan_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE NULL,
    status ENUM('borrowed', 'returned', 'overdue', 'lost') DEFAULT 'borrowed',
    issued_by INT NOT NULL,
    returned_by INT NULL,
    fine_amount DECIMAL(10,2) DEFAULT 0,
    fine_paid BOOLEAN DEFAULT FALSE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (issued_by) REFERENCES users(id),
    FOREIGN KEY (returned_by) REFERENCES users(id),
    INDEX idx_book_id (book_id),
    INDEX idx_student_id (student_id),
    INDEX idx_loan_date (loan_date),
    INDEX idx_due_date (due_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- HOSTEL MANAGEMENT
-- ========================================

-- Hostels
CREATE TABLE IF NOT EXISTS hostels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hostel_name VARCHAR(255) NOT NULL,
    hostel_code VARCHAR(20) NOT NULL UNIQUE,
    gender ENUM('male', 'female', 'mixed') NOT NULL,
    total_rooms INT NOT NULL,
    total_capacity INT NOT NULL,
    current_occupancy INT DEFAULT 0,
    warden_name VARCHAR(255),
    warden_contact VARCHAR(20),
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_hostel_code (hostel_code),
    INDEX idx_gender (gender),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rooms
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hostel_id INT NOT NULL,
    room_number VARCHAR(20) NOT NULL,
    room_type ENUM('single', 'double', 'dormitory') NOT NULL,
    capacity INT NOT NULL,
    current_occupancy INT DEFAULT 0,
    floor_number INT,
    has_bathroom BOOLEAN DEFAULT TRUE,
    status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (hostel_id) REFERENCES hostels(id) ON DELETE CASCADE,
    UNIQUE KEY unique_room (hostel_id, room_number),
    INDEX idx_hostel_id (hostel_id),
    INDEX idx_room_type (room_type),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Room allocations
CREATE TABLE IF NOT EXISTS room_allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    room_id INT NOT NULL,
    allocation_date DATE NOT NULL,
    vacate_date DATE NULL,
    status ENUM('active', 'vacated', 'transferred') DEFAULT 'active',
    allocated_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (allocated_by) REFERENCES users(id),
    UNIQUE KEY unique_student_allocation (student_id, status),
    INDEX idx_student_id (student_id),
    INDEX idx_room_id (room_id),
    INDEX idx_allocation_date (allocation_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- DASHBOARD OPERATIONS AND CRUD
-- ========================================

-- Dashboard comments
CREATE TABLE IF NOT EXISTS dashboard_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id INT NOT NULL,
    record_type ENUM('student', 'staff', 'course', 'exam', 'payment', 'announcement', 'document', 'event', 'complaint', 'other') NOT NULL,
    user_id INT NOT NULL,
    comment_text TEXT NOT NULL,
    parent_comment_id INT NULL,
    is_private BOOLEAN DEFAULT FALSE,
    mentions JSON,
    attachments JSON,
    status ENUM('active', 'deleted', 'edited') DEFAULT 'active',
    edited_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (parent_comment_id) REFERENCES dashboard_comments(id),
    INDEX idx_record_id (record_id),
    INDEX idx_record_type (record_type),
    INDEX idx_user_id (user_id),
    INDEX idx_parent_comment_id (parent_comment_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dashboard print logs
CREATE TABLE IF NOT EXISTS dashboard_print_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id INT NOT NULL,
    record_type ENUM('student', 'staff', 'course', 'exam', 'payment', 'announcement', 'document', 'event', 'report', 'other') NOT NULL,
    user_id INT NOT NULL,
    print_title VARCHAR(255) NOT NULL,
    print_format ENUM('pdf', 'html', 'excel', 'word', 'csv') NOT NULL,
    print_parameters JSON,
    file_path VARCHAR(500),
    file_size DECIMAL(10,2),
    print_count INT DEFAULT 1,
    ip_address VARCHAR(45),
    user_agent TEXT,
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'completed',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_record_id (record_id),
    INDEX idx_record_type (record_type),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dashboard send logs
CREATE TABLE IF NOT EXISTS dashboard_send_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id INT NOT NULL,
    record_type ENUM('student', 'staff', 'course', 'exam', 'payment', 'announcement', 'document', 'message', 'report', 'other') NOT NULL,
    sender_id INT NOT NULL,
    send_method ENUM('email', 'sms', 'whatsapp', 'internal_message', 'notification') NOT NULL,
    recipient_type ENUM('individual', 'group', 'role', 'department', 'all', 'custom') NOT NULL,
    recipients JSON,
    subject VARCHAR(255),
    message_text TEXT,
    attachments JSON,
    send_status ENUM('pending', 'sent', 'delivered', 'failed', 'cancelled') DEFAULT 'pending',
    delivery_details JSON,
    error_message TEXT,
    scheduled_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (sender_id) REFERENCES users(id),
    INDEX idx_record_id (record_id),
    INDEX idx_record_type (record_type),
    INDEX idx_sender_id (sender_id),
    INDEX idx_send_status (send_status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dashboard activity logs
CREATE TABLE IF NOT EXISTS dashboard_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type ENUM('create', 'read', 'update', 'delete', 'print', 'send', 'comment', 'login', 'logout', 'view', 'download', 'upload', 'export', 'import') NOT NULL,
    record_id INT NULL,
    record_type ENUM('student', 'staff', 'course', 'exam', 'payment', 'announcement', 'document', 'event', 'complaint', 'message', 'report', 'system', 'other') NULL,
    activity_description TEXT NOT NULL,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    session_id VARCHAR(255),
    duration_ms INT,
    status ENUM('success', 'failed', 'warning') DEFAULT 'success',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_activity_type (activity_type),
    INDEX idx_record_id (record_id),
    INDEX idx_record_type (record_type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dashboard user preferences
CREATE TABLE IF NOT EXISTS dashboard_user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    theme ENUM('light', 'dark', 'auto') DEFAULT 'light',
    language VARCHAR(10) DEFAULT 'en',
    timezone VARCHAR(50) DEFAULT 'Africa/Kampala',
    date_format ENUM('Y-m-d', 'd/m/Y', 'm/d/Y', 'd-M-Y') DEFAULT 'Y-m-d',
    time_format ENUM('24h', '12h') DEFAULT '24h',
    currency VARCHAR(10) DEFAULT 'UGX',
    items_per_page INT DEFAULT 10,
    auto_refresh_interval INT DEFAULT 300,
    notification_sound BOOLEAN DEFAULT TRUE,
    email_notifications BOOLEAN DEFAULT TRUE,
    sms_notifications BOOLEAN DEFAULT FALSE,
    dashboard_layout JSON,
    favorite_widgets JSON,
    recent_searches JSON,
    quick_filters JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dashboard notifications
CREATE TABLE IF NOT EXISTS dashboard_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notification_type ENUM('info', 'success', 'warning', 'error', 'system', 'message', 'reminder', 'alert', 'deadline') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    reference_id INT NULL,
    reference_type ENUM('student', 'staff', 'course', 'exam', 'payment', 'announcement', 'document', 'event', 'complaint', 'message', 'report', 'system') NULL,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    action_required BOOLEAN DEFAULT FALSE,
    action_url VARCHAR(500),
    action_button_text VARCHAR(100),
    action_deadline TIMESTAMP NULL,
    auto_dismiss BOOLEAN DEFAULT FALSE,
    dismiss_after INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_notification_type (notification_type),
    INDEX idx_priority (priority),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at),
    INDEX idx_reference_id (reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dashboard quick actions
CREATE TABLE IF NOT EXISTS dashboard_quick_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_name VARCHAR(100) NOT NULL,
    action_type ENUM('create', 'view', 'edit', 'delete', 'print', 'send', 'export', 'import', 'custom') NOT NULL,
    action_url VARCHAR(500) NOT NULL,
    action_icon VARCHAR(100),
    action_color VARCHAR(20),
    action_order INT DEFAULT 0,
    is_favorite BOOLEAN DEFAULT FALSE,
    use_count INT DEFAULT 0,
    last_used TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_action_type (action_type),
    INDEX idx_is_favorite (is_favorite),
    INDEX idx_action_order (action_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dashboard favorites
CREATE TABLE IF NOT EXISTS dashboard_favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    record_id INT NOT NULL,
    record_type ENUM('student', 'staff', 'course', 'exam', 'payment', 'announcement', 'document', 'event', 'complaint', 'report', 'other') NOT NULL,
    favorite_name VARCHAR(255),
    notes TEXT,
    is_pinned BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_user_record_favorite (user_id, record_id, record_type),
    INDEX idx_user_id (user_id),
    INDEX idx_record_type (record_type),
    INDEX idx_is_pinned (is_pinned)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- DATA MANAGEMENT AND IMPORT/EXPORT
-- ========================================

-- Import jobs
CREATE TABLE IF NOT EXISTS import_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_name VARCHAR(255) NOT NULL,
    import_type ENUM('students', 'staff', 'courses', 'fees', 'payments', 'exams', 'attendance', 'grades', 'other') NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size DECIMAL(10,2),
    file_type VARCHAR(50),
    total_records INT DEFAULT 0,
    processed_records INT DEFAULT 0,
    successful_records INT DEFAULT 0,
    failed_records INT DEFAULT 0,
    skipped_records INT DEFAULT 0,
    duplicate_records INT DEFAULT 0,
    validation_errors JSON,
    import_status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    error_message TEXT,
    started_by INT NOT NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (started_by) REFERENCES users(id),
    INDEX idx_import_type (import_type),
    INDEX idx_import_status (import_status),
    INDEX idx_started_by (started_by),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Export jobs
CREATE TABLE IF NOT EXISTS export_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_name VARCHAR(255) NOT NULL,
    export_type ENUM('students', 'staff', 'courses', 'fees', 'payments', 'exams', 'attendance', 'grades', 'reports', 'other') NOT NULL,
    export_format ENUM('csv', 'excel', 'pdf', 'json', 'xml') NOT NULL,
    export_parameters JSON,
    file_name VARCHAR(255),
    file_path VARCHAR(500),
    file_size DECIMAL(10,2),
    total_records INT DEFAULT 0,
    export_status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    error_message TEXT,
    requested_by INT NOT NULL,
    requested_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    download_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (requested_by) REFERENCES users(id),
    INDEX idx_export_type (export_type),
    INDEX idx_export_status (export_status),
    INDEX idx_requested_by (requested_by),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data validation rules
CREATE TABLE IF NOT EXISTS data_validations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(100) NOT NULL,
    column_name VARCHAR(100) NOT NULL,
    validation_type ENUM('required', 'unique', 'email', 'phone', 'numeric', 'date', 'min_length', 'max_length', 'pattern', 'custom') NOT NULL,
    validation_rule TEXT NOT NULL,
    error_message VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_table_name (table_name),
    INDEX idx_column_name (column_name),
    INDEX idx_validation_type (validation_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bulk operations
CREATE TABLE IF NOT EXISTS bulk_operations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operation_name VARCHAR(255) NOT NULL,
    operation_type ENUM('create', 'update', 'delete', 'activate', 'deactivate', 'archive') NOT NULL,
    target_table VARCHAR(100) NOT NULL,
    target_records JSON,
    operation_data JSON,
    total_records INT DEFAULT 0,
    processed_records INT DEFAULT 0,
    successful_records INT DEFAULT 0,
    failed_records INT DEFAULT 0,
    operation_status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    error_message TEXT,
    performed_by INT NOT NULL,
    performed_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (performed_by) REFERENCES users(id),
    INDEX idx_operation_type (operation_type),
    INDEX idx_target_table (target_table),
    INDEX idx_operation_status (operation_status),
    INDEX idx_performed_by (performed_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Data templates
CREATE TABLE IF NOT EXISTS data_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(255) NOT NULL UNIQUE,
    template_type ENUM('import', 'export') NOT NULL,
    target_table VARCHAR(100) NOT NULL,
    template_format ENUM('csv', 'excel', 'json') NOT NULL,
    column_mappings JSON,
    validation_rules JSON,
    sample_data JSON,
    description TEXT,
    is_default BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_template_type (template_type),
    INDEX idx_target_table (target_table),
    INDEX idx_is_default (is_default),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Import errors
CREATE TABLE IF NOT EXISTS import_errors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    import_job_id INT NOT NULL,
    row_number INT NOT NULL,
    column_name VARCHAR(100),
    error_type ENUM('validation', 'duplicate', 'constraint', 'format', 'required', 'other') NOT NULL,
    error_message TEXT NOT NULL,
    original_data JSON,
    suggested_fix TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (import_job_id) REFERENCES import_jobs(id),
    INDEX idx_import_job_id (import_job_id),
    INDEX idx_error_type (error_type),
    INDEX idx_row_number (row_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- API AND INTEGRATION
-- ========================================

-- API endpoints
CREATE TABLE IF NOT EXISTS api_endpoints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    endpoint_name VARCHAR(255) NOT NULL UNIQUE,
    endpoint_path VARCHAR(500) NOT NULL,
    http_method ENUM('GET', 'POST', 'PUT', 'DELETE', 'PATCH') NOT NULL,
    description TEXT,
    controller VARCHAR(100),
    method_name VARCHAR(100),
    parameters JSON,
    response_schema JSON,
    is_public BOOLEAN DEFAULT FALSE,
    requires_auth BOOLEAN DEFAULT TRUE,
    rate_limit INT DEFAULT 100,
    status ENUM('active', 'inactive', 'deprecated') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_endpoint_path (endpoint_path),
    INDEX idx_http_method (http_method),
    INDEX idx_is_public (is_public),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API keys
CREATE TABLE IF NOT EXISTS api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(255) NOT NULL,
    api_key VARCHAR(255) NOT NULL UNIQUE,
    key_hash VARCHAR(255) NOT NULL,
    user_id INT NOT NULL,
    permissions JSON,
    rate_limit INT DEFAULT 1000,
    ip_whitelist JSON,
    is_active BOOLEAN DEFAULT TRUE,
    expires_at TIMESTAMP NULL,
    last_used TIMESTAMP NULL,
    usage_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_api_key (api_key),
    INDEX idx_key_hash (key_hash),
    INDEX idx_user_id (user_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API logs
CREATE TABLE IF NOT EXISTS api_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_key_id INT NULL,
    endpoint_path VARCHAR(500),
    http_method VARCHAR(10),
    request_ip VARCHAR(45),
    user_agent TEXT,
    request_headers JSON,
    request_body LONGTEXT,
    response_status INT,
    response_headers JSON,
    response_body LONGTEXT,
    response_time_ms INT,
    status ENUM('success', 'error', 'rate_limited', 'unauthorized', 'forbidden') DEFAULT 'success',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (api_key_id) REFERENCES api_keys(id),
    INDEX idx_api_key_id (api_key_id),
    INDEX idx_endpoint_path (endpoint_path),
    INDEX idx_http_method (http_method),
    INDEX idx_response_status (response_status),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- API rate limits
CREATE TABLE IF NOT EXISTS api_rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_key_id INT,
    endpoint_path VARCHAR(500),
    ip_address VARCHAR(45),
    request_count INT DEFAULT 1,
    window_start TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    window_end TIMESTAMP DEFAULT (CURRENT_TIMESTAMP + INTERVAL 1 HOUR),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (api_key_id) REFERENCES api_keys(id),
    UNIQUE KEY unique_rate_limit (api_key_id, endpoint_path, ip_address, window_start),
    INDEX idx_api_key_id (api_key_id),
    INDEX idx_endpoint_path (endpoint_path),
    INDEX idx_window_end (window_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Webhooks
CREATE TABLE IF NOT EXISTS webhooks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    webhook_name VARCHAR(255) NOT NULL,
    webhook_url VARCHAR(500) NOT NULL,
    webhook_type ENUM('student_created', 'student_updated', 'payment_received', 'exam_result', 'attendance_marked', 'system_event') NOT NULL,
    secret_key VARCHAR(255),
    events JSON,
    headers JSON,
    is_active BOOLEAN DEFAULT TRUE,
    retry_count INT DEFAULT 3,
    timeout_seconds INT DEFAULT 30,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_webhook_type (webhook_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Webhook delivery logs
CREATE TABLE IF NOT EXISTS webhook_delivery_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    webhook_id INT NOT NULL,
    event_type VARCHAR(100) NOT NULL,
    payload JSON,
    response_status INT,
    response_body TEXT,
    response_time_ms INT,
    delivery_status ENUM('pending', 'delivered', 'failed', 'retrying') DEFAULT 'pending',
    error_message TEXT,
    attempt_number INT DEFAULT 1,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (webhook_id) REFERENCES webhooks(id),
    INDEX idx_webhook_id (webhook_id),
    INDEX idx_event_type (event_type),
    INDEX idx_delivery_status (delivery_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Integrations
CREATE TABLE IF NOT EXISTS integrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    integration_name VARCHAR(255) NOT NULL,
    integration_type ENUM('payment_gateway', 'email_service', 'sms_service', 'analytics', 'calendar', 'cloud_storage', 'other') NOT NULL,
    provider VARCHAR(100) NOT NULL,
    configuration JSON,
    api_credentials JSON,
    is_active BOOLEAN DEFAULT FALSE,
    last_sync TIMESTAMP NULL,
    sync_status ENUM('success', 'failed', 'pending') DEFAULT 'pending',
    error_message TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_integration_type (integration_type),
    INDEX idx_provider (provider),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- SYSTEM MANAGEMENT
-- ========================================

-- System settings
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity logs (general system activity)
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    table_name VARCHAR(100),
    record_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_table_name (table_name),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System logs
CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    log_level ENUM('debug', 'info', 'warning', 'error', 'critical') NOT NULL,
    log_category ENUM('authentication', 'authorization', 'database', 'file_system', 'email', 'payment', 'api', 'system') NOT NULL,
    message TEXT NOT NULL,
    context JSON,
    user_id INT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    session_id VARCHAR(255),
    request_id VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_log_level (log_level),
    INDEX idx_log_category (log_category),
    INDEX idx_created_at (created_at),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Backup logs
CREATE TABLE IF NOT EXISTS backup_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    backup_type ENUM('full', 'incremental', 'differential') NOT NULL,
    backup_method ENUM('manual', 'scheduled', 'automatic') NOT NULL,
    backup_file_path VARCHAR(500),
    backup_file_size DECIMAL(12,2),
    tables_backed_up JSON,
    start_time TIMESTAMP NOT NULL,
    end_time TIMESTAMP NULL,
    duration_seconds INT,
    status ENUM('started', 'running', 'completed', 'failed', 'cancelled') DEFAULT 'started',
    error_message TEXT,
    performed_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (performed_by) REFERENCES users(id),
    INDEX idx_backup_type (backup_type),
    INDEX idx_status (status),
    INDEX idx_start_time (start_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- INSERT DEFAULT DATA
-- ========================================

-- Insert default programs
INSERT INTO programs (program_code, program_name, program_type, duration_years, description, created_by) VALUES
('CM', 'Certificate in Midwifery', 'certificate', 2.0, '2-year certificate program in midwifery focusing on maternal and child health care', 1),
('CN', 'Certificate in Nursing', 'certificate', 2.0, '2-year certificate program in general nursing covering basic medical and surgical nursing', 1),
('DMORDN', 'Diploma in Midwifery', 'diploma', 3.0, '3-year diploma program in midwifery with advanced clinical skills and management', 1)
ON DUPLICATE KEY UPDATE program_name = VALUES(program_name);

-- Get program IDs
SET @cm_program_id = (SELECT id FROM programs WHERE program_code = 'CM');
SET @cn_program_id = (SELECT id FROM programs WHERE program_code = 'CN');
SET @dmordn_program_id = (SELECT id FROM programs WHERE program_code = 'DMORDN');

-- Insert default courses
INSERT INTO courses (course_code, course_name, program_id, semester, credits, description, created_by) VALUES
('CM101', 'Anatomy and Physiology I', @cm_program_id, 'year1_sem1', 3.0, 'Basic human anatomy and physiology focusing on body systems and functions', 1),
('CM102', 'Nursing Fundamentals I', @cm_program_id, 'year1_sem1', 4.0, 'Introduction to nursing principles, ethics, and basic nursing skills', 1),
('CM103', 'Pharmacology I', @cm_program_id, 'year1_sem1', 2.0, 'Basic pharmacology for nurses including drug calculations and administration', 1),
('CM104', 'Microbiology', @cm_program_id, 'year1_sem2', 3.0, 'Medical microbiology focusing on pathogens relevant to midwifery', 1),
('CM105', 'Midwifery I', @cm_program_id, 'year1_sem2', 4.0, 'Introduction to midwifery principles and basic midwifery skills', 1),
('CN101', 'Anatomy and Physiology I', @cn_program_id, 'year1_sem1', 3.0, 'Basic human anatomy and physiology focusing on body systems and functions', 1),
('CN102', 'Nursing Fundamentals I', @cn_program_id, 'year1_sem1', 4.0, 'Introduction to nursing principles, ethics, and basic nursing skills', 1),
('CN103', 'Psychology for Nurses', @cn_program_id, 'year1_sem1', 2.0, 'Basic psychology and therapeutic communication in nursing', 1),
('CN104', 'Medical-Surgical Nursing I', @cn_program_id, 'year1_sem2', 4.0, 'Medical-surgical nursing focusing on common health problems', 1),
('CN105', 'Community Health Nursing I', @cn_program_id, 'year1_sem2', 3.0, 'Community health nursing principles and practice', 1),
('DM101', 'Advanced Anatomy', @dmordn_program_id, 'year1_sem1', 3.0, 'Advanced human anatomy with emphasis on reproductive system', 1),
('DM102', 'Advanced Midwifery I', @dmordn_program_id, 'year1_sem1', 4.0, 'Advanced midwifery principles and evidence-based practice', 1),
('DM103', 'Research Methods', @dmordn_program_id, 'year1_sem1', 2.0, 'Nursing research methods, statistics, and evidence-based practice', 1),
('DM104', 'Obstetrics and Gynecology', @dmordn_program_id, 'year1_sem2', 4.0, 'Advanced OB/GYN topics including high-risk pregnancies', 1),
('DM105', 'Neonatology', @dmordn_program_id, 'year1_sem2', 3.0, 'Advanced neonatal care and management of complications', 1)
ON DUPLICATE KEY UPDATE course_name = VALUES(course_name);

-- Insert default fee structure
INSERT INTO fee_structure (
    program_id, academic_year, semester, tuition_fee, registration_fee, library_fee, lab_fee, 
    examination_fee, accommodation_fee, medical_fee, development_fee, other_fees, created_by
) VALUES
(@cm_program_id, '2024-2025', 'year1_sem1', 1500000, 50000, 100000, 200000, 150000, 300000, 75000, 100000, 50000, 1),
(@cm_program_id, '2024-2025', 'year1_sem2', 1500000, 0, 100000, 200000, 150000, 300000, 75000, 100000, 50000, 1),
(@cn_program_id, '2024-2025', 'year1_sem1', 1500000, 50000, 100000, 200000, 150000, 300000, 75000, 100000, 50000, 1),
(@cn_program_id, '2024-2025', 'year1_sem2', 1500000, 0, 100000, 200000, 150000, 300000, 75000, 100000, 50000, 1),
(@dmordn_program_id, '2024-2025', 'year1_sem1', 2000000, 50000, 150000, 250000, 200000, 350000, 100000, 125000, 75000, 1),
(@dmordn_program_id, '2024-2025', 'year1_sem2', 2000000, 0, 150000, 250000, 200000, 350000, 100000, 125000, 75000, 1)
ON DUPLICATE KEY UPDATE tuition_fee = VALUES(tuition_fee);

-- Insert default hostels
INSERT INTO hostels (hostel_name, hostel_code, gender, total_rooms, total_capacity, warden_name, warden_contact) VALUES
('Female Hostel A', 'FHA', 'female', 20, 80, 'Mrs. Sarah Namulindwa', '0772123474'),
('Female Hostel B', 'FHB', 'female', 20, 80, 'Mrs. Joyce Nankya', '0772123475'),
('Male Hostel A', 'MHA', 'male', 20, 80, 'Mr. John Mugisha', '0772123476'),
('Male Hostel B', 'MHB', 'male', 20, 80, 'Mr. Peter Lutaaya', '0772123477')
ON DUPLICATE KEY UPDATE hostel_name = VALUES(hostel_name);

-- Create rooms for each hostel
INSERT INTO rooms (hostel_id, room_number, room_type, capacity, floor_number, has_bathroom)
SELECT 
    h.id,
    CONCAT('Room', ROW_NUMBER() OVER (PARTITION BY h.id ORDER BY 1)),
    CASE WHEN h.hostel_code LIKE 'F%' THEN 'double' ELSE 'double' END,
    4,
    CASE WHEN ROW_NUMBER() OVER (PARTITION BY h.id ORDER BY 1) <= 10 THEN 1 ELSE 2 END,
    TRUE
FROM hostels h
CROSS JOIN (SELECT 1 as dummy UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) as numbers
WHERE numbers.dummy <= h.total_rooms / 4;

-- Insert default announcement categories
INSERT INTO announcement_categories (category_code, category_name, description, icon, color, is_system, created_by) VALUES
('GENERAL', 'General', 'General announcements and notices', 'fas fa-bullhorn', '#007bff', TRUE, 1),
('ACADEMIC', 'Academic', 'Academic-related announcements', 'fas fa-graduation-cap', '#28a745', TRUE, 1),
('EXAMINATION', 'Examination', 'Examination schedules and results', 'fas fa-file-alt', '#ffc107', TRUE, 1),
('EVENT', 'Event', 'School events and activities', 'fas fa-calendar-alt', '#17a2b8', TRUE, 1),
('FINANCE', 'Finance', 'Fee payment and financial notices', 'fas fa-money-bill-wave', '#20c997', TRUE, 1),
('URGENT', 'Urgent', 'Urgent announcements', 'fas fa-exclamation-triangle', '#fd7e14', TRUE, 1)
ON DUPLICATE KEY UPDATE category_name = VALUES(category_name);

-- Insert sample users (students)
INSERT INTO users (index_number, full_name, phone, role, type, status, created_at) VALUES
('U001/CM/001/24', 'Aisha Nakato', '0772123456', 'student', 'student', 'active', NOW()),
('U002/CM/002/24', 'Mariam Nalwoga', '0772123457', 'student', 'student', 'active', NOW()),
('U003/CM/003/24', 'Sarah Namulindwa', '0772123458', 'student', 'student', 'active', NOW()),
('U004/CM/004/24', 'Grace Babirye', '0772123459', 'student', 'student', 'active', NOW()),
('U005/CM/005/24', 'Joyce Nankya', '0772123460', 'student', 'student', 'active', NOW()),
('U001/CN/001/24', 'Fatuma Nakato', '0772123461', 'student', 'student', 'active', NOW()),
('U002/CN/002/24', 'Zaituni Nalwoga', '0772123462', 'student', 'student', 'active', NOW()),
('U003/CN/003/24', 'Aisha Namulindwa', '0772123463', 'student', 'student', 'active', NOW()),
('U004/CN/004/24', 'Mariam Babirye', '0772123464', 'student', 'student', 'active', NOW()),
('U005/CN/005/24', 'Sarah Nankya', '0772123465', 'student', 'student', 'active', NOW()),
('U001/DMORDN/001/24', 'Grace Nakato', '0772123466', 'student', 'student', 'active', NOW()),
('U002/DMORDN/002/24', 'Joyce Nalwoga', '0772123467', 'student', 'student', 'active', NOW()),
('U003/DMORDN/003/24', 'Fatuma Namulindwa', '0772123468', 'student', 'student', 'active', NOW()),
('U004/DMORDN/004/24', 'Zaituni Babirye', '0772123469', 'student', 'student', 'active', NOW()),
('U005/DMORDN/005/24', 'Aisha Nankya', '0772123470', 'student', 'student', 'active', NOW());

-- Insert sample users (staff)
INSERT INTO users (full_name, email, phone, password, role, type, status, created_at) VALUES
('Dr. John Mugisha', 'john.mugisha@isnm.ac.ug', '0772123471', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Director General', 'staff', 'active', NOW()),
('Dr. Peter Lutaaya', 'peter.lutaaya@isnm.ac.ug', '0772123472', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'School Principal', 'staff', 'active', NOW()),
('Mrs. Sarah Namulindwa', 'sarah.namulindwa@isnm.ac.ug', '0772123474', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'School Secretary', 'staff', 'active', NOW()),
('Mr. Henry Mugisha', 'henry.mugisha@isnm.ac.ug', '0772123473', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Academic Registrar', 'staff', 'active', NOW()),
('Mr. Joseph Nankya', 'joseph.nankya@isnm.ac.ug', '0772123475', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'School Bursar', 'staff', 'active', NOW()),
('Mr. Mariam Nakato', 'mariam.nakato@isnm.ac.ug', '0772123481', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lecturers', 'staff', 'active', NOW()),
('Mrs. Sarah Nalwoga', 'sarah.nalwoga@isnm.ac.ug', '0772123482', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lecturers', 'staff', 'active', NOW()),
('Dr. Grace Nakato', 'grace.nakato@isnm.ac.ug', '0772123483', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lecturers', 'staff', 'active', NOW()),
('Mrs. Joyce Babirye', 'joyce.babirye@isnm.ac.ug', '0772123484', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lecturers', 'staff', 'active', NOW()),
('Mr. Fatuma Nankya', 'fatuma.nankya@isnm.ac.ug', '0772123485', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lecturers', 'staff', 'active', NOW());

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
('academic_year', '2024-2025', 'Current academic year'),
('timezone', 'Africa/Kampala', 'Default timezone'),
('currency', 'UGX', 'Default currency'),
('date_format', 'Y-m-d', 'Default date format'),
('time_format', '24h', 'Default time format')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Insert default validation rules
INSERT INTO data_validations (table_name, column_name, validation_type, validation_rule, error_message, created_by) VALUES
('users', 'full_name', 'required', 'NOT NULL', 'Full name is required', 1),
('users', 'email', 'email', '^[A-Za-z0-9._%-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,4}$', 'Invalid email format', 1),
('users', 'phone', 'pattern', '^07[0-9]{8}$', 'Phone must be 10 digits starting with 07', 1),
('users', 'index_number', 'pattern', '^U[0-9]{3}/[A-Z]{2,4}/[0-9]{3}/[0-9]{2}$', 'Invalid index number format', 1),
('courses', 'course_code', 'unique', 'UNIQUE', 'Course code must be unique', 1),
('courses', 'course_name', 'required', 'NOT NULL', 'Course name is required', 1),
('courses', 'credits', 'numeric', '^[0-9]+(\\.[0-9]+)?$', 'Credits must be numeric', 1)
ON DUPLICATE KEY UPDATE error_message = VALUES(error_message);

-- ========================================
-- CREATE VIEWS FOR DASHBOARD OPERATIONS
-- ========================================

-- Complete student profile view
CREATE OR REPLACE VIEW complete_student_profile AS
SELECT 
    u.id,
    u.full_name,
    u.index_number,
    u.email,
    u.phone,
    u.type,
    u.status,
    u.created_at as admission_date,
    u.last_login,
    u.date_of_birth,
    u.gender,
    u.address,
    u.emergency_contact_name,
    u.emergency_contact_phone,
    u.profile_picture,
    p.program_name,
    p.program_type,
    COUNT(DISTINCT sar.course_id) as total_courses,
    COUNT(CASE WHEN sar.status = 'completed' THEN 1 END) as completed_courses,
    AVG(CASE WHEN sar.status = 'completed' THEN sar.gpa_points END) as current_gpa,
    SUM(sfa.balance) as total_balance,
    COUNT(CASE WHEN sfa.payment_status = 'paid' THEN 1 END) as paid_semesters,
    COUNT(DISTINCT bl.id) as books_borrowed,
    COUNT(CASE WHEN bl.status = 'borrowed' THEN 1 END) as current_loans,
    ra.room_number,
    h.hostel_name
FROM users u
LEFT JOIN student_academic_records sar ON u.id = sar.student_id
LEFT JOIN courses c ON sar.course_id = c.id
LEFT JOIN programs p ON c.program_id = p.id
LEFT JOIN student_fee_accounts sfa ON u.id = sfa.student_id
LEFT JOIN book_loans bl ON u.id = bl.student_id AND bl.status = 'borrowed'
LEFT JOIN room_allocations ra ON u.id = ra.student_id AND ra.status = 'active'
LEFT JOIN rooms r ON ra.room_id = r.id
LEFT JOIN hostels h ON r.hostel_id = h.id
WHERE u.type = 'student' AND u.status = 'active'
GROUP BY u.id, u.full_name, u.index_number, u.email, u.phone, u.type, u.status, u.created_at, u.last_login,
         u.date_of_birth, u.gender, u.address, u.emergency_contact_name, u.emergency_contact_phone, u.profile_picture,
         p.program_name, p.program_type, ra.room_number, h.hostel_name;

-- Complete staff profile view
CREATE OR REPLACE VIEW complete_staff_profile AS
SELECT 
    u.id,
    u.full_name,
    u.email,
    u.phone,
    u.role,
    u.type,
    u.status,
    u.created_at as employment_date,
    u.last_login,
    COUNT(DISTINCT c.id) as courses_taught,
    COUNT(DISTINCT sar.student_id) as students_supervised,
    COUNT(DISTINCT e.id) as exams_conducted,
    COUNT(DISTINCT pt.id) as payments_collected,
    COUNT(DISTINCT m.id) as messages_sent
FROM users u
LEFT JOIN courses c ON u.id = c.created_by
LEFT JOIN student_academic_records sar ON u.id = (SELECT marked_by FROM attendance_records WHERE student_id = sar.student_id LIMIT 1)
LEFT JOIN examinations e ON u.id = e.created_by
LEFT JOIN payment_transactions pt ON u.id = pt.collected_by
LEFT JOIN messages m ON u.id = m.sender_id
WHERE u.type = 'staff' AND u.status = 'active'
GROUP BY u.id, u.full_name, u.email, u.phone, u.role, u.type, u.status, u.created_at, u.last_login;

-- System overview view
CREATE OR REPLACE VIEW system_overview AS
SELECT 
    'Students' as category,
    COUNT(*) as total,
    COUNT(CASE WHEN last_login >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as active_last_week,
    COUNT(CASE WHEN created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as new_this_month
FROM users 
WHERE type = 'student' AND status = 'active'

UNION ALL

SELECT 
    'Staff' as category,
    COUNT(*) as total,
    COUNT(CASE WHEN last_login >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as active_last_week,
    COUNT(CASE WHEN created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as new_this_month
FROM users 
WHERE type = 'staff' AND status = 'active'

UNION ALL

SELECT 
    'Courses' as category,
    COUNT(*) as total,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_last_week,
    COUNT(CASE WHEN created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as new_this_month
FROM courses

UNION ALL

SELECT 
    'Books' as category,
    COUNT(*) as total,
    COUNT(CASE WHEN status = 'available' THEN 1 END) as active_last_week,
    COUNT(CASE WHEN created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as new_this_month
FROM books

UNION ALL

SELECT 
    'Hostel Rooms' as category,
    COUNT(*) as total,
    COUNT(CASE WHEN status = 'available' THEN 1 END) as active_last_week,
    COUNT(CASE WHEN created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as new_this_month
FROM rooms;

-- Dashboard student summary view
CREATE OR REPLACE VIEW dashboard_student_summary AS
SELECT 
    u.id,
    u.full_name,
    u.index_number,
    u.phone,
    p.program_name,
    COUNT(DISTINCT sar.course_id) as total_courses,
    COUNT(CASE WHEN sar.status = 'completed' THEN 1 END) as completed_courses,
    AVG(CASE WHEN sar.status = 'completed' THEN sar.gpa_points END) as current_gpa,
    COUNT(CASE WHEN ar.attendance_status = 'present' THEN 1 END) as present_days,
    COUNT(ar.attendance_status) as total_days,
    ROUND((COUNT(CASE WHEN ar.attendance_status = 'present' THEN 1 END) * 100.0) / COUNT(ar.attendance_status), 2) as attendance_rate,
    sfa.balance as fee_balance,
    sfa.payment_status,
    u.last_login
FROM users u
LEFT JOIN student_academic_records sar ON u.id = sar.student_id
LEFT JOIN courses c ON sar.course_id = c.id
LEFT JOIN programs p ON c.program_id = p.id
LEFT JOIN attendance_records ar ON u.id = ar.student_id
LEFT JOIN student_fee_accounts sfa ON u.id = sfa.student_id AND sfa.academic_year = '2024-2025'
WHERE u.type = 'student' AND u.status = 'active'
GROUP BY u.id, u.full_name, u.index_number, u.phone, p.program_name, sfa.balance, sfa.payment_status, u.last_login;

-- Dashboard staff summary view
CREATE OR REPLACE VIEW dashboard_staff_summary AS
SELECT 
    u.id,
    u.full_name,
    u.email,
    u.phone,
    u.role,
    u.type,
    u.status,
    u.created_at,
    u.last_login,
    COUNT(DISTINCT c.id) as courses_taught,
    COUNT(DISTINCT sar.student_id) as students_supervised,
    COUNT(DISTINCT e.id) as exams_conducted,
    COUNT(DISTINCT ar.id) as attendance_marked,
    COUNT(DISTINCT pt.id) as payments_collected,
    COUNT(DISTINCT m.id) as messages_sent
FROM users u
LEFT JOIN courses c ON u.id = c.created_by
LEFT JOIN student_academic_records sar ON u.id = (SELECT marked_by FROM attendance_records WHERE student_id = sar.student_id LIMIT 1)
LEFT JOIN examinations e ON u.id = e.created_by
LEFT JOIN attendance_records ar ON u.id = ar.marked_by
LEFT JOIN payment_transactions pt ON u.id = pt.collected_by
LEFT JOIN messages m ON u.id = m.sender_id
WHERE u.type = 'staff' AND u.status = 'active'
GROUP BY u.id, u.full_name, u.email, u.phone, u.role, u.type, u.status, u.created_at, u.last_login;

-- Fee collection summary view
CREATE OR REPLACE VIEW fee_collection_summary AS
SELECT 
    fs.academic_year,
    fs.semester,
    p.program_name,
    p.program_type,
    COUNT(DISTINCT sfa.student_id) as total_students,
    SUM(sfa.total_fee) as total_fees,
    SUM(sfa.amount_paid) as total_collected,
    SUM(sfa.balance) as total_balance,
    COUNT(CASE WHEN sfa.payment_status = 'paid' THEN 1 END) as fully_paid,
    COUNT(CASE WHEN sfa.payment_status = 'partial' THEN 1 END) as partially_paid,
    COUNT(CASE WHEN sfa.payment_status = 'unpaid' THEN 1 END) as unpaid,
    COUNT(CASE WHEN sfa.payment_status = 'overdue' THEN 1 END) as overdue,
    ROUND((SUM(sfa.amount_paid * 100.0) / SUM(sfa.total_fee), 2) as collection_rate
FROM fee_structure fs
JOIN student_fee_accounts sfa ON fs.id = sfa.fee_structure_id
JOIN programs p ON fs.program_id = p.id
WHERE fs.academic_year = '2024-2025'
GROUP BY fs.academic_year, fs.semester, p.program_name, p.program_type
ORDER BY fs.academic_year DESC, fs.semester;

-- Message statistics view
CREATE OR REPLACE VIEW message_statistics AS
SELECT 
    m.id,
    m.subject,
    m.message_type,
    m.priority,
    m.sender_id,
    sender.full_name as sender_name,
    COUNT(mr.recipient_id) as total_recipients,
    COUNT(CASE WHEN mr.delivery_status = 'delivered' THEN 1 END) as delivered_count,
    COUNT(CASE WHEN mr.delivery_status = 'read' THEN 1 END) as read_count,
    COUNT(CASE WHEN mr.delivery_status = 'failed' THEN 1 END) as failed_count,
    ROUND((COUNT(CASE WHEN mr.delivery_status = 'read' THEN 1 END) * 100.0) / COUNT(mr.recipient_id), 2) as read_rate,
    m.created_at,
    m.status
FROM messages m
JOIN users sender ON m.sender_id = sender.id
LEFT JOIN message_recipients mr ON m.id = mr.message_id
GROUP BY m.id, m.subject, m.message_type, m.priority, m.sender_id, sender.full_name, m.created_at, m.status
ORDER BY m.created_at DESC;

-- ========================================
-- STORED PROCEDURES FOR COMPLETE SYSTEM
-- ========================================

DELIMITER //

-- Student authentication procedure
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
        INSERT INTO login_attempts (user_identifier, user_type, ip_address, success, failure_reason)
        VALUES (p_index_number, 'student', p_ip_address, FALSE, 'Account locked');
        
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
            INSERT INTO login_attempts (user_identifier, user_type, ip_address, success, failure_reason)
            VALUES (p_index_number, 'student', p_ip_address, TRUE, 'Login successful');
            
            SET p_result = 'Login successful';
            SET p_success = TRUE;
            SET p_user_id = v_user_id;
        ELSE
            -- Failed - increment attempts
            UPDATE users 
            SET login_attempts = login_attempts + 1,
                locked_until = CASE 
                    WHEN login_attempts >= 4 THEN DATE_ADD(NOW(), INTERVAL 15 MINUTE)
                    ELSE NULL 
                END
            WHERE index_number = p_index_number AND type = 'student';
            
            -- Log failed attempt
            INSERT INTO login_attempts (user_identifier, user_type, ip_address, success, failure_reason)
            VALUES (p_index_number, 'student', p_ip_address, FALSE, 'Invalid credentials');
            
            SET p_result = 'Invalid student credentials. All fields must match exactly.';
            SET p_success = FALSE;
            SET p_user_id = NULL;
        END IF;
    END IF;
END //

-- Staff authentication procedure
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
        INSERT INTO login_attempts (user_identifier, user_type, ip_address, success, failure_reason)
        VALUES (p_email, 'staff', p_ip_address, FALSE, 'Account locked');
        
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
        
        IF v_user_id IS NOT NULL AND PASSWORD_VERIFY(p_password, v_hashed_password) THEN
            -- Success - reset attempts and update last login
            UPDATE users 
            SET login_attempts = 0, 
                locked_until = NULL, 
                last_login = NOW()
            WHERE id = v_user_id;
            
            -- Log successful attempt
            INSERT INTO login_attempts (user_identifier, user_type, ip_address, success, failure_reason)
            VALUES (p_email, 'staff', p_ip_address, TRUE, 'Login successful');
            
            SET p_result = 'Login successful';
            SET p_success = TRUE;
            SET p_user_id = v_user_id;
        ELSE
            -- Failed - increment attempts
            UPDATE users 
            SET login_attempts = login_attempts + 1,
                locked_until = CASE 
                    WHEN login_attempts >= 4 THEN DATE_ADD(NOW(), INTERVAL 15 MINUTE)
                    ELSE NULL 
                END
            WHERE email = p_email AND type = 'staff';
            
            -- Log failed attempt
            INSERT INTO login_attempts (user_identifier, user_type, ip_address, success, failure_reason)
            VALUES (p_email, 'staff', p_ip_address, FALSE, 'Invalid credentials');
            
            SET p_result = 'Invalid email or password';
            SET p_success = FALSE;
            SET p_user_id = NULL;
        END IF;
    END IF;
END //

-- Create student account procedure
CREATE PROCEDURE IF NOT EXISTS create_student_account(
    IN p_index_number VARCHAR(50),
    IN p_full_name VARCHAR(255),
    IN p_phone VARCHAR(20),
    IN p_created_by INT,
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN,
    OUT p_student_id INT
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
        SET p_student_id = NULL;
    ELSE
        -- Insert new student
        INSERT INTO users (
            index_number, full_name, phone, role, type, status, created_at
        ) VALUES (
            p_index_number, p_full_name, p_phone, 'student', 'student', 'active', NOW()
        );
        
        SET p_student_id = LAST_INSERT_ID();
        
        -- Log activity
        INSERT INTO dashboard_activity_logs (
            user_id, activity_type, record_id, record_type, activity_description, new_values
        ) VALUES (
            p_created_by, 'create', p_student_id, 'student',
            CONCAT('Created student account: ', p_full_name),
            JSON_OBJECT('index_number', p_index_number, 'full_name', p_full_name, 'phone', p_phone)
        );
        
        SET p_result = CONCAT('Student account created successfully with ID: ', p_student_id);
        SET p_success = TRUE;
    END IF;
END //

-- Create staff account procedure
CREATE PROCEDURE IF NOT EXISTS create_staff_account(
    IN p_full_name VARCHAR(255),
    IN p_email VARCHAR(255),
    IN p_phone VARCHAR(20),
    IN p_password VARCHAR(255),
    IN p_role VARCHAR(100),
    IN p_created_by INT,
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN,
    OUT p_staff_id INT
)
BEGIN
    DECLARE v_email_count INT DEFAULT 0;
    
    -- Check if email already exists
    SELECT COUNT(*) INTO v_email_count
    FROM users WHERE email = p_email AND type = 'staff';
    
    IF v_email_count > 0 THEN
        SET p_result = 'Email already exists';
        SET p_success = FALSE;
        SET p_staff_id = NULL;
    ELSE
        -- Insert new staff
        INSERT INTO users (
            full_name, email, phone, password, role, type, status, created_at
        ) VALUES (
            p_full_name, p_email, p_phone, PASSWORD_HASH(p_password), p_role, 'staff', 'active', NOW()
        );
        
        SET p_staff_id = LAST_INSERT_ID();
        
        -- Log activity
        INSERT INTO dashboard_activity_logs (
            user_id, activity_type, record_id, record_type, activity_description, new_values
        ) VALUES (
            p_created_by, 'create', p_staff_id, 'staff',
            CONCAT('Created staff account: ', p_full_name),
            JSON_OBJECT('full_name', p_full_name, 'email', p_email, 'role', p_role)
        );
        
        SET p_result = CONCAT('Staff account created successfully with ID: ', p_staff_id);
        SET p_success = TRUE;
    END IF;
END //

-- Get student dashboard data
CREATE PROCEDURE IF NOT EXISTS get_student_dashboard_data(
    IN p_student_id INT
)
BEGIN
    -- Get student basic info
    SELECT 
        u.id,
        u.full_name,
        u.index_number,
        u.email,
        u.phone,
        u.last_login,
        CASE 
            WHEN u.index_number LIKE '%/CM/%' THEN 'Certificate in Midwifery'
            WHEN u.index_number LIKE '%/CN/%' THEN 'Certificate in Nursing'
            WHEN u.index_number LIKE '%/DMORDN/%' THEN 'Diploma in Midwifery'
            ELSE 'Unknown Program'
        END as program
    FROM users u
    WHERE u.id = p_student_id AND u.type = 'student';
    
    -- Get academic statistics
    SELECT 
        COUNT(DISTINCT sar.course_id) as total_courses,
        COUNT(CASE WHEN sar.status = 'completed' THEN 1 END) as completed_courses,
        AVG(CASE WHEN sar.status = 'completed' THEN sar.gpa_points END) as current_gpa,
        COUNT(CASE WHEN sar.status = 'in_progress' THEN 1 END) as in_progress_courses
    FROM student_academic_records sar
    WHERE sar.student_id = p_student_id;
    
    -- Get fee summary
    SELECT 
        SUM(total_fee) as total_fees,
        SUM(amount_paid) as total_paid,
        SUM(balance) as total_balance,
        COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_semesters,
        COUNT(CASE WHEN payment_status = 'unpaid' THEN 1 END) as unpaid_semesters
    FROM student_fee_accounts
    WHERE student_id = p_student_id;
    
    -- Get attendance summary
    SELECT 
        COUNT(*) as total_classes,
        COUNT(CASE WHEN ar.attendance_status = 'present' THEN 1 END) as present_classes,
        COUNT(CASE WHEN ar.attendance_status = 'absent' THEN 1 END) as absent_classes,
        ROUND((COUNT(CASE WHEN ar.attendance_status = 'present' THEN 1 END) * 100.0) / COUNT(*), 2) as attendance_rate
    FROM attendance_records ar
    WHERE ar.student_id = p_student_id AND ar.attendance_date >= DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    -- Get unread notifications
    SELECT 
        n.id,
        n.title,
        n.message,
        n.priority,
        n.is_read,
        n.created_at
    FROM notifications n
    WHERE n.user_id = p_student_id AND n.is_read = FALSE
    ORDER BY n.priority DESC, n.created_at DESC
    LIMIT 5;
    
    -- Get recent activities
    SELECT 
        al.activity_type,
        al.activity_description,
        al.created_at
    FROM dashboard_activity_logs al
    WHERE al.user_id = p_student_id
    ORDER BY al.created_at DESC
    LIMIT 10;
END //

-- Get staff dashboard data
CREATE PROCEDURE IF NOT EXISTS get_staff_dashboard_data(
    IN p_staff_id INT
)
BEGIN
    -- Get staff basic info
    SELECT 
        u.id,
        u.full_name,
        u.email,
        u.phone,
        u.role,
        u.last_login,
        u.created_at as employment_date
    FROM users u
    WHERE u.id = p_staff_id AND u.type = 'staff';
    
    -- Get academic statistics
    SELECT 
        COUNT(DISTINCT c.id) as courses_taught,
        COUNT(DISTINCT sar.student_id) as students_supervised,
        COUNT(DISTINCT e.id) as exams_conducted,
        COUNT(DISTINCT ar.id) as attendance_marked
    FROM users u
    LEFT JOIN courses c ON u.id = c.created_by
    LEFT JOIN student_academic_records sar ON u.id = (SELECT marked_by FROM attendance_records WHERE student_id = sar.student_id LIMIT 1)
    LEFT JOIN examinations e ON u.id = e.created_by
    LEFT JOIN attendance_records ar ON u.id = ar.marked_by
    WHERE u.id = p_staff_id;
    
    -- Get financial statistics
    SELECT 
        COUNT(pt.id) as total_transactions,
        COALESCECE(SUM(pt.amount), 0) as total_collected,
        COUNT(CASE WHEN pt.payment_method = 'cash' THEN 1 END) as cash_payments,
        COUNT(CASE WHEN pt.payment_method = 'bank_transfer' THEN 1 END) as bank_transfers,
        COUNT(CASE WHEN pt.payment_method = 'mobile_money' THEN 1 END) as mobile_money_payments
    FROM payment_transactions pt
    WHERE pt.collected_by = p_staff_id AND pt.status = 'completed';
    
    -- Get communication statistics
    SELECT 
        COUNT(m.id) as total_messages,
        COUNT(CASE WHEN m.message_type = 'broadcast' THEN 1 END) as broadcast_messages,
        COUNT(CASE WHEN m.message_type = 'individual' THEN 1 END) as individual_messages
    FROM messages m
    WHERE m.sender_id = p_staff_id;
    
    -- Get recent notifications
    SELECT 
        n.id,
        n.title,
        n.message,
        n.priority,
        n.is_read,
        n.created_at
    FROM notifications n
    WHERE n.user_id = p_staff_id AND n.is_read = FALSE
    ORDER BY n.priority DESC, n.created_at DESC
    LIMIT 5;
    
    -- Get recent activities
    SELECT 
        al.activity_type,
        al.activity_description,
        al.created_at
    FROM dashboard_activity_logs al
    WHERE al.user_id = p_staff_id
    ORDER BY al.created_at DESC
    LIMIT 10;
END //

-- System health check procedure
CREATE PROCEDURE IF NOT EXISTS system_health_check(
    OUT p_health_status VARCHAR(50),
    OUT p_issues JSON,
    OUT p_recommendations JSON
)
BEGIN
    DECLARE v_total_students INT DEFAULT 0;
    DECLARE v_total_staff INT DEFAULT 0;
    DECLARE v_database_size DECIMAL(12,2) DEFAULT 0;
    DECLARE v_backup_status VARCHAR(50) DEFAULT 'OK';
    
    -- Get system statistics
    SELECT COUNT(*) INTO v_total_students FROM users WHERE type = 'student' AND status = 'active';
    SELECT COUNT(*) INTO v_total_staff FROM users WHERE type = 'staff' AND status = 'active';
    
    -- Check recent backups
    SELECT COUNT(*) INTO v_backup_status
    FROM backup_logs 
    WHERE status = 'completed' 
      AND start_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR);
    
    -- Determine health status
    IF v_total_students > 0 AND v_total_staff > 0 AND v_backup_status > 0 THEN
        SET p_health_status = 'HEALTHY';
        SET p_issues = JSON_ARRAY();
        SET p_recommendations = JSON_ARRAY('System is operating normally');
    ELSE
        SET p_health_status = 'WARNING';
        SET p_issues = JSON_ARRAY(
            CASE WHEN v_total_students = 0 THEN 'No active students found' ELSE NULL END,
            CASE WHEN v_total_staff = 0 THEN 'No active staff found' ELSE NULL END,
            CASE WHEN v_backup_status = 0 THEN 'No recent backups found' ELSE NULL END
        );
        SET p_recommendations = JSON_ARRAY(
            'Check user accounts',
            'Perform backup immediately',
            'Review system logs'
        );
    END IF;
END //

-- Cleanup old data procedure
CREATE PROCEDURE IF NOT EXISTS cleanup_old_data(
    IN p_days_to_keep INT DEFAULT 365
)
BEGIN
    DECLARE v_deleted_logs INT DEFAULT 0;
    DECLARE v_deleted_sessions INT DEFAULT 0;
    DECLARE v_deleted_attempts INT DEFAULT 0;
    
    -- Clean up old activity logs
    DELETE FROM dashboard_activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL p_days_to_keep DAY);
    SET v_deleted_logs = ROW_COUNT();
    
    -- Clean up old user sessions
    DELETE FROM user_sessions WHERE expires_at < DATE_SUB(NOW(), INTERVAL p_days_to_keep DAY);
    SET v_deleted_sessions = ROW_COUNT();
    
    -- Clean up old login attempts
    DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL p_days_to_keep DAY);
    SET v_deleted_attempts = ROW_COUNT();
    
    -- Clean up old notifications
    DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL p_days_to_keep DAY);
    
    -- Log cleanup activity
    INSERT INTO dashboard_activity_logs (
        user_id, action_type, record_id, record_type, activity_description, new_values
    ) VALUES (
        1, 'cleanup', NULL, 'system',
        CONCAT('Cleanup completed. Deleted ', v_deleted_logs, ' logs, ', v_deleted_sessions, ' sessions, ', v_deleted_attempts, ' login attempts'),
        JSON_OBJECT('deleted_logs', v_deleted_logs, 'deleted_sessions', v_deleted_sessions, 'deleted_attempts', v_deleted_attempts, 'days_kept', p_days_to_keep)
    );
END //

DELIMITER ;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Success message
SELECT 'Final complete system setup SQL executed successfully!' as message;
SELECT 'All tables, views, and stored procedures have been created for complete dashboard operations' as note;
SELECT 'The ISNM School Management System database is now fully configured with all dashboard operations including CRUD, print, send, comment, and integration capabilities' as status;
