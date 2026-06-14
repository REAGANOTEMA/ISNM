-- ISNM Database Setup: igangaschoolofl_students_db
-- Import into the igangaschoolofl_students_db database via phpMyAdmin
-- =============================================

USE `igangaschoolofl_students_db`;

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
-- ============================================================
-- ISNM COMPLETE BURSAR FINANCIAL MANAGEMENT SYSTEM
-- Comprehensive SQL Schema with all required features
-- Database: igangaschoolofl_students_db
-- ============================================================



-- ============================================================
-- 1. BURSAR USER ACCOUNTS & AUTHENTICATION
-- ============================================================

-- Drop tables in correct order to avoid foreign key constraints
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS proof_of_payments;
DROP TABLE IF EXISTS payment_receipts;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS student_invoices;
DROP TABLE IF EXISTS student_penalties;
DROP TABLE IF EXISTS penalty_configurations;
DROP TABLE IF EXISTS fee_adjustments;
DROP TABLE IF EXISTS sponsorships;
DROP TABLE IF EXISTS student_fee_assignments;
DROP TABLE IF EXISTS fee_structures;
DROP TABLE IF EXISTS programs;
DROP TABLE IF EXISTS bursar_users;
DROP TABLE IF EXISTS financial_reports;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS fee_reminders;
DROP TABLE IF EXISTS expenditure_records;
DROP TABLE IF EXISTS budget_records;
DROP TABLE IF EXISTS budgets;
DROP TABLE IF EXISTS cost_centers;
DROP TABLE IF EXISTS chart_of_accounts;
DROP TABLE IF EXISTS general_ledger;
DROP TABLE IF EXISTS cash_book;
DROP TABLE IF EXISTS assets;
DROP TABLE IF EXISTS asset_categories;
DROP TABLE IF EXISTS staff_salaries;
DROP TABLE IF EXISTS salary_components;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS bursar_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('bursar', 'accounts_assistant', 'auditor') DEFAULT 'bursar',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status)
);

-- ============================================================
-- 2. CHART OF ACCOUNTS
-- ============================================================

CREATE TABLE IF NOT EXISTS chart_of_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_code VARCHAR(20) UNIQUE NOT NULL,
    account_name VARCHAR(255) NOT NULL,
    account_type ENUM('Asset', 'Liability', 'Equity', 'Revenue', 'Expense') NOT NULL,
    parent_account_id INT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_account_id) REFERENCES chart_of_accounts(id) ON DELETE SET NULL,
    INDEX idx_account_code (account_code),
    INDEX idx_account_type (account_type)
);

-- ============================================================
-- 3. COST CENTERS
-- ============================================================

CREATE TABLE IF NOT EXISTS cost_centers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cost_center_code VARCHAR(20) UNIQUE NOT NULL,
    cost_center_name VARCHAR(255) NOT NULL,
    department VARCHAR(100),
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cost_center_code (cost_center_code)
);

-- ============================================================
-- 4. PROGRAMS
-- ============================================================

CREATE TABLE IF NOT EXISTS programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_code VARCHAR(20) UNIQUE NOT NULL,
    program_name VARCHAR(255) NOT NULL,
    program_type ENUM('Certificate', 'Diploma', 'Degree') DEFAULT 'Diploma',
    duration_years INT DEFAULT 2,
    total_fee DECIMAL(12,2) DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_program_code (program_code)
);

-- ============================================================
-- 5. FEE STRUCTURES
-- ============================================================

CREATE TABLE IF NOT EXISTS fee_structures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fee_name VARCHAR(255) NOT NULL,
    fee_type ENUM('Tuition', 'Registration', 'Library', 'Laboratory', 'Examination', 'Graduation', 'Other') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    program_id INT NULL,
    academic_year VARCHAR(20),
    semester VARCHAR(50),
    is_mandatory BOOLEAN DEFAULT TRUE,
    due_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE SET NULL,
    INDEX idx_fee_type (fee_type),
    INDEX idx_academic_year (academic_year)
);

