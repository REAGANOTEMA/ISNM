-- =====================================================
-- ISNM SCHOOL MANAGEMENT SYSTEM - REPORTING SYSTEM
-- Database: isnm_db
-- Supports all reporting operations: academic, financial, administrative, etc.
-- =====================================================

USE isnm_db;

-- Drop existing tables if they exist to ensure clean setup
DROP TABLE IF EXISTS report_templates;
DROP TABLE IF EXISTS generated_reports;
DROP TABLE IF EXISTS report_schedules;
DROP TABLE IF EXISTS dashboard_widgets;
DROP TABLE IF EXISTS analytics_data;
DROP TABLE IF EXISTS report_parameters;
DROP TABLE IF EXISTS report_access_logs;
DROP TABLE IF EXISTS report_subscriptions;
DROP TABLE IF EXISTS report_categories;
DROP TABLE IF EXISTS kpi_metrics;
DROP TABLE IF EXISTS kpi_data;
DROP TABLE IF EXISTS data_visualizations;
DROP TABLE IF EXISTS export_queues;
DROP TABLE IF EXISTS report_comments;
DROP TABLE IF EXISTS report_approvals;
DROP TABLE IF EXISTS custom_queries;
DROP TABLE IF EXISTS report_data_cache;

-- =====================================================
-- 1. REPORT CATEGORIES
-- =====================================================
CREATE TABLE report_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_code VARCHAR(20) NOT NULL UNIQUE,
    category_name VARCHAR(255) NOT NULL,
    category_description TEXT NULL,
    parent_category_id INT NULL,
    icon VARCHAR(50) NULL,
    color VARCHAR(20) DEFAULT '#007bff',
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (category_code),
    INDEX idx_parent (parent_category_id),
    INDEX idx_active (is_active),
    INDEX idx_sort (sort_order),
    FOREIGN KEY (parent_category_id) REFERENCES report_categories(id) ON DELETE SET NULL
);

-- =====================================================
-- 2. REPORT TEMPLATES
-- =====================================================
CREATE TABLE report_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_code VARCHAR(50) NOT NULL UNIQUE,
    template_name VARCHAR(255) NOT NULL,
    template_description TEXT NULL,
    category_id INT NOT NULL,
    report_type ENUM('academic', 'financial', 'administrative', 'student', 'staff', 'attendance', 'examination', 'library', 'hostel', 'custom') NOT NULL,
    sql_query TEXT NOT NULL,
    parameters JSON NULL, -- Report parameters definition
    columns_config JSON NULL, -- Column configuration (names, types, formatting)
    filters_config JSON NULL, -- Available filters
    chart_config JSON NULL, -- Chart configuration
    export_formats JSON NULL, -- Available export formats
    is_system_template BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    requires_approval BOOLEAN DEFAULT FALSE,
    created_by INT NOT NULL,
    updated_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (template_code),
    INDEX idx_category (category_id),
    INDEX idx_type (report_type),
    INDEX idx_active (is_active),
    INDEX idx_system (is_system_template),
    INDEX idx_created_by (created_by),
    FOREIGN KEY (category_id) REFERENCES report_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- 3. REPORT PARAMETERS
-- =====================================================
CREATE TABLE report_parameters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    parameter_name VARCHAR(100) NOT NULL,
    parameter_label VARCHAR(255) NOT NULL,
    parameter_type ENUM('text', 'number', 'date', 'daterange', 'select', 'multiselect', 'checkbox', 'radio') NOT NULL,
    default_value TEXT NULL,
    validation_rules JSON NULL, -- Validation rules for the parameter
    options_data JSON NULL, -- For select/multiselect types
    is_required BOOLEAN DEFAULT FALSE,
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    
    UNIQUE KEY unique_template_parameter (template_id, parameter_name),
    INDEX idx_template (template_id),
    INDEX idx_type (parameter_type),
    INDEX idx_required (is_required),
    INDEX idx_order (display_order),
    FOREIGN KEY (template_id) REFERENCES report_templates(id) ON DELETE CASCADE
);

