-- ==============================================================================
-- ISNM (Iganga School of Nursing & Midwifery) ERP System
-- SQL Migration File - Creates ALL missing tables across 4 databases
-- ==============================================================================

-- ==============================================================================
-- DATABASE 1: igangaschoolofl_students_db (students_db)
-- ==============================================================================

-- Fee structures per program per year
CREATE TABLE IF NOT EXISTS igangaschoolofl_students_db.fee_structures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program VARCHAR(255) NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    fee_category VARCHAR(100) NOT NULL, -- tuition, accommodation, clinical, library, activity, other
    amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    is_compulsory TINYINT(1) DEFAULT 1,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Student invoices
CREATE TABLE IF NOT EXISTS igangaschoolofl_students_db.student_invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(20),
    total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    amount_paid DECIMAL(12,2) NOT NULL DEFAULT 0,
    balance DECIMAL(12,2) NOT NULL DEFAULT 0,
    due_date DATE,
    status ENUM('Pending','Partially Paid','Paid','Overdue','Cancelled') DEFAULT 'Pending',
    invoice_date DATE DEFAULT (CURRENT_DATE),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Payments
CREATE TABLE IF NOT EXISTS igangaschoolofl_students_db.payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    invoice_id INT,
    payment_reference VARCHAR(50) NOT NULL UNIQUE,
    amount_received DECIMAL(12,2) NOT NULL,
    payment_method ENUM('cash','bank','mobile_money','cheque','other') DEFAULT 'cash',
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Pending','Completed','Failed','Refunded') DEFAULT 'Pending',
    proof_document VARCHAR(255),
    verified_by INT,
    verification_date DATETIME,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Payment receipts
CREATE TABLE IF NOT EXISTS igangaschoolofl_students_db.payment_receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_number VARCHAR(50) NOT NULL UNIQUE,
    payment_id INT NOT NULL,
    student_id VARCHAR(50) NOT NULL,
    receipt_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    generated_by INT,
    pdf_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Student course registrations
CREATE TABLE IF NOT EXISTS igangaschoolofl_students_db.student_course_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    course_id INT NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    registration_date DATE DEFAULT (CURRENT_DATE),
    status ENUM('Registered','Dropped','Completed') DEFAULT 'Registered',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Student attendance
CREATE TABLE IF NOT EXISTS igangaschoolofl_students_db.student_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    course_id INT,
    session_date DATE NOT NULL,
    session_type ENUM('class','clinical','practical') DEFAULT 'class',
    status ENUM('present','absent','late','excused') DEFAULT 'present',
    marked_by INT,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Clinical placements
CREATE TABLE IF NOT EXISTS igangaschoolofl_students_db.clinical_placements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    facility_name VARCHAR(255) NOT NULL,
    facility_location VARCHAR(255),
    supervisor_name VARCHAR(255),
    start_date DATE NOT NULL,
    end_date DATE,
    hours_completed INT DEFAULT 0,
    skills_assessment TEXT,
    status ENUM('Active','Completed','Cancelled') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Student discipline