-- ============================================================
-- 6. STUDENT FEE ASSIGNMENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS student_fee_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    fee_structure_id INT NOT NULL,
    assigned_amount DECIMAL(10,2) NOT NULL,
    paid_amount DECIMAL(10,2) DEFAULT 0,
    balance DECIMAL(10,2) GENERATED ALWAYS AS (assigned_amount - paid_amount) STORED,
    status ENUM('Unpaid', 'Partially Paid', 'Paid', 'Waived') DEFAULT 'Unpaid',
    due_date DATE,
    assigned_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (fee_structure_id) REFERENCES fee_structures(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    INDEX idx_student_id (student_id),
    INDEX idx_status (status),
    INDEX idx_due_date (due_date)
);

-- ============================================================
-- 7. SPONSORSHIPS
-- ============================================================

CREATE TABLE IF NOT EXISTS sponsorships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sponsorship_code VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    sponsor_name VARCHAR(255) NOT NULL,
    sponsor_type ENUM('Government', 'NGO', 'Private', 'Self', 'Other') DEFAULT 'Self',
    sponsorship_type ENUM('Full', 'Partial', 'Tuition Only', 'Other') DEFAULT 'Partial',
    amount DECIMAL(12,2) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    terms_conditions TEXT,
    status ENUM('Active', 'Expired', 'Cancelled') DEFAULT 'Active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    INDEX idx_sponsorship_code (sponsorship_code),
    INDEX idx_student_id (student_id),
    INDEX idx_status (status)
);

-- ============================================================
-- 8. BUDGETS
-- ============================================================

CREATE TABLE IF NOT EXISTS budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    budget_name VARCHAR(255) NOT NULL,
    fiscal_year VARCHAR(20) NOT NULL,
    total_amount DECIMAL(15,2) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('Draft', 'Approved', 'Active', 'Closed') DEFAULT 'Draft',
    approved_by INT,
    approved_date TIMESTAMP NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_fiscal_year (fiscal_year),
    INDEX idx_status (status)
);

-- ============================================================
-- 9. BUDGET RECORDS
-- ============================================================

CREATE TABLE IF NOT EXISTS budget_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    budget_id INT NOT NULL,
    budget_item VARCHAR(255) NOT NULL,
    allocated_amount DECIMAL(12,2) NOT NULL,
    spent_amount DECIMAL(12,2) DEFAULT 0,
    remaining_amount DECIMAL(12,2) GENERATED ALWAYS AS (allocated_amount - spent_amount) STORED,
    status ENUM('Active', 'Exhausted', 'Cancelled') DEFAULT 'Active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (budget_id) REFERENCES budgets(id) ON DELETE CASCADE,
    INDEX idx_budget_id (budget_id),
    INDEX idx_status (status)
);

-- ============================================================
-- 10. STUDENT INVOICES
-- ============================================================

CREATE TABLE IF NOT EXISTS student_invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    fee_assignment_id INT NULL,
    fee_type VARCHAR(100) NOT NULL,
    description TEXT,
    total_amount DECIMAL(12,2) NOT NULL,
    discount_amount DECIMAL(12,2) DEFAULT 0,
    net_amount DECIMAL(12,2) GENERATED ALWAYS AS (total_amount - discount_amount) STORED,
    amount_paid DECIMAL(12,2) DEFAULT 0,
    balance DECIMAL(12,2) GENERATED ALWAYS AS (net_amount - amount_paid) STORED,
    status ENUM('Draft', 'Pending', 'Partially Paid', 'Paid', 'Overdue', 'Cancelled', 'Waived') DEFAULT 'Pending',
    due_date DATE,
    issue_date DATE DEFAULT (CURDATE()),
    payment_method VARCHAR(50),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (fee_assignment_id) REFERENCES student_fee_assignments(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_student_id (student_id),
    INDEX idx_status (status),
    INDEX idx_due_date (due_date)
);

-- ============================================================
-- 11. PAYMENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_reference VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    invoice_id INT NULL,
    amount_received DECIMAL(12,2) NOT NULL,
    payment_method ENUM('Cash', 'Bank Transfer', 'Mobile Money', 'Cheque', 'Card', 'Other') DEFAULT 'Cash',
    payment_date DATE DEFAULT (CURDATE()),
    transaction_ref VARCHAR(100),
    slip_number VARCHAR(100),
    status ENUM('Pending', 'Completed', 'Failed', 'Reversed') DEFAULT 'Completed',
    received_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (invoice_id) REFERENCES student_invoices(id) ON DELETE SET NULL,
    FOREIGN KEY (received_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    INDEX idx_payment_reference (payment_reference),
    INDEX idx_student_id (student_id),
    INDEX idx_status (status),
    INDEX idx_payment_date (payment_date)
);

