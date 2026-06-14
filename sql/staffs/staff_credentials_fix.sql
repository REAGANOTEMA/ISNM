-- ============================================================
-- ISNM STAFF CREDENTIALS FIX
-- Sets correct bcrypt password hashes for all staff accounts
-- Run this in phpMyAdmin on igangaschoolofl_staffs_db
-- ============================================================

USE `igangaschoolofl_staffs_db`;

-- Ensure users view exists for auth compatibility
CREATE OR REPLACE VIEW users AS
SELECT 
    s.id,
    s.staff_id AS username,
    s.full_name AS user_name,
    s.email,
    s.password,
    s.position,
    s.department,
    s.role_id,
    sr.role_name,
    sr.role_level,
    sr.dashboard_path,
    s.status,
    s.phone,
    s.hire_date,
    s.last_login,
    s.login_attempts,
    s.locked_until,
    s.is_first_login,
    s.created_at,
    s.updated_at
FROM staff s
JOIN staff_roles sr ON s.role_id = sr.id;

-- Update all staff passwords to correct bcrypt hashes
-- Each staff can login with their email and specified password

-- Director General: DorisJoy2026
UPDATE staff SET password = '$2y$10$4zBecmIG2/ll1OuUq3oQr.6JRQ3gWZA8OhCYhsHnbGuVUTVLZ2IGq', password_changed = FALSE, is_first_login = TRUE, status = 'Active'
WHERE email = 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug';

-- CEO: Lovely2God
UPDATE staff SET password = '$2y$10$SVxP6T3Btn2EeOu.EQNER.js2Q97GgwlGoHVmg1/gOeepSOi9Xliq', password_changed = FALSE, is_first_login = TRUE, status = 'Active'
WHERE email = 'ceo@igangaschoolofnursingandmidwifery.ac.ug';

-- Director Academics: Stephen123
UPDATE staff SET password = '$2y$10$PZKaqoRDclIhO1cFUcdf7Op5cFWWcIwTraMhWoqOjkkjjidM2R9A2', password_changed = FALSE, is_first_login = TRUE, status = 'Active'
WHERE email = 'directoracademic@igangaschoolofnursingandmidwifery.ac.ug';

-- Director Finance: DorisJoy2026
UPDATE staff SET password = '$2y$10$4zBecmIG2/ll1OuUq3oQr.6JRQ3gWZA8OhCYhsHnbGuVUTVLZ2IGq', password_changed = FALSE, is_first_login = TRUE, status = 'Active'
WHERE email = 'finance@igangaschoolofnursingandmidwifery.ac.ug';

-- School Principal: isnm2026
UPDATE staff SET password = '$2y$10$i9ncKkYwDhg67aiRyJ.IcuIokjymvzpIHiJai6TCc8qAXvyMcbpKy', password_changed = FALSE, is_first_login = TRUE, status = 'Active'
WHERE email = 'principal@igangaschoolofnursingandmidwifery.ac.ug';

-- Deputy Principal: Isnm2026
UPDATE staff SET password = '$2y$10$qa/ObsOT45vtaXBzpM8kZ.WKRZ8ZDpeg/TD9j7Fa223v0B2wVUykC', password_changed = FALSE, is_first_login = TRUE, status = 'Active'
WHERE email = 'dep-principal@igangaschoolofnursingandmidwifery.ac.ug';

-- Academic Registrar: Lovely2God
UPDATE staff SET password = '$2y$10$SVxP6T3Btn2EeOu.EQNER.js2Q97GgwlGoHVmg1/gOeepSOi9Xliq', password_changed = FALSE, is_first_login = TRUE, status = 'Active'
WHERE email = 'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug';

-- HR Manager: Alexis2026
UPDATE staff SET password = '$2y$10$jCjDrn245Sdk0I3dD0GsXuS52TRH4bFZtxmwygreuq2D0JU5LpDuG', password_changed = FALSE, is_first_login = TRUE, status = 'Active'
WHERE email = 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug';

