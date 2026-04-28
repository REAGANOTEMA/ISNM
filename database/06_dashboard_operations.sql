-- ISNM School Management System - Dashboard Operations
-- Core SQL queries for all dashboard functionalities

USE isnm_db;

-- ========================================
-- ACADEMIC MANAGEMENT TABLES
-- ========================================

-- Programs table for managing academic programs
CREATE TABLE IF NOT EXISTS programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_code VARCHAR(20) NOT NULL UNIQUE,
    program_name VARCHAR(255) NOT NULL,
    program_type ENUM('certificate', 'diploma', 'degree') NOT NULL,
    duration_years DECIMAL(3,1) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_program_code (program_code),
    INDEX idx_program_type (program_type),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Courses table for managing individual courses
CREATE TABLE IF NOT EXISTS courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) NOT NULL UNIQUE,
    course_name VARCHAR(255) NOT NULL,
    program_id INT NOT NULL,
    semester ENUM('year1_sem1', 'year1_sem2', 'year2_sem1', 'year2_sem2', 'year3_sem1', 'year3_sem2') NOT NULL,
    credits DECIMAL(4,1) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE,
    INDEX idx_course_code (course_code),
    INDEX idx_program_id (program_id),
    INDEX idx_semester (semester),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Student academic records
CREATE TABLE IF NOT EXISTS student_academic_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    semester ENUM('year1_sem1', 'year1_sem2', 'year2_sem1', 'year2_sem2', 'year3_sem1', 'year3_sem2') NOT NULL,
    academic_year VARCHAR(9) NOT NULL, -- Format: 2024-2025
    grade DECIMAL(4,2),
    grade_letter VARCHAR(2),
    gpa_points DECIMAL(3,2),
    status ENUM('registered', 'in_progress', 'completed', 'failed') DEFAULT 'registered',
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

-- ========================================
-- FINANCE MANAGEMENT TABLES
-- ========================================

