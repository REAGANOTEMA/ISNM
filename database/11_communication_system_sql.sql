-- ISNM School Management System - Communication System SQL
-- Comprehensive SQL for messaging, notifications, announcements, and communication management

USE isnm_db;

-- ========================================
-- COMMUNICATION SYSTEM TABLES
-- ========================================

-- Drop existing tables if they exist to ensure clean creation
DROP TABLE IF EXISTS message_attachments;
DROP TABLE IF EXISTS message_recipients;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS announcement_categories;
DROP TABLE IF EXISTS announcements;
DROP TABLE IF EXISTS communication_logs;

-- Announcement categories table
CREATE TABLE announcement_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_code VARCHAR(20) NOT NULL UNIQUE,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50),
    color VARCHAR(20),
    is_system BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_category_code (category_code),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Announcements table
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    category_id INT NOT NULL,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    target_audience ENUM('all', 'students', 'staff', 'management', 'specific_program', 'specific_role') NOT NULL,
    program_filter VARCHAR(20) NULL, -- For specific_program audience
    role_filter VARCHAR(100) NULL, -- For specific_role audience
    start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_date TIMESTAMP NULL,
    is_pinned BOOLEAN DEFAULT FALSE,
    allow_comments BOOLEAN DEFAULT TRUE,
    requires_acknowledgment BOOLEAN DEFAULT FALSE,
    created_by INT NOT NULL,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    status ENUM('draft', 'published', 'archived', 'expired') DEFAULT 'draft',
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (category_id) REFERENCES announcement_categories(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (approved_by) REFERENCES users(id),
    INDEX idx_category_id (category_id),
    INDEX idx_priority (priority),
    INDEX idx_target_audience (target_audience),
    INDEX idx_start_date (start_date),
    INDEX idx_end_date (end_date),
    INDEX idx_status (status),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Messages table
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(255) NOT NULL,
    message_text TEXT NOT NULL,
    message_type ENUM('individual', 'broadcast', 'announcement', 'notice', 'reminder', 'alert') NOT NULL,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    sender_id INT NOT NULL,
    parent_message_id INT NULL, -- For reply threads
    thread_id INT NULL, -- For grouping related messages
    is_system_message BOOLEAN DEFAULT FALSE,
    auto_reply BOOLEAN DEFAULT FALSE,
    scheduled_send_time TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    allow_reply BOOLEAN DEFAULT TRUE,
    require_read_receipt BOOLEAN DEFAULT FALSE,
    status ENUM('draft', 'sent', 'delivered', 'read', 'archived', 'deleted') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (sender_id) REFERENCES users(id),
    FOREIGN KEY (parent_message_id) REFERENCES messages(id),
    INDEX idx_sender_id (sender_id),
    INDEX idx_message_type (message_type),
    INDEX idx_priority (priority),
    INDEX idx_parent_message_id (parent_message_id),
    INDEX idx_thread_id (thread_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Message recipients table (for individual and targeted messages)
CREATE TABLE message_recipients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    recipient_id INT NOT NULL,
    recipient_type ENUM('user', 'role', 'program', 'department') NOT NULL,
    recipient_value VARCHAR(100) NOT NULL, -- User ID, role name, program code, etc.
    delivery_status ENUM('pending', 'delivered', 'read', 'failed', 'bounced') DEFAULT 'pending',
    read_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    failed_reason TEXT,
    read_receipt_sent BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    FOREIGN KEY (recipient_id) REFERENCES users(id),
    UNIQUE KEY unique_message_recipient (message_id, recipient_id),
    INDEX idx_message_id (message_id),
    INDEX idx_recipient_id (recipient_id),
    INDEX idx_delivery_status (delivery_status),
    INDEX idx_read_at (read_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Message attachments table
CREATE TABLE message_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size DECIMAL(10,2),
    file_type VARCHAR(100),
    mime_type VARCHAR(100),
    download_count INT DEFAULT 0,
    is_embedded BOOLEAN DEFAULT FALSE,
    uploaded_by INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id),
    INDEX idx_message_id (message_id),
    INDEX idx_filename (filename),
    INDEX idx_file_type (file_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    notification_type ENUM('message', 'announcement', 'payment_reminder', 'exam_result', 'attendance', 'system', 'deadline', 'alert') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    reference_id INT NULL, -- Reference to related record (message_id, announcement_id, etc.)
    reference_type VARCHAR(50) NULL, -- Type of reference (message, announcement, etc.)
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    action_required BOOLEAN DEFAULT FALSE,
    action_url VARCHAR(500),
    action_button_text VARCHAR(100),
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_notification_type (notification_type),
    INDEX idx_priority (priority),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at),
    INDEX idx_reference_id (reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Communication logs table
CREATE TABLE communication_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    communication_type ENUM('message_sent', 'message_read', 'announcement_viewed', 'notification_sent', 'email_sent', 'sms_sent') NOT NULL,
    reference_id INT NULL,
    reference_type VARCHAR(50) NULL,
    recipient_count INT DEFAULT 0,
    success BOOLEAN DEFAULT TRUE,
    error_message TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_communication_type (communication_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ========================================
-- INSERT DEFAULT DATA
-- ========================================

-- Insert default announcement categories
INSERT INTO announcement_categories (category_code, category_name, description, icon, color, is_system, created_by) VALUES
('GENERAL', 'General', 'General announcements and notices', 'fas fa-bullhorn', '#007bff', TRUE, 1),
('ACADEMIC', 'Academic', 'Academic-related announcements', 'fas fa-graduation-cap', '#28a745', TRUE, 1),
('EXAMINATION', 'Examination', 'Examination schedules and results', 'fas fa-file-alt', '#ffc107', TRUE, 1),
('EVENT', 'Event', 'School events and activities', 'fas fa-calendar-alt', '#17a2b8', TRUE, 1),
('HOLIDAY', 'Holiday', 'Holiday notices and schedules', 'fas fa-umbrella-beach', '#dc3545', TRUE, 1),
('URGENT', 'Urgent', 'Urgent announcements', 'fas fa-exclamation-triangle', '#fd7e14', TRUE, 1),
('FINANCE', 'Finance', 'Fee payment and financial notices', 'fas fa-money-bill-wave', '#20c997', TRUE, 1),
('HOSTEL', 'Hostel', 'Hostel-related announcements', 'fas fa-bed', '#6f42c1', TRUE, 1),
('LIBRARY', 'Library', 'Library notices and updates', 'fas fa-book', '#e83e8c', TRUE, 1),
('HEALTH', 'Health', 'Health and medical announcements', 'fas fa-heartbeat', '#6c757d', TRUE, 1),
('MAINTENANCE', 'Maintenance', 'System maintenance notices', 'fas fa-tools', '#343a40', TRUE, 1),
('STAFF', 'Staff', 'Staff-only announcements', 'fas fa-users', '#6610f2', FALSE, 1)
ON DUPLICATE KEY UPDATE category_name = VALUES(category_name);

-- ========================================
-- CREATE VIEWS FOR COMMUNICATION REPORTING
-- ========================================

-- Message statistics view
CREATE OR REPLACE VIEW message_statistics AS
SELECT 
    m.id,
    m.subject,
    m.message_type,
    m.priority,
    m.sender_id,
    sender.full_name as sender_name,
    COUNT(mr.recipient_id) as total_recipients,
    COUNT(CASE WHEN mr.delivery_status = 'delivered' THEN 1 END) as delivered_count,
    COUNT(CASE WHEN mr.delivery_status = 'read' THEN 1 END) as read_count,
    COUNT(CASE WHEN mr.delivery_status = 'failed' THEN 1 END) as failed_count,
    ROUND((COUNT(CASE WHEN mr.delivery_status = 'read' THEN 1 END) * 100.0) / COUNT(mr.recipient_id), 2) as read_rate,
    m.created_at,
    m.status
FROM messages m
JOIN users sender ON m.sender_id = sender.id
LEFT JOIN message_recipients mr ON m.id = mr.message_id
GROUP BY m.id, m.subject, m.message_type, m.priority, m.sender_id, sender.full_name, m.created_at, m.status
ORDER BY m.created_at DESC;

-- Announcement statistics view
CREATE OR REPLACE VIEW announcement_statistics AS
SELECT 
    a.id,
    a.title,
    a.priority,
    a.target_audience,
    ac.category_name,
    a.created_by,
    creator.full_name as creator_name,
    a.view_count,
    a.is_pinned,
    a.start_date,
    a.end_date,
    a.status,
    CASE 
        WHEN a.end_date < NOW() THEN 'Expired'
        WHEN a.status = 'archived' THEN 'Archived'
        WHEN a.status = 'published' THEN 'Active'
        ELSE 'Draft'
    END as current_status
FROM announcements a
JOIN announcement_categories ac ON a.category_id = ac.id
JOIN users creator ON a.created_by = creator.id
ORDER BY a.created_at DESC;

-- User notification summary view
CREATE OR REPLACE VIEW user_notification_summary AS
SELECT 
    u.id as user_id,
    u.full_name,
    u.type,
    COUNT(n.id) as total_notifications,
    COUNT(CASE WHEN n.is_read = FALSE THEN 1 END) as unread_notifications,
    COUNT(CASE WHEN n.priority = 'urgent' AND n.is_read = FALSE THEN 1 END) as urgent_unread,
    COUNT(CASE WHEN n.notification_type = 'message' AND n.is_read = FALSE THEN 1 END) as unread_messages,
    COUNT(CASE WHEN n.notification_type = 'announcement' AND n.is_read = FALSE THEN 1 END) as unread_announcements,
    MAX(n.created_at) as latest_notification
FROM users u
LEFT JOIN notifications n ON u.id = n.user_id
WHERE u.status = 'active'
GROUP BY u.id, u.full_name, u.type
ORDER BY unread_notifications DESC, latest_notification DESC;

-- ========================================
-- STORED PROCEDURES FOR COMMUNICATION OPERATIONS
-- ========================================

DELIMITER //

-- Procedure to send individual message
CREATE PROCEDURE IF NOT EXISTS send_individual_message(
    IN p_sender_id INT,
    IN p_recipient_id INT,
    IN p_subject VARCHAR(255),
    IN p_message_text TEXT,
    IN p_priority VARCHAR(20),
    IN p_allow_reply BOOLEAN,
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN,
    OUT p_message_id INT
)
BEGIN
    DECLARE v_recipient_exists INT DEFAULT 0;
    DECLARE v_thread_id INT DEFAULT NULL;
    
    -- Check if recipient exists
    SELECT COUNT(*) INTO v_recipient_exists
    FROM users 
    WHERE id = p_recipient_id AND status = 'active';
    
    IF v_recipient_exists = 0 THEN
        SET p_result = 'Recipient not found or inactive';
        SET p_success = FALSE;
        SET p_message_id = NULL;
    ELSE
        -- Create message
        INSERT INTO messages (
            subject, message_text, message_type, priority, sender_id, thread_id, 
            allow_reply, status, created_at
        ) VALUES (
            p_subject, p_message_text, 'individual', p_priority, p_sender_id, v_thread_id,
            p_allow_reply, 'sent', NOW()
        );
        
        SET p_message_id = LAST_INSERT_ID();
        SET v_thread_id = p_message_id;
        
        -- Update thread_id for the message
        UPDATE messages SET thread_id = v_thread_id WHERE id = p_message_id;
        
        -- Add recipient
        INSERT INTO message_recipients (
            message_id, recipient_id, recipient_type, recipient_value, delivery_status, delivered_at
        ) VALUES (
            p_message_id, p_recipient_id, 'user', CONCAT('user_', p_recipient_id), 'delivered', NOW()
        );
        
        -- Create notification for recipient
        INSERT INTO notifications (
            user_id, notification_type, title, message, reference_id, reference_type, priority
        ) VALUES (
            p_recipient_id, 'message', p_subject, 
            CONCAT('You have a new message: ', LEFT(p_message_text, 100)), 
            p_message_id, 'message', p_priority
        );
        
        -- Log activity
        INSERT INTO communication_logs (
            user_id, action, description, communication_type, reference_id, reference_type, recipient_count
        ) VALUES (
            p_sender_id, 'MESSAGE_SEND', CONCAT('Sent message to user ', p_recipient_id), 
            'message_sent', p_message_id, 'message', 1
        );
        
        SET p_result = 'Message sent successfully';
        SET p_success = TRUE;
    END IF;
END //

-- Procedure to send broadcast message
CREATE PROCEDURE IF NOT EXISTS send_broadcast_message(
    IN p_sender_id INT,
    IN p_target_audience VARCHAR(50),
    IN p_target_value VARCHAR(100), -- For specific audiences
    IN p_subject VARCHAR(255),
    IN p_message_text TEXT,
    IN p_priority VARCHAR(20),
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN,
    OUT p_message_id INT,
    OUT p_recipient_count INT
)
BEGIN
    DECLARE v_thread_id INT DEFAULT NULL;
    DECLARE v_recipient_count INT DEFAULT 0;
    
    -- Create message
    INSERT INTO messages (
        subject, message_text, message_type, priority, sender_id, thread_id, 
        allow_reply, status, created_at
    ) VALUES (
        p_subject, p_message_text, 'broadcast', p_priority, p_sender_id, v_thread_id,
        TRUE, 'sent', NOW()
    );
    
    SET p_message_id = LAST_INSERT_ID();
    SET v_thread_id = p_message_id;
    
    -- Update thread_id for the message
    UPDATE messages SET thread_id = v_thread_id WHERE id = p_message_id;
    
    -- Add recipients based on target audience
    IF p_target_audience = 'all' THEN
        -- Send to all active users
        INSERT INTO message_recipients (
            message_id, recipient_id, recipient_type, recipient_value, delivery_status, delivered_at
        )
        SELECT p_message_id, u.id, 'user', CONCAT('user_', u.id), 'delivered', NOW()
        FROM users u WHERE u.status = 'active';
        
        SET v_recipient_count = ROW_COUNT();
        
        -- Create notifications for all users
        INSERT INTO notifications (
            user_id, notification_type, title, message, reference_id, reference_type, priority
        )
        SELECT 
            u.id, 'message', p_subject, 
            CONCAT('New broadcast message: ', LEFT(p_message_text, 100)), 
            p_message_id, 'message', p_priority
        FROM users u WHERE u.status = 'active';
        
    ELSEIF p_target_audience = 'students' THEN
        -- Send to all active students
        INSERT INTO message_recipients (
            message_id, recipient_id, recipient_type, recipient_value, delivery_status, delivered_at
        )
        SELECT p_message_id, u.id, 'user', CONCAT('user_', u.id), 'delivered', NOW()
        FROM users u WHERE u.type = 'student' AND u.status = 'active';
        
        SET v_recipient_count = ROW_COUNT();
        
        -- Create notifications for students
        INSERT INTO notifications (
            user_id, notification_type, title, message, reference_id, reference_type, priority
        )
        SELECT 
            u.id, 'message', p_subject, 
            CONCAT('New broadcast message: ', LEFT(p_message_text, 100)), 
            p_message_id, 'message', p_priority
        FROM users u WHERE u.type = 'student' AND u.status = 'active';
        
    ELSEIF p_target_audience = 'staff' THEN
        -- Send to all active staff
        INSERT INTO message_recipients (
            message_id, recipient_id, recipient_type, recipient_value, delivery_status, delivered_at
        )
        SELECT p_message_id, u.id, 'user', CONCAT('user_', u.id), 'delivered', NOW()
        FROM users u WHERE u.type = 'staff' AND u.status = 'active';
        
        SET v_recipient_count = ROW_COUNT();
        
        -- Create notifications for staff
        INSERT INTO notifications (
            user_id, notification_type, title, message, reference_id, reference_type, priority
        )
        SELECT 
            u.id, 'message', p_subject, 
            CONCAT('New broadcast message: ', LEFT(p_message_text, 100)), 
            p_message_id, 'message', p_priority
        FROM users u WHERE u.type = 'staff' AND u.status = 'active';
        
    ELSEIF p_target_audience = 'specific_program' THEN
        -- Send to students in specific program
        INSERT INTO message_recipients (
            message_id, recipient_id, recipient_type, recipient_value, delivery_status, delivered_at
        )
        SELECT p_message_id, u.id, 'user', CONCAT('user_', u.id), 'delivered', NOW()
        FROM users u 
        WHERE u.type = 'student' 
          AND u.status = 'active'
          AND (
              (p_target_value = 'CM' AND u.index_number LIKE '%/CM/%') OR
              (p_target_value = 'CN' AND u.index_number LIKE '%/CN/%') OR
              (p_target_value = 'DMORDN' AND u.index_number LIKE '%/DMORDN/%')
          );
        
        SET v_recipient_count = ROW_COUNT();
        
        -- Create notifications for program students
        INSERT INTO notifications (
            user_id, notification_type, title, message, reference_id, reference_type, priority
        )
        SELECT 
            u.id, 'message', p_subject, 
            CONCAT('New broadcast message: ', LEFT(p_message_text, 100)), 
            p_message_id, 'message', p_priority
        FROM users u 
        WHERE u.type = 'student' 
          AND u.status = 'active'
          AND (
              (p_target_value = 'CM' AND u.index_number LIKE '%/CM/%') OR
              (p_target_value = 'CN' AND u.index_number LIKE '%/CN/%') OR
              (p_target_value = 'DMORDN' AND u.index_number LIKE '%/DMORDN/%')
          );
          
    ELSEIF p_target_audience = 'specific_role' THEN
        -- Send to staff with specific role
        INSERT INTO message_recipients (
            message_id, recipient_id, recipient_type, recipient_value, delivery_status, delivered_at
        )
        SELECT p_message_id, u.id, 'user', CONCAT('user_', u.id), 'delivered', NOW()
        FROM users u 
        WHERE u.type = 'staff' 
          AND u.status = 'active'
          AND LOWER(u.role) LIKE CONCAT('%', LOWER(p_target_value), '%');
        
        SET v_recipient_count = ROW_COUNT();
        
        -- Create notifications for role staff
        INSERT INTO notifications (
            user_id, notification_type, title, message, reference_id, reference_type, priority
        )
        SELECT 
            u.id, 'message', p_subject, 
            CONCAT('New broadcast message: ', LEFT(p_message_text, 100)), 
            p_message_id, 'message', p_priority
        FROM users u 
        WHERE u.type = 'staff' 
          AND u.status = 'active'
          AND LOWER(u.role) LIKE CONCAT('%', LOWER(p_target_value), '%');
    END IF;
    
    -- Log activity
    INSERT INTO communication_logs (
        user_id, action, description, communication_type, reference_id, reference_type, recipient_count
    ) VALUES (
        p_sender_id, 'MESSAGE_BROADCAST', 
        CONCAT('Sent broadcast to ', p_target_audience, IF(p_target_value IS NOT NULL, CONCAT(' - ', p_target_value), '')), 
        'message_sent', p_message_id, 'message', v_recipient_count
    );
    
    SET p_recipient_count = v_recipient_count;
    SET p_result = CONCAT('Broadcast message sent to ', v_recipient_count, ' recipients');
    SET p_success = TRUE;
END //

-- Procedure to create announcement
CREATE PROCEDURE IF NOT EXISTS create_announcement(
    IN p_title VARCHAR(255),
    IN p_content TEXT,
    IN p_category_id INT,
    IN p_priority VARCHAR(20),
    IN p_target_audience VARCHAR(50),
    IN p_target_value VARCHAR(100),
    IN p_start_date TIMESTAMP,
    IN p_end_date TIMESTAMP,
    IN p_is_pinned BOOLEAN,
    IN p_allow_comments BOOLEAN,
    IN p_requires_acknowledgment BOOLEAN,
    IN p_created_by INT,
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN,
    OUT p_announcement_id INT
)
BEGIN
    -- Create announcement
    INSERT INTO announcements (
        title, content, category_id, priority, target_audience, program_filter, role_filter,
        start_date, end_date, is_pinned, allow_comments, requires_acknowledgment, created_by, status
    ) VALUES (
        p_title, p_content, p_category_id, p_priority, p_target_audience, p_target_value, NULL,
        p_start_date, p_end_date, p_is_pinned, p_allow_comments, p_requires_acknowledgment, p_created_by, 'published'
    );
    
    SET p_announcement_id = LAST_INSERT_ID();
    
    -- Create notifications for target audience
    IF p_target_audience = 'all' THEN
        INSERT INTO notifications (
            user_id, notification_type, title, message, reference_id, reference_type, priority, action_required
        )
        SELECT 
            u.id, 'announcement', p_title, 
            CONCAT('New announcement: ', LEFT(p_content, 100)), 
            p_announcement_id, 'announcement', p_priority, p_requires_acknowledgment
        FROM users u WHERE u.status = 'active';
        
    ELSEIF p_target_audience = 'students' THEN
        INSERT INTO notifications (
            user_id, notification_type, title, message, reference_id, reference_type, priority, action_required
        )
        SELECT 
            u.id, 'announcement', p_title, 
            CONCAT('New announcement: ', LEFT(p_content, 100)), 
            p_announcement_id, 'announcement', p_priority, p_requires_acknowledgment
        FROM users u WHERE u.type = 'student' AND u.status = 'active';
        
    ELSEIF p_target_audience = 'staff' THEN
        INSERT INTO notifications (
            user_id, notification_type, title, message, reference_id, reference_type, priority, action_required
        )
        SELECT 
            u.id, 'announcement', p_title, 
            CONCAT('New announcement: ', LEFT(p_content, 100)), 
            p_announcement_id, 'announcement', p_priority, p_requires_acknowledgment
        FROM users u WHERE u.type = 'staff' AND u.status = 'active';
    END IF;
    
    -- Log activity
    INSERT INTO communication_logs (
        user_id, action, description, communication_type, reference_id, reference_type
    ) VALUES (
        p_created_by, 'ANNOUNCEMENT_CREATE', 
        CONCAT('Created announcement: ', p_title), 
        'announcement_viewed', p_announcement_id, 'announcement'
    );
    
    SET p_result = 'Announcement created successfully';
    SET p_success = TRUE;
END //

-- Procedure to mark message as read
CREATE PROCEDURE IF NOT EXISTS mark_message_read(
    IN p_message_id INT,
    IN p_user_id INT,
    OUT p_result VARCHAR(255),
    OUT p_success BOOLEAN
)
BEGIN
    DECLARE v_recipient_exists INT DEFAULT 0;
    
    -- Check if user is a recipient of this message
    SELECT COUNT(*) INTO v_recipient_exists
    FROM message_recipients 
    WHERE message_id = p_message_id AND recipient_id = p_user_id;
    
    IF v_recipient_exists = 0 THEN
        SET p_result = 'Message not found or access denied';
        SET p_success = FALSE;
    ELSE
        -- Update recipient status
        UPDATE message_recipients 
        SET delivery_status = 'read', read_at = NOW(), updated_at = NOW()
        WHERE message_id = p_message_id AND recipient_id = p_user_id;
        
        -- Remove corresponding notification
        UPDATE notifications 
        SET is_read = TRUE, read_at = NOW()
        WHERE reference_id = p_message_id AND reference_type = 'message' AND user_id = p_user_id;
        
        -- Log activity
        INSERT INTO communication_logs (
            user_id, action, description, communication_type, reference_id, reference_type
        ) VALUES (
            p_user_id, 'MESSAGE_READ', 
            CONCAT('Marked message ', p_message_id, ' as read'), 
            'message_read', p_message_id, 'message'
        );
        
        SET p_result = 'Message marked as read';
        SET p_success = TRUE;
    END IF;
END //

-- Procedure to get user notifications
CREATE PROCEDURE IF NOT EXISTS get_user_notifications(
    IN p_user_id INT,
    IN p_limit INT DEFAULT 20,
    IN p_offset INT DEFAULT 0
)
BEGIN
    SELECT 
        n.id,
        n.notification_type,
        n.title,
        n.message,
        n.priority,
        n.is_read,
        n.read_at,
        n.action_required,
        n.action_url,
        n.action_button_text,
        n.created_at,
        CASE 
            WHEN n.expires_at IS NOT NULL AND n.expires_at < NOW() THEN 'expired'
            WHEN n.is_read = TRUE THEN 'read'
            ELSE 'unread'
        END as status
    FROM notifications n
    WHERE n.user_id = p_user_id 
      AND (n.expires_at IS NULL OR n.expires_at >= NOW())
    ORDER BY n.priority DESC, n.created_at DESC
    LIMIT p_limit OFFSET p_offset;
END //

DELIMITER ;

-- Success message
SELECT 'Communication system SQL created successfully!' as message;
SELECT 'All tables, views, and stored procedures for communication management are ready for use' as note;
