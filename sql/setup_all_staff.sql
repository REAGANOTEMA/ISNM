-- ============================================================
-- ISNM ERP — COMPLETE STAFF SETUP (Run this in phpMyAdmin)
-- ============================================================

-- 1. Create staff_roles table
CREATE TABLE IF NOT EXISTS `igangaschoolofl_staffs_db`.`staff_roles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `role_name` VARCHAR(100) NOT NULL,
  `role_description` TEXT,
  `role_level` VARCHAR(50) DEFAULT 'staff',
  `dashboard_path` VARCHAR(255) DEFAULT NULL,
  `permissions` JSON DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Populate roles (40 roles)
INSERT IGNORE INTO `igangaschoolofl_staffs_db`.`staff_roles` (id, role_name, role_description, role_level, dashboard_path) VALUES
(1, 'Director General', 'Executive leadership', 'executive', 'dashboards/director-general.php'),
(2, 'CEO', 'Chief Executive Officer', 'executive', 'dashboards/ceo.php'),
(3, 'Director Academics', 'Academic affairs oversight', 'director', 'dashboards/director-academics.php'),
(4, 'Director Finance', 'Financial oversight', 'director', 'dashboards/director-finance.php'),
(5, 'Director ICT', 'ICT department head', 'director', 'dashboards/director-ict.php'),
(6, 'School Principal', 'School administration head', 'director', 'dashboards/school-principal.php'),
(7, 'Deputy Principal', 'Deputy school administration', 'manager', 'dashboards/deputy-principal.php'),
(8, 'Academic Registrar', 'Student records management', 'manager', 'dashboards/academic-registrar.php'),
(9, 'HR Manager', 'Human resources management', 'manager', 'dashboards/hr-manager.php'),
(10, 'School Secretary', 'Administrative support', 'staff', 'dashboards/school-secretary.php'),
(11, 'School Librarian', 'Library management', 'staff', 'dashboards/school-librarian.php'),
(12, 'Head Nursing', 'Nursing department head', 'manager', 'dashboards/head-nursing.php'),
(13, 'Head Midwifery', 'Midwifery department head', 'manager', 'dashboards/head-midwifery.php'),
(14, 'Senior Lecturer', 'Senior teaching staff', 'staff', 'dashboards/senior-lecturers.php'),
(15, 'Lecturer', 'Teaching staff', 'staff', 'dashboards/lecturers.php'),
(16, 'Matron', 'Hostel management', 'staff', 'dashboards/matrons.php'),
(17, 'Warden', 'Hostel warden', 'staff', 'dashboards/wardens.php'),
(18, 'Sickbay', 'Health services', 'staff', 'dashboards/sickbay.php'),
(19, 'Driver', 'Transport services', 'staff', 'dashboards/drivers.php'),
(20, 'Security', 'Security services', 'staff', 'dashboards/security.php'),
(21, 'Storekeeper', 'Store management', 'staff', 'dashboards/storekeeper.php'),
(22, 'Guild President', 'Student governance', 'student', 'dashboards/guild-president.php'),
(23, 'Computer Lab Manager', 'Computer lab management', 'staff', 'dashboards/computer_lab.php'),
(24, 'School Bursar', 'Financial operations', 'manager', 'dashboards/school-bursar.php'),
(25, 'Store Keeper', 'Store operations', 'staff', 'dashboards/storekeeper.php'),
(26, 'Director Admissions', 'Admissions management', 'director', 'dashboards/director-admissions.php'),
(27, 'Bursar', 'Bursar operations', 'manager', 'dashboards/school-bursar.php'),
(28, 'Director Admissions & Requirements', 'Admissions and requirements', 'director', 'dashboards/director-admissions.php'),
(29, 'Head of Nursing', 'Nursing department', 'manager', 'dashboards/head-nursing.php'),
(30, 'Head of Midwifery', 'Midwifery department', 'manager', 'dashboards/head-midwifery.php'),
(31, 'Senior Lecturers', 'Senior teaching', 'staff', 'dashboards/senior-lecturers.php'),
(32, 'Lecturers', 'Teaching', 'staff', 'dashboards/lecturers.php'),
(33, 'Security Officer', 'Security', 'staff', 'dashboards/security.php'),
(34, 'Drivers', 'Transport', 'staff', 'dashboards/drivers.php'),
(35, 'Matrons', 'Hostel', 'staff', 'dashboards/matrons.php'),
(36, 'Wardens', 'Hostel warden', 'staff', 'dashboards/wardens.php'),
(37, 'Sickbay Nurse', 'Health services', 'staff', 'dashboards/sickbay.php'),
(38, 'System Administrator', 'System admin', 'admin', 'dashboards/director-general.php'),
(39, 'Computer Lab', 'Computer lab', 'staff', 'dashboards/computer_lab.php'),
(40, 'Skills Lab', 'Skills lab', 'staff', 'dashboards/skills-lab.php');

-- 3. Create staff table
CREATE TABLE IF NOT EXISTS `igangaschoolofl_staffs_db`.`staff` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `staff_id` VARCHAR(50) DEFAULT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `position` VARCHAR(255) DEFAULT NULL,
  `department` VARCHAR(255) DEFAULT NULL,
  `role_id` INT DEFAULT 0,
  `phone` VARCHAR(50) DEFAULT NULL,
  `profile_picture` VARCHAR(500) DEFAULT NULL,
  `status` VARCHAR(50) DEFAULT 'Active',
  `hire_date` DATE DEFAULT NULL,
  `login_attempts` INT DEFAULT 0,
  `locked_until` DATETIME DEFAULT NULL,
  `last_login` DATETIME DEFAULT NULL,
  `password_changed` TINYINT(1) DEFAULT 0,
  `is_first_login` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Insert/Update all 30 staff with bcrypt passwords
-- Run this regardless of existing data — it updates matching emails
INSERT INTO `igangaschoolofl_staffs_db`.`staff`
  (email, full_name, position, department, role_id, staff_id, password, status, hire_date, password_changed, is_first_login, login_attempts)
VALUES
('computer-lab@igangaschoolofnursingandmidwifery.ac.ug','Computer Lab Manager','Computer Lab Manager','ICT',0,'CLB-001','$2y$10$34lmyAZE5tSf7psT3x3rx.8KamnO5/vd8DStbS6P2mF/k.8VoX622','Active',CURDATE(),1,0,0),
('directorgeneral@igangaschoolofnursingandmidwifery.ac.ug','Director General','Director General','Executive',0,'DG-001','$2y$10$YTUATI07COmguelhbo6tVe6.fbRcQ92d0O6x9yhJ0gyzFf8cWA1ba','Active',CURDATE(),1,0,0),
('ceo@igangaschoolofnursingandmidwifery.ac.ug','Chief Executive Officer','CEO','Executive',0,'CEO-001','$2y$10$DHsvS2/YKAuYAyocmcmtN.bHO2leeh08WhUnnkj9Gx87XEa8YogwS','Active',CURDATE(),1,0,0),
('directoracademic@igangaschoolofnursingandmidwifery.ac.ug','Director Academics','Director Academics','Academic Affairs',0,'DA-001','$2y$10$w1mdSP/FFLTzx6s.5FHMTeV0ZofH.YqukPGyDt/zUzd05atPKzWAW','Active',CURDATE(),1,0,0),
('finance@igangaschoolofnursingandmidwifery.ac.ug','Director Finance','Director Finance','Finance',0,'DF-001','$2y$10$vUA/ix/FOXiuCqECzqJVGu9OmhIYCs2UZyJrqIyZ6RWR22nG/N8Oy','Active',CURDATE(),1,0,0),
('principal@igangaschoolofnursingandmidwifery.ac.ug','School Principal','School Principal','Administration',0,'PRIN-001','$2y$10$cTI0CQJSz0/tobhenQ/4uOpQ7ua3loE68WA5uk51bp8DJhhkHTDte','Active',CURDATE(),1,0,0),
('dep-principal@igangaschoolofnursingandmidwifery.ac.ug','Deputy Principal','Deputy Principal','Administration',0,'DP-001','$2y$10$DA6cBE1YXzoOFCIsHqma5.kqKMzr7dRzKNSbiBGzJaEbdnqF6xMnS','Active',CURDATE(),1,0,0),
('academicregistrar@igangaschoolofnursingandmidwifery.ac.ug','Academic Registrar','Academic Registrar','Academic Registrar',0,'AR-001','$2y$10$DIvNzaRKTsoE9IfPLqSK7OpfK7.N.JZwLHZfC4T69rDgZDloOZOmi','Active',CURDATE(),1,0,0),
('hr-manager@igangaschoolofnursingandmidwifery.ac.ug','HR Manager','HR Manager','Human Resources',0,'HR-001','$2y$10$xoH.tTCIJ4RG7BrLpLjtyuO1rMxkK3lKwFrWV.nZWzAu2t6u0rHmO','Active',CURDATE(),1,0,0),
('secretary@igangaschoolofnursingandmidwifery.ac.ug','School Secretary','School Secretary','Administration',0,'SEC-001','$2y$10$i5OjmEnZMVaTMDTSrTi2rOKGyc03p0LbpY5rwI5ppPrFtXUQm3K0u','Active',CURDATE(),1,0,0),
('library@igangaschoolofnursingandmidwifery.ac.ug','School Librarian','School Librarian','Library',0,'LIB-001','$2y$10$W3ZmG.lTXwdAj/DyJsrzTOc2.tO4WieXReTM5deqEqgSXTkbs/8ey','Active',CURDATE(),1,0,0),
('nursing-dep@igangaschoolofnursingandmidwifery.ac.ug','Head of Nursing','Head of Nursing','Nursing',0,'NUR-001','$2y$10$lnjc70.yDEy1EtW6VhX0SOICnvpoijYVLfPj9t6FpPAkb0runt7iC','Active',CURDATE(),1,0,0),
('midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug','Head of Midwifery','Head of Midwifery','Midwifery',0,'MID-001','$2y$10$UUntTMTHV9iIlk5jo0e/SO/znnmC1TEvI6oNv5LVrdclelNIcvWi2','Active',CURDATE(),1,0,0),
('senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug','Senior Lecturer','Senior Lecturer','Academic Affairs',0,'SL-001','$2y$10$9H8bqp/amdz6Bp8wyR/jSOygp7Vw6Gep/wt.IiuLhX/afTdGE9Ug6','Active',CURDATE(),1,0,0),
('lecturers@igangaschoolofnursingandmidwifery.ac.ug','Lecturer','Lecturer','Academic Affairs',0,'LEC-001','$2y$10$R.GNT4pxzR.B9bGFjcyI2.cn19Ikx9Wum0Fd/jbOU0kwNe7UXsm9i','Active',CURDATE(),1,0,0),
('matron@igangaschoolofnursingandmidwifery.ac.ug','Matron','Matron','Student Welfare',0,'MAT-001','$2y$10$k4PqE5O9nkKpj2LJju8H9unAJmG87IFaHZQTvKPs4BvjwufMdlQty','Active',CURDATE(),1,0,0),
('warden@igangaschoolofnursingandmidwifery.ac.ug','Warden','Warden','Student Welfare',0,'WAR-001','$2y$10$04Vq1qgqmdbc.d.Ex3f/O.ymV3b2nfB/VrupbqacNEPxqdhxTG3z6','Active',CURDATE(),1,0,0),
('sickbay@igangaschoolofnursingandmidwifery.ac.ug','Sickbay Nurse','Sickbay Nurse','Student Welfare',0,'SKB-001','$2y$10$cD.7sdD4C6AIoBggh1I9YurWqsGgR4o4qehTbKodgtxMf3bfG/MIi','Active',CURDATE(),1,0,0),
('drivers@igangaschoolofnursingandmidwifery.ac.ug','Driver','Driver','Transport',0,'DRV-001','$2y$10$WadSJ9dLL1ky.WC8Rbz7m.1o2teLNEZrR5dSAnd6CevSycTYDu1au','Active',CURDATE(),1,0,0),
('security@igangaschoolofnursingandmidwifery.ac.ug','Security Officer','Security Officer','Security',0,'SEC-001','$2y$10$CaQ9ZbcrsuMNvezSA5D2r.QX0Z5Fh/ROHTxfJ7Jh3ecMlUHPvjbOq','Active',CURDATE(),1,0,0),
('store@igangaschoolofnursingandmidwifery.ac.ug','Storekeeper','Storekeeper','Store',0,'STO-001','$2y$10$.7SxLad/gBgL5VZ7WhPu1eIed1A78BQApXDj4/RT.qbogcpHLKlvm','Active',CURDATE(),1,0,0),
('guildpresident@igangaschoolofnursingandmidwifery.ac.ug','Guild President','Guild President','Student Government',0,'G-001','$2y$10$C29qjseTv4UtRjgK.Aae9uNaS/.2cO1I6sJ2VZCCseCiSBenckdT2','Active',CURDATE(),1,0,0),
('admissions@igangaschoolofnursingandmidwifery.ac.ug','Director Admissions','Director Admissions','Admissions',0,'ADM-001','$2y$10$FOqTvhC5o/p6DOWT7op1ReOW73Gb879bboEPOGD24H2134EUBsx9C','Active',CURDATE(),1,0,0),
('dannybict@igangaschoolofnursingandmidwifery.ac.ug','Director ICT','Director ICT','ICT',0,'ICT-001','$2y$10$ILpun/AqJ.Y.1/4Dnxo5ge78duecpeok7DBqtQEEJG8UL6jBV85Qi','Active',CURDATE(),1,0,0),
('skills-lab@igangaschoolofnursingandmidwifery.ac.ug','Skills Lab Technician','Skills Lab Technician','Skills Laboratory',0,'SKL-001','$2y$10$jDAtAnewZKRDKATnOC.4juFNRxyuEhv5P1t7lk9usPKDm8agozMi6','Active',CURDATE(),1,0,0),
('bursar@igangaschoolofnursingandmidwifery.ac.ug','School Bursar','School Bursar','Finance',0,'BUR-001','$2y$10$kLOtw9FI0xxJ3zJvX/ZC/e9cgFMJN6BE4azUPWAEGwEdi/5fP5kl6','Active',CURDATE(),1,0,0),
('admissions-req@igangaschoolofnursingandmidwifery.ac.ug','Director Admissions & Requirements','Director Admissions','Admissions',0,'ADM-002','$2y$10$xKQvqhioVmVZ1.Ji8tLXSOAqDh6QlsQX26tv3.dEPtC1PprokOlv6','Active',CURDATE(),1,0,0),
('directorict@igangaschoolofnursingandmidwifery.ac.ug','Director ICT (Alt)','Director ICT','ICT',0,'ICT-002','$2y$10$wEqdjzAwAqhcwAlj83521uJsVDJu9VKdQBd712xfFkNHabr5.d.nO','Active',CURDATE(),1,0,0),
('computerlab@igangaschoolofnursingandmidwifery.ac.ug','Computer Lab Manager','Computer Lab Manager','ICT',0,'CLB-002','$2y$10$p7lVgvLE9WfLd/RnTGH10.zBcuKRXK7uL1i2lKml26FzP0n.zEbi.','Active',CURDATE(),1,0,0),
('skillslab@igangaschoolofnursingandmidwifery.ac.ug','Skills Lab Manager','Skills Lab Manager','Skills Laboratory',0,'SKL-002','$2y$10$lANcJV/Rpn0uonJ6PhAJUu5Q5/hv093WML.UX/k.xuOdhHTYRdU7m','Active',CURDATE(),1,0,0)
ON DUPLICATE KEY UPDATE
  password=VALUES(password), password_changed=1, is_first_login=0,
  login_attempts=0, locked_until=NULL, full_name=VALUES(full_name),
  position=VALUES(position), department=VALUES(department), staff_id=VALUES(staff_id);

-- 5. Link staff to roles (set role_id based on staff_roles.role_name matching position)
-- You can run this section even if roles already linked — it only updates where role_id=0
UPDATE `igangaschoolofl_staffs_db`.`staff` s
  JOIN `igangaschoolofl_staffs_db`.`staff_roles` sr ON sr.role_name = 'Director General'
  SET s.role_id = sr.id WHERE s.email = 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug' AND s.role_id = 0;

UPDATE `igangaschoolofl_staffs_db`.`staff` s
  JOIN `igangaschoolofl_staffs_db`.`staff_roles` sr ON sr.role_name = 'CEO'
  SET s.role_id = sr.id WHERE s.email = 'ceo@igangaschoolofnursingandmidwifery.ac.ug' AND s.role_id = 0;

UPDATE `igangaschoolofl_staffs_db`.`staff` s
  JOIN `igangaschoolofl_staffs_db`.`staff_roles` sr ON sr.role_name = 'Director Academics'
  SET s.role_id = sr.id WHERE s.email = 'directoracademic@igangaschoolofnursingandmidwifery.ac.ug' AND s.role_id = 0;

UPDATE `igangaschoolofl_staffs_db`.`staff` s
  JOIN `igangaschoolofl_staffs_db`.`staff_roles` sr ON sr.role_name = 'Director Finance'
  SET s.role_id = sr.id WHERE s.email = 'finance@igangaschoolofnursingandmidwifery.ac.ug' AND s.role_id = 0;

UPDATE `igangaschoolofl_staffs_db`.`staff` s
  JOIN `igangaschoolofl_staffs_db`.`staff_roles` sr ON sr.role_name = 'School Principal'
  SET s.role_id = sr.id WHERE s.email = 'principal@igangaschoolofnursingandmidwifery.ac.ug' AND s.role_id = 0;

UPDATE `igangaschoolofl_staffs_db`.`staff` s
  JOIN `igangaschoolofl_staffs_db`.`staff_roles` sr ON sr.role_name = 'Deputy Principal'
  SET s.role_id = sr.id WHERE s.email = 'dep-principal@igangaschoolofnursingandmidwifery.ac.ug' AND s.role_id = 0;

UPDATE `igangaschoolofl_staffs_db`.`staff` s
  JOIN `igangaschoolofl_staffs_db`.`staff_roles` sr ON sr.role_name = 'Academic Registrar'
  SET s.role_id = sr.id WHERE s.email = 'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug' AND s.role_id = 0;

UPDATE `igangaschoolofl_staffs_db`.`staff` s
  JOIN `igangaschoolofl_staffs_db`.`staff_roles` sr ON sr.role_name = 'HR Manager'
  SET s.role_id = sr.id WHERE s.email = 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug' AND s.role_id = 0;

UPDATE `igangaschoolofl_staffs_db`.`staff` s
  JOIN `igangaschoolofl_staffs_db`.`staff_roles` sr ON sr.role_name = 'School Secretary'
  SET s.role_id = sr.id WHERE s.email = 'secretary@igangaschoolofnursingandmidwifery.ac.ug' AND s.role_id = 0;

UPDATE `igangaschoolofl_staffs_db`.`staff` s
  JOIN `igangaschoolofl_staffs_db`.`staff_roles` sr ON sr.role_name = 'School Librarian'
  SET s.role_id = sr.id WHERE s.email = 'library@igangaschoolofnursingandmidwifery.ac.ug' AND s.role_id = 0;

UPDATE `igangaschoolofl_staffs_db`.`staff` s
  JOIN `igangaschoolofl_staffs_db`.`staff_roles` sr ON sr.role_name = 'Head of Nursing'
  SET s.role_id = sr.id WHERE s.email = 'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug' AND s.role_id = 0;

UPDATE `igangaschoolofl_staffs_db`.`staff` s
  JOIN `igangaschoolofl_staffs_db`.`staff_roles` sr ON sr.role_name = 'Head of Midwifery'
  SET s.role_id = sr.id WHERE s.email = 'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug' AND s.role_id = 0;

UPDATE `igangaschoolofl_staffs_db`.`staff` s
  JOIN `igangaschoolofl_staffs_db`.`staff_roles` sr ON sr.role_name = 'Senior Lecturer'
  SET s.role_id = sr.id WHERE s.email = 'senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug' AND s.role_id = 0;

UPDATE `igangaschoolofl_staffs_db`.`staff` s
  JOIN `igangaschoolofl_staffs_db`.`staff_roles` sr ON sr.role_name = 'Lecturer'
  SET s.role_id = sr.id WHERE s.email = 'lecturers@igangaschoolofnursingandmidwifery.ac.ug' AND s.role_id = 0;

UPDATE `igangaschoolofl_staffs_db`.`staff` s
  JOIN `igangaschoolofl_staffs_db`.`staff_roles` sr ON sr.role_name = 'Matron'
  SET s.role_id = sr.id WHERE s.email = 'matron@igangaschoolofnursingandmidwifery.ac.ug' AND s.role_id = 0;

UPDATE `igangaschoolofl_staffs_db`.`staff` s
  JOIN `igangaschoolofl_staffs_db`.`staff_roles` sr ON sr.role_name = 'Warden'
  SET s.role_id = sr.id WHERE s.email = 'warden@igangaschoolofnursingandmidwifery.ac.ug' AND s.role_id = 0;

UPDATE `igangaschoolofl_staffs_db`.`staff` s
  JOIN `igangaschoolofl_staffs_db`.`staff_roles` sr ON sr.role_name = 'Sickbay Nurse'
  SET s.role_id = sr.id WHERE s.email = 'sickbay@igangaschoolofnursingandmidwifery.ac.ug' AND s.role_id = 0;

UPDATE `igangaschoolofl_staffs_db`.`staff` s
  JOIN `igangaschoolofl_staffs_db`.`staff_roles` sr ON sr.role_name = 'Driver'
  SET s.role_id = sr.id WHERE s.email = 'drivers@igangaschoolofnursingandmidwifery.ac.ug' AND s.role_id = 0;

UPDATE `igangaschoolofl_staffs_db`.`staff` s
  JOIN `igangaschoolofl_staffs_db`.`staff_roles` sr ON sr.role_name = 'Security Officer'
  SET s.role_id = sr.id WHERE s.email = 'security@igangaschoolofnursingandmidwifery.ac.ug' AND s.role_id = 0;

UPDATE `igangaschoolofl_staffs_db`.`staff` s
  JOIN `igangaschoolofl_staffs_db`.`staff_roles` sr ON sr.role_name = 'Storekeeper'
  SET s.role_id = sr.id WHERE s.email = 'store@igangaschoolofnursingandmidwifery.ac.ug' AND s.role_id = 0;

UPDATE `igangaschoolofl_staffs_db`.`staff` s
  JOIN `igangaschoolofl_staffs_db`.`staff_roles` sr ON sr.role_name = 'Guild President'
  SET s.role_id = sr.id WHERE s.email = 'guildpresident@igangaschoolofnursingandmidwifery.ac.ug' AND s.role_id = 0;

UPDATE `igangaschoolofl_staffs_db`.`staff` s
  JOIN `igangaschoolofl_staffs_db`.`staff_roles` sr ON sr.role_name = 'Director Admissions'
  SET s.role_id = sr.id WHERE s.email = 'admissions@igangaschoolofnursingandmidwifery.ac.ug' AND s.role_id = 0;

UPDATE `igangaschoolofl_staffs_db`.`staff` s
  JOIN `igangaschoolofl_staffs_db`.`staff_roles` sr ON sr.role_name = 'Director ICT'
  SET s.role_id = sr.id WHERE s.email = 'dannybict@igangaschoolofnursingandmidwifery.ac.ug' AND s.role_id = 0;

UPDATE `igangaschoolofl_staffs_db`.`staff` s
  JOIN `igangaschoolofl_staffs_db`.`staff_roles` sr ON sr.role_name = 'Skills Lab'
  SET s.role_id = sr.id WHERE s.email = 'skills-lab@igangaschoolofnursingandmidwifery.ac.ug' AND s.role_id = 0;

UPDATE `igangaschoolofl_staffs_db`.`staff` s
  JOIN `igangaschoolofl_staffs_db`.`staff_roles` sr ON sr.role_name = 'School Bursar'
  SET s.role_id = sr.id WHERE s.email = 'bursar@igangaschoolofnursingandmidwifery.ac.ug' AND s.role_id = 0;

UPDATE `igangaschoolofl_staffs_db`.`staff` s
  JOIN `igangaschoolofl_staffs_db`.`staff_roles` sr ON sr.role_name = 'Director Admissions'
  SET s.role_id = sr.id WHERE s.email = 'admissions-req@igangaschoolofnursingandmidwifery.ac.ug' AND s.role_id = 0;

UPDATE `igangaschoolofl_staffs_db`.`staff` s
  JOIN `igangaschoolofl_staffs_db`.`staff_roles` sr ON sr.role_name = 'Director ICT'
  SET s.role_id = sr.id WHERE s.email = 'directorict@igangaschoolofnursingandmidwifery.ac.ug' AND s.role_id = 0;

UPDATE `igangaschoolofl_staffs_db`.`staff` s
  JOIN `igangaschoolofl_staffs_db`.`staff_roles` sr ON sr.role_name = 'Computer Lab Manager'
  SET s.role_id = sr.id WHERE s.email = 'computerlab@igangaschoolofnursingandmidwifery.ac.ug' AND s.role_id = 0;

UPDATE `igangaschoolofl_staffs_db`.`staff` s
  JOIN `igangaschoolofl_staffs_db`.`staff_roles` sr ON sr.role_name = 'Skills Lab'
  SET s.role_id = sr.id WHERE s.email = 'skillslab@igangaschoolofnursingandmidwifery.ac.ug' AND s.role_id = 0;

-- 6. Force-link all remaining (in case role_id still 0)
UPDATE `igangaschoolofl_staffs_db`.`staff` s
  JOIN `igangaschoolofl_staffs_db`.`staff_roles` sr ON sr.role_name = s.position
  SET s.role_id = sr.id WHERE s.role_id = 0;

-- 7. Unlock all and reset login attempts
UPDATE `igangaschoolofl_staffs_db`.`staff` SET login_attempts = 0, locked_until = NULL;

-- 8. Verify
SELECT COUNT(*) AS total_staff FROM `igangaschoolofl_staffs_db`.`staff`;
SELECT COUNT(*) AS staff_with_passwords FROM `igangaschoolofl_staffs_db`.`staff` WHERE password_changed = 1;
SELECT COUNT(*) AS staff_with_roles FROM `igangaschoolofl_staffs_db`.`staff` WHERE role_id > 0;
SELECT COUNT(*) AS total_roles FROM `igangaschoolofl_staffs_db`.`staff_roles`;
SELECT email, full_name, sr.role_name, sr.dashboard_path
  FROM `igangaschoolofl_staffs_db`.`staff` s
  JOIN `igangaschoolofl_staffs_db`.`staff_roles` sr ON s.role_id = sr.id
  ORDER BY sr.id;