CREATE TABLE IF NOT EXISTS igangaschoolofl_students_db.student_discipline (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    incident_date DATE NOT NULL,
    incident_type VARCHAR(100) NOT NULL,
    description TEXT,
    action_taken VARCHAR(255),
    action_date DATE,
    reported_by INT,
    status ENUM('Open','Resolved','Appealed') DEFAULT 'Open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Hostel management - rooms
CREATE TABLE IF NOT EXISTS igangaschoolofl_students_db.hostel_rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(20) NOT NULL UNIQUE,
    hostel_name VARCHAR(100) NOT NULL,
    capacity INT NOT NULL DEFAULT 4,
    occupancy INT NOT NULL DEFAULT 0,
    fee_per_semester DECIMAL(12,2) DEFAULT 0,
    status ENUM('Available','Full','Maintenance') DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Hostel management - allocations
CREATE TABLE IF NOT EXISTS igangaschoolofl_students_db.hostel_allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    room_id INT NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(20) NOT NULL,
    check_in_date DATE DEFAULT (CURRENT_DATE),
    check_out_date DATE,
    status ENUM('Active','Checked Out','Cancelled') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Library - books
CREATE TABLE IF NOT EXISTS igangaschoolofl_students_db.library_books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_title VARCHAR(255) NOT NULL,
    author VARCHAR(255),
    isbn VARCHAR(50),
    publisher VARCHAR(255),
    publication_year YEAR,
    category VARCHAR(100),
    total_copies INT DEFAULT 1,
    available_copies INT DEFAULT 1,
    shelf_location VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Library - borrowing records
CREATE TABLE IF NOT EXISTS igangaschoolofl_students_db.library_borrowing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    book_id INT NOT NULL,
    borrow_date DATE DEFAULT (CURRENT_DATE),
    due_date DATE NOT NULL,
    return_date DATE,
    fine_amount DECIMAL(10,2) DEFAULT 0,
    fine_paid TINYINT(1) DEFAULT 0,
    status ENUM('Borrowed','Returned','Overdue','Lost') DEFAULT 'Borrowed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==============================================================================
-- DATABASE 2: igangaschoolofl_staffs_db (staffs_db)
-- ==============================================================================

-- Penalty configurations for late fees
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.penalty_configurations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    fee_category VARCHAR(100),
    penalty_type ENUM('percentage','fixed') DEFAULT 'percentage',
    penalty_value DECIMAL(10,2) NOT NULL,
    grace_period_days INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Expenditure records
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.expenditure_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expenditure_number VARCHAR(50) NOT NULL UNIQUE,
    expense_category VARCHAR(100) NOT NULL,
    description TEXT,
    amount DECIMAL(12,2) NOT NULL,
    payment_method ENUM('cash','bank','mobile_money','cheque') DEFAULT 'cash',
    expense_date DATE DEFAULT (CURRENT_DATE),
    approved_by INT,
    receipt_document VARCHAR(255),
    notes TEXT,
    status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Budget management
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    budget_name VARCHAR(255) NOT NULL,
    fiscal_year VARCHAR(20) NOT NULL,
    department VARCHAR(100),
    total_budget DECIMAL(14,2) NOT NULL DEFAULT 0,
    amount_spent DECIMAL(14,2) NOT NULL DEFAULT 0,
    amount_remaining DECIMAL(14,2) GENERATED ALWAYS AS (total_budget - amount_spent) STORED,
    status ENUM('Draft','Active','Closed','Cancelled') DEFAULT 'Draft',
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Chart of accounts
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.chart_of_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_code VARCHAR(20) NOT NULL UNIQUE,
    account_name VARCHAR(255) NOT NULL,
    account_type ENUM('asset','liability','equity','income','expense') NOT NULL,
    parent_account_id INT,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- General ledger entries
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.general_ledger (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entry_number VARCHAR(50) NOT NULL UNIQUE,
    account_id INT NOT NULL,
    entry_type ENUM('debit','credit') NOT NULL,
    amount DECIMAL(14,2) NOT NULL,
    entry_date DATE DEFAULT (CURRENT_DATE),
    description TEXT,
    reference_type VARCHAR(50),
    reference_id INT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bank reconciliation
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.bank_reconciliation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bank_account_name VARCHAR(255) NOT NULL,
    statement_date DATE NOT NULL,
    opening_balance DECIMAL(14,2) DEFAULT 0,
    closing_balance DECIMAL(14,2) DEFAULT 0,
    total_deposits DECIMAL(14,2) DEFAULT 0,
    total_withdrawals DECIMAL(14,2) DEFAULT 0,
    reconciled TINYINT(1) DEFAULT 0,
    reconciliation_date DATETIME,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Staff leave records
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.staff_leave_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    leave_type ENUM('annual','sick','maternity','paternity','study','compassionate','other') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_days INT GENERATED ALWAYS AS (DATEDIFF(end_date, start_date) + 1) STORED,
    reason TEXT,
    status ENUM('Pending','Approved','Rejected','Cancelled') DEFAULT 'Pending',
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Staff attendance
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.staff_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    attendance_date DATE NOT NULL DEFAULT (CURRENT_DATE),
    check_in TIME,
    check_out TIME,
    status ENUM('present','absent','late','half_day','leave') DEFAULT 'present',
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_attendance (staff_id, attendance_date)
);

-- Performance appraisals
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.staff_appraisals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    appraisal_period VARCHAR(50) NOT NULL,
    appraisal_date DATE DEFAULT (CURRENT_DATE),
    rating DECIMAL(3,1),
    reviewer_id INT,
    strengths TEXT,
    areas_for_improvement TEXT,
    overall_comments TEXT,
    status ENUM('Draft','Submitted','Reviewed','Completed') DEFAULT 'Draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Training & CPD records
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.staff_training (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    training_title VARCHAR(255) NOT NULL,
    training_provider VARCHAR(255),
    start_date DATE,
    end_date DATE,
    training_type ENUM('workshop','seminar','course','conference','cpd','other') DEFAULT 'cpd',
    certificate_path VARCHAR(255),
    status ENUM('Registered','Completed','Cancelled') DEFAULT 'Registered',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Recruitment - job vacancies
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.job_vacancies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_title VARCHAR(255) NOT NULL,
    department VARCHAR(100),
    employment_type ENUM('full_time','part_time','contract','internship') DEFAULT 'full_time',
    description TEXT,
    requirements TEXT,
    salary_range VARCHAR(100),
    application_deadline DATE,
    status ENUM('Open','Closed','Filled') DEFAULT 'Open',
    posted_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Contract management
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.staff_contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    contract_type ENUM('permanent','fixed_term','probation','temporary','part_time') DEFAULT 'probation',
    start_date DATE NOT NULL,
    end_date DATE,
    renewal_date DATE,
    contract_document VARCHAR(255),
    status ENUM('Active','Expired','Terminated','Renewed') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==============================================================================
-- DATABASE 3: igangaschoolofl_website_db (website_db)
-- ==============================================================================

-- Student applications (for admission)
CREATE TABLE IF NOT EXISTS igangaschoolofl_website_db.student_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_number VARCHAR(50) NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    surname VARCHAR(100) NOT NULL,
    other_name VARCHAR(100),
    gender ENUM('Male','Female','Other') NOT NULL,
    date_of_birth DATE NOT NULL,
    nationality VARCHAR(100) DEFAULT 'Ugandan',
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(255),
    address TEXT,
    program_applied VARCHAR(255) NOT NULL,
    previous_school VARCHAR(255),
    uce_results VARCHAR(255),
    uace_results VARCHAR(255),
    status ENUM('Pending','Shortlisted','Admitted','Rejected','Withdrawn') DEFAULT 'Pending',
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_by INT,
    reviewed_at DATETIME
);

-- SMS/email notifications
CREATE TABLE IF NOT EXISTS igangaschoolofl_website_db.notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_type ENUM('student','staff','all') NOT NULL,
    recipient_id INT,
    channel ENUM('sms','email','both') DEFAULT 'both',
    subject VARCHAR(255),
    message TEXT NOT NULL,
    status ENUM('Pending','Sent','Failed') DEFAULT 'Pending',
    sent_at DATETIME,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Student portal messages
CREATE TABLE IF NOT EXISTS igangaschoolofl_website_db.portal_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    recipient_id INT,
    recipient_type ENUM('individual','group','all') DEFAULT 'individual',
    subject VARCHAR(255),
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    read_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ==============================================================================
-- DATABASE 3 (continued): Website public submissions
-- ==============================================================================

-- Contact form submissions
CREATE TABLE IF NOT EXISTS igangaschoolofl_website_db.contact_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    subject VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    status ENUM('unread','read','replied') DEFAULT 'unread',
    notified TINYINT(1) DEFAULT 0,
    replied_at DATETIME,
    replied_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_created (created_at)
);

