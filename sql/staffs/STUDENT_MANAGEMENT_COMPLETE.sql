-- ============================================================
-- STUDENT MANAGEMENT COMPLETE SQL
-- Database: igangaschoolofl_staffs_db (port 3307)
-- Compatible with MySQL 8.0
-- Uses: CREATE TABLE IF NOT EXISTS, INSERT IGNORE, simple UPDATE
-- ============================================================

USE igangaschoolofl_staffs_db;

-- ============================================================
-- 1. ADMISSION REQUIREMENTS
-- Remove incorrect supplies data, insert proper requirements
-- ============================================================

DELETE FROM admission_requirements WHERE requirement_name IN (
    'Surgical Gloves','Exam Gloves','Photocopying Ream','Ruled Paper Reams',
    'Omo','Toilet Papers','Compound Brooms','Soft Brooms','Rake','Cobweb Brush'
);

INSERT IGNORE INTO admission_requirements (id, requirement_name, type, is_active, is_mandatory, display_order) VALUES
(1, 'Completed Application Form', 'Document', 1, 1, 1),
(2, 'O-Level Certificate (UCE)', 'Document', 1, 1, 2),
(3, 'A-Level Certificate (UACE)', 'Document', 1, 1, 3),
(4, 'Birth Certificate', 'Document', 1, 1, 4),
(5, 'Passport Photos (4)', 'Photo', 1, 1, 5),
(6, 'National ID Copy', 'Document', 1, 1, 6),
(7, 'Medical Report', 'Document', 1, 1, 7),
(8, 'Recommendation Letter', 'Document', 1, 1, 8),
(9, 'Proof of Payment (Application Fee)', 'Payment', 1, 1, 9),
(10, 'Interview Letter', 'Document', 1, 1, 10),
(11, 'Guardian Consent Form', 'Form', 1, 1, 11),
(12, 'Health Declaration Form', 'Form', 1, 1, 12),
(13, 'Immunization Record', 'Document', 1, 0, 13),
(14, 'Previous School Report', 'Document', 1, 0, 14),
(15, 'Character Reference', 'Document', 1, 0, 15),
(16, 'Employment Letter (if applicable)', 'Document', 1, 0, 16),
(17, 'Community Service Certificate', 'Certificate', 1, 0, 17),
(18, 'Sports Certificate', 'Certificate', 1, 0, 18),
(19, 'Transcript', 'Document', 1, 1, 19),
(20, 'English Proficiency Certificate', 'Certificate', 1, 0, 20);

-- ============================================================
-- 2. STUDENT PROFILES TABLE (students_db)
-- ============================================================

USE igangaschoolofl_students_db;

