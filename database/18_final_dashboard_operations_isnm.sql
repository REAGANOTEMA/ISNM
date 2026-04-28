-- =====================================================
-- ISNM SCHOOL MANAGEMENT SYSTEM - FINAL DASHBOARD OPERATIONS
-- Database: isnm_db
-- Supports ALL dashboard operations: CRUD, Print, Send, Comment, etc.
-- =====================================================

USE isnm_db;

-- Drop existing tables if they exist to ensure clean setup
DROP TABLE IF EXISTS dashboard_comments;
DROP TABLE IF EXISTS dashboard_print_logs;
DROP TABLE IF EXISTS dashboard_send_logs;
DROP TABLE IF EXISTS dashboard_activity_logs;
DROP TABLE IF EXISTS dashboard_user_preferences;
DROP TABLE IF EXISTS dashboard_notifications;
DROP TABLE IF EXISTS dashboard_quick_actions;
DROP TABLE IF EXISTS dashboard_favorites;
DROP TABLE IF EXISTS dashboard_widgets;
DROP TABLE IF EXISTS dashboard_reports;
DROP TABLE IF EXISTS dashboard_audit_trail;
DROP TABLE IF EXISTS dashboard_file_attachments;
DROP TABLE IF EXISTS dashboard_bulk_operations;
DROP TABLE IF EXISTS dashboard_export_logs;
DROP TABLE IF EXISTS dashboard_import_logs;
DROP TABLE IF EXISTS dashboard_api_logs;
DROP TABLE IF EXISTS dashboard_sessions;
DROP TABLE IF EXISTS dashboard_permissions;
DROP TABLE IF EXISTS dashboard_roles;

