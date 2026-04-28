-- =====================================================
-- ISNM SCHOOL MANAGEMENT SYSTEM - COMMUNICATION SYSTEM
-- Database: isnm_db
-- Supports all communication operations: messages, announcements, notifications, etc.
-- =====================================================

USE isnm_db;

-- Drop existing tables if they exist to ensure clean setup
DROP TABLE IF EXISTS announcement_categories;
DROP TABLE IF EXISTS announcements;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS message_recipients;
DROP TABLE IF EXISTS message_attachments;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS notification_templates;
DROP TABLE IF EXISTS communication_logs;
DROP TABLE IF EXISTS email_queue;
DROP TABLE IF EXISTS sms_queue;
DROP TABLE IF EXISTS communication_preferences;
DROP TABLE IF EXISTS message_threads;
DROP TABLE IF EXISTS message_read_status;
DROP TABLE IF EXISTS emergency_contacts;
DROP TABLE IF EXISTS communication_groups;
DROP TABLE IF EXISTS group_members;
DROP TABLE IF EXISTS sms_templates;
DROP TABLE IF EXISTS email_templates;

-- =====================================================
-- 1. ANNOUNCEMENT CATEGORIES
-- =====================================================
CREATE TABLE announcement_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_code VARCHAR(20) NOT NULL UNIQUE,
    category_name VARCHAR(255) NOT NULL,
    category_description TEXT NULL,
    category_color VARCHAR(20) DEFAULT '#007bff',
    icon VARCHAR(50) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (category_code),
    INDEX idx_active (is_active)
);

-- =====================================================
-- 2. ANNOUNCEMENTS
-- =====================================================
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    category_id INT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    target_audience JSON NULL, -- Array of roles, departments, or specific users
    announcement_type ENUM('general', 'academic', 'financial', 'administrative', 'emergency', 'event') NOT NULL DEFAULT 'general',
    start_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    end_date TIMESTAMP NULL,
    is_pinned BOOLEAN DEFAULT FALSE,
    requires_acknowledgment BOOLEAN DEFAULT FALSE,
    attachment_urls JSON NULL, -- Array of attachment URLs
    view_count INT DEFAULT 0,
    acknowledgment_count INT DEFAULT 0,
    status ENUM('draft', 'published', 'archived', 'cancelled') DEFAULT 'draft',
    created_by INT NOT NULL,
    published_by INT NULL,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_category (category_id),
    INDEX idx_priority (priority),
    INDEX idx_type (announcement_type),
    INDEX idx_dates (start_date, end_date),
    INDEX idx_status (status),
    INDEX idx_pinned (is_pinned),
    INDEX idx_created_by (created_by),
    INDEX idx_published_by (published_by),
    FOREIGN KEY (category_id) REFERENCES announcement_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (published_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- 3. MESSAGES
-- =====================================================
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thread_id INT NOT NULL,
    sender_id INT NOT NULL,
    message_content TEXT NOT NULL,
    message_type ENUM('text', 'file', 'image', 'video', 'audio', 'link') NOT NULL DEFAULT 'text',
    subject VARCHAR(255) NULL,
    is_edited BOOLEAN DEFAULT FALSE,
    edited_at TIMESTAMP NULL,
    is_deleted BOOLEAN DEFAULT FALSE,
    deleted_at TIMESTAMP NULL,
    reply_to_message_id INT NULL,
    is_system_message BOOLEAN DEFAULT FALSE,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    metadata JSON NULL, -- Additional message metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_thread (thread_id),
    INDEX idx_sender (sender_id),
    INDEX idx_type (message_type),
    INDEX idx_priority (priority),
    INDEX idx_reply_to (reply_to_message_id),
    INDEX idx_created (created_at),
    INDEX idx_deleted (is_deleted),
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reply_to_message_id) REFERENCES messages(id) ON DELETE SET NULL
);

-- =====================================================
-- 4. MESSAGE THREADS
-- =====================================================
CREATE TABLE message_threads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thread_title VARCHAR(255) NULL,
    thread_type ENUM('direct', 'group', 'system', 'support') NOT NULL DEFAULT 'direct',
    created_by INT NOT NULL,
    last_message_id INT NULL,
    last_message_at TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_archived BOOLEAN DEFAULT FALSE,
    archived_at TIMESTAMP NULL,
    archived_by INT NULL,
    participant_count INT DEFAULT 0,
    message_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_type (thread_type),
    INDEX idx_created_by (created_by),
    INDEX idx_last_message (last_message_id),
    INDEX idx_active (is_active),
    INDEX idx_archived (is_archived),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (last_message_id) REFERENCES messages(id) ON DELETE SET NULL,
    FOREIGN KEY (archived_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- 5. MESSAGE RECIPIENTS
-- =====================================================
CREATE TABLE message_recipients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    recipient_id INT NOT NULL,
    recipient_type ENUM('user', 'group', 'role', 'department') NOT NULL DEFAULT 'user',
    delivery_status ENUM('pending', 'sent', 'delivered', 'read', 'failed', 'bounced') DEFAULT 'pending',
    read_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    failed_reason TEXT NULL,
    is_favorite BOOLEAN DEFAULT FALSE,
    is_archived BOOLEAN DEFAULT FALSE,
    archived_at TIMESTAMP NULL,
    is_deleted BOOLEAN DEFAULT FALSE,
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_message_recipient (message_id, recipient_id, recipient_type),
    INDEX idx_message (message_id),
    INDEX idx_recipient (recipient_id),
    INDEX idx_status (delivery_status),
    INDEX idx_read (read_at),
    INDEX idx_favorite (is_favorite),
    INDEX idx_archived (is_archived),
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
);

