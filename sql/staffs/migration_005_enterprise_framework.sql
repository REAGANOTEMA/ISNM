-- ═══════════════════════════════════════════════════════════════
-- ISNM ENTERPRISE FRAMEWORK — INCREMENTAL MIGRATION
-- Created: 2026-06-30
-- Description: Missing enterprise tables for task management,
--              calendar, file uploads, messaging, settings, audit
-- SAFE: Uses IF NOT EXISTS — no drops, no overwrites
-- ═══════════════════════════════════════════════════════════════

-- ─────────────────────────────────────────────────────────────
-- 1. TASK ASSIGNMENTS — Enterprise task management
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS task_assignments (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    assigned_by INT(11) UNSIGNED NOT NULL,
    assigned_to INT(11) UNSIGNED NOT NULL,
    priority ENUM('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
    status ENUM('pending','in_progress','completed','cancelled','on_hold') NOT NULL DEFAULT 'pending',
    due_date DATE,
    due_time TIME,
    completed_at DATETIME,
    category VARCHAR(100),
    reference_type VARCHAR(60),
    reference_id INT(11) UNSIGNED,
    reference_url VARCHAR(255),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_assigned_to (assigned_to),
    KEY idx_assigned_by (assigned_by),
    KEY idx_status (status),
    KEY idx_priority (priority),
    KEY idx_due_date (due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 2. CALENDAR EVENTS — Enterprise calendar / scheduling
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS calendar_events (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    start_time TIME,
    end_time TIME,
    location VARCHAR(255),
    event_type ENUM('meeting','deadline','holiday','exam','orientation','training','ceremony','other') NOT NULL DEFAULT 'meeting',
    audience ENUM('all','staff','students','specific') NOT NULL DEFAULT 'all',
    audience_role VARCHAR(100),
    audience_staff_ids TEXT,
    is_recurring TINYINT(1) NOT NULL DEFAULT 0,
    recurrence_pattern VARCHAR(50),
    created_by INT(11) UNSIGNED NOT NULL,
    color VARCHAR(7) DEFAULT '#3b82f6',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_event_date (event_date),
    KEY idx_event_type (event_type),
    KEY idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 3. FILE UPLOADS — Centralized file/document management
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS file_uploads (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT(11) UNSIGNED NOT NULL DEFAULT 0,
    mime_type VARCHAR(100),
    file_type VARCHAR(50),
    uploaded_by INT(11) UNSIGNED NOT NULL,
    uploaded_by_name VARCHAR(120),
    entity_type VARCHAR(60),
    entity_id INT(11) UNSIGNED,
    description TEXT,
    is_public TINYINT(1) NOT NULL DEFAULT 0,
    download_count INT(11) UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_entity (entity_type, entity_id),
    KEY idx_uploaded_by (uploaded_by),
    KEY idx_file_type (file_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 4. EMAIL LOGS — Track email delivery
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS email_logs (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    recipient_email VARCHAR(255) NOT NULL,
    recipient_name VARCHAR(120),
    recipient_type ENUM('staff','student','external') NOT NULL DEFAULT 'staff',
    recipient_id INT(11) UNSIGNED,
    subject VARCHAR(255) NOT NULL,
    body TEXT,
    template_name VARCHAR(100),
    status ENUM('queued','sent','delivered','failed','bounced') NOT NULL DEFAULT 'queued',
    error_message TEXT,
    sent_by INT(11) UNSIGNED,
    sent_at DATETIME,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_recipient (recipient_email),
    KEY idx_status (status),
    KEY idx_sent_at (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 5. SMS LOGS — Track SMS delivery
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS sms_logs (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    phone_number VARCHAR(20) NOT NULL,
    recipient_name VARCHAR(120),
    recipient_type ENUM('staff','student','external') NOT NULL DEFAULT 'staff',
    recipient_id INT(11) UNSIGNED,
    message TEXT NOT NULL,
    status ENUM('queued','sent','delivered','failed') NOT NULL DEFAULT 'queued',
    error_message TEXT,
    provider VARCHAR(50),
    provider_message_id VARCHAR(100),
    sent_by INT(11) UNSIGNED,
    sent_at DATETIME,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_phone (phone_number),
    KEY idx_status (status),
    KEY idx_sent_at (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 6. SYSTEM SETTINGS — Key-value configuration store
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS system_settings (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    setting_key VARCHAR(100) NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string','integer','boolean','json','text') NOT NULL DEFAULT 'string',
    setting_group VARCHAR(100) NOT NULL DEFAULT 'general',
    description VARCHAR(255),
    is_public TINYINT(1) NOT NULL DEFAULT 0,
    updated_by INT(11) UNSIGNED,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_setting_key (setting_key),
    KEY idx_setting_group (setting_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 7. STAFF NOTIFICATIONS — Per-user notification preferences
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS staff_notification_prefs (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    staff_id INT(11) UNSIGNED NOT NULL,
    notify_email TINYINT(1) NOT NULL DEFAULT 1,
    notify_sms TINYINT(1) NOT NULL DEFAULT 0,
    notify_in_app TINYINT(1) NOT NULL DEFAULT 1,
    notify_tasks TINYINT(1) NOT NULL DEFAULT 1,
    notify_approvals TINYINT(1) NOT NULL DEFAULT 1,
    notify_announcements TINYINT(1) NOT NULL DEFAULT 1,
    notify_messages TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_staff_notif (staff_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 8. STAFF MESSAGES — Direct messaging between staff
-- ─────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS staff_messages (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    sender_id INT(11) UNSIGNED NOT NULL,
    sender_name VARCHAR(120),
    recipient_id INT(11) UNSIGNED NOT NULL,
    recipient_name VARCHAR(120),
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    read_at DATETIME,
    priority ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
    parent_id INT(11) UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_sender (sender_id),
    KEY idx_recipient (recipient_id),
    KEY idx_is_read (is_read),
    KEY idx_parent (parent_id),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- SEED DEFAULT SYSTEM SETTINGS
-- ─────────────────────────────────────────────────────────────
INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_type, setting_group, description) VALUES
('institution_name', 'Iganga School of Nursing and Midwifery', 'string', 'general', 'Institution name'),
('institution_motto', 'Excellence in Nursing Education', 'string', 'general', 'Institution motto'),
('academic_year', '2026', 'string', 'academic', 'Current academic year'),
('current_semester', 'Semester 2', 'string', 'academic', 'Current semester'),
('max_login_attempts', '5', 'integer', 'security', 'Max failed login attempts before lock'),
('session_timeout', '3600', 'integer', 'security', 'Session timeout in seconds'),
('password_min_length', '8', 'integer', 'security', 'Minimum password length'),
('enable_audit_logging', '1', 'boolean', 'audit', 'Enable audit trail logging'),
('enable_email_notifications', '1', 'boolean', 'notifications', 'Enable email notifications'),
('enable_sms_notifications', '0', 'boolean', 'notifications', 'Enable SMS notifications'),
('timezone', 'Africa/Kampala', 'string', 'general', 'System timezone'),
('date_format', 'Y-m-d', 'string', 'general', 'Default date format'),
('currency', 'UGX', 'string', 'finance', 'Default currency'),
('institution_email', 'info@igangaschoolofnursingandmidwifery.ac.ug', 'string', 'general', 'Institution email'),
('institution_phone', '+256-XXX-XXXXXX', 'string', 'general', 'Institution phone');