-- School Secretary: Lovely2God
UPDATE staff SET password = '$2y$10$SVxP6T3Btn2EeOu.EQNER.js2Q97GgwlGoHVmg1/gOeepSOi9Xliq', password_changed = FALSE, is_first_login = TRUE, status = 'Active'
WHERE email = 'secretary@igangaschoolofnursingandmidwifery.ac.ug';

-- School Librarian: isnm2026
UPDATE staff SET password = '$2y$10$i9ncKkYwDhg67aiRyJ.IcuIokjymvzpIHiJai6TCc8qAXvyMcbpKy', password_changed = FALSE, is_first_login = TRUE, status = 'Active'
WHERE email = 'library@igangaschoolofnursingandmidwifery.ac.ug';

-- Head Nursing: isnm4life
UPDATE staff SET password = '$2y$10$2.SAjVhZ2FNMyERyUKVimOrTDI7UNRuaD8i57Jjoc1QVt9MX/mYRq', password_changed = FALSE, is_first_login = TRUE, status = 'Active'
WHERE email = 'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug';

-- Head Midwifery: Life2save
UPDATE staff SET password = '$2y$10$sNkPyOEVDL8v88B04VI8xuWtFALPzN/cM4nSc1WTCvzAwT8/YKzaS', password_changed = FALSE, is_first_login = TRUE, status = 'Active'
WHERE email = 'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug';

-- Senior Lecturers: isnm2026
UPDATE staff SET password = '$2y$10$i9ncKkYwDhg67aiRyJ.IcuIokjymvzpIHiJai6TCc8qAXvyMcbpKy', password_changed = FALSE, is_first_login = TRUE, status = 'Active'
WHERE email = 'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug';

-- Lecturers: Isnm4life
UPDATE staff SET password = '$2y$10$57g9UBif6t1rMsY.lsW4EOnlgt/qF66l0vnWeg0kvgSbubL483kZm', password_changed = FALSE, is_first_login = TRUE, status = 'Active'
WHERE email = 'lecturers@igangaschoolofnursingandmidwifery.ac.ug';

-- Matrons: Isnm2026
UPDATE staff SET password = '$2y$10$qa/ObsOT45vtaXBzpM8kZ.WKRZ8ZDpeg/TD9j7Fa223v0B2wVUykC', password_changed = FALSE, is_first_login = TRUE, status = 'Active'
WHERE email = 'matron@igangaschoolofnursingandmidwifery.ac.ug';

-- Wardens: Lovely2God
UPDATE staff SET password = '$2y$10$SVxP6T3Btn2EeOu.EQNER.js2Q97GgwlGoHVmg1/gOeepSOi9Xliq', password_changed = FALSE, is_first_login = TRUE, status = 'Active'
WHERE email = 'warden@igangaschoolofnursingandmidwifery.ac.ug';

-- Sickbay: isnm2026
UPDATE staff SET password = '$2y$10$i9ncKkYwDhg67aiRyJ.IcuIokjymvzpIHiJai6TCc8qAXvyMcbpKy', password_changed = FALSE, is_first_login = TRUE, status = 'Active'
WHERE email = 'sickbay@igangaschoolofnursingandmidwifery.ac.ug';

-- Drivers: isnm4life
UPDATE staff SET password = '$2y$10$2.SAjVhZ2FNMyERyUKVimOrTDI7UNRuaD8i57Jjoc1QVt9MX/mYRq', password_changed = FALSE, is_first_login = TRUE, status = 'Active'
WHERE email = 'drivers@igangaschoolofnursingandmidwifery.ac.ug';

-- Security: safty1st
UPDATE staff SET password = '$2y$10$UMJk5E2utanLv26Ed02LUuORWRKDLlYlW7Sk0BkhTI3ucV3etUusu', password_changed = FALSE, is_first_login = TRUE, status = 'Active'
WHERE email = 'security@igangaschoolofnursingandmidwifery.ac.ug';

-- Store Keeper: Isnm4life
UPDATE staff SET password = '$2y$10$57g9UBif6t1rMsY.lsW4EOnlgt/qF66l0vnWeg0kvgSbubL483kZm', password_changed = FALSE, is_first_login = TRUE, status = 'Active'
WHERE email = 'store@igangaschoolofnursingandmidwifery.ac.ug';