-- =====================================================
-- 6. MESSAGE READ STATUS
-- =====================================================
CREATE TABLE message_read_status (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    user_id INT NOT NULL,
    thread_id INT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    last_read_position INT DEFAULT 0, -- For long messages
    read_device VARCHAR(50) NULL, -- Device used to read message
    read_ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_message_user_read (message_id, user_id),
    INDEX idx_message (message_id),
    INDEX idx_user (user_id),
    INDEX idx_thread (thread_id),
    INDEX idx_read (is_read),
    INDEX idx_read_at (read_at),
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (thread_id) REFERENCES message_threads(id) ON DELETE CASCADE
);

-- =====================================================
-- 7. MESSAGE ATTACHMENTS
-- =====================================================
CREATE TABLE message_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    attachment_name VARCHAR(255) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    file_type ENUM('image', 'document', 'video', 'audio', 'archive', 'other') NOT NULL,
    thumbnail_path VARCHAR(500) NULL, -- For images and videos
    download_count INT DEFAULT 0,
    uploaded_by INT NOT NULL,
    is_virus_scanned BOOLEAN DEFAULT FALSE,
    scan_result VARCHAR(50) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_message (message_id),
    INDEX idx_type (file_type),
    INDEX idx_uploaded_by (uploaded_by),
    INDEX idx_scanned (is_virus_scanned),
    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 8. NOTIFICATIONS
-- =====================================================
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    notification_type ENUM('info', 'success', 'warning', 'error', 'system', 'message', 'announcement', 'reminder', 'alert') NOT NULL DEFAULT 'info',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    category VARCHAR(50) NULL, -- 'academic', 'financial', 'administrative', etc.
    action_url VARCHAR(500) NULL,
    action_text VARCHAR(100) NULL,
    icon VARCHAR(50) NULL,
    image_url VARCHAR(500) NULL,
    source_type ENUM('system', 'user', 'announcement', 'message', 'payment', 'academic', 'other') NOT NULL DEFAULT 'system',
    source_id INT NULL, -- Reference to source record
    is_read BOOLEAN DEFAULT FALSE,
    is_dismissed BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    dismissed_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    delivery_channels JSON NULL, -- Array of delivery channels: ['web', 'email', 'sms', 'push']
    delivery_status JSON NULL, -- Status per channel
    scheduled_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_user (user_id),
    INDEX idx_type (notification_type),
    INDEX idx_priority (priority),
    INDEX idx_category (category),
    INDEX idx_source (source_type, source_id),
    INDEX idx_read (is_read),
    INDEX idx_dismissed (is_dismissed),
    INDEX idx_expires (expires_at),
    INDEX idx_scheduled (scheduled_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 9. NOTIFICATION TEMPLATES
-- =====================================================
CREATE TABLE notification_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_code VARCHAR(50) NOT NULL UNIQUE,
    template_name VARCHAR(255) NOT NULL,
    template_description TEXT NULL,
    template_type ENUM('email', 'sms', 'push', 'web') NOT NULL,
    subject_template VARCHAR(255) NULL, -- For email templates
    message_template TEXT NOT NULL,
    variables JSON NULL, -- Available template variables
    is_active BOOLEAN DEFAULT TRUE,
    is_system BOOLEAN DEFAULT FALSE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (template_code),
    INDEX idx_type (template_type),
    INDEX idx_active (is_active),
    INDEX idx_system (is_system),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 10. COMMUNICATION LOGS
-- =====================================================
CREATE TABLE communication_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    log_type ENUM('email', 'sms', 'push', 'web', 'in_app') NOT NULL,
    recipient_id INT NOT NULL,
    recipient_contact VARCHAR(255) NOT NULL, -- Email, phone, or user ID
    subject VARCHAR(255) NULL,
    content TEXT NOT NULL,
    template_id INT NULL,
    template_variables JSON NULL,
    delivery_status ENUM('pending', 'sent', 'delivered', 'failed', 'bounced', 'opened', 'clicked') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    opened_at TIMESTAMP NULL,
    clicked_at TIMESTAMP NULL,
    failed_reason TEXT NULL,
    retry_count INT DEFAULT 0,
    max_retries INT DEFAULT 3,
    external_id VARCHAR(255) NULL, -- External service ID
    metadata JSON NULL, -- Additional metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_type (log_type),
    INDEX idx_recipient (recipient_id),
    INDEX idx_status (delivery_status),
    INDEX idx_sent (sent_at),
    INDEX idx_template (template_id),
    FOREIGN KEY (template_id) REFERENCES notification_templates(id) ON DELETE SET NULL
);

-- =====================================================
-- 11. EMAIL QUEUE
-- =====================================================
CREATE TABLE email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    queue_id VARCHAR(100) NOT NULL UNIQUE,
    to_email VARCHAR(255) NOT NULL,
    to_name VARCHAR(255) NULL,
    from_email VARCHAR(255) NOT NULL,
    from_name VARCHAR(255) NULL,
    reply_to VARCHAR(255) NULL,
    cc_emails JSON NULL, -- Array of CC emails
    bcc_emails JSON NULL, -- Array of BCC emails
    subject VARCHAR(255) NOT NULL,
    html_body TEXT NULL,
    text_body TEXT NULL,
    attachments JSON NULL, -- Array of attachment file paths
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    send_after TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    max_attempts INT DEFAULT 3,
    attempts_made INT DEFAULT 0,
    status ENUM('pending', 'processing', 'sent', 'failed', 'cancelled') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    error_message TEXT NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_queue_id (queue_id),
    INDEX idx_to_email (to_email),
    INDEX idx_status (status),
    INDEX idx_send_after (send_after),
    INDEX idx_priority (priority),
    INDEX idx_created_by (created_by),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- 12. SMS QUEUE