CREATE TABLE IF NOT EXISTS student_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    admission_status VARCHAR(50) DEFAULT 'Pending',
    requirements_completed INT DEFAULT 0,
    requirements_total INT DEFAULT 20,
    documents_uploaded INT DEFAULT 0,
    fees_paid DECIMAL(12,2) DEFAULT 0.00,
    fees_total DECIMAL(12,2) DEFAULT 0.00,
    fee_status VARCHAR(30) DEFAULT 'Unpaid',
    clearance_status VARCHAR(30) DEFAULT 'Pending',
    medical_clearance VARCHAR(30) DEFAULT 'Pending',
    library_clearance VARCHAR(30) DEFAULT 'Pending',
    hostel_clearance VARCHAR(30) DEFAULT 'Pending',
    academic_clearance VARCHAR(30) DEFAULT 'Pending',
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_student_profile (student_id),
    KEY idx_admission_status (admission_status),
    KEY idx_fee_status (fee_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 3. STUDENT FEE TRACKING TABLE (students_db)
-- ============================================================

CREATE TABLE IF NOT EXISTS student_fee_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    fee_type VARCHAR(100) NOT NULL,
    amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    balance DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(20) DEFAULT '',
    due_date DATE NULL,
    status VARCHAR(30) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_student_fee (student_id),
    KEY idx_fee_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 4. STUDENT DOCUMENT UPLOADS TABLE (students_db)
-- ============================================================

CREATE TABLE IF NOT EXISTS student_document_uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    document_name VARCHAR(300) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT DEFAULT 0,
    mime_type VARCHAR(100) DEFAULT '',
    uploaded_by INT NOT NULL,
    uploaded_by_role VARCHAR(100) DEFAULT '',
    verification_status ENUM('Pending','Verified','Rejected') DEFAULT 'Pending',
    verified_by INT NULL,
    verified_at DATETIME NULL,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_doc_student (student_id),
    KEY idx_doc_type (document_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 5. STUDENT ADMISSION TRACKING TABLE (staffs_db)
-- ============================================================

USE igangaschoolofl_staffs_db;

CREATE TABLE IF NOT EXISTS student_admission_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_number VARCHAR(50) NOT NULL,
    full_name VARCHAR(300) NOT NULL,
    program VARCHAR(150) DEFAULT '',
    intake VARCHAR(100) DEFAULT '',
    admission_date DATE NULL,
    admission_status VARCHAR(50) DEFAULT 'Pending',
    requirements_completed INT DEFAULT 0,
    requirements_total INT DEFAULT 20,
    fee_status VARCHAR(30) DEFAULT 'Unpaid',
    total_fees DECIMAL(12,2) DEFAULT 0.00,
    amount_paid DECIMAL(12,2) DEFAULT 0.00,
    documents_uploaded INT DEFAULT 0,
    assigned_to INT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_admission_tracking (student_number),
    KEY idx_admission_status (admission_status),
    KEY idx_fee_status (fee_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 6. STUDENT PROFILE COMMENTS TABLE (staffs_db)
-- ============================================================

CREATE TABLE IF NOT EXISTS student_profile_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_number VARCHAR(50) NOT NULL,
    commenter_id INT NOT NULL,
    commenter_name VARCHAR(200) DEFAULT '',
    comment TEXT NOT NULL,
    is_internal TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_comments_student (student_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 7. STUDENT AUDIT LOG TABLE (staffs_db)
-- ============================================================

CREATE TABLE IF NOT EXISTS student_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(200) NOT NULL,
    module VARCHAR(100) NOT NULL,
    record_id INT DEFAULT 0,
    student_number VARCHAR(50) DEFAULT '',
    description TEXT,
    ip_address VARCHAR(50) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_audit_module (module),
    KEY idx_audit_student (student_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 8. STUDENT STATUS HISTORY TABLE (staffs_db)
-- ============================================================

CREATE TABLE IF NOT EXISTS student_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_number VARCHAR(50) NOT NULL,
    old_status VARCHAR(50) DEFAULT '',
    new_status VARCHAR(50) NOT NULL,
    changed_by INT NOT NULL,
    changed_by_name VARCHAR(200) DEFAULT '',
    reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_status_student (student_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 9. SEED APPLICANTS (20 sample records)
-- ============================================================

INSERT IGNORE INTO applicants (id, full_name, other_names, date_of_birth, gender, phone, email, address, guardian_name, guardian_phone, guardian_relationship, application_number, program_id, intake, admission_date, status) VALUES
(1, 'Grace Nakato', '', '2002-03-15', 'Female', '+256701234001', 'grace.nakato@email.com', 'Kampala', 'John Nakato', '+256701234101', 'Father', 'APP-2024-001', 1, 'January', '2024-01-15', 'Registered'),
(2, 'David Ssali', '', '2001-07-22', 'Male', '+256701234002', 'david.ssali@email.com', 'Wakiso', 'Mary Ssali', '+256701234102', 'Mother', 'APP-2024-002', 2, 'January', '2024-01-15', 'Registered'),
(3, 'Mary Nalwoga', '', '2003-01-10', 'Female', '+256701234003', 'mary.nalwoga@email.com', 'Mukono', 'Peter Nalwoga', '+256701234103', 'Father', 'APP-2024-003', 1, 'January', '2024-01-15', 'Registered'),
(4, 'James Okello', '', '2000-11-05', 'Male', '+256701234004', 'james.okello@email.com', 'Jinja', 'Grace Okello', '+256701234104', 'Mother', 'APP-2024-004', 3, 'January', '2024-01-15', 'Registered'),
(5, 'Sarah Kyomugisha', '', '2002-06-18', 'Female', '+256701234005', 'sarah.kyomugisha@email.com', 'Mbarara', 'David Kyomugisha', '+256701234105', 'Father', 'APP-2024-005', 1, 'January', '2024-01-15', 'Registered'),
(6, 'Aisha Nansubuga', '', '2001-09-25', 'Female', '+256701234006', 'aisha.nansubuga@email.com', 'Lira', 'Hassan Nansubuga', '+256701234106', 'Father', 'APP-2024-006', 2, 'May', NULL, 'Approved'),
(7, 'Robert Ochieng', '', '2002-02-14', 'Male', '+256701234007', 'robert.ochieng@email.com', 'Soroti', 'Florence Ochieng', '+256701234107', 'Mother', 'APP-2024-007', 1, 'May', NULL, 'Approved'),
(8, 'Betty Namukasa', '', '2003-04-30', 'Female', '+256701234008', 'betty.namukasa@email.com', 'Entebbe', 'Joseph Namukasa', '+256701234108', 'Father', 'APP-2024-008', 3, 'May', NULL, 'Under Review'),
(9, 'Moses Byaruhanga', '', '2000-12-20', 'Male', '+256701234009', 'moses.byaruhanga@email.com', 'Kabale', 'Agnes Byaruhanga', '+256701234109', 'Mother', 'APP-2024-009', 1, 'May', NULL, 'Under Review'),
(10, 'Esther Auma', '', '2002-08-12', 'Female', '+256701234010', 'esther.auma@email.com', 'Gulu', 'Paul Auma', '+256701234110', 'Father', 'APP-2024-010', 2, 'May', NULL, 'New Applicant'),
(11, 'Samuel Mugisha', '', '2001-05-08', 'Male', '+256701234011', 'samuel.mugisha@email.com', 'Kasese', 'Ruth Mugisha', '+256701234111', 'Mother', 'APP-2024-011', 1, 'August', NULL, 'New Applicant'),
(12, 'Priscilla Ojok', '', '2003-07-03', 'Female', '+256701234012', 'priscilla.ojok@email.com', 'Arua', 'Charles Ojok', '+256701234112', 'Father', 'APP-2024-012', 3, 'August', NULL, 'New Applicant'),
(13, 'Isaac Tumwine', '', '2002-10-16', 'Male', '+256701234013', 'isaac.tumwine@email.com', 'Fort Portal', 'Juliet Tumwine', '+256701234113', 'Mother', 'APP-2024-013', 1, 'August', NULL, 'Rejected'),
(14, 'Hannah Apio', '', '2001-01-28', 'Female', '+256701234014', 'hannah.apio@email.com', 'Lira', 'Steven Apio', '+256701234114', 'Father', 'APP-2024-014', 2, 'January', '2024-01-15', 'Registered'),
(15, 'Daniel Kizza', '', '2002-04-11', 'Male', '+256701234015', 'daniel.kizza@email.com', 'Mbarara', 'Catherine Kizza', '+256701234115', 'Mother', 'APP-2024-015', 1, 'May', NULL, 'Approved'),
(16, 'Joyce Atim', '', '2003-09-07', 'Female', '+256701234016', 'joyce.atim@email.com', 'Soroti', 'George Atim', '+256701234116', 'Father', 'APP-2024-016', 3, 'May', NULL, 'Under Review'),
(17, 'Patrick Opio', '', '2000-03-19', 'Male', '+256701234017', 'patrick.opio@email.com', 'Gulu', 'Mary Opio', '+256701234117', 'Mother', 'APP-2024-017', 1, 'August', NULL, 'New Applicant'),
(18, 'Catherine Akello', '', '2002-11-22', 'Female', '+256701234018', 'catherine.akello@email.com', 'Jinja', 'James Akello', '+256701234118', 'Father', 'APP-2024-018', 2, 'August', NULL, 'New Applicant'),
(19, 'Fred Wasswa', '', '2001-06-14', 'Male', '+256701234019', 'fred.wasswa@email.com', 'Kampala', 'Nancy Wasswa', '+256701234119', 'Mother', 'APP-2024-019', 1, 'January', '2024-01-15', 'Registered'),
(20, 'Gladys Nabirye', '', '2003-02-25', 'Female', '+256701234020', 'gladys.nabirye@email.com', 'Mukono', 'Henry Nabirye', '+256701234120', 'Father', 'APP-2024-020', 3, 'May', NULL, 'Approved');

-- ============================================================
-- 10. SEED APPLICANT REQUIREMENT STATUS
-- ============================================================

-- Registered students: all requirements verified
INSERT IGNORE INTO applicant_requirement_status (applicant_id, requirement_id, status, submitted_by, submitted_at, verified_by, verified_at)
SELECT a.id, r.id, 'Verified', 1, NOW(), 1, NOW()
FROM applicants a CROSS JOIN admission_requirements r
WHERE a.status = 'Registered' AND r.is_active = 1;

-- Approved students: first 8 verified, next 4 submitted
INSERT IGNORE INTO applicant_requirement_status (applicant_id, requirement_id, status, submitted_by, submitted_at, verified_by, verified_at)
SELECT a.id, r.id, 'Verified', 1, NOW(), 1, NOW()
FROM applicants a CROSS JOIN admission_requirements r
WHERE a.status = 'Approved' AND r.is_active = 1 AND r.display_order <= 8;

INSERT IGNORE INTO applicant_requirement_status (applicant_id, requirement_id, status, submitted_by, submitted_at)
SELECT a.id, r.id, 'Submitted', 1, NOW()
FROM applicants a CROSS JOIN admission_requirements r
WHERE a.status = 'Approved' AND r.is_active = 1 AND r.display_order > 8 AND r.display_order <= 12;

-- Under Review: first 6 submitted, rest not submitted
INSERT IGNORE INTO applicant_requirement_status (applicant_id, requirement_id, status, submitted_by, submitted_at)
SELECT a.id, r.id, 'Submitted', 1, NOW()
FROM applicants a CROSS JOIN admission_requirements r
WHERE a.status = 'Under Review' AND r.is_active = 1 AND r.display_order <= 6;

INSERT IGNORE INTO applicant_requirement_status (applicant_id, requirement_id, status)
SELECT a.id, r.id, 'Not Submitted'
FROM applicants a CROSS JOIN admission_requirements r
WHERE a.status = 'Under Review' AND r.is_active = 1 AND r.display_order > 6;

-- New Applicants: all not submitted
INSERT IGNORE INTO applicant_requirement_status (applicant_id, requirement_id, status)
SELECT a.id, r.id, 'Not Submitted'
FROM applicants a CROSS JOIN admission_requirements r
WHERE a.status = 'New Applicant' AND r.is_active = 1;

-- Rejected: first 3 rejected, rest not submitted
INSERT IGNORE INTO applicant_requirement_status (applicant_id, requirement_id, status, submitted_by, submitted_at, rejected_by, verified_at, remarks)
SELECT a.id, r.id, 'Rejected', 1, NOW(), 1, NOW(), 'Document not clear'
FROM applicants a CROSS JOIN admission_requirements r
WHERE a.status = 'Rejected' AND r.is_active = 1 AND r.display_order IN (1, 2, 3);

INSERT IGNORE INTO applicant_requirement_status (applicant_id, requirement_id, status)
SELECT a.id, r.id, 'Not Submitted'
FROM applicants a CROSS JOIN admission_requirements r
WHERE a.status = 'Rejected' AND r.is_active = 1 AND r.display_order NOT IN (1, 2, 3);

-- ============================================================
-- 11. SEED STUDENT ADMISSION TRACKING
-- ============================================================

INSERT IGNORE INTO student_admission_tracking (student_number, full_name, program, intake, admission_date, admission_status, requirements_completed, requirements_total, fee_status, total_fees, amount_paid, documents_uploaded)
SELECT
    a.application_number,
    a.full_name,
    COALESCE(
        (SELECT p.program_name FROM academic_programs p WHERE p.id = a.program_id LIMIT 1),
        'Unknown Program'
    ),
    COALESCE(a.intake, 'January'),
    a.admission_date,
    CASE a.status
        WHEN 'Registered' THEN 'Registered'
        WHEN 'Approved' THEN 'Approved'
        WHEN 'Under Review' THEN 'Under Review'
        WHEN 'New Applicant' THEN 'New Applicant'
        WHEN 'Rejected' THEN 'Rejected'
        ELSE 'Pending'
    END,
    (SELECT COUNT(*) FROM applicant_requirement_status ars WHERE ars.applicant_id = a.id AND ars.status = 'Verified'),
    20,
    CASE a.status
        WHEN 'Registered' THEN 'Paid'
        WHEN 'Approved' THEN 'Partial'
        ELSE 'Unpaid'
    END,
    CASE
        WHEN a.program_id IN (1, 3) THEN 1500000.00
        ELSE 1200000.00
    END,
    CASE a.status
        WHEN 'Registered' THEN 1500000.00
        WHEN 'Approved' THEN 750000.00
        ELSE 0.00
    END,
    (SELECT COUNT(*) FROM student_documents sd WHERE sd.applicant_id = a.id)
FROM applicants a;

-- ============================================================
-- 12. SEED STUDENT PROFILE COMMENTS
-- ============================================================

INSERT IGNORE INTO student_profile_comments (student_number, commenter_id, commenter_name, comment, is_internal, created_at) VALUES
('APP-2024-001', 1, 'Admin User', 'All documents verified. Student is fully registered.', 0, '2024-01-16 10:30:00'),
('APP-2024-002', 1, 'Admin User', 'All documents verified. Student is fully registered.', 0, '2024-01-16 11:00:00'),
('APP-2024-006', 2, 'Admissions Officer', 'Application approved. Waiting for final registration.', 0, '2024-05-10 09:15:00'),
('APP-2024-006', 2, 'Admissions Officer', 'Guardian consent form still pending.', 1, '2024-05-12 14:20:00'),
('APP-2024-008', 3, 'Review Committee', 'Medical report needs clarification. Follow up with applicant.', 0, '2024-05-15 11:45:00'),
('APP-2024-008', 3, 'Review Committee', 'Applicant contacted. Will resubmit medical report by Friday.', 1, '2024-05-16 08:30:00'),
('APP-2024-013', 1, 'Admin User', 'Rejected: A-Level certificate could not be verified.', 0, '2024-08-20 16:00:00'),
('APP-2024-015', 2, 'Admissions Officer', 'Approved. Awaiting payment confirmation.', 0, '2024-05-18 10:00:00'),
('APP-2024-020', 2, 'Admissions Officer', 'Approved. All required documents submitted.', 0, '2024-05-20 13:30:00'),
('APP-2024-003', 1, 'Admin User', 'Registration complete. Welcome letter sent.', 0, '2024-01-17 09:00:00');

-- ============================================================
-- 13. SEED STUDENT AUDIT LOG
-- ============================================================

INSERT IGNORE INTO student_audit_log (user_id, action, module, record_id, student_number, description, ip_address, created_at) VALUES
(1, 'Login', 'Authentication', 0, '', 'Admin user logged in successfully', '192.168.1.100', '2024-01-15 08:00:00'),
(1, 'View Applicant List', 'Admissions', 0, '', 'Viewed all applicants', '192.168.1.100', '2024-01-15 08:05:00'),
(1, 'Verify Document', 'Admissions', 1, 'APP-2024-001', 'Verified O-Level Certificate for Grace Nakato', '192.168.1.100', '2024-01-15 09:30:00'),
(1, 'Verify Document', 'Admissions', 2, 'APP-2024-001', 'Verified Birth Certificate for Grace Nakato', '192.168.1.100', '2024-01-15 09:45:00'),
(1, 'Approve Applicant', 'Admissions', 6, 'APP-2024-006', 'Approved applicant Aisha Nansubuga', '192.168.1.100', '2024-05-10 09:00:00'),
(2, 'Review Application', 'Admissions', 8, 'APP-2024-008', 'Started review for Betty Namukasa', '192.168.1.101', '2024-05-14 10:00:00'),
(2, 'Reject Application', 'Admissions', 13, 'APP-2024-013', 'Rejected Isaac Tumwine - unverifiable documents', '192.168.1.101', '2024-08-20 15:30:00'),
(1, 'Update Admission Requirements', 'Admissions', 0, '', 'Updated admission requirements list - removed supplies, added proper docs', '192.168.1.100', '2024-01-14 14:00:00'),
(2, 'Add Comment', 'Student Profile', 0, 'APP-2024-008', 'Added follow-up comment for medical report', '192.168.1.101', '2024-05-16 08:30:00'),
(1, 'Export Report', 'Reports', 0, '', 'Exported applicant status report for January intake', '192.168.1.100', '2024-02-01 11:00:00'),
(1, 'Login', 'Authentication', 0, '', 'Admin user logged in successfully', '192.168.1.100', '2024-05-01 08:00:00'),
(1, 'Approve Applicant', 'Admissions', 7, 'APP-2024-007', 'Approved applicant Robert Ochieng', '192.168.1.100', '2024-05-08 14:00:00'),
(3, 'Review Application', 'Admissions', 9, 'APP-2024-009', 'Started review for Moses Byaruhanga', '192.168.1.102', '2024-05-15 09:00:00'),
(3, 'Add Comment', 'Student Profile', 0, 'APP-2024-008', 'Requested clarification on medical report', '192.168.1.102', '2024-05-15 11:45:00'),
(1, 'Login', 'Authentication', 0, '', 'Admin user logged in successfully', '192.168.1.100', '2024-08-01 08:00:00'),
(2, 'View Applicant List', 'Admissions', 0, '', 'Viewed August intake applicants', '192.168.1.101', '2024-08-01 08:10:00'),
(1, 'Update Fee Structure', 'Finance', 0, '', 'Updated tuition fees for 2024 academic year', '192.168.1.100', '2024-01-10 10:00:00'),
(1, 'Generate Invoice', 'Finance', 1, 'APP-2024-001', 'Generated registration invoice for Grace Nakato', '192.168.1.100', '2024-01-15 12:00:00'),
(2, 'Approve Applicant', 'Admissions', 15, 'APP-2024-015', 'Approved applicant Daniel Kizza', '192.168.1.101', '2024-05-18 10:00:00'),
(2, 'Approve Applicant', 'Admissions', 20, 'APP-2024-020', 'Approved applicant Gladys Nabirye', '192.168.1.101', '2024-05-20 13:00:00');

-- ============================================================
-- 14. SEED STUDENT STATUS HISTORY
-- ============================================================

INSERT IGNORE INTO student_status_history (student_number, old_status, new_status, changed_by, changed_by_name, reason, created_at) VALUES
('APP-2024-001', '', 'New Applicant', 1, 'System', 'Application submitted online', '2024-01-10 08:00:00'),
('APP-2024-001', 'New Applicant', 'Under Review', 1, 'Admin User', 'Application assigned for review', '2024-01-12 09:00:00'),
('APP-2024-001', 'Under Review', 'Approved', 1, 'Admin User', 'All documents verified and approved', '2024-01-14 10:00:00'),
('APP-2024-001', 'Approved', 'Registered', 1, 'Admin User', 'Student completed registration and fee payment', '2024-01-15 14:00:00'),
('APP-2024-002', '', 'New Applicant', 1, 'System', 'Application submitted online', '2024-01-11 08:30:00'),
('APP-2024-002', 'New Applicant', 'Under Review', 1, 'Admin User', 'Application assigned for review', '2024-01-12 11:00:00'),
('APP-2024-002', 'Under Review', 'Approved', 1, 'Admin User', 'All documents verified', '2024-01-13 15:00:00'),
('APP-2024-002', 'Approved', 'Registered', 1, 'Admin User', 'Registration complete with full payment', '2024-01-15 15:00:00'),
('APP-2024-006', '', 'New Applicant', 1, 'System', 'Application submitted for May intake', '2024-04-20 08:00:00'),
('APP-2024-006', 'New Applicant', 'Under Review', 2, 'Admissions Officer', 'Assigned for May intake review', '2024-05-01 09:00:00'),
('APP-2024-006', 'Under Review', 'Approved', 2, 'Admissions Officer', 'Documents verified, awaiting final registration', '2024-05-10 09:15:00'),
('APP-2024-008', '', 'New Applicant', 1, 'System', 'Application submitted for May intake', '2024-04-22 10:00:00'),
('APP-2024-008', 'New Applicant', 'Under Review', 3, 'Review Committee', 'Assigned for detailed review', '2024-05-14 10:00:00'),
('APP-2024-013', '', 'New Applicant', 1, 'System', 'Application submitted for August intake', '2024-07-25 08:00:00'),
('APP-2024-013', 'New Applicant', 'Under Review', 1, 'Admin User', 'Application assigned for review', '2024-08-01 09:00:00'),
('APP-2024-013', 'Under Review', 'Rejected', 1, 'Admin User', 'A-Level certificate could not be verified with issuing institution', '2024-08-20 15:30:00'),
('APP-2024-015', '', 'New Applicant', 1, 'System', 'Application submitted for May intake', '2024-04-25 08:00:00'),
('APP-2024-015', 'New Applicant', 'Under Review', 2, 'Admissions Officer', 'Assigned for review', '2024-05-05 09:00:00'),
('APP-2024-015', 'Under Review', 'Approved', 2, 'Admissions Officer', 'All documents verified and approved', '2024-05-18 10:00:00'),
('APP-2024-020', '', 'New Applicant', 1, 'System', 'Application submitted for May intake', '2024-04-28 08:00:00'),
('APP-2024-020', 'New Applicant', 'Under Review', 2, 'Admissions Officer', 'Application assigned for review', '2024-05-08 09:00:00'),
('APP-2024-020', 'Under Review', 'Approved', 2, 'Admissions Officer', 'All required documents submitted and verified', '2024-05-20 13:30:00');

-- ============================================================
-- DONE
-- ============================================================
