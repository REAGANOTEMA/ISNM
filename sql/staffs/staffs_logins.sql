-- ISNM STAFF LOGINS — CLEAN (25 accounts only)
-- Generated from current database state
-- Run in: igangaschoolofl_staffs_db

USE `igangaschoolofl_staffs_db`;

-- Ensure all roles exist
INSERT IGNORE INTO staff_roles (role_name, role_description, role_level, dashboard_path, permissions) VALUES
('Director General', 'Overall school administration and management with full access to all modules and departments', 'Executive', 'dashboards/director-general.php', '{\"all\": true, \"can_view_hr\": true, \"super_admin\": true, \"can_edit_all_data\": true, \"can_view_academic\": true, \"can_view_students\": true, \"can_view_financial\": true, \"can_delete_all_data\": true, \"can_manage_all_staff\": true, \"can_view_all_records\": true, \"can_view_all_departments\": true, \"can_access_all_dashboards\": true}'),
('School Principal', 'School academic and administrative leadership with cross-departmental viewing access', 'Executive', 'dashboards/school-principal.php', '{\"staff\": true, \"academic\": true, \"students\": true, \"can_view_hr\": true, \"administrative\": true, \"can_view_academic\": true, \"can_view_students\": true, \"can_view_financial\": true, \"can_view_all_records\": true, \"can_edit_own_department\": true, \"can_view_all_departments\": true, \"can_view_other_departments\": true}'),
('CEO', 'Chief Executive Officer for strategic management with cross-departmental viewing access', 'Executive', 'dashboards/ceo.php', '{\"financial\": true, \"strategic\": true, \"can_view_hr\": true, \"operational\": true, \"can_view_reports\": true, \"can_view_academic\": true, \"can_view_students\": true, \"can_view_financial\": true, \"can_view_all_records\": true, \"can_view_all_departments\": true, \"can_view_other_departments\": true}'),
('Director Academics', 'Academic programs and curriculum oversight with cross-departmental viewing access', 'Management', 'dashboards/director-academics.php', '{\"faculty\": true, \"academic\": true, \"curriculum\": true, \"can_view_hr\": true, \"can_view_academic\": true, \"can_view_students\": true, \"can_manage_courses\": true, \"can_view_financial\": true, \"can_view_all_records\": true, \"can_edit_own_department\": true, \"can_view_all_departments\": true, \"can_view_other_departments\": true}'),
('Director Finance', 'Financial management and oversight with cross-departmental viewing access', 'Management', 'dashboards/director-finance.php', '{\"budgeting\": true, \"financial\": true, \"reporting\": true, \"can_view_hr\": true, \"can_view_academic\": true, \"can_view_students\": true, \"can_view_financial\": true, \"can_manage_finances\": true, \"can_view_all_records\": true, \"can_edit_own_department\": true, \"can_view_all_departments\": true, \"can_view_other_departments\": true}'),
('Director ICT', 'Information Technology management with cross-departmental viewing access', 'Management', 'dashboards/director-ict.php', '{\"ict\": true, \"systems\": true, \"can_view_hr\": true, \"infrastructure\": true, \"can_manage_system\": true, \"can_view_academic\": true, \"can_view_students\": true, \"can_view_financial\": true, \"can_view_all_records\": true, \"can_edit_own_department\": true, \"can_view_all_departments\": true, \"can_view_other_departments\": true}'),
('HR Manager', 'Human resources management', 'Management', 'dashboards/hr-manager.php', '{\"hr\": true, \"staff\": true, \"training\": true, \"recruitment\": true, \"can_manage_staff\": true}'),
('Academic Registrar', 'Student registration and academic records management', 'Academic', 'dashboards/academic-registrar.php', '{\"academic\": true, \"students\": true, \"transcripts\": true, \"certificates\": true, \"registration\": true}'),
('School Bursar', 'Financial operations and fee management', 'Administrative', 'bursar_dashboard.php', '{\"fees\": true, \"financial\": true, \"collections\": true, \"can_manage_fees\": true}'),
('School Librarian', 'Library and resource management', 'Support', 'dashboards/school-librarian.php', '{\"catalog\": true, \"library\": true, \"resources\": true}'),
('Head Nursing', 'Nursing department management', 'Academic', 'dashboards/head-nursing.php', '{\"faculty\": true, \"nursing\": true, \"department\": true}'),
('Head Midwifery', 'Midwifery department management', 'Academic', 'dashboards/head-midwifery.php', '{\"faculty\": true, \"midwifery\": true, \"department\": true}'),
('Lecturers', 'Teaching and academic staff management', 'Academic', 'dashboards/lecturers.php', '{\"courses\": true, \"teaching\": true, \"lecturers\": true}'),
('Senior Lecturers', 'Senior teaching staff management', 'Academic', 'dashboards/senior-lecturers.php', '{\"senior\": true, \"teaching\": true, \"lecturers\": true}'),
('Sickbay', 'Medical and healthcare support services', 'Support', 'dashboards/sickbay.php', '{\"medical\": true, \"patient\": true, \"healthcare\": true}'),
('Matrons', 'Student welfare and residential staff management', 'Support', 'dashboards/matrons.php', '{\"residential\": true, \"student_welfare\": true}'),
('Security', 'Campus security and safety management', 'Support', 'dashboards/security.php', '{\"safety\": true, \"security\": true, \"emergency\": true}'),
('Drivers', 'Transportation and vehicle management', 'Support', 'dashboards/drivers.php', '{\"vehicles\": true, \"transportation\": true}'),
('Wardens', 'Student discipline and residential supervision', 'Support', 'dashboards/wardens.php', '{\"discipline\": true, \"residential\": true, \"student_welfare\": true}'),
('School Secretary', 'Administrative support and documentation', 'Administrative', 'dashboards/school-secretary.php', '{\"documentation\": true, \"administrative\": true, \"can_manage_documents\": true}'),
('Deputy Principal', 'Assistant to school principal', 'Management', 'dashboards/deputy-principal.php', '{\"academic\": true, \"administrative\": true, \"can_assist_principal\": true}'),
('Store Keeper', 'Manage store inventory for general utilities and food supplies', 'Support', 'dashboards/storekeeper.php', '{\"store\": true, \"inventory\": true, \"can_manage_store\": true}'),
('Guild President', 'Student guild', 'Support', 'dashboards/guild-president.php', '{\"student_affairs\": true}'),
('Director Admissions & Requirements', 'Admissions management', 'Management', 'dashboards/director-admissions.php', '{\"admissions\": true, \"requirements\": true}');

