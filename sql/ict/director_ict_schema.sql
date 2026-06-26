-- Director ICT schema additions for igangaschoolofl_ict
-- Creates only tables that do not already exist

-- 1. ICT Assets (comprehensive asset register)
CREATE TABLE IF NOT EXISTS `ict_assets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `asset_number` varchar(100) NOT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `serial_number` varchar(255) DEFAULT NULL,
  `asset_name` varchar(200) NOT NULL,
  `asset_type` enum('computer','printer','scanner','projector','network','server','ups','software','accessory','other') DEFAULT 'other',
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `category_id` int DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `current_status` enum('active','in_maintenance','retired','transferred') DEFAULT 'active',
  `assigned_staff_id` int DEFAULT NULL,
  `assigned_department` varchar(200) DEFAULT NULL,
  `current_location` varchar(255) DEFAULT NULL,
  `purchase_cost` decimal(15,2) DEFAULT 0.00,
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_number` (`asset_number`),
  KEY `asset_type` (`asset_type`),
  KEY `current_status` (`current_status`),
  KEY `assigned_staff_id` (`assigned_staff_id`),
  KEY `warranty_expiry` (`warranty_expiry`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. ICT Asset Categories
CREATE TABLE IF NOT EXISTS `ict_asset_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_name` varchar(255) NOT NULL,
  `description` text,
  `parent_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_name` (`category_name`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. ICT Asset Assignments
CREATE TABLE IF NOT EXISTS `ict_asset_assignments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `asset_id` int NOT NULL,
  `assigned_to_staff_id` int DEFAULT NULL,
  `assigned_department` varchar(200) DEFAULT NULL,
  `assignment_date` date NOT NULL,
  `expected_return_date` date DEFAULT NULL,
  `actual_return_date` date DEFAULT NULL,
  `assignment_notes` text,
  `condition_at_assignment` varchar(200) DEFAULT NULL,
  `condition_at_return` varchar(200) DEFAULT NULL,
  `assigned_by` int DEFAULT NULL,
  `status` enum('active','returned','transferred') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `asset_id` (`asset_id`),
  KEY `assigned_to_staff_id` (`assigned_to_staff_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. ICT Asset Maintenance
CREATE TABLE IF NOT EXISTS `ict_asset_maintenance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `asset_id` int NOT NULL,
  `maintenance_type` enum('routine','repair','upgrade','cleaning','other') DEFAULT 'routine',
  `description` text NOT NULL,
  `performed_by` varchar(200) DEFAULT NULL,
  `cost` decimal(15,2) DEFAULT 0.00,
  `parts_replaced` text,
  `service_provider` varchar(200) DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
  `scheduled_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `asset_id` (`asset_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. ICT Asset Warranty
CREATE TABLE IF NOT EXISTS `ict_asset_warranty` (
  `id` int NOT NULL AUTO_INCREMENT,
  `asset_id` int NOT NULL,
  `warranty_provider` varchar(200) DEFAULT NULL,
  `warranty_type` enum('standard','extended','onsite','carry_in') DEFAULT 'standard',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `coverage_details` text,
  `contact_phone` varchar(50) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `status` enum('active','expired','claimed') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `asset_id` (`asset_id`),
  KEY `status` (`status`),
  KEY `end_date` (`end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. ICT Servers
CREATE TABLE IF NOT EXISTS `ict_servers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `server_name` varchar(200) NOT NULL,
  `hostname` varchar(200) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `server_type` enum('physical','virtual','cloud') DEFAULT 'physical',
  `os` varchar(100) DEFAULT NULL,
  `os_version` varchar(100) DEFAULT NULL,
  `cpu_cores` int DEFAULT 0,
  `ram_gb` int DEFAULT 0,
  `storage_gb` int DEFAULT 0,
  `purpose` text,
  `location` varchar(200) DEFAULT NULL,
  `status` enum('online','offline','maintenance','decommissioned') DEFAULT 'online',
  `uptime_hours` int DEFAULT 0,
  `last_reboot` timestamp NULL DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `server_name` (`server_name`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. ICT Network Logs
CREATE TABLE IF NOT EXISTS `ict_network_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `device_id` int DEFAULT NULL,
  `log_type` enum('status_change','error','performance','security','config_change') DEFAULT 'status_change',
  `message` text NOT NULL,
  `severity` enum('info','warning','error','critical') DEFAULT 'info',
  `logged_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `device_id` (`device_id`),
  KEY `log_type` (`log_type`),
  KEY `severity` (`severity`),
  KEY `logged_at` (`logged_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. ICT WiFi Devices
CREATE TABLE IF NOT EXISTS `ict_wifi_devices` (
  `id` int NOT NULL AUTO_INCREMENT,
  `device_name` varchar(200) NOT NULL,
  `ssid` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `mac_address` varchar(17) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `firmware_version` varchar(50) DEFAULT NULL,
  `status` enum('online','offline','maintenance') DEFAULT 'online',
  `connected_clients` int DEFAULT 0,
  `max_clients` int DEFAULT 50,
  `band` enum('2.4ghz','5ghz','dual') DEFAULT 'dual',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. ICT System Backups
CREATE TABLE IF NOT EXISTS `ict_system_backups` (
  `id` int NOT NULL AUTO_INCREMENT,
  `backup_name` varchar(200) NOT NULL,
  `backup_type` enum('database','file','full','incremental') DEFAULT 'database',
  `target_database` varchar(100) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size_mb` decimal(15,2) DEFAULT 0.00,
  `checksum` varchar(64) DEFAULT NULL,
  `status` enum('running','completed','failed','verified') DEFAULT 'running',
  `initiated_by` int DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` timestamp NULL DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `backup_type` (`backup_type`),
  KEY `status` (`status`),
  KEY `started_at` (`started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. ICT Backup Logs
CREATE TABLE IF NOT EXISTS `ict_backup_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `backup_id` int DEFAULT NULL,
  `log_message` text NOT NULL,
  `log_level` enum('info','warning','error') DEFAULT 'info',
  `logged_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `backup_id` (`backup_id`),
  KEY `logged_at` (`logged_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. ICT Security Logs
CREATE TABLE IF NOT EXISTS `ict_security_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `event_type` enum('login','logout','failed_login','permission_change','account_lock','password_change','user_create','user_delete','settings_change','other') NOT NULL,
  `user_id` int DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `description` text,
  `severity` enum('info','warning','critical') DEFAULT 'info',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `event_type` (`event_type`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`),
  KEY `ip_address` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. ICT Failed Logins
CREATE TABLE IF NOT EXISTS `ict_failed_logins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `attempted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `reason` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `username` (`username`),
  KEY `ip_address` (`ip_address`),
  KEY `attempted_at` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. ICT Login Sessions
CREATE TABLE IF NOT EXISTS `ict_login_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `user_type` enum('staff','student') DEFAULT 'staff',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `login_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_activity` timestamp NULL DEFAULT NULL,
  `logout_at` timestamp NULL DEFAULT NULL,
  `session_duration_sec` int DEFAULT 0,
  `status` enum('active','expired','terminated') DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `login_at` (`login_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. ICT System Health
CREATE TABLE IF NOT EXISTS `ict_system_health` (
  `id` int NOT NULL AUTO_INCREMENT,
  `check_type` enum('cpu','memory','disk','network','database','service') NOT NULL,
  `check_name` varchar(200) DEFAULT NULL,
  `status` enum('healthy','warning','critical','unknown') DEFAULT 'healthy',
  `value` varchar(255) DEFAULT NULL,
  `threshold` varchar(255) DEFAULT NULL,
  `message` text,
  `checked_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `check_type` (`check_type`),
  KEY `status` (`status`),
  KEY `checked_at` (`checked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. ICT System Notifications
CREATE TABLE IF NOT EXISTS `ict_system_notifications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `notification_type` enum('info','warning','critical','success') DEFAULT 'info',
  `category` varchar(100) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `is_dismissed` tinyint(1) DEFAULT 0,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `notification_type` (`notification_type`),
  KEY `is_read` (`is_read`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. ICT System Alerts
CREATE TABLE IF NOT EXISTS `ict_system_alerts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `alert_type` enum('system','security','backup','performance','network','storage') NOT NULL,
  `severity` enum('info','warning','critical') DEFAULT 'info',
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `acknowledged_by` int DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `status` enum('active','acknowledged','resolved') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `alert_type` (`alert_type`),
  KEY `severity` (`severity`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. ICT System Settings
CREATE TABLE IF NOT EXISTS `ict_system_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `setting_group` varchar(100) DEFAULT 'general',
  `description` text,
  `is_encrypted` tinyint(1) DEFAULT 0,
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `setting_group` (`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. ICT Module Permissions
CREATE TABLE IF NOT EXISTS `ict_module_permissions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `module_name` varchar(100) NOT NULL,
  `role_keyword` varchar(50) NOT NULL,
  `can_view` tinyint(1) DEFAULT 1,
  `can_create` tinyint(1) DEFAULT 0,
  `can_edit` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0,
  `can_approve` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `module_role` (`module_name`,`role_keyword`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. ICT Device Categories
CREATE TABLE IF NOT EXISTS `ict_device_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_name` varchar(200) NOT NULL,
  `device_type` enum('computer','printer','scanner','projector','network','server','ups','accessory','other') DEFAULT 'other',
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. ICT Audit Logs
CREATE TABLE IF NOT EXISTS `ict_audit_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `resource_type` varchar(100) DEFAULT NULL,
  `resource_id` int DEFAULT NULL,
  `description` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `resource_type` (`resource_type`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Default settings
INSERT IGNORE INTO `ict_system_settings` (`setting_key`, `setting_value`, `setting_group`, `description`) VALUES
('session_timeout_minutes', '30', 'security', 'User session timeout in minutes'),
('max_login_attempts', '5', 'security', 'Maximum failed login attempts before lockout'),
('lockout_duration_minutes', '15', 'security', 'Account lockout duration in minutes'),
('password_min_length', '8', 'security', 'Minimum password length'),
('backup_retention_days', '30', 'backup', 'Days to retain backups'),
('auto_backup_enabled', 'true', 'backup', 'Enable automatic scheduled backups'),
('backup_time', '02:00', 'backup', 'Scheduled backup time'),
('system_health_interval', '5', 'monitoring', 'System health check interval in minutes'),
('notify_critical_alerts', 'true', 'alerts', 'Send notifications for critical alerts'),
('maintenance_mode', 'false', 'system', 'System maintenance mode flag');

-- Default asset categories
INSERT IGNORE INTO `ict_asset_categories` (`category_name`, `description`) VALUES
('Desktop Computers', 'Desktop workstations and PCs'),
('Laptops', 'Portable notebook computers'),
('Servers', 'Physical and virtual server systems'),
('Printers', 'All printer types including multi-function'),
('Scanners', 'Document and photo scanners'),
('Projectors', 'Multimedia projectors and displays'),
('Network Equipment', 'Routers, switches, access points'),
('UPS Systems', 'Uninterruptible power supplies'),
('Software', 'Licensed software packages'),
('Accessories', 'Peripherals and accessories');

-- Default device categories
INSERT IGNORE INTO `ict_device_categories` (`category_name`, `device_type`, `description`) VALUES
('Desktop Computers', 'computer', 'Desktop workstations'),
('Laptops', 'computer', 'Portable computers'),
('Network Printers', 'printer', 'Network-connected printers'),
('Scanners', 'scanner', 'Document scanners'),
('Projectors', 'projector', 'Multimedia projectors'),
('Network Switches', 'network', 'Managed and unmanaged switches'),
('Routers', 'network', 'Network routers'),
('Access Points', 'network', 'Wireless access points'),
('Servers', 'server', 'Server systems'),
('UPS', 'ups', 'Power backup units');
