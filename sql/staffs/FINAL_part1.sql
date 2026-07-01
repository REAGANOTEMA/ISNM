-- ============================================================
-- PART 1: Add priority_order column
-- Run this FIRST on HOSTING phpMyAdmin
-- If you get "#1060 Duplicate column name" error, that's OK!
-- It means the column already exists. Just continue to Part 2.
-- ============================================================

ALTER TABLE approval_requests ADD COLUMN priority_order SMALLINT UNSIGNED NOT NULL DEFAULT 2 AFTER priority;

SELECT 'Done - if duplicate column error, column already exists' as result;
