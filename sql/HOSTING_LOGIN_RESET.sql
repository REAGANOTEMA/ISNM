-- ============================================================
-- HOSTING LOGIN LOCK RESET
-- Run this on HOSTING database to unlock all accounts
-- ============================================================

-- Reset all staff login locks
UPDATE igangaschoolofl_staffs_db.staff 
SET login_attempts = 0, locked_until = NULL 
WHERE login_attempts > 0 OR locked_until IS NOT NULL;

-- Reset all student login locks
UPDATE igangaschoolofl_students_db.students 
SET login_attempts = 0, locked_until = NULL 
WHERE login_attempts > 0 OR locked_until IS NOT NULL;

-- Verify: these should return 0 locked rows
SELECT 'staff' as db, COUNT(*) as total, 
       SUM(CASE WHEN login_attempts > 0 OR locked_until IS NOT NULL THEN 1 ELSE 0 END) as locked
FROM igangaschoolofl_staffs_db.staff
UNION ALL
SELECT 'students' as db, COUNT(*) as total,
       SUM(CASE WHEN login_attempts > 0 OR locked_until IS NOT NULL THEN 1 ELSE 0 END) as locked
FROM igangaschoolofl_students_db.students;