-- Guild President: isnm4life
UPDATE staff SET password = '$2y$10$2.SAjVhZ2FNMyERyUKVimOrTDI7UNRuaD8i57Jjoc1QVt9MX/mYRq', password_changed = FALSE, is_first_login = TRUE, status = 'Active'
WHERE email = 'guildpresident@igangaschoolofnursingandmidwifery.ac.ug';

-- Admissions: 2268926931
UPDATE staff SET password = '$2y$10$5Vqu4b.bnLGkrgc.4eXAa.qzeJEF.I6BVu7MTewaHTPnH2gFxe0QS', password_changed = FALSE, is_first_login = TRUE, status = 'Active'
WHERE email = 'admissions@igangaschoolofnursingandmidwifery.ac.ug';

-- Director ICT (dannybict): Lovely2God
UPDATE staff SET password = '$2y$10$SVxP6T3Btn2EeOu.EQNER.js2Q97GgwlGoHVmg1/gOeepSOi9Xliq', password_changed = FALSE, is_first_login = TRUE, status = 'Active'
WHERE email = 'dannybict@igangaschoolofnursingandmidwifery.ac.ug';

-- Computer Lab: Techno123
UPDATE staff SET password = '$2y$10$m3WByCY455mVgUO/yTPsluFctG9YG7HVC5aon15hmXpKHizE5MKG6', password_changed = FALSE, is_first_login = TRUE, status = 'Active'
WHERE email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug';

-- School Bursar: bursar@isnm
UPDATE staff SET password = '$2y$10$hBMO1ckSzBXfFRE9sYUuAeeJwEp03Vhtrr37j75JOGhoxB8aH3CZ2', password_changed = FALSE, is_first_login = TRUE, status = 'Active'
WHERE email = 'bursar@igangaschoolofnursingandmidwifery.ac.ug';

-- Non-Teaching Staff: staff@123
UPDATE staff SET password = '$2y$10$WXIF1wpBChjduRdL/fDluu56hSxymIeZpJiYiJrdXh7nz0quaODW6', password_changed = FALSE, is_first_login = TRUE, status = 'Active'
WHERE email = 'nonteaching@isnm.ac.ug';

SELECT 'Staff credentials updated successfully' AS status;
SELECT email, position, 'Password updated' AS password_status FROM staff WHERE email IN (
    'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug',
    'ceo@igangaschoolofnursingandmidwifery.ac.ug',
    'directoracademic@igangaschoolofnursingandmidwifery.ac.ug',
    'finance@igangaschoolofnursingandmidwifery.ac.ug',
    'principal@igangaschoolofnursingandmidwifery.ac.ug',
    'dep-principal@igangaschoolofnursingandmidwifery.ac.ug',
    'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug',
    'hr-manager@igangaschoolofnursingandmidwifery.ac.ug',
    'secretary@igangaschoolofnursingandmidwifery.ac.ug',
    'library@igangaschoolofnursingandmidwifery.ac.ug',
    'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug',
    'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug',
    'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug',
    'lecturers@igangaschoolofnursingandmidwifery.ac.ug',
    'matron@igangaschoolofnursingandmidwifery.ac.ug',
    'warden@igangaschoolofnursingandmidwifery.ac.ug',
    'sickbay@igangaschoolofnursingandmidwifery.ac.ug',
    'drivers@igangaschoolofnursingandmidwifery.ac.ug',
    'security@igangaschoolofnursingandmidwifery.ac.ug',
    'store@igangaschoolofnursingandmidwifery.ac.ug',
    'guildpresident@igangaschoolofnursingandmidwifery.ac.ug',
    'admissions@igangaschoolofnursingandmidwifery.ac.ug',
    'dannybict@igangaschoolofnursingandmidwifery.ac.ug',
    'computer-lab@igangaschoolofnursingandmidwifery.ac.ug',
    'bursar@igangaschoolofnursingandmidwifery.ac.ug',
    'nonteaching@isnm.ac.ug'
);