-- =====================================================
CREATE TABLE sms_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    queue_id VARCHAR(100) NOT NULL UNIQUE,
    to_phone VARCHAR(20) NOT NULL,
    from_phone VARCHAR(20) NULL,
    message TEXT NOT NULL,
    message_type ENUM('promotional', 'transactional', 'alert', 'verification') NOT NULL DEFAULT 'transactional',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    send_after TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    max_attempts INT DEFAULT 3,
    attempts_made INT DEFAULT 0,
    status ENUM('pending', 'processing', 'sent', 'failed', 'cancelled') DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    delivery_report JSON NULL, -- Delivery report from SMS gateway
    error_message TEXT NULL,
    external_id VARCHAR(100) NULL, -- SMS gateway message ID
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_queue_id (queue_id),
    INDEX idx_to_phone (to_phone),
    INDEX idx_status (status),
    INDEX idx_send_after (send_after),
    INDEX idx_priority (priority),
    INDEX idx_type (message_type),
    INDEX idx_created_by (created_by),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- 13. COMMUNICATION PREFERENCES
-- =====================================================
CREATE TABLE communication_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    preference_type ENUM('email', 'sms', 'push', 'web', 'in_app') NOT NULL,
    is_enabled BOOLEAN DEFAULT TRUE,
    preference_category ENUM('general', 'academic', 'financial', 'administrative', 'emergency', 'marketing') NOT NULL DEFAULT 'general',
    frequency ENUM('immediate', 'hourly', 'daily', 'weekly', 'never') DEFAULT 'immediate',
    quiet_hours_enabled BOOLEAN DEFAULT FALSE,
    quiet_hours_start TIME NULL,
    quiet_hours_end TIME NULL,
    timezone VARCHAR(50) DEFAULT 'Africa/Kampala',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_user_type_category (user_id, preference_type, preference_category),
    INDEX idx_user (user_id),
    INDEX idx_type (preference_type),
    INDEX idx_category (preference_category),
    INDEX idx_enabled (is_enabled),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 14. EMERGENCY CONTACTS
-- =====================================================
CREATE TABLE emergency_contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    contact_name VARCHAR(255) NOT NULL,
    relationship VARCHAR(100) NOT NULL,
    primary_phone VARCHAR(20) NOT NULL,
    secondary_phone VARCHAR(20) NULL,
    email VARCHAR(255) NULL,
    address TEXT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_user (user_id),
    INDEX idx_primary (is_primary),
    INDEX idx_active (is_active),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 15. COMMUNICATION GROUPS
-- =====================================================
CREATE TABLE communication_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_name VARCHAR(255) NOT NULL,
    group_description TEXT NULL,
    group_type ENUM('department', 'program', 'level', 'role', 'custom', 'system') NOT NULL DEFAULT 'custom',
    group_color VARCHAR(20) DEFAULT '#007bff',
    icon VARCHAR(50) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_public BOOLEAN DEFAULT FALSE,
    allow_self_join BOOLEAN DEFAULT FALSE,
    member_limit INT DEFAULT 0, -- 0 = unlimited
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_name (group_name),
    INDEX idx_type (group_type),
    INDEX idx_active (is_active),
    INDEX idx_public (is_public),
    INDEX idx_created_by (created_by),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 16. GROUP MEMBERS
-- =====================================================
CREATE TABLE group_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    user_id INT NOT NULL,
    member_role ENUM('admin', 'moderator', 'member') NOT NULL DEFAULT 'member',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    invited_by INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    left_at TIMESTAMP NULL,
    left_reason VARCHAR(255) NULL,
    notification_preferences JSON NULL, -- Group-specific notification settings
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_group_user (group_id, user_id),
    INDEX idx_group (group_id),
    INDEX idx_user (user_id),
    INDEX idx_role (member_role),
    INDEX idx_active (is_active),
    INDEX idx_joined (joined_at),
    FOREIGN KEY (group_id) REFERENCES communication_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE SET NULL
);

-- =====================================================
-- 17. SMS TEMPLATES
-- =====================================================
CREATE TABLE sms_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_code VARCHAR(50) NOT NULL UNIQUE,
    template_name VARCHAR(255) NOT NULL,
    template_description TEXT NULL,
    message_content TEXT NOT NULL,
    message_type ENUM('promotional', 'transactional', 'alert', 'verification') NOT NULL DEFAULT 'transactional',
    variables JSON NULL, -- Available template variables
    character_count INT GENERATED ALWAYS AS (CHAR_LENGTH(message_content)) STORED,
    is_active BOOLEAN DEFAULT TRUE,
    is_system BOOLEAN DEFAULT FALSE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (template_code),
    INDEX idx_type (message_type),
    INDEX idx_active (is_active),
    INDEX idx_system (is_system),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- 18. EMAIL TEMPLATES
-- =====================================================
CREATE TABLE email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_code VARCHAR(50) NOT NULL UNIQUE,
    template_name VARCHAR(255) NOT NULL,
    template_description TEXT NULL,
    subject_template VARCHAR(255) NOT NULL,
    html_template TEXT NOT NULL,
    text_template TEXT NULL,
    template_type ENUM('notification', 'marketing', 'transactional', 'alert') NOT NULL DEFAULT 'notification',
    variables JSON NULL, -- Available template variables
    css_styles TEXT NULL, -- Inline CSS for email
    is_active BOOLEAN DEFAULT TRUE,
    is_system BOOLEAN DEFAULT FALSE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_code (template_code),
    INDEX idx_type (template_type),
    INDEX idx_active (is_active),
    INDEX idx_system (is_system),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- =====================================================