-- Volunteer applications
CREATE TABLE IF NOT EXISTS igangaschoolofl_website_db.volunteer_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    profession VARCHAR(100) NOT NULL,
    experience INT NOT NULL DEFAULT 0,
    opportunity VARCHAR(100) NOT NULL,
    availability VARCHAR(50) NOT NULL,
    duration VARCHAR(50) NOT NULL,
    skills TEXT NOT NULL,
    motivation TEXT NOT NULL,
    comments TEXT,
    status ENUM('pending','reviewed','contacted','accepted','declined') DEFAULT 'pending',
    notified TINYINT(1) DEFAULT 0,
    reviewed_at DATETIME,
    reviewed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_created (created_at)
);

-- Donation records
CREATE TABLE IF NOT EXISTS igangaschoolofl_website_db.donations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    donor_name VARCHAR(200) NOT NULL,
    donor_email VARCHAR(255) NOT NULL,
    donor_phone VARCHAR(50) NOT NULL,
    donor_address VARCHAR(500),
    amount DECIMAL(12,2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    payment_provider VARCHAR(50),
    transaction_reference VARCHAR(100),
    purpose VARCHAR(200) DEFAULT 'General Donation',
    notes TEXT,
    status ENUM('pending','completed','failed','refunded') DEFAULT 'pending',
    notified TINYINT(1) DEFAULT 0,
    acknowledged_at DATETIME,
    acknowledged_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_created (created_at)
);

-- ==============================================================================
-- DATABASE 1 (continued): Auto-Deduction / Subscription Payment System
-- ==============================================================================