-- ============================================================
-- 12. PAYMENT RECEIPTS
-- ============================================================

CREATE TABLE IF NOT EXISTS payment_receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_number VARCHAR(50) UNIQUE NOT NULL,
    payment_id INT NOT NULL,
    student_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_method VARCHAR(50),
    receipt_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    issued_by INT,
    voided BOOLEAN DEFAULT FALSE,
    voided_at TIMESTAMP NULL,
    voided_by INT,
    void_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (issued_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    FOREIGN KEY (voided_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    INDEX idx_receipt_number (receipt_number),
    INDEX idx_payment_id (payment_id),
    INDEX idx_student_id (student_id)
);

-- ============================================================
-- 13. PROOF OF PAYMENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS proof_of_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proof_number VARCHAR(50) UNIQUE NOT NULL,
    payment_id INT NOT NULL,
    student_id INT NOT NULL,
    document_path VARCHAR(500),
    uploaded_by INT,
    verified BOOLEAN DEFAULT FALSE,
    verified_by INT,
    verified_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    FOREIGN KEY (verified_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    INDEX idx_proof_number (proof_number),
    INDEX idx_payment_id (payment_id),
    INDEX idx_student_id (student_id)
);

-- ============================================================
-- 14. FEE ADJUSTMENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS fee_adjustments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    adjustment_number VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    invoice_id INT NULL,
    adjustment_type ENUM('Discount', 'Waiver', 'Penalty', 'Refund', 'Other') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    reason TEXT NOT NULL,
    approved_by INT,
    approved_at TIMESTAMP NULL,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (invoice_id) REFERENCES student_invoices(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    INDEX idx_adjustment_number (adjustment_number),
    INDEX idx_student_id (student_id),
    INDEX idx_status (status)
);

-- ============================================================
-- 15. STUDENT PENALTIES
-- ============================================================

CREATE TABLE IF NOT EXISTS student_penalties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    penalty_number VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    penalty_type VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) DEFAULT 0,
    reason TEXT,
    applied_by INT,
    applied_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    waived BOOLEAN DEFAULT FALSE,
    waived_by INT,
    waived_at TIMESTAMP NULL,
    waiver_reason TEXT,
    status ENUM('Active', 'Waived', 'Paid') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (applied_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    FOREIGN KEY (waived_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    INDEX idx_penalty_number (penalty_number),
    INDEX idx_student_id (student_id),
    INDEX idx_status (status)
);

-- ============================================================
-- 16. PENALTY CONFIGURATIONS
-- ============================================================

CREATE TABLE IF NOT EXISTS penalty_configurations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    penalty_name VARCHAR(100) NOT NULL UNIQUE,
    penalty_type VARCHAR(100),
    amount DECIMAL(10,2) DEFAULT 0,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_penalty_name (penalty_name)
);

-- ============================================================
-- 17. FEE REMINDERS
-- ============================================================

CREATE TABLE IF NOT EXISTS fee_reminders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reminder_number VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    invoice_id INT NULL,
    reminder_type ENUM('Email', 'SMS', 'Letter', 'Call') DEFAULT 'Email',
    reminder_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (invoice_id) REFERENCES student_invoices(id) ON DELETE SET NULL,
    FOREIGN KEY (sent_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    INDEX idx_reminder_number (reminder_number),
    INDEX idx_student_id (student_id),
    INDEX idx_reminder_date (reminder_date)
);

-- ============================================================
-- 18. EXPENDITURE RECORDS
-- ============================================================

