-- ============================================================
-- ISNM COMPLETE HR MANAGEMENT SYSTEM
-- Comprehensive SQL Schema with all required features
-- ============================================================

USE igangaschoolofl_staffs_db;

-- ============================================================
-- 1. HR USER ACCOUNTS & AUTHENTICATION
-- ============================================================

CREATE TABLE IF NOT EXISTS hr_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('hr_manager', 'hr_assistant', 'director', 'head_of_department', 'payroll_officer') DEFAULT 'hr_manager',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status)
);

-- ============================================================
-- 2. STAFF RECORDS MANAGEMENT
-- ============================================================

CREATE TABLE IF NOT EXISTS staff_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) UNIQUE NOT NULL,
    category_code VARCHAR(20),
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS staff_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(20) UNIQUE NOT NULL,
    staff_code VARCHAR(20),
    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    date_of_birth DATE,
    gender ENUM('male', 'female', 'other'),
    email VARCHAR(255),
    phone_primary VARCHAR(20),
    phone_secondary VARCHAR(20),
    national_id VARCHAR(50),
    passport_number VARCHAR(50),
    marital_status ENUM('single', 'married', 'divorced', 'widowed'),
    home_address TEXT,
    residential_address TEXT,
    city VARCHAR(100),
    district VARCHAR(100),
    country VARCHAR(100),
    next_of_kin_name VARCHAR(255),
    next_of_kin_phone VARCHAR(20),
    next_of_kin_relationship VARCHAR(50),
    profile_photo VARCHAR(255),
    status ENUM('active', 'on_leave', 'suspended', 'retired', 'resigned') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_staff_id (staff_id),
    INDEX idx_email (email),
    INDEX idx_status (status)
);

CREATE TABLE IF NOT EXISTS employment_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    job_title VARCHAR(255) NOT NULL,
    job_category VARCHAR(100),
    department VARCHAR(100) NOT NULL,
    sub_department VARCHAR(100),
    staff_category_id INT,
    employment_type ENUM('permanent', 'contract', 'temporary', 'part_time') DEFAULT 'permanent',
    grade VARCHAR(20),
    salary_grade VARCHAR(20),
    reports_to INT,
    employment_start_date DATE NOT NULL,
    employment_end_date DATE,
    contract_start_date DATE,
    contract_end_date DATE,
    contract_renewal_date DATE,
    office_location VARCHAR(255),
    office_contact VARCHAR(20),
    professional_license VARCHAR(100),
    license_expiry_date DATE,
    license_issuing_body VARCHAR(255),
    nursing_council_number VARCHAR(50),
    council_number_expiry DATE,
    qualification_level VARCHAR(100),
    specialization VARCHAR(255),
    years_of_experience INT,
    previous_employer VARCHAR(255),
    previous_position VARCHAR(255),
    reason_for_leaving TEXT,
    status ENUM('active', 'inactive', 'on_leave', 'suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_staff_id (staff_id),
    INDEX idx_department (department),
    INDEX idx_status (status),
    FOREIGN KEY (staff_id) REFERENCES staff_records(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_category_id) REFERENCES staff_categories(id)
);

