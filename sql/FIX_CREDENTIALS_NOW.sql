-- ================================================================
-- ISNM FIX CREDENTIALS — RUN THIS IN phpMyAdmin (igangaschoolofl_staffs_db)
-- ================================================================
-- WHAT THIS DOES:
--  1. Resets ALL account locks (login_attempts=0, locked_until=NULL)
--  2. Updates EVERY staff account with the correct bcrypt password hash
--  3. Covers BOTH email domains for compatibility
--  4. Inserts missing accounts (nonteaching, bursar assistant)
-- ================================================================

USE `igangaschoolofl_staffs_db`;
SET FOREIGN_KEY_CHECKS = 0;

-- ================================================================
-- STEP 1: UNLOCK ALL ACCOUNTS
-- ================================================================
UPDATE staff SET login_attempts = 0, locked_until = NULL WHERE status = 'Active';

-- ================================================================
-- STEP 2: UPDATE PASSWORDS FOR EXISTING STAFF
-- Matches on BOTH old (@isnm.ac.ug) and new (@igangaschoolofnursing...) emails
-- ================================================================

-- Password reference:
--   DorisJoy2026 = $2y$10$04.DbEy7SaXjwn0PeXx/R.skf7WR.PWLfLQymxPp4DBUvwwnKWceu
--   Lovely2God   = $2y$10$aLC/GlscC9hyn7PGTLtceeNaIrtSBOb3nN1zNq3WbGOoqUlq8t9n2
--   Stephen123   = $2y$10$s4HcGHA15OWVOUKHrc1zU.PiP3ZCYf0BGh.JLgCJR0R18zy0tzJzO
--   isnm2026     = $2y$10$.qu0222yVPH6N5l6Tpip8O88DlQKRUB3YaWTRFKKX0w15dO05Zo8u
--   Isnm2026     = $2y$10$fz2wUW.m5nI4yttBCyCq9.5GIyWWMN89159ZbKVeyhmJSX7PCxm8i
--   Alexis2026   = $2y$10$iC0FRXpscCKuQDj8t/RQNe8HP1szuxTU/O6apCjZfK2QnEIfIwYIG
--   isnm4life    = $2y$10$n3PwvLoehqkf9IJa1FUWHuWqTjjq1RXnPJqsKp8H30Nm2Iu0eQk6K
--   Life2save    = $2y$10$8nuoLwahPQEikvcIncW/R.TxQQGFVDWDWI9EVL3ZROdhtffuXd3Nu
--   Isnm4life    = $2y$10$EX4Ohm6Um/gOFU9y9Nyo..PnwqwdkL1XTBvodZ7G.eu1HwUBWrdzS
--   safty1st     = $2y$10$yemIoQdAtXgk3ZqeN.MvauNi3xNGe3VH3s8MmG33pgyiWVoJe02AW
--   2268926931   = $2y$10$jsElwWv0bhBo8qOF3/47ze9HEwR.7UiRvmgI0wE2G4gkylrbTnIG.
--   Techno123    = $2y$10$urZgn8VmD81qHF6.lQ3eoOJD4TdHGdreDzfotucSdrIgmEqDqeHgu

-- Director General — DorisJoy2026
UPDATE staff SET password = '$2y$10$04.DbEy7SaXjwn0PeXx/R.skf7WR.PWLfLQymxPp4DBUvwwnKWceu', login_attempts = 0, locked_until = NULL
WHERE LOWER(email) IN ('directorgeneral@igangaschoolofnursingandmidwifery.ac.ug','director.general@isnm.ac.ug');

-- CEO — Lovely2God
UPDATE staff SET password = '$2y$10$aLC/GlscC9hyn7PGTLtceeNaIrtSBOb3nN1zNq3WbGOoqUlq8t9n2', login_attempts = 0, locked_until = NULL
WHERE LOWER(email) IN ('ceo@igangaschoolofnursingandmidwifery.ac.ug','ceo@isnm.ac.ug');

-- Director Academics — Stephen123
UPDATE staff SET password = '$2y$10$s4HcGHA15OWVOUKHrc1zU.PiP3ZCYf0BGh.JLgCJR0R18zy0tzJzO', login_attempts = 0, locked_until = NULL
WHERE LOWER(email) IN ('directoracademic@igangaschoolofnursingandmidwifery.ac.ug','director.academics@isnm.ac.ug');

-- Director Finance — DorisJoy2026
UPDATE staff SET password = '$2y$10$04.DbEy7SaXjwn0PeXx/R.skf7WR.PWLfLQymxPp4DBUvwwnKWceu', login_attempts = 0, locked_until = NULL
WHERE LOWER(email) IN ('finance@igangaschoolofnursingandmidwifery.ac.ug','director.finance@isnm.ac.ug');

-- School Principal — isnm2026
UPDATE staff SET password = '$2y$10$.qu0222yVPH6N5l6Tpip8O88DlQKRUB3YaWTRFKKX0w15dO05Zo8u', login_attempts = 0, locked_until = NULL
WHERE LOWER(email) IN ('principal@igangaschoolofnursingandmidwifery.ac.ug');