-- =====================================================
-- 1. DASHBOARD COMMENTS SYSTEM
-- =====================================================
CREATE TABLE dashboard_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entity_type VARCHAR(50) NOT NULL, -- 'student', 'staff', 'course', 'payment', etc.
    entity_id INT NOT NULL,
    user_id INT NOT NULL,
    comment_text TEXT NOT NULL,
    parent_comment_id INT NULL,
    mentions JSON NULL, -- Store mentioned users
    attachments JSON NULL, -- Store attachment references
    is_edited BOOLEAN DEFAULT FALSE,
    is_deleted BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_user (user_id),
    INDEX idx_parent (parent_comment_id),
    INDEX idx_created (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 2. DASHBOARD PRINT LOGS
-- =====================================================
CREATE TABLE dashboard_print_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    document_type VARCHAR(100) NOT NULL, -- 'student_report', 'fee_statement', 'attendance', etc.
    document_id INT NOT NULL,
    print_format VARCHAR(50) DEFAULT 'PDF', -- 'PDF', 'Excel', 'Word'
    file_path VARCHAR(500) NULL,
    file_size INT NULL, -- in bytes
    print_status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    error_message TEXT NULL,
    print_count INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    
    INDEX idx_user (user_id),
    INDEX idx_document (document_type, document_id),
    INDEX idx_status (print_status),
    INDEX idx_created (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 3. DASHBOARD SEND LOGS
-- =====================================================
CREATE TABLE dashboard_send_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    send_type ENUM('email', 'sms', 'internal', 'notification', 'whatsapp') NOT NULL,
    recipient_type VARCHAR(50) NOT NULL, -- 'student', 'staff', 'parent', 'all'
    recipient_id INT NULL,
    recipient_contact VARCHAR(255) NOT NULL, -- email, phone, or user_id
    subject VARCHAR(255) NULL,
    message_content TEXT NOT NULL,
    attachments JSON NULL, -- Store attachment references
    send_status ENUM('pending', 'sent', 'delivered', 'failed', 'bounced') DEFAULT 'pending',
    delivery_status JSON NULL, -- Track delivery confirmation
    error_message TEXT NULL,
    retry_count INT DEFAULT 0,
    sent_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user (user_id),
    INDEX idx_recipient (recipient_type, recipient_id),
    INDEX idx_status (send_status),
    INDEX idx_type (send_type),
    INDEX idx_created (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 4. DASHBOARD ACTIVITY LOGS
-- =====================================================
CREATE TABLE dashboard_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL, -- 'create', 'update', 'delete', 'view', 'print', 'send'
    entity_type VARCHAR(50) NOT NULL, -- 'student', 'staff', 'course', 'payment', etc.
    entity_id INT NOT NULL,
    old_values JSON NULL, -- Store previous values for updates
    new_values JSON NULL, -- Store new values for updates
    description TEXT NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    session_id VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user (user_id),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at),
    INDEX idx_session (session_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 5. DASHBOARD USER PREFERENCES
-- =====================================================
CREATE TABLE dashboard_user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    preference_key VARCHAR(100) NOT NULL, -- 'theme', 'language', 'notifications', etc.
    preference_value JSON NOT NULL,
    category VARCHAR(50) DEFAULT 'general', -- 'ui', 'notifications', 'privacy', etc.
    is_system_default BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_user_preference (user_id, preference_key),
    INDEX idx_user (user_id),
    INDEX idx_category (category),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 6. DASHBOARD NOTIFICATIONS
-- =====================================================
CREATE TABLE dashboard_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    notification_type ENUM('info', 'success', 'warning', 'error', 'system') DEFAULT 'info',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    action_url VARCHAR(500) NULL,
    action_text VARCHAR(100) NULL,
    icon VARCHAR(50) NULL,
    is_read BOOLEAN DEFAULT FALSE,
    is_dismissed BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    dismissed_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user (user_id),
    INDEX idx_read (is_read),
    INDEX idx_priority (priority),
    INDEX idx_type (notification_type),
    INDEX idx_created (created_at),
    INDEX idx_expires (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 7. DASHBOARD QUICK ACTIONS
-- =====================================================
CREATE TABLE dashboard_quick_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_name VARCHAR(100) NOT NULL,
    action_type VARCHAR(50) NOT NULL, -- 'create_student', 'send_message', 'generate_report', etc.
    action_url VARCHAR(500) NOT NULL,
    action_icon VARCHAR(50) NULL,
    action_color VARCHAR(20) DEFAULT 'primary',
    is_favorite BOOLEAN DEFAULT FALSE,
    display_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_user (user_id),
    INDEX idx_favorite (is_favorite),
    INDEX idx_order (display_order),
    INDEX idx_active (is_active),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 8. DASHBOARD FAVORITES
-- =====================================================
CREATE TABLE dashboard_favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    entity_type VARCHAR(50) NOT NULL, -- 'student', 'staff', 'course', 'report', etc.
    entity_id INT NOT NULL,
    entity_name VARCHAR(255) NOT NULL,
    entity_url VARCHAR(500) NULL,
    category VARCHAR(50) DEFAULT 'general',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_user_entity (user_id, entity_type, entity_id),
    INDEX idx_user (user_id),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_category (category),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 9. DASHBOARD WIDGETS
-- =====================================================
CREATE TABLE dashboard_widgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    widget_name VARCHAR(100) NOT NULL,
    widget_type VARCHAR(50) NOT NULL, -- 'chart', 'table', 'card', 'calendar', etc.
    widget_config JSON NOT NULL, -- Widget configuration
    data_source VARCHAR(100) NULL, -- SQL query or API endpoint
    refresh_interval INT DEFAULT 300, -- in seconds
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_type (widget_type),
    INDEX idx_active (is_active),
    INDEX idx_created_by (created_by),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 10. DASHBOARD REPORTS
-- =====================================================
CREATE TABLE dashboard_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_name VARCHAR(255) NOT NULL,
    report_type VARCHAR(50) NOT NULL, -- 'student', 'financial', 'academic', 'attendance', etc.
    report_template VARCHAR(100) NULL,
    parameters JSON NULL, -- Report parameters
    sql_query TEXT NULL, -- SQL query for data
    file_format ENUM('PDF', 'Excel', 'CSV', 'Word') DEFAULT 'PDF',
    file_path VARCHAR(500) NULL,
    file_size INT NULL,
    generation_status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    error_message TEXT NULL,
    generated_by INT NOT NULL,
    generated_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_type (report_type),
    INDEX idx_status (generation_status),
    INDEX idx_generated_by (generated_by),
    INDEX idx_generated (generated_at),
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 11. DASHBOARD AUDIT TRAIL
-- =====================================================
CREATE TABLE dashboard_audit_trail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    module VARCHAR(50) NOT NULL, -- 'authentication', 'academic', 'finance', etc.
    record_id INT NULL,
    table_name VARCHAR(100) NULL,
    old_data JSON NULL,
    new_data JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    session_id VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_module (module),
    INDEX idx_table (table_name),
    INDEX idx_created (created_at),
    INDEX idx_session (session_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 12. DASHBOARD FILE ATTACHMENTS
-- =====================================================
CREATE TABLE dashboard_file_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_type VARCHAR(50) NOT NULL, -- 'image', 'document', 'video', 'audio', 'other'
    uploaded_by INT NOT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    download_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_uploaded_by (uploaded_by),
    INDEX idx_type (file_type),
    INDEX idx_public (is_public),
    INDEX idx_created (created_at),
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 13. DASHBOARD BULK OPERATIONS
-- =====================================================
CREATE TABLE dashboard_bulk_operations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    operation_type VARCHAR(50) NOT NULL, -- 'create', 'update', 'delete', 'import', 'export'
    entity_type VARCHAR(50) NOT NULL, -- 'students', 'staff', 'courses', etc.
    total_records INT NOT NULL DEFAULT 0,
    processed_records INT NOT NULL DEFAULT 0,
    successful_records INT NOT NULL DEFAULT 0,
    failed_records INT NOT NULL DEFAULT 0,
    operation_status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    parameters JSON NULL, -- Operation parameters
    error_log TEXT NULL,
    started_by INT NOT NULL,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_type (operation_type),
    INDEX idx_entity (entity_type),
    INDEX idx_status (operation_status),
    INDEX idx_started_by (started_by),
    INDEX idx_created (created_at),
    FOREIGN KEY (started_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 14. DASHBOARD EXPORT LOGS
-- =====================================================
CREATE TABLE dashboard_export_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    export_type VARCHAR(50) NOT NULL, -- 'students', 'staff', 'payments', 'reports', etc.
    export_format ENUM('CSV', 'Excel', 'PDF', 'JSON') NOT NULL,
    filters JSON NULL, -- Export filters
    total_records INT NOT NULL DEFAULT 0,
    file_path VARCHAR(500) NULL,
    file_size INT NULL,
    export_status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    error_message TEXT NULL,
    downloaded_by INT NOT NULL,
    downloaded_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_type (export_type),
    INDEX idx_format (export_format),
    INDEX idx_status (export_status),
    INDEX idx_downloaded_by (downloaded_by),
    INDEX idx_created (created_at),
    FOREIGN KEY (downloaded_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 15. DASHBOARD IMPORT LOGS
-- =====================================================
CREATE TABLE dashboard_import_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    import_type VARCHAR(50) NOT NULL, -- 'students', 'staff', 'courses', etc.
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    total_records INT NOT NULL DEFAULT 0,
    processed_records INT NOT NULL DEFAULT 0,
    successful_records INT NOT NULL DEFAULT 0,
    failed_records INT NOT NULL DEFAULT 0,
    duplicate_records INT NOT NULL DEFAULT 0,
    import_status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
    validation_errors JSON NULL, -- Store validation errors
    error_log TEXT NULL,
    imported_by INT NOT NULL,
    imported_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_type (import_type),
    INDEX idx_status (import_status),
    INDEX idx_imported_by (imported_by),
    INDEX idx_created (created_at),
    FOREIGN KEY (imported_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 16. DASHBOARD API LOGS
-- =====================================================
CREATE TABLE dashboard_api_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_endpoint VARCHAR(255) NOT NULL,
    http_method VARCHAR(10) NOT NULL,
    request_headers JSON NULL,
    request_body TEXT NULL,
    response_status INT NULL,
    response_headers JSON NULL,
    response_body TEXT NULL,
    execution_time INT NULL, -- in milliseconds
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    user_id INT NULL,
    session_id VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_endpoint (api_endpoint),
    INDEX idx_method (http_method),
    INDEX idx_status (response_status),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at),
    INDEX idx_execution_time (execution_time),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- 17. DASHBOARD SESSIONS
-- =====================================================
CREATE TABLE dashboard_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(255) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    logout_time TIMESTAMP NULL,
    session_duration INT NULL, -- in seconds
    is_active BOOLEAN DEFAULT TRUE,
    logout_reason ENUM('manual', 'timeout', 'forced', 'error') NULL,
    
    INDEX idx_session_id (session_id),
    INDEX idx_user (user_id),
    INDEX idx_active (is_active),
    INDEX idx_login (login_time),
    INDEX idx_last_activity (last_activity),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 18. DASHBOARD PERMISSIONS
-- =====================================================
CREATE TABLE dashboard_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    permission_name VARCHAR(100) NOT NULL UNIQUE,
    permission_description TEXT NULL,
    module VARCHAR(50) NOT NULL, -- 'students', 'staff', 'academic', 'finance', etc.
    action VARCHAR(50) NOT NULL, -- 'create', 'read', 'update', 'delete', 'print', 'send', etc.
    resource VARCHAR(50) NULL, -- 'own', 'department', 'all', etc.
    is_system BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_module (module),
    INDEX idx_action (action),
    INDEX idx_resource (resource),
    INDEX idx_system (is_system)
);

-- =====================================================
-- 19. DASHBOARD ROLES
-- =====================================================
CREATE TABLE dashboard_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(100) NOT NULL UNIQUE,
    role_description TEXT NULL,
    is_system_role BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_name (role_name),
    INDEX idx_active (is_active),
    INDEX idx_system (is_system_role)
);

-- =====================================================
-- 20. ROLE PERMISSIONS MAPPING
-- =====================================================
CREATE TABLE dashboard_role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    granted_by INT NOT NULL,
    
    UNIQUE KEY unique_role_permission (role_id, permission_id),
    INDEX idx_role (role_id),
    INDEX idx_permission (permission_id),
    INDEX idx_granted_by (granted_by),
    FOREIGN KEY (role_id) REFERENCES dashboard_roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES dashboard_permissions(id) ON DELETE CASCADE,
    FOREIGN KEY (granted_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- INSERT DEFAULT DASHBOARD PERMISSIONS
-- =====================================================
INSERT INTO dashboard_permissions (permission_name, permission_description, module, action, resource, is_system) VALUES
-- Student permissions
('student_view_own', 'View own student profile', 'students', 'read', 'own', TRUE),
('student_edit_own', 'Edit own student profile', 'students', 'update', 'own', TRUE),
('student_print_own', 'Print own documents', 'students', 'print', 'own', TRUE),
('student_view_fees', 'View own fee information', 'finance', 'read', 'own', TRUE),
('student_view_academic', 'View own academic records', 'academic', 'read', 'own', TRUE),
('student_view_attendance', 'View own attendance', 'academic', 'read', 'own', TRUE),

-- Staff permissions
('staff_view_all', 'View all staff profiles', 'staff', 'read', 'all', TRUE),
('staff_create', 'Create new staff accounts', 'staff', 'create', 'all', TRUE),
('staff_edit', 'Edit staff profiles', 'staff', 'update', 'all', TRUE),
('staff_delete', 'Delete staff accounts', 'staff', 'delete', 'all', TRUE),
('staff_print', 'Print staff documents', 'staff', 'print', 'all', TRUE),

-- Academic permissions
('academic_view', 'View academic records', 'academic', 'read', 'all', TRUE),
('academic_create', 'Create academic records', 'academic', 'create', 'all', TRUE),
('academic_edit', 'Edit academic records', 'academic', 'update', 'all', TRUE),
('academic_delete', 'Delete academic records', 'academic', 'delete', 'all', TRUE),
('academic_print', 'Print academic documents', 'academic', 'print', 'all', TRUE),

-- Finance permissions
('finance_view', 'View financial records', 'finance', 'read', 'all', TRUE),
('finance_create', 'Create financial records', 'finance', 'create', 'all', TRUE),
('finance_edit', 'Edit financial records', 'finance', 'update', 'all', TRUE),
('finance_delete', 'Delete financial records', 'finance', 'delete', 'all', TRUE),
('finance_print', 'Print financial documents', 'finance', 'print', 'all', TRUE),
('finance_send', 'Send financial notifications', 'finance', 'send', 'all', TRUE),

-- Dashboard permissions
('dashboard_view', 'Access dashboard', 'dashboard', 'read', 'all', TRUE),
('dashboard_customize', 'Customize dashboard layout', 'dashboard', 'update', 'own', TRUE),
('dashboard_export', 'Export dashboard data', 'dashboard', 'export', 'all', TRUE),
('dashboard_import', 'Import dashboard data', 'dashboard', 'import', 'all', TRUE),

-- System permissions
('system_view_logs', 'View system logs', 'system', 'read', 'all', TRUE),
('system_manage_users', 'Manage user accounts', 'system', 'manage', 'all', TRUE),
('system_backup', 'Perform system backup', 'system', 'backup', 'all', TRUE),
('system_restore', 'Perform system restore', 'system', 'restore', 'all', TRUE);

-- =====================================================
-- INSERT DEFAULT DASHBOARD ROLES
-- =====================================================
INSERT INTO dashboard_roles (role_name, role_description, is_system_role, is_active) VALUES
('Student', 'Student role with basic permissions', TRUE, TRUE),
('Lecturer', 'Lecturer role with teaching permissions', TRUE, TRUE),
('Secretary', 'Secretary role with administrative permissions', TRUE, TRUE),
('Accountant', 'Accountant role with financial permissions', TRUE, TRUE),
('Librarian', 'Librarian role with library permissions', TRUE, TRUE),
('Principal', 'Principal role with school management permissions', TRUE, TRUE),
('Director General', 'Director General role with full system access', TRUE, TRUE),
('Director Academics', 'Director Academics role with academic oversight', TRUE, TRUE),
('Director Finance', 'Director Finance role with financial oversight', TRUE, TRUE),
('Director ICT', 'Director ICT role with technical permissions', TRUE, TRUE),
('HR Manager', 'HR Manager role with personnel management', TRUE, TRUE),
('Bursar', 'Bursar role with financial management', TRUE, TRUE),
('Security', 'Security role with safety permissions', TRUE, TRUE),
('Matron', 'Matron role with student care permissions', TRUE, TRUE),
('Warden', 'Warden role with hostel management', TRUE, TRUE),
('Driver', 'Driver role with transport permissions', TRUE, TRUE),
('Lab Technician', 'Lab Technician role with laboratory permissions', TRUE, TRUE),
('Non-Teaching Staff', 'Non-Teaching Staff role with basic permissions', TRUE, TRUE);

-- =====================================================
-- CREATE STORED PROCEDURES FOR DASHBOARD OPERATIONS
-- =====================================================

DELIMITER //

-- Procedure to log dashboard activity
CREATE PROCEDURE log_dashboard_activity(
    IN p_user_id INT,
    IN p_action VARCHAR(100),
    IN p_entity_type VARCHAR(50),
    IN p_entity_id INT,
    IN p_old_values JSON,
    IN p_new_values JSON,
    IN p_description TEXT,
    IN p_ip_address VARCHAR(45),
    IN p_user_agent TEXT,
    IN p_session_id VARCHAR(255)
)
BEGIN
    INSERT INTO dashboard_activity_logs (
        user_id, action, entity_type, entity_id, old_values, new_values, 
        description, ip_address, user_agent, session_id
    ) VALUES (
        p_user_id, p_action, p_entity_type, p_entity_id, p_old_values, p_new_values,
        p_description, p_ip_address, p_user_agent, p_session_id
    );
END //

-- Procedure to add dashboard comment
CREATE PROCEDURE add_dashboard_comment(
    IN p_entity_type VARCHAR(50),
    IN p_entity_id INT,
    IN p_user_id INT,
    IN p_comment_text TEXT,
    IN p_parent_comment_id INT,
    IN p_mentions JSON,
    IN p_attachments JSON
)
BEGIN
    DECLARE v_comment_id INT;
    
    INSERT INTO dashboard_comments (
        entity_type, entity_id, user_id, comment_text, parent_comment_id, mentions, attachments
    ) VALUES (
        p_entity_type, p_entity_id, p_user_id, p_comment_text, p_parent_comment_id, p_mentions, p_attachments
    );
    
    SET v_comment_id = LAST_INSERT_ID();
    
    -- Log the activity
    CALL log_dashboard_activity(
        p_user_id, 'create', 'comment', v_comment_id, NULL, 
        JSON_OBJECT('comment_id', v_comment_id, 'entity_type', p_entity_type, 'entity_id', p_entity_id),
        CONCAT('Added comment on ', p_entity_type, ' ID: ', p_entity_id),
        NULL, NULL, NULL
    );
    
    SELECT v_comment_id as comment_id;
END //

-- Procedure to log print operation
CREATE PROCEDURE log_dashboard_print(
    IN p_user_id INT,
    IN p_document_type VARCHAR(100),
    IN p_document_id INT,
    IN p_print_format VARCHAR(50)
)
BEGIN
    DECLARE v_print_id INT;
    
    INSERT INTO dashboard_print_logs (
        user_id, document_type, document_id, print_format
    ) VALUES (
        p_user_id, p_document_type, p_document_id, p_print_format
    );
    
    SET v_print_id = LAST_INSERT_ID();
    
    -- Log the activity
    CALL log_dashboard_activity(
        p_user_id, 'print', p_document_type, p_document_id, NULL, NULL,
        CONCAT('Printed ', p_document_type, ' (ID: ', p_document_id, ') in ', p_print_format, ' format'),
        NULL, NULL, NULL
    );
    
    SELECT v_print_id as print_id;
END //

-- Procedure to log send operation
CREATE PROCEDURE log_dashboard_send(
    IN p_user_id INT,
    IN p_send_type ENUM('email', 'sms', 'internal', 'notification', 'whatsapp'),
    IN p_recipient_type VARCHAR(50),
    IN p_recipient_id INT,
    IN p_recipient_contact VARCHAR(255),
    IN p_subject VARCHAR(255),
    IN p_message_content TEXT,
    IN p_attachments JSON
)
BEGIN
    DECLARE v_send_id INT;
    
    INSERT INTO dashboard_send_logs (
        user_id, send_type, recipient_type, recipient_id, recipient_contact, 
        subject, message_content, attachments
    ) VALUES (
        p_user_id, p_send_type, p_recipient_type, p_recipient_id, p_recipient_contact,
        p_subject, p_message_content, p_attachments
    );
    
    SET v_send_id = LAST_INSERT_ID();
    
    -- Log the activity
    CALL log_dashboard_activity(
        p_user_id, 'send', p_recipient_type, p_recipient_id, NULL, NULL,
        CONCAT('Sent ', p_send_type, ' to ', p_recipient_type, ' (ID: ', p_recipient_id, ')'),
        NULL, NULL, NULL
    );
    
    SELECT v_send_id as send_id;
END //

-- Procedure to add to favorites
CREATE PROCEDURE add_dashboard_favorite(
    IN p_user_id INT,
    IN p_entity_type VARCHAR(50),
    IN p_entity_id INT,
    IN p_entity_name VARCHAR(255),
    IN p_entity_url VARCHAR(500),
    IN p_category VARCHAR(50),
    IN p_notes TEXT
)
BEGIN
    DECLARE v_favorite_id INT;
    
    INSERT INTO dashboard_favorites (
        user_id, entity_type, entity_id, entity_name, entity_url, category, notes
    ) VALUES (
        p_user_id, p_entity_type, p_entity_id, p_entity_name, p_entity_url, p_category, p_notes
    );
    
    SET v_favorite_id = LAST_INSERT_ID();
    
    -- Log the activity
    CALL log_dashboard_activity(
        p_user_id, 'create', 'favorite', v_favorite_id, NULL,
        JSON_OBJECT('favorite_id', v_favorite_id, 'entity_type', p_entity_type, 'entity_id', p_entity_id),
        CONCAT('Added ', p_entity_type, ' to favorites: ', p_entity_name),
        NULL, NULL, NULL
    );
    
    SELECT v_favorite_id as favorite_id;
END //

-- Procedure to update user preferences
CREATE PROCEDURE update_dashboard_preference(
    IN p_user_id INT,
    IN p_preference_key VARCHAR(100),
    IN p_preference_value JSON,
    IN p_category VARCHAR(50)
)
BEGIN
    INSERT INTO dashboard_user_preferences (
        user_id, preference_key, preference_value, category
    ) VALUES (
        p_user_id, p_preference_key, p_preference_value, p_category
    ) ON DUPLICATE KEY UPDATE 
        preference_value = VALUES(preference_value),
        category = VALUES(category),
        updated_at = CURRENT_TIMESTAMP;
    
    -- Log the activity
    CALL log_dashboard_activity(
        p_user_id, 'update', 'preference', 0, NULL,
        JSON_OBJECT('preference_key', p_preference_key, 'preference_value', p_preference_value),
        CONCAT('Updated preference: ', p_preference_key),
        NULL, NULL, NULL
    );
END //

-- Procedure to create notification
CREATE PROCEDURE create_dashboard_notification(
    IN p_user_id INT,
    IN p_title VARCHAR(255),
    IN p_message TEXT,
    IN p_notification_type ENUM('info', 'success', 'warning', 'error', 'system'),
    IN p_priority ENUM('low', 'medium', 'high', 'urgent'),
    IN p_action_url VARCHAR(500),
    IN p_action_text VARCHAR(100),
    IN p_icon VARCHAR(50),
    IN p_expires_at TIMESTAMP
)
BEGIN
    DECLARE v_notification_id INT;
    
    INSERT INTO dashboard_notifications (
        user_id, title, message, notification_type, priority, 
        action_url, action_text, icon, expires_at
    ) VALUES (
        p_user_id, p_title, p_message, p_notification_type, p_priority,
        p_action_url, p_action_text, p_icon, p_expires_at
    );
    
    SET v_notification_id = LAST_INSERT_ID();
    
    SELECT v_notification_id as notification_id;
END //

-- Procedure to get user dashboard data
CREATE PROCEDURE get_user_dashboard_data(IN p_user_id INT)
BEGIN
    -- Get user information
    SELECT u.*, ur.role_name as dashboard_role
    FROM users u
    LEFT JOIN dashboard_roles ur ON u.role = ur.role_name
    WHERE u.id = p_user_id;
    
    -- Get user notifications
    SELECT * FROM dashboard_notifications 
    WHERE user_id = p_user_id AND is_read = FALSE 
    ORDER BY priority DESC, created_at DESC;
    
    -- Get user favorites
    SELECT * FROM dashboard_favorites 
    WHERE user_id = p_user_id 
    ORDER BY created_at DESC;
    
    -- Get user quick actions
    SELECT * FROM dashboard_quick_actions 
    WHERE user_id = p_user_id AND is_active = TRUE 
    ORDER BY display_order ASC;
    
    -- Get recent activities
    SELECT * FROM dashboard_activity_logs 
    WHERE user_id = p_user_id 
    ORDER BY created_at DESC LIMIT 10;
END //

-- Procedure to get dashboard statistics
CREATE PROCEDURE get_dashboard_statistics(IN p_user_role VARCHAR(100))
BEGIN
    -- Get statistics based on user role
    CASE p_user_role
        WHEN 'Student' THEN
            SELECT 
                COUNT(*) as total_students,
                (SELECT COUNT(*) FROM dashboard_notifications WHERE user_id = p_user_id AND is_read = FALSE) as unread_notifications,
                (SELECT COUNT(*) FROM dashboard_favorites WHERE user_id = p_user_id) as total_favorites,
                (SELECT COUNT(*) FROM dashboard_activity_logs WHERE user_id = p_user_id AND DATE(created_at) = CURDATE()) as today_activities;
        WHEN 'Director General' THEN
            SELECT 
                (SELECT COUNT(*) FROM users WHERE type = 'student') as total_students,
                (SELECT COUNT(*) FROM users WHERE type = 'staff') as total_staff,
                (SELECT COUNT(*) FROM dashboard_notifications WHERE is_read = FALSE) as total_unread_notifications,
                (SELECT COUNT(*) FROM dashboard_activity_logs WHERE DATE(created_at) = CURDATE()) as today_activities;
        ELSE
            SELECT 
                (SELECT COUNT(*) FROM users WHERE type = 'student') as total_students,
                (SELECT COUNT(*) FROM users WHERE type = 'staff') as total_staff,
                (SELECT COUNT(*) FROM dashboard_notifications WHERE user_id = p_user_id AND is_read = FALSE) as unread_notifications,
                (SELECT COUNT(*) FROM dashboard_activity_logs WHERE user_id = p_user_id AND DATE(created_at) = CURDATE()) as today_activities;
    END CASE;
END //

DELIMITER ;

-- =====================================================
-- CREATE VIEWS FOR DASHBOARD OPERATIONS
-- =====================================================

-- View for user dashboard summary
CREATE VIEW user_dashboard_summary AS
SELECT 
    u.id as user_id,
    u.full_name,
    u.role,
    u.type,
    COUNT(DISTINCT dn.id) as unread_notifications,
    COUNT(DISTINCT df.id) as total_favorites,
    COUNT(DISTINCT dqa.id) as quick_actions,
    COUNT(DISTINCT dal.id) as recent_activities,
    MAX(dal.created_at) as last_activity
FROM users u
LEFT JOIN dashboard_notifications dn ON u.id = dn.user_id AND dn.is_read = FALSE
LEFT JOIN dashboard_favorites df ON u.id = df.user_id
LEFT JOIN dashboard_quick_actions dqa ON u.id = dqa.user_id AND dqa.is_active = TRUE
LEFT JOIN dashboard_activity_logs dal ON u.id = dal.user_id AND DATE(dal.created_at) = CURDATE()
GROUP BY u.id, u.full_name, u.role, u.type;

-- View for dashboard activity summary
CREATE VIEW dashboard_activity_summary AS
SELECT 
    DATE(created_at) as activity_date,
    action,
    entity_type,
    COUNT(*) as activity_count,
    COUNT(DISTINCT user_id) as unique_users
FROM dashboard_activity_logs
WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY DATE(created_at), action, entity_type
ORDER BY activity_date DESC, activity_count DESC;

-- View for dashboard usage statistics
CREATE VIEW dashboard_usage_statistics AS
SELECT 
    u.role,
    COUNT(DISTINCT u.id) as total_users,
    COUNT(DISTINCT ds.session_id) as total_sessions,
    AVG(ds.session_duration) as avg_session_duration,
    COUNT(DISTINCT dal.id) as total_activities,
    COUNT(DISTINCT dpl.id) as total_prints,
    COUNT(DISTINCT dsl.id) as total_sends
FROM users u
LEFT JOIN dashboard_sessions ds ON u.id = ds.user_id
LEFT JOIN dashboard_activity_logs dal ON u.id = dal.user_id
LEFT JOIN dashboard_print_logs dpl ON u.id = dpl.user_id
LEFT JOIN dashboard_send_logs dsl ON u.id = dsl.user_id
GROUP BY u.role;

-- =====================================================
-- TRIGGERS FOR AUTOMATIC LOGGING
-- =====================================================

DELIMITER //

-- Trigger to log user session start
CREATE TRIGGER after_session_insert
AFTER INSERT ON dashboard_sessions
FOR EACH ROW
BEGIN
    CALL log_dashboard_activity(
        NEW.user_id, 'login', 'session', NEW.id, NULL, NULL,
        CONCAT('User session started: ', NEW.session_id),
        NEW.ip_address, NEW.user_agent, NEW.session_id
    );
END //

-- Trigger to log user session end
CREATE TRIGGER before_session_update
BEFORE UPDATE ON dashboard_sessions
FOR EACH ROW
BEGIN
    IF NEW.is_active = FALSE AND OLD.is_active = TRUE THEN
        CALL log_dashboard_activity(
            NEW.user_id, 'logout', 'session', NEW.id, NULL, NULL,
            CONCAT('User session ended: ', NEW.session_id, ' - Reason: ', NEW.logout_reason),
            NEW.ip_address, NEW.user_agent, NEW.session_id
        );
    END IF;
END //

DELIMITER ;

-- =====================================================
-- FINAL SETUP COMPLETE
-- =====================================================

-- Grant necessary permissions for stored procedures
GRANT EXECUTE ON PROCEDURE log_dashboard_activity TO 'root'@'localhost';
GRANT EXECUTE ON PROCEDURE add_dashboard_comment TO 'root'@'localhost';
GRANT EXECUTE ON PROCEDURE log_dashboard_print TO 'root'@'localhost';
GRANT EXECUTE ON PROCEDURE log_dashboard_send TO 'root'@'localhost';
GRANT EXECUTE ON PROCEDURE add_dashboard_favorite TO 'root'@'localhost';
GRANT EXECUTE ON PROCEDURE update_dashboard_preference TO 'root'@'localhost';
GRANT EXECUTE ON PROCEDURE create_dashboard_notification TO 'root'@'localhost';
GRANT EXECUTE ON PROCEDURE get_user_dashboard_data TO 'root'@'localhost';
GRANT EXECUTE ON PROCEDURE get_dashboard_statistics TO 'root'@'localhost';

-- Insert default user preferences for all existing users
INSERT INTO dashboard_user_preferences (user_id, preference_key, preference_value, category, is_system_default)
SELECT 
    id as user_id,
    'theme' as preference_key,
    '{"theme": "light", "sidebar": "expanded", "notifications": "enabled"}' as preference_value,
    'ui' as category,
    TRUE as is_system_default
FROM users 
WHERE id NOT IN (SELECT user_id FROM dashboard_user_preferences WHERE preference_key = 'theme');

INSERT INTO dashboard_user_preferences (user_id, preference_key, preference_value, category, is_system_default)
SELECT 
    id as user_id,
    'notifications' as preference_key,
    '{"email": true, "sms": false, "push": true, "desktop": true}' as preference_value,
    'notifications' as category,
    TRUE as is_system_default
FROM users 
WHERE id NOT IN (SELECT user_id FROM dashboard_user_preferences WHERE preference_key = 'notifications');

-- Create default quick actions for different user roles
INSERT INTO dashboard_quick_actions (user_id, action_name, action_type, action_url, action_icon, action_color, display_order)
SELECT 
    u.id as user_id,
    CASE 
        WHEN u.type = 'student' THEN 'View Profile'
        WHEN u.role IN ('Director General', 'Principal', 'Director Academics') THEN 'Manage Students'
        WHEN u.role IN ('Accountant', 'Director Finance', 'Bursar') THEN 'Financial Reports'
        ELSE 'Dashboard'
    END as action_name,
    CASE 
        WHEN u.type = 'student' THEN 'view_profile'
        WHEN u.role IN ('Director General', 'Principal', 'Director Academics') THEN 'manage_students'
        WHEN u.role IN ('Accountant', 'Director Finance', 'Bursar') THEN 'financial_reports'
        ELSE 'dashboard'
    END as action_type,
    CASE 
        WHEN u.type = 'student' THEN '/student-profile.php'
        WHEN u.role IN ('Director General', 'Principal', 'Director Academics') THEN '/manage-students.php'
        WHEN u.role IN ('Accountant', 'Director Finance', 'Bursar') THEN '/financial-reports.php'
        ELSE '/dashboard.php'
    END as action_url,
    CASE 
        WHEN u.type = 'student' THEN 'fas fa-user'
        WHEN u.role IN ('Director General', 'Principal', 'Director Academics') THEN 'fas fa-users'
        WHEN u.role IN ('Accountant', 'Director Finance', 'Bursar') THEN 'fas fa-chart-line'
        ELSE 'fas fa-tachometer-alt'
    END as action_icon,
    'primary' as action_color,
    1 as display_order
FROM users u
WHERE u.id NOT IN (SELECT user_id FROM dashboard_quick_actions);

-- =====================================================
-- SETUP COMPLETE MESSAGE
-- =====================================================
SELECT 'ISNM Dashboard Operations Setup Complete!' as status,
       COUNT(*) as total_tables_created
FROM information_schema.tables 
WHERE table_schema = 'isnm_db' 
AND table_name LIKE 'dashboard_%';