CREATE TABLE IF NOT EXISTS staff_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    document_type VARCHAR(100) NOT NULL,
    document_name VARCHAR(255),
    file_path VARCHAR(255),
    file_type VARCHAR(50),
    file_size INT,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expiry_date DATE,
    status ENUM('active', 'expired', 'archived') DEFAULT 'active',
    notes TEXT,
    INDEX idx_staff_id (staff_id),
    FOREIGN KEY (staff_id) REFERENCES staff_records(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS staff_qualifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    qualification_name VARCHAR(255) NOT NULL,
    qualification_level VARCHAR(100),
    field_of_study VARCHAR(255),
    institution_name VARCHAR(255),
    completion_date DATE,
    certificate_number VARCHAR(50),
    grade VARCHAR(10),
    certificate_file VARCHAR(255),
    status ENUM('verified', 'pending', 'expired') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_staff_id (staff_id),
    FOREIGN KEY (staff_id) REFERENCES staff_records(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS work_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    position_title VARCHAR(255) NOT NULL,
    department VARCHAR(100),
    start_date DATE NOT NULL,
    end_date DATE,
    supervisor_name VARCHAR(255),
    key_achievements TEXT,
    reason_for_transfer_or_departure TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_staff_id (staff_id),
    FOREIGN KEY (staff_id) REFERENCES staff_records(id) ON DELETE CASCADE
);

-- ============================================================
-- 3. RECRUITMENT & HIRING
-- ============================================================

CREATE TABLE IF NOT EXISTS job_vacancies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vacancy_code VARCHAR(50) UNIQUE NOT NULL,
    job_title VARCHAR(255) NOT NULL,
    department VARCHAR(100) NOT NULL,
    position_type ENUM('internal', 'external') DEFAULT 'external',
    number_of_positions INT NOT NULL,
    job_description LONGTEXT,
    required_qualifications TEXT,
    required_experience TEXT,
    salary_range_min DECIMAL(15,2),
    salary_range_max DECIMAL(15,2),
    salary_currency VARCHAR(3) DEFAULT 'UGX',
    posting_date DATE NOT NULL,
    closing_date DATE NOT NULL,
    job_benefits TEXT,
    reporting_to VARCHAR(255),
    location VARCHAR(255),
    status ENUM('open', 'closed', 'filled', 'cancelled') DEFAULT 'open',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_closing_date (closing_date)
);

