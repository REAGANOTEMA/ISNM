-- ===============================================================
-- Migration: Department Tables for ISNM Dashboards
-- Date: 2026-06-30
-- Description: Creates ALL missing department tables referenced
-- by the dashboard files under dashboards/*.php
-- Target databases: igangaschoolofl_staffs_db, igangaschoolofl_students_db
-- Run: mysql -u root -p < this_file.sql
-- ===============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- Helper: drop a foreign key constraint if it exists (for idempotent re-runs)
DROP PROCEDURE IF EXISTS `drop_fk_if_exists`;
DELIMITER $$
CREATE PROCEDURE `drop_fk_if_exists`(p_schema VARCHAR(200), p_table VARCHAR(200), p_fk VARCHAR(200))
BEGIN
    DECLARE n INT;
    SET n = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = p_schema AND TABLE_NAME = p_table
             AND CONSTRAINT_NAME = p_fk AND CONSTRAINT_TYPE = 'FOREIGN KEY');
    IF n > 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', p_schema, '`.`', p_table, '` DROP FOREIGN KEY `', p_fk, '`');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

-- ===============================================================
-- STAFFS DATABASE TABLES
-- ===============================================================
USE `igangaschoolofl_staffs_db`;

-- -------------------------------------------------------------
-- 1. courses (referenced by senior-lecturers.php, lecturers.php)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `courses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `course_code` varchar(50) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `credits` int DEFAULT 0,
  `level` varchar(50) DEFAULT NULL,
  `department` varchar(200) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_code` (`course_code`),
  KEY `idx_courses_department` (`department`),
  KEY `idx_courses_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 2. assessments (referenced by senior-lecturers.php)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `assessments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `course_id` int DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `assessment_type` enum('Quiz','Test','Assignment','Midterm','Practical','Final Exam','Other') DEFAULT 'Other',
  `total_marks` decimal(10,2) DEFAULT 0.00,
  `weight` decimal(5,2) DEFAULT 0.00,
  `due_date` date DEFAULT NULL,
  `instructions` text,
  `status` enum('Draft','Published','Closed') DEFAULT 'Draft',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_assessments_course` (`course_id`),
  KEY `idx_assessments_creator` (`created_by`),
  KEY `idx_assessments_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 3. teaching_resources (referenced by senior-lecturers.php)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `teaching_resources` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `file_path` varchar(500) DEFAULT NULL,
  `file_type` enum('Document','Video','Audio','Image','Link','Other') DEFAULT 'Document',
  `resource_type` enum('Lecture Note','Syllabus','Reading Material','Assignment','Answer Key','Reference','Other') DEFAULT 'Other',
  `course_id` int DEFAULT NULL,
  `uploaded_by` int DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tr_course` (`course_id`),
  KEY `idx_tr_uploader` (`uploaded_by`),
  KEY `idx_tr_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 4. vehicles (referenced by drivers.php)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `vehicles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `vehicle_name` varchar(200) NOT NULL,
  `license_plate` varchar(50) NOT NULL,
  `vehicle_type` enum('Bus','Minibus','Van','Car','Ambulance','Truck','Other') DEFAULT 'Car',
  `fuel_type` enum('Petrol','Diesel','Electric','Hybrid') DEFAULT 'Diesel',
  `capacity` int DEFAULT 0,
  `manufacturer` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `year` year DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `insurance_expiry` date DEFAULT NULL,
  `last_service_date` date DEFAULT NULL,
  `next_service_date` date DEFAULT NULL,
  `status` enum('Active','Maintenance','Out of Service','Retired') DEFAULT 'Active',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `license_plate` (`license_plate`),
  KEY `idx_vehicles_status` (`status`),
  KEY `idx_vehicles_type` (`vehicle_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 5. trip_logs (referenced by drivers.php)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `trip_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `trip_number` varchar(50) NOT NULL,
  `vehicle_id` int DEFAULT NULL,
  `driver_id` int DEFAULT NULL,
  `trip_date` date NOT NULL,
  `departure_time` time DEFAULT NULL,
  `return_time` time DEFAULT NULL,
  `origin` varchar(255) DEFAULT NULL,
  `destination` varchar(255) NOT NULL,
  `passengers_count` int DEFAULT 0,
  `purpose` text,
  `distance_km` decimal(10,2) DEFAULT NULL,
  `fuel_used` decimal(10,2) DEFAULT NULL,
  `status` enum('Scheduled','Ongoing','Completed','Cancelled') DEFAULT 'Scheduled',
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `trip_number` (`trip_number`),
  KEY `idx_trip_vehicle` (`vehicle_id`),
  KEY `idx_trip_driver` (`driver_id`),
  KEY `idx_trip_date` (`trip_date`),
  KEY `idx_trip_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 6. route_schedules (referenced by drivers.php)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `route_schedules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `route_name` varchar(200) NOT NULL,
  `vehicle_id` int DEFAULT NULL,
  `driver_id` int DEFAULT NULL,
  `origin` varchar(255) DEFAULT NULL,
  `destination` varchar(255) NOT NULL,
  `departure_time` time NOT NULL,
  `arrival_time` time DEFAULT NULL,
  `frequency` enum('Daily','Weekdays','Weekends','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday','Custom') DEFAULT 'Daily',
  `description` text,
  `status` enum('Active','Inactive','Suspended') DEFAULT 'Active',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rs_vehicle` (`vehicle_id`),
  KEY `idx_rs_driver` (`driver_id`),
  KEY `idx_rs_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 7. store_categories (referenced by storekeeper.php)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `store_categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_name` varchar(200) NOT NULL,
  `description` text,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_name` (`category_name`),
  KEY `idx_sc_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 8. store_inventory (referenced by storekeeper.php)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `store_inventory` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_name` varchar(255) NOT NULL,
  `description` text,
  `category_id` int DEFAULT NULL,
  `quantity` decimal(15,2) DEFAULT 0.00,
  `unit` varchar(50) DEFAULT 'pcs',
  `reorder_level` decimal(15,2) DEFAULT 0.00,
  `unit_cost` decimal(15,2) DEFAULT NULL,
  `total_value` decimal(15,2) GENERATED ALWAYS AS ((quantity * unit_cost)) STORED,
  `location` varchar(200) DEFAULT NULL,
  `supplier` varchar(200) DEFAULT NULL,
  `sku` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive','discontinued') DEFAULT 'active',
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_si_category` (`category_id`),
  KEY `idx_si_status` (`status`),
  KEY `idx_si_reorder` (`reorder_level`),
  KEY `idx_si_name` (`item_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 9. store_inventory_transactions (referenced by storekeeper.php)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `store_inventory_transactions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `item_id` int NOT NULL,
  `transaction_type` enum('stock_in','stock_out','adjustment','transfer','return','request_fulfilled','order_received','damage','expired') NOT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `quantity_before` decimal(15,2) DEFAULT NULL,
  `quantity_after` decimal(15,2) DEFAULT NULL,
  `unit_cost` decimal(15,2) DEFAULT NULL,
  `total_cost` decimal(15,2) DEFAULT NULL,
  `reason` text,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sit_item` (`item_id`),
  KEY `idx_sit_type` (`transaction_type`),
  KEY `idx_sit_created` (`created_at`),
  KEY `idx_sit_reference` (`reference_type`,`reference_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 10. store_request_items (referenced by storekeeper.php)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `store_request_items` (
  `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id` int UNSIGNED NOT NULL,
  `item_id` int NOT NULL,
  `quantity_requested` decimal(15,2) NOT NULL,
  `quantity_fulfilled` decimal(15,2) DEFAULT 0.00,
  `unit` varchar(50) DEFAULT 'pcs',
  `notes` text,
  `status` enum('pending','fulfilled','partially_fulfilled') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sri_request` (`request_id`),
  KEY `idx_sri_item` (`item_id`),
  KEY `idx_sri_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 11. store_order_items (referenced by storekeeper.php)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `store_order_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `item_id` int NOT NULL,
  `quantity_ordered` decimal(15,2) NOT NULL,
  `quantity_received` decimal(15,2) DEFAULT 0.00,
  `unit_price` decimal(15,2) DEFAULT 0.00,
  `total_price` decimal(15,2) GENERATED ALWAYS AS ((quantity_ordered * unit_price)) STORED,
  `status` enum('pending','received','partially_received','cancelled') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_soi_order` (`order_id`),
  KEY `idx_soi_item` (`item_id`),
  KEY `idx_soi_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 12. hostel_maintenance_requests (referenced by wardens.php)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `hostel_maintenance_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `request_number` varchar(50) NOT NULL,
  `hostel_room_id` int DEFAULT NULL,
  `hostel_name` varchar(200) DEFAULT NULL,
  `room_number` varchar(50) DEFAULT NULL,
  `description` text NOT NULL,
  `priority` enum('Low','Medium','High','Critical') DEFAULT 'Medium',
  `requested_by` int DEFAULT NULL,
  `assigned_to` int DEFAULT NULL,
  `estimated_cost` decimal(15,2) DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `status` enum('Pending','In Progress','Completed','Closed','Cancelled') DEFAULT 'Pending',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `request_number` (`request_number`),
  KEY `idx_hmr_room` (`hostel_room_id`),
  KEY `idx_hmr_status` (`status`),
  KEY `idx_hmr_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 13. hostel_inspections (referenced by wardens.php)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `hostel_inspections` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hostel_room_id` int DEFAULT NULL,
  `hostel_name` varchar(200) DEFAULT NULL,
  `room_number` varchar(50) DEFAULT NULL,
  `inspection_date` date NOT NULL,
  `inspector_name` varchar(200) DEFAULT NULL,
  `inspector_id` int DEFAULT NULL,
  `cleanliness_rating` int DEFAULT NULL COMMENT '1-5',
  `safety_rating` int DEFAULT NULL COMMENT '1-5',
  `maintenance_rating` int DEFAULT NULL COMMENT '1-5',
  `overall_rating` decimal(3,1) DEFAULT NULL,
  `findings` text,
  `recommendations` text,
  `follow_up_date` date DEFAULT NULL,
  `status` enum('Scheduled','Completed','Cancelled','Rescheduled') DEFAULT 'Scheduled',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_hi_room` (`hostel_room_id`),
  KEY `idx_hi_date` (`inspection_date`),
  KEY `idx_hi_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 14. student_activities (referenced by wardens.php)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `student_activities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `activity_type` enum('Sports','Club','Community Service','Academic','Cultural','Religious','Entertainment','Other') DEFAULT 'Other',
  `activity_name` varchar(255) NOT NULL,
  `description` text,
  `activity_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `supervisor` varchar(200) DEFAULT NULL,
  `max_participants` int DEFAULT NULL,
  `status` enum('Planned','Ongoing','Completed','Cancelled') DEFAULT 'Planned',
  `notes` text,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_sa_student` (`student_id`),
  KEY `idx_sa_date` (`activity_date`),
  KEY `idx_sa_type` (`activity_type`),
  KEY `idx_sa_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 15. library_acquisitions (referenced by school-librarian.php)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `library_acquisitions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `isbn` varchar(50) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `publication_year` year DEFAULT NULL,
  `acquisition_type` enum('Purchase','Donation','Exchange','Subscription','Other') DEFAULT 'Purchase',
  `quantity` int DEFAULT 1,
  `unit_cost` decimal(15,2) DEFAULT NULL,
  `total_cost` decimal(15,2) DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `acquisition_date` date NOT NULL,
  `received_date` date DEFAULT NULL,
  `shelf_location` varchar(100) DEFAULT NULL,
  `status` enum('Ordered','Received','Processed','Rejected') DEFAULT 'Ordered',
  `acquired_by` int DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_la_date` (`acquisition_date`),
  KEY `idx_la_type` (`acquisition_type`),
  KEY `idx_la_status` (`status`),
  KEY `idx_la_isbn` (`isbn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 16. applications (referenced by lecturers.php, senior-lecturers.php,
--     from $studentsConn — created in staffs_db for redundancy)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `applications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `applicant_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `program` varchar(200) DEFAULT NULL,
  `intake` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `application_type` enum('Admission','Transfer','Readmission','Scholarship','Other') DEFAULT 'Admission',
  `status` enum('Pending','Reviewed','Accepted','Rejected','Waitlisted','Enrolled') DEFAULT 'Pending',
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_by` int DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_app_student` (`student_id`),
  KEY `idx_app_status` (`status`),
  KEY `idx_app_program` (`program`),
  KEY `idx_app_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===============================================================
-- STUDENTS DATABASE TABLES
-- ===============================================================
USE `igangaschoolofl_students_db`;

-- -------------------------------------------------------------
-- 1. applications (referenced by lecturers.php line 35 via $studentsConn)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `applications` (
  `id` int NOT NULL AUTO_INCREMENT,
  `student_id` int DEFAULT NULL,
  `applicant_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `program` varchar(200) DEFAULT NULL,
  `intake` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `application_type` enum('Admission','Transfer','Readmission','Scholarship','Other') DEFAULT 'Admission',
  `status` enum('Pending','Reviewed','Accepted','Rejected','Waitlisted','Enrolled') DEFAULT 'Pending',
  `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `reviewed_by` int DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_app_student` (`student_id`),
  KEY `idx_app_status` (`status`),
  KEY `idx_app_program` (`program`),
  KEY `idx_app_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 2. courses (referenced by senior-lecturers.php via $studentsConn)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `courses` (
  `id` int NOT NULL AUTO_INCREMENT,
  `course_code` varchar(50) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `credits` int DEFAULT 0,
  `level` varchar(50) DEFAULT NULL,
  `department` varchar(200) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_code` (`course_code`),
  KEY `idx_courses_department` (`department`),
  KEY `idx_courses_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------------
-- 3. library_acquisitions (referenced by school-librarian.php via
--    dynamic students DB name)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `library_acquisitions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `isbn` varchar(50) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `publication_year` year DEFAULT NULL,
  `acquisition_type` enum('Purchase','Donation','Exchange','Subscription','Other') DEFAULT 'Purchase',
  `quantity` int DEFAULT 1,
  `unit_cost` decimal(15,2) DEFAULT NULL,
  `total_cost` decimal(15,2) DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `acquisition_date` date NOT NULL,
  `received_date` date DEFAULT NULL,
  `shelf_location` varchar(100) DEFAULT NULL,
  `status` enum('Ordered','Received','Processed','Rejected') DEFAULT 'Ordered',
  `acquired_by` int DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_la_date` (`acquisition_date`),
  KEY `idx_la_type` (`acquisition_type`),
  KEY `idx_la_status` (`status`),
  KEY `idx_la_isbn` (`isbn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===============================================================
-- ENSURE PARENT TABLES EXIST BEFORE ADDING FKs
-- (these may already exist from the main schema import)
-- ===============================================================
USE `igangaschoolofl_staffs_db`;

CREATE TABLE IF NOT EXISTS `store_requests` (
  `id` int UNSIGNED NOT NULL,
  `request_number` varchar(60) NOT NULL,
  `requested_by` int UNSIGNED DEFAULT NULL,
  `department` varchar(80) DEFAULT NULL,
  `items` text,
  `urgency` varchar(20) NOT NULL DEFAULT 'medium',
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `forwarded_to` int UNSIGNED DEFAULT NULL,
  `approval_request_id` int UNSIGNED DEFAULT NULL,
  `approved_by` int UNSIGNED DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `store_orders` (
  `id` int NOT NULL,
  `order_number` varchar(50) NOT NULL,
  `supplier` varchar(200) DEFAULT 'Internal Requisition',
  `notes` text,
  `total_amount` decimal(15,2) DEFAULT '0.00',
  `status` varchar(50) DEFAULT 'pending_approval',
  `requested_by` int DEFAULT NULL,
  `approved_by` int DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `received_by` int DEFAULT NULL,
  `received_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===============================================================
-- FOREIGN KEY CONSTRAINTS (drop-then-create for idempotent re-runs)
-- ===============================================================
USE `igangaschoolofl_staffs_db`;

-- assessments FK
CALL drop_fk_if_exists('igangaschoolofl_staffs_db', 'assessments', 'fk_assessments_course');
ALTER TABLE `assessments` ADD CONSTRAINT `fk_assessments_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
CALL drop_fk_if_exists('igangaschoolofl_staffs_db', 'assessments', 'fk_assessments_creator');
ALTER TABLE `assessments` ADD CONSTRAINT `fk_assessments_creator` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- teaching_resources FK
CALL drop_fk_if_exists('igangaschoolofl_staffs_db', 'teaching_resources', 'fk_tr_course');
ALTER TABLE `teaching_resources` ADD CONSTRAINT `fk_tr_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
CALL drop_fk_if_exists('igangaschoolofl_staffs_db', 'teaching_resources', 'fk_tr_uploader');
ALTER TABLE `teaching_resources` ADD CONSTRAINT `fk_tr_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- trip_logs FK
CALL drop_fk_if_exists('igangaschoolofl_staffs_db', 'trip_logs', 'fk_trip_vehicle');
ALTER TABLE `trip_logs` ADD CONSTRAINT `fk_trip_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
CALL drop_fk_if_exists('igangaschoolofl_staffs_db', 'trip_logs', 'fk_trip_driver');
ALTER TABLE `trip_logs` ADD CONSTRAINT `fk_trip_driver` FOREIGN KEY (`driver_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- route_schedules FK
CALL drop_fk_if_exists('igangaschoolofl_staffs_db', 'route_schedules', 'fk_rs_vehicle');
ALTER TABLE `route_schedules` ADD CONSTRAINT `fk_rs_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
CALL drop_fk_if_exists('igangaschoolofl_staffs_db', 'route_schedules', 'fk_rs_driver');
ALTER TABLE `route_schedules` ADD CONSTRAINT `fk_rs_driver` FOREIGN KEY (`driver_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- store_inventory FK
CALL drop_fk_if_exists('igangaschoolofl_staffs_db', 'store_inventory', 'fk_si_category');
ALTER TABLE `store_inventory` ADD CONSTRAINT `fk_si_category` FOREIGN KEY (`category_id`) REFERENCES `store_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- store_inventory_transactions FK
CALL drop_fk_if_exists('igangaschoolofl_staffs_db', 'store_inventory_transactions', 'fk_sit_item');
ALTER TABLE `store_inventory_transactions` ADD CONSTRAINT `fk_sit_item` FOREIGN KEY (`item_id`) REFERENCES `store_inventory` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- store_request_items FK
CALL drop_fk_if_exists('igangaschoolofl_staffs_db', 'store_request_items', 'fk_sri_request');
ALTER TABLE `store_request_items` ADD CONSTRAINT `fk_sri_request` FOREIGN KEY (`request_id`) REFERENCES `store_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
CALL drop_fk_if_exists('igangaschoolofl_staffs_db', 'store_request_items', 'fk_sri_item');
ALTER TABLE `store_request_items` ADD CONSTRAINT `fk_sri_item` FOREIGN KEY (`item_id`) REFERENCES `store_inventory` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- store_order_items FK
CALL drop_fk_if_exists('igangaschoolofl_staffs_db', 'store_order_items', 'fk_soi_order');
ALTER TABLE `store_order_items` ADD CONSTRAINT `fk_soi_order` FOREIGN KEY (`order_id`) REFERENCES `store_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;
CALL drop_fk_if_exists('igangaschoolofl_staffs_db', 'store_order_items', 'fk_soi_item');
ALTER TABLE `store_order_items` ADD CONSTRAINT `fk_soi_item` FOREIGN KEY (`item_id`) REFERENCES `store_inventory` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

-- hostel_maintenance_requests FK
CALL drop_fk_if_exists('igangaschoolofl_staffs_db', 'hostel_maintenance_requests', 'fk_hmr_requested');
ALTER TABLE `hostel_maintenance_requests` ADD CONSTRAINT `fk_hmr_requested` FOREIGN KEY (`requested_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
CALL drop_fk_if_exists('igangaschoolofl_staffs_db', 'hostel_maintenance_requests', 'fk_hmr_assigned');
ALTER TABLE `hostel_maintenance_requests` ADD CONSTRAINT `fk_hmr_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- student_activities FK
CALL drop_fk_if_exists('igangaschoolofl_staffs_db', 'student_activities', 'fk_sa_created');
ALTER TABLE `student_activities` ADD CONSTRAINT `fk_sa_created` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- library_acquisitions FK (staffs_db)
CALL drop_fk_if_exists('igangaschoolofl_staffs_db', 'library_acquisitions', 'fk_la_acquired');
ALTER TABLE `library_acquisitions` ADD CONSTRAINT `fk_la_acquired` FOREIGN KEY (`acquired_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- Cleanup helper procedure
DROP PROCEDURE IF EXISTS `drop_fk_if_exists`;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
