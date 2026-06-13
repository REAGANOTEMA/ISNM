-- ISNM Student Academic and Finance Module Schema
-- Import into igangaschoolofl_students_db

CREATE TABLE IF NOT EXISTS student_admissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED DEFAULT NULL,
    application_number VARCHAR(80) NOT NULL UNIQUE,
    applicant_name VARCHAR(180) NOT NULL,
    program VARCHAR(120) NOT NULL,
    academic_year VARCHAR(30) DEFAULT NULL,
    admission_status ENUM('Applied','Interview','Admitted','Rejected','Deferred','Enrolled') DEFAULT 'Applied',
    application_date DATE NOT NULL,
    decision_date DATE DEFAULT NULL,
    decided_by INT UNSIGNED DEFAULT NULL,
    remarks TEXT DEFAULT NULL,
    INDEX idx_admission_status (admission_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    document_type ENUM('UCE','UACE','ID','Birth Certificate','Admission Letter','Other') NOT NULL,
    document_title VARCHAR(180) DEFAULT NULL,
    file_path VARCHAR(255) DEFAULT NULL,
    uploaded_by INT UNSIGNED DEFAULT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_student_documents (student_id, document_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS course_catalog (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(40) NOT NULL UNIQUE,
    course_name VARCHAR(180) NOT NULL,
    program VARCHAR(120) DEFAULT NULL,
    level VARCHAR(80) DEFAULT NULL,
    semester VARCHAR(40) DEFAULT NULL,
    credit_hours INT UNSIGNED DEFAULT 0,
    is_compulsory TINYINT(1) DEFAULT 0,
    status ENUM('Active','Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS course_prerequisites (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    prerequisite_course_id INT UNSIGNED NOT NULL,
    FOREIGN KEY (course_id) REFERENCES course_catalog(id) ON DELETE CASCADE,
    FOREIGN KEY (prerequisite_course_id) REFERENCES course_catalog(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS course_registrations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    academic_year VARCHAR(30) NOT NULL,
    semester VARCHAR(40) NOT NULL,
    registration_status ENUM('Registered','Dropped','Approved','Rejected') DEFAULT 'Registered',
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES course_catalog(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_course_registration (student_id, course_id, academic_year, semester)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS examination_sessions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    academic_year VARCHAR(30) NOT NULL,
    semester VARCHAR(40) NOT NULL,
    exam_type ENUM('Continuous Assessment','Coursework','Practical','Final Exam') DEFAULT 'Final Exam',
    exam_date DATE DEFAULT NULL,
    max_score DECIMAL(6,2) DEFAULT 100,
    status ENUM('Open','Closed','Published') DEFAULT 'Open',
    FOREIGN KEY (course_id) REFERENCES course_catalog(id) ON DELETE CASCADE,
    INDEX idx_exam_session (course_id, academic_year, semester, exam_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS assessment_scores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    examination_session_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    score DECIMAL(6,2) DEFAULT 0,
    max_score DECIMAL(6,2) DEFAULT 100,
    entered_by INT UNSIGNED DEFAULT NULL,
    entered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (examination_session_id) REFERENCES examination_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_assessment_score (examination_session_id, student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS examination_results (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED NOT NULL,
    academic_year VARCHAR(30) NOT NULL,
    semester VARCHAR(40) NOT NULL,
    total_score DECIMAL(6,2) DEFAULT 0,
    grade VARCHAR(10) DEFAULT NULL,
    status ENUM('Pending','Approved','Published','Withheld') DEFAULT 'Pending',
    lecturer_id INT UNSIGNED DEFAULT NULL,
    hod_id INT UNSIGNED DEFAULT NULL,
    registrar_id INT UNSIGNED DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    published_at DATETIME DEFAULT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES course_catalog(id) ON DELETE CASCADE,
    INDEX idx_exam_results_student (student_id, academic_year, semester)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS result_approval_workflow (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    examination_result_id INT UNSIGNED NOT NULL,
    approver_role ENUM('Lecturer','HOD','Registrar','Principal') NOT NULL,
    approver_id INT UNSIGNED DEFAULT NULL,
    action ENUM('Submitted','Approved','Returned','Published') NOT NULL,
    remarks TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (examination_result_id) REFERENCES examination_results(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_attendance (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    course_id INT UNSIGNED DEFAULT NULL,
    attendance_date DATE NOT NULL,
    attendance_type ENUM('Class','Clinical','Practical','Supervision') DEFAULT 'Class',
    status ENUM('Present','Absent','Late','Excused') DEFAULT 'Present',
    remarks TEXT DEFAULT NULL,
    recorded_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES course_catalog(id) ON DELETE SET NULL,
    UNIQUE KEY uniq_student_attendance_day (student_id, attendance_date, attendance_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS clinical_placements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    facility_name VARCHAR(180) NOT NULL,
    facility_type VARCHAR(80) DEFAULT NULL,
    placement_start DATE NOT NULL,
    placement_end DATE NOT NULL,
    supervisor_name VARCHAR(150) DEFAULT NULL,
    supervisor_phone VARCHAR(40) DEFAULT NULL,
    status ENUM('Scheduled','Active','Completed','Cancelled') DEFAULT 'Scheduled',
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_placement_dates (placement_start, placement_end)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS clinical_logbooks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    placement_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    procedure_name VARCHAR(180) NOT NULL,
    performed_at DATE NOT NULL,
    competency_level ENUM('Observed','Assisted','Performed','Competent') DEFAULT 'Observed',
    supervisor_comments TEXT DEFAULT NULL,
    FOREIGN KEY (placement_id) REFERENCES clinical_placements(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS clinical_evaluations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    placement_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    evaluator_name VARCHAR(150) DEFAULT NULL,
    evaluation_period VARCHAR(80) DEFAULT NULL,
    overall_score DECIMAL(5,2) DEFAULT 0,
    comments TEXT DEFAULT NULL,
    completion_verified TINYINT(1) DEFAULT 0,
    verified_at DATETIME DEFAULT NULL,
    FOREIGN KEY (placement_id) REFERENCES clinical_placements(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_discipline_cases (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    incident_date DATE NOT NULL,
    case_type VARCHAR(120) DEFAULT NULL,
    description TEXT NOT NULL,
    action_taken ENUM('Counselling','Verbal Warning','Written Warning','Suspension','Expulsion','Other') DEFAULT NULL,
    committee_decision TEXT DEFAULT NULL,
    status ENUM('Pending','Heard','Resolved','Closed') DEFAULT 'Pending',
    resolution_date DATE DEFAULT NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_hostels (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    hostel_name VARCHAR(120) NOT NULL,
    room_number VARCHAR(40) DEFAULT NULL,
    academic_year VARCHAR(30) NOT NULL,
    allocation_date DATE NOT NULL,
    checkout_date DATE DEFAULT NULL,
    status ENUM('Allocated','Checked Out','Cancelled') DEFAULT 'Allocated',
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_hostel_allocation (hostel_name, room_number, academic_year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS library_borrowings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    book_title VARCHAR(220) NOT NULL,
    accession_number VARCHAR(80) DEFAULT NULL,
    borrowed_date DATE NOT NULL,
    due_date DATE NOT NULL,
    returned_date DATE DEFAULT NULL,
    fine_amount DECIMAL(10,2) DEFAULT 0,
    status ENUM('Borrowed','Returned','Overdue') DEFAULT 'Borrowed',
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_library_due (due_date, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    request_type ENUM('Leave of Absence','Deferral','Document','Fee Adjustment','Other') NOT NULL,
    subject VARCHAR(180) DEFAULT NULL,
    message TEXT NOT NULL,
    status ENUM('Pending','Approved','Rejected','Resolved') DEFAULT 'Pending',
    reviewed_by INT UNSIGNED DEFAULT NULL,
    reviewed_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_notices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(180) NOT NULL,
    body TEXT NOT NULL,
    audience ENUM('All Students','Program','Year','Individual') DEFAULT 'All Students',
    target_program VARCHAR(120) DEFAULT NULL,
    target_year VARCHAR(80) DEFAULT NULL,
    target_student_id INT UNSIGNED DEFAULT NULL,
    priority ENUM('Normal','High','Urgent') DEFAULT 'Normal',
    created_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fee_structures (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    fee_name VARCHAR(180) NOT NULL,
    fee_type ENUM('Tuition','Accommodation','Clinical','Exam','Library','Penalty','Other') DEFAULT 'Tuition',
    amount DECIMAL(12,2) NOT NULL,
    program_id INT UNSIGNED DEFAULT NULL,
    academic_year VARCHAR(30) DEFAULT NULL,
    semester VARCHAR(40) DEFAULT NULL,
    due_date DATE DEFAULT NULL,
    is_mandatory TINYINT(1) DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS student_invoices (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(80) NOT NULL UNIQUE,
    student_id INT UNSIGNED NOT NULL,
    academic_year VARCHAR(30) DEFAULT NULL,
    semester VARCHAR(40) DEFAULT NULL,
    fee_type VARCHAR(80) DEFAULT NULL,
    total_amount DECIMAL(12,2) NOT NULL,
    amount_paid DECIMAL(12,2) DEFAULT 0,
    balance DECIMAL(12,2) GENERATED ALWAYS AS (total_amount - amount_paid) STORED,
    due_date DATE DEFAULT NULL,
    status ENUM('Pending','Paid','Partially Paid','Overdue','Waived','Cancelled') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_invoice_status (status, due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    payment_reference VARCHAR(80) NOT NULL UNIQUE,
    student_id INT UNSIGNED NOT NULL,
    invoice_id INT UNSIGNED DEFAULT NULL,
    amount_received DECIMAL(12,2) NOT NULL,
    payment_method ENUM('Cash','Bank Transfer','Mobile Money','MTN','Airtel','Cheque','Card','Other') DEFAULT 'Cash',
    transaction_ref VARCHAR(120) DEFAULT NULL,
    payment_date DATE NOT NULL,
    status ENUM('Pending','Completed','verified','approved','Rejected','Reversed') DEFAULT 'Completed',
    notes TEXT DEFAULT NULL,
    received_by INT UNSIGNED DEFAULT NULL,
    verified_by INT UNSIGNED DEFAULT NULL,
    verified_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (invoice_id) REFERENCES student_invoices(id) ON DELETE SET NULL,
    INDEX idx_payment_date (payment_date, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_receipts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    receipt_number VARCHAR(80) NOT NULL UNIQUE,
    payment_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_method VARCHAR(80) DEFAULT NULL,
    issued_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mobile_money_transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider ENUM('MTN MoMo','Airtel Money','Bank','Other') NOT NULL,
    transaction_id VARCHAR(120) NOT NULL UNIQUE,
    student_id INT UNSIGNED NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    phone_number VARCHAR(40) DEFAULT NULL,
    status ENUM('Initiated','Completed','Failed','Reversed') DEFAULT 'Initiated',
    payload JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS fee_reminders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reminder_number VARCHAR(80) NOT NULL UNIQUE,
    student_id INT UNSIGNED NOT NULL,
    reminder_type ENUM('Email','SMS','Portal','Printed') DEFAULT 'Email',
    message TEXT DEFAULT NULL,
    sent_by INT UNSIGNED DEFAULT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS budgets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    budget_name VARCHAR(180) NOT NULL,
    fiscal_year VARCHAR(30) NOT NULL,
    total_budget DECIMAL(14,2) DEFAULT 0,
    status ENUM('Draft','Approved','Active','Closed') DEFAULT 'Draft',
    approved_by INT UNSIGNED DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS budget_allocations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    budget_id INT UNSIGNED NOT NULL,
    department VARCHAR(120) NOT NULL,
    category VARCHAR(120) DEFAULT NULL,
    allocated_amount DECIMAL(14,2) NOT NULL,
    notes TEXT DEFAULT NULL,
    FOREIGN KEY (budget_id) REFERENCES budgets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS expenditures (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    budget_id INT UNSIGNED DEFAULT NULL,
    expense_date DATE NOT NULL,
    department VARCHAR(120) DEFAULT NULL,
    category VARCHAR(120) DEFAULT NULL,
    description TEXT NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    payment_mode VARCHAR(80) DEFAULT NULL,
    approval_status ENUM('Pending','Approved','Rejected','Paid') DEFAULT 'Pending',
    approved_by INT UNSIGNED DEFAULT NULL,
    paid_at DATETIME DEFAULT NULL,
    FOREIGN KEY (budget_id) REFERENCES budgets(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS expenditure_approvals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    expenditure_id INT UNSIGNED NOT NULL,
    approver_id INT UNSIGNED DEFAULT NULL,
    action ENUM('Submitted','Approved','Rejected','Paid') NOT NULL,
    remarks TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (expenditure_id) REFERENCES expenditures(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS chart_accounts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    account_code VARCHAR(40) NOT NULL UNIQUE,
    account_name VARCHAR(180) NOT NULL,
    account_type ENUM('Asset','Liability','Equity','Income','Expense') NOT NULL,
    parent_account_id INT UNSIGNED DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (parent_account_id) REFERENCES chart_accounts(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ledger_entries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entry_date DATE NOT NULL,
    account_id INT UNSIGNED NOT NULL,
    description TEXT NOT NULL,
    debit DECIMAL(14,2) DEFAULT 0,
    credit DECIMAL(14,2) DEFAULT 0,
    reference_type VARCHAR(80) DEFAULT NULL,
    reference_id INT UNSIGNED DEFAULT NULL,
    posted_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES chart_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bank_accounts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bank_name VARCHAR(150) NOT NULL,
    account_name VARCHAR(150) NOT NULL,
    account_number VARCHAR(80) DEFAULT NULL,
    opening_balance DECIMAL(14,2) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bank_reconciliations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bank_account_id INT UNSIGNED NOT NULL,
    reconciliation_month DATE NOT NULL,
    bank_balance DECIMAL(14,2) DEFAULT 0,
    system_balance DECIMAL(14,2) DEFAULT 0,
    difference DECIMAL(14,2) DEFAULT 0,
    status ENUM('Draft','Reviewed','Completed') DEFAULT 'Draft',
    reviewed_by INT UNSIGNED DEFAULT NULL,
    FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS assets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_name VARCHAR(180) NOT NULL,
    asset_category ENUM('Equipment','Furniture','Books','Vehicle','ICT','Building','Other') DEFAULT 'Other',
    purchase_date DATE NOT NULL,
    purchase_amount DECIMAL(14,2) NOT NULL,
    supplier VARCHAR(180) DEFAULT NULL,
    location VARCHAR(180) DEFAULT NULL,
    depreciation_rate DECIMAL(6,2) DEFAULT 0,
    status ENUM('Active','Disposed','Lost') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS asset_depreciation (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asset_id INT UNSIGNED NOT NULL,
    depreciation_date DATE NOT NULL,
    depreciation_amount DECIMAL(12,2) DEFAULT 0,
    accumulated_depreciation DECIMAL(12,2) DEFAULT 0,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_queue (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    recipient_type ENUM('Student','Staff','All') DEFAULT 'Student',
    recipient_id INT UNSIGNED DEFAULT NULL,
    channel ENUM('Portal','Email','SMS') DEFAULT 'Portal',
    subject VARCHAR(180) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('Pending','Sent','Failed') DEFAULT 'Pending',
    sent_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ura_reports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    report_period DATE NOT NULL,
    report_type VARCHAR(80) DEFAULT 'Withholding Tax',
    total_amount DECIMAL(14,2) DEFAULT 0,
    file_path VARCHAR(255) DEFAULT NULL,
    status ENUM('Draft','Submitted','Accepted','Rejected') DEFAULT 'Draft',
    submitted_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