-- Fee structure table
CREATE TABLE IF NOT EXISTS fee_structure (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    academic_year VARCHAR(9) NOT NULL,
    semester ENUM('year1_sem1', 'year1_sem2', 'year2_sem1', 'year2_sem2', 'year3_sem1', 'year3_sem2') NOT NULL,
    tuition_fee DECIMAL(10,2) NOT NULL,
    registration_fee DECIMAL(10,2) NOT NULL,
    library_fee DECIMAL(10,2) NOT NULL,
    lab_fee DECIMAL(10,2) NOT NULL,
    examination_fee DECIMAL(10,2) NOT NULL,
    other_fees DECIMAL(10,2) DEFAULT 0,
    total_fee DECIMAL(10,2) GENERATED ALWAYS AS (
        tuition_fee + registration_fee + library_fee + lab_fee + examination_fee + other_fees
    ) STORED,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE,
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
    amount_paid DECIMAL(10,2) DEFAULT 0,
    balance DECIMAL(10,2) GENERATED ALWAYS AS (total_fee - amount_paid) STORED,
    payment_status ENUM('unpaid', 'partial', 'paid', 'overdue') DEFAULT 'unpaid',
    due_date DATE NOT NULL,
    last_payment_date TIMESTAMP NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
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
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'bank_transfer', 'mobile_money', 'cheque') NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    receipt_number VARCHAR(100),
    paid_by VARCHAR(255),
    collected_by INT NOT NULL, -- Staff ID who collected payment
    notes TEXT,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (fee_account_id) REFERENCES student_fee_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (collected_by) REFERENCES users(id),
    INDEX idx_student_id (student_id),
    INDEX idx_fee_account_id (fee_account_id),
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_payment_date (payment_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- COMMUNICATION SYSTEM TABLES
-- ========================================

-- Messages table
CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    recipient_id INT NULL, -- NULL for broadcast messages
    subject VARCHAR(255) NOT NULL,
    message_text TEXT NOT NULL,
    message_type ENUM('individual', 'broadcast', 'announcement', 'notice') NOT NULL,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    status ENUM('draft', 'sent', 'delivered', 'read', 'archived') DEFAULT 'draft',
    sent_at TIMESTAMP NULL,
    read_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_sender_id (sender_id),
    INDEX idx_recipient_id (recipient_id),
    INDEX idx_message_type (message_type),
    INDEX idx_priority (priority),
    INDEX idx_status (status),
    INDEX idx_sent_at (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Message attachments
CREATE TABLE IF NOT EXISTS message_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size DECIMAL(10,2),
    file_type VARCHAR(100),
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    INDEX idx_message_id (message_id),
    INDEX idx_filename (filename)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- ATTENDANCE MANAGEMENT TABLES
-- ========================================

-- Attendance records
CREATE TABLE IF NOT EXISTS attendance_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    attendance_status ENUM('present', 'absent', 'late', 'excused') NOT NULL,
    marked_by INT NOT NULL, -- Staff ID who marked attendance
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
-- EXAMINATIONS AND ASSESSMENTS TABLES
-- ========================================

-- Examinations table
CREATE TABLE IF NOT EXISTS examinations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    exam_name VARCHAR(255) NOT NULL,
    exam_type ENUM('quiz', 'assignment', 'midterm', 'final', 'practical') NOT NULL,
    total_marks DECIMAL(5,2) NOT NULL,
    passing_marks DECIMAL(5,2) NOT NULL,
    exam_date DATE NOT NULL,
    exam_duration INT NOT NULL, -- Duration in minutes
    created_by INT NOT NULL,
    status ENUM('draft', 'scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'draft',
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
    remarks TEXT,
    submitted_by INT NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified BOOLEAN DEFAULT FALSE,
    verified_by INT NULL,
    verified_at TIMESTAMP NULL,
    
    FOREIGN KEY (exam_id) REFERENCES examinations(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (submitted_by) REFERENCES users(id),
    FOREIGN KEY (verified_by) REFERENCES users(id),
    UNIQUE KEY unique_exam_result (exam_id, student_id),
    INDEX idx_exam_id (exam_id),
    INDEX idx_student_id (student_id),
    INDEX idx_marks_obtained (marks_obtained),
    INDEX idx_verified (verified)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- LIBRARY MANAGEMENT TABLES
-- ========================================

-- Books table
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
-- HOSTEL MANAGEMENT TABLES
-- ========================================

-- Hostels table
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

-- Rooms table
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
-- SYSTEM ACTIVITY LOGS
-- ========================================

-- Activity logs table
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
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_table_name (table_name),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- INSERT DEFAULT DATA
-- ========================================

-- Insert default programs
INSERT INTO programs (program_code, program_name, program_type, duration_years, description) VALUES
('CM', 'Certificate in Midwifery', 'certificate', 2.0, '2-year certificate program in midwifery'),
('CN', 'Certificate in Nursing', 'certificate', 2.0, '2-year certificate program in nursing'),
('DMORDN', 'Diploma in Midwifery', 'diploma', 3.0, '3-year diploma program in midwifery')
ON DUPLICATE KEY UPDATE program_name = VALUES(program_name);

-- Get program IDs for use in other tables
SET @cm_program_id = (SELECT id FROM programs WHERE program_code = 'CM');
SET @cn_program_id = (SELECT id FROM programs WHERE program_code = 'CN');
SET @dmordn_program_id = (SELECT id FROM programs WHERE program_code = 'DMORDN');

-- Insert default courses for CM program
INSERT INTO courses (course_code, course_name, program_id, semester, credits, description) VALUES
('CM101', 'Anatomy and Physiology I', @cm_program_id, 'year1_sem1', 3.0, 'Basic human anatomy and physiology'),
('CM102', 'Nursing Fundamentals I', @cm_program_id, 'year1_sem1', 4.0, 'Introduction to nursing principles'),
('CM103', 'Pharmacology I', @cm_program_id, 'year1_sem1', 2.0, 'Basic pharmacology for nurses'),
('CM104', 'Microbiology', @cm_program_id, 'year1_sem2', 3.0, 'Medical microbiology'),
('CM105', 'Midwifery I', @cm_program_id, 'year1_sem2', 4.0, 'Introduction to midwifery')
ON DUPLICATE KEY UPDATE course_name = VALUES(course_name);

-- Insert default courses for CN program
INSERT INTO courses (course_code, course_name, program_id, semester, credits, description) VALUES
('CN101', 'Anatomy and Physiology I', @cn_program_id, 'year1_sem1', 3.0, 'Basic human anatomy and physiology'),
('CN102', 'Nursing Fundamentals I', @cn_program_id, 'year1_sem1', 4.0, 'Introduction to nursing principles'),
('CN103', 'Psychology for Nurses', @cn_program_id, 'year1_sem1', 2.0, 'Basic psychology'),
('CN104', 'Medical-Surgical Nursing I', @cn_program_id, 'year1_sem2', 4.0, 'Medical-surgical nursing'),
('CN105', 'Community Health Nursing I', @cn_program_id, 'year1_sem2', 3.0, 'Community health nursing')
ON DUPLICATE KEY UPDATE course_name = VALUES(course_name);

-- Insert default courses for DMORDN program
INSERT INTO courses (course_code, course_name, program_id, semester, credits, description) VALUES
('DM101', 'Advanced Anatomy', @dmordn_program_id, 'year1_sem1', 3.0, 'Advanced human anatomy'),
('DM102', 'Advanced Midwifery I', @dmordn_program_id, 'year1_sem1', 4.0, 'Advanced midwifery principles'),
('DM103', 'Research Methods', @dmordn_program_id, 'year1_sem1', 2.0, 'Nursing research methods'),
('DM104', 'Obstetrics and Gynecology', @dmordn_program_id, 'year1_sem2', 4.0, 'OB/GYN advanced topics'),
('DM105', 'Neonatology', @dmordn_program_id, 'year1_sem2', 3.0, 'Neonatal care')
ON DUPLICATE KEY UPDATE course_name = VALUES(course_name);

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

-- Insert default fee structure
INSERT INTO fee_structure (program_id, academic_year, semester, tuition_fee, registration_fee, library_fee, lab_fee, examination_fee, other_fees) VALUES
(@cm_program_id, '2024-2025', 'year1_sem1', 1500000, 50000, 100000, 200000, 150000, 50000),
(@cm_program_id, '2024-2025', 'year1_sem2', 1500000, 0, 100000, 200000, 150000, 50000),
(@cn_program_id, '2024-2025', 'year1_sem1', 1500000, 50000, 100000, 200000, 150000, 50000),
(@cn_program_id, '2024-2025', 'year1_sem2', 1500000, 0, 100000, 200000, 150000, 50000),
(@dmordn_program_id, '2024-2025', 'year1_sem1', 2000000, 50000, 150000, 250000, 200000, 75000),
(@dmordn_program_id, '2024-2025', 'year1_sem2', 2000000, 0, 150000, 250000, 200000, 75000)
ON DUPLICATE KEY UPDATE tuition_fee = VALUES(tuition_fee);

-- Create views for dashboard operations
CREATE OR REPLACE VIEW dashboard_student_summary AS
SELECT 
    u.id,
    u.full_name,
    u.index_number,
    u.phone,
    p.program_name,
    COUNT(sar.id) as registered_courses,
    COUNT(CASE WHEN sar.status = 'completed' THEN 1 END) as completed_courses,
    AVG(sar.gpa_points) as current_gpa,
    COUNT(CASE WHEN ar.attendance_status = 'present' THEN 1 END) as present_days,
    COUNT(CASE WHEN ar.attendance_status = 'absent' THEN 1 END) as absent_days,
    sfa.balance as fee_balance,
    sfa.payment_status,
    u.last_login
FROM users u
LEFT JOIN student_academic_records sar ON u.id = sar.student_id
LEFT JOIN programs p ON p.id = (SELECT program_id FROM courses WHERE id = sar.course_id LIMIT 1)
LEFT JOIN attendance_records ar ON u.id = ar.student_id
LEFT JOIN student_fee_accounts sfa ON u.id = sfa.student_id AND sfa.academic_year = '2024-2025'
WHERE u.type = 'student' AND u.status = 'active'
GROUP BY u.id, u.full_name, u.index_number, u.phone, p.program_name, sfa.balance, sfa.payment_status, u.last_login;

CREATE OR REPLACE VIEW dashboard_staff_summary AS
SELECT 
    u.id,
    u.full_name,
    u.email,
    u.role,
    COUNT(DISTINCT c.id) as courses_taught,
    COUNT(DISTINCT sar.student_id) as students_supervised,
    COUNT(DISTINCT e.id) as exams_conducted,
    COUNT(DISTINCT ar.id) as attendance_marked,
    COUNT(DISTINCT pt.id) as payments_collected,
    u.last_login
FROM users u
LEFT JOIN courses c ON u.id = (SELECT created_by FROM courses WHERE id = c.id)
LEFT JOIN student_academic_records sar ON u.id = (SELECT marked_by FROM attendance_records WHERE student_id = sar.student_id LIMIT 1)
LEFT JOIN examinations e ON u.id = e.created_by
LEFT JOIN attendance_records ar ON u.id = ar.marked_by
LEFT JOIN payment_transactions pt ON u.id = pt.collected_by
WHERE u.type = 'staff' AND u.status = 'active'
GROUP BY u.id, u.full_name, u.email, u.role, u.last_login;

-- Success message
SELECT 'Dashboard operations database setup completed successfully!' as message;
SELECT 'All tables, views, and default data created for comprehensive dashboard functionality' as note;
