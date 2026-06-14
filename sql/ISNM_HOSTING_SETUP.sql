-- =============================================================
-- ISNM MASTER SETUP SQL - SINGLE FILE IMPORT FOR PHPMYADMIN
-- Database: igangaschoolofl_students_db, igangaschoolofl_staffs_db, igangaschoolofl_website_db, igangaschoolofl_ict
-- ============================================================

-- Step 1: Create all four databases
CREATE DATABASE IF NOT EXISTS `igangaschoolofl_staffs_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `igangaschoolofl_students_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `igangaschoolofl_website_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS `igangaschoolofl_ict` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

SELECT 'Databases created successfully' as status;

-- Step 2: Staffs Database (MUST be created BEFORE students for cross-database FK references)
-- ISNM Final Complete Staffs Database Schema
-- Database: igangaschoolofl_staffs_db
-- Professional unified authentication system for all staff with role-based access control

-- Disable foreign key checks to allow dropping tables with dependencies
SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables if they exist (for fresh installation)
-- Drop child tables before parents to avoid foreign key constraint errors
DROP TABLE IF EXISTS receipt_templates;
DROP TABLE IF EXISTS staff_documents;
DROP TABLE IF EXISTS staff_salaries;
DROP TABLE IF EXISTS payroll_records;
DROP TABLE IF EXISTS generated_documents;
DROP TABLE IF EXISTS document_templates;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS dashboard_updates;
DROP TABLE IF EXISTS error_logs;
DROP TABLE IF EXISTS document_generation_log;
DROP TABLE IF EXISTS activity_log;
DROP TABLE IF EXISTS cache_management;
DROP TABLE IF EXISTS api_keys;
DROP TABLE IF EXISTS real_time_updates;
DROP TABLE IF EXISTS analytics_cache;
DROP TABLE IF EXISTS search_index;
DROP TABLE IF EXISTS data_sync_status;
DROP TABLE IF EXISTS performance_metrics;
DROP TABLE IF EXISTS smart_suggestions;
DROP TABLE IF EXISTS email_notifications_queue;
DROP TABLE IF EXISTS user_preferences;
DROP TABLE IF EXISTS advanced_reports;
DROP TABLE IF EXISTS system_logs;
DROP TABLE IF EXISTS backup_management;
DROP TABLE IF EXISTS security_incidents;
DROP TABLE IF EXISTS security_patrols;
DROP TABLE IF EXISTS access_control_logs;
DROP TABLE IF EXISTS security_equipment;
DROP TABLE IF EXISTS emergency_contacts;
DROP TABLE IF EXISTS student_welfare_cases;
DROP TABLE IF EXISTS counseling_sessions;
DROP TABLE IF EXISTS room_inspections;
DROP TABLE IF EXISTS duty_rosters;
DROP TABLE IF EXISTS visitor_logs;
DROP TABLE IF EXISTS student_activities;
DROP TABLE IF EXISTS vehicles;
DROP TABLE IF EXISTS trip_logs;
DROP TABLE IF EXISTS fuel_management;
DROP TABLE IF EXISTS route_schedules;
DROP TABLE IF EXISTS student_health_records;
DROP TABLE IF EXISTS health_incidents;
DROP TABLE IF EXISTS meal_tracking;
DROP TABLE IF EXISTS lab_equipment_maintenance;
DROP TABLE IF EXISTS lab_safety_records;
DROP TABLE IF EXISTS chemical_inventory;
DROP TABLE IF EXISTS skills_lab_sessions;
DROP TABLE IF EXISTS skills_laboratory;
DROP TABLE IF EXISTS it_infrastructure;
DROP TABLE IF EXISTS ura_reporting;
DROP TABLE IF EXISTS partnerships;
DROP TABLE IF EXISTS accreditation_management;
DROP TABLE IF EXISTS quality_assurance;
DROP TABLE IF EXISTS research_projects;
DROP TABLE IF EXISTS library_transactions;
DROP TABLE IF EXISTS library_management;
DROP TABLE IF EXISTS hostel_allocations;
DROP TABLE IF EXISTS hostel_management;
DROP TABLE IF EXISTS student_discipline;
DROP TABLE IF EXISTS clinical_placements;
DROP TABLE IF EXISTS student_attendance;
DROP TABLE IF EXISTS examination_records;
DROP TABLE IF EXISTS course_registrations;
DROP TABLE IF EXISTS student_academic_profiles;
DROP TABLE IF EXISTS student_admissions;
DROP TABLE IF EXISTS staff_resignations;
DROP TABLE IF EXISTS staff_promotions;
DROP TABLE IF EXISTS compliance_records;
DROP TABLE IF EXISTS disciplinary_records;
DROP TABLE IF EXISTS staff_contracts;
DROP TABLE IF EXISTS recruitment_applications;
DROP TABLE IF EXISTS recruitment_jobs;
DROP TABLE IF EXISTS fee_adjustments;
DROP TABLE IF EXISTS sponsorships;
DROP TABLE IF EXISTS inventory_transactions;
DROP TABLE IF EXISTS inventory;
DROP TABLE IF EXISTS general_ledger;
DROP TABLE IF EXISTS expenditure_records;
DROP TABLE IF EXISTS budget_records;
DROP TABLE IF EXISTS payment_records;
DROP TABLE IF EXISTS invoice_records;
DROP TABLE IF EXISTS fee_structures;
DROP TABLE IF EXISTS staff_activity_log;
DROP TABLE IF EXISTS staff_dashboard_access;
DROP TABLE IF EXISTS staff_password_resets;
DROP TABLE IF EXISTS staff_login_attempts;
DROP TABLE IF EXISTS staff_login_sessions;
DROP TABLE IF EXISTS staff_audit_logs;
DROP TABLE IF EXISTS staff_sessions;
DROP TABLE IF EXISTS staff_permissions;
DROP TABLE IF EXISTS staff_access_control;
DROP TABLE IF EXISTS staff_departments;
DROP TABLE IF EXISTS staff_profiles;
DROP TABLE IF EXISTS students;
DROP TABLE IF EXISTS financial_records;
DROP TABLE IF EXISTS fee_accounts;
DROP TABLE IF EXISTS course_assignments;
DROP TABLE IF EXISTS academic_records;
DROP TABLE IF EXISTS staff_attendance;
DROP TABLE IF EXISTS staff_leave_requests;
DROP TABLE IF EXISTS staff_performance;
DROP TABLE IF EXISTS staff_training;
DROP TABLE IF EXISTS staff_documents;
DROP TABLE IF EXISTS system_settings;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS staff;
DROP TABLE IF EXISTS staff_roles;
DROP TABLE IF EXISTS grading_notifications;
DROP TABLE IF EXISTS grade_change_history;
DROP TABLE IF EXISTS academic_calendar;
DROP TABLE IF EXISTS result_publication;
DROP TABLE IF EXISTS transcript_generation_log;
DROP TABLE IF EXISTS grading_approval_workflow;
DROP TABLE IF EXISTS grade_scales;
DROP TABLE IF EXISTS inventory_reports;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Staff Roles Table
CREATE TABLE staff_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(100) NOT NULL UNIQUE,
    role_description TEXT,
    role_level ENUM('Executive', 'Management', 'Academic', 'Support', 'Administrative') DEFAULT 'Academic',
    dashboard_path VARCHAR(255),
    permissions JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role_name (role_name),
    INDEX idx_role_level (role_level)
);

-- 2. Students Table
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_number VARCHAR(50) NOT NULL UNIQUE,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    address TEXT,
    program VARCHAR(100) NOT NULL,
    year_of_study INT DEFAULT 1,
    semester VARCHAR(50) DEFAULT 'Semester 1',
    admission_date DATE,
    status ENUM('Active', 'Inactive', 'Graduated', 'Suspended', 'Withdrawn') DEFAULT 'Active',
    guardian_name VARCHAR(200),
    guardian_phone VARCHAR(20),
    guardian_email VARCHAR(100),
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student_number (student_number),
    INDEX idx_email (email),
    INDEX idx_program (program),
    INDEX idx_status (status)
);

-- 3. Staff Table (Enhanced with authentication)
CREATE TABLE staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(50) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    position VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    role_id INT,
    status ENUM('Active', 'Inactive', 'On Leave', 'Suspended') DEFAULT 'Active',
    hire_date DATE,
    salary DECIMAL(10,2),
    address TEXT,
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    last_login TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    last_failed_attempt TIMESTAMP NULL,
    password_changed BOOLEAN DEFAULT FALSE,
    is_first_login BOOLEAN DEFAULT TRUE,
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    two_factor_secret VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES staff_roles(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_staff_id (staff_id),
    INDEX idx_email (email),
    INDEX idx_position (position),
    INDEX idx_department (department),
    INDEX idx_status (status),
    INDEX idx_role_id (role_id)
);

-- 3. Staff Profiles Table
CREATE TABLE staff_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    bio TEXT,
    profile_picture VARCHAR(255),
    qualifications TEXT,
    experience TEXT,
    skills TEXT,
    achievements TEXT,
    education_background TEXT,
    certifications TEXT,
    professional_memberships TEXT,
    research_interests TEXT,
    publications TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_staff_id (staff_id)
);

-- 4. Staff Departments Table
CREATE TABLE staff_departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL UNIQUE,
    department_code VARCHAR(20) NOT NULL UNIQUE,
    description TEXT,
    head_of_department_id INT,
    parent_department_id INT NULL,
    department_level ENUM('Executive', 'Management', 'Academic', 'Support', 'Administrative') DEFAULT 'Academic',
    budget DECIMAL(15,2),
    location VARCHAR(255),
    contact_email VARCHAR(100),
    contact_phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (head_of_department_id) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (parent_department_id) REFERENCES staff_departments(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_department_name (department_name),
    INDEX idx_department_code (department_code),
    INDEX idx_parent (parent_department_id),
    INDEX idx_level (department_level)
);

-- 5. Staff Permissions Table
CREATE TABLE staff_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    module VARCHAR(100) NOT NULL,
    permission_level ENUM('Read', 'Write', 'Delete', 'Admin', 'Super Admin') DEFAULT 'Read',
    granted_by INT,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_staff_id (staff_id),
    INDEX idx_module (module),
    INDEX idx_permission_level (permission_level),
    INDEX idx_is_active (is_active)
);

-- 6. Staff Login Sessions Table (Enhanced)
CREATE TABLE staff_login_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    session_token VARCHAR(255) NOT NULL UNIQUE,
    device_info TEXT,
    browser_info TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_staff_id (staff_id),
    INDEX idx_session_token (session_token),
    INDEX idx_expires_at (expires_at),
    INDEX idx_is_active (is_active)
);

-- 7. Staff Login Attempts Table
CREATE TABLE staff_login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    success BOOLEAN DEFAULT FALSE,
    failure_reason VARCHAR(255),
    staff_id INT NULL,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_email (email),
    INDEX idx_attempt_time (attempt_time),
    INDEX idx_success (success),
    INDEX idx_staff_id (staff_id)
);

-- 8. Staff Password Resets Table
CREATE TABLE staff_password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    reset_token VARCHAR(255) NOT NULL UNIQUE,
    reset_requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_used BOOLEAN DEFAULT FALSE,
    ip_address VARCHAR(45),
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_staff_id (staff_id),
    INDEX idx_reset_token (reset_token),
    INDEX idx_expires_at (expires_at),
    INDEX idx_is_used (is_used)
);

-- 9. Staff Activity Log Table
CREATE TABLE staff_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    activity_type ENUM('Login', 'Logout', 'Dashboard Access', 'Data View', 'Data Edit', 'Data Delete', 'Export', 'Print', 'Settings Change', 'Account Created', 'Account Updated') NOT NULL,
    activity_description TEXT,
    module_accessed VARCHAR(100),
    record_id INT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_staff_id (staff_id),
    INDEX idx_activity_type (activity_type),
    INDEX idx_module_accessed (module_accessed),
    INDEX idx_created_at (created_at)
);

-- 10. Staff Dashboard Access Control Table
CREATE TABLE staff_dashboard_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    dashboard_path VARCHAR(255) NOT NULL,
    access_level ENUM('Full', 'Read Only', 'Limited') DEFAULT 'Full',
    granted_by INT NULL,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_staff_id (staff_id),
    INDEX idx_dashboard_path (dashboard_path),
    INDEX idx_access_level (access_level),
    INDEX idx_is_active (is_active)
);

-- 11. Staff Audit Logs Table
CREATE TABLE staff_audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    table_affected VARCHAR(100),
    record_id INT,
    old_values TEXT,
    new_values TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_staff_id (staff_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
);

-- 12. Financial Records Table
CREATE TABLE financial_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_type ENUM('Collection', 'Payment', 'Refund', 'Adjustment') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'UGX',
    description TEXT,
    reference_number VARCHAR(100),
    payment_method VARCHAR(50),
    recorded_by INT,
    student_id INT NULL,
    staff_id INT NULL,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (recorded_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_record_type (record_type),
    INDEX idx_amount (amount),
    INDEX idx_transaction_date (transaction_date),
    INDEX idx_recorded_by (recorded_by)
);

-- 13. Fee Accounts Table
CREATE TABLE fee_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    fee_type VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    due_date DATE,
    paid_amount DECIMAL(10,2) DEFAULT 0,
    balance DECIMAL(10,2) GENERATED ALWAYS AS (amount - paid_amount) STORED,
    status ENUM('Unpaid', 'Partially Paid', 'Paid', 'Overdue') DEFAULT 'Unpaid',
    payment_method VARCHAR(50),
    receipt_number VARCHAR(50),
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_fee_type (fee_type),
    INDEX idx_status (status),
    INDEX idx_due_date (due_date)
);

-- 14. Course Assignments Table
CREATE TABLE course_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lecturer_id INT NOT NULL,
    course_code VARCHAR(20) NOT NULL,
    course_name VARCHAR(100) NOT NULL,
    semester VARCHAR(50),
    academic_year VARCHAR(20),
    class_schedule JSON,
    classroom VARCHAR(50),
    total_students INT DEFAULT 0,
    status ENUM('Active', 'Inactive', 'Completed') DEFAULT 'Active',
    assigned_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lecturer_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_lecturer_id (lecturer_id),
    INDEX idx_course_code (course_code),
    INDEX idx_semester (semester),
    INDEX idx_academic_year (academic_year)
);

-- 15. Academic Records Table (Staff View)
CREATE TABLE academic_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    lecturer_id INT,
    course_code VARCHAR(20) NOT NULL,
    course_name VARCHAR(100) NOT NULL,
    semester VARCHAR(50),
    academic_year VARCHAR(20),
    assessment_type ENUM('Exam', 'Assignment', 'Quiz', 'Project', 'Attendance') NOT NULL,
    marks DECIMAL(5,2),
    grade VARCHAR(10),
    credits DECIMAL(3,1),
    gpa DECIMAL(3,2),
    remarks TEXT,
    graded_by INT,
    grading_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (lecturer_id) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (graded_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_lecturer_id (lecturer_id),
    INDEX idx_course_code (course_code),
    INDEX idx_semester (semester),
    INDEX idx_academic_year (academic_year)
);

-- 16. Staff Attendance Table
CREATE TABLE staff_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    date DATE NOT NULL,
    check_in TIME,
    check_out TIME,
    status ENUM('Present', 'Absent', 'Late', 'Half Day', 'On Leave') NOT NULL,
    work_hours DECIMAL(4,2),
    overtime_hours DECIMAL(4,2) DEFAULT 0,
    remarks TEXT,
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_staff_id (staff_id),
    INDEX idx_date (date),
    INDEX idx_status (status)
);

-- 17. Staff Leave Requests Table
CREATE TABLE staff_leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    leave_type ENUM('Annual', 'Sick', 'Maternity', 'Paternity', 'Study', 'Compassionate', 'Unpaid') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    total_days INT NOT NULL,
    reason TEXT,
    status ENUM('Pending', 'Approved', 'Rejected', 'Cancelled') DEFAULT 'Pending',
    approved_by INT NULL,
    approval_date TIMESTAMP NULL,
    approval_remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_staff_id (staff_id),
    INDEX idx_leave_type (leave_type),
    INDEX idx_status (status),
    INDEX idx_start_date (start_date)
);

-- 18. Staff Performance Table
CREATE TABLE staff_performance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    evaluation_period VARCHAR(50) NOT NULL,
    performance_score DECIMAL(5,2),
    rating ENUM('Outstanding', 'Excellent', 'Good', 'Satisfactory', 'Needs Improvement') NOT NULL,
    strengths TEXT,
    areas_for_improvement TEXT,
    goals TEXT,
    evaluator_id INT,
    evaluation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (evaluator_id) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_staff_id (staff_id),
    INDEX idx_evaluation_period (evaluation_period),
    INDEX idx_rating (rating)
);

-- 19. Staff Training Table
CREATE TABLE staff_training (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    training_title VARCHAR(200) NOT NULL,
    training_provider VARCHAR(200),
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    training_type ENUM('Internal', 'External', 'Online', 'Workshop', 'Conference') NOT NULL,
    cost DECIMAL(10,2),
    status ENUM('Scheduled', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    certificate_obtained BOOLEAN DEFAULT FALSE,
    certificate_file VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_staff_id (staff_id),
    INDEX idx_training_type (training_type),
    INDEX idx_status (status)
);

-- 20. Staff Documents Table
CREATE TABLE staff_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    document_type ENUM('CV', 'Contract', 'Certificate', 'ID', 'Passport', 'Academic', 'Professional', 'Profile Picture', 'Other') NOT NULL,
    document_title VARCHAR(200) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size BIGINT,
    file_type VARCHAR(50),
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expiry_date DATE NULL,
    is_confidential BOOLEAN DEFAULT FALSE,
    uploaded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_staff_id (staff_id),
    INDEX idx_document_type (document_type),
    INDEX idx_upload_date (upload_date)
);

-- 21. System Settings Table
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value LONGTEXT,
    setting_type ENUM('text', 'number', 'boolean', 'file', 'json', 'email', 'url') DEFAULT 'text',
    description TEXT,
    category VARCHAR(50) DEFAULT 'general',
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key),
    INDEX idx_setting_type (setting_type),
    INDEX idx_category (category),
    INDEX idx_is_public (is_public)
);

-- 22. Access Control Management Table
CREATE TABLE staff_access_control (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    module_name VARCHAR(100) NOT NULL,
    access_level ENUM('None', 'Read', 'Write', 'Delete', 'Admin') DEFAULT 'Read',
    granted_by INT NULL,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    access_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_staff_id (staff_id),
    INDEX idx_module_name (module_name),
    INDEX idx_access_level (access_level),
    INDEX idx_is_active (is_active)
);

-- 23. Receipt Templates Table
CREATE TABLE IF NOT EXISTS receipt_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(100) NOT NULL UNIQUE,
    template_type ENUM('Fee Payment', 'Registration', 'Transcript', 'Certificate', 'General') NOT NULL,
    template_content LONGTEXT NOT NULL,
    template_variables JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_template_name (template_name),
    INDEX idx_template_type (template_type),
    INDEX idx_is_active (is_active)
);

-- 24. Staff Salaries Table
CREATE TABLE staff_salaries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    basic_salary DECIMAL(10,2) NOT NULL,
    allowances DECIMAL(10,2) DEFAULT 0,
    overtime_rate DECIMAL(10,2) DEFAULT 0,
    nssf_tax DECIMAL(10,2) DEFAULT 0,
    paye_tax DECIMAL(10,2) DEFAULT 0,
    effective_date DATE NOT NULL,
    end_date DATE NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (created_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_staff_id (staff_id),
    INDEX idx_effective_date (effective_date)
);

-- 25. Payroll Records Table
CREATE TABLE payroll_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    month VARCHAR(20) NOT NULL,
    year VARCHAR(4) NOT NULL,
    gross_salary DECIMAL(10,2) NOT NULL,
    net_salary DECIMAL(10,2) NOT NULL,
    total_fees_collected DECIMAL(10,2) DEFAULT 0,
    net_payment DECIMAL(10,2) NOT NULL,
    payslip_number VARCHAR(50) UNIQUE,
    processed_by INT,
    processing_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_staff_id (staff_id),
    INDEX idx_month_year (month, year),
    INDEX idx_processing_date (processing_date),
    INDEX idx_payslip_number (payslip_number)
);

-- 26. Generated Documents Table
CREATE TABLE generated_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_type ENUM('Transcript', 'Result Slip', 'Certificate', 'Receipt', 'Payslip', 'Report', 'Invoice', 'Timetable', 'Exam Schedule', 'Leave Form', 'Performance Review') NOT NULL,
    student_id INT NULL,
    staff_id INT NULL,
    generated_by INT NOT NULL,
    document_title VARCHAR(200) NOT NULL,
    document_content LONGTEXT NOT NULL,
    file_path VARCHAR(500),
    template_used INT NULL,
    generation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    is_public BOOLEAN DEFAULT FALSE,
    access_code VARCHAR(50) UNIQUE,
    download_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_document_type (document_type),
    INDEX idx_student_id (student_id),
    INDEX idx_staff_id (staff_id),
    INDEX idx_generated_by (generated_by),
    INDEX idx_generation_date (generation_date),
    INDEX idx_expires_at (expires_at),
    INDEX idx_access_code (access_code),
    INDEX idx_is_public (is_public)
);

-- 27. Notifications Table (Enhanced)
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    role_target VARCHAR(50),
    notification_type ENUM('info', 'success', 'warning', 'error', 'alert', 'reminder', 'system') DEFAULT 'info',
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    data JSON,
    is_read BOOLEAN DEFAULT FALSE,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    action_url VARCHAR(500),
    action_text VARCHAR(100),
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_role_target (role_target),
    INDEX idx_notification_type (notification_type),
    INDEX idx_is_read (is_read),
    INDEX idx_priority (priority),
    INDEX idx_expires_at (expires_at)
);

-- 28. Dashboard Updates Table
CREATE TABLE dashboard_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    update_type ENUM('new_feature', 'system_update', 'data_refresh', 'alert', 'maintenance') NOT NULL,
    update_title VARCHAR(200) NOT NULL,
    update_description TEXT,
    update_data JSON,
    target_users JSON,
    version VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_update_type (update_type),
    INDEX idx_is_active (is_active)
);

-- 29. Error Logs Table
CREATE TABLE error_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    error_message TEXT NOT NULL,
    error_code VARCHAR(50),
    user_id INT NULL,
    file_affected VARCHAR(255),
    line_number INT,
    stack_trace TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_error_code (error_code),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
);

-- 30. Document Generation Log Table
CREATE TABLE document_generation_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_type VARCHAR(50) NOT NULL,
    document_id VARCHAR(50),
    generated_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_document_type (document_type),
    INDEX idx_document_id (document_id),
    INDEX idx_generated_by (generated_by),
    INDEX idx_created_at (created_at)
);

-- 31. Activity Log Table (Unified)
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity ENUM('Login', 'Logout', 'Dashboard Access', 'Student View', 'Student Edit', 'Student Delete', 'Export', 'Print', 'Settings Change', 'Document Generate', 'Exam Create', 'Exam Schedule', 'Timetable Update', 'Certificate Generate', 'Report Generate', 'Bulk Import', 'Payment Process', 'Leave Request', 'Performance Review', 'Training Assign', 'Document Upload', 'System Update') NOT NULL,
    activity_description TEXT,
    module_accessed VARCHAR(100),
    record_id INT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_activity (activity),
    INDEX idx_module_accessed (module_accessed),
    INDEX idx_created_at (created_at)
);

-- 32. Cache Management Table
CREATE TABLE cache_management (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cache_key VARCHAR(255) NOT NULL UNIQUE,
    cache_type ENUM('system', 'user', 'data', 'reports', 'templates', 'dashboard', 'session') DEFAULT 'system',
    cache_data LONGTEXT,
    expiry_time TIMESTAMP DEFAULT (DATE_ADD(NOW(), INTERVAL 1 HOUR)),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_cache_key (cache_key),
    INDEX idx_cache_type (cache_type),
    INDEX idx_expiry_time (expiry_time)
);

-- 33. API Keys Table
CREATE TABLE api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(100) NOT NULL UNIQUE,
    api_key VARCHAR(255) NOT NULL UNIQUE,
    permissions JSON,
    allowed_origins TEXT,
    rate_limit INT DEFAULT 1000,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    last_used TIMESTAMP NULL,
    FOREIGN KEY (created_by) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_key_name (key_name),
    INDEX idx_api_key (api_key),
    INDEX idx_is_active (is_active),
    INDEX idx_expires_at (expires_at)
);

-- 34. Real-time Updates Table
CREATE TABLE real_time_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    update_type ENUM('new_student', 'staff_change', 'system_alert', 'data_sync', 'feature_update') NOT NULL,
    update_title VARCHAR(200) NOT NULL,
    update_description TEXT,
    update_data JSON,
    target_users JSON,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_update_type (update_type),
    INDEX idx_priority (priority),
    INDEX idx_is_active (is_active)
);

-- 36. Enhanced Document Templates Table
CREATE TABLE document_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(100) NOT NULL UNIQUE,
    template_type ENUM('transcript', 'certificate', 'receipt', 'invoice', 'payslip', 'report', 'timetable', 'exam_schedule', 'leave_form', 'performance_review', 'id_card', 'contract') NOT NULL,
    template_content LONGTEXT NOT NULL,
    template_variables JSON,
    is_default BOOLEAN DEFAULT FALSE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_template_name (template_name),
    INDEX idx_template_type (template_type),
    INDEX idx_is_default (is_default)
);

-- 37. Advanced Analytics Table
CREATE TABLE analytics_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cache_key VARCHAR(255) NOT NULL UNIQUE,
    cache_type ENUM('student_stats', 'staff_stats', 'financial_stats', 'performance_stats', 'attendance_stats', 'course_stats') DEFAULT 'student_stats',
    cache_data LONGTEXT,
    expiry_time TIMESTAMP DEFAULT (DATE_ADD(NOW(), INTERVAL 1 HOUR)),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_cache_key (cache_key),
    INDEX idx_cache_type (cache_type),
    INDEX idx_expiry_time (expiry_time)
);

-- 38. Advanced Search Index Table
CREATE TABLE search_index (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type ENUM('staff', 'student', 'document', 'course', 'department') NOT NULL,
    entity_id INT NOT NULL,
    searchable_content LONGTEXT,
    keywords JSON,
    keywords_text TEXT GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(keywords, '$.*'))) STORED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_entity_type (entity_type),
    INDEX idx_entity_id (entity_id),
    FULLTEXT idx_searchable_content (searchable_content),
    FULLTEXT idx_keywords (keywords_text)
);

-- 39. Data Sync Status Table
CREATE TABLE data_sync_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_name VARCHAR(100) NOT NULL,
    last_sync TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sync_status ENUM('success', 'failed', 'in_progress', 'pending') DEFAULT 'pending',
    sync_details TEXT,
    records_synced INT DEFAULT 0,
    error_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_table_name (table_name),
    INDEX idx_sync_status (sync_status),
    INDEX idx_last_sync (last_sync)
);

-- 40. Performance Metrics Table
CREATE TABLE performance_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    metric_type ENUM('response_time', 'actions_completed', 'errors_encountered', 'login_frequency', 'document_generation', 'data_export') NOT NULL,
    metric_value DECIMAL(10,2),
    metric_unit VARCHAR(20),
    period_type ENUM('daily', 'weekly', 'monthly', 'yearly') DEFAULT 'daily',
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_metric_type (metric_type),
    INDEX idx_period_type (period_type),
    INDEX idx_recorded_at (recorded_at)
);

-- 41. Smart Suggestions Table
CREATE TABLE smart_suggestions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    suggestion_type ENUM('action', 'report', 'shortcut', 'reminder', 'tip') NOT NULL,
    suggestion_text TEXT NOT NULL,
    suggestion_data JSON,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    context VARCHAR(100),
    is_dismissed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_suggestion_type (suggestion_type),
    INDEX idx_priority (priority),
    INDEX idx_is_dismissed (is_dismissed)
);

-- 42. Email Notifications Queue Table
CREATE TABLE email_notifications_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_email VARCHAR(255) NOT NULL,
    recipient_name VARCHAR(100),
    subject VARCHAR(200) NOT NULL,
    email_content LONGTEXT NOT NULL,
    email_type ENUM('notification', 'report', 'alert', 'reminder', 'system') DEFAULT 'notification',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('pending', 'sent', 'failed', 'cancelled') DEFAULT 'pending',
    send_attempts INT DEFAULT 0,
    scheduled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    error_message TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_recipient_email (recipient_email),
    INDEX idx_email_type (email_type),
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_scheduled_at (scheduled_at)
);

-- 43. Advanced User Preferences Table
CREATE TABLE user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    preference_key VARCHAR(100) NOT NULL,
    preference_value LONGTEXT,
    preference_type ENUM('ui', 'notifications', 'security', 'workflow', 'display') DEFAULT 'ui',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY unique_user_preference (user_id, preference_key),
    INDEX idx_user_id (user_id),
    INDEX idx_preference_key (preference_key),
    INDEX idx_preference_type (preference_type)
);

-- 44. Advanced Reports Table
CREATE TABLE advanced_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_name VARCHAR(200) NOT NULL UNIQUE,
    report_type ENUM('student', 'staff', 'financial', 'academic', 'performance', 'attendance', 'comprehensive') NOT NULL,
    report_query LONGTEXT NOT NULL,
    report_parameters JSON,
    report_template LONGTEXT,
    is_scheduled BOOLEAN DEFAULT FALSE,
    schedule_frequency ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly') DEFAULT 'monthly',
    recipients JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_report_name (report_name),
    INDEX idx_report_type (report_type),
    INDEX idx_is_active (is_active),
    INDEX idx_created_by (created_by)
);

-- 45. System Logs Table
CREATE TABLE system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    log_type ENUM('info', 'warning', 'error', 'debug', 'security', 'audit') NOT NULL,
    log_level ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    log_message TEXT NOT NULL,
    context_data JSON,
    user_id INT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_log_type (log_type),
    INDEX idx_log_level (log_level),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
);

-- 46. Backup Management Table
CREATE TABLE backup_management (
    id INT AUTO_INCREMENT PRIMARY KEY,
    backup_name VARCHAR(200) NOT NULL,
    backup_type ENUM('full', 'incremental', 'differential', 'snapshot') DEFAULT 'full',
    backup_path VARCHAR(500) NOT NULL,
    backup_size BIGINT,
    compression_type ENUM('none', 'gzip', 'zip', '7z') DEFAULT 'gzip',
    backup_status ENUM('in_progress', 'completed', 'failed', 'cancelled') DEFAULT 'in_progress',
    backup_tables JSON,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (created_by) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_backup_name (backup_name),
    INDEX idx_backup_type (backup_type),
    INDEX idx_backup_status (backup_status),
    INDEX idx_created_by (created_by)
);

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description, category, is_public) VALUES
('school_name', 'Institute of Strategic Nursing and Midwifery', 'text', 'School name for display on documents', 'general', TRUE),
('school_address', 'P.O. Box 12345, Kampala, Uganda', 'text', 'School address for documents', 'general', TRUE),
('school_phone', '+256 123 456 789', 'text', 'School phone number', 'general', TRUE),
('school_email', 'info@isnm.edu.ug', 'email', 'School email address', 'general', TRUE),
('school_website', 'www.isnm.edu.ug', 'url', 'School website URL', 'general', TRUE),
('academic_year', '2025/2026', 'text', 'Current academic year', 'academic', TRUE),
('semester', 'Semester 2', 'text', 'Current semester', 'academic', TRUE),
('currency', 'UGX', 'text', 'Default currency', 'financial', TRUE),
('max_login_attempts', '5', 'number', 'Maximum login attempts before account lock', 'security', FALSE),
('session_timeout', '30', 'number', 'Session timeout in minutes', 'security', FALSE),
('password_min_length', '8', 'number', 'Minimum password length', 'security', FALSE),
('enable_two_factor', 'false', 'boolean', 'Enable two-factor authentication', 'security', FALSE),
('auto_backup_enabled', 'true', 'boolean', 'Enable automatic backups', 'system', FALSE),
('backup_frequency', 'daily', 'text', 'Backup frequency', 'system', FALSE),
('max_upload_size', '10485760', 'number', 'Maximum upload file size in bytes', 'system', FALSE),
('default_language', 'en', 'text', 'Default system language', 'ui', FALSE),
('timezone', 'Africa/Kampala', 'text', 'System timezone', 'ui', FALSE),
('email_notifications_enabled', 'true', 'boolean', 'Enable email notifications', 'notifications', FALSE),
('sms_notifications_enabled', 'false', 'boolean', 'Enable SMS notifications', 'notifications', FALSE),
('dashboard_refresh_interval', '60', 'number', 'Dashboard auto-refresh interval in seconds', 'ui', FALSE),
('enable_real_time_updates', 'true', 'boolean', 'Enable real-time updates', 'system', FALSE),
('max_api_requests_per_hour', '1000', 'number', 'Maximum API requests per hour', 'api', FALSE),
('enable_audit_logging', 'true', 'boolean', 'Enable audit logging', 'security', FALSE),
('document_retention_days', '365', 'number', 'Document retention period in days', 'documents', FALSE),
('enable_advanced_search', 'true', 'boolean', 'Enable advanced search functionality', 'system', TRUE),
('enable_smart_suggestions', 'true', 'boolean', 'Enable smart suggestions', 'ui', TRUE),
('enable_performance_monitoring', 'true', 'boolean', 'Enable performance monitoring', 'system', TRUE),
('enable_data_sync', 'true', 'boolean', 'Enable data synchronization', 'system', TRUE),
('enable_real_time_notifications', 'true', 'boolean', 'Enable real-time notifications', 'notifications', TRUE),
('enable_email_queue', 'true', 'boolean', 'Enable email notification queue', 'system', TRUE),
('enable_analytics_cache', 'true', 'boolean', 'Enable analytics caching', 'system', TRUE),
('enable_backup_management', 'true', 'boolean', 'Enable backup management', 'system', TRUE),
('enable_api_access', 'true', 'boolean', 'Enable API access', 'api', TRUE),
('enable_user_preferences', 'true', 'boolean', 'Enable user preferences', 'ui', TRUE),
('enable_advanced_reports', 'true', 'boolean', 'Enable advanced reports', 'reports', TRUE),
('enable_system_logging', 'true', 'boolean', 'Enable comprehensive system logging', 'system', TRUE),
('enable_search_indexing', 'true', 'boolean', 'Enable search indexing', 'system', TRUE),
('enable_data_sync_status', 'true', 'boolean', 'Enable data sync status tracking', 'system', TRUE),
('enable_performance_metrics', 'true', 'boolean', 'Enable performance metrics', 'system', TRUE),
('enable_smart_suggestions_db', 'true', 'boolean', 'Enable smart suggestions database', 'ui', TRUE),
('enable_email_notifications_queue', 'true', 'boolean', 'Enable email notifications queue', 'notifications', TRUE),
('enable_backup_automation', 'true', 'boolean', 'Enable backup automation', 'system', TRUE),
('enable_system_health_monitoring', 'true', 'boolean', 'Enable system health monitoring', 'system', TRUE);

-- Insert default document templates
-- Use subquery to get an existing staff id (falls back to NULL if no staff yet)
INSERT INTO document_templates (template_name, template_type, template_content, is_default, created_by) VALUES
('Standard Transcript', 'transcript', '<html><body><h1>Academic Transcript</h1><table border="1"><tr><td>Student Name:</td><td>{{student_name}}</td></tr><tr><td>Student ID:</td><td>{{student_id}}</td></tr></table></body></html>', TRUE, (SELECT id FROM staff LIMIT 1)),
('Professional Certificate', 'certificate', '<html><body><h1>Certificate of Completion</h1><p>This is to certify that <strong>{{student_name}}</strong> has successfully completed the <strong>{{program}}</strong> program.</p></body></html>', TRUE, (SELECT id FROM staff LIMIT 1)),
('Standard Receipt', 'receipt', '<html><body><h1>Payment Receipt</h1><table border="1"><tr><td>Receipt No:</td><td>{{receipt_number}}</td></tr><tr><td>Amount:</td><td>{{amount}}</td></tr></table></body></html>', TRUE, (SELECT id FROM staff LIMIT 1)),
('Payslip Template', 'payslip', '<html><body><h1>Payslip</h1><table border="1"><tr><td>Employee:</td><td>{{employee_name}}</td></tr><tr><td>Net Salary:</td><td>{{net_salary}}</td></tr><tr><td>Tax:</td><td>{{tax}}</td></tr><tr><td>Allowance:</td><td>{{allowance}}</td></tr></table></body></html>', TRUE, (SELECT id FROM staff LIMIT 1)),
('Student ID Card', 'id_card', '<html><body><h1>Student ID Card</h1><div style="border: 2px solid #000; padding: 20px; width: 300px;"><p><strong>Name:</strong> {{student_name}}</p><p><strong>ID:</strong> {{student_id}}</p><p><strong>Program:</strong> {{program}}</p></div></body></html>', TRUE, (SELECT id FROM staff LIMIT 1)),
('Leave Request Form', 'leave_form', '<html><body><h1>Leave Request Form</h1><table border="1"><tr><td>Employee Name:</td><td>{{employee_name}}</td></tr><tr><td>Leave Type:</td><td>{{leave_type}}</td></tr><tr><td>Duration:</td><td>{{duration}}</td></tr><tr><td>Reason:</td><td>{{reason}}</td></tr></table></body></html>', TRUE, (SELECT id FROM staff LIMIT 1)),
('Performance Review', 'performance_review', '<html><body><h1>Performance Review</h1><table border="1"><tr><td>Employee:</td><td>{{employee_name}}</td></tr><tr><td>Period:</td><td>{{review_period}}</td></tr><tr><td>Rating:</td><td>{{rating}}</td></tr><tr><td>Comments:</td><td>{{comments}}</td></tr></table></body></html>', TRUE, (SELECT id FROM staff LIMIT 1));

-- Insert default staff roles with proper permissions
INSERT INTO staff_roles (role_name, role_description, role_level, dashboard_path, permissions) VALUES
('Director General', 'Overall school administration and management with full access to all modules and departments', 'Executive', 'dashboards/director-general.php', '{"all": true, "can_access_all_dashboards": true, "can_manage_all_staff": true, "can_view_all_departments": true, "can_edit_all_data": true, "can_delete_all_data": true, "can_view_financial": true, "can_view_academic": true, "can_view_hr": true, "can_view_students": true, "can_view_all_records": true, "super_admin": true}'),
('School Principal', 'School academic and administrative leadership with cross-departmental viewing access', 'Executive', 'dashboards/school-principal.php', '{"academic": true, "administrative": true, "staff": true, "students": true, "can_view_all_departments": true, "can_view_financial": true, "can_view_academic": true, "can_view_hr": true, "can_view_students": true, "can_view_all_records": true, "can_edit_own_department": true, "can_view_other_departments": true}'),
('CEO', 'Chief Executive Officer for strategic management with cross-departmental viewing access', 'Executive', 'dashboards/ceo.php', '{"strategic": true, "financial": true, "operational": true, "can_view_reports": true, "can_view_all_departments": true, "can_view_financial": true, "can_view_academic": true, "can_view_hr": true, "can_view_students": true, "can_view_all_records": true, "can_view_other_departments": true}'),
('Director Academics', 'Academic programs and curriculum oversight with cross-departmental viewing access', 'Management', 'dashboards/director-academics.php', '{"academic": true, "curriculum": true, "faculty": true, "can_manage_courses": true, "can_view_all_departments": true, "can_view_financial": true, "can_view_academic": true, "can_view_hr": true, "can_view_students": true, "can_view_all_records": true, "can_edit_own_department": true, "can_view_other_departments": true}'),
('Director Finance', 'Financial management and oversight with cross-departmental viewing access', 'Management', 'dashboards/director-finance.php', '{"financial": true, "budgeting": true, "reporting": true, "can_manage_finances": true, "can_view_all_departments": true, "can_view_financial": true, "can_view_academic": true, "can_view_hr": true, "can_view_students": true, "can_view_all_records": true, "can_edit_own_department": true, "can_view_other_departments": true}'),
('Director ICT', 'Information Technology management with cross-departmental viewing access', 'Management', 'dashboards/director-ict.php', '{"ict": true, "systems": true, "infrastructure": true, "can_manage_system": true, "can_view_all_departments": true, "can_view_financial": true, "can_view_academic": true, "can_view_hr": true, "can_view_students": true, "can_view_all_records": true, "can_edit_own_department": true, "can_view_other_departments": true}'),
('HR Manager', 'Human resources management', 'Management', 'dashboards/hr-manager.php', '{"hr": true, "staff": true, "recruitment": true, "training": true, "can_manage_staff": true}'),
('Academic Registrar', 'Student registration and academic records management', 'Academic', 'dashboards/academic-registrar.php', '{"academic": true, "students": true, "registration": true, "transcripts": true, "certificates": true}'),
('School Bursar', 'Financial operations and fee management', 'Administrative', 'bursar_dashboard.php', '{"financial": true, "fees": true, "collections": true, "can_manage_fees": true}'),
('School Librarian', 'Library and resource management', 'Support', 'dashboards/school-librarian.php', '{"library": true, "resources": true, "catalog": true}'),
('Head Nursing', 'Nursing department management', 'Academic', 'dashboards/head-nursing.php', '{"nursing": true, "department": true, "faculty": true}'),
('Head Midwifery', 'Midwifery department management', 'Academic', 'dashboards/head-midwifery.php', '{"midwifery": true, "department": true, "faculty": true}'),
('Lecturers', 'Teaching and academic staff management', 'Academic', 'dashboards/lecturers.php', '{"teaching": true, "lecturers": true, "courses": true}'),
('Senior Lecturers', 'Senior teaching staff management', 'Academic', 'dashboards/senior-lecturers.php', '{"teaching": true, "lecturers": true, "senior": true}'),
('Non-Teaching Staff', 'Administrative and support staff', 'Administrative', 'dashboards/non-teaching-staff.php', '{"administrative": true, "support": true}'),
('Sickbay', 'Medical and healthcare support services', 'Support', 'dashboards/sickbay.php', '{"healthcare": true, "patient": true, "medical": true}'),
('Matrons', 'Student welfare and residential staff management', 'Support', 'dashboards/matrons.php', '{"student_welfare": true, "residential": true}'),
('Security', 'Campus security and safety management', 'Support', 'dashboards/security.php', '{"security": true, "safety": true, "emergency": true}'),
('Drivers', 'Transportation and vehicle management', 'Support', 'dashboards/drivers.php', '{"transportation": true, "vehicles": true}'),
('Wardens', 'Student discipline and residential supervision', 'Support', 'dashboards/wardens.php', '{"student_welfare": true, "discipline": true, "residential": true}'),
('School Secretary', 'Administrative support and documentation', 'Administrative', 'dashboards/school-secretary.php', '{"administrative": true, "documentation": true, "can_manage_documents": true}'),
('Deputy Principal', 'Assistant to school principal', 'Management', 'dashboards/deputy-principal.php', '{"academic": true, "administrative": true, "can_assist_principal": true}'),
('Bursar', 'Financial assistant', 'Administrative', 'bursar_dashboard.php', '{"financial": true, "fees": true, "can_assist_bursar": true}'),
('Secretary', 'Administrative assistant', 'Administrative', 'dashboards/school-secretary.php', '{"administrative": true, "documentation": true, "can_assist_secretary": true}'),
('Store Keeper', 'Manage store inventory for general utilities and food supplies', 'Support', 'dashboards/storekeeper.php', '{"store": true, "inventory": true, "can_manage_store": true}');

-- Insert main administrator and dashboard staff accounts with unified credentials
-- Password: staff@123 (bcrypt hash: $2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K)
-- All staff can reset their password after first login

-- Director General
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) 
VALUES ('DG001', 'Director General', 'director.general@isnm.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Director General', 'Executive Office', (SELECT id FROM staff_roles WHERE role_name = 'Director General' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW()) 
ON DUPLICATE KEY UPDATE email = 'director.general@isnm.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', is_first_login = TRUE, updated_at = NOW();

-- CEO
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) 
VALUES ('CEO001', 'CEO', 'ceo@isnm.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Chief Executive Officer', 'Executive Office', (SELECT id FROM staff_roles WHERE role_name = 'CEO' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW()) 
ON DUPLICATE KEY UPDATE email = 'ceo@isnm.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', is_first_login = TRUE, updated_at = NOW();

-- School Principal
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) 
VALUES ('SP001', 'School Principal', 'principal@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$VVoHfONmCz.Bsvn1.t1UoesLbM01KNPXKT/b/VJIzxeUq0M9LabK.', 'School Principal', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'School Principal' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW()) 
ON DUPLICATE KEY UPDATE email = 'principal@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$VVoHfONmCz.Bsvn1.t1UoesLbM01KNPXKT/b/VJIzxeUq0M9LabK.', is_first_login = TRUE, updated_at = NOW();

-- School Secretary
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) 
VALUES ('SEC001', 'School Secretary', 'secretary@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$MtVRrE2x6uXh0CwEobzG.ueN1zcL/aE541mbLWpg3e7gnX4HkUxn.', 'School Secretary', 'Administrative Office', (SELECT id FROM staff_roles WHERE role_name = 'School Secretary' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW()) 
ON DUPLICATE KEY UPDATE email = 'secretary@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$MtVRrE2x6uXh0CwEobzG.ueN1zcL/aE541mbLWpg3e7gnX4HkUxn.', is_first_login = TRUE, updated_at = NOW();

-- Academic Registrar
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) 
VALUES ('AR001', 'Academic Registrar', 'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$Ha21Vlb7p046OaklPLFCteb8raqKNilEWDlzq8ypXVz491hHIICXS', 'Academic Registrar', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Academic Registrar' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW()) 
ON DUPLICATE KEY UPDATE email = 'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$Ha21Vlb7p046OaklPLFCteb8raqKNilEWDlzq8ypXVz491hHIICXS', is_first_login = TRUE, updated_at = NOW();

-- School Bursar
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) 
VALUES ('BUR001', 'School Bursar', 'bursar@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'School Bursar', 'Finance Department', (SELECT id FROM staff_roles WHERE role_name = 'School Bursar' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW()) 
ON DUPLICATE KEY UPDATE email = 'bursar@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', is_first_login = TRUE, updated_at = NOW();

-- HR Manager
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) 
VALUES ('HR001', 'HR Manager', 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$jEb8/OsV.9cydSvrBrZ1Hejase4BaTkPXT3FO/Gf9EazTrbXprKYi', 'HR Manager', 'Human Resources', (SELECT id FROM staff_roles WHERE role_name = 'HR Manager' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW()) 
ON DUPLICATE KEY UPDATE email = 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$jEb8/OsV.9cydSvrBrZ1Hejase4BaTkPXT3FO/Gf9EazTrbXprKYi', is_first_login = TRUE, updated_at = NOW();

-- Director Academics
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) 
VALUES ('DA001', 'Director Academics', 'director.academics@isnm.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Director Academics', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Director Academics' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW()) 
ON DUPLICATE KEY UPDATE email = 'director.academics@isnm.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', is_first_login = TRUE, updated_at = NOW();

-- Director ICT
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) 
VALUES ('DI001', 'Director ICT', 'director.ict@isnm.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Director ICT', 'Information Technology', (SELECT id FROM staff_roles WHERE role_name = 'Director ICT' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW()) 
ON DUPLICATE KEY UPDATE email = 'director.ict@isnm.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', is_first_login = TRUE, updated_at = NOW();

-- Director Finance
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) 
VALUES ('DF001', 'Director Finance', 'director.finance@isnm.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Director Finance', 'Finance Department', (SELECT id FROM staff_roles WHERE role_name = 'Director Finance' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW()) 
ON DUPLICATE KEY UPDATE email = 'director.finance@isnm.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', is_first_login = TRUE, updated_at = NOW();

-- Additional staff seed entries for remaining dashboard roles (standardized emails)
-- Password for all seeded accounts: staff@123 (bcrypt hash used below)
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('LIB001', 'School Librarian', 'library@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$GGfcvNfejW3f2fRptIUQIuK4c/W44n94twWtTAaOTqTVSuLZ52DsC', 'School Librarian', 'Library Services', (SELECT id FROM staff_roles WHERE role_name = 'School Librarian' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW()),
('HN001', 'Head Nursing', 'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$YO8OuL81gpaFdgP4nJEebeXNhLeM1.hFMD5KidDV9YDGkJMdAqbgW', 'Head Nursing', 'Nursing Department', (SELECT id FROM staff_roles WHERE role_name = 'Head Nursing' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW()),
('HM001', 'Head Midwifery', 'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$G7pMLdi2UjjmhEd8Lx0bmeaM7tGD4jrfvMsZh6HvY1Po8YqFRubRu', 'Head Midwifery', 'Midwifery Department', (SELECT id FROM staff_roles WHERE role_name = 'Head Midwifery' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW()),
('LEC001', 'Lecturers', 'lecturers@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$e52TV/DaoNDl4kjssi3Te.YHnpxHlaxatBX2wNg5yv3JkoYEEYV9i', 'Lecturer', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Lecturers' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW()),
('SLE001', 'Senior Lecturers', 'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$1gsFX/B27b5YuIAP7D5OSO2acgrtV7RcIMeja6RblX/9e5YSFfguy', 'Senior Lecturer', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Senior Lecturers' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW()),
('NTS001', 'Non-Teaching Staff', 'nonteaching@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Non-Teaching Staff', 'Administrative', (SELECT id FROM staff_roles WHERE role_name = 'Non-Teaching Staff' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW()),
('LAB001', 'Sickbay', 'sickbay@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$kzTn6S3OUtKLmGoLNo9GOOHqIki7NwUxvZJ6pJK02Yls6eR7Bln82', 'Sickbay', 'Support', (SELECT id FROM staff_roles WHERE role_name = 'Sickbay' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW()),
('MAT001', 'Matrons', 'matron@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$Qj7feWYysqaK1INwS50PFehU09Tgf6MOUNVBJZaOw3LZW/jGHZEkO', 'Matrons', 'Student Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Matrons' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW()),
('SECUR001', 'Security', 'security@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$0rLJuecuJuF6.Exxp7AQO.w0Dh0iwfwZri45gwya6OqENBJwjPA7C', 'Security', 'Security Services', (SELECT id FROM staff_roles WHERE role_name = 'Security' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW()),
('DRV001', 'Drivers', 'drivers@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$HrQ6V56zJJxIz8j.2grJVOWs2DjFGzA/wxzejvE3vtkk57KFuAjge', 'Drivers', 'Transport', (SELECT id FROM staff_roles WHERE role_name = 'Drivers' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW()),
('WDN001', 'Wardens', 'warden@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$jCKwMrdU.s1DVuA2HHFp6eBPK05F70IUoyAvRZX6Qf3wdPsCZBXM2', 'Wardens', 'Student Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Wardens' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW()),
('DP001', 'Deputy Principal', 'dep-principal@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$ANzSCNiGrURlS1ovFbQUKuK6ldOOBpiC0iW/MB7HVw/I5JC9wud.m', 'Deputy Principal', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Deputy Principal' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW()),
('STK001', 'Store Keeper', 'store@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$8qETvaYu2nreko/c/DyPROdIlMZyAciahJOVwHCV0KG4WxrcicxnS', 'Store Keeper', 'Facilities Management', (SELECT id FROM staff_roles WHERE role_name = 'Store Keeper' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW()),
('BURS002', 'Bursar', 'bursar.assistant@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Bursar', 'Finance Department', (SELECT id FROM staff_roles WHERE role_name = 'Bursar' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = VALUES(email), password = VALUES(password), is_first_login = TRUE, updated_at = NOW();

-- Insert dashboard access permissions for all staff roles
INSERT INTO staff_dashboard_access (staff_id, dashboard_path, access_level, granted_by) 
SELECT 
    s.id,
    sr.dashboard_path,
    'Full',
    (SELECT id FROM staff ORDER BY id ASC LIMIT 1)
FROM staff s 
JOIN staff_roles sr ON s.role_id = sr.id 
WHERE sr.role_name IN ('Director General', 'School Principal', 'CEO', 'Director Academics', 'Director Finance', 'Director ICT', 'HR Manager', 'Academic Registrar', 'School Bursar', 'School Librarian', 'Head Nursing', 'Head Midwifery', 'Lecturers', 'Senior Lecturers', 'Non-Teaching Staff', 'Sickbay', 'Matrons', 'Security', 'Drivers', 'Wardens', 'School Secretary', 'Bursar', 'Deputy Principal', 'Store Keeper');

-- Insert sample user preferences
INSERT INTO user_preferences (user_id, preference_key, preference_value, preference_type) VALUES
(1, 'theme', 'dark', 'ui'),
(1, 'language', 'en', 'ui'),
(1, 'notifications_email', 'true', 'notifications'),
(1, 'notifications_sms', 'false', 'notifications'),
(1, 'auto_save_interval', '5', 'ui'),
(1, 'dashboard_layout', 'grid', 'ui'),
(2, 'theme', 'light', 'ui'),
(2, 'language', 'en', 'ui'),
(2, 'notifications_email', 'true', 'notifications'),
(2, 'notifications_sms', 'true', 'notifications'),
(2, 'auto_save_interval', '10', 'ui'),
(2, 'dashboard_layout', 'list', 'ui'),
(3, 'theme', 'dark', 'ui'),
(3, 'language', 'en', 'ui'),
(3, 'notifications_email', 'false', 'notifications'),
(3, 'notifications_sms', 'false', 'notifications'),
(3, 'auto_save_interval', '15', 'ui'),
(3, 'dashboard_layout', 'grid', 'ui');

-- Insert sample advanced reports
INSERT INTO advanced_reports (report_name, report_type, report_query, report_parameters, report_template, is_scheduled, schedule_frequency, recipients, created_by) VALUES
('Monthly Staff Performance Report', 'staff', 'SELECT s.*, sp.performance_score, sp.rating FROM staff s LEFT JOIN staff_performance sp ON s.id = sp.staff_id WHERE s.status = "Active" ORDER BY sp.performance_score DESC', '{"period": "monthly", "department": "all"}', '<html><body><h1>Monthly Staff Performance Report</h1><table border="1">{{report_data}}</table></body></html>', TRUE, 'monthly', '["hr_manager", "school_principal"]', 1),
('Student Enrollment Statistics', 'student', 'SELECT program, COUNT(*) as total_students, AVG(gpa) as avg_gpa FROM students WHERE status = "Active" GROUP BY program', '{"year": "2026", "semester": "all"}', '<html><body><h1>Student Enrollment Statistics</h1><table border="1">{{report_data}}</table></body></html>', FALSE, 'monthly', '["academic_registrar", "school_principal"]', 1),
('Financial Summary Report', 'financial', 'SELECT record_type, SUM(amount) as total, COUNT(*) as count FROM financial_records WHERE transaction_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) GROUP BY record_type', '{"period": "monthly"}', '<html><body><h1>Financial Summary Report</h1><table border="1">{{report_data}}</table></body></html>', TRUE, 'monthly', '["school_bursar", "director_finance", "ceo"]', 1),
('Academic Performance Report', 'comprehensive', 'SELECT COUNT(*) as total_students, AVG(gpa) as avg_gpa, COUNT(CASE WHEN status = "Graduated" THEN 1 ELSE 0 END) as graduates FROM students WHERE YEAR(enrollment_date) = YEAR(CURDATE())', '{"year": "2026"}', '<html><body><h1>Academic Performance Report</h1><table border="1">{{report_data}}</table></body></html>', FALSE, 'yearly', '["academic_registrar", "school_principal", "director_academics"]', 1);

-- Insert default departments
INSERT INTO staff_departments (department_name, department_code, description, department_level) VALUES
('Executive Office', 'EXEC', 'School executive management and strategic planning', 'Executive'),
('Academic Affairs', 'ACAD', 'Academic programs and student services', 'Academic'),
('Finance Department', 'FIN', 'Financial management and operations', 'Administrative'),
('Human Resources', 'HR', 'Staff management and development', 'Administrative'),
('Information Technology', 'ICT', 'IT infrastructure and support', 'Support'),
('Nursing Department', 'NUR', 'Nursing education and training', 'Academic'),
('Midwifery Department', 'MID', 'Midwifery education and training', 'Academic'),
('Library Services', 'LIB', 'Library and information resources', 'Support'),
('Student Affairs', 'STU', 'Student welfare and support services', 'Support'),
('Security Services', 'SEC', 'Campus security and safety', 'Support'),
('Facilities Management', 'FAC', 'Physical infrastructure management', 'Support'),
('Quality Assurance', 'QA', 'Academic quality and standards', 'Academic');

-- Insert default receipt templates
INSERT INTO receipt_templates (template_name, template_type, template_content, template_variables, created_by) VALUES
('Fee Payment Receipt', 'Fee Payment', '<h2>ISNM FEE PAYMENT RECEIPT</h2><p><strong>Receipt No:</strong> {{receipt_number}}</p><p><strong>Student:</strong> {{student_name}}</p><p><strong>Amount:</strong> UGX {{amount}}</p><p><strong>Date:</strong> {{date}}</p><p><strong>Payment Method:</strong> {{payment_method}}</p>', '{"receipt_number": "string", "student_name": "string", "amount": "number", "date": "date", "payment_method": "string"}', (SELECT id FROM staff WHERE email = 'director.general@isnm.ac.ug'));

-- Insert default transcript templates
INSERT INTO generated_documents (document_type, generated_by, document_title, document_content, access_code, generation_date) VALUES
('Transcript', (SELECT id FROM staff WHERE email = 'director.general@isnm.ac.ug'), 'Official Academic Transcript', '<h2>IGANGA SCHOOL OF NURSING AND MIDWIFERY</h2><h3>OFFICIAL ACADEMIC TRANSCRIPT</h3><p><strong>Student Name:</strong> {{student_name}}</p><p><strong>Registration Number:</strong> {{registration_number}}</p><p><strong>Program:</strong> {{program}}</p><p><strong>Year:</strong> {{year}}</p><p><strong>GPA:</strong> {{gpa}}</p><p><strong>Status:</strong> {{status}}</p>', CONCAT('TRANS_', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s')), NOW());



-- Create view for staff login information
CREATE OR REPLACE VIEW staff_login_view AS
SELECT 
    s.id,
    s.staff_id,
    s.full_name,
    s.email,
    s.position,
    s.department,
    sr.role_name,
    sr.role_level,
    sr.dashboard_path,
    s.status,
    s.last_login,
    s.login_attempts,
    s.locked_until,
    s.is_first_login,
    CASE 
        WHEN s.locked_until > NOW() THEN 'Locked'
        WHEN s.login_attempts >= 5 THEN 'Warning'
        ELSE 'Active'
    END as account_status
FROM staff s
JOIN staff_roles sr ON s.role_id = sr.id;

-- Create view for users (alias for staff table for compatibility with some dashboards)
CREATE OR REPLACE VIEW users AS
SELECT 
    s.id,
    s.staff_id as username,
    s.full_name as user_name,
    s.email,
    s.password,
    s.position,
    s.department,
    s.role_id,
    sr.role_name,
    sr.role_level,
    sr.dashboard_path,
    s.status,
    s.phone,
    s.address,
    s.hire_date,
    s.last_login,
    s.login_attempts,
    s.locked_until,
    s.is_first_login,
    s.created_at,
    s.updated_at
FROM staff s
JOIN staff_roles sr ON s.role_id = sr.id;

-- Create stored procedure for staff authentication
DELIMITER //
CREATE PROCEDURE IF NOT EXISTS authenticate_staff(
    IN p_email VARCHAR(100),
    IN p_password VARCHAR(255),
    IN p_ip_address VARCHAR(45),
    IN p_user_agent TEXT
)
BEGIN
    DECLARE v_staff_id INT;
    DECLARE v_password_hash VARCHAR(255);
    DECLARE v_account_locked BOOLEAN;
    DECLARE v_login_attempts INT;
    DECLARE v_role_level VARCHAR(50);
    DECLARE v_dashboard_path VARCHAR(255);
    
    -- Check if account is locked
    SELECT s.id, s.password, s.locked_until > NOW(), s.login_attempts, sr.role_level, sr.dashboard_path
    INTO v_staff_id, v_password_hash, v_account_locked, v_login_attempts, v_role_level, v_dashboard_path
    FROM staff s
    JOIN staff_roles sr ON s.role_id = sr.id
    WHERE s.email = p_email AND s.status = 'Active';
    
    -- Record login attempt
    INSERT INTO staff_login_attempts (email, ip_address, user_agent, attempt_time, success, staff_id, failure_reason)
    VALUES (p_email, p_ip_address, p_user_agent, NOW(), 
            CASE 
                WHEN v_staff_id IS NULL THEN FALSE
                WHEN v_account_locked THEN FALSE
                WHEN v_password_hash = p_password THEN TRUE
                ELSE FALSE
            END,
            v_staff_id,
            CASE 
                WHEN v_staff_id IS NULL THEN 'Email not found'
                WHEN v_account_locked THEN 'Account locked'
                WHEN v_password_hash != p_password THEN 'Invalid password'
                ELSE 'Successful login'
            END
    );
    
    -- Update login attempts if failed
    IF v_staff_id IS NOT NULL AND (v_account_locked OR v_password_hash != p_password) THEN
        UPDATE staff 
        SET login_attempts = login_attempts + 1,
            last_failed_attempt = NOW(),
            locked_until = CASE 
                            WHEN login_attempts + 1 >= 5 THEN DATE_ADD(NOW(), INTERVAL 15 MINUTE)
                            ELSE locked_until 
                        END
        WHERE id = v_staff_id;
    END IF;
    
    -- Return authentication result
    SELECT 
        CASE 
            WHEN v_staff_id IS NULL THEN FALSE
            WHEN v_account_locked THEN FALSE
            WHEN v_password_hash = p_password THEN TRUE
            ELSE FALSE
        END as authenticated,
        v_staff_id as staff_id,
        v_role_level as role_level,
        v_dashboard_path as dashboard_path,
        v_login_attempts as login_attempts,
        v_account_locked as account_locked;
END //
DELIMITER ;

-- Create trigger for staff activity logging
DELIMITER //
CREATE TRIGGER IF NOT EXISTS log_staff_activity
AFTER INSERT ON staff_login_sessions
FOR EACH ROW
BEGIN
    INSERT INTO staff_activity_log (staff_id, activity_type, activity_description, ip_address, user_agent)
    VALUES (NEW.staff_id, 'Login', 'Staff member logged into the system', NEW.ip_address, NEW.user_agent);
END //
DELIMITER ;

-- COMPREHENSIVE FINANCIAL/BURSAR MODULE TABLES

CREATE TABLE fee_structures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fee_code VARCHAR(50) NOT NULL UNIQUE,
    fee_name VARCHAR(200) NOT NULL,
    fee_category ENUM('Tuition', 'Registration', 'Library', 'Laboratory', 'Clinical', 'Hostel', 'Examination', 'Identity Card', 'Medical', 'Sports', 'Other') NOT NULL,
    program VARCHAR(100),
    year_of_study INT,
    semester VARCHAR(50),
    amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'UGX',
    due_date DATE,
    is_mandatory BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    description TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_fee_code (fee_code),
    INDEX idx_fee_category (fee_category)
);

CREATE TABLE invoice_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    student_id INT NOT NULL,
    invoice_date DATE NOT NULL,
    due_date DATE NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    tax_amount DECIMAL(15,2) DEFAULT 0,
    discount_amount DECIMAL(15,2) DEFAULT 0,
    total_amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'UGX',
    status ENUM('Draft', 'Sent', 'Partial', 'Paid', 'Overdue', 'Cancelled') DEFAULT 'Draft',
    payment_terms TEXT,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (created_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_student_id (student_id),
    INDEX idx_status (status)
);

CREATE TABLE payment_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_number VARCHAR(50) NOT NULL UNIQUE,
    invoice_id INT,
    student_id INT NOT NULL,
    payment_date DATE NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'UGX',
    payment_method ENUM('Cash', 'Bank Transfer', 'Mobile Money', 'Credit Card', 'Cheque', 'Direct Debit', 'Other') NOT NULL,
    payment_reference VARCHAR(100),
    receipt_number VARCHAR(50),
    status ENUM('Pending', 'Completed', 'Failed', 'Refunded', 'Cancelled') DEFAULT 'Pending',
    proof_of_payment_file VARCHAR(500),
    notes TEXT,
    processed_by INT,
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoice_records(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_payment_number (payment_number),
    INDEX idx_student_id (student_id),
    INDEX idx_status (status)
);

CREATE TABLE budget_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    budget_code VARCHAR(50) NOT NULL UNIQUE,
    budget_name VARCHAR(200) NOT NULL,
    budget_category ENUM('Academic', 'Administrative', 'Operations', 'Capital Projects', 'Research', 'Student Services', 'Staff Development', 'Maintenance', 'Other') NOT NULL,
    department VARCHAR(100),
    fiscal_year VARCHAR(10) NOT NULL,
    allocated_amount DECIMAL(15,2) NOT NULL,
    spent_amount DECIMAL(15,2) DEFAULT 0,
    currency VARCHAR(10) DEFAULT 'UGX',
    status ENUM('Draft', 'Approved', 'Active', 'Closed', 'Cancelled') DEFAULT 'Draft',
    description TEXT,
    created_by INT,
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_budget_code (budget_code),
    INDEX idx_status (status)
);

CREATE TABLE expenditure_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expenditure_number VARCHAR(50) NOT NULL UNIQUE,
    budget_id INT,
    expenditure_date DATE NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'UGX',
    category ENUM('Salaries', 'Utilities', 'Supplies', 'Maintenance', 'Equipment', 'Travel', 'Training', 'Capital Expenditure', 'Other') NOT NULL,
    description TEXT NOT NULL,
    vendor_name VARCHAR(200),
    payment_method ENUM('Cash', 'Bank Transfer', 'Cheque', 'Credit Card', 'Other') NOT NULL,
    status ENUM('Pending', 'Approved', 'Paid', 'Rejected') DEFAULT 'Pending',
    requested_by INT,
    approved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (budget_id) REFERENCES budget_records(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_expenditure_number (expenditure_number),
    INDEX idx_status (status)
);

CREATE TABLE general_ledger (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entry_number VARCHAR(50) NOT NULL UNIQUE,
    entry_date DATE NOT NULL,
    account_code VARCHAR(50) NOT NULL,
    account_name VARCHAR(200) NOT NULL,
    account_type ENUM('Asset', 'Liability', 'Equity', 'Revenue', 'Expense') NOT NULL,
    debit_amount DECIMAL(15,2) DEFAULT 0,
    credit_amount DECIMAL(15,2) DEFAULT 0,
    currency VARCHAR(10) DEFAULT 'UGX',
    description TEXT NOT NULL,
    reference_number VARCHAR(100),
    fiscal_year VARCHAR(10) NOT NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_entry_number (entry_number),
    INDEX idx_account_code (account_code)
);

CREATE TABLE inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_code VARCHAR(50) NOT NULL UNIQUE,
    item_name VARCHAR(200) NOT NULL,
    item_category ENUM('Office Supplies', 'Laboratory Equipment', 'Medical Supplies', 'Furniture', 'Computers', 'Books', 'Uniforms', 'Food', 'Cleaning Supplies', 'Transport', 'Security', 'Hospitality', 'Other') NOT NULL,
    description TEXT,
    department VARCHAR(100) DEFAULT 'General',
    report_to VARCHAR(100) DEFAULT 'HR Manager',
    unit_of_measure VARCHAR(50) NOT NULL,
    quantity_on_hand INT DEFAULT 0,
    reorder_level INT DEFAULT 10,
    unit_cost DECIMAL(15,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'UGX',
    location VARCHAR(100),
    supplier VARCHAR(200),
    status ENUM('In Stock', 'Low Stock', 'Out of Stock', 'Discontinued') DEFAULT 'In Stock',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_item_code (item_code),
    INDEX idx_inventory_department (department),
    INDEX idx_inventory_report_to (report_to)
);

CREATE TABLE inventory_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_number VARCHAR(50) NOT NULL UNIQUE,
    inventory_id INT NOT NULL,
    reported_by INT NULL,
    report_to VARCHAR(100) NOT NULL,
    department VARCHAR(100) DEFAULT 'General',
    report_type ENUM('Low Stock','Damage','Request','Adjustment','Transfer','Other') NOT NULL DEFAULT 'Request',
    report_notes TEXT,
    request_status ENUM('Open','In Review','Resolved','Closed') DEFAULT 'Open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (reported_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_inventory_report_number (report_number),
    INDEX idx_inventory_report_status (request_status),
    INDEX idx_inventory_report_department (department)
);

CREATE TABLE inventory_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_number VARCHAR(50) NOT NULL UNIQUE,
    inventory_id INT NOT NULL,
    transaction_type ENUM('Purchase', 'Sale', 'Issue', 'Return', 'Adjustment', 'Transfer', 'Damage', 'Loss') NOT NULL,
    transaction_date DATE NOT NULL,
    quantity INT NOT NULL,
    unit_cost DECIMAL(15,2),
    total_cost DECIMAL(15,2),
    currency VARCHAR(10) DEFAULT 'UGX',
    reason TEXT,
    performed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inventory_id) REFERENCES inventory(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_transaction_number (transaction_number)
);

-- Sample department inventory items
INSERT IGNORE INTO inventory (item_code, item_name, item_category, description, department, report_to, unit_of_measure, quantity_on_hand, reorder_level, unit_cost, currency, location, supplier, status, created_by) VALUES
('NUR001', 'Surgical Masks', 'Medical Supplies', 'Disposable surgical masks for patient care', 'Nursing', 'HR Manager', 'boxes', 120, 15, 12.50, 'UGX', 'Nursing Store', 'MedSupply Ltd', 'In Stock', 1),
('MID001', 'Midwifery Kits', 'Medical Supplies', 'Delivery and emergency midwifery kits', 'Midwifery', 'HR Manager', 'sets', 35, 5, 105.00, 'UGX', 'Midwifery Store', 'HealthEquip Ltd', 'In Stock', 1),
('SCK001', 'Patient First Aid Kits', 'Medical Supplies', 'Portable first aid kits for sickbay emergencies', 'Sickbay', 'School Principal', 'kits', 18, 3, 75.00, 'UGX', 'Sickbay Storage', 'CarePlus Ltd', 'In Stock', 1),
('LIB001', 'Reference Books', 'Books', 'Professional reference books for library use', 'Library', 'School Librarian', 'pcs', 210, 20, 45.00, 'UGX', 'Library Shelves', 'EduBooks Ltd', 'In Stock', 1),
('ICT001', 'Network Switch', 'Computers', 'Managed network switch for campus ICT infrastructure', 'ICT', 'Director ICT', 'pcs', 8, 2, 420.00, 'UGX', 'ICT Server Room', 'TechNet Ltd', 'In Stock', 1),
('SEC001', 'Security Badges', 'Security', 'Access control badges for security staff', 'Security', 'Director General', 'pcs', 120, 20, 5.00, 'UGX', 'Security Office', 'SecureID Ltd', 'In Stock', 1),
('BRS001', 'Official Ledger Books', 'Office Supplies', 'Ledgers for bursar financial records', 'Bursar', 'School Bursar', 'pcs', 60, 10, 18.00, 'UGX', 'Bursar Office', 'OfficeMate Ltd', 'In Stock', 1),
('HRM001', 'Employee File Folders', 'Office Supplies', 'Folders for HR employee records', 'HR', 'HR Manager', 'pcs', 220, 30, 2.20, 'UGX', 'HR Office', 'Stationery Co', 'In Stock', 1);

CREATE TABLE sponsorships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sponsorship_code VARCHAR(50) NOT NULL UNIQUE,
    student_id INT NOT NULL,
    sponsor_name VARCHAR(200) NOT NULL,
    sponsor_type ENUM('Government', 'NGO', 'Private', 'Corporate', 'Individual', 'Scholarship', 'Other') NOT NULL,
    sponsorship_type ENUM('Full Tuition', 'Partial Tuition', 'Full Fees', 'Partial Fees', 'Living Expenses', 'Books', 'Other') NOT NULL,
    coverage_percentage DECIMAL(5,2),
    amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'UGX',
    start_date DATE NOT NULL,
    end_date DATE,
    status ENUM('Active', 'Inactive', 'Expired', 'Terminated') DEFAULT 'Active',
    terms_and_conditions TEXT,
    contact_person VARCHAR(100),
    contact_phone VARCHAR(20),
    contact_email VARCHAR(100),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (created_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_sponsorship_code (sponsorship_code)
);

CREATE TABLE fee_adjustments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    adjustment_number VARCHAR(50) NOT NULL UNIQUE,
    student_id INT NOT NULL,
    invoice_id INT,
    adjustment_type ENUM('Discount', 'Waiver', 'Penalty', 'Refund', 'Correction') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'UGX',
    reason TEXT NOT NULL,
    effective_date DATE NOT NULL,
    status ENUM('Pending', 'Approved', 'Rejected', 'Applied') DEFAULT 'Pending',
    approved_by INT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (invoice_id) REFERENCES invoice_records(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_adjustment_number (adjustment_number)
);

-- COMPREHENSIVE HR MANAGEMENT MODULE TABLES

CREATE TABLE recruitment_jobs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_code VARCHAR(50) NOT NULL UNIQUE,
    job_title VARCHAR(200) NOT NULL,
    job_category ENUM('Academic', 'Administrative', 'Support', 'Management', 'Technical') NOT NULL,
    department VARCHAR(100),
    job_type ENUM('Full Time', 'Part Time', 'Contract', 'Temporary', 'Internship') NOT NULL,
    description TEXT NOT NULL,
    requirements TEXT,
    qualifications TEXT,
    responsibilities TEXT,
    salary_range VARCHAR(100),
    vacancies INT DEFAULT 1,
    application_deadline DATE,
    status ENUM('Draft', 'Open', 'Closed', 'On Hold') DEFAULT 'Draft',
    posted_by INT,
    posted_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_job_code (job_code),
    INDEX idx_status (status)
);

CREATE TABLE recruitment_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    application_number VARCHAR(50) NOT NULL UNIQUE,
    job_id INT NOT NULL,
    applicant_name VARCHAR(200) NOT NULL,
    applicant_email VARCHAR(100) NOT NULL,
    applicant_phone VARCHAR(20),
    applicant_address TEXT,
    cv_file VARCHAR(500),
    cover_letter_file VARCHAR(500),
    other_documents TEXT,
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Received', 'Under Review', 'Shortlisted', 'Interview Scheduled', 'Interviewed', 'Offer Extended', 'Accepted', 'Rejected', 'Withdrawn') DEFAULT 'Received',
    interview_date DATE,
    interview_score DECIMAL(5,2),
    interview_notes TEXT,
    reviewed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES recruitment_jobs(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_application_number (application_number),
    INDEX idx_job_id (job_id),
    INDEX idx_status (status)
);

CREATE TABLE staff_contracts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contract_number VARCHAR(50) NOT NULL UNIQUE,
    staff_id INT NOT NULL,
    contract_type ENUM('Permanent', 'Probation', 'Fixed Term', 'Contract', 'Consultancy', 'Internship') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    job_title VARCHAR(200) NOT NULL,
    department VARCHAR(100),
    salary DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'UGX',
    contract_terms TEXT,
    benefits TEXT,
    probation_period INT DEFAULT 6,
    notice_period INT DEFAULT 30,
    status ENUM('Active', 'Expired', 'Terminated', 'Suspended', 'Renewed') DEFAULT 'Active',
    signed_date DATE,
    signed_by INT,
    contract_file VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (signed_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_contract_number (contract_number),
    INDEX idx_staff_id (staff_id),
    INDEX idx_status (status)
);

CREATE TABLE disciplinary_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_number VARCHAR(50) NOT NULL UNIQUE,
    staff_id INT NOT NULL,
    incident_date DATE NOT NULL,
    reported_date DATE NOT NULL,
    incident_type ENUM('Absence', 'Lateness', 'Misconduct', 'Insubordination', 'Negligence', 'Harassment', 'Theft', 'Fraud', 'Other') NOT NULL,
    description TEXT NOT NULL,
    severity ENUM('Minor', 'Moderate', 'Major', 'Critical') NOT NULL,
    witnesses TEXT,
    evidence_files TEXT,
    action_taken ENUM('Verbal Warning', 'Written Warning', 'Suspension', 'Demotion', 'Termination', 'Other') NOT NULL,
    action_details TEXT,
    reporter_id INT,
    status ENUM('Pending', 'Under Investigation', 'Resolved', 'Appealed', 'Closed') DEFAULT 'Pending',
    resolution_date DATE,
    resolution_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (reporter_id) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_case_number (case_number),
    INDEX idx_staff_id (staff_id),
    INDEX idx_status (status)
);

CREATE TABLE compliance_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    compliance_type ENUM('Background Check', 'Medical Examination', 'Police Clearance', 'License Renewal', 'Certification', 'Training', 'Other') NOT NULL,
    document_name VARCHAR(200) NOT NULL,
    document_number VARCHAR(100),
    issue_date DATE,
    expiry_date DATE,
    status ENUM('Valid', 'Expiring Soon', 'Expired', 'Pending') DEFAULT 'Valid',
    document_file VARCHAR(500),
    notes TEXT,
    reminder_sent BOOLEAN DEFAULT FALSE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (created_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_staff_id (staff_id),
    INDEX idx_compliance_type (compliance_type),
    INDEX idx_expiry_date (expiry_date),
    INDEX idx_status (status)
);

CREATE TABLE staff_promotions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    promotion_number VARCHAR(50) NOT NULL UNIQUE,
    staff_id INT NOT NULL,
    previous_position VARCHAR(200) NOT NULL,
    new_position VARCHAR(200) NOT NULL,
    previous_salary DECIMAL(10,2) NOT NULL,
    new_salary DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'UGX',
    effective_date DATE NOT NULL,
    promotion_reason TEXT,
    approved_by INT,
    approval_date TIMESTAMP NULL,
    status ENUM('Pending', 'Approved', 'Rejected', 'Implemented') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_promotion_number (promotion_number),
    INDEX idx_staff_id (staff_id),
    INDEX idx_status (status)
);

CREATE TABLE staff_resignations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resignation_number VARCHAR(50) NOT NULL UNIQUE,
    staff_id INT NOT NULL,
    resignation_date DATE NOT NULL,
    effective_date DATE NOT NULL,
    reason TEXT NOT NULL,
    notice_period_days INT DEFAULT 30,
    handover_notes TEXT,
    exit_interview_date DATE,
    exit_interview_notes TEXT,
    status ENUM('Submitted', 'Accepted', 'In Progress', 'Completed', 'Rejected') DEFAULT 'Submitted',
    approved_by INT,
    approval_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_resignation_number (resignation_number),
    INDEX idx_staff_id (staff_id),
    INDEX idx_status (status)
);

-- STUDENT MANAGEMENT TABLES

CREATE TABLE student_admissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admission_number VARCHAR(50) NOT NULL UNIQUE,
    student_id INT NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    program VARCHAR(100) NOT NULL,
    admission_date DATE NOT NULL,
    admission_status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_admission_number (admission_number)
);

CREATE TABLE student_academic_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    program VARCHAR(100) NOT NULL,
    year_of_study INT NOT NULL,
    semester VARCHAR(50) NOT NULL,
    gpa DECIMAL(3,2) DEFAULT 0,
    academic_status ENUM('Good Standing', 'Probation', 'Suspension') DEFAULT 'Good Standing',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_student_id (student_id)
);

CREATE TABLE course_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_number VARCHAR(50) NOT NULL UNIQUE,
    student_id INT NOT NULL,
    course_code VARCHAR(20) NOT NULL,
    semester VARCHAR(50) NOT NULL,
    status ENUM('Registered', 'Dropped', 'Completed') DEFAULT 'Registered',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_registration_number (registration_number)
);

CREATE TABLE examination_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_number VARCHAR(50) NOT NULL UNIQUE,
    student_id INT NOT NULL,
    course_code VARCHAR(20) NOT NULL,
    exam_type ENUM('Mid-Semester', 'Final', 'Supplementary') NOT NULL,
    marks_obtained DECIMAL(5,2) NOT NULL,
    total_marks DECIMAL(5,2) NOT NULL,
    grade VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_exam_number (exam_number)
);

CREATE TABLE student_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('Present', 'Absent', 'Late', 'Excused') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_date (date)
);

CREATE TABLE clinical_placements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    placement_number VARCHAR(50) NOT NULL UNIQUE,
    student_id INT NOT NULL,
    placement_site VARCHAR(200) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('Scheduled', 'In Progress', 'Completed') DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_placement_number (placement_number)
);

CREATE TABLE student_discipline (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_number VARCHAR(50) NOT NULL UNIQUE,
    student_id INT NOT NULL,
    incident_date DATE NOT NULL,
    incident_type ENUM('Absence', 'Misconduct', 'Academic Dishonesty', 'Other') NOT NULL,
    action_taken ENUM('Warning', 'Probation', 'Suspension', 'Expulsion') NOT NULL,
    status ENUM('Pending', 'Resolved', 'Closed') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_case_number (case_number)
);

CREATE TABLE hostel_management (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(20) NOT NULL UNIQUE,
    hostel_name VARCHAR(100) NOT NULL,
    capacity INT NOT NULL,
    occupied INT DEFAULT 0,
    room_type ENUM('Single', 'Double', 'Dormitory') NOT NULL,
    gender ENUM('Male', 'Female', 'Mixed') NOT NULL,
    status ENUM('Available', 'Occupied', 'Under Maintenance') DEFAULT 'Available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_room_number (room_number)
);

CREATE TABLE hostel_allocations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    allocation_number VARCHAR(50) NOT NULL UNIQUE,
    student_id INT NOT NULL,
    room_id INT NOT NULL,
    allocation_date DATE NOT NULL,
    end_date DATE,
    status ENUM('Active', 'Ended', 'Transferred') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (room_id) REFERENCES hostel_management(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_allocation_number (allocation_number)
);

-- GRADING SYSTEM ENHANCEMENT TABLES

-- Grade Scales Table
CREATE TABLE grade_scales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grade_letter VARCHAR(5) NOT NULL UNIQUE,
    grade_point DECIMAL(3,2) NOT NULL,
    min_percentage DECIMAL(5,2) NOT NULL,
    max_percentage DECIMAL(5,2) NOT NULL,
    grade_description VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_grade_letter (grade_letter),
    INDEX idx_is_active (is_active)
);

-- Grading Approval Workflow Table
CREATE TABLE grading_approval_workflow (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workflow_number VARCHAR(50) NOT NULL UNIQUE,
    examination_record_id INT NOT NULL,
    current_stage ENUM('Lecturer Entry', 'HOD Review', 'Registrar Approval', 'Principal Final Approval', 'Published', 'Rejected') DEFAULT 'Lecturer Entry',
    submitted_by INT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    hod_reviewed_by INT NULL,
    hod_reviewed_at TIMESTAMP NULL,
    hod_comments TEXT,
    hod_status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    registrar_approved_by INT NULL,
    registrar_approved_at TIMESTAMP NULL,
    registrar_comments TEXT,
    registrar_status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    principal_approved_by INT NULL,
    principal_approved_at TIMESTAMP NULL,
    principal_comments TEXT,
    principal_status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    published_at TIMESTAMP NULL,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (examination_record_id) REFERENCES examination_records(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (submitted_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (hod_reviewed_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (registrar_approved_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (principal_approved_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_workflow_number (workflow_number),
    INDEX idx_current_stage (current_stage),
    INDEX idx_examination_record_id (examination_record_id)
);

-- Transcript Generation Log Table
CREATE TABLE transcript_generation_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transcript_number VARCHAR(50) NOT NULL UNIQUE,
    student_id INT NOT NULL,
    requested_by INT NULL,
    approved_by INT NULL,
    generation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    purpose VARCHAR(200),
    copies INT DEFAULT 1,
    status ENUM('Pending', 'Approved', 'Generated', 'Rejected', 'Collected') DEFAULT 'Pending',
    approval_comments TEXT,
    file_path VARCHAR(500),
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_transcript_number (transcript_number),
    INDEX idx_student_id (student_id),
    INDEX idx_status (status)
);

-- Result Publication Table
CREATE TABLE result_publication (
    id INT AUTO_INCREMENT PRIMARY KEY,
    publication_id VARCHAR(50) NOT NULL UNIQUE,
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(50) NOT NULL,
    program VARCHAR(100),
    course_code VARCHAR(20),
    publication_date TIMESTAMP NULL,
    status ENUM('Draft', 'Scheduled', 'Published', 'Withdrawn') DEFAULT 'Draft',
    published_by INT NULL,
    scheduled_date TIMESTAMP NULL,
    notification_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (published_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_publication_id (publication_id),
    INDEX idx_academic_year (academic_year),
    INDEX idx_semester (semester),
    INDEX idx_status (status)
);

-- Academic Calendar Table
CREATE TABLE academic_calendar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    calendar_id VARCHAR(50) NOT NULL UNIQUE,
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(50) NOT NULL,
    semester_start_date DATE NOT NULL,
    semester_end_date DATE NOT NULL,
    exam_start_date DATE NOT NULL,
    exam_end_date DATE NOT NULL,
    result_publication_date DATE,
    registration_deadline DATE,
    add_drop_deadline DATE,
    withdrawal_deadline DATE,
    status ENUM('Upcoming', 'Current', 'Completed', 'Cancelled') DEFAULT 'Upcoming',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_calendar_id (calendar_id),
    INDEX idx_academic_year (academic_year),
    INDEX idx_semester (semester),
    INDEX idx_status (status)
);

-- Enhanced Examination Records Table (Add workflow fields)
ALTER TABLE examination_records 
ADD COLUMN workflow_id INT NULL AFTER grade,
ADD COLUMN continuous_assessment_marks DECIMAL(5,2) DEFAULT 0 AFTER workflow_id,
ADD COLUMN final_exam_marks DECIMAL(5,2) DEFAULT 0 AFTER continuous_assessment_marks,
ADD COLUMN total_marks_calculated DECIMAL(5,2) GENERATED ALWAYS AS (continuous_assessment_marks + final_exam_marks) STORED AFTER final_exam_marks,
ADD COLUMN lecturer_id INT NULL AFTER total_marks_calculated,
ADD COLUMN hod_id INT NULL AFTER lecturer_id,
ADD COLUMN grade_status ENUM('Draft', 'Submitted', 'Under Review', 'Approved', 'Published', 'Rejected') DEFAULT 'Draft' AFTER hod_id,
ADD FOREIGN KEY (workflow_id) REFERENCES grading_approval_workflow(id) ON DELETE SET NULL ON UPDATE CASCADE,
ADD FOREIGN KEY (lecturer_id) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
ADD FOREIGN KEY (hod_id) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE;

-- Insert default grade scales
INSERT INTO grade_scales (grade_letter, grade_point, min_percentage, max_percentage, grade_description) VALUES
('A', 4.0, 80.00, 100.00, 'Excellent'),
('B', 3.0, 70.00, 79.99, 'Very Good'),
('C', 2.0, 60.00, 69.99, 'Good'),
('D', 1.0, 50.00, 59.99, 'Satisfactory'),
('F', 0.0, 0.00, 49.99, 'Fail');

-- Grade Change History Table
CREATE TABLE grade_change_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workflow_number VARCHAR(50) NOT NULL,
    examination_record_id INT NOT NULL,
    changed_by INT NULL,
    previous_grade VARCHAR(5),
    new_grade VARCHAR(5),
    previous_ca_marks DECIMAL(5,2),
    new_ca_marks DECIMAL(5,2),
    previous_exam_marks DECIMAL(5,2),
    new_exam_marks DECIMAL(5,2),
    change_reason TEXT,
    change_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (examination_record_id) REFERENCES examination_records(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_workflow_number (workflow_number),
    INDEX idx_examination_record_id (examination_record_id),
    INDEX idx_change_timestamp (change_timestamp)
);

-- Grading Notifications Table
CREATE TABLE grading_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_id VARCHAR(50) NOT NULL UNIQUE,
    workflow_number VARCHAR(50) NOT NULL,
    recipient_id INT NOT NULL,
    sender_id INT NULL,
    notification_type ENUM('Grade Submitted', 'HOD Review Required', 'Registrar Approval Required', 'Principal Approval Required', 'Grade Published', 'Grade Rejected', 'Grade Modified') NOT NULL,
    message TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (workflow_number) REFERENCES grading_approval_workflow(workflow_number) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_notification_id (notification_id),
    INDEX idx_recipient_id (recipient_id),
    INDEX idx_is_read (is_read),
    INDEX idx_notification_type (notification_type)
);

-- Insert default academic calendar for current year
INSERT INTO academic_calendar (calendar_id, academic_year, semester, semester_start_date, semester_end_date, exam_start_date, exam_end_date, result_publication_date, registration_deadline, add_drop_deadline, withdrawal_deadline, status, created_by) VALUES
('CAL-2024-2025-S1', '2024-2025', 'Semester 1', '2024-09-01', '2024-12-15', '2024-12-01', '2024-12-15', '2025-01-15', '2024-09-15', '2024-09-30', '2024-10-31', 'Current', 1),
('CAL-2024-2025-S2', '2024-2025', 'Semester 2', '2025-02-01', '2025-05-31', '2025-05-15', '2025-05-31', '2025-06-15', '2025-02-15', '2025-02-28', '2025-03-31', 'Upcoming', 1);

-- ADDITIONAL TABLES FOR DASHBOARD FUNCTIONALITIES

-- Library Management Table
CREATE TABLE library_management (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id VARCHAR(50) NOT NULL UNIQUE,
    book_title VARCHAR(200) NOT NULL,
    author VARCHAR(200),
    isbn VARCHAR(20),
    category VARCHAR(100),
    publisher VARCHAR(200),
    publication_year INT,
    total_copies INT DEFAULT 1,
    available_copies INT DEFAULT 1,
    shelf_location VARCHAR(50),
    status ENUM('Available', 'Borrowed', 'Reserved', 'Lost', 'Under Repair') DEFAULT 'Available',
    added_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (added_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_book_id (book_id),
    INDEX idx_category (category),
    INDEX idx_status (status)
);

-- Library Transactions Table
CREATE TABLE library_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_number VARCHAR(50) NOT NULL UNIQUE,
    book_id INT NOT NULL,
    student_id INT,
    staff_id INT,
    transaction_type ENUM('Borrow', 'Return', 'Reserve', 'Renew') NOT NULL,
    borrow_date DATE,
    due_date DATE,
    return_date DATE,
    status ENUM('Active', 'Returned', 'Overdue', 'Lost') DEFAULT 'Active',
    fine_amount DECIMAL(10,2) DEFAULT 0,
    processed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES library_management(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_transaction_number (transaction_number),
    INDEX idx_status (status)
);

-- Research & Innovation Table
CREATE TABLE research_projects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_code VARCHAR(50) NOT NULL UNIQUE,
    project_title VARCHAR(200) NOT NULL,
    project_description TEXT,
    lead_researcher INT NOT NULL,
    research_team TEXT,
    start_date DATE,
    end_date DATE,
    funding_source VARCHAR(200),
    budget DECIMAL(15,2),
    status ENUM('Proposal', 'Ongoing', 'Completed', 'On Hold', 'Cancelled') DEFAULT 'Proposal',
    publication_details TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lead_researcher) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (created_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_project_code (project_code),
    INDEX idx_status (status)
);

-- Quality Assurance Table
CREATE TABLE quality_assurance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    qa_code VARCHAR(50) NOT NULL UNIQUE,
    assessment_type ENUM('Course Review', 'Program Review', 'Department Review', 'Institutional Review', 'Student Feedback', 'Staff Evaluation') NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    assessment_period VARCHAR(50),
    department VARCHAR(100),
    assessed_by INT,
    findings TEXT,
    recommendations TEXT,
    action_plan TEXT,
    status ENUM('Scheduled', 'In Progress', 'Completed', 'Follow-up Required') DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assessed_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_qa_code (qa_code),
    INDEX idx_status (status)
);

-- Accreditation Management Table
CREATE TABLE accreditation_management (
    id INT AUTO_INCREMENT PRIMARY KEY,
    accreditation_code VARCHAR(50) NOT NULL UNIQUE,
    program_name VARCHAR(200) NOT NULL,
    accrediting_body VARCHAR(200) NOT NULL,
    accreditation_type ENUM('Initial', 'Renewal', 'Re-accreditation', 'Special') NOT NULL,
    application_date DATE,
    site_visit_date DATE,
    accreditation_status ENUM('Pending', 'Under Review', 'Approved', 'Conditional', 'Denied', 'Expired') DEFAULT 'Pending',
    expiry_date DATE,
    report_file VARCHAR(500),
    compliance_notes TEXT,
    responsible_person INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (responsible_person) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_accreditation_code (accreditation_code),
    INDEX idx_status (accreditation_status)
);

-- Partnerships Table
CREATE TABLE partnerships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    partnership_code VARCHAR(50) NOT NULL UNIQUE,
    partner_name VARCHAR(200) NOT NULL,
    partner_type ENUM('Hospital', 'University', 'NGO', 'Government', 'Industry', 'International') NOT NULL,
    partnership_type ENUM('Clinical Training', 'Research', 'Funding', 'Exchange Program', 'Consultancy', 'Other') NOT NULL,
    description TEXT,
    start_date DATE,
    end_date DATE,
    status ENUM('Active', 'Inactive', 'Pending', 'Terminated') DEFAULT 'Pending',
    mou_file VARCHAR(500),
    contact_person VARCHAR(100),
    contact_email VARCHAR(100),
    contact_phone VARCHAR(20),
    responsible_person INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (responsible_person) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_partnership_code (partnership_code),
    INDEX idx_status (status)
);

-- URA Reporting Table
CREATE TABLE ura_reporting (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_code VARCHAR(50) NOT NULL UNIQUE,
    report_type ENUM('VAT Return', 'Income Tax', 'Paye Tax', 'Withholding Tax', 'Customs', 'Other') NOT NULL,
    reporting_period VARCHAR(50) NOT NULL,
    tax_year VARCHAR(10) NOT NULL,
    total_amount DECIMAL(15,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'UGX',
    submission_date DATE,
    status ENUM('Draft', 'Submitted', 'Accepted', 'Rejected', 'Amended') DEFAULT 'Draft',
    receipt_number VARCHAR(50),
    prepared_by INT,
    approved_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (prepared_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_report_code (report_code),
    INDEX idx_status (status)
);

-- IT Infrastructure Table
CREATE TABLE it_infrastructure (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_code VARCHAR(50) NOT NULL UNIQUE,
    asset_name VARCHAR(200) NOT NULL,
    asset_type ENUM('Computer', 'Server', 'Network Device', 'Printer', 'Projector', 'Software License', 'Other') NOT NULL,
    serial_number VARCHAR(100),
    specification TEXT,
    location VARCHAR(100),
    purchase_date DATE,
    warranty_expiry DATE,
    status ENUM('Operational', 'Under Maintenance', 'Out of Service', 'Retired') DEFAULT 'Operational',
    assigned_to INT,
    maintained_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (maintained_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_asset_code (asset_code),
    INDEX idx_status (status)
);

-- Skills Laboratory Table
CREATE TABLE skills_laboratory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lab_code VARCHAR(50) NOT NULL UNIQUE,
    lab_name VARCHAR(200) NOT NULL,
    lab_type ENUM('Nursing Skills Lab', 'Midwifery Skills Lab', 'Anatomy Lab', 'Physiology Lab', 'Other') NOT NULL,
    location VARCHAR(100),
    capacity INT,
    equipment_list TEXT,
    in_charge INT,
    status ENUM('Active', 'Under Maintenance', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (in_charge) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_lab_code (lab_code),
    INDEX idx_status (status)
);

-- Skills Lab Sessions Table
CREATE TABLE skills_lab_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_code VARCHAR(50) NOT NULL UNIQUE,
    lab_id INT NOT NULL,
    course_code VARCHAR(20),
    lecturer_id INT,
    session_topic VARCHAR(200),
    session_date DATE,
    start_time TIME,
    end_time TIME,
    student_group VARCHAR(100),
    status ENUM('Scheduled', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lab_id) REFERENCES skills_laboratory(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (lecturer_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_session_code (session_code),
    INDEX idx_status (status)
);

-- SECURITY DEPARTMENT TABLES

-- Security Incidents Table
CREATE TABLE security_incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_number VARCHAR(50) NOT NULL UNIQUE,
    incident_type ENUM('Unauthorized Access', 'Theft', 'Vandalism', 'Assault', 'Parking Violation', 'Vehicle Entry', 'Visitor Check-in', 'Emergency', 'Other') NOT NULL,
    incident_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    location VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    severity ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    status ENUM('Reported', 'Under Investigation', 'Resolved', 'Closed') DEFAULT 'Reported',
    reported_by INT NOT NULL,
    assigned_to INT,
    resolution_notes TEXT,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reported_by) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (assigned_to) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_incident_number (incident_number),
    INDEX idx_incident_type (incident_type),
    INDEX idx_incident_date (incident_date),
    INDEX idx_severity (severity),
    INDEX idx_status (status)
);

-- Security Patrols Table
CREATE TABLE security_patrols (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patrol_number VARCHAR(50) NOT NULL UNIQUE,
    guard_id INT NOT NULL,
    patrol_route VARCHAR(200) NOT NULL,
    patrol_area ENUM('Main Gate', 'Academic Block', 'Hostel Area', 'Parking Lot', 'Library', 'Laboratory', 'Sports Field', 'Perimeter', 'Full Campus') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    patrol_date DATE NOT NULL,
    status ENUM('Scheduled', 'In Progress', 'Completed', 'Cancelled', 'On Break') DEFAULT 'Scheduled',
    observations TEXT,
    incidents_found INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (guard_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_patrol_number (patrol_number),
    INDEX idx_guard_id (guard_id),
    INDEX idx_patrol_date (patrol_date),
    INDEX idx_status (status)
);

-- Access Control Logs Table
CREATE TABLE access_control_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    log_number VARCHAR(50) NOT NULL UNIQUE,
    access_type ENUM('Entry', 'Exit', 'Vehicle Entry', 'Vehicle Exit', 'Visitor Check-in', 'Visitor Check-out') NOT NULL,
    person_type ENUM('Student', 'Staff', 'Visitor', 'Unknown') NOT NULL,
    person_id INT NULL,
    person_name VARCHAR(200),
    access_point VARCHAR(100) NOT NULL,
    vehicle_number VARCHAR(50),
    access_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    purpose VARCHAR(200),
    status ENUM('Authorized', 'Unauthorized', 'Pending') DEFAULT 'Authorized',
    processed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (processed_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_log_number (log_number),
    INDEX idx_access_type (access_type),
    INDEX idx_access_time (access_time),
    INDEX idx_access_point (access_point),
    INDEX idx_status (status)
);

-- Security Equipment Table
CREATE TABLE security_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_code VARCHAR(50) NOT NULL UNIQUE,
    equipment_name VARCHAR(200) NOT NULL,
    equipment_type ENUM('CCTV Camera', 'Access Control System', 'Metal Detector', 'Radio', 'Alarm System', 'Fire Extinguisher', 'Emergency Light', 'Other') NOT NULL,
    location VARCHAR(200) NOT NULL,
    serial_number VARCHAR(100),
    purchase_date DATE,
    warranty_expiry DATE,
    status ENUM('Operational', 'Under Maintenance', 'Out of Service', 'Retired') DEFAULT 'Operational',
    last_maintenance_date DATE,
    next_maintenance_date DATE,
    maintained_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (maintained_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_equipment_code (equipment_code),
    INDEX idx_equipment_type (equipment_type),
    INDEX idx_location (location),
    INDEX idx_status (status)
);

-- Emergency Contacts Table
CREATE TABLE emergency_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contact_name VARCHAR(200) NOT NULL,
    contact_type ENUM('Police', 'Fire', 'Ambulance', 'Hospital', 'School Administration', 'Security Chief', 'Other') NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    alternative_phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    priority ENUM('Primary', 'Secondary', 'Tertiary') DEFAULT 'Primary',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_contact_type (contact_type),
    INDEX idx_priority (priority),
    INDEX idx_is_active (is_active)
);

-- WARDENS DEPARTMENT TABLES

-- Student Welfare Cases Table
CREATE TABLE student_welfare_cases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_number VARCHAR(50) NOT NULL UNIQUE,
    student_id INT NOT NULL,
    case_type ENUM('Academic Support', 'Personal Counseling', 'Financial Support', 'Health Issues', 'Disciplinary Issues', 'Homesickness', 'Family Problems', 'Other') NOT NULL,
    priority ENUM('Low', 'Medium', 'High', 'Urgent') DEFAULT 'Medium',
    case_description TEXT NOT NULL,
    immediate_actions TEXT,
    status ENUM('Open', 'In Progress', 'Under Review', 'Resolved', 'Closed') DEFAULT 'Open',
    assigned_warden INT NOT NULL,
    follow_up_required BOOLEAN DEFAULT TRUE,
    follow_up_date DATE,
    parent_contacted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (assigned_warden) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_case_number (case_number),
    INDEX idx_student_id (student_id),
    INDEX idx_case_type (case_type),
    INDEX idx_priority (priority),
    INDEX idx_status (status),
    INDEX idx_assigned_warden (assigned_warden)
);

-- Counseling Sessions Table
CREATE TABLE counseling_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_number VARCHAR(50) NOT NULL UNIQUE,
    student_id INT NOT NULL,
    counselor_id INT NOT NULL,
    session_type ENUM('Individual', 'Group', 'Family', 'Crisis Intervention') NOT NULL,
    topic VARCHAR(200) NOT NULL,
    scheduled_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    location VARCHAR(100),
    status ENUM('Scheduled', 'In Progress', 'Completed', 'Cancelled', 'Rescheduled') DEFAULT 'Scheduled',
    session_notes TEXT,
    action_plan TEXT,
    follow_up_required BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (counselor_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_session_number (session_number),
    INDEX idx_student_id (student_id),
    INDEX idx_counselor_id (counselor_id),
    INDEX idx_scheduled_date (scheduled_date),
    INDEX idx_status (status)
);

-- Room Inspections Table
CREATE TABLE room_inspections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inspection_number VARCHAR(50) NOT NULL UNIQUE,
    room_id INT NOT NULL,
    hostel_name VARCHAR(100) NOT NULL,
    room_number VARCHAR(20) NOT NULL,
    inspection_date DATE NOT NULL,
    inspector_id INT NOT NULL,
    cleanliness_score DECIMAL(3,2),
    condition_score DECIMAL(3,2),
    overall_status ENUM('Excellent', 'Good', 'Fair', 'Poor', 'Critical') DEFAULT 'Good',
    findings TEXT,
    maintenance_required BOOLEAN DEFAULT FALSE,
    maintenance_notes TEXT,
    follow_up_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (inspector_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_inspection_number (inspection_number),
    INDEX idx_room_id (room_id),
    INDEX idx_inspection_date (inspection_date),
    INDEX idx_overall_status (overall_status)
);

-- Duty Rosters Table
CREATE TABLE duty_rosters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    roster_number VARCHAR(50) NOT NULL UNIQUE,
    warden_id INT NOT NULL,
    duty_date DATE NOT NULL,
    shift ENUM('Morning', 'Afternoon', 'Evening', 'Night') NOT NULL,
    duty_area ENUM('Hostel A', 'Hostel B', 'Common Areas', 'Perimeter', 'Full Campus') NOT NULL,
    status ENUM('Scheduled', 'On Duty', 'Completed', 'Absent', 'Replaced') DEFAULT 'Scheduled',
    replacement_warden INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (warden_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (replacement_warden) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_roster_number (roster_number),
    INDEX idx_warden_id (warden_id),
    INDEX idx_duty_date (duty_date),
    INDEX idx_status (status)
);

-- Visitor Logs Table
CREATE TABLE visitor_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    log_number VARCHAR(50) NOT NULL UNIQUE,
    visitor_name VARCHAR(200) NOT NULL,
    visitor_type ENUM('Parent', 'Guardian', 'Official', 'Contractor', 'Delivery', 'Other') NOT NULL,
    visitor_id_number VARCHAR(100),
    visitor_phone VARCHAR(20),
    purpose VARCHAR(200) NOT NULL,
    person_visiting VARCHAR(200) NOT NULL,
    visit_date DATE NOT NULL,
    check_in_time TIME NOT NULL,
    check_out_time TIME,
    status ENUM('Checked In', 'Checked Out', 'Overstay') DEFAULT 'Checked In',
    authorized_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (authorized_by) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_log_number (log_number),
    INDEX idx_visit_date (visit_date),
    INDEX idx_visitor_type (visitor_type),
    INDEX idx_status (status)
);

-- Student Activities Table
CREATE TABLE student_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    activity_number VARCHAR(50) NOT NULL UNIQUE,
    activity_name VARCHAR(200) NOT NULL,
    activity_type ENUM('Sports', 'Cultural', 'Academic', 'Social', 'Religious', 'Workshop', 'Other') NOT NULL,
    description TEXT,
    activity_date DATE NOT NULL,
    start_time TIME,
    end_time TIME,
    location VARCHAR(200),
    organizer_id INT NOT NULL,
    max_participants INT,
    current_participants INT DEFAULT 0,
    status ENUM('Planning', 'Open for Registration', 'Registration Closed', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Planning',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organizer_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_activity_number (activity_number),
    INDEX idx_activity_date (activity_date),
    INDEX idx_activity_type (activity_type),
    INDEX idx_status (status)
);

-- DRIVERS DEPARTMENT TABLES

-- Vehicles Table
CREATE TABLE vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vehicle_code VARCHAR(50) NOT NULL UNIQUE,
    vehicle_name VARCHAR(200) NOT NULL,
    vehicle_type ENUM('Bus', 'Van', 'Car', 'Motorcycle', 'Other') NOT NULL,
    license_plate VARCHAR(20) NOT NULL UNIQUE,
    capacity INT NOT NULL,
    manufacturer VARCHAR(100),
    model VARCHAR(100),
    year INT,
    fuel_type ENUM('Petrol', 'Diesel', 'Electric', 'Hybrid') DEFAULT 'Diesel',
    status ENUM('Available', 'In Use', 'Maintenance', 'Out of Service', 'Retired') DEFAULT 'Available',
    purchase_date DATE,
    last_service_date DATE,
    next_service_date DATE,
    insurance_expiry DATE,
    assigned_driver INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_driver) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_vehicle_code (vehicle_code),
    INDEX idx_license_plate (license_plate),
    INDEX idx_status (status),
    INDEX idx_assigned_driver (assigned_driver)
);

-- Trip Logs Table
CREATE TABLE trip_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trip_number VARCHAR(50) NOT NULL UNIQUE,
    vehicle_id INT NOT NULL,
    driver_id INT NOT NULL,
    route_name VARCHAR(200) NOT NULL,
    trip_type ENUM('Morning Route', 'Evening Route', 'Field Trip', 'Medical Transfer', 'Other') NOT NULL,
    departure_time TIME NOT NULL,
    arrival_time TIME,
    trip_date DATE NOT NULL,
    start_location VARCHAR(200) NOT NULL,
    end_location VARCHAR(200) NOT NULL,
    passengers_count INT DEFAULT 0,
    distance_km DECIMAL(10,2),
    fuel_consumed DECIMAL(10,2),
    status ENUM('Scheduled', 'In Transit', 'Completed', 'Cancelled', 'Delayed') DEFAULT 'Scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_trip_number (trip_number),
    INDEX idx_vehicle_id (vehicle_id),
    INDEX idx_driver_id (driver_id),
    INDEX idx_trip_date (trip_date),
    INDEX idx_status (status)
);

-- Fuel Management Table
CREATE TABLE fuel_management (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fuel_number VARCHAR(50) NOT NULL UNIQUE,
    vehicle_id INT NOT NULL,
    fuel_type ENUM('Petrol', 'Diesel', 'Electric') DEFAULT 'Diesel',
    fuel_quantity DECIMAL(10,2) NOT NULL,
    unit_cost DECIMAL(10,2) NOT NULL,
    total_cost DECIMAL(15,2) GENERATED ALWAYS AS (fuel_quantity * unit_cost) STORED,
    fueling_date DATE NOT NULL,
    fueling_station VARCHAR(200),
    odometer_reading DECIMAL(10,2),
    filled_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (filled_by) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_fuel_number (fuel_number),
    INDEX idx_vehicle_id (vehicle_id),
    INDEX idx_fueling_date (fueling_date)
);

-- Route Schedules Table
CREATE TABLE route_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_code VARCHAR(50) NOT NULL UNIQUE,
    route_name VARCHAR(200) NOT NULL,
    route_type ENUM('Morning', 'Evening', 'Both') DEFAULT 'Both',
    departure_time TIME NOT NULL,
    return_time TIME,
    start_point VARCHAR(200) NOT NULL,
    end_point VARCHAR(200) NOT NULL,
    stops JSON,
    distance_km DECIMAL(10,2),
    estimated_duration_minutes INT,
    vehicle_id INT,
    driver_id INT,
    days_of_operation VARCHAR(50) DEFAULT 'Monday,Tuesday,Wednesday,Thursday,Friday',
    status ENUM('Active', 'Inactive', 'Seasonal') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_route_code (route_code),
    INDEX idx_route_type (route_type),
    INDEX idx_status (status)
);

-- MATRONS DEPARTMENT TABLES

-- Student Health Records Table
CREATE TABLE student_health_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_number VARCHAR(50) NOT NULL UNIQUE,
    student_id INT NOT NULL,
    blood_type VARCHAR(10),
    allergies TEXT,
    chronic_conditions TEXT,
    medications TEXT,
    emergency_contact_name VARCHAR(200),
    emergency_contact_phone VARCHAR(20),
    emergency_contact_relationship VARCHAR(100),
    insurance_provider VARCHAR(200),
    insurance_number VARCHAR(100),
    last_checkup_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_record_number (record_number),
    INDEX idx_student_id (student_id)
);

-- Health Incidents Table
CREATE TABLE health_incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_number VARCHAR(50) NOT NULL UNIQUE,
    student_id INT NOT NULL,
    incident_type ENUM('Illness', 'Injury', 'Accident', 'Allergic Reaction', 'Other') NOT NULL,
    symptoms TEXT NOT NULL,
    severity ENUM('Minor', 'Moderate', 'Severe', 'Critical') DEFAULT 'Moderate',
    incident_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    location VARCHAR(200),
    action_taken TEXT,
    treatment_given TEXT,
    referred_to VARCHAR(200),
    parent_notified BOOLEAN DEFAULT FALSE,
    parent_notification_time TIMESTAMP NULL,
    status ENUM('Reported', 'Under Observation', 'Resolved', 'Referred', 'Closed') DEFAULT 'Reported',
    reported_by INT NOT NULL,
    follow_up_required BOOLEAN DEFAULT FALSE,
    follow_up_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (reported_by) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_incident_number (incident_number),
    INDEX idx_student_id (student_id),
    INDEX idx_incident_date (incident_date),
    INDEX idx_severity (severity),
    INDEX idx_status (status)
);

-- Meal Tracking Table
CREATE TABLE meal_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meal_number VARCHAR(50) NOT NULL UNIQUE,
    student_id INT NOT NULL,
    meal_type ENUM('Breakfast', 'Lunch', 'Dinner', 'Snack') NOT NULL,
    meal_date DATE NOT NULL,
    meal_served BOOLEAN DEFAULT FALSE,
    meal_skipped BOOLEAN DEFAULT FALSE,
    skip_reason VARCHAR(200),
    special_dietary_requirements TEXT,
    allergies_noted TEXT,
    served_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (served_by) REFERENCES staff(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_meal_number (meal_number),
    INDEX idx_student_id (student_id),
    INDEX idx_meal_date (meal_date),
    INDEX idx_meal_type (meal_type)
);

-- SICKBAY / LEGACY LAB TECHNICIANS DEPARTMENT TABLES

-- Lab Equipment Maintenance Table
CREATE TABLE lab_equipment_maintenance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    maintenance_number VARCHAR(50) NOT NULL UNIQUE,
    equipment_id INT NOT NULL,
    equipment_name VARCHAR(200) NOT NULL,
    maintenance_type ENUM('Preventive', 'Corrective', 'Calibration', 'Inspection', 'Repair') NOT NULL,
    scheduled_date DATE NOT NULL,
    completed_date DATE,
    technician_id INT NOT NULL,
    maintenance_description TEXT,
    parts_used TEXT,
    cost DECIMAL(10,2),
    status ENUM('Scheduled', 'In Progress', 'Completed', 'Cancelled', 'Overdue') DEFAULT 'Scheduled',
    next_maintenance_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (technician_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_maintenance_number (maintenance_number),
    INDEX idx_equipment_id (equipment_id),
    INDEX idx_scheduled_date (scheduled_date),
    INDEX idx_status (status)
);

-- Lab Safety Records Table
CREATE TABLE lab_safety_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    safety_number VARCHAR(50) NOT NULL UNIQUE,
    lab_id INT NOT NULL,
    inspection_type ENUM('Safety Inspection', 'Equipment Check', 'Chemical Safety', 'Fire Safety', 'General Inspection') NOT NULL,
    inspection_date DATE NOT NULL,
    inspector_id INT NOT NULL,
    safety_score DECIMAL(5,2),
    overall_status ENUM('Excellent', 'Good', 'Fair', 'Poor', 'Critical') DEFAULT 'Good',
    findings TEXT,
    hazards_identified TEXT,
    corrective_actions TEXT,
    follow_up_required BOOLEAN DEFAULT FALSE,
    follow_up_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (lab_id) REFERENCES skills_laboratory(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (inspector_id) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_safety_number (safety_number),
    INDEX idx_lab_id (lab_id),
    INDEX idx_inspection_date (inspection_date),
    INDEX idx_overall_status (overall_status)
);

-- Chemical Inventory Table
CREATE TABLE chemical_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chemical_code VARCHAR(50) NOT NULL UNIQUE,
    chemical_name VARCHAR(200) NOT NULL,
    chemical_type ENUM('Acid', 'Base', 'Solvent', 'Reagent', 'Indicator', 'Other') NOT NULL,
    cas_number VARCHAR(50),
    hazard_class ENUM('Flammable', 'Corrosive', 'Toxic', 'Reactive', 'Oxidizer', 'Non-hazardous') DEFAULT 'Non-hazardous',
    storage_location VARCHAR(100),
    quantity_on_hand DECIMAL(10,2) NOT NULL,
    unit_of_measure VARCHAR(20) DEFAULT 'ml',
    reorder_level DECIMAL(10,2),
    supplier VARCHAR(200),
    expiry_date DATE,
    date_received DATE,
    received_by INT NOT NULL,
    status ENUM('In Stock', 'Low Stock', 'Expired', 'Discontinued') DEFAULT 'In Stock',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (received_by) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_chemical_code (chemical_code),
    INDEX idx_chemical_type (chemical_type),
    INDEX idx_hazard_class (hazard_class),
    INDEX idx_status (status),
    INDEX idx_expiry_date (expiry_date)
);

-- ADDITIONAL STORED PROCEDURES FOR DASHBOARD FUNCTIONALITIES

DELIMITER //
CREATE PROCEDURE IF NOT EXISTS get_dashboard_statistics(
    IN p_user_id INT,
    IN p_role VARCHAR(100)
)
BEGIN
    -- Return statistics based on user role
    IF p_role = 'Director General' OR p_role = 'School Principal' OR p_role = 'CEO' THEN
        SELECT 
            (SELECT COUNT(*) FROM students WHERE status = 'Active') as total_students,
            (SELECT COUNT(*) FROM staff WHERE status = 'Active') as total_staff,
            (SELECT COUNT(*) FROM student_admissions WHERE admission_status = 'Pending') as pending_applications,
            (SELECT COUNT(DISTINCT program) FROM students WHERE status = 'Active') as active_programs,
            (SELECT SUM(amount) FROM financial_records WHERE record_type = 'Collection' AND transaction_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as recent_collections;
    ELSEIF p_role = 'HR Manager' THEN
        SELECT 
            (SELECT COUNT(*) FROM staff WHERE status = 'Active') as total_staff,
            (SELECT COUNT(*) FROM recruitment_applications WHERE status = 'Received') as pending_applications,
            (SELECT COUNT(*) FROM staff_leave_requests WHERE status = 'Pending') as pending_leaves,
            (SELECT COUNT(*) FROM staff_training WHERE status = 'Scheduled') as upcoming_trainings;
    ELSEIF p_role = 'School Bursar' OR p_role = 'Bursar' OR p_role = 'Director Finance' THEN
        SELECT 
            (SELECT SUM(amount) FROM payment_records WHERE payment_date = CURDATE()) as today_collections,
            (SELECT SUM(amount) FROM payment_records WHERE payment_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as week_collections,
            (SELECT SUM(amount) FROM payment_records WHERE payment_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as month_collections,
            (SELECT SUM(balance) FROM fee_accounts WHERE status != 'Paid') as outstanding_fees,
            (SELECT COUNT(*) FROM students WHERE status = 'Active') as total_students;
    ELSEIF p_role = 'Academic Registrar' OR p_role = 'Director Academics' THEN
        SELECT 
            (SELECT COUNT(*) FROM students WHERE status = 'Active') as total_students,
            (SELECT COUNT(*) FROM staff WHERE position LIKE '%Lecturer%' AND status = 'Active') as total_lecturers,
            (SELECT COUNT(DISTINCT course_code) FROM course_assignments WHERE status = 'Active') as active_courses,
            (SELECT AVG(gpa) FROM student_academic_profiles WHERE academic_status = 'Good Standing') as avg_gpa;
    ELSEIF p_role = 'Head of Nursing' OR p_role = 'Head of Midwifery' THEN
        SELECT 
            (SELECT COUNT(*) FROM students WHERE program LIKE CONCAT('%', p_role, '%') AND status = 'Active') as department_students,
            (SELECT COUNT(*) FROM staff WHERE department = p_role AND status = 'Active') as department_staff,
            (SELECT COUNT(*) FROM course_assignments WHERE status = 'Active') as active_courses,
            (SELECT COUNT(*) FROM clinical_placements WHERE status = 'In Progress') as active_placements;
    ELSE
        SELECT 
            (SELECT COUNT(*) FROM students WHERE status = 'Active') as total_students,
            (SELECT COUNT(*) FROM staff WHERE status = 'Active') as total_staff,
            (SELECT COUNT(*) FROM course_assignments WHERE lecturer_id = p_user_id AND status = 'Active') as assigned_courses,
            (SELECT COUNT(*) FROM examination_records WHERE lecturer_id = p_user_id AND grade_status = 'Draft') as pending_grades;
    END IF;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE IF NOT EXISTS log_staff_activity(
    IN p_staff_id INT,
    IN p_activity_type VARCHAR(100),
    IN p_activity_description TEXT,
    IN p_module_accessed VARCHAR(100),
    IN p_record_id INT,
    IN p_ip_address VARCHAR(45),
    IN p_user_agent TEXT
)
BEGIN
    INSERT INTO staff_activity_log (
        staff_id, 
        activity_type, 
        activity_description, 
        module_accessed, 
        record_id, 
        ip_address, 
        user_agent
    ) VALUES (
        p_staff_id, 
        p_activity_type, 
        p_activity_description, 
        p_module_accessed, 
        p_record_id, 
        p_ip_address, 
        p_user_agent
    );
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE IF NOT EXISTS get_student_fee_status(
    IN p_student_id INT
)
BEGIN
    SELECT 
        s.student_number,
        s.first_name,
        s.last_name,
        s.program,
        COALESCE(SUM(fa.amount), 0) as total_fees,
        COALESCE(SUM(fa.paid_amount), 0) as total_paid,
        COALESCE(SUM(fa.balance), 0) as outstanding_balance,
        CASE 
            WHEN COALESCE(SUM(fa.balance), 0) = 0 THEN 'Cleared'
            WHEN COALESCE(SUM(fa.balance), 0) > 0 THEN 'Not Cleared'
        END as fee_status
    FROM students s
    LEFT JOIN fee_accounts fa ON s.id = fa.student_id
    WHERE s.id = p_student_id
    GROUP BY s.id;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE IF NOT EXISTS get_student_academic_summary(
    IN p_student_id INT
)
BEGIN
    SELECT 
        s.student_number,
        s.first_name,
        s.last_name,
        s.program,
        s.year_of_study,
        s.semester,
        sap.gpa,
        sap.academic_status,
        (SELECT COUNT(*) FROM examination_records WHERE student_id = p_student_id) as total_exams,
        (SELECT COUNT(*) FROM course_registrations WHERE student_id = p_student_id AND status = 'Registered') as registered_courses
    FROM students s
    LEFT JOIN student_academic_profiles sap ON s.id = sap.student_id
    WHERE s.id = p_student_id;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE IF NOT EXISTS request_password_reset(
    IN p_email VARCHAR(100),
    IN p_ip_address VARCHAR(45)
)
BEGIN
    DECLARE v_staff_id INT;
    DECLARE v_reset_token VARCHAR(255);
    DECLARE v_expires_at TIMESTAMP;
    
    -- Check if email exists
    SELECT id INTO v_staff_id FROM staff WHERE email = p_email AND status = 'Active' LIMIT 1;
    
    IF v_staff_id IS NOT NULL THEN
        -- Generate reset token
        SET v_reset_token = MD5(CONCAT(p_email, NOW(), RAND()));
        SET v_expires_at = DATE_ADD(NOW(), INTERVAL 1 HOUR);
        
        -- Insert reset token
        INSERT INTO staff_password_resets (
            staff_id, 
            reset_token, 
            reset_requested_at, 
            expires_at, 
            ip_address
        ) VALUES (
            v_staff_id, 
            v_reset_token, 
            NOW(), 
            v_expires_at, 
            p_ip_address
        );
        
        -- Return the reset token (in production, this would be sent via email)
        SELECT 
            v_reset_token as reset_token,
            v_expires_at as expires_at,
            'Password reset token generated successfully' as message,
            TRUE as success;
    ELSE
        SELECT 
            NULL as reset_token,
            NULL as expires_at,
            'Email not found or account inactive' as message,
            FALSE as success;
    END IF;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE IF NOT EXISTS reset_password_with_token(
    IN p_reset_token VARCHAR(255),
    IN p_new_password VARCHAR(255),
    IN p_ip_address VARCHAR(45)
)
BEGIN
    DECLARE v_staff_id INT;
    DECLARE v_token_valid BOOLEAN;
    DECLARE v_token_expired BOOLEAN;
    
    -- Check if token is valid and not expired
    SELECT 
        staff_id, 
        (expires_at > NOW()) as token_valid,
        (expires_at < NOW()) as token_expired
    INTO 
        v_staff_id, 
        v_token_valid, 
        v_token_expired
    FROM staff_password_resets 
    WHERE reset_token = p_reset_token AND is_used = FALSE 
    LIMIT 1;
    
    IF v_staff_id IS NOT NULL AND v_token_valid = TRUE THEN
        -- Update password
        UPDATE staff 
        SET password = p_new_password,
            password_changed = TRUE,
            is_first_login = FALSE,
            login_attempts = 0,
            locked_until = NULL,
            updated_at = NOW()
        WHERE id = v_staff_id;
        
        -- Mark token as used
        UPDATE staff_password_resets 
        SET is_used = TRUE 
        WHERE reset_token = p_reset_token;
        
        -- Log the password reset
        INSERT INTO staff_activity_log (
            staff_id, 
            activity_type, 
            activity_description, 
            module_accessed, 
            ip_address
        ) VALUES (
            v_staff_id, 
            'Settings Change', 
            'Password reset using token', 
            'authentication', 
            p_ip_address
        );
        
        SELECT 
            'Password reset successfully' as message,
            TRUE as success;
    ELSEIF v_staff_id IS NOT NULL AND v_token_expired = TRUE THEN
        SELECT 
            'Reset token has expired' as message,
            FALSE as success;
    ELSE
        SELECT 
            'Invalid reset token' as message,
            FALSE as success;
    END IF;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE IF NOT EXISTS change_password(
    IN p_staff_id INT,
    IN p_current_password VARCHAR(255),
    IN p_new_password VARCHAR(255),
    IN p_ip_address VARCHAR(45)
)
BEGIN
    DECLARE v_current_hash VARCHAR(255);
    DECLARE v_password_correct BOOLEAN;
    
    -- Get current password hash
    SELECT password INTO v_current_hash FROM staff WHERE id = p_staff_id LIMIT 1;
    
    -- Verify current password (this would use password_verify in PHP)
    SET v_password_correct = (v_current_hash = p_current_password);
    
    IF v_password_correct = TRUE THEN
        -- Update password
        UPDATE staff 
        SET password = p_new_password,
            password_changed = TRUE,
            is_first_login = FALSE,
            login_attempts = 0,
            locked_until = NULL,
            updated_at = NOW()
        WHERE id = p_staff_id;
        
        -- Log the password change
        INSERT INTO staff_activity_log (
            staff_id, 
            activity_type, 
            activity_description, 
            module_accessed, 
            ip_address
        ) VALUES (
            p_staff_id, 
            'Settings Change', 
            'Password changed by user', 
            'authentication', 
            p_ip_address
        );
        
        SELECT 
            'Password changed successfully' as message,
            TRUE as success;
    ELSE
        -- Increment login attempts
        UPDATE staff 
        SET login_attempts = login_attempts + 1,
            last_failed_attempt = NOW()
        WHERE id = p_staff_id;
        
        SELECT 
            'Current password is incorrect' as message,
            FALSE as success;
    END IF;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE IF NOT EXISTS get_staff_performance_summary(
    IN p_staff_id INT
)
BEGIN
    SELECT 
        s.staff_id,
        s.full_name,
        s.position,
        s.department,
        sr.role_name,
        (SELECT AVG(performance_score) FROM staff_performance WHERE staff_id = p_staff_id) as avg_performance,
        (SELECT COUNT(*) FROM staff_training WHERE staff_id = p_staff_id AND status = 'Completed') as completed_trainings,
        (SELECT COUNT(*) FROM course_assignments WHERE lecturer_id = p_staff_id AND status = 'Active') as active_courses,
        (SELECT COUNT(*) FROM staff_leave_requests WHERE staff_id = p_staff_id AND status = 'Approved' AND YEAR(start_date) = YEAR(CURDATE())) as approved_leaves
    FROM staff s
    JOIN staff_roles sr ON s.role_id = sr.id
    WHERE s.id = p_staff_id;
END //
DELIMITER ;

-- ADDITIONAL TRIGGERS FOR DASHBOARD FUNCTIONALITIES

DELIMITER //
CREATE TRIGGER IF NOT EXISTS log_grade_change_trigger
AFTER UPDATE ON examination_records
FOR EACH ROW
BEGIN
    IF OLD.grade != NEW.grade OR OLD.continuous_assessment_marks != NEW.continuous_assessment_marks OR OLD.final_exam_marks != NEW.final_exam_marks THEN
        INSERT INTO grade_change_history (
            workflow_number,
            examination_record_id,
            changed_by,
            previous_grade,
            new_grade,
            previous_ca_marks,
            new_ca_marks,
            previous_exam_marks,
            new_exam_marks,
            change_reason
        ) VALUES (
            (SELECT workflow_number FROM grading_approval_workflow WHERE examination_record_id = NEW.id LIMIT 1),
            NEW.id,
            NEW.lecturer_id,
            OLD.grade,
            NEW.grade,
            OLD.continuous_assessment_marks,
            NEW.continuous_assessment_marks,
            OLD.final_exam_marks,
            NEW.final_exam_marks,
            'Grade updated via dashboard'
        );
    END IF;
END //
DELIMITER ;

DELIMITER //
CREATE TRIGGER IF NOT EXISTS log_financial_transaction
AFTER INSERT ON payment_records
FOR EACH ROW
BEGIN
    INSERT INTO financial_records (
        record_type,
        amount,
        currency,
        description,
        reference_number,
        payment_method,
        recorded_by,
        student_id,
        transaction_date
    ) VALUES (
        'Collection',
        NEW.amount,
        NEW.currency,
        CONCAT('Payment - ', NEW.payment_reference),
        NEW.payment_number,
        NEW.payment_method,
        NEW.processed_by,
        NEW.student_id,
        NEW.payment_date
    );
END //
DELIMITER ;

DELIMITER //
CREATE TRIGGER IF NOT EXISTS update_fee_account_balance
AFTER INSERT ON payment_records
FOR EACH ROW
BEGIN
    UPDATE fee_accounts 
    SET paid_amount = paid_amount + NEW.amount,
        status = CASE 
            WHEN amount - (paid_amount + NEW.amount) <= 0 THEN 'Paid'
            WHEN paid_amount + NEW.amount > 0 THEN 'Partially Paid'
            ELSE 'Unpaid'
        END
    WHERE student_id = NEW.student_id;
END //
DELIMITER ;

-- DEPARTMENT-SPECIFIC STORED PROCEDURES FOR DASHBOARD STATISTICS

DELIMITER //
CREATE PROCEDURE IF NOT EXISTS get_security_dashboard_statistics(
    IN p_user_id INT
)
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM security_patrols WHERE patrol_date = CURDATE() AND status = 'In Progress') as active_patrols,
        (SELECT COUNT(*) FROM security_incidents WHERE DATE(incident_date) = CURDATE()) as incidents_today,
        (SELECT COUNT(*) FROM access_control_logs WHERE DATE(access_time) = CURDATE()) as access_entries_today,
        (SELECT COUNT(*) FROM security_equipment WHERE status = 'Operational') as operational_equipment,
        (SELECT COUNT(*) FROM security_patrols WHERE patrol_date = CURDATE() AND status = 'Scheduled') as scheduled_patrols,
        (SELECT COUNT(*) FROM security_incidents WHERE severity = 'High' AND status != 'Closed') as high_priority_incidents;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE IF NOT EXISTS get_wardens_dashboard_statistics(
    IN p_user_id INT
)
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM student_welfare_cases WHERE assigned_warden = p_user_id AND status IN ('Open', 'In Progress')) as open_welfare_cases,
        (SELECT COUNT(*) FROM counseling_sessions WHERE counselor_id = p_user_id AND scheduled_date = CURDATE()) as todays_counseling_sessions,
        (SELECT COUNT(*) FROM room_inspections WHERE inspection_date = CURDATE()) as todays_inspections,
        (SELECT COUNT(*) FROM student_discipline WHERE status = 'Pending') as pending_discipline_cases,
        (SELECT COUNT(*) FROM duty_rosters WHERE warden_id = p_user_id AND duty_date = CURDATE()) as todays_duties,
        (SELECT COUNT(*) FROM visitor_logs WHERE visit_date = CURDATE() AND status = 'Checked In') as current_visitors;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE IF NOT EXISTS get_drivers_dashboard_statistics(
    IN p_user_id INT
)
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM vehicles WHERE assigned_driver = p_user_id AND status = 'Available') as available_vehicles,
        (SELECT COUNT(*) FROM trip_logs WHERE driver_id = p_user_id AND trip_date = CURDATE() AND status = 'In Transit') as active_trips,
        (SELECT COUNT(*) FROM trip_logs WHERE driver_id = p_user_id AND trip_date = CURDATE() AND status = 'Completed') as completed_trips_today,
        (SELECT COUNT(*) FROM route_schedules WHERE driver_id = p_user_id AND status = 'Active') as assigned_routes,
        (SELECT SUM(fuel_quantity) FROM fuel_management WHERE filled_by = p_user_id AND fueling_date = CURDATE()) as fuel_consumed_today,
        (SELECT COUNT(*) FROM vehicles WHERE status = 'Maintenance') as vehicles_in_maintenance;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE IF NOT EXISTS get_matrons_dashboard_statistics(
    IN p_user_id INT
)
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM student_welfare_cases WHERE assigned_warden = p_user_id AND status IN ('Open', 'In Progress')) as open_welfare_cases,
        (SELECT COUNT(*) FROM counseling_sessions WHERE counselor_id = p_user_id AND scheduled_date = CURDATE()) as todays_counseling_sessions,
        (SELECT COUNT(*) FROM health_incidents WHERE reported_by = p_user_id AND DATE(incident_date) = CURDATE()) as health_incidents_today,
        (SELECT COUNT(*) FROM health_incidents WHERE severity IN('Severe', 'Critical') AND status != 'Closed') as critical_health_cases,
        (SELECT COUNT(*) FROM meal_tracking WHERE served_by = p_user_id AND meal_date = CURDATE()) as meals_served_today,
        (SELECT COUNT(*) FROM room_inspections WHERE inspector_id = p_user_id AND inspection_date = CURDATE()) as todays_inspections;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE IF NOT EXISTS get_sickbay_dashboard_statistics(
    IN p_user_id INT
)
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM lab_equipment_maintenance WHERE technician_id = p_user_id AND status = 'Scheduled') as scheduled_maintenance,
        (SELECT COUNT(*) FROM lab_equipment_maintenance WHERE technician_id = p_user_id AND status = 'In Progress') as maintenance_in_progress,
        (SELECT COUNT(*) FROM lab_safety_records WHERE inspector_id = p_user_id AND inspection_date = CURDATE()) as todays_inspections,
        (SELECT COUNT(*) FROM chemical_inventory WHERE status = 'Low Stock') as low_stock_chemicals,
        (SELECT COUNT(*) FROM chemical_inventory WHERE expiry_date < DATE_ADD(CURDATE(), INTERVAL 30 DAY)) as expiring_soon,
        (SELECT COUNT(*) FROM skills_lab_sessions WHERE lecturer_id = p_user_id AND session_date = CURDATE()) as todays_lab_sessions;
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE IF NOT EXISTS get_school_librarian_dashboard_statistics(
    IN p_user_id INT
)
BEGIN
    SELECT 
        (SELECT COUNT(*) FROM library_management WHERE status = 'Available') as available_books,
        (SELECT COUNT(*) FROM library_transactions WHERE transaction_type = 'Borrow' AND DATE(borrow_date) = CURDATE()) as books_borrowed_today,
        (SELECT COUNT(*) FROM library_transactions WHERE transaction_type = 'Return' AND DATE(return_date) = CURDATE()) as books_returned_today,
        (SELECT COUNT(*) FROM library_transactions WHERE status = 'Overdue') as overdue_books,
        (SELECT COUNT(*) FROM library_management WHERE status = 'Borrowed') as books_on_loan,
        (SELECT COUNT(*) FROM library_management WHERE status = 'Reserved') as reserved_books;
END //
DELIMITER ;

-- Update the main dashboard statistics procedure to include all departments
DELIMITER //
DROP PROCEDURE IF EXISTS get_dashboard_statistics //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE IF NOT EXISTS get_dashboard_statistics(
    IN p_user_id INT,
    IN p_role VARCHAR(100)
)
BEGIN
    -- Return statistics based on user role
    IF p_role = 'Director General' OR p_role = 'School Principal' OR p_role = 'CEO' THEN
        SELECT 
            (SELECT COUNT(*) FROM students WHERE status = 'Active') as total_students,
            (SELECT COUNT(*) FROM staff WHERE status = 'Active') as total_staff,
            (SELECT COUNT(*) FROM student_admissions WHERE admission_status = 'Pending') as pending_applications,
            (SELECT COUNT(DISTINCT program) FROM students WHERE status = 'Active') as active_programs,
            (SELECT SUM(amount) FROM financial_records WHERE record_type = 'Collection' AND transaction_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as recent_collections;
    ELSEIF p_role = 'Security' THEN
        CALL get_security_dashboard_statistics(p_user_id);
    ELSEIF p_role = 'Warden' THEN
        CALL get_wardens_dashboard_statistics(p_user_id);
    ELSEIF p_role = 'Driver' THEN
        CALL get_drivers_dashboard_statistics(p_user_id);
    ELSEIF p_role = 'Matron' THEN
        CALL get_matrons_dashboard_statistics(p_user_id);
    ELSEIF p_role = 'Sickbay' THEN
        CALL get_sickbay_dashboard_statistics(p_user_id);
    ELSEIF p_role = 'School Librarian' THEN
        CALL get_school_librarian_dashboard_statistics(p_user_id);
    ELSEIF p_role = 'HR Manager' THEN
        SELECT 
            (SELECT COUNT(*) FROM staff WHERE status = 'Active') as total_staff,
            (SELECT COUNT(*) FROM recruitment_applications WHERE status = 'Received') as pending_applications,
            (SELECT COUNT(*) FROM staff_leave_requests WHERE status = 'Pending') as pending_leaves,
            (SELECT COUNT(*) FROM staff_training WHERE status = 'Scheduled') as upcoming_trainings;
    ELSEIF p_role = 'School Bursar' OR p_role = 'Bursar' OR p_role = 'Director Finance' THEN
        SELECT 
            (SELECT SUM(amount) FROM payment_records WHERE payment_date = CURDATE()) as today_collections,
            (SELECT SUM(amount) FROM payment_records WHERE payment_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as week_collections,
            (SELECT SUM(amount) FROM payment_records WHERE payment_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as month_collections,
            (SELECT SUM(balance) FROM fee_accounts WHERE status != 'Paid') as outstanding_fees,
            (SELECT COUNT(*) FROM students WHERE status = 'Active') as total_students;
    ELSEIF p_role = 'Academic Registrar' OR p_role = 'Director Academics' THEN
        SELECT 
            (SELECT COUNT(*) FROM students WHERE status = 'Active') as total_students,
            (SELECT COUNT(*) FROM staff WHERE position LIKE '%Lecturer%' AND status = 'Active') as total_lecturers,
            (SELECT COUNT(DISTINCT course_code) FROM course_assignments WHERE status = 'Active') as active_courses,
            (SELECT AVG(gpa) FROM student_academic_profiles WHERE academic_status = 'Good Standing') as avg_gpa;
    ELSEIF p_role = 'Head of Nursing' OR p_role = 'Head of Midwifery' THEN
        SELECT 
            (SELECT COUNT(*) FROM students WHERE program LIKE CONCAT('%', p_role, '%') AND status = 'Active') as department_students,
            (SELECT COUNT(*) FROM staff WHERE department = p_role AND status = 'Active') as department_staff,
            (SELECT COUNT(*) FROM course_assignments WHERE status = 'Active') as active_courses,
            (SELECT COUNT(*) FROM clinical_placements WHERE status = 'In Progress') as active_placements;
    ELSE
        SELECT 
            (SELECT COUNT(*) FROM students WHERE status = 'Active') as total_students,
            (SELECT COUNT(*) FROM staff WHERE status = 'Active') as total_staff,
            (SELECT COUNT(*) FROM course_assignments WHERE lecturer_id = p_user_id AND status = 'Active') as assigned_courses,
            (SELECT COUNT(*) FROM examination_records WHERE lecturer_id = p_user_id AND grade_status = 'Draft') as pending_grades;
    END IF;
END //
DELIMITER ;

-- End of Final Complete Staffs Database Schema

-- Step 3: Students Database (with bursar financial tables)
-- Note: bursar_system.sql has FKs to igangaschoolofl_staffs_db.staff - requires staff table

USE igangaschoolofl_students_db;

-- Drop existing tables if they exist (for fresh installation)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS student_messages;
DROP TABLE IF EXISTS student_notifications;
DROP TABLE IF EXISTS student_downloads;
DROP TABLE IF EXISTS student_timetables;
DROP TABLE IF EXISTS student_fees;
DROP TABLE IF EXISTS student_attendance;
DROP TABLE IF EXISTS student_academic_records;
DROP TABLE IF EXISTS student_profiles;
DROP TABLE IF EXISTS student_password_resets;
DROP TABLE IF EXISTS students;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Students Table with Login Support
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_number VARCHAR(50) UNIQUE NOT NULL,
    registration_number VARCHAR(50) UNIQUE,
    national_student_id_number VARCHAR(50) UNIQUE,
    index_number VARCHAR(50) UNIQUE,
    
    first_name VARCHAR(100) NOT NULL,
    surname VARCHAR(100) NOT NULL,
    other_name VARCHAR(100),
    full_name VARCHAR(300),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255), -- For student login
    
    phone VARCHAR(20),
    mobile_number VARCHAR(20),
    program VARCHAR(100),
    course VARCHAR(100),
    current_year INT,
    year INT,
    level VARCHAR(50),
    set_name VARCHAR(50),
    current_semester VARCHAR(20),
    intake_date DATE,
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other') DEFAULT 'Other',
    nationality VARCHAR(100),
    address TEXT,
    
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    emergency_contact_email VARCHAR(100),
    
    guardian_name VARCHAR(200),
    guardian_phone VARCHAR(20),
    
    profile_picture VARCHAR(500),
    passport_photo VARCHAR(500),
    
    status ENUM('Active', 'Inactive', 'Graduated', 'Suspended', 'Withdrawn', 'deleted') DEFAULT 'Active',
    
    last_login TIMESTAMP NULL,
    locked_until TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    password_changed BOOLEAN DEFAULT FALSE,
    is_first_login BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_student_number (student_number),
    INDEX idx_registration_number (registration_number),
    INDEX idx_national_id (national_student_id_number),
    INDEX idx_index_number (index_number),
    INDEX idx_email (email),
    INDEX idx_program (program),
    INDEX idx_course (course),
    INDEX idx_current_year (current_year),
    INDEX idx_year (year),
    INDEX idx_status (status)
);

DROP TRIGGER IF EXISTS students_before_insert;
DROP TRIGGER IF EXISTS students_before_update;

DELIMITER //
CREATE TRIGGER students_before_insert
BEFORE INSERT ON students
FOR EACH ROW
BEGIN
    IF NEW.full_name IS NULL OR NEW.full_name = '' THEN
        SET NEW.full_name = TRIM(CONCAT(NEW.first_name, ' ', COALESCE(NEW.other_name, ''), ' ', NEW.surname));
    END IF;

    IF (NEW.phone IS NULL OR NEW.phone = '') AND NEW.mobile_number IS NOT NULL THEN
        SET NEW.phone = NEW.mobile_number;
    END IF;
    IF (NEW.mobile_number IS NULL OR NEW.mobile_number = '') AND NEW.phone IS NOT NULL THEN
        SET NEW.mobile_number = NEW.phone;
    END IF;

    IF (NEW.program IS NULL OR NEW.program = '') AND NEW.course IS NOT NULL THEN
        SET NEW.program = NEW.course;
    END IF;
    IF (NEW.course IS NULL OR NEW.course = '') AND NEW.program IS NOT NULL THEN
        SET NEW.course = NEW.program;
    END IF;

    IF (NEW.current_year IS NULL OR NEW.current_year = 0) AND NEW.year IS NOT NULL THEN
        SET NEW.current_year = NEW.year;
    END IF;
    IF (NEW.year IS NULL OR NEW.year = 0) AND NEW.current_year IS NOT NULL THEN
        SET NEW.year = NEW.current_year;
    END IF;

    IF (NEW.profile_picture IS NULL OR NEW.profile_picture = '') AND NEW.passport_photo IS NOT NULL THEN
        SET NEW.profile_picture = NEW.passport_photo;
    END IF;
    IF (NEW.passport_photo IS NULL OR NEW.passport_photo = '') AND NEW.profile_picture IS NOT NULL THEN
        SET NEW.passport_photo = NEW.profile_picture;
    END IF;
END //

CREATE TRIGGER students_before_update
BEFORE UPDATE ON students
FOR EACH ROW
BEGIN
    IF NEW.full_name IS NULL OR NEW.full_name = '' THEN
        SET NEW.full_name = TRIM(CONCAT(NEW.first_name, ' ', COALESCE(NEW.other_name, ''), ' ', NEW.surname));
    END IF;

    IF (NEW.phone IS NULL OR NEW.phone = '') AND NEW.mobile_number IS NOT NULL THEN
        SET NEW.phone = NEW.mobile_number;
    END IF;
    IF (NEW.mobile_number IS NULL OR NEW.mobile_number = '') AND NEW.phone IS NOT NULL THEN
        SET NEW.mobile_number = NEW.phone;
    END IF;

    IF (NEW.program IS NULL OR NEW.program = '') AND NEW.course IS NOT NULL THEN
        SET NEW.program = NEW.course;
    END IF;
    IF (NEW.course IS NULL OR NEW.course = '') AND NEW.program IS NOT NULL THEN
        SET NEW.course = NEW.program;
    END IF;

    IF (NEW.current_year IS NULL OR NEW.current_year = 0) AND NEW.year IS NOT NULL THEN
        SET NEW.current_year = NEW.year;
    END IF;
    IF (NEW.year IS NULL OR NEW.year = 0) AND NEW.current_year IS NOT NULL THEN
        SET NEW.year = NEW.current_year;
    END IF;

    IF (NEW.profile_picture IS NULL OR NEW.profile_picture = '') AND NEW.passport_photo IS NOT NULL THEN
        SET NEW.profile_picture = NEW.passport_photo;
    END IF;
    IF (NEW.passport_photo IS NULL OR NEW.passport_photo = '') AND NEW.profile_picture IS NOT NULL THEN
        SET NEW.passport_photo = NEW.profile_picture;
    END IF;
END //
DELIMITER ;

-- 2. Student Profiles Table
CREATE TABLE student_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    bio TEXT,
    interests TEXT,
    skills TEXT,
    achievements TEXT,
    education_background TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_student_id (student_id)
);

-- 3. Academic Records Table
CREATE TABLE student_academic_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    semester VARCHAR(50),
    academic_year VARCHAR(20),
    subject VARCHAR(100),
    course_code VARCHAR(20),
    grade VARCHAR(10),
    marks DECIMAL(5,2),
    credits DECIMAL(3,1),
    gpa DECIMAL(3,2),
    cgpa DECIMAL(3,2),
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_semester (semester),
    INDEX idx_academic_year (academic_year),
    INDEX idx_subject (subject)
);

-- 4. Attendance Table
CREATE TABLE student_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    date DATE NOT NULL,
    subject VARCHAR(100),
    course_code VARCHAR(20),
    status ENUM('Present', 'Absent', 'Late', 'Excused') NOT NULL,
    remarks TEXT,
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_date (date),
    INDEX idx_subject (subject),
    INDEX idx_status (status)
);

-- 5. Fees Table
CREATE TABLE student_fees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    fee_type VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    due_date DATE,
    paid_date DATE,
    status ENUM('Unpaid', 'Partially Paid', 'Paid', 'Overdue') DEFAULT 'Unpaid',
    payment_method VARCHAR(50),
    receipt_number VARCHAR(50),
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_fee_type (fee_type),
    INDEX idx_status (status),
    INDEX idx_due_date (due_date)
);

-- 6. Timetables Table
CREATE TABLE student_timetables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') NOT NULL,
    time_slot VARCHAR(50) NOT NULL,
    subject VARCHAR(100) NOT NULL,
    course_code VARCHAR(20),
    lecturer VARCHAR(100),
    classroom VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_day_of_week (day_of_week),
    INDEX idx_subject (subject)
);

-- 7. Notifications Table
CREATE TABLE student_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('General', 'Academic', 'Fee', 'Attendance', 'Exam', 'Event', 'Matron', 'Bursar') DEFAULT 'General',
    priority ENUM('Low', 'Medium', 'High', 'Urgent') DEFAULT 'Medium',
    is_read BOOLEAN DEFAULT FALSE,
    action_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_type (type),
    INDEX idx_priority (priority),
    INDEX idx_is_read (is_read)
);

-- 8. Downloads Table
CREATE TABLE student_downloads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size BIGINT,
    download_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_file_type (file_type),
    INDEX idx_created_at (created_at)
);

-- 9. Student Messages to Departments
CREATE TABLE student_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    department_email VARCHAR(100) NOT NULL, -- e.g., matrons@..., bursar@...
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    replied BOOLEAN DEFAULT FALSE,
    reply_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    replied_at TIMESTAMP NULL,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_student_id (student_id),
    INDEX idx_department_email (department_email),
    INDEX idx_is_read (is_read)
);

-- 10. Password Reset Tokens
CREATE TABLE student_password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    reset_token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    is_used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_reset_token (reset_token),
    INDEX idx_expires_at (expires_at)
);

-- Views for easy access

-- Student Login View
CREATE OR REPLACE VIEW student_login_view AS
SELECT 
    id,
    student_number,
    COALESCE(full_name, TRIM(CONCAT(first_name, ' ', COALESCE(other_name, ''), ' ', surname))) AS full_name,
    email,
    password,
    COALESCE(course, program) AS course,
    status,
    last_login,
    login_attempts,
    is_first_login
FROM students
WHERE status = 'Active';

-- Student Dashboard View
CREATE OR REPLACE VIEW student_dashboard_view AS
SELECT 
    s.id,
    s.student_number,
    COALESCE(s.full_name, TRIM(CONCAT(s.first_name, ' ', COALESCE(s.other_name, ''), ' ', s.surname))) AS full_name,
    COALESCE(s.course, s.program) AS course,
    COALESCE(s.year, s.current_year) AS year,
    s.set_name,
    s.email,
    COALESCE(s.profile_picture, s.passport_photo) AS profile_picture,
    COALESCE(sa.gpa, 0) as current_gpa,
    COALESCE(sf.balance, 0) as fee_balance,
    COALESCE(sa2.attendance_rate, 0) as attendance_rate
FROM students s
LEFT JOIN (
    SELECT student_id, gpa FROM student_academic_records 
    WHERE semester = (SELECT MAX(semester) FROM student_academic_records)
    GROUP BY student_id
) sa ON s.id = sa.student_id
LEFT JOIN (
    SELECT student_id, SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) * 100.0 / COUNT(*) as attendance_rate
    FROM student_attendance GROUP BY student_id
) sa2 ON s.id = sa2.student_id
LEFT JOIN (
    SELECT student_id, SUM(amount) as balance FROM student_fees 
    WHERE status IN ('Unpaid', 'Partially Paid', 'Overdue') GROUP BY student_id
) sf ON s.id = sf.student_id
WHERE s.status = 'Active';

-- End of Students Database Schema
-- ============================================================
-- ISNM COMPLETE BURSAR FINANCIAL MANAGEMENT SYSTEM
-- Comprehensive SQL Schema with all required features
-- Database: igangaschoolofl_students_db
-- ============================================================

USE igangaschoolofl_students_db;

-- ============================================================
-- 1. BURSAR USER ACCOUNTS & AUTHENTICATION
-- ============================================================

-- Drop tables in correct order to avoid foreign key constraints
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS proof_of_payments;
DROP TABLE IF EXISTS payment_receipts;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS student_invoices;
DROP TABLE IF EXISTS student_penalties;
DROP TABLE IF EXISTS penalty_configurations;
DROP TABLE IF EXISTS fee_adjustments;
DROP TABLE IF EXISTS sponsorships;
DROP TABLE IF EXISTS student_fee_assignments;
DROP TABLE IF EXISTS fee_structures;
DROP TABLE IF EXISTS programs;
DROP TABLE IF EXISTS bursar_users;
DROP TABLE IF EXISTS financial_reports;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS fee_reminders;
DROP TABLE IF EXISTS expenditure_records;
DROP TABLE IF EXISTS budget_records;
DROP TABLE IF EXISTS budgets;
DROP TABLE IF EXISTS cost_centers;
DROP TABLE IF EXISTS chart_of_accounts;
DROP TABLE IF EXISTS general_ledger;
DROP TABLE IF EXISTS cash_book;
DROP TABLE IF EXISTS assets;
DROP TABLE IF EXISTS asset_categories;
DROP TABLE IF EXISTS staff_salaries;
DROP TABLE IF EXISTS salary_components;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS bursar_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('bursar', 'accounts_assistant', 'auditor') DEFAULT 'bursar',
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status)
);

-- ============================================================
-- 2. CHART OF ACCOUNTS
-- ============================================================

CREATE TABLE IF NOT EXISTS chart_of_accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_code VARCHAR(20) UNIQUE NOT NULL,
    account_name VARCHAR(255) NOT NULL,
    account_type ENUM('Asset', 'Liability', 'Equity', 'Revenue', 'Expense') NOT NULL,
    parent_account_id INT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_account_id) REFERENCES chart_of_accounts(id) ON DELETE SET NULL,
    INDEX idx_account_code (account_code),
    INDEX idx_account_type (account_type)
);

-- ============================================================
-- 3. COST CENTERS
-- ============================================================

CREATE TABLE IF NOT EXISTS cost_centers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cost_center_code VARCHAR(20) UNIQUE NOT NULL,
    cost_center_name VARCHAR(255) NOT NULL,
    department VARCHAR(100),
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cost_center_code (cost_center_code)
);

-- ============================================================
-- 4. PROGRAMS
-- ============================================================

CREATE TABLE IF NOT EXISTS programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_code VARCHAR(20) UNIQUE NOT NULL,
    program_name VARCHAR(255) NOT NULL,
    program_type ENUM('Certificate', 'Diploma', 'Degree') DEFAULT 'Diploma',
    duration_years INT DEFAULT 2,
    total_fee DECIMAL(12,2) DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_program_code (program_code)
);

-- ============================================================
-- 5. FEE STRUCTURES
-- ============================================================

CREATE TABLE IF NOT EXISTS fee_structures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fee_name VARCHAR(255) NOT NULL,
    fee_type ENUM('Tuition', 'Registration', 'Library', 'Laboratory', 'Examination', 'Graduation', 'Other') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    program_id INT NULL,
    academic_year VARCHAR(20),
    semester VARCHAR(50),
    is_mandatory BOOLEAN DEFAULT TRUE,
    due_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE SET NULL,
    INDEX idx_fee_type (fee_type),
    INDEX idx_academic_year (academic_year)
);

-- ============================================================
-- 6. STUDENT FEE ASSIGNMENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS student_fee_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    fee_structure_id INT NOT NULL,
    assigned_amount DECIMAL(10,2) NOT NULL,
    paid_amount DECIMAL(10,2) DEFAULT 0,
    balance DECIMAL(10,2) GENERATED ALWAYS AS (assigned_amount - paid_amount) STORED,
    status ENUM('Unpaid', 'Partially Paid', 'Paid', 'Waived') DEFAULT 'Unpaid',
    due_date DATE,
    assigned_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (fee_structure_id) REFERENCES fee_structures(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    INDEX idx_student_id (student_id),
    INDEX idx_status (status),
    INDEX idx_due_date (due_date)
);

-- ============================================================
-- 7. SPONSORSHIPS
-- ============================================================

CREATE TABLE IF NOT EXISTS sponsorships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sponsorship_code VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    sponsor_name VARCHAR(255) NOT NULL,
    sponsor_type ENUM('Government', 'NGO', 'Private', 'Self', 'Other') DEFAULT 'Self',
    sponsorship_type ENUM('Full', 'Partial', 'Tuition Only', 'Other') DEFAULT 'Partial',
    amount DECIMAL(12,2) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    terms_conditions TEXT,
    status ENUM('Active', 'Expired', 'Cancelled') DEFAULT 'Active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    INDEX idx_sponsorship_code (sponsorship_code),
    INDEX idx_student_id (student_id),
    INDEX idx_status (status)
);

-- ============================================================
-- 8. BUDGETS
-- ============================================================

CREATE TABLE IF NOT EXISTS budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    budget_name VARCHAR(255) NOT NULL,
    fiscal_year VARCHAR(20) NOT NULL,
    total_amount DECIMAL(15,2) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    status ENUM('Draft', 'Approved', 'Active', 'Closed') DEFAULT 'Draft',
    approved_by INT,
    approved_date TIMESTAMP NULL,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_fiscal_year (fiscal_year),
    INDEX idx_status (status)
);

-- ============================================================
-- 9. BUDGET RECORDS
-- ============================================================

CREATE TABLE IF NOT EXISTS budget_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    budget_id INT NOT NULL,
    budget_item VARCHAR(255) NOT NULL,
    allocated_amount DECIMAL(12,2) NOT NULL,
    spent_amount DECIMAL(12,2) DEFAULT 0,
    remaining_amount DECIMAL(12,2) GENERATED ALWAYS AS (allocated_amount - spent_amount) STORED,
    status ENUM('Active', 'Exhausted', 'Cancelled') DEFAULT 'Active',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (budget_id) REFERENCES budgets(id) ON DELETE CASCADE,
    INDEX idx_budget_id (budget_id),
    INDEX idx_status (status)
);

-- ============================================================
-- 10. STUDENT INVOICES
-- ============================================================

CREATE TABLE IF NOT EXISTS student_invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    fee_assignment_id INT NULL,
    fee_type VARCHAR(100) NOT NULL,
    description TEXT,
    total_amount DECIMAL(12,2) NOT NULL,
    discount_amount DECIMAL(12,2) DEFAULT 0,
    net_amount DECIMAL(12,2) GENERATED ALWAYS AS (total_amount - discount_amount) STORED,
    amount_paid DECIMAL(12,2) DEFAULT 0,
    balance DECIMAL(12,2) GENERATED ALWAYS AS (net_amount - amount_paid) STORED,
    status ENUM('Draft', 'Pending', 'Partially Paid', 'Paid', 'Overdue', 'Cancelled', 'Waived') DEFAULT 'Pending',
    due_date DATE,
    issue_date DATE DEFAULT (CURDATE()),
    payment_method VARCHAR(50),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (fee_assignment_id) REFERENCES student_fee_assignments(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    INDEX idx_invoice_number (invoice_number),
    INDEX idx_student_id (student_id),
    INDEX idx_status (status),
    INDEX idx_due_date (due_date)
);

-- ============================================================
-- 11. PAYMENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    payment_reference VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    invoice_id INT NULL,
    amount_received DECIMAL(12,2) NOT NULL,
    payment_method ENUM('Cash', 'Bank Transfer', 'Mobile Money', 'Cheque', 'Card', 'Other') DEFAULT 'Cash',
    payment_date DATE DEFAULT (CURDATE()),
    transaction_ref VARCHAR(100),
    slip_number VARCHAR(100),
    status ENUM('Pending', 'Completed', 'Failed', 'Reversed') DEFAULT 'Completed',
    received_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (invoice_id) REFERENCES student_invoices(id) ON DELETE SET NULL,
    FOREIGN KEY (received_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    INDEX idx_payment_reference (payment_reference),
    INDEX idx_student_id (student_id),
    INDEX idx_status (status),
    INDEX idx_payment_date (payment_date)
);

-- ============================================================
-- 12. PAYMENT RECEIPTS
-- ============================================================

CREATE TABLE IF NOT EXISTS payment_receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receipt_number VARCHAR(50) UNIQUE NOT NULL,
    payment_id INT NOT NULL,
    student_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_method VARCHAR(50),
    receipt_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    issued_by INT,
    voided BOOLEAN DEFAULT FALSE,
    voided_at TIMESTAMP NULL,
    voided_by INT,
    void_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (issued_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    FOREIGN KEY (voided_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    INDEX idx_receipt_number (receipt_number),
    INDEX idx_payment_id (payment_id),
    INDEX idx_student_id (student_id)
);

-- ============================================================
-- 13. PROOF OF PAYMENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS proof_of_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proof_number VARCHAR(50) UNIQUE NOT NULL,
    payment_id INT NOT NULL,
    student_id INT NOT NULL,
    document_path VARCHAR(500),
    uploaded_by INT,
    verified BOOLEAN DEFAULT FALSE,
    verified_by INT,
    verified_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    FOREIGN KEY (verified_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    INDEX idx_proof_number (proof_number),
    INDEX idx_payment_id (payment_id),
    INDEX idx_student_id (student_id)
);

-- ============================================================
-- 14. FEE ADJUSTMENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS fee_adjustments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    adjustment_number VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    invoice_id INT NULL,
    adjustment_type ENUM('Discount', 'Waiver', 'Penalty', 'Refund', 'Other') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    reason TEXT NOT NULL,
    approved_by INT,
    approved_at TIMESTAMP NULL,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (invoice_id) REFERENCES student_invoices(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    INDEX idx_adjustment_number (adjustment_number),
    INDEX idx_student_id (student_id),
    INDEX idx_status (status)
);

-- ============================================================
-- 15. STUDENT PENALTIES
-- ============================================================

CREATE TABLE IF NOT EXISTS student_penalties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    penalty_number VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    penalty_type VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) DEFAULT 0,
    reason TEXT,
    applied_by INT,
    applied_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    waived BOOLEAN DEFAULT FALSE,
    waived_by INT,
    waived_at TIMESTAMP NULL,
    waiver_reason TEXT,
    status ENUM('Active', 'Waived', 'Paid') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (applied_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    FOREIGN KEY (waived_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    INDEX idx_penalty_number (penalty_number),
    INDEX idx_student_id (student_id),
    INDEX idx_status (status)
);

-- ============================================================
-- 16. PENALTY CONFIGURATIONS
-- ============================================================

CREATE TABLE IF NOT EXISTS penalty_configurations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    penalty_name VARCHAR(100) NOT NULL UNIQUE,
    penalty_type VARCHAR(100),
    amount DECIMAL(10,2) DEFAULT 0,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_penalty_name (penalty_name)
);

-- ============================================================
-- 17. FEE REMINDERS
-- ============================================================

CREATE TABLE IF NOT EXISTS fee_reminders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reminder_number VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    invoice_id INT NULL,
    reminder_type ENUM('Email', 'SMS', 'Letter', 'Call') DEFAULT 'Email',
    reminder_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    sent_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (invoice_id) REFERENCES student_invoices(id) ON DELETE SET NULL,
    FOREIGN KEY (sent_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    INDEX idx_reminder_number (reminder_number),
    INDEX idx_student_id (student_id),
    INDEX idx_reminder_date (reminder_date)
);

-- ============================================================
-- 18. EXPENDITURE RECORDS
-- ============================================================

CREATE TABLE IF NOT EXISTS expenditure_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    expenditure_number VARCHAR(50) UNIQUE NOT NULL,
    budget_record_id INT NULL,
    expenditure_type VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    payment_method VARCHAR(50),
    receipt_number VARCHAR(100),
    expenditure_date DATE DEFAULT (CURDATE()),
    approved_by INT,
    recorded_by INT,
    supporting_document VARCHAR(500),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (budget_record_id) REFERENCES budget_records(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    FOREIGN KEY (recorded_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    INDEX idx_expenditure_number (expenditure_number),
    INDEX idx_expenditure_date (expenditure_date),
    INDEX idx_recorded_by (recorded_by)
);

-- ============================================================
-- 19. GENERAL LEDGER
-- ============================================================

CREATE TABLE IF NOT EXISTS general_ledger (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entry_number VARCHAR(50) UNIQUE NOT NULL,
    account_id INT NOT NULL,
    cost_center_id INT NULL,
    transaction_type ENUM('Debit', 'Credit') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    reference_type VARCHAR(50),
    reference_id INT,
    description TEXT,
    transaction_date DATE DEFAULT (CURDATE()),
    posted_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES chart_of_accounts(id) ON DELETE CASCADE,
    FOREIGN KEY (cost_center_id) REFERENCES cost_centers(id) ON DELETE SET NULL,
    FOREIGN KEY (posted_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    INDEX idx_entry_number (entry_number),
    INDEX idx_account_id (account_id),
    INDEX idx_transaction_date (transaction_date)
);

-- ============================================================
-- 20. CASH BOOK
-- ============================================================

CREATE TABLE IF NOT EXISTS cash_book (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entry_number VARCHAR(50) UNIQUE NOT NULL,
    entry_type ENUM('Receipt', 'Payment') NOT NULL,
    description TEXT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    balance DECIMAL(15,2) NOT NULL,
    payment_method VARCHAR(50),
    reference_number VARCHAR(100),
    related_student_id INT NULL,
    transaction_date DATE DEFAULT (CURDATE()),
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (related_student_id) REFERENCES students(id) ON DELETE SET NULL,
    FOREIGN KEY (recorded_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    INDEX idx_entry_number (entry_number),
    INDEX idx_entry_type (entry_type),
    INDEX idx_transaction_date (transaction_date)
);

-- ============================================================
-- 21. ASSET CATEGORIES
-- ============================================================

CREATE TABLE IF NOT EXISTS asset_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    depreciation_rate DECIMAL(5,2) DEFAULT 0,
    useful_life_years INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- 22. ASSETS
-- ============================================================

CREATE TABLE IF NOT EXISTS assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_tag VARCHAR(50) UNIQUE NOT NULL,
    asset_name VARCHAR(255) NOT NULL,
    category_id INT NULL,
    purchase_date DATE,
    purchase_price DECIMAL(12,2),
    current_value DECIMAL(12,2),
    location VARCHAR(255),
    assigned_to INT NULL,
    status ENUM('Active', 'Disposed', 'Lost', 'Under Maintenance') DEFAULT 'Active',
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES asset_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    INDEX idx_asset_tag (asset_tag),
    INDEX idx_status (status)
);

-- ============================================================
-- 23. SALARY COMPONENTS
-- ============================================================

CREATE TABLE IF NOT EXISTS salary_components (
    id INT AUTO_INCREMENT PRIMARY KEY,
    component_name VARCHAR(100) NOT NULL UNIQUE,
    component_type ENUM('Earning', 'Deduction') DEFAULT 'Earning',
    description TEXT,
    is_percentage BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ============================================================
-- 24. STAFF SALARIES
-- ============================================================

CREATE TABLE IF NOT EXISTS staff_salaries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    base_salary DECIMAL(12,2) NOT NULL,
    allowances DECIMAL(12,2) DEFAULT 0,
    deductions DECIMAL(12,2) DEFAULT 0,
    net_salary DECIMAL(12,2) GENERATED ALWAYS AS (base_salary + allowances - deductions) STORED,
    effective_date DATE NOT NULL,
    end_date DATE NULL,
    status ENUM('Active', 'Inactive', 'Pending') DEFAULT 'Active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    INDEX idx_staff_id (staff_id),
    INDEX idx_effective_date (effective_date),
    INDEX idx_status (status)
);

-- ============================================================
-- 25. FINANCIAL REPORTS
-- ============================================================

CREATE TABLE IF NOT EXISTS financial_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_name VARCHAR(255) NOT NULL,
    report_type ENUM('Income Statement', 'Balance Sheet', 'Cash Flow', 'Budget vs Actual', 'Fee Collection', 'Expenditure', 'Custom') NOT NULL,
    report_period VARCHAR(50),
    start_date DATE,
    end_date DATE,
    report_data LONGTEXT,
    generated_by INT,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('Draft', 'Final', 'Archived') DEFAULT 'Draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (generated_by) REFERENCES igangaschoolofl_staffs_db.staff(id) ON DELETE SET NULL,
    INDEX idx_report_type (report_type),
    INDEX idx_generated_at (generated_at)
);

-- ============================================================
-- 26. NOTIFICATIONS
-- ============================================================

CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    notification_type ENUM('fee_reminder', 'payment_received', 'invoice_generated', 'budget_alert', 'system') DEFAULT 'system',
    recipient_type ENUM('student', 'staff', 'bursar') NOT NULL,
    recipient_id INT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    channel ENUM('email', 'sms', 'in_app') DEFAULT 'in_app',
    is_read BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_recipient_type (recipient_type),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
);

-- ============================================================
-- 27. INSERT DEFAULT PENALTY CONFIGURATIONS
-- ============================================================

INSERT IGNORE INTO penalty_configurations (penalty_name, penalty_type, amount, description) VALUES
('Late Registration', 'Late Fee', 50000, 'Penalty for late course registration'),
('Late Payment (1-7 days)', 'Late Fee', 10000, 'Penalty for fee payment 1-7 days after due date'),
('Late Payment (8-14 days)', 'Late Fee', 25000, 'Penalty for fee payment 8-14 days after due date'),
('Late Payment (15+ days)', 'Late Fee', 50000, 'Penalty for fee payment more than 15 days after due date'),
('Lost Library Book', 'Replacement', 30000, 'Replacement fee for lost library book'),
('Damaged Property', 'Damage', 20000, 'Penalty for damaging school property'),
('ID Card Replacement', 'Administrative', 10000, 'Fee for replacement of lost student ID card');

-- ============================================================
-- 28. INSERT DEFAULT CHART OF ACCOUNTS
-- ============================================================

INSERT IGNORE INTO chart_of_accounts (account_code, account_name, account_type, description) VALUES
('1000', 'Cash and Cash Equivalents', 'Asset', 'Cash on hand and in bank'),
('1100', 'Accounts Receivable', 'Asset', 'Student fees receivable'),
('1200', 'Inventory', 'Asset', 'Supplies and inventory'),
('1500', 'Fixed Assets', 'Asset', 'Property, plant and equipment'),
('2000', 'Accounts Payable', 'Liability', 'Amounts owed to suppliers'),
('2100', 'Accrued Liabilities', 'Liability', 'Accrued expenses'),
('3000', 'Net Assets', 'Equity', 'Institution net worth'),
('4000', 'Tuition Revenue', 'Revenue', 'Income from student tuition'),
('4100', 'Registration Revenue', 'Revenue', 'Income from student registration'),
('4200', 'Other Revenue', 'Revenue', 'Miscellaneous income'),
('5000', 'Salary Expenses', 'Expense', 'Staff salaries and wages'),
('5100', 'Administrative Expenses', 'Expense', 'Office and administrative costs'),
('5200', 'Operational Expenses', 'Expense', 'Day-to-day operational costs'),
('5300', 'Maintenance Expenses', 'Expense', 'Facility maintenance costs');

-- ============================================================
-- 29. INSERT DEFAULT COST CENTERS
-- ============================================================

INSERT IGNORE INTO cost_centers (cost_center_code, cost_center_name, department) VALUES
('CC-EXEC', 'Executive Office', 'Executive Office'),
('CC-NUR', 'Nursing Department', 'Nursing Department'),
('CC-MID', 'Midwifery Department', 'Midwifery Department'),
('CC-ACAD', 'Academic Affairs', 'Academic Affairs'),
('CC-FIN', 'Finance Department', 'Finance Department'),
('CC-HR', 'Human Resources', 'Human Resources'),
('CC-LIB', 'Library Services', 'Library Services'),
('CC-STU', 'Student Affairs', 'Student Affairs'),
('CC-SEC', 'Security Services', 'Security Services'),
('CC-ICT', 'Information Technology', 'Information Technology'),
('CC-FAC', 'Facilities Management', 'Facilities Management');

-- ============================================================
-- END OF BURSAR SYSTEM
-- ============================================================

-- Step 4: Department Dashboards & Views
-- ============================================================
-- ISNM COMPLETE ALL DEPARTMENTS DASHBOARDS SQL
-- Staff-specific tables only - student data is in students database
-- Run AFTER 04_final_complete_staffs_database.sql
-- Prerequisites for views: students database (igangaschoolofl_students_db)
-- Run AFTER sql/students/01_create_students_database.sql
-- ============================================================

USE igangaschoolofl_staffs_db;

-- ============================================================
-- STUDENT PROFILES VIEW (for cross-database access)
-- References students from igangaschoolofl_students_db
-- ============================================================

-- Create view for student search across databases
CREATE OR REPLACE VIEW universal_student_profiles AS
SELECT 
    s.id,
    s.student_number,
    s.national_student_id_number as national_id,
    s.index_number,
    s.registration_number,
    s.first_name,
    s.other_name as middle_name,
    s.surname as last_name,
    TRIM(CONCAT(s.first_name, ' ', COALESCE(s.other_name, ''), ' ', s.surname)) as full_name,
    s.email,
    s.phone,
    s.date_of_birth,
    s.gender,
    s.program,
    s.course,
    s.set_name as intake_set,
    s.intake_date,
    s.current_year as year_of_study,
    s.current_semester as semester,
    s.nationality,
    NULL as religion,
    s.address,
    NULL as district,
    s.guardian_name,
    s.guardian_phone,
    s.emergency_contact_name,
    s.emergency_contact_phone,
    s.profile_picture as photo_path,
    CASE WHEN s.profile_picture IS NOT NULL THEN TRUE ELSE FALSE END as photo_uploaded,
    s.status,
    s.created_at,
    s.updated_at
FROM igangaschoolofl_students_db.students s;

-- ============================================================
-- 6. VIEW FOR STUDENT SEARCH
-- Comprehensive view for searching students
-- ============================================================

CREATE OR REPLACE VIEW student_search_view AS
SELECT 
    sp.id,
    sp.student_number,
    sp.national_id,
    sp.index_number,
    sp.registration_number,
    sp.full_name,
    sp.first_name,
    sp.last_name,
    sp.email,
    sp.phone,
    sp.program,
    sp.intake_set,
    sp.year_of_study,
    sp.semester,
    sp.status,
    sp.district,
    sp.guardian_name,
    sp.guardian_phone,
    sp.photo_path as current_photo,
    COALESCE(sp.photo_uploaded, FALSE) as has_photo,
    NULL as staff_dashboard
FROM universal_student_profiles sp;

-- ============================================================
-- 7. VIEW FOR ALL STUDENTS (FOR CROSS-DEPARTMENT ACCESS)
-- ============================================================

CREATE OR REPLACE VIEW all_students_view AS
SELECT 
    sp.*,
    CASE 
        WHEN sp.photo_uploaded = TRUE THEN CONCAT('Photo Available: ', sp.photo_path)
        ELSE 'No Photo Available'
    END as photo_status
FROM universal_student_profiles sp;

-- ============================================================
-- 8. PROCEDURES FOR STUDENT SEARCH AND MANAGEMENT
-- ============================================================

-- Ensure no conflicting procedure exists before creating (prevents #1304)
DROP PROCEDURE IF EXISTS get_all_students;
DROP PROCEDURE IF EXISTS search_all_students;

DELIMITER //

-- Search all students by various criteria
CREATE OR REPLACE PROCEDURE search_all_students(
    IN p_search_term VARCHAR(255),
    IN p_program VARCHAR(100),
    IN p_intake_set VARCHAR(50),
    IN p_status VARCHAR(50),
    IN p_limit INT
)
BEGIN
    IF p_limit IS NULL OR p_limit <= 0 THEN
        SET p_limit = 100;
    END IF;
    SELECT 
        s.id, s.student_number, s.full_name, s.program, s.set_name as intake_set, 
        s.current_year as year_of_study, s.status, s.email
    FROM igangaschoolofl_students_db.students s
    WHERE (p_search_term IS NULL OR 
           s.full_name LIKE CONCAT('%', p_search_term, '%') OR
           s.student_number LIKE CONCAT('%', p_search_term, '%') OR
           s.index_number LIKE CONCAT('%', p_search_term, '%') OR
           s.national_student_id_number LIKE CONCAT('%', p_search_term, '%'))
      AND (p_program IS NULL OR s.program = p_program)
      AND (p_intake_set IS NULL OR s.set_name = p_intake_set)
      AND (p_status IS NULL OR s.status = p_status)
    ORDER BY s.full_name
    LIMIT p_limit;
END //

-- Get all students from all intake sets
CREATE PROCEDURE get_all_students()
BEGIN
    SELECT 
        set_name as intake_set,
        COUNT(*) as total_students,
        COUNT(CASE WHEN profile_picture IS NOT NULL THEN 1 END) as students_with_photos
    FROM igangaschoolofl_students_db.students
    GROUP BY set_name
    ORDER BY set_name DESC;
END //

DELIMITER ;

-- ============================================================
-- 10. GRANT DASHBOARD ACCESS TO ALL STAFF
-- ============================================================
-- Ensure `staff_dashboard_access` table exists (some environments may not have it)
CREATE TABLE IF NOT EXISTS staff_dashboard_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    dashboard_path VARCHAR(255) NOT NULL,
    access_level VARCHAR(50) DEFAULT 'Full',
    granted_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_staff_id (staff_id),
    INDEX idx_dashboard_path (dashboard_path),
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO staff_dashboard_access (staff_id, dashboard_path, access_level, granted_by)
SELECT 
    s.id,
    sr.dashboard_path,
    'Full',
    1
FROM staff s
JOIN staff_roles sr ON s.role_id = sr.id
WHERE sr.role_name IN (
     'Director General', 'CEO', 'Director Academics', 'Director ICT', 
     'Director Finance', 'School Principal', 'Deputy Principal', 'School Bursar',
     'Director Admissions & Requirements', 'Academic Registrar', 'HR Manager',
     'School Secretary', 'School Librarian', 'Head Nursing', 'Head Midwifery',
     'Senior Lecturers', 'Lecturers', 'Matrons', 'Wardens', 'Sickbay',
     'Drivers', 'Security', 'Store Keeper', 'Computer Lab Manager', 'Guild President'
);

-- ============================================================
-- 11. INSERT DEFAULT INSTITUTE SETTINGS
-- ============================================================

-- Ensure system_settings table exists
CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value LONGTEXT,
    setting_type ENUM('text', 'number', 'boolean', 'file', 'json') DEFAULT 'text',
    description TEXT,
    category VARCHAR(50) DEFAULT 'general',
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key),
    INDEX idx_setting_type (setting_type),
    INDEX idx_category (category),
    INDEX idx_is_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('institute_name', 'Iganga School of Nursing and Midwifery', 'text', 'Full institute name', TRUE),
('institute_short', 'ISNM', 'text', 'Short name', TRUE),
('institute_email', 'info@igangaschoolofnursingandmidwifery.ac.ug', 'email', 'Main email', TRUE),
('institute_phone', '+256-701-000-000', 'text', 'Main phone', TRUE),
('institute_address', 'Iganga, Uganda', 'text', 'Physical address', TRUE),
('academic_year', '2025/2026', 'text', 'Current academic year', TRUE),
('current_semester', 'Semester 2', 'text', 'Current semester', TRUE),
('allow_student_search', 'true', 'boolean', 'Enable student search', FALSE),
('allow_student_photo_upload', 'true', 'boolean', 'Enable photo upload', FALSE),
('allow_student_print', 'true', 'boolean', 'Enable printing', FALSE),
('max_search_results', '500', 'number', 'Max search results', FALSE);

COMMIT;
-- ============================================================
-- ISNM ACADEMIC REGISTRAR DASHBOARD SQL
-- Complete Academic Records Management System
-- ============================================================

USE igangaschoolofl_staffs_db;

-- ============================================================
-- 1. ACADEMIC REGISTRAR USER ACCOUNTS
-- ============================================================

INSERT IGNORE INTO staff (
    staff_id, 
    full_name, 
    email, 
    password, 
    phone, 
    position, 
    department, 
    role_id, 
    status, 
    hire_date,
    created_at
) VALUES
('REG001', 'Academic Registrar', 'registrar@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$registrar@isnmHashedPassword', '+256701000010', 'Academic Registrar', 'Academic Affairs',
 (SELECT id FROM staff_roles WHERE role_name = 'Academic Registrar' LIMIT 1), 'Active', CURDATE(), NOW()),
('AR002', 'Assistant Registrar', 'assistant_registrar@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$assistant_registrar@isnmHashedPassword', '+256701000025', 'Assistant Registrar', 'Academic Affairs',
 (SELECT id FROM staff_roles WHERE role_name = 'Academic Registrar' LIMIT 1), 'Active', CURDATE(), NOW());

-- ============================================================
-- 2. ACADEMIC REGISTRAR TABLES
-- ============================================================

-- Student Registration Management
CREATE TABLE IF NOT EXISTS registrar_student_registration (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    registration_number VARCHAR(50) UNIQUE NOT NULL,
    intake_set VARCHAR(20),
    program VARCHAR(100) NOT NULL,
    program_type ENUM('Certificate', 'Diploma', 'Degree') DEFAULT 'Diploma',
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(50) DEFAULT 'Semester 1',
    year_of_study INT DEFAULT 1,
    registration_date DATE NOT NULL,
    registration_status ENUM('Registered', 'Pending', 'Rejected', 'Cancelled') DEFAULT 'Pending',
    registration_fee DECIMAL(10,2) DEFAULT 0,
    registration_payment_status ENUM('Paid', 'Partial', 'Unpaid') DEFAULT 'Unpaid',
    registered_by INT,
    approved_by INT,
    approved_date TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES igangaschoolofl_students_db.students(id) ON DELETE CASCADE,
    FOREIGN KEY (registered_by) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_student_id (student_id),
    INDEX idx_registration_number (registration_number),
    INDEX idx_registration_date (registration_date)
);

-- Transcripts Management
CREATE TABLE IF NOT EXISTS registrar_transcripts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transcript_number VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    academic_year VARCHAR(20),
    program VARCHAR(100),
    cgpa DECIMAL(3,2),
    class_of_degree VARCHAR(50),
    transcript_status ENUM('Draft', 'Requested', 'Processing', 'Ready', 'Issued', 'Collected') DEFAULT 'Draft',
    requested_by INT,
    request_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_by INT,
    processed_date TIMESTAMP NULL,
    issued_by INT,
    issued_date TIMESTAMP NULL,
    collected_date TIMESTAMP NULL,
    collection_signature VARCHAR(255),
    purpose TEXT,
    copies_requested INT DEFAULT 1,
    copies_issued INT DEFAULT 0,
    fee DECIMAL(10,2) DEFAULT 0,
    payment_status ENUM('Paid', 'Unpaid') DEFAULT 'Unpaid',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES igangaschoolofl_students_db.students(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (processed_by) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (issued_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_transcript_number (transcript_number),
    INDEX idx_student_id (student_id),
    INDEX idx_status (transcript_status)
);

-- Academic Records
CREATE TABLE IF NOT EXISTS registrar_academic_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(50) NOT NULL,
    program VARCHAR(100),
    level VARCHAR(50),
    courses_taken INT,
    credits_earned INT,
    gpa DECIMAL(3,2),
    cgpa DECIMAL(3,2),
    academic_standing ENUM('Good Standing', 'Probation', 'Suspension') DEFAULT 'Good Standing',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES igangaschoolofl_students_db.students(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_academic_year (academic_year)
);

-- Graduation Management
CREATE TABLE IF NOT EXISTS registrar_graduation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    graduation_type ENUM('Certificate', 'Diploma', 'Degree') DEFAULT 'Diploma',
    graduation_date DATE,
    ceremony_date DATE,
    certificate_number VARCHAR(50),
    academic_year VARCHAR(20),
    program VARCHAR(100),
    gpa DECIMAL(3,2),
    cgpa DECIMAL(3,2),
    graduation_status ENUM('Eligible', 'Not Eligible', 'Applied', 'Approved', 'Graduated', 'Deferred') DEFAULT 'Eligible',
    application_date TIMESTAMP NULL,
    approved_by INT,
    approval_date TIMESTAMP NULL,
    certificate_issued BOOLEAN DEFAULT FALSE,
    certificate_issued_date TIMESTAMP NULL,
    graduation_fee DECIMAL(10,2) DEFAULT 0,
    payment_status ENUM('Paid', 'Unpaid') DEFAULT 'Unpaid',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES igangaschoolofl_students_db.students(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_student_id (student_id),
    INDEX idx_graduation_status (graduation_status)
);

-- Academic Calendar
CREATE TABLE IF NOT EXISTS registrar_academic_calendar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(50) NOT NULL,
    semester_start DATE NOT NULL,
    semester_end DATE NOT NULL,
    registration_start DATE,
    registration_end DATE,
    add_drop_deadline DATE,
    withdrawal_deadline DATE,
    exam_start DATE,
    exam_end DATE,
    result_publication_date DATE,
    status ENUM('Upcoming', 'Current', 'Completed', 'Cancelled') DEFAULT 'Upcoming',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_academic_year (academic_year),
    INDEX idx_semester (semester)
);

-- ============================================================
-- 3. PROCEDURES FOR ACADEMIC REGISTRAR
-- ============================================================

DELIMITER //

CREATE PROCEDURE get_student_registration_status(IN p_student_id INT)
BEGIN
    SELECT 
        sp.student_number,
        sp.full_name,
        sp.program,
        rr.registration_number,
        rr.registration_status,
        rr.registration_date,
        rr.academic_year,
        rr.semester
    FROM universal_student_profiles sp
    LEFT JOIN registrar_student_registration rr ON sp.id = rr.student_id
    WHERE sp.id = p_student_id;
END //

CREATE PROCEDURE generate_transcript_request(
    IN p_student_id INT,
    IN p_requested_by INT,
    IN p_purpose TEXT,
    IN p_copies INT
)
BEGIN
    DECLARE v_transcript_number VARCHAR(50);
    SET v_transcript_number = CONCAT('TRN', DATE_FORMAT(NOW(), '%Y%m%d'), LPAD(LAST_INSERT_ID() + 1, 4, '0'));
    
    INSERT INTO registrar_transcripts (
        transcript_number, student_id, requested_by, purpose, copies_requested
    ) VALUES (
        v_transcript_number, p_student_id, p_requested_by, p_purpose, p_copies
    );
END //

CREATE PROCEDURE get_graduation_eligible_students()
BEGIN
    SELECT 
        sp.student_number,
        sp.full_name,
        sp.program,
        sp.year_of_study,
        ra.gpa,
        ra.cgpa,
        ra.academic_standing
    FROM universal_student_profiles sp
    JOIN registrar_academic_records ra ON sp.id = ra.student_id
    WHERE sp.year_of_study >= 2 
      AND ra.cgpa >= 2.00
      AND sp.status = 'Active';
END //

DELIMITER ;

-- Insert default academic calendar
INSERT IGNORE INTO registrar_academic_calendar (
    academic_year, semester, semester_start, semester_end,
    registration_start, registration_end, add_drop_deadline,
    withdrawal_deadline, exam_start, exam_end, result_publication_date, status
) VALUES
('2025/2026', 'Semester 1', '2025-09-01', '2025-12-15',
 '2025-08-15', '2025-09-15', '2025-09-30',
 '2025-10-31', '2025-12-01', '2025-12-15', '2026-01-15', 'Current');

COMMIT;
-- ============================================================
-- ISNM NURSING DEPARTMENT DASHBOARD SQL
-- Complete Nursing Department Management System
-- ============================================================

USE igangaschoolofl_staffs_db;

-- ============================================================
-- 1. NURSING DEPARTMENT USER ACCOUNTS
-- ============================================================

INSERT IGNORE INTO staff (
    staff_id, 
    full_name, 
    email, 
    password, 
    phone, 
    position, 
    department, 
    role_id, 
    status, 
    hire_date,
    created_at
) VALUES
('NUR001', 'Head of Nursing', 'nursing@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$nursing@isnmHashedPassword', '+256701000014', 'Head of Nursing', 'Nursing Department',
 (SELECT id FROM staff_roles WHERE role_name = 'Head Nursing' LIMIT 1), 'Active', CURDATE(), NOW()),
('NURSE001', 'Senior Nursing Officer', 'senior_nurse@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$senior_nurse@isnmHashedPassword', '+256701000026', 'Senior Nursing Officer', 'Nursing Department',
 (SELECT id FROM staff_roles WHERE role_name = 'Senior Lecturers' LIMIT 1), 'Active', CURDATE(), NOW());

-- ============================================================
-- 2. NURSING DEPARTMENT TABLES
-- ============================================================

-- Nursing Students Management
CREATE TABLE IF NOT EXISTS nursing_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    student_number VARCHAR(50) UNIQUE NOT NULL,
    index_number VARCHAR(50),
    national_id VARCHAR(50),
    
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    
    program ENUM('Diploma in Nursing', 'BSc Nursing', 'Upgrading Nursing') DEFAULT 'Diploma in Nursing',
    intake_set VARCHAR(20),
    intake_date DATE,
    
    nationality VARCHAR(50) DEFAULT 'Ugandan',
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    marital_status ENUM('Single', 'Married', 'Divorced', 'Widowed'),
    
    district VARCHAR(100),
    county VARCHAR(100),
    sub_county VARCHAR(100),
    
    guardian_name VARCHAR(200),
    guardian_phone VARCHAR(20),
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    
    photo_path VARCHAR(500),
    photo_uploaded BOOLEAN DEFAULT FALSE,
    photo_upload_date TIMESTAMP NULL,
    
    status ENUM('Active', 'Inactive', 'Graduated', 'Suspended', 'Withdrawn') DEFAULT 'Active',
    year_of_study INT DEFAULT 1,
    semester VARCHAR(50) DEFAULT 'Semester 1',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES igangaschoolofl_students_db.students(id) ON DELETE CASCADE,
    INDEX idx_student_number (student_number),
    INDEX idx_full_name (full_name),
    INDEX idx_intake_set (intake_set)
);

-- Nursing Clinical Placements
CREATE TABLE IF NOT EXISTS nursing_clinical_placements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    placement_number VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    placement_site VARCHAR(255) NOT NULL,
    placement_department VARCHAR(100),
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    duration_days INT,
    supervisor_name VARCHAR(255),
    supervisor_contact VARCHAR(20),
    objectives TEXT,
    learning_outcomes TEXT,
    assessment_marks DECIMAL(5,2),
    status ENUM('Scheduled', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    report_submitted BOOLEAN DEFAULT FALSE,
    report_file VARCHAR(500),
    graded_by INT,
    graded_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES nursing_students(id) ON DELETE CASCADE,
    FOREIGN KEY (graded_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_placement_number (placement_number),
    INDEX idx_student_id (student_id),
    INDEX idx_status (status)
);

-- Nursing Clinical Log Book
CREATE TABLE IF NOT EXISTS nursing_clinical_logbook (
    id INT AUTO_INCREMENT PRIMARY KEY,
    logbook_id VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    placement_id INT,
    log_date DATE NOT NULL,
    shift ENUM('Morning', 'Afternoon', 'Night') DEFAULT 'Morning',
    patient_name VARCHAR(255),
    patient_age INT,
    patient_gender ENUM('Male', 'Female', 'Other'),
    diagnosis TEXT,
    procedure_performed TEXT,
    observations TEXT,
    interventions TEXT,
    outcomes TEXT,
    supervisor_initials VARCHAR(10),
    logged_by INT,
    log_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES nursing_students(id) ON DELETE CASCADE,
    FOREIGN KEY (logged_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_logbook_id (logbook_id),
    INDEX idx_student_id (student_id),
    INDEX idx_log_date (log_date)
);

-- Nursing Practical Assessment
CREATE TABLE IF NOT EXISTS nursing_practical_assessment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assessment_id VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    assessment_type ENUM('OSCE', 'VIVA', 'Practical', 'Clinical') NOT NULL,
    assessment_name VARCHAR(255) NOT NULL,
    date_conducted DATE,
    max_marks DECIMAL(5,2),
    marks_obtained DECIMAL(5,2),
    percentage DECIMAL(5,2),
    grade VARCHAR(10),
    assessor_id INT,
    assessor_comments TEXT,
    student_comments TEXT,
    status ENUM('Scheduled', 'Conducted', 'Graded', 'Reviewed') DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES nursing_students(id) ON DELETE CASCADE,
    FOREIGN KEY (assessor_id) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_assessment_id (assessment_id),
    INDEX idx_student_id (student_id),
    INDEX idx_status (status)
);

-- Nursing Skills Training
CREATE TABLE IF NOT EXISTS nursing_skills_training (
    id INT AUTO_INCREMENT PRIMARY KEY,
    training_id VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    skill_name VARCHAR(255) NOT NULL,
    skill_category VARCHAR(100),
    training_date DATE NOT NULL,
    trainer_id INT,
    competence_level ENUM('Beginner', 'Developing', 'Competent', 'Proficient', 'Expert') DEFAULT 'Beginner',
    assessment_score DECIMAL(5,2),
    certification_issued BOOLEAN DEFAULT FALSE,
    certificate_number VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES nursing_students(id) ON DELETE CASCADE,
    FOREIGN KEY (trainer_id) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_training_id (training_id),
    INDEX idx_student_id (student_id)
);

-- ============================================================
-- 3. PROCEDURES FOR NURSING DEPARTMENT
-- ============================================================

DELIMITER //

CREATE PROCEDURE get_nursing_students_by_intake(IN p_intake_set VARCHAR(20))
BEGIN
    SELECT 
        ns.student_number,
        ns.full_name,
        ns.program,
        ns.year_of_study,
        ns.semester,
        ns.status,
        ns.photo_path
    FROM nursing_students ns
    WHERE ns.intake_set = p_intake_set
    ORDER BY ns.student_number;
END //

CREATE PROCEDURE record_clinical_placement(
    IN p_student_id INT,
    IN p_site VARCHAR(255),
    IN p_start_date DATE,
    IN p_end_date DATE,
    IN p_supervisor VARCHAR(255)
)
BEGIN
    DECLARE v_placement_number VARCHAR(50);
    SET v_placement_number = CONCAT('CLIN', DATE_FORMAT(NOW(), '%Y%m%d'), LPAD(p_student_id, 4, '0'));
    
    INSERT INTO nursing_clinical_placements (
        placement_number, student_id, placement_site, start_date, end_date, supervisor_name
    ) VALUES (
        v_placement_number, p_student_id, p_site, p_start_date, p_end_date, p_supervisor
    );
END //

CREATE PROCEDURE get_clinical_logbook(IN p_student_id INT)
BEGIN
    SELECT 
        cl.log_date,
        cl.shift,
        cl.patient_name,
        cl.diagnosis,
        cl.interventions,
        cl.outcomes,
        cl.supervisor_initials
    FROM nursing_clinical_logbook cl
    WHERE cl.student_id = p_student_id
    ORDER BY cl.log_date DESC;
END //

CREATE PROCEDURE get_nursing_students_search(IN p_search_term VARCHAR(255))
BEGIN
    SELECT 
        ns.id,
        ns.student_number,
        ns.full_name,
        ns.program,
        ns.intake_set,
        ns.status,
        ns.photo_path,
        COALESCE(ns.photo_uploaded, FALSE) as has_photo
    FROM nursing_students ns
    WHERE ns.full_name LIKE CONCAT('%', p_search_term, '%')
       OR ns.student_number LIKE CONCAT('%', p_search_term, '%')
       OR ns.index_number LIKE CONCAT('%', p_search_term, '%')
    LIMIT 100;
END //

DELIMITER ;

COMMIT;
-- ============================================================
-- ISNM MIDWYIFERY DEPARTMENT DASHBOARD SQL
-- Complete Midwifery Department Management System
-- ============================================================

USE igangaschoolofl_staffs_db;

-- ============================================================
-- 1. MIDWYIFERY DEPARTMENT USER ACCOUNTS
-- ============================================================

INSERT IGNORE INTO staff (
    staff_id, 
    full_name, 
    email, 
    password, 
    phone, 
    position, 
    department, 
    role_id, 
    status, 
    hire_date,
    created_at
) VALUES
('MID001', 'Head of Midwifery', 'midwifery@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$midwifery@isnmHashedPassword', '+256701000015', 'Head of Midwifery', 'Midwifery Department',
 (SELECT id FROM staff_roles WHERE role_name = 'Head Midwifery' LIMIT 1), 'Active', CURDATE(), NOW()),
('MIDW001', 'Senior Midwifery Officer', 'senior_midwife@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$senior_midwife@isnmHashedPassword', '+256701000027', 'Senior Midwifery Officer', 'Midwifery Department',
 (SELECT id FROM staff_roles WHERE role_name = 'Senior Lecturers' LIMIT 1), 'Active', CURDATE(), NOW());

-- ============================================================
-- 2. MIDWYIFERY DEPARTMENT TABLES
-- ============================================================

-- Midwifery Students Management
CREATE TABLE IF NOT EXISTS midwifery_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    student_number VARCHAR(50) UNIQUE NOT NULL,
    index_number VARCHAR(50),
    national_id VARCHAR(50),
    
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    
    program ENUM('Certificate in Midwifery', 'Diploma in Midwifery', 'Upgrading Midwifery') DEFAULT 'Diploma in Midwifery',
    intake_set VARCHAR(20),
    intake_date DATE,
    
    nationality VARCHAR(50) DEFAULT 'Ugandan',
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    marital_status ENUM('Single', 'Married', 'Divorced', 'Widowed'),
    no_of_children INT,
    
    district VARCHAR(100),
    county VARCHAR(100),
    sub_county VARCHAR(100),
    
    guardian_name VARCHAR(200),
    guardian_phone VARCHAR(20),
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(20),
    
    photo_path VARCHAR(500),
    photo_uploaded BOOLEAN DEFAULT FALSE,
    photo_upload_date TIMESTAMP NULL,
    
    status ENUM('Active', 'Inactive', 'Graduated', 'Suspended', 'Withdrawn') DEFAULT 'Active',
    year_of_study INT DEFAULT 1,
    semester VARCHAR(50) DEFAULT 'Semester 1',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES igangaschoolofl_students_db.students(id) ON DELETE CASCADE,
    INDEX idx_student_number (student_number),
    INDEX idx_full_name (full_name),
    INDEX idx_intake_set (intake_set)
);

-- Antenatal Care Records
CREATE TABLE IF NOT EXISTS midwifery_antenatal_care (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    patient_name VARCHAR(255) NOT NULL,
    patient_age INT,
    gravida INT,
    para INT,
    antenatal_visit_date DATE NOT NULL,
    gestational_age_weeks INT,
    blood_pressure VARCHAR(20),
    weight_kg DECIMAL(5,2),
    fetal_heart_rate INT,
    fundal_height_cm INT,
    presentation ENUM('Cephalic', 'Breech', 'Transverse') DEFAULT 'Cephalic',
    pallor BOOLEAN DEFAULT FALSE,
    edema BOOLEAN DEFAULT FALSE,
    proteinuria BOOLEAN DEFAULT FALSE,
    diagnosis TEXT,
    management_plan TEXT,
    medication_given TEXT,
    next_visit_date DATE,
    supervised_by VARCHAR(255),
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES midwifery_students(id) ON DELETE CASCADE,
    INDEX idx_record_id (record_id),
    INDEX idx_student_id (student_id),
    INDEX idx_visit_date (antenatal_visit_date)
);

-- Labor and Delivery Records
CREATE TABLE IF NOT EXISTS midwifery_labor_delivery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    delivery_id VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    patient_name VARCHAR(255) NOT NULL,
    patient_age INT,
    gravida INT,
    para INT,
    delivery_date DATE NOT NULL,
    delivery_time TIME,
    delivery_type ENUM('Spontaneous Vaginal', 'Assisted', 'Elective C/S', 'Emergency C/S', 'Vacuum', 'Forceps') DEFAULT 'Spontaneous Vaginal',
    labor_duration_hours DECIMAL(5,2),
    rupture_of_membranes BOOLEAN DEFAULT FALSE,
    rupture_time TIME,
    oxytocin_used BOOLEAN DEFAULT FALSE,
    episiotomy BOOLEAN DEFAULT FALSE,
    perineal_tear ENUM('None', '1st Degree', '2nd Degree', '3rd Degree', '4th Degree') DEFAULT 'None',
    placenta_complete BOOLEAN DEFAULT TRUE,
    blood_loss_ml INT,
    newborn_gender ENUM('Male', 'Female', 'Other'),
    newborn_weight_gm INT,
    newborn_apgar_score INT,
    complications TEXT,
    interventions TEXT,
    medications_administered TEXT,
    outcome ENUM('Live Birth', 'Still Birth', 'Maternal Death') DEFAULT 'Live Birth',
    supervised_by VARCHAR(255),
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES midwifery_students(id) ON DELETE CASCADE,
    INDEX idx_delivery_id (delivery_id),
    INDEX idx_student_id (student_id),
    INDEX idx_delivery_date (delivery_date)
);

-- Postnatal Care Records
CREATE TABLE IF NOT EXISTS midwifery_postnatal_care (
    id INT AUTO_INCREMENT PRIMARY KEY,
    postnatal_id VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    patient_name VARCHAR(255) NOT NULL,
    visit_number INT,
    visit_date DATE NOT NULL,
    days_post_delivery INT,
    maternal_condition TEXT,
    uterus_involution BOOLEAN DEFAULT TRUE,
    lochia_type ENUM('Rubra', 'Serosa', 'Alba'),
    lochia_amount ENUM('Scanty', 'Moderate', 'Heavy'),
    perineal_wound_healing BOOLEAN DEFAULT TRUE,
    breastfeeding_status ENUM('Exclusive', 'Partial', 'None') DEFAULT 'Exclusive',
    newborn_condition TEXT,
    newborn_weight DECIMAL(5,2),
    newborn_feeding_frequency INT,
    complications TEXT,
    advice_given TEXT,
    next_visit_date DATE,
    supervised_by VARCHAR(255),
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES midwifery_students(id) ON DELETE CASCADE,
    INDEX idx_postnatal_id (postnatal_id),
    INDEX idx_student_id (student_id),
    INDEX idx_visit_date (visit_date)
);

-- Family Planning Records
CREATE TABLE IF NOT EXISTS midwifery_family_planning (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fp_id VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    client_name VARCHAR(255) NOT NULL,
    client_age INT,
    parity INT,
    method_selected ENUM('Pill', 'Injection', 'Implant', 'IUD', 'Sterilization', 'Natural', 'None') NOT NULL,
    previous_method ENUM('Pill', 'Injection', 'Implant', 'IUD', 'Sterilization', 'Natural', 'None'),
    counseling_done BOOLEAN DEFAULT TRUE,
    complications_history TEXT,
    follow_up_date DATE,
    supervised_by VARCHAR(255),
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES midwifery_students(id) ON DELETE CASCADE,
    INDEX idx_fp_id (fp_id),
    INDEX idx_student_id (student_id)
);

-- ============================================================
-- 3. PROCEDURES FOR MIDWYIFERY DEPARTMENT
-- ============================================================

DELIMITER //

CREATE PROCEDURE get_midwifery_students_by_intake(IN p_intake_set VARCHAR(20))
BEGIN
    SELECT 
        ms.student_number,
        ms.full_name,
        ms.program,
        ms.year_of_study,
        ms.semester,
        ms.status,
        ms.photo_path
    FROM midwifery_students ms
    WHERE ms.intake_set = p_intake_set
    ORDER BY ms.student_number;
END //

CREATE PROCEDURE record_antenatal_visit(
    IN p_student_id INT,
    IN p_patient_name VARCHAR(255),
    IN p_visit_date DATE,
    IN p_blood_pressure VARCHAR(20),
    IN p_fhr INT
)
BEGIN
    DECLARE v_record_id VARCHAR(50);
    SET v_record_id = CONCAT('AN', DATE_FORMAT(NOW(), '%Y%m%d'), LPAD(p_student_id, 4, '0'));
    
    INSERT INTO midwifery_antenatal_care (
        record_id, student_id, patient_name, antenatal_visit_date, blood_pressure, fetal_heart_rate
    ) VALUES (
        v_record_id, p_student_id, p_patient_name, p_visit_date, p_blood_pressure, p_fhr
    );
END //

CREATE PROCEDURE get_midwifery_students_search(IN p_search_term VARCHAR(255))
BEGIN
    SELECT 
        ms.id,
        ms.student_number,
        ms.full_name,
        ms.program,
        ms.intake_set,
        ms.status,
        ms.photo_path,
        COALESCE(ms.photo_uploaded, FALSE) as has_photo
    FROM midwifery_students ms
    WHERE ms.full_name LIKE CONCAT('%', p_search_term, '%')
       OR ms.student_number LIKE CONCAT('%', p_search_term, '%')
       OR ms.index_number LIKE CONCAT('%', p_search_term, '%')
    LIMIT 100;
END //

DELIMITER ;

COMMIT;
-- ============================================================
-- ISNM HR MANAGER DASHBOARD SQL
-- Complete Human Resources Management System
-- ============================================================

USE igangaschoolofl_staffs_db;

-- ============================================================
-- 1. HR MANAGER USER ACCOUNTS
-- ============================================================

INSERT IGNORE INTO staff (
    staff_id, 
    full_name, 
    email, 
    password, 
    phone, 
    position, 
    department, 
    role_id, 
    status, 
    hire_date,
    created_at
) VALUES
('HR001', 'HR Manager', 'hr@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$hr@isnmHashedPassword', '+256701000011', 'HR Manager', 'Human Resources',
 (SELECT id FROM staff_roles WHERE role_name = 'HR Manager' LIMIT 1), 'Active', CURDATE(), NOW()),
('HR002', 'HR Assistant', 'hr_assistant@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$hr_assistant@isnmHashedPassword', '+256701000028', 'HR Assistant', 'Human Resources',
 (SELECT id FROM staff_roles WHERE role_name = 'HR Manager' LIMIT 1), 'Active', CURDATE(), NOW());

-- ============================================================
-- 2. HR MANAGER TABLES (Using existing tables from 04_final_complete_staffs_database.sql)
-- Add search and integration views
-- ============================================================

-- Staff Search View
CREATE OR REPLACE VIEW hr_staff_search_view AS
SELECT 
    s.id,
    s.staff_id,
    s.full_name,
    s.email,
    s.phone,
    s.position,
    s.department,
    sr.role_name,
    s.status,
    s.hire_date,
    s.last_login,
    CASE 
        WHEN s.locked_until > NOW() THEN 'Locked'
        WHEN s.login_attempts >= 5 THEN 'Warning'
        ELSE 'Active'
    END as account_status
FROM staff s
LEFT JOIN staff_roles sr ON s.role_id = sr.id;

-- Staff Performance Summary View
CREATE OR REPLACE VIEW hr_performance_summary AS
SELECT 
    st.id as staff_id,
    st.full_name,
    st.position,
    st.department,
    sr.role_name,
    COALESCE(spf.performance_score, 0) as avg_performance_score,
    spf.rating as latest_rating,
    COALESCE(sl.total_leaves, 0) as total_leaves,
    COALESCE(sta.attendance_rate, 0) as attendance_rate,
    COALESCE(stt.training_count, 0) as training_completed
FROM staff st
LEFT JOIN staff_performance spf ON st.id = spf.staff_id
LEFT JOIN (
    SELECT staff_id, COUNT(*) as total_leaves FROM staff_leave_requests GROUP BY staff_id
) sl ON st.id = sl.staff_id
LEFT JOIN (
    SELECT staff_id, 
           SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) * 100.0 / COUNT(*) as attendance_rate 
    FROM staff_attendance GROUP BY staff_id
) sta ON st.id = sta.staff_id
LEFT JOIN (
    SELECT staff_id, COUNT(*) as training_count FROM staff_training WHERE status = 'Completed' GROUP BY staff_id
) stt ON st.id = stt.staff_id
LEFT JOIN staff_roles sr ON st.role_id = sr.id;

-- ============================================================
-- 3. PROCEDURES FOR HR MANAGER
-- ============================================================

DELIMITER //

-- Search staff by various criteria
CREATE PROCEDURE hr_search_staff(
    IN p_name VARCHAR(255),
    IN p_department VARCHAR(100),
    IN p_position VARCHAR(100),
    IN p_status VARCHAR(50)
)
BEGIN
    SELECT 
        s.id,
        s.staff_id,
        s.full_name,
        s.email,
        s.phone,
        s.position,
        s.department,
        sr.role_name,
        s.status,
        s.hire_date
    FROM staff s
    LEFT JOIN staff_roles sr ON s.role_id = sr.id
    WHERE (p_name IS NULL OR s.full_name LIKE CONCAT('%', p_name, '%'))
      AND (p_department IS NULL OR s.department = p_department)
      AND (p_position IS NULL OR s.position LIKE CONCAT('%', p_position, '%'))
      AND (p_status IS NULL OR s.status = p_status)
    ORDER BY s.full_name;
END //

-- Get staff profile with documents
CREATE PROCEDURE hr_get_staff_profile(IN p_staff_id INT)
BEGIN
    SELECT 
        s.*,
        sp.bio,
        sp.profile_picture,
        sp.qualifications,
        sp.experience,
        sp.skills,
        sp.education_background,
        sp.certifications,
        sd.document_type,
        sd.document_title,
        sd.file_path,
        sd.upload_date
    FROM staff s
    LEFT JOIN staff_profiles sp ON s.id = sp.staff_id
    LEFT JOIN staff_documents sd ON s.id = sd.staff_id
    WHERE s.id = p_staff_id;
END //

-- Update staff profile picture
CREATE PROCEDURE hr_update_profile_picture(
    IN p_staff_id INT,
    IN p_photo_path VARCHAR(500),
    IN p_updated_by INT
)
BEGIN
    INSERT INTO staff_profiles (staff_id, profile_picture) 
    VALUES (p_staff_id, p_photo_path)
    ON DUPLICATE KEY UPDATE 
        profile_picture = p_photo_path;
END //

DELIMITER ;

-- ============================================================
-- 4. HR REPORTING VIEWS
-- ============================================================

-- Staff by Department
CREATE OR REPLACE VIEW hr_staff_by_department AS
SELECT 
    department,
    COUNT(*) as total_staff,
    SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active_staff,
    SUM(CASE WHEN status IN ('Suspended', 'On Leave') THEN 1 ELSE 0 END) as inactive_staff,
    AVG(DATEDIFF(NOW(), hire_date) / 365) as avg_years_of_service
FROM staff
GROUP BY department
ORDER BY department;

-- Leave Summary
CREATE OR REPLACE VIEW hr_leave_summary AS
SELECT 
    leave_type,
    COUNT(*) as total_requests,
    SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
FROM staff_leave_requests
GROUP BY leave_type;

COMMIT;
-- ============================================================
-- ISNM LIBRARY MANAGER DASHBOARD SQL
-- Complete Library Management System
-- ============================================================

USE igangaschoolofl_staffs_db;

-- ============================================================
-- 1. LIBRARY MANAGER USER ACCOUNTS
-- ============================================================

INSERT IGNORE INTO staff (
    staff_id, 
    full_name, 
    email, 
    password, 
    phone, 
    position, 
    department, 
    role_id, 
    status, 
    hire_date,
    created_at
) VALUES
('LIB001', 'School Librarian', 'librarian@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$librarian@isnmHashedPassword', '+256701000013', 'School Librarian', 'Library Services',
 (SELECT id FROM staff_roles WHERE role_name = 'School Librarian' LIMIT 1), 'Active', CURDATE(), NOW()),
('LIB002', 'Assistant Librarian', 'assistant_librarian@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$assistant_librarian@isnmHashedPassword', '+256701000029', 'Assistant Librarian', 'Library Services',
 (SELECT id FROM staff_roles WHERE role_name = 'School Librarian' LIMIT 1), 'Active', CURDATE(), NOW());

-- ============================================================
-- 2. LIBRARY MANAGEMENT TABLES
-- ============================================================

-- Books and Resources Catalog
CREATE TABLE IF NOT EXISTS library_books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(255),
    author VARCHAR(255),
    editor VARCHAR(255),
    edition VARCHAR(50),
    isbn VARCHAR(20),
    issn VARCHAR(20),
    publisher VARCHAR(255),
    publication_year INT,
    publication_place VARCHAR(100),
    category VARCHAR(100),
    subcategory VARCHAR(100),
    call_number VARCHAR(50),
    total_copies INT DEFAULT 1,
    available_copies INT DEFAULT 1,
    shelf_location VARCHAR(100),
    condition_status ENUM('New', 'Good', 'Fair', 'Poor', 'Damaged') DEFAULT 'Good',
    price DECIMAL(10,2),
    currency VARCHAR(3) DEFAULT 'UGX',
    language VARCHAR(50) DEFAULT 'English',
    pages INT,
    description TEXT,
    keywords TEXT,
    cover_image VARCHAR(500),
    digital_copy_path VARCHAR(500),
    status ENUM('Available', 'Borrowed', 'Reserved', 'Lost', 'On Order', 'Archiv') DEFAULT 'Available',
    added_by INT,
    added_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (added_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_book_id (book_id),
    INDEX idx_title (title),
    INDEX idx_author (author),
    INDEX idx_category (category),
    INDEX idx_status (status)
);

-- Borrowing Records
CREATE TABLE IF NOT EXISTS library_borrowing (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id VARCHAR(50) UNIQUE NOT NULL,
    book_id INT NOT NULL,
    borrower_type ENUM('Student', 'Staff', 'External') NOT NULL,
    borrower_id INT,
    borrower_name VARCHAR(255),
    borrow_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE,
    return_status ENUM('Borrowed', 'Returned', 'Overdue', 'Lost') DEFAULT 'Borrowed',
    late_fee DECIMAL(10,2) DEFAULT 0,
    fine_paid BOOLEAN DEFAULT FALSE,
    processed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES library_books(id) ON DELETE CASCADE,
    FOREIGN KEY (processed_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_transaction_id (transaction_id),
    INDEX idx_book_id (book_id),
    INDEX idx_return_status (return_status),
    INDEX idx_due_date (due_date)
);

-- Library Members
CREATE TABLE IF NOT EXISTS library_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id VARCHAR(50) UNIQUE NOT NULL,
    member_type ENUM('Student', 'Staff', 'External') NOT NULL,
    student_id INT,
    staff_id INT,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(20),
    department VARCHAR(100),
    program VARCHAR(100),
    member_since DATE,
    membership_expiry DATE,
    max_books_allowed INT DEFAULT 3,
    current_books_borrowed INT DEFAULT 0,
    status ENUM('Active', 'Inactive', 'Suspended', 'Expired') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES igangaschoolofl_students_db.students(id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_member_id (member_id),
    INDEX idx_full_name (full_name)
);

-- Digital Resources
CREATE TABLE IF NOT EXISTS library_digital_resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resource_id VARCHAR(50) UNIQUE NOT NULL,
    title VARCHAR(255) NOT NULL,
    resource_type ENUM('Ebook', 'Journal', 'Video', 'Audio', 'Database', 'Article') NOT NULL,
    author_creator VARCHAR(255),
    publisher VARCHAR(255),
    publication_year INT,
    url VARCHAR(500),
    file_path VARCHAR(500),
    file_size_mb DECIMAL(10,2),
    access_level ENUM('Public', 'Members Only', 'Restricted') DEFAULT 'Members Only',
    description TEXT,
    subject_keywords TEXT,
    added_by INT,
    added_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (added_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_resource_id (resource_id),
    INDEX idx_title (title)
);

-- Library Fines and Fees
CREATE TABLE IF NOT EXISTS library_fines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fine_id VARCHAR(50) UNIQUE NOT NULL,
    transaction_id INT,
    member_id INT NOT NULL,
    fine_type ENUM('Overdue', 'Damage', 'Lost', 'Reservation') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'UGX',
    description TEXT,
    waived BOOLEAN DEFAULT FALSE,
    waived_by INT,
    waived_date TIMESTAMP NULL,
    paid BOOLEAN DEFAULT FALSE,
    payment_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (transaction_id) REFERENCES library_borrowing(id) ON DELETE SET NULL,
    FOREIGN KEY (member_id) REFERENCES library_members(id) ON DELETE CASCADE,
    INDEX idx_fine_id (fine_id),
    INDEX idx_member_id (member_id),
    INDEX idx_paid (paid)
);

-- ============================================================
-- 3. PROCEDURES FOR LIBRARY MANAGEMENT
-- ============================================================

DELIMITER //

-- Search books in library
CREATE PROCEDURE library_search_books(
    IN p_title VARCHAR(255),
    IN p_author VARCHAR(255),
    IN p_category VARCHAR(100),
    IN p_status VARCHAR(50)
)
BEGIN
    SELECT 
        lb.book_id,
        lb.title,
        lb.author,
        lb.publisher,
        lb.publication_year,
        lb.category,
        lb.total_copies,
        lb.available_copies,
        lb.shelf_location,
        lb.status
    FROM library_books lb
    WHERE (p_title IS NULL OR lb.title LIKE CONCAT('%', p_title, '%'))
      AND (p_author IS NULL OR lb.author LIKE CONCAT('%', p_author, '%'))
      AND (p_category IS NULL OR lb.category = p_category)
      AND (p_status IS NULL OR lb.status = p_status)
    ORDER BY lb.title;
END //

-- Borrow book
CREATE PROCEDURE library_borrow_book(
    IN p_book_id INT,
    IN p_member_id INT,
    IN p_processed_by INT
)
BEGIN
    DECLARE v_transaction_id VARCHAR(50);
    DECLARE v_due_date DATE;
    DECLARE v_current_copies INT;
    DECLARE v_available_copies INT;
    
    SET v_transaction_id = CONCAT('BRW', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'));
    SET v_due_date = DATE_ADD(CURDATE(), INTERVAL 14 DAY);
    
    -- Check available copies
    SELECT available_copies INTO v_available_copies 
    FROM library_books WHERE id = p_book_id;
    
    IF v_available_copies > 0 THEN
        INSERT INTO library_borrowing (
            transaction_id, book_id, borrower_id, borrower_name, 
            borrow_date, due_date, processed_by
        ) VALUES (
            v_transaction_id, p_book_id, p_member_id, 
            (SELECT full_name FROM library_members WHERE id = p_member_id),
            CURDATE(), v_due_date, p_processed_by
        );
        
        UPDATE library_books 
        SET available_copies = available_copies - 1
        WHERE id = p_book_id;
        
        UPDATE library_members 
        SET current_books_borrowed = current_books_borrowed + 1
        WHERE id = p_member_id;
    END IF;
END //

-- Return book
CREATE PROCEDURE library_return_book(
    IN p_transaction_id INT,
    IN p_processed_by INT
)
BEGIN
    UPDATE library_borrowing 
    SET return_date = CURDATE(),
        return_status = 'Returned'
    WHERE id = p_transaction_id;
    
    UPDATE library_books lb
    JOIN library_borrowing lbw ON lb.id = lbw.book_id
    SET lb.available_copies = lb.available_copies + 1
    WHERE lbw.id = p_transaction_id;
    
    UPDATE library_members lm
    JOIN library_borrowing lbw ON lm.id = lbw.borrower_id
    SET lm.current_books_borrowed = lm.current_books_borrowed - 1
    WHERE lbw.id = p_transaction_id;
END //

DELIMITER ;

COMMIT;
-- ============================================================
-- ISNM SECURITY DEPARTMENT DASHBOARD SQL
-- Complete Security Management System
-- ============================================================

USE igangaschoolofl_staffs_db;

-- ============================================================
-- 1. SECURITY USER ACCOUNTS
-- ============================================================

INSERT IGNORE INTO staff (
    staff_id, 
    full_name, 
    email, 
    password, 
    phone, 
    position, 
    department, 
    role_id, 
    status, 
    hire_date,
    created_at
) VALUES
('SEC001', 'Security Officer', 'security@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$security@isnmHashedPassword', '+256701000022', 'Security', 'Security Services',
 (SELECT id FROM staff_roles WHERE role_name = 'Security' LIMIT 1), 'Active', CURDATE(), NOW()),
('SEC002', 'Chief Security Officer', 'cso@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$cso@isnmHashedPassword', '+256701000030', 'Chief Security Officer', 'Security Services',
 (SELECT id FROM staff_roles WHERE role_name = 'Security' LIMIT 1), 'Active', CURDATE(), NOW());

-- ============================================================
-- 2. SECURITY MANAGEMENT TABLES
-- ============================================================

-- Security Patrols
CREATE TABLE IF NOT EXISTS security_patrols (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patrol_id VARCHAR(50) UNIQUE NOT NULL,
    patrol_date DATE NOT NULL,
    patrol_shift ENUM('Morning', 'Afternoon', 'Night') NOT NULL,
    patrol_area VARCHAR(255) NOT NULL,
    patrol_route TEXT,
    start_time TIME,
    end_time TIME,
    duration_minutes INT,
    team_leader INT,
    officers_involved TEXT,
    incidents_reported INT DEFAULT 0,
    status ENUM('Scheduled', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_leader) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_patrol_id (patrol_id),
    INDEX idx_patrol_date (patrol_date)
);

-- Security Incidents
CREATE TABLE IF NOT EXISTS security_incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id VARCHAR(50) UNIQUE NOT NULL,
    incident_date DATE NOT NULL,
    incident_time TIME,
    incident_type ENUM('Theft', 'Assault', 'Fire', 'Medical', 'Accident', 'Vandalism', 'Suspicious Activity', 'Other') NOT NULL,
    severity ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    location VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    parties_involved TEXT,
    injuries_reported BOOLEAN DEFAULT FALSE,
    injuries_description TEXT,
    reported_by INT,
    reporter_name VARCHAR(255),
    reported_via ENUM('Phone', 'Email', 'In Person', 'Radio', 'Online') DEFAULT 'In Person',
    response_team TEXT,
    action_taken TEXT,
    resolution_status ENUM('Reported', 'In Investigation', 'Resolved', 'Closed') DEFAULT 'Reported',
    resolved_by INT,
    resolution_date TIMESTAMP NULL,
    follow_up_required BOOLEAN DEFAULT FALSE,
    follow_up_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reported_by) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (resolved_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_incident_id (incident_id),
    INDEX idx_incident_date (incident_date),
    INDEX idx_severity (severity)
);

-- Access Control Logs
CREATE TABLE IF NOT EXISTS security_access_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    log_id VARCHAR(50) UNIQUE NOT NULL,
    access_point VARCHAR(255) NOT NULL,
    access_date DATE NOT NULL,
    access_time TIME,
    person_type ENUM('Staff', 'Student', 'Visitor', 'Vendor', 'Unknown') NOT NULL,
    person_id INT,
    person_name VARCHAR(255),
    access_direction ENUM('Entry', 'Exit') NOT NULL,
    access_method ENUM('ID Card', 'Biometric', 'PIN', 'Manual') DEFAULT 'ID Card',
    authorized BOOLEAN DEFAULT TRUE,
    denial_reason VARCHAR(255),
    captured_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_log_id (log_id),
    INDEX idx_access_date (access_date),
    INDEX idx_person_type (person_type)
);

-- Visitor Management
CREATE TABLE IF NOT EXISTS security_visitors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visitor_id VARCHAR(50) UNIQUE NOT NULL,
    visitor_name VARCHAR(255) NOT NULL,
    visitor_phone VARCHAR(20),
    visitor_email VARCHAR(100),
    visitor_company VARCHAR(255),
    visitor_nature ENUM('Official', 'Parent', 'Guardian', 'Service Provider', 'Delivery', 'Other') NOT NULL,
    purpose_of_visit TEXT,
    person_to_visit INT,
    person_to_visit_name VARCHAR(255),
    visit_date DATE NOT NULL,
    expected_arrival TIME,
    expected_departure TIME,
    actual_arrival TIMESTAMP NULL,
    actual_departure TIMESTAMP NULL,
    vehicle_number VARCHAR(50),
    items_carried TEXT,
    security_check_passed BOOLEAN DEFAULT FALSE,
    check_in_by INT,
    check_out_by INT,
    status ENUM('Scheduled', 'Checked In', 'On Campus', 'Checked Out', 'No Show') DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (person_to_visit) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (check_in_by) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (check_out_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_visitor_id (visitor_id),
    INDEX idx_visit_date (visit_date)
);

-- Security Equipment
CREATE TABLE IF NOT EXISTS security_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id VARCHAR(50) UNIQUE NOT NULL,
    equipment_name VARCHAR(255) NOT NULL,
    equipment_type ENUM('Camera', 'CCTV', 'Metal Detector', 'Scanner', 'Radio', 'Flashlight', 'Baton', 'Other') NOT NULL,
    serial_number VARCHAR(100),
    location_installed VARCHAR(255),
    installation_date DATE,
    last_maintenance_date DATE,
    next_maintenance_date DATE,
    condition_status ENUM('Excellent', 'Good', 'Fair', 'Poor', 'Broken') DEFAULT 'Good',
    status ENUM('Active', 'Inactive', 'Maintenance', 'Retired') DEFAULT 'Active',
    assigned_to INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_equipment_id (equipment_id),
    INDEX idx_status (status)
);

-- Emergency Contacts and Procedures
CREATE TABLE IF NOT EXISTS security_emergency_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contact_name VARCHAR(255) NOT NULL,
    contact_type ENUM('Police', 'Hospital', 'Fire', 'Ambulance', 'Internal') NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    secondary_phone VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    response_time_minutes INT,
    notes TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_contact_type (contact_type),
    INDEX idx_is_active (is_active)
);

-- ============================================================
-- 3. PROCEDURES FOR SECURITY DEPARTMENT
-- ============================================================

DELIMITER //

-- Report security incident
CREATE PROCEDURE security_report_incident(
    IN p_incident_type VARCHAR(50),
    IN p_location VARCHAR(255),
    IN p_description TEXT,
    IN p_reported_by INT,
    IN p_severity VARCHAR(20)
)
BEGIN
    DECLARE v_incident_id VARCHAR(50);
    SET v_incident_id = CONCAT('INC', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'));
    
    INSERT INTO security_incidents (
        incident_id, incident_type, location, description, reported_by, severity
    ) VALUES (
        v_incident_id, p_incident_type, p_location, p_description, p_reported_by, p_severity
    );
END //

-- Record visitor check-in
CREATE PROCEDURE security_visitor_checkin(
    IN p_visitor_id INT,
    IN p_checked_by INT
)
BEGIN
    UPDATE security_visitors 
    SET actual_arrival = NOW(),
        status = 'Checked In',
        check_in_by = p_checked_by
    WHERE id = p_visitor_id;
END //

-- Record visitor check-out
CREATE PROCEDURE security_visitor_checkout(
    IN p_visitor_id INT,
    IN p_checked_by INT
)
BEGIN
    UPDATE security_visitors 
    SET actual_departure = NOW(),
        status = 'Checked Out',
        check_out_by = p_checked_by
    WHERE id = p_visitor_id;
END //

DELIMITER ;

COMMIT;
-- ============================================================
-- ISNM SICKBAY DASHBOARD SQL
-- Complete Medical Support System
-- Formerly Lab Technicians department, now consolidated under Sickbay
-- ============================================================

USE igangaschoolofl_staffs_db;

-- ============================================================
-- 1. SICKBAY USER ACCOUNTS
-- ============================================================

INSERT IGNORE INTO staff (
    staff_id, 
    full_name, 
    email, 
    password, 
    phone, 
    position, 
    department, 
    role_id, 
    status, 
    hire_date,
    created_at
) VALUES
('SICK001', 'Sickbay', 'sickbay@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$kzTn6S3OUtKLmGoLNo9GOOHqIki7NwUxvZJ6pJK02Yls6eR7Bln82', '+256701000020', 'Sickbay', 'Support',
 (SELECT id FROM staff_roles WHERE role_name = 'Sickbay' LIMIT 1), 'Active', CURDATE(), NOW());

-- ============================================================
-- 2. MEDICAL SUPPORT MANAGEMENT TABLES
-- ============================================================

-- Laboratory Equipment
CREATE TABLE IF NOT EXISTS lab_equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id VARCHAR(50) UNIQUE NOT NULL,
    equipment_name VARCHAR(255) NOT NULL,
    equipment_type ENUM('Microscope', 'Centrifuge', 'Autoclave', 'Spectrophotometer', 'PCR', 'Incubator', 'Refrigerator', 'Freezer', 'Other') NOT NULL,
    manufacturer VARCHAR(255),
    serial_number VARCHAR(100),
    model VARCHAR(100),
    purchase_date DATE,
    warranty_expiry DATE,
    calibration_date DATE,
    next_calibration_date DATE,
    location VARCHAR(255),
    status ENUM('Operational', 'Maintenance', 'Repair', 'Retired') DEFAULT 'Operational',
    last_serviced_by VARCHAR(255),
    service_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_equipment_id (equipment_id),
    INDEX idx_status (status)
);

-- Laboratory Inventory
CREATE TABLE IF NOT EXISTS lab_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id VARCHAR(50) UNIQUE NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    item_category ENUM('Reagent', 'Chemical', 'Consumable', 'Glassware', 'Plasticware', 'Media', 'Antibody', 'Enzyme') NOT NULL,
    manufacturer VARCHAR(255),
    catalog_number VARCHAR(100),
    batch_number VARCHAR(100),
    unit_of_measure VARCHAR(50),
    quantity_on_hand DECIMAL(15,2) DEFAULT 0,
    reorder_level DECIMAL(15,2) DEFAULT 0,
    storage_location VARCHAR(255),
    expiry_date DATE,
    date_received DATE,
    received_by INT,
    status ENUM('In Stock', 'Low Stock', 'Out of Stock', 'Expired', 'Quarantine') DEFAULT 'In Stock',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (received_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_item_id (item_id),
    INDEX idx_item_name (item_name),
    INDEX idx_category (item_category),
    INDEX idx_expiry_date (expiry_date)
);

-- Lab Skills Sessions
CREATE TABLE IF NOT EXISTS lab_skills_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(50) UNIQUE NOT NULL,
    session_title VARCHAR(255) NOT NULL,
    skill_name VARCHAR(255) NOT NULL,
    session_date DATE NOT NULL,
    start_time TIME,
    end_time TIME,
    duration_minutes INT,
    target_department ENUM('Nursing', 'Midwifery', 'Both') DEFAULT 'Both',
    year_of_study INT,
    students_expected INT,
    students_attended INT,
    instructor_id INT,
    instructor_name VARCHAR(255),
    equipment_used TEXT,
    materials_used TEXT,
    pre_test_score DECIMAL(5,2),
    post_test_score DECIMAL(5,2),
    session_status ENUM('Scheduled', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    evaluation_notes TEXT,
    completed_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (instructor_id) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (completed_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_session_id (session_id),
    INDEX idx_session_date (session_date)
);

-- Lab Safety Records
CREATE TABLE IF NOT EXISTS lab_safety_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id VARCHAR(50) UNIQUE NOT NULL,
    incident_date DATE NOT NULL,
    incident_type ENUM('Accident', 'Near Miss', 'Spill', 'Exposure', 'Equipment Failure') NOT NULL,
    severity ENUM('Minor', 'Moderate', 'Severe', 'Critical') DEFAULT 'Minor',
    person_involved VARCHAR(255),
    person_type ENUM('Student', 'Staff', 'Visitor') NOT NULL,
    description TEXT NOT NULL,
    immediate_action TEXT,
    follow_up_action TEXT,
    reported_by INT,
    reported_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved BOOLEAN DEFAULT FALSE,
    resolution_date TIMESTAMP NULL,
    resolved_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reported_by) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (resolved_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_record_id (record_id),
    INDEX idx_incident_date (incident_date)
);

-- Chemical Inventory
CREATE TABLE IF NOT EXISTS lab_chemical_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chemical_id VARCHAR(50) UNIQUE NOT NULL,
    chemical_name VARCHAR(255) NOT NULL,
    cas_number VARCHAR(50),
    chemical_formula VARCHAR(100),
    hazard_classification VARCHAR(100),
    storage_requirements TEXT,
    quantity_on_hand DECIMAL(15,2),
    unit_of_measure VARCHAR(50),
    expiry_date DATE,
    date_received DATE,
    storage_location VARCHAR(255),
    supplier VARCHAR(255),
    msds_file VARCHAR(500),
    status ENUM('In Stock', 'Low Stock', 'Out of Stock', 'Expired') DEFAULT 'In Stock',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_chemical_id (chemical_id),
    INDEX idx_chemical_name (chemical_name),
    INDEX idx_expiry_date (expiry_date)
);

-- Lab Experiments Tracking
CREATE TABLE IF NOT EXISTS lab_experiments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    experiment_id VARCHAR(50) UNIQUE NOT NULL,
    experiment_name VARCHAR(255) NOT NULL,
    course_code VARCHAR(50),
    batch_number VARCHAR(50),
    session_id INT,
    experiment_date DATE NOT NULL,
    start_time TIME,
    end_time TIME,
    students_enrolled INT,
    students_completed INT,
    instructor_id INT,
    sickbay_staff_id INT,
    equipment_used TEXT,
    reagents_used TEXT,
    observations TEXT,
    results TEXT,
    status ENUM('Scheduled', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES lab_skills_sessions(id) ON DELETE SET NULL,
    FOREIGN KEY (instructor_id) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (sickbay_staff_id) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_experiment_id (experiment_id),
    INDEX idx_experiment_date (experiment_date)
);

-- ============================================================
-- 3. PROCEDURES FOR SICKBAY
-- ============================================================

DELIMITER //

-- Record lab equipment maintenance
CREATE PROCEDURE lab_record_maintenance(
    IN p_equipment_id INT,
    IN p_status VARCHAR(20),
    IN p_notes TEXT
)
BEGIN
    UPDATE lab_equipment 
    SET status = p_status,
        service_notes = CONCAT(COALESCE(service_notes, ''), '\n', p_notes),
        updated_at = NOW()
    WHERE id = p_equipment_id;
END //

-- Update inventory quantity
CREATE PROCEDURE lab_update_inventory(
    IN p_item_id INT,
    IN p_new_quantity DECIMAL(15,2)
)
BEGIN
    UPDATE lab_inventory 
    SET quantity_on_hand = p_new_quantity,
        status = CASE 
            WHEN p_new_quantity <= 0 THEN 'Out of Stock'
            WHEN p_new_quantity <= reorder_level THEN 'Low Stock'
            ELSE 'In Stock'
        END,
        updated_at = NOW()
    WHERE id = p_item_id;
END //

-- Schedule lab session
CREATE PROCEDURE lab_schedule_session(
    IN p_title VARCHAR(255),
    IN p_skill_name VARCHAR(255),
    IN p_session_date DATE,
    IN p_instructor_id INT
)
BEGIN
    DECLARE v_session_id VARCHAR(50);
    SET v_session_id = CONCAT('LSS', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'));
    
    INSERT INTO lab_skills_sessions (
        session_id, session_title, skill_name, session_date, instructor_id
    ) VALUES (
        v_session_id, p_title, p_skill_name, p_session_date, p_instructor_id
    );
END //

DELIMITER ;

COMMIT;
-- ============================================================
-- ISNM MATRONS & WARDENS DASHBOARD SQL
-- Complete Student Welfare Management System
-- ============================================================

USE igangaschoolofl_staffs_db;

-- ============================================================
-- 1. MATRONS & WARDENS USER ACCOUNTS
-- ============================================================

INSERT IGNORE INTO staff (
    staff_id, 
    full_name, 
    email, 
    password, 
    phone, 
    position, 
    department, 
    role_id, 
    status, 
    hire_date,
    created_at
) VALUES
('MAT001', 'Matron', 'matrons@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$matrons@isnmHashedPassword', '+256701000018', 'Matrons', 'Student Affairs',
 (SELECT id FROM staff_roles WHERE role_name = 'Matrons' LIMIT 1), 'Active', CURDATE(), NOW()),
('WAR001', 'Warden', 'wardens@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$wardens@isnmHashedPassword', '+256701000019', 'Wardens', 'Student Affairs',
 (SELECT id FROM staff_roles WHERE role_name = 'Wardens' LIMIT 1), 'Active', CURDATE(), NOW());

-- ============================================================
-- 2. STUDENT WELFARE MANAGEMENT TABLES
-- ============================================================

-- Student Welfare Cases
CREATE TABLE IF NOT EXISTS student_welfare_cases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    case_id VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    case_type ENUM('Financial', 'Health', 'Family', 'Academic', 'Discipline', 'Personal', 'Emergency') NOT NULL,
    case_priority ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    case_description TEXT NOT NULL,
    reported_by INT,
    reporter_name VARCHAR(255),
    reported_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_to INT,
    assigned_date TIMESTAMP NULL,
    action_taken TEXT,
    outcome TEXT,
    follow_up_required BOOLEAN DEFAULT FALSE,
    follow_up_date DATE,
    follow_up_completed BOOLEAN DEFAULT FALSE,
    closed BOOLEAN DEFAULT FALSE,
    closed_by INT,
    closure_date TIMESTAMP NULL,
    status ENUM('Reported', 'Assigned', 'In Progress', 'Follow Up', 'Closed', 'Escalated') DEFAULT 'Reported',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES igangaschoolofl_students_db.students(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_by) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (closed_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_case_id (case_id),
    INDEX idx_student_id (student_id),
    INDEX idx_status (status)
);

-- Student Health Records
CREATE TABLE IF NOT EXISTS student_health_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    health_facility VARCHAR(255),
    visit_date DATE NOT NULL,
    visit_time TIME,
    health_worker VARCHAR(255),
    complaint TEXT,
    diagnosis TEXT,
    treatment_given TEXT,
    medication_prescribed TEXT,
    medication_dispensed BOOLEAN DEFAULT FALSE,
    follow_up_date DATE,
    follow_up_completed BOOLEAN DEFAULT FALSE,
    referred BOOLEAN DEFAULT FALSE,
    referral_facility VARCHAR(255),
    recorded_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES igangaschoolofl_students_db.students(id) ON DELETE CASCADE,
    FOREIGN KEY (recorded_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_record_id (record_id),
    INDEX idx_student_id (student_id),
    INDEX idx_visit_date (visit_date)
);

-- Health Incidents
CREATE TABLE IF NOT EXISTS student_health_incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    incident_id VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    incident_date DATE NOT NULL,
    incident_time TIME,
    incident_type ENUM('Injury', 'Illness', 'Allergic Reaction', 'Mental Health', 'Emergency', 'Other') NOT NULL,
    location VARCHAR(255),
    description TEXT NOT NULL,
    severity ENUM('Minor', 'Moderate', 'Severe', 'Critical') NOT NULL,
    first_aid_provided BOOLEAN DEFAULT FALSE,
    first_aid_description TEXT,
    hospitalized BOOLEAN DEFAULT FALSE,
    hospital_name VARCHAR(255),
    attended_by VARCHAR(255),
    parent_notified BOOLEAN DEFAULT FALSE,
    parent_phone VARCHAR(20),
    reported_by INT,
    reported_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved BOOLEAN DEFAULT FALSE,
    resolution_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES igangaschoolofl_students_db.students(id) ON DELETE CASCADE,
    FOREIGN KEY (reported_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_incident_id (incident_id),
    INDEX idx_student_id (student_id),
    INDEX idx_incident_date (incident_date)
);

-- Counseling Sessions
CREATE TABLE IF NOT EXISTS student_counseling_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    counselor_id INT,
    counselor_name VARCHAR(255),
    session_date DATE NOT NULL,
    session_time TIME,
    session_duration_minutes INT,
    session_type ENUM('Individual', 'Group', 'Family', 'Crisis') DEFAULT 'Individual',
    issues_discussed TEXT,
    advice_given TEXT,
    referrals_made TEXT,
    follow_up_required BOOLEAN DEFAULT TRUE,
    follow_up_date DATE,
    session_outcome TEXT,
    student_feedback TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES igangaschoolofl_students_db.students(id) ON DELETE CASCADE,
    FOREIGN KEY (counselor_id) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_session_id (session_id),
    INDEX idx_student_id (student_id),
    INDEX idx_session_date (session_date)
);

-- Room Inspections
CREATE TABLE IF NOT EXISTS student_room_inspections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inspection_id VARCHAR(50) UNIQUE NOT NULL,
    room_id INT,
    room_number VARCHAR(50) NOT NULL,
    inspection_date DATE NOT NULL,
    inspected_by INT,
    inspector_name VARCHAR(255),
    cleanliness_score INT,
    maintenance_issues TEXT,
    disciplinary_issues TEXT,
    items_confiscated TEXT,
    action_taken TEXT,
    follow_up_required BOOLEAN DEFAULT FALSE,
    follow_up_date DATE,
    next_inspection_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inspected_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_inspection_id (inspection_id),
    INDEX idx_room_number (room_number),
    INDEX idx_inspection_date (inspection_date)
);

-- Emergency Contacts
CREATE TABLE IF NOT EXISTS student_emergency_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contact_id VARCHAR(50) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    contact_name VARCHAR(255) NOT NULL,
    contact_relationship VARCHAR(100),
    contact_phone VARCHAR(20) NOT NULL,
    contact_email VARCHAR(100),
    contact_address TEXT,
    is_primary BOOLEAN DEFAULT FALSE,
    notified BOOLEAN DEFAULT FALSE,
    last_notified TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES igangaschoolofl_students_db.students(id) ON DELETE CASCADE,
    INDEX idx_contact_id (contact_id),
    INDEX idx_student_id (student_id)
);

-- ============================================================
-- 3. PROCEDURES FOR MATRONS & WARDENS
-- ============================================================

DELIMITER //

-- Record student welfare case
CREATE PROCEDURE welfare_record_case(
    IN p_student_id INT,
    IN p_case_type VARCHAR(50),
    IN p_description TEXT,
    IN p_priority VARCHAR(20),
    IN p_reported_by INT
)
BEGIN
    DECLARE v_case_id VARCHAR(50);
    SET v_case_id = CONCAT('WEL', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'));
    
    INSERT INTO student_welfare_cases (
        case_id, student_id, case_type, case_description, case_priority, reported_by
    ) VALUES (
        v_case_id, p_student_id, p_case_type, p_description, p_priority, p_reported_by
    );
END //

-- Record health incident
CREATE PROCEDURE welfare_record_health_incident(
    IN p_student_id INT,
    IN p_incident_type VARCHAR(50),
    IN p_description TEXT,
    IN p_severity VARCHAR(20),
    IN p_reported_by INT
)
BEGIN
    DECLARE v_incident_id VARCHAR(50);
    SET v_incident_id = CONCAT('HLTH', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'));
    
    INSERT INTO student_health_incidents (
        incident_id, student_id, incident_type, description, severity, reported_by
    ) VALUES (
        v_incident_id, p_student_id, p_incident_type, p_description, p_severity, p_reported_by
    );
END //

-- Schedule counseling session
CREATE PROCEDURE welfare_schedule_counseling(
    IN p_student_id INT,
    IN p_counselor_id INT,
    IN p_session_date DATE,
    IN p_session_type VARCHAR(50)
)
BEGIN
    DECLARE v_session_id VARCHAR(50);
    SET v_session_id = CONCAT('COUN', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'));
    
    INSERT INTO student_counseling_sessions (
        session_id, student_id, counselor_id, session_date, session_type
    ) VALUES (
        v_session_id, p_student_id, p_counselor_id, p_session_date, p_session_type
    );
END //

DELIMITER ;

COMMIT;
-- ============================================================
-- ISNM DIRECTOR ACADEMICS DASHBOARD SQL
-- Complete Academic Leadership Management System
-- ============================================================

USE igangaschoolofl_staffs_db;

-- ============================================================
-- 1. DIRECTOR ACADEMICS USER ACCOUNTS
-- ============================================================

INSERT IGNORE INTO staff (
    staff_id, 
    full_name, 
    email, 
    password, 
    phone, 
    position, 
    department, 
    role_id, 
    status, 
    hire_date,
    created_at
) VALUES
('DA001', 'Director Academics', 'director_academics@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$director_academics@isnmHashedPassword', '+256701000003', 'Director Academics', 'Academic Affairs',
 (SELECT id FROM staff_roles WHERE role_name = 'Director Academics' LIMIT 1), 'Active', CURDATE(), NOW());

-- ============================================================
-- 2. ACADEMIC DIRECTOR TABLES
-- ============================================================

-- Program Management
CREATE TABLE IF NOT EXISTS academic_programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_code VARCHAR(20) UNIQUE NOT NULL,
    program_name VARCHAR(255) NOT NULL,
    program_type ENUM('Certificate', 'Diploma', 'Degree') NOT NULL,
    department VARCHAR(100) NOT NULL,
    duration_years INT DEFAULT 2,
    total_credits INT,
    program_coordinator INT,
    accreditation_status ENUM('Accredited', 'Provisional', 'Expired', 'Pending') DEFAULT 'Accredited',
    accreditation_body VARCHAR(255),
    accreditation_expiry DATE,
    status ENUM('Active', 'Inactive', 'Suspended') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (program_coordinator) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_program_code (program_code),
    INDEX idx_status (status)
);

-- Course Catalog
CREATE TABLE IF NOT EXISTS academic_course_catalog (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_code VARCHAR(20) UNIQUE NOT NULL,
    course_title VARCHAR(255) NOT NULL,
    credits INT NOT NULL,
    program_code VARCHAR(20),
    year_of_study INT,
    semester VARCHAR(50),
    theory_hours INT,
    practical_hours INT,
    tutorials_hours INT,
    assessment_method TEXT,
    course_coordinator INT,
    prerequisites TEXT,
    learning_outcomes TEXT,
    status ENUM('Active', 'Inactive', 'Under Review') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_coordinator) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_course_code (course_code),
    INDEX idx_program_code (program_code)
);

-- Academic Analytics
CREATE TABLE IF NOT EXISTS academic_analytics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    analytics_id VARCHAR(50) UNIQUE NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(50),
    program_code VARCHAR(20),
    total_enrolled INT,
    total_graduated INT,
    total_dropped INT,
    average_gpa DECIMAL(3,2),
    pass_rate DECIMAL(5,2),
    withdrawal_rate DECIMAL(5,2),
    employment_rate DECIMAL(5,2),
    analysis_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    generated_by INT,
    FOREIGN KEY (generated_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_academic_year (academic_year),
    INDEX idx_program_code (program_code)
);

-- Curriculum Development
CREATE TABLE IF NOT EXISTS academic_curriculum_development (
    id INT AUTO_INCREMENT PRIMARY KEY,
    curriculum_id VARCHAR(50) UNIQUE NOT NULL,
    program_code VARCHAR(20) NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    revision_number INT DEFAULT 1,
    changes_made TEXT,
    reason_for_changes TEXT,
    approved_by INT,
    approval_date TIMESTAMP NULL,
    status ENUM('Draft', 'Under Review', 'Approved', 'Implemented') DEFAULT 'Draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (approved_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_curriculum_id (curriculum_id)
);

-- Academic Reports
CREATE TABLE IF NOT EXISTS academic_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id VARCHAR(50) UNIQUE NOT NULL,
    report_type ENUM('Enrollment', 'Graduation', 'Performance', 'Employment', 'Accreditation', 'Compliance') NOT NULL,
    report_period VARCHAR(50),
    program_code VARCHAR(20),
    report_data LONGTEXT,
    generated_by INT,
    generated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    pdf_path VARCHAR(500),
    status ENUM('Draft', 'Final', 'Archived') DEFAULT 'Draft',
    FOREIGN KEY (generated_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_report_type (report_type),
    INDEX idx_generated_date (generated_date)
);

-- Timetable Management
CREATE TABLE IF NOT EXISTS academic_timetable (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timetable_id VARCHAR(50) UNIQUE NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    semester VARCHAR(50) NOT NULL,
    program_code VARCHAR(20),
    course_code VARCHAR(20),
    day_of_week ENUM('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
    start_time TIME,
    end_time TIME,
    venue VARCHAR(255),
    lecturer_id INT,
    timetable_status ENUM('Draft', 'Approved', 'Published', 'Cancelled') DEFAULT 'Draft',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lecturer_id) REFERENCES staff(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES staff(id) ON DELETE SET NULL,
    INDEX idx_timetable_id (timetable_id),
    INDEX idx_program_code (program_code)
);

-- ============================================================
-- 3. PROCEDURES FOR DIRECTOR ACADEMICS
-- ============================================================

DELIMITER //

-- Generate enrollment report
CREATE PROCEDURE academic_generate_enrollment_report(
    IN p_academic_year VARCHAR(20),
    IN p_program_code VARCHAR(20)
)
BEGIN
    SELECT 
        program,
        COUNT(*) as total_students,
        COUNT(CASE WHEN status = 'Active' THEN 1 END) as active_students,
        COUNT(CASE WHEN status = 'Graduated' THEN 1 END) as graduated_students,
        COUNT(CASE WHEN status = 'Suspended' THEN 1 END) as suspended_students
    FROM universal_student_profiles
    WHERE (p_academic_year IS NULL OR intake_set LIKE CONCAT('%', p_academic_year, '%'))
      AND (p_program_code IS NULL OR program = p_program_code)
    GROUP BY program;
END //

-- Update program coordinator
CREATE PROCEDURE academic_update_program_coordinator(
    IN p_program_code VARCHAR(20),
    IN p_coordinator_id INT
)
BEGIN
    UPDATE academic_programs 
    SET program_coordinator = p_coordinator_id,
        updated_at = NOW()
    WHERE program_code = p_program_code;
END //

DELIMITER ;

COMMIT;
-- ============================================================
-- ISNM DIRECTOR FINANCE DASHBOARD SQL
-- Complete Financial Management System
-- ============================================================
--
-- PREREQUISITES:
--   1. sql/staffs/04_final_complete_staffs_database.sql
--   2. sql/students/01_create_students_database.sql
--   3. sql/students/bursar_system.sql
--
-- All student data resides in igangaschoolofl_students_db.
-- Financial tables (fee_accounts, budget_records) are in
-- igangaschoolofl_staffs_db. Cross-database views are
-- created in 05_all_departments_complete_dashboards.sql.

USE igangaschoolofl_staffs_db;

-- ============================================================
-- 1. DIRECTOR FINANCE USER ACCOUNTS
-- ============================================================

INSERT IGNORE INTO staff (
    staff_id, 
    full_name, 
    email, 
    password, 
    phone, 
    position, 
    department, 
    role_id, 
    status, 
    hire_date,
    created_at
) VALUES
('DF001', 'Director Finance', 'director_finance@igangaschoolofnursingandmidwifery.ac.ug',
 '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', '+256701000005', 'Director Finance', 'Finance Department',
 (SELECT id FROM staff_roles WHERE role_name = 'Director Finance' LIMIT 1), 'Active', CURDATE(), NOW());

-- ============================================================
-- 2. FINANCE MANAGEMENT VIEWS
-- ============================================================

-- Finance Dashboard Summary View
-- Uses students table from students_db for active student count,
-- and billing tables from both databases for financial summaries.
-- Note: student_invoices => student_fee_assignments + payments in students_db
CREATE OR REPLACE VIEW finance_dashboard_summary AS
SELECT 
    -- Student Fee Summary (students are in the students database)
    (SELECT COUNT(*) FROM igangaschoolofl_students_db.students WHERE status = 'Active') as total_active_students,
    
    -- Invoice Summary (student_fee_assignments acts as invoice records)
    (SELECT COUNT(*) FROM igangaschoolofl_students_db.student_fee_assignments WHERE status IN ('Unpaid', 'Partially Paid', 'Overdue')) as pending_invoices,
    (SELECT COUNT(*) FROM igangaschoolofl_students_db.student_fee_assignments WHERE status = 'Paid') as paid_invoices,
    (SELECT SUM(assigned_amount) FROM igangaschoolofl_students_db.student_fee_assignments WHERE status IN ('Unpaid', 'Partially Paid', 'Overdue')) as pending_amount,
    (SELECT SUM(paid_amount) FROM igangaschoolofl_students_db.student_fee_assignments WHERE status = 'Paid') as collected_amount,
    
    -- Payment Summary
    (SELECT COUNT(*) FROM igangaschoolofl_students_db.payments WHERE status = 'Completed') as total_payments,
    (SELECT SUM(amount_received) FROM igangaschoolofl_students_db.payments WHERE status = 'Completed') as total_revenue,
    
    -- Budget Summary
    (SELECT COUNT(*) FROM igangaschoolofl_staffs_db.budget_records WHERE status = 'Active') as active_budgets,
    (SELECT SUM(allocated_amount) FROM igangaschoolofl_staffs_db.budget_records WHERE status = 'Active') as total_budget_allocated,
    (SELECT SUM(spent_amount) FROM igangaschoolofl_staffs_db.budget_records WHERE status = 'Active') as total_budget_spent,
    
    -- Sponsorship Summary
    (SELECT COUNT(*) FROM igangaschoolofl_students_db.sponsorships WHERE status = 'Active') as active_scholarships,
    (SELECT SUM(amount) FROM igangaschoolofl_students_db.sponsorships WHERE status = 'Active') as total_scholarship_value;

-- Student Fee Balance View
CREATE OR REPLACE VIEW finance_student_balances AS
SELECT 
    s.student_number,
    s.full_name,
    s.program,
    COALESCE(fs.fee_name, 'General Fee') as fee_type,
    sfa.assigned_amount as fee_balance,
    COALESCE(SUM(p.amount_received), 0) as amount_paid,
    (sfa.assigned_amount - COALESCE(SUM(p.amount_received), 0)) as outstanding_balance,
    sfa.status as fee_status
FROM igangaschoolofl_students_db.students s
JOIN igangaschoolofl_students_db.student_fee_assignments sfa ON s.id = sfa.student_id
LEFT JOIN igangaschoolofl_students_db.fee_structures fs ON sfa.fee_structure_id = fs.id
LEFT JOIN igangaschoolofl_students_db.payments p ON s.id = p.student_id AND p.status = 'Completed'
WHERE sfa.status IN ('Unpaid', 'Partially Paid', 'Overdue')
GROUP BY s.id, s.student_number, s.full_name, s.program, fs.fee_name, sfa.assigned_amount, sfa.status;

-- Revenue by Program View
CREATE OR REPLACE VIEW finance_revenue_by_program AS
SELECT 
    s.program,
    COUNT(DISTINCT s.id) as total_students,
    COUNT(DISTINCT sfa.id) as students_with_fees,
    SUM(sfa.assigned_amount) as total_assessed,
    COALESCE(SUM(CASE WHEN p.status = 'Completed' THEN p.amount_received ELSE 0 END), 0) as total_collected,
    (SUM(sfa.assigned_amount) - COALESCE(SUM(CASE WHEN p.status = 'Completed' THEN p.amount_received ELSE 0 END), 0)) as total_outstanding
FROM igangaschoolofl_students_db.students s
JOIN igangaschoolofl_students_db.student_fee_assignments sfa ON s.id = sfa.student_id
LEFT JOIN igangaschoolofl_students_db.payments p ON s.id = p.student_id
GROUP BY s.program;

-- ============================================================
-- 3. PROCEDURES FOR DIRECTOR FINANCE
-- ============================================================

DELIMITER //

-- Generate student fee statement
CREATE PROCEDURE finance_generate_statement(IN p_student_id INT)
BEGIN
    SELECT 
        s.student_number,
        s.full_name,
        s.program,
        COALESCE(fs.fee_name, 'General Fee') as fee_type,
        sfa.assigned_amount as assessed_amount,
        sfa.due_date,
        sfa.paid_amount,
        sfa.balance,
        sfa.status
    FROM igangaschoolofl_students_db.students s
    JOIN igangaschoolofl_students_db.student_fee_assignments sfa ON s.id = sfa.student_id
    LEFT JOIN igangaschoolofl_students_db.fee_structures fs ON sfa.fee_structure_id = fs.id
    WHERE s.id = p_student_id
    ORDER BY sfa.due_date;
END //

-- Record payment
CREATE PROCEDURE finance_record_payment(
    IN p_student_id INT,
    IN p_amount DECIMAL(15,2),
    IN p_payment_method VARCHAR(50),
    IN p_reference VARCHAR(100),
    IN p_processed_by INT
)
BEGIN
    DECLARE v_payment_reference VARCHAR(50);
    SET v_payment_reference = CONCAT('PAY', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'));
    
    INSERT INTO igangaschoolofl_students_db.payments (
        payment_reference, student_id, amount_received, payment_method, status
    ) VALUES (
        v_payment_reference, p_student_id, p_amount, p_payment_method, 'Completed'
    );
    
    UPDATE igangaschoolofl_students_db.student_fee_assignments 
    SET paid_amount = paid_amount + p_amount,
        status = CASE 
            WHEN (assigned_amount - (paid_amount + p_amount)) <= 0 THEN 'Paid'
            ELSE 'Partially Paid'
        END
    WHERE student_id = p_student_id AND status IN ('Unpaid', 'Partially Paid');
END //

-- Get overdue accounts
CREATE PROCEDURE finance_get_overdue_accounts()
BEGIN
    SELECT 
        s.student_number,
        s.full_name,
        s.program,
        sfa.fee_type,
        sfa.assigned_amount as amount,
        sfa.due_date,
        sfa.balance,
        DATEDIFF(CURDATE(), sfa.due_date) as days_overdue
    FROM igangaschoolofl_students_db.students s
    JOIN igangaschoolofl_students_db.student_fee_assignments sfa ON s.id = sfa.student_id
    WHERE sfa.status = 'Overdue' AND sfa.due_date < CURDATE()
    ORDER BY days_overdue DESC;
END //

DELIMITER ;

COMMIT;
-- ============================================================
-- STUDENT MANAGEMENT PROCEDURES AND PERMISSIONS
-- Allows Secretary and Director ICT to add/manage students
-- Database: igangaschoolofl_staffs_db
-- ============================================================

USE `igangaschoolofl_staffs_db`;

-- ============================================================
-- 1. ENSURE STAFF HAVE PERMISSIONS TO MANAGE STUDENTS
-- ============================================================

-- Safeguard: Ensure core tables exist before update/insert
CREATE TABLE IF NOT EXISTS staff_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(100) NOT NULL UNIQUE,
    role_description TEXT,
    role_level ENUM('Executive', 'Management', 'Academic', 'Support', 'Administrative') DEFAULT 'Academic',
    dashboard_path VARCHAR(255),
    permissions JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role_name (role_name),
    INDEX idx_role_level (role_level)
);

-- Safeguard: Ensure staff table exists before JOIN/INSERT
CREATE TABLE IF NOT EXISTS staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id VARCHAR(50) UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    position VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    role_id INT,
    status ENUM('Active', 'Inactive', 'On Leave', 'Suspended') DEFAULT 'Active',
    hire_date DATE,
    password_changed BOOLEAN DEFAULT FALSE,
    is_first_login BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES staff_roles(id) ON DELETE SET NULL
);

-- Safeguard for dashboard access table
CREATE TABLE IF NOT EXISTS staff_dashboard_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    dashboard_path VARCHAR(255) NOT NULL,
    access_level ENUM('Full', 'Read Only', 'Limited') DEFAULT 'Full',
    granted_by INT NULL,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_staff_id (staff_id),
    INDEX idx_dashboard_path (dashboard_path)
);

-- Update Secretary role permissions to include student management
UPDATE staff_roles 
SET permissions = '{"administrative": true, "documentation": true, "can_manage_documents": true, "can_add_students": true, "can_manage_students": true, "can_view_students": true}' 
WHERE role_name = 'School Secretary';

-- Update Director ICT role permissions to include student management
UPDATE staff_roles 
SET permissions = '{"ict": true, "systems": true, "infrastructure": true, "can_manage_system": true, "can_add_students": true, "can_manage_students": true, "can_view_all_students": true, "can_view_all_departments": true, "can_edit_student_data": true}' 
WHERE role_name = 'Director ICT';

-- Grant dashboard access to Student Management for Secretary
INSERT IGNORE INTO staff_dashboard_access (staff_id, dashboard_path, access_level, granted_by)
SELECT s.id, 'dashboards/student-management.php', 'Full', 1
FROM staff s
JOIN staff_roles sr ON s.role_id = sr.id
WHERE sr.role_name = 'School Secretary' AND s.id IS NOT NULL;

-- Grant dashboard access to Student Management for Director ICT
INSERT IGNORE INTO staff_dashboard_access (staff_id, dashboard_path, access_level, granted_by)
SELECT s.id, 'dashboards/student-management.php', 'Full', 1
FROM staff s
JOIN staff_roles sr ON s.role_id = sr.id
WHERE sr.role_name = 'Director ICT' AND s.id IS NOT NULL;

-- Grant dashboard access to Student Management for Academic Registrar (already has this access)
INSERT IGNORE INTO staff_dashboard_access (staff_id, dashboard_path, access_level, granted_by)
SELECT s.id, 'dashboards/student-management.php', 'Full', 1
FROM staff s
JOIN staff_roles sr ON s.role_id = sr.id
WHERE sr.role_name = 'Academic Registrar';

-- ============================================================
-- 1.1 UPDATE OFFICIAL STAFF CREDENTIALS
-- ============================================================

-- First, ensure roles exist (abbreviated for brevity, assuming existing role structure)
-- Then update or insert the specific staff accounts requested:

INSERT IGNORE INTO staff (staff_id, full_name, email, password, position, department, role_id, status) VALUES
('DG001', 'Director General', 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Director General', 'Executive Office', (SELECT id FROM staff_roles WHERE role_name = 'Director General' LIMIT 1), 'Active'),
('CEO001', 'CEO', 'ceo@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$Ha21Vlb7p046OaklPLFCteb8raqKNilEWDlzq8ypXVz491hHIICXS', 'Chief Executive Officer', 'Executive Office', (SELECT id FROM staff_roles WHERE role_name = 'CEO' LIMIT 1), 'Active'),
('DA001', 'Director Academics', 'directoracademic@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Director Academics', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Director Academics' LIMIT 1), 'Active'),
('DF001', 'Director Finance', 'finance@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Director Finance', 'Finance Department', (SELECT id FROM staff_roles WHERE role_name = 'Director Finance' LIMIT 1), 'Active'),
('PRINC001', 'School Principal', 'principal@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$VVoHfONmCz.Bsvn1.t1UoesLbM01KNPXKT/b/VJIzxeUq0M9LabK.', 'School Principal', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'School Principal' LIMIT 1), 'Active'),
('DEPUT001', 'Deputy Principal', 'dep-principal@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$ANzSCNiGrURlS1ovFbQUKuK6ldOOBpiC0iW/MB7HVw/I5JC9wud.m', 'Deputy Principal', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Deputy Principal' LIMIT 1), 'Active'),
('REG001', 'Academic Registrar', 'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$Ha21Vlb7p046OaklPLFCteb8raqKNilEWDlzq8ypXVz491hHIICXS', 'Academic Registrar', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Academic Registrar' LIMIT 1), 'Active'),
('HR001', 'HR Manager', 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$jEb8/OsV.9cydSvrBrZ1Hejase4BaTkPXT3FO/Gf9EazTrbXprKYi', 'HR Manager', 'Human Resources', (SELECT id FROM staff_roles WHERE role_name = 'HR Manager' LIMIT 1), 'Active'),
('SEC001', 'School Secretary', 'secretary@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$Ha21Vlb7p046OaklPLFCteb8raqKNilEWDlzq8ypXVz491hHIICXS', 'School Secretary', 'Administrative Support', (SELECT id FROM staff_roles WHERE role_name = 'School Secretary' LIMIT 1), 'Active'),
('LIB001', 'School Librarian', 'library@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$GGfcvNfejW3f2fRptIUQIuK4c/W44n94twWtTAaOTqTVSuLZ52DsC', 'School Librarian', 'Library Services', (SELECT id FROM staff_roles WHERE role_name = 'School Librarian' LIMIT 1), 'Active'),
('NUR001', 'Head Nursing', 'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$YO8OuL81gpaFdgP4nJEebeXNhLeM1.hFMD5KidDV9YDGkJMdAqbgW', 'Head Nursing', 'Nursing Department', (SELECT id FROM staff_roles WHERE role_name = 'Head Nursing' LIMIT 1), 'Active'),
('MID001', 'Head Midwifery', 'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$G7pMLdi2UjjmhEd8Lx0bmeaM7tGD4jrfvMsZh6HvY1Po8YqFRubRu', 'Head Midwifery', 'Midwifery Department', (SELECT id FROM staff_roles WHERE role_name = 'Head Midwifery' LIMIT 1), 'Active'),
('SL001', 'Senior Lecturers', 'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$VVoHfONmCz.Bsvn1.t1UoesLbM01KNPXKT/b/VJIzxeUq0M9LabK.', 'Senior Lecturers', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Senior Lecturers' LIMIT 1), 'Active'),
('LEC001', 'Lecturers', 'lecturers@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$e52TV/DaoNDl4kjssi3Te.YHnpxHlaxatBX2wNg5yv3JkoYEEYV9i', 'Lecturers', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Lecturers' LIMIT 1), 'Active'),
('MAT001', 'Matrons', 'matron@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$ANzSCNiGrURlS1ovFbQUKuK6ldOOBpiC0iW/MB7HVw/I5JC9wud.m', 'Matrons', 'Student Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Matrons' LIMIT 1), 'Active'),
('WAR001', 'Wardens', 'warden@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$Ha21Vlb7p046OaklPLFCteb8raqKNilEWDlzq8ypXVz491hHIICXS', 'Wardens', 'Student Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Wardens' LIMIT 1), 'Active'),
('SICK001', 'Sickbay', 'sickbay@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$VVoHfONmCz.Bsvn1.t1UoesLbM01KNPXKT/b/VJIzxeUq0M9LabK.', 'Sickbay', 'Support', (SELECT id FROM staff_roles WHERE role_name = 'Sickbay' LIMIT 1), 'Active'),
('DRV001', 'Drivers', 'drivers@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$YO8OuL81gpaFdgP4nJEebeXNhLeM1.hFMD5KidDV9YDGkJMdAqbgW', 'Drivers', 'Support', (SELECT id FROM staff_roles WHERE role_name = 'Drivers' LIMIT 1), 'Active'),
('SECUR001', 'Security', 'security@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$0rLJuecuJuF6.Exxp7AQO.w0Dh0iwfwZri45gwya6OqENBJwjPA7C', 'Security', 'Security Services', (SELECT id FROM staff_roles WHERE role_name = 'Security' LIMIT 1), 'Active'),
('STK001', 'Store Keeper', 'store@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$e52TV/DaoNDl4kjssi3Te.YHnpxHlaxatBX2wNg5yv3JkoYEEYV9i', 'Store Keeper', 'Support', (SELECT id FROM staff_roles WHERE role_name = 'Store Keeper' LIMIT 1), 'Active'),
('GUILD001', 'Guild President', 'guildpresident@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$YO8OuL81gpaFdgP4nJEebeXNhLeM1.hFMD5KidDV9YDGkJMdAqbgW', 'Guild President', 'Student Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Guild President' LIMIT 1), 'Active'),
('ADMS001', 'Admissions', 'admissions@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Director Admissions & Requirements', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Director Admissions & Requirements' LIMIT 1), 'Active'),
('DICT001', 'Director ICT', 'dannybict@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$Ha21Vlb7p046OaklPLFCteb8raqKNilEWDlzq8ypXVz491hHIICXS', 'Director ICT', 'Information Technology', (SELECT id FROM staff_roles WHERE role_name = 'Director ICT' LIMIT 1), 'Active')
ON DUPLICATE KEY UPDATE 
    password = VALUES(password),
    status = 'Active';

-- ============================================================
-- 2. PROCEDURES TO MANAGE STUDENT RECORDS
-- ============================================================

-- Drop existing procedures to avoid conflicts
DROP PROCEDURE IF EXISTS add_new_student;
DROP PROCEDURE IF EXISTS update_student_record;
DROP PROCEDURE IF EXISTS get_all_students_list;
DROP PROCEDURE IF EXISTS search_students;
DROP PROCEDURE IF EXISTS get_student_by_number;

DELIMITER //

-- Procedure to add a new student
CREATE PROCEDURE add_new_student(
    IN p_student_number VARCHAR(50),
    IN p_registration_number VARCHAR(50),
    IN p_index_number VARCHAR(50),
    IN p_national_id VARCHAR(50),
    IN p_first_name VARCHAR(100),
    IN p_surname VARCHAR(100),
    IN p_other_name VARCHAR(100),
    IN p_email VARCHAR(100),
    IN p_phone VARCHAR(20),
    IN p_program VARCHAR(100),
    IN p_year INT,
    IN p_set_name VARCHAR(50),
    IN p_intake_date DATE,
    IN p_date_of_birth DATE,
    IN p_gender ENUM('Male', 'Female', 'Other'),
    IN p_nationality VARCHAR(100),
    IN p_address TEXT,
    IN p_guardian_name VARCHAR(200),
    IN p_guardian_phone VARCHAR(20),
    IN p_emergency_contact_name VARCHAR(100),
    IN p_emergency_contact_phone VARCHAR(20),
    IN p_status ENUM('Active', 'Inactive', 'Graduated', 'Suspended', 'Withdrawn'),
    IN p_added_by INT
)
BEGIN
    DECLARE v_student_id INT;
    DECLARE v_password_hash VARCHAR(255);
    
    -- Default password: 12345678 (student must change on first login)
    SET v_password_hash = '$2y$10$N9qo8uLOickgx2ZMRZoMy.MrqJhZ3eP4dZB6lYqZ3eP4dZB6lYqZ3eP';
    
    -- Insert student record
    INSERT INTO igangaschoolofl_students_db.students (
        student_number, registration_number, index_number, national_student_id_number,
        first_name, surname, other_name, email, phone,
        program, current_year, set_name, intake_date,
        date_of_birth, gender, nationality, address,
        guardian_name, guardian_phone,
        emergency_contact_name, emergency_contact_phone,
        status, password, is_first_login, password_changed
    ) VALUES (
        p_student_number, p_registration_number, p_index_number, p_national_id,
        p_first_name, p_surname, p_other_name, p_email, p_phone,
        p_program, p_year, p_set_name, p_intake_date,
        p_date_of_birth, p_gender, p_nationality, p_address,
        p_guardian_name, p_guardian_phone,
        p_emergency_contact_name, p_emergency_contact_phone,
        p_status, v_password_hash, TRUE, FALSE
    );
    
    SET v_student_id = LAST_INSERT_ID();
    
    -- Create student profile record
    INSERT INTO igangaschoolofl_students_db.student_profiles (student_id)
    VALUES (v_student_id);
    
    -- Create default fee records
    INSERT INTO igangaschoolofl_students_db.student_fees (
        student_id, fee_type, amount, due_date, status
    ) VALUES
        (v_student_id, 'Tuition Fee', 500000, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Unpaid'),
        (v_student_id, 'Facility Fee', 50000, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Unpaid'),
        (v_student_id, 'Registration Fee', 20000, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Unpaid');
    
    -- Log the action
    INSERT INTO staff_activity_log (staff_id, activity_type, activity_description, module_accessed, record_id, ip_address)
    VALUES (p_added_by, 'Student Added', CONCAT('Added student: ', p_first_name, ' ', p_surname), 'Student Management', v_student_id, '0.0.0.0');
    
    SELECT v_student_id as student_id, 'Student added successfully' as message, TRUE as success;
END //

-- Procedure to update student record
CREATE PROCEDURE update_student_record(
    IN p_student_id INT,
    IN p_field VARCHAR(100),
    IN p_value TEXT,
    IN p_updated_by INT
)
BEGIN
    DECLARE v_old_value TEXT;
    
    -- Get old value for logging
    CASE p_field
        WHEN 'email' THEN
            SELECT email INTO v_old_value FROM igangaschoolofl_students_db.students WHERE id = p_student_id;
            UPDATE igangaschoolofl_students_db.students SET email = p_value WHERE id = p_student_id;
        WHEN 'phone' THEN
            SELECT phone INTO v_old_value FROM igangaschoolofl_students_db.students WHERE id = p_student_id;
            UPDATE igangaschoolofl_students_db.students SET phone = p_value WHERE id = p_student_id;
        WHEN 'program' THEN
            SELECT program INTO v_old_value FROM igangaschoolofl_students_db.students WHERE id = p_student_id;
            UPDATE igangaschoolofl_students_db.students SET program = p_value WHERE id = p_student_id;
        WHEN 'status' THEN
            SELECT status INTO v_old_value FROM igangaschoolofl_students_db.students WHERE id = p_student_id;
            UPDATE igangaschoolofl_students_db.students SET status = p_value WHERE id = p_student_id;
    END CASE;
    
    -- Log the update
    INSERT INTO staff_activity_log (staff_id, activity_type, activity_description, module_accessed, record_id)
    VALUES (p_updated_by, 'Student Updated', CONCAT('Updated ', p_field, ' from ', v_old_value, ' to ', p_value), 'Student Management', p_student_id);
    
    SELECT 'Student record updated successfully' as message, TRUE as success;
END //

-- Procedure to get all students
CREATE PROCEDURE get_all_students_list(
    IN p_program VARCHAR(100),
    IN p_set_name VARCHAR(50),
    IN p_status VARCHAR(50),
    IN p_limit INT
)
BEGIN
    IF p_limit IS NULL OR p_limit <= 0 THEN
        SET p_limit = 1000;
    END IF;
    
    SELECT 
        id, student_number, registration_number, index_number,
        full_name,
        email, phone, program, current_year, set_name, status,
        created_at
    FROM igangaschoolofl_students_db.students
    WHERE 
        (p_program IS NULL OR program = p_program)
        AND (p_set_name IS NULL OR set_name = p_set_name)
        AND (p_status IS NULL OR status = p_status)
    ORDER BY created_at DESC
    LIMIT p_limit;
END //

-- Procedure to search students
CREATE PROCEDURE search_students(
    IN p_search_term VARCHAR(100)
)
BEGIN
    SELECT 
        id, student_number, registration_number, index_number,
        full_name,
        email, phone, program, current_year, set_name, status,
        created_at
    FROM igangaschoolofl_students_db.students
    WHERE 
        student_number LIKE CONCAT('%', p_search_term, '%')
        OR registration_number LIKE CONCAT('%', p_search_term, '%')
        OR index_number LIKE CONCAT('%', p_search_term, '%')
        OR full_name LIKE CONCAT('%', p_search_term, '%')
        OR email LIKE CONCAT('%', p_search_term, '%')
        OR phone LIKE CONCAT('%', p_search_term, '%')
    ORDER BY created_at DESC;
END //

-- Procedure to get single student by number
CREATE PROCEDURE get_student_by_number(
    IN p_student_number VARCHAR(50)
)
BEGIN
    SELECT 
        id, student_number, registration_number, national_student_id_number,
        first_name, surname, other_name,
        CONCAT(first_name, ' ', surname, CASE WHEN other_name IS NOT NULL THEN CONCAT(' ', other_name) ELSE '' END) as full_name,
        email, phone, program, current_year, set_name, intake_date,
        date_of_birth, gender, nationality, address,
        guardian_name, guardian_phone,
        emergency_contact_name, emergency_contact_phone,
        status, created_at, updated_at
    FROM igangaschoolofl_students_db.students
    WHERE student_number = p_student_number;
END //

DELIMITER ;

-- ============================================================
-- 3. INSERT PROCEDURES INTO MASTER ROLE PERMISSIONS
-- ============================================================

-- Log all student management procedures for audit
INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('student_add_procedure', 'add_new_student', 'procedure', 'Procedure for adding new students', FALSE),
('student_update_procedure', 'update_student_record', 'procedure', 'Procedure for updating student records', FALSE),
('student_search_procedure', 'search_students', 'procedure', 'Procedure for searching students', FALSE),
('student_list_procedure', 'get_all_students_list', 'procedure', 'Procedure for listing all students', FALSE);

COMMIT;
-- ISNM Database Compatibility Layer
-- Creates compatibility views and tables for cross-schema references
-- Run this AFTER all main schema files

USE igangaschoolofl_staffs_db;

-- Compatibility: fee_payments -> payments (cross-database)
CREATE OR REPLACE VIEW fee_payments AS
SELECT 
    id,
    student_id,
    invoice_id AS fee_account_id,
    amount_received AS amount_paid,
    payment_method,
    payment_reference AS receipt_number,
    status,
    payment_date,
    notes,
    received_by AS processed_by,
    created_at,
    updated_at
FROM igangaschoolofl_students_db.payments;

-- Compatibility: student_fee_accounts -> student_fee_assignments (cross-database)
CREATE OR REPLACE VIEW student_fee_accounts AS
SELECT 
    id,
    student_id,
    fee_structure_id,
    assigned_amount AS total_fees,
    paid_amount AS amount_paid,
    balance,
    status,
    due_date,
    NULL AS receipt_number,
    assigned_by AS created_by,
    created_at,
    updated_at
FROM igangaschoolofl_students_db.student_fee_assignments;

-- Compatibility: users VIEW (already exists in 04_final_complete_staffs_database.sql)
-- Ensure it includes password for auth compatibility
CREATE OR REPLACE VIEW users AS
SELECT 
    s.id,
    s.staff_id AS username,
    s.full_name AS user_name,
    s.email,
    s.password,
    s.position,
    s.department,
    s.role_id,
    sr.role_name,
    sr.role_level,
    sr.dashboard_path,
    s.status,
    s.phone,
    s.address,
    s.hire_date,
    s.last_login,
    s.login_attempts,
    s.locked_until,
    s.is_first_login,
    s.created_at,
    s.updated_at
FROM staff s
JOIN staff_roles sr ON s.role_id = sr.id;

-- Compatibility: staff_users -> staff (for any remaining references)
CREATE OR REPLACE VIEW staff_users AS
SELECT 
    s.id,
    s.email,
    s.password AS password_hash,
    s.full_name,
    s.phone,
    s.position AS role,
    s.department,
    s.status AS is_active,
    s.is_first_login AS is_verified,
    s.created_at,
    s.updated_at
FROM staff s;

-- Compatibility: roles -> staff_roles
CREATE OR REPLACE VIEW roles AS
SELECT 
    id,
    role_name AS name,
    role_description AS description,
    permissions,
    created_at,
    updated_at
FROM staff_roles;

-- Compatibility: hr_users (minimal view for auth fallback)
CREATE OR REPLACE VIEW hr_users AS
SELECT 
    s.id,
    s.email,
    s.password AS password_hash,
    s.full_name,
    s.phone,
    s.position,
    s.department,
    s.status,
    s.created_at,
    s.updated_at
FROM staff s
WHERE s.department = 'Human Resources' OR s.position LIKE '%HR%';

-- End of compatibility views

-- Step 5: ICT Database
-- ============================================
-- ISNM Computer Lab Management System
-- ICT Department Database Tables
-- ============================================

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS igangaschoolofl_ict;
USE igangaschoolofl_ict;

-- ============================================
-- 1. Lab Computers Table
-- ============================================
CREATE TABLE IF NOT EXISTS lab_computers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    computer_id VARCHAR(50) UNIQUE NOT NULL,
    computer_name VARCHAR(100) NOT NULL,
    location VARCHAR(100) NOT NULL,
    status ENUM('online', 'offline', 'maintenance', 'deleted') DEFAULT 'online',
    ip_address VARCHAR(45),
    mac_address VARCHAR(17),
    specifications TEXT,
    os_installed VARCHAR(100),
    last_maintenance DATE,
    next_maintenance DATE,
    issues_reported TEXT,
    assigned_to VARCHAR(100),
    purchase_date DATE,
    warranty_expiry DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_location (location)
);

-- ============================================
-- 2. Lab Bookings Table
-- ============================================
CREATE TABLE IF NOT EXISTS lab_bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_reference VARCHAR(50) UNIQUE NOT NULL,
    course_name VARCHAR(100) NOT NULL,
    instructor_name VARCHAR(100) NOT NULL,
    instructor_email VARCHAR(100),
    booking_date DATE NOT NULL,
    time_slot VARCHAR(50) NOT NULL,
    number_of_students INT NOT NULL,
    purpose TEXT,
    special_requirements TEXT,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    approved_by INT,
    lab_assigned VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_date (booking_date),
    INDEX idx_status (status),
    INDEX idx_instructor (instructor_name)
);

-- ============================================
-- 3. IT Support Tickets Table
-- ============================================
CREATE TABLE IF NOT EXISTS it_support_tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ticket_number VARCHAR(50) UNIQUE NOT NULL,
    requester_name VARCHAR(100) NOT NULL,
    requester_email VARCHAR(100),
    requester_type ENUM('student', 'staff', 'faculty') NOT NULL,
    issue_type ENUM('hardware', 'software', 'network', 'account', 'other') NOT NULL,
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    description TEXT NOT NULL,
    status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
    assigned_to INT,
    resolution_notes TEXT,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_priority (priority),
    INDEX idx_requester (requester_name),
    INDEX idx_type (issue_type)
);

-- ============================================
-- 4. Software Inventory Table
-- ============================================
CREATE TABLE IF NOT EXISTS software_inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    software_name VARCHAR(200) NOT NULL,
    version VARCHAR(50),
    license_key VARCHAR(200),
    license_type ENUM('free', 'commercial', 'educational', 'trial') DEFAULT 'educational',
    license_expiry DATE,
    installation_count INT DEFAULT 0,
    update_available BOOLEAN DEFAULT FALSE,
    latest_version VARCHAR(50),
    download_url VARCHAR(500),
    category ENUM('os', 'office', 'development', 'design', 'antivirus', 'utility', 'other') DEFAULT 'utility',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_update (update_available)
);

-- ============================================
-- 5. Network Devices Table
-- ============================================
CREATE TABLE IF NOT EXISTS network_devices (
    id INT PRIMARY KEY AUTO_INCREMENT,
    device_name VARCHAR(100) NOT NULL,
    device_type ENUM('router', 'switch', 'access_point', 'firewall', 'server', 'other') NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    mac_address VARCHAR(17),
    location VARCHAR(100),
    status ENUM('online', 'offline', 'maintenance') DEFAULT 'online',
    firmware_version VARCHAR(50),
    last_check TIMESTAMP,
    uptime_hours INT DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_type (device_type),
    INDEX idx_ip (ip_address)
);

-- ============================================
-- 6. Maintenance Logs Table
-- ============================================
CREATE TABLE IF NOT EXISTS maintenance_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    computer_id VARCHAR(50) NOT NULL,
    maintenance_type ENUM('routine', 'repair', 'upgrade', 'cleaning') NOT NULL,
    description TEXT NOT NULL,
    performed_by VARCHAR(100) NOT NULL,
    cost DECIMAL(10,2) DEFAULT 0.00,
    parts_replaced TEXT,
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    scheduled_date DATE,
    completed_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_computer (computer_id),
    INDEX idx_status (status),
    INDEX idx_date (scheduled_date)
);

-- ============================================
-- 7. Lab Usage Statistics Table
-- ============================================
CREATE TABLE IF NOT EXISTS lab_usage_stats (
    id INT PRIMARY KEY AUTO_INCREMENT,
    lab_name VARCHAR(100) NOT NULL,
    date DATE NOT NULL,
    total_sessions INT DEFAULT 0,
    total_users INT DEFAULT 0,
    peak_concurrent_users INT DEFAULT 0,
    average_session_duration INT DEFAULT 0, -- in minutes
    computers_used INT DEFAULT 0,
    computers_available INT DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_lab_date (lab_name, date),
    INDEX idx_date (date),
    INDEX idx_lab (lab_name)
);

-- ============================================
-- Insert Sample Data for Testing
-- ============================================

-- Sample Lab Computers
INSERT INTO lab_computers (computer_id, computer_name, location, status, ip_address, mac_address, specifications, os_installed, last_maintenance, next_maintenance, issues_reported) VALUES
('LAB-A-001', 'Computer Lab A - Station 1', 'Lab A - Floor 1', 'online', '192.168.1.101', 'AA:BB:CC:DD:EE:01', 'Intel i5, 8GB RAM, 256GB SSD', 'Windows 11 Pro', '2024-05-01', '2024-08-01', NULL),
('LAB-A-002', 'Computer Lab A - Station 2', 'Lab A - Floor 1', 'online', '192.168.1.102', 'AA:BB:CC:DD:EE:02', 'Intel i5, 8GB RAM, 256GB SSD', 'Windows 11 Pro', '2024-05-01', '2024-08-01', NULL),
('LAB-A-003', 'Computer Lab A - Station 3', 'Lab A - Floor 1', 'offline', '192.168.1.103', 'AA:BB:CC:DD:EE:03', 'Intel i5, 8GB RAM, 256GB SSD', 'Windows 11 Pro', '2024-05-01', '2024-08-01', 'Hardware issue - PSU replacement needed'),
('LAB-B-001', 'Computer Lab B - Station 1', 'Lab B - Floor 2', 'online', '192.168.2.101', 'BB:CC:DD:EE:FF:01', 'Intel i7, 16GB RAM, 512GB SSD', 'Windows 11 Pro', '2024-05-15', '2024-08-15', NULL),
('LAB-B-002', 'Computer Lab B - Station 2', 'Lab B - Floor 2', 'maintenance', '192.168.2.102', 'BB:CC:DD:EE:FF:02', 'Intel i7, 16GB RAM, 512GB SSD', 'Windows 11 Pro', '2024-05-15', '2024-08-15', 'OS reinstallation in progress');

-- Sample Lab Bookings
INSERT INTO lab_bookings (booking_reference, course_name, instructor_name, instructor_email, booking_date, time_slot, number_of_students, purpose, special_requirements, status, approved_by, lab_assigned) VALUES
('BK-2024-001', 'Introduction to Nursing Informatics', 'Dr. Sarah Johnson', 'sjohnson@isnm.ac.ug', '2024-06-10', '09:00 AM - 11:00 AM', 25, 'Practical session on electronic health records', NULL, 'confirmed', NULL, 'Lab A'),
('BK-2024-002', 'Research Methods', 'Prof. Michael Okonkwo', 'mokonkwo@isnm.ac.ug', '2024-06-10', '02:00 PM - 04:00 PM', 30, 'Data analysis using SPSS', NULL, 'pending', NULL, 'Lab B'),
('BK-2024-003', 'Computer Literacy', 'Ms. Grace Namukasa', 'gnamukasa@isnm.ac.ug', '2024-06-11', '09:00 AM - 11:00 AM', 20, 'Basic computer skills training', NULL, 'confirmed', NULL, 'Lab A');

-- Sample IT Support Tickets
INSERT INTO it_support_tickets (ticket_number, requester_name, requester_email, requester_type, issue_type, priority, description, status, assigned_to, resolution_notes, resolved_at) VALUES
('TKT-2024-001', 'John Mugisha', 'jmugisha@student.isnm.ac.ug', 'student', 'software', 'medium', 'Unable to access SPSS software on Lab A computers', 'open', NULL, NULL, NULL),
('TKT-2024-002', 'Dr. Emily Achieng', 'eachieng@isnm.ac.ug', 'staff', 'hardware', 'high', 'Projector in Lab B not displaying properly', 'in_progress', NULL, NULL, NULL),
('TKT-2024-003', 'Peter Kato', 'pkato@student.isnm.ac.ug', 'student', 'account', 'low', 'Forgot password for student portal', 'open', NULL, NULL, NULL),
('TKT-2024-004', 'Ms. Ruth Akello', 'rakello@isnm.ac.ug', 'staff', 'network', 'critical', 'WiFi connection dropping frequently in Lab A', 'open', NULL, NULL, NULL);

-- Sample Software Inventory
INSERT INTO software_inventory (software_name, version, license_key, license_type, license_expiry, installation_count, update_available, latest_version, download_url, category, notes) VALUES
('Microsoft Office 365', '2024', NULL, 'educational', '2025-12-31', 50, FALSE, '2024', NULL, 'office', NULL),
('SPSS Statistics', '29.0', NULL, 'commercial', '2024-12-31', 25, TRUE, '30.0', NULL, 'development', NULL),
('Windows 11 Pro', '23H2', NULL, 'educational', '2026-06-30', 50, FALSE, '23H2', NULL, 'os', NULL),
('Adobe Creative Cloud', '2024', NULL, 'educational', '2024-08-31', 15, TRUE, '2024.1', NULL, 'design', NULL),
('Malwarebytes Antivirus', '5.0', NULL, 'commercial', '2025-01-15', 50, FALSE, '5.0', NULL, 'antivirus', NULL);

-- Sample Network Devices
INSERT INTO network_devices (device_name, device_type, ip_address, mac_address, location, status, firmware_version, last_check, uptime_hours, notes) VALUES
('Main Router', 'router', '192.168.0.1', '00:11:22:33:44:55', 'Server Room', 'online', 'v2.1.0', NULL, 720, NULL),
('Lab A Switch', 'switch', '192.168.1.1', '00:11:22:33:44:56', 'Lab A - Floor 1', 'online', 'v1.5.2', NULL, 480, NULL),
('Lab B Switch', 'switch', '192.168.2.1', '00:11:22:33:44:57', 'Lab B - Floor 2', 'online', 'v1.5.2', NULL, 480, NULL),
('WiFi Access Point A', 'access_point', '192.168.0.10', '00:11:22:33:44:58', 'Lab A - Floor 1', 'online', 'v3.2.1', NULL, 240, NULL),
('WiFi Access Point B', 'access_point', '192.168.0.11', '00:11:22:33:44:59', 'Lab B - Floor 2', 'offline', 'v3.2.1', NULL, 0, 'Needs repair'),
('Firewall', 'firewall', '192.168.0.2', '00:11:22:33:44:60', 'Server Room', 'online', 'v4.0.0', NULL, 720, NULL);

-- Sample Maintenance Logs
INSERT INTO maintenance_logs (computer_id, maintenance_type, description, performed_by, cost, parts_replaced, status, scheduled_date, completed_date) VALUES
('LAB-A-003', 'repair', 'Power supply unit replacement required', 'IT Technician - James', 150.00, NULL, 'scheduled', '2024-06-12', NULL),
('LAB-B-002', 'routine', 'Operating system reinstallation and updates', 'IT Technician - Sarah', 0.00, NULL, 'in_progress', '2024-06-10', NULL);

-- Sample Lab Usage Statistics
INSERT INTO lab_usage_stats (lab_name, date, total_sessions, total_users, peak_concurrent_users, average_session_duration, computers_used, computers_available, notes) VALUES
('Lab A', '2024-06-05', 8, 45, 25, 90, 22, 25, NULL),
('Lab B', '2024-06-05', 6, 35, 20, 85, 18, 20, NULL),
('Lab A', '2024-06-06', 10, 55, 28, 95, 24, 25, NULL),
('Lab B', '2024-06-06', 7, 40, 22, 80, 19, 20, NULL);

-- ============================================
-- Create Views for Common Queries
-- ============================================

-- View: Computer Availability Summary
CREATE OR REPLACE VIEW v_computer_availability AS
SELECT 
    location,
    COUNT(*) as total_computers,
    SUM(CASE WHEN status = 'online' THEN 1 ELSE 0 END) as online_count,
    SUM(CASE WHEN status = 'offline' THEN 1 ELSE 0 END) as offline_count,
    SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_count,
    ROUND(SUM(CASE WHEN status = 'online' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as availability_percentage
FROM lab_computers
WHERE status != 'deleted'
GROUP BY location;

-- View: Active Support Tickets Summary
CREATE OR REPLACE VIEW v_active_tickets AS
SELECT 
    priority,
    COUNT(*) as ticket_count,
    GROUP_CONCAT(ticket_number) as ticket_numbers
FROM it_support_tickets
WHERE status IN ('open', 'in_progress')
GROUP BY priority
ORDER BY 
    CASE priority 
        WHEN 'critical' THEN 1 
        WHEN 'high' THEN 2 
        WHEN 'medium' THEN 3 
        ELSE 4 
    END;

-- ============================================
-- Grant Permissions (adjust as needed)
-- ============================================
-- GRANT ALL PRIVILEGES ON igangaschoolofl_ict.* TO 'ict_user'@'localhost' IDENTIFIED BY 'secure_password';
-- FLUSH PRIVILEGES;

-- ============================================
-- End of Script
-- ============================================

-- Step 6: ICT Staff Accounts (in staffs database)
-- ISNM ICT Department Official Account Setup
-- Database: igangaschoolofl_staffs_db
-- This script creates/updates the ICT Director staff account

USE igangaschoolofl_staffs_db;

-- Safeguard: ensure role_description column exists in staff_roles
SET @dbname = DATABASE();
SELECT COUNT(*) INTO @col_exists
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'staff_roles' AND COLUMN_NAME = 'role_description';
SET @sql = IF(@col_exists = 0, 'ALTER TABLE staff_roles ADD COLUMN role_description TEXT AFTER role_name', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ensure the Director ICT role exists
INSERT IGNORE INTO staff_roles (role_name, role_description, role_level, dashboard_path, permissions)
VALUES ('Director ICT', 'Head of Computer Lab and IT Services - Independent Authority', 'Management', 'dashboards/director-ict.php', '{"ict":true,"systems":true,"can_manage_it":true,"can_access_computer_lab":true}');

-- Create/update the ICT Director account
-- Email: computer-lab@igangaschoolofnursingandmidwifery.ac.ug
-- Password: Techno123 (bcrypt hash below)
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at)
VALUES ('ICT001', 'ICT Department', 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'Director ICT', 'Information Communication Technology',
        (SELECT id FROM staff_roles WHERE role_name = 'Director ICT'),
        'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE
    staff_id = 'ICT001',
    position = 'Director ICT',
    department = 'Information Communication Technology',
    status = 'Active',
    updated_at = NOW();

-- Grant ICT-specific permissions
INSERT INTO staff_permissions (staff_id, module, permission_level, granted_by)
SELECT s.id, 'computer_lab', 'Admin', s.id
FROM staff s WHERE s.email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug'
ON DUPLICATE KEY UPDATE permission_level = 'Admin';

INSERT INTO staff_permissions (staff_id, module, permission_level, granted_by)
SELECT s.id, 'it_inventory', 'Admin', s.id
FROM staff s WHERE s.email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug'
ON DUPLICATE KEY UPDATE permission_level = 'Admin';

INSERT INTO staff_permissions (staff_id, module, permission_level, granted_by)
SELECT s.id, 'it_support', 'Admin', s.id
FROM staff s WHERE s.email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug'
ON DUPLICATE KEY UPDATE permission_level = 'Admin';

-- Log the account creation
INSERT INTO staff_activity_log (staff_id, activity_type, activity_description, module_accessed, ip_address, user_agent)
SELECT s.id, 'Account Created', 'ICT Department official account created/updated', 'authentication', 'SYSTEM', 'Account Setup Script'
FROM staff s WHERE s.email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug';

SELECT 'ICT Department Account Created Successfully' as status,
       email, position, department, 'Password: Techno123' as credentials,
       'Access: Director ICT Dashboard, Computer Lab, IT Inventory' as permissions
FROM staff WHERE email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug';
-- Computer Lab Manager Account Creation Script
-- Uses correct tables from igangaschoolofl_staffs_db
-- This script creates/updates the Computer Lab Manager staff account

USE igangaschoolofl_staffs_db;

-- Safeguard: ensure role_description column exists in staff_roles
SET @dbname = DATABASE();
SELECT COUNT(*) INTO @col_exists
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'staff_roles' AND COLUMN_NAME = 'role_description';
SET @sql = IF(@col_exists = 0, 'ALTER TABLE staff_roles ADD COLUMN role_description TEXT AFTER role_name', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- First ensure the Computer Lab Manager role exists
INSERT IGNORE INTO staff_roles (role_name, role_description, role_level, dashboard_path, permissions) VALUES
('Computer Lab Manager', 'Computer lab operations and IT support', 'Support', 'computer_lab.php', '{"ict": true, "lab_management": true, "it_support": true}');

-- Create/update the Computer Lab Manager account using ON DUPLICATE KEY UPDATE on email (UNIQUE column)
INSERT INTO staff (full_name, email, password, position, department, role_id, status, created_at)
SELECT 'Computer Lab Manager', 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'Computer Lab Manager', 'Information Technology',
        (SELECT id FROM staff_roles WHERE role_name = 'Computer Lab Manager'),
        'Active', NOW()
ON DUPLICATE KEY UPDATE
    full_name = 'Computer Lab Manager',
    position = 'Computer Lab Manager',
    department = 'Information Technology',
    password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    status = 'Active',
    updated_at = NOW();

INSERT INTO staff_permissions (staff_id, module, permission_level, granted_by)
SELECT s.id, 'computer_lab', 'Admin', s.id
FROM staff s WHERE s.email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug'
ON DUPLICATE KEY UPDATE permission_level = 'Admin';

INSERT INTO staff_permissions (staff_id, module, permission_level, granted_by)
SELECT s.id, 'it_inventory', 'Admin', s.id
FROM staff s WHERE s.email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug'
ON DUPLICATE KEY UPDATE permission_level = 'Admin';

INSERT INTO staff_permissions (staff_id, module, permission_level, granted_by)
SELECT s.id, 'it_support', 'Admin', s.id
FROM staff s WHERE s.email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug'
ON DUPLICATE KEY UPDATE permission_level = 'Admin';

SELECT 'Computer Lab Manager Account Created/Updated Successfully' as status,
       email, position, department, 'Password: Techno123' as credentials
FROM staff WHERE email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug';

-- Step 7: Staff Logins
-- ISNM LOGIN QUICK-FIX - Secure & Error Free
-- Safe to run WITHOUT dropping tables. Uses INSERT IGNORE / ON DUPLICATE KEY UPDATE.
-- Ensures all roles and staff accounts exist with correct bcrypt passwords.
-- Compatible with the full 04_final_complete_staffs_database.sql schema.
--
-- IMPORTANT: Only use this file if you have NOT run the master setup.
-- If you ran 99_MASTER_ALL_DEPARTMENTS.sql, skip this file entirely.

USE `igangaschoolofl_staffs_db`;

-- Safeguard: ensure role_description column exists in staff_roles
SET @dbname = DATABASE();
SET @tbl = 'staff_roles';
SET @col = 'role_description';
SELECT COUNT(*) INTO @col_exists
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tbl AND COLUMN_NAME = @col;
SET @sql = IF(@col_exists = 0, CONCAT('ALTER TABLE ', @tbl, ' ADD COLUMN ', @col, ' TEXT AFTER role_name'), 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Ensure all roles exist (idempotent)
INSERT IGNORE INTO staff_roles (role_name, role_description, role_level, dashboard_path, permissions) VALUES
('Director ICT', 'Head of Computer Lab and IT Services', 'Management', 'dashboards/director-ict.php', '{"ict":true,"systems":true,"can_manage_it":true,"can_access_computer_lab":true}'),
('Director General', 'Overall school administration', 'Executive', 'dashboards/director-general.php', '{"all":true,"can_access_all_dashboards":true}'),
('CEO', 'Chief Executive Officer', 'Executive', 'dashboards/ceo.php', '{"strategic":true,"financial":true}'),
('Director Academics', 'Academic programs oversight', 'Management', 'dashboards/director-academics.php', '{"academic":true,"curriculum":true}'),
('Director Finance', 'Financial management', 'Management', 'dashboards/director-finance.php', '{"financial":true,"budgeting":true}'),
('School Principal', 'School leadership', 'Executive', 'dashboards/school-principal.php', '{"academic":true,"administrative":true}'),
('Deputy Principal', 'Assistant principal', 'Management', 'dashboards/deputy-principal.php', '{"academic":true,"administrative":true}'),
('Academic Registrar', 'Student registration and records', 'Academic', 'dashboards/academic-registrar.php', '{"academic":true,"students":true,"registration":true}'),
('HR Manager', 'Human resources', 'Management', 'dashboards/hr-manager.php', '{"hr":true,"staff":true}'),
('School Secretary', 'Administrative support', 'Administrative', 'dashboards/school-secretary.php', '{"administrative":true,"documentation":true}'),
('School Librarian', 'Library management', 'Support', 'dashboards/school-librarian.php', '{"library":true,"resources":true}'),
('Head Nursing', 'Nursing department', 'Academic', 'dashboards/head-nursing.php', '{"nursing":true,"department":true}'),
('Head Midwifery', 'Midwifery department', 'Academic', 'dashboards/head-midwifery.php', '{"midwifery":true,"department":true}'),
('Senior Lecturers', 'Senior teaching staff', 'Academic', 'dashboards/senior-lecturers.php', '{"teaching":true,"lecturers":true}'),
('Lecturers', 'Teaching staff', 'Academic', 'dashboards/lecturers.php', '{"teaching":true,"lecturers":true}'),
('Matrons', 'Student welfare', 'Support', 'dashboards/matrons.php', '{"student_welfare":true,"residential":true}'),
('Wardens', 'Student discipline', 'Support', 'dashboards/wardens.php', '{"student_welfare":true,"discipline":true}'),
('Sickbay', 'Medical support', 'Support', 'dashboards/sickbay.php', '{"healthcare":true,"medical":true}'),
('Drivers', 'Transportation', 'Support', 'dashboards/drivers.php', '{"transportation":true,"vehicles":true}'),
('Security', 'Campus security', 'Support', 'dashboards/security.php', '{"security":true,"safety":true}'),
('Store Keeper', 'Store inventory', 'Support', 'dashboards/storekeeper.php', '{"store":true,"inventory":true}'),
('Guild President', 'Student guild', 'Support', 'dashboards/guild-president.php', '{"student_affairs":true}'),
('Director Admissions & Requirements', 'Admissions management', 'Management', 'dashboards/director-admissions.php', '{"admissions":true,"requirements":true}'),
('School Bursar', 'Financial operations', 'Administrative', 'bursar_dashboard.php', '{"financial":true,"fees":true}'),
('Bursar', 'Bursar assistant', 'Administrative', 'bursar_dashboard.php', '{"financial":true,"fees":true}');

-- Ensure key staff accounts exist with correct bcrypt password hashes
-- Password for all main accounts: staff@123
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('ICT001', 'ICT Department', 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Director ICT', 'Information Communication Technology', (SELECT id FROM staff_roles WHERE role_name = 'Director ICT'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', position = 'Director ICT', department = 'Information Communication Technology', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('ICT002', 'ICT Director', 'dannybict@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Director ICT', 'Information Technology', (SELECT id FROM staff_roles WHERE role_name = 'Director ICT'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'dannybict@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', position = 'Director ICT', department = 'Information Technology', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('DG001', 'Director General', 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Director General', 'Executive Office', (SELECT id FROM staff_roles WHERE role_name = 'Director General'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', position = 'Director General', department = 'Executive Office', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('CEO001', 'CEO', 'ceo@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Chief Executive Officer', 'Executive Office', (SELECT id FROM staff_roles WHERE role_name = 'CEO'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'ceo@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', position = 'Chief Executive Officer', department = 'Executive Office', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('DA001', 'Director Academics', 'directoracademic@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Director Academics', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Director Academics'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'directoracademic@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', position = 'Director Academics', department = 'Academic Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('DF001', 'Director Finance', 'finance@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Director Finance', 'Finance Department', (SELECT id FROM staff_roles WHERE role_name = 'Director Finance'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'finance@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', position = 'Director Finance', department = 'Finance Department', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('SP001', 'School Principal', 'principal@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$VVoHfONmCz.Bsvn1.t1UoesLbM01KNPXKT/b/VJIzxeUq0M9LabK.', 'School Principal', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'School Principal'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'principal@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$VVoHfONmCz.Bsvn1.t1UoesLbM01KNPXKT/b/VJIzxeUq0M9LabK.', position = 'School Principal', department = 'Academic Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('DP001', 'Deputy Principal', 'dep-principal@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$ANzSCNiGrURlS1ovFbQUKuK6ldOOBpiC0iW/MB7HVw/I5JC9wud.m', 'Deputy Principal', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Deputy Principal'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'dep-principal@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$ANzSCNiGrURlS1ovFbQUKuK6ldOOBpiC0iW/MB7HVw/I5JC9wud.m', position = 'Deputy Principal', department = 'Academic Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('AR001', 'Academic Registrar', 'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$Ha21Vlb7p046OaklPLFCteb8raqKNilEWDlzq8ypXVz491hHIICXS', 'Academic Registrar', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Academic Registrar'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$Ha21Vlb7p046OaklPLFCteb8raqKNilEWDlzq8ypXVz491hHIICXS', position = 'Academic Registrar', department = 'Academic Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('HR001', 'HR Manager', 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$jEb8/OsV.9cydSvrBrZ1Hejase4BaTkPXT3FO/Gf9EazTrbXprKYi', 'HR Manager', 'Human Resources', (SELECT id FROM staff_roles WHERE role_name = 'HR Manager'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$jEb8/OsV.9cydSvrBrZ1Hejase4BaTkPXT3FO/Gf9EazTrbXprKYi', position = 'HR Manager', department = 'Human Resources', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('SEC001', 'School Secretary', 'secretary@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$MtVRrE2x6uXh0CwEobzG.ueN1zcL/aE541mbLWpg3e7gnX4HkUxn.', 'School Secretary', 'Administrative Office', (SELECT id FROM staff_roles WHERE role_name = 'School Secretary'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'secretary@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$MtVRrE2x6uXh0CwEobzG.ueN1zcL/aE541mbLWpg3e7gnX4HkUxn.', position = 'School Secretary', department = 'Administrative Office', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('LIB001', 'School Librarian', 'library@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$GGfcvNfejW3f2fRptIUQIuK4c/W44n94twWtTAaOTqTVSuLZ52DsC', 'School Librarian', 'Library Services', (SELECT id FROM staff_roles WHERE role_name = 'School Librarian'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'library@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$GGfcvNfejW3f2fRptIUQIuK4c/W44n94twWtTAaOTqTVSuLZ52DsC', position = 'School Librarian', department = 'Library Services', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('HN001', 'Head Nursing', 'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$YO8OuL81gpaFdgP4nJEebeXNhLeM1.hFMD5KidDV9YDGkJMdAqbgW', 'Head Nursing', 'Nursing Department', (SELECT id FROM staff_roles WHERE role_name = 'Head Nursing'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$YO8OuL81gpaFdgP4nJEebeXNhLeM1.hFMD5KidDV9YDGkJMdAqbgW', position = 'Head Nursing', department = 'Nursing Department', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('HM001', 'Head Midwifery', 'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$G7pMLdi2UjjmhEd8Lx0bmeaM7tGD4jrfvMsZh6HvY1Po8YqFRubRu', 'Head Midwifery', 'Midwifery Department', (SELECT id FROM staff_roles WHERE role_name = 'Head Midwifery'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$G7pMLdi2UjjmhEd8Lx0bmeaM7tGD4jrfvMsZh6HvY1Po8YqFRubRu', position = 'Head Midwifery', department = 'Midwifery Department', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('LEC001', 'Lecturers', 'lecturers@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$e52TV/DaoNDl4kjssi3Te.YHnpxHlaxatBX2wNg5yv3JkoYEEYV9i', 'Lecturer', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Lecturers'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'lecturers@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$e52TV/DaoNDl4kjssi3Te.YHnpxHlaxatBX2wNg5yv3JkoYEEYV9i', position = 'Lecturer', department = 'Academic Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('SLE001', 'Senior Lecturers', 'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$1gsFX/B27b5YuIAP7D5OSO2acgrtV7RcIMeja6RblX/9e5YSFfguy', 'Senior Lecturer', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Senior Lecturers'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$1gsFX/B27b5YuIAP7D5OSO2acgrtV7RcIMeja6RblX/9e5YSFfguy', position = 'Senior Lecturer', department = 'Academic Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('NTS001', 'Non-Teaching Staff', 'nonteaching@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Non-Teaching Staff', 'Administrative', (SELECT id FROM staff_roles WHERE role_name = 'Non-Teaching Staff'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'nonteaching@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', position = 'Non-Teaching Staff', department = 'Administrative', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('LAB001', 'Sickbay', 'sickbay@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$kzTn6S3OUtKLmGoLNo9GOOHqIki7NwUxvZJ6pJK02Yls6eR7Bln82', 'Sickbay', 'Support', (SELECT id FROM staff_roles WHERE role_name = 'Sickbay'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'sickbay@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$kzTn6S3OUtKLmGoLNo9GOOHqIki7NwUxvZJ6pJK02Yls6eR7Bln82', position = 'Sickbay', department = 'Support', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('MAT001', 'Matrons', 'matron@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$Qj7feWYysqaK1INwS50PFehU09Tgf6MOUNVBJZaOw3LZW/jGHZEkO', 'Matrons', 'Student Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Matrons'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'matron@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$Qj7feWYysqaK1INwS50PFehU09Tgf6MOUNVBJZaOw3LZW/jGHZEkO', position = 'Matrons', department = 'Student Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('SECUR001', 'Security', 'security@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$0rLJuecuJuF6.Exxp7AQO.w0Dh0iwfwZri45gwya6OqENBJwjPA7C', 'Security', 'Security Services', (SELECT id FROM staff_roles WHERE role_name = 'Security'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'security@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$0rLJuecuJuF6.Exxp7AQO.w0Dh0iwfwZri45gwya6OqENBJwjPA7C', position = 'Security', department = 'Security Services', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('DRV001', 'Drivers', 'drivers@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$HrQ6V56zJJxIz8j.2grJVOWs2DjFGzA/wxzejvE3vtkk57KFuAjge', 'Drivers', 'Transport', (SELECT id FROM staff_roles WHERE role_name = 'Drivers'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'drivers@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$HrQ6V56zJJxIz8j.2grJVOWs2DjFGzA/wxzejvE3vtkk57KFuAjge', position = 'Drivers', department = 'Transport', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('WDN001', 'Wardens', 'warden@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$jCKwMrdU.s1DVuA2HHFp6eBPK05F70IUoyAvRZX6Qf3wdPsCZBXM2', 'Wardens', 'Student Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Wardens'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'warden@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$jCKwMrdU.s1DVuA2HHFp6eBPK05F70IUoyAvRZX6Qf3wdPsCZBXM2', position = 'Wardens', department = 'Student Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('STK001', 'Store Keeper', 'store@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$8qETvaYu2nreko/c/DyPROdIlMZyAciahJOVwHCV0KG4WxrcicxnS', 'Store Keeper', 'Facilities Management', (SELECT id FROM staff_roles WHERE role_name = 'Store Keeper'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'store@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$8qETvaYu2nreko/c/DyPROdIlMZyAciahJOVwHCV0KG4WxrcicxnS', position = 'Store Keeper', department = 'Facilities Management', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('BUR001', 'School Bursar', 'bursar@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'School Bursar', 'Finance Department', (SELECT id FROM staff_roles WHERE role_name = 'School Bursar'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'bursar@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', position = 'School Bursar', department = 'Finance Department', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('BURS002', 'Bursar', 'bursar.assistant@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Bursar', 'Finance Department', (SELECT id FROM staff_roles WHERE role_name = 'Bursar'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'bursar.assistant@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', position = 'Bursar', department = 'Finance Department', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('ADM001', 'Admissions', 'admissions@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Director Admissions & Requirements', 'Admissions', (SELECT id FROM staff_roles WHERE role_name = 'Director Admissions & Requirements'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'admissions@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', position = 'Director Admissions & Requirements', department = 'Admissions', status = 'Active', is_first_login = TRUE, updated_at = NOW();

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('GUILD001', 'Guild President', 'guildpresident@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', 'Guild President', 'Student Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Guild President'), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'guildpresident@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$4zcQrEqXVRJuRbsabv0bu.FZ5JllaLQHcAPNPGA0.7puX3Ltmhq.K', position = 'Guild President', department = 'Student Affairs', status = 'Active', is_first_login = TRUE, updated_at = NOW();

-- Verification
SELECT '========================================' AS info;
SELECT CONCAT('Roles: ', COUNT(*), ' | Staff: ', COUNT(*)) AS setup_check FROM staff_roles, staff;
SELECT 'Login fix complete. Use staff@123 for all accounts.' AS status;

-- Step 8: Website Database
-- ISNM Website Database Schema
-- Database: igangaschoolofl_website_db

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS igangaschoolofl_website_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE igangaschoolofl_website_db;

-- Drop existing tables if they exist (for fresh installation)
DROP TABLE IF EXISTS pages;
DROP TABLE IF EXISTS posts;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS galleries;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS contact_submissions;
DROP TABLE IF EXISTS news;
DROP TABLE IF EXISTS announcements;
DROP TABLE IF EXISTS settings;

-- 1. Pages Table
CREATE TABLE pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    content LONGTEXT NOT NULL,
    meta_title VARCHAR(200),
    meta_description TEXT,
    meta_keywords VARCHAR(500),
    status ENUM('Published', 'Draft', 'Archived') DEFAULT 'Draft',
    featured_image VARCHAR(500),
    page_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_page_order (page_order)
);

-- 2. Categories Table (MUST be before posts for FK reference)
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    parent_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_slug (slug),
    INDEX idx_parent_id (parent_id)
);

-- 3. Posts Table (Blog/News)
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    content LONGTEXT NOT NULL,
    excerpt TEXT,
    featured_image VARCHAR(500),
    category_id INT,
    author VARCHAR(100),
    status ENUM('Published', 'Draft', 'Archived') DEFAULT 'Draft',
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_category_id (category_id),
    INDEX idx_published_at (published_at)
);

-- 4. Galleries Table
CREATE TABLE galleries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    folder_name VARCHAR(100) NOT NULL,
    cover_image VARCHAR(500),
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- 5. Applications Table
CREATE TABLE applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    course VARCHAR(100),
    year INT,
    previous_school VARCHAR(200),
    guardian_name VARCHAR(100),
    guardian_phone VARCHAR(20),
    message TEXT,
    status ENUM('New', 'Under Review', 'Accepted', 'Rejected', 'Waitlisted') DEFAULT 'New',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_course (course),
    INDEX idx_created_at (created_at)
);

-- 6. Contact Submissions Table
CREATE TABLE contact_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message LONGTEXT NOT NULL,
    status ENUM('New', 'Read', 'Responded') DEFAULT 'New',
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);

-- 7. News Table
CREATE TABLE news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) NOT NULL UNIQUE,
    content LONGTEXT NOT NULL,
    excerpt TEXT,
    featured_image VARCHAR(500),
    status ENUM('Published', 'Draft', 'Archived') DEFAULT 'Draft',
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_published_at (published_at)
);

-- Enhance news table with author columns for director publishing
ALTER TABLE news
    ADD COLUMN IF NOT EXISTS author_id INT DEFAULT NULL AFTER featured_image,
    ADD COLUMN IF NOT EXISTS author_name VARCHAR(200) DEFAULT NULL AFTER author_id,
    ADD COLUMN IF NOT EXISTS author_role VARCHAR(100) DEFAULT NULL AFTER author_name;

-- 7b. Director News Table (admin-side news management)
CREATE TABLE IF NOT EXISTS director_news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    content LONGTEXT NOT NULL,
    excerpt TEXT,
    featured_image VARCHAR(500),
    author_id INT NOT NULL,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    published_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_author_id (author_id),
    INDEX idx_published_at (published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7c. News Images Table
CREATE TABLE IF NOT EXISTS news_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    news_id INT NOT NULL,
    image_path VARCHAR(500) NOT NULL,
    caption VARCHAR(255),
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_news_id (news_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- STORE MANAGEMENT SYSTEM
-- Tables for store inventory, requests, orders, and transactions
-- ============================================================

SET @_old_fk = @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;

-- 7d. Store Categories
CREATE TABLE IF NOT EXISTS store_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    icon VARCHAR(50) DEFAULT 'fas fa-box',
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category_name (category_name),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7e. Store Inventory
CREATE TABLE IF NOT EXISTS store_inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    item_name VARCHAR(255) NOT NULL,
    description TEXT,
    quantity DECIMAL(15,3) NOT NULL DEFAULT 0,
    unit VARCHAR(50) NOT NULL DEFAULT 'pcs',
    reorder_level DECIMAL(15,3) DEFAULT 10,
    unit_price DECIMAL(15,2) DEFAULT 0,
    location VARCHAR(100) DEFAULT 'Main Store',
    status ENUM('active','inactive','discontinued') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category_id (category_id),
    INDEX idx_item_name (item_name),
    INDEX idx_status (status),
    FOREIGN KEY (category_id) REFERENCES store_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7f. Store Inventory Transactions (audit trail)
CREATE TABLE IF NOT EXISTS store_inventory_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    transaction_type ENUM('add','remove','adjust','request_fulfilled','order_received','returned','damaged') NOT NULL,
    quantity DECIMAL(15,3) NOT NULL,
    quantity_before DECIMAL(15,3) DEFAULT 0,
    quantity_after DECIMAL(15,3) DEFAULT 0,
    reference_type VARCHAR(50) DEFAULT NULL,
    reference_id INT DEFAULT NULL,
    reason TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_item_id (item_id),
    INDEX idx_transaction_type (transaction_type),
    INDEX idx_created_at (created_at),
    INDEX idx_reference (reference_type, reference_id),
    FOREIGN KEY (item_id) REFERENCES store_inventory(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7g. Store Requests (staff request items from store)
CREATE TABLE IF NOT EXISTS store_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_number VARCHAR(50) NOT NULL UNIQUE,
    requested_by INT NOT NULL,
    department VARCHAR(200) DEFAULT NULL,
    notes TEXT,
    urgency ENUM('low','medium','high','urgent') DEFAULT 'medium',
    status ENUM('pending','approved','partially_fulfilled','fulfilled','rejected','forwarded') DEFAULT 'pending',
    forwarded_to INT DEFAULT NULL,
    forwarded_to_role VARCHAR(100) DEFAULT NULL,
    approved_by INT DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    fulfilled_by INT DEFAULT NULL,
    fulfilled_at DATETIME DEFAULT NULL,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_request_number (request_number),
    INDEX idx_requested_by (requested_by),
    INDEX idx_status (status),
    INDEX idx_forwarded_to (forwarded_to),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7h. Store Request Items (line items in each request)
CREATE TABLE IF NOT EXISTS store_request_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity_requested DECIMAL(15,3) NOT NULL,
    quantity_fulfilled DECIMAL(15,3) DEFAULT 0,
    unit_price DECIMAL(15,2) DEFAULT 0,
    notes TEXT,
    status ENUM('pending','fulfilled','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_request_id (request_id),
    INDEX idx_item_id (item_id),
    FOREIGN KEY (request_id) REFERENCES store_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES store_inventory(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7i. Store Orders (storekeeper orders replenishment)
CREATE TABLE IF NOT EXISTS store_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) NOT NULL UNIQUE,
    supplier VARCHAR(200) DEFAULT 'Internal Requisition',
    notes TEXT,
    total_amount DECIMAL(15,2) DEFAULT 0,
    status ENUM('draft','pending_approval','approved','ordered','partially_received','received','cancelled') DEFAULT 'draft',
    requested_by INT NOT NULL,
    approved_by INT DEFAULT NULL,
    approved_at DATETIME DEFAULT NULL,
    received_by INT DEFAULT NULL,
    received_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order_number (order_number),
    INDEX idx_status (status),
    INDEX idx_requested_by (requested_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7j. Store Order Items
CREATE TABLE IF NOT EXISTS store_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    item_id INT NOT NULL,
    quantity_ordered DECIMAL(15,3) NOT NULL,
    quantity_received DECIMAL(15,3) DEFAULT 0,
    unit_price DECIMAL(15,2) DEFAULT 0,
    notes TEXT,
    status ENUM('pending','received','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    INDEX idx_item_id (item_id),
    FOREIGN KEY (order_id) REFERENCES store_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (item_id) REFERENCES store_inventory(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = @_old_fk;

-- Populate Store Categories
INSERT IGNORE INTO store_categories (category_name, description, icon) VALUES
('General Utilities', 'Office supplies, cleaning, electrical, and general maintenance items', 'fas fa-tools'),
('Food Store Supplies', 'Food items, cooking ingredients, and kitchen supplies', 'fas fa-utensils'),
('Medical Supplies', 'Medical consumables, gloves, dressings, and clinical items', 'fas fa-kit-medical'),
('Cleaning & Hygiene', 'Cleaning agents, sanitizers, and hygiene products', 'fas fa-pump-soap'),
('Office Stationery', 'Paper, writing materials, filing and office stationery', 'fas fa-pen-ruler'),
('Electrical & Hardware', 'Electrical fittings, tools, and hardware items', 'fas fa-bolt'),
('Kitchen & Dining', 'Kitchen utensils, dining items, and catering supplies', 'fas fa-kitchen-set'),
('Furniture & Storage', 'Furniture, shelves, filing cabinets, and storage items', 'fas fa-couch'),
('ICT Supplies', 'Computer consumables, printer supplies, and ICT accessories', 'fas fa-laptop'),
('Teaching & Training', 'Teaching aids, simulation supplies, and training materials', 'fas fa-chalkboard-user');

-- Populate Store Items (using variables for category IDs)
SET @gen_util = (SELECT id FROM store_categories WHERE category_name = 'General Utilities');
SET @food     = (SELECT id FROM store_categories WHERE category_name = 'Food Store Supplies');
SET @medical  = (SELECT id FROM store_categories WHERE category_name = 'Medical Supplies');
SET @cleaning = (SELECT id FROM store_categories WHERE category_name = 'Cleaning & Hygiene');
SET @stationery = (SELECT id FROM store_categories WHERE category_name = 'Office Stationery');
SET @electrical = (SELECT id FROM store_categories WHERE category_name = 'Electrical & Hardware');
SET @kitchen    = (SELECT id FROM store_categories WHERE category_name = 'Kitchen & Dining');
SET @furniture  = (SELECT id FROM store_categories WHERE category_name = 'Furniture & Storage');
SET @ict        = (SELECT id FROM store_categories WHERE category_name = 'ICT Supplies');
SET @teaching   = (SELECT id FROM store_categories WHERE category_name = 'Teaching & Training');

INSERT IGNORE INTO store_inventory (category_id, item_name, unit, reorder_level) VALUES
(@gen_util, 'Surgical Gloves', 'boxes', 50),
(@gen_util, 'Binding Tape', 'rolls', 20),
(@gen_util, 'Examination Gloves', 'boxes', 50),
(@gen_util, 'Masking Tape', 'rolls', 30),
(@gen_util, 'Sink Pumps', 'pcs', 5),
(@gen_util, 'Ruled Reams', 'reams', 20),
(@gen_util, 'Requirements Clearance Books', 'books', 50),
(@gen_util, 'Receipt Books', 'books', 50),
(@gen_util, 'Photocopying Reams', 'reams', 50),
(@gen_util, 'Payment Voucher Books', 'books', 30),
(@gen_util, 'Binding Rings', 'packs', 20),
(@gen_util, 'Ring Binder Files', 'pcs', 30),
(@gen_util, 'Box Files', 'pcs', 30),
(@gen_util, 'Counter Books', 'books', 20),
(@gen_util, 'Layer File Trays', 'pcs', 10),
(@gen_util, 'Atlas Files', 'pcs', 20),
(@gen_util, 'Domiciliary Kit Bags', 'pcs', 30),
(@gen_util, 'PVC Covers', 'pcs', 50),
(@gen_util, 'Laminating Paper', 'packs', 15),
(@gen_util, 'Liquid Soap', 'liters', 50),
(@gen_util, 'Toilet Papers', 'rolls', 100),
(@gen_util, 'Insulation Tape', 'rolls', 20),
(@gen_util, 'Carbon Papers', 'packs', 20),
(@gen_util, 'Blackboard Dusters', 'pcs', 15);

INSERT IGNORE INTO store_inventory (category_id, item_name, unit, reorder_level) VALUES
(@cleaning, 'Omo (Detergent)', 'kg', 50),
(@cleaning, 'Vim (Cleaning Powder)', 'pcs', 30),
(@cleaning, 'Jik (Bleach)', 'liters', 30),
(@cleaning, 'Scrubbing Brushes', 'pcs', 20),
(@cleaning, 'Squeezers', 'pcs', 15),
(@cleaning, 'Mops', 'pcs', 20),
(@cleaning, 'Toilet Brushes', 'pcs', 20),
(@cleaning, 'Cobweb Brushes', 'pcs', 15),
(@cleaning, 'Soft Brooms', 'pcs', 20),
(@cleaning, 'Compound Brooms', 'pcs', 15),
(@cleaning, 'Rakes', 'pcs', 10),
(@cleaning, 'Stainless Steel Cleaner', 'liters', 10),
(@cleaning, 'Floor Polish', 'liters', 15),
(@cleaning, 'Air Freshener', 'pcs', 20),
(@cleaning, 'Hand Sanitizer', 'liters', 30),
(@cleaning, 'Disposable Gloves (Cleaning)', 'pairs', 100),
(@cleaning, 'Dustbins', 'pcs', 20),
(@cleaning, 'Dustpans', 'pcs', 15),
(@cleaning, 'Buckets', 'pcs', 20),
(@cleaning, 'Wheelbarrows', 'pcs', 5);

INSERT IGNORE INTO store_inventory (category_id, item_name, unit, reorder_level) VALUES
(@stationery, 'A3 Envelopes', 'packs', 20),
(@stationery, 'A4 Envelopes', 'packs', 30),
(@stationery, 'A5 Envelopes', 'packs', 20),
(@stationery, 'Markers (Permanent)', 'pcs', 30),
(@stationery, 'Markers (Whiteboard)', 'pcs', 30),
(@stationery, 'Color Papers', 'packs', 20),
(@stationery, 'Staple Wires', 'boxes', 30),
(@stationery, 'Paper Clips', 'boxes', 30),
(@stationery, 'Chalk (White)', 'boxes', 50),
(@stationery, 'Chalk (Colored)', 'boxes', 30),
(@stationery, 'Pens (Blue)', 'pcs', 100),
(@stationery, 'Pens (Black)', 'pcs', 100),
(@stationery, 'Pens (Red)', 'pcs', 50),
(@stationery, 'Pencils', 'pcs', 100),
(@stationery, 'Rubbers (Erasers)', 'pcs', 50),
(@stationery, 'Office Glue', 'pcs', 30),
(@stationery, 'Stick Glue', 'pcs', 30),
(@stationery, 'Sticky Notes', 'pads', 30),
(@stationery, 'Stapler Machines', 'pcs', 15),
(@stationery, 'Stapler Removers', 'pcs', 15),
(@stationery, 'Hole Punchers', 'pcs', 15),
(@stationery, 'Rulers (30cm)', 'pcs', 30),
(@stationery, 'Scissors', 'pcs', 20),
(@stationery, 'Calculators (Basic)', 'pcs', 10),
(@stationery, 'Bulldog Clips', 'pcs', 30),
(@stationery, 'Highlighter Markers', 'pcs', 30),
(@stationery, 'Correction Fluid', 'pcs', 20),
(@stationery, 'Correction Tape', 'pcs', 20),
(@stationery, 'Manila Envelopes', 'packs', 20),
(@stationery, 'Sticker Labels', 'sheets', 30);

INSERT IGNORE INTO store_inventory (category_id, item_name, unit, reorder_level) VALUES
(@electrical, 'Double Gang Switches', 'pcs', 20),
(@electrical, 'Single Gang Switches', 'pcs', 20),
(@electrical, 'Lamp Holders', 'pcs', 30),
(@electrical, 'Single Sockets', 'pcs', 20),
(@electrical, 'Double Sockets', 'pcs', 20),
(@electrical, 'Bulbs (LED 10W)', 'pcs', 50),
(@electrical, 'Bulbs (LED 20W)', 'pcs', 30),
(@electrical, 'Bulbs (LED 40W)', 'pcs', 20),
(@electrical, 'Mounting Boxes', 'pcs', 30),
(@electrical, 'PVC Conduit Pipes', 'pcs', 20),
(@electrical, 'Electrical Cables (1.5mm)', 'meters', 100),
(@electrical, 'Electrical Cables (2.5mm)', 'meters', 100),
(@electrical, 'Socket Spanners', 'sets', 5),
(@electrical, 'Screwdrivers Set', 'sets', 10),
(@electrical, 'Hammers', 'pcs', 10),
(@electrical, 'Combination Pliers', 'pcs', 10),
(@electrical, 'Long Nose Pliers', 'pcs', 10),
(@electrical, 'Measuring Tapes', 'pcs', 10),
(@electrical, 'Padlocks', 'pcs', 20),
(@electrical, 'Door Handles', 'pcs', 20),
(@electrical, 'Door Hinges', 'pcs', 30),
(@electrical, 'WD-40 Lubricant', 'cans', 10),
(@electrical, 'Painter Masking Tape', 'rolls', 20),
(@electrical, 'PVC Glue', 'cans', 10),
(@electrical, 'Super Glue', 'pcs', 20);

INSERT IGNORE INTO store_inventory (category_id, item_name, unit, reorder_level) VALUES
(@food, 'Posho (Maize Flour)', 'kg', 500),
(@food, 'Rice', 'kg', 300),
(@food, 'Beans', 'kg', 300),
(@food, 'Salt', 'kg', 50),
(@food, 'Cooking Oil', 'liters', 100),
(@food, 'Sugar', 'kg', 100),
(@food, 'Plates (Melamine)', 'pcs', 100),
(@food, 'Plates (Ceramic)', 'pcs', 50),
(@food, 'Cups (Plastic)', 'pcs', 100),
(@food, 'Cups (Ceramic)', 'pcs', 50),
(@food, 'Tablespoons', 'pcs', 100),
(@food, 'Teaspoons', 'pcs', 100),
(@food, 'Forks', 'pcs', 50),
(@food, 'Kitchen Knives', 'pcs', 20),
(@food, 'Sauce Pans', 'pcs', 20),
(@food, 'Cooking Pots (Large)', 'pcs', 10),
(@food, 'Cooking Pots (Medium)', 'pcs', 15),
(@food, 'Frying Pans', 'pcs', 10),
(@food, 'Thermos Flasks', 'pcs', 20),
(@food, 'Water Jugs', 'pcs', 20),
(@food, 'Charcoal', 'bags', 50),
(@food, 'Firewood', 'bundles', 30),
(@food, 'Tea Leaves', 'kg', 20),
(@food, 'Milk Powder', 'kg', 20),
(@food, 'Baking Flour', 'kg', 30),
(@food, 'Tomato Paste', 'cans', 50),
(@food, 'Onions', 'kg', 50),
(@food, 'Irish Potatoes', 'kg', 100),
(@food, 'Matooke (Green Bananas)', 'bunches', 50),
(@food, 'Cassava Flour', 'kg', 50),
(@food, 'Ghee', 'kg', 15),
(@food, 'Groundnut Paste', 'kg', 30),
(@food, 'Soy Flour', 'kg', 30),
(@food, 'Cabbage', 'pcs', 30),
(@food, 'Dried Fish', 'kg', 30),
(@food, 'Pasta (Spaghetti/Macaroni)', 'kg', 30);

INSERT IGNORE INTO store_inventory (category_id, item_name, unit, reorder_level) VALUES
(@kitchen, 'Chopping Boards', 'pcs', 10),
(@kitchen, 'Kitchen Towels', 'pcs', 30),
(@kitchen, 'Kitchen Aprons', 'pcs', 20),
(@kitchen, 'Oven Gloves', 'pairs', 10),
(@kitchen, 'Colanders', 'pcs', 10),
(@kitchen, 'Measuring Cups', 'sets', 10),
(@kitchen, 'Water Dispensers', 'pcs', 10),
(@kitchen, 'Ice Cube Trays', 'pcs', 15),
(@kitchen, 'Food Storage Containers', 'pcs', 30),
(@kitchen, 'Serving Trays', 'pcs', 20);

INSERT IGNORE INTO store_inventory (category_id, item_name, unit, reorder_level) VALUES
(@medical, 'Sterile Surgical Gloves', 'boxes', 50),
(@medical, 'Latex Examination Gloves', 'boxes', 100),
(@medical, 'Nitrile Examination Gloves', 'boxes', 50),
(@medical, 'Surgical Face Masks', 'boxes', 100),
(@medical, 'N95 Face Masks', 'boxes', 50),
(@medical, 'Syringes (5ml)', 'packs', 100),
(@medical, 'Syringes (10ml)', 'packs', 50),
(@medical, 'Cotton Wool', 'rolls', 50),
(@medical, 'Gauze Swabs', 'packs', 100),
(@medical, 'Crepe Bandages', 'rolls', 50),
(@medical, 'Elastic Bandages', 'rolls', 30),
(@medical, 'Medical Adhesive Tape', 'rolls', 50),
(@medical, 'Wound Dressings (Plaster)', 'packs', 50),
(@medical, 'Dettol Antiseptic', 'liters', 30),
(@medical, 'Methylated Spirit', 'liters', 30),
(@medical, 'Hydrogen Peroxide', 'liters', 20),
(@medical, 'Betadine Solution', 'liters', 20),
(@medical, 'Digital Thermometers', 'pcs', 20),
(@medical, 'Manual BP Machines', 'pcs', 10),
(@medical, 'Digital BP Machines', 'pcs', 5),
(@medical, 'Stethoscopes', 'pcs', 15),
(@medical, 'Tongue Depressors', 'packs', 50),
(@medical, 'Urine Test Strips', 'packs', 20),
(@medical, 'Specimen Containers', 'pcs', 100),
(@medical, 'Sharps Disposal Containers', 'pcs', 30),
(@medical, 'Disposable Bed Sheets', 'packs', 50),
(@medical, 'Disposable Protective Gowns', 'pcs', 100),
(@medical, 'Disposable Shoe Covers', 'pairs', 100),
(@medical, 'Disposable Hair Caps', 'pcs', 100);

INSERT IGNORE INTO store_inventory (category_id, item_name, unit, reorder_level) VALUES
(@ict, 'HP Toner Cartridges', 'pcs', 10),
(@ict, 'Canon Toner Cartridges', 'pcs', 10),
(@ict, 'Epson Toner Cartridges', 'pcs', 10),
(@ict, 'A4 Printing Paper', 'reams', 100),
(@ict, 'A3 Printing Paper', 'reams', 30),
(@ict, 'Flash Drives (16GB)', 'pcs', 20),
(@ict, 'Flash Drives (32GB)', 'pcs', 10),
(@ict, 'External Hard Drives (1TB)', 'pcs', 5),
(@ict, 'USB Keyboards', 'pcs', 20),
(@ict, 'USB Mice', 'pcs', 20),
(@ict, 'Mouse Pads', 'pcs', 30),
(@ict, 'USB Cables', 'pcs', 20),
(@ict, 'HDMI Cables', 'pcs', 10),
(@ict, 'VGA Cables', 'pcs', 10),
(@ict, 'Power Extension Strips', 'pcs', 20),
(@ict, 'UPS Batteries', 'pcs', 5),
(@ict, 'Cat6 Ethernet Cables', 'pcs', 20),
(@ict, 'Webcams', 'pcs', 5),
(@ict, 'Headphones', 'pcs', 10),
(@ict, 'Printer Label Sheets', 'packs', 15);

INSERT IGNORE INTO store_inventory (category_id, item_name, unit, reorder_level) VALUES
(@furniture, 'Office Desks', 'pcs', 10),
(@furniture, 'Office Chairs', 'pcs', 20),
(@furniture, 'Visitor Chairs', 'pcs', 20),
(@furniture, '4-Drawer Filing Cabinets', 'pcs', 10),
(@furniture, 'Bookshelves', 'pcs', 10),
(@furniture, 'Large Whiteboards', 'pcs', 10),
(@furniture, 'Small Whiteboards', 'pcs', 15),
(@furniture, 'Cork Notice Boards', 'pcs', 15),
(@furniture, 'Conference Tables', 'pcs', 5),
(@furniture, 'Metal Storage Shelves', 'pcs', 10),
(@furniture, 'Personal Lockers', 'pcs', 20),
(@furniture, 'Waste Paper Baskets', 'pcs', 30);

INSERT IGNORE INTO store_inventory (category_id, item_name, unit, reorder_level) VALUES
(@teaching, 'Skeleton Anatomical Models', 'pcs', 3),
(@teaching, 'Organ Anatomical Models', 'sets', 3),
(@teaching, 'Resuscitation Mannequins', 'pcs', 5),
(@teaching, 'Injection Practice Pads', 'pcs', 20),
(@teaching, 'IV Training Arms', 'pcs', 5),
(@teaching, 'Catheterization Models', 'pcs', 5),
(@teaching, 'Baby Delivery Simulators', 'pcs', 3),
(@teaching, 'First Aid Kits', 'kits', 20),
(@teaching, 'Portable Projectors', 'pcs', 5),
(@teaching, 'Projector Screens', 'pcs', 5),
(@teaching, 'Flip Chart Stands', 'pcs', 10),
(@teaching, 'Flip Chart Pads', 'pads', 30),
(@teaching, 'Nursing Wall Charts', 'sets', 10),
(@teaching, 'Midwifery Wall Charts', 'sets', 10),
(@teaching, 'Educational DVDs', 'pcs', 20);

-- 8. Announcements Table
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content LONGTEXT NOT NULL,
    announcement_type ENUM('general', 'academic', 'finance', 'admissions', 'events', 'emergency') DEFAULT 'general',
    target_audience ENUM('all', 'students', 'staff', 'website') DEFAULT 'all',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    posted_by_name VARCHAR(200) DEFAULT NULL,
    posted_by_role VARCHAR(100) DEFAULT NULL,
    posted_by INT DEFAULT NULL,
    posted_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expiry_date DATE NULL,
    status ENUM('draft', 'published', 'expired') DEFAULT 'draft',
    attachment_path VARCHAR(500) DEFAULT NULL,
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_announcement_type (announcement_type),
    INDEX idx_target_audience (target_audience),
    INDEX idx_priority (priority),
    INDEX idx_status (status),
    INDEX idx_posted_date (posted_date)
);

-- 8. Settings Table (Enhanced)
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value LONGTEXT,
    setting_type ENUM('text', 'number', 'boolean', 'file', 'json', 'array', 'email', 'url', 'color', 'date') DEFAULT 'text',
    description TEXT,
    category VARCHAR(50) DEFAULT 'general',
    is_public BOOLEAN DEFAULT FALSE,
    is_editable BOOLEAN DEFAULT TRUE,
    validation_rules JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key),
    INDEX idx_setting_type (setting_type),
    INDEX idx_category (category),
    INDEX idx_is_public (is_public),
    INDEX idx_is_editable (is_editable)
);

-- Insert sample data for testing

-- Sample pages
INSERT INTO pages (title, slug, content, meta_title, meta_description, meta_keywords, status, page_order) VALUES
('Home', 'home', '<h1>Welcome to ISNM</h1><p>Professional nursing and midwifery education.</p>', 'Home - ISNM', 'Welcome to Iganga School of Nursing and Midwifery', 'nursing, midwifery, education, uganda', 'Published', 1),
('About Us', 'about-us', '<h1>About ISNM</h1><p>Leading nursing and midwifery education in Uganda.</p>', 'About ISNM', 'Learn about our mission and values', 'about, mission, values', 'Published', 2),
('Programs', 'programs', '<h1>Our Programs</h1><p>Comprehensive nursing and midwifery programs.</p>', 'Programs - ISNM', 'Explore our academic programs', 'programs, courses, academics', 'Published', 3),
('Contact', 'contact', '<h1>Contact Us</h1><p>Get in touch with our team.</p>', 'Contact ISNM', 'Contact information and location', 'contact, location, address', 'Published', 4);

-- Sample categories
INSERT INTO categories (name, slug, description) VALUES
('News', 'news', 'Latest news and announcements'),
('Events', 'events', 'Upcoming events and activities'),
('Academics', 'academics', 'Academic information and updates'),
('Admissions', 'admissions', 'Admission information and requirements');

-- Sample posts
INSERT INTO posts (title, slug, content, excerpt, category_id, author, status, published_at) VALUES
('Welcome to ISNM', 'welcome-to-isnm', '<h1>Welcome to ISNM</h1><p>We are pleased to welcome you to our institution.</p>', 'Welcome message for new students and visitors.', 1, 'Administrator', 'Published', NOW()),
('New Academic Year 2025/2026', 'new-academic-year-2025-2026', '<h1>New Academic Year</h1><p>Applications are now open for the 2025/2026 academic year.</p>', 'Applications are open for the new academic year.', 1, 'Administrator', 'Published', NOW());

-- Sample galleries
INSERT INTO galleries (title, description, folder_name, cover_image, status) VALUES
('Campus Life', 'Photos of campus activities and facilities', 'campus-life', 'images/gallery/campus1.jpg', 'Active'),
('Graduation Ceremony', 'Recent graduation ceremony photos', 'graduation-2025', 'images/gallery/graduation1.jpg', 'Active'),
('Clinical Training', 'Clinical practice and training sessions', 'clinical-training', 'images/gallery/clinical1.jpg', 'Active');

-- Sample announcements (published by authorized roles)
INSERT INTO announcements (title, content, announcement_type, target_audience, priority, posted_by_name, posted_by_role, status, posted_date) VALUES
('Orientation Week 2026', 'Orientation for new students will run from July 1â€“5. All new students must report to the main hall by 8:00 AM.', 'academic', 'students', 'high', 'Dr. Jane K. Mwambazi', 'Director General', 'published', NOW()),
('Fee Payment Deadline', 'Final deadline for Semester 1 fees is June 30, 2026. Late payments will attract penalties.', 'finance', 'students', 'urgent', 'Mr. Samuel Ochieng', 'Director Finance', 'published', NOW()),
('Admissions Open', 'Applications for the Certificate and Diploma programs are now open. Apply via the Admissions portal.', 'admissions', 'all', 'medium', 'Ms. Alice Nabulime', 'Director Admissions', 'published', NOW()),
('Institution Leadership Message', 'The CEO welcomes all stakeholders to the new phase of institutional growth and collaboration.', 'general', 'staff', 'medium', 'Mr. Peter K. Lule', 'CEO', 'published', NOW());

-- Sample settings
INSERT INTO settings (setting_key, setting_value, setting_type, description, category, is_public) VALUES
('school_name', 'Iganga School of Nursing and Midwifery', 'text', 'School name for display', 'general', TRUE),
('school_address', 'P.O. Box 416, Iganga District, Uganda', 'text', 'School address', 'general', TRUE),
('school_phone', '+256 703 204722', 'text', 'School phone number', 'general', TRUE),
('school_email', 'admissions@igangaschoolofnursingandmidwifery.ac.ug', 'email', 'Primary school contact email', 'general', TRUE),
('school_website', 'isnm.ac.ug', 'url', 'School website URL', 'general', TRUE),
('mission_statement', 'To provide quality nursing and midwifery education for healthcare excellence', 'text', 'School mission statement', 'general', TRUE),
('vision_statement', 'To be a leading institution in nursing and midwifery education in Uganda', 'text', 'School vision statement', 'general', TRUE),
('admissions_email', 'admissions@igangaschoolofnursingandmidwifery.ac.ug', 'email', 'Admissions & Requirements Office â€” the official point of contact for all student intake and equipment clearance', 'admissions', TRUE),
('admissions_open', 'true', 'boolean', 'Admissions status', 'admissions', TRUE),
('current_academic_year', '2025/2026', 'text', 'Current academic year', 'academic', TRUE),
('current_semester', 'Semester 1', 'text', 'Current semester', 'academic', TRUE),
('contact_email', 'info@isnm.ac.ug', 'email', 'Contact email', 'contact', TRUE),
('contact_phone', '+256 123 456 789', 'text', 'Contact phone', 'contact', TRUE),
('social_media_facebook', 'https://facebook.com/ISNMUganda', 'url', 'Facebook page URL', 'social', TRUE),
('social_media_twitter', 'https://twitter.com/ISNMUganda', 'url', 'Twitter profile URL', 'social', TRUE),
('admissions_email', 'admissions@igangaschoolofnursingandmidwifery.ac.ug', 'email', 'Admissions & Requirements Office contact email', 'admissions', TRUE),
('site_maintenance', 'false', 'boolean', 'Site maintenance mode', 'system', FALSE),
('enable_notifications', 'true', 'boolean', 'Enable email notifications', 'notifications', FALSE),
('max_upload_size', '10485760', 'number', 'Maximum upload file size in bytes', 'system', FALSE),
('default_language', 'en', 'text', 'Default site language', 'ui', FALSE),
('timezone', 'Africa/Kampala', 'text', 'System timezone', 'system', FALSE),
('backup_frequency', 'daily', 'text', 'Backup frequency', 'system', FALSE),
('enable_analytics', 'true', 'boolean', 'Enable analytics tracking', 'analytics', FALSE),
('seo_meta_description', 'ISNM - Leading nursing and midwifery education in Uganda', 'text', 'Default SEO meta description', 'seo', TRUE),
('seo_meta_keywords', 'nursing, midwifery, education, Uganda, ISNM, healthcare', 'text', 'Default SEO meta keywords', 'seo', TRUE),
('google_analytics_code', '', 'text', 'Google Analytics tracking code', 'analytics', FALSE),
('facebook_pixel_code', '', 'text', 'Facebook Pixel tracking code', 'analytics', FALSE),
('cookie_policy', 'This site uses cookies to improve your experience.', 'text', 'Cookie policy text', 'privacy', TRUE),
('privacy_policy', 'Your privacy is important to us...', 'text', 'Privacy policy content', 'privacy', TRUE),
('terms_of_service', 'By using this site, you agree to...', 'text', 'Terms of service content', 'legal', TRUE);

-- End of Website Database Schema

SELECT '========================================' as '';
SELECT 'ISNM COMPLETE SETUP FINISHED!' as '';
SELECT 'All databases and tables created successfully.' as '';
