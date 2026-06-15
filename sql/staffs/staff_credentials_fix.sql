-- ISNM STAFF CREDENTIALS FIX — CLEAN (25 accounts only)
-- Run in: igangaschoolofl_staffs_db

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

UPDATE staff SET password = '$2y$10$RU6PrzIHTgggFW3sUy.e8eYEvlvzikGAU6RRa8pgv9c/x647piOqK', password_changed = FALSE, is_first_login = TRUE, status = 'Active' WHERE email = 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$rBRqOoVh5m0Igx4kdXtpfOFwPIDweterjIksjISwN4FCu..HteWam', password_changed = FALSE, is_first_login = TRUE, status = 'Active' WHERE email = 'ceo@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$l6XsX6XqY.Pbcd7XDzGjxeOufB1j9XfcNlv3kJGs3.MX79JWKs4ti', password_changed = FALSE, is_first_login = TRUE, status = 'Active' WHERE email = 'principal@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$m3Fosy0PWOX2NDhe.H83bOrdOamiuFvKjjv3gmL591/c/f7UU6Utm', password_changed = FALSE, is_first_login = TRUE, status = 'Active' WHERE email = 'secretary@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$Cbs9kpWc7uh2KbzRTr9qNuKUmKBAG7UDxb7SE4TbebziRQRlSy8YW', password_changed = FALSE, is_first_login = TRUE, status = 'Active' WHERE email = 'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$0z4Ii3PfeqVdR3uul1iczO5YJ2NjVg49Qla8PQ0GpUufYd0v5saXS', password_changed = FALSE, is_first_login = TRUE, status = 'Active' WHERE email = 'bursar@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$hesFYTZgh9X4Q2FyXU/4neiB7vkoIr15zwMa.R17g4DhWx2umLk22', password_changed = FALSE, is_first_login = TRUE, status = 'Active' WHERE email = 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$HbQJxmFSl2mCzxW83atloemF/UBA7sg9RKA6TqN7Mb9iKTrTdACtm', password_changed = FALSE, is_first_login = TRUE, status = 'Active' WHERE email = 'directoracademic@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$5BFMhfh8zO9myR6Ha8w.g.UL0PCEIcIWXGeTbPmOF1lQizIU0Wsm2', password_changed = FALSE, is_first_login = TRUE, status = 'Active' WHERE email = 'dannybict@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$uZQlJ0YNKT7FzCD7cjEVNOaXDD7xnPbqT.2Nv6qBO5O5KgaMx7NBC', password_changed = FALSE, is_first_login = TRUE, status = 'Active' WHERE email = 'finance@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$3E1cG3FKr.3hRqZr.9a.j.ljYkuj/zAl376Gb8oakdPHw0nLrCqgu', password_changed = FALSE, is_first_login = TRUE, status = 'Active' WHERE email = 'library@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$C6xhOfyPi4nj/kwaZfmoWeExbQHfnbceO7enAKt/oqs9jrdD4e7JK', password_changed = FALSE, is_first_login = TRUE, status = 'Active' WHERE email = 'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$LHAORiiXnly8kcd1sCJOP.r9/kJAq65lvEszWfk7DWcTeYPnNqYIO', password_changed = FALSE, is_first_login = TRUE, status = 'Active' WHERE email = 'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$dqYUVn3eri6frqS.fmqeGuvSyQ1jXZGcGoOTgqmSi0ccxuAkejS/S', password_changed = FALSE, is_first_login = TRUE, status = 'Active' WHERE email = 'lecturers@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$Z7GAj95kaxlSA1vf3ebGG.fg8uNhH0dce8FtksFqTUbk6/tRS5yAW', password_changed = FALSE, is_first_login = TRUE, status = 'Active' WHERE email = 'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$RR6yvXWHLGgbWPpQN09Jv.LX9PvVfqnBvSTIz1gP6CsH4qMNkGxyO', password_changed = FALSE, is_first_login = TRUE, status = 'Active' WHERE email = 'sickbay@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$BC8eNBiywm3cjp1CrGNNqefq28VFu5/ww6ZK73C7QYs8VarKkcwea', password_changed = FALSE, is_first_login = TRUE, status = 'Active' WHERE email = 'matron@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$X9ngKJfjBhCXBlHYUEaZcuTRE60vcbKyyYRZJEPNZXAEeYrUUGYZO', password_changed = FALSE, is_first_login = TRUE, status = 'Active' WHERE email = 'security@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$cogVgG3L7gIkPuGxrxzKm.kCRxTq9HnffahEUnSPACv.s8JBAsNeK', password_changed = FALSE, is_first_login = TRUE, status = 'Active' WHERE email = 'drivers@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$FxhwWDM4Xp0bDs5WRAxbyObpbvZDvOOHf52yiamFGTmdGUQpdAUcK', password_changed = FALSE, is_first_login = TRUE, status = 'Active' WHERE email = 'warden@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$9fOKtTQAgB/elswK9HgmE.BwFEvqipIFnSmcvJPeXpEgb3KXa8D.m', password_changed = FALSE, is_first_login = TRUE, status = 'Active' WHERE email = 'dep-principal@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$M6cl6Y9PoVugM6mlWnyEBeGOChjv8fsi3tDMFMqP43bOG6TGELDje', password_changed = FALSE, is_first_login = TRUE, status = 'Active' WHERE email = 'store@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$RLKRWsU8ITHZ9MEhARDfieO.bJc7S7RsV5uoX51kn0PSK79mnYcEa', password_changed = FALSE, is_first_login = TRUE, status = 'Active' WHERE email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$wLikyrgK4SzFsYDJ5BpOnOUXG9/qOh2DuoT6ud/jVVF/eQxfEKxDC', password_changed = FALSE, is_first_login = TRUE, status = 'Active' WHERE email = 'guildpresident@igangaschoolofnursingandmidwifery.ac.ug';
UPDATE staff SET password = '$2y$10$ObioUw9rfd59rbVBHxw60.e7n83Fmzjbtr.ZPJJgqRroTg1DI1KHy', password_changed = FALSE, is_first_login = TRUE, status = 'Active' WHERE email = 'admissions@igangaschoolofnursingandmidwifery.ac.ug';

SELECT 'All 25 staff credentials updated successfully' AS status;
