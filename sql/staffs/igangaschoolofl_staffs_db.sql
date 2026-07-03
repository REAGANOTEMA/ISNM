/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.6.23-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: igangaschoolofl_staffs_db
-- ------------------------------------------------------
-- Server version	10.6.23-MariaDB-cll-lve

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `academic_analytics`
--

DROP TABLE IF EXISTS `academic_analytics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_analytics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `metric_name` varchar(100) DEFAULT NULL,
  `metric_value` decimal(10,2) DEFAULT NULL,
  `calculated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_analytics`
--

LOCK TABLES `academic_analytics` WRITE;
/*!40000 ALTER TABLE `academic_analytics` DISABLE KEYS */;
/*!40000 ALTER TABLE `academic_analytics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `academic_approvals`
--

DROP TABLE IF EXISTS `academic_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
INSERT INTO `academic_calendar` VALUES (1,'CAL-2025-S1-001','2025/2026','Semester 1','2025-09-01','2026-01-31','2025-12-01','2025-12-20',NULL,NULL,'Ongoing',1,'2026-06-18 21:12:21',NULL);
/*!40000 ALTER TABLE `academic_calendar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `academic_course_catalog`
--

DROP TABLE IF EXISTS `academic_course_catalog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
INSERT INTO `academic_course_catalog` VALUES (1,'NUR101','Introduction to Nursing','Nursing',0,NULL,'Active','2026-06-18 21:12:21',NULL),(2,'NUR102','Anatomy and Physiology','Nursing',0,NULL,'Active','2026-06-18 21:12:21',NULL),(3,'NUR201','Medical-Surgical Nursing','Nursing',0,NULL,'Active','2026-06-18 21:12:21',NULL),(4,'MID101','Introduction to Midwifery','Midwifery',0,NULL,'Active','2026-06-18 21:12:21',NULL),(5,'MID102','Reproductive Health','Midwifery',0,NULL,'Active','2026-06-18 21:12:21',NULL),(6,'COM101','Communication Skills','General Studies',0,NULL,'Active','2026-06-18 21:12:21',NULL),(7,'BIO101','Biology','General Studies',0,NULL,'Active','2026-06-18 21:12:21',NULL),(8,'CHEM101','Chemistry','General Studies',0,NULL,'Active','2026-06-18 21:12:21',NULL),(9,'PHY101','Physics','General Studies',0,NULL,'Active','2026-06-18 21:12:21',NULL),(10,'ENG101','English','General Studies',0,NULL,'Active','2026-06-18 21:12:21',NULL),(11,'MATH101','Mathematics','General Studies',0,NULL,'Active','2026-06-18 21:12:21',NULL),(12,'PHARM101','Pharmacology','Nursing',0,NULL,'Active','2026-06-18 21:12:21',NULL);
/*!40000 ALTER TABLE `academic_course_catalog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `academic_curriculum_development`
--

DROP TABLE IF EXISTS `academic_curriculum_development`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
INSERT INTO `academic_programs` VALUES (1,'CERT-NUR','Certificate in Nursing','Certificate',3.0,'Nursing','Active','2026-06-22 12:10:24'),(2,'CERT-MID','Certificate in Midwifery','Certificate',3.0,'Midwifery','Active','2026-06-22 12:10:24'),(3,'DIP-NUR','Diploma in Nursing','Diploma',3.0,'Nursing','Active','2026-06-22 12:10:24'),(4,'DIP-MID','Diploma in Midwifery','Diploma',3.0,'Midwifery','Active','2026-06-22 12:10:24');
/*!40000 ALTER TABLE `academic_programs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `academic_records`
--

DROP TABLE IF EXISTS `academic_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `course_name` varchar(255) DEFAULT NULL,
  `course_type` varchar(50) DEFAULT NULL,
  `credits` int(11) DEFAULT 0,
  `assessment_marks` decimal(5,2) DEFAULT 0.00,
  `exam_marks` decimal(5,2) DEFAULT 0.00,
  `total_marks` decimal(5,2) DEFAULT 0.00,
  `grade` varchar(5) DEFAULT NULL,
  `grade_points` decimal(4,2) DEFAULT 0.00,
  `gpa_contribution` decimal(4,2) DEFAULT 0.00,
  `gpa` decimal(4,2) DEFAULT 0.00,
  `lecturer` varchar(255) DEFAULT NULL,
  `lecturer_id` int(11) DEFAULT NULL,
  `assessment_type` varchar(20) DEFAULT 'Exam',
  `marks` decimal(5,2) DEFAULT 0.00,
  `entered_by` int(11) DEFAULT NULL,
  `graded_by` int(11) DEFAULT NULL,
  `entry_date` date DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_academic_year` (`academic_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_records`
--

LOCK TABLES `academic_records` WRITE;
/*!40000 ALTER TABLE `academic_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `academic_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `academic_reports`
--

DROP TABLE IF EXISTS `academic_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_type` varchar(100) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `report_data` longtext DEFAULT NULL,
  `status` varchar(20) DEFAULT 'generated',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_reports`
--

LOCK TABLES `academic_reports` WRITE;
/*!40000 ALTER TABLE `academic_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `academic_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `academic_timetable`
--

DROP TABLE IF EXISTS `academic_timetable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_timetable` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `timetable_id` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `program_code` varchar(50) DEFAULT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `day_of_week` varchar(20) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `venue` varchar(100) DEFAULT NULL,
  `lecturer_id` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `timetable_status` varchar(20) DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_timetable`
--

LOCK TABLES `academic_timetable` WRITE;
/*!40000 ALTER TABLE `academic_timetable` DISABLE KEYS */;
/*!40000 ALTER TABLE `academic_timetable` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `access_control_logs`
--

DROP TABLE IF EXISTS `access_control_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `access_control_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `person_name` varchar(200) DEFAULT NULL,
  `person_type` varchar(50) DEFAULT 'Visitor',
  `access_point` varchar(100) DEFAULT NULL,
  `access_time` datetime DEFAULT current_timestamp(),
  `access_type` varchar(20) DEFAULT 'Entry',
  `badge_number` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `access_control_logs`
--

LOCK TABLES `access_control_logs` WRITE;
/*!40000 ALTER TABLE `access_control_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `access_control_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `access_logs`
--

DROP TABLE IF EXISTS `access_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `access_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `user_type` varchar(50) DEFAULT 'staff',
  `action` varchar(200) NOT NULL,
  `module` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_access_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `access_logs`
--

LOCK TABLES `access_logs` WRITE;
/*!40000 ALTER TABLE `access_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `access_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accreditation_management`
--

DROP TABLE IF EXISTS `accreditation_management`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `accreditation_management` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `accreditation_type` varchar(100) DEFAULT NULL,
  `body_name` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `valid_from` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `documents` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`documents`)),
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accreditation_management`
--

LOCK TABLES `accreditation_management` WRITE;
/*!40000 ALTER TABLE `accreditation_management` DISABLE KEYS */;
/*!40000 ALTER TABLE `accreditation_management` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `activity_log`
--

DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `activity` varchar(255) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `created_at_ts` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_al_user_date` (`user_id`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_log`
--

LOCK TABLES `activity_log` WRITE;
/*!40000 ALTER TABLE `activity_log` DISABLE KEYS */;
INSERT INTO `activity_log` VALUES (1,0,'Contact Form Submission','Otema Reagan (reaganotema2022@gmail.com) submitted contact form: Admissions','102.86.2.161','2026-07-01 13:32:53','2026-07-01 12:32:53');
/*!40000 ALTER TABLE `activity_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `activity_logs`
--

DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) DEFAULT NULL,
  `module` varchar(100) DEFAULT NULL,
  `entity_type` varchar(100) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_logs`
--

LOCK TABLES `activity_logs` WRITE;
/*!40000 ALTER TABLE `activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admission_activity_logs`
--

DROP TABLE IF EXISTS `admission_activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admission_activity_logs`
--

LOCK TABLES `admission_activity_logs` WRITE;
/*!40000 ALTER TABLE `admission_activity_logs` DISABLE KEYS */;
INSERT INTO `admission_activity_logs` VALUES (1,24,'Create Student','students',0,'Created student: Otema Reagan (u004/cm/076)','2026-06-22 13:01:24'),(2,24,'Add Applicant','applicants',1,'Added applicant: bamuwamye Derrick (APP-202685764)','2026-06-28 18:55:45'),(3,24,'Approve','applicants',1,'Applicant approved','2026-06-29 10:48:33'),(4,24,'Receive Requirement','receiving',9,'Requirement #9 received for all applicants','2026-06-29 17:18:00'),(5,24,'Receive Requirement','receiving',8,'Requirement #8 received for all applicants','2026-06-29 17:18:01'),(6,24,'Edit Applicant','applicants',1,'Edited applicant: Bamuwamye Derrick','2026-06-29 18:09:39');
/*!40000 ALTER TABLE `admission_activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admission_notifications`
--

DROP TABLE IF EXISTS `admission_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
INSERT INTO `admission_requirements` VALUES (1,'Completed Application Form','Document',NULL,1,1,1,'2026-07-01 19:55:20'),(2,'Examination Gloves','Document',NULL,1,1,2,'2026-06-22 11:07:53'),(3,'A-Level Certificate (UACE)','Document',NULL,1,1,3,'2026-07-01 19:55:20'),(4,'Birth Certificate','Document',NULL,1,1,4,'2026-07-01 19:55:20'),(5,'Passport Photos (4)','Photo',NULL,1,1,5,'2026-07-01 19:55:20'),(6,'National ID Copy','Document',NULL,1,1,6,'2026-07-01 19:55:20'),(7,'Medical Report','Document',NULL,1,1,7,'2026-07-01 19:55:20'),(8,'Recommendation Letter','Document',NULL,1,1,8,'2026-07-01 19:55:20'),(9,'Proof of Payment (Application Fee)','Payment',NULL,1,1,9,'2026-07-01 19:55:20'),(10,'Interview Letter','Document',NULL,1,1,10,'2026-07-01 19:55:20'),(11,'Scrubbing Brush','Document',NULL,1,1,11,'2026-06-22 11:07:53'),(12,'Squeezer','Document',NULL,1,1,12,'2026-06-22 11:07:53'),(13,'Toilet Brush','Document',NULL,1,1,13,'2026-06-22 11:07:53'),(14,'JIK','Document',NULL,1,1,14,'2026-06-22 11:07:53'),(15,'Vim','Document',NULL,1,1,15,'2026-06-22 11:07:53'),(16,'Mops','Document',NULL,1,1,16,'2026-06-22 11:07:53'),(17,'Sanitizer','Document',NULL,1,1,17,'2026-06-22 11:07:53'),(18,'Liquid Soap','Document',NULL,1,1,18,'2026-06-22 11:07:53'),(19,'Face Masks','Document',NULL,1,1,19,'2026-06-22 11:07:53'),(20,'Heavy Duty Gloves','Document',NULL,1,1,20,'2026-06-22 11:07:53');
/*!40000 ALTER TABLE `admission_requirements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `advanced_reports`
--

DROP TABLE IF EXISTS `advanced_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `advanced_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_type` varchar(100) DEFAULT NULL,
  `report_name` varchar(255) DEFAULT NULL,
  `report_data` longtext DEFAULT NULL,
  `parameters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`parameters`)),
  `generated_by` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'generated',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `advanced_reports`
--

LOCK TABLES `advanced_reports` WRITE;
/*!40000 ALTER TABLE `advanced_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `advanced_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `alert_recipients`
--

DROP TABLE IF EXISTS `alert_recipients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `analytics_cache`
--

DROP TABLE IF EXISTS `analytics_cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `analytics_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cache_key` varchar(255) NOT NULL,
  `cache_value` longtext DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cache_key` (`cache_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `analytics_cache`
--

LOCK TABLES `analytics_cache` WRITE;
/*!40000 ALTER TABLE `analytics_cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `analytics_cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
INSERT INTO `announcements` VALUES (1,'Welcome to New Academic Year','We welcome all staff and students to the new academic year 2026. Let us work together for excellence.','All','High',1,1,'2026-06-19 23:58:56'),(2,'Staff Meeting Reminder','There will be a general staff meeting on Friday at 10:00 AM in the main hall.','Staff','Normal',1,1,'2026-06-19 23:58:56'),(3,'Maintenance Notice','The library will be closed for maintenance on Saturday.','All','Low',1,1,'2026-06-19 23:58:56');
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `api_keys`
--

DROP TABLE IF EXISTS `api_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key_name` varchar(100) DEFAULT NULL,
  `api_key` varchar(255) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `is_active` tinyint(1) DEFAULT 1,
  `expires_at` datetime DEFAULT NULL,
  `last_used_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_api_key` (`api_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_keys`
--

LOCK TABLES `api_keys` WRITE;
/*!40000 ALTER TABLE `api_keys` DISABLE KEYS */;
/*!40000 ALTER TABLE `api_keys` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `applicant_messages`
--

DROP TABLE IF EXISTS `applicant_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=521 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `applicant_requirement_status`
--

LOCK TABLES `applicant_requirement_status` WRITE;
/*!40000 ALTER TABLE `applicant_requirement_status` DISABLE KEYS */;
INSERT INTO `applicant_requirement_status` VALUES (2,1,2,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-28 18:55:45','2026-06-28 18:55:45'),(11,1,11,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-28 18:55:45','2026-06-28 18:55:45'),(12,1,12,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-28 18:55:45','2026-06-28 18:55:45'),(13,1,13,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-28 18:55:45','2026-06-28 18:55:45'),(14,1,14,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-28 18:55:45','2026-06-28 18:55:45'),(15,1,15,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-28 18:55:45','2026-06-28 18:55:45'),(16,1,16,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-28 18:55:45','2026-06-28 18:55:45'),(17,1,17,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-28 18:55:45','2026-06-28 18:55:45'),(18,1,18,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-28 18:55:45','2026-06-28 18:55:45'),(19,1,19,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-28 18:55:45','2026-06-28 18:55:45'),(20,1,20,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-28 18:55:45','2026-06-28 18:55:45'),(21,2,1,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(22,2,2,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(23,2,3,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(24,2,4,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(25,2,5,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(26,2,6,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(27,2,7,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(28,2,8,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(29,2,9,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(30,2,10,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(31,2,11,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(32,2,12,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(33,2,13,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(34,2,14,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(35,2,15,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(36,2,16,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(37,2,17,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(38,2,18,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(39,2,19,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(40,2,20,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(41,3,1,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(42,3,2,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(43,3,3,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(44,3,4,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(45,3,5,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(46,3,6,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(47,3,7,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(48,3,8,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(49,3,9,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(50,3,10,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(51,3,11,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(52,3,12,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(53,3,13,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(54,3,14,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(55,3,15,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(56,3,16,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(57,3,17,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(58,3,18,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(59,3,19,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(60,3,20,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(61,4,1,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(62,4,2,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(63,4,3,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(64,4,4,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(65,4,5,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(66,4,6,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(67,4,7,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(68,4,8,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(69,4,9,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(70,4,10,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(71,4,11,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(72,4,12,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(73,4,13,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(74,4,14,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(75,4,15,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(76,4,16,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(77,4,17,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(78,4,18,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(79,4,19,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(80,4,20,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(81,5,1,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(82,5,2,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(83,5,3,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(84,5,4,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(85,5,5,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(86,5,6,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(87,5,7,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(88,5,8,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(89,5,9,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(90,5,10,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(91,5,11,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(92,5,12,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(93,5,13,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(94,5,14,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(95,5,15,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(96,5,16,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(97,5,17,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(98,5,18,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(99,5,19,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(100,5,20,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(101,14,1,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(102,14,2,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(103,14,3,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(104,14,4,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(105,14,5,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(106,14,6,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(107,14,7,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(108,14,8,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(109,14,9,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(110,14,10,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(111,14,11,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(112,14,12,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(113,14,13,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(114,14,14,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(115,14,15,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(116,14,16,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(117,14,17,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(118,14,18,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(119,14,19,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(120,14,20,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(121,19,1,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(122,19,2,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(123,19,3,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(124,19,4,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(125,19,5,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(126,19,6,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(127,19,7,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(128,19,8,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(129,19,9,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(130,19,10,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(131,19,11,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(132,19,12,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(133,19,13,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(134,19,14,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(135,19,15,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(136,19,16,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(137,19,17,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(138,19,18,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(139,19,19,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(140,19,20,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(148,1,1,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(149,6,1,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(150,7,1,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(151,15,1,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(152,20,1,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(153,6,2,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(154,7,2,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(155,15,2,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(156,20,2,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(157,1,3,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(158,6,3,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(159,7,3,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(160,15,3,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(161,20,3,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(162,1,4,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(163,6,4,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(164,7,4,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(165,15,4,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(166,20,4,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(167,1,5,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(168,6,5,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(169,7,5,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(170,15,5,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(171,20,5,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(172,1,6,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(173,6,6,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(174,7,6,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(175,15,6,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(176,20,6,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(177,1,7,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(178,6,7,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(179,7,7,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(180,15,7,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(181,20,7,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(182,1,8,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(183,6,8,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(184,7,8,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(185,15,8,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(186,20,8,'Verified',1,1,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(211,1,9,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(212,6,9,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(213,7,9,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(214,15,9,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(215,20,9,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(216,1,10,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(217,6,10,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(218,7,10,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(219,15,10,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(220,20,10,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(221,6,11,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(222,7,11,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(223,15,11,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(224,20,11,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(225,6,12,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(226,7,12,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(227,15,12,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(228,20,12,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(242,8,1,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(243,9,1,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(244,16,1,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(245,8,2,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(246,9,2,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(247,16,2,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(248,8,3,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(249,9,3,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(250,16,3,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(251,8,4,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(252,9,4,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(253,16,4,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(254,8,5,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(255,9,5,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(256,16,5,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(257,8,6,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(258,9,6,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(259,16,6,'Submitted',1,NULL,NULL,NULL,'2026-07-01 19:55:20',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(273,8,7,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(274,9,7,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(275,16,7,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(276,8,8,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(277,9,8,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(278,16,8,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(279,8,9,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(280,9,9,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(281,16,9,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(282,8,10,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(283,9,10,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(284,16,10,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(285,8,11,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(286,9,11,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(287,16,11,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(288,8,12,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(289,9,12,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(290,16,12,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(291,8,13,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(292,9,13,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(293,16,13,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(294,8,14,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(295,9,14,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(296,16,14,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(297,8,15,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(298,9,15,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(299,16,15,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(300,8,16,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(301,9,16,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(302,16,16,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(303,8,17,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(304,9,17,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(305,16,17,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(306,8,18,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(307,9,18,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(308,16,18,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(309,8,19,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(310,9,19,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(311,16,19,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(312,8,20,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(313,9,20,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(314,16,20,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(336,10,1,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(337,10,2,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(338,10,3,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(339,10,4,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(340,10,5,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(341,10,6,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(342,10,7,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(343,10,8,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(344,10,9,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(345,10,10,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(346,10,11,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(347,10,12,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(348,10,13,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(349,10,14,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(350,10,15,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(351,10,16,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(352,10,17,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(353,10,18,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(354,10,19,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(355,10,20,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(356,11,1,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(357,11,2,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(358,11,3,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(359,11,4,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(360,11,5,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(361,11,6,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(362,11,7,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(363,11,8,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(364,11,9,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(365,11,10,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(366,11,11,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(367,11,12,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(368,11,13,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(369,11,14,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(370,11,15,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(371,11,16,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(372,11,17,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(373,11,18,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(374,11,19,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(375,11,20,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(376,12,1,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(377,12,2,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(378,12,3,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(379,12,4,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(380,12,5,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(381,12,6,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(382,12,7,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(383,12,8,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(384,12,9,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(385,12,10,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(386,12,11,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(387,12,12,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(388,12,13,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(389,12,14,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(390,12,15,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(391,12,16,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(392,12,17,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(393,12,18,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(394,12,19,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(395,12,20,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(396,17,1,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(397,17,2,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(398,17,3,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(399,17,4,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(400,17,5,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(401,17,6,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(402,17,7,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(403,17,8,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(404,17,9,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(405,17,10,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(406,17,11,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(407,17,12,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(408,17,13,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(409,17,14,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(410,17,15,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(411,17,16,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(412,17,17,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(413,17,18,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(414,17,19,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(415,17,20,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(416,18,1,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(417,18,2,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(418,18,3,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(419,18,4,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(420,18,5,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(421,18,6,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(422,18,7,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(423,18,8,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(424,18,9,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(425,18,10,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(426,18,11,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(427,18,12,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(428,18,13,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(429,18,14,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(430,18,15,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(431,18,16,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(432,18,17,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(433,18,18,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(434,18,19,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(435,18,20,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(463,13,1,'Rejected',1,NULL,1,'Document not clear','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(464,13,2,'Rejected',1,NULL,1,'Document not clear','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(465,13,3,'Rejected',1,NULL,1,'Document not clear','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20','2026-07-01 19:55:20'),(466,13,4,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(467,13,5,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(468,13,6,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(469,13,7,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(470,13,8,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(471,13,9,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(472,13,10,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(473,13,11,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(474,13,12,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(475,13,13,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(476,13,14,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(477,13,15,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(478,13,16,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(479,13,17,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(480,13,18,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(481,13,19,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(482,13,20,'Not Submitted',NULL,NULL,NULL,NULL,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20');
/*!40000 ALTER TABLE `applicant_requirement_status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `applicants`
--

DROP TABLE IF EXISTS `applicants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `applicants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) NOT NULL,
  `other_names` varchar(200) DEFAULT '',
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
  `rejection_reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `deleted_at` datetime DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_app_number` (`application_number`),
  KEY `idx_applicant_name` (`full_name`),
  KEY `idx_applicant_phone` (`phone`),
  KEY `idx_applicant_status` (`status`),
  KEY `idx_app_status_date` (`status`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `applicants`
--

LOCK TABLES `applicants` WRITE;
/*!40000 ALTER TABLE `applicants` DISABLE KEYS */;
INSERT INTO `applicants` VALUES (1,'bamuwamye Derrick','','1988-02-18','Male','0700451998','christ2rine@gmail.com','Iganga','Byawaka Daniel','0700130260','Brother','APP-202685764',1,'May','2026-06-28','Approved',NULL,'2026-06-28 18:55:45','2026-06-29 10:48:33',NULL,NULL),(2,'David Ssali','','2001-07-22','Male','+256701234002','david.ssali@email.com','Wakiso','Mary Ssali','+256701234102','Mother','APP-2024-002',2,'January','2024-01-15','Registered',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20',NULL,NULL),(3,'Mary Nalwoga','','2003-01-10','Female','+256701234003','mary.nalwoga@email.com','Mukono','Peter Nalwoga','+256701234103','Father','APP-2024-003',1,'January','2024-01-15','Registered',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20',NULL,NULL),(4,'James Okello','','2000-11-05','Male','+256701234004','james.okello@email.com','Jinja','Grace Okello','+256701234104','Mother','APP-2024-004',3,'January','2024-01-15','Registered',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20',NULL,NULL),(5,'Sarah Kyomugisha','','2002-06-18','Female','+256701234005','sarah.kyomugisha@email.com','Mbarara','David Kyomugisha','+256701234105','Father','APP-2024-005',1,'January','2024-01-15','Registered',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20',NULL,NULL),(6,'Aisha Nansubuga','','2001-09-25','Female','+256701234006','aisha.nansubuga@email.com','Lira','Hassan Nansubuga','+256701234106','Father','APP-2024-006',2,'May',NULL,'Approved',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20',NULL,NULL),(7,'Robert Ochieng','','2002-02-14','Male','+256701234007','robert.ochieng@email.com','Soroti','Florence Ochieng','+256701234107','Mother','APP-2024-007',1,'May',NULL,'Approved',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20',NULL,NULL),(8,'Betty Namukasa','','2003-04-30','Female','+256701234008','betty.namukasa@email.com','Entebbe','Joseph Namukasa','+256701234108','Father','APP-2024-008',3,'May',NULL,'Under Review',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20',NULL,NULL),(9,'Moses Byaruhanga','','2000-12-20','Male','+256701234009','moses.byaruhanga@email.com','Kabale','Agnes Byaruhanga','+256701234109','Mother','APP-2024-009',1,'May',NULL,'Under Review',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20',NULL,NULL),(10,'Esther Auma','','2002-08-12','Female','+256701234010','esther.auma@email.com','Gulu','Paul Auma','+256701234110','Father','APP-2024-010',2,'May',NULL,'New Applicant',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20',NULL,NULL),(11,'Samuel Mugisha','','2001-05-08','Male','+256701234011','samuel.mugisha@email.com','Kasese','Ruth Mugisha','+256701234111','Mother','APP-2024-011',1,'August',NULL,'New Applicant',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20',NULL,NULL),(12,'Priscilla Ojok','','2003-07-03','Female','+256701234012','priscilla.ojok@email.com','Arua','Charles Ojok','+256701234112','Father','APP-2024-012',3,'August',NULL,'New Applicant',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20',NULL,NULL),(13,'Isaac Tumwine','','2002-10-16','Male','+256701234013','isaac.tumwine@email.com','Fort Portal','Juliet Tumwine','+256701234113','Mother','APP-2024-013',1,'August',NULL,'Rejected',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20',NULL,NULL),(14,'Hannah Apio','','2001-01-28','Female','+256701234014','hannah.apio@email.com','Lira','Steven Apio','+256701234114','Father','APP-2024-014',2,'January','2024-01-15','Registered',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20',NULL,NULL),(15,'Daniel Kizza','','2002-04-11','Male','+256701234015','daniel.kizza@email.com','Mbarara','Catherine Kizza','+256701234115','Mother','APP-2024-015',1,'May',NULL,'Approved',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20',NULL,NULL),(16,'Joyce Atim','','2003-09-07','Female','+256701234016','joyce.atim@email.com','Soroti','George Atim','+256701234116','Father','APP-2024-016',3,'May',NULL,'Under Review',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20',NULL,NULL),(17,'Patrick Opio','','2000-03-19','Male','+256701234017','patrick.opio@email.com','Gulu','Mary Opio','+256701234117','Mother','APP-2024-017',1,'August',NULL,'New Applicant',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20',NULL,NULL),(18,'Catherine Akello','','2002-11-22','Female','+256701234018','catherine.akello@email.com','Jinja','James Akello','+256701234118','Father','APP-2024-018',2,'August',NULL,'New Applicant',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20',NULL,NULL),(19,'Fred Wasswa','','2001-06-14','Male','+256701234019','fred.wasswa@email.com','Kampala','Nancy Wasswa','+256701234119','Mother','APP-2024-019',1,'January','2024-01-15','Registered',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20',NULL,NULL),(20,'Gladys Nabirye','','2003-02-25','Female','+256701234020','gladys.nabirye@email.com','Mukono','Henry Nabirye','+256701234120','Father','APP-2024-020',3,'May',NULL,'Approved',NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20',NULL,NULL);
/*!40000 ALTER TABLE `applicants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `application_reviews`
--

DROP TABLE IF EXISTS `application_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `application_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) DEFAULT NULL,
  `reviewer_id` int(11) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `recommendation` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `application_reviews`
--

LOCK TABLES `application_reviews` WRITE;
/*!40000 ALTER TABLE `application_reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `application_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `applications`
--

DROP TABLE IF EXISTS `applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `applicant_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `program` varchar(200) DEFAULT NULL,
  `intake` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `application_type` enum('Admission','Transfer','Readmission','Scholarship','Other') DEFAULT 'Admission',
  `status` enum('Pending','Reviewed','Accepted','Rejected','Waitlisted','Enrolled') DEFAULT 'Pending',
  `submitted_at` timestamp NULL DEFAULT current_timestamp(),
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_app_student` (`student_id`),
  KEY `idx_app_status` (`status`),
  KEY `idx_app_program` (`program`),
  KEY `idx_app_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `applications`
--

LOCK TABLES `applications` WRITE;
/*!40000 ALTER TABLE `applications` DISABLE KEYS */;
/*!40000 ALTER TABLE `applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `appraisal_periods`
--

DROP TABLE IF EXISTS `appraisal_periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `appraisal_periods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `period_name` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appraisal_periods`
--

LOCK TABLES `appraisal_periods` WRITE;
/*!40000 ALTER TABLE `appraisal_periods` DISABLE KEYS */;
/*!40000 ALTER TABLE `appraisal_periods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `appraisal_ratings`
--

DROP TABLE IF EXISTS `appraisal_ratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `appraisal_ratings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `appraisal_id` int(11) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `criteria` varchar(255) DEFAULT NULL,
  `rating` decimal(3,2) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `rated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `appraisal_ratings`
--

LOCK TABLES `appraisal_ratings` WRITE;
/*!40000 ALTER TABLE `appraisal_ratings` DISABLE KEYS */;
/*!40000 ALTER TABLE `appraisal_ratings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `appraisals`
--

DROP TABLE IF EXISTS `appraisals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approval_actions`
--

LOCK TABLES `approval_actions` WRITE;
/*!40000 ALTER TABLE `approval_actions` DISABLE KEYS */;
INSERT INTO `approval_actions` VALUES (1,3,2,1,'reject','yes',NULL,'Rejected',2,'2026-06-24 01:32:00'),(2,1,160,2,'create','Request created: Laboratory Equipment Restock',NULL,NULL,NULL,'2026-06-19 22:47:50'),(3,2,160,3,'create','Request created: Office Stationery Order',NULL,NULL,NULL,'2026-06-19 19:47:50'),(4,3,160,4,'create','Request created: Medical Consumables',NULL,NULL,NULL,'2026-06-19 00:47:50'),(5,4,161,5,'create','Request created: New Student: Akello Grace',NULL,NULL,NULL,'2026-06-19 21:47:50'),(6,5,161,5,'create','Request created: New Student: Bwire John',NULL,NULL,NULL,'2026-06-19 00:47:50'),(7,6,160,2,'create','Request created: End of Year Examination Schedule',NULL,NULL,NULL,'2026-06-19 18:47:50');
/*!40000 ALTER TABLE `approval_actions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `approval_requests`
--

DROP TABLE IF EXISTS `approval_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `approval_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `workflow_id` int(10) unsigned NOT NULL,
  `request_number` varchar(60) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `priority` varchar(20) DEFAULT 'Medium',
  `priority_order` smallint(5) unsigned NOT NULL DEFAULT 2,
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
INSERT INTO `approval_requests` VALUES (1,122,'REQ-20260620-A73F2B','Laboratory Equipment Restock','Request to restock essential laboratory equipment including microscopes and slides for Nursing dept.','High',2,2,'Mary Nalwoga','Head of Nursing',160,1,'Active','store_requests',1,NULL,NULL,NULL,NULL,'2026-06-19 22:47:50','2026-07-01 04:35:13'),(2,122,'REQ-20260620-B84C3D','Office Stationery Order','Monthly stationery supplies for administrative offices - paper, pens, folders, ink cartridges.','Medium',3,3,'James Okello','School Secretary',160,1,'Active','store_requests',2,NULL,NULL,NULL,NULL,'2026-06-19 19:47:50','2026-07-01 07:08:13'),(3,122,'REQ-20260619-C95D4E','Medical Consumables','Urgent restock of gloves, masks, sanitizers and first aid supplies for the sickbay.','Urgent',1,4,'Sarah Kyomugisha','Matron',160,1,'Rejected','store_requests',3,NULL,'yes',NULL,NULL,'2026-06-19 00:47:50','2026-07-01 07:08:13'),(4,123,'REQ-20260620-D06E5F','New Student: Akello Grace','Registration application for Diploma Nursing program. Submitted by Registrar.','Normal',4,5,'Peter Okoth','Academic Registrar',161,1,'Active','pending_students',1,NULL,NULL,NULL,NULL,'2026-06-19 21:47:50','2026-07-01 07:08:13'),(5,123,'REQ-20260619-E17F6G','New Student: Bwire John','Registration application for Certificate Midwifery program. All documents verified.','Normal',4,5,'Peter Okoth','Academic Registrar',161,1,'Active','pending_students',2,NULL,NULL,NULL,NULL,'2026-06-19 00:47:50','2026-07-01 07:08:13'),(6,122,'REQ-20260620-F28G7H','End of Year Examination Schedule','Proposed examination timetable for the June 2026 semester. Requires DG sign-off.','Medium',3,2,'Mary Nalwoga','Head of Nursing',160,1,'Active',NULL,NULL,NULL,NULL,NULL,NULL,'2026-06-19 18:47:50','2026-07-01 07:08:13');
/*!40000 ALTER TABLE `approval_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `approval_stages`
--

DROP TABLE IF EXISTS `approval_stages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=198 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approval_stages`
--

LOCK TABLES `approval_stages` WRITE;
/*!40000 ALTER TABLE `approval_stages` DISABLE KEYS */;
INSERT INTO `approval_stages` VALUES (188,125,'Director ICT Review',1,NULL,'Director ICT',0,'2026-06-29 13:41:38'),(189,125,'Director General Final Approval',2,NULL,'Director General',1,'2026-06-29 13:41:38'),(190,122,'Director General Approval',1,NULL,'Director General',1,'2026-06-29 13:41:38'),(191,123,'Director General Approval',1,NULL,'Director General',1,'2026-06-29 13:41:39'),(192,124,'Director General Approval',1,NULL,'Director General',1,'2026-06-29 13:41:39'),(193,126,'Director General Approval',1,NULL,'Director General',1,'2026-06-29 13:41:39'),(194,127,'Director General Approval',1,NULL,'Director General',1,'2026-06-29 13:41:39'),(195,128,'Director General Approval',1,NULL,'Director General',1,'2026-06-29 13:41:39'),(196,129,'Director General Approval',1,NULL,'Director General',1,'2026-06-29 13:41:39'),(197,130,'Director General Approval',1,NULL,'Director General',1,'2026-06-29 13:41:39');
/*!40000 ALTER TABLE `approval_stages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `approval_workflows`
--

DROP TABLE IF EXISTS `approval_workflows`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `approval_workflows` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `workflow_name` varchar(120) NOT NULL,
  `category` varchar(60) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_workflow_name` (`workflow_name`)
) ENGINE=InnoDB AUTO_INCREMENT=163 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `approval_workflows`
--

LOCK TABLES `approval_workflows` WRITE;
/*!40000 ALTER TABLE `approval_workflows` DISABLE KEYS */;
INSERT INTO `approval_workflows` VALUES (122,'General Department Request','General Administration','Standard approval workflow for general administrative requests requiring Director General sign-off',1,'2026-06-27 00:17:17'),(123,'HR Request','Human Resources','HR-related requests requiring Director General approval',1,'2026-06-27 00:17:17'),(124,'Finance Request','Finance','Financial requests and budget approvals requiring Director General sign-off',1,'2026-06-27 00:17:17'),(125,'ICT Request','ICT','ICT department requests requiring departmental review and Director General approval',1,'2026-06-27 00:17:17'),(126,'Academic Request','Academic','Academic affairs requests requiring Director General approval',1,'2026-06-27 00:17:17'),(127,'Admissions Request','Admissions','Admissions-related requests requiring Director General approval',1,'2026-06-27 00:17:17'),(128,'Library Request','Library','Library resource and service requests requiring Director General approval',1,'2026-06-27 00:17:17'),(129,'Store Requisition','Store & Assets','Store and asset requisitions requiring Director General approval',1,'2026-06-27 00:17:17'),(130,'Student Registration','Academic','Student registration requests requiring Director General approval',1,'2026-06-27 00:17:17'),(160,'Transport Trip Approval','transport','DG approval for new transport trips',1,'2026-07-01 06:22:53'),(161,'Vehicle Purchase Approval','transport','DG approval for new vehicle acquisitions',1,'2026-07-01 06:22:53'),(162,'Route Change Approval','transport','DG approval for route modifications',1,'2026-07-01 06:22:53');
/*!40000 ALTER TABLE `approval_workflows` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assessment_scores`
--

DROP TABLE IF EXISTS `assessment_scores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `assessment_scores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assessment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL,
  `graded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_score_assessment` (`assessment_id`),
  KEY `idx_score_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assessment_scores`
--

LOCK TABLES `assessment_scores` WRITE;
/*!40000 ALTER TABLE `assessment_scores` DISABLE KEYS */;
/*!40000 ALTER TABLE `assessment_scores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assessments`
--

DROP TABLE IF EXISTS `assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `assessments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `assessment_type` varchar(100) DEFAULT NULL,
  `total_marks` int(11) DEFAULT 100,
  `due_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_assessments_course` (`course_id`),
  KEY `fk_assessments_creator` (`created_by`),
  CONSTRAINT `fk_assessments_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_assessments_creator` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assessments`
--

LOCK TABLES `assessments` WRITE;
/*!40000 ALTER TABLE `assessments` DISABLE KEYS */;
/*!40000 ALTER TABLE `assessments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asset_categories`
--

DROP TABLE IF EXISTS `asset_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_categories`
--

LOCK TABLES `asset_categories` WRITE;
/*!40000 ALTER TABLE `asset_categories` DISABLE KEYS */;
INSERT INTO `asset_categories` VALUES (1,'Furniture','Desks, chairs, tables, cabinets','2026-07-03 05:16:47'),(2,'Electronics','Computers, printers, projectors','2026-07-03 05:16:47'),(3,'Medical Equipment','Beds, monitors, diagnostic tools','2026-07-03 05:16:47'),(4,'Vehicles','School vehicles, ambulances','2026-07-03 05:16:47'),(5,'Buildings','School buildings and structures','2026-07-03 05:16:47'),(6,'Library','Books and library equipment','2026-07-03 05:16:47');
/*!40000 ALTER TABLE `asset_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asset_depreciation`
--

DROP TABLE IF EXISTS `asset_depreciation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_depreciation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) DEFAULT NULL,
  `depreciation_method` varchar(50) DEFAULT NULL,
  `annual_rate` decimal(5,2) DEFAULT NULL,
  `accumulated_depreciation` decimal(15,2) DEFAULT 0.00,
  `book_value` decimal(15,2) DEFAULT NULL,
  `depreciation_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `asset_depreciation`
--

LOCK TABLES `asset_depreciation` WRITE;
/*!40000 ALTER TABLE `asset_depreciation` DISABLE KEYS */;
/*!40000 ALTER TABLE `asset_depreciation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assets`
--

DROP TABLE IF EXISTS `assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_name` varchar(255) NOT NULL,
  `asset_code` varchar(100) DEFAULT NULL,
  `asset_category_id` int(11) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `purchase_cost` decimal(15,2) DEFAULT 0.00,
  `value` decimal(15,2) DEFAULT 0.00,
  `purchase_date` date DEFAULT NULL,
  `useful_life_years` int(11) DEFAULT 5,
  `salvage_value` decimal(15,2) DEFAULT 0.00,
  `depreciation_method` varchar(50) DEFAULT 'Straight Line',
  `depreciation_value` decimal(15,2) DEFAULT 0.00,
  `status` enum('new','available','in_use','under_maintenance','disposed') DEFAULT 'new',
  `location` varchar(200) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assets`
--

LOCK TABLES `assets` WRITE;
/*!40000 ALTER TABLE `assets` DISABLE KEYS */;
INSERT INTO `assets` VALUES (1,'Dell Desktop Computers (x20)','AST-001',2,NULL,30000000.00,0.00,'2024-01-15',5,3000000.00,'Straight Line',0.00,'available',NULL,NULL,NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(2,'HP LaserJet Printers (x5)','AST-002',2,NULL,7500000.00,0.00,'2024-01-15',3,750000.00,'Straight Line',0.00,'in_use',NULL,NULL,NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(3,'Hospital Beds (x30)','AST-003',3,NULL,45000000.00,0.00,'2024-02-01',10,4500000.00,'Straight Line',0.00,'new',NULL,NULL,NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(4,'School Bus - Toyota Coaster','AST-004',4,NULL,180000000.00,0.00,'2023-06-01',15,18000000.00,'Straight Line',0.00,'in_use',NULL,NULL,NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(5,'Library Books Collection','AST-005',6,NULL,15000000.00,0.00,'2024-03-01',5,1500000.00,'Straight Line',0.00,'available',NULL,NULL,NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(6,'Projectors (x10)','AST-006',2,NULL,12000000.00,0.00,'2024-04-01',5,1200000.00,'Straight Line',0.00,'in_use',NULL,NULL,NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47');
/*!40000 ALTER TABLE `assets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `attendance_records`
--

DROP TABLE IF EXISTS `attendance_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `status` enum('Present','Absent','Late','Excused') NOT NULL DEFAULT 'Absent',
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `marked_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_session_student` (`session_id`,`student_id`),
  KEY `idx_student` (`student_id`),
  KEY `idx_session` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_records`
--

LOCK TABLES `attendance_records` WRITE;
/*!40000 ALTER TABLE `attendance_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `attendance_status`
--

DROP TABLE IF EXISTS `attendance_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `attendance_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `check_in_time` time DEFAULT NULL,
  `check_out_time` time DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Present',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_att_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_status`
--

LOCK TABLES `attendance_status` WRITE;
/*!40000 ALTER TABLE `attendance_status` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance_status` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `audit_trail`
--

DROP TABLE IF EXISTS `audit_trail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_name` varchar(200) NOT NULL,
  `bank_name` varchar(200) DEFAULT NULL,
  `account_number` varchar(100) NOT NULL,
  `current_balance` decimal(15,2) DEFAULT 0.00,
  `balance` decimal(15,2) DEFAULT 0.00,
  `status` enum('active','inactive','closed') DEFAULT 'active',
  `is_active` tinyint(4) DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_accounts`
--

LOCK TABLES `bank_accounts` WRITE;
/*!40000 ALTER TABLE `bank_accounts` DISABLE KEYS */;
INSERT INTO `bank_accounts` VALUES (1,'School Operations Account','Stanbic Bank','9030001234567',5000000.00,5000000.00,'active',1,NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(2,'School Tuition Account','Centenary Bank','3200123456',15000000.00,15000000.00,'active',1,NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(3,'School Development Fund','DFCU Bank','0100123456789',3000000.00,3000000.00,'active',1,NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47');
/*!40000 ALTER TABLE `bank_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_reconciliation`
--

DROP TABLE IF EXISTS `bank_reconciliation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_reconciliation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reconciliation_date` date NOT NULL,
  `bank_balance` decimal(15,2) DEFAULT NULL,
  `book_balance` decimal(15,2) DEFAULT NULL,
  `difference` decimal(15,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'unreconciled',
  `reconciled_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_reconciliation`
--

LOCK TABLES `bank_reconciliation` WRITE;
/*!40000 ALTER TABLE `bank_reconciliation` DISABLE KEYS */;
/*!40000 ALTER TABLE `bank_reconciliation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_reconciliations`
--

DROP TABLE IF EXISTS `bank_reconciliations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_reconciliations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `reconciliation_date` date NOT NULL,
  `bank_balance` decimal(15,2) DEFAULT NULL,
  `book_balance` decimal(15,2) DEFAULT NULL,
  `difference` decimal(15,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'unreconciled',
  `reconciled_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bank_reconciliations`
--

LOCK TABLES `bank_reconciliations` WRITE;
/*!40000 ALTER TABLE `bank_reconciliations` DISABLE KEYS */;
/*!40000 ALTER TABLE `bank_reconciliations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `budget_lines`
--

DROP TABLE IF EXISTS `budget_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `budget_lines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `budget_id` int(11) DEFAULT NULL,
  `line_item` varchar(255) DEFAULT NULL,
  `allocated_amount` decimal(15,2) DEFAULT 0.00,
  `spent_amount` decimal(15,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `budget_lines`
--

LOCK TABLES `budget_lines` WRITE;
/*!40000 ALTER TABLE `budget_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `budget_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_allowances`
--

DROP TABLE IF EXISTS `bursar_allowances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bursar_allowances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) DEFAULT NULL,
  `allowance_type` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `effective_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_allowances`
--

LOCK TABLES `bursar_allowances` WRITE;
/*!40000 ALTER TABLE `bursar_allowances` DISABLE KEYS */;
/*!40000 ALTER TABLE `bursar_allowances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_assets`
--

DROP TABLE IF EXISTS `bursar_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bursar_assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_name` varchar(255) DEFAULT NULL,
  `asset_code` varchar(50) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_cost` decimal(15,2) DEFAULT NULL,
  `current_value` decimal(15,2) DEFAULT NULL,
  `condition_status` varchar(50) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_assets`
--

LOCK TABLES `bursar_assets` WRITE;
/*!40000 ALTER TABLE `bursar_assets` DISABLE KEYS */;
/*!40000 ALTER TABLE `bursar_assets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_bank_reconciliation`
--

DROP TABLE IF EXISTS `bursar_bank_reconciliation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `bursar_budget_items`
--

DROP TABLE IF EXISTS `bursar_budget_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bursar_budget_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `budget_id` int(11) DEFAULT NULL,
  `item_name` varchar(255) DEFAULT NULL,
  `allocated_amount` decimal(15,2) DEFAULT NULL,
  `spent_amount` decimal(15,2) DEFAULT 0.00,
  `category` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_budget_items`
--

LOCK TABLES `bursar_budget_items` WRITE;
/*!40000 ALTER TABLE `bursar_budget_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `bursar_budget_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_cashbook`
--

DROP TABLE IF EXISTS `bursar_cashbook`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bursar_cashbook` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_date` date DEFAULT NULL,
  `date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `debit_amount` decimal(15,2) DEFAULT 0.00,
  `cash_in` decimal(15,2) DEFAULT 0.00,
  `amount` decimal(15,2) DEFAULT 0.00,
  `credit_amount` decimal(15,2) DEFAULT 0.00,
  `cash_out` decimal(15,2) DEFAULT 0.00,
  `running_balance` decimal(15,2) DEFAULT 0.00,
  `balance` decimal(15,2) DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_cashbook`
--

LOCK TABLES `bursar_cashbook` WRITE;
/*!40000 ALTER TABLE `bursar_cashbook` DISABLE KEYS */;
INSERT INTO `bursar_cashbook` VALUES (1,'2026-07-03',NULL,'Opening Balance','OP-001',10000000.00,0.00,0.00,0.00,0.00,10000000.00,0.00,NULL,'2026-07-03 05:16:47'),(2,'2026-07-03',NULL,'Tuition Fee Collection - Student A','RCPT-20250101-001',2500000.00,0.00,0.00,0.00,0.00,12500000.00,0.00,NULL,'2026-07-03 05:16:47'),(3,'2026-07-02',NULL,'Bank Deposit','DEP-001',0.00,0.00,0.00,5000000.00,0.00,7500000.00,0.00,NULL,'2026-07-03 05:16:47'),(4,'2026-07-01',NULL,'Office Supplies Purchase','PO-001',0.00,0.00,0.00,2500000.00,0.00,5000000.00,0.00,NULL,'2026-07-03 05:16:47'),(5,'2026-06-30',NULL,'Electricity Bill Payment','UTIL-001',0.00,0.00,0.00,1800000.00,0.00,3200000.00,0.00,NULL,'2026-07-03 05:16:47');
/*!40000 ALTER TABLE `bursar_cashbook` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_chart_of_accounts`
--

DROP TABLE IF EXISTS `bursar_chart_of_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bursar_chart_of_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_code` varchar(50) NOT NULL,
  `account_name` varchar(200) NOT NULL,
  `account_type` enum('asset','liability','equity','income','expense') DEFAULT 'asset',
  `balance` decimal(15,2) DEFAULT 0.00,
  `status` enum('active','inactive','closed') DEFAULT 'active',
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_chart_of_accounts`
--

LOCK TABLES `bursar_chart_of_accounts` WRITE;
/*!40000 ALTER TABLE `bursar_chart_of_accounts` DISABLE KEYS */;
INSERT INTO `bursar_chart_of_accounts` VALUES (1,'1000','Cash','asset',0.00,'active',NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(2,'1100','Bank Accounts','asset',0.00,'active',NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(3,'1200','Accounts Receivable','asset',0.00,'active',NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(4,'2000','Accounts Payable','liability',0.00,'active',NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(5,'3000','Retained Earnings','equity',0.00,'active',NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(6,'4000','Tuition Fees','income',0.00,'active',NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(7,'4100','Donations','income',0.00,'active',NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(8,'5000','Salaries','expense',0.00,'active',NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(9,'5100','Utilities','expense',0.00,'active',NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(10,'5200','Supplies','expense',0.00,'active',NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47');
/*!40000 ALTER TABLE `bursar_chart_of_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_daily_collections`
--

DROP TABLE IF EXISTS `bursar_daily_collections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bursar_daily_collections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `collection_date` date NOT NULL,
  `total_collected` decimal(15,2) DEFAULT 0.00,
  `collection_count` int(11) DEFAULT 0,
  `payment_method` varchar(50) DEFAULT NULL,
  `collected_by` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'recorded',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_daily_collections`
--

LOCK TABLES `bursar_daily_collections` WRITE;
/*!40000 ALTER TABLE `bursar_daily_collections` DISABLE KEYS */;
/*!40000 ALTER TABLE `bursar_daily_collections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_deductions`
--

DROP TABLE IF EXISTS `bursar_deductions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bursar_deductions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) DEFAULT NULL,
  `deduction_type` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_deductions`
--

LOCK TABLES `bursar_deductions` WRITE;
/*!40000 ALTER TABLE `bursar_deductions` DISABLE KEYS */;
/*!40000 ALTER TABLE `bursar_deductions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_discounts`
--

DROP TABLE IF EXISTS `bursar_discounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `bursar_expenses`
--

DROP TABLE IF EXISTS `bursar_expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bursar_expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `expense_number` varchar(50) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `expense_date` date DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_expenses`
--

LOCK TABLES `bursar_expenses` WRITE;
/*!40000 ALTER TABLE `bursar_expenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `bursar_expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_fee_items`
--

DROP TABLE IF EXISTS `bursar_fee_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bursar_fee_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fee_name` varchar(255) DEFAULT NULL,
  `fee_code` varchar(50) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `fee_type` varchar(100) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_fee_items`
--

LOCK TABLES `bursar_fee_items` WRITE;
/*!40000 ALTER TABLE `bursar_fee_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `bursar_fee_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_fee_reminders`
--

DROP TABLE IF EXISTS `bursar_fee_reminders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bursar_fee_reminders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `fee_account_id` int(11) DEFAULT NULL,
  `reminder_type` varchar(50) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_fee_reminders`
--

LOCK TABLES `bursar_fee_reminders` WRITE;
/*!40000 ALTER TABLE `bursar_fee_reminders` DISABLE KEYS */;
/*!40000 ALTER TABLE `bursar_fee_reminders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_general_ledger`
--

DROP TABLE IF EXISTS `bursar_general_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bursar_general_ledger` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_date` date DEFAULT NULL,
  `date` date DEFAULT NULL,
  `account_name` varchar(200) DEFAULT NULL,
  `account_code` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `debit_amount` decimal(15,2) DEFAULT 0.00,
  `debit` decimal(15,2) DEFAULT 0.00,
  `credit_amount` decimal(15,2) DEFAULT 0.00,
  `credit` decimal(15,2) DEFAULT 0.00,
  `reference` varchar(100) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_general_ledger`
--

LOCK TABLES `bursar_general_ledger` WRITE;
/*!40000 ALTER TABLE `bursar_general_ledger` DISABLE KEYS */;
INSERT INTO `bursar_general_ledger` VALUES (1,'2026-07-03',NULL,'Cash',NULL,'Opening balance',10000000.00,0.00,0.00,0.00,NULL,NULL,'2026-07-03 05:16:47'),(2,'2026-07-03',NULL,'Tuition Fees',NULL,'Fee collection - January',0.00,0.00,5000000.00,0.00,NULL,NULL,'2026-07-03 05:16:47'),(3,'2026-07-01',NULL,'Bank Accounts',NULL,'Bank deposit',5000000.00,0.00,0.00,0.00,NULL,NULL,'2026-07-03 05:16:47'),(4,'2026-06-30',NULL,'Salaries',NULL,'Staff salary payment',0.00,0.00,8000000.00,0.00,NULL,NULL,'2026-07-03 05:16:47'),(5,'2026-06-28',NULL,'Utilities',NULL,'Electricity bill',0.00,0.00,1800000.00,0.00,NULL,NULL,'2026-07-03 05:16:47');
/*!40000 ALTER TABLE `bursar_general_ledger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_invoices`
--

DROP TABLE IF EXISTS `bursar_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bursar_invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `paid_amount` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_invoices`
--

LOCK TABLES `bursar_invoices` WRITE;
/*!40000 ALTER TABLE `bursar_invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `bursar_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_payment_verification`
--

DROP TABLE IF EXISTS `bursar_payment_verification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `bursar_payments`
--

DROP TABLE IF EXISTS `bursar_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bursar_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_number` varchar(50) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `invoice_id` int(11) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'completed',
  `processed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_payments`
--

LOCK TABLES `bursar_payments` WRITE;
/*!40000 ALTER TABLE `bursar_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `bursar_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_payroll`
--

DROP TABLE IF EXISTS `bursar_payroll`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bursar_payroll` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payroll_period` varchar(50) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `basic_salary` decimal(15,2) DEFAULT NULL,
  `allowances` decimal(15,2) DEFAULT 0.00,
  `deductions` decimal(15,2) DEFAULT 0.00,
  `net_salary` decimal(15,2) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `processed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_payroll`
--

LOCK TABLES `bursar_payroll` WRITE;
/*!40000 ALTER TABLE `bursar_payroll` DISABLE KEYS */;
/*!40000 ALTER TABLE `bursar_payroll` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_penalties`
--

DROP TABLE IF EXISTS `bursar_penalties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bursar_penalties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `penalty_type` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_penalties`
--

LOCK TABLES `bursar_penalties` WRITE;
/*!40000 ALTER TABLE `bursar_penalties` DISABLE KEYS */;
/*!40000 ALTER TABLE `bursar_penalties` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_penalty_config`
--

DROP TABLE IF EXISTS `bursar_penalty_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `bursar_receipts`
--

DROP TABLE IF EXISTS `bursar_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bursar_receipts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `receipt_number` varchar(50) DEFAULT NULL,
  `payment_id` int(11) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `issued_by` int(11) DEFAULT NULL,
  `issued_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_receipts`
--

LOCK TABLES `bursar_receipts` WRITE;
/*!40000 ALTER TABLE `bursar_receipts` DISABLE KEYS */;
/*!40000 ALTER TABLE `bursar_receipts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_requisition_reviews`
--

DROP TABLE IF EXISTS `bursar_requisition_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `bursar_settings`
--

DROP TABLE IF EXISTS `bursar_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bursar_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_settings`
--

LOCK TABLES `bursar_settings` WRITE;
/*!40000 ALTER TABLE `bursar_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `bursar_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_sponsorships`
--

DROP TABLE IF EXISTS `bursar_sponsorships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `bursar_tax_records`
--

DROP TABLE IF EXISTS `bursar_tax_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `bursar_tax_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) DEFAULT NULL,
  `tax_type` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `tax_period` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bursar_tax_records`
--

LOCK TABLES `bursar_tax_records` WRITE;
/*!40000 ALTER TABLE `bursar_tax_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `bursar_tax_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bursar_vat_reports`
--

DROP TABLE IF EXISTS `bursar_vat_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `cache_data`
--

DROP TABLE IF EXISTS `cache_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cache_key` varchar(255) NOT NULL,
  `cache_value` longtext DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cache_key` (`cache_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_data`
--

LOCK TABLES `cache_data` WRITE;
/*!40000 ALTER TABLE `cache_data` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_data` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_management`
--

DROP TABLE IF EXISTS `cache_management`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `calendar_events`
--

DROP TABLE IF EXISTS `calendar_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `calendar_events` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `event_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `event_type` enum('meeting','deadline','holiday','exam','orientation','training','ceremony','other') NOT NULL DEFAULT 'meeting',
  `audience` enum('all','staff','students','specific') NOT NULL DEFAULT 'all',
  `audience_role` varchar(100) DEFAULT NULL,
  `audience_staff_ids` text DEFAULT NULL,
  `is_recurring` tinyint(1) NOT NULL DEFAULT 0,
  `recurrence_pattern` varchar(50) DEFAULT NULL,
  `created_by` int(11) unsigned NOT NULL,
  `color` varchar(7) DEFAULT '#3b82f6',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_event_date` (`event_date`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_created_by` (`created_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `calendar_events`
--

LOCK TABLES `calendar_events` WRITE;
/*!40000 ALTER TABLE `calendar_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `calendar_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cash_book`
--

DROP TABLE IF EXISTS `cash_book`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cash_book` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_date` date DEFAULT NULL,
  `date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `debit_amount` decimal(15,2) DEFAULT 0.00,
  `cash_in` decimal(15,2) DEFAULT 0.00,
  `amount` decimal(15,2) DEFAULT 0.00,
  `credit_amount` decimal(15,2) DEFAULT 0.00,
  `cash_out` decimal(15,2) DEFAULT 0.00,
  `running_balance` decimal(15,2) DEFAULT 0.00,
  `balance` decimal(15,2) DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cash_book`
--

LOCK TABLES `cash_book` WRITE;
/*!40000 ALTER TABLE `cash_book` DISABLE KEYS */;
/*!40000 ALTER TABLE `cash_book` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cashbook`
--

DROP TABLE IF EXISTS `cashbook`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cashbook` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_date` date DEFAULT NULL,
  `date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `debit_amount` decimal(15,2) DEFAULT 0.00,
  `cash_in` decimal(15,2) DEFAULT 0.00,
  `amount` decimal(15,2) DEFAULT 0.00,
  `credit_amount` decimal(15,2) DEFAULT 0.00,
  `cash_out` decimal(15,2) DEFAULT 0.00,
  `running_balance` decimal(15,2) DEFAULT 0.00,
  `balance` decimal(15,2) DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cashbook`
--

LOCK TABLES `cashbook` WRITE;
/*!40000 ALTER TABLE `cashbook` DISABLE KEYS */;
INSERT INTO `cashbook` VALUES (1,'2026-07-03',NULL,'Opening Balance','OP-001',10000000.00,0.00,0.00,0.00,0.00,10000000.00,0.00,NULL,'2026-07-03 05:16:47'),(2,'2026-07-03',NULL,'Tuition Fee Collection - Student A','RCPT-20250101-001',2500000.00,0.00,0.00,0.00,0.00,12500000.00,0.00,NULL,'2026-07-03 05:16:47'),(3,'2026-07-02',NULL,'Bank Deposit','DEP-001',0.00,0.00,0.00,5000000.00,0.00,7500000.00,0.00,NULL,'2026-07-03 05:16:47'),(4,'2026-07-01',NULL,'Office Supplies Purchase','PO-001',0.00,0.00,0.00,2500000.00,0.00,5000000.00,0.00,NULL,'2026-07-03 05:16:47'),(5,'2026-06-30',NULL,'Electricity Bill Payment','UTIL-001',0.00,0.00,0.00,1800000.00,0.00,3200000.00,0.00,NULL,'2026-07-03 05:16:47');
/*!40000 ALTER TABLE `cashbook` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `certificate_templates`
--

DROP TABLE IF EXISTS `certificate_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `chart_of_accounts`
--

DROP TABLE IF EXISTS `chart_of_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `chart_of_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_code` varchar(50) NOT NULL,
  `account_name` varchar(200) NOT NULL,
  `account_type` enum('asset','liability','equity','income','expense') DEFAULT 'asset',
  `balance` decimal(15,2) DEFAULT 0.00,
  `status` enum('active','inactive','closed') DEFAULT 'active',
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `chart_of_accounts`
--

LOCK TABLES `chart_of_accounts` WRITE;
/*!40000 ALTER TABLE `chart_of_accounts` DISABLE KEYS */;
INSERT INTO `chart_of_accounts` VALUES (1,'1000','Cash','asset',0.00,'active',NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(2,'1100','Bank Accounts','asset',0.00,'active',NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(3,'1200','Accounts Receivable','asset',0.00,'active',NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(4,'2000','Accounts Payable','liability',0.00,'active',NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(5,'3000','Retained Earnings','equity',0.00,'active',NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(6,'4000','Tuition Fees','income',0.00,'active',NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(7,'4100','Donations','income',0.00,'active',NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(8,'5000','Salaries','expense',0.00,'active',NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(9,'5100','Utilities','expense',0.00,'active',NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(10,'5200','Supplies','expense',0.00,'active',NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47');
/*!40000 ALTER TABLE `chart_of_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `chemical_inventory`
--

DROP TABLE IF EXISTS `chemical_inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `class_sessions`
--

DROP TABLE IF EXISTS `class_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `class_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_id` int(10) unsigned NOT NULL,
  `lecturer_id` int(11) NOT NULL,
  `session_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `room` varchar(50) DEFAULT NULL,
  `session_type` enum('Lecture','Tutorial','Practical','Clinical','Exam') DEFAULT 'Lecture',
  `academic_year` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `total_students` int(11) DEFAULT 0,
  `present_count` int(11) DEFAULT 0,
  `status` enum('Scheduled','In Progress','Completed','Cancelled') DEFAULT 'Scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_course_date` (`course_id`,`session_date`),
  KEY `idx_lecturer` (`lecturer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `class_sessions`
--

LOCK TABLES `class_sessions` WRITE;
/*!40000 ALTER TABLE `class_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `class_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `classes`
--

DROP TABLE IF EXISTS `classes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `clinical_evaluations`
--

DROP TABLE IF EXISTS `clinical_evaluations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinical_evaluations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `placement_id` int(11) DEFAULT NULL,
  `evaluator_name` varchar(200) DEFAULT NULL,
  `evaluator_title` varchar(100) DEFAULT NULL,
  `evaluation_date` date DEFAULT NULL,
  `professional_conduct` decimal(4,1) DEFAULT NULL,
  `clinical_skills` decimal(4,1) DEFAULT NULL,
  `communication` decimal(4,1) DEFAULT NULL,
  `teamwork` decimal(4,1) DEFAULT NULL,
  `initiative` decimal(4,1) DEFAULT NULL,
  `overall_rating` decimal(4,1) DEFAULT NULL,
  `strengths` text DEFAULT NULL,
  `areas_for_improvement` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `status` enum('Draft','Submitted','Final') DEFAULT 'Draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_student` (`student_id`),
  KEY `idx_placement` (`placement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clinical_evaluations`
--

LOCK TABLES `clinical_evaluations` WRITE;
/*!40000 ALTER TABLE `clinical_evaluations` DISABLE KEYS */;
/*!40000 ALTER TABLE `clinical_evaluations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clinical_placements`
--

DROP TABLE IF EXISTS `clinical_placements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `clinical_rotations`
--

DROP TABLE IF EXISTS `clinical_rotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinical_rotations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `rotation_name` varchar(255) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `facility` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `supervisor_id` int(11) DEFAULT NULL,
  `hours_completed` int(11) DEFAULT 0,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clinical_rotations`
--

LOCK TABLES `clinical_rotations` WRITE;
/*!40000 ALTER TABLE `clinical_rotations` DISABLE KEYS */;
/*!40000 ALTER TABLE `clinical_rotations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clinical_training`
--

DROP TABLE IF EXISTS `clinical_training`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `clinical_training` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `rotation_type` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `supervisor` varchar(200) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Scheduled',
  `evaluation_score` decimal(5,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clinical_training`
--

LOCK TABLES `clinical_training` WRITE;
/*!40000 ALTER TABLE `clinical_training` DISABLE KEYS */;
/*!40000 ALTER TABLE `clinical_training` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `communication_channels`
--

DROP TABLE IF EXISTS `communication_channels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
INSERT INTO `compliance_requirements` VALUES (1,'NCHE Annual Report','Academic',NULL,NULL,'Annual','Not Assessed','2026-09-18',NULL,NULL,NULL,'2026-06-20 01:28:34',NULL),(2,'UNMC License Renewal','Regulatory',NULL,NULL,'Annual','Not Assessed','2026-12-17',NULL,NULL,NULL,'2026-06-20 01:28:34',NULL),(3,'Fire Safety Inspection','Safety',NULL,NULL,'Annual','Not Assessed','2026-08-19',NULL,NULL,NULL,'2026-06-20 01:28:34',NULL),(4,'Tax Filing','Financial',NULL,NULL,'Annual','Not Assessed','2026-08-04',NULL,NULL,NULL,'2026-06-20 01:28:34',NULL),(5,'NCHE Annual Report','Academic',NULL,NULL,'Annual','Not Assessed','2026-09-18',NULL,NULL,NULL,'2026-06-20 01:41:08',NULL),(6,'UNMC License Renewal','Regulatory',NULL,NULL,'Annual','Not Assessed','2026-12-17',NULL,NULL,NULL,'2026-06-20 01:41:08',NULL),(7,'Fire Safety Inspection','Safety',NULL,NULL,'Annual','Not Assessed','2026-08-19',NULL,NULL,NULL,'2026-06-20 01:41:08',NULL),(8,'Tax Filing','Financial',NULL,NULL,'Annual','Not Assessed','2026-08-04',NULL,NULL,NULL,'2026-06-20 01:41:08',NULL),(9,'NCHE Annual Report','Academic',NULL,NULL,'Annual','Not Assessed','2026-09-18',NULL,NULL,NULL,'2026-06-20 01:45:03',NULL),(10,'UNMC License Renewal','Regulatory',NULL,NULL,'Annual','Not Assessed','2026-12-17',NULL,NULL,NULL,'2026-06-20 01:45:03',NULL),(11,'Fire Safety Inspection','Safety',NULL,NULL,'Annual','Not Assessed','2026-08-19',NULL,NULL,NULL,'2026-06-20 01:45:03',NULL),(12,'Tax Filing','Financial',NULL,NULL,'Annual','Not Assessed','2026-08-04',NULL,NULL,NULL,'2026-06-20 01:45:03',NULL),(13,'NCHE Annual Report','Academic',NULL,NULL,'Annual','Not Assessed','2026-09-18',NULL,NULL,NULL,'2026-06-20 01:46:53',NULL),(14,'UNMC License Renewal','Regulatory',NULL,NULL,'Annual','Not Assessed','2026-12-17',NULL,NULL,NULL,'2026-06-20 01:46:53',NULL),(15,'Fire Safety Inspection','Safety',NULL,NULL,'Annual','Not Assessed','2026-08-19',NULL,NULL,NULL,'2026-06-20 01:46:53',NULL),(16,'Tax Filing','Financial',NULL,NULL,'Annual','Not Assessed','2026-08-04',NULL,NULL,NULL,'2026-06-20 01:46:53',NULL);
/*!40000 ALTER TABLE `compliance_requirements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `compliance_tracking`
--

DROP TABLE IF EXISTS `compliance_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `compliance_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requirement_id` int(11) DEFAULT NULL,
  `period` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `evidence_path` varchar(500) DEFAULT NULL,
  `submitted_by` int(11) DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `compliance_tracking`
--

LOCK TABLES `compliance_tracking` WRITE;
/*!40000 ALTER TABLE `compliance_tracking` DISABLE KEYS */;
/*!40000 ALTER TABLE `compliance_tracking` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cost_centers`
--

DROP TABLE IF EXISTS `cost_centers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cost_centers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `department` varchar(200) DEFAULT '',
  `budget` decimal(15,2) DEFAULT 0.00,
  `allocated_amount` decimal(15,2) DEFAULT 0.00,
  `status` enum('active','inactive','closed') DEFAULT 'active',
  `description` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cost_centers`
--

LOCK TABLES `cost_centers` WRITE;
/*!40000 ALTER TABLE `cost_centers` DISABLE KEYS */;
INSERT INTO `cost_centers` VALUES (1,'Academic Affairs','CC-001','Academic',200000000.00,0.00,'active',NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(2,'Administration','CC-002','Admin',100000000.00,0.00,'active',NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(3,'Clinical Services','CC-003','Clinical',150000000.00,0.00,'active',NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(4,'Library','CC-004','Library',50000000.00,0.00,'active',NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(5,'Hostel Management','CC-005','Welfare',80000000.00,0.00,'active',NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47');
/*!40000 ALTER TABLE `cost_centers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `counseling_sessions`
--

DROP TABLE IF EXISTS `counseling_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `course_assignments`
--

DROP TABLE IF EXISTS `course_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lecturer_id` int(11) DEFAULT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `course_name` varchar(255) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `classroom` varchar(100) DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_lecturer` (`lecturer_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_assignments`
--

LOCK TABLES `course_assignments` WRITE;
/*!40000 ALTER TABLE `course_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `course_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_catalog`
--

DROP TABLE IF EXISTS `course_catalog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_catalog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_code` varchar(50) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `program` varchar(200) DEFAULT NULL,
  `level` varchar(50) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `credit_hours` int(11) DEFAULT 0,
  `is_compulsory` tinyint(1) DEFAULT 1,
  `status` varchar(20) DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_catalog`
--

LOCK TABLES `course_catalog` WRITE;
/*!40000 ALTER TABLE `course_catalog` DISABLE KEYS */;
/*!40000 ALTER TABLE `course_catalog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_registrations`
--

DROP TABLE IF EXISTS `course_registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  KEY `idx_cr_student` (`student_id`),
  KEY `idx_cr_course_student` (`course_id`,`student_id`)
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
-- Table structure for table `courses`
--

DROP TABLE IF EXISTS `courses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_code` varchar(50) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `credits` int(11) DEFAULT 0,
  `level` varchar(50) DEFAULT NULL,
  `department` varchar(200) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_code` (`course_code`),
  KEY `idx_courses_department` (`department`),
  KEY `idx_courses_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `courses`
--

LOCK TABLES `courses` WRITE;
/*!40000 ALTER TABLE `courses` DISABLE KEYS */;
/*!40000 ALTER TABLE `courses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `daily_sick_records`
--

DROP TABLE IF EXISTS `daily_sick_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `dashboard_configs`
--

DROP TABLE IF EXISTS `dashboard_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dashboard_configs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `dashboard_type` varchar(100) DEFAULT NULL,
  `config_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`config_data`)),
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dashboard_configs`
--

LOCK TABLES `dashboard_configs` WRITE;
/*!40000 ALTER TABLE `dashboard_configs` DISABLE KEYS */;
/*!40000 ALTER TABLE `dashboard_configs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dashboard_updates`
--

DROP TABLE IF EXISTS `dashboard_updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dashboard_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dashboard_type` varchar(100) DEFAULT NULL,
  `update_type` varchar(100) DEFAULT NULL,
  `update_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`update_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dashboard_updates`
--

LOCK TABLES `dashboard_updates` WRITE;
/*!40000 ALTER TABLE `dashboard_updates` DISABLE KEYS */;
/*!40000 ALTER TABLE `dashboard_updates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `data_ownership_rules`
--

DROP TABLE IF EXISTS `data_ownership_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
INSERT INTO `data_ownership_rules` VALUES (1,1,NULL,'all','full',1,'2026-06-20 01:28:34'),(2,3,NULL,'all','full',1,'2026-06-20 01:28:34'),(3,4,NULL,'all','full',1,'2026-06-20 01:28:34'),(4,1,NULL,'all','full',1,'2026-06-20 01:41:08'),(5,3,NULL,'all','full',1,'2026-06-20 01:41:08'),(6,4,NULL,'all','full',1,'2026-06-20 01:41:08'),(7,1,NULL,'all','full',1,'2026-06-20 01:45:02'),(8,3,NULL,'all','full',1,'2026-06-20 01:45:02'),(9,4,NULL,'all','full',1,'2026-06-20 01:45:02'),(10,1,NULL,'all','full',1,'2026-06-20 01:46:53'),(11,3,NULL,'all','full',1,'2026-06-20 01:46:53'),(12,4,NULL,'all','full',1,'2026-06-20 01:46:53');
/*!40000 ALTER TABLE `data_ownership_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `data_sync_status`
--

DROP TABLE IF EXISTS `data_sync_status`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `delegation_records`
--

DROP TABLE IF EXISTS `delegation_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `delegation_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `delegated_by` int(11) DEFAULT NULL,
  `delegated_to` int(11) DEFAULT NULL,
  `duty_description` text DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delegation_records`
--

LOCK TABLES `delegation_records` WRITE;
/*!40000 ALTER TABLE `delegation_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `delegation_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `department_reviews`
--

DROP TABLE IF EXISTS `department_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `departmental_budgets`
--

DROP TABLE IF EXISTS `departmental_budgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `departmental_budgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department_id` int(11) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `allocated_amount` decimal(15,2) DEFAULT 0.00,
  `spent_amount` decimal(15,2) DEFAULT 0.00,
  `remaining_amount` decimal(15,2) DEFAULT 0.00,
  `status` varchar(20) DEFAULT 'active',
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departmental_budgets`
--

LOCK TABLES `departmental_budgets` WRITE;
/*!40000 ALTER TABLE `departmental_budgets` DISABLE KEYS */;
/*!40000 ALTER TABLE `departmental_budgets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `dg_read_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_key` varchar(64) NOT NULL,
  `user_id` int(11) NOT NULL,
  `read_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_nk_uid` (`notification_key`,`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dg_read_notifications`
--

LOCK TABLES `dg_read_notifications` WRITE;
/*!40000 ALTER TABLE `dg_read_notifications` DISABLE KEYS */;
INSERT INTO `dg_read_notifications` VALUES (1,'activity_308',1,'2026-06-29 17:29:39'),(2,'news_2',1,'2026-06-29 17:29:39'),(3,'activity_307',1,'2026-06-29 17:29:39'),(4,'activity_306',1,'2026-06-29 17:29:39'),(5,'activity_305',1,'2026-06-29 17:29:39'),(6,'activity_304',1,'2026-06-29 17:29:39'),(7,'activity_303',1,'2026-06-29 17:29:39'),(8,'activity_302',1,'2026-06-29 17:29:39'),(9,'activity_301',1,'2026-06-29 17:29:39'),(10,'activity_300',1,'2026-06-29 17:29:39'),(11,'activity_299',1,'2026-06-29 17:29:39'),(12,'activity_298',1,'2026-06-29 17:29:39'),(13,'activity_297',1,'2026-06-29 17:29:39'),(14,'activity_296',1,'2026-06-29 17:29:39'),(15,'activity_295',1,'2026-06-29 17:29:39'),(16,'activity_294',1,'2026-06-29 17:29:39'),(17,'activity_293',1,'2026-06-29 17:29:39'),(18,'activity_292',1,'2026-06-29 17:29:39'),(19,'activity_291',1,'2026-06-29 17:29:39'),(20,'activity_290',1,'2026-06-29 17:29:39'),(21,'activity_289',1,'2026-06-29 17:29:39'),(22,'activity_288',1,'2026-06-29 17:29:39'),(23,'activity_287',1,'2026-06-29 17:29:39'),(24,'activity_286',1,'2026-06-29 17:29:39'),(25,'activity_285',1,'2026-06-29 17:29:39'),(26,'activity_284',1,'2026-06-29 17:29:39'),(27,'activity_283',1,'2026-06-29 17:29:39'),(28,'activity_282',1,'2026-06-29 17:29:39'),(29,'activity_281',1,'2026-06-29 17:29:39'),(30,'activity_280',1,'2026-06-29 17:29:39'),(31,'activity_279',1,'2026-06-29 17:29:39'),(32,'activity_278',1,'2026-06-29 17:29:39'),(33,'activity_277',1,'2026-06-29 17:29:39'),(34,'activity_276',1,'2026-06-29 17:29:39'),(35,'activity_275',1,'2026-06-29 17:29:39'),(36,'activity_274',1,'2026-06-29 17:29:39'),(37,'activity_273',1,'2026-06-29 17:29:39'),(38,'activity_272',1,'2026-06-29 17:29:39'),(39,'activity_271',1,'2026-06-29 17:29:39'),(40,'activity_270',1,'2026-06-29 17:29:39'),(41,'activity_269',1,'2026-06-29 17:29:39'),(42,'activity_268',1,'2026-06-29 17:29:39'),(43,'activity_267',1,'2026-06-29 17:29:39'),(44,'activity_266',1,'2026-06-29 17:29:39'),(45,'activity_265',1,'2026-06-29 17:29:39'),(46,'activity_264',1,'2026-06-29 17:29:39'),(47,'activity_263',1,'2026-06-29 17:29:39'),(48,'activity_262',1,'2026-06-29 17:29:39'),(49,'activity_261',1,'2026-06-29 17:29:39'),(50,'activity_260',1,'2026-06-29 17:29:39'),(51,'activity_259',1,'2026-06-29 17:29:39');
/*!40000 ALTER TABLE `dg_read_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `director_departments`
--

DROP TABLE IF EXISTS `director_departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `director_news`
--

LOCK TABLES `director_news` WRITE;
/*!40000 ALTER TABLE `director_news` DISABLE KEYS */;
INSERT INTO `director_news` VALUES (2,'OFFICIAL PRESS RELEASE: REGISTRATION OF NURSING AND MIDWIFERY STUDENTS FOR THE JUNE–JULY SESSION','official-press-release-registration-of-nursing-and-midwifery-students-for-the-june-july-session','Office of the Director General\r\nDepartment of Nursing and Midwifery Education and Regulation\r\n\r\nThe Office of the Director General wishes to formally inform all stakeholders, training institutions, students, and the general public that the registration exercise for Nursing and Midwifery candidates for the June–July session is officially ongoing.\r\n\r\nThis exercise commenced in June 2026 and will run through July 2026, providing a defined window for eligible candidates to complete their registration in accordance with the established professional and regulatory guidelines.\r\n\r\nPurpose of the Registration Exercise\r\n\r\nThe registration process is a critical step in ensuring that all Nursing and Midwifery students meet the required standards for training, practice, and professional development. It is designed to:\r\n\r\nVerify eligibility of candidates entering Nursing and Midwifery programs\r\nEnsure compliance with national training and regulatory standards\r\nMaintain accurate records of students across accredited institutions\r\nStrengthen the quality and integrity of health training systems\r\nEligibility and Requirements\r\n\r\nAll candidates intending to register are reminded to ensure that they meet the minimum entry requirements as prescribed by the regulatory body. Applicants are expected to present valid academic documents, identification records, and any other supporting materials as required by their respective institutions.\r\n\r\nInstitutions are also advised to guide students accordingly and ensure that only qualified candidates are submitted for registration.\r\n\r\nImportant Notice to Institutions and Applicants\r\n\r\nThe Office of the Director General emphasizes the importance of timely submission of registration details. Late submissions or incomplete documentation may not be processed within the current registration window.\r\n\r\nAll training institutions are urged to:\r\n\r\nFacilitate smooth registration processes for their students\r\nSubmit verified records within the stipulated timeline\r\nEnsure accuracy and accountability in all submitted data\r\nCommitment to Excellence\r\n\r\nThe Nursing and Midwifery profession remains a cornerstone of the national healthcare system. The Directorate is committed to upholding high standards of training, ethics, and professional conduct.\r\n\r\nThis registration exercise is part of the broader effort to strengthen healthcare delivery by ensuring that only competent and well-prepared professionals are admitted into the system.\r\n\r\nConclusion\r\n\r\nAll stakeholders are encouraged to take note of the registration period running from June to July 2026 and to comply fully with the outlined requirements. The Office of the Director General appreciates the continued cooperation of institutions, students, and partners in advancing the standards of Nursing and Midwifery education.\r\n\r\nIssued by:\r\nOffice of the Director General\r\nNursing and Midwifery Education and Regulation\r\nJune 2026','OFFICIAL PRESS RELEASE: REGISTRATION OF NURSING AND MIDWIFERY STUDENTS FOR THE JUNE–JULY SESSION','',NULL,NULL,1,'published','2026-06-29 19:00:59','2026-06-29 16:00:59','2026-06-29 16:00:59');
/*!40000 ALTER TABLE `director_news` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `director_performance_reviews`
--

DROP TABLE IF EXISTS `director_performance_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `disciplinary_actions`
--

DROP TABLE IF EXISTS `disciplinary_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `disciplinary_actions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `incident_date` date NOT NULL,
  `offense_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `action_taken` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Open',
  `reported_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_da_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `disciplinary_actions`
--

LOCK TABLES `disciplinary_actions` WRITE;
/*!40000 ALTER TABLE `disciplinary_actions` DISABLE KEYS */;
/*!40000 ALTER TABLE `disciplinary_actions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `disciplinary_cases`
--

DROP TABLE IF EXISTS `disciplinary_cases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `disciplinary_cases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `case_number` varchar(50) DEFAULT NULL,
  `party_id` int(11) DEFAULT NULL,
  `party_type` varchar(50) DEFAULT NULL,
  `incident_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'open',
  `assigned_to` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `disciplinary_cases`
--

LOCK TABLES `disciplinary_cases` WRITE;
/*!40000 ALTER TABLE `disciplinary_cases` DISABLE KEYS */;
/*!40000 ALTER TABLE `disciplinary_cases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `disciplinary_records`
--

DROP TABLE IF EXISTS `disciplinary_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `disciplinary_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `incident_date` date DEFAULT NULL,
  `incident_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `action_taken` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'open',
  `reported_by` int(11) DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `disciplinary_records`
--

LOCK TABLES `disciplinary_records` WRITE;
/*!40000 ALTER TABLE `disciplinary_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `disciplinary_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_generation_log`
--

DROP TABLE IF EXISTS `document_generation_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_generation_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_type` varchar(100) DEFAULT NULL,
  `document_id` int(11) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `created_at_ts` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_generation_log`
--

LOCK TABLES `document_generation_log` WRITE;
/*!40000 ALTER TABLE `document_generation_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_generation_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_print_configs`
--

DROP TABLE IF EXISTS `document_print_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_print_configs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_type` varchar(100) DEFAULT NULL,
  `paper_size` varchar(20) DEFAULT 'A4',
  `orientation` varchar(20) DEFAULT 'portrait',
  `margin_top` int(11) DEFAULT 20,
  `margin_bottom` int(11) DEFAULT 20,
  `margin_left` int(11) DEFAULT 15,
  `margin_right` int(11) DEFAULT 15,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_print_configs`
--

LOCK TABLES `document_print_configs` WRITE;
/*!40000 ALTER TABLE `document_print_configs` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_print_configs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_settings`
--

DROP TABLE IF EXISTS `document_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_settings`
--

LOCK TABLES `document_settings` WRITE;
/*!40000 ALTER TABLE `document_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `document_templates`
--

DROP TABLE IF EXISTS `document_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `document_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_name` varchar(200) DEFAULT NULL,
  `template_type` varchar(100) DEFAULT NULL,
  `template_content` longtext DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `is_deleted` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `document_templates`
--

LOCK TABLES `document_templates` WRITE;
/*!40000 ALTER TABLE `document_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `document_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `donations`
--

DROP TABLE IF EXISTS `donations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `donations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `donor_name` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(100) DEFAULT 'cash',
  `method` varchar(100) DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `donation_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','completed','cancelled') DEFAULT 'completed',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `donations`
--

LOCK TABLES `donations` WRITE;
/*!40000 ALTER TABLE `donations` DISABLE KEYS */;
INSERT INTO `donations` VALUES (1,'John Doe Foundation',NULL,NULL,5000000.00,'bank',NULL,'Library renovation fund',NULL,'2026-06-23','2026-07-03 05:16:47','completed','2026-07-03 05:16:47'),(2,'Parents Association',NULL,NULL,2000000.00,'cash',NULL,'Sports equipment',NULL,'2026-06-13','2026-07-03 05:16:47','completed','2026-07-03 05:16:47'),(3,'Anonymous Donor',NULL,NULL,1000000.00,'mobile_money',NULL,'Student welfare',NULL,'2026-06-18','2026-07-03 05:16:47','completed','2026-07-03 05:16:47');
/*!40000 ALTER TABLE `donations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `duty_roster`
--

DROP TABLE IF EXISTS `duty_roster`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `email_logs`
--

DROP TABLE IF EXISTS `email_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_logs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `recipient_email` varchar(255) NOT NULL,
  `recipient_name` varchar(120) DEFAULT NULL,
  `recipient_type` enum('staff','student','external') NOT NULL DEFAULT 'staff',
  `recipient_id` int(11) unsigned DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `body` text DEFAULT NULL,
  `template_name` varchar(100) DEFAULT NULL,
  `status` enum('queued','sent','delivered','failed','bounced') NOT NULL DEFAULT 'queued',
  `error_message` text DEFAULT NULL,
  `sent_by` int(11) unsigned DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_recipient` (`recipient_email`),
  KEY `idx_status` (`status`),
  KEY `idx_sent_at` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_logs`
--

LOCK TABLES `email_logs` WRITE;
/*!40000 ALTER TABLE `email_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `email_notifications_queue`
--

DROP TABLE IF EXISTS `email_notifications_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `email_notifications_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `recipient_email` varchar(255) DEFAULT NULL,
  `recipient_name` varchar(255) DEFAULT NULL,
  `subject` varchar(500) DEFAULT NULL,
  `email_content` text DEFAULT NULL,
  `email_type` varchar(50) DEFAULT NULL,
  `priority` varchar(20) DEFAULT 'normal',
  `status` varchar(20) DEFAULT 'pending',
  `scheduled_at` datetime DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `email_notifications_queue`
--

LOCK TABLES `email_notifications_queue` WRITE;
/*!40000 ALTER TABLE `email_notifications_queue` DISABLE KEYS */;
/*!40000 ALTER TABLE `email_notifications_queue` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `emergency_contacts`
--

DROP TABLE IF EXISTS `emergency_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `emergency_contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contact_name` varchar(255) DEFAULT NULL,
  `relationship` varchar(100) DEFAULT NULL,
  `phone_primary` varchar(50) DEFAULT NULL,
  `phone_secondary` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `priority` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `emergency_contacts`
--

LOCK TABLES `emergency_contacts` WRITE;
/*!40000 ALTER TABLE `emergency_contacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `emergency_contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employee_training`
--

DROP TABLE IF EXISTS `employee_training`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `employee_training` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `training_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `status` varchar(50) DEFAULT 'Enrolled',
  `completion_date` date DEFAULT NULL,
  `certificate_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_et_training` (`training_id`),
  KEY `idx_et_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employee_training`
--

LOCK TABLES `employee_training` WRITE;
/*!40000 ALTER TABLE `employee_training` DISABLE KEYS */;
/*!40000 ALTER TABLE `employee_training` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employment_contracts`
--

DROP TABLE IF EXISTS `employment_contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `employment_contracts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `contract_type` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `salary` decimal(15,2) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `terms` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employment_contracts`
--

LOCK TABLES `employment_contracts` WRITE;
/*!40000 ALTER TABLE `employment_contracts` DISABLE KEYS */;
/*!40000 ALTER TABLE `employment_contracts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employment_details`
--

DROP TABLE IF EXISTS `employment_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `employment_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `employment_type` varchar(100) DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `salary_grade` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employment_details`
--

LOCK TABLES `employment_details` WRITE;
/*!40000 ALTER TABLE `employment_details` DISABLE KEYS */;
/*!40000 ALTER TABLE `employment_details` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `error_logs`
--

DROP TABLE IF EXISTS `error_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
  KEY `student_id` (`student_id`),
  KEY `idx_er_exam_student` (`exam_id`,`student_id`)
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
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `examination_results`
--

DROP TABLE IF EXISTS `examination_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `examination_results` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `ca_score` decimal(5,2) DEFAULT NULL,
  `exam_score` decimal(5,2) DEFAULT NULL,
  `total_score` decimal(5,2) DEFAULT NULL,
  `grade` varchar(5) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Draft',
  `entered_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `examination_results`
--

LOCK TABLES `examination_results` WRITE;
/*!40000 ALTER TABLE `examination_results` DISABLE KEYS */;
/*!40000 ALTER TABLE `examination_results` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `exams`
--

DROP TABLE IF EXISTS `exams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `expenditures`
--

DROP TABLE IF EXISTS `expenditures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `expenditures` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `expenditure_number` varchar(50) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `budget_line_id` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `expenditure_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenditures`
--

LOCK TABLES `expenditures` WRITE;
/*!40000 ALTER TABLE `expenditures` DISABLE KEYS */;
/*!40000 ALTER TABLE `expenditures` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expense_approvals`
--

DROP TABLE IF EXISTS `expense_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `expense_approvals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `expense_id` int(11) DEFAULT NULL,
  `expense_type` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `requested_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expense_approvals`
--

LOCK TABLES `expense_approvals` WRITE;
/*!40000 ALTER TABLE `expense_approvals` DISABLE KEYS */;
/*!40000 ALTER TABLE `expense_approvals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `expense_title` varchar(255) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `category` varchar(100) DEFAULT 'General',
  `requested_by` varchar(200) DEFAULT '',
  `description` text DEFAULT NULL,
  `expense_date` date DEFAULT NULL,
  `date` date DEFAULT NULL,
  `status` enum('pending','approved','rejected','paid') DEFAULT 'pending',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenses`
--

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
INSERT INTO `expenses` VALUES (1,'Office Supplies - January','Office Supplies - January',2500000.00,'Administrative','Bursar',NULL,'2026-07-03',NULL,'approved',NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(2,'Electricity Bill - January','Electricity Bill - January',1800000.00,'Utilities','Bursar',NULL,'2026-07-03',NULL,'paid',NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(3,'Internet Subscription','Internet Subscription',500000.00,'Utilities','ICT Department',NULL,'2026-06-28',NULL,'approved',NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(4,'Cleaning Materials','Cleaning Materials',350000.00,'General','Matron',NULL,'2026-06-26',NULL,'pending',NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47');
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `facilities`
--

DROP TABLE IF EXISTS `facilities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `fee_accounts`
--

DROP TABLE IF EXISTS `fee_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fee_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `fee_type` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT 0.00,
  `paid` decimal(15,2) DEFAULT 0.00,
  `balance` decimal(15,2) DEFAULT 0.00,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fee_accounts`
--

LOCK TABLES `fee_accounts` WRITE;
/*!40000 ALTER TABLE `fee_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `fee_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fee_adjustments`
--

DROP TABLE IF EXISTS `fee_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fee_adjustments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(50) NOT NULL,
  `adjustment_type` enum('discount','waiver','refund','penalty') DEFAULT 'discount',
  `type` varchar(50) DEFAULT 'discount',
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `reason` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
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
SET character_set_client = utf8mb4;
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
-- Table structure for table `fee_structure`
--

DROP TABLE IF EXISTS `fee_structure`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fee_structure` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program` varchar(200) DEFAULT NULL,
  `level` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `fee_type` varchar(50) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fee_structure`
--

LOCK TABLES `fee_structure` WRITE;
/*!40000 ALTER TABLE `fee_structure` DISABLE KEYS */;
/*!40000 ALTER TABLE `fee_structure` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `file_uploads`
--

DROP TABLE IF EXISTS `file_uploads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `file_uploads` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) unsigned NOT NULL DEFAULT 0,
  `mime_type` varchar(100) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `uploaded_by` int(11) unsigned NOT NULL,
  `uploaded_by_name` varchar(120) DEFAULT NULL,
  `entity_type` varchar(60) DEFAULT NULL,
  `entity_id` int(11) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `download_count` int(11) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_entity` (`entity_type`,`entity_id`),
  KEY `idx_uploaded_by` (`uploaded_by`),
  KEY `idx_file_type` (`file_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `file_uploads`
--

LOCK TABLES `file_uploads` WRITE;
/*!40000 ALTER TABLE `file_uploads` DISABLE KEYS */;
/*!40000 ALTER TABLE `file_uploads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `financial_audit_log`
--

DROP TABLE IF EXISTS `financial_audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `financial_audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `action` varchar(100) DEFAULT NULL,
  `table_name` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `old_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`old_values`)),
  `new_values` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`new_values`)),
  `performed_by` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `financial_audit_log`
--

LOCK TABLES `financial_audit_log` WRITE;
/*!40000 ALTER TABLE `financial_audit_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `financial_audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `financial_messages`
--

DROP TABLE IF EXISTS `financial_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `financial_records`
--

DROP TABLE IF EXISTS `financial_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `financial_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `record_type` varchar(50) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `transaction_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `financial_records`
--

LOCK TABLES `financial_records` WRITE;
/*!40000 ALTER TABLE `financial_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `financial_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fuel_management`
--

DROP TABLE IF EXISTS `fuel_management`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `fuel_management` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle_id` int(11) DEFAULT NULL,
  `fuel_type` varchar(50) DEFAULT NULL,
  `fuel_quantity` decimal(10,2) DEFAULT NULL,
  `cost_per_unit` decimal(10,2) DEFAULT NULL,
  `total_cost` decimal(15,2) DEFAULT NULL,
  `fueling_date` date DEFAULT NULL,
  `odometer_reading` int(11) DEFAULT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `station` varchar(255) DEFAULT NULL,
  `receipt_number` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `fuel_management`
--

LOCK TABLES `fuel_management` WRITE;
/*!40000 ALTER TABLE `fuel_management` DISABLE KEYS */;
/*!40000 ALTER TABLE `fuel_management` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `general_ledger`
--

DROP TABLE IF EXISTS `general_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `general_ledger` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_date` date DEFAULT NULL,
  `date` date DEFAULT NULL,
  `account_name` varchar(200) DEFAULT NULL,
  `account_code` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `debit_amount` decimal(15,2) DEFAULT 0.00,
  `debit` decimal(15,2) DEFAULT 0.00,
  `credit_amount` decimal(15,2) DEFAULT 0.00,
  `credit` decimal(15,2) DEFAULT 0.00,
  `reference` varchar(100) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `general_ledger`
--

LOCK TABLES `general_ledger` WRITE;
/*!40000 ALTER TABLE `general_ledger` DISABLE KEYS */;
INSERT INTO `general_ledger` VALUES (1,'2026-07-03',NULL,'Cash',NULL,'Opening balance',10000000.00,0.00,0.00,0.00,NULL,NULL,'2026-07-03 05:16:47'),(2,'2026-07-03',NULL,'Tuition Fees',NULL,'Fee collection - January',0.00,0.00,5000000.00,0.00,NULL,NULL,'2026-07-03 05:16:47'),(3,'2026-07-01',NULL,'Bank Accounts',NULL,'Bank deposit',5000000.00,0.00,0.00,0.00,NULL,NULL,'2026-07-03 05:16:47'),(4,'2026-06-30',NULL,'Salaries',NULL,'Staff salary payment',0.00,0.00,8000000.00,0.00,NULL,NULL,'2026-07-03 05:16:47'),(5,'2026-06-28',NULL,'Utilities',NULL,'Electricity bill',0.00,0.00,1800000.00,0.00,NULL,NULL,'2026-07-03 05:16:47');
/*!40000 ALTER TABLE `general_ledger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `generated_documents`
--

DROP TABLE IF EXISTS `generated_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
INSERT INTO `gpa_settings` VALUES (1,'pass_mark','50','Minimum pass percentage','2026-06-26 06:25:09','2026-06-26 06:25:09'),(2,'distinction_threshold','80','Minimum percentage for Distinction','2026-06-26 06:25:09','2026-06-26 06:25:09'),(3,'credit_threshold','60','Minimum percentage for Credit','2026-06-26 06:25:09','2026-06-26 06:25:09'),(4,'supplementary_min','35','Minimum percentage eligible for supplementary exam','2026-06-26 06:25:09','2026-06-26 06:25:09'),(5,'max_supplementary_grade','C','Maximum grade after supplementary exam','2026-06-26 06:25:09','2026-06-26 06:25:09'),(6,'retake_max_attempts','3','Maximum retake attempts allowed','2026-06-26 06:25:09','2026-06-26 06:25:09'),(7,'academic_probation_cgpa','1.50','CGPA below this triggers academic probation','2026-06-26 06:25:09','2026-06-26 06:25:09'),(8,'suspension_cgpa','1.00','CGPA below this triggers suspension','2026-06-26 06:25:09','2026-06-26 06:25:09'),(9,'graduation_min_cgpa','2.00','Minimum CGPA required for graduation','2026-06-26 06:25:09','2026-06-26 06:25:09'),(10,'grading_system','letter','Grading system type','2026-06-26 06:25:09','2026-06-26 06:25:09');
/*!40000 ALTER TABLE `gpa_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grade_change_history`
--

DROP TABLE IF EXISTS `grade_change_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `grade_change_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `old_grade` varchar(5) DEFAULT NULL,
  `new_grade` varchar(5) DEFAULT NULL,
  `old_marks` decimal(5,2) DEFAULT NULL,
  `new_marks` decimal(5,2) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grade_change_history`
--

LOCK TABLES `grade_change_history` WRITE;
/*!40000 ALTER TABLE `grade_change_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `grade_change_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grade_scale`
--

DROP TABLE IF EXISTS `grade_scale`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
INSERT INTO `grade_scale` VALUES (1,'A',4.00,80.00,100.00,'Distinction',1,'2026-06-26 06:25:09'),(2,'B',3.00,70.00,79.99,'Credit',1,'2026-06-26 06:25:09'),(3,'C',2.00,60.00,69.99,'Credit',1,'2026-06-26 06:25:09'),(4,'D',1.00,50.00,59.99,'Pass',1,'2026-06-26 06:25:09'),(5,'F',0.00,0.00,49.99,'Fail',1,'2026-06-26 06:25:09');
/*!40000 ALTER TABLE `grade_scale` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grade_scales`
--

DROP TABLE IF EXISTS `grade_scales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `grading_approval_workflow_log`
--

DROP TABLE IF EXISTS `grading_approval_workflow_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `grading_approval_workflow_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `result_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `acted_by` int(11) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `acted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grading_approval_workflow_log`
--

LOCK TABLES `grading_approval_workflow_log` WRITE;
/*!40000 ALTER TABLE `grading_approval_workflow_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `grading_approval_workflow_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grading_notifications`
--

DROP TABLE IF EXISTS `grading_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `grading_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `notification_type` varchar(100) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grading_notifications`
--

LOCK TABLES `grading_notifications` WRITE;
/*!40000 ALTER TABLE `grading_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `grading_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `graduation_approvals`
--

DROP TABLE IF EXISTS `graduation_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `health_incidents`
--

DROP TABLE IF EXISTS `health_incidents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `health_incidents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `incident_number` varchar(50) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `incident_type` varchar(100) DEFAULT NULL,
  `symptoms` text DEFAULT NULL,
  `severity` varchar(20) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `action_taken` text DEFAULT NULL,
  `treatment_given` text DEFAULT NULL,
  `referred_to` varchar(255) DEFAULT NULL,
  `parent_notified` tinyint(1) DEFAULT 0,
  `follow_up_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Reported',
  `reported_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `health_incidents`
--

LOCK TABLES `health_incidents` WRITE;
/*!40000 ALTER TABLE `health_incidents` DISABLE KEYS */;
/*!40000 ALTER TABLE `health_incidents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hostel_blocks`
--

DROP TABLE IF EXISTS `hostel_blocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hostel_blocks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `block_name` varchar(100) NOT NULL,
  `total_rooms` int(11) DEFAULT 0,
  `gender` enum('Male','Female','Mixed') DEFAULT 'Mixed',
  `status` enum('Active','Inactive','Maintenance') DEFAULT 'Active',
  `warden_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hostel_blocks`
--

LOCK TABLES `hostel_blocks` WRITE;
/*!40000 ALTER TABLE `hostel_blocks` DISABLE KEYS */;
/*!40000 ALTER TABLE `hostel_blocks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hostel_clearance`
--

DROP TABLE IF EXISTS `hostel_clearance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hostel_clearance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `hostel_allocation_id` int(11) DEFAULT NULL,
  `cleared_by` int(11) DEFAULT NULL,
  `clearance_date` date DEFAULT NULL,
  `condition_notes` text DEFAULT NULL,
  `key_returned` tinyint(1) DEFAULT 0,
  `status` enum('Pending','Cleared','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hostel_clearance`
--

LOCK TABLES `hostel_clearance` WRITE;
/*!40000 ALTER TABLE `hostel_clearance` DISABLE KEYS */;
/*!40000 ALTER TABLE `hostel_clearance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hostel_inspections`
--

DROP TABLE IF EXISTS `hostel_inspections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hostel_inspections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hostel_room_id` int(11) DEFAULT NULL,
  `inspection_date` date NOT NULL,
  `inspected_by` int(11) DEFAULT NULL,
  `condition_rating` varchar(20) DEFAULT 'Good',
  `cleanliness_rating` varchar(20) DEFAULT 'Good',
  `findings` text DEFAULT NULL,
  `recommendations` text DEFAULT NULL,
  `status` enum('Open','In Progress','Completed','Closed') DEFAULT 'Open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hostel_inspections`
--

LOCK TABLES `hostel_inspections` WRITE;
/*!40000 ALTER TABLE `hostel_inspections` DISABLE KEYS */;
/*!40000 ALTER TABLE `hostel_inspections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hostel_maintenance_requests`
--

DROP TABLE IF EXISTS `hostel_maintenance_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hostel_maintenance_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `hostel_room_id` int(11) DEFAULT NULL,
  `requested_by` int(11) DEFAULT NULL,
  `issue_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `priority` enum('Low','Medium','High','Urgent') DEFAULT 'Medium',
  `status` enum('Open','In Progress','Completed','Closed') DEFAULT 'Open',
  `assigned_to` int(11) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_hmr_requested` (`requested_by`),
  KEY `fk_hmr_assigned` (`assigned_to`),
  CONSTRAINT `fk_hmr_assigned` FOREIGN KEY (`assigned_to`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_hmr_requested` FOREIGN KEY (`requested_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hostel_maintenance_requests`
--

LOCK TABLES `hostel_maintenance_requests` WRITE;
/*!40000 ALTER TABLE `hostel_maintenance_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `hostel_maintenance_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hostel_management`
--

DROP TABLE IF EXISTS `hostel_management`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `hr_activity_logs`
--

DROP TABLE IF EXISTS `hr_activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr_activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `user_role` varchar(100) DEFAULT NULL,
  `action_type` varchar(100) DEFAULT NULL,
  `entity_type` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_activity_logs`
--

LOCK TABLES `hr_activity_logs` WRITE;
/*!40000 ALTER TABLE `hr_activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr_activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_announcements`
--

DROP TABLE IF EXISTS `hr_announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr_announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `content` text DEFAULT NULL,
  `priority` varchar(20) DEFAULT 'normal',
  `target_audience` varchar(100) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `expires_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_announcements`
--

LOCK TABLES `hr_announcements` WRITE;
/*!40000 ALTER TABLE `hr_announcements` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr_announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_reports`
--

DROP TABLE IF EXISTS `hr_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_type` varchar(100) DEFAULT NULL,
  `report_title` varchar(255) DEFAULT NULL,
  `report_data` longtext DEFAULT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'generated',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_reports`
--

LOCK TABLES `hr_reports` WRITE;
/*!40000 ALTER TABLE `hr_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_settings`
--

DROP TABLE IF EXISTS `hr_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_settings`
--

LOCK TABLES `hr_settings` WRITE;
/*!40000 ALTER TABLE `hr_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hr_users`
--

DROP TABLE IF EXISTS `hr_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `hr_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(255) DEFAULT NULL,
  `role` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hr_users`
--

LOCK TABLES `hr_users` WRITE;
/*!40000 ALTER TABLE `hr_users` DISABLE KEYS */;
/*!40000 ALTER TABLE `hr_users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `igangaschoolofl_students_db.bank_accounts`
--

DROP TABLE IF EXISTS `igangaschoolofl_students_db.bank_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `igangaschoolofl_students_db.bank_accounts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `bank_name` varchar(200) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `account_name` varchar(200) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ba_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `igangaschoolofl_students_db.bank_accounts`
--

LOCK TABLES `igangaschoolofl_students_db.bank_accounts` WRITE;
/*!40000 ALTER TABLE `igangaschoolofl_students_db.bank_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `igangaschoolofl_students_db.bank_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `igangaschoolofl_students_db.fee_structure`
--

DROP TABLE IF EXISTS `igangaschoolofl_students_db.fee_structure`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `igangaschoolofl_students_db.fee_structure` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `fee_type` varchar(100) DEFAULT NULL,
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `amount_paid` decimal(15,2) DEFAULT 0.00,
  `balance` decimal(15,2) DEFAULT 0.00,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_fs_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `igangaschoolofl_students_db.fee_structure`
--

LOCK TABLES `igangaschoolofl_students_db.fee_structure` WRITE;
/*!40000 ALTER TABLE `igangaschoolofl_students_db.fee_structure` DISABLE KEYS */;
/*!40000 ALTER TABLE `igangaschoolofl_students_db.fee_structure` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `igangaschoolofl_students_db.journal_entries`
--

DROP TABLE IF EXISTS `igangaschoolofl_students_db.journal_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `igangaschoolofl_students_db.journal_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entry_date` date NOT NULL,
  `description` text DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `total_debit` decimal(15,2) DEFAULT 0.00,
  `total_credit` decimal(15,2) DEFAULT 0.00,
  `status` varchar(50) DEFAULT 'Draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `igangaschoolofl_students_db.journal_entries`
--

LOCK TABLES `igangaschoolofl_students_db.journal_entries` WRITE;
/*!40000 ALTER TABLE `igangaschoolofl_students_db.journal_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `igangaschoolofl_students_db.journal_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `igangaschoolofl_students_db.journal_entry_lines`
--

DROP TABLE IF EXISTS `igangaschoolofl_students_db.journal_entry_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `igangaschoolofl_students_db.journal_entry_lines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entry_id` int(11) DEFAULT NULL,
  `account_name` varchar(200) DEFAULT NULL,
  `debit` decimal(15,2) DEFAULT 0.00,
  `credit` decimal(15,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_jel_entry` (`entry_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `igangaschoolofl_students_db.journal_entry_lines`
--

LOCK TABLES `igangaschoolofl_students_db.journal_entry_lines` WRITE;
/*!40000 ALTER TABLE `igangaschoolofl_students_db.journal_entry_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `igangaschoolofl_students_db.journal_entry_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `igangaschoolofl_students_db.notifications`
--

DROP TABLE IF EXISTS `igangaschoolofl_students_db.notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `igangaschoolofl_students_db.notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `igangaschoolofl_students_db.notifications`
--

LOCK TABLES `igangaschoolofl_students_db.notifications` WRITE;
/*!40000 ALTER TABLE `igangaschoolofl_students_db.notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `igangaschoolofl_students_db.notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `igangaschoolofl_students_db.scholarships`
--

DROP TABLE IF EXISTS `igangaschoolofl_students_db.scholarships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `igangaschoolofl_students_db.scholarships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `scholarship_name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT 0.00,
  `eligibility` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `igangaschoolofl_students_db.scholarships`
--

LOCK TABLES `igangaschoolofl_students_db.scholarships` WRITE;
/*!40000 ALTER TABLE `igangaschoolofl_students_db.scholarships` DISABLE KEYS */;
/*!40000 ALTER TABLE `igangaschoolofl_students_db.scholarships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `igangaschoolofl_students_db.student_scholarships`
--

DROP TABLE IF EXISTS `igangaschoolofl_students_db.student_scholarships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `igangaschoolofl_students_db.student_scholarships` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `scholarship_id` int(11) DEFAULT NULL,
  `awarded_date` date DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT 0.00,
  `status` varchar(50) DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ss_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `igangaschoolofl_students_db.student_scholarships`
--

LOCK TABLES `igangaschoolofl_students_db.student_scholarships` WRITE;
/*!40000 ALTER TABLE `igangaschoolofl_students_db.student_scholarships` DISABLE KEYS */;
/*!40000 ALTER TABLE `igangaschoolofl_students_db.student_scholarships` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `incident_reports`
--

DROP TABLE IF EXISTS `incident_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `incident_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_number` varchar(50) DEFAULT NULL,
  `reported_by` int(11) DEFAULT NULL,
  `incident_type` varchar(100) DEFAULT NULL,
  `severity` varchar(20) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'open',
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `incident_reports`
--

LOCK TABLES `incident_reports` WRITE;
/*!40000 ALTER TABLE `incident_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `incident_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `institutional_alerts`
--

DROP TABLE IF EXISTS `institutional_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
INSERT INTO `institutional_alerts` VALUES (1,'Staff Attendance Drop','Staff attendance dropped below 80% this week.','info','high',NULL,NULL,0,NULL,NULL,NULL,'2026-06-20 01:28:34'),(2,'Fee Collection Target','Monthly fee collection at 65% of target.','info','medium',NULL,NULL,0,NULL,NULL,NULL,'2026-06-20 01:28:34'),(3,'Exam Preparation','Final exams scheduled in 3 weeks.','info','low',NULL,NULL,0,NULL,NULL,NULL,'2026-06-20 01:28:34'),(4,'Test Alert','Test','info','low',NULL,NULL,0,NULL,NULL,NULL,'2026-06-20 01:33:53'),(5,'Staff Attendance Drop','Staff attendance dropped below 80% this week.','info','high',NULL,NULL,0,NULL,NULL,NULL,'2026-06-20 01:37:17'),(6,'Fee Collection Target','Monthly fee collection at 65% of target.','info','medium',NULL,NULL,0,NULL,NULL,NULL,'2026-06-20 01:37:17'),(7,'Exam Preparation','Final exams scheduled in 3 weeks.','info','low',NULL,NULL,0,NULL,NULL,NULL,'2026-06-20 01:37:17'),(8,'Staff Attendance Drop','Staff attendance dropped below 80% this week.','info','high',NULL,NULL,0,NULL,NULL,NULL,'2026-06-20 01:41:08'),(9,'Fee Collection Target','Monthly fee collection at 65% of target.','info','medium',NULL,NULL,0,NULL,NULL,NULL,'2026-06-20 01:41:08'),(10,'Exam Preparation','Final exams scheduled in 3 weeks.','info','low',NULL,NULL,0,NULL,NULL,NULL,'2026-06-20 01:41:08'),(11,'Staff Attendance Drop','Staff attendance dropped below 80% this week.','info','high',NULL,NULL,0,NULL,NULL,NULL,'2026-06-20 01:45:03'),(12,'Fee Collection Target','Monthly fee collection at 65% of target.','info','medium',NULL,NULL,0,NULL,NULL,NULL,'2026-06-20 01:45:03'),(13,'Exam Preparation','Final exams scheduled in 3 weeks.','info','low',NULL,NULL,0,NULL,NULL,NULL,'2026-06-20 01:45:03'),(14,'Staff Attendance Drop','Staff attendance dropped below 80% this week.','info','high',NULL,NULL,0,NULL,NULL,NULL,'2026-06-20 01:46:53'),(15,'Fee Collection Target','Monthly fee collection at 65% of target.','info','medium',NULL,NULL,0,NULL,NULL,NULL,'2026-06-20 01:46:53'),(16,'Exam Preparation','Final exams scheduled in 3 weeks.','info','low',NULL,NULL,0,NULL,NULL,NULL,'2026-06-20 01:46:53');
/*!40000 ALTER TABLE `institutional_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `institutional_risks`
--

DROP TABLE IF EXISTS `institutional_risks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
INSERT INTO `institutional_risks` VALUES (1,'Student Enrolment Decline',NULL,'Operational','Possible','Major',12,NULL,NULL,NULL,'Identified',NULL,'2026-06-20 01:28:34',NULL),(2,'Staff Retention',NULL,'HR','Likely','Moderate',12,NULL,NULL,NULL,'Monitoring',NULL,'2026-06-20 01:28:34',NULL),(3,'Budget Shortfall',NULL,'Financial','Possible','Major',12,NULL,NULL,NULL,'Identified',NULL,'2026-06-20 01:28:34',NULL),(4,'Regulatory Non-Compliance',NULL,'Compliance','Unlikely','Major',6,NULL,NULL,NULL,'Monitoring',NULL,'2026-06-20 01:28:34',NULL),(5,'Student Enrolment Decline',NULL,'Operational','Possible','Major',12,NULL,NULL,NULL,'Identified',NULL,'2026-06-20 01:41:08',NULL),(6,'Staff Retention',NULL,'HR','Likely','Moderate',12,NULL,NULL,NULL,'Monitoring',NULL,'2026-06-20 01:41:08',NULL),(7,'Budget Shortfall',NULL,'Financial','Possible','Major',12,NULL,NULL,NULL,'Identified',NULL,'2026-06-20 01:41:08',NULL),(8,'Regulatory Non-Compliance',NULL,'Compliance','Unlikely','Major',6,NULL,NULL,NULL,'Monitoring',NULL,'2026-06-20 01:41:08',NULL),(9,'Student Enrolment Decline',NULL,'Operational','Possible','Major',12,NULL,NULL,NULL,'Identified',NULL,'2026-06-20 01:45:03',NULL),(10,'Staff Retention',NULL,'HR','Likely','Moderate',12,NULL,NULL,NULL,'Monitoring',NULL,'2026-06-20 01:45:03',NULL),(11,'Budget Shortfall',NULL,'Financial','Possible','Major',12,NULL,NULL,NULL,'Identified',NULL,'2026-06-20 01:45:03',NULL),(12,'Regulatory Non-Compliance',NULL,'Compliance','Unlikely','Major',6,NULL,NULL,NULL,'Monitoring',NULL,'2026-06-20 01:45:03',NULL),(13,'Student Enrolment Decline',NULL,'Operational','Possible','Major',12,NULL,NULL,NULL,'Identified',NULL,'2026-06-20 01:46:53',NULL),(14,'Staff Retention',NULL,'HR','Likely','Moderate',12,NULL,NULL,NULL,'Monitoring',NULL,'2026-06-20 01:46:53',NULL),(15,'Budget Shortfall',NULL,'Financial','Possible','Major',12,NULL,NULL,NULL,'Identified',NULL,'2026-06-20 01:46:53',NULL),(16,'Regulatory Non-Compliance',NULL,'Compliance','Unlikely','Major',6,NULL,NULL,NULL,'Monitoring',NULL,'2026-06-20 01:46:53',NULL);
/*!40000 ALTER TABLE `institutional_risks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `intakes`
--

DROP TABLE IF EXISTS `intakes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `interview_scheduling`
--

DROP TABLE IF EXISTS `interview_scheduling`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `interview_scheduling` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) DEFAULT NULL,
  `interview_date` datetime DEFAULT NULL,
  `interviewer_id` int(11) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `interview_scheduling`
--

LOCK TABLES `interview_scheduling` WRITE;
/*!40000 ALTER TABLE `interview_scheduling` DISABLE KEYS */;
/*!40000 ALTER TABLE `interview_scheduling` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `inventory_items`
--

DROP TABLE IF EXISTS `inventory_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_name` varchar(255) DEFAULT NULL,
  `item_code` varchar(50) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT 0,
  `unit` varchar(50) DEFAULT NULL,
  `unit_cost` decimal(15,2) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `reorder_level` int(11) DEFAULT 0,
  `status` varchar(20) DEFAULT 'in_stock',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_items`
--

LOCK TABLES `inventory_items` WRITE;
/*!40000 ALTER TABLE `inventory_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_reports`
--

DROP TABLE IF EXISTS `inventory_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `inventory_transactions`
--

DROP TABLE IF EXISTS `inventory_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) DEFAULT NULL,
  `transaction_type` varchar(50) DEFAULT NULL,
  `quantity` int(11) DEFAULT NULL,
  `unit_cost` decimal(15,2) DEFAULT NULL,
  `total_cost` decimal(15,2) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_transactions`
--

LOCK TABLES `inventory_transactions` WRITE;
/*!40000 ALTER TABLE `inventory_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoice_records`
--

DROP TABLE IF EXISTS `invoice_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `paid_amount` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `invoice_records`
--

LOCK TABLES `invoice_records` WRITE;
/*!40000 ALTER TABLE `invoice_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoice_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `it_infrastructure`
--

DROP TABLE IF EXISTS `it_infrastructure`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `position_id` int(11) DEFAULT NULL,
  `applicant_name` varchar(200) NOT NULL,
  `email` varchar(200) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `cv_path` varchar(500) DEFAULT NULL,
  `cover_letter` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Received',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_app_position` (`position_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_applications`
--

LOCK TABLES `job_applications` WRITE;
/*!40000 ALTER TABLE `job_applications` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_offers`
--

DROP TABLE IF EXISTS `job_offers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_offers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `salary` decimal(15,2) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `offered_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_offers`
--

LOCK TABLES `job_offers` WRITE;
/*!40000 ALTER TABLE `job_offers` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_offers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_vacancies`
--

DROP TABLE IF EXISTS `job_vacancies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_vacancies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `salary_range` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'open',
  `posted_date` date DEFAULT NULL,
  `closing_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_vacancies`
--

LOCK TABLES `job_vacancies` WRITE;
/*!40000 ALTER TABLE `job_vacancies` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_vacancies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lab_attendance`
--

DROP TABLE IF EXISTS `lab_attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `student_name` varchar(200) DEFAULT NULL,
  `session` varchar(100) DEFAULT NULL,
  `attendance_status` varchar(50) DEFAULT 'Present',
  `check_in_time` time DEFAULT NULL,
  `marked_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_lab_attendance` (`session_id`,`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_attendance`
--

LOCK TABLES `lab_attendance` WRITE;
/*!40000 ALTER TABLE `lab_attendance` DISABLE KEYS */;
/*!40000 ALTER TABLE `lab_attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lab_checkouts`
--

DROP TABLE IF EXISTS `lab_checkouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_checkouts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `equipment_id` int(10) unsigned NOT NULL,
  `borrower_id` int(10) unsigned NOT NULL,
  `borrower_name` varchar(120) NOT NULL,
  `checkout_date` datetime NOT NULL,
  `expected_return` datetime DEFAULT NULL,
  `actual_return` datetime DEFAULT NULL,
  `status` enum('checked_out','returned','overdue') NOT NULL DEFAULT 'checked_out',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_checkouts`
--

LOCK TABLES `lab_checkouts` WRITE;
/*!40000 ALTER TABLE `lab_checkouts` DISABLE KEYS */;
/*!40000 ALTER TABLE `lab_checkouts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lab_chemical_inventory`
--

DROP TABLE IF EXISTS `lab_chemical_inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_chemical_inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `chemical_name` varchar(255) DEFAULT NULL,
  `chemical_formula` varchar(100) DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `storage_location` varchar(255) DEFAULT NULL,
  `hazard_level` varchar(50) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `reorder_level` decimal(10,2) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'in_stock',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_chemical_inventory`
--

LOCK TABLES `lab_chemical_inventory` WRITE;
/*!40000 ALTER TABLE `lab_chemical_inventory` DISABLE KEYS */;
/*!40000 ALTER TABLE `lab_chemical_inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lab_consumables`
--

DROP TABLE IF EXISTS `lab_consumables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_consumables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_name` varchar(200) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT 0,
  `unit` varchar(50) DEFAULT 'piece',
  `min_stock_level` int(11) DEFAULT 5,
  `unit_cost` decimal(15,2) DEFAULT 0.00,
  `supplier` varchar(200) DEFAULT NULL,
  `last_ordered_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_consumables`
--

LOCK TABLES `lab_consumables` WRITE;
/*!40000 ALTER TABLE `lab_consumables` DISABLE KEYS */;
/*!40000 ALTER TABLE `lab_consumables` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lab_demonstrations`
--

DROP TABLE IF EXISTS `lab_demonstrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_demonstrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` int(10) unsigned DEFAULT NULL,
  `skill_name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `instructor_id` int(10) unsigned DEFAULT NULL,
  `demo_date` date NOT NULL,
  `students_count` int(10) unsigned DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_demonstrations`
--

LOCK TABLES `lab_demonstrations` WRITE;
/*!40000 ALTER TABLE `lab_demonstrations` DISABLE KEYS */;
/*!40000 ALTER TABLE `lab_demonstrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lab_equipment`
--

DROP TABLE IF EXISTS `lab_equipment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_equipment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equipment_code` varchar(50) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `available_quantity` int(11) DEFAULT 1,
  `condition_status` varchar(50) DEFAULT 'Good',
  `location` varchar(200) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `purchase_cost` decimal(15,2) DEFAULT NULL,
  `supplier` varchar(200) DEFAULT NULL,
  `last_maintenance_date` date DEFAULT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `status` enum('active','maintenance','retired') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_equip_code` (`equipment_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_equipment`
--

LOCK TABLES `lab_equipment` WRITE;
/*!40000 ALTER TABLE `lab_equipment` DISABLE KEYS */;
/*!40000 ALTER TABLE `lab_equipment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lab_equipment_checkout`
--

DROP TABLE IF EXISTS `lab_equipment_checkout`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_equipment_checkout` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equipment_id` int(11) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checkout_date` datetime DEFAULT current_timestamp(),
  `expected_return_date` datetime DEFAULT NULL,
  `actual_return_date` datetime DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `purpose` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'checked_out',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_lec_equipment` (`equipment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_equipment_checkout`
--

LOCK TABLES `lab_equipment_checkout` WRITE;
/*!40000 ALTER TABLE `lab_equipment_checkout` DISABLE KEYS */;
/*!40000 ALTER TABLE `lab_equipment_checkout` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lab_equipment_checkouts`
--

DROP TABLE IF EXISTS `lab_equipment_checkouts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_equipment_checkouts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equipment_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `checkout_date` datetime DEFAULT current_timestamp(),
  `expected_return_date` datetime DEFAULT NULL,
  `actual_return_date` datetime DEFAULT NULL,
  `quantity_checked_out` int(11) DEFAULT 1,
  `quantity_returned` int(11) DEFAULT 0,
  `purpose` text DEFAULT NULL,
  `status` enum('checked_out','returned','overdue') DEFAULT 'checked_out',
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_checkout_equipment` (`equipment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_equipment_checkouts`
--

LOCK TABLES `lab_equipment_checkouts` WRITE;
/*!40000 ALTER TABLE `lab_equipment_checkouts` DISABLE KEYS */;
/*!40000 ALTER TABLE `lab_equipment_checkouts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lab_equipment_maintenance`
--

DROP TABLE IF EXISTS `lab_equipment_maintenance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_equipment_maintenance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equipment_id` int(11) DEFAULT NULL,
  `maintenance_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `maintenance_date` date DEFAULT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `cost` decimal(15,2) DEFAULT NULL,
  `performed_by` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_equipment_maintenance`
--

LOCK TABLES `lab_equipment_maintenance` WRITE;
/*!40000 ALTER TABLE `lab_equipment_maintenance` DISABLE KEYS */;
/*!40000 ALTER TABLE `lab_equipment_maintenance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lab_experiments`
--

DROP TABLE IF EXISTS `lab_experiments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_experiments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `experiment_name` varchar(255) DEFAULT NULL,
  `experiment_code` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `instructor_id` int(11) DEFAULT NULL,
  `scheduled_date` date DEFAULT NULL,
  `duration_hours` decimal(4,1) DEFAULT NULL,
  `max_students` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_experiments`
--

LOCK TABLES `lab_experiments` WRITE;
/*!40000 ALTER TABLE `lab_experiments` DISABLE KEYS */;
/*!40000 ALTER TABLE `lab_experiments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lab_id_card_requests`
--

DROP TABLE IF EXISTS `lab_id_card_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_id_card_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `request_type` varchar(50) DEFAULT 'new',
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','printed','rejected') DEFAULT 'pending',
  `requested_by` int(11) DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_id_card_requests`
--

LOCK TABLES `lab_id_card_requests` WRITE;
/*!40000 ALTER TABLE `lab_id_card_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `lab_id_card_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lab_incidents`
--

DROP TABLE IF EXISTS `lab_incidents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_incidents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `incident_date` date NOT NULL,
  `incident_time` time DEFAULT NULL,
  `reported_by` int(11) DEFAULT NULL,
  `incident_type` varchar(100) DEFAULT NULL,
  `severity` varchar(50) DEFAULT 'Medium',
  `description` text DEFAULT NULL,
  `equipment_involved` varchar(200) DEFAULT NULL,
  `student_involved` varchar(200) DEFAULT NULL,
  `action_taken` text DEFAULT NULL,
  `status` enum('open','investigating','resolved','closed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_incidents`
--

LOCK TABLES `lab_incidents` WRITE;
/*!40000 ALTER TABLE `lab_incidents` DISABLE KEYS */;
/*!40000 ALTER TABLE `lab_incidents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lab_inventory`
--

DROP TABLE IF EXISTS `lab_inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_name` varchar(255) DEFAULT NULL,
  `item_code` varchar(50) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT 0,
  `unit` varchar(50) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `reorder_level` int(11) DEFAULT 0,
  `status` varchar(20) DEFAULT 'in_stock',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_inventory`
--

LOCK TABLES `lab_inventory` WRITE;
/*!40000 ALTER TABLE `lab_inventory` DISABLE KEYS */;
/*!40000 ALTER TABLE `lab_inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lab_practical_sessions`
--

DROP TABLE IF EXISTS `lab_practical_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_practical_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_code` varchar(50) NOT NULL,
  `title` varchar(200) NOT NULL,
  `course_name` varchar(200) DEFAULT NULL,
  `instructor_name` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `instructor` varchar(200) DEFAULT NULL,
  `program` varchar(100) DEFAULT NULL,
  `year_level` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `session_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `max_students` int(11) DEFAULT 30,
  `status` enum('scheduled','ongoing','completed','cancelled') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_session_code` (`session_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_practical_sessions`
--

LOCK TABLES `lab_practical_sessions` WRITE;
/*!40000 ALTER TABLE `lab_practical_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `lab_practical_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lab_printing_jobs`
--

DROP TABLE IF EXISTS `lab_printing_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_printing_jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `document_name` varchar(200) DEFAULT NULL,
  `pages` int(11) DEFAULT 1,
  `copies` int(11) DEFAULT 1,
  `cost` decimal(10,2) DEFAULT 0.00,
  `status` enum('pending','printing','completed','cancelled') DEFAULT 'pending',
  `requested_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_printing_jobs`
--

LOCK TABLES `lab_printing_jobs` WRITE;
/*!40000 ALTER TABLE `lab_printing_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `lab_printing_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lab_safety_records`
--

DROP TABLE IF EXISTS `lab_safety_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_safety_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `record_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `hazard_level` varchar(50) DEFAULT NULL,
  `reported_by` int(11) DEFAULT NULL,
  `action_taken` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'open',
  `inspection_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_safety_records`
--

LOCK TABLES `lab_safety_records` WRITE;
/*!40000 ALTER TABLE `lab_safety_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `lab_safety_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lab_sessions`
--

DROP TABLE IF EXISTS `lab_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_sessions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `session_name` varchar(200) NOT NULL,
  `instructor_id` int(10) unsigned DEFAULT NULL,
  `instructor_name` varchar(120) DEFAULT NULL,
  `scheduled_date` date NOT NULL,
  `scheduled_time` time DEFAULT NULL,
  `duration_minutes` int(10) unsigned DEFAULT 60,
  `max_students` int(10) unsigned DEFAULT 30,
  `room` varchar(50) DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_sessions`
--

LOCK TABLES `lab_sessions` WRITE;
/*!40000 ALTER TABLE `lab_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `lab_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lab_skills_demonstrations`
--

DROP TABLE IF EXISTS `lab_skills_demonstrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_skills_demonstrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `skill_name` varchar(200) NOT NULL,
  `skill_category` varchar(100) DEFAULT NULL,
  `instructor` varchar(200) DEFAULT NULL,
  `date_demonstrated` date DEFAULT NULL,
  `competency` varchar(50) DEFAULT 'Beginner',
  `attempt_number` int(11) DEFAULT 1,
  `notes` text DEFAULT NULL,
  `next_review_date` date DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_skills_demonstrations`
--

LOCK TABLES `lab_skills_demonstrations` WRITE;
/*!40000 ALTER TABLE `lab_skills_demonstrations` DISABLE KEYS */;
/*!40000 ALTER TABLE `lab_skills_demonstrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lab_skills_sessions`
--

DROP TABLE IF EXISTS `lab_skills_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_skills_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_name` varchar(255) DEFAULT NULL,
  `skill_area` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `instructor_id` int(11) DEFAULT NULL,
  `scheduled_date` date DEFAULT NULL,
  `duration_hours` decimal(4,1) DEFAULT NULL,
  `max_participants` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_skills_sessions`
--

LOCK TABLES `lab_skills_sessions` WRITE;
/*!40000 ALTER TABLE `lab_skills_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `lab_skills_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `late_payment_settings`
--

DROP TABLE IF EXISTS `late_payment_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `late_payment_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `late_payment_settings`
--

LOCK TABLES `late_payment_settings` WRITE;
/*!40000 ALTER TABLE `late_payment_settings` DISABLE KEYS */;
INSERT INTO `late_payment_settings` VALUES (1,'grace_period_days','15',NULL,'2026-07-03 05:16:47'),(2,'late_fee_percentage','5',NULL,'2026-07-03 05:16:47'),(3,'late_fee_fixed','20000',NULL,'2026-07-03 05:16:47'),(4,'max_late_fee','100000',NULL,'2026-07-03 05:16:47');
/*!40000 ALTER TABLE `late_payment_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_balance`
--

DROP TABLE IF EXISTS `leave_balance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_balance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `leave_type_id` int(11) DEFAULT NULL,
  `year` int(11) NOT NULL,
  `total_days` int(11) DEFAULT 30,
  `used_days` int(11) DEFAULT 0,
  `remaining_days` int(11) DEFAULT 30,
  `balance_days` int(11) DEFAULT 30,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_lb_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_balance`
--

LOCK TABLES `leave_balance` WRITE;
/*!40000 ALTER TABLE `leave_balance` DISABLE KEYS */;
/*!40000 ALTER TABLE `leave_balance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_balances`
--

DROP TABLE IF EXISTS `leave_balances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_balances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `leave_type_id` int(11) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `total_days` int(11) DEFAULT 0,
  `used_days` int(11) DEFAULT 0,
  `remaining_days` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_staff_id` (`staff_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1639 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_balances`
--

LOCK TABLES `leave_balances` WRITE;
/*!40000 ALTER TABLE `leave_balances` DISABLE KEYS */;
INSERT INTO `leave_balances` VALUES (1,7,1,2025,30,0,30,'2026-07-02 08:09:39'),(2,7,2,2025,14,0,14,'2026-07-02 08:09:39'),(3,7,3,2025,90,0,90,'2026-07-02 08:09:39'),(4,7,4,2025,7,0,7,'2026-07-02 08:09:39'),(5,7,5,2025,5,0,5,'2026-07-02 08:09:39'),(6,7,6,2025,30,0,30,'2026-07-02 08:09:39'),(7,7,7,2025,10,0,10,'2026-07-02 08:09:39'),(8,7,8,2025,28,0,28,'2026-07-02 08:09:39'),(9,7,9,2025,15,0,15,'2026-07-02 08:09:39'),(10,7,10,2025,30,0,30,'2026-07-02 08:09:39'),(11,7,11,2025,90,0,90,'2026-07-02 08:09:39'),(12,7,12,2025,14,0,14,'2026-07-02 08:09:39'),(13,7,13,2025,10,0,10,'2026-07-02 08:09:39'),(14,7,14,2025,30,0,30,'2026-07-02 08:09:39'),(15,51,1,2025,30,0,30,'2026-07-02 08:09:39'),(16,51,2,2025,14,0,14,'2026-07-02 08:09:39'),(17,51,3,2025,90,0,90,'2026-07-02 08:09:39'),(18,51,4,2025,7,0,7,'2026-07-02 08:09:39'),(19,51,5,2025,5,0,5,'2026-07-02 08:09:39'),(20,51,6,2025,30,0,30,'2026-07-02 08:09:39'),(21,51,7,2025,10,0,10,'2026-07-02 08:09:39'),(22,51,8,2025,28,0,28,'2026-07-02 08:09:39'),(23,51,9,2025,15,0,15,'2026-07-02 08:09:39'),(24,51,10,2025,30,0,30,'2026-07-02 08:09:39'),(25,51,11,2025,90,0,90,'2026-07-02 08:09:39'),(26,51,12,2025,14,0,14,'2026-07-02 08:09:39'),(27,51,13,2025,10,0,10,'2026-07-02 08:09:39'),(28,51,14,2025,30,0,30,'2026-07-02 08:09:39'),(29,2,1,2025,30,0,30,'2026-07-02 08:09:39'),(30,2,2,2025,14,0,14,'2026-07-02 08:09:39'),(31,2,3,2025,90,0,90,'2026-07-02 08:09:39'),(32,2,4,2025,7,0,7,'2026-07-02 08:09:39'),(33,2,5,2025,5,0,5,'2026-07-02 08:09:39'),(34,2,6,2025,30,0,30,'2026-07-02 08:09:39'),(35,2,7,2025,10,0,10,'2026-07-02 08:09:39'),(36,2,8,2025,28,0,28,'2026-07-02 08:09:39'),(37,2,9,2025,15,0,15,'2026-07-02 08:09:39'),(38,2,10,2025,30,0,30,'2026-07-02 08:09:39'),(39,2,11,2025,90,0,90,'2026-07-02 08:09:39'),(40,2,12,2025,14,0,14,'2026-07-02 08:09:39'),(41,2,13,2025,10,0,10,'2026-07-02 08:09:39'),(42,2,14,2025,30,0,30,'2026-07-02 08:09:39'),(43,22,1,2025,30,0,30,'2026-07-02 08:09:39'),(44,22,2,2025,14,0,14,'2026-07-02 08:09:39'),(45,22,3,2025,90,0,90,'2026-07-02 08:09:39'),(46,22,4,2025,7,0,7,'2026-07-02 08:09:39'),(47,22,5,2025,5,0,5,'2026-07-02 08:09:39'),(48,22,6,2025,30,0,30,'2026-07-02 08:09:39'),(49,22,7,2025,10,0,10,'2026-07-02 08:09:39'),(50,22,8,2025,28,0,28,'2026-07-02 08:09:39'),(51,22,9,2025,15,0,15,'2026-07-02 08:09:39'),(52,22,10,2025,30,0,30,'2026-07-02 08:09:39'),(53,22,11,2025,90,0,90,'2026-07-02 08:09:39'),(54,22,12,2025,14,0,14,'2026-07-02 08:09:39'),(55,22,13,2025,10,0,10,'2026-07-02 08:09:39'),(56,22,14,2025,30,0,30,'2026-07-02 08:09:39'),(57,6,1,2025,30,0,30,'2026-07-02 08:09:39'),(58,6,2,2025,14,0,14,'2026-07-02 08:09:39'),(59,6,3,2025,90,0,90,'2026-07-02 08:09:39'),(60,6,4,2025,7,0,7,'2026-07-02 08:09:39'),(61,6,5,2025,5,0,5,'2026-07-02 08:09:39'),(62,6,6,2025,30,0,30,'2026-07-02 08:09:39'),(63,6,7,2025,10,0,10,'2026-07-02 08:09:39'),(64,6,8,2025,28,0,28,'2026-07-02 08:09:39'),(65,6,9,2025,15,0,15,'2026-07-02 08:09:39'),(66,6,10,2025,30,0,30,'2026-07-02 08:09:39'),(67,6,11,2025,90,0,90,'2026-07-02 08:09:39'),(68,6,12,2025,14,0,14,'2026-07-02 08:09:39'),(69,6,13,2025,10,0,10,'2026-07-02 08:09:39'),(70,6,14,2025,30,0,30,'2026-07-02 08:09:39'),(71,3,1,2025,30,0,30,'2026-07-02 08:09:39'),(72,3,2,2025,14,0,14,'2026-07-02 08:09:39'),(73,3,3,2025,90,0,90,'2026-07-02 08:09:39'),(74,3,4,2025,7,0,7,'2026-07-02 08:09:39'),(75,3,5,2025,5,0,5,'2026-07-02 08:09:39'),(76,3,6,2025,30,0,30,'2026-07-02 08:09:39'),(77,3,7,2025,10,0,10,'2026-07-02 08:09:39'),(78,3,8,2025,28,0,28,'2026-07-02 08:09:39'),(79,3,9,2025,15,0,15,'2026-07-02 08:09:39'),(80,3,10,2025,30,0,30,'2026-07-02 08:09:39'),(81,3,11,2025,90,0,90,'2026-07-02 08:09:39'),(82,3,12,2025,14,0,14,'2026-07-02 08:09:39'),(83,3,13,2025,10,0,10,'2026-07-02 08:09:39'),(84,3,14,2025,30,0,30,'2026-07-02 08:09:39'),(85,24,1,2025,30,0,30,'2026-07-02 08:09:39'),(86,24,2,2025,14,0,14,'2026-07-02 08:09:39'),(87,24,3,2025,90,0,90,'2026-07-02 08:09:39'),(88,24,4,2025,7,0,7,'2026-07-02 08:09:39'),(89,24,5,2025,5,0,5,'2026-07-02 08:09:39'),(90,24,6,2025,30,0,30,'2026-07-02 08:09:39'),(91,24,7,2025,10,0,10,'2026-07-02 08:09:39'),(92,24,8,2025,28,0,28,'2026-07-02 08:09:39'),(93,24,9,2025,15,0,15,'2026-07-02 08:09:39'),(94,24,10,2025,30,0,30,'2026-07-02 08:09:39'),(95,24,11,2025,90,0,90,'2026-07-02 08:09:39'),(96,24,12,2025,14,0,14,'2026-07-02 08:09:39'),(97,24,13,2025,10,0,10,'2026-07-02 08:09:39'),(98,24,14,2025,30,0,30,'2026-07-02 08:09:39'),(99,4,1,2025,30,0,30,'2026-07-02 08:09:39'),(100,4,2,2025,14,0,14,'2026-07-02 08:09:39'),(101,4,3,2025,90,0,90,'2026-07-02 08:09:39'),(102,4,4,2025,7,0,7,'2026-07-02 08:09:39'),(103,4,5,2025,5,0,5,'2026-07-02 08:09:39'),(104,4,6,2025,30,0,30,'2026-07-02 08:09:39'),(105,4,7,2025,10,0,10,'2026-07-02 08:09:39'),(106,4,8,2025,28,0,28,'2026-07-02 08:09:39'),(107,4,9,2025,15,0,15,'2026-07-02 08:09:39'),(108,4,10,2025,30,0,30,'2026-07-02 08:09:39'),(109,4,11,2025,90,0,90,'2026-07-02 08:09:39'),(110,4,12,2025,14,0,14,'2026-07-02 08:09:39'),(111,4,13,2025,10,0,10,'2026-07-02 08:09:39'),(112,4,14,2025,30,0,30,'2026-07-02 08:09:39'),(113,1,1,2025,30,0,30,'2026-07-02 08:09:39'),(114,1,2,2025,14,0,14,'2026-07-02 08:09:39'),(115,1,3,2025,90,0,90,'2026-07-02 08:09:39'),(116,1,4,2025,7,0,7,'2026-07-02 08:09:39'),(117,1,5,2025,5,0,5,'2026-07-02 08:09:39'),(118,1,6,2025,30,0,30,'2026-07-02 08:09:39'),(119,1,7,2025,10,0,10,'2026-07-02 08:09:39'),(120,1,8,2025,28,0,28,'2026-07-02 08:09:39'),(121,1,9,2025,15,0,15,'2026-07-02 08:09:39'),(122,1,10,2025,30,0,30,'2026-07-02 08:09:39'),(123,1,11,2025,90,0,90,'2026-07-02 08:09:39'),(124,1,12,2025,14,0,14,'2026-07-02 08:09:39'),(125,1,13,2025,10,0,10,'2026-07-02 08:09:39'),(126,1,14,2025,30,0,30,'2026-07-02 08:09:39'),(127,23,1,2025,30,0,30,'2026-07-02 08:09:39'),(128,23,2,2025,14,0,14,'2026-07-02 08:09:39'),(129,23,3,2025,90,0,90,'2026-07-02 08:09:39'),(130,23,4,2025,7,0,7,'2026-07-02 08:09:39'),(131,23,5,2025,5,0,5,'2026-07-02 08:09:39'),(132,23,6,2025,30,0,30,'2026-07-02 08:09:39'),(133,23,7,2025,10,0,10,'2026-07-02 08:09:39'),(134,23,8,2025,28,0,28,'2026-07-02 08:09:39'),(135,23,9,2025,15,0,15,'2026-07-02 08:09:39'),(136,23,10,2025,30,0,30,'2026-07-02 08:09:39'),(137,23,11,2025,90,0,90,'2026-07-02 08:09:39'),(138,23,12,2025,14,0,14,'2026-07-02 08:09:39'),(139,23,13,2025,10,0,10,'2026-07-02 08:09:39'),(140,23,14,2025,30,0,30,'2026-07-02 08:09:39'),(141,18,1,2025,30,0,30,'2026-07-02 08:09:39'),(142,18,2,2025,14,0,14,'2026-07-02 08:09:39'),(143,18,3,2025,90,0,90,'2026-07-02 08:09:39'),(144,18,4,2025,7,0,7,'2026-07-02 08:09:39'),(145,18,5,2025,5,0,5,'2026-07-02 08:09:39'),(146,18,6,2025,30,0,30,'2026-07-02 08:09:39'),(147,18,7,2025,10,0,10,'2026-07-02 08:09:39'),(148,18,8,2025,28,0,28,'2026-07-02 08:09:39'),(149,18,9,2025,15,0,15,'2026-07-02 08:09:39'),(150,18,10,2025,30,0,30,'2026-07-02 08:09:39'),(151,18,11,2025,90,0,90,'2026-07-02 08:09:39'),(152,18,12,2025,14,0,14,'2026-07-02 08:09:39'),(153,18,13,2025,10,0,10,'2026-07-02 08:09:39'),(154,18,14,2025,30,0,30,'2026-07-02 08:09:39'),(155,21,1,2025,30,0,30,'2026-07-02 08:09:39'),(156,21,2,2025,14,0,14,'2026-07-02 08:09:39'),(157,21,3,2025,90,0,90,'2026-07-02 08:09:39'),(158,21,4,2025,7,0,7,'2026-07-02 08:09:39'),(159,21,5,2025,5,0,5,'2026-07-02 08:09:39'),(160,21,6,2025,30,0,30,'2026-07-02 08:09:39'),(161,21,7,2025,10,0,10,'2026-07-02 08:09:39'),(162,21,8,2025,28,0,28,'2026-07-02 08:09:39'),(163,21,9,2025,15,0,15,'2026-07-02 08:09:39'),(164,21,10,2025,30,0,30,'2026-07-02 08:09:39'),(165,21,11,2025,90,0,90,'2026-07-02 08:09:39'),(166,21,12,2025,14,0,14,'2026-07-02 08:09:39'),(167,21,13,2025,10,0,10,'2026-07-02 08:09:39'),(168,21,14,2025,30,0,30,'2026-07-02 08:09:39'),(169,12,1,2025,30,0,30,'2026-07-02 08:09:39'),(170,12,2,2025,14,0,14,'2026-07-02 08:09:39'),(171,12,3,2025,90,0,90,'2026-07-02 08:09:39'),(172,12,4,2025,7,0,7,'2026-07-02 08:09:39'),(173,12,5,2025,5,0,5,'2026-07-02 08:09:39'),(174,12,6,2025,30,0,30,'2026-07-02 08:09:39'),(175,12,7,2025,10,0,10,'2026-07-02 08:09:39'),(176,12,8,2025,28,0,28,'2026-07-02 08:09:39'),(177,12,9,2025,15,0,15,'2026-07-02 08:09:39'),(178,12,10,2025,30,0,30,'2026-07-02 08:09:39'),(179,12,11,2025,90,0,90,'2026-07-02 08:09:39'),(180,12,12,2025,14,0,14,'2026-07-02 08:09:39'),(181,12,13,2025,10,0,10,'2026-07-02 08:09:39'),(182,12,14,2025,30,0,30,'2026-07-02 08:09:39'),(183,11,1,2025,30,0,30,'2026-07-02 08:09:39'),(184,11,2,2025,14,0,14,'2026-07-02 08:09:39'),(185,11,3,2025,90,0,90,'2026-07-02 08:09:39'),(186,11,4,2025,7,0,7,'2026-07-02 08:09:39'),(187,11,5,2025,5,0,5,'2026-07-02 08:09:39'),(188,11,6,2025,30,0,30,'2026-07-02 08:09:39'),(189,11,7,2025,10,0,10,'2026-07-02 08:09:39'),(190,11,8,2025,28,0,28,'2026-07-02 08:09:39'),(191,11,9,2025,15,0,15,'2026-07-02 08:09:39'),(192,11,10,2025,30,0,30,'2026-07-02 08:09:39'),(193,11,11,2025,90,0,90,'2026-07-02 08:09:39'),(194,11,12,2025,14,0,14,'2026-07-02 08:09:39'),(195,11,13,2025,10,0,10,'2026-07-02 08:09:39'),(196,11,14,2025,30,0,30,'2026-07-02 08:09:39'),(197,8,1,2025,30,0,30,'2026-07-02 08:09:39'),(198,8,2,2025,14,0,14,'2026-07-02 08:09:39'),(199,8,3,2025,90,0,90,'2026-07-02 08:09:39'),(200,8,4,2025,7,0,7,'2026-07-02 08:09:39'),(201,8,5,2025,5,0,5,'2026-07-02 08:09:39'),(202,8,6,2025,30,0,30,'2026-07-02 08:09:39'),(203,8,7,2025,10,0,10,'2026-07-02 08:09:39'),(204,8,8,2025,28,0,28,'2026-07-02 08:09:39'),(205,8,9,2025,15,0,15,'2026-07-02 08:09:39'),(206,8,10,2025,30,0,30,'2026-07-02 08:09:39'),(207,8,11,2025,90,0,90,'2026-07-02 08:09:39'),(208,8,12,2025,14,0,14,'2026-07-02 08:09:39'),(209,8,13,2025,10,0,10,'2026-07-02 08:09:39'),(210,8,14,2025,30,0,30,'2026-07-02 08:09:39'),(211,14,1,2025,30,0,30,'2026-07-02 08:09:39'),(212,14,2,2025,14,0,14,'2026-07-02 08:09:39'),(213,14,3,2025,90,0,90,'2026-07-02 08:09:39'),(214,14,4,2025,7,0,7,'2026-07-02 08:09:39'),(215,14,5,2025,5,0,5,'2026-07-02 08:09:39'),(216,14,6,2025,30,0,30,'2026-07-02 08:09:39'),(217,14,7,2025,10,0,10,'2026-07-02 08:09:39'),(218,14,8,2025,28,0,28,'2026-07-02 08:09:39'),(219,14,9,2025,15,0,15,'2026-07-02 08:09:39'),(220,14,10,2025,30,0,30,'2026-07-02 08:09:39'),(221,14,11,2025,90,0,90,'2026-07-02 08:09:39'),(222,14,12,2025,14,0,14,'2026-07-02 08:09:39'),(223,14,13,2025,10,0,10,'2026-07-02 08:09:39'),(224,14,14,2025,30,0,30,'2026-07-02 08:09:39'),(225,15,1,2025,30,0,30,'2026-07-02 08:09:39'),(226,15,2,2025,14,0,14,'2026-07-02 08:09:39'),(227,15,3,2025,90,0,90,'2026-07-02 08:09:39'),(228,15,4,2025,7,0,7,'2026-07-02 08:09:39'),(229,15,5,2025,5,0,5,'2026-07-02 08:09:39'),(230,15,6,2025,30,0,30,'2026-07-02 08:09:39'),(231,15,7,2025,10,0,10,'2026-07-02 08:09:39'),(232,15,8,2025,28,0,28,'2026-07-02 08:09:39'),(233,15,9,2025,15,0,15,'2026-07-02 08:09:39'),(234,15,10,2025,30,0,30,'2026-07-02 08:09:39'),(235,15,11,2025,90,0,90,'2026-07-02 08:09:39'),(236,15,12,2025,14,0,14,'2026-07-02 08:09:39'),(237,15,13,2025,10,0,10,'2026-07-02 08:09:39'),(238,15,14,2025,30,0,30,'2026-07-02 08:09:39'),(239,25,1,2025,30,0,30,'2026-07-02 08:09:39'),(240,25,2,2025,14,0,14,'2026-07-02 08:09:39'),(241,25,3,2025,90,0,90,'2026-07-02 08:09:39'),(242,25,4,2025,7,0,7,'2026-07-02 08:09:39'),(243,25,5,2025,5,0,5,'2026-07-02 08:09:39'),(244,25,6,2025,30,0,30,'2026-07-02 08:09:39'),(245,25,7,2025,10,0,10,'2026-07-02 08:09:39'),(246,25,8,2025,28,0,28,'2026-07-02 08:09:39'),(247,25,9,2025,15,0,15,'2026-07-02 08:09:39'),(248,25,10,2025,30,0,30,'2026-07-02 08:09:39'),(249,25,11,2025,90,0,90,'2026-07-02 08:09:39'),(250,25,12,2025,14,0,14,'2026-07-02 08:09:39'),(251,25,13,2025,10,0,10,'2026-07-02 08:09:39'),(252,25,14,2025,30,0,30,'2026-07-02 08:09:39'),(253,10,1,2025,30,0,30,'2026-07-02 08:09:39'),(254,10,2,2025,14,0,14,'2026-07-02 08:09:39'),(255,10,3,2025,90,0,90,'2026-07-02 08:09:39'),(256,10,4,2025,7,0,7,'2026-07-02 08:09:39'),(257,10,5,2025,5,0,5,'2026-07-02 08:09:39'),(258,10,6,2025,30,0,30,'2026-07-02 08:09:39'),(259,10,7,2025,10,0,10,'2026-07-02 08:09:39'),(260,10,8,2025,28,0,28,'2026-07-02 08:09:39'),(261,10,9,2025,15,0,15,'2026-07-02 08:09:39'),(262,10,10,2025,30,0,30,'2026-07-02 08:09:39'),(263,10,11,2025,90,0,90,'2026-07-02 08:09:39'),(264,10,12,2025,14,0,14,'2026-07-02 08:09:39'),(265,10,13,2025,10,0,10,'2026-07-02 08:09:39'),(266,10,14,2025,30,0,30,'2026-07-02 08:09:39'),(267,5,1,2025,30,0,30,'2026-07-02 08:09:39'),(268,5,2,2025,14,0,14,'2026-07-02 08:09:39'),(269,5,3,2025,90,0,90,'2026-07-02 08:09:39'),(270,5,4,2025,7,0,7,'2026-07-02 08:09:39'),(271,5,5,2025,5,0,5,'2026-07-02 08:09:39'),(272,5,6,2025,30,0,30,'2026-07-02 08:09:39'),(273,5,7,2025,10,0,10,'2026-07-02 08:09:39'),(274,5,8,2025,28,0,28,'2026-07-02 08:09:39'),(275,5,9,2025,15,0,15,'2026-07-02 08:09:39'),(276,5,10,2025,30,0,30,'2026-07-02 08:09:39'),(277,5,11,2025,90,0,90,'2026-07-02 08:09:39'),(278,5,12,2025,14,0,14,'2026-07-02 08:09:39'),(279,5,13,2025,10,0,10,'2026-07-02 08:09:39'),(280,5,14,2025,30,0,30,'2026-07-02 08:09:39'),(281,9,1,2025,30,0,30,'2026-07-02 08:09:39'),(282,9,2,2025,14,0,14,'2026-07-02 08:09:39'),(283,9,3,2025,90,0,90,'2026-07-02 08:09:39'),(284,9,4,2025,7,0,7,'2026-07-02 08:09:39'),(285,9,5,2025,5,0,5,'2026-07-02 08:09:39'),(286,9,6,2025,30,0,30,'2026-07-02 08:09:39'),(287,9,7,2025,10,0,10,'2026-07-02 08:09:39'),(288,9,8,2025,28,0,28,'2026-07-02 08:09:39'),(289,9,9,2025,15,0,15,'2026-07-02 08:09:39'),(290,9,10,2025,30,0,30,'2026-07-02 08:09:39'),(291,9,11,2025,90,0,90,'2026-07-02 08:09:39'),(292,9,12,2025,14,0,14,'2026-07-02 08:09:39'),(293,9,13,2025,10,0,10,'2026-07-02 08:09:39'),(294,9,14,2025,30,0,30,'2026-07-02 08:09:39'),(295,19,1,2025,30,0,30,'2026-07-02 08:09:39'),(296,19,2,2025,14,0,14,'2026-07-02 08:09:39'),(297,19,3,2025,90,0,90,'2026-07-02 08:09:39'),(298,19,4,2025,7,0,7,'2026-07-02 08:09:39'),(299,19,5,2025,5,0,5,'2026-07-02 08:09:39'),(300,19,6,2025,30,0,30,'2026-07-02 08:09:39'),(301,19,7,2025,10,0,10,'2026-07-02 08:09:39'),(302,19,8,2025,28,0,28,'2026-07-02 08:09:39'),(303,19,9,2025,15,0,15,'2026-07-02 08:09:39'),(304,19,10,2025,30,0,30,'2026-07-02 08:09:39'),(305,19,11,2025,90,0,90,'2026-07-02 08:09:39'),(306,19,12,2025,14,0,14,'2026-07-02 08:09:39'),(307,19,13,2025,10,0,10,'2026-07-02 08:09:39'),(308,19,14,2025,30,0,30,'2026-07-02 08:09:39'),(309,13,1,2025,30,0,30,'2026-07-02 08:09:39'),(310,13,2,2025,14,0,14,'2026-07-02 08:09:39'),(311,13,3,2025,90,0,90,'2026-07-02 08:09:39'),(312,13,4,2025,7,0,7,'2026-07-02 08:09:39'),(313,13,5,2025,5,0,5,'2026-07-02 08:09:39'),(314,13,6,2025,30,0,30,'2026-07-02 08:09:39'),(315,13,7,2025,10,0,10,'2026-07-02 08:09:39'),(316,13,8,2025,28,0,28,'2026-07-02 08:09:39'),(317,13,9,2025,15,0,15,'2026-07-02 08:09:39'),(318,13,10,2025,30,0,30,'2026-07-02 08:09:39'),(319,13,11,2025,90,0,90,'2026-07-02 08:09:39'),(320,13,12,2025,14,0,14,'2026-07-02 08:09:39'),(321,13,13,2025,10,0,10,'2026-07-02 08:09:39'),(322,13,14,2025,30,0,30,'2026-07-02 08:09:39'),(323,17,1,2025,30,0,30,'2026-07-02 08:09:39'),(324,17,2,2025,14,0,14,'2026-07-02 08:09:39'),(325,17,3,2025,90,0,90,'2026-07-02 08:09:39'),(326,17,4,2025,7,0,7,'2026-07-02 08:09:39'),(327,17,5,2025,5,0,5,'2026-07-02 08:09:39'),(328,17,6,2025,30,0,30,'2026-07-02 08:09:39'),(329,17,7,2025,10,0,10,'2026-07-02 08:09:39'),(330,17,8,2025,28,0,28,'2026-07-02 08:09:39'),(331,17,9,2025,15,0,15,'2026-07-02 08:09:39'),(332,17,10,2025,30,0,30,'2026-07-02 08:09:39'),(333,17,11,2025,90,0,90,'2026-07-02 08:09:39'),(334,17,12,2025,14,0,14,'2026-07-02 08:09:39'),(335,17,13,2025,10,0,10,'2026-07-02 08:09:39'),(336,17,14,2025,30,0,30,'2026-07-02 08:09:39'),(337,20,1,2025,30,0,30,'2026-07-02 08:09:39'),(338,20,2,2025,14,0,14,'2026-07-02 08:09:39'),(339,20,3,2025,90,0,90,'2026-07-02 08:09:39'),(340,20,4,2025,7,0,7,'2026-07-02 08:09:39'),(341,20,5,2025,5,0,5,'2026-07-02 08:09:39'),(342,20,6,2025,30,0,30,'2026-07-02 08:09:39'),(343,20,7,2025,10,0,10,'2026-07-02 08:09:39'),(344,20,8,2025,28,0,28,'2026-07-02 08:09:39'),(345,20,9,2025,15,0,15,'2026-07-02 08:09:39'),(346,20,10,2025,30,0,30,'2026-07-02 08:09:39'),(347,20,11,2025,90,0,90,'2026-07-02 08:09:39'),(348,20,12,2025,14,0,14,'2026-07-02 08:09:39'),(349,20,13,2025,10,0,10,'2026-07-02 08:09:39'),(350,20,14,2025,30,0,30,'2026-07-02 08:09:39'),(351,16,1,2025,30,0,30,'2026-07-02 08:09:39'),(352,16,2,2025,14,0,14,'2026-07-02 08:09:39'),(353,16,3,2025,90,0,90,'2026-07-02 08:09:39'),(354,16,4,2025,7,0,7,'2026-07-02 08:09:39'),(355,16,5,2025,5,0,5,'2026-07-02 08:09:39'),(356,16,6,2025,30,0,30,'2026-07-02 08:09:39'),(357,16,7,2025,10,0,10,'2026-07-02 08:09:39'),(358,16,8,2025,28,0,28,'2026-07-02 08:09:39'),(359,16,9,2025,15,0,15,'2026-07-02 08:09:39'),(360,16,10,2025,30,0,30,'2026-07-02 08:09:39'),(361,16,11,2025,90,0,90,'2026-07-02 08:09:39'),(362,16,12,2025,14,0,14,'2026-07-02 08:09:39'),(363,16,13,2025,10,0,10,'2026-07-02 08:09:39'),(364,16,14,2025,30,0,30,'2026-07-02 08:09:39'),(365,7,1,2025,30,0,30,'2026-07-03 04:37:04'),(366,7,2,2025,14,0,14,'2026-07-03 04:37:04'),(367,7,3,2025,90,0,90,'2026-07-03 04:37:04'),(368,7,4,2025,7,0,7,'2026-07-03 04:37:04'),(369,7,5,2025,5,0,5,'2026-07-03 04:37:04'),(370,7,6,2025,30,0,30,'2026-07-03 04:37:04'),(371,7,7,2025,10,0,10,'2026-07-03 04:37:04'),(372,7,8,2025,28,0,28,'2026-07-03 04:37:04'),(373,7,9,2025,15,0,15,'2026-07-03 04:37:04'),(374,7,10,2025,30,0,30,'2026-07-03 04:37:04'),(375,7,11,2025,90,0,90,'2026-07-03 04:37:04'),(376,7,12,2025,14,0,14,'2026-07-03 04:37:04'),(377,7,13,2025,10,0,10,'2026-07-03 04:37:04'),(378,7,14,2025,30,0,30,'2026-07-03 04:37:04'),(379,7,15,2025,28,0,28,'2026-07-03 04:37:04'),(380,7,16,2025,15,0,15,'2026-07-03 04:37:04'),(381,7,17,2025,30,0,30,'2026-07-03 04:37:04'),(382,7,18,2025,90,0,90,'2026-07-03 04:37:04'),(383,7,19,2025,14,0,14,'2026-07-03 04:37:04'),(384,7,20,2025,10,0,10,'2026-07-03 04:37:04'),(385,7,21,2025,30,0,30,'2026-07-03 04:37:04'),(386,51,1,2025,30,0,30,'2026-07-03 04:37:04'),(387,51,2,2025,14,0,14,'2026-07-03 04:37:04'),(388,51,3,2025,90,0,90,'2026-07-03 04:37:04'),(389,51,4,2025,7,0,7,'2026-07-03 04:37:04'),(390,51,5,2025,5,0,5,'2026-07-03 04:37:04'),(391,51,6,2025,30,0,30,'2026-07-03 04:37:04'),(392,51,7,2025,10,0,10,'2026-07-03 04:37:04'),(393,51,8,2025,28,0,28,'2026-07-03 04:37:04'),(394,51,9,2025,15,0,15,'2026-07-03 04:37:04'),(395,51,10,2025,30,0,30,'2026-07-03 04:37:04'),(396,51,11,2025,90,0,90,'2026-07-03 04:37:04'),(397,51,12,2025,14,0,14,'2026-07-03 04:37:04'),(398,51,13,2025,10,0,10,'2026-07-03 04:37:04'),(399,51,14,2025,30,0,30,'2026-07-03 04:37:04'),(400,51,15,2025,28,0,28,'2026-07-03 04:37:04'),(401,51,16,2025,15,0,15,'2026-07-03 04:37:04'),(402,51,17,2025,30,0,30,'2026-07-03 04:37:04'),(403,51,18,2025,90,0,90,'2026-07-03 04:37:04'),(404,51,19,2025,14,0,14,'2026-07-03 04:37:04'),(405,51,20,2025,10,0,10,'2026-07-03 04:37:04'),(406,51,21,2025,30,0,30,'2026-07-03 04:37:04'),(407,2,1,2025,30,0,30,'2026-07-03 04:37:04'),(408,2,2,2025,14,0,14,'2026-07-03 04:37:04'),(409,2,3,2025,90,0,90,'2026-07-03 04:37:04'),(410,2,4,2025,7,0,7,'2026-07-03 04:37:04'),(411,2,5,2025,5,0,5,'2026-07-03 04:37:04'),(412,2,6,2025,30,0,30,'2026-07-03 04:37:04'),(413,2,7,2025,10,0,10,'2026-07-03 04:37:04'),(414,2,8,2025,28,0,28,'2026-07-03 04:37:04'),(415,2,9,2025,15,0,15,'2026-07-03 04:37:04'),(416,2,10,2025,30,0,30,'2026-07-03 04:37:04'),(417,2,11,2025,90,0,90,'2026-07-03 04:37:04'),(418,2,12,2025,14,0,14,'2026-07-03 04:37:04'),(419,2,13,2025,10,0,10,'2026-07-03 04:37:04'),(420,2,14,2025,30,0,30,'2026-07-03 04:37:04'),(421,2,15,2025,28,0,28,'2026-07-03 04:37:04'),(422,2,16,2025,15,0,15,'2026-07-03 04:37:04'),(423,2,17,2025,30,0,30,'2026-07-03 04:37:04'),(424,2,18,2025,90,0,90,'2026-07-03 04:37:04'),(425,2,19,2025,14,0,14,'2026-07-03 04:37:04'),(426,2,20,2025,10,0,10,'2026-07-03 04:37:04'),(427,2,21,2025,30,0,30,'2026-07-03 04:37:04'),(428,22,1,2025,30,0,30,'2026-07-03 04:37:04'),(429,22,2,2025,14,0,14,'2026-07-03 04:37:04'),(430,22,3,2025,90,0,90,'2026-07-03 04:37:04'),(431,22,4,2025,7,0,7,'2026-07-03 04:37:04'),(432,22,5,2025,5,0,5,'2026-07-03 04:37:04'),(433,22,6,2025,30,0,30,'2026-07-03 04:37:04'),(434,22,7,2025,10,0,10,'2026-07-03 04:37:04'),(435,22,8,2025,28,0,28,'2026-07-03 04:37:04'),(436,22,9,2025,15,0,15,'2026-07-03 04:37:04'),(437,22,10,2025,30,0,30,'2026-07-03 04:37:04'),(438,22,11,2025,90,0,90,'2026-07-03 04:37:04'),(439,22,12,2025,14,0,14,'2026-07-03 04:37:04'),(440,22,13,2025,10,0,10,'2026-07-03 04:37:04'),(441,22,14,2025,30,0,30,'2026-07-03 04:37:04'),(442,22,15,2025,28,0,28,'2026-07-03 04:37:04'),(443,22,16,2025,15,0,15,'2026-07-03 04:37:04'),(444,22,17,2025,30,0,30,'2026-07-03 04:37:04'),(445,22,18,2025,90,0,90,'2026-07-03 04:37:04'),(446,22,19,2025,14,0,14,'2026-07-03 04:37:04'),(447,22,20,2025,10,0,10,'2026-07-03 04:37:04'),(448,22,21,2025,30,0,30,'2026-07-03 04:37:04'),(449,6,1,2025,30,0,30,'2026-07-03 04:37:04'),(450,6,2,2025,14,0,14,'2026-07-03 04:37:04'),(451,6,3,2025,90,0,90,'2026-07-03 04:37:04'),(452,6,4,2025,7,0,7,'2026-07-03 04:37:04'),(453,6,5,2025,5,0,5,'2026-07-03 04:37:04'),(454,6,6,2025,30,0,30,'2026-07-03 04:37:04'),(455,6,7,2025,10,0,10,'2026-07-03 04:37:04'),(456,6,8,2025,28,0,28,'2026-07-03 04:37:04'),(457,6,9,2025,15,0,15,'2026-07-03 04:37:04'),(458,6,10,2025,30,0,30,'2026-07-03 04:37:04'),(459,6,11,2025,90,0,90,'2026-07-03 04:37:04'),(460,6,12,2025,14,0,14,'2026-07-03 04:37:04'),(461,6,13,2025,10,0,10,'2026-07-03 04:37:04'),(462,6,14,2025,30,0,30,'2026-07-03 04:37:04'),(463,6,15,2025,28,0,28,'2026-07-03 04:37:04'),(464,6,16,2025,15,0,15,'2026-07-03 04:37:04'),(465,6,17,2025,30,0,30,'2026-07-03 04:37:04'),(466,6,18,2025,90,0,90,'2026-07-03 04:37:04'),(467,6,19,2025,14,0,14,'2026-07-03 04:37:04'),(468,6,20,2025,10,0,10,'2026-07-03 04:37:04'),(469,6,21,2025,30,0,30,'2026-07-03 04:37:04'),(470,3,1,2025,30,0,30,'2026-07-03 04:37:04'),(471,3,2,2025,14,0,14,'2026-07-03 04:37:04'),(472,3,3,2025,90,0,90,'2026-07-03 04:37:04'),(473,3,4,2025,7,0,7,'2026-07-03 04:37:04'),(474,3,5,2025,5,0,5,'2026-07-03 04:37:04'),(475,3,6,2025,30,0,30,'2026-07-03 04:37:04'),(476,3,7,2025,10,0,10,'2026-07-03 04:37:04'),(477,3,8,2025,28,0,28,'2026-07-03 04:37:04'),(478,3,9,2025,15,0,15,'2026-07-03 04:37:04'),(479,3,10,2025,30,0,30,'2026-07-03 04:37:04'),(480,3,11,2025,90,0,90,'2026-07-03 04:37:04'),(481,3,12,2025,14,0,14,'2026-07-03 04:37:04'),(482,3,13,2025,10,0,10,'2026-07-03 04:37:04'),(483,3,14,2025,30,0,30,'2026-07-03 04:37:04'),(484,3,15,2025,28,0,28,'2026-07-03 04:37:04'),(485,3,16,2025,15,0,15,'2026-07-03 04:37:04'),(486,3,17,2025,30,0,30,'2026-07-03 04:37:04'),(487,3,18,2025,90,0,90,'2026-07-03 04:37:04'),(488,3,19,2025,14,0,14,'2026-07-03 04:37:04'),(489,3,20,2025,10,0,10,'2026-07-03 04:37:04'),(490,3,21,2025,30,0,30,'2026-07-03 04:37:04'),(491,24,1,2025,30,0,30,'2026-07-03 04:37:04'),(492,24,2,2025,14,0,14,'2026-07-03 04:37:04'),(493,24,3,2025,90,0,90,'2026-07-03 04:37:04'),(494,24,4,2025,7,0,7,'2026-07-03 04:37:04'),(495,24,5,2025,5,0,5,'2026-07-03 04:37:04'),(496,24,6,2025,30,0,30,'2026-07-03 04:37:04'),(497,24,7,2025,10,0,10,'2026-07-03 04:37:04'),(498,24,8,2025,28,0,28,'2026-07-03 04:37:04'),(499,24,9,2025,15,0,15,'2026-07-03 04:37:04'),(500,24,10,2025,30,0,30,'2026-07-03 04:37:04'),(501,24,11,2025,90,0,90,'2026-07-03 04:37:04'),(502,24,12,2025,14,0,14,'2026-07-03 04:37:04'),(503,24,13,2025,10,0,10,'2026-07-03 04:37:04'),(504,24,14,2025,30,0,30,'2026-07-03 04:37:04'),(505,24,15,2025,28,0,28,'2026-07-03 04:37:04'),(506,24,16,2025,15,0,15,'2026-07-03 04:37:04'),(507,24,17,2025,30,0,30,'2026-07-03 04:37:04'),(508,24,18,2025,90,0,90,'2026-07-03 04:37:04'),(509,24,19,2025,14,0,14,'2026-07-03 04:37:04'),(510,24,20,2025,10,0,10,'2026-07-03 04:37:04'),(511,24,21,2025,30,0,30,'2026-07-03 04:37:04'),(512,4,1,2025,30,0,30,'2026-07-03 04:37:04'),(513,4,2,2025,14,0,14,'2026-07-03 04:37:04'),(514,4,3,2025,90,0,90,'2026-07-03 04:37:04'),(515,4,4,2025,7,0,7,'2026-07-03 04:37:04'),(516,4,5,2025,5,0,5,'2026-07-03 04:37:04'),(517,4,6,2025,30,0,30,'2026-07-03 04:37:04'),(518,4,7,2025,10,0,10,'2026-07-03 04:37:04'),(519,4,8,2025,28,0,28,'2026-07-03 04:37:04'),(520,4,9,2025,15,0,15,'2026-07-03 04:37:04'),(521,4,10,2025,30,0,30,'2026-07-03 04:37:04'),(522,4,11,2025,90,0,90,'2026-07-03 04:37:04'),(523,4,12,2025,14,0,14,'2026-07-03 04:37:04'),(524,4,13,2025,10,0,10,'2026-07-03 04:37:04'),(525,4,14,2025,30,0,30,'2026-07-03 04:37:04'),(526,4,15,2025,28,0,28,'2026-07-03 04:37:04'),(527,4,16,2025,15,0,15,'2026-07-03 04:37:04'),(528,4,17,2025,30,0,30,'2026-07-03 04:37:04'),(529,4,18,2025,90,0,90,'2026-07-03 04:37:04'),(530,4,19,2025,14,0,14,'2026-07-03 04:37:04'),(531,4,20,2025,10,0,10,'2026-07-03 04:37:04'),(532,4,21,2025,30,0,30,'2026-07-03 04:37:04'),(533,1,1,2025,30,0,30,'2026-07-03 04:37:04'),(534,1,2,2025,14,0,14,'2026-07-03 04:37:04'),(535,1,3,2025,90,0,90,'2026-07-03 04:37:04'),(536,1,4,2025,7,0,7,'2026-07-03 04:37:04'),(537,1,5,2025,5,0,5,'2026-07-03 04:37:04'),(538,1,6,2025,30,0,30,'2026-07-03 04:37:04'),(539,1,7,2025,10,0,10,'2026-07-03 04:37:04'),(540,1,8,2025,28,0,28,'2026-07-03 04:37:04'),(541,1,9,2025,15,0,15,'2026-07-03 04:37:04'),(542,1,10,2025,30,0,30,'2026-07-03 04:37:04'),(543,1,11,2025,90,0,90,'2026-07-03 04:37:04'),(544,1,12,2025,14,0,14,'2026-07-03 04:37:04'),(545,1,13,2025,10,0,10,'2026-07-03 04:37:04'),(546,1,14,2025,30,0,30,'2026-07-03 04:37:04'),(547,1,15,2025,28,0,28,'2026-07-03 04:37:04'),(548,1,16,2025,15,0,15,'2026-07-03 04:37:04'),(549,1,17,2025,30,0,30,'2026-07-03 04:37:04'),(550,1,18,2025,90,0,90,'2026-07-03 04:37:04'),(551,1,19,2025,14,0,14,'2026-07-03 04:37:04'),(552,1,20,2025,10,0,10,'2026-07-03 04:37:04'),(553,1,21,2025,30,0,30,'2026-07-03 04:37:04'),(554,23,1,2025,30,0,30,'2026-07-03 04:37:04'),(555,23,2,2025,14,0,14,'2026-07-03 04:37:04'),(556,23,3,2025,90,0,90,'2026-07-03 04:37:04'),(557,23,4,2025,7,0,7,'2026-07-03 04:37:04'),(558,23,5,2025,5,0,5,'2026-07-03 04:37:04'),(559,23,6,2025,30,0,30,'2026-07-03 04:37:04'),(560,23,7,2025,10,0,10,'2026-07-03 04:37:04'),(561,23,8,2025,28,0,28,'2026-07-03 04:37:04'),(562,23,9,2025,15,0,15,'2026-07-03 04:37:04'),(563,23,10,2025,30,0,30,'2026-07-03 04:37:04'),(564,23,11,2025,90,0,90,'2026-07-03 04:37:04'),(565,23,12,2025,14,0,14,'2026-07-03 04:37:04'),(566,23,13,2025,10,0,10,'2026-07-03 04:37:04'),(567,23,14,2025,30,0,30,'2026-07-03 04:37:04'),(568,23,15,2025,28,0,28,'2026-07-03 04:37:04'),(569,23,16,2025,15,0,15,'2026-07-03 04:37:04'),(570,23,17,2025,30,0,30,'2026-07-03 04:37:04'),(571,23,18,2025,90,0,90,'2026-07-03 04:37:04'),(572,23,19,2025,14,0,14,'2026-07-03 04:37:04'),(573,23,20,2025,10,0,10,'2026-07-03 04:37:04'),(574,23,21,2025,30,0,30,'2026-07-03 04:37:04'),(575,18,1,2025,30,0,30,'2026-07-03 04:37:04'),(576,18,2,2025,14,0,14,'2026-07-03 04:37:04'),(577,18,3,2025,90,0,90,'2026-07-03 04:37:04'),(578,18,4,2025,7,0,7,'2026-07-03 04:37:04'),(579,18,5,2025,5,0,5,'2026-07-03 04:37:04'),(580,18,6,2025,30,0,30,'2026-07-03 04:37:04'),(581,18,7,2025,10,0,10,'2026-07-03 04:37:04'),(582,18,8,2025,28,0,28,'2026-07-03 04:37:04'),(583,18,9,2025,15,0,15,'2026-07-03 04:37:04'),(584,18,10,2025,30,0,30,'2026-07-03 04:37:04'),(585,18,11,2025,90,0,90,'2026-07-03 04:37:04'),(586,18,12,2025,14,0,14,'2026-07-03 04:37:04'),(587,18,13,2025,10,0,10,'2026-07-03 04:37:04'),(588,18,14,2025,30,0,30,'2026-07-03 04:37:04'),(589,18,15,2025,28,0,28,'2026-07-03 04:37:04'),(590,18,16,2025,15,0,15,'2026-07-03 04:37:04'),(591,18,17,2025,30,0,30,'2026-07-03 04:37:04'),(592,18,18,2025,90,0,90,'2026-07-03 04:37:04'),(593,18,19,2025,14,0,14,'2026-07-03 04:37:04'),(594,18,20,2025,10,0,10,'2026-07-03 04:37:04'),(595,18,21,2025,30,0,30,'2026-07-03 04:37:04'),(596,21,1,2025,30,0,30,'2026-07-03 04:37:04'),(597,21,2,2025,14,0,14,'2026-07-03 04:37:04'),(598,21,3,2025,90,0,90,'2026-07-03 04:37:04'),(599,21,4,2025,7,0,7,'2026-07-03 04:37:04'),(600,21,5,2025,5,0,5,'2026-07-03 04:37:04'),(601,21,6,2025,30,0,30,'2026-07-03 04:37:04'),(602,21,7,2025,10,0,10,'2026-07-03 04:37:04'),(603,21,8,2025,28,0,28,'2026-07-03 04:37:04'),(604,21,9,2025,15,0,15,'2026-07-03 04:37:04'),(605,21,10,2025,30,0,30,'2026-07-03 04:37:04'),(606,21,11,2025,90,0,90,'2026-07-03 04:37:04'),(607,21,12,2025,14,0,14,'2026-07-03 04:37:04'),(608,21,13,2025,10,0,10,'2026-07-03 04:37:04'),(609,21,14,2025,30,0,30,'2026-07-03 04:37:04'),(610,21,15,2025,28,0,28,'2026-07-03 04:37:04'),(611,21,16,2025,15,0,15,'2026-07-03 04:37:04'),(612,21,17,2025,30,0,30,'2026-07-03 04:37:04'),(613,21,18,2025,90,0,90,'2026-07-03 04:37:04'),(614,21,19,2025,14,0,14,'2026-07-03 04:37:04'),(615,21,20,2025,10,0,10,'2026-07-03 04:37:04'),(616,21,21,2025,30,0,30,'2026-07-03 04:37:04'),(617,12,1,2025,30,0,30,'2026-07-03 04:37:04'),(618,12,2,2025,14,0,14,'2026-07-03 04:37:04'),(619,12,3,2025,90,0,90,'2026-07-03 04:37:04'),(620,12,4,2025,7,0,7,'2026-07-03 04:37:04'),(621,12,5,2025,5,0,5,'2026-07-03 04:37:04'),(622,12,6,2025,30,0,30,'2026-07-03 04:37:04'),(623,12,7,2025,10,0,10,'2026-07-03 04:37:04'),(624,12,8,2025,28,0,28,'2026-07-03 04:37:04'),(625,12,9,2025,15,0,15,'2026-07-03 04:37:04'),(626,12,10,2025,30,0,30,'2026-07-03 04:37:04'),(627,12,11,2025,90,0,90,'2026-07-03 04:37:04'),(628,12,12,2025,14,0,14,'2026-07-03 04:37:04'),(629,12,13,2025,10,0,10,'2026-07-03 04:37:04'),(630,12,14,2025,30,0,30,'2026-07-03 04:37:04'),(631,12,15,2025,28,0,28,'2026-07-03 04:37:04'),(632,12,16,2025,15,0,15,'2026-07-03 04:37:04'),(633,12,17,2025,30,0,30,'2026-07-03 04:37:04'),(634,12,18,2025,90,0,90,'2026-07-03 04:37:04'),(635,12,19,2025,14,0,14,'2026-07-03 04:37:04'),(636,12,20,2025,10,0,10,'2026-07-03 04:37:04'),(637,12,21,2025,30,0,30,'2026-07-03 04:37:04'),(638,11,1,2025,30,0,30,'2026-07-03 04:37:04'),(639,11,2,2025,14,0,14,'2026-07-03 04:37:04'),(640,11,3,2025,90,0,90,'2026-07-03 04:37:04'),(641,11,4,2025,7,0,7,'2026-07-03 04:37:04'),(642,11,5,2025,5,0,5,'2026-07-03 04:37:04'),(643,11,6,2025,30,0,30,'2026-07-03 04:37:04'),(644,11,7,2025,10,0,10,'2026-07-03 04:37:04'),(645,11,8,2025,28,0,28,'2026-07-03 04:37:04'),(646,11,9,2025,15,0,15,'2026-07-03 04:37:04'),(647,11,10,2025,30,0,30,'2026-07-03 04:37:04'),(648,11,11,2025,90,0,90,'2026-07-03 04:37:04'),(649,11,12,2025,14,0,14,'2026-07-03 04:37:04'),(650,11,13,2025,10,0,10,'2026-07-03 04:37:04'),(651,11,14,2025,30,0,30,'2026-07-03 04:37:04'),(652,11,15,2025,28,0,28,'2026-07-03 04:37:04'),(653,11,16,2025,15,0,15,'2026-07-03 04:37:04'),(654,11,17,2025,30,0,30,'2026-07-03 04:37:04'),(655,11,18,2025,90,0,90,'2026-07-03 04:37:04'),(656,11,19,2025,14,0,14,'2026-07-03 04:37:04'),(657,11,20,2025,10,0,10,'2026-07-03 04:37:04'),(658,11,21,2025,30,0,30,'2026-07-03 04:37:04'),(659,8,1,2025,30,0,30,'2026-07-03 04:37:04'),(660,8,2,2025,14,0,14,'2026-07-03 04:37:04'),(661,8,3,2025,90,0,90,'2026-07-03 04:37:04'),(662,8,4,2025,7,0,7,'2026-07-03 04:37:04'),(663,8,5,2025,5,0,5,'2026-07-03 04:37:04'),(664,8,6,2025,30,0,30,'2026-07-03 04:37:04'),(665,8,7,2025,10,0,10,'2026-07-03 04:37:04'),(666,8,8,2025,28,0,28,'2026-07-03 04:37:04'),(667,8,9,2025,15,0,15,'2026-07-03 04:37:04'),(668,8,10,2025,30,0,30,'2026-07-03 04:37:04'),(669,8,11,2025,90,0,90,'2026-07-03 04:37:04'),(670,8,12,2025,14,0,14,'2026-07-03 04:37:04'),(671,8,13,2025,10,0,10,'2026-07-03 04:37:04'),(672,8,14,2025,30,0,30,'2026-07-03 04:37:04'),(673,8,15,2025,28,0,28,'2026-07-03 04:37:04'),(674,8,16,2025,15,0,15,'2026-07-03 04:37:04'),(675,8,17,2025,30,0,30,'2026-07-03 04:37:04'),(676,8,18,2025,90,0,90,'2026-07-03 04:37:04'),(677,8,19,2025,14,0,14,'2026-07-03 04:37:04'),(678,8,20,2025,10,0,10,'2026-07-03 04:37:04'),(679,8,21,2025,30,0,30,'2026-07-03 04:37:04'),(680,14,1,2025,30,0,30,'2026-07-03 04:37:04'),(681,14,2,2025,14,0,14,'2026-07-03 04:37:04'),(682,14,3,2025,90,0,90,'2026-07-03 04:37:04'),(683,14,4,2025,7,0,7,'2026-07-03 04:37:04'),(684,14,5,2025,5,0,5,'2026-07-03 04:37:04'),(685,14,6,2025,30,0,30,'2026-07-03 04:37:04'),(686,14,7,2025,10,0,10,'2026-07-03 04:37:04'),(687,14,8,2025,28,0,28,'2026-07-03 04:37:04'),(688,14,9,2025,15,0,15,'2026-07-03 04:37:04'),(689,14,10,2025,30,0,30,'2026-07-03 04:37:04'),(690,14,11,2025,90,0,90,'2026-07-03 04:37:04'),(691,14,12,2025,14,0,14,'2026-07-03 04:37:04'),(692,14,13,2025,10,0,10,'2026-07-03 04:37:04'),(693,14,14,2025,30,0,30,'2026-07-03 04:37:04'),(694,14,15,2025,28,0,28,'2026-07-03 04:37:04'),(695,14,16,2025,15,0,15,'2026-07-03 04:37:04'),(696,14,17,2025,30,0,30,'2026-07-03 04:37:04'),(697,14,18,2025,90,0,90,'2026-07-03 04:37:04'),(698,14,19,2025,14,0,14,'2026-07-03 04:37:04'),(699,14,20,2025,10,0,10,'2026-07-03 04:37:04'),(700,14,21,2025,30,0,30,'2026-07-03 04:37:04'),(701,15,1,2025,30,0,30,'2026-07-03 04:37:04'),(702,15,2,2025,14,0,14,'2026-07-03 04:37:04'),(703,15,3,2025,90,0,90,'2026-07-03 04:37:04'),(704,15,4,2025,7,0,7,'2026-07-03 04:37:04'),(705,15,5,2025,5,0,5,'2026-07-03 04:37:04'),(706,15,6,2025,30,0,30,'2026-07-03 04:37:04'),(707,15,7,2025,10,0,10,'2026-07-03 04:37:04'),(708,15,8,2025,28,0,28,'2026-07-03 04:37:04'),(709,15,9,2025,15,0,15,'2026-07-03 04:37:04'),(710,15,10,2025,30,0,30,'2026-07-03 04:37:04'),(711,15,11,2025,90,0,90,'2026-07-03 04:37:04'),(712,15,12,2025,14,0,14,'2026-07-03 04:37:04'),(713,15,13,2025,10,0,10,'2026-07-03 04:37:04'),(714,15,14,2025,30,0,30,'2026-07-03 04:37:04'),(715,15,15,2025,28,0,28,'2026-07-03 04:37:04'),(716,15,16,2025,15,0,15,'2026-07-03 04:37:04'),(717,15,17,2025,30,0,30,'2026-07-03 04:37:04'),(718,15,18,2025,90,0,90,'2026-07-03 04:37:04'),(719,15,19,2025,14,0,14,'2026-07-03 04:37:04'),(720,15,20,2025,10,0,10,'2026-07-03 04:37:04'),(721,15,21,2025,30,0,30,'2026-07-03 04:37:04'),(722,25,1,2025,30,0,30,'2026-07-03 04:37:04'),(723,25,2,2025,14,0,14,'2026-07-03 04:37:04'),(724,25,3,2025,90,0,90,'2026-07-03 04:37:04'),(725,25,4,2025,7,0,7,'2026-07-03 04:37:04'),(726,25,5,2025,5,0,5,'2026-07-03 04:37:04'),(727,25,6,2025,30,0,30,'2026-07-03 04:37:04'),(728,25,7,2025,10,0,10,'2026-07-03 04:37:04'),(729,25,8,2025,28,0,28,'2026-07-03 04:37:04'),(730,25,9,2025,15,0,15,'2026-07-03 04:37:04'),(731,25,10,2025,30,0,30,'2026-07-03 04:37:04'),(732,25,11,2025,90,0,90,'2026-07-03 04:37:04'),(733,25,12,2025,14,0,14,'2026-07-03 04:37:04'),(734,25,13,2025,10,0,10,'2026-07-03 04:37:04'),(735,25,14,2025,30,0,30,'2026-07-03 04:37:04'),(736,25,15,2025,28,0,28,'2026-07-03 04:37:04'),(737,25,16,2025,15,0,15,'2026-07-03 04:37:04'),(738,25,17,2025,30,0,30,'2026-07-03 04:37:04'),(739,25,18,2025,90,0,90,'2026-07-03 04:37:04'),(740,25,19,2025,14,0,14,'2026-07-03 04:37:04'),(741,25,20,2025,10,0,10,'2026-07-03 04:37:04'),(742,25,21,2025,30,0,30,'2026-07-03 04:37:04'),(743,10,1,2025,30,0,30,'2026-07-03 04:37:04'),(744,10,2,2025,14,0,14,'2026-07-03 04:37:04'),(745,10,3,2025,90,0,90,'2026-07-03 04:37:04'),(746,10,4,2025,7,0,7,'2026-07-03 04:37:04'),(747,10,5,2025,5,0,5,'2026-07-03 04:37:04'),(748,10,6,2025,30,0,30,'2026-07-03 04:37:04'),(749,10,7,2025,10,0,10,'2026-07-03 04:37:04'),(750,10,8,2025,28,0,28,'2026-07-03 04:37:04'),(751,10,9,2025,15,0,15,'2026-07-03 04:37:04'),(752,10,10,2025,30,0,30,'2026-07-03 04:37:04'),(753,10,11,2025,90,0,90,'2026-07-03 04:37:04'),(754,10,12,2025,14,0,14,'2026-07-03 04:37:04'),(755,10,13,2025,10,0,10,'2026-07-03 04:37:04'),(756,10,14,2025,30,0,30,'2026-07-03 04:37:04'),(757,10,15,2025,28,0,28,'2026-07-03 04:37:04'),(758,10,16,2025,15,0,15,'2026-07-03 04:37:04'),(759,10,17,2025,30,0,30,'2026-07-03 04:37:04'),(760,10,18,2025,90,0,90,'2026-07-03 04:37:04'),(761,10,19,2025,14,0,14,'2026-07-03 04:37:04'),(762,10,20,2025,10,0,10,'2026-07-03 04:37:04'),(763,10,21,2025,30,0,30,'2026-07-03 04:37:04'),(764,5,1,2025,30,0,30,'2026-07-03 04:37:04'),(765,5,2,2025,14,0,14,'2026-07-03 04:37:04'),(766,5,3,2025,90,0,90,'2026-07-03 04:37:04'),(767,5,4,2025,7,0,7,'2026-07-03 04:37:04'),(768,5,5,2025,5,0,5,'2026-07-03 04:37:04'),(769,5,6,2025,30,0,30,'2026-07-03 04:37:04'),(770,5,7,2025,10,0,10,'2026-07-03 04:37:04'),(771,5,8,2025,28,0,28,'2026-07-03 04:37:04'),(772,5,9,2025,15,0,15,'2026-07-03 04:37:04'),(773,5,10,2025,30,0,30,'2026-07-03 04:37:04'),(774,5,11,2025,90,0,90,'2026-07-03 04:37:04'),(775,5,12,2025,14,0,14,'2026-07-03 04:37:04'),(776,5,13,2025,10,0,10,'2026-07-03 04:37:04'),(777,5,14,2025,30,0,30,'2026-07-03 04:37:04'),(778,5,15,2025,28,0,28,'2026-07-03 04:37:04'),(779,5,16,2025,15,0,15,'2026-07-03 04:37:04'),(780,5,17,2025,30,0,30,'2026-07-03 04:37:04'),(781,5,18,2025,90,0,90,'2026-07-03 04:37:04'),(782,5,19,2025,14,0,14,'2026-07-03 04:37:04'),(783,5,20,2025,10,0,10,'2026-07-03 04:37:04'),(784,5,21,2025,30,0,30,'2026-07-03 04:37:04'),(785,9,1,2025,30,0,30,'2026-07-03 04:37:04'),(786,9,2,2025,14,0,14,'2026-07-03 04:37:04'),(787,9,3,2025,90,0,90,'2026-07-03 04:37:04'),(788,9,4,2025,7,0,7,'2026-07-03 04:37:04'),(789,9,5,2025,5,0,5,'2026-07-03 04:37:04'),(790,9,6,2025,30,0,30,'2026-07-03 04:37:04'),(791,9,7,2025,10,0,10,'2026-07-03 04:37:04'),(792,9,8,2025,28,0,28,'2026-07-03 04:37:04'),(793,9,9,2025,15,0,15,'2026-07-03 04:37:04'),(794,9,10,2025,30,0,30,'2026-07-03 04:37:04'),(795,9,11,2025,90,0,90,'2026-07-03 04:37:04'),(796,9,12,2025,14,0,14,'2026-07-03 04:37:04'),(797,9,13,2025,10,0,10,'2026-07-03 04:37:04'),(798,9,14,2025,30,0,30,'2026-07-03 04:37:04'),(799,9,15,2025,28,0,28,'2026-07-03 04:37:04'),(800,9,16,2025,15,0,15,'2026-07-03 04:37:04'),(801,9,17,2025,30,0,30,'2026-07-03 04:37:04'),(802,9,18,2025,90,0,90,'2026-07-03 04:37:04'),(803,9,19,2025,14,0,14,'2026-07-03 04:37:04'),(804,9,20,2025,10,0,10,'2026-07-03 04:37:04'),(805,9,21,2025,30,0,30,'2026-07-03 04:37:04'),(806,19,1,2025,30,0,30,'2026-07-03 04:37:04'),(807,19,2,2025,14,0,14,'2026-07-03 04:37:04'),(808,19,3,2025,90,0,90,'2026-07-03 04:37:04'),(809,19,4,2025,7,0,7,'2026-07-03 04:37:04'),(810,19,5,2025,5,0,5,'2026-07-03 04:37:04'),(811,19,6,2025,30,0,30,'2026-07-03 04:37:04'),(812,19,7,2025,10,0,10,'2026-07-03 04:37:04'),(813,19,8,2025,28,0,28,'2026-07-03 04:37:04'),(814,19,9,2025,15,0,15,'2026-07-03 04:37:04'),(815,19,10,2025,30,0,30,'2026-07-03 04:37:04'),(816,19,11,2025,90,0,90,'2026-07-03 04:37:04'),(817,19,12,2025,14,0,14,'2026-07-03 04:37:04'),(818,19,13,2025,10,0,10,'2026-07-03 04:37:04'),(819,19,14,2025,30,0,30,'2026-07-03 04:37:04'),(820,19,15,2025,28,0,28,'2026-07-03 04:37:04'),(821,19,16,2025,15,0,15,'2026-07-03 04:37:04'),(822,19,17,2025,30,0,30,'2026-07-03 04:37:04'),(823,19,18,2025,90,0,90,'2026-07-03 04:37:04'),(824,19,19,2025,14,0,14,'2026-07-03 04:37:04'),(825,19,20,2025,10,0,10,'2026-07-03 04:37:04'),(826,19,21,2025,30,0,30,'2026-07-03 04:37:04'),(827,13,1,2025,30,0,30,'2026-07-03 04:37:04'),(828,13,2,2025,14,0,14,'2026-07-03 04:37:04'),(829,13,3,2025,90,0,90,'2026-07-03 04:37:04'),(830,13,4,2025,7,0,7,'2026-07-03 04:37:04'),(831,13,5,2025,5,0,5,'2026-07-03 04:37:04'),(832,13,6,2025,30,0,30,'2026-07-03 04:37:04'),(833,13,7,2025,10,0,10,'2026-07-03 04:37:04'),(834,13,8,2025,28,0,28,'2026-07-03 04:37:04'),(835,13,9,2025,15,0,15,'2026-07-03 04:37:04'),(836,13,10,2025,30,0,30,'2026-07-03 04:37:04'),(837,13,11,2025,90,0,90,'2026-07-03 04:37:04'),(838,13,12,2025,14,0,14,'2026-07-03 04:37:04'),(839,13,13,2025,10,0,10,'2026-07-03 04:37:04'),(840,13,14,2025,30,0,30,'2026-07-03 04:37:04'),(841,13,15,2025,28,0,28,'2026-07-03 04:37:04'),(842,13,16,2025,15,0,15,'2026-07-03 04:37:04'),(843,13,17,2025,30,0,30,'2026-07-03 04:37:04'),(844,13,18,2025,90,0,90,'2026-07-03 04:37:04'),(845,13,19,2025,14,0,14,'2026-07-03 04:37:04'),(846,13,20,2025,10,0,10,'2026-07-03 04:37:04'),(847,13,21,2025,30,0,30,'2026-07-03 04:37:04'),(848,17,1,2025,30,0,30,'2026-07-03 04:37:04'),(849,17,2,2025,14,0,14,'2026-07-03 04:37:04'),(850,17,3,2025,90,0,90,'2026-07-03 04:37:04'),(851,17,4,2025,7,0,7,'2026-07-03 04:37:04'),(852,17,5,2025,5,0,5,'2026-07-03 04:37:04'),(853,17,6,2025,30,0,30,'2026-07-03 04:37:04'),(854,17,7,2025,10,0,10,'2026-07-03 04:37:04'),(855,17,8,2025,28,0,28,'2026-07-03 04:37:04'),(856,17,9,2025,15,0,15,'2026-07-03 04:37:04'),(857,17,10,2025,30,0,30,'2026-07-03 04:37:04'),(858,17,11,2025,90,0,90,'2026-07-03 04:37:04'),(859,17,12,2025,14,0,14,'2026-07-03 04:37:04'),(860,17,13,2025,10,0,10,'2026-07-03 04:37:04'),(861,17,14,2025,30,0,30,'2026-07-03 04:37:04'),(862,17,15,2025,28,0,28,'2026-07-03 04:37:04'),(863,17,16,2025,15,0,15,'2026-07-03 04:37:04'),(864,17,17,2025,30,0,30,'2026-07-03 04:37:04'),(865,17,18,2025,90,0,90,'2026-07-03 04:37:04'),(866,17,19,2025,14,0,14,'2026-07-03 04:37:04'),(867,17,20,2025,10,0,10,'2026-07-03 04:37:04'),(868,17,21,2025,30,0,30,'2026-07-03 04:37:04'),(869,20,1,2025,30,0,30,'2026-07-03 04:37:04'),(870,20,2,2025,14,0,14,'2026-07-03 04:37:04'),(871,20,3,2025,90,0,90,'2026-07-03 04:37:04'),(872,20,4,2025,7,0,7,'2026-07-03 04:37:04'),(873,20,5,2025,5,0,5,'2026-07-03 04:37:04'),(874,20,6,2025,30,0,30,'2026-07-03 04:37:04'),(875,20,7,2025,10,0,10,'2026-07-03 04:37:04'),(876,20,8,2025,28,0,28,'2026-07-03 04:37:04'),(877,20,9,2025,15,0,15,'2026-07-03 04:37:04'),(878,20,10,2025,30,0,30,'2026-07-03 04:37:04'),(879,20,11,2025,90,0,90,'2026-07-03 04:37:04'),(880,20,12,2025,14,0,14,'2026-07-03 04:37:04'),(881,20,13,2025,10,0,10,'2026-07-03 04:37:04'),(882,20,14,2025,30,0,30,'2026-07-03 04:37:04'),(883,20,15,2025,28,0,28,'2026-07-03 04:37:04'),(884,20,16,2025,15,0,15,'2026-07-03 04:37:04'),(885,20,17,2025,30,0,30,'2026-07-03 04:37:04'),(886,20,18,2025,90,0,90,'2026-07-03 04:37:04'),(887,20,19,2025,14,0,14,'2026-07-03 04:37:04'),(888,20,20,2025,10,0,10,'2026-07-03 04:37:04'),(889,20,21,2025,30,0,30,'2026-07-03 04:37:04'),(890,16,1,2025,30,0,30,'2026-07-03 04:37:04'),(891,16,2,2025,14,0,14,'2026-07-03 04:37:04'),(892,16,3,2025,90,0,90,'2026-07-03 04:37:04'),(893,16,4,2025,7,0,7,'2026-07-03 04:37:04'),(894,16,5,2025,5,0,5,'2026-07-03 04:37:04'),(895,16,6,2025,30,0,30,'2026-07-03 04:37:04'),(896,16,7,2025,10,0,10,'2026-07-03 04:37:04'),(897,16,8,2025,28,0,28,'2026-07-03 04:37:04'),(898,16,9,2025,15,0,15,'2026-07-03 04:37:04'),(899,16,10,2025,30,0,30,'2026-07-03 04:37:04'),(900,16,11,2025,90,0,90,'2026-07-03 04:37:04'),(901,16,12,2025,14,0,14,'2026-07-03 04:37:04'),(902,16,13,2025,10,0,10,'2026-07-03 04:37:04'),(903,16,14,2025,30,0,30,'2026-07-03 04:37:04'),(904,16,15,2025,28,0,28,'2026-07-03 04:37:04'),(905,16,16,2025,15,0,15,'2026-07-03 04:37:04'),(906,16,17,2025,30,0,30,'2026-07-03 04:37:04'),(907,16,18,2025,90,0,90,'2026-07-03 04:37:04'),(908,16,19,2025,14,0,14,'2026-07-03 04:37:04'),(909,16,20,2025,10,0,10,'2026-07-03 04:37:04'),(910,16,21,2025,30,0,30,'2026-07-03 04:37:04'),(911,7,1,2025,30,0,30,'2026-07-03 05:16:24'),(912,51,1,2025,30,0,30,'2026-07-03 05:16:24'),(913,2,1,2025,30,0,30,'2026-07-03 05:16:24'),(914,22,1,2025,30,0,30,'2026-07-03 05:16:24'),(915,6,1,2025,30,0,30,'2026-07-03 05:16:24'),(916,3,1,2025,30,0,30,'2026-07-03 05:16:24'),(917,24,1,2025,30,0,30,'2026-07-03 05:16:24'),(918,4,1,2025,30,0,30,'2026-07-03 05:16:24'),(919,1,1,2025,30,0,30,'2026-07-03 05:16:24'),(920,23,1,2025,30,0,30,'2026-07-03 05:16:24'),(921,18,1,2025,30,0,30,'2026-07-03 05:16:24'),(922,21,1,2025,30,0,30,'2026-07-03 05:16:24'),(923,12,1,2025,30,0,30,'2026-07-03 05:16:24'),(924,11,1,2025,30,0,30,'2026-07-03 05:16:24'),(925,8,1,2025,30,0,30,'2026-07-03 05:16:24'),(926,14,1,2025,30,0,30,'2026-07-03 05:16:24'),(927,15,1,2025,30,0,30,'2026-07-03 05:16:24'),(928,25,1,2025,30,0,30,'2026-07-03 05:16:24'),(929,10,1,2025,30,0,30,'2026-07-03 05:16:24'),(930,5,1,2025,30,0,30,'2026-07-03 05:16:24'),(931,9,1,2025,30,0,30,'2026-07-03 05:16:24'),(932,19,1,2025,30,0,30,'2026-07-03 05:16:24'),(933,13,1,2025,30,0,30,'2026-07-03 05:16:24'),(934,17,1,2025,30,0,30,'2026-07-03 05:16:24'),(935,20,1,2025,30,0,30,'2026-07-03 05:16:24'),(936,16,1,2025,30,0,30,'2026-07-03 05:16:24'),(937,7,2,2025,14,0,14,'2026-07-03 05:16:24'),(938,51,2,2025,14,0,14,'2026-07-03 05:16:24'),(939,2,2,2025,14,0,14,'2026-07-03 05:16:24'),(940,22,2,2025,14,0,14,'2026-07-03 05:16:24'),(941,6,2,2025,14,0,14,'2026-07-03 05:16:24'),(942,3,2,2025,14,0,14,'2026-07-03 05:16:24'),(943,24,2,2025,14,0,14,'2026-07-03 05:16:24'),(944,4,2,2025,14,0,14,'2026-07-03 05:16:24'),(945,1,2,2025,14,0,14,'2026-07-03 05:16:24'),(946,23,2,2025,14,0,14,'2026-07-03 05:16:24'),(947,18,2,2025,14,0,14,'2026-07-03 05:16:24'),(948,21,2,2025,14,0,14,'2026-07-03 05:16:24'),(949,12,2,2025,14,0,14,'2026-07-03 05:16:24'),(950,11,2,2025,14,0,14,'2026-07-03 05:16:24'),(951,8,2,2025,14,0,14,'2026-07-03 05:16:24'),(952,14,2,2025,14,0,14,'2026-07-03 05:16:24'),(953,15,2,2025,14,0,14,'2026-07-03 05:16:24'),(954,25,2,2025,14,0,14,'2026-07-03 05:16:24'),(955,10,2,2025,14,0,14,'2026-07-03 05:16:24'),(956,5,2,2025,14,0,14,'2026-07-03 05:16:24'),(957,9,2,2025,14,0,14,'2026-07-03 05:16:24'),(958,19,2,2025,14,0,14,'2026-07-03 05:16:24'),(959,13,2,2025,14,0,14,'2026-07-03 05:16:24'),(960,17,2,2025,14,0,14,'2026-07-03 05:16:24'),(961,20,2,2025,14,0,14,'2026-07-03 05:16:24'),(962,16,2,2025,14,0,14,'2026-07-03 05:16:24'),(963,7,3,2025,90,0,90,'2026-07-03 05:16:24'),(964,51,3,2025,90,0,90,'2026-07-03 05:16:24'),(965,2,3,2025,90,0,90,'2026-07-03 05:16:24'),(966,22,3,2025,90,0,90,'2026-07-03 05:16:24'),(967,6,3,2025,90,0,90,'2026-07-03 05:16:24'),(968,3,3,2025,90,0,90,'2026-07-03 05:16:24'),(969,24,3,2025,90,0,90,'2026-07-03 05:16:24'),(970,4,3,2025,90,0,90,'2026-07-03 05:16:24'),(971,1,3,2025,90,0,90,'2026-07-03 05:16:24'),(972,23,3,2025,90,0,90,'2026-07-03 05:16:24'),(973,18,3,2025,90,0,90,'2026-07-03 05:16:24'),(974,21,3,2025,90,0,90,'2026-07-03 05:16:24'),(975,12,3,2025,90,0,90,'2026-07-03 05:16:24'),(976,11,3,2025,90,0,90,'2026-07-03 05:16:24'),(977,8,3,2025,90,0,90,'2026-07-03 05:16:24'),(978,14,3,2025,90,0,90,'2026-07-03 05:16:24'),(979,15,3,2025,90,0,90,'2026-07-03 05:16:24'),(980,25,3,2025,90,0,90,'2026-07-03 05:16:24'),(981,10,3,2025,90,0,90,'2026-07-03 05:16:24'),(982,5,3,2025,90,0,90,'2026-07-03 05:16:24'),(983,9,3,2025,90,0,90,'2026-07-03 05:16:24'),(984,19,3,2025,90,0,90,'2026-07-03 05:16:24'),(985,13,3,2025,90,0,90,'2026-07-03 05:16:24'),(986,17,3,2025,90,0,90,'2026-07-03 05:16:24'),(987,20,3,2025,90,0,90,'2026-07-03 05:16:24'),(988,16,3,2025,90,0,90,'2026-07-03 05:16:24'),(989,7,4,2025,7,0,7,'2026-07-03 05:16:24'),(990,51,4,2025,7,0,7,'2026-07-03 05:16:24'),(991,2,4,2025,7,0,7,'2026-07-03 05:16:24'),(992,22,4,2025,7,0,7,'2026-07-03 05:16:24'),(993,6,4,2025,7,0,7,'2026-07-03 05:16:24'),(994,3,4,2025,7,0,7,'2026-07-03 05:16:24'),(995,24,4,2025,7,0,7,'2026-07-03 05:16:24'),(996,4,4,2025,7,0,7,'2026-07-03 05:16:24'),(997,1,4,2025,7,0,7,'2026-07-03 05:16:24'),(998,23,4,2025,7,0,7,'2026-07-03 05:16:24'),(999,18,4,2025,7,0,7,'2026-07-03 05:16:24'),(1000,21,4,2025,7,0,7,'2026-07-03 05:16:24'),(1001,12,4,2025,7,0,7,'2026-07-03 05:16:24'),(1002,11,4,2025,7,0,7,'2026-07-03 05:16:24'),(1003,8,4,2025,7,0,7,'2026-07-03 05:16:24'),(1004,14,4,2025,7,0,7,'2026-07-03 05:16:24'),(1005,15,4,2025,7,0,7,'2026-07-03 05:16:24'),(1006,25,4,2025,7,0,7,'2026-07-03 05:16:24'),(1007,10,4,2025,7,0,7,'2026-07-03 05:16:24'),(1008,5,4,2025,7,0,7,'2026-07-03 05:16:24'),(1009,9,4,2025,7,0,7,'2026-07-03 05:16:24'),(1010,19,4,2025,7,0,7,'2026-07-03 05:16:24'),(1011,13,4,2025,7,0,7,'2026-07-03 05:16:24'),(1012,17,4,2025,7,0,7,'2026-07-03 05:16:24'),(1013,20,4,2025,7,0,7,'2026-07-03 05:16:24'),(1014,16,4,2025,7,0,7,'2026-07-03 05:16:24'),(1015,7,5,2025,5,0,5,'2026-07-03 05:16:24'),(1016,51,5,2025,5,0,5,'2026-07-03 05:16:24'),(1017,2,5,2025,5,0,5,'2026-07-03 05:16:24'),(1018,22,5,2025,5,0,5,'2026-07-03 05:16:24'),(1019,6,5,2025,5,0,5,'2026-07-03 05:16:24'),(1020,3,5,2025,5,0,5,'2026-07-03 05:16:24'),(1021,24,5,2025,5,0,5,'2026-07-03 05:16:24'),(1022,4,5,2025,5,0,5,'2026-07-03 05:16:24'),(1023,1,5,2025,5,0,5,'2026-07-03 05:16:24'),(1024,23,5,2025,5,0,5,'2026-07-03 05:16:24'),(1025,18,5,2025,5,0,5,'2026-07-03 05:16:24'),(1026,21,5,2025,5,0,5,'2026-07-03 05:16:24'),(1027,12,5,2025,5,0,5,'2026-07-03 05:16:24'),(1028,11,5,2025,5,0,5,'2026-07-03 05:16:24'),(1029,8,5,2025,5,0,5,'2026-07-03 05:16:24'),(1030,14,5,2025,5,0,5,'2026-07-03 05:16:24'),(1031,15,5,2025,5,0,5,'2026-07-03 05:16:24'),(1032,25,5,2025,5,0,5,'2026-07-03 05:16:24'),(1033,10,5,2025,5,0,5,'2026-07-03 05:16:24'),(1034,5,5,2025,5,0,5,'2026-07-03 05:16:24'),(1035,9,5,2025,5,0,5,'2026-07-03 05:16:24'),(1036,19,5,2025,5,0,5,'2026-07-03 05:16:24'),(1037,13,5,2025,5,0,5,'2026-07-03 05:16:24'),(1038,17,5,2025,5,0,5,'2026-07-03 05:16:24'),(1039,20,5,2025,5,0,5,'2026-07-03 05:16:24'),(1040,16,5,2025,5,0,5,'2026-07-03 05:16:24'),(1041,7,6,2025,30,0,30,'2026-07-03 05:16:24'),(1042,51,6,2025,30,0,30,'2026-07-03 05:16:24'),(1043,2,6,2025,30,0,30,'2026-07-03 05:16:24'),(1044,22,6,2025,30,0,30,'2026-07-03 05:16:24'),(1045,6,6,2025,30,0,30,'2026-07-03 05:16:24'),(1046,3,6,2025,30,0,30,'2026-07-03 05:16:24'),(1047,24,6,2025,30,0,30,'2026-07-03 05:16:24'),(1048,4,6,2025,30,0,30,'2026-07-03 05:16:24'),(1049,1,6,2025,30,0,30,'2026-07-03 05:16:24'),(1050,23,6,2025,30,0,30,'2026-07-03 05:16:24'),(1051,18,6,2025,30,0,30,'2026-07-03 05:16:24'),(1052,21,6,2025,30,0,30,'2026-07-03 05:16:24'),(1053,12,6,2025,30,0,30,'2026-07-03 05:16:24'),(1054,11,6,2025,30,0,30,'2026-07-03 05:16:24'),(1055,8,6,2025,30,0,30,'2026-07-03 05:16:24'),(1056,14,6,2025,30,0,30,'2026-07-03 05:16:24'),(1057,15,6,2025,30,0,30,'2026-07-03 05:16:24'),(1058,25,6,2025,30,0,30,'2026-07-03 05:16:24'),(1059,10,6,2025,30,0,30,'2026-07-03 05:16:24'),(1060,5,6,2025,30,0,30,'2026-07-03 05:16:24'),(1061,9,6,2025,30,0,30,'2026-07-03 05:16:24'),(1062,19,6,2025,30,0,30,'2026-07-03 05:16:24'),(1063,13,6,2025,30,0,30,'2026-07-03 05:16:24'),(1064,17,6,2025,30,0,30,'2026-07-03 05:16:24'),(1065,20,6,2025,30,0,30,'2026-07-03 05:16:24'),(1066,16,6,2025,30,0,30,'2026-07-03 05:16:24'),(1067,7,7,2025,10,0,10,'2026-07-03 05:16:24'),(1068,51,7,2025,10,0,10,'2026-07-03 05:16:24'),(1069,2,7,2025,10,0,10,'2026-07-03 05:16:24'),(1070,22,7,2025,10,0,10,'2026-07-03 05:16:24'),(1071,6,7,2025,10,0,10,'2026-07-03 05:16:24'),(1072,3,7,2025,10,0,10,'2026-07-03 05:16:24'),(1073,24,7,2025,10,0,10,'2026-07-03 05:16:24'),(1074,4,7,2025,10,0,10,'2026-07-03 05:16:24'),(1075,1,7,2025,10,0,10,'2026-07-03 05:16:24'),(1076,23,7,2025,10,0,10,'2026-07-03 05:16:24'),(1077,18,7,2025,10,0,10,'2026-07-03 05:16:24'),(1078,21,7,2025,10,0,10,'2026-07-03 05:16:24'),(1079,12,7,2025,10,0,10,'2026-07-03 05:16:24'),(1080,11,7,2025,10,0,10,'2026-07-03 05:16:24'),(1081,8,7,2025,10,0,10,'2026-07-03 05:16:24'),(1082,14,7,2025,10,0,10,'2026-07-03 05:16:24'),(1083,15,7,2025,10,0,10,'2026-07-03 05:16:24'),(1084,25,7,2025,10,0,10,'2026-07-03 05:16:24'),(1085,10,7,2025,10,0,10,'2026-07-03 05:16:24'),(1086,5,7,2025,10,0,10,'2026-07-03 05:16:24'),(1087,9,7,2025,10,0,10,'2026-07-03 05:16:24'),(1088,19,7,2025,10,0,10,'2026-07-03 05:16:24'),(1089,13,7,2025,10,0,10,'2026-07-03 05:16:24'),(1090,17,7,2025,10,0,10,'2026-07-03 05:16:24'),(1091,20,7,2025,10,0,10,'2026-07-03 05:16:24'),(1092,16,7,2025,10,0,10,'2026-07-03 05:16:24'),(1093,7,8,2025,28,0,28,'2026-07-03 05:16:24'),(1094,51,8,2025,28,0,28,'2026-07-03 05:16:24'),(1095,2,8,2025,28,0,28,'2026-07-03 05:16:24'),(1096,22,8,2025,28,0,28,'2026-07-03 05:16:24'),(1097,6,8,2025,28,0,28,'2026-07-03 05:16:24'),(1098,3,8,2025,28,0,28,'2026-07-03 05:16:24'),(1099,24,8,2025,28,0,28,'2026-07-03 05:16:24'),(1100,4,8,2025,28,0,28,'2026-07-03 05:16:24'),(1101,1,8,2025,28,0,28,'2026-07-03 05:16:24'),(1102,23,8,2025,28,0,28,'2026-07-03 05:16:24'),(1103,18,8,2025,28,0,28,'2026-07-03 05:16:24'),(1104,21,8,2025,28,0,28,'2026-07-03 05:16:24'),(1105,12,8,2025,28,0,28,'2026-07-03 05:16:24'),(1106,11,8,2025,28,0,28,'2026-07-03 05:16:24'),(1107,8,8,2025,28,0,28,'2026-07-03 05:16:24'),(1108,14,8,2025,28,0,28,'2026-07-03 05:16:24'),(1109,15,8,2025,28,0,28,'2026-07-03 05:16:24'),(1110,25,8,2025,28,0,28,'2026-07-03 05:16:24'),(1111,10,8,2025,28,0,28,'2026-07-03 05:16:24'),(1112,5,8,2025,28,0,28,'2026-07-03 05:16:24'),(1113,9,8,2025,28,0,28,'2026-07-03 05:16:24'),(1114,19,8,2025,28,0,28,'2026-07-03 05:16:24'),(1115,13,8,2025,28,0,28,'2026-07-03 05:16:24'),(1116,17,8,2025,28,0,28,'2026-07-03 05:16:24'),(1117,20,8,2025,28,0,28,'2026-07-03 05:16:24'),(1118,16,8,2025,28,0,28,'2026-07-03 05:16:24'),(1119,7,9,2025,15,0,15,'2026-07-03 05:16:24'),(1120,51,9,2025,15,0,15,'2026-07-03 05:16:24'),(1121,2,9,2025,15,0,15,'2026-07-03 05:16:24'),(1122,22,9,2025,15,0,15,'2026-07-03 05:16:24'),(1123,6,9,2025,15,0,15,'2026-07-03 05:16:24'),(1124,3,9,2025,15,0,15,'2026-07-03 05:16:24'),(1125,24,9,2025,15,0,15,'2026-07-03 05:16:24'),(1126,4,9,2025,15,0,15,'2026-07-03 05:16:24'),(1127,1,9,2025,15,0,15,'2026-07-03 05:16:24'),(1128,23,9,2025,15,0,15,'2026-07-03 05:16:24'),(1129,18,9,2025,15,0,15,'2026-07-03 05:16:24'),(1130,21,9,2025,15,0,15,'2026-07-03 05:16:24'),(1131,12,9,2025,15,0,15,'2026-07-03 05:16:24'),(1132,11,9,2025,15,0,15,'2026-07-03 05:16:24'),(1133,8,9,2025,15,0,15,'2026-07-03 05:16:24'),(1134,14,9,2025,15,0,15,'2026-07-03 05:16:24'),(1135,15,9,2025,15,0,15,'2026-07-03 05:16:24'),(1136,25,9,2025,15,0,15,'2026-07-03 05:16:24'),(1137,10,9,2025,15,0,15,'2026-07-03 05:16:24'),(1138,5,9,2025,15,0,15,'2026-07-03 05:16:24'),(1139,9,9,2025,15,0,15,'2026-07-03 05:16:24'),(1140,19,9,2025,15,0,15,'2026-07-03 05:16:24'),(1141,13,9,2025,15,0,15,'2026-07-03 05:16:24'),(1142,17,9,2025,15,0,15,'2026-07-03 05:16:24'),(1143,20,9,2025,15,0,15,'2026-07-03 05:16:24'),(1144,16,9,2025,15,0,15,'2026-07-03 05:16:24'),(1145,7,10,2025,30,0,30,'2026-07-03 05:16:24'),(1146,51,10,2025,30,0,30,'2026-07-03 05:16:24'),(1147,2,10,2025,30,0,30,'2026-07-03 05:16:24'),(1148,22,10,2025,30,0,30,'2026-07-03 05:16:24'),(1149,6,10,2025,30,0,30,'2026-07-03 05:16:24'),(1150,3,10,2025,30,0,30,'2026-07-03 05:16:24'),(1151,24,10,2025,30,0,30,'2026-07-03 05:16:24'),(1152,4,10,2025,30,0,30,'2026-07-03 05:16:24'),(1153,1,10,2025,30,0,30,'2026-07-03 05:16:24'),(1154,23,10,2025,30,0,30,'2026-07-03 05:16:24'),(1155,18,10,2025,30,0,30,'2026-07-03 05:16:24'),(1156,21,10,2025,30,0,30,'2026-07-03 05:16:24'),(1157,12,10,2025,30,0,30,'2026-07-03 05:16:24'),(1158,11,10,2025,30,0,30,'2026-07-03 05:16:24'),(1159,8,10,2025,30,0,30,'2026-07-03 05:16:24'),(1160,14,10,2025,30,0,30,'2026-07-03 05:16:24'),(1161,15,10,2025,30,0,30,'2026-07-03 05:16:24'),(1162,25,10,2025,30,0,30,'2026-07-03 05:16:24'),(1163,10,10,2025,30,0,30,'2026-07-03 05:16:24'),(1164,5,10,2025,30,0,30,'2026-07-03 05:16:24'),(1165,9,10,2025,30,0,30,'2026-07-03 05:16:24'),(1166,19,10,2025,30,0,30,'2026-07-03 05:16:24'),(1167,13,10,2025,30,0,30,'2026-07-03 05:16:24'),(1168,17,10,2025,30,0,30,'2026-07-03 05:16:24'),(1169,20,10,2025,30,0,30,'2026-07-03 05:16:24'),(1170,16,10,2025,30,0,30,'2026-07-03 05:16:24'),(1171,7,11,2025,90,0,90,'2026-07-03 05:16:24'),(1172,51,11,2025,90,0,90,'2026-07-03 05:16:24'),(1173,2,11,2025,90,0,90,'2026-07-03 05:16:24'),(1174,22,11,2025,90,0,90,'2026-07-03 05:16:24'),(1175,6,11,2025,90,0,90,'2026-07-03 05:16:24'),(1176,3,11,2025,90,0,90,'2026-07-03 05:16:24'),(1177,24,11,2025,90,0,90,'2026-07-03 05:16:24'),(1178,4,11,2025,90,0,90,'2026-07-03 05:16:24'),(1179,1,11,2025,90,0,90,'2026-07-03 05:16:24'),(1180,23,11,2025,90,0,90,'2026-07-03 05:16:24'),(1181,18,11,2025,90,0,90,'2026-07-03 05:16:24'),(1182,21,11,2025,90,0,90,'2026-07-03 05:16:24'),(1183,12,11,2025,90,0,90,'2026-07-03 05:16:24'),(1184,11,11,2025,90,0,90,'2026-07-03 05:16:24'),(1185,8,11,2025,90,0,90,'2026-07-03 05:16:24'),(1186,14,11,2025,90,0,90,'2026-07-03 05:16:24'),(1187,15,11,2025,90,0,90,'2026-07-03 05:16:24'),(1188,25,11,2025,90,0,90,'2026-07-03 05:16:24'),(1189,10,11,2025,90,0,90,'2026-07-03 05:16:24'),(1190,5,11,2025,90,0,90,'2026-07-03 05:16:24'),(1191,9,11,2025,90,0,90,'2026-07-03 05:16:24'),(1192,19,11,2025,90,0,90,'2026-07-03 05:16:24'),(1193,13,11,2025,90,0,90,'2026-07-03 05:16:24'),(1194,17,11,2025,90,0,90,'2026-07-03 05:16:24'),(1195,20,11,2025,90,0,90,'2026-07-03 05:16:24'),(1196,16,11,2025,90,0,90,'2026-07-03 05:16:24'),(1197,7,12,2025,14,0,14,'2026-07-03 05:16:24'),(1198,51,12,2025,14,0,14,'2026-07-03 05:16:24'),(1199,2,12,2025,14,0,14,'2026-07-03 05:16:24'),(1200,22,12,2025,14,0,14,'2026-07-03 05:16:24'),(1201,6,12,2025,14,0,14,'2026-07-03 05:16:24'),(1202,3,12,2025,14,0,14,'2026-07-03 05:16:24'),(1203,24,12,2025,14,0,14,'2026-07-03 05:16:24'),(1204,4,12,2025,14,0,14,'2026-07-03 05:16:24'),(1205,1,12,2025,14,0,14,'2026-07-03 05:16:24'),(1206,23,12,2025,14,0,14,'2026-07-03 05:16:24'),(1207,18,12,2025,14,0,14,'2026-07-03 05:16:24'),(1208,21,12,2025,14,0,14,'2026-07-03 05:16:24'),(1209,12,12,2025,14,0,14,'2026-07-03 05:16:24'),(1210,11,12,2025,14,0,14,'2026-07-03 05:16:24'),(1211,8,12,2025,14,0,14,'2026-07-03 05:16:24'),(1212,14,12,2025,14,0,14,'2026-07-03 05:16:24'),(1213,15,12,2025,14,0,14,'2026-07-03 05:16:24'),(1214,25,12,2025,14,0,14,'2026-07-03 05:16:24'),(1215,10,12,2025,14,0,14,'2026-07-03 05:16:24'),(1216,5,12,2025,14,0,14,'2026-07-03 05:16:24'),(1217,9,12,2025,14,0,14,'2026-07-03 05:16:24'),(1218,19,12,2025,14,0,14,'2026-07-03 05:16:24'),(1219,13,12,2025,14,0,14,'2026-07-03 05:16:24'),(1220,17,12,2025,14,0,14,'2026-07-03 05:16:24'),(1221,20,12,2025,14,0,14,'2026-07-03 05:16:24'),(1222,16,12,2025,14,0,14,'2026-07-03 05:16:24'),(1223,7,13,2025,10,0,10,'2026-07-03 05:16:24'),(1224,51,13,2025,10,0,10,'2026-07-03 05:16:24'),(1225,2,13,2025,10,0,10,'2026-07-03 05:16:24'),(1226,22,13,2025,10,0,10,'2026-07-03 05:16:24'),(1227,6,13,2025,10,0,10,'2026-07-03 05:16:24'),(1228,3,13,2025,10,0,10,'2026-07-03 05:16:24'),(1229,24,13,2025,10,0,10,'2026-07-03 05:16:24'),(1230,4,13,2025,10,0,10,'2026-07-03 05:16:24'),(1231,1,13,2025,10,0,10,'2026-07-03 05:16:24'),(1232,23,13,2025,10,0,10,'2026-07-03 05:16:24'),(1233,18,13,2025,10,0,10,'2026-07-03 05:16:24'),(1234,21,13,2025,10,0,10,'2026-07-03 05:16:24'),(1235,12,13,2025,10,0,10,'2026-07-03 05:16:24'),(1236,11,13,2025,10,0,10,'2026-07-03 05:16:24'),(1237,8,13,2025,10,0,10,'2026-07-03 05:16:24'),(1238,14,13,2025,10,0,10,'2026-07-03 05:16:24'),(1239,15,13,2025,10,0,10,'2026-07-03 05:16:24'),(1240,25,13,2025,10,0,10,'2026-07-03 05:16:24'),(1241,10,13,2025,10,0,10,'2026-07-03 05:16:24'),(1242,5,13,2025,10,0,10,'2026-07-03 05:16:24'),(1243,9,13,2025,10,0,10,'2026-07-03 05:16:24'),(1244,19,13,2025,10,0,10,'2026-07-03 05:16:24'),(1245,13,13,2025,10,0,10,'2026-07-03 05:16:24'),(1246,17,13,2025,10,0,10,'2026-07-03 05:16:24'),(1247,20,13,2025,10,0,10,'2026-07-03 05:16:24'),(1248,16,13,2025,10,0,10,'2026-07-03 05:16:24'),(1249,7,14,2025,30,0,30,'2026-07-03 05:16:24'),(1250,51,14,2025,30,0,30,'2026-07-03 05:16:24'),(1251,2,14,2025,30,0,30,'2026-07-03 05:16:24'),(1252,22,14,2025,30,0,30,'2026-07-03 05:16:24'),(1253,6,14,2025,30,0,30,'2026-07-03 05:16:24'),(1254,3,14,2025,30,0,30,'2026-07-03 05:16:24'),(1255,24,14,2025,30,0,30,'2026-07-03 05:16:24'),(1256,4,14,2025,30,0,30,'2026-07-03 05:16:24'),(1257,1,14,2025,30,0,30,'2026-07-03 05:16:24'),(1258,23,14,2025,30,0,30,'2026-07-03 05:16:24'),(1259,18,14,2025,30,0,30,'2026-07-03 05:16:24'),(1260,21,14,2025,30,0,30,'2026-07-03 05:16:24'),(1261,12,14,2025,30,0,30,'2026-07-03 05:16:24'),(1262,11,14,2025,30,0,30,'2026-07-03 05:16:24'),(1263,8,14,2025,30,0,30,'2026-07-03 05:16:24'),(1264,14,14,2025,30,0,30,'2026-07-03 05:16:24'),(1265,15,14,2025,30,0,30,'2026-07-03 05:16:24'),(1266,25,14,2025,30,0,30,'2026-07-03 05:16:24'),(1267,10,14,2025,30,0,30,'2026-07-03 05:16:24'),(1268,5,14,2025,30,0,30,'2026-07-03 05:16:24'),(1269,9,14,2025,30,0,30,'2026-07-03 05:16:24'),(1270,19,14,2025,30,0,30,'2026-07-03 05:16:24'),(1271,13,14,2025,30,0,30,'2026-07-03 05:16:24'),(1272,17,14,2025,30,0,30,'2026-07-03 05:16:24'),(1273,20,14,2025,30,0,30,'2026-07-03 05:16:24'),(1274,16,14,2025,30,0,30,'2026-07-03 05:16:24'),(1275,7,15,2025,28,0,28,'2026-07-03 05:16:24'),(1276,51,15,2025,28,0,28,'2026-07-03 05:16:24'),(1277,2,15,2025,28,0,28,'2026-07-03 05:16:24'),(1278,22,15,2025,28,0,28,'2026-07-03 05:16:24'),(1279,6,15,2025,28,0,28,'2026-07-03 05:16:24'),(1280,3,15,2025,28,0,28,'2026-07-03 05:16:24'),(1281,24,15,2025,28,0,28,'2026-07-03 05:16:24'),(1282,4,15,2025,28,0,28,'2026-07-03 05:16:24'),(1283,1,15,2025,28,0,28,'2026-07-03 05:16:24'),(1284,23,15,2025,28,0,28,'2026-07-03 05:16:24'),(1285,18,15,2025,28,0,28,'2026-07-03 05:16:24'),(1286,21,15,2025,28,0,28,'2026-07-03 05:16:24'),(1287,12,15,2025,28,0,28,'2026-07-03 05:16:24'),(1288,11,15,2025,28,0,28,'2026-07-03 05:16:24'),(1289,8,15,2025,28,0,28,'2026-07-03 05:16:24'),(1290,14,15,2025,28,0,28,'2026-07-03 05:16:24'),(1291,15,15,2025,28,0,28,'2026-07-03 05:16:24'),(1292,25,15,2025,28,0,28,'2026-07-03 05:16:24'),(1293,10,15,2025,28,0,28,'2026-07-03 05:16:24'),(1294,5,15,2025,28,0,28,'2026-07-03 05:16:24'),(1295,9,15,2025,28,0,28,'2026-07-03 05:16:24'),(1296,19,15,2025,28,0,28,'2026-07-03 05:16:24'),(1297,13,15,2025,28,0,28,'2026-07-03 05:16:24'),(1298,17,15,2025,28,0,28,'2026-07-03 05:16:24'),(1299,20,15,2025,28,0,28,'2026-07-03 05:16:24'),(1300,16,15,2025,28,0,28,'2026-07-03 05:16:24'),(1301,7,16,2025,15,0,15,'2026-07-03 05:16:24'),(1302,51,16,2025,15,0,15,'2026-07-03 05:16:24'),(1303,2,16,2025,15,0,15,'2026-07-03 05:16:24'),(1304,22,16,2025,15,0,15,'2026-07-03 05:16:24'),(1305,6,16,2025,15,0,15,'2026-07-03 05:16:24'),(1306,3,16,2025,15,0,15,'2026-07-03 05:16:24'),(1307,24,16,2025,15,0,15,'2026-07-03 05:16:24'),(1308,4,16,2025,15,0,15,'2026-07-03 05:16:24'),(1309,1,16,2025,15,0,15,'2026-07-03 05:16:24'),(1310,23,16,2025,15,0,15,'2026-07-03 05:16:24'),(1311,18,16,2025,15,0,15,'2026-07-03 05:16:24'),(1312,21,16,2025,15,0,15,'2026-07-03 05:16:24'),(1313,12,16,2025,15,0,15,'2026-07-03 05:16:24'),(1314,11,16,2025,15,0,15,'2026-07-03 05:16:24'),(1315,8,16,2025,15,0,15,'2026-07-03 05:16:24'),(1316,14,16,2025,15,0,15,'2026-07-03 05:16:24'),(1317,15,16,2025,15,0,15,'2026-07-03 05:16:24'),(1318,25,16,2025,15,0,15,'2026-07-03 05:16:24'),(1319,10,16,2025,15,0,15,'2026-07-03 05:16:24'),(1320,5,16,2025,15,0,15,'2026-07-03 05:16:24'),(1321,9,16,2025,15,0,15,'2026-07-03 05:16:24'),(1322,19,16,2025,15,0,15,'2026-07-03 05:16:24'),(1323,13,16,2025,15,0,15,'2026-07-03 05:16:24'),(1324,17,16,2025,15,0,15,'2026-07-03 05:16:24'),(1325,20,16,2025,15,0,15,'2026-07-03 05:16:24'),(1326,16,16,2025,15,0,15,'2026-07-03 05:16:24'),(1327,7,17,2025,30,0,30,'2026-07-03 05:16:24'),(1328,51,17,2025,30,0,30,'2026-07-03 05:16:24'),(1329,2,17,2025,30,0,30,'2026-07-03 05:16:24'),(1330,22,17,2025,30,0,30,'2026-07-03 05:16:24'),(1331,6,17,2025,30,0,30,'2026-07-03 05:16:24'),(1332,3,17,2025,30,0,30,'2026-07-03 05:16:24'),(1333,24,17,2025,30,0,30,'2026-07-03 05:16:24'),(1334,4,17,2025,30,0,30,'2026-07-03 05:16:24'),(1335,1,17,2025,30,0,30,'2026-07-03 05:16:24'),(1336,23,17,2025,30,0,30,'2026-07-03 05:16:24'),(1337,18,17,2025,30,0,30,'2026-07-03 05:16:24'),(1338,21,17,2025,30,0,30,'2026-07-03 05:16:24'),(1339,12,17,2025,30,0,30,'2026-07-03 05:16:24'),(1340,11,17,2025,30,0,30,'2026-07-03 05:16:24'),(1341,8,17,2025,30,0,30,'2026-07-03 05:16:24'),(1342,14,17,2025,30,0,30,'2026-07-03 05:16:24'),(1343,15,17,2025,30,0,30,'2026-07-03 05:16:24'),(1344,25,17,2025,30,0,30,'2026-07-03 05:16:24'),(1345,10,17,2025,30,0,30,'2026-07-03 05:16:24'),(1346,5,17,2025,30,0,30,'2026-07-03 05:16:24'),(1347,9,17,2025,30,0,30,'2026-07-03 05:16:24'),(1348,19,17,2025,30,0,30,'2026-07-03 05:16:24'),(1349,13,17,2025,30,0,30,'2026-07-03 05:16:24'),(1350,17,17,2025,30,0,30,'2026-07-03 05:16:24'),(1351,20,17,2025,30,0,30,'2026-07-03 05:16:24'),(1352,16,17,2025,30,0,30,'2026-07-03 05:16:24'),(1353,7,18,2025,90,0,90,'2026-07-03 05:16:24'),(1354,51,18,2025,90,0,90,'2026-07-03 05:16:24'),(1355,2,18,2025,90,0,90,'2026-07-03 05:16:24'),(1356,22,18,2025,90,0,90,'2026-07-03 05:16:24'),(1357,6,18,2025,90,0,90,'2026-07-03 05:16:24'),(1358,3,18,2025,90,0,90,'2026-07-03 05:16:24'),(1359,24,18,2025,90,0,90,'2026-07-03 05:16:24'),(1360,4,18,2025,90,0,90,'2026-07-03 05:16:24'),(1361,1,18,2025,90,0,90,'2026-07-03 05:16:24'),(1362,23,18,2025,90,0,90,'2026-07-03 05:16:24'),(1363,18,18,2025,90,0,90,'2026-07-03 05:16:24'),(1364,21,18,2025,90,0,90,'2026-07-03 05:16:24'),(1365,12,18,2025,90,0,90,'2026-07-03 05:16:24'),(1366,11,18,2025,90,0,90,'2026-07-03 05:16:24'),(1367,8,18,2025,90,0,90,'2026-07-03 05:16:24'),(1368,14,18,2025,90,0,90,'2026-07-03 05:16:24'),(1369,15,18,2025,90,0,90,'2026-07-03 05:16:24'),(1370,25,18,2025,90,0,90,'2026-07-03 05:16:24'),(1371,10,18,2025,90,0,90,'2026-07-03 05:16:24'),(1372,5,18,2025,90,0,90,'2026-07-03 05:16:24'),(1373,9,18,2025,90,0,90,'2026-07-03 05:16:24'),(1374,19,18,2025,90,0,90,'2026-07-03 05:16:24'),(1375,13,18,2025,90,0,90,'2026-07-03 05:16:24'),(1376,17,18,2025,90,0,90,'2026-07-03 05:16:24'),(1377,20,18,2025,90,0,90,'2026-07-03 05:16:24'),(1378,16,18,2025,90,0,90,'2026-07-03 05:16:24'),(1379,7,19,2025,14,0,14,'2026-07-03 05:16:24'),(1380,51,19,2025,14,0,14,'2026-07-03 05:16:24'),(1381,2,19,2025,14,0,14,'2026-07-03 05:16:24'),(1382,22,19,2025,14,0,14,'2026-07-03 05:16:24'),(1383,6,19,2025,14,0,14,'2026-07-03 05:16:24'),(1384,3,19,2025,14,0,14,'2026-07-03 05:16:24'),(1385,24,19,2025,14,0,14,'2026-07-03 05:16:24'),(1386,4,19,2025,14,0,14,'2026-07-03 05:16:24'),(1387,1,19,2025,14,0,14,'2026-07-03 05:16:24'),(1388,23,19,2025,14,0,14,'2026-07-03 05:16:24'),(1389,18,19,2025,14,0,14,'2026-07-03 05:16:24'),(1390,21,19,2025,14,0,14,'2026-07-03 05:16:24'),(1391,12,19,2025,14,0,14,'2026-07-03 05:16:24'),(1392,11,19,2025,14,0,14,'2026-07-03 05:16:24'),(1393,8,19,2025,14,0,14,'2026-07-03 05:16:24'),(1394,14,19,2025,14,0,14,'2026-07-03 05:16:24'),(1395,15,19,2025,14,0,14,'2026-07-03 05:16:24'),(1396,25,19,2025,14,0,14,'2026-07-03 05:16:24'),(1397,10,19,2025,14,0,14,'2026-07-03 05:16:24'),(1398,5,19,2025,14,0,14,'2026-07-03 05:16:24'),(1399,9,19,2025,14,0,14,'2026-07-03 05:16:24'),(1400,19,19,2025,14,0,14,'2026-07-03 05:16:24'),(1401,13,19,2025,14,0,14,'2026-07-03 05:16:24'),(1402,17,19,2025,14,0,14,'2026-07-03 05:16:24'),(1403,20,19,2025,14,0,14,'2026-07-03 05:16:24'),(1404,16,19,2025,14,0,14,'2026-07-03 05:16:24'),(1405,7,20,2025,10,0,10,'2026-07-03 05:16:24'),(1406,51,20,2025,10,0,10,'2026-07-03 05:16:24'),(1407,2,20,2025,10,0,10,'2026-07-03 05:16:24'),(1408,22,20,2025,10,0,10,'2026-07-03 05:16:24'),(1409,6,20,2025,10,0,10,'2026-07-03 05:16:24'),(1410,3,20,2025,10,0,10,'2026-07-03 05:16:24'),(1411,24,20,2025,10,0,10,'2026-07-03 05:16:24'),(1412,4,20,2025,10,0,10,'2026-07-03 05:16:24'),(1413,1,20,2025,10,0,10,'2026-07-03 05:16:24'),(1414,23,20,2025,10,0,10,'2026-07-03 05:16:24'),(1415,18,20,2025,10,0,10,'2026-07-03 05:16:24'),(1416,21,20,2025,10,0,10,'2026-07-03 05:16:24'),(1417,12,20,2025,10,0,10,'2026-07-03 05:16:24'),(1418,11,20,2025,10,0,10,'2026-07-03 05:16:24'),(1419,8,20,2025,10,0,10,'2026-07-03 05:16:24'),(1420,14,20,2025,10,0,10,'2026-07-03 05:16:24'),(1421,15,20,2025,10,0,10,'2026-07-03 05:16:24'),(1422,25,20,2025,10,0,10,'2026-07-03 05:16:24'),(1423,10,20,2025,10,0,10,'2026-07-03 05:16:24'),(1424,5,20,2025,10,0,10,'2026-07-03 05:16:24'),(1425,9,20,2025,10,0,10,'2026-07-03 05:16:24'),(1426,19,20,2025,10,0,10,'2026-07-03 05:16:24'),(1427,13,20,2025,10,0,10,'2026-07-03 05:16:24'),(1428,17,20,2025,10,0,10,'2026-07-03 05:16:24'),(1429,20,20,2025,10,0,10,'2026-07-03 05:16:24'),(1430,16,20,2025,10,0,10,'2026-07-03 05:16:24'),(1431,7,21,2025,30,0,30,'2026-07-03 05:16:24'),(1432,51,21,2025,30,0,30,'2026-07-03 05:16:24'),(1433,2,21,2025,30,0,30,'2026-07-03 05:16:24'),(1434,22,21,2025,30,0,30,'2026-07-03 05:16:24'),(1435,6,21,2025,30,0,30,'2026-07-03 05:16:24'),(1436,3,21,2025,30,0,30,'2026-07-03 05:16:24'),(1437,24,21,2025,30,0,30,'2026-07-03 05:16:24'),(1438,4,21,2025,30,0,30,'2026-07-03 05:16:24'),(1439,1,21,2025,30,0,30,'2026-07-03 05:16:24'),(1440,23,21,2025,30,0,30,'2026-07-03 05:16:24'),(1441,18,21,2025,30,0,30,'2026-07-03 05:16:24'),(1442,21,21,2025,30,0,30,'2026-07-03 05:16:24'),(1443,12,21,2025,30,0,30,'2026-07-03 05:16:24'),(1444,11,21,2025,30,0,30,'2026-07-03 05:16:24'),(1445,8,21,2025,30,0,30,'2026-07-03 05:16:24'),(1446,14,21,2025,30,0,30,'2026-07-03 05:16:24'),(1447,15,21,2025,30,0,30,'2026-07-03 05:16:24'),(1448,25,21,2025,30,0,30,'2026-07-03 05:16:24'),(1449,10,21,2025,30,0,30,'2026-07-03 05:16:24'),(1450,5,21,2025,30,0,30,'2026-07-03 05:16:24'),(1451,9,21,2025,30,0,30,'2026-07-03 05:16:24'),(1452,19,21,2025,30,0,30,'2026-07-03 05:16:24'),(1453,13,21,2025,30,0,30,'2026-07-03 05:16:24'),(1454,17,21,2025,30,0,30,'2026-07-03 05:16:24'),(1455,20,21,2025,30,0,30,'2026-07-03 05:16:24'),(1456,16,21,2025,30,0,30,'2026-07-03 05:16:24'),(1457,7,22,2025,28,0,28,'2026-07-03 05:16:24'),(1458,51,22,2025,28,0,28,'2026-07-03 05:16:24'),(1459,2,22,2025,28,0,28,'2026-07-03 05:16:24'),(1460,22,22,2025,28,0,28,'2026-07-03 05:16:24'),(1461,6,22,2025,28,0,28,'2026-07-03 05:16:24'),(1462,3,22,2025,28,0,28,'2026-07-03 05:16:24'),(1463,24,22,2025,28,0,28,'2026-07-03 05:16:24'),(1464,4,22,2025,28,0,28,'2026-07-03 05:16:24'),(1465,1,22,2025,28,0,28,'2026-07-03 05:16:24'),(1466,23,22,2025,28,0,28,'2026-07-03 05:16:24'),(1467,18,22,2025,28,0,28,'2026-07-03 05:16:24'),(1468,21,22,2025,28,0,28,'2026-07-03 05:16:24'),(1469,12,22,2025,28,0,28,'2026-07-03 05:16:24'),(1470,11,22,2025,28,0,28,'2026-07-03 05:16:24'),(1471,8,22,2025,28,0,28,'2026-07-03 05:16:24'),(1472,14,22,2025,28,0,28,'2026-07-03 05:16:24'),(1473,15,22,2025,28,0,28,'2026-07-03 05:16:24'),(1474,25,22,2025,28,0,28,'2026-07-03 05:16:24'),(1475,10,22,2025,28,0,28,'2026-07-03 05:16:24'),(1476,5,22,2025,28,0,28,'2026-07-03 05:16:24'),(1477,9,22,2025,28,0,28,'2026-07-03 05:16:24'),(1478,19,22,2025,28,0,28,'2026-07-03 05:16:24'),(1479,13,22,2025,28,0,28,'2026-07-03 05:16:24'),(1480,17,22,2025,28,0,28,'2026-07-03 05:16:24'),(1481,20,22,2025,28,0,28,'2026-07-03 05:16:24'),(1482,16,22,2025,28,0,28,'2026-07-03 05:16:24'),(1483,7,23,2025,15,0,15,'2026-07-03 05:16:24'),(1484,51,23,2025,15,0,15,'2026-07-03 05:16:24'),(1485,2,23,2025,15,0,15,'2026-07-03 05:16:24'),(1486,22,23,2025,15,0,15,'2026-07-03 05:16:24'),(1487,6,23,2025,15,0,15,'2026-07-03 05:16:24'),(1488,3,23,2025,15,0,15,'2026-07-03 05:16:24'),(1489,24,23,2025,15,0,15,'2026-07-03 05:16:24'),(1490,4,23,2025,15,0,15,'2026-07-03 05:16:24'),(1491,1,23,2025,15,0,15,'2026-07-03 05:16:24'),(1492,23,23,2025,15,0,15,'2026-07-03 05:16:24'),(1493,18,23,2025,15,0,15,'2026-07-03 05:16:24'),(1494,21,23,2025,15,0,15,'2026-07-03 05:16:24'),(1495,12,23,2025,15,0,15,'2026-07-03 05:16:24'),(1496,11,23,2025,15,0,15,'2026-07-03 05:16:24'),(1497,8,23,2025,15,0,15,'2026-07-03 05:16:24'),(1498,14,23,2025,15,0,15,'2026-07-03 05:16:24'),(1499,15,23,2025,15,0,15,'2026-07-03 05:16:24'),(1500,25,23,2025,15,0,15,'2026-07-03 05:16:24'),(1501,10,23,2025,15,0,15,'2026-07-03 05:16:24'),(1502,5,23,2025,15,0,15,'2026-07-03 05:16:24'),(1503,9,23,2025,15,0,15,'2026-07-03 05:16:24'),(1504,19,23,2025,15,0,15,'2026-07-03 05:16:24'),(1505,13,23,2025,15,0,15,'2026-07-03 05:16:24'),(1506,17,23,2025,15,0,15,'2026-07-03 05:16:24'),(1507,20,23,2025,15,0,15,'2026-07-03 05:16:24'),(1508,16,23,2025,15,0,15,'2026-07-03 05:16:24'),(1509,7,24,2025,30,0,30,'2026-07-03 05:16:24'),(1510,51,24,2025,30,0,30,'2026-07-03 05:16:24'),(1511,2,24,2025,30,0,30,'2026-07-03 05:16:24'),(1512,22,24,2025,30,0,30,'2026-07-03 05:16:24'),(1513,6,24,2025,30,0,30,'2026-07-03 05:16:24'),(1514,3,24,2025,30,0,30,'2026-07-03 05:16:24'),(1515,24,24,2025,30,0,30,'2026-07-03 05:16:24'),(1516,4,24,2025,30,0,30,'2026-07-03 05:16:24'),(1517,1,24,2025,30,0,30,'2026-07-03 05:16:24'),(1518,23,24,2025,30,0,30,'2026-07-03 05:16:24'),(1519,18,24,2025,30,0,30,'2026-07-03 05:16:24'),(1520,21,24,2025,30,0,30,'2026-07-03 05:16:24'),(1521,12,24,2025,30,0,30,'2026-07-03 05:16:24'),(1522,11,24,2025,30,0,30,'2026-07-03 05:16:24'),(1523,8,24,2025,30,0,30,'2026-07-03 05:16:24'),(1524,14,24,2025,30,0,30,'2026-07-03 05:16:24'),(1525,15,24,2025,30,0,30,'2026-07-03 05:16:24'),(1526,25,24,2025,30,0,30,'2026-07-03 05:16:24'),(1527,10,24,2025,30,0,30,'2026-07-03 05:16:24'),(1528,5,24,2025,30,0,30,'2026-07-03 05:16:24'),(1529,9,24,2025,30,0,30,'2026-07-03 05:16:24'),(1530,19,24,2025,30,0,30,'2026-07-03 05:16:24'),(1531,13,24,2025,30,0,30,'2026-07-03 05:16:24'),(1532,17,24,2025,30,0,30,'2026-07-03 05:16:24'),(1533,20,24,2025,30,0,30,'2026-07-03 05:16:24'),(1534,16,24,2025,30,0,30,'2026-07-03 05:16:24'),(1535,7,25,2025,90,0,90,'2026-07-03 05:16:24'),(1536,51,25,2025,90,0,90,'2026-07-03 05:16:24'),(1537,2,25,2025,90,0,90,'2026-07-03 05:16:24'),(1538,22,25,2025,90,0,90,'2026-07-03 05:16:24'),(1539,6,25,2025,90,0,90,'2026-07-03 05:16:24'),(1540,3,25,2025,90,0,90,'2026-07-03 05:16:24'),(1541,24,25,2025,90,0,90,'2026-07-03 05:16:24'),(1542,4,25,2025,90,0,90,'2026-07-03 05:16:24'),(1543,1,25,2025,90,0,90,'2026-07-03 05:16:24'),(1544,23,25,2025,90,0,90,'2026-07-03 05:16:24'),(1545,18,25,2025,90,0,90,'2026-07-03 05:16:24'),(1546,21,25,2025,90,0,90,'2026-07-03 05:16:24'),(1547,12,25,2025,90,0,90,'2026-07-03 05:16:24'),(1548,11,25,2025,90,0,90,'2026-07-03 05:16:24'),(1549,8,25,2025,90,0,90,'2026-07-03 05:16:24'),(1550,14,25,2025,90,0,90,'2026-07-03 05:16:24'),(1551,15,25,2025,90,0,90,'2026-07-03 05:16:24'),(1552,25,25,2025,90,0,90,'2026-07-03 05:16:24'),(1553,10,25,2025,90,0,90,'2026-07-03 05:16:24'),(1554,5,25,2025,90,0,90,'2026-07-03 05:16:24'),(1555,9,25,2025,90,0,90,'2026-07-03 05:16:24'),(1556,19,25,2025,90,0,90,'2026-07-03 05:16:24'),(1557,13,25,2025,90,0,90,'2026-07-03 05:16:24'),(1558,17,25,2025,90,0,90,'2026-07-03 05:16:24'),(1559,20,25,2025,90,0,90,'2026-07-03 05:16:24'),(1560,16,25,2025,90,0,90,'2026-07-03 05:16:24'),(1561,7,26,2025,14,0,14,'2026-07-03 05:16:24'),(1562,51,26,2025,14,0,14,'2026-07-03 05:16:24'),(1563,2,26,2025,14,0,14,'2026-07-03 05:16:24'),(1564,22,26,2025,14,0,14,'2026-07-03 05:16:24'),(1565,6,26,2025,14,0,14,'2026-07-03 05:16:24'),(1566,3,26,2025,14,0,14,'2026-07-03 05:16:24'),(1567,24,26,2025,14,0,14,'2026-07-03 05:16:24'),(1568,4,26,2025,14,0,14,'2026-07-03 05:16:24'),(1569,1,26,2025,14,0,14,'2026-07-03 05:16:24'),(1570,23,26,2025,14,0,14,'2026-07-03 05:16:24'),(1571,18,26,2025,14,0,14,'2026-07-03 05:16:24'),(1572,21,26,2025,14,0,14,'2026-07-03 05:16:24'),(1573,12,26,2025,14,0,14,'2026-07-03 05:16:24'),(1574,11,26,2025,14,0,14,'2026-07-03 05:16:24'),(1575,8,26,2025,14,0,14,'2026-07-03 05:16:24'),(1576,14,26,2025,14,0,14,'2026-07-03 05:16:24'),(1577,15,26,2025,14,0,14,'2026-07-03 05:16:24'),(1578,25,26,2025,14,0,14,'2026-07-03 05:16:24'),(1579,10,26,2025,14,0,14,'2026-07-03 05:16:24'),(1580,5,26,2025,14,0,14,'2026-07-03 05:16:24'),(1581,9,26,2025,14,0,14,'2026-07-03 05:16:24'),(1582,19,26,2025,14,0,14,'2026-07-03 05:16:24'),(1583,13,26,2025,14,0,14,'2026-07-03 05:16:24'),(1584,17,26,2025,14,0,14,'2026-07-03 05:16:24'),(1585,20,26,2025,14,0,14,'2026-07-03 05:16:24'),(1586,16,26,2025,14,0,14,'2026-07-03 05:16:24'),(1587,7,27,2025,10,0,10,'2026-07-03 05:16:24'),(1588,51,27,2025,10,0,10,'2026-07-03 05:16:24'),(1589,2,27,2025,10,0,10,'2026-07-03 05:16:24'),(1590,22,27,2025,10,0,10,'2026-07-03 05:16:24'),(1591,6,27,2025,10,0,10,'2026-07-03 05:16:24'),(1592,3,27,2025,10,0,10,'2026-07-03 05:16:24'),(1593,24,27,2025,10,0,10,'2026-07-03 05:16:24'),(1594,4,27,2025,10,0,10,'2026-07-03 05:16:24'),(1595,1,27,2025,10,0,10,'2026-07-03 05:16:24'),(1596,23,27,2025,10,0,10,'2026-07-03 05:16:24'),(1597,18,27,2025,10,0,10,'2026-07-03 05:16:24'),(1598,21,27,2025,10,0,10,'2026-07-03 05:16:24'),(1599,12,27,2025,10,0,10,'2026-07-03 05:16:24'),(1600,11,27,2025,10,0,10,'2026-07-03 05:16:24'),(1601,8,27,2025,10,0,10,'2026-07-03 05:16:24'),(1602,14,27,2025,10,0,10,'2026-07-03 05:16:24'),(1603,15,27,2025,10,0,10,'2026-07-03 05:16:24'),(1604,25,27,2025,10,0,10,'2026-07-03 05:16:24'),(1605,10,27,2025,10,0,10,'2026-07-03 05:16:24'),(1606,5,27,2025,10,0,10,'2026-07-03 05:16:24'),(1607,9,27,2025,10,0,10,'2026-07-03 05:16:24'),(1608,19,27,2025,10,0,10,'2026-07-03 05:16:24'),(1609,13,27,2025,10,0,10,'2026-07-03 05:16:24'),(1610,17,27,2025,10,0,10,'2026-07-03 05:16:24'),(1611,20,27,2025,10,0,10,'2026-07-03 05:16:24'),(1612,16,27,2025,10,0,10,'2026-07-03 05:16:24'),(1613,7,28,2025,30,0,30,'2026-07-03 05:16:24'),(1614,51,28,2025,30,0,30,'2026-07-03 05:16:24'),(1615,2,28,2025,30,0,30,'2026-07-03 05:16:24'),(1616,22,28,2025,30,0,30,'2026-07-03 05:16:24'),(1617,6,28,2025,30,0,30,'2026-07-03 05:16:24'),(1618,3,28,2025,30,0,30,'2026-07-03 05:16:24'),(1619,24,28,2025,30,0,30,'2026-07-03 05:16:24'),(1620,4,28,2025,30,0,30,'2026-07-03 05:16:24'),(1621,1,28,2025,30,0,30,'2026-07-03 05:16:24'),(1622,23,28,2025,30,0,30,'2026-07-03 05:16:24'),(1623,18,28,2025,30,0,30,'2026-07-03 05:16:24'),(1624,21,28,2025,30,0,30,'2026-07-03 05:16:24'),(1625,12,28,2025,30,0,30,'2026-07-03 05:16:24'),(1626,11,28,2025,30,0,30,'2026-07-03 05:16:24'),(1627,8,28,2025,30,0,30,'2026-07-03 05:16:24'),(1628,14,28,2025,30,0,30,'2026-07-03 05:16:24'),(1629,15,28,2025,30,0,30,'2026-07-03 05:16:24'),(1630,25,28,2025,30,0,30,'2026-07-03 05:16:24'),(1631,10,28,2025,30,0,30,'2026-07-03 05:16:24'),(1632,5,28,2025,30,0,30,'2026-07-03 05:16:24'),(1633,9,28,2025,30,0,30,'2026-07-03 05:16:24'),(1634,19,28,2025,30,0,30,'2026-07-03 05:16:24'),(1635,13,28,2025,30,0,30,'2026-07-03 05:16:24'),(1636,17,28,2025,30,0,30,'2026-07-03 05:16:24'),(1637,20,28,2025,30,0,30,'2026-07-03 05:16:24'),(1638,16,28,2025,30,0,30,'2026-07-03 05:16:24');
/*!40000 ALTER TABLE `leave_balances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_requests`
--

DROP TABLE IF EXISTS `leave_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `leave_type_id` int(11) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_lreq_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_requests`
--

LOCK TABLES `leave_requests` WRITE;
/*!40000 ALTER TABLE `leave_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `leave_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leave_types`
--

DROP TABLE IF EXISTS `leave_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `leave_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_name` varchar(100) DEFAULT NULL,
  `leave_type_name` varchar(100) DEFAULT NULL,
  `days_per_year` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leave_types`
--

LOCK TABLES `leave_types` WRITE;
/*!40000 ALTER TABLE `leave_types` DISABLE KEYS */;
INSERT INTO `leave_types` VALUES (1,'Annual Leave',NULL,30,NULL,1,'2026-06-29 13:37:58'),(2,'Sick Leave',NULL,14,NULL,1,'2026-06-29 13:37:58'),(3,'Maternity Leave',NULL,90,NULL,1,'2026-06-29 13:37:58'),(4,'Paternity Leave',NULL,7,NULL,1,'2026-06-29 13:37:58'),(5,'Compassionate Leave',NULL,5,NULL,1,'2026-06-29 13:37:58'),(6,'Study Leave',NULL,30,NULL,1,'2026-06-29 13:37:58'),(7,'Casual Leave',NULL,10,NULL,1,'2026-06-29 13:37:58'),(8,'Annual','Annual Leave',28,'Annual paid leave',1,'2026-07-02 08:09:39'),(9,'Sick','Sick Leave',15,'Paid sick leave with medical certificate',1,'2026-07-02 08:09:39'),(10,'Study','Study Leave',30,'Leave for examinations and academic purposes',1,'2026-07-02 08:09:39'),(11,'Maternity','Maternity Leave',90,'Paid maternity leave',1,'2026-07-02 08:09:39'),(12,'Paternity','Paternity Leave',14,'Paid paternity leave',1,'2026-07-02 08:09:39'),(13,'Compassionate','Compassionate Leave',10,'Leave for bereavement or family emergencies',1,'2026-07-02 08:09:39'),(14,'Unpaid','Unpaid Leave',30,'Leave without pay',1,'2026-07-02 08:09:39'),(15,'Annual','Annual Leave',28,'Annual paid leave',1,'2026-07-03 04:37:04'),(16,'Sick','Sick Leave',15,'Paid sick leave with medical certificate',1,'2026-07-03 04:37:04'),(17,'Study','Study Leave',30,'Leave for examinations and academic purposes',1,'2026-07-03 04:37:04'),(18,'Maternity','Maternity Leave',90,'Paid maternity leave',1,'2026-07-03 04:37:04'),(19,'Paternity','Paternity Leave',14,'Paid paternity leave',1,'2026-07-03 04:37:04'),(20,'Compassionate','Compassionate Leave',10,'Leave for bereavement or family emergencies',1,'2026-07-03 04:37:04'),(21,'Unpaid','Unpaid Leave',30,'Leave without pay',1,'2026-07-03 04:37:04'),(22,'Annual','Annual Leave',28,'Annual paid leave',1,'2026-07-03 05:16:24'),(23,'Sick','Sick Leave',15,'Paid sick leave with medical certificate',1,'2026-07-03 05:16:24'),(24,'Study','Study Leave',30,'Leave for examinations and academic purposes',1,'2026-07-03 05:16:24'),(25,'Maternity','Maternity Leave',90,'Paid maternity leave',1,'2026-07-03 05:16:24'),(26,'Paternity','Paternity Leave',14,'Paid paternity leave',1,'2026-07-03 05:16:24'),(27,'Compassionate','Compassionate Leave',10,'Leave for bereavement or family emergencies',1,'2026-07-03 05:16:24'),(28,'Unpaid','Unpaid Leave',30,'Leave without pay',1,'2026-07-03 05:16:24');
/*!40000 ALTER TABLE `leave_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leaves`
--

DROP TABLE IF EXISTS `leaves`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `library_acquisitions`
--

DROP TABLE IF EXISTS `library_acquisitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_acquisitions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `isbn` varchar(50) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `publication_year` year(4) DEFAULT NULL,
  `acquisition_type` enum('Purchase','Donation','Exchange','Subscription','Other') DEFAULT 'Purchase',
  `quantity` int(11) DEFAULT 1,
  `unit_cost` decimal(15,2) DEFAULT NULL,
  `total_cost` decimal(15,2) DEFAULT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `invoice_number` varchar(100) DEFAULT NULL,
  `acquisition_date` date NOT NULL,
  `received_date` date DEFAULT NULL,
  `shelf_location` varchar(100) DEFAULT NULL,
  `status` enum('Ordered','Received','Processed','Rejected') DEFAULT 'Ordered',
  `acquired_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_la_date` (`acquisition_date`),
  KEY `idx_la_type` (`acquisition_type`),
  KEY `idx_la_status` (`status`),
  KEY `idx_la_isbn` (`isbn`),
  KEY `fk_la_acquired` (`acquired_by`),
  CONSTRAINT `fk_la_acquired` FOREIGN KEY (`acquired_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_acquisitions`
--

LOCK TABLES `library_acquisitions` WRITE;
/*!40000 ALTER TABLE `library_acquisitions` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_acquisitions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_books`
--

DROP TABLE IF EXISTS `library_books`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `library_borrowing`
--

DROP TABLE IF EXISTS `library_borrowing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_borrowing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `book_id` int(11) DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `borrow_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'borrowed',
  `renewal_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_borrowing`
--

LOCK TABLES `library_borrowing` WRITE;
/*!40000 ALTER TABLE `library_borrowing` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_borrowing` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_clearance`
--

DROP TABLE IF EXISTS `library_clearance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_clearance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `cleared_by` int(11) DEFAULT NULL,
  `clearance_date` datetime DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_lc_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_clearance`
--

LOCK TABLES `library_clearance` WRITE;
/*!40000 ALTER TABLE `library_clearance` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_clearance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_digital_resources`
--

DROP TABLE IF EXISTS `library_digital_resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_digital_resources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `resource_type` varchar(100) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `download_count` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `added_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_digital_resources`
--

LOCK TABLES `library_digital_resources` WRITE;
/*!40000 ALTER TABLE `library_digital_resources` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_digital_resources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_fines`
--

DROP TABLE IF EXISTS `library_fines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `library_management`
--

DROP TABLE IF EXISTS `library_management`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_management` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `book_title` varchar(255) DEFAULT NULL,
  `author` varchar(255) DEFAULT NULL,
  `isbn` varchar(50) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `quantity` int(11) DEFAULT 1,
  `available` int(11) DEFAULT 1,
  `status` varchar(20) DEFAULT 'Available',
  `location` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_management`
--

LOCK TABLES `library_management` WRITE;
/*!40000 ALTER TABLE `library_management` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_management` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_members`
--

DROP TABLE IF EXISTS `library_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member_id` varchar(50) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `member_type` varchar(50) DEFAULT 'Student',
  `full_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Active',
  `registration_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_members`
--

LOCK TABLES `library_members` WRITE;
/*!40000 ALTER TABLE `library_members` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_members` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `library_transactions`
--

DROP TABLE IF EXISTS `library_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `library_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `book_id` int(11) DEFAULT NULL,
  `member_id` int(11) DEFAULT NULL,
  `borrow_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `status` varchar(20) DEFAULT 'borrowed',
  `fine_amount` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_member_id` (`member_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `library_transactions`
--

LOCK TABLES `library_transactions` WRITE;
/*!40000 ALTER TABLE `library_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `library_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `meal_tracking`
--

DROP TABLE IF EXISTS `meal_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
  KEY `ms_expiry_status` (`expiry_date`,`status`),
  KEY `idx_ms_category_status` (`category`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `medicine_stock`
--

LOCK TABLES `medicine_stock` WRITE;
/*!40000 ALTER TABLE `medicine_stock` DISABLE KEYS */;
INSERT INTO `medicine_stock` VALUES (1,'PARA001','Paracetamol','Acetaminophen','Painkiller','Tablet','500mg',NULL,NULL,200,'tablets',50,50.00,NULL,'UGX',NULL,'2027-12-31','Cabinet A1',0,'1-2 tablets every 4-6 hours as needed for pain/fever',NULL,'In Stock',NULL,NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(2,'IBU001','Ibuprofen','Ibuprofen','Anti-inflammatory','Tablet','400mg',NULL,NULL,150,'tablets',30,100.00,NULL,'UGX',NULL,'2027-10-31','Cabinet A1',0,'1 tablet 3 times daily after meals',NULL,'In Stock',NULL,NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(3,'AMOX001','Amoxicillin','Amoxicillin','Antibiotic','Capsule','500mg',NULL,NULL,100,'capsules',20,200.00,NULL,'UGX',NULL,'2027-08-31','Cabinet B1',1,'1 capsule 3 times daily for 7 days',NULL,'In Stock',NULL,NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(4,'CTM001','Chlorpheniramine','Chlorpheniramine Maleate','Allergy','Tablet','4mg',NULL,NULL,100,'tablets',20,50.00,NULL,'UGX',NULL,'2027-11-30','Cabinet A2',0,'1 tablet every 4-6 hours for allergies',NULL,'In Stock',NULL,NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(5,'ORS001','Oral Rehydration Salts','ORS','Other','Powder','20.5g/sachet',NULL,NULL,100,'sachets',30,500.00,NULL,'UGX',NULL,'2028-06-30','Cabinet C1',0,'Dissolve 1 sachet in 1L water, drink after each loose stool',NULL,'In Stock',NULL,NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(6,'ART001','Artemether/Lumefantrine','Coartem','Antimalarial','Tablet','20/120mg',NULL,NULL,60,'tablets',20,1500.00,NULL,'UGX',NULL,'2027-09-30','Cabinet B2',1,'4 tablets twice daily for 3 days',NULL,'In Stock',NULL,NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(7,'VITC001','Vitamin C','Ascorbic Acid','Vitamins','Tablet','500mg',NULL,NULL,300,'tablets',50,30.00,NULL,'UGX',NULL,'2028-12-31','Cabinet C1',0,'1 tablet daily for immune support',NULL,'In Stock',NULL,NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(8,'MET001','Metered Dose Inhaler','Salbutamol','Respiratory','Inhaler','100mcg/dose',NULL,NULL,10,'inhalers',3,15000.00,NULL,'UGX',NULL,'2027-06-30','Cabinet A3',1,'1-2 puffs as needed for asthma symptoms',NULL,'In Stock',NULL,NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(9,'ANT001','Antacid','Aluminum/Magnesium Hydroxide','Digestive','Tablet','500mg',NULL,NULL,200,'tablets',40,100.00,NULL,'UGX',NULL,'2027-11-30','Cabinet C1',0,'1-2 tablets after meals or when symptomatic',NULL,'In Stock',NULL,NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(10,'HYD001','Hydrocortisone Cream','Hydrocortisone','Dermatological','Cream','1%',NULL,NULL,20,'tubes',5,5000.00,NULL,'UGX',NULL,'2027-08-31','Cabinet D1',0,'Apply thin layer to affected area 2-3 times daily',NULL,'In Stock',NULL,NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(11,'DIA001','Diazepam','Diazepam','Painkiller','Tablet','5mg',NULL,NULL,30,'tablets',10,200.00,NULL,'UGX',NULL,'2026-12-31','Cabinet B2',1,'1 tablet at bedtime for anxiety or muscle spasms',NULL,'In Stock',NULL,NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(12,'BAN001','Bandages','Cotton Bandage','First Aid','Other','4 inches x 5 meters',NULL,NULL,50,'rolls',10,1500.00,NULL,'UGX',NULL,'2029-12-31','Shelf E1',0,'For wound dressing and injury management',NULL,'In Stock',NULL,NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(13,'GAU001','Gauze Swabs','Sterile Gauze','First Aid','Other','10x10cm',NULL,NULL,200,'packs',50,800.00,NULL,'UGX',NULL,'2029-12-31','Shelf E1',0,'Sterile swabs for wound cleaning and dressing',NULL,'In Stock',NULL,NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(14,'GLU001','Glucose Powder','Dextrose','Vitamins','Powder','500g',NULL,NULL,10,'packs',3,5000.00,NULL,'UGX',NULL,'2028-06-30','Cabinet C1',0,'Mix 2 tablespoons in water for energy',NULL,'In Stock',NULL,NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(15,'ALC001','Alcohol Swabs','Isopropyl Alcohol','First Aid','Solution','70%',NULL,NULL,300,'swabs',50,100.00,NULL,'UGX',NULL,'2028-12-31','Shelf E1',0,'Use for cleaning skin before injections',NULL,'In Stock',NULL,NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(16,'CLO001','Chloroquine','Chloroquine Phosphate','Antimalarial','Tablet','250mg',NULL,NULL,50,'tablets',15,300.00,NULL,'UGX',NULL,'2027-05-31','Cabinet B2',1,'As prescribed for malaria treatment',NULL,'In Stock',NULL,NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(17,'MEF001','Mefenamic Acid','Mefenamic Acid','Painkiller','Capsule','500mg',NULL,NULL,80,'capsules',20,200.00,NULL,'UGX',NULL,'2027-07-31','Cabinet A1',0,'1 capsule 3 times daily for pain and inflammation',NULL,'In Stock',NULL,NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(18,'METR001','Metronidazole','Metronidazole','Antibiotic','Tablet','400mg',NULL,NULL,100,'tablets',20,150.00,NULL,'UGX',NULL,'2027-09-30','Cabinet B1',1,'1 tablet 3 times daily for 5-7 days',NULL,'In Stock',NULL,NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(19,'DIC001','Diclofenac Gel','Diclofenac Diethylamine','Anti-inflammatory','Cream','1%',NULL,NULL,15,'tubes',5,7000.00,NULL,'UGX',NULL,'2027-10-31','Cabinet D1',0,'Apply to affected area 3-4 times daily',NULL,'In Stock',NULL,NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(20,'CET001','Cetirizine','Cetirizine Hydrochloride','Allergy','Tablet','10mg',NULL,NULL,100,'tablets',20,100.00,NULL,'UGX',NULL,'2027-12-31','Cabinet A2',0,'1 tablet daily for allergy symptoms',NULL,'In Stock',NULL,NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(21,'ASP001','Aspirin','Acetylsalicylic Acid','Painkiller','Tablet','300mg',NULL,NULL,100,'tablets',25,50.00,NULL,'UGX',NULL,'2027-06-30','Cabinet A1',0,'1-2 tablets every 4-6 hours for pain/fever',NULL,'In Stock',NULL,NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(22,'ZIN001','Zinc Tablets','Zinc Sulfate','Vitamins','Tablet','20mg',NULL,NULL,150,'tablets',30,100.00,NULL,'UGX',NULL,'2028-09-30','Cabinet C1',0,'1 tablet daily for immune support and wound healing',NULL,'In Stock',NULL,NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(23,'CLOT001','Clotrimazole Cream','Clotrimazole','Antifungal','Cream','1%',NULL,NULL,15,'tubes',5,4000.00,NULL,'UGX',NULL,'2027-08-31','Cabinet D1',0,'Apply to affected area twice daily for 2 weeks',NULL,'In Stock',NULL,NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(24,'EYE001','Eye Drops','Chloramphenicol','Other','Drops','0.5%',NULL,NULL,20,'bottles',5,5000.00,NULL,'UGX',NULL,'2027-04-30','Cabinet A3',1,'1-2 drops in affected eye every 2-4 hours',NULL,'In Stock',NULL,NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(25,'BET001','Betadine Solution','Povidone-Iodine','First Aid','Solution','10%',NULL,NULL,10,'bottles',3,8000.00,NULL,'UGX',NULL,'2028-03-31','Shelf E1',0,'Apply to wounds for disinfection',NULL,'In Stock',NULL,NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44');
/*!40000 ALTER TABLE `medicine_stock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `medicine_stock_transactions`
--

DROP TABLE IF EXISTS `medicine_stock_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `menu_groups`
--

DROP TABLE IF EXISTS `menu_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `menu_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_name` varchar(100) NOT NULL COMMENT 'Unique identifier like executive, finance, library',
  `display_name` varchar(200) NOT NULL COMMENT 'Shown in sidebar',
  `icon` varchar(100) DEFAULT 'fas fa-circle',
  `sort_order` int(11) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `group_name` (`group_name`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu_groups`
--

LOCK TABLES `menu_groups` WRITE;
/*!40000 ALTER TABLE `menu_groups` DISABLE KEYS */;
INSERT INTO `menu_groups` VALUES (1,'executive','Executive','fas fa-crown',1,'active'),(2,'academic_mgmt','Academic Management','fas fa-graduation-cap',2,'active'),(3,'academic_registrar','Academic Registrar','fas fa-clipboard-list',3,'active'),(4,'overview','Overview','fas fa-chart-pie',4,'active'),(5,'student_fees','Student Fees','fas fa-money-bill-wave',5,'active'),(6,'payments','Payments','fas fa-credit-card',6,'active'),(7,'payroll','Payroll','fas fa-wallet',7,'active'),(8,'budgets','Budgets & Expenditure','fas fa-chart-line',8,'active'),(9,'accounts','Accounts','fas fa-book',9,'active'),(10,'requisitions','Requisitions','fas fa-shopping-cart',10,'active'),(11,'communications','Communications','fas fa-envelope',11,'active'),(12,'reports','Reports','fas fa-file-alt',12,'active'),(13,'tools','Tools','fas fa-tools',13,'active'),(14,'admissions','Admissions','fas fa-door-open',14,'active'),(15,'human_resources','Human Resources','fas fa-users',15,'active'),(16,'ict','ICT Department','fas fa-laptop',16,'active'),(17,'security','Security & Transport','fas fa-shield-alt',17,'active'),(18,'library','Library','fas fa-book-open',18,'active'),(19,'nursing','Nursing Department','fas fa-user-md',19,'active'),(20,'midwifery','Midwifery Department','fas fa-baby',20,'active'),(21,'health_center','Health Center','fas fa-heartbeat',21,'active'),(22,'hostel','Hostel Management','fas fa-bed',22,'active'),(23,'store','Store & Assets','fas fa-boxes',23,'active'),(24,'transport','Transport','fas fa-truck',24,'active'),(25,'skills_lab','Skills Laboratory','fas fa-flask',25,'active'),(26,'computer_lab','Computer Lab','fas fa-desktop',26,'active'),(27,'guild','Student Government','fas fa-handshake',27,'active'),(28,'secretary','Secretary','fas fa-archive',28,'active'),(29,'student_welfare','Student Welfare','fas fa-heart',29,'active');
/*!40000 ALTER TABLE `menu_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menu_items`
--

DROP TABLE IF EXISTS `menu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `route` varchar(500) NOT NULL COMMENT 'File path or URL',
  `icon` varchar(100) DEFAULT 'fas fa-link',
  `sort_order` int(11) DEFAULT 0,
  `target` enum('self','blank') DEFAULT 'self',
  `requires_module` varchar(100) DEFAULT NULL COMMENT 'Module check like module_config key',
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `idx_mi_group` (`group_id`),
  KEY `idx_mi_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu_items`
--

LOCK TABLES `menu_items` WRITE;
/*!40000 ALTER TABLE `menu_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `menu_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menu_role_groups`
--

DROP TABLE IF EXISTS `menu_role_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `menu_role_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_group` (`role_id`,`group_id`),
  KEY `idx_mrg_group` (`group_id`)
) ENGINE=InnoDB AUTO_INCREMENT=229 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu_role_groups`
--

LOCK TABLES `menu_role_groups` WRITE;
/*!40000 ALTER TABLE `menu_role_groups` DISABLE KEYS */;
INSERT INTO `menu_role_groups` VALUES (8,1,1),(1,1,2),(2,1,3),(17,1,4),(26,1,5),(18,1,6),(19,1,7),(5,1,8),(3,1,9),(21,1,10),(6,1,11),(20,1,12),(28,1,13),(4,1,14),(12,1,15),(13,1,16),(23,1,17),(14,1,18),(16,1,19),(15,1,20),(10,1,21),(11,1,22),(25,1,23),(29,1,24),(24,1,25),(7,1,26),(9,1,27),(22,1,28),(27,1,29),(39,2,1),(32,2,2),(33,2,3),(48,2,4),(57,2,5),(49,2,6),(50,2,7),(36,2,8),(34,2,9),(52,2,10),(37,2,11),(51,2,12),(59,2,13),(35,2,14),(43,2,15),(44,2,16),(54,2,17),(45,2,18),(47,2,19),(46,2,20),(41,2,21),(42,2,22),(56,2,23),(60,2,24),(55,2,25),(38,2,26),(40,2,27),(53,2,28),(58,2,29),(74,3,1),(70,3,2),(71,3,3),(73,3,11),(75,3,12),(72,3,14),(80,4,1),(81,4,4),(86,4,5),(82,4,6),(83,4,7),(78,4,8),(77,4,9),(85,4,10),(79,4,11),(84,4,12),(94,5,1),(92,5,11),(96,5,12),(97,5,13),(95,5,16),(93,5,26),(101,6,1),(100,6,11),(102,6,12),(99,6,14),(110,7,1),(106,7,2),(107,7,3),(109,7,11),(116,7,12),(108,7,14),(112,7,15),(117,7,17),(113,7,18),(115,7,19),(114,7,20),(111,7,22),(118,7,23),(120,7,24),(119,7,29),(121,8,2),(122,8,3),(123,8,11),(125,8,12),(124,8,22),(126,8,29),(128,9,2),(129,9,3),(131,9,11),(132,9,12),(130,9,14),(138,10,4),(143,10,5),(139,10,6),(140,10,7),(136,10,8),(135,10,9),(142,10,10),(137,10,11),(141,10,12),(144,10,13),(151,11,11),(152,11,12),(150,11,14),(153,11,28),(154,11,29),(158,12,1),(157,12,11),(160,12,12),(159,12,15),(165,13,12),(164,13,18),(167,14,11),(170,14,12),(168,14,15),(169,14,19),(171,14,25),(174,15,11),(177,15,12),(175,15,15),(176,15,20),(178,15,25),(181,16,2),(182,16,12),(184,17,2),(185,17,12),(187,18,17),(188,19,23),(189,20,24),(190,21,21),(191,21,22),(192,21,29),(193,22,22),(194,22,29),(196,23,11),(197,23,27),(198,23,29),(199,24,21),(200,25,26),(201,26,25),(64,27,1),(63,27,11),(66,27,12),(67,27,13),(65,27,16);
/*!40000 ALTER TABLE `menu_role_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `menu_roles`
--

DROP TABLE IF EXISTS `menu_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `menu_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_key` varchar(100) NOT NULL COMMENT 'Lowercase role name like director general, registrar, lecturer',
  `display_name` varchar(200) NOT NULL,
  `dashboard_file` varchar(200) DEFAULT NULL COMMENT 'Primary dashboard file',
  `status` enum('active','inactive') DEFAULT 'active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_key` (`role_key`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `menu_roles`
--

LOCK TABLES `menu_roles` WRITE;
/*!40000 ALTER TABLE `menu_roles` DISABLE KEYS */;
INSERT INTO `menu_roles` VALUES (1,'director general','Director General','director-general.php','active'),(2,'ceo','Chief Executive Officer','ceo.php','active'),(3,'director academics','Director Academics','director-academics.php','active'),(4,'director finance','Director Finance','director-finance.php','active'),(5,'director ict','Director ICT','director-ict.php','active'),(6,'director admissions','Director Admissions','director-admissions.php','active'),(7,'school principal','School Principal','school-principal.php','active'),(8,'deputy principal','Deputy Principal','deputy-principal.php','active'),(9,'academic registrar','Academic Registrar','academic-registrar.php','active'),(10,'school bursar','School Bursar','school-bursar.php','active'),(11,'school secretary','School Secretary','school-secretary.php','active'),(12,'hr manager','HR Manager','hr-manager.php','active'),(13,'school librarian','School Librarian','school-librarian.php','active'),(14,'head of nursing','Head of Nursing','head-nursing.php','active'),(15,'head of midwifery','Head of Midwifery','head-midwifery.php','active'),(16,'senior lecturer','Senior Lecturer','senior-lecturers.php','active'),(17,'lecturer','Lecturer','lecturers.php','active'),(18,'security officer','Security Officer','security.php','active'),(19,'storekeeper','Storekeeper','storekeeper.php','active'),(20,'driver','Driver','drivers.php','active'),(21,'matron','Matron','matrons.php','active'),(22,'warden','Warden','wardens.php','active'),(23,'guild president','Guild President','guild-president.php','active'),(24,'sickbay nurse','Sickbay Nurse','sickbay.php','active'),(25,'computer lab','Computer Lab Manager','computer_lab.php','active'),(26,'skills lab','Skills Lab Technician','skills-lab.php','active'),(27,'system administrator','System Administrator','system-admin.php','active');
/*!40000 ALTER TABLE `menu_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `midwifery_antenatal_care`
--

DROP TABLE IF EXISTS `midwifery_antenatal_care`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `midwifery_antenatal_care` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `visit_date` date DEFAULT NULL,
  `gestational_age` varchar(50) DEFAULT NULL,
  `blood_pressure` varchar(20) DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT NULL,
  `fundal_height` decimal(5,1) DEFAULT NULL,
  `fetal_heart_rate` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `assessor_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `midwifery_antenatal_care`
--

LOCK TABLES `midwifery_antenatal_care` WRITE;
/*!40000 ALTER TABLE `midwifery_antenatal_care` DISABLE KEYS */;
/*!40000 ALTER TABLE `midwifery_antenatal_care` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `midwifery_clinical_placements`
--

DROP TABLE IF EXISTS `midwifery_clinical_placements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `midwifery_clinical_placements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `facility_name` varchar(200) NOT NULL,
  `department` varchar(100) DEFAULT 'Maternity',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `supervisor` varchar(120) DEFAULT NULL,
  `deliveries_observed` int(10) unsigned DEFAULT 0,
  `deliveries_assisted` int(10) unsigned DEFAULT 0,
  `status` enum('scheduled','in_progress','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `midwifery_clinical_placements`
--

LOCK TABLES `midwifery_clinical_placements` WRITE;
/*!40000 ALTER TABLE `midwifery_clinical_placements` DISABLE KEYS */;
/*!40000 ALTER TABLE `midwifery_clinical_placements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `midwifery_family_planning`
--

DROP TABLE IF EXISTS `midwifery_family_planning`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `midwifery_family_planning` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `method` varchar(100) DEFAULT NULL,
  `counseling_date` date DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `assessor_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `midwifery_family_planning`
--

LOCK TABLES `midwifery_family_planning` WRITE;
/*!40000 ALTER TABLE `midwifery_family_planning` DISABLE KEYS */;
/*!40000 ALTER TABLE `midwifery_family_planning` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `midwifery_labor_delivery`
--

DROP TABLE IF EXISTS `midwifery_labor_delivery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `midwifery_labor_delivery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `delivery_date` datetime DEFAULT NULL,
  `delivery_type` varchar(100) DEFAULT NULL,
  `baby_weight` decimal(5,2) DEFAULT NULL,
  `apgar_score` int(11) DEFAULT NULL,
  `complications` text DEFAULT NULL,
  `outcome` varchar(100) DEFAULT NULL,
  `assessor_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `midwifery_labor_delivery`
--

LOCK TABLES `midwifery_labor_delivery` WRITE;
/*!40000 ALTER TABLE `midwifery_labor_delivery` DISABLE KEYS */;
/*!40000 ALTER TABLE `midwifery_labor_delivery` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `midwifery_postnatal_care`
--

DROP TABLE IF EXISTS `midwifery_postnatal_care`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `midwifery_postnatal_care` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `patient_id` int(11) DEFAULT NULL,
  `visit_date` date DEFAULT NULL,
  `days_postpartum` int(11) DEFAULT NULL,
  `maternal_condition` text DEFAULT NULL,
  `baby_condition` text DEFAULT NULL,
  `breastfeeding_status` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `assessor_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `midwifery_postnatal_care`
--

LOCK TABLES `midwifery_postnatal_care` WRITE;
/*!40000 ALTER TABLE `midwifery_postnatal_care` DISABLE KEYS */;
/*!40000 ALTER TABLE `midwifery_postnatal_care` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `midwifery_skills_training`
--

DROP TABLE IF EXISTS `midwifery_skills_training`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `midwifery_skills_training` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `skill_name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `is_mandatory` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `midwifery_skills_training`
--

LOCK TABLES `midwifery_skills_training` WRITE;
/*!40000 ALTER TABLE `midwifery_skills_training` DISABLE KEYS */;
/*!40000 ALTER TABLE `midwifery_skills_training` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `midwifery_students`
--

DROP TABLE IF EXISTS `midwifery_students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `midwifery_students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `program` varchar(255) DEFAULT NULL,
  `cohort` varchar(50) DEFAULT NULL,
  `clinical_hours` int(11) DEFAULT 0,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `midwifery_students`
--

LOCK TABLES `midwifery_students` WRITE;
/*!40000 ALTER TABLE `midwifery_students` DISABLE KEYS */;
/*!40000 ALTER TABLE `midwifery_students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `module_audit_log`
--

DROP TABLE IF EXISTS `module_audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `module_audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL COMMENT 'view/create/edit/delete/approve',
  `record_id` int(11) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `module_id` (`module_id`),
  CONSTRAINT `module_audit_log_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `system_modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `module_audit_log`
--

LOCK TABLES `module_audit_log` WRITE;
/*!40000 ALTER TABLE `module_audit_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `module_audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `module_departments`
--

DROP TABLE IF EXISTS `module_departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `module_departments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `label` varchar(150) NOT NULL,
  `icon` varchar(50) DEFAULT 'building',
  `color` varchar(20) DEFAULT '#3b82f6',
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `module_departments`
--

LOCK TABLES `module_departments` WRITE;
/*!40000 ALTER TABLE `module_departments` DISABLE KEYS */;
INSERT INTO `module_departments` VALUES (1,'leadership','Leadership & Strategy','crown','#1e3a8a',1,1,'2026-06-30 18:31:20'),(2,'academic','Academic Affairs','book','#3b82f6',2,1,'2026-06-30 18:31:20'),(3,'finance','Finance & Accounts','money-bill','#10b981',3,1,'2026-06-30 18:31:20'),(4,'hr','HR & Administration','users','#8b5cf6',4,1,'2026-06-30 18:31:20'),(5,'student_services','Student Services','user-graduate','#f59e0b',5,1,'2026-06-30 18:31:20'),(6,'operations','Operations & Logistics','cogs','#6366f1',6,1,'2026-06-30 18:31:20'),(7,'compliance','Compliance & Quality','shield-alt','#ef4444',7,1,'2026-06-30 18:31:20'),(8,'clinical','Clinical & Health','heartbeat','#ef4444',8,1,'2026-06-30 18:31:20'),(9,'transport','Transport','bus','#f97316',9,1,'2026-06-30 18:31:20'),(10,'security','Security & Access','shield-alt','#64748b',10,1,'2026-06-30 18:31:20'),(11,'communication','Communications','envelope','#0ea5e9',11,1,'2026-06-30 18:31:20'),(12,'documents','Document Center','folder-open','#a855f7',12,1,'2026-06-30 18:31:20'),(13,'quality','Quality & Compliance','check-circle','#22c55e',13,1,'2026-06-30 18:31:20'),(14,'research','Research & Partners','flask','#06b6d4',14,1,'2026-06-30 18:31:20'),(15,'graduation','Graduation & Awards','graduation-cap','#eab308',15,1,'2026-06-30 18:31:20'),(16,'scholarships','Scholarships','award','#f43f5e',16,1,'2026-06-30 18:31:20'),(17,'procurement','Procurement','shopping-cart','#84cc16',17,1,'2026-06-30 18:31:20'),(18,'workflow','Tasks & Workflow','tasks','#a78bfa',18,1,'2026-06-30 18:31:20'),(19,'calendar','Calendar & Events','calendar-alt','#fb923c',19,1,'2026-06-30 18:31:20'),(20,'system','System Administration','cogs','#475569',20,1,'2026-06-30 18:31:20'),(21,'website','Website & Content','globe','#2dd4bf',21,1,'2026-06-30 18:31:20'),(22,'student_activities','Student Activities','trophy','#e879f9',22,1,'2026-06-30 18:31:20'),(23,'student_portal','Student Portal','graduation-cap','#3b82f6',23,1,'2026-06-30 18:31:20');
/*!40000 ALTER TABLE `module_departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `module_permissions`
--

DROP TABLE IF EXISTS `module_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `module_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `can_view` tinyint(1) DEFAULT 1,
  `can_create` tinyint(1) DEFAULT 0,
  `can_edit` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0,
  `can_approve` tinyint(1) DEFAULT 0,
  `can_export` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_module_role` (`module_id`,`role_id`),
  CONSTRAINT `module_permissions_ibfk_1` FOREIGN KEY (`module_id`) REFERENCES `system_modules` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=618 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `module_permissions`
--

LOCK TABLES `module_permissions` WRITE;
/*!40000 ALTER TABLE `module_permissions` DISABLE KEYS */;
INSERT INTO `module_permissions` VALUES (20,8,1,1,0,0,0,0,1,'2026-06-30 18:56:24'),(21,17,1,1,0,0,0,0,1,'2026-06-30 18:56:24'),(22,29,1,1,0,0,0,0,1,'2026-06-30 18:56:24'),(23,9,1,1,0,0,0,1,0,'2026-06-30 18:56:24'),(24,60,1,1,1,1,0,0,0,'2026-06-30 18:56:24'),(25,62,1,1,1,1,0,0,0,'2026-06-30 18:56:24'),(26,63,1,1,1,1,0,0,0,'2026-06-30 18:56:24'),(27,64,1,1,1,1,0,0,0,'2026-06-30 18:56:24'),(28,65,1,1,0,0,0,0,0,'2026-06-30 18:56:24'),(29,66,1,1,1,1,0,0,0,'2026-06-30 18:56:24'),(30,68,1,1,1,1,0,0,0,'2026-06-30 18:56:24'),(31,69,1,1,1,1,0,0,0,'2026-06-30 18:56:24'),(39,8,2,1,0,0,0,0,1,'2026-06-30 18:56:24'),(40,17,2,1,0,0,0,0,1,'2026-06-30 18:56:24'),(41,29,2,1,0,0,0,0,1,'2026-06-30 18:56:24'),(42,9,2,1,0,0,0,1,0,'2026-06-30 18:56:24'),(43,60,2,1,1,0,0,0,0,'2026-06-30 18:56:24'),(44,62,2,1,1,0,0,0,0,'2026-06-30 18:56:24'),(45,63,2,1,0,0,0,0,0,'2026-06-30 18:56:24'),(46,64,2,1,0,0,0,0,0,'2026-06-30 18:56:24'),(47,66,2,1,0,0,0,0,0,'2026-06-30 18:56:24'),(50,1,3,1,1,1,0,0,0,'2026-06-30 18:56:24'),(51,2,3,1,1,1,0,0,0,'2026-06-30 18:56:24'),(52,3,3,1,1,1,0,0,0,'2026-06-30 18:56:24'),(53,4,3,1,1,1,0,0,0,'2026-06-30 18:56:24'),(54,5,3,1,1,1,0,0,0,'2026-06-30 18:56:24'),(55,6,3,1,1,1,0,0,0,'2026-06-30 18:56:24'),(56,7,3,1,1,1,0,0,0,'2026-06-30 18:56:24'),(57,8,3,1,0,0,0,0,1,'2026-06-30 18:56:24'),(58,9,3,1,0,0,0,1,0,'2026-06-30 18:56:24'),(59,60,3,1,1,1,0,0,0,'2026-06-30 18:56:24'),(60,62,3,1,1,1,0,0,0,'2026-06-30 18:56:24'),(61,63,3,1,1,1,0,0,0,'2026-06-30 18:56:24'),(62,64,3,1,1,1,0,0,0,'2026-06-30 18:56:24'),(63,65,3,1,0,0,0,0,0,'2026-06-30 18:56:24'),(65,10,4,1,1,1,0,0,0,'2026-06-30 18:56:24'),(66,11,4,1,1,1,0,0,0,'2026-06-30 18:56:24'),(67,12,4,1,1,1,0,0,0,'2026-06-30 18:56:24'),(68,13,4,1,1,1,0,0,0,'2026-06-30 18:56:24'),(69,14,4,1,1,1,0,0,0,'2026-06-30 18:56:24'),(70,15,4,1,1,1,0,0,0,'2026-06-30 18:56:24'),(71,16,4,1,1,1,0,0,0,'2026-06-30 18:56:24'),(72,17,4,1,0,0,0,0,1,'2026-06-30 18:56:24'),(73,18,4,1,1,1,0,0,0,'2026-06-30 18:56:24'),(74,19,4,1,1,1,0,0,0,'2026-06-30 18:56:24'),(75,20,4,1,1,1,0,0,0,'2026-06-30 18:56:24'),(76,60,4,1,1,1,0,0,0,'2026-06-30 18:56:24'),(77,63,4,1,1,1,0,0,0,'2026-06-30 18:56:24'),(79,36,5,1,1,1,1,0,0,'2026-06-30 18:56:24'),(80,37,5,1,1,1,1,0,0,'2026-06-30 18:56:24'),(81,38,5,1,1,1,1,0,0,'2026-06-30 18:56:24'),(82,39,5,1,1,1,1,0,0,'2026-06-30 18:56:24'),(83,40,5,1,1,1,1,0,0,'2026-06-30 18:56:24'),(84,41,5,1,1,1,1,0,0,'2026-06-30 18:56:24'),(85,60,5,1,0,0,0,0,0,'2026-06-30 18:56:24'),(92,1,6,1,0,0,0,0,0,'2026-06-30 18:56:24'),(93,2,6,1,0,0,0,0,0,'2026-06-30 18:56:24'),(94,3,6,1,0,0,0,0,0,'2026-06-30 18:56:24'),(95,4,6,1,0,0,0,0,0,'2026-06-30 18:56:24'),(96,5,6,1,0,0,0,0,0,'2026-06-30 18:56:24'),(97,7,6,1,0,0,0,0,0,'2026-06-30 18:56:24'),(98,8,6,1,0,0,0,0,1,'2026-06-30 18:56:24'),(99,9,6,1,0,0,0,1,0,'2026-06-30 18:56:24'),(100,21,6,1,0,0,0,0,0,'2026-06-30 18:56:24'),(101,29,6,1,0,0,0,0,1,'2026-06-30 18:56:24'),(102,32,6,1,0,0,0,0,0,'2026-06-30 18:56:24'),(103,35,6,1,0,0,0,0,0,'2026-06-30 18:56:24'),(104,49,6,1,0,0,0,0,0,'2026-06-30 18:56:24'),(105,50,6,1,0,0,0,0,0,'2026-06-30 18:56:24'),(106,51,6,1,0,0,0,0,0,'2026-06-30 18:56:24'),(107,60,6,1,1,1,0,0,0,'2026-06-30 18:56:24'),(108,62,6,1,1,1,0,0,0,'2026-06-30 18:56:24'),(109,63,6,1,1,1,0,0,0,'2026-06-30 18:56:24'),(110,64,6,1,0,0,0,0,0,'2026-06-30 18:56:24'),(111,65,6,1,0,0,0,0,0,'2026-06-30 18:56:24'),(112,66,6,1,0,0,0,0,0,'2026-06-30 18:56:24'),(113,67,6,1,0,0,0,0,0,'2026-06-30 18:56:24'),(114,70,6,1,0,0,0,0,0,'2026-06-30 18:56:24'),(120,1,7,1,1,1,0,0,0,'2026-06-30 18:56:24'),(121,2,7,1,1,1,0,0,0,'2026-06-30 18:56:24'),(122,3,7,1,1,1,0,0,0,'2026-06-30 18:56:24'),(123,4,7,1,1,1,0,0,0,'2026-06-30 18:56:24'),(124,5,7,1,1,1,0,0,0,'2026-06-30 18:56:24'),(125,6,7,1,1,1,0,0,0,'2026-06-30 18:56:24'),(126,7,7,1,1,1,0,0,0,'2026-06-30 18:56:24'),(127,8,7,1,0,0,0,0,1,'2026-06-30 18:56:24'),(128,9,7,1,0,0,0,1,0,'2026-06-30 18:56:24'),(129,32,7,1,1,1,0,0,0,'2026-06-30 18:56:24'),(130,35,7,1,1,1,0,0,0,'2026-06-30 18:56:24'),(131,49,7,1,1,1,0,0,0,'2026-06-30 18:56:24'),(132,50,7,1,1,1,0,0,0,'2026-06-30 18:56:24'),(133,51,7,1,1,1,0,0,0,'2026-06-30 18:56:24'),(134,53,7,1,1,1,0,0,0,'2026-06-30 18:56:24'),(135,60,7,1,1,1,0,0,0,'2026-06-30 18:56:24'),(136,62,7,1,1,1,0,0,0,'2026-06-30 18:56:24'),(137,63,7,1,1,1,0,0,0,'2026-06-30 18:56:24'),(138,64,7,1,0,0,0,0,0,'2026-06-30 18:56:24'),(139,65,7,1,0,0,0,0,0,'2026-06-30 18:56:24'),(140,66,7,1,1,1,0,0,0,'2026-06-30 18:56:24'),(141,67,7,1,1,1,0,0,0,'2026-06-30 18:56:24'),(142,70,7,1,1,1,0,0,0,'2026-06-30 18:56:24'),(143,71,7,1,1,1,0,0,0,'2026-06-30 18:56:24'),(150,1,8,1,1,1,0,0,0,'2026-06-30 18:56:25'),(151,2,8,1,1,1,0,0,0,'2026-06-30 18:56:25'),(152,3,8,1,1,1,0,0,0,'2026-06-30 18:56:25'),(153,4,8,1,1,1,0,0,0,'2026-06-30 18:56:25'),(154,5,8,1,1,1,0,0,0,'2026-06-30 18:56:25'),(155,6,8,1,1,1,0,0,0,'2026-06-30 18:56:25'),(156,7,8,1,1,1,0,0,0,'2026-06-30 18:56:25'),(157,8,8,1,0,0,0,0,1,'2026-06-30 18:56:25'),(158,9,8,1,0,0,0,1,0,'2026-06-30 18:56:25'),(159,32,8,1,1,1,0,0,0,'2026-06-30 18:56:25'),(160,33,8,1,1,1,0,0,0,'2026-06-30 18:56:25'),(161,34,8,1,1,1,0,0,0,'2026-06-30 18:56:25'),(162,35,8,1,1,1,0,0,0,'2026-06-30 18:56:25'),(163,70,8,1,1,1,0,0,0,'2026-06-30 18:56:25'),(164,71,8,1,1,1,0,0,0,'2026-06-30 18:56:25'),(165,60,8,1,1,1,0,0,0,'2026-06-30 18:56:25'),(166,63,8,1,1,1,0,0,0,'2026-06-30 18:56:25'),(167,64,8,1,0,0,0,0,0,'2026-06-30 18:56:25'),(168,65,8,1,0,0,0,0,0,'2026-06-30 18:56:25'),(170,21,9,1,1,1,0,0,0,'2026-06-30 18:56:25'),(171,22,9,1,1,1,0,0,0,'2026-06-30 18:56:25'),(172,23,9,1,1,1,0,0,0,'2026-06-30 18:56:25'),(173,24,9,1,1,1,0,0,0,'2026-06-30 18:56:25'),(174,25,9,1,1,1,0,0,0,'2026-06-30 18:56:25'),(175,26,9,1,1,1,0,0,0,'2026-06-30 18:56:25'),(176,27,9,1,1,1,0,0,0,'2026-06-30 18:56:25'),(177,28,9,1,1,1,0,0,0,'2026-06-30 18:56:25'),(178,29,9,1,0,0,0,0,1,'2026-06-30 18:56:25'),(179,30,9,1,1,1,0,0,0,'2026-06-30 18:56:25'),(180,31,9,1,1,1,0,0,0,'2026-06-30 18:56:25'),(181,60,9,1,0,0,0,0,0,'2026-06-30 18:56:25'),(183,60,10,1,1,1,0,0,0,'2026-06-30 18:56:25'),(184,61,10,1,1,1,0,0,0,'2026-06-30 18:56:25'),(185,62,10,1,1,1,0,0,0,'2026-06-30 18:56:25'),(187,42,11,1,1,1,0,0,0,'2026-06-30 18:56:25'),(188,43,11,1,1,1,0,0,0,'2026-06-30 18:56:25'),(189,44,11,1,1,1,0,0,0,'2026-06-30 18:56:25'),(190,45,11,1,1,1,0,0,0,'2026-06-30 18:56:25'),(191,46,11,1,1,1,0,0,0,'2026-06-30 18:56:25'),(192,60,11,1,0,0,0,0,0,'2026-06-30 18:56:25'),(194,20,21,1,1,1,0,0,0,'2026-06-30 18:56:25'),(195,60,21,1,0,0,0,0,0,'2026-06-30 18:56:25'),(198,60,22,1,0,0,0,0,0,'2026-06-30 18:56:25'),(199,62,22,1,0,0,0,0,0,'2026-06-30 18:56:25'),(205,38,23,1,1,1,0,0,0,'2026-06-30 18:56:25'),(206,39,23,1,1,1,0,0,0,'2026-06-30 18:56:25'),(207,41,23,1,1,1,0,0,0,'2026-06-30 18:56:25'),(208,60,23,1,0,0,0,0,0,'2026-06-30 18:56:25'),(210,10,24,1,1,1,0,0,0,'2026-06-30 18:56:25'),(211,11,24,1,1,1,0,0,0,'2026-06-30 18:56:25'),(212,12,24,1,1,1,0,0,0,'2026-06-30 18:56:25'),(213,13,24,1,1,1,0,0,0,'2026-06-30 18:56:25'),(214,14,24,1,1,1,0,0,0,'2026-06-30 18:56:25'),(215,15,24,1,1,1,0,0,0,'2026-06-30 18:56:25'),(216,16,24,1,1,1,0,0,0,'2026-06-30 18:56:25'),(217,17,24,1,0,0,0,0,1,'2026-06-30 18:56:25'),(218,18,24,1,1,1,0,0,0,'2026-06-30 18:56:25'),(219,19,24,1,1,1,0,0,0,'2026-06-30 18:56:25'),(220,20,24,1,1,1,0,0,0,'2026-06-30 18:56:25'),(221,60,24,1,0,0,0,0,0,'2026-06-30 18:56:25'),(223,32,26,1,1,1,0,0,0,'2026-06-30 18:56:25'),(224,33,26,1,1,1,0,0,0,'2026-06-30 18:56:25'),(225,34,26,1,1,1,0,0,0,'2026-06-30 18:56:25'),(226,35,26,1,1,1,0,0,0,'2026-06-30 18:56:25'),(227,60,26,1,0,0,0,0,0,'2026-06-30 18:56:25'),(228,63,26,1,0,0,0,0,0,'2026-06-30 18:56:25'),(229,64,26,1,0,0,0,0,0,'2026-06-30 18:56:25'),(231,32,28,1,1,1,0,0,0,'2026-06-30 18:56:25'),(232,33,28,1,1,1,0,0,0,'2026-06-30 18:56:25'),(233,34,28,1,1,1,0,0,0,'2026-06-30 18:56:25'),(234,35,28,1,1,1,0,0,0,'2026-06-30 18:56:25'),(235,60,28,1,0,0,0,0,0,'2026-06-30 18:56:25'),(236,63,28,1,0,0,0,0,0,'2026-06-30 18:56:25'),(237,64,28,1,0,0,0,0,0,'2026-06-30 18:56:25'),(239,50,29,1,1,1,0,0,0,'2026-06-30 18:56:25'),(240,49,29,1,1,1,0,0,0,'2026-06-30 18:56:25'),(241,53,29,1,1,1,0,0,0,'2026-06-30 18:56:25'),(242,52,29,1,1,1,0,0,0,'2026-06-30 18:56:25'),(243,54,29,1,1,1,0,0,0,'2026-06-30 18:56:25'),(244,60,29,1,0,0,0,0,0,'2026-06-30 18:56:25'),(246,51,30,1,1,1,0,0,0,'2026-06-30 18:56:25'),(247,49,30,1,1,1,0,0,0,'2026-06-30 18:56:25'),(248,53,30,1,1,1,0,0,0,'2026-06-30 18:56:25'),(249,54,30,1,1,1,0,0,0,'2026-06-30 18:56:25'),(250,60,30,1,0,0,0,0,0,'2026-06-30 18:56:25'),(252,1,31,1,1,1,0,0,0,'2026-06-30 18:56:25'),(253,2,31,1,1,1,0,0,0,'2026-06-30 18:56:25'),(254,3,31,1,1,1,0,0,0,'2026-06-30 18:56:25'),(255,4,31,1,0,0,0,0,0,'2026-06-30 18:56:25'),(256,5,31,1,1,1,0,0,0,'2026-06-30 18:56:25'),(257,6,31,1,1,1,0,0,0,'2026-06-30 18:56:25'),(258,60,31,1,0,0,0,0,0,'2026-06-30 18:56:25'),(260,1,32,1,1,1,0,0,0,'2026-06-30 18:56:25'),(261,2,32,1,1,1,0,0,0,'2026-06-30 18:56:25'),(262,3,32,1,1,1,0,0,0,'2026-06-30 18:56:25'),(263,4,32,1,0,0,0,0,0,'2026-06-30 18:56:25'),(264,6,32,1,1,1,0,0,0,'2026-06-30 18:56:25'),(265,60,32,1,0,0,0,0,0,'2026-06-30 18:56:25'),(267,56,33,1,1,1,0,0,0,'2026-06-30 18:56:25'),(268,57,33,1,1,1,0,0,0,'2026-06-30 18:56:25'),(269,58,33,1,1,1,0,0,0,'2026-06-30 18:56:25'),(270,59,33,1,1,1,0,0,0,'2026-06-30 18:56:25'),(271,60,33,1,0,0,0,0,0,'2026-06-30 18:56:25'),(273,55,34,1,1,1,0,0,0,'2026-06-30 18:56:25'),(274,60,34,1,0,0,0,0,0,'2026-06-30 18:56:25'),(276,47,35,1,1,1,0,0,0,'2026-06-30 18:56:25'),(277,48,35,1,1,1,0,0,0,'2026-06-30 18:56:25'),(278,60,35,1,0,0,0,0,0,'2026-06-30 18:56:25'),(280,47,36,1,1,1,0,0,0,'2026-06-30 18:56:25'),(281,48,36,1,1,1,0,0,0,'2026-06-30 18:56:25'),(282,60,36,1,0,0,0,0,0,'2026-06-30 18:56:25'),(284,52,37,1,1,1,0,0,0,'2026-06-30 18:56:25'),(285,53,37,1,1,1,0,0,0,'2026-06-30 18:56:25'),(286,54,37,1,1,1,0,0,0,'2026-06-30 18:56:25'),(287,60,37,1,0,0,0,0,0,'2026-06-30 18:56:25'),(289,38,39,1,1,1,0,0,0,'2026-06-30 18:56:25'),(290,41,39,1,1,1,0,0,0,'2026-06-30 18:56:25'),(291,60,39,1,0,0,0,0,0,'2026-06-30 18:56:25'),(293,38,40,1,1,1,0,0,0,'2026-06-30 18:56:25'),(294,41,40,1,1,1,0,0,0,'2026-06-30 18:56:25'),(295,60,40,1,0,0,0,0,0,'2026-06-30 18:56:25'),(297,38,41,1,1,1,0,0,0,'2026-06-30 18:56:25'),(298,41,41,1,1,1,0,0,0,'2026-06-30 18:56:25'),(299,60,41,1,0,0,0,0,0,'2026-06-30 18:56:25'),(301,159,34,1,0,0,0,0,0,'2026-07-01 05:58:42'),(302,160,34,1,1,1,1,0,0,'2026-07-01 05:58:42'),(303,161,34,1,1,1,1,0,0,'2026-07-01 05:58:42'),(304,162,34,1,1,1,1,0,0,'2026-07-01 05:58:42'),(305,163,34,1,1,1,1,0,0,'2026-07-01 05:58:42'),(306,164,34,1,1,1,1,0,0,'2026-07-01 05:58:42'),(307,165,34,1,0,0,0,0,0,'2026-07-01 05:58:42'),(309,173,1,1,0,1,0,0,0,'2026-07-01 06:22:53'),(310,73,1,1,1,1,0,1,0,'2026-07-01 06:52:37'),(311,74,1,1,1,1,0,0,0,'2026-07-01 06:52:37'),(312,147,1,1,1,1,0,0,0,'2026-07-01 06:52:37'),(313,148,1,1,1,1,0,0,0,'2026-07-01 06:52:37'),(314,149,1,1,0,0,0,0,1,'2026-07-01 06:52:37'),(315,150,1,1,1,0,0,0,0,'2026-07-01 06:52:37'),(316,151,1,1,0,0,1,0,0,'2026-07-01 06:52:37'),(329,73,2,1,0,0,0,1,0,'2026-07-01 06:52:37'),(330,74,2,1,1,0,0,0,0,'2026-07-01 06:52:37'),(340,74,3,1,1,1,0,0,0,'2026-07-01 06:52:38'),(355,74,4,1,1,1,0,0,0,'2026-07-01 06:52:38'),(369,74,5,1,0,0,0,0,0,'2026-07-01 06:52:38'),(370,147,5,1,1,1,1,0,0,'2026-07-01 06:52:38'),(371,148,5,1,1,1,1,0,0,'2026-07-01 06:52:38'),(372,149,5,1,0,0,0,0,1,'2026-07-01 06:52:38'),(373,150,5,1,1,0,0,0,0,'2026-07-01 06:52:38'),(374,151,5,1,0,0,1,0,0,'2026-07-01 06:52:38'),(382,73,6,1,0,0,0,1,0,'2026-07-01 06:52:38'),(383,74,6,1,1,1,0,0,0,'2026-07-01 06:52:38'),(384,113,6,1,0,0,0,0,0,'2026-07-01 06:52:38'),(386,112,6,1,0,0,0,0,0,'2026-07-01 06:52:38'),(410,73,7,1,0,0,0,1,0,'2026-07-01 06:52:38'),(411,74,7,1,1,1,0,0,0,'2026-07-01 06:52:38'),(412,113,7,1,1,1,0,0,0,'2026-07-01 06:52:38'),(414,112,7,1,1,1,0,0,0,'2026-07-01 06:52:38'),(440,74,8,1,1,1,0,0,0,'2026-07-01 06:52:38'),(460,74,9,1,0,0,0,0,0,'2026-07-01 06:52:38'),(473,74,10,1,1,1,0,0,0,'2026-07-01 06:52:38'),(477,74,11,1,0,0,0,0,0,'2026-07-01 06:52:38'),(484,75,21,1,1,1,0,0,0,'2026-07-01 06:52:39'),(485,74,21,1,0,0,0,0,0,'2026-07-01 06:52:39'),(488,113,22,1,1,1,0,0,0,'2026-07-01 06:52:39'),(490,112,22,1,1,1,0,0,0,'2026-07-01 06:52:39'),(492,74,22,1,0,0,0,0,0,'2026-07-01 06:52:39'),(495,74,23,1,0,0,0,0,0,'2026-07-01 06:52:39'),(500,74,24,1,0,0,0,0,0,'2026-07-01 06:52:39'),(513,74,26,1,0,0,0,0,0,'2026-07-01 06:52:39'),(521,74,28,1,0,0,0,0,0,'2026-07-01 06:52:39'),(529,74,29,1,0,0,0,0,0,'2026-07-01 06:52:39'),(536,74,30,1,0,0,0,0,0,'2026-07-01 06:52:39'),(542,74,31,1,0,0,0,0,0,'2026-07-01 06:52:39'),(550,74,32,1,0,0,0,0,0,'2026-07-01 06:52:39'),(557,74,33,1,0,0,0,0,0,'2026-07-01 06:52:39'),(563,74,34,1,0,0,0,0,0,'2026-07-01 06:52:39'),(566,74,35,1,0,0,0,0,0,'2026-07-01 06:52:39'),(570,74,36,1,0,0,0,0,0,'2026-07-01 06:52:39'),(574,74,37,1,0,0,0,0,0,'2026-07-01 06:52:39'),(579,74,39,1,0,0,0,0,0,'2026-07-01 06:52:39'),(583,74,40,1,0,0,0,0,0,'2026-07-01 06:52:39'),(587,74,41,1,0,0,0,0,0,'2026-07-01 06:52:39'),(613,114,35,1,1,0,0,0,0,'2026-07-02 06:19:06'),(614,115,36,1,1,0,0,0,0,'2026-07-02 06:19:06'),(615,116,21,1,1,1,1,0,1,'2026-07-02 06:19:06'),(616,117,21,1,0,1,0,1,0,'2026-07-02 06:19:06'),(617,118,1,1,0,0,0,1,0,'2026-07-02 06:19:06');
/*!40000 ALTER TABLE `module_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `national_exam_results`
--

DROP TABLE IF EXISTS `national_exam_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `news_images`
--

DROP TABLE IF EXISTS `news_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `news_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `news_id` int(11) DEFAULT NULL,
  `image_path` varchar(500) DEFAULT NULL,
  `image_caption` varchar(255) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news_images`
--

LOCK TABLES `news_images` WRITE;
/*!40000 ALTER TABLE `news_images` DISABLE KEYS */;
/*!40000 ALTER TABLE `news_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `news_subscribers`
--

DROP TABLE IF EXISTS `news_subscribers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news_views`
--

LOCK TABLES `news_views` WRITE;
/*!40000 ALTER TABLE `news_views` DISABLE KEYS */;
INSERT INTO `news_views` VALUES (1,1,NULL,'public','197.239.12.138','2026-06-29 16:02:00'),(2,1,NULL,'public','197.239.12.138','2026-06-29 16:02:53'),(3,1,NULL,'public','197.239.12.138','2026-06-29 16:03:34'),(4,1,1,'staff','197.239.12.138','2026-06-29 16:08:43'),(5,2,1,'staff','197.239.12.138','2026-06-29 16:24:57'),(6,2,1,'staff','197.239.12.138','2026-06-29 16:25:21'),(7,2,NULL,'public','102.86.8.114','2026-07-01 18:53:14'),(8,2,NULL,'public','74.7.242.55','2026-07-02 04:04:13');
/*!40000 ALTER TABLE `news_views` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_reads`
--

DROP TABLE IF EXISTS `notification_reads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `type` varchar(50) DEFAULT 'info',
  `priority` varchar(20) DEFAULT 'normal',
  `audience` varchar(50) DEFAULT 'all',
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notif_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
INSERT INTO `notifications` VALUES (1,'New Contact: Admissions','Otema Reagan submitted a contact form regarding \"Admissions\". Email: reaganotema2022@gmail.com, Phone: +256772514889','form_submission','normal','director_general',0,'2026-07-01 12:32:53');
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nursing_clinical_logbook`
--

DROP TABLE IF EXISTS `nursing_clinical_logbook`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `nursing_clinical_logbook` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `placement_id` int(11) DEFAULT NULL,
  `log_date` date DEFAULT NULL,
  `shift_type` varchar(50) DEFAULT NULL,
  `hours` decimal(4,1) DEFAULT NULL,
  `activities` text DEFAULT NULL,
  `supervisor_signature` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nursing_clinical_logbook`
--

LOCK TABLES `nursing_clinical_logbook` WRITE;
/*!40000 ALTER TABLE `nursing_clinical_logbook` DISABLE KEYS */;
/*!40000 ALTER TABLE `nursing_clinical_logbook` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nursing_clinical_placements`
--

DROP TABLE IF EXISTS `nursing_clinical_placements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `nursing_clinical_placements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `facility_name` varchar(255) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `supervisor_name` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nursing_clinical_placements`
--

LOCK TABLES `nursing_clinical_placements` WRITE;
/*!40000 ALTER TABLE `nursing_clinical_placements` DISABLE KEYS */;
/*!40000 ALTER TABLE `nursing_clinical_placements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nursing_practical_assessment`
--

DROP TABLE IF EXISTS `nursing_practical_assessment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `nursing_practical_assessment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `assessment_type` varchar(100) DEFAULT NULL,
  `skill_area` varchar(255) DEFAULT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `max_score` decimal(5,2) DEFAULT NULL,
  `assessor_id` int(11) DEFAULT NULL,
  `assessment_date` date DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nursing_practical_assessment`
--

LOCK TABLES `nursing_practical_assessment` WRITE;
/*!40000 ALTER TABLE `nursing_practical_assessment` DISABLE KEYS */;
/*!40000 ALTER TABLE `nursing_practical_assessment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nursing_skills_training`
--

DROP TABLE IF EXISTS `nursing_skills_training`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `nursing_skills_training` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `skill_name` varchar(255) DEFAULT NULL,
  `skill_category` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `duration_hours` decimal(5,1) DEFAULT NULL,
  `max_participants` int(11) DEFAULT NULL,
  `instructor_id` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nursing_skills_training`
--

LOCK TABLES `nursing_skills_training` WRITE;
/*!40000 ALTER TABLE `nursing_skills_training` DISABLE KEYS */;
/*!40000 ALTER TABLE `nursing_skills_training` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `nursing_students`
--

DROP TABLE IF EXISTS `nursing_students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `nursing_students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `program` varchar(255) DEFAULT NULL,
  `cohort` varchar(50) DEFAULT NULL,
  `clinical_hours` int(11) DEFAULT 0,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `nursing_students`
--

LOCK TABLES `nursing_students` WRITE;
/*!40000 ALTER TABLE `nursing_students` DISABLE KEYS */;
/*!40000 ALTER TABLE `nursing_students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `official_duties`
--

DROP TABLE IF EXISTS `official_duties`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `official_duties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) DEFAULT NULL,
  `duty_title` varchar(255) DEFAULT NULL,
  `duty_description` text DEFAULT NULL,
  `duty_icon` varchar(100) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `official_duties`
--

LOCK TABLES `official_duties` WRITE;
/*!40000 ALTER TABLE `official_duties` DISABLE KEYS */;
/*!40000 ALTER TABLE `official_duties` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `onboarding_checklist`
--

DROP TABLE IF EXISTS `onboarding_checklist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `onboarding_checklist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_name` varchar(200) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_approvals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` int(11) NOT NULL,
  `payment_type` varchar(50) DEFAULT 'fee_payment',
  `requested_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approval_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approval_remarks` text DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
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
-- Table structure for table `payment_methods`
--

DROP TABLE IF EXISTS `payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_methods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `method_name` varchar(100) DEFAULT NULL,
  `method_code` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_methods`
--

LOCK TABLES `payment_methods` WRITE;
/*!40000 ALTER TABLE `payment_methods` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_methods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_records`
--

DROP TABLE IF EXISTS `payment_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `fee_account_id` int(11) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'Completed',
  `processed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_records`
--

LOCK TABLES `payment_records` WRITE;
/*!40000 ALTER TABLE `payment_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_routes`
--

DROP TABLE IF EXISTS `payment_routes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_routes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `route_name` varchar(100) DEFAULT NULL,
  `route_code` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payment_routes`
--

LOCK TABLES `payment_routes` WRITE;
/*!40000 ALTER TABLE `payment_routes` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_routes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_subscriptions`
--

DROP TABLE IF EXISTS `payment_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
INSERT INTO `payments` VALUES (1,1,4303623.00,0.00,'Cheque','2026-04-01','verified',NULL,'2026-06-19 23:58:56'),(2,1,1154598.00,0.00,'Mobile Money','2026-01-13','verified',NULL,'2026-06-19 23:58:56'),(3,1,2373654.00,0.00,'POS','2026-02-04','pending',NULL,'2026-06-19 23:58:56'),(4,1,903361.00,0.00,'Bank Transfer','2026-02-03','pending',NULL,'2026-06-19 23:58:56'),(5,1,516178.00,0.00,'Mobile Money','2026-04-15','approved',NULL,'2026-06-19 23:58:56'),(6,1,3369769.00,0.00,'Bank Transfer','2026-04-06','approved',NULL,'2026-06-19 23:58:56'),(7,1,1195561.00,0.00,'Bank Transfer','2026-02-28','verified',NULL,'2026-06-19 23:58:56'),(8,1,2818435.00,0.00,'Bank Transfer','2026-04-03','approved',NULL,'2026-06-19 23:58:56'),(9,1,1694306.00,0.00,'POS','2026-05-28','verified',NULL,'2026-06-19 23:58:56'),(10,1,1310012.00,0.00,'Bank Transfer','2026-05-23','pending',NULL,'2026-06-19 23:58:56'),(11,2,4079351.00,0.00,'Cheque','2026-01-18','approved',NULL,'2026-06-19 23:58:56'),(12,2,3786321.00,0.00,'Mobile Money','2026-05-14','approved',NULL,'2026-06-19 23:58:56'),(13,2,4845372.00,0.00,'Cheque','2026-06-12','verified',NULL,'2026-06-19 23:58:56'),(14,2,2205793.00,0.00,'Cheque','2026-02-07','verified',NULL,'2026-06-19 23:58:56'),(15,2,3532582.00,0.00,'Cheque','2026-02-11','pending',NULL,'2026-06-19 23:58:56'),(16,2,4559246.00,0.00,'POS','2026-01-07','pending',NULL,'2026-06-19 23:58:56'),(17,2,1664302.00,0.00,'Bank Transfer','2026-02-24','pending',NULL,'2026-06-19 23:58:56'),(18,2,231198.00,0.00,'Cash','2025-12-28','approved',NULL,'2026-06-19 23:58:56'),(19,2,371793.00,0.00,'Mobile Money','2025-12-30','pending',NULL,'2026-06-19 23:58:56'),(20,2,4921083.00,0.00,'Bank Transfer','2026-03-18','pending',NULL,'2026-06-19 23:58:56'),(21,3,1347820.00,0.00,'Cheque','2026-06-13','pending',NULL,'2026-06-19 23:58:56'),(22,3,679021.00,0.00,'Mobile Money','2026-03-04','approved',NULL,'2026-06-19 23:58:56'),(23,3,841699.00,0.00,'Cash','2025-12-25','pending',NULL,'2026-06-19 23:58:56'),(24,3,2118353.00,0.00,'Cash','2026-05-22','verified',NULL,'2026-06-19 23:58:56'),(25,3,1529731.00,0.00,'Bank Transfer','2026-01-03','verified',NULL,'2026-06-19 23:58:56'),(26,3,150061.00,0.00,'Cash','2026-05-06','approved',NULL,'2026-06-19 23:58:56'),(27,3,2099931.00,0.00,'Mobile Money','2026-01-17','approved',NULL,'2026-06-19 23:58:56'),(28,3,3984452.00,0.00,'Mobile Money','2026-04-29','verified',NULL,'2026-06-19 23:58:56'),(29,3,1757402.00,0.00,'Bank Transfer','2026-01-08','pending',NULL,'2026-06-19 23:58:56'),(30,3,2363593.00,0.00,'Cash','2026-04-15','pending',NULL,'2026-06-19 23:58:56'),(31,4,4897316.00,0.00,'Cash','2026-06-06','approved',NULL,'2026-06-19 23:58:56'),(32,4,4530396.00,0.00,'POS','2026-03-04','approved',NULL,'2026-06-19 23:58:56'),(33,4,2981352.00,0.00,'Bank Transfer','2026-01-17','pending',NULL,'2026-06-19 23:58:56'),(34,4,1748722.00,0.00,'Bank Transfer','2026-06-14','pending',NULL,'2026-06-19 23:58:56'),(35,4,231509.00,0.00,'Cheque','2026-01-22','pending',NULL,'2026-06-19 23:58:56'),(36,4,306115.00,0.00,'Cash','2026-01-13','approved',NULL,'2026-06-19 23:58:56'),(37,4,4653839.00,0.00,'Cheque','2026-04-17','pending',NULL,'2026-06-19 23:58:56'),(38,4,3217739.00,0.00,'Mobile Money','2026-04-10','approved',NULL,'2026-06-19 23:58:56'),(39,4,1228940.00,0.00,'Mobile Money','2026-05-09','pending',NULL,'2026-06-19 23:58:56'),(40,4,1651005.00,0.00,'Cheque','2026-01-06','approved',NULL,'2026-06-19 23:58:56'),(41,5,4721389.00,0.00,'POS','2026-02-09','approved',NULL,'2026-06-19 23:58:56'),(42,5,149174.00,0.00,'POS','2026-03-09','approved',NULL,'2026-06-19 23:58:56'),(43,5,617859.00,0.00,'Mobile Money','2025-12-25','approved',NULL,'2026-06-19 23:58:56'),(44,5,3024579.00,0.00,'POS','2025-12-30','approved',NULL,'2026-06-19 23:58:56'),(45,5,4439374.00,0.00,'Cheque','2026-05-05','verified',NULL,'2026-06-19 23:58:56'),(46,5,333072.00,0.00,'Mobile Money','2026-05-04','pending',NULL,'2026-06-19 23:58:56'),(47,5,3767992.00,0.00,'Cash','2026-06-20','pending',NULL,'2026-06-19 23:58:56'),(48,5,189456.00,0.00,'Cheque','2026-06-15','verified',NULL,'2026-06-19 23:58:56'),(49,5,3666993.00,0.00,'Cash','2026-04-25','approved',NULL,'2026-06-19 23:58:56'),(50,5,4837535.00,0.00,'POS','2026-03-31','approved',NULL,'2026-06-19 23:58:56');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_allowance_types`
--

DROP TABLE IF EXISTS `payroll_allowance_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_allowance_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `allowance_code` varchar(20) NOT NULL,
  `allowance_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_taxable` tinyint(1) DEFAULT 1,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_allowance_code` (`allowance_code`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_allowance_types`
--

LOCK TABLES `payroll_allowance_types` WRITE;
/*!40000 ALTER TABLE `payroll_allowance_types` DISABLE KEYS */;
INSERT INTO `payroll_allowance_types` VALUES (1,'HRA','Housing Allowance',NULL,1,'active','2026-06-29 13:37:57'),(2,'TRANSPORT','Transport Allowance',NULL,1,'active','2026-06-29 13:37:57'),(3,'MEDICAL','Medical Allowance',NULL,0,'active','2026-06-29 13:37:57'),(4,'LUNCH','Lunch Allowance',NULL,1,'active','2026-06-29 13:37:57'),(5,'UTILITY','Utility Allowance',NULL,1,'active','2026-06-29 13:37:57');
/*!40000 ALTER TABLE `payroll_allowance_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_allowances`
--

DROP TABLE IF EXISTS `payroll_allowances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=79 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_allowances`
--

LOCK TABLES `payroll_allowances` WRITE;
/*!40000 ALTER TABLE `payroll_allowances` DISABLE KEYS */;
INSERT INTO `payroll_allowances` VALUES (1,7,'Medical Allowance',450000.00,'January',1,1,'2026-07-02 08:09:39'),(2,51,'Medical Allowance',180000.00,'January',1,1,'2026-07-02 08:09:39'),(3,2,'Transport Allowance',750000.00,'January',1,1,'2026-07-02 08:09:39'),(4,22,'Transport Allowance',75000.00,'January',1,1,'2026-07-02 08:09:39'),(5,6,'Housing Allowance',400000.00,'January',1,1,'2026-07-02 08:09:39'),(6,3,'Transport Allowance',380000.00,'January',1,1,'2026-07-02 08:09:39'),(7,24,'Transport Allowance',180000.00,'January',1,1,'2026-07-02 08:09:39'),(8,4,'Medical Allowance',350000.00,'January',1,1,'2026-07-02 08:09:39'),(9,1,'Housing Allowance',275000.00,'January',1,1,'2026-07-02 08:09:39'),(10,23,'Transport Allowance',175000.00,'January',1,1,'2026-07-02 08:09:39'),(11,18,'Transport Allowance',180000.00,'January',1,1,'2026-07-02 08:09:39'),(12,21,'Transport Allowance',150000.00,'January',1,1,'2026-07-02 08:09:39'),(13,12,'Housing Allowance',60000.00,'January',1,1,'2026-07-02 08:09:39'),(14,11,'Medical Allowance',180000.00,'January',1,1,'2026-07-02 08:09:39'),(15,8,'Housing Allowance',150000.00,'January',1,1,'2026-07-02 08:09:39'),(16,14,'Transport Allowance',120000.00,'January',1,1,'2026-07-02 08:09:39'),(17,15,'Medical Allowance',180000.00,'January',1,1,'2026-07-02 08:09:39'),(18,25,'Medical Allowance',450000.00,'January',1,1,'2026-07-02 08:09:39'),(19,10,'Medical Allowance',150000.00,'January',1,1,'2026-07-02 08:09:39'),(20,5,'Transport Allowance',750000.00,'January',1,1,'2026-07-02 08:09:39'),(21,9,'Transport Allowance',150000.00,'January',1,1,'2026-07-02 08:09:39'),(22,19,'Transport Allowance',60000.00,'January',1,1,'2026-07-02 08:09:39'),(23,13,'Transport Allowance',120000.00,'January',1,1,'2026-07-02 08:09:39'),(24,17,'Medical Allowance',60000.00,'January',1,1,'2026-07-02 08:09:39'),(25,20,'Medical Allowance',180000.00,'January',1,1,'2026-07-02 08:09:39'),(26,16,'Housing Allowance',120000.00,'January',1,1,'2026-07-02 08:09:39'),(27,7,'Housing Allowance',300000.00,'January',1,1,'2026-07-03 04:37:04'),(28,51,'Transport Allowance',180000.00,'January',1,1,'2026-07-03 04:37:04'),(29,2,'Transport Allowance',250000.00,'January',1,1,'2026-07-03 04:37:04'),(30,22,'Transport Allowance',150000.00,'January',1,1,'2026-07-03 04:37:04'),(31,6,'Medical Allowance',400000.00,'January',1,1,'2026-07-03 04:37:04'),(32,3,'Transport Allowance',380000.00,'January',1,1,'2026-07-03 04:37:04'),(33,24,'Transport Allowance',180000.00,'January',1,1,'2026-07-03 04:37:04'),(34,4,'Housing Allowance',350000.00,'January',1,1,'2026-07-03 04:37:04'),(35,1,'Medical Allowance',550000.00,'January',1,1,'2026-07-03 04:37:04'),(36,23,'Medical Allowance',350000.00,'January',1,1,'2026-07-03 04:37:04'),(37,18,'Transport Allowance',120000.00,'January',1,1,'2026-07-03 04:37:04'),(38,21,'Transport Allowance',100000.00,'January',1,1,'2026-07-03 04:37:04'),(39,12,'Transport Allowance',120000.00,'January',1,1,'2026-07-03 04:37:04'),(40,11,'Medical Allowance',120000.00,'January',1,1,'2026-07-03 04:37:04'),(41,8,'Housing Allowance',450000.00,'January',1,1,'2026-07-03 04:37:04'),(42,14,'Medical Allowance',180000.00,'January',1,1,'2026-07-03 04:37:04'),(43,15,'Housing Allowance',180000.00,'January',1,1,'2026-07-03 04:37:04'),(44,25,'Medical Allowance',450000.00,'January',1,1,'2026-07-03 04:37:04'),(45,10,'Transport Allowance',225000.00,'January',1,1,'2026-07-03 04:37:04'),(46,5,'Transport Allowance',500000.00,'January',1,1,'2026-07-03 04:37:04'),(47,9,'Transport Allowance',225000.00,'January',1,1,'2026-07-03 04:37:04'),(48,19,'Housing Allowance',60000.00,'January',1,1,'2026-07-03 04:37:04'),(49,13,'Transport Allowance',180000.00,'January',1,1,'2026-07-03 04:37:04'),(50,17,'Transport Allowance',60000.00,'January',1,1,'2026-07-03 04:37:04'),(51,20,'Medical Allowance',180000.00,'January',1,1,'2026-07-03 04:37:04'),(52,16,'Housing Allowance',120000.00,'January',1,1,'2026-07-03 04:37:04'),(53,7,'Housing Allowance',150000.00,'January',1,1,'2026-07-03 05:16:23'),(54,51,'Transport Allowance',120000.00,'January',1,1,'2026-07-03 05:16:23'),(55,2,'Transport Allowance',250000.00,'January',1,1,'2026-07-03 05:16:23'),(56,22,'Transport Allowance',75000.00,'January',1,1,'2026-07-03 05:16:23'),(57,6,'Transport Allowance',200000.00,'January',1,1,'2026-07-03 05:16:23'),(58,3,'Housing Allowance',190000.00,'January',1,1,'2026-07-03 05:16:23'),(59,24,'Transport Allowance',180000.00,'January',1,1,'2026-07-03 05:16:23'),(60,4,'Housing Allowance',350000.00,'January',1,1,'2026-07-03 05:16:23'),(61,1,'Housing Allowance',550000.00,'January',1,1,'2026-07-03 05:16:23'),(62,23,'Housing Allowance',350000.00,'January',1,1,'2026-07-03 05:16:23'),(63,18,'Transport Allowance',120000.00,'January',1,1,'2026-07-03 05:16:23'),(64,21,'Medical Allowance',100000.00,'January',1,1,'2026-07-03 05:16:23'),(65,12,'Transport Allowance',60000.00,'January',1,1,'2026-07-03 05:16:23'),(66,11,'Transport Allowance',180000.00,'January',1,1,'2026-07-03 05:16:23'),(67,8,'Medical Allowance',300000.00,'January',1,1,'2026-07-03 05:16:23'),(68,14,'Housing Allowance',180000.00,'January',1,1,'2026-07-03 05:16:23'),(69,15,'Housing Allowance',120000.00,'January',1,1,'2026-07-03 05:16:23'),(70,25,'Housing Allowance',300000.00,'January',1,1,'2026-07-03 05:16:23'),(71,10,'Medical Allowance',225000.00,'January',1,1,'2026-07-03 05:16:23'),(72,5,'Housing Allowance',500000.00,'January',1,1,'2026-07-03 05:16:23'),(73,9,'Housing Allowance',75000.00,'January',1,1,'2026-07-03 05:16:23'),(74,19,'Housing Allowance',60000.00,'January',1,1,'2026-07-03 05:16:23'),(75,13,'Transport Allowance',180000.00,'January',1,1,'2026-07-03 05:16:23'),(76,17,'Housing Allowance',180000.00,'January',1,1,'2026-07-03 05:16:23'),(77,20,'Medical Allowance',60000.00,'January',1,1,'2026-07-03 05:16:23'),(78,16,'Medical Allowance',180000.00,'January',1,1,'2026-07-03 05:16:23');
/*!40000 ALTER TABLE `payroll_allowances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_approval_history`
--

DROP TABLE IF EXISTS `payroll_approval_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_approval_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `step` varchar(100) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `acted_by` int(11) DEFAULT NULL,
  `acted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_approval_entity` (`entity_type`,`entity_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_approval_history`
--

LOCK TABLES `payroll_approval_history` WRITE;
/*!40000 ALTER TABLE `payroll_approval_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_approval_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_approvals`
--

DROP TABLE IF EXISTS `payroll_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `payroll_audit_logs`
--

DROP TABLE IF EXISTS `payroll_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` int(11) DEFAULT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_audit_logs`
--

LOCK TABLES `payroll_audit_logs` WRITE;
/*!40000 ALTER TABLE `payroll_audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_bonus`
--

DROP TABLE IF EXISTS `payroll_bonus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_bonus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payroll_employee_id` int(11) DEFAULT NULL,
  `bonus_type` varchar(100) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT 0.00,
  `bonus_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pb_employee` (`payroll_employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_bonus`
--

LOCK TABLES `payroll_bonus` WRITE;
/*!40000 ALTER TABLE `payroll_bonus` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_bonus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_bonuses`
--

DROP TABLE IF EXISTS `payroll_bonuses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `payroll_deduction_types`
--

DROP TABLE IF EXISTS `payroll_deduction_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_deduction_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `deduction_code` varchar(20) NOT NULL,
  `deduction_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_statutory` tinyint(1) DEFAULT 0,
  `category` varchar(50) DEFAULT 'other',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_deduction_code` (`deduction_code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_deduction_types`
--

LOCK TABLES `payroll_deduction_types` WRITE;
/*!40000 ALTER TABLE `payroll_deduction_types` DISABLE KEYS */;
INSERT INTO `payroll_deduction_types` VALUES (1,'NSSF','NSSF Employee',NULL,1,'statutory','active','2026-06-29 13:37:57'),(2,'PAYE','PAYE Tax',NULL,1,'statutory','active','2026-06-29 13:37:57'),(3,'LOAN','Staff Loan',NULL,0,'voluntary','active','2026-06-29 13:37:57'),(4,'ADVANCE','Salary Advance',NULL,0,'voluntary','active','2026-06-29 13:37:57');
/*!40000 ALTER TABLE `payroll_deduction_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_deductions`
--

DROP TABLE IF EXISTS `payroll_deductions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=157 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_deductions`
--

LOCK TABLES `payroll_deductions` WRITE;
/*!40000 ALTER TABLE `payroll_deductions` DISABLE KEYS */;
INSERT INTO `payroll_deductions` VALUES (1,7,'NSSF',300000.00,'January',1,1,'2026-07-02 08:09:39'),(2,51,'NSSF',120000.00,'January',1,1,'2026-07-02 08:09:39'),(3,2,'NSSF',500000.00,'January',1,1,'2026-07-02 08:09:39'),(4,22,'NSSF',150000.00,'January',1,1,'2026-07-02 08:09:39'),(5,6,'NSSF',400000.00,'January',1,1,'2026-07-02 08:09:39'),(6,3,'NSSF',380000.00,'January',1,1,'2026-07-02 08:09:39'),(7,24,'NSSF',120000.00,'January',1,1,'2026-07-02 08:09:39'),(8,4,'NSSF',350000.00,'January',1,1,'2026-07-02 08:09:39'),(9,1,'NSSF',550000.00,'January',1,1,'2026-07-02 08:09:39'),(10,23,'NSSF',350000.00,'January',1,1,'2026-07-02 08:09:39'),(11,18,'NSSF',120000.00,'January',1,1,'2026-07-02 08:09:39'),(12,21,'NSSF',100000.00,'January',1,1,'2026-07-02 08:09:39'),(13,12,'NSSF',120000.00,'January',1,1,'2026-07-02 08:09:39'),(14,11,'NSSF',120000.00,'January',1,1,'2026-07-02 08:09:39'),(15,8,'NSSF',300000.00,'January',1,1,'2026-07-02 08:09:39'),(16,14,'NSSF',120000.00,'January',1,1,'2026-07-02 08:09:39'),(17,15,'NSSF',120000.00,'January',1,1,'2026-07-02 08:09:39'),(18,25,'NSSF',300000.00,'January',1,1,'2026-07-02 08:09:39'),(19,10,'NSSF',150000.00,'January',1,1,'2026-07-02 08:09:39'),(20,5,'NSSF',500000.00,'January',1,1,'2026-07-02 08:09:39'),(21,9,'NSSF',150000.00,'January',1,1,'2026-07-02 08:09:39'),(22,19,'NSSF',120000.00,'January',1,1,'2026-07-02 08:09:39'),(23,13,'NSSF',120000.00,'January',1,1,'2026-07-02 08:09:39'),(24,17,'NSSF',120000.00,'January',1,1,'2026-07-02 08:09:39'),(25,20,'NSSF',120000.00,'January',1,1,'2026-07-02 08:09:39'),(26,16,'NSSF',120000.00,'January',1,1,'2026-07-02 08:09:39'),(27,7,'PAYE',360000.00,'January',1,1,'2026-07-02 08:09:39'),(28,51,'PAYE',144000.00,'January',1,1,'2026-07-02 08:09:39'),(29,2,'PAYE',600000.00,'January',1,1,'2026-07-02 08:09:39'),(30,22,'PAYE',180000.00,'January',1,1,'2026-07-02 08:09:39'),(31,6,'PAYE',480000.00,'January',1,1,'2026-07-02 08:09:39'),(32,3,'PAYE',456000.00,'January',1,1,'2026-07-02 08:09:39'),(33,24,'PAYE',144000.00,'January',1,1,'2026-07-02 08:09:39'),(34,4,'PAYE',420000.00,'January',1,1,'2026-07-02 08:09:39'),(35,1,'PAYE',660000.00,'January',1,1,'2026-07-02 08:09:39'),(36,23,'PAYE',420000.00,'January',1,1,'2026-07-02 08:09:39'),(37,18,'PAYE',144000.00,'January',1,1,'2026-07-02 08:09:39'),(38,21,'PAYE',120000.00,'January',1,1,'2026-07-02 08:09:39'),(39,12,'PAYE',144000.00,'January',1,1,'2026-07-02 08:09:39'),(40,11,'PAYE',144000.00,'January',1,1,'2026-07-02 08:09:39'),(41,8,'PAYE',360000.00,'January',1,1,'2026-07-02 08:09:39'),(42,14,'PAYE',144000.00,'January',1,1,'2026-07-02 08:09:39'),(43,15,'PAYE',144000.00,'January',1,1,'2026-07-02 08:09:39'),(44,25,'PAYE',360000.00,'January',1,1,'2026-07-02 08:09:39'),(45,10,'PAYE',180000.00,'January',1,1,'2026-07-02 08:09:39'),(46,5,'PAYE',600000.00,'January',1,1,'2026-07-02 08:09:39'),(47,9,'PAYE',180000.00,'January',1,1,'2026-07-02 08:09:39'),(48,19,'PAYE',144000.00,'January',1,1,'2026-07-02 08:09:39'),(49,13,'PAYE',144000.00,'January',1,1,'2026-07-02 08:09:39'),(50,17,'PAYE',144000.00,'January',1,1,'2026-07-02 08:09:39'),(51,20,'PAYE',144000.00,'January',1,1,'2026-07-02 08:09:39'),(52,16,'PAYE',144000.00,'January',1,1,'2026-07-02 08:09:39'),(53,7,'NSSF',300000.00,'January',1,1,'2026-07-03 04:37:04'),(54,51,'NSSF',120000.00,'January',1,1,'2026-07-03 04:37:04'),(55,2,'NSSF',500000.00,'January',1,1,'2026-07-03 04:37:04'),(56,22,'NSSF',150000.00,'January',1,1,'2026-07-03 04:37:04'),(57,6,'NSSF',400000.00,'January',1,1,'2026-07-03 04:37:04'),(58,3,'NSSF',380000.00,'January',1,1,'2026-07-03 04:37:04'),(59,24,'NSSF',120000.00,'January',1,1,'2026-07-03 04:37:04'),(60,4,'NSSF',350000.00,'January',1,1,'2026-07-03 04:37:04'),(61,1,'NSSF',550000.00,'January',1,1,'2026-07-03 04:37:04'),(62,23,'NSSF',350000.00,'January',1,1,'2026-07-03 04:37:04'),(63,18,'NSSF',120000.00,'January',1,1,'2026-07-03 04:37:04'),(64,21,'NSSF',100000.00,'January',1,1,'2026-07-03 04:37:04'),(65,12,'NSSF',120000.00,'January',1,1,'2026-07-03 04:37:04'),(66,11,'NSSF',120000.00,'January',1,1,'2026-07-03 04:37:04'),(67,8,'NSSF',300000.00,'January',1,1,'2026-07-03 04:37:04'),(68,14,'NSSF',120000.00,'January',1,1,'2026-07-03 04:37:04'),(69,15,'NSSF',120000.00,'January',1,1,'2026-07-03 04:37:04'),(70,25,'NSSF',300000.00,'January',1,1,'2026-07-03 04:37:04'),(71,10,'NSSF',150000.00,'January',1,1,'2026-07-03 04:37:04'),(72,5,'NSSF',500000.00,'January',1,1,'2026-07-03 04:37:04'),(73,9,'NSSF',150000.00,'January',1,1,'2026-07-03 04:37:04'),(74,19,'NSSF',120000.00,'January',1,1,'2026-07-03 04:37:04'),(75,13,'NSSF',120000.00,'January',1,1,'2026-07-03 04:37:04'),(76,17,'NSSF',120000.00,'January',1,1,'2026-07-03 04:37:04'),(77,20,'NSSF',120000.00,'January',1,1,'2026-07-03 04:37:04'),(78,16,'NSSF',120000.00,'January',1,1,'2026-07-03 04:37:04'),(79,7,'PAYE',360000.00,'January',1,1,'2026-07-03 04:37:04'),(80,51,'PAYE',144000.00,'January',1,1,'2026-07-03 04:37:04'),(81,2,'PAYE',600000.00,'January',1,1,'2026-07-03 04:37:04'),(82,22,'PAYE',180000.00,'January',1,1,'2026-07-03 04:37:04'),(83,6,'PAYE',480000.00,'January',1,1,'2026-07-03 04:37:04'),(84,3,'PAYE',456000.00,'January',1,1,'2026-07-03 04:37:04'),(85,24,'PAYE',144000.00,'January',1,1,'2026-07-03 04:37:04'),(86,4,'PAYE',420000.00,'January',1,1,'2026-07-03 04:37:04'),(87,1,'PAYE',660000.00,'January',1,1,'2026-07-03 04:37:04'),(88,23,'PAYE',420000.00,'January',1,1,'2026-07-03 04:37:04'),(89,18,'PAYE',144000.00,'January',1,1,'2026-07-03 04:37:04'),(90,21,'PAYE',120000.00,'January',1,1,'2026-07-03 04:37:04'),(91,12,'PAYE',144000.00,'January',1,1,'2026-07-03 04:37:04'),(92,11,'PAYE',144000.00,'January',1,1,'2026-07-03 04:37:04'),(93,8,'PAYE',360000.00,'January',1,1,'2026-07-03 04:37:04'),(94,14,'PAYE',144000.00,'January',1,1,'2026-07-03 04:37:04'),(95,15,'PAYE',144000.00,'January',1,1,'2026-07-03 04:37:04'),(96,25,'PAYE',360000.00,'January',1,1,'2026-07-03 04:37:04'),(97,10,'PAYE',180000.00,'January',1,1,'2026-07-03 04:37:04'),(98,5,'PAYE',600000.00,'January',1,1,'2026-07-03 04:37:04'),(99,9,'PAYE',180000.00,'January',1,1,'2026-07-03 04:37:04'),(100,19,'PAYE',144000.00,'January',1,1,'2026-07-03 04:37:04'),(101,13,'PAYE',144000.00,'January',1,1,'2026-07-03 04:37:04'),(102,17,'PAYE',144000.00,'January',1,1,'2026-07-03 04:37:04'),(103,20,'PAYE',144000.00,'January',1,1,'2026-07-03 04:37:04'),(104,16,'PAYE',144000.00,'January',1,1,'2026-07-03 04:37:04'),(105,7,'NSSF',300000.00,'January',1,1,'2026-07-03 05:16:23'),(106,51,'NSSF',120000.00,'January',1,1,'2026-07-03 05:16:23'),(107,2,'NSSF',500000.00,'January',1,1,'2026-07-03 05:16:23'),(108,22,'NSSF',150000.00,'January',1,1,'2026-07-03 05:16:23'),(109,6,'NSSF',400000.00,'January',1,1,'2026-07-03 05:16:23'),(110,3,'NSSF',380000.00,'January',1,1,'2026-07-03 05:16:23'),(111,24,'NSSF',120000.00,'January',1,1,'2026-07-03 05:16:23'),(112,4,'NSSF',350000.00,'January',1,1,'2026-07-03 05:16:23'),(113,1,'NSSF',550000.00,'January',1,1,'2026-07-03 05:16:23'),(114,23,'NSSF',350000.00,'January',1,1,'2026-07-03 05:16:23'),(115,18,'NSSF',120000.00,'January',1,1,'2026-07-03 05:16:23'),(116,21,'NSSF',100000.00,'January',1,1,'2026-07-03 05:16:23'),(117,12,'NSSF',120000.00,'January',1,1,'2026-07-03 05:16:23'),(118,11,'NSSF',120000.00,'January',1,1,'2026-07-03 05:16:23'),(119,8,'NSSF',300000.00,'January',1,1,'2026-07-03 05:16:23'),(120,14,'NSSF',120000.00,'January',1,1,'2026-07-03 05:16:23'),(121,15,'NSSF',120000.00,'January',1,1,'2026-07-03 05:16:23'),(122,25,'NSSF',300000.00,'January',1,1,'2026-07-03 05:16:23'),(123,10,'NSSF',150000.00,'January',1,1,'2026-07-03 05:16:23'),(124,5,'NSSF',500000.00,'January',1,1,'2026-07-03 05:16:23'),(125,9,'NSSF',150000.00,'January',1,1,'2026-07-03 05:16:23'),(126,19,'NSSF',120000.00,'January',1,1,'2026-07-03 05:16:23'),(127,13,'NSSF',120000.00,'January',1,1,'2026-07-03 05:16:23'),(128,17,'NSSF',120000.00,'January',1,1,'2026-07-03 05:16:23'),(129,20,'NSSF',120000.00,'January',1,1,'2026-07-03 05:16:23'),(130,16,'NSSF',120000.00,'January',1,1,'2026-07-03 05:16:23'),(131,7,'PAYE',360000.00,'January',1,1,'2026-07-03 05:16:23'),(132,51,'PAYE',144000.00,'January',1,1,'2026-07-03 05:16:23'),(133,2,'PAYE',600000.00,'January',1,1,'2026-07-03 05:16:23'),(134,22,'PAYE',180000.00,'January',1,1,'2026-07-03 05:16:23'),(135,6,'PAYE',480000.00,'January',1,1,'2026-07-03 05:16:23'),(136,3,'PAYE',456000.00,'January',1,1,'2026-07-03 05:16:23'),(137,24,'PAYE',144000.00,'January',1,1,'2026-07-03 05:16:23'),(138,4,'PAYE',420000.00,'January',1,1,'2026-07-03 05:16:23'),(139,1,'PAYE',660000.00,'January',1,1,'2026-07-03 05:16:23'),(140,23,'PAYE',420000.00,'January',1,1,'2026-07-03 05:16:23'),(141,18,'PAYE',144000.00,'January',1,1,'2026-07-03 05:16:23'),(142,21,'PAYE',120000.00,'January',1,1,'2026-07-03 05:16:23'),(143,12,'PAYE',144000.00,'January',1,1,'2026-07-03 05:16:23'),(144,11,'PAYE',144000.00,'January',1,1,'2026-07-03 05:16:23'),(145,8,'PAYE',360000.00,'January',1,1,'2026-07-03 05:16:23'),(146,14,'PAYE',144000.00,'January',1,1,'2026-07-03 05:16:23'),(147,15,'PAYE',144000.00,'January',1,1,'2026-07-03 05:16:23'),(148,25,'PAYE',360000.00,'January',1,1,'2026-07-03 05:16:23'),(149,10,'PAYE',180000.00,'January',1,1,'2026-07-03 05:16:23'),(150,5,'PAYE',600000.00,'January',1,1,'2026-07-03 05:16:23'),(151,9,'PAYE',180000.00,'January',1,1,'2026-07-03 05:16:23'),(152,19,'PAYE',144000.00,'January',1,1,'2026-07-03 05:16:23'),(153,13,'PAYE',144000.00,'January',1,1,'2026-07-03 05:16:23'),(154,17,'PAYE',144000.00,'January',1,1,'2026-07-03 05:16:23'),(155,20,'PAYE',144000.00,'January',1,1,'2026-07-03 05:16:23'),(156,16,'PAYE',144000.00,'January',1,1,'2026-07-03 05:16:23');
/*!40000 ALTER TABLE `payroll_deductions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_details`
--

DROP TABLE IF EXISTS `payroll_details`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `payroll_employee_allowances`
--

DROP TABLE IF EXISTS `payroll_employee_allowances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_employee_allowances` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payroll_employee_id` int(11) NOT NULL,
  `allowance_type_id` int(11) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `is_taxable` tinyint(1) DEFAULT 1,
  `is_recurring` tinyint(1) DEFAULT 1,
  `effective_from` date DEFAULT NULL,
  `effective_to` date DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pea_employee` (`payroll_employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_employee_allowances`
--

LOCK TABLES `payroll_employee_allowances` WRITE;
/*!40000 ALTER TABLE `payroll_employee_allowances` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_employee_allowances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_employee_deductions`
--

DROP TABLE IF EXISTS `payroll_employee_deductions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_employee_deductions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payroll_employee_id` int(11) NOT NULL,
  `deduction_type_id` int(11) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `is_recurring` tinyint(1) DEFAULT 1,
  `effective_from` date DEFAULT NULL,
  `effective_to` date DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ped_employee` (`payroll_employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_employee_deductions`
--

LOCK TABLES `payroll_employee_deductions` WRITE;
/*!40000 ALTER TABLE `payroll_employee_deductions` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_employee_deductions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_employees`
--

DROP TABLE IF EXISTS `payroll_employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_employees`
--

LOCK TABLES `payroll_employees` WRITE;
/*!40000 ALTER TABLE `payroll_employees` DISABLE KEYS */;
INSERT INTO `payroll_employees` VALUES (1,7,'Stanbic Bank Uganda','100000000007','SBICUGKA','TIN000007','NSSF000007','monthly','UG3',3000000.00,'2022-01-15','active','2026-07-02 08:09:39'),(2,51,'Stanbic Bank Uganda','100000000051','SBICUGKA','TIN000051','NSSF000051','monthly','UG5',1200000.00,'2022-01-15','active','2026-07-02 08:09:39'),(3,2,'Stanbic Bank Uganda','100000000002','SBICUGKA','TIN000002','NSSF000002','monthly','UG1',5000000.00,'2022-01-15','active','2026-07-02 08:09:39'),(4,22,'Stanbic Bank Uganda','100000000022','SBICUGKA','TIN000022','NSSF000022','monthly','UG5',1500000.00,'2022-01-15','active','2026-07-02 08:09:39'),(5,6,'Stanbic Bank Uganda','100000000006','SBICUGKA','TIN000006','NSSF000006','monthly','UG2',4000000.00,'2022-01-15','active','2026-07-02 08:09:39'),(6,3,'Stanbic Bank Uganda','100000000003','SBICUGKA','TIN000003','NSSF000003','monthly','UG2',3800000.00,'2022-01-15','active','2026-07-02 08:09:39'),(7,24,'Stanbic Bank Uganda','100000000024','SBICUGKA','TIN000024','NSSF000024','monthly','UG5',1200000.00,'2022-01-15','active','2026-07-02 08:09:39'),(8,4,'Stanbic Bank Uganda','100000000004','SBICUGKA','TIN000004','NSSF000004','monthly','UG2',3500000.00,'2022-01-15','active','2026-07-02 08:09:39'),(9,1,'Stanbic Bank Uganda','100000000001','SBICUGKA','TIN000001','NSSF000001','monthly','UG1',5500000.00,'2022-01-15','active','2026-07-02 08:09:39'),(10,23,'Stanbic Bank Uganda','100000000023','SBICUGKA','TIN000023','NSSF000023','monthly','UG2',3500000.00,'2022-01-15','active','2026-07-02 08:09:39'),(11,18,'Stanbic Bank Uganda','100000000018','SBICUGKA','TIN000018','NSSF000018','monthly','UG5',1200000.00,'2022-01-15','active','2026-07-02 08:09:39'),(12,21,'Stanbic Bank Uganda','100000000021','SBICUGKA','TIN000021','NSSF000021','monthly','UG6',1000000.00,'2022-01-15','active','2026-07-02 08:09:39'),(13,12,'Stanbic Bank Uganda','100000000012','SBICUGKA','TIN000012','NSSF000012','monthly','UG5',1200000.00,'2022-01-15','active','2026-07-02 08:09:39'),(14,11,'Stanbic Bank Uganda','100000000011','SBICUGKA','TIN000011','NSSF000011','monthly','UG5',1200000.00,'2022-01-15','active','2026-07-02 08:09:39'),(15,8,'Stanbic Bank Uganda','100000000008','SBICUGKA','TIN000008','NSSF000008','monthly','UG3',3000000.00,'2022-01-15','active','2026-07-02 08:09:39'),(16,14,'Stanbic Bank Uganda','100000000014','SBICUGKA','TIN000014','NSSF000014','monthly','UG5',1200000.00,'2022-01-15','active','2026-07-02 08:09:39'),(17,15,'Stanbic Bank Uganda','100000000015','SBICUGKA','TIN000015','NSSF000015','monthly','UG5',1200000.00,'2022-01-15','active','2026-07-02 08:09:39'),(18,25,'Stanbic Bank Uganda','100000000025','SBICUGKA','TIN000025','NSSF000025','monthly','UG3',3000000.00,'2022-01-15','active','2026-07-02 08:09:39'),(19,10,'Stanbic Bank Uganda','100000000010','SBICUGKA','TIN000010','NSSF000010','monthly','UG5',1500000.00,'2022-01-15','active','2026-07-02 08:09:39'),(20,5,'Stanbic Bank Uganda','100000000005','SBICUGKA','TIN000005','NSSF000005','monthly','UG1',5000000.00,'2022-01-15','active','2026-07-02 08:09:39'),(21,9,'Stanbic Bank Uganda','100000000009','SBICUGKA','TIN000009','NSSF000009','monthly','UG5',1500000.00,'2022-01-15','active','2026-07-02 08:09:39'),(22,19,'Stanbic Bank Uganda','100000000019','SBICUGKA','TIN000019','NSSF000019','monthly','UG5',1200000.00,'2022-01-15','active','2026-07-02 08:09:39'),(23,13,'Stanbic Bank Uganda','100000000013','SBICUGKA','TIN000013','NSSF000013','monthly','UG5',1200000.00,'2022-01-15','active','2026-07-02 08:09:39'),(24,17,'Stanbic Bank Uganda','100000000017','SBICUGKA','TIN000017','NSSF000017','monthly','UG5',1200000.00,'2022-01-15','active','2026-07-02 08:09:39'),(25,20,'Stanbic Bank Uganda','100000000020','SBICUGKA','TIN000020','NSSF000020','monthly','UG5',1200000.00,'2022-01-15','active','2026-07-02 08:09:39'),(26,16,'Stanbic Bank Uganda','100000000016','SBICUGKA','TIN000016','NSSF000016','monthly','UG5',1200000.00,'2022-01-15','active','2026-07-02 08:09:39');
/*!40000 ALTER TABLE `payroll_employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_items`
--

DROP TABLE IF EXISTS `payroll_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payroll_run_id` int(11) DEFAULT NULL,
  `payroll_employee_id` int(11) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `basic_salary` decimal(15,2) DEFAULT 0.00,
  `total_allowances` decimal(15,2) DEFAULT 0.00,
  `total_deductions` decimal(15,2) DEFAULT 0.00,
  `net_salary` decimal(15,2) DEFAULT 0.00,
  `status` varchar(50) DEFAULT 'Draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pi_employee` (`payroll_employee_id`),
  KEY `idx_pi_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_items`
--

LOCK TABLES `payroll_items` WRITE;
/*!40000 ALTER TABLE `payroll_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_loans`
--

DROP TABLE IF EXISTS `payroll_loans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_loans` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payroll_employee_id` int(11) NOT NULL,
  `loan_number` varchar(50) NOT NULL,
  `loan_type` varchar(50) DEFAULT 'staff_loan',
  `principal_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `interest_rate` decimal(5,2) DEFAULT 0.00,
  `installments` int(11) DEFAULT 1,
  `installment_amount` decimal(15,2) DEFAULT 0.00,
  `loan_date` date DEFAULT NULL,
  `amount_paid` decimal(15,2) DEFAULT 0.00,
  `installments_paid` int(11) DEFAULT 0,
  `status` enum('pending','active','completed','defaulted') DEFAULT 'pending',
  `created_by` int(11) DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_loan_number` (`loan_number`),
  KEY `idx_loan_employee` (`payroll_employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_loans`
--

LOCK TABLES `payroll_loans` WRITE;
/*!40000 ALTER TABLE `payroll_loans` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_loans` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_overtime`
--

DROP TABLE IF EXISTS `payroll_overtime`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `payroll_payments`
--

DROP TABLE IF EXISTS `payroll_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payroll_run_id` int(11) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(50) DEFAULT 'bank_transfer',
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `employee_count` int(11) DEFAULT 0,
  `reference_number` varchar(100) DEFAULT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'pending',
  `processed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_payment_run` (`payroll_run_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_payments`
--

LOCK TABLES `payroll_payments` WRITE;
/*!40000 ALTER TABLE `payroll_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_payslips`
--

DROP TABLE IF EXISTS `payroll_payslips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_payslips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payroll_item_id` int(11) NOT NULL,
  `payroll_run_id` int(11) NOT NULL,
  `payroll_employee_id` int(11) NOT NULL,
  `staff_id` int(11) NOT NULL,
  `payslip_number` varchar(50) NOT NULL,
  `payslip_html` longtext DEFAULT NULL,
  `pdf_generated` tinyint(1) DEFAULT 0,
  `generated_by` int(11) DEFAULT NULL,
  `generated_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_payslip_number` (`payslip_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_payslips`
--

LOCK TABLES `payroll_payslips` WRITE;
/*!40000 ALTER TABLE `payroll_payslips` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_payslips` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_periods`
--

DROP TABLE IF EXISTS `payroll_periods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_periods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `period_name` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Open',
  `processed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_periods`
--

LOCK TABLES `payroll_periods` WRITE;
/*!40000 ALTER TABLE `payroll_periods` DISABLE KEYS */;
INSERT INTO `payroll_periods` VALUES (1,'January 2025','2025-01-01','2025-01-31','Open',NULL,'2026-07-02 08:09:39'),(2,'February 2025','2025-02-01','2025-02-28','Open',NULL,'2026-07-02 08:09:39'),(3,'March 2025','2025-03-01','2025-03-31','Open',NULL,'2026-07-02 08:09:39'),(4,'April 2025','2025-04-01','2025-04-30','Open',NULL,'2026-07-02 08:09:39'),(5,'May 2025','2025-05-01','2025-05-31','Open',NULL,'2026-07-02 08:09:39'),(6,'June 2025','2025-06-01','2025-06-30','Open',NULL,'2026-07-02 08:09:39'),(7,'July 2025','2025-07-01','2025-07-31','Open',NULL,'2026-07-02 08:09:39'),(8,'August 2025','2025-08-01','2025-08-31','Open',NULL,'2026-07-02 08:09:39'),(9,'September 2025','2025-09-01','2025-09-30','Open',NULL,'2026-07-02 08:09:39'),(10,'October 2025','2025-10-01','2025-10-31','Open',NULL,'2026-07-02 08:09:39'),(11,'November 2025','2025-11-01','2025-11-30','Open',NULL,'2026-07-02 08:09:39'),(12,'December 2025','2025-12-01','2025-12-31','Open',NULL,'2026-07-02 08:09:39'),(13,'January 2025','2025-01-01','2025-01-31','Open',NULL,'2026-07-03 04:37:04'),(14,'February 2025','2025-02-01','2025-02-28','Open',NULL,'2026-07-03 04:37:04'),(15,'March 2025','2025-03-01','2025-03-31','Open',NULL,'2026-07-03 04:37:04'),(16,'April 2025','2025-04-01','2025-04-30','Open',NULL,'2026-07-03 04:37:04'),(17,'May 2025','2025-05-01','2025-05-31','Open',NULL,'2026-07-03 04:37:04'),(18,'June 2025','2025-06-01','2025-06-30','Open',NULL,'2026-07-03 04:37:04'),(19,'July 2025','2025-07-01','2025-07-31','Open',NULL,'2026-07-03 04:37:04'),(20,'August 2025','2025-08-01','2025-08-31','Open',NULL,'2026-07-03 04:37:04'),(21,'September 2025','2025-09-01','2025-09-30','Open',NULL,'2026-07-03 04:37:04'),(22,'October 2025','2025-10-01','2025-10-31','Open',NULL,'2026-07-03 04:37:04'),(23,'November 2025','2025-11-01','2025-11-30','Open',NULL,'2026-07-03 04:37:04'),(24,'December 2025','2025-12-01','2025-12-31','Open',NULL,'2026-07-03 04:37:04'),(25,'January 2025','2025-01-01','2025-01-31','Open',NULL,'2026-07-03 05:16:23'),(26,'February 2025','2025-02-01','2025-02-28','Open',NULL,'2026-07-03 05:16:23'),(27,'March 2025','2025-03-01','2025-03-31','Open',NULL,'2026-07-03 05:16:23'),(28,'April 2025','2025-04-01','2025-04-30','Open',NULL,'2026-07-03 05:16:23'),(29,'May 2025','2025-05-01','2025-05-31','Open',NULL,'2026-07-03 05:16:23'),(30,'June 2025','2025-06-01','2025-06-30','Open',NULL,'2026-07-03 05:16:23'),(31,'July 2025','2025-07-01','2025-07-31','Open',NULL,'2026-07-03 05:16:23'),(32,'August 2025','2025-08-01','2025-08-31','Open',NULL,'2026-07-03 05:16:23'),(33,'September 2025','2025-09-01','2025-09-30','Open',NULL,'2026-07-03 05:16:23'),(34,'October 2025','2025-10-01','2025-10-31','Open',NULL,'2026-07-03 05:16:23'),(35,'November 2025','2025-11-01','2025-11-30','Open',NULL,'2026-07-03 05:16:23'),(36,'December 2025','2025-12-01','2025-12-31','Open',NULL,'2026-07-03 05:16:23');
/*!40000 ALTER TABLE `payroll_periods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_records`
--

DROP TABLE IF EXISTS `payroll_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  KEY `idx_status` (`status`),
  KEY `idx_pr_staff_year` (`staff_id`,`year`)
) ENGINE=InnoDB AUTO_INCREMENT=53 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_records`
--

LOCK TABLES `payroll_records` WRITE;
/*!40000 ALTER TABLE `payroll_records` DISABLE KEYS */;
INSERT INTO `payroll_records` VALUES (1,1,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(2,2,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(3,3,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(4,4,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(5,5,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(6,6,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(7,7,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(8,8,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(9,9,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(10,10,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(11,11,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(12,12,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(13,13,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(14,14,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(15,15,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(16,16,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(17,17,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(18,18,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(19,19,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(20,20,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(21,21,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(22,22,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(23,23,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(24,24,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(25,25,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL),(26,51,6,2026,0.00,0.00,0.00,0.00,0.00,0.00,0.00,0.00,25,'2026-06-25 00:34:35','Processed',NULL,NULL);
/*!40000 ALTER TABLE `payroll_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_reports`
--

DROP TABLE IF EXISTS `payroll_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_type` varchar(50) NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `generated_by` int(11) NOT NULL,
  `report_data` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_report_type` (`report_type`),
  KEY `idx_period` (`period_start`,`period_end`),
  KEY `idx_generated_by` (`generated_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_reports`
--

LOCK TABLES `payroll_reports` WRITE;
/*!40000 ALTER TABLE `payroll_reports` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_runs`
--

DROP TABLE IF EXISTS `payroll_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_runs`
--

LOCK TABLES `payroll_runs` WRITE;
/*!40000 ALTER TABLE `payroll_runs` DISABLE KEYS */;
INSERT INTO `payroll_runs` VALUES (1,'January 2025','2025-01-01','2025-01-31','January 2025 Salary Payment',59200000.00,13024000.00,46176000.00,'approved',NULL,NULL,NULL,'2026-07-02 08:09:39'),(2,'January 2025','2025-01-01','2025-01-31','January 2025 Salary Payment',59200000.00,13024000.00,46176000.00,'approved',NULL,NULL,NULL,'2026-07-03 04:37:04'),(3,'January 2025','2025-01-01','2025-01-31','January 2025 Salary Payment',59200000.00,13024000.00,46176000.00,'approved',NULL,NULL,NULL,'2026-07-03 05:16:23');
/*!40000 ALTER TABLE `payroll_runs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll_settings`
--

DROP TABLE IF EXISTS `payroll_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payroll_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll_settings`
--

LOCK TABLES `payroll_settings` WRITE;
/*!40000 ALTER TABLE `payroll_settings` DISABLE KEYS */;
INSERT INTO `payroll_settings` VALUES (1,'currency','PHP','2026-06-28 05:57:22'),(2,'currency_symbol','₱','2026-06-28 05:57:22'),(3,'tax_rate','0','2026-06-28 05:57:22'),(4,'sss_rate','0.045','2026-06-28 05:57:22'),(5,'philhealth_rate','0.0225','2026-06-28 05:57:22'),(6,'pagibig_rate','0.02','2026-06-28 05:57:22'),(7,'overtime_rate','1.25','2026-06-28 05:57:22'),(9,'nssf_rate','10','2026-07-02 08:09:39'),(10,'paye_rate','12','2026-07-02 08:09:39'),(11,'employer_nssf_rate','10','2026-07-02 08:09:39'),(12,'institution_name','Iganga School of Nursing and Midwifery','2026-07-02 08:09:39'),(13,'payroll_start_day','1','2026-07-02 08:09:39'),(14,'payroll_end_day','28','2026-07-02 08:09:39'),(15,'payment_day','28','2026-07-02 08:09:39'),(16,'tax_threshold','235000','2026-07-02 08:09:39');
/*!40000 ALTER TABLE `payroll_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payslips`
--

DROP TABLE IF EXISTS `payslips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `penalty_config`
--

DROP TABLE IF EXISTS `penalty_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `penalty_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `penalty_name` varchar(200) DEFAULT NULL,
  `penalty_type` varchar(20) DEFAULT NULL,
  `penalty_value` decimal(15,2) DEFAULT NULL,
  `grace_days` int(11) DEFAULT 0,
  `max_charge` decimal(15,2) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `penalty_config`
--

LOCK TABLES `penalty_config` WRITE;
/*!40000 ALTER TABLE `penalty_config` DISABLE KEYS */;
/*!40000 ALTER TABLE `penalty_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `penalty_configurations`
--

DROP TABLE IF EXISTS `penalty_configurations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `penalty_configurations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `penalty_name` varchar(200) DEFAULT NULL,
  `penalty_type` varchar(50) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `penalty_configurations`
--

LOCK TABLES `penalty_configurations` WRITE;
/*!40000 ALTER TABLE `penalty_configurations` DISABLE KEYS */;
/*!40000 ALTER TABLE `penalty_configurations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pending_students`
--

DROP TABLE IF EXISTS `pending_students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
INSERT INTO `pending_students` VALUES (1,'Akello',NULL,'Grace','ISNM-2026-006','Diploma Nursing','1','2026','January',NULL,NULL,NULL,5,'pending_approval',4,NULL,'2026-06-19 21:47:50'),(2,'Bwire',NULL,'John','ISNM-2026-007','Certificate Midwifery','1','2026','January',NULL,NULL,NULL,5,'pending_approval',5,NULL,'2026-06-19 00:47:50');
/*!40000 ALTER TABLE `pending_students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `performance_indicators`
--

DROP TABLE IF EXISTS `performance_indicators`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `performance_metrics`
--

DROP TABLE IF EXISTS `performance_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `performance_metrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `metric_type` varchar(100) DEFAULT NULL,
  `metric_name` varchar(255) DEFAULT NULL,
  `metric_value` decimal(10,2) DEFAULT NULL,
  `metric_unit` varchar(50) DEFAULT NULL,
  `target_value` decimal(10,2) DEFAULT NULL,
  `period` varchar(50) DEFAULT NULL,
  `recorded_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `performance_metrics`
--

LOCK TABLES `performance_metrics` WRITE;
/*!40000 ALTER TABLE `performance_metrics` DISABLE KEYS */;
/*!40000 ALTER TABLE `performance_metrics` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `performance_reviews`
--

DROP TABLE IF EXISTS `performance_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `performance_reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `reviewer_id` int(11) DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `review_period` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `overall_score` decimal(5,2) DEFAULT NULL,
  `comments` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `performance_reviews`
--

LOCK TABLES `performance_reviews` WRITE;
/*!40000 ALTER TABLE `performance_reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `performance_reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `professional_licenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_name` varchar(200) NOT NULL,
  `license_number` varchar(100) DEFAULT NULL,
  `license_type` varchar(100) NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `issuing_body` varchar(200) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `professional_licenses`
--

LOCK TABLES `professional_licenses` WRITE;
/*!40000 ALTER TABLE `professional_licenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `professional_licenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `programs`
--

DROP TABLE IF EXISTS `programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `programs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_code` varchar(50) DEFAULT NULL,
  `program_name` varchar(255) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `duration_years` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `programs`
--

LOCK TABLES `programs` WRITE;
/*!40000 ALTER TABLE `programs` DISABLE KEYS */;
/*!40000 ALTER TABLE `programs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proof_of_payments`
--

DROP TABLE IF EXISTS `proof_of_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `proof_of_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` int(11) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proof_of_payments`
--

LOCK TABLES `proof_of_payments` WRITE;
/*!40000 ALTER TABLE `proof_of_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `proof_of_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quality_assurance`
--

DROP TABLE IF EXISTS `quality_assurance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `real_time_updates`
--

DROP TABLE IF EXISTS `real_time_updates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `real_time_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `update_type` varchar(100) DEFAULT NULL,
  `update_title` varchar(255) DEFAULT NULL,
  `update_description` text DEFAULT NULL,
  `update_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`update_data`)),
  `priority` varchar(20) DEFAULT 'normal',
  `target_user` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT NULL,
  `created_at_ts` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `real_time_updates`
--

LOCK TABLES `real_time_updates` WRITE;
/*!40000 ALTER TABLE `real_time_updates` DISABLE KEYS */;
/*!40000 ALTER TABLE `real_time_updates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `receipt_templates`
--

DROP TABLE IF EXISTS `receipt_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `receipt_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `template_name` varchar(200) DEFAULT NULL,
  `template_type` varchar(100) DEFAULT NULL,
  `template_content` longtext DEFAULT NULL,
  `header_text` text DEFAULT NULL,
  `footer_text` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `receipt_templates`
--

LOCK TABLES `receipt_templates` WRITE;
/*!40000 ALTER TABLE `receipt_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `receipt_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recruitment`
--

DROP TABLE IF EXISTS `recruitment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `recruitment_applications`
--

DROP TABLE IF EXISTS `recruitment_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recruitment_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vacancy_id` int(11) DEFAULT NULL,
  `applicant_name` varchar(255) DEFAULT NULL,
  `applicant_email` varchar(255) DEFAULT NULL,
  `applicant_phone` varchar(50) DEFAULT NULL,
  `cv_path` varchar(500) DEFAULT NULL,
  `cover_letter` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'received',
  `reviewed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recruitment_applications`
--

LOCK TABLES `recruitment_applications` WRITE;
/*!40000 ALTER TABLE `recruitment_applications` DISABLE KEYS */;
/*!40000 ALTER TABLE `recruitment_applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recruitment_jobs`
--

DROP TABLE IF EXISTS `recruitment_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `recruitment_jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `salary_range` varchar(100) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'open',
  `posted_date` date DEFAULT NULL,
  `closing_date` date DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recruitment_jobs`
--

LOCK TABLES `recruitment_jobs` WRITE;
/*!40000 ALTER TABLE `recruitment_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `recruitment_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recycle_bin`
--

DROP TABLE IF EXISTS `recycle_bin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `registrar_academic_calendar`
--

DROP TABLE IF EXISTS `registrar_academic_calendar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `registrar_academic_calendar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `event_name` varchar(255) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `event_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registrar_academic_calendar`
--

LOCK TABLES `registrar_academic_calendar` WRITE;
/*!40000 ALTER TABLE `registrar_academic_calendar` DISABLE KEYS */;
/*!40000 ALTER TABLE `registrar_academic_calendar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registrar_academic_records`
--

DROP TABLE IF EXISTS `registrar_academic_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `registrar_academic_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `record_type` varchar(100) DEFAULT NULL,
  `record_data` longtext DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registrar_academic_records`
--

LOCK TABLES `registrar_academic_records` WRITE;
/*!40000 ALTER TABLE `registrar_academic_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `registrar_academic_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registrar_graduation`
--

DROP TABLE IF EXISTS `registrar_graduation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `registrar_graduation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `graduation_date` date DEFAULT NULL,
  `classification` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registrar_graduation`
--

LOCK TABLES `registrar_graduation` WRITE;
/*!40000 ALTER TABLE `registrar_graduation` DISABLE KEYS */;
/*!40000 ALTER TABLE `registrar_graduation` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `registrar_student_registration`
--

DROP TABLE IF EXISTS `registrar_student_registration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `registrar_transcripts`
--

DROP TABLE IF EXISTS `registrar_transcripts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `registrar_transcripts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transcript_number` varchar(50) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `transcript_status` varchar(50) DEFAULT 'Pending',
  `request_date` datetime DEFAULT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `registrar_transcripts`
--

LOCK TABLES `registrar_transcripts` WRITE;
/*!40000 ALTER TABLE `registrar_transcripts` DISABLE KEYS */;
/*!40000 ALTER TABLE `registrar_transcripts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `requirement_history`
--

DROP TABLE IF EXISTS `requirement_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `result_approvals`
--

DROP TABLE IF EXISTS `result_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `result_approvals` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `result_id` int(11) NOT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approval_date` datetime DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `result_approvals`
--

LOCK TABLES `result_approvals` WRITE;
/*!40000 ALTER TABLE `result_approvals` DISABLE KEYS */;
/*!40000 ALTER TABLE `result_approvals` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `result_publication`
--

DROP TABLE IF EXISTS `result_publication`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `result_publication` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `publication_number` varchar(50) NOT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `scheduled_date` datetime DEFAULT NULL,
  `published_by` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Published',
  `published_at` datetime DEFAULT NULL,
  `notification_sent` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `result_publication`
--

LOCK TABLES `result_publication` WRITE;
/*!40000 ALTER TABLE `result_publication` DISABLE KEYS */;
/*!40000 ALTER TABLE `result_publication` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `result_publications`
--

DROP TABLE IF EXISTS `result_publications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
INSERT INTO `roles` VALUES (1,'Director General',NULL,'2026-06-09 22:56:09'),(2,'CEO',NULL,'2026-06-09 22:56:09'),(3,'Director Academics',NULL,'2026-06-09 22:56:09'),(4,'Director Finance',NULL,'2026-06-09 22:56:09'),(5,'Director ICT',NULL,'2026-06-09 22:56:09'),(6,'School Principal',NULL,'2026-06-09 22:56:09'),(7,'Deputy Principal',NULL,'2026-06-09 22:56:09'),(8,'Academic Registrar',NULL,'2026-06-09 22:56:09'),(9,'HR Manager',NULL,'2026-06-09 22:56:09'),(10,'School Secretary',NULL,'2026-06-09 22:56:09'),(11,'School Librarian',NULL,'2026-06-09 22:56:09'),(12,'Head Nursing',NULL,'2026-06-09 22:56:09'),(13,'Head Midwifery',NULL,'2026-06-09 22:56:09'),(14,'Senior Lecturers',NULL,'2026-06-09 22:56:09'),(15,'Lecturers',NULL,'2026-06-09 22:56:09'),(16,'Matrons',NULL,'2026-06-09 22:56:09'),(17,'Wardens',NULL,'2026-06-09 22:56:09'),(18,'Sickbay',NULL,'2026-06-09 22:56:09'),(19,'Drivers',NULL,'2026-06-09 22:56:09'),(20,'Security',NULL,'2026-06-09 22:56:09'),(21,'Storekeeper',NULL,'2026-06-09 22:56:09'),(22,'Guild President',NULL,'2026-06-09 22:56:09'),(23,'Computer Lab Manager',NULL,'2026-06-09 22:56:09'),(24,'School Bursar',NULL,'2026-06-09 22:56:09'),(25,'Store Keeper','Store inventory','2026-06-13 02:38:49'),(26,'Director Admissions & Requirements','Admissions management','2026-06-13 02:38:49'),(27,'Bursar','Bursar assistant','2026-06-13 02:38:49');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `room_inspections`
--

DROP TABLE IF EXISTS `room_inspections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `route_schedules`
--

DROP TABLE IF EXISTS `route_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `route_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `route_name` varchar(200) DEFAULT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `departure_time` time DEFAULT NULL,
  `arrival_time` time DEFAULT NULL,
  `route_start` varchar(200) DEFAULT NULL,
  `route_end` varchar(200) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_rs_vehicle` (`vehicle_id`),
  KEY `fk_rs_driver` (`driver_id`),
  CONSTRAINT `fk_rs_driver` FOREIGN KEY (`driver_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_rs_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `route_schedules`
--

LOCK TABLES `route_schedules` WRITE;
/*!40000 ALTER TABLE `route_schedules` DISABLE KEYS */;
/*!40000 ALTER TABLE `route_schedules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salary_structures`
--

DROP TABLE IF EXISTS `salary_structures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `salary_structures` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) DEFAULT NULL,
  `role_name` varchar(200) DEFAULT NULL,
  `base_salary` decimal(15,2) DEFAULT 0.00,
  `housing_allowance` decimal(15,2) DEFAULT 0.00,
  `transport_allowance` decimal(15,2) DEFAULT 0.00,
  `medical_allowance` decimal(15,2) DEFAULT 0.00,
  `effective_from` date DEFAULT NULL,
  `effective_to` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salary_structures`
--

LOCK TABLES `salary_structures` WRITE;
/*!40000 ALTER TABLE `salary_structures` DISABLE KEYS */;
/*!40000 ALTER TABLE `salary_structures` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `scholarships`
--

DROP TABLE IF EXISTS `scholarships`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `secretary_announcements`
--

DROP TABLE IF EXISTS `secretary_announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `secretary_announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `target_audience` varchar(100) DEFAULT 'all',
  `is_active` tinyint(1) DEFAULT 1,
  `publish_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `secretary_announcements`
--

LOCK TABLES `secretary_announcements` WRITE;
/*!40000 ALTER TABLE `secretary_announcements` DISABLE KEYS */;
/*!40000 ALTER TABLE `secretary_announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `secretary_appointments`
--

DROP TABLE IF EXISTS `secretary_appointments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `secretary_appointments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `secretary_appointments`
--

LOCK TABLES `secretary_appointments` WRITE;
/*!40000 ALTER TABLE `secretary_appointments` DISABLE KEYS */;
/*!40000 ALTER TABLE `secretary_appointments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `secretary_contacts`
--

DROP TABLE IF EXISTS `secretary_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `secretary_contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `contact_name` varchar(255) NOT NULL,
  `contact_email` varchar(255) DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `organization` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `secretary_contacts`
--

LOCK TABLES `secretary_contacts` WRITE;
/*!40000 ALTER TABLE `secretary_contacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `secretary_contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `secretary_documents`
--

DROP TABLE IF EXISTS `secretary_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `secretary_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_type` varchar(50) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `status` enum('active','archived') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `secretary_documents`
--

LOCK TABLES `secretary_documents` WRITE;
/*!40000 ALTER TABLE `secretary_documents` DISABLE KEYS */;
/*!40000 ALTER TABLE `secretary_documents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `secretary_meetings`
--

DROP TABLE IF EXISTS `secretary_meetings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `secretary_meetings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `meeting_date` date NOT NULL,
  `meeting_time` time NOT NULL,
  `duration_minutes` int(11) DEFAULT 60,
  `location` varchar(255) DEFAULT NULL,
  `attendees` text DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `secretary_meetings`
--

LOCK TABLES `secretary_meetings` WRITE;
/*!40000 ALTER TABLE `secretary_meetings` DISABLE KEYS */;
/*!40000 ALTER TABLE `secretary_meetings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `secretary_messages`
--

DROP TABLE IF EXISTS `secretary_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `secretary_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `secretary_messages`
--

LOCK TABLES `secretary_messages` WRITE;
/*!40000 ALTER TABLE `secretary_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `secretary_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `secretary_requests`
--

DROP TABLE IF EXISTS `secretary_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `secretary_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `request_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `priority` enum('low','medium','high') DEFAULT 'medium',
  `status` enum('pending','in_progress','resolved','rejected') DEFAULT 'pending',
  `assigned_to` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `secretary_requests`
--

LOCK TABLES `secretary_requests` WRITE;
/*!40000 ALTER TABLE `secretary_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `secretary_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `security_access_logs`
--

DROP TABLE IF EXISTS `security_access_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `security_emergency_contacts`
--

DROP TABLE IF EXISTS `security_emergency_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `security_emergency_contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `contact_name` varchar(200) NOT NULL,
  `contact_type` varchar(100) DEFAULT NULL,
  `phone_number` varchar(50) DEFAULT NULL,
  `email` varchar(200) DEFAULT NULL,
  `organization` varchar(200) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_emergency_contacts`
--

LOCK TABLES `security_emergency_contacts` WRITE;
/*!40000 ALTER TABLE `security_emergency_contacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `security_emergency_contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `security_equipment`
--

DROP TABLE IF EXISTS `security_equipment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `security_equipment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equipment_name` varchar(200) NOT NULL,
  `equipment_type` varchar(100) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `status` enum('Operational','Maintenance','Retired','Damaged') DEFAULT 'Operational',
  `purchase_date` date DEFAULT NULL,
  `last_maintenance_date` date DEFAULT NULL,
  `next_maintenance_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_equipment`
--

LOCK TABLES `security_equipment` WRITE;
/*!40000 ALTER TABLE `security_equipment` DISABLE KEYS */;
/*!40000 ALTER TABLE `security_equipment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `security_incidents`
--

DROP TABLE IF EXISTS `security_incidents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `security_patrols`
--

DROP TABLE IF EXISTS `security_patrols`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `security_patrols` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `guard_id` int(11) DEFAULT NULL,
  `patrol_area` varchar(200) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `patrol_date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `status` enum('Scheduled','In Progress','Completed','Cancelled') DEFAULT 'Scheduled',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_patrols`
--

LOCK TABLES `security_patrols` WRITE;
/*!40000 ALTER TABLE `security_patrols` DISABLE KEYS */;
/*!40000 ALTER TABLE `security_patrols` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `security_visitors`
--

DROP TABLE IF EXISTS `security_visitors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `security_visitors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visitor_name` varchar(200) NOT NULL,
  `visitor_phone` varchar(50) DEFAULT NULL,
  `visitor_nature` varchar(200) DEFAULT NULL,
  `person_to_visit_name` varchar(200) DEFAULT NULL,
  `visit_date` date NOT NULL,
  `expected_arrival` time DEFAULT NULL,
  `actual_arrival` time DEFAULT NULL,
  `expected_departure` time DEFAULT NULL,
  `actual_departure` time DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Expected',
  `badge_number` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `security_visitors`
--

LOCK TABLES `security_visitors` WRITE;
/*!40000 ALTER TABLE `security_visitors` DISABLE KEYS */;
/*!40000 ALTER TABLE `security_visitors` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `semesters`
--

DROP TABLE IF EXISTS `semesters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `sickbay_medicine_stock`
--

DROP TABLE IF EXISTS `sickbay_medicine_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sickbay_medicine_stock` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `medicine_name` varchar(200) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `quantity` int(10) unsigned NOT NULL DEFAULT 0,
  `unit` varchar(30) DEFAULT 'tablets',
  `expiry_date` date DEFAULT NULL,
  `reorder_level` int(10) unsigned DEFAULT 10,
  `status` enum('available','low_stock','out_of_stock','expired') NOT NULL DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sickbay_medicine_stock`
--

LOCK TABLES `sickbay_medicine_stock` WRITE;
/*!40000 ALTER TABLE `sickbay_medicine_stock` DISABLE KEYS */;
/*!40000 ALTER TABLE `sickbay_medicine_stock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sickbay_medicine_transactions`
--

DROP TABLE IF EXISTS `sickbay_medicine_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sickbay_medicine_transactions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `medicine_id` int(10) unsigned NOT NULL,
  `transaction_type` enum('received','dispensed','adjusted','expired') NOT NULL,
  `quantity` int(11) NOT NULL,
  `visit_id` int(10) unsigned DEFAULT NULL,
  `performed_by` int(10) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sickbay_medicine_transactions`
--

LOCK TABLES `sickbay_medicine_transactions` WRITE;
/*!40000 ALTER TABLE `sickbay_medicine_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sickbay_medicine_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sickbay_settings`
--

DROP TABLE IF EXISTS `sickbay_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
INSERT INTO `sickbay_settings` VALUES (1,'reorder_level','10','2026-06-19 22:59:38'),(2,'low_stock_threshold','10','2026-06-19 22:59:38'),(3,'auto_status','1','2026-06-19 22:59:38'),(4,'notify_low_stock','1','2026-06-19 22:59:38'),(5,'default_theme','default-blue','2026-06-19 22:59:38');
/*!40000 ALTER TABLE `sickbay_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sickbay_visits`
--

DROP TABLE IF EXISTS `sickbay_visits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sickbay_visits` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned DEFAULT NULL,
  `student_name` varchar(120) NOT NULL,
  `visit_date` datetime NOT NULL,
  `symptoms` text DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `medication_given` text DEFAULT NULL,
  `nurse_id` int(10) unsigned DEFAULT NULL,
  `nurse_name` varchar(120) DEFAULT NULL,
  `status` enum('treated','referred','follow_up','resolved') NOT NULL DEFAULT 'treated',
  `follow_up_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sickbay_visits`
--

LOCK TABLES `sickbay_visits` WRITE;
/*!40000 ALTER TABLE `sickbay_visits` DISABLE KEYS */;
/*!40000 ALTER TABLE `sickbay_visits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sickness_directory`
--

DROP TABLE IF EXISTS `sickness_directory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
INSERT INTO `sickness_directory` VALUES (1,'MLR','Malaria','Infectious','Fever, chills, headache, sweating, fatigue','Mosquito-borne parasitic infection common in tropical regions',0,'Artemisinin-based combination therapy, antimalarials','Active',NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(2,'TYP','Typhoid','Infectious','Prolonged fever, abdominal pain, headache, constipation or diarrhea','Bacterial infection spread through contaminated food/water',1,'Antibiotics (ciprofloxacin, azithromycin), hydration','Active',NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(3,'FLU','Influenza','Infectious','Fever, cough, sore throat, body aches, fatigue','Viral respiratory infection spread through droplets',1,'Rest, fluids, antipyretics, antivirals if severe','Active',NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(4,'COLD','Common Cold','Infectious','Runny nose, sneezing, sore throat, cough, mild fever','Viral upper respiratory tract infection',1,'Rest, antihistamines, decongestants, vitamin C','Active',NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(5,'URTI','Upper Respiratory Tract Infection','Infectious','Cough, sore throat, nasal congestion, fever','Bacterial or viral infection of upper airways',1,'Antibiotics if bacterial, rest, fluids','Active',NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(6,'HDCH','Headache/Tension Headache','Non-Infectious','Head pain, pressure around forehead, neck tension','Common tension-type headache from stress or fatigue',0,'Rest, analgesics (paracetamol, ibuprofen)','Active',NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(7,'GSTR','Gastritis','Non-Infectious','Abdominal pain, nausea, bloating, indigestion','Inflammation of stomach lining from diet, stress, or infection',0,'Antacids, dietary changes, proton pump inhibitors','Active',NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(8,'DIAR','Diarrhea','Infectious','Loose watery stools, abdominal cramps, dehydration','Common infection from contaminated food/water or viruses',1,'ORS, hydration, antidiarrheals, antibiotics if bacterial','Active',NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(9,'ALLG','Allergic Reaction','Non-Infectious','Rash, itching, sneezing, watery eyes, swelling','Immune response to allergens (food, dust, pollen, drugs)',0,'Antihistamines, corticosteroids, avoid triggers','Active',NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(10,'INJR','Injury/Accident','Injury','Pain, swelling, bruising, bleeding, limited mobility','Physical trauma from falls, sports, or accidents',0,'First aid, rest, ice, compression, elevation, analgesics','Active',NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(11,'ANEM','Anemia','Nutritional','Fatigue, weakness, pale skin, shortness of breath, dizziness','Low red blood cell count from iron deficiency or other causes',0,'Iron supplements, dietary changes, B12 if needed','Active',NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(12,'MALN','Malnutrition','Nutritional','Weight loss, fatigue, poor growth, weakened immunity','Inadequate nutrient intake affecting overall health',0,'Nutritional supplementation, diet counseling','Active',NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(13,'CONS','Constipation','Non-Infectious','Infrequent bowel movements, straining, hard stools','Common digestive issue from diet or lifestyle factors',0,'Increased fiber intake, hydration, laxatives if needed','Active',NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(14,'SORE','Sore Throat','Infectious','Pain or scratchiness in throat, difficulty swallowing','Viral or bacterial throat infection',1,'Warm salt water gargle, lozenges, antibiotics if strep','Active',NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(15,'EYEI','Eye Infection','Infectious','Redness, itching, discharge, swollen eyelids','Bacterial or viral conjunctivitis',1,'Antibiotic or antiviral eye drops, hygiene','Active',NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(16,'SKIN','Skin Infection/Rash','Infectious','Redness, itching, bumps, blisters, peeling','Fungal, bacterial, or viral skin infection',1,'Topical or oral antibiotics/antifungals, hygiene','Active',NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(17,'FATG','Fatigue/General Malaise','Non-Infectious','Tiredness, low energy, reduced motivation','General feeling of being unwell without specific diagnosis',0,'Rest, nutrition, hydration, stress management','Active',NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(18,'MSTR','Menstrual Cramps','Non-Infectious','Lower abdominal pain, back pain, nausea during menstruation','Painful menstrual periods common in young women',0,'Analgesics, heat therapy, rest, NSAIDs','Active',NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(19,'ANXT','Anxiety/Stress','Mental Health','Worry, restlessness, rapid heartbeat, difficulty concentrating','Mental health condition common among students under academic pressure',0,'Counseling, stress management, relaxation techniques','Active',NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(20,'BACK','Back Pain','Non-Infectious','Lower or upper back pain, stiffness, muscle tension','Musculoskeletal pain from poor posture, heavy lifting, or strain',0,'Rest, analgesics, physiotherapy, posture correction','Active',NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(21,'THRP','Throat Infection/Pharyngitis','Infectious','Sore throat, red tonsils, swollen lymph nodes, fever','Inflammation of the pharynx from viral or bacterial infection',1,'Antibiotics if bacterial, rest, warm fluids','Active',NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(22,'TOOT','Toothache','Non-Infectious','Tooth pain, sensitivity, swelling around tooth','Dental pain from cavities, infection, or impaction',0,'Analgesics, dental referral, antibiotics if infected','Active',NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(23,'URIN','Urinary Tract Infection','Infectious','Painful urination, frequent urination, lower abdominal pain','Bacterial infection of the urinary tract',0,'Antibiotics, increased fluid intake, cranberry','Active',NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(24,'ACNE','Acne/Skin Breakout','Non-Infectious','Pimples, blackheads, whiteheads, inflamed skin','Common skin condition from hormonal changes and stress',0,'Topical treatments, hygiene, dietary changes','Active',NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44'),(25,'FUNG','Fungal Infection','Infectious','Itching, redness, peeling skin, rash with defined edges','Fungal skin infection common in tropical climates',1,'Antifungal creams or oral medication, keep area dry','Active',NULL,'2026-06-19 22:53:44','2026-06-19 22:53:44');
/*!40000 ALTER TABLE `sickness_directory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `skills_laboratory`
--

DROP TABLE IF EXISTS `skills_laboratory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `skills_laboratory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lab_name` varchar(200) NOT NULL,
  `location` varchar(200) DEFAULT NULL,
  `capacity` int(11) DEFAULT 30,
  `status` varchar(50) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `skills_laboratory`
--

LOCK TABLES `skills_laboratory` WRITE;
/*!40000 ALTER TABLE `skills_laboratory` DISABLE KEYS */;
/*!40000 ALTER TABLE `skills_laboratory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sms_logs`
--

DROP TABLE IF EXISTS `sms_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_logs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `phone_number` varchar(20) NOT NULL,
  `recipient_name` varchar(120) DEFAULT NULL,
  `recipient_type` enum('staff','student','external') NOT NULL DEFAULT 'staff',
  `recipient_id` int(11) unsigned DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('queued','sent','delivered','failed') NOT NULL DEFAULT 'queued',
  `error_message` text DEFAULT NULL,
  `provider` varchar(50) DEFAULT NULL,
  `provider_message_id` varchar(100) DEFAULT NULL,
  `sent_by` int(11) unsigned DEFAULT NULL,
  `sent_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_phone` (`phone_number`),
  KEY `idx_status` (`status`),
  KEY `idx_sent_at` (`sent_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sms_logs`
--

LOCK TABLES `sms_logs` WRITE;
/*!40000 ALTER TABLE `sms_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `sms_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sports_events`
--

DROP TABLE IF EXISTS `sports_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
  KEY `role_id` (`role_id`),
  KEY `idx_staff_email` (`email`),
  KEY `idx_staff_status` (`status`),
  KEY `idx_staff_role_status` (`role_id`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff`
--

LOCK TABLES `staff` WRITE;
/*!40000 ALTER TABLE `staff` DISABLE KEYS */;
INSERT INTO `staff` VALUES (1,NULL,'Doris Joy Namugwanya','directorgeneral@igangaschoolofnursingandmidwifery.ac.ug','','$2y$10$5ZCEt690hGgitPo/i2hN9u47/msQyL/WGUjLqRrV5FxDkIJ1E8Z4G',1,'Director General','Executive Office','Active','2026-06-09','2026-07-02 18:59:12',0,NULL,0,1,NULL,NULL,'2026-06-09 22:56:10','2026-07-03 05:14:47'),(2,NULL,'Doris Joy','ceo@igangaschoolofnursingandmidwifery.ac.ug','','$2y$10$9OCaF6L19fgSaGCxFIg4r.zqRGhOmJ9NH7O/drLcnZxJuc98fLmIm',2,'Chief Executive Officer','Executive Office','Active','2026-06-09','2026-06-28 10:30:19',0,NULL,0,1,NULL,'','2026-06-09 22:56:10','2026-07-03 05:14:47'),(3,NULL,'Stephen Bywaka','directoracademic@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$.ISH3kz4OBP2pm9zZdErAOa.gRlJ6jzXywDjl5KKzXBO4rWynaUX6',3,'Director Academics','Academic Affairs','Active','2026-06-09','2026-07-01 17:18:49',0,NULL,0,1,NULL,NULL,'2026-06-09 22:56:10','2026-07-03 05:14:47'),(4,NULL,'Finance Director','finance@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$mdRzTcfvpjfW3bT3Sw90ZeI25KAVPfaDOZWfaWNqA2UYFcYiurqvK',4,'Director Finance','Finance Department','Active','2026-06-09','2026-06-29 11:25:43',0,NULL,0,1,NULL,NULL,'2026-06-09 22:56:10','2026-07-03 05:14:47'),(5,NULL,'School Principal','principal@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$47TAy3ADQyAKVrB7HKPuw.f60KFLoKwRQOpspDDO0ZRzuodEZN9mu',6,'School Principal','Academic Affairs','Active','2026-06-09','2026-06-29 11:27:12',0,NULL,0,1,NULL,NULL,'2026-06-09 22:56:11','2026-07-03 05:14:47'),(6,NULL,'Deputy Principal','dep-principal@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$fX8gBipJ9jktOlqImT.dGethgebkpMQtISb4HPLKBWzP4W6Sfl8eG',7,'Deputy Principal','Academic Affairs','Active','2026-06-09','2026-06-29 11:28:25',0,NULL,0,1,NULL,NULL,'2026-06-09 22:56:11','2026-07-03 05:14:47'),(7,NULL,'Academic Registrar','academicregistrar@igangaschoolofnursingandmidwifery.ac.ug','0772514889','$2y$10$PqCRfIJ85BuVxyK7ARsJMOKOX2gzssx9wKlgCMZ2c.ma8X1qYlbWi',8,'Academic Registrar','Academic Affairs','Active','2026-06-09','2026-07-01 17:58:34',0,NULL,0,1,NULL,'Lubas Road','2026-06-09 22:56:11','2026-07-03 05:14:47'),(8,NULL,'HR Manager','hr-manager@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$cmGaNRE4umHoIRfZlQ2zHuSUhCx9U3Ir6dgHlC6Vht5WjUSQnUXQ.',9,'HR Manager','Human Resources','Active','2026-06-09','2026-06-26 06:04:57',0,NULL,0,1,NULL,NULL,'2026-06-09 22:56:14','2026-07-03 05:14:47'),(9,NULL,'School Secretary','secretary@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$YJfwOcBemAZeSBPe5aaMhuDQ5xqsCDWCs.ikP5NeXL2AUqfGIajke',10,'School Secretary','Administrative Office','Active','2026-06-09','2026-07-01 17:41:33',0,NULL,0,1,NULL,NULL,'2026-06-09 22:56:23','2026-07-03 05:14:47'),(10,NULL,'School Librarian','library@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$FaZokKAdFdpBpRwcSM5dSObZG740ZACI8ahL9vE9Uf5SoWWQUZksC',11,'School Librarian','Library Services','Active','2026-06-09','2026-07-01 10:04:36',0,NULL,0,1,NULL,NULL,'2026-06-09 22:56:29','2026-07-03 05:14:47'),(11,NULL,'Head of Nursing','nursing-dep@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$vIoSrwFiG5x2a.Nzo0qWIecJ2AzDij65kmK4.1CNJBeyCqYYI411m',12,'Head Nursing','Nursing Department','Active','2026-06-09','2026-06-29 12:03:08',0,NULL,0,1,NULL,NULL,'2026-06-09 22:56:31','2026-07-03 05:14:47'),(12,NULL,'Head of Midwifery','midwifery-dep@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$xX6uOjGTbVHnq77cTjKBheWwYY33OeWP/hAb/FfaFVFEoeesjEtc6',13,'Head Midwifery','Midwifery Department','Active','2026-06-09','2026-07-01 10:08:43',0,NULL,0,1,NULL,NULL,'2026-06-09 22:56:33','2026-07-03 05:14:47'),(13,NULL,'Senior Lecturers','senior-lecturers@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$OEffzU3AOkx8wziH.nt4bu2raX/HyGv3IpdDQXMZqVwYj9MotSj.2',14,'Senior Lecturer','Academic Affairs','Active','2026-06-09','2026-06-29 12:08:55',0,NULL,0,1,NULL,NULL,'2026-06-09 22:56:33','2026-07-03 05:14:47'),(14,NULL,'Lecturers','lecturers@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$f5G4glNdiJ5HhWPrwakHD.FtGARFXBN5mDPbisPemvaSMF5VNAjhS',15,'Lecturer','Academic Affairs','Active','2026-06-09','2026-06-13 03:14:38',0,NULL,0,1,NULL,NULL,'2026-06-09 22:56:34','2026-07-03 05:14:47'),(15,NULL,'Matron','matron@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$9Rh2T.Jwe9ykgITF0waOk.TmlCjLQ86NT1.2rNhZJe2kNLv.qq1eK',16,'Matrons','Student Affairs','Active','2026-06-09','2026-06-29 12:12:51',0,NULL,0,1,NULL,NULL,'2026-06-09 22:56:34','2026-07-03 05:14:47'),(16,NULL,'Warden','warden@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$I1QssfbA4knhiBcpM6NlLOfaH3wclQ2th8xvQZcDP3kgaRLjIheQi',17,'Wardens','Student Affairs','Active','2026-06-09','2026-06-29 12:14:49',0,NULL,0,1,NULL,NULL,'2026-06-09 22:56:34','2026-07-03 05:14:47'),(17,NULL,'Sickbay Officer','sickbay@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$pjQ6f52wnY9zYohpBewoTuQ5Z46kg5yHg6SCpDfRh1hhLR2jFK5f6',18,'Sickbay','Support','Active','2026-06-09','2026-06-29 12:20:14',0,NULL,0,1,NULL,NULL,'2026-06-09 22:56:34','2026-07-03 05:14:47'),(18,NULL,'Driver','drivers@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$.YJEVKnLJMlGQcjCOxy1u.ZuahC98gmg04/RJQEV.6LNyN6MYLE7.',19,'Drivers','Transport','Active','2026-06-09','2026-07-02 18:58:20',0,NULL,0,1,NULL,NULL,'2026-06-09 22:56:35','2026-07-03 05:14:47'),(19,NULL,'Security Officer','security@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$65qSTfGLgwRrgRNiBuyfTOuqfMmpSuS2QANmmfG6VAw0FGR2aRQzG',20,'Security','Security Services','Active','2026-06-09','2026-06-13 03:14:39',0,NULL,0,1,NULL,NULL,'2026-06-09 22:56:35','2026-07-03 05:14:47'),(20,NULL,'Storekeeper','store@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$g5jTroKwPIL1SEbLYG4wqO1oPr7T6blGHXKj68gzwhY85SHiSnHBC',21,'Store Keeper','Facilities Management','Active','2026-06-09','2026-06-29 12:28:45',0,NULL,0,1,NULL,NULL,'2026-06-09 22:56:36','2026-07-03 05:14:47'),(21,NULL,'Guild President','guildpresident@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$n9XgEm1ItVC9.K7RqadF7eQGZmALTFAS5RKWoCEkXXj0zxUhRIoo.',22,'Guild President','Student Affairs','Active','2026-06-09','2026-06-13 03:14:39',0,NULL,0,1,NULL,NULL,'2026-06-09 22:56:36','2026-07-03 05:14:47'),(22,NULL,'Computer Lab Manager','computer-lab@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$zWybyrTprzkLr0B/0jwUB.iUeQJ.CVLUTZp9Z0dHfE.TEXPfI6Nza',23,'Director ICT','Information Communication Technology','Active','2026-06-09','2026-06-30 06:32:30',0,NULL,0,1,NULL,NULL,'2026-06-09 22:56:36','2026-07-03 05:14:47'),(23,NULL,'Danny ICT Director','dannybict@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$SmT1Yk4QWDbwASz0sPLzv.ywxHcPF2ttF6c3deyN.6A/p4ND.kWC6',5,'Director ICT','Information Technology','Active','2026-06-09','2026-07-02 21:05:46',0,NULL,0,1,NULL,NULL,'2026-06-09 22:56:36','2026-07-03 05:14:47'),(24,NULL,'Admissions Officer Derrick','admissions@igangaschoolofnursingandmidwifery.ac.ug','','$2y$10$cKEebG67aspdUj9BiDNrrO8Y76aIBYHbnlInrSKyvM5MpH97bR2qa',26,'Director Admissions & Requirements','Admissions','Active','2026-06-09','2026-07-02 16:36:15',0,NULL,1,1,NULL,NULL,'2026-06-09 22:56:37','2026-07-03 05:14:47'),(25,NULL,'School Bursar','bursar@igangaschoolofnursingandmidwifery.ac.ug',NULL,'$2y$10$LpzZa5tyhQAgS7ek5nPHB.6sACHFSR8Sy1FTGdAQC6uLBil8GX9xW',24,'School Bursar','Finance Department','Active','2026-06-10','2026-07-03 05:40:16',0,NULL,0,1,NULL,NULL,'2026-06-10 00:56:49','2026-07-03 05:14:47'),(51,'BURS002','Bursar','bursar.assistant@isnm.ac.ug',NULL,'$2y$10$U61BKsKqMuX1LajK/sSOme3yETx/qnoNw75CxEiBr7mX8pd.922v.',27,'Bursar','Finance Department','Active','2026-06-13',NULL,0,NULL,1,0,NULL,NULL,'2026-06-13 02:38:49','2026-06-13 02:38:49');
/*!40000 ALTER TABLE `staff` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_activity_log`
--

DROP TABLE IF EXISTS `staff_activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=359 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_activity_log`
--

LOCK TABLES `staff_activity_log` WRITE;
/*!40000 ALTER TABLE `staff_activity_log` DISABLE KEYS */;
INSERT INTO `staff_activity_log` VALUES (1,1,'Login','User logged in successfully','authentication','::1','curl/8.19.0','2026-06-09 23:06:48'),(2,4,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-06-09 23:07:33'),(3,4,'Login','User logged in successfully','authentication','::1','curl/8.19.0','2026-06-09 23:16:40'),(4,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-06-09 23:27:04'),(5,25,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-06-10 00:57:13'),(6,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-06-10 01:02:10'),(7,9,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-06-10 06:12:56'),(8,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-17 03:34:12'),(9,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-17 22:18:02'),(10,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 02:13:54'),(11,2,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 02:15:02'),(12,2,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 02:20:03'),(13,3,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 02:20:34'),(14,3,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 02:21:34'),(15,4,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 02:21:40'),(16,4,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 02:23:30'),(17,5,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 02:23:54'),(18,5,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 02:24:29'),(19,6,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 02:25:03'),(20,6,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 02:34:20'),(21,25,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 02:34:25'),(22,25,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 02:35:04'),(23,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 02:35:42'),(24,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 09:31:15'),(25,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 10:01:54'),(26,2,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 10:01:58'),(27,25,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 10:03:51'),(28,25,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 10:50:46'),(29,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 10:50:50'),(30,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 11:29:09'),(31,25,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 11:29:16'),(32,25,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 11:55:42'),(33,7,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 11:56:46'),(34,7,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 13:17:40'),(35,7,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 13:17:44'),(36,7,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 13:17:51'),(37,7,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 13:17:56'),(38,7,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 13:18:39'),(39,23,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 13:19:17'),(40,23,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 13:20:09'),(41,22,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 13:20:23'),(42,22,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 13:36:57'),(43,22,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 13:37:05'),(44,22,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 13:37:13'),(45,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 13:37:22'),(46,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 13:44:48'),(47,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 13:44:53'),(48,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 13:47:22'),(49,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 13:47:26'),(50,7,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 20:38:19'),(51,17,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-19 22:33:02'),(52,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-19 23:52:59'),(53,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 03:31:17'),(54,2,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 03:31:21'),(55,2,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 04:07:27'),(56,25,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 04:07:31'),(57,25,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 04:08:03'),(58,17,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 04:08:13'),(59,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 11:16:50'),(60,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 12:18:28'),(61,25,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 12:18:33'),(62,25,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 15:41:44'),(63,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 15:41:52'),(64,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 00:45:04'),(65,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 02:19:19'),(66,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 02:19:30'),(67,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 02:27:12'),(68,2,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 02:27:16'),(69,2,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 03:02:25'),(70,3,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 03:02:29'),(71,3,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 03:38:27'),(72,4,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 03:38:32'),(73,4,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 03:40:03'),(74,25,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 03:40:07'),(75,25,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 03:41:00'),(76,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 03:41:15'),(77,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 08:22:54'),(78,8,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 08:23:34'),(79,8,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 08:27:56'),(80,7,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 08:28:06'),(81,7,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 08:38:24'),(82,10,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 08:38:59'),(83,10,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 08:41:06'),(84,9,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 08:41:11'),(85,9,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 09:42:45'),(86,22,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 09:42:51'),(87,17,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 09:46:09'),(88,17,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 09:47:31'),(89,17,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 09:48:32'),(90,17,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 09:51:42'),(91,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 09:58:10'),(92,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 09:58:10'),(93,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 09:58:20'),(94,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 10:30:58'),(95,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 10:31:07'),(96,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 10:32:25'),(97,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 10:32:28'),(98,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 10:32:37'),(99,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 10:33:45'),(100,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 10:36:03'),(101,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 10:36:09'),(102,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 10:37:01'),(103,7,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 10:37:24'),(104,7,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 21:01:48'),(105,7,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 21:03:10'),(106,7,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 21:03:15'),(107,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 21:14:03'),(108,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 21:14:39'),(109,8,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 21:14:44'),(110,8,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 21:26:39'),(111,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 21:26:55'),(112,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 22:54:34'),(113,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 22:56:35'),(114,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 00:35:18'),(115,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 00:35:45'),(116,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 01:09:10'),(117,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 01:13:36'),(118,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 01:13:52'),(119,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 01:19:37'),(120,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 01:19:57'),(121,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 01:23:33'),(122,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 01:23:38'),(123,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 10:49:58'),(124,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 10:50:07'),(125,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 11:15:02'),(126,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 11:15:10'),(127,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 11:33:52'),(128,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 11:34:00'),(129,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 11:57:38'),(130,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 11:57:49'),(131,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 12:48:33'),(132,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 12:48:39'),(133,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 12:59:26'),(134,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 12:59:31'),(135,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 13:06:54'),(136,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 13:06:59'),(137,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 13:10:18'),(138,4,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 13:10:22'),(139,4,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 13:59:43'),(140,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 13:59:47'),(141,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 14:59:12'),(142,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 14:59:19'),(143,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 22:02:54'),(144,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 01:21:00'),(145,25,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 05:12:44'),(146,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 22:40:59'),(147,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 03:32:48'),(148,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 03:42:32'),(149,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 03:55:02'),(150,3,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 03:55:07'),(151,3,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 05:26:18'),(152,5,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 05:26:23'),(153,5,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 05:26:50'),(154,9,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 05:26:54'),(155,9,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 06:46:40'),(156,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 06:46:58'),(157,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 07:01:10'),(158,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 07:01:17'),(159,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 08:01:53'),(160,25,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 08:02:15'),(161,25,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 08:04:18'),(162,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 08:04:28'),(163,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 08:10:00'),(164,17,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 08:10:06'),(165,25,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 23:37:39'),(166,25,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 01:36:45'),(167,9,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 01:36:50'),(168,9,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 02:39:48'),(169,4,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 02:39:53'),(170,4,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 03:11:14'),(171,3,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 03:11:18'),(172,3,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 03:11:50'),(173,5,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 03:11:55'),(174,5,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 03:33:42'),(175,6,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 03:33:47'),(176,6,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 03:44:41'),(177,5,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 03:44:45'),(178,5,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 03:47:13'),(179,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 03:47:18'),(180,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 07:25:44'),(181,24,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 07:25:54'),(182,24,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 08:06:13'),(183,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 08:58:55'),(184,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 09:05:31'),(185,2,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 09:22:04'),(186,2,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 09:22:27'),(187,4,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 09:22:40'),(188,4,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 09:26:50'),(189,17,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 04:07:21'),(190,17,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 04:17:57'),(191,8,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 04:18:03'),(192,8,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 05:39:01'),(193,8,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 05:39:08'),(194,8,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 05:47:58'),(195,25,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 05:48:03'),(196,8,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8737','2026-06-26 06:02:14'),(197,8,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8737','2026-06-26 06:02:22'),(198,25,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8737','2026-06-26 06:03:07'),(199,8,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8737','2026-06-26 06:03:20'),(200,25,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 06:36:04'),(201,7,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 06:36:27'),(202,7,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 07:09:32'),(203,23,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 07:09:45'),(204,23,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 12:36:33'),(205,23,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 12:36:42'),(206,23,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 14:05:05'),(207,23,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 14:05:09'),(208,23,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 21:23:09'),(209,23,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 21:23:13'),(210,23,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 22:04:50'),(211,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 22:04:55'),(212,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 22:09:06'),(213,23,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 22:09:09'),(214,23,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 22:12:42'),(215,22,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 22:12:56'),(216,22,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 22:42:18'),(217,23,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 22:43:32'),(218,23,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 22:44:37'),(219,22,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 22:44:41'),(220,22,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 23:26:46'),(221,22,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 23:26:56'),(222,22,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 23:38:27'),(223,23,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 23:38:31'),(224,23,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 23:38:47'),(225,22,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 23:38:51'),(226,22,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 23:55:47'),(227,1,'Login','User logged in successfully','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 07:26:14'),(228,1,'Logout','User logged out','authentication','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 08:26:31'),(229,1,'Login','User logged in successfully','authentication','102.86.0.11','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-28 09:16:09'),(230,1,'Login','User logged in successfully','authentication','102.86.0.11','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-28 09:17:57'),(231,24,'Login','User logged in successfully','authentication','102.86.0.11','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-28 09:19:29'),(232,24,'Logout','User logged out','authentication','102.86.0.11','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-28 09:22:09'),(233,2,'Login','User logged in successfully','authentication','102.86.0.11','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-28 09:30:19'),(234,2,'Logout','User logged out','authentication','102.86.0.11','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-28 09:30:52'),(235,3,'Login','User logged in successfully','authentication','102.86.0.11','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-28 09:31:37'),(236,3,'Logout','User logged out','authentication','102.86.0.11','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-28 09:33:19'),(237,5,'Login','User logged in successfully','authentication','102.86.0.11','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-28 09:33:54'),(238,5,'Logout','User logged out','authentication','102.86.0.11','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-28 09:37:35'),(239,4,'Login','User logged in successfully','authentication','102.86.0.11','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-28 09:37:51'),(240,1,'Login','User logged in successfully','authentication','102.86.0.11','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-28 09:44:11'),(241,23,'Login','User logged in successfully','authentication','102.86.0.11','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-28 09:46:52'),(242,22,'Login','User logged in successfully','authentication','102.86.0.11','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-28 09:49:18'),(243,6,'Login','User logged in successfully','authentication','102.86.0.11','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-28 09:50:17'),(244,6,'Logout','User logged out','authentication','102.86.0.11','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-28 09:51:26'),(245,9,'Login','User logged in successfully','authentication','102.86.0.11','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-28 09:52:05'),(246,9,'Logout','User logged out','authentication','102.86.0.11','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-28 09:54:26'),(247,7,'Login','User logged in successfully','authentication','102.86.0.11','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-28 10:13:55'),(248,7,'Logout','User logged out','authentication','102.86.0.11','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-28 10:16:13'),(249,24,'Login','User logged in successfully','authentication','102.86.0.11','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-28 13:02:54'),(250,24,'Login','User logged in successfully','authentication','129.205.27.165','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.5 Safari/605.1.15','2026-06-28 18:45:21'),(251,24,'Logout','User logged out','authentication','129.205.27.165','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.5 Safari/605.1.15','2026-06-28 19:24:08'),(252,24,'Login','User logged in successfully','authentication','129.205.27.165','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.5 Safari/605.1.15','2026-06-28 19:25:29'),(253,24,'Logout','User logged out','authentication','129.205.27.165','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.5 Safari/605.1.15','2026-06-29 05:53:02'),(254,24,'Login','User logged in successfully','authentication','129.205.27.165','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.5 Safari/605.1.15','2026-06-29 05:53:50'),(255,24,'Logout','User logged out','authentication','129.205.27.165','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.5 Safari/605.1.15','2026-06-29 06:31:42'),(256,24,'Login','User logged in successfully','authentication','129.205.27.165','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.5 Safari/605.1.15','2026-06-29 06:33:33'),(257,24,'Login','User logged in successfully','authentication','41.210.141.186','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 07:37:35'),(258,24,'Logout','User logged out','authentication','41.210.141.186','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 07:40:31'),(259,1,'Login','User logged in successfully','authentication','41.210.141.186','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 07:48:56'),(260,1,'Logout','User logged out','authentication','41.210.141.186','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 07:53:38'),(261,24,'Login','User logged in successfully','authentication','41.210.141.186','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 07:53:42'),(262,1,'Login','User logged in successfully','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 08:42:33'),(263,1,'Logout','User logged out','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 08:43:20'),(264,3,'Login','User logged in successfully','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 08:43:33'),(265,3,'Logout','User logged out','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 08:44:06'),(266,24,'Login','User logged in successfully','authentication','41.210.141.140','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-29 08:50:24'),(267,1,'Login','User logged in successfully','authentication','197.239.12.138','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 09:46:09'),(268,1,'Logout','User logged out','authentication','197.239.12.138','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 09:46:31'),(269,3,'Login','User logged in successfully','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 10:17:08'),(270,3,'Logout','User logged out','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 10:20:55'),(271,1,'Login','User logged in successfully','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 10:22:55'),(272,1,'Logout','User logged out','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 10:25:16'),(273,4,'Login','User logged in successfully','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 10:25:43'),(274,4,'Logout','User logged out','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 10:26:50'),(275,5,'Login','User logged in successfully','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 10:27:12'),(276,5,'Logout','User logged out','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 10:28:00'),(277,6,'Login','User logged in successfully','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 10:28:25'),(278,6,'Logout','User logged out','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 10:29:36'),(279,24,'Login','User logged in successfully','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 10:30:58'),(280,24,'Logout','User logged out','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 10:32:28'),(281,7,'Login','User logged in successfully','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 10:32:43'),(282,24,'Login','User logged in successfully','authentication','102.85.249.154','Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.5 Mobile/15E148 Safari/604.1','2026-06-29 10:48:02'),(283,7,'Logout','User logged out','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 10:49:21'),(284,9,'Login','User logged in successfully','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 10:50:35'),(285,9,'Logout','User logged out','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 10:51:30'),(286,10,'Login','User logged in successfully','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 10:56:38'),(287,10,'Logout','User logged out','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 11:00:42'),(288,11,'Login','User logged in successfully','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 11:03:08'),(289,11,'Logout','User logged out','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 11:05:14'),(290,12,'Login','User logged in successfully','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 11:06:28'),(291,12,'Logout','User logged out','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 11:08:26'),(292,13,'Login','User logged in successfully','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 11:08:55'),(293,13,'Logout','User logged out','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 11:12:28'),(294,15,'Login','User logged in successfully','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 11:12:51'),(295,15,'Logout','User logged out','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 11:14:17'),(296,16,'Login','User logged in successfully','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 11:14:49'),(297,16,'Logout','User logged out','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 11:19:13'),(298,17,'Login','User logged in successfully','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 11:20:14'),(299,17,'Logout','User logged out','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 11:21:47'),(300,18,'Login','User logged in successfully','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 11:22:56'),(301,18,'Logout','User logged out','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 11:24:32'),(302,20,'Login','User logged in successfully','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 11:28:45'),(303,20,'Logout','User logged out','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 11:42:40'),(304,23,'Login','User logged in successfully','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 11:48:39'),(305,23,'Logout','User logged out','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 11:52:12'),(306,22,'Login','User logged in successfully','authentication','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 11:52:26'),(307,1,'Login','User logged in successfully','authentication','197.239.12.138','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 15:43:26'),(308,1,'Login','User logged in successfully','authentication','197.239.12.138','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 16:08:09'),(309,24,'Login','User logged in successfully','authentication','197.239.12.138','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-29 17:16:36'),(310,24,'Logout','User logged out','authentication','129.205.27.165','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.5 Safari/605.1.15','2026-06-29 18:08:27'),(311,24,'Login','User logged in successfully','authentication','129.205.27.165','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.5 Safari/605.1.15','2026-06-29 18:09:05'),(312,1,'Logout','User logged out','authentication','102.86.0.21','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 20:17:46'),(313,1,'Login','User logged in successfully','authentication','102.86.0.21','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-30 05:05:33'),(314,22,'Login','User logged in successfully','authentication','102.86.0.21','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-30 05:32:30'),(315,22,'Logout','User logged out','authentication','102.86.0.21','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-30 05:33:25'),(316,10,'Login','User logged in successfully','authentication','41.210.141.87','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-01 09:04:36'),(317,3,'Login','User logged in successfully','authentication','41.210.141.87','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-07-01 09:05:24'),(318,3,'Logout','User logged out','authentication','41.210.141.87','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-07-01 09:05:32'),(319,1,'Login','User logged in successfully','authentication','41.210.141.87','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-07-01 09:05:44'),(320,10,'Logout','User logged out','authentication','41.210.141.87','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-01 09:06:01'),(321,12,'Login','User logged in successfully','authentication','41.210.141.87','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-01 09:08:43'),(322,24,'Login','User logged in successfully','authentication','41.210.141.87','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-07-01 09:09:11'),(323,24,'Login','User logged in successfully','authentication','41.210.141.87','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-01 09:10:53'),(324,24,'Logout','User logged out','authentication','41.210.141.87','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-01 09:11:44'),(325,23,'Login','User logged in successfully','authentication','41.210.141.87','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-01 09:12:59'),(326,24,'Logout','User logged out','authentication','41.210.141.87','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-07-01 09:20:39'),(327,1,'Login','User logged in successfully','authentication','102.86.2.161','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-07-01 10:13:45'),(328,24,'Login','User logged in successfully','authentication','102.86.2.161','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-07-01 12:18:18'),(329,1,'Login','User logged in successfully','authentication','102.86.2.161','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-07-01 12:33:22'),(330,12,'Logout','User logged out','authentication','102.86.2.161','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-01 14:04:01'),(331,1,'Login','User logged in successfully','authentication','102.86.2.161','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-07-01 16:10:35'),(332,1,'Logout','User logged out','authentication','102.86.2.161','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-07-01 16:17:41'),(333,3,'Login','User logged in successfully','authentication','102.86.2.161','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-07-01 16:18:49'),(334,24,'Login','User logged in successfully','authentication','129.205.27.165','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.5 Safari/605.1.15','2026-07-01 16:23:37'),(335,3,'Logout','User logged out','authentication','102.86.2.161','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-07-01 16:23:39'),(336,24,'Login','User logged in successfully','authentication','102.86.2.161','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-07-01 16:24:11'),(337,24,'Logout','User logged out','authentication','102.86.2.161','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-07-01 16:40:19'),(338,9,'Login','User logged in successfully','authentication','102.86.2.161','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-07-01 16:41:33'),(339,9,'Logout','User logged out','authentication','102.86.2.161','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-07-01 16:51:35'),(340,7,'Login','User logged in successfully','authentication','102.86.2.161','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-07-01 16:58:34'),(341,7,'Logout','User logged out','authentication','102.86.2.161','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-07-01 17:01:08'),(342,23,'Login','User logged in successfully','authentication','102.86.8.114','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-07-01 19:00:15'),(343,24,'Login','User logged in successfully','authentication','102.86.8.114','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-02 08:56:25'),(344,24,'Login','User logged in successfully','authentication','102.86.8.114','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-07-02 09:00:27'),(345,24,'Login','User logged in successfully','authentication','102.86.8.114','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-02 09:00:55'),(346,24,'Login','User logged in successfully','authentication','102.85.205.216','Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.5 Mobile/15E148 Safari/604.1','2026-07-02 09:05:01'),(347,24,'Logout','User logged out','authentication','102.86.8.114','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-02 09:28:48'),(348,1,'Login','User logged in successfully','authentication','102.86.8.114','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-02 09:29:17'),(349,24,'Login','User logged in successfully','authentication','129.205.27.165','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.5 Safari/605.1.15','2026-07-02 15:32:07'),(350,24,'Logout','User logged out','authentication','129.205.27.165','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.5 Safari/605.1.15','2026-07-02 15:34:51'),(351,24,'Login','User logged in successfully','authentication','129.205.27.165','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.5 Safari/605.1.15','2026-07-02 15:36:15'),(352,18,'Login','User logged in successfully','authentication','102.86.8.114','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-07-02 17:58:20'),(353,18,'Logout','User logged out','authentication','102.86.8.114','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-07-02 17:59:01'),(354,1,'Login','User logged in successfully','authentication','102.86.8.114','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-07-02 17:59:12'),(355,23,'Login','User logged in successfully','authentication','102.85.6.218','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0','2026-07-02 20:05:46'),(356,25,'Login','User logged in successfully','authentication','102.34.28.10','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-03 03:55:07'),(357,25,'Login','User logged in successfully','authentication','41.210.155.211','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-03 04:16:31'),(358,25,'Login','User logged in successfully','authentication','41.210.155.211','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-03 04:40:17');
/*!40000 ALTER TABLE `staff_activity_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_appraisals`
--

DROP TABLE IF EXISTS `staff_appraisals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
  KEY `idx_attendance_status` (`status`),
  KEY `idx_sa_staff_date` (`staff_id`,`date`),
  KEY `idx_sa_status_date` (`status`,`date`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_attendance`
--

LOCK TABLES `staff_attendance` WRITE;
/*!40000 ALTER TABLE `staff_attendance` DISABLE KEYS */;
INSERT INTO `staff_attendance` VALUES (1,1,'2026-06-20','Absent',NULL,NULL,NULL,NULL,'2026-06-19 23:58:56'),(2,2,'2026-06-20','On Leave',NULL,NULL,NULL,NULL,'2026-06-19 23:58:56'),(3,3,'2026-06-20','Present',NULL,NULL,NULL,NULL,'2026-06-19 23:58:56'),(4,4,'2026-06-20','On Leave',NULL,NULL,NULL,NULL,'2026-06-19 23:58:56'),(5,23,'2026-06-20','Present',NULL,NULL,NULL,NULL,'2026-06-19 23:58:56'),(6,5,'2026-06-20','Present',NULL,NULL,NULL,NULL,'2026-06-19 23:58:56'),(7,6,'2026-06-20','Late',NULL,NULL,NULL,NULL,'2026-06-19 23:58:56'),(8,7,'2026-06-20','Absent',NULL,NULL,NULL,NULL,'2026-06-19 23:58:56'),(9,24,'2026-06-20','Present',NULL,NULL,NULL,NULL,'2026-06-19 23:58:56'),(10,8,'2026-06-20','Present',NULL,NULL,NULL,NULL,'2026-06-19 23:58:56'),(11,9,'2026-06-20','On Leave',NULL,NULL,NULL,NULL,'2026-06-19 23:58:56'),(12,10,'2026-06-20','Present',NULL,NULL,NULL,NULL,'2026-06-19 23:58:56'),(13,11,'2026-06-20','Present',NULL,NULL,NULL,NULL,'2026-06-19 23:58:56'),(14,12,'2026-06-20','Present',NULL,NULL,NULL,NULL,'2026-06-19 23:58:56'),(15,13,'2026-06-20','Late',NULL,NULL,NULL,NULL,'2026-06-19 23:58:56'),(16,14,'2026-06-20','On Leave',NULL,NULL,NULL,NULL,'2026-06-19 23:58:56'),(17,15,'2026-06-20','Present',NULL,NULL,NULL,NULL,'2026-06-19 23:58:56'),(18,16,'2026-06-20','Present',NULL,NULL,NULL,NULL,'2026-06-19 23:58:56'),(19,17,'2026-06-20','Present',NULL,NULL,NULL,NULL,'2026-06-19 23:58:56'),(20,18,'2026-06-20','Late',NULL,NULL,NULL,NULL,'2026-06-19 23:58:56');
/*!40000 ALTER TABLE `staff_attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_communications`
--

DROP TABLE IF EXISTS `staff_communications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
INSERT INTO `staff_departments` VALUES (1,'Executive Leadership','EXEC',1,NULL,1,'2026-06-19 23:58:56'),(2,'Academic Affairs','ACAD',2,NULL,1,'2026-06-19 23:58:56'),(3,'Finance & Accounts','FIN',3,NULL,1,'2026-06-19 23:58:56'),(4,'Human Resources','HR',4,NULL,1,'2026-06-19 23:58:56'),(5,'Nursing Department','NUR',5,NULL,1,'2026-06-19 23:58:56'),(6,'Midwifery Department','MID',6,NULL,1,'2026-06-19 23:58:56'),(7,'ICT','ICT',7,NULL,1,'2026-06-19 23:58:56'),(8,'Admissions','ADM',8,NULL,1,'2026-06-19 23:58:56'),(9,'Library','LIB',9,NULL,1,'2026-06-19 23:58:56'),(10,'Security & Transport','SEC',10,NULL,1,'2026-06-19 23:58:56'),(11,'Store & Assets','STR',11,NULL,1,'2026-06-19 23:58:56'),(12,'Student Services','SVS',12,NULL,1,'2026-06-19 23:58:56');
/*!40000 ALTER TABLE `staff_departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_disciplinary`
--

DROP TABLE IF EXISTS `staff_disciplinary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_disciplinary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `incident_date` date NOT NULL,
  `offense_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `action_taken` text DEFAULT NULL,
  `status` enum('Open','Under Investigation','Resolved','Closed') DEFAULT 'Open',
  `reported_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_disc_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_disciplinary`
--

LOCK TABLES `staff_disciplinary` WRITE;
/*!40000 ALTER TABLE `staff_disciplinary` DISABLE KEYS */;
/*!40000 ALTER TABLE `staff_disciplinary` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_inbox`
--

DROP TABLE IF EXISTS `staff_inbox`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_inbox` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` int(10) unsigned NOT NULL,
  `sender_name` varchar(120) NOT NULL DEFAULT '',
  `sender_role` varchar(80) NOT NULL DEFAULT '',
  `recipient_id` int(10) unsigned NOT NULL,
  `recipient_name` varchar(120) NOT NULL DEFAULT '',
  `subject` varchar(255) NOT NULL DEFAULT '',
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `parent_id` int(10) unsigned DEFAULT NULL,
  `is_deleted_sender` tinyint(1) NOT NULL DEFAULT 0,
  `is_deleted_recipient` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sender` (`sender_id`),
  KEY `idx_recipient` (`recipient_id`),
  KEY `idx_thread` (`parent_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_inbox`
--

LOCK TABLES `staff_inbox` WRITE;
/*!40000 ALTER TABLE `staff_inbox` DISABLE KEYS */;
/*!40000 ALTER TABLE `staff_inbox` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_leave_requests`
--

DROP TABLE IF EXISTS `staff_leave_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_leave_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `leave_type_id` int(11) DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Cancelled') DEFAULT 'Pending',
  `approved_by` int(11) DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `approval_date` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_lr_staff` (`staff_id`),
  KEY `idx_slr_dates` (`start_date`,`end_date`),
  KEY `idx_slr_type` (`leave_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_leave_requests`
--

LOCK TABLES `staff_leave_requests` WRITE;
/*!40000 ALTER TABLE `staff_leave_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `staff_leave_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_licenses`
--

DROP TABLE IF EXISTS `staff_licenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_licenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) DEFAULT NULL,
  `license_type` varchar(100) NOT NULL,
  `license_number` varchar(100) DEFAULT NULL,
  `issuing_body` varchar(200) DEFAULT NULL,
  `issue_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'valid',
  `document_path` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_license_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_licenses`
--

LOCK TABLES `staff_licenses` WRITE;
/*!40000 ALTER TABLE `staff_licenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `staff_licenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_login_sessions`
--

DROP TABLE IF EXISTS `staff_login_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_login_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `session_token` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=210 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_login_sessions`
--

LOCK TABLES `staff_login_sessions` WRITE;
/*!40000 ALTER TABLE `staff_login_sessions` DISABLE KEYS */;
INSERT INTO `staff_login_sessions` VALUES (1,1,'pu2hvlihjqangi7jviepaf0ob7','::1','curl/8.19.0','2026-06-09 23:06:48','2026-06-09 23:36:48'),(2,4,'83656fpgh06q9gouhm60nk3tuq','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-06-09 23:07:33','2026-06-09 23:37:33'),(3,4,'lh39hd80nldj2uegqkjhjk2efn','::1','curl/8.19.0','2026-06-09 23:16:40','2026-06-09 23:46:40'),(4,1,'7ljqo58oc291b11bqi2s3cjffg','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-06-09 23:27:04','2026-06-09 23:57:04'),(5,25,'hlr81jh15cqvlf6nl6j8nlhk3f','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-06-10 00:57:13','2026-06-10 01:27:13'),(6,24,'ae3he9cgsdvgdf024bolec2r14','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-06-10 01:02:10','2026-06-10 01:32:10'),(7,9,'dr24ed01jpd3hparhq890kpnf0','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36','2026-06-10 06:12:56','2026-06-10 06:42:56'),(8,1,'k8j0smrve1hncrjkq2he9fu0rh','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-17 03:34:12','2026-06-17 04:04:12'),(9,1,'2f99647bj7odhsl4cj6vhlals8','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-17 22:18:02','2026-06-17 22:48:02'),(10,2,'suho7uaqglfdjpgt6f6bpr0nqb','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 02:15:02','2026-06-18 02:45:02'),(11,3,'gn380t4p7ebopr4pbmd83r3098','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 02:20:34','2026-06-18 02:50:34'),(12,4,'j0bvg0i2bsstfd5f2b71pnbhv2','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 02:21:40','2026-06-18 02:51:40'),(13,5,'1p2sqtjhn2q39oq8uok2bka2s6','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 02:23:54','2026-06-18 02:53:54'),(14,6,'ebpn95qsf7pvk6jr5iad1vi3lk','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 02:25:03','2026-06-18 02:55:03'),(15,25,'s2q2c95audemj51h44e3vmah41','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 02:34:25','2026-06-18 03:04:25'),(16,24,'qf5sbbkufe4onpt0j5qdg8cfp4','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 02:35:42','2026-06-18 03:05:42'),(17,1,'1359ma7hua0fmmvl8espcd9an7','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 09:31:15','2026-06-18 10:01:15'),(18,2,'blmvsuvsqc3h3fq4ed857p8asq','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 10:01:58','2026-06-18 10:31:58'),(19,25,'vv9i7126ujrh0ht0sekerd5vnq','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 10:03:51','2026-06-18 10:33:51'),(20,1,'sc7nqfk1p54kusvoh7959k81c7','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 10:50:50','2026-06-18 11:20:50'),(21,25,'6rclk83t17947n4pj1ngh9hj81','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 11:29:16','2026-06-18 11:59:16'),(22,7,'30mj4uha05dsb1rdea48hkrgb5','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 11:56:46','2026-06-18 12:26:46'),(23,7,'2hlgnoq56hhvf37ar4im2ue7nm','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 13:17:44','2026-06-18 13:47:44'),(24,7,'gpqmln2qp7o00ek4rjjenf4khj','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 13:17:56','2026-06-18 13:47:56'),(25,23,'34mpge2kds50ab697a1agal7us','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 13:19:17','2026-06-18 13:49:17'),(26,22,'mmt06pq180c82ofjuf8hgihiva','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 13:20:23','2026-06-18 13:50:23'),(27,22,'jef954p75gcad385f70ig9tadc','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 13:37:05','2026-06-18 14:07:05'),(28,24,'t8g4s4ib33vp3villv4iv8p5no','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 13:37:22','2026-06-18 14:07:22'),(29,24,'h0l3knqrvi229h6laq5ltln2to','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 13:44:53','2026-06-18 14:14:53'),(30,24,'jv52bv72042nq2v2vileprunqr','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 13:47:26','2026-06-18 14:17:26'),(31,7,'0pa58vehm4juir1f0924c929eu','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-18 20:38:19','2026-06-18 21:08:19'),(32,17,'gv23nhcevrnc6cu2sqj1q6ksp0','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-19 22:33:02','2026-06-19 23:03:02'),(33,1,'erd3bpes4jq9qfk173g5561tn7','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-19 23:52:59','2026-06-20 00:22:59'),(34,2,'c31ettfnja46ueh449bkq22vh7','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 03:31:21','2026-06-20 04:01:21'),(35,25,'79007ugk7c1mi07d7m5c9l9c71','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 04:07:31','2026-06-20 04:37:31'),(36,17,'6a3hb5erpafv3av162128t5dpb','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 04:08:13','2026-06-20 04:38:13'),(37,1,'qbin2lntmfe0ctm7s80b5ccsi6','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 11:16:50','2026-06-20 11:46:50'),(38,25,'63qd2kbtvalb6jlf259akkthcc','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 12:18:33','2026-06-20 12:48:33'),(39,1,'5adsl9dnpdml0l9089vi78sk1j','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-20 15:41:52','2026-06-20 16:11:52'),(40,1,'titkd3lgrb6p0n2s92875b1f1l','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 00:45:04','2026-06-21 01:15:04'),(41,1,'np4ea04g9arhbh2ticlj8a8jk0','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 02:19:30','2026-06-21 02:49:30'),(42,2,'06f1nkaks13lc7ht4kvuq2sl56','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 02:27:16','2026-06-21 02:57:16'),(43,3,'dqv8stll1pfe9kmc8lkvchal44','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 03:02:29','2026-06-21 03:32:29'),(44,4,'mkqhi86baa63c035veice145ll','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 03:38:32','2026-06-21 04:08:32'),(45,25,'5tu70v12sp531pvi6bnd4ktr1f','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 03:40:07','2026-06-21 04:10:07'),(46,24,'2pgtv3ai29nri6qac8qrc9ff13','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 03:41:15','2026-06-21 04:11:15'),(47,8,'3fjn3qhpi54ad00ig5jr46adrh','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 08:23:34','2026-06-21 08:53:34'),(48,7,'mfufdau7qocjbtu885pm8ko2od','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 08:28:06','2026-06-21 08:58:06'),(49,10,'rvau0732fn5eb7aq561lc5qkm1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 08:38:59','2026-06-21 09:08:59'),(50,9,'vbguukqdpatqmm20c3m9gjjkcf','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 08:41:11','2026-06-21 09:11:11'),(51,22,'tq6q7ogmro8nmn0207kngvd21h','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 09:42:51','2026-06-21 10:12:51'),(52,17,'u7kckp0ni8u4jro21r3902smaj','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 09:46:09','2026-06-21 10:16:09'),(53,17,'vmp1feirc6evkuqm4kr14kivj1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 09:48:32','2026-06-21 10:18:32'),(54,24,'0a98v19vemano2jnpabb2j1p2g','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 09:58:10','2026-06-21 10:28:10'),(55,24,'0s7tbe4ouk2fiht3obv1nvphav','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 09:58:20','2026-06-21 10:28:20'),(56,24,'cukgdorpavsii00locajfjqpci','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 10:31:07','2026-06-21 11:01:07'),(57,24,'lslmk523ctp75jif1nie3uqd82','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 10:32:28','2026-06-21 11:02:28'),(58,24,'veofu3mv8j6t624aa4fs53p2fn','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 10:33:45','2026-06-21 11:03:45'),(59,24,'4cqichecqd00evma1u7sbk3j2q','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 10:36:09','2026-06-21 11:06:09'),(60,7,'d522eht2ekupd06is4b5571tss','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 10:37:24','2026-06-21 11:07:24'),(61,7,'irrr02lhbcgfrpu4l69j7d7fvl','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 21:03:10','2026-06-21 21:33:10'),(62,24,'f2v52677oj0d7cv0fts20sga63','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 21:14:03','2026-06-21 21:44:03'),(63,8,'q96npe7qia97egg4delvjpcd0u','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 21:14:44','2026-06-21 21:44:44'),(64,24,'jmg7854n7jgeu8odup6l9iot29','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 21:26:55','2026-06-21 21:56:55'),(65,24,'4tblptijta3l5tfta0pvb25601','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-21 22:56:35','2026-06-21 23:26:35'),(66,24,'hpq3utci8urukiaruh92vob8mn','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 00:35:45','2026-06-22 01:05:45'),(67,24,'t49b41nfcaruon5ro15p15mltc','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 01:09:10','2026-06-22 01:39:10'),(68,24,'osoor0p43434atvgr7j66t22f6','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 01:13:52','2026-06-22 01:43:52'),(69,24,'0me8chv0u24pr6jfgg9oe23lov','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 01:19:57','2026-06-22 01:49:57'),(70,24,'kdlr506tct65oilrnuj67pb72i','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 01:23:38','2026-06-22 01:53:38'),(71,24,'jslvmsv36efl4ukgf1q7g2skrt','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 10:50:07','2026-06-22 11:20:07'),(72,24,'hdvul1svlg8ui13hcqk01cclda','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 11:15:10','2026-06-22 11:45:10'),(73,24,'18d7dqitp2ml8j9nqte0qvvtbc','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 11:34:00','2026-06-22 12:04:00'),(74,24,'ur2fs528fiomfrd25ggfbntevu','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 11:57:49','2026-06-22 12:27:49'),(75,1,'qrs5r3d0crst274csne31s6bik','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 12:48:39','2026-06-22 13:18:39'),(76,24,'pqumq7aq89oarcoi7u0vfc4euc','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 12:59:31','2026-06-22 13:29:31'),(77,1,'4dfl0lha5fktfpih6dio8m629n','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 13:06:59','2026-06-22 13:36:59'),(78,4,'uoi1qs187pr2gd1799ousa63cu','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 13:10:22','2026-06-22 13:40:22'),(79,1,'1s9luflbvjvmdor92928kc9lbf','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 13:59:47','2026-06-22 14:29:47'),(80,24,'ttjcmn74g42n5lstnqmv6pijpu','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 14:59:19','2026-06-22 15:29:19'),(81,1,'1pmpbl6de5stu5mdelph4h475e','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-22 22:02:54','2026-06-22 22:32:54'),(82,24,'hljpb5ph7e3j24ckvan8mauluh','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 01:21:00','2026-06-23 01:51:00'),(83,25,'ks1tultbpko3s70j5fhq4e4t9h','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 05:12:44','2026-06-23 05:42:44'),(84,1,'rf0mklksts3um16lm1c90l2gbq','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-23 22:40:59','2026-06-23 23:10:59'),(85,1,'ppfvcia8sprhfv7t5i2dsn38a0','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 03:42:32','2026-06-24 04:12:32'),(86,3,'lgp10qeu8kiecak9fv098pjglo','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 03:55:07','2026-06-24 04:25:07'),(87,5,'6pu90rj74r1pq47q228fcjoniu','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 05:26:23','2026-06-24 05:56:23'),(88,9,'jfn6065k8m3goqpe3sprr17b6p','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 05:26:54','2026-06-24 05:56:54'),(89,1,'794af9ukhkur706kvkfku12iaq','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 06:46:58','2026-06-24 07:16:58'),(90,24,'9jebpeo9cqldprvn4khlrnr9vi','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 07:01:17','2026-06-24 07:31:17'),(91,25,'8a4liolknvm9st9v1kv3r106fl','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 08:02:15','2026-06-24 08:32:15'),(92,1,'25vgr9ffnilj3iaufkdi2olvgd','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 08:04:28','2026-06-24 08:34:28'),(93,17,'f584elfqqdtdaneqgvu4j2sgb5','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 08:10:06','2026-06-24 08:40:06'),(94,25,'ik5cfqa2gkgjgfmgmefk090jjd','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-24 23:37:39','2026-06-25 00:07:39'),(95,9,'34ca3u1j6isj17ocoq43vba38c','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 01:36:50','2026-06-25 02:06:50'),(96,4,'e5vsb9gmjeun7kito01jamqs3d','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 02:39:53','2026-06-25 03:09:53'),(97,3,'7rqduhslro0lq0nplmpbunpgf0','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 03:11:18','2026-06-25 03:41:18'),(98,5,'tl71050ga502dbhf0tggle7b8d','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 03:11:55','2026-06-25 03:41:55'),(99,6,'89n9gmr0fjrhmuuolavg7bh7vj','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 03:33:47','2026-06-25 04:03:47'),(100,5,'t5s26jd6cbasdfv24scv7oqgnc','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 03:44:45','2026-06-25 04:14:45'),(101,24,'p2ek6i7irhqbkkppvei7r15olf','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 03:47:18','2026-06-25 04:17:18'),(102,24,'btumr0h7kam4vbeliviv0vht80','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 07:25:54','2026-06-25 07:55:54'),(103,1,'affknfo0e0cod2oi2jru0qjgph','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 08:58:55','2026-06-25 09:28:55'),(104,2,'h447aeemqdhvj8dlaofabvmss9','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 09:22:04','2026-06-25 09:52:04'),(105,4,'a087m1fgf0fu8elbeoe57g6il1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-25 09:22:40','2026-06-25 09:52:40'),(106,17,'n9o9l07t52qa71jjmg3l9egl3q','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 04:07:21','2026-06-26 04:37:21'),(107,8,'j92qk81fhdbtt122h79ckue45f','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 04:18:03','2026-06-26 04:48:03'),(108,8,'edj83pm5bjgr8g6vbeci45ajod','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 05:39:08','2026-06-26 06:09:08'),(109,25,'41s1vms3719jbporauptnbjumd','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 05:48:03','2026-06-26 06:18:03'),(110,8,'3vg1268gsos1b49pha89j8qcdl','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8737','2026-06-26 06:02:14','2026-06-26 06:32:14'),(111,8,'t0va2mgidaq269fsdhp6dscgr3','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8737','2026-06-26 06:02:22','2026-06-26 06:32:22'),(112,25,'gq0ncrfvok3ljs5trl2u2meclg','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8737','2026-06-26 06:03:07','2026-06-26 06:33:07'),(113,8,'22gvam5engahgclurhh82mppuh','::1','Mozilla/5.0 (Windows NT; Windows NT 10.0; en-US) WindowsPowerShell/5.1.26100.8737','2026-06-26 06:03:20','2026-06-26 06:33:20'),(114,7,'nj45vbijbkug1j88nncnts0oah','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 06:36:27','2026-06-26 07:06:27'),(115,23,'9m3qh7jl3j8bq9fm0qafu02snb','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 07:09:45','2026-06-26 07:39:45'),(116,23,'3tnohnrv7m0us60fmm0slh4vjn','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 12:36:42','2026-06-26 13:06:42'),(117,23,'odl6daac37jvuiaqq30ebv211t','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 14:05:09','2026-06-26 14:35:09'),(118,23,'vgh2eulctao8s12rdgdu77t1q7','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 21:23:13','2026-06-26 21:53:13'),(119,1,'ijjcb8ppqqeg0eh07rctuob530','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 22:04:55','2026-06-26 22:34:55'),(120,23,'98sb0ebgrcuh2adlceuj198qfm','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 22:09:09','2026-06-26 22:39:09'),(121,22,'6undm5ctv6iltdc39d2cnr2kom','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 22:12:56','2026-06-26 22:42:56'),(122,23,'1vhpuhmg2t9v6s93e4vt1gdasl','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 22:43:32','2026-06-26 23:13:32'),(123,22,'7qj0v2vurgdm825hnht7cc6cki','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 22:44:41','2026-06-26 23:14:41'),(124,22,'384f35su5t0cqbgqg2q623n8e7','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 23:26:56','2026-06-26 23:56:56'),(125,23,'cl13gqakjddlkkagtdm07brjs2','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 23:38:31','2026-06-27 00:08:31'),(126,22,'hvq4ej7521ircu9s4qdi9a4nu8','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 23:38:51','2026-06-27 00:08:51'),(127,22,'89a3kokapu2e5308u309ftsdoa','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-26 23:55:47','2026-06-27 00:25:47'),(128,1,'obs5jdudi11f3h2ffk564c90j6','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-27 07:26:14','2026-06-27 07:56:14'),(129,1,'dtfc42ie2jp7q8lg7d7rtmq951','102.86.0.11','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-28 09:16:09','2026-06-28 10:46:09'),(130,1,'g1uof5ac3uqajq5uv4u2jltegl','102.86.0.11','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-28 09:17:57','2026-06-28 10:47:57'),(131,24,'fvfvneojdba8pqd2gkcoo64ce5','102.86.0.11','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-28 09:19:29','2026-06-28 10:49:29'),(132,2,'fj6hnb7dr6289crsbi7657j1ob','102.86.0.11','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-28 09:30:19','2026-06-28 11:00:19'),(133,3,'g1saq1sdjmfoa80f5kg0fv821c','102.86.0.11','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-28 09:31:37','2026-06-28 11:01:37'),(134,5,'rrjt2b1247sav01l64kbnmo1df','102.86.0.11','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-28 09:33:54','2026-06-28 11:03:54'),(135,4,'uicdfkse2n65g5f1psihl509e8','102.86.0.11','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-28 09:37:51','2026-06-28 11:07:51'),(136,1,'dqlh2i1v44eo808b95i60ae3sh','102.86.0.11','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-28 09:44:11','2026-06-28 11:14:11'),(137,23,'f8ve2bfdh4u62milleta0hm4qi','102.86.0.11','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-28 09:46:52','2026-06-28 11:16:52'),(138,22,'24k7bh1lhnt9vr72lir40k9qqs','102.86.0.11','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-28 09:49:18','2026-06-28 11:19:18'),(139,6,'4ae0fftlpko6no17n90e5fk1i0','102.86.0.11','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-28 09:50:17','2026-06-28 11:20:17'),(140,9,'qf8ddcmjjju34nf4m7609t7p1m','102.86.0.11','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-28 09:52:05','2026-06-28 11:22:05'),(141,7,'tqf8pahte5cb8sn2rgsu2l4lmr','102.86.0.11','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-28 10:13:55','2026-06-28 11:43:55'),(142,24,'5tr9c2jnvukj2a1cvt2fagq986','102.86.0.11','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-28 13:02:54','2026-06-28 14:32:54'),(143,24,'mdjqqnuu147ms2gm7ulp1q0obe','129.205.27.165','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.5 Safari/605.1.15','2026-06-28 18:45:21','2026-06-28 20:15:21'),(144,24,'8r37ni8j63gpfd2obs3u9dktb1','129.205.27.165','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.5 Safari/605.1.15','2026-06-28 19:25:29','2026-06-28 20:55:29'),(145,24,'lt45lctg9h9e6p0kaa4kdoie7r','129.205.27.165','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.5 Safari/605.1.15','2026-06-29 05:53:50','2026-06-29 07:23:50'),(146,24,'r8gmdhbs72lm14qm1tdo93tt7e','129.205.27.165','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.5 Safari/605.1.15','2026-06-29 06:33:33','2026-06-29 08:03:33'),(147,24,'lfuu64e523p3hv8mdcqn2kva1u','41.210.141.186','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 07:37:35','2026-06-29 09:07:35'),(148,1,'n18n858in9mbd04jc1i6ireo4k','41.210.141.186','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 07:48:56','2026-06-29 09:18:56'),(149,24,'h26net6fasru2thrnni7vvc9eg','41.210.141.186','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 07:53:42','2026-06-29 09:23:42'),(150,1,'56icvij3hjujd3kgh5mk5sjldn','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 08:42:33','2026-06-29 10:12:33'),(151,3,'ibpj21ihm9h6l5gbuq778labop','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 08:43:33','2026-06-29 10:13:33'),(152,24,'g3t1d9ekti0067mg6f3mgdl5ev','41.210.141.140','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-29 08:50:24','2026-06-29 10:20:24'),(153,1,'8cuiidjs2suu4nq01fqju5i4ev','197.239.12.138','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 09:46:09','2026-06-29 11:16:09'),(154,3,'7h5trdnn0eg6d9pi6l6qsqfl5a','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 10:17:08','2026-06-29 11:47:08'),(155,1,'jgsruvj127fkngplufdv9djpc7','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 10:22:55','2026-06-29 11:52:55'),(156,4,'lftdug5l6lqp2dttu0khcmk2ki','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 10:25:43','2026-06-29 11:55:43'),(157,5,'l569f6sre8oqob80unp83i77an','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 10:27:12','2026-06-29 11:57:12'),(158,6,'v5scce1m0t6ic68iupcg38fp9b','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 10:28:25','2026-06-29 11:58:25'),(159,24,'lmvlqep1pf629t0sdq98uuno4e','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 10:30:58','2026-06-29 12:00:58'),(160,7,'usakh1hva9ob1s7s7c76f1fev3','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 10:32:43','2026-06-29 12:02:43'),(161,24,'er4cb63u1f2eqikqei03vlfe51','102.85.249.154','Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.5 Mobile/15E148 Safari/604.1','2026-06-29 10:48:02','2026-06-29 12:18:02'),(162,9,'u6ui99km1ec0ffcsm59p0tad35','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 10:50:35','2026-06-29 12:20:35'),(163,10,'4hm46dthvdb172fg7kfqj69lt8','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 10:56:38','2026-06-29 12:26:38'),(164,11,'dk6gjmgq3hti2kph6b1g9f9aai','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 11:03:08','2026-06-29 12:33:08'),(165,12,'0jjuu05mkb0931l50ednlhcda4','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 11:06:28','2026-06-29 12:36:28'),(166,13,'3gdf84615p9jktsq5p2434fibu','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 11:08:55','2026-06-29 12:38:55'),(167,15,'112cssgubal5ndf3lh7kj1jf8t','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 11:12:51','2026-06-29 12:42:51'),(168,16,'45p0hcpea2s6mavvrace6c734f','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 11:14:49','2026-06-29 12:44:49'),(169,17,'97v6djpqo7euois8cdo5lai975','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 11:20:14','2026-06-29 12:50:14'),(170,18,'savr4fiifrg2679v9urontikoe','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 11:22:56','2026-06-29 12:52:56'),(171,20,'grlpmtjo1e8g072qsm5r0edhf7','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 11:28:45','2026-06-29 12:58:45'),(172,23,'3o4mde6ut0512mfq117v2cb660','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 11:48:39','2026-06-29 13:18:39'),(173,22,'fr6mjk0kfmv2prrafa9emepufn','41.210.141.140','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 11:52:26','2026-06-29 13:22:26'),(174,1,'kojimqhsvh4gho3gr1ods1cblc','197.239.12.138','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 15:43:26','2026-06-29 17:13:26'),(175,1,'6ume3k88h3l56k9qo62jc35t4p','197.239.12.138','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-29 16:08:09','2026-06-29 17:38:09'),(176,24,'rrhsiaq0hg83b0rhirsantnkp0','197.239.12.138','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-06-29 17:16:36','2026-06-29 18:46:36'),(177,24,'ln036egq1mrligvf8l5hu1iduu','129.205.27.165','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.5 Safari/605.1.15','2026-06-29 18:09:05','2026-06-29 19:39:05'),(178,1,'r830p6j3fshaoeer1nd5kk4m0h','102.86.0.21','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-30 05:05:33','2026-06-30 06:35:33'),(179,22,'aecvnjmdtu9jvq3557o7ulgd0j','102.86.0.21','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-06-30 05:32:30','2026-06-30 07:02:30'),(180,10,'pcdrkv5qmk6p231ioaiu6dlq4b','41.210.141.87','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-01 09:04:36','2026-07-01 10:34:36'),(181,3,'6qao8l2ifjpegnknr1jk4lnusr','41.210.141.87','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-07-01 09:05:24','2026-07-01 10:35:24'),(182,1,'cuukko3u26643ha6ao2scfpbm7','41.210.141.87','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-07-01 09:05:44','2026-07-01 10:35:44'),(183,12,'k5n0ma3ffeq5p023knp6hu2tjs','41.210.141.87','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-01 09:08:43','2026-07-01 10:38:43'),(184,24,'g1sufdkgtf6rgga093uahist6u','41.210.141.87','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-07-01 09:09:11','2026-07-01 10:39:11'),(185,24,'i4u4sn39th0nmdeqontce2o9vs','41.210.141.87','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-01 09:10:53','2026-07-01 10:40:53'),(186,23,'jd4r5p8h90kqtuad2rb77vbrtf','41.210.141.87','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-01 09:12:59','2026-07-01 10:42:59'),(187,1,'cenqlccbk5cdjr5jvro26qpdgo','102.86.2.161','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-07-01 10:13:45','2026-07-01 11:43:45'),(188,24,'lafo1rms9d99hja9e7orp9r1vo','102.86.2.161','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-07-01 12:18:18','2026-07-01 13:48:18'),(189,1,'bgbq2t8db6re4ml4732jg51n7v','102.86.2.161','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-07-01 12:33:22','2026-07-01 14:03:22'),(190,1,'nnjjtovah57bg9qdv8bt1q8qqj','102.86.2.161','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-07-01 16:10:35','2026-07-01 17:40:35'),(191,3,'j50fi2r1uvkgutf4nlphodugun','102.86.2.161','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-07-01 16:18:49','2026-07-01 17:48:49'),(192,24,'r7mdgr74423isuibgj7acdkmm2','129.205.27.165','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.5 Safari/605.1.15','2026-07-01 16:23:37','2026-07-01 17:53:37'),(193,24,'ookvbkm2u8tjljgrlametskg5a','102.86.2.161','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-07-01 16:24:11','2026-07-01 17:54:11'),(194,9,'me8cc4mpvfq28ot7a8hl96aj7d','102.86.2.161','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-07-01 16:41:33','2026-07-01 18:11:33'),(195,7,'g354o6sck6tl02hqsppu2vt1rl','102.86.2.161','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','2026-07-01 16:58:34','2026-07-01 18:28:34'),(196,23,'35dvr29kclpugfcbgnq4bjm38j','102.86.8.114','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-07-01 19:00:15','2026-07-01 20:30:15'),(197,24,'f8dn9qnf0p8hdupro30shtpj67','102.86.8.114','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-02 08:56:25','2026-07-02 10:26:25'),(198,24,'rj2t544e0q9reg53hno1v0j4is','102.86.8.114','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-07-02 09:00:27','2026-07-02 10:30:27'),(199,24,'rhkcussp1thiooasge7e5784p4','102.86.8.114','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-02 09:00:55','2026-07-02 10:30:55'),(200,24,'tgkadurhs8neaimf8bcgfene8n','102.85.205.216','Mozilla/5.0 (iPhone; CPU iPhone OS 18_7 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.5 Mobile/15E148 Safari/604.1','2026-07-02 09:05:01','2026-07-02 10:35:01'),(201,1,'vla8th6oitar3kcc6599h8ek7o','102.86.8.114','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-02 09:29:17','2026-07-02 10:59:17'),(202,24,'j1mtt1ui79l48v8uggvbmp0f9v','129.205.27.165','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.5 Safari/605.1.15','2026-07-02 15:32:07','2026-07-02 17:02:07'),(203,24,'o84gnkulua2oj7hkqaha5pfcen','129.205.27.165','Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/26.5 Safari/605.1.15','2026-07-02 15:36:15','2026-07-02 17:06:15'),(204,18,'4ntlt05jr6323nihe0u9p7jeqn','102.86.8.114','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-07-02 17:58:20','2026-07-02 19:28:20'),(205,1,'kgio301cojajhcl9udq89i8lqh','102.86.8.114','Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Mobile Safari/537.36','2026-07-02 17:59:12','2026-07-02 19:29:12'),(206,23,'8c6bq9bo9v6e6vfs0fsou8j0gn','102.85.6.218','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:152.0) Gecko/20100101 Firefox/152.0','2026-07-02 20:05:46','2026-07-02 21:35:46'),(207,25,'08e16bvipoic8f4u6ed45dnvtk','102.34.28.10','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-03 03:55:07','2026-07-03 05:25:07'),(208,25,'8tdqdkk1tt91l0abkl3ds2clcp','41.210.155.211','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-03 04:16:31','2026-07-03 05:46:31'),(209,25,'atlo3n9j8e23ti7rn7knpd9ghd','41.210.155.211','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36','2026-07-03 04:40:17','2026-07-03 06:10:17');
/*!40000 ALTER TABLE `staff_login_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_messages`
--

DROP TABLE IF EXISTS `staff_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_messages` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) unsigned NOT NULL,
  `sender_name` varchar(120) DEFAULT NULL,
  `recipient_id` int(11) unsigned NOT NULL,
  `recipient_name` varchar(120) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `parent_id` int(11) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sender` (`sender_id`),
  KEY `idx_recipient` (`recipient_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_parent` (`parent_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_messages`
--

LOCK TABLES `staff_messages` WRITE;
/*!40000 ALTER TABLE `staff_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `staff_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_notification_prefs`
--

DROP TABLE IF EXISTS `staff_notification_prefs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_notification_prefs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) unsigned NOT NULL,
  `notify_email` tinyint(1) NOT NULL DEFAULT 1,
  `notify_sms` tinyint(1) NOT NULL DEFAULT 0,
  `notify_in_app` tinyint(1) NOT NULL DEFAULT 1,
  `notify_tasks` tinyint(1) NOT NULL DEFAULT 1,
  `notify_approvals` tinyint(1) NOT NULL DEFAULT 1,
  `notify_announcements` tinyint(1) NOT NULL DEFAULT 1,
  `notify_messages` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_staff_notif` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_notification_prefs`
--

LOCK TABLES `staff_notification_prefs` WRITE;
/*!40000 ALTER TABLE `staff_notification_prefs` DISABLE KEYS */;
/*!40000 ALTER TABLE `staff_notification_prefs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_notification_reads`
--

DROP TABLE IF EXISTS `staff_notification_reads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_notification_reads` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `notification_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_read` (`notification_id`,`user_id`),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_notification_reads`
--

LOCK TABLES `staff_notification_reads` WRITE;
/*!40000 ALTER TABLE `staff_notification_reads` DISABLE KEYS */;
/*!40000 ALTER TABLE `staff_notification_reads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_notifications`
--

DROP TABLE IF EXISTS `staff_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_notifications` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'info',
  `icon` varchar(50) DEFAULT 'fa-bell',
  `url` varchar(500) DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `target_role` varchar(80) DEFAULT NULL,
  `target_user_id` int(10) unsigned DEFAULT NULL,
  `created_by` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_target_user` (`target_user_id`),
  KEY `idx_read` (`is_read`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_notifications`
--

LOCK TABLES `staff_notifications` WRITE;
/*!40000 ALTER TABLE `staff_notifications` DISABLE KEYS */;
INSERT INTO `staff_notifications` VALUES (1,'Website Submissions Active','All website contact forms, donations, volunteer applications, and student applications are now routed to director dashboards.','info','fa-globe',NULL,'normal',0,'CEO',NULL,NULL,'2026-07-01 07:08:13'),(2,'Website Submissions Active','All website contact forms, donations, volunteer applications, and student applications are now routed to director dashboards.','info','fa-globe',NULL,'normal',0,'Director Academics',NULL,NULL,'2026-07-01 07:08:13'),(3,'Website Submissions Active','All website contact forms, donations, volunteer applications, and student applications are now routed to director dashboards.','info','fa-globe',NULL,'normal',0,'Director Admissions & Requirements',NULL,NULL,'2026-07-01 07:08:13'),(4,'Website Submissions Active','All website contact forms, donations, volunteer applications, and student applications are now routed to director dashboards.','info','fa-globe',NULL,'normal',0,'Director Finance',NULL,NULL,'2026-07-01 07:08:13'),(5,'Website Submissions Active','All website contact forms, donations, volunteer applications, and student applications are now routed to director dashboards.','info','fa-globe',NULL,'normal',0,'Director General',NULL,NULL,'2026-07-01 07:08:13'),(6,'Website Submissions Active','All website contact forms, donations, volunteer applications, and student applications are now routed to director dashboards.','info','fa-globe',NULL,'normal',0,'Director ICT',NULL,NULL,'2026-07-01 07:08:13');
/*!40000 ALTER TABLE `staff_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_profiles`
--

DROP TABLE IF EXISTS `staff_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  KEY `idx_sp_department` (`department`),
  KEY `idx_sp_phone` (`phone`),
  CONSTRAINT `staff_profiles_ibfk_1` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_profiles`
--

LOCK TABLES `staff_profiles` WRITE;
/*!40000 ALTER TABLE `staff_profiles` DISABLE KEYS */;
INSERT INTO `staff_profiles` VALUES (3,1,NULL,'',NULL,NULL,NULL,'2026-06-24 00:08:59','2026-06-24 00:08:59'),(4,24,NULL,'',NULL,NULL,NULL,'2026-06-24 07:01:59','2026-06-24 07:01:59');
/*!40000 ALTER TABLE `staff_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_recruitment`
--

DROP TABLE IF EXISTS `staff_recruitment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_recruitment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `position_title` varchar(200) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `requirements` text DEFAULT NULL,
  `salary_range` varchar(100) DEFAULT NULL,
  `posted_date` date DEFAULT NULL,
  `closing_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_recruitment`
--

LOCK TABLES `staff_recruitment` WRITE;
/*!40000 ALTER TABLE `staff_recruitment` DISABLE KEYS */;
/*!40000 ALTER TABLE `staff_recruitment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_resignations`
--

DROP TABLE IF EXISTS `staff_resignations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
INSERT INTO `staff_roles` VALUES (1,'Director General',NULL,1,'dashboards/director-general.php',NULL,'2026-06-09 22:56:09','2026-06-09 22:56:09'),(2,'CEO',NULL,1,'dashboards/ceo.php',NULL,'2026-06-09 22:56:09','2026-06-09 22:56:09'),(3,'Director Academics',NULL,2,'dashboards/director-academics.php',NULL,'2026-06-09 22:56:09','2026-06-09 22:56:09'),(4,'Director Finance',NULL,2,'dashboards/director-finance.php',NULL,'2026-06-09 22:56:09','2026-06-09 22:56:09'),(5,'Director ICT',NULL,2,'dashboards/director-ict.php',NULL,'2026-06-09 22:56:09','2026-06-09 22:56:09'),(6,'School Principal',NULL,2,'dashboards/school-principal.php',NULL,'2026-06-09 22:56:09','2026-06-09 22:56:09'),(7,'Deputy Principal',NULL,3,'dashboards/deputy-principal.php',NULL,'2026-06-09 22:56:09','2026-06-09 22:56:09'),(8,'Academic Registrar',NULL,3,'dashboards/academic-registrar.php',NULL,'2026-06-09 22:56:09','2026-06-09 22:56:09'),(9,'HR Manager',NULL,3,'dashboards/hr-manager.php',NULL,'2026-06-09 22:56:09','2026-06-09 22:56:09'),(10,'School Secretary',NULL,4,'dashboards/school-secretary.php',NULL,'2026-06-09 22:56:09','2026-06-09 22:56:09'),(11,'School Librarian',NULL,4,'dashboards/school-librarian.php',NULL,'2026-06-09 22:56:09','2026-06-09 22:56:09'),(12,'Head Nursing',NULL,3,'dashboards/head-nursing.php',NULL,'2026-06-09 22:56:09','2026-06-09 22:56:09'),(13,'Head Midwifery',NULL,3,'dashboards/head-midwifery.php',NULL,'2026-06-09 22:56:09','2026-06-09 22:56:09'),(14,'Senior Lecturers',NULL,4,'dashboards/senior-lecturers.php',NULL,'2026-06-09 22:56:09','2026-06-09 22:56:09'),(15,'Lecturers',NULL,5,'dashboards/lecturers.php',NULL,'2026-06-09 22:56:09','2026-06-09 22:56:09'),(16,'Matrons',NULL,4,'dashboards/matrons.php',NULL,'2026-06-09 22:56:09','2026-06-09 22:56:09'),(17,'Wardens',NULL,5,'dashboards/wardens.php',NULL,'2026-06-09 22:56:09','2026-06-09 22:56:09'),(18,'Sickbay',NULL,5,'dashboards/sickbay.php',NULL,'2026-06-09 22:56:09','2026-06-09 22:56:09'),(19,'Drivers',NULL,6,'dashboards/drivers.php',NULL,'2026-06-09 22:56:09','2026-06-09 22:56:09'),(20,'Security',NULL,6,'dashboards/security.php',NULL,'2026-06-09 22:56:09','2026-06-09 22:56:09'),(21,'Storekeeper',NULL,5,'dashboards/storekeeper.php',NULL,'2026-06-09 22:56:09','2026-06-09 22:56:09'),(22,'Guild President',NULL,5,'dashboards/guild-president.php',NULL,'2026-06-09 22:56:09','2026-06-09 22:56:09'),(23,'Computer Lab Manager',NULL,3,'computer_lab.php',NULL,'2026-06-09 22:56:09','2026-06-09 22:56:09'),(24,'School Bursar',NULL,3,'dashboards/school-bursar.php',NULL,'2026-06-09 22:56:09','2026-06-26 05:57:33'),(25,'Store Keeper','Store inventory',0,'dashboards/storekeeper.php','{\"store\":true,\"inventory\":true}','2026-06-13 02:38:49','2026-06-13 02:38:49'),(26,'Director Admissions & Requirements','Admissions management',0,'dashboards/director-admissions.php','{\"admissions\":true,\"requirements\":true}','2026-06-13 02:38:49','2026-06-13 02:38:49'),(27,'Bursar','Bursar assistant',0,'dashboards/school-bursar.php','{\"financial\":true,\"fees\":true}','2026-06-13 02:38:49','2026-06-26 05:57:33');
/*!40000 ALTER TABLE `staff_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_salaries`
--

DROP TABLE IF EXISTS `staff_salaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
INSERT INTO `staff_salaries` VALUES (1,7,1500000.00,0.00,0.00,0.00,0.02,'2026-06-25',25,0.00,0.02,1499999.98,NULL,NULL,'Active','2026-06-25 00:35:20','2026-06-25 00:35:20');
/*!40000 ALTER TABLE `staff_salaries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff_training`
--

DROP TABLE IF EXISTS `staff_training`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff_training` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) DEFAULT NULL,
  `training_name` varchar(200) NOT NULL,
  `training_type` varchar(100) DEFAULT NULL,
  `provider` varchar(200) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Planned',
  `certificate_path` varchar(500) DEFAULT NULL,
  `cost` decimal(15,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_training_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_training`
--

LOCK TABLES `staff_training` WRITE;
/*!40000 ALTER TABLE `staff_training` DISABLE KEYS */;
/*!40000 ALTER TABLE `staff_training` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `store_categories`
--

DROP TABLE IF EXISTS `store_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `store_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT 'fas fa-box',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `store_categories`
--

LOCK TABLES `store_categories` WRITE;
/*!40000 ALTER TABLE `store_categories` DISABLE KEYS */;
INSERT INTO `store_categories` VALUES (1,'General Utilities','Stationery, electrical, office supplies, cleaning materials','fas fa-tools','active','2026-07-02 05:59:38'),(2,'Food Store','Food items, kitchen supplies, dining items','fas fa-utensils','active','2026-07-02 05:59:38'),(3,'Cleaning & Hygiene','Cleaning products, hygiene supplies, sanitation items','fas fa-broom','active','2026-07-02 05:59:38'),(4,'Medical Supplies','Medical equipment, PPE, pharmaceutical items','fas fa-medkit','active','2026-07-02 05:59:38'),(5,'Maintenance','Hardware, tools, spare parts, building materials','fas fa-wrench','active','2026-07-02 05:59:38'),(6,'ICT Equipment','Computers, peripherals, networking equipment','fas fa-laptop','active','2026-07-02 05:59:38'),(7,'Furniture','Desks, chairs, cabinets, fittings','fas fa-chair','active','2026-07-02 05:59:38'),(8,'Vehicles & Transport','Vehicle parts, fuel, transport equipment','fas fa-truck','active','2026-07-02 05:59:38');
/*!40000 ALTER TABLE `store_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `store_inventory`
--

DROP TABLE IF EXISTS `store_inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `store_inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `item_name` varchar(200) NOT NULL,
  `item_code` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `unit` varchar(50) DEFAULT 'piece',
  `quantity` int(11) DEFAULT 0,
  `reorder_level` int(11) DEFAULT 10,
  `unit_cost` decimal(15,2) DEFAULT 0.00,
  `location` varchar(200) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `batch_number` varchar(50) DEFAULT NULL,
  `supplier` varchar(200) DEFAULT NULL,
  `status` enum('active','inactive','discontinued') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_inventory_category` (`category_id`),
  CONSTRAINT `fk_si_category` FOREIGN KEY (`category_id`) REFERENCES `store_categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `store_inventory`
--

LOCK TABLES `store_inventory` WRITE;
/*!40000 ALTER TABLE `store_inventory` DISABLE KEYS */;
INSERT INTO `store_inventory` VALUES (1,3,'OMO','CU-001','Washing detergent/powder','packet',100,20,5000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(2,3,'JIK','CU-002','Bleach/disinfectant','bottle',80,15,3500.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(3,3,'VIM','CU-003','Scouring powder','packet',60,15,2500.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(4,3,'Examination Gloves','CU-004','Disposable examination gloves (box of 100)','box',50,10,25000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(5,3,'Surgical Gloves','CU-005','Sterile surgical gloves (pair)','pair',40,10,8000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(6,3,'Scrubbing Brushes','CU-006','Heavy duty scrubbing brush','piece',30,10,5000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(7,3,'Squeezers','CU-007','Mop wringer/squeezer','piece',15,5,15000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(8,3,'Mops','CU-008','Floor mop with handle','piece',25,8,12000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(9,3,'Soft Brooms','CU-009','Soft bristle broom','piece',30,10,8000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(10,3,'Compound Brooms','CU-010','Heavy duty compound broom','piece',25,8,10000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(11,3,'Ruled Reams','CU-011','A4 ruled paper ream','ream',50,15,15000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(12,3,'Toilet Brushes','CU-012','Toilet cleaning brush','piece',20,8,6000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(13,3,'High Dusters (Cobweb Brushes)','CU-013','Extended reach cobweb brush','piece',10,5,15000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(14,3,'Sink Pumps','CU-014','Manual sink/drainer pump','piece',8,3,25000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(15,3,'Liquid Soap','CU-015','Hand washing liquid soap','liter',40,10,8000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(16,3,'Sanitizer','CU-016','Hand sanitizer (500ml)','bottle',30,10,12000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(17,3,'Toilet Papers','CU-017','Toilet roll (pack of 4)','pack',50,15,10000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(18,3,'Face Masks','CU-018','Disposable face masks (box of 50)','box',40,10,15000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(19,3,'Heavy Duty Gloves','CU-019','Rubber cleaning gloves','pair',30,10,8000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(20,3,'Rake','CU-020','Garden rake','piece',8,3,20000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(21,3,'Photocopying Reams','CU-021','A4 plain paper ream','ream',100,20,18000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(22,3,'Blackboard Dusters','CU-022','Chalkboard duster/eraser','piece',15,5,5000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(23,3,'Chalk','CU-023','Whiteboard/chalk chalk (box)','box',30,10,3000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(24,3,'Markers','CU-024','Whiteboard markers (pack of 4)','pack',25,8,8000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(25,3,'Highlighter Markers','CU-025','Highlighter pen set','set',20,5,6000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(26,3,'Pens','CU-026','Ballpoint pen (box of 50)','box',30,10,12000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(27,3,'Rubbers','CU-027','Eraser/rubber','piece',40,10,2000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(28,3,'Office Glue','CU-028','Liquid adhesive glue','bottle',25,8,3000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(29,3,'Sticker Notes','CU-029','Sticky notes (pack of 12)','pack',20,5,5000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(30,3,'Stick Glue','CU-030','Glue stick','piece',30,10,2000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(31,3,'Insulation Tape','CU-031','Electrical insulation tape','roll',20,5,3000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(32,3,'Binding Tape','CU-032','Document binding tape','roll',15,5,4000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(33,3,'Masking Tape','CU-033','Masking tape roll','roll',15,5,4000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(34,3,'Binding Rings','CU-034','Document binding rings (pack)','pack',20,5,5000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(35,3,'Ring Binder Files','CU-035','Ring binder folder','piece',30,10,8000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(36,3,'Box Files','CU-036','Document box file','piece',25,8,10000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(37,3,'Counter Books','CU-037','Counter exercise book','piece',50,15,3000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(38,3,'Envelops A3','CU-038','A3 envelope','piece',100,20,1500.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(39,3,'Envelops A4','CU-039','A4 envelope','piece',200,30,1000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(40,3,'Envelops A5','CU-040','A5 envelope','piece',150,25,800.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(41,3,'Color Papers','CU-041','Colored A4 paper (pack)','pack',20,5,12000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(42,3,'Layer File Trays','CU-042','Document tray/organizer','piece',15,5,15000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(43,3,'Laminating Paper','CU-043','Laminating pouch A4 (pack of 100)','pack',10,3,25000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(44,3,'Staple Wires','CU-044','Stapler refill wires (pack)','pack',30,10,3000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(45,3,'Paper Clips','CU-045','Paper clips (box)','box',25,8,2000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(46,3,'PVC Covers','CU-046','Document PVC cover','piece',100,20,500.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(47,3,'Atlas Files','CU-047','Atlas file folder','piece',20,5,12000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(48,3,'Carbon Papers','CU-048','Carbon paper (pack of 100)','pack',10,3,8000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(49,3,'Receipt Books','CU-049','Duplicate receipt book','book',20,5,15000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(50,3,'Payment Voucher Books','CU-050','Payment voucher book','book',10,3,20000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(51,3,'Requirements Clearance Books','CU-051','Clearance/requirements record book','book',10,3,18000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(52,3,'Dormeciliary Kit Bags','CU-052','Dormitory kit bag','piece',15,5,25000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(53,1,'Switches Double Gang','EL-001','Double gang electrical switch','piece',20,5,15000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(54,1,'Switches Single Gang','EL-002','Single gang electrical switch','piece',25,8,10000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(55,1,'Sockets Single','EL-003','Single electrical socket','piece',20,5,12000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(56,1,'Sockets Double','EL-004','Double electrical socket','piece',15,5,18000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(57,1,'Bulbs','EL-005','LED light bulb','piece',50,15,5000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(58,1,'Lamp Holders','EL-006','Bulb lamp holder','piece',20,5,8000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(59,1,'Mounding Boxes','EL-007','Electrical mounding box','piece',15,5,6000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(60,2,'Posho','FD-001','Maize flour/posho','kg',500,100,3500.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(61,2,'Rice','FD-002','White rice','kg',200,50,8000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(62,2,'Beans','FD-003','Dried beans','kg',150,40,5000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(63,2,'Salt','FD-004','Table salt','kg',50,15,2000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(64,2,'Cooking Oil','FD-005','Vegetable cooking oil','liter',100,25,8000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(65,2,'Sugar','FD-006','White sugar','kg',100,25,4000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(66,2,'Plates','FD-007','Dining plate','piece',100,20,8000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06'),(67,2,'Charcoal','FD-008','Cooking charcoal','bag',30,10,25000.00,NULL,NULL,NULL,NULL,'active','2026-07-02 06:19:06');
/*!40000 ALTER TABLE `store_inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `store_inventory_transactions`
--

DROP TABLE IF EXISTS `store_inventory_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `store_inventory_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_id` int(11) NOT NULL,
  `transaction_type` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `quantity_before` int(11) DEFAULT NULL,
  `quantity_after` int(11) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_trans_item` (`item_id`),
  CONSTRAINT `fk_sit_item` FOREIGN KEY (`item_id`) REFERENCES `store_inventory` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `store_inventory_transactions`
--

LOCK TABLES `store_inventory_transactions` WRITE;
/*!40000 ALTER TABLE `store_inventory_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `store_inventory_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `store_order_items`
--

DROP TABLE IF EXISTS `store_order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `store_order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity_ordered` int(11) NOT NULL DEFAULT 1,
  `quantity_received` int(11) DEFAULT 0,
  `unit_price` decimal(15,2) DEFAULT 0.00,
  `status` enum('pending','received','cancelled') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_oi_order` (`order_id`),
  KEY `fk_soi_item` (`item_id`),
  CONSTRAINT `fk_soi_item` FOREIGN KEY (`item_id`) REFERENCES `store_inventory` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_soi_order` FOREIGN KEY (`order_id`) REFERENCES `store_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `store_order_items`
--

LOCK TABLES `store_order_items` WRITE;
/*!40000 ALTER TABLE `store_order_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `store_order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `store_orders`
--

DROP TABLE IF EXISTS `store_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `store_request_items`
--

DROP TABLE IF EXISTS `store_request_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `store_request_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(10) unsigned NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity_requested` int(11) NOT NULL DEFAULT 1,
  `quantity_fulfilled` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `status` enum('pending','fulfilled','partial') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ri_request` (`request_id`),
  KEY `idx_request_id` (`request_id`),
  CONSTRAINT `fk_sri_request` FOREIGN KEY (`request_id`) REFERENCES `store_requests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `store_request_items`
--

LOCK TABLES `store_request_items` WRITE;
/*!40000 ALTER TABLE `store_request_items` DISABLE KEYS */;
INSERT INTO `store_request_items` VALUES (18,1,1,10,10,'OMO packets for cleaning',NULL,'fulfilled','2026-07-02 06:19:06'),(19,1,2,5,5,'JIK bleach bottles',NULL,'fulfilled','2026-07-02 06:19:06'),(20,1,8,8,8,'Mops for dormitory',NULL,'fulfilled','2026-07-02 06:19:06'),(21,1,12,10,10,'Toilet brushes',NULL,'fulfilled','2026-07-02 06:19:06'),(22,2,5,20,20,'Surgical gloves pairs',NULL,'fulfilled','2026-07-02 06:19:06'),(23,2,4,10,10,'Examination gloves boxes',NULL,'fulfilled','2026-07-02 06:19:06'),(24,2,15,5,5,'Liquid soap liters',NULL,'fulfilled','2026-07-02 06:19:06'),(25,3,17,20,20,'Toilet paper packs',NULL,'fulfilled','2026-07-02 06:19:06'),(26,3,16,10,10,'Sanitizer bottles',NULL,'fulfilled','2026-07-02 06:19:06'),(27,3,18,5,5,'Face mask boxes',NULL,'fulfilled','2026-07-02 06:19:06'),(28,4,33,100,100,'Posho kg',NULL,'fulfilled','2026-07-02 06:19:06'),(29,4,34,50,50,'Rice kg',NULL,'fulfilled','2026-07-02 06:19:06'),(30,4,35,30,30,'Beans kg',NULL,'fulfilled','2026-07-02 06:19:06'),(31,4,36,20,20,'Cooking oil liters',NULL,'fulfilled','2026-07-02 06:19:06'),(32,5,39,20,0,'Bulbs for hostel',NULL,'pending','2026-07-02 06:19:06'),(33,5,40,10,0,'Double gang switches',NULL,'pending','2026-07-02 06:19:06'),(34,5,42,5,0,'Double sockets',NULL,'pending','2026-07-02 06:19:06');
/*!40000 ALTER TABLE `store_request_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `store_requests`
--

DROP TABLE IF EXISTS `store_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `store_requests` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `request_number` varchar(60) NOT NULL,
  `requested_by` int(10) unsigned DEFAULT NULL,
  `requester_name` varchar(120) DEFAULT NULL,
  `requester_role` varchar(100) DEFAULT NULL,
  `department` varchar(80) DEFAULT NULL,
  `items` text DEFAULT NULL,
  `urgency` varchar(20) NOT NULL DEFAULT 'medium',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
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
INSERT INTO `store_requests` VALUES (1,'REQ-2026-001',22,'Matron Grace','matron','Dormitory','OMO, JIK, Mops, Toilet Brushes','medium','medium','pending',NULL,NULL,NULL,'Monthly cleaning supplies for female dormitory','2026-07-02 06:19:06',NULL),(2,'REQ-2026-002',23,'Warden James','warden','Hostel A','Surgical Gloves, Examination Gloves, Liquid Soap','high','medium','pending',NULL,NULL,NULL,'Urgent hygiene supplies for hostel A sickbay','2026-07-02 06:19:06',NULL),(3,'REQ-2026-003',22,'Matron Grace','matron','Dormitory','Toilet Papers, Sanitizer, Face Masks','medium','medium','approved',NULL,NULL,NULL,'Weekly hygiene supplies','2026-06-30 06:19:06',NULL),(4,'REQ-2026-004',20,'Storekeeper Peter','storekeeper','Store','Posho, Rice, Beans, Cooking Oil','high','medium','fulfilled',NULL,NULL,NULL,'Food store restocking','2026-06-29 06:19:06',NULL),(5,'REQ-2026-005',23,'Warden James','warden','Hostel B','Bulbs, Switches, Sockets','low','medium','rejected',NULL,NULL,NULL,'Electrical maintenance items','2026-06-27 06:19:06',NULL);
/*!40000 ALTER TABLE `store_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_academic_profiles`
--

DROP TABLE IF EXISTS `student_academic_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_academic_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `program` varchar(200) DEFAULT NULL,
  `level` varchar(20) DEFAULT NULL,
  `gpa` decimal(4,2) DEFAULT NULL,
  `total_credits` int(11) DEFAULT 0,
  `academic_standing` varchar(50) DEFAULT 'Good',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sap_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_academic_profiles`
--

LOCK TABLES `student_academic_profiles` WRITE;
/*!40000 ALTER TABLE `student_academic_profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_academic_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_activities`
--

DROP TABLE IF EXISTS `student_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `activity_name` varchar(200) DEFAULT NULL,
  `activity_type` varchar(100) DEFAULT NULL,
  `activity_date` date DEFAULT NULL,
  `expected_participants` int(11) DEFAULT 0,
  `location` varchar(200) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Planned',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_sa_created` (`created_by`),
  CONSTRAINT `fk_sa_created` FOREIGN KEY (`created_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_activities`
--

LOCK TABLES `student_activities` WRITE;
/*!40000 ALTER TABLE `student_activities` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_admission_tracking`
--

DROP TABLE IF EXISTS `student_admission_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_admission_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_number` varchar(50) NOT NULL,
  `full_name` varchar(300) NOT NULL,
  `program` varchar(150) DEFAULT '',
  `intake` varchar(100) DEFAULT '',
  `admission_date` date DEFAULT NULL,
  `admission_status` varchar(50) DEFAULT 'Pending',
  `requirements_completed` int(11) DEFAULT 0,
  `requirements_total` int(11) DEFAULT 20,
  `fee_status` varchar(30) DEFAULT 'Unpaid',
  `total_fees` decimal(12,2) DEFAULT 0.00,
  `amount_paid` decimal(12,2) DEFAULT 0.00,
  `documents_uploaded` int(11) DEFAULT 0,
  `assigned_to` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admission_tracking` (`student_number`),
  KEY `idx_admission_status` (`admission_status`),
  KEY `idx_fee_status` (`fee_status`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_admission_tracking`
--

LOCK TABLES `student_admission_tracking` WRITE;
/*!40000 ALTER TABLE `student_admission_tracking` DISABLE KEYS */;
INSERT INTO `student_admission_tracking` VALUES (1,'APP-202685764','bamuwamye Derrick','Certificate in Nursing','May','2026-06-28','Approved',7,20,'Partial',1500000.00,750000.00,0,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(2,'APP-2024-002','David Ssali','Certificate in Midwifery','January','2024-01-15','Registered',20,20,'Paid',1200000.00,1500000.00,0,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(3,'APP-2024-003','Mary Nalwoga','Certificate in Nursing','January','2024-01-15','Registered',20,20,'Paid',1500000.00,1500000.00,0,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(4,'APP-2024-004','James Okello','Diploma in Nursing','January','2024-01-15','Registered',20,20,'Paid',1500000.00,1500000.00,0,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(5,'APP-2024-005','Sarah Kyomugisha','Certificate in Nursing','January','2024-01-15','Registered',20,20,'Paid',1500000.00,1500000.00,0,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(6,'APP-2024-006','Aisha Nansubuga','Certificate in Midwifery','May',NULL,'Approved',8,20,'Partial',1200000.00,750000.00,0,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(7,'APP-2024-007','Robert Ochieng','Certificate in Nursing','May',NULL,'Approved',8,20,'Partial',1500000.00,750000.00,0,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(8,'APP-2024-008','Betty Namukasa','Diploma in Nursing','May',NULL,'Under Review',0,20,'Unpaid',1500000.00,0.00,0,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(9,'APP-2024-009','Moses Byaruhanga','Certificate in Nursing','May',NULL,'Under Review',0,20,'Unpaid',1500000.00,0.00,0,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(10,'APP-2024-010','Esther Auma','Certificate in Midwifery','May',NULL,'New Applicant',0,20,'Unpaid',1200000.00,0.00,0,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(11,'APP-2024-011','Samuel Mugisha','Certificate in Nursing','August',NULL,'New Applicant',0,20,'Unpaid',1500000.00,0.00,0,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(12,'APP-2024-012','Priscilla Ojok','Diploma in Nursing','August',NULL,'New Applicant',0,20,'Unpaid',1500000.00,0.00,0,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(13,'APP-2024-013','Isaac Tumwine','Certificate in Nursing','August',NULL,'Rejected',0,20,'Unpaid',1500000.00,0.00,0,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(14,'APP-2024-014','Hannah Apio','Certificate in Midwifery','January','2024-01-15','Registered',20,20,'Paid',1200000.00,1500000.00,0,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(15,'APP-2024-015','Daniel Kizza','Certificate in Nursing','May',NULL,'Approved',8,20,'Partial',1500000.00,750000.00,0,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(16,'APP-2024-016','Joyce Atim','Diploma in Nursing','May',NULL,'Under Review',0,20,'Unpaid',1500000.00,0.00,0,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(17,'APP-2024-017','Patrick Opio','Certificate in Nursing','August',NULL,'New Applicant',0,20,'Unpaid',1500000.00,0.00,0,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(18,'APP-2024-018','Catherine Akello','Certificate in Midwifery','August',NULL,'New Applicant',0,20,'Unpaid',1200000.00,0.00,0,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(19,'APP-2024-019','Fred Wasswa','Certificate in Nursing','January','2024-01-15','Registered',20,20,'Paid',1500000.00,1500000.00,0,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20'),(20,'APP-2024-020','Gladys Nabirye','Diploma in Nursing','May',NULL,'Approved',8,20,'Partial',1500000.00,750000.00,0,NULL,NULL,'2026-07-01 19:55:20','2026-07-01 19:55:20');
/*!40000 ALTER TABLE `student_admission_tracking` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_admissions`
--

DROP TABLE IF EXISTS `student_admissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `student_audit_log`
--

DROP TABLE IF EXISTS `student_audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_audit_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(200) NOT NULL,
  `module` varchar(100) NOT NULL,
  `record_id` int(11) DEFAULT 0,
  `student_number` varchar(50) DEFAULT '',
  `description` text DEFAULT NULL,
  `ip_address` varchar(50) DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_module` (`module`),
  KEY `idx_audit_student` (`student_number`)
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_audit_log`
--

LOCK TABLES `student_audit_log` WRITE;
/*!40000 ALTER TABLE `student_audit_log` DISABLE KEYS */;
INSERT INTO `student_audit_log` VALUES (1,1,'Login','Authentication',0,'','Admin user logged in successfully','192.168.1.100','2024-01-15 08:00:00'),(2,1,'View Applicant List','Admissions',0,'','Viewed all applicants','192.168.1.100','2024-01-15 08:05:00'),(3,1,'Verify Document','Admissions',1,'APP-2024-001','Verified O-Level Certificate for Grace Nakato','192.168.1.100','2024-01-15 09:30:00'),(4,1,'Verify Document','Admissions',2,'APP-2024-001','Verified Birth Certificate for Grace Nakato','192.168.1.100','2024-01-15 09:45:00'),(5,1,'Approve Applicant','Admissions',6,'APP-2024-006','Approved applicant Aisha Nansubuga','192.168.1.100','2024-05-10 08:00:00'),(6,2,'Review Application','Admissions',8,'APP-2024-008','Started review for Betty Namukasa','192.168.1.101','2024-05-14 09:00:00'),(7,2,'Reject Application','Admissions',13,'APP-2024-013','Rejected Isaac Tumwine - unverifiable documents','192.168.1.101','2024-08-20 14:30:00'),(8,1,'Update Admission Requirements','Admissions',0,'','Updated admission requirements list - removed supplies, added proper docs','192.168.1.100','2024-01-14 14:00:00'),(9,2,'Add Comment','Student Profile',0,'APP-2024-008','Added follow-up comment for medical report','192.168.1.101','2024-05-16 07:30:00'),(10,1,'Export Report','Reports',0,'','Exported applicant status report for January intake','192.168.1.100','2024-02-01 11:00:00'),(11,1,'Login','Authentication',0,'','Admin user logged in successfully','192.168.1.100','2024-05-01 07:00:00'),(12,1,'Approve Applicant','Admissions',7,'APP-2024-007','Approved applicant Robert Ochieng','192.168.1.100','2024-05-08 13:00:00'),(13,3,'Review Application','Admissions',9,'APP-2024-009','Started review for Moses Byaruhanga','192.168.1.102','2024-05-15 08:00:00'),(14,3,'Add Comment','Student Profile',0,'APP-2024-008','Requested clarification on medical report','192.168.1.102','2024-05-15 10:45:00'),(15,1,'Login','Authentication',0,'','Admin user logged in successfully','192.168.1.100','2024-08-01 07:00:00'),(16,2,'View Applicant List','Admissions',0,'','Viewed August intake applicants','192.168.1.101','2024-08-01 07:10:00'),(17,1,'Update Fee Structure','Finance',0,'','Updated tuition fees for 2024 academic year','192.168.1.100','2024-01-10 10:00:00'),(18,1,'Generate Invoice','Finance',1,'APP-2024-001','Generated registration invoice for Grace Nakato','192.168.1.100','2024-01-15 12:00:00'),(19,2,'Approve Applicant','Admissions',15,'APP-2024-015','Approved applicant Daniel Kizza','192.168.1.101','2024-05-18 09:00:00'),(20,2,'Approve Applicant','Admissions',20,'APP-2024-020','Approved applicant Gladys Nabirye','192.168.1.101','2024-05-20 12:00:00'),(21,1,'Login','Authentication',0,'','Admin user logged in successfully','192.168.1.100','2024-01-15 08:00:00'),(22,1,'View Applicant List','Admissions',0,'','Viewed all applicants','192.168.1.100','2024-01-15 08:05:00'),(23,1,'Verify Document','Admissions',1,'APP-2024-001','Verified O-Level Certificate for Grace Nakato','192.168.1.100','2024-01-15 09:30:00'),(24,1,'Verify Document','Admissions',2,'APP-2024-001','Verified Birth Certificate for Grace Nakato','192.168.1.100','2024-01-15 09:45:00'),(25,1,'Approve Applicant','Admissions',6,'APP-2024-006','Approved applicant Aisha Nansubuga','192.168.1.100','2024-05-10 08:00:00'),(26,2,'Review Application','Admissions',8,'APP-2024-008','Started review for Betty Namukasa','192.168.1.101','2024-05-14 09:00:00'),(27,2,'Reject Application','Admissions',13,'APP-2024-013','Rejected Isaac Tumwine - unverifiable documents','192.168.1.101','2024-08-20 14:30:00'),(28,1,'Update Admission Requirements','Admissions',0,'','Updated admission requirements list - removed supplies, added proper docs','192.168.1.100','2024-01-14 14:00:00'),(29,2,'Add Comment','Student Profile',0,'APP-2024-008','Added follow-up comment for medical report','192.168.1.101','2024-05-16 07:30:00'),(30,1,'Export Report','Reports',0,'','Exported applicant status report for January intake','192.168.1.100','2024-02-01 11:00:00'),(31,1,'Login','Authentication',0,'','Admin user logged in successfully','192.168.1.100','2024-05-01 07:00:00'),(32,1,'Approve Applicant','Admissions',7,'APP-2024-007','Approved applicant Robert Ochieng','192.168.1.100','2024-05-08 13:00:00'),(33,3,'Review Application','Admissions',9,'APP-2024-009','Started review for Moses Byaruhanga','192.168.1.102','2024-05-15 08:00:00'),(34,3,'Add Comment','Student Profile',0,'APP-2024-008','Requested clarification on medical report','192.168.1.102','2024-05-15 10:45:00'),(35,1,'Login','Authentication',0,'','Admin user logged in successfully','192.168.1.100','2024-08-01 07:00:00'),(36,2,'View Applicant List','Admissions',0,'','Viewed August intake applicants','192.168.1.101','2024-08-01 07:10:00'),(37,1,'Update Fee Structure','Finance',0,'','Updated tuition fees for 2024 academic year','192.168.1.100','2024-01-10 10:00:00'),(38,1,'Generate Invoice','Finance',1,'APP-2024-001','Generated registration invoice for Grace Nakato','192.168.1.100','2024-01-15 12:00:00'),(39,2,'Approve Applicant','Admissions',15,'APP-2024-015','Approved applicant Daniel Kizza','192.168.1.101','2024-05-18 09:00:00'),(40,2,'Approve Applicant','Admissions',20,'APP-2024-020','Approved applicant Gladys Nabirye','192.168.1.101','2024-05-20 12:00:00'),(41,1,'Login','Authentication',0,'','Admin user logged in successfully','192.168.1.100','2024-01-15 08:00:00'),(42,1,'View Applicant List','Admissions',0,'','Viewed all applicants','192.168.1.100','2024-01-15 08:05:00'),(43,1,'Verify Document','Admissions',1,'APP-2024-001','Verified O-Level Certificate for Grace Nakato','192.168.1.100','2024-01-15 09:30:00'),(44,1,'Verify Document','Admissions',2,'APP-2024-001','Verified Birth Certificate for Grace Nakato','192.168.1.100','2024-01-15 09:45:00'),(45,1,'Approve Applicant','Admissions',6,'APP-2024-006','Approved applicant Aisha Nansubuga','192.168.1.100','2024-05-10 08:00:00'),(46,2,'Review Application','Admissions',8,'APP-2024-008','Started review for Betty Namukasa','192.168.1.101','2024-05-14 09:00:00'),(47,2,'Reject Application','Admissions',13,'APP-2024-013','Rejected Isaac Tumwine - unverifiable documents','192.168.1.101','2024-08-20 14:30:00'),(48,1,'Update Admission Requirements','Admissions',0,'','Updated admission requirements list','192.168.1.100','2024-01-14 14:00:00'),(49,2,'Add Comment','Student Profile',0,'APP-2024-008','Added follow-up comment for medical report','192.168.1.101','2024-05-16 07:30:00'),(50,1,'Export Report','Reports',0,'','Exported applicant status report for January intake','192.168.1.100','2024-02-01 11:00:00'),(51,1,'Login','Authentication',0,'','Admin user logged in successfully','192.168.1.100','2024-05-01 07:00:00'),(52,1,'Approve Applicant','Admissions',7,'APP-2024-007','Approved applicant Robert Ochieng','192.168.1.100','2024-05-08 13:00:00'),(53,3,'Review Application','Admissions',9,'APP-2024-009','Started review for Moses Byaruhanga','192.168.1.102','2024-05-15 08:00:00'),(54,3,'Add Comment','Student Profile',0,'APP-2024-008','Requested clarification on medical report','192.168.1.102','2024-05-15 10:45:00'),(55,1,'Login','Authentication',0,'','Admin user logged in successfully','192.168.1.100','2024-08-01 07:00:00'),(56,2,'View Applicant List','Admissions',0,'','Viewed August intake applicants','192.168.1.101','2024-08-01 07:10:00'),(57,1,'Update Fee Structure','Finance',0,'','Updated tuition fees for 2024 academic year','192.168.1.100','2024-01-10 10:00:00'),(58,1,'Generate Invoice','Finance',1,'APP-2024-001','Generated registration invoice for Grace Nakato','192.168.1.100','2024-01-15 12:00:00'),(59,2,'Approve Applicant','Admissions',15,'APP-2024-015','Approved applicant Daniel Kizza','192.168.1.101','2024-05-18 09:00:00'),(60,2,'Approve Applicant','Admissions',20,'APP-2024-020','Approved applicant Gladys Nabirye','192.168.1.101','2024-05-20 12:00:00'),(61,1,'Login','Authentication',0,'','Admin user logged in successfully','192.168.1.100','2024-01-15 08:00:00'),(62,1,'View Applicant List','Admissions',0,'','Viewed all applicants','192.168.1.100','2024-01-15 08:05:00'),(63,1,'Verify Document','Admissions',1,'APP-2024-001','Verified O-Level Certificate for Grace Nakato','192.168.1.100','2024-01-15 09:30:00'),(64,1,'Verify Document','Admissions',2,'APP-2024-001','Verified Birth Certificate for Grace Nakato','192.168.1.100','2024-01-15 09:45:00'),(65,1,'Approve Applicant','Admissions',6,'APP-2024-006','Approved applicant Aisha Nansubuga','192.168.1.100','2024-05-10 08:00:00'),(66,2,'Review Application','Admissions',8,'APP-2024-008','Started review for Betty Namukasa','192.168.1.101','2024-05-14 09:00:00'),(67,2,'Reject Application','Admissions',13,'APP-2024-013','Rejected Isaac Tumwine - unverifiable documents','192.168.1.101','2024-08-20 14:30:00'),(68,1,'Update Admission Requirements','Admissions',0,'','Updated admission requirements list','192.168.1.100','2024-01-14 14:00:00'),(69,2,'Add Comment','Student Profile',0,'APP-2024-008','Added follow-up comment for medical report','192.168.1.101','2024-05-16 07:30:00'),(70,1,'Export Report','Reports',0,'','Exported applicant status report for January intake','192.168.1.100','2024-02-01 11:00:00'),(71,1,'Login','Authentication',0,'','Admin user logged in successfully','192.168.1.100','2024-05-01 07:00:00'),(72,1,'Approve Applicant','Admissions',7,'APP-2024-007','Approved applicant Robert Ochieng','192.168.1.100','2024-05-08 13:00:00'),(73,3,'Review Application','Admissions',9,'APP-2024-009','Started review for Moses Byaruhanga','192.168.1.102','2024-05-15 08:00:00'),(74,3,'Add Comment','Student Profile',0,'APP-2024-008','Requested clarification on medical report','192.168.1.102','2024-05-15 10:45:00'),(75,1,'Login','Authentication',0,'','Admin user logged in successfully','192.168.1.100','2024-08-01 07:00:00'),(76,2,'View Applicant List','Admissions',0,'','Viewed August intake applicants','192.168.1.101','2024-08-01 07:10:00'),(77,1,'Update Fee Structure','Finance',0,'','Updated tuition fees for 2024 academic year','192.168.1.100','2024-01-10 10:00:00'),(78,1,'Generate Invoice','Finance',1,'APP-2024-001','Generated registration invoice for Grace Nakato','192.168.1.100','2024-01-15 12:00:00'),(79,2,'Approve Applicant','Admissions',15,'APP-2024-015','Approved applicant Daniel Kizza','192.168.1.101','2024-05-18 09:00:00'),(80,2,'Approve Applicant','Admissions',20,'APP-2024-020','Approved applicant Gladys Nabirye','192.168.1.101','2024-05-20 12:00:00');
/*!40000 ALTER TABLE `student_audit_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_competencies`
--

DROP TABLE IF EXISTS `student_competencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_competencies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `placement_id` int(11) DEFAULT NULL,
  `skill_name` varchar(200) NOT NULL,
  `skill_category` varchar(100) DEFAULT NULL,
  `competency_level` enum('Beginner','Intermediate','Advanced','Proficient','Not Assessed') DEFAULT 'Not Assessed',
  `score` decimal(5,2) DEFAULT NULL,
  `max_score` decimal(5,2) DEFAULT 100.00,
  `assessed_by` int(11) DEFAULT NULL,
  `assessment_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_student` (`student_id`),
  KEY `idx_placement` (`placement_id`),
  KEY `idx_skill` (`skill_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_competencies`
--

LOCK TABLES `student_competencies` WRITE;
/*!40000 ALTER TABLE `student_competencies` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_competencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_counseling_sessions`
--

DROP TABLE IF EXISTS `student_counseling_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_counseling_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `session_date` date NOT NULL,
  `session_time` time DEFAULT NULL,
  `session_type` varchar(100) DEFAULT NULL,
  `issues_discussed` text DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `counselor_id` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Scheduled',
  `follow_up_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_counseling_sessions`
--

LOCK TABLES `student_counseling_sessions` WRITE;
/*!40000 ALTER TABLE `student_counseling_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_counseling_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_discipline`
--

DROP TABLE IF EXISTS `student_discipline`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_discipline` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `incident_type` varchar(100) DEFAULT NULL,
  `offense` varchar(200) DEFAULT NULL,
  `incident_date` date DEFAULT NULL,
  `action_taken` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `reported_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sd_status_date` (`status`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_discipline`
--

LOCK TABLES `student_discipline` WRITE;
/*!40000 ALTER TABLE `student_discipline` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_discipline` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_discipline_records`
--

DROP TABLE IF EXISTS `student_discipline_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `student_document_uploads`
--

DROP TABLE IF EXISTS `student_document_uploads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_document_uploads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `document_type` varchar(100) NOT NULL,
  `document_name` varchar(300) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT 0,
  `mime_type` varchar(100) DEFAULT '',
  `uploaded_by` int(11) NOT NULL,
  `uploaded_by_role` varchar(100) DEFAULT '',
  `verification_status` enum('Pending','Verified','Rejected') DEFAULT 'Pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_doc_student` (`student_id`),
  KEY `idx_doc_type` (`document_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_document_uploads`
--

LOCK TABLES `student_document_uploads` WRITE;
/*!40000 ALTER TABLE `student_document_uploads` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_document_uploads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_documents`
--

DROP TABLE IF EXISTS `student_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `student_emergency_contacts`
--

DROP TABLE IF EXISTS `student_emergency_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_emergency_contacts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `contact_name` varchar(200) NOT NULL,
  `relationship` varchar(100) DEFAULT NULL,
  `phone_number` varchar(50) DEFAULT NULL,
  `email` varchar(200) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sec_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_emergency_contacts`
--

LOCK TABLES `student_emergency_contacts` WRITE;
/*!40000 ALTER TABLE `student_emergency_contacts` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_emergency_contacts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_fee_accounts`
--

DROP TABLE IF EXISTS `student_fee_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  KEY `invoice_number` (`invoice_number`),
  KEY `idx_sfa_status_student` (`status`,`student_id`)
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
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `student_fee_tracking`
--

DROP TABLE IF EXISTS `student_fee_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_fee_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `fee_type` varchar(100) NOT NULL,
  `amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `amount_paid` decimal(12,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(12,2) NOT NULL DEFAULT 0.00,
  `academic_year` varchar(20) NOT NULL,
  `semester` varchar(20) DEFAULT '',
  `due_date` date DEFAULT NULL,
  `status` varchar(30) DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_student_fee` (`student_id`),
  KEY `idx_fee_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_fee_tracking`
--

LOCK TABLES `student_fee_tracking` WRITE;
/*!40000 ALTER TABLE `student_fee_tracking` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_fee_tracking` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_fees`
--

DROP TABLE IF EXISTS `student_fees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `student_finance`
--

DROP TABLE IF EXISTS `student_finance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_finance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `transaction_type` varchar(50) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT 0.00,
  `balance` decimal(15,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `reference_number` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sf_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_finance`
--

LOCK TABLES `student_finance` WRITE;
/*!40000 ALTER TABLE `student_finance` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_finance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_guardian`
--

DROP TABLE IF EXISTS `student_guardian`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_guardian` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `gname` varchar(200) DEFAULT NULL,
  `gphone` varchar(50) DEFAULT NULL,
  `gemail` varchar(200) DEFAULT NULL,
  `relationship` varchar(100) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sg_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_guardian`
--

LOCK TABLES `student_guardian` WRITE;
/*!40000 ALTER TABLE `student_guardian` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_guardian` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_health_incidents`
--

DROP TABLE IF EXISTS `student_health_incidents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_health_incidents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `incident_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `severity` enum('low','medium','high','critical') NOT NULL DEFAULT 'low',
  `action_taken` text DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_severity` (`severity`),
  KEY `idx_incident_type` (`incident_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_health_incidents`
--

LOCK TABLES `student_health_incidents` WRITE;
/*!40000 ALTER TABLE `student_health_incidents` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_health_incidents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_health_records`
--

DROP TABLE IF EXISTS `student_health_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_health_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL,
  `record_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `recorded_by` int(11) DEFAULT NULL,
  `record_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_shr_student` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_health_records`
--

LOCK TABLES `student_health_records` WRITE;
/*!40000 ALTER TABLE `student_health_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_health_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_hostel_allocations`
--

DROP TABLE IF EXISTS `student_hostel_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
  KEY `idx_invoices_student` (`student_id`),
  KEY `idx_sinv_student_date` (`student_id`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_invoices`
--

LOCK TABLES `student_invoices` WRITE;
/*!40000 ALTER TABLE `student_invoices` DISABLE KEYS */;
INSERT INTO `student_invoices` VALUES (1,1,'INV-2024-001',1500000.00,1000000.00,500000.00,'partial','2024-12-31','2026-06-19 23:59:17'),(2,2,'INV-2024-002',1200000.00,1200000.00,0.00,'paid','2024-11-30','2026-06-19 23:59:17'),(3,3,'INV-2024-003',1500000.00,0.00,1500000.00,'pending','2025-01-31','2026-06-19 23:59:17'),(4,4,'INV-2024-004',1800000.00,800000.00,1000000.00,'partial','2025-02-28','2026-06-19 23:59:17'),(5,5,'INV-2024-005',1500000.00,500000.00,1000000.00,'partial','2025-03-31','2026-06-19 23:59:17');
/*!40000 ALTER TABLE `student_invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_logbook`
--

DROP TABLE IF EXISTS `student_logbook`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_logbook` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `placement_id` int(11) DEFAULT NULL,
  `entry_date` date NOT NULL,
  `ward_unit` varchar(100) DEFAULT NULL,
  `shift` enum('Morning','Afternoon','Night','Full Day') DEFAULT 'Morning',
  `procedures_performed` text DEFAULT NULL,
  `patients_seen` int(11) DEFAULT 0,
  `skills_demonstrated` text DEFAULT NULL,
  `supervisor_name` varchar(150) DEFAULT NULL,
  `supervisor_signature` varchar(100) DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_student` (`student_id`),
  KEY `idx_placement` (`placement_id`),
  KEY `idx_date` (`entry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_logbook`
--

LOCK TABLES `student_logbook` WRITE;
/*!40000 ALTER TABLE `student_logbook` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_logbook` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_messages`
--

DROP TABLE IF EXISTS `student_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sender_id` (`sender_id`),
  KEY `idx_recipient_id` (`recipient_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_smsg_recipient` (`recipient_id`,`is_read`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_messages`
--

LOCK TABLES `student_messages` WRITE;
/*!40000 ALTER TABLE `student_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_password_resets`
--

DROP TABLE IF EXISTS `student_password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `student_profile_comments`
--

DROP TABLE IF EXISTS `student_profile_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_profile_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_number` varchar(50) NOT NULL,
  `commenter_id` int(11) NOT NULL,
  `commenter_name` varchar(200) DEFAULT '',
  `comment` text NOT NULL,
  `is_internal` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_comments_student` (`student_number`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_profile_comments`
--

LOCK TABLES `student_profile_comments` WRITE;
/*!40000 ALTER TABLE `student_profile_comments` DISABLE KEYS */;
INSERT INTO `student_profile_comments` VALUES (1,'APP-2024-001',1,'Admin User','All documents verified. Student is fully registered.',0,'2024-01-16 10:30:00'),(2,'APP-2024-002',1,'Admin User','All documents verified. Student is fully registered.',0,'2024-01-16 11:00:00'),(3,'APP-2024-006',2,'Admissions Officer','Application approved. Waiting for final registration.',0,'2024-05-10 08:15:00'),(4,'APP-2024-006',2,'Admissions Officer','Guardian consent form still pending.',1,'2024-05-12 13:20:00'),(5,'APP-2024-008',3,'Review Committee','Medical report needs clarification. Follow up with applicant.',0,'2024-05-15 10:45:00'),(6,'APP-2024-008',3,'Review Committee','Applicant contacted. Will resubmit medical report by Friday.',1,'2024-05-16 07:30:00'),(7,'APP-2024-013',1,'Admin User','Rejected: A-Level certificate could not be verified.',0,'2024-08-20 15:00:00'),(8,'APP-2024-015',2,'Admissions Officer','Approved. Awaiting payment confirmation.',0,'2024-05-18 09:00:00'),(9,'APP-2024-020',2,'Admissions Officer','Approved. All required documents submitted.',0,'2024-05-20 12:30:00'),(10,'APP-2024-003',1,'Admin User','Registration complete. Welcome letter sent.',0,'2024-01-17 09:00:00'),(11,'APP-2024-001',1,'Admin User','All documents verified. Student is fully registered.',0,'2024-01-16 10:30:00'),(12,'APP-2024-002',1,'Admin User','All documents verified. Student is fully registered.',0,'2024-01-16 11:00:00'),(13,'APP-2024-006',2,'Admissions Officer','Application approved. Waiting for final registration.',0,'2024-05-10 08:15:00'),(14,'APP-2024-006',2,'Admissions Officer','Guardian consent form still pending.',1,'2024-05-12 13:20:00'),(15,'APP-2024-008',3,'Review Committee','Medical report needs clarification. Follow up with applicant.',0,'2024-05-15 10:45:00'),(16,'APP-2024-008',3,'Review Committee','Applicant contacted. Will resubmit medical report by Friday.',1,'2024-05-16 07:30:00'),(17,'APP-2024-013',1,'Admin User','Rejected: A-Level certificate could not be verified.',0,'2024-08-20 15:00:00'),(18,'APP-2024-015',2,'Admissions Officer','Approved. Awaiting payment confirmation.',0,'2024-05-18 09:00:00'),(19,'APP-2024-020',2,'Admissions Officer','Approved. All required documents submitted.',0,'2024-05-20 12:30:00'),(20,'APP-2024-003',1,'Admin User','Registration complete. Welcome letter sent.',0,'2024-01-17 09:00:00'),(21,'APP-2024-001',1,'Admin User','All documents verified. Student is fully registered.',0,'2024-01-16 10:30:00'),(22,'APP-2024-002',1,'Admin User','All documents verified. Student is fully registered.',0,'2024-01-16 11:00:00'),(23,'APP-2024-006',2,'Admissions Officer','Application approved. Waiting for final registration.',0,'2024-05-10 08:15:00'),(24,'APP-2024-006',2,'Admissions Officer','Guardian consent form still pending.',1,'2024-05-12 13:20:00'),(25,'APP-2024-008',3,'Review Committee','Medical report needs clarification. Follow up with applicant.',0,'2024-05-15 10:45:00'),(26,'APP-2024-008',3,'Review Committee','Applicant contacted. Will resubmit medical report by Friday.',1,'2024-05-16 07:30:00'),(27,'APP-2024-013',1,'Admin User','Rejected: A-Level certificate could not be verified.',0,'2024-08-20 15:00:00'),(28,'APP-2024-015',2,'Admissions Officer','Approved. Awaiting payment confirmation.',0,'2024-05-18 09:00:00'),(29,'APP-2024-020',2,'Admissions Officer','Approved. All required documents submitted.',0,'2024-05-20 12:30:00'),(30,'APP-2024-003',1,'Admin User','Registration complete. Welcome letter sent.',0,'2024-01-17 09:00:00'),(31,'APP-2024-001',1,'Admin User','All documents verified. Student is fully registered.',0,'2024-01-16 10:30:00'),(32,'APP-2024-002',1,'Admin User','All documents verified. Student is fully registered.',0,'2024-01-16 11:00:00'),(33,'APP-2024-006',2,'Admissions Officer','Application approved. Waiting for final registration.',0,'2024-05-10 08:15:00'),(34,'APP-2024-006',2,'Admissions Officer','Guardian consent form still pending.',1,'2024-05-12 13:20:00'),(35,'APP-2024-008',3,'Review Committee','Medical report needs clarification. Follow up with applicant.',0,'2024-05-15 10:45:00'),(36,'APP-2024-008',3,'Review Committee','Applicant contacted. Will resubmit medical report by Friday.',1,'2024-05-16 07:30:00'),(37,'APP-2024-013',1,'Admin User','Rejected: A-Level certificate could not be verified.',0,'2024-08-20 15:00:00'),(38,'APP-2024-015',2,'Admissions Officer','Approved. Awaiting payment confirmation.',0,'2024-05-18 09:00:00'),(39,'APP-2024-020',2,'Admissions Officer','Approved. All required documents submitted.',0,'2024-05-20 12:30:00'),(40,'APP-2024-003',1,'Admin User','Registration complete. Welcome letter sent.',0,'2024-01-17 09:00:00');
/*!40000 ALTER TABLE `student_profile_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_profiles`
--

DROP TABLE IF EXISTS `student_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `student_program_enrollment`
--

DROP TABLE IF EXISTS `student_program_enrollment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_program_enrollment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `enrollment_date` date NOT NULL,
  `expected_graduation` date DEFAULT NULL,
  `actual_graduation` date DEFAULT NULL,
  `enrollment_status` enum('Enrolled','Deferred','Transferred','Withdrawn','Graduated','Completed') DEFAULT 'Enrolled',
  `academic_year` varchar(20) DEFAULT NULL,
  `intake_period` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_student` (`student_id`),
  KEY `idx_program` (`program_id`),
  KEY `idx_status` (`enrollment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_program_enrollment`
--

LOCK TABLES `student_program_enrollment` WRITE;
/*!40000 ALTER TABLE `student_program_enrollment` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_program_enrollment` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_progression`
--

DROP TABLE IF EXISTS `student_progression`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `student_requests`
--

DROP TABLE IF EXISTS `student_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `request_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `status` enum('pending','in_progress','completed','rejected') NOT NULL DEFAULT 'pending',
  `assigned_to` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_student_id` (`student_id`),
  KEY `idx_status` (`status`),
  KEY `idx_assigned_to` (`assigned_to`),
  KEY `idx_sreq_status_assigned` (`status`,`assigned_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_requests`
--

LOCK TABLES `student_requests` WRITE;
/*!40000 ALTER TABLE `student_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_semester_gpa`
--

DROP TABLE IF EXISTS `student_semester_gpa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_semester_gpa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `academic_year` varchar(20) NOT NULL,
  `semester` varchar(20) NOT NULL,
  `total_credits` int(11) DEFAULT 0,
  `earned_credits` int(11) DEFAULT 0,
  `semester_gpa` decimal(4,2) DEFAULT NULL,
  `cumulative_gpa` decimal(4,2) DEFAULT NULL,
  `academic_standing` enum('Good Standing','Probation','Dismissed','Suspended','Graduated') DEFAULT 'Good Standing',
  `credits_attempted` int(11) DEFAULT 0,
  `credits_passed` int(11) DEFAULT 0,
  `courses_completed` int(11) DEFAULT 0,
  `courses_failed` int(11) DEFAULT 0,
  `calculated_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_student_semester` (`student_id`,`academic_year`,`semester`),
  KEY `idx_gpa` (`semester_gpa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_semester_gpa`
--

LOCK TABLES `student_semester_gpa` WRITE;
/*!40000 ALTER TABLE `student_semester_gpa` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_semester_gpa` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_sick_leave`
--

DROP TABLE IF EXISTS `student_sick_leave`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
  KEY `idx_ssl_date_range` (`leave_from`,`leave_to`),
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
-- Table structure for table `student_status_history`
--

DROP TABLE IF EXISTS `student_status_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_status_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_number` varchar(50) NOT NULL,
  `old_status` varchar(50) DEFAULT '',
  `new_status` varchar(50) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `changed_by_name` varchar(200) DEFAULT '',
  `reason` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status_student` (`student_number`)
) ENGINE=InnoDB AUTO_INCREMENT=89 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_status_history`
--

LOCK TABLES `student_status_history` WRITE;
/*!40000 ALTER TABLE `student_status_history` DISABLE KEYS */;
INSERT INTO `student_status_history` VALUES (1,'APP-2024-001','','New Applicant',1,'System','Application submitted online','2024-01-10 08:00:00'),(2,'APP-2024-001','New Applicant','Under Review',1,'Admin User','Application assigned for review','2024-01-12 09:00:00'),(3,'APP-2024-001','Under Review','Approved',1,'Admin User','All documents verified and approved','2024-01-14 10:00:00'),(4,'APP-2024-001','Approved','Registered',1,'Admin User','Student completed registration and fee payment','2024-01-15 14:00:00'),(5,'APP-2024-002','','New Applicant',1,'System','Application submitted online','2024-01-11 08:30:00'),(6,'APP-2024-002','New Applicant','Under Review',1,'Admin User','Application assigned for review','2024-01-12 11:00:00'),(7,'APP-2024-002','Under Review','Approved',1,'Admin User','All documents verified','2024-01-13 15:00:00'),(8,'APP-2024-002','Approved','Registered',1,'Admin User','Registration complete with full payment','2024-01-15 15:00:00'),(9,'APP-2024-006','','New Applicant',1,'System','Application submitted for May intake','2024-04-20 07:00:00'),(10,'APP-2024-006','New Applicant','Under Review',2,'Admissions Officer','Assigned for May intake review','2024-05-01 08:00:00'),(11,'APP-2024-006','Under Review','Approved',2,'Admissions Officer','Documents verified, awaiting final registration','2024-05-10 08:15:00'),(12,'APP-2024-008','','New Applicant',1,'System','Application submitted for May intake','2024-04-22 09:00:00'),(13,'APP-2024-008','New Applicant','Under Review',3,'Review Committee','Assigned for detailed review','2024-05-14 09:00:00'),(14,'APP-2024-013','','New Applicant',1,'System','Application submitted for August intake','2024-07-25 07:00:00'),(15,'APP-2024-013','New Applicant','Under Review',1,'Admin User','Application assigned for review','2024-08-01 08:00:00'),(16,'APP-2024-013','Under Review','Rejected',1,'Admin User','A-Level certificate could not be verified with issuing institution','2024-08-20 14:30:00'),(17,'APP-2024-015','','New Applicant',1,'System','Application submitted for May intake','2024-04-25 07:00:00'),(18,'APP-2024-015','New Applicant','Under Review',2,'Admissions Officer','Assigned for review','2024-05-05 08:00:00'),(19,'APP-2024-015','Under Review','Approved',2,'Admissions Officer','All documents verified and approved','2024-05-18 09:00:00'),(20,'APP-2024-020','','New Applicant',1,'System','Application submitted for May intake','2024-04-28 07:00:00'),(21,'APP-2024-020','New Applicant','Under Review',2,'Admissions Officer','Application assigned for review','2024-05-08 08:00:00'),(22,'APP-2024-020','Under Review','Approved',2,'Admissions Officer','All required documents submitted and verified','2024-05-20 12:30:00'),(23,'APP-2024-001','','New Applicant',1,'System','Application submitted online','2024-01-10 08:00:00'),(24,'APP-2024-001','New Applicant','Under Review',1,'Admin User','Application assigned for review','2024-01-12 09:00:00'),(25,'APP-2024-001','Under Review','Approved',1,'Admin User','All documents verified and approved','2024-01-14 10:00:00'),(26,'APP-2024-001','Approved','Registered',1,'Admin User','Student completed registration and fee payment','2024-01-15 14:00:00'),(27,'APP-2024-002','','New Applicant',1,'System','Application submitted online','2024-01-11 08:30:00'),(28,'APP-2024-002','New Applicant','Under Review',1,'Admin User','Application assigned for review','2024-01-12 11:00:00'),(29,'APP-2024-002','Under Review','Approved',1,'Admin User','All documents verified','2024-01-13 15:00:00'),(30,'APP-2024-002','Approved','Registered',1,'Admin User','Registration complete with full payment','2024-01-15 15:00:00'),(31,'APP-2024-006','','New Applicant',1,'System','Application submitted for May intake','2024-04-20 07:00:00'),(32,'APP-2024-006','New Applicant','Under Review',2,'Admissions Officer','Assigned for May intake review','2024-05-01 08:00:00'),(33,'APP-2024-006','Under Review','Approved',2,'Admissions Officer','Documents verified, awaiting final registration','2024-05-10 08:15:00'),(34,'APP-2024-008','','New Applicant',1,'System','Application submitted for May intake','2024-04-22 09:00:00'),(35,'APP-2024-008','New Applicant','Under Review',3,'Review Committee','Assigned for detailed review','2024-05-14 09:00:00'),(36,'APP-2024-013','','New Applicant',1,'System','Application submitted for August intake','2024-07-25 07:00:00'),(37,'APP-2024-013','New Applicant','Under Review',1,'Admin User','Application assigned for review','2024-08-01 08:00:00'),(38,'APP-2024-013','Under Review','Rejected',1,'Admin User','A-Level certificate could not be verified with issuing institution','2024-08-20 14:30:00'),(39,'APP-2024-015','','New Applicant',1,'System','Application submitted for May intake','2024-04-25 07:00:00'),(40,'APP-2024-015','New Applicant','Under Review',2,'Admissions Officer','Assigned for review','2024-05-05 08:00:00'),(41,'APP-2024-015','Under Review','Approved',2,'Admissions Officer','All documents verified and approved','2024-05-18 09:00:00'),(42,'APP-2024-020','','New Applicant',1,'System','Application submitted for May intake','2024-04-28 07:00:00'),(43,'APP-2024-020','New Applicant','Under Review',2,'Admissions Officer','Application assigned for review','2024-05-08 08:00:00'),(44,'APP-2024-020','Under Review','Approved',2,'Admissions Officer','All required documents submitted and verified','2024-05-20 12:30:00'),(45,'APP-2024-001','','New Applicant',1,'System','Application submitted online','2024-01-10 08:00:00'),(46,'APP-2024-001','New Applicant','Under Review',1,'Admin User','Application assigned for review','2024-01-12 09:00:00'),(47,'APP-2024-001','Under Review','Approved',1,'Admin User','All documents verified and approved','2024-01-14 10:00:00'),(48,'APP-2024-001','Approved','Registered',1,'Admin User','Student completed registration and fee payment','2024-01-15 14:00:00'),(49,'APP-2024-002','','New Applicant',1,'System','Application submitted online','2024-01-11 08:30:00'),(50,'APP-2024-002','New Applicant','Under Review',1,'Admin User','Application assigned for review','2024-01-12 11:00:00'),(51,'APP-2024-002','Under Review','Approved',1,'Admin User','All documents verified','2024-01-13 15:00:00'),(52,'APP-2024-002','Approved','Registered',1,'Admin User','Registration complete with full payment','2024-01-15 15:00:00'),(53,'APP-2024-006','','New Applicant',1,'System','Application submitted for May intake','2024-04-20 07:00:00'),(54,'APP-2024-006','New Applicant','Under Review',2,'Admissions Officer','Assigned for May intake review','2024-05-01 08:00:00'),(55,'APP-2024-006','Under Review','Approved',2,'Admissions Officer','Documents verified, awaiting final registration','2024-05-10 08:15:00'),(56,'APP-2024-008','','New Applicant',1,'System','Application submitted for May intake','2024-04-22 09:00:00'),(57,'APP-2024-008','New Applicant','Under Review',3,'Review Committee','Assigned for detailed review','2024-05-14 09:00:00'),(58,'APP-2024-013','','New Applicant',1,'System','Application submitted for August intake','2024-07-25 07:00:00'),(59,'APP-2024-013','New Applicant','Under Review',1,'Admin User','Application assigned for review','2024-08-01 08:00:00'),(60,'APP-2024-013','Under Review','Rejected',1,'Admin User','A-Level certificate could not be verified','2024-08-20 14:30:00'),(61,'APP-2024-015','','New Applicant',1,'System','Application submitted for May intake','2024-04-25 07:00:00'),(62,'APP-2024-015','New Applicant','Under Review',2,'Admissions Officer','Assigned for review','2024-05-05 08:00:00'),(63,'APP-2024-015','Under Review','Approved',2,'Admissions Officer','All documents verified and approved','2024-05-18 09:00:00'),(64,'APP-2024-020','','New Applicant',1,'System','Application submitted for May intake','2024-04-28 07:00:00'),(65,'APP-2024-020','New Applicant','Under Review',2,'Admissions Officer','Application assigned for review','2024-05-08 08:00:00'),(66,'APP-2024-020','Under Review','Approved',2,'Admissions Officer','All required documents submitted and verified','2024-05-20 12:30:00'),(67,'APP-2024-001','','New Applicant',1,'System','Application submitted online','2024-01-10 08:00:00'),(68,'APP-2024-001','New Applicant','Under Review',1,'Admin User','Application assigned for review','2024-01-12 09:00:00'),(69,'APP-2024-001','Under Review','Approved',1,'Admin User','All documents verified and approved','2024-01-14 10:00:00'),(70,'APP-2024-001','Approved','Registered',1,'Admin User','Student completed registration and fee payment','2024-01-15 14:00:00'),(71,'APP-2024-002','','New Applicant',1,'System','Application submitted online','2024-01-11 08:30:00'),(72,'APP-2024-002','New Applicant','Under Review',1,'Admin User','Application assigned for review','2024-01-12 11:00:00'),(73,'APP-2024-002','Under Review','Approved',1,'Admin User','All documents verified','2024-01-13 15:00:00'),(74,'APP-2024-002','Approved','Registered',1,'Admin User','Registration complete with full payment','2024-01-15 15:00:00'),(75,'APP-2024-006','','New Applicant',1,'System','Application submitted for May intake','2024-04-20 07:00:00'),(76,'APP-2024-006','New Applicant','Under Review',2,'Admissions Officer','Assigned for May intake review','2024-05-01 08:00:00'),(77,'APP-2024-006','Under Review','Approved',2,'Admissions Officer','Documents verified, awaiting final registration','2024-05-10 08:15:00'),(78,'APP-2024-008','','New Applicant',1,'System','Application submitted for May intake','2024-04-22 09:00:00'),(79,'APP-2024-008','New Applicant','Under Review',3,'Review Committee','Assigned for detailed review','2024-05-14 09:00:00'),(80,'APP-2024-013','','New Applicant',1,'System','Application submitted for August intake','2024-07-25 07:00:00'),(81,'APP-2024-013','New Applicant','Under Review',1,'Admin User','Application assigned for review','2024-08-01 08:00:00'),(82,'APP-2024-013','Under Review','Rejected',1,'Admin User','A-Level certificate could not be verified','2024-08-20 14:30:00'),(83,'APP-2024-015','','New Applicant',1,'System','Application submitted for May intake','2024-04-25 07:00:00'),(84,'APP-2024-015','New Applicant','Under Review',2,'Admissions Officer','Assigned for review','2024-05-05 08:00:00'),(85,'APP-2024-015','Under Review','Approved',2,'Admissions Officer','All documents verified and approved','2024-05-18 09:00:00'),(86,'APP-2024-020','','New Applicant',1,'System','Application submitted for May intake','2024-04-28 07:00:00'),(87,'APP-2024-020','New Applicant','Under Review',2,'Admissions Officer','Application assigned for review','2024-05-08 08:00:00'),(88,'APP-2024-020','Under Review','Approved',2,'Admissions Officer','All required documents submitted and verified','2024-05-20 12:30:00');
/*!40000 ALTER TABLE `student_status_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_timetables`
--

DROP TABLE IF EXISTS `student_timetables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `student_transcripts`
--

DROP TABLE IF EXISTS `student_transcripts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_transcripts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `transcript_number` varchar(50) NOT NULL,
  `request_type` enum('Official','Unofficial','Certified','Digital') DEFAULT 'Official',
  `purpose` text DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `total_credits` int(11) DEFAULT 0,
  `cumulative_gpa` decimal(4,2) DEFAULT NULL,
  `class_of_award` varchar(100) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `status` enum('Requested','Processing','Ready','Issued','Collected') DEFAULT 'Requested',
  `requested_by` int(11) DEFAULT NULL,
  `processed_by` int(11) DEFAULT NULL,
  `issued_by` int(11) DEFAULT NULL,
  `issued_at` datetime DEFAULT NULL,
  `collected_at` datetime DEFAULT NULL,
  `fee_amount` decimal(10,2) DEFAULT 0.00,
  `fee_paid` tinyint(1) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_student` (`student_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_transcripts`
--

LOCK TABLES `student_transcripts` WRITE;
/*!40000 ALTER TABLE `student_transcripts` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_transcripts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_warnings`
--

DROP TABLE IF EXISTS `student_warnings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_warnings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `warning_type` enum('Academic','Discipline','Attendance','Clinical','Financial','Other') NOT NULL,
  `severity` enum('Verbal','Written','Final','Suspension','Expulsion') NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `issued_by` int(11) NOT NULL,
  `issued_by_name` varchar(200) DEFAULT NULL,
  `warning_date` date NOT NULL,
  `valid_until` date DEFAULT NULL,
  `acknowledged` tinyint(1) DEFAULT 0,
  `acknowledged_at` datetime DEFAULT NULL,
  `status` enum('Active','Expired','Appealed','Revoked') DEFAULT 'Active',
  `appeal_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_student` (`student_id`),
  KEY `idx_type` (`warning_type`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_warnings`
--

LOCK TABLES `student_warnings` WRITE;
/*!40000 ALTER TABLE `student_warnings` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_warnings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_welfare_cases`
--

DROP TABLE IF EXISTS `student_welfare_cases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_welfare_cases` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `case_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `case_description` text DEFAULT NULL,
  `immediate_actions` text DEFAULT NULL,
  `severity` varchar(20) DEFAULT 'medium',
  `reported_by` int(10) unsigned DEFAULT NULL,
  `status` varchar(30) DEFAULT 'open',
  `assigned_to` int(10) unsigned DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `student_number` varchar(60) DEFAULT NULL,
  `full_name` varchar(200) DEFAULT NULL,
  `program` varchar(120) DEFAULT NULL,
  `level` varchar(20) DEFAULT NULL,
  `status` varchar(30) DEFAULT 'Active',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_student_number` (`student_number`),
  KEY `idx_stu_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
INSERT INTO `students` VALUES (1,'Grace','Nakato','ISNM-2024-001','Grace Nakato','Diploma Nursing',NULL,'Active'),(2,'David','Ssali','ISNM-2024-002','David Ssali','Certificate Midwifery',NULL,'Active'),(3,'Mary','Nalwoga','ISNM-2024-003','Mary Nalwoga','Certificate Nursing',NULL,'Active'),(4,'James','Okello','ISNM-2024-004','James Okello','Diploma Midwifery',NULL,'Active'),(5,'Sarah','Kyomugisha','ISNM-2024-005','Sarah Kyomugisha','Diploma Nursing',NULL,'Active');
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subjects`
--

DROP TABLE IF EXISTS `subjects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `system_modules`
--

DROP TABLE IF EXISTS `system_modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_modules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `label` varchar(150) NOT NULL,
  `department_id` int(11) NOT NULL,
  `icon` varchar(50) DEFAULT 'cube',
  `route` varchar(200) NOT NULL,
  `handler_url` varchar(200) DEFAULT NULL,
  `tables_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'All tables this module touches' CHECK (json_valid(`tables_json`)),
  `description` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `is_student_module` tinyint(1) DEFAULT 0 COMMENT '1 = student portal only',
  `is_document_module` tinyint(1) DEFAULT 0 COMMENT '1 = document center',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `department_id` (`department_id`),
  CONSTRAINT `system_modules_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `module_departments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=260 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_modules`
--

LOCK TABLES `system_modules` WRITE;
/*!40000 ALTER TABLE `system_modules` DISABLE KEYS */;
INSERT INTO `system_modules` VALUES (1,'academic_records','Academic Records',1,'file-alt','../dashboards/academic-registrar.php?page=academic_records',NULL,'[\"academic_records\",\"academic_programs\",\"academic_course_catalog\",\"academic_curriculum_development\",\"registrar_academic_records\"]','Student academic records, transcripts, GPA',1,1,0,0,'2026-06-30 18:31:20','2026-07-01 03:07:01'),(2,'exams_results','Exams & Results',1,'clipboard-check','../dashboards/exams-results.php',NULL,'[\"exam_results\",\"exam_schedules\",\"examination_records\",\"examination_results\",\"national_exam_results\",\"exams\",\"result_approvals\",\"result_publication\",\"result_publications\"]','Examination records, grading, results publication',2,1,0,0,'2026-06-30 18:31:20','2026-07-01 03:07:01'),(3,'course_management','Course Management',1,'layer-group','../dashboards/curriculum-management.php',NULL,'[\"course_assignments\",\"course_registrations\",\"classes\",\"subjects\",\"academic_analytics\"]','Course assignments, registration, subjects',3,1,0,0,'2026-06-30 18:31:20','2026-07-01 03:07:01'),(4,'timetable','Timetable',1,'calendar','../dashboards/timetable.php',NULL,'[\"academic_timetable\",\"timetables\",\"student_timetables\"]','Class timetables and scheduling',4,1,0,0,'2026-06-30 18:31:20','2026-07-01 03:07:01'),(5,'grading_system','Grading & GPA',1,'star','../dashboards/grade-scales.php',NULL,'[\"grade_scale\",\"grade_scales\",\"grades\",\"grade_change_history\",\"gpa_settings\",\"grading_approval_workflow\",\"grading_approval_workflow_log\",\"grading_notifications\"]','Grading scales, GPA calculation, grade changes',5,1,0,0,'2026-06-30 18:31:20','2026-07-01 03:07:01'),(6,'assessment_scores','Assessment Scores',1,'poll','../dashboards/exams-results.php?page=assessment_scores',NULL,'[\"assessment_scores\",\"assessments\"]','Continuous assessment tracking',6,1,0,0,'2026-06-30 18:31:20','2026-07-01 03:07:01'),(7,'academic_calendar','Academic Calendar',1,'calendar-alt','../dashboards/academic-calendar.php',NULL,'[\"academic_calendar\",\"registrar_academic_calendar\",\"semesters\"]','Academic terms, semesters, calendar',7,1,0,0,'2026-06-30 18:31:20','2026-07-01 03:07:01'),(8,'academic_reports','Academic Reports',1,'chart-bar','../dashboards/director-academics.php?page=academic_reports',NULL,'[\"academic_reports\",\"academic_summary\",\"academic_audit_logs\"]','Academic analytics and reports',8,1,0,0,'2026-06-30 18:31:20','2026-07-01 03:07:01'),(9,'academic_approvals','Academic Approvals',1,'check-double','../dashboards/director-academics.php?page=academic_approvals',NULL,'[\"academic_approvals\",\"approval_stages\"]','Academic workflow approvals',9,1,0,0,'2026-06-30 18:31:20','2026-07-01 03:07:01'),(10,'fee_management','Fees Management',2,'money-bill-wave','../dashboards/bursar-billing.php',NULL,'[\"bursar_fee_items\",\"fee_accounts\",\"fee_adjustments\",\"student_fees\",\"student_fee_accounts\",\"student_fee_assignments\",\"student_invoices\",\"bursar_invoices\",\"invoice_records\",\"fee_structure\",\"fee_structures\",\"late_payment_settings\"]','Fee structure, accounts, invoicing',1,1,0,0,'2026-06-30 18:31:20','2026-07-01 03:07:01'),(11,'payments','Payments & Receipts',2,'receipt','../dashboards/bursar-payments.php',NULL,'[\"bursar_payments\",\"bursar_receipts\",\"bursar_payment_verification\",\"fee_payments\",\"payment_records\",\"payment_methods\",\"payment_routes\",\"payment_approvals\",\"payments\",\"proof_of_payments\",\"payment_receipts\"]','Payment processing, receipts, verification',2,1,0,0,'2026-06-30 18:31:20','2026-07-01 03:07:01'),(12,'budget_management','Budget & Expenses',2,'chart-pie','../dashboards/budget-management.php',NULL,'[\"budget_lines\",\"bursar_budget_items\",\"departmental_budgets\",\"bursar_expenses\",\"expenses\",\"expenditures\",\"expenditure_tracking\",\"cost_centers\",\"cost_center_management\"]','Budget planning, expense tracking',3,1,0,0,'2026-06-30 18:31:20','2026-07-01 03:07:01'),(13,'payroll','Payroll',2,'money-check','../dashboards/bursar-payroll.php',NULL,'[\"bursar_payroll\",\"payroll_records\",\"payroll_runs\",\"payroll_payslips\",\"payroll_payments\",\"payroll_employees\",\"payroll_items\",\"payroll_periods\",\"payroll_settings\",\"payroll_allowances\",\"payroll_allowance_types\",\"payroll_deductions\",\"payroll_deduction_types\",\"payroll_employee_allowances\",\"payroll_employee_deductions\",\"payroll_bonuses\",\"payroll_bonus\",\"payroll_loans\",\"payroll_overtime\",\"payroll_approval_history\",\"payroll_approvals\",\"payroll_audit_logs\",\"staff_salaries\",\"salary_structures\",\"payslips\",\"subscription_deductions\"]','Salary processing, payslips, deductions',4,1,0,0,'2026-06-30 18:31:20','2026-07-01 03:07:01'),(14,'general_ledger','General Ledger',2,'book','../dashboards/general-ledger.php',NULL,'[\"bursar_general_ledger\",\"bursar_chart_of_accounts\",\"bursar_cashbook\",\"cashbook\",\"general_ledger\",\"journal_entries\",\"journal_entry_lines\"]','Chart of accounts, journal entries',5,1,0,0,'2026-06-30 18:31:20','2026-07-01 03:07:01'),(15,'tax_management','Tax & VAT',2,'file-invoice','../dashboards/bursar-tax.php',NULL,'[\"bursar_tax_filings\",\"bursar_tax_periods\",\"bursar_tax_records\",\"bursar_vat_reports\",\"bursar_withholding_tax\",\"ura_reports\"]','Tax filings, VAT reports, URA',6,1,0,0,'2026-06-30 18:31:20','2026-07-01 03:07:01'),(16,'bank_reconciliation','Bank Reconciliation',2,'university','../dashboards/bank-reconciliation.php',NULL,'[\"bank_accounts\",\"bank_reconciliation\",\"bank_reconciliations\"]','Bank accounts, reconciliation',7,1,0,0,'2026-06-30 18:31:20','2026-07-01 03:07:01'),(17,'financial_reports','Financial Reports',2,'chart-line','../dashboards/financial-reports.php',NULL,'[\"financial_records\",\"financial_audit_log\",\"financial_reports\",\"advanced_reports\",\"bursar_daily_collections\"]','Financial analytics and reports',8,1,0,0,'2026-06-30 18:31:20','2026-07-01 03:07:01'),(18,'scholarships_mgmt','Scholarships & Sponsorships',2,'award','../dashboards/scholarships-sponsorships.php',NULL,'[\"bursar_scholarships\",\"bursar_sponsorships\",\"scholarships\",\"student_scholarships\"]','Scholarship and sponsorship management',9,1,0,0,'2026-06-30 18:31:20','2026-07-01 03:07:01'),(19,'bursar_allowances','Allowances & Bonuses',2,'hand-holding-usd','../dashboards/bursar-payroll.php?page=bursar_allowances',NULL,'[\"bursar_allowances\",\"bursar_deductions\",\"bursar_discounts\",\"bursar_penalties\",\"bursar_penalty_config\"]','Allowances, deductions, penalties',10,1,0,0,'2026-06-30 18:31:20','2026-07-01 03:07:01'),(20,'bursar_assets','Assets & Depreciation',2,'building','../dashboards/storekeeper.php?page=bursar_assets',NULL,'[\"bursar_assets\",\"asset_depreciation\",\"finance_assets\"]','Asset tracking and depreciation',11,1,0,0,'2026-06-30 18:31:20','2026-07-01 03:07:01'),(21,'staff_management','Staff Records',3,'users','../dashboards/staff-directory.php',NULL,'[\"staff\",\"hr_users\",\"staff_profiles\",\"staff_departments\",\"departments\",\"employment_contracts\",\"employment_details\",\"staff_contracts\",\"staff_salaries\",\"salary_structures\"]','Staff profiles, contracts, departments',1,1,0,0,'2026-06-30 18:31:20','2026-07-01 03:07:01'),(22,'leave_management','Leave Management',3,'calendar-minus','../dashboards/leave-management.php',NULL,'[\"leave_requests\",\"leave_balances\",\"leave_balance\",\"leave_types\",\"leaves\",\"staff_leave_requests\"]','Leave requests, balances, types',2,1,0,0,'2026-06-30 18:31:20','2026-07-01 03:07:01'),(23,'attendance','Attendance',3,'fingerprint','../dashboards/staff-attendance.php',NULL,'[\"attendance\",\"attendance_status\",\"staff_attendance\",\"staff_login_sessions\"]','Staff attendance tracking',3,1,0,0,'2026-06-30 18:31:20','2026-07-01 03:07:01'),(24,'recruitment','Recruitment',3,'user-plus','../dashboards/recruitment.php',NULL,'[\"recruitment\",\"recruitment_applications\",\"recruitment_jobs\",\"job_applications\",\"job_offers\",\"job_vacancies\",\"interview_scheduling\"]','Job postings, applications, interviews',4,1,0,0,'2026-06-30 18:31:20','2026-07-01 03:07:01'),(25,'training_cpd','Training & CPD',3,'graduation-cap','../dashboards/training-cpd.php',NULL,'[\"employee_training\",\"staff_training\",\"trainings\"]','Staff training and continuing professional development',5,1,0,0,'2026-06-30 18:31:20','2026-07-01 03:07:01'),(26,'appraisals','Appraisals',3,'star','../dashboards/performance-appraisal.php',NULL,'[\"appraisals\",\"staff_appraisals\",\"appraisal_periods\",\"appraisal_ratings\",\"performance_indicators\",\"performance_metrics\",\"performance_reviews\"]','Performance appraisals and reviews',6,1,0,0,'2026-06-30 18:31:20','2026-07-01 03:07:01'),(27,'disciplinary','Disciplinary',3,'gavel','../dashboards/staff-disciplinary.php',NULL,'[\"disciplinary_actions\",\"disciplinary_cases\",\"disciplinary_records\",\"staff_disciplinary\"]','Disciplinary cases and actions',7,1,0,0,'2026-06-30 18:31:20','2026-07-01 03:07:01'),(28,'resignations','Resignations',3,'sign-out-alt','../dashboards/resignations.php',NULL,'[\"staff_resignations\"]','Resignation processing',8,1,0,0,'2026-06-30 18:31:20','2026-07-01 03:07:01'),(29,'hr_reports','HR Reports',3,'chart-bar','../dashboards/hr-manager.php?page=hr_reports',NULL,'[\"hr_reports\",\"hr_activity_log\",\"hr_activity_logs\",\"staff_audit_logs\",\"staff_activity_log\"]','HR analytics and activity logs',9,1,0,0,'2026-06-30 18:31:20','2026-07-01 03:07:01'),(30,'hr_settings','HR Settings',3,'cogs','../dashboards/hr-manager.php?page=hr_settings',NULL,'[\"hr_settings\"]','HR system configuration',10,1,0,0,'2026-06-30 18:31:20','2026-07-01 03:07:01'),(31,'professional_licenses','Professional Licenses',3,'id-badge','../dashboards/professional-licenses.php',NULL,'[\"professional_licenses\",\"staff_licenses\"]','Staff professional license tracking',11,1,0,0,'2026-06-30 18:31:20','2026-07-01 03:07:01'),(32,'applicant_management','Applicant Management',4,'user-plus','../dashboards/director-admissions.php?page=applicant_management',NULL,'[\"applicants\",\"applicant_messages\",\"applicant_requirement_status\",\"admission_requirements\",\"admission_activity_logs\"]','Application processing, requirements',1,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(33,'intake_planning','Intake Planning',4,'calendar-plus','../dashboards/intake-planning.php',NULL,'[\"intakes\",\"student_admissions\",\"pending_students\"]','Intake planning, student admissions',2,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(34,'admission_letters','Admission Letters',4,'mail-bulk','../dashboards/admission-letters.php',NULL,'[\"admission_notifications\"]','Admission letter generation',3,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(35,'enrollment','Enrollment & Registration',4,'clipboard-list','../dashboards/director-admissions.php?page=enrollment',NULL,'[\"registrar_student_registration\",\"student_course_registrations\",\"course_catalog\",\"course_prerequisites\"]','Student enrollment and course registration',4,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(36,'it_infrastructure','IT Infrastructure',5,'server','../dashboards/director-ict.php?page=it_infrastructure',NULL,'[\"it_infrastructure\"]','Servers, network, infrastructure',1,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(37,'cybersecurity','Cybersecurity',5,'shield-alt','../dashboards/cybersecurity.php',NULL,'[\"api_keys\",\"backup_management\"]','Security policies, API keys, backups',2,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(38,'ict_support','ICT Support',5,'headset','../dashboards/it-support-tickets.php',NULL,'[\"it_support_tickets\"]','IT helpdesk and support tickets',3,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(39,'ict_policy','ICT Policy',5,'file-contract','../dashboards/ict-policy.php',NULL,'[\"ict_policy\"]','ICT policies and procedures',4,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(40,'system_logs','System Logs',5,'list-alt','../dashboards/system-admin.php?page=system_logs',NULL,'[\"system_logs\",\"error_logs\",\"audit_trail\",\"analytics_cache\"]','System and error logs',5,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(41,'digital_learning','Digital Learning',5,'laptop','../dashboards/digital-learning.php',NULL,'[\"digital_learning\",\"teaching_resources\"]','E-learning platform management',6,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(42,'library_catalog','Book Catalog',6,'book','../dashboards/school-librarian.php?page=library_catalog',NULL,'[\"library_books\"]','Book catalog management',1,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(43,'library_borrowing','Borrowing',6,'hand-holding','../dashboards/school-librarian.php?page=library_borrowing',NULL,'[\"library_borrowing\",\"library_transactions\",\"library_members\"]','Book borrowing and returns',2,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(44,'library_resources','Digital Resources',6,'ebook','../dashboards/school-librarian.php?page=library_resources',NULL,'[\"library_digital_resources\"]','Digital library resources',3,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(45,'library_fines','Fines & Clearance',6,'exclamation-triangle','../dashboards/school-librarian.php?page=library_fines',NULL,'[\"library_fines\",\"library_clearance\"]','Library fines and clearance',4,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(46,'library_management','Library Settings',6,'cogs','../dashboards/school-librarian.php?page=library_management',NULL,'[\"library_management\"]','Library system configuration',5,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(47,'hostel_management','Hostel Management',7,'home','../dashboards/hostel-management.php',NULL,'[\"hostel_management\",\"hostel_inspections\",\"hostel_maintenance_requests\",\"hostel_clearance\",\"student_hostel_allocations\",\"hostel_allocations\",\"hostel_rooms\"]','Hostel allocation, inspections, maintenance',1,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(48,'meal_tracking','Meal Tracking',7,'utensils','../dashboards/meal-accommodation.php',NULL,'[\"meal_tracking\"]','Meal plan tracking',2,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(49,'clinical_placements','Clinical Placements',8,'hospital','../dashboards/clinical-placement.php',NULL,'[\"clinical_placements\",\"nursing_clinical_placements\",\"nursing_clinical_logbook\",\"clinical_rotations\",\"clinical_placement\",\"clinical_placements_students\"]','Clinical placement management',1,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(50,'nursing_training','Nursing Training',8,'user-nurse','../dashboards/head-nursing.php',NULL,'[\"nursing_practical_assessment\",\"nursing_skills_training\",\"nursing_students\",\"midwifery_students\"]','Nursing practical training',2,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(51,'midwifery','Midwifery',8,'baby','../dashboards/head-midwifery.php',NULL,'[\"midwifery_antenatal_care\",\"midwifery_family_planning\",\"midwifery_labor_delivery\",\"midwifery_postnatal_care\"]','Midwifery clinical records',3,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(52,'sickbay','Sickbay',8,'medkit','../dashboards/sickbay.php',NULL,'[\"sickbay_settings\",\"daily_sick_records\",\"health_incidents\",\"student_health_records\",\"student_health_incidents\",\"student_sick_leave\",\"sickness_directory\",\"medicine_stock\",\"medicine_stock_transactions\"]','Student health and sickbay',4,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(53,'clinical_assessments','Clinical Assessments',8,'clipboard-check','../dashboards/clinical-placement.php?page=clinical_assessments',NULL,'[\"clinical_assessments\",\"clinical_training\"]','Clinical assessment tracking',5,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(54,'incidents','Incident Reports',8,'exclamation-circle','../dashboards/head-nursing.php?page=incidents',NULL,'[\"incident_reports\"]','Health incident reporting',6,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(55,'vehicle_management','Vehicles',9,'bus','../dashboards/drivers.php',NULL,'[\"fuel_management\",\"trip_logs\",\"route_schedules\"]','Vehicle fleet, fuel, trips',1,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(56,'access_control','Access Control',10,'key','../dashboards/security.php?page=access_control',NULL,'[\"access_control_logs\",\"access_logs\",\"security_access_logs\"]','Access logs and control',1,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(57,'visitor_management','Visitors',10,'id-card','../dashboards/visitor-access.php',NULL,'[\"security_visitors\",\"visitor_logs\"]','Visitor registration and tracking',2,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(58,'security_patrols','Patrols & Equipment',10,'shield-alt','../dashboards/security.php?page=security_patrols',NULL,'[\"security_patrols\",\"security_equipment\"]','Patrol schedules, equipment',3,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(59,'emergency','Emergency',10,'exclamation-triangle','../dashboards/security.php?page=emergency',NULL,'[\"security_emergency_contacts\",\"emergency_contacts\"]','Emergency contacts and procedures',4,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(60,'notifications','Notifications',11,'bell','../dashboards/notifications.php',NULL,'[\"notifications\",\"notification_logs\",\"notification_reads\",\"email_notifications_queue\",\"email_logs\",\"sms_logs\",\"dg_read_notifications\",\"institutional_alerts\",\"alerts\"]','System notifications, email, SMS',1,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(61,'messaging','Messaging',11,'envelope','../dashboards/messaging.php',NULL,'[\"communications\",\"staff_communications\",\"staff_messages\",\"portal_messages\",\"student_communication_messages\",\"student_messages\",\"messages\",\"secretary_messages\",\"financial_messages\",\"financial_notices\",\"financial_notices\"]','Internal messaging system',2,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(62,'announcements','Announcements',11,'bullhorn','../dashboards/student-announcements.php',NULL,'[\"announcements\",\"hr_announcements\",\"director_news\",\"principal_notices\",\"circulars\"]','System-wide announcements',3,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(63,'document_center','Document Center',12,'folder-open','../dashboards/document_management.php',NULL,'[\"file_uploads\",\"generated_documents\",\"document_generation_log\",\"document_templates\",\"document_print_configs\",\"document_settings\",\"document_tracking\"]','Central document storage',1,1,0,1,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(64,'certificates','Certificates',12,'certificate','../dashboards/print_certificate.php',NULL,'[\"certificate_templates\",\"certificate_uploads\",\"certificate_verification\",\"certificates\",\"registrar_certificates\"]','Certificate templates and generation',2,1,0,1,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(65,'transcripts','Transcripts',12,'file-alt','../dashboards/print_transcript.php',NULL,'[\"transcript_items\",\"transcript_templates\",\"transcripts\",\"registrar_transcript_requests\"]','Transcript generation and management',3,1,0,1,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(66,'quality_assurance','Quality Assurance',13,'check-circle','../dashboards/quality-assurance.php',NULL,'[\"quality_assurance\",\"compliance_records\",\"compliance_requirements\",\"compliance_tracking\",\"compliance_alerts\",\"accreditation_management\"]','Quality and accreditation',1,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(67,'penalty_config','Penalty Configuration',13,'exclamation','../dashboards/penalty-configurations.php',NULL,'[\"penalty_config\",\"penalty_configurations\"]','Penalty rules and configuration',2,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(68,'research_projects','Research Projects',14,'flask','../dashboards/research-projects.php',NULL,'[\"research_projects\"]','Research project management',1,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(69,'partnerships','Partnerships',14,'handshake','../dashboards/partnerships.php',NULL,'[\"partnerships\",\"partner_schools\"]','Institutional partnerships',2,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(70,'graduation_mgmt','Graduation Management',15,'graduation-cap','../dashboards/graduation-management.php',NULL,'[\"graduation_candidates\",\"graduation_approvals\",\"registrar_graduation\"]','Graduation candidates and approvals',1,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(71,'transcript_requests','Transcript Requests',15,'file-alt','../dashboards/staff_transcript_generation.php',NULL,'[\"registrar_transcript_requests\"]','Transcript request processing',2,1,0,0,'2026-06-30 18:31:21','2026-07-01 03:07:01'),(73,'approval_workflows','Approval Workflows',1,'tasks','/workflow/approvals',NULL,'[\"approval_requests\",\"approval_actions\",\"approval_workflows\",\"approval_stages\"]','Multi-level approval workflows',2,1,0,0,'2026-06-30 18:57:32','2026-06-30 18:57:32'),(74,'calendar_events','Calendar & Events',1,'calendar-alt','../dashboards/academic-calendar.php?page=calendar_events',NULL,'[\"calendar_events\"]','Event scheduling',3,1,0,0,'2026-06-30 18:57:32','2026-07-01 03:07:01'),(75,'procurement','Procurement',1,'shopping-cart','../dashboards/storekeeper.php',NULL,'[\"procurement_requests\"]','Procurement',8,1,0,0,'2026-06-30 18:57:32','2026-07-01 03:07:01'),(76,'task_management','Task Management',1,'clipboard-list','/workflow/tasks',NULL,'[\"task_assignments\"]','Task assignments',9,1,0,0,'2026-06-30 18:57:32','2026-06-30 18:57:32'),(112,'counseling','Counseling',5,'hands-helping','../dashboards/counseling-welfare.php',NULL,'[\"counseling_sessions\"]','Student counseling',13,1,0,0,'2026-06-30 18:57:32','2026-07-01 03:07:01'),(113,'guild_management','Guild & Student Union',5,'trophy','../dashboards/guild-president.php?page=guild_management',NULL,'[\"student_activities\"]','Student guild',14,1,0,0,'2026-06-30 18:57:32','2026-07-01 03:07:01'),(114,'sports_events','Sports & Events',5,'futbol','../dashboards/guild-president.php?page=sports_events',NULL,'[\"sports_events\"]','Sports events',15,1,0,0,'2026-06-30 18:57:32','2026-07-01 03:07:01'),(115,'volunteer_applications','Volunteer Applications',5,'hand-holding-heart','../dashboards/volunteer-applications.php',NULL,'[\"volunteer_applications\"]','Volunteer program',16,1,0,0,'2026-06-30 18:57:32','2026-07-01 03:07:01'),(116,'inventory_management','Inventory Management',6,'boxes','../dashboards/storekeeper.php?page=inventory',NULL,'{\"main\":[\"store_inventory\",\"store_categories\",\"store_transactions\"]}','Manage store inventory, categories and stock levels',3,1,0,0,'2026-07-02 06:18:06','2026-07-02 06:18:06'),(117,'requisition_processing','Requisition Processing',6,'clipboard-list','../dashboards/storekeeper.php?page=requests',NULL,'{\"main\":[\"store_requests\",\"store_request_items\"]}','Process and fulfill incoming requisitions',4,1,0,0,'2026-07-02 06:18:06','2026-07-02 06:18:06'),(118,'store_approval','Store Approval',1,'check-circle','../dashboards/director-general.php?page=store',NULL,'{\"main\":[\"store_requests\",\"store_request_items\"]}','DG approval for store requisitions',5,1,0,0,'2026-07-02 06:18:06','2026-07-02 06:18:06'),(147,'system_settings','System Settings',9,'cogs','../dashboards/system-admin.php?page=system_settings',NULL,'[\"system_settings\"]','System settings',1,1,0,0,'2026-06-30 18:57:32','2026-07-01 03:07:01'),(148,'user_management','User Management',9,'users-cog','../dashboards/system-admin.php?page=user_management',NULL,'[\"users\",\"staff_roles\"]','User management',2,1,0,0,'2026-06-30 18:57:32','2026-07-01 03:07:01'),(149,'audit_trail','Audit Trail',9,'history','../dashboards/audit-management.php',NULL,'[\"audit_trail\",\"system_logs\"]','Audit trail',3,1,0,0,'2026-06-30 18:57:32','2026-07-01 03:07:01'),(150,'backup_management','Backup & Recovery',9,'database','../dashboards/system-admin.php?page=backup_management',NULL,'[\"backup_management\"]','Backup management',4,1,0,0,'2026-06-30 18:57:32','2026-07-01 03:07:01'),(151,'recycle_bin','Recycle Bin',9,'trash-alt','../dashboards/recycle_bin.php',NULL,'[\"recycle_bin\"]','Recycle bin',5,1,0,0,'2026-06-30 18:57:32','2026-07-01 03:07:01'),(152,'website_content','Website Content',9,'globe','/website/content',NULL,'[\"news_images\"]','Website content',6,1,0,0,'2026-06-30 18:57:32','2026-06-30 18:57:32'),(153,'news_management','News & Announcements',9,'newspaper','/website/news',NULL,'[\"director_news\"]','News management',7,1,0,0,'2026-06-30 18:57:32','2026-06-30 18:57:32'),(159,'transport_dashboard','Transport Dashboard',6,'bus','../dashboards/drivers.php?page=home',NULL,'[]','Main transport dashboard overview',1,1,0,0,'2026-07-01 05:58:42','2026-07-01 05:58:42'),(160,'transport_vehicles','Vehicles',6,'car','../dashboards/drivers.php?page=transport-vehicles',NULL,'[\"transport_vehicles\"]','Manage transport vehicles',2,1,0,0,'2026-07-01 05:58:42','2026-07-01 05:58:42'),(161,'transport_routes','Routes',6,'route','../dashboards/drivers.php?page=transport-routes',NULL,'[\"transport_routes\"]','Manage transport routes',3,1,0,0,'2026-07-01 05:58:42','2026-07-01 05:58:42'),(162,'transport_trips','Trips',6,'road','../dashboards/drivers.php?page=transport-trips',NULL,'[\"transport_trips\"]','Manage transport trips',4,1,0,0,'2026-07-01 05:58:42','2026-07-01 05:58:42'),(163,'transport_students','Student Transport',6,'users','../dashboards/drivers.php?page=student-transport',NULL,'[\"transport_student_assignments\"]','Manage student transport assignments',5,1,0,0,'2026-07-01 05:58:42','2026-07-01 05:58:42'),(164,'transport_fuel','Fuel Log',6,'gas-pump','../dashboards/drivers.php?page=fuel-log',NULL,'[\"transport_fuel_log\"]','Track fuel consumption',6,1,0,0,'2026-07-01 05:58:42','2026-07-01 05:58:42'),(165,'transport_reports','Reports',6,'chart-bar','../dashboards/drivers.php?page=reports',NULL,'[\"transport_vehicles\",\"transport_trips\",\"transport_fuel_log\"]','Transport reports and analytics',7,1,0,0,'2026-07-01 05:58:42','2026-07-01 05:58:42'),(173,'transport_approvals','Transport Approvals',6,'bus','../dashboards/director-general.php?page=transport',NULL,'[\"transport_trips\",\"transport_vehicles\",\"transport_routes\"]','Approve transport trips and view routes',51,1,0,0,'2026-07-01 06:22:53','2026-07-01 06:22:53');
/*!40000 ALTER TABLE `system_modules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `system_settings` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_type` enum('string','integer','boolean','json','text') NOT NULL DEFAULT 'string',
  `setting_group` varchar(100) NOT NULL DEFAULT 'general',
  `description` varchar(255) DEFAULT NULL,
  `is_public` tinyint(1) NOT NULL DEFAULT 0,
  `updated_by` int(11) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_setting_key` (`setting_key`),
  KEY `idx_setting_group` (`setting_group`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `system_settings`
--

LOCK TABLES `system_settings` WRITE;
/*!40000 ALTER TABLE `system_settings` DISABLE KEYS */;
INSERT INTO `system_settings` VALUES (1,'institution_name','Iganga School of Nursing and Midwifery','string','general','Institution name',0,NULL,'2026-06-30 15:35:02','2026-06-30 15:35:02'),(2,'institution_motto','Excellence in Nursing Education','string','general','Institution motto',0,NULL,'2026-06-30 15:35:02','2026-06-30 15:35:02'),(3,'academic_year','2026','string','academic','Current academic year',0,NULL,'2026-06-30 15:35:02','2026-06-30 15:35:02'),(4,'current_semester','Semester 2','string','academic','Current semester',0,NULL,'2026-06-30 15:35:02','2026-06-30 15:35:02'),(5,'max_login_attempts','5','integer','security','Max failed login attempts before lock',0,NULL,'2026-06-30 15:35:02','2026-06-30 15:35:02'),(6,'session_timeout','3600','integer','security','Session timeout in seconds',0,NULL,'2026-06-30 15:35:02','2026-06-30 15:35:02'),(7,'password_min_length','8','integer','security','Minimum password length',0,NULL,'2026-06-30 15:35:02','2026-06-30 15:35:02'),(8,'enable_audit_logging','1','boolean','audit','Enable audit trail logging',0,NULL,'2026-06-30 15:35:02','2026-06-30 15:35:02'),(9,'enable_email_notifications','1','boolean','notifications','Enable email notifications',0,NULL,'2026-06-30 15:35:02','2026-06-30 15:35:02'),(10,'enable_sms_notifications','0','boolean','notifications','Enable SMS notifications',0,NULL,'2026-06-30 15:35:02','2026-06-30 15:35:02'),(11,'timezone','Africa/Kampala','string','general','System timezone',0,NULL,'2026-06-30 15:35:02','2026-06-30 15:35:02'),(12,'date_format','Y-m-d','string','general','Default date format',0,NULL,'2026-06-30 15:35:02','2026-06-30 15:35:02'),(13,'currency','UGX','string','finance','Default currency',0,NULL,'2026-06-30 15:35:02','2026-06-30 15:35:02'),(14,'institution_email','info@igangaschoolofnursingandmidwifery.ac.ug','string','general','Institution email',0,NULL,'2026-06-30 15:35:02','2026-06-30 15:35:02'),(15,'institution_phone','+256-XXX-XXXXXX','string','general','Institution phone',0,NULL,'2026-06-30 15:35:02','2026-06-30 15:35:02');
/*!40000 ALTER TABLE `system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `task_assignments`
--

DROP TABLE IF EXISTS `task_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `task_assignments` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `assigned_by` int(11) unsigned NOT NULL,
  `assigned_to` int(11) unsigned NOT NULL,
  `priority` enum('low','medium','high','urgent') NOT NULL DEFAULT 'medium',
  `status` enum('pending','in_progress','completed','cancelled','on_hold') NOT NULL DEFAULT 'pending',
  `due_date` date DEFAULT NULL,
  `due_time` time DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `reference_type` varchar(60) DEFAULT NULL,
  `reference_id` int(11) unsigned DEFAULT NULL,
  `reference_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_assigned_to` (`assigned_to`),
  KEY `idx_assigned_by` (`assigned_by`),
  KEY `idx_status` (`status`),
  KEY `idx_priority` (`priority`),
  KEY `idx_due_date` (`due_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `task_assignments`
--

LOCK TABLES `task_assignments` WRITE;
/*!40000 ALTER TABLE `task_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `task_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teacher_guardian`
--

DROP TABLE IF EXISTS `teacher_guardian`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `teacher_guardian` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `teacher_id` int(11) DEFAULT NULL,
  `gname` varchar(200) DEFAULT NULL,
  `gphone` varchar(50) DEFAULT NULL,
  `gemail` varchar(200) DEFAULT NULL,
  `relationship` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tg_teacher` (`teacher_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teacher_guardian`
--

LOCK TABLES `teacher_guardian` WRITE;
/*!40000 ALTER TABLE `teacher_guardian` DISABLE KEYS */;
/*!40000 ALTER TABLE `teacher_guardian` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teachers`
--

DROP TABLE IF EXISTS `teachers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `teaching_announcements`
--

DROP TABLE IF EXISTS `teaching_announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `teaching_announcements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lecturer_id` int(10) unsigned NOT NULL,
  `title` varchar(200) NOT NULL,
  `content` text NOT NULL,
  `target_audience` varchar(100) DEFAULT 'all',
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teaching_announcements`
--

LOCK TABLES `teaching_announcements` WRITE;
/*!40000 ALTER TABLE `teaching_announcements` DISABLE KEYS */;
/*!40000 ALTER TABLE `teaching_announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teaching_assessments`
--

DROP TABLE IF EXISTS `teaching_assessments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `teaching_assessments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `lecturer_id` int(10) unsigned NOT NULL,
  `student_id` int(10) unsigned DEFAULT NULL,
  `course_name` varchar(200) NOT NULL,
  `assessment_type` enum('quiz','assignment','exam','practical','project') NOT NULL DEFAULT 'assignment',
  `title` varchar(200) NOT NULL,
  `total_marks` decimal(6,2) DEFAULT 100.00,
  `marks_obtained` decimal(6,2) DEFAULT NULL,
  `assessment_date` date NOT NULL,
  `comments` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teaching_assessments`
--

LOCK TABLES `teaching_assessments` WRITE;
/*!40000 ALTER TABLE `teaching_assessments` DISABLE KEYS */;
/*!40000 ALTER TABLE `teaching_assessments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `teaching_quality_reviews`
--

DROP TABLE IF EXISTS `teaching_quality_reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `teaching_resources`
--

DROP TABLE IF EXISTS `teaching_resources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `teaching_resources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `resource_type` varchar(100) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_tr_course` (`course_id`),
  KEY `fk_tr_uploader` (`uploaded_by`),
  CONSTRAINT `fk_tr_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tr_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `teaching_resources`
--

LOCK TABLES `teaching_resources` WRITE;
/*!40000 ALTER TABLE `teaching_resources` DISABLE KEYS */;
/*!40000 ALTER TABLE `teaching_resources` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `timetables`
--

DROP TABLE IF EXISTS `timetables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `trainings`
--

DROP TABLE IF EXISTS `trainings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `trainings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `training_type` varchar(100) DEFAULT NULL,
  `provider` varchar(200) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `max_participants` int(11) DEFAULT 50,
  `status` varchar(50) DEFAULT 'Planned',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trainings`
--

LOCK TABLES `trainings` WRITE;
/*!40000 ALTER TABLE `trainings` DISABLE KEYS */;
/*!40000 ALTER TABLE `trainings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transcript_items`
--

DROP TABLE IF EXISTS `transcript_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `transport_fuel_log`
--

DROP TABLE IF EXISTS `transport_fuel_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `transport_fuel_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vehicle_id` int(10) unsigned DEFAULT NULL,
  `driver_id` int(10) unsigned DEFAULT NULL,
  `fuel_date` date NOT NULL,
  `liters` decimal(8,2) NOT NULL DEFAULT 0.00,
  `cost` decimal(10,2) DEFAULT 0.00,
  `odometer_reading` decimal(10,1) DEFAULT NULL,
  `station` varchar(200) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transport_fuel_log`
--

LOCK TABLES `transport_fuel_log` WRITE;
/*!40000 ALTER TABLE `transport_fuel_log` DISABLE KEYS */;
/*!40000 ALTER TABLE `transport_fuel_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transport_routes`
--

DROP TABLE IF EXISTS `transport_routes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `transport_routes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `route_name` varchar(200) NOT NULL,
  `start_location` varchar(200) NOT NULL,
  `end_location` varchar(200) NOT NULL,
  `distance_km` decimal(8,2) DEFAULT 0.00,
  `estimated_duration_minutes` int(10) unsigned DEFAULT 30,
  `route_type` enum('morning','evening','both') NOT NULL DEFAULT 'both',
  `fare_amount` decimal(10,2) DEFAULT 0.00,
  `stops_json` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `dg_approval_status` enum('pending','approved','rejected') DEFAULT 'approved',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transport_routes`
--

LOCK TABLES `transport_routes` WRITE;
/*!40000 ALTER TABLE `transport_routes` DISABLE KEYS */;
INSERT INTO `transport_routes` VALUES (1,'Main Campus - Hospital','Iganga School of Nursing','Iganga Regional Hospital',5.50,20,'both',2000.00,'[\"Main Gate\",\"Town Center\",\"Hospital Road\"]','active','approved',NULL,'2026-07-01 05:58:42','2026-07-01 05:58:42'),(2,'Campus - Town Center','Iganga School of Nursing','Iganga Town Center',3.20,15,'both',1500.00,'[\"Main Gate\",\"Market Square\",\"Town Center\"]','active','approved',NULL,'2026-07-01 05:58:42','2026-07-01 05:58:42'),(3,'Hostel - Campus','Student Hostels','Main Campus',1.50,10,'both',1000.00,'[\"Hostel Block A\",\"Pathway\",\"Main Campus\"]','active','approved',NULL,'2026-07-01 05:58:42','2026-07-01 05:58:42'),(4,'Campus - Clinical Site','Iganga School of Nursing','Budondo Health Centre III',8.00,30,'morning',3000.00,'[\"Main Gate\",\"Highway Junction\",\"Budondo\"]','active','approved',NULL,'2026-07-01 05:58:42','2026-07-01 05:58:42');
/*!40000 ALTER TABLE `transport_routes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transport_student_assignments`
--

DROP TABLE IF EXISTS `transport_student_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `transport_student_assignments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned NOT NULL,
  `student_name` varchar(120) NOT NULL DEFAULT '',
  `registration_number` varchar(50) DEFAULT NULL,
  `route_id` int(10) unsigned DEFAULT NULL,
  `vehicle_id` int(10) unsigned DEFAULT NULL,
  `pickup_point` varchar(200) DEFAULT NULL,
  `dropoff_point` varchar(200) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_student` (`student_id`),
  KEY `idx_route` (`route_id`),
  KEY `idx_vehicle` (`vehicle_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transport_student_assignments`
--

LOCK TABLES `transport_student_assignments` WRITE;
/*!40000 ALTER TABLE `transport_student_assignments` DISABLE KEYS */;
INSERT INTO `transport_student_assignments` VALUES (1,1,'Nambatya Sarah','ISM/2024/001',1,1,'Town Center','Hospital Road','2025/2026','active',NULL,'2026-07-01 05:58:42','2026-07-01 05:58:42'),(2,2,'Nakku Rose','ISM/2024/002',1,1,'Main Gate','Hospital Road','2025/2026','active',NULL,'2026-07-01 05:58:42','2026-07-01 05:58:42'),(3,3,'Namutebi Grace','ISM/2024/003',2,2,'Market Square','Town Center','2025/2026','active',NULL,'2026-07-01 05:58:42','2026-07-01 05:58:42'),(4,4,'Kizza David','ISM/2024/004',2,2,'Main Gate','Town Center','2025/2026','active',NULL,'2026-07-01 05:58:42','2026-07-01 05:58:42'),(5,5,'Ochieng James','ISM/2024/005',3,3,'Hostel Block A','Main Campus','2025/2026','active',NULL,'2026-07-01 05:58:42','2026-07-01 05:58:42'),(6,6,'Tumusiime Frank','ISM/2024/006',4,1,'Main Gate','Budondo','2025/2026','active',NULL,'2026-07-01 05:58:42','2026-07-01 05:58:42'),(7,7,'Apio Christine','ISM/2024/007',3,3,'Hostel Block A','Main Campus','2025/2026','active',NULL,'2026-07-01 05:58:42','2026-07-01 05:58:42'),(8,8,'Otim Samuel','ISM/2024/008',1,1,'Highway Junction','Hospital Road','2025/2026','active',NULL,'2026-07-01 05:58:42','2026-07-01 05:58:42'),(9,1,'Nambatya Sarah','ISM/2024/001',1,1,'Town Center','Hospital Road','2025/2026','active',NULL,'2026-07-01 06:22:11','2026-07-01 06:22:11'),(10,2,'Nakku Rose','ISM/2024/002',1,1,'Main Gate','Hospital Road','2025/2026','active',NULL,'2026-07-01 06:22:11','2026-07-01 06:22:11'),(11,3,'Namutebi Grace','ISM/2024/003',2,2,'Market Square','Town Center','2025/2026','active',NULL,'2026-07-01 06:22:11','2026-07-01 06:22:11'),(12,4,'Kizza David','ISM/2024/004',2,2,'Main Gate','Town Center','2025/2026','active',NULL,'2026-07-01 06:22:11','2026-07-01 06:22:11'),(13,5,'Ochieng James','ISM/2024/005',3,3,'Hostel Block A','Main Campus','2025/2026','active',NULL,'2026-07-01 06:22:11','2026-07-01 06:22:11'),(14,6,'Tumusiime Frank','ISM/2024/006',4,1,'Main Gate','Budondo','2025/2026','active',NULL,'2026-07-01 06:22:11','2026-07-01 06:22:11'),(15,7,'Apio Christine','ISM/2024/007',3,3,'Hostel Block A','Main Campus','2025/2026','active',NULL,'2026-07-01 06:22:11','2026-07-01 06:22:11'),(16,8,'Otim Samuel','ISM/2024/008',1,1,'Highway Junction','Hospital Road','2025/2026','active',NULL,'2026-07-01 06:22:11','2026-07-01 06:22:11');
/*!40000 ALTER TABLE `transport_student_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transport_trips`
--

DROP TABLE IF EXISTS `transport_trips`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `transport_trips` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vehicle_id` int(10) unsigned DEFAULT NULL,
  `driver_id` int(10) unsigned DEFAULT NULL,
  `route_id` int(10) unsigned DEFAULT NULL,
  `route_name` varchar(200) DEFAULT NULL,
  `departure_time` datetime DEFAULT NULL,
  `arrival_time` datetime DEFAULT NULL,
  `passengers_count` int(10) unsigned DEFAULT 0,
  `fuel_cost` decimal(10,2) DEFAULT 0.00,
  `distance_km` decimal(8,2) DEFAULT 0.00,
  `status` enum('scheduled','in_progress','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `dg_approval_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `dg_approved_by` int(10) unsigned DEFAULT NULL,
  `dg_approved_at` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `requested_by` int(10) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `trip_distance` decimal(8,2) DEFAULT 0.00,
  `trip_fare` decimal(10,2) DEFAULT 0.00,
  `student_count` int(10) unsigned DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transport_trips`
--

LOCK TABLES `transport_trips` WRITE;
/*!40000 ALTER TABLE `transport_trips` DISABLE KEYS */;
INSERT INTO `transport_trips` VALUES (1,1,18,1,'Main Campus - Hospital','2026-07-02 07:00:00',NULL,25,45000.00,0.00,'scheduled','pending',NULL,NULL,NULL,18,'Morning clinical rotation trip','2026-07-01 06:22:53',5.50,0.00,0),(2,2,18,2,'Campus - Town Center','2026-07-02 14:00:00',NULL,30,35000.00,0.00,'scheduled','pending',NULL,NULL,NULL,18,'Afternoon student transport','2026-07-01 06:22:53',3.20,0.00,0);
/*!40000 ALTER TABLE `transport_trips` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transport_vehicles`
--

DROP TABLE IF EXISTS `transport_vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `transport_vehicles` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `vehicle_number` varchar(30) NOT NULL,
  `vehicle_type` varchar(50) NOT NULL DEFAULT 'bus',
  `capacity` int(10) unsigned DEFAULT 0,
  `fuel_type` varchar(30) DEFAULT 'diesel',
  `insurance_expiry` date DEFAULT NULL,
  `status` enum('active','maintenance','retired') NOT NULL DEFAULT 'active',
  `dg_approval_status` enum('pending','approved','rejected') DEFAULT 'approved',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transport_vehicles`
--

LOCK TABLES `transport_vehicles` WRITE;
/*!40000 ALTER TABLE `transport_vehicles` DISABLE KEYS */;
/*!40000 ALTER TABLE `transport_vehicles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `trip_logs`
--

DROP TABLE IF EXISTS `trip_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `trip_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle_id` int(11) DEFAULT NULL,
  `driver_id` int(11) DEFAULT NULL,
  `trip_date` date NOT NULL,
  `destination` varchar(200) DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `start_km` int(11) DEFAULT 0,
  `end_km` int(11) DEFAULT 0,
  `fuel_cost` decimal(10,2) DEFAULT 0.00,
  `status` varchar(50) DEFAULT 'Scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_trip_vehicle` (`vehicle_id`),
  KEY `fk_trip_driver` (`driver_id`),
  CONSTRAINT `fk_trip_driver` FOREIGN KEY (`driver_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_trip_vehicle` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `trip_logs`
--

LOCK TABLES `trip_logs` WRITE;
/*!40000 ALTER TABLE `trip_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `trip_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ura_reports`
--

DROP TABLE IF EXISTS `ura_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ura_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `report_name` varchar(200) NOT NULL,
  `name` varchar(200) DEFAULT NULL,
  `tax_period` varchar(50) DEFAULT NULL,
  `period` varchar(50) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT 0.00,
  `tax_amount` decimal(15,2) DEFAULT 0.00,
  `status` enum('pending','filed','submitted') DEFAULT 'pending',
  `filed_date` date DEFAULT NULL,
  `report_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ura_reports`
--

LOCK TABLES `ura_reports` WRITE;
/*!40000 ALTER TABLE `ura_reports` DISABLE KEYS */;
INSERT INTO `ura_reports` VALUES (1,'PAYE Return - January 2025',NULL,'2025-01',NULL,80000000.00,9600000.00,'filed','2025-02-10',NULL,NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(2,'NSSF Return - January 2025',NULL,'2025-01',NULL,80000000.00,8000000.00,'filed','2025-02-10',NULL,NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47'),(3,'Withholding Tax - Q1 2025',NULL,'2025-Q1',NULL,200000000.00,12000000.00,'pending',NULL,NULL,NULL,NULL,'2026-07-03 05:16:47','2026-07-03 05:16:47');
/*!40000 ALTER TABLE `ura_reports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_sessions`
--

DROP TABLE IF EXISTS `user_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = utf8mb4 */;
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
-- Table structure for table `vehicles`
--

DROP TABLE IF EXISTS `vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `vehicle_name` varchar(200) NOT NULL,
  `license_plate` varchar(50) NOT NULL,
  `vehicle_type` enum('Bus','Minibus','Van','Car','Ambulance','Truck','Other') DEFAULT 'Car',
  `fuel_type` enum('Petrol','Diesel','Electric','Hybrid') DEFAULT 'Diesel',
  `capacity` int(11) DEFAULT 0,
  `manufacturer` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `year` year(4) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `insurance_expiry` date DEFAULT NULL,
  `last_service_date` date DEFAULT NULL,
  `next_service_date` date DEFAULT NULL,
  `status` enum('Active','Maintenance','Out of Service','Retired') DEFAULT 'Active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `license_plate` (`license_plate`),
  KEY `idx_vehicles_status` (`status`),
  KEY `idx_vehicles_type` (`vehicle_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicles`
--

LOCK TABLES `vehicles` WRITE;
/*!40000 ALTER TABLE `vehicles` DISABLE KEYS */;
/*!40000 ALTER TABLE `vehicles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `visitor_logs`
--

DROP TABLE IF EXISTS `visitor_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `visitor_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visitor_name` varchar(200) NOT NULL,
  `visitor_phone` varchar(50) DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `person_to_visit` varchar(200) DEFAULT NULL,
  `check_in_time` datetime DEFAULT current_timestamp(),
  `check_out_time` datetime DEFAULT NULL,
  `badge_number` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `visitor_logs`
--

LOCK TABLES `visitor_logs` WRITE;
/*!40000 ALTER TABLE `visitor_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `visitor_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `website_submission_logs`
--

DROP TABLE IF EXISTS `website_submission_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `website_submission_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `submission_type` enum('contact','donation','volunteer','application') NOT NULL,
  `submission_id` int(10) unsigned NOT NULL COMMENT 'ID from website_db table',
  `action_by` int(10) unsigned NOT NULL DEFAULT 0,
  `action_type` varchar(50) NOT NULL DEFAULT 'viewed' COMMENT 'viewed,approved,resolved,emailed',
  `action_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_type_id` (`submission_type`,`submission_id`),
  KEY `idx_action_by` (`action_by`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `website_submission_logs`
--

LOCK TABLES `website_submission_logs` WRITE;
/*!40000 ALTER TABLE `website_submission_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `website_submission_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `welfare_actions`
--

DROP TABLE IF EXISTS `welfare_actions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `welfare_actions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `case_id` int(10) unsigned NOT NULL,
  `action_by` int(10) unsigned DEFAULT NULL,
  `action_by_name` varchar(120) DEFAULT NULL,
  `action_type` varchar(50) NOT NULL DEFAULT 'comment',
  `notes` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_case` (`case_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `welfare_actions`
--

LOCK TABLES `welfare_actions` WRITE;
/*!40000 ALTER TABLE `welfare_actions` DISABLE KEYS */;
/*!40000 ALTER TABLE `welfare_actions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `welfare_cases`
--

DROP TABLE IF EXISTS `welfare_cases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `welfare_cases` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned DEFAULT NULL,
  `student_name` varchar(120) NOT NULL,
  `case_type` enum('health','discipline','academic','personal','housing','other') NOT NULL DEFAULT 'personal',
  `description` text NOT NULL,
  `reported_by` int(10) unsigned DEFAULT NULL,
  `reported_by_name` varchar(120) DEFAULT NULL,
  `assigned_to` int(10) unsigned DEFAULT NULL,
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `status` enum('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  `resolution_notes` text DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `welfare_cases`
--

LOCK TABLES `welfare_cases` WRITE;
/*!40000 ALTER TABLE `welfare_cases` DISABLE KEYS */;
/*!40000 ALTER TABLE `welfare_cases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'igangaschoolofl_staffs_db'
--

--
-- Dumping routines for database 'igangaschoolofl_staffs_db'
--
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_dashboard_statistics` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
DELIMITER ;;
CREATE DEFINER=`igangaschoolofl_staffs_db`@`localhost` PROCEDURE `get_dashboard_statistics`(IN p_user_id INT, IN p_role VARCHAR(100))
BEGIN
    SELECT
        (SELECT COUNT(*) FROM igangaschoolofl_staffs_db.staff WHERE status='Active') AS total_staff,
        (SELECT COUNT(*) FROM igangaschoolofl_students_db.students WHERE status='Active') AS total_students,
        0 AS pending_applications,
        2 AS active_programs,
        0 AS total_revenue,
        0 AS total_expenses;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `get_staff_performance_summary` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
DELIMITER ;;
CREATE DEFINER=`igangaschoolofl_staffs_db`@`localhost` PROCEDURE `get_staff_performance_summary`(IN p_user_id INT)
BEGIN
    SELECT s.id, s.full_name, sr.role_name, s.department, s.status,
           0 AS performance_score, 'Good' AS rating
    FROM staff s
    LEFT JOIN staff_roles sr ON s.role_id = sr.id
    WHERE s.status = 'Active'
    ORDER BY s.full_name
    LIMIT 20;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Final view structure for view `fee_payments`
--

/*!50001 DROP VIEW IF EXISTS `fee_payments`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`igangaschoolofl_staffs_db`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `fee_payments` AS select `igangaschoolofl_students_db`.`payments`.`id` AS `id`,`igangaschoolofl_students_db`.`payments`.`student_id` AS `student_id`,`igangaschoolofl_students_db`.`payments`.`invoice_id` AS `fee_account_id`,`igangaschoolofl_students_db`.`payments`.`amount_received` AS `amount_paid`,`igangaschoolofl_students_db`.`payments`.`payment_method` AS `payment_method`,`igangaschoolofl_students_db`.`payments`.`payment_reference` AS `receipt_number`,`igangaschoolofl_students_db`.`payments`.`status` AS `status`,`igangaschoolofl_students_db`.`payments`.`payment_date` AS `payment_date`,`igangaschoolofl_students_db`.`payments`.`notes` AS `notes`,`igangaschoolofl_students_db`.`payments`.`received_by` AS `processed_by`,`igangaschoolofl_students_db`.`payments`.`created_at` AS `created_at`,`igangaschoolofl_students_db`.`payments`.`updated_at` AS `updated_at` from `igangaschoolofl_students_db`.`payments` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-03  6:54:31
