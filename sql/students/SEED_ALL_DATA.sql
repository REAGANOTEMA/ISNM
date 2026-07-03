-- ============================================================
-- COMPREHENSIVE SEED DATA — MATCHES EXACT TABLE SCHEMAS
-- Iganga School of Nursing and Midwifery
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- ============================================================
-- CREATE MISSING TABLES (if not present in this database)
-- ============================================================
CREATE TABLE IF NOT EXISTS course_catalog (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  course_code VARCHAR(50) NOT NULL,
  course_name VARCHAR(255) NOT NULL,
  program VARCHAR(200) DEFAULT NULL,
  level VARCHAR(50) DEFAULT NULL,
  semester VARCHAR(50) DEFAULT NULL,
  credit_hours INT DEFAULT 0,
  is_compulsory TINYINT(1) DEFAULT 1,
  status VARCHAR(20) DEFAULT 'Active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS exams (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  type VARCHAR(50) DEFAULT NULL,
  subject_id INT DEFAULT NULL,
  class_id INT DEFAULT NULL,
  date DATE DEFAULT NULL,
  duration INT DEFAULT 0,
  total_marks INT DEFAULT 100,
  passing_marks INT DEFAULT 50,
  term VARCHAR(20) DEFAULT NULL,
  academic_year VARCHAR(20) DEFAULT NULL,
  status VARCHAR(20) DEFAULT 'scheduled',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS exam_results (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  exam_id INT DEFAULT NULL,
  student_id INT DEFAULT NULL,
  marks_obtained DECIMAL(5,2) DEFAULT NULL,
  grade VARCHAR(5) DEFAULT NULL,
  remarks TEXT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS fee_structure (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  program VARCHAR(200) DEFAULT NULL,
  level VARCHAR(50) DEFAULT NULL,
  academic_year VARCHAR(20) DEFAULT NULL,
  semester VARCHAR(20) DEFAULT NULL,
  fee_type VARCHAR(50) DEFAULT NULL,
  amount DECIMAL(12,2) DEFAULT 0.00,
  description TEXT DEFAULT NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS student_fee_tracking (
  id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  student_id INT DEFAULT NULL,
  fee_type VARCHAR(50) DEFAULT NULL,
  amount DECIMAL(12,2) DEFAULT 0.00,
  amount_paid DECIMAL(12,2) DEFAULT 0.00,
  balance DECIMAL(12,2) DEFAULT 0.00,
  academic_year VARCHAR(20) DEFAULT NULL,
  semester VARCHAR(20) DEFAULT NULL,
  due_date DATE DEFAULT NULL,
  status VARCHAR(20) DEFAULT 'Pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 1. STUDENTS (150 sample students across all 6 programs)
-- ============================================================
INSERT IGNORE INTO students (student_number, index_number, first_name, surname, full_name, email, password, phone, program, current_year, level, gender, status, is_first_login, nationality, address)
WITH RECURSIVE seq (rn) AS (
    SELECT 1 UNION ALL SELECT rn + 1 FROM seq WHERE rn < 150
)
SELECT
    CONCAT('ISNM/', LPAD(s.rn, 4, '0'), '/',
        CASE WHEN s.rn <= 40 THEN '25' WHEN s.rn <= 90 THEN '24' ELSE '23' END),
    CONCAT('UACE/',
        CASE WHEN s.rn <= 20 THEN 'CNM' WHEN s.rn <= 40 THEN 'CNN' WHEN s.rn <= 70 THEN 'DNM' WHEN s.rn <= 90 THEN 'DMM' WHEN s.rn <= 110 THEN 'DNE' ELSE 'BNM' END,
        '/', LPAD(s.rn, 4, '0')),
    ELT(1 + FLOOR(RAND() * 15), 'Sarah', 'Grace', 'Esther', 'Mary', 'Ruth', 'Jane', 'Alice', 'Joy', 'Faith', 'Peace', 'Moses', 'John', 'David', 'Samuel', 'Peter'),
    ELT(1 + FLOOR(RAND() * 15), 'Nakamya', 'Nabirye', 'Namukwaya', 'Nanteza', 'Nakato', 'Mukasa', 'Sserwadda', 'Kizza', 'Wasswa', 'Okello', 'Ochieng', 'Muwonge', 'Lubega', 'Ssenyonjo', 'Kintu'),
    CONCAT(ELT(1 + FLOOR(RAND() * 15), 'Sarah', 'Grace', 'Esther', 'Mary', 'Ruth', 'Jane', 'Alice', 'Joy', 'Faith', 'Peace', 'Moses', 'John', 'David', 'Samuel', 'Peter'),
           ' ', ELT(1 + FLOOR(RAND() * 15), 'Nakamya', 'Nabirye', 'Namukwaya', 'Nanteza', 'Nakato', 'Mukasa', 'Sserwadda', 'Kizza', 'Wasswa', 'Okello', 'Ochieng', 'Muwonge', 'Lubega', 'Ssenyonjo', 'Kintu')),
    CONCAT('student', s.rn, '@isnm.ac.ug'),
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    CONCAT('+256-7', ELT(1 + FLOOR(RAND() * 3), '0', '7', '8'), LPAD(FLOOR(RAND() * 10000000), 7, '0')),
    CASE WHEN s.rn <= 20 THEN 'Certificate in Midwifery'
         WHEN s.rn <= 40 THEN 'Certificate in Nursing'
         WHEN s.rn <= 70 THEN 'Diploma in Nursing'
         WHEN s.rn <= 90 THEN 'Diploma in Midwifery'
         WHEN s.rn <= 110 THEN 'Diploma in Nursing Education'
         ELSE 'Bachelor of Science in Nursing' END,
    CASE WHEN s.rn <= 40 THEN 1 WHEN s.rn <= 90 THEN 2 ELSE 3 END,
    CASE WHEN s.rn <= 40 THEN 'Certificate' WHEN s.rn <= 90 THEN 'Diploma' ELSE 'Degree' END,
    CASE WHEN s.rn % 6 = 0 THEN 'Male' ELSE 'Female' END,
    'Active', 1, 'Ugandan',
    ELT(1 + FLOOR(RAND() * 5), 'Iganga', 'Jinja', 'Bugiri', 'Kamuli', 'Mayuge')
FROM seq s;

-- ============================================================
-- 2. PROGRAMS (existing table — match exact schema)
-- ============================================================
INSERT IGNORE INTO programs (program_code, program_name, program_type, duration_years, total_fee, is_active) VALUES
('CNM', 'Certificate in Midwifery', 'Certificate', 2, 1220000, 1),
('CNN', 'Certificate in Nursing', 'Certificate', 2, 1150000, 1),
('DNM', 'Diploma in Nursing', 'Diploma', 3, 1625000, 1),
('DMM', 'Diploma in Midwifery', 'Diploma', 3, 1685000, 1),
('DNE', 'Diploma in Nursing Education', 'Diploma', 3, 1485000, 1),
('BNM', 'Bachelor of Science in Nursing', 'Degree', 4, 3100000, 1);

-- ============================================================
-- 3. COURSE CATALOG (match exact schema)
-- ============================================================
INSERT IGNORE INTO course_catalog (course_code, course_name, program, level, semester, credit_hours, is_compulsory, status) VALUES
('CNN101', 'Fundamentals of Nursing I', 'Certificate in Nursing', 'Certificate', 'Semester 1', 4, 1, 'Active'),
('CNN102', 'Anatomy & Physiology I', 'Certificate in Nursing', 'Certificate', 'Semester 1', 3, 1, 'Active'),
('CNN103', 'Community Health Nursing I', 'Certificate in Nursing', 'Certificate', 'Semester 1', 3, 1, 'Active'),
('CNN104', 'Medical Surgical Nursing I', 'Certificate in Nursing', 'Certificate', 'Semester 2', 4, 1, 'Active'),
('CNN105', 'Anatomy & Physiology II', 'Certificate in Nursing', 'Certificate', 'Semester 2', 3, 1, 'Active'),
('CNN106', 'Pharmacology I', 'Certificate in Nursing', 'Certificate', 'Semester 2', 3, 1, 'Active'),
('CNN201', 'Fundamentals of Nursing II', 'Certificate in Nursing', 'Certificate', 'Semester 3', 4, 1, 'Active'),
('CNN202', 'Psychiatric Nursing', 'Certificate in Nursing', 'Certificate', 'Semester 3', 3, 1, 'Active'),
('CNN203', 'Pediatric Nursing', 'Certificate in Nursing', 'Certificate', 'Semester 3', 3, 1, 'Active'),
('CNN204', 'Community Health Nursing II', 'Certificate in Nursing', 'Certificate', 'Semester 4', 4, 1, 'Active'),
('CNM101', 'Introduction to Midwifery', 'Certificate in Midwifery', 'Certificate', 'Semester 1', 4, 1, 'Active'),
('CNM102', 'Anatomy for Midwives', 'Certificate in Midwifery', 'Certificate', 'Semester 1', 3, 1, 'Active'),
('CNM103', 'Fundamentals of Midwifery Care', 'Certificate in Midwifery', 'Certificate', 'Semester 1', 4, 1, 'Active'),
('CNM104', 'Antenatal Care', 'Certificate in Midwifery', 'Certificate', 'Semester 2', 4, 1, 'Active'),
('CNM105', 'Labour & Delivery Management', 'Certificate in Midwifery', 'Certificate', 'Semester 2', 5, 1, 'Active'),
('CNM106', 'Postnatal Care', 'Certificate in Midwifery', 'Certificate', 'Semester 2', 3, 1, 'Active'),
('CNM201', 'Emergency Midwifery', 'Certificate in Midwifery', 'Certificate', 'Semester 3', 4, 1, 'Active'),
('CNM202', 'Neonatal Care', 'Certificate in Midwifery', 'Certificate', 'Semester 3', 3, 1, 'Active'),
('CNM203', 'Community Midwifery', 'Certificate in Midwifery', 'Certificate', 'Semester 4', 4, 1, 'Active'),
('DNM101', 'Nursing Science I', 'Diploma in Nursing', 'Diploma', 'Semester 1', 4, 1, 'Active'),
('DNM102', 'Human Anatomy & Physiology I', 'Diploma in Nursing', 'Diploma', 'Semester 1', 3, 1, 'Active'),
('DNM103', 'Nutrition & Dietetics', 'Diploma in Nursing', 'Diploma', 'Semester 1', 3, 1, 'Active'),
('DNM104', 'Medical Surgical Nursing I', 'Diploma in Nursing', 'Diploma', 'Semester 2', 5, 1, 'Active'),
('DNM105', 'Pharmacology I', 'Diploma in Nursing', 'Diploma', 'Semester 2', 3, 1, 'Active'),
('DNM106', 'Pathology & Microbiology', 'Diploma in Nursing', 'Diploma', 'Semester 2', 3, 1, 'Active'),
('DNM201', 'Medical Surgical Nursing II', 'Diploma in Nursing', 'Diploma', 'Semester 3', 5, 1, 'Active'),
('DNM202', 'Pediatric Nursing', 'Diploma in Nursing', 'Diploma', 'Semester 3', 4, 1, 'Active'),
('DNM203', 'Psychiatric Nursing', 'Diploma in Nursing', 'Diploma', 'Semester 3', 3, 1, 'Active'),
('DNM204', 'Community Health Nursing I', 'Diploma in Nursing', 'Diploma', 'Semester 4', 4, 1, 'Active'),
('DNM205', 'Nursing Research', 'Diploma in Nursing', 'Diploma', 'Semester 4', 3, 0, 'Active'),
('DNM301', 'Medical Surgical Nursing III', 'Diploma in Nursing', 'Diploma', 'Semester 5', 5, 1, 'Active'),
('DNM302', 'Community Health Nursing II', 'Diploma in Nursing', 'Diploma', 'Semester 5', 4, 1, 'Active'),
('DNM303', 'Nursing Management & Leadership', 'Diploma in Nursing', 'Diploma', 'Semester 5', 4, 1, 'Active'),
('DNM304', 'Clinical Practicum I', 'Diploma in Nursing', 'Diploma', 'Semester 5', 6, 1, 'Active'),
('DNM305', 'Final Clinical Practicum', 'Diploma in Nursing', 'Diploma', 'Semester 6', 8, 1, 'Active'),
('DNM306', 'Nursing Ethics & Legal Issues', 'Diploma in Nursing', 'Diploma', 'Semester 6', 3, 1, 'Active'),
('DMM101', 'Midwifery Science I', 'Diploma in Midwifery', 'Diploma', 'Semester 1', 4, 1, 'Active'),
('DMM102', 'Anatomy for Midwives', 'Diploma in Midwifery', 'Diploma', 'Semester 1', 3, 1, 'Active'),
('DMM103', 'Reproductive Health', 'Diploma in Midwifery', 'Diploma', 'Semester 1', 3, 1, 'Active'),
('DMM201', 'Advanced Midwifery Practice', 'Diploma in Midwifery', 'Diploma', 'Semester 3', 5, 1, 'Active'),
('DMM202', 'Maternal Health', 'Diploma in Midwifery', 'Diploma', 'Semester 3', 4, 1, 'Active'),
('DMM301', 'Midwifery Clinical Practicum', 'Diploma in Midwifery', 'Diploma', 'Semester 5', 8, 1, 'Active'),
('DNE101', 'Foundations of Education', 'Diploma in Nursing Education', 'Diploma', 'Semester 1', 3, 1, 'Active'),
('DNE102', 'Educational Psychology', 'Diploma in Nursing Education', 'Diploma', 'Semester 1', 3, 1, 'Active'),
('DNE201', 'Curriculum Development', 'Diploma in Nursing Education', 'Diploma', 'Semester 3', 4, 1, 'Active'),
('DNE202', 'Teaching Methods in Nursing', 'Diploma in Nursing Education', 'Diploma', 'Semester 3', 4, 1, 'Active'),
('DNE301', 'Practice Teaching', 'Diploma in Nursing Education', 'Diploma', 'Semester 5', 6, 1, 'Active');

-- ============================================================
-- 4. EXAMS (match exact schema)
-- ============================================================
INSERT IGNORE INTO exams (name, type, subject_id, class_id, date, duration, total_marks, passing_marks, term, academic_year, status) VALUES
('Fundamentals of Nursing I - CAT1', 'CAT', NULL, NULL, '2024-10-15', 60, 30, 15, 'Term 1', '2024/2025', 'completed'),
('Fundamentals of Nursing I - Final', 'Final', NULL, NULL, '2024-12-10', 180, 100, 50, 'Term 1', '2024/2025', 'scheduled'),
('Anatomy & Physiology I - CAT1', 'CAT', NULL, NULL, '2024-10-16', 60, 30, 15, 'Term 1', '2024/2025', 'completed'),
('Anatomy & Physiology I - Final', 'Final', NULL, NULL, '2024-12-11', 180, 100, 50, 'Term 1', '2024/2025', 'scheduled'),
('Intro to Midwifery - CAT1', 'CAT', NULL, NULL, '2024-10-17', 60, 30, 15, 'Term 1', '2024/2025', 'completed'),
('Intro to Midwifery - Final', 'Final', NULL, NULL, '2024-12-12', 180, 100, 50, 'Term 1', '2024/2025', 'scheduled'),
('Nursing Science I - CAT1', 'CAT', NULL, NULL, '2024-10-18', 60, 30, 15, 'Term 1', '2024/2025', 'completed'),
('Nursing Science I - Final', 'Final', NULL, NULL, '2024-12-13', 180, 100, 50, 'Term 1', '2024/2025', 'scheduled'),
('Med Surg Nursing I - CAT1', 'CAT', NULL, NULL, '2025-02-20', 60, 30, 15, 'Term 2', '2024/2025', 'scheduled'),
('Med Surg Nursing I - Final', 'Final', NULL, NULL, '2025-04-25', 180, 100, 50, 'Term 2', '2024/2025', 'scheduled'),
('Community Health I - CAT1', 'CAT', NULL, NULL, '2024-10-20', 60, 30, 15, 'Term 1', '2024/2025', 'completed'),
('Community Health I - Final', 'Final', NULL, NULL, '2024-12-15', 180, 100, 50, 'Term 1', '2024/2025', 'scheduled'),
('Pharmacology I - CAT1', 'CAT', NULL, NULL, '2025-02-22', 60, 30, 15, 'Term 2', '2024/2025', 'scheduled'),
('Med Surg Nursing II - CAT1', 'CAT', NULL, NULL, '2025-06-10', 60, 30, 15, 'Term 3', '2024/2025', 'scheduled'),
('Med Surg Nursing II - Final', 'Final', NULL, NULL, '2025-08-15', 180, 100, 50, 'Term 3', '2024/2025', 'scheduled');

-- ============================================================
-- 5. EXAM RESULTS (match exact schema: exam_id, student_id, marks_obtained, grade, remarks)
-- ============================================================
INSERT IGNORE INTO exam_results (exam_id, student_id, marks_obtained, grade, remarks)
SELECT e.id, s.id,
    FLOOR(RAND() * 40 + 60),
    CASE WHEN RAND() > 0.3 THEN 'A' WHEN RAND() > 0.5 THEN 'B+' ELSE 'B' END,
    'Pass'
FROM students s
CROSS JOIN exams e
WHERE s.id <= 50 AND e.name LIKE '%CAT1%'
LIMIT 200;

-- ============================================================
-- 6. STUDENT ACADEMIC RECORDS (match exact schema)
-- ============================================================
INSERT IGNORE INTO student_academic_records (student_id, semester, academic_year, subject, course_code, grade, marks, credits, gpa, cgpa, remarks)
SELECT s.id, 'Semester 1', '2024/2025', cc.course_name, cc.course_code,
    CASE WHEN RAND() > 0.3 THEN 'A' WHEN RAND() > 0.5 THEN 'B+' ELSE 'B' END,
    ROUND(RAND() * 40 + 60, 2),
    cc.credit_hours,
    ROUND(RAND() * 1.5 + 2.5, 2),
    ROUND(RAND() * 1.5 + 2.5, 2),
    'Pass'
FROM students s
CROSS JOIN course_catalog cc
WHERE s.id <= 100 AND cc.semester = 'Semester 1'
LIMIT 400;

-- ============================================================
-- 7. FEE STRUCTURE (match exact schema)
-- ============================================================
INSERT IGNORE INTO fee_structure (program, level, academic_year, semester, fee_type, amount, description, is_active) VALUES
('Certificate in Nursing', 'Certificate', '2024/2025', 'Semester 1', 'Tuition', 850000, 'Semester 1 Tuition Fee', 1),
('Certificate in Nursing', 'Certificate', '2024/2025', 'Semester 1', 'Functional', 150000, 'Functional Fee', 1),
('Certificate in Nursing', 'Certificate', '2024/2025', 'Semester 1', 'Examination', 50000, 'Examination Fee', 1),
('Certificate in Nursing', 'Certificate', '2024/2025', 'Semester 1', 'Practical', 100000, 'Practical / Clinical Fee', 1),
('Certificate in Midwifery', 'Certificate', '2024/2025', 'Semester 1', 'Tuition', 900000, 'Semester 1 Tuition Fee', 1),
('Certificate in Midwifery', 'Certificate', '2024/2025', 'Semester 1', 'Functional', 150000, 'Functional Fee', 1),
('Certificate in Midwifery', 'Certificate', '2024/2025', 'Semester 1', 'Examination', 50000, 'Examination Fee', 1),
('Certificate in Midwifery', 'Certificate', '2024/2025', 'Semester 1', 'Practical', 120000, 'Practical / Clinical Fee', 1),
('Diploma in Nursing', 'Diploma', '2024/2025', 'Semester 1', 'Tuition', 1200000, 'Semester 1 Tuition Fee', 1),
('Diploma in Nursing', 'Diploma', '2024/2025', 'Semester 1', 'Functional', 200000, 'Functional Fee', 1),
('Diploma in Nursing', 'Diploma', '2024/2025', 'Semester 1', 'Examination', 75000, 'Examination Fee', 1),
('Diploma in Nursing', 'Diploma', '2024/2025', 'Semester 1', 'Practical', 150000, 'Practical / Clinical Fee', 1),
('Diploma in Midwifery', 'Diploma', '2024/2025', 'Semester 1', 'Tuition', 1250000, 'Semester 1 Tuition Fee', 1),
('Diploma in Midwifery', 'Diploma', '2024/2025', 'Semester 1', 'Functional', 200000, 'Functional Fee', 1),
('Diploma in Midwifery', 'Diploma', '2024/2025', 'Semester 1', 'Examination', 75000, 'Examination Fee', 1),
('Diploma in Midwifery', 'Diploma', '2024/2025', 'Semester 1', 'Practical', 160000, 'Practical / Clinical Fee', 1),
('Diploma in Nursing Education', 'Diploma', '2024/2025', 'Semester 1', 'Tuition', 1100000, 'Semester 1 Tuition Fee', 1),
('Diploma in Nursing Education', 'Diploma', '2024/2025', 'Semester 1', 'Functional', 180000, 'Functional Fee', 1),
('Diploma in Nursing Education', 'Diploma', '2024/2025', 'Semester 1', 'Examination', 75000, 'Examination Fee', 1),
('Diploma in Nursing Education', 'Diploma', '2024/2025', 'Semester 1', 'Practical', 130000, 'Practical / Clinical Fee', 1);

-- ============================================================
-- 8. FEE TRACKING (match exact schema: student_id int, fee_type, amount, amount_paid, balance)
-- ============================================================
INSERT IGNORE INTO student_fee_tracking (student_id, fee_type, amount, amount_paid, balance, academic_year, semester, due_date, status)
SELECT s.id,
    'Tuition',
    CASE
        WHEN s.program = 'Certificate in Nursing' THEN 1150000
        WHEN s.program = 'Certificate in Midwifery' THEN 1220000
        WHEN s.program = 'Diploma in Nursing' THEN 1625000
        WHEN s.program = 'Diploma in Midwifery' THEN 1685000
        ELSE 1500000
    END,
    FLOOR(RAND() * 800000 + 200000),
    0,
    '2024/2025',
    'Semester 1',
    '2024-09-30',
    'Partial'
FROM students s WHERE s.id <= 300;

UPDATE student_fee_tracking SET balance = amount - amount_paid;
UPDATE student_fee_tracking SET status = 'Paid' WHERE balance <= 0;
UPDATE student_fee_tracking SET status = 'Pending' WHERE amount_paid = 0;

-- ============================================================
-- 9. INVOICES (match exact schema: balance is STORED GENERATED — do NOT insert)
-- ============================================================
INSERT IGNORE INTO student_invoices (invoice_number, student_id, fee_type, total_amount, amount_paid, due_date, issue_date, status)
SELECT CONCAT('INV', LPAD(s.id, 6, '0'), '-S1'), s.id,
    'Tuition',
    CASE
        WHEN s.program = 'Certificate in Nursing' THEN 1150000
        WHEN s.program = 'Certificate in Midwifery' THEN 1220000
        WHEN s.program = 'Diploma in Nursing' THEN 1625000
        ELSE 1500000
    END,
    FLOOR(RAND() * 800000 + 100000),
    '2024-09-30',
    '2024-08-01',
    'Partially Paid'
FROM students s WHERE s.id <= 300;

-- ============================================================
-- 10. PAYMENTS (match exact schema)
-- ============================================================
INSERT IGNORE INTO payments (payment_reference, student_id, amount_received, payment_method, payment_date, transaction_ref, slip_number, status, received_by, notes)
SELECT CONCAT('PAY', LPAD(s.id, 6, '0'), '-01'),
    s.id,
    FLOOR(RAND() * 500000 + 100000),
    ELT(FLOOR(RAND()*4)+1, 'Cash', 'Mobile Money', 'Bank Transfer', 'Cheque'),
    DATE_ADD('2024-08-01', INTERVAL FLOOR(RAND()*60) DAY),
    CONCAT('TXN', FLOOR(RAND()*999999)),
    CONCAT('SLIP', FLOOR(RAND()*999999)),
    'Completed',
    25,
    'Tuition Fee Payment'
FROM students s WHERE s.id <= 200;

-- ============================================================
-- 11. TIMETABLE (match exact schema: program, year_of_study, semester, day_of_week, time_slot, subject)
-- ============================================================
INSERT IGNORE INTO timetable (program, year_of_study, semester, day_of_week, time_slot, subject, course_code, lecturer, room, academic_year) VALUES
('Certificate in Nursing', 1, 'Semester 1', 'Monday', '08:00-10:00', 'Fundamentals of Nursing I', 'CNN101', 'Sr. Nakamya Florence', 'Lecture Hall A', '2024/2025'),
('Certificate in Nursing', 1, 'Semester 1', 'Wednesday', '10:00-12:00', 'Fundamentals of Nursing I', 'CNN101', 'Sr. Nakamya Florence', 'Skills Lab 1', '2024/2025'),
('Certificate in Nursing', 1, 'Semester 1', 'Tuesday', '08:00-10:00', 'Anatomy & Physiology I', 'CNN102', 'Mr. Okello David', 'Lecture Hall B', '2024/2025'),
('Certificate in Nursing', 1, 'Semester 1', 'Thursday', '14:00-16:00', 'Anatomy & Physiology I', 'CNN102', 'Mr. Okello David', 'Lecture Hall B', '2024/2025'),
('Certificate in Nursing', 1, 'Semester 1', 'Wednesday', '08:00-10:00', 'Community Health Nursing I', 'CNN103', 'Mrs. Nabirye Sarah', 'Lecture Hall A', '2024/2025'),
('Certificate in Nursing', 1, 'Semester 1', 'Friday', '08:00-12:00', 'Community Health Nursing I', 'CNN103', 'Mrs. Nabirye Sarah', 'Community Site', '2024/2025'),
('Certificate in Midwifery', 1, 'Semester 1', 'Monday', '10:00-12:00', 'Introduction to Midwifery', 'CNM101', 'Mrs. Musimenta Grace', 'Lecture Hall A', '2024/2025'),
('Certificate in Midwifery', 1, 'Semester 1', 'Thursday', '08:00-10:00', 'Introduction to Midwifery', 'CNM101', 'Mrs. Musimenta Grace', 'Lecture Hall A', '2024/2025'),
('Certificate in Midwifery', 1, 'Semester 1', 'Tuesday', '10:00-12:00', 'Anatomy for Midwives', 'CNM102', 'Mr. Okello David', 'Lecture Hall B', '2024/2025'),
('Certificate in Midwifery', 1, 'Semester 1', 'Wednesday', '14:00-16:00', 'Fundamentals of Midwifery Care', 'CNM103', 'Mrs. Musimenta Grace', 'Skills Lab 2', '2024/2025'),
('Certificate in Midwifery', 1, 'Semester 1', 'Friday', '10:00-12:00', 'Fundamentals of Midwifery Care', 'CNM103', 'Mrs. Musimenta Grace', 'Skills Lab 2', '2024/2025'),
('Diploma in Nursing', 1, 'Semester 1', 'Monday', '08:00-10:00', 'Nursing Science I', 'DNM101', 'Dr. Mubiru John', 'Lecture Hall C', '2024/2025'),
('Diploma in Nursing', 1, 'Semester 1', 'Thursday', '10:00-12:00', 'Nursing Science I', 'DNM101', 'Dr. Mubiru John', 'Lecture Hall C', '2024/2025'),
('Diploma in Nursing', 1, 'Semester 1', 'Tuesday', '14:00-16:00', 'Human Anatomy & Physiology I', 'DNM102', 'Mr. Okello David', 'Lecture Hall B', '2024/2025'),
('Diploma in Nursing', 1, 'Semester 1', 'Wednesday', '10:00-12:00', 'Nutrition & Dietetics', 'DNM103', 'Mrs. Nalwoga Christine', 'Lecture Hall C', '2024/2025'),
('Diploma in Nursing', 1, 'Semester 2', 'Monday', '14:00-16:00', 'Medical Surgical Nursing I', 'DNM104', 'Sr. Nakamya Florence', 'Skills Lab 1', '2024/2025'),
('Diploma in Nursing', 1, 'Semester 2', 'Friday', '08:00-12:00', 'Medical Surgical Nursing I', 'DNM104', 'Sr. Nakamya Florence', 'Ward 3', '2024/2025'),
('Diploma in Midwifery', 1, 'Semester 1', 'Tuesday', '08:00-10:00', 'Midwifery Science I', 'DMM101', 'Mrs. Musimenta Grace', 'Lecture Hall A', '2024/2025'),
('Diploma in Midwifery', 1, 'Semester 1', 'Wednesday', '08:00-10:00', 'Anatomy for Midwives', 'DMM102', 'Mr. Okello David', 'Lecture Hall B', '2024/2025'),
('Diploma in Midwifery', 1, 'Semester 1', 'Friday', '14:00-16:00', 'Reproductive Health', 'DMM103', 'Mrs. Musimenta Grace', 'Lecture Hall A', '2024/2025'),
('Diploma in Nursing Education', 1, 'Semester 1', 'Monday', '10:00-12:00', 'Foundations of Education', 'DNE101', 'Dr. Waswa Robert', 'Lecture Hall D', '2024/2025'),
('Diploma in Nursing Education', 1, 'Semester 1', 'Thursday', '14:00-16:00', 'Educational Psychology', 'DNE102', 'Dr. Waswa Robert', 'Lecture Hall D', '2024/2025'),
('Diploma in Nursing', 2, 'Semester 3', 'Monday', '08:00-10:00', 'Medical Surgical Nursing II', 'DNM201', 'Dr. Mubiru John', 'Lecture Hall C', '2024/2025'),
('Diploma in Nursing', 2, 'Semester 3', 'Wednesday', '14:00-16:00', 'Pediatric Nursing', 'DNM202', 'Sr. Nakamya Florence', 'Lecture Hall A', '2024/2025'),
('Diploma in Nursing', 2, 'Semester 3', 'Friday', '10:00-12:00', 'Psychiatric Nursing', 'DNM203', 'Mrs. Nabirye Sarah', 'Lecture Hall B', '2024/2025'),
('Diploma in Nursing', 3, 'Semester 5', 'Tuesday', '08:00-12:00', 'Clinical Practicum I', 'DNM304', 'Head of Nursing', 'Iganga RRH', '2024/2025'),
('Diploma in Nursing', 3, 'Semester 5', 'Thursday', '10:00-12:00', 'Nursing Management & Leadership', 'DNM303', 'Dr. Mubiru John', 'Lecture Hall C', '2024/2025');

-- ============================================================
-- 12. ANNOUNCEMENTS (match exact schema: title, body, target_audience, priority, posted_by, is_active, expires_at)
-- ============================================================
INSERT IGNORE INTO announcements (title, body, target_audience, priority, posted_by, is_active, expires_at) VALUES
('Welcome to New Academic Year 2024/2025', 'We welcome all students and staff to the new academic year. Registration is now open for all programs. Please complete your registration before the deadline.', 'All', 'High', 5, 1, '2025-03-31'),
('Semester 1 Examination Schedule Released', 'The examination timetable for Semester 1 has been released. All students should check their examination dates and venues. Examinations begin on 10th December 2024.', 'All', 'High', 7, 1, '2025-01-15'),
('Clinical Placement Guidelines', 'All Diploma Year 2 and Year 3 students scheduled for clinical placements must attend the orientation session on Friday 15th November 2024. Bring your clinical gear.', 'students', 'Normal', 3, 1, '2025-01-31'),
('Staff Training Workshop', 'All staff members are invited to a capacity building workshop on ICT Skills for Education on 20th November 2024. Attendance is mandatory.', 'staff', 'Normal', 23, 1, '2025-01-15'),
('Fee Payment Deadline Reminder', 'Students with outstanding fees are reminded that the deadline for Semester 1 fee payment is 30th September 2024. Defaulters will not be allowed to sit for examinations.', 'students', 'Urgent', 25, 1, '2024-10-31'),
('Library Hours Extended During Exams', 'The library will extend its operating hours during the examination period. The library will now be open from 7:00 AM to 9:00 PM on weekdays.', 'All', 'Low', 10, 1, '2025-01-15'),
('Health and Safety Protocols', 'All students and staff are reminded to follow the health and safety protocols at all times. Hand washing stations are available at all entry points.', 'All', 'Normal', 5, 1, '2025-06-30'),
('Sports Week Activities', 'The annual sports week will be held from 18th to 22nd November 2024. All students are encouraged to participate. Registration at the Guild Office.', 'students', 'Low', 21, 1, '2025-01-31'),
('Nursing Council Registration Update', 'Final year students are reminded to complete their Nursing and Midwifery Council registration. The deadline has been extended to 31st January 2025.', 'students', 'High', 7, 1, '2025-02-28'),
('Holiday Notice - Christmas Break', 'The institution will close for Christmas break on 20th December 2024 and reopen on 6th January 2025. Merry Christmas and Happy New Year!', 'All', 'Low', 5, 1, '2025-01-31');

-- ============================================================
-- 13. LIBRARY BOOKS (match exact schema)
-- ============================================================
INSERT IGNORE INTO library_books (id, book_title, author, isbn, publisher, publication_year, category, total_copies, available_copies, shelf_location) VALUES
(1, 'Myles Textbook for Midwives', 'Jayne Marshall', '978-0702051876', 'Elsevier', 2021, 'Textbook', 6, 5, 'Section A - Shelf 1'),
(2, 'Fundamentals of Nursing', 'Carol Taylor', '978-1496384584', 'Wolters Kluwer', 2022, 'Textbook', 10, 8, 'Section A - Shelf 2'),
(3, 'Medical-Surgical Nursing', 'Donna Ignatavicius', '978-0323596480', 'Elsevier', 2021, 'Textbook', 5, 4, 'Section A - Shelf 3'),
(4, 'Anatomy and Physiology for Nurses', 'Roger Watson', '978-1608318023', 'Saunders', 2020, 'Textbook', 7, 6, 'Section A - Shelf 4'),
(5, 'Pharmacology for Nurses', 'Michael Weatherley', '978-0702077111', 'Elsevier', 2022, 'Textbook', 4, 3, 'Section A - Shelf 5'),
(6, 'Psychiatric Mental Health Nursing', 'Mary Ann Boyd', '978-1496309112', 'Wolters Kluwer', 2021, 'Textbook', 5, 4, 'Section B - Shelf 1'),
(7, 'Community Health Nursing', 'Mary Jo Clark', '978-1284165210', 'Jones & Bartlett', 2022, 'Textbook', 5, 5, 'Section B - Shelf 2'),
(8, 'Maternal Child Nursing Care', 'Shannon Perry', '978-1496309112', 'Elsevier', 2022, 'Textbook', 6, 6, 'Section B - Shelf 3'),
(9, 'Pediatric Nursing', 'Mary Jo Brancaglioni', '978-1608317790', 'Saunders', 2021, 'Textbook', 4, 3, 'Section B - Shelf 4'),
(10, 'Clinical Skills for Nursing', 'Elizabeth Boahene', '978-0702073144', 'Elsevier', 2023, 'Reference', 5, 5, 'Section C - Shelf 1'),
(11, 'Nursing Research Methods', 'Diane Polit', '978-1119538639', 'Wolters Kluwer', 2020, 'Reference', 4, 4, 'Section C - Shelf 2'),
(12, 'Nursing Ethics & Professional Responsibility', 'Janie Butts', '978-0323476638', 'Jones & Bartlett', 2022, 'Reference', 3, 3, 'Section C - Shelf 3'),
(13, 'Clinical Handbook of Fluids Electrolytes', 'Linda Honan', '978-1496384591', 'Wolters Kluwer', 2021, 'Handbook', 3, 2, 'Section C - Shelf 4'),
(14, 'Nursing Diagnosis Handbook', 'Gail Ackley', '978-0135218334', 'Elsevier', 2022, 'Handbook', 7, 6, 'Section D - Shelf 1'),
(15, 'UGANDA Nursing and Midwifery Council Guidelines', 'UNMC', '978-1719643436', 'UNMC Press', 2023, 'Regulation', 12, 10, 'Section D - Shelf 2'),
(16, 'Oxford Dictionary of Medical Terms', 'Oxford University Press', '978-0198765432', 'Oxford', 2020, 'Dictionary', 3, 3, 'Reference Desk'),
(17, 'Holes Human Anatomy & Physiology', 'David Shier', '978-0143774617', 'McGraw Hill', 2021, 'Textbook', 5, 4, 'Section A - Shelf 6'),
(18, 'Lippincott Manual of Nursing Practice', 'Sandra Nettina', '978-1605479767', 'Wolters Kluwer', 2022, 'Handbook', 5, 5, 'Reference Desk'),
(19, 'Brunner & Suddarths Textbook of Medical-Surgical Nursing', 'Janice Hinkle', '978-0323555968', 'Wolters Kluwer', 2022, 'Textbook', 8, 6, 'Section A - Shelf 7'),
(20, 'Foundations of Nursing', 'Cooper Gosnell', '978-0134444819', 'Elsevier', 2020, 'Textbook', 8, 7, 'Section A - Shelf 8');

-- ============================================================
-- 14. LIBRARY BORROWING (match exact schema: student_id varchar)
-- ============================================================
INSERT IGNORE INTO library_borrowing (student_id, book_id, borrow_date, due_date, return_date, fine_amount, fine_paid, status)
SELECT s.student_number, FLOOR(RAND()*20)+1,
    DATE_ADD('2024-09-01', INTERVAL FLOOR(RAND()*60) DAY),
    DATE_ADD('2024-09-15', INTERVAL FLOOR(RAND()*60) DAY),
    CASE WHEN RAND() > 0.3 THEN DATE_ADD('2024-09-15', INTERVAL FLOOR(RAND()*60) DAY) ELSE NULL END,
    CASE WHEN RAND() > 0.7 THEN FLOOR(RAND()*50000) ELSE 0 END,
    IF(RAND() > 0.5, 1, 0),
    CASE WHEN RAND() > 0.3 THEN 'Returned' WHEN RAND() > 0.6 THEN 'Overdue' ELSE 'Borrowed' END
FROM students s WHERE s.id <= 80;

-- ============================================================
-- 15. HOSTEL BLOCKS & ROOMS (match exact schema)
-- ============================================================
CREATE TABLE IF NOT EXISTS hostel_blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    block_name VARCHAR(100) NOT NULL,
    total_rooms INT DEFAULT 0,
    gender ENUM('Male','Female','Mixed') DEFAULT 'Mixed',
    status ENUM('Active','Inactive','Maintenance') DEFAULT 'Active',
    warden_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_block_name (block_name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS hostel_rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(20) NOT NULL,
    hostel_name VARCHAR(100) NOT NULL,
    capacity INT NOT NULL DEFAULT 4,
    occupancy INT NOT NULL DEFAULT 0,
    fee_per_semester DECIMAL(12,2) DEFAULT 0.00,
    status ENUM('Available','Full','Maintenance') DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY room_number (room_number),
    KEY idx_hr_status (status)
) ENGINE=InnoDB;

INSERT IGNORE INTO hostel_blocks (block_name, total_rooms, gender, status) VALUES
('Block A - Queen Anne', 24, 'Female', 'Active'),
('Block B - Victoria', 24, 'Female', 'Active'),
('Block C - Florence Nightingale', 16, 'Female', 'Active'),
('Block D - Mary Seacole', 16, 'Female', 'Active'),
('Block E - Male Hostel', 16, 'Male', 'Active');

INSERT IGNORE INTO hostel_rooms (room_number, hostel_name, capacity, occupancy, fee_per_semester, status)
SELECT CONCAT(ELT(b.bid, 'QA','VB','FN','MS','MH'), '-', f.fn, '-', LPAD(r.rn, 2, '0')),
    ELT(b.bid, 'Block A - Queen Anne', 'Block B - Victoria', 'Block C - Florence Nightingale', 'Block D - Mary Seacole', 'Block E - Male Hostel'),
    4,
    IF(RAND() > 0.5, FLOOR(RAND()*4), 0),
    250000,
    IF(RAND() > 0.3, 'Available', 'Full')
FROM
    (SELECT 1 as fn UNION SELECT 2 UNION SELECT 3) f
CROSS JOIN
    (SELECT 1 as rn UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8) r
CROSS JOIN
    (SELECT 1 as bid UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) b
WHERE (b.bid <= 4 AND f.fn <= 3) OR (b.bid = 5 AND f.fn <= 2);

-- ============================================================
-- 16. CLINICAL SITES (CREATE first since it doesn't exist)
-- ============================================================
CREATE TABLE IF NOT EXISTS clinical_sites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    site_name VARCHAR(200) NOT NULL,
    location VARCHAR(200),
    capacity INT DEFAULT 20,
    contact_person VARCHAR(100),
    contact_phone VARCHAR(20),
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT IGNORE INTO clinical_sites (site_name, location, capacity, contact_person, contact_phone) VALUES
('Iganga Regional Referral Hospital', 'Iganga Town', 30, 'Dr. Wasswa Moses', '+256-772-123456'),
('Iganga Health Centre IV', 'Iganga Municipality', 20, 'Sr. Namukasa Florence', '+256-782-234567'),
('Bugiri District Hospital', 'Bugiri Town', 25, 'Dr. Ochieng James', '+256-702-345678'),
('Namutumba Health Centre III', 'Namutumba', 15, 'Sr. Nabirye Sarah', '+256-772-456789'),
('Kaliro Health Centre III', 'Kaliro Town', 15, 'Mr. Wamboga John', '+256-782-567890'),
('Mayuge Health Centre III', 'Mayuge District', 12, 'Dr. Mugisha Patrick', '+256-702-678901'),
('Busolwe Hospital', 'Butaleja District', 20, 'Sr. Ajok Betty', '+256-772-789012'),
('Kamuli District Hospital', 'Kamuli Town', 25, 'Dr. Ssemwanga Robert', '+256-782-890123');

-- ============================================================
-- 17. STUDENT DISCIPLINE (match exact schema: student_id varchar)
-- ============================================================
INSERT IGNORE INTO student_discipline (student_id, incident_date, incident_type, description, action_taken, action_date, status) VALUES
('183366', '2024-10-10', 'Minor', 'Late submission of assignment', 'Warning issued', '2024-10-12', 'Resolved'),
('183364', '2024-10-15', 'Minor', 'Absence from practical session', 'Make-up session scheduled', '2024-10-17', 'Resolved'),
('183362', '2024-11-01', 'Major', 'Plagiarism in coursework', 'Under review', NULL, 'Open'),
('183359', '2024-10-20', 'Minor', 'Uniform violation', 'Verbal warning', '2024-10-21', 'Resolved'),
('183357', '2024-11-05', 'Minor', 'Noise in dormitory after hours', 'Written warning', '2024-11-06', 'Resolved'),
('183354', '2024-11-10', 'Major', 'Unauthorized absence from clinical', 'Parent notified', '2024-11-12', 'Resolved');

-- ============================================================
-- 18. STUDENT MESSAGES (match exact schema: student_id int)
-- ============================================================
-- Get the IDs of first 5 students
INSERT IGNORE INTO student_messages (student_id, department_email, subject, message, is_read, replied)
SELECT s.id, 'academic@isnm.ac.ug', 'Welcome to ISNM Student Portal', 'Welcome to Iganga School of Nursing and Midwifery. Your student portal is now active.', 0, 0
FROM students s WHERE s.id = 1;

INSERT IGNORE INTO student_messages (student_id, department_email, subject, message, is_read, replied)
SELECT s.id, 'registrar@isnm.ac.ug', 'Registration Confirmation', 'Your Semester 1 registration has been confirmed. Please check your course list.', 1, 0
FROM students s WHERE s.id = 1;

INSERT IGNORE INTO student_messages (student_id, department_email, subject, message, is_read, replied)
SELECT s.id, 'lecturer@isnm.ac.ug', 'Assignment Reminder', 'Reminder: Assignment for Fundamentals of Nursing I is due next week.', 0, 0
FROM students s WHERE s.id = 2;

INSERT IGNORE INTO student_messages (student_id, department_email, subject, message, is_read, replied)
SELECT s.id, 'bursar@isnm.ac.ug', 'Fee Payment Reminder', 'You have an outstanding balance. Please visit the bursar office to clear your fees.', 0, 0
FROM students s WHERE s.id = 3;

INSERT IGNORE INTO student_messages (student_id, department_email, subject, message, is_read, replied)
SELECT s.id, 'bursar@isnm.ac.ug', 'Fee Receipt', 'Your payment of UGX 500,000 has been received. Thank you.', 1, 0
FROM students s WHERE s.id = 4;

-- ============================================================
-- 19. STUDENT NOTIFICATIONS (match exact schema: student_id int)
-- ============================================================
INSERT IGNORE INTO student_notifications (student_id, type, title, message, is_read)
SELECT s.id, 'General', 'Welcome to ISNM', 'Your student portal has been activated. Explore all available features.', 0
FROM students s WHERE s.id = 1;

INSERT IGNORE INTO student_notifications (student_id, type, title, message, is_read)
SELECT s.id, 'Academic', 'Semester 1 Registration Open', 'Registration for Semester 1 2024/2025 is now open. Register before the deadline.', 0
FROM students s WHERE s.id = 1;

INSERT IGNORE INTO student_notifications (student_id, type, title, message, is_read)
SELECT s.id, 'Financial', 'Fee Payment Due', 'Semester 1 fees are due by 30th September 2024.', 1
FROM students s WHERE s.id = 1;

INSERT IGNORE INTO student_notifications (student_id, type, title, message, is_read)
SELECT s.id, 'Academic', 'New Course Assignment', 'You have been assigned to Fundamentals of Nursing I - CNN101.', 0
FROM students s WHERE s.id = 2;

INSERT IGNORE INTO student_notifications (student_id, type, title, message, is_read)
SELECT s.id, 'Clinical', 'Clinical Placement Update', 'Clinical placement at Iganga RRH starts next month. Prepare accordingly.', 0
FROM students s WHERE s.id = 2;

INSERT IGNORE INTO student_notifications (student_id, type, title, message, is_read)
SELECT s.id, 'Administrative', 'Library Book Due', 'Your borrowed book "Myles Textbook for Midwives" is due for return.', 0
FROM students s WHERE s.id = 3;

INSERT IGNORE INTO student_notifications (student_id, type, title, message, is_read)
SELECT s.id, 'Academic', 'Exam Schedule Released', 'Semester 1 exam schedule is now available on the portal.', 0
FROM students s WHERE s.id = 4;

INSERT IGNORE INTO student_notifications (student_id, type, title, message, is_read)
SELECT s.id, 'Administrative', 'Hostel Room Allocation', 'Your room has been allocated: Block B - VB-2-05.', 1
FROM students s WHERE s.id = 5;

-- ============================================================
-- 20. STUDENT REQUESTS (match exact schema)
-- ============================================================
INSERT IGNORE INTO student_requests (student_id, request_type, reason, status)
SELECT s.id, 'Other', 'Request for official transcript for scholarship application', 'Pending'
FROM students s WHERE s.id = 1;

INSERT IGNORE INTO student_requests (student_id, request_type, reason, status)
SELECT s.id, 'Other', 'Request for enrollment confirmation letter', 'Pending'
FROM students s WHERE s.id = 2;

INSERT IGNORE INTO student_requests (student_id, request_type, reason, status)
SELECT s.id, 'Other', 'Request for hostel room allocation', 'Pending'
FROM students s WHERE s.id = 3;

-- ============================================================
-- 21. STUDENT ATTENDANCE (match exact schema)
-- ============================================================
INSERT IGNORE INTO student_attendance (student_id, date, course_code, status)
SELECT s.id, DATE_ADD('2024-09-02', INTERVAL d.day_num DAY), 'CNN101',
    IF(RAND() > 0.15, 'Present', IF(RAND() > 0.5, 'Absent', 'Late'))
FROM students s
CROSS JOIN (SELECT 1 as day_num UNION SELECT 8 UNION SELECT 15 UNION SELECT 22 UNION SELECT 29) d
WHERE s.program = 'Certificate in Nursing' AND s.id <= 100;

-- ============================================================
-- 22. STUDENT COURSE REGISTRATIONS (match exact schema)
-- ============================================================
INSERT IGNORE INTO student_course_registrations (student_id, course_id, academic_year, semester, status)
SELECT s.id, cc.id, '2024/2025', cc.semester, 'Registered'
FROM students s
CROSS JOIN course_catalog cc
WHERE s.program COLLATE utf8mb4_unicode_ci = cc.program AND cc.semester = 'Semester 1' AND s.year = 1
AND s.id <= 200
LIMIT 800;

-- ============================================================
-- 23. STUDENT SEMESTER GPA (match exact schema)
-- ============================================================
CREATE TABLE IF NOT EXISTS student_semester_gpa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    total_credits INT DEFAULT 0,
    earned_credits INT DEFAULT 0,
    semester_gpa DECIMAL(4,2),
    cumulative_gpa DECIMAL(4,2),
    academic_standing ENUM('Good Standing','Probation','Dismissed','Suspended','Graduated') DEFAULT 'Good Standing',
    credits_attempted INT DEFAULT 0,
    credits_passed INT DEFAULT 0,
    courses_completed INT DEFAULT 0,
    courses_failed INT DEFAULT 0,
    calculated_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_student_semester (student_id, academic_year, semester),
    KEY idx_gpa (semester_gpa)
) ENGINE=InnoDB;

INSERT IGNORE INTO student_semester_gpa (student_id, academic_year, semester, total_credits, earned_credits, semester_gpa, cumulative_gpa, academic_standing, credits_attempted, credits_passed, courses_completed, courses_failed)
SELECT s.id, '2024/2025', 'Semester 1',
    18, FLOOR(RAND() * 18 + 5),
    ROUND(RAND() * 1.5 + 2.0, 2),
    ROUND(RAND() * 1.5 + 2.0, 2),
    'Good Standing',
    18, FLOOR(RAND() * 18 + 5),
    FLOOR(RAND() * 6 + 2), FLOOR(RAND() * 2)
FROM students s WHERE s.id <= 200;

-- ============================================================
-- 24. COURSE ASSIGNMENTS (match exact schema: course_id, lecturer_id)
-- ============================================================
CREATE TABLE IF NOT EXISTS course_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    lecturer_id INT NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    assigned_by INT,
    status ENUM('Active','Inactive','Completed') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_course (course_id),
    KEY idx_lecturer (lecturer_id),
    KEY idx_year_sem (academic_year, semester)
) ENGINE=InnoDB;

INSERT IGNORE INTO course_assignments (course_id, lecturer_id, academic_year, semester, assigned_by, status)
SELECT cc.id, 14, '2024/2025', cc.semester, 3, 'Active'
FROM course_catalog cc
WHERE cc.semester IN ('Semester 1', 'Semester 3', 'Semester 5')
LIMIT 30;

-- ============================================================
-- SUMMARY
-- ============================================================
SELECT 'ALL SEED DATA COMPLETE' as Status,
    (SELECT COUNT(*) FROM students) as Students,
    (SELECT COUNT(*) FROM programs) as Programs,
    (SELECT COUNT(*) FROM course_catalog) as Courses,
    (SELECT COUNT(*) FROM exams) as Exams,
    (SELECT COUNT(*) FROM exam_results) as ExamResults,
    (SELECT COUNT(*) FROM timetable) as Timetable,
    (SELECT COUNT(*) FROM announcements) as Announcements,
    (SELECT COUNT(*) FROM library_books) as LibraryBooks,
    (SELECT COUNT(*) FROM hostel_rooms) as HostelRooms,
    (SELECT COUNT(*) FROM clinical_sites) as ClinicalSites,
    (SELECT COUNT(*) FROM student_discipline) as Discipline,
    (SELECT COUNT(*) FROM student_messages) as Messages,
    (SELECT COUNT(*) FROM student_notifications) as Notifications,
    (SELECT COUNT(*) FROM student_requests) as Requests,
    (SELECT COUNT(*) FROM student_fee_tracking) as FeeTracking,
    (SELECT COUNT(*) FROM student_invoices) as Invoices,
    (SELECT COUNT(*) FROM payments) as Payments,
    (SELECT COUNT(*) FROM student_semester_gpa) as SemesterGPA,
    (SELECT COUNT(*) FROM student_course_registrations) as CourseRegistrations,
    (SELECT COUNT(*) FROM course_assignments) as CourseAssignments,
    (SELECT COUNT(*) FROM fee_structure) as FeeStructure;

SET FOREIGN_KEY_CHECKS = 1;
