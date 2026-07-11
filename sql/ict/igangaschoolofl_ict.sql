/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.6.23-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: igangaschool_ict
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
-- Table structure for table `academic_programs`
--

DROP TABLE IF EXISTS `academic_programs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `academic_programs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `program_code` varchar(20) NOT NULL,
  `program_name` varchar(255) NOT NULL,
  `program_type` enum('Certificate','Diploma','Degree','Short Course') NOT NULL DEFAULT 'Diploma',
  `department` varchar(100) DEFAULT NULL,
  `duration_years` decimal(3,1) NOT NULL DEFAULT 2.0,
  `total_fee` decimal(14,2) NOT NULL DEFAULT 0.00,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `program_code` (`program_code`),
  KEY `idx_prog_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `academic_programs`
--

LOCK TABLES `academic_programs` WRITE;
/*!40000 ALTER TABLE `academic_programs` DISABLE KEYS */;
/*!40000 ALTER TABLE `academic_programs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admission_activity_logs`
--

DROP TABLE IF EXISTS `admission_activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admission_activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `applicant_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_log_app` (`applicant_id`),
  KEY `idx_log_user` (`user_id`),
  KEY `idx_log_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admission_activity_logs`
--

LOCK TABLES `admission_activity_logs` WRITE;
/*!40000 ALTER TABLE `admission_activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `admission_activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admission_communications`
--

DROP TABLE IF EXISTS `admission_communications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admission_communications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `applicant_id` int(11) NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `communication_type` enum('Email','SMS','Portal','WhatsApp','Internal Note') NOT NULL DEFAULT 'Portal',
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('Sent','Delivered','Read','Failed') DEFAULT 'Sent',
  `sent_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_com_app` (`applicant_id`),
  KEY `idx_com_type` (`communication_type`),
  CONSTRAINT `admission_communications_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admission_communications`
--

LOCK TABLES `admission_communications` WRITE;
/*!40000 ALTER TABLE `admission_communications` DISABLE KEYS */;
/*!40000 ALTER TABLE `admission_communications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admission_decisions`
--

DROP TABLE IF EXISTS `admission_decisions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admission_decisions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `applicant_id` int(11) NOT NULL,
  `decision` enum('Approved','Rejected','Deferred','Waitlisted') NOT NULL,
  `decision_reason` text DEFAULT NULL,
  `decided_by` int(11) DEFAULT NULL,
  `decided_at` timestamp NULL DEFAULT NULL,
  `notified_applicant` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_dec_app` (`applicant_id`),
  CONSTRAINT `admission_decisions_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admission_decisions`
--

LOCK TABLES `admission_decisions` WRITE;
/*!40000 ALTER TABLE `admission_decisions` DISABLE KEYS */;
/*!40000 ALTER TABLE `admission_decisions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admission_interviews`
--

DROP TABLE IF EXISTS `admission_interviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `admission_interviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `applicant_id` int(11) NOT NULL,
  `interviewer_id` int(11) DEFAULT NULL,
  `interview_date` datetime NOT NULL,
  `interview_mode` enum('In-Person','Online','Phone') NOT NULL DEFAULT 'In-Person',
  `interview_link` varchar(500) DEFAULT NULL,
  `interview_score` decimal(5,2) DEFAULT NULL,
  `interview_outcome` enum('Pass','Fail','Pending','Reschedule') DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `recommendation` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_int_app` (`applicant_id`),
  KEY `idx_int_date` (`interview_date`),
  CONSTRAINT `admission_interviews_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admission_interviews`
--

LOCK TABLES `admission_interviews` WRITE;
/*!40000 ALTER TABLE `admission_interviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `admission_interviews` ENABLE KEYS */;
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
  `user_id` int(11) DEFAULT NULL,
  `type` enum('info','success','warning','danger') NOT NULL DEFAULT 'info',
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `link` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_n_app` (`applicant_id`),
  KEY `idx_n_user` (`user_id`),
  KEY `idx_n_read` (`is_read`)
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
  `requirement_name` varchar(255) NOT NULL,
  `type` enum('Document','Certificate','ID','Photo','Form','Other') NOT NULL DEFAULT 'Document',
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_mandatory` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_req_active` (`is_active`),
  KEY `idx_req_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admission_requirements`
--

LOCK TABLES `admission_requirements` WRITE;
/*!40000 ALTER TABLE `admission_requirements` DISABLE KEYS */;
/*!40000 ALTER TABLE `admission_requirements` ENABLE KEYS */;
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
  `status` enum('Not Submitted','Pending','Submitted','Verified','Rejected','Missing','Received','Not Yet Given') NOT NULL DEFAULT 'Not Submitted',
  `remarks` text DEFAULT NULL COMMENT 'System/admin remarks',
  `director_notes` text DEFAULT NULL COMMENT 'Admission Director private notes',
  `submitted_by` int(11) DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_app_req` (`applicant_id`,`requirement_id`),
  KEY `idx_ars_status` (`status`),
  KEY `requirement_id` (`requirement_id`),
  CONSTRAINT `applicant_requirement_status_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE,
  CONSTRAINT `applicant_requirement_status_ibfk_2` FOREIGN KEY (`requirement_id`) REFERENCES `admission_requirements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `applicants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `application_number` varchar(30) NOT NULL,
  `student_number` varchar(50) DEFAULT NULL,
  `registration_number` varchar(50) DEFAULT NULL,
  `portal_username` varchar(100) DEFAULT NULL,
  `portal_password_hash` varchar(255) DEFAULT NULL,
  `full_name` varchar(255) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `surname` varchar(100) DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `alternative_phone` varchar(20) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT 'Ugandan',
  `district` varchar(100) DEFAULT NULL,
  `county` varchar(100) DEFAULT NULL,
  `religion` varchar(50) DEFAULT NULL,
  `marital_status` enum('Single','Married','Divorced','Widowed') DEFAULT 'Single',
  `address` text DEFAULT NULL,
  `photo_path` varchar(500) DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `intake` varchar(50) DEFAULT NULL,
  `intake_id` int(11) DEFAULT NULL,
  `application_source` enum('Online','Manual','Walk-in','Referral','Other') DEFAULT 'Online',
  `status` enum('New','Under Review','Waiting for Documents','Requirements Verified','Interview Scheduled','Approved','Rejected','Registered','Withdrawn') NOT NULL DEFAULT 'New',
  `rejection_reason` text DEFAULT NULL,
  `previous_education` text DEFAULT NULL,
  `previous_institution` varchar(255) DEFAULT NULL,
  `previous_qualification` varchar(255) DEFAULT NULL,
  `guardian_name` varchar(200) DEFAULT NULL,
  `guardian_phone` varchar(20) DEFAULT NULL,
  `guardian_email` varchar(100) DEFAULT NULL,
  `guardian_relationship` varchar(50) DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `registered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `application_number` (`application_number`),
  UNIQUE KEY `student_number` (`student_number`),
  KEY `idx_app_status` (`status`),
  KEY `idx_app_program` (`program_id`),
  KEY `idx_app_intake` (`intake`),
  KEY `idx_app_name` (`full_name`),
  KEY `idx_app_phone` (`phone`),
  KEY `idx_app_email` (`email`),
  KEY `idx_app_created` (`created_at`),
  KEY `intake_id` (`intake_id`),
  CONSTRAINT `applicants_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `academic_programs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `applicants_ibfk_2` FOREIGN KEY (`intake_id`) REFERENCES `intakes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `applicants`
--

LOCK TABLES `applicants` WRITE;
/*!40000 ALTER TABLE `applicants` DISABLE KEYS */;
/*!40000 ALTER TABLE `applicants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `complaint_submissions`
--

DROP TABLE IF EXISTS `complaint_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `complaint_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `complainant_name` varchar(255) NOT NULL,
  `complainant_email` varchar(255) NOT NULL,
  `complainant_phone` varchar(20) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `severity` varchar(50) DEFAULT 'medium' COMMENT 'low, medium, high, urgent',
  `status` varchar(50) DEFAULT 'filed' COMMENT 'filed, acknowledged, investigating, resolved, closed',
  `assigned_to` int(11) DEFAULT NULL,
  `resolution` longtext DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_email` (`complainant_email`),
  KEY `idx_status` (`status`),
  KEY `idx_severity` (`severity`),
  KEY `idx_department` (`department`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `complaint_submissions`
--

LOCK TABLES `complaint_submissions` WRITE;
/*!40000 ALTER TABLE `complaint_submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `complaint_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `computer_repairs`
--

DROP TABLE IF EXISTS `computer_repairs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `computer_repairs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `repair_number` varchar(50) NOT NULL,
  `computer_id` int(11) DEFAULT NULL,
  `equipment_id` int(11) DEFAULT NULL,
  `reported_by` varchar(200) NOT NULL,
  `reporter_type` enum('student','staff','lecturer') DEFAULT 'student',
  `reporter_id` int(11) DEFAULT NULL,
  `issue_description` text NOT NULL,
  `issue_category` enum('hardware','software','network','other') DEFAULT 'other',
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `assigned_technician` varchar(200) DEFAULT NULL,
  `diagnosis` text DEFAULT NULL,
  `resolution` text DEFAULT NULL,
  `parts_replaced` text DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT 0.00,
  `status` enum('reported','diagnosed','in_progress','completed','closed','cancelled') DEFAULT 'reported',
  `reported_date` timestamp NULL DEFAULT current_timestamp(),
  `diagnosed_date` timestamp NULL DEFAULT NULL,
  `completed_date` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `repair_number` (`repair_number`),
  KEY `computer_id` (`computer_id`),
  KEY `equipment_id` (`equipment_id`),
  KEY `status` (`status`),
  KEY `priority` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `computer_repairs`
--

LOCK TABLES `computer_repairs` WRITE;
/*!40000 ALTER TABLE `computer_repairs` DISABLE KEYS */;
/*!40000 ALTER TABLE `computer_repairs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `course_catalog`
--