-- Deputy Principal — Isnm2026
UPDATE staff SET password = '$2y$10$fz2wUW.m5nI4yttBCyCq9.5GIyWWMN89159ZbKVeyhmJSX7PCxm8i', login_attempts = 0, locked_until = NULL
WHERE LOWER(email) IN ('dep-principal@igangaschoolofnursingandmidwifery.ac.ug');

-- Academic Registrar — Lovely2God
UPDATE staff SET password = '$2y$10$aLC/GlscC9hyn7PGTLtceeNaIrtSBOb3nN1zNq3WbGOoqUlq8t9n2', login_attempts = 0, locked_until = NULL
WHERE LOWER(email) IN ('academicregistrar@igangaschoolofnursingandmidwifery.ac.ug');

-- HR Manager — Alexis2026
UPDATE staff SET password = '$2y$10$iC0FRXpscCKuQDj8t/RQNe8HP1szuxTU/O6apCjZfK2QnEIfIwYIG', login_attempts = 0, locked_until = NULL
WHERE LOWER(email) IN ('hr-manager@igangaschoolofnursingandmidwifery.ac.ug');

-- School Secretary — Lovely2God
UPDATE staff SET password = '$2y$10$aLC/GlscC9hyn7PGTLtceeNaIrtSBOb3nN1zNq3WbGOoqUlq8t9n2', login_attempts = 0, locked_until = NULL
WHERE LOWER(email) IN ('secretary@igangaschoolofnursingandmidwifery.ac.ug');

-- School Librarian — isnm2026
UPDATE staff SET password = '$2y$10$.qu0222yVPH6N5l6Tpip8O88DlQKRUB3YaWTRFKKX0w15dO05Zo8u', login_attempts = 0, locked_until = NULL
WHERE LOWER(email) IN ('library@igangaschoolofnursingandmidwifery.ac.ug');

-- Head Nursing — isnm4life
UPDATE staff SET password = '$2y$10$n3PwvLoehqkf9IJa1FUWHuWqTjjq1RXnPJqsKp8H30Nm2Iu0eQk6K', login_attempts = 0, locked_until = NULL
WHERE LOWER(email) IN ('nursing-dep@igangaschoolofnursingandmidwifery.ac.ug');

-- Head Midwifery — Life2save
UPDATE staff SET password = '$2y$10$8nuoLwahPQEikvcIncW/R.TxQQGFVDWDWI9EVL3ZROdhtffuXd3Nu', login_attempts = 0, locked_until = NULL
WHERE LOWER(email) IN ('midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug');

-- Senior Lecturers — isnm2026
UPDATE staff SET password = '$2y$10$.qu0222yVPH6N5l6Tpip8O88DlQKRUB3YaWTRFKKX0w15dO05Zo8u', login_attempts = 0, locked_until = NULL
WHERE LOWER(email) IN ('senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug');

-- Lecturers — Isnm4life
UPDATE staff SET password = '$2y$10$EX4Ohm6Um/gOFU9y9Nyo..PnwqwdkL1XTBvodZ7G.eu1HwUBWrdzS', login_attempts = 0, locked_until = NULL
WHERE LOWER(email) IN ('lecturers@igangaschoolofnursingandmidwifery.ac.ug');

-- Matrons — Isnm2026
UPDATE staff SET password = '$2y$10$fz2wUW.m5nI4yttBCyCq9.5GIyWWMN89159ZbKVeyhmJSX7PCxm8i', login_attempts = 0, locked_until = NULL
WHERE LOWER(email) IN ('matron@igangaschoolofnursingandmidwifery.ac.ug');

-- Wardens — Lovely2God
UPDATE staff SET password = '$2y$10$aLC/GlscC9hyn7PGTLtceeNaIrtSBOb3nN1zNq3WbGOoqUlq8t9n2', login_attempts = 0, locked_until = NULL
WHERE LOWER(email) IN ('warden@igangaschoolofnursingandmidwifery.ac.ug');

-- Sickbay — isnm2026
UPDATE staff SET password = '$2y$10$.qu0222yVPH6N5l6Tpip8O88DlQKRUB3YaWTRFKKX0w15dO05Zo8u', login_attempts = 0, locked_until = NULL
WHERE LOWER(email) IN ('sickbay@igangaschoolofnursingandmidwifery.ac.ug');

-- Drivers — isnm4life
UPDATE staff SET password = '$2y$10$n3PwvLoehqkf9IJa1FUWHuWqTjjq1RXnPJqsKp8H30Nm2Iu0eQk6K', login_attempts = 0, locked_until = NULL
WHERE LOWER(email) IN ('drivers@igangaschoolofnursingandmidwifery.ac.ug');

