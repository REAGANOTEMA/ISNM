-- ISNM Final Complete Staffs Database Schema
-- Database: staffs_db
-- Professional unified authentication system for all staff with role-based access control

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS staffs_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE staffs_db;

-- Drop existing tables if they exist (for fresh installation)
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
DROP TABLE IF EXISTS staff_departments;
DROP TABLE IF EXISTS staff_profiles;
DROP TABLE IF EXISTS staff;
DROP TABLE IF EXISTS staff_roles;
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
    expires_at TIMESTAMP NOT NULL,
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
    expires_at TIMESTAMP NOT NULL,
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
    activity_type ENUM('Login', 'Logout', 'Dashboard Access', 'Data View', 'Data Edit', 'Data Delete', 'Export', 'Print', 'Settings Change') NOT NULL,
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
CREATE TABLE receipt_templates (
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
INSERT INTO document_templates (template_name, template_type, template_content, is_default, created_by) VALUES
('Standard Transcript', 'transcript', '<html><body><h1>Academic Transcript</h1><table border="1"><tr><td>Student Name:</td><td>{{student_name}}</td></tr><tr><td>Student ID:</td><td>{{student_id}}</td></tr></table></body></html>', TRUE, 1),
('Professional Certificate', 'certificate', '<html><body><h1>Certificate of Completion</h1><p>This is to certify that <strong>{{student_name}}</strong> has successfully completed the <strong>{{program}}</strong> program.</p></body></html>', TRUE, 1),
('Standard Receipt', 'receipt', '<html><body><h1>Payment Receipt</h1><table border="1"><tr><td>Receipt No:</td><td>{{receipt_number}}</td></tr><tr><td>Amount:</td><td>{{amount}}</td></tr></table></body></html>', TRUE, 1),
('Payslip Template', 'payslip', '<html><body><h1>Payslip</h1><table border="1"><tr><td>Employee:</td><td>{{employee_name}}</td></tr><tr><td>Net Salary:</td><td>{{net_salary}}</td></tr><tr><td>Tax:</td><td>{{tax}}</td></tr><tr><td>Allowance:</td><td>{{allowance}}</td></tr></table></body></html>', TRUE, 1),
('Student ID Card', 'id_card', '<html><body><h1>Student ID Card</h1><div style="border: 2px solid #000; padding: 20px; width: 300px;"><p><strong>Name:</strong> {{student_name}}</p><p><strong>ID:</strong> {{student_id}}</p><p><strong>Program:</strong> {{program}}</p></div></body></html>', TRUE, 1),
('Leave Request Form', 'leave_form', '<html><body><h1>Leave Request Form</h1><table border="1"><tr><td>Employee Name:</td><td>{{employee_name}}</td></tr><tr><td>Leave Type:</td><td>{{leave_type}}</td></tr><tr><td>Duration:</td><td>{{duration}}</td></tr><tr><td>Reason:</td><td>{{reason}}</td></tr></table></body></html>', TRUE, 1),
('Performance Review', 'performance_review', '<html><body><h1>Performance Review</h1><table border="1"><tr><td>Employee:</td><td>{{employee_name}}</td></tr><tr><td>Period:</td><td>{{review_period}}</td></tr><tr><td>Rating:</td><td>{{rating}}</td></tr><tr><td>Comments:</td><td>{{comments}}</td></tr></table></body></html>', TRUE, 1);

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
('School Bursar', 'Financial operations and fee management', 'Administrative', 'dashboards/school-bursar.php', '{"financial": true, "fees": true, "collections": true, "can_manage_fees": true}'),
('School Librarian', 'Library and resource management', 'Support', 'dashboards/school-librarian.php', '{"library": true, "resources": true, "catalog": true}'),
('Head Nursing', 'Nursing department management', 'Academic', 'dashboards/head-nursing.php', '{"nursing": true, "department": true, "faculty": true}'),
('Head Midwifery', 'Midwifery department management', 'Academic', 'dashboards/head-midwifery.php', '{"midwifery": true, "department": true, "faculty": true}'),
('Lecturers', 'Teaching and academic staff management', 'Academic', 'dashboards/lecturers.php', '{"teaching": true, "lecturers": true, "courses": true}'),
('Senior Lecturers', 'Senior teaching staff management', 'Academic', 'dashboards/senior-lecturers.php', '{"teaching": true, "lecturers": true, "senior": true}'),
('Non-Teaching Staff', 'Administrative and support staff', 'Administrative', 'dashboards/non-teaching-staff.php', '{"administrative": true, "support": true}'),
('Lab Technicians', 'Laboratory and technical staff management', 'Support', 'dashboards/lab-technicians.php', '{"laboratory": true, "equipment": true}'),
('Matrons', 'Student welfare and residential staff management', 'Support', 'dashboards/matrons.php', '{"student_welfare": true, "residential": true}'),
('Security', 'Campus security and safety management', 'Support', 'dashboards/security.php', '{"security": true, "safety": true, "emergency": true}'),
('Drivers', 'Transportation and vehicle management', 'Support', 'dashboards/drivers.php', '{"transportation": true, "vehicles": true}'),
('Wardens', 'Student discipline and residential supervision', 'Support', 'dashboards/wardens.php', '{"student_welfare": true, "discipline": true, "residential": true}'),
('School Secretary', 'Administrative support and documentation', 'Administrative', 'dashboards/school-secretary.php', '{"administrative": true, "documentation": true, "can_manage_documents": true}'),
('Deputy Principal', 'Assistant to school principal', 'Management', 'dashboards/deputy-principal.php', '{"academic": true, "administrative": true, "can_assist_principal": true}'),
('Bursar', 'Financial assistant', 'Administrative', 'dashboards/bursar.php', '{"financial": true, "fees": true, "can_assist_bursar": true}'),
('Secretary', 'Administrative assistant', 'Administrative', 'dashboards/secretary.php', '{"administrative": true, "documentation": true, "can_assist_secretary": true}');

-- Insert main administrator account with unified credentials
-- Email: administration@isnm.ac
-- Password: 12345678 (bcrypt hash)
INSERT INTO staff (
    staff_id, 
    full_name, 
    email, 
    password, 
    position, 
    department, 
    role_id, 
    status, 
    hire_date,
    password_changed,
    is_first_login,
    created_at
) VALUES (
    'ADMIN001',
    'System Administrator',
    'administration@isnm.ac',
    '$2y$10$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36WQoeG6Lruj3vjPGga31lW',
    'System Administrator',
    'Executive Office',
    (SELECT id FROM staff_roles WHERE role_name = 'Director General' LIMIT 1),
    'Active',
    CURDATE(),
    FALSE,
    TRUE,
    NOW()
) ON DUPLICATE KEY UPDATE 
    email = 'administration@isnm.ac',
    password = '$2y$10$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36WQoeG6Lruj3vjPGga31lW',
    is_first_login = TRUE,
    updated_at = NOW();

-- Insert dashboard access permissions for all staff roles
INSERT INTO staff_dashboard_access (staff_id, dashboard_path, access_level, granted_by) 
SELECT 
    s.id,
    sr.dashboard_path,
    'Full',
    1
FROM staff s 
JOIN staff_roles sr ON s.role_id = sr.id 
WHERE sr.role_name IN ('Director General', 'School Principal', 'CEO', 'Director Academics', 'Director Finance', 'Director ICT', 'HR Manager', 'Academic Registrar', 'School Bursar', 'School Librarian', 'Head Nursing', 'Head Midwifery', 'Lecturers', 'Senior Lecturers', 'Non-Teaching Staff', 'Lab Technicians', 'Matrons', 'Security', 'Drivers', 'Wardens', 'School Secretary', 'Bursar', 'Deputy Principal');

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
('Fee Payment Receipt', 'Fee Payment', '<h2>ISNM FEE PAYMENT RECEIPT</h2><p><strong>Receipt No:</strong> {{receipt_number}}</p><p><strong>Student:</strong> {{student_name}}</p><p><strong>Amount:</strong> UGX {{amount}}</p><p><strong>Date:</strong> {{date}}</p><p><strong>Payment Method:</strong> {{payment_method}}</p>', '{"receipt_number": "string", "student_name": "string", "amount": "number", "date": "date", "payment_method": "string"}', (SELECT id FROM staff WHERE email = 'isnm@administration.ac'));

-- Insert default transcript templates
INSERT INTO generated_documents (document_type, generated_by, document_title, document_content, access_code, generation_date) VALUES
('Student Transcript', (SELECT id FROM staff WHERE email = 'isnm@administration.ac'), 'Official Academic Transcript', '<h2>IGANGA SCHOOL OF NURSING AND MIDWIFERY</h2><h3>OFFICIAL ACADEMIC TRANSCRIPT</h3><p><strong>Student Name:</strong> {{student_name}}</p><p><strong>Registration Number:</strong> {{registration_number}}</p><p><strong>Program:</strong> {{program}}</p><p><strong>Year:</strong> {{year}}</p><p><strong>GPA:</strong> {{gpa}}</p><p><strong>Status:</strong> {{status}}</p>', 'TRANS_' . date('YmdHis'), NOW());

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('school_name', 'Iganga School of Nursing and Midwifery', 'text', 'Official school name', true),
('school_code', 'ISNM', 'text', 'School abbreviation', true),
('academic_year', '2024-2025', 'text', 'Current academic year', true),
('semester', 'Semester 1', 'text', 'Current semester', true),
('max_login_attempts', '5', 'number', 'Maximum login attempts before lockout', false),
('lockout_duration', '900', 'number', 'Account lockout duration in seconds', false),
('session_timeout', '3600', 'number', 'Session timeout in seconds', false),
('default_password', '12345678', 'text', 'Default password for new accounts', false),
('school_address', 'Iganga, Uganda', 'text', 'School physical address', true),
('school_phone', '+256 XXX XXX XXX', 'text', 'School contact phone', true),
('school_email', 'info@isnm.ug', 'text', 'School contact email', true),
('developer_email', 'isnm@administration.ac', 'text', 'Developer login email', false),
('allow_password_change', 'true', 'boolean', 'Allow staff to change passwords', true),
('require_two_factor', 'false', 'boolean', 'Require two-factor authentication', false),
('allow_profile_upload', 'true', 'boolean', 'Allow staff to upload profile pictures', true),
('max_file_size', '5242880', 'number', 'Maximum file upload size in bytes', false),
('allowed_file_types', 'jpg,jpeg,png,pdf,doc,docx', 'text', 'Allowed file types for upload', false),
('allow_receipt_printing', 'true', 'boolean', 'Allow staff to print receipts', true),
('allow_transcript_generation', 'true', 'boolean', 'Allow transcript generation for authorized staff', false),
('receipt_template_path', 'templates/receipts/', 'text', 'Path to receipt templates', false),
('transcript_template_path', 'templates/transcripts/', 'text', 'Path to transcript templates', false);

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
    item_category ENUM('Office Supplies', 'Laboratory Equipment', 'Medical Supplies', 'Furniture', 'Computers', 'Books', 'Uniforms', 'Food', 'Cleaning Supplies', 'Other') NOT NULL,
    description TEXT,
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
    INDEX idx_item_code (item_code)
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
    submitted_by INT NOT NULL,
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
    requested_by INT NOT NULL,
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
    changed_by INT NOT NULL,
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
    sender_id INT NOT NULL,
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

-- LAB TECHNICIANS DEPARTMENT TABLES

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
CREATE PROCEDURE IF NOT EXISTS get_lab_technicians_dashboard_statistics(
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
    ELSEIF p_role = 'Lab Technician' THEN
        CALL get_lab_technicians_dashboard_statistics(p_user_id);
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