CREATE TABLE IF NOT EXISTS expenditure_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expenditure_number VARCHAR(50) UNIQUE NOT NULL,
    budget_record_id INT NULL,
    expenditure_type VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_method VARCHAR(50),
    receipt_number VARCHAR(100),
    expenditure_date DATE DEFAULT (CURDATE()),
    approved_by INT,
    recorded_by INT,
    supporting_document VARCHAR(500),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (budget_record_id) REFERENCES budget_records(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    FOREIGN KEY (recorded_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    INDEX idx_expenditure_number (expenditure_number),
    INDEX idx_expenditure_date (expenditure_date),
    INDEX idx_recorded_by (recorded_by)
);

-- ============================================================
-- 19. GENERAL LEDGER
-- ============================================================

CREATE TABLE IF NOT EXISTS general_ledger (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entry_number VARCHAR(50) UNIQUE NOT NULL,
    account_id INT NOT NULL,
    cost_center_id INT NULL,
    transaction_type ENUM('Debit', 'Credit') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    reference_type VARCHAR(50),
    reference_id INT,
    description TEXT,
    transaction_date DATE DEFAULT (CURDATE()),
    posted_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES chart_of_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (cost_center_id) REFERENCES cost_centers(id) ON DELETE SET NULL,
    FOREIGN KEY (posted_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    INDEX idx_entry_number (entry_number),
    INDEX idx_account_id (account_id),
    INDEX idx_transaction_date (transaction_date)
);

-- ============================================================
-- 20. CASH BOOK
-- ============================================================

CREATE TABLE IF NOT EXISTS cash_book (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entry_number VARCHAR(50) UNIQUE NOT NULL,
    entry_type ENUM('Receipt', 'Payment') NOT NULL,
    description TEXT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    balance DECIMAL(15,2) NOT NULL,
    payment_method VARCHAR(50),
    reference_number VARCHAR(100),
    related_student_id INT NULL,
    transaction_date DATE DEFAULT (CURDATE()),
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (related_student_id) REFERENCES students(id) ON DELETE SET NULL,
    FOREIGN KEY (recorded_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    INDEX idx_entry_number (entry_number),
    INDEX idx_entry_type (entry_type),
    INDEX idx_transaction_date (transaction_date)
);

-- ============================================================
-- 21. ASSET CATEGORIES
-- ============================================================

CREATE TABLE IF NOT EXISTS asset_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    depreciation_rate DECIMAL(5,2) DEFAULT 0,
    useful_life_years INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- 22. ASSETS
-- ============================================================

CREATE TABLE IF NOT EXISTS assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_tag VARCHAR(50) UNIQUE NOT NULL,
    asset_name VARCHAR(255) NOT NULL,
    category_id INT NULL,
    purchase_date DATE,
    purchase_price DECIMAL(12,2),
    current_value DECIMAL(12,2),
    location VARCHAR(255),
    assigned_to INT NULL,
    status ENUM('Active', 'Disposed', 'Lost', 'Under Maintenance') DEFAULT 'Active',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES asset_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    INDEX idx_asset_tag (asset_tag),
    INDEX idx_status (status)
);

-- ============================================================
-- 23. SALARY COMPONENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS salary_components (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_name VARCHAR(100) NOT NULL UNIQUE,
    component_type ENUM('Earning', 'Deduction') DEFAULT 'Earning',
    description TEXT,
    is_percentage BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- 24. STAFF SALARIES
-- ============================================================

CREATE TABLE IF NOT EXISTS staff_salaries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    base_salary DECIMAL(12,2) NOT NULL,
    allowances DECIMAL(12,2) DEFAULT 0,
    deductions DECIMAL(12,2) DEFAULT 0,
    net_salary DECIMAL(12,2) GENERATED ALWAYS AS (base_salary + allowances - deductions) STORED,
    effective_date DATE NOT NULL,
    end_date DATE NULL,
    status ENUM('Active', 'Inactive', 'Pending') DEFAULT 'Active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    INDEX idx_staff_id (staff_id),
    INDEX idx_effective_date (effective_date),
    INDEX idx_status (status)
);

-- ============================================================
-- 25. FINANCIAL REPORTS
-- ============================================================

CREATE TABLE IF NOT EXISTS financial_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_name VARCHAR(255) NOT NULL,
    report_type ENUM('Income Statement', 'Balance Sheet', 'Cash Flow', 'Budget vs Actual', 'Fee Collection', 'Expenditure', 'Custom') NOT NULL,
    report_period VARCHAR(50),
    start_date DATE,
    end_date DATE,
    report_data LONGTEXT,
    generated_by INT,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Draft', 'Final', 'Archived') DEFAULT 'Draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    INDEX idx_report_type (report_type),
    INDEX idx_generated_at (generated_at)
);

-- ============================================================
-- 26. NOTIFICATIONS
-- ============================================================

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_type ENUM('fee_reminder', 'payment_received', 'invoice_generated', 'budget_alert', 'system') DEFAULT 'system',
    recipient_type ENUM('student', 'staff', 'bursar') NOT NULL,
    recipient_id INT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    channel ENUM('email', 'sms', 'in_app') DEFAULT 'in_app',
    is_read BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_recipient_type (recipient_type),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
);