-- INSERT DEFAULT DATA
-- =====================================================

-- Insert default announcement categories
INSERT INTO announcement_categories (category_code, category_name, category_description, category_color, icon) VALUES
('GEN', 'General Announcements', 'General school announcements and notices', '#007bff', 'fas fa-bullhorn'),
('ACA', 'Academic Updates', 'Academic calendar, exam schedules, and academic notices', '#28a745', 'fas fa-graduation-cap'),
('FIN', 'Financial Notices', 'Fee payment deadlines, financial aid information', '#ffc107', 'fas fa-dollar-sign'),
('ADM', 'Administrative', 'Administrative policies and procedures', '#6c757d', 'fas fa-cog'),
('EVE', 'Events', 'School events, seminars, and activities', '#e83e8c', 'fas fa-calendar-alt'),
('EMG', 'Emergency', 'Emergency announcements and alerts', '#dc3545', 'fas fa-exclamation-triangle'),
('HOL', 'Holidays', 'Holiday schedules and breaks', '#17a2b8', 'fas fa-umbrella-beach'),
('STF', 'Staff Notices', 'Staff-specific announcements', '#6610f2', 'fas fa-users');

-- Insert default communication groups
INSERT INTO communication_groups (group_name, group_description, group_type, is_public, created_by) VALUES
('All Students', 'All registered students', 'system', TRUE, 1),
('All Staff', 'All school staff members', 'system', TRUE, 1),
('Nursing Students', 'Students enrolled in Nursing programs', 'program', TRUE, 1),
('Midwifery Students', 'Students enrolled in Midwifery programs', 'program', TRUE, 1),
('Teaching Staff', 'All teaching and academic staff', 'role', TRUE, 1),
('Administrative Staff', 'All administrative staff', 'role', TRUE, 1),
('Level 1 Students', 'First year students', 'level', TRUE, 1),
('Level 2 Students', 'Second year students', 'level', TRUE, 1),
('Finance Department', 'Finance department staff', 'department', FALSE, 1),
('Academic Department', 'Academic department staff', 'department', FALSE, 1);

-- Insert default notification templates
INSERT INTO notification_templates (template_code, template_name, template_description, template_type, subject_template, message_template, variables, is_system) VALUES
('WELCOME_MSG', 'Welcome Message', 'Welcome message for new users', 'web', 'Welcome to ISNM!', 'Welcome {{full_name}} to the International School of Nursing and Midwifery! We are excited to have you join our community.', '{"full_name": "User full name", "role": "User role"}', TRUE),
('FEE_REMINDER', 'Fee Payment Reminder', 'Reminder for fee payment', 'sms', NULL, 'Dear {{full_name}}, this is a reminder that your fee payment of {{amount}} is due on {{due_date}}. Please make payment to avoid late fees.', '{"full_name": "Student name", "amount": "Fee amount", "due_date": "Due date"}', TRUE),
('EXAM_SCHEDULE', 'Exam Schedule', 'Exam schedule notification', 'email', 'Exam Schedule - {{exam_title}}', 'Dear {{full_name}},\n\nYour {{exam_title}} is scheduled on {{exam_date}} at {{exam_time}} in {{venue}}.\n\nPlease arrive 15 minutes early.\n\nBest regards,\nISNM Administration', '{"full_name": "Student name", "exam_title": "Exam title", "exam_date": "Exam date", "exam_time": "Exam time", "venue": "Exam venue"}', TRUE),
('PASSWORD_RESET', 'Password Reset', 'Password reset notification', 'email', 'Password Reset Request', 'Dear {{full_name}},\n\nYou requested a password reset for your ISNM account. Click the link below to reset your password:\n{{reset_link}}\n\nThis link will expire in 24 hours.\n\nIf you did not request this, please ignore this email.\n\nBest regards,\nISNM Administration', '{"full_name": "User name", "reset_link": "Password reset link"}', TRUE),
('ATTENDANCE_ALERT', 'Low Attendance Alert', 'Alert for low attendance', 'sms', NULL, 'Alert: Your attendance in {{course_name}} is {{attendance_percentage}}%. Please improve your attendance to avoid academic penalties.', '{"course_name": "Course name", "attendance_percentage": "Attendance percentage"}', TRUE);

-- Insert default SMS templates
INSERT INTO sms_templates (template_code, template_name, template_description, message_content, message_type, variables, is_system) VALUES
('PAYMENT_CONFIRM', 'Payment Confirmation', 'Confirm successful payment', 'Dear {{full_name}}, your payment of {{amount}} has been received successfully. Receipt: {{receipt_number}}. Thank you!', 'transactional', '{"full_name": "Student name", "amount": "Payment amount", "receipt_number": "Receipt number"}', TRUE),
('CLASS_CANCELLED', 'Class Cancellation', 'Notify class cancellation', 'Class {{course_code}} scheduled for {{date}} at {{time}} has been cancelled. Next class will be on {{next_date}}.', 'alert', '{"course_code": "Course code", "date": "Date", "time": "Time", "next_date": "Next class date"}', TRUE),
('RESULT_PUBLISHED', 'Results Published', 'Notify exam results', 'Your {{exam_type}} results for {{course_name}} have been published. Check your student portal for details.', 'transactional', '{"exam_type": "Exam type", "course_name": "Course name"}', TRUE),
('LIBRARY_OVERDUE', 'Library Overdue', 'Library book overdue notice', 'Your library book "{{book_title}}" is overdue. Please return it to the library immediately to avoid fines.', 'alert', '{"book_title": "Book title"}', TRUE),
('HOLIDAY_NOTICE', 'Holiday Notice', 'Holiday announcement', 'School will be closed for {{holiday_name}} from {{start_date}} to {{end_date}. Classes resume on {{resume_date}}.', 'alert', '{"holiday_name": "Holiday name", "start_date": "Start date", "end_date": "End date", "resume_date": "Resume date"}', TRUE);

