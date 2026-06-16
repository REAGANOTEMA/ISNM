-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 16, 2026 at 08:30 PM
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

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `add_role_description_col_if_missing` ()   BEGIN
    DECLARE CONTINUE HANDLER FOR 1060 BEGIN END;
    ALTER TABLE staff_roles ADD COLUMN role_description TEXT AFTER role_name;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `it_support_tickets`
--

CREATE TABLE `it_support_tickets` (
  `id` int NOT NULL,
  `ticket_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `requester_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `requester_email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requester_type` enum('student','staff','faculty') COLLATE utf8mb4_unicode_ci NOT NULL,
  `issue_type` enum('hardware','software','network','account','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` enum('low','medium','high','critical') COLLATE utf8mb4_unicode_ci DEFAULT 'medium',
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('open','in_progress','resolved','closed') COLLATE utf8mb4_unicode_ci DEFAULT 'open',
  `assigned_to` int DEFAULT NULL,
  `resolution_notes` text COLLATE utf8mb4_unicode_ci,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `it_support_tickets`
--

INSERT INTO `it_support_tickets` (`id`, `ticket_number`, `requester_name`, `requester_email`, `requester_type`, `issue_type`, `priority`, `description`, `status`, `assigned_to`, `resolution_notes`, `resolved_at`, `created_at`, `updated_at`) VALUES
(1, 'TKT-2024-001', 'John Mugisha', 'jmugisha@student.isnm.ac.ug', 'student', 'software', 'medium', 'Unable to access SPSS software on Lab A computers', 'open', NULL, NULL, NULL, '2026-06-14 18:38:56', '2026-06-14 18:38:56'),
(2, 'TKT-2024-002', 'Dr. Emily Achieng', 'eachieng@isnm.ac.ug', 'staff', 'hardware', 'high', 'Projector in Lab B not displaying properly', 'in_progress', NULL, NULL, NULL, '2026-06-14 18:38:56', '2026-06-14 18:38:56'),
(3, 'TKT-2024-003', 'Peter Kato', 'pkato@student.isnm.ac.ug', 'student', 'account', 'low', 'Forgot password for student portal', 'open', NULL, NULL, NULL, '2026-06-14 18:38:56', '2026-06-14 18:38:56'),
(4, 'TKT-2024-004', 'Ms. Ruth Akello', 'rakello@isnm.ac.ug', 'staff', 'network', 'critical', 'WiFi connection dropping frequently in Lab A', 'open', NULL, NULL, NULL, '2026-06-14 18:38:56', '2026-06-14 18:38:56');

-- --------------------------------------------------------

--
-- Table structure for table `lab_bookings`
--

CREATE TABLE `lab_bookings` (
  `id` int NOT NULL,
  `booking_reference` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `instructor_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `instructor_email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `booking_date` date NOT NULL,
  `time_slot` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `number_of_students` int NOT NULL,
  `purpose` text COLLATE utf8mb4_unicode_ci,
  `special_requirements` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','confirmed','cancelled','completed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `lab_assigned` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lab_bookings`
--

INSERT INTO `lab_bookings` (`id`, `booking_reference`, `course_name`, `instructor_name`, `instructor_email`, `booking_date`, `time_slot`, `number_of_students`, `purpose`, `special_requirements`, `status`, `approved_by`, `lab_assigned`, `created_at`, `updated_at`) VALUES
(1, 'BK-2024-001', 'Introduction to Nursing Informatics', 'Dr. Sarah Johnson', 'sjohnson@isnm.ac.ug', '2024-06-10', '09:00 AM - 11:00 AM', 25, 'Practical session on electronic health records', NULL, 'confirmed', NULL, 'Lab A', '2026-06-14 18:38:55', '2026-06-14 18:38:55'),
(2, 'BK-2024-002', 'Research Methods', 'Prof. Michael Okonkwo', 'mokonkwo@isnm.ac.ug', '2024-06-10', '02:00 PM - 04:00 PM', 30, 'Data analysis using SPSS', NULL, 'pending', NULL, 'Lab B', '2026-06-14 18:38:55', '2026-06-14 18:38:55'),
(3, 'BK-2024-003', 'Computer Literacy', 'Ms. Grace Namukasa', 'gnamukasa@isnm.ac.ug', '2024-06-11', '09:00 AM - 11:00 AM', 20, 'Basic computer skills training', NULL, 'confirmed', NULL, 'Lab A', '2026-06-14 18:38:55', '2026-06-14 18:38:55');

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
(1, 'LAB-A-001', 'Computer Lab A - Station 1', 'Lab A - Floor 1', 'online', '192.168.1.101', 'AA:BB:CC:DD:EE:01', 'Intel i5, 8GB RAM, 256GB SSD', 'Windows 11 Pro', '2024-05-01', '2024-08-01', NULL, NULL, NULL, NULL, '2026-06-14 18:38:55', '2026-06-14 18:38:55'),
(2, 'LAB-A-002', 'Computer Lab A - Station 2', 'Lab A - Floor 1', 'online', '192.168.1.102', 'AA:BB:CC:DD:EE:02', 'Intel i5, 8GB RAM, 256GB SSD', 'Windows 11 Pro', '2024-05-01', '2024-08-01', NULL, NULL, NULL, NULL, '2026-06-14 18:38:55', '2026-06-14 18:38:55'),
(3, 'LAB-A-003', 'Computer Lab A - Station 3', 'Lab A - Floor 1', 'offline', '192.168.1.103', 'AA:BB:CC:DD:EE:03', 'Intel i5, 8GB RAM, 256GB SSD', 'Windows 11 Pro', '2024-05-01', '2024-08-01', 'Hardware issue - PSU replacement needed', NULL, NULL, NULL, '2026-06-14 18:38:55', '2026-06-14 18:38:55'),
(4, 'LAB-B-001', 'Computer Lab B - Station 1', 'Lab B - Floor 2', 'online', '192.168.2.101', 'BB:CC:DD:EE:FF:01', 'Intel i7, 16GB RAM, 512GB SSD', 'Windows 11 Pro', '2024-05-15', '2024-08-15', NULL, NULL, NULL, NULL, '2026-06-14 18:38:55', '2026-06-14 18:38:55'),
(5, 'LAB-B-002', 'Computer Lab B - Station 2', 'Lab B - Floor 2', 'maintenance', '192.168.2.102', 'BB:CC:DD:EE:FF:02', 'Intel i7, 16GB RAM, 512GB SSD', 'Windows 11 Pro', '2024-05-15', '2024-08-15', 'OS reinstallation in progress', NULL, NULL, NULL, '2026-06-14 18:38:55', '2026-06-14 18:38:55');

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
(1, 'Lab A', '2024-06-05', 8, 45, 25, 90, 22, 25, NULL, '2026-06-14 18:38:56'),
(2, 'Lab B', '2024-06-05', 6, 35, 20, 85, 18, 20, NULL, '2026-06-14 18:38:56'),
(3, 'Lab A', '2024-06-06', 10, 55, 28, 95, 24, 25, NULL, '2026-06-14 18:38:56'),
(4, 'Lab B', '2024-06-06', 7, 40, 22, 80, 19, 20, NULL, '2026-06-14 18:38:56');

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
(1, 'LAB-A-003', 'repair', 'Power supply unit replacement required', 'IT Technician - James', 150.00, NULL, 'scheduled', '2024-06-12', NULL, '2026-06-14 18:38:56', '2026-06-14 18:38:56'),
(2, 'LAB-B-002', 'routine', 'Operating system reinstallation and updates', 'IT Technician - Sarah', 0.00, NULL, 'in_progress', '2024-06-10', NULL, '2026-06-14 18:38:56', '2026-06-14 18:38:56'),
(3, 'LAB-A-003', 'repair', 'Power supply unit replacement required', 'IT Technician - James', 150.00, NULL, 'scheduled', '2024-06-12', NULL, '2026-06-15 04:19:05', '2026-06-15 04:19:05'),
(4, 'LAB-B-002', 'routine', 'Operating system reinstallation and updates', 'IT Technician - Sarah', 0.00, NULL, 'in_progress', '2024-06-10', NULL, '2026-06-15 04:19:05', '2026-06-15 04:19:05');

-- --------------------------------------------------------

--
-- Table structure for table `network_devices`
--

CREATE TABLE `network_devices` (
  `id` int NOT NULL,
  `device_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `device_type` enum('router','switch','access_point','firewall','server','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mac_address` varchar(17) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('online','offline','maintenance') COLLATE utf8mb4_unicode_ci DEFAULT 'online',
  `firmware_version` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_check` timestamp NULL DEFAULT NULL,
  `uptime_hours` int DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `network_devices`
--

INSERT INTO `network_devices` (`id`, `device_name`, `device_type`, `ip_address`, `mac_address`, `location`, `status`, `firmware_version`, `last_check`, `uptime_hours`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Main Router', 'router', '192.168.0.1', '00:11:22:33:44:55', 'Server Room', 'online', 'v2.1.0', NULL, 720, NULL, '2026-06-14 18:38:56', '2026-06-14 18:38:56'),
(2, 'Lab A Switch', 'switch', '192.168.1.1', '00:11:22:33:44:56', 'Lab A - Floor 1', 'online', 'v1.5.2', NULL, 480, NULL, '2026-06-14 18:38:56', '2026-06-14 18:38:56'),
(3, 'Lab B Switch', 'switch', '192.168.2.1', '00:11:22:33:44:57', 'Lab B - Floor 2', 'online', 'v1.5.2', NULL, 480, NULL, '2026-06-14 18:38:56', '2026-06-14 18:38:56'),
(4, 'WiFi Access Point A', 'access_point', '192.168.0.10', '00:11:22:33:44:58', 'Lab A - Floor 1', 'online', 'v3.2.1', NULL, 240, NULL, '2026-06-14 18:38:56', '2026-06-14 18:38:56'),
(5, 'WiFi Access Point B', 'access_point', '192.168.0.11', '00:11:22:33:44:59', 'Lab B - Floor 2', 'offline', 'v3.2.1', NULL, 0, 'Needs repair', '2026-06-14 18:38:56', '2026-06-14 18:38:56'),
(6, 'Firewall', 'firewall', '192.168.0.2', '00:11:22:33:44:60', 'Server Room', 'online', 'v4.0.0', NULL, 720, NULL, '2026-06-14 18:38:56', '2026-06-14 18:38:56'),
(7, 'Main Router', 'router', '192.168.0.1', '00:11:22:33:44:55', 'Server Room', 'online', 'v2.1.0', NULL, 720, NULL, '2026-06-15 04:19:05', '2026-06-15 04:19:05'),
(8, 'Lab A Switch', 'switch', '192.168.1.1', '00:11:22:33:44:56', 'Lab A - Floor 1', 'online', 'v1.5.2', NULL, 480, NULL, '2026-06-15 04:19:05', '2026-06-15 04:19:05'),
(9, 'Lab B Switch', 'switch', '192.168.2.1', '00:11:22:33:44:57', 'Lab B - Floor 2', 'online', 'v1.5.2', NULL, 480, NULL, '2026-06-15 04:19:05', '2026-06-15 04:19:05'),
(10, 'WiFi Access Point A', 'access_point', '192.168.0.10', '00:11:22:33:44:58', 'Lab A - Floor 1', 'online', 'v3.2.1', NULL, 240, NULL, '2026-06-15 04:19:05', '2026-06-15 04:19:05'),
(11, 'WiFi Access Point B', 'access_point', '192.168.0.11', '00:11:22:33:44:59', 'Lab B - Floor 2', 'offline', 'v3.2.1', NULL, 0, 'Needs repair', '2026-06-15 04:19:05', '2026-06-15 04:19:05'),
(12, 'Firewall', 'firewall', '192.168.0.2', '00:11:22:33:44:60', 'Server Room', 'online', 'v4.0.0', NULL, 720, NULL, '2026-06-15 04:19:05', '2026-06-15 04:19:05');

-- --------------------------------------------------------

--
-- Table structure for table `software_inventory`
--

CREATE TABLE `software_inventory` (
  `id` int NOT NULL,
  `software_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_key` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_type` enum('free','commercial','educational','trial') COLLATE utf8mb4_unicode_ci DEFAULT 'educational',
  `license_expiry` date DEFAULT NULL,
  `installation_count` int DEFAULT '0',
  `update_available` tinyint(1) DEFAULT '0',
  `latest_version` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `download_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` enum('os','office','development','design','antivirus','utility','other') COLLATE utf8mb4_unicode_ci DEFAULT 'utility',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `software_inventory`
--

INSERT INTO `software_inventory` (`id`, `software_name`, `version`, `license_key`, `license_type`, `license_expiry`, `installation_count`, `update_available`, `latest_version`, `download_url`, `category`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Microsoft Office 365', '2024', NULL, 'educational', '2025-12-31', 50, 0, '2024', NULL, 'office', NULL, '2026-06-14 18:38:56', '2026-06-14 18:38:56'),
(2, 'SPSS Statistics', '29.0', NULL, 'commercial', '2024-12-31', 25, 1, '30.0', NULL, 'development', NULL, '2026-06-14 18:38:56', '2026-06-14 18:38:56'),
(3, 'Windows 11 Pro', '23H2', NULL, 'educational', '2026-06-30', 50, 0, '23H2', NULL, 'os', NULL, '2026-06-14 18:38:56', '2026-06-14 18:38:56'),
(4, 'Adobe Creative Cloud', '2024', NULL, 'educational', '2024-08-31', 15, 1, '2024.1', NULL, 'design', NULL, '2026-06-14 18:38:56', '2026-06-14 18:38:56'),
(5, 'Malwarebytes Antivirus', '5.0', NULL, 'commercial', '2025-01-15', 50, 0, '5.0', NULL, 'antivirus', NULL, '2026-06-14 18:38:56', '2026-06-14 18:38:56'),
(6, 'Microsoft Office 365', '2024', NULL, 'educational', '2025-12-31', 50, 0, '2024', NULL, 'office', NULL, '2026-06-15 04:19:05', '2026-06-15 04:19:05'),
(7, 'SPSS Statistics', '29.0', NULL, 'commercial', '2024-12-31', 25, 1, '30.0', NULL, 'development', NULL, '2026-06-15 04:19:05', '2026-06-15 04:19:05'),
(8, 'Windows 11 Pro', '23H2', NULL, 'educational', '2026-06-30', 50, 0, '23H2', NULL, 'os', NULL, '2026-06-15 04:19:05', '2026-06-15 04:19:05'),
(9, 'Adobe Creative Cloud', '2024', NULL, 'educational', '2024-08-31', 15, 1, '2024.1', NULL, 'design', NULL, '2026-06-15 04:19:05', '2026-06-15 04:19:05'),
(10, 'Malwarebytes Antivirus', '5.0', NULL, 'commercial', '2025-01-15', 50, 0, '5.0', NULL, 'antivirus', NULL, '2026-06-15 04:19:05', '2026-06-15 04:19:05');

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
`location` varchar(100)
,`total_computers` bigint
,`online_count` decimal(23,0)
,`offline_count` decimal(23,0)
,`maintenance_count` decimal(23,0)
,`availability_percentage` decimal(29,2)
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
  ADD UNIQUE KEY `ticket_number` (`ticket_number`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_priority` (`priority`),
  ADD KEY `idx_requester` (`requester_name`),
  ADD KEY `idx_type` (`issue_type`);

--
-- Indexes for table `lab_bookings`
--
ALTER TABLE `lab_bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_reference` (`booking_reference`),
  ADD KEY `idx_date` (`booking_date`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_instructor` (`instructor_name`);

--
-- Indexes for table `lab_computers`
--
ALTER TABLE `lab_computers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `computer_id` (`computer_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_location` (`location`);

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_type` (`device_type`),
  ADD KEY `idx_ip` (`ip_address`);

--
-- Indexes for table `software_inventory`
--
ALTER TABLE `software_inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_update` (`update_available`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `it_support_tickets`
--
ALTER TABLE `it_support_tickets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `lab_bookings`
--
ALTER TABLE `lab_bookings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `lab_computers`
--
ALTER TABLE `lab_computers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `lab_usage_stats`
--
ALTER TABLE `lab_usage_stats`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `maintenance_logs`
--
ALTER TABLE `maintenance_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `network_devices`
--
ALTER TABLE `network_devices`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `software_inventory`
--
ALTER TABLE `software_inventory`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
