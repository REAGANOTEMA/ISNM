-- ISNM HR Management Module Schema
-- Import into igangaschoolofl_staffs_db

CREATE TABLE IF NOT EXISTS staff_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(50) NOT NULL UNIQUE,
    next_of_kin_name VARCHAR(150) DEFAULT NULL,
    next_of_kin_relationship VARCHAR(80) DEFAULT NULL,
    next_of_kin_phone VARCHAR(40) DEFAULT NULL,
    next_of_kin_email VARCHAR(150) DEFAULT NULL,
    next_of_kin_address TEXT DEFAULT NULL,
    marital_status VARCHAR(50) DEFAULT NULL,
    dependants INT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staff_documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(50) NOT NULL,
    document_type ENUM('CV','Certificate','Appointment Letter','Contract','License','Other') NOT NULL,
    document_title VARCHAR(180) NOT NULL,
    file_path VARCHAR(255) DEFAULT NULL,
    uploaded_by INT UNSIGNED DEFAULT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staff_work_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(50) NOT NULL,
    position VARCHAR(120) NOT NULL,
    department VARCHAR(120) DEFAULT NULL,
    start_date DATE NOT NULL,
    end_date DATE DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staff_contracts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(50) NOT NULL,
    contract_type ENUM('Permanent','Temporary','Contract','Internship','Part Time') DEFAULT 'Contract',
    start_date DATE NOT NULL,
    end_date DATE DEFAULT NULL,
    salary_grade VARCHAR(80) DEFAULT NULL,
    status ENUM('Active','Expired','Terminated','Renewed') DEFAULT 'Active',
    renewal_reminder_sent TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_staff_contract_dates (end_date, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS recruitment_jobs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_code VARCHAR(60) NOT NULL UNIQUE,
    job_title VARCHAR(180) NOT NULL,
    department VARCHAR(120) DEFAULT NULL,
    job_type VARCHAR(80) DEFAULT 'Full Time',
    job_category VARCHAR(80) DEFAULT 'Academic',
    vacancy_scope ENUM('Internal','External','Both') DEFAULT 'Both',
    description TEXT DEFAULT NULL,
    requirements TEXT DEFAULT NULL,
    application_deadline DATE DEFAULT NULL,
    vacancies INT UNSIGNED DEFAULT 1,
    status ENUM('Draft','Open','Closed','Filled') DEFAULT 'Open',
    posted_by INT UNSIGNED DEFAULT NULL,
    posted_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS recruitment_applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id INT UNSIGNED NOT NULL,
    applicant_name VARCHAR(180) NOT NULL,
    applicant_email VARCHAR(150) DEFAULT NULL,
    applicant_phone VARCHAR(40) DEFAULT NULL,
    qualifications TEXT DEFAULT NULL,
    application_status ENUM('Received','Shortlisted','Interview','Selected','Rejected','Withdrawn') DEFAULT 'Received',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES recruitment_jobs(id) ON DELETE CASCADE,
    INDEX idx_recruitment_status (application_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS interview_schedules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id INT UNSIGNED NOT NULL,
    interview_date DATETIME NOT NULL,
    venue VARCHAR(180) DEFAULT NULL,
    panel_members TEXT DEFAULT NULL,
    status ENUM('Scheduled','Completed','Cancelled') DEFAULT 'Scheduled',
    FOREIGN KEY (application_id) REFERENCES recruitment_applications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS interview_scores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id INT UNSIGNED NOT NULL,
    evaluator_name VARCHAR(150) DEFAULT NULL,
    criteria VARCHAR(180) NOT NULL,
    score DECIMAL(5,2) DEFAULT 0,
    max_score DECIMAL(5,2) DEFAULT 100,
    remarks TEXT DEFAULT NULL,
    evaluated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES recruitment_applications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS onboarding_checklists (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(50) DEFAULT NULL,
    applicant_name VARCHAR(180) NOT NULL,
    item VARCHAR(180) NOT NULL,
    is_complete TINYINT(1) DEFAULT 0,
    completed_at DATETIME DEFAULT NULL,
    completed_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_onboarding_staff (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staff_attendance (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(50) NOT NULL,
    attendance_date DATE NOT NULL,
    check_in TIME DEFAULT NULL,
    check_out TIME DEFAULT NULL,
    source ENUM('Manual','Biometric','Roster') DEFAULT 'Manual',
    status ENUM('Present','Absent','Late','Leave','Duty','Half Day') DEFAULT 'Present',
    notes TEXT DEFAULT NULL,
    recorded_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON UPDATE CASCADE ON DELETE CASCADE,
    UNIQUE KEY uniq_staff_attendance_day (staff_id, attendance_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS duty_rosters (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(50) NOT NULL,
    duty_date DATE NOT NULL,
    shift VARCHAR(80) DEFAULT NULL,
    location VARCHAR(180) DEFAULT NULL,
    duty_type VARCHAR(100) DEFAULT 'Clinical',
    status ENUM('Scheduled','Completed','Cancelled','Changed') DEFAULT 'Scheduled',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_duty_date (duty_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staff_leave_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(50) NOT NULL,
    leave_type ENUM('Annual','Sick','Maternity','Study','Compassionate','Unpaid') DEFAULT 'Annual',
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT DEFAULT NULL,
    status ENUM('Pending','Approved','Rejected','Cancelled') DEFAULT 'Pending',
    approved_by INT UNSIGNED DEFAULT NULL,
    approval_date DATETIME DEFAULT NULL,
    approval_remarks TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_leave_status_dates (status, start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payroll_structures (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(50) NOT NULL,
    basic_pay DECIMAL(12,2) DEFAULT 0,
    allowance_name VARCHAR(120) DEFAULT NULL,
    allowance_amount DECIMAL(12,2) DEFAULT 0,
    deduction_name VARCHAR(120) DEFAULT NULL,
    deduction_amount DECIMAL(12,2) DEFAULT 0,
    effective_from DATE NOT NULL,
    effective_to DATE DEFAULT NULL,
    status ENUM('Active','Inactive') DEFAULT 'Active',
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payroll_inputs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payroll_month DATE NOT NULL,
    staff_id VARCHAR(50) NOT NULL,
    basic_pay DECIMAL(12,2) DEFAULT 0,
    allowances DECIMAL(12,2) DEFAULT 0,
    deductions DECIMAL(12,2) DEFAULT 0,
    overtime_hours DECIMAL(8,2) DEFAULT 0,
    overtime_amount DECIMAL(12,2) DEFAULT 0,
    validation_status ENUM('Pending','Validated','Rejected') DEFAULT 'Pending',
    validated_by INT UNSIGNED DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_payroll_month (payroll_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payslips (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payroll_month DATE NOT NULL,
    staff_id VARCHAR(50) NOT NULL,
    gross_pay DECIMAL(12,2) DEFAULT 0,
    net_pay DECIMAL(12,2) DEFAULT 0,
    deductions_summary TEXT DEFAULT NULL,
    file_path VARCHAR(255) DEFAULT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_payslip_month (payroll_month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS overtime_records (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(50) NOT NULL,
    overtime_date DATE NOT NULL,
    hours DECIMAL(8,2) DEFAULT 0,
    rate DECIMAL(12,2) DEFAULT 0,
    approved_by INT UNSIGNED DEFAULT NULL,
    status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
    notes TEXT DEFAULT NULL,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS performance_appraisals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(50) NOT NULL,
    appraisal_period VARCHAR(80) NOT NULL,
    appraisal_type ENUM('Quarterly','Annual','Probation') DEFAULT 'Annual',
    overall_score DECIMAL(5,2) DEFAULT 0,
    rating ENUM('Excellent','Very Good','Good','Fair','Poor') DEFAULT 'Good',
    supervisor_comments TEXT DEFAULT NULL,
    promotion_recommendation ENUM('Yes','No','Consider') DEFAULT 'No',
    status ENUM('Draft','Submitted','Reviewed','Completed') DEFAULT 'Draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS performance_indicators (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    appraisal_id INT UNSIGNED NOT NULL,
    indicator VARCHAR(180) NOT NULL,
    target_score DECIMAL(5,2) DEFAULT 100,
    achieved_score DECIMAL(5,2) DEFAULT 0,
    comments TEXT DEFAULT NULL,
    FOREIGN KEY (appraisal_id) REFERENCES performance_appraisals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS performance_feedback (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    appraisal_id INT UNSIGNED NOT NULL,
    feedback_by INT UNSIGNED DEFAULT NULL,
    feedback TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appraisal_id) REFERENCES performance_appraisals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS trainings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    training_type ENUM('Workshop','Seminar','CPD','License Renewal','Scholarship','Other') DEFAULT 'CPD',
    start_date DATE NOT NULL,
    end_date DATE DEFAULT NULL,
    provider VARCHAR(180) DEFAULT NULL,
    status ENUM('Planned','Completed','Cancelled') DEFAULT 'Planned'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staff_training_records (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(50) NOT NULL,
    training_id INT UNSIGNED NOT NULL,
    attendance_status ENUM('Registered','Attended','Absent','Completed') DEFAULT 'Registered',
    certificate_path VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (training_id) REFERENCES trainings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS professional_licenses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(50) NOT NULL,
    license_type VARCHAR(120) NOT NULL,
    license_number VARCHAR(100) DEFAULT NULL,
    issuing_body VARCHAR(150) DEFAULT NULL,
    expiry_date DATE NOT NULL,
    status ENUM('Valid','Expiring Soon','Expired') DEFAULT 'Valid',
    document_path VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON UPDATE CASCADE ON DELETE CASCADE,
    INDEX idx_license_expiry (expiry_date, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disciplinary_records (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(50) NOT NULL,
    incident_date DATE NOT NULL,
    incident_type VARCHAR(120) DEFAULT NULL,
    description TEXT NOT NULL,
    status ENUM('Pending','Under Investigation','Heard','Resolved','Closed') DEFAULT 'Pending',
    committee_decision TEXT DEFAULT NULL,
    resolution_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(staff_id) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS disciplinary_actions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    disciplinary_record_id INT UNSIGNED NOT NULL,
    action_type ENUM('Verbal Warning','Written Warning','Final Warning','Suspension','Dismissal','Other') NOT NULL,
    action_date DATE NOT NULL,
    issued_by INT UNSIGNED DEFAULT NULL,
    document_path VARCHAR(255) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    FOREIGN KEY (disciplinary_record_id) REFERENCES disciplinary_records(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staff_memos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    body TEXT NOT NULL,
    audience ENUM('All Staff','Department','Role','Individual') DEFAULT 'All Staff',
    target_department VARCHAR(120) DEFAULT NULL,
    target_role VARCHAR(120) DEFAULT NULL,
    target_staff_id VARCHAR(50) DEFAULT NULL,
    priority ENUM('Low','Normal','High','Urgent') DEFAULT 'Normal',
    created_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_staff_memo_target (audience, target_department, target_role, target_staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staff_notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(50) DEFAULT NULL,
    title VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    notification_type ENUM('Meeting','Duty','Alert','Memo','Payroll','Leave') DEFAULT 'Alert',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_staff_notification_read (staff_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
