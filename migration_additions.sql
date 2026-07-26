-- ================================================================
-- SECTION 1B: igangaschool_staffs - Additional tables from audit
-- ================================================================

-- Staff (core)
CREATE TABLE IF NOT EXISTS `staff` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(50) DEFAULT NULL,
    full_name VARCHAR(200) NOT NULL,
    email VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(50) DEFAULT NULL,
    department VARCHAR(100) DEFAULT '',
    position VARCHAR(200) DEFAULT '',
    role_id INT DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Active',
    profile_photo VARCHAR(500) DEFAULT NULL,
    password VARCHAR(500) DEFAULT NULL,
    last_login DATETIME DEFAULT NULL,
    resignation_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_dept (department),
    KEY idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Departments
CREATE TABLE IF NOT EXISTS `departments` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    code VARCHAR(50) DEFAULT NULL,
    description TEXT,
    head_id INT DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Staff Inbox (Communications)
CREATE TABLE IF NOT EXISTS `staff_inbox` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    recipient_id INT NOT NULL,
    subject VARCHAR(300) NOT NULL,
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    is_deleted_sender TINYINT(1) DEFAULT 0,
    is_deleted_recipient TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_recipient (recipient_id),
    KEY idx_sender (sender_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Staff Profiles
CREATE TABLE IF NOT EXISTS `staff_profiles` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    profile_picture VARCHAR(500) DEFAULT NULL,
    bio TEXT,
    date_of_birth DATE DEFAULT NULL,
    gender VARCHAR(20) DEFAULT NULL,
    nationality VARCHAR(100) DEFAULT NULL,
    national_id VARCHAR(100) DEFAULT NULL,
    marital_status VARCHAR(50) DEFAULT NULL,
    emergency_contact VARCHAR(200) DEFAULT NULL,
    emergency_phone VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_sp_staff (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Staff Work History
CREATE TABLE IF NOT EXISTS `staff_work_history` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    company VARCHAR(300) DEFAULT '',
    position VARCHAR(200) DEFAULT '',
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    description TEXT,
    reason_leaving VARCHAR(300) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_wh_staff (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Staff Resignations
CREATE TABLE IF NOT EXISTS `staff_resignations` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    resignation_date DATE DEFAULT NULL,
    last_working_date DATE DEFAULT NULL,
    reason TEXT,
    status VARCHAR(50) DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_sr_staff (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- HR Announcements
CREATE TABLE IF NOT EXISTS `hr_announcements` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(300) NOT NULL,
    content TEXT,
    priority VARCHAR(50) DEFAULT 'Normal',
    is_active TINYINT(1) DEFAULT 1,
    created_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Employee Training (enrollments)
CREATE TABLE IF NOT EXISTS `employee_training` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    training_id INT NOT NULL,
    staff_id INT NOT NULL,
    status VARCHAR(50) DEFAULT 'Enrolled',
    completion_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_et_staff (staff_id),
    KEY idx_et_training (training_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Performance Indicators
CREATE TABLE IF NOT EXISTS `performance_indicators` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    indicator_name VARCHAR(300) NOT NULL,
    indicator_category VARCHAR(200) DEFAULT '',
    target_value DECIMAL(14,2) DEFAULT 0,
    current_value DECIMAL(14,2) DEFAULT 0,
    unit VARCHAR(50) DEFAULT '',
    period VARCHAR(50) DEFAULT '',
    department VARCHAR(200) DEFAULT '',
    status VARCHAR(50) DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Promotion Recommendations
CREATE TABLE IF NOT EXISTS `promotion_recommendations` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    current_position VARCHAR(200) DEFAULT '',
    recommended_position VARCHAR(200) DEFAULT '',
    reason TEXT,
    recommended_by INT DEFAULT 0,
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_pr_staff (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- IT Infrastructure
CREATE TABLE IF NOT EXISTS `it_infrastructure` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_name VARCHAR(300) NOT NULL,
    asset_type VARCHAR(100) DEFAULT '',
    serial_number VARCHAR(100) DEFAULT '',
    location VARCHAR(200) DEFAULT '',
    status VARCHAR(50) DEFAULT 'Active',
    assigned_to VARCHAR(200) DEFAULT '',
    purchase_date DATE DEFAULT NULL,
    warranty_expiry DATE DEFAULT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sickbay Settings
CREATE TABLE IF NOT EXISTS `sickbay_settings` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_ss_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Scholarships
CREATE TABLE IF NOT EXISTS `scholarships` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    amount DECIMAL(12,2) DEFAULT 0,
    eligibility TEXT,
    status VARCHAR(20) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sponsorships
CREATE TABLE IF NOT EXISTS `sponsorships` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sponsor_name VARCHAR(255) NOT NULL,
    student_id INT DEFAULT NULL,
    amount DECIMAL(12,2) DEFAULT 0,
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Department Reviews
CREATE TABLE IF NOT EXISTS `department_reviews` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department VARCHAR(200) NOT NULL,
    reviewer_id INT DEFAULT 0,
    review_period VARCHAR(50) DEFAULT '',
    overall_score DECIMAL(5,2) DEFAULT 0,
    strengths TEXT,
    weaknesses TEXT,
    recommendations TEXT,
    status ENUM('draft','submitted','reviewed') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student Counseling Sessions (staff-side)
CREATE TABLE IF NOT EXISTS `student_counseling_sessions` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    student_name VARCHAR(200) DEFAULT '',
    session_type VARCHAR(100) DEFAULT '',
    session_date DATE DEFAULT NULL,
    session_time TIME DEFAULT NULL,
    counselor_id INT DEFAULT 0,
    counselor_name VARCHAR(200) DEFAULT '',
    notes TEXT,
    action_plan TEXT,
    status VARCHAR(50) DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_scs_student (student_id),
    KEY idx_scs_date (session_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student Health Incidents (staff-side)
CREATE TABLE IF NOT EXISTS `student_health_incidents` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    incident_type VARCHAR(100) DEFAULT '',
    description TEXT,
    severity VARCHAR(50) DEFAULT 'Medium',
    action_taken TEXT,
    resolved TINYINT(1) DEFAULT 0,
    resolved_date DATE DEFAULT NULL,
    recorded_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_shi_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Counseling Records (staff-side, matrons)
CREATE TABLE IF NOT EXISTS `counseling_records` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    session_date DATE NOT NULL,
    counselor_name VARCHAR(255) NOT NULL,
    session_type VARCHAR(100) DEFAULT '',
    notes TEXT,
    action_plan TEXT,
    created_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Group Counseling (staff-side, matrons)
CREATE TABLE IF NOT EXISTS `group_counseling` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    topic VARCHAR(255) NOT NULL,
    counselor VARCHAR(255) NOT NULL,
    participants_count INT DEFAULT 0,
    session_date DATE NOT NULL,
    notes TEXT,
    created_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student Referrals (staff-side, matrons)
CREATE TABLE IF NOT EXISTS `student_referrals` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    referral_type VARCHAR(100) NOT NULL,
    reason TEXT NOT NULL,
    referred_to VARCHAR(255) DEFAULT '',
    urgency VARCHAR(50) DEFAULT 'Medium',
    status VARCHAR(50) DEFAULT 'Pending',
    created_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_sref_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student Health Records (staff-side, matrons)
CREATE TABLE IF NOT EXISTS `student_health_records` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    condition_name VARCHAR(255) NOT NULL,
    diagnosis TEXT,
    treatment TEXT,
    medication TEXT,
    recorded_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_shr_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student Medications (staff-side, matrons)
CREATE TABLE IF NOT EXISTS `student_medications` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    medication_name VARCHAR(255) NOT NULL,
    dosage VARCHAR(100) DEFAULT '',
    frequency VARCHAR(100) DEFAULT '',
    start_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Active',
    recorded_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_sm_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Emergency Records (staff-side, matrons)
CREATE TABLE IF NOT EXISTS `emergency_records` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    emergency_type VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    location VARCHAR(255) DEFAULT '',
    action_taken TEXT,
    reported_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_er_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Discipline Cases (staff-side, matrons)
CREATE TABLE IF NOT EXISTS `discipline_cases` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    incident_type VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    incident_date DATE DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Open',
    reported_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_dc_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Disciplinary Actions (staff-side, matrons)
CREATE TABLE IF NOT EXISTS `disciplinary_actions` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id INT NOT NULL,
    action_type VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    taken_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_da_case (case_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Behavior Reports (staff-side, matrons)
CREATE TABLE IF NOT EXISTS `behavior_reports` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    behavior_type VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    report_date DATE DEFAULT NULL,
    recorded_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_br_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Parent Meetings (staff-side, matrons)
CREATE TABLE IF NOT EXISTS `parent_meetings` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    parent_name VARCHAR(255) NOT NULL,
    meeting_date DATE NOT NULL,
    topic VARCHAR(255) NOT NULL,
    outcome TEXT,
    recorded_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_pm_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Hostel Activities (staff-side, matrons)
CREATE TABLE IF NOT EXISTS `hostel_activities` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    activity_name VARCHAR(255) NOT NULL,
    description TEXT,
    activity_date DATE NOT NULL,
    location VARCHAR(255) DEFAULT '',
    status VARCHAR(50) DEFAULT 'Planned',
    created_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activity Schedules (staff-side, matrons)
CREATE TABLE IF NOT EXISTS `activity_schedules` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    activity_id INT NOT NULL,
    schedule_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    created_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_as_activity (activity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activity Participation (staff-side, matrons)
CREATE TABLE IF NOT EXISTS `activity_participation` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    activity_id INT NOT NULL,
    student_id INT NOT NULL,
    status VARCHAR(50) DEFAULT 'Registered',
    registered_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ap_activity (activity_id),
    KEY idx_ap_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Room Assignments (staff-side, matrons)
CREATE TABLE IF NOT EXISTS `room_assignments` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    room_number VARCHAR(50) NOT NULL,
    bed_number VARCHAR(50) DEFAULT '',
    hostel VARCHAR(100) NOT NULL,
    status VARCHAR(50) DEFAULT 'Active',
    assigned_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ra_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Room Inspections (staff-side, matrons)
CREATE TABLE IF NOT EXISTS `room_inspections` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(50) NOT NULL,
    inspector VARCHAR(255) NOT NULL,
    inspection_date DATE NOT NULL,
    score INT DEFAULT 0,
    notes TEXT,
    created_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Maintenance Requests (staff-side, matrons)
CREATE TABLE IF NOT EXISTS `maintenance_requests` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(50) NOT NULL,
    issue TEXT NOT NULL,
    priority VARCHAR(50) DEFAULT 'Medium',
    reported_by VARCHAR(255) DEFAULT '',
    status VARCHAR(50) DEFAULT 'Pending',
    created_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Nursing Skills Training
CREATE TABLE IF NOT EXISTS `nursing_skills_training` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    skill_name VARCHAR(300) NOT NULL,
    category VARCHAR(200) DEFAULT '',
    description TEXT,
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Nursing Practical Assessments
CREATE TABLE IF NOT EXISTS `nursing_practical_assessment` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    skill_id INT NOT NULL,
    assessment_date DATE DEFAULT NULL,
    score DECIMAL(8,2) DEFAULT 0,
    grade VARCHAR(10) DEFAULT '',
    assessor VARCHAR(200) DEFAULT '',
    comments TEXT,
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_npa_student (student_id),
    KEY idx_npa_skill (skill_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Course Evaluations (staff-side)
CREATE TABLE IF NOT EXISTS `course_evaluations` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lecturer_id INT DEFAULT 0,
    course_id VARCHAR(100) DEFAULT '',
    semester VARCHAR(20) DEFAULT '',
    feedback TEXT,
    rating INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Course Syllabi (staff-side)
CREATE TABLE IF NOT EXISTS `course_syllabi` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lecturer_id INT DEFAULT 0,
    course_name VARCHAR(255) DEFAULT '',
    semester VARCHAR(20) DEFAULT '',
    topics TEXT,
    learning_outcomes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Course Prerequisites
CREATE TABLE IF NOT EXISTS `course_prerequisites` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(50) NOT NULL,
    prerequisite_code VARCHAR(50) NOT NULL,
    program_code VARCHAR(50) DEFAULT '',
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_cp_course (course_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student Requirements (staff-side, requirement portal)
CREATE TABLE IF NOT EXISTS `student_requirements` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    student_name VARCHAR(200) DEFAULT NULL,
    registration_number VARCHAR(50) DEFAULT NULL,
    requirement_type VARCHAR(100) NOT NULL,
    status ENUM('pending','submitted','verified','rejected') DEFAULT 'pending',
    verified_by INT DEFAULT NULL,
    verified_date DATE DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sreq_student (student_id),
    INDEX idx_sreq_type (requirement_type),
    INDEX idx_sreq_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Grading Approval Workflow Log (staff-side)
CREATE TABLE IF NOT EXISTS `grading_approval_workflow_log` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workflow_id INT NOT NULL,
    stage VARCHAR(100) DEFAULT '',
    action VARCHAR(200) DEFAULT '',
    comments TEXT,
    actor_id INT DEFAULT 0,
    actor_name VARCHAR(200) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_gawl_workflow (workflow_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Budgets (staff-side, bursar)
CREATE TABLE IF NOT EXISTS `budgets` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    budget_name VARCHAR(300) NOT NULL,
    total_amount DECIMAL(14,2) DEFAULT 0,
    spent_amount DECIMAL(14,2) DEFAULT 0,
    fiscal_year VARCHAR(20) DEFAULT '',
    department VARCHAR(200) DEFAULT '',
    status VARCHAR(50) DEFAULT 'Draft',
    created_by INT DEFAULT 0,
    approved_by INT DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payroll Approvals
CREATE TABLE IF NOT EXISTS `payroll_approvals` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    budget_id INT DEFAULT 0,
    request_type VARCHAR(50) DEFAULT '',
    requested_by INT DEFAULT 0,
    amount DECIMAL(14,2) DEFAULT 0,
    description TEXT,
    status ENUM('pending','approved','rejected','changes_requested','escalated') DEFAULT 'pending',
    approver_id INT DEFAULT 0,
    approver_name VARCHAR(200) DEFAULT '',
    approver_comments TEXT,
    escalated_to INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- System Logs
CREATE TABLE IF NOT EXISTS `system_logs` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT 0,
    action VARCHAR(200) NOT NULL,
    entity_type VARCHAR(100) DEFAULT '',
    entity_id INT DEFAULT 0,
    description TEXT,
    ip_address VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_sl_action (action),
    KEY idx_sl_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- System Settings
CREATE TABLE IF NOT EXISTS `system_settings` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_name VARCHAR(100) NOT NULL,
    setting_value TEXT,
    setting_type VARCHAR(50) DEFAULT 'string',
    description VARCHAR(500) DEFAULT NULL,
    updated_by INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_sys_set (setting_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Computer Repairs (ICT)
CREATE TABLE IF NOT EXISTS `computer_repairs` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    computer_id INT DEFAULT 0,
    issue_description TEXT NOT NULL,
    priority VARCHAR(50) DEFAULT 'medium',
    status VARCHAR(50) DEFAULT 'open',
    reported_by VARCHAR(200) DEFAULT '',
    reported_date DATE DEFAULT NULL,
    resolved_date DATE DEFAULT NULL,
    resolution_notes TEXT,
    cost DECIMAL(14,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- IT Support Tickets
CREATE TABLE IF NOT EXISTS `it_support_tickets` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_number VARCHAR(50) NOT NULL,
    requester_name VARCHAR(200) DEFAULT '',
    requester_email VARCHAR(100) DEFAULT '',
    requester_type VARCHAR(50) DEFAULT '',
    issue_type VARCHAR(100) DEFAULT '',
    priority VARCHAR(50) DEFAULT 'medium',
    description TEXT,
    status VARCHAR(50) DEFAULT 'open',
    assigned_to INT DEFAULT NULL,
    resolution_notes TEXT,
    resolved_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_ist_status (status),
    KEY idx_ist_priority (priority)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Lab Rooms (ICT)
CREATE TABLE IF NOT EXISTS `lab_rooms` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_name VARCHAR(200) NOT NULL,
    location VARCHAR(200) DEFAULT '',
    capacity INT DEFAULT 0,
    status VARCHAR(50) DEFAULT 'active',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student ID Cards (ICT)
CREATE TABLE IF NOT EXISTS `student_id_cards` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    card_number VARCHAR(50) NOT NULL,
    card_type VARCHAR(50) DEFAULT 'student',
    status VARCHAR(50) DEFAULT 'active',
    issued_date DATE DEFAULT NULL,
    expiry_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_sic_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admissions: Applicants
CREATE TABLE IF NOT EXISTS `applicants` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_number VARCHAR(30) NOT NULL UNIQUE,
    student_number VARCHAR(50) DEFAULT NULL,
    registration_number VARCHAR(50) DEFAULT NULL,
    full_name VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) DEFAULT NULL,
    middle_name VARCHAR(100) DEFAULT NULL,
    surname VARCHAR(100) DEFAULT NULL,
    gender ENUM('Male','Female','Other') DEFAULT NULL,
    date_of_birth DATE DEFAULT NULL,
    email VARCHAR(100) DEFAULT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    nationality VARCHAR(100) DEFAULT 'Ugandan',
    district VARCHAR(100) DEFAULT NULL,
    religion VARCHAR(50) DEFAULT NULL,
    address TEXT DEFAULT NULL,
    program_id INT DEFAULT NULL,
    intake VARCHAR(50) DEFAULT NULL,
    application_source ENUM('Online','Manual','Walk-in','Referral','Other') DEFAULT 'Online',
    status ENUM('New','Under Review','Waiting for Documents','Requirements Verified','Interview Scheduled','Approved','Rejected','Registered','Withdrawn') NOT NULL DEFAULT 'New',
    rejection_reason TEXT DEFAULT NULL,
    guardian_name VARCHAR(200) DEFAULT NULL,
    guardian_phone VARCHAR(20) DEFAULT NULL,
    emergency_contact_name VARCHAR(100) DEFAULT NULL,
    emergency_contact_phone VARCHAR(20) DEFAULT NULL,
    submitted_at TIMESTAMP NULL DEFAULT NULL,
    reviewed_by INT DEFAULT NULL,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    approved_by INT DEFAULT NULL,
    approved_at TIMESTAMP NULL DEFAULT NULL,
    registered_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admissions: Admission Tracking
CREATE TABLE IF NOT EXISTS `student_admission_tracking` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_number VARCHAR(50) DEFAULT NULL,
    application_number VARCHAR(30) NOT NULL,
    applicant_id INT DEFAULT NULL,
    program VARCHAR(255) DEFAULT NULL,
    intake VARCHAR(50) DEFAULT NULL,
    admission_date DATE DEFAULT NULL,
    admission_status ENUM('Pending','Under Review','Requirements Pending','Approved','Rejected','Registered') NOT NULL DEFAULT 'Pending',
    requirements_total INT NOT NULL DEFAULT 0,
    requirements_completed INT NOT NULL DEFAULT 0,
    documents_uploaded INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_track_app(application_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admissions: Activity Logs
CREATE TABLE IF NOT EXISTS `admission_activity_logs` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT DEFAULT NULL,
    user_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_log_app(applicant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admissions: Requirements
CREATE TABLE IF NOT EXISTS `admission_requirements` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requirement_name VARCHAR(255) NOT NULL,
    type ENUM('Document','Certificate','ID','Photo','Form','Other') NOT NULL DEFAULT 'Document',
    display_order INT NOT NULL DEFAULT 0,
    is_mandatory TINYINT(1) NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admissions: Applicant Requirement Status
CREATE TABLE IF NOT EXISTS `applicant_requirement_status` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    requirement_id INT NOT NULL,
    status ENUM('Not Submitted','Pending','Submitted','Verified','Rejected','Missing','Received','Not Yet Given') NOT NULL DEFAULT 'Not Submitted',
    remarks TEXT DEFAULT NULL,
    submitted_by INT DEFAULT NULL,
    submitted_at TIMESTAMP NULL DEFAULT NULL,
    verified_by INT DEFAULT NULL,
    verified_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_app_req(applicant_id,requirement_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admissions: Student Documents
CREATE TABLE IF NOT EXISTS `student_documents` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    requirement_id INT DEFAULT NULL,
    document_name VARCHAR(255) NOT NULL,
    document_type VARCHAR(100) DEFAULT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT DEFAULT NULL,
    verification_status ENUM('Pending','Verified','Rejected') NOT NULL DEFAULT 'Pending',
    verification_remarks TEXT DEFAULT NULL,
    verified_by INT DEFAULT NULL,
    verified_at TIMESTAMP NULL DEFAULT NULL,
    document_status ENUM('Active','Deleted') NOT NULL DEFAULT 'Active',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_doc_app(applicant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admissions: Requirement History
CREATE TABLE IF NOT EXISTS `requirement_history` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    requirement_id INT DEFAULT NULL,
    action VARCHAR(100) NOT NULL,
    previous_status VARCHAR(50) DEFAULT NULL,
    new_status VARCHAR(50) DEFAULT NULL,
    performed_by INT DEFAULT NULL,
    remarks TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_rh_app(applicant_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================================
-- SECTION 2B: igangaschool_students - Additional tables from audit
-- ================================================================

-- Student Profiles
CREATE TABLE IF NOT EXISTS `student_profiles` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    profile_type ENUM('Academic','Finance','Library','Attendance','Medical','Requirements','General') NOT NULL DEFAULT 'General',
    profile_data LONGTEXT DEFAULT NULL,
    status ENUM('Active','Inactive','Suspended') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_profile_student_type (student_id, profile_type),
    KEY idx_sp_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student Financial Profiles
CREATE TABLE IF NOT EXISTS `student_financial_profiles` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    total_fees DECIMAL(14,2) DEFAULT 0.00,
    total_paid DECIMAL(14,2) DEFAULT 0.00,
    balance DECIMAL(14,2) DEFAULT 0.00,
    status ENUM('pending','partial','paid','overdue') DEFAULT 'pending',
    academic_year VARCHAR(20) DEFAULT NULL,
    semester VARCHAR(20) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_sfp_student (student_id),
    KEY idx_sfp_student (student_id),
    KEY idx_sfp_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student Academic Profiles
CREATE TABLE IF NOT EXISTS `student_academic_profiles` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    current_program VARCHAR(200) DEFAULT NULL,
    current_year INT DEFAULT 1,
    current_semester VARCHAR(20) DEFAULT NULL,
    academic_year VARCHAR(20) DEFAULT NULL,
    gpa DECIMAL(5,2) DEFAULT NULL,
    cumulative_gpa DECIMAL(5,2) DEFAULT NULL,
    total_credits INT DEFAULT 0,
    earned_credits INT DEFAULT 0,
    credit_hours_earned INT DEFAULT 0,
    total_credit_hours INT DEFAULT 0,
    academic_standing VARCHAR(100) DEFAULT 'Good Standing',
    advisor_name VARCHAR(200) DEFAULT NULL,
    advisor_email VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_sap_student (student_id),
    KEY idx_sap_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student Medical Profiles
CREATE TABLE IF NOT EXISTS `student_medical_profiles` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    blood_group VARCHAR(10) DEFAULT NULL,
    medical_conditions TEXT DEFAULT NULL,
    allergies TEXT DEFAULT NULL,
    disability TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_smp_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student Requirements Status
CREATE TABLE IF NOT EXISTS `student_requirements_status` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    requirement_id INT NOT NULL,
    status ENUM('Not Submitted','Pending','Submitted','Verified','Rejected','Missing','Received','Not Yet Given') NOT NULL DEFAULT 'Not Submitted',
    document_path VARCHAR(500) DEFAULT NULL,
    document_name VARCHAR(255) DEFAULT NULL,
    remarks TEXT DEFAULT NULL,
    verified_by INT DEFAULT NULL,
    verified_by_name VARCHAR(200) DEFAULT NULL,
    verified_at TIMESTAMP NULL DEFAULT NULL,
    submission_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_srs_student_req (student_id, requirement_id),
    KEY idx_srs_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student Status History
CREATE TABLE IF NOT EXISTS `student_status_history` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    status_type VARCHAR(50) NOT NULL,
    old_value VARCHAR(100) DEFAULT NULL,
    new_value VARCHAR(100) DEFAULT NULL,
    changed_by INT DEFAULT NULL,
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ssh_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student Timetables
CREATE TABLE IF NOT EXISTS `student_timetables` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_code VARCHAR(50) DEFAULT '',
    course_name VARCHAR(300) DEFAULT '',
    day_of_week VARCHAR(20) NOT NULL,
    start_time TIME DEFAULT NULL,
    end_time TIME DEFAULT NULL,
    room VARCHAR(100) DEFAULT '',
    lecturer_name VARCHAR(200) DEFAULT '',
    academic_year VARCHAR(20) DEFAULT NULL,
    semester VARCHAR(20) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_st_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student Semester GPA
CREATE TABLE IF NOT EXISTS `student_semester_gpa` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    academic_year VARCHAR(20) DEFAULT NULL,
    semester VARCHAR(20) DEFAULT NULL,
    semester_gpa DECIMAL(5,2) DEFAULT 0.00,
    cumulative_gpa DECIMAL(5,2) DEFAULT 0.00,
    credits_earned INT DEFAULT 0,
    academic_standing VARCHAR(100) DEFAULT 'Good Standing',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_ssg_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student Fee Tracking
CREATE TABLE IF NOT EXISTS `student_fee_tracking` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    fee_type VARCHAR(100) DEFAULT '',
    description VARCHAR(300) DEFAULT '',
    amount DECIMAL(14,2) DEFAULT 0,
    amount_paid DECIMAL(14,2) DEFAULT 0,
    balance DECIMAL(14,2) DEFAULT 0,
    due_date DATE DEFAULT NULL,
    semester VARCHAR(20) DEFAULT NULL,
    academic_year VARCHAR(20) DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_sft_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student Fee Accounts
CREATE TABLE IF NOT EXISTS `student_fee_accounts` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    fee_type VARCHAR(100) DEFAULT '',
    description VARCHAR(300) DEFAULT '',
    amount DECIMAL(14,2) DEFAULT 0,
    amount_paid DECIMAL(14,2) DEFAULT 0,
    balance DECIMAL(14,2) DEFAULT 0,
    due_date DATE DEFAULT NULL,
    semester VARCHAR(20) DEFAULT NULL,
    academic_year VARCHAR(20) DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_sfa_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student Fee Assignments
CREATE TABLE IF NOT EXISTS `student_fee_assignments` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    fee_structure_id INT DEFAULT 0,
    amount DECIMAL(14,2) DEFAULT 0,
    amount_paid DECIMAL(14,2) DEFAULT 0,
    status VARCHAR(50) DEFAULT 'Pending',
    academic_year VARCHAR(20) DEFAULT NULL,
    semester VARCHAR(20) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_sfa2_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student Fees
CREATE TABLE IF NOT EXISTS `student_fees` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    fee_type VARCHAR(100) DEFAULT '',
    description VARCHAR(300) DEFAULT '',
    amount DECIMAL(14,2) DEFAULT 0,
    paid_amount DECIMAL(14,2) DEFAULT 0,
    due_date DATE DEFAULT NULL,
    semester VARCHAR(20) DEFAULT NULL,
    academic_year VARCHAR(20) DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_sf_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student Course Registrations
CREATE TABLE IF NOT EXISTS `student_course_registrations` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT DEFAULT 0,
    course_code VARCHAR(50) DEFAULT '',
    course_name VARCHAR(300) DEFAULT '',
    semester VARCHAR(20) DEFAULT NULL,
    academic_year VARCHAR(20) DEFAULT NULL,
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_scr_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student Competencies
CREATE TABLE IF NOT EXISTS `student_competencies` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    skill_category VARCHAR(200) DEFAULT '',
    skill_name VARCHAR(300) DEFAULT '',
    proficiency_level VARCHAR(50) DEFAULT 'Beginner',
    assessed_by INT DEFAULT 0,
    assessment_date DATE DEFAULT NULL,
    notes TEXT,
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_sc_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student Emergency Contacts
CREATE TABLE IF NOT EXISTS `student_emergency_contacts` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    contact_name VARCHAR(200) NOT NULL,
    relationship VARCHAR(100) DEFAULT '',
    phone VARCHAR(50) DEFAULT '',
    email VARCHAR(100) DEFAULT '',
    address TEXT,
    is_primary TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_sec_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student Logbook
CREATE TABLE IF NOT EXISTS `student_logbook` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    entry_date DATE DEFAULT NULL,
    entry_type VARCHAR(100) DEFAULT '',
    description TEXT,
    hours DECIMAL(5,2) DEFAULT 0,
    supervisor VARCHAR(200) DEFAULT '',
    status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_slb_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student Downloads
CREATE TABLE IF NOT EXISTS `student_downloads` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT DEFAULT 0,
    file_name VARCHAR(300) NOT NULL,
    file_type VARCHAR(100) DEFAULT '',
    file_size INT DEFAULT 0,
    description TEXT,
    uploaded_by INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student Login Attempts
CREATE TABLE IF NOT EXISTS `student_login_attempts` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    ip_address VARCHAR(50) DEFAULT '',
    success TINYINT(1) DEFAULT 0,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_sla_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student Requests
CREATE TABLE IF NOT EXISTS `student_requests` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    request_type VARCHAR(100) DEFAULT '',
    reason TEXT,
    status VARCHAR(50) DEFAULT 'Pending',
    response TEXT,
    responded_by INT DEFAULT 0,
    responded_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_srq_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student Messages
CREATE TABLE IF NOT EXISTS `student_messages` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    sender VARCHAR(200) DEFAULT 'System',
    subject VARCHAR(300) DEFAULT '',
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_sm_msg_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student Warnings
CREATE TABLE IF NOT EXISTS `student_warnings` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    warning_type VARCHAR(100) DEFAULT '',
    reason TEXT,
    warning_date DATE DEFAULT NULL,
    issued_by INT DEFAULT 0,
    status VARCHAR(50) DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_sw_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Student Academic Records (portal-side)
CREATE TABLE IF NOT EXISTS `student_academic_records` (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_code VARCHAR(50) DEFAULT '',
    course_name VARCHAR(300) DEFAULT '',
    assessment_type VARCHAR(100) DEFAULT '',
    marks DECIMAL(8,2) DEFAULT 0,
    grade VARCHAR(10) DEFAULT '',
    gpa DECIMAL(4,2) DEFAULT 0,
    graded_by INT DEFAULT 0,
    academic_year VARCHAR(20) DEFAULT NULL,
    semester VARCHAR(20) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_sar_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
