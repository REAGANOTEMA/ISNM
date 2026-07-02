-- ═══════════════════════════════════════════════════════════════
-- ISNM STUDENT MANAGEMENT SYSTEM — MISSING TABLES
-- Run: C:\xampp\mysql\bin\mysql.exe -u root -P 3307 igangaschoolofl_students_db < STUDENT_MANAGEMENT_MISSING.sql
-- ═══════════════════════════════════════════════════════════════

-- ── 1. ACADEMIC CALENDAR ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS academic_calendar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    registration_deadline DATE,
    exam_start_date DATE,
    exam_end_date DATE,
    results_release_date DATE,
    is_current TINYINT(1) DEFAULT 0,
    status ENUM('Active','Inactive','Completed') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_cal_semester (academic_year, semester)
) ENGINE=InnoDB;

-- ── 2. COURSE ASSIGNMENTS (Lecturer → Course) ─────────────────
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

-- ── 3. CLASS SESSIONS (for attendance tracking) ────────────────
CREATE TABLE IF NOT EXISTS class_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    lecturer_id INT NOT NULL,
    session_date DATE NOT NULL,
    start_time TIME,
    end_time TIME,
    room VARCHAR(50),
    session_type ENUM('Lecture','Tutorial','Practical','Clinical','Exam') DEFAULT 'Lecture',
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    total_students INT DEFAULT 0,
    present_count INT DEFAULT 0,
    status ENUM('Scheduled','In Progress','Completed','Cancelled') DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_course_date (course_id, session_date),
    KEY idx_lecturer (lecturer_id)
) ENGINE=InnoDB;

-- ── 4. STUDENT ATTENDANCE RECORDS (detailed) ───────────────────
CREATE TABLE IF NOT EXISTS attendance_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    student_id INT NOT NULL,
    status ENUM('Present','Absent','Late','Excused') NOT NULL DEFAULT 'Absent',
    time_in TIME,
    time_out TIME,
    marked_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_session_student (session_id, student_id),
    KEY idx_student (student_id),
    KEY idx_session (session_id)
) ENGINE=InnoDB;

-- ── 5. STUDENT LOGBOOK (Clinical Placement) ───────────────────
CREATE TABLE IF NOT EXISTS student_logbook (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    placement_id INT,
    entry_date DATE NOT NULL,
    ward_unit VARCHAR(100),
    shift ENUM('Morning','Afternoon','Night','Full Day') DEFAULT 'Morning',
    procedures_performed TEXT,
    patients_seen INT DEFAULT 0,
    skills_demonstrated TEXT,
    supervisor_name VARCHAR(150),
    supervisor_signature VARCHAR(100),
    verified TINYINT(1) DEFAULT 0,
    verified_by INT,
    verified_at DATETIME,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_student (student_id),
    KEY idx_placement (placement_id),
    KEY idx_date (entry_date)
) ENGINE=InnoDB;

-- ── 6. STUDENT COMPETENCY TRACKING ────────────────────────────
CREATE TABLE IF NOT EXISTS student_competencies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    placement_id INT,
    skill_name VARCHAR(200) NOT NULL,
    skill_category VARCHAR(100),
    competency_level ENUM('Beginner','Intermediate','Advanced','Proficient','Not Assessed') DEFAULT 'Not Assessed',
    score DECIMAL(5,2),
    max_score DECIMAL(5,2) DEFAULT 100,
    assessed_by INT,
    assessment_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_student (student_id),
    KEY idx_placement (placement_id),
    KEY idx_skill (skill_name)
) ENGINE=InnoDB;

-- ── 7. STUDENT WARNINGS ───────────────────────────────────────
CREATE TABLE IF NOT EXISTS student_warnings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    warning_type ENUM('Academic','Discipline','Attendance','Clinical','Financial','Other') NOT NULL,
    severity ENUM('Verbal','Written','Final','Suspension','Expulsion') NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    issued_by INT NOT NULL,
    issued_by_name VARCHAR(200),
    warning_date DATE NOT NULL,
    valid_until DATE,
    acknowledged TINYINT(1) DEFAULT 0,
    acknowledged_at DATETIME,
    status ENUM('Active','Expired','Appealed','Revoked') DEFAULT 'Active',
    appeal_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_student (student_id),
    KEY idx_type (warning_type),
    KEY idx_status (status)
) ENGINE=InnoDB;

-- ── 8. RESULT APPROVALS (Lecturer → HOD → Registrar → Principal)
CREATE TABLE IF NOT EXISTS result_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    submitted_by INT NOT NULL,
    submitted_by_name VARCHAR(200),
    submitted_by_role VARCHAR(50),
    submitted_at DATETIME NOT NULL,
    hod_reviewed_by INT,
    hod_reviewed_at DATETIME,
    hod_status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
    hod_remarks TEXT,
    registrar_reviewed_by INT,
    registrar_reviewed_at DATETIME,
    registrar_status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
    registrar_remarks TEXT,
    principal_reviewed_by INT,
    principal_reviewed_at DATETIME,
    principal_status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
    principal_remarks TEXT,
    final_status ENUM('Draft','Submitted','HOD Approved','Registrar Approved','Published','Rejected') DEFAULT 'Draft',
    published_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_course_semester (course_id, academic_year, semester),
    KEY idx_status (final_status)
) ENGINE=InnoDB;