-- =====================================================
-- 4. GENERATED REPORTS
-- =====================================================
CREATE TABLE generated_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_code VARCHAR(50) NOT NULL UNIQUE,
    template_id INT NOT NULL,
    report_name VARCHAR(255) NOT NULL,
    report_description TEXT NULL,
    parameters_used JSON NULL, -- Actual parameter values used
    generated_by INT NOT NULL,
    generation_status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    data_rows INT DEFAULT 0,
    file_path VARCHAR(500) NULL,
    file_size INT NULL,
    file_format ENUM('PDF', 'Excel', 'CSV', 'Word', 'JSON', 'HTML') DEFAULT 'PDF',
    report_period_start DATE NULL,
    report_period_end DATE NULL,
    execution_time INT NULL, -- in milliseconds
    error_message TEXT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    expires_at TIMESTAMP NULL,
    download_count INT DEFAULT 0,
    view_count INT DEFAULT 0,
    generated_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (report_code),
    INDEX idx_template (template_id),
    INDEX idx_generated_by (generated_by),
    INDEX idx_status (generation_status),
    INDEX idx_period (report_period_start, report_period_end),
    INDEX idx_public (is_public),
    INDEX idx_expires (expires_at),
    INDEX idx_generated (generated_at),
    FOREIGN KEY (template_id) REFERENCES report_templates(id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 5. REPORT SCHEDULES
-- =====================================================
CREATE TABLE report_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    schedule_name VARCHAR(255) NOT NULL,
    template_id INT NOT NULL,
    schedule_type ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom') NOT NULL,
    schedule_config JSON NOT NULL, -- Schedule configuration (time, days, etc.)
    parameters JSON NULL, -- Default parameters for scheduled reports
    recipients JSON NULL, -- List of recipients (users, emails, etc.)
    delivery_methods JSON NULL, -- Delivery methods (email, download, etc.)
    export_format ENUM('PDF', 'Excel', 'CSV', 'Word') DEFAULT 'PDF',
    is_active BOOLEAN DEFAULT TRUE,
    last_run_at TIMESTAMP NULL,
    next_run_at TIMESTAMP NULL,
    run_count INT DEFAULT 0,
    success_count INT DEFAULT 0,
    failure_count INT DEFAULT 0,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_template (template_id),
    INDEX idx_type (schedule_type),
    INDEX idx_active (is_active),
    INDEX idx_next_run (next_run_at),
    INDEX idx_last_run (last_run_at),
    INDEX idx_created_by (created_by),
    FOREIGN KEY (template_id) REFERENCES report_templates(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 6. DASHBOARD WIDGETS
-- =====================================================
CREATE TABLE dashboard_widgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    widget_name VARCHAR(255) NOT NULL,
    widget_type ENUM('chart', 'table', 'metric', 'gauge', 'progress', 'list', 'calendar', 'map') NOT NULL,
    widget_category ENUM('academic', 'financial', 'administrative', 'student', 'staff', 'system') NOT NULL,
    data_source VARCHAR(255) NOT NULL, -- SQL query or API endpoint
    config JSON NOT NULL, -- Widget configuration
    refresh_interval INT DEFAULT 300, -- in seconds
    is_system_widget BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    default_roles JSON NULL, -- Roles that can see this widget by default
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_name (widget_name),
    INDEX idx_type (widget_type),
    INDEX idx_category (widget_category),
    INDEX idx_active (is_active),
    INDEX idx_system (is_system_widget),
    INDEX idx_created_by (created_by),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 7. ANALYTICS DATA
-- =====================================================
CREATE TABLE analytics_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metric_name VARCHAR(255) NOT NULL,
    metric_category ENUM('academic', 'financial', 'administrative', 'engagement', 'performance') NOT NULL,
    metric_value DECIMAL(15,2) NOT NULL,
    metric_unit VARCHAR(50) NULL, -- 'students', 'currency', 'percentage', etc.
    period_type ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly') NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    dimensions JSON NULL, -- Additional dimensions (department, program, etc.)
    data_source VARCHAR(255) NULL,
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_metric_period (metric_name, period_type, period_start, period_end),
    INDEX idx_metric (metric_name),
    INDEX idx_category (metric_category),
    INDEX idx_period (period_type, period_start, period_end),
    INDEX idx_calculated (calculated_at)
);

-- =====================================================
-- 8. KPI METRICS
-- =====================================================
CREATE TABLE kpi_metrics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metric_code VARCHAR(50) NOT NULL UNIQUE,
    metric_name VARCHAR(255) NOT NULL,
    metric_description TEXT NULL,
    metric_category ENUM('academic', 'financial', 'administrative', 'operational', 'quality') NOT NULL,
    calculation_formula TEXT NULL, -- SQL formula for calculation
    target_value DECIMAL(15,2) NULL,
    threshold_min DECIMAL(15,2) NULL,
    threshold_max DECIMAL(15,2) NULL,
    metric_unit VARCHAR(50) NULL,
    aggregation_type ENUM('sum', 'average', 'count', 'min', 'max', 'percentage') NOT NULL,
    period_type ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (metric_code),
    INDEX idx_category (metric_category),
    INDEX idx_active (is_active),
    INDEX idx_order (display_order)
);

-- =====================================================
-- 9. KPI DATA
-- =====================================================
CREATE TABLE kpi_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metric_id INT NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    actual_value DECIMAL(15,2) NOT NULL,
    target_value DECIMAL(15,2) NULL,
    variance DECIMAL(15,2) GENERATED ALWAYS AS (actual_value - COALESCE(target_value, 0)) STORED,
    variance_percentage DECIMAL(5,2) GENERATED ALWAYS AS (
        CASE 
            WHEN target_value > 0 THEN ((actual_value - target_value) / target_value) * 100
            ELSE 0
        END
    ) STORED,
    status ENUM('excellent', 'good', 'average', 'poor', 'critical') GENERATED ALWAYS AS (
        CASE 
            WHEN target_value IS NOT NULL THEN
                CASE 
                    WHEN variance_percentage >= 10 THEN 'excellent'
                    WHEN variance_percentage >= 0 THEN 'good'
                    WHEN variance_percentage >= -10 THEN 'average'
                    WHEN variance_percentage >= -20 THEN 'poor'
                    ELSE 'critical'
                END
            ELSE 'average'
        END
    ) STORED,
    calculated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_metric_period (metric_id, period_start, period_end),
    INDEX idx_metric (metric_id),
    INDEX idx_period (period_start, period_end),
    INDEX idx_status (status),
    INDEX idx_calculated (calculated_at),
    FOREIGN KEY (metric_id) REFERENCES kpi_metrics(id) ON DELETE CASCADE
);

-- =====================================================
-- 10. DATA VISUALIZATIONS
-- =====================================================
CREATE TABLE data_visualizations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visualization_name VARCHAR(255) NOT NULL,
    visualization_type ENUM('line', 'bar', 'pie', 'donut', 'area', 'scatter', 'heatmap', 'gauge', 'funnel', 'radar') NOT NULL,
    data_source VARCHAR(255) NOT NULL, -- SQL query or API endpoint
    config JSON NOT NULL, -- Visualization configuration
    filters JSON NULL, -- Available filters
    is_public BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_name (visualization_name),
    INDEX idx_type (visualization_type),
    INDEX idx_public (is_public),
    INDEX idx_active (is_active),
    INDEX idx_created_by (created_by),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 11. REPORT ACCESS LOGS
