-- ISNM Final Complete Staffs Database Schema
-- Database: staffs_db
-- Professional unified authentication system for all staff with role-based access control

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS staffs_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE staffs_db;

-- Drop existing tables if they exist (for fresh installation)
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

-- 2. Staff Table (Enhanced with authentication)
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
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key),
    INDEX idx_setting_type (setting_type),
    INDEX idx_is_public (is_public)
);

-- 22. Access Control Management Table
CREATE TABLE staff_access_control (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    module_name VARCHAR(100) NOT NULL,
    access_level ENUM('None', 'Read', 'Write', 'Delete', 'Admin') DEFAULT 'Read',
    granted_by INT NOT NULL,
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
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    is_public BOOLEAN DEFAULT FALSE,
    access_code VARCHAR(50) UNIQUE,
    download_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
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

-- 34. System Settings Table (Enhanced)
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value LONGTEXT,
    setting_type ENUM('text', 'number', 'boolean', 'file', 'json', 'array', 'email', 'url', 'color', 'date') DEFAULT 'text',
    description TEXT,
    category VARCHAR(50),
    is_public BOOLEAN DEFAULT FALSE,
    is_editable BOOLEAN DEFAULT TRUE,
    validation_rules JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (created_by) REFERENCES staff(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_setting_key (setting_key),
    INDEX idx_setting_type (setting_type),
    INDEX idx_is_public (is_public),
    INDEX idx_is_editable (is_editable)
);

-- 35. Real-time Updates Table
CREATE TABLE real_time_updates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    update_type ENUM('new_student', 'staff_change', 'system_alert', 'data_sync', 'feature_update') NOT NULL,
    update_title VARCHAR(200) NOT NULL,
    update_description TEXT,
    update_data JSON,
    target_users JSON,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    is_active BOOLEAN DEFAULT TRUE,
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_entity_type (entity_type),
    INDEX idx_entity_id (entity_id),
    FULLTEXT idx_searchable_content (searchable_content),
    FULLTEXT idx_keywords (keywords)
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
('Payslip Template', 'payslip', '<html><body><h1>Payslip</h1><table border="1"><tr><td>Employee:</td><td>{{employee_name}}</td></tr><tr><td>Net Salary:</td><td>{{net_salary}}</td></tr></table></body></html>', TRUE, 1),
('Student ID Card', 'id_card', '<html><body><h1>Student ID Card</h1><div style="border: 2px solid #000; padding: 20px; width: 300px;"><p><strong>Name:</strong> {{student_name}}</p><p><strong>ID:</strong> {{student_id}}</p><p><strong>Program:</strong> {{program}}</p></div></body></html>', TRUE, 1),
('Leave Request Form', 'leave_form', '<html><body><h1>Leave Request Form</h1><table border="1"><tr><td>Employee Name:</td><td>{{employee_name}}</td></tr><tr><td>Leave Type:</td><td>{{leave_type}}</td></tr><tr><td>Duration:</td><td>{{duration}}</td></tr></table></body></html>', TRUE, 1),
('Performance Review', 'performance_review', '<html><body><h1>Performance Review</h1><table border="1"><tr><td>Employee:</td><td>{{employee_name}}</td></tr><tr><td>Period:</td><td>{{review_period}}</td></tr><tr><td>Rating:</td><td>{{rating}}</td></tr></table></body></html>', TRUE, 1);

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

-- Insert default staff roles with proper permissions
INSERT INTO staff_roles (role_name, role_description, role_level, dashboard_path, permissions) VALUES
('Director General', 'Overall school administration and management', 'Executive', 'dashboards/director-general.php', '{"all": true, "can_access_all_dashboards": true, "can_manage_all_staff": true}'),
('School Principal', 'School academic and administrative leadership', 'Executive', 'dashboards/school-principal.php', '{"academic": true, "administrative": true, "staff": true, "students": true, "can_view_all_departments": true}'),
('CEO', 'Chief Executive Officer for strategic management', 'Executive', 'dashboards/ceo.php', '{"strategic": true, "financial": true, "operational": true, "can_view_reports": true}'),
('Director Academics', 'Academic programs and curriculum oversight', 'Management', 'dashboards/director-academics.php', '{"academic": true, "curriculum": true, "faculty": true, "can_manage_courses": true}'),
('Director Finance', 'Financial management and oversight', 'Management', 'dashboards/director-finance.php', '{"financial": true, "budgeting": true, "reporting": true, "can_manage_finances": true}'),
('Director ICT', 'Information Technology management', 'Management', 'dashboards/director-ict.php', '{"ict": true, "systems": true, "infrastructure": true, "can_manage_system": true}'),
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
('School Bursar', 'Financial operations and fee management', 'Administrative', 'dashboards/school-bursar.php', '{"financial": true, "fees": true, "collections": true, "can_manage_fees": true}'),
('School Secretary', 'Administrative support and documentation', 'Administrative', 'dashboards/school-secretary.php', '{"administrative": true, "documentation": true, "can_manage_documents": true}'),
('School Librarian', 'Library and resource management', 'Support', 'dashboards/school-librarian.php', '{"library": true, "resources": true, "can_manage_library": true}'),
('Lecturers', 'Teaching and academic instruction', 'Academic', 'dashboards/lecturers.php', '{"teaching": true, "grading": true, "attendance": true, "can_manage_grades": true}'),
('Senior Lecturers', 'Senior teaching and curriculum development', 'Academic', 'dashboards/senior-lecturers.php', '{"teaching": true, "grading": true, "curriculum": true, "can_develop_curriculum": true}'),
('Head Nursing', 'Nursing program leadership', 'Academic', 'dashboards/head-nursing.php', '{"nursing": true, "teaching": true, "clinical": true, "can_manage_nursing": true}'),
('Head Midwifery', 'Midwifery program leadership', 'Academic', 'dashboards/head-midwifery.php', '{"midwifery": true, "teaching": true, "clinical": true, "can_manage_midwifery": true}'),
('Academic Registrar', 'Academic records and registration', 'Administrative', 'dashboards/academic-registrar.php', '{"academic": true, "registration": true, "records": true, "can_manage_registration": true, "can_generate_transcripts": true, "can_generate_results": true}'),
('Deputy Principal', 'Assistant to school principal', 'Management', 'dashboards/deputy-principal.php', '{"academic": true, "administrative": true, "can_assist_principal": true}'),
('Bursar', 'Financial assistant', 'Administrative', 'dashboards/bursar.php', '{"financial": true, "fees": true, "can_assist_bursar": true}'),
('Secretary', 'Administrative assistant', 'Administrative', 'dashboards/secretary.php', '{"administrative": true, "documentation": true, "can_assist_secretary": true}'),
('Matrons', 'Student welfare and accommodation', 'Support', 'dashboards/matrons.php', '{"student_welfare": true, "accommodation": true, "can_manage_welfare": true}'),
('Wardens', 'Student supervision and discipline', 'Support', 'dashboards/wardens.php', '{"student_supervision": true, "discipline": true, "can_manage_discipline": true}'),
('Lab Technicians', 'Laboratory management and support', 'Support', 'dashboards/lab-technicians.php', '{"laboratory": true, "equipment": true, "can_manage_lab": true}'),
('Drivers', 'Transportation services', 'Support', 'dashboards/drivers.php', '{"transport": true, "logistics": true, "can_manage_transport": true}'),
('Security', 'Campus security and safety', 'Support', 'dashboards/security.php', '{"security": true, "safety": true, "can_manage_security": true}'),
('Non-Teaching Staff', 'General support staff', 'Support', 'dashboards/non-teaching-staff.php', '{"general": true, "can_assist_general": true}');

-- Insert developer/administrator account for system setup and testing
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
    'isnm@administration.ac',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Password: 12345678 (hashed)
    'System Administrator',
    'Executive Office',
    (SELECT id FROM staff_roles WHERE role_name = 'Director General' LIMIT 1),
    'Active',
    CURDATE(),
    FALSE,
    FALSE,
    NOW()
) ON DUPLICATE KEY UPDATE 
    email = VALUES(email),
    password = VALUES(password),
    updated_at = NOW();