-- Payment subscriptions (auto-deduction plans)
CREATE TABLE IF NOT EXISTS igangaschoolofl_students_db.payment_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    subscription_type ENUM('fee_installment','hostel','library','other') NOT NULL DEFAULT 'fee_installment',
    reference_type VARCHAR(50) COMMENT 'eg: fee_structure_id, hostel_room_id',
    reference_id INT,
    total_amount DECIMAL(12,2) NOT NULL,
    installment_amount DECIMAL(12,2) NOT NULL,
    frequency ENUM('monthly','weekly','quarterly') NOT NULL DEFAULT 'monthly',
    total_installments INT NOT NULL,
    installments_collected INT NOT NULL DEFAULT 0,
    start_date DATE NOT NULL DEFAULT (CURRENT_DATE),
    next_due_date DATE NOT NULL,
    end_date DATE,
    payment_method ENUM('mobile_money','bank','cash') DEFAULT 'mobile_money',
    payment_provider VARCHAR(50) COMMENT 'mtn_momo, airtel_money, etc.',
    phone_number VARCHAR(20),
    bank_name VARCHAR(100),
    bank_account VARCHAR(50),
    status ENUM('active','paused','completed','cancelled','failed') NOT NULL DEFAULT 'active',
    notes TEXT,
    created_by VARCHAR(50) COMMENT 'student_id or staff_id who created',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student (student_id),
    INDEX idx_status (status),
    INDEX idx_next_due (next_due_date)
);

-- Subscription deduction logs
CREATE TABLE IF NOT EXISTS igangaschoolofl_students_db.subscription_deductions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subscription_id INT NOT NULL,
    student_id VARCHAR(50) NOT NULL,
    installment_number INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    due_date DATE NOT NULL,
    processed_date DATETIME,
    status ENUM('pending','success','failed','skipped') NOT NULL DEFAULT 'pending',
    payment_reference VARCHAR(50),
    payment_id INT COMMENT 'FK to payments.id if successful',
    failure_reason TEXT,
    attempt_count INT DEFAULT 0,
    last_attempt_date DATETIME,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_subscription (subscription_id),
    INDEX idx_student (student_id),
    INDEX idx_status (status)
);

-- ==============================================================================
-- DATABASE 4: igangaschoolofl_website_db — Notifications System
-- ==============================================================================

-- Notifications: one row per notification event
CREATE TABLE IF NOT EXISTS igangaschoolofl_website_db.notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    url VARCHAR(500) DEFAULT '' COMMENT 'Link to click when notification is opened',
    type ENUM('info','warning','success','danger','announcement') NOT NULL DEFAULT 'info',
    icon VARCHAR(50) DEFAULT 'fas fa-bell',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notification reads: tracks per-user read status