-- ── 9. TRANSCRIPT RECORDS ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS student_transcripts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    transcript_number VARCHAR(50) NOT NULL,
    request_type ENUM('Official','Unofficial','Certified','Digital') DEFAULT 'Official',
    purpose TEXT,
    academic_year VARCHAR(20),
    semester VARCHAR(20),
    total_credits INT DEFAULT 0,
    cumulative_gpa DECIMAL(4,2),
    class_of_award VARCHAR(100),
    file_path VARCHAR(500),
    status ENUM('Requested','Processing','Ready','Issued','Collected') DEFAULT 'Requested',
    requested_by INT,
    processed_by INT,
    issued_by INT,
    issued_at DATETIME,
    collected_at DATETIME,
    fee_amount DECIMAL(10,2) DEFAULT 0,
    fee_paid TINYINT(1) DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_student (student_id),
    KEY idx_status (status)
) ENGINE=InnoDB;

-- ── 10. SEMESTER GPA TRACKING ─────────────────────────────────
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

-- ── 11. STUDENT CLINICAL EVALUATIONS ──────────────────────────
CREATE TABLE IF NOT EXISTS clinical_evaluations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    placement_id INT,
    evaluator_name VARCHAR(200),
    evaluator_title VARCHAR(100),
    evaluation_date DATE,
    professional_conduct DECIMAL(4,1),
    clinical_skills DECIMAL(4,1),
    communication DECIMAL(4,1),
    teamwork DECIMAL(4,1),
    initiative DECIMAL(4,1),
    overall_rating DECIMAL(4,1),
    strengths TEXT,
    areas_for_improvement TEXT,
    recommendations TEXT,
    status ENUM('Draft','Submitted','Final') DEFAULT 'Draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_student (student_id),
    KEY idx_placement (placement_id)
) ENGINE=InnoDB;

-- ── 12. STUDENT PROGRAM ENROLLMENT ────────────────────────────
CREATE TABLE IF NOT EXISTS student_program_enrollment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    program_id INT NOT NULL,
    enrollment_date DATE NOT NULL,
    expected_graduation DATE,
    actual_graduation DATE,
    enrollment_status ENUM('Enrolled','Deferred','Transferred','Withdrawn','Graduated','Completed') DEFAULT 'Enrolled',
    academic_year VARCHAR(20),
    intake_period VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_student (student_id),
    KEY idx_program (program_id),
    KEY idx_status (enrollment_status)
) ENGINE=InnoDB;

-- ── 13. FEE PAYMENTS (detailed) ───────────────────────────────
CREATE TABLE IF NOT EXISTS fee_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_reference VARCHAR(50) NOT NULL,
    student_id INT NOT NULL,
    invoice_id INT,
    amount DECIMAL(12,2) NOT NULL,
    payment_method ENUM('Cash','Bank Transfer','Mobile Money','Cheque','Card','Other') NOT NULL,
    payment_date DATE NOT NULL,
    transaction_ref VARCHAR(100),
    received_by INT,
    status ENUM('Pending','Completed','Failed','Reversed') DEFAULT 'Completed',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_student (student_id),
    KEY idx_reference (payment_reference)
) ENGINE=InnoDB;

-- ── 14. HOSTEL ROOMS (if not exists) ──────────────────────────
CREATE TABLE IF NOT EXISTS hostel_blocks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    block_name VARCHAR(100) NOT NULL,
    total_rooms INT DEFAULT 0,
    gender ENUM('Male','Female','Mixed') DEFAULT 'Mixed',
    status ENUM('Active','Inactive','Maintenance') DEFAULT 'Active',
    warden_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ═══════════════════════════════════════════════════════════════
-- SEED DATA
-- ═══════════════════════════════════════════════════════════════

-- Academic Calendar
INSERT IGNORE INTO academic_calendar (academic_year, semester, start_date, end_date, registration_deadline, exam_start_date, exam_end_date, results_release_date, is_current, status) VALUES
('2026', 'Semester 1', '2026-02-01', '2026-06-30', '2026-03-15', '2026-06-01', '2026-06-20', '2026-07-15', 1, 'Active'),
('2026', 'Semester 2', '2026-08-01', '2026-12-15', '2026-08-30', '2026-12-01', '2026-12-12', '2027-01-15', 0, 'Inactive'),
('2025', 'Semester 1', '2025-02-01', '2025-06-30', '2025-03-15', '2025-06-01', '2025-06-20', '2025-07-15', 0, 'Completed'),
('2025', 'Semester 2', '2025-08-01', '2025-12-15', '2025-08-30', '2025-12-01', '2025-12-12', '2026-01-15', 0, 'Completed');