-- Insert default email templates
INSERT INTO email_templates (template_code, template_name, template_description, subject_template, html_template, text_template, template_type, variables, is_system) VALUES
('ADMISSION_CONFIRM', 'Admission Confirmation', 'Confirm student admission', 'Admission Confirmation - ISNM', '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Admission Confirmation</title></head><body style="font-family: Arial, sans-serif;"><div style="max-width: 600px; margin: 0 auto;"><h2 style="color: #007bff;">Admission Confirmation</h2><p>Dear {{full_name}},</p><p>Congratulations! You have been successfully admitted to the {{program_name}} program at the International School of Nursing and Midwifery.</p><p><strong>Admission Details:</strong></p><ul><li>Student ID: {{index_number}}</li><li>Program: {{program_name}}</li><li>Level: {{level}}</li><li>Start Date: {{start_date}}</li></ul><p>Please complete your registration and fee payment before the deadline.</p><p>Welcome to ISNM!</p></div></body></html>', 'Dear {{full_name}},\n\nCongratulations! You have been successfully admitted to the {{program_name}} program at the International School of Nursing and Midwifery.\n\nAdmission Details:\nStudent ID: {{index_number}}\nProgram: {{program_name}}\nLevel: {{level}}\nStart Date: {{start_date}}\n\nPlease complete your registration and fee payment before the deadline.\n\nWelcome to ISNM!', 'transactional', '{"full_name": "Student name", "program_name": "Program name", "index_number": "Student ID", "level": "Level", "start_date": "Start date"}', TRUE),
('MONTHLY_NEWSLETTER', 'Monthly Newsletter', 'School monthly newsletter', 'ISNM Monthly Newsletter - {{month}} {{year}}', '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Monthly Newsletter</title></head><body style="font-family: Arial, sans-serif;"><div style="max-width: 600px; margin: 0 auto;"><h1 style="color: #007bff;">ISNM Monthly Newsletter</h1><h2>{{month}} {{year}}</h2>{{newsletter_content}}<p>Best regards,<br>ISNM Administration</p></div></body></html>', 'ISNM Monthly Newsletter - {{month}} {{year}}\n\n{{newsletter_content}}\n\nBest regards,\nISNM Administration', 'marketing', '{"month": "Month", "year": "Year", "newsletter_content": "Newsletter content"}', TRUE);

-- Insert default communication preferences for all users
INSERT INTO communication_preferences (user_id, preference_type, preference_category, is_enabled, frequency)
SELECT 
    id as user_id,
    pt.preference_type,
    cp.preference_category,
    TRUE as is_enabled,
    'immediate' as frequency
FROM users u
CROSS JOIN (
    SELECT 'email' as preference_type UNION SELECT 'sms' UNION SELECT 'push' UNION SELECT 'web' UNION SELECT 'in_app'
) pt
CROSS JOIN (
    SELECT 'general' as preference_category UNION SELECT 'academic' UNION SELECT 'financial' UNION SELECT 'administrative' UNION SELECT 'emergency'
) cp;

-- =====================================================
-- CREATE STORED PROCEDURES FOR COMMUNICATION OPERATIONS
-- =====================================================

DELIMITER //

-- Procedure to send message
CREATE PROCEDURE send_message(
    IN p_sender_id INT,
    IN p_thread_id INT,
    IN p_message_content TEXT,
    IN p_message_type ENUM('text', 'file', 'image', 'video', 'audio', 'link'),
    IN p_subject VARCHAR(255),
    IN p_recipient_ids JSON, -- Array of recipient IDs
    IN p_priority ENUM('low', 'medium', 'high', 'urgent')
)
BEGIN
    DECLARE v_message_id INT;
    DECLARE v_recipient_id INT;
    DECLARE v_done INT DEFAULT FALSE;
    DECLARE recipient_cursor CURSOR FOR SELECT value FROM JSON_TABLE(p_recipient_ids, '$[*]' COLUMNS (value INT PATH '$')) AS jt;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = TRUE;
    
    -- Insert message
    INSERT INTO messages (
        thread_id, sender_id, message_content, message_type, subject, priority
    ) VALUES (
        p_thread_id, p_sender_id, p_message_content, p_message_type, p_subject, p_priority
    );
    
    SET v_message_id = LAST_INSERT_ID();
    
    -- Update thread with last message
    UPDATE message_threads 
    SET last_message_id = v_message_id,
        last_message_at = CURRENT_TIMESTAMP,
        message_count = message_count + 1
    WHERE id = p_thread_id;
    
    -- Add recipients
    OPEN recipient_cursor;
    recipient_loop: LOOP
        FETCH recipient_cursor INTO v_recipient_id;
        IF v_done THEN
            LEAVE recipient_loop;
        END IF;
        
        INSERT INTO message_recipients (
            message_id, recipient_id, delivery_status
        ) VALUES (
            v_message_id, v_recipient_id, 'sent'
        );
        
        -- Create notification for recipient
        INSERT INTO notifications (
            user_id, title, message, notification_type, priority, source_type, source_id, action_url
        ) VALUES (
            v_recipient_id, 
            'New Message', 
            CONCAT('You have a new message from ', (SELECT full_name FROM users WHERE id = p_sender_id)),
            'message',
            p_priority,
            'message',
            v_message_id,
            CONCAT('/messages/thread/', p_thread_id)
        );
    END LOOP;
    CLOSE recipient_cursor;
    
    -- Log message sent activity
    INSERT INTO dashboard_activity_logs (
        user_id, action, entity_type, entity_id, description
    ) VALUES (
        p_sender_id, 'send', 'message', v_message_id,
        CONCAT('Sent message to thread ', p_thread_id)
    );
    
    SELECT v_message_id as message_id;
