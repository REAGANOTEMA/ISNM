-- ============================================================
-- fix_approvals_news_messaging.sql
-- Comprehensive fix for approvals, news, and messaging system
-- Database: igangaschoolofl_staffs_db (port 3307)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. APPROVAL FIXES
-- ============================================================

-- 1a. Fix orphaned approval requests
-- All requests 1-6 reference non-existent workflow_ids (1, 2, 3).
-- Only workflows 122-130 exist. Map them correctly:
--   workflow 1 (General)  -> 122 (General Department Request)
--   workflow 2 (HR)       -> 123 (HR Request)
--   workflow 3 (Other)    -> 122 (General Department Request)

UPDATE approval_requests
   SET workflow_id = 122,
       current_stage_id = 160,
       current_stage_order = 1
 WHERE id = 1
   AND (workflow_id != 122 OR current_stage_id IS NULL);

UPDATE approval_requests
   SET workflow_id = 122,
       current_stage_id = 160,
       current_stage_order = 1
 WHERE id = 2
   AND (workflow_id != 122 OR current_stage_id IS NULL);

UPDATE approval_requests
   SET workflow_id = 122,
       current_stage_id = 160,
       current_stage_order = 1
 WHERE id = 3
   AND (workflow_id != 122 OR current_stage_id IS NULL);

UPDATE approval_requests
   SET workflow_id = 123,
       current_stage_id = 161,
       current_stage_order = 1
 WHERE id = 4
   AND (workflow_id != 123 OR current_stage_id IS NULL);

UPDATE approval_requests
   SET workflow_id = 123,
       current_stage_id = 161,
       current_stage_order = 1
 WHERE id = 5
   AND (workflow_id != 123 OR current_stage_id IS NULL);

-- 1b. Fix priority sort order bug
-- Priority is stored as varchar and sorts alphabetically (High < Low < Medium < Urgent).
-- Add a numeric sort column to fix ordering.

ALTER TABLE approval_requests
  ADD COLUMN priority_order SMALLINT UNSIGNED NOT NULL DEFAULT 2
  AFTER priority;

UPDATE approval_requests
   SET priority_order = CASE priority
       WHEN 'Urgent' THEN 1
       WHEN 'High'   THEN 2
       WHEN 'Medium' THEN 3
       WHEN 'Normal' THEN 4
       WHEN 'Low'    THEN 5
       ELSE 4
   END;

-- 1c. Insert missing 'create' actions for all approval requests
-- Every request should have an initial 'create' action record.

INSERT IGNORE INTO approval_actions (request_id, stage_id, action_by, action_type, comments, created_at)
SELECT ar.id,
       ar.current_stage_id,
       ar.requester_id,
       'create',
       CONCAT('Request created: ', ar.title),
       ar.created_at
  FROM approval_requests ar
 WHERE NOT EXISTS (
       SELECT 1 FROM approval_actions aa
        WHERE aa.request_id = ar.id
          AND aa.action_type = 'create'
 );

-- ============================================================
-- 2. NEWS FIXES (director_news)
-- Table structure verified - no changes needed.
-- ============================================================

-- ============================================================
-- 3. MESSAGING SYSTEM - Unified staff_inbox
-- ============================================================

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
    parent_id INT UNSIGNED DEFAULT NULL COMMENT 'For threaded replies',
    is_deleted_sender TINYINT(1) NOT NULL DEFAULT 0,
    is_deleted_recipient TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sender (sender_id),
    INDEX idx_recipient (recipient_id),
    INDEX idx_thread (parent_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 4. NOTIFICATIONS TABLE
-- ============================================================

CREATE TABLE IF NOT EXISTS staff_notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type VARCHAR(50) NOT NULL DEFAULT 'info',
    icon VARCHAR(50) DEFAULT 'fa-bell',
    url VARCHAR(500) DEFAULT NULL,
    priority ENUM('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    target_role VARCHAR(80) DEFAULT NULL COMMENT 'NULL=all roles',
    target_user_id INT UNSIGNED DEFAULT NULL,
    created_by INT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_target_user (target_user_id),
    INDEX idx_read (is_read),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- 5. STAFF NOTIFICATION READS (per-user read tracking)
-- ============================================================

CREATE TABLE IF NOT EXISTS staff_notification_reads (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    notification_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    read_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_read (notification_id, user_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- VERIFICATION QUERIES
-- ============================================================

-- Verify fixed approval requests
SELECT id, workflow_id, current_stage_id, title, status, priority, priority_order
  FROM approval_requests
 ORDER BY priority_order, id;

-- Verify create actions exist for all requests
SELECT ar.id AS request_id, ar.title, aa.action_type, aa.created_at
  FROM approval_requests ar
  LEFT JOIN approval_actions aa ON ar.id = aa.request_id AND aa.action_type = 'create'
 ORDER BY ar.id;

-- Verify new tables exist
SELECT TABLE_NAME, ENGINE
  FROM information_schema.TABLES
 WHERE TABLE_SCHEMA = 'igangaschoolofl_staffs_db'
   AND TABLE_NAME IN ('staff_inbox', 'staff_notifications', 'staff_notification_reads');
