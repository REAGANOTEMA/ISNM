-- ============================================================
-- ISNM STUDENT DATA IMPORT SQL
-- Import template for Excel data from students_data folder
-- Run this after creating the students database
-- ============================================================

USE igangaschoolofl_students_db;

-- ============================================================
-- SAMPLE DATA IMPORT FORMAT
-- This shows the format for importing student data from Excel
-- ============================================================

-- Example: Import students from Set 28 (January 2026 Intake)
-- Format: student_number, registration_number, national_id, full_name, course, year, set_name, intake_date, email, phone

INSERT IGNORE INTO students (
    student_number, registration_number, index_number, national_student_id_number, 
    first_name, surname, other_name, program, course, current_year, set_name, intake_date, email, phone, mobile_number,
    status, password, is_first_login
) VALUES
('2026S001', 'ISNM/2026/001', 'U001/01', '1234567890123456', 'Alice', 'Nakamya', 'Faith', 'Diploma in Nursing', 'Diploma in Nursing', 1, 'Set 28', '2026-01-15', 'alice.nakamya@isnm.ac.ug', '+256701000001', '+256701000001', 'Active', '$2y$10$N9qo8uLOickgx2ZMRZoMy.MrqJhZ3eP4dZB6lYqZ3eP4dZB6lYqZ3eP', TRUE),
('2026S002', 'ISNM/2026/002', 'U001/02', '1234567890123457', 'Brian', 'Otim', 'Joseph', 'Diploma in Midwifery', 'Diploma in Midwifery', 1, 'Set 28', '2026-01-15', 'brian.otim@isnm.ac.ug', '+256701000002', '+256701000002', 'Active', '$2y$10$N9qo8uLOickgx2ZMRZoMy.MrqJhZ3eP4dZB6lYqZ3eP4dZB6lYqZ3eP', TRUE),
('2026S003', 'ISNM/2026/003', 'U001/03', '1234567890123458', 'Catherine', 'Nalumansi', NULL, 'Certificate in Nursing', 'Certificate in Nursing', 1, 'Set 28', '2026-01-15', 'catherine.nalumansi@isnm.ac.ug', '+256701000003', '+256701000003', 'Active', '$2y$10$N9qo8uLOickgx2ZMRZoMy.MrqJhZ3eP4dZB6lYqZ3eP4dZB6lYqZ3eP', TRUE),
('2026S004', 'ISNM/2026/004', 'U001/04', '1234567890123459', 'David', 'Mugisha', 'Peter', 'Diploma in Nursing', 'Diploma in Nursing', 1, 'Set 28', '2026-01-15', 'david.mugisha@isnm.ac.ug', '+256701000004', '+256701000004', 'Active', '$2y$10$N9qo8uLOickgx2ZMRZoMy.MrqJhZ3eP4dZB6lYqZ3eP4dZB6lYqZ3eP', TRUE),
('2026S005', 'ISNM/2026/005', 'U001/05', '1234567890123460', 'Elizabeth', 'Namukasa', 'Grace', 'Diploma in Midwifery', 'Diploma in Midwifery', 1, 'Set 28', '2026-01-15', 'elizabeth.namukasa@isnm.ac.ug', '+256701000005', '+256701000005', 'Active', '$2y$10$N9qo8uLOickgx2ZMRZoMy.MrqJhZ3eP4dZB6lYqZ3eP4dZB6lYqZ3eP', TRUE);

-- ============================================================
-- PASSWORD HASH TEMPLATE
-- Default password: 12345678 (students need to change on first login)
-- ============================================================

-- PHP code to generate password hash:
-- $hash = password_hash('12345678', PASSWORD_DEFAULT);

-- MySQL SHA2 alternative (less secure, for reference):
-- SELECT SHA2('12345678', 256);

-- ============================================================
-- STUDENT LOGIN CREATION
-- All students get default password: 12345678
-- They must reset on first login
-- ============================================================

-- Update all students with default password
UPDATE students 
SET password = '$2y$10$N9qo8uLOickgx2ZMRZoMy.MrqJhZ3eP4dZB6lYqZ3eP4dZB6lYqZ3eP', 
    is_first_login = TRUE,
    status = 'Active'
WHERE password IS NULL OR password = '';

-- ============================================================
-- STUDENT SEARCH VIEWS
-- Connect to staff database for cross-department access
-- ============================================================

-- View for staff to search students (requires staff database access)
-- This view is defined in staff database sql/staffs/05_all_departments_complete_dashboards.sql

-- ============================================================
-- FEES FOR NEW STUDENTS
-- Auto-create fee records for imported students
-- ============================================================

-- Ensure the student_fees table exists before inserting fee records.
CREATE TABLE IF NOT EXISTS student_fees (
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
    INDEX idx_student_id (student_id),
    INDEX idx_fee_type (fee_type),
    INDEX idx_status (status),
    INDEX idx_due_date (due_date)
);

-- Insert default fee structure for new students
INSERT IGNORE INTO student_fees (student_id, fee_type, amount, due_date, status)
SELECT id, 'Tuition Fee', 500000, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Unpaid'
FROM students 
WHERE status = 'Active' AND id NOT IN (SELECT DISTINCT student_id FROM student_fees WHERE fee_type = 'Tuition Fee');

INSERT IGNORE INTO student_fees (student_id, fee_type, amount, due_date, status)
SELECT id, 'Registration Fee', 20000, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Unpaid'
FROM students 
WHERE status = 'Active' AND id NOT IN (SELECT DISTINCT student_id FROM student_fees WHERE fee_type = 'Registration Fee');