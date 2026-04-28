-- ISNM School Management System - Dashboard CRUD Operations
-- Complete SQL for all dashboard operations: Create, Read, Update, Delete, Print, Send, Comment

USE isnm_school;

-- ========================================
-- DASHBOARD CRUD OPERATIONS TABLES
-- ========================================

-- Drop existing tables if they exist to ensure clean creation
DROP TABLE IF EXISTS dashboard_comments;
DROP TABLE IF EXISTS dashboard_print_logs;
DROP TABLE IF EXISTS dashboard_send_logs;
DROP TABLE IF EXISTS dashboard_activity_logs;
DROP TABLE IF EXISTS dashboard_user_preferences;
DROP TABLE IF EXISTS dashboard_notifications;
DROP TABLE IF EXISTS dashboard_quick_actions;
DROP TABLE IF EXISTS dashboard_favorites;

-- Dashboard comments table for commenting on records
CREATE TABLE dashboard_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id INT NOT NULL,
    record_type ENUM('student', 'staff', 'course', 'exam', 'payment', 'announcement', 'document', 'event', 'complaint', 'other') NOT NULL,
    user_id INT NOT NULL,
    comment_text TEXT NOT NULL,
    parent_comment_id INT NULL, -- For threaded comments
    is_private BOOLEAN DEFAULT FALSE,
    mentions JSON, -- JSON array of mentioned user IDs
    attachments JSON, -- JSON array of attachment file paths
    status ENUM('active', 'deleted', 'edited') DEFAULT 'active',
    edited_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (parent_comment_id) REFERENCES dashboard_comments(id),
    INDEX idx_record_id (record_id),
    INDEX idx_record_type (record_type),
    INDEX idx_user_id (user_id),
    INDEX idx_parent_comment_id (parent_comment_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dashboard print logs table for tracking print operations
CREATE TABLE dashboard_print_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id INT NOT NULL,
    record_type ENUM('student', 'staff', 'course', 'exam', 'payment', 'announcement', 'document', 'event', 'report', 'other') NOT NULL,
    user_id INT NOT NULL,
    print_title VARCHAR(255) NOT NULL,
    print_format ENUM('pdf', 'html', 'excel', 'word', 'csv') NOT NULL,
    print_parameters JSON, -- Print parameters and filters
    file_path VARCHAR(500),
    file_size DECIMAL(10,2),
    print_count INT DEFAULT 1,
    ip_address VARCHAR(45),
    user_agent TEXT,
    status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'completed',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_record_id (record_id),
    INDEX idx_record_type (record_type),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dashboard send logs table for tracking send operations
CREATE TABLE dashboard_send_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    record_id INT NOT NULL,
    record_type ENUM('student', 'staff', 'course', 'exam', 'payment', 'announcement', 'document', 'message', 'report', 'other') NOT NULL,
    sender_id INT NOT NULL,
    send_method ENUM('email', 'sms', 'whatsapp', 'internal_message', 'notification') NOT NULL,
    recipient_type ENUM('individual', 'group', 'role', 'department', 'all', 'custom') NOT NULL,
    recipients JSON, -- JSON array of recipient IDs or emails
    subject VARCHAR(255),
    message_text TEXT,
    attachments JSON, -- JSON array of attachment file paths
    send_status ENUM('pending', 'sent', 'delivered', 'failed', 'cancelled') DEFAULT 'pending',
    delivery_details JSON, -- Delivery status per recipient
    error_message TEXT,
    scheduled_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (sender_id) REFERENCES users(id),
    INDEX idx_record_id (record_id),
    INDEX idx_record_type (record_type),
    INDEX idx_sender_id (sender_id),
    INDEX idx_send_status (send_status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dashboard activity logs table for tracking all dashboard activities
CREATE TABLE dashboard_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type ENUM('create', 'read', 'update', 'delete', 'print', 'send', 'comment', 'login', 'logout', 'view', 'download', 'upload', 'export', 'import') NOT NULL,
    record_id INT NULL,
    record_type ENUM('student', 'staff', 'course', 'exam', 'payment', 'announcement', 'document', 'event', 'complaint', 'message', 'report', 'system', 'other') NULL,
    activity_description TEXT NOT NULL,
    old_values JSON, -- Previous values for update operations
    new_values JSON, -- New values for update operations
    ip_address VARCHAR(45),
    user_agent TEXT,
    session_id VARCHAR(255),
    duration_ms INT, -- Time taken for the operation
    status ENUM('success', 'failed', 'warning') DEFAULT 'success',
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_activity_type (activity_type),
    INDEX idx_record_id (record_id),
    INDEX idx_record_type (record_type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dashboard user preferences table for personalizing dashboard experience
CREATE TABLE dashboard_user_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    theme ENUM('light', 'dark', 'auto') DEFAULT 'light',
    language VARCHAR(10) DEFAULT 'en',
    timezone VARCHAR(50) DEFAULT 'Africa/Kampala',
    date_format ENUM('Y-m-d', 'd/m/Y', 'm/d/Y', 'd-M-Y') DEFAULT 'Y-m-d',
    time_format ENUM('24h', '12h') DEFAULT '24h',
    currency VARCHAR(10) DEFAULT 'UGX',
    items_per_page INT DEFAULT 10,
    auto_refresh_interval INT DEFAULT 300, -- seconds
    notification_sound BOOLEAN DEFAULT TRUE,
    email_notifications BOOLEAN DEFAULT TRUE,
    sms_notifications BOOLEAN DEFAULT FALSE,
    dashboard_layout JSON, -- Custom dashboard layout configuration
    favorite_widgets JSON, -- Array of favorite widget IDs
    recent_searches JSON, -- Array of recent search terms
    quick_filters JSON, -- Saved filters for quick access
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dashboard notifications table for real-time notifications
CREATE TABLE dashboard_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notification_type ENUM('info', 'success', 'warning', 'error', 'system', 'message', 'reminder', 'alert', 'deadline') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    reference_id INT NULL,
    reference_type ENUM('student', 'staff', 'course', 'exam', 'payment', 'announcement', 'document', 'event', 'complaint', 'message', 'report', 'system') NULL,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    action_required BOOLEAN DEFAULT FALSE,
    action_url VARCHAR(500),
    action_button_text VARCHAR(100),
    action_deadline TIMESTAMP NULL,
    auto_dismiss BOOLEAN DEFAULT FALSE,
    dismiss_after INT DEFAULT 0, -- seconds, 0 means no auto dismiss
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_notification_type (notification_type),
    INDEX idx_priority (priority),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at),
    INDEX idx_reference_id (reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dashboard quick actions table for frequently used actions
CREATE TABLE dashboard_quick_actions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action_name VARCHAR(100) NOT NULL,
    action_type ENUM('create', 'view', 'edit', 'delete', 'print', 'send', 'export', 'import', 'custom') NOT NULL,
    action_url VARCHAR(500) NOT NULL,
    action_icon VARCHAR(100),
    action_color VARCHAR(20),
    action_order INT DEFAULT 0,
    is_favorite BOOLEAN DEFAULT FALSE,
    use_count INT DEFAULT 0,
    last_used TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_action_type (action_type),
    INDEX idx_is_favorite (is_favorite),
    INDEX idx_action_order (action_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dashboard favorites table for bookmarked records
CREATE TABLE dashboard_favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    record_id INT NOT NULL,
    record_type ENUM('student', 'staff', 'course', 'exam', 'payment', 'announcement', 'document', 'event', 'complaint', 'report', 'other') NOT NULL,
    favorite_name VARCHAR(255),
    notes TEXT,
    is_pinned BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE KEY unique_user_record_favorite (user_id, record_id, record_type),
    INDEX idx_user_id (user_id),
    INDEX idx_record_type (record_type),
    INDEX idx_is_pinned (is_pinned)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- STUDENT DASHBOARD OPERATIONS
-- ========================================

-- Student profile CRUD operations
CREATE OR REPLACE PROCEDURE create_student_profile(
    IN p_student_id INT,
    IN p_full_name VARCHAR(255),
    IN p_email VARCHAR(255),
    IN p_phone VARCHAR(20),
    IN p_date_of_birth DATE,
    IN p_gender VARCHAR(10),
    IN p_address TEXT,
    IN p_emergency_contact_name VARCHAR(255),
    IN p_emergency_contact_phone VARCHAR(20),
    IN p_created_by INT,
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN,
    OUT p_new_student_id INT
)
BEGIN
    DECLARE v_index_number VARCHAR(50);
    DECLARE v_program_code VARCHAR(20);
    
    -- Generate index number based on program
    SET v_program_code = 'CM'; -- Default to Certificate in Midwifery
    SET v_index_number = CONCAT('U001/', v_program_code, '/', LPAD(p_student_id, 3, '0'), '/24');
    
    -- Insert student record
    INSERT INTO users (
        index_number, full_name, email, phone, date_of_birth, gender, address,
        emergency_contact_name, emergency_contact_phone, role, type, status, created_at
    ) VALUES (
        v_index_number, p_full_name, p_email, p_phone, p_date_of_birth, p_gender, p_address,
        p_emergency_contact_name, p_emergency_contact_phone, 'student', 'student', 'active', NOW()
    );
    
    SET p_new_student_id = LAST_INSERT_ID();
    
    -- Log activity
    INSERT INTO dashboard_activity_logs (
        user_id, activity_type, record_id, record_type, activity_description, new_values
    ) VALUES (
        p_created_by, 'create', p_new_student_id, 'student', 
        CONCAT('Created student profile: ', p_full_name),
        JSON_OBJECT('full_name', p_full_name, 'email', p_email, 'index_number', v_index_number)
    );
    
    SET p_result = CONCAT('Student profile created successfully with ID: ', p_new_student_id);
    SET p_success = TRUE;
END //

CREATE OR REPLACE PROCEDURE update_student_profile(
    IN p_student_id INT,
    IN p_full_name VARCHAR(255),
    IN p_email VARCHAR(255),
    IN p_phone VARCHAR(20),
    IN p_address TEXT,
    IN p_emergency_contact_name VARCHAR(255),
    IN p_emergency_contact_phone VARCHAR(20),
    IN p_updated_by INT,
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN
)
BEGIN
    DECLARE v_old_values JSON;
    DECLARE v_new_values JSON;
    
    -- Get old values for logging
    SELECT JSON_OBJECT(
        'full_name', full_name, 'email', email, 'phone', phone,
        'address', address, 'emergency_contact_name', emergency_contact_name,
        'emergency_contact_phone', emergency_contact_phone
    ) INTO v_old_values
    FROM users WHERE id = p_student_id AND type = 'student';
    
    -- Update student record
    UPDATE users 
    SET full_name = p_full_name,
        email = p_email,
        phone = p_phone,
        address = p_address,
        emergency_contact_name = p_emergency_contact_name,
        emergency_contact_phone = p_emergency_contact_phone,
        updated_at = NOW()
    WHERE id = p_student_id AND type = 'student';
    
    -- Get new values for logging
    SELECT JSON_OBJECT(
        'full_name', full_name, 'email', email, 'phone', phone,
        'address', address, 'emergency_contact_name', emergency_contact_name,
        'emergency_contact_phone', emergency_contact_phone
    ) INTO v_new_values
    FROM users WHERE id = p_student_id AND type = 'student';
    
    -- Log activity
    INSERT INTO dashboard_activity_logs (
        user_id, activity_type, record_id, record_type, activity_description, old_values, new_values
    ) VALUES (
        p_updated_by, 'update', p_student_id, 'student',
        CONCAT('Updated student profile: ', p_full_name),
        v_old_values, v_new_values
    );
    
    SET p_result = 'Student profile updated successfully';
    SET p_success = TRUE;
END //

CREATE OR REPLACE PROCEDURE delete_student_profile(
    IN p_student_id INT,
    IN p_deleted_by INT,
    IN p_reason TEXT,
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN
)
BEGIN
    DECLARE v_old_values JSON;
    
    -- Get old values for logging
    SELECT JSON_OBJECT(
        'full_name', full_name, 'index_number', index_number, 'email', email, 'phone', phone
    ) INTO v_old_values
    FROM users WHERE id = p_student_id AND type = 'student';
    
    -- Soft delete student record
    UPDATE users 
    SET status = 'deleted', updated_at = NOW()
    WHERE id = p_student_id AND type = 'student';
    
    -- Log activity
    INSERT INTO dashboard_activity_logs (
        user_id, activity_type, record_id, record_type, activity_description, old_values
    ) VALUES (
        p_deleted_by, 'delete', p_student_id, 'student',
        CONCAT('Deleted student profile: ', p_reason),
        v_old_values
    );
    
    SET p_result = 'Student profile deleted successfully';
    SET p_success = TRUE;
END //

-- Student academic record operations
CREATE OR REPLACE PROCEDURE create_student_academic_record(
    IN p_student_id INT,
    IN p_course_id INT,
    IN p_semester VARCHAR(20),
    IN p_academic_year VARCHAR(9),
    IN p_created_by INT,
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN,
    OUT p_record_id INT
)
BEGIN
    -- Insert academic record
    INSERT INTO student_academic_records (
        student_id, course_id, semester, academic_year, registration_date, status, created_at
    ) VALUES (
        p_student_id, p_course_id, p_semester, p_academic_year, CURDATE(), 'registered', NOW()
    );
    
    SET p_record_id = LAST_INSERT_ID();
    
    -- Log activity
    INSERT INTO dashboard_activity_logs (
        user_id, activity_type, record_id, record_type, activity_description, new_values
    ) VALUES (
        p_created_by, 'create', p_record_id, 'student',
        CONCAT('Created academic record for student ID: ', p_student_id),
        JSON_OBJECT('student_id', p_student_id, 'course_id', p_course_id, 'semester', p_semester)
    );
    
    SET p_result = 'Academic record created successfully';
    SET p_success = TRUE;
END //

-- Student comment operations
CREATE OR REPLACE PROCEDURE add_student_comment(
    IN p_student_id INT,
    IN p_user_id INT,
    IN p_comment_text TEXT,
    IN p_parent_comment_id INT,
    IN p_is_private BOOLEAN,
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN,
    OUT p_comment_id INT
)
BEGIN
    -- Insert comment
    INSERT INTO dashboard_comments (
        record_id, record_type, user_id, comment_text, parent_comment_id, is_private
    ) VALUES (
        p_student_id, 'student', p_user_id, p_comment_text, p_parent_comment_id, p_is_private
    );
    
    SET p_comment_id = LAST_INSERT_ID();
    
    -- Log activity
    INSERT INTO dashboard_activity_logs (
        user_id, activity_type, record_id, record_type, activity_description, new_values
    ) VALUES (
        p_user_id, 'comment', p_student_id, 'student',
        CONCAT('Added comment to student ID: ', p_student_id),
        JSON_OBJECT('comment_text', p_comment_text, 'is_private', p_is_private)
    );
    
    SET p_result = 'Comment added successfully';
    SET p_success = TRUE;
END //

-- Student print operations
CREATE OR REPLACE PROCEDURE print_student_record(
    IN p_student_id INT,
    IN p_user_id INT,
    IN p_print_title VARCHAR(255),
    IN p_print_format VARCHAR(20),
    IN p_print_parameters JSON,
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN,
    OUT p_print_log_id INT
)
BEGIN
    -- Insert print log
    INSERT INTO dashboard_print_logs (
        record_id, record_type, user_id, print_title, print_format, print_parameters, ip_address, user_agent
    ) VALUES (
        p_student_id, 'student', p_user_id, p_print_title, p_print_format, p_print_parameters,
        '127.0.0.1', 'Mozilla/5.0'
    );
    
    SET p_print_log_id = LAST_INSERT_ID();
    
    -- Log activity
    INSERT INTO dashboard_activity_logs (
        user_id, activity_type, record_id, record_type, activity_description, new_values
    ) VALUES (
        p_user_id, 'print', p_student_id, 'student',
        CONCAT('Printed student record: ', p_print_title),
        JSON_OBJECT('print_format', p_print_format, 'print_title', p_print_title)
    );
    
    SET p_result = 'Print job queued successfully';
    SET p_success = TRUE;
END //

-- Student send operations
CREATE OR REPLACE PROCEDURE send_student_record(
    IN p_student_id INT,
    IN p_sender_id INT,
    IN p_send_method VARCHAR(20),
    IN p_recipients JSON,
    IN p_subject VARCHAR(255),
    IN p_message_text TEXT,
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN,
    OUT p_send_log_id INT
)
BEGIN
    -- Insert send log
    INSERT INTO dashboard_send_logs (
        record_id, record_type, sender_id, send_method, recipient_type, recipients, subject, message_text, send_status
    ) VALUES (
        p_student_id, 'student', p_sender_id, p_send_method, 'individual', p_recipients, p_subject, p_message_text, 'pending'
    );
    
    SET p_send_log_id = LAST_INSERT_ID();
    
    -- Log activity
    INSERT INTO dashboard_activity_logs (
        user_id, activity_type, record_id, record_type, activity_description, new_values
    ) VALUES (
        p_sender_id, 'send', p_student_id, 'student',
        CONCAT('Sent student record via ', p_send_method),
        JSON_OBJECT('send_method', p_send_method, 'subject', p_subject, 'recipients_count', JSON_LENGTH(p_recipients))
    );
    
    SET p_result = 'Send operation queued successfully';
    SET p_success = TRUE;
END //

-- ========================================
-- STAFF DASHBOARD OPERATIONS
-- ========================================

-- Staff profile CRUD operations
CREATE OR REPLACE PROCEDURE create_staff_profile(
    IN p_full_name VARCHAR(255),
    IN p_email VARCHAR(255),
    IN p_phone VARCHAR(20),
    IN p_password VARCHAR(255),
    IN p_role VARCHAR(100),
    IN p_created_by INT,
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN,
    OUT p_new_staff_id INT
)
BEGIN
    DECLARE v_email_count INT DEFAULT 0;
    
    -- Check if email already exists
    SELECT COUNT(*) INTO v_email_count
    FROM users WHERE email = p_email AND type = 'staff';
    
    IF v_email_count > 0 THEN
        SET p_result = 'Email already exists';
        SET p_success = FALSE;
        SET p_new_staff_id = NULL;
    ELSE
        -- Insert staff record
        INSERT INTO users (
            full_name, email, phone, password, role, type, status, created_at
        ) VALUES (
            p_full_name, p_email, p_phone, PASSWORD_HASH(p_password), p_role, 'staff', 'active', NOW()
        );
        
        SET p_new_staff_id = LAST_INSERT_ID();
        
        -- Log activity
        INSERT INTO dashboard_activity_logs (
            user_id, activity_type, record_id, record_type, activity_description, new_values
        ) VALUES (
            p_created_by, 'create', p_new_staff_id, 'staff',
            CONCAT('Created staff profile: ', p_full_name),
            JSON_OBJECT('full_name', p_full_name, 'email', p_email, 'role', p_role)
        );
        
        SET p_result = CONCAT('Staff profile created successfully with ID: ', p_new_staff_id);
        SET p_success = TRUE;
    END IF;
END //

CREATE OR REPLACE PROCEDURE update_staff_profile(
    IN p_staff_id INT,
    IN p_full_name VARCHAR(255),
    IN p_email VARCHAR(255),
    IN p_phone VARCHAR(20),
    IN p_role VARCHAR(100),
    IN p_updated_by INT,
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN
)
BEGIN
    DECLARE v_old_values JSON;
    DECLARE v_new_values JSON;
    
    -- Get old values for logging
    SELECT JSON_OBJECT(
        'full_name', full_name, 'email', email, 'phone', phone, 'role', role
    ) INTO v_old_values
    FROM users WHERE id = p_staff_id AND type = 'staff';
    
    -- Update staff record
    UPDATE users 
    SET full_name = p_full_name,
        email = p_email,
        phone = p_phone,
        role = p_role,
        updated_at = NOW()
    WHERE id = p_staff_id AND type = 'staff';
    
    -- Get new values for logging
    SELECT JSON_OBJECT(
        'full_name', full_name, 'email', email, 'phone', phone, 'role', role
    ) INTO v_new_values
    FROM users WHERE id = p_staff_id AND type = 'staff';
    
    -- Log activity
    INSERT INTO dashboard_activity_logs (
        user_id, activity_type, record_id, record_type, activity_description, old_values, new_values
    ) VALUES (
        p_updated_by, 'update', p_staff_id, 'staff',
        CONCAT('Updated staff profile: ', p_full_name),
        v_old_values, v_new_values
    );
    
    SET p_result = 'Staff profile updated successfully';
    SET p_success = TRUE;
END //

-- ========================================
-- COURSE MANAGEMENT OPERATIONS
-- ========================================

CREATE OR REPLACE PROCEDURE create_course(
    IN p_course_code VARCHAR(20),
    IN p_course_name VARCHAR(255),
    IN p_program_id INT,
    IN p_semester VARCHAR(20),
    IN p_credits DECIMAL(4,1),
    IN p_description TEXT,
    IN p_created_by INT,
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN,
    OUT p_course_id INT
)
BEGIN
    DECLARE v_course_count INT DEFAULT 0;
    
    -- Check if course code already exists
    SELECT COUNT(*) INTO v_course_count
    FROM courses WHERE course_code = p_course_code;
    
    IF v_course_count > 0 THEN
        SET p_result = 'Course code already exists';
        SET p_success = FALSE;
        SET p_course_id = NULL;
    ELSE
        -- Insert course
        INSERT INTO courses (
            course_code, course_name, program_id, semester, credits, description, created_by, status
        ) VALUES (
            p_course_code, p_course_name, p_program_id, p_semester, p_credits, p_description, p_created_by, 'active'
        );
        
        SET p_course_id = LAST_INSERT_ID();
        
        -- Log activity
        INSERT INTO dashboard_activity_logs (
            user_id, activity_type, record_id, record_type, activity_description, new_values
        ) VALUES (
            p_created_by, 'create', p_course_id, 'course',
            CONCAT('Created course: ', p_course_name),
            JSON_OBJECT('course_code', p_course_code, 'course_name', p_course_name, 'credits', p_credits)
        );
        
        SET p_result = CONCAT('Course created successfully with ID: ', p_course_id);
        SET p_success = TRUE;
    END IF;
END //

CREATE OR REPLACE PROCEDURE update_course(
    IN p_course_id INT,
    IN p_course_name VARCHAR(255),
    IN p_program_id INT,
    IN p_semester VARCHAR(20),
    IN p_credits DECIMAL(4,1),
    IN p_description TEXT,
    IN p_updated_by INT,
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN
)
BEGIN
    DECLARE v_old_values JSON;
    DECLARE v_new_values JSON;
    
    -- Get old values for logging
    SELECT JSON_OBJECT(
        'course_name', course_name, 'program_id', program_id, 'semester', semester, 'credits', credits
    ) INTO v_old_values
    FROM courses WHERE id = p_course_id;
    
    -- Update course
    UPDATE courses 
    SET course_name = p_course_name,
        program_id = p_program_id,
        semester = p_semester,
        credits = p_credits,
        description = p_description,
        updated_at = NOW()
    WHERE id = p_course_id;
    
    -- Get new values for logging
    SELECT JSON_OBJECT(
        'course_name', course_name, 'program_id', program_id, 'semester', semester, 'credits', credits
    ) INTO v_new_values
    FROM courses WHERE id = p_course_id;
    
    -- Log activity
    INSERT INTO dashboard_activity_logs (
        user_id, activity_type, record_id, record_type, activity_description, old_values, new_values
    ) VALUES (
        p_updated_by, 'update', p_course_id, 'course',
        CONCAT('Updated course: ', p_course_name),
        v_old_values, v_new_values
    );
    
    SET p_result = 'Course updated successfully';
    SET p_success = TRUE;
END //

-- ========================================
-- EXAMINATION MANAGEMENT OPERATIONS
-- ========================================

CREATE OR REPLACE PROCEDURE create_examination(
    IN p_course_id INT,
    IN p_exam_name VARCHAR(255),
    IN p_exam_type VARCHAR(20),
    IN p_total_marks DECIMAL(5,2),
    IN p_passing_marks DECIMAL(5,2),
    IN p_exam_date DATE,
    IN p_exam_duration INT,
    IN p_created_by INT,
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN,
    OUT p_exam_id INT
)
BEGIN
    -- Insert examination
    INSERT INTO examinations (
        course_id, exam_name, exam_type, total_marks, passing_marks, exam_date, exam_duration, created_by, status
    ) VALUES (
        p_course_id, p_exam_name, p_exam_type, p_total_marks, p_passing_marks, p_exam_date, p_exam_duration, p_created_by, 'scheduled'
    );
    
    SET p_exam_id = LAST_INSERT_ID();
    
    -- Log activity
    INSERT INTO dashboard_activity_logs (
        user_id, activity_type, record_id, record_type, activity_description, new_values
    ) VALUES (
        p_created_by, 'create', p_exam_id, 'exam',
        CONCAT('Created examination: ', p_exam_name),
        JSON_OBJECT('exam_name', p_exam_name, 'exam_type', p_exam_type, 'total_marks', p_total_marks)
    );
    
    SET p_result = CONCAT('Examination created successfully with ID: ', p_exam_id);
    SET p_success = TRUE;
END //

-- ========================================
-- PAYMENT MANAGEMENT OPERATIONS
-- ========================================

CREATE OR REPLACE PROCEDURE create_payment_transaction(
    IN p_student_id INT,
    IN p_fee_account_id INT,
    IN p_amount DECIMAL(10,2),
    IN p_payment_method VARCHAR(20),
    IN p_receipt_number VARCHAR(100),
    IN p_paid_by VARCHAR(255),
    IN p_collected_by INT,
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN,
    OUT p_transaction_id INT
)
BEGIN
    DECLARE v_transaction_id VARCHAR(100);
    DECLARE v_balance DECIMAL(10,2);
    
    -- Generate transaction ID
    SET v_transaction_id = CONCAT('TXN', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'), LPAD(p_student_id, 6, '0'));
    
    -- Get current balance
    SELECT balance INTO v_balance
    FROM student_fee_accounts 
    WHERE id = p_fee_account_id AND student_id = p_student_id;
    
    -- Insert payment transaction
    INSERT INTO payment_transactions (
        student_id, fee_account_id, transaction_id, amount, payment_method, receipt_number, paid_by, collected_by, status
    ) VALUES (
        p_student_id, p_fee_account_id, v_transaction_id, p_amount, p_payment_method, p_receipt_number, p_paid_by, p_collected_by, 'completed'
    );
    
    SET p_transaction_id = LAST_INSERT_ID();
    
    -- Update fee account
    UPDATE student_fee_accounts 
    SET amount_paid = amount_paid + p_amount,
        last_payment_date = NOW(),
        payment_status = CASE 
            WHEN (balance - p_amount) <= 0 THEN 'paid'
            WHEN amount_paid + p_amount > 0 THEN 'partial'
            ELSE 'unpaid'
        END,
        updated_at = NOW()
    WHERE id = p_fee_account_id;
    
    -- Log activity
    INSERT INTO dashboard_activity_logs (
        user_id, activity_type, record_id, record_type, activity_description, new_values
    ) VALUES (
        p_collected_by, 'create', p_transaction_id, 'payment',
        CONCAT('Payment transaction created: ', v_transaction_id),
        JSON_OBJECT('amount', p_amount, 'payment_method', p_payment_method, 'receipt_number', p_receipt_number)
    );
    
    SET p_result = CONCAT('Payment transaction created successfully with ID: ', p_transaction_id);
    SET p_success = TRUE;
END //

-- ========================================
-- DASHBOARD UTILITY PROCEDURES
-- ========================================

-- Procedure to get student dashboard data
CREATE OR REPLACE PROCEDURE get_student_dashboard_data(
    IN p_student_id INT
)
BEGIN
    -- Get student basic info
    SELECT 
        u.id,
        u.full_name,
        u.index_number,
        u.email,
        u.phone,
        u.last_login,
        CASE 
            WHEN u.index_number LIKE '%/CM/%' THEN 'Certificate in Midwifery'
            WHEN u.index_number LIKE '%/CN/%' THEN 'Certificate in Nursing'
            WHEN u.index_number LIKE '%/DMORDN/%' THEN 'Diploma in Midwifery'
            ELSE 'Unknown Program'
        END as program
    FROM users u
    WHERE u.id = p_student_id AND u.type = 'student';
    
    -- Get academic statistics
    SELECT 
        COUNT(DISTINCT sar.course_id) as total_courses,
        COUNT(CASE WHEN sar.status = 'completed' THEN 1 END) as completed_courses,
        AVG(CASE WHEN sar.status = 'completed' THEN sar.gpa_points END) as current_gpa,
        COUNT(CASE WHEN sar.status = 'in_progress' THEN 1 END) as in_progress_courses
    FROM student_academic_records sar
    WHERE sar.student_id = p_student_id;
    
    -- Get fee summary
    SELECT 
        SUM(total_fee) as total_fees,
        SUM(amount_paid) as total_paid,
        SUM(balance) as total_balance,
        COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_semesters,
        COUNT(CASE WHEN payment_status = 'unpaid' THEN 1 END) as unpaid_semesters
    FROM student_fee_accounts
    WHERE student_id = p_student_id;
    
    -- Get attendance summary
    SELECT 
        COUNT(*) as total_classes,
        COUNT(CASE WHEN ar.attendance_status = 'present' THEN 1 END) as present_classes,
        COUNT(CASE WHEN ar.attendance_status = 'absent' THEN 1 END) as absent_classes,
        ROUND((COUNT(CASE WHEN ar.attendance_status = 'present' THEN 1 END) * 100.0) / COUNT(*), 2) as attendance_rate
    FROM attendance_records ar
    WHERE ar.student_id = p_student_id AND ar.attendance_date >= DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    -- Get recent notifications
    SELECT 
        n.id,
        n.title,
        n.message,
        n.priority,
        n.is_read,
        n.created_at
    FROM notifications n
    WHERE n.user_id = p_student_id AND n.is_read = FALSE
    ORDER BY n.priority DESC, n.created_at DESC
    LIMIT 5;
    
    -- Get recent activities
    SELECT 
        al.activity_type,
        al.activity_description,
        al.created_at
    FROM dashboard_activity_logs al
    WHERE al.user_id = p_student_id
    ORDER BY al.created_at DESC
    LIMIT 10;
END //

-- Procedure to get staff dashboard data
CREATE OR REPLACE PROCEDURE get_staff_dashboard_data(
    IN p_staff_id INT
)
BEGIN
    -- Get staff basic info
    SELECT 
        u.id,
        u.full_name,
        u.email,
        u.phone,
        u.role,
        u.last_login,
        u.created_at as employment_date
    FROM users u
    WHERE u.id = p_staff_id AND u.type = 'staff';
    
    -- Get academic statistics
    SELECT 
        COUNT(DISTINCT c.id) as courses_taught,
        COUNT(DISTINCT sar.student_id) as students_supervised,
        COUNT(DISTINCT e.id) as exams_conducted,
        COUNT(DISTINCT ar.id) as attendance_marked
    FROM users u
    LEFT JOIN courses c ON u.id = c.created_by
    LEFT JOIN student_academic_records sar ON u.id = (SELECT marked_by FROM attendance_records WHERE student_id = sar.student_id LIMIT 1)
    LEFT JOIN examinations e ON u.id = e.created_by
    LEFT JOIN attendance_records ar ON u.id = ar.marked_by
    WHERE u.id = p_staff_id;
    
    -- Get financial statistics
    SELECT 
        COUNT(pt.id) as total_transactions,
        COALESCE(SUM(pt.amount), 0) as total_collected,
        COUNT(CASE WHEN pt.payment_method = 'cash' THEN 1 END) as cash_payments,
        COUNT(CASE WHEN pt.payment_method = 'bank_transfer' THEN 1 END) as bank_transfers,
        COUNT(CASE WHEN pt.payment_method = 'mobile_money' THEN 1 END) as mobile_money_payments
    FROM payment_transactions pt
    WHERE pt.collected_by = p_staff_id AND pt.status = 'completed';
    
    -- Get communication statistics
    SELECT 
        COUNT(m.id) as total_messages,
        COUNT(CASE WHEN m.message_type = 'broadcast' THEN 1 END) as broadcast_messages,
        COUNT(CASE WHEN m.message_type = 'individual' THEN 1 END) as individual_messages
    FROM messages m
    WHERE m.sender_id = p_staff_id;
    
    -- Get recent notifications
    SELECT 
        n.id,
        n.title,
        n.message,
        n.priority,
        n.is_read,
        n.created_at
    FROM notifications n
    WHERE n.user_id = p_staff_id AND n.is_read = FALSE
    ORDER BY n.priority DESC, n.created_at DESC
    LIMIT 5;
    
    -- Get recent activities
    SELECT 
        al.activity_type,
        al.activity_description,
        al.created_at
    FROM dashboard_activity_logs al
    WHERE al.user_id = p_staff_id
    ORDER BY al.created_at DESC
    LIMIT 10;
END //

-- Procedure to add favorite record
CREATE OR REPLACE PROCEDURE add_favorite_record(
    IN p_user_id INT,
    IN p_record_id INT,
    IN p_record_type VARCHAR(20),
    IN p_favorite_name VARCHAR(255),
    IN p_notes TEXT,
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN
)
BEGIN
    -- Insert favorite record
    INSERT INTO dashboard_favorites (
        user_id, record_id, record_type, favorite_name, notes
    ) VALUES (
        p_user_id, p_record_id, p_record_type, p_favorite_name, p_notes
    )
    ON DUPLICATE KEY UPDATE
        favorite_name = VALUES(favorite_name),
        notes = VALUES(notes),
        updated_at = NOW();
    
    -- Log activity
    INSERT INTO dashboard_activity_logs (
        user_id, activity_type, record_id, record_type, activity_description, new_values
    ) VALUES (
        p_user_id, 'create', p_record_id, p_record_type,
        CONCAT('Added favorite: ', p_favorite_name),
        JSON_OBJECT('record_type', p_record_type, 'favorite_name', p_favorite_name)
    );
    
    SET p_result = 'Favorite record added successfully';
    SET p_success = TRUE;
END //

-- Procedure to update user preferences
CREATE OR REPLACE PROCEDURE update_user_preferences(
    IN p_user_id INT,
    IN p_theme VARCHAR(10),
    IN p_language VARCHAR(10),
    IN p_timezone VARCHAR(50),
    IN p_items_per_page INT,
    IN p_email_notifications BOOLEAN,
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN
)
BEGIN
    -- Update or insert user preferences
    INSERT INTO dashboard_user_preferences (
        user_id, theme, language, timezone, items_per_page, email_notifications
    ) VALUES (
        p_user_id, p_theme, p_language, p_timezone, p_items_per_page, p_email_notifications
    )
    ON DUPLICATE KEY UPDATE
        theme = VALUES(theme),
        language = VALUES(language),
        timezone = VALUES(timezone),
        items_per_page = VALUES(items_per_page),
        email_notifications = VALUES(email_notifications),
        updated_at = NOW();
    
    SET p_result = 'User preferences updated successfully';
    SET p_success = TRUE;
END //

DELIMITER ;

-- Success message
SELECT 'Dashboard CRUD operations SQL created successfully!' as message;
SELECT 'All dashboard operations (Create, Read, Update, Delete, Print, Send, Comment) are ready for use' as note;