END //

-- Procedure to create announcement
CREATE PROCEDURE create_announcement(
    IN p_title VARCHAR(255),
    IN p_content TEXT,
    IN p_category_id INT,
    IN p_priority ENUM('low', 'medium', 'high', 'urgent'),
    IN p_target_audience JSON,
    IN p_announcement_type ENUM('general', 'academic', 'financial', 'administrative', 'emergency', 'event'),
    IN p_start_date TIMESTAMP,
    IN p_end_date TIMESTAMP,
    IN p_is_pinned BOOLEAN,
    IN p_requires_acknowledgment BOOLEAN,
    IN p_created_by INT
)
BEGIN
    DECLARE v_announcement_id INT;
    DECLARE v_target_user_id INT;
    DECLARE v_done INT DEFAULT FALSE;
    DECLARE audience_cursor CURSOR FOR 
        SELECT value FROM JSON_TABLE(p_target_audience, '$[*]' COLUMNS (value INT PATH '$')) AS jt;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = TRUE;
    
    -- Insert announcement
    INSERT INTO announcements (
        title, content, category_id, priority, target_audience, announcement_type,
        start_date, end_date, is_pinned, requires_acknowledgment, created_by
    ) VALUES (
        p_title, p_content, p_category_id, p_priority, p_target_audience, p_announcement_type,
        p_start_date, p_end_date, p_is_pinned, p_requires_acknowledgment, p_created_by
    );
    
    SET v_announcement_id = LAST_INSERT_ID();
    
    -- Create notifications for target audience
    IF p_target_audience IS NOT NULL THEN
        OPEN audience_cursor;
        audience_loop: LOOP
            FETCH audience_cursor INTO v_target_user_id;
            IF v_done THEN
                LEAVE audience_loop;
            END IF;
            
            INSERT INTO notifications (
                user_id, title, message, notification_type, priority, category, source_type, source_id, action_url
            ) VALUES (
                v_target_user_id,
                p_title,
                CONCAT(SUBSTRING(p_content, 1, 100), '...'),
                'announcement',
                p_priority,
                p_announcement_type,
                'announcement',
                v_announcement_id,
                CONCAT('/announcements/view/', v_announcement_id)
            );
        END LOOP;
        CLOSE audience_cursor;
    END IF;
    
    -- Log announcement creation
    INSERT INTO dashboard_activity_logs (
        user_id, action, entity_type, entity_id, description
    ) VALUES (
        p_created_by, 'create', 'announcement', v_announcement_id,
        CONCAT('Created announcement: ', p_title)
    );
    
    SELECT v_announcement_id as announcement_id;
END //