CREATE TABLE IF NOT EXISTS igangaschoolofl_website_db.notification_reads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_id INT NOT NULL,
    user_id INT NOT NULL COMMENT 'FK to staffs_db.staff.id or students_db.students.id',
    user_type ENUM('staff','student') NOT NULL DEFAULT 'staff',
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_notif_user (notification_id, user_id, user_type),
    FOREIGN KEY (notification_id) REFERENCES igangaschoolofl_website_db.notifications(id) ON DELETE CASCADE,
    INDEX idx_user (user_id, user_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================================================================
-- Skills Lab — Staff Role Registration (staffs_db)
-- ==============================================================================

INSERT IGNORE INTO igangaschoolofl_staffs_db.staff_roles
    (`id`, `role_name`, `role_description`, `role_level`, `dashboard_path`, `permissions`, `created_at`, `updated_at`)
VALUES
    (53, 'Skills Lab Manager', 'Skills laboratory management including equipment, practical sessions, skills demonstrations, and consumables', 'Support', 'dashboards/skills-lab.php', '{"equipment": true, "checkout": true, "sessions": true, "skills": true, "consumables": true, "attendance": true, "incidents": true}', NOW(), NOW());

-- ==============================================================================
-- SKILLS LAB TABLES (students_db)
-- ==============================================================================

-- 1. Lab Equipment Inventory
CREATE TABLE IF NOT EXISTS igangaschoolofl_students_db.lab_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category ENUM('mannequin','model','instrument','furniture','consumable','other') NOT NULL DEFAULT 'other',
    quantity INT NOT NULL DEFAULT 1,
    available_quantity INT NOT NULL DEFAULT 1,
    condition_status ENUM('excellent','good','fair','poor') DEFAULT 'good',
    location VARCHAR(255),
    serial_number VARCHAR(100),
    purchase_date DATE,
    purchase_cost DECIMAL(12,2),
    supplier VARCHAR(255),
    last_maintenance_date DATE,
    next_maintenance_date DATE,
    status ENUM('active','maintenance','retired') DEFAULT 'active',
    image_url VARCHAR(500),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Equipment Checkouts
CREATE TABLE IF NOT EXISTS igangaschoolofl_students_db.lab_equipment_checkouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL,
    student_id VARCHAR(50) NOT NULL,
    checked_out_by INT NOT NULL COMMENT 'staff_id',
    checkout_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    expected_return_date DATE NOT NULL,
    actual_return_date DATETIME,
    quantity_checked_out INT NOT NULL DEFAULT 1,
    quantity_returned INT DEFAULT 0,
    purpose VARCHAR(255),
    notes TEXT,
    status ENUM('checked_out','returned','overdue','lost_damaged') DEFAULT 'checked_out',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES igangaschoolofl_students_db.lab_equipment(id) ON DELETE CASCADE,
    INDEX idx_student (student_id),
    INDEX idx_status (status),
    INDEX idx_expected_return (expected_return_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Practical Sessions
CREATE TABLE IF NOT EXISTS igangaschoolofl_students_db.lab_practical_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_code VARCHAR(50) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    instructor VARCHAR(255),
    program VARCHAR(255),
    year_level VARCHAR(50),
    semester VARCHAR(20),
    session_date DATE NOT NULL,
    start_time TIME,
    end_time TIME,
    location VARCHAR(255),
    max_students INT DEFAULT 30,
    status ENUM('scheduled','ongoing','completed','cancelled') DEFAULT 'scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date (session_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Skills Demonstrations
CREATE TABLE IF NOT EXISTS igangaschoolofl_students_db.lab_skills_demonstrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(50) NOT NULL,
    skill_name VARCHAR(255) NOT NULL,
    skill_category VARCHAR(100),
    instructor VARCHAR(255),
    date_demonstrated DATE NOT NULL DEFAULT (CURRENT_DATE),
    competency ENUM('exceeds_expectations','meets_expectations','needs_improvement','unsatisfactory') DEFAULT 'meets_expectations',
    attempt_number INT DEFAULT 1,
    notes TEXT,
    next_review_date DATE,
    verified_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_student (student_id),
    INDEX idx_skill (skill_name),
    INDEX idx_competency (competency)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Consumables
CREATE TABLE IF NOT EXISTS igangaschoolofl_students_db.lab_consumables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,
    category VARCHAR(100),
    quantity DECIMAL(10,2) NOT NULL DEFAULT 0,
    unit VARCHAR(50) NOT NULL DEFAULT 'pieces',
    min_stock_level DECIMAL(10,2) DEFAULT 10,
    unit_cost DECIMAL(10,2) DEFAULT 0,
    supplier VARCHAR(255),
    last_ordered_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_stock (quantity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Lab Attendance
CREATE TABLE IF NOT EXISTS igangaschoolofl_students_db.lab_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    student_id VARCHAR(50) NOT NULL,
    attendance_status ENUM('present','absent','late','excused') DEFAULT 'present',
    check_in_time TIME,
    check_out_time TIME,
    marked_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES igangaschoolofl_students_db.lab_practical_sessions(id) ON DELETE CASCADE,
    UNIQUE KEY uk_session_student (session_id, student_id),
    INDEX idx_student (student_id),
    INDEX idx_status (attendance_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Lab Incidents
CREATE TABLE IF NOT EXISTS igangaschoolofl_students_db.lab_incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_date DATE NOT NULL DEFAULT (CURRENT_DATE),
    incident_time TIME,
    reported_by INT,
    incident_type ENUM('injury','equipment_damage','safety_hazard','near_miss','other') NOT NULL DEFAULT 'other',
    severity ENUM('minor','moderate','serious','critical') DEFAULT 'minor',
    description TEXT NOT NULL,
    equipment_involved VARCHAR(255),
    student_involved VARCHAR(255),
    action_taken TEXT,
    status ENUM('open','investigating','resolved','closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date (incident_date),
    INDEX idx_type (incident_type),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================================================================
-- Skills Lab Manager User Account (staffs_db)
-- ==============================================================================

INSERT IGNORE INTO igangaschoolofl_staffs_db.staff
    (`id`, `staff_id`, `full_name`, `email`, `password`, `phone`, `position`, `department`, `role_id`, `status`, `hire_date`, `salary`, `address`, `emergency_contact_name`, `emergency_contact_phone`, `is_first_login`, `created_at`, `updated_at`)
VALUES
    (75, 'SKL001', 'Skills Lab Manager', 'skills-lab@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$VlgcTKefl1ANCgn87eD2we4/dnTWCxgtH9PqB7tNKqhrYfQO1vJmW', NULL, 'Skills Lab Manager', 'Skills Laboratory', 53, 'Active', CURDATE(), NULL, NULL, NULL, NULL, 1, NOW(), NOW());