DROP TABLE IF EXISTS `course_catalog`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `course_catalog` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(255) NOT NULL,
  `credit_hours` decimal(4,1) DEFAULT 0.0,
  `is_compulsory` tinyint(1) NOT NULL DEFAULT 0,
  `department` varchar(100) DEFAULT NULL,
  `level` varchar(50) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `course_code` (`course_code`),
  KEY `idx_cc_level` (`level`),
  KEY `idx_cc_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `course_catalog`
--

LOCK TABLES `course_catalog` WRITE;
/*!40000 ALTER TABLE `course_catalog` DISABLE KEYS */;
/*!40000 ALTER TABLE `course_catalog` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `daily_sick_records`
--

DROP TABLE IF EXISTS `daily_sick_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `daily_sick_records` (
  `id` int(11) NOT NULL,
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
  `referred_to` varchar(255) DEFAULT NULL,
  `attended_by` varchar(200) DEFAULT NULL,
  `visit_date` date NOT NULL,
  `visit_time` time DEFAULT NULL,
  `follow_up_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
-- Table structure for table `feedback_submissions`
--

DROP TABLE IF EXISTS `feedback_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `feedback_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `rating` int(11) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `feedback` longtext NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'received',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_rating` (`rating`),
  KEY `idx_category` (`category`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feedback_submissions`
--

LOCK TABLES `feedback_submissions` WRITE;
/*!40000 ALTER TABLE `feedback_submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `feedback_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `form_submissions`
--

DROP TABLE IF EXISTS `form_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `form_submissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `form_type` varchar(50) NOT NULL COMMENT 'application, contact, feedback, complaint, volunteer',
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` longtext DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending' COMMENT 'pending, read, responded, closed',
  `assigned_to` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_type` (`form_type`),
  KEY `idx_email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `form_submissions`
--

LOCK TABLES `form_submissions` WRITE;
/*!40000 ALTER TABLE `form_submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `form_submissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ict_asset_assignments`
--

DROP TABLE IF EXISTS `ict_asset_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ict_asset_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) NOT NULL,
  `assigned_to_staff_id` int(11) DEFAULT NULL,
  `assigned_department` varchar(200) DEFAULT NULL,
  `assignment_date` date NOT NULL,
  `expected_return_date` date DEFAULT NULL,
  `actual_return_date` date DEFAULT NULL,
  `assignment_notes` text DEFAULT NULL,
  `condition_at_assignment` varchar(200) DEFAULT NULL,
  `condition_at_return` varchar(200) DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `status` enum('active','returned','transferred') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `asset_id` (`asset_id`),
  KEY `assigned_to_staff_id` (`assigned_to_staff_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ict_asset_assignments`
--

LOCK TABLES `ict_asset_assignments` WRITE;
/*!40000 ALTER TABLE `ict_asset_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `ict_asset_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ict_asset_categories`
--

DROP TABLE IF EXISTS `ict_asset_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ict_asset_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_name` (`category_name`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ict_asset_categories`
--

LOCK TABLES `ict_asset_categories` WRITE;
/*!40000 ALTER TABLE `ict_asset_categories` DISABLE KEYS */;
INSERT INTO `ict_asset_categories` VALUES (1,'Desktop Computers','Desktop workstations and PCs',NULL,'2026-06-26 19:25:21','2026-06-26 19:25:21'),(2,'Laptops','Portable notebook computers',NULL,'2026-06-26 19:25:21','2026-06-26 19:25:21'),(3,'Servers','Physical and virtual server systems',NULL,'2026-06-26 19:25:21','2026-06-26 19:25:21'),(4,'Printers','All printer types including multi-function',NULL,'2026-06-26 19:25:21','2026-06-26 19:25:21'),(5,'Scanners','Document and photo scanners',NULL,'2026-06-26 19:25:21','2026-06-26 19:25:21'),(6,'Projectors','Multimedia projectors and displays',NULL,'2026-06-26 19:25:21','2026-06-26 19:25:21'),(7,'Network Equipment','Routers, switches, access points',NULL,'2026-06-26 19:25:21','2026-06-26 19:25:21'),(8,'UPS Systems','Uninterruptible power supplies',NULL,'2026-06-26 19:25:21','2026-06-26 19:25:21'),(9,'Software','Licensed software packages',NULL,'2026-06-26 19:25:21','2026-06-26 19:25:21'),(10,'Accessories','Peripherals and accessories',NULL,'2026-06-26 19:25:21','2026-06-26 19:25:21');
/*!40000 ALTER TABLE `ict_asset_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ict_asset_maintenance`
--

DROP TABLE IF EXISTS `ict_asset_maintenance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ict_asset_maintenance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) NOT NULL,
  `maintenance_type` enum('routine','repair','upgrade','cleaning','other') DEFAULT 'routine',
  `description` text NOT NULL,
  `performed_by` varchar(200) DEFAULT NULL,
  `cost` decimal(15,2) DEFAULT 0.00,
  `parts_replaced` text DEFAULT NULL,
  `service_provider` varchar(200) DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
  `scheduled_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `asset_id` (`asset_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ict_asset_maintenance`
--

LOCK TABLES `ict_asset_maintenance` WRITE;
/*!40000 ALTER TABLE `ict_asset_maintenance` DISABLE KEYS */;
/*!40000 ALTER TABLE `ict_asset_maintenance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ict_asset_warranty`
--

DROP TABLE IF EXISTS `ict_asset_warranty`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ict_asset_warranty` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_id` int(11) NOT NULL,
  `warranty_provider` varchar(200) DEFAULT NULL,
  `warranty_type` enum('standard','extended','onsite','carry_in') DEFAULT 'standard',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `coverage_details` text DEFAULT NULL,
  `contact_phone` varchar(50) DEFAULT NULL,
  `contact_email` varchar(100) DEFAULT NULL,
  `status` enum('active','expired','claimed') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `asset_id` (`asset_id`),
  KEY `status` (`status`),
  KEY `end_date` (`end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ict_asset_warranty`
--

LOCK TABLES `ict_asset_warranty` WRITE;
/*!40000 ALTER TABLE `ict_asset_warranty` DISABLE KEYS */;
/*!40000 ALTER TABLE `ict_asset_warranty` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ict_assets`
--

DROP TABLE IF EXISTS `ict_assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ict_assets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_number` varchar(100) NOT NULL,
  `barcode` varchar(255) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `serial_number` varchar(255) DEFAULT NULL,
  `asset_name` varchar(200) NOT NULL,
  `asset_type` enum('computer','printer','scanner','projector','network','server','ups','software','accessory','other') DEFAULT 'other',
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `current_status` enum('active','in_maintenance','retired','transferred') DEFAULT 'active',
  `assigned_staff_id` int(11) DEFAULT NULL,
  `assigned_department` varchar(200) DEFAULT NULL,
  `current_location` varchar(255) DEFAULT NULL,
  `purchase_cost` decimal(15,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `asset_number` (`asset_number`),
  KEY `asset_type` (`asset_type`),
  KEY `current_status` (`current_status`),
  KEY `assigned_staff_id` (`assigned_staff_id`),
  KEY `warranty_expiry` (`warranty_expiry`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ict_assets`
--

LOCK TABLES `ict_assets` WRITE;
/*!40000 ALTER TABLE `ict_assets` DISABLE KEYS */;
/*!40000 ALTER TABLE `ict_assets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ict_audit_logs`
--

DROP TABLE IF EXISTS `ict_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ict_audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `resource_type` varchar(100) DEFAULT NULL,
  `resource_id` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `resource_type` (`resource_type`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ict_audit_logs`
--

LOCK TABLES `ict_audit_logs` WRITE;
/*!40000 ALTER TABLE `ict_audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `ict_audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ict_backup_logs`
--

DROP TABLE IF EXISTS `ict_backup_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ict_backup_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `backup_id` int(11) DEFAULT NULL,
  `log_message` text NOT NULL,
  `log_level` enum('info','warning','error') DEFAULT 'info',
  `logged_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `backup_id` (`backup_id`),
  KEY `logged_at` (`logged_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ict_backup_logs`
--

LOCK TABLES `ict_backup_logs` WRITE;
/*!40000 ALTER TABLE `ict_backup_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `ict_backup_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ict_device_categories`
--

DROP TABLE IF EXISTS `ict_device_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ict_device_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(200) NOT NULL,
  `device_type` enum('computer','printer','scanner','projector','network','server','ups','accessory','other') DEFAULT 'other',
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ict_device_categories`
--

LOCK TABLES `ict_device_categories` WRITE;
/*!40000 ALTER TABLE `ict_device_categories` DISABLE KEYS */;
INSERT INTO `ict_device_categories` VALUES (1,'Desktop Computers','computer','Desktop workstations','2026-06-26 19:25:21','2026-06-26 19:25:21'),(2,'Laptops','computer','Portable computers','2026-06-26 19:25:21','2026-06-26 19:25:21'),(3,'Network Printers','printer','Network-connected printers','2026-06-26 19:25:21','2026-06-26 19:25:21'),(4,'Scanners','scanner','Document scanners','2026-06-26 19:25:21','2026-06-26 19:25:21'),(5,'Projectors','projector','Multimedia projectors','2026-06-26 19:25:21','2026-06-26 19:25:21'),(6,'Network Switches','network','Managed and unmanaged switches','2026-06-26 19:25:21','2026-06-26 19:25:21'),(7,'Routers','network','Network routers','2026-06-26 19:25:21','2026-06-26 19:25:21'),(8,'Access Points','network','Wireless access points','2026-06-26 19:25:21','2026-06-26 19:25:21'),(9,'Servers','server','Server systems','2026-06-26 19:25:21','2026-06-26 19:25:21'),(10,'UPS','ups','Power backup units','2026-06-26 19:25:21','2026-06-26 19:25:21');
/*!40000 ALTER TABLE `ict_device_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ict_failed_logins`
--

DROP TABLE IF EXISTS `ict_failed_logins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ict_failed_logins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `attempted_at` timestamp NULL DEFAULT current_timestamp(),
  `reason` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `username` (`username`),
  KEY `ip_address` (`ip_address`),
  KEY `attempted_at` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ict_failed_logins`
--

LOCK TABLES `ict_failed_logins` WRITE;
/*!40000 ALTER TABLE `ict_failed_logins` DISABLE KEYS */;
/*!40000 ALTER TABLE `ict_failed_logins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ict_login_sessions`
--

DROP TABLE IF EXISTS `ict_login_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ict_login_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `user_type` enum('staff','student') DEFAULT 'staff',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `login_at` timestamp NULL DEFAULT current_timestamp(),
  `last_activity` timestamp NULL DEFAULT NULL,
  `logout_at` timestamp NULL DEFAULT NULL,
  `session_duration_sec` int(11) DEFAULT 0,
  `status` enum('active','expired','terminated') DEFAULT 'active',
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `login_at` (`login_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ict_login_sessions`
--

LOCK TABLES `ict_login_sessions` WRITE;
/*!40000 ALTER TABLE `ict_login_sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `ict_login_sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ict_module_permissions`
--

DROP TABLE IF EXISTS `ict_module_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ict_module_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_name` varchar(100) NOT NULL,
  `role_keyword` varchar(50) NOT NULL,
  `can_view` tinyint(1) DEFAULT 1,
  `can_create` tinyint(1) DEFAULT 0,
  `can_edit` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0,
  `can_approve` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `module_role` (`module_name`,`role_keyword`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ict_module_permissions`
--

LOCK TABLES `ict_module_permissions` WRITE;
/*!40000 ALTER TABLE `ict_module_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `ict_module_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ict_network_logs`
--

DROP TABLE IF EXISTS `ict_network_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ict_network_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `device_id` int(11) DEFAULT NULL,
  `log_type` enum('status_change','error','performance','security','config_change') DEFAULT 'status_change',
  `message` text NOT NULL,
  `severity` enum('info','warning','error','critical') DEFAULT 'info',
  `logged_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `device_id` (`device_id`),
  KEY `log_type` (`log_type`),
  KEY `severity` (`severity`),
  KEY `logged_at` (`logged_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ict_network_logs`
--

LOCK TABLES `ict_network_logs` WRITE;
/*!40000 ALTER TABLE `ict_network_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `ict_network_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ict_security_logs`
--

DROP TABLE IF EXISTS `ict_security_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ict_security_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `event_type` enum('login','logout','failed_login','permission_change','account_lock','password_change','user_create','user_delete','settings_change','other') NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `severity` enum('info','warning','critical') DEFAULT 'info',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `event_type` (`event_type`),
  KEY `user_id` (`user_id`),
  KEY `created_at` (`created_at`),
  KEY `ip_address` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ict_security_logs`
--

LOCK TABLES `ict_security_logs` WRITE;
/*!40000 ALTER TABLE `ict_security_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `ict_security_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ict_servers`
--

DROP TABLE IF EXISTS `ict_servers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ict_servers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server_name` varchar(200) NOT NULL,
  `hostname` varchar(200) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `server_type` enum('physical','virtual','cloud') DEFAULT 'physical',
  `os` varchar(100) DEFAULT NULL,
  `os_version` varchar(100) DEFAULT NULL,
  `cpu_cores` int(11) DEFAULT 0,
  `ram_gb` int(11) DEFAULT 0,
  `storage_gb` int(11) DEFAULT 0,
  `purpose` text DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `status` enum('online','offline','maintenance','decommissioned') DEFAULT 'online',
  `uptime_hours` int(11) DEFAULT 0,
  `last_reboot` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `server_name` (`server_name`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ict_servers`
--

LOCK TABLES `ict_servers` WRITE;
/*!40000 ALTER TABLE `ict_servers` DISABLE KEYS */;
/*!40000 ALTER TABLE `ict_servers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ict_system_alerts`
--

DROP TABLE IF EXISTS `ict_system_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ict_system_alerts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `alert_type` enum('system','security','backup','performance','network','storage') NOT NULL,
  `severity` enum('info','warning','critical') DEFAULT 'info',
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `acknowledged_by` int(11) DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `status` enum('active','acknowledged','resolved') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `alert_type` (`alert_type`),
  KEY `severity` (`severity`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ict_system_alerts`
--

LOCK TABLES `ict_system_alerts` WRITE;
/*!40000 ALTER TABLE `ict_system_alerts` DISABLE KEYS */;
/*!40000 ALTER TABLE `ict_system_alerts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ict_system_backups`
--

DROP TABLE IF EXISTS `ict_system_backups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ict_system_backups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `backup_name` varchar(200) NOT NULL,
  `backup_type` enum('database','file','full','incremental') DEFAULT 'database',
  `target_database` varchar(100) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `file_size_mb` decimal(15,2) DEFAULT 0.00,
  `checksum` varchar(64) DEFAULT NULL,
  `status` enum('running','completed','failed','verified') DEFAULT 'running',
  `initiated_by` int(11) DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `backup_type` (`backup_type`),
  KEY `status` (`status`),
  KEY `started_at` (`started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ict_system_backups`
--

LOCK TABLES `ict_system_backups` WRITE;
/*!40000 ALTER TABLE `ict_system_backups` DISABLE KEYS */;
/*!40000 ALTER TABLE `ict_system_backups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ict_system_health`
--

DROP TABLE IF EXISTS `ict_system_health`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ict_system_health` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `check_type` enum('cpu','memory','disk','network','database','service') NOT NULL,
  `check_name` varchar(200) DEFAULT NULL,
  `status` enum('healthy','warning','critical','unknown') DEFAULT 'healthy',
  `value` varchar(255) DEFAULT NULL,
  `threshold` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `checked_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `check_type` (`check_type`),
  KEY `status` (`status`),
  KEY `checked_at` (`checked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ict_system_health`
--

LOCK TABLES `ict_system_health` WRITE;
/*!40000 ALTER TABLE `ict_system_health` DISABLE KEYS */;
/*!40000 ALTER TABLE `ict_system_health` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ict_system_notifications`
--

DROP TABLE IF EXISTS `ict_system_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ict_system_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `notification_type` enum('info','warning','critical','success') DEFAULT 'info',
  `category` varchar(100) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `is_dismissed` tinyint(1) DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `notification_type` (`notification_type`),
  KEY `is_read` (`is_read`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ict_system_notifications`
--

LOCK TABLES `ict_system_notifications` WRITE;
/*!40000 ALTER TABLE `ict_system_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `ict_system_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ict_system_settings`
--

DROP TABLE IF EXISTS `ict_system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ict_system_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `setting_group` varchar(100) DEFAULT 'general',
  `description` text DEFAULT NULL,
  `is_encrypted` tinyint(1) DEFAULT 0,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`),
  KEY `setting_group` (`setting_group`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ict_system_settings`
--

LOCK TABLES `ict_system_settings` WRITE;
/*!40000 ALTER TABLE `ict_system_settings` DISABLE KEYS */;
INSERT INTO `ict_system_settings` VALUES (1,'session_timeout_minutes','30','security','User session timeout in minutes',0,NULL,'2026-06-26 19:25:21','2026-06-26 19:25:21'),(2,'max_login_attempts','5','security','Maximum failed login attempts before lockout',0,NULL,'2026-06-26 19:25:21','2026-06-26 19:25:21'),(3,'lockout_duration_minutes','15','security','Account lockout duration in minutes',0,NULL,'2026-06-26 19:25:21','2026-06-26 19:25:21'),(4,'password_min_length','8','security','Minimum password length',0,NULL,'2026-06-26 19:25:21','2026-06-26 19:25:21'),(5,'backup_retention_days','30','backup','Days to retain backups',0,NULL,'2026-06-26 19:25:21','2026-06-26 19:25:21'),(6,'auto_backup_enabled','true','backup','Enable automatic scheduled backups',0,NULL,'2026-06-26 19:25:21','2026-06-26 19:25:21'),(7,'backup_time','02:00','backup','Scheduled backup time',0,NULL,'2026-06-26 19:25:21','2026-06-26 19:25:21'),(8,'system_health_interval','5','monitoring','System health check interval in minutes',0,NULL,'2026-06-26 19:25:21','2026-06-26 19:25:21'),(9,'notify_critical_alerts','true','alerts','Send notifications for critical alerts',0,NULL,'2026-06-26 19:25:21','2026-06-26 19:25:21'),(10,'maintenance_mode','false','system','System maintenance mode flag',0,NULL,'2026-06-26 19:25:21','2026-06-26 19:25:21');
/*!40000 ALTER TABLE `ict_system_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ict_wifi_devices`
--

DROP TABLE IF EXISTS `ict_wifi_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `ict_wifi_devices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `device_name` varchar(200) NOT NULL,
  `ssid` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `mac_address` varchar(17) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `firmware_version` varchar(50) DEFAULT NULL,
  `status` enum('online','offline','maintenance') DEFAULT 'online',
  `connected_clients` int(11) DEFAULT 0,
  `max_clients` int(11) DEFAULT 50,
  `band` enum('2.4ghz','5ghz','dual') DEFAULT 'dual',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ict_wifi_devices`
--

LOCK TABLES `ict_wifi_devices` WRITE;
/*!40000 ALTER TABLE `ict_wifi_devices` DISABLE KEYS */;
/*!40000 ALTER TABLE `ict_wifi_devices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `id_card_print_history`
--

DROP TABLE IF EXISTS `id_card_print_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `id_card_print_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `card_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `print_type` enum('new','reprint','bulk') DEFAULT 'new',
  `reason` varchar(200) DEFAULT NULL,
  `printed_by` int(11) DEFAULT NULL,
  `print_date` timestamp NULL DEFAULT current_timestamp(),
  `copies` int(11) DEFAULT 1,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `card_id` (`card_id`),
  KEY `student_id` (`student_id`),
  KEY `printed_by` (`printed_by`),
  KEY `print_date` (`print_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `id_card_print_history`
--

LOCK TABLES `id_card_print_history` WRITE;
/*!40000 ALTER TABLE `id_card_print_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `id_card_print_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `id_card_replacements`
--

DROP TABLE IF EXISTS `id_card_replacements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `id_card_replacements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `original_card_id` int(11) DEFAULT NULL,
  `reason` enum('lost','damaged','stolen','name_change','info_update','other') NOT NULL,
  `description` text DEFAULT NULL,
  `charge_amount` decimal(10,2) DEFAULT 0.00,
  `payment_status` enum('pending','paid','waived') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `replacement_date` timestamp NULL DEFAULT current_timestamp(),
  `new_card_id` int(11) DEFAULT NULL,
  `status` enum('pending','approved','completed','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `original_card_id` (`original_card_id`),
  KEY `new_card_id` (`new_card_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `id_card_replacements`
--

LOCK TABLES `id_card_replacements` WRITE;
/*!40000 ALTER TABLE `id_card_replacements` DISABLE KEYS */;
/*!40000 ALTER TABLE `id_card_replacements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `intakes`
--

DROP TABLE IF EXISTS `intakes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `intakes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `intake_name` varchar(100) NOT NULL,
  `intake_month` varchar(20) NOT NULL,
  `intake_year` year(4) NOT NULL,
  `application_start` date DEFAULT NULL,
  `application_deadline` date DEFAULT NULL,
  `status` enum('Open','Closed','Upcoming') NOT NULL DEFAULT 'Upcoming',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_intake` (`intake_month`,`intake_year`),
  KEY `idx_intake_status` (`status`)
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
-- Table structure for table `it_support_tickets`
--

DROP TABLE IF EXISTS `it_support_tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `it_support_tickets` (
  `id` int(11) NOT NULL,
  `ticket_number` varchar(50) NOT NULL,
  `requester_name` varchar(100) NOT NULL,
  `requester_email` varchar(100) DEFAULT NULL,
  `requester_type` enum('student','staff','faculty') NOT NULL,
  `issue_type` enum('hardware','software','network','account','other') NOT NULL,
  `priority` enum('low','medium','high','critical') DEFAULT 'medium',
  `description` text NOT NULL,
  `status` enum('open','in_progress','resolved','closed') DEFAULT 'open',
  `assigned_to` int(11) DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  KEY `idx_ist_status_priority` (`status`,`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `it_support_tickets`
--

LOCK TABLES `it_support_tickets` WRITE;
/*!40000 ALTER TABLE `it_support_tickets` DISABLE KEYS */;
INSERT INTO `it_support_tickets` VALUES (1,'TKT-2024-001','John Mugisha','jmugisha@student.isnm.ac.ug','student','software','medium','Unable to access SPSS software on Lab A computers','open',NULL,NULL,NULL,'2026-06-14 18:38:56','2026-06-14 18:38:56'),(2,'TKT-2024-002','Dr. Emily Achieng','eachieng@isnm.ac.ug','staff','hardware','high','Projector in Lab B not displaying properly','in_progress',NULL,NULL,NULL,'2026-06-14 18:38:56','2026-06-14 18:38:56'),(3,'TKT-2024-003','Peter Kato','pkato@student.isnm.ac.ug','student','account','low','Forgot password for student portal','open',NULL,NULL,NULL,'2026-06-14 18:38:56','2026-06-14 18:38:56'),(4,'TKT-2024-004','Ms. Ruth Akello','rakello@isnm.ac.ug','staff','network','critical','WiFi connection dropping frequently in Lab A','open',NULL,NULL,NULL,'2026-06-14 18:38:56','2026-06-14 18:38:56');
/*!40000 ALTER TABLE `it_support_tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lab_asset_assignments`
--

DROP TABLE IF EXISTS `lab_asset_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_asset_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `asset_type` enum('computer','equipment','accessory') NOT NULL,
  `asset_id` int(11) NOT NULL,
  `assigned_to_type` enum('student','staff','lecturer','lab') DEFAULT 'lab',
  `assigned_to_id` int(11) DEFAULT NULL,
  `lab_room_id` int(11) DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `assigned_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `status` enum('active','returned','transferred') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `asset_type` (`asset_type`,`asset_id`),
  KEY `assigned_to_type` (`assigned_to_type`,`assigned_to_id`),
  KEY `lab_room_id` (`lab_room_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_asset_assignments`
--

LOCK TABLES `lab_asset_assignments` WRITE;
/*!40000 ALTER TABLE `lab_asset_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `lab_asset_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lab_attendance`
--

DROP TABLE IF EXISTS `lab_attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `lab_room_id` int(11) DEFAULT NULL,
  `session_id` int(11) DEFAULT NULL,
  `attendance_date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `computer_id` int(11) DEFAULT NULL,
  `seat_number` varchar(20) DEFAULT NULL,
  `status` enum('present','absent','late','excused') DEFAULT 'present',
  `marked_by` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `student_id` (`student_id`),
  KEY `lab_room_id` (`lab_room_id`),
  KEY `session_id` (`session_id`),
  KEY `attendance_date` (`attendance_date`),
  KEY `computer_id` (`computer_id`)
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
-- Table structure for table `lab_bookings`
--

DROP TABLE IF EXISTS `lab_bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_bookings` (
  `id` int(11) NOT NULL,
  `booking_reference` varchar(50) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `instructor_name` varchar(100) NOT NULL,
  `instructor_email` varchar(100) DEFAULT NULL,
  `booking_date` date NOT NULL,
  `time_slot` varchar(50) NOT NULL,
  `number_of_students` int(11) NOT NULL,
  `purpose` text DEFAULT NULL,
  `special_requirements` text DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','completed') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `lab_assigned` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `lab_room_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  KEY `idx_lb_date_status` (`booking_date`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_bookings`
--

LOCK TABLES `lab_bookings` WRITE;
/*!40000 ALTER TABLE `lab_bookings` DISABLE KEYS */;
INSERT INTO `lab_bookings` VALUES (1,'BK-2024-001','Introduction to Nursing Informatics','Dr. Sarah Johnson','sjohnson@isnm.ac.ug','2024-06-10','09:00 AM - 11:00 AM',25,'Practical session on electronic health records',NULL,'confirmed',NULL,'Lab A','2026-06-14 18:38:55','2026-06-14 18:38:55',NULL,NULL,NULL),(2,'BK-2024-002','Research Methods','Prof. Michael Okonkwo','mokonkwo@isnm.ac.ug','2024-06-10','02:00 PM - 04:00 PM',30,'Data analysis using SPSS',NULL,'pending',NULL,'Lab B','2026-06-14 18:38:55','2026-06-14 18:38:55',NULL,NULL,NULL),(3,'BK-2024-003','Computer Literacy','Ms. Grace Namukasa','gnamukasa@isnm.ac.ug','2024-06-11','09:00 AM - 11:00 AM',20,'Basic computer skills training',NULL,'confirmed',NULL,'Lab A','2026-06-14 18:38:55','2026-06-14 18:38:55',NULL,NULL,NULL);
/*!40000 ALTER TABLE `lab_bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lab_computer_assignments`
--

DROP TABLE IF EXISTS `lab_computer_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_computer_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `computer_id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `staff_id` int(11) DEFAULT NULL,
  `assignment_type` enum('student','staff','lecturer') DEFAULT 'student',
  `assigned_by` int(11) DEFAULT NULL,
  `assigned_date` date NOT NULL,
  `return_date` date DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `status` enum('active','returned','transferred') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `computer_id` (`computer_id`),
  KEY `student_id` (`student_id`),
  KEY `staff_id` (`staff_id`),
  KEY `status` (`status`),
  KEY `assigned_date` (`assigned_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_computer_assignments`
--

LOCK TABLES `lab_computer_assignments` WRITE;
/*!40000 ALTER TABLE `lab_computer_assignments` DISABLE KEYS */;
/*!40000 ALTER TABLE `lab_computer_assignments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lab_computers`
--

DROP TABLE IF EXISTS `lab_computers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_computers` (
  `id` int(11) NOT NULL,
  `computer_id` varchar(50) NOT NULL,
  `computer_name` varchar(100) NOT NULL,
  `lab_name` varchar(100) DEFAULT NULL,
  `location` varchar(100) NOT NULL,
  `status` enum('online','offline','maintenance','deleted') DEFAULT 'online',
  `ip_address` varchar(45) DEFAULT NULL,
  `mac_address` varchar(17) DEFAULT NULL,
  `specifications` text DEFAULT NULL,
  `os_installed` varchar(100) DEFAULT NULL,
  `operating_system` varchar(100) DEFAULT NULL,
  `last_maintenance` date DEFAULT NULL,
  `next_maintenance` date DEFAULT NULL,
  `issues_reported` text DEFAULT NULL,
  `assigned_to` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_computers`
--

LOCK TABLES `lab_computers` WRITE;
/*!40000 ALTER TABLE `lab_computers` DISABLE KEYS */;
INSERT INTO `lab_computers` VALUES (1,'LAB-A-001','Computer Lab A - Station 1',NULL,'Lab A - Floor 1','online','192.168.1.101','AA:BB:CC:DD:EE:01','Intel i5, 8GB RAM, 256GB SSD','Windows 11 Pro',NULL,'2024-05-01','2024-08-01',NULL,NULL,NULL,NULL,'2026-06-14 18:38:55','2026-06-14 18:38:55'),(2,'LAB-A-002','Computer Lab A - Station 2',NULL,'Lab A - Floor 1','online','192.168.1.102','AA:BB:CC:DD:EE:02','Intel i5, 8GB RAM, 256GB SSD','Windows 11 Pro',NULL,'2024-05-01','2024-08-01',NULL,NULL,NULL,NULL,'2026-06-14 18:38:55','2026-06-14 18:38:55'),(3,'LAB-A-003','Computer Lab A - Station 3',NULL,'Lab A - Floor 1','offline','192.168.1.103','AA:BB:CC:DD:EE:03','Intel i5, 8GB RAM, 256GB SSD','Windows 11 Pro',NULL,'2024-05-01','2024-08-01','Hardware issue - PSU replacement needed',NULL,NULL,NULL,'2026-06-14 18:38:55','2026-06-14 18:38:55'),(4,'LAB-B-001','Computer Lab B - Station 1',NULL,'Lab B - Floor 2','online','192.168.2.101','BB:CC:DD:EE:FF:01','Intel i7, 16GB RAM, 512GB SSD','Windows 11 Pro',NULL,'2024-05-15','2024-08-15',NULL,NULL,NULL,NULL,'2026-06-14 18:38:55','2026-06-14 18:38:55'),(5,'LAB-B-002','Computer Lab B - Station 2',NULL,'Lab B - Floor 2','maintenance','192.168.2.102','BB:CC:DD:EE:FF:02','Intel i7, 16GB RAM, 512GB SSD','Windows 11 Pro',NULL,'2024-05-15','2024-08-15','OS reinstallation in progress',NULL,NULL,NULL,'2026-06-14 18:38:55','2026-06-14 18:38:55');
/*!40000 ALTER TABLE `lab_computers` ENABLE KEYS */;
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
  `item_category` enum('toner','ink','paper','cable','mouse','keyboard','usb','cd_dvd','other') DEFAULT 'other',
  `quantity` int(11) NOT NULL DEFAULT 0,
  `reorder_level` int(11) DEFAULT 5,
  `unit` varchar(50) DEFAULT 'pcs',
  `unit_cost` decimal(10,2) DEFAULT 0.00,
  `supplier` varchar(200) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `last_restocked` date DEFAULT NULL,
  `status` enum('in_stock','low_stock','out_of_stock') DEFAULT 'in_stock',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `item_category` (`item_category`),
  KEY `status` (`status`)
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
-- Table structure for table `lab_equipment`
--

DROP TABLE IF EXISTS `lab_equipment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_equipment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `equipment_code` varchar(50) NOT NULL,
  `equipment_name` varchar(200) NOT NULL,
  `equipment_type` enum('computer','printer','scanner','projector','ups','accessory','other') DEFAULT 'other',
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `serial_number` varchar(200) DEFAULT NULL,
  `lab_room_id` int(11) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `warranty_expiry` date DEFAULT NULL,
  `condition_status` enum('excellent','good','fair','poor','faulty','retired') DEFAULT 'good',
  `status` enum('available','in_use','maintenance','retired') DEFAULT 'available',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `equipment_code` (`equipment_code`),
  KEY `lab_room_id` (`lab_room_id`),
  KEY `equipment_type` (`equipment_type`),
  KEY `status` (`status`)
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
  `equipment_id` int(11) NOT NULL,
  `checked_out_to` varchar(200) NOT NULL,
  `borrower_type` enum('student','staff','lecturer') DEFAULT 'student',
  `borrower_id` int(11) DEFAULT NULL,
  `checkout_date` datetime NOT NULL,
  `expected_return` datetime DEFAULT NULL,
  `actual_return` datetime DEFAULT NULL,
  `purpose` text DEFAULT NULL,
  `condition_at_checkout` varchar(200) DEFAULT NULL,
  `condition_at_return` varchar(200) DEFAULT NULL,
  `checked_out_by` int(11) DEFAULT NULL,
  `status` enum('checked_out','returned','overdue','lost','damaged') DEFAULT 'checked_out',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `equipment_id` (`equipment_id`),
  KEY `borrower_id` (`borrower_id`),
  KEY `status` (`status`),
  KEY `expected_return` (`expected_return`)
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
-- Table structure for table `lab_practical_sessions`
--

DROP TABLE IF EXISTS `lab_practical_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_practical_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_code` varchar(50) NOT NULL,
  `course_name` varchar(200) NOT NULL,
  `instructor_name` varchar(200) DEFAULT NULL,
  `instructor_id` int(11) DEFAULT NULL,
  `lab_room_id` int(11) DEFAULT NULL,
  `session_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `program` varchar(200) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `max_students` int(11) DEFAULT 0,
  `status` enum('scheduled','ongoing','completed','cancelled') DEFAULT 'scheduled',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_code` (`session_code`),
  KEY `lab_room_id` (`lab_room_id`),
  KEY `session_date` (`session_date`),
  KEY `status` (`status`)
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
-- Table structure for table `lab_rooms`
--

DROP TABLE IF EXISTS `lab_rooms`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_name` varchar(100) NOT NULL,
  `room_code` varchar(20) NOT NULL,
  `capacity` int(11) NOT NULL DEFAULT 0,
  `computer_count` int(11) NOT NULL DEFAULT 0,
  `location` varchar(200) DEFAULT NULL,
  `status` enum('active','inactive','maintenance') DEFAULT 'active',
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_code` (`room_code`),
  KEY `status` (`status`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_rooms`
--

LOCK TABLES `lab_rooms` WRITE;
/*!40000 ALTER TABLE `lab_rooms` DISABLE KEYS */;
INSERT INTO `lab_rooms` VALUES (1,'Computer Lab A','LAB-A',40,40,'Main Building, Ground Floor','active',NULL,'2026-06-26 19:08:42','2026-06-26 19:08:42'),(2,'Computer Lab B','LAB-B',30,30,'Main Building, Ground Floor','active',NULL,'2026-06-26 19:08:42','2026-06-26 19:08:42'),(3,'Computer Lab C','LAB-C',25,25,'Main Building, First Floor','active',NULL,'2026-06-26 19:08:42','2026-06-26 19:08:42'),(4,'Computer Lab D','LAB-D',20,20,'Main Building, First Floor','active',NULL,'2026-06-26 19:08:42','2026-06-26 19:08:42'),(5,'Skills Lab','SKILLS-1',15,15,'Clinical Building','active',NULL,'2026-06-26 19:08:42','2026-06-26 19:08:42');
/*!40000 ALTER TABLE `lab_rooms` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lab_usage_stats`
--

DROP TABLE IF EXISTS `lab_usage_stats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `lab_usage_stats` (
  `id` int(11) NOT NULL,
  `lab_name` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `total_sessions` int(11) DEFAULT 0,
  `total_users` int(11) DEFAULT 0,
  `peak_concurrent_users` int(11) DEFAULT 0,
  `average_session_duration` int(11) DEFAULT 0,
  `computers_used` int(11) DEFAULT 0,
  `computers_available` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `lab_usage_stats`
--

LOCK TABLES `lab_usage_stats` WRITE;
/*!40000 ALTER TABLE `lab_usage_stats` DISABLE KEYS */;
INSERT INTO `lab_usage_stats` VALUES (1,'Lab A','2024-06-05',8,45,25,90,22,25,NULL,'2026-06-14 18:38:56'),(2,'Lab B','2024-06-05',6,35,20,85,18,20,NULL,'2026-06-14 18:38:56'),(3,'Lab A','2024-06-06',10,55,28,95,24,25,NULL,'2026-06-14 18:38:56'),(4,'Lab B','2024-06-06',7,40,22,80,19,20,NULL,'2026-06-14 18:38:56');
/*!40000 ALTER TABLE `lab_usage_stats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenance_logs`
--

DROP TABLE IF EXISTS `maintenance_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `maintenance_logs` (
  `id` int(11) NOT NULL,
  `computer_id` varchar(50) NOT NULL,
  `maintenance_type` enum('routine','repair','upgrade','cleaning') NOT NULL,
  `description` text NOT NULL,
  `performed_by` varchar(100) NOT NULL,
  `cost` decimal(10,2) DEFAULT 0.00,
  `parts_replaced` text DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled') DEFAULT 'scheduled',
  `scheduled_date` date DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `maintenance_logs`
--

LOCK TABLES `maintenance_logs` WRITE;
/*!40000 ALTER TABLE `maintenance_logs` DISABLE KEYS */;
INSERT INTO `maintenance_logs` VALUES (1,'LAB-A-003','repair','Power supply unit replacement required','IT Technician - James',150.00,NULL,'scheduled','2024-06-12',NULL,'2026-06-14 18:38:56','2026-06-14 18:38:56'),(2,'LAB-B-002','routine','Operating system reinstallation and updates','IT Technician - Sarah',0.00,NULL,'in_progress','2024-06-10',NULL,'2026-06-14 18:38:56','2026-06-14 18:38:56'),(3,'LAB-A-003','repair','Power supply unit replacement required','IT Technician - James',150.00,NULL,'scheduled','2024-06-12',NULL,'2026-06-15 04:19:05','2026-06-15 04:19:05'),(4,'LAB-B-002','routine','Operating system reinstallation and updates','IT Technician - Sarah',0.00,NULL,'in_progress','2024-06-10',NULL,'2026-06-15 04:19:05','2026-06-15 04:19:05');
/*!40000 ALTER TABLE `maintenance_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `medicine_stock`
--

DROP TABLE IF EXISTS `medicine_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `medicine_stock` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `medicine_stock`
--

LOCK TABLES `medicine_stock` WRITE;
/*!40000 ALTER TABLE `medicine_stock` DISABLE KEYS */;
INSERT INTO `medicine_stock` VALUES (1,'PARA001','Paracetamol','Acetaminophen','Painkiller','Tablet','500mg',NULL,NULL,200,'tablets',50,50.00,NULL,'UGX',NULL,'2027-12-31','Cabinet A1',0,'1-2 tablets every 4-6 hours as needed for pain/fever',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:34','2026-06-20 08:42:34'),(2,'IBU001','Ibuprofen','Ibuprofen','Anti-inflammatory','Tablet','400mg',NULL,NULL,150,'tablets',30,100.00,NULL,'UGX',NULL,'2027-10-31','Cabinet A1',0,'1 tablet 3 times daily after meals',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:34','2026-06-20 08:42:34'),(3,'AMOX001','Amoxicillin','Amoxicillin','Antibiotic','Capsule','500mg',NULL,NULL,100,'capsules',20,200.00,NULL,'UGX',NULL,'2027-08-31','Cabinet B1',1,'1 capsule 3 times daily for 7 days',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:34','2026-06-20 08:42:34'),(4,'CTM001','Chlorpheniramine','Chlorpheniramine Maleate','Allergy','Tablet','4mg',NULL,NULL,100,'tablets',20,50.00,NULL,'UGX',NULL,'2027-11-30','Cabinet A2',0,'1 tablet every 4-6 hours for allergies',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:34','2026-06-20 08:42:34'),(5,'ORS001','Oral Rehydration Salts','ORS','Other','Powder','20.5g/sachet',NULL,NULL,100,'sachets',30,500.00,NULL,'UGX',NULL,'2028-06-30','Cabinet C1',0,'Dissolve 1 sachet in 1L water, drink after each loose stool',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:34','2026-06-20 08:42:34'),(6,'ART001','Artemether/Lumefantrine','Coartem','Antimalarial','Tablet','20/120mg',NULL,NULL,60,'tablets',20,1500.00,NULL,'UGX',NULL,'2027-09-30','Cabinet B2',1,'4 tablets twice daily for 3 days',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:34','2026-06-20 08:42:34'),(7,'VITC001','Vitamin C','Ascorbic Acid','Vitamins','Tablet','500mg',NULL,NULL,300,'tablets',50,30.00,NULL,'UGX',NULL,'2028-12-31','Cabinet C1',0,'1 tablet daily for immune support',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:34','2026-06-20 08:42:34'),(8,'MET001','Metered Dose Inhaler','Salbutamol','Respiratory','Inhaler','100mcg/dose',NULL,NULL,10,'inhalers',3,15000.00,NULL,'UGX',NULL,'2027-06-30','Cabinet A3',1,'1-2 puffs as needed for asthma symptoms',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:34','2026-06-20 08:42:34'),(9,'ANT001','Antacid','Aluminum/Magnesium Hydroxide','Digestive','Tablet','500mg',NULL,NULL,200,'tablets',40,100.00,NULL,'UGX',NULL,'2027-11-30','Cabinet C1',0,'1-2 tablets after meals or when symptomatic',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:34','2026-06-20 08:42:34'),(10,'HYD001','Hydrocortisone Cream','Hydrocortisone','Dermatological','Cream','1%',NULL,NULL,20,'tubes',5,5000.00,NULL,'UGX',NULL,'2027-08-31','Cabinet D1',0,'Apply thin layer to affected area 2-3 times daily',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:34','2026-06-20 08:42:34'),(11,'DIA001','Diazepam','Diazepam','Painkiller','Tablet','5mg',NULL,NULL,30,'tablets',10,200.00,NULL,'UGX',NULL,'2026-12-31','Cabinet B2',1,'1 tablet at bedtime for anxiety or muscle spasms',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:34','2026-06-20 08:42:34'),(12,'BAN001','Bandages','Cotton Bandage','First Aid','Other','4 inches x 5 meters',NULL,NULL,50,'rolls',10,1500.00,NULL,'UGX',NULL,'2029-12-31','Shelf E1',0,'For wound dressing and injury management',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:34','2026-06-20 08:42:34'),(13,'GAU001','Gauze Swabs','Sterile Gauze','First Aid','Other','10x10cm',NULL,NULL,200,'packs',50,800.00,NULL,'UGX',NULL,'2029-12-31','Shelf E1',0,'Sterile swabs for wound cleaning and dressing',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:34','2026-06-20 08:42:34'),(14,'GLU001','Glucose Powder','Dextrose','Vitamins','Powder','500g',NULL,NULL,10,'packs',3,5000.00,NULL,'UGX',NULL,'2028-06-30','Cabinet C1',0,'Mix 2 tablespoons in water for energy',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:34','2026-06-20 08:42:34'),(15,'ALC001','Alcohol Swabs','Isopropyl Alcohol','First Aid','Solution','70%',NULL,NULL,300,'swabs',50,100.00,NULL,'UGX',NULL,'2028-12-31','Shelf E1',0,'Use for cleaning skin before injections',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:34','2026-06-20 08:42:34'),(16,'CLO001','Chloroquine','Chloroquine Phosphate','Antimalarial','Tablet','250mg',NULL,NULL,50,'tablets',15,300.00,NULL,'UGX',NULL,'2027-05-31','Cabinet B2',1,'As prescribed for malaria treatment',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:34','2026-06-20 08:42:34'),(17,'MEF001','Mefenamic Acid','Mefenamic Acid','Painkiller','Capsule','500mg',NULL,NULL,80,'capsules',20,200.00,NULL,'UGX',NULL,'2027-07-31','Cabinet A1',0,'1 capsule 3 times daily for pain and inflammation',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:34','2026-06-20 08:42:34'),(18,'METR001','Metronidazole','Metronidazole','Antibiotic','Tablet','400mg',NULL,NULL,100,'tablets',20,150.00,NULL,'UGX',NULL,'2027-09-30','Cabinet B1',1,'1 tablet 3 times daily for 5-7 days',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:34','2026-06-20 08:42:34'),(19,'DIC001','Diclofenac Gel','Diclofenac Diethylamine','Anti-inflammatory','Cream','1%',NULL,NULL,15,'tubes',5,7000.00,NULL,'UGX',NULL,'2027-10-31','Cabinet D1',0,'Apply to affected area 3-4 times daily',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:34','2026-06-20 08:42:34'),(20,'CET001','Cetirizine','Cetirizine Hydrochloride','Allergy','Tablet','10mg',NULL,NULL,100,'tablets',20,100.00,NULL,'UGX',NULL,'2027-12-31','Cabinet A2',0,'1 tablet daily for allergy symptoms',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:34','2026-06-20 08:42:34'),(21,'ASP001','Aspirin','Acetylsalicylic Acid','Painkiller','Tablet','300mg',NULL,NULL,100,'tablets',25,50.00,NULL,'UGX',NULL,'2027-06-30','Cabinet A1',0,'1-2 tablets every 4-6 hours for pain/fever',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:34','2026-06-20 08:42:34'),(22,'ZIN001','Zinc Tablets','Zinc Sulfate','Vitamins','Tablet','20mg',NULL,NULL,150,'tablets',30,100.00,NULL,'UGX',NULL,'2028-09-30','Cabinet C1',0,'1 tablet daily for immune support and wound healing',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:34','2026-06-20 08:42:34'),(23,'CLOT001','Clotrimazole Cream','Clotrimazole','Antifungal','Cream','1%',NULL,NULL,15,'tubes',5,4000.00,NULL,'UGX',NULL,'2027-08-31','Cabinet D1',0,'Apply to affected area twice daily for 2 weeks',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:34','2026-06-20 08:42:34'),(24,'EYE001','Eye Drops','Chloramphenicol','Other','Drops','0.5%',NULL,NULL,20,'bottles',5,5000.00,NULL,'UGX',NULL,'2027-04-30','Cabinet A3',1,'1-2 drops in affected eye every 2-4 hours',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:34','2026-06-20 08:42:34'),(25,'BET001','Betadine Solution','Povidone-Iodine','First Aid','Solution','10%',NULL,NULL,10,'bottles',3,8000.00,NULL,'UGX',NULL,'2028-03-31','Shelf E1',0,'Apply to wounds for disinfection',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:34','2026-06-20 08:42:34');
/*!40000 ALTER TABLE `medicine_stock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `medicine_stock_transactions`
--

DROP TABLE IF EXISTS `medicine_stock_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `medicine_stock_transactions` (
  `id` int(11) NOT NULL,
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
  `created_at` timestamp NULL DEFAULT current_timestamp()
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
-- Table structure for table `network_devices`
--

DROP TABLE IF EXISTS `network_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `network_devices` (
  `id` int(11) NOT NULL,
  `device_name` varchar(100) NOT NULL,
  `device_type` enum('router','switch','access_point','firewall','server','other') NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `mac_address` varchar(17) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `status` enum('online','offline','maintenance') DEFAULT 'online',
  `firmware_version` varchar(50) DEFAULT NULL,
  `last_check` timestamp NULL DEFAULT NULL,
  `uptime_hours` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  KEY `idx_nd_type_status` (`device_type`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `network_devices`
--

LOCK TABLES `network_devices` WRITE;
/*!40000 ALTER TABLE `network_devices` DISABLE KEYS */;
INSERT INTO `network_devices` VALUES (1,'Main Router','router','192.168.0.1','00:11:22:33:44:55','Server Room','online','v2.1.0',NULL,720,NULL,'2026-06-14 18:38:56','2026-06-14 18:38:56'),(2,'Lab A Switch','switch','192.168.1.1','00:11:22:33:44:56','Lab A - Floor 1','online','v1.5.2',NULL,480,NULL,'2026-06-14 18:38:56','2026-06-14 18:38:56'),(3,'Lab B Switch','switch','192.168.2.1','00:11:22:33:44:57','Lab B - Floor 2','online','v1.5.2',NULL,480,NULL,'2026-06-14 18:38:56','2026-06-14 18:38:56'),(4,'WiFi Access Point A','access_point','192.168.0.10','00:11:22:33:44:58','Lab A - Floor 1','online','v3.2.1',NULL,240,NULL,'2026-06-14 18:38:56','2026-06-14 18:38:56'),(5,'WiFi Access Point B','access_point','192.168.0.11','00:11:22:33:44:59','Lab B - Floor 2','offline','v3.2.1',NULL,0,'Needs repair','2026-06-14 18:38:56','2026-06-14 18:38:56'),(6,'Firewall','firewall','192.168.0.2','00:11:22:33:44:60','Server Room','online','v4.0.0',NULL,720,NULL,'2026-06-14 18:38:56','2026-06-14 18:38:56'),(7,'Main Router','router','192.168.0.1','00:11:22:33:44:55','Server Room','online','v2.1.0',NULL,720,NULL,'2026-06-15 04:19:05','2026-06-15 04:19:05'),(8,'Lab A Switch','switch','192.168.1.1','00:11:22:33:44:56','Lab A - Floor 1','online','v1.5.2',NULL,480,NULL,'2026-06-15 04:19:05','2026-06-15 04:19:05'),(9,'Lab B Switch','switch','192.168.2.1','00:11:22:33:44:57','Lab B - Floor 2','online','v1.5.2',NULL,480,NULL,'2026-06-15 04:19:05','2026-06-15 04:19:05'),(10,'WiFi Access Point A','access_point','192.168.0.10','00:11:22:33:44:58','Lab A - Floor 1','online','v3.2.1',NULL,240,NULL,'2026-06-15 04:19:05','2026-06-15 04:19:05'),(11,'WiFi Access Point B','access_point','192.168.0.11','00:11:22:33:44:59','Lab B - Floor 2','offline','v3.2.1',NULL,0,'Needs repair','2026-06-15 04:19:05','2026-06-15 04:19:05'),(12,'Firewall','firewall','192.168.0.2','00:11:22:33:44:60','Server Room','online','v4.0.0',NULL,720,NULL,'2026-06-15 04:19:05','2026-06-15 04:19:05');
/*!40000 ALTER TABLE `network_devices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `staff_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL COMMENT 'application, contact, feedback, complaint, system',
  `title` varchar(255) NOT NULL,
  `message` longtext NOT NULL,
  `related_id` int(11) DEFAULT NULL,
  `from_email` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_staff_unread` (`staff_id`,`is_read`),
  KEY `idx_created` (`created_at`),
  KEY `idx_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `printing_charges`
--

DROP TABLE IF EXISTS `printing_charges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `printing_charges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `print_type` enum('bw','color','photocopy') NOT NULL,
  `paper_size` enum('A4','A3','letter','legal') DEFAULT 'A4',
  `charge_per_page` decimal(10,2) NOT NULL DEFAULT 0.00,
  `description` varchar(200) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `print_type_paper` (`print_type`,`paper_size`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `printing_charges`
--

LOCK TABLES `printing_charges` WRITE;
/*!40000 ALTER TABLE `printing_charges` DISABLE KEYS */;
INSERT INTO `printing_charges` VALUES (1,'bw','A4',100.00,'Black & White A4 per page',1,NULL,'2026-06-26 19:08:42','2026-06-26 19:08:42'),(2,'color','A4',500.00,'Colour A4 per page',1,NULL,'2026-06-26 19:08:42','2026-06-26 19:08:42'),(3,'photocopy','A4',50.00,'Photocopy A4 per page',1,NULL,'2026-06-26 19:08:42','2026-06-26 19:08:42'),(4,'bw','A3',200.00,'Black & White A3 per page',1,NULL,'2026-06-26 19:08:42','2026-06-26 19:08:42'),(5,'color','A3',1000.00,'Colour A3 per page',1,NULL,'2026-06-26 19:08:42','2026-06-26 19:08:42'),(6,'photocopy','A3',100.00,'Photocopy A3 per page',1,NULL,'2026-06-26 19:08:42','2026-06-26 19:08:42');
/*!40000 ALTER TABLE `printing_charges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `printing_jobs`
--

DROP TABLE IF EXISTS `printing_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `printing_jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_number` varchar(50) NOT NULL,
  `requester_name` varchar(200) NOT NULL,
  `requester_type` enum('student','staff') NOT NULL,
  `requester_id` int(11) DEFAULT NULL,
  `document_name` varchar(200) DEFAULT NULL,
  `file_path` varchar(500) DEFAULT NULL,
  `pages` int(11) NOT NULL DEFAULT 1,
  `copies` int(11) DEFAULT 1,
  `print_type` enum('bw','color','photocopy') DEFAULT 'bw',
  `paper_size` enum('A4','A3','letter','legal') DEFAULT 'A4',
  `charge_per_page` decimal(10,2) DEFAULT 0.00,
  `total_charge` decimal(10,2) DEFAULT 0.00,
  `payment_status` enum('pending','paid','waived') DEFAULT 'pending',
  `status` enum('pending','printing','completed','cancelled') DEFAULT 'pending',
  `printed_by` int(11) DEFAULT NULL,
  `printed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_number` (`job_number`),
  KEY `requester_id` (`requester_id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `printing_jobs`
--

LOCK TABLES `printing_jobs` WRITE;
/*!40000 ALTER TABLE `printing_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `printing_jobs` ENABLE KEYS */;
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
  `action` varchar(100) NOT NULL,
  `previous_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_rh_app` (`applicant_id`),
  KEY `idx_rh_action` (`action`),
  CONSTRAINT `requirement_history_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `requirement_history`
--

LOCK TABLES `requirement_history` WRITE;
/*!40000 ALTER TABLE `requirement_history` DISABLE KEYS */;
/*!40000 ALTER TABLE `requirement_history` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sickness_directory`
--

DROP TABLE IF EXISTS `sickness_directory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sickness_directory` (
  `id` int(11) NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sickness_directory`
--

LOCK TABLES `sickness_directory` WRITE;
/*!40000 ALTER TABLE `sickness_directory` DISABLE KEYS */;
INSERT INTO `sickness_directory` VALUES (1,'MLR','Malaria','Infectious','Fever, chills, headache, sweating, fatigue','Mosquito-borne parasitic infection common in tropical regions',0,'Artemisinin-based combination therapy, antimalarials','Active',NULL,'2026-06-20 08:42:33','2026-06-20 08:42:33'),(2,'TYP','Typhoid','Infectious','Prolonged fever, abdominal pain, headache, constipation or diarrhea','Bacterial infection spread through contaminated food/water',1,'Antibiotics (ciprofloxacin, azithromycin), hydration','Active',NULL,'2026-06-20 08:42:33','2026-06-20 08:42:33'),(3,'FLU','Influenza','Infectious','Fever, cough, sore throat, body aches, fatigue','Viral respiratory infection spread through droplets',1,'Rest, fluids, antipyretics, antivirals if severe','Active',NULL,'2026-06-20 08:42:33','2026-06-20 08:42:33'),(4,'COLD','Common Cold','Infectious','Runny nose, sneezing, sore throat, cough, mild fever','Viral upper respiratory tract infection',1,'Rest, antihistamines, decongestants, vitamin C','Active',NULL,'2026-06-20 08:42:33','2026-06-20 08:42:33'),(5,'URTI','Upper Respiratory Tract Infection','Infectious','Cough, sore throat, nasal congestion, fever','Bacterial or viral infection of upper airways',1,'Antibiotics if bacterial, rest, fluids','Active',NULL,'2026-06-20 08:42:33','2026-06-20 08:42:33'),(6,'HDCH','Headache/Tension Headache','Non-Infectious','Head pain, pressure around forehead, neck tension','Common tension-type headache from stress or fatigue',0,'Rest, analgesics (paracetamol, ibuprofen)','Active',NULL,'2026-06-20 08:42:33','2026-06-20 08:42:33'),(7,'GSTR','Gastritis','Non-Infectious','Abdominal pain, nausea, bloating, indigestion','Inflammation of stomach lining from diet, stress, or infection',0,'Antacids, dietary changes, proton pump inhibitors','Active',NULL,'2026-06-20 08:42:33','2026-06-20 08:42:33'),(8,'DIAR','Diarrhea','Infectious','Loose watery stools, abdominal cramps, dehydration','Common infection from contaminated food/water or viruses',1,'ORS, hydration, antidiarrheals, antibiotics if bacterial','Active',NULL,'2026-06-20 08:42:33','2026-06-20 08:42:33'),(9,'ALLG','Allergic Reaction','Non-Infectious','Rash, itching, sneezing, watery eyes, swelling','Immune response to allergens (food, dust, pollen, drugs)',0,'Antihistamines, corticosteroids, avoid triggers','Active',NULL,'2026-06-20 08:42:33','2026-06-20 08:42:33'),(10,'INJR','Injury/Accident','Injury','Pain, swelling, bruising, bleeding, limited mobility','Physical trauma from falls, sports, or accidents',0,'First aid, rest, ice, compression, elevation, analgesics','Active',NULL,'2026-06-20 08:42:33','2026-06-20 08:42:33'),(11,'ANEM','Anemia','Nutritional','Fatigue, weakness, pale skin, shortness of breath, dizziness','Low red blood cell count from iron deficiency or other causes',0,'Iron supplements, dietary changes, B12 if needed','Active',NULL,'2026-06-20 08:42:33','2026-06-20 08:42:33'),(12,'MALN','Malnutrition','Nutritional','Weight loss, fatigue, poor growth, weakened immunity','Inadequate nutrient intake affecting overall health',0,'Nutritional supplementation, diet counseling','Active',NULL,'2026-06-20 08:42:33','2026-06-20 08:42:33'),(13,'CONS','Constipation','Non-Infectious','Infrequent bowel movements, straining, hard stools','Common digestive issue from diet or lifestyle factors',0,'Increased fiber intake, hydration, laxatives if needed','Active',NULL,'2026-06-20 08:42:33','2026-06-20 08:42:33'),(14,'SORE','Sore Throat','Infectious','Pain or scratchiness in throat, difficulty swallowing','Viral or bacterial throat infection',1,'Warm salt water gargle, lozenges, antibiotics if strep','Active',NULL,'2026-06-20 08:42:33','2026-06-20 08:42:33'),(15,'EYEI','Eye Infection','Infectious','Redness, itching, discharge, swollen eyelids','Bacterial or viral conjunctivitis',1,'Antibiotic or antiviral eye drops, hygiene','Active',NULL,'2026-06-20 08:42:33','2026-06-20 08:42:33'),(16,'SKIN','Skin Infection/Rash','Infectious','Redness, itching, bumps, blisters, peeling','Fungal, bacterial, or viral skin infection',1,'Topical or oral antibiotics/antifungals, hygiene','Active',NULL,'2026-06-20 08:42:33','2026-06-20 08:42:33'),(17,'FATG','Fatigue/General Malaise','Non-Infectious','Tiredness, low energy, reduced motivation','General feeling of being unwell without specific diagnosis',0,'Rest, nutrition, hydration, stress management','Active',NULL,'2026-06-20 08:42:33','2026-06-20 08:42:33'),(18,'MSTR','Menstrual Cramps','Non-Infectious','Lower abdominal pain, back pain, nausea during menstruation','Painful menstrual periods common in young women',0,'Analgesics, heat therapy, rest, NSAIDs','Active',NULL,'2026-06-20 08:42:33','2026-06-20 08:42:33'),(19,'ANXT','Anxiety/Stress','Mental Health','Worry, restlessness, rapid heartbeat, difficulty concentrating','Mental health condition common among students under academic pressure',0,'Counseling, stress management, relaxation techniques','Active',NULL,'2026-06-20 08:42:33','2026-06-20 08:42:33'),(20,'BACK','Back Pain','Non-Infectious','Lower or upper back pain, stiffness, muscle tension','Musculoskeletal pain from poor posture, heavy lifting, or strain',0,'Rest, analgesics, physiotherapy, posture correction','Active',NULL,'2026-06-20 08:42:33','2026-06-20 08:42:33'),(21,'THRP','Throat Infection/Pharyngitis','Infectious','Sore throat, red tonsils, swollen lymph nodes, fever','Inflammation of the pharynx from viral or bacterial infection',1,'Antibiotics if bacterial, rest, warm fluids','Active',NULL,'2026-06-20 08:42:33','2026-06-20 08:42:33'),(22,'TOOT','Toothache','Non-Infectious','Tooth pain, sensitivity, swelling around tooth','Dental pain from cavities, infection, or impaction',0,'Analgesics, dental referral, antibiotics if infected','Active',NULL,'2026-06-20 08:42:33','2026-06-20 08:42:33'),(23,'URIN','Urinary Tract Infection','Infectious','Painful urination, frequent urination, lower abdominal pain','Bacterial infection of the urinary tract',0,'Antibiotics, increased fluid intake, cranberry','Active',NULL,'2026-06-20 08:42:33','2026-06-20 08:42:33'),(24,'ACNE','Acne/Skin Breakout','Non-Infectious','Pimples, blackheads, whiteheads, inflamed skin','Common skin condition from hormonal changes and stress',0,'Topical treatments, hygiene, dietary changes','Active',NULL,'2026-06-20 08:42:33','2026-06-20 08:42:33'),(25,'FUNG','Fungal Infection','Infectious','Itching, redness, peeling skin, rash with defined edges','Fungal skin infection common in tropical climates',1,'Antifungal creams or oral medication, keep area dry','Active',NULL,'2026-06-20 08:42:33','2026-06-20 08:42:33');
/*!40000 ALTER TABLE `sickness_directory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `software_installations`
--

DROP TABLE IF EXISTS `software_installations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `software_installations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `software_id` int(11) DEFAULT NULL,
  `computer_id` int(11) DEFAULT NULL,
  `lab_room_id` int(11) DEFAULT NULL,
  `installed_by` varchar(200) DEFAULT NULL,
  `installation_date` date DEFAULT NULL,
  `license_key_used` varchar(200) DEFAULT NULL,
  `version_installed` varchar(50) DEFAULT NULL,
  `status` enum('installed','updated','removed') DEFAULT 'installed',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `software_id` (`software_id`),
  KEY `computer_id` (`computer_id`),
  KEY `lab_room_id` (`lab_room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `software_installations`
--

LOCK TABLES `software_installations` WRITE;
/*!40000 ALTER TABLE `software_installations` DISABLE KEYS */;
/*!40000 ALTER TABLE `software_installations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `software_inventory`
--

DROP TABLE IF EXISTS `software_inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `software_inventory` (
  `id` int(11) NOT NULL,
  `software_name` varchar(200) NOT NULL,
  `version` varchar(50) DEFAULT NULL,
  `license_key` varchar(200) DEFAULT NULL,
  `license_type` enum('free','commercial','educational','trial') DEFAULT 'educational',
  `license_expiry` date DEFAULT NULL,
  `installation_count` int(11) DEFAULT 0,
  `update_available` tinyint(1) DEFAULT 0,
  `latest_version` varchar(50) DEFAULT NULL,
  `download_url` varchar(500) DEFAULT NULL,
  `category` enum('os','office','development','design','antivirus','utility','other') DEFAULT 'utility',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `software_inventory`
--

LOCK TABLES `software_inventory` WRITE;
/*!40000 ALTER TABLE `software_inventory` DISABLE KEYS */;
INSERT INTO `software_inventory` VALUES (1,'Microsoft Office 365','2024',NULL,'educational','2025-12-31',50,0,'2024',NULL,'office',NULL,'2026-06-14 18:38:56','2026-06-14 18:38:56'),(2,'SPSS Statistics','29.0',NULL,'commercial','2024-12-31',25,1,'30.0',NULL,'development',NULL,'2026-06-14 18:38:56','2026-06-14 18:38:56'),(3,'Windows 11 Pro','23H2',NULL,'educational','2026-06-30',50,0,'23H2',NULL,'os',NULL,'2026-06-14 18:38:56','2026-06-14 18:38:56'),(4,'Adobe Creative Cloud','2024',NULL,'educational','2024-08-31',15,1,'2024.1',NULL,'design',NULL,'2026-06-14 18:38:56','2026-06-14 18:38:56'),(5,'Malwarebytes Antivirus','5.0',NULL,'commercial','2025-01-15',50,0,'5.0',NULL,'antivirus',NULL,'2026-06-14 18:38:56','2026-06-14 18:38:56'),(6,'Microsoft Office 365','2024',NULL,'educational','2025-12-31',50,0,'2024',NULL,'office',NULL,'2026-06-15 04:19:05','2026-06-15 04:19:05'),(7,'SPSS Statistics','29.0',NULL,'commercial','2024-12-31',25,1,'30.0',NULL,'development',NULL,'2026-06-15 04:19:05','2026-06-15 04:19:05'),(8,'Windows 11 Pro','23H2',NULL,'educational','2026-06-30',50,0,'23H2',NULL,'os',NULL,'2026-06-15 04:19:05','2026-06-15 04:19:05'),(9,'Adobe Creative Cloud','2024',NULL,'educational','2024-08-31',15,1,'2024.1',NULL,'design',NULL,'2026-06-15 04:19:05','2026-06-15 04:19:05'),(10,'Malwarebytes Antivirus','5.0',NULL,'commercial','2025-01-15',50,0,'5.0',NULL,'antivirus',NULL,'2026-06-15 04:19:05','2026-06-15 04:19:05');
/*!40000 ALTER TABLE `software_inventory` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `staff`
--

DROP TABLE IF EXISTS `staff`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `staff` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_staff_role` (`role_id`),
  KEY `idx_staff_email` (`email`),
  CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `staff_roles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff`
--

LOCK TABLES `staff` WRITE;
/*!40000 ALTER TABLE `staff` DISABLE KEYS */;
/*!40000 ALTER TABLE `staff` ENABLE KEYS */;
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
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `staff_roles`
--

LOCK TABLES `staff_roles` WRITE;
/*!40000 ALTER TABLE `staff_roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `staff_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_academic_profiles`
--

DROP TABLE IF EXISTS `student_academic_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_academic_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_number` varchar(50) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `program` varchar(255) DEFAULT NULL,
  `academic_year` year(4) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `status` enum('Active','Completed','Dropped','Transferred') NOT NULL DEFAULT 'Active',
  `gpa` decimal(4,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sap_student` (`student_number`),
  KEY `idx_sap_year` (`academic_year`),
  KEY `idx_sap_program` (`program`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_academic_profiles`
--

LOCK TABLES `student_academic_profiles` WRITE;
/*!40000 ALTER TABLE `student_academic_profiles` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_academic_profiles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_academic_records`
--

DROP TABLE IF EXISTS `student_academic_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_academic_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `course_code` varchar(50) DEFAULT NULL,
  `marks` decimal(5,2) DEFAULT NULL,
  `grade` varchar(5) DEFAULT NULL,
  `credit_hours` decimal(4,1) DEFAULT 0.0,
  `gpa` decimal(4,2) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ar_student` (`student_id`),
  KEY `idx_ar_course` (`course_code`),
  KEY `idx_ar_year_sem` (`academic_year`,`semester`),
  CONSTRAINT `student_academic_records_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_academic_records`
--

LOCK TABLES `student_academic_records` WRITE;
/*!40000 ALTER TABLE `student_academic_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_academic_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_admission_tracking`
--

DROP TABLE IF EXISTS `student_admission_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_admission_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_number` varchar(50) DEFAULT NULL,
  `application_number` varchar(30) NOT NULL,
  `applicant_id` int(11) DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `intake` varchar(50) DEFAULT NULL,
  `admission_date` date DEFAULT NULL,
  `admission_status` enum('Pending','Under Review','Requirements Pending','Approved','Rejected','Registered') NOT NULL DEFAULT 'Pending',
  `requirements_total` int(11) NOT NULL DEFAULT 0,
  `requirements_completed` int(11) NOT NULL DEFAULT 0,
  `documents_uploaded` int(11) NOT NULL DEFAULT 0,
  `interview_scheduled` tinyint(1) NOT NULL DEFAULT 0,
  `interview_date` datetime DEFAULT NULL,
  `interview_notes` text DEFAULT NULL,
  `communication_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_track_app` (`application_number`),
  KEY `idx_track_status` (`admission_status`),
  KEY `idx_track_student` (`student_number`),
  KEY `applicant_id` (`applicant_id`),
  CONSTRAINT `student_admission_tracking_ibfk_1` FOREIGN KEY (`applicant_id`) REFERENCES `applicants` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_admission_tracking`
--

LOCK TABLES `student_admission_tracking` WRITE;
/*!40000 ALTER TABLE `student_admission_tracking` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_admission_tracking` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_attendance`
--

DROP TABLE IF EXISTS `student_attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `date` date DEFAULT NULL,
  `attendance_date` date DEFAULT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `status` enum('Present','Absent','Late','Excused','Holiday') NOT NULL DEFAULT 'Present',
  `subject` varchar(255) DEFAULT NULL,
  `lecturer` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_att_student` (`student_id`),
  KEY `idx_att_date` (`date`),
  KEY `idx_att_status` (`status`),
  CONSTRAINT `student_attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_attendance`
--

LOCK TABLES `student_attendance` WRITE;
/*!40000 ALTER TABLE `student_attendance` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_attendance` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_clinical_logbook_entries`
--

DROP TABLE IF EXISTS `student_clinical_logbook_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_clinical_logbook_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `date` date DEFAULT NULL,
  `procedure_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `supervisor_name` varchar(255) DEFAULT NULL,
  `supervisor_comment` text DEFAULT NULL,
  `verification_status` enum('Pending','Verified','Rejected') NOT NULL DEFAULT 'Pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cle_student` (`student_id`),
  KEY `idx_cle_status` (`verification_status`),
  CONSTRAINT `student_clinical_logbook_entries_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_clinical_logbook_entries`
--

LOCK TABLES `student_clinical_logbook_entries` WRITE;
/*!40000 ALTER TABLE `student_clinical_logbook_entries` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_clinical_logbook_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_clinical_placements`
--

DROP TABLE IF EXISTS `student_clinical_placements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_clinical_placements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `facility_name` varchar(255) NOT NULL,
  `facility_location` varchar(255) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `supervisor_name` varchar(255) DEFAULT NULL,
  `supervisor_phone` varchar(20) DEFAULT NULL,
  `supervisor_email` varchar(100) DEFAULT NULL,
  `supervisor_evaluation` text DEFAULT NULL,
  `status` enum('Active','Completed','Upcoming','Cancelled') NOT NULL DEFAULT 'Active',
  `hours_completed` int(11) DEFAULT 0,
  `hours_required` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cp_student` (`student_id`),
  KEY `idx_cp_status` (`status`),
  CONSTRAINT `student_clinical_placements_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_clinical_placements`
--

LOCK TABLES `student_clinical_placements` WRITE;
/*!40000 ALTER TABLE `student_clinical_placements` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_clinical_placements` ENABLE KEYS */;
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
  `skill_name` varchar(255) NOT NULL,
  `skill_category` varchar(100) DEFAULT NULL,
  `proficiency` enum('Not Attempted','Beginner','Intermediate','Competent','Expert') NOT NULL DEFAULT 'Not Attempted',
  `date_assessed` date DEFAULT NULL,
  `assessed_by` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sc_student` (`student_id`),
  KEY `idx_sc_category` (`skill_category`),
  CONSTRAINT `student_competencies_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_competencies`
--

LOCK TABLES `student_competencies` WRITE;
/*!40000 ALTER TABLE `student_competencies` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_competencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_course_registrations`
--

DROP TABLE IF EXISTS `student_course_registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_course_registrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `course_code` varchar(20) DEFAULT NULL,
  `course_name` varchar(255) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `status` enum('Registered','Dropped','Completed','Incomplete') NOT NULL DEFAULT 'Registered',
  `registered_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_scr_student` (`student_id`),
  KEY `idx_scr_course` (`course_code`),
  KEY `idx_scr_year_sem` (`academic_year`,`semester`),
  KEY `course_id` (`course_id`),
  CONSTRAINT `student_course_registrations_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_course_registrations_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `course_catalog` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_course_registrations`
--

LOCK TABLES `student_course_registrations` WRITE;
/*!40000 ALTER TABLE `student_course_registrations` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_course_registrations` ENABLE KEYS */;
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
  `document_name` varchar(255) NOT NULL,
  `document_type` varchar(100) DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `file_mime` varchar(100) DEFAULT NULL,
  `verification_status` enum('Pending','Verified','Rejected') NOT NULL DEFAULT 'Pending',
  `verification_remarks` text DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `document_status` enum('Active','Deleted') NOT NULL DEFAULT 'Active',
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_doc_app` (`applicant_id`),
  KEY `idx_doc_ver` (`verification_status`),
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
-- Table structure for table `student_fee_tracking`
--

DROP TABLE IF EXISTS `student_fee_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_fee_tracking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `fee_type` varchar(100) DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT 0.00,
  `paid` decimal(14,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(14,2) NOT NULL DEFAULT 0.00,
  `due_date` date DEFAULT NULL,
  `status` enum('Pending','Paid','Partial','Overdue','Waived') NOT NULL DEFAULT 'Pending',
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_ft_student` (`student_id`),
  KEY `idx_ft_status` (`status`),
  KEY `idx_ft_year` (`academic_year`),
  CONSTRAINT `student_fee_tracking_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
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
-- Table structure for table `student_hostel_allocations`
--

DROP TABLE IF EXISTS `student_hostel_allocations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_hostel_allocations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `hostel_name` varchar(100) DEFAULT NULL,
  `room_number` varchar(20) DEFAULT NULL,
  `bed_number` varchar(20) DEFAULT NULL,
  `check_in_date` date DEFAULT NULL,
  `check_out_date` date DEFAULT NULL,
  `status` enum('Active','Checked Out','Reserved') NOT NULL DEFAULT 'Active',
  `fee_per_semester` decimal(14,2) DEFAULT 0.00,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`),
  KEY `idx_ha_status` (`status`),
  CONSTRAINT `student_hostel_allocations_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
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
-- Table structure for table `student_id_cards`
--

DROP TABLE IF EXISTS `student_id_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_id_cards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `card_number` varchar(50) NOT NULL,
  `registration_number` varchar(50) DEFAULT NULL,
  `program` varchar(200) DEFAULT NULL,
  `intake` varchar(50) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `photo_path` varchar(500) DEFAULT NULL,
  `qr_code` text DEFAULT NULL,
  `barcode` varchar(200) DEFAULT NULL,
  `status` enum('active','expired','lost','damaged','replaced') DEFAULT 'active',
  `issued_by` int(11) DEFAULT NULL,
  `issued_date` timestamp NULL DEFAULT current_timestamp(),
  `last_print_date` timestamp NULL DEFAULT NULL,
  `print_count` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `card_number` (`card_number`),
  KEY `student_id` (`student_id`),
  KEY `status` (`status`),
  KEY `expiry_date` (`expiry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_id_cards`
--

LOCK TABLES `student_id_cards` WRITE;
/*!40000 ALTER TABLE `student_id_cards` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_id_cards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_library_borrowing`
--

DROP TABLE IF EXISTS `student_library_borrowing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_library_borrowing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `book_title` varchar(255) NOT NULL,
  `book_author` varchar(255) DEFAULT NULL,
  `isbn` varchar(50) DEFAULT NULL,
  `borrow_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `fine_amount` decimal(10,2) DEFAULT 0.00,
  `fine_paid` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('Borrowed','Returned','Overdue','Lost') NOT NULL DEFAULT 'Borrowed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_lb_student` (`student_id`),
  KEY `idx_lb_status` (`status`),
  KEY `idx_lb_due` (`due_date`),
  CONSTRAINT `student_library_borrowing_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_library_borrowing`
--

LOCK TABLES `student_library_borrowing` WRITE;
/*!40000 ALTER TABLE `student_library_borrowing` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_library_borrowing` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_messages`
--

DROP TABLE IF EXISTS `student_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `sender` varchar(255) DEFAULT 'System',
  `subject` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sm_student` (`student_id`),
  KEY `idx_sm_read` (`is_read`),
  CONSTRAINT `student_messages_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_messages`
--

LOCK TABLES `student_messages` WRITE;
/*!40000 ALTER TABLE `student_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_notifications`
--

DROP TABLE IF EXISTS `student_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) DEFAULT NULL COMMENT 'NULL = broadcast to all',
  `title` varchar(255) NOT NULL,
  `message` text DEFAULT NULL,
  `type` enum('info','success','warning','danger','announcement') NOT NULL DEFAULT 'info',
  `priority` enum('Low','Normal','High','Urgent') NOT NULL DEFAULT 'Normal',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `link` varchar(500) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sn_student` (`student_id`),
  KEY `idx_sn_read` (`is_read`),
  KEY `idx_sn_type` (`type`),
  CONSTRAINT `student_notifications_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_notifications`
--

LOCK TABLES `student_notifications` WRITE;
/*!40000 ALTER TABLE `student_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_payment_transactions`
--

DROP TABLE IF EXISTS `student_payment_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_payment_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `fee_id` int(11) DEFAULT NULL,
  `amount` decimal(14,2) NOT NULL,
  `payment_method` enum('Cash','Bank Transfer','Mobile Money','Cheque','Other') NOT NULL DEFAULT 'Cash',
  `transaction_ref` varchar(100) DEFAULT NULL,
  `paid_by` varchar(255) DEFAULT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `payment_date` timestamp NULL DEFAULT current_timestamp(),
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pt_student` (`student_id`),
  KEY `idx_pt_method` (`payment_method`),
  KEY `fee_id` (`fee_id`),
  CONSTRAINT `student_payment_transactions_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `student_payment_transactions_ibfk_2` FOREIGN KEY (`fee_id`) REFERENCES `student_fee_tracking` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_payment_transactions`
--

LOCK TABLES `student_payment_transactions` WRITE;
/*!40000 ALTER TABLE `student_payment_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_payment_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `student_profiles`
--

DROP TABLE IF EXISTS `student_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_profiles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `bio` text DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `medical_info` text DEFAULT NULL,
  `next_of_kin` varchar(255) DEFAULT NULL,
  `next_of_kin_phone` varchar(20) DEFAULT NULL,
  `sponsor_name` varchar(255) DEFAULT NULL,
  `sponsor_phone` varchar(20) DEFAULT NULL,
  `sponsor_email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`),
  CONSTRAINT `student_profiles_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
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
-- Table structure for table `student_requests`
--

DROP TABLE IF EXISTS `student_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `request_type` enum('Leave of Absence','Deferral','Transfer','Withdrawal','Transcript','Other') NOT NULL DEFAULT 'Other',
  `reason` text NOT NULL,
  `status` enum('Pending','Approved','Rejected','Cancelled') NOT NULL DEFAULT 'Pending',
  `admin_response` text DEFAULT NULL,
  `responded_by` int(11) DEFAULT NULL,
  `responded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sr_student` (`student_id`),
  KEY `idx_sr_status` (`status`),
  KEY `idx_sr_type` (`request_type`),
  CONSTRAINT `student_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
  `academic_year` varchar(20) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `total_credits` decimal(6,2) DEFAULT 0.00,
  `earned_credits` decimal(6,2) DEFAULT 0.00,
  `semester_gpa` decimal(4,2) DEFAULT 0.00,
  `cumulative_gpa` decimal(4,2) DEFAULT 0.00,
  `academic_standing` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sg_student` (`student_id`),
  KEY `idx_sg_year_sem` (`academic_year`,`semester`),
  CONSTRAINT `student_semester_gpa_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
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
  `id` int(11) NOT NULL,
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
  `recommended_by` varchar(200) NOT NULL,
  `recommender_title` varchar(100) DEFAULT NULL,
  `approved_by` varchar(200) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Expired','Extended') DEFAULT 'Pending',
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
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
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
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_timetables` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `subject` varchar(255) NOT NULL,
  `lecturer` varchar(255) DEFAULT NULL,
  `room` varchar(100) DEFAULT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_tt_student` (`student_id`),
  KEY `idx_tt_day` (`day_of_week`),
  KEY `idx_tt_time` (`start_time`),
  CONSTRAINT `student_timetables_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
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
-- Table structure for table `student_warnings`
--

DROP TABLE IF EXISTS `student_warnings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `student_warnings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` int(11) NOT NULL,
  `warning_type` varchar(100) DEFAULT NULL,
  `severity` enum('Verbal','Written','Final','Suspension') NOT NULL DEFAULT 'Written',
  `reason` text NOT NULL,
  `issued_by` varchar(255) DEFAULT NULL,
  `issued_date` date DEFAULT NULL,
  `warning_date` date DEFAULT NULL,
  `status` enum('Active','Resolved','Expired') NOT NULL DEFAULT 'Active',
  `resolution` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sw_student` (`student_id`),
  KEY `idx_sw_status` (`status`),
  CONSTRAINT `student_warnings_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `student_warnings`
--

LOCK TABLES `student_warnings` WRITE;
/*!40000 ALTER TABLE `student_warnings` DISABLE KEYS */;
/*!40000 ALTER TABLE `student_warnings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `students`
--

DROP TABLE IF EXISTS `students`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `students` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `student_id` varchar(50) DEFAULT NULL,
  `student_number` varchar(50) DEFAULT NULL,
  `registration_number` varchar(50) DEFAULT NULL,
  `index_number` varchar(50) DEFAULT NULL,
  `first_name` varchar(100) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `other_name` varchar(100) DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT 'Ugandan',
  `district` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `program` varchar(255) DEFAULT NULL,
  `level` varchar(50) DEFAULT NULL,
  `set_name` varchar(100) DEFAULT NULL COMMENT 'e.g. Set 25, Set 28',
  `intake_year` year(4) DEFAULT NULL,
  `intake_period` varchar(50) DEFAULT NULL,
  `status` enum('Active','Inactive','Graduated','Suspended','Withdrawn','deleted') NOT NULL DEFAULT 'Active',
  `password` varchar(255) DEFAULT NULL,
  `is_first_login` tinyint(1) NOT NULL DEFAULT 1,
  `password_changed` tinyint(1) NOT NULL DEFAULT 0,
  `profile_picture` varchar(500) DEFAULT NULL,
  `passport_photo` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `student_id` (`student_id`),
  UNIQUE KEY `student_number` (`student_number`),
  UNIQUE KEY `registration_number` (`registration_number`),
  KEY `idx_stu_name` (`full_name`),
  KEY `idx_stu_program` (`program`),
  KEY `idx_stu_set` (`set_name`),
  KEY `idx_stu_status` (`status`),
  KEY `idx_stu_phone` (`phone`),
  KEY `idx_stu_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `students`
--

LOCK TABLES `students` WRITE;
/*!40000 ALTER TABLE `students` DISABLE KEYS */;
/*!40000 ALTER TABLE `students` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `v_computer_availability`
--

DROP TABLE IF EXISTS `v_computer_availability`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `v_computer_availability` (
  `location` varchar(100) DEFAULT NULL,
  `total_computers` bigint(20) DEFAULT NULL,
  `online_count` decimal(23,0) DEFAULT NULL,
  `offline_count` decimal(23,0) DEFAULT NULL,
  `maintenance_count` decimal(23,0) DEFAULT NULL,
  `availability_percentage` decimal(29,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `v_computer_availability`
--

LOCK TABLES `v_computer_availability` WRITE;
/*!40000 ALTER TABLE `v_computer_availability` DISABLE KEYS */;
/*!40000 ALTER TABLE `v_computer_availability` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `volunteer_applications`
--

DROP TABLE IF EXISTS `volunteer_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `volunteer_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `skills` longtext DEFAULT NULL,
  `availability` longtext DEFAULT NULL,
  `motivation` longtext DEFAULT NULL,
  `experience` longtext DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending' COMMENT 'pending, reviewed, accepted, rejected, interviewed',
  `reviewed_by` int(11) DEFAULT NULL,
  `review_date` timestamp NULL DEFAULT NULL,
  `decision` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`),
  KEY `idx_status` (`status`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `volunteer_applications`
--

LOCK TABLES `volunteer_applications` WRITE;
/*!40000 ALTER TABLE `volunteer_applications` DISABLE KEYS */;
/*!40000 ALTER TABLE `volunteer_applications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `website_announcements`
--

DROP TABLE IF EXISTS `website_announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `website_announcements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` longtext NOT NULL,
  `category` varchar(100) DEFAULT NULL COMMENT 'General, Academic, Administrative, Event, etc.',
  `author` varchar(255) DEFAULT NULL COMMENT 'Director or staff name',
  `image_url` varchar(500) DEFAULT NULL,
  `featured` tinyint(1) DEFAULT 0 COMMENT 'Show on homepage',
  `status` varchar(50) DEFAULT 'published' COMMENT 'draft, published, archived',
  `views` int(11) DEFAULT 0,
  `published_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_featured` (`featured`),
  KEY `idx_category` (`category`),
  KEY `idx_published` (`published_at`),
  FULLTEXT KEY `idx_search` (`title`,`content`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `website_announcements`
--

LOCK TABLES `website_announcements` WRITE;
/*!40000 ALTER TABLE `website_announcements` DISABLE KEYS */;
/*!40000 ALTER TABLE `website_announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'igangaschool_ict'
--

--
-- Dumping routines for database 'igangaschool_ict'
--
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `AddColIfMissing` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
DELIMITER ;;
CREATE  PROCEDURE `AddColIfMissing`(IN `p_schema` VARCHAR(255), IN `p_table` VARCHAR(255), IN `p_col` VARCHAR(255), IN `p_def` TEXT)
BEGIN
    DECLARE cnt INT DEFAULT 0;
    SELECT COUNT(*) INTO cnt FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = p_schema AND TABLE_NAME = p_table AND COLUMN_NAME = p_col;
    IF cnt = 0 THEN
        SET @s = CONCAT('ALTER TABLE `', p_schema, '`.`', p_table, '` ADD COLUMN `', p_col, '` ', p_def);
        PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
    END IF;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `add_role_description_col_if_missing` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_general_ci */ ;
DELIMITER ;;
CREATE  PROCEDURE `add_role_description_col_if_missing`()
BEGIN
    DECLARE CONTINUE HANDLER FOR 1060 BEGIN END;
    ALTER TABLE staff_roles ADD COLUMN role_description TEXT AFTER role_name;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
/*!50003 DROP PROCEDURE IF EXISTS `MigratePayroll` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_unicode_ci */ ;
DELIMITER ;;
CREATE  PROCEDURE `MigratePayroll`()
BEGIN
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payroll_runs' AND COLUMN_NAME='total_paye') THEN
        ALTER TABLE `payroll_runs` ADD COLUMN `total_paye` DECIMAL(15,2) DEFAULT 0.00 AFTER `total_gross`;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payroll_runs' AND COLUMN_NAME='total_nssf') THEN
        ALTER TABLE `payroll_runs` ADD COLUMN `total_nssf` DECIMAL(15,2) DEFAULT 0.00 AFTER `total_paye`;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payroll_runs' AND COLUMN_NAME='run_date') THEN
        ALTER TABLE `payroll_runs` ADD COLUMN `run_date` DATE DEFAULT NULL AFTER `end_date`;
    END IF;
    -- Extend status enum
    ALTER TABLE `payroll_runs` MODIFY COLUMN `status` ENUM('draft','approved','processed','paid','completed','processing') DEFAULT 'draft';

    -- payroll_details: add housing_allowance, transport_allowance
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payroll_details' AND COLUMN_NAME='housing_allowance') THEN
        ALTER TABLE `payroll_details` ADD COLUMN `housing_allowance` DECIMAL(12,2) DEFAULT 0.00 AFTER `basic_salary`;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payroll_details' AND COLUMN_NAME='transport_allowance') THEN
        ALTER TABLE `payroll_details` ADD COLUMN `transport_allowance` DECIMAL(12,2) DEFAULT 0.00 AFTER `housing_allowance`;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payroll_details' AND COLUMN_NAME='`status`') THEN
        ALTER TABLE `payroll_details` ADD COLUMN `status` VARCHAR(20) DEFAULT 'calculated' AFTER `payment_status`;
    END IF;

    -- payroll_employees: add allowance columns
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payroll_employees' AND COLUMN_NAME='housing_allowance') THEN
        ALTER TABLE `payroll_employees` ADD COLUMN `housing_allowance` DECIMAL(12,2) DEFAULT 0.00 AFTER `basic_salary`;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payroll_employees' AND COLUMN_NAME='transport_allowance') THEN
        ALTER TABLE `payroll_employees` ADD COLUMN `transport_allowance` DECIMAL(12,2) DEFAULT 0.00 AFTER `housing_allowance`;
    END IF;

    -- salary_structures: add staff_id FK
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='salary_structures' AND COLUMN_NAME='staff_id') THEN
        ALTER TABLE `salary_structures` ADD COLUMN `staff_id` INT(11) DEFAULT NULL AFTER `id`;
        ALTER TABLE `salary_structures` ADD INDEX `idx_ss_staff_id` (`staff_id`);
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='salary_structures' AND COLUMN_NAME='housing_allowance') THEN
        ALTER TABLE `salary_structures` ADD COLUMN `housing_allowance` DECIMAL(12,2) DEFAULT 0.00 AFTER `base_salary`;
    END IF;
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='salary_structures' AND COLUMN_NAME='transport_allowance') THEN
        ALTER TABLE `salary_structures` ADD COLUMN `transport_allowance` DECIMAL(12,2) DEFAULT 0.00 AFTER `housing_allowance`;
    END IF;

    -- bursar_vat_reports: add created_at
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='bursar_vat_reports' AND COLUMN_NAME='created_at') THEN
        ALTER TABLE `bursar_vat_reports` ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `status`;
    END IF;

    -- bursar_withholding_tax: add period column
    IF NOT EXISTS (SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='bursar_withholding_tax' AND COLUMN_NAME='period') THEN
        ALTER TABLE `bursar_withholding_tax` ADD COLUMN `period` VARCHAR(20) DEFAULT NULL AFTER `tax_date`;
    END IF;

    -- payroll_approvals: extend level enum to include CEO, add comments support
    ALTER TABLE `payroll_approvals` MODIFY COLUMN `level` ENUM('HR','PayrollOfficer','Bursar','DirectorFinance','CEO') NOT NULL;
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-10 13:07:18
