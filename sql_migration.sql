-- ==============================================================================
-- ISNM (Iganga School of Nursing & Midwifery) ERP System
-- SQL Migration File - Creates ALL missing tables across 4 databases
-- ==============================================================================

-- Safe helpers (MySQL-compatible, works without IF NOT EXISTS / IF NOT EXISTS)
DELIMITER //
DROP PROCEDURE IF EXISTS AddColIfMissing//
CREATE PROCEDURE AddColIfMissing(
    IN p_schema VARCHAR(255), IN p_table VARCHAR(255),
    IN p_col VARCHAR(255), IN p_def TEXT)
BEGIN
    DECLARE cnt INT DEFAULT 0;
    SELECT COUNT(*) INTO cnt FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = p_schema AND TABLE_NAME = p_table AND COLUMN_NAME = p_col;
    IF cnt = 0 THEN
        SET @s = CONCAT('ALTER TABLE `', p_schema, '`.`', p_table, '` ADD COLUMN `', p_col, '` ', p_def);
        PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END//

DROP PROCEDURE IF EXISTS AddIdxIfMissing//
CREATE PROCEDURE AddIdxIfMissing(
    IN p_schema VARCHAR(255), IN p_table VARCHAR(255),
    IN p_idx VARCHAR(255), IN p_cols TEXT)
BEGIN
    DECLARE cnt INT DEFAULT 0;
    SELECT COUNT(*) INTO cnt FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = p_schema AND TABLE_NAME = p_table AND INDEX_NAME = p_idx;
    IF cnt = 0 THEN
        SET @s = CONCAT('ALTER TABLE `', p_schema, '`.`', p_table, '` ADD INDEX `', p_idx, '` (', p_cols, ')');
        PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END//

DELIMITER ;

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
    reviewed_at DATETIME,
    additional_data TEXT COMMENT 'JSON-encoded additional form fields',
    academic_document_path VARCHAR(255),
    photo_path VARCHAR(255),
    uce_certificate_path VARCHAR(255),
    uace_certificate_path VARCHAR(255),
    unmeb_result_slip_path VARCHAR(255),
    unmeb_certificate_path VARCHAR(255),
    enrolment_certificate_path VARCHAR(255),
    practicing_license_path VARCHAR(255),
    academic_transcript_path VARCHAR(255)
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

-- ==============================================================================
-- Official Duties Table (staffs_db)
-- Dynamically manages director duties & responsibilities per role
-- ==============================================================================

CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.official_duties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL COMMENT 'FK to staff_roles.id',
    duty_title VARCHAR(255) NOT NULL,
    duty_icon VARCHAR(100) DEFAULT 'fas fa-tasks',
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role_id),
    INDEX idx_active (is_active),
    CONSTRAINT fk_duties_role FOREIGN KEY (role_id) REFERENCES igangaschoolofl_staffs_db.staff_roles(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed data: Director General (role_id=1)
INSERT IGNORE INTO igangaschoolofl_staffs_db.official_duties (role_id, duty_title, duty_icon, sort_order) VALUES
(1, 'Overall Institutional Leadership &amp; Strategic Direction', 'fas fa-sitemap', 1),
(1, 'Oversight of All Academic, Administrative &amp; Financial Operations', 'fas fa-eye', 2),
(1, 'Policy Formulation &amp; Implementation', 'fas fa-file-contract', 3),
(1, 'Staff Supervision &amp; Performance Management', 'fas fa-users-cog', 4),
(1, 'Budget Approval &amp; Financial Oversight', 'fas fa-check-double', 5),
(1, 'Stakeholder Engagement &amp; Institutional Representation', 'fas fa-handshake', 6),
(1, 'Quality Assurance &amp; Compliance', 'fas fa-certificate', 7),
(1, 'Student Welfare &amp; Institutional Discipline', 'fas fa-user-shield', 8),
(1, 'Public Relations &amp; Community Engagement', 'fas fa-bullhorn', 9),
(1, 'Strategic Planning &amp; Institutional Development', 'fas fa-chart-line', 10);

-- Seed data: Director Academics (role_id=4)
INSERT IGNORE INTO igangaschoolofl_staffs_db.official_duties (role_id, duty_title, duty_icon, sort_order) VALUES
(4, 'Curriculum Development &amp; Review', 'fas fa-book-open', 1),
(4, 'Academic Program Oversight', 'fas fa-graduation-cap', 2),
(4, 'Examination Management', 'fas fa-clipboard-list', 3),
(4, 'Academic Calendar Management', 'fas fa-calendar-alt', 4),
(4, 'Quality Assurance of Academic Programs', 'fas fa-check-double', 5),
(4, 'Lecturer Supervision &amp; Evaluation', 'fas fa-chalkboard-teacher', 6),
(4, 'Student Academic Progress Monitoring', 'fas fa-chart-line', 7),
(4, 'Academic Policy Development', 'fas fa-file-alt', 8),
(4, 'Timetable Coordination', 'fas fa-clock', 9),
(4, 'Academic Reporting &amp; Documentation', 'fas fa-print', 10);

-- Seed data: Director Finance (role_id=5)
INSERT IGNORE INTO igangaschoolofl_staffs_db.official_duties (role_id, duty_title, duty_icon, sort_order) VALUES
(5, 'Financial Planning &amp; Budget Management', 'fas fa-calculator', 1),
(5, 'Revenue Collection &amp; Reconciliation', 'fas fa-money-bill-wave', 2),
(5, 'Expense Tracking &amp; Control', 'fas fa-receipt', 3),
(5, 'Financial Reporting &amp; Analysis', 'fas fa-chart-pie', 4),
(5, 'Audit Coordination &amp; Compliance', 'fas fa-search-dollar', 5),
(5, 'Payroll Management', 'fas fa-wallet', 6),
(5, 'Fee Structure Development', 'fas fa-file-invoice', 7),
(5, 'Procurement Oversight', 'fas fa-truck-loading', 8),
(5, 'Bank Reconciliation', 'fas fa-university', 9),
(5, 'URA Tax Compliance', 'fas fa-file-invoice-dollar', 10);

-- Seed data: Director ICT (role_id=6)
INSERT IGNORE INTO igangaschoolofl_staffs_db.official_duties (role_id, duty_title, duty_icon, sort_order) VALUES
(6, 'Technology Infrastructure Management', 'fas fa-server', 1),
(6, 'IT Support &amp; Helpdesk Management', 'fas fa-headset', 2),
(6, 'Computer Lab Management', 'fas fa-desktop', 3),
(6, 'Network Administration', 'fas fa-network-wired', 4),
(6, 'Software &amp; License Management', 'fas fa-download', 5),
(6, 'Digital Learning Platform Management', 'fas fa-laptop-code', 6),
(6, 'ICT Policy Development', 'fas fa-shield-alt', 7),
(6, 'Cybersecurity &amp; Data Protection', 'fas fa-lock', 8),
(6, 'System Maintenance &amp; Upgrades', 'fas fa-tools', 9),
(6, 'ICT Asset Management', 'fas fa-boxes', 10);

-- Seed data: Director Admissions (role_id=27)
INSERT IGNORE INTO igangaschoolofl_staffs_db.official_duties (role_id, duty_title, duty_icon, sort_order) VALUES
(27, 'Student Admission &amp; Enrollment Management', 'fas fa-file-signature', 1),
(27, 'Application Processing &amp; Review', 'fas fa-check-circle', 2),
(27, 'Admission Requirements Management', 'fas fa-clipboard-check', 3),
(27, 'Intake Planning &amp; Coordination', 'fas fa-calendar-plus', 4),
(27, 'Admission Letters &amp; Documentation', 'fas fa-envelope-open-text', 5),
(27, 'Enrollment Confirmation &amp; Registration', 'fas fa-user-check', 6),
(27, 'Admission Policy Development', 'fas fa-file-contract', 7),
(27, 'Student Records Management', 'fas fa-folder-open', 8),
(27, 'Orientation Program Coordination', 'fas fa-handshake', 9),
(27, 'Admission Reporting &amp; Statistics', 'fas fa-chart-bar', 10);

-- ==============================================================================
-- DATABASE 3: igangaschoolofl_staffs_db (staffs_db) — New Tables
-- ==============================================================================

-- Recycle Bin: Soft-deleted items across all modules
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.recycle_bin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_table VARCHAR(255) NOT NULL COMMENT 'Source table where item was deleted from',
    original_id_column VARCHAR(255) NOT NULL DEFAULT 'id' COMMENT 'Primary key column name',
    original_id INT NOT NULL COMMENT 'The ID in the original table',
    item_title VARCHAR(500) NOT NULL COMMENT 'Readable title for the trashed item',
    item_description TEXT COMMENT 'Optional description/context',
    deleted_by INT DEFAULT 0 COMMENT 'staff.id who deleted it',
    deleted_by_name VARCHAR(255) DEFAULT '',
    deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_original (original_table, original_id),
    INDEX idx_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add soft-delete columns to existing tables
CALL AddColIfMissing('igangaschoolofl_staffs_db', 'receipt_templates', 'is_deleted', 'TINYINT(1) DEFAULT 0');
CALL AddColIfMissing('igangaschoolofl_staffs_db', 'receipt_templates', 'deleted_at', 'TIMESTAMP NULL DEFAULT NULL');
CALL AddColIfMissing('igangaschoolofl_staffs_db', 'document_templates', 'is_deleted', 'TINYINT(1) DEFAULT 0');
CALL AddColIfMissing('igangaschoolofl_staffs_db', 'document_templates', 'deleted_at', 'TIMESTAMP NULL DEFAULT NULL');
CALL AddColIfMissing('igangaschoolofl_staffs_db', 'staff_announcements', 'is_deleted', 'TINYINT(1) DEFAULT 0');
CALL AddColIfMissing('igangaschoolofl_staffs_db', 'staff_announcements', 'deleted_at', 'TIMESTAMP NULL DEFAULT NULL');

-- ==============================================================================
-- Application & Submission Routing
-- ==============================================================================

-- Route submissions to specific roles/departments
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.submission_routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    submission_type ENUM('application','volunteer','donation','contact','complaint','feedback') NOT NULL,
    route_to_role_id INT NOT NULL COMMENT 'FK to staff_roles.id',
    route_to_role_name VARCHAR(255) NOT NULL,
    route_order INT DEFAULT 0 COMMENT 'Routing priority order',
    notify_via ENUM('email','notification','both') DEFAULT 'both',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (submission_type),
    INDEX idx_role (route_to_role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Application review workflow
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.application_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL COMMENT 'FK to student_applications.id',
    reviewer_id INT NOT NULL COMMENT 'FK to staff.id',
    review_status ENUM('Pending','Reviewed','Approved','Rejected','Forwarded') DEFAULT 'Pending',
    review_notes TEXT,
    forwarded_to INT DEFAULT NULL COMMENT 'FK to staff.id if forwarded',
    forwarded_to_role VARCHAR(255) DEFAULT NULL,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_app (application_id),
    INDEX idx_reviewer (reviewer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default routes: applications → Admissions Director & Secretary
INSERT IGNORE INTO igangaschoolofl_staffs_db.submission_routes (submission_type, route_to_role_id, route_to_role_name, route_order, is_active) VALUES
('application', 27, 'Director Admissions', 1, 1),
('application', 22, 'Secretary', 2, 1),
('volunteer', 27, 'Director Admissions', 1, 1),
('volunteer', 22, 'Secretary', 2, 1),
('donation', 5, 'Director Finance', 1, 1),
('contact', 1, 'Director General', 1, 1),
('feedback', 1, 'Director General', 1, 1),
('complaint', 1, 'Director General', 1, 1);

-- ==============================================================================
-- Payment Routing & Notification
-- ==============================================================================

-- Payment notifications routing
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.payment_routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_type ENUM('fee_payment','registration','other') DEFAULT 'fee_payment',
    notify_role_id INT NOT NULL COMMENT 'FK to staff_roles.id',
    notify_role_name VARCHAR(255) NOT NULL,
    notify_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (payment_type),
    INDEX idx_role (notify_role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payment approval workflow
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.payment_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_id INT NOT NULL COMMENT 'FK to payments.id',
    approved_by INT NOT NULL COMMENT 'FK to staff.id',
    approval_status ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
    approval_notes TEXT,
    approved_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_payment (payment_id),
    INDEX idx_approver (approved_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default payment routes: notify Finance, DG, and all directors
INSERT IGNORE INTO igangaschoolofl_staffs_db.payment_routes (payment_type, notify_role_id, notify_role_name, notify_order, is_active) VALUES
('fee_payment', 5, 'Director Finance', 1, 1),
('fee_payment', 1, 'Director General', 2, 1),
('fee_payment', 4, 'Director Academics', 3, 1),
('fee_payment', 6, 'Director ICT', 4, 1),
('fee_payment', 27, 'Director Admissions', 5, 1);

-- ==============================================================================
-- Profile & Theme Settings
-- ==============================================================================

-- User theme preferences
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL UNIQUE COMMENT 'FK to staff.id',
    theme_id VARCHAR(50) DEFAULT 'default-blue',
    sidebar_collapsed TINYINT(1) DEFAULT 0,
    notifications_enabled TINYINT(1) DEFAULT 1,
    email_notifications TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_staff (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add bio column to staff_profiles if not exists
CALL AddColIfMissing('igangaschoolofl_staffs_db', 'staff_profiles', 'bio', 'TEXT DEFAULT NULL AFTER profile_picture');
CALL AddColIfMissing('igangaschoolofl_staffs_db', 'staff_profiles', 'department', 'VARCHAR(255) DEFAULT NULL AFTER bio');
CALL AddColIfMissing('igangaschoolofl_staffs_db', 'staff_profiles', 'phone', 'VARCHAR(50) DEFAULT NULL AFTER department');
CALL AddColIfMissing('igangaschoolofl_staffs_db', 'staff_profiles', 'updated_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER department');

-- ==============================================================================
-- Enhanced Document Management
-- ==============================================================================

-- Document print configurations
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.document_print_configs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_type VARCHAR(100) NOT NULL UNIQUE COMMENT 'e.g. receipt, transcript, certificate',
    page_size ENUM('A4','A5','Letter','Legal','Receipt') DEFAULT 'A4',
    orientation ENUM('portrait','landscape') DEFAULT 'portrait',
    logo_width_px INT DEFAULT 80,
    show_logo TINYINT(1) DEFAULT 1,
    show_header TINYINT(1) DEFAULT 1,
    show_footer TINYINT(1) DEFAULT 1,
    footer_text TEXT,
    margin_top_mm INT DEFAULT 15,
    margin_bottom_mm INT DEFAULT 15,
    margin_left_mm INT DEFAULT 15,
    margin_right_mm INT DEFAULT 15,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default print configs
INSERT IGNORE INTO igangaschoolofl_staffs_db.document_print_configs (document_type, page_size, orientation, logo_width_px) VALUES
('receipt', 'A4', 'portrait', 80),
('transcript', 'A4', 'portrait', 100),
('certificate', 'A4', 'landscape', 120),
('invoice', 'A4', 'portrait', 80),
('payslip', 'A5', 'portrait', 60),
('report', 'A4', 'portrait', 80),
('timetable', 'A4', 'landscape', 60),
('exam_schedule', 'A4', 'landscape', 60),
('leave_form', 'A4', 'portrait', 60),
('performance_review', 'A4', 'portrait', 70),
('id_card', 'A5', 'landscape', 50),
('contract', 'A4', 'portrait', 80);

-- ==============================================================================
-- Audit Trail Enhancements
-- ==============================================================================

-- Staff activity log with soft-delete tracking
CALL AddColIfMissing('igangaschoolofl_staffs_db', 'staff_activity_log', 'related_table', 'VARCHAR(255) DEFAULT NULL AFTER activity_description');
CALL AddColIfMissing('igangaschoolofl_staffs_db', 'staff_activity_log', 'related_id', 'INT DEFAULT NULL AFTER related_table');
CALL AddColIfMissing('igangaschoolofl_staffs_db', 'staff_activity_log', 'ip_address', 'VARCHAR(45) DEFAULT NULL AFTER related_id');
CALL AddIdxIfMissing('igangaschoolofl_staffs_db', 'staff_activity_log', 'idx_related', 'related_table, related_id');

-- ==============================================================================
-- INDEXES for performance on existing tables
-- ==============================================================================

CALL AddIdxIfMissing('igangaschoolofl_staffs_db', 'staff_audit_logs', 'idx_staff_action', 'staff_id, action');
CALL AddIdxIfMissing('igangaschoolofl_staffs_db', 'notifications', 'idx_user_read', 'user_id, is_read');
CALL AddIdxIfMissing('igangaschoolofl_staffs_db', 'staff_attendance', 'idx_staff_date', 'staff_id, attendance_date');

-- ==============================================================================
-- V2: Director Hierarchy, Approvals, Audit Trail, Performance, Alerts
-- ==============================================================================

-- ==============================================================================
-- 1. DIRECTOR HIERARCHY & DEPARTMENT OWNERSHIP
-- ==============================================================================

-- Hierarchy levels for the institutional chain of command
CALL AddColIfMissing('igangaschoolofl_staffs_db', 'staff_roles', 'hierarchy_level', 'INT DEFAULT 99 COMMENT ''Lower = higher authority (1=DG)''');
CALL AddColIfMissing('igangaschoolofl_staffs_db', 'staff_roles', 'reporting_to_role_id', 'INT DEFAULT NULL COMMENT ''FK to staff_roles.id''');
CALL AddColIfMissing('igangaschoolofl_staffs_db', 'staff_roles', 'can_approve_level', 'INT DEFAULT 0 COMMENT ''Max approval level this role can authorize''');
CALL AddColIfMissing('igangaschoolofl_staffs_db', 'staff_roles', 'is_executive', 'TINYINT(1) DEFAULT 0');

-- Update hierarchy levels for director roles
UPDATE igangaschoolofl_staffs_db.staff_roles SET hierarchy_level = 1, reporting_to_role_id = NULL,     can_approve_level = 10, is_executive = 1 WHERE id = 1;  -- Director General
UPDATE igangaschoolofl_staffs_db.staff_roles SET hierarchy_level = 2, reporting_to_role_id = 1,        can_approve_level = 8,  is_executive = 1 WHERE id = 3;  -- CEO / Chief Executive
UPDATE igangaschoolofl_staffs_db.staff_roles SET hierarchy_level = 3, reporting_to_role_id = 1,        can_approve_level = 6,  is_executive = 1 WHERE id = 4;  -- Director Academics
UPDATE igangaschoolofl_staffs_db.staff_roles SET hierarchy_level = 3, reporting_to_role_id = 1,        can_approve_level = 6,  is_executive = 1 WHERE id = 5;  -- Director Finance
UPDATE igangaschoolofl_staffs_db.staff_roles SET hierarchy_level = 3, reporting_to_role_id = 1,        can_approve_level = 5,  is_executive = 1 WHERE id = 6;  -- Director ICT
UPDATE igangaschoolofl_staffs_db.staff_roles SET hierarchy_level = 3, reporting_to_role_id = 1,        can_approve_level = 5,  is_executive = 1 WHERE id = 27; -- Director Admissions

-- Department ownership table: maps roles to departments they own/manage
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.director_departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL COMMENT 'FK to staff_roles.id',
    department_name VARCHAR(255) NOT NULL,
    department_code VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    is_primary TINYINT(1) DEFAULT 1 COMMENT 'Primary department for this role',
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES igangaschoolofl_staffs_db.staff_roles(id) ON DELETE CASCADE,
    INDEX idx_role (role_id),
    INDEX idx_dept (department_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed department ownership
INSERT IGNORE INTO igangaschoolofl_staffs_db.director_departments (role_id, department_name, department_code, description, is_primary) VALUES
(1,  'Institution-wide Oversight', 'EXEC', 'Overall institutional leadership and strategic direction', 1),
(3,  'Executive Management', 'CEO', 'Chief Executive Officer operations', 1),
(4,  'Academic Affairs', 'ACAD', 'Academic programs, curriculum, examinations, and quality assurance', 1),
(5,  'Finance & Accounts', 'FIN', 'Financial management, budgeting, revenue, and expenditure control', 1),
(6,  'ICT & Systems', 'ICT', 'Information technology infrastructure, systems, and cybersecurity', 1),
(27, 'Admissions & Enrollment', 'ADM', 'Student admissions, application processing, and enrollment', 1);

-- Delegation / acting director records
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.delegation_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    delegator_staff_id INT NOT NULL COMMENT 'FK to staff.id - the person delegating authority',
    delegate_staff_id INT NOT NULL COMMENT 'FK to staff.id - the person receiving authority',
    delegated_role_id INT NOT NULL COMMENT 'FK to staff_roles.id - which role authority is delegated for',
    delegation_type ENUM('acting','temporary','specific_task') DEFAULT 'temporary',
    start_date DATETIME NOT NULL,
    end_date DATETIME,
    scope_of_authority TEXT COMMENT 'What powers are delegated (JSON)',
    reason TEXT,
    status ENUM('Active','Expired','Revoked') DEFAULT 'Active',
    approved_by INT COMMENT 'FK to staff.id - DG approval',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_delegator (delegator_staff_id),
    INDEX idx_delegate (delegate_staff_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================================================================
-- 2. APPROVAL WORKFLOW SYSTEM
-- ==============================================================================

-- Workflow definitions
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.approval_workflows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workflow_name VARCHAR(255) NOT NULL,
    workflow_code VARCHAR(50) NOT NULL UNIQUE COMMENT 'e.g. FINANCE_REQUEST, STUDENT_ISSUE, ADMISSION_DECISION',
    description TEXT,
    category ENUM('Finance','Academic','Admissions','Student Affairs','System','HR','Other') NOT NULL DEFAULT 'Other',
    requires_final_approval TINYINT(1) DEFAULT 0 COMMENT 'Requires DG final sign-off',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Workflow stages (the steps in each workflow)
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.approval_stages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workflow_id INT NOT NULL,
    stage_order INT NOT NULL COMMENT 'Sequence number (1,2,3...)',
    stage_name VARCHAR(255) NOT NULL COMMENT 'e.g. Request, Review, Recommendation, Approval',
    stage_code ENUM('request','review','recommendation','approval','final_approval','rejection') NOT NULL,
    assigned_role_id INT DEFAULT NULL COMMENT 'FK to staff_roles.id - which role handles this stage',
    assigned_staff_id INT DEFAULT NULL COMMENT 'FK to staff.id - specific person (optional)',
    can_escalate TINYINT(1) DEFAULT 0,
    escalate_after_hours INT DEFAULT 48 COMMENT 'Auto-escalate after N hours',
    escalation_role_id INT DEFAULT NULL COMMENT 'FK to staff_roles.id - escalate to',
    required_notes TINYINT(1) DEFAULT 0 COMMENT 'Requires notes/justification',
    allow_rejection TINYINT(1) DEFAULT 1,
    reject_requires_reason TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (workflow_id) REFERENCES igangaschoolofl_staffs_db.approval_workflows(id) ON DELETE CASCADE,
    UNIQUE KEY uk_workflow_order (workflow_id, stage_order),
    INDEX idx_role (assigned_role_id),
    INDEX idx_stage_code (stage_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Approval requests (actual items flowing through workflow)
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.approval_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workflow_id INT NOT NULL,
    request_number VARCHAR(50) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    priority ENUM('Low','Medium','High','Critical') DEFAULT 'Medium',
    requester_id INT NOT NULL COMMENT 'FK to staff.id',
    requester_name VARCHAR(255),
    requester_role VARCHAR(255),
    current_stage_id INT DEFAULT NULL COMMENT 'FK to approval_stages.id - current stage',
    current_stage_order INT DEFAULT 0,
    status ENUM('Draft','Active','Approved','Rejected','Cancelled','Escalated') DEFAULT 'Draft',
    reference_type VARCHAR(100) COMMENT 'Type of record this relates to',
    reference_id INT COMMENT 'ID of related record',
    reference_url VARCHAR(500) COMMENT 'Link to related record',
    final_approval_by INT DEFAULT NULL COMMENT 'FK to staff.id',
    final_approval_at DATETIME DEFAULT NULL,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (workflow_id) REFERENCES igangaschoolofl_staffs_db.approval_workflows(id),
    INDEX idx_status (status),
    INDEX idx_requester (requester_id),
    INDEX idx_priority (priority),
    INDEX idx_ref (reference_type, reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Individual actions taken on each request
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.approval_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    stage_id INT NOT NULL,
    action_by INT NOT NULL COMMENT 'FK to staff.id',
    action_type ENUM('submit','review','recommend','approve','reject','escalate','return','cancel') NOT NULL,
    comments TEXT,
    notes TEXT COMMENT 'Internal notes',
    decision ENUM('Pending','Approved','Rejected','Returned','Escalated') DEFAULT 'Pending',
    previous_stage_order INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES igangaschoolofl_staffs_db.approval_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (stage_id) REFERENCES igangaschoolofl_staffs_db.approval_stages(id),
    INDEX idx_request (request_id),
    INDEX idx_action_by (action_by),
    INDEX idx_decision (decision)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed workflows
INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_workflows (workflow_name, workflow_code, description, category, requires_final_approval) VALUES
('Finance Request', 'FINANCE_REQUEST', 'Budget approvals, expenditure requests, procurement', 'Finance', 1),
('Student Issue Resolution', 'STUDENT_ISSUE', 'Student complaints, disciplinary appeals, special requests', 'Student Affairs', 0),
('Admission Decision', 'ADMISSION_DECISION', 'Admission approvals, special entry, transfers', 'Admissions', 1),
('Academic Decision', 'ACADEMIC_DECISION', 'Curriculum changes, exam adjustments, academic appeals', 'Academic', 0),
('System Change', 'SYSTEM_CHANGE', 'System configuration, data access, security changes', 'System', 1),
('HR Request', 'HR_REQUEST', 'Staff hiring, leave approvals, performance reviews', 'HR', 0);

-- Seed stages for Finance Request workflow (id=1)
INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_stages (workflow_id, stage_order, stage_name, stage_code, assigned_role_id, required_notes) VALUES
(1, 1, 'Request Submission', 'request', 5, 1),
(1, 2, 'Finance Review', 'review', 5, 1),
(1, 3, 'Director Recommendation', 'recommendation', 5, 1),
(1, 4, 'Executive Approval', 'approval', 1, 1),
(1, 5, 'Final Sign-off', 'final_approval', 1, 0);

-- Seed stages for Student Issue workflow (id=2)
INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_stages (workflow_id, stage_order, stage_name, stage_code, assigned_role_id, required_notes) VALUES
(2, 1, 'Issue Reported', 'request', 27, 1),
(2, 2, 'Department Review', 'review', 4, 1),
(2, 3, 'Director Recommendation', 'recommendation', 4, 1),
(2, 4, 'Final Resolution', 'approval', 1, 0);

-- Seed stages for Admission Decision workflow (id=3)
INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_stages (workflow_id, stage_order, stage_name, stage_code, assigned_role_id, required_notes) VALUES
(3, 1, 'Application Review', 'request', 27, 1),
(3, 2, 'Admissions Recommendation', 'recommendation', 27, 1),
(3, 3, 'Director Approval', 'approval', 4, 1),
(3, 4, 'Final Authorization', 'final_approval', 1, 0);

-- Seed stages for Academic Decision workflow (id=4)
INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_stages (workflow_id, stage_order, stage_name, stage_code, assigned_role_id, required_notes) VALUES
(4, 1, 'Request Submission', 'request', 4, 1),
(4, 2, 'Academic Review', 'review', 4, 1),
(4, 3, 'Director Approval', 'approval', 4, 1);

-- Seed stages for System Change workflow (id=5)
INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_stages (workflow_id, stage_order, stage_name, stage_code, assigned_role_id, required_notes) VALUES
(5, 1, 'Change Request', 'request', 6, 1),
(5, 2, 'ICT Review', 'review', 6, 1),
(5, 3, 'Director Approval', 'approval', 6, 1),
(5, 4, 'Executive Authorization', 'final_approval', 1, 0);

-- Seed stages for HR Request workflow (id=6)
INSERT IGNORE INTO igangaschoolofl_staffs_db.approval_stages (workflow_id, stage_order, stage_name, stage_code, assigned_role_id, required_notes) VALUES
(6, 1, 'HR Request', 'request', 7, 1),
(6, 2, 'HR Review', 'review', 7, 1),
(6, 3, 'Director Approval', 'approval', 1, 1);

-- ==============================================================================
-- 3. COMPREHENSIVE AUDIT TRAIL SYSTEM
-- ==============================================================================

-- Enhanced audit trail with full detail tracking
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.audit_trail (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL COMMENT 'FK to staff.id',
    staff_name VARCHAR(255),
    role_name VARCHAR(255),
    action VARCHAR(100) NOT NULL COMMENT 'e.g. CREATE, UPDATE, DELETE, APPROVE, REJECT, LOGIN, LOGOUT',
    category ENUM('User','Student','Finance','Academic','Admissions','System','Document','Settings','Approval','Alert','Other') DEFAULT 'Other',
    description TEXT,
    table_affected VARCHAR(255),
    record_id INT,
    record_identifier VARCHAR(255) COMMENT 'Human-readable record identifier',
    previous_values JSON COMMENT 'Previous state of changed fields',
    new_values JSON COMMENT 'New state of changed fields',
    ip_address VARCHAR(45),
    user_agent TEXT,
    session_id VARCHAR(100),
    request_method VARCHAR(10),
    request_uri VARCHAR(500),
    is_deleted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_staff (staff_id),
    INDEX idx_action (action),
    INDEX idx_category (category),
    INDEX idx_table (table_affected),
    INDEX idx_record (record_id),
    INDEX idx_created (created_at),
    INDEX idx_staff_date (staff_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================================================================
-- 4. DIRECTOR PERFORMANCE MONITORING
-- ==============================================================================

-- Department targets and KPIs
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.department_targets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_code VARCHAR(50) NOT NULL,
    fiscal_year VARCHAR(20) NOT NULL,
    target_name VARCHAR(255) NOT NULL,
    target_category ENUM('academic','financial','admissions','staff','operational','compliance','other') NOT NULL DEFAULT 'other',
    target_value DECIMAL(14,2) NOT NULL DEFAULT 0,
    target_unit VARCHAR(50) DEFAULT 'count',
    achieved_value DECIMAL(14,2) DEFAULT 0,
    target_period ENUM('monthly','quarterly','semester','annual') DEFAULT 'semester',
    period_start DATE,
    period_end DATE,
    weight INT DEFAULT 1 COMMENT 'Importance weight for scoring',
    status ENUM('Not Started','In Progress','Achieved','Exceeded','Missed') DEFAULT 'Not Started',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_dept (department_code),
    INDEX idx_year (fiscal_year),
    INDEX idx_category (target_category),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Director performance reviews
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.director_performance_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL COMMENT 'FK to staff.id',
    role_id INT NOT NULL COMMENT 'FK to staff_roles.id',
    review_period VARCHAR(50) NOT NULL COMMENT 'e.g. Q1 2026, Semester 1 2026',
    review_start DATE,
    review_end DATE,
    overall_score DECIMAL(5,2) DEFAULT 0 COMMENT '0-100 score',
    tasks_completed INT DEFAULT 0,
    tasks_pending INT DEFAULT 0,
    tasks_delayed INT DEFAULT 0,
    reports_submitted INT DEFAULT 0,
    issues_resolved INT DEFAULT 0,
    performance_rating ENUM('Excellent','Good','Satisfactory','Needs Improvement','Poor') DEFAULT 'Satisfactory',
    reviewer_id INT DEFAULT NULL COMMENT 'FK to staff.id (usually DG)',
    review_notes TEXT,
    status ENUM('Active','Under Review','Completed','Appealed') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_staff (staff_id),
    INDEX idx_period (review_period),
    INDEX idx_rating (performance_rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Director report submissions tracker
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.director_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL COMMENT 'FK to staff.id',
    report_type ENUM('weekly','monthly','quarterly','semester','annual','incident','special') NOT NULL DEFAULT 'monthly',
    report_title VARCHAR(255) NOT NULL,
    report_period VARCHAR(50),
    summary TEXT,
    report_data JSON COMMENT 'Structured report data',
    file_path VARCHAR(500),
    is_submitted TINYINT(1) DEFAULT 0,
    submitted_at DATETIME DEFAULT NULL,
    reviewed_by INT DEFAULT NULL,
    review_status ENUM('Pending','Reviewed','Needs Revision','Accepted') DEFAULT 'Pending',
    review_comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_staff (staff_id),
    INDEX idx_type (report_type),
    INDEX idx_submitted (is_submitted)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================================================================
-- 5. INTELLIGENT MANAGEMENT ALERTS
-- ==============================================================================

CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.institutional_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_title VARCHAR(255) NOT NULL,
    alert_message TEXT NOT NULL,
    alert_type ENUM('warning','danger','info','success','critical') NOT NULL DEFAULT 'info',
    priority ENUM('Low','Medium','High','Critical') NOT NULL DEFAULT 'Medium',
    category ENUM('attendance','academic','finance','admissions','system','staff','compliance','approval','other') NOT NULL DEFAULT 'other',
    department_code VARCHAR(50) DEFAULT NULL COMMENT 'NULL = institution-wide',
    source VARCHAR(100) COMMENT 'Auto-generated or manual source identifier',
    source_url VARCHAR(500) COMMENT 'Link to relevant page',
    is_auto_generated TINYINT(1) DEFAULT 0,
    is_read TINYINT(1) DEFAULT 0,
    is_resolved TINYINT(1) DEFAULT 0,
    resolved_at DATETIME DEFAULT NULL,
    resolved_by INT DEFAULT NULL,
    expires_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (alert_type),
    INDEX idx_priority (priority),
    INDEX idx_category (category),
    INDEX idx_dept (department_code),
    INDEX idx_read (is_read),
    INDEX idx_resolved (is_resolved),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Alert recipients: who should see which alerts
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.alert_recipients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_id INT NOT NULL,
    role_id INT DEFAULT NULL COMMENT 'FK to staff_roles.id - NULL for all',
    staff_id INT DEFAULT NULL COMMENT 'FK to staff.id - specific person',
    is_acknowledged TINYINT(1) DEFAULT 0,
    acknowledged_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (alert_id) REFERENCES igangaschoolofl_staffs_db.institutional_alerts(id) ON DELETE CASCADE,
    INDEX idx_alert (alert_id),
    INDEX idx_role (role_id),
    INDEX idx_staff (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================================================================
-- 6. COMPLIANCE TRACKING
-- ==============================================================================

CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.compliance_requirements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requirement_name VARCHAR(255) NOT NULL,
    description TEXT,
    category ENUM('accreditation','regulatory','safety','academic','financial','legal','other') DEFAULT 'regulatory',
    frequency ENUM('once','annual','semester','quarterly','monthly','ongoing') DEFAULT 'annual',
    due_date DATE,
    assigned_role_id INT COMMENT 'FK to staff_roles.id',
    assigned_staff_id INT COMMENT 'FK to staff.id',
    status ENUM('Not Started','In Progress','Compliant','Non-Compliant','Overdue') DEFAULT 'Not Started',
    evidence_path VARCHAR(500),
    reviewed_by INT,
    reviewed_at DATETIME,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_status (status),
    INDEX idx_due (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================================================================
-- 7. INSTITUTIONAL RISK REGISTER
-- ==============================================================================

CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.institutional_risks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    risk_title VARCHAR(255) NOT NULL,
    risk_description TEXT,
    risk_category ENUM('financial','academic','operational','reputational','compliance','strategic','security') NOT NULL DEFAULT 'operational',
    likelihood ENUM('Rare','Unlikely','Possible','Likely','Almost Certain') DEFAULT 'Possible',
    impact ENUM('Insignificant','Minor','Moderate','Major','Catastrophic') DEFAULT 'Moderate',
    risk_score INT GENERATED ALWAYS AS (
        CASE
            WHEN likelihood = 'Rare' THEN 1 WHEN likelihood = 'Unlikely' THEN 2
            WHEN likelihood = 'Possible' THEN 3 WHEN likelihood = 'Likely' THEN 4
            WHEN likelihood = 'Almost Certain' THEN 5 ELSE 3
        END *
        CASE
            WHEN impact = 'Insignificant' THEN 1 WHEN impact = 'Minor' THEN 2
            WHEN impact = 'Moderate' THEN 3 WHEN impact = 'Major' THEN 4
            WHEN impact = 'Catastrophic' THEN 5 ELSE 3
        END
    ) STORED,
    mitigation_strategy TEXT,
    contingency_plan TEXT,
    owner_staff_id INT COMMENT 'FK to staff.id',
    status ENUM('Identified','Assessed','Mitigated','Monitored','Closed') DEFAULT 'Identified',
    review_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (risk_category),
    INDEX idx_score (risk_score),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================================================================
-- 8. EXECUTIVE DASHBOARD CONFIG
-- ==============================================================================

-- Saved dashboard layouts per user
CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.dashboard_configs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    config_key VARCHAR(100) NOT NULL,
    config_value JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_staff_key (staff_id, config_key),
    INDEX idx_staff (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================================================================
-- 9. DATA ACCESS / OWNERSHIP RULES
-- ==============================================================================

CREATE TABLE IF NOT EXISTS igangaschoolofl_staffs_db.data_ownership_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL COMMENT 'FK to staff_roles.id',
    department_code VARCHAR(50) NOT NULL,
    access_level ENUM('full','read','write','none') NOT NULL DEFAULT 'read',
    data_category ENUM('student','academic','financial','admission','staff','system','all') NOT NULL DEFAULT 'all',
    can_export TINYINT(1) DEFAULT 0,
    can_delete TINYINT(1) DEFAULT 0,
    can_approve TINYINT(1) DEFAULT 0,
    is_owner TINYINT(1) DEFAULT 0 COMMENT 'This role owns this data category',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_role (role_id),
    INDEX idx_dept (department_code),
    UNIQUE KEY uk_role_dept_category (role_id, department_code, data_category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed data ownership rules
INSERT IGNORE INTO igangaschoolofl_staffs_db.data_ownership_rules (role_id, department_code, access_level, data_category, can_export, can_delete, can_approve, is_owner) VALUES
(1,  'EXEC', 'full', 'all', 1, 1, 1, 1),
(1,  'ACAD', 'full', 'all', 1, 1, 1, 0),
(1,  'FIN',  'full', 'all', 1, 1, 1, 0),
(1,  'ADM',  'full', 'all', 1, 1, 1, 0),
(1,  'ICT',  'full', 'all', 1, 1, 1, 0),
(3,  'CEO',  'full', 'all', 1, 0, 1, 1),
(4,  'ACAD', 'full', 'academic', 1, 0, 1, 1),
(4,  'ACAD', 'read', 'student', 1, 0, 0, 0),
(5,  'FIN',  'full', 'financial', 1, 0, 1, 1),
(5,  'FIN',  'read', 'student', 1, 0, 0, 0),
(6,  'ICT',  'full', 'system', 1, 0, 0, 1),
(6,  'ICT',  'read', 'all', 1, 0, 0, 0),
(27, 'ADM',  'full', 'admission', 1, 0, 1, 1),
(27, 'ADM',  'read', 'student', 1, 0, 0, 0);

-- ==============================================================================
-- END OF V2 MIGRATION
-- ==============================================================================
