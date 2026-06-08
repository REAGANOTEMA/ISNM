-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 08, 2026 at 04:49 PM
-- Server version: 8.0.45
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `igangaschoolofl_ict`
--

-- --------------------------------------------------------

--
-- Table structure for table `it_support_tickets`
--

CREATE TABLE `it_support_tickets` (
  `id` int NOT NULL,
  `ticket_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `requester_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `requester_type` enum('staff','student') COLLATE utf8mb4_unicode_ci DEFAULT 'staff',
  `requester_email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `issue_type` enum('hardware','software','network','login','other') COLLATE utf8mb4_unicode_ci DEFAULT 'other',
  `priority` enum('low','medium','high','critical') COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('open','in_progress','resolved','closed') COLLATE utf8mb4_unicode_ci DEFAULT 'open',
  `assigned_to` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_bookings`
--

CREATE TABLE `lab_bookings` (
  `id` int NOT NULL,
  `booking_reference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `instructor_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `booking_date` date NOT NULL,
  `time_slot` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `number_of_students` int DEFAULT '0',
  `status` enum('pending','confirmed','cancelled','completed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_computers`
--

CREATE TABLE `lab_computers` (
  `id` int NOT NULL,
  `computer_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `computer_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('online','offline','maintenance','deleted') COLLATE utf8mb4_unicode_ci DEFAULT 'online',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mac_address` varchar(17) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `specifications` text COLLATE utf8mb4_unicode_ci,
  `os_installed` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_maintenance` date DEFAULT NULL,
  `next_maintenance` date DEFAULT NULL,
  `issues_reported` text COLLATE utf8mb4_unicode_ci,
  `assigned_to` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lab_computers`
--

INSERT INTO `lab_computers` (`id`, `computer_id`, `computer_name`, `location`, `status`, `ip_address`, `mac_address`, `specifications`, `os_installed`, `last_maintenance`, `next_maintenance`, `issues_reported`, `assigned_to`, `purchase_date`, `warranty_expiry`, `created_at`, `updated_at`) VALUES
(1, 'LAB-PC-001', 'Workstation 1', 'Main Lab', 'online', '192.168.1.101', NULL, NULL, 'Windows 11', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-08 06:49:39', '2026-06-08 06:49:39'),
(2, 'LAB-PC-002', 'Workstation 2', 'Main Lab', 'online', '192.168.1.102', NULL, NULL, 'Windows 11', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-08 06:49:39', '2026-06-08 06:49:39'),
(3, 'LAB-PC-003', 'Workstation 3', 'Main Lab', 'offline', '192.168.1.103', NULL, NULL, 'Windows 11', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-08 06:49:39', '2026-06-08 06:49:39'),
(4, 'LAB-PC-004', 'Workstation 4', 'Main Lab', 'online', '192.168.1.104', NULL, NULL, 'Windows 11', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-08 06:49:39', '2026-06-08 06:49:39'),
(5, 'LAB-PC-005', 'Workstation 5', 'Main Lab', 'maintenance', '192.168.1.105', NULL, NULL, 'Windows 11', NULL, NULL, NULL, NULL, NULL, NULL, '2026-06-08 06:49:39', '2026-06-08 06:49:39');

-- --------------------------------------------------------

--
-- Table structure for table `lab_usage_stats`
--

CREATE TABLE `lab_usage_stats` (
  `id` int NOT NULL,
  `lab_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `total_sessions` int DEFAULT '0',
  `total_users` int DEFAULT '0',
  `peak_concurrent_users` int DEFAULT '0',
  `average_session_duration` int DEFAULT '0',
  `computers_used` int DEFAULT '0',
  `computers_available` int DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lab_usage_stats`
--

INSERT INTO `lab_usage_stats` (`id`, `lab_name`, `date`, `total_sessions`, `total_users`, `peak_concurrent_users`, `average_session_duration`, `computers_used`, `computers_available`, `notes`, `created_at`) VALUES
(1, 'Lab A', '2024-06-05', 8, 45, 25, 90, 22, 25, NULL, '2026-06-08 05:07:40'),
(2, 'Lab B', '2024-06-05', 6, 35, 20, 85, 18, 20, NULL, '2026-06-08 05:07:40'),
(3, 'Lab A', '2024-06-06', 10, 55, 28, 95, 24, 25, NULL, '2026-06-08 05:07:40'),
(4, 'Lab B', '2024-06-06', 7, 40, 22, 80, 19, 20, NULL, '2026-06-08 05:07:40');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_logs`
--

CREATE TABLE `maintenance_logs` (
  `id` int NOT NULL,
  `computer_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `maintenance_type` enum('routine','repair','upgrade','cleaning') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `performed_by` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cost` decimal(10,2) DEFAULT '0.00',
  `parts_replaced` text COLLATE utf8mb4_unicode_ci,
  `status` enum('scheduled','in_progress','completed','cancelled') COLLATE utf8mb4_unicode_ci DEFAULT 'scheduled',
  `scheduled_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `maintenance_logs`
--

INSERT INTO `maintenance_logs` (`id`, `computer_id`, `maintenance_type`, `description`, `performed_by`, `cost`, `parts_replaced`, `status`, `scheduled_date`, `completed_date`, `created_at`, `updated_at`) VALUES
(1, 'LAB-A-003', 'repair', 'Power supply unit replacement required', 'IT Technician - James', 150.00, NULL, 'scheduled', '2024-06-12', NULL, '2026-06-08 05:07:40', '2026-06-08 05:07:40'),
(2, 'LAB-B-002', 'routine', 'Operating system reinstallation and updates', 'IT Technician - Sarah', 0.00, NULL, 'in_progress', '2024-06-10', NULL, '2026-06-08 05:07:40', '2026-06-08 05:07:40');

-- --------------------------------------------------------

--
-- Table structure for table `network_devices`
--

CREATE TABLE `network_devices` (
  `id` int NOT NULL,
  `device_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `device_type` enum('router','switch','access_point','firewall','server','other') COLLATE utf8mb4_unicode_ci DEFAULT 'other',
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mac_address` varchar(17) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('online','offline','maintenance') COLLATE utf8mb4_unicode_ci DEFAULT 'online',
  `firmware_version` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_checked` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `network_devices`
--

INSERT INTO `network_devices` (`id`, `device_name`, `device_type`, `ip_address`, `mac_address`, `location`, `status`, `firmware_version`, `last_checked`, `created_at`, `updated_at`) VALUES
(1, 'Main Router', 'router', '192.168.1.1', 'AA:BB:CC:DD:EE:01', 'Server Room', 'online', NULL, NULL, '2026-06-08 06:49:39', '2026-06-08 06:49:39'),
(2, 'Lab Switch', 'switch', '192.168.1.2', 'AA:BB:CC:DD:EE:02', 'Main Lab', 'online', NULL, NULL, '2026-06-08 06:49:39', '2026-06-08 06:49:39'),
(3, 'WiFi Access Point 1', 'access_point', '192.168.1.10', 'AA:BB:CC:DD:EE:10', 'Main Lab', 'online', NULL, NULL, '2026-06-08 06:49:39', '2026-06-08 06:49:39');

-- --------------------------------------------------------

--
-- Table structure for table `software_inventory`
--

CREATE TABLE `software_inventory` (
  `id` int NOT NULL,
  `software_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_type` enum('free','open_source','commercial','educational') COLLATE utf8mb4_unicode_ci DEFAULT 'educational',
  `installed_on` date DEFAULT NULL,
  `update_available` tinyint(1) DEFAULT '0',
  `last_updated` date DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `software_inventory`
--

INSERT INTO `software_inventory` (`id`, `software_name`, `version`, `license_type`, `installed_on`, `update_available`, `last_updated`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Microsoft Office 365', '2024', 'educational', '2024-01-15', 0, NULL, NULL, '2026-06-08 06:49:39', '2026-06-08 06:49:39'),
(2, 'Visual Studio Code', '1.85', 'free', '2024-02-01', 0, NULL, NULL, '2026-06-08 06:49:39', '2026-06-08 06:49:39'),
(3, 'Adobe Creative Suite', '2024', 'educational', '2024-01-20', 0, NULL, NULL, '2026-06-08 06:49:39', '2026-06-08 06:49:39'),
(4, 'Antivirus Pro', '12.5', 'commercial', '2024-03-01', 0, NULL, NULL, '2026-06-08 06:49:39', '2026-06-08 06:49:39');

-- --------------------------------------------------------

--
-- Table structure for table `staff`
--

CREATE TABLE `staff` (
  `id` int NOT NULL,
  `staff_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff`
--

INSERT INTO `staff` (`id`, `staff_id`, `email`, `password`, `role_id`) VALUES
(1, 'ICT001', 'computer-lab@igangaschoolofnursingandmidwifery.ac.ug', 'Techno123', 1),
(2, 'DG001', 'directorgeneral@igangaschoolofnursingandmidwifery.ac.ug', 'DorisJoy2026', 2),
(3, 'CEO001', 'ceo@igangaschoolofnursingandmidwifery.ac.ug', 'Lovely2God', 3),
(4, 'DA001', 'directoracademic@igangaschoolofnursingandmidwifery.ac.ug', 'Stephen123', 4),
(5, 'FIN001', 'finance@igangaschoolofnursingandmidwifery.ac.ug', 'DorisJoy2026', 5),
(6, 'PR001', 'principal@igangaschoolofnursingandmidwifery.ac.ug', 'isnm2026', 6),
(7, 'DP001', 'dep-principal@igangaschoolofnursingandmidwifery.ac.ug', 'Isnm2026', 7),
(8, 'REG001', 'academicregistrar@igangaschoolofnursingandmidwifery.ac.ug', 'Lovely2God', 8),
(9, 'HR001', 'hr-manager@igangaschoolofnursingandmidwifery.ac.ug', 'Alexis2026', 9),
(10, 'LIB001', 'library@igangaschoolofnursingandmidwifery.ac.ug', 'isnm2026', 11),
(11, 'NUR001', 'nursing-dep@igangaschoolofnursingandmidwifery.ac.ug', 'isnm4life', 12),
(12, 'MID001', 'midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug', 'Life2save', 13),
(13, 'AD001', 'admissions@igangaschoolofnursingandmidwifery.ac.ug', '2268926931', 23),
(14, 'ICT002', 'dannybict@igangaschoolofnursingandmidwifery.ac.ug', 'Lovely2God', 1);

-- --------------------------------------------------------

--
-- Table structure for table `staff_activity_log`
--

CREATE TABLE `staff_activity_log` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `activity_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activity_description` text COLLATE utf8mb4_unicode_ci,
  `module_accessed` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_login_attempts`
--

CREATE TABLE `staff_login_attempts` (
  `id` int NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `success` tinyint(1) DEFAULT '0',
  `failure_reason` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `staff_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_login_sessions`
--

CREATE TABLE `staff_login_sessions` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `session_token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_permissions`
--

CREATE TABLE `staff_permissions` (
  `id` int NOT NULL,
  `staff_id` int NOT NULL,
  `module` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `permission_level` enum('View','Edit','Admin','Super Admin') COLLATE utf8mb4_unicode_ci DEFAULT 'View',
  `granted_by` int DEFAULT NULL,
  `granted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_roles`
--

CREATE TABLE `staff_roles` (
  `id` int NOT NULL,
  `role_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dashboard_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `staff_roles`
--

INSERT INTO `staff_roles` (`id`, `role_name`, `dashboard_path`) VALUES
(1, 'Director ICT', 'dashboards/director-ict.php'),
(2, 'Director General', 'dashboards/director-general.php'),
(3, 'CEO', 'dashboards/ceo.php'),
(4, 'Director Academics', 'dashboards/director-academics.php'),
(5, 'Director Finance', 'dashboards/director-finance.php'),
(6, 'School Principal', 'dashboards/school-principal.php'),
(7, 'Deputy Principal', 'dashboards/deputy-principal.php'),
(8, 'Academic Registrar', 'dashboards/academic-registrar.php'),
(9, 'HR Manager', 'dashboards/hr-manager.php'),
(10, 'School Secretary', 'dashboards/school-secretary.php'),
(11, 'School Librarian', 'dashboards/school-librarian.php'),
(12, 'Head Nursing', 'dashboards/head-nursing.php'),
(13, 'Head Midwifery', 'dashboards/head-midwifery.php'),
(14, 'Senior Lecturers', 'dashboards/senior-lecturers.php'),
(15, 'Lecturers', 'dashboards/lecturers.php'),
(16, 'Matrons', 'dashboards/matrons.php'),
(17, 'Wardens', 'dashboards/wardens.php'),
(18, 'Sickbay', 'dashboards/sickbay.php'),
(19, 'Drivers', 'dashboards/drivers.php'),
(20, 'Security', 'dashboards/security.php'),
(21, 'Store Keeper', 'dashboards/storekeeper.php'),
(22, 'Guild President', 'dashboards/guild-president.php'),
(23, 'Director Admissions & Requirements', 'dashboards/director-admissions.php');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_active_tickets`
-- (See below for the actual view)
--
CREATE TABLE `v_active_tickets` (
`priority` enum('low','medium','high','critical')
,`ticket_count` bigint
,`ticket_numbers` text
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_computer_availability`
-- (See below for the actual view)
--
CREATE TABLE `v_computer_availability` (
`availability_percentage` decimal(29,2)
,`location` varchar(100)
,`maintenance_count` decimal(23,0)
,`offline_count` decimal(23,0)
,`online_count` decimal(23,0)
,`total_computers` bigint
);

-- --------------------------------------------------------

--
-- Structure for view `v_active_tickets`
--
DROP TABLE IF EXISTS `v_active_tickets`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_active_tickets`  AS SELECT `it_support_tickets`.`priority` AS `priority`, count(0) AS `ticket_count`, group_concat(`it_support_tickets`.`ticket_number` separator ',') AS `ticket_numbers` FROM `it_support_tickets` WHERE (`it_support_tickets`.`status` in ('open','in_progress')) GROUP BY `it_support_tickets`.`priority` ORDER BY (case `it_support_tickets`.`priority` when 'critical' then 1 when 'high' then 2 when 'medium' then 3 else 4 end) ASC ;

-- --------------------------------------------------------

--
-- Structure for view `v_computer_availability`
--
DROP TABLE IF EXISTS `v_computer_availability`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_computer_availability`  AS SELECT `lab_computers`.`location` AS `location`, count(0) AS `total_computers`, sum((case when (`lab_computers`.`status` = 'online') then 1 else 0 end)) AS `online_count`, sum((case when (`lab_computers`.`status` = 'offline') then 1 else 0 end)) AS `offline_count`, sum((case when (`lab_computers`.`status` = 'maintenance') then 1 else 0 end)) AS `maintenance_count`, round(((sum((case when (`lab_computers`.`status` = 'online') then 1 else 0 end)) * 100.0) / count(0)),2) AS `availability_percentage` FROM `lab_computers` WHERE (`lab_computers`.`status` <> 'deleted') GROUP BY `lab_computers`.`location` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `it_support_tickets`
--
ALTER TABLE `it_support_tickets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ticket_number` (`ticket_number`);

--
-- Indexes for table `lab_bookings`
--
ALTER TABLE `lab_bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_reference` (`booking_reference`);

--
-- Indexes for table `lab_computers`
--
ALTER TABLE `lab_computers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `computer_id` (`computer_id`);

--
-- Indexes for table `lab_usage_stats`
--
ALTER TABLE `lab_usage_stats`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_lab_date` (`lab_name`,`date`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_lab` (`lab_name`);

--
-- Indexes for table `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_computer` (`computer_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_date` (`scheduled_date`);

--
-- Indexes for table `network_devices`
--
ALTER TABLE `network_devices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `software_inventory`
--
ALTER TABLE `software_inventory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff`
--
ALTER TABLE `staff`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `staff_id` (`staff_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `staff_activity_log`
--
ALTER TABLE `staff_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `staff_login_attempts`
--
ALTER TABLE `staff_login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `staff_login_sessions`
--
ALTER TABLE `staff_login_sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_staff_id` (`staff_id`),
  ADD KEY `idx_session_token` (`session_token`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `staff_permissions`
--
ALTER TABLE `staff_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_staff_module` (`staff_id`,`module`);

--
-- Indexes for table `staff_roles`
--
ALTER TABLE `staff_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `it_support_tickets`
--
ALTER TABLE `it_support_tickets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_bookings`
--
ALTER TABLE `lab_bookings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_computers`
--
ALTER TABLE `lab_computers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `lab_usage_stats`
--
ALTER TABLE `lab_usage_stats`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `network_devices`
--
ALTER TABLE `network_devices`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `software_inventory`
--
ALTER TABLE `software_inventory`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `staff`
--
ALTER TABLE `staff`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `staff_activity_log`
--
ALTER TABLE `staff_activity_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_login_attempts`
--
ALTER TABLE `staff_login_attempts`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_login_sessions`
--
ALTER TABLE `staff_login_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_permissions`
--
ALTER TABLE `staff_permissions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_roles`
--
ALTER TABLE `staff_roles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `staff`
--
ALTER TABLE `staff`
  ADD CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `staff_roles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
