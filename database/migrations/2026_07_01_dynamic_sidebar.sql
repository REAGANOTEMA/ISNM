-- ISNM Dynamic Sidebar Migration
-- Tables: menu_groups, menu_items, menu_roles, menu_role_groups
-- Database: igangaschoolofl_staffs_db

-- Menu groups (parent sections in sidebar)
CREATE TABLE IF NOT EXISTS `menu_groups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `group_name` varchar(100) NOT NULL COMMENT 'Unique identifier like executive, finance, library',
  `display_name` varchar(200) NOT NULL COMMENT 'Shown in sidebar',
  `icon` varchar(100) DEFAULT 'fas fa-circle',
  `sort_order` int DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_name` (`group_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Menu items (individual links)
CREATE TABLE IF NOT EXISTS `menu_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `group_id` int NOT NULL,
  `title` varchar(200) NOT NULL,
  `route` varchar(500) NOT NULL COMMENT 'File path or URL',
  `icon` varchar(100) DEFAULT 'fas fa-link',
  `sort_order` int DEFAULT 0,
  `target` enum('self','blank') DEFAULT 'self',
  `requires_module` varchar(100) DEFAULT NULL COMMENT 'Module check like module_config key',
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `idx_mi_group` (`group_id`),
  KEY `idx_mi_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Roles
CREATE TABLE IF NOT EXISTS `menu_roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role_key` varchar(100) NOT NULL COMMENT 'Lowercase role name like director general, registrar, lecturer',
  `display_name` varchar(200) NOT NULL,
  `dashboard_file` varchar(200) DEFAULT NULL COMMENT 'Primary dashboard file',
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_key` (`role_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Role-group permissions (which groups each role sees)
CREATE TABLE IF NOT EXISTS `menu_role_groups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `role_id` int NOT NULL,
  `group_id` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_group` (`role_id`,`group_id`),
  KEY `idx_mrg_group` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed data: all menu groups
INSERT IGNORE INTO `menu_groups` (group_name, display_name, icon, sort_order) VALUES
('executive', 'Executive', 'fas fa-crown', 1),
('academic_mgmt', 'Academic Management', 'fas fa-graduation-cap', 2),
('academic_registrar', 'Academic Registrar', 'fas fa-clipboard-list', 3),
('overview', 'Overview', 'fas fa-chart-pie', 4),
('student_fees', 'Student Fees', 'fas fa-money-bill-wave', 5),
('payments', 'Payments', 'fas fa-credit-card', 6),
('payroll', 'Payroll', 'fas fa-wallet', 7),
('budgets', 'Budgets & Expenditure', 'fas fa-chart-line', 8),
('accounts', 'Accounts', 'fas fa-book', 9),
('requisitions', 'Requisitions', 'fas fa-shopping-cart', 10),
('communications', 'Communications', 'fas fa-envelope', 11),
('reports', 'Reports', 'fas fa-file-alt', 12),
('tools', 'Tools', 'fas fa-tools', 13),
('admissions', 'Admissions', 'fas fa-door-open', 14),
('human_resources', 'Human Resources', 'fas fa-users', 15),
('ict', 'ICT Department', 'fas fa-laptop', 16),
('security', 'Security & Transport', 'fas fa-shield-alt', 17),
('library', 'Library', 'fas fa-book-open', 18),
('nursing', 'Nursing Department', 'fas fa-user-md', 19),
('midwifery', 'Midwifery Department', 'fas fa-baby', 20),
('health_center', 'Health Center', 'fas fa-heartbeat', 21),
('hostel', 'Hostel Management', 'fas fa-bed', 22),
('store', 'Store & Assets', 'fas fa-boxes', 23),
('transport', 'Transport', 'fas fa-truck', 24),
('skills_lab', 'Skills Laboratory', 'fas fa-flask', 25),
('computer_lab', 'Computer Lab', 'fas fa-desktop', 26),
('guild', 'Student Government', 'fas fa-handshake', 27),
('secretary', 'Secretary', 'fas fa-archive', 28),
('student_welfare', 'Student Welfare', 'fas fa-heart', 29);

-- Seed all roles
INSERT IGNORE INTO `menu_roles` (role_key, display_name, dashboard_file) VALUES
('director general', 'Director General', 'director-general.php'),
('ceo', 'Chief Executive Officer', 'ceo.php'),
('director academics', 'Director Academics', 'director-academics.php'),
('director finance', 'Director Finance', 'director-finance.php'),
('director ict', 'Director ICT', 'director-ict.php'),
('director admissions', 'Director Admissions', 'director-admissions.php'),
('school principal', 'School Principal', 'school-principal.php'),
('deputy principal', 'Deputy Principal', 'deputy-principal.php'),
('academic registrar', 'Academic Registrar', 'academic-registrar.php'),
('school bursar', 'School Bursar', 'school-bursar.php'),
('school secretary', 'School Secretary', 'school-secretary.php'),
('hr manager', 'HR Manager', 'hr-manager.php'),
('school librarian', 'School Librarian', 'school-librarian.php'),
('head of nursing', 'Head of Nursing', 'head-nursing.php'),
('head of midwifery', 'Head of Midwifery', 'head-midwifery.php'),
('senior lecturer', 'Senior Lecturer', 'senior-lecturers.php'),
('lecturer', 'Lecturer', 'lecturers.php'),
('security officer', 'Security Officer', 'security.php'),
('storekeeper', 'Storekeeper', 'storekeeper.php'),
('driver', 'Driver', 'drivers.php'),
('matron', 'Matron', 'matrons.php'),
('warden', 'Warden', 'wardens.php'),
('guild president', 'Guild President', 'guild-president.php'),
('sickbay nurse', 'Sickbay Nurse', 'sickbay.php'),
('computer lab', 'Computer Lab Manager', 'computer_lab.php'),
('skills lab', 'Skills Lab Technician', 'skills-lab.php'),
('system administrator', 'System Administrator', 'system-admin.php');

-- Role-group permissions

-- Director General: sees everything
INSERT IGNORE INTO `menu_role_groups` (role_id, group_id)
SELECT r.id, g.id FROM menu_roles r CROSS JOIN menu_groups g WHERE r.role_key = 'director general';

-- CEO: sees everything
INSERT IGNORE INTO `menu_role_groups` (role_id, group_id)
SELECT r.id, g.id FROM menu_roles r CROSS JOIN menu_groups g WHERE r.role_key = 'ceo';

-- System Administrator: sees executive, ict, communications, reports, tools
INSERT IGNORE INTO `menu_role_groups` (role_id, group_id)
SELECT r.id, g.id FROM menu_roles r CROSS JOIN menu_groups g
WHERE r.role_key = 'system administrator'
  AND g.group_name IN ('executive','ict','communications','reports','tools');

-- Director Academics
INSERT IGNORE INTO `menu_role_groups` (role_id, group_id)
SELECT r.id, g.id FROM menu_roles r CROSS JOIN menu_groups g
WHERE r.role_key = 'director academics'
  AND g.group_name IN ('executive','academic_mgmt','academic_registrar','admissions','reports','communications');

-- Director Finance
INSERT IGNORE INTO `menu_role_groups` (role_id, group_id)
SELECT r.id, g.id FROM menu_roles r CROSS JOIN menu_groups g
WHERE r.role_key = 'director finance'
  AND g.group_name IN ('executive','overview','student_fees','payments','payroll','budgets','accounts','requisitions','communications','reports');

-- Director ICT
INSERT IGNORE INTO `menu_role_groups` (role_id, group_id)
SELECT r.id, g.id FROM menu_roles r CROSS JOIN menu_groups g
WHERE r.role_key = 'director ict'
  AND g.group_name IN ('executive','ict','computer_lab','communications','reports','tools');

-- Director Admissions
INSERT IGNORE INTO `menu_role_groups` (role_id, group_id)
SELECT r.id, g.id FROM menu_roles r CROSS JOIN menu_groups g
WHERE r.role_key = 'director admissions'
  AND g.group_name IN ('executive','admissions','communications','reports');

-- School Principal
INSERT IGNORE INTO `menu_role_groups` (role_id, group_id)
SELECT r.id, g.id FROM menu_roles r CROSS JOIN menu_groups g
WHERE r.role_key = 'school principal'
  AND g.group_name IN ('executive','academic_mgmt','academic_registrar','admissions','reports','communications','human_resources','nursing','midwifery','library','store','transport','security','student_welfare','hostel');

-- Deputy Principal
INSERT IGNORE INTO `menu_role_groups` (role_id, group_id)
SELECT r.id, g.id FROM menu_roles r CROSS JOIN menu_groups g
WHERE r.role_key = 'deputy principal'
  AND g.group_name IN ('academic_mgmt','academic_registrar','reports','communications','student_welfare','hostel');

-- Academic Registrar
INSERT IGNORE INTO `menu_role_groups` (role_id, group_id)
SELECT r.id, g.id FROM menu_roles r CROSS JOIN menu_groups g
WHERE r.role_key = 'academic registrar'
  AND g.group_name IN ('academic_registrar','admissions','academic_mgmt','reports','communications');

-- School Bursar
INSERT IGNORE INTO `menu_role_groups` (role_id, group_id)
SELECT r.id, g.id FROM menu_roles r CROSS JOIN menu_groups g
WHERE r.role_key = 'school bursar'
  AND g.group_name IN ('overview','student_fees','payments','payroll','budgets','accounts','requisitions','communications','reports','tools');

-- School Secretary
INSERT IGNORE INTO `menu_role_groups` (role_id, group_id)
SELECT r.id, g.id FROM menu_roles r CROSS JOIN menu_groups g
WHERE r.role_key = 'school secretary'
  AND g.group_name IN ('secretary','communications','reports','admissions','student_welfare');

-- HR Manager
INSERT IGNORE INTO `menu_role_groups` (role_id, group_id)
SELECT r.id, g.id FROM menu_roles r CROSS JOIN menu_groups g
WHERE r.role_key = 'hr manager'
  AND g.group_name IN ('human_resources','reports','communications','executive');

-- School Librarian
INSERT IGNORE INTO `menu_role_groups` (role_id, group_id)
SELECT r.id, g.id FROM menu_roles r CROSS JOIN menu_groups g
WHERE r.role_key = 'school librarian'
  AND g.group_name IN ('library','reports');

-- Head of Nursing
INSERT IGNORE INTO `menu_role_groups` (role_id, group_id)
SELECT r.id, g.id FROM menu_roles r CROSS JOIN menu_groups g
WHERE r.role_key = 'head of nursing'
  AND g.group_name IN ('nursing','skills_lab','reports','communications','human_resources');

-- Head of Midwifery
INSERT IGNORE INTO `menu_role_groups` (role_id, group_id)
SELECT r.id, g.id FROM menu_roles r CROSS JOIN menu_groups g
WHERE r.role_key = 'head of midwifery'
  AND g.group_name IN ('midwifery','skills_lab','reports','communications','human_resources');

-- Senior Lecturer
INSERT IGNORE INTO `menu_role_groups` (role_id, group_id)
SELECT r.id, g.id FROM menu_roles r CROSS JOIN menu_groups g
WHERE r.role_key = 'senior lecturer'
  AND g.group_name IN ('academic_mgmt','reports');

-- Lecturer
INSERT IGNORE INTO `menu_role_groups` (role_id, group_id)
SELECT r.id, g.id FROM menu_roles r CROSS JOIN menu_groups g
WHERE r.role_key = 'lecturer'
  AND g.group_name IN ('academic_mgmt','reports');

-- Security Officer
INSERT IGNORE INTO `menu_role_groups` (role_id, group_id)
SELECT r.id, g.id FROM menu_roles r CROSS JOIN menu_groups g
WHERE r.role_key = 'security officer'
  AND g.group_name IN ('security');

-- Storekeeper
INSERT IGNORE INTO `menu_role_groups` (role_id, group_id)
SELECT r.id, g.id FROM menu_roles r CROSS JOIN menu_groups g
WHERE r.role_key = 'storekeeper'
  AND g.group_name IN ('store');

-- Driver
INSERT IGNORE INTO `menu_role_groups` (role_id, group_id)
SELECT r.id, g.id FROM menu_roles r CROSS JOIN menu_groups g
WHERE r.role_key = 'driver'
  AND g.group_name IN ('transport');

-- Matron
INSERT IGNORE INTO `menu_role_groups` (role_id, group_id)
SELECT r.id, g.id FROM menu_roles r CROSS JOIN menu_groups g
WHERE r.role_key = 'matron'
  AND g.group_name IN ('hostel','student_welfare','health_center');

-- Warden
INSERT IGNORE INTO `menu_role_groups` (role_id, group_id)
SELECT r.id, g.id FROM menu_roles r CROSS JOIN menu_groups g
WHERE r.role_key = 'warden'
  AND g.group_name IN ('hostel','student_welfare');

-- Guild President
INSERT IGNORE INTO `menu_role_groups` (role_id, group_id)
SELECT r.id, g.id FROM menu_roles r CROSS JOIN menu_groups g
WHERE r.role_key = 'guild president'
  AND g.group_name IN ('guild','communications','student_welfare');

-- Sickbay Nurse
INSERT IGNORE INTO `menu_role_groups` (role_id, group_id)
SELECT r.id, g.id FROM menu_roles r CROSS JOIN menu_groups g
WHERE r.role_key = 'sickbay nurse'
  AND g.group_name IN ('health_center');

-- Computer Lab Manager
INSERT IGNORE INTO `menu_role_groups` (role_id, group_id)
SELECT r.id, g.id FROM menu_roles r CROSS JOIN menu_groups g
WHERE r.role_key = 'computer lab'
  AND g.group_name IN ('computer_lab');

-- Skills Lab Technician
INSERT IGNORE INTO `menu_role_groups` (role_id, group_id)
SELECT r.id, g.id FROM menu_roles r CROSS JOIN menu_groups g
WHERE r.role_key = 'skills lab'
  AND g.group_name IN ('skills_lab');

-- Ensure store_requests has proper AUTO_INCREMENT
ALTER TABLE `store_requests` MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;