-- Warning Types (seed a few)
INSERT IGNORE INTO student_warnings (student_id, warning_type, severity, title, description, issued_by, issued_by_name, warning_date, status) VALUES
(1, 'Attendance', 'Written', 'Poor Clinical Attendance', 'Student has missed 3 consecutive clinical sessions without prior notification.', 101, 'Dr. Nakamya Sarah', '2026-05-15', 'Active'),
(2, 'Academic', 'Verbal', 'Below Average Performance', 'Student scored below 50% in Fundamentals of Nursing exam.', 102, 'Prof. Mugisha John', '2026-04-20', 'Expired');

-- Sample Course Assignments
INSERT IGNORE INTO course_assignments (course_id, lecturer_id, academic_year, semester, assigned_by, status) VALUES
(1, 101, '2026', 'Semester 1', 1, 'Active'),
(2, 102, '2026', 'Semester 1', 1, 'Active'),
(3, 103, '2026', 'Semester 1', 1, 'Active');

-- Sample Result Approvals
INSERT IGNORE INTO result_approvals (course_id, academic_year, semester, submitted_by, submitted_by_name, submitted_by_role, submitted_at, final_status) VALUES
(1, '2026', 'Semester 1', 101, 'Dr. Nakamya Sarah', 'Lecturer', '2026-06-25 10:00:00', 'Published'),
(2, '2026', 'Semester 1', 102, 'Prof. Mugisha John', 'Lecturer', '2026-06-25 11:00:00', 'HOD Approved');

-- Sample Logbook Entries
INSERT IGNORE INTO student_logbook (student_id, placement_id, entry_date, ward_unit, shift, procedures_performed, patients_seen, skills_demonstrated, supervisor_name, verified) VALUES
(1, 1, '2026-05-01', 'Maternity Ward', 'Morning', 'Antenatal care, vital signs monitoring', 8, 'BP measurement, fundal height', 'Sr. Nabirye Florence', 1),
(1, 1, '2026-05-02', 'Pediatric Ward', 'Morning', 'Child immunization, growth monitoring', 12, 'IM injection, growth chart plotting', 'Sr. Nabirye Florence', 1),
(2, 1, '2026-05-01', 'Surgical Ward', 'Afternoon', 'Wound dressing, catheter care', 6, 'Aseptic technique, catheterization', 'Mr. Ochieng David', 1);

-- Sample Competency Records
INSERT IGNORE INTO student_competencies (student_id, placement_id, skill_name, skill_category, competency_level, score, assessed_by, assessment_date) VALUES
(1, 1, 'Vital Signs Assessment', 'Clinical Skills', 'Proficient', 92, 101, '2026-05-15'),
(1, 1, 'Patient History Taking', 'Communication', 'Advanced', 88, 101, '2026-05-15'),
(1, 1, 'IV Cannulation', 'Procedures', 'Intermediate', 75, 102, '2026-05-20'),
(2, 1, 'Wound Management', 'Clinical Skills', 'Advanced', 85, 102, '2026-05-15'),
(2, 1, 'Drug Administration', 'Pharmacology', 'Proficient', 90, 101, '2026-05-20');

-- Sample Clinical Evaluations
INSERT IGNORE INTO clinical_evaluations (student_id, placement_id, evaluator_name, evaluator_title, evaluation_date, professional_conduct, clinical_skills, communication, teamwork, initiative, overall_rating, strengths, areas_for_improvement, status) VALUES
(1, 1, 'Sr. Nabirye Florence', 'Clinical Instructor', '2026-05-30', 9.0, 8.5, 9.0, 8.0, 8.5, 8.6, 'Excellent patient interaction, punctual, eager to learn', 'Needs more confidence in emergency situations', 'Final'),
(2, 1, 'Mr. Ochieng David', 'Ward Manager', '2026-05-30', 8.5, 8.0, 7.5, 8.5, 7.0, 7.9, 'Good teamwork, follows protocols', 'Communication with patients needs improvement', 'Final');

SELECT 'STUDENT MANAGEMENT MISSING TABLES COMPLETE' as Status,
       (SELECT COUNT(*) FROM academic_calendar) as AcademicCalendar,
       (SELECT COUNT(*) FROM course_assignments) as CourseAssignments,
       (SELECT COUNT(*) FROM student_logbook) as LogbookEntries,
       (SELECT COUNT(*) FROM student_competencies) as Competencies,
       (SELECT COUNT(*) FROM student_warnings) as Warnings,
       (SELECT COUNT(*) FROM result_approvals) as ResultApprovals,
       (SELECT COUNT(*) FROM clinical_evaluations) as ClinicalEvaluations;