-- =====================================================
CREATE TABLE report_access_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    user_id INT NOT NULL,
    action_type ENUM('view', 'download', 'share', 'print', 'email') NOT NULL,
    access_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    session_id VARCHAR(255) NULL,
    metadata JSON NULL, -- Additional metadata
    
    INDEX idx_report (report_id),
    INDEX idx_user (user_id),
    INDEX idx_action (action_type),
    INDEX idx_access_time (access_time),
    INDEX idx_session (session_id),
    FOREIGN KEY (report_id) REFERENCES generated_reports(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 12. REPORT SUBSCRIPTIONS
-- =====================================================
CREATE TABLE report_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    user_id INT NOT NULL,
    subscription_type ENUM('immediate', 'daily', 'weekly', 'monthly') NOT NULL,
    delivery_method ENUM('email', 'dashboard', 'download') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_report_user (report_id, user_id),
    INDEX idx_report (report_id),
    INDEX idx_user (user_id),
    INDEX idx_type (subscription_type),
    INDEX idx_active (is_active),
    INDEX idx_last_sent (last_sent_at),
    FOREIGN KEY (report_id) REFERENCES generated_reports(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 13. EXPORT QUEUES
-- =====================================================
CREATE TABLE export_queues (
    id INT AUTO_INCREMENT PRIMARY KEY,
    queue_id VARCHAR(100) NOT NULL UNIQUE,
    report_id INT NOT NULL,
    export_type ENUM('PDF', 'Excel', 'CSV', 'Word', 'JSON', 'HTML') NOT NULL,
    export_options JSON NULL, -- Export-specific options
    status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    file_path VARCHAR(500) NULL,
    file_size INT NULL,
    download_url VARCHAR(500) NULL,
    expires_at TIMESTAMP NULL,
    error_message TEXT NULL,
    processing_time INT NULL, -- in milliseconds
    requested_by INT NOT NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    
    INDEX idx_queue_id (queue_id),
    INDEX idx_report (report_id),
    INDEX idx_status (status),
    INDEX idx_type (export_type),
    INDEX idx_requested_by (requested_by),
    INDEX idx_expires (expires_at),
    FOREIGN KEY (report_id) REFERENCES generated_reports(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 14. REPORT COMMENTS
-- =====================================================
CREATE TABLE report_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    user_id INT NOT NULL,
    comment_text TEXT NOT NULL,
    parent_comment_id INT NULL,
    is_edited BOOLEAN DEFAULT FALSE,
    edited_at TIMESTAMP NULL,
    is_deleted BOOLEAN DEFAULT FALSE,
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_report (report_id),
    INDEX idx_user (user_id),
    INDEX idx_parent (parent_comment_id),
    INDEX idx_created (created_at),
    FOREIGN KEY (report_id) REFERENCES generated_reports(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_comment_id) REFERENCES report_comments(id) ON DELETE CASCADE
);

-- =====================================================
-- 15. REPORT APPROVALS
-- =====================================================
CREATE TABLE report_approvals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_id INT NOT NULL,
    approver_id INT NOT NULL,
    approval_status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approval_level INT DEFAULT 1, -- For multi-level approvals
    comments TEXT NULL,
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_report_approver_level (report_id, approver_id, approval_level),
    INDEX idx_report (report_id),
    INDEX idx_approver (approver_id),
    INDEX idx_status (approval_status),
    INDEX idx_level (approval_level),
    FOREIGN KEY (report_id) REFERENCES generated_reports(id) ON DELETE CASCADE,
    FOREIGN KEY (approver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 16. CUSTOM QUERIES
-- =====================================================
CREATE TABLE custom_queries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    query_name VARCHAR(255) NOT NULL,
    query_description TEXT NULL,
    sql_query TEXT NOT NULL,
    query_category ENUM('academic', 'financial', 'administrative', 'student', 'staff', 'custom') NOT NULL,
    parameters JSON NULL, -- Query parameters
    is_public BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    execution_count INT DEFAULT 0,
    last_executed_at TIMESTAMP NULL,
    average_execution_time INT DEFAULT 0, -- in milliseconds
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_name (query_name),
    INDEX idx_category (query_category),
    INDEX idx_public (is_public),
    INDEX idx_active (is_active),
    INDEX idx_created_by (created_by),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 17. REPORT DATA CACHE
-- =====================================================
CREATE TABLE report_data_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cache_key VARCHAR(255) NOT NULL UNIQUE,
    report_id INT NULL,
    query_hash VARCHAR(64) NOT NULL, -- MD5 hash of the query
    cached_data LONGTEXT NOT NULL, -- Serialized JSON data
    cache_size INT NOT NULL, -- Size in bytes
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    access_count INT DEFAULT 0,
    last_accessed_at TIMESTAMP NULL,
    
    INDEX idx_cache_key (cache_key),
    INDEX idx_query_hash (query_hash),
    INDEX idx_report (report_id),
    INDEX idx_expires (expires_at),
    INDEX idx_accessed (last_accessed_at),
    FOREIGN KEY (report_id) REFERENCES generated_reports(id) ON DELETE CASCADE
);

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- Insert default report categories
INSERT INTO report_categories (category_code, category_name, category_description, icon, color, sort_order) VALUES
('ACA', 'Academic Reports', 'Academic performance, enrollment, and examination reports', 'fas fa-graduation-cap', '#28a745', 1),
('FIN', 'Financial Reports', 'Fee collection, expenses, and budget reports', 'fas fa-dollar-sign', '#ffc107', 2),
('ADM', 'Administrative Reports', 'Administrative and operational reports', 'fas fa-cogs', '#6c757d', 3),
('STD', 'Student Reports', 'Student-specific reports and analytics', 'fas fa-user-graduate', '#17a2b8', 4),
('STF', 'Staff Reports', 'Staff performance and HR reports', 'fas fa-users', '#6610f2', 5),
('ATT', 'Attendance Reports', 'Student and staff attendance reports', 'fas fa-calendar-check', '#e83e8c', 6),
('LIB', 'Library Reports', 'Library usage and inventory reports', 'fas fa-book', '#fd7e14', 7),
('SYS', 'System Reports', 'System usage and performance reports', 'fas fa-server', '#20c997', 8);

-- Insert default report templates
INSERT INTO report_templates (template_code, template_name, template_description, category_id, report_type, sql_query, parameters, columns_config, is_system_template, created_by) VALUES
('STUDENT_ENROLL', 'Student Enrollment Report', 'Student enrollment statistics by program and level', 4, 'student', 
'SELECT ap.program_name, sar.current_level, COUNT(*) as total_students, SUM(CASE WHEN u.gender = "Male" THEN 1 ELSE 0 END) as male_students, SUM(CASE WHEN u.gender = "Female" THEN 1 ELSE 0 END) as female_students FROM users u JOIN student_academic_records sar ON u.id = sar.student_id JOIN academic_programs ap ON sar.program_id = ap.id WHERE u.type = "student" AND sar.academic_status = "active" GROUP BY ap.program_name, sar.current_level ORDER BY ap.program_name, sar.current_level',
'{"program_id": {"type": "select", "label": "Program", "required": false}, "level": {"type": "number", "label": "Level", "required": false}}',
'{"program_name": {"label": "Program", "type": "string"}, "current_level": {"label": "Level", "type": "number"}, "total_students": {"label": "Total Students", "type": "number"}, "male_students": {"label": "Male Students", "type": "number"}, "female_students": {"label": "Female Students", "type": "number"}}',
TRUE, 1),

('FEE_COLLECTION', 'Fee Collection Report', 'Fee payment collection statistics', 2, 'financial',
'SELECT ap.program_name, s.session_name, sem.semester_name, COUNT(DISTINCT sfa.student_id) as total_students, SUM(sfa.total_fees) as total_fees, SUM(sfa.amount_paid) as total_collected, SUM(sfa.balance_due) as total_outstanding, ROUND((SUM(sfa.amount_paid) / SUM(sfa.total_fees)) * 100, 2) as collection_rate FROM student_fee_accounts sfa JOIN academic_programs ap ON sfa.program_id = ap.id JOIN academic_sessions s ON sfa.session_id = s.id JOIN academic_semesters sem ON sfa.semester_id = sem.id GROUP BY ap.program_name, s.session_name, sem.semester_name ORDER BY s.session_name DESC, sem.semester_name DESC',
'{"session_id": {"type": "select", "label": "Academic Session", "required": false}, "program_id": {"type": "select", "label": "Program", "required": false}}',
'{"program_name": {"label": "Program", "type": "string"}, "session_name": {"label": "Session", "type": "string"}, "semester_name": {"label": "Semester", "type": "string"}, "total_students": {"label": "Total Students", "type": "number"}, "total_fees": {"label": "Total Fees", "type": "currency"}, "total_collected": {"label": "Total Collected", "type": "currency"}, "total_outstanding": {"label": "Total Outstanding", "type": "currency"}, "collection_rate": {"label": "Collection Rate (%)", "type": "percentage"}}',
TRUE, 1),

('EXAM_RESULTS', 'Examination Results Report', 'Student examination results analysis', 1, 'academic',
'SELECT ac.course_code, ac.course_title, e.exam_title, e.exam_type, COUNT(DISTINCT er.student_id) as total_students, COUNT(CASE WHEN er.marks_obtained >= e.passing_marks THEN 1 END) as passed_students, COUNT(CASE WHEN er.marks_obtained < e.passing_marks THEN 1 END) as failed_students, ROUND(AVG(er.marks_obtained), 2) as average_marks, MAX(er.marks_obtained) as highest_marks, MIN(er.marks_obtained) as lowest_marks, ROUND((COUNT(CASE WHEN er.marks_obtained >= e.passing_marks THEN 1 END) * 100.0 / COUNT(*)), 2) as pass_rate FROM examinations e JOIN academic_courses ac ON e.course_id = ac.id LEFT JOIN exam_results er ON e.id = er.exam_id GROUP BY e.id, ac.course_code, ac.course_title, e.exam_title, e.exam_type ORDER BY e.exam_date DESC',
'{"session_id": {"type": "select", "label": "Academic Session", "required": false}, "course_id": {"type": "select", "label": "Course", "required": false}, "exam_type": {"type": "select", "label": "Exam Type", "required": false}}',
'{"course_code": {"label": "Course Code", "type": "string"}, "course_title": {"label": "Course Title", "type": "string"}, "exam_title": {"label": "Exam Title", "type": "string"}, "exam_type": {"label": "Exam Type", "type": "string"}, "total_students": {"label": "Total Students", "type": "number"}, "passed_students": {"label": "Passed Students", "type": "number"}, "failed_students": {"label": "Failed Students", "type": "number"}, "average_marks": {"label": "Average Marks", "type": "number"}, "highest_marks": {"label": "Highest Marks", "type": "number"}, "lowest_marks": {"label": "Lowest Marks", "type": "number"}, "pass_rate": {"label": "Pass Rate (%)", "type": "percentage"}}',
TRUE, 1),

('ATTENDANCE_SUMMARY', 'Attendance Summary Report', 'Student attendance statistics by course', 6, 'attendance',
'SELECT ac.course_code, ac.course_title, COUNT(DISTINCT ats.id) as total_sessions, COUNT(DISTINCT ar.student_id) as total_students, COUNT(CASE WHEN ar.attendance_status = "present" THEN 1 END) as total_present, COUNT(CASE WHEN ar.attendance_status = "absent" THEN 1 END) as total_absent, ROUND((COUNT(CASE WHEN ar.attendance_status = "present" THEN 1 END) * 100.0 / (COUNT(CASE WHEN ar.attendance_status = "present" THEN 1 END) + COUNT(CASE WHEN ar.attendance_status = "absent" THEN 1 END))), 2) as attendance_percentage FROM academic_courses ac LEFT JOIN attendance_sessions ats ON ac.id = ats.course_id LEFT JOIN attendance_records ar ON ats.id = ar.attendance_session_id GROUP BY ac.id, ac.course_code, ac.course_title ORDER BY ac.course_code',
'{"session_id": {"type": "select", "label": "Academic Session", "required": false}, "course_id": {"type": "select", "label": "Course", "required": false}}',
'{"course_code": {"label": "Course Code", "type": "string"}, "course_title": {"label": "Course Title", "type": "string"}, "total_sessions": {"label": "Total Sessions", "type": "number"}, "total_students": {"label": "Total Students", "type": "number"}, "total_present": {"label": "Total Present", "type": "number"}, "total_absent": {"label": "Total Absent", "type": "number"}, "attendance_percentage": {"label": "Attendance %", "type": "percentage"}}',
TRUE, 1);

-- Insert default KPI metrics
INSERT INTO kpi_metrics (metric_code, metric_name, metric_description, metric_category, target_value, threshold_min, threshold_max, metric_unit, aggregation_type, period_type) VALUES
('TOTAL_STUDENTS', 'Total Students', 'Total number of active students', 'academic', 500, 400, 600, 'students', 'count', 'monthly'),
('TOTAL_STAFF', 'Total Staff', 'Total number of active staff members', 'administrative', 100, 80, 120, 'staff', 'count', 'monthly'),
('COLLECTION_RATE', 'Fee Collection Rate', 'Percentage of fees collected', 'financial', 95.00, 85.00, 100.00, 'percentage', 'average', 'monthly'),
('ATTENDANCE_RATE', 'Student Attendance Rate', 'Average student attendance percentage', 'academic', 90.00, 75.00, 95.00, 'percentage', 'average', 'monthly'),
('PASS_RATE', 'Examination Pass Rate', 'Percentage of students passing examinations', 'academic', 85.00, 70.00, 95.00, 'percentage', 'average', 'monthly'),
('GRADUATION_RATE', 'Graduation Rate', 'Percentage of students graduating on time', 'academic', 90.00, 80.00, 95.00, 'percentage', 'average', 'yearly');

-- Insert default dashboard widgets
INSERT INTO dashboard_widgets (widget_name, widget_type, widget_category, data_source, config, refresh_interval, is_system_widget, created_by) VALUES
('Student Enrollment', 'metric', 'academic', 'SELECT COUNT(*) as value FROM users WHERE type = "student" AND status = "active"', '{"title": "Total Students", "icon": "fas fa-user-graduate", "color": "#28a745", "format": "number"}', 300, TRUE, 1),
('Staff Count', 'metric', 'administrative', 'SELECT COUNT(*) as value FROM users WHERE type = "staff" AND status = "active"', '{"title": "Total Staff", "icon": "fas fa-users", "color": "#007bff", "format": "number"}', 300, TRUE, 1),
('Fee Collection', 'metric', 'financial', 'SELECT ROUND((SUM(amount_paid) / SUM(total_fees)) * 100, 2) as value FROM student_fee_accounts WHERE session_id = (SELECT id FROM academic_sessions WHERE is_current = TRUE)', '{"title": "Collection Rate %", "icon": "fas fa-dollar-sign", "color": "#ffc107", "format": "percentage"}', 300, TRUE, 1),
('Recent Exams', 'list', 'academic', 'SELECT e.exam_title, ac.course_code, e.exam_date FROM examinations e JOIN academic_courses ac ON e.course_id = ac.id ORDER BY e.exam_date DESC LIMIT 5', '{"title": "Recent Examinations", "icon": "fas fa-clipboard-list", "columns": ["exam_title", "course_code", "exam_date"]}', 600, TRUE, 1),
('Attendance Overview', 'gauge', 'academic', 'SELECT ROUND(AVG(attendance_percentage), 2) as value FROM attendance_statistics', '{"title": "Average Attendance", "icon": "fas fa-calendar-check", "color": "#17a2b8", "max": 100, "thresholds": [{"value": 90, "color": "green"}, {"value": 75, "color": "yellow"}, {"value": 0, "color": "red"}]}', 300, TRUE, 1);

-- =====================================================
-- CREATE STORED PROCEDURES FOR REPORTING OPERATIONS
-- =====================================================

DELIMITER //

-- Procedure to generate report
CREATE PROCEDURE generate_report(
    IN p_template_id INT,
    IN p_report_name VARCHAR(255),
    IN p_parameters JSON,
    IN p_generated_by INT,
    IN p_export_format ENUM('PDF', 'Excel', 'CSV', 'Word', 'JSON', 'HTML')
)
BEGIN
    DECLARE v_report_code VARCHAR(50);
    DECLARE v_sql_query TEXT;
    DECLARE v_start_time BIGINT;
    DECLARE v_end_time BIGINT;
    DECLARE v_execution_time INT;
    DECLARE v_report_id INT;
    DECLARE v_data_rows INT;
    
    -- Get template SQL query
    SELECT sql_query INTO v_sql_query
    FROM report_templates
    WHERE id = p_template_id;
    
    -- Generate report code
    SET v_report_code = CONCAT('RPT', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'), LPAD(p_generated_by, 4, '0'));
    
    -- Record start time
    SET v_start_time = UNIX_TIMESTAMP(NOW(3)) * 1000 + MICROSECOND(NOW(3)) / 1000;
    
    -- Create report record
    INSERT INTO generated_reports (
        report_code, template_id, report_name, parameters_used, generated_by,
        file_format, generation_status, created_at
    ) VALUES (
        v_report_code, p_template_id, p_report_name, p_parameters, p_generated_by,
        p_export_format, 'processing', CURRENT_TIMESTAMP
    );
    
    SET v_report_id = LAST_INSERT_ID();
    
    -- Execute the query and count rows (simplified for this example)
    -- In a real implementation, you would execute the dynamic SQL and store results
    SET v_data_rows = 100; -- Placeholder
    
    -- Record end time and calculate execution time
    SET v_end_time = UNIX_TIMESTAMP(NOW(3)) * 1000 + MICROSECOND(NOW(3)) / 1000;
    SET v_execution_time = v_end_time - v_start_time;
    
    -- Update report record
    UPDATE generated_reports 
    SET data_rows = v_data_rows,
        execution_time = v_execution_time,
        generation_status = 'completed',
        generated_at = CURRENT_TIMESTAMP
    WHERE id = v_report_id;
    
    -- Log report generation
    INSERT INTO report_access_logs (
        report_id, user_id, action_type, access_time
    ) VALUES (
        v_report_id, p_generated_by, 'view', CURRENT_TIMESTAMP
    );
    
    SELECT v_report_id as report_id, v_report_code as report_code;
END //

-- Procedure to calculate KPI data
CREATE PROCEDURE calculate_kpi_data(
    IN p_metric_id INT,
    IN p_period_start DATE,
    IN p_period_end DATE
)
BEGIN
    DECLARE v_metric_code VARCHAR(50);
    DECLARE v_calculation_formula TEXT;
    DECLARE v_actual_value DECIMAL(15,2);
    DECLARE v_target_value DECIMAL(15,2);
    
    -- Get metric details
    SELECT metric_code, calculation_formula, target_value
    INTO v_metric_code, v_calculation_formula, v_target_value
    FROM kpi_metrics
    WHERE id = p_metric_id;
    
    -- Calculate actual value based on metric type
    CASE v_metric_code
        WHEN 'TOTAL_STUDENTS' THEN
            SELECT COUNT(*) INTO v_actual_value
            FROM users 
            WHERE type = 'student' 
            AND status = 'active';
            
        WHEN 'TOTAL_STAFF' THEN
            SELECT COUNT(*) INTO v_actual_value
            FROM users 
            WHERE type = 'staff' 
            AND status = 'active';
            
        WHEN 'COLLECTION_RATE' THEN
            SELECT ROUND((SUM(amount_paid) / SUM(total_fees)) * 100, 2) INTO v_actual_value
            FROM student_fee_accounts 
            WHERE session_id = (SELECT id FROM academic_sessions WHERE is_current = TRUE);
            
        WHEN 'ATTENDANCE_RATE' THEN
            SELECT ROUND(AVG(attendance_percentage), 2) INTO v_actual_value
            FROM attendance_statistics;
            
        WHEN 'PASS_RATE' THEN
            SELECT ROUND(AVG(pass_rate), 2) INTO v_actual_value
            FROM examination_results_summary;
            
        ELSE
            SET v_actual_value = 0;
    END CASE;
    
    -- Insert KPI data
    INSERT INTO kpi_data (
        metric_id, period_start, period_end, actual_value, target_value
    ) VALUES (
        p_metric_id, p_period_start, p_period_end, v_actual_value, v_target_value
    ) ON DUPLICATE KEY UPDATE
        actual_value = VALUES(actual_value),
        target_value = VALUES(target_value),
        calculated_at = CURRENT_TIMESTAMP;
    
    SELECT v_actual_value as actual_value, v_target_value as target_value;
END //

-- Procedure to schedule report
CREATE PROCEDURE schedule_report(
    IN p_schedule_name VARCHAR(255),
    IN p_template_id INT,
    IN p_schedule_type ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom'),
    IN p_schedule_config JSON,
    IN p_parameters JSON,
    IN p_recipients JSON,
    IN p_delivery_methods JSON,
    IN p_created_by INT
)
BEGIN
    DECLARE v_schedule_id INT;
    DECLARE v_next_run TIMESTAMP;
    
    -- Calculate next run time based on schedule type
    CASE p_schedule_type
        WHEN 'daily' THEN
            SET v_next_run = DATE_ADD(CURDATE(), INTERVAL 1 DAY);
        WHEN 'weekly' THEN
            SET v_next_run = DATE_ADD(CURDATE(), INTERVAL 1 WEEK);
        WHEN 'monthly' THEN
            SET v_next_run = DATE_ADD(CURDATE(), INTERVAL 1 MONTH);
        WHEN 'quarterly' THEN
            SET v_next_run = DATE_ADD(CURDATE(), INTERVAL 3 MONTH);
        WHEN 'yearly' THEN
            SET v_next_run = DATE_ADD(CURDATE(), INTERVAL 1 YEAR);
        ELSE
            SET v_next_run = JSON_UNQUOTE(JSON_EXTRACT(p_schedule_config, '$.next_run'));
    END CASE;
    
    -- Create schedule
    INSERT INTO report_schedules (
        schedule_name, template_id, schedule_type, schedule_config, parameters,
        recipients, delivery_methods, next_run_at, created_by
    ) VALUES (
        p_schedule_name, p_template_id, p_schedule_type, p_schedule_config, p_parameters,
        p_recipients, p_delivery_methods, v_next_run, p_created_by
    );
    
    SET v_schedule_id = LAST_INSERT_ID();
    
    SELECT v_schedule_id as schedule_id, v_next_run as next_run_at;
END //

-- Procedure to get dashboard data
CREATE PROCEDURE get_dashboard_data(IN p_user_id INT, IN p_dashboard_type VARCHAR(50))
BEGIN
    -- Get user role
    DECLARE v_user_role VARCHAR(100);
    SELECT role INTO v_user_role FROM users WHERE id = p_user_id;
    
    -- Return relevant widgets based on user role and dashboard type
    SELECT 
        dw.id as widget_id,
        dw.widget_name,
        dw.widget_type,
        dw.widget_category,
        dw.data_source,
        dw.config,
        dw.refresh_interval
    FROM dashboard_widgets dw
    WHERE dw.is_active = TRUE
    AND (dw.is_system_widget = TRUE OR dw.created_by = p_user_id)
    AND (dw.default_roles IS NULL OR JSON_CONTAINS(dw.default_roles, JSON_QUOTE(v_user_role)))
    ORDER BY dw.widget_name;
END //

-- Procedure to cache report data
CREATE PROCEDURE cache_report_data(
    IN p_report_id INT,
    IN p_query_hash VARCHAR(64),
    IN p_data LONGTEXT,
    IN p_cache_duration INT -- in minutes
)
BEGIN
    DECLARE v_cache_key VARCHAR(255);
    DECLARE v_expires_at TIMESTAMP;
    
    -- Generate cache key
    SET v_cache_key = CONCAT('report_', p_report_id, '_', p_query_hash);
    SET v_expires_at = DATE_ADD(NOW(), INTERVAL p_cache_duration MINUTE);
    
    -- Insert cache data
    INSERT INTO report_data_cache (
        cache_key, report_id, query_hash, cached_data, cache_size, expires_at
    ) VALUES (
        v_cache_key, p_report_id, p_query_hash, p_data, LENGTH(p_data), v_expires_at
    ) ON DUPLICATE KEY UPDATE
        cached_data = VALUES(cached_data),
        cache_size = VALUES(cache_size),
        expires_at = VALUES(expires_at),
        access_count = 0,
        last_accessed_at = NULL;
    
    SELECT v_cache_key as cache_key;
END //

-- Procedure to get report statistics
CREATE PROCEDURE get_report_statistics(IN p_period_start DATE, IN p_period_end DATE)
BEGIN
    -- Report generation statistics
    SELECT 
        'Generated Reports' as metric,
        COUNT(*) as total_count,
        COUNT(CASE WHEN generation_status = 'completed' THEN 1 END) as successful,
        COUNT(CASE WHEN generation_status = 'failed' THEN 1 END) as failed,
        ROUND(AVG(execution_time), 2) as avg_execution_time,
        SUM(data_rows) as total_data_rows
    FROM generated_reports 
    WHERE DATE(created_at) BETWEEN p_period_start AND p_period_end;
    
    -- Report access statistics
    SELECT 
        'Report Access' as metric,
        COUNT(*) as total_access,
        COUNT(DISTINCT user_id) as unique_users,
        COUNT(CASE WHEN action_type = 'download' THEN 1 END) as downloads,
        COUNT(CASE WHEN action_type = 'view' THEN 1 END) as views
    FROM report_access_logs 
    WHERE DATE(access_time) BETWEEN p_period_start AND p_period_end;
    
    -- Popular reports
    SELECT 
        rt.template_name,
        COUNT(gr.id) as generation_count,
        COUNT(DISTINCT gr.generated_by) as unique_users,
        AVG(gr.execution_time) as avg_execution_time
    FROM generated_reports gr
    JOIN report_templates rt ON gr.template_id = rt.id
    WHERE DATE(gr.created_at) BETWEEN p_period_start AND p_period_end
    GROUP BY rt.id, rt.template_name
    ORDER BY generation_count DESC
    LIMIT 10;
END //

DELIMITER ;

-- =====================================================
-- CREATE VIEWS FOR REPORTING OPERATIONS
-- =====================================================

-- View for report template summary
CREATE VIEW report_template_summary AS
SELECT 
    rt.id as template_id,
    rt.template_code,
    rt.template_name,
    rt.report_type,
    rc.category_name,
    rt.is_system_template,
    rt.is_active,
    COUNT(gr.id) as usage_count,
    COUNT(DISTINCT gr.generated_by) as unique_users,
    AVG(gr.execution_time) as avg_execution_time,
    MAX(gr.created_at) as last_used
FROM report_templates rt
JOIN report_categories rc ON rt.category_id = rc.id
LEFT JOIN generated_reports gr ON rt.id = gr.template_id
GROUP BY rt.id, rt.template_code, rt.template_name, rt.report_type, rc.category_name, rt.is_system_template, rt.is_active;

-- View for KPI performance summary
CREATE VIEW kpi_performance_summary AS
SELECT 
    km.metric_code,
    km.metric_name,
    km.metric_category,
    km.target_value,
    kd.actual_value,
    kd.variance,
    kd.variance_percentage,
    kd.status,
    kd.period_start,
    kd.period_end,
    CASE 
        WHEN kd.status = 'excellent' THEN '#28a745'
        WHEN kd.status = 'good' THEN '#17a2b8'
        WHEN kd.status = 'average' THEN '#ffc107'
        WHEN kd.status = 'poor' THEN '#fd7e14'
        ELSE '#dc3545'
    END as status_color
FROM kpi_metrics km
JOIN kpi_data kd ON km.id = kd.metric_id
WHERE kd.period_end = (
    SELECT MAX(period_end) 
    FROM kpi_data 
    WHERE metric_id = km.id
);

-- View for dashboard widget performance
CREATE VIEW dashboard_widget_performance AS
SELECT 
    dw.id as widget_id,
    dw.widget_name,
    dw.widget_type,
    dw.widget_category,
    dw.refresh_interval,
    COUNT(DISTINCT CASE WHEN dw.is_system_widget = TRUE THEN 1 END) as is_system,
    COUNT(DISTINCT CASE WHEN dw.is_system_widget = FALSE THEN dw.created_by END) as custom_count,
    dw.is_active
FROM dashboard_widgets dw
GROUP BY dw.id, dw.widget_name, dw.widget_type, dw.widget_category, dw.refresh_interval, dw.is_active;

-- View for report usage trends
CREATE VIEW report_usage_trends AS
SELECT 
    DATE(gr.created_at) as report_date,
        rt.report_type,
    COUNT(*) as reports_generated,
    COUNT(DISTINCT gr.generated_by) as unique_users,
    AVG(gr.execution_time) as avg_execution_time,
    SUM(gr.data_rows) as total_data_rows
FROM generated_reports gr
JOIN report_templates rt ON gr.template_id = rt.id
WHERE gr.generation_status = 'completed'
GROUP BY DATE(gr.created_at), rt.report_type
ORDER BY report_date DESC;

-- =====================================================
-- TRIGGERS FOR AUTOMATIC REPORTING OPERATIONS
-- =====================================================

DELIMITER //

-- Trigger to log report access
CREATE TRIGGER after_report_access_insert
AFTER INSERT ON report_access_logs
FOR EACH ROW
BEGIN
    -- Update report download/view count
    IF NEW.action_type = 'download' THEN
        UPDATE generated_reports 
        SET download_count = download_count + 1
        WHERE id = NEW.report_id;
    ELSEIF NEW.action_type = 'view' THEN
        UPDATE generated_reports 
        SET view_count = view_count + 1
        WHERE id = NEW.report_id;
    END IF;
    
    -- Update cache access count
    UPDATE report_data_cache 
    SET access_count = access_count + 1,
        last_accessed_at = CURRENT_TIMESTAMP
    WHERE report_id = NEW.report_id;
END //

-- Trigger to update report statistics
CREATE TRIGGER after_generated_report_insert
AFTER INSERT ON generated_reports
FOR EACH ROW
BEGIN
    -- Update template usage count
    UPDATE report_templates 
    SET updated_at = CURRENT_TIMESTAMP
    WHERE id = NEW.template_id;
    
    -- Log to analytics
    INSERT INTO analytics_data (
        metric_name, metric_category, metric_value, metric_unit,
        period_type, period_start, period_end
    ) VALUES (
        'reports_generated', 'administrative', 1, 'count',
        'daily', DATE(NEW.created_at), DATE(NEW.created_at)
    );
END //

-- Trigger to calculate KPI on schedule
CREATE TRIGGER after_kpi_data_insert
AFTER INSERT ON kpi_data
FOR EACH ROW
BEGIN
    -- Update analytics with KPI data
    INSERT INTO analytics_data (
        metric_name, metric_category, metric_value, metric_unit,
        period_type, period_start, period_end
    ) VALUES (
        (SELECT metric_code FROM kpi_metrics WHERE id = NEW.metric_id),
        (SELECT metric_category FROM kpi_metrics WHERE id = NEW.metric_id),
        NEW.actual_value,
        (SELECT metric_unit FROM kpi_metrics WHERE id = NEW.metric_id),
        (SELECT period_type FROM kpi_metrics WHERE id = NEW.metric_id),
        NEW.period_start,
        NEW.period_end
    ) ON DUPLICATE KEY UPDATE
        metric_value = NEW.actual_value,
        calculated_at = CURRENT_TIMESTAMP;
END //

DELIMITER ;

-- =====================================================
-- FINAL SETUP COMPLETE
-- =====================================================

-- Grant necessary permissions for stored procedures
GRANT EXECUTE ON PROCEDURE generate_report TO 'root'@'localhost';
GRANT EXECUTE ON PROCEDURE calculate_kpi_data TO 'root'@'localhost';
GRANT EXECUTE ON PROCEDURE schedule_report TO 'root'@'localhost';
GRANT EXECUTE ON PROCEDURE get_dashboard_data TO 'root'@'localhost';
GRANT EXECUTE ON PROCEDURE cache_report_data TO 'root'@'localhost';
GRANT EXECUTE ON PROCEDURE get_report_statistics TO 'root'@'localhost';

-- =====================================================
-- SETUP COMPLETE MESSAGE
-- =====================================================
SELECT 'ISNM Reporting System Setup Complete!' as status,
       COUNT(*) as total_tables_created
FROM information_schema.tables 
WHERE table_schema = 'isnm_db' 
AND table_name IN ('report_templates', 'generated_reports', 'report_schedules', 'dashboard_widgets', 'analytics_data', 'kpi_metrics', 'kpi_data');