CREATE TABLE IF NOT EXISTS job_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_number VARCHAR(50) UNIQUE NOT NULL,
    vacancy_id INT NOT NULL,
    applicant_first_name VARCHAR(100) NOT NULL,
    applicant_last_name VARCHAR(100) NOT NULL,
    applicant_email VARCHAR(255) NOT NULL,
    applicant_phone VARCHAR(20),
    application_date DATE NOT NULL,
    cv_file VARCHAR(255),
    cover_letter TEXT,
    qualifications TEXT,
    years_of_experience INT,
    current_employer VARCHAR(255),
    notice_period_days INT,
    application_status ENUM('received', 'reviewing', 'shortlisted', 'rejected', 'interviewed', 'offered', 'hired', 'withdrawn') DEFAULT 'received',
    shortlist_date DATE,
    interview_date DATE,
    interview_feedback TEXT,
    interview_score DECIMAL(5,2),
    offer_date DATE,
    offer_accepted BOOLEAN,
    joining_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_vacancy (vacancy_id),
    INDEX idx_status (application_status),
    INDEX idx_email (applicant_email),
    FOREIGN KEY (vacancy_id) REFERENCES job_vacancies(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS interview_scheduling (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL,
    interview_round INT NOT NULL,
    interview_type ENUM('phone', 'panel', 'technical', 'final') DEFAULT 'panel',
    interview_date DATE NOT NULL,
    interview_time TIME,
    interview_location VARCHAR(255),
    interview_panel LONGTEXT,
    interview_questions TEXT,
    conducted_by INT,
    interview_notes TEXT,
    interview_score DECIMAL(5,2),
    recommendation ENUM('proceed', 'reject', 'hold') DEFAULT 'hold',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_application (application_id),
    FOREIGN KEY (application_id) REFERENCES job_applications(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS job_offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    offer_number VARCHAR(50) UNIQUE NOT NULL,
    application_id INT NOT NULL,
    staff_id INT,
    offer_date DATE NOT NULL,
    job_title VARCHAR(255) NOT NULL,
    department VARCHAR(100),
    salary_offered DECIMAL(15,2),
    salary_currency VARCHAR(3) DEFAULT 'UGX',
    contract_type VARCHAR(50),
    contract_duration_months INT,
    start_date DATE NOT NULL,
    benefits_details TEXT,
    employment_terms TEXT,
    offer_status ENUM('sent', 'accepted', 'rejected', 'withdrawn') DEFAULT 'sent',
    acceptance_date DATE,
    response_deadline DATE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_application (application_id),
    FOREIGN KEY (application_id) REFERENCES job_applications(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff_records(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS onboarding_checklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    checklist_item VARCHAR(255) NOT NULL,
    item_category VARCHAR(100),
    is_completed BOOLEAN DEFAULT FALSE,
    completed_date TIMESTAMP NULL,
    completed_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_staff_id (staff_id),
    FOREIGN KEY (staff_id) REFERENCES staff_records(id) ON DELETE CASCADE
);

-- ============================================================
-- 4. ATTENDANCE & TIME MANAGEMENT
-- ============================================================

CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    check_in_time TIME,
    check_out_time TIME,
    total_hours DECIMAL(5,2),
    attendance_status ENUM('present', 'absent', 'late', 'half_day', 'on_leave') DEFAULT 'present',
    remarks TEXT,
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_attendance (staff_id, attendance_date),
    INDEX idx_staff_id (staff_id),
    INDEX idx_attendance_date (attendance_date),
    INDEX idx_status (attendance_status),
    FOREIGN KEY (staff_id) REFERENCES staff_records(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS leave_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    leave_type_name VARCHAR(100) UNIQUE NOT NULL,
    leave_type_code VARCHAR(20),
    days_per_year INT NOT NULL,
    is_paid BOOLEAN DEFAULT TRUE,
    requires_approval BOOLEAN DEFAULT TRUE,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    leave_request_number VARCHAR(50) UNIQUE NOT NULL,
    staff_id INT NOT NULL,
    leave_type_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    number_of_days DECIMAL(5,1),
    reason TEXT,
    emergency_contact_number VARCHAR(20),
    approved_by INT,
    approval_date TIMESTAMP NULL,
    approval_comments TEXT,
    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_staff_id (staff_id),
    INDEX idx_status (status),
    INDEX idx_start_date (start_date),
    FOREIGN KEY (staff_id) REFERENCES staff_records(id) ON DELETE CASCADE,
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id),
    FOREIGN KEY (approved_by) REFERENCES staff_records(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS leave_balance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    leave_type_id INT NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    total_days INT NOT NULL,
    used_days DECIMAL(5,1) DEFAULT 0,
    remaining_days DECIMAL(5,1),
    carried_forward_days DECIMAL(5,1) DEFAULT 0,
    last_updated DATE,
    INDEX idx_staff_id (staff_id),
    INDEX idx_year (academic_year),
    UNIQUE KEY unique_balance (staff_id, leave_type_id, academic_year),
    FOREIGN KEY (staff_id) REFERENCES staff_records(id) ON DELETE CASCADE,
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id)
);

CREATE TABLE IF NOT EXISTS duty_roster (
    id INT AUTO_INCREMENT PRIMARY KEY,
    roster_id VARCHAR(50) UNIQUE NOT NULL,
    staff_id INT NOT NULL,
    department VARCHAR(100),
    duty_date DATE NOT NULL,
    start_time TIME,
    end_time TIME,
    duty_type VARCHAR(100),
    unit_assigned VARCHAR(255),
    supervisor INT,
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_staff_id (staff_id),
    INDEX idx_duty_date (duty_date),
    INDEX idx_department (department),
    FOREIGN KEY (staff_id) REFERENCES staff_records(id) ON DELETE CASCADE
);

-- ============================================================
-- 5. PERFORMANCE MANAGEMENT
-- ============================================================

CREATE TABLE IF NOT EXISTS appraisal_periods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    period_name VARCHAR(100) UNIQUE NOT NULL,
    period_code VARCHAR(20),
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    review_deadline DATE,
    status ENUM('open', 'closed', 'archived') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS performance_indicators (
    id INT AUTO_INCREMENT PRIMARY KEY,
    indicator_code VARCHAR(50) UNIQUE NOT NULL,
    indicator_name VARCHAR(255) NOT NULL,
    indicator_category VARCHAR(100),
    description TEXT,
    measurement_type VARCHAR(50),
    target_value VARCHAR(100),
    weight INT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS staff_appraisals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appraisal_number VARCHAR(50) UNIQUE NOT NULL,
    staff_id INT NOT NULL,
    appraisal_period_id INT NOT NULL,
    appraisee_name VARCHAR(255),
    job_title VARCHAR(255),
    appraiser_id INT,
    appraiser_name VARCHAR(255),
    appraisal_date DATE,
    overall_rating DECIMAL(3,1),
    overall_comments TEXT,
    strengths TEXT,
    areas_for_improvement TEXT,
    goals_achieved TEXT,
    goals_not_achieved TEXT,
    training_recommendations TEXT,
    promotion_recommended BOOLEAN DEFAULT FALSE,
    performance_status ENUM('exceeds_expectations', 'meets_expectations', 'needs_improvement', 'unsatisfactory') DEFAULT 'meets_expectations',
    status ENUM('draft', 'submitted', 'reviewed', 'finalized') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_staff_id (staff_id),
    INDEX idx_period (appraisal_period_id),
    FOREIGN KEY (staff_id) REFERENCES staff_records(id) ON DELETE CASCADE,
    FOREIGN KEY (appraisal_period_id) REFERENCES appraisal_periods(id),
    FOREIGN KEY (appraiser_id) REFERENCES staff_records(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS appraisal_ratings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appraisal_id INT NOT NULL,
    indicator_id INT NOT NULL,
    rating DECIMAL(3,1) NOT NULL,
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_appraisal (appraisal_id),
    FOREIGN KEY (appraisal_id) REFERENCES staff_appraisals(id) ON DELETE CASCADE,
    FOREIGN KEY (indicator_id) REFERENCES performance_indicators(id)
);

-- ============================================================
-- 6. TRAINING & DEVELOPMENT
-- ============================================================

CREATE TABLE IF NOT EXISTS training_programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_code VARCHAR(50) UNIQUE NOT NULL,
    program_name VARCHAR(255) NOT NULL,
    program_category VARCHAR(100),
    description TEXT,
    provider_name VARCHAR(255),
    provider_contact VARCHAR(255),
    target_audience VARCHAR(255),
    program_duration_days INT,
    program_start_date DATE,
    program_end_date DATE,
    venue VARCHAR(255),
    budget_required DECIMAL(15,2),
    status ENUM('planned', 'ongoing', 'completed', 'cancelled') DEFAULT 'planned',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status)
);

CREATE TABLE IF NOT EXISTS staff_training (
    id INT AUTO_INCREMENT PRIMARY KEY,
    training_record_number VARCHAR(50) UNIQUE NOT NULL,
    staff_id INT NOT NULL,
    training_program_id INT,
    training_name VARCHAR(255),
    training_provider VARCHAR(255),
    training_type ENUM('cpd', 'certification', 'workshop', 'conference', 'online_course', 'other') DEFAULT 'cpd',
    training_start_date DATE NOT NULL,
    training_end_date DATE,
    training_hours INT,
    training_cost DECIMAL(15,2),
    certificate_obtained BOOLEAN DEFAULT FALSE,
    certificate_file VARCHAR(255),
    certificate_number VARCHAR(100),
    competency_gained TEXT,
    approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT,
    approved_date TIMESTAMP NULL,
    status ENUM('planned', 'attended', 'completed', 'cancelled') DEFAULT 'planned',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_staff_id (staff_id),
    FOREIGN KEY (staff_id) REFERENCES staff_records(id) ON DELETE CASCADE,
    FOREIGN KEY (training_program_id) REFERENCES training_programs(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS professional_licenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    license_type VARCHAR(255) NOT NULL,
    license_number VARCHAR(100) UNIQUE NOT NULL,
    issuing_body VARCHAR(255),
    issue_date DATE NOT NULL,
    expiry_date DATE NOT NULL,
    license_document VARCHAR(255),
    status ENUM('active', 'expired', 'pending_renewal', 'suspended') DEFAULT 'active',
    renewal_reminder_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_staff_id (staff_id),
    INDEX idx_expiry_date (expiry_date),
    FOREIGN KEY (staff_id) REFERENCES staff_records(id) ON DELETE CASCADE
);

-- ============================================================
-- 7. DISCIPLINARY & CONDUCT
-- ============================================================

CREATE TABLE IF NOT EXISTS incident_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_number VARCHAR(50) UNIQUE NOT NULL,
    staff_id INT NOT NULL,
    incident_date DATE NOT NULL,
    incident_time TIME,
    incident_category VARCHAR(100),
    incident_description LONGTEXT NOT NULL,
    severity ENUM('minor', 'moderate', 'severe') DEFAULT 'moderate',
    witnesses TEXT,
    reported_by INT,
    reported_date DATE,
    investigation_status ENUM('open', 'under_investigation', 'closed') DEFAULT 'open',
    investigation_findings TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_staff_id (staff_id),
    INDEX idx_incident_date (incident_date),
    FOREIGN KEY (staff_id) REFERENCES staff_records(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_by) REFERENCES staff_records(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS disciplinary_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    disciplinary_number VARCHAR(50) UNIQUE NOT NULL,
    staff_id INT NOT NULL,
    incident_id INT,
    action_type ENUM('verbal_warning', 'written_warning', 'final_warning', 'suspension', 'dismissal', 'other') NOT NULL,
    action_date DATE NOT NULL,
    reason TEXT NOT NULL,
    action_description LONGTEXT,
    duration_days INT,
    issued_by INT,
    action_letter_date DATE,
    action_letter_file VARCHAR(255),
    staff_acknowledgment BOOLEAN DEFAULT FALSE,
    acknowledgment_date TIMESTAMP NULL,
    appeal_allowed BOOLEAN DEFAULT FALSE,
    appeal_deadline DATE,
    status ENUM('issued', 'acknowledged', 'appealed', 'closed') DEFAULT 'issued',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_staff_id (staff_id),
    FOREIGN KEY (staff_id) REFERENCES staff_records(id) ON DELETE CASCADE,
    FOREIGN KEY (incident_id) REFERENCES incident_reports(id) ON DELETE SET NULL,
    FOREIGN KEY (issued_by) REFERENCES staff_records(id) ON DELETE SET NULL
);

-- ============================================================
-- 8. CONTRACT & COMPLIANCE
-- ============================================================

CREATE TABLE IF NOT EXISTS employment_contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contract_number VARCHAR(50) UNIQUE NOT NULL,
    staff_id INT NOT NULL,
    contract_start_date DATE NOT NULL,
    contract_end_date DATE NOT NULL,
    contract_type ENUM('permanent', 'fixed_term', 'probation') DEFAULT 'permanent',
    contract_duration_months INT,
    renewal_reminder_date DATE,
    contract_file VARCHAR(255),
    terms_and_conditions TEXT,
    contract_status ENUM('active', 'expiring', 'expired', 'renewed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_staff_id (staff_id),
    INDEX idx_end_date (contract_end_date),
    FOREIGN KEY (staff_id) REFERENCES staff_records(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS compliance_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    compliance_item VARCHAR(255) NOT NULL,
    requirement_type VARCHAR(100),
    required_date DATE,
    completion_date DATE,
    status ENUM('not_started', 'in_progress', 'completed', 'overdue') DEFAULT 'not_started',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_staff_id (staff_id),
    INDEX idx_status (status),
    FOREIGN KEY (staff_id) REFERENCES staff_records(id) ON DELETE CASCADE
);

-- ============================================================
-- 9. PAYROLL SUPPORT
-- ============================================================

CREATE TABLE IF NOT EXISTS salary_structures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    structure_code VARCHAR(50) UNIQUE NOT NULL,
    structure_start_date DATE NOT NULL,
    structure_end_date DATE,
    basic_salary DECIMAL(15,2) NOT NULL,
    housing_allowance DECIMAL(15,2) DEFAULT 0,
    transport_allowance DECIMAL(15,2) DEFAULT 0,
    medical_allowance DECIMAL(15,2) DEFAULT 0,
    other_allowances LONGTEXT,
    total_allowances DECIMAL(15,2) DEFAULT 0,
    gross_salary DECIMAL(15,2),
    nssf_deduction DECIMAL(15,2) DEFAULT 0,
    income_tax_rate DECIMAL(5,2),
    other_deductions LONGTEXT,
    total_deductions DECIMAL(15,2) DEFAULT 0,
    net_salary DECIMAL(15,2),
    bank_account_number VARCHAR(50),
    bank_name VARCHAR(100),
    status ENUM('active', 'inactive', 'archived') DEFAULT 'active',
    approved_by INT,
    approved_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_staff_id (staff_id),
    FOREIGN KEY (staff_id) REFERENCES staff_records(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS payslips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payslip_number VARCHAR(50) UNIQUE NOT NULL,
    staff_id INT NOT NULL,
    salary_month VARCHAR(20) NOT NULL,
    basic_salary DECIMAL(15,2),
    allowances DECIMAL(15,2),
    gross_salary DECIMAL(15,2),
    deductions DECIMAL(15,2),
    net_salary DECIMAL(15,2),
    payment_method ENUM('bank_transfer', 'cash', 'cheque') DEFAULT 'bank_transfer',
    payment_date DATE,
    generated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    viewed_by_employee BOOLEAN DEFAULT FALSE,
    viewed_date TIMESTAMP NULL,
    status ENUM('generated', 'approved', 'paid') DEFAULT 'generated',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_staff_id (staff_id),
    INDEX idx_salary_month (salary_month),
    FOREIGN KEY (staff_id) REFERENCES staff_records(id) ON DELETE CASCADE
);

-- ============================================================
-- 10. COMMUNICATION & NOTIFICATIONS
-- ============================================================

CREATE TABLE IF NOT EXISTS staff_announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    announcement_number VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    announcement_type VARCHAR(100),
    audience VARCHAR(100),
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    announcement_date DATE NOT NULL,
    posted_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_priority (priority),
    INDEX idx_announcement_date (announcement_date)
);

CREATE TABLE IF NOT EXISTS staff_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    notification_type VARCHAR(100),
    title VARCHAR(255),
    message TEXT,
    related_id INT,
    related_type VARCHAR(50),
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    action_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_staff_id (staff_id),
    INDEX idx_is_read (is_read),
    FOREIGN KEY (staff_id) REFERENCES staff_records(id) ON DELETE CASCADE
);

-- ============================================================
-- 11. REPORTS & ANALYTICS
-- ============================================================

CREATE TABLE IF NOT EXISTS hr_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_code VARCHAR(50) UNIQUE NOT NULL,
    report_name VARCHAR(255) NOT NULL,
    report_type ENUM('staff_list', 'attendance_report', 'leave_summary', 'turnover_analysis', 'salary_summary', 'training_report', 'performance_report', 'general_report') NOT NULL,
    report_period_start DATE,
    report_period_end DATE,
    generated_by INT,
    generated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    report_data LONGTEXT,
    pdf_path VARCHAR(255),
    excel_path VARCHAR(255),
    status ENUM('draft', 'finalized', 'archived') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_report_type (report_type)
);

-- ============================================================
-- 12. ACTIVITY LOGS & AUDIT TRAIL
-- ============================================================

CREATE TABLE IF NOT EXISTS hr_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    user_name VARCHAR(255),
    user_role VARCHAR(50),
    action_type VARCHAR(100),
    entity_type VARCHAR(50),
    entity_id INT,
    entity_name VARCHAR(255),
    old_values LONGTEXT,
    new_values LONGTEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    status ENUM('success', 'failure') DEFAULT 'success',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_action_type (action_type),
    INDEX idx_created_at (created_at)
);

