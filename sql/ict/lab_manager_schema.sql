-- Computer Lab Manager schema additions for igangaschoolofl_ict
-- Creates only tables that do not already exist

-- 1. Lab Rooms
CREATE TABLE IF NOT EXISTS `lab_rooms` (
  `id` int NOT NULL AUTO_INCREMENT,
  `room_name` varchar(100) NOT NULL,
  `room_code` varchar(20) NOT NULL,
  `capacity` int NOT NULL DEFAULT 0,
  `computer_count` int NOT NULL DEFAULT 0,
  `location` varchar(200) DEFAULT NULL,
  `status` enum('active','inactive','maintenance') DEFAULT 'active',
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_code` (`room_code`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Student ID Cards
CREATE TABLE IF NOT EXISTS `student_id_cards` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `card_number` varchar(50) NOT NULL,
  `registration_number` varchar(50) DEFAULT NULL,
  `program` varchar(200) DEFAULT NULL,
  `intake` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `photo_path` varchar(500) DEFAULT NULL,
  `qr_code` text,
  `barcode` varchar(200) DEFAULT NULL,
  `status` enum('active','expired','lost','damaged','replaced') DEFAULT 'active',
  `issued_by` int DEFAULT NULL,
  `issued_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_print_date` timestamp NULL DEFAULT NULL,
  `print_count` int DEFAULT 0,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `card_number` (`card_number`),
  KEY `student_id` (`student_id`),
  KEY `status` (`status`),
  KEY `expiry_date` (`expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. ID Card Print History
CREATE TABLE IF NOT EXISTS `id_card_print_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `card_id` int NOT NULL,
  `student_id` int NOT NULL,
  `print_type` enum('new','reprint','bulk') DEFAULT 'new',
  `reason` varchar(200) DEFAULT NULL,
  `printed_by` int DEFAULT NULL,
  `print_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `copies` int DEFAULT 1,
  `notes` text,
  PRIMARY KEY (`id`),
  KEY `card_id` (`card_id`),
  KEY `student_id` (`student_id`),
  KEY `printed_by` (`printed_by`),
  KEY `print_date` (`print_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. ID Card Replacements
CREATE TABLE IF NOT EXISTS `id_card_replacements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `original_card_id` int DEFAULT NULL,
  `reason` enum('lost','damaged','stolen','name_change','info_update','other') NOT NULL,
  `description` text,
  `charge_amount` decimal(10,2) DEFAULT 0.00,
  `payment_status` enum('pending','paid','waived') DEFAULT 'pending',
  `approved_by` int DEFAULT NULL,
  `replacement_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `new_card_id` int DEFAULT NULL,
  `status` enum('pending','approved','completed','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `original_card_id` (`original_card_id`),
  KEY `new_card_id` (`new_card_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Lab Computer Assignments
CREATE TABLE IF NOT EXISTS `lab_computer_assignments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `computer_id` int NOT NULL,
  `student_id` int DEFAULT NULL,
  `staff_id` int DEFAULT NULL,
  `assignment_type` enum('student','staff','lecturer') DEFAULT 'student',
  `assigned_by` int DEFAULT NULL,
  `assigned_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `purpose` text,
  `status` enum('active','returned','transferred') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `computer_id` (`computer_id`),
  KEY `student_id` (`student_id`),
  KEY `staff_id` (`staff_id`),
  KEY `status` (`status`),
  KEY `assigned_date` (`assigned_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Lab Attendance
CREATE TABLE IF NOT EXISTS `lab_attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int NOT NULL,
  `lab_room_id` int DEFAULT NULL,
  `session_id` int DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `computer_id` int DEFAULT NULL,
  `seat_number` varchar(20) DEFAULT NULL,
  `status` enum('present','absent','late','excused') DEFAULT 'present',
  `marked_by` int DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `lab_room_id` (`lab_room_id`),
  KEY `session_id` (`session_id`),
  KEY `attendance_date` (`attendance_date`),
  KEY `computer_id` (`computer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Lab Practical Sessions
CREATE TABLE IF NOT EXISTS `lab_practical_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `session_code` varchar(50) NOT NULL,
  `course_name` varchar(200) NOT NULL,
  `instructor_name` varchar(200) DEFAULT NULL,
  `instructor_id` int DEFAULT NULL,
  `lab_room_id` int DEFAULT NULL,
  `session_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `program` varchar(200) DEFAULT NULL,
  `year` int DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `max_students` int DEFAULT 0,
  `status` enum('scheduled','ongoing','completed','cancelled') DEFAULT 'scheduled',
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_code` (`session_code`),
  KEY `lab_room_id` (`lab_room_id`),
  KEY `session_date` (`session_date`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Lab Equipment
CREATE TABLE IF NOT EXISTS `lab_equipment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `equipment_code` varchar(50) NOT NULL,
  `equipment_name` varchar(200) NOT NULL,
  `equipment_type` enum('computer','printer','scanner','projector','ups','accessory','other') DEFAULT 'other',
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `serial_number` varchar(200) DEFAULT NULL,
  `lab_room_id` int DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `condition_status` enum('excellent','good','fair','poor','faulty','retired') DEFAULT 'good',
  `status` enum('available','in_use','maintenance','retired') DEFAULT 'available',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `equipment_code` (`equipment_code`),
  KEY `lab_room_id` (`lab_room_id`),
  KEY `equipment_type` (`equipment_type`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Lab Equipment Checkout
CREATE TABLE IF NOT EXISTS `lab_equipment_checkout` (
  `id` int NOT NULL AUTO_INCREMENT,
  `equipment_id` int NOT NULL,
  `checked_out_to` varchar(200) NOT NULL,
  `borrower_type` enum('student','staff','lecturer') DEFAULT 'student',
  `borrower_id` int DEFAULT NULL,
  `checkout_date` datetime NOT NULL,
  `expected_return` datetime DEFAULT NULL,
  `actual_return` datetime DEFAULT NULL,
  `purpose` text,
  `condition_at_checkout` varchar(200) DEFAULT NULL,
  `condition_at_return` varchar(200) DEFAULT NULL,
  `checked_out_by` int DEFAULT NULL,
  `status` enum('checked_out','returned','overdue','lost','damaged') DEFAULT 'checked_out',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `equipment_id` (`equipment_id`),
  KEY `borrower_id` (`borrower_id`),
  KEY `status` (`status`),
  KEY `expected_return` (`expected_return`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Printing Jobs
CREATE TABLE IF NOT EXISTS `printing_jobs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `job_number` varchar(50) NOT NULL,
  `requester_name` varchar(200) NOT NULL,
  `requester_type` enum('student','staff') NOT NULL,
  `requester_id` int DEFAULT NULL,
  `document_name` varchar(200) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `pages` int NOT NULL DEFAULT 1,
  `copies` int DEFAULT 1,
  `print_type` enum('bw','color','photocopy') DEFAULT 'bw',
  `paper_size` enum('A4','A3','letter','legal') DEFAULT 'A4',
  `charge_per_page` decimal(10,2) DEFAULT 0.00,
  `total_charge` decimal(10,2) DEFAULT 0.00,
  `payment_status` enum('pending','paid','waived') DEFAULT 'pending',
  `status` enum('pending','printing','completed','cancelled') DEFAULT 'pending',
  `printed_by` int DEFAULT NULL,
  `printed_at` timestamp NULL DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_number` (`job_number`),
  KEY `requester_id` (`requester_id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Printing Charges Config
CREATE TABLE IF NOT EXISTS `printing_charges` (
  `id` int NOT NULL AUTO_INCREMENT,
  `print_type` enum('bw','color','photocopy') NOT NULL,
  `paper_size` enum('A4','A3','letter','legal') DEFAULT 'A4',
  `charge_per_page` decimal(10,2) NOT NULL DEFAULT 0.00,
  `description` varchar(200) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `print_type_paper` (`print_type`,`paper_size`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Computer Repairs
CREATE TABLE IF NOT EXISTS `computer_repairs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `repair_number` varchar(50) NOT NULL,
  `computer_id` int DEFAULT NULL,
  `equipment_id` int DEFAULT NULL,
  `reported_by` varchar(200) NOT NULL,
  `reporter_type` enum('student','staff','lecturer') DEFAULT 'student',
  `reporter_id` int DEFAULT NULL,
  `issue_description` text NOT NULL,
  `issue_category` enum('hardware','software','network','other') DEFAULT 'other',
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `assigned_technician` varchar(200) DEFAULT NULL,
  `diagnosis` text,
  `resolution` text,
  `parts_replaced` text,
  `cost` decimal(10,2) DEFAULT 0.00,
  `status` enum('reported','diagnosed','in_progress','completed','closed','cancelled') DEFAULT 'reported',
  `reported_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `diagnosed_date` timestamp NULL DEFAULT NULL,
  `completed_date` timestamp NULL DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `repair_number` (`repair_number`),
  KEY `computer_id` (`computer_id`),
  KEY `equipment_id` (`equipment_id`),
  KEY `status` (`status`),
  KEY `priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Software Installations
CREATE TABLE IF NOT EXISTS `software_installations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `software_id` int DEFAULT NULL,
  `computer_id` int DEFAULT NULL,
  `lab_room_id` int DEFAULT NULL,
  `installed_by` varchar(200) DEFAULT NULL,
  `installation_date` date DEFAULT NULL,
  `license_key_used` varchar(200) DEFAULT NULL,
  `version_installed` varchar(50) DEFAULT NULL,
  `status` enum('installed','updated','removed') DEFAULT 'installed',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `software_id` (`software_id`),
  KEY `computer_id` (`computer_id`),
  KEY `lab_room_id` (`lab_room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Asset Assignments (consolidated)
CREATE TABLE IF NOT EXISTS `lab_asset_assignments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `asset_type` enum('computer','equipment','accessory') NOT NULL,
  `asset_id` int NOT NULL,
  `assigned_to_type` enum('student','staff','lecturer','lab') DEFAULT 'lab',
  `assigned_to_id` int DEFAULT NULL,
  `lab_room_id` int DEFAULT NULL,
  `assigned_by` int DEFAULT NULL,
  `assigned_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `purpose` text,
  `status` enum('active','returned','transferred') DEFAULT 'active',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `asset_type` (`asset_type`,`asset_id`),
  KEY `assigned_to_type` (`assigned_to_type`,`assigned_to_id`),
  KEY `lab_room_id` (`lab_room_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Computer Consumables
CREATE TABLE IF NOT EXISTS `lab_consumables` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_name` varchar(200) NOT NULL,
  `item_category` enum('toner','ink','paper','cable','mouse','keyboard','usb','cd_dvd','other') DEFAULT 'other',
  `quantity` int NOT NULL DEFAULT 0,
  `reorder_level` int DEFAULT 5,
  `unit` varchar(50) DEFAULT 'pcs',
  `unit_cost` decimal(10,2) DEFAULT 0.00,
  `supplier` varchar(200) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `last_restocked` date DEFAULT NULL,
  `status` enum('in_stock','low_stock','out_of_stock') DEFAULT 'in_stock',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `item_category` (`item_category`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Default printing charges
INSERT IGNORE INTO `printing_charges` (`print_type`, `paper_size`, `charge_per_page`, `description`) VALUES
('bw', 'A4', 100.00, 'Black & White A4 per page'),
('color', 'A4', 500.00, 'Colour A4 per page'),
('photocopy', 'A4', 50.00, 'Photocopy A4 per page'),
('bw', 'A3', 200.00, 'Black & White A3 per page'),
('color', 'A3', 1000.00, 'Colour A3 per page'),
('photocopy', 'A3', 100.00, 'Photocopy A3 per page');

-- 17. Default lab rooms
INSERT IGNORE INTO `lab_rooms` (`room_name`, `room_code`, `capacity`, `computer_count`, `location`, `status`) VALUES
('Computer Lab A', 'LAB-A', 40, 40, 'Main Building, Ground Floor', 'active'),
('Computer Lab B', 'LAB-B', 30, 30, 'Main Building, Ground Floor', 'active'),
('Computer Lab C', 'LAB-C', 25, 25, 'Main Building, First Floor', 'active'),
('Computer Lab D', 'LAB-D', 20, 20, 'Main Building, First Floor', 'active'),
('Skills Lab', 'SKILLS-1', 15, 15, 'Clinical Building', 'active');