-- ============================================================
-- 27. INSERT DEFAULT PENALTY CONFIGURATIONS
-- ============================================================

INSERT IGNORE INTO penalty_configurations (penalty_name, penalty_type, amount, description) VALUES
('Late Registration', 'Late Fee', 50000, 'Penalty for late course registration'),
('Late Payment (1-7 days)', 'Late Fee', 10000, 'Penalty for fee payment 1-7 days after due date'),
('Late Payment (8-14 days)', 'Late Fee', 25000, 'Penalty for fee payment 8-14 days after due date'),
('Late Payment (15+ days)', 'Late Fee', 50000, 'Penalty for fee payment more than 15 days after due date'),
('Lost Library Book', 'Replacement', 30000, 'Replacement fee for lost library book'),
('Damaged Property', 'Damage', 20000, 'Penalty for damaging school property'),
('ID Card Replacement', 'Administrative', 10000, 'Fee for replacement of lost student ID card');

-- ============================================================
-- 28. INSERT DEFAULT CHART OF ACCOUNTS
-- ============================================================

INSERT IGNORE INTO chart_of_accounts (account_code, account_name, account_type, description) VALUES
('1000', 'Cash and Cash Equivalents', 'Asset', 'Cash on hand and in bank'),
('1100', 'Accounts Receivable', 'Asset', 'Student fees receivable'),
('1200', 'Inventory', 'Asset', 'Supplies and inventory'),
('1500', 'Fixed Assets', 'Asset', 'Property, plant and equipment'),
('2000', 'Accounts Payable', 'Liability', 'Amounts owed to suppliers'),
('2100', 'Accrued Liabilities', 'Liability', 'Accrued expenses'),
('3000', 'Net Assets', 'Equity', 'Institution net worth'),
('4000', 'Tuition Revenue', 'Revenue', 'Income from student tuition'),
('4100', 'Registration Revenue', 'Revenue', 'Income from student registration'),
('4200', 'Other Revenue', 'Revenue', 'Miscellaneous income'),
('5000', 'Salary Expenses', 'Expense', 'Staff salaries and wages'),
('5100', 'Administrative Expenses', 'Expense', 'Office and administrative costs'),
('5200', 'Operational Expenses', 'Expense', 'Day-to-day operational costs'),
('5300', 'Maintenance Expenses', 'Expense', 'Facility maintenance costs');

-- ============================================================
-- 29. INSERT DEFAULT COST CENTERS
-- ============================================================

INSERT IGNORE INTO cost_centers (cost_center_code, cost_center_name, department) VALUES
('CC-EXEC', 'Executive Office', 'Executive Office'),
('CC-NUR', 'Nursing Department', 'Nursing Department'),
('CC-MID', 'Midwifery Department', 'Midwifery Department'),
('CC-ACAD', 'Academic Affairs', 'Academic Affairs'),
('CC-FIN', 'Finance Department', 'Finance Department'),
('CC-HR', 'Human Resources', 'Human Resources'),
('CC-LIB', 'Library Services', 'Library Services'),
('CC-STU', 'Student Affairs', 'Student Affairs'),
('CC-SEC', 'Security Services', 'Security Services'),
('CC-ICT', 'Information Technology', 'Information Technology'),
('CC-FAC', 'Facilities Management', 'Facilities Management');

-- ============================================================
-- END OF BURSAR SYSTEM
-- ============================================================

-- Step 4: Department Dashboards & Views
-- ============================================================
-- ISNM COMPLETE ALL DEPARTMENTS DASHBOARDS SQL
-- Staff-specific tables only - student data is in students database
-- Run AFTER 04_final_complete_staffs_database.sql
-- Prerequisites for views: students database (igangaschoolofl_students_db)
-- Run AFTER sql/students/01_create_students_database.sql
-- ============================================================
