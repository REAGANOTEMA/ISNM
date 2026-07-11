/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.6.23-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: igangaschool_website
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
-- Table structure for table `contact_submissions`
--

DROP TABLE IF EXISTS `contact_submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `contact_submissions` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `status` enum('unread','read','replied') DEFAULT 'unread',
  `notified` tinyint(1) DEFAULT 0,
  `replied_at` datetime DEFAULT NULL,
  `replied_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contact_submissions`
--

LOCK TABLES `contact_submissions` WRITE;
/*!40000 ALTER TABLE `contact_submissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `contact_submissions` ENABLE KEYS */;
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
-- Table structure for table `donations`
--

DROP TABLE IF EXISTS `donations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `donations` (
  `id` int(11) NOT NULL,
  `donor_name` varchar(200) NOT NULL,
  `donor_email` varchar(255) NOT NULL,
  `donor_phone` varchar(50) NOT NULL,
  `donor_address` varchar(500) DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `payment_provider` varchar(50) DEFAULT NULL,
  `transaction_reference` varchar(100) DEFAULT NULL,
  `purpose` varchar(200) DEFAULT 'General Donation',
  `notes` text DEFAULT NULL,
  `status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `notified` tinyint(1) DEFAULT 0,
  `acknowledged_at` datetime DEFAULT NULL,
  `acknowledged_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `donations`
--

LOCK TABLES `donations` WRITE;
/*!40000 ALTER TABLE `donations` DISABLE KEYS */;
/*!40000 ALTER TABLE `donations` ENABLE KEYS */;
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
INSERT INTO `medicine_stock` VALUES (1,'PARA001','Paracetamol','Acetaminophen','Painkiller','Tablet','500mg',NULL,NULL,200,'tablets',50,50.00,NULL,'UGX',NULL,'2027-12-31','Cabinet A1',0,'1-2 tablets every 4-6 hours as needed for pain/fever',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:46','2026-06-20 08:42:46'),(2,'IBU001','Ibuprofen','Ibuprofen','Anti-inflammatory','Tablet','400mg',NULL,NULL,150,'tablets',30,100.00,NULL,'UGX',NULL,'2027-10-31','Cabinet A1',0,'1 tablet 3 times daily after meals',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:46','2026-06-20 08:42:46'),(3,'AMOX001','Amoxicillin','Amoxicillin','Antibiotic','Capsule','500mg',NULL,NULL,100,'capsules',20,200.00,NULL,'UGX',NULL,'2027-08-31','Cabinet B1',1,'1 capsule 3 times daily for 7 days',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:46','2026-06-20 08:42:46'),(4,'CTM001','Chlorpheniramine','Chlorpheniramine Maleate','Allergy','Tablet','4mg',NULL,NULL,100,'tablets',20,50.00,NULL,'UGX',NULL,'2027-11-30','Cabinet A2',0,'1 tablet every 4-6 hours for allergies',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:46','2026-06-20 08:42:46'),(5,'ORS001','Oral Rehydration Salts','ORS','Other','Powder','20.5g/sachet',NULL,NULL,100,'sachets',30,500.00,NULL,'UGX',NULL,'2028-06-30','Cabinet C1',0,'Dissolve 1 sachet in 1L water, drink after each loose stool',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:46','2026-06-20 08:42:46'),(6,'ART001','Artemether/Lumefantrine','Coartem','Antimalarial','Tablet','20/120mg',NULL,NULL,60,'tablets',20,1500.00,NULL,'UGX',NULL,'2027-09-30','Cabinet B2',1,'4 tablets twice daily for 3 days',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:46','2026-06-20 08:42:46'),(7,'VITC001','Vitamin C','Ascorbic Acid','Vitamins','Tablet','500mg',NULL,NULL,300,'tablets',50,30.00,NULL,'UGX',NULL,'2028-12-31','Cabinet C1',0,'1 tablet daily for immune support',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:46','2026-06-20 08:42:46'),(8,'MET001','Metered Dose Inhaler','Salbutamol','Respiratory','Inhaler','100mcg/dose',NULL,NULL,10,'inhalers',3,15000.00,NULL,'UGX',NULL,'2027-06-30','Cabinet A3',1,'1-2 puffs as needed for asthma symptoms',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:46','2026-06-20 08:42:46'),(9,'ANT001','Antacid','Aluminum/Magnesium Hydroxide','Digestive','Tablet','500mg',NULL,NULL,200,'tablets',40,100.00,NULL,'UGX',NULL,'2027-11-30','Cabinet C1',0,'1-2 tablets after meals or when symptomatic',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:46','2026-06-20 08:42:46'),(10,'HYD001','Hydrocortisone Cream','Hydrocortisone','Dermatological','Cream','1%',NULL,NULL,20,'tubes',5,5000.00,NULL,'UGX',NULL,'2027-08-31','Cabinet D1',0,'Apply thin layer to affected area 2-3 times daily',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:46','2026-06-20 08:42:46'),(11,'DIA001','Diazepam','Diazepam','Painkiller','Tablet','5mg',NULL,NULL,30,'tablets',10,200.00,NULL,'UGX',NULL,'2026-12-31','Cabinet B2',1,'1 tablet at bedtime for anxiety or muscle spasms',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:46','2026-06-20 08:42:46'),(12,'BAN001','Bandages','Cotton Bandage','First Aid','Other','4 inches x 5 meters',NULL,NULL,50,'rolls',10,1500.00,NULL,'UGX',NULL,'2029-12-31','Shelf E1',0,'For wound dressing and injury management',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:46','2026-06-20 08:42:46'),(13,'GAU001','Gauze Swabs','Sterile Gauze','First Aid','Other','10x10cm',NULL,NULL,200,'packs',50,800.00,NULL,'UGX',NULL,'2029-12-31','Shelf E1',0,'Sterile swabs for wound cleaning and dressing',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:46','2026-06-20 08:42:46'),(14,'GLU001','Glucose Powder','Dextrose','Vitamins','Powder','500g',NULL,NULL,10,'packs',3,5000.00,NULL,'UGX',NULL,'2028-06-30','Cabinet C1',0,'Mix 2 tablespoons in water for energy',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:46','2026-06-20 08:42:46'),(15,'ALC001','Alcohol Swabs','Isopropyl Alcohol','First Aid','Solution','70%',NULL,NULL,300,'swabs',50,100.00,NULL,'UGX',NULL,'2028-12-31','Shelf E1',0,'Use for cleaning skin before injections',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:46','2026-06-20 08:42:46'),(16,'CLO001','Chloroquine','Chloroquine Phosphate','Antimalarial','Tablet','250mg',NULL,NULL,50,'tablets',15,300.00,NULL,'UGX',NULL,'2027-05-31','Cabinet B2',1,'As prescribed for malaria treatment',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:46','2026-06-20 08:42:46'),(17,'MEF001','Mefenamic Acid','Mefenamic Acid','Painkiller','Capsule','500mg',NULL,NULL,80,'capsules',20,200.00,NULL,'UGX',NULL,'2027-07-31','Cabinet A1',0,'1 capsule 3 times daily for pain and inflammation',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:46','2026-06-20 08:42:46'),(18,'METR001','Metronidazole','Metronidazole','Antibiotic','Tablet','400mg',NULL,NULL,100,'tablets',20,150.00,NULL,'UGX',NULL,'2027-09-30','Cabinet B1',1,'1 tablet 3 times daily for 5-7 days',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:46','2026-06-20 08:42:46'),(19,'DIC001','Diclofenac Gel','Diclofenac Diethylamine','Anti-inflammatory','Cream','1%',NULL,NULL,15,'tubes',5,7000.00,NULL,'UGX',NULL,'2027-10-31','Cabinet D1',0,'Apply to affected area 3-4 times daily',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:46','2026-06-20 08:42:46'),(20,'CET001','Cetirizine','Cetirizine Hydrochloride','Allergy','Tablet','10mg',NULL,NULL,100,'tablets',20,100.00,NULL,'UGX',NULL,'2027-12-31','Cabinet A2',0,'1 tablet daily for allergy symptoms',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:46','2026-06-20 08:42:46'),(21,'ASP001','Aspirin','Acetylsalicylic Acid','Painkiller','Tablet','300mg',NULL,NULL,100,'tablets',25,50.00,NULL,'UGX',NULL,'2027-06-30','Cabinet A1',0,'1-2 tablets every 4-6 hours for pain/fever',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:46','2026-06-20 08:42:46'),(22,'ZIN001','Zinc Tablets','Zinc Sulfate','Vitamins','Tablet','20mg',NULL,NULL,150,'tablets',30,100.00,NULL,'UGX',NULL,'2028-09-30','Cabinet C1',0,'1 tablet daily for immune support and wound healing',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:46','2026-06-20 08:42:46'),(23,'CLOT001','Clotrimazole Cream','Clotrimazole','Antifungal','Cream','1%',NULL,NULL,15,'tubes',5,4000.00,NULL,'UGX',NULL,'2027-08-31','Cabinet D1',0,'Apply to affected area twice daily for 2 weeks',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:46','2026-06-20 08:42:46'),(24,'EYE001','Eye Drops','Chloramphenicol','Other','Drops','0.5%',NULL,NULL,20,'bottles',5,5000.00,NULL,'UGX',NULL,'2027-04-30','Cabinet A3',1,'1-2 drops in affected eye every 2-4 hours',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:46','2026-06-20 08:42:46'),(25,'BET001','Betadine Solution','Povidone-Iodine','First Aid','Solution','10%',NULL,NULL,10,'bottles',3,8000.00,NULL,'UGX',NULL,'2028-03-31','Shelf E1',0,'Apply to wounds for disinfection',NULL,'In Stock',NULL,NULL,'2026-06-20 08:42:46','2026-06-20 08:42:46');
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
-- Table structure for table `news`
--

DROP TABLE IF EXISTS `news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `news` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `excerpt` text DEFAULT NULL,
  `featured_image` varchar(500) DEFAULT NULL,
  `author_id` int(11) DEFAULT NULL,
  `author_name` varchar(255) DEFAULT NULL,
  `author_role` varchar(255) DEFAULT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_news_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `news`
--

LOCK TABLES `news` WRITE;
/*!40000 ALTER TABLE `news` DISABLE KEYS */;
/*!40000 ALTER TABLE `news` ENABLE KEYS */;
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
-- Dumping events for database 'igangaschool_website'
--

--
-- Dumping routines for database 'igangaschool_website'
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
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-07-10 13:08:22