-- Insert sample staff accounts for testing (all with same password: 12345678)
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, created_by, created_at) VALUES
('PRINC001', 'Dr. Sarah Johnson', 'principal@isnm.edu.ug', '$2y$10$abcdefghijklmnopqrstuvwx', 'School Principal', 'Academic Affairs', 2, 'Active', DATE_SUB(CURDATE(), INTERVAL 2 YEAR), 1, NOW()),
('HR001', 'Mr. Michael Brown', 'hr@isnm.edu.ug', '$2y$10$abcdefghijklmnopqrstuvwx', 'HR Manager', 'Human Resources', 3, 'Active', DATE_SUB(CURDATE(), INTERVAL 1 YEAR), 1, NOW()),
('BUR001', 'Ms. Grace Nakato', 'bursar@isnm.edu.ug', '$2y$10$abcdefghijklmnopqrstuvwx', 'School Bursar', 'Finance', 4, 'Active', DATE_SUB(CURDATE(), INTERVAL 6 MONTH), 1, NOW()),
('ACAD001', 'Prof. David Mugisha', 'academic@isnm.edu.ug', '$2y$10$abcdefghijklmnopqrstuvwx', 'Academic Registrar', 'Academic Affairs', 2, 'Active', DATE_SUB(CURDATE(), INTERVAL 3 YEAR), 1, NOW());
('LIB001', 'Librarian', 'librarian@isnm.ug', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'School Librarian', 'Library Services', (SELECT id FROM staff_roles WHERE role_name = 'School Librarian' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE 
    email = VALUES(email),
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

-- End of Final Complete Staffs Database Schema