-- ============================================================
-- 13. SYSTEM SETTINGS
-- ============================================================

CREATE TABLE IF NOT EXISTS hr_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value LONGTEXT,
    setting_type VARCHAR(50),
    description VARCHAR(255),
    is_editable BOOLEAN DEFAULT TRUE,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- INSERT DEFAULT DATA
-- ============================================================

-- Insert leave types
INSERT IGNORE INTO leave_types (leave_type_name, leave_type_code, days_per_year, is_paid, requires_approval) VALUES
('Annual Leave', 'AL', 20, TRUE, TRUE),
('Sick Leave', 'SL', 10, TRUE, TRUE),
('Maternity Leave', 'ML', 60, TRUE, TRUE),
('Paternity Leave', 'PL', 5, TRUE, TRUE),
('Study Leave', 'STL', 5, FALSE, TRUE),
('Bereavement Leave', 'BL', 3, TRUE, TRUE),
('Unpaid Leave', 'UL', 0, FALSE, TRUE);

-- Insert staff categories
INSERT IGNORE INTO staff_categories (category_name, category_code, description) VALUES
('Teaching Staff', 'TS', 'Nurses, Midwives, Instructors'),
('Administrative', 'AD', 'Office and administrative staff'),
('Support Staff', 'SS', 'Cleaners, drivers, support personnel'),
('Managerial', 'MG', 'Managers, directors, supervisors');