-- Procedure to send notification
CREATE PROCEDURE send_notification(
    IN p_user_id INT,
    IN p_title VARCHAR(255),
    IN p_message TEXT,
    IN p_notification_type ENUM('info', 'success', 'warning', 'error', 'system', 'message', 'announcement', 'reminder', 'alert'),
    IN p_priority ENUM('low', 'medium', 'high', 'urgent'),
    IN p_category VARCHAR(50),
    IN p_action_url VARCHAR(500),
    IN p_action_text VARCHAR(100),
    IN p_source_type ENUM('system', 'user', 'announcement', 'message', 'payment', 'academic', 'other'),
    IN p_source_id INT,
    IN p_delivery_channels JSON
)
BEGIN
    DECLARE v_notification_id INT;
    DECLARE v_channel VARCHAR(20);
    DECLARE v_done INT DEFAULT FALSE;
    DECLARE channel_cursor CURSOR FOR SELECT value FROM JSON_TABLE(p_delivery_channels, '$[*]' COLUMNS (value VARCHAR(20) PATH '$')) AS jt;
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET v_done = TRUE;
    
    -- Insert notification
    INSERT INTO notifications (
        user_id, title, message, notification_type, priority, category, action_url, action_text,
        source_type, source_id, delivery_channels
    ) VALUES (
        p_user_id, p_title, p_message, p_notification_type, p_priority, p_category,
        p_action_url, p_action_text, p_source_type, p_source_id, p_delivery_channels
    );
    
    SET v_notification_id = LAST_INSERT_ID();
    
    -- Queue notifications for different channels
    OPEN channel_cursor;
    channel_loop: LOOP
        FETCH channel_cursor INTO v_channel;
        IF v_done THEN
            LEAVE channel_loop;
        END IF;
        
        CASE v_channel
            WHEN 'email' THEN
                INSERT INTO email_queue (
                    queue_id, to_email, subject, html_body, priority
                ) VALUES (
                    CONCAT('EMAIL', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'), LPAD(v_notification_id, 6, '0')),
                    (SELECT email FROM users WHERE id = p_user_id),
                    p_title,
                    CONCAT('<p>', p_message, '</p>'),
                    p_priority
                );
                
            WHEN 'sms' THEN
                INSERT INTO sms_queue (
                    queue_id, to_phone, message, priority
                ) VALUES (
                    CONCAT('SMS', DATE_FORMAT(NOW(), '%Y%m%d%H%i%s'), LPAD(v_notification_id, 6, '0')),
                    (SELECT phone FROM users WHERE id = p_user_id),
                    CONCAT(p_title, ': ', p_message),
                    p_priority
                );
        END CASE;
    END LOOP;
    CLOSE channel_cursor;
    
    SELECT v_notification_id as notification_id;
END //

-- Procedure to mark message as read
CREATE PROCEDURE mark_message_read(
    IN p_message_id INT,
    IN p_user_id INT,
    IN p_thread_id INT
)
BEGIN
    -- Update read status
    INSERT INTO message_read_status (
        message_id, user_id, thread_id, is_read, read_at
    ) VALUES (
        p_message_id, p_user_id, p_thread_id, TRUE, CURRENT_TIMESTAMP
    ) ON DUPLICATE KEY UPDATE
        is_read = TRUE,
        read_at = CURRENT_TIMESTAMP;
    
    -- Update message recipient status
    UPDATE message_recipients 
    SET delivery_status = 'read', read_at = CURRENT_TIMESTAMP
    WHERE message_id = p_message_id AND recipient_id = p_user_id;
    
    SELECT 'Message marked as read' as status;
END //

-- Procedure to get user conversations
CREATE PROCEDURE get_user_conversations(IN p_user_id INT)
BEGIN
    -- Get threads where user is a participant
    SELECT 
        mt.id as thread_id,
        mt.thread_title,
        mt.thread_type,
        mt.last_message_at,
        m.message_content as last_message,
        u.full_name as last_message_sender,
        mr.delivery_status,
        COUNT(CASE WHEN mr.recipient_id = p_user_id AND mr.delivery_status != 'read' THEN 1 END) as unread_count,
        mt.message_count
    FROM message_threads mt
    JOIN messages m ON mt.last_message_id = m.id
    JOIN users u ON m.sender_id = u.id
    JOIN message_recipients mr ON m.id = mr.message_id AND mr.recipient_id = p_user_id
    WHERE mr.recipient_id = p_user_id
    AND mt.is_active = TRUE
    ORDER BY mt.last_message_at DESC;
END //

-- Procedure to get communication statistics
CREATE PROCEDURE get_communication_statistics(IN p_period_start DATE, IN p_period_end DATE)
BEGIN
    -- Message statistics
    SELECT 
        'Messages' as category,
        COUNT(*) as total_sent,
        COUNT(DISTINCT m.sender_id) as unique_senders,
        COUNT(DISTINCT mr.recipient_id) as unique_recipients,
        COUNT(CASE WHEN m.created_at BETWEEN p_period_start AND p_period_end THEN 1 END) as period_sent
    FROM messages m
    JOIN message_recipients mr ON m.id = mr.message_id;
    
    -- Announcement statistics
    SELECT 
        'Announcements' as category,
        COUNT(*) as total_announcements,
        COUNT(CASE WHEN a.priority = 'urgent' THEN 1 END) as urgent_announcements,
        COUNT(CASE WHEN a.is_pinned = TRUE THEN 1 END) as pinned_announcements,
        COUNT(CASE WHEN a.created_at BETWEEN p_period_start AND p_period_end THEN 1 END) as period_announcements,
        SUM(a.view_count) as total_views
    FROM announcements a;
    
    -- Notification statistics
    SELECT 
        'Notifications' as category,
        COUNT(*) as total_notifications,
        COUNT(CASE WHEN n.is_read = TRUE THEN 1 END) as read_notifications,
        COUNT(CASE WHEN n.is_read = FALSE THEN 1 END) as unread_notifications,
        COUNT(CASE WHEN n.created_at BETWEEN p_period_start AND p_period_end THEN 1 END) as period_notifications
    FROM notifications n;
    
    -- Email queue statistics
    SELECT 
        'Email Queue' as category,
        COUNT(*) as total_emails,
        COUNT(CASE WHEN eq.status = 'sent' THEN 1 END) as sent_emails,
        COUNT(CASE WHEN eq.status = 'failed' THEN 1 END) as failed_emails,
        COUNT(CASE WHEN eq.created_at BETWEEN p_period_start AND p_period_end THEN 1 END) as period_emails
    FROM email_queue eq;
    
    -- SMS queue statistics
    SELECT 
        'SMS Queue' as category,
        COUNT(*) as total_sms,
        COUNT(CASE WHEN sq.status = 'sent' THEN 1 END) as sent_sms,
        COUNT(CASE WHEN sq.status = 'failed' THEN 1 END) as failed_sms,
        COUNT(CASE WHEN sq.created_at BETWEEN p_period_start AND p_period_end THEN 1 END) as period_sms
    FROM sms_queue sq;
END //

DELIMITER ;

-- =====================================================
-- CREATE VIEWS FOR COMMUNICATION OPERATIONS
-- =====================================================

-- View for user message summary
CREATE VIEW user_message_summary AS
SELECT 
    u.id as user_id,
    u.full_name,
    COUNT(DISTINCT mt.id) as total_threads,
    COUNT(DISTINCT m.id) as total_messages,
    COUNT(DISTINCT CASE WHEN m.sender_id = u.id THEN m.id END) as sent_messages,
    COUNT(DISTINCT CASE WHEN m.sender_id != u.id THEN m.id END) as received_messages,
    COUNT(DISTINCT CASE WHEN mr.recipient_id = u.id AND mr.delivery_status != 'read' THEN mr.id END) as unread_messages,
    COUNT(DISTINCT ma.id) as total_attachments
FROM users u
LEFT JOIN message_recipients mr ON u.id = mr.recipient_id
LEFT JOIN messages m ON mr.message_id = m.id
LEFT JOIN message_threads mt ON m.thread_id = mt.id
LEFT JOIN message_attachments ma ON m.id = ma.message_id
GROUP BY u.id, u.full_name;

-- View for announcement engagement
CREATE VIEW announcement_engagement AS
SELECT 
    a.id as announcement_id,
    a.title,
    a.category_id,
    ac.category_name,
    a.priority,
    a.view_count,
    a.acknowledgment_count,
    COUNT(DISTINCT mr.recipient_id) as total_recipients,
    COUNT(DISTINCT CASE WHEN mr.delivery_status = 'read' THEN mr.recipient_id END) as read_count,
    ROUND((COUNT(DISTINCT CASE WHEN mr.delivery_status = 'read' THEN mr.recipient_id END) * 100.0 / 
          COUNT(DISTINCT mr.recipient_id)), 2) as read_percentage,
    a.created_at,
    a.start_date,
    a.end_date
FROM announcements a
JOIN announcement_categories ac ON a.category_id = ac.id
LEFT JOIN message_recipients mr ON mr.entity_type = 'announcement' AND mr.entity_id = a.id
GROUP BY a.id, a.title, a.category_id, ac.category_name, a.priority, a.view_count, a.acknowledgment_count, a.created_at, a.start_date, a.end_date;

-- View for notification delivery
CREATE VIEW notification_delivery AS
SELECT 
    n.id as notification_id,
    n.title,
    n.notification_type,
    n.priority,
    n.user_id,
    u.full_name as user_name,
    n.source_type,
    n.source_id,
    n.is_read,
    n.is_dismissed,
    n.read_at,
    n.created_at,
    n.expires_at,
    JSON_LENGTH(n.delivery_channels) as total_channels,
    JSON_LENGTH(n.delivery_status) as delivered_channels
FROM notifications n
JOIN users u ON n.user_id = u.id;

-- View for communication effectiveness
CREATE VIEW communication_effectiveness AS
SELECT 
    DATE(cl.created_at) as communication_date,
    cl.log_type,
    COUNT(*) as total_sent,
    COUNT(CASE WHEN cl.delivery_status = 'sent' THEN 1 END) as successfully_sent,
    COUNT(CASE WHEN cl.delivery_status = 'delivered' THEN 1 END) as successfully_delivered,
    COUNT(CASE WHEN cl.delivery_status = 'opened' THEN 1 END) as opened,
    COUNT(CASE WHEN cl.delivery_status = 'clicked' THEN 1 END) as clicked,
    COUNT(CASE WHEN cl.delivery_status = 'failed' THEN 1 END) as failed,
    ROUND((COUNT(CASE WHEN cl.delivery_status = 'delivered' THEN 1 END) * 100.0 / COUNT(*)), 2) as delivery_rate,
    ROUND((COUNT(CASE WHEN cl.delivery_status = 'opened' THEN 1 END) * 100.0 / COUNT(*)), 2) as open_rate
FROM communication_logs cl
GROUP BY DATE(cl.created_at), cl.log_type
ORDER BY communication_date DESC;

-- =====================================================
-- TRIGGERS FOR AUTOMATIC COMMUNICATION OPERATIONS
-- =====================================================

DELIMITER //

-- Trigger to log message activity
CREATE TRIGGER after_message_insert
AFTER INSERT ON messages
FOR EACH ROW
BEGIN
    -- Update message thread
    UPDATE message_threads 
    SET message_count = message_count + 1,
        last_message_at = NEW.created_at
    WHERE id = NEW.thread_id;
    
    -- Log communication activity
    INSERT INTO communication_logs (
        log_type, recipient_id, recipient_contact, subject, content, delivery_status, created_at
    ) VALUES (
        'in_app', NEW.sender_id, (SELECT email FROM users WHERE id = NEW.sender_id),
        (SELECT thread_title FROM message_threads WHERE id = NEW.thread_id),
        NEW.message_content, 'sent', NEW.created_at
    );
END //

-- Trigger to update announcement view count
CREATE TRIGGER after_announcement_view
AFTER UPDATE ON announcements
FOR EACH ROW
BEGIN
    IF NEW.view_count > OLD.view_count THEN
        -- Log view activity
        INSERT INTO communication_logs (
            log_type, recipient_id, subject, content, delivery_status, created_at
        ) VALUES (
            'web', 0, NEW.title, 'Announcement viewed', 'opened', CURRENT_TIMESTAMP
        );
    END IF;
END //

-- Trigger to mark notification as read
CREATE TRIGGER after_notification_read
AFTER UPDATE ON notifications
FOR EACH ROW
BEGIN
    IF OLD.is_read = FALSE AND NEW.is_read = TRUE THEN
        -- Update communication log
        UPDATE communication_logs 
        SET delivery_status = 'opened', opened_at = NEW.read_at
        WHERE source_type = 'notification' AND source_id = NEW.id;
    END IF;
END //

DELIMITER ;

-- =====================================================
-- FINAL SETUP COMPLETE
-- =====================================================

-- Grant necessary permissions for stored procedures
GRANT EXECUTE ON PROCEDURE send_message TO 'root'@'localhost';
GRANT EXECUTE ON PROCEDURE create_announcement TO 'root'@'localhost';
GRANT EXECUTE ON PROCEDURE send_notification TO 'root'@'localhost';
GRANT EXECUTE ON PROCEDURE mark_message_read TO 'root'@'localhost';
GRANT EXECUTE ON PROCEDURE get_user_conversations TO 'root'@'localhost';
GRANT EXECUTE ON PROCEDURE get_communication_statistics TO 'root'@'localhost';

-- =====================================================
-- SETUP COMPLETE MESSAGE
-- =====================================================
SELECT 'ISNM Communication System Setup Complete!' as status,
       COUNT(*) as total_tables_created
FROM information_schema.tables 
WHERE table_schema = 'isnm_db' 
AND table_name IN ('announcements', 'messages', 'notifications', 'announcement_categories', 'message_threads', 'communication_logs', 'email_queue', 'sms_queue');