-- Insert/update all staff accounts
INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('DG001', 'Director General', 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$RU6PrzIHTgggFW3sUy.e8eYEvlvzikGAU6RRa8pgv9c/x647piOqK', 'Director General', 'Executive Office', (SELECT id FROM staff_roles WHERE role_name = 'Director General' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$RU6PrzIHTgggFW3sUy.e8eYEvlvzikGAU6RRa8pgv9c/x647piOqK', status = 'Active', is_first_login = TRUE;

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('CEO001', 'CEO', 'ceo@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$rBRqOoVh5m0Igx4kdXtpfOFwPIDweterjIksjISwN4FCu..HteWam', 'Chief Executive Officer', 'Executive Office', (SELECT id FROM staff_roles WHERE role_name = 'CEO' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'ceo@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$rBRqOoVh5m0Igx4kdXtpfOFwPIDweterjIksjISwN4FCu..HteWam', status = 'Active', is_first_login = TRUE;

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('SP001', 'School Principal', 'principal@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$l6XsX6XqY.Pbcd7XDzGjxeOufB1j9XfcNlv3kJGs3.MX79JWKs4ti', 'School Principal', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'School Principal' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'principal@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$l6XsX6XqY.Pbcd7XDzGjxeOufB1j9XfcNlv3kJGs3.MX79JWKs4ti', status = 'Active', is_first_login = TRUE;

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('SEC001', 'School Secretary', 'secretary@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$m3Fosy0PWOX2NDhe.H83bOrdOamiuFvKjjv3gmL591/c/f7UU6Utm', 'School Secretary', 'Administrative Office', (SELECT id FROM staff_roles WHERE role_name = 'School Secretary' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'secretary@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$m3Fosy0PWOX2NDhe.H83bOrdOamiuFvKjjv3gmL591/c/f7UU6Utm', status = 'Active', is_first_login = TRUE;

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('AR001', 'Academic Registrar', 'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$Cbs9kpWc7uh2KbzRTr9qNuKUmKBAG7UDxb7SE4TbebziRQRlSy8YW', 'Academic Registrar', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Academic Registrar' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$Cbs9kpWc7uh2KbzRTr9qNuKUmKBAG7UDxb7SE4TbebziRQRlSy8YW', status = 'Active', is_first_login = TRUE;

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('BUR001', 'School Bursar', 'bursar@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$0z4Ii3PfeqVdR3uul1iczO5YJ2NjVg49Qla8PQ0GpUufYd0v5saXS', 'School Bursar', 'Finance Department', (SELECT id FROM staff_roles WHERE role_name = 'School Bursar' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'bursar@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$0z4Ii3PfeqVdR3uul1iczO5YJ2NjVg49Qla8PQ0GpUufYd0v5saXS', status = 'Active', is_first_login = TRUE;

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('HR001', 'HR Manager', 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$hesFYTZgh9X4Q2FyXU/4neiB7vkoIr15zwMa.R17g4DhWx2umLk22', 'HR Manager', 'Human Resources', (SELECT id FROM staff_roles WHERE role_name = 'HR Manager' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$hesFYTZgh9X4Q2FyXU/4neiB7vkoIr15zwMa.R17g4DhWx2umLk22', status = 'Active', is_first_login = TRUE;

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('DA001', 'Director Academics', 'directoracademic@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$HbQJxmFSl2mCzxW83atloemF/UBA7sg9RKA6TqN7Mb9iKTrTdACtm', 'Director Academics', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Director Academics' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'directoracademic@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$HbQJxmFSl2mCzxW83atloemF/UBA7sg9RKA6TqN7Mb9iKTrTdACtm', status = 'Active', is_first_login = TRUE;

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('DI001', 'Director ICT', 'dannybict@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$5BFMhfh8zO9myR6Ha8w.g.UL0PCEIcIWXGeTbPmOF1lQizIU0Wsm2', 'Director ICT', 'Information Technology', (SELECT id FROM staff_roles WHERE role_name = 'Director ICT' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'dannybict@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$5BFMhfh8zO9myR6Ha8w.g.UL0PCEIcIWXGeTbPmOF1lQizIU0Wsm2', status = 'Active', is_first_login = TRUE;

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('DF001', 'Director Finance', 'finance@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$uZQlJ0YNKT7FzCD7cjEVNOaXDD7xnPbqT.2Nv6qBO5O5KgaMx7NBC', 'Director Finance', 'Finance Department', (SELECT id FROM staff_roles WHERE role_name = 'Director Finance' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'finance@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$uZQlJ0YNKT7FzCD7cjEVNOaXDD7xnPbqT.2Nv6qBO5O5KgaMx7NBC', status = 'Active', is_first_login = TRUE;

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('LIB001', 'School Librarian', 'library@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$3E1cG3FKr.3hRqZr.9a.j.ljYkuj/zAl376Gb8oakdPHw0nLrCqgu', 'School Librarian', 'Library Services', (SELECT id FROM staff_roles WHERE role_name = 'School Librarian' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'library@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$3E1cG3FKr.3hRqZr.9a.j.ljYkuj/zAl376Gb8oakdPHw0nLrCqgu', status = 'Active', is_first_login = TRUE;

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('HN001', 'Head Nursing', 'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$C6xhOfyPi4nj/kwaZfmoWeExbQHfnbceO7enAKt/oqs9jrdD4e7JK', 'Head Nursing', 'Nursing Department', (SELECT id FROM staff_roles WHERE role_name = 'Head Nursing' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$C6xhOfyPi4nj/kwaZfmoWeExbQHfnbceO7enAKt/oqs9jrdD4e7JK', status = 'Active', is_first_login = TRUE;

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('HM001', 'Head Midwifery', 'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$LHAORiiXnly8kcd1sCJOP.r9/kJAq65lvEszWfk7DWcTeYPnNqYIO', 'Head Midwifery', 'Midwifery Department', (SELECT id FROM staff_roles WHERE role_name = 'Head Midwifery' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$LHAORiiXnly8kcd1sCJOP.r9/kJAq65lvEszWfk7DWcTeYPnNqYIO', status = 'Active', is_first_login = TRUE;

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('LEC001', 'Lecturers', 'lecturers@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$dqYUVn3eri6frqS.fmqeGuvSyQ1jXZGcGoOTgqmSi0ccxuAkejS/S', 'Lecturer', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Lecturers' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'lecturers@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$dqYUVn3eri6frqS.fmqeGuvSyQ1jXZGcGoOTgqmSi0ccxuAkejS/S', status = 'Active', is_first_login = TRUE;

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('SLE001', 'Senior Lecturers', 'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$Z7GAj95kaxlSA1vf3ebGG.fg8uNhH0dce8FtksFqTUbk6/tRS5yAW', 'Senior Lecturer', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Senior Lecturers' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$Z7GAj95kaxlSA1vf3ebGG.fg8uNhH0dce8FtksFqTUbk6/tRS5yAW', status = 'Active', is_first_login = TRUE;

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('LAB001', 'Sickbay', 'sickbay@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$RR6yvXWHLGgbWPpQN09Jv.LX9PvVfqnBvSTIz1gP6CsH4qMNkGxyO', 'Sickbay', 'Support', (SELECT id FROM staff_roles WHERE role_name = 'Sickbay' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'sickbay@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$RR6yvXWHLGgbWPpQN09Jv.LX9PvVfqnBvSTIz1gP6CsH4qMNkGxyO', status = 'Active', is_first_login = TRUE;

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('MAT001', 'Matrons', 'matron@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$BC8eNBiywm3cjp1CrGNNqefq28VFu5/ww6ZK73C7QYs8VarKkcwea', 'Matrons', 'Student Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Matrons' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'matron@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$BC8eNBiywm3cjp1CrGNNqefq28VFu5/ww6ZK73C7QYs8VarKkcwea', status = 'Active', is_first_login = TRUE;

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('SECUR001', 'Security', 'security@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$X9ngKJfjBhCXBlHYUEaZcuTRE60vcbKyyYRZJEPNZXAEeYrUUGYZO', 'Security', 'Security Services', (SELECT id FROM staff_roles WHERE role_name = 'Security' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'security@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$X9ngKJfjBhCXBlHYUEaZcuTRE60vcbKyyYRZJEPNZXAEeYrUUGYZO', status = 'Active', is_first_login = TRUE;

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('DRV001', 'Drivers', 'drivers@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$cogVgG3L7gIkPuGxrxzKm.kCRxTq9HnffahEUnSPACv.s8JBAsNeK', 'Drivers', 'Transport', (SELECT id FROM staff_roles WHERE role_name = 'Drivers' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'drivers@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$cogVgG3L7gIkPuGxrxzKm.kCRxTq9HnffahEUnSPACv.s8JBAsNeK', status = 'Active', is_first_login = TRUE;

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('WDN001', 'Wardens', 'warden@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$FxhwWDM4Xp0bDs5WRAxbyObpbvZDvOOHf52yiamFGTmdGUQpdAUcK', 'Wardens', 'Student Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Wardens' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'warden@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$FxhwWDM4Xp0bDs5WRAxbyObpbvZDvOOHf52yiamFGTmdGUQpdAUcK', status = 'Active', is_first_login = TRUE;

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('DP001', 'Deputy Principal', 'dep-principal@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$9fOKtTQAgB/elswK9HgmE.BwFEvqipIFnSmcvJPeXpEgb3KXa8D.m', 'Deputy Principal', 'Academic Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Deputy Principal' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'dep-principal@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$9fOKtTQAgB/elswK9HgmE.BwFEvqipIFnSmcvJPeXpEgb3KXa8D.m', status = 'Active', is_first_login = TRUE;

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('STK001', 'Store Keeper', 'store@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$M6cl6Y9PoVugM6mlWnyEBeGOChjv8fsi3tDMFMqP43bOG6TGELDje', 'Store Keeper', 'Facilities Management', (SELECT id FROM staff_roles WHERE role_name = 'Store Keeper' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'store@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$M6cl6Y9PoVugM6mlWnyEBeGOChjv8fsi3tDMFMqP43bOG6TGELDje', status = 'Active', is_first_login = TRUE;

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('ICT001', 'ICT Department', 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$RLKRWsU8ITHZ9MEhARDfieO.bJc7S7RsV5uoX51kn0PSK79mnYcEa', 'Director ICT', 'Information Communication Technology', (SELECT id FROM staff_roles WHERE role_name = 'Director ICT' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$RLKRWsU8ITHZ9MEhARDfieO.bJc7S7RsV5uoX51kn0PSK79mnYcEa', status = 'Active', is_first_login = TRUE;

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('GUILD001', 'Guild President', 'guildpresident@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$wLikyrgK4SzFsYDJ5BpOnOUXG9/qOh2DuoT6ud/jVVF/eQxfEKxDC', 'Guild President', 'Student Affairs', (SELECT id FROM staff_roles WHERE role_name = 'Guild President' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'guildpresident@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$wLikyrgK4SzFsYDJ5BpOnOUXG9/qOh2DuoT6ud/jVVF/eQxfEKxDC', status = 'Active', is_first_login = TRUE;

INSERT INTO staff (staff_id, full_name, email, password, position, department, role_id, status, hire_date, password_changed, is_first_login, created_at) VALUES
('ADM001', 'Director Admissions', 'admissions@igangaschoolofnursingandmidwifery.ac.ug', '$2y$10$ObioUw9rfd59rbVBHxw60.e7n83Fmzjbtr.ZPJJgqRroTg1DI1KHy', 'Director Admissions & Requirements', 'Admissions', (SELECT id FROM staff_roles WHERE role_name = 'Director Admissions & Requirements' LIMIT 1), 'Active', CURDATE(), FALSE, TRUE, NOW())
ON DUPLICATE KEY UPDATE email = 'admissions@igangaschoolofnursingandmidwifery.ac.ug', password = '$2y$10$ObioUw9rfd59rbVBHxw60.e7n83Fmzjbtr.ZPJJgqRroTg1DI1KHy', status = 'Active', is_first_login = TRUE;

-- Reset all locks
UPDATE staff SET login_attempts = 0, locked_until = NULL WHERE status = 'Active';

SELECT CONCAT('Setup complete: ', COUNT(*), ' staff accounts') AS result FROM staff;
