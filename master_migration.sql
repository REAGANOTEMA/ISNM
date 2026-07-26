-- ================================================================
-- ISNM Master SQL Migration Script
-- Generated: 2026-07-26
-- Description: All CREATE TABLE IF NOT EXISTS statements for every
--   table referenced across all ~90+ dashboard PHP files.
-- Databases: igangaschool_staffs, igangaschool_students, igangaschool_website
-- ================================================================

-- ================================================================
-- SECTION 1: igangaschool_staffs (Staff Database)
-- ================================================================

-- ── Academic Calendar ──
CREATE TABLE IF NOT EXISTS academic_calendar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(100) DEFAULT NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    exam_start_date DATE NULL,
    exam_end_date DATE NULL,
    is_current TINYINT(1) DEFAULT 0,
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_year_sem (academic_year, semester)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Semesters ──
CREATE TABLE IF NOT EXISTS semesters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    academic_year VARCHAR(20) NOT NULL,
    semester_name VARCHAR(100) NOT NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    is_current TINYINT(1) DEFAULT 0,
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Academic Programs ──
CREATE TABLE IF NOT EXISTS academic_programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_code VARCHAR(50) NOT NULL UNIQUE,
    program_name VARCHAR(300) NOT NULL,
    program_type VARCHAR(100) DEFAULT '',
    department VARCHAR(200) DEFAULT '',
    duration_years INT DEFAULT 3,
    total_fee DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Academic Course Catalog ──