-- Insert appraisal periods
INSERT IGNORE INTO appraisal_periods (period_name, period_code, start_date, end_date, review_deadline) VALUES
('Annual 2025', 'AP2025', '2025-01-01', '2025-12-31', '2026-01-31');

-- Insert performance indicators
INSERT IGNORE INTO performance_indicators (indicator_code, indicator_name, indicator_category, measurement_type, weight) VALUES
('PI001', 'Punctuality', 'Attendance', 'score', 10),
('PI002', 'Work Quality', 'Performance', 'score', 25),
('PI003', 'Teamwork', 'Behavior', 'score', 15),
('PI004', 'Initiative', 'Competency', 'score', 20),
('PI005', 'Communication', 'Skills', 'score', 15),
('PI006', 'Compliance', 'Conduct', 'score', 15);

-- Insert HR user (will be updated with proper password hash)
INSERT IGNORE INTO hr_users (email, password_hash, full_name, role, status) VALUES
('hr@igangaschoolofnursingandmidwifery.ac.ug', 'placeholder', 'HR Manager', 'hr_manager', 'active');

-- Insert default HR settings
INSERT IGNORE INTO hr_settings (setting_key, setting_value, setting_type, description) VALUES
('institution_name', 'Iganga School of Nursing and Midwifery', 'text', 'Institution name'),
('institution_phone', '+256-701-000-000', 'text', 'Institution phone'),
('institution_email', 'hr@igangaschoolofnursingandmidwifery.ac.ug', 'text', 'Institution email'),
('currency', 'UGX', 'text', 'Currency for salaries'),
('payroll_frequency', 'monthly', 'text', 'Payroll processing frequency'),
('banking_integration', 'false', 'boolean', 'Enable banking integration'),
('leave_accrual_method', 'annual', 'text', 'Leave accrual method'),
('contract_renewal_notice_days', '30', 'number', 'Days notice for contract renewal'),
('license_renewal_notice_days', '60', 'number', 'Days notice for license renewal');

COMMIT;
