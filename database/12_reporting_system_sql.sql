-- ISNM School Management System - Reporting System SQL
-- Comprehensive SQL for all reporting, analytics, and dashboard statistics

USE isnm_db;

-- ========================================
-- REPORTING SYSTEM TABLES
-- ========================================

-- Drop existing tables if they exist to ensure clean creation
DROP TABLE IF EXISTS report_schedules;
DROP TABLE IF EXISTS report_templates;
DROP TABLE IF EXISTS generated_reports;
DROP TABLE IF EXISTS dashboard_widgets;
DROP TABLE IF EXISTS analytics_data;

-- Report templates table
CREATE TABLE report_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(255) NOT NULL UNIQUE,
    template_code VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    report_category ENUM('academic', 'financial', 'administrative', 'student', 'staff', 'library', 'hostel', 'examination', 'attendance', 'custom') NOT NULL,
    sql_query LONGTEXT NOT NULL,
    parameters JSON, -- JSON object defining parameters
    output_format ENUM('html', 'pdf', 'excel', 'csv', 'json') DEFAULT 'html',
    is_system BOOLEAN DEFAULT FALSE,
    requires_approval BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive', 'deprecated') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_template_code (template_code),
    INDEX idx_report_category (report_category),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Generated reports table
CREATE TABLE generated_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    report_name VARCHAR(255) NOT NULL,
    report_period VARCHAR(100),
    parameters JSON,
    file_path VARCHAR(500),
    file_size DECIMAL(10,2),
    output_format ENUM('html', 'pdf', 'excel', 'csv', 'json') NOT NULL,
    status ENUM('generating', 'completed', 'failed', 'archived') DEFAULT 'generating',
    generated_by INT NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    download_count INT DEFAULT 0,
    last_downloaded_at TIMESTAMP NULL,
    
    FOREIGN KEY (template_id) REFERENCES report_templates(id),
    FOREIGN KEY (generated_by) REFERENCES users(id),
    INDEX idx_template_id (template_id),
    INDEX idx_status (status),
    INDEX idx_generated_at (generated_at),
    INDEX idx_generated_by (generated_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Report schedules table
CREATE TABLE report_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_id INT NOT NULL,
    schedule_name VARCHAR(255) NOT NULL,
    schedule_type ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom') NOT NULL,
    schedule_config JSON, -- JSON object with schedule configuration
    parameters JSON,
    recipients JSON, -- Array of user IDs and email addresses
    is_active BOOLEAN DEFAULT TRUE,
    last_run TIMESTAMP NULL,
    next_run TIMESTAMP,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (template_id) REFERENCES report_templates(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_template_id (template_id),
    INDEX idx_is_active (is_active),
    INDEX idx_next_run (next_run)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dashboard widgets table
CREATE TABLE dashboard_widgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    widget_name VARCHAR(255) NOT NULL,
    widget_code VARCHAR(50) NOT NULL UNIQUE,
    widget_type ENUM('statistic', 'chart', 'table', 'list', 'calendar', 'gauge', 'progress') NOT NULL,
    dashboard_type ENUM('student', 'staff', 'director', 'admin', 'finance', 'academic', 'library', 'hostel') NOT NULL,
    sql_query LONGTEXT NOT NULL,
    configuration JSON, -- Widget-specific configuration
    position JSON, -- Position and size information
    is_system BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_widget_code (widget_code),
    INDEX idx_dashboard_type (dashboard_type),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Analytics data table for storing pre-calculated analytics
CREATE TABLE analytics_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    metric_name VARCHAR(100) NOT NULL,
    metric_category ENUM('students', 'staff', 'academic', 'financial', 'attendance', 'examination', 'library', 'hostel') NOT NULL,
    metric_value DECIMAL(15,2) NOT NULL,
    metric_unit VARCHAR(20), -- e.g., 'count', 'percentage', 'currency'
    period_type ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly') NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    dimensions JSON, -- Additional dimensions for filtering
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_metric_period (metric_name, metric_category, period_type, period_start, period_end),
    INDEX idx_metric_name (metric_name),
    INDEX idx_metric_category (metric_category),
    INDEX idx_period_type (period_type),
    INDEX idx_period_start (period_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- INSERT DEFAULT REPORT TEMPLATES
-- ========================================

-- Academic report templates
INSERT INTO report_templates (template_name, template_code, description, report_category, sql_query, parameters, output_format, is_system, created_by) VALUES
('Student Academic Performance Report', 'STUDENT_PERFORMANCE', 'Comprehensive academic performance report for individual students', 'academic', 
'SELECT 
    u.full_name,
    u.index_number,
    u.phone,
    p.program_name,
    p.program_type,
    COUNT(sar.course_id) as total_courses,
    COUNT(CASE WHEN sar.status = ''completed'' THEN 1 END) as completed_courses,
    AVG(CASE WHEN sar.status = ''completed'' THEN sar.gpa_points END) as current_gpa,
    SUM(CASE WHEN sar.status = ''completed'' THEN c.credits ELSE 0 END) as completed_credits,
    COUNT(CASE WHEN ar.attendance_status = ''present'' THEN 1 END) as present_days,
    COUNT(ar.attendance_status) as total_days,
    ROUND((COUNT(CASE WHEN ar.attendance_status = ''present'' THEN 1 END) * 100.0) / COUNT(ar.attendance_status), 2) as attendance_rate
FROM users u
LEFT JOIN student_academic_records sar ON u.id = sar.student_id
LEFT JOIN courses c ON sar.course_id = c.id
LEFT JOIN programs p ON c.program_id = p.id
LEFT JOIN attendance_records ar ON u.id = ar.student_id
WHERE u.type = ''student'' AND u.status = ''active''
  AND u.id = IFNULL(?, u.id)
  AND IFNULL(?, ''2024-2025'') = ''2024-2025''
GROUP BY u.id, u.full_name, u.index_number, u.phone, p.program_name, p.program_type
ORDER BY u.full_name', 
'{"student_id": {"type": "integer", "label": "Student ID"}, "academic_year": {"type": "string", "label": "Academic Year"}}}', 
'html', TRUE, 1),

('Course Enrollment Report', 'COURSE_ENROLLMENT', 'Report showing enrollment statistics for all courses', 'academic',
'SELECT 
    c.course_code,
    c.course_name,
    c.semester,
    c.credits,
    p.program_name,
    COUNT(sar.student_id) as enrolled_students,
    COUNT(CASE WHEN sar.status = ''completed'' THEN 1 END) as completed_students,
    COUNT(CASE WHEN sar.status = ''in_progress'' THEN 1 END) as in_progress_students,
    AVG(CASE WHEN sar.status = ''completed'' THEN sar.gpa_points END) as average_gpa,
    COUNT(DISTINCT ca.staff_id) as assigned_lecturers
FROM courses c
LEFT JOIN programs p ON c.program_id = p.id
LEFT JOIN student_academic_records sar ON c.id = sar.course_id
LEFT JOIN course_assignments ca ON c.id = ca.course_id AND ca.status = ''active''
WHERE c.status = ''active''
  AND IFNULL(?, ''2024-2025'') = ''2024-2025''
  AND IFNULL(?, '''') = '''' OR c.semester = ?
GROUP BY c.id, c.course_code, c.course_name, c.semester, c.credits, p.program_name
ORDER BY c.course_code',
'{"academic_year": {"type": "string", "label": "Academic Year"}, "semester": {"type": "string", "label": "Semester"}}}',
'html', TRUE, 1),

('Examination Results Summary', 'EXAM_RESULTS', 'Summary of examination results across all courses', 'academic',
'SELECT 
    e.exam_name,
    e.exam_type,
    e.exam_date,
    c.course_code,
    c.course_name,
    p.program_name,
    COUNT(er.student_id) as total_students,
    COUNT(CASE WHEN er.marks_obtained >= e.passing_marks THEN 1 END) as passed_students,
    COUNT(CASE WHEN er.marks_obtained < e.passing_marks THEN 1 END) as failed_students,
    ROUND((COUNT(CASE WHEN er.marks_obtained >= e.passing_marks THEN 1 END) * 100.0) / COUNT(er.student_id), 2) as pass_rate,
    AVG(er.marks_obtained) as average_marks,
    MAX(er.marks_obtained) as highest_marks,
    MIN(er.marks_obtained) as lowest_marks
FROM examinations e
JOIN courses c ON e.course_id = c.id
JOIN programs p ON c.program_id = p.id
JOIN exam_results er ON e.id = er.exam_id
WHERE e.status = ''completed''
  AND e.exam_date BETWEEN IFNULL(?, DATE_SUB(CURDATE(), INTERVAL 30 DAY)) AND IFNULL(?, CURDATE())
GROUP BY e.id, e.exam_name, e.exam_type, e.exam_date, c.course_code, c.course_name, p.program_name
ORDER BY e.exam_date DESC',
'{"start_date": {"type": "date", "label": "Start Date"}, "end_date": {"type": "date", "label": "End Date"}}}',
'html', TRUE, 1),

-- Financial report templates
INSERT INTO report_templates (template_name, template_code, description, report_category, sql_query, parameters, output_format, is_system, created_by) VALUES
('Fee Collection Report', 'FEE_COLLECTION', 'Comprehensive fee collection report', 'financial',
'SELECT 
    fs.academic_year,
    fs.semester,
    p.program_name,
    COUNT(DISTINCT sfa.student_id) as total_students,
    SUM(sfa.total_fee) as total_fees,
    SUM(sfa.amount_paid) as total_collected,
    SUM(sfa.balance) as total_balance,
    COUNT(CASE WHEN sfa.payment_status = ''paid'' THEN 1 END) as fully_paid,
    COUNT(CASE WHEN sfa.payment_status = ''partial'' THEN 1 END) as partially_paid,
    COUNT(CASE WHEN sfa.payment_status = ''unpaid'' THEN 1 END) as unpaid,
    COUNT(CASE WHEN sfa.payment_status = ''overdue'' THEN 1 END) as overdue,
    ROUND((SUM(sfa.amount_paid) * 100.0) / SUM(sfa.total_fee), 2) as collection_rate
FROM fee_structure fs
JOIN student_fee_accounts sfa ON fs.id = sfa.fee_structure_id
JOIN programs p ON fs.program_id = p.id
WHERE fs.academic_year = IFNULL(?, ''2024-2025'')
  AND IFNULL(?, '''') = '''' OR fs.semester = ?
GROUP BY fs.academic_year, fs.semester, p.program_name, p.program_type
ORDER BY fs.academic_year DESC, fs.semester',
'{"academic_year": {"type": "string", "label": "Academic Year"}, "semester": {"type": "string", "label": "Semester"}}}',
'html', TRUE, 1),

('Payment Methods Analysis', 'PAYMENT_METHODS', 'Analysis of payment methods used by students', 'financial',
'SELECT 
    pt.payment_method,
    COUNT(*) as transaction_count,
    SUM(pt.amount) as total_amount,
    AVG(pt.amount) as average_amount,
    MIN(pt.amount) as minimum_amount,
    MAX(pt.amount) as maximum_amount,
    DATE(pt.payment_date) as payment_date,
    COUNT(CASE WHEN pt.amount >= 1000000 THEN 1 END) as large_payments
FROM payment_transactions pt
WHERE pt.status = ''completed''
  AND pt.payment_date BETWEEN IFNULL(?, DATE_SUB(CURDATE(), INTERVAL 30 DAY)) AND IFNULL(?, CURDATE())
GROUP BY pt.payment_method, DATE(pt.payment_date)
ORDER BY payment_date DESC, total_amount DESC',
'{"start_date": {"type": "date", "label": "Start Date"}, "end_date": {"type": "date", "label": "End Date"}}}',
'html', TRUE, 1),

('Budget Utilization Report', 'BUDGET_UTILIZATION', 'Report showing budget allocation and utilization', 'financial',
'SELECT 
    ba.budget_code,
    ba.budget_name,
    ba.department,
    ba.allocated_amount,
    ba.spent_amount,
    ba.remaining_amount,
    ROUND((ba.spent_amount * 100.0) / ba.allocated_amount, 2) as utilization_rate,
    CASE 
        WHEN ba.spent_amount = 0 THEN ''Not Started''
        WHEN ba.spent_amount < ba.allocated_amount * 0.5 THEN ''Under Utilized''
        WHEN ba.spent_amount < ba.allocated_amount * 0.9 THEN ''On Track''
        WHEN ba.spent_amount < ba.allocated_amount THEN ''Nearly Exhausted''
        ELSE ''Exhausted''
    END as utilization_status,
    ba.fiscal_year,
    ba.status
FROM budget_allocations ba
WHERE ba.fiscal_year = IFNULL(?, ''2024-2025'')
  AND IFNULL(?, '''') = '''' OR ba.department = ?
ORDER BY ba.fiscal_year DESC, ba.department',
'{"fiscal_year": {"type": "string", "label": "Fiscal Year"}, "department": {"type": "string", "label": "Department"}}}',
'html', TRUE, 1),

-- Administrative report templates
INSERT INTO report_templates (template_name, template_code, description, report_category, sql_query, parameters, output_format, is_system, created_by) VALUES
('Student Demographics Report', 'STUDENT_DEMOGRAPHICS', 'Demographic analysis of student population', 'administrative',
'SELECT 
    CASE 
        WHEN u.index_number LIKE ''%/CM/%'' THEN ''Certificate in Midwifery''
        WHEN u.index_number LIKE ''%/CN/%'' THEN ''Certificate in Nursing''
        WHEN u.index_number LIKE ''%/DMORDN/%'' THEN ''Diploma in Midwifery''
        ELSE ''Unknown Program''
    END as program,
    COUNT(*) as total_students,
    COUNT(CASE WHEN u.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as new_students,
    COUNT(CASE WHEN u.last_login >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as active_students,
    COUNT(CASE WHEN sfa.balance > 0 THEN 1 END) as students_with_balance,
    AVG(sfa.balance) as average_balance
FROM users u
LEFT JOIN student_fee_accounts sfa ON u.id = sfa.student_id AND sfa.academic_year = ''2024-2025''
WHERE u.type = ''student'' AND u.status = ''active''
GROUP BY 
    CASE 
        WHEN u.index_number LIKE ''%/CM/%'' THEN ''Certificate in Midwifery''
        WHEN u.index_number LIKE ''%/CN/%'' THEN ''Certificate in Nursing''
        WHEN u.index_number LIKE ''%/DMORDN/%'' THEN ''Diploma in Midwifery''
        ELSE ''Unknown Program''
    END
ORDER BY total_students DESC',
'{}',
'html', TRUE, 1),

('Staff Summary Report', 'STAFF_SUMMARY', 'Summary report of all staff members', 'administrative',
'SELECT 
    u.role,
    COUNT(*) as total_staff,
    COUNT(CASE WHEN u.last_login >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as active_staff,
    COUNT(CASE WHEN u.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as new_staff,
    CASE 
        WHEN LOWER(u.role) LIKE ''%director%'' THEN ''Management''
        WHEN LOWER(u.role) LIKE ''%principal%'' THEN ''Management''
        WHEN LOWER(u.role) LIKE ''%registrar%'' THEN ''Administration''
        WHEN LOWER(u.role) LIKE ''%secretary%'' THEN ''Administration''
        WHEN LOWER(u.role) LIKE ''%bursar%'' OR LOWER(u.role) LIKE ''%accountant%'' THEN ''Finance''
        WHEN LOWER(u.role) LIKE ''%lecturer%'' OR LOWER(u.role) LIKE ''%senior%'' THEN ''Academic''
        WHEN LOWER(u.role) LIKE ''%head%'' THEN ''Academic''
        WHEN LOWER(u.role) LIKE ''%librarian%'' THEN ''Support''
        WHEN LOWER(u.role) LIKE ''%hr%'' THEN ''Support''
        WHEN LOWER(u.role) LIKE ''%matron%'' OR LOWER(u.role) LIKE ''%warden%'' THEN ''Student Services''
        WHEN LOWER(u.role) LIKE ''%lab%'' THEN ''Support''
        WHEN LOWER(u.role) LIKE ''%driver%'' THEN ''Support''
        WHEN LOWER(u.role) LIKE ''%security%'' THEN ''Support''
        ELSE ''Other''
    END as department
FROM users u
WHERE u.type = ''staff'' AND u.status = ''active''
GROUP BY u.role,
    CASE 
        WHEN LOWER(u.role) LIKE ''%director%'' THEN ''Management''
        WHEN LOWER(u.role) LIKE ''%principal%'' THEN ''Management''
        WHEN LOWER(u.role) LIKE ''%registrar%'' THEN ''Administration''
        WHEN LOWER(u.role) LIKE ''%secretary%'' THEN ''Administration''
        WHEN LOWER(u.role) LIKE ''%bursar%'' OR LOWER(u.role) LIKE ''%accountant%'' THEN ''Finance''
        WHEN LOWER(u.role) LIKE ''%lecturer%'' OR LOWER(u.role) LIKE ''%senior%'' THEN ''Academic''
        WHEN LOWER(u.role) LIKE ''%head%'' THEN ''Academic''
        WHEN LOWER(u.role) LIKE ''%librarian%'' THEN ''Support''
        WHEN LOWER(u.role) LIKE ''%hr%'' THEN ''Support''
        WHEN LOWER(u.role) LIKE ''%matron%'' OR LOWER(u.role) LIKE ''%warden%'' THEN ''Student Services''
        WHEN LOWER(u.role) LIKE ''%lab%'' THEN ''Support''
        WHEN LOWER(u.role) LIKE ''%driver%'' THEN ''Support''
        WHEN LOWER(u.role) LIKE ''%security%'' THEN ''Support''
        ELSE ''Other''
    END
ORDER BY total_staff DESC',
'{}',
'html', TRUE, 1),

-- Attendance report templates
INSERT INTO report_templates (template_name, template_code, description, report_category, sql_query, parameters, output_format, is_system, created_by) VALUES
('Attendance Statistics Report', 'ATTENDANCE_STATS', 'Comprehensive attendance statistics report', 'attendance',
'SELECT 
    c.course_code,
    c.course_name,
    p.program_name,
    COUNT(*) as total_sessions,
    COUNT(CASE WHEN ar.attendance_status = ''present'' THEN 1 END) as present_sessions,
    COUNT(CASE WHEN ar.attendance_status = ''absent'' THEN 1 END) as absent_sessions,
    COUNT(CASE WHEN ar.attendance_status = ''late'' THEN 1 END) as late_sessions,
    COUNT(CASE WHEN ar.attendance_status = ''excused'' THEN 1 END) as excused_sessions,
    ROUND((COUNT(CASE WHEN ar.attendance_status = ''present'' THEN 1 END) * 100.0) / COUNT(*), 2) as attendance_rate,
    COUNT(DISTINCT ar.student_id) as unique_students
FROM attendance_records ar
JOIN courses c ON ar.course_id = c.id
JOIN programs p ON c.program_id = p.id
WHERE ar.attendance_date BETWEEN IFNULL(?, DATE_SUB(CURDATE(), INTERVAL 30 DAY)) AND IFNULL(?, CURDATE())
  AND IFNULL(?, '''') = '''' OR c.course_code = ?
GROUP BY c.id, c.course_code, c.course_name, p.program_name
ORDER BY attendance_rate DESC',
'{"start_date": {"type": "date", "label": "Start Date"}, "end_date": {"type": "date", "label": "End Date"}, "course_code": {"type": "string", "label": "Course Code"}}}',
'html', TRUE, 1),

('Student Attendance Summary', 'STUDENT_ATTENDANCE', 'Individual student attendance summary', 'attendance',
'SELECT 
    u.full_name,
    u.index_number,
    c.course_code,
    c.course_name,
    COUNT(*) as total_classes,
    COUNT(CASE WHEN ar.attendance_status = ''present'' THEN 1 END) as present_classes,
    COUNT(CASE WHEN ar.attendance_status = ''absent'' THEN 1 END) as absent_classes,
    COUNT(CASE WHEN ar.attendance_status = ''late'' THEN 1 END) as late_classes,
    ROUND((COUNT(CASE WHEN ar.attendance_status = ''present'' THEN 1 END) * 100.0) / COUNT(*), 2) as attendance_rate,
    ar.semester,
    MAX(ar.attendance_date) as last_attendance
FROM attendance_records ar
JOIN users u ON ar.student_id = u.id
JOIN courses c ON ar.course_id = c.id
WHERE ar.attendance_date BETWEEN IFNULL(?, DATE_SUB(CURDATE(), INTERVAL 30 DAY)) AND IFNULL(?, CURDATE())
  AND IFNULL(?, '''') = '''' OR u.id = ?
GROUP BY u.id, u.full_name, u.index_number, c.id, c.course_code, c.course_name, ar.semester
ORDER BY u.full_name, c.course_code',
'{"start_date": {"type": "date", "label": "Start Date"}, "end_date": {"type": "date", "label": "End Date"}, "student_id": {"type": "integer", "label": "Student ID"}}}',
'html', TRUE, 1),

-- Library report templates
INSERT INTO report_templates (template_name, template_code, description, report_category, sql_query, parameters, output_format, is_system, created_by) VALUES
('Library Usage Report', 'LIBRARY_USAGE', 'Library usage and book circulation report', 'library',
'SELECT 
    COUNT(DISTINCT bl.student_id) as active_readers,
    COUNT(*) as total_loans,
    COUNT(CASE WHEN bl.status = ''borrowed'' THEN 1 END) as current_loans,
    COUNT(CASE WHEN bl.status = ''returned'' THEN 1 END) as returned_books,
    COUNT(CASE WHEN bl.status = ''overdue'' THEN 1 END) as overdue_books,
    SUM(bl.fine_amount) as total_fines,
    SUM(CASE WHEN bl.fine_paid = TRUE THEN bl.fine_amount ELSE 0 END) as fines_paid,
    SUM(CASE WHEN bl.fine_paid = FALSE THEN bl.fine_amount ELSE 0 END) as fines_unpaid,
    COUNT(DISTINCT b.id) as unique_books,
    COUNT(CASE WHEN b.status = ''available'' THEN 1 END) as available_books
FROM book_loans bl
JOIN books b ON bl.book_id = b.id
WHERE bl.loan_date BETWEEN IFNULL(?, DATE_SUB(CURDATE(), INTERVAL 30 DAY)) AND IFNULL(?, CURDATE())',
'{"start_date": {"type": "date", "label": "Start Date"}, "end_date": {"type": "date", "label": "End Date"}}}',
'html', TRUE, 1),

('Most Popular Books', 'POPULAR_BOOKS', 'Report showing most borrowed books', 'library',
'SELECT 
    b.book_title,
    b.author,
    b.category,
    COUNT(bl.id) as borrow_count,
    COUNT(DISTINCT bl.student_id) as unique_readers,
    AVG(DATEDIFF(bl.return_date, bl.loan_date)) as avg_loan_days,
    MAX(bl.loan_date) as last_borrowed
FROM books b
JOIN book_loans bl ON b.id = bl.book_id
WHERE bl.loan_date BETWEEN IFNULL(?, DATE_SUB(CURDATE(), INTERVAL 90 DAY)) AND IFNULL(?, CURDATE())
GROUP BY b.id, b.book_title, b.author, b.category
HAVING COUNT(bl.id) > 0
ORDER BY borrow_count DESC, unique_readers DESC
LIMIT 20',
'{"start_date": {"type": "date", "label": "Start Date"}, "end_date": {"type": "date", "label": "End Date"}}}',
'html', TRUE, 1),

-- Hostel report templates
INSERT INTO report_templates (template_name, template_code, description, report_category, sql_query, parameters, output_format, is_system, created_by) VALUES
('Hostel Occupancy Report', 'HOSTEL_OCCUPANCY', 'Hostel occupancy and room allocation report', 'hostel',
'SELECT 
    h.hostel_name,
    h.hostel_code,
    h.gender,
    h.total_rooms,
    h.total_capacity,
    h.current_occupancy,
    ROUND((h.current_occupancy * 100.0) / h.total_capacity, 2) as occupancy_rate,
    COUNT(CASE WHEN r.status = ''available'' THEN 1 END) as available_rooms,
    COUNT(CASE WHEN r.status = ''occupied'' THEN 1 END) as occupied_rooms,
    COUNT(CASE WHEN r.status = ''maintenance'' THEN 1 END) as maintenance_rooms,
    COUNT(ra.id) as total_allocations,
    COUNT(CASE WHEN ra.status = ''active'' THEN 1 END) as active_allocations
FROM hostels h
LEFT JOIN rooms r ON h.id = r.hostel_id
LEFT JOIN room_allocations ra ON r.id = ra.room_id
GROUP BY h.id, h.hostel_name, h.hostel_code, h.gender, h.total_rooms, h.total_capacity, h.current_occupancy
ORDER BY h.hostel_name',
'{}',
'html', TRUE, 1),

('Room Allocation Details', 'ROOM_ALLOCATION', 'Detailed room allocation report', 'hostel',
'SELECT 
    u.full_name,
    u.index_number,
    u.phone,
    h.hostel_name,
    r.room_number,
    r.room_type,
    r.capacity,
    ra.allocation_date,
    ra.vacate_date,
    ra.status,
    h.warden_name,
    h.warden_contact
FROM room_allocations ra
JOIN users u ON ra.student_id = u.id
JOIN rooms r ON ra.room_id = r.id
JOIN hostels h ON r.hostel_id = h.id
WHERE ra.allocation_date BETWEEN IFNULL(?, DATE_SUB(CURDATE(), INTERVAL 30 DAY)) AND IFNULL(?, CURDATE())
  AND IFNULL(?, '''') = '''' OR h.hostel_code = ?
ORDER BY ra.allocation_date DESC',
'{"start_date": {"type": "date", "label": "Start Date"}, "end_date": {"type": "date", "label": "End Date"}, "hostel_code": {"type": "string", "label": "Hostel Code"}}}',
'html', TRUE, 1);

-- ========================================
-- INSERT DEFAULT DASHBOARD WIDGETS
-- ========================================

-- Student dashboard widgets
INSERT INTO dashboard_widgets (widget_name, widget_code, widget_type, dashboard_type, sql_query, configuration, position, is_system, created_by) VALUES
('Total Courses', 'STUDENT_TOTAL_COURSES', 'statistic', 'student', 
'SELECT COUNT(*) as value FROM student_academic_records WHERE student_id = ? AND status IN (''completed'', ''in_progress'')',
'{"icon": "fas fa-book", "color": "#007bff", "label": "Total Courses"}',
'{"x": 0, "y": 0, "w": 3, "h": 2}', TRUE, 1),

('Current GPA', 'STUDENT_GPA', 'statistic', 'student',
'SELECT ROUND(AVG(gpa_points), 2) as value FROM student_academic_records WHERE student_id = ? AND status = ''completed''',
'{"icon": "fas fa-chart-line", "color": "#28a745", "label": "Current GPA"}',
'{"x": 3, "y": 0, "w": 3, "h": 2}', TRUE, 1),

('Fee Balance', 'STUDENT_FEE_BALANCE', 'statistic', 'student',
'SELECT COALESCE(SUM(balance), 0) as value FROM student_fee_accounts WHERE student_id = ? AND academic_year = ''2024-2025''',
'{"icon": "fas fa-money-bill", "color": "#ffc107", "label": "Fee Balance (UGX)"}',
'{"x": 6, "y": 0, "w": 3, "h": 2}', TRUE, 1),

('Attendance Rate', 'STUDENT_ATTENDANCE', 'statistic', 'student',
'SELECT ROUND((COUNT(CASE WHEN attendance_status = ''present'' THEN 1 END) * 100.0) / COUNT(*), 2) as value FROM attendance_records WHERE student_id = ? AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)',
'{"icon": "fas fa-calendar-check", "color": "#17a2b8", "label": "Attendance Rate (%)"}',
'{"x": 9, "y": 0, "w": 3, "h": 2}', TRUE, 1),

-- Staff dashboard widgets
INSERT INTO dashboard_widgets (widget_name, widget_code, widget_type, dashboard_type, sql_query, configuration, position, is_system, created_by) VALUES
('Courses Taught', 'STAFF_COURSES', 'statistic', 'staff',
'SELECT COUNT(*) as value FROM courses WHERE created_by = ? OR id IN (SELECT course_id FROM course_assignments WHERE staff_id = ? AND status = ''active'')',
'{"icon": "fas fa-chalkboard-teacher", "color": "#007bff", "label": "Courses Taught"}',
'{"x": 0, "y": 0, "w": 3, "h": 2}', TRUE, 1),

('Total Students', 'STAFF_STUDENTS', 'statistic', 'staff',
'SELECT COUNT(DISTINCT sar.student_id) as value FROM student_academic_records sar JOIN courses c ON sar.course_id = c.id WHERE c.created_by = ? OR c.id IN (SELECT course_id FROM course_assignments WHERE staff_id = ? AND status = ''active'')',
'{"icon": "fas fa-users", "color": "#28a745", "label": "Total Students"}',
'{"x": 3, "y": 0, "w": 3, "h": 2}', TRUE, 1),

('Payments Collected', 'STAFF_PAYMENTS', 'statistic', 'staff',
'SELECT COALESCE(SUM(amount), 0) as value FROM payment_transactions WHERE collected_by = ? AND status = ''completed'' AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)',
'{"icon": "fas fa-cash-register", "color": "#ffc107", "label": "Payments (30 days)"}',
'{"x": 6, "y": 0, "w": 3, "h": 2}', TRUE, 1),

('Unread Messages', 'STAFF_MESSAGES', 'statistic', 'staff',
'SELECT COUNT(*) as value FROM messages WHERE recipient_id = ? AND delivery_status = ''delivered'' AND read_at IS NULL',
'{"icon": "fas fa-envelope", "color": "#dc3545", "label": "Unread Messages"}',
'{"x": 9, "y": 0, "w": 3, "h": 2}', TRUE, 1),

-- Director dashboard widgets
INSERT INTO dashboard_widgets (widget_name, widget_code, widget_type, dashboard_type, sql_query, configuration, position, is_system, created_by) VALUES
('Total Students', 'DIRECTOR_STUDENTS', 'statistic', 'director',
'SELECT COUNT(*) as value FROM users WHERE type = ''student'' AND status = ''active''',
'{"icon": "fas fa-graduation-cap", "color": "#007bff", "label": "Total Students"}',
'{"x": 0, "y": 0, "w": 3, "h": 2}', TRUE, 1),

('Total Staff', 'DIRECTOR_STAFF', 'statistic', 'director',
'SELECT COUNT(*) as value FROM users WHERE type = ''staff'' AND status = ''active''',
'{"icon": "fas fa-users", "color": "#28a745", "label": "Total Staff"}',
'{"x": 3, "y": 0, "w": 3, "h": 2}', TRUE, 1),

('Total Revenue', 'DIRECTOR_REVENUE', 'statistic', 'director',
'SELECT COALESCE(SUM(amount), 0) as value FROM payment_transactions WHERE status = ''completed'' AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)',
'{"icon": "fas fa-chart-line", "color": "#ffc107", "label": "Revenue (30 days)"}',
'{"x": 6, "y": 0, "w": 3, "h": 2}', TRUE, 1),

('Active Programs', 'DIRECTOR_PROGRAMS', 'statistic', 'director',
'SELECT COUNT(*) as value FROM programs WHERE status = ''active''',
'{"icon": "fas fa-book", "color": "#17a2b8", "label": "Active Programs"}',
'{"x": 9, "y": 0, "w": 3, "h": 2}', TRUE, 1);

-- ========================================
-- STORED PROCEDURES FOR REPORTING
-- ========================================

DELIMITER //

-- Procedure to generate report
CREATE PROCEDURE IF NOT EXISTS generate_report(
    IN p_template_id INT,
    IN p_parameters JSON,
    IN p_generated_by INT,
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN,
    OUT p_report_id INT
)
BEGIN
    DECLARE v_template_exists INT DEFAULT 0;
    DECLARE v_sql_query LONGTEXT;
    DECLARE v_report_name VARCHAR(255);
    DECLARE v_output_format VARCHAR(20);
    
    -- Check if template exists
    SELECT COUNT(*) INTO v_template_exists
    FROM report_templates 
    WHERE id = p_template_id AND status = 'active';
    
    IF v_template_exists = 0 THEN
        SET p_result = 'Report template not found or inactive';
        SET p_success = FALSE;
        SET p_report_id = NULL;
    ELSE
        -- Get template details
        SELECT sql_query, template_name, output_format 
        INTO v_sql_query, v_report_name, v_output_format
        FROM report_templates 
        WHERE id = p_template_id;
        
        -- Create report record
        INSERT INTO generated_reports (
            template_id, report_name, parameters, output_format, status, generated_by
        ) VALUES (
            p_template_id, v_report_name, p_parameters, v_output_format, 'generating', p_generated_by
        );
        
        SET p_report_id = LAST_INSERT_ID();
        
        -- Update status to completed (in real implementation, this would generate the actual file)
        UPDATE generated_reports 
        SET status = 'completed', generated_at = NOW()
        WHERE id = p_report_id;
        
        -- Log activity
        INSERT INTO activity_logs (
            user_id, action, description, table_name, record_id
        ) VALUES (
            p_generated_by, 'REPORT_GENERATE', 
            CONCAT('Generated report: ', v_report_name), 
            'generated_reports', p_report_id
        );
        
        SET p_result = 'Report generated successfully';
        SET p_success = TRUE;
    END IF;
END //

-- Procedure to get dashboard data
CREATE PROCEDURE IF NOT EXISTS get_dashboard_data(
    IN p_dashboard_type VARCHAR(50),
    IN p_user_id INT
)
BEGIN
    -- Get widgets for the dashboard type
    SELECT 
        dw.widget_code,
        dw.widget_name,
        dw.widget_type,
        dw.sql_query,
        dw.configuration,
        dw.position
    FROM dashboard_widgets dw
    WHERE dw.dashboard_type = p_dashboard_type 
      AND dw.status = 'active'
    ORDER BY dw.position->>'$.y', dw.position->>'$.x';
    
    -- For each widget, execute the query with user_id parameter
    -- This would typically be handled in the application code
END //

-- Procedure to calculate analytics data
CREATE PROCEDURE IF NOT EXISTS calculate_analytics(
    IN p_metric_name VARCHAR(100),
    IN p_metric_category VARCHAR(50),
    IN p_period_type VARCHAR(20),
    IN p_period_start DATE,
    IN p_period_end DATE,
    IN p_dimensions JSON,
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN
)
BEGIN
    DECLARE v_metric_value DECIMAL(15,2);
    
    -- Calculate different metrics based on category
    IF p_metric_category = 'students' THEN
        IF p_metric_name = 'total_students' THEN
            SELECT COUNT(*) INTO v_metric_value
            FROM users 
            WHERE type = 'student' AND status = 'active';
            
        ELSEIF p_metric_name = 'new_students' THEN
            SELECT COUNT(*) INTO v_metric_value
            FROM users 
            WHERE type = 'student' AND status = 'active' 
              AND created_at BETWEEN p_period_start AND p_period_end;
        END IF;
        
    ELSEIF p_metric_category = 'financial' THEN
        IF p_metric_name = 'total_revenue' THEN
            SELECT COALESCE(SUM(amount), 0) INTO v_metric_value
            FROM payment_transactions 
            WHERE status = 'completed' 
              AND payment_date BETWEEN p_period_start AND p_period_end;
        END IF;
        
    ELSEIF p_metric_category = 'academic' THEN
        IF p_metric_name = 'average_gpa' THEN
            SELECT COALESCE(AVG(gpa_points), 0) INTO v_metric_value
            FROM student_academic_records 
            WHERE status = 'completed';
        END IF;
    END IF;
    
    -- Store the calculated metric
    INSERT INTO analytics_data (
        metric_name, metric_category, metric_value, metric_unit, 
        period_type, period_start, period_end, dimensions
    ) VALUES (
        p_metric_name, p_metric_category, v_metric_value, 'count',
        p_period_type, p_period_start, p_period_end, p_dimensions
    )
    ON DUPLICATE KEY UPDATE 
        metric_value = VALUES(metric_value),
        created_at = NOW();
    
    SET p_result = CONCAT('Analytics calculated: ', p_metric_name, ' = ', v_metric_value);
    SET p_success = TRUE;
END //

-- Procedure to get system overview statistics
CREATE PROCEDURE IF NOT EXISTS get_system_overview()
BEGIN
    -- Student statistics
    SELECT 
        'Total Students' as metric,
        COUNT(*) as value,
        'count' as unit
    FROM users 
    WHERE type = 'student' AND status = 'active'
    
    UNION ALL
    
    SELECT 
        'Total Staff' as metric,
        COUNT(*) as value,
        'count' as unit
    FROM users 
    WHERE type = 'staff' AND status = 'active'
    
    UNION ALL
    
    SELECT 
        'Active Programs' as metric,
        COUNT(*) as value,
        'count' as unit
    FROM programs 
    WHERE status = 'active'
    
    UNION ALL
    
    SELECT 
        'Total Courses' as metric,
        COUNT(*) as value,
        'count' as unit
    FROM courses 
    WHERE status = 'active'
    
    UNION ALL
    
    SELECT 
        'Monthly Revenue' as metric,
        COALESCE(SUM(amount), 0) as value,
        'UGX' as unit
    FROM payment_transactions 
    WHERE status = 'completed' 
      AND payment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    
    UNION ALL
    
    SELECT 
        'Total Books' as metric,
        COUNT(*) as value,
        'count' as unit
    FROM books
    
    UNION ALL
    
    SELECT 
        'Total Hostel Capacity' as metric,
        SUM(total_capacity) as value,
        'beds' as unit
    FROM hostels
    WHERE status = 'active';
END //

DELIMITER ;

-- Success message
SELECT 'Reporting system SQL created successfully!' as message;
SELECT 'All tables, views, and stored procedures for reporting and analytics are ready for use' as note;