CREATE TABLE IF NOT EXISTS academic_course_catalog (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(50) NOT NULL,
    course_title VARCHAR(300) NOT NULL,
    credits DECIMAL(5,2) DEFAULT 0.00,
    program_code VARCHAR(50) DEFAULT '',
    year_of_study INT DEFAULT 1,
    semester VARCHAR(100) DEFAULT '',
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_program (program_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Academic Intakes ──
CREATE TABLE IF NOT EXISTS intakes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    intake_name VARCHAR(200) NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    intake_month VARCHAR(20) DEFAULT NULL,
    intake_year YEAR DEFAULT NULL,
    start_date DATE NULL,
    end_date DATE NULL,
    application_start DATE DEFAULT NULL,
    application_deadline DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_intake(intake_month, intake_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Academic Curriculum Development ──
CREATE TABLE IF NOT EXISTS academic_curriculum_development (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_code VARCHAR(50) NOT NULL,
    revision_number INT DEFAULT 1,
    academic_year VARCHAR(20) DEFAULT NULL,
    description TEXT,
    status VARCHAR(50) DEFAULT 'Draft',
    created_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_program (program_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Curriculum Development (simplified) ──
CREATE TABLE IF NOT EXISTS curriculum_development (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_name VARCHAR(100) NOT NULL,
    course_code VARCHAR(50) NOT NULL,
    course_title VARCHAR(150) NOT NULL,
    credit_units INT DEFAULT 0,
    status VARCHAR(50) DEFAULT 'Draft',
    developed_by VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Grade Scales ──
CREATE TABLE IF NOT EXISTS grade_scales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grade_letter VARCHAR(5) NOT NULL,
    grade_point DECIMAL(4,2) DEFAULT 0.00,
    min_percentage DECIMAL(5,2) DEFAULT 0.00,
    max_percentage DECIMAL(5,2) DEFAULT 100.00,
    remark VARCHAR(200) DEFAULT '',
    created_by INT DEFAULT 0,
    status VARCHAR(50) DEFAULT 'Active',
    UNIQUE KEY uq_grade (grade_letter)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── GPA Settings ──
CREATE TABLE IF NOT EXISTS gpa_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description VARCHAR(500) DEFAULT NULL,
    updated_by INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Examination Records ──
CREATE TABLE IF NOT EXISTS examination_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_code VARCHAR(50) NOT NULL,
    exam_type VARCHAR(50) DEFAULT 'Final',
    marks_obtained DECIMAL(8,2) DEFAULT 0.00,
    total_marks DECIMAL(8,2) DEFAULT 100.00,
    grade VARCHAR(5) DEFAULT '',
    continuous_assessment_marks DECIMAL(8,2) DEFAULT 0.00,
    final_exam_marks DECIMAL(8,2) DEFAULT 0.00,
    grade_status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_student_course_exam (student_id, course_code, exam_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Grading Approval Workflow ──
CREATE TABLE IF NOT EXISTS grading_approval_workflow (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workflow_number INT NOT NULL UNIQUE,
    examination_record_id INT DEFAULT 0,
    hod_status VARCHAR(50) DEFAULT 'Pending',
    registrar_status VARCHAR(50) DEFAULT 'Pending',
    principal_status VARCHAR(50) DEFAULT 'Pending',
    hod_approved_by INT DEFAULT 0,
    registrar_approved_by INT DEFAULT 0,
    principal_approved_by INT DEFAULT 0,
    current_stage VARCHAR(100) DEFAULT 'HOD',
    published_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_record (examination_record_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Academic Records (director-academics) ──
CREATE TABLE IF NOT EXISTS academic_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_code VARCHAR(50) DEFAULT '',
    course_name VARCHAR(300) DEFAULT '',
    assessment_type VARCHAR(50) DEFAULT 'Exam',
    marks DECIMAL(8,2) DEFAULT 0.00,
    marks_obtained DECIMAL(8,2) DEFAULT 0.00,
    grade VARCHAR(5) DEFAULT '',
    gpa DECIMAL(4,2) DEFAULT 0.00,
    graded_by INT DEFAULT 0,
    lecturer_id INT DEFAULT 0,
    staff_id INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Grade Appeals ──
CREATE TABLE IF NOT EXISTS grade_appeals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    student_name VARCHAR(255) DEFAULT '',
    course_name VARCHAR(255) DEFAULT '',
    lecturer_id INT DEFAULT 0,
    reason TEXT,
    notes TEXT,
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Result Publications ──
CREATE TABLE IF NOT EXISTS result_publications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(100) NOT NULL,
    published_by INT DEFAULT 0,
    published_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50) DEFAULT 'Published',
    UNIQUE KEY uq_pub (academic_year, semester)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Result Approvals ──
CREATE TABLE IF NOT EXISTS result_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_number VARCHAR(50) DEFAULT '',
    status VARCHAR(50) DEFAULT 'Pending',
    comments TEXT,
    approved_by INT DEFAULT 0,
    approval_date DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Transcripts ──
CREATE TABLE IF NOT EXISTS transcripts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    transcript_number VARCHAR(50) UNIQUE,
    template_id INT DEFAULT 0,
    academic_year VARCHAR(20) DEFAULT NULL,
    semester VARCHAR(100) DEFAULT NULL,
    total_credit_hours DECIMAL(10,2) DEFAULT 0.00,
    cumulative_gpa DECIMAL(4,2) DEFAULT 0.00,
    status VARCHAR(50) DEFAULT 'Draft',
    generated_by INT DEFAULT 0,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_archived TINYINT(1) DEFAULT 0,
    is_downloadable TINYINT(1) DEFAULT 0,
    KEY idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Transcript Items ──
CREATE TABLE IF NOT EXISTS transcript_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transcript_id INT NOT NULL,
    course_code VARCHAR(50) NOT NULL,
    course_title VARCHAR(300) DEFAULT '',
    credit_hours DECIMAL(5,2) DEFAULT 0.00,
    marks_obtained DECIMAL(8,2) DEFAULT 0.00,
    grade VARCHAR(5) DEFAULT '',
    grade_point DECIMAL(4,2) DEFAULT 0.00,
    academic_year VARCHAR(20) DEFAULT NULL,
    semester VARCHAR(100) DEFAULT NULL,
    KEY idx_transcript (transcript_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Transcript Templates ──
CREATE TABLE IF NOT EXISTS transcript_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(200) NOT NULL,
    template_html TEXT,
    orientation VARCHAR(20) DEFAULT 'portrait',
    is_default TINYINT(1) DEFAULT 0,
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Registrar Transcripts ──
CREATE TABLE IF NOT EXISTS registrar_transcripts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transcript_number VARCHAR(50) UNIQUE,
    student_id INT NOT NULL,
    academic_year VARCHAR(20) DEFAULT NULL,
    program VARCHAR(300) DEFAULT '',
    transcript_status VARCHAR(50) DEFAULT 'Draft',
    request_date DATETIME DEFAULT NULL,
    generated_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_archived TINYINT(1) DEFAULT 0,
    is_downloadable TINYINT(1) DEFAULT 0,
    KEY idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Certificates ──
CREATE TABLE IF NOT EXISTS certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    certificate_number VARCHAR(50) UNIQUE,
    template_id INT DEFAULT 0,
    certificate_type VARCHAR(100) DEFAULT 'Certificate',
    program_name VARCHAR(300) DEFAULT NULL,
    completion_date DATE NULL,
    issue_date DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Draft',
    generated_by INT DEFAULT 0,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_archived TINYINT(1) DEFAULT 0,
    is_downloadable TINYINT(1) DEFAULT 0,
    KEY idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Certificate Templates ──
CREATE TABLE IF NOT EXISTS certificate_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(200) NOT NULL,
    template_html TEXT,
    orientation VARCHAR(20) DEFAULT 'landscape',
    is_default TINYINT(1) DEFAULT 0,
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Certificate Uploads ──
CREATE TABLE IF NOT EXISTS certificate_uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    certificate_id INT NOT NULL,
    file_name VARCHAR(300) DEFAULT '',
    file_path VARCHAR(500) DEFAULT '',
    file_size INT DEFAULT 0,
    mime_type VARCHAR(100) DEFAULT '',
    uploaded_by INT DEFAULT 0,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_certificate (certificate_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Certificate Verification ──
CREATE TABLE IF NOT EXISTS certificate_verification (
    id INT AUTO_INCREMENT PRIMARY KEY,
    certificate_number VARCHAR(50) NOT NULL,
    verified_by VARCHAR(200) DEFAULT NULL,
    verification_reference VARCHAR(100) DEFAULT NULL,
    verification_status VARCHAR(50) DEFAULT 'Verified',
    verified_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_cert_number (certificate_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Registrar Certificates ──
CREATE TABLE IF NOT EXISTS registrar_certificates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    certificate_number VARCHAR(50) UNIQUE,
    student_id INT NOT NULL,
    full_name VARCHAR(300) DEFAULT '',
    program VARCHAR(300) DEFAULT '',
    certificate_type VARCHAR(100) DEFAULT 'Certificate',
    status VARCHAR(50) DEFAULT 'Draft',
    generated_by INT DEFAULT 0,
    generated_date DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_archived TINYINT(1) DEFAULT 0,
    is_downloadable TINYINT(1) DEFAULT 0,
    KEY idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Generated Documents ──
CREATE TABLE IF NOT EXISTS generated_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_type VARCHAR(50) NOT NULL,
    student_id INT NOT NULL,
    document_title VARCHAR(300) DEFAULT '',
    document_content LONGTEXT,
    document_number VARCHAR(100) DEFAULT NULL,
    generated_by INT DEFAULT 0,
    generation_date DATETIME DEFAULT NULL,
    file_path VARCHAR(500) DEFAULT '',
    status VARCHAR(50) DEFAULT 'Active',
    is_archived TINYINT(1) DEFAULT 0,
    is_downloadable TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_student (student_id),
    KEY idx_type (document_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Course Registrations ──
CREATE TABLE IF NOT EXISTS course_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    student_number VARCHAR(50) DEFAULT '',
    course_code VARCHAR(50) NOT NULL,
    course_name VARCHAR(300) DEFAULT '',
    academic_year VARCHAR(20) DEFAULT NULL,
    semester VARCHAR(100) DEFAULT NULL,
    registration_date DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Registered',
    grade VARCHAR(5) DEFAULT '',
    marks DECIMAL(8,2) DEFAULT NULL,
    registered_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_student (student_id),
    KEY idx_course (course_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Course Assignments ──
CREATE TABLE IF NOT EXISTS course_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lecturer_id INT NOT NULL,
    course_code VARCHAR(50) NOT NULL,
    course_name VARCHAR(300) DEFAULT '',
    semester VARCHAR(100) DEFAULT '',
    academic_year VARCHAR(20) DEFAULT '',
    classroom VARCHAR(100) DEFAULT '',
    assigned_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_lecturer (lecturer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Academic Timetable ──
CREATE TABLE IF NOT EXISTS academic_timetable (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timetable_id VARCHAR(50) DEFAULT '',
    academic_year VARCHAR(20) DEFAULT '',
    semester VARCHAR(100) DEFAULT '',
    program_code VARCHAR(50) DEFAULT '',
    course_code VARCHAR(50) DEFAULT '',
    day_of_week VARCHAR(20) NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    venue VARCHAR(200) DEFAULT '',
    lecturer_id INT DEFAULT 0,
    timetable_status VARCHAR(50) DEFAULT 'Active',
    created_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_day (day_of_week)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Timetable (director-academics variant) ──
CREATE TABLE IF NOT EXISTS timetable (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lecturer_id INT DEFAULT 0,
    course_code VARCHAR(50) DEFAULT '',
    day_of_week VARCHAR(20) DEFAULT '',
    start_time TIME DEFAULT NULL,
    end_time TIME DEFAULT NULL,
    room VARCHAR(200) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Graduation Candidates ──
CREATE TABLE IF NOT EXISTS graduation_candidates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT DEFAULT 0,
    student_name VARCHAR(300) DEFAULT '',
    full_name VARCHAR(300) DEFAULT '',
    program VARCHAR(200) DEFAULT '',
    program_name VARCHAR(200) DEFAULT '',
    program_id INT DEFAULT 0,
    index_number VARCHAR(50) DEFAULT '',
    student_id_col VARCHAR(50) DEFAULT '',
    award VARCHAR(200) DEFAULT '',
    award_title VARCHAR(200) DEFAULT '',
    academic_year VARCHAR(20) DEFAULT NULL,
    graduation_year VARCHAR(20) DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    remarks TEXT,
    notes TEXT,
    submitted_by INT DEFAULT 0,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_student_program (student_id, program_id),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Graduation Approvals ──
CREATE TABLE IF NOT EXISTS graduation_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    candidate_id INT NOT NULL,
    approved_by INT DEFAULT 0,
    approval_level VARCHAR(100) DEFAULT 'Registrar',
    status VARCHAR(50) DEFAULT 'Pending',
    remarks TEXT,
    approved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_candidate (candidate_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Student Progression ──
CREATE TABLE IF NOT EXISTS student_progression (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    from_year INT DEFAULT 0,
    to_year INT DEFAULT 0,
    from_semester VARCHAR(100) DEFAULT NULL,
    to_semester VARCHAR(100) DEFAULT NULL,
    academic_year VARCHAR(20) DEFAULT NULL,
    progression_type VARCHAR(50) DEFAULT 'Promotion',
    approved_by INT DEFAULT 0,
    status VARCHAR(50) DEFAULT 'Approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Registrar Student Registration ──
CREATE TABLE IF NOT EXISTS registrar_student_registration (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(100) NOT NULL,
    registration_date DATE DEFAULT NULL,
    registration_status VARCHAR(50) DEFAULT 'Registered',
    registered_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_student (student_id),
    KEY idx_year (academic_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Registrar Settings ──
CREATE TABLE IF NOT EXISTS registrar_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description VARCHAR(500) DEFAULT NULL,
    updated_by INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── National Exam Results ──
CREATE TABLE IF NOT EXISTS national_exam_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_code VARCHAR(50) NOT NULL,
    exam_body VARCHAR(100) DEFAULT 'UNEB',
    marks_obtained DECIMAL(8,2) DEFAULT 0.00,
    grade VARCHAR(5) DEFAULT '',
    academic_year VARCHAR(20) DEFAULT NULL,
    semester VARCHAR(100) DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    entered_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Clinical Assessments ──
CREATE TABLE IF NOT EXISTS clinical_assessments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    clinical_placement_id INT DEFAULT 0,
    assessment_type VARCHAR(100) DEFAULT '',
    score DECIMAL(8,2) DEFAULT 0.00,
    max_score DECIMAL(8,2) DEFAULT 100.00,
    grade VARCHAR(5) DEFAULT '',
    assessed_by INT DEFAULT 0,
    assessment_date DATE DEFAULT NULL,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Clinical Placements ──
CREATE TABLE IF NOT EXISTS clinical_placements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    facility_name VARCHAR(300) NOT NULL,
    department VARCHAR(200) DEFAULT '',
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    supervisor_name VARCHAR(200) DEFAULT '',
    supervisor_phone VARCHAR(50) DEFAULT '',
    supervisor_notes TEXT DEFAULT NULL,
    competency_score DECIMAL(5,2) DEFAULT NULL,
    supervisor_evaluation TEXT DEFAULT NULL,
    logbook_submitted TINYINT(1) DEFAULT 0,
    status VARCHAR(50) DEFAULT 'Active',
    created_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Clinical Training (director-academics) ──
CREATE TABLE IF NOT EXISTS clinical_training (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT DEFAULT 0,
    supervisor_id INT DEFAULT 0,
    facility_name VARCHAR(300) DEFAULT '',
    department VARCHAR(200) DEFAULT '',
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Academic Approvals ──
CREATE TABLE IF NOT EXISTS academic_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(100) NOT NULL,
    entity_id INT NOT NULL,
    requested_by INT DEFAULT 0,
    approved_by INT DEFAULT 0,
    approval_level VARCHAR(100) DEFAULT 'Registrar',
    status VARCHAR(50) DEFAULT 'Pending',
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at DATETIME DEFAULT NULL,
    KEY idx_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Academic Audit Logs ──
CREATE TABLE IF NOT EXISTS academic_audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(200) NOT NULL,
    entity_type VARCHAR(100) DEFAULT '',
    entity_id INT DEFAULT 0,
    description TEXT,
    ip_address VARCHAR(50) DEFAULT NULL,
    user_agent VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_user (user_id),
    KEY idx_action (action),
    KEY idx_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Communications ──
CREATE TABLE IF NOT EXISTS communications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_type VARCHAR(50) DEFAULT 'student',
    recipient_id INT DEFAULT 0,
    subject VARCHAR(300) NOT NULL,
    message TEXT NOT NULL,
    channel VARCHAR(50) DEFAULT 'portal',
    sent_by INT DEFAULT 0,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Alumni ──
CREATE TABLE IF NOT EXISTS alumni (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) DEFAULT NULL,
    index_number VARCHAR(50) DEFAULT NULL,
    first_name VARCHAR(100) NOT NULL,
    surname VARCHAR(100) NOT NULL,
    other_name VARCHAR(100) DEFAULT NULL,
    full_name VARCHAR(300) DEFAULT NULL,
    email VARCHAR(200) DEFAULT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    gender ENUM('Male','Female','Other') DEFAULT 'Other',
    date_of_birth DATE DEFAULT NULL,
    nationality VARCHAR(100) DEFAULT 'Ugandan',
    address TEXT DEFAULT NULL,
    program VARCHAR(200) DEFAULT NULL,
    graduation_year YEAR DEFAULT NULL,
    graduation_class VARCHAR(50) DEFAULT NULL,
    current_employer VARCHAR(255) DEFAULT NULL,
    current_position VARCHAR(255) DEFAULT NULL,
    employment_status ENUM('employed','self-employed','unemployed','student','retired') DEFAULT 'employed',
    industry VARCHAR(200) DEFAULT NULL,
    location_city VARCHAR(100) DEFAULT NULL,
    location_country VARCHAR(100) DEFAULT 'Uganda',
    linkedin VARCHAR(500) DEFAULT NULL,
    bio TEXT DEFAULT NULL,
    skills TEXT DEFAULT NULL,
    interests TEXT DEFAULT NULL,
    membership_status ENUM('active','inactive','lifetime') DEFAULT 'active',
    profile_photo VARCHAR(500) DEFAULT NULL,
    emergency_contact VARCHAR(100) DEFAULT NULL,
    emergency_phone VARCHAR(50) DEFAULT NULL,
    newsletter_optin TINYINT(1) DEFAULT 1,
    notes TEXT DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY idx_alumni_email (email),
    KEY idx_alumni_graduation (graduation_year),
    KEY idx_alumni_status (membership_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Alumni Contributions ──
CREATE TABLE IF NOT EXISTS alumni_contributions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alumni_id INT NOT NULL,
    contribution_type ENUM('donation','sponsorship','volunteer','mentorship','other') DEFAULT 'donation',
    amount DECIMAL(12,2) DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'UGX',
    description TEXT DEFAULT NULL,
    contribution_date DATE DEFAULT (CURDATE()),
    payment_method VARCHAR(50) DEFAULT NULL,
    transaction_ref VARCHAR(100) DEFAULT NULL,
    acknowledged TINYINT(1) DEFAULT 0,
    acknowledged_by INT(11) DEFAULT NULL,
    acknowledged_at DATETIME DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY alumni_id (alumni_id),
    KEY idx_contrib_type (contribution_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Alumni Events ──
CREATE TABLE IF NOT EXISTS alumni_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alumni_id INT NOT NULL,
    event_name VARCHAR(255) NOT NULL,
    event_date DATE DEFAULT NULL,
    attended TINYINT(1) DEFAULT 0,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY alumni_id (alumni_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Alumni Jobs ──
CREATE TABLE IF NOT EXISTS alumni_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alumni_id INT NOT NULL,
    company VARCHAR(255) NOT NULL,
    position VARCHAR(255) NOT NULL,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    is_current TINYINT(1) DEFAULT 0,
    description TEXT DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY alumni_id (alumni_id),
    KEY idx_jobs_current (is_current)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Events ──
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(300) NOT NULL,
    description TEXT,
    event_date DATE,
    event_time TIME,
    end_date DATE DEFAULT NULL,
    end_time TIME DEFAULT NULL,
    location VARCHAR(300) DEFAULT '',
    category VARCHAR(100) DEFAULT '',
    event_type VARCHAR(100) DEFAULT '',
    organizer VARCHAR(200) DEFAULT '',
    organizer_email VARCHAR(200) DEFAULT '',
    target_audience VARCHAR(100) DEFAULT 'All',
    max_attendees INT DEFAULT 0,
    status VARCHAR(50) DEFAULT 'Scheduled',
    created_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Event Attendees ──
CREATE TABLE IF NOT EXISTS event_attendees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    full_name VARCHAR(200) DEFAULT '',
    email VARCHAR(200) DEFAULT '',
    phone VARCHAR(50) DEFAULT '',
    organization VARCHAR(200) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_event (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Event Categories ──
CREATE TABLE IF NOT EXISTS event_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    color VARCHAR(20) DEFAULT '#0d6efd',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Duty Rosters ──
CREATE TABLE IF NOT EXISTS duty_rosters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_name VARCHAR(200) NOT NULL,
    shift VARCHAR(100) NOT NULL,
    roster_date DATE NOT NULL,
    location VARCHAR(200) DEFAULT '',
    notes TEXT,
    created_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Welfare Cases ──
CREATE TABLE IF NOT EXISTS welfare_cases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    student_name VARCHAR(200) DEFAULT '',
    case_type VARCHAR(100) DEFAULT '',
    description TEXT,
    reported_by INT,
    reported_by_name VARCHAR(200) DEFAULT '',
    assigned_to INT DEFAULT 0,
    priority VARCHAR(50) DEFAULT 'Medium',
    status ENUM('open','in_progress','resolved','closed','Open','Pending') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Welfare Actions ──
CREATE TABLE IF NOT EXISTS welfare_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    action_description TEXT,
    action_by VARCHAR(200) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_case (case_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Guild Feedback ──
CREATE TABLE IF NOT EXISTS guild_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    category VARCHAR(100) DEFAULT '',
    subject VARCHAR(255) DEFAULT '',
    message TEXT,
    priority ENUM('normal','important','urgent') DEFAULT 'normal',
    status ENUM('pending','reviewed','acted') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Calendar Events (guild-president) ──
CREATE TABLE IF NOT EXISTS calendar_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATE,
    start_time TIME,
    end_time TIME,
    location VARCHAR(255),
    event_type VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Sports Events ──
CREATE TABLE IF NOT EXISTS sports_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATETIME,
    location VARCHAR(200) DEFAULT '',
    event_type VARCHAR(100) DEFAULT '',
    is_active TINYINT(1) DEFAULT 1,
    created_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Student Counseling Sessions ──
CREATE TABLE IF NOT EXISTS counseling_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT DEFAULT 0,
    student_name VARCHAR(200) DEFAULT '',
    session_type VARCHAR(50) DEFAULT 'general',
    counselor_name VARCHAR(200) DEFAULT '',
    notes TEXT,
    session_date DATE,
    session_time TIME DEFAULT NULL,
    status VARCHAR(30) DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Student Discipline ──
CREATE TABLE IF NOT EXISTS student_discipline (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    offense TEXT,
    reported_by VARCHAR(200),
    hearing_date DATE,
    outcome VARCHAR(500),
    action_taken VARCHAR(200),
    status ENUM('open','resolved','appealed','Pending') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Student Discipline Records ──
CREATE TABLE IF NOT EXISTS student_discipline_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    violation_type VARCHAR(200),
    description TEXT,
    severity ENUM('low','medium','high') DEFAULT 'medium',
    action_taken VARCHAR(200),
    status ENUM('pending','resolved','appealed','Pending','Open','Under Investigation') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Student Welfare Cases (staff DB variant) ──
CREATE TABLE IF NOT EXISTS student_welfare_cases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    student_name VARCHAR(200) DEFAULT '',
    case_type VARCHAR(200),
    issue_description TEXT,
    description TEXT,
    priority VARCHAR(20) DEFAULT 'normal',
    severity ENUM('low','medium','high','critical') DEFAULT 'medium',
    status ENUM('open','in_progress','resolved','closed','open') DEFAULT 'open',
    assigned_to VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Student Activities ──
CREATE TABLE IF NOT EXISTS student_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    activity_name VARCHAR(200) DEFAULT '',
    activity_description TEXT,
    activity_date DATE DEFAULT NULL,
    location VARCHAR(200) DEFAULT '',
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Student Appeals ──
CREATE TABLE IF NOT EXISTS student_appeals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    appeal_type VARCHAR(200),
    reason TEXT,
    outcome VARCHAR(500),
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    reviewed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Quality Assurance (staff DB) ──
CREATE TABLE IF NOT EXISTS quality_assurance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_title VARCHAR(300),
    review_type VARCHAR(200) DEFAULT '',
    review_area VARCHAR(200) DEFAULT '',
    department VARCHAR(200),
    reviewer VARCHAR(200) DEFAULT '',
    reviewed_by INT DEFAULT 0,
    review_date DATE DEFAULT NULL,
    score DECIMAL(5,2),
    findings TEXT,
    recommendations TEXT,
    status ENUM('draft','completed','reviewed') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Quality Standards ──
CREATE TABLE IF NOT EXISTS quality_standards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    standard_code VARCHAR(50) DEFAULT '',
    standard_name VARCHAR(200) NOT NULL,
    description TEXT,
    department VARCHAR(200) DEFAULT '',
    compliance_status VARCHAR(50) DEFAULT 'Pending',
    last_reviewed DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Quality Assurance Reviews (ceo) ──
CREATE TABLE IF NOT EXISTS quality_assurance_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_title VARCHAR(300) DEFAULT '',
    review_area VARCHAR(200) DEFAULT '',
    department VARCHAR(200) DEFAULT '',
    score DECIMAL(5,2) DEFAULT 0,
    findings TEXT,
    recommendations TEXT,
    reviewed_by INT DEFAULT 0,
    review_date DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Staff Audit Logs (ceo) ──
CREATE TABLE IF NOT EXISTS staff_audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT 0,
    action VARCHAR(200) DEFAULT '',
    entity_type VARCHAR(100) DEFAULT '',
    entity_id INT DEFAULT 0,
    description TEXT,
    ip_address VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Communication Log ──
CREATE TABLE IF NOT EXISTS communication_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT,
    sender_name VARCHAR(200),
    recipient_role VARCHAR(100),
    subject VARCHAR(300),
    message TEXT,
    is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Department Performance ──
CREATE TABLE IF NOT EXISTS department_performance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department VARCHAR(200),
    metric VARCHAR(200),
    value DECIMAL(14,2),
    period VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Compliance Tracking ──
CREATE TABLE IF NOT EXISTS compliance_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department VARCHAR(200),
    compliance_type VARCHAR(200),
    status ENUM('compliant','non_compliant','pending_review') DEFAULT 'pending_review',
    notes TEXT,
    reviewed_by VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Compliance Requirements ──
CREATE TABLE IF NOT EXISTS compliance_requirements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requirement_name VARCHAR(200) NOT NULL,
    description TEXT,
    deadline DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    created_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Improvement Tracking ──
CREATE TABLE IF NOT EXISTS improvement_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    area VARCHAR(200),
    improvement_action TEXT,
    target_date DATE,
    progress DECIMAL(5,2) DEFAULT 0,
    status ENUM('planned','in_progress','completed') DEFAULT 'planned',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Deputy Tasks ──
CREATE TABLE IF NOT EXISTS deputy_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_title VARCHAR(300),
    description TEXT,
    assigned_by VARCHAR(200),
    priority ENUM('low','medium','high','urgent') DEFAULT 'medium',
    status ENUM('pending','in_progress','completed','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Teaching Quality Reviews ──
CREATE TABLE IF NOT EXISTS teaching_quality_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lecturer_id INT,
    review_date DATE,
    teaching_score DECIMAL(5,2),
    course_code VARCHAR(50),
    observer VARCHAR(200),
    feedback TEXT,
    status ENUM('draft','completed','reviewed') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Teaching Assessments ──
CREATE TABLE IF NOT EXISTS teaching_assessments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lecturer_id INT NOT NULL,
    assessment_title VARCHAR(200) DEFAULT '',
    course_code VARCHAR(50) DEFAULT '',
    score DECIMAL(5,2) DEFAULT 0,
    comments TEXT,
    assessed_by INT DEFAULT 0,
    assessment_date DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_lecturer (lecturer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Teaching Resources ──
CREATE TABLE IF NOT EXISTS teaching_resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lecturer_id INT NOT NULL,
    resource_name VARCHAR(200) DEFAULT '',
    resource_type VARCHAR(50) DEFAULT 'Document',
    file_path VARCHAR(500) DEFAULT '',
    course_code VARCHAR(50) DEFAULT '',
    description TEXT,
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_lecturer (lecturer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Meeting Minutes ──
CREATE TABLE IF NOT EXISTS meeting_minutes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meeting_id INT,
    agenda_item VARCHAR(300),
    discussion TEXT,
    resolution TEXT,
    action_items TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Meeting Actions ──
CREATE TABLE IF NOT EXISTS meeting_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meeting_id INT,
    action_item TEXT,
    assigned_to VARCHAR(200),
    due_date DATE,
    status ENUM('pending','in_progress','completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Payroll History ──
CREATE TABLE IF NOT EXISTS payroll_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    gross_salary DECIMAL(14,2) DEFAULT 0,
    deductions DECIMAL(14,2) DEFAULT 0,
    net_salary DECIMAL(14,2) DEFAULT 0,
    payment_date DATE DEFAULT NULL,
    payment_method VARCHAR(50) DEFAULT '',
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_staff (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Salary Structures ──
CREATE TABLE IF NOT EXISTS salary_structures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    basic_salary DECIMAL(14,2) DEFAULT 0,
    total_allowances DECIMAL(14,2) DEFAULT 0,
    total_deductions DECIMAL(14,2) DEFAULT 0,
    net_salary DECIMAL(14,2) DEFAULT 0,
    status VARCHAR(50) DEFAULT 'active',
    effective_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_staff (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Staff Contracts ──
CREATE TABLE IF NOT EXISTS staff_contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contract_number VARCHAR(50) NOT NULL,
    staff_id INT NOT NULL,
    contract_type VARCHAR(100) DEFAULT '',
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    job_title VARCHAR(200) DEFAULT '',
    department VARCHAR(200) DEFAULT '',
    salary DECIMAL(14,2) DEFAULT 0,
    probation_period VARCHAR(100) DEFAULT '',
    notice_period VARCHAR(100) DEFAULT '',
    contract_terms TEXT,
    benefits TEXT,
    signed_date DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_staff (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Staff Appraisals ──
CREATE TABLE IF NOT EXISTS staff_appraisals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    appraisal_period VARCHAR(50) DEFAULT '',
    overall_score DECIMAL(5,2) DEFAULT 0,
    strengths TEXT,
    improvements TEXT,
    goals TEXT,
    comments TEXT,
    status VARCHAR(50) DEFAULT 'Draft',
    reviewed_by INT DEFAULT 0,
    review_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_staff (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Performance Reviews (hr-manager) ──
CREATE TABLE IF NOT EXISTS performance_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    review_period VARCHAR(50) DEFAULT '',
    score DECIMAL(5,2) DEFAULT 0,
    comments TEXT,
    reviewer_id INT DEFAULT 0,
    status VARCHAR(50) DEFAULT 'Draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_staff (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Leave Requests ──
CREATE TABLE IF NOT EXISTS leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    leave_type_id INT DEFAULT 0,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    days_requested INT DEFAULT 0,
    reason TEXT,
    status VARCHAR(50) DEFAULT 'Pending',
    approved_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_staff (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Leave Types ──
CREATE TABLE IF NOT EXISTS leave_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_name VARCHAR(100) NOT NULL,
    days_allowed INT DEFAULT 0,
    description TEXT,
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Leave Balance ──
CREATE TABLE IF NOT EXISTS leave_balance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    leave_type_id INT DEFAULT 0,
    year INT DEFAULT 0,
    days_used DECIMAL(5,2) DEFAULT 0,
    days_remaining DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_staff (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Job Vacancies ──
CREATE TABLE IF NOT EXISTS job_vacancies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    department_id INT DEFAULT 0,
    description TEXT,
    requirements TEXT,
    salary_range VARCHAR(100) DEFAULT '',
    posted_date DATE DEFAULT NULL,
    closing_date DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Job Applications ──
CREATE TABLE IF NOT EXISTS job_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    position_id INT DEFAULT 0,
    applicant_name VARCHAR(200) DEFAULT '',
    email VARCHAR(100) DEFAULT '',
    phone VARCHAR(50) DEFAULT '',
    cv_path VARCHAR(500) DEFAULT '',
    status VARCHAR(50) DEFAULT 'New',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_position (position_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Staff Attendance ──
CREATE TABLE IF NOT EXISTS staff_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    date DATE DEFAULT NULL,
    check_in TIME DEFAULT NULL,
    check_out TIME DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Present',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_staff (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Attendance (generic) ──
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT DEFAULT 0,
    date DATE DEFAULT NULL,
    check_in TIME DEFAULT NULL,
    check_out TIME DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Present',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Staff Disciplinary ──
CREATE TABLE IF NOT EXISTS staff_disciplinary (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    offense VARCHAR(200) DEFAULT '',
    description TEXT,
    action_taken VARCHAR(200) DEFAULT '',
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_staff (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Staff Training ──
CREATE TABLE IF NOT EXISTS staff_training (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    training_name VARCHAR(200) DEFAULT '',
    provider VARCHAR(200) DEFAULT '',
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_staff (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Staff Licenses ──
CREATE TABLE IF NOT EXISTS staff_licenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    license_name VARCHAR(200) DEFAULT '',
    license_number VARCHAR(100) DEFAULT '',
    issued_by VARCHAR(200) DEFAULT '',
    issue_date DATE DEFAULT NULL,
    expiry_date DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_staff (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Employment Contracts (hr-manager) ──
CREATE TABLE IF NOT EXISTS employment_contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    contract_type VARCHAR(100) DEFAULT '',
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_staff (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Staff Tasks ──
CREATE TABLE IF NOT EXISTS staff_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    task_title VARCHAR(200) DEFAULT '',
    description TEXT,
    due_date DATE DEFAULT NULL,
    priority VARCHAR(50) DEFAULT 'Medium',
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_staff (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Professional Licenses ──
CREATE TABLE IF NOT EXISTS professional_licenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    license_name VARCHAR(200) DEFAULT '',
    license_number VARCHAR(100) DEFAULT '',
    issuing_authority VARCHAR(200) DEFAULT '',
    issue_date DATE DEFAULT NULL,
    expiry_date DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_staff (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Staff Recruitment ──
CREATE TABLE IF NOT EXISTS staff_recruitment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    position VARCHAR(200) DEFAULT '',
    department VARCHAR(200) DEFAULT '',
    applicant_name VARCHAR(200) DEFAULT '',
    email VARCHAR(100) DEFAULT '',
    phone VARCHAR(50) DEFAULT '',
    qualifications TEXT,
    status VARCHAR(50) DEFAULT 'New',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Research Projects ──
CREATE TABLE IF NOT EXISTS research_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_title VARCHAR(300) DEFAULT '',
    description TEXT,
    lead_researcher VARCHAR(200) DEFAULT '',
    department VARCHAR(200) DEFAULT '',
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Cost Centers ──
CREATE TABLE IF NOT EXISTS cost_centers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cost_center_code VARCHAR(50) NOT NULL,
    cost_center_name VARCHAR(200) NOT NULL,
    department VARCHAR(200) DEFAULT '',
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Onboarding Checklist ──
CREATE TABLE IF NOT EXISTS onboarding_checklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT DEFAULT 0,
    item_name VARCHAR(200) DEFAULT '',
    status VARCHAR(50) DEFAULT 'Pending',
    completed_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Onboarding Tasks ──
CREATE TABLE IF NOT EXISTS onboarding_tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT DEFAULT 0,
    task_name VARCHAR(200) DEFAULT '',
    assigned_to VARCHAR(200) DEFAULT '',
    due_date DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Onboarding Completions ──
CREATE TABLE IF NOT EXISTS onboarding_completions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT DEFAULT 0,
    task_id INT DEFAULT 0,
    completed_by INT DEFAULT 0,
    completed_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Partnerships ──
CREATE TABLE IF NOT EXISTS partnerships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partner_name VARCHAR(200) NOT NULL,
    partner_type VARCHAR(100) DEFAULT '',
    contact_person VARCHAR(200) DEFAULT '',
    email VARCHAR(100) DEFAULT '',
    phone VARCHAR(50) DEFAULT '',
    description TEXT,
    start_date DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Intake Plans ──
CREATE TABLE IF NOT EXISTS intake_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    intake_name VARCHAR(200) DEFAULT '',
    program VARCHAR(200) DEFAULT '',
    target_count INT DEFAULT 0,
    planned_start DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Draft',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Inventory Reports ──
CREATE TABLE IF NOT EXISTS inventory_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inventory_id INT DEFAULT 0,
    report_type VARCHAR(100) DEFAULT '',
    department VARCHAR(200) DEFAULT '',
    description TEXT,
    created_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Inventory (referenced in inventory-reports) ──
CREATE TABLE IF NOT EXISTS inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(200) DEFAULT '',
    item_category VARCHAR(100) DEFAULT '',
    location VARCHAR(200) DEFAULT '',
    department VARCHAR(200) DEFAULT '',
    quantity INT DEFAULT 0,
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Institutional Alerts ──
CREATE TABLE IF NOT EXISTS institutional_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_title VARCHAR(300) NOT NULL,
    alert_type VARCHAR(100) DEFAULT 'info',
    message TEXT,
    target_audience VARCHAR(100) DEFAULT 'All',
    status VARCHAR(50) DEFAULT 'Active',
    created_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Alert Recipients ──
CREATE TABLE IF NOT EXISTS alert_recipients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_id INT NOT NULL,
    recipient_id INT DEFAULT 0,
    recipient_role VARCHAR(100) DEFAULT '',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_alert (alert_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Alerts (director-general) ──
CREATE TABLE IF NOT EXISTS alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(300) DEFAULT '',
    message TEXT,
    alert_type VARCHAR(100) DEFAULT 'info',
    severity VARCHAR(50) DEFAULT 'medium',
    status VARCHAR(50) DEFAULT 'active',
    target_audience VARCHAR(100) DEFAULT 'All',
    created_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Transport Vehicles ──
CREATE TABLE IF NOT EXISTS transport_vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_number VARCHAR(50) NOT NULL,
    vehicle_type VARCHAR(100) DEFAULT '',
    vehicle_name VARCHAR(200) DEFAULT '',
    capacity INT DEFAULT 0,
    fuel_type VARCHAR(50) DEFAULT 'Diesel',
    insurance_expiry DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Transport Drivers ──
CREATE TABLE IF NOT EXISTS transport_drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT DEFAULT 0,
    driver_name VARCHAR(200) DEFAULT '',
    license_number VARCHAR(100) DEFAULT '',
    phone VARCHAR(50) DEFAULT '',
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Transport Routes ──
CREATE TABLE IF NOT EXISTS transport_routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_name VARCHAR(200) NOT NULL,
    start_location VARCHAR(200) DEFAULT '',
    end_location VARCHAR(200) DEFAULT '',
    distance_km DECIMAL(8,2) DEFAULT 0,
    estimated_duration_minutes INT DEFAULT 30,
    route_type VARCHAR(50) DEFAULT 'both',
    fare_amount DECIMAL(14,2) DEFAULT 0,
    notes TEXT,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Transport Trips ──
CREATE TABLE IF NOT EXISTS transport_trips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT DEFAULT 0,
    driver_id INT DEFAULT 0,
    route_id INT DEFAULT 0,
    route_name VARCHAR(200) DEFAULT '',
    departure_time DATETIME DEFAULT NULL,
    arrival_time DATETIME DEFAULT NULL,
    passengers_count INT DEFAULT 0,
    fuel_cost DECIMAL(14,2) DEFAULT 0,
    trip_distance DECIMAL(8,2) DEFAULT 0,
    trip_fare DECIMAL(14,2) DEFAULT 0,
    notes TEXT,
    requested_by INT DEFAULT 0,
    dg_approval_status VARCHAR(50) DEFAULT 'pending',
    dg_approved_by INT DEFAULT 0,
    dg_approved_at DATETIME DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Transport Student Assignments ──
CREATE TABLE IF NOT EXISTS transport_student_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT DEFAULT 0,
    student_name VARCHAR(200) DEFAULT '',
    registration_number VARCHAR(50) DEFAULT '',
    route_id INT DEFAULT 0,
    vehicle_id INT DEFAULT 0,
    pickup_point VARCHAR(200) DEFAULT '',
    dropoff_point VARCHAR(200) DEFAULT '',
    academic_year VARCHAR(20) DEFAULT '',
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Transport Fuel Log ──
CREATE TABLE IF NOT EXISTS transport_fuel_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT DEFAULT 0,
    driver_id INT DEFAULT 0,
    fuel_date DATE DEFAULT NULL,
    liters DECIMAL(8,2) DEFAULT 0,
    cost DECIMAL(14,2) DEFAULT 0,
    odometer_reading INT DEFAULT 0,
    station VARCHAR(200) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Vehicle Maintenance ──
CREATE TABLE IF NOT EXISTS vehicle_maintenance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT DEFAULT 0,
    maintenance_type VARCHAR(100) DEFAULT '',
    description TEXT,
    cost DECIMAL(14,2) DEFAULT 0,
    maintenance_date DATE DEFAULT NULL,
    next_maintenance DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_vehicle (vehicle_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Fuel Management ──
CREATE TABLE IF NOT EXISTS fuel_management (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT DEFAULT 0,
    vehicle_name VARCHAR(200) DEFAULT '',
    fueling_date DATE DEFAULT NULL,
    liters DECIMAL(8,2) DEFAULT 0,
    cost DECIMAL(14,2) DEFAULT 0,
    odometer_reading INT DEFAULT 0,
    station VARCHAR(200) DEFAULT '',
    driver_id INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Trip Logs ──
CREATE TABLE IF NOT EXISTS trip_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_id INT DEFAULT 0,
    driver_id INT DEFAULT 0,
    route_name VARCHAR(200) DEFAULT '',
    start_location VARCHAR(200) DEFAULT '',
    end_location VARCHAR(200) DEFAULT '',
    trip_date DATE DEFAULT NULL,
    departure_time TIME DEFAULT NULL,
    arrival_time TIME DEFAULT NULL,
    distance_km DECIMAL(8,2) DEFAULT 0,
    passengers INT DEFAULT 0,
    status VARCHAR(50) DEFAULT 'Completed',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Department Requests ──
CREATE TABLE IF NOT EXISTS department_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_number VARCHAR(50) NOT NULL,
    from_department VARCHAR(100) NOT NULL,
    to_department VARCHAR(100) DEFAULT 'Store',
    item_name VARCHAR(300) NOT NULL,
    quantity INT DEFAULT 1,
    unit VARCHAR(50) DEFAULT '',
    purpose TEXT,
    urgency VARCHAR(50) DEFAULT 'Normal',
    status VARCHAR(50) DEFAULT 'Pending',
    requested_by INT,
    approved_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_from_dept (from_department),
    INDEX idx_to_dept (to_department)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Store Requests ──
CREATE TABLE IF NOT EXISTS store_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_number VARCHAR(50) DEFAULT '',
    requested_by INT DEFAULT 0,
    requester_name VARCHAR(255) DEFAULT '',
    requester_role VARCHAR(50) DEFAULT '',
    department VARCHAR(100) DEFAULT '',
    urgency VARCHAR(50) DEFAULT 'Normal',
    status VARCHAR(50) DEFAULT 'pending',
    notes TEXT,
    items TEXT,
    rejection_reason TEXT,
    fulfilled_by INT DEFAULT NULL,
    fulfilled_at DATETIME DEFAULT NULL,
    approved_by INT DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    approval_request_id INT DEFAULT NULL,
    forwarded_to INT DEFAULT NULL,
    forwarded_to_role VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sr_status (status),
    INDEX idx_sr_by (requested_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Store Categories ──
CREATE TABLE IF NOT EXISTS store_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(200) DEFAULT '',
    description TEXT,
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Store Inventory ──
CREATE TABLE IF NOT EXISTS store_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_code VARCHAR(50) DEFAULT '',
    item_name VARCHAR(200) DEFAULT '',
    category_id INT DEFAULT NULL,
    unit VARCHAR(50) DEFAULT '',
    quantity DECIMAL(14,2) DEFAULT 0,
    reorder_level DECIMAL(14,2) DEFAULT 0,
    unit_cost DECIMAL(14,2) DEFAULT 0,
    location VARCHAR(200) DEFAULT '',
    batch_number VARCHAR(100) DEFAULT NULL,
    expiry_date DATE DEFAULT NULL,
    supplier VARCHAR(200) DEFAULT '',
    status VARCHAR(20) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Store Request Items ──
CREATE TABLE IF NOT EXISTS store_request_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT DEFAULT 0,
    item_id INT DEFAULT 0,
    quantity_requested DECIMAL(14,2) DEFAULT 0,
    quantity_fulfilled DECIMAL(14,2) DEFAULT 0,
    notes TEXT,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Store Inventory Transactions ──
CREATE TABLE IF NOT EXISTS store_inventory_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT DEFAULT 0,
    transaction_type VARCHAR(50) DEFAULT '',
    quantity DECIMAL(14,2) DEFAULT 0,
    quantity_before DECIMAL(14,2) DEFAULT NULL,
    quantity_after DECIMAL(14,2) DEFAULT NULL,
    reason TEXT,
    created_by INT DEFAULT NULL,
    reference_type VARCHAR(50) DEFAULT NULL,
    reference_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Expenses ──
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expense_id VARCHAR(50) DEFAULT '',
    description TEXT,
    expense_category VARCHAR(100) DEFAULT '',
    amount DECIMAL(14,2) DEFAULT 0,
    expense_date DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    requested_by INT DEFAULT 0,
    approved_by INT DEFAULT 0,
    approval_date DATETIME DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Chemical Inventory ──
CREATE TABLE IF NOT EXISTS chemical_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chemical_code VARCHAR(50) DEFAULT '',
    chemical_name VARCHAR(200) DEFAULT '',
    chemical_type VARCHAR(100) DEFAULT '',
    cas_number VARCHAR(50) DEFAULT '',
    hazard_class VARCHAR(100) DEFAULT '',
    storage_location VARCHAR(200) DEFAULT '',
    quantity_on_hand DECIMAL(10,2) DEFAULT 0,
    unit_of_measure VARCHAR(50) DEFAULT '',
    reorder_level DECIMAL(10,2) DEFAULT 0,
    supplier VARCHAR(200) DEFAULT '',
    expiry_date DATE DEFAULT NULL,
    date_received DATE DEFAULT NULL,
    received_by INT DEFAULT 0,
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Receipt Templates ──
CREATE TABLE IF NOT EXISTS receipt_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(200) NOT NULL,
    template_type VARCHAR(100) DEFAULT '',
    template_content LONGTEXT,
    is_active TINYINT(1) DEFAULT 1,
    is_default TINYINT(1) DEFAULT 0,
    created_by INT DEFAULT 0,
    is_deleted TINYINT(1) DEFAULT 0,
    deleted_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Document Templates ──
CREATE TABLE IF NOT EXISTS document_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(200) NOT NULL,
    template_type VARCHAR(100) DEFAULT '',
    template_content LONGTEXT,
    is_default TINYINT(1) DEFAULT 0,
    created_by INT DEFAULT 0,
    is_deleted TINYINT(1) DEFAULT 0,
    deleted_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Proof of Payments ──
CREATE TABLE IF NOT EXISTS proof_of_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT DEFAULT 0,
    payment_amount DECIMAL(14,2) DEFAULT 0,
    payment_method VARCHAR(50) DEFAULT '',
    payment_date DATE DEFAULT NULL,
    reference_number VARCHAR(100) DEFAULT '',
    file_path VARCHAR(500) DEFAULT '',
    status VARCHAR(50) DEFAULT 'Pending',
    verified_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Payment Subscriptions ──
CREATE TABLE IF NOT EXISTS payment_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT DEFAULT 0,
    subscription_type VARCHAR(100) DEFAULT '',
    amount DECIMAL(14,2) DEFAULT 0,
    frequency VARCHAR(50) DEFAULT 'Monthly',
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Subscription Deductions ──
CREATE TABLE IF NOT EXISTS subscription_deductions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subscription_id INT DEFAULT 0,
    amount DECIMAL(14,2) DEFAULT 0,
    deduction_date DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Penalty Configurations ──
CREATE TABLE IF NOT EXISTS penalty_configurations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    penalty_name VARCHAR(200) NOT NULL,
    penalty_type VARCHAR(100) DEFAULT '',
    amount DECIMAL(14,2) DEFAULT 0,
    description TEXT,
    applies_to VARCHAR(100) DEFAULT 'All',
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Cybersecurity: Security Incidents ──
CREATE TABLE IF NOT EXISTS security_incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_type VARCHAR(100),
    description TEXT,
    severity VARCHAR(20) DEFAULT 'medium',
    status VARCHAR(30) DEFAULT 'Open',
    location VARCHAR(200) DEFAULT '',
    reported_by VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Cybersecurity: Access Logs ──
CREATE TABLE IF NOT EXISTS security_access_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(100),
    access_type VARCHAR(50),
    location VARCHAR(200),
    ip_address VARCHAR(50),
    status VARCHAR(20) DEFAULT 'allowed',
    accessed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Security Patrols ──
CREATE TABLE IF NOT EXISTS security_patrols (
    id INT AUTO_INCREMENT PRIMARY KEY,
    guard_id INT DEFAULT 0,
    patrol_date DATE DEFAULT NULL,
    start_time TIME DEFAULT NULL,
    end_time TIME DEFAULT NULL,
    route VARCHAR(200) DEFAULT '',
    notes TEXT,
    status VARCHAR(50) DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Security Visitors ──
CREATE TABLE IF NOT EXISTS security_visitors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visitor_name VARCHAR(200) DEFAULT '',
    visitor_phone VARCHAR(50) DEFAULT '',
    visitor_id_number VARCHAR(100) DEFAULT '',
    purpose VARCHAR(300) DEFAULT '',
    person_visiting VARCHAR(200) DEFAULT '',
    visit_date DATE DEFAULT NULL,
    actual_arrival DATETIME DEFAULT NULL,
    actual_departure DATETIME DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Expected',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Security Equipment ──
CREATE TABLE IF NOT EXISTS security_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_name VARCHAR(200) DEFAULT '',
    equipment_type VARCHAR(100) DEFAULT '',
    location VARCHAR(200) DEFAULT '',
    status VARCHAR(50) DEFAULT 'Operational',
    next_maintenance_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Security Emergency Contacts ──
CREATE TABLE IF NOT EXISTS security_emergency_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contact_name VARCHAR(200) DEFAULT '',
    contact_type VARCHAR(100) DEFAULT '',
    phone VARCHAR(50) DEFAULT '',
    email VARCHAR(100) DEFAULT '',
    organization VARCHAR(200) DEFAULT '',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Visitor Logs ──
CREATE TABLE IF NOT EXISTS visitor_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visitor_name VARCHAR(200) DEFAULT '',
    visitor_phone VARCHAR(50) DEFAULT '',
    visitor_id_number VARCHAR(100) DEFAULT '',
    purpose VARCHAR(300) DEFAULT '',
    person_visiting VARCHAR(200) DEFAULT '',
    check_in_time DATETIME DEFAULT NULL,
    check_out_time DATETIME DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Checked In',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Access Control Logs ──
CREATE TABLE IF NOT EXISTS access_control_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(200) DEFAULT '',
    access_type VARCHAR(50) DEFAULT '',
    location VARCHAR(200) DEFAULT '',
    access_time DATETIME DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Allowed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Digital Learning: Library Digital Resources ──
CREATE TABLE IF NOT EXISTS library_digital_resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(300),
    author_creator VARCHAR(200),
    resource_type VARCHAR(50),
    access_level VARCHAR(50),
    publication_year VARCHAR(10),
    file_url VARCHAR(500),
    description TEXT,
    added_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Digital Learning: Skills Laboratory ──
CREATE TABLE IF NOT EXISTS skills_laboratory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lab_name VARCHAR(200),
    location VARCHAR(200),
    capacity INT DEFAULT 0,
    status VARCHAR(50) DEFAULT 'Active',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Sickbay: Medicine Stock ──
CREATE TABLE IF NOT EXISTS sickbay_medicine_stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicine_name VARCHAR(200) DEFAULT '',
    medicine_code VARCHAR(50) DEFAULT '',
    category VARCHAR(100) DEFAULT '',
    quantity INT DEFAULT 0,
    unit VARCHAR(50) DEFAULT 'Tablets',
    unit_cost DECIMAL(14,2) DEFAULT 0,
    supplier VARCHAR(200) DEFAULT '',
    expiry_date DATE DEFAULT NULL,
    reorder_level INT DEFAULT 10,
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Sickbay: Medicine Transactions ──
CREATE TABLE IF NOT EXISTS sickbay_medicine_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    medicine_id INT DEFAULT 0,
    transaction_type VARCHAR(50) DEFAULT '',
    quantity INT DEFAULT 0,
    reference VARCHAR(100) DEFAULT '',
    notes TEXT,
    created_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_medicine (medicine_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Sickbay: Daily Sick Records ──
CREATE TABLE IF NOT EXISTS daily_sick_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_number VARCHAR(50) DEFAULT '',
    student_id INT DEFAULT 0,
    student_name VARCHAR(200) DEFAULT '',
    student_number VARCHAR(50) DEFAULT '',
    program VARCHAR(200) DEFAULT '',
    year_of_study INT DEFAULT 0,
    sickness_id INT DEFAULT NULL,
    sickness_name VARCHAR(200) DEFAULT '',
    temperature VARCHAR(20) DEFAULT '',
    blood_pressure VARCHAR(20) DEFAULT '',
    symptoms TEXT,
    diagnosis TEXT,
    treatment_given TEXT,
    medicines_prescribed TEXT,
    severity VARCHAR(50) DEFAULT '',
    status VARCHAR(50) DEFAULT '',
    referred_to VARCHAR(200) DEFAULT '',
    attended_by VARCHAR(200) DEFAULT '',
    visit_date DATE DEFAULT NULL,
    visit_time TIME DEFAULT NULL,
    follow_up_date DATE DEFAULT NULL,
    notes TEXT,
    is_deleted TINYINT(1) DEFAULT 0,
    deleted_at DATETIME DEFAULT NULL,
    created_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Sickbay: Visits ──
CREATE TABLE IF NOT EXISTS sickbay_visits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT DEFAULT 0,
    student_name VARCHAR(200) DEFAULT '',
    visit_date DATE DEFAULT NULL,
    visit_time TIME DEFAULT NULL,
    reason TEXT,
    diagnosis TEXT,
    treatment TEXT,
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Sickbay: Sickness Directory ──
CREATE TABLE IF NOT EXISTS sickness_directory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sickness_code VARCHAR(50) DEFAULT '',
    sickness_name VARCHAR(200) DEFAULT '',
    category VARCHAR(100) DEFAULT '',
    common_symptoms TEXT,
    description TEXT,
    is_contagious TINYINT(1) DEFAULT 0,
    typical_treatment TEXT,
    status VARCHAR(50) DEFAULT 'Active',
    created_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Sickbay: Student Sick Leave ──
CREATE TABLE IF NOT EXISTS student_sick_leave (
    id INT AUTO_INCREMENT PRIMARY KEY,
    leave_number VARCHAR(50) DEFAULT '',
    student_id INT DEFAULT 0,
    student_name VARCHAR(200) DEFAULT '',
    student_number VARCHAR(50) DEFAULT '',
    program VARCHAR(200) DEFAULT '',
    year_of_study INT DEFAULT 0,
    sickness_id INT DEFAULT NULL,
    sickness_name VARCHAR(200) DEFAULT '',
    leave_from DATE DEFAULT NULL,
    leave_to DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    recommended_by VARCHAR(200) DEFAULT '',
    bed_rest_required TINYINT(1) DEFAULT 0,
    doctor_notes TEXT,
    created_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Skills Lab: Lab Equipment ──
CREATE TABLE IF NOT EXISTS lab_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_code VARCHAR(50) DEFAULT '',
    equipment_name VARCHAR(200) DEFAULT '',
    category VARCHAR(100) DEFAULT 'other',
    condition_status VARCHAR(50) DEFAULT 'good',
    quantity INT DEFAULT 1,
    location VARCHAR(200) DEFAULT '',
    last_maintenance DATE DEFAULT NULL,
    next_maintenance DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Skills Lab: Checkouts ──
CREATE TABLE IF NOT EXISTS lab_checkouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT DEFAULT 0,
    student_id INT DEFAULT 0,
    student_name VARCHAR(200) DEFAULT '',
    checkout_date DATETIME DEFAULT NULL,
    expected_return DATETIME DEFAULT NULL,
    actual_return DATETIME DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'checked_out',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_equipment (equipment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Skills Lab: Sessions ──
CREATE TABLE IF NOT EXISTS lab_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_name VARCHAR(200) DEFAULT '',
    course_code VARCHAR(50) DEFAULT '',
    lecturer_id INT DEFAULT 0,
    scheduled_date DATE DEFAULT NULL,
    start_time TIME DEFAULT NULL,
    end_time TIME DEFAULT NULL,
    max_students INT DEFAULT 30,
    status VARCHAR(50) DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Skills Lab: Demonstrations ──
CREATE TABLE IF NOT EXISTS lab_demonstrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT DEFAULT 0,
    equipment_id INT DEFAULT 0,
    demonstration_title VARCHAR(200) DEFAULT '',
    description TEXT,
    demonstrated_by INT DEFAULT 0,
    demonstration_date DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Skills Lab: Consumables ──
CREATE TABLE IF NOT EXISTS lab_consumables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(200) DEFAULT '',
    item_code VARCHAR(50) DEFAULT '',
    category VARCHAR(100) DEFAULT '',
    quantity INT DEFAULT 0,
    min_stock_level INT DEFAULT 10,
    unit VARCHAR(50) DEFAULT '',
    unit_cost DECIMAL(14,2) DEFAULT 0,
    supplier VARCHAR(200) DEFAULT '',
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Skills Lab: Attendance ──
CREATE TABLE IF NOT EXISTS lab_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT DEFAULT 0,
    student_id INT DEFAULT 0,
    student_name VARCHAR(200) DEFAULT '',
    status VARCHAR(50) DEFAULT 'Present',
    attendance_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Skills Lab: Incidents ──
CREATE TABLE IF NOT EXISTS lab_incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_date DATE DEFAULT NULL,
    incident_time TIME DEFAULT NULL,
    reported_by INT DEFAULT 0,
    incident_type VARCHAR(100) DEFAULT 'other',
    severity VARCHAR(50) DEFAULT 'minor',
    description TEXT,
    equipment_involved VARCHAR(200) DEFAULT '',
    student_involved VARCHAR(200) DEFAULT '',
    action_taken TEXT,
    status VARCHAR(50) DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Lab Practical Sessions ──
CREATE TABLE IF NOT EXISTS lab_practical_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_name VARCHAR(200) DEFAULT '',
    course_code VARCHAR(50) DEFAULT '',
    lab_room_id INT DEFAULT 0,
    scheduled_date DATE DEFAULT NULL,
    start_time TIME DEFAULT NULL,
    end_time TIME DEFAULT NULL,
    lecturer_id INT DEFAULT 0,
    status VARCHAR(50) DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Recycle Bin ──
CREATE TABLE IF NOT EXISTS recycle_bin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_table VARCHAR(100) DEFAULT '',
    original_id_column VARCHAR(50) DEFAULT 'id',
    original_id INT DEFAULT 0,
    item_name VARCHAR(300) DEFAULT '',
    item_type VARCHAR(50) DEFAULT '',
    deleted_by INT DEFAULT 0,
    deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Director News ──
CREATE TABLE IF NOT EXISTS director_news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(300) NOT NULL,
    slug VARCHAR(300) NOT NULL,
    content LONGTEXT,
    excerpt TEXT,
    featured_image VARCHAR(500) DEFAULT '',
    author_id INT DEFAULT 0,
    author_name VARCHAR(200) DEFAULT '',
    author_role VARCHAR(100) DEFAULT '',
    status VARCHAR(50) DEFAULT 'draft',
    published_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── CMS Events ──
CREATE TABLE IF NOT EXISTS cms_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(300) DEFAULT '',
    description TEXT,
    event_date DATE DEFAULT NULL,
    event_type VARCHAR(100) DEFAULT '',
    location VARCHAR(200) DEFAULT '',
    is_active TINYINT(1) DEFAULT 1,
    created_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── CMS Testimonials ──
CREATE TABLE IF NOT EXISTS cms_testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    content TEXT,
    author_name VARCHAR(200) DEFAULT '',
    author_role VARCHAR(100) DEFAULT '',
    rating INT DEFAULT 5,
    is_featured TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── CMS FAQs ──
CREATE TABLE IF NOT EXISTS cms_faqs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(500) DEFAULT '',
    answer TEXT,
    category VARCHAR(100) DEFAULT '',
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── CMS Settings ──
CREATE TABLE IF NOT EXISTS cms_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Pending Students ──
CREATE TABLE IF NOT EXISTS pending_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) DEFAULT '',
    middle_name VARCHAR(100) DEFAULT '',
    last_name VARCHAR(100) DEFAULT '',
    student_number VARCHAR(50) DEFAULT '',
    program VARCHAR(200) DEFAULT '',
    level VARCHAR(50) DEFAULT '',
    intake_year INT DEFAULT 0,
    intake_period VARCHAR(50) DEFAULT '',
    phone VARCHAR(50) DEFAULT '',
    email VARCHAR(100) DEFAULT '',
    date_of_birth DATE DEFAULT NULL,
    submitted_by INT DEFAULT 0,
    status VARCHAR(50) DEFAULT 'pending_approval',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Staff Departments ──
CREATE TABLE IF NOT EXISTS staff_departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(200) NOT NULL,
    department_code VARCHAR(50) DEFAULT '',
    department_level INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Staff Roles ──
CREATE TABLE IF NOT EXISTS staff_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(100) NOT NULL,
    role_level INT DEFAULT 0,
    dashboard_path VARCHAR(200) DEFAULT '',
    is_active TINYINT(1) DEFAULT 1,
    is_executive TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Staff Activity Log ──
CREATE TABLE IF NOT EXISTS staff_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT DEFAULT 0,
    activity_type VARCHAR(100) DEFAULT '',
    activity_description TEXT,
    ip_address VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Approval Requests ──
CREATE TABLE IF NOT EXISTS approval_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_type VARCHAR(100) DEFAULT '',
    requester_id INT DEFAULT 0,
    entity_type VARCHAR(100) DEFAULT '',
    entity_id INT DEFAULT 0,
    status VARCHAR(50) DEFAULT 'Active',
    approved_by INT DEFAULT 0,
    approved_at DATETIME DEFAULT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── News (staff DB copy for sync) ──
CREATE TABLE IF NOT EXISTS news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(300) NOT NULL,
    slug VARCHAR(300) NOT NULL,
    summary TEXT,
    content LONGTEXT,
    featured_image VARCHAR(500) DEFAULT '',
    category VARCHAR(100) DEFAULT 'General',
    tags VARCHAR(500) DEFAULT '',
    status ENUM('draft','published','scheduled','archived') DEFAULT 'draft',
    is_featured TINYINT(1) DEFAULT 0,
    published_at DATETIME DEFAULT NULL,
    scheduled_at DATETIME DEFAULT NULL,
    archived_at DATETIME DEFAULT NULL,
    author_id INT DEFAULT 0,
    author_name VARCHAR(200) DEFAULT '',
    author_role VARCHAR(100) DEFAULT '',
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── News Categories (staff DB) ──
CREATE TABLE IF NOT EXISTS news_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    description TEXT,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Nursing Students ──
CREATE TABLE IF NOT EXISTS nursing_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT DEFAULT 0,
    student_name VARCHAR(200) DEFAULT '',
    program VARCHAR(200) DEFAULT '',
    year_of_study INT DEFAULT 0,
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Nursing Clinical Placements ──
CREATE TABLE IF NOT EXISTS nursing_clinical_placements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT DEFAULT 0,
    facility_name VARCHAR(300) DEFAULT '',
    department VARCHAR(200) DEFAULT '',
    supervisor_name VARCHAR(200) DEFAULT '',
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Midwifery Students ──
CREATE TABLE IF NOT EXISTS midwifery_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT DEFAULT 0,
    student_name VARCHAR(200) DEFAULT '',
    program VARCHAR(200) DEFAULT '',
    year_of_study INT DEFAULT 0,
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Midwifery Clinical Placements ──
CREATE TABLE IF NOT EXISTS midwifery_clinical_placements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT DEFAULT 0,
    facility_name VARCHAR(300) DEFAULT '',
    department VARCHAR(200) DEFAULT '',
    supervisor_name VARCHAR(200) DEFAULT '',
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Midwifery Skills Training ──
CREATE TABLE IF NOT EXISTS midwifery_skills_training (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT DEFAULT 0,
    skill_name VARCHAR(200) DEFAULT '',
    description TEXT,
    assessed_by INT DEFAULT 0,
    score DECIMAL(5,2) DEFAULT 0,
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Student Welfare Cases (staff DB for wardens) ──
-- Note: Already defined above; this is the extended version used by wardens
-- No additional CREATE needed; covered by the welfare_cases table above.


-- ================================================================
-- SECTION 2: igangaschool_students (Students Database)
-- ================================================================

-- ── Students (core table - referenced everywhere) ──
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) DEFAULT NULL,
    student_number VARCHAR(50) DEFAULT NULL,
    registration_number VARCHAR(50) DEFAULT NULL,
    first_name VARCHAR(100) DEFAULT '',
    middle_name VARCHAR(100) DEFAULT '',
    surname VARCHAR(100) DEFAULT '',
    full_name VARCHAR(300) DEFAULT '',
    gender ENUM('Male','Female','Other') DEFAULT NULL,
    date_of_birth DATE DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    mobile_number VARCHAR(50) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    program VARCHAR(200) DEFAULT '',
    level VARCHAR(50) DEFAULT '',
    year INT DEFAULT 1,
    current_semester INT DEFAULT 1,
    intake_year INT DEFAULT 0,
    intake_period VARCHAR(50) DEFAULT '',
    guardian_name VARCHAR(200) DEFAULT NULL,
    guardian_phone VARCHAR(50) DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Active',
    password VARCHAR(255) DEFAULT '',
    is_first_login TINYINT(1) DEFAULT 1,
    password_changed TINYINT(1) DEFAULT 0,
    is_deleted TINYINT(1) DEFAULT 0,
    deleted_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Student Attendance ──
CREATE TABLE IF NOT EXISTS student_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT DEFAULT 0,
    date DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Present',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Student Notifications ──
CREATE TABLE IF NOT EXISTS student_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT DEFAULT 0,
    type VARCHAR(50) DEFAULT 'info',
    title VARCHAR(200) DEFAULT '',
    message TEXT,
    priority VARCHAR(50) DEFAULT 'Normal',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Payments ──
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT DEFAULT 0,
    amount_received DECIMAL(14,2) DEFAULT 0,
    payment_date DATE DEFAULT NULL,
    payment_method VARCHAR(50) DEFAULT '',
    reference_number VARCHAR(100) DEFAULT '',
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Student Invoices ──
CREATE TABLE IF NOT EXISTS student_invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT DEFAULT 0,
    invoice_number VARCHAR(50) DEFAULT '',
    amount DECIMAL(14,2) DEFAULT 0,
    balance DECIMAL(14,2) DEFAULT 0,
    description TEXT,
    due_date DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Announcements ──
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(300) DEFAULT '',
    body TEXT,
    target_audience VARCHAR(100) DEFAULT 'All',
    priority VARCHAR(50) DEFAULT 'Normal',
    posted_by INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Programs ──
CREATE TABLE IF NOT EXISTS programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_name VARCHAR(200) DEFAULT '',
    program_type VARCHAR(100) DEFAULT '',
    duration_years DECIMAL(3,1) DEFAULT 2.0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Student Admissions ──
CREATE TABLE IF NOT EXISTS student_admissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT DEFAULT 0,
    program VARCHAR(200) DEFAULT '',
    admission_date DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Clinical Placements (students DB variant) ──
CREATE TABLE IF NOT EXISTS clinical_placements_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT DEFAULT 0,
    facility_name VARCHAR(300) DEFAULT '',
    supervisor_name VARCHAR(200) DEFAULT '',
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    competency_score DECIMAL(5,2) DEFAULT NULL,
    supervisor_evaluation TEXT DEFAULT NULL,
    logbook_submitted TINYINT(1) DEFAULT 0,
    status VARCHAR(50) DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Hostel Rooms ──
CREATE TABLE IF NOT EXISTS hostel_rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hostel_name VARCHAR(200) DEFAULT '',
    block_name VARCHAR(100) DEFAULT '',
    room_number VARCHAR(50) DEFAULT '',
    capacity INT DEFAULT 4,
    room_type VARCHAR(50) DEFAULT 'Standard',
    status VARCHAR(50) DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Hostel Assignments ──
CREATE TABLE IF NOT EXISTS hostel_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT DEFAULT 0,
    hostel_room_id INT DEFAULT 0,
    academic_year VARCHAR(20) DEFAULT '',
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Hostel Allocations (wardens) ──
CREATE TABLE IF NOT EXISTS hostel_allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT DEFAULT 0,
    hostel_room_id INT DEFAULT 0,
    academic_year VARCHAR(20) DEFAULT '',
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Meal Tracking ──
CREATE TABLE IF NOT EXISTS meal_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT DEFAULT 0,
    meal_type VARCHAR(50) DEFAULT '',
    meal_date DATE DEFAULT NULL,
    meal_time TIME DEFAULT NULL,
    notes TEXT,
    recorded_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Hostel Inspections ──
CREATE TABLE IF NOT EXISTS hostel_inspections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hostel_room_id INT DEFAULT 0,
    inspection_date DATE DEFAULT NULL,
    inspected_by INT DEFAULT 0,
    findings TEXT,
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Accommodation Requests ──
CREATE TABLE IF NOT EXISTS accommodation_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT DEFAULT 0,
    request_type VARCHAR(100) DEFAULT '',
    description TEXT,
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Hostel (legacy name) ──
CREATE TABLE IF NOT EXISTS hostel (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hostel_name VARCHAR(200) DEFAULT '',
    location VARCHAR(200) DEFAULT '',
    capacity INT DEFAULT 0,
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ================================================================
-- SECTION 3: igangaschool_website (Website Database)
-- ================================================================

-- ── News ──
CREATE TABLE IF NOT EXISTS news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(300) NOT NULL,
    slug VARCHAR(300) NOT NULL,
    summary TEXT,
    content LONGTEXT,
    excerpt TEXT,
    featured_image VARCHAR(500) DEFAULT '',
    category VARCHAR(100) DEFAULT 'General',
    tags VARCHAR(500) DEFAULT '',
    status ENUM('draft','published','scheduled','archived') DEFAULT 'draft',
    is_featured TINYINT(1) DEFAULT 0,
    published_at DATETIME DEFAULT NULL,
    scheduled_at DATETIME DEFAULT NULL,
    archived_at DATETIME DEFAULT NULL,
    author_id INT DEFAULT 0,
    author_name VARCHAR(200) DEFAULT '',
    author_role VARCHAR(100) DEFAULT '',
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── News Categories ──
CREATE TABLE IF NOT EXISTS news_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL,
    description TEXT,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── News Views ──
CREATE TABLE IF NOT EXISTS news_views (
    id INT AUTO_INCREMENT PRIMARY KEY,
    news_id INT DEFAULT 0,
    viewer_ip VARCHAR(50) DEFAULT '',
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_news (news_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Donations ──
CREATE TABLE IF NOT EXISTS donations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    donor_name VARCHAR(200) NOT NULL,
    donor_email VARCHAR(200) DEFAULT '',
    donor_phone VARCHAR(50) DEFAULT '',
    donor_type VARCHAR(50) DEFAULT 'Individual',
    amount DECIMAL(14,2) DEFAULT 0,
    currency VARCHAR(3) DEFAULT 'UGX',
    payment_method VARCHAR(50) DEFAULT '',
    transaction_ref VARCHAR(100) DEFAULT '',
    purpose VARCHAR(300) DEFAULT '',
    category VARCHAR(100) DEFAULT '',
    is_anonymous TINYINT(1) DEFAULT 0,
    receipt_number VARCHAR(100) DEFAULT '',
    status VARCHAR(50) DEFAULT 'Pending',
    received_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Contact Submissions ──
CREATE TABLE IF NOT EXISTS contact_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(200) DEFAULT '',
    email VARCHAR(100) DEFAULT '',
    phone VARCHAR(50) DEFAULT '',
    subject VARCHAR(300) DEFAULT '',
    message TEXT,
    status VARCHAR(50) DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Student Applications ──
CREATE TABLE IF NOT EXISTS student_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(200) DEFAULT '',
    email VARCHAR(100) DEFAULT '',
    phone VARCHAR(50) DEFAULT '',
    program VARCHAR(200) DEFAULT '',
    status VARCHAR(50) DEFAULT 'New',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Volunteer Applications ──
CREATE TABLE IF NOT EXISTS volunteer_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(200) DEFAULT '',
    email VARCHAR(100) DEFAULT '',
    phone VARCHAR(50) DEFAULT '',
    skills TEXT,
    availability TEXT,
    motivation TEXT,
    status VARCHAR(50) DEFAULT 'New',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Pages ──
CREATE TABLE IF NOT EXISTS pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(300) NOT NULL,
    slug VARCHAR(300) NOT NULL,
    content LONGTEXT,
    status VARCHAR(50) DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- END OF MIGRATION
-- Total: ~170 unique tables across 3 databases
-- ================================================================