-- Security — safty1st
UPDATE staff SET password = '$2y$10$yemIoQdAtXgk3ZqeN.MvauNi3xNGe3VH3s8MmG33pgyiWVoJe02AW', login_attempts = 0, locked_until = NULL
WHERE LOWER(email) IN ('security@igangaschoolofnursingandmidwifery.ac.ug');

-- Store Keeper — Isnm4life
UPDATE staff SET password = '$2y$10$EX4Ohm6Um/gOFU9y9Nyo..PnwqwdkL1XTBvodZ7G.eu1HwUBWrdzS', login_attempts = 0, locked_until = NULL
WHERE LOWER(email) IN ('store@igangaschoolofnursingandmidwifery.ac.ug');

-- Guild President — isnm4life
UPDATE staff SET password = '$2y$10$n3PwvLoehqkf9IJa1FUWHuWqTjjq1RXnPJqsKp8H30Nm2Iu0eQk6K', login_attempts = 0, locked_until = NULL
WHERE LOWER(email) IN ('guildpresident@igangaschoolofnursingandmidwifery.ac.ug');

-- Director Admissions — 2268926931
UPDATE staff SET password = '$2y$10$jsElwWv0bhBo8qOF3/47ze9HEwR.7UiRvmgI0wE2G4gkylrbTnIG.', login_attempts = 0, locked_until = NULL
WHERE LOWER(email) IN ('admissions@igangaschoolofnursingandmidwifery.ac.ug');

-- Director ICT (Danny) — Lovely2God
UPDATE staff SET password = '$2y$10$aLC/GlscC9hyn7PGTLtceeNaIrtSBOb3nN1zNq3WbGOoqUlq8t9n2', login_attempts = 0, locked_until = NULL
WHERE LOWER(email) IN ('dannybict@igangaschoolofnursingandmidwifery.ac.ug','director.ict@isnm.ac.ug');

-- Computer Lab — Techno123
UPDATE staff SET password = '$2y$10$urZgn8VmD81qHF6.lQ3eoOJD4TdHGdreDzfotucSdrIgmEqDqeHgu', login_attempts = 0, locked_until = NULL
WHERE LOWER(email) IN ('computer-lab@igangaschoolofnursingandmidwifery.ac.ug');

-- School Bursar — bursar@isnm
UPDATE staff SET password = '$2y$10$RRbhT2PyL7yHVzJd5El5We3U9PAxBI1CES7x9OlTLK.MgGm9K.F7a', login_attempts = 0, locked_until = NULL
WHERE LOWER(email) IN ('bursar@igangaschoolofnursingandmidwifery.ac.ug','bursar@isnm.ac.ug');

-- Non-Teaching Staff — Lovely2God
UPDATE staff SET password = '$2y$10$aLC/GlscC9hyn7PGTLtceeNaIrtSBOb3nN1zNq3WbGOoqUlq8t9n2', login_attempts = 0, locked_until = NULL
WHERE LOWER(email) IN ('nonteaching@isnm.ac.ug');

-- Bursar Assistant — Lovely2God
UPDATE staff SET password = '$2y$10$aLC/GlscC9hyn7PGTLtceeNaIrtSBOb3nN1zNq3WbGOoqUlq8t9n2', login_attempts = 0, locked_until = NULL
WHERE LOWER(email) IN ('bursar.assistant@isnm.ac.ug');

-- ================================================================
-- STEP 3: ENSURE MISSING ACCOUNTS EXIST
-- (nonteaching, bursar.assistant, etc.)
-- ================================================================

-- Insert Non-Teaching Staff if missing
INSERT IGNORE INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at)
SELECT 'NTS001', 'Non-Teaching Staff', 'nonteaching@isnm.ac.ug', '$2y$10$aLC/GlscC9hyn7PGTLtceeNaIrtSBOb3nN1zNq3WbGOoqUlq8t9n2', 'Non-Teaching Staff', 'Administrative', id, 'Active', CURDATE(), 0, 1, NOW()
FROM staff_roles WHERE role_name = 'Non-Teaching Staff'
AND NOT EXISTS (SELECT 1 FROM staff WHERE email = 'nonteaching@isnm.ac.ug');

-- Insert Bursar Assistant if missing
INSERT IGNORE INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at)
SELECT 'BURS002', 'Bursar Assistant', 'bursar.assistant@isnm.ac.ug', '$2y$10$aLC/GlscC9hyn7PGTLtceeNaIrtSBOb3nN1zNq3WbGOoqUlq8t9n2', 'Bursar', 'Finance Department', id, 'Active', CURDATE(), 0, 1, NOW()
FROM staff_roles WHERE role_name = 'Bursar'
AND NOT EXISTS (SELECT 1 FROM staff WHERE email = 'bursar.assistant@isnm.ac.ug');

SET FOREIGN_KEY_CHECKS = 1;

-- ================================================================
-- VERIFICATION
-- ================================================================
SELECT staff_id, full_name, email, position, status FROM staff ORDER BY id;
SELECT 'FIX COMPLETE' AS result;
