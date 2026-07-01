SET NAMES utf8mb4;

-- ============================================================
-- 1. WEBSITE SUBMISSIONS TRACKING
-- ============================================================

CREATE TABLE IF NOT EXISTS website_submission_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    submission_type ENUM('contact','donation','volunteer','application') NOT NULL,
    submission_id INT UNSIGNED NOT NULL COMMENT 'ID from website_db table',
    action_by INT UNSIGNED NOT NULL DEFAULT 0,
    action_type VARCHAR(50) NOT NULL DEFAULT 'viewed' COMMENT 'viewed,approved,resolved,emailed',
    action_notes TEXT,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type_id (submission_type, submission_id),
    INDEX idx_action_by (action_by),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 2. SEED DEFAULT WORKFLOWS (only if missing)
-- ============================================================

INSERT IGNORE INTO approval_workflows (id, workflow_name, category, description, is_active) VALUES
(122, 'General Department Request', 'General Administration', 'General departmental requests requiring supervisor and director approval', 1),
(123, 'HR Request', 'Human Resources', 'Human resources requests including leave, transfers, and disciplinary matters', 1),
(124, 'Finance Request', 'Finance', 'Financial requests including budgets, expenditures, and procurement', 1),
(125, 'ICT Request', 'ICT', 'Information and communication technology requests', 1),
(126, 'Academic Request', 'Academic', 'Academic matters including curriculum, examinations, and student affairs', 1),
(127, 'Admissions Request', 'Admissions', 'Student admissions and enrollment requests', 1),
(128, 'Library Request', 'Library', 'Library services and resource requests', 1),
(129, 'Store Requisition', 'Store & Assets', 'Store and asset requisition requests', 1),
(130, 'Student Registration', 'Academic', 'Student registration and academic records requests', 1);

-- ============================================================
-- 3. SEED STAGES FOR WORKFLOWS (only if missing)
-- ============================================================

-- General Department Request (122) - already has Director General Approval at stage 1
INSERT IGNORE INTO approval_stages (id, workflow_id, stage_name, stage_order, assigned_role_name, assigned_role_id) VALUES
(160, 122, 'Director General Approval', 1, 'Director General', NULL);

-- HR Request (123)
INSERT IGNORE INTO approval_stages (id, workflow_id, stage_name, stage_order, assigned_role_name, assigned_role_id) VALUES
(161, 123, 'Director General Approval', 1, 'Director General', NULL);

-- Finance Request (124)
INSERT IGNORE INTO approval_stages (id, workflow_id, stage_name, stage_order, assigned_role_name, assigned_role_id) VALUES
(162, 124, 'Director General Approval', 1, 'Director General', NULL);

-- ICT Request (125)
INSERT IGNORE INTO approval_stages (id, workflow_id, stage_name, stage_order, assigned_role_name, assigned_role_id) VALUES
(158, 125, 'Director ICT Review', 1, 'Director ICT', NULL),
(159, 125, 'Director General Final Approval', 2, 'Director General', NULL);

-- Academic Request (126)
INSERT IGNORE INTO approval_stages (id, workflow_id, stage_name, stage_order, assigned_role_name, assigned_role_id) VALUES
(163, 126, 'Director General Approval', 1, 'Director General', NULL);

-- Admissions Request (127)
INSERT IGNORE INTO approval_stages (id, workflow_id, stage_name, stage_order, assigned_role_name, assigned_role_id) VALUES
(164, 127, 'Director General Approval', 1, 'Director General', NULL);

-- Library Request (128)
INSERT IGNORE INTO approval_stages (id, workflow_id, stage_name, stage_order, assigned_role_name, assigned_role_id) VALUES
(165, 128, 'Director General Approval', 1, 'Director General', NULL);

-- Store Requisition (129)
INSERT IGNORE INTO approval_stages (id, workflow_id, stage_name, stage_order, assigned_role_name, assigned_role_id) VALUES
(166, 129, 'Director General Approval', 1, 'Director General', NULL);

-- Student Registration (130)
INSERT IGNORE INTO approval_stages (id, workflow_id, stage_name, stage_order, assigned_role_name, assigned_role_id) VALUES
(167, 130, 'Director General Approval', 1, 'Director General', NULL);

-- ============================================================
-- 4. SEED NOTIFICATION FOR ALL DIRECTORS
-- ============================================================

INSERT INTO staff_notifications (title, message, type, icon, priority, target_role, created_at)
SELECT 
    'Website Submissions Active',
    'All website contact forms, donations, volunteer applications, and student applications are now routed to director dashboards. Check the Website Submissions section.',
    'info',
    'fa-globe',
    'normal',
    role_name,
    NOW()
FROM staff_roles 
WHERE (role_name LIKE '%Director%' OR role_name IN ('CEO', 'Principal'))
AND NOT EXISTS (
    SELECT 1 FROM staff_notifications 
    WHERE title = 'Website Submissions Active' 
    AND target_role = staff_roles.role_name
);

-- ============================================================
-- VERIFICATION
-- ============================================================

SELECT 'WEBSITE_SUBMISSION_LOGS' as tbl, COUNT(*) as row_count FROM website_submission_logs
UNION ALL SELECT 'STAFF_NOTIFICATIONS', COUNT(*) FROM staff_notifications
UNION ALL SELECT 'APPROVAL_REQUESTS', COUNT(*) FROM approval_requests
UNION ALL SELECT 'APPROVAL_WORKFLOWS', COUNT(*) FROM approval_workflows
UNION ALL SELECT 'APPROVAL_STAGES', COUNT(*) FROM approval_stages
UNION ALL SELECT 'APPROVAL_ACTIONS', COUNT(*) FROM approval_actions
UNION ALL SELECT 'STAFF_INBOX', COUNT(*) FROM staff_inbox
UNION ALL SELECT 'DIRECTOR_NEWS', COUNT(*) FROM director_news;

SELECT id, workflow_name, category FROM approval_workflows ORDER BY id;
SELECT id, workflow_id, stage_name, stage_order, assigned_role_name FROM approval_stages ORDER BY workflow_id, stage_order;
