-- ============================================================
-- PART 2: Fix data and create tables
-- Run this SECOND on HOSTING phpMyAdmin
-- Make sure you already ran Part 1 (or column already exists)
-- ============================================================

-- Update priority_order values
UPDATE approval_requests SET priority_order = CASE priority WHEN 'Urgent' THEN 1 WHEN 'High' THEN 2 WHEN 'Medium' THEN 3 WHEN 'Normal' THEN 4 WHEN 'Low' THEN 5 ELSE 4 END;

-- Fix approval requests workflow mapping
UPDATE approval_requests SET workflow_id=122, current_stage_id=160, current_stage_order=1 WHERE id IN (1,2,3);
UPDATE approval_requests SET workflow_id=123, current_stage_id=161, current_stage_order=1 WHERE id IN (4,5);
UPDATE approval_requests SET workflow_id=122, current_stage_id=160, current_stage_order=1 WHERE id=6;

-- Create tables
CREATE TABLE IF NOT EXISTS staff_notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'info',
    icon VARCHAR(50) DEFAULT 'fa-bell',
    url VARCHAR(500) DEFAULT NULL,
    priority ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    target_role VARCHAR(80) DEFAULT NULL,
    target_user_id INT UNSIGNED DEFAULT NULL,
    created_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_target_user (target_user_id),
    INDEX idx_read (is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS staff_notification_reads (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    notification_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    read_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_read (notification_id, user_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS staff_inbox (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_id INT UNSIGNED NOT NULL,
    sender_name VARCHAR(120) NOT NULL DEFAULT '',
    sender_role VARCHAR(80) NOT NULL DEFAULT '',
    recipient_id INT UNSIGNED NOT NULL,
    recipient_name VARCHAR(120) NOT NULL DEFAULT '',
    subject VARCHAR(255) NOT NULL DEFAULT '',
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    read_at DATETIME DEFAULT NULL,
    priority ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
    parent_id INT UNSIGNED DEFAULT NULL,
    is_deleted_sender TINYINT(1) NOT NULL DEFAULT 0,
    is_deleted_recipient TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sender (sender_id),
    INDEX idx_recipient (recipient_id),
    INDEX idx_thread (parent_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS website_submission_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    submission_type ENUM('contact','donation','volunteer','application') NOT NULL,
    submission_id INT UNSIGNED NOT NULL,
    action_by INT UNSIGNED NOT NULL DEFAULT 0,
    action_type VARCHAR(50) NOT NULL DEFAULT 'viewed',
    action_notes TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type_id (submission_type, submission_id),
    INDEX idx_action_by (action_by),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert create actions
INSERT IGNORE INTO approval_actions (request_id, stage_id, action_by, action_type, comments, created_at)
SELECT ar.id, ar.current_stage_id, ar.requester_id, 'create', CONCAT('Request created: ', ar.title), ar.created_at
FROM approval_requests ar
WHERE NOT EXISTS (SELECT 1 FROM approval_actions aa WHERE aa.request_id = ar.id AND aa.action_type = 'create');

-- Seed workflows
INSERT IGNORE INTO approval_workflows (id, workflow_name, category, description, is_active) VALUES
(122, 'General Department Request', 'General Administration', 'General departmental requests', 1),
(123, 'HR Request', 'Human Resources', 'Human resources requests', 1),
(124, 'Finance Request', 'Finance', 'Financial requests', 1),
(125, 'ICT Request', 'ICT', 'ICT requests', 1),
(126, 'Academic Request', 'Academic', 'Academic matters', 1),
(127, 'Admissions Request', 'Admissions', 'Student admissions requests', 1),
(128, 'Library Request', 'Library', 'Library services requests', 1),
(129, 'Store Requisition', 'Store & Assets', 'Store and asset requisitions', 1),
(130, 'Student Registration', 'Academic', 'Student registration requests', 1);

-- Seed stages
INSERT IGNORE INTO approval_stages (id, workflow_id, stage_name, stage_order, assigned_role_name, assigned_role_id) VALUES
(158, 125, 'Director ICT Review', 1, 'Director ICT', NULL),
(159, 125, 'Director General Final Approval', 2, 'Director General', NULL),
(160, 122, 'Director General Approval', 1, 'Director General', NULL),
(161, 123, 'Director General Approval', 1, 'Director General', NULL),
(162, 124, 'Director General Approval', 1, 'Director General', NULL),
(163, 126, 'Director General Approval', 1, 'Director General', NULL),
(164, 127, 'Director General Approval', 1, 'Director General', NULL),
(165, 128, 'Director General Approval', 1, 'Director General', NULL),
(166, 129, 'Director General Approval', 1, 'Director General', NULL),
(167, 130, 'Director General Approval', 1, 'Director General', NULL);

-- Seed notifications
INSERT IGNORE INTO staff_notifications (title, message, type, icon, priority, target_role, created_at)
SELECT 'Website Submissions Active', 'All website contact forms, donations, volunteer applications, and student applications are now routed to director dashboards.', 'info', 'fa-globe', 'normal', role_name, NOW()
FROM staff_roles WHERE (role_name LIKE '%Director%' OR role_name IN ('CEO', 'Principal'))
AND NOT EXISTS (SELECT 1 FROM staff_notifications WHERE title = 'Website Submissions Active' AND target_role = staff_roles.role_name);

-- Verify
SELECT 'SUCCESS' as result;
SELECT COUNT(*) as approval_requests FROM approval_requests;
SELECT COUNT(*) as approval_actions FROM approval_actions;
SELECT COUNT(*) as approval_workflows FROM approval_workflows;
SELECT COUNT(*) as approval_stages FROM approval_stages;
SELECT COUNT(*) as staff_notifications FROM staff_notifications;
SELECT COUNT(*) as staff_inbox FROM staff_inbox;
