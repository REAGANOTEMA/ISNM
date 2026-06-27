-- ==============================================================
-- ISNM (Iganga School of Nursing and Midwifery) ERP System
-- Staff Database Schema and Seed Data
-- ==============================================================

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';
START TRANSACTION;
SET time_zone = '+00:00';

--
-- Database: igangaschoolofl_staffs_db
--
DROP DATABASE IF EXISTS `igangaschoolofl_staffs_db`;
CREATE DATABASE IF NOT EXISTS `igangaschoolofl_staffs_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `igangaschoolofl_staffs_db`;
CREATE TABLE `academic_approvals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reference_type` varchar(50) NOT NULL COMMENT 'result|transcript|certificate|graduation',
  `reference_id` int(11) NOT NULL,
  `approval_level` varchar(50) NOT NULL COMMENT 'lecturer|hod|director_academics|registrar|principal|director_general',
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_aa_ref` (`reference_type`,`reference_id`),
  KEY `idx_aa_level` (`approval_level`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_approvals`
--

LOCK TABLES `academic_approvals` WRITE;
/*!40000 ALTER TABLE `academic_approvals` DISABLE KEYS */;
/*!40000 ALTER TABLE `academic_approvals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `academic_audit_logs`
--

DROP TABLE IF EXISTS `academic_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `academic_audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `old_values` longtext DEFAULT NULL,
  `new_values` longtext DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_aal_action` (`action`),
  KEY `idx_aal_entity` (`entity_type`,`entity_id`),
  KEY `idx_aal_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_audit_logs`
--

LOCK TABLES `academic_audit_logs` WRITE;
/*!40000 ALTER TABLE `academic_audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `academic_audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `academic_calendar`
--

DROP TABLE IF EXISTS `academic_calendar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `academic_calendar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `calendar_id` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(30) DEFAULT NULL,
  `semester_start_date` date DEFAULT NULL,
  `semester_end_date` date DEFAULT NULL,
  `exam_start_date` date DEFAULT NULL,
  `exam_end_date` date DEFAULT NULL,
  `result_publication_date` date DEFAULT NULL,
  `registration_deadline` date DEFAULT NULL,
  `status` enum('Upcoming','Ongoing','Completed') DEFAULT 'Upcoming',
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `calendar_id` (`calendar_id`),
  KEY `academic_year` (`academic_year`),
  KEY `semester` (`semester`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_calendar`
--

LOCK TABLES `academic_calendar` WRITE;
/*!40000 ALTER TABLE `academic_calendar` DISABLE KEYS */;
INSERT INTO `academic_calendar` (`id`, `calendar_id`, `academic_year`, `semester`, `semester_start_date`, `semester_end_date`, `exam_start_date`, `exam_end_date`, `result_publication_date`, `registration_deadline`, `status`, `created_by`, `created_at`, `updated_at`) VALUES (1,'CAL-2025-S1-001','2025/2026','Semester 1','2025-09-01','2026-01-31','2025-12-01','2025-12-20',NULL,NULL,'Ongoing',1,'2026-06-18 21:12:21',NULL);
/*!40000 ALTER TABLE `academic_calendar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `academic_course_catalog`
--

DROP TABLE IF EXISTS `academic_course_catalog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `academic_course_catalog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_code` varchar(20) DEFAULT NULL,
  `course_name` varchar(200) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `credit_hours` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_code` (`course_code`),
  KEY `department` (`department`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_course_catalog`
--

LOCK TABLES `academic_course_catalog` WRITE;
/*!40000 ALTER TABLE `academic_course_catalog` DISABLE KEYS */;
INSERT INTO `academic_course_catalog` (`id`, `course_code`, `course_name`, `department`, `credit_hours`, `description`, `status`, `created_at`, `updated_at`) VALUES (1,'NUR101','Introduction to Nursing','Nursing',0,NULL,'Active','2026-06-18 21:12:21',NULL),(2,'NUR102','Anatomy and Physiology','Nursing',0,NULL,'Active','2026-06-18 21:12:21',NULL),(3,'NUR201','Medical-Surgical Nursing','Nursing',0,NULL,'Active','2026-06-18 21:12:21',NULL),(4,'MID101','Introduction to Midwifery','Midwifery',0,NULL,'Active','2026-06-18 21:12:21',NULL),(5,'MID102','Reproductive Health','Midwifery',0,NULL,'Active','2026-06-18 21:12:21',NULL),(6,'COM101','Communication Skills','General Studies',0,NULL,'Active','2026-06-18 21:12:21',NULL),(7,'BIO101','Biology','General Studies',0,NULL,'Active','2026-06-18 21:12:21',NULL),(8,'CHEM101','Chemistry','General Studies',0,NULL,'Active','2026-06-18 21:12:21',NULL),(9,'PHY101','Physics','General Studies',0,NULL,'Active','2026-06-18 21:12:21',NULL),(10,'ENG101','English','General Studies',0,NULL,'Active','2026-06-18 21:12:21',NULL),(11,'MATH101','Mathematics','General Studies',0,NULL,'Active','2026-06-18 21:12:21',NULL),(12,'PHARM101','Pharmacology','Nursing',0,NULL,'Active','2026-06-18 21:12:21',NULL);
/*!40000 ALTER TABLE `academic_course_catalog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `academic_curriculum_development`
--

DROP TABLE IF EXISTS `academic_curriculum_development`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `academic_curriculum_development` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `program_id` int(10) unsigned DEFAULT NULL,
  `course_code` varchar(30) DEFAULT NULL,
  `course_name` varchar(200) DEFAULT NULL,
  `credit_hours` int(11) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_curriculum_development`
--

LOCK TABLES `academic_curriculum_development` WRITE;
/*!40000 ALTER TABLE `academic_curriculum_development` DISABLE KEYS */;
/*!40000 ALTER TABLE `academic_curriculum_development` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `academic_programs`
--

DROP TABLE IF EXISTS `academic_programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `academic_programs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_code` varchar(50) NOT NULL,
  `program_name` varchar(255) NOT NULL,
  `program_type` enum('Certificate','Diploma','Degree','Other') DEFAULT 'Certificate',
  `duration_years` decimal(3,1) DEFAULT 3.0,
  `department` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `program_code` (`program_code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_programs`
--

LOCK TABLES `academic_programs` WRITE;
/*!40000 ALTER TABLE `academic_programs` DISABLE KEYS */;
INSERT INTO `academic_programs` (`id`, `program_code`, `program_name`, `program_type`, `duration_years`, `department`, `status`, `created_at`) VALUES (1,'CERT-NUR','Certificate in Nursing','Certificate',3.0,'Nursing','Active','2026-06-22 19:10:24'),(2,'CERT-MID','Certificate in Midwifery','Certificate',3.0,'Midwifery','Active','2026-06-22 19:10:24'),(3,'DIP-NUR','Diploma in Nursing','Diploma',3.0,'Nursing','Active','2026-06-22 19:10:24'),(4,'DIP-MID','Diploma in Midwifery','Diploma',3.0,'Midwifery','Active','2026-06-22 19:10:24');
/*!40000 ALTER TABLE `academic_programs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admission_activity_logs`
--

DROP TABLE IF EXISTS `admission_activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admission_activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(50) DEFAULT 'admissions',
  `record_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_aal_user` (`user_id`),
  KEY `idx_aal_module` (`module`),
  KEY `idx_aal_action` (`action`),
  KEY `idx_aal_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admission_activity_logs`
--

LOCK TABLES `admission_activity_logs` WRITE;
/*!40000 ALTER TABLE `admission_activity_logs` DISABLE KEYS */;
INSERT INTO `admission_activity_logs` (`id`, `user_id`, `action`, `module`, `record_id`, `description`, `created_at`) VALUES (1,24,'Create Student','students',0,'Created student: Otema Reagan (u004/cm/076)','2026-06-22 20:01:24');
/*!40000 ALTER TABLE `admission_activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admission_notifications`
--

DROP TABLE IF EXISTS `admission_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admission_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `applicant_id` int(11) DEFAULT NULL,
  `recipient_type` varchar(50) DEFAULT 'applicant',
  `recipient_id` int(11) DEFAULT NULL,
  `title` varchar(300) NOT NULL,
  `message` text NOT NULL,
  `channel` varchar(50) DEFAULT 'portal',
  `sent_by` int(11) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admission_notifications`
--

LOCK TABLES `admission_notifications` WRITE;
/*!40000 ALTER TABLE `admission_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `admission_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admission_requirements`
--

DROP TABLE IF EXISTS `admission_requirements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admission_requirements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requirement_name` varchar(200) NOT NULL,
  `type` varchar(50) DEFAULT 'Document',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `is_mandatory` tinyint(1) DEFAULT 1,
  `display_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_req_name` (`requirement_name`),
  KEY `idx_req_active` (`is_active`),
  KEY `idx_req_order` (`display_order`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admission_requirements`
--

LOCK TABLES `admission_requirements` WRITE;
/*!40000 ALTER TABLE `admission_requirements` DISABLE KEYS */;
INSERT INTO `admission_requirements` (`id`, `requirement_name`, `type`, `description`, `is_active`, `is_mandatory`, `display_order`, `created_at`) VALUES (1,'Surgical Gloves','Document',NULL,1,1,1,'2026-06-22 18:07:53'),(2,'Examination Gloves','Document',NULL,1,1,2,'2026-06-22 18:07:53'),(3,'Photocopying Ream','Document',NULL,1,1,3,'2026-06-22 18:07:53'),(4,'Ruled Paper Reams','Document',NULL,1,1,4,'2026-06-22 18:07:53'),(5,'Omo','Document',NULL,1,1,5,'2026-06-22 18:07:53'),(6,'Toilet Papers','Document',NULL,1,1,6,'2026-06-22 18:07:53'),(7,'Compound Brooms','Document',NULL,1,1,7,'2026-06-22 18:07:53'),(8,'Soft Brooms','Document',NULL,1,1,8,'2026-06-22 18:07:53'),(9,'Rake','Document',NULL,1,1,9,'2026-06-22 18:07:53'),(10,'Cobweb Brush','Document',NULL,1,1,10,'2026-06-22 18:07:53'),(11,'Scrubbing Brush','Document',NULL,1,1,11,'2026-06-22 18:07:53'),(12,'Squeezer','Document',NULL,1,1,12,'2026-06-22 18:07:53'),(13,'Toilet Brush','Document',NULL,1,1,13,'2026-06-22 18:07:53'),(14,'JIK','Document',NULL,1,1,14,'2026-06-22 18:07:53'),(15,'Vim','Document',NULL,1,1,15,'2026-06-22 18:07:53'),(16,'Mops','Document',NULL,1,1,16,'2026-06-22 18:07:53'),(17,'Sanitizer','Document',NULL,1,1,17,'2026-06-22 18:07:53'),(18,'Liquid Soap','Document',NULL,1,1,18,'2026-06-22 18:07:53'),(19,'Face Masks','Document',NULL,1,1,19,'2026-06-22 18:07:53'),(20,'Heavy Duty Gloves','Document',NULL,1,1,20,'2026-06-22 18:07:53');
/*!40000 ALTER TABLE `admission_requirements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `alert_recipients`
--

DROP TABLE IF EXISTS `alert_recipients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `alert_recipients` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `alert_id` int(10) unsigned DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alert_recipients`
--

LOCK TABLES `alert_recipients` WRITE;
/*!40000 ALTER TABLE `alert_recipients` DISABLE KEYS */;
/*!40000 ALTER TABLE `alert_recipients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `alerts`
--

DROP TABLE IF EXISTS `alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `alerts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `type` varchar(30) DEFAULT 'info',
  `status` varchar(30) DEFAULT 'active',
  `link` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `resolved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `alerts`
--

LOCK TABLES `alerts` WRITE;
/*!40000 ALTER TABLE `alerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `body` text DEFAULT NULL,
  `target_audience` varchar(60) DEFAULT 'All',
  `priority` varchar(20) DEFAULT 'Normal',
  `posted_by` int(10) unsigned DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ann_active` (`is_active`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcements`
--

LOCK TABLES `announcements` WRITE;
/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
INSERT INTO `announcements` (`id`, `title`, `body`, `target_audience`, `priority`, `posted_by`, `is_active`, `created_at`) VALUES (1,'Welcome to New Academic Year','We welcome all staff and students to the new academic year 2026. Let us work together for excellence.','All','High',1,1,'2026-06-20 06:58:56'),(2,'Staff Meeting Reminder','There will be a general staff meeting on Friday at 10:00 AM in the main hall.','Staff','Normal',1,1,'2026-06-20 06:58:56'),(3,'Maintenance Notice','The library will be closed for maintenance on Saturday.','All','Low',1,1,'2026-06-20 06:58:56');
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `applicant_messages`
--

DROP TABLE IF EXISTS `applicant_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `applicant_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `applicant_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `recipient_type` varchar(50) DEFAULT 'applicant',
  `subject` varchar(300) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `applicant_messages`
--

LOCK TABLES `applicant_messages` WRITE;
/*!40000 ALTER TABLE `applicant_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `applicant_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `applicant_requirement_status`
--

DROP TABLE IF EXISTS `applicant_requirement_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `applicant_requirement_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `applicant_id` int(11) NOT NULL,
  `requirement_id` int(11) NOT NULL,
  `status` enum('Not Submitted','Submitted','Verified','Rejected','Missing') DEFAULT 'Not Submitted',
  `submitted_by` int(11) DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `rejected_by` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_app_req` (`applicant_id`,`requirement_id`),
  KEY `idx_ars_applicant` (`applicant_id`),
  KEY `idx_ars_requirement` (`requirement_id`),
  KEY `idx_ars_status` (`status`),
  CONSTRAINT `applicant_requirement_status_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `applicant_requirement_status_ibfk_2` FOREIGN KEY (`requirement_id`) REFERENCES `admission_requirements` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `applicant_requirement_status`
--

LOCK TABLES `applicant_requirement_status` WRITE;
/*!40000 ALTER TABLE `applicant_requirement_status` DISABLE KEYS */;
/*!40000 ALTER TABLE `applicant_requirement_status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `applicants`
--

DROP TABLE IF EXISTS `applicants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `applicants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) NOT NULL,
  `date_of_birth` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT 'Other',
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `guardian_name` varchar(255) DEFAULT NULL,
  `guardian_phone` varchar(20) DEFAULT NULL,
  `guardian_relationship` varchar(100) DEFAULT NULL,
  `application_number` varchar(50) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `intake` varchar(100) DEFAULT NULL,
  `admission_date` date DEFAULT NULL,
  `status` enum('New Applicant','Under Review','Approved','Rejected','Registered') DEFAULT 'New Applicant',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_app_number` (`application_number`),
  KEY `idx_applicant_name` (`full_name`),
  KEY `idx_applicant_phone` (`phone`),
  KEY `idx_applicant_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `applicants`
--

LOCK TABLES `applicants` WRITE;
/*!40000 ALTER TABLE `applicants` DISABLE KEYS */;
/*!40000 ALTER TABLE `applicants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `appraisals`
--

DROP TABLE IF EXISTS `appraisals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `appraisals` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(10) unsigned NOT NULL,
  `appraisal_period` varchar(100) DEFAULT NULL,
  `rating` decimal(3,1) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `reviewer_id` int(10) unsigned DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appraisals`
--

LOCK TABLES `appraisals` WRITE;
/*!40000 ALTER TABLE `appraisals` DISABLE KEYS */;
/*!40000 ALTER TABLE `appraisals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `approval_actions`
--

DROP TABLE IF EXISTS `approval_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `approval_actions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `request_id` int(10) unsigned NOT NULL,
  `stage_id` int(10) unsigned DEFAULT NULL,
  `action_by` int(10) unsigned DEFAULT NULL,
  `action_type` varchar(30) NOT NULL,
  `comments` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `decision` varchar(30) DEFAULT NULL,
  `previous_stage_order` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_request` (`request_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approval_actions`
--

LOCK TABLES `approval_actions` WRITE;
/*!40000 ALTER TABLE `approval_actions` DISABLE KEYS */;
INSERT INTO `approval_actions` (`id`, `request_id`, `stage_id`, `action_by`, `action_type`, `comments`, `notes`, `decision`, `previous_stage_order`, `created_at`) VALUES (1,3,2,1,'reject','yes',NULL,'Rejected',2,'2026-06-24 08:32:00');
/*!40000 ALTER TABLE `approval_actions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `approval_requests`
--

DROP TABLE IF EXISTS `approval_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `approval_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `workflow_id` int(10) unsigned NOT NULL,
  `request_number` varchar(60) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` varchar(20) DEFAULT 'Medium',
  `requester_id` int(10) unsigned DEFAULT NULL,
  `requester_name` varchar(120) DEFAULT NULL,
  `requester_role` varchar(80) DEFAULT NULL,
  `current_stage_id` int(10) unsigned DEFAULT NULL,
  `current_stage_order` int(10) unsigned DEFAULT 1,
  `status` varchar(30) DEFAULT 'Active',
  `reference_type` varchar(60) DEFAULT NULL,
  `reference_id` int(10) unsigned DEFAULT NULL,
  `reference_url` varchar(255) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `final_approval_by` int(10) unsigned DEFAULT NULL,
  `final_approval_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_ref` (`reference_type`,`reference_id`),
  KEY `idx_requester` (`requester_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approval_requests`
--

LOCK TABLES `approval_requests` WRITE;
/*!40000 ALTER TABLE `approval_requests` DISABLE KEYS */;
INSERT INTO `approval_requests` (`id`, `workflow_id`, `request_number`, `title`, `description`, `priority`, `requester_id`, `requester_name`, `requester_role`, `current_stage_id`, `current_stage_order`, `status`, `reference_type`, `reference_id`, `reference_url`, `rejection_reason`, `final_approval_by`, `final_approval_at`, `created_at`, `updated_at`) VALUES (1,1,'REQ-20260620-A73F2B','Laboratory Equipment Restock','Request to restock essential laboratory equipment including microscopes and slides for Nursing dept.','High',2,'Mary Nalwoga','Head of Nursing',2,2,'Active','store_requests',1,NULL,NULL,NULL,NULL,'2026-06-20 05:47:50','2026-06-20 07:47:50'),(2,1,'REQ-20260620-B84C3D','Office Stationery Order','Monthly stationery supplies for administrative offices - paper, pens, folders, ink cartridges.','Medium',3,'James Okello','School Secretary',2,2,'Active','store_requests',2,NULL,NULL,NULL,NULL,'2026-06-20 02:47:50','2026-06-20 07:47:50'),(3,1,'REQ-20260619-C95D4E','Medical Consumables','Urgent restock of gloves, masks, sanitizers and first aid supplies for the sickbay.','Urgent',4,'Sarah Kyomugisha','Matron',2,2,'Rejected','store_requests',3,NULL,'yes',NULL,NULL,'2026-06-19 07:47:50','2026-06-24 08:32:00'),(4,2,'REQ-20260620-D06E5F','New Student: Akello Grace','Registration application for Diploma Nursing program. Submitted by Registrar.','Normal',5,'Peter Okoth','Academic Registrar',4,2,'Active','pending_students',1,NULL,NULL,NULL,NULL,'2026-06-20 04:47:50','2026-06-20 07:47:50'),(5,2,'REQ-20260619-E17F6G','New Student: Bwire John','Registration application for Certificate Midwifery program. All documents verified.','Normal',5,'Peter Okoth','Academic Registrar',4,2,'Active','pending_students',2,NULL,NULL,NULL,NULL,'2026-06-19 07:47:50','2026-06-20 07:47:50'),(6,3,'REQ-20260620-F28G7H','End of Year Examination Schedule','Proposed examination timetable for the June 2026 semester. Requires DG sign-off.','Medium',2,'Mary Nalwoga','Head of Nursing',5,1,'Active',NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-20 01:47:50','2026-06-20 07:47:50');
/*!40000 ALTER TABLE `approval_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `approval_stages`
--

DROP TABLE IF EXISTS `approval_stages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `approval_stages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `workflow_id` int(10) unsigned NOT NULL,
  `stage_name` varchar(120) NOT NULL,
  `stage_order` int(10) unsigned NOT NULL,
  `assigned_role_id` int(10) unsigned DEFAULT NULL,
  `assigned_role_name` varchar(80) DEFAULT NULL,
  `is_final` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_workflow_stage_order` (`workflow_id`,`stage_order`),
  KEY `idx_workflow` (`workflow_id`)
) ENGINE=InnoDB AUTO_INCREMENT=158 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approval_stages`
--

LOCK TABLES `approval_stages` WRITE;
/*!40000 ALTER TABLE `approval_stages` DISABLE KEYS */;
INSERT INTO `approval_stages` (`id`, `workflow_id`, `stage_name`, `stage_order`, `assigned_role_id`, `assigned_role_name`, `is_final`, `created_at`) VALUES (138,125,'Director ICT Review',1,NULL,'Director ICT',0,'2026-06-27 07:17:17'),(139,125,'Director General Final Approval',2,NULL,'Director General',1,'2026-06-27 07:17:17'),(140,122,'Director General Approval',1,NULL,'Director General',1,'2026-06-27 07:17:17'),(141,123,'Director General Approval',1,NULL,'Director General',1,'2026-06-27 07:17:17'),(142,124,'Director General Approval',1,NULL,'Director General',1,'2026-06-27 07:17:17'),(143,126,'Director General Approval',1,NULL,'Director General',1,'2026-06-27 07:17:17'),(144,127,'Director General Approval',1,NULL,'Director General',1,'2026-06-27 07:17:17'),(145,128,'Director General Approval',1,NULL,'Director General',1,'2026-06-27 07:17:17'),(146,129,'Director General Approval',1,NULL,'Director General',1,'2026-06-27 07:17:17'),(147,130,'Director General Approval',1,NULL,'Director General',1,'2026-06-27 07:17:17');
/*!40000 ALTER TABLE `approval_stages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `approval_workflows`
--

DROP TABLE IF EXISTS `approval_workflows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `approval_workflows` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `workflow_name` varchar(120) NOT NULL,
  `category` varchar(60) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_workflow_name` (`workflow_name`)
) ENGINE=InnoDB AUTO_INCREMENT=132 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approval_workflows`
--

LOCK TABLES `approval_workflows` WRITE;
/*!40000 ALTER TABLE `approval_workflows` DISABLE KEYS */;
INSERT INTO `approval_workflows` (`id`, `workflow_name`, `category`, `description`, `is_active`, `created_at`) VALUES (122,'General Department Request','General Administration','Standard approval workflow for general administrative requests requiring Director General sign-off',1,'2026-06-27 07:17:17'),(123,'HR Request','Human Resources','HR-related requests requiring Director General approval',1,'2026-06-27 07:17:17'),(124,'Finance Request','Finance','Financial requests and budget approvals requiring Director General sign-off',1,'2026-06-27 07:17:17'),(125,'ICT Request','ICT','ICT department requests requiring departmental review and Director General approval',1,'2026-06-27 07:17:17'),(126,'Academic Request','Academic','Academic affairs requests requiring Director General approval',1,'2026-06-27 07:17:17'),(127,'Admissions Request','Admissions','Admissions-related requests requiring Director General approval',1,'2026-06-27 07:17:17'),(128,'Library Request','Library','Library resource and service requests requiring Director General approval',1,'2026-06-27 07:17:17'),(129,'Store Requisition','Store & Assets','Store and asset requisitions requiring Director General approval',1,'2026-06-27 07:17:17'),(130,'Student Registration','Academic','Student registration requests requiring Director General approval',1,'2026-06-27 07:17:17');
/*!40000 ALTER TABLE `approval_workflows` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `class_id` int(10) unsigned DEFAULT NULL,
  `subject_id` int(10) unsigned DEFAULT NULL,
  `date` date NOT NULL,
  `status` varchar(30) DEFAULT 'present',
  `remarks` text DEFAULT NULL,
  `marked_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance`
--

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_trail`
--

DROP TABLE IF EXISTS `audit_trail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `audit_trail` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(10) unsigned DEFAULT NULL,
  `action_type` varchar(60) NOT NULL,
  `entity_type` varchar(60) DEFAULT NULL,
  `entity_id` int(10) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_at_staff` (`staff_id`),
  KEY `idx_at_action` (`action_type`),
  KEY `idx_at_entity` (`entity_type`,`entity_id`),
  KEY `idx_at_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_trail`
--

LOCK TABLES `audit_trail` WRITE;
/*!40000 ALTER TABLE `audit_trail` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_trail` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `backup_management`
--

DROP TABLE IF EXISTS `backup_management`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `backup_management` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `backup_name` varchar(200) DEFAULT NULL,
  `backup_type` varchar(50) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `backup_management`
--

LOCK TABLES `backup_management` WRITE;
/*!40000 ALTER TABLE `backup_management` DISABLE KEYS */;
/*!40000 ALTER TABLE `backup_management` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_accounts`
--

DROP TABLE IF EXISTS `bank_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bank_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bank_name` varchar(200) DEFAULT NULL,
  `account_name` varchar(200) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `balance` decimal(15,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `status` varchar(20) DEFAULT 'Active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_accounts`
--

LOCK TABLES `bank_accounts` WRITE;
/*!40000 ALTER TABLE `bank_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `bank_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_bank_reconciliation`
--

DROP TABLE IF EXISTS `bursar_bank_reconciliation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bursar_bank_reconciliation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reconciliation_date` date NOT NULL,
  `bank_balance` decimal(15,2) NOT NULL,
  `book_balance` decimal(15,2) NOT NULL,
  `difference` decimal(15,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'unreconciled',
  `reconciled_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_bank_reconciliation`
--

LOCK TABLES `bursar_bank_reconciliation` WRITE;
/*!40000 ALTER TABLE `bursar_bank_reconciliation` DISABLE KEYS */;
/*!40000 ALTER TABLE `bursar_bank_reconciliation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_cashbook`
--

DROP TABLE IF EXISTS `bursar_cashbook`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bursar_cashbook` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_date` date NOT NULL,
  `transaction_type` varchar(20) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL,
  `category` varchar(50) DEFAULT '',
  `reference` varchar(100) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cashbook_date` (`transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_cashbook`
--

LOCK TABLES `bursar_cashbook` WRITE;
/*!40000 ALTER TABLE `bursar_cashbook` DISABLE KEYS */;
/*!40000 ALTER TABLE `bursar_cashbook` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_chart_of_accounts`
--

DROP TABLE IF EXISTS `bursar_chart_of_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bursar_chart_of_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_code` varchar(20) NOT NULL,
  `account_name` varchar(200) NOT NULL,
  `account_type` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uqx_coa_code` (`account_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_chart_of_accounts`
--

LOCK TABLES `bursar_chart_of_accounts` WRITE;
/*!40000 ALTER TABLE `bursar_chart_of_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `bursar_chart_of_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_discounts`
--

DROP TABLE IF EXISTS `bursar_discounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bursar_discounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fee_account_id` int(11) NOT NULL,
  `discount_type` varchar(20) NOT NULL,
  `discount_value` decimal(15,2) NOT NULL,
  `discount_amount` decimal(15,2) NOT NULL,
  `reason` text DEFAULT NULL,
  `applied_by` int(11) NOT NULL,
  `applied_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_bdiscounts_account` (`fee_account_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_discounts`
--

LOCK TABLES `bursar_discounts` WRITE;
/*!40000 ALTER TABLE `bursar_discounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `bursar_discounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_general_ledger`
--

DROP TABLE IF EXISTS `bursar_general_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bursar_general_ledger` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entry_date` date NOT NULL,
  `account_code` varchar(20) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `debit_amount` decimal(15,2) DEFAULT 0.00,
  `credit_amount` decimal(15,2) DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_gledger_date` (`entry_date`),
  KEY `idx_gledger_account` (`account_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_general_ledger`
--

LOCK TABLES `bursar_general_ledger` WRITE;
/*!40000 ALTER TABLE `bursar_general_ledger` DISABLE KEYS */;
/*!40000 ALTER TABLE `bursar_general_ledger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_payment_verification`
--

DROP TABLE IF EXISTS `bursar_payment_verification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bursar_payment_verification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(50) NOT NULL,
  `fee_account_id` int(11) DEFAULT 0,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `payment_date` date NOT NULL,
  `proof_file` varchar(255) DEFAULT '',
  `status` varchar(20) DEFAULT 'pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_bverif_status` (`status`),
  KEY `idx_bverif_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_payment_verification`
--

LOCK TABLES `bursar_payment_verification` WRITE;
/*!40000 ALTER TABLE `bursar_payment_verification` DISABLE KEYS */;
/*!40000 ALTER TABLE `bursar_payment_verification` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_penalty_config`
--

DROP TABLE IF EXISTS `bursar_penalty_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bursar_penalty_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `penalty_name` varchar(200) NOT NULL,
  `penalty_type` varchar(20) NOT NULL,
  `penalty_value` decimal(15,2) NOT NULL,
  `grace_days` int(11) DEFAULT 0,
  `max_charge` decimal(15,2) DEFAULT 0.00,
  `status` varchar(20) DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_penalty_config`
--

LOCK TABLES `bursar_penalty_config` WRITE;
/*!40000 ALTER TABLE `bursar_penalty_config` DISABLE KEYS */;
/*!40000 ALTER TABLE `bursar_penalty_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_requisition_reviews`
--

DROP TABLE IF EXISTS `bursar_requisition_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bursar_requisition_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requester_id` int(11) NOT NULL,
  `item_description` varchar(500) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT 0.00,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_requisition_reviews`
--

LOCK TABLES `bursar_requisition_reviews` WRITE;
/*!40000 ALTER TABLE `bursar_requisition_reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `bursar_requisition_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_scholarships`
--

DROP TABLE IF EXISTS `bursar_scholarships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bursar_scholarships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `scholarship_name` varchar(200) NOT NULL,
  `scholarship_type` varchar(50) NOT NULL,
  `scholarship_value` decimal(15,2) NOT NULL,
  `description` text DEFAULT NULL,
  `provider` varchar(200) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_scholarships`
--

LOCK TABLES `bursar_scholarships` WRITE;
/*!40000 ALTER TABLE `bursar_scholarships` DISABLE KEYS */;
/*!40000 ALTER TABLE `bursar_scholarships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_sponsorships`
--

DROP TABLE IF EXISTS `bursar_sponsorships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bursar_sponsorships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(50) NOT NULL,
  `sponsor_name` varchar(200) NOT NULL,
  `sponsor_contact` varchar(100) DEFAULT NULL,
  `sponsor_email` varchar(200) DEFAULT NULL,
  `coverage_percent` decimal(5,2) DEFAULT 100.00,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_bsponsorships_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_sponsorships`
--

LOCK TABLES `bursar_sponsorships` WRITE;
/*!40000 ALTER TABLE `bursar_sponsorships` DISABLE KEYS */;
/*!40000 ALTER TABLE `bursar_sponsorships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_tax_filings`
--

DROP TABLE IF EXISTS `bursar_tax_filings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bursar_tax_filings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tax_period_id` int(11) NOT NULL,
  `filing_date` date NOT NULL,
  `tax_type` varchar(50) NOT NULL,
  `total_revenue` decimal(15,2) DEFAULT 0.00,
  `tax_amount` decimal(15,2) DEFAULT 0.00,
  `due_date` date DEFAULT NULL,
  `filing_status` varchar(20) DEFAULT 'filed',
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_taxfiling_period` (`tax_period_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_tax_filings`
--

LOCK TABLES `bursar_tax_filings` WRITE;
/*!40000 ALTER TABLE `bursar_tax_filings` DISABLE KEYS */;
/*!40000 ALTER TABLE `bursar_tax_filings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_tax_periods`
--

DROP TABLE IF EXISTS `bursar_tax_periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bursar_tax_periods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `period_name` varchar(100) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_tax_periods`
--

LOCK TABLES `bursar_tax_periods` WRITE;
/*!40000 ALTER TABLE `bursar_tax_periods` DISABLE KEYS */;
/*!40000 ALTER TABLE `bursar_tax_periods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_vat_reports`
--

DROP TABLE IF EXISTS `bursar_vat_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bursar_vat_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `output_vat` decimal(15,2) DEFAULT 0.00,
  `input_vat` decimal(15,2) DEFAULT 0.00,
  `net_vat` decimal(15,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'draft',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_vat_reports`
--

LOCK TABLES `bursar_vat_reports` WRITE;
/*!40000 ALTER TABLE `bursar_vat_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `bursar_vat_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_withholding_tax`
--

DROP TABLE IF EXISTS `bursar_withholding_tax`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bursar_withholding_tax` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tax_date` date NOT NULL,
  `payee_name` varchar(200) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `gross_amount` decimal(15,2) NOT NULL,
  `wht_rate` decimal(5,2) DEFAULT 6.00,
  `wht_amount` decimal(15,2) DEFAULT 0.00,
  `status` varchar(20) DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `idx_wht_date` (`tax_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_withholding_tax`
--

LOCK TABLES `bursar_withholding_tax` WRITE;
/*!40000 ALTER TABLE `bursar_withholding_tax` DISABLE KEYS */;
/*!40000 ALTER TABLE `bursar_withholding_tax` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_management`
--

DROP TABLE IF EXISTS `cache_management`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_management` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cache_key` varchar(255) NOT NULL,
  `cache_value` longtext DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `cache_key` (`cache_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_management`
--

LOCK TABLES `cache_management` WRITE;
/*!40000 ALTER TABLE `cache_management` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_management` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `certificate_templates`
--

DROP TABLE IF EXISTS `certificate_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `certificate_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_name` varchar(200) NOT NULL,
  `certificate_type` varchar(100) DEFAULT NULL,
  `template_data` longtext DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `certificate_templates`
--

LOCK TABLES `certificate_templates` WRITE;
/*!40000 ALTER TABLE `certificate_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `certificate_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `certificate_uploads`
--

DROP TABLE IF EXISTS `certificate_uploads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `certificate_uploads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `certificate_type` varchar(100) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cu_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `certificate_uploads`
--

LOCK TABLES `certificate_uploads` WRITE;
/*!40000 ALTER TABLE `certificate_uploads` DISABLE KEYS */;
/*!40000 ALTER TABLE `certificate_uploads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `certificate_verification`
--

DROP TABLE IF EXISTS `certificate_verification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `certificate_verification` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `certificate_number` varchar(50) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `verification_code` varchar(100) NOT NULL,
  `verification_url` varchar(500) DEFAULT NULL,
  `verified_by` varchar(255) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_v_cert` (`certificate_number`),
  KEY `idx_v_code` (`verification_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `certificate_verification`
--

LOCK TABLES `certificate_verification` WRITE;
/*!40000 ALTER TABLE `certificate_verification` DISABLE KEYS */;
/*!40000 ALTER TABLE `certificate_verification` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `certificates`
--

DROP TABLE IF EXISTS `certificates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `certificates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `certificate_number` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `certificate_type` enum('National Certificate','Diploma','Completion Letter','Recommendation Letter','Training Certificate','Clinical Placement Certificate') NOT NULL,
  `program` varchar(255) DEFAULT NULL,
  `award` varchar(255) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `gpa` decimal(4,2) DEFAULT NULL,
  `cgpa` decimal(4,2) DEFAULT NULL,
  `class_of_award` varchar(100) DEFAULT NULL,
  `status` enum('draft','pending_principal','pending_dg','approved','rejected','released') DEFAULT 'draft',
  `requested_by` int(11) DEFAULT NULL,
  `requested_at` datetime DEFAULT NULL,
  `approved_by_registrar` int(11) DEFAULT NULL,
  `approved_at_registrar` datetime DEFAULT NULL,
  `approved_by_principal` int(11) DEFAULT NULL,
  `approved_at_principal` datetime DEFAULT NULL,
  `approved_by_dg` int(11) DEFAULT NULL,
  `approved_at_dg` datetime DEFAULT NULL,
  `rejected_by` int(11) DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `qr_code` varchar(500) DEFAULT NULL,
  `barcode` varchar(100) DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `student_downloadable` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `certificate_number` (`certificate_number`),
  KEY `idx_c_student` (`student_id`),
  KEY `idx_c_type` (`certificate_type`),
  KEY `idx_c_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `certificates`
--

LOCK TABLES `certificates` WRITE;
/*!40000 ALTER TABLE `certificates` DISABLE KEYS */;
/*!40000 ALTER TABLE `certificates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chemical_inventory`
--

DROP TABLE IF EXISTS `chemical_inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chemical_inventory` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `chemical_name` varchar(200) DEFAULT NULL,
  `cas_number` varchar(50) DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT 0.00,
  `unit` varchar(30) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `storage_location` varchar(100) DEFAULT NULL,
  `safety_data_sheet` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chemical_inventory`
--

LOCK TABLES `chemical_inventory` WRITE;
/*!40000 ALTER TABLE `chemical_inventory` DISABLE KEYS */;
/*!40000 ALTER TABLE `chemical_inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `classes`
--

DROP TABLE IF EXISTS `classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `classes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `code` varchar(30) DEFAULT NULL,
  `teacher_id` int(10) unsigned DEFAULT NULL,
  `room` varchar(50) DEFAULT NULL,
  `capacity` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `classes`
--

LOCK TABLES `classes` WRITE;
/*!40000 ALTER TABLE `classes` DISABLE KEYS */;
/*!40000 ALTER TABLE `classes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clinical_assessments`
--

DROP TABLE IF EXISTS `clinical_assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clinical_assessments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `placement_id` int(11) DEFAULT NULL,
  `assessment_date` date DEFAULT NULL,
  `skill_assessed` varchar(255) DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `max_score` decimal(5,2) DEFAULT 100.00,
  `passed` tinyint(1) DEFAULT 0,
  `assessed_by` int(11) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ca_student` (`student_id`),
  KEY `idx_ca_placement` (`placement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clinical_assessments`
--

LOCK TABLES `clinical_assessments` WRITE;
/*!40000 ALTER TABLE `clinical_assessments` DISABLE KEYS */;
/*!40000 ALTER TABLE `clinical_assessments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clinical_placements`
--

DROP TABLE IF EXISTS `clinical_placements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clinical_placements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `facility_name` varchar(300) NOT NULL,
  `department` varchar(200) DEFAULT '',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `supervisor_name` varchar(200) DEFAULT '',
  `supervisor_phone` varchar(50) DEFAULT '',
  `status` varchar(50) DEFAULT 'Active',
  `created_by` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clinical_placements`
--

LOCK TABLES `clinical_placements` WRITE;
/*!40000 ALTER TABLE `clinical_placements` DISABLE KEYS */;
/*!40000 ALTER TABLE `clinical_placements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `communication_channels`
--

DROP TABLE IF EXISTS `communication_channels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `communication_channels` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department_code` varchar(20) NOT NULL,
  `department_name` varchar(255) NOT NULL,
  `routing_email` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_department_code` (`department_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `communication_channels`
--

LOCK TABLES `communication_channels` WRITE;
/*!40000 ALTER TABLE `communication_channels` DISABLE KEYS */;
/*!40000 ALTER TABLE `communication_channels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `communications`
--

DROP TABLE IF EXISTS `communications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `communications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient_type` varchar(50) DEFAULT 'student',
  `recipient_id` int(11) DEFAULT 0,
  `subject` varchar(300) NOT NULL,
  `message` text NOT NULL,
  `channel` varchar(50) DEFAULT 'portal',
  `sent_by` int(11) DEFAULT 0,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `communications`
--

LOCK TABLES `communications` WRITE;
/*!40000 ALTER TABLE `communications` DISABLE KEYS */;
/*!40000 ALTER TABLE `communications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compliance_records`
--

DROP TABLE IF EXISTS `compliance_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `compliance_records` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `compliance_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'compliant',
  `review_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compliance_records`
--

LOCK TABLES `compliance_records` WRITE;
/*!40000 ALTER TABLE `compliance_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `compliance_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compliance_requirements`
--

DROP TABLE IF EXISTS `compliance_requirements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `compliance_requirements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `requirement_name` varchar(255) NOT NULL,
  `category` varchar(60) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `regulatory_body` varchar(120) DEFAULT NULL,
  `frequency` varchar(30) DEFAULT 'Annual',
  `status` enum('Compliant','Partial','Non-Compliant','Not Assessed','Exempt') DEFAULT 'Not Assessed',
  `due_date` date DEFAULT NULL,
  `last_assessment_date` date DEFAULT NULL,
  `assigned_to` int(10) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cr_status` (`status`),
  KEY `idx_cr_due` (`due_date`),
  KEY `idx_cr_category` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compliance_requirements`
--

LOCK TABLES `compliance_requirements` WRITE;
/*!40000 ALTER TABLE `compliance_requirements` DISABLE KEYS */;
INSERT INTO `compliance_requirements` (`id`, `requirement_name`, `category`, `description`, `regulatory_body`, `frequency`, `status`, `due_date`, `last_assessment_date`, `assigned_to`, `notes`, `created_at`, `updated_at`) VALUES (1,'NCHE Annual Report','Academic',NULL,NULL,'Annual','Not Assessed','2026-09-18',NULL,NULL,NULL,'2026-06-20 08:28:34',NULL),(2,'UNMC License Renewal','Regulatory',NULL,NULL,'Annual','Not Assessed','2026-12-17',NULL,NULL,NULL,'2026-06-20 08:28:34',NULL),(3,'Fire Safety Inspection','Safety',NULL,NULL,'Annual','Not Assessed','2026-08-19',NULL,NULL,NULL,'2026-06-20 08:28:34',NULL),(4,'Tax Filing','Financial',NULL,NULL,'Annual','Not Assessed','2026-08-04',NULL,NULL,NULL,'2026-06-20 08:28:34',NULL),(5,'NCHE Annual Report','Academic',NULL,NULL,'Annual','Not Assessed','2026-09-18',NULL,NULL,NULL,'2026-06-20 08:41:08',NULL),(6,'UNMC License Renewal','Regulatory',NULL,NULL,'Annual','Not Assessed','2026-12-17',NULL,NULL,NULL,'2026-06-20 08:41:08',NULL),(7,'Fire Safety Inspection','Safety',NULL,NULL,'Annual','Not Assessed','2026-08-19',NULL,NULL,NULL,'2026-06-20 08:41:08',NULL),(8,'Tax Filing','Financial',NULL,NULL,'Annual','Not Assessed','2026-08-04',NULL,NULL,NULL,'2026-06-20 08:41:08',NULL),(9,'NCHE Annual Report','Academic',NULL,NULL,'Annual','Not Assessed','2026-09-18',NULL,NULL,NULL,'2026-06-20 08:45:03',NULL),(10,'UNMC License Renewal','Regulatory',NULL,NULL,'Annual','Not Assessed','2026-12-17',NULL,NULL,NULL,'2026-06-20 08:45:03',NULL),(11,'Fire Safety Inspection','Safety',NULL,NULL,'Annual','Not Assessed','2026-08-19',NULL,NULL,NULL,'2026-06-20 08:45:03',NULL),(12,'Tax Filing','Financial',NULL,NULL,'Annual','Not Assessed','2026-08-04',NULL,NULL,NULL,'2026-06-20 08:45:03',NULL),(13,'NCHE Annual Report','Academic',NULL,NULL,'Annual','Not Assessed','2026-09-18',NULL,NULL,NULL,'2026-06-20 08:46:53',NULL),(14,'UNMC License Renewal','Regulatory',NULL,NULL,'Annual','Not Assessed','2026-12-17',NULL,NULL,NULL,'2026-06-20 08:46:53',NULL),(15,'Fire Safety Inspection','Safety',NULL,NULL,'Annual','Not Assessed','2026-08-19',NULL,NULL,NULL,'2026-06-20 08:46:53',NULL),(16,'Tax Filing','Financial',NULL,NULL,'Annual','Not Assessed','2026-08-04',NULL,NULL,NULL,'2026-06-20 08:46:53',NULL);
/*!40000 ALTER TABLE `compliance_requirements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `counseling_sessions`
--

DROP TABLE IF EXISTS `counseling_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `counseling_sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `counselor_id` int(10) unsigned DEFAULT NULL,
  `session_date` datetime DEFAULT NULL,
  `session_type` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `status` varchar(30) DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `counseling_sessions`
--

LOCK TABLES `counseling_sessions` WRITE;
/*!40000 ALTER TABLE `counseling_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `counseling_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_registrations`
--

DROP TABLE IF EXISTS `course_registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `course_registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(30) DEFAULT NULL,
  `status` enum('Pending','Submitted','Registered','Approved','Rejected','Dropped') DEFAULT 'Pending',
  `registration_date` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `course_id` (`course_id`),
  KEY `status` (`status`),
  KEY `idx_cr_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_registrations`
--

LOCK TABLES `course_registrations` WRITE;
/*!40000 ALTER TABLE `course_registrations` DISABLE KEYS */;
/*!40000 ALTER TABLE `course_registrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `daily_sick_records`
--

DROP TABLE IF EXISTS `daily_sick_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `daily_sick_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `record_number` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `student_name` varchar(300) NOT NULL,
  `student_number` varchar(50) DEFAULT NULL,
  `program` varchar(100) DEFAULT NULL,
  `year_of_study` int(11) DEFAULT NULL,
  `sickness_id` int(11) DEFAULT NULL,
  `sickness_name` varchar(255) DEFAULT NULL,
  `temperature` varchar(20) DEFAULT NULL,
  `blood_pressure` varchar(20) DEFAULT NULL,
  `symptoms` text DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment_given` text DEFAULT NULL,
  `medicines_prescribed` text DEFAULT NULL,
  `severity` enum('Mild','Moderate','Severe','Critical') DEFAULT 'Mild',
  `status` enum('Treated','Referred','Admitted','Discharged','Follow-up','Critical') DEFAULT 'Treated',
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `referred_to` varchar(255) DEFAULT NULL,
  `attended_by` varchar(200) DEFAULT NULL,
  `visit_date` date NOT NULL,
  `visit_time` time DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `record_number` (`record_number`),
  KEY `student_id` (`student_id`),
  KEY `sickness_id` (`sickness_id`),
  KEY `visit_date` (`visit_date`),
  KEY `status` (`status`),
  KEY `severity` (`severity`),
  KEY `student_name` (`student_name`),
  KEY `program` (`program`),
  KEY `dsr_student_date` (`student_id`,`visit_date`),
  CONSTRAINT `daily_sick_records_ibfk_1` FOREIGN KEY (`sickness_id`) REFERENCES `sickness_directory` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `daily_sick_records`
--

LOCK TABLES `daily_sick_records` WRITE;
/*!40000 ALTER TABLE `daily_sick_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `daily_sick_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `data_ownership_rules`
--

DROP TABLE IF EXISTS `data_ownership_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `data_ownership_rules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int(10) unsigned NOT NULL,
  `department_code` varchar(20) DEFAULT NULL,
  `data_category` varchar(100) NOT NULL DEFAULT 'all',
  `access_level` enum('none','read','write','full') DEFAULT 'none',
  `is_owner` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_dor_role` (`role_id`),
  KEY `idx_dor_dept` (`department_code`),
  KEY `idx_dor_category` (`data_category`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `data_ownership_rules`
--

LOCK TABLES `data_ownership_rules` WRITE;
/*!40000 ALTER TABLE `data_ownership_rules` DISABLE KEYS */;
INSERT INTO `data_ownership_rules` (`id`, `role_id`, `department_code`, `data_category`, `access_level`, `is_owner`, `created_at`) VALUES (1,1,NULL,'all','full',1,'2026-06-20 08:28:34'),(2,3,NULL,'all','full',1,'2026-06-20 08:28:34'),(3,4,NULL,'all','full',1,'2026-06-20 08:28:34'),(4,1,NULL,'all','full',1,'2026-06-20 08:41:08'),(5,3,NULL,'all','full',1,'2026-06-20 08:41:08'),(6,4,NULL,'all','full',1,'2026-06-20 08:41:08'),(7,1,NULL,'all','full',1,'2026-06-20 08:45:02'),(8,3,NULL,'all','full',1,'2026-06-20 08:45:02'),(9,4,NULL,'all','full',1,'2026-06-20 08:45:02'),(10,1,NULL,'all','full',1,'2026-06-20 08:46:53'),(11,3,NULL,'all','full',1,'2026-06-20 08:46:53'),(12,4,NULL,'all','full',1,'2026-06-20 08:46:53');
/*!40000 ALTER TABLE `data_ownership_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `data_sync_status`
--

DROP TABLE IF EXISTS `data_sync_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `data_sync_status` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sync_type` varchar(100) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `records_processed` int(11) DEFAULT 0,
  `errors` int(11) DEFAULT 0,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `data_sync_status`
--

LOCK TABLES `data_sync_status` WRITE;
/*!40000 ALTER TABLE `data_sync_status` DISABLE KEYS */;
/*!40000 ALTER TABLE `data_sync_status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `department_reviews`
--

DROP TABLE IF EXISTS `department_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `department_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department` varchar(200) DEFAULT NULL,
  `reviewer_id` int(11) DEFAULT NULL,
  `review_period` varchar(50) DEFAULT NULL,
  `overall_score` decimal(5,2) DEFAULT NULL,
  `strengths` text DEFAULT NULL,
  `weaknesses` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `status` enum('draft','submitted','reviewed') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `department_reviews`
--

LOCK TABLES `department_reviews` WRITE;
/*!40000 ALTER TABLE `department_reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `department_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `department_targets`
--

DROP TABLE IF EXISTS `department_targets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `department_targets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `department_code` varchar(20) NOT NULL,
  `fiscal_year` varchar(10) DEFAULT NULL,
  `target_name` varchar(255) NOT NULL,
  `target_category` varchar(60) DEFAULT NULL,
  `target_value` decimal(12,2) DEFAULT NULL,
  `actual_value` decimal(12,2) DEFAULT NULL,
  `unit` varchar(30) DEFAULT NULL,
  `weight_pct` decimal(5,2) DEFAULT 100.00,
  `status` enum('Pending','In Progress','Achieved','Not Met','Delayed') DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_dt_dept` (`department_code`),
  KEY `idx_dt_fiscal` (`fiscal_year`),
  KEY `idx_dt_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `department_targets`
--

LOCK TABLES `department_targets` WRITE;
/*!40000 ALTER TABLE `department_targets` DISABLE KEYS */;
/*!40000 ALTER TABLE `department_targets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `departments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `code` varchar(30) DEFAULT NULL,
  `hod_id` int(10) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dg_read_notifications`
--

DROP TABLE IF EXISTS `dg_read_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dg_read_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_key` varchar(64) NOT NULL,
  `user_id` int(11) NOT NULL,
  `read_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_nk_uid` (`notification_key`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dg_read_notifications`
--

LOCK TABLES `dg_read_notifications` WRITE;
/*!40000 ALTER TABLE `dg_read_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `dg_read_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `director_departments`
--

DROP TABLE IF EXISTS `director_departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `director_departments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `role_id` int(10) unsigned NOT NULL,
  `department_code` varchar(20) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_dd_role_dept` (`role_id`,`department_code`),
  KEY `idx_dd_role` (`role_id`),
  KEY `idx_dd_dept` (`department_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `director_departments`
--

LOCK TABLES `director_departments` WRITE;
/*!40000 ALTER TABLE `director_departments` DISABLE KEYS */;
/*!40000 ALTER TABLE `director_departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `director_news`
--

DROP TABLE IF EXISTS `director_news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `director_news` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `excerpt` text DEFAULT NULL,
  `featured_image` varchar(500) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `author_id` int(11) DEFAULT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `director_news`
--

LOCK TABLES `director_news` WRITE;
/*!40000 ALTER TABLE `director_news` DISABLE KEYS */;
/*!40000 ALTER TABLE `director_news` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `director_performance_reviews`
--

DROP TABLE IF EXISTS `director_performance_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `director_performance_reviews` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(10) unsigned NOT NULL,
  `review_period` varchar(60) DEFAULT NULL,
  `fiscal_year` varchar(10) DEFAULT NULL,
  `overall_score` decimal(5,2) DEFAULT NULL,
  `targets_met` int(11) DEFAULT 0,
  `targets_total` int(11) DEFAULT 0,
  `summary` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `reviewed_by` int(10) unsigned DEFAULT NULL,
  `status` enum('Draft','Submitted','Approved','Archived') DEFAULT 'Draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_dpr_staff` (`staff_id`),
  KEY `idx_dpr_fiscal` (`fiscal_year`),
  KEY `idx_dpr_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `director_performance_reviews`
--

LOCK TABLES `director_performance_reviews` WRITE;
/*!40000 ALTER TABLE `director_performance_reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `director_performance_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `duty_roster`
--

DROP TABLE IF EXISTS `duty_roster`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `duty_roster` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(10) unsigned DEFAULT NULL,
  `duty_date` date DEFAULT NULL,
  `shift` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `duty_roster`
--

LOCK TABLES `duty_roster` WRITE;
/*!40000 ALTER TABLE `duty_roster` DISABLE KEYS */;
/*!40000 ALTER TABLE `duty_roster` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `duty_rosters`
--

DROP TABLE IF EXISTS `duty_rosters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `duty_rosters` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(10) unsigned DEFAULT NULL,
  `duty_date` date DEFAULT NULL,
  `duty_type` varchar(100) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `status` varchar(30) DEFAULT 'scheduled',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `duty_rosters`
--

LOCK TABLES `duty_rosters` WRITE;
/*!40000 ALTER TABLE `duty_rosters` DISABLE KEYS */;
/*!40000 ALTER TABLE `duty_rosters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `error_logs`
--

DROP TABLE IF EXISTS `error_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `error_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `error_type` varchar(100) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `file` varchar(500) DEFAULT NULL,
  `line` int(11) DEFAULT NULL,
  `stack_trace` text DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `error_logs`
--

LOCK TABLES `error_logs` WRITE;
/*!40000 ALTER TABLE `error_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `error_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exam_results`
--

DROP TABLE IF EXISTS `exam_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exam_results` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `exam_id` int(10) unsigned NOT NULL,
  `student_id` int(10) unsigned NOT NULL,
  `marks_obtained` decimal(5,2) DEFAULT 0.00,
  `grade` varchar(5) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `exam_id` (`exam_id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exam_results`
--

LOCK TABLES `exam_results` WRITE;
/*!40000 ALTER TABLE `exam_results` DISABLE KEYS */;
/*!40000 ALTER TABLE `exam_results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exam_schedules`
--

DROP TABLE IF EXISTS `exam_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exam_schedules` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `exam_id` int(10) unsigned NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room` varchar(50) DEFAULT NULL,
  `invigilator_id` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exam_schedules`
--

LOCK TABLES `exam_schedules` WRITE;
/*!40000 ALTER TABLE `exam_schedules` DISABLE KEYS */;
/*!40000 ALTER TABLE `exam_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `examination_records`
--

DROP TABLE IF EXISTS `examination_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `examination_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `exam_number` varchar(50) DEFAULT NULL,
  `exam_type` varchar(50) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `continuous_assessment_marks` decimal(8,2) DEFAULT NULL,
  `final_exam_marks` decimal(8,2) DEFAULT NULL,
  `marks_obtained` decimal(8,2) DEFAULT NULL,
  `total_marks` decimal(8,2) DEFAULT 100.00,
  `grade` varchar(5) DEFAULT NULL,
  `grade_status` enum('Not Entered','Entered','Approved','Published') DEFAULT 'Not Entered',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `course_code` (`course_code`),
  KEY `exam_type` (`exam_type`),
  KEY `idx_exam_student` (`student_id`),
  KEY `idx_exam_course` (`course_code`),
  KEY `idx_exam_status` (`grade_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `examination_records`
--

LOCK TABLES `examination_records` WRITE;
/*!40000 ALTER TABLE `examination_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `examination_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exams`
--

DROP TABLE IF EXISTS `exams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exams` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `subject_id` int(10) unsigned DEFAULT NULL,
  `class_id` int(10) unsigned DEFAULT NULL,
  `date` date DEFAULT NULL,
  `duration` int(11) DEFAULT 0,
  `total_marks` int(11) DEFAULT 100,
  `passing_marks` int(11) DEFAULT 50,
  `term` varchar(30) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `exams`
--

LOCK TABLES `exams` WRITE;
/*!40000 ALTER TABLE `exams` DISABLE KEYS */;
/*!40000 ALTER TABLE `exams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `expenses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `expense_number` varchar(60) DEFAULT NULL,
  `category` varchar(80) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `expense_date` date DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `approved_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_expenses_date` (`expense_date`),
  KEY `idx_expenses_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenses`
--

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
INSERT INTO `expenses` (`id`, `expense_number`, `category`, `description`, `amount`, `expense_date`, `status`, `approved_by`, `created_at`) VALUES (1,NULL,'Supplies','Sample Supplies expense',1155541.00,'2014-01-20','approved',NULL,'2026-06-20 06:58:56'),(2,NULL,'Equipment','Sample Equipment expense',281885.00,'2022-12-20','approved',NULL,'2026-06-20 06:58:56'),(3,NULL,'Salaries','Sample Salaries expense',1791389.00,'2017-11-20','approved',NULL,'2026-06-20 06:58:56'),(4,NULL,'Transport','Sample Transport expense',932502.00,'2019-06-20','approved',NULL,'2026-06-20 06:58:56'),(5,NULL,'Other','Sample Other expense',1195613.00,'2015-10-20','approved',NULL,'2026-06-20 06:58:56'),(6,NULL,'Maintenance','Sample Maintenance expense',1799641.00,'2021-08-20','approved',NULL,'2026-06-20 06:58:56'),(7,NULL,'Other','Sample Other expense',577084.00,'2023-11-20','approved',NULL,'2026-06-20 06:58:56'),(8,NULL,'Other','Sample Other expense',459948.00,'2015-08-20','approved',NULL,'2026-06-20 06:58:56'),(9,NULL,'Utilities','Sample Utilities expense',1660252.00,'2013-05-20','approved',NULL,'2026-06-20 06:58:56'),(10,NULL,'Maintenance','Sample Maintenance expense',1097576.00,'2022-01-20','approved',NULL,'2026-06-20 06:58:56'),(11,NULL,'Maintenance','Sample Maintenance expense',1769462.00,'2016-02-20','approved',NULL,'2026-06-20 06:58:56'),(12,NULL,'Other','Sample Other expense',1057051.00,'2012-12-20','approved',NULL,'2026-06-20 06:58:56'),(13,NULL,'Other','Sample Other expense',99759.00,'2012-05-20','approved',NULL,'2026-06-20 06:58:56'),(14,NULL,'Supplies','Sample Supplies expense',1509836.00,'2025-01-20','approved',NULL,'2026-06-20 06:58:56'),(15,NULL,'Equipment','Sample Equipment expense',1842522.00,'2016-10-20','approved',NULL,'2026-06-20 06:58:56'),(16,NULL,'Other','Sample Other expense',412867.00,'2020-02-20','approved',NULL,'2026-06-20 06:58:56'),(17,NULL,'Salaries','Sample Salaries expense',349421.00,'2012-06-20','approved',NULL,'2026-06-20 06:58:56'),(18,NULL,'Maintenance','Sample Maintenance expense',1440233.00,'2016-01-20','approved',NULL,'2026-06-20 06:58:56'),(19,NULL,'Utilities','Sample Utilities expense',164347.00,'2017-03-20','approved',NULL,'2026-06-20 06:58:56'),(20,NULL,'Equipment','Sample Equipment expense',585657.00,'2017-02-20','approved',NULL,'2026-06-20 06:58:56'),(21,NULL,'Equipment','Sample Equipment expense',322309.00,'2015-09-20','approved',NULL,'2026-06-20 06:58:56'),(22,NULL,'Supplies','Sample Supplies expense',1484606.00,'2020-11-20','approved',NULL,'2026-06-20 06:58:56'),(23,NULL,'Equipment','Sample Equipment expense',185112.00,'2011-08-20','approved',NULL,'2026-06-20 06:58:56'),(24,NULL,'Equipment','Sample Equipment expense',286701.00,'2013-05-20','approved',NULL,'2026-06-20 06:58:56'),(25,NULL,'Maintenance','Sample Maintenance expense',1019441.00,'2020-12-20','approved',NULL,'2026-06-20 06:58:56'),(26,NULL,'Maintenance','Sample Maintenance expense',778746.00,'2015-06-20','approved',NULL,'2026-06-20 06:58:56'),(27,NULL,'Other','Sample Other expense',1680279.00,'2025-11-20','approved',NULL,'2026-06-20 06:58:56'),(28,NULL,'Supplies','Sample Supplies expense',1579464.00,'2018-09-20','approved',NULL,'2026-06-20 06:58:56'),(29,NULL,'Salaries','Sample Salaries expense',1274586.00,'2022-08-20','approved',NULL,'2026-06-20 06:58:56'),(30,NULL,'Other','Sample Other expense',172348.00,'2011-07-20','approved',NULL,'2026-06-20 06:58:56');
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `facilities`
--

DROP TABLE IF EXISTS `facilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `facilities` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` varchar(100) DEFAULT NULL,
  `capacity` int(11) DEFAULT 0,
  `location` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(30) DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `facilities`
--

LOCK TABLES `facilities` WRITE;
/*!40000 ALTER TABLE `facilities` DISABLE KEYS */;
/*!40000 ALTER TABLE `facilities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `facility_bookings`
--

DROP TABLE IF EXISTS `facility_bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `facility_bookings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `facility_id` int(10) unsigned NOT NULL,
  `booked_by` int(10) unsigned NOT NULL,
  `purpose` text DEFAULT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `approved_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `facility_bookings`
--

LOCK TABLES `facility_bookings` WRITE;
/*!40000 ALTER TABLE `facility_bookings` DISABLE KEYS */;
/*!40000 ALTER TABLE `facility_bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fee_adjustments`
--

DROP TABLE IF EXISTS `fee_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fee_adjustments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(50) NOT NULL,
  `adjustment_type` varchar(50) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fee_adjustments`
--

LOCK TABLES `fee_adjustments` WRITE;
/*!40000 ALTER TABLE `fee_adjustments` DISABLE KEYS */;
/*!40000 ALTER TABLE `fee_adjustments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary table structure for view `fee_payments`
--

DROP TABLE IF EXISTS `fee_payments`;
/*!50001 DROP VIEW IF EXISTS `fee_payments`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE VIEW `fee_payments` AS SELECT
 1 AS `id`,
  1 AS `student_id`,
  1 AS `fee_account_id`,
  1 AS `amount_paid`,
  1 AS `payment_method`,
  1 AS `receipt_number`,
  1 AS `status`,
  1 AS `payment_date`,
  1 AS `notes`,
  1 AS `processed_by`,
  1 AS `created_at`,
  1 AS `updated_at` */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `financial_messages`
--

DROP TABLE IF EXISTS `financial_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `financial_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `attachment` varchar(255) DEFAULT '',
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `sender_role` varchar(50) DEFAULT '',
  `recipient_role` varchar(50) DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sender` (`sender_id`),
  KEY `idx_recipient` (`recipient_id`),
  KEY `idx_read` (`is_read`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `financial_messages`
--

LOCK TABLES `financial_messages` WRITE;
/*!40000 ALTER TABLE `financial_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `financial_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `financial_notices`
--

DROP TABLE IF EXISTS `financial_notices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `financial_notices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `author_id` int(11) NOT NULL,
  `author_role` varchar(50) DEFAULT '',
  `priority` enum('Low','Normal','High','Urgent') DEFAULT 'Normal',
  `is_published` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_published` (`is_published`),
  KEY `idx_priority` (`priority`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `financial_notices`
--

LOCK TABLES `financial_notices` WRITE;
/*!40000 ALTER TABLE `financial_notices` DISABLE KEYS */;
/*!40000 ALTER TABLE `financial_notices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `generated_documents`
--

DROP TABLE IF EXISTS `generated_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `generated_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_type` varchar(50) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `document_title` varchar(255) DEFAULT NULL,
  `document_description` varchar(500) DEFAULT NULL,
  `month` int(11) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `gross_salary` decimal(12,2) DEFAULT 0.00,
  `net_pay` decimal(12,2) DEFAULT 0.00,
  `file_path` varchar(500) DEFAULT NULL,
  `document_content` longtext DEFAULT NULL,
  `access_code` varchar(100) DEFAULT NULL,
  `generation_date` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `document_type` (`document_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `generated_documents`
--

LOCK TABLES `generated_documents` WRITE;
/*!40000 ALTER TABLE `generated_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `generated_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gpa_settings`
--

DROP TABLE IF EXISTS `gpa_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gpa_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gpa_settings`
--

LOCK TABLES `gpa_settings` WRITE;
/*!40000 ALTER TABLE `gpa_settings` DISABLE KEYS */;
INSERT INTO `gpa_settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES (1,'pass_mark','50','Minimum pass percentage','2026-06-26 13:25:09','2026-06-26 13:25:09'),(2,'distinction_threshold','80','Minimum percentage for Distinction','2026-06-26 13:25:09','2026-06-26 13:25:09'),(3,'credit_threshold','60','Minimum percentage for Credit','2026-06-26 13:25:09','2026-06-26 13:25:09'),(4,'supplementary_min','35','Minimum percentage eligible for supplementary exam','2026-06-26 13:25:09','2026-06-26 13:25:09'),(5,'max_supplementary_grade','C','Maximum grade after supplementary exam','2026-06-26 13:25:09','2026-06-26 13:25:09'),(6,'retake_max_attempts','3','Maximum retake attempts allowed','2026-06-26 13:25:09','2026-06-26 13:25:09'),(7,'academic_probation_cgpa','1.50','CGPA below this triggers academic probation','2026-06-26 13:25:09','2026-06-26 13:25:09'),(8,'suspension_cgpa','1.00','CGPA below this triggers suspension','2026-06-26 13:25:09','2026-06-26 13:25:09'),(9,'graduation_min_cgpa','2.00','Minimum CGPA required for graduation','2026-06-26 13:25:09','2026-06-26 13:25:09'),(10,'grading_system','letter','Grading system type','2026-06-26 13:25:09','2026-06-26 13:25:09');
/*!40000 ALTER TABLE `gpa_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grade_scale`
--

DROP TABLE IF EXISTS `grade_scale`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grade_scale` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grade_letter` varchar(5) NOT NULL,
  `grade_point` decimal(4,2) NOT NULL,
  `min_percentage` decimal(5,2) NOT NULL,
  `max_percentage` decimal(5,2) NOT NULL,
  `description` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `grade_letter` (`grade_letter`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grade_scale`
--

LOCK TABLES `grade_scale` WRITE;
/*!40000 ALTER TABLE `grade_scale` DISABLE KEYS */;
INSERT INTO `grade_scale` (`id`, `grade_letter`, `grade_point`, `min_percentage`, `max_percentage`, `description`, `is_active`, `created_at`) VALUES (1,'A',4.00,80.00,100.00,'Distinction',1,'2026-06-26 13:25:09'),(2,'B',3.00,70.00,79.99,'Credit',1,'2026-06-26 13:25:09'),(3,'C',2.00,60.00,69.99,'Credit',1,'2026-06-26 13:25:09'),(4,'D',1.00,50.00,59.99,'Pass',1,'2026-06-26 13:25:09'),(5,'F',0.00,0.00,49.99,'Fail',1,'2026-06-26 13:25:09');
/*!40000 ALTER TABLE `grade_scale` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grade_scales`
--

DROP TABLE IF EXISTS `grade_scales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grade_scales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grade_letter` varchar(5) NOT NULL,
  `grade_point` decimal(4,2) DEFAULT 0.00,
  `min_percentage` decimal(5,2) DEFAULT 0.00,
  `max_percentage` decimal(5,2) DEFAULT 100.00,
  `status` varchar(50) DEFAULT 'Active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_grade` (`grade_letter`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grade_scales`
--

LOCK TABLES `grade_scales` WRITE;
/*!40000 ALTER TABLE `grade_scales` DISABLE KEYS */;
/*!40000 ALTER TABLE `grade_scales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grades`
--

DROP TABLE IF EXISTS `grades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grades` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `subject_id` int(10) unsigned DEFAULT NULL,
  `grade` varchar(5) DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `term` varchar(30) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grades`
--

LOCK TABLES `grades` WRITE;
/*!40000 ALTER TABLE `grades` DISABLE KEYS */;
/*!40000 ALTER TABLE `grades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grading_approval_workflow`
--

DROP TABLE IF EXISTS `grading_approval_workflow`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `grading_approval_workflow` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `current_stage` varchar(100) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Returned') DEFAULT 'Pending',
  `comments` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `current_stage` (`current_stage`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grading_approval_workflow`
--

LOCK TABLES `grading_approval_workflow` WRITE;
/*!40000 ALTER TABLE `grading_approval_workflow` DISABLE KEYS */;
/*!40000 ALTER TABLE `grading_approval_workflow` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `graduation_approvals`
--

DROP TABLE IF EXISTS `graduation_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `graduation_approvals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `graduation_id` int(11) NOT NULL,
  `approval_level` enum('senate','principal','director_general') NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ga_grad` (`graduation_id`),
  KEY `idx_ga_level` (`approval_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `graduation_approvals`
--

LOCK TABLES `graduation_approvals` WRITE;
/*!40000 ALTER TABLE `graduation_approvals` DISABLE KEYS */;
/*!40000 ALTER TABLE `graduation_approvals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `graduation_candidates`
--

DROP TABLE IF EXISTS `graduation_candidates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `graduation_candidates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `program` varchar(255) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `cgpa` decimal(4,2) DEFAULT NULL,
  `class_of_award` varchar(100) DEFAULT NULL,
  `total_credits` int(11) DEFAULT 0,
  `bursar_cleared` tinyint(1) DEFAULT 0,
  `library_cleared` tinyint(1) DEFAULT 0,
  `registrar_cleared` tinyint(1) DEFAULT 0,
  `hod_cleared` tinyint(1) DEFAULT 0,
  `is_eligible` tinyint(1) DEFAULT 0,
  `senate_approved` tinyint(1) DEFAULT 0,
  `senate_approved_at` datetime DEFAULT NULL,
  `principal_approved` tinyint(1) DEFAULT 0,
  `principal_approved_at` datetime DEFAULT NULL,
  `dg_approved` tinyint(1) DEFAULT 0,
  `dg_approved_at` datetime DEFAULT NULL,
  `status` enum('pending','eligible','approved','graduated','deferred') DEFAULT 'pending',
  `graduation_date` date DEFAULT NULL,
  `ceremony_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_gc_student` (`student_id`),
  KEY `idx_gc_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `graduation_candidates`
--

LOCK TABLES `graduation_candidates` WRITE;
/*!40000 ALTER TABLE `graduation_candidates` DISABLE KEYS */;
/*!40000 ALTER TABLE `graduation_candidates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hostel_management`
--

DROP TABLE IF EXISTS `hostel_management`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hostel_management` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_number` varchar(20) NOT NULL,
  `hostel_name` varchar(100) NOT NULL,
  `capacity` int(11) NOT NULL,
  `occupied` int(11) DEFAULT 0,
  `room_type` enum('Single','Double','Dormitory') NOT NULL,
  `gender` enum('Male','Female','Mixed') NOT NULL,
  `status` enum('Available','Occupied','Under Maintenance') DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_number` (`room_number`),
  KEY `idx_room_number` (`room_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hostel_management`
--

LOCK TABLES `hostel_management` WRITE;
/*!40000 ALTER TABLE `hostel_management` DISABLE KEYS */;
/*!40000 ALTER TABLE `hostel_management` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_activity_log`
--

DROP TABLE IF EXISTS `hr_activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hr_activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_activity_log`
--

LOCK TABLES `hr_activity_log` WRITE;
/*!40000 ALTER TABLE `hr_activity_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr_activity_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `institutional_alerts`
--

DROP TABLE IF EXISTS `institutional_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `institutional_alerts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `alert_type` varchar(30) DEFAULT 'info',
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `department_code` varchar(20) DEFAULT NULL,
  `source` varchar(100) DEFAULT NULL,
  `is_resolved` tinyint(1) DEFAULT 0,
  `resolved_by` int(10) unsigned DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ia_priority` (`priority`),
  KEY `idx_ia_resolved` (`is_resolved`),
  KEY `idx_ia_dept` (`department_code`),
  KEY `idx_ia_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `institutional_alerts`
--

LOCK TABLES `institutional_alerts` WRITE;
/*!40000 ALTER TABLE `institutional_alerts` DISABLE KEYS */;
INSERT INTO `institutional_alerts` (`id`, `title`, `description`, `alert_type`, `priority`, `department_code`, `source`, `is_resolved`, `resolved_by`, `resolved_at`, `created_by`, `created_at`) VALUES (1,'Staff Attendance Drop','Staff attendance dropped below 80% this week.','info','high',NULL,NULL,0,NULL,NULL,NULL,'2026-06-20 08:28:34'),(2,'Fee Collection Target','Monthly fee collection at 65% of target.','info','medium',NULL,NULL,0,NULL,NULL,NULL,'2026-06-20 08:28:34'),(3,'Exam Preparation','Final exams scheduled in 3 weeks.','info','low',NULL,NULL,0,NULL,NULL,NULL,'2026-06-20 08:28:34'),(4,'Test Alert','Test','info','low',NULL,NULL,0,NULL,NULL,NULL,'2026-06-20 08:33:53'),(5,'Staff Attendance Drop','Staff attendance dropped below 80% this week.','info','high',NULL,NULL,0,NULL,NULL,NULL,'2026-06-20 08:37:17'),(6,'Fee Collection Target','Monthly fee collection at 65% of target.','info','medium',NULL,NULL,0,NULL,NULL,NULL,'2026-06-20 08:37:17'),(7,'Exam Preparation','Final exams scheduled in 3 weeks.','info','low',NULL,NULL,0,NULL,NULL,NULL,'2026-06-20 08:37:17'),(8,'Staff Attendance Drop','Staff attendance dropped below 80% this week.','info','high',NULL,NULL,0,NULL,NULL,NULL,'2026-06-20 08:41:08'),(9,'Fee Collection Target','Monthly fee collection at 65% of target.','info','medium',NULL,NULL,0,NULL,NULL,NULL,'2026-06-20 08:41:08'),(10,'Exam Preparation','Final exams scheduled in 3 weeks.','info','low',NULL,NULL,0,NULL,NULL,NULL,'2026-06-20 08:41:08'),(11,'Staff Attendance Drop','Staff attendance dropped below 80% this week.','info','high',NULL,NULL,0,NULL,NULL,NULL,'2026-06-20 08:45:03'),(12,'Fee Collection Target','Monthly fee collection at 65% of target.','info','medium',NULL,NULL,0,NULL,NULL,NULL,'2026-06-20 08:45:03'),(13,'Exam Preparation','Final exams scheduled in 3 weeks.','info','low',NULL,NULL,0,NULL,NULL,NULL,'2026-06-20 08:45:03'),(14,'Staff Attendance Drop','Staff attendance dropped below 80% this week.','info','high',NULL,NULL,0,NULL,NULL,NULL,'2026-06-20 08:46:53'),(15,'Fee Collection Target','Monthly fee collection at 65% of target.','info','medium',NULL,NULL,0,NULL,NULL,NULL,'2026-06-20 08:46:53'),(16,'Exam Preparation','Final exams scheduled in 3 weeks.','info','low',NULL,NULL,0,NULL,NULL,NULL,'2026-06-20 08:46:53');
/*!40000 ALTER TABLE `institutional_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `institutional_risks`
--

DROP TABLE IF EXISTS `institutional_risks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `institutional_risks` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `risk_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `risk_category` varchar(60) DEFAULT NULL,
  `likelihood` enum('Rare','Unlikely','Possible','Likely','Almost Certain') DEFAULT 'Possible',
  `impact` enum('Negligible','Minor','Moderate','Major','Severe') DEFAULT 'Moderate',
  `risk_score` int(11) DEFAULT 0,
  `mitigation_strategy` text DEFAULT NULL,
  `contingency_plan` text DEFAULT NULL,
  `owner` int(10) unsigned DEFAULT NULL,
  `status` enum('Identified','Assessed','Mitigated','Monitoring','Closed') DEFAULT 'Identified',
  `target_resolution` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ir_score` (`risk_score`),
  KEY `idx_ir_status` (`status`),
  KEY `idx_ir_category` (`risk_category`),
  KEY `idx_ir_owner` (`owner`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `institutional_risks`
--

LOCK TABLES `institutional_risks` WRITE;
/*!40000 ALTER TABLE `institutional_risks` DISABLE KEYS */;
INSERT INTO `institutional_risks` (`id`, `risk_name`, `description`, `risk_category`, `likelihood`, `impact`, `risk_score`, `mitigation_strategy`, `contingency_plan`, `owner`, `status`, `target_resolution`, `created_at`, `updated_at`) VALUES (1,'Student Enrolment Decline',NULL,'Operational','Possible','Major',12,NULL,NULL,NULL,'Identified',NULL,'2026-06-20 08:28:34',NULL),(2,'Staff Retention',NULL,'HR','Likely','Moderate',12,NULL,NULL,NULL,'Monitoring',NULL,'2026-06-20 08:28:34',NULL),(3,'Budget Shortfall',NULL,'Financial','Possible','Major',12,NULL,NULL,NULL,'Identified',NULL,'2026-06-20 08:28:34',NULL),(4,'Regulatory Non-Compliance',NULL,'Compliance','Unlikely','Major',6,NULL,NULL,NULL,'Monitoring',NULL,'2026-06-20 08:28:34',NULL),(5,'Student Enrolment Decline',NULL,'Operational','Possible','Major',12,NULL,NULL,NULL,'Identified',NULL,'2026-06-20 08:41:08',NULL),(6,'Staff Retention',NULL,'HR','Likely','Moderate',12,NULL,NULL,NULL,'Monitoring',NULL,'2026-06-20 08:41:08',NULL),(7,'Budget Shortfall',NULL,'Financial','Possible','Major',12,NULL,NULL,NULL,'Identified',NULL,'2026-06-20 08:41:08',NULL),(8,'Regulatory Non-Compliance',NULL,'Compliance','Unlikely','Major',6,NULL,NULL,NULL,'Monitoring',NULL,'2026-06-20 08:41:08',NULL),(9,'Student Enrolment Decline',NULL,'Operational','Possible','Major',12,NULL,NULL,NULL,'Identified',NULL,'2026-06-20 08:45:03',NULL),(10,'Staff Retention',NULL,'HR','Likely','Moderate',12,NULL,NULL,NULL,'Monitoring',NULL,'2026-06-20 08:45:03',NULL),(11,'Budget Shortfall',NULL,'Financial','Possible','Major',12,NULL,NULL,NULL,'Identified',NULL,'2026-06-20 08:45:03',NULL),(12,'Regulatory Non-Compliance',NULL,'Compliance','Unlikely','Major',6,NULL,NULL,NULL,'Monitoring',NULL,'2026-06-20 08:45:03',NULL),(13,'Student Enrolment Decline',NULL,'Operational','Possible','Major',12,NULL,NULL,NULL,'Identified',NULL,'2026-06-20 08:46:53',NULL),(14,'Staff Retention',NULL,'HR','Likely','Moderate',12,NULL,NULL,NULL,'Monitoring',NULL,'2026-06-20 08:46:53',NULL),(15,'Budget Shortfall',NULL,'Financial','Possible','Major',12,NULL,NULL,NULL,'Identified',NULL,'2026-06-20 08:46:53',NULL),(16,'Regulatory Non-Compliance',NULL,'Compliance','Unlikely','Major',6,NULL,NULL,NULL,'Monitoring',NULL,'2026-06-20 08:46:53',NULL);
/*!40000 ALTER TABLE `institutional_risks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `intakes`
--

DROP TABLE IF EXISTS `intakes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `intakes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `intake_name` varchar(200) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `intakes`
--

LOCK TABLES `intakes` WRITE;
/*!40000 ALTER TABLE `intakes` DISABLE KEYS */;
/*!40000 ALTER TABLE `intakes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `item_name` varchar(255) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT 0,
  `unit` varchar(30) DEFAULT NULL,
  `unit_price` decimal(12,2) DEFAULT 0.00,
  `supplier` varchar(200) DEFAULT NULL,
  `reorder_level` int(11) DEFAULT 0,
  `location` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory`
--

LOCK TABLES `inventory` WRITE;
/*!40000 ALTER TABLE `inventory` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_reports`
--

DROP TABLE IF EXISTS `inventory_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_reports` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `report_type` varchar(100) DEFAULT NULL,
  `generated_by` int(10) unsigned DEFAULT NULL,
  `parameters` text DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_reports`
--

LOCK TABLES `inventory_reports` WRITE;
/*!40000 ALTER TABLE `inventory_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `it_infrastructure`
--

DROP TABLE IF EXISTS `it_infrastructure`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `it_infrastructure` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `asset_name` varchar(200) DEFAULT NULL,
  `asset_type` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'operational',
  `purchase_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `it_infrastructure`
--

LOCK TABLES `it_infrastructure` WRITE;
/*!40000 ALTER TABLE `it_infrastructure` DISABLE KEYS */;
/*!40000 ALTER TABLE `it_infrastructure` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_applications`
--

DROP TABLE IF EXISTS `job_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_applications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `recruitment_id` int(10) unsigned DEFAULT NULL,
  `applicant_name` varchar(200) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `cover_letter` text DEFAULT NULL,
  `resume_path` varchar(500) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_applications`
--

LOCK TABLES `job_applications` WRITE;
/*!40000 ALTER TABLE `job_applications` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `late_payment_settings`
--

DROP TABLE IF EXISTS `late_payment_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `late_payment_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `late_payment_settings`
--

LOCK TABLES `late_payment_settings` WRITE;
/*!40000 ALTER TABLE `late_payment_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `late_payment_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leaves`
--

DROP TABLE IF EXISTS `leaves`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leaves` (
  `s_no` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` int(10) unsigned NOT NULL,
  `leave_type` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `send_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_by` int(10) unsigned DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`s_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leaves`
--

LOCK TABLES `leaves` WRITE;
/*!40000 ALTER TABLE `leaves` DISABLE KEYS */;
/*!40000 ALTER TABLE `leaves` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_books`
--

DROP TABLE IF EXISTS `library_books`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `library_books` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `isbn` varchar(50) DEFAULT NULL,
  `publisher` varchar(200) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `available` int(11) DEFAULT 1,
  `shelf_location` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_books`
--

LOCK TABLES `library_books` WRITE;
/*!40000 ALTER TABLE `library_books` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_books` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_fines`
--

DROP TABLE IF EXISTS `library_fines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `library_fines` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `borrowing_id` int(10) unsigned NOT NULL,
  `student_id` int(10) unsigned NOT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `reason` varchar(200) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'unpaid',
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_fines`
--

LOCK TABLES `library_fines` WRITE;
/*!40000 ALTER TABLE `library_fines` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_fines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `meal_tracking`
--

DROP TABLE IF EXISTS `meal_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meal_tracking` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned DEFAULT NULL,
  `meal_type` varchar(50) DEFAULT NULL,
  `meal_date` date DEFAULT NULL,
  `meal_time` time DEFAULT NULL,
  `status` varchar(30) DEFAULT 'served',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `meal_tracking`
--

LOCK TABLES `meal_tracking` WRITE;
/*!40000 ALTER TABLE `meal_tracking` DISABLE KEYS */;
/*!40000 ALTER TABLE `meal_tracking` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `medicine_stock`
--

DROP TABLE IF EXISTS `medicine_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `medicine_stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `medicine_code` varchar(50) NOT NULL,
  `medicine_name` varchar(255) NOT NULL,
  `generic_name` varchar(255) DEFAULT NULL,
  `category` enum('Antibiotic','Painkiller','Anti-inflammatory','Antimalarial','Antiviral','Antifungal','Vitamins','First Aid','Allergy','Digestive','Respiratory','Dermatological','Ophthalmic','Other') DEFAULT 'Other',
  `dosage_form` enum('Tablet','Capsule','Syrup','Injection','Cream','Ointment','Drops','Inhaler','Suppository','Powder','Solution','Other') DEFAULT 'Tablet',
  `strength` varchar(100) DEFAULT NULL,
  `manufacturer` varchar(200) DEFAULT NULL,
  `supplier` varchar(200) DEFAULT NULL,
  `quantity_in_stock` int(11) NOT NULL DEFAULT 0,
  `unit` varchar(50) NOT NULL DEFAULT 'pcs',
  `reorder_level` int(11) DEFAULT 10,
  `unit_cost` decimal(15,2) DEFAULT NULL,
  `selling_price` decimal(15,2) DEFAULT NULL,
  `currency` varchar(10) DEFAULT 'UGX',
  `batch_number` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `storage_location` varchar(100) DEFAULT NULL,
  `requires_prescription` tinyint(1) DEFAULT 0,
  `instructions` text DEFAULT NULL,
  `side_effects` text DEFAULT NULL,
  `status` enum('In Stock','Low Stock','Out of Stock','Expired','Discontinued') DEFAULT 'In Stock',
  `last_restocked` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `medicine_code` (`medicine_code`),
  KEY `medicine_name` (`medicine_name`),
  KEY `category` (`category`),
  KEY `expiry_date` (`expiry_date`),
  KEY `status` (`status`),
  KEY `supplier` (`supplier`),
  KEY `ms_expiry_status` (`expiry_date`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `medicine_stock`
--

LOCK TABLES `medicine_stock` WRITE;
/*!40000 ALTER TABLE `medicine_stock` DISABLE KEYS */;
INSERT INTO `medicine_stock` (`id`, `medicine_code`, `medicine_name`, `generic_name`, `category`, `dosage_form`, `strength`, `manufacturer`, `supplier`, `quantity_in_stock`, `unit`, `reorder_level`, `unit_cost`, `selling_price`, `currency`, `batch_number`, `expiry_date`, `storage_location`, `requires_prescription`, `instructions`, `side_effects`, `status`, `last_restocked`, `created_by`, `created_at`, `updated_at`) VALUES (1,'PARA001','Paracetamol','Acetaminophen','Painkiller','Tablet','500mg',NULL,NULL,200,'tablets',50,50.00,NULL,'UGX',NULL,'2027-12-31','Cabinet A1',0,'1-2 tablets every 4-6 hours as needed for pain/fever',NULL,'In Stock',NULL,NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(2,'IBU001','Ibuprofen','Ibuprofen','Anti-inflammatory','Tablet','400mg',NULL,NULL,150,'tablets',30,100.00,NULL,'UGX',NULL,'2027-10-31','Cabinet A1',0,'1 tablet 3 times daily after meals',NULL,'In Stock',NULL,NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(3,'AMOX001','Amoxicillin','Amoxicillin','Antibiotic','Capsule','500mg',NULL,NULL,100,'capsules',20,200.00,NULL,'UGX',NULL,'2027-08-31','Cabinet B1',1,'1 capsule 3 times daily for 7 days',NULL,'In Stock',NULL,NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(4,'CTM001','Chlorpheniramine','Chlorpheniramine Maleate','Allergy','Tablet','4mg',NULL,NULL,100,'tablets',20,50.00,NULL,'UGX',NULL,'2027-11-30','Cabinet A2',0,'1 tablet every 4-6 hours for allergies',NULL,'In Stock',NULL,NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(5,'ORS001','Oral Rehydration Salts','ORS','Other','Powder','20.5g/sachet',NULL,NULL,100,'sachets',30,500.00,NULL,'UGX',NULL,'2028-06-30','Cabinet C1',0,'Dissolve 1 sachet in 1L water, drink after each loose stool',NULL,'In Stock',NULL,NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(6,'ART001','Artemether/Lumefantrine','Coartem','Antimalarial','Tablet','20/120mg',NULL,NULL,60,'tablets',20,1500.00,NULL,'UGX',NULL,'2027-09-30','Cabinet B2',1,'4 tablets twice daily for 3 days',NULL,'In Stock',NULL,NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(7,'VITC001','Vitamin C','Ascorbic Acid','Vitamins','Tablet','500mg',NULL,NULL,300,'tablets',50,30.00,NULL,'UGX',NULL,'2028-12-31','Cabinet C1',0,'1 tablet daily for immune support',NULL,'In Stock',NULL,NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(8,'MET001','Metered Dose Inhaler','Salbutamol','Respiratory','Inhaler','100mcg/dose',NULL,NULL,10,'inhalers',3,15000.00,NULL,'UGX',NULL,'2027-06-30','Cabinet A3',1,'1-2 puffs as needed for asthma symptoms',NULL,'In Stock',NULL,NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(9,'ANT001','Antacid','Aluminum/Magnesium Hydroxide','Digestive','Tablet','500mg',NULL,NULL,200,'tablets',40,100.00,NULL,'UGX',NULL,'2027-11-30','Cabinet C1',0,'1-2 tablets after meals or when symptomatic',NULL,'In Stock',NULL,NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(10,'HYD001','Hydrocortisone Cream','Hydrocortisone','Dermatological','Cream','1%',NULL,NULL,20,'tubes',5,5000.00,NULL,'UGX',NULL,'2027-08-31','Cabinet D1',0,'Apply thin layer to affected area 2-3 times daily',NULL,'In Stock',NULL,NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(11,'DIA001','Diazepam','Diazepam','Painkiller','Tablet','5mg',NULL,NULL,30,'tablets',10,200.00,NULL,'UGX',NULL,'2026-12-31','Cabinet B2',1,'1 tablet at bedtime for anxiety or muscle spasms',NULL,'In Stock',NULL,NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(12,'BAN001','Bandages','Cotton Bandage','First Aid','Other','4 inches x 5 meters',NULL,NULL,50,'rolls',10,1500.00,NULL,'UGX',NULL,'2029-12-31','Shelf E1',0,'For wound dressing and injury management',NULL,'In Stock',NULL,NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(13,'GAU001','Gauze Swabs','Sterile Gauze','First Aid','Other','10x10cm',NULL,NULL,200,'packs',50,800.00,NULL,'UGX',NULL,'2029-12-31','Shelf E1',0,'Sterile swabs for wound cleaning and dressing',NULL,'In Stock',NULL,NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(14,'GLU001','Glucose Powder','Dextrose','Vitamins','Powder','500g',NULL,NULL,10,'packs',3,5000.00,NULL,'UGX',NULL,'2028-06-30','Cabinet C1',0,'Mix 2 tablespoons in water for energy',NULL,'In Stock',NULL,NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(15,'ALC001','Alcohol Swabs','Isopropyl Alcohol','First Aid','Solution','70%',NULL,NULL,300,'swabs',50,100.00,NULL,'UGX',NULL,'2028-12-31','Shelf E1',0,'Use for cleaning skin before injections',NULL,'In Stock',NULL,NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(16,'CLO001','Chloroquine','Chloroquine Phosphate','Antimalarial','Tablet','250mg',NULL,NULL,50,'tablets',15,300.00,NULL,'UGX',NULL,'2027-05-31','Cabinet B2',1,'As prescribed for malaria treatment',NULL,'In Stock',NULL,NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(17,'MEF001','Mefenamic Acid','Mefenamic Acid','Painkiller','Capsule','500mg',NULL,NULL,80,'capsules',20,200.00,NULL,'UGX',NULL,'2027-07-31','Cabinet A1',0,'1 capsule 3 times daily for pain and inflammation',NULL,'In Stock',NULL,NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(18,'METR001','Metronidazole','Metronidazole','Antibiotic','Tablet','400mg',NULL,NULL,100,'tablets',20,150.00,NULL,'UGX',NULL,'2027-09-30','Cabinet B1',1,'1 tablet 3 times daily for 5-7 days',NULL,'In Stock',NULL,NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(19,'DIC001','Diclofenac Gel','Diclofenac Diethylamine','Anti-inflammatory','Cream','1%',NULL,NULL,15,'tubes',5,7000.00,NULL,'UGX',NULL,'2027-10-31','Cabinet D1',0,'Apply to affected area 3-4 times daily',NULL,'In Stock',NULL,NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(20,'CET001','Cetirizine','Cetirizine Hydrochloride','Allergy','Tablet','10mg',NULL,NULL,100,'tablets',20,100.00,NULL,'UGX',NULL,'2027-12-31','Cabinet A2',0,'1 tablet daily for allergy symptoms',NULL,'In Stock',NULL,NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(21,'ASP001','Aspirin','Acetylsalicylic Acid','Painkiller','Tablet','300mg',NULL,NULL,100,'tablets',25,50.00,NULL,'UGX',NULL,'2027-06-30','Cabinet A1',0,'1-2 tablets every 4-6 hours for pain/fever',NULL,'In Stock',NULL,NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(22,'ZIN001','Zinc Tablets','Zinc Sulfate','Vitamins','Tablet','20mg',NULL,NULL,150,'tablets',30,100.00,NULL,'UGX',NULL,'2028-09-30','Cabinet C1',0,'1 tablet daily for immune support and wound healing',NULL,'In Stock',NULL,NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(23,'CLOT001','Clotrimazole Cream','Clotrimazole','Antifungal','Cream','1%',NULL,NULL,15,'tubes',5,4000.00,NULL,'UGX',NULL,'2027-08-31','Cabinet D1',0,'Apply to affected area twice daily for 2 weeks',NULL,'In Stock',NULL,NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(24,'EYE001','Eye Drops','Chloramphenicol','Other','Drops','0.5%',NULL,NULL,20,'bottles',5,5000.00,NULL,'UGX',NULL,'2027-04-30','Cabinet A3',1,'1-2 drops in affected eye every 2-4 hours',NULL,'In Stock',NULL,NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(25,'BET001','Betadine Solution','Povidone-Iodine','First Aid','Solution','10%',NULL,NULL,10,'bottles',3,8000.00,NULL,'UGX',NULL,'2028-03-31','Shelf E1',0,'Apply to wounds for disinfection',NULL,'In Stock',NULL,NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44');
/*!40000 ALTER TABLE `medicine_stock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `medicine_stock_transactions`
--

DROP TABLE IF EXISTS `medicine_stock_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `medicine_stock_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_number` varchar(50) NOT NULL,
  `medicine_id` int(11) NOT NULL,
  `transaction_type` enum('Purchase','Issue','Return','Adjustment','Damage','Expired') NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_cost` decimal(15,2) DEFAULT NULL,
  `total_cost` decimal(15,2) DEFAULT NULL,
  `currency` varchar(10) DEFAULT 'UGX',
  `reference` varchar(200) DEFAULT NULL,
  `issued_to` varchar(200) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `transaction_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_number` (`transaction_number`),
  KEY `medicine_id` (`medicine_id`),
  KEY `transaction_type` (`transaction_type`),
  KEY `transaction_date` (`transaction_date`),
  KEY `student_id` (`student_id`),
  KEY `mst_medicine_date` (`medicine_id`,`transaction_date`),
  CONSTRAINT `medicine_stock_transactions_ibfk_1` FOREIGN KEY (`medicine_id`) REFERENCES `medicine_stock` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `medicine_stock_transactions`
--

LOCK TABLES `medicine_stock_transactions` WRITE;
/*!40000 ALTER TABLE `medicine_stock_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `medicine_stock_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `national_exam_results`
--

DROP TABLE IF EXISTS `national_exam_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `national_exam_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `exam_type` varchar(100) DEFAULT NULL,
  `exam_year` varchar(20) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `grade` varchar(10) DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `national_exam_number` varchar(100) DEFAULT NULL,
  `certificate_number` varchar(100) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ner_student` (`student_id`),
  KEY `idx_ner_exam` (`exam_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `national_exam_results`
--

LOCK TABLES `national_exam_results` WRITE;
/*!40000 ALTER TABLE `national_exam_results` DISABLE KEYS */;
/*!40000 ALTER TABLE `national_exam_results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `news_subscribers`
--

DROP TABLE IF EXISTS `news_subscribers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `news_subscribers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `user_type` enum('staff','student') NOT NULL,
  `subscribed` tinyint(1) DEFAULT 1,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_subscriber` (`user_id`,`user_type`),
  KEY `idx_ns_subscribed` (`subscribed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news_subscribers`
--

LOCK TABLES `news_subscribers` WRITE;
/*!40000 ALTER TABLE `news_subscribers` DISABLE KEYS */;
/*!40000 ALTER TABLE `news_subscribers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `news_views`
--

DROP TABLE IF EXISTS `news_views`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `news_views` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `news_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_type` enum('staff','student','public') DEFAULT 'public',
  `ip_address` varchar(45) DEFAULT NULL,
  `viewed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_nv_news` (`news_id`),
  KEY `idx_nv_user` (`user_id`,`user_type`),
  KEY `idx_nv_date` (`viewed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news_views`
--

LOCK TABLES `news_views` WRITE;
/*!40000 ALTER TABLE `news_views` DISABLE KEYS */;
/*!40000 ALTER TABLE `news_views` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_reads`
--

DROP TABLE IF EXISTS `notification_reads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notification_reads` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `notification_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `notif_user` (`notification_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_reads`
--

LOCK TABLES `notification_reads` WRITE;
/*!40000 ALTER TABLE `notification_reads` DISABLE KEYS */;
/*!40000 ALTER TABLE `notification_reads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `type` varchar(50) DEFAULT 'info',
  `priority` varchar(20) DEFAULT 'normal',
  `audience` varchar(50) DEFAULT 'all',
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `onboarding_checklist`
--

DROP TABLE IF EXISTS `onboarding_checklist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `onboarding_checklist` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(10) unsigned DEFAULT NULL,
  `task_name` varchar(200) DEFAULT NULL,
  `assigned_to` int(10) unsigned DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `onboarding_checklist`
--

LOCK TABLES `onboarding_checklist` WRITE;
/*!40000 ALTER TABLE `onboarding_checklist` DISABLE KEYS */;
/*!40000 ALTER TABLE `onboarding_checklist` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `partner_schools`
--

DROP TABLE IF EXISTS `partner_schools`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `partner_schools` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `contact_person` varchar(200) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `partnership_type` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(30) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partner_schools`
--

LOCK TABLES `partner_schools` WRITE;
/*!40000 ALTER TABLE `partner_schools` DISABLE KEYS */;
/*!40000 ALTER TABLE `partner_schools` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `partnerships`
--

DROP TABLE IF EXISTS `partnerships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `partnerships` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `partner_name` varchar(200) DEFAULT NULL,
  `partner_type` varchar(100) DEFAULT NULL,
  `contact_person` varchar(200) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `mou_file` varchar(500) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'active',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partnerships`
--

LOCK TABLES `partnerships` WRITE;
/*!40000 ALTER TABLE `partnerships` DISABLE KEYS */;
/*!40000 ALTER TABLE `partnerships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_resets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_approvals`
--

DROP TABLE IF EXISTS `payment_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_approvals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` int(11) NOT NULL,
  `payment_type` varchar(50) DEFAULT 'fee_payment',
  `requested_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approval_status` varchar(20) DEFAULT NULL,
  `approval_remarks` text DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_approvals`
--

LOCK TABLES `payment_approvals` WRITE;
/*!40000 ALTER TABLE `payment_approvals` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_approvals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_subscriptions`
--

DROP TABLE IF EXISTS `payment_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payment_subscriptions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `subscription_type` varchar(100) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `frequency` varchar(30) DEFAULT 'monthly',
  `next_due_date` date DEFAULT NULL,
  `status` varchar(30) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_subscriptions`
--

LOCK TABLES `payment_subscriptions` WRITE;
/*!40000 ALTER TABLE `payment_subscriptions` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_subscriptions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned DEFAULT NULL,
  `amount_received` decimal(12,2) NOT NULL DEFAULT 0.00,
  `amount_paid` decimal(12,2) DEFAULT 0.00,
  `payment_method` varchar(60) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `reference` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_payments_date` (`payment_date`),
  KEY `idx_payments_status` (`status`),
  KEY `idx_payments_student` (`student_id`)
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` (`id`, `student_id`, `amount_received`, `amount_paid`, `payment_method`, `payment_date`, `status`, `reference`, `created_at`) VALUES (1,1,4303623.00,0.00,'Cheque','2026-04-01','verified',NULL,'2026-06-20 06:58:56'),(2,1,1154598.00,0.00,'Mobile Money','2026-01-13','verified',NULL,'2026-06-20 06:58:56'),(3,1,2373654.00,0.00,'POS','2026-02-04','pending',NULL,'2026-06-20 06:58:56'),(4,1,903361.00,0.00,'Bank Transfer','2026-02-03','pending',NULL,'2026-06-20 06:58:56'),(5,1,516178.00,0.00,'Mobile Money','2026-04-15','approved',NULL,'2026-06-20 06:58:56'),(6,1,3369769.00,0.00,'Bank Transfer','2026-04-06','approved',NULL,'2026-06-20 06:58:56'),(7,1,1195561.00,0.00,'Bank Transfer','2026-02-28','verified',NULL,'2026-06-20 06:58:56'),(8,1,2818435.00,0.00,'Bank Transfer','2026-04-03','approved',NULL,'2026-06-20 06:58:56'),(9,1,1694306.00,0.00,'POS','2026-05-28','verified',NULL,'2026-06-20 06:58:56'),(10,1,1310012.00,0.00,'Bank Transfer','2026-05-23','pending',NULL,'2026-06-20 06:58:56'),(11,2,4079351.00,0.00,'Cheque','2026-01-18','approved',NULL,'2026-06-20 06:58:56'),(12,2,3786321.00,0.00,'Mobile Money','2026-05-14','approved',NULL,'2026-06-20 06:58:56'),(13,2,4845372.00,0.00,'Cheque','2026-06-12','verified',NULL,'2026-06-20 06:58:56'),(14,2,2205793.00,0.00,'Cheque','2026-02-07','verified',NULL,'2026-06-20 06:58:56'),(15,2,3532582.00,0.00,'Cheque','2026-02-11','pending',NULL,'2026-06-20 06:58:56'),(16,2,4559246.00,0.00,'POS','2026-01-07','pending',NULL,'2026-06-20 06:58:56'),(17,2,1664302.00,0.00,'Bank Transfer','2026-02-24','pending',NULL,'2026-06-20 06:58:56'),(18,2,231198.00,0.00,'Cash','2025-12-28','approved',NULL,'2026-06-20 06:58:56'),(19,2,371793.00,0.00,'Mobile Money','2025-12-30','pending',NULL,'2026-06-20 06:58:56'),(20,2,4921083.00,0.00,'Bank Transfer','2026-03-18','pending',NULL,'2026-06-20 06:58:56'),(21,3,1347820.00,0.00,'Cheque','2026-06-13','pending',NULL,'2026-06-20 06:58:56'),(22,3,679021.00,0.00,'Mobile Money','2026-03-04','approved',NULL,'2026-06-20 06:58:56'),(23,3,841699.00,0.00,'Cash','2025-12-25','pending',NULL,'2026-06-20 06:58:56'),(24,3,2118353.00,0.00,'Cash','2026-05-22','verified',NULL,'2026-06-20 06:58:56'),(25,3,1529731.00,0.00,'Bank Transfer','2026-01-03','verified',NULL,'2026-06-20 06:58:56'),(26,3,150061.00,0.00,'Cash','2026-05-06','approved',NULL,'2026-06-20 06:58:56'),(27,3,2099931.00,0.00,'Mobile Money','2026-01-17','approved',NULL,'2026-06-20 06:58:56'),(28,3,3984452.00,0.00,'Mobile Money','2026-04-29','verified',NULL,'2026-06-20 06:58:56'),(29,3,1757402.00,0.00,'Bank Transfer','2026-01-08','pending',NULL,'2026-06-20 06:58:56'),(30,3,2363593.00,0.00,'Cash','2026-04-15','pending',NULL,'2026-06-20 06:58:56'),(31,4,4897316.00,0.00,'Cash','2026-06-06','approved',NULL,'2026-06-20 06:58:56'),(32,4,4530396.00,0.00,'POS','2026-03-04','approved',NULL,'2026-06-20 06:58:56'),(33,4,2981352.00,0.00,'Bank Transfer','2026-01-17','pending',NULL,'2026-06-20 06:58:56'),(34,4,1748722.00,0.00,'Bank Transfer','2026-06-14','pending',NULL,'2026-06-20 06:58:56'),(35,4,231509.00,0.00,'Cheque','2026-01-22','pending',NULL,'2026-06-20 06:58:56'),(36,4,306115.00,0.00,'Cash','2026-01-13','approved',NULL,'2026-06-20 06:58:56'),(37,4,4653839.00,0.00,'Cheque','2026-04-17','pending',NULL,'2026-06-20 06:58:56'),(38,4,3217739.00,0.00,'Mobile Money','2026-04-10','approved',NULL,'2026-06-20 06:58:56'),(39,4,1228940.00,0.00,'Mobile Money','2026-05-09','pending',NULL,'2026-06-20 06:58:56'),(40,4,1651005.00,0.00,'Cheque','2026-01-06','approved',NULL,'2026-06-20 06:58:56'),(41,5,4721389.00,0.00,'POS','2026-02-09','approved',NULL,'2026-06-20 06:58:56'),(42,5,149174.00,0.00,'POS','2026-03-09','approved',NULL,'2026-06-20 06:58:56'),(43,5,617859.00,0.00,'Mobile Money','2025-12-25','approved',NULL,'2026-06-20 06:58:56'),(44,5,3024579.00,0.00,'POS','2025-12-30','approved',NULL,'2026-06-20 06:58:56'),(45,5,4439374.00,0.00,'Cheque','2026-05-05','verified',NULL,'2026-06-20 06:58:56'),(46,5,333072.00,0.00,'Mobile Money','2026-05-04','pending',NULL,'2026-06-20 06:58:56'),(47,5,3767992.00,0.00,'Cash','2026-06-20','pending',NULL,'2026-06-20 06:58:56'),(48,5,189456.00,0.00,'Cheque','2026-06-15','verified',NULL,'2026-06-20 06:58:56'),(49,5,3666993.00,0.00,'Cash','2026-04-25','approved',NULL,'2026-06-20 06:58:56'),(50,5,4837535.00,0.00,'POS','2026-03-31','approved',NULL,'2026-06-20 06:58:56');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_allowances`
--

DROP TABLE IF EXISTS `payroll_allowances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_allowances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `allowance_type` varchar(100) NOT NULL,
  `amount` decimal(12,2) DEFAULT 0.00,
  `month` varchar(20) NOT NULL,
  `is_recurring` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_allowances`
--

LOCK TABLES `payroll_allowances` WRITE;
/*!40000 ALTER TABLE `payroll_allowances` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_allowances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_approvals`
--

DROP TABLE IF EXISTS `payroll_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_approvals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payroll_run_id` int(11) NOT NULL,
  `level` enum('HR','PayrollOfficer','Bursar','DirectorFinance') NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_run_level` (`payroll_run_id`,`level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_approvals`
--

LOCK TABLES `payroll_approvals` WRITE;
/*!40000 ALTER TABLE `payroll_approvals` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_approvals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_bonuses`
--

DROP TABLE IF EXISTS `payroll_bonuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_bonuses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `bonus_type` varchar(100) NOT NULL,
  `amount` decimal(12,2) DEFAULT 0.00,
  `month` varchar(20) NOT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_bonuses`
--

LOCK TABLES `payroll_bonuses` WRITE;
/*!40000 ALTER TABLE `payroll_bonuses` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_bonuses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_deductions`
--

DROP TABLE IF EXISTS `payroll_deductions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_deductions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `deduction_type` varchar(100) NOT NULL,
  `amount` decimal(12,2) DEFAULT 0.00,
  `month` varchar(20) NOT NULL,
  `is_recurring` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_deductions`
--

LOCK TABLES `payroll_deductions` WRITE;
/*!40000 ALTER TABLE `payroll_deductions` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_deductions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_details`
--

DROP TABLE IF EXISTS `payroll_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payroll_run_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `basic_salary` decimal(12,2) DEFAULT 0.00,
  `total_allowances` decimal(12,2) DEFAULT 0.00,
  `overtime_pay` decimal(12,2) DEFAULT 0.00,
  `bonuses` decimal(12,2) DEFAULT 0.00,
  `gross_pay` decimal(12,2) DEFAULT 0.00,
  `paye_tax` decimal(12,2) DEFAULT 0.00,
  `nssf_employee` decimal(12,2) DEFAULT 0.00,
  `nssf_employer` decimal(12,2) DEFAULT 0.00,
  `other_deductions` decimal(12,2) DEFAULT 0.00,
  `leave_deductions` decimal(12,2) DEFAULT 0.00,
  `net_pay` decimal(12,2) DEFAULT 0.00,
  `paid_leave_days` decimal(5,1) DEFAULT 0.0,
  `unpaid_leave_days` decimal(5,1) DEFAULT 0.0,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `payment_date` date DEFAULT NULL,
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_details`
--

LOCK TABLES `payroll_details` WRITE;
/*!40000 ALTER TABLE `payroll_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_employees`
--

DROP TABLE IF EXISTS `payroll_employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account` varchar(50) DEFAULT NULL,
  `bank_code` varchar(20) DEFAULT NULL,
  `tax_identification` varchar(50) DEFAULT NULL,
  `nssf_number` varchar(50) DEFAULT NULL,
  `salary_type` enum('monthly','annual') DEFAULT 'monthly',
  `salary_grade` varchar(50) DEFAULT NULL,
  `basic_salary` decimal(12,2) DEFAULT 0.00,
  `hire_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_employees`
--

LOCK TABLES `payroll_employees` WRITE;
/*!40000 ALTER TABLE `payroll_employees` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_overtime`
--

DROP TABLE IF EXISTS `payroll_overtime`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_overtime` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `hours` decimal(8,2) DEFAULT 0.00,
  `rate` decimal(10,2) DEFAULT 0.00,
  `total_pay` decimal(12,2) DEFAULT 0.00,
  `month` varchar(20) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_overtime`
--

LOCK TABLES `payroll_overtime` WRITE;
/*!40000 ALTER TABLE `payroll_overtime` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_overtime` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_records`
--

DROP TABLE IF EXISTS `payroll_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `month` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `gross_salary` decimal(12,2) DEFAULT 0.00,
  `total_allowances` decimal(12,2) DEFAULT 0.00,
  `total_deductions` decimal(12,2) DEFAULT 0.00,
  `nssf_tax` decimal(12,2) DEFAULT 0.00,
  `paye_tax` decimal(12,2) DEFAULT 0.00,
  `net_salary` decimal(12,2) DEFAULT 0.00,
  `total_fees_collected` decimal(12,2) DEFAULT 0.00,
  `net_payment` decimal(12,2) DEFAULT 0.00,
  `processed_by` int(11) DEFAULT 0,
  `processing_date` datetime DEFAULT current_timestamp(),
  `status` enum('Draft','Processed','Approved','Paid') DEFAULT 'Draft',
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_staff_period` (`staff_id`,`month`,`year`),
  KEY `idx_period` (`month`,`year`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_records`
--

LOCK TABLES `payroll_records` WRITE;
/*!40000 ALTER TABLE `payroll_records` DISABLE KEYS */;
INSERT INTO `payroll_records` (`id`, `staff_id`, `month`, `year`, `gross_salary`, `total_allowances`, `total_deductions`, `nssf_tax`, `paye_tax`, `net_salary`, `total_fees_collected`, `net_payment`, `processed_by`, `processing_date`, `status`, `approved_by`, `approved_at`) VALUES (1,1,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(2,2,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(3,3,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(4,4,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(5,5,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(6,6,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(7,7,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(8,8,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(9,9,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(10,10,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(11,11,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(12,12,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(13,13,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(14,14,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(15,15,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(16,16,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(17,17,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(18,18,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(19,19,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(20,20,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(21,21,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(22,22,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(23,23,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(24,24,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(25,25,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(26,51,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL);
/*!40000 ALTER TABLE `payroll_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_runs`
--

DROP TABLE IF EXISTS `payroll_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payroll_runs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `period` varchar(20) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `total_gross` decimal(15,2) DEFAULT 0.00,
  `total_deductions` decimal(15,2) DEFAULT 0.00,
  `total_net` decimal(15,2) DEFAULT 0.00,
  `status` enum('draft','approved','processed','paid') DEFAULT 'draft',
  `created_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_runs`
--

LOCK TABLES `payroll_runs` WRITE;
/*!40000 ALTER TABLE `payroll_runs` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_runs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payslips`
--

DROP TABLE IF EXISTS `payslips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `payslips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payslip_number` varchar(50) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `payroll_run_id` int(11) DEFAULT NULL,
  `payroll_detail_id` int(11) DEFAULT NULL,
  `salary_month` varchar(20) NOT NULL,
  `basic_salary` decimal(15,2) DEFAULT NULL,
  `allowances` decimal(15,2) DEFAULT NULL,
  `gross_salary` decimal(15,2) DEFAULT NULL,
  `deductions` decimal(15,2) DEFAULT NULL,
  `payment_ref` varchar(100) DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `net_salary` decimal(15,2) DEFAULT NULL,
  `payment_method` enum('bank_transfer','cash','cheque') DEFAULT 'bank_transfer',
  `payment_date` date DEFAULT NULL,
  `generated_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `viewed_by_employee` tinyint(1) DEFAULT 0,
  `viewed_date` timestamp NULL DEFAULT NULL,
  `status` enum('generated','approved','paid') DEFAULT 'generated',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `payslip_number` (`payslip_number`),
  KEY `idx_payslip_run` (`payroll_run_id`),
  KEY `idx_payslip_detail` (`payroll_detail_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payslips`
--

LOCK TABLES `payslips` WRITE;
/*!40000 ALTER TABLE `payslips` DISABLE KEYS */;
/*!40000 ALTER TABLE `payslips` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pending_students`
--

DROP TABLE IF EXISTS `pending_students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pending_students` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) NOT NULL,
  `student_number` varchar(60) NOT NULL,
  `program` varchar(120) DEFAULT NULL,
  `level` varchar(20) DEFAULT '1',
  `intake_year` varchar(4) DEFAULT NULL,
  `intake_period` varchar(20) DEFAULT 'January',
  `phone` varchar(30) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `submitted_by` int(10) unsigned DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending_approval',
  `approval_request_id` int(10) unsigned DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_approval` (`approval_request_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pending_students`
--

LOCK TABLES `pending_students` WRITE;
/*!40000 ALTER TABLE `pending_students` DISABLE KEYS */;
INSERT INTO `pending_students` (`id`, `first_name`, `middle_name`, `last_name`, `student_number`, `program`, `level`, `intake_year`, `intake_period`, `phone`, `email`, `date_of_birth`, `submitted_by`, `status`, `approval_request_id`, `rejection_reason`, `created_at`) VALUES (1,'Akello',NULL,'Grace','ISNM-2026-006','Diploma Nursing','1','2026','January',NULL,NULL,NULL,5,'pending_approval',4,NULL,'2026-06-20 04:47:50'),(2,'Bwire',NULL,'John','ISNM-2026-007','Certificate Midwifery','1','2026','January',NULL,NULL,NULL,5,'pending_approval',5,NULL,'2026-06-19 07:47:50');
/*!40000 ALTER TABLE `pending_students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `performance_indicators`
--

DROP TABLE IF EXISTS `performance_indicators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `performance_indicators` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `indicator_name` varchar(200) DEFAULT NULL,
  `target_value` decimal(12,2) DEFAULT NULL,
  `actual_value` decimal(12,2) DEFAULT NULL,
  `period` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `status` varchar(30) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `performance_indicators`
--

LOCK TABLES `performance_indicators` WRITE;
/*!40000 ALTER TABLE `performance_indicators` DISABLE KEYS */;
/*!40000 ALTER TABLE `performance_indicators` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `permissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `portal_messages`
--

DROP TABLE IF EXISTS `portal_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `portal_messages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) DEFAULT NULL,
  `recipient_type` varchar(30) DEFAULT 'individual',
  `subject` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `portal_messages`
--

LOCK TABLES `portal_messages` WRITE;
/*!40000 ALTER TABLE `portal_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `portal_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `professional_licenses`
--

DROP TABLE IF EXISTS `professional_licenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `professional_licenses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(10) unsigned NOT NULL,
  `license_number` varchar(100) DEFAULT NULL,
  `license_type` varchar(100) DEFAULT NULL,
  `issuing_body` varchar(200) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` varchar(30) DEFAULT 'active',
  `document_path` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `professional_licenses`
--

LOCK TABLES `professional_licenses` WRITE;
/*!40000 ALTER TABLE `professional_licenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `professional_licenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quality_assurance`
--

DROP TABLE IF EXISTS `quality_assurance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quality_assurance` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `review_type` varchar(100) DEFAULT NULL,
  `reviewer_id` int(10) unsigned DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `findings` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `status` varchar(30) DEFAULT 'in_progress',
  `review_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quality_assurance`
--

LOCK TABLES `quality_assurance` WRITE;
/*!40000 ALTER TABLE `quality_assurance` DISABLE KEYS */;
/*!40000 ALTER TABLE `quality_assurance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recruitment`
--

DROP TABLE IF EXISTS `recruitment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recruitment` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `position_title` varchar(200) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `vacancies` int(11) DEFAULT 1,
  `requirements` text DEFAULT NULL,
  `status` varchar(30) DEFAULT 'open',
  `posted_date` date DEFAULT NULL,
  `closing_date` date DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recruitment`
--

LOCK TABLES `recruitment` WRITE;
/*!40000 ALTER TABLE `recruitment` DISABLE KEYS */;
/*!40000 ALTER TABLE `recruitment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recycle_bin`
--

DROP TABLE IF EXISTS `recycle_bin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recycle_bin` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `original_table` varchar(100) DEFAULT NULL,
  `original_id` int(10) unsigned DEFAULT NULL,
  `data` longtext DEFAULT NULL,
  `deleted_by` int(10) unsigned DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `restored_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recycle_bin`
--

LOCK TABLES `recycle_bin` WRITE;
/*!40000 ALTER TABLE `recycle_bin` DISABLE KEYS */;
/*!40000 ALTER TABLE `recycle_bin` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registrar_student_registration`
--

DROP TABLE IF EXISTS `registrar_student_registration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `registrar_student_registration` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` varchar(100) NOT NULL,
  `registration_date` date DEFAULT NULL,
  `registration_status` varchar(50) DEFAULT 'Registered',
  `registered_by` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_student` (`student_id`),
  KEY `idx_year` (`academic_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registrar_student_registration`
--

LOCK TABLES `registrar_student_registration` WRITE;
/*!40000 ALTER TABLE `registrar_student_registration` DISABLE KEYS */;
/*!40000 ALTER TABLE `registrar_student_registration` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registrar_transcript_requests`
--

DROP TABLE IF EXISTS `registrar_transcript_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `registrar_transcript_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `request_type` varchar(50) DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `requested_by` int(10) unsigned DEFAULT NULL,
  `processed_by` int(10) unsigned DEFAULT NULL,
  `fee_paid` decimal(10,2) DEFAULT 0.00,
  `generated_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registrar_transcript_requests`
--

LOCK TABLES `registrar_transcript_requests` WRITE;
/*!40000 ALTER TABLE `registrar_transcript_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `registrar_transcript_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `requirement_history`
--

DROP TABLE IF EXISTS `requirement_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `requirement_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `applicant_id` int(11) NOT NULL,
  `requirement_id` int(11) DEFAULT NULL,
  `action` enum('Submitted','Verified','Rejected','Updated','Reset') NOT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rh_applicant` (`applicant_id`),
  KEY `idx_rh_requirement` (`requirement_id`),
  KEY `idx_rh_action` (`action`),
  KEY `idx_rh_created` (`created_at`),
  CONSTRAINT `requirement_history_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `requirement_history_ibfk_2` FOREIGN KEY (`requirement_id`) REFERENCES `admission_requirements` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `requirement_history`
--

LOCK TABLES `requirement_history` WRITE;
/*!40000 ALTER TABLE `requirement_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `requirement_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `research_projects`
--

DROP TABLE IF EXISTS `research_projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `research_projects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `project_title` varchar(300) DEFAULT NULL,
  `principal_investigator` varchar(200) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `funding_source` varchar(200) DEFAULT NULL,
  `budget` decimal(12,2) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(30) DEFAULT 'active',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `research_projects`
--

LOCK TABLES `research_projects` WRITE;
/*!40000 ALTER TABLE `research_projects` DISABLE KEYS */;
/*!40000 ALTER TABLE `research_projects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `result_publications`
--

DROP TABLE IF EXISTS `result_publications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `result_publications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publication_number` varchar(50) NOT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `status` enum('draft','scheduled','published','withdrawn') DEFAULT 'draft',
  `published_by` int(11) DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `scheduled_date` datetime DEFAULT NULL,
  `notification_sent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rp_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `result_publications`
--

LOCK TABLES `result_publications` WRITE;
/*!40000 ALTER TABLE `result_publications` DISABLE KEYS */;
/*!40000 ALTER TABLE `result_publications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` (`id`, `name`, `description`, `created_at`) VALUES (1,'Director General',NULL,'2026-06-10 05:56:09'),(2,'CEO',NULL,'2026-06-10 05:56:09'),(3,'Director Academics',NULL,'2026-06-10 05:56:09'),(4,'Director Finance',NULL,'2026-06-10 05:56:09'),(5,'Director ICT',NULL,'2026-06-10 05:56:09'),(6,'School Principal',NULL,'2026-06-10 05:56:09'),(7,'Deputy Principal',NULL,'2026-06-10 05:56:09'),(8,'Academic Registrar',NULL,'2026-06-10 05:56:09'),(9,'HR Manager',NULL,'2026-06-10 05:56:09'),(10,'School Secretary',NULL,'2026-06-10 05:56:09'),(11,'School Librarian',NULL,'2026-06-10 05:56:09'),(12,'Head Nursing',NULL,'2026-06-10 05:56:09'),(13,'Head Midwifery',NULL,'2026-06-10 05:56:09'),(14,'Senior Lecturers',NULL,'2026-06-10 05:56:09'),(15,'Lecturers',NULL,'2026-06-10 05:56:09'),(16,'Matrons',NULL,'2026-06-10 05:56:09'),(17,'Wardens',NULL,'2026-06-10 05:56:09'),(18,'Sickbay',NULL,'2026-06-10 05:56:09'),(19,'Drivers',NULL,'2026-06-10 05:56:09'),(20,'Security',NULL,'2026-06-10 05:56:09'),(21,'Storekeeper',NULL,'2026-06-10 05:56:09'),(22,'Guild President',NULL,'2026-06-10 05:56:09'),(23,'Computer Lab Manager',NULL,'2026-06-10 05:56:09'),(24,'School Bursar',NULL,'2026-06-10 05:56:09'),(25,'Store Keeper','Store inventory','2026-06-13 09:38:49'),(26,'Director Admissions & Requirements','Admissions management','2026-06-13 09:38:49'),(27,'Bursar','Bursar assistant','2026-06-13 09:38:49');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `room_inspections`
--

DROP TABLE IF EXISTS `room_inspections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `room_inspections` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `room_id` int(10) unsigned DEFAULT NULL,
  `inspector_id` int(10) unsigned DEFAULT NULL,
  `inspection_date` date DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `findings` text DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `room_inspections`
--

LOCK TABLES `room_inspections` WRITE;
/*!40000 ALTER TABLE `room_inspections` DISABLE KEYS */;
/*!40000 ALTER TABLE `room_inspections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `scholarships`
--

DROP TABLE IF EXISTS `scholarships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `scholarships` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `scholarship_name` varchar(200) DEFAULT NULL,
  `provider` varchar(200) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scholarships`
--

LOCK TABLES `scholarships` WRITE;
/*!40000 ALTER TABLE `scholarships` DISABLE KEYS */;
/*!40000 ALTER TABLE `scholarships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `security_access_logs`
--

DROP TABLE IF EXISTS `security_access_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `security_access_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `access_type` varchar(50) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `status` varchar(30) DEFAULT NULL,
  `accessed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_access_logs`
--

LOCK TABLES `security_access_logs` WRITE;
/*!40000 ALTER TABLE `security_access_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `security_access_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `security_incidents`
--

DROP TABLE IF EXISTS `security_incidents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `security_incidents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `incident_type` varchar(100) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `reported_by` int(10) unsigned DEFAULT NULL,
  `status` varchar(30) DEFAULT 'reported',
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_incidents`
--

LOCK TABLES `security_incidents` WRITE;
/*!40000 ALTER TABLE `security_incidents` DISABLE KEYS */;
/*!40000 ALTER TABLE `security_incidents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `semesters`
--

DROP TABLE IF EXISTS `semesters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `semesters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `academic_year` varchar(20) NOT NULL,
  `semester_name` varchar(100) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_current` tinyint(1) DEFAULT 0,
  `status` varchar(50) DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `semesters`
--

LOCK TABLES `semesters` WRITE;
/*!40000 ALTER TABLE `semesters` DISABLE KEYS */;
/*!40000 ALTER TABLE `semesters` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sickbay_settings`
--

DROP TABLE IF EXISTS `sickbay_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sickbay_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sickbay_settings`
--

LOCK TABLES `sickbay_settings` WRITE;
/*!40000 ALTER TABLE `sickbay_settings` DISABLE KEYS */;
INSERT INTO `sickbay_settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES (1,'reorder_level','10','2026-06-20 05:59:38'),(2,'low_stock_threshold','10','2026-06-20 05:59:38'),(3,'auto_status','1','2026-06-20 05:59:38'),(4,'notify_low_stock','1','2026-06-20 05:59:38'),(5,'default_theme','default-blue','2026-06-20 05:59:38');
/*!40000 ALTER TABLE `sickbay_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sickness_directory`
--

DROP TABLE IF EXISTS `sickness_directory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sickness_directory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sickness_code` varchar(20) NOT NULL,
  `sickness_name` varchar(255) NOT NULL,
  `category` enum('Infectious','Non-Infectious','Chronic','Injury','Mental Health','Nutritional','Other') DEFAULT 'Other',
  `common_symptoms` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_contagious` tinyint(1) DEFAULT 0,
  `typical_treatment` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `sickness_code` (`sickness_code`),
  KEY `sickness_name` (`sickness_name`),
  KEY `category` (`category`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sickness_directory`
--

LOCK TABLES `sickness_directory` WRITE;
/*!40000 ALTER TABLE `sickness_directory` DISABLE KEYS */;
INSERT INTO `sickness_directory` (`id`, `sickness_code`, `sickness_name`, `category`, `common_symptoms`, `description`, `is_contagious`, `typical_treatment`, `status`, `created_by`, `created_at`, `updated_at`) VALUES (1,'MLR','Malaria','Infectious','Fever, chills, headache, sweating, fatigue','Mosquito-borne parasitic infection common in tropical regions',0,'Artemisinin-based combination therapy, antimalarials','Active',NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(2,'TYP','Typhoid','Infectious','Prolonged fever, abdominal pain, headache, constipation or diarrhea','Bacterial infection spread through contaminated food/water',1,'Antibiotics (ciprofloxacin, azithromycin), hydration','Active',NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(3,'FLU','Influenza','Infectious','Fever, cough, sore throat, body aches, fatigue','Viral respiratory infection spread through droplets',1,'Rest, fluids, antipyretics, antivirals if severe','Active',NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(4,'COLD','Common Cold','Infectious','Runny nose, sneezing, sore throat, cough, mild fever','Viral upper respiratory tract infection',1,'Rest, antihistamines, decongestants, vitamin C','Active',NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(5,'URTI','Upper Respiratory Tract Infection','Infectious','Cough, sore throat, nasal congestion, fever','Bacterial or viral infection of upper airways',1,'Antibiotics if bacterial, rest, fluids','Active',NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(6,'HDCH','Headache/Tension Headache','Non-Infectious','Head pain, pressure around forehead, neck tension','Common tension-type headache from stress or fatigue',0,'Rest, analgesics (paracetamol, ibuprofen)','Active',NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(7,'GSTR','Gastritis','Non-Infectious','Abdominal pain, nausea, bloating, indigestion','Inflammation of stomach lining from diet, stress, or infection',0,'Antacids, dietary changes, proton pump inhibitors','Active',NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(8,'DIAR','Diarrhea','Infectious','Loose watery stools, abdominal cramps, dehydration','Common infection from contaminated food/water or viruses',1,'ORS, hydration, antidiarrheals, antibiotics if bacterial','Active',NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(9,'ALLG','Allergic Reaction','Non-Infectious','Rash, itching, sneezing, watery eyes, swelling','Immune response to allergens (food, dust, pollen, drugs)',0,'Antihistamines, corticosteroids, avoid triggers','Active',NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(10,'INJR','Injury/Accident','Injury','Pain, swelling, bruising, bleeding, limited mobility','Physical trauma from falls, sports, or accidents',0,'First aid, rest, ice, compression, elevation, analgesics','Active',NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(11,'ANEM','Anemia','Nutritional','Fatigue, weakness, pale skin, shortness of breath, dizziness','Low red blood cell count from iron deficiency or other causes',0,'Iron supplements, dietary changes, B12 if needed','Active',NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(12,'MALN','Malnutrition','Nutritional','Weight loss, fatigue, poor growth, weakened immunity','Inadequate nutrient intake affecting overall health',0,'Nutritional supplementation, diet counseling','Active',NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(13,'CONS','Constipation','Non-Infectious','Infrequent bowel movements, straining, hard stools','Common digestive issue from diet or lifestyle factors',0,'Increased fiber intake, hydration, laxatives if needed','Active',NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(14,'SORE','Sore Throat','Infectious','Pain or scratchiness in throat, difficulty swallowing','Viral or bacterial throat infection',1,'Warm salt water gargle, lozenges, antibiotics if strep','Active',NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(15,'EYEI','Eye Infection','Infectious','Redness, itching, discharge, swollen eyelids','Bacterial or viral conjunctivitis',1,'Antibiotic or antiviral eye drops, hygiene','Active',NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(16,'SKIN','Skin Infection/Rash','Infectious','Redness, itching, bumps, blisters, peeling','Fungal, bacterial, or viral skin infection',1,'Topical or oral antibiotics/antifungals, hygiene','Active',NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(17,'FATG','Fatigue/General Malaise','Non-Infectious','Tiredness, low energy, reduced motivation','General feeling of being unwell without specific diagnosis',0,'Rest, nutrition, hydration, stress management','Active',NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(18,'MSTR','Menstrual Cramps','Non-Infectious','Lower abdominal pain, back pain, nausea during menstruation','Painful menstrual periods common in young women',0,'Analgesics, heat therapy, rest, NSAIDs','Active',NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(19,'ANXT','Anxiety/Stress','Mental Health','Worry, restlessness, rapid heartbeat, difficulty concentrating','Mental health condition common among students under academic pressure',0,'Counseling, stress management, relaxation techniques','Active',NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(20,'BACK','Back Pain','Non-Infectious','Lower or upper back pain, stiffness, muscle tension','Musculoskeletal pain from poor posture, heavy lifting, or strain',0,'Rest, analgesics, physiotherapy, posture correction','Active',NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(21,'THRP','Throat Infection/Pharyngitis','Infectious','Sore throat, red tonsils, swollen lymph nodes, fever','Inflammation of the pharynx from viral or bacterial infection',1,'Antibiotics if bacterial, rest, warm fluids','Active',NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(22,'TOOT','Toothache','Non-Infectious','Tooth pain, sensitivity, swelling around tooth','Dental pain from cavities, infection, or impaction',0,'Analgesics, dental referral, antibiotics if infected','Active',NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(23,'URIN','Urinary Tract Infection','Infectious','Painful urination, frequent urination, lower abdominal pain','Bacterial infection of the urinary tract',0,'Antibiotics, increased fluid intake, cranberry','Active',NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(24,'ACNE','Acne/Skin Breakout','Non-Infectious','Pimples, blackheads, whiteheads, inflamed skin','Common skin condition from hormonal changes and stress',0,'Topical treatments, hygiene, dietary changes','Active',NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44'),(25,'FUNG','Fungal Infection','Infectious','Itching, redness, peeling skin, rash with defined edges','Fungal skin infection common in tropical climates',1,'Antifungal creams or oral medication, keep area dry','Active',NULL,'2026-06-20 05:53:44','2026-06-20 05:53:44');
/*!40000 ALTER TABLE `sickness_directory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sports_events`
--

DROP TABLE IF EXISTS `sports_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sports_events` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `sport_type` varchar(100) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `event_date` datetime DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(30) DEFAULT 'upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sports_events`
--

LOCK TABLES `sports_events` WRITE;
/*!40000 ALTER TABLE `sports_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `sports_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sports_teams`
--

DROP TABLE IF EXISTS `sports_teams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sports_teams` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `sport_type` varchar(100) DEFAULT NULL,
  `coach_id` int(10) unsigned DEFAULT NULL,
  `captain_id` int(10) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sports_teams`
--

LOCK TABLES `sports_teams` WRITE;
/*!40000 ALTER TABLE `sports_teams` DISABLE KEYS */;
/*!40000 ALTER TABLE `sports_teams` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff`
--

DROP TABLE IF EXISTS `staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` varchar(20) DEFAULT NULL,
  `full_name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `position` varchar(150) DEFAULT NULL,
  `department` varchar(150) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Active',
  `hire_date` date DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `is_first_login` tinyint(1) DEFAULT 1,
  `password_changed` tinyint(1) DEFAULT 0,
  `profile_photo` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff`
--

LOCK TABLES `staff` WRITE;
/*!40000 ALTER TABLE `staff` DISABLE KEYS */;
INSERT INTO `staff` (`id`, `staff_id`, `full_name`, `email`, `phone`, `password`, `role_id`, `position`, `department`, `status`, `hire_date`, `last_login`, `login_attempts`, `locked_until`, `is_first_login`, `password_changed`, `profile_photo`, `address`, `created_at`, `updated_at`) VALUES (1,NULL,'Doris Joy Namugwanya','directorgeneral@igangaschoolofnursingandmidwifery.ac.ug','','$2y$10$9OkGyLqxrkWGQ380t05Kj./Gzu7DBUNM75BIileuHsw5nFDzPyksa',1,'Director General','Executive Office','Active','2026-06-09','2026-06-27 07:26:14',0,NULL,0,1,NULL,NULL,'2026-06-10 05:56:10','2026-06-27 14:26:14'),(2,NULL,'Doris Joy','ceo@igangaschoolofnursingandmidwifery.ac.ug','','$2y$10$xXJsVElSZzu.wTNPpSKh2e9mYwUnEz3Fh6N8LKh1qrwyaXbRDqZyC',2,'Chief Executive Officer','Executive Office','Active','2026-06-09','2026-06-25 09:22:04',0,NULL,0,1,NULL,'','2026-06-10 05:56:10','2026-06-25 16:22:04'),(3,NULL,'Stephen Bywaka','directoracademic@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$0W2zpD9Mx9jrzFyGY0wzP.vfdAB8wu8JQU.UNPhQ73EM9ABy36r0q',3,'Director Academics','Academic Affairs','Active','2026-06-09','2026-06-25 03:11:18',0,NULL,0,1,NULL,NULL,'2026-06-10 05:56:10','2026-06-25 10:11:18'),(4,NULL,'Finance Director','finance@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$1B4WKBhbkTe8zAYkJbbEe.D9NtkuxflDZN356rGzPvD16QrWCKywu',4,'Director Finance','Finance Department','Active','2026-06-09','2026-06-25 09:22:40',0,NULL,0,1,NULL,NULL,'2026-06-10 05:56:10','2026-06-25 16:22:40'),(5,NULL,'School Principal','principal@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$4u3./3VtmlkZAT2xuF7MLudpeJ4AbZLKjxXryhjGKvaFeulUimvGW',6,'School Principal','Academic Affairs','Active','2026-06-09','2026-06-25 03:44:45',0,NULL,0,1,NULL,NULL,'2026-06-10 05:56:11','2026-06-25 10:44:45'),(6,NULL,'Deputy Principal','dep-principal@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$JszQnm6ppJ6ggmPqkZUHp.qg50dfBBcH7IHXh.2buKGazBNr3lATi',7,'Deputy Principal','Academic Affairs','Active','2026-06-09','2026-06-25 03:33:47',0,NULL,0,1,NULL,NULL,'2026-06-10 05:56:11','2026-06-25 10:33:47'),(7,NULL,'Academic Registrar','academicregistrar@igangaschoolofnursingandmidwifery.ac.ug','0772514889','$2y$10$GO1MFp48tQvP0o4d4DlMZukTH6epueBuCaAu0EXKD0ZglCNFno5zi',8,'Academic Registrar','Academic Affairs','Active','2026-06-09','2026-06-26 06:36:27',0,NULL,0,1,NULL,'Lubas Road','2026-06-10 05:56:11','2026-06-26 13:36:27'),(8,NULL,'HR Manager','hr-manager@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$fE/SVKQqJ4BYu2QlLdvlou5Vs1ug7OOivy8hcCdXzctlpKUZwvfP.',9,'HR Manager','Human Resources','Active','2026-06-09','2026-06-26 06:04:57',0,NULL,0,1,NULL,NULL,'2026-06-10 05:56:14','2026-06-26 13:04:57'),(9,NULL,'School Secretary','secretary@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$rV7s4oFYEGX.6STyluPxRO7AHKRJdI5fEBqg1XJDX9NKfCXCuSuea',10,'School Secretary','Administrative Office','Active','2026-06-09','2026-06-25 01:36:49',0,NULL,0,1,NULL,NULL,'2026-06-10 05:56:23','2026-06-25 08:36:49'),(10,NULL,'School Librarian','library@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$P/fxbkdmQ75Q4rv7x1HXz.34No68cJNJLHqSPki02VjdGbiKO83iS',11,'School Librarian','Library Services','Active','2026-06-09','2026-06-21 08:38:59',0,NULL,0,1,NULL,NULL,'2026-06-10 05:56:29','2026-06-21 15:38:59'),(11,NULL,'Head of Nursing','nursing-dep@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$Iw8BStEfmuQ4THpt0djno.ZNV4KzveqG1R2yZtf2awMAz5u9EOi0a',12,'Head Nursing','Nursing Department','Active','2026-06-09','2026-06-13 03:14:38',0,NULL,0,1,NULL,NULL,'2026-06-10 05:56:31','2026-06-13 10:14:38'),(12,NULL,'Head of Midwifery','midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$.sB5xOu5VTfjRndsyBY71uCRuX.Bn6mEm6bqQjb/5L3EmzCcpARCu',13,'Head Midwifery','Midwifery Department','Active','2026-06-09','2026-06-13 03:14:38',0,NULL,0,1,NULL,NULL,'2026-06-10 05:56:33','2026-06-13 10:14:38'),(13,NULL,'Senior Lecturers','senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$331R3j5oa4oUjpgFDqZhTOANB4N8M41gU1CHXXIHg4LuylO6JMCwu',14,'Senior Lecturer','Academic Affairs','Active','2026-06-09','2026-06-13 03:14:38',0,NULL,0,1,NULL,NULL,'2026-06-10 05:56:33','2026-06-13 10:14:38'),(14,NULL,'Lecturers','lecturers@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$.kjo780DIjtfeTxVcarWq.mZcfcmxmCw.5c53/PaFXalTVBQMRCOG',15,'Lecturer','Academic Affairs','Active','2026-06-09','2026-06-13 03:14:38',0,NULL,0,1,NULL,NULL,'2026-06-10 05:56:34','2026-06-13 10:14:38'),(15,NULL,'Matron','matron@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$ymrXcnRhazxfrVpyNyaUk.R7naE6eUus6eFUEYdO0bw.HJmXOU7Qq',16,'Matrons','Student Affairs','Active','2026-06-09','2026-06-13 03:14:38',0,NULL,0,1,NULL,NULL,'2026-06-10 05:56:34','2026-06-13 10:14:38'),(16,NULL,'Warden','warden@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$5WAJaPKTb8xLi.SRfC6cD.UQ0JnCA5AqlRSS6aJdz9LD7C0gWtMty',17,'Wardens','Student Affairs','Active','2026-06-09','2026-06-13 03:14:38',0,NULL,0,1,NULL,NULL,'2026-06-10 05:56:34','2026-06-13 10:14:38'),(17,NULL,'Sickbay Officer','sickbay@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$xKCeFMFeDVhXZOxpEoQFPOBR8Cx60T7De1rIAnjAxaSSTmdwCN2Ym',18,'Sickbay','Support','Active','2026-06-09','2026-06-26 04:07:21',0,NULL,0,1,NULL,NULL,'2026-06-10 05:56:34','2026-06-26 11:07:21'),(18,NULL,'Driver','drivers@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$xZnL4zt/B7h0/E7SHNAhfe4MPYA4HhfioLU7qRQ0ORkv9eABxfIia',19,'Drivers','Transport','Active','2026-06-09','2026-06-13 03:14:39',0,NULL,0,1,NULL,NULL,'2026-06-10 05:56:35','2026-06-13 10:14:39'),(19,NULL,'Security Officer','security@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$H3mJR/813QrKDzaQMK/yC.HfM4mGpYwgPFmlZL3h/WyTSD4d5zsQq',20,'Security','Security Services','Active','2026-06-09','2026-06-13 03:14:39',0,NULL,0,1,NULL,NULL,'2026-06-10 05:56:35','2026-06-13 10:14:39'),(20,NULL,'Storekeeper','store@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$2BJLSl5d1x.KCCV83Unqv.LrM9MDrXGO.pm3Ly99plAGdjUJuxVhi',21,'Store Keeper','Facilities Management','Active','2026-06-09','2026-06-13 03:14:39',0,NULL,0,1,NULL,NULL,'2026-06-10 05:56:36','2026-06-13 10:14:39'),(21,NULL,'Guild President','guildpresident@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$2Acd3VjS07HN.YJHFjyzWOk9QsxmYpBY9oXDc1xwyPtKelUSpMtgi',22,'Guild President','Student Affairs','Active','2026-06-09','2026-06-13 03:14:39',0,NULL,0,1,NULL,NULL,'2026-06-10 05:56:36','2026-06-13 10:14:39'),(22,NULL,'Computer Lab Manager','computer-lab@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$KlyNxRbEDLRbU4XO1uP6Ru9jjXAJP8owjUaneUmAAiK9s4eDUZnM2',23,'Director ICT','Information Communication Technology','Active','2026-06-09','2026-06-26 23:55:46',0,NULL,0,1,NULL,NULL,'2026-06-10 05:56:36','2026-06-27 06:55:46'),(23,NULL,'Danny ICT Director','dannybict@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$6au4jFh5fu7rXKWuAoKDauv.h9sQ6ONfUaBiGydeqh7JU2sO1BYoi',5,'Director ICT','Information Technology','Active','2026-06-09','2026-06-26 23:38:31',0,NULL,0,1,NULL,NULL,'2026-06-10 05:56:36','2026-06-27 06:38:31'),(24,NULL,'Admissions Officer Derrick','admissions@igangaschoolofnursingandmidwifery.ac.ug','','$2y$10$tLG3brrbgq6IfcHkV1O95ujGlp892EyxpFezOmACyrKA2f3b17NkG',26,'Director Admissions & Requirements','Admissions','Active','2026-06-09','2026-06-25 07:25:54',0,NULL,1,1,NULL,NULL,'2026-06-10 05:56:37','2026-06-25 14:25:54'),(25,NULL,'School Bursar','bursar@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$WgxHRWfiQH.Wv3UgHkiKIODKCs9wTXTkSxuEgBkQ6OyxTby/Tp.GG',24,'School Bursar','Finance Department','Active','2026-06-10','2026-06-26 06:04:57',0,NULL,0,1,NULL,NULL,'2026-06-10 07:56:49','2026-06-26 13:04:57'),(51,'BURS002','Bursar','bursar.assistant@isnm.ac.ug',NULL,'$2y$10$U61BKsKqMuX1LajK/sSOme3yETx/qnoNw75CxEiBr7mX8pd.922v.',27,'Bursar','Finance Department','Active','2026-06-13',NULL,0,NULL,1,0,NULL,NULL,'2026-06-13 09:38:49','2026-06-13 09:38:49');
/*!40000 ALTER TABLE `staff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_activity_log`
--

DROP TABLE IF EXISTS `staff_activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff_activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) DEFAULT NULL,
  `activity_type` varchar(100) DEFAULT NULL,
  `activity_description` text DEFAULT NULL,
  `module_accessed` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=229 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_activity_log`
--

LOCK TABLES `staff_activity_log` WRITE;
/*!40000 ALTER TABLE `staff_activity_log` DISABLE KEYS */;
INSERT INTO `staff_activity_log` (`id`, `staff_id`, `activity_type`, `activity_description`, `module_accessed`, `ip_address`, `user_agent`, `created_at`) VALUES (1,1,'Login','User logged in successfully','authentication','::1','curl/8.19.0','2026-06-10 06:06:48'),(2,4,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-06-10 06:07:33'),(3,4,'Login','User logged in successfully','authentication','::1','curl/8.19.0','2026-06-10 06:16:40'),(4,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-06-10 06:27:04'),(5,25,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-06-10 07:57:13'),(6,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-06-10 08:02:10'),(7,9,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-06-10 13:12:56'),(8,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-17 10:34:12'),(9,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 05:18:02'),(10,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 09:13:54'),(11,2,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 09:15:02'),(12,2,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 09:20:03'),(13,3,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 09:20:34'),(14,3,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 09:21:34'),(15,4,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 09:21:40'),(16,4,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 09:23:30'),(17,5,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 09:23:54'),(18,5,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 09:24:29'),(19,6,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 09:25:03'),(20,6,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 09:34:20'),(21,25,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 09:34:25'),(22,25,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 09:35:04'),(23,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 09:35:42'),(24,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 16:31:15'),(25,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 17:01:54'),(26,2,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 17:01:58'),(27,25,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 17:03:51'),(28,25,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 17:50:46'),(29,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 17:50:50'),(30,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 18:29:09'),(31,25,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 18:29:16'),(32,25,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 18:55:42'),(33,7,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 18:56:46'),(34,7,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 20:17:40'),(35,7,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 20:17:44'),(36,7,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 20:17:51'),(37,7,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 20:17:56'),(38,7,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 20:18:39'),(39,23,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 20:19:17'),(40,23,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 20:20:09'),(41,22,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 20:20:23'),(42,22,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 20:36:57'),(43,22,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 20:37:05'),(44,22,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 20:37:13'),(45,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 20:37:22'),(46,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 20:44:48'),(47,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 20:44:53'),(48,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 20:47:22'),(49,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 20:47:26'),(50,7,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-19 03:38:19'),(51,17,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 05:33:02'),(52,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 06:52:59'),(53,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 10:31:17'),(54,2,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 10:31:21'),(55,2,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 11:07:27'),(56,25,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 11:07:31'),(57,25,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 11:08:03'),(58,17,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 11:08:13'),(59,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 18:16:50'),(60,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 19:18:28'),(61,25,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 19:18:33'),(62,25,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 22:41:44'),(63,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 22:41:52'),(64,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 07:45:04'),(65,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 09:19:19'),(66,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 09:19:30'),(67,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 09:27:12'),(68,2,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 09:27:16'),(69,2,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 10:02:25'),(70,3,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 10:02:29'),(71,3,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 10:38:27'),(72,4,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 10:38:32'),(73,4,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 10:40:03'),(74,25,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 10:40:07'),(75,25,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 10:41:00'),(76,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 10:41:15'),(77,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 15:22:54'),(78,8,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 15:23:34'),(79,8,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 15:27:56'),(80,7,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 15:28:06'),(81,7,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 15:38:24'),(82,10,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 15:38:59'),(83,10,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 15:41:06'),(84,9,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 15:41:11'),(85,9,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 16:42:45'),(86,22,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 16:42:51'),(87,17,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 16:46:09'),(88,17,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 16:47:31'),(89,17,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 16:48:32'),(90,17,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 16:51:42'),(91,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 16:58:10'),(92,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 16:58:10'),(93,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 16:58:20'),(94,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 17:30:58'),(95,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 17:31:07'),(96,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 17:32:25'),(97,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 17:32:28'),(98,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 17:32:37'),(99,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 17:33:45'),(100,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 17:36:03'),(101,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 17:36:09'),(102,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 17:37:01'),(103,7,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 17:37:24'),(104,7,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 04:01:48'),(105,7,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 04:03:10'),(106,7,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 04:03:15'),(107,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 04:14:03'),(108,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 04:14:39'),(109,8,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 04:14:44'),(110,8,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 04:26:39'),(111,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 04:26:55'),(112,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 05:54:34'),(113,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 05:56:35'),(114,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 07:35:18'),(115,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 07:35:45'),(116,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 08:09:10'),(117,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 08:13:36'),(118,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 08:13:52'),(119,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 08:19:37'),(120,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 08:19:57'),(121,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 08:23:33'),(122,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 08:23:38'),(123,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 17:49:58'),(124,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 17:50:07'),(125,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 18:15:02'),(126,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 18:15:10'),(127,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 18:33:52'),(128,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 18:34:00'),(129,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 18:57:38'),(130,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 18:57:49'),(131,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 19:48:33'),(132,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 19:48:39'),(133,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 19:59:26'),(134,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 19:59:31'),(135,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 20:06:54'),(136,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 20:06:59'),(137,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 20:10:18'),(138,4,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 20:10:22'),(139,4,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 20:59:43'),(140,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 20:59:47'),(141,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 21:59:12'),(142,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 21:59:19'),(143,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 05:02:54'),(144,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 08:21:00'),(145,25,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 12:12:44'),(146,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 05:40:59'),(147,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 10:32:48'),(148,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 10:42:32'),(149,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 10:55:02'),(150,3,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 10:55:07'),(151,3,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 12:26:18'),(152,5,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 12:26:23'),(153,5,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 12:26:50'),(154,9,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 12:26:54'),(155,9,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 13:46:40'),(156,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 13:46:58'),(157,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 14:01:10'),(158,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 14:01:17'),(159,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 15:01:53'),(160,25,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 15:02:15'),(161,25,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 15:04:18'),(162,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 15:04:28'),(163,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 15:10:00'),(164,17,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 15:10:06'),(165,25,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 06:37:39'),(166,25,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 08:36:45'),(167,9,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 08:36:50'),(168,9,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 09:39:48'),(169,4,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 09:39:53'),(170,4,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 10:11:14'),(171,3,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 10:11:18'),(172,3,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 10:11:50'),(173,5,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 10:11:55'),(174,5,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 10:33:42'),(175,6,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 10:33:47'),(176,6,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 10:44:41'),(177,5,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 10:44:45'),(178,5,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 10:47:13'),(179,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 10:47:18'),(180,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 14:25:44'),(181,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 14:25:54'),(182,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 15:06:13'),(183,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 15:58:55'),(184,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 16:05:31'),(185,2,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 16:22:04'),(186,2,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 16:22:27'),(187,4,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 16:22:40'),(188,4,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 16:26:50'),(189,17,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 11:07:21'),(190,17,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 11:17:57'),(191,8,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 11:18:03'),(192,8,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 12:39:01'),(193,8,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 12:39:08'),(194,8,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 12:47:58'),(195,25,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 12:48:03'),(196,8,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8737','2026-06-26 13:02:14'),(197,8,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8737','2026-06-26 13:02:22'),(198,25,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8737','2026-06-26 13:03:07'),(199,8,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8737','2026-06-26 13:03:20'),(200,25,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 13:36:04'),(201,7,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 13:36:27'),(202,7,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 14:09:32'),(203,23,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 14:09:45'),(204,23,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 19:36:33'),(205,23,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 19:36:42'),(206,23,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 21:05:05'),(207,23,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 21:05:09'),(208,23,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 04:23:09'),(209,23,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 04:23:13'),(210,23,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 05:04:50'),(211,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 05:04:55'),(212,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 05:09:06'),(213,23,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 05:09:09'),(214,23,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 05:12:42'),(215,22,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 05:12:56'),(216,22,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 05:42:18'),(217,23,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 05:43:32'),(218,23,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 05:44:37'),(219,22,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 05:44:41'),(220,22,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 06:26:46'),(221,22,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 06:26:56'),(222,22,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 06:38:27'),(223,23,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 06:38:31'),(224,23,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 06:38:47'),(225,22,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 06:38:51'),(226,22,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 06:55:47'),(227,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 14:26:14'),(228,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 15:26:31');
/*!40000 ALTER TABLE `staff_activity_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_appraisals`
--

DROP TABLE IF EXISTS `staff_appraisals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff_appraisals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) DEFAULT NULL,
  `reviewer_id` int(11) DEFAULT NULL,
  `review_date` date DEFAULT NULL,
  `performance_score` decimal(5,2) DEFAULT NULL,
  `strengths` text DEFAULT NULL,
  `areas_improvement` text DEFAULT NULL,
  `overall_rating` varchar(50) DEFAULT NULL,
  `status` enum('draft','submitted','reviewed','completed') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_appraisals`
--

LOCK TABLES `staff_appraisals` WRITE;
/*!40000 ALTER TABLE `staff_appraisals` DISABLE KEYS */;
/*!40000 ALTER TABLE `staff_appraisals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_attendance`
--

DROP TABLE IF EXISTS `staff_attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff_attendance` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(10) unsigned NOT NULL,
  `date` date NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'Present',
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `recorded_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_attendance_staff_date` (`staff_id`,`date`),
  KEY `idx_attendance_date` (`date`),
  KEY `idx_attendance_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_attendance`
--

LOCK TABLES `staff_attendance` WRITE;
/*!40000 ALTER TABLE `staff_attendance` DISABLE KEYS */;
INSERT INTO `staff_attendance` (`id`, `staff_id`, `date`, `status`, `time_in`, `time_out`, `remarks`, `recorded_by`, `created_at`) VALUES (1,1,'2026-06-20','Absent',NULL,NULL,NULL,NULL,'2026-06-20 06:58:56'),(2,2,'2026-06-20','On Leave',NULL,NULL,NULL,NULL,'2026-06-20 06:58:56'),(3,3,'2026-06-20','Present',NULL,NULL,NULL,NULL,'2026-06-20 06:58:56'),(4,4,'2026-06-20','On Leave',NULL,NULL,NULL,NULL,'2026-06-20 06:58:56'),(5,23,'2026-06-20','Present',NULL,NULL,NULL,NULL,'2026-06-20 06:58:56'),(6,5,'2026-06-20','Present',NULL,NULL,NULL,NULL,'2026-06-20 06:58:56'),(7,6,'2026-06-20','Late',NULL,NULL,NULL,NULL,'2026-06-20 06:58:56'),(8,7,'2026-06-20','Absent',NULL,NULL,NULL,NULL,'2026-06-20 06:58:56'),(9,24,'2026-06-20','Present',NULL,NULL,NULL,NULL,'2026-06-20 06:58:56'),(10,8,'2026-06-20','Present',NULL,NULL,NULL,NULL,'2026-06-20 06:58:56'),(11,9,'2026-06-20','On Leave',NULL,NULL,NULL,NULL,'2026-06-20 06:58:56'),(12,10,'2026-06-20','Present',NULL,NULL,NULL,NULL,'2026-06-20 06:58:56'),(13,11,'2026-06-20','Present',NULL,NULL,NULL,NULL,'2026-06-20 06:58:56'),(14,12,'2026-06-20','Present',NULL,NULL,NULL,NULL,'2026-06-20 06:58:56'),(15,13,'2026-06-20','Late',NULL,NULL,NULL,NULL,'2026-06-20 06:58:56'),(16,14,'2026-06-20','On Leave',NULL,NULL,NULL,NULL,'2026-06-20 06:58:56'),(17,15,'2026-06-20','Present',NULL,NULL,NULL,NULL,'2026-06-20 06:58:56'),(18,16,'2026-06-20','Present',NULL,NULL,NULL,NULL,'2026-06-20 06:58:56'),(19,17,'2026-06-20','Present',NULL,NULL,NULL,NULL,'2026-06-20 06:58:56'),(20,18,'2026-06-20','Late',NULL,NULL,NULL,NULL,'2026-06-20 06:58:56');
/*!40000 ALTER TABLE `staff_attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_communications`
--

DROP TABLE IF EXISTS `staff_communications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff_communications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `sender_email` varchar(255) NOT NULL,
  `sender_name` varchar(255) NOT NULL,
  `recipient_type` enum('department','all_staff') NOT NULL DEFAULT 'department',
  `recipient_id` varchar(50) DEFAULT NULL,
  `recipient_name` varchar(255) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message_body` text NOT NULL,
  `priority` enum('Low','Normal','High','Urgent') NOT NULL DEFAULT 'Normal',
  `email_status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sender_id` (`sender_id`),
  KEY `idx_recipient_type` (`recipient_type`),
  KEY `idx_recipient_id` (`recipient_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_communications`
--

LOCK TABLES `staff_communications` WRITE;
/*!40000 ALTER TABLE `staff_communications` DISABLE KEYS */;
/*!40000 ALTER TABLE `staff_communications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_contracts`
--

DROP TABLE IF EXISTS `staff_contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff_contracts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(10) unsigned NOT NULL,
  `contract_type` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `salary` decimal(12,2) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'active',
  `document_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_contracts`
--

LOCK TABLES `staff_contracts` WRITE;
/*!40000 ALTER TABLE `staff_contracts` DISABLE KEYS */;
/*!40000 ALTER TABLE `staff_contracts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_departments`
--

DROP TABLE IF EXISTS `staff_departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff_departments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `department_name` varchar(120) NOT NULL,
  `department_code` varchar(20) DEFAULT NULL,
  `department_level` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_dept_name` (`department_name`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_departments`
--

LOCK TABLES `staff_departments` WRITE;
/*!40000 ALTER TABLE `staff_departments` DISABLE KEYS */;
INSERT INTO `staff_departments` (`id`, `department_name`, `department_code`, `department_level`, `description`, `is_active`, `created_at`) VALUES (1,'Executive Leadership','EXEC',1,NULL,1,'2026-06-20 06:58:56'),(2,'Academic Affairs','ACAD',2,NULL,1,'2026-06-20 06:58:56'),(3,'Finance & Accounts','FIN',3,NULL,1,'2026-06-20 06:58:56'),(4,'Human Resources','HR',4,NULL,1,'2026-06-20 06:58:56'),(5,'Nursing Department','NUR',5,NULL,1,'2026-06-20 06:58:56'),(6,'Midwifery Department','MID',6,NULL,1,'2026-06-20 06:58:56'),(7,'ICT','ICT',7,NULL,1,'2026-06-20 06:58:56'),(8,'Admissions','ADM',8,NULL,1,'2026-06-20 06:58:56'),(9,'Library','LIB',9,NULL,1,'2026-06-20 06:58:56'),(10,'Security & Transport','SEC',10,NULL,1,'2026-06-20 06:58:56'),(11,'Store & Assets','STR',11,NULL,1,'2026-06-20 06:58:56'),(12,'Student Services','SVS',12,NULL,1,'2026-06-20 06:58:56');
/*!40000 ALTER TABLE `staff_departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_login_sessions`
--

DROP TABLE IF EXISTS `staff_login_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff_login_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `session_token` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=129 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_login_sessions`
--

LOCK TABLES `staff_login_sessions` WRITE;
/*!40000 ALTER TABLE `staff_login_sessions` DISABLE KEYS */;
INSERT INTO `staff_login_sessions` (`id`, `staff_id`, `session_token`, `ip_address`, `user_agent`, `created_at`, `expires_at`) VALUES (1,1,'pu2hvlihjqangi7jviepaf0ob7','::1','curl/8.19.0','2026-06-10 06:06:48','2026-06-09 23:36:48'),(2,4,'83656fpgh06q9gouhm60nk3tuq','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-06-10 06:07:33','2026-06-09 23:37:33'),(3,4,'lh39hd80nldj2uegqkjhjk2efn','::1','curl/8.19.0','2026-06-10 06:16:40','2026-06-09 23:46:40'),(4,1,'7ljqo58oc291b11bqi2s3cjffg','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-06-10 06:27:04','2026-06-09 23:57:04'),(5,25,'hlr81jh15cqvlf6nl6j8nlhk3f','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-06-10 07:57:13','2026-06-10 01:27:13'),(6,24,'ae3he9cgsdvgdf024bolec2r14','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-06-10 08:02:10','2026-06-10 01:32:10'),(7,9,'dr24ed01jpd3hparhq890kpnf0','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-06-10 13:12:56','2026-06-10 06:42:56'),(8,1,'k8j0smrve1hncrjkq2he9fu0rh','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-17 10:34:12','2026-06-17 04:04:12'),(9,1,'2f99647bj7odhsl4cj6vhlals8','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 05:18:02','2026-06-17 22:48:02'),(10,2,'suho7uaqglfdjpgt6f6bpr0nqb','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 09:15:02','2026-06-18 02:45:02'),(11,3,'gn380t4p7ebopr4pbmd83r3098','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 09:20:34','2026-06-18 02:50:34'),(12,4,'j0bvg0i2bsstfd5f2b71pnbhv2','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 09:21:40','2026-06-18 02:51:40'),(13,5,'1p2sqtjhn2q39oq8uok2bka2s6','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 09:23:54','2026-06-18 02:53:54'),(14,6,'ebpn95qsf7pvk6jr5iad1vi3lk','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 09:25:03','2026-06-18 02:55:03'),(15,25,'s2q2c95audemj51h44e3vmah41','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 09:34:25','2026-06-18 03:04:25'),(16,24,'qf5sbbkufe4onpt0j5qdg8cfp4','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 09:35:42','2026-06-18 03:05:42'),(17,1,'1359ma7hua0fmmvl8espcd9an7','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 16:31:15','2026-06-18 10:01:15'),(18,2,'blmvsuvsqc3h3fq4ed857p8asq','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 17:01:58','2026-06-18 10:31:58'),(19,25,'vv9i7126ujrh0ht0sekerd5vnq','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 17:03:51','2026-06-18 10:33:51'),(20,1,'sc7nqfk1p54kusvoh7959k81c7','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 17:50:50','2026-06-18 11:20:50'),(21,25,'6rclk83t17947n4pj1ngh9hj81','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 18:29:16','2026-06-18 11:59:16'),(22,7,'30mj4uha05dsb1rdea48hkrgb5','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 18:56:46','2026-06-18 12:26:46'),(23,7,'2hlgnoq56hhvf37ar4im2ue7nm','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 20:17:44','2026-06-18 13:47:44'),(24,7,'gpqmln2qp7o00ek4rjjenf4khj','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 20:17:56','2026-06-18 13:47:56'),(25,23,'34mpge2kds50ab697a1agal7us','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 20:19:17','2026-06-18 13:49:17'),(26,22,'mmt06pq180c82ofjuf8hgihiva','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 20:20:23','2026-06-18 13:50:23'),(27,22,'jef954p75gcad385f70ig9tadc','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 20:37:05','2026-06-18 14:07:05'),(28,24,'t8g4s4ib33vp3villv4iv8p5no','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 20:37:22','2026-06-18 14:07:22'),(29,24,'h0l3knqrvi229h6laq5ltln2to','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 20:44:53','2026-06-18 14:14:53'),(30,24,'jv52bv72042nq2v2vileprunqr','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 20:47:26','2026-06-18 14:17:26'),(31,7,'0pa58vehm4juir1f0924c929eu','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-19 03:38:19','2026-06-18 21:08:19'),(32,17,'gv23nhcevrnc6cu2sqj1q6ksp0','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 05:33:02','2026-06-19 23:03:02'),(33,1,'erd3bpes4jq9qfk173g5561tn7','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 06:52:59','2026-06-20 00:22:59'),(34,2,'c31ettfnja46ueh449bkq22vh7','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 10:31:21','2026-06-20 04:01:21'),(35,25,'79007ugk7c1mi07d7m5c9l9c71','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 11:07:31','2026-06-20 04:37:31'),(36,17,'6a3hb5erpafv3av162128t5dpb','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 11:08:13','2026-06-20 04:38:13'),(37,1,'qbin2lntmfe0ctm7s80b5ccsi6','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 18:16:50','2026-06-20 11:46:50'),(38,25,'63qd2kbtvalb6jlf259akkthcc','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 19:18:33','2026-06-20 12:48:33'),(39,1,'5adsl9dnpdml0l9089vi78sk1j','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 22:41:52','2026-06-20 16:11:52'),(40,1,'titkd3lgrb6p0n2s92875b1f1l','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 07:45:04','2026-06-21 01:15:04'),(41,1,'np4ea04g9arhbh2ticlj8a8jk0','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 09:19:30','2026-06-21 02:49:30'),(42,2,'06f1nkaks13lc7ht4kvuq2sl56','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 09:27:16','2026-06-21 02:57:16'),(43,3,'dqv8stll1pfe9kmc8lkvchal44','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 10:02:29','2026-06-21 03:32:29'),(44,4,'mkqhi86baa63c035veice145ll','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 10:38:32','2026-06-21 04:08:32'),(45,25,'5tu70v12sp531pvi6bnd4ktr1f','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 10:40:07','2026-06-21 04:10:07'),(46,24,'2pgtv3ai29nri6qac8qrc9ff13','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 10:41:15','2026-06-21 04:11:15'),(47,8,'3fjn3qhpi54ad00ig5jr46adrh','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 15:23:34','2026-06-21 08:53:34'),(48,7,'mfufdau7qocjbtu885pm8ko2od','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 15:28:06','2026-06-21 08:58:06'),(49,10,'rvau0732fn5eb7aq561lc5qkm1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 15:38:59','2026-06-21 09:08:59'),(50,9,'vbguukqdpatqmm20c3m9gjjkcf','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 15:41:11','2026-06-21 09:11:11'),(51,22,'tq6q7ogmro8nmn0207kngvd21h','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 16:42:51','2026-06-21 10:12:51'),(52,17,'u7kckp0ni8u4jro21r3902smaj','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 16:46:09','2026-06-21 10:16:09'),(53,17,'vmp1feirc6evkuqm4kr14kivj1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 16:48:32','2026-06-21 10:18:32'),(54,24,'0a98v19vemano2jnpabb2j1p2g','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 16:58:10','2026-06-21 10:28:10'),(55,24,'0s7tbe4ouk2fiht3obv1nvphav','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 16:58:20','2026-06-21 10:28:20'),(56,24,'cukgdorpavsii00locajfjqpci','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 17:31:07','2026-06-21 11:01:07'),(57,24,'lslmk523ctp75jif1nie3uqd82','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 17:32:28','2026-06-21 11:02:28'),(58,24,'veofu3mv8j6t624aa4fs53p2fn','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 17:33:45','2026-06-21 11:03:45'),(59,24,'4cqichecqd00evma1u7sbk3j2q','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 17:36:09','2026-06-21 11:06:09'),(60,7,'d522eht2ekupd06is4b5571tss','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 17:37:24','2026-06-21 11:07:24'),(61,7,'irrr02lhbcgfrpu4l69j7d7fvl','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 04:03:10','2026-06-21 21:33:10'),(62,24,'f2v52677oj0d7cv0fts20sga63','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 04:14:03','2026-06-21 21:44:03'),(63,8,'q96npe7qia97egg4delvjpcd0u','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 04:14:44','2026-06-21 21:44:44'),(64,24,'jmg7854n7jgeu8odup6l9iot29','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 04:26:55','2026-06-21 21:56:55'),(65,24,'4tblptijta3l5tfta0pvb25601','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 05:56:35','2026-06-21 23:26:35'),(66,24,'hpq3utci8urukiaruh92vob8mn','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 07:35:45','2026-06-22 01:05:45'),(67,24,'t49b41nfcaruon5ro15p15mltc','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 08:09:10','2026-06-22 01:39:10'),(68,24,'osoor0p43434atvgr7j66t22f6','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 08:13:52','2026-06-22 01:43:52'),(69,24,'0me8chv0u24pr6jfgg9oe23lov','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 08:19:57','2026-06-22 01:49:57'),(70,24,'kdlr506tct65oilrnuj67pb72i','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 08:23:38','2026-06-22 01:53:38'),(71,24,'jslvmsv36efl4ukgf1q7g2skrt','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 17:50:07','2026-06-22 11:20:07'),(72,24,'hdvul1svlg8ui13hcqk01cclda','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 18:15:10','2026-06-22 11:45:10'),(73,24,'18d7dqitp2ml8j9nqte0qvvtbc','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 18:34:00','2026-06-22 12:04:00'),(74,24,'ur2fs528fiomfrd25ggfbntevu','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 18:57:49','2026-06-22 12:27:49'),(75,1,'qrs5r3d0crst274csne31s6bik','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 19:48:39','2026-06-22 13:18:39'),(76,24,'pqumq7aq89oarcoi7u0vfc4euc','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 19:59:31','2026-06-22 13:29:31'),(77,1,'4dfl0lha5fktfpih6dio8m629n','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 20:06:59','2026-06-22 13:36:59'),(78,4,'uoi1qs187pr2gd1799ousa63cu','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 20:10:22','2026-06-22 13:40:22'),(79,1,'1s9luflbvjvmdor92928kc9lbf','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 20:59:47','2026-06-22 14:29:47'),(80,24,'ttjcmn74g42n5lstnqmv6pijpu','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 21:59:19','2026-06-22 15:29:19'),(81,1,'1pmpbl6de5stu5mdelph4h475e','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 05:02:54','2026-06-22 22:32:54'),(82,24,'hljpb5ph7e3j24ckvan8mauluh','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 08:21:00','2026-06-23 01:51:00'),(83,25,'ks1tultbpko3s70j5fhq4e4t9h','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 12:12:44','2026-06-23 05:42:44'),(84,1,'rf0mklksts3um16lm1c90l2gbq','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 05:40:59','2026-06-23 23:10:59'),(85,1,'ppfvcia8sprhfv7t5i2dsn38a0','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 10:42:32','2026-06-24 04:12:32'),(86,3,'lgp10qeu8kiecak9fv098pjglo','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 10:55:07','2026-06-24 04:25:07'),(87,5,'6pu90rj74r1pq47q228fcjoniu','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 12:26:23','2026-06-24 05:56:23'),(88,9,'jfn6065k8m3goqpe3sprr17b6p','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 12:26:54','2026-06-24 05:56:54'),(89,1,'794af9ukhkur706kvkfku12iaq','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 13:46:58','2026-06-24 07:16:58'),(90,24,'9jebpeo9cqldprvn4khlrnr9vi','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 14:01:17','2026-06-24 07:31:17'),(91,25,'8a4liolknvm9st9v1kv3r106fl','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 15:02:15','2026-06-24 08:32:15'),(92,1,'25vgr9ffnilj3iaufkdi2olvgd','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 15:04:28','2026-06-24 08:34:28'),(93,17,'f584elfqqdtdaneqgvu4j2sgb5','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 15:10:06','2026-06-24 08:40:06'),(94,25,'ik5cfqa2gkgjgfmgmefk090jjd','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 06:37:39','2026-06-25 00:07:39'),(95,9,'34ca3u1j6isj17ocoq43vba38c','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 08:36:50','2026-06-25 02:06:50'),(96,4,'e5vsb9gmjeun7kito01jamqs3d','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 09:39:53','2026-06-25 03:09:53'),(97,3,'7rqduhslro0lq0nplmpbunpgf0','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 10:11:18','2026-06-25 03:41:18'),(98,5,'tl71050ga502dbhf0tggle7b8d','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 10:11:55','2026-06-25 03:41:55'),(99,6,'89n9gmr0fjrhmuuolavg7bh7vj','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 10:33:47','2026-06-25 04:03:47'),(100,5,'t5s26jd6cbasdfv24scv7oqgnc','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 10:44:45','2026-06-25 04:14:45'),(101,24,'p2ek6i7irhqbkkppvei7r15olf','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 10:47:18','2026-06-25 04:17:18'),(102,24,'btumr0h7kam4vbeliviv0vht80','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 14:25:54','2026-06-25 07:55:54'),(103,1,'affknfo0e0cod2oi2jru0qjgph','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 15:58:55','2026-06-25 09:28:55'),(104,2,'h447aeemqdhvj8dlaofabvmss9','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 16:22:04','2026-06-25 09:52:04'),(105,4,'a087m1fgf0fu8elbeoe57g6il1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 16:22:40','2026-06-25 09:52:40'),(106,17,'n9o9l07t52qa71jjmg3l9egl3q','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 11:07:21','2026-06-26 04:37:21'),(107,8,'j92qk81fhdbtt122h79ckue45f','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 11:18:03','2026-06-26 04:48:03'),(108,8,'edj83pm5bjgr8g6vbeci45ajod','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 12:39:08','2026-06-26 06:09:08'),(109,25,'41s1vms3719jbporauptnbjumd','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 12:48:03','2026-06-26 06:18:03'),(110,8,'3vg1268gsos1b49pha89j8qcdl','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8737','2026-06-26 13:02:14','2026-06-26 06:32:14'),(111,8,'t0va2mgidaq269fsdhp6dscgr3','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8737','2026-06-26 13:02:22','2026-06-26 06:32:22'),(112,25,'gq0ncrfvok3ljs5trl2u2meclg','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8737','2026-06-26 13:03:07','2026-06-26 06:33:07'),(113,8,'22gvam5engahgclurhh82mppuh','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8737','2026-06-26 13:03:20','2026-06-26 06:33:20'),(114,7,'nj45vbijbkug1j88nncnts0oah','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 13:36:27','2026-06-26 07:06:27'),(115,23,'9m3qh7jl3j8bq9fm0qafu02snb','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 14:09:45','2026-06-26 07:39:45'),(116,23,'3tnohnrv7m0us60fmm0slh4vjn','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 19:36:42','2026-06-26 13:06:42'),(117,23,'odl6daac37jvuiaqq30ebv211t','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 21:05:09','2026-06-26 14:35:09'),(118,23,'vgh2eulctao8s12rdgdu77t1q7','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 04:23:13','2026-06-26 21:53:13'),(119,1,'ijjcb8ppqqeg0eh07rctuob530','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 05:04:55','2026-06-26 22:34:55'),(120,23,'98sb0ebgrcuh2adlceuj198qfm','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 05:09:09','2026-06-26 22:39:09'),(121,22,'6undm5ctv6iltdc39d2cnr2kom','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 05:12:56','2026-06-26 22:42:56'),(122,23,'1vhpuhmg2t9v6s93e4vt1gdasl','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 05:43:32','2026-06-26 23:13:32'),(123,22,'7qj0v2vurgdm825hnht7cc6cki','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 05:44:41','2026-06-26 23:14:41'),(124,22,'384f35su5t0cqbgqg2q623n8e7','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 06:26:56','2026-06-26 23:56:56'),(125,23,'cl13gqakjddlkkagtdm07brjs2','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 06:38:31','2026-06-27 00:08:31'),(126,22,'hvq4ej7521ircu9s4qdi9a4nu8','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 06:38:51','2026-06-27 00:08:51'),(127,22,'89a3kokapu2e5308u309ftsdoa','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 06:55:47','2026-06-27 00:25:47'),(128,1,'obs5jdudi11f3h2ffk564c90j6','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 14:26:14','2026-06-27 07:56:14');
/*!40000 ALTER TABLE `staff_login_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_profiles`
--

DROP TABLE IF EXISTS `staff_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `department` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `staff_id` (`staff_id`),
  CONSTRAINT `staff_profiles_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_profiles`
--

LOCK TABLES `staff_profiles` WRITE;
/*!40000 ALTER TABLE `staff_profiles` DISABLE KEYS */;
INSERT INTO `staff_profiles` (`id`, `staff_id`, `profile_picture`, `bio`, `department`, `phone`, `address`, `created_at`, `updated_at`) VALUES (3,1,NULL,'',NULL,NULL,NULL,'2026-06-24 07:08:59','2026-06-24 07:08:59'),(4,24,NULL,'',NULL,NULL,NULL,'2026-06-24 14:01:59','2026-06-24 14:01:59');
/*!40000 ALTER TABLE `staff_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_resignations`
--

DROP TABLE IF EXISTS `staff_resignations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff_resignations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(10) unsigned NOT NULL,
  `resignation_date` date DEFAULT NULL,
  `last_working_date` date DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `approved_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_resignations`
--

LOCK TABLES `staff_resignations` WRITE;
/*!40000 ALTER TABLE `staff_resignations` DISABLE KEYS */;
/*!40000 ALTER TABLE `staff_resignations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_roles`
--

DROP TABLE IF EXISTS `staff_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(100) NOT NULL,
  `role_description` text DEFAULT NULL,
  `role_level` int(11) DEFAULT 5,
  `dashboard_path` varchar(255) DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_roles`
--

LOCK TABLES `staff_roles` WRITE;
/*!40000 ALTER TABLE `staff_roles` DISABLE KEYS */;
INSERT INTO `staff_roles` (`id`, `role_name`, `role_description`, `role_level`, `dashboard_path`, `permissions`, `created_at`, `updated_at`) VALUES (1,'Director General',NULL,1,'dashboards/director-general.php',NULL,'2026-06-10 05:56:09','2026-06-10 05:56:09'),(2,'CEO',NULL,1,'dashboards/ceo.php',NULL,'2026-06-10 05:56:09','2026-06-10 05:56:09'),(3,'Director Academics',NULL,2,'dashboards/director-academics.php',NULL,'2026-06-10 05:56:09','2026-06-10 05:56:09'),(4,'Director Finance',NULL,2,'dashboards/director-finance.php',NULL,'2026-06-10 05:56:09','2026-06-10 05:56:09'),(5,'Director ICT',NULL,2,'dashboards/director-ict.php',NULL,'2026-06-10 05:56:09','2026-06-10 05:56:09'),(6,'School Principal',NULL,2,'dashboards/school-principal.php',NULL,'2026-06-10 05:56:09','2026-06-10 05:56:09'),(7,'Deputy Principal',NULL,3,'dashboards/deputy-principal.php',NULL,'2026-06-10 05:56:09','2026-06-10 05:56:09'),(8,'Academic Registrar',NULL,3,'dashboards/academic-registrar.php',NULL,'2026-06-10 05:56:09','2026-06-10 05:56:09'),(9,'HR Manager',NULL,3,'dashboards/hr-manager.php',NULL,'2026-06-10 05:56:09','2026-06-10 05:56:09'),(10,'School Secretary',NULL,4,'dashboards/school-secretary.php',NULL,'2026-06-10 05:56:09','2026-06-10 05:56:09'),(11,'School Librarian',NULL,4,'dashboards/school-librarian.php',NULL,'2026-06-10 05:56:09','2026-06-10 05:56:09'),(12,'Head Nursing',NULL,3,'dashboards/head-nursing.php',NULL,'2026-06-10 05:56:09','2026-06-10 05:56:09'),(13,'Head Midwifery',NULL,3,'dashboards/head-midwifery.php',NULL,'2026-06-10 05:56:09','2026-06-10 05:56:09'),(14,'Senior Lecturers',NULL,4,'dashboards/senior-lecturers.php',NULL,'2026-06-10 05:56:09','2026-06-10 05:56:09'),(15,'Lecturers',NULL,5,'dashboards/lecturers.php',NULL,'2026-06-10 05:56:09','2026-06-10 05:56:09'),(16,'Matrons',NULL,4,'dashboards/matrons.php',NULL,'2026-06-10 05:56:09','2026-06-10 05:56:09'),(17,'Wardens',NULL,5,'dashboards/wardens.php',NULL,'2026-06-10 05:56:09','2026-06-10 05:56:09'),(18,'Sickbay',NULL,5,'dashboards/sickbay.php',NULL,'2026-06-10 05:56:09','2026-06-10 05:56:09'),(19,'Drivers',NULL,6,'dashboards/drivers.php',NULL,'2026-06-10 05:56:09','2026-06-10 05:56:09'),(20,'Security',NULL,6,'dashboards/security.php',NULL,'2026-06-10 05:56:09','2026-06-10 05:56:09'),(21,'Storekeeper',NULL,5,'dashboards/storekeeper.php',NULL,'2026-06-10 05:56:09','2026-06-10 05:56:09'),(22,'Guild President',NULL,5,'dashboards/guild-president.php',NULL,'2026-06-10 05:56:09','2026-06-10 05:56:09'),(23,'Computer Lab Manager',NULL,3,'computer_lab.php',NULL,'2026-06-10 05:56:09','2026-06-10 05:56:09'),(24,'School Bursar',NULL,3,'dashboards/school-bursar.php',NULL,'2026-06-10 05:56:09','2026-06-26 12:57:33'),(25,'Store Keeper','Store inventory',0,'dashboards/storekeeper.php','{\"store\":true,\"inventory\":true}','2026-06-13 09:38:49','2026-06-13 09:38:49'),(26,'Director Admissions & Requirements','Admissions management',0,'dashboards/director-admissions.php','{\"admissions\":true,\"requirements\":true}','2026-06-13 09:38:49','2026-06-13 09:38:49'),(27,'Bursar','Bursar assistant',0,'dashboards/school-bursar.php','{\"financial\":true,\"fees\":true}','2026-06-13 09:38:49','2026-06-26 12:57:33');
/*!40000 ALTER TABLE `staff_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_salaries`
--

DROP TABLE IF EXISTS `staff_salaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `staff_salaries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(10) unsigned NOT NULL,
  `basic_salary` decimal(12,2) DEFAULT 0.00,
  `allowances` decimal(12,2) DEFAULT 0.00,
  `overtime_rate` decimal(12,2) DEFAULT 0.00,
  `nssf_tax` decimal(12,2) DEFAULT 0.00,
  `paye_tax` decimal(12,2) DEFAULT 0.00,
  `effective_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `bonus` decimal(10,2) DEFAULT 0.00,
  `deductions` decimal(10,2) DEFAULT 0.00,
  `net_salary` decimal(12,2) DEFAULT 0.00,
  `payment_month` varchar(20) DEFAULT NULL,
  `payment_year` varchar(10) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_salaries`
--

LOCK TABLES `staff_salaries` WRITE;
/*!40000 ALTER TABLE `staff_salaries` DISABLE KEYS */;
INSERT INTO `staff_salaries` (`id`, `staff_id`, `basic_salary`, `allowances`, `overtime_rate`, `nssf_tax`, `paye_tax`, `effective_date`, `created_by`, `bonus`, `deductions`, `net_salary`, `payment_month`, `payment_year`, `status`, `created_at`, `updated_at`) VALUES (1,7,1500000.00,0.00,0.00,0.00,0.02,'2026-06-25',25,0.00,0.02,1499999.98,NULL,NULL,'Active','2026-06-25 07:35:20','2026-06-25 07:35:20');
/*!40000 ALTER TABLE `staff_salaries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `store_orders`
--

DROP TABLE IF EXISTS `store_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `store_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_number` varchar(50) NOT NULL,
  `supplier` varchar(200) DEFAULT 'Internal Requisition',
  `notes` text DEFAULT NULL,
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `status` varchar(50) DEFAULT 'pending_approval',
  `requested_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `received_by` int(11) DEFAULT NULL,
  `received_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_orders_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `store_orders`
--

LOCK TABLES `store_orders` WRITE;
/*!40000 ALTER TABLE `store_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `store_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `store_requests`
--

DROP TABLE IF EXISTS `store_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `store_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `request_number` varchar(60) NOT NULL,
  `requested_by` int(10) unsigned DEFAULT NULL,
  `department` varchar(80) DEFAULT NULL,
  `items` text DEFAULT NULL,
  `urgency` varchar(20) NOT NULL DEFAULT 'medium',
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `forwarded_to` int(10) unsigned DEFAULT NULL,
  `approval_request_id` int(10) unsigned DEFAULT NULL,
  `approved_by` int(10) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_request_number` (`request_number`),
  KEY `idx_store_status` (`status`),
  KEY `idx_store_urgency` (`urgency`),
  KEY `idx_approval` (`approval_request_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `store_requests`
--

LOCK TABLES `store_requests` WRITE;
/*!40000 ALTER TABLE `store_requests` DISABLE KEYS */;
INSERT INTO `store_requests` (`id`, `request_number`, `requested_by`, `department`, `items`, `urgency`, `status`, `forwarded_to`, `approval_request_id`, `approved_by`, `notes`, `created_at`, `updated_at`) VALUES (1,'SR-2026-0001',1,NULL,NULL,'medium','pending_approval',NULL,1,NULL,NULL,'2026-06-08 15:58:56','2026-06-20 07:47:50'),(2,'SR-2026-0002',1,NULL,NULL,'urgent','pending_approval',NULL,2,NULL,NULL,'2026-06-10 15:58:56','2026-06-20 07:47:50'),(3,'SR-2026-0003',1,NULL,NULL,'medium','pending_approval',NULL,3,NULL,NULL,'2026-06-10 15:58:56','2026-06-20 07:47:50'),(4,'SR-2026-0004',1,NULL,NULL,'high','pending',NULL,NULL,NULL,NULL,'2026-06-14 15:58:56',NULL),(5,'SR-2026-0005',1,NULL,NULL,'high','pending',NULL,NULL,NULL,NULL,'2026-06-18 15:58:56',NULL);
/*!40000 ALTER TABLE `store_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_admissions`
--

DROP TABLE IF EXISTS `student_admissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_admissions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `admission_date` date NOT NULL,
  `program` varchar(200) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `admission_type` varchar(50) DEFAULT 'regular',
  `status` varchar(30) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `idx_sa_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_admissions`
--

LOCK TABLES `student_admissions` WRITE;
/*!40000 ALTER TABLE `student_admissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_admissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_discipline_records`
--

DROP TABLE IF EXISTS `student_discipline_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_discipline_records` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `incident_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `reported_by` int(10) unsigned DEFAULT NULL,
  `action_taken` text DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_discipline_records`
--

LOCK TABLES `student_discipline_records` WRITE;
/*!40000 ALTER TABLE `student_discipline_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_discipline_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_documents`
--

DROP TABLE IF EXISTS `student_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `applicant_id` int(11) NOT NULL,
  `requirement_id` int(11) DEFAULT NULL,
  `document_type` varchar(100) NOT NULL DEFAULT 'Other',
  `document_title` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `verification_status` enum('Pending','Verified','Rejected') DEFAULT 'Pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `document_status` varchar(50) DEFAULT 'Pending',
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_docs_applicant` (`applicant_id`),
  KEY `idx_docs_status` (`verification_status`),
  KEY `idx_doc_requirement` (`requirement_id`),
  CONSTRAINT `student_documents_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_documents`
--

LOCK TABLES `student_documents` WRITE;
/*!40000 ALTER TABLE `student_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_fee_accounts`
--

DROP TABLE IF EXISTS `student_fee_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_fee_accounts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `total_fees` decimal(12,2) DEFAULT 0.00,
  `amount_paid` decimal(12,2) DEFAULT 0.00,
  `balance` decimal(12,2) DEFAULT 0.00,
  `status` varchar(30) DEFAULT 'active',
  `due_date` date DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `program` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `status` (`status`),
  KEY `invoice_number` (`invoice_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_fee_accounts`
--

LOCK TABLES `student_fee_accounts` WRITE;
/*!40000 ALTER TABLE `student_fee_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_fee_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_fee_assignments`
--

DROP TABLE IF EXISTS `student_fee_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_fee_assignments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `fee_structure_id` int(10) unsigned DEFAULT NULL,
  `total_amount` decimal(12,2) DEFAULT NULL,
  `amount_paid` decimal(12,2) DEFAULT 0.00,
  `balance` decimal(12,2) DEFAULT 0.00,
  `academic_year` varchar(20) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'active',
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_fee_assignments`
--

LOCK TABLES `student_fee_assignments` WRITE;
/*!40000 ALTER TABLE `student_fee_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_fee_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_fees`
--

DROP TABLE IF EXISTS `student_fees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_fees` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `fee_type` varchar(100) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT 0.00,
  `balance` decimal(12,2) DEFAULT 0.00,
  `due_date` date DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_fees`
--

LOCK TABLES `student_fees` WRITE;
/*!40000 ALTER TABLE `student_fees` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_fees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_hostel_allocations`
--

DROP TABLE IF EXISTS `student_hostel_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_hostel_allocations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `room_id` int(10) unsigned DEFAULT NULL,
  `allocation_date` date DEFAULT NULL,
  `status` varchar(30) DEFAULT 'active',
  `checkout_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_hostel_allocations`
--

LOCK TABLES `student_hostel_allocations` WRITE;
/*!40000 ALTER TABLE `student_hostel_allocations` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_hostel_allocations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_invoices`
--

DROP TABLE IF EXISTS `student_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_invoices` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned DEFAULT NULL,
  `invoice_number` varchar(60) DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `amount_paid` decimal(12,2) DEFAULT 0.00,
  `balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_invoices_status` (`status`),
  KEY `idx_invoices_student` (`student_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_invoices`
--

LOCK TABLES `student_invoices` WRITE;
/*!40000 ALTER TABLE `student_invoices` DISABLE KEYS */;
INSERT INTO `student_invoices` (`id`, `student_id`, `invoice_number`, `total_amount`, `amount_paid`, `balance`, `status`, `due_date`, `created_at`) VALUES (1,1,'INV-2024-001',1500000.00,1000000.00,500000.00,'partial','2024-12-31','2026-06-20 06:59:17'),(2,2,'INV-2024-002',1200000.00,1200000.00,0.00,'paid','2024-11-30','2026-06-20 06:59:17'),(3,3,'INV-2024-003',1500000.00,0.00,1500000.00,'pending','2025-01-31','2026-06-20 06:59:17'),(4,4,'INV-2024-004',1800000.00,800000.00,1000000.00,'partial','2025-02-28','2026-06-20 06:59:17'),(5,5,'INV-2024-005',1500000.00,500000.00,1000000.00,'partial','2025-03-31','2026-06-20 06:59:17');
/*!40000 ALTER TABLE `student_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_password_resets`
--

DROP TABLE IF EXISTS `student_password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_password_resets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_password_resets`
--

LOCK TABLES `student_password_resets` WRITE;
/*!40000 ALTER TABLE `student_password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_penalties`
--

DROP TABLE IF EXISTS `student_penalties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_penalties` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `penalty_type` varchar(100) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `reason` text DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `waived_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_penalties`
--

LOCK TABLES `student_penalties` WRITE;
/*!40000 ALTER TABLE `student_penalties` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_penalties` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_profiles`
--

DROP TABLE IF EXISTS `student_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_profiles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `bio` text DEFAULT NULL,
  `emergency_contact` varchar(100) DEFAULT NULL,
  `emergency_phone` varchar(30) DEFAULT NULL,
  `medical_info` text DEFAULT NULL,
  `guardian_name` varchar(200) DEFAULT NULL,
  `guardian_phone` varchar(30) DEFAULT NULL,
  `guardian_email` varchar(150) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_profiles`
--

LOCK TABLES `student_profiles` WRITE;
/*!40000 ALTER TABLE `student_profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_progression`
--

DROP TABLE IF EXISTS `student_progression`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_progression` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `from_year` int(11) DEFAULT NULL,
  `from_semester` varchar(50) DEFAULT NULL,
  `to_year` int(11) DEFAULT NULL,
  `to_semester` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `cgpa` decimal(4,2) DEFAULT NULL,
  `decision` enum('promoted','probation','repeat','withdrawn','supplementary') DEFAULT 'promoted',
  `remarks` text DEFAULT NULL,
  `decided_by` int(11) DEFAULT NULL,
  `decided_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sp_student` (`student_id`),
  KEY `idx_sp_decision` (`decision`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_progression`
--

LOCK TABLES `student_progression` WRITE;
/*!40000 ALTER TABLE `student_progression` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_progression` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_sick_leave`
--

DROP TABLE IF EXISTS `student_sick_leave`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_sick_leave` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `leave_number` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `student_name` varchar(300) NOT NULL,
  `student_number` varchar(50) DEFAULT NULL,
  `program` varchar(100) DEFAULT NULL,
  `year_of_study` int(11) DEFAULT NULL,
  `sickness_id` int(11) DEFAULT NULL,
  `sickness_name` varchar(255) DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `leave_from` date NOT NULL,
  `leave_to` date NOT NULL,
  `total_days` int(11) GENERATED ALWAYS AS (to_days(`leave_to`) - to_days(`leave_from`) + 1) STORED,
  `leave_type` enum('Medical','Sick','Maternity','Injury','Quarantine','Other') DEFAULT 'Sick',
  `recommended_by` varchar(200) DEFAULT NULL,
  `recommender_title` varchar(100) DEFAULT NULL,
  `approved_by` varchar(200) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Expired','Extended') DEFAULT 'Pending',
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `extended_to` date DEFAULT NULL,
  `extension_reason` text DEFAULT NULL,
  `doctor_notes` text DEFAULT NULL,
  `bed_rest_required` tinyint(1) DEFAULT 1,
  `parent_guardian_notified` tinyint(1) DEFAULT 0,
  `matron_notified` tinyint(1) DEFAULT 0,
  `class_teacher_notified` tinyint(1) DEFAULT 0,
  `documents_submitted` tinyint(1) DEFAULT 0,
  `documents_path` varchar(500) DEFAULT NULL,
  `return_date_actual` date DEFAULT NULL,
  `return_notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `leave_number` (`leave_number`),
  KEY `student_id` (`student_id`),
  KEY `sickness_id` (`sickness_id`),
  KEY `leave_from` (`leave_from`),
  KEY `leave_to` (`leave_to`),
  KEY `status` (`status`),
  KEY `student_name` (`student_name`),
  KEY `program` (`program`),
  KEY `ssl_student_status` (`student_id`,`status`),
  CONSTRAINT `student_sick_leave_ibfk_1` FOREIGN KEY (`sickness_id`) REFERENCES `sickness_directory` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_sick_leave`
--

LOCK TABLES `student_sick_leave` WRITE;
/*!40000 ALTER TABLE `student_sick_leave` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_sick_leave` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_timetables`
--

DROP TABLE IF EXISTS `student_timetables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_timetables` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `course_code` varchar(30) DEFAULT NULL,
  `course_name` varchar(200) DEFAULT NULL,
  `day_of_week` varchar(20) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `lecturer` varchar(200) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_timetables`
--

LOCK TABLES `student_timetables` WRITE;
/*!40000 ALTER TABLE `student_timetables` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_timetables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_welfare_cases`
--

DROP TABLE IF EXISTS `student_welfare_cases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `student_welfare_cases` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `case_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(30) DEFAULT 'open',
  `assigned_to` int(10) unsigned DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_welfare_cases`
--

LOCK TABLES `student_welfare_cases` WRITE;
/*!40000 ALTER TABLE `student_welfare_cases` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_welfare_cases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `students` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `student_number` varchar(60) DEFAULT NULL,
  `full_name` varchar(200) DEFAULT NULL,
  `program` varchar(120) DEFAULT NULL,
  `level` varchar(20) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'Active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_student_number` (`student_number`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` (`id`, `first_name`, `last_name`, `student_number`, `full_name`, `program`, `level`, `status`) VALUES (1,'Grace','Nakato','ISNM-2024-001','Grace Nakato','Diploma Nursing',NULL,'Active'),(2,'David','Ssali','ISNM-2024-002','David Ssali','Certificate Midwifery',NULL,'Active'),(3,'Mary','Nalwoga','ISNM-2024-003','Mary Nalwoga','Certificate Nursing',NULL,'Active'),(4,'James','Okello','ISNM-2024-004','James Okello','Diploma Midwifery',NULL,'Active'),(5,'Sarah','Kyomugisha','ISNM-2024-005','Sarah Kyomugisha','Diploma Nursing',NULL,'Active');
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subjects` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `code` varchar(30) DEFAULT NULL,
  `department_id` int(10) unsigned DEFAULT NULL,
  `credits` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subjects`
--

LOCK TABLES `subjects` WRITE;
/*!40000 ALTER TABLE `subjects` DISABLE KEYS */;
/*!40000 ALTER TABLE `subjects` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscription_deductions`
--

DROP TABLE IF EXISTS `subscription_deductions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `subscription_deductions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `subscription_id` int(10) unsigned NOT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `deduction_date` date DEFAULT NULL,
  `status` varchar(30) DEFAULT 'pending',
  `reference` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `subscription_id` (`subscription_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `subscription_deductions`
--

LOCK TABLES `subscription_deductions` WRITE;
/*!40000 ALTER TABLE `subscription_deductions` DISABLE KEYS */;
/*!40000 ALTER TABLE `subscription_deductions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_logs`
--

DROP TABLE IF EXISTS `system_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `log_type` varchar(50) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `log_type` (`log_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_logs`
--

LOCK TABLES `system_logs` WRITE;
/*!40000 ALTER TABLE `system_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `system_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teachers`
--

DROP TABLE IF EXISTS `teachers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teachers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(10) unsigned NOT NULL,
  `department_id` int(10) unsigned DEFAULT NULL,
  `specialization` varchar(200) DEFAULT NULL,
  `qualification` varchar(200) DEFAULT NULL,
  `employment_type` varchar(50) DEFAULT 'full-time',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teachers`
--

LOCK TABLES `teachers` WRITE;
/*!40000 ALTER TABLE `teachers` DISABLE KEYS */;
/*!40000 ALTER TABLE `teachers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teaching_quality_reviews`
--

DROP TABLE IF EXISTS `teaching_quality_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teaching_quality_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lecturer_id` int(11) DEFAULT NULL,
  `review_date` date DEFAULT NULL,
  `teaching_score` decimal(5,2) DEFAULT NULL,
  `course_code` varchar(50) DEFAULT NULL,
  `observer` varchar(200) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `status` enum('draft','completed','reviewed') DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teaching_quality_reviews`
--

LOCK TABLES `teaching_quality_reviews` WRITE;
/*!40000 ALTER TABLE `teaching_quality_reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `teaching_quality_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `timetables`
--

DROP TABLE IF EXISTS `timetables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `timetables` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `class_id` int(10) unsigned DEFAULT NULL,
  `subject_id` int(10) unsigned DEFAULT NULL,
  `teacher_id` int(10) unsigned DEFAULT NULL,
  `day_of_week` varchar(20) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `room` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `timetables`
--

LOCK TABLES `timetables` WRITE;
/*!40000 ALTER TABLE `timetables` DISABLE KEYS */;
/*!40000 ALTER TABLE `timetables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transcript_items`
--

DROP TABLE IF EXISTS `transcript_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transcript_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transcript_id` int(11) NOT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `course_name` varchar(255) DEFAULT NULL,
  `credit_units` decimal(4,1) DEFAULT 0.0,
  `marks` decimal(5,2) DEFAULT 0.00,
  `grade` varchar(5) DEFAULT NULL,
  `grade_points` decimal(4,2) DEFAULT 0.00,
  `semester_gpa` decimal(4,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ti_transcript` (`transcript_id`),
  CONSTRAINT `fk_ti_transcript` FOREIGN KEY (`transcript_id`) REFERENCES `transcripts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transcript_items`
--

LOCK TABLES `transcript_items` WRITE;
/*!40000 ALTER TABLE `transcript_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `transcript_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transcript_templates`
--

DROP TABLE IF EXISTS `transcript_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transcript_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_name` varchar(200) NOT NULL,
  `template_data` longtext DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transcript_templates`
--

LOCK TABLES `transcript_templates` WRITE;
/*!40000 ALTER TABLE `transcript_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `transcript_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transcripts`
--

DROP TABLE IF EXISTS `transcripts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transcripts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transcript_number` varchar(50) NOT NULL,
  `student_id` int(11) NOT NULL,
  `program` varchar(255) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `cgpa` decimal(4,2) DEFAULT 0.00,
  `total_credits` int(11) DEFAULT 0,
  `class_of_degree` varchar(100) DEFAULT NULL,
  `academic_standing` varchar(100) DEFAULT 'Good Standing',
  `purpose` text DEFAULT NULL,
  `status` enum('draft','pending','approved','rejected','generated','issued') DEFAULT 'draft',
  `requested_by` int(11) DEFAULT NULL,
  `requested_at` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `rejected_by` int(11) DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `generated_at` datetime DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `is_archived` tinyint(1) DEFAULT 0,
  `student_downloadable` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `transcript_number` (`transcript_number`),
  KEY `idx_t_student` (`student_id`),
  KEY `idx_t_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transcripts`
--

LOCK TABLES `transcripts` WRITE;
/*!40000 ALTER TABLE `transcripts` DISABLE KEYS */;
/*!40000 ALTER TABLE `transcripts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ura_reports`
--

DROP TABLE IF EXISTS `ura_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ura_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_name` varchar(200) DEFAULT NULL,
  `tax_period` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT 0.00,
  `status` varchar(50) DEFAULT 'pending',
  `report_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ura_reports`
--

LOCK TABLES `ura_reports` WRITE;
/*!40000 ALTER TABLE `ura_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `ura_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_sessions`
--

DROP TABLE IF EXISTS `user_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `token` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_sessions`
--

LOCK TABLES `user_sessions` WRITE;
/*!40000 ALTER TABLE `user_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(200) DEFAULT NULL,
  `role` varchar(50) DEFAULT 'staff',
  `status` varchar(30) DEFAULT 'active',
  `staff_id` int(10) unsigned DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'igangaschoolofl_staffs_db'
--

-- insufficient privileges to SHOW CREATE PROCEDURE `get_dashboard_statistics`
-- does igangaschoolofl_staffs_db have permissions on mysql.proc?

mysqldump.exe : mysqldump.exe: igangaschoolofl_staffs_db has insufficient privileges to SHOW CREATE PROCEDURE 
`get_dashboard_statistics`!
At line:2 char:1
+ & "C:\xampp\mysql\bin\mysqldump.exe" --user=igangaschoolofl_staffs_db ...
+ ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    + CategoryInfo          : NotSpecified: (mysqldump.exe: ...rd_statistics`!:String) [], RemoteException
    + FullyQualifiedErrorId : NativeCommandError

-- ============================================================
-- ISNM Missing Tables
-- Generated SQL: 41 tables with seed data
-- Engine: InnoDB | Charset: utf8mb4
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------
-- 1. salary_structures
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `salary_structures` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id` INT NOT NULL,
  `basic_salary` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `housing_allowance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `transport_allowance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `medical_allowance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `education_allowance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total_allowances` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `paye_tax` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `nssf` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `total_deductions` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `net_salary` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `effective_date` DATE NOT NULL,
  `status` ENUM('active','inactive','archived') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_staff_id` (`staff_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_effective_date` (`effective_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 2. disciplinary_actions
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `disciplinary_actions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `case_number` VARCHAR(50) NOT NULL,
  `staff_id` INT NOT NULL,
  `incident_date` DATE NOT NULL,
  `incident_type` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `witnesses` TEXT DEFAULT NULL,
  `evidence_path` VARCHAR(500) DEFAULT NULL,
  `action_taken` TEXT DEFAULT NULL,
  `disciplinary_level` ENUM('Verbal Warning','Written Warning','Final Warning','Suspension','Termination') NOT NULL DEFAULT 'Verbal Warning',
  `status` ENUM('Open','Under Review','Resolved','Closed') NOT NULL DEFAULT 'Open',
  `reported_by` INT DEFAULT NULL,
  `reviewed_by` INT DEFAULT NULL,
  `resolution_notes` TEXT DEFAULT NULL,
  `resolved_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uk_case_number` (`case_number`),
  INDEX `idx_staff_id` (`staff_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_disciplinary_level` (`disciplinary_level`),
  INDEX `idx_incident_date` (`incident_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 3. staff_leave_requests
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff_leave_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id` INT NOT NULL,
  `leave_type` VARCHAR(50) NOT NULL,
  `leave_type_id` INT DEFAULT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `reason` TEXT DEFAULT NULL,
  `status` ENUM('Pending','Approved','Rejected','Cancelled') NOT NULL DEFAULT 'Pending',
  `approved_by` INT DEFAULT NULL,
  `approval_date` DATETIME DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_staff_id` (`staff_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_leave_type_id` (`leave_type_id`),
  INDEX `idx_start_date` (`start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 4. employment_contracts
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `employment_contracts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id` INT NOT NULL,
  `contract_type` ENUM('Permanent','Temporary','Contract','Probation') NOT NULL DEFAULT 'Probation',
  `start_date` DATE NOT NULL,
  `end_date` DATE DEFAULT NULL,
  `probation_end_date` DATE DEFAULT NULL,
  `salary` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `terms` TEXT DEFAULT NULL,
  `status` ENUM('active','expired','terminated','renewed') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_staff_id` (`staff_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_contract_type` (`contract_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 5. staff_disciplinary
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff_disciplinary` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id` INT NOT NULL,
  `incident_date` DATE NOT NULL,
  `incident_type` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `action_taken` TEXT DEFAULT NULL,
  `status` ENUM('Open','Under Review','Resolved','Closed') NOT NULL DEFAULT 'Open',
  `reported_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_staff_id` (`staff_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_incident_date` (`incident_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 6. staff_licenses
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff_licenses` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id` INT NOT NULL,
  `license_type` VARCHAR(100) NOT NULL,
  `license_number` VARCHAR(100) NOT NULL,
  `issuing_body` VARCHAR(200) DEFAULT NULL,
  `issue_date` DATE DEFAULT NULL,
  `expiry_date` DATE DEFAULT NULL,
  `status` ENUM('valid','expired','suspended','revoked') NOT NULL DEFAULT 'valid',
  `document_path` VARCHAR(500) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_staff_id` (`staff_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_expiry_date` (`expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 7. staff_training
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff_training` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id` INT NOT NULL,
  `training_name` VARCHAR(255) NOT NULL,
  `training_provider` VARCHAR(200) DEFAULT NULL,
  `training_type` ENUM('Internal','External','Online','Workshop') NOT NULL DEFAULT 'Internal',
  `start_date` DATE DEFAULT NULL,
  `end_date` DATE DEFAULT NULL,
  `hours` INT DEFAULT 0,
  `cost` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `certificate_path` VARCHAR(500) DEFAULT NULL,
  `status` ENUM('Scheduled','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Scheduled',
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_staff_id` (`staff_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_training_type` (`training_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 8. staff_recruitment
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff_recruitment` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `position_title` VARCHAR(200) NOT NULL,
  `department` VARCHAR(100) DEFAULT NULL,
  `vacancy_count` INT NOT NULL DEFAULT 1,
  `description` TEXT DEFAULT NULL,
  `requirements` TEXT DEFAULT NULL,
  `salary_range` VARCHAR(100) DEFAULT NULL,
  `application_deadline` DATE DEFAULT NULL,
  `status` ENUM('Open','Closed','On Hold','Filled') NOT NULL DEFAULT 'Open',
  `created_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_department` (`department`),
  INDEX `idx_application_deadline` (`application_deadline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 9. leave_requests
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `leave_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id` INT NOT NULL,
  `leave_type_id` INT NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `days_requested` INT NOT NULL DEFAULT 1,
  `reason` TEXT DEFAULT NULL,
  `status` ENUM('Pending','Approved','Rejected','Cancelled') NOT NULL DEFAULT 'Pending',
  `approved_by` INT DEFAULT NULL,
  `approval_date` DATETIME DEFAULT NULL,
  `remarks` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_staff_id` (`staff_id`),
  INDEX `idx_leave_type_id` (`leave_type_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_start_date` (`start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 10. leave_balance
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `leave_balance` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id` INT NOT NULL,
  `leave_type_id` INT NOT NULL,
  `year` INT NOT NULL,
  `total_days` INT NOT NULL DEFAULT 0,
  `used_days` INT NOT NULL DEFAULT 0,
  `balance_days` INT NOT NULL DEFAULT 0,
  `remaining_days` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uk_staff_leave_year` (`staff_id`, `leave_type_id`, `year`),
  INDEX `idx_year` (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 11. leave_types
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `leave_types` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type_name` VARCHAR(100) NOT NULL,
  `leave_type_name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `default_days` INT NOT NULL DEFAULT 0,
  `is_paid` TINYINT(1) NOT NULL DEFAULT 1,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uk_type_name` (`type_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 12. disciplinary_cases
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `disciplinary_cases` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `case_number` VARCHAR(50) NOT NULL,
  `staff_id` INT NOT NULL,
  `incident_date` DATE NOT NULL,
  `incident_type` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `status` ENUM('open','investigating','resolved','closed') NOT NULL DEFAULT 'open',
  `reported_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uk_case_number` (`case_number`),
  INDEX `idx_staff_id` (`staff_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 13. job_vacancies
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `job_vacancies` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `department` VARCHAR(100) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `requirements` TEXT DEFAULT NULL,
  `vacancy_count` INT NOT NULL DEFAULT 1,
  `application_deadline` DATE DEFAULT NULL,
  `status` ENUM('open','closed','on_hold') NOT NULL DEFAULT 'open',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_department` (`department`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 14. performance_reviews
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `performance_reviews` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id` INT NOT NULL,
  `review_period` VARCHAR(50) NOT NULL,
  `review_date` DATE NOT NULL,
  `reviewer_id` INT DEFAULT NULL,
  `performance_score` DECIMAL(5,2) DEFAULT NULL,
  `rating` ENUM('Outstanding','Excellent','Good','Satisfactory','Needs Improvement') DEFAULT NULL,
  `strengths` TEXT DEFAULT NULL,
  `areas_for_improvement` TEXT DEFAULT NULL,
  `goals` TEXT DEFAULT NULL,
  `comments` TEXT DEFAULT NULL,
  `status` ENUM('draft','finalized','archived') NOT NULL DEFAULT 'draft',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_staff_id` (`staff_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_review_date` (`review_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 15. staff_trainings
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `staff_trainings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `training_type` ENUM('Internal','External','Online','Workshop') NOT NULL DEFAULT 'Internal',
  `provider` VARCHAR(200) DEFAULT NULL,
  `start_date` DATE DEFAULT NULL,
  `end_date` DATE DEFAULT NULL,
  `max_participants` INT DEFAULT 20,
  `cost` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('scheduled','in_progress','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_training_type` (`training_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 16. leave_balances
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `leave_balances` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id` INT NOT NULL,
  `leave_type_id` INT NOT NULL,
  `year` INT NOT NULL,
  `total_days` INT NOT NULL DEFAULT 0,
  `used_days` INT NOT NULL DEFAULT 0,
  `remaining_days` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uk_staff_leave_year` (`staff_id`, `leave_type_id`, `year`),
  INDEX `idx_year` (`year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 17. trainings
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `trainings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `training_type` VARCHAR(100) NOT NULL DEFAULT 'General',
  `provider` VARCHAR(200) DEFAULT NULL,
  `start_date` DATE DEFAULT NULL,
  `end_date` DATE DEFAULT NULL,
  `location` VARCHAR(200) DEFAULT NULL,
  `max_participants` INT DEFAULT 20,
  `cost` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('Planned','Active','Completed','Cancelled') NOT NULL DEFAULT 'Planned',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_training_type` (`training_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 18. employee_training
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `employee_training` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id` INT NOT NULL,
  `training_id` INT NOT NULL,
  `enrollment_date` DATE DEFAULT NULL,
  `completion_date` DATE DEFAULT NULL,
  `certificate_path` VARCHAR(500) DEFAULT NULL,
  `score` DECIMAL(5,2) DEFAULT NULL,
  `status` ENUM('Enrolled','In Progress','Completed','Dropped') NOT NULL DEFAULT 'Enrolled',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_staff_id` (`staff_id`),
  INDEX `idx_training_id` (`training_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 19. student_health_records
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `student_health_records` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `record_number` VARCHAR(50) NOT NULL,
  `student_id` INT NOT NULL,
  `blood_type` VARCHAR(10) DEFAULT NULL,
  `allergies` TEXT DEFAULT NULL,
  `medications` TEXT DEFAULT NULL,
  `medical_conditions` TEXT DEFAULT NULL,
  `disability_info` TEXT DEFAULT NULL,
  `insurance_provider` VARCHAR(200) DEFAULT NULL,
  `insurance_number` VARCHAR(100) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uk_record_number` (`record_number`),
  INDEX `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 20. health_incidents
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `health_incidents` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `incident_number` VARCHAR(50) NOT NULL,
  `student_id` INT NOT NULL,
  `incident_type` VARCHAR(100) NOT NULL,
  `symptoms` TEXT DEFAULT NULL,
  `diagnosis` TEXT DEFAULT NULL,
  `treatment` TEXT DEFAULT NULL,
  `severity` ENUM('Mild','Moderate','Severe','Critical') NOT NULL DEFAULT 'Mild',
  `location` VARCHAR(200) DEFAULT NULL,
  `reported_by` INT DEFAULT NULL,
  `status` ENUM('Open','In Treatment','Resolved','Referred') NOT NULL DEFAULT 'Open',
  `incident_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uk_incident_number` (`incident_number`),
  INDEX `idx_student_id` (`student_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_severity` (`severity`),
  INDEX `idx_incident_date` (`incident_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 21. student_health_incidents
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `student_health_incidents` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `incident_id` VARCHAR(50) NOT NULL,
  `student_id` INT NOT NULL,
  `incident_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `incident_type` VARCHAR(100) NOT NULL,
  `location` VARCHAR(200) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `action_taken` TEXT DEFAULT NULL,
  `resolved` TINYINT(1) NOT NULL DEFAULT 0,
  `resolved_at` DATETIME DEFAULT NULL,
  `resolved_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uk_incident_id` (`incident_id`),
  INDEX `idx_student_id` (`student_id`),
  INDEX `idx_resolved` (`resolved`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 22. emergency_contacts
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `emergency_contacts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(200) NOT NULL,
  `relationship` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `priority` INT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_is_active` (`is_active`),
  INDEX `idx_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 23. student_emergency_contacts
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `student_emergency_contacts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT NOT NULL,
  `contact_name` VARCHAR(200) NOT NULL,
  `relationship` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_student_id` (`student_id`),
  INDEX `idx_is_primary` (`is_primary`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 24. student_counseling_sessions
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `student_counseling_sessions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `session_id` VARCHAR(50) NOT NULL,
  `student_id` INT NOT NULL,
  `counselor_id` INT DEFAULT NULL,
  `session_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `session_type` ENUM('Academic','Personal','Career','Health','Disciplinary') NOT NULL DEFAULT 'Personal',
  `concern_category` VARCHAR(100) DEFAULT NULL,
  `session_notes` TEXT DEFAULT NULL,
  `action_plan` TEXT DEFAULT NULL,
  `follow_up_date` DATE DEFAULT NULL,
  `status` ENUM('Scheduled','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Scheduled',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uk_session_id` (`session_id`),
  INDEX `idx_student_id` (`student_id`),
  INDEX `idx_counselor_id` (`counselor_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_session_type` (`session_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 25. student_discipline
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `student_discipline` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `case_number` VARCHAR(50) NOT NULL,
  `student_id` INT NOT NULL,
  `incident_date` DATE NOT NULL,
  `incident_type` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `location` VARCHAR(200) DEFAULT NULL,
  `witnesses` TEXT DEFAULT NULL,
  `action_taken` TEXT DEFAULT NULL,
  `disciplinary_level` ENUM('Verbal Warning','Written Warning','Suspension','Expulsion') NOT NULL DEFAULT 'Verbal Warning',
  `status` ENUM('Pending','Under Review','Resolved','Closed') NOT NULL DEFAULT 'Pending',
  `reported_by` INT DEFAULT NULL,
  `resolved_by` INT DEFAULT NULL,
  `resolution_notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uk_case_number` (`case_number`),
  INDEX `idx_student_id` (`student_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_disciplinary_level` (`disciplinary_level`),
  INDEX `idx_incident_date` (`incident_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 26. hostel_allocations
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `hostel_allocations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT NOT NULL,
  `hostel_room_id` INT NOT NULL,
  `academic_year` VARCHAR(20) NOT NULL,
  `semester` INT NOT NULL DEFAULT 1,
  `allocation_date` DATE NOT NULL,
  `checkout_date` DATE DEFAULT NULL,
  `status` ENUM('Active','Inactive','Completed','Transferred') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_student_id` (`student_id`),
  INDEX `idx_hostel_room_id` (`hostel_room_id`),
  INDEX `idx_academic_year` (`academic_year`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 27. hostel_rooms
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `hostel_rooms` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hostel_name` VARCHAR(100) NOT NULL,
  `room_number` VARCHAR(20) NOT NULL,
  `floor` INT NOT NULL DEFAULT 1,
  `room_type` ENUM('Single','Double','Triple','Quad') NOT NULL DEFAULT 'Double',
  `total_beds` INT NOT NULL DEFAULT 2,
  `occupied_beds` INT NOT NULL DEFAULT 0,
  `gender` ENUM('Male','Female','Mixed') NOT NULL DEFAULT 'Male',
  `status` ENUM('Available','Full','Under Maintenance','Closed') NOT NULL DEFAULT 'Available',
  `monthly_rate` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_hostel_name` (`hostel_name`),
  INDEX `idx_status` (`status`),
  INDEX `idx_gender` (`gender`),
  INDEX `idx_room_type` (`room_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 28. student_activities
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `student_activities` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `activity_name` VARCHAR(200) NOT NULL,
  `activity_type` ENUM('Sports','Academic','Social','Religious','Club') NOT NULL DEFAULT 'Club',
  `description` TEXT DEFAULT NULL,
  `activity_date` DATETIME DEFAULT NULL,
  `location` VARCHAR(200) DEFAULT NULL,
  `organizer` VARCHAR(200) DEFAULT NULL,
  `max_participants` INT DEFAULT 50,
  `status` ENUM('Planned','Active','Completed','Cancelled') NOT NULL DEFAULT 'Planned',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_activity_type` (`activity_type`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 29. hostel_maintenance_requests
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `hostel_maintenance_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_number` VARCHAR(50) NOT NULL,
  `hostel_room_id` INT NOT NULL,
  `reported_by` INT DEFAULT NULL,
  `issue_type` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `priority` ENUM('Low','Medium','High','Urgent') NOT NULL DEFAULT 'Medium',
  `status` ENUM('Open','In Progress','Completed','Closed') NOT NULL DEFAULT 'Open',
  `assigned_to` INT DEFAULT NULL,
  `resolved_at` DATETIME DEFAULT NULL,
  `resolution_notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uk_request_number` (`request_number`),
  INDEX `idx_hostel_room_id` (`hostel_room_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 30. hostel_inspections
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `hostel_inspections` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hostel_room_id` INT NOT NULL,
  `inspector_id` INT DEFAULT NULL,
  `inspection_date` DATE NOT NULL,
  `inspection_type` ENUM('Routine','Security','Safety','Hygiene') NOT NULL DEFAULT 'Routine',
  `findings` TEXT DEFAULT NULL,
  `issues_found` TEXT DEFAULT NULL,
  `status` ENUM('Scheduled','Completed','Follow-up Required') NOT NULL DEFAULT 'Scheduled',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_hostel_room_id` (`hostel_room_id`),
  INDEX `idx_inspection_date` (`inspection_date`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 31. fee_accounts
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `fee_accounts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT NOT NULL,
  `fee_type` VARCHAR(100) NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `paid_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `balance` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `academic_year` VARCHAR(20) NOT NULL,
  `semester` INT NOT NULL DEFAULT 1,
  `due_date` DATE DEFAULT NULL,
  `status` ENUM('Pending','Partial','Paid','Waived') NOT NULL DEFAULT 'Pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_student_id` (`student_id`),
  INDEX `idx_academic_year` (`academic_year`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 32. payment_records
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payment_records` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `payment_number` VARCHAR(50) NOT NULL,
  `student_id` INT NOT NULL,
  `amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `payment_method` ENUM('Cash','Mobile Money','Bank Transfer','Cheque','Card') NOT NULL DEFAULT 'Cash',
  `payment_reference` VARCHAR(100) DEFAULT NULL,
  `payment_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `description` TEXT DEFAULT NULL,
  `status` ENUM('Completed','Pending','Failed','Refunded') NOT NULL DEFAULT 'Completed',
  `received_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uk_payment_number` (`payment_number`),
  INDEX `idx_student_id` (`student_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_payment_date` (`payment_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 33. official_duties
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `official_duties` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` INT NOT NULL,
  `duty_title` VARCHAR(200) NOT NULL,
  `duty_description` TEXT DEFAULT NULL,
  `duty_icon` VARCHAR(50) DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_role_id` (`role_id`),
  INDEX `idx_sort_order` (`sort_order`),
  INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 34. document_settings
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `document_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uk_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 35. receipt_templates
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `receipt_templates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `template_name` VARCHAR(200) NOT NULL,
  `template_type` VARCHAR(100) NOT NULL,
  `template_content` LONGTEXT DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_template_type` (`template_type`),
  INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 36. document_templates
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `document_templates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `template_name` VARCHAR(200) NOT NULL,
  `template_type` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `template_content` LONGTEXT DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_template_type` (`template_type`),
  INDEX `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 37. registrar_settings
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `registrar_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uk_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 38. student_downloads
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `student_downloads` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `file_type` VARCHAR(50) DEFAULT NULL,
  `file_size` INT DEFAULT 0,
  `download_count` INT NOT NULL DEFAULT 0,
  `category` VARCHAR(100) DEFAULT NULL,
  `uploaded_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_student_id` (`student_id`),
  INDEX `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 39. student_requests
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `student_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_number` VARCHAR(50) NOT NULL,
  `student_id` INT NOT NULL,
  `request_type` VARCHAR(100) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `priority` ENUM('Low','Medium','High','Urgent') NOT NULL DEFAULT 'Medium',
  `status` ENUM('Open','In Progress','Resolved','Closed') NOT NULL DEFAULT 'Open',
  `assigned_to` INT DEFAULT NULL,
  `resolution_notes` TEXT DEFAULT NULL,
  `resolved_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uk_request_number` (`request_number`),
  INDEX `idx_student_id` (`student_id`),
  INDEX `idx_status` (`status`),
  INDEX `idx_priority` (`priority`),
  INDEX `idx_request_type` (`request_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 40. student_messages
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `student_messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` INT NOT NULL,
  `sender_id` INT DEFAULT NULL,
  `sender_type` ENUM('student','staff','system') NOT NULL DEFAULT 'system',
  `subject` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_student_id` (`student_id`),
  INDEX `idx_is_read` (`is_read`),
  INDEX `idx_sender_type` (`sender_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ------------------------------------------------------------
-- 41. hr_users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `hr_users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(200) NOT NULL,
  `role` VARCHAR(50) NOT NULL DEFAULT 'staff',
  `status` ENUM('active','inactive','locked') NOT NULL DEFAULT 'active',
  `last_login` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE INDEX `uk_email` (`email`),
  INDEX `idx_status` (`status`),
  INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- ============================================================
-- SEED DATA
-- ============================================================

-- ------------------------------------------------------------
-- Seed: leave_types (7 rows)
-- ------------------------------------------------------------
INSERT IGNORE INTO `leave_types` (`type_name`, `leave_type_name`, `description`, `default_days`, `is_paid`, `status`) VALUES
('Annual Leave', 'Annual Leave', 'Yearly paid leave entitlement for all staff members.', 21, 1, 'active'),
('Sick Leave', 'Sick Leave', 'Leave taken due to illness or medical appointments.', 14, 1, 'active'),
('Maternity Leave', 'Maternity Leave', 'Leave for female employees during and after pregnancy.', 90, 1, 'active'),
('Paternity Leave', 'Paternity Leave', 'Leave for male employees following the birth of a child.', 7, 1, 'active'),
('Study Leave', 'Study Leave', 'Leave granted for academic pursuits and examinations.', 14, 0, 'active'),
('Compassionate Leave', 'Compassionate Leave', 'Leave taken due to bereavement or family emergency.', 5, 1, 'active'),
('Unpaid Leave', 'Unpaid Leave', 'Leave taken without pay for personal reasons.', 0, 0, 'active');

-- ------------------------------------------------------------
-- Seed: official_duties (20 rows) - Roles 1-4
-- Role 1: Director General
-- Role 2: Bursar
-- Role 3: HR Manager
-- Role 4: Registrar
-- ------------------------------------------------------------
INSERT IGNORE INTO `official_duties` (`role_id`, `duty_title`, `duty_description`, `duty_icon`, `sort_order`, `is_active`) VALUES
-- Director General (role_id = 1)
(1, 'Institutional Leadership', 'Provide overall strategic direction and vision for the institution.', 'fa-solid fa-building-columns', 1, 1),
(1, 'Policy Development', 'Develop and implement institutional policies and guidelines.', 'fa-solid fa-file-shield', 2, 1),
(1, 'Stakeholder Engagement', 'Liaise with government bodies, parents, and community partners.', 'fa-solid fa-handshake', 3, 1),
(1, 'Resource Mobilization', 'Oversee fundraising and resource allocation initiatives.', 'fa-solid fa-coins', 4, 1),
(1, 'Quality Assurance', 'Ensure academic and operational standards are maintained.', 'fa-solid fa-award', 5, 1),
-- Bursar (role_id = 2)
(2, 'Financial Management', 'Manage all institutional finances including budgeting and forecasting.', 'fa-solid fa-chart-line', 6, 1),
(2, 'Fee Collection', 'Oversee student fee collection and account reconciliation.', 'fa-solid fa-money-bill-wave', 7, 1),
(2, 'Financial Reporting', 'Prepare monthly and annual financial statements and reports.', 'fa-solid fa-file-invoice-dollar', 8, 1),
(2, 'Procurement Oversight', 'Supervise procurement processes and vendor management.', 'fa-solid fa-cart-shopping', 9, 1),
(2, 'Audit Coordination', 'Coordinate internal and external audit processes.', 'fa-solid fa-magnifying-glass-dollar', 10, 1),
-- HR Manager (role_id = 3)
(3, 'Recruitment & Onboarding', 'Manage staff recruitment, interviews, and onboarding processes.', 'fa-solid fa-user-plus', 11, 1),
(3, 'Payroll Administration', 'Administer staff payroll, salaries, and benefits.', 'fa-solid fa-money-check-dollar', 12, 1),
(3, 'Leave Management', 'Oversee staff leave applications, balances, and approvals.', 'fa-solid fa-calendar-days', 13, 1),
(3, 'Performance Management', 'Coordinate staff performance reviews and appraisals.', 'fa-solid fa-star', 14, 1),
(3, 'Disciplinary Management', 'Handle staff disciplinary cases and grievance resolution.', 'fa-solid fa-gavel', 15, 1),
-- Registrar (role_id = 4)
(4, 'Student Registration', 'Manage student enrollment, registration, and records.', 'fa-solid fa-user-graduate', 16, 1),
(4, 'Academic Records', 'Maintain and update all student academic records and transcripts.', 'fa-solid fa-folder-open', 17, 1),
(4, 'Examination Management', 'Coordinate examination schedules, results, and certifications.', 'fa-solid fa-clipboard-check', 18, 1),
(4, 'Document Issuance', 'Issue official transcripts, certificates, and letters.', 'fa-solid fa-file-certificate', 19, 1),
(4, 'Alumni Coordination', 'Manage alumni database and post-graduation interactions.', 'fa-solid fa-people-group', 20, 1);

-- ------------------------------------------------------------
-- Seed: registrar_settings (9 rows)
-- ------------------------------------------------------------
INSERT IGNORE INTO `registrar_settings` (`setting_key`, `setting_value`, `description`) VALUES
('institution_name', 'ISNM International School of Natural Medicine', 'Official name of the institution.'),
('institution_address', 'P.O. Box 1234, Kampala, Uganda', 'Physical and postal address of the institution.'),
('institution_phone', '+256-700-123456', 'Primary contact phone number.'),
('institution_email', 'info@isnm.ac.ug', 'Primary institutional email address.'),
('current_academic_year', '2025/2026', 'Currently active academic year.'),
('current_semester', '1', 'Currently active semester number.'),
('transcript_prefix', 'TRN', 'Prefix used for transcript document numbers.'),
('certificate_prefix', 'CRT', 'Prefix used for certificate document numbers.'),
('registration_prefix', 'REG', 'Prefix used for student registration numbers.');

-- ------------------------------------------------------------
-- Seed: document_settings (11 rows)
-- ------------------------------------------------------------
INSERT IGNORE INTO `document_settings` (`setting_key`, `setting_value`) VALUES
('header_text', 'ISNM International School of Natural Medicine'),
('footer_text', 'P.O. Box 1234, Kampala, Uganda | Tel: +256-700-123456'),
('institution_logo', 'assets/images/logo.png'),
('official_stamp', 'assets/images/stamp.png'),
('font_family', 'Times New Roman'),
('font_size', '12'),
('page_orientation', 'Portrait'),
('margin_top', '25'),
('margin_bottom', '25'),
('margin_left', '30'),
('margin_right', '30');

SET FOREIGN_KEY_CHECKS = 1;


-- ============================================================
-- ISNM Missing Tables - Generated from PHP code analysis
-- All 123 missing tables + 5 views
-- ============================================================

-- ACADEMIC RECORDS

CREATE TABLE IF NOT EXISTS `academic_analytics` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `academic_year` VARCHAR(20), `semester` VARCHAR(50),
  `program` VARCHAR(255), `metric_name` VARCHAR(100),
  `metric_value` DECIMAL(10,2), `calculated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `academic_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL, `academic_year` VARCHAR(20), `semester` VARCHAR(50),
  `year` INT, `course_code` VARCHAR(20), `course_name` VARCHAR(255),
  `course_type` VARCHAR(50), `credits` INT DEFAULT 0,
  `assessment_marks` DECIMAL(5,2) DEFAULT 0, `exam_marks` DECIMAL(5,2) DEFAULT 0,
  `total_marks` DECIMAL(5,2) DEFAULT 0, `grade` VARCHAR(5),
  `grade_points` DECIMAL(4,2) DEFAULT 0, `gpa_contribution` DECIMAL(4,2) DEFAULT 0,
  `gpa` DECIMAL(4,2) DEFAULT 0, `lecturer` VARCHAR(255), `lecturer_id` INT,
  `assessment_type` VARCHAR(20) DEFAULT 'Exam', `marks` DECIMAL(5,2) DEFAULT 0,
  `entered_by` INT, `graded_by` INT, `entry_date` DATE,
  `updated_by` INT, `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_student_id` (`student_id`), KEY `idx_academic_year` (`academic_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `academic_reports` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `report_type` VARCHAR(100), `academic_year` VARCHAR(20), `semester` VARCHAR(50),
  `program` VARCHAR(255), `generated_by` INT, `report_data` LONGTEXT,
  `status` VARCHAR(20) DEFAULT 'generated', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `academic_timetable` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `timetable_id` VARCHAR(50), `academic_year` VARCHAR(20), `semester` VARCHAR(50),
  `program_code` VARCHAR(50), `course_code` VARCHAR(20), `course_id` INT,
  `day_of_week` VARCHAR(20), `start_time` TIME, `end_time` TIME,
  `venue` VARCHAR(100), `lecturer_id` INT, `created_by` INT,
  `timetable_status` VARCHAR(20) DEFAULT 'Active', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `registrar_academic_calendar` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `academic_year` VARCHAR(20), `semester` VARCHAR(50),
  `event_name` VARCHAR(255), `start_date` DATE, `end_date` DATE,
  `event_type` VARCHAR(100), `description` TEXT,
  `is_active` TINYINT(1) DEFAULT 1, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `registrar_academic_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT, `academic_year` VARCHAR(20), `semester` VARCHAR(50),
  `record_type` VARCHAR(100), `record_data` LONGTEXT,
  `status` VARCHAR(20) DEFAULT 'active', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `registrar_graduation` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT, `academic_year` VARCHAR(20), `program` VARCHAR(255),
  `graduation_date` DATE, `classification` VARCHAR(100),
  `status` VARCHAR(50) DEFAULT 'pending', `approved_by` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `registrar_transcripts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `transcript_number` VARCHAR(50), `student_id` INT, `academic_year` VARCHAR(20),
  `program` VARCHAR(255), `transcript_status` VARCHAR(50) DEFAULT 'Pending',
  `request_date` DATETIME, `generated_by` INT, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `result_publication` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `publication_number` VARCHAR(50) NOT NULL, `academic_year` VARCHAR(20),
  `semester` VARCHAR(50), `program` VARCHAR(255), `course_code` VARCHAR(20),
  `scheduled_date` DATETIME, `published_by` INT, `status` VARCHAR(50) DEFAULT 'Published',
  `published_at` DATETIME, `notification_sent` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `grade_change_history` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT, `course_code` VARCHAR(20), `academic_year` VARCHAR(20),
  `semester` VARCHAR(50), `old_grade` VARCHAR(5), `new_grade` VARCHAR(5),
  `old_marks` DECIMAL(5,2), `new_marks` DECIMAL(5,2), `reason` TEXT,
  `changed_by` INT, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `grading_notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT, `course_code` VARCHAR(20), `academic_year` VARCHAR(20),
  `semester` VARCHAR(50), `notification_type` VARCHAR(100), `message` TEXT,
  `is_read` TINYINT(1) DEFAULT 0, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `course_assignments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `lecturer_id` INT, `course_code` VARCHAR(20), `course_name` VARCHAR(255),
  `course_id` INT, `semester` VARCHAR(50), `academic_year` VARCHAR(20),
  `classroom` VARCHAR(100), `assigned_by` INT, `status` VARCHAR(20) DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_lecturer` (`lecturer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- HR & STAFF MANAGEMENT

CREATE TABLE IF NOT EXISTS `hr_activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT, `user_name` VARCHAR(255), `user_role` VARCHAR(100),
  `action_type` VARCHAR(100), `entity_type` VARCHAR(100),
  `ip_address` VARCHAR(45), `user_agent` TEXT, `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hr_announcements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255), `content` TEXT, `priority` VARCHAR(20) DEFAULT 'normal',
  `target_audience` VARCHAR(100), `created_by` INT,
  `is_active` TINYINT(1) DEFAULT 1, `expires_at` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hr_reports` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `report_type` VARCHAR(100), `report_title` VARCHAR(255),
  `report_data` LONGTEXT, `generated_by` INT,
  `status` VARCHAR(20) DEFAULT 'generated', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hr_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL, `setting_value` TEXT,
  `description` TEXT, `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hr_users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL, `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(255), `role` VARCHAR(100),
  `status` VARCHAR(20) DEFAULT 'active', `last_login` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `leave_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `staff_id` INT NOT NULL, `leave_type_id` INT,
  `start_date` DATE, `end_date` DATE, `reason` TEXT,
  `status` VARCHAR(20) DEFAULT 'Pending', `reviewed_by` INT,
  `approval_date` DATETIME, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `leave_types` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `type_name` VARCHAR(100), `leave_type_name` VARCHAR(100),
  `days_per_year` INT DEFAULT 0, `description` TEXT,
  `is_active` TINYINT(1) DEFAULT 1, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `leave_balance` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `staff_id` INT NOT NULL, `leave_type_id` INT, `year` INT,
  `total_days` INT DEFAULT 0, `used_days` INT DEFAULT 0,
  `remaining_days` INT DEFAULT 0, `balance_days` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `leave_balances` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `staff_id` INT NOT NULL, `leave_type_id` INT, `year` INT,
  `total_days` INT DEFAULT 0, `used_days` INT DEFAULT 0,
  `remaining_days` INT DEFAULT 0, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `employment_contracts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `staff_id` INT NOT NULL, `contract_type` VARCHAR(100),
  `start_date` DATE, `end_date` DATE, `salary` DECIMAL(15,2),
  `status` VARCHAR(20) DEFAULT 'active', `terms` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `employment_details` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `staff_id` INT NOT NULL, `employment_type` VARCHAR(100),
  `hire_date` DATE, `department_id` INT, `position` VARCHAR(255),
  `salary_grade` VARCHAR(50), `status` VARCHAR(20) DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disciplinary_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT, `staff_id` INT, `incident_date` DATE,
  `incident_type` VARCHAR(100), `description` TEXT, `action_taken` TEXT,
  `status` VARCHAR(20) DEFAULT 'open', `reported_by` INT,
  `resolved_by` INT, `resolved_date` DATE, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_student_id` (`student_id`), KEY `idx_staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disciplinary_actions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `staff_id` INT NOT NULL, `incident_date` DATE,
  `incident_type` VARCHAR(100), `description` TEXT, `action_taken` TEXT,
  `status` VARCHAR(20) DEFAULT 'Open', `reported_by` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `disciplinary_cases` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `case_number` VARCHAR(50), `party_id` INT, `party_type` VARCHAR(50),
  `incident_date` DATE, `description` TEXT, `status` VARCHAR(20) DEFAULT 'open',
  `assigned_to` INT, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `incident_reports` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `report_number` VARCHAR(50), `reported_by` INT,
  `incident_type` VARCHAR(100), `severity` VARCHAR(20), `description` TEXT,
  `location` VARCHAR(255), `status` VARCHAR(20) DEFAULT 'open',
  `resolved_by` INT, `resolved_date` DATETIME, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `job_vacancies` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255), `department_id` INT, `description` TEXT,
  `requirements` TEXT, `salary_range` VARCHAR(100),
  `status` VARCHAR(20) DEFAULT 'open', `posted_date` DATE,
  `closing_date` DATE, `created_by` INT, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE IF NOT EXISTS `job_offers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `application_id` INT, `staff_id` INT, `position` VARCHAR(255),
  `salary` DECIMAL(15,2), `start_date` DATE, `status` VARCHAR(20) DEFAULT 'pending',
  `offered_by` INT, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `performance_reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `staff_id` INT NOT NULL, `reviewer_id` INT, `reviewed_by` INT,
  `review_period` VARCHAR(50), `academic_year` VARCHAR(20),
  `overall_score` DECIMAL(5,2), `comments` TEXT,
  `status` VARCHAR(20) DEFAULT 'draft', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `performance_metrics` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT, `staff_id` INT, `metric_type` VARCHAR(100),
  `metric_name` VARCHAR(255), `metric_value` DECIMAL(10,2),
  `metric_unit` VARCHAR(50), `target_value` DECIMAL(10,2),
  `period` VARCHAR(50), `recorded_at` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `appraisal_periods` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `period_name` VARCHAR(100), `start_date` DATE, `end_date` DATE,
  `status` VARCHAR(20) DEFAULT 'active', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `appraisal_ratings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `appraisal_id` INT, `staff_id` INT, `criteria` VARCHAR(255),
  `rating` DECIMAL(3,2), `comments` TEXT, `rated_by` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `interview_scheduling` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `application_id` INT, `interview_date` DATETIME, `interviewer_id` INT,
  `location` VARCHAR(255), `status` VARCHAR(20) DEFAULT 'scheduled',
  `notes` TEXT, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



CREATE TABLE IF NOT EXISTS `delegation_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `delegated_by` INT, `delegated_to` INT, `duty_description` TEXT,
  `start_date` DATE, `end_date` DATE, `status` VARCHAR(20) DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `official_duties` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `role_id` INT, `duty_title` VARCHAR(255), `duty_description` TEXT,
  `duty_icon` VARCHAR(100), `sort_order` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- FINANCE & BURSAR

CREATE TABLE IF NOT EXISTS `fee_accounts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT, `fee_type` VARCHAR(100),
  `amount` DECIMAL(15,2) DEFAULT 0, `paid` DECIMAL(15,2) DEFAULT 0,
  `balance` DECIMAL(15,2) DEFAULT 0, `academic_year` VARCHAR(20),
  `semester` VARCHAR(50), `status` VARCHAR(20) DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payment_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT, `fee_account_id` INT, `amount` DECIMAL(15,2),
  `payment_method` VARCHAR(50), `reference_number` VARCHAR(100),
  `status` VARCHAR(20) DEFAULT 'Completed', `processed_by` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payment_methods` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `method_name` VARCHAR(100), `method_code` VARCHAR(50),
  `is_active` TINYINT(1) DEFAULT 1, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payment_routes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `route_name` VARCHAR(100), `route_code` VARCHAR(50),
  `description` TEXT, `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cashbook` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `transaction_date` DATE NOT NULL, `transaction_type` VARCHAR(20),
  `description` VARCHAR(255), `amount` DECIMAL(15,2),
  `category` VARCHAR(50), `reference` VARCHAR(100),
  `balance_after` DECIMAL(15,2), `created_by` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_transaction_date` (`transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bank_reconciliation` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `reconciliation_date` DATE NOT NULL, `bank_balance` DECIMAL(15,2),
  `book_balance` DECIMAL(15,2), `difference` DECIMAL(15,2),
  `notes` TEXT, `status` VARCHAR(20) DEFAULT 'unreconciled',
  `reconciled_by` INT, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bank_reconciliations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `reconciliation_date` DATE NOT NULL, `bank_balance` DECIMAL(15,2),
  `book_balance` DECIMAL(15,2), `difference` DECIMAL(15,2),
  `notes` TEXT, `status` VARCHAR(20) DEFAULT 'unreconciled',
  `reconciled_by` INT, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `penalty_config` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `penalty_name` VARCHAR(200), `penalty_type` VARCHAR(20),
  `penalty_value` DECIMAL(15,2), `grace_days` INT DEFAULT 0,
  `max_charge` DECIMAL(15,2), `status` VARCHAR(20) DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `penalty_configurations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `penalty_name` VARCHAR(200), `penalty_type` VARCHAR(50),
  `amount` DECIMAL(15,2), `description` TEXT,
  `is_active` TINYINT(1) DEFAULT 1, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `receipt_templates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `template_name` VARCHAR(200), `template_type` VARCHAR(100),
  `template_content` LONGTEXT, `header_text` TEXT, `footer_text` TEXT,
  `is_active` TINYINT(1) DEFAULT 1, `created_by` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `document_templates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `template_name` VARCHAR(200), `template_type` VARCHAR(100),
  `template_content` LONGTEXT, `is_default` TINYINT(1) DEFAULT 0,
  `is_deleted` TINYINT(1) DEFAULT 0, `created_by` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `document_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL, `setting_value` TEXT,
  `description` TEXT, `updated_at` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `document_generation_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `document_type` VARCHAR(100), `document_id` INT,
  `file_path` VARCHAR(500), `generated_by` INT,
  `created_at` DATETIME, `created_at_ts` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `document_print_configs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `document_type` VARCHAR(100), `paper_size` VARCHAR(20) DEFAULT 'A4',
  `orientation` VARCHAR(20) DEFAULT 'portrait',
  `margin_top` INT DEFAULT 20, `margin_bottom` INT DEFAULT 20,
  `margin_left` INT DEFAULT 15, `margin_right` INT DEFAULT 15,
  `is_active` TINYINT(1) DEFAULT 1, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expenditures` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `expenditure_number` VARCHAR(50), `description` VARCHAR(255),
  `amount` DECIMAL(15,2), `category` VARCHAR(100),
  `department_id` INT, `budget_line_id` INT,
  `status` VARCHAR(20) DEFAULT 'pending', `approved_by` INT,
  `expenditure_date` DATE, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `expense_approvals` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `expense_id` INT, `expense_type` VARCHAR(100),
  `amount` DECIMAL(15,2), `requested_by` INT, `approved_by` INT,
  `status` VARCHAR(20) DEFAULT 'pending', `notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `departmental_budgets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `department_id` INT, `academic_year` VARCHAR(20),
  `allocated_amount` DECIMAL(15,2) DEFAULT 0, `spent_amount` DECIMAL(15,2) DEFAULT 0,
  `remaining_amount` DECIMAL(15,2) DEFAULT 0, `status` VARCHAR(20) DEFAULT 'active',
  `approved_by` INT, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `budget_lines` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `budget_id` INT, `line_item` VARCHAR(255),
  `allocated_amount` DECIMAL(15,2) DEFAULT 0, `spent_amount` DECIMAL(15,2) DEFAULT 0,
  `description` TEXT, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `invoice_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_number` VARCHAR(50), `student_id` INT,
  `amount` DECIMAL(15,2), `due_date` DATE,
  `status` VARCHAR(20) DEFAULT 'pending', `paid_amount` DECIMAL(15,2) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `financial_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `record_type` VARCHAR(50), `reference_number` VARCHAR(100),
  `description` VARCHAR(255), `amount` DECIMAL(15,2),
  `category` VARCHAR(100), `transaction_date` DATE,
  `created_by` INT, `status` VARCHAR(20) DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `financial_audit_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `action` VARCHAR(100), `table_name` VARCHAR(100), `record_id` INT,
  `old_values` JSON, `new_values` JSON, `performed_by` INT,
  `ip_address` VARCHAR(45), `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `advanced_reports` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `report_type` VARCHAR(100), `report_name` VARCHAR(255),
  `report_data` LONGTEXT, `parameters` JSON, `generated_by` INT,
  `status` VARCHAR(20) DEFAULT 'generated', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- BURSAR SPECIFIC

CREATE TABLE IF NOT EXISTS `bursar_allowances` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `staff_id` INT, `allowance_type` VARCHAR(100),
  `amount` DECIMAL(15,2), `effective_date` DATE,
  `status` VARCHAR(20) DEFAULT 'active', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bursar_assets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `asset_name` VARCHAR(255), `asset_code` VARCHAR(50),
  `category` VARCHAR(100), `purchase_date` DATE,
  `purchase_cost` DECIMAL(15,2), `current_value` DECIMAL(15,2),
  `condition_status` VARCHAR(50), `location` VARCHAR(255),
  `status` VARCHAR(20) DEFAULT 'active', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bursar_budget_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `budget_id` INT, `item_name` VARCHAR(255),
  `allocated_amount` DECIMAL(15,2), `spent_amount` DECIMAL(15,2) DEFAULT 0,
  `category` VARCHAR(100), `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bursar_daily_collections` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `collection_date` DATE NOT NULL, `total_collected` DECIMAL(15,2) DEFAULT 0,
  `collection_count` INT DEFAULT 0, `payment_method` VARCHAR(50),
  `collected_by` INT, `status` VARCHAR(20) DEFAULT 'recorded',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bursar_deductions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `staff_id` INT, `deduction_type` VARCHAR(100),
  `amount` DECIMAL(15,2), `description` TEXT,
  `is_active` TINYINT(1) DEFAULT 1, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bursar_expenses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `expense_number` VARCHAR(50), `description` VARCHAR(255),
  `amount` DECIMAL(15,2), `category` VARCHAR(100),
  `expense_date` DATE, `approved_by` INT,
  `status` VARCHAR(20) DEFAULT 'pending', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bursar_fee_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `fee_name` VARCHAR(255), `fee_code` VARCHAR(50),
  `amount` DECIMAL(15,2), `fee_type` VARCHAR(100),
  `academic_year` VARCHAR(20), `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bursar_fee_reminders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT, `fee_account_id` INT,
  `reminder_type` VARCHAR(50), `message` TEXT,
  `sent_at` DATETIME, `status` VARCHAR(20) DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bursar_invoices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `invoice_number` VARCHAR(50), `student_id` INT,
  `amount` DECIMAL(15,2), `due_date` DATE,
  `status` VARCHAR(20) DEFAULT 'pending', `paid_amount` DECIMAL(15,2) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bursar_payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `payment_number` VARCHAR(50), `student_id` INT, `invoice_id` INT,
  `amount` DECIMAL(15,2), `payment_method` VARCHAR(50),
  `reference_number` VARCHAR(100), `status` VARCHAR(20) DEFAULT 'completed',
  `processed_by` INT, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bursar_payroll` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `payroll_period` VARCHAR(50), `staff_id` INT,
  `basic_salary` DECIMAL(15,2), `allowances` DECIMAL(15,2) DEFAULT 0,
  `deductions` DECIMAL(15,2) DEFAULT 0, `net_salary` DECIMAL(15,2),
  `status` VARCHAR(20) DEFAULT 'pending', `processed_by` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bursar_penalties` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT, `penalty_type` VARCHAR(100),
  `amount` DECIMAL(15,2), `description` TEXT,
  `status` VARCHAR(20) DEFAULT 'active', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bursar_receipts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `receipt_number` VARCHAR(50), `payment_id` INT, `student_id` INT,
  `amount` DECIMAL(15,2), `payment_method` VARCHAR(50),
  `issued_by` INT, `issued_date` DATE, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bursar_settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL, `setting_value` TEXT,
  `description` TEXT, `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `bursar_tax_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `staff_id` INT, `tax_type` VARCHAR(100),
  `amount` DECIMAL(15,2), `tax_period` VARCHAR(50),
  `status` VARCHAR(20) DEFAULT 'pending', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ACTIVITY & AUDIT LOGS

CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT, `activity` VARCHAR(255), `details` TEXT,
  `ip_address` VARCHAR(45), `created_at` DATETIME,
  `created_at_ts` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT, `action` VARCHAR(100), `module` VARCHAR(100),
  `entity_type` VARCHAR(100), `entity_id` INT, `description` TEXT,
  `ip_address` VARCHAR(45), `user_agent` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_user_id` (`user_id`), KEY `idx_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `access_control_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT, `user_name` VARCHAR(255), `action` VARCHAR(100),
  `resource` VARCHAR(255), `access_time` DATETIME,
  `ip_address` VARCHAR(45), `status` VARCHAR(20) DEFAULT 'success',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_access_time` (`access_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `analytics_cache` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `cache_key` VARCHAR(255) NOT NULL, `cache_value` LONGTEXT,
  `expires_at` DATETIME, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_cache_key` (`cache_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `api_keys` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `key_name` VARCHAR(100), `api_key` VARCHAR(255) NOT NULL,
  `user_id` INT, `permissions` JSON, `is_active` TINYINT(1) DEFAULT 1,
  `expires_at` DATETIME, `last_used_at` DATETIME,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_api_key` (`api_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;









-- LIBRARY

CREATE TABLE IF NOT EXISTS `library_members` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `member_id` VARCHAR(50), `student_id` INT, `staff_id` INT,
  `member_type` VARCHAR(50) DEFAULT 'Student', `full_name` VARCHAR(255),
  `email` VARCHAR(255), `phone` VARCHAR(50),
  `status` VARCHAR(20) DEFAULT 'Active', `registration_date` DATE,
  `expiry_date` DATE, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `library_management` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `book_title` VARCHAR(255), `author` VARCHAR(255),
  `isbn` VARCHAR(50), `category` VARCHAR(100),
  `quantity` INT DEFAULT 1, `available` INT DEFAULT 1,
  `status` VARCHAR(20) DEFAULT 'Available', `location` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `library_transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `book_id` INT, `member_id` INT, `borrow_date` DATE,
  `due_date` DATE, `return_date` DATE,
  `status` VARCHAR(20) DEFAULT 'borrowed',
  `fine_amount` DECIMAL(10,2) DEFAULT 0, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_member_id` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `library_digital_resources` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255), `resource_type` VARCHAR(100),
  `file_path` VARCHAR(500), `file_size` BIGINT,
  `category` VARCHAR(100), `uploaded_by` INT,
  `download_count` INT DEFAULT 0, `is_active` TINYINT(1) DEFAULT 1,
  `added_date` DATETIME, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `library_borrowing` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `book_id` INT, `member_id` INT, `borrow_date` DATE,
  `due_date` DATE, `return_date` DATE,
  `status` VARCHAR(20) DEFAULT 'borrowed',
  `renewal_count` INT DEFAULT 0, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;





-- SECURITY & FACILITIES

CREATE TABLE IF NOT EXISTS `emergency_contacts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `contact_name` VARCHAR(255), `relationship` VARCHAR(100),
  `phone_primary` VARCHAR(50), `phone_secondary` VARCHAR(50),
  `email` VARCHAR(255), `address` TEXT, `staff_id` INT, `student_id` INT,
  `is_active` TINYINT(1) DEFAULT 1, `priority` INT DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `fuel_management` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `vehicle_id` INT, `fuel_type` VARCHAR(50),
  `fuel_quantity` DECIMAL(10,2), `cost_per_unit` DECIMAL(10,2),
  `total_cost` DECIMAL(15,2), `fueling_date` DATE,
  `odometer_reading` INT, `driver_id` INT,
  `station` VARCHAR(255), `receipt_number` VARCHAR(100),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;







-- HEALTH & WELFARE

CREATE TABLE IF NOT EXISTS `health_incidents` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `incident_number` VARCHAR(50), `student_id` INT, `staff_id` INT,
  `incident_type` VARCHAR(100), `symptoms` TEXT, `severity` VARCHAR(20),
  `location` VARCHAR(255), `action_taken` TEXT, `treatment_given` TEXT,
  `referred_to` VARCHAR(255), `parent_notified` TINYINT(1) DEFAULT 0,
  `follow_up_date` DATE, `status` VARCHAR(20) DEFAULT 'Reported',
  `reported_by` INT, `notes` TEXT, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- DOCUMENTS & REAL-TIME

CREATE TABLE IF NOT EXISTS `real_time_updates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `update_type` VARCHAR(100), `update_title` VARCHAR(255),
  `update_description` TEXT, `update_data` JSON,
  `priority` VARCHAR(20) DEFAULT 'normal', `target_user` INT,
  `is_read` TINYINT(1) DEFAULT 0, `created_at` DATETIME,
  `created_at_ts` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `email_notifications_queue` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `recipient_email` VARCHAR(255), `recipient_name` VARCHAR(255),
  `subject` VARCHAR(500), `email_content` TEXT,
  `email_type` VARCHAR(50), `priority` VARCHAR(20) DEFAULT 'normal',
  `status` VARCHAR(20) DEFAULT 'pending', `scheduled_at` DATETIME,
  `sent_at` DATETIME, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `news_images` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `news_id` INT, `image_path` VARCHAR(500),
  `image_caption` VARCHAR(255), `sort_order` INT DEFAULT 0,
  `is_primary` TINYINT(1) DEFAULT 0, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;







CREATE TABLE IF NOT EXISTS `dashboard_configs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT, `dashboard_type` VARCHAR(100),
  `config_data` JSON, `is_default` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `dashboard_updates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `dashboard_type` VARCHAR(100), `update_type` VARCHAR(100),
  `update_data` JSON, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



-- NURSING & MIDWIFERY

CREATE TABLE IF NOT EXISTS `nursing_students` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL, `program` VARCHAR(255),
  `cohort` VARCHAR(50), `clinical_hours` INT DEFAULT 0,
  `status` VARCHAR(20) DEFAULT 'active', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nursing_clinical_logbook` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT, `placement_id` INT, `log_date` DATE,
  `shift_type` VARCHAR(50), `hours` DECIMAL(4,1),
  `activities` TEXT, `supervisor_signature` VARCHAR(255),
  `status` VARCHAR(20) DEFAULT 'pending', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nursing_clinical_placements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT, `facility_name` VARCHAR(255),
  `department` VARCHAR(100), `start_date` DATE, `end_date` DATE,
  `supervisor_name` VARCHAR(255), `status` VARCHAR(20) DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nursing_practical_assessment` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT, `assessment_type` VARCHAR(100),
  `skill_area` VARCHAR(255), `score` DECIMAL(5,2),
  `max_score` DECIMAL(5,2), `assessor_id` INT,
  `assessment_date` DATE, `comments` TEXT,
  `status` VARCHAR(20) DEFAULT 'completed', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `nursing_skills_training` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `skill_name` VARCHAR(255), `skill_category` VARCHAR(100),
  `description` TEXT, `duration_hours` DECIMAL(5,1),
  `max_participants` INT, `instructor_id` INT,
  `status` VARCHAR(20) DEFAULT 'scheduled', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `midwifery_students` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT NOT NULL, `program` VARCHAR(255),
  `cohort` VARCHAR(50), `clinical_hours` INT DEFAULT 0,
  `status` VARCHAR(20) DEFAULT 'active', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `midwifery_antenatal_care` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT, `patient_id` INT, `visit_date` DATE,
  `gestational_age` VARCHAR(50), `blood_pressure` VARCHAR(20),
  `weight` DECIMAL(5,2), `fundal_height` DECIMAL(5,1),
  `fetal_heart_rate` INT, `notes` TEXT, `assessor_id` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `midwifery_family_planning` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT, `patient_id` INT, `method` VARCHAR(100),
  `counseling_date` DATE, `follow_up_date` DATE,
  `notes` TEXT, `assessor_id` INT, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `midwifery_labor_delivery` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT, `patient_id` INT, `delivery_date` DATETIME,
  `delivery_type` VARCHAR(100), `baby_weight` DECIMAL(5,2),
  `apgar_score` INT, `complications` TEXT, `outcome` VARCHAR(100),
  `assessor_id` INT, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `midwifery_postnatal_care` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT, `patient_id` INT, `visit_date` DATE,
  `days_postpartum` INT, `maternal_condition` TEXT,
  `baby_condition` TEXT, `breastfeeding_status` VARCHAR(100),
  `notes` TEXT, `assessor_id` INT, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- LABORATORY

CREATE TABLE IF NOT EXISTS `lab_chemical_inventory` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `chemical_name` VARCHAR(255), `chemical_formula` VARCHAR(100),
  `quantity` DECIMAL(10,2), `unit` VARCHAR(50),
  `storage_location` VARCHAR(255), `hazard_level` VARCHAR(50),
  `expiry_date` DATE, `reorder_level` DECIMAL(10,2),
  `status` VARCHAR(20) DEFAULT 'in_stock', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lab_equipment_maintenance` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `equipment_id` INT, `maintenance_type` VARCHAR(100),
  `description` TEXT, `maintenance_date` DATE,
  `next_maintenance_date` DATE, `cost` DECIMAL(15,2),
  `performed_by` VARCHAR(255), `status` VARCHAR(20) DEFAULT 'completed',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lab_experiments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `experiment_name` VARCHAR(255), `experiment_code` VARCHAR(50),
  `description` TEXT, `course_id` INT, `instructor_id` INT,
  `scheduled_date` DATE, `duration_hours` DECIMAL(4,1),
  `max_students` INT, `status` VARCHAR(20) DEFAULT 'scheduled',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lab_inventory` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `item_name` VARCHAR(255), `item_code` VARCHAR(50),
  `category` VARCHAR(100), `quantity` INT DEFAULT 0,
  `unit` VARCHAR(50), `location` VARCHAR(255),
  `reorder_level` INT DEFAULT 0, `status` VARCHAR(20) DEFAULT 'in_stock',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lab_safety_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `record_type` VARCHAR(100), `description` TEXT,
  `location` VARCHAR(255), `hazard_level` VARCHAR(50),
  `reported_by` INT, `action_taken` TEXT,
  `status` VARCHAR(20) DEFAULT 'open', `inspection_date` DATE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `lab_skills_sessions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `session_name` VARCHAR(255), `skill_area` VARCHAR(100),
  `description` TEXT, `instructor_id` INT,
  `scheduled_date` DATE, `duration_hours` DECIMAL(4,1),
  `max_participants` INT, `status` VARCHAR(20) DEFAULT 'scheduled',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- RECRUITMENT

CREATE TABLE IF NOT EXISTS `recruitment_applications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `vacancy_id` INT, `applicant_name` VARCHAR(255),
  `applicant_email` VARCHAR(255), `applicant_phone` VARCHAR(50),
  `cv_path` VARCHAR(500), `cover_letter` TEXT,
  `status` VARCHAR(20) DEFAULT 'received', `reviewed_by` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `recruitment_jobs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(255), `department_id` INT,
  `description` TEXT, `requirements` TEXT,
  `salary_range` VARCHAR(100), `status` VARCHAR(20) DEFAULT 'open',
  `posted_date` DATE, `closing_date` DATE, `created_by` INT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `application_reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `application_id` INT, `reviewer_id` INT,
  `rating` DECIMAL(3,2), `comments` TEXT,
  `recommendation` VARCHAR(100), `status` VARCHAR(20) DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- INFRASTRUCTURE & ASSETS

CREATE TABLE IF NOT EXISTS `asset_depreciation` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `asset_id` INT, `depreciation_method` VARCHAR(50),
  `annual_rate` DECIMAL(5,2), `accumulated_depreciation` DECIMAL(15,2) DEFAULT 0,
  `book_value` DECIMAL(15,2), `depreciation_date` DATE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `inventory_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `item_name` VARCHAR(255), `item_code` VARCHAR(50),
  `category` VARCHAR(100), `quantity` INT DEFAULT 0,
  `unit` VARCHAR(50), `unit_cost` DECIMAL(15,2),
  `location` VARCHAR(255), `reorder_level` INT DEFAULT 0,
  `status` VARCHAR(20) DEFAULT 'in_stock', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `inventory_transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `item_id` INT, `transaction_type` VARCHAR(50),
  `quantity` INT, `unit_cost` DECIMAL(15,2),
  `total_cost` DECIMAL(15,2), `reference_number` VARCHAR(100),
  `notes` TEXT, `performed_by` INT, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;





-- COMPLIANCE & ACCREDITATION

CREATE TABLE IF NOT EXISTS `accreditation_management` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `accreditation_type` VARCHAR(100), `body_name` VARCHAR(255),
  `status` VARCHAR(50), `valid_from` DATE, `valid_until` DATE,
  `documents` JSON, `notes` TEXT, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;





CREATE TABLE IF NOT EXISTS `compliance_tracking` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `requirement_id` INT, `period` VARCHAR(50),
  `status` VARCHAR(20) DEFAULT 'pending', `evidence_path` VARCHAR(500),
  `submitted_by` INT, `verified_by` INT, `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;







-- REMAINING TABLES

CREATE TABLE IF NOT EXISTS `clinical_rotations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` INT, `rotation_name` VARCHAR(255),
  `department` VARCHAR(100), `facility` VARCHAR(255),
  `start_date` DATE, `end_date` DATE, `supervisor_id` INT,
  `hours_completed` INT DEFAULT 0, `status` VARCHAR(20) DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cost_centers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `center_code` VARCHAR(50), `center_name` VARCHAR(255),
  `department_id` INT, `description` TEXT,
  `budget_allocated` DECIMAL(15,2) DEFAULT 0,
  `budget_spent` DECIMAL(15,2) DEFAULT 0,
  `status` VARCHAR(20) DEFAULT 'active', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;









CREATE TABLE IF NOT EXISTS `programs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `program_code` VARCHAR(50), `program_name` VARCHAR(255),
  `department_id` INT, `duration_years` INT, `description` TEXT,
  `status` VARCHAR(20) DEFAULT 'active', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `proof_of_payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `payment_id` INT, `student_id` INT,
  `file_path` VARCHAR(500), `file_name` VARCHAR(255),
  `uploaded_by` INT, `verified_by` INT,
  `status` VARCHAR(20) DEFAULT 'pending', `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

































-- ============================================================
-- VIEWS
-- ============================================================

CREATE OR REPLACE VIEW `hr_leave_summary` AS
SELECT lr.id, lr.staff_id, s.first_name, s.last_name,
  lt.type_name AS leave_type, lr.start_date, lr.end_date,
  DATEDIFF(lr.end_date, lr.start_date) + 1 AS days_requested,
  lr.status, lr.reason, lr.created_at
FROM leave_requests lr
LEFT JOIN staff s ON lr.staff_id = s.id
LEFT JOIN leave_types lt ON lr.leave_type_id = lt.id;

CREATE OR REPLACE VIEW `hr_performance_summary` AS
SELECT pr.id, pr.staff_id, s.first_name, s.last_name,
  d.name AS department, pr.review_period, pr.overall_score,
  pr.status, pr.created_at
FROM performance_reviews pr
LEFT JOIN staff s ON pr.staff_id = s.id
LEFT JOIN staff_departments sd ON s.id = sd.staff_id
LEFT JOIN departments d ON sd.department_id = d.id;

CREATE OR REPLACE VIEW `hr_staff_by_department` AS
SELECT s.id AS staff_id, s.first_name, s.last_name, s.email,
  d.name AS department, s.employment_status, s.date_of_joining
FROM staff s
LEFT JOIN staff_departments sd ON s.id = sd.staff_id
LEFT JOIN departments d ON sd.department_id = d.id;

CREATE OR REPLACE VIEW `hr_staff_search_view` AS
SELECT s.id, s.staff_id AS staff_number, s.first_name, s.last_name,
  CONCAT(s.first_name, ' ', s.last_name) AS full_name,
  s.email, s.phone, d.name AS department, s.position, s.employment_status
FROM staff s
LEFT JOIN staff_departments sd ON s.id = sd.staff_id
LEFT JOIN departments d ON sd.department_id = d.id;

CREATE OR REPLACE VIEW `all_students_view` AS
SELECT st.id, st.student_id AS student_number, st.first_name, st.last_name,
  CONCAT(st.first_name, ' ', st.last_name) AS full_name,
  st.email, st.phone, st.program, st.department,
  st.year_of_study, st.status
FROM students st;


SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
